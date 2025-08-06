<?php
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['pr_id'])) {
    header('Location: request_sale.php');
    exit;
}

$pr_id_to_confirm = (int)$_POST['pr_id'];

// เริ่ม Transaction
$conn->begin_transaction();

try {
    // 1. ดึงข้อมูลแผนที่จะยืนยัน
    $stmt_get_plan = $conn->prepare("SELECT br_id, pr_date, pr_request FROM plan_request WHERE pr_id = ? AND pr_status = 1");
    $stmt_get_plan->bind_param('i', $pr_id_to_confirm);
    $stmt_get_plan->execute();
    $result = $stmt_get_plan->get_result();
    $plan = $result->fetch_assoc();
    $stmt_get_plan->close();

    if (!$plan) {
        throw new Exception("ไม่พบแผนที่ต้องการยืนยัน หรือแผนถูกยืนยันไปแล้ว");
    }

    $br_id = $plan['br_id'];
    $pr_date = $plan['pr_date'];
    $pr_request_json = $plan['pr_request'];

    // 2. อัปเดตแผนอื่น ๆ ในวันและสายเดียวกันให้เป็นสถานะ 2 (รูปแบบเก่า)
    $stmt_update_others = $conn->prepare(
        "UPDATE plan_request SET pr_status = 2 WHERE br_id = ? AND pr_date = ? AND pr_status = 1 AND pr_id != ?"
    );
    $stmt_update_others->bind_param('isi', $br_id, $pr_date, $pr_id_to_confirm);
    $stmt_update_others->execute();
    $stmt_update_others->close();

    // 3. อัปเดตคิวมาตรฐาน (queue_request)
    $stmt_update_queue = $conn->prepare("UPDATE queue_request SET qr_request = ? WHERE br_id = ?");
    $stmt_update_queue->bind_param('si', $pr_request_json, $br_id);
    $stmt_update_queue->execute();
    $stmt_update_queue->close();

    // 4. อัปเดตสถานะแผนที่ยืนยันเป็น 3 (ยืนยันแล้ว)
    $stmt_confirm_plan = $conn->prepare("UPDATE plan_request SET pr_status = 3 WHERE pr_id = ?");
    $stmt_confirm_plan->bind_param('i', $pr_id_to_confirm);
    $stmt_confirm_plan->execute();
    $stmt_confirm_plan->close();

    // ถ้าทุกอย่างสำเร็จ
    $conn->commit();
    echo "ยืนยันแผนสำเร็จ";

} catch (Exception $e) {
    // ถ้ามีข้อผิดพลาด
    $conn->rollback();
    die("เกิดข้อผิดพลาด: " . $e->getMessage());
}

// Redirect กลับไปหน้าเดิม
header("Location: request_sale.php?date=" . urlencode($pr_date));
exit;
