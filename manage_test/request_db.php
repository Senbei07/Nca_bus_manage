<?php
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // รับข้อมูล request[br_id][] และ reserve[br_id][]
    $all_request = $_POST['request'] ?? [];
    $all_reserve = $_POST['reserve'] ?? [];

    foreach ($all_request as $br_id => $request_arr) {
        $reserve_arr = $all_reserve[$br_id] ?? [];
        $qr_request = json_encode([
            'request' => array_values($request_arr),
            'reserve' => array_values($reserve_arr)
        ], JSON_UNESCAPED_UNICODE);

        $br_id_safe = mysqli_real_escape_string($conn, $br_id);
        $qr_request_safe = mysqli_real_escape_string($conn, $qr_request);
        $sql = "UPDATE `queue_request` SET `qr_request`='$qr_request_safe' WHERE `br_id`='$br_id_safe'";
        mysqli_query($conn, $sql);
    }

    // redirect กลับหรือแสดงผลลัพธ์
    header('Location: request.php');
    exit;
}
