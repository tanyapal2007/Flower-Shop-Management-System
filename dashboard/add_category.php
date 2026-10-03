<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <!-- Meta, title, CSS, favicons, etc. -->
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Gentelella Alela! | </title>

    <!-- Bootstrap -->
    <link href="assets/vendors/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="assets/vendors/font-awesome/css/font-awesome.min.css" rel="stylesheet">
    <!-- NProgress -->
    <link href="assets/vendors/nprogress/nprogress.css" rel="stylesheet">
    <!-- iCheck -->
    <link href="assets/vendors/iCheck/skins/flat/green.css" rel="stylesheet">
    <!-- bootstrap-progressbar -->
    <link href="assets/vendors/bootstrap-progressbar/css/bootstrap-progressbar-3.3.4.min.css" rel="stylesheet">
    <!-- PNotify -->
    <link href="assets/vendors/pnotify/dist/pnotify.css" rel="stylesheet">
    <link href="assets/vendors/pnotify/dist/pnotify.buttons.css" rel="stylesheet">
    <link href="assets/vendors/pnotify/dist/pnotify.nonblock.css" rel="stylesheet">

    <!-- Custom Theme Style -->
    <link href="assets/css/custom.min.css" rel="stylesheet">
    <link href="assets/css/custom-popup.css" rel="stylesheet">

</head>

