<?php
include "../config/database.php";

$subcategory_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($subcategory_id <= 0) {
    header("Location: product_subcategory.php");
    exit;
}

/* GET SUBCATEGORY DATA */
$sql = "SELECT * FROM product_subcategory WHERE subcategory_id = $subcategory_id";
$result = mysqli_query($conn, $sql);

if (!$result || mysqli_num_rows($result) == 0) {
    header("Location: product_subcategory.php");
    exit;
}

$subcategory = mysqli_fetch_assoc($result);

/* GET CATEGORIES */
$category_sql = "SELECT category_id, category_name
                 FROM product_category
                 ORDER BY category_name ASC";

$category_result = mysqli_query($conn, $category_sql);

$error_message = "";

/* UPDATE SUBCATEGORY */
if (isset($_POST['update_subcategory'])) {

    $subcategory_id = intval($_POST['subcategory_id']);
    $category_id = intval($_POST['category_id']);

    $subcategory_name = mysqli_real_escape_string(
        $conn,
        trim($_POST['subcategory_name'])
    );

    $subcategory_description = mysqli_real_escape_string(
        $conn,
        trim($_POST['subcategory_description'])
    );

    $status = intval($_POST['status']);

    /* VALIDATION */

    if ($category_id <= 0) {

        $error_message = "Please select category.";
    } elseif ($subcategory_name == "") {

        $error_message = "Subcategory name is required.";
    } else {

        /* CHECK DUPLICATE SUBCATEGORY */

        $check_sql = "SELECT subcategory_id
                      FROM product_subcategory
                      WHERE subcategory_name = '$subcategory_name'
                      AND subcategory_id != $subcategory_id";

        $check_result = mysqli_query($conn, $check_sql);

        if ($check_result && mysqli_num_rows($check_result) > 0) {

            $error_message = "This subcategory already exists.";
        } else {

            /* OLD IMAGE */

            $subcategory_image = $subcategory['subcategory_image'];

            /* NEW IMAGE */

            if (
                isset($_FILES['subcategory_image']) &&
                $_FILES['subcategory_image']['error'] == 0
            ) {

                $image_name = $_FILES['subcategory_image']['name'];
                $image_tmp = $_FILES['subcategory_image']['tmp_name'];

                $extension = strtolower(
                    pathinfo($image_name, PATHINFO_EXTENSION)
                );

                $allowed_extensions = array(
                    "jpg",
                    "jpeg",
                    "png",
                    "webp"
                );

                if (!in_array($extension, $allowed_extensions)) {

                    $error_message =
                        "Only JPG, JPEG, PNG and WEBP images are allowed.";
                } else {

                    $upload_folder = "../../uploads/subcategories/";

                    if (!is_dir($upload_folder)) {

                        mkdir(
                            $upload_folder,
                            0777,
                            true
                        );
                    }

                    $new_image_name =
                        time() . "_" .
                        uniqid() . "." .
                        $extension;

                    if (
                        move_uploaded_file(
                            $image_tmp,
                            $upload_folder . $new_image_name
                        )
                    ) {

                        /* DELETE OLD IMAGE */

                        if (
                            !empty($subcategory_image) &&
                            file_exists(
                                $upload_folder . $subcategory_image
                            )
                        ) {

                            unlink(
                                $upload_folder . $subcategory_image
                            );
                        }

                        $subcategory_image = $new_image_name;
                    } else {

                        $error_message = "Image upload failed.";
                    }
                }
            }

            /* UPDATE */

            if ($error_message == "") {

                $update_sql = "UPDATE product_subcategory SET
                    category_id = $category_id,
                    subcategory_name = '$subcategory_name',
                    subcategory_description = '$subcategory_description',
                    subcategory_image = '$subcategory_image',
                    status = $status
                    WHERE subcategory_id = $subcategory_id";

                if (mysqli_query($conn, $update_sql)) {

                    header(
                        "Location: product_subcategory.php?updated=1"
                    );

                    exit;
                } else {

                    $error_message = mysqli_error($conn);
                }
            }
        }
    }

    /* KEEP ENTERED DATA IF ERROR */

    if ($error_message != "") {

        $subcategory['category_id'] =
            $_POST['category_id'] ?? '';

        $subcategory['subcategory_name'] =
            $_POST['subcategory_name'] ?? '';

        $subcategory['subcategory_description'] =
            $_POST['subcategory_description'] ?? '';

        $subcategory['status'] =
            $_POST['status'] ?? 1;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="utf-8">

    <meta http-equiv="X-UA-Compatible"
        content="IE=edge">

    <meta name="viewport"
        content="width=device-width, initial-scale=1">

    <title>Edit Product Subcategory</title>

    <link href="assets/vendors/bootstrap/dist/css/bootstrap.min.css"
        rel="stylesheet">

    <link href="assets/vendors/font-awesome/css/font-awesome.min.css"
        rel="stylesheet">

    <link href="assets/vendors/nprogress/nprogress.css"
        rel="stylesheet">

    <link href="assets/vendors/iCheck/skins/flat/green.css"
        rel="stylesheet">

    <link href="assets/vendors/pnotify/dist/pnotify.css"
        rel="stylesheet">

    <link href="assets/vendors/pnotify/dist/pnotify.buttons.css"
        rel="stylesheet">

    <link href="assets/vendors/pnotify/dist/pnotify.nonblock.css"
        rel="stylesheet">

    <link href="assets/css/custom.min.css"
        rel="stylesheet">

    <link href="assets/css/custom-popup.css"
        rel="stylesheet">

</head>

<body class="nav-md">

    <div class="container body">

        <div class="main_container">

            <?php include 'sidebar.php'; ?>

            <div class="right_col" role="main">

                <!-- TOP NAVIGATION -->

                <div class="top_nav">

                    <div class="nav_menu">

                        <nav>

                            <div class="nav toggle">

                                <a id="menu_toggle">

                                    <i class="fa fa-bars"></i>

                                </a>

                            </div>

                        </nav>

                    </div>

                </div>

                <!-- PAGE CONTENT -->

                <div class="container-fluid py-4">

                    <!-- HEADER -->

                    <div class="row">

                        <div class="col-md-12">

                            <div class="page-title">

                                <div class="title_left">

                                    <h3>
                                        Edit Product Subcategory
                                    </h3>

                                </div>

                                <div class="title_right">

                                    <a href="product_subcategory.php"
                                        class="btn btn-secondary">

                                        <i class="fa fa-arrow-left"></i>

                                        Back to Subcategories

                                    </a>

                                </div>

                            </div>

                        </div>

                    </div>

                    <!-- ERROR -->

                    <?php if ($error_message != "") { ?>

                        <div class="alert alert-danger">

                            <i class="fa fa-times-circle"></i>

                            <?php
                            echo htmlspecialchars($error_message);
                            ?>

                        </div>

                    <?php } ?>

                    <!-- FORM -->

                    <div class="row">

                        <div class="col-md-12">

                            <div class="x_panel">

                                <div class="x_title">

                                    <h2>
                                        Subcategory Information
                                    </h2>

                                    <div class="clearfix"></div>

                                </div>

                                <div class="x_content">

                                    <form method="POST"
                                        enctype="multipart/form-data">

                                        <input
                                            type="hidden"
                                            name="subcategory_id"
                                            value="<?php
                                                    echo $subcategory['subcategory_id'];
                                                    ?>">

                                        <div class="row">

                                            <!-- CATEGORY -->

                                            <div class="col-md-6 mb-3">

                                                <label class="form-label">

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

                                                    if ($category_result) {

                                                        while (
                                                            $category =
                                                            mysqli_fetch_assoc(
                                                                $category_result
                                                            )
                                                        ) {

                                                            $selected = "";

                                                            if (
                                                                $category['category_id']
                                                                ==
                                                                $subcategory['category_id']
                                                            ) {

                                                                $selected = "selected";
                                                            }

                                                    ?>

                                                            <option
                                                                value="<?php
                                                                        echo $category['category_id'];
                                                                        ?>"
                                                                <?php echo $selected; ?>>

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

                                            <!-- STATUS -->

                                            <div class="col-md-6 mb-3">

                                                <label class="form-label">

                                                    Status

                                                </label>

                                                <select
                                                    name="status"
                                                    class="form-control">

                                                    <option
                                                        value="1"
                                                        <?php
                                                        echo (
                                                            $subcategory['status'] == 1
                                                        )
                                                            ? "selected"
                                                            : "";
                                                        ?>>

                                                        Active

                                                    </option>

                                                    <option
                                                        value="0"
                                                        <?php
                                                        echo (
                                                            $subcategory['status'] == 0
                                                        )
                                                            ? "selected"
                                                            : "";
                                                        ?>>

                                                        Inactive

                                                    </option>

                                                </select>

                                            </div>

                                            <!-- SUBCATEGORY NAME -->

                                            <div class="col-md-6 mb-3">

                                                <label class="form-label">

                                                    Subcategory Name

                                                    <span class="text-danger">
                                                        *
                                                    </span>

                                                </label>

                                                <input
                                                    type="text"
                                                    name="subcategory_name"
                                                    class="form-control"
                                                    value="<?php
                                                            echo htmlspecialchars(
                                                                $subcategory['subcategory_name']
                                                            );
                                                            ?>"
                                                    placeholder="Enter subcategory name"
                                                    required>

                                            </div>

                                            <!-- IMAGE -->

                                            <div class="col-md-6 mb-3">

                                                <label class="form-label">

                                                    Subcategory Image

                                                </label>

                                                <input
                                                    type="file"
                                                    name="subcategory_image"
                                                    class="form-control"
                                                    accept=".jpg,.jpeg,.png,.webp">

                                                <small class="text-muted">

                                                    Allowed:
                                                    JPG, JPEG, PNG, WEBP

                                                </small>

                                                <?php
                                                if (
                                                    !empty($subcategory['subcategory_image'])
                                                ) {
                                                ?>

                                                    <div class="mt-3">

                                                        <img
                                                            src="../../uploads/subcategories/<?php
                                                                                                echo htmlspecialchars(
                                                                                                    $subcategory['subcategory_image']
                                                                                                );
                                                                                                ?>"
                                                            width="100"
                                                            height="100"
                                                            style="object-fit:cover;border-radius:8px;">

                                                    </div>

                                                <?php
                                                }
                                                ?>

                                            </div>

                                            <!-- DESCRIPTION -->

                                            <div class="col-md-12 mb-3">

                                                <label class="form-label">

                                                    Subcategory Description

                                                </label>

                                                <textarea
                                                    name="subcategory_description"
                                                    class="form-control"
                                                    rows="5"
                                                    placeholder="Enter subcategory description"><?php
                                                                                                echo htmlspecialchars(
                                                                                                    $subcategory['subcategory_description']
                                                                                                );
                                                                                                ?></textarea>

                                            </div>

                                        </div>

                                        <!-- BUTTONS -->

                                        <div class="border-top pt-4 mt-3">

                                            <a
                                                href="product_subcategory.php"
                                                class="btn btn-default">

                                                <i class="fa fa-times"></i>

                                                Cancel

                                            </a>

                                            <button
                                                type="submit"
                                                name="update_subcategory"
                                                value="1"
                                                class="btn btn-primary">

                                                <i class="fa fa-save"></i>

                                                Update Subcategory

                                            </button>

                                        </div>

                                    </form>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>

                <!-- FOOTER -->

                <footer>

                    <div class="pull-right">

                        Gentelella Admin Template

                    </div>

                    <div class="clearfix"></div>

                </footer>

            </div>

        </div>

    </div>

    <script src="assets/vendors/jquery/dist/jquery.min.js"></script>

    <script src="assets/vendors/bootstrap/dist/js/bootstrap.min.js"></script>

    <script src="assets/vendors/nprogress/nprogress.js"></script>

    <script src="assets/vendors/pnotify/dist/pnotify.js"></script>

    <script src="assets/vendors/pnotify/dist/pnotify.buttons.js"></script>

    <script src="assets/vendors/pnotify/dist/pnotify.nonblock.js"></script>

    <script src="assets/js/custom.min.js"></script>

    <script src="assets/js/custom-popup.js"></script>

</body>

</html>