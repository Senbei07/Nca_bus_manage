<?php
// เชื่อมต่อฐานข้อมูล
include 'config.php';

// ตรวจสอบการเชื่อมต่อฐานข้อมูล
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// --- จัดการการยืนยัน/ยกเลิก/ย้อนกลับ แผน (POST Request) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $redirect_url = "confirm_plan.php?" . http_build_query($_GET);

    if ($action === 'revert' && isset($_POST['br_id'], $_POST['date'])) {
        // Action: Revert a confirmed plan back to standard
        $br_id_to_revert = (int)$_POST['br_id'];
        $date_to_revert = $_POST['date'];

        // Change status of confirmed plan (status=1) to old (status=2)
        $stmt_revert = $conn->prepare("UPDATE plan_request SET pr_status = 2 WHERE br_id = ? AND pr_date = ? AND pr_status = 1");
        $stmt_revert->bind_param('is', $br_id_to_revert, $date_to_revert);
        $stmt_revert->execute();
        $stmt_revert->close();
        
        header("Location: " . $redirect_url);
        exit;

    } elseif (($action === 'confirm' || $action === 'cancel') && isset($_POST['pr_id'])) {
        // Action: Confirm or Cancel a pending plan
        $pr_id_to_action = (int)$_POST['pr_id'];

        $conn->begin_transaction();
        try {
            // ดึงข้อมูลแผนที่จะดำเนินการ (ต้องมีสถานะ 0 คือรอยืนยัน)
            $stmt_get = $conn->prepare("SELECT br_id, pr_date, pr_request FROM plan_request WHERE pr_id = ? AND pr_status = 0");
            $stmt_get->bind_param('i', $pr_id_to_action);
            $stmt_get->execute();
            $plan = $stmt_get->get_result()->fetch_assoc();
            $stmt_get->close();

            if (!$plan) throw new Exception("ไม่พบแผนที่ต้องการดำเนินการ หรืออาจถูกดำเนินการไปแล้ว");

            $br_id = $plan['br_id'];
            $pr_date = $plan['pr_date'];
            $pr_request_json = $plan['pr_request'];

            if ($action === 'confirm') {
                // 1. ทำให้แผนที่เคยยืนยันแล้ว (status=1) กลายเป็นแผนเก่า (status=2)
                $stmt_update_old = $conn->prepare("UPDATE plan_request SET pr_status = 2 WHERE br_id = ? AND pr_date = ? AND pr_status = 1");
                $stmt_update_old->bind_param('is', $br_id, $pr_date);
                $stmt_update_old->execute();
                $stmt_update_old->close();

                // 2. อัปเดตสถานะแผนใหม่ที่เพิ่งยืนยันเป็น 1 (ยืนยันแล้ว)
                $stmt_confirm = $conn->prepare("UPDATE plan_request SET pr_status = 1 WHERE pr_id = ?");
                $stmt_confirm->bind_param('i', $pr_id_to_action);
                $stmt_confirm->execute();
                $stmt_confirm->close();

            } elseif ($action === 'cancel') {
                // ยกเลิกแผน: เปลี่ยนสถานะเป็น 3 (ไม่ใช้งาน/ปฏิเสธ)
                $stmt_cancel = $conn->prepare("UPDATE plan_request SET pr_status = 3 WHERE pr_id = ? AND pr_status = 0");
                $stmt_cancel->bind_param('i', $pr_id_to_action);
                $stmt_cancel->execute();
                $stmt_cancel->close();
            }
            
            $conn->commit();
        } catch (Exception $e) {
            $conn->rollback();
            die("เกิดข้อผิดพลาด: " . $e->getMessage());
        }
        
        // Redirect กลับไปหน้าเดิมเพื่อรีเฟรชข้อมูล
        header("Location: " . $redirect_url);
        exit;
    }
}


// --- การจัดการ Input และ Filter ---
$plan_date = $_GET['date'] ?? date('Y-m-d');

// Check if the selected date is in the past or today
$today = new DateTime();
$today->setTime(0, 0, 0);
$selected_date_obj = new DateTime($plan_date);
$is_editable = ($selected_date_obj > $today);

