<?php
    include 'config.php';
    include 'function/groupEmployee.php';

    if (!$conn) {
        die("Connection failed: " . mysqli_connect_error());
    }

    // ดึง br_id ทั้งหมดเพื่อใช้เป็น pool พนักงาน
    $all_routes_pool = [];
    $sql_all_routes = "SELECT DISTINCT br_id FROM `queue_request` ORDER BY br_id";
    $result_all_routes = mysqli_query($conn, $sql_all_routes);
    while ($row = mysqli_fetch_assoc($result_all_routes)) {
        $all_routes_pool[] = $row['br_id'];
    }
    $route = $all_routes_pool; // ใช้ routes ทั้งหมดสำหรับ employee pool

    $normal_code = [3, 2, 1];

    $date = $_GET['date'] ?? null;

    // Initialize variables to prevent errors when no date is selected
    $plan = [];
    $main_break = [];
    $exnotredy = [];
    $coachnotredy = [];
    $no_plan_message = null;
    $pr_ids = [];

    // Only process if a date is selected
    if ($date) {
        // ดึงข้อมูลพนักงานหลักและแผนการเดินรถจากฐานข้อมูล
        list($goto, $re, $main, $main_re, $break, $return_request, $time, $pr_ids) = getMainDriver($conn, $route, $date);

        if (empty($re)) {
            $no_plan_message = "ไม่พบแผนสำหรับวันที่เลือก กรุณาอัพเดทแผนหรือเลือกวันจัดรถใหม่";
        } else {
?>
<script>
    console.log('is goto ', <?php echo json_encode($time); ?>);
</script>
<?php 

            // นำข้อมูลพนักงานหลักและแผนการเดินรถมาจัดคิวการเดินรถ
            // โดยจัดกลุ่มตามเส้นทางและแผนการเดินรถที่กำหนด
            list($new_plan, $main, $x, $return ) = groupMainDriver($goto, $re, $main, $main_re, $return_request, $normal_code, $time);
            
            foreach ($re as $key => $value) {
                $queue_num[$key] = count($value);
            }
            
            // ดึงข้อมูลพนักงานพ่วงและโค้ช
            list($new_ex, $exnotredy, $re_dataex) = getEmployee($conn, $route, $goto, $queue_num, $x, 2, $return_request, $time);
            list($new_coach, $coachnotredy) = getEmployee($conn, $route, $goto, $queue_num, $x, 3, $return_request, $time);

            $main_break = [];
            $new_main = [];

            foreach ($main as $key => $value) {
                $route_key = $value['em_queue'][0];
                $new_main[$route_key][] = $value;
            }

            // จัดกลุ่มพนักงานหลักที่พักและสำรองตามเส้นทาง
            $main_break = groupByRouteWithNewQueue($goto, $new_main, $main_break);
            $main_break = groupByRouteWithNewQueue($goto, $break, $main_break);

            $plan = [];
            $main_end = [];
            $ex_end = [];
            $coach_end = [];

            foreach ($queue_num as $key => $v) {
                $num = 1;
                while ($num <= $v) {
                    
                    $plan[$key][] = [
                        'em_id' => $new_plan[$key][$num]['em_id'], // รหัสพนักงาน
                        'em_name' => $new_plan[$key][$num]['em_name'], // ชื่อพนักงาน
                        'em_surname' => $new_plan[$key][$num]['em_surname'], // นามสกุลพนักงาน
                        'car' => $new_plan[$key][$num]['car'], // รหัสรถ
                        'bt_id' => $new_plan[$key][$num]['bt_id'], // ประเภทของรถ
                        'licen' => $new_plan[$key][$num]['licen'], // หมายเลขทะเบียนรถ
                        'date_start' => $new_plan[$key][$num]['date'], // วันที่กำหนด
                        'time_start' => $new_plan[$key][$num]['time'], // เวลาที่กำหนด
                        'date_end' => $new_plan[$key][$num]['dateend'], // วันที่สิ้นสุด
                        'time_end' => $new_plan[$key][$num]['timeend'], // เวลาที่สิ้นสุด
                        'locat_id_start' => $new_plan[$key][$num]['locat_id_start'], // รหัสสถานที่เริ่มต้น
                        'locat_id_end' => $new_plan[$key][$num]['locat_id_end'], // รหัสสถานที่สิ้นสุด
                        'em_queue' => $new_plan[$key][$num]['em_queue'], // คิวของพนักงาน
                        'new_queue' => $new_plan[$key][$num]['new_queue'], // คิวใหม่ของพนักงาน
                        'ex_id' => $new_ex[$key][$num - 1]['em_id'], // รหัสพนักงานพ่วง
                        'ex_name' => $new_ex[$key][$num - 1]['em_name'], // ชื่อพนักงานพ่วง
                        'ex_surname' => $new_ex[$key][$num - 1]['em_surname'], // นามสกุลพนักงานพ่วง
                        'ex_queue' => $new_ex[$key][$num - 1]['em_queue'], // คิวของพนักงานพ่วง
                        'ex_new_queue' => $new_ex[$key][$num - 1]['new_queue'], // คิวใหม่ของพนักงานพ่วง
                        'coach_id' => $new_coach[$key][$num - 1]['em_id'], // รหัสโค้ช
                        'coach_name' => $new_coach[$key][$num - 1]['em_name'], // ชื่อโค้ช
                        'coach_surname' => $new_coach[$key][$num - 1]['em_surname'], // นามสกุลโค้ช
                        'coach_new_queue' => $new_coach[$key][$num - 1]['new_queue'], // คิวใหม่ของโค้ช
                    ];

                    $num++;
                }
            }

            // --- FOR UI TESTING WITH 100 ROUTES ---
            // This block generates dummy data to test the UI with a large number of routes.
            // It should be removed for production use.
            $test_plan = [];
            $test_goto = [];
            $test_main_break = [];
            $test_exnotredy = [];
            $test_coachnotredy = [];

            for ($i = 1; $i <= 100; $i++) {
                $br_id = 1000 + $i; // Example route IDs
                $go_route = 2000 + $i; // Example destination route ID

                // Dummy plan data
                $test_plan[$br_id] = [];
                $num_rows = rand(2, 5);
                for ($j = 1; $j <= $num_rows; $j++) {
                    $test_plan[$br_id][] = [
                        'em_name' => "พขร.ทดสอบ $br_id-$j",
                        'new_queue' => "$br_id-3-$j",
                        'licen' => "ท-123$i",
                        'ex_name' => "พ่วงทดสอบ $br_id-$j",
                        'ex_new_queue' => "$br_id-2-$j",
                        'coach_name' => "โค้ชทดสอบ $br_id-$j",
                        'coach_new_queue' => "$br_id-2-$j",
                        'time_start' => '08:00',
                        'time_end' => '17:00',
                        'date_start' => $date,
                        'date_end' => $date,
                    ];
                }

                // Dummy related data
                $test_goto[$br_id] = $go_route;
                $test_main_break[$go_route] = [['em_name' => "พักหลัก $go_route", 'new_queue' => "$go_route-1-1"]];
                $test_exnotredy[$br_id] = [['em_name' => "พักพ่วง $br_id", 'new_queue' => "$br_id-1-1"]];
                $test_coachnotredy[$br_id] = [['em_name' => "พักโค้ช $br_id", 'new_queue' => "$br_id-1-1"]];
            }
            
            // Overwrite real data with test data
            $plan = $test_plan;
            $goto = $test_goto;
            $main_break = $test_main_break;
            $exnotredy = $test_exnotredy;
            $coachnotredy = $test_coachnotredy;
            // --- END OF TEST BLOCK ---
        }
    } // End of if($date)
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
            <div class="card-header">เลือกวันที่</div>
            <div class="card-body">
                <form action="" method="get" id="date-filter-form">
                    <div class="row align-items-end">
                        <div class="col-md-4">
                            <label for="date-select" class="form-label"><strong>วันที่:</strong></label>
                            <input type="date" id="date-select" name="date" class="form-control" value="<?php echo htmlspecialchars($date); ?>">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">ดูแผน</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <?php if ($date): ?>
            <?php if ($no_plan_message): ?>
                <div class="alert alert-warning" role="alert">
                    <?php echo htmlspecialchars($no_plan_message); ?>
                </div>
            <?php else: ?>
                <!-- ฟอร์มสำหรับส่งข้อมูล -->
                <form method="post" action="manage_db.php" id="plan-form">
                    <input type="hidden" name="plan_data" id="plan_data">
                    <input type="hidden" name="pr_ids_data" id="pr_ids_data">
                    <input type="hidden" name="main_break_data" id="main_break_data">
                    <input type="hidden" name="exnotredy_data" id="exnotredy_data">
                    <input type="hidden" name="coachnotredy_data" id="coachnotredy_data">
                    <button type="submit" class="btn btn-success mb-4 w-100 btn-lg">บันทึกแผนทั้งหมด</button>
                </form>

                <div class="row">
                    <!-- Sidebar for Route Selection -->
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-header fw-bold">
                                เส้นทาง
                            </div>
                            <div class="p-2">
                                <input type="text" id="route-search" class="form-control" placeholder="ค้นหาสาย...">
                            </div>
                            <div style="max-height: 65vh; overflow-y: auto;">
                                <div class="nav flex-column nav-pills p-2" id="v-pills-tab" role="tablist" aria-orientation="vertical">
                                    <?php $first = true; ?>
                                    <?php foreach ($plan as $br_id => $rows): ?>
                                        <button class="nav-link text-start <?php if($first) echo 'active'; ?>" id="v-pills-<?php echo $br_id; ?>-tab" data-bs-toggle="pill" data-bs-target="#v-pills-<?php echo $br_id; ?>" type="button" role="tab" aria-controls="v-pills-<?php echo $br_id; ?>" aria-selected="<?php echo $first ? 'true' : 'false'; ?>">
                                            เส้นทาง <?php echo htmlspecialchars($br_id); ?>
                                        </button>
                                        <?php $first = false; ?>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Content Area -->
                    <div class="col-md-9">
                        <div class="tab-content" id="v-pills-tabContent">
                            <?php $first = true; ?>
                            <?php foreach ($plan as $br_id => $rows): ?>
                                <div class="tab-pane fade <?php if($first) echo 'show active'; ?>" id="v-pills-<?php echo $br_id; ?>" role="tabpanel" aria-labelledby="v-pills-<?php echo $br_id; ?>-tab">
                                    <div class="card">
                                        <div class="card-body">
                                            <h4 class="text-info">แผนการเดินรถ</h4>
                                            <div class="table-responsive mb-4">
                                                <table class="table table-bordered table-sm table-hover">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th>#</th>
                                                            <th>เวลาออก</th>
                                                            <th>เวลาถึง</th>
                                                            <th>พขร.หลัก</th>
                                                            <th>รถ</th>
                                                            <th>พขร.พ่วง</th>
                                                            <th>โค้ช</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($rows as $idx => $row): ?>
                                                            <tr>
                                                                <td><?php echo $idx + 1; ?></td>
                                                                <td><?php echo $row['time_start'] ? date('H:i', strtotime($row['time_start'])) : '-'; ?></td>
                                                                <td>
                                                                    <?php
                                                                        if (!empty($row['time_end'])) {
                                                                            if ($row['date_end'] != $row['date_start']) {
                                                                                // Format to show date if it's on the next day
                                                                                echo date('d/m H:i', strtotime($row['date_end'] . ' ' . $row['time_end']));
                                                                            } else {
                                                                                echo date('H:i', strtotime($row['time_end']));
                                                                            }
                                                                        } else {
                                                                            echo '-';
                                                                        }
                                                                    ?>
                                                                </td>
                                                                <td><?php echo htmlspecialchars($row['em_name']); ?> <span class="badge bg-light text-dark"><?php echo htmlspecialchars($row['new_queue']); ?></span></td>
                                                                <td><?php echo htmlspecialchars($row['licen']); ?></td>
                                                                <td><?php echo htmlspecialchars($row['ex_name']); ?> <span class="badge bg-light text-dark"><?php echo htmlspecialchars($row['ex_new_queue']); ?></span></td>
                                                                <td><?php echo htmlspecialchars($row['coach_name']); ?> <span class="badge bg-light text-dark"><?php echo htmlspecialchars($row['coach_new_queue']); ?></span></td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
        
                                            <div class="row">
                                                <div class="col-md-4">
                                                    <h5 class="text-secondary">พขร. พัก</h5>
                                                    <?php 
                                                        $go_route = $goto[$br_id] ?? null;
                                                        $break_list = $main_break[$go_route] ?? [];
                                                    ?>
                                                    <?php if (!empty($break_list)): ?>
                                                        <ul class="list-group">
                                                            <?php foreach ($break_list as $item): ?>
                                                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                                                    <?php echo htmlspecialchars($item['em_name']); ?>
                                                                    <span class="badge bg-secondary rounded-pill"><?php echo htmlspecialchars($item['new_queue']); ?></span>
                                                                </li>
                                                            <?php endforeach; ?>
                                                        </ul>
                                                    <?php else: ?>
                                                        <p><i>ไม่มีข้อมูล</i></p>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="col-md-4">
                                                    <h5 class="text-warning">พขร.พ่วง พัก</h5>
                                                    <?php $ex_not_ready_list = $exnotredy[$br_id] ?? []; ?>
                                                    <?php if (!empty($ex_not_ready_list)): ?>
                                                        <ul class="list-group">
                                                            <?php foreach ($ex_not_ready_list as $item): ?>
                                                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                                                    <?php echo htmlspecialchars($item['em_name']); ?>
                                                                    <span class="badge bg-warning text-dark rounded-pill"><?php echo htmlspecialchars($item['new_queue']); ?></span>
                                                                </li>
                                                            <?php endforeach; ?>
                                                        </ul>
                                                    <?php else: ?>
                                                        <p><i>ไม่มีข้อมูล</i></p>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="col-md-4">
                                                    <h5 class="text-info">โค้ช พัก</h5>
                                                    <?php $coach_not_ready_list = $coachnotredy[$br_id] ?? []; ?>
                                                    <?php if (!empty($coach_not_ready_list)): ?>
                                                        <ul class="list-group">
                                                            <?php foreach ($coach_not_ready_list as $item): ?>
                                                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                                                    <?php echo htmlspecialchars($item['em_name']); ?>
                                                                    <span class="badge bg-info rounded-pill"><?php echo htmlspecialchars($item['new_queue']); ?></span>
                                                                </li>
                                                            <?php endforeach; ?>
                                                        </ul>
                                                    <?php else: ?>
                                                        <p><i>ไม่มีข้อมูล</i></p>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php $first = false; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="alert alert-info" role="alert">
                กรุณาเลือกวันที่เพื่อดูแผนการเดินรถ
            </div>
        <?php endif; ?>

        <script>
            // เมื่อกด submit ฟอร์ม จะใส่ข้อมูล plan, main_break, exnotredy, coachnotredy เป็น JSON ลงใน input hidden
            const planForm = document.getElementById('plan-form');
            if (planForm) {
                planForm.addEventListener('submit', function(e) {
                    document.getElementById('plan_data').value = JSON.stringify(<?php echo json_encode($plan); ?>);
                    document.getElementById('pr_ids_data').value = JSON.stringify(<?php echo json_encode($pr_ids); ?>);
                    document.getElementById('main_break_data').value = JSON.stringify(<?php echo json_encode($main_break); ?>);
                    document.getElementById('exnotredy_data').value = JSON.stringify(<?php echo json_encode($exnotredy); ?>);
                    document.getElementById('coachnotredy_data').value = JSON.stringify(<?php echo json_encode($coachnotredy); ?>);
                });
            }

            // Auto-submit form on date change
            document.getElementById('date-select').addEventListener('change', function() {
                document.getElementById('date-filter-form').submit();
            });

            // Set min date to tomorrow to prevent selecting today or past dates
            const dateInput = document.getElementById('date-select');
            if (dateInput) {
                const tomorrow = new Date();
                tomorrow.setDate(tomorrow.getDate() + 1);
                dateInput.min = tomorrow.toISOString().split('T')[0];
            }

            // Sidebar Search Filter
            const searchInput = document.getElementById('route-search');
            if (searchInput) {
                searchInput.addEventListener('keyup', function() {
                    const filter = searchInput.value.toLowerCase();
                    const navLinks = document.querySelectorAll('#v-pills-tab .nav-link');
                    
                    navLinks.forEach(link => {
                        const routeIdText = link.textContent || link.innerText;
                        if (routeIdText.toLowerCase().includes(filter)) {
                            link.style.display = '';
                        } else {
                            link.style.display = 'none';
                        }
                    });
                });
            }
        </script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        <script>
            let data = <?php echo json_encode($new_plan ?? []); ?>;
            console.log('is data1 ', data);
            let data2 = <?php echo json_encode($main ?? []); ?>;
            console.log('is data2 ', data2);
        </script>
</body>
</html>