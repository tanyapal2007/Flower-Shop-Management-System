<?php

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

require_once "../config/database.php";


/* =========================================================
   ADMIN CHECK
========================================================= */

if (!isset($_SESSION['user_id'])) {
  header("Location: ../login.php");
  exit;
}

$user_id = (int) $_SESSION['user_id'];


/* =========================================================
   FETCH ADMIN
========================================================= */

$stmt = $conn->prepare("
    SELECT
        name,
        role
    FROM users
    WHERE user_id = :user_id
");

$stmt->execute([
  ':user_id' => $user_id
]);

$admin = $stmt->fetch(PDO::FETCH_ASSOC);


/* =========================================================
   CHECK ADMIN ROLE
========================================================= */

if (!$admin || $admin['role'] !== 'admin') {
  header("Location: ../index.php");
  exit;
}


/* =========================================================
   DASHBOARD COUNTS
========================================================= */


/* -------------------------
   TOTAL PRODUCTS
------------------------- */

$stmt = $conn->query("
    SELECT COUNT(*)
    FROM products
    WHERE status = 1
");

$total_products = (int) $stmt->fetchColumn();


/* -------------------------
   TOTAL CATEGORIES
------------------------- */

$stmt = $conn->query("
    SELECT COUNT(*)
    FROM product_category
");

$total_categories = (int) $stmt->fetchColumn();


/* -------------------------
   TOTAL USERS
------------------------- */

$stmt = $conn->query("
    SELECT COUNT(*)
    FROM users
    WHERE role = 'user'
");

$total_users = (int) $stmt->fetchColumn();


/* -------------------------
   TOTAL ORDERS
------------------------- */

$stmt = $conn->query("
    SELECT COUNT(*)
    FROM orders
");

$total_orders = (int) $stmt->fetchColumn();


/* =========================================================
   RECENT ORDERS
========================================================= */

/*
   order_date is not used because it does not exist
   in your current orders table.

   Latest orders are shown using order_id.
*/

$stmt = $conn->query("
    SELECT
        o.order_id,
        o.user_id,
        o.total_amount,
        o.order_status,
        u.name
    FROM orders o

    LEFT JOIN users u
        ON u.user_id = o.user_id

    ORDER BY o.order_id DESC

    LIMIT 5
");

$recent_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);


/* =========================================================
   RECENT PRODUCTS
========================================================= */

/*
   Using products.product_price directly
   so no unknown column from product_prices is required.
*/

$stmt = $conn->query("
    SELECT
        product_id,
        product_name,
        product_price,
        stock_quantity,
        status
    FROM products
    ORDER BY product_id DESC
    LIMIT 5
");

$recent_products = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">

<head>

  <meta charset="utf-8">

  <meta http-equiv="X-UA-Compatible" content="IE=edge">

  <meta name="viewport"
    content="width=device-width, initial-scale=1">

  <title>Flower Shop Admin Dashboard</title>


  <!-- Bootstrap -->
  <link
    href="assets/vendors/bootstrap/dist/css/bootstrap.min.css"
    rel="stylesheet">


  <!-- Font Awesome -->
  <link
    href="assets/vendors/font-awesome/css/font-awesome.min.css"
    rel="stylesheet">


  <!-- NProgress -->
  <link
    href="assets/vendors/nprogress/nprogress.css"
    rel="stylesheet">


  <!-- Custom Theme -->
  <link
    href="assets/css/custom.min.css"
    rel="stylesheet">


  <style>
    /* =====================================================
           DASHBOARD CARDS
        ===================================================== */

    .dashboard-card {

      background: #ffffff;

      border-radius: 5px;

      padding: 20px;

      min-height: 130px;

      margin-bottom: 20px;

      box-shadow:
        0 1px 5px rgba(0, 0, 0, 0.08);
    }


    .dashboard-card .card-icon {

      float: left;

      width: 60px;

      height: 60px;

      line-height: 60px;

      text-align: center;

      border-radius: 50%;

      background: #f5f5f5;

      font-size: 25px;

      margin-right: 15px;
    }


    .dashboard-card h3 {

      margin: 5px 0;

      font-size: 28px;

      font-weight: 600;
    }


    .dashboard-card p {

      margin: 0;

      color: #777;

      font-size: 14px;
    }


    /* =====================================================
           DASHBOARD PANELS
        ===================================================== */

    .dashboard-panel {

      background: #ffffff;

      border: 1px solid #e6e9ed;

      border-radius: 4px;

      margin-bottom: 20px;
    }


    .dashboard-panel .panel-title {

      padding: 15px 20px;

      border-bottom: 1px solid #e6e9ed;
    }


    .dashboard-panel .panel-title h3 {

      margin: 0;

      font-size: 18px;

      font-weight: 600;
    }


    .dashboard-panel .panel-body {

      padding: 20px;
    }


    /* =====================================================
           QUICK ACTIONS
        ===================================================== */

    .quick-action {

      display: block;

      padding: 20px 10px;

      text-align: center;

      border: 1px solid #e6e9ed;

      border-radius: 5px;

      color: #555;

      background: #fff;

      margin-bottom: 15px;

      text-decoration: none !important;
    }


    .quick-action:hover {

      background: #f8f8f8;

      color: #555;
    }


    .quick-action i {

      display: block;

      font-size: 30px;

      margin-bottom: 10px;
    }


    .quick-action span {

      font-size: 14px;

      font-weight: 600;
    }


    /* =====================================================
           TABLE
        ===================================================== */

    .dashboard-table {

      width: 100%;
    }


    .dashboard-table th {

      background: #f7f7f7;

      padding: 12px;

      font-size: 13px;
    }


    .dashboard-table td {

      padding: 12px;

      border-top: 1px solid #eee;

      font-size: 13px;
    }


    /* =====================================================
           STATUS
        ===================================================== */

    .status {

      padding: 5px 10px;

      border-radius: 3px;

      font-size: 11px;

      font-weight: 600;

      display: inline-block;
    }


    .status-success {

      background: #dff0d8;

      color: #3c763d;
    }


    .status-warning {

      background: #fcf8e3;

      color: #8a6d3b;
    }


    .status-danger {

      background: #f2dede;

      color: #a94442;
    }


    /* =====================================================
           PRODUCT STATUS
        ===================================================== */

    .product-active {

      color: #3c763d;

      font-weight: 600;
    }


    .product-inactive {

      color: #a94442;

      font-weight: 600;
    }


    /* =====================================================
           PAGE TITLE
        ===================================================== */

    .dashboard-heading {

      margin-bottom: 20px;
    }


    .dashboard-heading h3 {

      margin-top: 0;

      margin-bottom: 5px;

      font-weight: 600;
    }


    .dashboard-heading p {

      color: #777;

      margin: 0;
    }
  </style>

</head>


<body class="nav-md">


  <div class="container body">


    <div class="main_container">


      <!-- =====================================================
         SIDEBAR
         
         YOUR ORIGINAL SIDEBAR
         NO CHANGE
    ====================================================== -->

      <?php include 'sidebar.php'; ?>


      <!-- =====================================================
         TOP NAVIGATION
    ====================================================== -->

      <div class="top_nav">

        <div class="nav_menu">

          <nav>


            <!-- MENU BUTTON -->

            <div class="nav toggle">

              <a id="menu_toggle">

                <i class="fa fa-bars"></i>

              </a>

            </div>


            <!-- RIGHT MENU -->

            <ul class="nav navbar-nav navbar-right">


              <!-- ADMIN PROFILE -->

              <li>

                <a
                  href="javascript:;"
                  class="user-profile dropdown-toggle"
                  data-toggle="dropdown"
                  aria-expanded="false">

                  <img
                    src="assets/images/img.jpg"
                    alt="Profile">

                  <?php
                  echo htmlspecialchars(
                    $admin['name']
                  );
                  ?>

                  <span
                    class="fa fa-angle-down"></span>

                </a>


                <ul
                  class="dropdown-menu dropdown-usermenu pull-right">

                  <li>

                    <a href="javascript:;">

                      Profile

                    </a>

                  </li>


                  <li>

                    <a href="javascript:;">

                      Settings

                    </a>

                  </li>


                  <li>

                    <a href="javascript:;">

                      Help

                    </a>

                  </li>


                  <li>

                    <a href="../login.php">

                      <i
                        class="fa fa-sign-out pull-right"></i>

                      Log Out

                    </a>

                  </li>

                </ul>

              </li>


              <!-- NOTIFICATION -->

              <li
                role="presentation"
                class="dropdown">

                <a
                  href="javascript:;"
                  class="dropdown-toggle info-number"
                  data-toggle="dropdown"
                  aria-expanded="false">

                  <i
                    class="fa fa-envelope-o"></i>

                  <span
                    class="badge bg-green">
                    0
                  </span>

                </a>


                <ul
                  id="menu1"
                  class="dropdown-menu list-unstyled msg_list"
                  role="menu">

                  <li>

                    <div
                      class="text-center">

                      <a>

                        <strong>
                          No New Notifications
                        </strong>

                      </a>

                    </div>

                  </li>

                </ul>

              </li>

            </ul>

          </nav>

        </div>

      </div>


      <!-- =====================================================
         PAGE CONTENT
    ====================================================== -->

      <div
        class="right_col"
        role="main">

        <div>


          <!-- =================================================
                 PAGE HEADING
            ================================================== -->

          <div class="row">

            <div class="col-md-12">

              <div class="dashboard-heading">

                <h3>
                  Flower Shop Dashboard
                </h3>

                <p>
                  Welcome back,
                  <?php
                  echo htmlspecialchars(
                    $admin['name']
                  );
                  ?>.
                  Manage your flower shop from here.
                </p>

              </div>

            </div>

          </div>


          <!-- =================================================
                 STATISTICS CARDS
            ================================================== -->

          <div class="row">


            <!-- TOTAL PRODUCTS -->

            <div
              class="col-md-3 col-sm-6 col-xs-12">

              <div class="dashboard-card">

                <div class="card-icon">

                  <i
                    class="fa fa-leaf"></i>

                </div>


                <p>
                  Total Products
                </p>


                <h3>

                  <?php
                  echo $total_products;
                  ?>

                </h3>

              </div>

            </div>


            <!-- TOTAL CATEGORIES -->

            <div
              class="col-md-3 col-sm-6 col-xs-12">

              <div class="dashboard-card">

                <div class="card-icon">

                  <i
                    class="fa fa-list"></i>

                </div>


                <p>
                  Total Categories
                </p>


                <h3>

                  <?php
                  echo $total_categories;
                  ?>

                </h3>

              </div>

            </div>


            <!-- TOTAL ORDERS -->

            <div
              class="col-md-3 col-sm-6 col-xs-12">

              <div class="dashboard-card">

                <div class="card-icon">

                  <i
                    class="fa fa-shopping-cart"></i>

                </div>


                <p>
                  Total Orders
                </p>


                <h3>

                  <?php
                  echo $total_orders;
                  ?>

                </h3>

              </div>

            </div>


            <!-- TOTAL CUSTOMERS -->

            <div
              class="col-md-3 col-sm-6 col-xs-12">

              <div class="dashboard-card">

                <div class="card-icon">

                  <i
                    class="fa fa-users"></i>

                </div>


                <p>
                  Total Customers
                </p>


                <h3>

                  <?php
                  echo $total_users;
                  ?>

                </h3>

              </div>

            </div>

          </div>


          <!-- =================================================
                 QUICK ACTIONS
            ================================================== -->

          <div class="row">

            <div class="col-md-12">

              <div class="dashboard-panel">


                <div class="panel-title">

                  <h3>
                    Quick Actions
                  </h3>

                </div>


                <div class="panel-body">

                  <div class="row">


                    <!-- PRODUCTS -->

                    <div
                      class="col-md-3 col-sm-6">

                      <a
                        href="products.php"
                        class="quick-action">

                        <i
                          class="fa fa-leaf"></i>

                        <span>
                          Manage Products
                        </span>

                      </a>

                    </div>


                    <!-- CATEGORIES -->

                    <div
                      class="col-md-3 col-sm-6">

                      <a
                        href="category-management.php"
                        class="quick-action">

                        <i
                          class="fa fa-list"></i>

                        <span>
                          Manage Categories
                        </span>

                      </a>

                    </div>


                    <!-- USERS -->

                    <div
                      class="col-md-3 col-sm-6">

                      <a
                        href="All_user.php"
                        class="quick-action">

                        <i
                          class="fa fa-users"></i>

                        <span>
                          Manage Users
                        </span>

                      </a>

                    </div>


                    <!-- ORDERS -->

                    <div
                      class="col-md-3 col-sm-6">

                      <a
                        href="order-details.php"
                        class="quick-action">

                        <i
                          class="fa fa-shopping-cart"></i>

                        <span>
                          View Orders
                        </span>

                      </a>

                    </div>

                  </div>

                </div>

              </div>

            </div>

          </div>


          <!-- =================================================
                 RECENT ORDERS
            ================================================== -->

          <div class="row">


            <div class="col-md-7 col-sm-12">

              <div class="dashboard-panel">


                <div class="panel-title">

                  <h3>
                    Recent Orders
                  </h3>

                </div>


                <div class="panel-body">


                  <div
                    class="table-responsive">

                    <table
                      class="dashboard-table">

                      <thead>

                        <tr>

                          <th>
                            Order ID
                          </th>

                          <th>
                            Customer
                          </th>

                          <th>
                            Amount
                          </th>

                          <th>
                            Status
                          </th>

                        </tr>

                      </thead>


                      <tbody>


                        <?php if (!empty($recent_orders)): ?>


                          <?php foreach (
                            $recent_orders
                            as $order
                          ): ?>


                            <tr>


                              <!-- ORDER ID -->

                              <td>

                                #
                                <?php
                                echo (int)
                                $order['order_id'];
                                ?>

                              </td>


                              <!-- CUSTOMER -->

                              <td>

                                <?php

                                echo htmlspecialchars(
                                  $order['name']
                                    ?? 'Guest'
                                );

                                ?>

                              </td>


                              <!-- AMOUNT -->

                              <td>

                                ₹<?php

                                  echo number_format(
                                    (float)
                                    (
                                      $order['total_amount'] ?? 0
                                    ),
                                    2
                                  );

                                  ?>

                              </td>


                              <!-- STATUS -->

                              <td>

                                <?php

                                $status =
                                  strtolower(
                                    trim(
                                      $order['order_status']
                                        ?? 'pending'
                                    )
                                  );


                                if (
                                  $status ===
                                  'completed'
                                  ||
                                  $status ===
                                  'delivered'
                                ) {

                                  $status_class =
                                    'status-success';
                                } elseif (
                                  $status ===
                                  'cancelled'
                                  ||
                                  $status ===
                                  'rejected'
                                ) {

                                  $status_class =
                                    'status-danger';
                                } else {

                                  $status_class =
                                    'status-warning';
                                }

                                ?>


                                <span
                                  class="status
                                                        <?php
                                                        echo $status_class;
                                                        ?>">

                                  <?php

                                  echo htmlspecialchars(
                                    ucfirst(
                                      $status
                                    )
                                  );

                                  ?>

                                </span>

                              </td>


                            </tr>


                          <?php endforeach; ?>


                        <?php else: ?>


                          <tr>

                            <td
                              colspan="4"
                              style="
                                                    text-align:center;
                                                    padding:25px;
                                                ">

                              No orders found.

                            </td>

                          </tr>


                        <?php endif; ?>


                      </tbody>

                    </table>

                  </div>

                </div>

              </div>

            </div>


            <!-- =================================================
                     RECENT PRODUCTS
                ================================================== -->

            <div class="col-md-5 col-sm-12">

              <div class="dashboard-panel">


                <div class="panel-title">

                  <h3>
                    Recent Products
                  </h3>

                </div>


                <div class="panel-body">


                  <div
                    class="table-responsive">

                    <table
                      class="dashboard-table">

                      <thead>

                        <tr>

                          <th>
                            Product
                          </th>

                          <th>
                            Price
                          </th>

                          <th>
                            Stock
                          </th>

                        </tr>

                      </thead>


                      <tbody>


                        <?php if (!empty($recent_products)): ?>


                          <?php foreach (
                            $recent_products
                            as $product
                          ): ?>


                            <tr>


                              <!-- PRODUCT -->

                              <td>

                                <?php

                                echo htmlspecialchars(
                                  $product['product_name']
                                );

                                ?>

                              </td>


                              <!-- PRICE -->

                              <td>

                                ₹<?php

                                  echo number_format(
                                    (float)
                                    (
                                      $product['product_price'] ?? 0
                                    ),
                                    2
                                  );

                                  ?>

                              </td>


                              <!-- STOCK -->

                              <td>

                                <?php

                                echo (int)
                                (
                                  $product['stock_quantity'] ?? 0
                                );

                                ?>

                              </td>


                            </tr>


                          <?php endforeach; ?>


                        <?php else: ?>


                          <tr>

                            <td
                              colspan="3"
                              style="
                                                    text-align:center;
                                                    padding:25px;
                                                ">

                              No products found.

                            </td>

                          </tr>


                        <?php endif; ?>


                      </tbody>

                    </table>

                  </div>

                </div>

              </div>

            </div>

          </div>


          <!-- =================================================
                 WELCOME PANEL
            ================================================== -->

          <div class="row">

            <div class="col-md-12">

              <div class="dashboard-panel">




              </div>

            </div>

          </div>


        </div>

      </div>


      <!-- =====================================================
         FOOTER
    ====================================================== -->

      <footer>

        <div class="pull-right">

          Flower Shop Admin Panel

        </div>

        <div class="clearfix"></div>

      </footer>


    </div>

  </div>


  <!-- =========================================================
     JAVASCRIPT
========================================================= -->

  <script
    src="assets/vendors/jquery/dist/jquery.min.js">
  </script>


  <script
    src="assets/vendors/bootstrap/dist/js/bootstrap.min.js">
  </script>


  <script
    src="assets/vendors/fastclick/lib/fastclick.js">
  </script>


  <script
    src="assets/vendors/nprogress/nprogress.js">
  </script>


  <script
    src="assets/js/custom.min.js">
  </script>


</body>

</html>