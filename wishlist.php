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
   REMOVE FROM WISHLIST
========================================================= */

if (isset($_GET['remove_id'])) {

    $remove_id = (int) $_GET['remove_id'];

    if ($remove_id > 0) {

        $delete_sql = "
            DELETE FROM wishlist
            WHERE user_id = :user_id
            AND product_id = :product_id
        ";

        $delete_stmt = $conn->prepare($delete_sql);

        $delete_stmt->execute([
            ':user_id' => $user_id,
            ':product_id' => $remove_id
        ]);
    }

    header("Location: wishlist.php");
    exit;
}


/* =========================================================
   FETCH WISHLIST PRODUCTS
========================================================= */

$sql = "
    SELECT

        w.product_id,

        p.product_name,
        p.product_code,
        p.product_description,
        p.product_image,
        p.stock_quantity,

        ps.subcategory_name,

        pc.category_name,

        pp.original_price,
        pp.discount_percentage,
        pp.selling_price,

        pi.image_name AS uploaded_image

    FROM wishlist w

    INNER JOIN products p
        ON w.product_id = p.product_id

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

        ORDER BY product_images.image_id DESC

        LIMIT 1

    ) pi ON TRUE

    WHERE w.user_id = :user_id

    AND p.status = 1

    ORDER BY w.product_id DESC
";


