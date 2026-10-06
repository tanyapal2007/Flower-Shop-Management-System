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
   GET ORDER ID
========================================================= */

$order_id = isset($_GET['order_id'])
    ? (int) $_GET['order_id']
    : 0;


if ($order_id <= 0) {

    header("Location: my-orders.php");
    exit;
}


/* =========================================================
   FETCH USER
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
   FETCH ORDER
   IMPORTANT:
   ORDER MUST BELONG TO LOGGED-IN USER
========================================================= */

$stmt = $conn->prepare("
    SELECT
        o.order_id,
        o.user_id,
        o.total_amount,
        o.order_status,
        o.created_at
    FROM orders o
    WHERE
        o.order_id = :order_id
        AND o.user_id = :user_id
    LIMIT 1
");

$stmt->execute([
    ':order_id' => $order_id,
    ':user_id' => $user_id
]);

$order = $stmt->fetch(PDO::FETCH_ASSOC);


/* =========================================================
   ORDER NOT FOUND
========================================================= */

if (!$order) {

    header("Location: my-orders.php");
    exit;
}


/* =========================================================
   FETCH ORDER ITEMS
========================================================= */

$stmt = $conn->prepare("
    SELECT
        oi.order_item_id,
        oi.order_id,
        oi.product_id,
        oi.quantity,
        oi.price,

        p.product_name,
        p.product_image,

        pi.image_name AS uploaded_image

    FROM order_items oi

    LEFT JOIN products p
        ON p.product_id = oi.product_id

    LEFT JOIN LATERAL
    (
        SELECT
            product_images.image_name
        FROM product_images
        WHERE
            product_images.product_id = oi.product_id
        ORDER BY
            product_images.is_primary DESC,
            product_images.image_id DESC
        LIMIT 1
    ) pi ON TRUE

    WHERE oi.order_id = :order_id

    ORDER BY oi.order_item_id ASC
");

$stmt->execute([
    ':order_id' => $order_id
]);

$order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);


/* =========================================================
   ORDER VALUES
========================================================= */

$total_amount =
    (float)($order['total_amount'] ?? 0);


$order_status =
    strtolower(
        trim(
            $order['order_status'] ?? 'pending'
        )
    );


/* =========================================================
   STATUS CLASS
========================================================= */

$status_class = 'status_default';


switch ($order_status) {

    case 'pending':

        $status_class = 'status_pending';

        break;


    case 'processing':

        $status_class = 'status_processing';

        break;


    case 'confirmed':

        $status_class = 'status_confirmed';

        break;


    case 'shipped':

        $status_class = 'status_shipped';

        break;


    case 'delivered':

        $status_class = 'status_delivered';

        break;


    case 'cancelled':

    case 'canceled':

        $status_class = 'status_cancelled';

        break;


    case 'completed':

        $status_class = 'status_completed';

        break;
}


/* =========================================================
   FORMAT DATE
========================================================= */

$order_date = '';

if (!empty($order['created_at'])) {

    $order_date =
        date(
            'd M Y, h:i A',
            strtotime($order['created_at'])
        );
}


/* =========================================================
   TOTAL ITEMS
========================================================= */

$total_items = 0;

foreach ($order_items as $item) {

    $total_items +=
        (int)($item['quantity'] ?? 0);
}


/* =========================================================
   SHIPPING
========================================================= */

$subtotal = 0;

foreach ($order_items as $item) {

    $quantity =
        (int)($item['quantity'] ?? 0);

    $price =
        (float)($item['price'] ?? 0);

    $subtotal +=
        $quantity * $price;
}


/*
   Use order total from database.
   Shipping is calculated only for display.
*/

$shipping = $total_amount - $subtotal;

if ($shipping < 0) {

    $shipping = 0;
}


/* =========================================================
   PROFILE / USER INFORMATION
========================================================= */

$customer_name =
    trim($user['name'] ?? '');


$customer_email =
    trim($user['email'] ?? '');


$customer_phone =
    trim($user['phone'] ?? '');

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>
        Order Details #<?php echo (int)$order['order_id']; ?> - Fior
    </title>


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
           ORDER DETAILS SECTION
        ===================================================== */

        .order_details_section {

            padding: 60px 0;

            background: #fafafa;

            min-height: 700px;
        }


        .order_details_container {

            max-width: 1100px;

            margin: 0 auto;
        }


        /* =====================================================
           PAGE TOP
        ===================================================== */

        .page_top {

            display: flex;

            justify-content: space-between;

            align-items: center;

            margin-bottom: 25px;
        }


        .page_title h2 {

            margin: 0;

            font-size: 30px;

            font-weight: 700;

            color: #222;
        }


        .page_title p {

            margin: 6px 0 0;

            color: #777;

            font-size: 14px;
        }


        /* =====================================================
           TOP BUTTONS
        ===================================================== */

        .top_buttons {

            display: flex;

            gap: 8px;

            align-items: center;
        }


        .back_btn,
        .print_btn {

            display: inline-flex;

            align-items: center;

            gap: 6px;

            padding: 10px 16px;

            border-radius: 5px;

            font-size: 13px;

            font-weight: 600;

            text-decoration: none;

            cursor: pointer;
        }


        .back_btn {

            background: #fff;

            border: 1px solid #ddd;

            color: #555;
        }


        .back_btn:hover {

            color: #df2f68;

            border-color: #df2f68;

            text-decoration: none;
        }


        .print_btn {

            background: #df2f68;

            border: 1px solid #df2f68;

            color: #fff;
        }


        .print_btn:hover {

            background: #c92359;

            border-color: #c92359;

            color: #fff;
        }


        /* =====================================================
           ORDER MAIN BOX
        ===================================================== */

        .order_main_box {

            background: #fff;

            border-radius: 10px;

            box-shadow:
                0 3px 15px rgba(0, 0, 0, 0.07);

            overflow: hidden;
        }


        /* =====================================================
           ORDER HEADER
        ===================================================== */

        .order_header {

            display: flex;

            justify-content: space-between;

            align-items: center;

            padding: 25px;

            border-bottom: 1px solid #eee;
        }


        .order_number {

            font-size: 18px;

            font-weight: 700;

            color: #222;
        }


        .order_number span {

            color: #df2f68;
        }


        .order_date {

            margin-top: 7px;

            color: #777;

            font-size: 13px;
        }


        /* =====================================================
           STATUS
        ===================================================== */

        .order_status {

            display: inline-block;

            padding: 7px 16px;

            border-radius: 20px;

            font-size: 12px;

            font-weight: 600;

            text-transform: capitalize;
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
           CUSTOMER BOX
        ===================================================== */

        .customer_box {

            padding: 25px;

            border-bottom: 1px solid #eee;
        }


        .section_heading {

            font-size: 18px;

            font-weight: 700;

            color: #222;

            margin-bottom: 18px;
        }


        .customer_details {

            display: grid;

            grid-template-columns:
                repeat(3, 1fr);

            gap: 15px;
        }


        .customer_item {

            background: #fafafa;

            padding: 15px;

            border-radius: 6px;
        }


        .customer_label {

            display: block;

            font-size: 12px;

            color: #888;

            margin-bottom: 5px;
        }


        .customer_value {

            display: block;

            font-size: 14px;

            color: #333;

            font-weight: 600;

            word-break: break-word;
        }


        /* =====================================================
           PRODUCTS
        ===================================================== */

        .products_box {

            padding: 25px;

            border-bottom: 1px solid #eee;
        }


        .products_table_wrapper {

            width: 100%;

            overflow-x: auto;
        }


        .products_table {

            width: 100%;

            border-collapse: collapse;

            min-width: 750px;
        }


        .products_table thead {

            background: #f8f8f8;
        }


        .products_table th {

            padding: 13px 12px;

            text-align: left;

            font-size: 12px;

            color: #555;

            font-weight: 600;

            border-bottom: 1px solid #eee;

            white-space: nowrap;
        }


        .products_table td {

            padding: 15px 12px;

            border-bottom: 1px solid #eee;

            vertical-align: middle;

            font-size: 13px;

            color: #444;
        }


        .products_table tbody tr:last-child td {

            border-bottom: none;
        }


        /* =====================================================
           PRODUCT
        ===================================================== */

        .product_details {

            display: flex;

            align-items: center;

            gap: 12px;
        }


        .product_image {

            width: 65px;

            height: 65px;

            border-radius: 6px;

            object-fit: cover;

            border: 1px solid #eee;

            background: #fafafa;
        }


        .product_name {

            font-size: 14px;

            font-weight: 600;

            color: #333;

            line-height: 1.4;
        }


        .product_code {

            color: #999;

            font-size: 11px;

            margin-top: 3px;
        }


        .quantity {

            font-weight: 600;

            color: #333;
        }


        .item_price {

            color: #555;

            white-space: nowrap;
        }


        .item_total {

            color: #df2f68;

            font-weight: 700;

            white-space: nowrap;
        }


        /* =====================================================
           TOTAL SECTION
        ===================================================== */

        .total_box {

            padding: 25px;

            display: flex;

            justify-content: flex-end;
        }


        .total_content {

            width: 350px;

            max-width: 100%;
        }


        .total_row {

            display: flex;

            justify-content: space-between;

            padding: 8px 0;

            font-size: 14px;

            color: #555;
        }


        .total_row.grand_total {

            margin-top: 8px;

            padding-top: 15px;

            border-top: 1px solid #ddd;

            font-size: 18px;

            font-weight: 700;

            color: #222;
        }


        .grand_total_amount {

            color: #df2f68;
        }


        /* =====================================================
           BOTTOM BUTTON
        ===================================================== */

        .bottom_buttons {

            margin-top: 20px;

            display: flex;

            justify-content: space-between;

            align-items: center;
        }


        .bottom_back {

            color: #666;

            font-size: 13px;

            text-decoration: none;
        }


        .bottom_back:hover {

            color: #df2f68;

            text-decoration: none;
        }


        /* =====================================================
           EMPTY PRODUCTS
        ===================================================== */

        .empty_products {

            padding: 35px;

            text-align: center;

            color: #777;
        }


        /* =====================================================
           PRINT
        ===================================================== */

        @media print {

            body {

                background: #fff !important;
            }


            .no-print {

                display: none !important;
            }


            .order_details_section {

                padding: 0 !important;

                background: #fff !important;
            }


            .order_main_box {

                box-shadow: none !important;

                border: 1px solid #ddd;
            }


            .order_details_container {

                max-width: 100% !important;
            }


            .page_title h2 {

                font-size: 24px;
            }


            .products_table {

                min-width: 0;
            }

        }


        /* =====================================================
           MOBILE
        ===================================================== */

        @media (max-width: 767px) {

            .order_details_section {

                padding: 40px 15px;
            }


            .page_top {

                flex-direction: column;

                align-items: flex-start;

                gap: 15px;
            }


            .top_buttons {

                width: 100%;
            }


            .back_btn,
            .print_btn {

                flex: 1;

                justify-content: center;
            }


            .order_header {

                flex-direction: column;

                align-items: flex-start;

                gap: 15px;

                padding: 20px;
            }


            .customer_box,
            .products_box,
            .total_box {

                padding: 20px;
            }


            .customer_details {

                grid-template-columns: 1fr;
            }


            .total_content {

                width: 100%;
            }

        }
    </style>

</head>


<body>


    <!-- =====================================================
         HEADER
    ===================================================== -->

    <div class="no-print">

        <?php

        if (file_exists("header.php")) {

            include "header.php";
        }

        ?>

    </div>


    <!-- =====================================================
         ORDER DETAILS SECTION
    ===================================================== -->

    <section class="order_details_section">

        <div class="container">

            <div class="order_details_container">


                <!-- =================================================
                     PAGE TOP
                ================================================== -->

                <div class="page_top no-print">


                    <div class="page_title">

                        <h2>
                            Order Details
                        </h2>

                        <p>
                            View your complete order information
                        </p>

                    </div>


                    <div class="top_buttons">


                        <a
                            href="my-orders.php"
                            class="back_btn">

                            <i class="fa fa-arrow-left"></i>

                            My Orders

                        </a>


                        <button
                            type="button"
                            onclick="window.print();"
                            class="print_btn">

                            <i class="fa fa-print"></i>

                            Print Order

                        </button>


                    </div>


                </div>


                <!-- =================================================
                     MAIN ORDER BOX
                ================================================== -->

                <div class="order_main_box">


                    <!-- =================================================
                         ORDER HEADER
                    ================================================== -->

                    <div class="order_header">


                        <div>

                            <div class="order_number">

                                Order #

                                <span>

                                    <?php

                                    echo htmlspecialchars(
                                        $order['order_id']
                                    );

                                    ?>

                                </span>

                            </div>


                            <div class="order_date">

                                <i class="fa fa-calendar"></i>

                                <?php

                                echo htmlspecialchars(
                                    $order_date
                                );

                                ?>

                            </div>

                        </div>


                        <!-- STATUS -->

                        <span
                            class="order_status <?php

                                                echo htmlspecialchars(
                                                    $status_class
                                                );

                                                ?>">

                            <?php

                            echo htmlspecialchars(
                                ucfirst($order_status)
                            );

                            ?>

                        </span>


                    </div>


                    <!-- =================================================
                         CUSTOMER INFORMATION
                    ================================================== -->

                    <div class="customer_box">


                        <div class="section_heading">

                            <i class="fa fa-user"></i>

                            Customer Information

                        </div>


                        <div class="customer_details">


                            <!-- NAME -->

                            <div class="customer_item">

                                <span class="customer_label">

                                    Name

                                </span>

                                <span class="customer_value">

                                    <?php

                                    echo htmlspecialchars(
                                        $customer_name
                                    );

                                    ?>

                                </span>

                            </div>


                            <!-- EMAIL -->

                            <div class="customer_item">

                                <span class="customer_label">

                                    Email

                                </span>

                                <span class="customer_value">

                                    <?php

                                    echo htmlspecialchars(
                                        $customer_email
                                    );

                                    ?>

                                </span>

                            </div>


                            <!-- PHONE -->

                            <div class="customer_item">

                                <span class="customer_label">

                                    Phone

                                </span>

                                <span class="customer_value">

                                    <?php

                                    echo htmlspecialchars(
                                        $customer_phone
                                    );

                                    ?>

                                </span>

                            </div>


                        </div>

                    </div>


                    <!-- =================================================
                         PRODUCTS
                    ================================================== -->

                    <div class="products_box">


                        <div class="section_heading">

                            <i class="fa fa-box"></i>

                            Ordered Products

                        </div>


                        <?php if (!empty($order_items)) { ?>


                            <div class="products_table_wrapper">


                                <table class="products_table">


                                    <thead>

                                        <tr>

                                            <th>
                                                Product
                                            </th>

                                            <th>
                                                Quantity
                                            </th>

                                            <th>
                                                Price
                                            </th>

                                            <th>
                                                Total
                                            </th>

                                        </tr>

                                    </thead>


                                    <tbody>


                                        <?php foreach (
                                            $order_items
                                            as $item
                                        ) { ?>


                                            <?php

                                            /* =================================
                                               PRODUCT IMAGE
                                            ================================= */

                                            $uploaded_image =
                                                trim(
                                                    $item['uploaded_image']
                                                        ?? ''
                                                );


                                            if (
                                                $uploaded_image !== ''
                                            ) {

                                                $product_image =
                                                    "uploads/products/" .
                                                    basename(
                                                        $uploaded_image
                                                    );
                                            } elseif (
                                                !empty($item['product_image'])
                                            ) {

                                                $old_image =
                                                    trim(
                                                        $item['product_image']
                                                    );


                                                if (
                                                    strpos(
                                                        $old_image,
                                                        'assets/images/'
                                                    ) === 0
                                                ) {

                                                    $product_image =
                                                        $old_image;
                                                } else {

                                                    $product_image =
                                                        "assets/images/" .
                                                        basename(
                                                            $old_image
                                                        );
                                                }
                                            } else {

                                                $product_image =
                                                    "assets/images/no-image.jpg";
                                            }


                                            /* =================================
                                               ITEM VALUES
                                            ================================= */

                                            $quantity =
                                                (int)(
                                                    $item['quantity']
                                                    ?? 0
                                                );


                                            $price =
                                                (float)(
                                                    $item['price']
                                                    ?? 0
                                                );


                                            $item_total =
                                                $quantity *
                                                $price;

                                            ?>


                                            <!-- =================================
                                                 PRODUCT ROW
                                            ================================= -->

                                            <tr>


                                                <!-- PRODUCT -->

                                                <td>

                                                    <div
                                                        class="product_details">


                                                        <img
                                                            src="<?php

                                                                    echo htmlspecialchars(
                                                                        $product_image
                                                                    );

                                                                    ?>"

                                                            alt="<?php

                                                                    echo htmlspecialchars(
                                                                        $item['product_name']
                                                                            ??
                                                                            'Product'
                                                                    );

                                                                    ?>"

                                                            class="product_image">


                                                        <div>

                                                            <div
                                                                class="product_name">

                                                                <?php

                                                                echo htmlspecialchars(
                                                                    $item['product_name']
                                                                        ??
                                                                        'Product'
                                                                );

                                                                ?>

                                                            </div>

                                                        </div>


                                                    </div>

                                                </td>


                                                <!-- QUANTITY -->

                                                <td>

                                                    <span
                                                        class="quantity">

                                                        <?php

                                                        echo $quantity;

                                                        ?>

                                                    </span>

                                                </td>


                                                <!-- PRICE -->

                                                <td>

                                                    <span
                                                        class="item_price">

                                                        ₹<?php

                                                            echo number_format(
                                                                $price,
                                                                2
                                                            );

                                                            ?>

                                                    </span>

                                                </td>


                                                <!-- TOTAL -->

                                                <td>

                                                    <span
                                                        class="item_total">

                                                        ₹<?php

                                                            echo number_format(
                                                                $item_total,
                                                                2
                                                            );

                                                            ?>

                                                    </span>

                                                </td>


                                            </tr>


                                        <?php } ?>


                                    </tbody>


                                </table>


                            </div>


                        <?php } else { ?>


                            <div class="empty_products">

                                No products found for this order.

                            </div>


                        <?php } ?>


                    </div>


                    <!-- =================================================
                         TOTAL
                    ================================================== -->

                    <div class="total_box">


                        <div class="total_content">


                            <!-- SUBTOTAL -->

                            <div class="total_row">

                                <span>
                                    Subtotal
                                </span>

                                <span>
                                    ₹<?php

                                        echo number_format(
                                            $subtotal,
                                            2
                                        );

                                        ?>
                                </span>

                            </div>


                            <!-- SHIPPING -->

                            <div class="total_row">

                                <span>
                                    Shipping
                                </span>

                                <span>

                                    <?php

                                    if ($shipping > 0) {

                                        echo '₹' .
                                            number_format(
                                                $shipping,
                                                2
                                            );
                                    } else {

                                        echo 'Free';
                                    }

                                    ?>

                                </span>

                            </div>


                            <!-- GRAND TOTAL -->

                            <div
                                class="total_row grand_total">

                                <span>
                                    Total Amount
                                </span>

                                <span
                                    class="grand_total_amount">

                                    ₹<?php

                                        echo number_format(
                                            $total_amount,
                                            2
                                        );

                                        ?>

                                </span>

                            </div>


                        </div>

                    </div>


                </div>


                <!-- =================================================
                     BOTTOM BUTTONS
                ================================================== -->

                <div class="bottom_buttons no-print">


                    <a
                        href="my-orders.php"
                        class="bottom_back">

                        <i class="fa fa-arrow-left"></i>

                        Back to My Orders

                    </a>


                    <button
                        type="button"
                        onclick="window.print();"
                        class="print_btn">

                        <i class="fa fa-print"></i>

                        Print Order

                    </button>


                </div>


            </div>

        </div>

    </section>


    <!-- =====================================================
         FOOTER
    ===================================================== -->

    <div class="no-print">

        <?php

        if (file_exists("footer.php")) {

            include "footer.php";
        }

        ?>

    </div>


    <!-- =====================================================
         JS
    ===================================================== -->

    <script
        src="js/jquery-3.4.1.min.js">
    </script>


    <script
        src="js/bootstrap.js">
    </script>


    <script
        src="js/custom.js">
    </script>


</body>

</html>