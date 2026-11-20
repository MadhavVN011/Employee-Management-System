<?php include 'includes/session.php'; ?>
<?php 
  include '../timezone.php'; 
  $today = date('Y-m-d');
  $year = date('Y');
  if(isset($_GET['year'])){
    $year = (int) $_GET['year'];
  }
?>
<?php include 'includes/header.php'; ?>
<body class="hold-transition skin-blue sidebar-mini">
<div class="wrapper">

  	<?php include 'includes/navbar.php'; ?>
  	<?php include 'includes/menubar.php'; ?>

  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
      <div class="page-eyebrow">YOUR WORKSPACE</div>
      <h1>Your team, at a glance.</h1>
      <p class="page-description">Welcome back, <?php echo h($user['firstname']); ?>. Here's a little clarity for your workday.</p>
      <ol class="breadcrumb">
        <li><a href="#"><i class="fa fa-dashboard"></i> Home</a></li>
        <li class="active">Dashboard</li>
      </ol>
    </section>

    <!-- Main content -->
    <section class="content">
      <?php
        if(isset($_SESSION['error'])){
          echo "
            <div class='alert alert-danger alert-dismissible'>
              <button type='button' class='close' data-dismiss='alert' aria-hidden='true'>&times;</button>
              <h4><i class='icon fa fa-warning'></i> Error!</h4>
              ".h($_SESSION['error'])."
            </div>
          ";
          unset($_SESSION['error']);
        }
        if(isset($_SESSION['success'])){
          echo "
            <div class='alert alert-success alert-dismissible'>
              <button type='button' class='close' data-dismiss='alert' aria-hidden='true'>&times;</button>
              <h4><i class='icon fa fa-check'></i> Success!</h4>
              ".h($_SESSION['success'])."
            </div>
          ";
          unset($_SESSION['success']);
        }
      ?>
      <!-- Small boxes (Stat box) -->
      <div class="row stats-grid">
        <div class="col-lg-3 col-xs-6">
          <!-- small box -->
          <div class="small-box bg-aqua">
            <div class="inner">
              <?php
                $sql = "SELECT * FROM employees";
                $query = $conn->query($sql);

                echo "<h3>".$query->num_rows."</h3>";
              ?>

              <p>Total Employees</p>
            </div>
            <div class="icon">
              <i class="ion ion-person-stalker"></i>
            </div>
            <a href="employee.php" class="small-box-footer">View employees <i class="fa fa-long-arrow-right" aria-hidden="true"></i></a>
          </div>
        </div>
        <!-- ./col -->
        <div class="col-lg-3 col-xs-6">
          <!-- small box -->
          <div class="small-box bg-green">
            <div class="inner">
              <?php
                $sql = "SELECT * FROM attendance";
                $query = $conn->query($sql);
                $total = $query->num_rows;

                $sql = "SELECT * FROM attendance WHERE status = 1";
                $query = $conn->query($sql);
                $early = $query->num_rows;
                
                $percentage = $total > 0 ? ($early/$total)*100 : 0;

                echo "<h3>".number_format($percentage, 2)."<sup style='font-size: 20px'>%</sup></h3>";
              ?>
          
              <p>On Time Percentage</p>
            </div>
            <div class="icon">
              <i class="ion ion-pie-graph"></i>
            </div>
            <a href="attendance.php" class="small-box-footer">Explore attendance <i class="fa fa-long-arrow-right" aria-hidden="true"></i></a>
          </div>
        </div>
        <!-- ./col -->
        <div class="col-lg-3 col-xs-6">
          <!-- small box -->
          <div class="small-box bg-yellow">
            <div class="inner">
              <?php
                $sql = "SELECT * FROM attendance WHERE date = '$today' AND status = 1";
                $query = $conn->query($sql);

                echo "<h3>".$query->num_rows."</h3>"
              ?>
             
              <p>On Time Today</p>
            </div>
            <div class="icon">
              <i class="ion ion-clock"></i>
            </div>
            <a href="attendance.php" class="small-box-footer">View today's attendance <i class="fa fa-long-arrow-right" aria-hidden="true"></i></a>
          </div>
        </div>
        <!-- ./col -->
        <div class="col-lg-3 col-xs-6">
          <!-- small box -->
          <div class="small-box bg-red">
            <div class="inner">
              <?php
                $sql = "SELECT * FROM attendance WHERE date = '$today' AND status = 0";
                $query = $conn->query($sql);

                echo "<h3>".$query->num_rows."</h3>"
              ?>

              <p>Late Today</p>
            </div>
            <div class="icon">
              <i class="ion ion-alert-circled"></i>
            </div>
            <a href="attendance.php" class="small-box-footer">Review late arrivals <i class="fa fa-long-arrow-right" aria-hidden="true"></i></a>
          </div>
        </div>
        <!-- ./col -->
      </div>
      <!-- /.row -->
      <div class="row dashboard-bottom">
        <div class="col-lg-8 col-md-8">
          <div class="box">
            <div class="box-header with-border">
              <div><h3 class="box-title">Attendance overview</h3><p class="box-subtitle">Every arrival tells a part of the story.</p></div>
              <div class="box-tools pull-right">
                <form class="form-inline">
                  <div class="form-group">
                    <label for="select_year">Year</label>
                    <select class="form-control input-sm" id="select_year">
                      <?php
                        for($i=2015; $i<=2065; $i++){
                          $selected = ($i==$year)?'selected':'';
                          echo "
                            <option value='".$i."' ".$selected.">".$i."</option>
                          ";
                        }
                      ?>
                    </select>
                  </div>
                </form>
              </div>
            </div>
            <div class="box-body">
              <div class="chart">
                <div id="legend" class="chart-legend"></div>
                <canvas id="barChart" style="height:270px" aria-label="Monthly on-time and late attendance" role="img"></canvas>
                <div id="chart-empty" hidden><span class="empty-chart-icon"><i class="fa fa-bar-chart" aria-hidden="true"></i></span><strong>A fresh page for <?php echo h($year); ?>.</strong><p>No attendance recorded for this year yet.<br>Check back as your team checks in, or choose another year.</p></div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-lg-4 col-md-4">
          <div class="box quick-card">
            <div class="page-eyebrow">A LITTLE LESS ADMIN</div>
            <h3>Make room for<br>the work that matters.</h3>
            <p>Your everyday essentials, right here.</p>
            <a href="employee.php" class="quick-link"><i class="fa fa-user-o" aria-hidden="true"></i><span><strong>Your people</strong><small>Meet and manage your team</small></span><i class="fa fa-long-arrow-right" aria-hidden="true"></i></a>
            <a href="attendance.php" class="quick-link"><i class="fa fa-calendar-check-o" aria-hidden="true"></i><span><strong>Time, well tracked</strong><small>Review employee attendance</small></span><i class="fa fa-long-arrow-right" aria-hidden="true"></i></a>
            <a href="payroll.php" class="quick-link"><i class="fa fa-credit-card" aria-hidden="true"></i><span><strong>Payroll, made clear</strong><small>Review pay and generate reports</small></span><i class="fa fa-long-arrow-right" aria-hidden="true"></i></a>
          </div>
          <div class="workspace-tip"><i class="fa fa-leaf" aria-hidden="true"></i><div><strong>A little planning goes a long way.</strong><p>Keep your team's schedules up to date for more accurate attendance and payroll.</p></div></div>
        </div>
      </div>

      </section>
      <!-- right col -->
    </div>
  	<?php include 'includes/footer.php'; ?>

