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
            $stmt_get = $conn->prepare("SELECT br_id, pr_name, pr_date, pr_request FROM plan_request WHERE pr_id = ? AND pr_status = 0");
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
$cancelled_plans = []; // เพิ่มตัวแปรสำหรับเก็บแผน pr_status = 3

if (!empty($selected_routes)) {
    // 1. ดึงแผนที่ยืนยันแล้ว (status=1)
    $confirmed_plans = [];
    $sql_confirmed = "SELECT br_id, pr_name, pr_request FROM plan_request WHERE pr_date = ? AND pr_status = 1 AND br_id IN (" . implode(',', $selected_routes) . ")";
    $stmt_confirmed = $conn->prepare($sql_confirmed);
    $stmt_confirmed->bind_param('s', $plan_date);
    $stmt_confirmed->execute();
    $result_confirmed = $stmt_confirmed->get_result();
    while ($row = $result_confirmed->fetch_assoc()) {
        $plan_data = json_decode($row['pr_request'], true);
        // ใช้ ex จากใน pr_request เท่านั้น
        $confirmed_plans[$row['br_id']] = $plan_data;
    }
    $stmt_confirmed->close();

    // 2. ดึงแผนสำรองจากคิวมาตรฐาน
    $standard_queues = [];
    $sql_standard = "SELECT br_id, qr_name, qr_request FROM queue_request WHERE br_id IN (" . implode(',', $selected_routes) . ")";
    $result_standard = $conn->query($sql_standard);
    while ($row = $result_standard->fetch_assoc()) {
        $plan_data = json_decode($row['qr_request'], true);
        // ใช้ ex จากใน qr_request เท่านั้น
        $standard_queues[$row['br_id']] = $plan_data;
    }

    // 3. ดึงข้อมูลแผนที่รอยืนยัน (status=0)
    $pending_plans = [];
    $sql_pending = "SELECT pr_id, pr_name, br_id, pr_request FROM plan_request WHERE pr_date = ? AND pr_status = 0 AND br_id IN (" . implode(',', $selected_routes) . ")";
    $stmt_pending = $conn->prepare($sql_pending);
    $stmt_pending->bind_param('s', $plan_date);
    $stmt_pending->execute();
    $result_pending = $stmt_pending->get_result();
    while ($row = $result_pending->fetch_assoc()) {
        echo "1";
        $plan_data = json_decode($row['pr_request'], true);
        // ใช้ ex จากใน pr_request เท่านั้น
        $pending_plans[$row['br_id']] = [
            'pr_id' => $row['pr_id'],
            'pr_name' => $row['pr_name'],
            'data' => $plan_data
        ];
    }
    echo "<script>console.log('pending_plans:', " . json_encode($pending_plans) . ");</script>";
    $stmt_pending->close();

    // --- ดึงแผนที่ pr_status = 3 (cancelled) ของแต่ละสาย (br_id) ทุกวัน ---
    $sql_cancelled = "SELECT pr_id, pr_name, br_id, pr_date, pr_request FROM plan_request WHERE pr_status = 3 AND br_id IN (" . implode(',', $selected_routes) . ") ORDER BY pr_date DESC";
    $result_cancelled = $conn->query($sql_cancelled);
    while ($row = $result_cancelled->fetch_assoc()) {
        $br_id = $row['br_id'];
        if (!isset($cancelled_plans[$br_id])) $cancelled_plans[$br_id] = [];
        $plan_data = json_decode($row['pr_request'], true);
        // ใช้ ex จากใน pr_request เท่านั้น
        $cancelled_plans[$br_id][] = [
            'pr_id' => $row['pr_id'],
            'pr_name' => $row['pr_name'],
            'pr_date' => $row['pr_date'],
            'data' => $plan_data
        ];
    }

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

        $sql_point = "SELECT 
                        brk_in_route.br_id AS br_id,
                        brk_in_route.bir_time AS bir_time,
                        brk_in_route.brkp_id AS brkp_id,
                        break_point.brkp_name AS brkp_name,
                        brk_in_route.bir_type AS brkp_type,
                        brk_in_route.bir_status AS brkp_status
                FROM `brk_in_route` 
                LEFT JOIN 
                    break_point 
                        ON brk_in_route.brkp_id = break_point.brkp_id";

    $result_point = mysqli_query($conn, $sql_point);

    while($row = mysqli_fetch_assoc($result_point)) {
        $point[$row['br_id']][] = [
            'id' => $row['brkp_id'],
            'name' => $row['brkp_name'],
            'time' => $row['bir_time'],
            'status' => $row['brkp_status'],
            'type' => $row['brkp_type']
        ];
    }
// เพิ่ม encode $point สำหรับ JS
$point_json = json_encode($point);
$cancelled_plans_json = json_encode($cancelled_plans); // encode สำหรับ JS
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
        const pointData = <?php echo $point_json; ?>;
        const cancelledPlans = <?php echo $cancelled_plans_json; ?>; // เพิ่มข้อมูลแผน pr_status=3

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
                        time_plus: [...(dataToEdit.time_plus || [])],
                        stops: dataToEdit.stops ? dataToEdit.stops.map(arr => Array.isArray(arr) ? [...arr] : []) : (dataToEdit.point ? dataToEdit.point.map(arr => Array.isArray(arr) ? [...arr] : []) : []),
                        ex: Array.isArray(dataToEdit.ex) ? dataToEdit.ex.map(e => ({ ...e })) : []
                    };
                }
            });
        }

        // --- Modal Checklist จุดพัก ---
        function createPointChecklistPopup(br_id, idx, selectedPoints, disabledAttr) {
            const pts = pointData[br_id] || [];
            const requiredPoints = pts.filter(pt => pt.status == 1).map(pt => pt.id.toString());
            let mergedSelected = Array.isArray(selectedPoints) ? [...selectedPoints] : [];
            requiredPoints.forEach(val => {
                if (!mergedSelected.includes(val)) mergedSelected.push(val);
            });

            // ปรับข้อความบนปุ่ม
            let label = '';
            if (mergedSelected.length === 0) {
                label = 'เลือกจุดรับส่ง';
            } else if (
                pts.length > 0 &&
                pts.every(pt => mergedSelected.includes(pt.id.toString()))
            ) {
                label = 'เลือกครบทุกจุด';
            } else if (mergedSelected.length === 1) {
                const pt = pts.find(pt => pt.id.toString() === mergedSelected[0]);
                label = pt ? pt.name : (pts[0] ? pts[0].name : 'เลือกจุดรับส่ง');
            } else {
                const firstPt = pts.find(pt => pt.id.toString() === mergedSelected[0]);
                const firstName = firstPt ? firstPt.name : (pts[0] ? pts[0].name : '');
                label = `${firstName} และอีก ${mergedSelected.length - 1} จุด`;
            }

            // --- แก้ไขจุดนี้: input hidden ส่งเฉพาะ selectedPoints จริง ไม่ใช่ mergedSelected ---
            let html = `
                <button type="button" class="btn btn-outline-primary btn-sm w-100 text-truncate" data-bs-toggle="modal" data-bs-target="#pointModal_${br_id}_${idx}" ${disabledAttr}>
                    ${label}
                </button>
                <input type="hidden" name="point[${br_id}][]" value="${Array.isArray(selectedPoints) ? selectedPoints.join(',') : ''}" data-idx="${idx}">
                <!-- Modal -->
                <div class="modal fade" id="pointModal_${br_id}_${idx}" tabindex="-1" aria-labelledby="pointModalLabel_${br_id}_${idx}" aria-hidden="true">
                  <div class="modal-dialog modal-dialog-scrollable">
                    <div class="modal-content">
                      <div class="modal-header">
                        <h5 class="modal-title" id="pointModalLabel_${br_id}_${idx}">เลือกจุดรับส่ง (Route ${br_id} ลำดับ ${idx+1})</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                      </div>
                      <div class="modal-body">
                        <div class="mb-2 d-flex gap-2">
                          <button type="button" class="btn btn-sm btn-outline-success" onclick="selectAllPoints('${br_id}',${idx})" ${disabledAttr}>เลือกทั้งหมด</button>
                          <button type="button" class="btn btn-sm btn-outline-danger" onclick="clearAllPoints('${br_id}',${idx})" ${disabledAttr}>ล้างการเลือก</button>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
            `;
            pts.forEach((pt) => {
                const val = pt.id.toString();
                const checked = mergedSelected.includes(val) ? 'checked' : '';
                const disabled = pt.status == 1 ? 'disabled' : '';
                html += `<div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" id="point_${br_id}_${idx}_${val}" value="${val}" ${checked} ${disabled} ${disabledAttr}
                        onchange="onPointChecklistPopupChange('${br_id}',${idx},this)">
                    <label class="form-check-label" for="point_${br_id}_${idx}_${val}">${pt.name} (${pt.time} นาที)${pt.status == 1 ? ' <span class="text-danger">*</span>' : ''}</label>
                </div>`;
            });
            html += `
                        </div>
                      </div>
                      <div class="modal-footer">
                        <button type="button" class="btn btn-primary" onclick="confirmPointChecklistPopup('${br_id}',${idx})" ${disabledAttr}>ยืนยัน</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
                      </div>
                    </div>
                  </div>
                </div>
            `;
            return html;
        }

        // --- Modal Checklist Logic ---
        function selectAllPoints(br_id, idx) {
            const pts = pointData[br_id] || [];
            const allVals = pts.map(pt => pt.id.toString());
            editablePlans[br_id].stops = editablePlans[br_id].stops || [];
            editablePlans[br_id].stops[idx] = allVals;
            updateTimePlus(br_id, idx);
            // อัปเดต checkbox ใน modal เฉพาะ
            pts.forEach((pt) => {
                const val = pt.id.toString();
                const cb = document.getElementById(`point_${br_id}_${idx}_${val}`);
                if (cb) cb.checked = true;
            });
            // ไม่ต้อง renderTables() ทันที เพื่อไม่ให้ modal หาย
        }
        function clearAllPoints(br_id, idx) {
            const pts = pointData[br_id] || [];
            const requiredPoints = pts.filter(pt => pt.status == 1).map(pt => pt.id.toString());
            editablePlans[br_id].stops = editablePlans[br_id].stops || [];
            editablePlans[br_id].stops[idx] = [...requiredPoints];
            updateTimePlus(br_id, idx);
            // อัปเดต checkbox ใน modal เฉพาะ
            pts.forEach((pt) => {
                const val = pt.id.toString();
                const cb = document.getElementById(`point_${br_id}_${idx}_${val}`);
                if (cb) cb.checked = requiredPoints.includes(val);
            });
            // ไม่ต้อง renderTables() ทันที เพื่อไม่ให้ modal หาย
        }
        function onPointChecklistPopupChange(br_id, idx, checkboxElem) {
            editablePlans[br_id].stops = editablePlans[br_id].stops || [];
            let selected = editablePlans[br_id].stops[idx] || [];
            if (!Array.isArray(selected)) selected = [];
            const val = checkboxElem.value;
            const pts = pointData[br_id] || [];
            const requiredPoints = pts.filter(pt => pt.status == 1).map(pt => pt.id.toString());
            if (checkboxElem.disabled) return;
            if (checkboxElem.checked) {
                if (!selected.includes(val)) selected.push(val);
            } else {
                selected = selected.filter(v => v !== val);
            }
            requiredPoints.forEach(rid => {
                if (!selected.includes(rid)) selected.push(rid);
            });
            editablePlans[br_id].stops[idx] = selected;
            updateTimePlus(br_id, idx);
        }
        function confirmPointChecklistPopup(br_id, idx) {
            const modal = bootstrap.Modal.getInstance(document.getElementById(`pointModal_${br_id}_${idx}`));
            if (modal) modal.hide();
            const pts = pointData[br_id] || [];
            const requiredPoints = pts.filter(pt => pt.status == 1).map(pt => pt.id.toString());
            const selected = [];
            pts.forEach((pt) => {
                const val = pt.id.toString();
                const cb = document.getElementById(`point_${br_id}_${idx}_${val}`);
                if (cb && cb.checked) selected.push(val);
            });
            requiredPoints.forEach(rid => {
                if (!selected.includes(rid)) selected.push(rid);
            });
            editablePlans[br_id].stops[idx] = selected;
            updateTimePlus(br_id, idx);
            setTimeout(() => renderTables(), 0);
        }
        function updateTimePlus(br_id, idx) {
            const stops = editablePlans[br_id].stops[idx] || [];
            const pts = pointData[br_id] || [];
            let total = 0;
            (stops || []).forEach(val => {
                const pt = pts.find(pt => pt.id.toString() === val);
                if (pt) total += parseInt(pt.time);
            });
            if (!editablePlans[br_id].time_plus) editablePlans[br_id].time_plus = [];
            editablePlans[br_id].time_plus[idx] = total.toString();
        }

        // --- Toggling and Editing Logic ---
        function toggleSource(br_id, source) {
            displaySource[br_id] = source;
            initializeEditableData();
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

        function removeRow(br_id, type, idx) {
            let arr = editablePlans[br_id][type] || [];
            if (arr.length > 0) {
                arr.splice(idx, 1);
                if (type === 'request') {
                    (editablePlans[br_id].time || []).splice(idx, 1);
                    (editablePlans[br_id].time_plus || []).splice(idx, 1);
                    (editablePlans[br_id].stops || []).splice(idx, 1);
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
                editablePlans[br_id].time_plus.splice(insertIdx, 0, '0');
                if (!editablePlans[br_id].stops) editablePlans[br_id].stops = [];
                editablePlans[br_id].stops.splice(insertIdx, 0, []);
            }
            renderTables();
        }

        // ฟังก์ชัน normalize ex ให้เป็น array ของ object {start1:"", end1:"", start2:"", end2:""} (string id)
        function normalizeExData() {
            Object.entries(editablePlans).forEach(([br_id, obj]) => {
                if (!obj.ex) obj.ex = [];
                obj.ex = obj.ex.map(e => {
                    // ถ้า e เป็น array (แบบเก่า) หรือไม่ใช่ object ให้แปลงเป็น object ที่มี string
                    if (!e || typeof e !== 'object' || Array.isArray(e)) {
                        e = {start1:"", end1:"", start2:"", end2:""};
                    }
                    // แปลง array เป็น string ตัวแรก หรือ "" ถ้าไม่มีข้อมูล
                    const arrToStr = v => Array.isArray(v) ? (v.length > 0 ? v[0].toString() : "") : (v !== undefined ? v.toString() : "");
                    e.start1 = arrToStr(e.start1);
                    e.end1 = arrToStr(e.end1);
                    e.start2 = arrToStr(e.start2);
                    e.end2 = arrToStr(e.end2);
                    return e;
                });
            });
        }

        // ฟังก์ชันสร้าง select จุดจอดขึ้น/ลง สำหรับ ex driver (single-select, แยกคนที่ 1/2)
        function createExPointSelect(br_id, idx, selected, type, person) {
            // type: 'start' or 'end', person: 1 or 2
            // เลือกเฉพาะ point ที่ type == 2
            const pts = (pointData[br_id] || []).filter(pt => pt.type == 2);
            let html = `<select class="form-select" name="ex_${type}${person}[${br_id}][]" data-idx="${idx}" onchange="onExPointChange('${br_id}',${idx},this,'${type}${person}')">`;
            html += `<option value="">- ไม่เลือก -</option>`;
            pts.forEach((pt) => {
                const val = pt.id.toString();
                // selected เป็น string id
                const isSelected = (selected === val) ? 'selected' : '';
                html += `<option value="${val}" ${isSelected}>${pt.name}</option>`;
            });
            html += `</select>`;
            return html;
        }
  
        // --- ปรับ renderTables ให้ใช้ modal checklist จุดพัก ---
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
                let sourceText = '';
                if (currentSource === 'confirmed') {
                    sourceText = 'แผนยืนยันแล้ว';
                } else if (currentSource === 'special') {
                    sourceText = 'แผนที่บันทึกไว้';
                } else {
                    sourceText = 'คิวมาตรฐาน';
                }

                html += `<div class="card mb-4"><div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Route: ${br_id} (ที่มา: ${sourceText})</h5>
                        <div>`;

                if (originalPlan && originalPlan.confirmed && isEditable) {
                    html += `<div class="btn-group btn-group-sm me-2">
                                <button type="button" class="btn btn-light" onclick="toggleSource('${br_id}', 'standard')" ${currentSource === 'standard' ? 'disabled' : ''}>ใช้แผนมาตรฐาน</button>
                                <button type="button" class="btn btn-warning" onclick="toggleSource('${br_id}', 'confirmed')" ${currentSource === 'confirmed' ? 'disabled' : ''}>ใช้แผนยืนยัน</button>
                             </div>`;
                }

                // --- เพิ่มปุ่มเลือกแผน cancelled ---
                if ((cancelledPlans[br_id] || []).length > 0 && isEditable) {
                    html += `<button type="button" class="btn btn-outline-dark btn-sm me-2" onclick="showCancelledPlansSelector('${br_id}')">เลือกแผนที่บันทึกไว้</button>`;
                }

                if (originalPlan && originalPlan.pending) {
                    html += `<button type="button" class="btn btn-warning btn-sm" onclick="showCompareModal('${br_id}')">มีแผนใหม่รอยืนยัน</button>`;
                }
                html += `</div></div><div class="card-body"><div class="table-responsive">
                            <input type="hidden" name="source[${br_id}]" value="${sourceText}">
                            <table class="table table-sm table-bordered align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>ลำดับ</th>
                                    <th>Queue Request</th>
                                    <th>เวลา</th>
                                    <th>เวลาเดินทาง(จุดจอด)</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>`;
                
                const reqArr = plan_data.request || [];
                const stopsArr = plan_data.stops || [];
                const timeArr = plan_data.time || [];
                const exArr = plan_data.ex || [];

                reqArr.forEach((qr_request, idx) => {
                    const selectedPoints = stopsArr[idx] || [];
                    const timePlusVal = plan_data.time_plus && plan_data.time_plus[idx] ? plan_data.time_plus[idx] : '0';
                    const timeVal = timeArr[idx] || '';
                    const exObj = exArr[idx] || {start1:[], end1:[], start2:[], end2:[]};
                    html += `<tr>
                                <td>${idx + 1}</td>
                                <td>${createSelect(`request[${br_id}][]`, qr_request, routeOptions, br_id, 'request', idx)}</td>
                                <td>
                                    <input type="time" class="form-control form-control-sm" name="time[${br_id}][]" value="${timeVal}" ${disabledAttr}>
                                </td>
                                <td>
                                    ${createPointChecklistPopup(br_id, idx, selectedPoints, disabledAttr)}
                                    <input type="number" class="form-control mt-1" name="time_plus[${br_id}][]" value="${timePlusVal}" data-idx="${idx}" readonly>
                                </td>
                                    <td><div class="btn-group btn-group-sm">
                                        <button type='button' class="btn btn-outline-secondary" onclick="insertRow('${br_id}','request',${idx},'before')" ${disabledAttr}>แทรกก่อน</button>
                                        <button type='button' class="btn btn-outline-secondary" onclick="insertRow('${br_id}','request',${idx},'after')" ${disabledAttr}>แทรกหลัง</button>
                                        <button type='button' class="btn btn-outline-danger" onclick="removeRow('${br_id}','request',${idx})" ${disabledAttr}>ลบ</button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td></td>
                                <td colspan="5">
                                    <div class="row g-2 align-items-center">
                                        <div class="col-auto"><b>จุดจอดขึ้น (ex driver คนที่ 1):</b></div>
                                        <div class="col">${createExPointSelect(br_id, idx, exObj.start1, 'start', 1)}</div>
                                        <div class="col-auto"><b>จุดจอดลง (ex driver คนที่ 1):</b></div>
                                        <div class="col">${createExPointSelect(br_id, idx, exObj.end1, 'end', 1)}</div>
                                    </div>
                                    <div class="row g-2 align-items-center mt-2">
                                        <div class="col-auto"><b>จุดจอดขึ้น (ex driver คนที่ 2):</b></div>
                                        <div class="col">${createExPointSelect(br_id, idx, exObj.start2, 'start', 2)}</div>
                                        <div class="col-auto"><b>จุดจอดลง (ex driver คนที่ 2):</b></div>
                                        <div class="col">${createExPointSelect(br_id, idx, exObj.end2, 'end', 2)}</div>
                                    </div>
                                </td>
                            </tr>`;
                });

                // Add hidden inputs for reserve data
                const reserveArr = plan_data.reserve || [];
                reserveArr.forEach((qr_reserve, idx) => {
                    html += `<input type="hidden" name="reserve[${br_id}][]" value="${qr_reserve}">`;
                });

                if (isEditable) {
                    html += `<tr><td>ใหม่</td>
                        <td>${createSelect('', '2', routeOptions, br_id, 'request', reqArr.length)}</td>
                        <td></td>
                        <td>
                            ${createPointChecklistPopup(br_id, reqArr.length, [], disabledAttr)}
                            <input type="number" class="form-control mt-1" name="time_plus[${br_id}][]" value="0" data-idx="${reqArr.length}" readonly>
                        </td>
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

            // --- sync checkbox modal กับ selectedPoints ทุกครั้งที่ modal เปิด ---
            Object.entries(editablePlans).forEach(([br_id, plan_data]) => {
                const reqArr = plan_data.request || [];
                const stopsArr = plan_data.stops || [];
                reqArr.forEach((_, idx) => {
                    const modalId = `pointModal_${br_id}_${idx}`;
                    const modalElem = document.getElementById(modalId);
                    if (modalElem) {
                        modalElem.removeEventListener('shown.bs.modal', modalElem._syncPointsListener || (()=>{}));
                        const syncPointsListener = function() {
                            const selectedPoints = (stopsArr[idx] || []).map(String);
                            const pts = pointData[br_id] || [];
                            pts.forEach((pt) => {
                                const val = pt.id.toString();
                                const cb = document.getElementById(`point_${br_id}_${idx}_${val}`);
                                if (cb) cb.checked = selectedPoints.includes(val);
                            });
                        };
                        modalElem.addEventListener('shown.bs.modal', syncPointsListener);
                        modalElem._syncPointsListener = syncPointsListener;
                    }
                });
            });
        }

        // --- Modal Logic: เปรียบเทียบแผน/ยืนยัน/ปฏิเสธ ---
        const compareModal = new bootstrap.Modal(document.getElementById('compareModal'));

        function createStaticPlanTable(data) {
            const requests = data.request || [];
            const times = data.time || [];
            const time_pluses = data.time_plus || [];
            if (requests.length === 0) return '<p class="text-muted">ไม่มีข้อมูล</p>';
            let tableHtml = '<table class="table table-sm table-bordered"><thead><tr><th>ลำดับ</th><th>Code</th><th>เวลา</th><th>เวลาเดินทาง</th></tr></thead><tbody>';
            requests.forEach((req, idx) => {
                tableHtml += `<tr><td>${idx + 1}</td><td>${req}</td><td>${times[idx] || '-'}</td><td>${time_pluses[idx] || '0'} นาที</td></tr>`;
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

        // --- เพิ่มฟังก์ชันสำหรับเลือกแผนที่บันทึกไว้ (pr_status=3) พร้อมเปรียบเทียบก่อนยืนยัน ---
        function showCancelledPlansSelector(br_id) {
            const plans = cancelledPlans[br_id] || [];
            if (plans.length === 0) {
                alert('ไม่มีแผนที่บันทึกไว้สำหรับสายนี้');
                return;
            }
            let modalId = `cancelledPlansModal_${br_id}`;
            let modalElem = document.getElementById(modalId);
            if (modalElem) modalElem.remove();
            let html = `
            <div class="modal fade" id="${modalId}" tabindex="-1" aria-labelledby="${modalId}_label" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="${modalId}_label">เลือกแผนที่บันทึกไว้ (Route ${br_id})</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="list-group mb-3">
                            ${plans.map((plan, idx) => `
                                <button type="button" class="list-group-item list-group-item-action"
                                    onclick="showCompareSpecialPlan('${br_id}', ${idx})">
                                    วันที่: ${plan.pr_name}</span>
                                </button>
                            `).join('')}
                        </div>
                        <div id="specialPlanCompare_${br_id}"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
                    </div>
                    </div>
                </div>
            </div>
            `;
            document.body.insertAdjacentHTML('beforeend', html);
            let modal = new bootstrap.Modal(document.getElementById(modalId));
            modal.show();
        }

        // เปรียบเทียบแผนปัจจุบันกับแผนพิเศษที่เลือก และให้กดยืนยันเพื่อใช้งาน
        function showCompareSpecialPlan(br_id, idx) {
            const plans = cancelledPlans[br_id] || [];
            const plan = plans[idx];
            if (!plan) return;
            // แผนปัจจุบัน
            const currentPlan = editablePlans[br_id];

            // ฟังก์ชันแสดงจุดจอด
            function renderStops(stopsArr, br_id_for_stops) {
                if (!Array.isArray(stopsArr)) return '-';
                const pts = pointData[br_id_for_stops] || [];
                return stopsArr.map((stopList, i) => {
                    if (!Array.isArray(stopList)) return '-';
                    const names = stopList.map(id => {
                        const pt = pts.find(p => String(p.id) === String(id));
                        return pt ? pt.name : id;
                    });
                    return `<div><span class="badge bg-secondary">${i + 1}</span> ${names.join(', ')}</div>`;
                }).join('');
            }

            // ฟังก์ชันสร้างตาราง
            function createStaticPlanTable(data, br_id_for_stops) {
                const requests = data.request || [];
                const times = data.time || [];
                const time_pluses = data.time_plus || [];
                // รองรับ stops ทั้งแบบ stops และ point (สำหรับแผนเก่า)
                const stops = Array.isArray(data.stops) ? data.stops : (Array.isArray(data.point) ? data.point : []);
                if (requests.length === 0) return '<p class="text-muted">ไม่มีข้อมูล</p>';
                let tableHtml = '<table class="table table-sm table-bordered"><thead><tr><th>ลำดับ</th><th>Code</th><th>เวลา</th><th>เวลาเดินทาง</th><th>จุดจอด</th></tr></thead><tbody>';
                requests.forEach((req, idx) => {
                    tableHtml += `<tr>
                        <td>${idx + 1}</td>
                        <td>${req}</td>
                        <td>${times[idx] || '-'}</td>
                        <td>${time_pluses[idx] || '0'} นาที</td>
                        <td>${renderStops([stops[idx]], br_id_for_stops)}</td>
                    </tr>`;
                });
                tableHtml += '</tbody></table>';
                return tableHtml;
            }

            // HTML เปรียบเทียบ
            let html = `
                <div class="row">
                    <div class="col-md-6">
                        <h6>แผนปัจจุบัน</h6>
                        <div class="table-responsive">${createStaticPlanTable(currentPlan, br_id)}</div>
                    </div>
                    <div class="col-md-6">
                        <h6>แผนที่บันทึกไว้ (${plan.pr_name})</h6>
                        <div class="table-responsive">${createStaticPlanTable(plan.data, br_id)}</div>
                    </div>
                </div>
                <div class="mt-3 text-end">
                    <button type="button" class="btn btn-success" onclick="confirmUseSpecialPlan('${br_id}', ${idx})">ยืนยันการใช้แผนนี้</button>
                </div>
            `;
            document.getElementById(`specialPlanCompare_${br_id}`).innerHTML = html;
        }

        

        // เมื่อกดยืนยันการใช้แผนพิเศษ
        function confirmUseSpecialPlan(br_id, idx) {
            const plan = cancelledPlans[br_id][idx];
            if (!plan) return;
            if (!confirm('คุณต้องการใช้แผนนี้แทนแผนปัจจุบันหรือไม่?')) return;
            editablePlans[br_id] = {
                request: [...(plan.data.request || [])],
                reserve: [...(plan.data.reserve || [])],
                time: [...(plan.data.time || [])],
                time_plus: [...(plan.data.time_plus || [])],
                stops: plan.data.stops ? plan.data.stops.map(arr => Array.isArray(arr) ? [...arr] : []) : (plan.data.point ? plan.data.point.map(arr => Array.isArray(arr) ? [...arr] : []) : [])
            };
            // เปลี่ยนที่มาเป็น "แผนที่บันทึกไว้"
            displaySource[br_id] = 'special';
            // ปิด modal
            const modalElem = document.getElementById(`cancelledPlansModal_${br_id}`);
            if (modalElem) {
                const modal = bootstrap.Modal.getInstance(modalElem);
                if (modal) modal.hide();
                setTimeout(() => modalElem.remove(), 500);
            }
            renderTables();
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
            // กำหนด stops array ถ้ายังไม่มี
            Object.keys(editablePlans).forEach(br_id => {
                if (!editablePlans[br_id].stops) {
                    editablePlans[br_id].stops = [];
                }
            });
            renderTables();

            // --- ลบ input ของแถว "ใหม่" ออกจากฟอร์มก่อน submit ---
            document.addEventListener('submit', function(e) {

                if (e.target && e.target.id === 'edit-plan-form') {
                    normalizeExData(); // แปลงข้อมูล ex ก่อน submit
                    Object.entries(editablePlans).forEach(([br_id, obj]) => {
                        const reqArr = obj.request || [];
                        const form = e.target;
                        // ลบ input[name="request[br_id][]"] ของแถวใหม่
                        let inputs = form.querySelectorAll(`select[name="request[${br_id}][]"]`);
                        if (inputs.length > reqArr.length) {
                            inputs[inputs.length - 1].remove();
                        }
                        // ลบ input[name="time[br_id][]"] ของแถวใหม่
                        inputs = form.querySelectorAll(`input[name="time[${br_id}][]"]`);
                        if (inputs.length > reqArr.length) {
                            inputs[inputs.length - 1].remove();
                        }
                        // ลบ input[name="time_plus[br_id][]"] ของแถวใหม่
                        inputs = form.querySelectorAll(`input[name="time_plus[${br_id}][]"]`);
                        if (inputs.length > reqArr.length) {
                            inputs[inputs.length - 1].remove();
                        }
                        // ลบ input[name="point[br_id][]"] ของแถวใหม่
                        inputs = form.querySelectorAll(`input[name="point[${br_id}][]"]`);
                        if (inputs.length > reqArr.length) {
                            inputs[inputs.length - 1].remove();
                        }
                    });
                }
            });
        });
    </script>
</body>