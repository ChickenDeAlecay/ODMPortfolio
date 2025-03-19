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
// Fetch worker job count data
$jobs_sql = "SELECT 
                staff.first_name, 
                staff.last_name, 
                COUNT(work_schedule.id) AS job_count
             FROM 
                work_schedule
             JOIN 
                staff ON work_schedule.staff_id = staff.id
             GROUP BY 
                staff.id
             ORDER BY 
                job_count DESC";

$result = runAndCheckSQL($connect, $jobs_sql);

// Prepare data for Google Charts
$data = [];
while ($row = mysqli_fetch_assoc($result)) {
    $data[] = [$row['first_name'] . ' ' . $row['last_name'], (int)$row['job_count']];
}

// Fetch radiation exposure data
$radiation_sql = "SELECT 
                    staff.first_name, 
                    staff.last_name, 
                    SUM(job.radiation_exposure) AS total_radiation_exposure
                 FROM 
                    work_schedule
                 JOIN 
                    staff ON work_schedule.staff_id = staff.id
                 JOIN 
                    job ON work_schedule.job_id = job.id
                 GROUP BY 
                    staff.id
                 ORDER BY 
                    total_radiation_exposure DESC";

$radiation_result = runAndCheckSQL($connect, $radiation_sql);

// Prepare data for Google Charts
$radiation_data = [];
while ($row = mysqli_fetch_assoc($radiation_result)) {
    $radiation_data[] = [$row['first_name'] . ' ' . $row['last_name'], (float)$row['total_radiation_exposure']];
}

// Include Google Charts script
?>
<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
<script type="text/javascript">
  google.charts.load('current', {'packages':['corechart', 'bar', 'gauge']});
  google.charts.setOnLoadCallback(drawCharts);

  function drawCharts() {
    // Draw bar chart for worker jobs worked
    var jobData = google.visualization.arrayToDataTable([
      ['Worker', 'Jobs Worked'],
      <?php
      foreach ($data as $row) {
          echo "['" . $row[0] . "', " . $row[1] . "],";
      }
      ?>
    ]);

    var jobOptions = {
      title: 'Worker Jobs Worked',
      hAxis: {title: 'Jobs Worked', minValue: 0},
      vAxis: {title: 'Worker'}
    };

    var jobChart = new google.visualization.BarChart(document.getElementById('job_chart_div'));
    jobChart.draw(jobData, jobOptions);

    // Draw gauge charts for radiation exposure per staff
    <?php
    foreach ($radiation_data as $row) {
        $staff_name = $row[0];
        $exposure = $row[1];
    ?>
    var gaugeData = google.visualization.arrayToDataTable([
      ['Label', 'Value'],
      ['Exposure', <?php echo $exposure; ?>],
    ]);

    var gaugeOptions = {
      width: 400, height: 120, 
      min: 0, max: 350,
      greenFrom: 0, greenTo: 50,
      yellowFrom:50, yellowTo: 100,
      redFrom: 100, redTo: 350,
      minorTicks: 5
    };

    var gaugeChart = new google.visualization.Gauge(document.getElementById('gauge_chart_<?php echo md5($staff_name); ?>'));
    gaugeChart.draw(gaugeData, gaugeOptions);
    <?php
    }
    ?>
  }
</script>

<!-- Bar chart for worker jobs worked -->
<div id="job_chart_div" style="width: 100%; height: 500px;"></div>

<!-- Gauge charts for radiation exposure per staff -->
<?php
foreach ($radiation_data as $row) {
    $staff_name = $row[0];
?>
    <div>
        <h3><?php echo $staff_name; ?></h3>
        <div id="gauge_chart_<?php echo md5($staff_name); ?>" style="width: 400px; height: 120px;"></div>
    </div>
<?php
}
?>

<!-- ====================================================== -->
<!-- PAGE CONTENT ENDS HERE -->
<!-- ====================================================== -->

<?php include_once("includes/footer.php"); ?>