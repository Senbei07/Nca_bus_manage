<?php 
    session_start();
    $conn = mysqli_connect("localhost","root","","nca_mange_plan");
    
    // Set charset to UTF-8
    if ($conn) {
        mysqli_set_charset($conn, "utf8");
    }
?>