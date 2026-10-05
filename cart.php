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
   FETCH CART PRODUCTS
========================================================= */

$sql = "
    SELECT
        c.cart_id,
        c.user_id,
        c.product_id,
        c.quantity,

        p.product_name,
        p.product_code,
        p.stock_quantity,
        p.product_image,

        ps.subcategory_name,
        pc.category_name,

        pp.original_price,
        pp.discount_percentage,
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
            product_prices.original_price,
            product_prices.discount_percentage,
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
   CALCULATE TOTALS
========================================================= */

$subtotal = 0;


foreach ($cart_items as $item) {

    $price = (float)(
        $item['selling_price'] ?? 0
    );

    $quantity = (int)(
        $item['quantity'] ?? 1
    );

    $subtotal += $price * $quantity;
}


/* =========================================================
   SHIPPING
========================================================= */

$shipping = empty($cart_items) ? 0 : 3;


/* =========================================================
   GRAND TOTAL
========================================================= */

$grand_total = $subtotal + $shipping;

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>
        Fior - My Cart
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
           COMPACT HEADER
        ====================================================== */

        .cart-page .hero_area {

            min-height: auto !important;

            height: auto !important;

            padding: 0 !important;

            margin: 0 !important;

        }


        .cart-page .header_section {

            padding-top: 5px !important;

            padding-bottom: 5px !important;

            margin: 0 !important;

        }


        .cart-page .navbar {

            padding-top: 4px !important;

            padding-bottom: 4px !important;

            margin: 0 !important;

        }


        .cart-page .navbar-brand {

            margin-top: 0 !important;

            margin-bottom: 0 !important;

        }


        .cart-page .navbar-nav {

            margin-top: 0 !important;

            margin-bottom: 0 !important;

        }


        .cart-page .hero_area .container {

            margin-top: 0 !important;

            margin-bottom: 0 !important;

            padding-top: 0 !important;

            padding-bottom: 0 !important;

        }


        /* =====================================================
           CART SECTION
        ====================================================== */

        .cart-section {

            padding: 35px 0 50px 0;

            background: #f8f8f8;

            min-height: 500px;

        }


        /* =====================================================
           CART CONTAINER
        ====================================================== */

        .cart-container {

            width: 95%;

            max-width: 1200px;

            margin: auto;

        }


        /* =====================================================
           CART TITLE
        ====================================================== */

        .cart-title {

            text-align: center;

            margin-bottom: 30px;

        }


        .cart-title h2 {

            color: #17233c;

            font-weight: 600;

            margin: 0;

        }


        /* =====================================================
           CART BOX
        ====================================================== */

        .cart-box {

            background: #ffffff;

            border-radius: 8px;

            padding: 20px;

            box-shadow:
                0 2px 10px rgba(0, 0, 0, 0.05);

            margin-bottom: 20px;

        }


        /* =====================================================
           CART ITEM
        ====================================================== */

        .cart-item {

            display: flex;

            align-items: center;

            gap: 18px;

            padding: 18px 0;

            border-bottom: 1px solid #eeeeee;

        }


        .cart-item:first-child {

            padding-top: 0;

        }


        .cart-item:last-child {

            border-bottom: none;

            padding-bottom: 0;

        }


        /* =====================================================
           PRODUCT IMAGE
        ====================================================== */

        .cart-product-image {

            width: 90px;

            height: 90px;

            flex-shrink: 0;

        }


        .cart-product-image img {

            width: 100%;

            height: 100%;

            object-fit: cover;

            border-radius: 7px;

        }


        /* =====================================================
           PRODUCT INFORMATION
        ====================================================== */

        .cart-product-info {

            flex: 1;

            min-width: 150px;

        }


        .cart-product-name {

            font-size: 17px;

            font-weight: 600;

            color: #17233c;

            margin-bottom: 5px;

        }


        .cart-product-category {

            font-size: 13px;

            color: #888;

            margin-bottom: 5px;

        }


        .cart-product-code {

            font-size: 12px;

            color: #999;

        }


        .cart-product-price {

            margin-top: 7px;

            font-size: 14px;

            color: #555;

        }


        /* =====================================================
           QUANTITY
        ====================================================== */

        .quantity-box {

            display: flex;

            align-items: center;

            border: 1px solid #dddddd;

            border-radius: 5px;

            overflow: hidden;

            flex-shrink: 0;

        }


        .quantity-btn {

            width: 32px;

            height: 32px;

            border: none;

            background: #f5f5f5;

            cursor: pointer;

            font-size: 18px;

            color: #333;

        }


        .quantity-btn:hover {

            background: #e91e63;

            color: #ffffff;

        }


        .cart-quantity {

            width: 40px;

            text-align: center;

            font-size: 14px;

            font-weight: 600;

        }


        /* =====================================================
           ITEM TOTAL
        ====================================================== */

        .cart-item-total {

            min-width: 90px;

            text-align: right;

            font-size: 16px;

            font-weight: 600;

            color: #17233c;

        }


        /* =====================================================
           REMOVE
        ====================================================== */

        .remove-cart {

            border: none;

            background: none;

            color: #e91e63;

            cursor: pointer;

            font-size: 14px;

            padding: 5px;

        }


        .remove-cart:hover {

            color: #c92359;

            text-decoration: underline;

        }


        /* =====================================================
           SUMMARY
        ====================================================== */

        .summary-title {

            font-size: 20px;

            font-weight: 600;

            color: #17233c;

            margin-bottom: 18px;

        }


        .summary-row {

            display: flex;

            justify-content: space-between;

            padding: 9px 0;

            color: #555;

        }


        .summary-total {

            display: flex;

            justify-content: space-between;

            border-top: 1px solid #eeeeee;

            margin-top: 10px;

            padding-top: 15px;

            font-size: 20px;

            font-weight: 700;

            color: #17233c;

        }


        /* =====================================================
           CHECKOUT BUTTON
        ====================================================== */

        .checkout-btn {

            display: block;

            width: 100%;

            border: none;

            background: #e91e63;

            color: #ffffff;

            padding: 14px;

            border-radius: 6px;

            text-align: center;

            font-size: 16px;

            font-weight: 600;

            margin-top: 20px;

            text-decoration: none;

            cursor: pointer;

        }


        .checkout-btn:hover {

            background: #c92359;

            color: #ffffff;

            text-decoration: none;

        }


        /* =====================================================
           EMPTY CART
        ====================================================== */

        .empty-cart {

            text-align: center;

            padding: 60px 20px;

        }


        .empty-cart h3 {

            color: #17233c;

            margin-bottom: 10px;

        }


        .empty-cart p {

            color: #777;

            margin-bottom: 20px;

        }


        .continue-shopping {

            display: inline-block;

            background: #e91e63;

            color: #ffffff;

            padding: 11px 20px;

            border-radius: 6px;

            text-decoration: none;

            font-weight: 600;

        }


        .continue-shopping:hover {

            background: #c92359;

            color: #ffffff;

            text-decoration: none;

        }


        /* =====================================================
           MOBILE
        ====================================================== */

        @media (max-width: 768px) {

            .cart-section {

                padding: 25px 0 40px 0;

            }


            .cart-item {

                flex-wrap: wrap;

            }


            .cart-product-info {

                min-width: calc(100% - 110px);

            }


            .quantity-box {

                margin-left: 108px;

            }


            .cart-item-total {

                margin-left: auto;

            }


            .remove-cart {

                margin-left: auto;

            }

        }


        /* =====================================================
           SMALL MOBILE
        ====================================================== */

        @media (max-width: 500px) {

            .cart-container {

                width: 94%;

            }


            .cart-box {

                padding: 15px;

            }


            .cart-product-image {

                width: 75px;

                height: 75px;

            }


            .cart-product-name {

                font-size: 15px;

            }


            .quantity-box {

                margin-left: 0;

            }

        }
    </style>

