<?php
include('config.php');

$perPage = 10;  // จำนวนรายการต่อหน้า
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page - 1) * $perPage;

// ดึงข้อมูลสำหรับ filter (สายรถ, วันที่)
$filter_line = isset($_GET['line']) ? $_GET['line'] : '';
$filter_date = isset($_GET['date']) ? $_GET['date'] : '';

// สร้างเงื่อนไข WHERE แบบง่าย ๆ (ปรับตามความจริงในฐานข้อมูล)
$where = [];
if (!empty($filter_line)) {
    $where[] = "br.br_name LIKE '%" . mysqli_real_escape_string($conn, $filter_line) . "%'";
}
if (!empty($filter_date)) {
    $where[] = "DATE(bi.some_date_column) = '" . mysqli_real_escape_string($conn, $filter_date) . "'";
}
$whereSQL = '';
if (count($where) > 0) {
    $whereSQL = 'WHERE ' . implode(' AND ', $where);
}

// นับจำนวนรวมทั้งหมด เพื่อคำนวณจำนวนหน้า
$countSQL = "SELECT COUNT(*) as total FROM bus_info AS bi
    LEFT JOIN bus_routes AS br ON bi.br_id = br.br_id
    $whereSQL";

$countResult = mysqli_query($conn, $countSQL);
$totalRow = mysqli_fetch_assoc($countResult)['total'];
$totalPages = ceil($totalRow / $perPage);

// ดึงข้อมูลหน้าปัจจุบันพร้อม filter และแบ่งหน้า
$sql = "SELECT *, lo_start.locat_name_th as lo_start, lo_end.locat_name_th AS lo_end
    FROM bus_info AS bi
    LEFT JOIN bus_routes AS br ON bi.br_id = br.br_id
    LEFT JOIN location AS lo_start ON br.br_start = lo_start.locat_id
    LEFT JOIN location AS lo_end ON br.br_end = lo_end.locat_id
    LEFT JOIN bus_type AS bt ON bi.bt_id = bt.bt_id
    LEFT JOIN bus_sub_class AS bsc ON bt.bsc_id = bsc.bsc_id
    LEFT JOIN bus_status AS BS ON bi.bs_id = bs.bs_id
    $whereSQL
    LIMIT $start, $perPage";

$result = mysqli_query($conn, $sql);
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="css/edit.css">
</head>
<body class='bg-light'>
    <div class="container">
        <main class="main mw-100-vw col75-25" id='main'>
            <?php include('header.php') ?>
            <div class="section ">
                <div class="fillter">
                    <h1 class='.over-icon'>Overview</h1>
                    <form action="" class="bar" method="GET">
                        <div>
                            <label for="line">สายรถ</label>
                            <input type="text" name="line" value="<?php echo htmlspecialchars($filter_line); ?>">
                        </div>
                        <div>
                            <label for="date">วันที่</label>
                            <input type="date" name="date" value="<?php echo htmlspecialchars($filter_date); ?>">
                        </div>
                        <button type="submit" class="btn btn-primary">ค้นหา</button>
                    </form>

                </div>
                <h3>Edit</h3>
                <div class="table">
                    <table class="table table-bordered ">
                        <thead>
                            <tr>
                                <td>เลขที่</td>
                                <td>รหัส</td>
                                <td>ชื่อรุ่น</td>
                                <td>จำนวนที่นั่ง</td>
                                <td>ประเภทรถ</td>
                                <td>ประเภทย่อย</td>
                                <td>ต้นทาง</td>
                                <td>ปลายทาง</td>
                                <td>สถานะ</td>
                                <td>edit</td>

                            </tr>
                        </thead>
                        <tbody>
                            <?php
                                $i = 1;
                                while($rows = mysqli_fetch_assoc($result)){
                                    ?>
                            <tr>
                                <td><?php echo $i ?></td>
                                <td><?php echo $rows['bi_licenseplate'] ?></td>
                                <td><?php echo $rows['bi_model'] ?></td>
                                <td><?php echo $rows['bi_capacity'] ?></td>
                                <td><?php echo $rows['bt_name'] ?></td>
                                <td><?php echo $rows['bsc_name'] ?></td>
                                <td><?php echo $rows['lo_start'] ?></td>
                                <td><?php echo $rows['lo_end'] ?></td>
                                <td><?php echo $rows['bs_name'] ?></td>
                                <td>
                                    <button class="btn btn-primary btn-edit" onclick="active('edit-area')">Edit</button>

                                </td>
                            </tr>
                                <?php
                                $i++;
                                }
                                ?> 

                        </tbody>
                    </table>
                </div>
                <nav>
  <ul class="pagination">
    <?php if($page > 1): ?>
      <li class="page-item">
        <a class="page-link" href="?page=<?php echo $page - 1 ?>&line=<?php echo urlencode($filter_line) ?>&date=<?php echo urlencode($filter_date) ?>">Previous</a>
      </li>
    <?php endif; ?>

    <?php for($p = 1; $p <= $totalPages; $p++): ?>
      <li class="page-item <?php echo ($p == $page) ? 'active' : '' ?>">
        <a class="page-link" href="?page=<?php echo $p ?>&line=<?php echo urlencode($filter_line) ?>&date=<?php echo urlencode($filter_date) ?>"><?php echo $p ?></a>
      </li>
    <?php endfor; ?>

    <?php if($page < $totalPages): ?>
      <li class="page-item">
        <a class="page-link" href="?page=<?php echo $page + 1 ?>&line=<?php echo urlencode($filter_line) ?>&date=<?php echo urlencode($filter_date) ?>">Next</a>
      </li>
    <?php endif; ?>
  </ul>
