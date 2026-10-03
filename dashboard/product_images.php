<?php

/* =========================================================
   DATABASE
========================================================= */

include "../config/database.php";


/* =========================================================
   GET PRODUCT ID
========================================================= */

if (!isset($_GET['product_id']) || !is_numeric($_GET['product_id'])) {

    header("Location: products.php");
    exit;
}

$product_id = intval($_GET['product_id']);


/* =========================================================
   CHECK PRODUCT
========================================================= */

$product_sql = "
    SELECT product_id, product_name
    FROM products
    WHERE product_id = $product_id
";

$product_result = mysqli_query($conn, $product_sql);

if (!$product_result || mysqli_num_rows($product_result) == 0) {

    header("Location: products.php");
    exit;
}

$product = mysqli_fetch_assoc($product_result);


/* =========================================================
   DELETE IMAGE
========================================================= */

if (isset($_GET['delete'])) {

    $image_id = intval($_GET['delete']);


    /* GET IMAGE NAME */

    $image_sql = "
        SELECT image_name
        FROM product_images
        WHERE image_id = $image_id
        AND product_id = $product_id
    ";

    $image_result = mysqli_query($conn, $image_sql);


    if ($image_result && mysqli_num_rows($image_result) > 0) {

        $image_data = mysqli_fetch_assoc($image_result);

        $image_path = "../uploads/products/" . $image_data['image_name'];


        /* DELETE FILE */

        if (file_exists($image_path)) {

            unlink($image_path);
        }


        /* DELETE DATABASE RECORD */

        $delete_sql = "
            DELETE FROM product_images
            WHERE image_id = $image_id
            AND product_id = $product_id
        ";

        mysqli_query($conn, $delete_sql);
    }


    header(
        "Location: product_images.php?product_id=" . $product_id
    );

    exit;
}


/* =========================================================
   ADD MULTIPLE IMAGES
========================================================= */

$message = "";
$message_type = "";


if (isset($_POST['upload_images'])) {


    if (
        !isset($_FILES['product_images']) ||
        empty($_FILES['product_images']['name'][0])
    ) {

        $message = "Please select at least one image.";
        $message_type = "danger";
    } else {


        $upload_folder = "../uploads/products/";


        /* CREATE FOLDER */

        if (!is_dir($upload_folder)) {

            mkdir(
                $upload_folder,
                0777,
                true
            );
        }


        $allowed_extensions = array(
            "jpg",
            "jpeg",
            "png",
            "webp"
        );


        $total_images =
            count($_FILES['product_images']['name']);


        $success_count = 0;


        for (
            $i = 0;
            $i < $total_images;
            $i++
        ) {


            /* CHECK ERROR */

            if (
                $_FILES['product_images']['error'][$i]
                != UPLOAD_ERR_OK
            ) {

                continue;
            }


            $original_name =
                $_FILES['product_images']['name'][$i];


            $tmp_name =
                $_FILES['product_images']['tmp_name'][$i];


            $extension =
                strtolower(
                    pathinfo(
                        $original_name,
                        PATHINFO_EXTENSION
                    )
                );


            /* CHECK EXTENSION */

            if (
                !in_array(
                    $extension,
                    $allowed_extensions
                )
            ) {

                continue;
            }


            /* UNIQUE FILE NAME */

            $new_name =
                time()
                . "_"
                . uniqid()
                . "."
                . $extension;


            $destination =
                $upload_folder
                . $new_name;


            /* MOVE IMAGE */

            if (
                move_uploaded_file(
                    $tmp_name,
                    $destination
                )
            ) {


                /* INSERT DATABASE */

                $image_name =
                    mysqli_real_escape_string(
                        $conn,
                        $new_name
                    );


                $insert_sql = "
                    INSERT INTO product_images
                    (
                        product_id,
                        image_name,
                        status
                    )
                    VALUES
                    (
                        '$product_id',
                        '$image_name',
                        1
                    )
                ";


                if (
                    mysqli_query(
                        $conn,
                        $insert_sql
                    )
                ) {

                    $success_count++;
                } else {

                    /* REMOVE FILE IF DB INSERT FAILS */

                    if (
                        file_exists($destination)
                    ) {

                        unlink($destination);
                    }
                }
            }
        }


        if ($success_count > 0) {

            $message =
                $success_count
                . " image(s) uploaded successfully.";

            $message_type = "success";
        } else {

            $message =
                "No image was uploaded. Please select valid JPG, JPEG, PNG or WEBP files.";

            $message_type = "danger";
        }
    }
}


/* =========================================================
   FETCH PRODUCT IMAGES
========================================================= */

$images_sql = "
    SELECT
        image_id,
        image_name,
        status,
        created_at
    FROM product_images
    WHERE product_id = $product_id
    ORDER BY image_id DESC
";

