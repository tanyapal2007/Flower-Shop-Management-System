<?php

/* =========================================================
   DATABASE
========================================================= */

include "../config/database.php";


/* =========================================================
   CHECK PRODUCT ID
========================================================= */

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {

    header("Location: products.php");
    exit;
}

$product_id = intval($_GET['id']);

if ($product_id <= 0) {

    header("Location: products.php");
    exit;
}


/* =========================================================
   FETCH PRODUCT
========================================================= */

$product_sql = "
SELECT
    p.product_id,
    p.subcategory_id,
    p.product_name,
    p.product_description,
    p.product_code,
    p.stock_quantity,
    p.status,
    p.created_at,
    p.updated_at,

    p.brand_name,
    p.color,
    p.size,
    p.material,

    ps.category_id,
    ps.subcategory_name,

    pc.category_name

FROM products p

LEFT JOIN product_subcategory ps
    ON p.subcategory_id = ps.subcategory_id

LEFT JOIN product_category pc
    ON ps.category_id = pc.category_id

WHERE p.product_id = $product_id
LIMIT 1
";


$product_result = mysqli_query($conn, $product_sql);


if (!$product_result) {

    die("Product Query Error: " . mysqli_error($conn));
}


if (mysqli_num_rows($product_result) == 0) {

    header("Location: products.php");
    exit;
}


$product = mysqli_fetch_assoc($product_result);


/* =========================================================
   FETCH EXISTING PRICE
========================================================= */

$price = [

    'price_id' => '',
    'original_price' => '',
    'discount_percentage' => '0',
    'selling_price' => '',
    'start_date' => '',
    'end_date' => '',
    'status' => '1'

];


$price_sql = "
SELECT
    price_id,
    original_price,
    discount_percentage,
    selling_price,
    start_date,
    end_date,
    status

FROM product_prices

WHERE product_id = $product_id

ORDER BY price_id DESC

LIMIT 1
";


$price_result = mysqli_query($conn, $price_sql);


if ($price_result && mysqli_num_rows($price_result) > 0) {

    $price = mysqli_fetch_assoc($price_result);
}


/* =========================================================
   UPDATE PRODUCT
========================================================= */

$error_message = "";


