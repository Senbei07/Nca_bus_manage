<?php 
    // เชื่อมต่อฐานข้อมูล
    include 'config.php';

    // ตรวจสอบการเชื่อมต่อฐานข้อมูล
    if (!$conn) {
        die("Connection failed: " . mysqli_connect_error());
    }

    // 1. ดึง br_id ทั้งหมดที่มีในตารางเพื่อสร้างตัวกรอง
    $all_routes = [];
    $sql_all_routes = "SELECT DISTINCT br_id FROM `queue_request` ORDER BY br_id";
    $result_all_routes = mysqli_query($conn, $sql_all_routes);
    while ($row = mysqli_fetch_assoc($result_all_routes)) {
        $all_routes[] = $row['br_id'];
    }

    // 2. กำหนดสายที่ต้องการดึงข้อมูลจาก GET parameter, ถ้าไม่มีให้ใช้สายแรกเป็นค่าเริ่มต้น
    $route = isset($_GET['routes']) && is_array($_GET['routes']) ? $_GET['routes'] : ([]);
    // Ensure that only valid routes are processed
    $route = array_intersect($route, $all_routes);

    // 3. สร้าง array สำหรับเก็บข้อมูล request/reserve ของแต่ละ br_id ที่เลือก
    $request = [];

    if (!empty($route)) {
        // ดึงข้อมูล queue_request ของสายที่เลือกจากฐานข้อมูล
        $sql_request = "SELECT * FROM `queue_request` WHERE br_id IN (" . implode(',', $route) . ") ORDER BY br_id";
        $result_request = mysqli_query($conn, $sql_request);

        while ($row = mysqli_fetch_assoc($result_request)) {
            $qr_request = json_decode($row['qr_request'], true);
            $request[$row['br_id']]['request'] = isset($qr_request['request']) ? $qr_request['request'] : [];
            $request[$row['br_id']]['reserve'] = isset($qr_request['reserve']) ? $qr_request['reserve'] : [];
            
            // ปรับการดึงข้อมูลเป็น time
            $request[$row['br_id']]['time'] = isset($qr_request['time']) ? $qr_request['time'] : [];
            $request[$row['br_id']]['time_plus'] = isset($qr_request['time_plus']) ? $qr_request['time_plus'] : [];
        }
    }

    // 4. ถ้าเป็นการร้องขอแบบ AJAX ให้ส่งข้อมูล JSON กลับไปแล้วจบการทำงาน
    if (isset($_GET['ajax'])) {
        header('Content-Type: application/json');
        echo json_encode($request);
        exit;
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <!-- Bootstrap CSS สำหรับตกแต่ง UI -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Choices.js CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css"/>
</head>

<body class="bg-light">
    <div class="container py-4">
        <h1 class="mb-4">Request</h1>
        
        <div class="card mb-4">
            <div class="card-header">
                กำหนดวันที่
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <label for="common-date" class="form-label"><strong>วันที่สำหรับสร้างรายการ:</strong></label>
                        <input type="date" id="common-date" class="form-control">
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Form -->
        <div class="card mb-4">
            <div class="card-header">
                ตัวกรองสาย
            </div>
            <div class="card-body">
                <form action="" method="get" class="row g-3 align-items-start">
                    <div class="col-md-12">
                        <label for="route-select" class="form-label"><strong>เลือกสาย (พิมพ์เพื่อค้นหา):</strong></label>
                        <select class="form-control" name="routes[]" id="route-select" multiple>
                            <?php foreach ($all_routes as $r): ?>
                                <option value="<?php echo $r; ?>" <?php echo in_array($r, $route) ? 'selected' : ''; ?>>
                                    สาย <?php echo $r; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>
            </div>
        </div>

        <!-- ตำแหน่งสำหรับ render ตาราง request/reserve -->
        <div id="request-tables">
            <div class="text-center p-5">
                <div class="spinner-border" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        </div>
    </div>
    <!-- Bootstrap JS (optional, สำหรับ dropdowns ฯลฯ) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Choices.js JS -->
    <script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
    <script>
        // Initialize Choices.js
        document.addEventListener('DOMContentLoaded', function() {
            const selectElement = document.getElementById('route-select');
            const tablesContainer = document.getElementById('request-tables');
            
            const routeSelect = new Choices(selectElement, {
                removeItemButton: true,
                placeholder: true,
                placeholderValue: 'เลือกสาย...',
                searchPlaceholderValue: 'ค้นหาสาย...',
            });

            // Set default date for common date input based on time
            const commonDateInput = document.getElementById('common-date');
            if (commonDateInput) {
                const now = new Date();
                const toYYYYMMDD = (date) => {
                    // Adjust for timezone offset to get correct local date string
                    date.setMinutes(date.getMinutes() - date.getTimezoneOffset());
                    return date.toISOString().split('T')[0];
                };

                if (now.getHours() < 15) {
                    // Before 3 PM, can select today. Default and min is today.
                    commonDateInput.value = toYYYYMMDD(now);
                    commonDateInput.min = toYYYYMMDD(now);
                } else {
                    // 3 PM or later, must select tomorrow. Default and min is tomorrow.
                    const tomorrow = new Date();
                    tomorrow.setDate(now.getDate() + 1);
                    commonDateInput.value = toYYYYMMDD(tomorrow);
                    commonDateInput.min = toYYYYMMDD(tomorrow);
                }
            }

            // ฟังก์ชันสำหรับดึงข้อมูลและ render ตารางใหม่
            async function updateTables() {
                const selectedRoutes = routeSelect.getValue(true);
                const params = new URLSearchParams();
                selectedRoutes.forEach(r => params.append('routes[]', r));
                params.append('ajax', '1');

                // แสดงสถานะกำลังโหลด
                tablesContainer.innerHTML = `<div class="text-center p-5"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>`;

                try {
                    const response = await fetch(`?${params.toString()}`);
                    const newData = await response.json();
                    
                    // อัปเดตข้อมูล global และ render ตารางใหม่
                    Object.assign(request, newData);
                    
                    // ลบข้อมูลสายที่ไม่ได้เลือกแล้วออกจาก object `request`
                    for (const br_id in request) {
                        if (!selectedRoutes.includes(br_id)) {
                            delete request[br_id];
                        }
                    }

                    renderTables();
                } catch (error) {
                    console.error('Error fetching data:', error);
                    tablesContainer.innerHTML = `<div class="alert alert-danger">เกิดข้อผิดพลาดในการโหลดข้อมูล</div>`;
                }
            }

            // เพิ่ม Event Listener เพื่อให้ฟอร์มทำงานอัตโนมัติเมื่อมีการเปลี่ยนแปลง
            selectElement.addEventListener('change', function(event) {
                updateTables();
            });
        });
    </script>
</body>

</html>
<script>
// แปลงข้อมูล request จาก PHP เป็น object ฝั่ง JS
let request = <?php echo json_encode($request); ?>;

function onTimeChange(br_id, idx, inputElem) {
    if (!request[br_id].time) request[br_id].time = [];
    request[br_id].time[idx] = inputElem.value;
}

function onTimePlusChange(br_id, idx, inputElem) {
    if (!request[br_id].time_plus) request[br_id].time_plus = [];
    request[br_id].time_plus[idx] = inputElem.value;
}

// ========================
// ฟังก์ชันหลักสำหรับ render ตาราง request/reserve ของทุกสาย
// ========================
function renderTables() {
    const container = document.getElementById('request-tables');
    
    let html = '';
    // ฟอร์มหลักสำหรับ submit ข้อมูลทั้งหมด
    html += `<form method='post' action='request_sale_db.php' id='all-route-form'>`;
    // วนลูปแต่ละสาย (br_id)
    Object.entries(request).forEach(([br_id, obj]) => {
        html += `<div class="card mb-4 shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Route: ${br_id}</h5>
            </div>
            <div class="card-body">
                <b>Request</b>
                <div class="table-responsive">
                <table class="table table-bordered align-middle table-sm">
                    <thead class="table-light">
                        <tr>
                            <th>ลำดับ</th>
                            <th>รหัสประจำคิว</th>
                            <th>Queue Request</th>
                            <th>เวลา</th>
                            <th>เวลาเดินทาง (นาที)</th>
                            <th>action</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-${br_id}-request">`;
        // วนลูปแต่ละแถวของ request
        const reqArr = obj.request || [];
        const timeArr = obj.time || [];
        const timePlusArr = obj.time_plus || [];
        reqArr.forEach((qr_request, idx) => {
            let code = (idx === reqArr.length - 1) ? `${br_id}-3-last` : `${br_id}-3-${idx + 1}`;
            let timeVal = timeArr[idx] || '';
            let timePlusVal = timePlusArr[idx] || '90';
            html += `<tr>
                <td>${idx + 1}</td>
                <td>${code}</td>
                <td>${qr_request}<input type="hidden" name="request[${br_id}][]" value="${qr_request}"></td>
                <td>
                    <input type="time" class="form-control" name="time[${br_id}][]" value="${timeVal}" onchange="onTimeChange('${br_id}', ${idx}, this)">
                </td>
                <td>
                    <input type="number" class="form-control" name="time_plus[${br_id}][]" value="${timePlusVal}" onchange="onTimePlusChange('${br_id}', ${idx}, this)" min="0">
                </td>
                <td>
                    <div class="btn-group btn-group-sm" role="group">
                        <button type='button' class="btn btn-outline-secondary" onclick="insertRow('${br_id}','request',${idx},'before')" ${idx === 0 ? 'disabled' : ''}>แทรกก่อน</button>
                        <button type='button' class="btn btn-outline-secondary" onclick="insertRow('${br_id}','request',${idx},'after')" ${idx === reqArr.length - 1 ? 'disabled' : ''}>แทรกหลัง</button>
                        <button type='button' class="btn btn-outline-danger" onclick="removeRow('${br_id}','request',${idx})">ลบ</button>
                    </div>
                </td>
            </tr>`;
        });
        // แถวสำหรับเพิ่มข้อมูลใหม่ (request)
        html += `<tr>
            <td>ใหม่</td>
            <td>${br_id}-3-ใหม่</td>
            <td>2</td>
            <td><input type="time" class="form-control" name="time[${br_id}][]" value=""></td>
            <td><input type="number" class="form-control" name="time_plus[${br_id}][]" value="90" min="0"></td>
            <td><button type='button' class="btn btn-success btn-sm" onclick="insertRow('${br_id}','request',${reqArr.length-1},'after')">เพิ่ม</button></td>
        </tr>`;
        html += '</tbody></table></div>';
        
        // Add hidden inputs for reserve data to prevent it from being lost
        const reserveArr = obj.reserve || [];
        reserveArr.forEach((qr_reserve) => {
            html += `<input type="hidden" name="reserve[${br_id}][]" value="${qr_reserve}">`;
        });

        // ========================
        // ส่วนของ Reserve (ถูกลบออกเนื่องจากซ้ำซ้อนและไม่ได้ใช้งาน)
        // ========================

        html += '</div></div>';   
    });
    // ปุ่มบันทึกข้อมูลทั้งหมด
    html += `<div class='my-3'><button type='submit' class="btn btn-primary btn-lg w-100" id="submit-btn">บันทึกทั้งหมด</button></div>`;
    html += `</form>`;
    container.innerHTML = html;
}

// ========================
// ฟังก์ชันลบแถว (request/reserve) ตามสายและ index ที่เลือก
// ========================
function removeRow(br_id, type, idx) {
    let arr = request[br_id][type] || [];
    if (arr.length > 0) {
        arr.splice(idx, 1);
        // Only remove time if it's a request row
        if (type === 'request') {
            let timeArr = request[br_id].time || [];
            timeArr.splice(idx, 1);
            let timePlusArr = request[br_id].time_plus || [];
            timePlusArr.splice(idx, 1);
        }
        renderTables();
    }
}

// ========================
// ฟังก์ชันเพิ่มแถวใหม่ (request/reserve) ก่อนหรือหลัง index ที่เลือก
// ========================
function insertRow(br_id, type, idx, pos) {
    let arr = request[br_id][type] || [];
    let insertIdx = pos === 'before' ? idx : idx + 1;
    
    arr.splice(insertIdx, 0, '2'); // เพิ่มค่า default '2'
    
    // Only add a time if it's a request row
    if (type === 'request') {
        if (!request[br_id].time) request[br_id].time = [];
        const now = new Date();
        // ปรับ timezone offset เพื่อให้แสดงเวลาท้องถิ่นถูกต้องใน input
        now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
        const currentTime = now.toISOString().slice(11, 16);
        request[br_id].time.splice(insertIdx, 0, currentTime); // เพิ่มค่า time ปัจจุบัน
        if (!request[br_id].time_plus) request[br_id].time_plus = [];
        request[br_id].time_plus.splice(insertIdx, 0, '90'); // เพิ่มค่า time_plus เริ่มต้น
    }
    
    renderTables();
    // เลื่อน scroll ไปยังแถวที่เพิ่ม (optional)
    setTimeout(() => {
        const tbody = document.getElementById(`tbody-${br_id}-${type}`);
        if (tbody && tbody.children[insertIdx]) {
            tbody.children[insertIdx].scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }, 100);
}

// ========================
// เรียก renderTables() ครั้งแรกเมื่อโหลดหน้า
// ========================
renderTables();

// ========================
// ดัก submit ฟอร์มรวมทุกสายเพื่อป้องกันการส่งถ้ามีซ้ำ
// ========================
document.addEventListener('submit', function(e) {
    if (e.target && e.target.id === 'all-route-form') {
        const form = e.target;
        const commonDate = document.getElementById('common-date').value;

        if (!commonDate) {
            alert('กรุณากำหนดวันที่ก่อนบันทึก');
            e.preventDefault();
            return;
        }

        // Create hidden inputs for the `date` array before submitting
        Object.entries(request).forEach(([br_id, obj]) => {
            const reqArr = obj.request || [];
            const timeArr = obj.time || [];
            const timePlusArr = obj.time_plus || [];

            reqArr.forEach((qr_request, idx) => {
                const time = timeArr[idx];
                const timePlus = timePlusArr[idx];
                // Only create date input if time is also set
                if (time) {
                    const fullDateTime = `${commonDate}T${time}`;
                    const dateInput = document.createElement('input');
                    dateInput.type = 'hidden';
                    dateInput.name = `date[${br_id}][request][${idx}]`;
                    dateInput.value = fullDateTime;
                    form.appendChild(dateInput);

                    const timePlusInput = document.createElement('input');
                    timePlusInput.type = 'hidden';
                    timePlusInput.name = `time_plus[${br_id}][${idx}]`;
                    timePlusInput.value = timePlus;
                    form.appendChild(timePlusInput);
                }
            });
        });
    }
});
</script>