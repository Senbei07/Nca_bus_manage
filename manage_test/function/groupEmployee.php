<?php


function getMainDriver($conn, $route){
    $route_in = '(' . implode(',', $route) . ')';


    $sql_main = "SELECT * FROM `employee` AS em LEFT JOIN bus_info AS bi ON em.main_car = bi.bi_id  WHERE main_route IN $route_in  AND et_id = 1 order by em_queue";


    $result_main = mysqli_query($conn, $sql_main);


    $sql_re = "SELECT * FROM `queue_request` WHERE br_id IN $route_in ORDER BY br_id";
    $result_re = mysqli_query($conn, $sql_re);
    $queue = [];
    $re = [];
    $re_break = [];
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
                $re_break[] = $v;
                $break_key[$v][] = $row['br_id'];
            }
        }
    }
    $route_name = [];
    $main_re = [];
    $break = [];

    while($row_main= mysqli_fetch_assoc($result_main)) {
        if(!(in_array($row_main['main_route'], $route_name))) {
            $route_name[] = $row_main['main_route'];
        }
        
        
        if(in_array($row_main['em_queue'], $queue)) {
            $main_re[] = $row_main;
        } elseif (in_array($row_main['em_queue'], $re_break)) {


            $key = $break_key[$row_main['em_queue']][0];
                $break[$key][] = $row_main;
        } else{
            $row_main['route_queue'] = mb_substr($row_main['em_queue'], 0, 1);
            $main[] = $row_main;
        }
    }


    return [$re, $main, $main_re, $break];
}
    

function getEmployee($conn, $route, $num, $x, $type){
    $route_in = '(' . implode(',', $route) . ')';

    $sql = "SELECT * FROM `employee` WHERE main_route IN $route_in  AND et_id = $type order by em_queue";

    $result = mysqli_query($conn, $sql);

    $data = [];
    while($row = mysqli_fetch_assoc($result)) {
        $data[$row['main_route']][] = [
            'em_id' => $row['em_id'],
            'es_id' => $row['es_id'],
            'main_route' => $row['main_route'],
            'em_name' => $row['em_name'],
            'em_surname' => $row['em_surname'],
            'main_car' => $row['main_car'],
            'em_queue' => $row['em_queue'],
        ];
        
    }

    foreach( $num as $key => $value){

        $i = 1;
        $a = 1;
        while($i <= $value || $x) {
            if(!isset($data[$key][$i-1])) {
                $new_data[$key][] = $new_data[$key][$a-1];
                $a++;
            }elseif($data[$key][$i-1]['es_id'] == 1){
                $new_data[$key][] = [
                    'em_id' => $data[$key][$i-1]['em_id'],
                    'es_id' => $data[$key][$i-1]['es_id'],
                    'route' => $data[$key][$i-1]['main_route'],
                    'em_name' => $data[$key][$i-1]['em_name'],
                    'em_surname' => $data[$key][$i-1]['em_surname'],
                    'em_queue' => $data[$key][$i-1]['em_queue'],
                    'new_queue' => $key.'-2-'.($i),
                ];
                unset($data[$key][$i-1]);

            }

            if (!empty($new_data[$key]) && count($new_data[$key]) >= $value) {
            $x = false;
        }
            
            $i++;
        }
    }


    $notredy = groupByRouteWithNewQueue($data);

    return[$new_data, $notredy];

}

/**
 * สร้างแผนใหม่จากข้อมูล route และ main ตามเงื่อนไขที่กำหนด
 *
 * @param array $re ตาราง route ที่ต้องจัดแผน (array ของ route => array)
 * @param array $main ตารางหลักสำหรับ filter และ update (array ของข้อมูลพนักงาน)
 * @param array $main_re ตารางหลักสำหรับค้นหา em_queue (array ของข้อมูลพนักงาน)
 * @param array $normal_code รหัสที่ถือว่าเป็นปกติ (array ของรหัส)
 * @return array $new_plan แผนใหม่ที่สร้างขึ้น
 */
