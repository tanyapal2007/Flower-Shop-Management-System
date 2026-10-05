<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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
   FETCH CART ITEMS
========================================================= */

$sql = "
    SELECT
        c.cart_id,
        c.product_id,
        c.quantity,

        p.product_name,
        p.product_code,
        p.stock_quantity,
        p.product_image,

        ps.subcategory_name,
        pc.category_name,

        pp.selling_price,

        pi.image_name

    FROM cart c

    INNER JOIN products p
        ON c.product_id = p.product_id

    LEFT JOIN product_subcategory ps
        ON p.subcategory_id = ps.subcategory_id

    LEFT JOIN product_category pc
        ON ps.category_id = pc.category_id

    LEFT JOIN LATERAL
    (
        SELECT
            product_prices.selling_price
        FROM product_prices
        WHERE product_prices.product_id = p.product_id
        ORDER BY product_prices.price_id DESC
        LIMIT 1
    ) pp ON TRUE

    LEFT JOIN LATERAL
    (
        SELECT
            product_images.image_name
        FROM product_images
        WHERE product_images.product_id = p.product_id
        ORDER BY
            product_images.is_primary DESC,
            product_images.image_id DESC
        LIMIT 1
    ) pi ON TRUE

    WHERE c.user_id = :user_id

    ORDER BY c.cart_id DESC
";


