<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../config/database.php";


/* =========================================================
   CHECK LOGIN
========================================================= */

$login_user_id = $_SESSION['user_id'] ?? 0;

if (empty($login_user_id)) {
    header("Location: ../index.php");
    exit;
}


/* =========================================================
   CHECK ADMIN
========================================================= */

try {

    $stmt = $conn->prepare("
        SELECT
            user_id,
            name,
            phone,
            role,
            status
        FROM users
        WHERE user_id = :user_id
        LIMIT 1
    ");

    $stmt->execute([
        ':user_id' => $login_user_id
    ]);

    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if (
        !$admin ||
        $admin['role'] !== 'admin' ||
        (int)$admin['status'] !== 1
    ) {
        header("Location: ../index.php");
        exit;
    }
} catch (PDOException $e) {

    die("Admin authentication error: " . $e->getMessage());
}


/* =========================================================
   PAGINATION
========================================================= */

$per_page = 10;

$page = isset($_GET['page'])
    ? (int)$_GET['page']
    : 1;

if ($page < 1) {
    $page = 1;
}


/* =========================================================
   COUNT TOTAL ORDERS
========================================================= */

try {

    $count_stmt = $conn->prepare("
        SELECT COUNT(*)
        FROM orders
    ");

    $count_stmt->execute();

    $total_orders = (int)$count_stmt->fetchColumn();
} catch (PDOException $e) {

    die("Order count error: " . $e->getMessage());
}


/* =========================================================
   CALCULATE TOTAL PAGES
========================================================= */

$total_pages = max(
    1,
    (int)ceil($total_orders / $per_page)
);


/* =========================================================
   CHECK PAGE LIMIT
========================================================= */

if ($page > $total_pages) {
    $page = $total_pages;
}


/* =========================================================
   OFFSET
========================================================= */

$offset = ($page - 1) * $per_page;


/* =========================================================
   FETCH ORDERS
========================================================= */

try {

    $sql = "
        SELECT
            o.order_id,
            o.user_id,
            o.total_amount,
            o.order_status,

            u.name,
            u.phone

        FROM orders o

        INNER JOIN users u
            ON u.user_id = o.user_id

        ORDER BY
            o.order_id DESC

        LIMIT :limit
        OFFSET :offset
    ";

    $stmt = $conn->prepare($sql);

    $stmt->bindValue(
        ':limit',
        $per_page,
        PDO::PARAM_INT
    );

    $stmt->bindValue(
        ':offset',
        $offset,
        PDO::PARAM_INT
    );

    $stmt->execute();

    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {

    die("Order fetch error: " . $e->getMessage());
}

?>


<!DOCTYPE html>
<html lang="en">


<head>

    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">

    <meta charset="utf-8">

    <meta
        http-equiv="X-UA-Compatible"
        content="IE=edge">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1">

    <title>
        Orders | Flower Website
    </title>


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


</head>


<body class="nav-md">


    <div class="container body">


        <div class="main_container">


            <!-- =====================================================
         SIDEBAR
    ====================================================== -->

            <?php include "sidebar.php"; ?>


            <!-- =====================================================
         RIGHT CONTENT
    ====================================================== -->

            <div class="right_col" role="main">


                <!-- =================================================
             PAGE TITLE
        ================================================== -->

                <div class="page-title">

                    <div class="title_left">

                        <h3>

                            Orders

                            <small>
                                All Order Details
                            </small>

                        </h3>

                    </div>

                </div>


                <div class="clearfix"></div>


                <!-- =================================================
             ORDERS TABLE
        ================================================== -->

                <div class="row">


                    <div class="col-md-12 col-sm-12 col-xs-12">


                        <div class="x_panel">


                            <div class="x_title">

                                <h2>
                                    All Orders
                                </h2>

                                <div class="clearfix"></div>

                            </div>


                            <div class="x_content">


                                <?php if (count($orders) > 0) { ?>


                                    <div class="table-responsive">


                                        <table
                                            class="table table-striped table-bordered">


                                            <thead>

                                                <tr>

                                                    <th>
                                                        #
                                                    </th>

                                                    <th>
                                                        Order ID
                                                    </th>

                                                    <th>
                                                        User ID
                                                    </th>

                                                    <th>
                                                        User Name
                                                    </th>

                                                    <th>
                                                        Phone
                                                    </th>

                                                    <th>
                                                        Total Amount
                                                    </th>

                                                    <th>
                                                        Order Status
                                                    </th>

                                                    <th>
                                                        Action
                                                    </th>

                                                </tr>

                                            </thead>


                                            <tbody>


                                                <?php

                                                /*
                                     * Serial number
                                     * according to current page
                                     */

                                                $sr_no = $offset + 1;


                                                foreach ($orders as $order) {

                                                    $status = strtolower(
                                                        trim(
                                                            $order['order_status']
                                                        )
                                                    );

                                                ?>


                                                    <tr>


                                                        <!-- SERIAL NUMBER -->

                                                        <td>

                                                            <?php
                                                            echo $sr_no;
                                                            ?>

                                                        </td>


                                                        <!-- ORDER ID -->

                                                        <td>

                                                            <strong>

                                                                #

                                                                <?php
                                                                echo (int)$order['order_id'];
                                                                ?>

                                                            </strong>

                                                        </td>


                                                        <!-- USER ID -->

                                                        <td>

                                                            <?php
                                                            echo (int)$order['user_id'];
                                                            ?>

                                                        </td>


                                                        <!-- USER NAME -->

                                                        <td>

                                                            <?php
                                                            echo htmlspecialchars(
                                                                $order['name']
                                                            );
                                                            ?>

                                                        </td>


                                                        <!-- PHONE -->

                                                        <td>

                                                            <?php
                                                            echo htmlspecialchars(
                                                                $order['phone']
                                                            );
                                                            ?>

                                                        </td>


                                                        <!-- TOTAL AMOUNT -->

                                                        <td>

                                                            <strong>

                                                                ₹

                                                                <?php
                                                                echo number_format(
                                                                    (float)$order['total_amount'],
                                                                    2
                                                                );
                                                                ?>

                                                            </strong>

                                                        </td>


                                                        <!-- ORDER STATUS -->

                                                        <td>


                                                            <?php

                                                            if (
                                                                $status === 'completed' ||
                                                                $status === 'delivered'
                                                            ) {

                                                            ?>

                                                                <span
                                                                    class="label label-success">

                                                                    <?php
                                                                    echo htmlspecialchars(
                                                                        $order['order_status']
                                                                    );
                                                                    ?>

                                                                </span>

                                                            <?php

                                                            } elseif (
                                                                $status === 'cancelled' ||
                                                                $status === 'canceled'
                                                            ) {

                                                            ?>

                                                                <span
                                                                    class="label label-danger">

                                                                    <?php
                                                                    echo htmlspecialchars(
                                                                        $order['order_status']
                                                                    );
                                                                    ?>

                                                                </span>

                                                            <?php

                                                            } elseif (
                                                                $status === 'pending'
                                                            ) {

                                                            ?>

                                                                <span
                                                                    class="label label-warning">

                                                                    <?php
                                                                    echo htmlspecialchars(
                                                                        $order['order_status']
                                                                    );
                                                                    ?>

                                                                </span>

                                                            <?php

                                                            } else {

                                                            ?>

                                                                <span
                                                                    class="label label-info">

                                                                    <?php
                                                                    echo htmlspecialchars(
                                                                        $order['order_status']
                                                                    );
                                                                    ?>

                                                                </span>

                                                            <?php

                                                            }

                                                            ?>


                                                        </td>


                                                        <!-- ACTION -->

                                                        <td>

                                                            <a
                                                                href="order-details.php?order_id=<?php echo (int)$order['order_id']; ?>"
                                                                class="btn btn-primary btn-xs">

                                                                <i class="fa fa-eye"></i>

                                                                View Details

                                                            </a>

                                                        </td>


                                                    </tr>


                                                <?php

                                                    $sr_no++;
                                                }

                                                ?>


                                            </tbody>


                                        </table>


                                    </div>


                                    <!-- =================================================
                                 PAGINATION INFORMATION
                            ================================================== -->

                                    <div class="row">


                                        <div class="col-md-6">

                                            <p style="margin-top:20px;">

                                                Showing

                                                <strong>
                                                    <?php
                                                    echo $offset + 1;
                                                    ?>
                                                </strong>

                                                to

                                                <strong>
                                                    <?php
                                                    echo min(
                                                        $offset + $per_page,
                                                        $total_orders
                                                    );
                                                    ?>
                                                </strong>

                                                of

                                                <strong>
                                                    <?php
                                                    echo $total_orders;
                                                    ?>
                                                </strong>

                                                orders

                                            </p>

                                        </div>


                                        <div class="col-md-6">


                                            <!-- =================================================
                                         PAGINATION BUTTONS
                                    ================================================== -->

                                            <ul
                                                class="pagination pull-right"
                                                style="margin-top:15px;">


                                                <!-- PREVIOUS -->

                                                <?php if ($page > 1) { ?>

                                                    <li>

                                                        <a
                                                            href="?page=<?php echo $page - 1; ?>">

                                                            Previous

                                                        </a>

                                                    </li>

                                                <?php } else { ?>

                                                    <li class="disabled">

                                                        <span>
                                                            Previous
                                                        </span>

                                                    </li>

                                                <?php } ?>


                                                <!-- PAGE NUMBERS -->

                                                <?php

                                                for (
                                                    $i = 1;
                                                    $i <= $total_pages;
                                                    $i++
                                                ) {

                                                ?>


                                                    <li
                                                        class="<?php echo ($i == $page) ? 'active' : ''; ?>">

                                                        <a
                                                            href="?page=<?php echo $i; ?>">

                                                            <?php
                                                            echo $i;
                                                            ?>

                                                        </a>

                                                    </li>


                                                <?php

                                                }

                                                ?>


                                                <!-- NEXT -->

                                                <?php if ($page < $total_pages) { ?>

                                                    <li>

                                                        <a
                                                            href="?page=<?php echo $page + 1; ?>">

                                                            Next

                                                        </a>

                                                    </li>

                                                <?php } else { ?>

                                                    <li class="disabled">

                                                        <span>
                                                            Next
                                                        </span>

                                                    </li>

                                                <?php } ?>


                                            </ul>


                                        </div>


                                    </div>


                                <?php } else { ?>


                                    <!-- =================================================
                                 NO ORDERS
                            ================================================== -->

                                    <div class="alert alert-info">

                                        <i class="fa fa-info-circle"></i>

                                        No orders found.

                                    </div>


                                <?php } ?>


                            </div>


                        </div>


                    </div>


                </div>


            </div>


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