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
   VARIABLES
========================================================= */

$error_message = "";

$product_name = "";
$product_description = "";
$product_code = "";
$brand_name = "";
$color = "";
$size = "";
$material = "";

$subcategory_id = 0;
$stock_quantity = 0;
$status = 1;


/* =========================================================
   MAKE SURE PRODUCT EXTRA COLUMNS EXIST
========================================================= */

try {

    $conn->exec("
        ALTER TABLE products
        ADD COLUMN IF NOT EXISTS brand_name VARCHAR(100)
    ");

    $conn->exec("
        ALTER TABLE products
        ADD COLUMN IF NOT EXISTS color VARCHAR(100)
    ");

    $conn->exec("
        ALTER TABLE products
        ADD COLUMN IF NOT EXISTS size VARCHAR(100)
    ");

    $conn->exec("
        ALTER TABLE products
        ADD COLUMN IF NOT EXISTS material VARCHAR(100)
    ");
} catch (PDOException $e) {

    $error_message =
        "Database Error: " .
        $e->getMessage();
}


/* =========================================================
   ADD PRODUCT
========================================================= */

if (
    isset($_POST['add_product']) &&
    empty($error_message)
) {

    /* =====================================================
       GET FORM DATA
    ====================================================== */

    $subcategory_id = (int)(
        $_POST['subcategory_id'] ?? 0
    );

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

    $status = (int)(
        $_POST['status'] ?? 1
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


    /* =====================================================
       VALIDATION
    ====================================================== */

    if ($subcategory_id <= 0) {

        $error_message =
            "Please select a subcategory.";
    } elseif ($product_name === '') {

        $error_message =
            "Please enter product name.";
    } elseif ($stock_quantity < 0) {

        $error_message =
            "Stock quantity cannot be negative.";
    } elseif (!in_array($status, [0, 1], true)) {

        $error_message =
            "Invalid product status.";
    } else {

        try {

            /* =============================================
               CHECK SUBCATEGORY
            ============================================== */

            $subcategory_check_sql = "
                SELECT subcategory_id
                FROM product_subcategory
                WHERE subcategory_id = :subcategory_id
                AND status = 1
                LIMIT 1
            ";

            $subcategory_check_stmt =
                $conn->prepare(
                    $subcategory_check_sql
                );

            $subcategory_check_stmt->execute([
                ':subcategory_id' =>
                $subcategory_id
            ]);

            $subcategory_exists =
                $subcategory_check_stmt->fetch(
                    PDO::FETCH_ASSOC
                );


            if (!$subcategory_exists) {

                $error_message =
                    "Selected subcategory is not available.";
            } else {

                /* =========================================
                   CHECK PRODUCT CODE
                ========================================== */

                if ($product_code !== '') {

                    $check_code_sql = "
                        SELECT product_id
                        FROM products
                        WHERE product_code = :product_code
                        LIMIT 1
                    ";

                    $check_code_stmt =
                        $conn->prepare(
                            $check_code_sql
                        );

                    $check_code_stmt->execute([
                        ':product_code' =>
                        $product_code
                    ]);

                    $existing_product =
                        $check_code_stmt->fetch(
                            PDO::FETCH_ASSOC
                        );


                    if ($existing_product) {

                        $error_message =
                            "This product code already exists. Please enter a different product code.";
                    }
                }


                /* =========================================
                   INSERT PRODUCT
                ========================================== */

                if ($error_message === '') {

                    $conn->beginTransaction();


                    $insert_sql = "
                        INSERT INTO products
                        (
                            subcategory_id,
                            product_name,
                            product_description,
                            product_code,
                            stock_quantity,
                            status,
                            brand_name,
                            color,
                            size,
                            material
                        )
                        VALUES
                        (
                            :subcategory_id,
                            :product_name,
                            :product_description,
                            :product_code,
                            :stock_quantity,
                            :status,
                            :brand_name,
                            :color,
                            :size,
                            :material
                        )
                        RETURNING product_id
                    ";


                    $insert_stmt =
                        $conn->prepare(
                            $insert_sql
                        );


                    $insert_stmt->execute([

                        ':subcategory_id' =>
                        $subcategory_id,

                        ':product_name' =>
                        $product_name,

                        ':product_description' =>
                        $product_description !== ''
                            ? $product_description
                            : null,

                        ':product_code' =>
                        $product_code !== ''
                            ? $product_code
                            : null,

                        ':stock_quantity' =>
                        $stock_quantity,

                        ':status' =>
                        $status,

                        ':brand_name' =>
                        $brand_name !== ''
                            ? $brand_name
                            : null,

                        ':color' =>
                        $color !== ''
                            ? $color
                            : null,

                        ':size' =>
                        $size !== ''
                            ? $size
                            : null,

                        ':material' =>
                        $material !== ''
                            ? $material
                            : null
                    ]);


                    /* =====================================
                       GET PRODUCT ID
                    ====================================== */

                    $product_id =
                        $insert_stmt->fetchColumn();


                    if (!$product_id) {

                        throw new Exception(
                            "Product ID could not be generated."
                        );
                    }


                    /* =====================================
                       COMMIT
                    ====================================== */

                    $conn->commit();


                    /* =====================================
                       REDIRECT TO PRICE PAGE
                    ====================================== */

                    header(
                        "Location: add_price.php?product_id=" .
                            (int)$product_id
                    );

                    exit;
                }
            }
        } catch (Exception $e) {

            if ($conn->inTransaction()) {
                $conn->rollBack();
            }

            $error_message =
                "Product Add Failed: " .
                $e->getMessage();
        }
    }
}


/* =========================================================
   GET CATEGORIES
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
   GET SUBCATEGORIES
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

    <title>Add Product</title>


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


    <link
        href="assets/css/custom-popup.css"
        rel="stylesheet">


    <style>
        .product-card {
            background: #ffffff;
            border-radius: 5px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
            padding: 25px;
        }

        .page-title {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .page-subtitle {
            color: #777;
            margin-bottom: 0;
        }

        .form-label {
            font-weight: 600;
            margin-bottom: 7px;
        }

        .required {
            color: red;
        }

        .section-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 20px;
        }

        .form-control,
        .form-select {
            min-height: 40px;
        }

        .description-box {
            resize: vertical;
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

                                        <span class="fa fa-angle-down"></span>

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

                                            <a href="logout.php">

                                                <i class="fa fa-sign-out pull-right"></i>

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

                <div class="container-fluid">


                    <!-- PAGE HEADER -->

                    <div
                        class="row"
                        style="
                        margin-top:20px;
                        margin-bottom:20px;
                    ">

                        <div class="col-md-8">

                            <h3 class="page-title">

                                Add Product

                            </h3>

                            <p class="page-subtitle">

                                Create a new product

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


                    <!-- ERROR MESSAGE -->

                    <?php if (!empty($error_message)) { ?>

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
                     PRODUCT FORM CARD
                ================================================== -->

                    <div class="product-card">


                        <div class="section-title">

                            <i class="fa fa-cube"></i>

                            Product Information

                        </div>


                        <form
                            method="POST"
                            action=""
                            autocomplete="off">


                            <div class="row">


                                <!-- =================================================
                                 CATEGORY
                            ================================================== -->

                                <div class="col-md-6">

                                    <div class="form-group">

                                        <label class="form-label">

                                            Category

                                            <span class="required">
                                                *
                                            </span>

                                        </label>


                                        <select
                                            id="category_id"
                                            class="form-control"
                                            onchange="loadSubcategories(this.value)"
                                            required>

                                            <option value="">
                                                Select Category
                                            </option>


                                            <?php foreach (
                                                $categories
                                                as $category
                                            ) { ?>

                                                <option
                                                    value="<?php
                                                            echo (int)
                                                            $category['category_id'];
                                                            ?>">

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

                                <div class="col-md-6">

                                    <div class="form-group">

                                        <label class="form-label">

                                            Subcategory

                                            <span class="required">
                                                *
                                            </span>

                                        </label>


                                        <select
                                            name="subcategory_id"
                                            id="subcategory_id"
                                            class="form-control"
                                            required>

                                            <option value="">
                                                Select Subcategory
                                            </option>


                                            <?php foreach (
                                                $subcategories
                                                as $subcategory
                                            ) { ?>

                                                <option
                                                    value="<?php
                                                            echo (int)
                                                            $subcategory['subcategory_id'];
                                                            ?>"
                                                    data-category="<?php
                                                                    echo (int)
                                                                    $subcategory['category_id'];
                                                                    ?>">

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

                                <div class="col-md-6">

                                    <div class="form-group">

                                        <label class="form-label">

                                            Product Name

                                            <span class="required">
                                                *
                                            </span>

                                        </label>


                                        <input
                                            type="text"
                                            name="product_name"
                                            class="form-control"
                                            placeholder="Enter product name"
                                            value="<?php
                                                    echo htmlspecialchars(
                                                        $product_name
                                                    );
                                                    ?>"
                                            required>

                                    </div>

                                </div>


                                <!-- =================================================
                                 PRODUCT CODE
                            ================================================== -->

                                <div class="col-md-6">

                                    <div class="form-group">

                                        <label class="form-label">

                                            Product Code

                                        </label>


                                        <input
                                            type="text"
                                            name="product_code"
                                            class="form-control"
                                            placeholder="Enter product code"
                                            value="<?php
                                                    echo htmlspecialchars(
                                                        $product_code
                                                    );
                                                    ?>">

                                    </div>

                                </div>


                                <!-- =================================================
                                 BRAND NAME
                            ================================================== -->

                                <div class="col-md-6">

                                    <div class="form-group">

                                        <label class="form-label">

                                            Brand Name

                                        </label>


                                        <input
                                            type="text"
                                            name="brand_name"
                                            class="form-control"
                                            placeholder="Enter brand name"
                                            value="<?php
                                                    echo htmlspecialchars(
                                                        $brand_name
                                                    );
                                                    ?>">

                                    </div>

                                </div>


                                <!-- =================================================
                                 COLOR
                            ================================================== -->

                                <div class="col-md-6">

                                    <div class="form-group">

                                        <label class="form-label">

                                            Color

                                        </label>


                                        <input
                                            type="text"
                                            name="color"
                                            class="form-control"
                                            placeholder="Enter color"
                                            value="<?php
                                                    echo htmlspecialchars(
                                                        $color
                                                    );
                                                    ?>">

                                    </div>

                                </div>


                                <!-- =================================================
                                 SIZE
                            ================================================== -->

                                <div class="col-md-6">

                                    <div class="form-group">

                                        <label class="form-label">

                                            Size

                                        </label>


                                        <input
                                            type="text"
                                            name="size"
                                            class="form-control"
                                            placeholder="Enter size"
                                            value="<?php
                                                    echo htmlspecialchars(
                                                        $size
                                                    );
                                                    ?>">

                                    </div>

                                </div>


                                <!-- =================================================
                                 MATERIAL
                            ================================================== -->

                                <div class="col-md-6">

                                    <div class="form-group">

                                        <label class="form-label">

                                            Material

                                        </label>


                                        <input
                                            type="text"
                                            name="material"
                                            class="form-control"
                                            placeholder="Enter material"
                                            value="<?php
                                                    echo htmlspecialchars(
                                                        $material
                                                    );
                                                    ?>">

                                    </div>

                                </div>


                                <!-- =================================================
                                 STOCK
                            ================================================== -->

                                <div class="col-md-6">

                                    <div class="form-group">

                                        <label class="form-label">

                                            Stock Quantity

                                        </label>


                                        <input
                                            type="number"
                                            name="stock_quantity"
                                            class="form-control"
                                            min="0"
                                            value="<?php
                                                    echo (int)
                                                    $stock_quantity;
                                                    ?>"
                                            placeholder="Enter stock quantity">

                                    </div>

                                </div>


                                <!-- =================================================
                                 STATUS
                            ================================================== -->

                                <div class="col-md-6">

                                    <div class="form-group">

                                        <label class="form-label">

                                            Status

                                        </label>


                                        <select
                                            name="status"
                                            class="form-control">

                                            <option
                                                value="1"
                                                <?php
                                                echo $status === 1
                                                    ? 'selected'
                                                    : '';
                                                ?>>

                                                Active

                                            </option>

                                            <option
                                                value="0"
                                                <?php
                                                echo $status === 0
                                                    ? 'selected'
                                                    : '';
                                                ?>>

                                                Inactive

                                            </option>

                                        </select>

                                    </div>

                                </div>


                                <!-- =================================================
                                 DESCRIPTION
                            ================================================== -->

                                <div class="col-md-12">

                                    <div class="form-group">

                                        <label class="form-label">

                                            Product Description

                                        </label>


                                        <textarea
                                            name="product_description"
                                            class="form-control description-box"
                                            rows="5"
                                            placeholder="Enter product description"><?php
                                                                                    echo htmlspecialchars(
                                                                                        $product_description
                                                                                    );
                                                                                    ?></textarea>

                                    </div>

                                </div>


                            </div>


                            <!-- =================================================
                             BUTTONS
                        ================================================== -->

                            <div
                                style="
                                border-top:1px solid #eee;
                                margin-top:20px;
                                padding-top:20px;
                            ">


                                <a
                                    href="products.php"
                                    class="btn btn-default">

                                    <i class="fa fa-times"></i>

                                    Cancel

                                </a>


                                <button
                                    type="submit"
                                    name="add_product"
                                    value="1"
                                    class="btn btn-primary">

                                    <i class="fa fa-save"></i>

                                    Save Product

                                </button>


                            </div>


                        </form>

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
        src="assets/vendors/iCheck/icheck.min.js">
    </script>


    <script
        src="assets/js/custom.min.js">
    </script>


    <script
        src="assets/js/custom-popup.js">
    </script>


    <script>
        function loadSubcategories(categoryId) {

            const subcategory =
                document.getElementById("subcategory_id");

            const options =
                subcategory.querySelectorAll("option");


            subcategory.value = "";


            options.forEach(function(option) {

                if (option.value === "") {

                    option.style.display = "block";

                    return;
                }


                if (
                    option.getAttribute("data-category") ==
                    categoryId
                ) {

                    option.style.display = "block";

                } else {

                    option.style.display = "none";

                }

            });

        }
    </script>


</body>

</html>