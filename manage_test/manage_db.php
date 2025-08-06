<?php
// include 'config.php';

// รับข้อมูลที่ส่งมาจากฟอร์มและแปลงเป็น array
$plan = isset($_POST['plan_data']) ? json_decode($_POST['plan_data'], true) : [];
$pr_ids = isset($_POST['pr_ids_data']) ? json_decode($_POST['pr_ids_data'], true) : [];
$main_break = isset($_POST['main_break_data']) ? json_decode($_POST['main_break_data'], true) : [];
$exnotredy = isset($_POST['exnotredy_data']) ? json_decode($_POST['exnotredy_data'], true) : [];
$coachnotredy = isset($_POST['coachnotredy_data']) ? json_decode($_POST['coachnotredy_data'], true) : [];

echo "<script>console.log('plan_data:', " . json_encode($plan) . ");</script>";
echo "<script>console.log('main_break:', " . json_encode($main_break) . ");</script>";


$conn->begin_transaction();

try {
    // 1. เตรียม Statements
    $stmt_check = $conn->prepare("SELECT bg_id FROM bus_plan WHERE br_id = ? AND pr_id = ? AND bp_pr_no = ?");
    $stmt_update_group = $conn->prepare("UPDATE bus_group SET bi_id = ?, main_dri = ?, ex_1 = ?, coach = ? WHERE gb_id = ?");
    $stmt_insert_group = $conn->prepare("INSERT INTO bus_group (bi_id, main_dri, ex_1, ex_2, coach) VALUES (?, ?, ?, 0, ?)");
    $stmt_insert_plan = $conn->prepare("INSERT INTO bus_plan (br_id, pr_id, bp_pr_no, bg_id, bs_id, bp_date) VALUES (?, ?, ?, ?, 1, NOW())");

    // 2. วนลูปจัดการข้อมูล plan
    foreach ($plan as $br_id => $rows) {
        foreach ($rows as $idx => $row) {
            $pr_id = $pr_ids[$br_id] ?? null;
            $bp_pr_no = $idx + 1;

            // ข้อมูลพนักงานและรถ
            $main_dri = intval($row['em_id']);
            $ex_1 = intval($row['ex_id']);
            $coach = intval($row['coach_id']);
            $car = intval($row['car']);

            // ตรวจสอบว่ามีแผนนี้อยู่แล้วหรือไม่
            $stmt_check->bind_param("iii", $br_id, $pr_id, $bp_pr_no);
            $stmt_check->execute();
            $result = $stmt_check->get_result();
            $existing_plan = $result->fetch_assoc();

            if ($existing_plan) {
                // --- ถ้ามี ให้อัปเดต ---
                $bg_id = $existing_plan['bg_id'];
                $stmt_update_group->bind_param("iiiii", $car, $main_dri, $ex_1, $coach, $bg_id);
                $stmt_update_group->execute();
            } else {
                // --- ถ้าไม่มี ให้เพิ่มใหม่ ---
                $stmt_insert_group->bind_param("iiii", $car, $main_dri, $ex_1, $coach);
                $stmt_insert_group->execute();
                $bg_id = $conn->insert_id;

                $stmt_insert_plan->bind_param("iiii", $br_id, $pr_id, $bp_pr_no, $bg_id);
                $stmt_insert_plan->execute();
            }
        }
    }

    // ปิด statements
    $stmt_check->close();
    $stmt_update_group->close();
    $stmt_insert_group->close();
    $stmt_insert_plan->close();

    // 3. อัปเดต queue ของพนักงานใน employee (ส่วนนี้เหมือนเดิม)
    // --- 3.1 อัปเดตจาก plan (main, ex, coach)
    foreach ($plan as $br_id => $rows) {
        foreach ($rows as $row) {
            // main
            $sql = "UPDATE employee SET em_queue=? WHERE em_id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $row['new_queue'], $row['em_id']);
            $stmt->execute();
            $stmt->close();
            $sql = "INSERT INTO emp_history (`em_id`, `eh_his`) VALUES (?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $row['em_id'], $row['new_queue']);
            $stmt->execute();
            $stmt->close();

            // ex
            $sql = "UPDATE employee SET em_queue=? WHERE em_id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $row['ex_new_queue'], $row['ex_id']);
            $stmt->execute();
            $stmt->close();
            $sql = "INSERT INTO emp_history (`em_id`, `eh_his`) VALUES (?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $row['ex_id'], $row['ex_new_queue']);
            $stmt->execute();
            $stmt->close();

            // coach
            $sql = "UPDATE employee SET em_queue=? WHERE em_id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $row['coach_new_queue'], $row['coach_id']);
            $stmt->execute();
            $stmt->close();
            $sql = "INSERT INTO emp_history (`em_id`, `eh_his`) VALUES (?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $row['coach_id'], $row['coach_new_queue']);
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
            $sql = "INSERT INTO emp_history (`em_id`, `eh_his`) VALUES (?, ?)";
            $stmt = $conn->prepare($sql);   
            $stmt->bind_param("ss", $row['em_id'], $row['new_queue']);
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
            $sql = "INSERT INTO emp_history (`em_id`, `eh_his`) VALUES (?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $row['em_id'], $row['new_queue']);
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
            $sql = "INSERT INTO emp_history (`em_id`, `eh_his`) VALUES (?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $row['em_id'], $row['new_queue']);
            $stmt->execute();
            $stmt->close();
        }
    }

    $conn->commit();
    echo "<h3>บันทึกข้อมูลเรียบร้อย</h3>";

} catch (Exception $e) {
    $conn->rollback();
    die("เกิดข้อผิดพลาด: " . $e->getMessage());
}

$conn->close();

// header("Location: manage.php");
// exit();
?>