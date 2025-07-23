<?php
    include 'config.php';


    if (!$conn) {
        die("Connection failed: " . mysqli_connect_error());
    }

    $sql_main = "SELECT * FROM `employee` WHERE main_route >= 2 AND  main_route <= 4  AND et_id = 1 order by em_queue";
    $sql_ex = "SELECT * FROM `employee` WHERE main_route >= 2 AND  main_route <= 4  AND et_id = 2 order by em_queue";
    $sql_coach = "SELECT * FROM `employee` WHERE main_route >= 2 AND  main_route <= 4  AND et_id = 3 order by em_queue";

    $result_main = mysqli_query($conn, $sql_main);
    $result_ex = mysqli_query($conn, $sql_ex);
    $result_coach = mysqli_query($conn, $sql_coach);

    $sql_re = "SELECT * FROM `queue_request` ORDER BY br_id";
    $result_re = mysqli_query($conn, $sql_re);
    $queue = [];
    $re = [];
    while ($row = mysqli_fetch_assoc($result_re)) {

        $qr_request = json_decode($row['qr_request'], true);

        $in_request = false;
        foreach($qr_request['request'] as $v) {
            if ($v !== "0") {
                $queue[] = $v;
            }else{
            }
            $re[$row['br_id']][] = $v;
        }
        foreach($qr_request['reserve'] as $v) {
            if ($v !== "0") {
                $queue[] = $v;
            }
            $re[$row['br_id']][] = $v;
        }
    }

    

     $queue_num = [
        '2' =>3,
        '3' => 3,
        '4' => 3
    ];

