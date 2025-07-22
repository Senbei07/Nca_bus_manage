<?php
    include 'config.php';


    if (!$conn) {
        die("Connection failed: " . mysqli_connect_error());
    }

    $sql_main = "SELECT * FROM `employee` WHERE main_route = 1 AND et_id = 1 order by em_queue";
    $sql_ex = "SELECT * FROM `employee` WHERE main_route = 1 AND et_id = 2 order by em_queue";
    $sql_coach = "SELECT * FROM `employee` WHERE main_route = 1 AND et_id = 3 order by em_queue";

    $result_main = mysqli_query($conn, $sql_main);
    $result_ex = mysqli_query($conn, $sql_ex);      
    $result_coach = mysqli_query($conn, $sql_coach);

    $main = [];
    $ex = [];
    $coach = [];

    $notredy = [];


    $queue_num = 5;

    $i = 1;
    $a = 1;
    $reserve  = false;

    while($row_main = mysqli_fetch_assoc($result_main)) {

        // ...existing code...
        if($row_main['es_id'] != 1 || $reserve) {
            $notredy[] = [
                'em_name' => $row_main['em_name'],
                'em_surname' => $row_main['em_surname'],
                'em_queue' => '1-'.$a
            ];
            $a++;
        }else{
            $main[] = [
                'em_name' => $row_main['em_name'],
                'em_surname' => $row_main['em_surname'],
                'em_queue' => '3-'.$i
            ];
            $i++;
            if($i > $queue_num) {
                $reserve  = true;
                
            }
        }

    }

    $ex = [];
    $exnotredy = [];
    $reserve = false;
    $i = 1;
    $a = 1;


    while($row_ex = mysqli_fetch_assoc($result_ex)) {

        // ...existing code...
        if($row_ex['es_id'] != 1 || $reserve) {
            $exnotredy[] = [
                'em_name' => $row_ex['em_name'],
                'em_surname' => $row_ex['em_surname'],
                'em_queue' => '1-'.$a
            ];
            $a++;
        }else{
            $ex[] = [
                'em_name' => $row_ex['em_name'],
                'em_surname' => $row_ex['em_surname'],
                'em_queue' => '2-'.$i
            ];
            $i++;
            if($i > $queue_num) {
                $reserve  = true;
                
            }
        }

    }

    $coach = [];
    $coachnotredy = [];
    $reserve = false;
    $i = 1;
    $a = 1;


    while($row_coach = mysqli_fetch_assoc($result_coach)) {

        // ...existing code...
        if($row_coach['es_id'] != 1 || $reserve) {
            $coachnotredy[] = [
                'em_name' => $row_coach['em_name'],
                'em_surname' => $row_coach['em_surname'],
                'em_queue' => '1-'.$a
            ];
            $a++;
        }else{
            $coach[] = [
                'em_name' => $row_coach['em_name'],
                'em_surname' => $row_coach['em_surname'],
                'em_queue' => '2-'.$i
            ];
            $i++;
            if($i > $queue_num) {
                $reserve  = true;
                
            }
        }

    }
    
    if($queue_num > count($main)) {
        echo "</tr><br>";
        echo "</tr><br>";
        echo "ต้องมีกลับหัว";
        echo "</tr><br>";
        echo "</tr><br>";
    }

    // ทำกลับหัวให้ main
    $x = 0;
    while($queue_num > count($main)){
        $main[] = [
            'em_name' => $main[$x]['em_name'],
            'em_surname' => $main[$x]['em_surname'],
            'em_queue' => '3-'.$i
        ];
        $i++;
        $x++;
    }

    // ทำกลับหัวให้ ex
    $x = 0;
    $i = count($ex) > 0 ? (int)explode('-', $ex[count($ex)-1]['em_queue'])[1] + 1 : 1;
    while($queue_num > count($ex)){
        $ex[] = [
            'em_name' => $ex[$x]['em_name'],
            'em_surname' => $ex[$x]['em_surname'],
            'em_queue' => '2-'.$i
        ];
        $i++;
        $x++;
    }

    // ทำกลับหัวให้ coach
    $x = 0;
    $i = count($coach) > 0 ? (int)explode('-', $coach[count($coach)-1]['em_queue'])[1] + 1 : 1;
    while($queue_num > count($coach)){
        $coach[] = [
            'em_name' => $coach[$x]['em_name'],
            'em_surname' => $coach[$x]['em_surname'],
            'em_queue' => '2-'.$i
        ];
        $i++;
        $x++;
    }

    // แสดงผล $main ในรูปแบบตาราง
    echo "<h3>รายชื่อหลัก</h3>";
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>ลำดับคิว</th><th>ชื่อ</th><th>นามสกุล</th></tr>";
    foreach($main as $row) {
        echo "<tr>";
        echo "<td>{$row['em_queue']}</td>";
        echo "<td>{$row['em_name']}</td>";
        echo "<td>{$row['em_surname']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "<br>จำนวนทั้งหมด: ".count($main)."<br><br>";

    // แสดงผล $notredy ในรูปแบบตาราง
    echo "<h3>รายชื่อสำรอง/ไม่พร้อม</h3>";
    if(count($notredy) > 0) {
        echo "<table border='1' cellpadding='5' cellspacing='0'>";
        echo "<tr><th>ลำดับคิว</th><th>ชื่อ</th><th>นามสกุล</th></tr>";
        foreach($notredy as $row) {
            echo "<tr>";
            echo "<td>{$row['em_queue']}</td>";
            echo "<td>{$row['em_name']}</td>";
            echo "<td>{$row['em_surname']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<i>ไม่มีข้อมูล</i>";
    }
    echo "<br>";

    // แสดงผล $ex ในรูปแบบตาราง
    echo "<h3>รายชื่อ Ex</h3>";
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>ลำดับคิว</th><th>ชื่อ</th><th>นามสกุล</th></tr>";
    foreach($ex as $row) {
        echo "<tr>";
        echo "<td>{$row['em_queue']}</td>";
        echo "<td>{$row['em_name']}</td>";
        echo "<td>{$row['em_surname']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "<br>จำนวนทั้งหมด: ".count($ex)."<br><br>";

    // แสดงผล $exnotredy ในรูปแบบตาราง
    echo "<h3>รายชื่อ Ex สำรอง/ไม่พร้อม</h3>";
    if(count($exnotredy) > 0) {
        echo "<table border='1' cellpadding='5' cellspacing='0'>";
        echo "<tr><th>ลำดับคิว</th><th>ชื่อ</th><th>นามสกุล</th></tr>";
        foreach($exnotredy as $row) {
            echo "<tr>";
            echo "<td>{$row['em_queue']}</td>";
            echo "<td>{$row['em_name']}</td>";
            echo "<td>{$row['em_surname']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<i>ไม่มีข้อมูล</i>";
    }
    echo "<br>";

    // แสดงผล $coach ในรูปแบบตาราง
    echo "<h3>รายชื่อ Coach</h3>";
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>ลำดับคิว</th><th>ชื่อ</th><th>นามสกุล</th></tr>";
    foreach($coach as $row) {
        echo "<tr>";
        echo "<td>{$row['em_queue']}</td>";
        echo "<td>{$row['em_name']}</td>";
        echo "<td>{$row['em_surname']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "<br>จำนวนทั้งหมด: ".count($coach)."<br><br>";

    // แสดงผล $coachnotredy ในรูปแบบตาราง
    echo "<h3>รายชื่อ Coach สำรอง/ไม่พร้อม</h3>";
    if(count($coachnotredy) > 0) {
        echo "<table border='1' cellpadding='5' cellspacing='0'>";
        echo "<tr><th>ลำดับคิว</th><th>ชื่อ</th><th>นามสกุล</th></tr>";
        foreach($coachnotredy as $row) {
            echo "<tr>";
            echo "<td>{$row['em_queue']}</td>";
            echo "<td>{$row['em_name']}</td>";
            echo "<td>{$row['em_surname']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<i>ไม่มีข้อมูล</i>";
    }
    echo "<br>";


   
?>