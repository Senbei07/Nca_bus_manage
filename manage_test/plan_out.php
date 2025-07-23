<?php
    include 'config.php';


    if (!$conn) {
        die("Connection failed: " . mysqli_connect_error());
    };

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
    emM.main_route > 1
    AND bp.bp_id > (
        SELECT
            IFNULL(MIN(t.bp_id), 0)
        FROM (
            SELECT bp_id
            FROM bus_plan
            ORDER BY bp_id DESC
            LIMIT 1 OFFSET 9
        ) AS t
    )
ORDER BY bp.bp_id ASC;";

    $result = mysqli_query($conn, $sql);
    if (!$result) {
        die("Query failed: " . mysqli_error($conn));
    }

    $sql_main = "SELECT * FROM `employee`
                    WHERE main_route > 1
                    AND et_id = 1
                    AND (
                            (em_queue LIKE '2-%' AND em_queue < '2-3-1')
                        OR (em_queue LIKE '3-%' AND em_queue < '3-3-1')
                        OR (em_queue LIKE '4-%' AND em_queue < '4-3-1')
                    )
                    ORDER BY em_queue";
    $sql_ex = "SELECT * FROM `employee` WHERE main_route > 1 AND et_id = 2 AND (
                            (em_queue LIKE '2-%' AND em_queue < '2-2-1')
                        OR (em_queue LIKE '3-%' AND em_queue < '3-2-1')
                        OR (em_queue LIKE '4-%' AND em_queue < '4-2-1')
                    )";
    $sql_coach = "SELECT * FROM `employee` WHERE main_route > 1 AND et_id = 3 AND (
                            (em_queue LIKE '2-%' AND em_queue < '2-2-1')
                        OR (em_queue LIKE '3-%' AND em_queue < '3-2-1')
                        OR (em_queue LIKE '4-%' AND em_queue < '4-2-1')
                    )";

    $result_main = mysqli_query($conn, $sql_main);
    $result_ex = mysqli_query($conn, $sql_ex);  
    $result_coach = mysqli_query($conn, $sql_coach);

    // Group data by route
    $route_groups = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $route_groups[$row['route']][] = $row;
    }

    foreach ($route_groups as $route => $rows) {
        echo "<h3>เส้นทาง: {$route}</h3>";
        echo "<table border='1' cellpadding='5' cellspacing='0'>";
        echo "<tr>
                <th>#</th>
                <th>emM_id</th>
                <th>route</th>
                <th>br_id</th>
                <th>ทะเบียน</th>
                <th>พขร</th>
                <th>ex1</th>
                <th>ex2</th>
                <th>coach</th>
            </tr>";
        $i = 1;
        foreach ($rows as $row) {
            echo "<tr>
                    <td>{$i}</td>
                    <td>{$row['emM_id']}</td>
                    <td>{$row['route']}</td>
                    <td>{$row['br_id']}</td>
                    <td>{$row['licen']}</td>
                    <td>{$row['emM']} ({$row['emM_que']})</td>
                    <td>{$row['emX1']} ({$row['emX1_que']})</td>
                    <td>{$row['emX2']} ({$row['emX2_que']})</td>
                    <td>{$row['emC']} ({$row['emC_que']})</td>
                </tr>";
            $i++;
        }
        echo "</table><br>";
    }
    // พขร พัก
    echo "<h3>พขร พัก</h3>";
    echo "<table border='1' cellpadding='5'><tr><th>emM_id</th><th>ชื่อ</th><th>คิว</th></tr>";
    while ($row_main = mysqli_fetch_assoc($result_main)) {
        echo "<tr><td>{$row_main['em_id']}</td><td>{$row_main['em_name']} {$row_main['em_surname']}</td><td>{$row_main['em_queue']}</td></tr>";
    }
    echo "</table><br>";

    // สำรอง พัก
    echo "<h3>สำรอง พัก</h3>";
    echo "<table border='1' cellpadding='5'><tr><th>ชื่อ</th><th>คิว</th></tr>";
    while ($row_ex = mysqli_fetch_assoc($result_ex)) {
        echo "<tr><td>{$row_ex['em_name']} {$row_ex['em_surname']}</td><td>{$row_ex['em_queue']}</td></tr>";
    }
    echo "</table><br>";

    // โค้ช พัก
    echo "<h3>โค้ช พัก</h3>";
    echo "<table border='1' cellpadding='5'><tr><th>ชื่อ</th><th>คิว</th></tr>";
    while ($row_coach = mysqli_fetch_assoc($result_coach)) {
        echo "<tr><td>{$row_coach['em_name']} {$row_coach['em_surname']}</td><td>{$row_coach['em_queue']}</td></tr>";
    }
    echo "</table><br>";
    
?>