<?php

ob_start();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/config/database.php";


/* =========================================================
   CHECK LOGIN
========================================================= */

if (
    !isset($_SESSION['user_id']) ||
    empty($_SESSION['user_id'])
) {
    header("Location: login.php");
    exit;
}

$user_id = (int) $_SESSION['user_id'];


/* =========================================================
   CHECK POST REQUEST
========================================================= */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: checkout.php");
    exit;
}


/* =========================================================
   PAYMENT METHOD
========================================================= */

$payment_method = $_POST['payment_method'] ?? 'cod';

$allowed_payment_methods = [
    'cod',
    'upi',
    'card',
    'netbanking'
];

if (!in_array($payment_method, $allowed_payment_methods, true)) {
    die("Invalid payment method.");
}


/* =========================================================
   FETCH CART
========================================================= */

$cart_sql = "
    SELECT
        c.cart_id,
        c.product_id,
        c.quantity,

        p.product_name,
        p.product_code,
        p.stock_quantity,
        p.status,

        pp.selling_price

    FROM cart c

    INNER JOIN products p
        ON c.product_id = p.product_id

    LEFT JOIN LATERAL
    (
        SELECT
            product_prices.selling_price
        FROM product_prices
        WHERE product_prices.product_id = p.product_id
        ORDER BY product_prices.price_id DESC
        LIMIT 1
    ) pp ON TRUE

    WHERE c.user_id = :user_id

    ORDER BY c.cart_id ASC
";


