<?php 

    include('config.php');

    $em_id = $_POST['driver_id'];
    $group_id = $_POST['group_id'];
    
    echo $rou = $_POST['route']
    echo $rou = $_POST['date']
    echo $rou = $_POST['time']
    echo $rou = $_POST['id']


    
    $sql = "UPDATE `group` SET main_dri = '$em_id' WHERE group_id = '$group_id'";

    $result = mysqli_query($conn, $sql);
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