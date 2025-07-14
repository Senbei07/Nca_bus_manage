<?php 
    include("config.php");
    // session_start();

    $sql = "SELECT
                dpt.dpt_id AS id,
                dpt.dpt_date_start AS d_start,
                dpt.dpt_time_start AS t_start,
                dpt.dpt_date_end AS d_end,
                dpt.dpt_time_end AS t_end,
                dpt.group AS group_id,
                dpt.pt_id AS pt_id,

                bi.bi_licenseplate AS licen,
                bi.bi_capacity AS capacity,

                bt.bt_name AS bus_type,

                bsc.bsc_name AS sub_class,

                bs.bs_name AS bus_status,

                br.br_id AS br_id,
                
                title_m.title_name AS m_title,
                em_main.em_name AS m_name,
                em_main.em_surname AS m_surname,
                gen_m.gen_name_th AS m_gen,
                
                title_ex1.title_name AS ex1_title,
                em_ex1.em_name AS ex1_name,
                em_ex1.em_surname AS ex1_surname,
                gen_ex1.gen_name_th AS ex1_gen,
                
                title_ex2.title_name AS ex2_title,
                em_ex2.em_name AS ex2_name,
                em_ex2.em_surname AS ex2_surname,
                gen_ex2.gen_name_th AS ex2_gen,
                
                title_coach.title_name AS coach_title,
                em_coach.em_name AS coach_name,
                em_coach.em_surname AS coach_surname,
                gen_coach.gen_name_th AS coach_gen,
                
                loc_start.locat_name_th AS loc_start,
                loc_start.locat_name_eng AS loc_start_eng,
                loc_end.locat_name_th AS loc_end,
                loc_end.locat_name_eng AS loc_end_eng,
                loc_start.locat_id AS start_id,
                loc_end.locat_id AS end_id

            FROM `dri_plan_t` AS dpt 
            LEFT JOIN 
                bus_routes AS br ON dpt.br_id = br.br_id 
            LEFT JOIN
                `group` AS g ON dpt.group = g.group_id 
            LEFT JOIN 
                employee AS em_main ON g.main_dri = em_main.em_id
            LEFT JOIN 
                employee AS em_ex1 ON g.ex_dri1 = em_ex1.em_id
            LEFT JOIN 
                employee AS em_ex2 ON g.ex_dri2 = em_ex2.em_id
            LEFT JOIN 
                employee AS em_coach ON g.coach = em_coach.em_id
            LEFT JOIN 
                title_name AS title_m ON em_main.title_id = title_m.title_id
            LEFT JOIN 
                title_name AS title_ex1 ON em_ex1.title_id = title_ex1.title_id
            LEFT JOIN 
                title_name AS title_ex2 ON em_ex2.title_id = title_ex2.title_id
            LEFT JOIN 
                title_name AS title_coach ON em_coach.title_id = title_coach.title_id
            LEFT JOIN 
                gender AS gen_m ON em_main.gen_id = gen_m.gen_id
            LEFT JOIN 
                gender AS gen_ex1 ON em_ex1.gen_id = gen_ex1.gen_id
            LEFT JOIN 
                gender AS gen_ex2 ON em_ex2.gen_id = gen_ex2.gen_id
            LEFT JOIN 
                gender AS gen_coach ON em_coach.gen_id = gen_coach.gen_id
            LEFT JOIN 
                location AS loc_start ON br.br_start = loc_start.locat_id
            LEFT JOIN 
                location AS loc_end ON br.br_end = loc_end.locat_id
            LEFT JOIN 
                bus_zone AS bz ON br.bz_id = bz.bz_id
            LEFT JOIN 
                bus_info AS bi ON g.bi_id = bi.bi_id
            LEFT JOIN 
                bus_type AS bt ON bi.bt_id = bt.bt_id
            LEFT JOIN 
                bus_sub_class AS bsc ON bt.bsc_id = bsc.bsc_id
            LEFT JOIN 
                bus_status AS bs ON bi.bs_id = bs.bs_id
            ORDER BY dpt_id ;
    ";

    $result = mysqli_query($conn, $sql);

    $event_zone_data = [];
    $event_route_data = [];

    // วนลูปเพื่อดึงข้อมูลทีละแถวและเพิ่มเข้าในอาร์เรย์
    while($row = mysqli_fetch_assoc($result)){

        $main_driver_name =  $row['m_title'] . $row['m_name'] . ' ' . $row['m_surname'] ;
        $ex_driver1_name =  $row['ex1_title'] . $row['ex1_name'] . ' ' . $row['ex1_surname'] ;
        $ex_driver2_name = $row['ex2_title'] . $row['ex2_name'] . ' ' . $row['ex2_surname'] ;
        $coach_name = $row['coach_title'] . $row['coach_name'] . ' ' . $row['coach_surname'] ;
        
        $event_zone_data[] = [
            'id'               => $row['id'],
            'date'             => $row['d_start'],
            'date_end'         => $row['d_end'],
            'start'            => $row['d_start'] . 'T' . $row['t_start'],
            'end'              => $row['d_end'] . 'T' . $row['t_end'],
            'title'            => $row['licen'], 
            'resource'         => $row['loc_start_eng'].$row['loc_end_eng'], 
            'time'             => substr($row['t_start'], 0, 5) . ' - ' . substr($row['t_end'], 0, 5),
            'time_start'       => substr($row['t_start'], 0, 5) ,
            'time_end'         => substr($row['t_end'], 0, 5),
            'br_id'            => $row['br_id'],
            'lo_start'         => $row['loc_start'],
            'lo_end'           => $row['loc_end'],
            'group'           => $row['group_id'],
            'main_dri'         => $main_driver_name,
            'ex_dri1'          => $ex_driver1_name,
            'ex_dri2'          => $ex_driver2_name,
            'coach'            => $coach_name,
            'pt_id'            => $row['pt_id'],

        ];
        $event_route_data[] = [
            'id'               => $row['id'],
            'date'             => $row['d_start'],
            'start'            => $row['d_start'] . 'T' . $row['t_start'],
            'end'              => $row['d_end'] . 'T' . $row['t_end'],
            'title'            => $row['licen'], 
            'resource'         => $row['licen'], 
            'time'             => substr($row['t_start'], 0, 5) . ' - ' . substr($row['t_end'], 0, 5),
            'time_start'       => substr($row['t_start'], 0, 5) ,
            'time_end'         => substr($row['t_end'], 0, 5),
            'br_id'            => $row['br_id'],
            'lo_start'         => $row['loc_start'],
            'lo_end'           => $row['loc_end'],
            'main_dri'         => $main_driver_name,
            'ex_dri1'          => $ex_driver1_name,
            'ex_dri2'          => $ex_driver2_name,
            'coach'            => $coach_name,
        ];
    }


    $sql_route = "SELECT
                      lo_start.locat_name_th AS start_th,
                        lo_end.locat_name_th AS end_th,
                        lo_start.locat_name_eng AS start_eng,
                        lo_end.locat_name_eng AS end_eng,
                        br.bz_id AS zone
                    FROM `bus_routes` AS br
                    LEFT JOIN 
                      location AS lo_start ON br.br_start = lo_start.locat_id
                    LEFT JOIN 
                      location AS lo_end ON br.br_end = lo_end.locat_id;
    ";

  $result_route = mysqli_query($conn,$sql_route);

    

    $north_zone = [];
    $northeastern_zone = [];
    $cross_zone = [];

    

    // วนลูปเพื่อดึงข้อมูลทีละแถวและเพิ่มเข้าในอาร์เรย์
    while($row_route = mysqli_fetch_assoc($result_route)){

        $id =  $row_route['start_eng'] . $row_route['end_eng'];
        $name =  $row_route['start_th'] .'-'. $row_route['end_th'];
        if($row_route['zone'] == '1'){
        $north_zone[] = [
            'id'    => $id,
            'name'  => $name,
            'color' => '#1dab2f',
            'status'=> 'on site',

        ];
        }else if($row_route['zone'] == '2'){
        $northeastern_zone[] = [
            'id'    => $id,
            'name'  => $name,
            'color' => '#1dab2f',
            'status'=> 'on site',

        ];
        }else{
          $cross_zone[] = [
            'id'    => $id,
            'name'  => $name,
            'color' => '#1dab2f',
            'status'=> 'on site',

        ];
        }

    }

    $allzone = [$north_zone,$northeastern_zone,$cross_zone];

    $resources_zone_data = [];
                       
     $sql_zone = "SELECT * FROM bus_zone";
     $result_zone = mysqli_query($conn, $sql_zone);
     $i = 0 ;
     while($row_zone = mysqli_fetch_assoc($result_zone)){
      $resources_zone_data[] = [
        'id' => $row_zone['bz_name_en'],
        'name' => $row_zone['bz_name_th'],
        'eventCreation' => false,
        'children'  => $allzone[$i]
      ];
      $i++;
     }

    $sql_bus = " SELECT 
                    br.br_id AS id,
                    lo_start.locat_name_th AS lo_s_th,
                    lo_end.locat_name_eng AS lo_end_th,
                    lo_start.locat_name_th AS lo_s_en,
                    lo_end.locat_name_eng AS lo_end_en,
                    bi.bi_licenseplate AS licen
                  FROM 
                    `bus_routes` AS br 
                  LEFT JOIN 
                    bus_info AS bi ON br.br_id = bi.br_id 
                  LEFT JOIN 
                    location AS lo_start ON br.br_start = lo_start.locat_id
                  LEFT JOIN
                    location AS lo_end ON br.br_end = lo_end.locat_id
                  ORDER BY 
                    br.br_id;";
      $result_bus = mysqli_query($conn , $sql_bus);

      $bus =[];
      $route_group = [];
      $route_old = '';
      $route_name = '';
      while($rows_bus = mysqli_fetch_assoc($result_bus)){
          $route_new = $rows_bus['id'];
          // echo $route_new;
          // echo '<br>';
          // echo '++++++++++++++++++++++++++++++++++++++';
          // echo '<br>';
        
        if($route_new == $route_old || $route_old == ''){
          $route_group[] = [
                    'id' => $rows_bus['licen'],
                    'name' => $rows_bus['licen'],
                    'color' => '#1dab2f',
                    'status'  => 'on site'
          ];
          // echo $route_new;
          // echo '<br>';
          // echo $route_old;
          // echo '<br>';
          // echo '=----------------------';
          // echo '<br>';
          $route_old = $route_new;
          $route_name = $rows_bus['lo_end_th'];
        }else{
          $bus[] = [
                    'id' => $route_name,
                    'name' => $route_name,
                    'eventCreation' => false,
                    'children'  => $route_group
          ];
          $route_group = [];

          $route_group[] = [
                    'id' => $rows_bus['licen'],
                    'name' => $rows_bus['licen'],
                    'color' => '#1dab2f',
                    'status'  => 'on site'
          ];
          $route_new = $route_old;
        }
      }

