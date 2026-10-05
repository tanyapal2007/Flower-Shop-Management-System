<?php

/* =========================================================
   START SESSION
========================================================= */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/* =========================================================
   DATABASE CONNECTION
========================================================= */

require_once "../config/database.php";


/* =========================================================
   CHECK LOGIN
========================================================= */

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}


/* =========================================================
   CHECK ADMIN
========================================================= */

$user_id = $_SESSION['user_id'];

$admin_sql = "
    SELECT user_id, name, phone, role, status
    FROM users
    WHERE user_id = :user_id
    LIMIT 1
";

$admin_stmt = $conn->prepare($admin_sql);
$admin_stmt->execute([
    ':user_id' => $user_id
]);

$admin = $admin_stmt->fetch(PDO::FETCH_ASSOC);


/* =========================================================
   ADMIN VALIDATION
========================================================= */

if (!$admin || $admin['role'] !== 'admin' || (int)$admin['status'] !== 1) {
    header("Location: ../index.php");
    exit;
}


/* =========================================================
   POPUP VARIABLES
========================================================= */

$popup_message = "";
$popup_type = "";
$popup_title = "";
$popup_icon = "";

$redirect_page = "";


/* =========================================================
   ADD CATEGORY
========================================================= */

if (isset($_POST['add_category'])) {

    $category_name = trim($_POST['category_name'] ?? '');

    $category_description = trim(
        $_POST['category_description'] ?? ''
    );

    $status = isset($_POST['status'])
        ? (int)$_POST['status']
        : 1;


    /* =====================================================
       VALIDATE CATEGORY NAME
    ===================================================== */

    if ($category_name === '') {

        $popup_message = "Category name is required.";
        $popup_type = "error";
        $popup_title = "Validation Error";
        $popup_icon = "fa fa-times";
    } else {

        /* =================================================
           CATEGORY IMAGE
        ================================================= */

        $category_image = "";


        /* =================================================
           CHECK IMAGE
        ================================================= */

        if (
            isset($_FILES['category_image']) &&
            $_FILES['category_image']['error'] != UPLOAD_ERR_NO_FILE
        ) {

            /* ---------------------------------------------
               CHECK UPLOAD ERROR
            --------------------------------------------- */

            if ($_FILES['category_image']['error'] != UPLOAD_ERR_OK) {

                $popup_message =
                    "Image upload error. Error Code: " .
                    $_FILES['category_image']['error'];

                $popup_type = "error";
                $popup_title = "Image Error";
                $popup_icon = "fa fa-times";
            } else {

                /* -----------------------------------------
                   IMAGE INFORMATION
                ----------------------------------------- */

                $image_name = $_FILES['category_image']['name'];

                $image_tmp = $_FILES['category_image']['tmp_name'];

                $extension = strtolower(
                    pathinfo(
                        $image_name,
                        PATHINFO_EXTENSION
                    )
                );


                /* -----------------------------------------
                   ALLOWED EXTENSIONS
                ----------------------------------------- */

                $allowed_extensions = [
                    "jpg",
                    "jpeg",
                    "png",
                    "webp"
                ];


                if (!in_array($extension, $allowed_extensions)) {

                    $popup_message =
                        "Only JPG, JPEG, PNG and WEBP images are allowed.";

                    $popup_type = "error";
                    $popup_title = "Invalid Image";
                    $popup_icon = "fa fa-times";
                } else {

                    /* -------------------------------------
                       UPLOAD FOLDER
                    ------------------------------------- */

                    $upload_folder = "../uploads/categories/";


                    /* -------------------------------------
                       CREATE FOLDER IF NOT EXISTS
                    ------------------------------------- */

                    if (!is_dir($upload_folder)) {

                        mkdir(
                            $upload_folder,
                            0777,
                            true
                        );
                    }


                    /* -------------------------------------
                       GENERATE UNIQUE IMAGE NAME
                    ------------------------------------- */

                    $category_image =
                        time() .
                        "_" .
                        uniqid() .
                        "." .
                        $extension;


                    /* -------------------------------------
                       UPLOAD IMAGE
                    ------------------------------------- */

                    $upload_path =
                        $upload_folder .
                        $category_image;


                    if (!move_uploaded_file(
                        $image_tmp,
                        $upload_path
                    )) {

                        $popup_message =
                            "Image could not be uploaded.";

                        $popup_type = "error";
                        $popup_title = "Upload Failed";
                        $popup_icon = "fa fa-times";

                        $category_image = "";
                    }
                }
            }
        }


        /* =================================================
           INSERT CATEGORY
        ================================================= */

        if ($popup_type === "") {

            try {

                $sql = "
                    INSERT INTO product_category
                    (
                        category_name,
                        category_description,
                        category_image,
                        status
                    )
                    VALUES
                    (
                        :category_name,
                        :category_description,
                        :category_image,
                        :status
                    )
                ";


                $stmt = $conn->prepare($sql);


                $stmt->execute([
                    ':category_name' =>
                    $category_name,

                    ':category_description' =>
                    $category_description,

                    ':category_image' =>
                    $category_image,

                    ':status' =>
                    $status
                ]);


                /* -----------------------------------------
                   SUCCESS
                ----------------------------------------- */

                $popup_message =
                    "Category added successfully.";

                $popup_type = "success";

                $popup_title = "Category Added";

                $popup_icon = "fa fa-check";

                $redirect_page =
                    "category-management.php";
            } catch (PDOException $e) {

                /* -----------------------------------------
                   DELETE IMAGE IF DATABASE INSERT FAILS
                ----------------------------------------- */

                if ($category_image !== "") {

                    $uploaded_file =
                        "../uploads/categories/" .
                        $category_image;


                    if (file_exists($uploaded_file)) {
                        unlink($uploaded_file);
                    }
                }


                $popup_message =
                    "Database Error: " .
                    $e->getMessage();

                $popup_type = "error";

                $popup_title =
                    "Category Add Failed";

                $popup_icon =
                    "fa fa-times";
            }
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

    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Add Product Category</title>


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


    <!-- Bootstrap Progressbar -->
    <link
        href="assets/vendors/bootstrap-progressbar/css/bootstrap-progressbar-3.3.4.min.css"
        rel="stylesheet">


    <!-- PNotify -->
    <link
        href="assets/vendors/pnotify/dist/pnotify.css"
        rel="stylesheet">

    <link
        href="assets/vendors/pnotify/dist/pnotify.buttons.css"
        rel="stylesheet">

    <link
        href="assets/vendors/pnotify/dist/pnotify.nonblock.css"
        rel="stylesheet">


    <!-- Custom Theme -->
    <link
        href="assets/css/custom.min.css"
        rel="stylesheet">

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

            <?php include 'sidebar.php'; ?>


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

                                            <a href="logout.php">

                                                <i class="fa fa-sign-out pull-right"></i>

                                                Log Out

                                            </a>

                                        </li>

                                    </ul>

                                </li>


                                <!-- =================================================
                                     NOTIFICATION
                                ================================================== -->

                                <li role="presentation" class="dropdown">

                                    <a
                                        href="javascript:;"
                                        class="dropdown-toggle info-number"
                                        data-toggle="dropdown"
                                        aria-expanded="false">

                                        <i class="fa fa-envelope-o"></i>

                                        <span class="badge bg-green">
                                            6
                                        </span>

                                    </a>


                                    <ul
                                        id="menu1"
                                        class="dropdown-menu list-unstyled msg_list"
                                        role="menu">


                                        <li>

                                            <a>

                                                <span class="image">

                                                    <img
                                                        src="assets/images/img.jpg"
                                                        alt="Profile Image">

                                                </span>

                                                <span>

                                                    <span>
                                                        John Smith
                                                    </span>

                                                    <span class="time">
                                                        3 mins ago
                                                    </span>

                                                </span>

                                                <span class="message">
                                                    Film festivals used to be
                                                    do-or-die moments for movie
                                                    makers.
                                                </span>

                                            </a>

                                        </li>


                                        <li>

                                            <a>

                                                <span class="image">

                                                    <img
                                                        src="assets/images/img.jpg"
                                                        alt="Profile Image">

                                                </span>

                                                <span>

                                                    <span>
                                                        John Smith
                                                    </span>

                                                    <span class="time">
                                                        3 mins ago
                                                    </span>

                                                </span>

                                                <span class="message">
                                                    New notification received.
                                                </span>

                                            </a>

                                        </li>


                                        <li>

                                            <a>

                                                <span class="image">

                                                    <img
                                                        src="assets/images/img.jpg"
                                                        alt="Profile Image">

                                                </span>

                                                <span>

                                                    <span>
                                                        John Smith
                                                    </span>

                                                    <span class="time">
                                                        3 mins ago
                                                    </span>

                                                </span>

                                                <span class="message">
                                                    Your category has been updated.
                                                </span>

                                            </a>

                                        </li>


                                        <li>

                                            <a>

                                                <span class="image">

                                                    <img
                                                        src="assets/images/img.jpg"
                                                        alt="Profile Image">

                                                </span>

                                                <span>

                                                    <span>
                                                        John Smith
                                                    </span>

                                                    <span class="time">
                                                        3 mins ago
                                                    </span>

                                                </span>

                                                <span class="message">
                                                    Welcome to the admin panel.
                                                </span>

                                            </a>

                                        </li>


                                        <li>

                                            <div class="text-center">

                                                <a>

                                                    <strong>
                                                        See All Alerts
                                                    </strong>

                                                    <i class="fa fa-angle-right"></i>

                                                </a>

                                            </div>

                                        </li>

                                    </ul>

                                </li>

                            </ul>

                        </nav>

                    </div>

                </div>


                <!-- =================================================
                     ADD CATEGORY PAGE
                ================================================== -->

                <div class="container-fluid py-4">


                    <!-- PAGE HEADER -->

                    <div
                        class="d-flex justify-content-between align-items-center mb-4">

                        <div>

                            <h3 class="fw-bold mb-1">
                                Add Product Category
                            </h3>

                            <p class="text-muted mb-0">
                                Create a new product category
                            </p>

                        </div>


                        <a
                            href="category-management.php"
                            class="btn btn-secondary">

                            <i class="fa fa-arrow-left me-1"></i>

                            Back to Categories

                        </a>

                    </div>


                    <!-- =================================================
                         FORM CARD
                    ================================================== -->

                    <div class="card border-0 shadow-sm">


                        <!-- CARD HEADER -->

                        <div class="card-header bg-white py-3">

                            <h5 class="fw-bold mb-0">

                                Category Information

                            </h5>

                        </div>


                        <!-- CARD BODY -->

                        <div class="card-body">


                            <form
                                method="POST"
                                enctype="multipart/form-data">


                                <div class="row g-4">


                                    <!-- CATEGORY NAME -->

                                    <div class="col-md-6">

                                        <label
                                            class="form-label fw-semibold">

                                            Category Name

                                            <span class="text-danger">
                                                *
                                            </span>

                                        </label>


                                        <input
                                            type="text"
                                            name="category_name"
                                            class="form-control"
                                            placeholder="Enter category name"
                                            required>

                                    </div>


                                    <!-- STATUS -->

                                    <div class="col-md-6">

                                        <label
                                            class="form-label fw-semibold">

                                            Status

                                        </label>


                                        <select
                                            name="status"
                                            class="form-select">

                                            <option value="1">
                                                Active
                                            </option>

                                            <option value="0">
                                                Inactive
                                            </option>

                                        </select>

                                    </div>


                                    <!-- CATEGORY IMAGE -->

                                    <div class="col-md-6">

                                        <label
                                            class="form-label fw-semibold">

                                            Category Image

                                        </label>


                                        <input
                                            type="file"
                                            name="category_image"
                                            class="form-control"
                                            accept=".jpg,.jpeg,.png,.webp">


                                        <small class="text-muted">

                                            Allowed:
                                            JPG, JPEG, PNG, WEBP

                                        </small>

                                    </div>


                                    <!-- DESCRIPTION -->

                                    <div class="col-md-12">

                                        <label
                                            class="form-label fw-semibold">

                                            Category Description

                                        </label>


                                        <textarea
                                            name="category_description"
                                            class="form-control"
                                            rows="5"
                                            placeholder="Enter category description"></textarea>

                                    </div>

                                </div>


                                <!-- BUTTONS -->

                                <div class="border-top mt-4 pt-4">


                                    <a
                                        href="category-management.php"
                                        class="btn btn-secondary me-2">

                                        <i class="fa fa-times me-1"></i>

                                        Cancel

                                    </a>


                                    <button
                                        type="submit"
                                        name="add_category"
                                        value="1"
                                        class="btn btn-primary">

                                        <i class="fa fa-save me-1"></i>

                                        Save Category

                                    </button>

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


    <!-- CUSTOM NOTIFICATIONS -->

    <div
        id="custom_notifications"
        class="custom-notifications dsp_none">

        <ul
            class="list-unstyled notifications clearfix"
            data-tabbed_notifications="notif-group">
        </ul>

        <div class="clearfix"></div>

        <div
            id="notif-group"
            class="tabbed_notifications">
        </div>

    </div>


    <!-- jQuery -->

    <script
        src="assets/vendors/jquery/dist/jquery.min.js">
    </script>


    <!-- Bootstrap -->

    <script
        src="assets/vendors/bootstrap/dist/js/bootstrap.min.js">
    </script>


    <!-- FastClick -->

    <script
        src="assets/vendors/fastclick/lib/fastclick.js">
    </script>


    <!-- NProgress -->

    <script
        src="assets/vendors/nprogress/nprogress.js">
    </script>


    <!-- Bootstrap Progressbar -->

    <script
        src="assets/vendors/bootstrap-progressbar/bootstrap-progressbar.min.js">
    </script>


    <!-- iCheck -->

    <script
        src="assets/vendors/iCheck/icheck.min.js">
    </script>


    <!-- PNotify -->

    <script
        src="assets/vendors/pnotify/dist/pnotify.js">
    </script>

    <script
        src="assets/vendors/pnotify/dist/pnotify.buttons.js">
    </script>

    <script
        src="assets/vendors/pnotify/dist/pnotify.nonblock.js">
    </script>


    <!-- Custom Theme -->

    <script
        src="assets/js/custom.min.js">
    </script>


    <!-- Custom Popup -->

    <script
        src="assets/js/custom-popup.js">
    </script>


    <!-- =====================================================
         PNotify MESSAGE
    ====================================================== -->

    <?php if ($popup_message !== "") { ?>

        <script>
            $(document).ready(function() {

                init_PNotify(
                    <?php echo json_encode($popup_message); ?>,
                    <?php echo json_encode($popup_type); ?>,
                    <?php echo json_encode($popup_title); ?>,
                    <?php echo json_encode($popup_icon); ?>
                );


                <?php if ($redirect_page !== "") { ?>

                    setTimeout(function() {

                        window.location.href =
                            "<?php echo $redirect_page; ?>";

                    }, 2000);

                <?php } ?>

            });
        </script>

    <?php } ?>


</body>

</html>