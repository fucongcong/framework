
<!DOCTYPE html>
<html class="">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title></title>
  <meta name="keywords" content="" />
  <meta name="description" content="" />
 
  <link rel="stylesheet" href="//cdn.bootcss.com/bootstrap/3.3.5/css/bootstrap.min.css">
  
  
</head>
<body>

    <table class="table">
      <th>任务pid</th>
      <th>任务名</th>
      <th>下次执行时间</th>
    

    <?php 
    foreach ($work_ids as $work_id) {
        echo '<tr>';
        echo "<td>{$work_id}</td>";
        echo "<td>{$work_id}</td>";
        echo "<td>{$work_id}</td>";
        echo '</tr>';
    }
    ?>   
    </table>
  </body>
</html>