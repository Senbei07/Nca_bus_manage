<?php
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // รับข้อมูลจากฟอร์ม
    $common_date = $_POST['common_date'] ?? '';
    $all_requests = $_POST['request'] ?? [];
    $all_reserves = $_POST['reserve'] ?? [];
    $all_times = $_POST['time'] ?? [];
    $all_time_plus = $_POST['time_plus'] ?? [];
    $all_points = $_POST['point'] ?? [];
    // เพิ่ม ex driver ถ้ามี
    $all_ex = $_POST['ex'] ?? [];
    $all_plan_names = $_POST['plan_name'] ?? [];

    // ตรวจสอบวันที่
    if (empty($common_date)) {
        die('ไม่พบวันที่');
    }

    // เตรียมข้อมูลสำหรับแต่ละสาย (br_id)
    foreach ($all_requests as $br_id => $requests) {
        // decode ex string json เป็น array
        $ex_arr = [];
        if (isset($_POST['ex'][$br_id])) {
            foreach ($_POST['ex'][$br_id] as $ex_val) {
                if (is_string($ex_val) && $ex_val !== '') {
                    $decoded = json_decode($ex_val, true);
                    $ex_arr[] = is_array($decoded) ? $decoded : [];
                } else {
                    $ex_arr[] = [];
                }
            }
        }
        $plan_data = [
            'request' => $requests,
            'reserve' => $all_reserves[$br_id] ?? [],
            'time' => $all_times[$br_id] ?? [],
            'time_plus' => $all_time_plus[$br_id] ?? [],
            'point' => [],
            'ex' => $ex_arr,
        ];
        // normalize point เป็น array ของ array
        if (isset($all_points[$br_id])) {
            foreach ($all_points[$br_id] as $p) {
                if (is_array($p)) {
                    $plan_data['point'][] = array_map('strval', $p);
                } else {
                    $arr = array_filter(explode(',', $p), 'strlen');
                    $plan_data['point'][] = array_map('strval', $arr);
                }
            }
        }

        // รับชื่อแผน
        $plan_name = $all_plan_names[$br_id] ?? '';

        $sql = "INSERT INTO `plan_request` (`br_id`, `pr_name`, `pr_date`, `pr_request`, `pr_status`, `pr_loc`) VALUES (?, ?, ?, ?, 0, Now())
                ON DUPLICATE KEY UPDATE `pr_request` = VALUES(`pr_request`), `pr_status` = 0, `pr_name` = VALUES(`pr_name`)";
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt) {
            $pr_request_json = json_encode($plan_data, JSON_UNESCAPED_UNICODE);
            mysqli_stmt_bind_param($stmt, 'isss', $br_id, $plan_name, $common_date, $pr_request_json);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        } else {
            error_log("MySQLi prepare failed: " . mysqli_error($conn));
        }
    }

    // redirect กลับไปหน้าจัดการ
    header('Location: request_sale.php');
    exit;
}
