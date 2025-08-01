<?php
include 'config.php';
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>สรุปแผนออกเดินรถ</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #e0e7ff 0%, #f8fafc 100%); min-height: 100vh; }
        .container { margin-top: 40px; margin-bottom: 40px; }
        .modern-card {
            background: #fff;
            border-radius: 1.2rem;
            box-shadow: 0 4px 24px 0 rgba(0,0,0,0.08);
            padding: 2rem 1.5rem;
            margin-bottom: 2.5rem;
            transition: box-shadow 0.2s;
        }
        .modern-card:hover {
            box-shadow: 0 8px 32px 0 rgba(0,0,0,0.14);
        }
        h1 {
            font-weight: 700;
            letter-spacing: 1px;
        }
        h3.section-title {
            margin-top: 0;
            margin-bottom: 1.5rem;
            color: #2563eb;
            font-weight: 600;
            letter-spacing: 0.5px;
        }
        .table {
            border-radius: 0.7rem;
            overflow: hidden;
        }
        .table thead th {
            background: #2563eb;
            color: #fff;
            border: none;
        }
        .badge {
            font-size: 1em;
            padding: 0.5em 0.8em;
            border-radius: 0.7em;
        }
        .badge.bg-primary { background: #2563eb !important; }
        .badge.bg-secondary { background: #64748b !important; }
        .badge.bg-warning { background: #facc15 !important; color: #333 !important; }
        @media (max-width: 767.98px) {
            .container { margin-top: 15px; margin-bottom: 15px; }
            h1 { font-size: 1.3rem; }
            h3.section-title { font-size: 1rem; }
            .modern-card { padding: 1rem 0.5rem; }
            .table th, .table td { font-size: 0.85rem; padding: 0.4rem; }
        }
        @media (max-width: 575.98px) {
            .table-responsive { font-size: 0.8rem; }
            .table th, .table td { padding: 0.3rem; }
            .modern-card { padding: 0.7rem 0.2rem; }
        }
    </style>
</head>
<body>
<div class="container">
    <h1 class="mb-4 text-center text-primary">สรุปแผนออกเดินรถ</h1>
<?php
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
};

$route = [2,3,4];
// Convert PHP array to a string for SQL IN clause: (3,4,5)
$route_in = '(' . implode(',', $route) . ')';

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
    emM.main_route IN $route_in
    AND bp.bp_id > (
        SELECT IFNULL(MIN(t.bp_id), 0)
        FROM (
            SELECT bp.bp_id
            FROM bus_plan bp
            LEFT JOIN bus_group bg ON bp.bg_id = bg.gb_id
            LEFT JOIN employee emM ON bg.main_dri = emM.em_id
            WHERE emM.main_route IN $route_in
            ORDER BY bp.bp_id DESC
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
    echo "<div class='modern-card'>";
    echo "<h3 class='section-title'>เส้นทาง: {$route}</h3>";
    echo "<div class='table-responsive'><table class='table table-bordered table-striped align-middle mb-0'>";
    echo "<thead><tr>
            <th>#</th>
            <th>emM_id</th>
            <th>เส้นทาง</th>
            <th>br_id</th>
            <th>ทะเบียน</th>
            <th>พขร</th>
            <th>ex1</th>
            <th>ex2</th>
            <th>coach</th>
        </tr></thead><tbody>";
    $i = 1;
    foreach ($rows as $row) {
        echo "<tr>
                <td>{$i}</td>
                <td>{$row['emM_id']}</td>
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
    echo "</tbody></table></div></div>";
}
// พขร พัก
echo "<div class='modern-card'>";
echo "<h3 class='section-title'>พขร พัก</h3>";
echo "<div class='table-responsive'><table class='table table-bordered table-striped align-middle mb-0'><thead><tr><th>emM_id</th><th>ชื่อ</th><th>คิว</th></tr></thead><tbody>";
while ($row_main = mysqli_fetch_assoc($result_main)) {
    echo "<tr><td>{$row_main['em_id']}</td><td>{$row_main['em_name']} {$row_main['em_surname']}</td><td><span class='badge bg-primary'>{$row_main['em_queue']}</span></td></tr>";
}
echo "</tbody></table></div></div>";

// สำรอง พัก
echo "<div class='modern-card'>";
echo "<h3 class='section-title'>สำรอง พัก</h3>";
echo "<div class='table-responsive'><table class='table table-bordered table-striped align-middle mb-0'><thead><tr><th>ชื่อ</th><th>คิว</th></tr></thead><tbody>";
while ($row_ex = mysqli_fetch_assoc($result_ex)) {
    echo "<tr><td>{$row_ex['em_name']} {$row_ex['em_surname']}</td><td><span class='badge bg-secondary'>{$row_ex['em_queue']}</span></td></tr>";
}
echo "</tbody></table></div></div>";

// โค้ช พัก
echo "<div class='modern-card'>";
echo "<h3 class='section-title'>โค้ช พัก</h3>";
echo "<div class='table-responsive'><table class='table table-bordered table-striped align-middle mb-0'><thead><tr><th>ชื่อ</th><th>คิว</th></tr></thead><tbody>";
while ($row_coach = mysqli_fetch_assoc($result_coach)) {
    echo "<tr><td>{$row_coach['em_name']} {$row_coach['em_surname']}</td><td><span class='badge bg-warning text-dark'>{$row_coach['em_queue']}</span></td></tr>";
}
echo "</tbody></table></div></div>";

?>
</div>
</body>
</html>