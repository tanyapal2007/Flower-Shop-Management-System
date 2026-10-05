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


if (!$admin) {
    header("Location: ../index.php");
    exit;
}


if ((int)$admin['status'] !== 1) {
    header("Location: ../index.php");
    exit;
}


if ($admin['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}


/* =========================================================
   GET PRODUCT ID
========================================================= */

$product_id = isset($_GET['product_id'])
    ? (int)$_GET['product_id']
    : 0;


/* =========================================================
   PRODUCT ID CHECK
========================================================= */

if ($product_id <= 0) {
?>

    <!DOCTYPE html>
    <html lang="en">

    <head>

        <meta charset="utf-8">

        <meta
            name="viewport"
            content="width=device-width, initial-scale=1">

        <title>Product Images</title>

        <link
            href="assets/vendors/bootstrap/dist/css/bootstrap.min.css"
            rel="stylesheet">

        <link
            href="assets/vendors/font-awesome/css/font-awesome.min.css"
            rel="stylesheet">

        <link
            href="assets/css/custom.min.css"
            rel="stylesheet">

    </head>

    <body class="nav-md">

        <div class="container body">

            <div class="main_container">

                <?php include "sidebar.php"; ?>


                <div class="right_col" role="main">

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
                                                alt="Profile">

                                            <?php
                                            echo htmlspecialchars(
                                                $admin['name']
                                            );
                                            ?>

                                            <span class="fa fa-angle-down"></span>

                                        </a>

                                        <ul class="dropdown-menu dropdown-usermenu pull-right">

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


                    <div class="container-fluid">

                        <div
                            class="alert alert-danger"
                            style="margin-top:30px;">

                            <h4>
                                <i class="fa fa-exclamation-triangle"></i>
                                Product ID Missing
                            </h4>

                            <p>
                                Please open Product Images from the
                                Products page using the Product Images button.
                            </p>

                            <br>

                            <a
                                href="products.php"
                                class="btn btn-primary">

                                <i class="fa fa-arrow-left"></i>

                                Back to Products

                            </a>

                        </div>

                    </div>


                    <footer>

                        <div class="pull-right">

                            Product Management Admin Panel

                        </div>

                        <div class="clearfix"></div>

                    </footer>

                </div>

            </div>

        </div>


        <script
            src="assets/vendors/jquery/dist/jquery.min.js">
        </script>

        <script
            src="assets/vendors/bootstrap/dist/js/bootstrap.min.js">
        </script>

        <script
            src="assets/js/custom.min.js">
        </script>

    </body>

    </html>

<?php
    exit;
}


/* =========================================================
   VARIABLES
========================================================= */

$error = "";
$success = "";


/* =========================================================
   UPLOAD DIRECTORY
========================================================= */

$upload_dir = "../uploads/products/";


if (!is_dir($upload_dir)) {

    mkdir(
        $upload_dir,
        0777,
        true
    );
}


/* =========================================================
   FETCH PRODUCT
========================================================= */

$product_sql = "
    SELECT
        p.product_id,
        p.product_name,
        p.product_code,
        p.stock_quantity,
        p.product_description,
        p.brand_name,
        p.color,
        p.size,
        p.material,
        p.status,

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


try {

    $product_stmt = $conn->prepare($product_sql);

    $product_stmt->execute([
        ':product_id' => $product_id
    ]);

    $product = $product_stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {

    $product = false;

    $error = $e->getMessage();
}


/* =========================================================
   PRODUCT NOT FOUND
========================================================= */

if (!$product) {

?>

    <!DOCTYPE html>
    <html lang="en">

    <head>

        <meta charset="utf-8">

        <meta
            name="viewport"
            content="width=device-width, initial-scale=1">

        <title>Product Not Found</title>

        <link
            href="assets/vendors/bootstrap/dist/css/bootstrap.min.css"
            rel="stylesheet">

        <link
            href="assets/vendors/font-awesome/css/font-awesome.min.css"
            rel="stylesheet">

        <link
            href="assets/css/custom.min.css"
            rel="stylesheet">

    </head>

    <body class="nav-md">

        <div class="container body">

            <div class="main_container">

                <?php include "sidebar.php"; ?>


                <div class="right_col" role="main">

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
                                                alt="Profile">

                                            <?php
                                            echo htmlspecialchars(
                                                $admin['name']
                                            );
                                            ?>

                                            <span class="fa fa-angle-down"></span>

                                        </a>

                                        <ul class="dropdown-menu dropdown-usermenu pull-right">

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


                    <div class="container-fluid">

                        <div
                            class="alert alert-danger"
                            style="margin-top:30px;">

                            <h4>

                                <i class="fa fa-exclamation-triangle"></i>

                                Product Not Found

                            </h4>

                            <p>
                                Product ID:
                                <strong>
                                    <?php echo (int)$product_id; ?>
                                </strong>
                                does not exist.
                            </p>

                            <?php if (!empty($error)) { ?>

                                <hr>

                                <p>
                                    <?php
                                    echo htmlspecialchars($error);
                                    ?>
                                </p>

                            <?php } ?>

                            <br>

                            <a
                                href="products.php"
                                class="btn btn-primary">

                                <i class="fa fa-arrow-left"></i>

                                Back to Products

                            </a>

                        </div>

                    </div>


                    <footer>

                        <div class="pull-right">

                            Product Management Admin Panel

                        </div>

                        <div class="clearfix"></div>

                    </footer>

                </div>

            </div>

        </div>


        <script
            src="assets/vendors/jquery/dist/jquery.min.js">
        </script>

        <script
            src="assets/vendors/bootstrap/dist/js/bootstrap.min.js">
        </script>

        <script
            src="assets/js/custom.min.js">
        </script>

    </body>

    </html>

<?php

    exit;
}


/* =========================================================
   DELETE IMAGE
========================================================= */

if (isset($_GET['delete_image'])) {

    $image_id = (int)$_GET['delete_image'];


    if ($image_id > 0) {

        try {

            /* GET IMAGE */

            $get_image_sql = "
                SELECT
                    image_name
                FROM product_images
                WHERE image_id = :image_id
                  AND product_id = :product_id
                LIMIT 1
            ";

            $get_image_stmt =
                $conn->prepare(
                    $get_image_sql
                );

            $get_image_stmt->execute([

                ':image_id' =>
                $image_id,

                ':product_id' =>
                $product_id

            ]);


            $image_data =
                $get_image_stmt->fetch(
                    PDO::FETCH_ASSOC
                );


            /* DELETE FILE */

            if (
                $image_data &&
                !empty($image_data['image_name'])
            ) {

                $image_name =
                    basename(
                        $image_data['image_name']
                    );

                $image_path =
                    $upload_dir .
                    $image_name;


                if (
                    file_exists(
                        $image_path
                    )
                ) {

                    unlink(
                        $image_path
                    );
                }
            }


            /* DELETE DATABASE RECORD */

            $delete_image_sql = "
                DELETE FROM product_images
                WHERE image_id = :image_id
                  AND product_id = :product_id
            ";

            $delete_image_stmt =
                $conn->prepare(
                    $delete_image_sql
                );

            $delete_image_stmt->execute([

                ':image_id' =>
                $image_id,

                ':product_id' =>
                $product_id

            ]);


            $success =
                "Image deleted successfully.";
        } catch (PDOException $e) {

            $error =
                "Unable to delete image: " .
                $e->getMessage();
        }
    }
}


/* =========================================================
   UPLOAD MULTIPLE IMAGES
========================================================= */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_FILES['product_images'])
) {

    $allowed_extensions = [
        'jpg',
        'jpeg',
        'png',
        'gif',
        'webp'
    ];

    $max_file_size =
        5 * 1024 * 1024;


    $uploaded_count = 0;


    $file_names =
        $_FILES['product_images']['name'];

    $file_tmp_names =
        $_FILES['product_images']['tmp_name'];

    $file_sizes =
        $_FILES['product_images']['size'];

    $file_errors =
        $_FILES['product_images']['error'];


    foreach (
        $file_names as $key => $original_name
    ) {

        if (
            $file_errors[$key]
            !== UPLOAD_ERR_OK
        ) {

            continue;
        }


        $tmp_name =
            $file_tmp_names[$key];

        $file_size =
            (int)$file_sizes[$key];


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
                $allowed_extensions,
                true
            )
        ) {

            $error =
                "Only JPG, JPEG, PNG, GIF and WEBP images are allowed.";

            continue;
        }


        /* CHECK SIZE */

        if (
            $file_size >
            $max_file_size
        ) {

            $error =
                "Image size must be less than 5 MB.";

            continue;
        }


        /* UNIQUE FILE NAME */

        $new_image_name =
            "product_" .
            $product_id .
            "_" .
            time() .
            "_" .
            uniqid() .
            "." .
            $extension;


        $destination =
            $upload_dir .
            $new_image_name;


        /* MOVE FILE */

        if (
            move_uploaded_file(
                $tmp_name,
                $destination
            )
        ) {

            try {

                $insert_image_sql = "
                    INSERT INTO product_images
                    (
                        product_id,
                        image_name,
                        created_at
                    )
                    VALUES
                    (
                        :product_id,
                        :image_name,
                        CURRENT_TIMESTAMP
                    )
                ";


                $insert_image_stmt =
                    $conn->prepare(
                        $insert_image_sql
                    );


                $insert_image_stmt->execute([

                    ':product_id' =>
                    $product_id,

                    ':image_name' =>
                    $new_image_name

                ]);


                $uploaded_count++;
            } catch (PDOException $e) {

                if (
                    file_exists(
                        $destination
                    )
                ) {

                    unlink(
                        $destination
                    );
                }


                $error =
                    "Database error while saving image: " .
                    $e->getMessage();
            }
        }
    }


    if ($uploaded_count > 0) {

        $success =
            $uploaded_count .
            " product image(s) uploaded successfully.";
    }
}