</div>
<!-- ./wrapper -->

<!-- Chart Data -->
<?php
  $and = 'AND YEAR(date) = '.$year;
  $months = array();
  $ontime = array();
  $late = array();
  for( $m = 1; $m <= 12; $m++ ) {
    $sql = "SELECT * FROM attendance WHERE MONTH(date) = '$m' AND status = 1 $and";
    $oquery = $conn->query($sql);
    array_push($ontime, $oquery->num_rows);

    $sql = "SELECT * FROM attendance WHERE MONTH(date) = '$m' AND status = 0 $and";
    $lquery = $conn->query($sql);
    array_push($late, $lquery->num_rows);

    $num = str_pad( $m, 2, 0, STR_PAD_LEFT );
    $month =  date('M', mktime(0, 0, 0, $m, 1));
    array_push($months, $month);
  }

  $months = json_encode($months);
  $late = json_encode($late);
  $ontime = json_encode($ontime);

?>
<!-- End Chart Data -->
<?php include 'includes/scripts.php'; ?>
<script>
$(function(){
  var barChartCanvas = $('#barChart').get(0).getContext('2d')
  var barChartData = {
    labels  : <?php echo $months; ?>,
    datasets: [
      {
        label               : 'Late',
        fillColor           : 'rgba(218, 174, 145, 0.85)',
        strokeColor         : 'rgba(218, 174, 145, 0.85)',
        pointColor          : 'rgba(218, 174, 145, 0.85)',
        pointStrokeColor    : '#c1c7d1',
        pointHighlightFill  : '#fff',
        pointHighlightStroke: 'rgba(220,220,220,1)',
        data                : <?php echo $late; ?>
      },
      {
        label               : 'On time',
        fillColor           : 'rgba(60,141,188,0.9)',
        strokeColor         : 'rgba(60,141,188,0.8)',
        pointColor          : '#3b8bba',
        pointStrokeColor    : 'rgba(60,141,188,1)',
        pointHighlightFill  : '#fff',
        pointHighlightStroke: 'rgba(60,141,188,1)',
        data                : <?php echo $ontime; ?>
      }
    ]
  }
  barChartData.datasets[1].fillColor   = '#79966b'
  barChartData.datasets[1].strokeColor = '#79966b'
  barChartData.datasets[1].pointColor  = '#79966b'
  var barChartOptions                  = {
    //Boolean - Whether the scale should start at zero, or an order of magnitude down from the lowest value
    scaleBeginAtZero        : true,
    //Boolean - Whether grid lines are shown across the chart
    scaleShowGridLines      : true,
    //String - Colour of the grid lines
    scaleGridLineColor      : 'rgba(0,0,0,.05)',
    //Number - Width of the grid lines
    scaleGridLineWidth      : 1,
    //Boolean - Whether to show horizontal lines (except X axis)
    scaleShowHorizontalLines: true,
    //Boolean - Whether to show vertical lines (except Y axis)
    scaleShowVerticalLines  : true,
    //Boolean - If there is a stroke on each bar
    barShowStroke           : true,
    //Number - Pixel width of the bar stroke
    barStrokeWidth          : 2,
    //Number - Spacing between each of the X value sets
    barValueSpacing         : 5,
    //Number - Spacing between data sets within X values
    barDatasetSpacing       : 1,
    //String - A legend template
    legendTemplate          : '<ul class="<%=name.toLowerCase()%>-legend"><% for (var i=0; i<datasets.length; i++){%><li><span style="background-color:<%=datasets[i].fillColor%>"></span><%if(datasets[i].label){%><%=datasets[i].label%><%}%></li><%}%></ul>',
    //Boolean - whether to make the chart responsive
    responsive              : true,
    maintainAspectRatio     : false
  }

  barChartOptions.datasetFill = false
  var hasAttendance = barChartData.datasets.some(function (dataset) {
    return dataset.data.some(function (count) { return count > 0; });
  });
  var myChart;
  function renderChartTheme() {
    if (!hasAttendance) return;
    var dark = document.documentElement.getAttribute('data-theme') === 'dark';
    if (myChart) myChart.destroy();
    barChartOptions.scaleFontColor = dark ? '#b4c2ad' : '#63725b';
    barChartOptions.scaleGridLineColor = dark ? 'rgba(195,215,184,.12)' : 'rgba(0,0,0,.05)';
    barChartOptions.scaleLineColor = barChartOptions.scaleGridLineColor;
    barChartOptions.animation = false;
    barChartData.datasets[1].fillColor = barChartData.datasets[1].strokeColor = dark ? '#a1c58e' : '#79966b';
    myChart = new Chart(barChartCanvas).Bar(barChartData, barChartOptions);
    document.getElementById('legend').innerHTML = myChart.generateLegend();
  }
  renderChartTheme();
  document.addEventListener('ems:themechange', renderChartTheme);
  if (!hasAttendance) {
    $('#barChart, #legend').hide();
    $('#chart-empty').prop('hidden', false);
  }
});
</script>
<script>
$(function(){
  $('#select_year').change(function(){
    window.location.href = 'home.php?year='+$(this).val();
  });
});
</script>
</body>
</html>
