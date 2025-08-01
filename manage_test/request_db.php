<?php
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // รับข้อมูล request, reserve, และ time จากฟอร์ม
    $all_request = $_POST['request'] ?? [];
    $all_reserve = $_POST['reserve'] ?? [];
    $all_time = $_POST['time'] ?? [];
    $all_time_plus = $_POST['time_plus'] ?? [];

    // สร้าง array ของ br_id ทั้งหมดที่จะทำการอัปเดต
    $all_br_ids = array_unique(array_merge(array_keys($all_request), array_keys($all_reserve)));

    // เตรียมคำสั่ง SQL สำหรับอัปเดต
    $sql = "UPDATE `queue_request` SET `qr_request` = ? WHERE `br_id` = ?";
    $stmt = mysqli_prepare($conn, $sql);

    if ($stmt) {
        foreach ($all_br_ids as $br_id) {
            // ดึงข้อมูล request, reserve, และ time สำหรับ br_id ปัจจุบัน
            $request_arr = $all_request[$br_id] ?? [];
            $reserve_arr = $all_reserve[$br_id] ?? [];
            $time_arr = $all_time[$br_id] ?? [];
            $time_plus_arr = $all_time_plus[$br_id] ?? [];

            // สร้าง JSON object
            $qr_request_json = json_encode([
                'request' => array_values($request_arr),
                'reserve' => array_values($reserve_arr),
                'time'    => array_values($time_arr),
                'time_plus' => array_values($time_plus_arr)
            ], JSON_UNESCAPED_UNICODE);

            // ผูกค่า parameter และ execute คำสั่ง
            mysqli_stmt_bind_param($stmt, 'si', $qr_request_json, $br_id);
            mysqli_stmt_execute($stmt);
        }
        mysqli_stmt_close($stmt);
    } else {
        // จัดการ error กรณีที่ prepare statement ไม่สำเร็จ
        error_log("MySQLi prepare failed: " . mysqli_error($conn));
    }

    // redirect กลับไปหน้าจัดการคิว
    header('Location: request.php');
    exit;
}