if (isset($_POST['update_product'])) {


    /* -----------------------------------------------------
       GET FORM DATA
    ----------------------------------------------------- */

    $subcategory_id = intval($_POST['subcategory_id'] ?? 0);

    $product_name = trim(
        $_POST['product_name'] ?? ''
    );

    $product_description = trim(
        $_POST['product_description'] ?? ''
    );

    $product_code = trim(
        $_POST['product_code'] ?? ''
    );

    $stock_quantity = intval(
        $_POST['stock_quantity'] ?? 0
    );

    $brand_name = trim(
        $_POST['brand_name'] ?? ''
    );

    $color = trim(
        $_POST['color'] ?? ''
    );

    $size = trim(
        $_POST['size'] ?? ''
    );

    $material = trim(
        $_POST['material'] ?? ''
    );

    $status = intval(
        $_POST['status'] ?? 1
    );


    /* -----------------------------------------------------
       PRICE DATA
    ----------------------------------------------------- */

    $original_price = trim(
        $_POST['original_price'] ?? ''
    );

    $discount_percentage = trim(
        $_POST['discount_percentage'] ?? '0'
    );

    $selling_price = trim(
        $_POST['selling_price'] ?? ''
    );

    $start_date = trim(
        $_POST['start_date'] ?? ''
    );

    $end_date = trim(
        $_POST['end_date'] ?? ''
    );

    $price_status = intval(
        $_POST['price_status'] ?? 1
    );


    /* =====================================================
       VALIDATION
    ===================================================== */

    if ($subcategory_id <= 0) {

        $error_message = "Please select a subcategory.";
    } elseif ($product_name == '') {

        $error_message = "Product name is required.";
    } elseif ($product_code == '') {

        $error_message = "Product code is required.";
    } elseif ($stock_quantity < 0) {

        $error_message = "Stock quantity cannot be negative.";
    }


    /* =====================================================
       CHECK DUPLICATE PRODUCT CODE
    ===================================================== */

    if ($error_message == '') {


        $code_check_sql = "
        SELECT product_id

        FROM products

        WHERE product_code = ?
        AND product_id != ?

        LIMIT 1
        ";


        $code_stmt = mysqli_prepare(
            $conn,
            $code_check_sql
        );


        mysqli_stmt_bind_param(
            $code_stmt,
            "si",
            $product_code,
            $product_id
        );


        mysqli_stmt_execute($code_stmt);


        $code_result = mysqli_stmt_get_result(
            $code_stmt
        );


        if (mysqli_num_rows($code_result) > 0) {

            $error_message =
                "This product code already exists. Please use another product code.";
        }


        mysqli_stmt_close($code_stmt);
    }


    /* =====================================================
       UPDATE PRODUCTS TABLE
    ===================================================== */

    if ($error_message == '') {


        $update_sql = "
        UPDATE products

        SET
            subcategory_id = ?,
            product_name = ?,
            product_description = ?,
            product_code = ?,
            stock_quantity = ?,
            status = ?,
            brand_name = ?,
            color = ?,
            size = ?,
            material = ?

        WHERE product_id = ?
        ";


        $update_stmt = mysqli_prepare(
            $conn,
            $update_sql
        );


        if (!$update_stmt) {

            $error_message =
                "Prepare Error: " . mysqli_error($conn);
        } else {


            mysqli_stmt_bind_param(
                $update_stmt,
                "isssiissssi",
                $subcategory_id,
                $product_name,
                $product_description,
                $product_code,
                $stock_quantity,
                $status,
                $brand_name,
                $color,
                $size,
                $material,
                $product_id
            );


            if (!mysqli_stmt_execute($update_stmt)) {

                $error_message =
                    "Product Update Error: "
                    . mysqli_stmt_error($update_stmt);
            }


            mysqli_stmt_close($update_stmt);
        }
    }


    /* =====================================================
       UPDATE / INSERT PRICE
    ===================================================== */

    if ($error_message == '') {


        /*
         * Price is optional.
         * If original price is entered, save/update price.
         */

        if ($original_price !== '') {


            if ($selling_price === '') {

                /*
                 * Automatically calculate selling price
                 */

                $selling_price =
                    (float)$original_price
                    -
                    (
                        (float)$original_price
                        *
                        (float)$discount_percentage
                        /
                        100
                    );
            }


            /* ---------------------------------------------
               CHECK EXISTING PRICE
            --------------------------------------------- */

            $existing_price_sql = "
            SELECT price_id

            FROM product_prices

            WHERE product_id = ?

            ORDER BY price_id DESC

            LIMIT 1
            ";


            $existing_price_stmt = mysqli_prepare(
                $conn,
                $existing_price_sql
            );


            mysqli_stmt_bind_param(
                $existing_price_stmt,
                "i",
                $product_id
            );


            mysqli_stmt_execute(
                $existing_price_stmt
            );


            $existing_price_result =
                mysqli_stmt_get_result(
                    $existing_price_stmt
                );


            if (
                mysqli_num_rows(
                    $existing_price_result
                ) > 0
            ) {


                $existing_price =
                    mysqli_fetch_assoc(
                        $existing_price_result
                    );


                $price_id =
                    intval(
                        $existing_price['price_id']
                    );


                /* -----------------------------------------
                   UPDATE PRICE
                ----------------------------------------- */

                $price_update_sql = "
                UPDATE product_prices

                SET
                    original_price = ?,
                    discount_percentage = ?,
                    selling_price = ?,
                    start_date = NULLIF(?, ''),
                    end_date = NULLIF(?, ''),
                    status = ?

                WHERE price_id = ?
                ";


                $price_stmt = mysqli_prepare(
                    $conn,
                    $price_update_sql
                );


                mysqli_stmt_bind_param(
                    $price_stmt,
                    "dddssii",
                    $original_price,
                    $discount_percentage,
                    $selling_price,
                    $start_date,
                    $end_date,
                    $price_status,
                    $price_id
                );


                if (!mysqli_stmt_execute($price_stmt)) {

                    $error_message =
                        "Price Update Error: "
                        . mysqli_stmt_error($price_stmt);
                }


                mysqli_stmt_close($price_stmt);
            } else {


                /* -----------------------------------------
                   INSERT NEW PRICE
                ----------------------------------------- */

                $price_insert_sql = "
                INSERT INTO product_prices
                (
                    product_id,
                    original_price,
                    discount_percentage,
                    selling_price,
                    start_date,
                    end_date,
                    status
                )

                VALUES
                (
                    ?,
                    ?,
                    ?,
                    ?,
                    NULLIF(?, ''),
                    NULLIF(?, ''),
                    ?
                )
                ";


                $price_stmt = mysqli_prepare(
                    $conn,
                    $price_insert_sql
                );


                mysqli_stmt_bind_param(
                    $price_stmt,
                    "idddssi",
                    $product_id,
                    $original_price,
                    $discount_percentage,
                    $selling_price,
                    $start_date,
                    $end_date,
                    $price_status
                );


                if (!mysqli_stmt_execute($price_stmt)) {

                    $error_message =
                        "Price Insert Error: "
                        . mysqli_stmt_error($price_stmt);
                }


                mysqli_stmt_close($price_stmt);
            }


            mysqli_stmt_close(
                $existing_price_stmt
            );
        }
    }


    /* =====================================================
       SUCCESS
    ===================================================== */

    if ($error_message == '') {

        header("Location: products.php");
        exit;
    }
}


