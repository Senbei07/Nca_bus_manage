<?php
include 'config.php';

// ฟังก์ชันรับข้อมูล JSON จาก POST
function getPostJson($key) {
    return isset($_POST[$key]) ? json_decode($_POST[$key], true) : [];
}

$plan = getPostJson('plan');
$main_not_ready = getPostJson('main_not_ready');
$ex_not_ready = getPostJson('ex_not_ready');
$coach_not_ready = getPostJson('coach_not_ready');

// ตัวอย่าง: แสดงผลข้อมูลที่รับมา
echo "<h2 class='mt-4'>ข้อมูลที่ได้รับ</h2>";
echo "<pre class='bg-white p-3 rounded'>";
echo "plan:\n";
print_r($plan);
echo "\nmain_not_ready:\n";
print_r($main_not_ready);
echo "\nex_not_ready:\n";
print_r($ex_not_ready);
echo "\ncoach_not_ready:\n";
print_r($coach_not_ready);
echo "</pre>";

// 1. เพิ่มข้อมูลลงในตาราง bus_group (main_dri, ex_1, coach, bi_id = car)
$group_ids = [];
foreach ($plan as $idx => $row) {
    $main_dri = intval($row['em_id']);
    $ex_1 = intval($row['ex_id']);
    $coach = intval($row['coach_id']);
    $car = intval($row['car']); // bi_id

    $sql = "INSERT INTO bus_group (bi_id, main_dri, ex_1, ex_2, coach) VALUES (?, ?, ?, 0, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiii", $car, $main_dri, $ex_1, $coach);
    $stmt->execute();
    $bg_id = $conn->insert_id;
    $group_ids[] = $bg_id;
    $stmt->close();
}

// 2. เพิ่มข้อมูลใน bus_plan (br_id, bg_id, bs_id=1)
$br_id = isset($plan[0]['route']) ? intval($plan[0]['route']) : 1; // ตัวอย่าง: ใช้ route เป็น br_id
foreach ($group_ids as $idx => $bg_id) {
    $sql = "INSERT INTO bus_plan (br_id, bg_id, bs_id) VALUES (?, ?, 1)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $br_id, $bg_id);
    $stmt->execute();
    $stmt->close();
}

// 3. อัปเดต queue ของพนักงานใน employee
foreach ($plan as $row) {
    // main
    $sql = "UPDATE employee SET em_queue=? WHERE em_id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $row['main_queue'], $row['em_id']);
    $stmt->execute();
    $stmt->close();

    // ex
    $sql = "UPDATE employee SET em_queue=? WHERE em_id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $row['ex_queue'], $row['ex_id']);
    $stmt->execute();
    $stmt->close();

    // coach
    $sql = "UPDATE employee SET em_queue=? WHERE em_id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $row['coach_queue'], $row['coach_id']);
    $stmt->execute();
    $stmt->close();
}

// 4. อัปเดต main_not_ready
foreach ($main_not_ready as $row) {
    if (!isset($row['em_id']) || !isset($row['em_queue'])) continue;
    $sql = "UPDATE employee SET em_queue=? WHERE em_id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $row['em_queue'], $row['em_id']);
    $stmt->execute();
    $stmt->close();
}

// 5. อัปเดต ex_not_ready
foreach ($ex_not_ready as $row) {
    if (!isset($row['em_id']) || !isset($row['em_queue'])) continue;
    $sql = "UPDATE employee SET em_queue=? WHERE em_id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $row['em_queue'], $row['em_id']);
    $stmt->execute();
    $stmt->close();
}

// 6. อัปเดต coach_not_ready
foreach ($coach_not_ready as $row) {
    if (!isset($row['em_id']) || !isset($row['em_queue'])) continue;
    $sql = "UPDATE employee SET em_queue=? WHERE em_id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $row['em_queue'], $row['em_id']);
    $stmt->execute();
    $stmt->close();
}

echo "<h3 class='text-success mt-4'>บันทึกข้อมูลเรียบร้อย</h3>";
$conn->close();
?>