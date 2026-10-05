<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../config/database.php";


/* =========================================================
   GET LOGGED IN USER
========================================================= */

$login_user_id = $_SESSION['user_id'] ?? 0;


/* =========================================================
   CHECK LOGIN
========================================================= */

if (empty($login_user_id)) {
    header("Location: ../index.php");
    exit;
}


/* =========================================================
   CHECK ADMIN
========================================================= */

$admin_sql = "
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

$admin_stmt = $conn->prepare($admin_sql);

$admin_stmt->execute([
    ':user_id' => $login_user_id
]);

$admin = $admin_stmt->fetch(PDO::FETCH_ASSOC);


/* =========================================================
   USER NOT FOUND
========================================================= */

if (!$admin) {
    header("Location: ../index.php");
    exit;
}


/* =========================================================
   CHECK STATUS
========================================================= */

if ((int)$admin['status'] !== 1) {
    header("Location: ../index.php");
    exit;
}


/* =========================================================
   CHECK ADMIN ROLE
========================================================= */

if ($admin['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}


/* =========================================================
   ADD SUBCATEGORY
========================================================= */

if (isset($_POST['add_subcategory'])) {

    $category_id = (int)($_POST['category_id'] ?? 0);

    $subcategory_name = trim(
        $_POST['subcategory_name'] ?? ''
    );

    $subcategory_description = trim(
        $_POST['subcategory_description'] ?? ''
    );

    $status = isset($_POST['status'])
        ? (int)$_POST['status']
        : 1;


    /* =====================================================
       BASIC VALIDATION
    ===================================================== */

    if ($category_id <= 0) {

        echo "<script>
            alert('Please select a category.');
            window.history.back();
        </script>";

        exit;
    }


    if ($subcategory_name === '') {

        echo "<script>
            alert('Please enter subcategory name.');
            window.history.back();
        </script>";

        exit;
    }


    /* =====================================================
       CHECK CATEGORY EXISTS
    ===================================================== */

    $category_check_sql = "
        SELECT category_id
        FROM product_category
        WHERE category_id = :category_id
        AND status = 1
        LIMIT 1
    ";

    $category_check_stmt = $conn->prepare(
        $category_check_sql
    );

    $category_check_stmt->execute([
        ':category_id' => $category_id
    ]);

    $category_exists = $category_check_stmt->fetch(
        PDO::FETCH_ASSOC
    );


    if (!$category_exists) {

        echo "<script>
            alert('Selected category is invalid.');
            window.history.back();
        </script>";

        exit;
    }


    /* =====================================================
       IMAGE UPLOAD
       Folder: Project/assets/images/
    ===================================================== */

    $subcategory_image = "";


    if (
        isset($_FILES['subcategory_image']) &&
        $_FILES['subcategory_image']['error'] !== UPLOAD_ERR_NO_FILE
    ) {


        /* -------------------------------------------------
           CHECK UPLOAD ERROR
        ------------------------------------------------- */

        if (
            $_FILES['subcategory_image']['error']
            !== UPLOAD_ERR_OK
        ) {

            echo "<script>
                alert('Error while uploading image.');
                window.history.back();
            </script>";

            exit;
        }


        /* -------------------------------------------------
           ORIGINAL FILE NAME
        ------------------------------------------------- */

        $image_name = $_FILES['subcategory_image']['name'];


        /* -------------------------------------------------
           TEMP FILE
        ------------------------------------------------- */

        $image_tmp = $_FILES['subcategory_image']['tmp_name'];


        /* -------------------------------------------------
           EXTENSION
        ------------------------------------------------- */

        $extension = strtolower(
            pathinfo(
                $image_name,
                PATHINFO_EXTENSION
            )
        );


        /* -------------------------------------------------
           ALLOWED EXTENSIONS
        ------------------------------------------------- */

        $allowed_extensions = [
            'jpg',
            'jpeg',
            'png',
            'webp'
        ];


        if (
            !in_array(
                $extension,
                $allowed_extensions,
                true
            )
        ) {

            echo "<script>
                alert('Only JPG, JPEG, PNG and WEBP images are allowed.');
                window.history.back();
            </script>";

            exit;
        }


        /* -------------------------------------------------
           IMAGE SIZE CHECK
        ------------------------------------------------- */

        $max_file_size = 5 * 1024 * 1024;


        if (
            $_FILES['subcategory_image']['size']
            > $max_file_size
        ) {

            echo "<script>
                alert('Image size must be less than 5 MB.');
                window.history.back();
            </script>";

            exit;
        }


        /* -------------------------------------------------
           CHECK ACTUAL IMAGE
        ------------------------------------------------- */

        $image_info = getimagesize($image_tmp);


        if ($image_info === false) {

            echo "<script>
                alert('Please upload a valid image.');
                window.history.back();
            </script>";

            exit;
        }


        /* -------------------------------------------------
           UPLOAD FOLDER
        ------------------------------------------------- */

        $upload_folder = "../assets/images/";


        /* -------------------------------------------------
           CREATE FOLDER IF NOT EXISTS
        ------------------------------------------------- */

        if (!is_dir($upload_folder)) {

            mkdir(
                $upload_folder,
                0777,
                true
            );
        }


        /* -------------------------------------------------
           GENERATE UNIQUE FILE NAME
        ------------------------------------------------- */

        $subcategory_image =
            time()
            . "_"
            . uniqid()
            . "."
            . $extension;


        /* -------------------------------------------------
           MOVE IMAGE
        ------------------------------------------------- */

        $upload_success = move_uploaded_file(
            $image_tmp,
            $upload_folder . $subcategory_image
        );


        if (!$upload_success) {

            echo "<script>
                alert('Image upload failed.');
                window.history.back();
            </script>";

            exit;
        }
    }


    /* =====================================================
       INSERT SUBCATEGORY
    ===================================================== */

    $insert_sql = "
        INSERT INTO product_subcategory
        (
            category_id,
            subcategory_name,
            subcategory_description,
            subcategory_image,
            status
        )
        VALUES
        (
            :category_id,
            :subcategory_name,
            :subcategory_description,
            :subcategory_image,
            :status
        )
    ";


    try {

        $insert_stmt = $conn->prepare(
            $insert_sql
        );


        $insert_stmt->execute([

            ':category_id' =>
            $category_id,

            ':subcategory_name' =>
            $subcategory_name,

            ':subcategory_description' =>
            $subcategory_description,

            ':subcategory_image' =>
            $subcategory_image,

            ':status' =>
            $status

        ]);


        echo "<script>

            alert(
                'Subcategory added successfully.'
            );

            window.location.href =
                'product_subcategory.php';

        </script>";

        exit;
    } catch (PDOException $e) {


        /* -------------------------------------------------
           DELETE UPLOADED IMAGE IF INSERT FAILED
        ------------------------------------------------- */

        if (
            !empty($subcategory_image)
        ) {

            $uploaded_image =
                "../assets/images/"
                . $subcategory_image;


            if (
                file_exists($uploaded_image)
            ) {

                unlink($uploaded_image);
            }
        }


        echo "<script>

            alert(
                " . json_encode(
            "Subcategory add failed: "
                . $e->getMessage()
        ) . "
            );

            window.history.back();

        </script>";

        exit;
    }
}


/* =========================================================
   GET ACTIVE CATEGORIES
========================================================= */

$category_sql = "
    SELECT
        category_id,
        category_name
    FROM product_category
    WHERE status = 1
    ORDER BY category_name ASC
";


$category_stmt = $conn->prepare(
    $category_sql
);


$category_stmt->execute();


$categories = $category_stmt->fetchAll(
    PDO::FETCH_ASSOC
);

?>


<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="utf-8">

    <meta name="viewport"
        content="width=device-width, initial-scale=1">

    <title>Add Product Subcategory</title>


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


    <!-- Custom Theme -->

    <link
        href="assets/css/custom.min.css"
        rel="stylesheet">


    <style>
        .card {

            background: #fff;

            border: 1px solid #ddd;

            border-radius: 5px;

            padding: 25px;

            margin-top: 20px;
        }


        .card-header {

            border-bottom: 1px solid #eee;

            padding-bottom: 15px;

            margin-bottom: 25px;
        }


        .card-body {

            padding: 0;
        }


        .form-group {

            margin-bottom: 20px;
        }
    </style>

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
             TOP NAV
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


                                <!-- USER PROFILE -->

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
                                            $admin['name']
                                        );

                                        ?>


                                        <span class="fa fa-angle-down"></span>


                                    </a>


                                    <ul
                                        class="dropdown-menu dropdown-usermenu pull-right">


                                        <li>

                                            <a href="javascript:;">

                                                <i class="fa fa-user pull-right"></i>

                                                Profile

                                            </a>

                                        </li>


                                        <li>

                                            <a href="javascript:;">

                                                <i class="fa fa-cog pull-right"></i>

                                                Settings

                                            </a>

                                        </li>


                                        <li>

                                            <a href="javascript:;">

                                                <i class="fa fa-question-circle pull-right"></i>

                                                Help

                                            </a>

                                        </li>


                                        <li>

                                            <a href="../logout.php">

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
             PAGE
        ================================================== -->

                <div class="container-fluid">


                    <!-- HEADER -->

                    <div class="row"
                        style="margin-bottom: 20px;">


                        <div class="col-md-8">


                            <h3>

                                <i class="fa fa-plus"></i>

                                Add Product Subcategory

                            </h3>


                            <p class="text-muted">

                                Create a new product subcategory

                            </p>


                        </div>


                        <div class="col-md-4 text-right">


                            <a
                                href="product_subcategory.php"
                                class="btn btn-secondary">


                                <i class="fa fa-arrow-left"></i>

                                Back to Subcategories


                            </a>


                        </div>


                    </div>


                    <!-- FORM CARD -->

                    <div class="card">


                        <div class="card-header">


                            <h4>

                                Subcategory Information

                            </h4>


                        </div>


                        <div class="card-body">


                            <form
                                method="POST"
                                enctype="multipart/form-data">


                                <div class="row">


                                    <!-- CATEGORY -->

                                    <div class="col-md-6">


                                        <div class="form-group">


                                            <label>

                                                Category

                                                <span class="text-danger">
                                                    *
                                                </span>

                                            </label>


                                            <select
                                                name="category_id"
                                                class="form-control"
                                                required>


                                                <option value="">

                                                    Select Category

                                                </option>


                                                <?php

                                                foreach (
                                                    $categories as $category
                                                ) {

                                                ?>


                                                    <option
                                                        value="<?php
                                                                echo (int)$category['category_id'];
                                                                ?>">


                                                        <?php

                                                        echo htmlspecialchars(
                                                            $category['category_name']
                                                        );

                                                        ?>


                                                    </option>


                                                <?php

                                                }

                                                ?>


                                            </select>


                                        </div>


                                    </div>


                                    <!-- SUBCATEGORY NAME -->

                                    <div class="col-md-6">


                                        <div class="form-group">


                                            <label>

                                                Subcategory Name

                                                <span class="text-danger">
                                                    *
                                                </span>

                                            </label>


                                            <input
                                                type="text"
                                                name="subcategory_name"
                                                class="form-control"
                                                placeholder="Enter subcategory name"
                                                required>


                                        </div>


                                    </div>


                                    <!-- IMAGE -->

                                    <div class="col-md-6">


                                        <div class="form-group">


                                            <label>

                                                Subcategory Image

                                            </label>


                                            <input
                                                type="file"
                                                name="subcategory_image"
                                                class="form-control"
                                                accept=".jpg,.jpeg,.png,.webp">


                                            <small class="text-muted">

                                                Allowed: JPG, JPEG, PNG, WEBP
                                                (Maximum 5 MB)

                                            </small>


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


                                    <!-- DESCRIPTION -->

                                    <div class="col-md-12">


                                        <div class="form-group">


                                            <label>

                                                Subcategory Description

                                            </label>


                                            <textarea
                                                name="subcategory_description"
                                                class="form-control"
                                                rows="5"
                                                placeholder="Enter subcategory description"></textarea>


                                        </div>


                                    </div>


                                </div>


                                <!-- BUTTONS -->

                                <div
                                    style="
                                border-top:1px solid #eee;
                                padding-top:20px;
                                margin-top:10px;
                            ">


                                    <a
                                        href="product_subcategory.php"
                                        class="btn btn-secondary">


                                        <i class="fa fa-times"></i>

                                        Cancel


                                    </a>


                                    <button
                                        type="submit"
                                        name="add_subcategory"
                                        value="1"
                                        class="btn btn-primary">


                                        <i class="fa fa-save"></i>

                                        Save Subcategory


                                    </button>


                                </div>


                            </form>


                        </div>


                    </div>


                </div>


                <!-- FOOTER -->

                <footer>


                    <div class="pull-right">

                        Product Management Admin Panel

                    </div>


                    <div class="clearfix"></div>


                </footer>


            </div>
            <!-- right_col -->


        </div>
        <!-- main_container -->


    </div>
    <!-- container body -->


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


</body>

</html>