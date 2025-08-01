<?php
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // รับข้อมูล date, request, และ reserve จากฟอร์ม
    $all_dates = $_POST['date'] ?? [];
    $all_requests = $_POST['request'] ?? [];
    $all_reserves = $_POST['reserve'] ?? []; // รับข้อมูล reserve
    $all_time_plus = $_POST['time_plus'] ?? [];

    // สร้าง array สำหรับจัดกลุ่มข้อมูลตาม br_id และ วันที่
    $plans_by_date = [];

    // วนลูปตาม br_id ที่ส่งมา
    foreach ($all_dates as $br_id => $types) {
        if (isset($types['request'])) {
            // วนลูปตามข้อมูล request ของแต่ละ br_id
            foreach ($types['request'] as $idx => $datetime) {
                // ข้ามไปถ้าไม่มีข้อมูลวันที่หรือเวลา
                if (empty($datetime)) {
                    continue;
                }

                // แยกวันที่และเวลาออกจาก datetime-local
                $date_obj = new DateTime($datetime);
                $pr_date = $date_obj->format('Y-m-d');
                $pr_time = $date_obj->format('H:i:s');
                
                $qr_request_code = $all_requests[$br_id][$idx] ?? null;

                if ($qr_request_code !== null) {
                    // จัดกลุ่มข้อมูลตาม br_id และ pr_date
                    if (!isset($plans_by_date[$br_id][$pr_date])) {
                        $plans_by_date[$br_id][$pr_date] = [
                            'request' => [],
                            'reserve' => $all_reserves[$br_id] ?? [], // เพิ่ม reserve เข้าไปในแผน
                            'time' => [],
                            'time_plus' => [],
                        ];
                    }
                    $plans_by_date[$br_id][$pr_date]['request'][] = $qr_request_code;
                    $plans_by_date[$br_id][$pr_date]['time'][] = $pr_time;
                    $plans_by_date[$br_id][$pr_date]['time_plus'][] = $all_time_plus[$br_id][$idx] ?? '90';
                }
            }
        }
    }

    // เตรียมคำสั่ง SQL สำหรับ INSERT หรือ UPDATE ข้อมูล (UPSERT)
    // หากมีข้อมูลสำหรับ br_id และ pr_date อยู่แล้ว จะทำการ UPDATE pr_request
    $sql = "INSERT INTO `plan_request` (`br_id`, `pr_date`, `pr_request`, `pr_status`, `pr_loc`) VALUES (?, ?, ?, 0, Now())
            ON DUPLICATE KEY UPDATE `pr_request` = VALUES(`pr_request`), `pr_status` = 0";
    $stmt = mysqli_prepare($conn, $sql);

    if ($stmt) {
        // วนลูปข้อมูลที่จัดกลุ่มแล้วเพื่อทำการ INSERT หรือ UPDATE
        foreach ($plans_by_date as $br_id => $dates) {
            foreach ($dates as $pr_date => $plan_data) {
                // สร้าง JSON สำหรับคอลัมน์ pr_request
                $pr_request_json = json_encode($plan_data, JSON_UNESCAPED_UNICODE);
                
                // ผูกค่า parameter และ execute คำสั่ง
                mysqli_stmt_bind_param($stmt, 'iss', $br_id, $pr_date, $pr_request_json);
                mysqli_stmt_execute($stmt);
            }
        }
        mysqli_stmt_close($stmt);
    } else {
        // จัดการ error กรณีที่ prepare statement ไม่สำเร็จ
        error_log("MySQLi prepare failed: " . mysqli_error($conn));
    }

    // redirect กลับไปหน้าจัดการ
    header('Location: request_sale.php');
    exit;
}