function groupMainDriver($re, $main, $main_re, $normal_code) {

    // print_r($re);
    $new_plan = [];

    foreach ($re as $r_key => $route_values) {
        $re_count = count($route_values);
        $j = 1;
        $x = 1;
        $last = true;
        $last_id = null;
        $last_on = null;

        // วนลูปตามจำนวนสมาชิกใน $re[$r_key]
        while ($j <= $re_count) {
            $re_value = $route_values[$j - 1];
            $type = [1, 2];
            
            
            if (in_array($re_value, $type) && !empty($main)) {

                $filtered = array_filter($main, function($item) use ($r_key, $re_value) {
                    return $item['route_queue'] == $r_key && $item['es_id'] == '1' && $item['bt_id'] == $re_value;
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
                        'licen' => $first['bi_licen'],
                        'em_queue' => $first['em_queue'],
                        'new_queue' => $r_key . '-3-' . ($j),
                    ];
                    unset($main[$firstKey]);
                    $main = array_values($main);
                    if ($j == $re_count) {
                        $new_plan[$r_key][count($new_plan[$r_key])]['new_queue'] = $r_key . '-3-last';
                    }
                } else {

                    if(!empty($new_plan[$r_key])){

                        if ($last && isset($new_plan[$r_key][$x])) {
                            $new_plan[$r_key][count($new_plan[$r_key])]['new_queue'] = $r_key . '-3-last';
                            $last_id = count($new_plan[$r_key]);
                            $last_on = $j;
                            $last = false;
                        }
                        if (isset($new_plan[$r_key][$x])) {
                            $new_plan[$r_key][$j] = $new_plan[$r_key][$x];
                            $new_plan[$r_key][$j]['new_queue'] = $r_key . '-3-' . ($j+1);
                            $x++;
                        }
                    }else{
                        $new_plan[$r_key][$j] = [
                        'em_id' => 'ไม่พบข้อมูล',
                        'es_id' => 'ไม่พบข้อมูล',
                        'route' => 'ไม่พบข้อมูล',
                        'em_name' => 'ไม่พบข้อมูล',
                        'em_surname' => 'ไม่พบข้อมูล',
                        'car' => 'ไม่พบข้อมูล',
                        'licen' => 'ไม่พบข้อมูล',
                        'em_queue' => 'ไม่พบข้อมูล',
                        'new_queue' => $r_key . '-3-' . ($j),
                    ];

                    }


                }
            } else {
                // หา em_queue ใน main_re แล้วดึงข้อมูลมา
                $code_queue = '3';
                if (is_array($re_value) && isset($re_value[2]) && !in_array($re_value[2], $normal_code)) {
                    $code_queue = $re_value[2];
                }
                // หา index ของ em_queue ที่ตรงกับ $re_value ใน $main_re
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
                        'licen' => $emp['bi_licen'],
                        'em_queue' => $emp['em_queue'],
                        'new_queue' => $r_key . '-' . ($code_queue) . '-' . ($j),
                    ];
                    if ($j == $re_count) {
                        $new_plan[$r_key][count($new_plan[$r_key])]['new_queue'] = $r_key . '-3-last';
                        if(isset($last_id) && isset($last_on)) {
                            $new_plan[$r_key][$last_id]['new_queue'] = $r_key . '-3-'.($last_on);
                        }
                    }
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
                            'licen' => $first['bi_licen'],
                            'em_queue' => $first['em_queue'],
                            'new_queue' => $r_key . '-3-' . ($j),
                        ];
                        unset($main[$firstKey]);
                        $main = array_values($main);
                        if ($j == $re_count) {
                            $new_plan[$r_key][count($new_plan[$r_key])]['new_queue'] = $r_key . '-3-last';
                        }
                    } else {
                        if ($last && isset($new_plan[$r_key][$x])) {
                            $new_plan[$r_key][count($new_plan[$r_key])]['new_queue'] = $r_key . '-3-last';
                            $last = false;
                        }
                        if (isset($new_plan[$r_key][$x])) {
                            $new_plan[$r_key][$j] = $new_plan[$r_key][$x];
                            $new_plan[$r_key][$j]['new_queue'] = $r_key . '-3-' . ($j);
                            $x++;
                        }
                    }
                }
            }
            $j++;
        }
    }


    
    return [$new_plan,$main,$x];
}



