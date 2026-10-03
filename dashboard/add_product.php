<?php

include "../config/database.php";

/* =========================================================
   ADD PRODUCT
========================================================= */

if (isset($_POST['add_product'])) {

    $subcategory_id = intval($_POST['subcategory_id'] ?? 0);

    $product_name = mysqli_real_escape_string(
        $conn,
        $_POST['product_name'] ?? ''
    );

    $product_description = mysqli_real_escape_string(
        $conn,
        $_POST['product_description'] ?? ''
    );

    $product_code = mysqli_real_escape_string(
        $conn,
        $_POST['product_code'] ?? ''
    );

    $stock_quantity = intval(
        $_POST['stock_quantity'] ?? 0
    );

    $status = intval(
        $_POST['status'] ?? 1
    );

    $brand_name = mysqli_real_escape_string(
        $conn,
        $_POST['brand_name'] ?? ''
    );

    $color = mysqli_real_escape_string(
        $conn,
        $_POST['color'] ?? ''
    );

    $size = mysqli_real_escape_string(
        $conn,
        $_POST['size'] ?? ''
    );

    $material = mysqli_real_escape_string(
        $conn,
        $_POST['material'] ?? ''
    );


    /* =========================================================
       VALIDATION
    ========================================================= */

    if ($subcategory_id <= 0) {

        $error_message = "Please select a subcategory.";
    } elseif (empty($product_name)) {

        $error_message = "Please enter product name.";
    } else {

        /* =====================================================
           INSERT PRODUCT
        ====================================================== */

        $check_code = mysqli_query($conn, "SELECT product_id FROM products WHERE product_code = '$product_code'");

        if (mysqli_num_rows($check_code) > 0) {
            echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            init_PNotify(
                'This product code already exists. Please enter a different product code.',
                'error',
                'Duplicate Product Code',
                'fa fa-times'
            );
        });
    </script>";
        } else {

            $sql = "INSERT INTO products
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
        '$subcategory_id',
        '$product_name',
        '$product_description',
        '$product_code',
        '$stock_quantity',
        '$status',
        '$brand_name',
        '$color',
        '$size',
        '$material'
    )";

            if (mysqli_query($conn, $sql)) {

                $product_id = mysqli_insert_id($conn);

                header("Location: add_price.php?product_id=" . $product_id);
                exit;
            } else {

                echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                init_PNotify(
                    'Product could not be added.',
                    'error',
                    'Add Product Failed',
                    'fa fa-times'
                );
            });
        </script>";
            }
        }


        if (mysqli_query($conn, $sql)) {

            /* ================================================
               GET INSERTED PRODUCT ID
            ================================================= */

            $product_id = mysqli_insert_id($conn);


            /* ================================================
               REDIRECT TO PRICE PAGE
            ================================================= */

            header(
                "Location: add_price.php?product_id=" . $product_id
            );

            exit;
        } else {

            $error_message =
                "Product Add Failed: " .
                mysqli_error($conn);
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

$category_result = mysqli_query(
    $conn,
    $category_sql
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

$subcategory_result = mysqli_query(
    $conn,
    $subcategory_sql
);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">

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


    <!-- PNotify -->

    <!-- <link
        href="assets/vendors/pnotify/dist/pnotify.css"
        rel="stylesheet">

    <link
        href="assets/vendors/pnotify/dist/pnotify.buttons.css"
        rel="stylesheet">

    <link
        href="assets/vendors/pnotify/dist/pnotify.nonblock.css"
        rel="stylesheet"> -->


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

            <div class="right_col" role="main">


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
                                        data-toggle="dropdown"
                                        aria-expanded="false">

                                        <img
                                            src="assets/images/img.jpg"
                                            alt="">

                                        John Doe

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

                                                <span class="badge bg-red pull-right">
                                                    50%
                                                </span>

                                                <span>
                                                    Settings
                                                </span>

                                            </a>

                                        </li>

                                        <li>

                                            <a href="javascript:;">
                                                Help
                                            </a>

                                        </li>

                                        <li>

                                            <a href="login.php">

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
                        style="margin-top:20px; margin-bottom:20px;">

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
                                                        value="<?php echo $category['category_id']; ?>">

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
                                                        value="<?php echo $subcategory['subcategory_id']; ?>"
                                                        data-category="<?php echo $subcategory['category_id']; ?>">

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
                                            placeholder="Enter product code">

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
                                            placeholder="Enter brand name">

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
                                            placeholder="Enter color">

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
                                            placeholder="Enter size">

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
                                            placeholder="Enter material">

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
                                            value="0"
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

                                            <option value="1">
                                                Active
                                            </option>

                                            <option value="0">
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
                                            placeholder="Enter product description"></textarea>

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

    <!-- 
    <script
        src="assets/vendors/pnotify/dist/pnotify.js">
    </script>


    <script
        src="assets/vendors/pnotify/dist/pnotify.buttons.js">
    </script>


    <script
        src="assets/vendors/pnotify/dist/pnotify.nonblock.js">
    </script> -->


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