?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="css/manage.css">

    
    <script>
        // Ignore this in your implementation
        window.isMbscDemo = true;
    </script>

        <!-- Mobiscroll JS and CSS Includes -->
    <link rel="stylesheet" href="css/mobiscroll.javascript.min.css">
    <script src="js/mobiscroll.javascript.min.js"></script>
      
</head>


<body class='bg-light'>
    <div class="alert-popup d-none" id="alert">
        <h4 class="alert-title">แจ้งปัญหารถโดยสาร</h4>
        <?php for ($i = 1; $i <= 3; $i++) { ?>
            <div class="alert-card">
                <div class="alert-header">
                    <strong>สาย:</strong> กรุงเทพ - เชียงใหม่<br>
                    <strong>เที่ยวเวลา:</strong> 06.30
                </div>
                <div class="alert-body">
                    <p><strong>พขร พ่วง1:</strong> นาย เอบี</p>
                    <p><strong>พขร พ่วง2:</strong> บีเอ</p>
                    <p><strong>โค้ช:</strong> นางสาว เอเอเอ</p>
                    <p class="text-danger"><strong>ปัญหา:</strong> Lorem ipsum dolor sit amet, consectetur adipisicing elit. Sint cumque atque odio.</p>
                </div>
            </div>
        <?php } ?>
        <div class="text-center mt-3">
            <button class="btn btn-danger" onclick="active('alert')">ปิด</button>
        </div>
    </div>

    <div class="container ">
        <aside class="sidebar shadow d-none" id="sidebar">
            <?php include('sidebar.php') ?>
        </aside> 
        <main class="main mw-100-vw" id='main'>
            <?php include('header.php'); ?>
            <div class="section w-70">
                <div class="Overview">
                    <div class="plan over-bar">
                        <p>จำนวนเที่ยวตามแผน</p>
                        <h3>300</h3>
                    </div>
                    <div class="out over-bar">
                        <p>รถออกแล้ว</p>
                        <h3>24</h3>
                    </div>
                    <div class="delay over-bar">
                        <p>รถอออกช้า (อู่) :  </p>
                        <p>รถอออกช้า (ต้นทาง) :  </p>
                    </div>
                    <div class="acsident over-bar">
                        <p>อุบัติเหตุระหว่างทาง</p>
                        <h3>0</h3>
                    </div>
                    <div class="maintenace over-bar">
                        <p>รถตามแผนจอดซ่อม</p>
                        <h3>16</h3>
                    </div>
                    <div class="maintenace over-bar bg-danger" onclick="active('alert')">
                        <p >พบปัญหาด่วน</p>
                        <h3>3</h3>
                    </div>
                </div>
                <div class="fillter w-100 d-flex  justify-content-end mt-3" id="fillter">
                    <form action="">
                        <select name="route" id="route-select" class="form-select w-auto">
                            <?php
                                foreach($busRoutes as $route){
                            ?>
                            <option value="<?php echo $route ?>"><?php echo $route ?></option>
                            <?php } ?>
                        </select>
                    </form>
                    <button class="zone bg-secondary  " id="btn-zone" onclick="dataselect('zone')">Zone</button>
                    <button class="route " id="btn-route" onclick="dataselect('route')">Route</button>
                    <button class="list " id="btn-list" onclick="dataselect('list')">list</button>
                    <!-- <button class="route w-20" id="Orientation" onclick="setOrientation()">แนวนอน</button> -->
                </div>
                <div class="bus-table horizontal " id='zone'>
                    <div id="calendar-zone"></div>
                        <div style="display:none">
                            <div id="filtering-popup-zone">
                                <div class="mbsc-form-group">
                                    <div class="mbsc-form-group-title">Operational Status</div>
                                    <label>
                                        <input
                                        type="checkbox"
                                        mbsc-checkbox
                                        data-label="In maintenance"
                                        class="mds-resource-filtering-checkbox"
                                        value="in maintenance"
                                        checked
                                        />
                                    </label>
                                    <label>
                                        <input
                                        type="checkbox"
                                        mbsc-checkbox
                                        data-label="On site"
                                        class="mds-resource-filtering-checkbox"
                                        value="on site"
                                        checked
                                        />
                                    </label>
                                </div>
                                <div class="mbsc-form-group">
                                    <div class="mbsc-form-group-title">Job sites</div>
                                    <div id="resource-list"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bus-table horizontal pt-20 m-10 w-100 h-30 d-none" id='list'>
                        <div class="table-container" style='height:500px;'>
                            <h3 class='position-fixed'>เที่ยวไป</h3>
                            <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>ลำดับ</th>
                                            <th>รหัสรถ</th>
                                            <th>ทะเบียนรถ</th>
                                            <th>สถานะ</th>
                                            <th>เที่ยวรถ (ไป)</th>
                                            <th>เที่ยวรถ (กลับ)</th>
                                            <th>พลขับรถ</th>
                                            <th>พลขับรถ พ่วง1</th>
                                            <th>พลขับรถ พ่วง2</th>
                                            <th>โค้ช</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        for($i =1 ; $i <= 20; $i++){
                                        ?>
                                        <tr>
                                            <td><?php echo $i;?></td>
                                            <td>18-1234</td>
                                            <td> กข-2386</td>
                                            <td>กำลังเดินทางไป</td>
                                            <td>7.00 18-1234</td>
                                            <td>17.00 18-1234</td>
                                            <td>นาย กอขอ คองอ</td>
                                            <td>นาย กอกอ ปอปอ</td>
                                            <td>นาย กอไก่ ปอปลา</td>
                                            <td>นางสาว เอบี ซีดี</td>
                                        </tr>
                                        <?php
                                            }
                                        ?>
                                    </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                
                    
                   
                </div>
                </div>
                </div>
            </div>

        </main>            
        <aside class='info-window bg-light position-absolute d-flex flex-col d-none' id='info-window'>
            <div>
                <div class='info-header d-flex'>
                    <h2 id='info-route-num'>18-1234</h2>
                    <p class='info-bus-status bg-info' id='info-bus-status'> รถกำลังเดินทาง</p>
                </div>
                <div class='info-con'>         
                    <p id='info-route'>สาย: กรุงเทพ - เชียงใหม่</p>
                    <p id='info-time'>เที่ยวเวลา :06.30</p>
                    <p id='info-start'>เวลาออกต้นทาง :06.50</p>
                    <p id='info-end'>เวลาถึงปลายทาง :--</p>
                    <p id='info-main-dri'>พขร :นายกอขอคอ เอบี</p>
                    <p id='info-ex-dri1'>พขร พ่วง1:นาย เอบี</p>
                    <p id='info-ex-dri2'>พขร พ่วง2: บีเอ </p>
                    <p id='info-coach'>โค้ช: นางสาว เอเอเอ</p>
                    <p id='info-start-on'>เวลาออกจากอู่: 06.15 น.</p>
                </div>
                <div class='info-btn'>

                    <button onclick="active('changedriver')" id=''>เปลี่ยนพลขับ</button>
                    <form action="change_route.php" method="post"  id='change'>
                        <input type="hidden" id='change_id' name='change_id' class='d-none'>
                        <input type="hidden" id='change_date' name='change_date' class='d-none'>
                        <input type="hidden" id='change_time' name='change_time' class='d-none'>
                        <input type="hidden" id='change_lo' name='change_lo' class='d-none'>
                        <button type='submit' for='change'  >ขยับสายรถ</button>
                    </form>
                    <button onclick="active('editdata')" >แก้ไขข้อมูล</button>
                    <button onclick="active('returnbus')" >รถกลับหัว</button>
                    <!-- <button onclick="active('save')" id=''>บันทึก</button> -->
                    <button onclick="active('info-window','close')" id='' class='bg-danger'>ปิด</button>
                </div>
            </div>  
        </aside>
    <div class="info-menu changedriver bg-light position-absolute d-none" id="changedriver">
        <h4>เปลี่ยนพลขับ</h4>
        <p class="text-danger">คำเตือน .....</p>

        <div class="info-menu-con">
            <form action="update_driver.php" method="post">
                <div class="mb-3">
                    <label for="driver" class="form-label">เลือกพลขับใหม่:</label>
                    <select name="driver_id" id="driver-select" class="form-select" required>
                        <!-- <option> จะถูกเพิ่มด้วย JavaScript -->
                    </select>
                </div>

                <!-- ส่งข้อมูลอื่นเพิ่มเติม เช่น plan_id -->
                <input type="hidden" name="group_id" id="em-group-id" value="">

                <button type="submit" class="btn btn-primary">บันทึก</button>
                <button type="button" onclick="active('changedriver')" class="btn btn-outline-secondary mt-2">ปิด</button>
            </form>
        </div>
    </div>

    <div class="info-menu returnbus bg-light position-absolute d-none" id="returnbus">
    <h4>เลือกรถกลับหัว</h4>
    <p class="text-danger">เลือกรถที่พร้อมให้บริการในรอบกลับหัว</p>

    <div class="info-menu-con">
        <form action="update_return_bus.php" method="post">
            <div class="mb-3">
                <label for="return-bus-select" class="form-label">เลือกรถกลับหัว:</label>
                <select name="return_bus_id" id="return-bus-select" class="form-select" required>
                    <!-- option จะถูกเพิ่มด้วย JS -->
                </select>
            </div>

            <div id="bus-details" style="margin-top: 10px;">
                <!-- รายละเอียดรถจะแสดงที่นี่ -->
            </div>

            <input type="hidden" name="group_id" id="returnbus-group-id" value="">
            <button type="submit" class="btn btn-success">บันทึก</button>
            <button type="button" onclick="active('returnbus')" class="btn btn-outline-secondary mt-2">ปิด</button>
        </form>

    </div>
