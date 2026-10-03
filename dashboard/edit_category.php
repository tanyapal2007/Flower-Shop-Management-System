<?php
session_start();
include "../config/database.php";

/* =========================================================
   GET CATEGORY ID
========================================================= */

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: category-management.php");
    exit;
}

$category_id = (int) $_GET['id'];


/* =========================================================
   FETCH CATEGORY
========================================================= */

$sql = "SELECT * FROM product_category WHERE category_id = $category_id LIMIT 1";

$result = mysqli_query($conn, $sql);

if (!$result || mysqli_num_rows($result) == 0) {
    header("Location: category-management.php");
    exit;
}

$category = mysqli_fetch_assoc($result);


/* =========================================================
   UPDATE CATEGORY
========================================================= */

if (isset($_POST['update_category'])) {

    $category_name = mysqli_real_escape_string(
        $conn,
        trim($_POST['category_name'])
    );

    $category_description = mysqli_real_escape_string(
        $conn,
        trim($_POST['category_description'])
    );

    $status = isset($_POST['status']) ? (int)$_POST['status'] : 0;

    /* Keep old image */
    $category_image = $category['category_image'];


    /* =====================================================
       IMAGE UPLOAD
    ===================================================== */

    if (
        isset($_FILES['category_image']) &&
        $_FILES['category_image']['error'] == 0
    ) {

        $upload_dir = "uploads/categories/";

        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_name = $_FILES['category_image']['name'];
        $tmp_name = $_FILES['category_image']['tmp_name'];

        $extension = strtolower(
            pathinfo($file_name, PATHINFO_EXTENSION)
        );

        $allowed = ['jpg', 'jpeg', 'png', 'webp'];

        if (in_array($extension, $allowed)) {

            $new_name =
                "category_" .
                time() .
                "_" .
                rand(1000, 9999) .
                "." .
                $extension;

            if (move_uploaded_file(
                $tmp_name,
                $upload_dir . $new_name
            )) {

                /* Delete old image */
                if (
                    !empty($category['category_image']) &&
                    file_exists(
                        $upload_dir . $category['category_image']
                    )
                ) {

                    unlink(
                        $upload_dir .
                            $category['category_image']
                    );
                }

                $category_image = $new_name;
            }
        }
    }


    /* =====================================================
       UPDATE QUERY
    ===================================================== */

    $update_sql = "
        UPDATE product_category
        SET
            category_name = '$category_name',
            category_description = '$category_description',
            category_image = '$category_image',
            status = '$status'
        WHERE category_id = $category_id
    ";


    if (mysqli_query($conn, $update_sql)) {

        header("Location: category-management.php");
        exit;
    } else {

        $error = mysqli_error($conn);
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="utf-8">

    <meta name="viewport"
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


                            <ul class="nav navbar-nav navbar-right">

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


                            <!-- ERROR -->

                            <?php if (isset($error)) { ?>

                                <div class="alert alert-danger">

                                    <?php
                                    echo htmlspecialchars($error);
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
                                                                $category['status'] == 1
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
                                                                $category['status'] == 0
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
                                                        <span class="text-danger">
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


                                            <!-- OLD IMAGE -->

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
                                                            src="uploads/categories/<?php
                                                                                    echo htmlspecialchars(
                                                                                        $category['category_image']
                                                                                    );
                                                                                    ?>"
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


                                                    <small class="text-muted">

                                                        Leave empty if you don't
                                                        want to change the image.

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

                                                <i class="fa fa-arrow-left"></i>

                                                Back

                                            </a>


                                            <button
                                                type="submit"
                                                name="update_category"
                                                value="1"
                                                class="btn btn-primary">

                                                <i class="fa fa-save"></i>

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