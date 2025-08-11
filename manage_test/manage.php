<?php
    include 'config.php';
    include 'function/groupEmployee.php';

    // Database connection and route pool setup
    if (!$conn) {
        die("Connection failed: " . mysqli_connect_error());
    }

    $all_routes_pool = [];
    $sql_all_routes = "SELECT DISTINCT br_id FROM `queue_request` ORDER BY br_id";
    $result_all_routes = mysqli_query($conn, $sql_all_routes);
    while ($row = mysqli_fetch_assoc($result_all_routes)) {
        $all_routes_pool[] = $row['br_id'];
    }
    $route = $all_routes_pool;

    $sql_point = "SELECT 
                    brk_in_route.br_id AS br_id,
                    brk_in_route.bir_time AS bir_time,
                    brk_in_route.brkp_id AS brkp_id,
                    break_point.brkp_name AS brkp_name,
                    brk_in_route.bir_type AS brkp_type,
                    brk_in_route.bir_status AS brkp_status
                FROM `brk_in_route` 
                LEFT JOIN break_point ON brk_in_route.brkp_id = break_point.brkp_id";

    $result_point = mysqli_query($conn, $sql_point);
    $point = [];
    while ($row = mysqli_fetch_assoc($result_point)) {
        $point[$row['br_id']][] = [
            'id' => $row['brkp_id'],
            'name' => $row['brkp_name'],
            'time' => $row['bir_time'],
            'status' => $row['brkp_status'],
            'type' => $row['brkp_type']
        ];
    }

    // Debugging output
    echo "<script>console.log('point:', " . json_encode($point) . ");</script>";

    $normal_code = [3, 2, 1];

    $date = $_GET['date'] ?? null;
    // ไม่ต้องใช้ select route ด้านบน
    $selected_route = isset($_GET['route']) ? $_GET['route'] : (count($all_routes_pool) > 0 ? $all_routes_pool[0] : null);

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
        list($goto, $re, $main, $main_re, $break, $return_request, $time, $pr_ids, $ex_request, $coach_request) = getMainDriver($conn, $route, $date);

        if (empty($re)) {
            $no_plan_message = "ไม่พบแผนสำหรับวันที่เลือก กรุณาอัพเดทแผนหรือเลือกวันจัดรถใหม่";
        } else {


            // นำข้อมูลพนักงานหลักและแผนการเดินรถมาจัดคิวการเดินรถ
            // โดยจัดกลุ่มตามเส้นทางและแผนการเดินรถที่กำหนด
            list($new_plan, $main, $x, $return ) = groupMainDriver($goto, $re, $main, $main_re, $return_request, $normal_code, $time);
            
            foreach ($re as $key => $value) {
                $queue_num[$key] = count($value);
            }
            
            // ดึงข้อมูลพนักงานพ่วงและโค้ช
            list($new_ex, $exnotredy, $re_dataex) = getEmpData($conn, $route, $goto, $return_request, $time, $ex_request, 'extra');
            echo "<script>console.log('exnotredy:', " . json_encode($exnotredy) . ");</script>";
            
            $exnotgroup = [];

            // จัดกลุ่มพนักงานพ่วงตามจุดพัก
            // $exnotredy เป็น array ที่มี key เป็นชื่อจุดพัก และ value เป็น array ของพนักงานพ่วงที่ไม่พร้อม
            $exnotgroup = [];
            foreach($point as $key => $value) {
                foreach($exnotredy as $point_name => $ex_list) {
                    // ดึง id ทั้งหมดใน $value มาเทียบกับ $point_name
                    $ids = array_column($value, 'id');
                    if (in_array($point_name, $ids)) {
                        // เพิ่ม address ในแต่ละแถวของ $ex_list
                        foreach ($ex_list as &$item) {
                            $item['address'] = $key;
                        }
                        unset($item); // ป้องกัน reference bug ใน PHP
                        // รวม $ex_list เข้ากับ array เดิม (flatten)
                        if (!isset($exnotgroup[$key])) $exnotgroup[$key] = [];
                        $exnotgroup[$key] = array_merge($exnotgroup[$key], $ex_list);
                    }
                }
            }

            echo "<script>console.log('exnotgroup:', " . json_encode($exnotgroup) . ");</script>";

            
            list($new_coach, $coachnotredy) = getEmpData($conn, $route, $goto, $return_request, $time, $coach_request, 'coach');
            

            //ใช้สำหรับแบ่งแบบ ตามจุดพัก ต้นทาง-ปลายทาง
            // $coachnotgroup = [];
            // foreach($point as $key => $value) {
            //     foreach($coachnotredy as $point_name => $list) {
            //         // เช็คเฉพาะ index แรกของ $value
            //         if (isset($value[0]['id']) && $point_name == $value[0]['id']) {
            //             if (!isset($coachnotgroup[$key])) $coachnotgroup[$key] = [];
            //             // เพิ่ม address ในแต่ละแถวของ $list
            //             foreach ($list as &$item) {
            //                 $item['address'] = $key;
            //             }
            //             unset($item); // ป้องกัน bug จากการอ้างอิง reference
            //             $coachnotgroup[$key] = array_merge($coachnotgroup[$key], $list);
            //         }
            //     }
            // }
            
            $coachnotgroup = [];
            foreach($coachnotredy as $key => $v){
                foreach($v as $k => $vv){
                    $vv['address'] = $vv['main_route']; // เพิ่ม address ในแต่ละแถว
                    $coachnotgroup[$vv['main_route']][] = $vv; 
                }

            }


            echo "<script>console.log('coachnotredy:', " . json_encode($coachnotredy) . ");</script>";
            echo "<script>console.log('coachnotgroup:', " . json_encode($coachnotgroup) . ");</script>";

            $main_break = [];
            $new_main = [];

            foreach ($main as $key => $value) {
                $route_key = $value['em_queue'][0];
                $new_main[$route_key][] = $value;
            }

            // จัดกลุ่มพนักงานหลักที่พักและสำรองตามเส้นทาง
            $main_break = groupByRouteWithNewQueue($goto, $new_main, 1, $main_break);
            $main_break = groupByRouteWithNewQueue($goto, $break, 1, $main_break);


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
                        'em_address' => $key, // ที่อยู่ของพนักงาน
                        'ex_id' => $new_ex[$key]['ex1'][$num - 1]['em_id'], // รหัสพนักงานพ่วง
                        'ex_name' => $new_ex[$key]['ex1'][$num - 1]['em_name'], // ชื่อพนักงานพ่วง
                        'ex_surname' => $new_ex[$key]['ex1'][$num - 1]['em_surname'], // นามสกุลพนักงานพ่วง
                        'ex_queue' => $new_ex[$key]['ex1'][$num - 1]['em_queue'], // คิวของพนักงานพ่วง
                        'ex_new_queue' => $new_ex[$key]['ex1'][$num - 1]['em_new_queue'], // คิวใหม่ของพนักงานพ่วง
                        'ex_address' => $key, // ที่อยู่ของพนักงานพ่วง
                        'ex_id2' => $new_ex[$key]['ex2'][$num - 1]['em_id'], // รหัสพนักงานพ่วง
                        'ex_name2' => $new_ex[$key]['ex2'][$num - 1]['em_name'], // ชื่อพนักงานพ่วง
                        'ex_surname2' => $new_ex[$key]['ex2'][$num - 1]['em_surname'], // นามสกุลพนักงานพ่วง
                        'ex_queue2' => $new_ex[$key]['ex2'][$num - 1]['em_queue'], // คิวของพนักงานพ่วง
                        'ex_new_queue2' => $new_ex[$key]['ex2'][$num - 1]['em_new_queue'], // คิวใหม่ของพนักงานพ่วง
                        'ex_address2' => $key, // ที่อยู่ของพนักงานพ่วง
                        'coach_id' => $new_coach[$key]['coach'][$num - 1]['em_id'], // รหัสโค้ช
                        'coach_name' => $new_coach[$key]['coach'][$num - 1]['em_name'], // ชื่อโค้ช
                        'coach_surname' => $new_coach[$key]['coach'][$num - 1]['em_surname'], // นามสกุลโค้ช
                        'coach_queue' => $new_coach[$key]['coach'][$num - 1]['em_queue'], // คิวใหม่ของโค้ช
                        'coach_new_queue' => $new_coach[$key]['coach'][$num - 1]['em_new_queue'], // คิวใหม่ของโค้ช
                        'coach_address' => $key, // ที่อยู่ของโค้ช
                    ];

                    $num++;
                }
            }
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
    <style>
        .sortable-ghost {
            opacity: 0.4;
            background-color: rgba(0, 123, 255, 0.25);
        }
        .table-hover .sortable-tbody tr:hover {
            cursor: grab;
        }
    </style>
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
                    <a href="confirm_plan.php?date=<?php echo urlencode($date); ?>" class="btn btn-link">เพิ่มแผน</a>
                </div>
            <?php else: ?>
                <!-- Plan form and content -->
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
                            <div class="card-header fw-bold">เส้นทาง</div>
                            <div class="p-2">
                                <input type="text" id="route-search" class="form-control" placeholder="ค้นหาสาย...">
                            </div>
                            <div style="max-height: 65vh; overflow-y: auto;">
                                <div class="nav flex-column nav-pills p-2" id="v-pills-tab" role="tablist" aria-orientation="vertical">
                                    <?php foreach ($all_routes_pool as $route_id): ?>
                                        <button class="nav-link text-start <?php if($route_id == $selected_route) echo 'active'; ?>"
                                            id="v-pills-<?php echo $route_id; ?>-tab"
                                            data-bs-toggle="pill"
                                            data-bs-target="#v-pills-<?php echo $route_id; ?>"
                                            type="button"
                                            role="tab"
                                            aria-controls="v-pills-<?php echo $route_id; ?>"
                                            aria-selected="<?php echo $route_id == $selected_route ? 'true' : 'false'; ?>"
                                            data-route="<?php echo htmlspecialchars($route_id); ?>">
                                            เส้นทาง <?php echo htmlspecialchars($route_id); ?>
                                        </button>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Content Area -->
                    <div class="col-md-9">
                        <div class="tab-content" id="v-pills-tabContent">
                            <?php foreach ($all_routes_pool as $route_id): ?>
                                <?php if (!isset($plan[$route_id])) continue; ?>
                                <div class="tab-pane fade <?php if($route_id == $selected_route) echo 'show active'; ?>"
                                    id="v-pills-<?php echo $route_id; ?>"
                                    role="tabpanel"
                                    aria-labelledby="v-pills-<?php echo $route_id; ?>-tab">
                                    <?php $rows = $plan[$route_id]; $br_id = $route_id; ?>
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
                                                            <th>พขร.พ่วง</th>
                                                            <th>โค้ช</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="sortable-tbody" data-br-id="<?php echo htmlspecialchars($br_id); ?>">
                                                        <?php foreach ($rows as $idx => $row): ?>
                                                            <tr data-row-index="<?php echo $idx; ?>">
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
                                                                <td><?php echo htmlspecialchars($row['em_name']); ?> <span class="badge bg-light text-dark"><?php echo htmlspecialchars($row['em_queue']); ?> => <?php echo htmlspecialchars($row['new_queue']); ?></span></td>
                                                                <td><?php echo htmlspecialchars($row['licen']); ?></td>
                                                                <td><?php echo htmlspecialchars($row['ex_name']); ?> <span class="badge bg-light text-dark"><?php if($row['ex_queue'] != "0" && !empty($row['ex_queue'])){ echo htmlspecialchars($row['ex_queue']); ?> => <?php echo htmlspecialchars($row['ex_new_queue']); }?></span></td>
                                                                <td><?php echo htmlspecialchars($row['ex_name2']); ?> <span class="badge bg-light text-dark"><?php if($row['ex_queue2'] != "0" && !empty($row['ex_queue2'])){ echo htmlspecialchars($row['ex_queue2']); ?> => <?php echo htmlspecialchars($row['ex_new_queue2']); }?></span></td>
                                                                <td><?php echo htmlspecialchars($row['coach_name']); ?> <span class="badge bg-light text-dark"><?php echo htmlspecialchars($row['coach_queue']); ?> => <?php echo htmlspecialchars($row['coach_new_queue']); ?></span></td>
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
                                                        $break_list = $main_break[$br_id] ?? [];
                                                    ?>
                                                    <!-- เพิ่ม dropdown สำหรับเลือกสาย -->
                                                    <select class="form-select form-select-sm mb-2 main-break-route-select" data-type="main" data-current-br="<?php echo htmlspecialchars($br_id); ?>">
                                                        <?php foreach ($goto as $route_br_id => $route_go): ?>
                                                            <option value="<?php echo htmlspecialchars($route_br_id); ?>" <?php if($route_br_id == $br_id) echo 'selected'; ?>>
                                                                สาย <?php echo htmlspecialchars($route_br_id); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                    <div id="main-break-list-container-<?php echo htmlspecialchars($br_id); ?>">
                                                        <!-- ลบ PHP ที่ render รายการออก -->
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <h5 class="text-warning">พขร.พ่วง พัก</h5>
                                                    <!-- เปลี่ยน dropdown เป็นเลือกจุดพัก -->
                                                    <select class="form-select form-select-sm mb-2 ex-break-route-select" data-type="ex" data-current-br="<?php echo htmlspecialchars($br_id); ?>">
                                                        <?php foreach ($exnotgroup as $point_name => $ex_list): ?>
                                                            <option value="<?php echo htmlspecialchars($point_name); ?>">
                                                                สาย <?php echo htmlspecialchars($point_name); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                    <div id="ex-break-list-container-<?php echo htmlspecialchars($br_id); ?>">
                                                        <!-- ลบ PHP ที่ render รายการออก -->
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <h5 class="text-info">โค้ช พัก</h5>
                                                    <!-- เพิ่ม dropdown สำหรับเลือกสาย -->
                                                    <select class="form-select form-select-sm mb-2 coach-break-route-select" data-type="coach" data-current-br="<?php echo htmlspecialchars($br_id); ?>">
                                                        <?php foreach ($plan as $route_br_id => $_): ?>
                                                            <option value="<?php echo htmlspecialchars($route_br_id); ?>" <?php if($route_br_id == $br_id) echo 'selected'; ?>>
                                                                สาย <?php echo htmlspecialchars($route_br_id); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                    <div id="coach-break-list-container-<?php echo htmlspecialchars($br_id); ?>">
                                                        <!-- ลบ PHP ที่ render รายการออก -->
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
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

                <!-- Popup Modal for เลือกพขร.พ่วงที่ต้องการเปลี่ยน -->
                <!-- Popup Modal for แจ้งเตือน -->
                <div class="modal fade" id="alertModal" tabindex="-1" aria-labelledby="alertModalLabel" aria-hidden="true" style="display:none;">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="alertModalLabel">แจ้งเตือน</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="ปิด"></button>
                            </div>
                            <div class="modal-body text-center" id="alertModalBody">
                                <!-- message -->
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal fade" id="exSelectModal" tabindex="-1" aria-labelledby="exSelectModalLabel" aria-hidden="true" style="display:none;">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="exSelectModalLabel">เลือกพขร.พ่วงที่ต้องการเปลี่ยน</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="ปิด"></button>
                            </div>
                            <div class="modal-body text-center">
                                <button type="button" class="btn btn-warning m-2" id="chooseEx1">พขร.พ่วงคนที่ 1</button>
                                <button type="button" class="btn btn-warning m-2" id="chooseEx2">พขร.พ่วงคนที่ 2</button>
                            </div>
                        </div>
                    </div>
                </div>

                <script>

            

  // --- START: Drag and Drop Logic ---
            // --- State Management ---
            const LS_KEY = 'manage_plan_state';
            let jsPlan = <?php echo json_encode($plan ?? []); ?>;
            let jsMainBreak = <?php echo json_encode($main_break ?? []); ?>;
            let jsExBreak = <?php echo json_encode($exnotgroup ?? []); ?>;
            let jsCoachBreak = <?php echo json_encode($coachnotgroup ?? []); ?>;
            let jsGoto = <?php echo json_encode($goto ?? []); ?>;

            // โหลด state จาก localStorage ถ้ามี
            function loadStateFromLS() {
                try {
                    const state = JSON.parse(localStorage.getItem(LS_KEY));
                    if (state) {
                        if (state.jsPlan) jsPlan = state.jsPlan;
                        if (state.jsMainBreak) jsMainBreak = state.jsMainBreak;
                        if (state.jsExBreak) jsExBreak = state.jsExBreak;
                        if (state.jsCoachBreak) jsCoachBreak = state.jsCoachBreak;
                    }
                } catch (e) {}
            }
            loadStateFromLS();
            localStorage.removeItem('manage_plan_state');

            // เซฟ state ลง localStorage
            function saveStateToLS() {
                localStorage.setItem(LS_KEY, JSON.stringify({
                    jsPlan, jsMainBreak, jsExBreak, jsCoachBreak
                }));
            }



            document.addEventListener('DOMContentLoaded', function () {




                // Main table drag for reordering
                const sortableTables = document.querySelectorAll('.sortable-tbody');
                sortableTables.forEach(tableBody => {
                    new Sortable(tableBody, {
                        animation: 150,
                        ghostClass: 'sortable-ghost',
                        handle: 'tr',
                        onEnd: function (evt) {
                            const br_id = evt.from.dataset.brId;
                            const oldIndex = evt.oldIndex;
                            const newIndex = evt.newIndex;
                            if (oldIndex === newIndex) return;
                            const movedItem = jsPlan[br_id].splice(oldIndex, 1)[0];
                            jsPlan[br_id].splice(newIndex, 0, movedItem);
                            const lastIndex = jsPlan[br_id].length - 1;
                            jsPlan[br_id].forEach((row, idx) => {
                                row.new_queue = (idx === lastIndex) ? `${br_id}-3-last` : `${br_id}-3-${idx + 1}`;
                                row.ex_new_queue = `${br_id}-2-${idx + 1}`;
                                row.coach_new_queue = `${br_id}-2-${idx + 1}`;
                            });
                            const tbody = evt.from;
                            const tableRows = tbody.querySelectorAll('tr');
                            tableRows.forEach((domRow, index) => {
                                const planItem = jsPlan[br_id][index];
                                domRow.cells[0].innerText = index + 1;
                                domRow.cells[3].querySelector('.badge').innerText = planItem.new_queue;
                                domRow.cells[5].querySelector('.badge').innerText = planItem.ex_new_queue;
                                domRow.cells[6].querySelector('.badge').innerText = planItem.coach_new_queue;
                            });
                            saveStateToLS();
                            console.log('DND Main Table', { jsPlan, jsMainBreak, jsExBreak, jsCoachBreak }); // ใน onEnd ตารางหลัก
                        }
                    });
                });

                // --- Drag from พขร. พัก to พขร.หลัก (ข้ามสายได้) ---
                // ใช้ event delegation เพื่อรองรับ element ที่ถูกสร้างใหม่
                document.querySelectorAll('.main-break-list').forEach(breakList => {
                    breakList.addEventListener('dragstart', function(e) {
                        if (e.target.classList.contains('break-driver-item')) {
                            e.dataTransfer.setData('text/plain', JSON.stringify({
                                breakIndex: e.target.dataset.breakIndex,
                                goRoute: e.target.dataset.goRoute,
                                brId: e.target.dataset.brId,
                                address: e.target.dataset.address,
                                type: 'main'
                            }));
                            e.dataTransfer.effectAllowed = 'move';
                        }
                    });
                });
                // รองรับ main-break-list ที่ถูกสร้างใหม่ (เช่นหลังเปลี่ยนสาย)
                document.body.addEventListener('dragstart', function(e) {
                    if (e.target.classList && e.target.classList.contains('break-driver-item')) {
                        e.dataTransfer.setData('text/plain', JSON.stringify({
                            breakIndex: e.target.dataset.breakIndex,
                            goRoute: e.target.dataset.goRoute,
                            brId: e.target.dataset.brId,
                            address: e.target.dataset.address,
                            type: 'main'
                        }));
                        e.dataTransfer.effectAllowed = 'move';
                    }
                });

                // --- Drag from พขร.พ่วง พัก to พขร.พ่วง (ข้ามสายได้) ---
                document.querySelectorAll('.ex-break-list').forEach(breakList => {
                    breakList.addEventListener('dragstart', function(e) {
                        if (e.target.classList.contains('ex-driver-item')) {
                            e.dataTransfer.setData('text/plain', JSON.stringify({
                                breakIndex: e.target.dataset.breakIndex,
                                brId: e.target.dataset.brId,
                                goRoute: e.target.dataset.goRoute,
                                emId: e.target.dataset.emId,
                                queue: e.target.dataset.queue,
                                newQueue: e.target.dataset.newQueue,
                                address: e.target.dataset.address,
                                type: 'ex'
                            }));
                            e.dataTransfer.effectAllowed = 'move';
                        }
                    });
                });
                // รองรับ ex-break-list ที่ถูกสร้างใหม่
                document.body.addEventListener('dragstart', function(e) {
                    if (e.target.classList && e.target.classList.contains('ex-driver-item')) {
                        e.dataTransfer.setData('text/plain', JSON.stringify({
                            breakIndex: e.target.dataset.breakIndex,
                            goRoute: e.target.dataset.goRoute,
                            brId: e.target.dataset.brId,
                            emId: e.target.dataset.emId,
                            queue: e.target.dataset.emQueue,
                            newQueue: e.target.dataset.newQueue,
                            address: e.target.dataset.address,
                            type: 'ex'
                        }));
                        e.dataTransfer.effectAllowed = 'move';
                    }
                });

                // --- Drag from โค้ช พัก to โค้ช (ข้ามสายได้) ---
                document.querySelectorAll('.coach-break-list').forEach(breakList => {
                    breakList.addEventListener('dragstart', function(e) {
                        if (e.target.classList.contains('coach-driver-item')) {
                            e.dataTransfer.setData('text/plain', JSON.stringify({
                                breakIndex: e.target.dataset.breakIndex,
                                brId: e.target.dataset.brId,
                                goRoute: e.target.dataset.goRoute,
                                queue: e.target.dataset.queue,
                                newQueue: e.target.dataset.newQueue,
                                address: e.target.dataset.address,
                                type: 'coach'
                            }));
                            e.dataTransfer.effectAllowed = 'move';
                        }
                    });
                });
                // รองรับ coach-break-list ที่ถูกสร้างใหม่
                document.body.addEventListener('dragstart', function(e) {
                    if (e.target.classList && e.target.classList.contains('coach-driver-item')) {
                        e.dataTransfer.setData('text/plain', JSON.stringify({
                            breakIndex: e.target.dataset.breakIndex,
                            brId: e.target.dataset.brId,
                            goRoute: e.target.dataset.goRoute,
                            queue: e.target.dataset.queue,
                            newQueue: e.target.dataset.newQueue,
                            address: e.target.dataset.address,
                            type: 'coach'
                        }));
                        e.dataTransfer.effectAllowed = 'move';
                    }
                });

                // --- Drop logic for all driver types ---
                document.querySelectorAll('.sortable-tbody').forEach(tbody => {
                    tbody.querySelectorAll('tr').forEach((row, rowIndex) => {
                        row.setAttribute('data-row-index', rowIndex);
                        row.addEventListener('dragover', function(e) {
                            e.preventDefault();
                            e.dataTransfer.dropEffect = 'move';
                            row.classList.add('table-primary');
                        });
                        row.addEventListener('dragleave', function(e) {
                            row.classList.remove('table-primary');
                        });
                        row.addEventListener('drop', function(e) {
                            e.preventDefault();
                            row.classList.remove('table-primary');
                            let data;
                            try {
                                data = JSON.parse(e.dataTransfer.getData('text/plain'));
                                // console.log("Drop data:", data);
                            } catch (err) { return; }
                            const dropRowIndex = parseInt(row.getAttribute('data-row-index'));
                            const brId = row.closest('tbody').dataset.brId;
                            // console.log("DROP EVENT", { data, dropRowIndex, brId });

                            // --- Main driver swap (ข้ามสายได้) ---
                            if (data.type === 'main') {
                                const { breakIndex, goRoute } = data;
                                // console.log("เข้าสู่โค้ด MAIN SWAP", data);
                                const breakDriver = jsMainBreak[goRoute].splice(breakIndex, breakIndex+1)[0];
                                const mainDriver = jsPlan[brId][dropRowIndex];

                                
                                // swap driver
                                jsPlan[brId][dropRowIndex] = {
                                    ...mainDriver,
                                    em_id: breakDriver.em_id,
                                    em_name: breakDriver.em_name,
                                    em_surname: breakDriver.em_surname,
                                    em_queue: breakDriver.em_queue,
                                    em_address: breakDriver.address,
                                };
                                // นำ mainDriver ไปพักที่สายที่ตนเองทำงานอยู่ (brId)
                                if (!jsMainBreak[mainDriver.em_address]) jsMainBreak[mainDriver.em_address] = [];
                                    jsMainBreak[mainDriver.em_address].unshift({
                                    em_id: mainDriver.em_id,
                                    em_name: mainDriver.em_name,
                                    em_surname: mainDriver.em_surname,
                                    em_queue: mainDriver.em_queue,
                                    new_queue: mainDriver.new_queue,
                                    address: mainDriver.em_address,
                                });
                                // Recalculate queues
                                const lastIndex = jsPlan[brId].length - 1;
                                jsPlan[brId].forEach((row, idx) => {
                                    row.new_queue = (idx === lastIndex) ? `${brId}-3-last` : `${brId}-3-${idx + 1}`;
                                });
                                jsMainBreak[breakDriver.address].forEach((driver, idx) => {
                                    driver.new_queue = `${breakDriver.address}-1-${idx + 1}`;
                                });
                                jsMainBreak[mainDriver.em_address].forEach((driver, idx) => {
                                    driver.new_queue = `${mainDriver.em_address}-1-${idx + 1}`;
                                });
                                console.log('DND Main Swap', { jsPlan, jsMainBreak, jsExBreak, jsCoachBreak });   // หลังสลับ main ใน event drop

                                updatePlanTableDOM(brId);
                                updateBreakListDOM(breakDriver.address, breakDriver.address, 'main');
                                updateBreakListDOM(mainDriver.em_address, mainDriver.em_address, 'main');
                                saveStateToLS();
                                console.log('DND Main Swap', { jsPlan, jsMainBreak, jsExBreak, jsCoachBreak }); // ใน onEnd ตารางหลัก
                            }
                            // --- Ex driver swap (ข้ามสายได้) ---
                            else if (data.type === 'ex') {
                                const { breakIndex, goRoute } = data;
                                const breakDri = jsExBreak[goRoute].slice(breakIndex, breakIndex+1)[0]; //ดึงข้อมูลออกมาดู
                                // console.log("goRoute", goRoute);
                                // console.log("breakDri", breakDri);
                                const ExDriver = jsPlan[brId][dropRowIndex];
                                
                                // --- เพิ่ม popup ให้เลือกว่าจะเปลี่ยน ex1 หรือ ex2 ---
                                let exTarget = null;
                                // สร้าง modal ถ้ายังไม่มี
                                // เรียกใช้ modal ที่มีอยู่แล้ว ไม่ต้องสร้างใหม่
                                const exSelectModal = new bootstrap.Modal(document.getElementById('exSelectModal'));
                                console.log('DND ex Swap', { jsPlan, jsMainBreak, jsExBreak, jsCoachBreak }); // ใน onEnd ตารางหลัก
                                exSelectModal.show();
                                // ป้องกัน event ซ้อน
                                document.getElementById('chooseEx1').onclick = null;
                                document.getElementById('chooseEx2').onclick = null;
                                document.getElementById('chooseEx1').onclick = function() {
                                    exTarget = 'ex1';
                                    exSelectModal.hide();
                                    proceedSwapEx(exTarget);
                                };
                                document.getElementById('chooseEx2').onclick = function() {
                                    exTarget = 'ex2';
                                    exSelectModal.hide();
                                    proceedSwapEx(exTarget);
                                };
                                
                                // ฟังก์ชันใหม่สำหรับดำเนินการ swap หลังเลือก
                                function proceedSwapEx(exTarget) {
                                    // ตรวจสอบเงื่อนไข queue
                                    if (ExDriver[exTarget === 'ex1' ? 'ex_queue' : 'ex_queue2'] == '') {
                                            showAlertModal('ไม่สามารถเปลี่ยนพขร.พ่วงได้ เนื่องจากยังไม่ได้กำหนดไว้ในแผน');
                                            return;
                                    } else if (breakDri.em_queue !== ExDriver[exTarget === 'ex1' ? 'ex_queue' : 'ex_queue2']) {
                                            showAlertModal('จุดพักพ่วงไม่ตรงกับที่กำหนดไว้ในแผน ไม่สามารถเปลี่ยนพขร.พ่วงได้');
                                            return;
                                    }

                                    // ฟังก์ชันสำหรับแสดง popup แจ้งเตือน

                                    const breakDriver = jsExBreak[goRoute].splice(breakIndex, 1)[0]; // ลบโค้ชจากสายต้นทาง
                                    console.log("เข้าสู่โค้ด ex SWAP", {jsPlan,goRoute,breakIndex, brId, dropRowIndex,breakDriver,ExDriver });
                                    // swap ex fields ตามที่เลือก
                                    if (exTarget === 'ex1') {
                                        jsPlan[brId][dropRowIndex] = {
                                            ...ExDriver,
                                            ex_id: breakDriver.em_id,
                                            ex_name: breakDriver.em_name,
                                            ex_surname: breakDriver.em_surname,
                                            ex_queue: breakDriver.em_queue,
                                            ex_new_queue: breakDriver.new_queue,
                                            ex_address: breakDriver.address,
                                        };
                                        // นำโค้ชเดิมไปพักที่ ex-break-list สายต้นทาง (fromBrId)
                                        if (!jsExBreak[ExDriver.ex_address]) jsExBreak[ExDriver.ex_address] = [];
                                        jsExBreak[ExDriver.ex_address].unshift({
                                            em_id: ExDriver.ex_id,
                                            em_name: ExDriver.ex_name,
                                            em_surname: ExDriver.ex_surname,
                                            em_queue: ExDriver.ex_queue,
                                            new_queue: ExDriver.ex_new_queue,
                                            address: ExDriver.ex_address,
                                        });
                                    } else {
                                        jsPlan[brId][dropRowIndex] = {
                                            ...ExDriver,
                                            ex_id2: breakDriver.em_id,
                                            ex_name2: breakDriver.em_name,
                                            ex_surname2: breakDriver.em_surname,
                                            ex_queue2: breakDriver.em_queue,
                                            ex_new_queue2: breakDriver.new_queue,
                                            ex_address2: breakDriver.address,
                                        };
                                        // นำโค้ชเดิมไปพักที่ ex-break-list สายต้นทาง (fromBrId)
                                        if (!jsExBreak[ExDriver.ex_address2]) jsExBreak[ExDriver.ex_address2] = [];
                                        jsExBreak[ExDriver.ex_address2].unshift({
                                            em_id: ExDriver.ex_id,
                                            em_name: ExDriver.ex_name,
                                            em_surname: ExDriver.ex_surname,
                                            em_queue: ExDriver.ex_queue,
                                            new_queue: ExDriver.ex_new_queue,
                                            address: ExDriver.ex_address,
                                        });
                                    }
                                    // Recalculate queues
                                    jsPlan[brId].forEach((row, idx) => {
                                        row.ex_new_queue = `${ExDriver.ex_new_queue}`;
                                        row.ex_new_queue2 = `${ExDriver.ex_new_queue2}`;
                                    });
                                    jsExBreak[ExDriver.ex_address].forEach((driver, idx) => {
                                        driver.new_queue = driver.em_queue;
                                    });
                                    jsExBreak[breakDriver.address].forEach((driver, idx) => {
                                        driver.new_queue = driver.em_queue;
                                    });
                                    updatePlanTableDOM(brId);
                                    updateBreakListDOM(breakDriver.address, brId, 'ex');
                                    updateBreakListDOM(breakDriver.address, breakDriver.address, 'ex');
                                    saveStateToLS();
                                }

                                
                            }
                            // --- Coach driver swap (ข้ามสายได้) ---
                            else if (data.type === 'coach') {
                                // data.breakIndex: index ใน coach-break-list สายต้นทาง
                                // data.brId: สายต้นทาง (fromBrId)
                                const goroute = data.goRoute; // สายที่ drop
                                const breakIndex = data.breakIndex;
                                const breakDri = jsCoachBreak[goroute].slice(breakIndex, breakIndex+1)[0]; // ลบโค้ชจากสายต้นทาง
                                const coachDriver = jsPlan[brId][dropRowIndex];



                                if (breakDri['em_queue'] != coachDriver['coach_queue']) {
                                        showAlertModal('ไม่สามารถเปลี่ยนโค้ชได้ เนื่องจากโค้ชไม่ได้อยู่ในจุดพักที่กำหนด');
                                        return;
                                }

                                const breakDriver = jsCoachBreak[goroute].splice(breakIndex, 1)[0]; // ลบโค้ชจากสายต้นทาง
                                console.log("เข้าสู่โค้ด COACH SWAP", {data,goroute,breakIndex, brId, dropRowIndex,breakDriver,coachDriver });
                                
                                   
                                    // swap coach fields
                                    jsPlan[brId][dropRowIndex] = {
                                        ...coachDriver,
                                        coach_id: breakDriver.em_id,
                                        coach_name: breakDriver.em_name,
                                        coach_surname: breakDriver.em_surname,
                                        coach_queue: breakDriver.em_queue,
                                        coach_new_queue: breakDriver.new_queue,
                                        coach_address: breakDriver.address,
                                    };
                                    // นำโค้ชเดิมไปพักที่ coach-break-list สายต้นทาง (fromBrId)
                                    if (!jsCoachBreak[coachDriver.coach_address]) jsCoachBreak[coachDriver.coach_address] = [];
                                    jsCoachBreak[coachDriver.coach_address].unshift({
                                        em_id: coachDriver.coach_id,
                                        em_name: coachDriver.coach_name,
                                        em_surname: coachDriver.coach_surname,
                                        em_queue: coachDriver.coach_queue,
                                        new_queue: coachDriver.coach_new_queue,
                                        address: coachDriver.coach_address,
                                    });
                                    // Recalculate queues
                                    jsPlan[brId].forEach((row, idx) => {
                                        row.coach_new_queue = `${coachDriver.coach_new_queue}`;
                                    });
                                    jsCoachBreak[coachDriver.coach_address].forEach((driver, idx) => {
                                        driver.new_queue = driver.em_queue;
                                    });
                                    (jsCoachBreak[breakDriver.address] || []).forEach((driver, idx) => {
                                        driver.new_queue = driver.em_queue;
                                    });
                                
                                    updatePlanTableDOM(brId);
                                    updateBreakListDOM(coachDriver.coach_address, coachDriver.coach_address, 'coach');
                                    updateBreakListDOM(breakDriver.address, breakDriver.address, 'coach');
                                    saveStateToLS();
                                    console.log('DND Coach Swap', { jsPlan, jsMainBreak, jsExBreak, jsCoachBreak });
                                
                            }
                        });
                    });
                });

                // --- Sortable for พขร. พัก (main-break-list) ---
                // ปรับให้รองรับข้ามสาย (drag & drop ภายใน list เดิมเท่านั้น)
                document.body.addEventListener('sortupdate', function(e) {
                    // ไม่ต้องใช้ ถ้าใช้ Sortable.js
                });
                document.querySelectorAll('.main-break-list').forEach(breakList => {
                    const goRoute = breakList.dataset.goRoute;
                    const brId = breakList.dataset.brId;
                    new Sortable(breakList, {
                        animation: 150,
                        ghostClass: 'sortable-ghost',
                        onEnd: function (evt) {
                            if (evt.oldIndex === evt.newIndex) return;
                            const arr = jsMainBreak[goRoute];
                            const moved = arr.splice(evt.oldIndex, 1)[0];
                            arr.splice(evt.newIndex, 0, moved);
                            // Recalculate queue
                            arr.forEach((driver, idx) => {
                                driver.new_queue = `${goRoute}-1-${idx + 1}`;
                            });
                            updateBreakListDOM(goRoute, brId, 'main');
                            saveStateToLS();
                        }
                    });
                });

                // --- Sortable for พขร.พ่วง พัก ---
                document.querySelectorAll('.ex-break-list').forEach(breakList => {
                    const brId = breakList.dataset.brId;
                    new Sortable(breakList, {
                        animation: 150,
                        ghostClass: 'sortable-ghost',
                        onEnd: function (evt) {
                            if (evt.oldIndex === evt.newIndex) return;
                            const arr = jsExBreak[brId];
                            const moved = arr.splice(evt.oldIndex, 1)[0];
                            arr.splice(evt.newIndex, 0, moved);
                            arr.forEach((driver, idx) => {
                                driver.new_queue = `${brId}-2-${idx + 1}`;
                            });
                            updateBreakListDOM(brId, brId, 'ex');
                            saveStateToLS();
                        }
                    });
                });

                // --- Sortable for โค้ช พัก ---
                document.querySelectorAll('.coach-break-list').forEach(breakList => {
                    const brId = breakList.dataset.brId;
                    new Sortable(breakList, {
                        animation: 150,
                        ghostClass: 'sortable-ghost',
                        onEnd: function (evt) {
                            if (evt.oldIndex === evt.newIndex) return;
                            const arr = jsCoachBreak[brId];
                            const moved = arr.splice(evt.oldIndex, 1)[0];
                            arr.splice(evt.newIndex, 0, moved);
                            arr.forEach((driver, idx) => {
                                driver.new_queue = `${brId}-2-${idx + 1}`;
                            });
                            updateBreakListDOM(brId, brId, 'coach');
                            saveStateToLS();
                        }
                    });
                });

                // --- เพิ่ม event สำหรับ dropdown เลือกสายของกลุ่มพัก ---
                // Main break
                document.querySelectorAll('.main-break-route-select').forEach(select => {
                    select.addEventListener('change', function() {
                        const goRoute = this.value;
                        const brId = this.getAttribute('data-current-br');
                        updateBreakListDOM(goRoute, brId, 'main', true);
                    });
                });
                // Ex break
                document.querySelectorAll('.ex-break-route-select').forEach(select => {
                    select.addEventListener('change', function() {
                        const brId = this.value;
                        const currentBr = this.getAttribute('data-current-br');
                        updateBreakListDOM(brId, currentBr, 'ex', true);
                    });
                });
                // Coach break
                document.querySelectorAll('.coach-break-route-select').forEach(select => {
                    select.addEventListener('change', function() {
                        const brId = this.value;
                        const currentBr = this.getAttribute('data-current-br');
                        updateBreakListDOM(brId, currentBr, 'coach', true);
                    });
                });

                function updatePlanTableDOM(br_id) {
                    const tbody = document.querySelector(`.sortable-tbody[data-br-id="${br_id}"]`);
                    if (!tbody) return;
                    jsPlan[br_id].forEach((planItem, index) => {
                        const row = tbody.querySelector(`tr[data-row-index="${index}"]`);
                        if (row) {
                            row.cells[3].innerHTML = `${planItem.em_name} <span class="badge bg-light text-dark">${planItem.new_queue}</span>`;
                            row.cells[5].innerHTML = `${planItem.ex_name} <span class="badge bg-light text-dark">${planItem.ex_queue} => ${planItem.ex_new_queue}</span>`;
                            // ex2 (คนที่ 2) ควรอยู่ cell[6]
                            row.cells[6].innerHTML = `${planItem.ex_name2} <span class="badge bg-light text-dark">${planItem.ex_queue2} => ${planItem.ex_new_queue2}</span>`;
                            // coach ควรอยู่ cell[7]
                            row.cells[7].innerHTML = `${planItem.coach_name} <span class="badge bg-light text-dark">${planItem.coach_queue} => ${planItem.coach_new_queue}</span>`;
                        }
                    });
                }
                function updateBreakListDOM(go_route, br_id, type, replaceContainer) {
                    let breakListUl, breakData, badgeClass, containerId, html = '';
                    if (type === 'main' || !type) {
                        breakData = jsMainBreak[go_route] || [];
                        badgeClass = 'bg-secondary';
                        containerId = `main-break-list-container-${br_id}`;
                    } else if (type === 'ex') {
                        breakData = jsExBreak[go_route] || [];
                        badgeClass = 'bg-warning text-dark';
                        containerId = `ex-break-list-container-${br_id}`;
                    } else if (type === 'coach') {
                        breakData = jsCoachBreak[go_route] || [];
                        badgeClass = 'bg-info';
                        containerId = `coach-break-list-container-${br_id}`;
                    }
                    if (replaceContainer) {
                        if (breakData.length > 0) {
                            html += `<ul class="list-group ${type}-break-list" id="${type}-break-list-${go_route}" data-go-route="${go_route}" data-br-id="${br_id}">`;
                            breakData.forEach((item, index) => {
                            if(type === 'ex') {

                                html += `<li class="list-group-item d-flex justify-content-between align-items-center ex-driver-item" draggable="true" data-break-index="${index}" data-go-route="${go_route}" data-br-id="${br_id}" data-em-id="${item.em_id}" data-em-queue="${item.em_queue}" data-new-queue="${item.new_queue}" data-address="${item.address}">
                                    ${item.em_name}
                                    <span class="badge ${badgeClass} rounded-pill">${item.em_queue}</span>
                                </li>`;
                            } else if(type === 'coach') {
                                html += `<li class="list-group-item d-flex justify-content-between align-items-center coach-driver-item" draggable="true" data-break-index="${index}" data-go-route="${go_route}" data-br-id="${br_id}" data-em-id="${item.em_id}" data-em-queue="${item.em_queue}" data-new-queue="${item.new_queue} data-address="${item.address}">
                                    ${item.em_name}
                                    <span class="badge ${badgeClass} rounded-pill">${item.em_queue}</span>
                                </li>`;
                            } else {
                                html += `<li class="list-group-item d-flex justify-content-between align-items-center break-driver-item" draggable="true" data-break-index="${index}" data-go-route="${go_route}" data-br-id="${br_id}" data-em-id="${item.em_id}" data-em-queue="${item.em_queue}" data-new-queue="${item.new_queue} data-address="${item.address}">
                                    ${item.em_name}
                                    <span class="badge ${badgeClass} rounded-pill">${item.em_queue} => ${item.new_queue}</span>
                                </li>`;
                            }
                            });
                            html += `</ul>`;
                        } else {
                            html = `<p><i>ไม่มีข้อมูล</i></p>`;
                        }
                        document.getElementById(containerId).innerHTML = html;
                    } else {
                        // ...เดิม...
                        if (type === 'main' || !type) {
                            breakListUl = document.getElementById(`main-break-list-${go_route}`);
                        } else if (type === 'ex') {
                            breakListUl = document.getElementById(`ex-break-list-${br_id}`);
                        } else if (type === 'coach') {
                            breakListUl = document.getElementById(`coach-break-list-${br_id}`);
                        }
                        if (!breakListUl) return;
                        breakListUl.innerHTML = '';
                        breakData.forEach((item, index) => {
                            const li = document.createElement('li');
                            if (type === 'main' || !type) {
                                li.className = 'list-group-item d-flex justify-content-between align-items-center break-driver-item';
                                li.setAttribute('draggable', 'true');
                                li.dataset.breakIndex = index;
                                li.dataset.goRoute = go_route;
                                li.dataset.brId = br_id;
                                li.dataset.emId = item.em_id;
                                li.dataset.address = item.address;
                            } else if (type === 'ex') {
                                li.className = 'list-group-item d-flex justify-content-between align-items-center ex-driver-item';
                                li.setAttribute('draggable', 'true');
                                li.dataset.breakIndex = index;
                                li.dataset.goRoute = go_route;
                                li.dataset.brId = br_id;
                                li.dataset.emId = item.em_id;
                                li.dataset.emQueue = item.em_queue;
                            } else if (type === 'coach') {
                                li.className = 'list-group-item d-flex justify-content-between align-items-center coach-driver-item';
                                li.setAttribute('draggable', 'true');
                                li.dataset.breakIndex = index;
                                li.dataset.goRoute = go_route;
                                li.dataset.brId = br_id;
                            }
                            li.innerHTML = `
                                ${item.em_name}
                                <span class="badge ${badgeClass} rounded-pill">${item.new_queue}</span>
                            `;
                            breakListUl.appendChild(li);
                        });
                    }
                    // รีอินิท sortable หลังเปลี่ยนสาย
                    if (replaceContainer) {
                        setTimeout(() => {
                            if (type === 'main' || !type) {
                                const breakList = document.getElementById(`main-break-list-${go_route}`);
                                if (breakList) {
                                    new Sortable(breakList, {
                                        animation: 150,
                                        ghostClass: 'sortable-ghost',
                                        onEnd: function (evt) {
                                            if (evt.oldIndex === evt.newIndex) return;
                                            const goRoute = breakList.dataset.goRoute;
                                            const brId = breakList.dataset.brId;
                                            const arr = jsMainBreak[goRoute];
                                            const moved = arr.splice(evt.oldIndex, 1)[0];
                                            arr.splice(evt.newIndex, 0, moved);
                                            arr.forEach((driver, idx) => {
                                                driver.new_queue = `${goRoute}-1-${idx + 1}`;
                                            });
                                            updateBreakListDOM(goRoute, brId, 'main');
                                            saveStateToLS();
                                        }
                                    });
                                }
                            } else if (type === 'ex') {
                                const breakList = document.getElementById(`ex-break-list-${go_route}`);
                                if (breakList) {
                                    new Sortable(breakList, {
                                        animation: 150,
                                        ghostClass: 'sortable-ghost',
                                        onEnd: function (evt) {
                                            if (evt.oldIndex === evt.newIndex) return;
                                            const goRoute = breakList.dataset.goRoute;
                                            const brId = breakList.dataset.brId;
                                            const arr = jsExBreak[goRoute];   
                                            const moved = arr.splice(evt.oldIndex, 1)[0];
                                            arr.splice(evt.newIndex, 0, moved);
                                            // arr.forEach((driver, idx) => {
                                            //     driver.new_queue = `${goRoute}-2-${idx + 1}`;
                                            // });
                                            updateBreakListDOM(goRoute, brId, 'ex');
                                            saveStateToLS();
                                        }
                                    });
                                }
                            } else if (type === 'coach') {
                                const breakList = document.getElementById(`coach-break-list-${go_route}`);
                                if (breakList) {
                                    new Sortable(breakList, {
                                        animation: 150,
                                        ghostClass: 'sortable-ghost',
                                        onEnd: function (evt) {
                                            if (evt.oldIndex === evt.newIndex) return;
                                            const goRoute = breakList.dataset.goRoute;
                                            const brId = breakList.dataset.brId;
                                            const arr = jsCoachBreak[goRoute];
                                            const moved = arr.splice(evt.oldIndex, 1)[0];
                                            arr.splice(evt.newIndex, 0, moved);
                                            // arr.forEach((driver, idx) => {
                                            //     driver.new_queue = `${goRoute}-2-${idx + 1}`;
                                            // });
                                            updateBreakListDOM(goRoute, brId, 'coach');
                                            saveStateToLS();
                                        }
                                    });
                                }
                            }
                        }, 10);
                    }
                }

                // หลังจาก render หรือโหลดหน้า
                Object.keys(jsPlan).forEach(function(br_id) {
                    // main break
                    updateBreakListDOM(br_id, br_id, 'main', true);
                    // ex break
                    updateBreakListDOM(br_id, br_id, 'ex', true);
                    // coach break
                    updateBreakListDOM(br_id, br_id, 'coach', true);
                });
            });
            // --- END: Drag and Drop Logic ---

            // เมื่อกด submit ฟอร์ม จะใส่ข้อมูล plan, main_break, exnotredy, coachnotredy เป็น JSON ลงใน input hidden
            const planForm = document.getElementById('plan-form');
            if (planForm) {
                planForm.addEventListener('submit', function(e) {
                    // อัปเดต hidden input ด้วยข้อมูล JS ล่าสุด
                    document.getElementById('plan_data').value = JSON.stringify(jsPlan);
                    document.getElementById('pr_ids_data').value = JSON.stringify(<?php echo json_encode($pr_ids); ?>);
                    document.getElementById('main_break_data').value = JSON.stringify(jsMainBreak);
                    document.getElementById('exnotredy_data').value = JSON.stringify(jsExBreak);
                    document.getElementById('coachnotredy_data').value = JSON.stringify(jsCoachBreak);
                });
            }

            // Auto-submit form on date change only
            document.getElementById('date-select').addEventListener('change', function() {
                document.getElementById('date-filter-form').submit();
            });

            // Sidebar route tab click: switch tab client-side (no reload)
            document.querySelectorAll('#v-pills-tab .nav-link').forEach(function(btn) {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    // Remove active from all tabs
                    document.querySelectorAll('#v-pills-tab .nav-link').forEach(function(tab) {
                        tab.classList.remove('active');
                        tab.setAttribute('aria-selected', 'false');
                    });
                    // Add active to clicked tab
                    btn.classList.add('active');
                    btn.setAttribute('aria-selected', 'true');
                    // Hide all tab-panes
                    document.querySelectorAll('.tab-pane').forEach(function(pane) {
                        pane.classList.remove('show', 'active');
                    });
                    // Show selected tab-pane
                    const targetId = btn.getAttribute('data-bs-target');
                    const pane = document.querySelector(targetId);
                    if (pane) {
                        pane.classList.add('show', 'active');
                    }
                });
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

            function showAlertModal(message) {
                const alertModal = document.getElementById('alertModal');
                document.getElementById('alertModalBody').innerHTML = message;
                const bsAlertModal = new bootstrap.Modal(alertModal);
                bsAlertModal.show();
            }

            
        </script>
        <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>