$all_routes = [];
$sql_all_routes = "SELECT DISTINCT br_id FROM `queue_request` ORDER BY br_id";
$result_all_routes = $conn->query($sql_all_routes);
while ($row = $result_all_routes->fetch_assoc()) {
    $all_routes[] = $row['br_id'];
}

$selected_routes = isset($_GET['routes']) && is_array($_GET['routes']) ? $_GET['routes'] : $all_routes;
$selected_routes = array_intersect($selected_routes, $all_routes);

// --- การดึงข้อมูล ---
$plans = [];

if (!empty($selected_routes)) {
    // 1. ดึงแผนที่ยืนยันแล้ว (status=1)
    $confirmed_plans = [];
    $sql_confirmed = "SELECT br_id, pr_request FROM plan_request WHERE pr_date = ? AND pr_status = 1 AND br_id IN (" . implode(',', $selected_routes) . ")";
    $stmt_confirmed = $conn->prepare($sql_confirmed);
    $stmt_confirmed->bind_param('s', $plan_date);
    $stmt_confirmed->execute();
    $result_confirmed = $stmt_confirmed->get_result();
    while ($row = $result_confirmed->fetch_assoc()) {
        $confirmed_plans[$row['br_id']] = json_decode($row['pr_request'], true);
    }
    $stmt_confirmed->close();

    // 2. ดึงแผนสำรองจากคิวมาตรฐาน
    $standard_queues = [];
    $sql_standard = "SELECT br_id, qr_request FROM queue_request WHERE br_id IN (" . implode(',', $selected_routes) . ")";
    $result_standard = $conn->query($sql_standard);
    while ($row = $result_standard->fetch_assoc()) {
        $standard_queues[$row['br_id']] = json_decode($row['qr_request'], true);
    }

    // 3. ดึงข้อมูลแผนที่รอยืนยัน (status=0)
    $pending_plans = [];
    $sql_pending = "SELECT pr_id, br_id, pr_request FROM plan_request WHERE pr_date = ? AND pr_status = 0 AND br_id IN (" . implode(',', $selected_routes) . ")";
    $stmt_pending = $conn->prepare($sql_pending);
    $stmt_pending->bind_param('s', $plan_date);
    $stmt_pending->execute();
    $result_pending = $stmt_pending->get_result();
    while ($row = $result_pending->fetch_assoc()) {
        $pending_plans[$row['br_id']] = [
            'pr_id' => $row['pr_id'],
            'data' => json_decode($row['pr_request'], true)
        ];
    }
    $stmt_pending->close();

    // 4. รวมข้อมูล
    foreach ($selected_routes as $br_id) {
        if (isset($standard_queues[$br_id])) {
             $plans[$br_id] = [
                'standard' => $standard_queues[$br_id],
                'confirmed' => $confirmed_plans[$br_id] ?? null,
                'pending' => $pending_plans[$br_id] ?? null
            ];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ยืนยันแผนการเดินรถ</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Choices.js CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css"/>
</head>
<body class="bg-light">
    <div class="container py-4">
        <h1 class="mb-4">ภาพรวมและแก้ไขแผนการเดินรถ</h1>

        <!-- Filter Form -->
        <div class="card mb-4">
            <div class="card-header">ตัวกรอง</div>
            <div class="card-body">
                <form action="" method="get" id="filter-form" class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label for="date-select" class="form-label"><strong>เลือกวันที่:</strong></label>
                        <input type="date" id="date-select" name="date" class="form-control" value="<?php echo htmlspecialchars($plan_date); ?>">
                    </div>
                    <div class="col-md-8">
                        <label for="route-select" class="form-label"><strong>เลือกสาย:</strong></label>
                        <select class="form-control" name="routes[]" id="route-select" multiple>
                            <?php foreach ($all_routes as $r): ?>
                                <option value="<?php echo $r; ?>" <?php echo in_array($r, $selected_routes) ? 'selected' : ''; ?>>
                                    สาย <?php echo $r; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>
            </div>
        </div>

        <!-- Plan Tables -->
        <div id="plan-tables"></div>
    </div>

    <!-- Comparison Modal -->
    <div class="modal fade" id="compareModal" tabindex="-1" aria-labelledby="compareModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="compareModalLabel">เปรียบเทียบแผน Route: </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 id="active-plan-title">แผนปัจจุบัน</h6>
                            <div class="table-responsive" id="active-plan-table"></div>
                        </div>
                        <div class="col-md-6">
                            <h6>แผนใหม่ (รอยืนยัน)</h6>
                            <div class="table-responsive" id="pending-plan-table"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <form id="modal-action-form" method="POST" action="">
                        <input type="hidden" name="pr_id" id="modal-pr-id">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
                        <button type="submit" name="action" value="cancel" class="btn btn-danger">ปฏิเสธแผนใหม่</button>
                        <button type="submit" name="action" value="confirm" class="btn btn-primary">ยืนยันแผนใหม่</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- JS Libraries -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>

    <script>
        // --- Data from PHP ---
        const allPlansData = <?php echo json_encode($plans); ?>;
        const planDate = '<?php echo $plan_date; ?>';
        const isEditable = <?php echo json_encode($is_editable); ?>;

        // --- State Management ---
        let editablePlans = {};
        let displaySource = {}; // { br_id: 'standard' | 'confirmed' }

        // --- Initialization ---
        function initializeEditableData() {
            editablePlans = {};
            Object.keys(allPlansData).forEach(br_id => {
                // Set initial display source: 'confirmed' if available, otherwise 'standard'
                if (!displaySource[br_id]) {
                    displaySource[br_id] = allPlansData[br_id].confirmed ? 'confirmed' : 'standard';
                }
                
                const source = displaySource[br_id];
                const dataToEdit = allPlansData[br_id][source];

                // Deep copy the data for editing
                if (dataToEdit) {
                    editablePlans[br_id] = {
                        request: [...(dataToEdit.request || [])],
                        reserve: [...(dataToEdit.reserve || [])],
                        time: [...(dataToEdit.time || [])],
                        time_plus: [...(dataToEdit.time_plus || [])]
                    };
                }
            });
        }

        // --- Modal Logic ---
        const compareModal = new bootstrap.Modal(document.getElementById('compareModal'));

        function createStaticPlanTable(data) {
            const requests = data.request || [];
            const times = data.time || [];
            const time_pluses = data.time_plus || [];
            if (requests.length === 0) return '<p class="text-muted">ไม่มีข้อมูล</p>';
            let tableHtml = '<table class="table table-sm table-bordered"><thead><tr><th>ลำดับ</th><th>Code</th><th>เวลา</th><th>เวลาเดินทาง</th></tr></thead><tbody>';
            requests.forEach((req, idx) => {
                tableHtml += `<tr><td>${idx + 1}</td><td>${req}</td><td>${times[idx] || '-'}</td><td>${time_pluses[idx] || '90'} นาที</td></tr>`;
            });
            tableHtml += '</tbody></table>';
            return tableHtml;
        }

        function showCompareModal(br_id) {
            const planData = allPlansData[br_id];
            if (!planData || !planData.pending) return;
            
            const activePlanSource = displaySource[br_id];
            const activePlanData = allPlansData[br_id][activePlanSource];
            const sourceText = activePlanSource === 'confirmed' ? 'แผนยืนยันแล้ว' : 'คิวมาตรฐาน';

            document.getElementById('compareModalLabel').innerText = `เปรียบเทียบแผน Route: ${br_id}`;
            document.getElementById('active-plan-title').innerText = `แผนปัจจุบัน (ที่มา: ${sourceText})`;
            document.getElementById('active-plan-table').innerHTML = createStaticPlanTable(activePlanData);
            document.getElementById('pending-plan-table').innerHTML = createStaticPlanTable(planData.pending.data);
            document.getElementById('modal-pr-id').value = planData.pending.pr_id;
            document.getElementById('modal-action-form').onsubmit = () => confirm('คุณแน่ใจหรือไม่?');
            compareModal.show();
        }

        // --- Toggling and Editing Logic ---
        function toggleSource(br_id, source) {
            displaySource[br_id] = source;
            initializeEditableData(); // Re-initialize data based on new source
            renderTables();
        }

        function getAllCodeOptions(plans) {
            const groupMap = {};
            Object.entries(plans).forEach(([br_id, obj]) => {
                if (!groupMap[br_id]) groupMap[br_id] = [];
                (obj.request || []).forEach((req, i, arr) => {
                    groupMap[br_id].push({ value: `${br_id}-3-${i === arr.length - 1 ? 'last' : i + 1}`, label: `${br_id}-3-${i === arr.length - 1 ? 'last' : i + 1}` });
                });
                (obj.reserve || []).forEach((res, i) => {
                    groupMap[br_id].push({ value: `${br_id}-1-${i + 1}`, label: `${br_id}-1-${i + 1}` });
                });
            });
            groupMap['อื่นๆ'] = [{ value: '0', label: '0' }, { value: '1', label: '1' }, { value: '2', label: '2' }];
            return groupMap;
        }

        function createSelect(name, selected, routeOptions, br_id, type, idx) {
            const disabledAttr = isEditable ? '' : 'disabled';
            let html = `<select name="${name}" class="form-select form-select-sm" onchange="onQueueChange('${br_id}','${type}',${idx},this)" ${disabledAttr}>`;
            Object.entries(routeOptions).forEach(([group, opts]) => {
                html += `<optgroup label="${group}">`;
                opts.forEach(opt => {
                    html += `<option value="${opt.value}" ${opt.value === selected ? 'selected' : ''}>${opt.label}</option>`;
                });
                html += `</optgroup>`;
            });
            return html + '</select>';
        }

        function onQueueChange(br_id, type, idx, selectElem) {
            editablePlans[br_id][type][idx] = selectElem.value;
            renderTables();
        }

        function onTimeChange(br_id, idx, inputElem) {
            if (!editablePlans[br_id].time) editablePlans[br_id].time = [];
            editablePlans[br_id].time[idx] = inputElem.value;
        }

        function onTimePlusChange(br_id, idx, inputElem) {
            if (!editablePlans[br_id].time_plus) editablePlans[br_id].time_plus = [];
            editablePlans[br_id].time_plus[idx] = inputElem.value;
        }

        function removeRow(br_id, type, idx) {
            let arr = editablePlans[br_id][type] || [];
            if (arr.length > 0) {
                arr.splice(idx, 1);
                if (type === 'request') {
                    (editablePlans[br_id].time || []).splice(idx, 1);
                    (editablePlans[br_id].time_plus || []).splice(idx, 1);
                }
                renderTables();
            }
        }

        function insertRow(br_id, type, idx, pos) {
            let arr = editablePlans[br_id][type] || [];
            let insertIdx = pos === 'before' ? idx : idx + 1;
            arr.splice(insertIdx, 0, '2');
            if (type === 'request') {
                if (!editablePlans[br_id].time) editablePlans[br_id].time = [];
                const now = new Date();
                now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
                editablePlans[br_id].time.splice(insertIdx, 0, now.toISOString().slice(11, 16));
                if (!editablePlans[br_id].time_plus) editablePlans[br_id].time_plus = [];
                editablePlans[br_id].time_plus.splice(insertIdx, 0, '90');
            }
            renderTables();
        }

        function renderTables() {
            const container = document.getElementById('plan-tables');
            const routeOptions = getAllCodeOptions(editablePlans);
            let html = `<form method='post' action='confirm_plan_db.php' id='edit-plan-form'>`;
            html += `<input type="hidden" name="plan_date" value="${planDate}">`;

            if (Object.keys(editablePlans).length === 0) {
                container.innerHTML = '<div class="alert alert-info">ไม่พบข้อมูลสำหรับสายที่เลือก</div>';
                return;
            }

            const disabledAttr = isEditable ? '' : 'disabled';

            Object.entries(editablePlans).forEach(([br_id, plan_data]) => {
                const originalPlan = allPlansData[br_id];
                const currentSource = displaySource[br_id];
                const sourceText = currentSource === 'confirmed' ? 'แผนยืนยันแล้ว' : 'คิวมาตรฐาน';

                html += `<div class="card mb-4"><div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Route: ${br_id} (ที่มา: ${sourceText})</h5>
                        <div>`;

                if (originalPlan.confirmed && isEditable) {
                    html += `<div class="btn-group btn-group-sm me-2">
                                <button type="button" class="btn btn-light" onclick="toggleSource('${br_id}', 'standard')" ${currentSource === 'standard' ? 'disabled' : ''}>ใช้แผนมาตรฐาน</button>
                                <button type="button" class="btn btn-warning" onclick="toggleSource('${br_id}', 'confirmed')" ${currentSource === 'confirmed' ? 'disabled' : ''}>ใช้แผนยืนยัน</button>
                             </div>`;
                }
                
                if (originalPlan.pending) {
                    html += `<button type="button" class="btn btn-warning btn-sm" onclick="showCompareModal('${br_id}')">มีแผนใหม่รอยืนยัน</button>`;
                }
                html += `</div></div><div class="card-body"><div class="table-responsive">
                            <input type="hidden" name="source[${br_id}]" value="${sourceText}">
                            <table class="table table-sm table-bordered align-middle">
                            <thead class="table-light"><tr><th>ลำดับ</th><th>Queue Request</th><th>เวลา</th><th>เวลาเดินทาง (นาที)</th><th>Action</th></tr></thead><tbody>`;
                
                const reqArr = plan_data.request || [];
                reqArr.forEach((qr_request, idx) => {
                    html += `<tr><td>${idx + 1}</td>
                        <td>${createSelect(`request[${br_id}][]`, qr_request, routeOptions, br_id, 'request', idx)}</td>
                        <td><input type="time" class="form-control form-control-sm" name="time[${br_id}][]" value="${(plan_data.time || [])[idx] || ''}" onchange="onTimeChange('${br_id}', ${idx}, this)" ${disabledAttr}></td>
                        <td><input type="number" class="form-control form-control-sm" name="time_plus[${br_id}][]" value="${(plan_data.time_plus || [])[idx] || '90'}" onchange="onTimePlusChange('${br_id}', ${idx}, this)" ${disabledAttr} min="0"></td>
                        <td><div class="btn-group btn-group-sm">
                            <button type='button' class="btn btn-outline-secondary" onclick="insertRow('${br_id}','request',${idx},'before')" ${disabledAttr}>แทรกก่อน</button>
                            <button type='button' class="btn btn-outline-secondary" onclick="insertRow('${br_id}','request',${idx},'after')" ${disabledAttr}>แทรกหลัง</button>
                            <button type='button' class="btn btn-outline-danger" onclick="removeRow('${br_id}','request',${idx})" ${disabledAttr}>ลบ</button>
                        </div></td></tr>`;
                });

                // Add hidden inputs for reserve data
                const reserveArr = plan_data.reserve || [];
                reserveArr.forEach((qr_reserve, idx) => {
                    html += `<input type="hidden" name="reserve[${br_id}][]" value="${qr_reserve}">`;
                });

                if (isEditable) {
                    html += `<tr><td>ใหม่</td>
                        <td>${createSelect('', '2', routeOptions, br_id, 'request', reqArr.length)}</td>
                        <td><input type="time" class="form-control form-control-sm" name="time[${br_id}][]" onchange="onTimeChange('${br_id}', ${reqArr.length}, this)"></td>
                        <td><input type="number" class="form-control form-control-sm" name="time_plus[${br_id}][]" value="90" onchange="onTimePlusChange('${br_id}', ${reqArr.length}, this)" min="0"></td>
                        <td><button type='button' class="btn btn-success btn-sm" onclick="insertRow('${br_id}','request',${reqArr.length - 1},'after')">เพิ่ม</button></td>
                    </tr>`;
                }
                html += `</tbody></table></div></div></div>`;
            });

            if (isEditable) {
                html += `<div class='my-3'><button type='submit' class="btn btn-success btn-lg w-100">บันทึกเป็นแผนใหม่ (ส่งเพื่อยืนยัน)</button></div>`;
            }
            html += `</form>`;
            container.innerHTML = html;
        }

        // --- Main Execution ---
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('filter-form');
            const dateSelect = document.getElementById('date-select');
            const routeSelectEl = document.getElementById('route-select');

            const choices = new Choices(routeSelectEl, {
                removeItemButton: true, placeholder: true, placeholderValue: 'เลือกสาย...', searchPlaceholderValue: 'ค้นหาสาย...',
            });

            dateSelect.addEventListener('change', () => form.submit());
            routeSelectEl.addEventListener('change', () => form.submit());

            initializeEditableData();
            renderTables();
        });
    </script>
</body>
</html>