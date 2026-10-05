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

if (
    !$admin ||
    $admin['role'] !== 'admin' ||
    (int)$admin['status'] !== 1
) {
    header("Location: ../index.php");
    exit;
}


/* =========================================================
   GET CATEGORY ID
========================================================= */

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: category-management.php");
    exit;
}

$category_id = (int)$_GET['id'];


/* =========================================================
   FETCH CATEGORY
========================================================= */

$sql = "
    SELECT *
    FROM product_category
    WHERE category_id = :category_id
    LIMIT 1
";

$stmt = $conn->prepare($sql);

$stmt->execute([
    ':category_id' => $category_id
]);

$category = $stmt->fetch(PDO::FETCH_ASSOC);


/* =========================================================
   CATEGORY NOT FOUND
========================================================= */

if (!$category) {
    header("Location: category-management.php");
    exit;
}


/* =========================================================
   ERROR VARIABLE
========================================================= */

$error = "";


/* =========================================================
   UPDATE CATEGORY
========================================================= */

if (isset($_POST['update_category'])) {

    /* =====================================================
       GET FORM DATA
    ===================================================== */

    $category_name = trim(
        $_POST['category_name'] ?? ''
    );

    $category_description = trim(
        $_POST['category_description'] ?? ''
    );

    $status = isset($_POST['status'])
        ? (int)$_POST['status']
        : 0;


    /* =====================================================
       VALIDATE CATEGORY NAME
    ===================================================== */

    if ($category_name === '') {

        $error = "Category name is required.";
    } else {

        /* =================================================
           KEEP OLD IMAGE
        ================================================= */

        $category_image =
            $category['category_image'] ?? "";


        /* =================================================
           IMAGE UPLOAD
        ================================================= */

        if (
            isset($_FILES['category_image']) &&
            $_FILES['category_image']['error'] != UPLOAD_ERR_NO_FILE
        ) {

            /* ---------------------------------------------
               CHECK UPLOAD ERROR
            --------------------------------------------- */

            if (
                $_FILES['category_image']['error']
                != UPLOAD_ERR_OK
            ) {

                $error =
                    "Image upload error. Error Code: " .
                    $_FILES['category_image']['error'];
            } else {

                /* -----------------------------------------
                   GET IMAGE DETAILS
                ----------------------------------------- */

                $file_name =
                    $_FILES['category_image']['name'];

                $tmp_name =
                    $_FILES['category_image']['tmp_name'];

                $extension = strtolower(
                    pathinfo(
                        $file_name,
                        PATHINFO_EXTENSION
                    )
                );


                /* -----------------------------------------
                   ALLOWED EXTENSIONS
                ----------------------------------------- */

                $allowed = [
                    'jpg',
                    'jpeg',
                    'png',
                    'webp'
                ];


                if (!in_array($extension, $allowed)) {

                    $error =
                        "Only JPG, JPEG, PNG and WEBP images are allowed.";
                } else {

                    /* -------------------------------------
                       UPLOAD DIRECTORY
                    ------------------------------------- */

                    $upload_dir =
                        "../assets/images/";


                    /* -------------------------------------
                       CREATE DIRECTORY
                    ------------------------------------- */

                    if (!is_dir($upload_dir)) {

                        mkdir(
                            $upload_dir,
                            0777,
                            true
                        );
                    }


                    /* -------------------------------------
                       GENERATE NEW IMAGE NAME
                    ------------------------------------- */

                    $new_name =
                        "category_" .
                        time() .
                        "_" .
                        rand(1000, 9999) .
                        "." .
                        $extension;


                    $new_image_path =
                        $upload_dir .
                        $new_name;


                    /* -------------------------------------
                       MOVE NEW IMAGE
                    ------------------------------------- */

                    if (
                        move_uploaded_file(
                            $tmp_name,
                            $new_image_path
                        )
                    ) {

                        /* ---------------------------------
                           DELETE OLD IMAGE
                        --------------------------------- */

                        if (
                            !empty($category['category_image'])
                        ) {

                            $old_image_path =
                                $upload_dir .
                                $category['category_image'];


                            if (
                                file_exists(
                                    $old_image_path
                                )
                            ) {

                                unlink(
                                    $old_image_path
                                );
                            }
                        }


                        /* ---------------------------------
                           USE NEW IMAGE
                        --------------------------------- */

                        $category_image =
                            $new_name;
                    } else {

                        $error =
                            "New image could not be uploaded.";
                    }
                }
            }
        }


        /* =================================================
           UPDATE DATABASE
        ================================================= */

        if ($error === "") {

            try {

                $update_sql = "
                    UPDATE product_category
                    SET
                        category_name = :category_name,
                        category_description = :category_description,
                        category_image = :category_image,
                        status = :status
                    WHERE category_id = :category_id
                ";


                $update_stmt =
                    $conn->prepare($update_sql);


                $update_stmt->execute([

                    ':category_name' =>
                    $category_name,

                    ':category_description' =>
                    $category_description,

                    ':category_image' =>
                    $category_image,

                    ':status' =>
                    $status,

                    ':category_id' =>
                    $category_id

                ]);


                /* -----------------------------------------
                   REDIRECT AFTER SUCCESS
                ----------------------------------------- */

                header(
                    "Location: category-management.php"
                );

                exit;
            } catch (PDOException $e) {

                $error =
                    "Database Error: " .
                    $e->getMessage();
            }
        }
    }


    /* =====================================================
       FETCH UPDATED DATA IF ERROR OCCURS
    ===================================================== */

    if ($error !== "") {

        $refresh_sql = "
            SELECT *
            FROM product_category
            WHERE category_id = :category_id
            LIMIT 1
        ";

        $refresh_stmt =
            $conn->prepare($refresh_sql);

        $refresh_stmt->execute([
            ':category_id' => $category_id
        ]);

        $category =
            $refresh_stmt->fetch(PDO::FETCH_ASSOC);
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1">

    <title>Edit Category</title>


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


    <!-- Custom -->

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

                                            <a href="profile.php">

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

                <div class="container-fluid">


                    <div class="row">

                        <div class="col-md-12">


                            <!-- PAGE HEADER -->

                            <div class="page-title">

                                <div class="title_left">

                                    <h3>
                                        Edit Category
                                    </h3>

                                </div>

                            </div>


                            <div class="clearfix"></div>


                            <!-- =================================================
                                 ERROR MESSAGE
                            ================================================== -->

                            <?php if ($error !== "") { ?>

                                <div class="alert alert-danger">

                                    <?php

                                    echo htmlspecialchars(
                                        $error
                                    );

                                    ?>

                                </div>

                            <?php } ?>


                            <!-- =================================================
                                 EDIT FORM
                            ================================================== -->

                            <div class="x_panel">


                                <div class="x_title">

                                    <h2>
                                        Update Category Details
                                    </h2>

                                    <div class="clearfix"></div>

                                </div>


                                <div class="x_content">


                                    <form
                                        method="POST"
                                        enctype="multipart/form-data">


                                        <div class="row">


                                            <!-- CATEGORY ID -->

                                            <div class="col-md-6">

                                                <div class="form-group">

                                                    <label>
                                                        Category ID
                                                    </label>


                                                    <input
                                                        type="text"
                                                        class="form-control"
                                                        value="<?php
                                                                echo htmlspecialchars(
                                                                    $category['category_id']
                                                                );
                                                                ?>"
                                                        readonly>

                                                </div>

                                            </div>


                                            <!-- STATUS -->

                                            <div class="col-md-6">

                                                <div class="form-group">

                                                    <label>
                                                        Status
                                                    </label>


                                                    <select
                                                        name="status"
                                                        class="form-control"
                                                        required>


                                                        <option
                                                            value="1"
                                                            <?php

                                                            if (
                                                                (int)$category['status']
                                                                === 1
                                                            ) {

                                                                echo "selected";
                                                            }

                                                            ?>>

                                                            Active

                                                        </option>


                                                        <option
                                                            value="0"
                                                            <?php

                                                            if (
                                                                (int)$category['status']
                                                                === 0
                                                            ) {

                                                                echo "selected";
                                                            }

                                                            ?>>

                                                            Inactive

                                                        </option>


                                                    </select>

                                                </div>

                                            </div>


                                            <!-- CATEGORY NAME -->

                                            <div class="col-md-12">

                                                <div class="form-group">


                                                    <label>

                                                        Category Name

                                                        <span
                                                            class="text-danger">

                                                            *

                                                        </span>

                                                    </label>


                                                    <input
                                                        type="text"
                                                        name="category_name"
                                                        class="form-control"
                                                        value="<?php
                                                                echo htmlspecialchars(
                                                                    $category['category_name']
                                                                );
                                                                ?>"
                                                        placeholder="Enter category name"
                                                        required>

                                                </div>

                                            </div>


                                            <!-- DESCRIPTION -->

                                            <div class="col-md-12">

                                                <div class="form-group">


                                                    <label>

                                                        Category Description

                                                    </label>


                                                    <textarea
                                                        name="category_description"
                                                        class="form-control"
                                                        rows="5"
                                                        placeholder="Enter category description"><?php

                                                                                                    echo htmlspecialchars(
                                                                                                        $category['category_description'] ?? ''
                                                                                                    );

                                                                                                    ?></textarea>


                                                </div>

                                            </div>


                                            <!-- CURRENT IMAGE -->

                                            <div class="col-md-6">

                                                <div class="form-group">


                                                    <label>

                                                        Current Image

                                                    </label>


                                                    <br>


                                                    <?php

                                                    if (
                                                        !empty($category['category_image'])
                                                    ) {

                                                    ?>
                                                        <img
                                                            src="assets/images/<?php echo htmlspecialchars($category['category_image']); ?>"
                                                            alt="Category Image"
                                                            style="
                                                                width:150px;
                                                                height:150px;
                                                                object-fit:cover;
                                                                border-radius:10px;
                                                                border:1px solid #ddd;
                                                            ">
                                                    <?php

                                                    } else {

                                                    ?>

                                                        <div
                                                            style="
                                                                width:150px;
                                                                height:150px;
                                                                background:#f5f5f5;
                                                                display:flex;
                                                                align-items:center;
                                                                justify-content:center;
                                                                border-radius:10px;
                                                                border:1px solid #ddd;
                                                            ">

                                                            <i
                                                                class="fa fa-image fa-3x text-muted">
                                                            </i>

                                                        </div>

                                                    <?php

                                                    }

                                                    ?>


                                                </div>

                                            </div>


                                            <!-- NEW IMAGE -->

                                            <div class="col-md-6">

                                                <div class="form-group">


                                                    <label>

                                                        Change Category Image

                                                    </label>


                                                    <input
                                                        type="file"
                                                        name="category_image"
                                                        class="form-control"
                                                        accept=".jpg,.jpeg,.png,.webp">


                                                    <small
                                                        class="text-muted">

                                                        Leave empty if you
                                                        don't want to change
                                                        the image.

                                                    </small>


                                                </div>

                                            </div>


                                        </div>


                                        <!-- BUTTONS -->

                                        <div class="ln_solid"></div>


                                        <div class="form-group">


                                            <a
                                                href="category-management.php"
                                                class="btn btn-secondary">

                                                <i
                                                    class="fa fa-arrow-left">
                                                </i>

                                                Back

                                            </a>


                                            <button
                                                type="submit"
                                                name="update_category"
                                                value="1"
                                                class="btn btn-primary">

                                                <i
                                                    class="fa fa-save">
                                                </i>

                                                Update Category

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

                        Gentelella -
                        Bootstrap Admin Template

                    </div>


                    <div class="clearfix"></div>

                </footer>


            </div>

        </div>

    </div>


    <!-- =========================================================
         JAVASCRIPT
    ========================================================== -->


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


    <!-- Custom -->

    <script
        src="assets/js/custom.min.js">
    </script>


</body>

</html>