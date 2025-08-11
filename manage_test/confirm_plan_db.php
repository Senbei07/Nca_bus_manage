<?php
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: confirm_plan.php');
    exit;
}

$all_requests = $_POST['request'] ?? [];
$all_times = $_POST['time'] ?? [];
$all_reserves = $_POST['reserve'] ?? [];
$all_sources = $_POST['source'] ?? [];
$all_time_pluses = $_POST['time_plus'] ?? [];
$all_points = $_POST['point'] ?? [];
$plan_date = $_POST['plan_date'] ?? date('Y-m-d');

// รับข้อมูล ex driver
$all_ex_start1 = $_POST['ex_start1'] ?? [];
$all_ex_end1 = $_POST['ex_end1'] ?? [];
$all_ex_start2 = $_POST['ex_start2'] ?? [];
$all_ex_end2 = $_POST['ex_end2'] ?? [];

echo "<script>console.log('Received data:', " . json_encode([
    'requests' => $all_requests,
    'times' => $all_times,
    'reserves' => $all_reserves,
    'sources' => $all_sources,
    'time_pluses' => $all_time_pluses,
    'points' => $all_points,
    'ex_start1' => $all_ex_start1,
    'ex_end1' => $all_ex_end1,
    'ex_start2' => $all_ex_start2,
    'ex_end2' => $all_ex_end2
]) . ");</script>";



$conn->begin_transaction();
try {
    // Prepare statements
    $stmt_archive = $conn->prepare(
        "UPDATE plan_request SET pr_status = 2 WHERE br_id = ? AND pr_date = ? AND (pr_status = 0 OR pr_status = 1)"
    );
    // --- แก้ไข: ให้ pr_status = 0 (pending) ตอน insert ---
    $stmt_insert = $conn->prepare(
        "INSERT INTO plan_request (br_id, pr_date, pr_request, pr_status, pr_loc) VALUES (?, ?, ?, 1, NOW())"
    );

    foreach ($all_requests as $br_id => $requests) {
        // Filter out empty time values which correspond to the "new" row if not filled
        $valid_times = array_filter($all_times[$br_id] ?? []);
        $valid_requests = array_intersect_key($requests, $valid_times);

        if (empty($valid_requests)) {
            continue; // Skip if no valid entries for this route
        }

        $reserves = $all_reserves[$br_id] ?? [];
        $time_pluses = array_intersect_key($all_time_pluses[$br_id] ?? [], $valid_times);
        
        // --- เพิ่มการจัดการ point ---
        $points_raw = $all_points[$br_id] ?? [];
        $valid_points = [];
        foreach (array_keys($valid_requests) as $idx) {
            $point_str = $points_raw[$idx] ?? '';
            // แปลงเป็น array ของ int (หรือ string)
            if ($point_str === '' || $point_str === null) {
                $valid_points[] = [];
            } else {
                // รองรับทั้ง string "31,32,33" หรือ array
                if (is_array($point_str)) {
                    $valid_points[] = $point_str;
                } else {
                    $arr = array_filter(explode(',', $point_str), fn($v) => $v !== '');
                    // แปลงเป็น int ทั้งหมด
                    $valid_points[] = array_map('intval', $arr);
                }
            }
        }

        // --- เพิ่มการจัดการ ex driver ---
        $ex_start1_arr = $all_ex_start1[$br_id] ?? [];
        $ex_end1_arr = $all_ex_end1[$br_id] ?? [];
        $ex_start2_arr = $all_ex_start2[$br_id] ?? [];
        $ex_end2_arr = $all_ex_end2[$br_id] ?? [];
        $ex_data = [];
        $max_ex = max(count($valid_requests), count($ex_start1_arr), count($ex_end1_arr), count($ex_start2_arr), count($ex_end2_arr));
        foreach (array_keys($valid_requests) as $i) {
            $ex_data[] = [
                'start1' => isset($ex_start1_arr[$i]) ? strval($ex_start1_arr[$i]) : '',
                'end1'   => isset($ex_end1_arr[$i]) ? strval($ex_end1_arr[$i]) : '',
                'start2' => isset($ex_start2_arr[$i]) ? strval($ex_start2_arr[$i]) : '',
                'end2'   => isset($ex_end2_arr[$i]) ? strval($ex_end2_arr[$i]) : '',
            ];
        }
        $plan_data = [
            'request' => array_values($valid_requests),
            'reserve' => array_values($reserves),
            'time' => array_values($valid_times),
            'time_plus' => array_values($time_pluses),
            'point' => $valid_points,
            'ex' => $ex_data
        ];
        $pr_request_json = json_encode($plan_data, JSON_UNESCAPED_UNICODE);

        // 1. Archive any existing pending (0) or confirmed (1) plans for this route and date
        $stmt_archive->bind_param('is', $br_id, $plan_date);
        $stmt_archive->execute();

        // 2. Insert the new plan as pending (status 0)
        $stmt_insert->bind_param('iss', $br_id, $plan_date, $pr_request_json);
        $stmt_insert->execute();
    }

    $stmt_archive->close();
    $stmt_insert->close();
    $conn->commit();

} catch (Exception $e) {
    $conn->rollback();
    die("เกิดข้อผิดพลาดในการบันทึก: " . $e->getMessage());
}

// Redirect back with the date to show the updated status
header("Location: confirm_plan.php?date=" . urlencode($plan_date));
exit;
?>