function groupByRouteWithNewQueue($source, $target = []) {
    foreach ($source as $key => $routeArr) {
            if (isset($routeArr['main_route'])) {
                $items = [$routeArr];
            } else {
                $items = $routeArr;
            }
            
            foreach ($items as $item) {
                $route = $item['main_route'];
                $num = !empty($target[$key]) ? count($target[$key]) + 1 : 1;
                $target[$key][] = [
                    'em_id' => $item['em_id'],
                    'es_id' => $item['es_id'],
                    'main_route' => $item['main_route'],
                    'em_name' => $item['em_name'],
                    'em_surname' => $item['em_surname'],
                    'main_car' => $item['main_car'],
                    'em_queue' => $item['em_queue'],
                    'new_queue' => $key . '-1-' . $num,
                ];
            }
        }


        return $target;
}

function getEmployeeData($conn, $br_id) {
        $sql = "SELECT 
                bp.bp_id AS id,
                CONCAT(loS.locat_name_th, ' - ', loE.locat_name_th) AS route,
                br.br_id AS br_id,
                bi.bi_licen AS licen,
                emM.em_id AS emM_id,
                CONCAT(emM.em_name, ' ', emM.em_surname) AS emM,
                emM.em_queue AS emM_que,
                CONCAT(emX1.em_name, ' ', emX1.em_surname) AS emX1,
                emX1.em_queue AS emX1_que,
                CONCAT(emX2.em_name, ' ', emX2.em_surname) AS emX2,
                emX2.em_queue AS emX2_que,
                CONCAT(emC.em_name, ' ', emC.em_surname) AS emC,
                emC.em_queue AS emC_que
            FROM 
                bus_plan AS bp
            LEFT JOIN 
                bus_routes AS br ON bp.br_id = br.br_id
            LEFT JOIN 
                location AS loS ON br.br_start = loS.locat_id
            LEFT JOIN
                location AS loE ON br.br_end = loE.locat_id
            LEFT JOIN 
                bus_group AS bg ON bp.bg_id = bg.gb_id
            LEFT JOIN 
                bus_info AS bi ON bg.bi_id = bi.bi_id
            LEFT JOIN 
                employee AS emM ON bg.main_dri = emM.em_id
            LEFT JOIN
                employee AS emX1 ON bg.ex_1 = emX1.em_id
            LEFT JOIN 
                employee AS emX2 ON bg.ex_2 = emX2.em_id
            LEFT JOIN 
                employee AS emC ON bg.coach = emC.em_id
            WHERE 
                emM.main_route =  $br_id
                AND bp.bp_id > (
                    SELECT IFNULL(MIN(t.bp_id), 0)
                    FROM (
                        SELECT bp.bp_id
                        FROM bus_plan bp
                        LEFT JOIN bus_group bg ON bp.bg_id = bg.gb_id
                        LEFT JOIN employee emM ON bg.main_dri = emM.em_id
                        WHERE emM.main_route =  $br_id
                        ORDER BY bp.bp_id DESC
                        LIMIT 1 OFFSET 5
                    ) AS t
                )
            ORDER BY bp.bp_id ASC;";
    $result_plan = mysqli_query($conn, $sql);

    $sql_main = "SELECT * FROM `employee` WHERE et_id = 1 AND em_queue < '3-1' AND main_route =  $br_id ";
    $sql_ex = "SELECT * FROM `employee` WHERE et_id = 2 AND em_queue < '2-1' AND main_route =  $br_id ";
    $sql_coach = "SELECT * FROM `employee` WHERE et_id = 3 AND em_queue < '2-1' AND main_route =  $br_id ";
    $result_main = mysqli_query($conn, $sql_main);
    $result_ex = mysqli_query($conn, $sql_ex);  
    $result_coach = mysqli_query($conn, $sql_coach);

    return [$result_plan,$result_main,$result_ex,$result_coach];
}

?>