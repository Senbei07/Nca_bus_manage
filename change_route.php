<?php 
        include("config.php");

    echo $id = $_POST['change_id'];
    echo'<br>';
    echo $date = $_POST['change_date'];
    echo'<br>';
    echo $time = $_POST['change_time'];
    echo'<br>';
    echo $lo = $_POST['change_lo'];
    echo'<br>';
$data = [];
$case = "";
$idList = [];

$sql = "SELECT dpt_id, `group` FROM `dri_plan_t` WHERE dpt_date_start = '$date' AND br_id = $lo AND dpt_time_start >= '$time';";
$result = mysqli_query($conn, $sql);

// เก็บ id และ group แยกก่อน
$ids = [];
$groups = [];

while ($row = mysqli_fetch_assoc($result)) {
    $ids[] = $row['dpt_id'];
    $groups[] = $row['group'];
}

// หมุน group ขึ้น 1 ตำแหน่ง
$first_group = array_shift($groups); // เอาตัวแรกออก
$groups[] = $first_group;            // ไปต่อท้าย

// รวม id กับ group
$data = [];
foreach ($ids as $index => $id) {
    $data[] = [
        'id' => $id,
        'group' => $groups[$index]
    ];
}

foreach ($data as $row) {
    $id = (int)$row['id'];
    $group = mysqli_real_escape_string($conn, $row['group']);
    $case .= "WHEN $id THEN '$group' ";
    $idList[] = $id;
}

$idStr = implode(",", $idList);

$sql = "UPDATE dri_plan_t SET `group` = CASE dpt_id $case END WHERE dpt_id IN ($idStr)";

// สั่ง query
if (mysqli_query($conn, $sql)) {
    echo "Update สำเร็จ!";
} else {
    echo "Error: " . mysqli_error($conn);
}

// ทดสอบแสดงผล
echo "<pre>";
print_r($data);
echo "</pre>";

header('location: manage2.php');

?>