<?php
include "../config/database.php";

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
    $product_id = intval($_GET['product_id']);
}

if ($product_id <= 0) {
    die("Invalid Product ID.");
}

/* =========================================================
   GET PRODUCT DETAILS
========================================================= */

$product_sql = "SELECT product_id, product_name
                FROM products
                WHERE product_id = '$product_id'";

$product_result = mysqli_query($conn, $product_sql);

if (!$product_result || mysqli_num_rows($product_result) == 0) {
    die("Product not found.");
}

$product = mysqli_fetch_assoc($product_result);

/* =========================================================
   ADD PRODUCT PRICE
========================================================= */

if (isset($_POST['add_price'])) {

    $original_price = isset($_POST['original_price'])
        ? floatval($_POST['original_price'])
        : 0;

    $discount_percentage = isset($_POST['discount_percentage'])
        ? floatval($_POST['discount_percentage'])
        : 0;

    $selling_price = isset($_POST['selling_price'])
        ? floatval($_POST['selling_price'])
        : 0;

    $start_date = isset($_POST['start_date'])
        ? mysqli_real_escape_string($conn, $_POST['start_date'])
        : "";

    $end_date = isset($_POST['end_date'])
        ? mysqli_real_escape_string($conn, $_POST['end_date'])
        : "";

    $status = isset($_POST['status'])
        ? intval($_POST['status'])
        : 1;

    /* =====================================================
       VALIDATION
    ====================================================== */

    if ($original_price <= 0) {

        $popup_message = "Please enter a valid original price.";
        $popup_type = "error";
        $popup_title = "Invalid Price";
        $popup_icon = "fa fa-times";
    } elseif ($discount_percentage < 0 || $discount_percentage > 100) {

        $popup_message = "Discount must be between 0 and 100.";
        $popup_type = "error";
        $popup_title = "Invalid Discount";
        $popup_icon = "fa fa-times";
    } elseif ($selling_price < 0) {

        $popup_message = "Please enter a valid selling price.";
        $popup_type = "error";
        $popup_title = "Invalid Selling Price";
        $popup_icon = "fa fa-times";
    } else {

        /* =====================================================
           INSERT PRICE
        ====================================================== */

        $sql = "INSERT INTO product_prices
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
            '$product_id',
            '$original_price',
            '$discount_percentage',
            '$selling_price',
            '$start_date',
            '$end_date',
            '$status'
        )";

        if (mysqli_query($conn, $sql)) {

            $popup_message = "Product price added successfully.";
            $popup_type = "success";
            $popup_title = "Price Added";
            $popup_icon = "fa fa-check";
            $redirect_page = "products.php";
        } else {

            $popup_message = mysqli_error($conn);
            $popup_type = "error";
            $popup_title = "Price Add Failed";
            $popup_icon = "fa fa-times";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">

    <meta charset="utf-8">

    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    <meta name="viewport"
        content="width=device-width, initial-scale=1">

    <title>Add Product Price</title>

    <!-- Bootstrap -->
    <link href="assets/vendors/bootstrap/dist/css/bootstrap.min.css"
        rel="stylesheet">

    <!-- Font Awesome -->
    <link href="assets/vendors/font-awesome/css/font-awesome.min.css"
        rel="stylesheet">

    <!-- NProgress -->
    <link href="assets/vendors/nprogress/nprogress.css"
        rel="stylesheet">

    <!-- iCheck -->
    <link href="assets/vendors/iCheck/skins/flat/green.css"
        rel="stylesheet">

    <!-- PNotify
    <link href="assets/vendors/pnotify/dist/pnotify.css"
        rel="stylesheet">

    <link href="assets/vendors/pnotify/dist/pnotify.buttons.css"
        rel="stylesheet">

    <link href="assets/vendors/pnotify/dist/pnotify.nonblock.css" -->
    <!-- rel="stylesheet"> -->

    <!-- Custom Theme -->
    <link href="assets/css/custom.min.css"
        rel="stylesheet">

    <!-- Custom Popup -->
    <link href="assets/css/custom-popup.css"
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

                                    <a href="javascript:;"
                                        class="user-profile dropdown-toggle"
                                        data-toggle="dropdown"
                                        aria-expanded="false">

                                        <img src="assets/images/img.jpg"
                                            alt="">

                                        John Doe

                                        <span class="fa fa-angle-down"></span>

                                    </a>


                                    <ul class="dropdown-menu dropdown-usermenu pull-right">

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

                <div class="container-fluid"
                    style="padding:25px;">


                    <!-- PAGE HEADER -->

                    <div class="row">

                        <div class="col-md-12">

                            <div class="page-title">

                                <h3>
                                    Add Product Price
                                </h3>

                                <p class="text-muted">
                                    Add pricing details for your product
                                </p>

                            </div>

                        </div>

                    </div>


                    <!-- =================================================
                     FORM CARD
                ================================================== -->

                    <div class="row">

                        <div class="col-md-12">

                            <div class="x_panel">

                                <div class="x_title">

                                    <h2>
                                        Product Price Information
                                    </h2>

                                    <div class="clearfix"></div>

                                </div>


                                <div class="x_content">


                                    <form method="POST"
                                        action="add_price.php?product_id=<?php echo $product_id; ?>">


                                        <!-- PRODUCT -->

                                        <div class="form-group">

                                            <label>
                                                Product
                                            </label>

                                            <input type="text"
                                                class="form-control"
                                                value="<?php echo htmlspecialchars($product['product_name']); ?>"
                                                readonly>

                                        </div>


                                        <!-- PRODUCT ID -->

                                        <div class="form-group">

                                            <label>
                                                Product ID
                                            </label>

                                            <input type="text"
                                                class="form-control"
                                                value="<?php echo $product_id; ?>"
                                                readonly>

                                        </div>


                                        <div class="row">


                                            <!-- ORIGINAL PRICE -->

                                            <div class="col-md-6">

                                                <div class="form-group">

                                                    <label>
                                                        Original Price
                                                        <span class="text-danger">*</span>
                                                    </label>

                                                    <input type="number"
                                                        name="original_price"
                                                        id="original_price"
                                                        class="form-control"
                                                        placeholder="Enter original price"
                                                        step="0.01"
                                                        min="0"
                                                        required>

                                                </div>

                                            </div>


                                            <!-- DISCOUNT -->

                                            <div class="col-md-6">

                                                <div class="form-group">

                                                    <label>
                                                        Discount Percentage
                                                    </label>

                                                    <input type="number"
                                                        name="discount_percentage"
                                                        id="discount_percentage"
                                                        class="form-control"
                                                        placeholder="Enter discount %"
                                                        step="0.01"
                                                        min="0"
                                                        max="100"
                                                        value="0">

                                                </div>

                                            </div>


                                        </div>


                                        <div class="row">


                                            <!-- SELLING PRICE -->

                                            <div class="col-md-6">

                                                <div class="form-group">

                                                    <label>
                                                        Selling Price
                                                        <span class="text-danger">*</span>
                                                    </label>

                                                    <input type="number"
                                                        name="selling_price"
                                                        id="selling_price"
                                                        class="form-control"
                                                        placeholder="Enter selling price"
                                                        step="0.01"
                                                        min="0"
                                                        required>

                                                </div>

                                            </div>


                                            <!-- STATUS -->

                                            <div class="col-md-6">

                                                <div class="form-group">

                                                    <label>
                                                        Status
                                                    </label>

                                                    <select name="status"
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


                                        </div>


                                        <div class="row">


                                            <!-- START DATE -->

                                            <div class="col-md-6">

                                                <div class="form-group">

                                                    <label>
                                                        Start Date
                                                    </label>

                                                    <input type="date"
                                                        name="start_date"
                                                        class="form-control">

                                                </div>

                                            </div>


                                            <!-- END DATE -->

                                            <div class="col-md-6">

                                                <div class="form-group">

                                                    <label>
                                                        End Date
                                                    </label>

                                                    <input type="date"
                                                        name="end_date"
                                                        class="form-control">

                                                </div>

                                            </div>


                                        </div>


                                        <!-- BUTTONS -->

                                        <div class="ln_solid"></div>


                                        <div class="form-group">

                                            <a href="products.php"
                                                class="btn btn-default">

                                                <i class="fa fa-times"></i>

                                                Cancel

                                            </a>


                                            <button type="submit"
                                                name="add_price"
                                                value="1"
                                                class="btn btn-primary">

                                                <i class="fa fa-save"></i>

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

                        Gentelella - Bootstrap Admin Template

                    </div>

                    <div class="clearfix"></div>

                </footer>

            </div>

        </div>

    </div>


    <!-- =========================================================
     PNOTIFY
========================================================= -->

    <!-- <div id="custom_notifications"
        class="custom-notifications dsp_none">

        <ul class="list-unstyled notifications clearfix"
            data-tabbed_notifications="notif-group">

        </ul>

        <div class="clearfix"></div>

        <div id="notif-group"
            class="tabbed_notifications">

        </div>

    </div> -->


    <!-- =========================================================
     JAVASCRIPT
========================================================= -->

    <script src="assets/vendors/jquery/dist/jquery.min.js"></script>

    <script src="assets/vendors/bootstrap/dist/js/bootstrap.min.js"></script>

    <script src="assets/vendors/fastclick/lib/fastclick.js"></script>

    <script src="assets/vendors/nprogress/nprogress.js"></script>

    <script src="assets/vendors/iCheck/icheck.min.js"></script>

    <!-- <script src="assets/vendors/pnotify/dist/pnotify.js"></script>

    <script src="assets/vendors/pnotify/dist/pnotify.buttons.js"></script>

    <script src="assets/vendors/pnotify/dist/pnotify.nonblock.js"></script> -->

    <script src="assets/js/custom.min.js"></script>

    <script src="assets/js/custom-popup.js"></script>


    <?php if ($popup_message != "") { ?>

        <script>
            document.addEventListener("DOMContentLoaded", function() {

                init_PNotify(
                    <?php echo json_encode($popup_message); ?>,
                    <?php echo json_encode($popup_type); ?>,
                    <?php echo json_encode($popup_title); ?>,
                    <?php echo json_encode($popup_icon); ?>
                );

                <?php if ($redirect_page != "") { ?>

                    setTimeout(function() {

                        window.location.href =
                            <?php echo json_encode($redirect_page); ?>;

                    });

                <?php } ?>

            });
        </script>

    <?php } ?>


</body>

</html>