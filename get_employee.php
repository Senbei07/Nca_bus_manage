<?php
include('config.php');
header('Content-Type: application/json');

if (isset($_GET['route'], $_GET['date'], $_GET['time'], $_GET['id'])) {
    $route = intval($_GET['route']);
    $date = $_GET['date'];
    $time = $_GET['time'];
    $datetime_early = new DateTime($time);
    $datetime_early->sub(new DateInterval('PT1H'));
    $new_time_string_early = $datetime_early->format('H:i:s');
    $id = (intval($_GET['id']) == 1) ? 2 : 1;

    // ดึงข้อมูลพลขับ
    $sql = "SELECT 
                em.em_id, 
                CONCAT(title.title_name, em.em_name, ' ', em.em_surname) AS fullname 
            FROM employee AS em 
            LEFT JOIN title_name AS title ON em.title_id = title.title_id 
            WHERE et_id = 2 AND main_route = $route
            LIMIT 5";
    $result = mysqli_query($conn, $sql);

    $drivers = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $drivers[] = [
            'id' => $row['em_id'],
            'name' => $row['fullname']
        ];
    }

    // ดึงข้อมูล return bus (เปลี่ยนวันที่และ pt_id ให้ใช้ตัวแปรจริง)
    $sql_return = " SELECT
                        dpt.dpt_id AS id,
                        pt.pt_name_th AS pt_name,
                        dpt.dpt_time_end AS time_end,
                        
                        bi.bi_licenseplate AS licen,
                        bi.bi_id AS bi_id,
                        g.main_dri AS main_dri,
                        title.title_name AS title,
                        em.em_name AS name,
                        em.em_surname AS surname
                    FROM
                        dri_plan_t AS dpt
                    LEFT JOIN
                        `group` AS g ON dpt.group = g.group_id
                    LEFT JOIN
                        bus_info AS bi ON g.bi_id = bi.bi_id
                    LEFT JOIN 
                        employee AS em ON g.main_dri = em.em_id
                    LEFT JOIN 
                        title_name as title ON em.title_id = title.title_id
                    LEFT JOIN 
                        plan_type AS pt ON dpt.pt_id = pt.pt_id
                    WHERE
                        dpt_date_end = '$date'
                    AND
                        dpt_time_end < '$new_time_string_early'
                    AND
                        dpt.br_id = $route

                    AND
                        dpt.pt_id = 1
                    LIMIT 5;";

    $result_return = mysqli_query($conn, $sql_return);

    $return_bus = [];
    while ($row = mysqli_fetch_assoc($result_return)) {
        $return_bus[] = $row;
    }

    echo json_encode([
        'drivers' => $drivers,
        'return_bus' => $return_bus
    ]);
    exit;

} else {
    echo json_encode(['error' => 'ข้อมูลไม่ครบ']);
    exit;
}
