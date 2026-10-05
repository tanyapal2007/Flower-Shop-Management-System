<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "config/database.php";


/* =========================================================
   CHECK PRODUCT ID
========================================================= */

$product_id = isset($_GET['product_id'])
    ? (int)$_GET['product_id']
    : 0;


if ($product_id <= 0) {
    header("Location: gallery.php");
    exit;
}


/* =========================================================
   FETCH PRODUCT
========================================================= */

$sql = "
    SELECT
        p.product_id,
        p.product_name,
        p.product_code,
        p.stock_quantity,
        p.product_description,
        p.product_image,
        p.status,

        p.brand_name,
        p.color,
        p.size,
        p.material,

        ps.subcategory_name,
        pc.category_name,

        pp.original_price,
        pp.discount_percentage,
        pp.selling_price

    FROM products p

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

    WHERE
        p.product_id = :product_id
        AND p.status = 1

    LIMIT 1
";


try {

    $stmt = $conn->prepare($sql);

    $stmt->execute([
        ':product_id' => $product_id
    ]);

    $product = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {

    die("Product Fetch Error: " .
        htmlspecialchars($e->getMessage()));
}


/* =========================================================
   PRODUCT NOT FOUND
========================================================= */

if (!$product) {

    header("Location: gallery.php");
    exit;
}


/* =========================================================
   FETCH PRODUCT IMAGES
========================================================= */

$imageSql = "
    SELECT
        image_id,
        image_name,
        is_primary

    FROM product_images

    WHERE product_id = :product_id

    ORDER BY
        is_primary DESC,
        image_id ASC

    LIMIT 3
";


try {

    $imageStmt = $conn->prepare($imageSql);

    $imageStmt->execute([
        ':product_id' => $product_id
    ]);

    $productImages =
        $imageStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {

    $productImages = [];
}


/* =========================================================
   IMAGE LIST
========================================================= */

$images = [];


/* ---------------------------------------------------------
   IMAGES FROM product_images TABLE
--------------------------------------------------------- */

foreach ($productImages as $image) {

    $imageName =
        trim($image['image_name'] ?? '');

    if ($imageName !== '') {

        $images[] =
            "uploads/products/" .
            basename($imageName);
    }
}


/* ---------------------------------------------------------
   OLD product_image FALLBACK
--------------------------------------------------------- */

if (empty($images)) {

    $oldImage =
        trim($product['product_image'] ?? '');

    if ($oldImage !== '') {

        if (
            strpos(
                $oldImage,
                'assets/images/'
            ) === 0
        ) {

            $images[] = $oldImage;
        } else {

            $images[] =
                "assets/images/" .
                basename($oldImage);
        }
    }
}


/* ---------------------------------------------------------
   NO IMAGE FALLBACK
--------------------------------------------------------- */

if (empty($images)) {

    $images[] =
        "assets/images/no-image.jpg";
}


/* =========================================================
   PRICE
========================================================= */

$sellingPrice =
    $product['selling_price'] ?? null;

$originalPrice =
    $product['original_price'] ?? null;

$discount =
    $product['discount_percentage'] ?? null;


/* =========================================================
   STOCK
========================================================= */

$stock =
    (int)($product['stock_quantity'] ?? 0);

$outOfStock =
    ($stock <= 0);


/* =========================================================
   PRODUCT DETAILS
========================================================= */

$productName =
    $product['product_name'] ?? 'Flower';

$category =
    $product['category_name'] ?? 'Flowers';

$subcategory =
    $product['subcategory_name'] ?? 'Flowers';

$description =
    trim($product['product_description'] ?? '');

if ($description === '') {

    $description =
        "Beautiful fresh flowers arranged with care. "
        . "Perfect for gifting, celebrations and special occasions.";
}

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
        name="description"
        content="<?php echo htmlspecialchars($productName); ?>">

    <title>
        <?php echo htmlspecialchars($productName); ?> - Fior
    </title>


    <!-- Owl Carousel -->

    <link
        rel="stylesheet"
        type="text/css"
        href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.1.3/assets/owl.carousel.min.css">


    <!-- Bootstrap -->

    <link
        rel="stylesheet"
        type="text/css"
        href="css/bootstrap.css">


    <!-- Fonts -->

    <link
        href="https://fonts.googleapis.com/css?family=Baloo+Chettan|Poppins:400,600,700&display=swap"
        rel="stylesheet">


    <!-- Main CSS -->

    <link
        href="css/style.css"
        rel="stylesheet">


    <!-- Responsive CSS -->

    <link
        href="css/responsive.css"
        rel="stylesheet">


    <style>
        /* =====================================================
           PRODUCT DETAILS
        ===================================================== */

        .product_details_section {

            padding: 70px 0;

            background: #fff;

        }


        .product_details_wrapper {

            background: #ffffff;

            border-radius: 8px;

            padding: 30px;

            box-shadow:
                0 3px 20px rgba(0, 0, 0, 0.08);

        }


        /* =====================================================
           IMAGE AREA
        ===================================================== */

        .product_image_area {

            display: flex;

            gap: 15px;

        }


        .product_thumbnails {

            width: 85px;

            display: flex;

            flex-direction: column;

            gap: 12px;

        }


        .product_thumbnail {

            width: 80px;

            height: 80px;

            object-fit: cover;

            border-radius: 6px;

            border: 2px solid #eeeeee;

            cursor: pointer;

            padding: 2px;

            transition: 0.3s;

        }


        .product_thumbnail:hover,

        .product_thumbnail.active {

            border-color: #df2f68;

        }


        .product_main_image {

            flex: 1;

        }


        .product_main_image img {

            width: 100%;

            height: 480px;

            object-fit: cover;

            border-radius: 8px;

            display: block;

        }


        /* =====================================================
           PRODUCT INFORMATION
        ===================================================== */

        .product_details_info {

            padding: 10px 10px 10px 30px;

        }


        .product_details_category {

            color: #df2f68;

            font-size: 14px;

            font-weight: 600;

            margin-bottom: 8px;

            text-transform: uppercase;

        }


        .product_details_info h1 {

            font-family: 'Baloo Chettan', cursive;

            color: #17233c;

            font-size: 38px;

            line-height: 1.2;

            margin-bottom: 15px;

        }


        .product_code {

            color: #888;

            font-size: 14px;

            margin-bottom: 20px;

        }


        /* =====================================================
           PRICE
        ===================================================== */

        .details_price {

            margin-bottom: 20px;

        }


        .details_selling_price {

            color: #df2f68;

            font-size: 30px;

            font-weight: 700;

        }


        .details_original_price {

            color: #999;

            text-decoration: line-through;

            font-size: 17px;

            margin-left: 10px;

        }


        .details_discount {

            background: #df2f68;

            color: #ffffff;

            padding: 5px 10px;

            border-radius: 15px;

            font-size: 12px;

            margin-left: 8px;

        }


        /* =====================================================
           DESCRIPTION
        ===================================================== */

        .details_description {

            color: #666;

            font-size: 15px;

            line-height: 1.8;

            margin-bottom: 20px;

        }


        /* =====================================================
           PRODUCT INFORMATION
        ===================================================== */

        .product_information {

            border-top: 1px solid #eeeeee;

            border-bottom: 1px solid #eeeeee;

            padding: 15px 0;

            margin-bottom: 20px;

        }


        .information_row {

            display: flex;

            padding: 6px 0;

            font-size: 14px;

        }


        .information_label {

            width: 130px;

            color: #555;

            font-weight: 600;

        }


        .information_value {

            color: #777;

        }


        /* =====================================================
           STOCK
        ===================================================== */

        .stock_available {

            color: #198754;

            font-weight: 600;

            margin-bottom: 15px;

        }


        .stock_unavailable {

            color: #dc3545;

            font-weight: 600;

            margin-bottom: 15px;

        }


        /* =====================================================
           ADD CART
        ===================================================== */

        .details_add_cart {

            border: none;

            background: #df2f68;

            color: #ffffff;

            padding: 13px 30px;

            border-radius: 6px;

            font-size: 15px;

            cursor: pointer;

            transition: 0.3s;

        }


        .details_add_cart:hover {

            background: #c92359;

        }


        .details_add_cart:disabled {

            background: #999;

            cursor: not-allowed;

        }


        /* =====================================================
           BACK BUTTON
        ===================================================== */

        .back_gallery {

            display: inline-block;

            margin-bottom: 20px;

            color: #df2f68;

            font-size: 14px;

            font-weight: 600;

            text-decoration: none;

        }


        .back_gallery:hover {

            color: #c92359;

            text-decoration: none;

        }


        /* =====================================================
           RESPONSIVE
        ===================================================== */

        @media (max-width: 991px) {

            .product_details_info {

                padding:
                    30px 0 0 0;

            }

            .product_main_image img {

                height: 400px;

            }

        }


        @media (max-width: 576px) {

            .product_image_area {

                flex-direction: column;

            }


            .product_thumbnails {

                width: 100%;

                flex-direction: row;

                overflow-x: auto;

            }


            .product_thumbnail {

                flex-shrink: 0;

            }


            .product_main_image img {

                height: 350px;

            }


            .product_details_info h1 {

                font-size: 30px;

            }

        }
    </style>

</head>


<body class="sub_page">


    <!-- =====================================================
         HEADER
    ===================================================== -->

    <div class="hero_area">

        <?php include 'header.php'; ?>

    </div>


    <!-- =====================================================
         PRODUCT DETAILS
    ===================================================== -->

    <section class="product_details_section">

        <div class="container">


            <!-- BACK -->

            <a
                href="gallery.php"
                class="back_gallery">

                ← Back to Gallery

            </a>


            <div class="product_details_wrapper">

                <div class="row">


                    <!-- =================================================
                         LEFT IMAGE AREA
                    ================================================== -->

                    <div class="col-lg-6">

                        <div class="product_image_area">


                            <!-- THUMBNAILS -->

                            <div class="product_thumbnails">

                                <?php foreach (
                                    $images
                                    as $index => $image
                                ) { ?>

                                    <img
                                        src="<?php echo htmlspecialchars($image); ?>"
                                        class="product_thumbnail <?php echo $index === 0 ? 'active' : ''; ?>"
                                        alt="<?php echo htmlspecialchars($productName); ?>"
                                        onclick="changeProductImage(this)">

                                <?php } ?>

                            </div>


                            <!-- MAIN IMAGE -->

                            <div class="product_main_image">

                                <img
                                    id="mainProductImage"
                                    src="<?php echo htmlspecialchars($images[0]); ?>"
                                    alt="<?php echo htmlspecialchars($productName); ?>">

                            </div>

                        </div>

                    </div>


                    <!-- =================================================
                         RIGHT PRODUCT INFORMATION
                    ================================================== -->

                    <div class="col-lg-6">

                        <div class="product_details_info">


                            <!-- CATEGORY -->

                            <div class="product_details_category">

                                <?php
                                echo htmlspecialchars($category);
                                ?>

                                /

                                <?php
                                echo htmlspecialchars($subcategory);
                                ?>

                            </div>


                            <!-- PRODUCT NAME -->

                            <h1>

                                <?php
                                echo htmlspecialchars($productName);
                                ?>

                            </h1>


                            <!-- PRODUCT CODE -->

                            <?php if (
                                !empty($product['product_code'])
                            ) { ?>

                                <div class="product_code">

                                    Product Code:
                                    <strong>
                                        <?php
                                        echo htmlspecialchars(
                                            $product['product_code']
                                        );
                                        ?>
                                    </strong>

                                </div>

                            <?php } ?>


                            <!-- PRICE -->

                            <div class="details_price">

                                <?php if (
                                    $sellingPrice !== null
                                ) { ?>

                                    <span
                                        class="details_selling_price">

                                        ₹<?php
                                            echo number_format(
                                                (float)$sellingPrice,
                                                2
                                            );
                                            ?>

                                    </span>

                                <?php } else { ?>

                                    <span
                                        class="details_selling_price">

                                        Price Not Available

                                    </span>

                                <?php } ?>


                                <?php if (
                                    $originalPrice !== null &&
                                    $sellingPrice !== null &&
                                    (float)$originalPrice >
                                    (float)$sellingPrice
                                ) { ?>

                                    <span
                                        class="details_original_price">

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

                                    <span
                                        class="details_discount">

                                        <?php
                                        echo number_format(
                                            (float)$discount,
                                            0
                                        );
                                        ?>% OFF

                                    </span>

                                <?php } ?>

                            </div>


                            <!-- DESCRIPTION -->

                            <p class="details_description">

                                <?php
                                echo nl2br(
                                    htmlspecialchars(
                                        $description
                                    )
                                );
                                ?>

                            </p>


                            <!-- PRODUCT INFORMATION -->

                            <div class="product_information">


                                <?php if (
                                    !empty($product['brand_name'])
                                ) { ?>

                                    <div
                                        class="information_row">

                                        <div
                                            class="information_label">

                                            Brand

                                        </div>

                                        <div
                                            class="information_value">

                                            <?php
                                            echo htmlspecialchars(
                                                $product['brand_name']
                                            );
                                            ?>

                                        </div>

                                    </div>

                                <?php } ?>


                                <?php if (
                                    !empty($product['color'])
                                ) { ?>

                                    <div
                                        class="information_row">

                                        <div
                                            class="information_label">

                                            Color

                                        </div>

                                        <div
                                            class="information_value">

                                            <?php
                                            echo htmlspecialchars(
                                                $product['color']
                                            );
                                            ?>

                                        </div>

                                    </div>

                                <?php } ?>


                                <?php if (
                                    !empty($product['size'])
                                ) { ?>

                                    <div
                                        class="information_row">

                                        <div
                                            class="information_label">

                                            Size

                                        </div>

                                        <div
                                            class="information_value">

                                            <?php
                                            echo htmlspecialchars(
                                                $product['size']
                                            );
                                            ?>

                                        </div>

                                    </div>

                                <?php } ?>


                                <?php if (
                                    !empty($product['material'])
                                ) { ?>

                                    <div
                                        class="information_row">

                                        <div
                                            class="information_label">

                                            Material

                                        </div>

                                        <div
                                            class="information_value">

                                            <?php
                                            echo htmlspecialchars(
                                                $product['material']
                                            );
                                            ?>

                                        </div>

                                    </div>

                                <?php } ?>


                            </div>


                            <!-- STOCK -->

                            <?php if ($outOfStock) { ?>

                                <div
                                    class="stock_unavailable">

                                    ❌ Out of Stock

                                </div>

                            <?php } else { ?>

                                <div
                                    class="stock_available">

                                    ✓ In Stock
                                    (<?php echo $stock; ?> available)

                                </div>

                            <?php } ?>


                            <!-- ADD TO CART -->

                            <?php if ($outOfStock) { ?>

                                <button
                                    type="button"
                                    class="details_add_cart"
                                    disabled>

                                    Out of Stock

                                </button>

                            <?php } else { ?>

                                <button
                                    type="button"
                                    class="details_add_cart"
                                    id="detailsAddCart"
                                    data-product-id="<?php echo (int)$product_id; ?>">

                                    🛒 Add to Cart

                                </button>

                            <?php } ?>


                        </div>

                    </div>

                </div>

            </div>

        </div>

    </section>


    <!-- =====================================================
         FOOTER
    ===================================================== -->

    <?php include 'footer.php'; ?>


    <!-- =====================================================
         JAVASCRIPT
    ===================================================== -->

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
         IMAGE CHANGE
    ===================================================== -->

    <script>
        function changeProductImage(element) {

            var mainImage =
                document.getElementById(
                    'mainProductImage'
                );

            mainImage.src =
                element.src;


            document
                .querySelectorAll(
                    '.product_thumbnail'
                )
                .forEach(function(image) {

                    image.classList.remove(
                        'active'
                    );

                });


            element.classList.add(
                'active'
            );
        }
    </script>


    <!-- =====================================================
         ADD TO CART
    ===================================================== -->

    <script>
        var addCartButton =
            document.getElementById(
                'detailsAddCart'
            );


        if (addCartButton) {

            addCartButton.addEventListener(
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
                            productId
                        )

                        .then(function(response) {

                            return response.json();

                        })

                        .then(function(data) {


                            /* =================================================
                               LOGIN REQUIRED
                            ================================================= */

                            if (data.login_required) {

                                window.location.href =
                                    "login.php";

                                return;
                            }


                            /* =================================================
                               SUCCESS
                            ================================================= */

                            if (
                                data.success === true ||
                                data.status === "success"
                            ) {

                                var quantity =
                                    parseInt(
                                        data.quantity
                                    ) || 1;


                                currentButton.innerHTML =
                                    "🛒 Add to Cart (" +
                                    quantity +
                                    ")";


                                currentButton.disabled =
                                    false;

                                return;
                            }


                            /* =================================================
                               ERROR
                            ================================================= */

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

        }
    </script>


</body>

</html>