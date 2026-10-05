```php
<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../config/database.php";


/* =========================================================
   CHECK LOGIN
========================================================= */

$login_user_id = $_SESSION['user_id'] ?? 0;

if (!$login_user_id) {
    header("Location: ../login.php");
    exit;
}


/* =========================================================
   CHECK ADMIN
========================================================= */

$admin_check_sql = "
    SELECT
        user_id,
        name,
        phone,
        role,
        status,
        created_at
    FROM users
    WHERE user_id = :user_id
    LIMIT 1
";

$admin_check_stmt = $conn->prepare($admin_check_sql);

$admin_check_stmt->execute([
    ':user_id' => $login_user_id
]);

$admin = $admin_check_stmt->fetch(PDO::FETCH_ASSOC);


if (
    !$admin ||
    $admin['role'] !== 'admin' ||
    (int)$admin['status'] !== 1
) {
    header("Location: ../index.php");
    exit;
}


/* =========================================================
   CHECK PRODUCT ID
========================================================= */

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: products.php");
    exit;
}

$product_id = (int)$_GET['id'];

if ($product_id <= 0) {
    header("Location: products.php");
    exit;
}


/* =========================================================
   VARIABLES
========================================================= */

$error_message = "";


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

    WHERE p.product_id = :product_id

    LIMIT 1
";

$product_stmt = $conn->prepare($product_sql);

$product_stmt->execute([
    ':product_id' => $product_id
]);

$product = $product_stmt->fetch(PDO::FETCH_ASSOC);


if (!$product) {
    header("Location: products.php");
    exit;
}


/* =========================================================
   DEFAULT PRICE DATA
========================================================= */

$price = [
    'price_id' => '',
    'original_price' => '',
    'discount_percentage' => '0',
    'selling_price' => '',
    'start_date' => '',
    'end_date' => ''
];


/* =========================================================
   FETCH EXISTING PRICE
========================================================= */

$price_sql = "
    SELECT
        price_id,
        original_price,
        discount_percentage,
        selling_price,
        start_date,
        end_date

    FROM product_prices

    WHERE product_id = :product_id

    ORDER BY price_id DESC

    LIMIT 1
";

$price_stmt = $conn->prepare($price_sql);

$price_stmt->execute([
    ':product_id' => $product_id
]);

$existing_price = $price_stmt->fetch(PDO::FETCH_ASSOC);

if ($existing_price) {
    $price = $existing_price;
}


/* =========================================================
   UPDATE PRODUCT
========================================================= */

if (isset($_POST['update_product'])) {


    /* -----------------------------------------------------
       PRODUCT DATA
    ----------------------------------------------------- */

    $subcategory_id = (int)($_POST['subcategory_id'] ?? 0);

    $product_name = trim(
        $_POST['product_name'] ?? ''
    );

    $product_description = trim(
        $_POST['product_description'] ?? ''
    );

    $product_code = trim(
        $_POST['product_code'] ?? ''
    );

    $stock_quantity = (int)(
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

    $status = (int)(
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


    /* =====================================================
       VALIDATION
    ===================================================== */

    if ($subcategory_id <= 0) {

        $error_message =
            "Please select a subcategory.";

    } elseif ($product_name === '') {

        $error_message =
            "Product name is required.";

    } elseif ($product_code === '') {

        $error_message =
            "Product code is required.";

    } elseif ($stock_quantity < 0) {

        $error_message =
            "Stock quantity cannot be negative.";

    } elseif (
        $discount_percentage !== '' &&
        (
            (float)$discount_percentage < 0 ||
            (float)$discount_percentage > 100
        )
    ) {

        $error_message =
            "Discount must be between 0 and 100.";

    } elseif (
        $original_price !== '' &&
        (float)$original_price < 0
    ) {

        $error_message =
            "Original price cannot be negative.";

    } elseif (
        $selling_price !== '' &&
        (float)$selling_price < 0
    ) {

        $error_message =
            "Selling price cannot be negative.";
    }


    /* =====================================================
       CHECK DUPLICATE PRODUCT CODE
    ===================================================== */

    if ($error_message === '') {

        $code_check_sql = "
            SELECT product_id

            FROM products

            WHERE product_code = :product_code

            AND product_id != :product_id

            LIMIT 1
        ";

        $code_stmt = $conn->prepare(
            $code_check_sql
        );

        $code_stmt->execute([
            ':product_code' => $product_code,
            ':product_id' => $product_id
        ]);

        $duplicate_product =
            $code_stmt->fetch(PDO::FETCH_ASSOC);

        if ($duplicate_product) {

            $error_message =
                "This product code already exists. Please use another product code.";
        }
    }


    /* =====================================================
       UPDATE PRODUCT
    ===================================================== */

    if ($error_message === '') {

        try {

            $update_sql = "
                UPDATE products

                SET
                    subcategory_id = :subcategory_id,
                    product_name = :product_name,
                    product_description = :product_description,
                    product_code = :product_code,
                    stock_quantity = :stock_quantity,
                    status = :status,
                    brand_name = :brand_name,
                    color = :color,
                    size = :size,
                    material = :material

                WHERE product_id = :product_id
            ";

            $update_stmt = $conn->prepare(
                $update_sql
            );

            $update_stmt->execute([

                ':subcategory_id' =>
                    $subcategory_id,

                ':product_name' =>
                    $product_name,

                ':product_description' =>
                    $product_description,

                ':product_code' =>
                    $product_code,

                ':stock_quantity' =>
                    $stock_quantity,

                ':status' =>
                    $status,

                ':brand_name' =>
                    $brand_name,

                ':color' =>
                    $color,

                ':size' =>
                    $size,

                ':material' =>
                    $material,

                ':product_id' =>
                    $product_id
            ]);


            /* =================================================
               UPDATE / INSERT PRICE
            ================================================= */

            if ($original_price !== '') {


                /* -------------------------------------------------
                   DISCOUNT
                ------------------------------------------------- */

                $discount_value =
                    $discount_percentage === ''
                    ? 0
                    : (float)$discount_percentage;


                /* -------------------------------------------------
                   SELLING PRICE AUTO CALCULATION
                ------------------------------------------------- */

                if ($selling_price === '') {

                    $selling_value =
                        (float)$original_price
                        -
                        (
                            (float)$original_price
                            *
                            $discount_value
                            /
                            100
                        );

                } else {

                    $selling_value =
                        (float)$selling_price;
                }


                /* -------------------------------------------------
                   CHECK EXISTING PRICE
                ------------------------------------------------- */

                $existing_price_sql = "
                    SELECT price_id

                    FROM product_prices

                    WHERE product_id = :product_id

                    ORDER BY price_id DESC

                    LIMIT 1
                ";

                $existing_price_stmt =
                    $conn->prepare(
                        $existing_price_sql
                    );

                $existing_price_stmt->execute([
                    ':product_id' => $product_id
                ]);

                $existing_price =
                    $existing_price_stmt->fetch(
                        PDO::FETCH_ASSOC
                    );


                /* =================================================
                   UPDATE EXISTING PRICE
                ================================================= */

                if ($existing_price) {

                    $price_id =
                        (int)$existing_price['price_id'];


                    $price_update_sql = "
                        UPDATE product_prices

                        SET
                            original_price = :original_price,
                            discount_percentage = :discount_percentage,
                            selling_price = :selling_price,
                            start_date = :start_date,
                            end_date = :end_date

                        WHERE price_id = :price_id
                    ";

                    $price_update_stmt =
                        $conn->prepare(
                            $price_update_sql
                        );

                    $price_update_stmt->execute([

                        ':original_price' =>
                            (float)$original_price,

                        ':discount_percentage' =>
                            $discount_value,

                        ':selling_price' =>
                            $selling_value,

                        ':start_date' =>
                            ($start_date === ''
                                ? null
                                : $start_date),

                        ':end_date' =>
                            ($end_date === ''
                                ? null
                                : $end_date),

                        ':price_id' =>
                            $price_id
                    ]);


                /* =================================================
                   INSERT NEW PRICE
                ================================================= */

                } else {

                    $price_insert_sql = "
                        INSERT INTO product_prices
                        (
                            product_id,
                            original_price,
                            discount_percentage,
                            selling_price,
                            start_date,
                            end_date
                        )

                        VALUES
                        (
                            :product_id,
                            :original_price,
                            :discount_percentage,
                            :selling_price,
                            :start_date,
                            :end_date
                        )
                    ";

                    $price_insert_stmt =
                        $conn->prepare(
                            $price_insert_sql
                        );

                    $price_insert_stmt->execute([

                        ':product_id' =>
                            $product_id,

                        ':original_price' =>
                            (float)$original_price,

                        ':discount_percentage' =>
                            $discount_value,

                        ':selling_price' =>
                            $selling_value,

                        ':start_date' =>
                            ($start_date === ''
                                ? null
                                : $start_date),

                        ':end_date' =>
                            ($end_date === ''
                                ? null
                                : $end_date)
                    ]);
                }
            }


            /* =================================================
               SUCCESS
            ================================================= */

            header("Location: products.php");
            exit;


        } catch (PDOException $e) {

            $error_message =
                "Update Error: " .
                $e->getMessage();
        }
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

$category_stmt =
    $conn->prepare($category_sql);

$category_stmt->execute();

$categories =
    $category_stmt->fetchAll(
        PDO::FETCH_ASSOC
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

$subcategory_stmt =
    $conn->prepare($subcategory_sql);

$subcategory_stmt->execute();

$subcategories =
    $subcategory_stmt->fetchAll(
        PDO::FETCH_ASSOC
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


                    <ul class="nav navbar-nav navbar-right">

                        <li>

                            <a
                                href="javascript:;"
                                class="user-profile dropdown-toggle"
                                data-toggle="dropdown">

                                <img
                                    src="assets/images/img.jpg"
                                    alt="">

                                <?php
                                echo htmlspecialchars(
                                    $admin['name']
                                );
                                ?>

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

                                    <a href="../logout.php">

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

            <?php if ($error_message !== '') { ?>

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
                                <span class="text-danger">*</span>

                            </label>


                            <div class="col-md-6">

                                <select
                                    id="category_id"
                                    class="form-control"
                                    required>

                                    <option value="">

                                        Select Category

                                    </option>


                                    <?php foreach (
                                        $categories as $category
                                    ) { ?>

                                        <option
                                            value="<?php
                                            echo (int)$category['category_id'];
                                            ?>"
                                            <?php
                                            if (
                                                (int)$category['category_id']
                                                ===
                                                (int)$product['category_id']
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

                                    <?php } ?>

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
                                <span class="text-danger">*</span>

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


                                    <?php foreach (
                                        $subcategories as $subcategory
                                    ) { ?>

                                        <option
                                            value="<?php
                                            echo (int)$subcategory['subcategory_id'];
                                            ?>"
                                            data-category="<?php
                                            echo (int)$subcategory['category_id'];
                                            ?>"
                                            <?php
                                            if (
                                                (int)$subcategory['subcategory_id']
                                                ===
                                                (int)$product['subcategory_id']
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

                                    <?php } ?>

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
                                <span class="text-danger">*</span>

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
                                <span class="text-danger">*</span>

                            </label>


                            <div class="col-md-6">

                                <input
                                    type="text"
                                    name="product_code"
                                    class="form-control"
                                    required
                                    value="<?php
                                    echo htmlspecialchars(
                                        $product['product_code'] ?? ''
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
                                            (int)$product['status'] === 1
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
                                            (int)$product['status'] === 0
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

                                    Selling price will be calculated
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

                Fior Flower Shop - Admin Panel

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

$(document).ready(function () {


    /* =========================================================
       CATEGORY -> SUBCATEGORY
    ========================================================= */

    function filterSubcategories() {

        var categoryId =
            $('#category_id').val();

        $('#subcategory_id option').each(function () {

            var option = $(this);

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

        });
    }


    $('#category_id').on(
        'change',
        function () {

            $('#subcategory_id').val('');

            filterSubcategories();

        }
    );


    filterSubcategories();


    /* =========================================================
       AUTO CALCULATE SELLING PRICE
    ========================================================= */

    $('#original_price, #discount_percentage')
        .on('input', function () {

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

            } else {

                $('#selling_price').val('');

            }

        });

});

</script>

</body>

</html>
```
