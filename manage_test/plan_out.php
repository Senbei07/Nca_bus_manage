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
            WHERE emM.main_route > 1";

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
                    )";
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

    $i = 1;
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
        echo "<tr>
                <td>{$i}</td>
                <td>{$row['id']}</td>
                <td>{$row['route']}</td>
                <td>{$row['br_id']}</td>
                <td>{$row['licen']}</td>
                <td>{$row['emM']} ({$row['emM_que']})</td>
                <td>{$row['emX1']} ({$row['emX1_que']})</td>
                <td>{$row['emX2']} ({$row['emX2_que']})</td>
                <td>{$row['emC']} ({$row['emC_que']})</td>
            </tr><br>";

        $i++;
        if(($i-1) % 3 == 0) {
            echo "<br>----------------------------------------------------------------------------------------------<br>";
        }
    }
    echo "<br>พขร พัก <br>";
    while ($row_main = mysqli_fetch_assoc($result_main)) {
        
        echo "<td>{$row_main['em_name']} {$row_main['em_surname']} ({$row_main['em_queue']})</td><br>";
         echo "<br>----------------------------------------------------------------------------------------------<br>";
    }
       echo "<br>สำรอง พัก <br>";
    while ($row_ex = mysqli_fetch_assoc($result_ex)) {
     
        echo "<td>{$row_ex['em_name']} {$row_ex['em_surname']} ({$row_ex['em_queue']})</td><br>";
         echo "<br>----------------------------------------------------------------------------------------------<br>";
    }
    echo "<br>โค้ช พัก <br>";
    while ($row_coach = mysqli_fetch_assoc($result_coach)) {
        echo "<td>{$row_coach['em_name']} {$row_coach['em_surname']} ({$row_coach['em_queue']})</td><br>";
         echo "<br>----------------------------------------------------------------------------------------------<br>";
    }
    
?>