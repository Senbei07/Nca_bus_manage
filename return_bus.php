<?php 



    echo $rou = $_POST['route']
    echo $rou = $_POST['date']
    echo $rou = $_POST['time']
    echo $rou = $_POST['id']

    
    $group_id = $_POST['group_id'];
    $sql = "UPDATE `group` SET main_dri = '$em_id' WHERE group_id = '$group_id'";

    $result = mysqli_query($conn, $sql);
    if (mysqli_query($conn, $sql)) {
    echo "Update สำเร็จ!";
    } else {
        echo "Error: " . mysqli_error($conn);
    }
$sql = 'SELECT * FROM `dri_plan_t`WHERE dpt_date_end = '2025-07-11' AND dpt_time_end < '12:00:00' AND br_id = 1 AND pt_id ='2';';

?>