<body class="nav-md">
    <div class="container body">
        <div class="main_container">
            <?php include 'sidebar.php'; ?>
        </div>
        <div class="right_col" role="main">

            <!-- top navigation -->
            <div class="top_nav">
                <div class="nav_menu">
                    <nav>
                        <div class="nav toggle">
                            <a id="menu_toggle"><i class="fa fa-bars"></i></a>
                        </div>

                        <ul class="nav navbar-nav navbar-right">
                            <li class="">
                                <a href="javascript:;" class="user-profile dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                                    <img src="assets/images/img.jpg" alt="">John Doe
                                    <span class=" fa fa-angle-down"></span>
                                </a>
                                <ul class="dropdown-menu dropdown-usermenu pull-right">
                                    <li><a href="javascript:;"> Profile</a></li>
                                    <li>
                                        <a href="javascript:;">
                                            <span class="badge bg-red pull-right">50%</span>
                                            <span>Settings</span>
                                        </a>
                                    </li>
                                    <li><a href="javascript:;">Help</a></li>
                                    <li><a href="login.php"><i class="fa fa-sign-out pull-right"></i> Log Out</a></li>
                                </ul>
                            </li>

                            <li role="presentation" class="dropdown">
                                <a href="javascript:;" class="dropdown-toggle info-number" data-toggle="dropdown" aria-expanded="false">
                                    <i class="fa fa-envelope-o"></i>
                                    <span class="badge bg-green">6</span>
                                </a>
                                <ul id="menu1" class="dropdown-menu list-unstyled msg_list" role="menu">
                                    <li>
                                        <a>
                                            <span class="image"><img src="assets/images/img.jpg" alt="Profile Image" /></span>
                                            <span>
                                                <span>John Smith</span>
                                                <span class="time">3 mins ago</span>
                                            </span>
                                            <span class="message">
                                                Film festivals used to be do-or-die moments for movie makers. They were where...
                                            </span>
                                        </a>
                                    </li>
                                    <li>
                                        <a>
                                            <span class="image"><img src="assets/images/img.jpg" alt="Profile Image" /></span>
                                            <span>
                                                <span>John Smith</span>
                                                <span class="time">3 mins ago</span>
                                            </span>
                                            <span class="message">
                                                Film festivals used to be do-or-die moments for movie makers. They were where...
                                            </span>
                                        </a>
                                    </li>
                                    <li>
                                        <a>
                                            <span class="image"><img src="assets/images/img.jpg" alt="Profile Image" /></span>
                                            <span>
                                                <span>John Smith</span>
                                                <span class="time">3 mins ago</span>
                                            </span>
                                            <span class="message">
                                                Film festivals used to be do-or-die moments for movie makers. They were where...
                                            </span>
                                        </a>
                                    </li>
                                    <li>
                                        <a>
                                            <span class="image"><img src="assets/images/img.jpg" alt="Profile Image" /></span>
                                            <span>
                                                <span>John Smith</span>
                                                <span class="time">3 mins ago</span>
                                            </span>
                                            <span class="message">
                                                Film festivals used to be do-or-die moments for movie makers. They were where...
                                            </span>
                                        </a>
                                    </li>
                                    <li>
                                        <div class="text-center">
                                            <a>
                                                <strong>See All Alerts</strong>
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
            <!-- /top navigation -->

            <?php
            include "../config/database.php";

            $popup_message = "";
            $popup_type = "";
            $popup_title = "";
            $popup_icon = "";
            $redirect_page = "category-management.php";

            if (!$conn) {
                die("Database Connection Failed: " . mysqli_connect_error());
            }

            if (isset($_POST['add_category'])) {

                $category_name = trim($_POST['category_name']);
                $category_description = trim($_POST['category_description']);
                $status = isset($_POST['status']) ? intval($_POST['status']) : 1;

                if ($category_name == "") {
                    $popup_message = "Category name is required.";
                    $popup_type = "error";
                    $popup_title = "Validation Error";
                    $popup_icon = "fa fa-times";
                } else {

                    $category_name = mysqli_real_escape_string($conn, $category_name);
                    $category_description = mysqli_real_escape_string($conn, $category_description);

                    $category_image = "";

                    if (isset($_FILES['category_image']) && $_FILES['category_image']['error'] != 4) {

                        if ($_FILES['category_image']['error'] != 0) {

                            $popup_message = "Image upload error. Error Code: " . $_FILES['category_image']['error'];
                            $popup_type = "error";
                            $popup_title = "Image Error";
                            $popup_icon = "fa fa-times";
                        } else {

                            $image_name = $_FILES['category_image']['name'];
                            $image_tmp = $_FILES['category_image']['tmp_name'];

                            $extension = strtolower(pathinfo($image_name, PATHINFO_EXTENSION));

                            $allowed_extensions = array("jpg", "jpeg", "png", "webp");

                            if (!in_array($extension, $allowed_extensions)) {

                                $popup_message = "Only JPG, JPEG, PNG and WEBP images are allowed.";
                                $popup_type = "error";
                                $popup_title = "Invalid Image";
                                $popup_icon = "fa fa-times";
                            } else {

                                $upload_folder = "../../uploads/categories/";

                                if (!is_dir($upload_folder)) {
                                    mkdir($upload_folder, 0777, true);
                                }

                                $category_image = time() . "_" . uniqid() . "." . $extension;

                                if (!move_uploaded_file($image_tmp, $upload_folder . $category_image)) {

                                    $popup_message = "Image could not be uploaded.";
                                    $popup_type = "error";
                                    $popup_title = "Upload Failed";
                                    $popup_icon = "fa fa-times";

                                    $category_image = "";
                                }
                            }
                        }
                    }

                    if ($popup_type == "") {

                        $sql = "INSERT INTO product_category 
                    (category_name, category_description, category_image, status)
                    VALUES 
                    ('$category_name', '$category_description', '$category_image', '$status')";

                        $result = mysqli_query($conn, $sql);

                        if ($result) {

                            $popup_message = "Category added successfully.";
                            $popup_type = "success";
                            $popup_title = "Category Added";
                            $popup_icon = "fa fa-check";
                            $redirect_page = "category-management.php";
                        } else {

                            $popup_message = "Database Error: " . mysqli_error($conn);
                            $popup_type = "error";
                            $popup_title = "Category Add Failed";
                            $popup_icon = "fa fa-times";
                        }
                    }
                }
            }
            ?>


            <!-- =========================================================
     ADD CATEGORY PAGE
