<!DOCTYPE html>
<html lang="en">

<head>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <!-- Meta, title, CSS, favicons, etc. -->
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <title>Gentelella Alela! | </title>

  <!-- Bootstrap -->
  <link href="assets/vendors/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Font Awesome -->
  <link href="assets/vendors/font-awesome/css/font-awesome.min.css" rel="stylesheet">
  <!-- NProgress -->
  <link href="assets/vendors/nprogress/nprogress.css" rel="stylesheet">
  <!-- iCheck -->
  <link href="assets/vendors/iCheck/skins/flat/green.css" rel="stylesheet">
  <!-- JQVMap -->
  <link href="assets/vendors/jqvmap/dist/jqvmap.min.css" rel="stylesheet" />

  <!-- Custom Theme Style -->
  <link href="assets/css/custom.min.css" rel="stylesheet">
</head>

<body class="nav-md">
  <div class="container body">
    <div class="main_container">
      <?php include 'sidebar.php'; ?>
    </div>

    <!-- top navigation -->
    <div class="top_nav">
      <div class="nav_menu">
        <nav>
          <div class="nav toggle">
            <a id="menu_toggle"><i class="fa fa-bars"></i></a>
          </div>

          <ul class="nav navbar-nav navbar-right">
            <li class="">
              <a href="javascript:;" class="user-profile dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                <img src="assets/images/img.jpg" alt="">John Doe
                <span class=" fa fa-angle-down"></span>
              </a>
              <ul class="dropdown-menu dropdown-usermenu pull-right">
                <li><a href="javascript:;"> Profile</a></li>
                <li>
                  <a href="javascript:;">
                    <span class="badge bg-red pull-right">50%</span>
                    <span>Settings</span>
                  </a>
                </li>
                <li><a href="javascript:;">Help</a></li>
                <li><a href="login.html"><i class="fa fa-sign-out pull-right"></i> Log Out</a></li>
              </ul>
            </li>

            <li role="presentation" class="dropdown">
              <a href="javascript:;" class="dropdown-toggle info-number" data-toggle="dropdown" aria-expanded="false">
                <i class="fa fa-envelope-o"></i>
                <span class="badge bg-green">6</span>
              </a>
              <ul id="menu1" class="dropdown-menu list-unstyled msg_list" role="menu">
                <li>
                  <a>
                    <span class="image"><img src="assets/images/img.jpg" alt="Profile Image" /></span>
                    <span>
                      <span>John Smith</span>
                      <span class="time">3 mins ago</span>
                    </span>
                    <span class="message">
                      Film festivals used to be do-or-die moments for movie makers. They were where...
                    </span>
                  </a>
                </li>
                <li>
                  <a>
                    <span class="image"><img src="assets/images/img.jpg" alt="Profile Image" /></span>
                    <span>
                      <span>John Smith</span>
                      <span class="time">3 mins ago</span>
                    </span>
                    <span class="message">
                      Film festivals used to be do-or-die moments for movie makers. They were where...
                    </span>
                  </a>
                </li>
                <li>
                  <a>
                    <span class="image"><img src="assets/images/img.jpg" alt="Profile Image" /></span>
                    <span>
                      <span>John Smith</span>
                      <span class="time">3 mins ago</span>
                    </span>
                    <span class="message">
                      Film festivals used to be do-or-die moments for movie makers. They were where...
                    </span>
                  </a>
                </li>
                <li>
                  <a>
                    <span class="image"><img src="assets/images/img.jpg" alt="Profile Image" /></span>
                    <span>
                      <span>John Smith</span>
                      <span class="time">3 mins ago</span>
                    </span>
                    <span class="message">
                      Film festivals used to be do-or-die moments for movie makers. They were where...
                    </span>
                  </a>
                </li>
                <li>
                  <div class="text-center">
                    <a>
                      <strong>See All Alerts</strong>
                      <i class="fa fa-angle-right"></i>
                    </a>
                  </div>
                </li>
              </ul>
            </li>
          </ul>
        </nav>
      </div>
    </div>
    <!-- /top navigation -->

    <!-- page content -->
    <div class="right_col" role="main">
      <div class="">
        <div class="page-title">
          <div class="title_left">
            <h3>Other Charts</h3>
          </div>

          <div class="title_right">
            <div class="col-md-5 col-sm-5 col-xs-12 form-group pull-right top_search">
              <div class="input-group">
                <input type="text" class="form-control" placeholder="Search for...">
                <span class="input-group-btn">
                  <button class="btn btn-default" type="button">Go!</button>
                </span>
              </div>
            </div>
          </div>
        </div>

        <div class="clearfix"></div>

        <div class="row">
          <div class="col-md-12">
            <div class="x_panel">
              <div class="x_title">
                <h2>Other Graph types <small> Assorted graph types</small></h2>
                <ul class="nav navbar-right panel_toolbox">
                  <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                  </li>
                  <li class="dropdown">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false"><i class="fa fa-wrench"></i></a>
                    <ul class="dropdown-menu" role="menu">
                      <li><a href="#">Settings 1</a>
                      </li>
                      <li><a href="#">Settings 2</a>
                      </li>
                    </ul>
                  </li>
                  <li><a class="close-link"><i class="fa fa-close"></i></a>
                  </li>
                </ul>
                <div class="clearfix"></div>
              </div>
              <div class="x_content">

                <div class="row">
                  <div class="col-md-6" style="padding: 30px">
                    <p>USA Vector Map</p>
                    <div id="usa_map" style="height:400px !important;"></div>
                  </div>
                  <div class="col-md-6" style="padding: 30px">
                    <p>World Vector Map</p>
                    <div id="world-map-gdp" style="height:400px;"></div>
                  </div>
                </div>
                <div class="clearfix"></div>


                <div class="row">
                  <div class="col-md-6">
                    <div class="panel panel-body">
                      <div class="x_title">
                        <h4>Easy Pie Chart</h4>
                      </div>

                      <p>Easy pie chart is a jQuery plugin that uses the canvas element to render highly customizable, very easy to implement, simple pie charts for single values.</p>
                      <div class="row">
                        <div class="col-xs-4">
                          <span class="chart" data-percent="86">
                            <span class="percent"></span>
                          </span>
                        </div>
                        <div class="col-xs-4">
                          <span class="chart" data-percent="73">
                            <span class="percent"></span>
                          </span>
                        </div>
                        <div class="col-xs-4">
                          <span class="chart" data-percent="60">
                            <span class="percent"></span>
                          </span>
                        </div>
                        <div class="clearfix"></div>
                      </div>

                    </div>
                  </div>

                  <div class="col-md-6">
                    <div class="panel panel-body">
                      <div class="x_title">
                        <h4>Sparkline Inline Graphs</h4>
                      </div>

                      <p>Yet another great library for inline data visualizations</p>
                      <table class="table table-striped">
                        <thead>
                          <tr>
                            <th style="width:20%"></th>
                            <th style="width:50%"></th>
                          </tr>
                        </thead>
                        <tbody>
                          <tr>
                            <th>
                              <span class="sparkline_line" style="height: 160px;">
                                <canvas width="200" height="60" style="display: inline-block; vertical-align: top; width: 94px; height: 30px;"></canvas>
                              </span>
                            </th>
                            <td>Line Graph</td>
                          </tr>
                          <tr>
                            <th>
                              <span class="sparkline_area" style="height: 160px;">
                                <canvas width="200" height="60" style="display: inline-block; vertical-align: top; width: 94px; height: 30px;"></canvas>
                              </span>
                            </th>
                            <td>Line Area Graph</td>
                          </tr>
                          <tr>
                            <th>
                              <span class="sparkline_bar" style="height: 160px;">
                                <canvas width="200" height="60" style="display: inline-block; vertical-align: top; width: 94px; height: 30px;"></canvas>
                              </span>
                            </th>
                            <td>Bar Graph</td>
                          </tr>
                          <tr>
                            <th>
                              <span class="sparkline_pie" style="height: 160px;">
                                <canvas width="200" height="60" style="display: inline-block; vertical-align: top; width: 94px; height: 30px;"></canvas>
                              </span>
                            </th>
                            <td>Pie Chart</td>
                          </tr>
                          <tr>
                            <th>
                              <span class="sparkline_discreet" style="height: 160px;">
                                <canvas width="200" height="60" style="display: inline-block; vertical-align: top; width: 94px; height: 30px;"></canvas>
                              </span>
                            </th>
                            <td>Discrete chart</td>
                          </tr>
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <!-- /page content -->

    <!-- footer content -->
    <footer>
      <div class="pull-right">
        Gentelella - Bootstrap Admin Template by <a href="https://colorlib.com">Colorlib</a>
      </div>
      <div class="clearfix"></div>
    </footer>
    <!-- /footer content -->
  </div>
  </div>

  <!-- jQuery -->
  <script src="assets/vendors/jquery/dist/jquery.min.js"></script>
  <!-- Bootstrap -->
  <script src="assets/vendors/bootstrap/dist/js/bootstrap.min.js"></script>
  <!-- FastClick -->
  <script src="assets/vendors/fastclick/lib/fastclick.js"></script>
  <!-- NProgress -->
  <script src="assets/vendors/nprogress/nprogress.js"></script>
  <!-- jQuery Sparklines -->
  <script src="assets/vendors/jquery-sparkline/dist/jquery.sparkline.min.js"></script>
  <!-- JQVMap -->
  <script src="assets/vendors/jqvmap/dist/jquery.vmap.js"></script>
  <script src="assets/vendors/jqvmap/dist/maps/jquery.vmap.world.js"></script>
  <script src="assets/vendors/jqvmap/dist/maps/jquery.vmap.usa.js"></script>
  <script src="assets/vendors/jqvmap/examples/js/jquery.vmap.sampledata.js"></script>
  <!-- easy-pie-chart -->
  <script src="assets/vendors/jquery.easy-pie-chart/dist/jquery.easypiechart.min.js"></script>

  <!-- Custom Theme Scripts -->
  <script src="assets/js/custom.min.js"></script>

</body>

</html>