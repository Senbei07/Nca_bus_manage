<?php
    include 'config.php';

    if (!$conn) {
        die("Connection failed: " . mysqli_connect_error());
    }

    // ฟังก์ชันสำหรับจัดกลุ่มข้อมูล
    function groupEmployees($conn, $et_id, $queue_num, $queue_prefix) {
        $sql = "SELECT * FROM `employee` WHERE main_route = 1 AND et_id = $et_id ORDER BY em_queue";
        $result = mysqli_query($conn, $sql);

        $ready = [];
        $not_ready = [];
        $reserve = false;
        $num = 1;
        $a = 1;

        while($row = mysqli_fetch_assoc($result)) {
            if($row['es_id'] != 1 || $reserve) {
                $not_ready[] = [
                    'em_id' => $row['em_id'],
                    'em_name' => $row['em_name'],
                    'em_surname' => $row['em_surname'],
                    'car' => $row['main_car'],
                    'route' => $row['main_route'],
                    'em_queue' => '1-'.$a
                ];
                $a++;
            } else {
                $ready[] = [
                    'em_id' => $row['em_id'],
                    'em_name' => $row['em_name'],
                    'em_surname' => $row['em_surname'],
                    'car' => $row['main_car'],
                    'route' => $row['main_route'],
                    'em_queue' => $queue_prefix.'-'.$num
                ];
                $num++;
                if($num > $queue_num) {
                    $reserve = true;
                }
            }
        }

        // วนกลับหัวถ้าจำนวนไม่ครบ
        $x = 0;
        while($queue_num > count($ready)){
            $ready[] = [
                'em_id' => $ready[$x]['em_id'],
                'em_name' => $ready[$x]['em_name'],
                'em_surname' => $ready[$x]['em_surname'],
                'car' => $ready[$x]['car'],
                'route' => $ready[$x]['route'],
                'em_queue' => $queue_prefix.'-'.$num
            ];
            $num++;
            $x++;
        }

        return [$ready, $not_ready];
    }

    $queue_num = 5;
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>จัดการแผนเดินรถ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; }
        .container { max-width: 1100px; }
        h1 { margin-top: 2rem; }
        .btn-success { font-size: 1.2rem; padding: 0.5rem 2rem; }
        .section-title { margin-top: 2rem; margin-bottom: 1rem; }
        .rest-section { background: #fff3cd; border-radius: 8px; padding: 1rem; margin-bottom: 1rem; }
        .table th, .table td { vertical-align: middle; }
    </style>
</head>
<body>
<div class="container py-4">
    <h1 class="mb-4 text-center">จัดการแผนเดินรถ</h1>
    <?php
    // Main
    list($main, $main_not_ready) = groupEmployees($conn, 1, $queue_num, '3');
    // Ex
    list($ex, $ex_not_ready) = groupEmployees($conn, 2, $queue_num, '2');
    // Coach
    list($coach, $coach_not_ready) = groupEmployees($conn, 3, $queue_num, '2');

    // สร้างข้อมูล plan สำหรับส่งไป manage_db.php
    $plan = [];
    $x = 0;
    while($x <= $queue_num-1 ){
        $plan[] = [
            'em_id' => $main[$x]['em_id'],
            'main_queue' => $main[$x]['em_queue'],
            'car' => $main[$x]['car'],
            'route' => $main[$x]['route'],
            'ex_id' => $ex[$x]['em_id'],
            'ex_queue' => $ex[$x]['em_queue'],
            'coach_id' => $coach[$x]['em_id'],
            'coach_queue' => $coach[$x]['em_queue']
        ];
        $x++;
    }
    ?>

    <div class="table-responsive">
        <table class="table table-bordered table-striped align-middle">
            <thead class="table-dark">
                <tr>
                    <th>#</th>
                    <th>พนักงานหลัก<br><span class="small">(Main)</span></th>
                    <th>คิวหลัก</th>
                    <th>รถ</th>
                    <th>เส้นทาง</th>
                    <th>Ex</th>
                    <th>คิว Ex</th>
                    <th>Coach</th>
                    <th>คิว Coach</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if(count($plan) > 0) {
                    foreach($plan as $i => $row) {
                        echo "<tr>";
                        echo "<td>".($i+1)."</td>";
                        echo "<td>{$main[$i]['em_name']} {$main[$i]['em_surname']}</td>";
                        echo "<td><span class='badge bg-primary'>{$row['main_queue']}</span></td>";
                        echo "<td>{$row['car']}</td>";
                        echo "<td>{$row['route']}</td>";
                        echo "<td>{$ex[$i]['em_name']} {$ex[$i]['em_surname']}</td>";
                        echo "<td><span class='badge bg-success'>{$row['ex_queue']}</span></td>";
                        echo "<td>{$coach[$i]['em_name']} {$coach[$i]['em_surname']}</td>";
                        echo "<td><span class='badge bg-warning text-dark'>{$row['coach_queue']}</span></td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='9' class='text-center'>ไม่มีข้อมูลแผนเดินรถ</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

    <form action="manage_db.php" method="post" class="mt-5 text-center">
        <input type="hidden" name="plan" value='<?php echo json_encode($plan, JSON_UNESCAPED_UNICODE); ?>'>
        <input type="hidden" name="main_not_ready" value='<?php echo json_encode($main_not_ready, JSON_UNESCAPED_UNICODE); ?>'>
        <input type="hidden" name="ex_not_ready" value='<?php echo json_encode($ex_not_ready, JSON_UNESCAPED_UNICODE); ?>'>
        <input type="hidden" name="coach_not_ready" value='<?php echo json_encode($coach_not_ready, JSON_UNESCAPED_UNICODE); ?>'>
        <button type="submit" class="btn btn-success mt-3">ส่งข้อมูลทั้งหมด</button>
    </form>

    <div class="section-title h4">พขร สำรอง/ไม่พร้อม</div>
    <div class="rest-section row">
        <?php
        if(count($main_not_ready) > 0) {
            foreach($main_not_ready as $row) {
                echo "<div class='col-md-3 mb-2'><span class='fw-bold'>{$row['em_name']} {$row['em_surname']}</span> <span class='badge bg-primary'>{$row['em_queue']}</span></div>";
            }
        } else {
            echo "<div class='col-12'>ไม่มีข้อมูล</div>";
        }
        ?>
    </div>

    <div class="section-title h4">Ex สำรอง/ไม่พร้อม</div>
    <div class="rest-section row">
        <?php
        if(count($ex_not_ready) > 0) {
            foreach($ex_not_ready as $row) {
                echo "<div class='col-md-3 mb-2'><span class='fw-bold'>{$row['em_name']} {$row['em_surname']}</span> <span class='badge bg-success'>{$row['em_queue']}</span></div>";
            }
        } else {
            echo "<div class='col-12'>ไม่มีข้อมูล</div>";
        }
        ?>
    </div>

    <div class="section-title h4">Coach สำรอง/ไม่พร้อม</div>
    <div class="rest-section row">
        <?php
        if(count($coach_not_ready) > 0) {
            foreach($coach_not_ready as $row) {
                echo "<div class='col-md-3 mb-2'><span class='fw-bold'>{$row['em_name']} {$row['em_surname']}</span> <span class='badge bg-warning text-dark'>{$row['em_queue']}</span></div>";
            }
        } else {
            echo "<div class='col-12'>ไม่มีข้อมูล</div>";
        }
        ?>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>