try {

    $stmt = $conn->prepare($sql);

    $stmt->execute([
        ':user_id' => $user_id
    ]);

    $wishlist_products =
        $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {

    die("Wishlist Fetch Error: " .
        htmlspecialchars($e->getMessage()));
}


/* =========================================================
   WISHLIST COUNT
========================================================= */

$wishlist_count =
    count($wishlist_products);

?>

<!DOCTYPE html>
<html>

<head>

    <meta charset="utf-8">

    <meta
        http-equiv="X-UA-Compatible"
        content="IE=edge">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <meta
        name="keywords"
        content="Fior wishlist, flower wishlist, flowers">

    <meta
        name="description"
        content="Fior Flower Wishlist">

    <title>Fior - Wishlist</title>


    <!-- =====================================================
         BOOTSTRAP
    ====================================================== -->

    <link
        rel="stylesheet"
        type="text/css"
        href="css/bootstrap.css">


    <!-- =====================================================
         GOOGLE FONTS
    ====================================================== -->

    <link
        href="https://fonts.googleapis.com/css?family=Baloo+Chettan|Poppins:400,600,700&display=swap"
        rel="stylesheet">


    <!-- =====================================================
         MAIN CSS
    ====================================================== -->

    <link
        href="css/style.css"
        rel="stylesheet">


    <!-- =====================================================
         RESPONSIVE CSS
    ====================================================== -->

    <link
        href="css/responsive.css"
        rel="stylesheet">


    <style>
        /* =====================================================
           WISHLIST SECTION
        ====================================================== */

        .wishlist_section {

            padding: 60px 0;

        }


        /* =====================================================
           HEADING
        ====================================================== */

        .wishlist_heading {

            text-align: center;

            margin-bottom: 35px;

        }


        .wishlist_heading h2 {

            color: #17233c;

            font-size: 32px;

            font-weight: 600;

            margin-bottom: 8px;

        }


        .wishlist_heading p {

            color: #777;

            margin: 0;

        }


        .wishlist_count {

            color: #df2f68;

            font-weight: 600;

        }


        /* =====================================================
           WISHLIST GRID
        ====================================================== */

        .wishlist_grid {

            display: grid;

            grid-template-columns:
                repeat(3, 1fr);

            gap: 25px;

        }


        /* =====================================================
           WISHLIST CARD
        ====================================================== */

        .wishlist_card {

            background: #ffffff;

            border: 1px solid #eeeeee;

            border-radius: 10px;

            overflow: hidden;

            padding: 10px;

            transition: 0.3s;

            position: relative;

        }


        .wishlist_card:hover {

            box-shadow:
                0 6px 22px rgba(0, 0, 0, 0.10);

            transform:
                translateY(-3px);

        }


        /* =====================================================
           IMAGE
        ====================================================== */

        .wishlist_image {

            width: 100%;

            height: 230px;

            object-fit: cover;

            border-radius: 7px;

            display: block;

        }


        /* =====================================================
           REMOVE BUTTON
        ====================================================== */

        .remove_wishlist {

            position: absolute;

            top: 18px;

            right: 18px;

            width: 38px;

            height: 38px;

            border-radius: 50%;

            background: #ffffff;

            color: #df2f68;

            border: none;

            font-size: 21px;

            display: flex;

            align-items: center;

            justify-content: center;

            text-decoration: none;

            box-shadow:
                0 2px 8px rgba(0, 0, 0, 0.15);

            transition: 0.3s;

        }


        .remove_wishlist:hover {

            background: #df2f68;

            color: #ffffff;

            text-decoration: none;

        }


        /* =====================================================
           PRODUCT INFO
        ====================================================== */

        .wishlist_info {

            padding: 12px 5px 5px;

        }


        .wishlist_category {

            color: #888;

            font-size: 13px;

            margin-bottom: 5px;

        }


        .wishlist_name {

            margin: 0 0 8px;

            font-size: 18px;

            font-weight: 600;

        }


        .wishlist_name a {

            color: #17233c;

            text-decoration: none;

            transition: 0.3s;

        }


        .wishlist_name a:hover {

            color: #df2f68;

        }


        /* =====================================================
           PRICE
        ====================================================== */

        .wishlist_price {

            margin-bottom: 12px;

        }


        .selling_price {

            color: #e52d68;

            font-size: 19px;

            font-weight: 700;

        }


        .original_price {

            color: #999;

            font-size: 13px;

            text-decoration: line-through;

            margin-left: 7px;

        }


        .discount {

            background: #e52d68;

            color: #ffffff;

            padding: 3px 7px;

            border-radius: 15px;

            font-size: 10px;

            margin-left: 5px;

        }


        /* =====================================================
           BUTTONS
        ====================================================== */

        .wishlist_buttons {

            display: flex;

            gap: 7px;

        }


        .cart_btn {

            flex: 1;

            border: none;

            background: #df2f68;

            color: #ffffff;

            padding: 10px;

            border-radius: 6px;

            font-size: 13px;

            cursor: pointer;

            transition: 0.3s;

        }


        .cart_btn:hover {

            background: #c92359;

        }


        .cart_btn:disabled {

            background: #999;

            cursor: not-allowed;

        }


        .details_btn {

            flex: 1;

            border: 1px solid #df2f68;

            background: #ffffff;

            color: #df2f68;

            padding: 9px;

            border-radius: 6px;

            font-size: 13px;

            text-align: center;

            text-decoration: none;

            transition: 0.3s;

        }


        .details_btn:hover {

            background: #df2f68;

            color: #ffffff;

            text-decoration: none;

        }


        /* =====================================================
           OUT OF STOCK
        ====================================================== */

        .out_stock {

            width: 100%;

            background: #999999;

            color: #ffffff;

            border: none;

            padding: 10px;

            border-radius: 6px;

            font-size: 13px;

        }


        /* =====================================================
           EMPTY WISHLIST
        ====================================================== */

        .empty_wishlist {

            text-align: center;

            padding: 70px 20px;

            background: #ffffff;

            border: 1px solid #eeeeee;

            border-radius: 10px;

        }


        .empty_wishlist .heart {

            font-size: 65px;

            color: #df2f68;

            margin-bottom: 15px;

        }


        .empty_wishlist h3 {

            color: #17233c;

            margin-bottom: 10px;

        }


        .empty_wishlist p {

            color: #777;

            margin-bottom: 20px;

        }


        .shop_now_btn {

            display: inline-block;

            background: #df2f68;

            color: #ffffff;

            padding: 11px 25px;

            border-radius: 6px;

            text-decoration: none;

            transition: 0.3s;

        }


        .shop_now_btn:hover {

            background: #c92359;

            color: #ffffff;

            text-decoration: none;

        }


        /* =====================================================
           RESPONSIVE
        ====================================================== */

        @media (max-width: 992px) {

            .wishlist_grid {

                grid-template-columns:
                    repeat(2, 1fr);

            }

        }


        @media (max-width: 576px) {

            .wishlist_grid {

                grid-template-columns: 1fr;

            }

        }
    </style>

</head>


<body class="sub_page">


    <!-- =====================================================
         HEADER
    ====================================================== -->

    <div class="hero_area">

        <?php include 'header.php'; ?>

    </div>


    <!-- =====================================================
         WISHLIST SECTION
    ====================================================== -->

    <section class="wishlist_section">


        <div class="container">


            <!-- HEADING -->

            <div class="wishlist_heading">

                <h2>

                    My Wishlist

                </h2>

                <p>

                    Your favourite flowers are saved here.

                    <span class="wishlist_count">

                        <?php echo $wishlist_count; ?>

                        Item<?php
                            echo ($wishlist_count != 1)
                                ? 's'
                                : '';
                            ?>

                    </span>

                </p>

            </div>


            <?php if (!empty($wishlist_products)) { ?>


                <!-- =================================================
                     WISHLIST PRODUCTS
                ================================================== -->

                <div class="wishlist_grid">


                    <?php foreach (
                        $wishlist_products
                        as $product
                    ) { ?>


                        <?php

                        /* =================================================
                           PRODUCT IMAGE
                        ================================================= */

                        $uploadedImage =
                            trim(
                                $product['uploaded_image']
                                    ?? ''
                            );


                        if (
                            $uploadedImage !== ''
                        ) {

                            $imagePath =
                                "uploads/products/" .
                                basename(
                                    $uploadedImage
                                );
                        } elseif (
                            !empty($product['product_image'])
                        ) {

                            $oldImage =
                                trim(
                                    $product['product_image']
                                );


                            if (
                                strpos(
                                    $oldImage,
                                    'assets/images/'
                                ) === 0
                            ) {

                                $imagePath =
                                    $oldImage;
                            } else {

                                $imagePath =
                                    "assets/images/" .
                                    basename(
                                        $oldImage
                                    );
                            }
                        } else {

                            $imagePath =
                                "assets/images/no-image.jpg";
                        }


                        /* =================================================
                           PRICE
                        ================================================= */

                        $sellingPrice =
                            $product['selling_price']
                            ?? null;


                        $originalPrice =
                            $product['original_price']
                            ?? null;


                        $discount =
                            $product['discount_percentage']
                            ?? null;


                        /* =================================================
                           STOCK
                        ================================================= */

                        $stock =
                            (int)(
                                $product['stock_quantity']
                                ?? 0
                            );


                        $outOfStock =
                            ($stock <= 0);

                        ?>


                        <!-- =================================================
                             PRODUCT CARD
                        ================================================== -->

                        <div class="wishlist_card">


                            <!-- REMOVE -->

                            <a
                                href="wishlist.php?remove_id=<?php echo (int)$product['product_id']; ?>"
                                class="remove_wishlist"
                                title="Remove from Wishlist"
                                onclick="
                                    return confirm(
                                        'Remove this product from wishlist?'
                                    );
                                ">

                                ♥

                            </a>


                            <!-- IMAGE -->

                            <img
                                src="<?php echo htmlspecialchars($imagePath); ?>"
                                alt="<?php echo htmlspecialchars($product['product_name']); ?>"
                                class="wishlist_image">


                            <div class="wishlist_info">


                                <!-- CATEGORY -->

                                <div class="wishlist_category">

                                    <?php

                                    echo htmlspecialchars(
                                        $product['subcategory_name']
                                            ?? 'Flowers'
                                    );

                                    ?>

                                </div>


                                <!-- PRODUCT NAME -->

                                <h5 class="wishlist_name">

                                    <a
                                        href="product-details.php?product_id=<?php echo (int)$product['product_id']; ?>">

                                        <?php

                                        echo htmlspecialchars(
                                            $product['product_name']
                                        );

                                        ?>

                                    </a>

                                </h5>


                                <!-- PRICE -->

                                <div class="wishlist_price">


                                    <?php if (
                                        $sellingPrice !== null
                                    ) { ?>

                                        <span class="selling_price">

                                            ₹<?php

                                                echo number_format(
                                                    (float)$sellingPrice,
                                                    2
                                                );

                                                ?>

                                        </span>

                                    <?php } else { ?>

                                        <span class="selling_price">

                                            Price Not Available

                                        </span>

                                    <?php } ?>


                                    <?php if (
                                        $originalPrice !== null &&
                                        $sellingPrice !== null &&
                                        (float)$originalPrice >
                                        (float)$sellingPrice
                                    ) { ?>

                                        <span class="original_price">

                                            ₹<?php

                                                echo number_format(
                                                    (float)$originalPrice,
                                                    2
                                                );

                                                ?>

                                        </span>

                                    <?php } ?>


                                    <?php if (
                                        $discount !== null &&
                                        (float)$discount > 0
                                    ) { ?>

                                        <span class="discount">

                                            <?php

                                            echo number_format(
                                                (float)$discount,
                                                0
                                            );

                                            ?>% OFF

                                        </span>

                                    <?php } ?>


                                </div>


                                <!-- =================================================
                                     ACTION BUTTONS
                                ================================================== -->

                                <?php if (
                                    $outOfStock
                                ) { ?>


                                    <button
                                        type="button"
                                        class="out_stock"
                                        disabled>

                                        Out of Stock

                                    </button>


                                <?php } else { ?>


                                    <div class="wishlist_buttons">


                                        <!-- ADD TO CART -->

                                        <button
                                            type="button"
                                            class="cart_btn"
                                            data-product-id="<?php echo (int)$product['product_id']; ?>">

                                            🛒 Add to Cart

                                        </button>


                                        <!-- DETAILS -->

                                        <a
                                            href="product-details.php?product_id=<?php echo (int)$product['product_id']; ?>"
                                            class="details_btn">

                                            View Details

                                        </a>


                                    </div>


                                <?php } ?>


                            </div>


                        </div>


                    <?php } ?>


                </div>


            <?php } else { ?>


                <!-- =================================================
                     EMPTY WISHLIST
                ================================================== -->

                <div class="empty_wishlist">


                    <div class="heart">

                        ♡

                    </div>


                    <h3>

                        Your Wishlist is Empty

                    </h3>


                    <p>

                        You haven't added any flowers
                        to your wishlist yet.

                    </p>


                    <a
                        href="gallery.php"
                        class="shop_now_btn">

                        🌸 Browse Flowers

                    </a>


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


    <!-- =====================================================
         ADD TO CART
    ====================================================== -->

    <script>
        document
            .querySelectorAll('.cart_btn')
            .forEach(function(button) {

                button.addEventListener(
                    'click',
                    function() {


                        var productId =
                            this.getAttribute(
                                'data-product-id'
                            );


                        var currentButton =
                            this;


                        currentButton.disabled =
                            true;


                        currentButton.innerHTML =
                            "Adding...";


                        fetch(
                                "config/backend_cart.php?action=add&product_id=" +
                                productId, {
                                    method: "GET",

                                    headers: {
                                        "X-Requested-With": "XMLHttpRequest"
                                    }
                                }
                            )

                            .then(function(response) {

                                return response.json();

                            })

                            .then(function(data) {


                                /* LOGIN REQUIRED */

                                if (
                                    data.login_required
                                ) {

                                    window.location.href =
                                        "login.php";

                                    return;

                                }


                                /* SUCCESS */

                                if (
                                    data.success === true ||
                                    data.status === "success"
                                ) {

                                    window.location.href =
                                        "cart.php";

                                    return;

                                }


                                /* ERROR */

                                alert(
                                    data.message ||
                                    "Unable to add product to cart."
                                );


                                currentButton.disabled =
                                    false;


                                currentButton.innerHTML =
                                    "🛒 Add to Cart";

                            })


                            .catch(function(error) {

                                console.error(error);


                                alert(
                                    "Something went wrong while adding product to cart."
                                );


                                currentButton.disabled =
                                    false;


                                currentButton.innerHTML =
                                    "🛒 Add to Cart";

                            });

                    }
                );

            });
    </script>


</body>

</html>