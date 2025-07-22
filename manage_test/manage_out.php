<?php
    include 'config.php';


    if (!$conn) {
        die("Connection failed: " . mysqli_connect_error());
    }

    $sql_main = "SELECT * FROM `employee` WHERE main_route >= 2 AND  main_route <= 4  AND et_id = 1 order by em_queue";



    $result_main = mysqli_query($conn, $sql_main);

    $sql_re = "SELECT * FROM `queue_request`";
    $result_re = mysqli_query($conn, $sql_re);
    $queue = [];
    $re = [];
    while ($row = mysqli_fetch_assoc($result_re)) {
        $re[$row['br_id']][] = $row['qr_request'];
        if($row['qr_request'] != '0') {
            $queue[] = $row['qr_request'];
        }
    }

    $queue_num = 5;
    $route_name = [];

    while($row_main= mysqli_fetch_assoc($result_main)) {
        if(!(in_array($row_main['main_route'], $route_name))) {
            $route_name[] = $row_main['main_route'];
        }

        // echo "<td>{$row_main['em_name']} {$row_main['em_surname']} ({$row_main['em_queue']})</td><br>";
        if(in_array($row_main['em_queue'], $queue)) {
            $main_re[] = $row_main;
        } else{
            $main[] = $row_main;
        }
    }
    print_r($route_name);




    $plan = [3,3,3];

    $new_plan = [];
    $new_break = [];

    $i = 0;
    foreach($plan as $key => $value){
        $j = 1;
        $x = 1;
        $r_key = $route_name[$i];
        $re_count = isset($re[$r_key]) ? count($re[$r_key]) : 0;
        while ($j <= $value || $j <= $re_count) {
            $re_value = ($j-1 < $re_count) ? $re[$r_key][$j-1] : '0';
            if($j <= $value) {
                if($re_value == '0' ) {
                    // ใช้ r_key (route จริง) ใน filter
                    $filtered = array_filter($main, function($item) use ($r_key) {
                        return $item['main_route'] == $r_key && $item['es_id'] == '1';
                    });
                    $first = reset($filtered);
                    $firstKey = key($filtered);
                    if ($firstKey !== null && $first !== false) {
                        $new_plan[$r_key][$j] = $first['em_name'] . ' ' . $first['em_surname'] . ' (' . $first['em_queue'] . ')';
                        unset($main[$firstKey]);
                        $main = array_values($main);
                    } else {
                        $new_plan[$r_key][$j] = $new_plan[$r_key][$x];
                        $x++;
                    }
                } else {
                    // หา em_queue ใน main_re แล้วแสดงชื่อ-นามสกุล
                    $idx = array_search($re_value, array_column($main_re, 'em_queue'));
                    if ($idx !== false && isset($main_re[$idx])) {
                        $emp = $main_re[$idx];
                        $new_plan[$r_key][$j] = $emp['em_name'] . ' ' . $emp['em_surname'] . ' (' . $emp['em_queue'] . ')';
                    } else {
                        $new_plan[$r_key][$j] = '-';
                    }
                }
            } else {
                $new_break[$r_key][$j] = $re_value;
            }
            $j++;
        }
        $i++;
    }

    // แสดงผล new_plan
    echo "<h3>new_plan</h3>";
    if (!empty($new_plan)) {
        echo "<table border='1' cellpadding='5' cellspacing='0'>";
        echo "<tr><th>Route</th><th>Queue</th><th>Employee</th></tr>";
        foreach($new_plan as $route => $vals) {
            foreach($vals as $idx => $v) {
                echo "<tr>";
                echo "<td>{$route}</td>";
                echo "<td>{$idx}</td>";
                echo "<td>{$v}</td>";
                echo "</tr>";
            }
        }
        echo "</table><br>";
    } else {
        echo "<i>new_plan is empty</i><br>";
    }

    echo "<h3>new_break</h3>";
    if (!empty($new_break)) {
        echo "<table border='1' cellpadding='5' cellspacing='0'>";
        echo "<tr><th>Route</th><th>Queue</th><th>Break Value</th></tr>";
        foreach($new_break as $route => $vals) {
            foreach($vals as $idx => $v) {
                echo "<tr>";
                echo "<td>{$route}</td>";
                echo "<td>{$idx}</td>";
                echo "<td>{$v}</td>";
                echo "</tr>";
            }
        }
        echo "</table><br>";
    } else {
        echo "<i>new_break is empty</i><br>";
    }

    // แยก $main ที่เหลือไปตาม main_route
    $main_2 = [];
    $main_3 = [];
    $main_4 = [];
    foreach ($main as $item) {
        if (isset($item['main_route'])) {
            if ($item['main_route'] == 2) {
                $main_2[] = $item;
            } elseif ($item['main_route'] == 3) {
                $main_3[] = $item;
            } elseif ($item['main_route'] == 4) {
                $main_4[] = $item;
            }
        }
    }



    // ถ้า new_break มีข้อมูล ให้นำไปแยกใส่ใน main_2, main_3, main_4 ตาม main_route
    foreach ($new_break as $route => $vals) {
        foreach ($vals as $idx => $v) {
            // ข้ามถ้า v เป็น 0 หรือว่าง
            if ($v === '0' || $v === '-' || empty($v)) continue;
            // หาใน main_re ก่อน (กรณี v เป็น em_queue)
            $emp = null;
            $find_idx = array_search($v, array_column($main_re, 'em_queue'));
            if ($find_idx !== false && isset($main_re[$find_idx])) {
                $emp = $main_re[$find_idx];
            }
            // ใส่เข้า main_X ตาม route ที่ถูกเรียก
            if ($emp) {
                if ($route == '2') {
                    $main_2[] = $emp;
                } elseif ($route == '3') {
                    $main_3[] = $emp;
                } elseif ($route == '4') {
                    $main_4[] = $emp;
                }
            }
        }
    }

    // แสดงผลแต่ละกลุ่ม
    echo "<h3>main_2</h3>";
    if (!empty($main_2)) {
        echo "<table border='1' cellpadding='5' cellspacing='0'>";
        echo "<tr><th>ชื่อ</th><th>นามสกุล</th><th>คิว</th></tr>";
        foreach ($main_2 as $employee) {
            echo "<tr>";
            echo "<td>{$employee['em_name']}</td>";
            echo "<td>{$employee['em_surname']}</td>";
            echo "<td>{$employee['em_queue']}</td>";
            echo "</tr>";
        }
        echo "</table><br>";
    } else {
        echo "<i>main_2 is empty</i><br>";
    }

    echo "<h3>main_3</h3>";
    if (!empty($main_3)) {
        echo "<table border='1' cellpadding='5' cellspacing='0'>";
        echo "<tr><th>ชื่อ</th><th>นามสกุล</th><th>คิว</th></tr>";
        foreach ($main_3 as $employee) {
            echo "<tr>";
            echo "<td>{$employee['em_name']}</td>";
            echo "<td>{$employee['em_surname']}</td>";
            echo "<td>{$employee['em_queue']}</td>";
            echo "</tr>";
        }
        echo "</table><br>";
    } else {
        echo "<i>main_3 is empty</i><br>";
    }

    echo "<h3>main_4</h3>";
    if (!empty($main_4)) {
        echo "<table border='1' cellpadding='5' cellspacing='0'>";
        echo "<tr><th>ชื่อ</th><th>นามสกุล</th><th>คิว</th></tr>";
        foreach ($main_4 as $employee) {
            echo "<tr>";
            echo "<td>{$employee['em_name']}</td>";
            echo "<td>{$employee['em_surname']}</td>";
            echo "<td>{$employee['em_queue']}</td>";
            echo "</tr>";
        }
        echo "</table><br>";
    } else {
        echo "<i>main_4 is empty</i><br>";
    }




?>