try {

    $stmt = $conn->prepare($sql);

    $stmt->execute([
        ':user_id' => $user_id
    ]);

    $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {

    die("Cart Fetch Error: " .
        htmlspecialchars($e->getMessage()));
}


/* =========================================================
   CHECK EMPTY CART
========================================================= */

if (empty($cart_items)) {

    header("Location: cart.php");
    exit;
}


/* =========================================================
   CALCULATE SUBTOTAL
========================================================= */

$subtotal = 0;

foreach ($cart_items as $item) {

    $price = (float) (
        $item['selling_price'] ?? 0
    );

    $quantity = (int) (
        $item['quantity'] ?? 1
    );

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
   FETCH USER NAME AND PHONE
   FROM users TABLE
========================================================= */

$user_sql = "
    SELECT
        name,
        phone
    FROM users
    WHERE user_id = :user_id
    LIMIT 1
";


try {

    $user_stmt = $conn->prepare($user_sql);

    $user_stmt->execute([
        ':user_id' => $user_id
    ]);

    $user = $user_stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {

    die("User Fetch Error: " .
        htmlspecialchars($e->getMessage()));
}


/* =========================================================
   FETCH ADDRESS
   FROM user_profile TABLE
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


try {

    $profile_stmt = $conn->prepare($profile_sql);

    $profile_stmt->execute([
        ':user_id' => $user_id
    ]);

    $profile = $profile_stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {

    die("Profile Fetch Error: " .
        htmlspecialchars($e->getMessage()));
}


/* =========================================================
   USER DETAILS
========================================================= */

$full_name = $user['name'] ?? '';

$phone = $user['phone'] ?? '';


/* =========================================================
   ADDRESS DETAILS
========================================================= */

$address = $profile['address'] ?? '';

$city = $profile['city'] ?? '';

$state = $profile['state'] ?? '';

$pincode = $profile['pincode'] ?? '';

$country = $profile['country'] ?? 'India';


/* =========================================================
   CHECK ADDRESS
========================================================= */

$has_address =
    !empty(trim($address)) ||
    !empty(trim($city)) ||
    !empty(trim($state)) ||
    !empty(trim($pincode));

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>
        Fior - Checkout
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


    <style>
        * {
            box-sizing: border-box;
        }


        /* =====================================================
           CHECKOUT PAGE HEADER
        ====================================================== */

        .checkout-page .hero_area {

            min-height: auto !important;

            height: auto !important;

            padding-bottom: 0 !important;

        }


        .checkout-page .header_section {

            padding-top: 5px !important;

            padding-bottom: 5px !important;

        }


        .checkout-page .navbar {

            padding-top: 5px !important;

            padding-bottom: 5px !important;

        }


        .checkout-page .hero_area .container {

            margin-top: 0 !important;

            margin-bottom: 0 !important;

        }


        /* =====================================================
           CHECKOUT CONTAINER
        ====================================================== */

        .checkout-container {

            width: 90%;

            max-width: 1200px;

            margin: 25px auto 45px auto;

        }


        /* =====================================================
           BACK TO CART
        ====================================================== */

        .back-cart {

            display: inline-block;

            margin-bottom: 15px;

            color: #e91e63;

            text-decoration: none;

            font-weight: 600;

        }


        .back-cart:hover {

            color: #d81b60;

            text-decoration: none;

        }


        /* =====================================================
           TITLE
        ====================================================== */

        .checkout-title {

            font-size: 32px;

            margin-bottom: 25px;

            font-weight: 600;

            color: #17233c;

        }


        /* =====================================================
           GRID
        ====================================================== */

        .checkout-grid {

            display: grid;

            grid-template-columns: 1.6fr 1fr;

            gap: 25px;

            align-items: start;

        }


        /* =====================================================
           BOX
        ====================================================== */

        .checkout-box {

            background: #ffffff;

            border-radius: 10px;

            padding: 25px;

            margin-bottom: 20px;

            box-shadow:
                0 2px 10px rgba(0, 0, 0, 0.06);

        }


        .checkout-box h3 {

            margin-top: 0;

            margin-bottom: 20px;

            font-size: 21px;

            color: #17233c;

        }


        /* =====================================================
           ADDRESS HEADER
        ====================================================== */

        .address-header {

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 15px;

            margin-bottom: 20px;

        }


        .address-header h3 {

            margin: 0;

        }


        /* =====================================================
           ADD ADDRESS BUTTON
        ====================================================== */

        .add-address-btn {

            display: inline-block;

            background: #e91e63;

            color: #ffffff;

            padding: 9px 16px;

            border-radius: 6px;

            text-decoration: none;

            font-size: 14px;

            font-weight: 600;

            white-space: nowrap;

        }


        .add-address-btn:hover {

            background: #d81b60;

            color: #ffffff;

            text-decoration: none;

        }


        /* =====================================================
           SAVED ADDRESS
        ====================================================== */

        .saved-address {

            border: 1px solid #e5e5e5;

            border-radius: 8px;

            padding: 18px;

            background: #fafafa;

        }


        .saved-address-title {

            display: flex;

            align-items: center;

            gap: 15px;

            margin-bottom: 10px;

            color: #17233c;

        }


        .saved-address-title strong {

            font-size: 16px;

        }


        .saved-address-title span {

            color: #666;

            font-size: 14px;

        }


        .saved-address-text {

            color: #555;

            line-height: 1.7;

            font-size: 14px;

        }


        /* =====================================================
           CHANGE ADDRESS
        ====================================================== */

        .change-address-btn {

            display: inline-block;

            margin-top: 12px;

            color: #e91e63;

            font-size: 14px;

            font-weight: 600;

            text-decoration: none;

        }


        .change-address-btn:hover {

            color: #d81b60;

            text-decoration: underline;

        }


        /* =====================================================
           NO ADDRESS
        ====================================================== */

        .no-address {

            text-align: center;

            padding: 25px 20px;

            border: 1px dashed #ddd;

            border-radius: 8px;

            background: #fafafa;

        }


        .no-address p {

            margin-bottom: 15px;

            color: #777;

        }


        .add-address-main-btn {

            display: inline-block;

            background: #e91e63;

            color: #ffffff;

            padding: 10px 18px;

            border-radius: 6px;

            text-decoration: none;

            font-weight: 600;

        }


        .add-address-main-btn:hover {

            background: #d81b60;

            color: #ffffff;

            text-decoration: none;

        }


        /* =====================================================
           PAYMENT
        ====================================================== */

        .payment-option {

            display: block;

            border: 1px solid #ddd;

            padding: 14px;

            border-radius: 7px;

            margin-bottom: 10px;

            cursor: pointer;

        }


        .payment-option:hover {

            border-color: #e91e63;

        }


        .payment-option input {

            margin-right: 8px;

        }


        /* =====================================================
           PRODUCT
        ====================================================== */

        .checkout-product {

            display: flex;

            align-items: center;

            gap: 15px;

            padding: 15px 0;

            border-bottom: 1px solid #eee;

        }


        .checkout-product:first-child {

            padding-top: 0;

        }


        .checkout-product:last-child {

            border-bottom: none;

        }


        .checkout-product img {

            width: 75px;

            height: 75px;

            object-fit: cover;

            border-radius: 7px;

            flex-shrink: 0;

        }


        .product-info {

            flex: 1;

        }


        .product-name {

            font-weight: 600;

            margin-bottom: 6px;

            color: #17233c;

        }


        .product-category {

            font-size: 13px;

            color: #777;

            margin-bottom: 5px;

        }


        .product-qty {

            font-size: 14px;

            color: #555;

        }


        .product-total {

            font-weight: 600;

            white-space: nowrap;

            color: #17233c;

        }


        /* =====================================================
           SUMMARY
        ====================================================== */

        .summary-row {

            display: flex;

            justify-content: space-between;

            padding: 10px 0;

        }


        .summary-row.total {

            border-top: 1px solid #ddd;

            margin-top: 10px;

            padding-top: 15px;

            font-size: 20px;

            font-weight: bold;

            color: #17233c;

        }


        /* =====================================================
           PLACE ORDER
        ====================================================== */

        .place-order-btn {

            width: 100%;

            border: none;

            background: #e91e63;

            color: #ffffff;

            padding: 15px;

            border-radius: 7px;

            font-size: 17px;

            font-weight: bold;

            cursor: pointer;

            margin-top: 20px;

        }


        .place-order-btn:hover {

            background: #d81b60;

        }


        /* =====================================================
           MOBILE
        ====================================================== */

        @media (max-width: 768px) {

            .checkout-grid {

                grid-template-columns: 1fr;

            }


            .checkout-container {

                width: 95%;

            }


            .checkout-product {

                flex-wrap: wrap;

            }


            .product-total {

                margin-left: auto;

            }

        }


        @media (max-width: 600px) {

            .address-header {

                align-items: flex-start;

            }


            .saved-address-title {

                flex-direction: column;

                align-items: flex-start;

                gap: 5px;

            }


            .checkout-title {

                font-size: 27px;

            }

        }
    </style>

</head>


<body class="checkout-page">


    <!-- =====================================================
         HEADER
    ====================================================== -->

    <div class="hero_area">

        <?php include 'header.php'; ?>

    </div>


    <!-- =====================================================
         CHECKOUT
    ====================================================== -->

    <div class="checkout-container">


        <!-- =================================================
             BACK TO CART
        ================================================== -->

        <a
            href="cart.php"
            class="back-cart">

            ← Back to Cart

        </a>


        <!-- =================================================
             TITLE
        ================================================== -->

        <h1 class="checkout-title">

            Checkout

        </h1>


        <!-- =================================================
             FORM
        ================================================== -->

        <form
            action="place_order.php"
            method="POST">


            <div class="checkout-grid">


                <!-- =================================================
                     LEFT SIDE
                ================================================= -->

                <div>


                    <!-- =================================================
                         DELIVERY ADDRESS
                    ================================================== -->

                    <div class="checkout-box">


                        <div class="address-header">


                            <h3>

                                Delivery Address

                            </h3>


                            <a
                                href="user-address.php"
                                class="add-address-btn">

                                + Add Address

                            </a>


                        </div>


                        <?php if (!$has_address) { ?>


                            <!-- =================================================
                                 NO ADDRESS
                            ================================================== -->

                            <div class="no-address">

                                <p>

                                    No delivery address found.

                                </p>


                                <a
                                    href="user-address.php"
                                    class="add-address-main-btn">

                                    + Add New Address

                                </a>

                            </div>


                        <?php } else { ?>


                            <!-- =================================================
                                 SAVED ADDRESS
                            ================================================== -->

                            <div class="saved-address">


                                <div class="saved-address-title">


                                    <strong>

                                        <?php
                                        echo htmlspecialchars(
                                            $full_name
                                        );
                                        ?>

                                    </strong>


                                    <span>

                                        <?php
                                        echo htmlspecialchars(
                                            $phone
                                        );
                                        ?>

                                    </span>


                                </div>


                                <div class="saved-address-text">


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
                                    ?>


                                    ,


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


                                <a
                                    href="dashboard/user-address.php"
                                    class="change-address-btn">

                                    Change Address

                                </a>


                            </div>


                            <!-- =================================================
                                 HIDDEN ADDRESS VALUES
                            ================================================== -->

                            <input
                                type="hidden"
                                name="full_name"
                                value="<?php
                                        echo htmlspecialchars(
                                            $full_name
                                        );
                                        ?>">


                            <input
                                type="hidden"
                                name="phone"
                                value="<?php
                                        echo htmlspecialchars(
                                            $phone
                                        );
                                        ?>">


                            <input
                                type="hidden"
                                name="address"
                                value="<?php
                                        echo htmlspecialchars(
                                            $address
                                        );
                                        ?>">


                            <input
                                type="hidden"
                                name="city"
                                value="<?php
                                        echo htmlspecialchars(
                                            $city
                                        );
                                        ?>">


                            <input
                                type="hidden"
                                name="state"
                                value="<?php
                                        echo htmlspecialchars(
                                            $state
                                        );
                                        ?>">


                            <input
                                type="hidden"
                                name="pincode"
                                value="<?php
                                        echo htmlspecialchars(
                                            $pincode
                                        );
                                        ?>">


                            <input
                                type="hidden"
                                name="country"
                                value="<?php
                                        echo htmlspecialchars(
                                            $country
                                        );
                                        ?>">


                        <?php } ?>


                    </div>


                    <!-- =================================================
                         PAYMENT METHOD
                    ================================================== -->

                    <div class="checkout-box">


                        <h3>

                            Payment Method

                        </h3>


                        <label class="payment-option">


                            <input
                                type="radio"
                                name="payment_method"
                                value="cod"
                                checked>


                            Cash on Delivery


                        </label>


                        <label class="payment-option">


                            <input
                                type="radio"
                                name="payment_method"
                                value="upi">


                            UPI


                        </label>


                        <label class="payment-option">


                            <input
                                type="radio"
                                name="payment_method"
                                value="card">


                            Credit / Debit Card


                        </label>


                        <label class="payment-option">


                            <input
                                type="radio"
                                name="payment_method"
                                value="netbanking">


                            Net Banking


                        </label>


                    </div>


                </div>


                <!-- =================================================
                     RIGHT SIDE
                ================================================== -->

                <div>


                    <!-- =================================================
                         YOUR ORDER
                    ================================================== -->

                    <div class="checkout-box">


                        <h3>

                            Your Order

                        </h3>


                        <?php foreach ($cart_items as $item) { ?>


                            <?php


                            /* =========================================
                               IMAGE
                            ========================================== */

                            if (!empty($item['image_name'])) {


                                $image =
                                    "uploads/products/" .
                                    basename(
                                        $item['image_name']
                                    );
                            } elseif (
                                !empty($item['product_image'])
                            ) {


                                $oldImage =
                                    trim(
                                        $item['product_image']
                                    );


                                if (
                                    strpos(
                                        $oldImage,
                                        'assets/images/'
                                    ) === 0
                                ) {


                                    $image =
                                        $oldImage;
                                } else {


                                    $image =
                                        "assets/images/" .
                                        basename(
                                            $oldImage
                                        );
                                }
                            } else {


                                $image =
                                    "assets/images/no-image.jpg";
                            }


                            /* =========================================
                               PRICE
                            ========================================== */

                            $price =
                                (float) (
                                    $item['selling_price'] ?? 0
                                );


                            /* =========================================
                               QUANTITY
                            ========================================== */

                            $quantity =
                                (int) (
                                    $item['quantity'] ?? 1
                                );


                            /* =========================================
                               ITEM TOTAL
                            ========================================== */

                            $item_total =
                                $price * $quantity;


                            ?>


                            <div class="checkout-product">


                                <!-- IMAGE -->

                                <img
                                    src="<?php
                                            echo htmlspecialchars(
                                                $image
                                            );
                                            ?>"
                                    alt="<?php
                                            echo htmlspecialchars(
                                                $item['product_name']
                                            );
                                            ?>">


                                <!-- PRODUCT INFORMATION -->

                                <div class="product-info">


                                    <div class="product-name">


                                        <?php
                                        echo htmlspecialchars(
                                            $item['product_name']
                                        );
                                        ?>


                                    </div>


                                    <div class="product-category">


                                        <?php
                                        echo htmlspecialchars(
                                            $item['subcategory_name']
                                                ?? 'Flowers'
                                        );
                                        ?>


                                    </div>


                                    <div class="product-qty">


                                        ₹<?php
                                            echo number_format(
                                                $price,
                                                2
                                            );
                                            ?>


                                        ×


                                        <?php
                                        echo $quantity;
                                        ?>


                                    </div>


                                </div>


                                <!-- ITEM TOTAL -->

                                <div class="product-total">


                                    ₹<?php
                                        echo number_format(
                                            $item_total,
                                            2
                                        );
                                        ?>


                                </div>


                            </div>


                        <?php } ?>


                    </div>


                    <!-- =================================================
                         ORDER SUMMARY
                    ================================================== -->

                    <div class="checkout-box">


                        <h3>

                            Order Summary

                        </h3>


                        <!-- SUBTOTAL -->

                        <div class="summary-row">


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

                        <div class="summary-row">


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


                        <!-- GRAND TOTAL -->

                        <div class="summary-row total">


                            <span>

                                Grand Total

                            </span>


                            <span>


                                ₹<?php
                                    echo number_format(
                                        $grand_total,
                                        2
                                    );
                                    ?>


                            </span>


                        </div>


                        <!-- GRAND TOTAL HIDDEN -->

                        <input
                            type="hidden"
                            name="grand_total"
                            value="<?php
                                    echo htmlspecialchars(
                                        $grand_total
                                    );
                                    ?>">


                        <!-- PLACE ORDER -->

                        <button
                            type="submit"
                            class="place-order-btn">

                            Place Order

                        </button>


                    </div>


                </div>


            </div>


        </form>


    </div>


    <!-- =====================================================
         FOOTER
    ====================================================== -->

    <?php include 'footer.php'; ?>


    <!-- =====================================================
         JAVASCRIPT
    ====================================================== -->

    <script src="js/jquery-3.4.1.min.js"></script>

    <script src="js/bootstrap.js"></script>

    <script src="js/custom.js"></script>


</body>

</html>