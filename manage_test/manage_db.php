<?php
include 'config.php';

// รับข้อมูลที่ส่งมาจากฟอร์มและแปลงเป็น array
$plan = isset($_POST['plan_data']) ? json_decode($_POST['plan_data'], true) : [];
$main_break = isset($_POST['main_break_data']) ? json_decode($_POST['main_break_data'], true) : [];
$exnotredy = isset($_POST['exnotredy_data']) ? json_decode($_POST['exnotredy_data'], true) : [];
$coachnotredy = isset($_POST['coachnotredy_data']) ? json_decode($_POST['coachnotredy_data'], true) : [];

// ตัวอย่าง: แสดงผลข้อมูลที่รับมา
echo "<h2>ข้อมูลที่ได้รับ</h2>";
echo "<pre>";
echo "plan:\n";
print_r($plan);
echo "\nmain_break:\n";
print_r($main_break);
echo "\nexnotredy:\n";
print_r($exnotredy);
echo "\ncoachnotredy:\n";
print_r($coachnotredy);
echo "</pre>";


// 1. เพิ่มข้อมูลลงในตาราง bus_group (main_dri, ex_1, coach, bi_id = car)
$group_ids = []; // เก็บ bg_id ที่เพิ่มใหม่ เพื่อใช้ใน bus_plan
foreach ($plan as $br_id => $rows) {
    foreach ($rows as $row) {
        // เพิ่มข้อมูลลง bus_group
        $main_dri = intval($row['em_id']);
        $ex_1 = intval($row['ex_id']);
        $coach = intval($row['coach_id']);
        $car = intval($row['car']); // bi_id

        $sql = "INSERT INTO bus_group (bi_id, main_dri, ex_1, ex_2, coach) VALUES (?, ?, ?, 0, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iiii", $car, $main_dri, $ex_1, $coach);
        $stmt->execute();
        $bg_id = $conn->insert_id;
        $group_ids[$br_id][] = $bg_id;
        $stmt->close();
    }
}

// 2. เพิ่มข้อมูลใน bus_plan (br_id, bg_id, bs_id=1)
foreach ($plan as $br_id => $rows) {
    foreach ($rows as $idx => $row) {
        $bg_id = $group_ids[$br_id][$idx];
        $sql = "INSERT INTO bus_plan (br_id, bg_id, bs_id) VALUES (?, ?, 1)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $br_id, $bg_id);
        $stmt->execute();
        $stmt->close();
    }
}

// 3. อัปเดต queue ของพนักงานใน employee
// --- 3.1 อัปเดตจาก plan (main, ex, coach)
foreach ($plan as $br_id => $rows) {
    foreach ($rows as $row) {
        // main
        $sql = "UPDATE employee SET em_queue=? WHERE em_id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $row['new_queue'], $row['em_id']);
        $stmt->execute();
        $stmt->close();

        // ex
        $sql = "UPDATE employee SET em_queue=? WHERE em_id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $row['ex_new_queue'], $row['ex_id']);
        $stmt->execute();
        $stmt->close();

        // coach
        $sql = "UPDATE employee SET em_queue=? WHERE em_id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $row['coach_new_queue'], $row['coach_id']);
        $stmt->execute();
        $stmt->close();
    }
}

// --- 3.2 อัปเดต main_break
foreach ($main_break as $route => $list) {
    foreach ($list as $row) {
        if (!isset($row['em_id']) || !isset($row['new_queue'])) continue;
        $sql = "UPDATE employee SET em_queue=? WHERE em_id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $row['new_queue'], $row['em_id']);
        $stmt->execute();
        $stmt->close();
    }
}

// --- 3.3 อัปเดต exnotredy
foreach ($exnotredy as $route => $list) {
    foreach ($list as $row) {
        if (!isset($row['em_id']) || !isset($row['new_queue'])) continue;
        $sql = "UPDATE employee SET em_queue=? WHERE em_id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $row['new_queue'], $row['em_id']);
        $stmt->execute();
        $stmt->close();
    }
}

// --- 3.4 อัปเดต coachnotredy
foreach ($coachnotredy as $route => $list) {
    foreach ($list as $row) {
        if (!isset($row['em_id']) || !isset($row['new_queue'])) continue;
        $sql = "UPDATE employee SET em_queue=? WHERE em_id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $row['new_queue'], $row['em_id']);
        $stmt->execute();
        $stmt->close();
    }
}

echo "<h3>บันทึกข้อมูลเรียบร้อย</h3>";
$conn->close();

header("Location: manage.php");
exit();
?>