</head>


<body class="cart-page">


    <!-- =====================================================
         HEADER
    ====================================================== -->

    <div class="hero_area">

        <?php include 'header.php'; ?>

    </div>


    <!-- =====================================================
         CART SECTION
    ====================================================== -->

    <section class="cart-section">

        <div class="cart-container">


            <!-- TITLE -->

            <div class="cart-title">

                <h2>
                    My Cart
                </h2>

            </div>


            <?php if (empty($cart_items)) { ?>


                <!-- =================================================
                     EMPTY CART
                ================================================== -->

                <div class="cart-box empty-cart">

                    <h3>
                        Your Cart is Empty
                    </h3>

                    <p>
                        You have not added any products to your cart.
                    </p>

                    <a
                        href="gallery.php"
                        class="continue-shopping">

                        Continue Shopping

                    </a>

                </div>


            <?php } else { ?>


                <div class="row">


                    <!-- =============================================
                         LEFT SIDE
                    ============================================== -->

                    <div class="col-lg-8">


                        <div class="cart-box">


                            <?php foreach (
                                $cart_items
                                as $item
                            ) { ?>


                                <?php

                                /* =================================
                                   IMAGE
                                ================================= */

                                if (
                                    !empty($item['image_name'])
                                ) {

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


                                /* =================================
                                   PRICE
                                ================================= */

                                $price =
                                    (float)(
                                        $item['selling_price']
                                        ?? 0
                                    );


                                /* =================================
                                   QUANTITY
                                ================================= */

                                $quantity =
                                    (int)(
                                        $item['quantity']
                                        ?? 1
                                    );


                                /* =================================
                                   ITEM TOTAL
                                ================================= */

                                $item_total =
                                    $price * $quantity;

                                ?>


                                <div
                                    class="cart-item"
                                    data-cart-id="<?php
                                                    echo (int)$item['cart_id'];
                                                    ?>"
                                    data-price="<?php
                                                echo htmlspecialchars($price);
                                                ?>"
                                    data-stock="<?php
                                                echo (int)$item['stock_quantity'];
                                                ?>">


                                    <!-- PRODUCT IMAGE -->

                                    <div class="cart-product-image">

                                        <img
                                            src="<?php
                                                    echo htmlspecialchars($image);
                                                    ?>"
                                            alt="<?php
                                                    echo htmlspecialchars(
                                                        $item['product_name']
                                                    );
                                                    ?>">

                                    </div>


                                    <!-- PRODUCT INFORMATION -->

                                    <div class="cart-product-info">

                                        <div class="cart-product-name">

                                            <?php
                                            echo htmlspecialchars(
                                                $item['product_name']
                                            );
                                            ?>

                                        </div>


                                        <div class="cart-product-category">

                                            <?php
                                            echo htmlspecialchars(
                                                $item['subcategory_name']
                                                    ?? 'Flowers'
                                            );
                                            ?>

                                        </div>


                                        <?php if (
                                            !empty($item['product_code'])
                                        ) { ?>

                                            <div class="cart-product-code">

                                                Code:
                                                <?php
                                                echo htmlspecialchars(
                                                    $item['product_code']
                                                );
                                                ?>

                                            </div>

                                        <?php } ?>


                                        <div class="cart-product-price">

                                            ₹<?php
                                                echo number_format(
                                                    $price,
                                                    2
                                                );
                                                ?>

                                            per item

                                        </div>

                                    </div>


                                    <!-- QUANTITY -->

                                    <div class="quantity-box">

                                        <button
                                            type="button"
                                            class="quantity-btn quantity-minus">

                                            −

                                        </button>


                                        <span class="cart-quantity">

                                            <?php
                                            echo $quantity;
                                            ?>

                                        </span>


                                        <button
                                            type="button"
                                            class="quantity-btn quantity-plus">

                                            +

                                        </button>

                                    </div>


                                    <!-- ITEM TOTAL -->

                                    <div class="cart-item-total">

                                        ₹<?php
                                            echo number_format(
                                                $item_total,
                                                2
                                            );
                                            ?>

                                    </div>


                                    <!-- REMOVE -->

                                    <button
                                        type="button"
                                        class="remove-cart">

                                        Remove

                                    </button>


                                </div>


                            <?php } ?>


                        </div>

                    </div>


                    <!-- =============================================
                         RIGHT SIDE
                    ============================================== -->

                    <div class="col-lg-4">


                        <div class="cart-box">


                            <div class="summary-title">

                                Cart Summary

                            </div>


                            <!-- SUBTOTAL -->

                            <div class="summary-row">

                                <span>
                                    Subtotal
                                </span>

                                <span id="cart-subtotal">

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

                                <span id="cart-shipping">

                                    ₹<?php
                                        echo number_format(
                                            $shipping,
                                            2
                                        );
                                        ?>

                                </span>

                            </div>


                            <!-- GRAND TOTAL -->

                            <div class="summary-total">

                                <span>
                                    Grand Total
                                </span>

                                <span id="cart-grand-total">

                                    ₹<?php
                                        echo number_format(
                                            $grand_total,
                                            2
                                        );
                                        ?>

                                </span>

                            </div>


                            <!-- CHECKOUT -->

                            <button
                                type="button"
                                class="checkout-btn"
                                onclick="window.location.href='checkout.php';">

                                Proceed to Checkout

                            </button>


                        </div>

                    </div>


                </div>


            <?php } ?>


        </div>

    </section>


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


    <script>
        /* =====================================================
           UPDATE SUMMARY
        ====================================================== */

        function updateCartSummary() {

            let subtotal = 0;


            document
                .querySelectorAll('.cart-item')
                .forEach(function(item) {

                    let price =
                        parseFloat(
                            item.dataset.price
                        ) || 0;


                    let quantity =
                        parseInt(
                            item.querySelector(
                                '.cart-quantity'
                            ).textContent
                        ) || 0;


                    subtotal +=
                        price * quantity;

                });


            let shipping =
                subtotal > 0 ? 3 : 0;


            let grandTotal =
                subtotal + shipping;


            document.getElementById(
                    'cart-subtotal'
                ).textContent =
                '₹' + subtotal.toFixed(2);


            document.getElementById(
                    'cart-shipping'
                ).textContent =
                '₹' + shipping.toFixed(2);


            document.getElementById(
                    'cart-grand-total'
                ).textContent =
                '₹' + grandTotal.toFixed(2);

        }


        /* =====================================================
           UPDATE QUANTITY
        ====================================================== */

        function updateQuantity(
            cartId,
            newQuantity,
            cartItem
        ) {

            if (newQuantity < 1) {

                return;

            }


            let stock =
                parseInt(
                    cartItem.dataset.stock
                ) || 0;


            if (
                stock > 0 &&
                newQuantity > stock
            ) {

                alert(
                    'Only ' +
                    stock +
                    ' item(s) available.'
                );

                return;

            }


            fetch(
                    'config/backend_cart.php?action=update' +
                    '&cart_id=' +
                    cartId +
                    '&quantity=' +
                    newQuantity
                )

                .then(function(response) {

                    return response.json();

                })

                .then(function(data) {

                    if (data.success) {

                        cartItem.querySelector(
                                '.cart-quantity'
                            ).textContent =
                            data.quantity;


                        let price =
                            parseFloat(
                                cartItem.dataset.price
                            ) || 0;


                        let total =
                            price *
                            parseInt(
                                data.quantity
                            );


                        cartItem.querySelector(
                                '.cart-item-total'
                            ).textContent =
                            '₹' +
                            total.toFixed(2);


                        updateCartSummary();


                    } else {

                        alert(
                            data.message ||
                            'Unable to update cart.'
                        );

                    }

                })

                .catch(function(error) {

                    console.error(error);

                    alert(
                        'Something went wrong while updating cart.'
                    );

                });

        }


        /* =====================================================
           PLUS BUTTON
        ====================================================== */

        document
            .querySelectorAll('.quantity-plus')
            .forEach(function(button) {

                button.addEventListener(
                    'click',
                    function() {

                        let cartItem =
                            button.closest(
                                '.cart-item'
                            );


                        let cartId =
                            cartItem.dataset.cartId;


                        let quantity =
                            parseInt(
                                cartItem.querySelector(
                                    '.cart-quantity'
                                ).textContent
                            ) || 0;


                        updateQuantity(
                            cartId,
                            quantity + 1,
                            cartItem
                        );

                    }
                );

            });


        /* =====================================================
           MINUS BUTTON
        ====================================================== */

        document
            .querySelectorAll('.quantity-minus')
            .forEach(function(button) {

                button.addEventListener(
                    'click',
                    function() {

                        let cartItem =
                            button.closest(
                                '.cart-item'
                            );


                        let cartId =
                            cartItem.dataset.cartId;


                        let quantity =
                            parseInt(
                                cartItem.querySelector(
                                    '.cart-quantity'
                                ).textContent
                            ) || 0;


                        if (quantity <= 1) {

                            return;

                        }


                        updateQuantity(
                            cartId,
                            quantity - 1,
                            cartItem
                        );

                    }
                );

            });


        /* =====================================================
           REMOVE CART ITEM
        ====================================================== */

        document
            .querySelectorAll('.remove-cart')
            .forEach(function(button) {

                button.addEventListener(
                    'click',
                    function() {

                        let cartItem =
                            button.closest(
                                '.cart-item'
                            );


                        let cartId =
                            cartItem.dataset.cartId;


                        fetch(
                                'config/backend_cart.php?action=remove' +
                                '&cart_id=' +
                                cartId
                            )

                            .then(function(response) {

                                return response.json();

                            })

                            .then(function(data) {

                                if (data.success) {

                                    cartItem.remove();


                                    let remaining =
                                        document.querySelectorAll(
                                            '.cart-item'
                                        );


                                    if (
                                        remaining.length === 0
                                    ) {

                                        window.location.reload();

                                        return;

                                    }


                                    updateCartSummary();


                                } else {

                                    alert(
                                        data.message ||
                                        'Unable to remove item.'
                                    );

                                }

                            })

                            .catch(function(error) {

                                console.error(error);

                                alert(
                                    'Something went wrong while removing product.'
                                );

                            });

                    }
                );

            });
    </script>


</body>

</html>