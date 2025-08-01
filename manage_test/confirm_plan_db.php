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
$plan_date = $_POST['plan_date'] ?? date('Y-m-d');

$conn->begin_transaction();
try {
    // Prepare statements
    $stmt_archive = $conn->prepare(
        "UPDATE plan_request SET pr_status = 2 WHERE br_id = ? AND pr_date = ? AND (pr_status = 0 OR pr_status = 1)"
    );
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
        
        $plan_data = [
            'request' => array_values($valid_requests),
            'reserve' => array_values($reserves),
            'time' => array_values($valid_times),
            'time_plus' => array_values($time_pluses)
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
