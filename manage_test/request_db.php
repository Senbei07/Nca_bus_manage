<?php
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // รับข้อมูล request, reserve, และ time จากฟอร์ม
    $all_request = $_POST['request'] ?? [];
    $all_reserve = $_POST['reserve'] ?? [];
    $all_time = $_POST['time'] ?? [];
    $all_time_plus = $_POST['time_plus'] ?? [];
    $all_point = $_POST['point'] ?? [];
    $all_plan_type = $_POST['plan_type'] ?? [];
    $all_plan_names = $_POST['plan_name'] ?? [];

    // รับข้อมูล ex driver (จุดจอดขึ้น/ลง คนที่ 1/2)
    $all_ex_start1 = $_POST['ex_start1'] ?? [];
    $all_ex_end1 = $_POST['ex_end1'] ?? [];
    $all_ex_start2 = $_POST['ex_start2'] ?? [];
    $all_ex_end2 = $_POST['ex_end2'] ?? [];

    echo "<script>console.log('Received data:', " . json_encode([
        'requests' => $all_request,
        'reserves' => $all_reserve,
        'times' => $all_time,
        'time_pluses' => $all_time_plus,
        'points' => $all_point
    ]) . ");</script>";

    // สร้าง array ของ br_id ทั้งหมดที่จะทำการอัปเดต
    $all_br_ids = array_unique(array_merge(array_keys($all_request), array_keys($all_reserve)));

    // เตรียม SQL สำหรับอัปเดต queue_request
    $sql_update = "UPDATE `queue_request` SET `qr_request` = ? WHERE `br_id` = ?";
    $stmt_update = mysqli_prepare($conn, $sql_update);

    // เตรียม SQL สำหรับ insert plan_request
    $sql_insert = "INSERT INTO `plan_request` (`br_id`, pr_name, `pr_date`, `pr_request`, `pr_plus`, `pr_status`, `pr_loc`) VALUES (?, ?, 0, ?, 0, 3, NOW())";
    $stmt_insert = mysqli_prepare($conn, $sql_insert);

    foreach ($all_br_ids as $br_id) {
        $plan_type = $all_plan_type[$br_id] ?? 'standard';
        $request_arr = $all_request[$br_id] ?? [];
        $reserve_arr = $all_reserve[$br_id] ?? [];
        $time_arr = $all_time[$br_id] ?? [];
        $time_plus_arr = $all_time_plus[$br_id] ?? [];
        $point_arr = $all_point[$br_id] ?? [];

        // --- ex driver array mapping ---
        $ex_start1_arr = $all_ex_start1[$br_id] ?? [];
        $ex_end1_arr = $all_ex_end1[$br_id] ?? [];
        $ex_start2_arr = $all_ex_start2[$br_id] ?? [];
        $ex_end2_arr = $all_ex_end2[$br_id] ?? [];

        // point_arr เป็น array ของ string (เช่น "1,2,3") หรือ "" -> ต้องแปลงเป็น array ของ int
        $point_data = [];
        foreach ($point_arr as $pt) {
            if (trim($pt) === '') {
                $point_data[] = [];
            } else {
                $point_data[] = array_map('intval', explode(',', $pt));
            }
        }
        // --- ex driver: สร้าง array ของ object {start1, end1, start2, end2} ---
        $ex_data = [];
        $max_ex = max(count($request_arr), count($ex_start1_arr), count($ex_end1_arr), count($ex_start2_arr), count($ex_end2_arr));
        for ($i = 0; $i < $max_ex; $i++) {
            $ex_data[] = [
                'start1' => isset($ex_start1_arr[$i]) ? strval($ex_start1_arr[$i]) : '',
                'end1'   => isset($ex_end1_arr[$i]) ? strval($ex_end1_arr[$i]) : '',
                'start2' => isset($ex_start2_arr[$i]) ? strval($ex_start2_arr[$i]) : '',
                'end2'   => isset($ex_end2_arr[$i]) ? strval($ex_end2_arr[$i]) : '',
            ];
        }

        // สร้าง JSON object
        $qr_request_json = json_encode([
            'request' => array_values($request_arr),
            'reserve' => array_values($reserve_arr),
            'time'    => array_values($time_arr),
            'time_plus' => array_values($time_plus_arr),
            'point' => $point_data,
            'ex' => $ex_data
        ], JSON_UNESCAPED_UNICODE);

        if ($plan_type === 'special') {
            // เพิ่มข้อมูลใหม่ใน plan_request
            if ($stmt_insert) {
                mysqli_stmt_bind_param($stmt_insert, 'iss', $br_id, $all_plan_names[$br_id], $qr_request_json);
                mysqli_stmt_execute($stmt_insert);
            }
        } else {
            // อัปเดต queue_request ตามปกติ
            if ($stmt_update) {
                mysqli_stmt_bind_param($stmt_update, 'si', $qr_request_json, $br_id);
                mysqli_stmt_execute($stmt_update);
            }
        }
    }
    if ($stmt_update) mysqli_stmt_close($stmt_update);
    if ($stmt_insert) mysqli_stmt_close($stmt_insert);

    // redirect กลับไปหน้าจัดการคิว
    header('Location: request.php');
    exit;
}
