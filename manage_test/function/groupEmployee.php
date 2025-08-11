<?php

/**
 * ดึงข้อมูลพนักงานหลักและแผนการเดินรถจากฐานข้อมูล
 * 
 * @param mysqli $conn การเชื่อมต่อฐานข้อมูล
 * @param array $route ตารางเส้นทางที่ต้องการ (array ของ route => array)
 */
function getMainDriver($conn, $route, $date) {
    $route_in = '(' . implode(',', $route) . ')';

    $sql_main = "SELECT 
            *,
            los.locat_id as locat_id_start,
            loe.locat_id as locat_id_end
        FROM `employee` AS em 
        LEFT JOIN bus_info AS bi ON em.main_car = bi.bi_id 
        LEFT JOIN bus_routes AS br ON em.main_route = br.br_id
        LEFT JOIN location AS los ON br.br_start = los.locat_id
        LEFT JOIN location AS loe ON br.br_end = loe.locat_id
        WHERE main_route IN $route_in  
        AND et_id = 1 
        ORDER BY em_queue";
    $result_main = mysqli_query($conn, $sql_main);

    $sql_re = "SELECT 
            pr.pr_id AS pr_id,
            pr.br_id AS br_id,
            qr.qr_return  AS qr_return, 
            pr.pr_request  AS pr_request,
            pr.pr_date AS pr_date,
            qr.br_go AS br_go

            FROM `plan_request` AS pr LEFT JOIN queue_request AS qr ON pr.br_id = qr.br_id WHERE pr_date = '$date' AND pr_status = 1;";
    $result_re = mysqli_query($conn, $sql_re);

    $queue = [];
    $re = [];
    $re_break = [];
    $return_request = [];
    $goto = [];
    $break_key = [];
    $time = [];
    $pr_ids = [];
    $ex_request = [];
    $coach_request = [];
    while ($row = mysqli_fetch_assoc($result_re)) {
        $pr_ids[$row['br_id']] = $row['pr_id'];
        $pr_request = json_decode($row['pr_request'], true);
        $goto[$row['br_id']] = $row['br_go'];



        foreach ($pr_request['request'] as $v) {
            if ($v !== "0") $queue[] = $v;
            $re[$row['br_id']][] = $v;
        }
        foreach ($pr_request['reserve'] as $v) {
            if ($v !== "0") {
                $re_break[] = $v;
                $break_key[$v][] = $row['br_id'];
            }
        }
        foreach ($pr_request['time'] as $key => $v) {
            $time[$row['br_id']][] = [
                'date' => $row['pr_date'],
                'time' => $v,
                'time_plus' => $pr_request['time_plus'][$key] ?? '90'
            ];

            $ex_time[] = $v;

        }


        foreach ($pr_request['ex'] as $ex) {
            $time2 = array_shift($ex_time);
            $ex_request[] = [
                'br_id' => $row['br_id'],
                'br_group' => [$row['br_id']],
                'goto' => $row['br_go'],
                'date' => $row['pr_date'],
                'time' => $time2,
                'time_plus' => $pr_request['time_plus'][$key],
                "ex_start1" => $ex['start1'] ?? [],
                "ex_end1" => $ex['end1'] ?? [],
                "ex_start2" => $ex['start2'] ?? [],
                "ex_end2" => $ex['end2'] ?? [],
            ];

            if($row['br_id'] == 1) {
                $start  = "1";
                $end  = "10";
            }elseif($row['br_id'] == 2) {
                $start  = "10";
                $end  = "1";
            }elseif($row['br_id'] == 3) {
                $start  = "11";
                $end  = "20";
            }elseif($row['br_id'] == 4) {
                $start  = "20";
                $end  = "11";
            }

            if($start == "1"){

            }

            $coach_request[] = [
                'br_id' => $row['br_id'],
                'br_group' => ($start == "1") ? [1,3,4] : [$row['br_id']],
                'goto' => $row['br_go'],
                'date' => $row['pr_date'],
                'time' => $time2,
                'time_plus' => $pr_request['time_plus'][$key],
                "coach_start" => $start,
                "coach_end" => $end,
            ];

        }
        

        usort($ex_request, function($a, $b) {
            return $a['time'] <=> $b['time'];
        });
        usort($coach_request, function($a, $b) {
            return $a['time'] <=> $b['time'];
        });

        $qr_return = json_decode($row['qr_return'], true);
        foreach ($qr_return as $v) {
            if ($v !== "0") {
                $return_request[$row['br_id']][] = $v;
            }
        }
    }


    // print_r($time);

    $route_name = [];
    $main_re = [];
    $break = [];
    $main = [];

    while ($row_main = mysqli_fetch_assoc($result_main)) {
        if (!in_array($row_main['main_route'], $route_name)) {
            $route_name[] = $row_main['main_route'];
        }
        if (in_array($row_main['em_queue'], $queue)) {
            $main_re[] = $row_main;
        } elseif (in_array($row_main['em_queue'], $re_break)) {
            $key = $break_key[$row_main['em_queue']][0];
            $break[$key][] = $row_main;
        } else {
            $row_main['route_queue'] = mb_substr($row_main['em_queue'], 0, 1);
            $main[] = $row_main;
        }
    }



    return [$goto, $re, $main, $main_re, $break, $return_request, $time, $pr_ids, $ex_request, $coach_request];
}


