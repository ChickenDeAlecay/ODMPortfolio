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
$worker_jobs_sql = "SELECT 
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

$result = runAndCheckSQL($connect, $worker_jobs_sql);

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

// Fetch data for staff with no work allocated
$staff_no_work_sql = "SELECT 
                        staff.first_name, 
                        staff.last_name 
                      FROM 
                        staff 
                      LEFT JOIN 
                        work_schedule ON staff.id = work_schedule.staff_id 
                      WHERE 
                        work_schedule.staff_id IS NULL";

// Execute the query
$staff_no_work_result = runAndCheckSQL($connect, $staff_no_work_sql);

// Prepare data for Google Charts
$no_work_data = [];
while ($row = mysqli_fetch_assoc($staff_no_work_result)) {
    $no_work_data[] = [$row['first_name'] . ' ' . $row['last_name']];
}

// Include Google Charts script
?>
<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
<script type="text/javascript">
  google.charts.load('current', {'packages':['corechart', 'bar']});
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

    // Draw pie chart for radiation exposure per staff
    var radiationData = google.visualization.arrayToDataTable([
      ['Staff', 'Radiation Exposure'],
      <?php
      foreach ($radiation_data as $row) {
          echo "['" . $row[0] . "', " . $row[1] . "],";
      }
      ?>
    ]);

    var radiationOptions = {
      title: 'Radiation Exposure per Staff',
      is3D: true
    };

    var radiationChart = new google.visualization.PieChart(document.getElementById('radiation_chart_div'));
    radiationChart.draw(radiationData, radiationOptions);

    // Draw bar chart for staff with no work allocated
    var noWorkData = google.visualization.arrayToDataTable([
      ['Staff', 'No Work'],
      <?php
      foreach ($no_work_data as $row) {
          echo "['" . $row[0] . "', " . $row[1] . "],";
      }
      ?>
    ]);

    var noWorkOptions = {
      title: 'Staff with No Work Allocated',
      hAxis: {title: 'Staff'}
    };

    var noWorkChart = new google.visualization.BarChart(document.getElementById('no_work_chart_div'));
    noWorkChart.draw(noWorkData, noWorkOptions);
  }
</script>

<!-- Bar chart for worker jobs worked -->
<div id="job_chart_div" style="width: 100%; height: 500px;"></div>

<!-- Pie chart for radiation exposure per staff -->
<div id="radiation_chart_div" style="width: 100%; height: 500px;"></div>

<!-- Bar chart for staff with no work allocated -->
<div id="no_work_chart_div" style="width: 100%; height: 500px;"></div>

<!-- ====================================================== -->
<!-- PAGE CONTENT ENDS HERE -->
<!-- ====================================================== -->

<?php include_once("includes/footer.php"); ?>