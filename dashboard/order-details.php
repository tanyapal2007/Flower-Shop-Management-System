
<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../config/database.php";


/* =========================================================
   CHECK LOGIN
========================================================= */

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$admin_id = (int) $_SESSION['user_id'];


/* =========================================================
   CHECK ADMIN
========================================================= */

$stmt = $conn->prepare("
    SELECT
        name,
        role
    FROM users
    WHERE user_id = :user_id
");

$stmt->execute([
    ':user_id' => $admin_id
]);

$admin = $stmt->fetch(PDO::FETCH_ASSOC);


if (!$admin || $admin['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}


/* =========================================================
   SELECTED USER
========================================================= */

$selected_user_id = isset($_GET['user_id'])
    ? (int) $_GET['user_id']
    : 0;


/* =========================================================
   VARIABLES
========================================================= */

$user_orders = [];
$selected_user = null;

$total_users = 0;
$total_users_with_orders = 0;
$total_orders = 0;
$total_sales = 0;

$page_title = "User Orders";


/* =========================================================
   PARTICULAR USER ORDERS
========================================================= */

if ($selected_user_id > 0) {


    /* =====================================================
       FETCH SELECTED USER
    ====================================================== */

    $user_stmt = $conn->prepare("
        SELECT
            user_id,
            name,
            phone
        FROM users
        WHERE user_id = :user_id
          AND role = 'user'
    ");

    $user_stmt->execute([
        ':user_id' => $selected_user_id
    ]);

    $selected_user = $user_stmt->fetch(PDO::FETCH_ASSOC);


    /* =====================================================
       USER NOT FOUND
    ====================================================== */

    if (!$selected_user) {

        header("Location: order-details.php");
        exit;
    }


    /* =====================================================
       FETCH USER ORDERS

       IMPORTANT:
       orders table only uses:
       order_id
       user_id
       total_amount
       order_status

       NO product_id
       NO order_details
       NO order_date
    ====================================================== */

    $order_stmt = $conn->prepare("
        SELECT
            order_id,
            user_id,
            total_amount,
            order_status
        FROM orders
        WHERE user_id = :user_id
        ORDER BY order_id DESC
    ");

    $order_stmt->execute([
        ':user_id' => $selected_user_id
    ]);

    $user_orders = $order_stmt->fetchAll(PDO::FETCH_ASSOC);


    /* =====================================================
       USER TOTAL
    ====================================================== */

    foreach ($user_orders as $order) {

        $total_sales += (float) (
            $order['total_amount'] ?? 0
        );
    }


    $total_orders = count($user_orders);

    $page_title = "Orders - " . $selected_user['name'];
}


/* =========================================================
   ALL USERS ORDER SUMMARY
========================================================= */ else {


    /* =====================================================
       TOTAL REGISTERED USERS
    ====================================================== */

    $stmt = $conn->query("
        SELECT COUNT(*)
        FROM users
        WHERE role = 'user'
    ");

    $total_users = (int) $stmt->fetchColumn();


    /* =====================================================
       USERS WHO HAVE PLACED ORDERS
    ====================================================== */

    $stmt = $conn->query("
        SELECT COUNT(DISTINCT u.user_id)
        FROM users u

        INNER JOIN orders o
            ON o.user_id = u.user_id

        WHERE u.role = 'user'
    ");

    $total_users_with_orders = (int) $stmt->fetchColumn();


    /* =====================================================
       TOTAL ORDERS
    ====================================================== */

    $stmt = $conn->query("
        SELECT COUNT(*)
        FROM orders
    ");

    $total_orders = (int) $stmt->fetchColumn();


    /* =====================================================
       TOTAL SALES
    ====================================================== */

    $stmt = $conn->query("
        SELECT
            COALESCE(
                SUM(total_amount),
                0
            )
        FROM orders
    ");

    $total_sales = (float) $stmt->fetchColumn();


    /* =====================================================
       USER-WISE ORDER SUMMARY
    ====================================================== */

    $sql = "
        SELECT

            u.user_id,
            u.name,
            u.phone,

            COUNT(o.order_id) AS total_orders,

            COALESCE(
                SUM(o.total_amount),
                0
            ) AS total_amount,

            COUNT(
                CASE
                    WHEN LOWER(
                        COALESCE(o.order_status, '')
                    ) = 'pending'
                    THEN 1
                END
            ) AS pending_orders,

            COUNT(
                CASE
                    WHEN LOWER(
                        COALESCE(o.order_status, '')
                    ) IN (
                        'completed',
                        'delivered'
                    )
                    THEN 1
                END
            ) AS completed_orders

        FROM users u

        INNER JOIN orders o
            ON o.user_id = u.user_id

        WHERE u.role = 'user'

        GROUP BY
            u.user_id,
            u.name,
            u.phone

        ORDER BY
            total_orders DESC,
            u.name ASC
    ";

    $stmt = $conn->query($sql);

    $user_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="utf-8">

    <meta
        http-equiv="X-UA-Compatible"
        content="IE=edge">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1">

    <title>
        <?php echo htmlspecialchars($page_title); ?>
    </title>


    <!-- =====================================================
         BOOTSTRAP
    ====================================================== -->

    <link
        href="assets/vendors/bootstrap/dist/css/bootstrap.min.css"
        rel="stylesheet">


    <!-- =====================================================
         FONT AWESOME
    ====================================================== -->

    <link
        href="assets/vendors/font-awesome/css/font-awesome.min.css"
        rel="stylesheet">


    <!-- =====================================================
         NPROGRESS
    ====================================================== -->

    <link
        href="assets/vendors/nprogress/nprogress.css"
        rel="stylesheet">


    <!-- =====================================================
         GENTELELLA
    ====================================================== -->

    <link
        href="assets/css/custom.min.css"
        rel="stylesheet">


    <style>
        /* =====================================================
           PAGE
        ====================================================== */

        .orders-page {
            padding: 20px;
        }


        .page-title {
            margin-bottom: 20px;
        }


        .page-title h2 {
            margin: 0;

            font-weight: 600;
        }


        .page-title p {
            margin-top: 7px;

            color: #777;
        }


        /* =====================================================
           SUMMARY CARDS
        ====================================================== */

        .summary-card {
            background: #ffffff;

            border: 1px solid #e5e5e5;

            padding: 20px;

            margin-bottom: 20px;

            min-height: 95px;
        }


        .summary-icon {
            float: left;

            width: 52px;
            height: 52px;

            line-height: 52px;

            text-align: center;

            background: #f5f5f5;

            font-size: 22px;
        }


        .summary-content {
            margin-left: 68px;
        }


        .summary-content h3 {
            margin: 0 0 5px 0;

            font-size: 25px;

            font-weight: 600;
        }


        .summary-content span {
            color: #777;

            font-size: 13px;
        }


        /* =====================================================
           CONTENT CARD
        ====================================================== */

        .content-card {
            background: #ffffff;

            border: 1px solid #e5e5e5;

            padding: 20px;

            margin-bottom: 20px;
        }


        .content-card-header {
            min-height: 45px;

            border-bottom: 1px solid #eee;

            margin-bottom: 15px;
        }


        .content-card-header h3 {
            margin: 0;

            font-size: 19px;

            font-weight: 600;
        }


        /* =====================================================
           TABLE
        ====================================================== */

        .orders-table {
            width: 100%;

            margin-bottom: 0;
        }


        .orders-table th {
            background: #f7f7f7;

            border: 1px solid #ddd !important;

            padding: 12px;

            font-weight: 600;

            white-space: nowrap;
        }


        .orders-table td {
            border: 1px solid #eee !important;

            padding: 12px;

            vertical-align: middle !important;
        }


        .user-name {
            font-weight: 600;
        }


        .phone {
            color: #777;
        }


        .order-count {
            display: inline-block;

            min-width: 35px;

            padding: 5px 10px;

            text-align: center;

            background: #f5f5f5;

            border-radius: 15px;

            font-weight: 600;
        }


        .amount {
            font-weight: 600;
        }


        /* =====================================================
           STATUS
        ====================================================== */

        .status {
            display: inline-block;

            padding: 5px 10px;

            border-radius: 4px;

            font-size: 12px;

            text-transform: capitalize;
        }


        .status-pending {
            background: #fff3cd;

            color: #856404;
        }


        .status-completed {
            background: #d4edda;

            color: #155724;
        }


        .status-cancelled {
            background: #f8d7da;

            color: #721c24;
        }


        .status-other {
            background: #e9ecef;

            color: #495057;
        }


        /* =====================================================
           SELECTED USER
        ====================================================== */

        .selected-user-box {
            background: #f8f9fa;

            border: 1px solid #e5e5e5;

            padding: 15px;

            margin-bottom: 20px;
        }


        .selected-user-box h4 {
            margin: 0 0 8px 0;

            font-weight: 600;
        }


        .selected-user-box p {
            margin: 4px 0;

            color: #666;
        }


        /* =====================================================
           EMPTY
        ====================================================== */

        .empty-box {
            text-align: center;

            padding: 50px 20px;

            color: #777;
        }


        .empty-box i {
            font-size: 45px;

            color: #ccc;

            margin-bottom: 15px;
        }


        /* =====================================================
           PRINT
        ====================================================== */

        @media print {

            .no-print,
            .left_col,
            .top_nav,
            footer {
                display: none !important;
            }


            .right_col {
                margin-left: 0 !important;

                width: 100% !important;
            }


            .orders-page {
                padding: 0 !important;
            }

        }
    </style>

</head>


<body class="nav-md">


    <div class="container body">

        <div class="main_container">


            <!-- =================================================
             SIDEBAR
        ================================================== -->

            <?php include 'sidebar.php'; ?>


            <!-- =================================================
             TOP NAVIGATION
        ================================================== -->

            <div class="top_nav no-print">

                <div class="nav_menu">

                    <nav>

                        <div class="nav toggle">

                            <a id="menu_toggle">

                                <i class="fa fa-bars"></i>

                            </a>

                        </div>


                        <ul class="nav navbar-nav navbar-right">

                            <li>

                                <a
                                    href="javascript:;"
                                    class="user-profile dropdown-toggle"
                                    data-toggle="dropdown"
                                    aria-expanded="false">

                                    <?php
                                    echo htmlspecialchars(
                                        $admin['name'] ?? 'Admin'
                                    );
                                    ?>

                                    <span class="fa fa-angle-down"></span>

                                </a>


                                <ul
                                    class="dropdown-menu dropdown-usermenu pull-right">

                                    <li>

                                        <a href="../index.php">

                                            <i class="fa fa-home pull-right"></i>

                                            View Website

                                        </a>

                                    </li>


                                    <li>

                                        <a href="../login.php">

                                            <i class="fa fa-sign-out pull-right"></i>

                                            Log Out

                                        </a>

                                    </li>

                                </ul>

                            </li>

                        </ul>

                    </nav>

                </div>

            </div>


            <!-- =================================================
             MAIN CONTENT
        ================================================== -->

            <div
                class="right_col"
                role="main">


                <div class="orders-page">


                    <?php if ($selected_user_id <= 0): ?>


                        <!-- =========================================
                         MAIN USER SUMMARY
                    ========================================== -->

                        <div class="page-title">

                            <h2>

                                <i class="fa fa-users"></i>

                                User Orders

                            </h2>


                            <p>

                                See how many orders each user has placed.

                            </p>

                        </div>


                        <!-- =========================================
                         SUMMARY CARDS
                    ========================================== -->

                        <div class="row">


                            <!-- TOTAL USERS -->

                            <div class="col-md-3 col-sm-6">

                                <div class="summary-card">

                                    <div class="summary-icon">

                                        <i class="fa fa-users"></i>

                                    </div>


                                    <div class="summary-content">

                                        <h3>
                                            <?php echo $total_users; ?>
                                        </h3>

                                        <span>
                                            Total Users
                                        </span>

                                    </div>


                                    <div style="clear: both;"></div>

                                </div>

                            </div>


                            <!-- USERS WITH ORDERS -->

                            <div class="col-md-3 col-sm-6">

                                <div class="summary-card">

                                    <div class="summary-icon">

                                        <i class="fa fa-user"></i>

                                    </div>


                                    <div class="summary-content">

                                        <h3>
                                            <?php echo $total_users_with_orders; ?>
                                        </h3>

                                        <span>
                                            Users With Orders
                                        </span>

                                    </div>


                                    <div style="clear: both;"></div>

                                </div>

                            </div>


                            <!-- TOTAL ORDERS -->

                            <div class="col-md-3 col-sm-6">

                                <div class="summary-card">

                                    <div class="summary-icon">

                                        <i class="fa fa-shopping-cart"></i>

                                    </div>


                                    <div class="summary-content">

                                        <h3>
                                            <?php echo $total_orders; ?>
                                        </h3>

                                        <span>
                                            Total Orders
                                        </span>

                                    </div>


                                    <div style="clear: both;"></div>

                                </div>

                            </div>


                            <!-- TOTAL SALES -->

                            <div class="col-md-3 col-sm-6">

                                <div class="summary-card">

                                    <div class="summary-icon">

                                        <i class="fa fa-inr"></i>

                                    </div>


                                    <div class="summary-content">

                                        <h3>

                                            ₹<?php
                                                echo number_format(
                                                    $total_sales,
                                                    2
                                                );
                                                ?>

                                        </h3>

                                        <span>
                                            Total Order Amount
                                        </span>

                                    </div>


                                    <div style="clear: both;"></div>

                                </div>

                            </div>


                        </div>


                        <!-- =========================================
                         USER-WISE TABLE
                    ========================================== -->

                        <div class="content-card">


                            <div class="content-card-header">

                                <div class="row">

                                    <div class="col-md-8">

                                        <h3>

                                            <i class="fa fa-list"></i>

                                            Orders By User

                                        </h3>

                                    </div>


                                    <div
                                        class="col-md-4 text-right no-print">

                                        <button
                                            type="button"
                                            class="btn btn-default btn-sm"
                                            onclick="window.print();">

                                            <i class="fa fa-print"></i>

                                            Print

                                        </button>

                                    </div>

                                </div>

                            </div>


                            <?php if (!empty($user_orders)): ?>


                                <div class="table-responsive">

                                    <table
                                        class="table orders-table">

                                        <thead>

                                            <tr>

                                                <th>
                                                    #
                                                </th>

                                                <th>
                                                    User
                                                </th>

                                                <th>
                                                    Phone
                                                </th>

                                                <th>
                                                    Total Orders
                                                </th>

                                                <th>
                                                    Pending
                                                </th>

                                                <th>
                                                    Completed
                                                </th>

                                                <th>
                                                    Total Amount
                                                </th>

                                                <th class="no-print">
                                                    Action
                                                </th>

                                            </tr>

                                        </thead>


                                        <tbody>

                                            <?php $sr_no = 1; ?>


                                            <?php foreach (
                                                $user_orders as $user
                                            ): ?>


                                                <tr>


                                                    <!-- NUMBER -->

                                                    <td>

                                                        <?php
                                                        echo $sr_no++;
                                                        ?>

                                                    </td>


                                                    <!-- USER -->

                                                    <td>

                                                        <span class="user-name">

                                                            <?php
                                                            echo htmlspecialchars(
                                                                $user['name']
                                                            );
                                                            ?>

                                                        </span>

                                                    </td>


                                                    <!-- PHONE -->

                                                    <td>

                                                        <span class="phone">

                                                            <?php
                                                            echo htmlspecialchars(
                                                                $user['phone']
                                                            );
                                                            ?>

                                                        </span>

                                                    </td>


                                                    <!-- TOTAL ORDERS -->

                                                    <td>

                                                        <span class="order-count">

                                                            <?php
                                                            echo (int)
                                                            $user['total_orders'];
                                                            ?>

                                                        </span>

                                                    </td>


                                                    <!-- PENDING -->

                                                    <td>

                                                        <?php
                                                        echo (int)
                                                        $user['pending_orders'];
                                                        ?>

                                                    </td>


                                                    <!-- COMPLETED -->

                                                    <td>

                                                        <?php
                                                        echo (int)
                                                        $user['completed_orders'];
                                                        ?>

                                                    </td>


                                                    <!-- TOTAL AMOUNT -->

                                                    <td>

                                                        <span class="amount">

                                                            ₹<?php
                                                                echo number_format(
                                                                    (float)
                                                                    $user['total_amount'],
                                                                    2
                                                                );
                                                                ?>

                                                        </span>

                                                    </td>


                                                    <!-- VIEW -->

                                                    <td class="no-print">

                                                        <a
                                                            href="order-details.php?user_id=<?php echo (int) $user['user_id']; ?>"
                                                            class="btn btn-primary btn-sm">

                                                            <i class="fa fa-eye"></i>

                                                            View Orders

                                                        </a>

                                                    </td>


                                                </tr>


                                            <?php endforeach; ?>

                                        </tbody>

                                    </table>

                                </div>


                            <?php else: ?>


                                <div class="empty-box">

                                    <i class="fa fa-shopping-cart"></i>

                                    <h4>
                                        No Orders Found
                                    </h4>

                                    <p>
                                        No user has placed an order yet.
                                    </p>

                                </div>


                            <?php endif; ?>


                        </div>


                    <?php else: ?>


                        <!-- =========================================
                         PARTICULAR USER ORDERS
                    ========================================== -->

                        <div class="page-title">

                            <h2>

                                <i class="fa fa-shopping-cart"></i>

                                User Orders

                            </h2>


                            <p>
                                All orders placed by this user.
                            </p>

                        </div>


                        <!-- =========================================
                         SELECTED USER INFORMATION
                    ========================================== -->

                        <div class="selected-user-box">

                            <h4>

                                <i class="fa fa-user"></i>

                                <?php
                                echo htmlspecialchars(
                                    $selected_user['name']
                                );
                                ?>

                            </h4>


                            <p>

                                <strong>
                                    Phone:
                                </strong>

                                <?php
                                echo htmlspecialchars(
                                    $selected_user['phone']
                                );
                                ?>

                            </p>


                            <p>

                                <strong>
                                    Total Orders:
                                </strong>

                                <?php
                                echo $total_orders;
                                ?>

                            </p>


                            <p>

                                <strong>
                                    Total Amount:
                                </strong>

                                ₹<?php
                                    echo number_format(
                                        $total_sales,
                                        2
                                    );
                                    ?>

                            </p>

                        </div>


                        <!-- =========================================
                         BUTTONS
                    ========================================== -->

                        <div
                            class="no-print"
                            style="margin-bottom: 15px;">

                            <a
                                href="order-details.php"
                                class="btn btn-default">

                                <i class="fa fa-arrow-left"></i>

                                Back To User Orders

                            </a>


                            <button
                                type="button"
                                class="btn btn-default"
                                onclick="window.print();">

                                <i class="fa fa-print"></i>

                                <i class="fa fa-print"></i>

                                Print

                            </button>

                        </div>


                        <!-- =========================================
                         ORDER LIST
                    ========================================== -->

                        <div class="content-card">


                            <div class="content-card-header">

                                <h3>

                                    <i class="fa fa-list"></i>

                                    Order List

                                </h3>

                            </div>


                            <?php if (!empty($user_orders)): ?>


                                <div class="table-responsive">

                                    <table
                                        class="table orders-table">

                                        <thead>

                                            <tr>

                                                <th>
                                                    #
                                                </th>

                                                <th>
                                                    Order ID
                                                </th>

                                                <th>
                                                    User
                                                </th>

                                                <th>
                                                    Total Amount
                                                </th>

                                                <th>
                                                    Status
                                                </th>

                                            </tr>

                                        </thead>


                                        <tbody>

                                            <?php $order_sr = 1; ?>


                                            <?php foreach (
                                                $user_orders as $order
                                            ): ?>


                                                <?php

                                                $status = strtolower(
                                                    trim(
                                                        $order['order_status'] ?? ''
                                                    )
                                                );


                                                $status_class =
                                                    'status-other';


                                                if (
                                                    $status === 'pending'
                                                ) {

                                                    $status_class =
                                                        'status-pending';
                                                } elseif (
                                                    $status === 'completed'
                                                    ||
                                                    $status === 'delivered'
                                                ) {

                                                    $status_class =
                                                        'status-completed';
                                                } elseif (
                                                    $status === 'cancelled'
                                                    ||
                                                    $status === 'canceled'
                                                ) {

                                                    $status_class =
                                                        'status-cancelled';
                                                }

                                                ?>


                                                <tr>


                                                    <!-- NUMBER -->

                                                    <td>

                                                        <?php
                                                        echo $order_sr++;
                                                        ?>

                                                    </td>


                                                    <!-- ORDER ID -->

                                                    <td>

                                                        <strong>

                                                            #<?php
                                                                echo (int)
                                                                $order['order_id'];
                                                                ?>

                                                        </strong>

                                                    </td>


                                                    <!-- USER -->

                                                    <td>

                                                        <?php
                                                        echo htmlspecialchars(
                                                            $selected_user['name']
                                                        );
                                                        ?>

                                                    </td>


                                                    <!-- TOTAL -->

                                                    <td>

                                                        <strong>

                                                            ₹<?php
                                                                echo number_format(
                                                                    (float)
                                                                    $order['total_amount'],
                                                                    2
                                                                );
                                                                ?>

                                                        </strong>

                                                    </td>


                                                    <!-- STATUS -->

                                                    <td>

                                                        <span
                                                            class="status <?php echo $status_class; ?>">

                                                            <?php

                                                            if ($status !== '') {

                                                                echo htmlspecialchars(
                                                                    $status
                                                                );
                                                            } else {

                                                                echo 'Unknown';
                                                            }

                                                            ?>

                                                        </span>

                                                    </td>


                                                </tr>


                                            <?php endforeach; ?>


                                        </tbody>

                                    </table>

                                </div>


                            <?php else: ?>


                                <div class="empty-box">

                                    <i class="fa fa-shopping-cart"></i>

                                    <h4>
                                        No Orders Found
                                    </h4>

                                    <p>
                                        This user has not placed any orders.
                                    </p>

                                </div>


                            <?php endif; ?>


                        </div>


                    <?php endif; ?>


                </div>

            </div>


            <!-- =================================================
             FOOTER
        ================================================== -->

            <footer class="no-print">

                <div class="pull-right">

                    Flower Shop Admin Panel

                </div>

                <div class="clearfix"></div>

            </footer>


        </div>

    </div>


    <!-- =====================================================
     JAVASCRIPT
====================================================== -->

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