/**
 * ดึงข้อมูลพนักงานตามเส้นทางและจัดกลุ่มตามประเภท
 * 
 * @param mysqli $conn การเชื่อมต่อฐานข้อมูล
 * @param array $route ตารางเส้นทางที่ต้องการ (array ของ route => array)
 * @param array $goto ปลายทางของพนักงาน (array ของ route => go)
 * @param array $return_request ข้อมูลการดึงรถ (array ของ route => array)
 * @param array $time เวลาที่ต้องการ (array ของ route => time)
 * @param array $ex_request ข้อมูลพนักงานพ่วง (array ของ route => array)
 * @param string $type ประเภทของพนักงาน ('extra' หรือ 'coach')
 */
  
function getEmpData($conn, $route, $goto, $return_request, $time, $ex_request, $type) {
    $route_in = '(' . implode(',', $route) . ')';
    $et_id = $type === 'coach' ? 3 : 2;
    $group_keys = $type === 'coach' ? ['coach'] : ['ex1', 'ex2'];
    $sql = "SELECT * FROM `employee` WHERE main_route IN $route_in AND et_id = $et_id ORDER BY em_timeOut";
    $result = mysqli_query($conn, $sql);

    $data = [];
    $ex_data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $ex_data[$row['em_queue']][] = [
            'em_id' => $row['em_id'],
            'es_id' => $row['es_id'],
            'main_route' => $row['main_route'],
            'goto' => $goto[$row['main_route']],
            'em_name' => $row['em_name'],
            'em_surname' => $row['em_surname'],
            'main_car' => $row['main_car'],
            'em_queue' => $row['em_queue'],
            'new_queue' => $row['em_queue'],
            'em_timeOut' => $row['em_timeOut'],
        ];
    }
    if(isset($ex_request)){
        $return = [];
        foreach($ex_request as $v){
            $results = [];
            $keys = [];
            $dataempty = [];
            // กรองข้อมูล
            foreach ($group_keys as $i => $key) {
                $start_key = $type === 'coach' ? 'coach_start' : ($i == 0 ? 'ex_start1' : 'ex_start2');
                $end_key   = $type === 'coach' ? 'coach_end'   : ($i == 0 ? 'ex_end1'   : 'ex_end2');
                $dataempty[$start_key] = false;
                $results[$key] = null; $keys[$key] = null;
                if(!empty($v[$start_key])) {
                    list($results[$key], $keys[$key]) = getFilteredStart($v, $ex_data, $start_key);
                    if($results[$key] == null) {
                        $dataempty[$start_key] = true;
                    }
                    unset($ex_data[$v[$start_key]][$keys[$key]]);
                }
            }
            // เวลาต่างๆ
            $date = $v['date'];
            $time = $v['time'];
            $time_plus = $v['time_plus'];
            $datetime = new DateTime("$date $time");
            $datetime->modify("+{$time_plus} minutes");
            $end_date = $datetime->format('Y-m-d');
            $end_time = $datetime->format('H:i:s');
            // จัด data
            foreach ($group_keys as $i => $key) {
                $start_key = $type === 'coach' ? 'coach_start' : ($i == 0 ? 'ex_start1' : 'ex_start2');
                $end_key   = $type === 'coach' ? 'coach_end'   : ($i == 0 ? 'ex_end1'   : 'ex_end2');
                $result = $results[$key];
                $data[$v['br_id']][$key][] = [
                    'em_id'       => empty($v[$start_key]) ? "" : ($result['em_id'] ?? 'ไม่พบข้อมูล'),
                    'es_id'       => empty($v[$start_key]) ? "" : $result['es_id'] ?? 'ไม่พบข้อมูล',
                    'main_route'  => $v['br_id'],
                    'goto'        => $v['goto'],
                    'date_start'  => $date,
                    'time_start'  => $time,
                    'date_end'    => $end_date,
                    'time_end'    => $end_time,
                    'em_name'     => empty($v[$start_key]) ? "ไม่ใช้งาน" : ($result['em_name'] ?? 'ไม่พบข้อมูล'),
                    'em_surname'  => empty($v[$start_key]) ? "" : ($result['em_surname'] ?? 'ไม่พบข้อมูล'),
                    'main_car'    => empty($v[$start_key]) ? "" : ($result['main_car'] ?? 'ไม่พบข้อมูล'),
                    'em_queue'    => empty($v[$start_key]) ? "" : (empty($result) ? "0" : $v[$start_key]),
                    'em_new_queue'=> empty($v[$end_key]) ? "" : (empty($result) ? "0" : $v[$end_key]),
                    'em_timeOut'  => $v['time'],
                ];
            }
            // สร้าง redata
            foreach ($group_keys as $i => $key) {
                $start_key = $type === 'coach' ? 'coach_start' : ($i == 0 ? 'ex_start1' : 'ex_start2');
                $end_key   = $type === 'coach' ? 'coach_end'   : ($i == 0 ? 'ex_end1'   : 'ex_end2');
                $result    = $results[$key];
                $ex_type   = $key;
                $em_new_queue = (!empty($v[$end_key]) && !empty($result)) ? $v[$end_key] : "0";
                $redata[$em_new_queue][] = [
                    'ex_type'      => $ex_type,
                    'em_id'        => $result['em_id'] ?? 'ไม่พบข้อมูล',
                    'es_id'        => $result['es_id'] ?? 'ไม่พบข้อมูล',
                    'br_id'        => $v['br_id'],
                    'goto'         => $v['goto'],
                    'date_start'   => $date,
                    'time_start'   => $time,
                    'date_end'     => $end_date,
                    'time_end'     => $end_time,
                    'em_name'      => $result['em_name'] ?? 'ไม่พบข้อมูล',
                    'em_surname'   => $result['em_surname'] ?? 'ไม่พบข้อมูล',
                    'main_car'     => $result['main_car'] ?? 'ไม่พบข้อมูล',
                    'em_queue'     => (!empty($v[$start_key])) ? $v[$start_key] : "0",
                    'em_new_queue' => $em_new_queue,
                    'em_timeOut'   => $v['time'],
                ];
            }
            // return เฉพาะกรณีข้อมูลหาย
            $start_key = $type === 'coach' ? 'coach_start' :'ex_start1';
            $start_key2 = $type === 'coach' ? false :'ex_start2';
            if ($dataempty[$start_key] || ($start_key2 ? $dataempty[$start_key2] : false)) {
                $returnItem = [
                    'br_id'    => $v['br_id'],
                    'goto'     => $v['goto'],
                    'date'     => $v['date'],
                    'time'     => $v['time'],
                    'index'    => count($type === 'coach' ? $data[$v['br_id']] : $data[$v['br_id']]['ex1']) - 1,
                ];
                if ($type === 'coach') {
                    $returnItem['coach_start'] = !empty($v['coach_start']) ? $v['coach_start'] : 0;
                    $returnItem['coach_end']   = !empty($v['coach_end'])   ? $v['coach_end']   : 0;
                }
                if ($type === 'extra') {
                    $returnItem['ex_start1'] = !empty($v['ex_start1']) ? $v['ex_start1'] : 0;
                    $returnItem['ex_end1']   = !empty($v['ex_end1'])   ? $v['ex_end1']   : 0;
                    $returnItem['ex_start2'] = !empty($v['ex_start2']) ? $v['ex_start2'] : 0;
                    $returnItem['ex_end2']   = !empty($v['ex_end2'])   ? $v['ex_end2']   : 0;
                }
                $return[] = $returnItem;
            }
        }
        // จัดการ return ใน data (เหมือนเดิม)
        if(isset($return)){
            foreach ($return as $r) {
                foreach ($group_keys as $i => $key) {
                    $start_key = $type === 'coach' ? 'coach_start' : ($i == 0 ? 'ex_start1' : 'ex_start2');
                    $end_key   = $type === 'coach' ? 'coach_end'   : ($i == 0 ? 'ex_end1'   : 'ex_end2');
                    if($r[$start_key] != 0) {
                        if(isset($redata[$r[$start_key]])) {
                            $filtered = array_filter($redata[$r[$start_key]], function($item) use ($r) {
                                return (
                                    ($item['br_id'] == $r['goto'] || $item['goto'] == $r['goto']) &&
                                    $item['date_end'] <= $r['date'] &&
                                    $item['time_end'] <= $r['time']
                                );
                            });
                            $first = reset($filtered);
                            if($first) {
                                $data[$r['br_id']][$key][$r['index']] = [
                                    'em_id'         => $first['em_id'] ?? 'ไม่พบข้อมูล',
                                    'es_id'         => $first['es_id'] ?? 'ไม่พบข้อมูล',
                                    'main_route'    => $r['br_id'],
                                    'goto'          => $r['goto'],
                                    'date_start'    => $r['date'],
                                    'time_start'    => $r['time'],
                                    'date_end'      => $first['date_end'] ?? null,
                                    'time_end'      => $first['time_end'] ?? null,
                                    'em_name'       => $first['em_name'] ?? 'ไม่พบข้อมูล',
                                    'em_surname'    => $first['em_surname'] ?? 'ไม่พบข้อมูล',
                                    'main_car'      => $first['main_car'] ?? 'ไม่พบข้อมูล',
                                    'em_queue'      => $r[$start_key] ?? "0",
                                    'em_new_queue'  => $r[$end_key] ?? "0",
                                    'em_timeOut'    => $r['time'],
                                ];
                            }
                        }
                    } else {
                        $filtered = $data[$r['br_id']][$key][$r['index']] ?? null;
                    }
                }
            }
        }
        return [$data ?? [], $ex_data, $return ?? []];
    }
}