/* =========================================================
   FETCH ALL CATEGORIES
========================================================= */

$category_sql = "
SELECT
    category_id,
    category_name

FROM product_category

WHERE status = 1

ORDER BY category_name ASC
";


$category_result =
    mysqli_query(
        $conn,
        $category_sql
    );


/* =========================================================
   FETCH ALL SUBCATEGORIES
========================================================= */

$subcategory_sql = "
SELECT
    subcategory_id,
    category_id,
    subcategory_name

FROM product_subcategory

WHERE status = 1

ORDER BY subcategory_name ASC
";


$subcategory_result =
    mysqli_query(
        $conn,
        $subcategory_sql
    );


?>

<!DOCTYPE html>

<html lang="en">


<head>

    <meta charset="utf-8">

    <meta
        http-equiv="X-UA-Compatible"
        content="IE=edge">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1">


    <title>Edit Product</title>


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


    <!-- iCheck -->

    <link
        href="assets/vendors/iCheck/skins/flat/green.css"
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

            <?php include 'sidebar.php'; ?>


            <!-- =====================================================
         RIGHT CONTENT
    ====================================================== -->

            <div
                class="right_col"
                role="main">


                <!-- =================================================
             TOP NAVIGATION
        ================================================== -->

                <div class="top_nav">

                    <div class="nav_menu">

                        <nav>

                            <div class="nav toggle">

                                <a id="menu_toggle">

                                    <i class="fa fa-bars"></i>

                                </a>

                            </div>


                            <ul
                                class="nav navbar-nav navbar-right">

                                <li>

                                    <a
                                        href="javascript:;"
                                        class="user-profile dropdown-toggle"
                                        data-toggle="dropdown">

                                        <img
                                            src="assets/images/img.jpg"
                                            alt="">

                                        John Doe

                                        <span
                                            class="fa fa-angle-down">
                                        </span>

                                    </a>


                                    <ul
                                        class="dropdown-menu dropdown-usermenu pull-right">

                                        <li>
                                            <a href="javascript:;">
                                                Profile
                                            </a>
                                        </li>

                                        <li>
                                            <a href="javascript:;">
                                                Settings
                                            </a>
                                        </li>

                                        <li>
                                            <a href="javascript:;">
                                                Help
                                            </a>
                                        </li>

                                        <li>

                                            <a href="login.php">

                                                <i
                                                    class="fa fa-sign-out pull-right">
                                                </i>

                                                Log Out

                                            </a>

                                        </li>

                                    </ul>

                                </li>

                            </ul>

                        </nav>

                    </div>

                </div>


                <!-- =================================================
             PAGE CONTENT
        ================================================== -->

                <div
                    class="container-fluid"
                    style="padding:25px;">


                    <!-- PAGE HEADER -->

                    <div class="row">

                        <div class="col-md-8">

                            <h2 style="margin-top:0;">

                                Edit Product

                            </h2>

                            <p class="text-muted">

                                Update product information

                            </p>

                        </div>


                        <div class="col-md-4 text-right">

                            <a
                                href="products.php"
                                class="btn btn-default">

                                <i class="fa fa-arrow-left"></i>

                                Back to Products

                            </a>

                        </div>

                    </div>


                    <br>


                    <!-- =================================================
                 ERROR MESSAGE
            ================================================== -->

                    <?php if ($error_message != '') { ?>

                        <div class="alert alert-danger">

                            <i class="fa fa-times-circle"></i>

                            <?php
                            echo htmlspecialchars(
                                $error_message
                            );
                            ?>

                        </div>

                    <?php } ?>


                    <!-- =================================================
                 PRODUCT FORM
            ================================================== -->

                    <div class="x_panel">


                        <div class="x_title">

                            <h2>

                                Product Information

                            </h2>

                            <div class="clearfix"></div>

                        </div>


                        <div class="x_content">


                            <form
                                method="POST"
                                class="form-horizontal form-label-left">


                                <!-- =================================================
                             CATEGORY
                        ================================================== -->

                                <div class="form-group">

                                    <label
                                        class="control-label col-md-3">

                                        Category
                                        <span class="text-danger">
                                            *
                                        </span>

                                    </label>


                                    <div class="col-md-6">

                                        <select
                                            id="category_id"
                                            class="form-control"
                                            required>

                                            <option value="">

                                                Select Category

                                            </option>


                                            <?php

                                            if ($category_result) {

                                                while (
                                                    $category =
                                                    mysqli_fetch_assoc(
                                                        $category_result
                                                    )
                                                ) {

                                            ?>

                                                    <option
                                                        value="<?php
                                                                echo $category['category_id'];
                                                                ?>"
                                                        <?php

                                                        if (
                                                            $category['category_id']
                                                            ==
                                                            $product['category_id']
                                                        ) {

                                                            echo 'selected';
                                                        }

                                                        ?>>

                                                        <?php

                                                        echo htmlspecialchars(
                                                            $category['category_name']
                                                        );

                                                        ?>

                                                    </option>

                                            <?php

                                                }
                                            }

                                            ?>

                                        </select>

                                    </div>

                                </div>


                                <!-- =================================================
                             SUBCATEGORY
                        ================================================== -->

                                <div class="form-group">

                                    <label
                                        class="control-label col-md-3">

                                        Subcategory
                                        <span class="text-danger">
                                            *
                                        </span>

                                    </label>


                                    <div class="col-md-6">

                                        <select
                                            name="subcategory_id"
                                            id="subcategory_id"
                                            class="form-control"
                                            required>

                                            <option value="">

                                                Select Subcategory

                                            </option>


                                            <?php

                                            if ($subcategory_result) {

                                                while (
                                                    $subcategory =
                                                    mysqli_fetch_assoc(
                                                        $subcategory_result
                                                    )
                                                ) {

                                            ?>

                                                    <option
                                                        value="<?php
                                                                echo $subcategory['subcategory_id'];
                                                                ?>"
                                                        data-category="<?php
                                                                        echo $subcategory['category_id'];
                                                                        ?>"
                                                        <?php

                                                        if (
                                                            $subcategory['subcategory_id']
                                                            ==
                                                            $product['subcategory_id']
                                                        ) {

                                                            echo 'selected';
                                                        }

                                                        ?>>

                                                        <?php

                                                        echo htmlspecialchars(
                                                            $subcategory['subcategory_name']
                                                        );

                                                        ?>

                                                    </option>

                                            <?php

                                                }
                                            }

                                            ?>

                                        </select>

                                    </div>

                                </div>


                                <!-- =================================================
                             PRODUCT NAME
                        ================================================== -->

                                <div class="form-group">

                                    <label
                                        class="control-label col-md-3">

                                        Product Name
                                        <span class="text-danger">
                                            *
                                        </span>

                                    </label>


                                    <div class="col-md-6">

                                        <input
                                            type="text"
                                            name="product_name"
                                            class="form-control"
                                            required
                                            value="<?php
                                                    echo htmlspecialchars(
                                                        $product['product_name']
                                                    );
                                                    ?>">

                                    </div>

                                </div>


                                <!-- =================================================
                             PRODUCT CODE
                        ================================================== -->

                                <div class="form-group">

                                    <label
                                        class="control-label col-md-3">

                                        Product Code
                                        <span class="text-danger">
                                            *
                                        </span>

                                    </label>


                                    <div class="col-md-6">

                                        <input
                                            type="text"
                                            name="product_code"
                                            class="form-control"
                                            required
                                            value="<?php
                                                    echo htmlspecialchars(
                                                        $product['product_code']
                                                    );
                                                    ?>">

                                    </div>

                                </div>


                                <!-- =================================================
                             DESCRIPTION
                        ================================================== -->

                                <div class="form-group">

                                    <label
                                        class="control-label col-md-3">

                                        Description

                                    </label>


                                    <div class="col-md-6">

                                        <textarea
                                            name="product_description"
                                            class="form-control"
                                            rows="5"><?php

                                                        echo htmlspecialchars(
                                                            $product['product_description'] ?? ''
                                                        );

                                                        ?></textarea>

                                    </div>

                                </div>


                                <!-- =================================================
                             BRAND
                        ================================================== -->

                                <div class="form-group">

                                    <label
                                        class="control-label col-md-3">

                                        Brand Name

                                    </label>


                                    <div class="col-md-6">

                                        <input
                                            type="text"
                                            name="brand_name"
                                            class="form-control"
                                            value="<?php
                                                    echo htmlspecialchars(
                                                        $product['brand_name'] ?? ''
                                                    );
                                                    ?>">

                                    </div>

                                </div>


                                <!-- =================================================
                             COLOR
                        ================================================== -->

                                <div class="form-group">

                                    <label
                                        class="control-label col-md-3">

                                        Color

                                    </label>


                                    <div class="col-md-6">

                                        <input
                                            type="text"
                                            name="color"
                                            class="form-control"
                                            value="<?php
                                                    echo htmlspecialchars(
                                                        $product['color'] ?? ''
                                                    );
                                                    ?>">

                                    </div>

                                </div>


                                <!-- =================================================
                             SIZE
                        ================================================== -->

                                <div class="form-group">

                                    <label
                                        class="control-label col-md-3">

                                        Size

                                    </label>


                                    <div class="col-md-6">

                                        <input
                                            type="text"
                                            name="size"
                                            class="form-control"
                                            value="<?php
                                                    echo htmlspecialchars(
                                                        $product['size'] ?? ''
                                                    );
                                                    ?>">

                                    </div>

                                </div>


                                <!-- =================================================
                             MATERIAL
                        ================================================== -->

                                <div class="form-group">

                                    <label
                                        class="control-label col-md-3">

                                        Material

                                    </label>


                                    <div class="col-md-6">

                                        <input
                                            type="text"
                                            name="material"
                                            class="form-control"
                                            value="<?php
                                                    echo htmlspecialchars(
                                                        $product['material'] ?? ''
                                                    );
                                                    ?>">

                                    </div>

                                </div>


                                <!-- =================================================
                             STOCK
                        ================================================== -->

                                <div class="form-group">

                                    <label
                                        class="control-label col-md-3">

                                        Stock Quantity

                                    </label>


                                    <div class="col-md-6">

                                        <input
                                            type="number"
                                            name="stock_quantity"
                                            class="form-control"
                                            min="0"
                                            value="<?php
                                                    echo (int)$product['stock_quantity'];
                                                    ?>">

                                    </div>

                                </div>


                                <!-- =================================================
                             STATUS
                        ================================================== -->

                                <div class="form-group">

                                    <label
                                        class="control-label col-md-3">

                                        Status

                                    </label>


                                    <div class="col-md-6">

                                        <select
                                            name="status"
                                            class="form-control">


                                            <option
                                                value="1"
                                                <?php

                                                if (
                                                    $product['status'] == 1
                                                ) {

                                                    echo 'selected';
                                                }

                                                ?>>

                                                Active

                                            </option>


                                            <option
                                                value="0"
                                                <?php

                                                if (
                                                    $product['status'] == 0
                                                ) {

                                                    echo 'selected';
                                                }

                                                ?>>

                                                Inactive

                                            </option>


                                        </select>

                                    </div>

                                </div>


                                <!-- =================================================
                             PRICE SECTION
                        ================================================== -->

                                <div class="ln_solid"></div>


                                <h3>

                                    <i class="fa fa-money"></i>

                                    Product Price

                                </h3>


                                <br>


                                <!-- ORIGINAL PRICE -->

                                <div class="form-group">

                                    <label
                                        class="control-label col-md-3">

                                        Original Price

                                    </label>


                                    <div class="col-md-6">

                                        <input
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            name="original_price"
                                            id="original_price"
                                            class="form-control"
                                            value="<?php
                                                    echo htmlspecialchars(
                                                        $price['original_price'] ?? ''
                                                    );
                                                    ?>">

                                    </div>

                                </div>


                                <!-- DISCOUNT -->

                                <div class="form-group">

                                    <label
                                        class="control-label col-md-3">

                                        Discount (%)

                                    </label>


                                    <div class="col-md-6">

                                        <input
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            max="100"
                                            name="discount_percentage"
                                            id="discount_percentage"
                                            class="form-control"
                                            value="<?php
                                                    echo htmlspecialchars(
                                                        $price['discount_percentage'] ?? '0'
                                                    );
                                                    ?>">

                                    </div>

                                </div>


                                <!-- SELLING PRICE -->

                                <div class="form-group">

                                    <label
                                        class="control-label col-md-3">

                                        Selling Price

                                    </label>


                                    <div class="col-md-6">

                                        <input
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            name="selling_price"
                                            id="selling_price"
                                            class="form-control"
                                            value="<?php
                                                    echo htmlspecialchars(
                                                        $price['selling_price'] ?? ''
                                                    );
                                                    ?>">

                                        <small class="text-muted">

                                            Selling price can be calculated
                                            automatically using discount.

                                        </small>

                                    </div>

                                </div>


                                <!-- START DATE -->

                                <div class="form-group">

                                    <label
                                        class="control-label col-md-3">

                                        Start Date

                                    </label>


                                    <div class="col-md-6">

                                        <input
                                            type="date"
                                            name="start_date"
                                            class="form-control"
                                            value="<?php
                                                    echo htmlspecialchars(
                                                        $price['start_date'] ?? ''
                                                    );
                                                    ?>">

                                    </div>

                                </div>


                                <!-- END DATE -->

                                <div class="form-group">

                                    <label
                                        class="control-label col-md-3">

                                        End Date

                                    </label>


                                    <div class="col-md-6">

                                        <input
                                            type="date"
                                            name="end_date"
                                            class="form-control"
                                            value="<?php
                                                    echo htmlspecialchars(
                                                        $price['end_date'] ?? ''
                                                    );
                                                    ?>">

                                    </div>

                                </div>


                                <!-- PRICE STATUS -->

                                <div class="form-group">

                                    <label
                                        class="control-label col-md-3">

                                        Price Status

                                    </label>


                                    <div class="col-md-6">

                                        <select
                                            name="price_status"
                                            class="form-control">


                                            <option
                                                value="1"
                                                <?php

                                                if (
                                                    ($price['status'] ?? 1) == 1
                                                ) {

                                                    echo 'selected';
                                                }

                                                ?>>

                                                Active

                                            </option>


                                            <option
                                                value="0"
                                                <?php

                                                if (
                                                    ($price['status'] ?? 1) == 0
                                                ) {

                                                    echo 'selected';
                                                }

                                                ?>>

                                                Inactive

                                            </option>


                                        </select>

                                    </div>

                                </div>


                                <!-- =================================================
                             BUTTONS
                        ================================================== -->

                                <div class="ln_solid"></div>


                                <div class="form-group">

                                    <div
                                        class="col-md-6 col-md-offset-3">


                                        <a
                                            href="products.php"
                                            class="btn btn-default">

                                            <i class="fa fa-times"></i>

                                            Cancel

                                        </a>


                                        <button
                                            type="submit"
                                            name="update_product"
                                            value="1"
                                            class="btn btn-primary">

                                            <i class="fa fa-save"></i>

                                            Update Product

                                        </button>


                                    </div>

                                </div>


                            </form>

                        </div>

                    </div>


                </div>


                <!-- =================================================
             FOOTER
        ================================================== -->

                <footer>

                    <div class="pull-right">

                        Gentelella -
                        Bootstrap Admin Template by
                        <a href="https://colorlib.com">
                            Colorlib
                        </a>

                    </div>

                    <div class="clearfix"></div>

                </footer>


            </div>

        </div>

    </div>


    <!-- =========================================================
     JAVASCRIPT
