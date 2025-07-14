<?php 

    include('config.php');

    $em_id = $_POST['dri_id'];
    $bus_id = $_POST['bus_id'];
    $group_id = $_POST['group_id'];
    


    
    $sql = "UPDATE `group` SET main_dri = '$em_id', bi_id = '$bus_id' WHERE group_id = '$group_id'";

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