/**
 * ฟังก์ชันสำหรับกรองข้อมูลพนักงานพ่วงและโค้ชตามเงื่อนไขที่กำหนด
 *
 * @param array $v ข้อมูลพนักงานพ่วงหรือโค้ช
 * @param array $ex_data ข้อมูลพนักงานพ่วงหรือโค้ชที่จัดกลุ่มตาม em_queue
 * @param string $keyName ชื่อคีย์ที่ใช้ในการกรอง (เช่น 'ex_start1', 'ex_start2')
 * @return array|null ข้อมูลที่กรองแล้ว หรือ null หากไม่มีข้อมูลที่ตรงเงื่อนไข
 */
function getFilteredStart($v, $ex_data, $keyName) {
    if (!empty($ex_data[$v[$keyName]]) && is_array($ex_data[$v[$keyName]])) {
        $br = $v['br_group'];
        $filtered = array_filter($ex_data[$v[$keyName]], function($item) use ($br) {
            return isset($item['main_route'], $item['es_id'])
                && (in_array($item['main_route'], $br) || in_array($item['goto'], $br))
                && $item['es_id'] == '1';
        });
        return !empty($filtered)
            ? [reset($filtered),key($filtered)]
            : [null,null];
    }
    return [null,null];
}

// ใช้แบบนี้