try {

    $cart_stmt = $conn->prepare($cart_sql);

    $cart_stmt->execute([
        ':user_id' => $user_id
    ]);

    $cart_items = $cart_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {

    die("Cart Fetch Error: " .
        htmlspecialchars($e->getMessage()));
}


/* =========================================================
   CHECK CART
========================================================= */

if (empty($cart_items)) {

    die("
        <div style='
            font-family: Poppins, Arial, sans-serif;
            text-align: center;
            margin-top: 100px;
        '>

            <h2>Cart is Empty</h2>

            <p>No products were found in your cart.</p>

            <a href='cart.php'>
                Go Back to Cart
            </a>

        </div>
    ");
}


/* =========================================================
   CALCULATE TOTAL
========================================================= */

$subtotal = 0;

foreach ($cart_items as $item) {

    $product_name = $item['product_name'];

    $quantity = (int) $item['quantity'];

    $stock = (int) $item['stock_quantity'];

    $status = (int) $item['status'];

    $price = (float) (
        $item['selling_price'] ?? 0
    );


    if ($status !== 1) {

        die("Product " .
            htmlspecialchars($product_name) .
            " is currently unavailable.");
    }


    if ($price <= 0) {

        die("Price not found for product: " .
            htmlspecialchars($product_name));
    }


    if ($quantity <= 0) {

        die("Invalid product quantity.");
    }


    if ($quantity > $stock) {

        die("Only " .
            $stock .
            " item(s) available for " .
            htmlspecialchars($product_name));
    }


    $subtotal += $price * $quantity;
}


/* =========================================================
   SHIPPING
========================================================= */

$shipping = 3;


/* =========================================================
   GRAND TOTAL
========================================================= */

$grand_total = $subtotal + $shipping;


/* =========================================================
   GET USER
========================================================= */

$user_sql = "
    SELECT
        name,
        phone
    FROM users
    WHERE user_id = :user_id
    LIMIT 1
";

$user_stmt = $conn->prepare($user_sql);

$user_stmt->execute([
    ':user_id' => $user_id
]);

$user = $user_stmt->fetch(PDO::FETCH_ASSOC);

$full_name = $user['name'] ?? '';

$phone = $user['phone'] ?? '';


/* =========================================================
   GET ADDRESS
========================================================= */

$profile_sql = "
    SELECT
        address,
        city,
        state,
        pincode,
        country
    FROM user_profile
    WHERE user_id = :user_id
    LIMIT 1
";

$profile_stmt = $conn->prepare($profile_sql);

$profile_stmt->execute([
    ':user_id' => $user_id
]);

$profile = $profile_stmt->fetch(PDO::FETCH_ASSOC);

$address = $profile['address'] ?? '';

$city = $profile['city'] ?? '';

$state = $profile['state'] ?? '';

$pincode = $profile['pincode'] ?? '';

$country = $profile['country'] ?? 'India';


/* =========================================================
   PAYMENT LABEL
========================================================= */

$payment_labels = [

    'cod' =>
    'Cash on Delivery',

    'upi' =>
    'UPI',

    'card' =>
    'Credit / Debit Card',

    'netbanking' =>
    'Net Banking'

];

$payment_name =
    $payment_labels[$payment_method]
    ?? ucfirst($payment_method);


/* =========================================================
   PLACE ORDER
========================================================= */

try {

    $conn->beginTransaction();


    /* =====================================================
       INSERT ORDER
    ===================================================== */

    $order_sql = "
        INSERT INTO orders
        (
            user_id,
            total_amount,
            order_status,
            created_at
        )
        VALUES
        (
            :user_id,
            :total_amount,
            :order_status,
            CURRENT_TIMESTAMP
        )
        RETURNING order_id, created_at
    ";

    $order_stmt = $conn->prepare($order_sql);

    $order_stmt->execute([

        ':user_id' =>
        $user_id,

        ':total_amount' =>
        $grand_total,

        ':order_status' =>
        'Pending'

    ]);

    $order = $order_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {

        throw new Exception(
            "Order could not be created."
        );
    }

    $order_id =
        (int) $order['order_id'];

    $created_at =
        $order['created_at'];


    /* =====================================================
       INSERT ORDER ITEMS
    ===================================================== */

    $item_sql = "
        INSERT INTO order_items
        (
            order_id,
            product_id,
            quantity,
            price
        )
        VALUES
        (
            :order_id,
            :product_id,
            :quantity,
            :price
        )
    ";

    $item_stmt =
        $conn->prepare($item_sql);


    /* =====================================================
       UPDATE STOCK
    ===================================================== */

    $stock_sql = "
        UPDATE products

        SET stock_quantity =
            stock_quantity - :quantity

        WHERE product_id = :product_id
    ";

    $stock_stmt =
        $conn->prepare($stock_sql);


    foreach ($cart_items as $item) {

        $item_stmt->execute([

            ':order_id' =>
            $order_id,

            ':product_id' =>
            (int) $item['product_id'],

            ':quantity' =>
            (int) $item['quantity'],

            ':price' =>
            (float) $item['selling_price']

        ]);


        $stock_stmt->execute([

            ':quantity' =>
            (int) $item['quantity'],

            ':product_id' =>
            (int) $item['product_id']

        ]);
    }


    /* =====================================================
       DELETE CART
    ===================================================== */

    $delete_sql = "
        DELETE FROM cart
        WHERE user_id = :user_id
    ";

    $delete_stmt =
        $conn->prepare($delete_sql);

    $delete_stmt->execute([

        ':user_id' =>
        $user_id

    ]);


    /* =====================================================
       COMMIT
    ===================================================== */

    $conn->commit();
} catch (Exception $e) {

    if ($conn->inTransaction()) {

        $conn->rollBack();
    }

    die("
        <div style='
            font-family: Poppins, Arial, sans-serif;
            text-align: center;
            margin-top: 100px;
        '>

            <h2>Order Could Not Be Placed</h2>

            <p>" .
        htmlspecialchars(
            $e->getMessage()
        ) .
        "</p>

            <a href='checkout.php'>
                Back to Checkout
            </a>

        </div>
    ");
}


/* =========================================================
   TOTAL QUANTITY
========================================================= */

$total_quantity = 0;

foreach ($cart_items as $item) {

    $total_quantity +=
        (int) $item['quantity'];
}


/* =========================================================
   ORDER DATE
========================================================= */

$order_date = !empty($created_at)
    ? date(
        "d M Y, h:i A",
        strtotime($created_at)
    )
    : date("d M Y, h:i A");

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>Fior - Order Confirmed</title>


    <!-- =====================================================
         BOOTSTRAP
    ====================================================== -->

    <link
        rel="stylesheet"
        type="text/css"
        href="css/bootstrap.css">


    <!-- =====================================================
         FIOR FONT
    ====================================================== -->

    <link
        href="https://fonts.googleapis.com/css?family=Baloo+Chettan|Poppins:400,500,600,700&display=swap"
        rel="stylesheet">


    <!-- =====================================================
         WEBSITE CSS
    ====================================================== -->

    <link
        rel="stylesheet"
        type="text/css"
        href="css/style.css">


    <link
        rel="stylesheet"
        type="text/css"
        href="css/responsive.css">


    <!-- =====================================================
         FONT AWESOME
    ====================================================== -->

    <link
        rel="stylesheet"
        href="https://use.fontawesome.com/releases/v5.15.4/css/all.css">


    <style>
        /* =====================================================
           FIOR ORDER PAGE
        ====================================================== */

        .order-success-page {

            margin: 0;

            padding: 0;

            background: #ffffff;

            color: #333333;

            font-family: 'Poppins', sans-serif;

        }


        /* =====================================================
           HEADER
        ====================================================== */

        .order-success-page .hero_area {

            min-height: auto !important;

            height: auto !important;

            padding-bottom: 0 !important;

        }


        .order-success-page .header_section {

            padding-top: 0 !important;

            padding-bottom: 0 !important;

        }


        /* =====================================================
           SUCCESS SECTION
        ====================================================== */

        .fior-success-section {

            position: relative;

            background: #f7e9ee;

            border-top: 1px solid #ead6de;

            border-bottom: 1px solid #ead6de;

            text-align: center;

            padding: 50px 20px 45px;

            overflow: hidden;

        }


        .fior-success-section:before {

            content: "✿";

            position: absolute;

            left: 7%;

            top: 20px;

            font-size: 80px;

            color: rgba(175, 13, 13, 0.08);

        }


        .fior-success-section:after {

            content: "✿";

            position: absolute;

            right: 7%;

            bottom: 5px;

            font-size: 75px;

            color: rgba(200, 98, 143, 0.10);

        }


        .fior-success-icon {

            position: relative;

            z-index: 2;

            width: 78px;

            height: 78px;

            margin: 0 auto 20px;

            border-radius: 50%;

            background: #ffffff;

            border: 2px solid #c8628f;

            color: #c8628f;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 32px;

        }


        .fior-success-section h1 {

            position: relative;

            z-index: 2;

            margin: 0 0 10px;

            font-family: 'Baloo Chettan', cursive;

            font-size: 38px;

            font-weight: 500;

            color: #af0d0d;

        }


        .fior-success-section p {

            position: relative;

            z-index: 2;

            margin: 5px 0;

            font-size: 15px;

            color: #555555;

        }


        .fior-order-number {

            color: #af0d0d;

            font-weight: 600;

        }


        /* =====================================================
           MAIN AREA
        ====================================================== */

        .fior-success-container {

            max-width: 1100px;

            margin: 40px auto 60px;

        }


        /* =====================================================
           CONFIRMATION BAR
        ====================================================== */

        .fior-confirmation {

            background: #ffffff;

            border: 1px solid #eadfe4;

            padding: 20px;

            margin-bottom: 25px;

            text-align: center;

        }


        .fior-status {

            display: inline-block;

            padding: 7px 20px;

            background: #f7e9ee;

            border: 1px solid #e6ccd6;

            color: #af0d0d;

            font-size: 14px;

            font-weight: 600;

        }


        .fior-confirmation p {

            margin: 10px 0 0;

            font-size: 14px;

            color: #777777;

        }


        /* =====================================================
           CARDS
        ====================================================== */

        .fior-card {

            background: #ffffff;

            border: 1px solid #eadfe4;

            padding: 25px;

            margin-bottom: 25px;

        }


        /* =====================================================
           CARD TITLE
        ====================================================== */

        .fior-card-title {

            position: relative;

            margin: 0 0 22px;

            padding-bottom: 12px;

            border-bottom: 1px solid #eadfe4;

            font-family: 'Baloo Chettan', cursive;

            font-size: 23px;

            font-weight: 400;

            color: #af0d0d;

        }


        .fior-card-title i {

            color: #c8628f;

            margin-right: 8px;

        }


        /* =====================================================
           ITEMS
        ====================================================== */

        .fior-item {

            padding: 16px 0;

            border-bottom: 1px solid #eeeeee;

        }


        .fior-item:first-child {

            padding-top: 0;

        }


        .fior-item:last-child {

            padding-bottom: 0;

            border-bottom: none;

        }


        .fior-item-name {

            font-size: 15px;

            font-weight: 600;

            color: #333333;

            margin-bottom: 5px;

        }


        .fior-item-price {

            font-size: 15px;

            font-weight: 600;

            color: #af0d0d;

        }


        .fior-small-text {

            font-size: 13px;

            color: #888888;

        }


        /* =====================================================
           ADDRESS
        ====================================================== */

        .fior-address {

            background: #f7f3f5;

            border-left: 3px solid #c8628f;

            padding: 18px;

            line-height: 1.8;

            color: #666666;

            font-size: 14px;

        }


        .fior-address strong {

            color: #333333;

            font-size: 15px;

        }


        /* =====================================================
           PAYMENT
        ====================================================== */

        .fior-total-row {

            display: flex;

            justify-content: space-between;

            align-items: center;

            gap: 15px;

            padding: 9px 0;

            font-size: 14px;

            color: #666666;

        }


        .fior-total-row strong {

            color: #333333;

        }


        .fior-card hr {

            margin: 12px 0;

            border: 0;

            border-top: 1px solid #eeeeee;

        }


        .fior-grand-total {

            font-family: 'Baloo Chettan', cursive;

            font-size: 25px;

            color: #af0d0d;

        }


        /* =====================================================
           ORDER INFORMATION
        ====================================================== */

        .fior-info-row {

            display: flex;

            justify-content: space-between;

            gap: 15px;

            padding: 11px 0;

            border-bottom: 1px solid #eeeeee;

            font-size: 14px;

        }


        .fior-info-row:last-child {

            border-bottom: none;

        }


        .fior-info-row strong {

            color: #333333;

        }


        .fior-pending {

            color: #af0d0d;

            font-weight: 600;

        }


        /* =====================================================
           BUTTONS
        ====================================================== */

        .fior-actions {

            text-align: center;

            margin-top: 5px;

        }


        .fior-btn {

            display: inline-block;

            min-width: 175px;

            padding: 12px 22px;

            margin: 5px;

            border-radius: 0;

            font-family: 'Poppins', sans-serif;

            font-size: 13px;

            font-weight: 600;

            text-decoration: none;

            transition: all 0.3s ease;

        }


        .fior-btn-primary {

            background: #af0d0d;

            border: 1px solid #af0d0d;

            color: #ffffff;

        }


        .fior-btn-primary:hover {

            background: #8e0909;

            border-color: #8e0909;

            color: #ffffff;

            text-decoration: none;

        }


        .fior-btn-secondary {

            background: #c8628f;

            border: 1px solid #c8628f;

            color: #ffffff;

        }


        .fior-btn-secondary:hover {

            background: #af0d0d;

            border-color: #af0d0d;

            color: #ffffff;

            text-decoration: none;

        }


        .fior-btn-print {

            background: #ffffff;

            border: 1px solid #af0d0d;

            color: #af0d0d;

        }


        .fior-btn-print:hover {

            background: #af0d0d;

            border-color: #af0d0d;

            color: #ffffff;

        }


        /* =====================================================
           FOOTER
        ====================================================== */

        .order-success-page footer {

            margin-top: 0;

        }


        /* =====================================================
           MOBILE
        ====================================================== */

        @media (max-width: 991px) {

            .fior-success-container {

                width: 92%;

            }

        }


        @media (max-width: 768px) {

            .fior-success-section {

                padding: 40px 15px;

            }


            .fior-success-section h1 {

                font-size: 30px;

            }


            .fior-success-icon {

                width: 68px;

                height: 68px;

                font-size: 28px;

            }


            .fior-card {

                padding: 20px;

            }


            .fior-btn {

                width: 100%;

                margin: 5px 0;

            }

        }


        /* =====================================================
           PRINT
        ====================================================== */

        @media print {

            @page {

                size: A4;

                margin: 12mm;

            }


            body {

                background: #ffffff !important;

            }


            .hero_area,

            footer,

            .no-print {

                display: none !important;

            }


            .fior-success-section {

                background: #f7e9ee !important;

                border: 1px solid #ead6de;

                -webkit-print-color-adjust: exact;

                print-color-adjust: exact;

            }


            .fior-card,

            .fior-confirmation {

                box-shadow: none !important;

                break-inside: avoid;

            }


            .fior-card {

                border: 1px solid #dddddd;

            }

        }
    </style>

</head>


<body class="order-success-page">


    <!-- =========================================================
     SAME FIOR WEBSITE HEADER
========================================================= -->

    <div class="hero_area">

        <?php include 'header.php'; ?>

    </div>


    <!-- =========================================================
     FIOR SUCCESS SECTION
========================================================= -->

    <section class="fior-success-section">

        <div class="fior-success-icon">

            <i class="fa fa-check"></i>

        </div>


        <h1>
            Order Placed Successfully!
        </h1>


        <p>
            Thank you for shopping with Fior.
        </p>


        <p>

            Your order

            <span class="fior-order-number">
                #<?php echo $order_id; ?>
            </span>

            has been confirmed.

        </p>

    </section>


    <!-- =========================================================
     MAIN CONTENT
========================================================= -->

    <div class="container fior-success-container">


        <!-- =====================================================
         ORDER CONFIRMED
    ====================================================== -->

        <div class="fior-confirmation">

            <div class="fior-status">

                <i class="fa fa-check-circle"></i>

                &nbsp;

                Order Confirmed

            </div>


            <p>

                Order Date:

                <?php
                echo htmlspecialchars($order_date);
                ?>

            </p>

        </div>


        <!-- =====================================================
         TWO COLUMN CONTENT
    ====================================================== -->

        <div class="row">


            <!-- =================================================
             LEFT COLUMN
        ================================================== -->

            <div class="col-lg-7">


                <!-- =============================================
                 ORDER ITEMS
            ============================================== -->

                <div class="fior-card">

                    <h3 class="fior-card-title">

                        <i class="fa fa-shopping-bag"></i>

                        Order Items

                    </h3>


                    <?php foreach ($cart_items as $item) { ?>

                        <?php

                        $item_price =
                            (float) $item['selling_price'];

                        $item_quantity =
                            (int) $item['quantity'];

                        $item_total =
                            $item_price *
                            $item_quantity;

                        ?>


                        <div class="fior-item">

                            <div
                                class="d-flex justify-content-between">


                                <div>

                                    <div class="fior-item-name">

                                        <?php
                                        echo htmlspecialchars(
                                            $item['product_name']
                                        );
                                        ?>

                                    </div>


                                    <?php
                                    if (!empty($item['product_code'])) {
                                    ?>

                                        <div class="fior-small-text">

                                            Product Code:

                                            <?php
                                            echo htmlspecialchars(
                                                $item['product_code']
                                            );
                                            ?>

                                        </div>

                                    <?php } ?>


                                    <div class="fior-small-text">

                                        Qty:

                                        <?php
                                        echo $item_quantity;
                                        ?>

                                        ×

                                        ₹<?php
                                            echo number_format(
                                                $item_price,
                                                2
                                            );
                                            ?>

                                    </div>

                                </div>


                                <div class="fior-item-price">

                                    ₹<?php
                                        echo number_format(
                                            $item_total,
                                            2
                                        );
                                        ?>

                                </div>


                            </div>

                        </div>


                    <?php } ?>


                </div>


                <!-- =============================================
                 DELIVERY ADDRESS
            ============================================== -->

                <div class="fior-card">

                    <h3 class="fior-card-title">

                        <i class="fa fa-map-marker-alt"></i>

                        Delivery Address

                    </h3>


                    <div class="fior-address">

                        <strong>

                            <?php
                            echo htmlspecialchars(
                                $full_name
                            );
                            ?>

                        </strong>

                        <br>

                        <?php
                        echo htmlspecialchars(
                            $phone
                        );
                        ?>

                        <br>

                        <?php
                        echo htmlspecialchars(
                            $address
                        );
                        ?>

                        <br>

                        <?php
                        echo htmlspecialchars(
                            $city
                        );
                        ?>,

                        <?php
                        echo htmlspecialchars(
                            $state
                        );
                        ?>

                        -

                        <?php
                        echo htmlspecialchars(
                            $pincode
                        );
                        ?>

                        <br>

                        <?php
                        echo htmlspecialchars(
                            $country
                        );
                        ?>

                    </div>

                </div>


            </div>


            <!-- =================================================
             RIGHT COLUMN
        ================================================== -->

            <div class="col-lg-5">


                <!-- =============================================
                 PAYMENT DETAILS
            ============================================== -->

                <div class="fior-card">

                    <h3 class="fior-card-title">

                        <i class="fa fa-credit-card"></i>

                        Payment Details

                    </h3>


                    <div class="fior-total-row">

                        <span>
                            Payment Method
                        </span>

                        <strong>

                            <?php
                            echo htmlspecialchars(
                                $payment_name
                            );
                            ?>

                        </strong>

                    </div>


                    <hr>


                    <div class="fior-total-row">

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


                    <div class="fior-total-row">

                        <span>
                            Shipping
                        </span>

                        <span>

                            ₹<?php
                                echo number_format(
                                    $shipping,
                                    2
                                );
                                ?>

                        </span>

                    </div>


                    <hr>


                    <div class="fior-total-row">

                        <strong>
                            Total Amount
                        </strong>

                        <span class="fior-grand-total">

                            ₹<?php
                                echo number_format(
                                    $grand_total,
                                    2
                                );
                                ?>

                        </span>

                    </div>

                </div>


                <!-- =============================================
                 ORDER INFORMATION
            ============================================== -->

                <div class="fior-card">

                    <h3 class="fior-card-title">

                        <i class="fa fa-info-circle"></i>

                        Order Information

                    </h3>


                    <div class="fior-info-row">

                        <strong>
                            Order ID
                        </strong>

                        <span>
                            #<?php echo $order_id; ?>
                        </span>

                    </div>


                    <div class="fior-info-row">

                        <strong>
                            Status
                        </strong>

                        <span class="fior-pending">
                            Pending
                        </span>

                    </div>


                    <div class="fior-info-row">

                        <strong>
                            Total Quantity
                        </strong>

                        <span>

                            <?php
                            echo $total_quantity;
                            ?>

                        </span>

                    </div>

                </div>


            </div>

        </div>


        <!-- =====================================================
         ACTION BUTTONS
    ====================================================== -->

        <div class="fior-actions no-print">


            <a
                href="gallery.php"
                class="fior-btn fior-btn-primary">

                <i class="fa fa-shopping-cart"></i>

                &nbsp;

                Continue Shopping

            </a>


            <a
                href="dashboard/orders.php"
                class="fior-btn fior-btn-secondary">

                <i class="fa fa-list"></i>

                &nbsp;

                My Orders

            </a>


            <button
                type="button"
                class="fior-btn fior-btn-print"
                onclick="window.print()">

                <i class="fa fa-print"></i>

                &nbsp;

                Download / Print

            </button>


        </div>


    </div>


    <!-- =========================================================
     SAME FIOR WEBSITE FOOTER
========================================================= -->

    <


        <!--=========================================================JAVASCRIPT=========================================================-->

        <script
            type="text/javascript"
            src="js/jquery-3.4.1.min.js">
        </script>


        <script
            type="text/javascript"
            src="js/bootstrap.js">
        </script>


        <script
            type="text/javascript"
            src="js/custom.js">
        </script>


</body>

</html>