========================================================= -->


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


    <script>
        /* =========================================================
   CATEGORY -> SUBCATEGORY
========================================================= */

        $(document).ready(function() {


            function filterSubcategories() {

                var categoryId =
                    $('#category_id').val();

                $('#subcategory_id option').each(
                    function() {

                        var option =
                            $(this);

                        if (option.val() === '') {

                            option.show();

                            return;
                        }


                        if (
                            option.attr('data-category') ==
                            categoryId
                        ) {

                            option.show();

                        } else {

                            option.hide();
                        }

                    }
                );


                /*
                 * Keep currently selected
                 * subcategory when page loads.
                 */

            }


            $('#category_id').on(
                'change',
                function() {

                    $('#subcategory_id').val('');

                    filterSubcategories();

                }
            );


            filterSubcategories();


            /* =====================================================
               AUTO CALCULATE SELLING PRICE
            ===================================================== */

            $('#original_price, #discount_percentage')
                .on('input', function() {


                    var original =
                        parseFloat(
                            $('#original_price').val()
                        ) || 0;


                    var discount =
                        parseFloat(
                            $('#discount_percentage').val()
                        ) || 0;


                    if (original > 0) {

                        var selling =
                            original -
                            (
                                original *
                                discount /
                                100
                            );


                        $('#selling_price').val(
                            selling.toFixed(2)
                        );
                    }

                });

        });
    </script>


</body>

</html>