/* =========================================================
   FETCH PRODUCT IMAGES
========================================================= */

$images_sql = "
    SELECT
        image_id,
        product_id,
        image_name,
        created_at
    FROM product_images
    WHERE product_id = :product_id
    ORDER BY image_id DESC
";


$images_stmt =
    $conn->prepare(
        $images_sql
    );


$images_stmt->execute([

    ':product_id' =>
    $product_id

]);


$images =
    $images_stmt->fetchAll(
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

    <title>
        Product Images -
        <?php
        echo htmlspecialchars(
            $product['product_name']
        );
        ?>
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


    <!-- Custom Theme -->

    <link
        href="assets/css/custom.min.css"
        rel="stylesheet">


    <style>
        .product-info-table td {
            vertical-align: middle;
        }


        .product-info-table td:first-child {
            width: 180px;
            font-weight: bold;
        }


        .upload-box {
            border: 2px dashed #ddd;
            padding: 30px;
            text-align: center;
            background: #fafafa;
            margin-bottom: 20px;
        }


        .upload-box i {
            font-size: 40px;
            color: #999;
            margin-bottom: 15px;
        }


        .image-card {
            border: 1px solid #ddd;
            border-radius: 5px;
            background: #fff;
            padding: 10px;
            margin-bottom: 20px;
        }


        .image-box {
            width: 100%;
            height: 190px;
            overflow: hidden;
            background: #f5f5f5;
            border-radius: 4px;

            display: flex;

            align-items: center;

            justify-content: center;
        }


        .image-box img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }


        .image-details {
            padding-top: 10px;
        }


        .image-details p {
            margin-bottom: 8px;
        }


        .empty-gallery {
            text-align: center;
            padding: 50px 20px;
        }


        .empty-gallery i {
            font-size: 50px;
            color: #ccc;
            margin-bottom: 15px;
        }


        .product-name-heading {
            margin-top: 0;
        }


        .status-active {
            color: #26B99A;
            font-weight: bold;
        }


        .status-inactive {
            color: #d9534f;
            font-weight: bold;
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


                            <ul
                                class="nav navbar-nav navbar-right">


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


                                        <span
                                            class="fa fa-angle-down">
                                        </span>


                                    </a>


                                    <ul
                                        class="dropdown-menu dropdown-usermenu pull-right">


                                        <li>

                                            <a
                                                href="javascript:;">

                                                <i
                                                    class="fa fa-user pull-right">
                                                </i>

                                                Profile

                                            </a>

                                        </li>


                                        <li>

                                            <a
                                                href="javascript:;">

                                                <i
                                                    class="fa fa-cog pull-right">
                                                </i>

                                                Settings

                                            </a>

                                        </li>


                                        <li>

                                            <a
                                                href="javascript:;">

                                                <i
                                                    class="fa fa-question-circle pull-right">
                                                </i>

                                                Help

                                            </a>

                                        </li>


                                        <li>

                                            <a
                                                href="../logout.php">

                                                <i
                                                    class="fa fa-sign-out pull-right">
                                                </i>

                                                Log Out

                                            </a>

                                        </li>


                                    </ul>

                                </li>


                                <!-- MESSAGE -->

                                <li
                                    role="presentation"
                                    class="dropdown">


                                    <a
                                        href="javascript:;"
                                        class="dropdown-toggle info-number"
                                        data-toggle="dropdown"
                                        aria-expanded="false">


                                        <i
                                            class="fa fa-envelope-o">
                                        </i>


                                        <span
                                            class="badge bg-green">

                                            6

                                        </span>


                                    </a>


                                    <ul
                                        class="dropdown-menu list-unstyled msg_list"
                                        role="menu">


                                        <li>

                                            <a>


                                                <span class="image">

                                                    <img
                                                        src="assets/images/img.jpg"
                                                        alt="Profile">

                                                </span>


                                                <span>

                                                    <span>

                                                        Admin

                                                    </span>


                                                    <span class="time">

                                                        3 mins ago

                                                    </span>

                                                </span>


                                                <span class="message">

                                                    Welcome to admin dashboard.

                                                </span>


                                            </a>

                                        </li>


                                        <li>

                                            <div class="text-center">

                                                <a>

                                                    <strong>

                                                        See All Alerts

                                                    </strong>


                                                    <i
                                                        class="fa fa-angle-right">
                                                    </i>

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
                 PAGE CONTENT
            ================================================== -->

                <div class="container-fluid">


                    <!-- PAGE HEADER -->

                    <div
                        class="row"
                        style="margin-bottom:20px;">


                        <div class="col-md-8">


                            <h3 class="product-name-heading">

                                <i class="fa fa-picture-o"></i>

                                Product Images

                            </h3>


                            <p class="text-muted">

                                Manage images for:

                                <strong>

                                    <?php

                                    echo htmlspecialchars(
                                        $product['product_name']
                                    );

                                    ?>

                                </strong>

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


                    <!-- =================================================
                     ALERTS
                ================================================== -->


                    <?php if (!empty($success)) { ?>

                        <div
                            class="alert alert-success alert-dismissible">


                            <button
                                type="button"
                                class="close"
                                data-dismiss="alert">

                                &times;

                            </button>


                            <i class="fa fa-check-circle"></i>


                            <?php

                            echo htmlspecialchars(
                                $success
                            );

                            ?>


                        </div>

                    <?php } ?>


                    <?php if (!empty($error)) { ?>

                        <div
                            class="alert alert-danger alert-dismissible">


                            <button
                                type="button"
                                class="close"
                                data-dismiss="alert">

                                &times;

                            </button>


                            <i class="fa fa-exclamation-circle"></i>


                            <?php

                            echo htmlspecialchars(
                                $error
                            );

                            ?>


                        </div>

                    <?php } ?>


                    <!-- =================================================
                     PRODUCT INFORMATION
                ================================================== -->

                    <div class="x_panel">


                        <div class="x_title">


                            <h2>

                                Product Information

                            </h2>


                            <div class="clearfix"></div>


                        </div>


                        <div class="x_content">


                            <div class="table-responsive">


                                <table
                                    class="table table-bordered product-info-table">


                                    <tbody>


                                        <tr>

                                            <td>
                                                Product ID
                                            </td>

                                            <td>

                                                <?php

                                                echo htmlspecialchars(
                                                    $product['product_id']
                                                );

                                                ?>

                                            </td>

                                        </tr>


                                        <tr>

                                            <td>
                                                Product Code
                                            </td>

                                            <td>

                                                <?php

                                                echo htmlspecialchars(
                                                    $product['product_code']
                                                        ?: 'N/A'
                                                );

                                                ?>

                                            </td>

                                        </tr>


                                        <tr>

                                            <td>
                                                Product Name
                                            </td>

                                            <td>

                                                <strong>

                                                    <?php

                                                    echo htmlspecialchars(
                                                        $product['product_name']
                                                    );

                                                    ?>

                                                </strong>

                                            </td>

                                        </tr>


                                        <tr>

                                            <td>
                                                Category
                                            </td>

                                            <td>

                                                <?php

                                                echo htmlspecialchars(
                                                    $product['category_name']
                                                        ?: 'N/A'
                                                );

                                                ?>

                                            </td>

                                        </tr>


                                        <tr>

                                            <td>
                                                Subcategory
                                            </td>

                                            <td>

                                                <?php

                                                echo htmlspecialchars(
                                                    $product['subcategory_name']
                                                        ?: 'N/A'
                                                );

                                                ?>

                                            </td>

                                        </tr>


                                        <tr>

                                            <td>
                                                Brand
                                            </td>

                                            <td>

                                                <?php

                                                echo htmlspecialchars(
                                                    $product['brand_name']
                                                        ?: 'N/A'
                                                );

                                                ?>

                                            </td>

                                        </tr>


                                        <tr>

                                            <td>
                                                Color
                                            </td>

                                            <td>

                                                <?php

                                                echo htmlspecialchars(
                                                    $product['color']
                                                        ?: 'N/A'
                                                );

                                                ?>

                                            </td>

                                        </tr>


                                        <tr>

                                            <td>
                                                Size
                                            </td>

                                            <td>

                                                <?php

                                                echo htmlspecialchars(
                                                    $product['size']
                                                        ?: 'N/A'
                                                );

                                                ?>

                                            </td>

                                        </tr>


                                        <tr>

                                            <td>
                                                Material
                                            </td>

                                            <td>

                                                <?php

                                                echo htmlspecialchars(
                                                    $product['material']
                                                        ?: 'N/A'
                                                );

                                                ?>

                                            </td>

                                        </tr>


                                        <tr>

                                            <td>
                                                Stock
                                            </td>

                                            <td>

                                                <?php

                                                echo htmlspecialchars(
                                                    $product['stock_quantity']
                                                );

                                                ?>

                                            </td>

                                        </tr>


                                        <tr>

                                            <td>
                                                Status
                                            </td>

                                            <td>

                                                <?php

                                                if (
                                                    (int)$product['status'] === 1
                                                ) {

                                                ?>

                                                    <span
                                                        class="status-active">

                                                        Active

                                                    </span>

                                                <?php

                                                } else {

                                                ?>

                                                    <span
                                                        class="status-inactive">

                                                        Inactive

                                                    </span>

                                                <?php

                                                }

                                                ?>

                                            </td>

                                        </tr>


                                    </tbody>


                                </table>


                            </div>


                        </div>


                    </div>


                    <!-- =================================================
                     UPLOAD IMAGES
                ================================================== -->

                    <div class="x_panel">


                        <div class="x_title">


                            <h2>

                                Upload Product Images

                            </h2>


                            <div class="clearfix"></div>


                        </div>


                        <div class="x_content">


                            <form
                                method="POST"
                                enctype="multipart/form-data">


                                <div class="upload-box">


                                    <i
                                        class="fa fa-cloud-upload">
                                    </i>


                                    <h4>

                                        Select Product Images

                                    </h4>


                                    <p class="text-muted">

                                        You can select multiple images.

                                        <br>

                                        JPG, JPEG, PNG, GIF and WEBP

                                        <br>

                                        Maximum size: 5 MB per image.

                                    </p>


                                    <br>


                                    <input
                                        type="file"
                                        name="product_images[]"
                                        class="form-control"
                                        multiple
                                        accept=".jpg,.jpeg,.png,.gif,.webp"
                                        required>


                                    <br>


                                    <button
                                        type="submit"
                                        class="btn btn-primary">


                                        <i class="fa fa-upload"></i>

                                        Upload Images


                                    </button>


                                </div>


                            </form>


                        </div>


                    </div>


                    <!-- =================================================
                     PRODUCT IMAGE GALLERY
                ================================================== -->

                    <div class="x_panel">


                        <div class="x_title">


                            <h2>

                                Product Image Gallery

                                <small>

                                    <?php

                                    echo count($images);

                                    ?>

                                    Images

                                </small>

                            </h2>


                            <div class="clearfix"></div>


                        </div>


                        <div class="x_content">


                            <?php if (!empty($images)) { ?>


                                <div class="row">


                                    <?php foreach (
                                        $images as $image
                                    ) { ?>


                                        <div
                                            class="col-md-3 col-sm-4 col-xs-12">


                                            <div
                                                class="image-card">


                                                <!-- IMAGE -->

                                                <div
                                                    class="image-box">


                                                    <img
                                                        src="../uploads/products/<?php
                                                                                    echo htmlspecialchars(
                                                                                        basename(
                                                                                            $image['image_name']
                                                                                        )
                                                                                    );
                                                                                    ?>"
                                                        alt="Product Image">


                                                </div>


                                                <!-- IMAGE DETAILS -->

                                                <div
                                                    class="image-details">


                                                    <p>

                                                        <strong>
                                                            Image ID:
                                                        </strong>

                                                        <?php

                                                        echo htmlspecialchars(
                                                            $image['image_id']
                                                        );

                                                        ?>

                                                    </p>


                                                    <p
                                                        class="text-muted">

                                                        <?php

                                                        if (
                                                            !empty($image['created_at'])
                                                        ) {

                                                            echo date(
                                                                "d M Y, h:i A",
                                                                strtotime(
                                                                    $image['created_at']
                                                                )
                                                            );
                                                        } else {

                                                            echo "N/A";
                                                        }

                                                        ?>

                                                    </p>


                                                    <!-- DELETE -->

                                                    <a
                                                        href="product_images.php?product_id=<?php
                                                                                            echo (int)$product_id;
                                                                                            ?>&delete_image=<?php
                                                                    echo (int)$image['image_id'];
                                                                    ?>"
                                                        class="btn btn-danger btn-sm btn-block"
                                                        onclick="return confirm('Are you sure you want to delete this image?');">


                                                        <i
                                                            class="fa fa-trash">
                                                        </i>

                                                        Delete Image


                                                    </a>


                                                </div>


                                            </div>


                                        </div>


                                    <?php } ?>


                                </div>


                            <?php } else { ?>


                                <div
                                    class="empty-gallery">


                                    <i
                                        class="fa fa-picture-o">
                                    </i>


                                    <h4>

                                        No Product Images Found

                                    </h4>


                                    <p class="text-muted">

                                        Upload images using the form above.

                                    </p>


                                </div>


                            <?php } ?>


                        </div>


                    </div>


                </div>


                <!-- =================================================
                 FOOTER
            ================================================== -->

                <footer>


                    <div class="pull-right">

                        Product Management Admin Panel

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