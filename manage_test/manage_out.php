<?php
    include 'config.php';
    include 'function/groupEmployee.php';

    if (!$conn) {
        die("Connection failed: " . mysqli_connect_error());
    }

    // กำหนดเส้นทางที่ต้องการ
    $route = [2, 3, 4];

    $normal_code = [3, 2, 1];

    list($re, $main, $main_re, $break) = getMainDriver($conn, $route);
    list($new_plan, $main, $x) = groupMainDriver($re, $main, $main_re, $normal_code);

    foreach ($re as $key => $value) {
        $queue_num[$key] = count($value);
    }

    list($new_ex, $exnotredy) = getEmployee($conn, $route, $queue_num, $x, 2);
    list($new_coach, $coachnotredy) = getEmployee($conn, $route, $queue_num, $x, 3);

    $main_break = [];
    $main_break = groupByRouteWithNewQueue($main, $main_break);
    $main_break = groupByRouteWithNewQueue($break, $main_break);

    $plan = [];
    foreach ($queue_num as $key => $v) {
        $num = 1;
        while ($num <= $v) {
            $plan[$key][] = [
                'em_id' => $new_plan[$key][$num]['em_id'],
                'em_name' => $new_plan[$key][$num]['em_name'],
                'em_surname' => $new_plan[$key][$num]['em_surname'],
                'car' => $new_plan[$key][$num]['car'],
                'licen' => $new_plan[$key][$num]['licen'],
                'em_queue' => $new_plan[$key][$num]['em_queue'],
                'new_queue' => $new_plan[$key][$num]['new_queue'],
                'ex_id' => $new_ex[$key][$num - 1]['em_id'],
                'ex_name' => $new_ex[$key][$num - 1]['em_name'],
                'ex_surname' => $new_ex[$key][$num - 1]['em_surname'],
                'ex_queue' => $new_ex[$key][$num - 1]['em_queue'],
                'ex_new_queue' => $new_ex[$key][$num - 1]['new_queue'],
                'coach_id' => $new_coach[$key][$num - 1]['em_id'],
                'coach_name' => $new_coach[$key][$num - 1]['em_name'],
                'coach_surname' => $new_coach[$key][$num - 1]['em_surname'],
                'coach_new_queue' => $new_coach[$key][$num - 1]['new_queue'],
            ];
            $num++;
        }
    }
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
        
        <div class="card mb-4">
            <div class="card-header bg-info text-white">ตัวอย่างข้อมูลแผน (Plan) ที่จะส่ง</div>
            <div class="card-body">
                <?php foreach ($plan as $route => $rows): ?>
                    <b>Route <?= htmlspecialchars($route) ?></b>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>em_id</th>
                                    <th>ชื่อ</th>
                                    <th>นามสกุล</th>
                                    <th>car</th>
                                    <th>em_queue</th>
                                    <th>new_queue</th>
                                    <th>ex_id</th>
                                    <th>ชื่อพ่วง</th>
                                    <th>นามสกุลพ่วง</th>
                                    <th>ex_queue</th>
                                    <th>ex_new_queue</th>
                                    <th>coach_id</th>
                                    <th>ชื่อโค้ช</th>
                                    <th>นามสกุลโค้ช</th>
                                    <th>coach_new_queue</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($rows as $idx => $row): ?>
                                    <tr>
                                        <td><?= $idx + 1 ?></td>
                                        <td><?= htmlspecialchars($row['em_id']) ?></td>
                                        <td><?= htmlspecialchars($row['em_name']) ?></td>
                                        <td><?= htmlspecialchars($row['em_surname']) ?></td>
                                        <td><?= htmlspecialchars($row['car']) ?></td>
                                        <td><?= htmlspecialchars($row['em_queue']) ?></td>
                                        <td><?= htmlspecialchars($row['new_queue']) ?></td>
                                        <td><?= htmlspecialchars($row['ex_id']) ?></td>
                                        <td><?= htmlspecialchars($row['ex_name']) ?></td>
                                        <td><?= htmlspecialchars($row['ex_surname']) ?></td>
                                        <td><?= htmlspecialchars($row['ex_queue']) ?></td>
                                        <td><?= htmlspecialchars($row['ex_new_queue']) ?></td>
                                        <td><?= htmlspecialchars($row['coach_id']) ?></td>
                                        <td><?= htmlspecialchars($row['coach_name']) ?></td>
                                        <td><?= htmlspecialchars($row['coach_surname']) ?></td>
                                        <td><?= htmlspecialchars($row['coach_new_queue']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- ฟอร์มสำหรับส่งข้อมูล -->
        <form method="post" action="manage_out_db.php" id="plan-form">
            <input type="hidden" name="plan_data" id="plan_data">
            <input type="hidden" name="main_break_data" id="main_break_data">
            <input type="hidden" name="exnotredy_data" id="exnotredy_data">
            <input type="hidden" name="coachnotredy_data" id="coachnotredy_data">
            <button type="submit" class="btn btn-success mb-4">บันทึกแผน</button>
        </form>

        <!-- รายชื่อ พขร พัก -->
        <h3 class='mt-4 text-secondary'>รายชื่อ พขร พัก</h3>
        <?php if (!empty($main_break)): ?>
            <div class='table-responsive'>
                <table class='table table-bordered table-striped align-middle'>
                    <thead class='table-dark'>
                        <tr>
                            <th>#</th>
                            <th>em_id</th>
                            <th>ชื่อ</th>
                            <th>นามสกุล</th>
                            <th>main_route</th>
                            <th>main_car</th>
                            <th>em_queue</th>
                            <th>new_queue</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($main_break as $v): ?>
                            <?php foreach ($v as $idx => $row): ?>
                                <?php if (!isset($row['em_id'])) continue; ?>
                                <tr>
                                    <td><?= $idx ?></td>
                                    <td><?= htmlspecialchars($row['em_id']) ?></td>
                                    <td><?= htmlspecialchars($row['em_name']) ?></td>
                                    <td><?= htmlspecialchars($row['em_surname']) ?></td>
                                    <td><?= htmlspecialchars($row['main_route']) ?></td>
                                    <td><?= htmlspecialchars($row['main_car']) ?></td>
                                    <td><?= htmlspecialchars($row['em_queue']) ?></td>
                                    <td><?= htmlspecialchars($row['new_queue']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <i>ไม่มีข้อมูล</i><br>
        <?php endif; ?>

        <!-- รายชื่อ พขร พ่วง พัก/ไม่พร้อม -->
        <h3 class='mt-4 text-warning'>รายชื่อ พขร พ่วง พัก/ไม่พร้อม</h3>
        <?php foreach ($exnotredy as $route => $list): ?>
            <b>Route <?= htmlspecialchars($route) ?></b>
            <?php if (count($list) > 0): ?>
                <div class='table-responsive'>
                    <table class='table table-bordered table-striped align-middle'>
                        <thead class='table-dark'>
                            <tr>
                                <th>#</th>
                                <th>em_id</th>
                                <th>ชื่อ</th>
                                <th>นามสกุล</th>
                                <th>em_queue</th>
                                <th>new_queue</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $idx = 1; foreach ($list as $row): ?>
                                <tr>
                                    <td><?= $idx ?></td>
                                    <td><?= htmlspecialchars($row['em_id']) ?></td>
                                    <td><?= htmlspecialchars($row['em_name']) ?></td>
                                    <td><?= htmlspecialchars($row['em_surname']) ?></td>
                                    <td><?= htmlspecialchars($row['em_queue']) ?></td>
                                    <td><?= htmlspecialchars($row['new_queue']) ?></td>
                                </tr>
                            <?php $idx++; endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <i>ไม่มีข้อมูล</i><br>
            <?php endif; ?>
        <?php endforeach; ?>

        <!-- รายชื่อ โค้ช พัก/ไม่พร้อม -->
        <h3 class='mt-4 text-info'>รายชื่อ โค้ช พัก/ไม่พร้อม</h3>
        <?php foreach ($coachnotredy as $route => $list): ?>
            <b>Route <?= htmlspecialchars($route) ?></b>
            <?php if (count($list) > 0): ?>
                <div class='table-responsive'>
                    <table class='table table-bordered table-striped align-middle'>
                        <thead class='table-dark'>
                            <tr>
                                <th>#</th>
                                <th>em_id</th>
                                <th>ชื่อ</th>
                                <th>นามสกุล</th>
                                <th>em_queue</th>
                                <th>new_queue</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $idx = 1; foreach ($list as $row): ?>
                                <tr>
                                    <td><?= $idx ?></td>
                                    <td><?= htmlspecialchars($row['em_id']) ?></td>
                                    <td><?= htmlspecialchars($row['em_name']) ?></td>
                                    <td><?= htmlspecialchars($row['em_surname']) ?></td>
                                    <td><?= htmlspecialchars($row['em_queue']) ?></td>
                                    <td><?= htmlspecialchars($row['new_queue']) ?></td>
                                </tr>
                            <?php $idx++; endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <i>ไม่มีข้อมูล</i><br>
            <?php endif; ?>
        <?php endforeach; ?>

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
            let data = <?php echo json_encode($newwww); ?>;
            console.log('is data1 ', data);
            let data2 = <?php echo json_encode($plan); ?>;
            console.log('is data2 ', data2);
        </script>
</body>
</html>