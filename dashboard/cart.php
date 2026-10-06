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
   FETCH USERS WHO HAVE PRODUCTS IN CART
========================================================= */

try {

    $sql = "
        SELECT
            u.user_id,
            u.name,
            u.phone,

            COUNT(DISTINCT c.product_id) AS total_products,

            COALESCE(SUM(c.quantity), 0) AS total_quantity

        FROM cart c

        INNER JOIN users u
            ON u.user_id = c.user_id

        GROUP BY
            u.user_id,
            u.name,
            u.phone

        ORDER BY
            u.user_id DESC
    ";

    $stmt = $conn->prepare($sql);
    $stmt->execute();

    $cart_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {

    die("Cart fetch error: " . $e->getMessage());
}

?>


<!DOCTYPE html>
<html lang="en">


<head>

    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">

    <meta charset="utf-8">

    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Cart | Flower Website</title>


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

    <!-- Custom Theme Style -->
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
                            Cart
                            <small>User Cart Details</small>
                        </h3>

                    </div>

                </div>


                <div class="clearfix"></div>


                <!-- =================================================
             CART TABLE
        ================================================== -->

                <div class="row">


                    <div class="col-md-12 col-sm-12 col-xs-12">


                        <div class="x_panel">


                            <div class="x_title">

                                <h2>
                                    User Cart
                                </h2>

                                <div class="clearfix"></div>

                            </div>


                            <div class="x_content">


                                <?php if (count($cart_users) > 0) { ?>


                                    <div class="table-responsive">


                                        <table
                                            class="table table-striped table-bordered">


                                            <thead>

                                                <tr>

                                                    <th style="width:70px;">
                                                        #
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
                                                        Total Products
                                                    </th>

                                                    <th>
                                                        Total Quantity
                                                    </th>

                                                    <th style="width:120px;">
                                                        Action
                                                    </th>

                                                </tr>

                                            </thead>


                                            <tbody>


                                                <?php

                                                $sr_no = 1;

                                                foreach ($cart_users as $user) {

                                                ?>


                                                    <tr>


                                                        <!-- SERIAL NUMBER -->

                                                        <td>
                                                            <?php echo $sr_no; ?>
                                                        </td>


                                                        <!-- USER ID -->

                                                        <td>
                                                            <?php
                                                            echo htmlspecialchars(
                                                                $user['user_id']
                                                            );
                                                            ?>
                                                        </td>


                                                        <!-- USER NAME -->

                                                        <td>

                                                            <strong>

                                                                <?php
                                                                echo htmlspecialchars(
                                                                    $user['name']
                                                                );
                                                                ?>

                                                            </strong>

                                                        </td>


                                                        <!-- PHONE -->

                                                        <td>

                                                            <?php
                                                            echo htmlspecialchars(
                                                                $user['phone']
                                                            );
                                                            ?>

                                                        </td>


                                                        <!-- DIFFERENT PRODUCTS -->

                                                        <td>

                                                            <span class="label label-info">

                                                                <?php
                                                                echo (int)$user['total_products'];
                                                                ?>

                                                            </span>

                                                        </td>


                                                        <!-- TOTAL QUANTITY -->

                                                        <td>

                                                            <span class="label label-success">

                                                                <?php
                                                                echo (int)$user['total_quantity'];
                                                                ?>

                                                            </span>

                                                        </td>


                                                        <!-- VIEW CART -->

                                                        <td>

                                                            <a
                                                                href="cart-details.php?user_id=<?php echo (int)$user['user_id']; ?>"
                                                                class="btn btn-primary btn-xs">

                                                                <i class="fa fa-shopping-cart"></i>

                                                                View Cart

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


                                <?php } else { ?>


                                    <!-- =================================================
                                 NO CART DATA
                            ================================================== -->

                                    <div class="alert alert-info">

                                        <i class="fa fa-info-circle"></i>

                                        No user has any product in the cart.

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

    <!-- jQuery -->
    <script
        src="assets/vendors/jquery/dist/jquery.min.js">
    </script>


    <!-- Bootstrap -->
    <script
        src="assets/vendors/bootstrap/dist/js/bootstrap.min.js">
    </script>


    <!-- FastClick -->
    <script
        src="assets/vendors/fastclick/lib/fastclick.js">
    </script>


    <!-- NProgress -->
    <script
        src="assets/vendors/nprogress/nprogress.js">
    </script>


    <!-- Custom Theme -->
    <script
        src="assets/js/custom.min.js">
    </script>


</body>

</html>