/**
 * สร้างแผนใหม่จากข้อมูล route และ main ตามเงื่อนไขที่กำหนด
 *
 * @param array $goto ปลายทางของพนักงาน (array ของ route => go)
 * @param array $re ตาราง route ที่ต้องจัดแผน (array ของ route => array)
 * @param array $main ตารางหลักสำหรับ filter และ update (array ของข้อมูลพนักงาน)
 * @param array $main_re ตารางหลักสำหรับค้นหา em_queue (array ของข้อมูลพนักงาน)
 * @param array $return_request ข้อมูลการดึงรถ (array ของ route => array)
 * @param array $normal_code รหัสที่ถือว่าเป็นปกติ (array ของรหัส)
 */
function groupMainDriver($goto, $re, $main, $main_re, $return_request, $normal_code, $time) {   
    $new_plan = [];
    $return = [];
    $data_end_location = [];
    echo "<script>console.log('re:', " . json_encode($re) . ");</script>";

    foreach ($re as $r_key => $route_values) {
        $re_count = count($route_values);
        $j = 1;
        $x = 1;
        $last = true;
        $last_id = null;
        $last_on = null;

        while ($j <= $re_count) {
            $re_value = $route_values[$j - 1];
            $type = [1, 2];
            $go = $goto[$r_key];

                $date = $time[$r_key][$j - 1]['date'] ?? null;
                $time_value = $time[$r_key][$j - 1]['time'] ?? null;
                $plus = $time[$r_key][$j - 1]['time_plus'] ?? '90';

                $dateend = null;
                $timeend = null;
                if ($date && $time_value) {
                    // รวมวันที่และเวลาเป็น string เดียว
                    $datetime = new DateTime("$date $time_value");

                    // บวกนาที
                    $datetime->add(new DateInterval('PT' . $plus . 'M'));

                    // แยกวันที่และเวลาใหม่ออกมา
                    $dateend = $datetime->format('Y-m-d');
                    $timeend = $datetime->format('H:i:s');
                }

            if (in_array($re_value, $type) && !empty($main)) {
                $filtered = array_filter($main, function($item) use ($re_value, $r_key) {
                    return $item['em_queue'][2] != 3 && $item['route_queue'] == $r_key && $item['es_id'] == '1' && $item['bt_id'] == $re_value;
                });
                if (empty($filtered)) {
                    $filtered = array_filter($main, function($item) use ($go, $re_value) {
                        return $item['route_queue'] == $go && $item['em_queue'][2] == 3 && $item['es_id'] == '1' && $item['bt_id'] == $re_value;
                    });
                }
                $first = reset($filtered);
                $firstKey = key($filtered);

                if ($firstKey !== null && $first !== false) {



                    $new_plan[$r_key][$j] = [
                        'em_id' => $first['em_id'],
                        'es_id' => $first['es_id'],
                        'route' => $first['main_route'],
                        'locat_id_start' => $first['locat_id_start'],
                        'locat_id_end' => $first['locat_id_end'],
                        'em_name' => $first['em_name'],
                        'em_surname' => $first['em_surname'],
                        'car' => $first['main_car'],
                        'bt_id' => $first['bt_id'],
                        'licen' => $first['bi_licen'],
                        'date' => $date,
                        'time' => $time_value,
                        'dateend' => $dateend,
                        'timeend' => $timeend,
                        'em_queue' => $first['em_queue'],
                        'new_queue' => $r_key . '-3-' . ($j),
                    ];
                    unset($main[$firstKey]);
                    $main = array_values($main);
                    if ($j == $re_count) {
                        $new_plan[$r_key][count($new_plan[$r_key])]['new_queue'] = $r_key . '-3-last';
                    }
                } else {
                    $return[] = [
                        'route' => "$r_key",
                        'em_no' => $j,
                        'bt_id' => $re_value,
                        're_return' => $return_request[$r_key],
                        'date' => $date,
                        'time' => $time_value,
                        'dateend' => $dateend,
                        'timeend' => $timeend,
                        'locat_id_start' => $new_plan[$r_key][$x]['locat_id_start'] ?? null,
                    ];
                    $new_plan[$r_key][$j] = [
                        'em_id' => 'ไม่พบข้อมูล',
                        'es_id' => 'ไม่พบข้อมูล',
                        'route' => 'ไม่พบข้อมูล',
                        'locat_id_start' => 'ไม่พบข้อมูล',
                        'locat_id_end' => 'ไม่พบข้อมูล',
                        'em_name' => 'ไม่พบข้อมูล',
                        'em_surname' => 'ไม่พบข้อมูล',
                        'car' => 'ไม่พบข้อมูล',
                        'bt_id' => 'ไม่พบข้อมูล',
                        'licen' => 'ไม่พบข้อมูล',
                        'date' => $date,
                        'time' => $time_value,
                        'dateend' => $dateend,
                        'timeend' => $timeend,
                        'em_queue' => 'ไม่พบข้อมูล',
                        'new_queue' => $r_key . '-3-' . ($j),
                    ];
                }
            } else {
                $code_queue = '3';
                if (is_array($re_value) && isset($re_value[2]) && !in_array($re_value[2], $normal_code)) {
                    $code_queue = $re_value[2];
                }
                $idx = array_search($re_value, array_column($main_re, 'em_queue'));
                if ($idx !== false && isset($main_re[$idx])) {
                    $emp = $main_re[$idx];
                    $new_plan[$r_key][$j] = [
                        'em_id' => $emp['em_id'],
                        'es_id' => $emp['es_id'],
                        'route' => $emp['main_route'],
                        'locat_id_start' => $emp['locat_id_start'],
                        'locat_id_end' => $emp['locat_id_end'],
                        'em_name' => $emp['em_name'],
                        'em_surname' => $emp['em_surname'],
                        'car' => $emp['main_car'],
                        'bt_id' => $emp['bt_id'],
                        'licen' => $emp['bi_licen'],
                        'date' => $date,
                        'time' => $time_value,
                        'dateend' => $dateend,
                        'timeend' => $timeend,
                        'em_queue' => $emp['em_queue'],
                        'new_queue' => $r_key . '-' . ($code_queue) . '-' . ($j),
                    ];
                    if ($j == $re_count) {
                        $new_plan[$r_key][count($new_plan[$r_key])]['new_queue'] = $r_key . '-3-last';
                        if (isset($last_id) && isset($last_on)) {
                            $new_plan[$r_key][$last_id]['new_queue'] = $r_key . '-3-' . ($last_on);
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
                            'locat_id_start' => $first['locat_id_start'],
                            'locat_id_end' => $first['locat_id_end'],
                            'em_name' => $first['em_name'],
                            'em_surname' => $first['em_surname'],
                            'car' => $first['main_car'],
                            'bt_id' => $first['bt_id'],
                            'licen' => $first['bi_licen'],
                            'date' => $date,
                            'time' => $time_value,
                            'dateend' => $dateend,
                            'timeend' => $timeend,
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

    $re_data = $new_plan;

    if (!empty($return)) {
        foreach ($return as $r_value) {
            $re_return_index = 0;

            $re_return_key = $r_value["re_return"][$re_return_index];
            if (isset($re_data[$re_return_key]) && is_array($re_data[$re_return_key])) {
                $data_plan = reset($re_data[$re_return_key]);


            $dateend = $data_plan['dateend'];
            $timeend = $data_plan['timeend'];

            $date = $r_value['date'];
            $time = $r_value['time'];

            // สร้าง DateTime Object สำหรับเวลาสิ้นสุดก่อนหน้า
            $datetime_end = new DateTime("$dateend $timeend");

            // สร้าง DateTime Object สำหรับเวลาเริ่มต้นใหม่
            $datetime_start = new DateTime("$date $time");

            // เปรียบเทียบ
            if ($datetime_end >= $datetime_start) {
                $data_plan = null;
            }


            

                
            } else {
                $data_plan = null;
            }
            $new_data = [
                'em_id' => $data_plan['em_id'] ?? 'ไม่พบข้อมูล',
                'em_name' => $data_plan['em_name'] ?? 'ไม่พบข้อมูล',
                'em_surname' => $data_plan['em_surname'] ?? 'ไม่พบข้อมูล',
                'car' => $data_plan['car'] ?? 'ไม่พบข้อมูล',
                'bt_id' => $data_plan['bt_id'] ?? 'ไม่พบข้อมูล',
                'licen' => $data_plan['licen'] ?? 'ไม่พบข้อมูล',
                'em_queue' => $data_plan['em_queue'] ?? 'ไม่พบข้อมูล',
                'new_queue' => $r_value["route"] . '-3-' . ($r_value["em_no"] + 1),
            ];
            foreach ($new_data as $k => $v) {
                $new_plan[$r_value["route"]][$r_value["em_no"]][$k] = $v;
                $re_data[$r_value["route"]][$r_value["em_no"]][$k] = $v;
            }
            unset($re_data[$r_value["re_return"][0]][$firstKey]);
        }
    }
    return [$new_plan, $main, $x, $return];
}

/**
 * จัดกลุ่มข้อมูลพนักงานพัก และสำรอง
 *
 * @param array $goto ปลายทางของพนักงาน (array ของ route => go)
 * @param array $source ตารางข้อมูลต้นทาง (array ของ route => array)
 * @param array $target ตารางข้อมูลเป้าหมาย (array ของ go => array)
 * @return array
 */
function groupByRouteWithNewQueue($goto, $source, $type , $target = []) {

    foreach ($source as $key => $routeArr) {
        if (isset($routeArr['main_route'])) {
            $items = [$routeArr];
        } else {
            $items = $routeArr;
        }
        $go = $goto[$key];
        if($type == 1){
            $route_key = $go;

        }else{
            $route_key = $key;
        }
        foreach ($items as $item) {

            $num = !empty($target[$route_key]) ? count($target[$route_key])+1 : 1;
            $target[$route_key][] = [
                'em_id' => $item['em_id'],
                'es_id' => $item['es_id'],
                'main_route' => $item['main_route'],
                'em_name' => $item['em_name'],
                'em_surname' => $item['em_surname'],
                'main_car' => $item['main_car'],
                'em_queue' => $item['em_queue'],
                'new_queue' => $route_key. '-1-' . $num,
                'address' => $route_key,
            ];
        }
    }

    return $target;
}

/**
 * ดึงข้อมูลพนักงานและแผนการเดินรถจากฐานข้อมูล
 *
 * @param mysqli $conn การเชื่อมต่อฐานข้อมูล
 * @param int $br_id รหัสเส้นทางที่ต้องการดึงข้อมูล
 */
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
        LEFT JOIN bus_routes AS br ON bp.br_id = br.br_id
        LEFT JOIN location AS loS ON br.br_start = loS.locat_id
        LEFT JOIN location AS loE ON br.br_end = loE.locat_id
        LEFT JOIN bus_group AS bg ON bp.bg_id = bg.gb_id
        LEFT JOIN bus_info AS bi ON bg.bi_id = bi.bi_id
        LEFT JOIN employee AS emM ON bg.main_dri = emM.em_id
        LEFT JOIN employee AS emX1 ON bg.ex_1 = emX1.em_id
        LEFT JOIN employee AS emX2 ON bg.ex_2 = emX2.em_id
        LEFT JOIN employee AS emC ON bg.coach = emC.em_id
        WHERE emM.main_route = $br_id
            AND bp.bp_id > (
                SELECT IFNULL(MIN(t.bp_id), 0)
                FROM (
                    SELECT bp.bp_id
                    FROM bus_plan bp
                    LEFT JOIN bus_group bg ON bp.bg_id = bg.gb_id
                    LEFT JOIN employee emM ON bg.main_dri = emM.em_id
                    WHERE emM.main_route = $br_id
                    ORDER BY bp.bp_id DESC
                    LIMIT 1 OFFSET 5
                ) AS t
            )
        ORDER BY bp.bp_id ASC;";
    $result_plan = mysqli_query($conn, $sql);

    $sql_main = "SELECT * FROM `employee` WHERE et_id = 1 AND em_queue < '3-1' AND main_route = $br_id ";
    $sql_ex = "SELECT * FROM `employee` WHERE et_id = 2 AND em_queue < '2-1' AND main_route = $br_id ";
    $sql_coach = "SELECT * FROM `employee` WHERE et_id = 3 AND em_queue < '2-1' AND main_route = $br_id ";
    $result_main = mysqli_query($conn, $sql_main);
    $result_ex = mysqli_query($conn, $sql_ex);
    $result_coach = mysqli_query($conn, $sql_coach);

    return [$result_plan, $result_main, $result_ex, $result_coach];
}

?>