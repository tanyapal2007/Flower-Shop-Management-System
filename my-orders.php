
<?php

/* =========================================================
   SESSION START
========================================================= */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/* =========================================================
   DATABASE CONNECTION
========================================================= */

require_once "config/database.php";


/* =========================================================
   CHECK LOGIN
========================================================= */

if (!isset($_SESSION['user_id'])) {

    header("Location: login.php");
    exit;
}


$user_id = (int) $_SESSION['user_id'];


/* =========================================================
   FETCH USER DETAILS
========================================================= */

$stmt = $conn->prepare("
    SELECT
        user_id,
        name,
        email,
        phone
    FROM users
    WHERE user_id = :user_id
    LIMIT 1
");

$stmt->execute([
    ':user_id' => $user_id
]);

$user = $stmt->fetch(PDO::FETCH_ASSOC);


if (!$user) {

    session_destroy();

    header("Location: login.php");
    exit;
}


/* =========================================================
   FETCH MY ORDERS
========================================================= */

$stmt = $conn->prepare("
    SELECT
        o.order_id,
        o.total_amount,
        o.order_status,
        o.created_at,

        COUNT(oi.order_item_id) AS total_products,

        COALESCE(
            SUM(oi.quantity),
            0
        ) AS total_quantity

    FROM orders o

    LEFT JOIN order_items oi
        ON oi.order_id = o.order_id

    WHERE o.user_id = :user_id

    GROUP BY
        o.order_id,
        o.total_amount,
        o.order_status,
        o.created_at

    ORDER BY
        o.created_at DESC
");

$stmt->execute([
    ':user_id' => $user_id
]);

$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);


/* =========================================================
   PROFILE UPDATE MESSAGE
========================================================= */

$profile_updated =
    isset($_GET['profile_updated']) &&
    $_GET['profile_updated'] == '1';

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>My Orders - Fior</title>


    <!-- =====================================================
         BOOTSTRAP
    ====================================================== -->

    <link
        rel="stylesheet"
        href="css/bootstrap.css">


    <!-- =====================================================
         MAIN CSS
    ====================================================== -->

    <link
        rel="stylesheet"
        href="css/style.css">


    <!-- =====================================================
         RESPONSIVE CSS
    ====================================================== -->

    <link
        rel="stylesheet"
        href="css/responsive.css">


    <!-- =====================================================
         FONT AWESOME
    ====================================================== -->

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">


    <style>
        /* =====================================================
           ORDERS SECTION
        ===================================================== */

        .orders_section {

            padding: 70px 0;

            background: #fafafa;

            min-height: 700px;
        }


        .orders_container {

            max-width: 1150px;

            margin: 0 auto;
        }


        /* =====================================================
           PAGE TITLE
        ===================================================== */

        .orders_title {

            text-align: center;

            margin-bottom: 35px;
        }


        .orders_title h2 {

            font-size: 32px;

            font-weight: 700;

            color: #222;

            margin-bottom: 8px;
        }


        .orders_title p {

            color: #777;

            margin: 0;
        }


        /* =====================================================
           SUCCESS MESSAGE
        ===================================================== */

        .success_message {

            background: #d1e7dd;

            color: #0f5132;

            border: 1px solid #badbcc;

            padding: 12px 15px;

            border-radius: 5px;

            margin-bottom: 25px;

            font-size: 14px;
        }


        /* =====================================================
           ORDERS TABLE BOX
        ===================================================== */

        .orders_table_box {

            background: #fff;

            border-radius: 10px;

            padding: 0;

            box-shadow:
                0 3px 15px rgba(0, 0, 0, 0.07);

            overflow: hidden;
        }


        /* =====================================================
           TABLE RESPONSIVE
        ===================================================== */

        .table_responsive {

            width: 100%;

            overflow-x: auto;
        }


        /* =====================================================
           ORDERS TABLE
        ===================================================== */

        .orders_table {

            width: 100%;

            border-collapse: collapse;

            margin: 0;

            min-width: 850px;
        }


        /* =====================================================
           TABLE HEADER
        ===================================================== */

        .orders_table thead {

            background: #df2f68;

            color: #fff;
        }


        .orders_table thead th {

            padding: 16px 15px;

            font-size: 13px;

            font-weight: 600;

            text-align: left;

            white-space: nowrap;

            border: none;
        }


        /* =====================================================
           TABLE BODY
        ===================================================== */

        .orders_table tbody tr {

            border-bottom: 1px solid #eee;

            transition: 0.2s;
        }


        .orders_table tbody tr:last-child {

            border-bottom: none;
        }


        .orders_table tbody tr:hover {

            background: #fff8fa;
        }


        .orders_table tbody td {

            padding: 17px 15px;

            font-size: 13px;

            color: #444;

            vertical-align: middle;
        }


        /* =====================================================
           ORDER ID
        ===================================================== */

        .order_id {

            font-weight: 700;

            color: #df2f68;

            white-space: nowrap;
        }


        /* =====================================================
           DATE
        ===================================================== */

        .order_date {

            color: #666;

            white-space: nowrap;
        }


        /* =====================================================
           PRODUCT COUNT
        ===================================================== */

        .product_count {

            font-weight: 600;

            color: #333;
        }


        /* =====================================================
           QUANTITY
        ===================================================== */

        .quantity_value {

            font-weight: 600;

            color: #333;
        }


        /* =====================================================
           TOTAL AMOUNT
        ===================================================== */

        .total_amount {

            color: #df2f68;

            font-weight: 700;

            white-space: nowrap;
        }


        /* =====================================================
           STATUS
        ===================================================== */

        .order_status {

            display: inline-block;

            padding: 6px 13px;

            border-radius: 20px;

            font-size: 11px;

            font-weight: 600;

            text-transform: capitalize;

            white-space: nowrap;
        }


        .status_pending {

            background: #fff3cd;

            color: #856404;
        }


        .status_processing {

            background: #cfe2ff;

            color: #084298;
        }


        .status_confirmed {

            background: #cfe2ff;

            color: #084298;
        }


        .status_shipped {

            background: #cff4fc;

            color: #055160;
        }


        .status_delivered {

            background: #d1e7dd;

            color: #0f5132;
        }


        .status_cancelled {

            background: #f8d7da;

            color: #842029;
        }


        .status_completed {

            background: #d1e7dd;

            color: #0f5132;
        }


        .status_default {

            background: #eee;

            color: #555;
        }


        /* =====================================================
           VIEW BUTTON
        ===================================================== */

        .view_order_btn {

            display: inline-block;

            background: #df2f68;

            color: #fff;

            padding: 8px 13px;

            border-radius: 5px;

            text-decoration: none;

            font-size: 12px;

            font-weight: 600;

            white-space: nowrap;
        }


        .view_order_btn:hover {

            background: #c92359;

            color: #fff;

            text-decoration: none;
        }


        /* =====================================================
           BACK ACCOUNT
        ===================================================== */

        .back_account_area {

            margin-top: 20px;

            text-align: left;
        }


        .back_account_btn {

            color: #666;

            font-size: 13px;

            text-decoration: none;
        }


        .back_account_btn:hover {

            color: #df2f68;

            text-decoration: none;
        }


        /* =====================================================
           EMPTY ORDERS
        ===================================================== */

        .empty_orders {

            background: #fff;

            border-radius: 10px;

            padding: 60px 30px;

            text-align: center;

            box-shadow:
                0 3px 15px rgba(0, 0, 0, 0.07);
        }


        .empty_orders_icon {

            width: 75px;

            height: 75px;

            border-radius: 50%;

            background: #ffe6ef;

            color: #df2f68;

            display: flex;

            align-items: center;

            justify-content: center;

            margin: 0 auto 20px;

            font-size: 30px;
        }


        .empty_orders h3 {

            font-size: 22px;

            color: #333;

            margin-bottom: 10px;
        }


        .empty_orders p {

            color: #777;

            font-size: 14px;

            margin-bottom: 25px;
        }


        .shop_now_btn {

            display: inline-block;

            background: #df2f68;

            color: #fff;

            padding: 11px 25px;

            border-radius: 5px;

            text-decoration: none;

            font-size: 14px;

            font-weight: 600;
        }


        .shop_now_btn:hover {

            background: #c92359;

            color: #fff;

            text-decoration: none;
        }


        /* =====================================================
           MOBILE
        ===================================================== */

        @media (max-width: 767px) {

            .orders_section {

                padding: 45px 15px;
            }


            .orders_title h2 {

                font-size: 26px;
            }


            .orders_table_box {

                border-radius: 7px;
            }


            .orders_table {

                min-width: 850px;
            }


            .orders_table thead th,
            .orders_table tbody td {

                padding: 13px 12px;
            }


            .back_account_area {

                margin-top: 15px;
            }
        }
    </style>

</head>


<body>


    <!-- =====================================================
         HEADER
    ===================================================== -->

    <?php

    if (file_exists("header.php")) {

        include "header.php";
    }

    ?>


    <!-- =====================================================
         ORDERS SECTION
    ===================================================== -->

    <section class="orders_section">

        <div class="container">

            <div class="orders_container">


                <!-- =================================================
                     PAGE TITLE
                ================================================== -->

                <div class="orders_title">

                    <h2>
                        My Orders
                    </h2>

                    <p>
                        View and track all your orders
                    </p>

                </div>


                <!-- =================================================
                     PROFILE UPDATE MESSAGE
                ================================================== -->

                <?php if ($profile_updated) { ?>

                    <div class="success_message">

                        <i class="fa fa-check-circle"></i>

                        Profile updated successfully!

                    </div>

                <?php } ?>


                <!-- =================================================
                     CHECK ORDERS
                ================================================== -->

                <?php if (!empty($orders)) { ?>


                    <!-- =================================================
                         TABLE
                    ================================================== -->

                    <div class="orders_table_box">

                        <div class="table_responsive">

                            <table class="orders_table">

                                <thead>

                                    <tr>

                                        <th>
                                            Order ID
                                        </th>

                                        <th>
                                            Order Date
                                        </th>

                                        <th>
                                            Products
                                        </th>

                                        <th>
                                            Quantity
                                        </th>

                                        <th>
                                            Total Amount
                                        </th>

                                        <th>
                                            Status
                                        </th>

                                        <th>
                                            Action
                                        </th>

                                    </tr>

                                </thead>


                                <tbody>


                                    <?php foreach ($orders as $order) { ?>


                                        <?php

                                        /* =====================================
                                           STATUS
                                        ===================================== */

                                        $status =
                                            strtolower(
                                                trim(
                                                    $order['order_status']
                                                        ?? 'pending'
                                                )
                                            );


                                        $status_class =
                                            'status_default';


                                        switch ($status) {

                                            case 'pending':

                                                $status_class =
                                                    'status_pending';

                                                break;


                                            case 'processing':

                                                $status_class =
                                                    'status_processing';

                                                break;


                                            case 'confirmed':

                                                $status_class =
                                                    'status_confirmed';

                                                break;


                                            case 'shipped':

                                                $status_class =
                                                    'status_shipped';

                                                break;


                                            case 'delivered':

                                                $status_class =
                                                    'status_delivered';

                                                break;


                                            case 'cancelled':

                                            case 'canceled':

                                                $status_class =
                                                    'status_cancelled';

                                                break;


                                            case 'completed':

                                                $status_class =
                                                    'status_completed';

                                                break;
                                        }


                                        /* =====================================
                                           DATE
                                        ===================================== */

                                        $order_date = '';

                                        if (!empty($order['created_at'])) {

                                            $order_date =
                                                date(
                                                    'd M Y, h:i A',
                                                    strtotime(
                                                        $order['created_at']
                                                    )
                                                );
                                        }


                                        /* =====================================
                                           VALUES
                                        ===================================== */

                                        $total_products =
                                            (int)
                                            ($order['total_products'] ?? 0);


                                        $total_quantity =
                                            (int)
                                            ($order['total_quantity'] ?? 0);


                                        $total_amount =
                                            (float)
                                            ($order['total_amount'] ?? 0);

                                        ?>


                                        <!-- =====================================
                                             ORDER ROW
                                        ===================================== -->

                                        <tr>


                                            <!-- ORDER ID -->

                                            <td>

                                                <span class="order_id">

                                                    #

                                                    <?php

                                                    echo htmlspecialchars(
                                                        $order['order_id']
                                                    );

                                                    ?>

                                                </span>

                                            </td>


                                            <!-- DATE -->

                                            <td>

                                                <span class="order_date">

                                                    <i class="fa fa-calendar"></i>

                                                    <?php

                                                    echo htmlspecialchars(
                                                        $order_date
                                                    );

                                                    ?>

                                                </span>

                                            </td>


                                            <!-- PRODUCTS -->

                                            <td>

                                                <span class="product_count">

                                                    <?php

                                                    echo $total_products;

                                                    ?>

                                                    Product<?php

                                                            echo $total_products != 1
                                                                ? 's'
                                                                : '';

                                                            ?>

                                                </span>

                                            </td>


                                            <!-- QUANTITY -->

                                            <td>

                                                <span class="quantity_value">

                                                    <?php

                                                    echo $total_quantity;

                                                    ?>

                                                    Item<?php

                                                        echo $total_quantity != 1
                                                            ? 's'
                                                            : '';

                                                        ?>

                                                </span>

                                            </td>


                                            <!-- TOTAL -->

                                            <td>

                                                <span class="total_amount">

                                                    ₹<?php

                                                        echo number_format(
                                                            $total_amount,
                                                            2
                                                        );

                                                        ?>

                                                </span>

                                            </td>


                                            <!-- STATUS -->

                                            <td>

                                                <span
                                                    class="order_status <?php

                                                                        echo htmlspecialchars(
                                                                            $status_class
                                                                        );

                                                                        ?>">

                                                    <?php

                                                    echo htmlspecialchars(
                                                        ucfirst($status)
                                                    );

                                                    ?>

                                                </span>

                                            </td>


                                            <!-- ACTION -->

                                            <td>

                                                <a
                                                    href="order-details.php?order_id=<?php

                                                                                        echo urlencode(
                                                                                            $order['order_id']
                                                                                        );

                                                                                        ?>"
                                                    class="view_order_btn">

                                                    <i class="fa fa-eye"></i>

                                                    View Details

                                                </a>

                                            </td>


                                        </tr>


                                    <?php } ?>


                                </tbody>

                            </table>

                        </div>

                    </div>


                    <!-- =================================================
                         BACK TO ACCOUNT
                    ================================================== -->

                    <div class="back_account_area">

                        <a
                            href="myaccount.php"
                            class="back_account_btn">

                            <i class="fa fa-arrow-left"></i>

                            Back to My Account

                        </a>

                    </div>


                <?php } else { ?>


                    <!-- =================================================
                         NO ORDERS
                    ================================================== -->

                    <div class="empty_orders">


                        <div class="empty_orders_icon">

                            <i class="fa fa-bag-shopping"></i>

                        </div>


                        <h3>
                            No Orders Yet
                        </h3>


                        <p>

                            You haven't placed any orders yet.
                            Start shopping and your orders will
                            appear here.

                        </p>


                        <a
                            href="shop.php"
                            class="shop_now_btn">

                            <i class="fa fa-shopping-bag"></i>

                            Start Shopping

                        </a>


                    </div>


                <?php } ?>


            </div>

        </div>

    </section>


    <!-- =====================================================
         FOOTER
    ===================================================== -->

    <?php

    if (file_exists("footer.php")) {

        include "footer.php";
    }

    ?>


    <!-- =====================================================
         JS
    ===================================================== -->

    <script src="js/jquery-3.4.1.min.js"></script>

    <script src="js/bootstrap.js"></script>

    <script src="js/custom.js"></script>


</body>

</html>