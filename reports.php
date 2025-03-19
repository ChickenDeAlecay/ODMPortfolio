<?php
include_once("includes/_connect.php");
include_once("includes/header.php");
include_once("includes/nav.php");
include_once("includes/utils.php");
?>

<!-- ====================================================== -->
<!-- PAGE CONTENT STARTS HERE -->
<!-- ====================================================== -->

<h2>Power Station report page</h2>

<?php
// Fetch worker hours data
$hours_sql = "SELECT 
                staff.first_name, 
                staff.last_name, 
                SUM(work_schedule.id) AS job_count
             FROM 
                work_schedule
             JOIN 
                staff ON work_schedule.staff_id = staff.id
             GROUP BY 
                staff.id
             ORDER BY 
                job_count DESC";

$result = runAndCheckSQL($connect, $hours_sql);

// Prepare data for Google Charts
$data = [];
while ($row = mysqli_fetch_assoc($result)) {
    $data[] = [$row['first_name'] . ' ' . $row['last_name'], (int)$row['total_hours']];
}

// Include Google Charts script
?>
<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
<script type="text/javascript">
  google.charts.load('current', {'packages':['corechart']});
  google.charts.setOnLoadCallback(drawChart);

  function drawChart() {
    var data = google.visualization.arrayToDataTable([
      ['Worker', 'Hours Worked'],
      <?php
      foreach ($data as $row) {
          echo "['" . $row[0] . "', " . $row[1] . "],";
      }
      ?>
    ]);

    var options = {
      title: 'Worker Hours',
      hAxis: {title: 'Worker', titleTextStyle: {color: '#333'}},
      vAxis: {minValue: 0}
    };

    var chart = new google.visualization.AreaChart(document.getElementById('chart_div'));
    chart.draw(data, options);
  }
</script>

<div id="chart_div" style="width: 100%; height: 500px;"></div>

<!-- ====================================================== -->
<!-- PAGE CONTENT ENDS HERE -->
<!-- ====================================================== -->

<?php include_once("includes/footer.php"); ?>