========================================================= -->

            <div class="container-fluid py-4">


                <!-- =====================================================
         PAGE HEADER
    ====================================================== -->

                <div class="d-flex justify-content-between align-items-center mb-4">


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



                <!-- =====================================================
         FORM CARD
    ====================================================== -->

                <div class="card border-0 shadow-sm">


                    <!-- CARD HEADER -->

                    <div class="card-header bg-white py-3">


                        <h5 class="fw-bold mb-0">

                            Category Information

                        </h5>


                    </div>



                    <!-- CARD BODY -->

                    <div class="card-body">


                        <form method="POST" enctype="multipart/form-data">
                            <div class="row g-4">
                                <!-- =================================================
                                 CATEGORY NAME
                                ================================================== -->
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">
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
                                <!-- =================================================
                                     STATUS
                                    ================================================== -->
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">
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
                                <!-- =================================================
                                     CATEGORY IMAGE
                                     ================================================== -->
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">
                                        Category Image
                                    </label>
                                    <input
                                        type="file"
                                        name="category_image"
                                        class="form-control"
                                        accept=".jpg,.jpeg,.png,.webp">
                                    <small class="text-muted">
                                        Allowed: JPG, JPEG, PNG, WEBP
                                    </small>
                                </div>
                                <!-- =================================================
                                     DESCRIPTION
                                    ================================================== -->
                                <div class="col-md-12">
                                    <label class="form-label fw-semibold">
                                        Category Description
                                    </label>
                                    <textarea
                                        name="category_description"
                                        class="form-control"
                                        rows="5"
                                        placeholder="Enter category description"></textarea>
                                </div>
                            </div>
                            <!-- =====================================================
                            BUTTONS
                            ====================================================== -->
                            <div class="border-top mt-4 pt-4">
                                <a
                                    href="category-management.php"
                                    class="btn btn-secondary me-2">
                                    <i class="fa fa-times me-1"></i>
                                    Cancel
                                </a>
                                <button type="submit" name="add_category" value="1" class="btn btn-primary">
                                    <i class="fa fa-save me-1"></i>
                                    Save Category
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <!-- footer content -->
            <footer>
                <div class="pull-right">
                    Gentelella - Bootstrap Admin Template by <a href="https://colorlib.com">Colorlib</a>
                </div>
                <div class="clearfix"></div>
            </footer>
            <!-- /footer content -->
        </div>
    </div>
    <div id="custom_notifications" class="custom-notifications dsp_none">
        <ul class="list-unstyled notifications clearfix" data-tabbed_notifications="notif-group">
        </ul>
        <div class="clearfix"></div>
        <div id="notif-group" class="tabbed_notifications"></div>
    </div>


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
    <!-- jQuery -->
    <script src="assets/vendors/jquery/dist/jquery.min.js"></script>
    <!-- Bootstrap -->
    <script src="assets/vendors/bootstrap/dist/js/bootstrap.min.js"></script>
    <!-- FastClick -->
    <script src="assets/vendors/fastclick/lib/fastclick.js"></script>
    <!-- NProgress -->
    <script src="assets/vendors/nprogress/nprogress.js"></script>
    <!-- bootstrap-progressbar -->
    <script src="assets/vendors/bootstrap-progressbar/bootstrap-progressbar.min.js"></script>
    <!-- iCheck -->
    <script src="assets/vendors/iCheck/icheck.min.js"></script>
    <!-- PNotify -->
    <script src="assets/vendors/pnotify/dist/pnotify.js"></script>
    <script src="assets/vendors/pnotify/dist/pnotify.buttons.js"></script>
    <script src="assets/vendors/pnotify/dist/pnotify.nonblock.js"></script>



    <script src="assets/js/custom.min.js"></script>
    <script src="assets/js/custom-popup.js"></script>
    <?php if ($popup_message != "") { ?>

        <script>
            $(document).ready(function() {

                init_PNotify(
                    <?php echo json_encode($popup_message); ?>,
                    <?php echo json_encode($popup_type); ?>,
                    <?php echo json_encode($popup_title); ?>,
                    <?php echo json_encode($popup_icon); ?>
                );

                <?php if ($redirect_page != "") { ?>

                    setTimeout(function() {
                        window.location.href = "<?php echo $redirect_page; ?>";
                    }, 2000);

                <?php } ?>

            });
        </script>

    <?php } ?>

</body>

</html>