// Bootstrap HTML header
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Out</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
<?php





    $route_name = [];
    $main_re = [];

    while($row_main= mysqli_fetch_assoc($result_main)) {
        if(!(in_array($row_main['main_route'], $route_name))) {
            $route_name[] = $row_main['main_route'];
        }

        // echo "<td>{$row_main['em_name']} {$row_main['em_surname']} ({$row_main['em_queue']})</td><br>";
        if(in_array($row_main['em_queue'], $queue)) {
            $main_re[] = $row_main;
        } else{
            $row_main['route_queue'] = mb_substr($row_main['em_queue'], 0, 1);
            $main[] = $row_main;
        }
    }


    


    $ex = [];
    while($row_ex = mysqli_fetch_assoc($result_ex)) {
        $ex[$row_ex['main_route']][] = [
            'em_id' => $row_ex['em_id'],
            'es_id' => $row_ex['es_id'],
            'main_route' => $row_ex['main_route'],
            'em_name' => $row_ex['em_name'],
            'em_surname' => $row_ex['em_surname'],
            'main_car' => $row_ex['main_car'],
            'em_queue' => $row_ex['em_queue'],
        ];

    }
    $ex_old = $ex;

    // print_r($ex);

    $coach = [];
    while($row_coach = mysqli_fetch_assoc($result_coach)) {
        $coach[$row_coach['main_route']][] = $row_coach;
    }

        
    $main_break = [];




    $new_plan = [];
    $new_break = [];

    $i = 0;
    foreach( $queue_num as $key => $value){
        $j = 1;
        $x = 1;
        $r_key = $key;
        $re_count = isset($re[$r_key]) ? count($re[$r_key]) : 0;
        while ($j <= $value || $j <= $re_count) {
            $re_value = ($j-1 < $re_count) ? $re[$r_key][$j-1] : '0';
            if($j <= $value) {
                if($re_value == '0' ) {
                    // ใช้ r_key (route จริง) ใน filter
                    $filtered = array_filter($main, function($item) use ($r_key) {
                        return $item['route_queue'] == $r_key && $item['es_id'] == '1';
                    });

                    $first = reset($filtered);
                    $firstKey = key($filtered);

                    if ($firstKey !== null && $first !== false) {
                        $new_plan[$r_key][$j] = [
                            'em_id' => $first['em_id'],
                            'es_id' => $first['es_id'],
                            'route' => $first['main_route'],
                            'em_name' => $first['em_name'],
                            'em_surname' => $first['em_surname'],
                            'car' => $first['main_car'],
                            'em_queue' => $first['em_queue'],
                            'new_queue' => $r_key . '-3-' . ($j),
                        ];
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
                        $new_plan[$r_key][$j] = [
                            'em_id' => $emp['em_id'],
                            'es_id' => $emp['es_id'],
                            'route' => $emp['main_route'],
                            'em_name' => $emp['em_name'],
                            'em_surname' => $emp['em_surname'],
                            'car' => $emp['main_car'],
                            'em_queue' => $emp['em_queue'],
                            'new_queue' => $r_key . '-3-' . ($j),
                        ];
                    } else {
                        $filtered = array_filter($main, function($item) use ($r_key) {
                        return $item['main_route'] == $r_key && $item['es_id'] == '1';
                        });

                        $first = reset($filtered);
                        $firstKey = key($filtered);

                        if ($firstKey !== null && $first !== false) {
                            $new_plan[$r_key][$j] = [
                                'em_id' => $first['em_id'],
                                'es_id' => $first['es_id'],
                                'route' => $first['main_route'],
                                'em_name' => $first['em_name'],
                                'em_surname' => $first['em_surname'],
                                'car' => $first['main_car'],
                                'em_queue' => $first['em_queue'],
                                'new_queue' => $r_key . '-3-' . ($j),
                            ];
                            unset($main[$firstKey]);
                            $main = array_values($main);
                        } else {
                            $new_plan[$r_key][$j] = $new_plan[$r_key][$x];
                            $x++;
                        }
                    }
                }
            } else {
                $idx = array_search($re_value, array_column($main_re, 'em_queue'));
                $emp = $main_re[$idx];
                if (!empty($main_break[$r_key])) {
                    $num = count($main_break[$r_key]) + 1;
                } else {
                    $num = 1;
                }
                $main_break[$r_key][] = [
                    'em_id' => $emp['em_id'],
                    'es_id' => $emp['es_id'],
                    'main_route' => $emp['main_route'],
                    'em_name' => $emp['em_name'],
                    'em_surname' => $emp['em_surname'],
                    'main_car' => $emp['main_car'],
                    'em_queue' => $emp['em_queue'],
                    'new_queue' => $r_key . '-1-' . ($num),
                ];
            }
            $j++;
        }
        $i++;
    }

    foreach ($main as $item) {
        if (!empty($main_break[$item['main_route']])) {
            $num = count($main_break[$item['main_route']]) + 1;
        } else {
            $num = 1;
        }
        $main_break[$item['main_route']][] = [
            'em_id' => $item['em_id'],
            'es_id' => $item['es_id'],
            'main_route' => $item['main_route'],
            'em_name' => $item['em_name'],
            'em_surname' => $item['em_surname'],
            'main_car' => $item['main_car'],
            'em_queue' => $item['em_queue'],
            'new_queue' => $item['main_route'] . '-1-' . ($num),

        ];

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

    $new_ex = [];
    $new_coach = [];

    foreach( $queue_num as $key => $value){
        $i = 1;
        $a = 1;
        while($i <= $value || $x) {
            if(!isset($ex[$key][$i-1])) {
                $new_ex[$key][] = $new_ex[$key][$a-1];
                $a++;
            }elseif($ex[$key][$i-1]['es_id'] == 1){
                $new_ex[$key][] = [
                    'em_id' => $ex[$key][$i-1]['em_id'],
                    'es_id' => $ex[$key][$i-1]['es_id'],
                    'route' => $ex[$key][$i-1]['main_route'],
                    'em_name' => $ex[$key][$i-1]['em_name'],
                    'em_surname' => $ex[$key][$i-1]['em_surname'],
                    'em_queue' => $ex[$key][$i-1]['em_queue'],
                    'new_queue' => $key.'-2-'.($i),
                ];
                unset($ex[$key][$i-1]);

            }

            if (!empty($new_ex[$key]) && count($new_ex[$key]) >= $value) {
            $x = false;
        }
            
            $i++;
        }





        $i = 1;
        $a = 1;
        $x = true;
        while($i <= $value || $x) {
            if(!isset($coach[$key][$i-1])) {
                $new_coach[$key][] = $new_coach[$key][$a-1];
                $a++;
            }elseif($coach[$key][$i-1]['es_id'] == 1){
                $new_coach[$key][] = [
                    'es_id' => $coach[$key][$i-1]['es_id'],
                    'route' => $coach[$key][$i-1]['main_route'],
                    'em_id' => $coach[$key][$i-1]['em_id'],
                    'em_name' => $coach[$key][$i-1]['em_name'],
                    'em_surname' => $coach[$key][$i-1]['em_surname'],
                    'em_queue' => $coach[$key][$i-1]['em_queue'],
                    'new_queue' => $key.'-2-'.($i),
                ];
                unset($coach[$key][$i-1]);

            }
            if(count($new_coach[$key]) >= $value) {
                $x = false;
            }
            
            $i++;
        }

    }
    
$exnotredy = [];

foreach ($ex as $routeArr) {
    foreach ($routeArr as $item) {
        // นับจำนวนที่มีอยู่แล้ว เพื่อกำหนด queue ใหม่
        if (!empty($exnotredy[$item['main_route']])) {
            $num = count($exnotredy[$item['main_route']]) + 1;
        } else {
            $num = 1;
        }
        // เพิ่มข้อมูลเข้า array โดยใช้ array_push จะเพิ่ม index ให้เอง
        $exnotredy[$item['main_route']][] = [
            'em_id' => $item['em_id'],
            'es_id' => $item['es_id'],
            'main_route' => $item['main_route'],
            'em_name' => $item['em_name'],
            'em_surname' => $item['em_surname'],
            'main_car' => $item['main_car'],
            'em_queue' => $item['em_queue'],
            'new_queue' => $item['main_route'] . '-1-' . $num,
        ];
    }
}
    //  $exnotredy = $ex;
    $coachnotredy= [];

    
foreach ($coach as $routeArr) {
    foreach ($routeArr as $item) {
        // นับจำนวนที่มีอยู่แล้ว เพื่อกำหนด queue ใหม่
        if (!empty($coachnotredy[$item['main_route']])) {
            $num = count($coachnotredy[$item['main_route']]) + 1;
        } else {
            $num = 1;
        }
        // เพิ่มข้อมูลเข้า array โดยใช้ array_push จะเพิ่ม index ให้เอง
        $coachnotredy[$item['main_route']][] = [
            'em_id' => $item['em_id'],
            'es_id' => $item['es_id'],
            'main_route' => $item['main_route'],
            'em_name' => $item['em_name'],
            'em_surname' => $item['em_surname'],
            'main_car' => $item['main_car'],
            'em_queue' => $item['em_queue'],
            'new_queue' => $item['main_route'] . '-1-' . $num,
        ];
    }
}


    mysqli_close($conn);

    $plan = [];
    foreach($queue_num as $key => $v) {
        // echo "<h3 class='mt-4 text-primary'>Plan for Route {$key}</h3>";
        $num = 1;
        while ($num <= $v) {
            $plan[$key][] = [
                'em_id' => $new_plan[$key][$num]['em_id'],
                'car' => $new_plan[$key][$num]['car'],
                'em_queue' => $new_plan[$key][$num]['em_queue'],
                'new_queue' => $new_plan[$key][$num]['new_queue'],
                'ex_id' => $new_ex[$key][$num-1]['em_id'],
                'ex_queue' => $new_ex[$key][$num-1]['em_queue'],
                'ex_new_queue' => $new_ex[$key][$num-1]['new_queue'],
                'coach_id' => $new_coach[$key][$num-1]['em_id'],
                'coach_new_queue' => $new_coach[$key][$num-1]['new_queue'],
            ];

            $num++;
        }
    }

    // แสดงตัวอย่างข้อมูล plan ในรูปแบบตารางก่อนส่ง
    echo '<div class="card mb-4">';
    echo '<div class="card-header bg-info text-white">ตัวอย่างข้อมูลแผน (Plan) ที่จะส่ง</div>';
    echo '<div class="card-body">';
    foreach($plan as $route => $rows) {
        echo "<b>Route {$route}</b>";
        echo '<div class="table-responsive"><table class="table table-bordered table-sm">';
        echo '<thead><tr>
                <th>#</th>
                <th>em_id</th>
                <th>car</th>
                <th>em_queue</th>
                <th>new_queue</th>
                <th>ex_id</th>
                <th>ex_queue</th>
                <th>ex_new_queue</th>
                <th>coach_id</th>
                <th>coach_new_queue</th>
            </tr></thead><tbody>';
        foreach($rows as $idx => $row) {
            echo '<tr>';
            echo '<td>' . ($idx+1) . '</td>';
            echo '<td>' . htmlspecialchars($row['em_id']) . '</td>';
            echo '<td>' . htmlspecialchars($row['car']) . '</td>';
            echo '<td>' . htmlspecialchars($row['em_queue']) . '</td>';
            echo '<td>' . htmlspecialchars($row['new_queue']) . '</td>';
            echo '<td>' . htmlspecialchars($row['ex_id']) . '</td>';
            echo '<td>' . htmlspecialchars($row['ex_queue']) . '</td>';
            echo '<td>' . htmlspecialchars($row['ex_new_queue']) . '</td>';
            echo '<td>' . htmlspecialchars($row['coach_id']) . '</td>';
            echo '<td>' . htmlspecialchars($row['coach_new_queue']) . '</td>';
            echo '</tr>';
        }
        echo '</tbody></table></div>';
    }
    echo '</div></div>';

    // สร้างฟอร์มสำหรับส่งข้อมูล plan, main_break, exnotredy, coachnotredy ไปยัง manage_out_db.php
    echo '<form method="post" action="manage_out_db.php" id="plan-form">';
    echo '<input type="hidden" name="plan_data" id="plan_data">';
    echo '<input type="hidden" name="main_break_data" id="main_break_data">';
    echo '<input type="hidden" name="exnotredy_data" id="exnotredy_data">';
    echo '<input type="hidden" name="coachnotredy_data" id="coachnotredy_data">';
    echo '<button type="submit" class="btn btn-success mb-4">บันทึกแผน</button>';
    echo '</form>';

    // แสดงแผน (debug)
    // print_r($plan);

    echo "<h3 class='mt-4 text-secondary'>รายชื่อ Main ที่เหลือ (ยังไม่ได้จัดคิว)</h3>";
if (!empty($main_break)) {
    echo "<div class='table-responsive'><table class='table table-bordered table-striped align-middle'>";
    echo "<thead class='table-dark'><tr><th>#</th><th>em_id</th><th>ชื่อ</th><th>นามสกุล</th><th>main_route</th><th>main_car</th><th>em_queue</th><th>new_queue</th></tr></thead><tbody>";
    $idx = 1;
    foreach($main_break as $v) {
        foreach($v as $idx => $row) {
            if (!isset($row['em_id'])) continue; // ข้ามถ้าไม่มี em_id
            echo "<tr>";
            echo "<td>{$idx}</td>";
            echo "<td>{$row['em_id']}</td>";
            echo "<td>{$row['em_name']}</td>";
            echo "<td>{$row['em_surname']}</td>";
            echo "<td>{$row['main_route']}</td>";
            echo "<td>{$row['main_car']}</td>";
            echo "<td>{$row['em_queue']}</td>";
            echo "<td>{$row['new_queue']}</td>";
            echo "</tr>";
        }
            $idx++;
    }
    echo "</tbody></table></div>";
} else {
    echo "<i>ไม่มีข้อมูล</i><br>";
}


    // แสดงผล exnotredy แยกตาม route
echo "<h3 class='mt-4 text-warning'>รายชื่อ Ex สำรอง/ไม่พร้อม</h3>";
foreach($exnotredy as $route => $list) {
    echo "<b>Route {$route}</b>";
    if(count($list) > 0) {
        echo "<div class='table-responsive'><table class='table table-bordered table-striped align-middle'>";
        echo "<thead class='table-dark'><tr><th>#</th><th>em_id</th><th>ชื่อ</th><th>นามสกุล</th><th>em_queue</th><th>new_queue</th></tr></thead><tbody>";
        $idx = 1;
        foreach($list as $row) {
            echo "<tr>";
            echo "<td>{$idx}</td>";
            echo "<td>{$row['em_id']}</td>";
            echo "<td>{$row['em_name']}</td>";
            echo "<td>{$row['em_surname']}</td>";
            echo "<td>{$row['em_queue']}</td>";
            echo "<td>{$row['new_queue']}</td>";
            echo "</tr>";
            $idx++;
        }
        echo "</tbody></table></div>";
    } else {
        echo "<i>ไม่มีข้อมูล</i><br>";
    }
}

// แสดงผล coachnotredy แยกตาม route
echo "<h3 class='mt-4 text-info'>รายชื่อ Coach สำรอง/ไม่พร้อม</h3>";
foreach($coachnotredy as $route => $list) {
    echo "<b>Route {$route}</b>";
    if(count($list) > 0) {
        echo "<div class='table-responsive'><table class='table table-bordered table-striped align-middle'>";
        echo "<thead class='table-dark'><tr><th>#</th><th>em_id</th><th>ชื่อ</th><th>นามสกุล</th><th>em_queue</th><th>new_queue</th></tr></thead><tbody>";
        $idx = 1;
        foreach($list as $row) {
            echo "<tr>";
            echo "<td>{$idx}</td>";
            echo "<td>{$row['em_id']}</td>";
            echo "<td>{$row['em_name']}</td>";
            echo "<td>{$row['em_surname']}</td>";
            echo "<td>{$row['em_queue']}</td>";
            echo "<td>{$row['new_queue']}</td>";
            echo "</tr>";
            $idx++;
        }
        echo "</tbody></table></div>";
    } else {
        echo "<i>ไม่มีข้อมูล</i><br>";
    }
}

?>
<script>
    // เมื่อกด submit ฟอร์ม จะใส่ข้อมูล plan, main_break, exnotredy, coachnotredy เป็น JSON ลงใน input hidden
    document.getElementById('plan-form').addEventListener('submit', function(e) {
        document.getElementById('plan_data').value = JSON.stringify(<?php echo json_encode($plan); ?>);
        document.getElementById('main_break_data').value = JSON.stringify(<?php echo json_encode($main_break); ?>);
        document.getElementById('exnotredy_data').value = JSON.stringify(<?php echo json_encode($exnotredy); ?>);
        document.getElementById('coachnotredy_data').value = JSON.stringify(<?php echo json_encode($coachnotredy); ?>);
    });
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    let data = <?php echo json_encode($ex); ?>;
    console.log('is data1 ', data);
    let data2 = <?php echo json_encode($plan); ?>;
    console.log('is data2 ', data2);
</script>
</body>
</html>