$images_result =
    mysqli_query(
        $conn,
        $images_sql
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


    <title>Product Images</title>


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
        .image-card {

            border: 1px solid #ddd;

            border-radius: 6px;

            padding: 10px;

            margin-bottom: 20px;

            background: #fff;

        }


        .product-image {

            width: 100%;

            height: 180px;

            object-fit: cover;

            border-radius: 5px;

        }


        .image-name {

            margin-top: 10px;

            word-break: break-all;

            font-size: 13px;

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

            <div class="right_col"
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


                <div class="container-fluid">


                    <!-- PAGE HEADER -->

                    <div class="row">

                        <div class="col-md-12">

                            <div class="page-title">

                                <div class="title_left">

                                    <h3>

                                        Product Images

                                    </h3>

                                    <p class="text-muted">

                                        Manage images for
                                        <strong>

                                            <?php
                                            echo htmlspecialchars(
                                                $product['product_name']
                                            );
                                            ?>

                                        </strong>

                                    </p>

                                </div>


                                <div class="title_right">

                                    <a
                                        href="products.php"
                                        class="btn btn-default">

                                        <i class="fa fa-arrow-left"></i>

                                        Back to Products

                                    </a>

                                </div>

                            </div>

                        </div>

                    </div>


                    <!-- =================================================
                 MESSAGE
            ================================================== -->

                    <?php if ($message != "") { ?>

                        <div class="alert alert-<?php echo $message_type; ?>">

                            <button
                                type="button"
                                class="close"
                                data-dismiss="alert">

                                ×

                            </button>

                            <?php
                            echo htmlspecialchars($message);
                            ?>

                        </div>

                    <?php } ?>


                    <!-- =================================================
                 ADD IMAGES
            ================================================== -->

                    <div class="row">

                        <div class="col-md-12">


                            <div class="x_panel">


                                <div class="x_title">

                                    <h2>

                                        Add Product Images

                                        <small>

                                            You can select multiple images

                                        </small>

                                    </h2>

                                    <div class="clearfix"></div>

                                </div>


                                <div class="x_content">


                                    <form
                                        method="POST"
                                        enctype="multipart/form-data">


                                        <input
                                            type="hidden"
                                            name="product_id"
                                            value="<?php echo $product_id; ?>">


                                        <div class="form-group">


                                            <label>

                                                Select Product Images

                                                <span
                                                    class="text-danger">
                                                    *
                                                </span>

                                            </label>


                                            <input
                                                type="file"
                                                name="product_images[]"
                                                class="form-control"
                                                accept=".jpg,.jpeg,.png,.webp"
                                                multiple
                                                required>


                                            <small
                                                class="text-muted">

                                                You can select multiple
                                                JPG, JPEG, PNG or WEBP images.

                                            </small>


                                        </div>


                                        <br>


                                        <button
                                            type="submit"
                                            name="upload_images"
                                            value="1"
                                            class="btn btn-primary">


                                            <i class="fa fa-upload"></i>

                                            Upload Images


                                        </button>


                                    </form>


                                </div>

                            </div>

                        </div>

                    </div>


                    <!-- =================================================
                 IMAGE LIST
            ================================================== -->

                    <div class="row">

                        <div class="col-md-12">


                            <div class="x_panel">


                                <div class="x_title">

                                    <h2>

                                        Product Image List

                                        <small>

                                            <?php
                                            echo htmlspecialchars(
                                                $product['product_name']
                                            );
                                            ?>

                                        </small>

                                    </h2>


                                    <div class="clearfix"></div>

                                </div>


                                <div class="x_content">


                                    <div class="row">


                                        <?php

                                        if (
                                            $images_result
                                            &&
                                            mysqli_num_rows(
                                                $images_result
                                            ) > 0
                                        ) {


                                            while (
                                                $image =
                                                mysqli_fetch_assoc(
                                                    $images_result
                                                )
                                            ) {

                                        ?>


                                                <div
                                                    class="col-md-3 col-sm-4 col-xs-6">


                                                    <div
                                                        class="image-card">


                                                        <img
                                                            src="../uploads/products/<?php echo htmlspecialchars($image['image_name']); ?>"
                                                            class="product-image"
                                                            alt="Product Image">


                                                        <div
                                                            class="image-name">

                                                            <?php
                                                            echo htmlspecialchars(
                                                                $image['image_name']
                                                            );
                                                            ?>

                                                        </div>


                                                        <div
                                                            class="text-muted">

                                                            <small>

                                                                <?php
                                                                echo date(
                                                                    "d-m-Y",
                                                                    strtotime(
                                                                        $image['created_at']
                                                                    )
                                                                );
                                                                ?>

                                                            </small>

                                                        </div>


                                                        <br>


                                                        <a
                                                            href="product_images.php?product_id=<?php echo $product_id; ?>&delete=<?php echo $image['image_id']; ?>"
                                                            class="btn btn-sm btn-danger"
                                                            onclick="return confirm('Are you sure you want to delete this image?');">

                                                            <i
                                                                class="fa fa-trash">
                                                            </i>

                                                            Delete

                                                        </a>


                                                    </div>


                                                </div>


                                            <?php

                                            }
                                        } else {

                                            ?>


                                            <div class="col-md-12">

                                                <div
                                                    class="alert alert-info">

                                                    <i
                                                        class="fa fa-info-circle">
                                                    </i>

                                                    No images added for this
                                                    product yet.

                                                </div>

                                            </div>


                                        <?php

                                        }

                                        ?>


                                    </div>


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


    <!-- =====================================================
     JAVASCRIPT
====================================================== -->


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