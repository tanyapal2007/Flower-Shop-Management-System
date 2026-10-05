<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../config/database.php";


/* =========================================================
   ADMIN LOGIN CHECK
========================================================= */

$login_user_id = $_SESSION['user_id'] ?? 0;

if (!$login_user_id) {
    header("Location: ../login.php");
    exit;
}


/* =========================================================
   CHECK ADMIN
========================================================= */

$admin_stmt = $conn->prepare("
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
");

$admin_stmt->execute([
    ':user_id' => $login_user_id
]);

$admin = $admin_stmt->fetch(PDO::FETCH_ASSOC);


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

$product_id = 0;
$product = null;

$popup_message = "";
$popup_type = "";
$popup_title = "";
$popup_icon = "";
$redirect_page = "";


/* =========================================================
   GET PRODUCT ID
========================================================= */

if (isset($_GET['product_id'])) {

    $product_id = (int)$_GET['product_id'];
}


if ($product_id <= 0) {

    die("Invalid Product ID.");
}


/* =========================================================
   GET PRODUCT DETAILS
========================================================= */

try {

    $product_sql = "
        SELECT
            product_id,
            product_name
        FROM products
        WHERE product_id = :product_id
        LIMIT 1
    ";

    $product_stmt = $conn->prepare($product_sql);

    $product_stmt->execute([
        ':product_id' => $product_id
    ]);

    $product = $product_stmt->fetch(PDO::FETCH_ASSOC);


    if (!$product) {

        die("Product not found.");
    }
} catch (PDOException $e) {

    die("Database Error: " .
        htmlspecialchars($e->getMessage()));
}


/* =========================================================
   ADD PRODUCT PRICE
========================================================= */

if (isset($_POST['add_price'])) {


    /* =====================================================
       GET FORM VALUES
    ====================================================== */

    $original_price = isset($_POST['original_price'])
        ? (float)$_POST['original_price']
        : 0;


    $discount_percentage = isset(
        $_POST['discount_percentage']
    )
        ? (float)$_POST['discount_percentage']
        : 0;


    $selling_price = isset($_POST['selling_price'])
        ? (float)$_POST['selling_price']
        : 0;


    $start_date = trim(
        $_POST['start_date'] ?? ''
    );


    $end_date = trim(
        $_POST['end_date'] ?? ''
    );


    $status = isset($_POST['status'])
        ? (int)$_POST['status']
        : 1;


    /* =====================================================
       VALIDATION
    ====================================================== */

    if ($original_price <= 0) {

        $popup_message =
            "Please enter a valid original price.";

        $popup_type =
            "error";

        $popup_title =
            "Invalid Price";

        $popup_icon =
            "fa fa-times";
    } elseif (
        $discount_percentage < 0 ||
        $discount_percentage > 100
    ) {

        $popup_message =
            "Discount must be between 0 and 100.";

        $popup_type =
            "error";

        $popup_title =
            "Invalid Discount";

        $popup_icon =
            "fa fa-times";
    } elseif ($selling_price <= 0) {

        $popup_message =
            "Please enter a valid selling price.";

        $popup_type =
            "error";

        $popup_title =
            "Invalid Selling Price";

        $popup_icon =
            "fa fa-times";
    } elseif ($selling_price > $original_price) {

        $popup_message =
            "Selling price cannot be greater than original price.";

        $popup_type =
            "error";

        $popup_title =
            "Invalid Selling Price";

        $popup_icon =
            "fa fa-times";
    } elseif (
        $start_date !== '' &&
        $end_date !== '' &&
        $end_date < $start_date
    ) {

        $popup_message =
            "End date cannot be earlier than start date.";

        $popup_type =
            "error";

        $popup_title =
            "Invalid Date";

        $popup_icon =
            "fa fa-times";
    } else {


        /* =================================================
           DATABASE INSERT
        ================================================== */

        try {


            /* =============================================
               CHECK PRODUCT EXISTS
            ============================================== */

            $check_product = $conn->prepare("
                SELECT
                    product_id
                FROM products
                WHERE product_id = :product_id
                LIMIT 1
            ");


            $check_product->execute([
                ':product_id' => $product_id
            ]);


            $product_exists =
                $check_product->fetch(PDO::FETCH_ASSOC);


            if (!$product_exists) {

                throw new Exception(
                    "Product does not exist."
                );
            }


            /* =============================================
               INSERT PRODUCT PRICE
            ============================================== */

            $sql = "
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
                    :product_id,
                    :original_price,
                    :discount_percentage,
                    :selling_price,
                    :start_date,
                    :end_date,
                    :status
                )
                RETURNING price_id
            ";


            $stmt = $conn->prepare($sql);


            $stmt->execute([
                ':product_id' => $product_id,

                ':original_price' =>
                $original_price,

                ':discount_percentage' =>
                $discount_percentage,

                ':selling_price' =>
                $selling_price,

                ':start_date' => (
                    $start_date !== ''
                    ? $start_date
                    : null
                ),

                ':end_date' => (
                    $end_date !== ''
                    ? $end_date
                    : null
                ),

                ':status' =>
                $status
            ]);


            /* =============================================
               GET INSERTED PRICE ID
            ============================================== */

            $price_id =
                $stmt->fetchColumn();


            /* =============================================
               SUCCESS
            ============================================== */

            if ($price_id) {

                $popup_message =
                    "Product price added successfully.";

                $popup_type =
                    "success";

                $popup_title =
                    "Price Added";

                $popup_icon =
                    "fa fa-check";

                $redirect_page =
                    "products.php";
            } else {

                throw new Exception(
                    "Price was not inserted."
                );
            }
        } catch (Exception $e) {


            /* =============================================
               ERROR
            ============================================== */

            $popup_message =
                "Price Add Failed: " .
                $e->getMessage();

            $popup_type =
                "error";

            $popup_title =
                "Price Add Failed";

            $popup_icon =
                "fa fa-times";
        }
    }
}

?>


<!DOCTYPE html>

<html lang="en">


<head>


    <meta
        http-equiv="Content-Type"
        content="text/html; charset=UTF-8">


    <meta charset="utf-8">


    <meta
        http-equiv="X-UA-Compatible"
        content="IE=edge">


    <meta
        name="viewport"
        content="width=device-width, initial-scale=1">


    <title>
        Add Product Price
    </title>


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


    <!-- Custom Popup -->

    <link
        href="assets/css/custom-popup.css"
        rel="stylesheet">


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


                            <ul
                                class="nav navbar-nav navbar-right">


                                <li>


                                    <a
                                        href="javascript:;"
                                        class="user-profile dropdown-toggle"
                                        data-toggle="dropdown"
                                        aria-expanded="false">


                                        <img
                                            src="assets/images/img.jpg"
                                            alt="Profile">


                                        <?php

                                        echo htmlspecialchars(
                                            $admin['name'] ?? 'Admin'
                                        );

                                        ?>


                                        <span
                                            class="fa fa-angle-down"></span>


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
                                                    class="fa fa-sign-out pull-right"></i>

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


                        <div class="col-md-12">


                            <div class="page-title">


                                <h3>
                                    Add Product Price
                                </h3>


                                <p class="text-muted">

                                    Add pricing details
                                    for your product

                                </p>


                            </div>


                        </div>


                    </div>


                    <!-- =================================================
                     FORM
                ================================================== -->

                    <div class="row">


                        <div class="col-md-12">


                            <div class="x_panel">


                                <div class="x_title">


                                    <h2>
                                        Product Price Information
                                    </h2>


                                    <div
                                        class="clearfix"></div>


                                </div>


                                <div class="x_content">


                                    <form
                                        method="POST"
                                        action="add_price.php?product_id=<?php echo (int)$product_id; ?>">


                                        <!-- PRODUCT -->

                                        <div
                                            class="form-group">


                                            <label>
                                                Product
                                            </label>


                                            <input
                                                type="text"
                                                class="form-control"
                                                value="<?php echo htmlspecialchars($product['product_name']); ?>"
                                                readonly>


                                        </div>


                                        <!-- PRODUCT ID -->

                                        <div
                                            class="form-group">


                                            <label>
                                                Product ID
                                            </label>


                                            <input
                                                type="text"
                                                class="form-control"
                                                value="<?php echo (int)$product_id; ?>"
                                                readonly>


                                        </div>


                                        <div class="row">


                                            <!-- ORIGINAL PRICE -->

                                            <div class="col-md-6">


                                                <div
                                                    class="form-group">


                                                    <label>

                                                        Original Price

                                                        <span
                                                            class="text-danger">
                                                            *
                                                        </span>

                                                    </label>


                                                    <input
                                                        type="number"
                                                        name="original_price"
                                                        id="original_price"
                                                        class="form-control"
                                                        placeholder="Enter original price"
                                                        step="0.01"
                                                        min="0.01"
                                                        value="<?php echo htmlspecialchars($_POST['original_price'] ?? ''); ?>"
                                                        required>


                                                </div>


                                            </div>


                                            <!-- DISCOUNT -->

                                            <div class="col-md-6">


                                                <div
                                                    class="form-group">


                                                    <label>
                                                        Discount Percentage
                                                    </label>


                                                    <input
                                                        type="number"
                                                        name="discount_percentage"
                                                        id="discount_percentage"
                                                        class="form-control"
                                                        placeholder="Enter discount %"
                                                        step="0.01"
                                                        min="0"
                                                        max="100"
                                                        value="<?php echo htmlspecialchars($_POST['discount_percentage'] ?? '0'); ?>">


                                                </div>


                                            </div>


                                        </div>


                                        <div class="row">


                                            <!-- SELLING PRICE -->

                                            <div class="col-md-6">


                                                <div
                                                    class="form-group">


                                                    <label>

                                                        Selling Price

                                                        <span
                                                            class="text-danger">
                                                            *
                                                        </span>

                                                    </label>


                                                    <input
                                                        type="number"
                                                        name="selling_price"
                                                        id="selling_price"
                                                        class="form-control"
                                                        placeholder="Enter selling price"
                                                        step="0.01"
                                                        min="0.01"
                                                        value="<?php echo htmlspecialchars($_POST['selling_price'] ?? ''); ?>"
                                                        required>


                                                </div>


                                            </div>


                                            <!-- STATUS -->

                                            <div class="col-md-6">


                                                <div
                                                    class="form-group">


                                                    <label>
                                                        Status
                                                    </label>


                                                    <select
                                                        name="status"
                                                        class="form-control">


                                                        <option
                                                            value="1"
                                                            <?php

                                                            echo (
                                                                ($_POST['status'] ?? '1') == '1'
                                                                ? 'selected'
                                                                : ''
                                                            );

                                                            ?>>
                                                            Active
                                                        </option>


                                                        <option
                                                            value="0"
                                                            <?php

                                                            echo (
                                                                ($_POST['status'] ?? '') == '0'
                                                                ? 'selected'
                                                                : ''
                                                            );

                                                            ?>>
                                                            Inactive
                                                        </option>


                                                    </select>


                                                </div>


                                            </div>


                                        </div>


                                        <div class="row">


                                            <!-- START DATE -->

                                            <div class="col-md-6">


                                                <div
                                                    class="form-group">


                                                    <label>
                                                        Start Date
                                                    </label>


                                                    <input
                                                        type="date"
                                                        name="start_date"
                                                        class="form-control"
                                                        value="<?php echo htmlspecialchars($_POST['start_date'] ?? ''); ?>">


                                                </div>


                                            </div>


                                            <!-- END DATE -->

                                            <div class="col-md-6">


                                                <div
                                                    class="form-group">


                                                    <label>
                                                        End Date
                                                    </label>


                                                    <input
                                                        type="date"
                                                        name="end_date"
                                                        class="form-control"
                                                        value="<?php echo htmlspecialchars($_POST['end_date'] ?? ''); ?>">


                                                </div>


                                            </div>


                                        </div>


                                        <!-- BUTTONS -->

                                        <div class="ln_solid"></div>


                                        <div
                                            class="form-group">


                                            <a
                                                href="products.php"
                                                class="btn btn-default">

                                                <i
                                                    class="fa fa-times"></i>

                                                Cancel

                                            </a>


                                            <button
                                                type="submit"
                                                name="add_price"
                                                value="1"
                                                class="btn btn-primary">

                                                <i
                                                    class="fa fa-save"></i>

                                                Add Price

                                            </button>


                                        </div>


                                    </form>


                                </div>


                            </div>


                        </div>


                    </div>


                </div>


                <!-- =================================================
                 FOOTER
            ================================================== -->

                <footer>


                    <div class="pull-right">

                        Fior Flower Shop -
                        Admin Panel

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
        src="assets/vendors/jquery/dist/jquery.min.js"></script>


    <script
        src="assets/vendors/bootstrap/dist/js/bootstrap.min.js"></script>


    <script
        src="assets/vendors/fastclick/lib/fastclick.js"></script>


    <script
        src="assets/vendors/nprogress/nprogress.js"></script>


    <script
        src="assets/vendors/iCheck/icheck.min.js"></script>


    <script
        src="assets/js/custom.min.js"></script>


    <script
        src="assets/js/custom-popup.js"></script>


    <?php if ($popup_message !== "") { ?>


        <script>
            document.addEventListener(
                "DOMContentLoaded",
                function() {


                    if (
                        typeof init_PNotify === "function"
                    ) {


                        init_PNotify(

                            <?php
                            echo json_encode(
                                $popup_message
                            );
                            ?>,

                            <?php
                            echo json_encode(
                                $popup_type
                            );
                            ?>,

                            <?php
                            echo json_encode(
                                $popup_title
                            );
                            ?>,

                            <?php
                            echo json_encode(
                                $popup_icon
                            );
                            ?>

                        );


                    } else {


                        alert(
                            <?php
                            echo json_encode(
                                $popup_message
                            );
                            ?>
                        );

                    }


                    <?php if ($redirect_page !== "") { ?>


                        setTimeout(
                            function() {

                                window.location.href =
                                    <?php
                                    echo json_encode(
                                        $redirect_page
                                    );
                                    ?>;

                            },
                            1500
                        );


                    <?php } ?>


                }
            );
        </script>


    <?php } ?>


</body>

</html>