</nav>




<div class="edit-area d-none" id="edit-area">
  <form action="">
    <h2>แก้ไขข้อมูลรถ</h2>

    <div>
      <label>เลขที่ (รหัสรถโดยสาร)</label>
      <input type="text" value="18-1234" disabled>
    </div>

    <div>
      <label>เลขทะเบียน</label>
      <input type="text" name="licenseplate" required>
    </div>

    <div>
      <label>ชื่อรุ่น</label>
      <input type="text" name="model" required>
    </div>

    <div>
      <label>จำนวนที่นั่ง</label>
      <input type="number" name="capacity" min="1" required>
    </div>

    <div>
      <label>ประเภทรถ</label>
      <select name="bus_type" required>
        <option value="">-- เลือกประเภทรถ --</option>
        <option value="1">ประเภทรถ 1</option>
        <option value="2">ประเภทรถ 2</option>
        <option value="3">ประเภทรถ 3</option>
      </select>
    </div>

    <div>
      <label>ประเภทย่อย</label>
      <select name="bus_sub_type" required>
        <option value="">-- เลือกประเภทย่อย --</option>
        <option value="1">ประเภทย่อย 1</option>
        <option value="2">ประเภทย่อย 2</option>
        <option value="3">ประเภทย่อย 3</option>
      </select>
    </div>

    <div>
      <label>ต้นทาง</label>
      <input type="text" name="start_location" required>
      <!-- หรือใช้ select ถ้ามีข้อมูลต้นทางจากฐานข้อมูล -->
    </div>

    <div>
      <label>ปลายทาง</label>
      <input type="text" name="end_location" required>
      <!-- หรือใช้ select ถ้ามีข้อมูลปลายทางจากฐานข้อมูล -->
    </div>

    <div>
      <label>สถานะ</label>
      <select name="status" required>
        <option value="">-- เลือกสถานะ --</option>
        <option value="1">สถานะ 1</option>
        <option value="2">สถานะ 2</option>
        <option value="3">สถานะ 3</option>
      </select>
    </div>

    <button type="submit" class="submit">SAVE</button>
    <div onclick="active('edit-area')" class="btn btn-outline-secondary mt-2">Close</div>
  </form>
</div>


<div class="add-area d-none" id="add-area">
  <form action="">
    <h2>แก้ไขข้อมูลรถ</h2>

    <div>
      <label>เลขที่ (รหัสรถโดยสาร)</label>
      <input type="text" value="18-1234" disabled>
    </div>

    <div>
      <label>เลขทะเบียน</label>
      <input type="text" name="licenseplate" required>
    </div>

    <div>
      <label>ชื่อรุ่น</label>
      <input type="text" name="model" required>
    </div>

    <div>
      <label>จำนวนที่นั่ง</label>
      <input type="number" name="capacity" min="1" required>
    </div>

    <div>
      <label>ประเภทรถ</label>
      <select name="bus_type" required>
        <option value="">-- เลือกประเภทรถ --</option>
        <option value="1">ประเภทรถ 1</option>
        <option value="2">ประเภทรถ 2</option>
        <option value="3">ประเภทรถ 3</option>
      </select>
    </div>

    <div>
      <label>ประเภทย่อย</label>
      <select name="bus_sub_type" required>
        <option value="">-- เลือกประเภทย่อย --</option>
        <option value="1">ประเภทย่อย 1</option>
        <option value="2">ประเภทย่อย 2</option>
        <option value="3">ประเภทย่อย 3</option>
      </select>
    </div>

    <div>
      <label>ต้นทาง</label>
      <input type="text" name="start_location" required>
      <!-- หรือใช้ select ถ้ามีข้อมูลต้นทางจากฐานข้อมูล -->
    </div>

    <div>
      <label>ปลายทาง</label>
      <input type="text" name="end_location" required>
      <!-- หรือใช้ select ถ้ามีข้อมูลปลายทางจากฐานข้อมูล -->
    </div>

    <div>
      <label>สถานะ</label>
      <select name="status" required>
        <option value="">-- เลือกสถานะ --</option>
        <option value="1">สถานะ 1</option>
        <option value="2">สถานะ 2</option>
        <option value="3">สถานะ 3</option>
      </select>
    </div>

    <button type="submit" class="submit">SAVE</button>
    <div onclick="active('add-area')" class="btn btn-outline-secondary mt-2">Close</div>
  </form>
</div>
             
            <!-- <aside class="info-bar ">
                <div class='plan dash-text'></div>
                <div class='delay dash-text'></div>
                <div class='maintain dash-text'></div>
                <div class='accident dash-text'></div>
            </aside> -->
        </main>
    </div>

    <script>

    function active(value,action,bus_id){
        if(value == 'sidebar'){
            let main = document.getElementById('main');
            main.classList.toggle('mw-100-vw')
            main.classList.toggle('mw-85-vw')
            main.classList.toggle('col75-25')
            main.classList.toggle('col60-40')
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
        }else{
            let element = document.getElementById(value);
            element.classList.toggle('d-none');
        }
    }


    </script>
</body>
</html>