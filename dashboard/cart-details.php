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
   GET USER ID
========================================================= */

$user_id = isset($_GET['user_id'])
    ? (int)$_GET['user_id']
    : 0;


if ($user_id <= 0) {

    header("Location: cart.php");
    exit;
}


/* =========================================================
   FETCH USER
========================================================= */

try {

    $stmt = $conn->prepare("
        SELECT
            user_id,
            name,
            phone
        FROM users
        WHERE user_id = :user_id
        LIMIT 1
    ");

    $stmt->execute([
        ':user_id' => $user_id
    ]);

    $cart_user = $stmt->fetch(PDO::FETCH_ASSOC);


    if (!$cart_user) {

        header("Location: cart.php");
        exit;
    }
} catch (PDOException $e) {

    die("User fetch error: " . $e->getMessage());
}


/* =========================================================
   FETCH USER CART PRODUCTS
========================================================= */

try {

    $sql = "
        SELECT
            c.cart_id,
            c.user_id,
            c.product_id,
            c.quantity,

            p.product_name,
            p.product_code,
            p.product_image,

            COALESCE(
                pp.selling_price,
                p.product_price,
                0
            ) AS selling_price

        FROM cart c

        INNER JOIN products p
            ON p.product_id = c.product_id

        LEFT JOIN LATERAL
        (
            SELECT
                product_prices.selling_price
            FROM product_prices
            WHERE product_prices.product_id = p.product_id
            ORDER BY product_prices.price_id DESC
            LIMIT 1
        ) pp
            ON TRUE

        WHERE c.user_id = :user_id

        ORDER BY c.cart_id DESC
    ";

    $stmt = $conn->prepare($sql);

    $stmt->execute([
        ':user_id' => $user_id
    ]);

    $cart_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {

    die("Cart fetch error: " . $e->getMessage());
}


/* =========================================================
   CALCULATE TOTAL
========================================================= */

$grand_total = 0;

foreach ($cart_products as $item) {

    $price = (float)$item['selling_price'];

    $quantity = (int)$item['quantity'];

    $item_total = $price * $quantity;

    $grand_total += $item_total;
}

?>


<!DOCTYPE html>
<html lang="en">


<head>

    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">

    <meta charset="utf-8">

    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1">

    <title>
        Cart Details | Flower Website
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


    <style>
        .product-image {

            width: 70px;

            height: 70px;

            object-fit: cover;

            border-radius: 5px;

            border: 1px solid #ddd;

        }

        .user-info-box {

            background: #f7f7f7;

            padding: 15px;

            margin-bottom: 20px;

            border-radius: 4px;

        }

        .total-box {

            text-align: right;

            font-size: 18px;

            font-weight: bold;

            padding: 15px;

        }
    </style>


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

                            Cart Details

                            <small>
                                User Cart Products
                            </small>

                        </h3>

                    </div>

                </div>


                <div class="clearfix"></div>


                <!-- =================================================
             USER INFORMATION
        ================================================== -->

                <div class="row">

                    <div class="col-md-12">


                        <div class="x_panel">


                            <div class="x_title">

                                <h2>
                                    User Information
                                </h2>

                                <div class="clearfix"></div>

                            </div>


                            <div class="x_content">


                                <div class="user-info-box">


                                    <div class="row">


                                        <div class="col-md-4">

                                            <strong>
                                                User ID:
                                            </strong>

                                            <?php
                                            echo htmlspecialchars(
                                                $cart_user['user_id']
                                            );
                                            ?>

                                        </div>


                                        <div class="col-md-4">

                                            <strong>
                                                Name:
                                            </strong>

                                            <?php
                                            echo htmlspecialchars(
                                                $cart_user['name']
                                            );
                                            ?>

                                        </div>


                                        <div class="col-md-4">

                                            <strong>
                                                Phone:
                                            </strong>

                                            <?php
                                            echo htmlspecialchars(
                                                $cart_user['phone']
                                            );
                                            ?>

                                        </div>


                                    </div>


                                </div>


                            </div>


                        </div>


                    </div>

                </div>


                <!-- =================================================
             CART PRODUCTS
        ================================================== -->

                <div class="row">

                    <div class="col-md-12">


                        <div class="x_panel">


                            <div class="x_title">

                                <h2>
                                    Cart Products
                                </h2>

                                <div class="clearfix"></div>

                            </div>


                            <div class="x_content">


                                <?php if (count($cart_products) > 0) { ?>


                                    <div class="table-responsive">


                                        <table
                                            class="table table-striped table-bordered">


                                            <thead>

                                                <tr>

                                                    <th>
                                                        #
                                                    </th>

                                                    <th>
                                                        Image
                                                    </th>

                                                    <th>
                                                        Product Name
                                                    </th>

                                                    <th>
                                                        Product Code
                                                    </th>

                                                    <th>
                                                        Price
                                                    </th>

                                                    <th>
                                                        Quantity
                                                    </th>

                                                    <th>
                                                        Total
                                                    </th>

                                                </tr>

                                            </thead>


                                            <tbody>


                                                <?php

                                                $sr_no = 1;

                                                foreach ($cart_products as $item) {

                                                    $price =
                                                        (float)$item['selling_price'];

                                                    $quantity =
                                                        (int)$item['quantity'];

                                                    $item_total =
                                                        $price * $quantity;

                                                ?>


                                                    <tr>


                                                        <!-- SERIAL NUMBER -->

                                                        <td>

                                                            <?php
                                                            echo $sr_no;
                                                            ?>

                                                        </td>


                                                        <!-- PRODUCT IMAGE -->

                                                        <td>

                                                            <?php

                                                            $image_name =
                                                                $item['product_image'] ?? '';

                                                            $image_path =
                                                                "../uploads/products/"
                                                                . $image_name;

                                                            if (
                                                                !empty($image_name) &&
                                                                file_exists(
                                                                    __DIR__
                                                                        . "/../uploads/products/"
                                                                        . $image_name
                                                                )
                                                            ) {

                                                            ?>

                                                                <img
                                                                    src="<?php
                                                                            echo htmlspecialchars(
                                                                                $image_path
                                                                            );
                                                                            ?>"
                                                                    class="product-image"
                                                                    alt="Product">

                                                            <?php

                                                            } else {

                                                            ?>

                                                                <span
                                                                    class="label label-default">
                                                                    No Image
                                                                </span>

                                                            <?php

                                                            }

                                                            ?>

                                                        </td>


                                                        <!-- PRODUCT NAME -->

                                                        <td>

                                                            <strong>

                                                                <?php
                                                                echo htmlspecialchars(
                                                                    $item['product_name']
                                                                );
                                                                ?>

                                                            </strong>

                                                        </td>


                                                        <!-- PRODUCT CODE -->

                                                        <td>

                                                            <?php

                                                            echo htmlspecialchars(
                                                                $item['product_code'] ?? '-'
                                                            );

                                                            ?>

                                                        </td>


                                                        <!-- PRICE -->

                                                        <td>

                                                            ₹
                                                            <?php
                                                            echo number_format(
                                                                $price,
                                                                2
                                                            );
                                                            ?>

                                                        </td>


                                                        <!-- QUANTITY -->

                                                        <td>

                                                            <span
                                                                class="label label-info">

                                                                <?php
                                                                echo $quantity;
                                                                ?>

                                                            </span>

                                                        </td>


                                                        <!-- TOTAL -->

                                                        <td>

                                                            <strong>

                                                                ₹
                                                                <?php
                                                                echo number_format(
                                                                    $item_total,
                                                                    2
                                                                );
                                                                ?>

                                                            </strong>

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
                                 GRAND TOTAL
                            ================================================== -->

                                    <div class="row">

                                        <div class="col-md-8">
                                        </div>


                                        <div class="col-md-4">


                                            <div class="total-box">

                                                Total Cart Amount:

                                                <span>

                                                    ₹
                                                    <?php
                                                    echo number_format(
                                                        $grand_total,
                                                        2
                                                    );
                                                    ?>

                                                </span>

                                            </div>


                                        </div>

                                    </div>


                                <?php } else { ?>


                                    <!-- =================================================
                                 EMPTY CART
                            ================================================== -->

                                    <div class="alert alert-info">

                                        <i class="fa fa-info-circle"></i>

                                        This user has no products in the cart.

                                    </div>


                                <?php } ?>


                                <!-- =================================================
                             BACK BUTTON
                        ================================================== -->

                                <div style="margin-top:15px;">

                                    <a
                                        href="cart.php"
                                        class="btn btn-default">

                                        <i class="fa fa-arrow-left"></i>

                                        Back to Cart

                                    </a>

                                </div>


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