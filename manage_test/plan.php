<?php
    include 'config.php';
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>แผนเดินรถ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; }
        .table th, .table td { vertical-align: middle; }
        .section-title { margin-top: 2rem; margin-bottom: 1rem; }
        .rest-section { background: #fff3cd; border-radius: 8px; padding: 1rem; margin-bottom: 1rem; }
    </style>
</head>
<body>
<div class="container py-4">
    <h1 class="mb-4 text-center">แผนเดินรถ</h1>
    <div class="table-responsive">
        <table class="table table-bordered table-striped align-middle">
            <thead class="table-dark">
                <tr>
                    <th>#</th>
                    <th>ID</th>
                    <th>เส้นทาง</th>
                    <th>รหัสเส้นทาง</th>
                    <th>ทะเบียนรถ</th>
                    <th>พขร หลัก</th>
                    <th>Ex1</th>
                    <th>Ex2</th>
                    <th>Coach</th>
                </tr>
            </thead>
            <tbody>
<?php
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
    emM.main_route = 1
    AND bp.bp_id > (
        SELECT
            IFNULL(MIN(t.bp_id), 0)
        FROM (
            SELECT bp_id
            FROM bus_plan
            ORDER BY bp_id DESC
            LIMIT 1 OFFSET 5
        ) AS t
    )
ORDER BY bp.bp_id ASC;";

    $result = mysqli_query($conn, $sql);
    if (!$result) {
        die("<tr><td colspan='9'>Query failed: " . mysqli_error($conn) . "</td></tr>");
    }

    $sql_main = "SELECT * FROM `employee` WHERE et_id = 1 AND em_queue < '3-1' AND main_route = 1 ";
    $sql_ex = "SELECT * FROM `employee` WHERE et_id = 2 AND em_queue < '2-1' AND main_route = 1 ";
    $sql_coach = "SELECT * FROM `employee` WHERE et_id = 3 AND em_queue < '2-1' AND main_route = 1 ";

    $result_main = mysqli_query($conn, $sql_main);
    $result_ex = mysqli_query($conn, $sql_ex);  
    $result_coach = mysqli_query($conn, $sql_coach);

    $i = 1;
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<tr>
                <td>{$i}</td>
                <td>{$row['id']}</td>
                <td>{$row['route']}</td>
                <td>{$row['br_id']}</td>
                <td>{$row['licen']}</td>
                <td>{$row['emM']} <span class='badge bg-primary'>{$row['emM_que']}</span></td>
                <td>{$row['emX1']} <span class='badge bg-secondary'>{$row['emX1_que']}</span></td>
                <td>{$row['emX2']} <span class='badge bg-secondary'>{$row['emX2_que']}</span></td>
                <td>{$row['emC']} <span class='badge bg-warning text-dark'>{$row['emC_que']}</span></td>
            </tr>";
        $i++;
    }
?>
            </tbody>
        </table>
    </div>

    <div class="section-title h4">พขร พัก</div>
    <div class="rest-section row">
        <?php
        while ($row_main = mysqli_fetch_assoc($result_main)) {
            echo "<div class='col-md-3 mb-2'><span class='fw-bold'>{$row_main['em_name']} {$row_main['em_surname']}</span> <span class='badge bg-primary'>{$row_main['em_queue']}</span></div>";
        }
        ?>
    </div>

    <div class="section-title h4">สำรอง พัก</div>
    <div class="rest-section row">
        <?php
        while ($row_ex = mysqli_fetch_assoc($result_ex)) {
            echo "<div class='col-md-3 mb-2'><span class='fw-bold'>{$row_ex['em_name']} {$row_ex['em_surname']}</span> <span class='badge bg-secondary'>{$row_ex['em_queue']}</span></div>";
        }
        ?>
    </div>

    <div class="section-title h4">โค้ช พัก</div>
    <div class="rest-section row">
        <?php
        while ($row_coach = mysqli_fetch_assoc($result_coach)) {
            echo "<div class='col-md-3 mb-2'><span class='fw-bold'>{$row_coach['em_name']} {$row_coach['em_surname']}</span> <span class='badge bg-warning text-dark'>{$row_coach['em_queue']}</span></div>";
        }
        ?>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
