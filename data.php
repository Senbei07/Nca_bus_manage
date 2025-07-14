<?php

    include("config.php");
    // session_start();

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
        }else{
          $bus[] = [
                    'id' => $rows_bus['lo_end_en'],
                    'name' => $rows_bus['lo_end_en'],
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
          echo 2;
        }
        

      }

    // print_r($bus);
    ?>

    <script>

      console.log(<?php echo json_encode($bus); ?>)
    </script>