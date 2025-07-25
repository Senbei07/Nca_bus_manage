<?php 
    // เชื่อมต่อฐานข้อมูล
    include 'config.php';

    // ตรวจสอบการเชื่อมต่อฐานข้อมูล
    if (!$conn) {
        die("Connection failed: " . mysqli_connect_error());
    }

    // กำหนดสายที่ต้องการดึงข้อมูล
    $route = [2,3,4];
    // ดึงข้อมูล queue_request ทั้งหมดจากฐานข้อมูล เรียงตาม br_id
    $sql_request = "SELECT * FROM `queue_request` WHERE br_id IN (" . implode(',', $route) . ") ORDER BY br_id";
    $result_request = mysqli_query($conn, $sql_request);

    // สร้าง array สำหรับเก็บข้อมูล request/reserve ของแต่ละ br_id
    $request = [];
    while ($row = mysqli_fetch_assoc($result_request)) {
        $qr_request = json_decode($row['qr_request'], true);
        $request[$row['br_id']]['request'] = $qr_request['request'];
        $request[$row['br_id']]['reserve'] = $qr_request['reserve'];
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
</head>

<body class="bg-light">
    <div class="container py-4">
        <h1 class="mb-4">จัดการ Request & Reserve</h1>
        <!-- ตำแหน่งสำหรับ render ตาราง request/reserve -->
        <div id="request-tables"></div>
    </div>
    <!-- Bootstrap JS (optional, สำหรับ dropdowns ฯลฯ) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
<script>
// แปลงข้อมูล request จาก PHP เป็น object ฝั่ง JS
const request = <?php echo json_encode($request); ?>;

// ========================
// ฟังก์ชันสำหรับสร้างตัวเลือกทั้งหมดใน select (dropdown) ของแต่ละสาย
// ========================
function getAllCodeOptions(request) {
    const groupMap = {};
    Object.entries(request).forEach(([br_id, obj]) => {
        if (!groupMap[br_id]) groupMap[br_id] = [];
        const reqArr = obj.request || [];
        for (let i = 0; i < reqArr.length; i++) {
            let code = (i === reqArr.length - 1) ? `${br_id}-3-last` : `${br_id}-3-${i + 1}`;
            groupMap[br_id].push({ value: code, label: code });
        }
        const reserveArr = obj.reserve || [];
        for (let i = 0; i < reserveArr.length; i++) {
            let code = `${br_id}-1-${i + 1}`;
            groupMap[br_id].push({ value: code, label: code });
        }
    });
    // เพิ่มตัวเลือก '0' ใน optgroup "อื่นๆ"
    groupMap['อื่นๆ'] = [
        { value: '0', label: '0' },
        { value: '1', label: '1' },
        { value: '2', label: '2' }
    ];
    return groupMap;
}
// routeOptions จะถูกสร้างใหม่ทุกครั้งใน renderTables()

// ========================
// ฟังก์ชันสำหรับสร้าง select dropdown ในแต่ละ cell ของตาราง
// ========================
function createSelect(name, selected, routeOptions, br_id, type, idx, isDup) {
    // ถ้ามี isDup จะเพิ่ม class is-invalid เพื่อแสดง error
    let html = `<select name="${name}" class="form-select${isDup ? ' is-invalid' : ''}" onchange="onQueueChange('${br_id}','${type}',${idx},this)">`;
    // วนลูป optgroup (แต่ละสาย)
    Object.entries(routeOptions).forEach(([group, opts]) => {
        html += `<optgroup label="${group}">`;
        for (const opt of opts) {
            html += `<option value="${opt.value}" ${opt.value === selected ? 'selected' : ''}>${opt.label}</option>`;
        }
        html += `</optgroup>`;
    });
    html += '</select>';
    // ถ้ามีซ้ำและไม่ได้เลือก '0' ให้แสดงข้อความ error
    if (isDup && selected && selected !== '0') {
        html += '<div class="invalid-feedback">ซ้ำ</div>';
    }
    return html;
}

// ========================
// ตัวแปรเก็บ queue ที่ถูกเลือกทั้งหมด (ใช้สำหรับตรวจสอบซ้ำ)
// ========================
let usedQueues = [];
function updateUsedQueues() {
    usedQueues = [];
    Object.values(request).forEach(obj => {
        (obj.request || []).forEach(q => { if(q && q !== '0') usedQueues.push(q); });
        (obj.reserve || []).forEach(q => { if(q && q !== '0') usedQueues.push(q); });
    });
}

// ========================
// ฟังก์ชันเมื่อมีการเปลี่ยนค่า select (queue) ในแต่ละ cell
// ========================
function onQueueChange(br_id, type, idx, selectElem) {
    const newValue = selectElem.value;
    // อัปเดตข้อมูลใน request object
    request[br_id][type][idx] = newValue;
    // render ตารางใหม่เพื่ออัปเดต UI และตรวจสอบซ้ำ
    renderTables();
}

// ========================
// ฟังก์ชันหลักสำหรับ render ตาราง request/reserve ของทุกสาย
// ========================
function renderTables() {
    const container = document.getElementById('request-tables');
    const routeOptions = getAllCodeOptions(request);

    // หา code ที่ซ้ำกันในทุกสาย (request + reserve)
    let allSelected = [];
    let codeLocation = {}; // เก็บตำแหน่งแต่ละ code
    Object.entries(request).forEach(([br_id, obj]) => {
        (obj.request || []).forEach((qr_request, idx) => {
            if (qr_request && qr_request !== '0') {
                allSelected.push(qr_request);
                if (!codeLocation[qr_request]) codeLocation[qr_request] = [];
                codeLocation[qr_request].push(`Route ${br_id} - Request ลำดับ ${idx + 1}`);
            }
        });
        (obj.reserve || []).forEach((qr_request, idx) => {
            if (qr_request && qr_request !== '0') {
                allSelected.push(qr_request);
                if (!codeLocation[qr_request]) codeLocation[qr_request] = [];
                codeLocation[qr_request].push(`Route ${br_id} - Reserve ลำดับ ${idx + 1}`);
            }
        });
    });
    let seen = new Set();
    let duplicateCodes = new Set();
    allSelected.forEach(code => {
        if (code === '1' || code === '2') return;
        if (seen.has(code)) duplicateCodes.add(code);
        else seen.add(code);
    });

    let html = '';
    // ไม่แสดง alert ในหน้า ถ้ามีซ้ำจะใช้ pop-up
    html += `<div id="dup-alert"></div>`;
    // ฟอร์มหลักสำหรับ submit ข้อมูลทั้งหมด
    html += `<form method='post' action='request_db.php' id='all-route-form'>`;
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
                        <tr><th>ลำดับ</th><th>รหัสประจำคิว</th><th>queue request)</th><th>action</th></tr>
                    </thead>
                    <tbody id="tbody-${br_id}-request">`;
        // วนลูปแต่ละแถวของ request
        const reqArr = obj.request || [];
        reqArr.forEach((qr_request, idx) => {
            // ถ้าเป็นแถวสุดท้าย (ไม่นับแถว "ใหม่") ให้รหัส = last
            let code = (idx === reqArr.length - 1) ? `${br_id}-3-last` : `${br_id}-3-${idx + 1}`;
            const isDup = duplicateCodes.has(qr_request) && qr_request !== '0';
            html += `<tr>`;
            html += `<td>${idx + 1}</td>`;
            html += `<td>${code}</td>`;
            html += `<td>${createSelect(`request[${br_id}][]`, qr_request, routeOptions, br_id, 'request', idx, isDup)}`;
            html += `</td>`;
            html += `<td>
                <div class="btn-group btn-group-sm" role="group">
                    <button type='button' class="btn btn-outline-secondary" onclick="insertRow('${br_id}','request',${idx},'before')">แทรกก่อน</button>
                    <button type='button' class="btn btn-outline-secondary" onclick="insertRow('${br_id}','request',${idx},'after')">แทรกหลัง</button>
                    <button type='button' class="btn btn-outline-danger" onclick="removeRow('${br_id}','request',${idx})">ลบ</button>
                </div>
            </td>`;
            html += `</tr>`;
        });
        // แถวสำหรับเพิ่มข้อมูลใหม่ (request)
        html += `<tr>`;
        html += `<td>ใหม่</td>`;
        html += `<td>${br_id}-3-ใหม่</td>`;
        html += `<td>${createSelect('', '0', routeOptions, br_id, 'request', reqArr.length, false)}</td>`;
        html += `<td><button type='button' class="btn btn-success btn-sm" onclick="insertRow('${br_id}','request',${reqArr.length-1},'after')">เพิ่ม</button></td>`;
        html += `</tr>`;
        html += '</tbody></table></div>';
        // ========================
        // ส่วนของ Reserve
        // ========================
        html += `<b>Reserve</b>
                <div class="table-responsive">
                <table class="table table-bordered align-middle table-sm">
                    <thead class="table-light">
                        <tr><th>ลำดับ</th><th>รหัสประจำคิว</th><th>queue request)</th><th>action</th></tr>
                    </thead>
                    <tbody id="tbody-${br_id}-reserve">`;
        const reserveArr = obj.reserve || [];
        reserveArr.forEach((qr_request, idx) => {
            // ไม่มี last สำหรับ reserve
            let code = `${br_id}-1-${idx + 1}`;
            const isDup = duplicateCodes.has(qr_request) && qr_request !== '0';
            html += `<tr>`;
            html += `<td>${idx + 1}</td>`;
            html += `<td>${code}</td>`;
            html += `<td>${createSelect(`reserve[${br_id}][]`, qr_request, routeOptions, br_id, 'reserve', idx, isDup)}`;
            html += `</td>`;
            html += `<td>
                <div class="btn-group btn-group-sm" role="group">
                    <button type='button' class="btn btn-outline-secondary" onclick="insertRow('${br_id}','reserve',${idx},'before')">แทรกก่อน</button>
                    <button type='button' class="btn btn-outline-secondary" onclick="insertRow('${br_id}','reserve',${idx},'after')">แทรกหลัง</button>
                    <button type='button' class="btn btn-outline-danger" onclick="removeRow('${br_id}','reserve',${idx})">ลบ</button>
                </div>
            </td>`;
            html += `</tr>`;
        });
        // แถวสำหรับเพิ่มข้อมูลใหม่ (reserve)
        html += `<tr>`;
        html += `<td>ใหม่</td>`;
        html += `<td>${br_id}-1-ใหม่</td>`;
        html += `<td>${createSelect('', '0', routeOptions, br_id, 'reserve', reserveArr.length, false)}</td>`;
        html += `<td><button type='button' class="btn btn-success btn-sm" onclick="insertRow('${br_id}','reserve',${reserveArr.length-1},'after')">เพิ่ม</button></td>`;
        html += `</tr>`;
        html += '</tbody></table></div>';
        html += '</div></div>';
    });
    // ปุ่มบันทึกข้อมูลทั้งหมด
    html += `<div class='my-3'><button type='submit' class="btn btn-primary btn-lg w-100" id="submit-btn">บันทึกทั้งหมด</button></div>`;
    html += `</form>`;
    container.innerHTML = html;
    // ปิดปุ่ม submit ถ้ามีซ้ำ
    setTimeout(() => {
        const btn = document.getElementById('submit-btn');
        if (btn) btn.disabled = duplicateCodes.size > 0;
    }, 0);
}

// ========================
// ฟังก์ชันลบแถว (request/reserve) ตามสายและ index ที่เลือก
// ========================
function removeRow(br_id, type, idx) {
    let arr = request[br_id][type] || [];
    if (arr.length > 0) {
        arr.splice(idx, 1);
        request[br_id][type] = arr;
        renderTables();
    }
}

// ========================
// ฟังก์ชันเพิ่มแถวใหม่ (request/reserve) ก่อนหรือหลัง index ที่เลือก
// ========================
function insertRow(br_id, type, idx, pos) {
    // เพิ่มข้อมูลใหม่ใน request object (ฝั่ง JS เท่านั้น)
    let arr = request[br_id][type] || [];
    let insertIdx = pos === 'before' ? idx : idx + 1;
    arr.splice(insertIdx, 0, '0'); // เพิ่มค่า default '0'
    request[br_id][type] = arr;
    renderTables();
    // เลื่อน scroll ไปยังแถวที่เพิ่ม (optional)
    setTimeout(() => {
        const tbody = document.getElementById(`tbody-${br_id}-${type}`);
        if (tbody && tbody.children[insertIdx]) {
            tbody.children[insertIdx].scrollIntoView({behavior:'smooth', block:'center'});
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
        let allSelected = [];
        let codeLocation = {};
        Object.entries(request).forEach(([br_id, obj]) => {
            (obj.request || []).forEach((qr_request, idx) => {
                allSelected.push(qr_request);
                if (!codeLocation[qr_request]) codeLocation[qr_request] = [];
                codeLocation[qr_request].push(`Route ${br_id} - Request ลำดับ ${idx + 1}`);
            });
            (obj.reserve || []).forEach((qr_request, idx) => {
                allSelected.push(qr_request);
                if (!codeLocation[qr_request]) codeLocation[qr_request] = [];
                codeLocation[qr_request].push(`Route ${br_id} - Reserve ลำดับ ${idx + 1}`);
            });
        });
        let seen = new Set();
        let duplicateCodes = new Set();
        let hasZero = false;
        allSelected.forEach(code => {
            if (code === '0') hasZero = true;
            if (code === '1' || code === '2' || code === '0') return;
            if (seen.has(code)) duplicateCodes.add(code);
            else seen.add(code);
        });
        if (duplicateCodes.size > 0 || hasZero) {
            e.preventDefault();
            let msg = '';
            if (hasZero) {
                msg = 'พบตัวเลือกที่ยังเป็น 0 กรุณาเปลี่ยนก่อนบันทึก';
            } else {
                msg = 'พบการเลือก queue ซ้ำกัน:\n';
                duplicateCodes.forEach(code => {
                    msg += `- ${code} : ${codeLocation[code].join(', ')}\n`;
                });
            }
            alert(msg);
            return false;
        }
    }
});
</script>