</div>



        <div class="info-menu editdata bg-light w-25 h-50 position-absolute  d-flex flex-column justify-content-start align-items-center d-none" id='editdata'>
            <h4>แก้ไขข้อมูล</h4>
            <p class='text-danger'>คำเตือน .....</p>
            <div class="info-menu-con">
                <form action="" class='d-flex flex-column w-100' style='flex-direaction:column;'>
                    <div>
                        <label for="">วิธี</label>
                        <select name="driver" id="">
                            <option value="1">รถกลับหัว</option>
                            <option value="2">รถพัก</option>
                            <option value="3">รถในโซน</option>
                        </select>
                    </div>
                    <div>
                        <label for="">โซน</label>
                        <select name="driver" id="">
                            <option value="3">อีสาน</option>
                            <option value="1">เหนือ</option>
                            <option value="2">นอกโซน</option>
                        </select>
                    </div>
                    <div>
                        <label for="">รถสาย</label>
                        <select name="driver" id="" >
                            <?php foreach($busRoutes as $route){ ?>
                            <option value="1"><?php echo 'กรุงเทพ-'.$route; ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <div>
                        <label for="">ทะเบียนรถ</label>
                        <input type="text" value='กข-8265' disabled>
                    </div>
                    <div>
                        <label for="">พลขับรถ</label>
                        <input type="text" value='นายไก่ไก่ ไข่ไก่' disabled>
                    </div>
                    <p>ออกเมื่อ</p>
                    <p>กลับถึงเมื่อ</p>
                    <p>สถานะ: พร้อม</p>
                    <button type="submit">Save</button>
                </form>
            </div>
            <button onclick="active('editdata')" >close</button>
        </div>
    </div>

    <script>


let event_zone = <?php echo json_encode($event_zone_data); ?>;
let Resources_zone = <?php echo json_encode($resources_zone_data); ?>;
let Resources_route = <?php echo json_encode($bus); ?>;
let event_route = <?php echo json_encode($event_route_data); ?>;

 
         const mySelect = document.getElementById('route-select');
         let myEvents = event_zone;
         let myResources = Resources_zone;
         bus_plan(myEvents,myResources);


        function active(value,action,bus_id){
            if(value == 'sidebar'){
                let main = document.getElementById('main');
                main.classList.toggle('mw-100-vw')
                main.classList.toggle('mw-85-vw')
                let element = document.getElementById(value);
                element.classList.toggle('d-none');
            }else if(action != null){
                if(action == 'active'){
                    console.log(value);
                    let element = document.getElementById(value);
                    element.classList.remove('d-none');
                }else{
                    let element = document.getElementById(value);
                    element.classList.add('d-none');
                }
            }else if(value == 'info-window'){
              let element = document.getElementById(value);
              element.classList.add('d-none');
              element.classList.remove('d-none');
            }else{
                let element = document.getElementById(value);
                element.classList.toggle('d-none');
            }
        }

        function dataselect(datatype){
            console.log(datatype)
            let btn_zone = document.getElementById('btn-zone');
            let btn_route = document.getElementById('btn-route');
            let btn_list = document.getElementById('btn-list');
            let zone = document.getElementById('zone');
            // let route = document.getElementById('route');
            let list = document.getElementById('list');
            let formroute = document.getElementById('route-select');
        
            if(datatype == 'zone'){
                btn_zone.classList.add('bg-secondary');
                btn_route.classList.remove('bg-secondary');
                btn_list.classList.remove('bg-secondary');
                zone.classList.remove('d-none');
                // route.classList.add('d-none');
                list.classList.add('d-none');
                formroute.classList.add('d-none');

                myEvents = event_zone;
                myResources = Resources_zone;
                bus_plan(myEvents,myResources);


            }else if(datatype == 'route'){
                btn_zone.classList.remove('bg-secondary');
                btn_route.classList.add('bg-secondary');                
                btn_list.classList.remove('bg-secondary');
                zone.classList.remove('d-none');
                list.classList.add('d-none');
                formroute.classList.add('d-none');

                myEvents = event_route;
                myResources = Resources_route;
                bus_plan(myEvents,myResources);
                
            }else{
                btn_zone.classList.remove('bg-secondary');
                btn_route.classList.remove('bg-secondary');                
                btn_list.classList.add('bg-secondary');                
                zone.classList.add('d-none');
                list.classList.remove('d-none');
                formroute.classList.remove('d-none');
            }
        }
        
        function setOrientation(a){
            let Orientation = document.getElementById('Orientation');
            console.log(Orientation.textContent)
            if(Orientation.textContent == 'แนวตั้ง'){
                console.log('ver')
                Orientation.textContent = 'แนวนอน';
                document.querySelectorAll('.bus-table').classList.remove('vertical');
                document.querySelectorAll('.bus-table').classList.add('horizontal');
            }else{
                console.log('ve')
                Orientation.textContent = 'แนวตั้ง';
                document.querySelectorAll('.bus-table').classList.remove('horizontal');
                document.querySelectorAll('.bus-table').classList.add('vertical');
            }
            

        }

        mySelect.addEventListener('change', (event) => {
            console.log(event.target.value);
        })

           mobiscroll.setOptions({
      locale: mobiscroll.localeTh,           // Specify language like: locale: mobiscroll.localePl or omit setting to use default
      theme: 'windows',                      // Specify theme like: theme: 'ios' or omit setting to use default
        themeVariant: 'light'                // More info about themeVariant: https://mobiscroll.com/docs/javascript/eventcalendar/api#opt-themeVariant
    });

    
    
    function bus_plan(myEvents,myResources){
    var calendarElm = document.getElementById('calendar-zone');
    var popupElm = document.getElementById('filtering-popup-zone');
    var resourceList = document.getElementById('resource-list');
    
    var filters = {};
    var filteredResources = myResources;
    var searchTimeout;
    var searchQuery;
    
    function filterResources() {
      filteredResources = myResources
        .map(function (site) {
          return {
            id: site.id,
            name: site.name,
            color: site.color,
            eventCreation: site.eventCreation,
            children: site.children.filter(function (resource) {
              return filters[resource.status] && (!searchQuery || resource.name.toLowerCase().includes(searchQuery.toLowerCase()));
            }),
          };
        })
        .filter(function (site) {
          return site.children.length > 0 && filters[site.id];
        });
    
      calendar.setOptions({ resources: filteredResources });
    }
    
    var popup = mobiscroll.popup(popupElm, {
      buttons: [                             // More info about buttons: https://mobiscroll.com/docs/javascript/eventcalendar/api#opt-buttons
        'cancel',
        {
          text: 'Apply',
          keyCode: 'enter',
          handler: function () {
            document.querySelectorAll('.mds-resource-filtering-checkbox').forEach(function (checkbox) {
              filters[checkbox.value] = checkbox.checked;
            });
            filterResources();
            popup.close();
            mobiscroll.toast({
              message: 'Filters applied',
            });
          },
          cssClass: 'mbsc-popup-button-primary',
        },
      ],
      contentPadding: false,
      display: 'anchored',                   // Specify display mode like: display: 'bottom' or omit setting to use default
      focusOnClose: false,                   // More info about focusOnClose: https://mobiscroll.com/docs/javascript/eventcalendar/api#opt-focusOnClose
      focusOnOpen: false,
      showOverlay: false,
      width: 400,                            // More info about width: https://mobiscroll.com/docs/javascript/eventcalendar/api#opt-width
    });
    
    var calendar = mobiscroll.eventcalendar(calendarElm, {
      cssClass: 'mds-resource-calendar-zone',
      clickToCreate: false,                   // More info about clickToCreate: https://mobiscroll.com/docs/javascript/eventcalendar/api#opt-clickToCreate
      dragToCreate: false,                    // More info about dragToCreate: https://mobiscroll.com/docs/javascript/eventcalendar/api#opt-dragToCreate
      dragToResize: false,                    // More info about dragToResize: https://mobiscroll.com/docs/javascript/eventcalendar/api#opt-dragToResize
      dragToMove: false,                      // More info about dragToMove: https://mobiscroll.com/docs/javascript/eventcalendar/api#opt-dragToMove
      view: {                                // More info about view: https://mobiscroll.com/docs/javascript/eventcalendar/api#opt-view
        timeline: {
          type: 'week',
          startDay: 0,
          endDay: 8,
          timeCellStep: 60,
          timeLabelStep: 60,
          weekNumbers: true,
        },
      },
      data: myEvents,                        // More info about data: https://mobiscroll.com/docs/javascript/eventcalendar/api#opt-data
      resources: myResources, 
      onEventClick: function (args) {                           // More info about onEventClick: https://mobiscroll.com/docs/javascript/eventcalendar/api#event-onEventClick
        // console.log(args)
        
        fetch(`get_employee.php?route=${args.event.br_id}&date=${args.event.date_end}&time=${args.event.time_start}&id=${args.event.pt_id}`)
        .then(response => response.json())
        .then(data => {
            active('info-window');

            // แสดงรายชื่อพลขับ
            const driverSelect = document.getElementById('driver-select');
            driverSelect.innerHTML = '';
            if (data.drivers && data.drivers.length > 0) {
                data.drivers.forEach(driver => {
                    const option = document.createElement('option');
                    option.value = driver.id;
                    option.textContent = driver.name;
                    driverSelect.appendChild(option);
                });
            } else {
                driverSelect.innerHTML = "<option value=''>ไม่พบข้อมูลพลขับ</option>";
            }

            // แสดงทะเบียนรถใน select
            const returnBusSelect = document.getElementById('return-bus-select');
            const busDetailsDiv = document.getElementById('bus-details');
            returnBusSelect.innerHTML = '';

            // เก็บข้อมูลรถทั้งหมดไว้ใน Map (หรือ Object) เพื่อเรียกดูทีหลัง
            const busesMap = new Map();

            if (data.return_bus && data.return_bus.length > 0) {
                data.return_bus.forEach(bus => {
                    busesMap.set(bus.id, bus);

                    const option = document.createElement('option');
                    option.value = bus.id;
                    option.textContent = bus.licen; // แสดงแค่ทะเบียนรถ
                    returnBusSelect.appendChild(option);
                });

                // ฟังก์ชันแสดงรายละเอียดรถ
                function showBusDetails(busId) {
                    const bus = busesMap.get(busId);
                    if (bus) {
                        busDetailsDiv.innerHTML = `
                            <p><strong>ทะเบียนรถ:</strong> ${bus.licen}</p>
                            <p><strong>วันที่กลับหัว:</strong> ${bus.date_end || bus.dpt_date_end || '-'}</p>
                            <p><strong>เวลา:</strong> ${bus.time_end || bus.dpt_time_end || '-'}</p>
                            <p><strong>พลขับ:</strong> ${bus.title} ${bus.name} ${bus.surname}</p>
                            <p><strong>แผนเดินรถ:</strong> ${bus.pt_name}</p>
                            <input type="hidden" value='${bus.main_dri}' name='dri_id' disabled>
                            <input type="hidden" value='${bus.bi_id}' name='bus_id' disabled>
                            <input type="hidden" value='${args.event['group']}' name='group_id' disabled>
                        `;
                    } else {
                        busDetailsDiv.innerHTML = '<p>ไม่พบข้อมูลรายละเอียดรถ</p>';
                    }
                }

                // แสดงข้อมูลรถคันแรกตั้งต้น
                showBusDetails(returnBusSelect.value);

                // event เมื่อเปลี่ยนทะเบียนรถ
                returnBusSelect.addEventListener('change', e => {
                    showBusDetails(e.target.value);
                });

            } else {
                returnBusSelect.innerHTML = "<option value=''>ไม่พบรถกลับหัว</option>";
                busDetailsDiv.innerHTML = '';
            }

        })
        .catch(error => {
            console.error('เกิดข้อผิดพลาด:', error);
            document.getElementById('driver-select').innerHTML = "<option value=''>โหลดข้อมูลไม่สำเร็จ</option>";
            document.getElementById('return-bus-select').innerHTML = "<option value=''>โหลดข้อมูลไม่สำเร็จ</option>";
        });




        



        let em_group_id = document.getElementById('em-group-id');
        em_group_id.value = `${args.event['group']}`;
        // console.log(args.event['group']);



        let route_id = document.getElementById('info-route-num');
        let route = document.getElementById('info-route');
        let time = document.getElementById('info-time');

        let start = document.getElementById('info-start');
        let end = document.getElementById('info-end');


        let main = document.getElementById('info-main-dri');
        let ex1 = document.getElementById('info-ex-dri1');
        let ex2 = document.getElementById('info-ex-dri2');
        let coach = document.getElementById('info-coach');
        let start_on = document.getElementById('info-start-on');
        let change_id = document.getElementById('change_id');
        let change_date = document.getElementById('change_date');
        let change_time = document.getElementById('change_time');
        let change_lo = document.getElementById('change_lo');

        
        route_id.innerHTML = args.event['title'];
        route.innerHTML = `สาย : ${args.event['lo_start']} - ${args.event['lo_end']}`
        time.innerHTML = `เที่ยวออกเวลา : ${args.event['time']}`;
        start.innerHTML = `เวลาออกต้นทาง : ${args.event['time_start']}`;
        end.innerHTML = `เวลาถึงทาง : ${args.event['time_end']}`;
        main.innerHTML = `พนักงานขับ : ${args.event['main_dri']}`;
        ex1.innerHTML = `พนักงานขับ พ่วง1 : ${args.event['ex_dri1']}`;
        ex2.innerHTML = `พนักงานขับ พ่วง2 : ${args.event['ex_dri2']}`;
        coach.innerHTML = `โค้ช : ${args.event['coach']}`;
        change_id.value = `${args.event['id']}`;
        change_date.value = `${args.event['date']}`;
        change_time.value = `${args.event['time_start']}`;
        change_lo.value = `${args.event['br_id']}`;



        
      },               // More info about resources: https://mobiscroll.com/docs/javascript/eventcalendar/api#opt-resources


      // onEventHoverIn: function (args) {
      //   console.log(args)
      // },



      renderResource: function (resource) {  // More info about renderResource: https://mobiscroll.com/docs/javascript/eventcalendar/api#renderer-renderResource
        return (
          '<div>' +
          '<div class="mds-resource-filtering-name">' +
          resource.name +
          '</div>' +
          (resource.status
            ? '<div class="mds-resource-filtering-status">' +
              '<span class="mds-resource-filtering-status-dot" style="background-color:' +
              (resource.status === 'on site' ? 'green' : 'orange') +
              ';"></span>' +
              resource.status +
              '</div>'
            : '') +
          '</div>'
        );
      },
      renderResourceEmpty: function () {
        return (
          '<div class="mds-resource-filtering-empty mbsc-flex mbsc-align-items-center">' +
          '<div  class="mbsc-flex-1-1">' +
          '<img src="https://img.mobiscroll.com/demos/filter-no-result.png" alt="Empty list" style="width:100px;" />' +
          '<p class="mbsc-font mbsc-margin mbsc-medium mbsc-italic mbsc-txt-muted">No resources match your search.</p>' +
          '<p class="mbsc-margin mbsc-medium mbsc-italic mbsc-txt-muted">Adjust your filters or try a different keyword.</p>' +
          '<button mbsc-button id="reset-filters" data-variant="outline">Reset Filters</button>' +
          '</div>' +
          '</div>'
        );
      },
      renderResourceHeader: function () {    // More info about renderResourceHeader: https://mobiscroll.com/docs/javascript/eventcalendar/api#renderer-renderResourceHeader
        return (
          '<div class="mbsc-flex mbsc-align-items-center mbsc-font mds-resource-filtering-search">' +
          '<label class="mbsc-flex-1-1">' +
          '<input type="text" mbsc-input id="search-input" autocomplete="off" data-input-style="outline" data-start-icon="material-search" placeholder="Search..." />' +
          '</label>' +
          '<button mbsc-button id="filter-button" data-start-icon="material-filter-list" data-variant="outline" class="mbsc-flex-none">Filter</button>' +
          '</div>'
        );
      },
    });
    
    calendarElm.addEventListener('input', function (event) {
      if (event.target.matches('#search-input')) {
        clearTimeout(searchTimeout);
        searchQuery = event.target.value.toLowerCase();
        searchTimeout = setTimeout(filterResources, 300);
      }
    });
    
    calendarElm.addEventListener('click', function (event) {
      if (event.target.matches('#filter-button')) {
        // Create resource checkbox list
        var checkboxes = '';
        myResources.forEach(function (site) {
          checkboxes +=
            '<label>' +
            '<input type="checkbox" mbsc-checkbox class="mds-resource-filtering-checkbox" value="' +
            site.id +
            '" checked /> ' +
            site.name +
            '</label>';
        });
    
        resourceList.innerHTML = checkboxes;
        mobiscroll.enhance(resourceList);
    
        // Set checkbox checked states
        document.querySelectorAll('.mds-resource-filtering-checkbox').forEach(function (checkboxElm) {
          var checkbox = mobiscroll.getInst(checkboxElm);
          checkbox.checked = filters[checkboxElm.value];
        });
    
        popup.setOptions({ anchor: event.target });
        popup.open();
      }
    });
    
    calendarElm.addEventListener('click', function (event) {
      if (event.target.matches('#reset-filters')) {
        searchQuery = '';
    
        document.getElementById('search-input').value = '';
        document.querySelectorAll('.mds-resource-filtering-checkbox').forEach(function (checkboxElm) {
          var checkbox = mobiscroll.getInst(checkboxElm);
          checkbox.checked = true;
          filters[checkbox.value] = true;
        });
    
        filterResources();
        mobiscroll.toast({
          message: 'Filters cleared',
        });
      }
    });
    
    // Set initial filters
    filters['on site'] = true;
    filters['in maintenance'] = true;
    myResources.forEach(function (site) {
      filters[site.id] = true;
      site.children.forEach(function (resource) {
        filters[resource.id] = true;
      });
    });

    }


        
    </script>
</body>
</html>