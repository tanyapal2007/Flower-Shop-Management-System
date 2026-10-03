<?php
session_start();

include "../config/database.php";


/* =========================================================
   GET LOGGED IN USER ID
========================================================= */

$login_user_id = $_SESSION['user_id'] ?? 0;


/* =========================================================
   CHECK LOGIN
========================================================= */

if ($login_user_id == 0) {
    header("Location: ../index.php");
    exit;
}


/* =========================================================
   CHECK ADMIN USER
========================================================= */

$admin_check_sql = "
    SELECT
        user_id,
        name,
        phone,
        role,
        status,
        created_at
    FROM `user`
    WHERE user_id = $login_user_id
    LIMIT 1
";

$admin_check_result = mysqli_query($conn, $admin_check_sql);


if (!$admin_check_result) {
    die("Admin Check Error: " . mysqli_error($conn));
}


/* =========================================================
   USER NOT FOUND
========================================================= */

if (mysqli_num_rows($admin_check_result) == 0) {
    header("Location: ../index.php");
    exit;
}


$admin = mysqli_fetch_assoc($admin_check_result);


/* =========================================================
   CHECK USER STATUS
========================================================= */

if ($admin['status'] != 1) {
    header("Location: ../index.php");
    exit;
}


/* =========================================================
   CHECK ADMIN ROLE
========================================================= */

if ($admin['role'] != 'admin') {
    header("Location: ../index.php");
    exit;
}


/* =========================================================
   DELETE SUBCATEGORY
========================================================= */

if (isset($_GET['delete'])) {

    $subcategory_id = (int) $_GET['delete'];


    /* -----------------------------------------------------
       GET IMAGE NAME BEFORE DELETE
    ----------------------------------------------------- */

    $get_image_sql = "
        SELECT subcategory_image
        FROM product_subcategory
        WHERE subcategory_id = $subcategory_id
        LIMIT 1
    ";

    $get_image = mysqli_query($conn, $get_image_sql);


    if ($get_image && mysqli_num_rows($get_image) > 0) {

        $image_data = mysqli_fetch_assoc($get_image);

        if (!empty($image_data['subcategory_image'])) {

            $image_path = "../uploads/subcategories/" .
                $image_data['subcategory_image'];

            if (file_exists($image_path)) {
                unlink($image_path);
            }
        }
    }


    /* -----------------------------------------------------
       DELETE SUBCATEGORY
    ----------------------------------------------------- */

    $delete_sql = "
        DELETE FROM product_subcategory
        WHERE subcategory_id = $subcategory_id
    ";


    if (mysqli_query($conn, $delete_sql)) {

        header("Location: product_subcategory.php");
        exit;
    } else {

        die("Subcategory Delete Error: " .
            mysqli_error($conn));
    }
}


/* =========================================================
   FETCH SUBCATEGORIES
========================================================= */

$sql = "
    SELECT
        ps.subcategory_id,
        ps.subcategory_name,
        ps.subcategory_description,
        ps.subcategory_image,
        ps.status,
        ps.created_at,
        pc.category_name
    FROM product_subcategory ps
    LEFT JOIN product_category pc
        ON ps.category_id = pc.category_id
    ORDER BY ps.subcategory_id DESC
";


$result = mysqli_query($conn, $sql);


if (!$result) {
    die("Subcategory Fetch Error: " .
        mysqli_error($conn));
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

    <title>Product Subcategory</title>


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


    <!-- DataTables CSS -->

    <link
        rel="stylesheet"
        href="https://cdn.datatables.net/2.3.4/css/dataTables.dataTables.css">


    <style>
        .page-title-box {
            margin-bottom: 25px;
        }


        .subcategory-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #ddd;
        }


        .no-image {
            width: 60px;
            height: 60px;
            background: #f1f1f1;
            border-radius: 8px;

            display: flex;
            align-items: center;
            justify-content: center;
        }


        .action-buttons a {
            margin: 2px;
        }


        .table>tbody>tr>td {
            vertical-align: middle;
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


                                <!-- MESSAGE -->

                                <li
                                    role="presentation"
                                    class="dropdown">

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
                 PAGE CONTENT
            ================================================== -->

                <div class="container-fluid">


                    <!-- PAGE HEADER -->

                    <div
                        class="row"
                        style="margin-bottom: 20px;">


                        <div class="col-md-8">

                            <h3>

                                <i class="fa fa-list"></i>

                                Product Subcategory

                            </h3>


                            <p class="text-muted">

                                Manage product subcategories

                            </p>

                        </div>


                        <div class="col-md-4 text-right">

                            <a
                                href="add_subcategory.php"
                                class="btn btn-primary">

                                <i class="fa fa-plus"></i>

                                Add Subcategory

                            </a>

                        </div>

                    </div>


                    <!-- =================================================
                     SUBCATEGORY TABLE
                ================================================== -->

                    <div class="x_panel">


                        <div class="x_title">

                            <h2>

                                Subcategory List

                            </h2>


                            <div class="clearfix"></div>

                        </div>


                        <div class="x_content">


                            <div class="table-responsive">


                                <table
                                    id="subcategory_table"
                                    class="table table-striped table-bordered"
                                    style="width:100%;">


                                    <thead>

                                        <tr>

                                            <th>#</th>

                                            <th>ID</th>

                                            <th>Image</th>

                                            <th>Category</th>

                                            <th>Subcategory Name</th>

                                            <th>Description</th>

                                            <th>Status</th>

                                            <th>Created Date</th>

                                            <th class="text-center">
                                                Action
                                            </th>

                                        </tr>

                                    </thead>


                                    <tbody>


                                        <?php

                                        if (
                                            $result &&
                                            mysqli_num_rows($result) > 0
                                        ) {

                                            $count = 1;


                                            while (
                                                $subcategory =
                                                mysqli_fetch_assoc($result)
                                            ) {

                                        ?>


                                                <tr>


                                                    <!-- NUMBER -->

                                                    <td>

                                                        <?php
                                                        echo $count++;
                                                        ?>

                                                    </td>


                                                    <!-- ID -->

                                                    <td>

                                                        <?php
                                                        echo htmlspecialchars(
                                                            $subcategory['subcategory_id']
                                                        );
                                                        ?>

                                                    </td>


                                                    <!-- IMAGE -->

                                                    <td>


                                                        <?php

                                                        if (
                                                            !empty($subcategory['subcategory_image'])
                                                        ) {

                                                        ?>


                                                            <img
                                                                src="../uploads/subcategories/<?php
                                                                                                echo htmlspecialchars(
                                                                                                    $subcategory['subcategory_image']
                                                                                                );
                                                                                                ?>"
                                                                alt="Subcategory"
                                                                class="subcategory-image">


                                                        <?php

                                                        } else {

                                                        ?>


                                                            <div class="no-image">

                                                                <i
                                                                    class="fa fa-image text-muted"></i>

                                                            </div>


                                                        <?php

                                                        }

                                                        ?>


                                                    </td>


                                                    <!-- CATEGORY -->

                                                    <td>

                                                        <?php

                                                        if (
                                                            !empty($subcategory['category_name'])
                                                        ) {

                                                        ?>

                                                            <span
                                                                class="label label-info">

                                                                <?php
                                                                echo htmlspecialchars(
                                                                    $subcategory['category_name']
                                                                );
                                                                ?>

                                                            </span>

                                                        <?php

                                                        } else {

                                                            echo '<span class="text-muted">N/A</span>';
                                                        }

                                                        ?>

                                                    </td>


                                                    <!-- SUBCATEGORY NAME -->

                                                    <td>

                                                        <strong>

                                                            <?php
                                                            echo htmlspecialchars(
                                                                $subcategory['subcategory_name']
                                                            );
                                                            ?>

                                                        </strong>

                                                    </td>


                                                    <!-- DESCRIPTION -->

                                                    <td>

                                                        <?php

                                                        if (
                                                            !empty($subcategory['subcategory_description'])
                                                        ) {

                                                            echo htmlspecialchars(
                                                                $subcategory['subcategory_description']
                                                            );
                                                        } else {

                                                            echo
                                                            '<span class="text-muted">
                                                    N/A
                                                </span>';
                                                        }

                                                        ?>

                                                    </td>


                                                    <!-- STATUS -->

                                                    <td>


                                                        <?php

                                                        if (
                                                            $subcategory['status'] == 1
                                                        ) {

                                                        ?>

                                                            <span
                                                                class="label label-success">

                                                                Active

                                                            </span>

                                                        <?php

                                                        } else {

                                                        ?>

                                                            <span
                                                                class="label label-default">

                                                                Inactive

                                                            </span>

                                                        <?php

                                                        }

                                                        ?>

                                                    </td>


                                                    <!-- DATE -->

                                                    <td>

                                                        <?php

                                                        if (
                                                            !empty($subcategory['created_at'])
                                                        ) {

                                                            echo date(
                                                                "d M Y, h:i A",
                                                                strtotime(
                                                                    $subcategory['created_at']
                                                                )
                                                            );
                                                        } else {

                                                            echo "N/A";
                                                        }

                                                        ?>

                                                    </td>


                                                    <!-- ACTION -->

                                                    <td class="text-center action-buttons">


                                                        <!-- EDIT -->

                                                        <a
                                                            href="edit_product_subcategory.php?id=<?php
                                                                                                    echo (int)$subcategory['subcategory_id'];
                                                                                                    ?>"
                                                            class="btn btn-warning btn-sm"
                                                            title="Edit Subcategory">

                                                            <i class="fa fa-edit"></i>

                                                        </a>


                                                        <!-- DELETE -->

                                                        <a
                                                            href="product_subcategory.php?delete=<?php
                                                                                                    echo (int)$subcategory['subcategory_id'];
                                                                                                    ?>"
                                                            class="btn btn-danger btn-sm"
                                                            title="Delete Subcategory"
                                                            onclick="return confirm(
                                                    'Are you sure you want to delete this subcategory?'
                                                );">

                                                            <i class="fa fa-trash"></i>

                                                        </a>


                                                    </td>


                                                </tr>


                                            <?php

                                            }
                                        } else {

                                            ?>


                                            <tr>

                                                <td
                                                    colspan="9"
                                                    class="text-center">

                                                    <br>

                                                    <i
                                                        class="fa fa-folder-open fa-3x text-muted"></i>


                                                    <h4>

                                                        No Subcategories Found

                                                    </h4>


                                                    <p class="text-muted">

                                                        Click
                                                        <strong>
                                                            Add Subcategory
                                                        </strong>
                                                        to create one.

                                                    </p>

                                                    <br>

                                                </td>

                                            </tr>


                                        <?php

                                        }

                                        ?>


                                    </tbody>

                                </table>


                            </div>

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
            <!-- /right_col -->


        </div>
        <!-- /main_container -->


    </div>
    <!-- /container body -->


    <!-- =========================================================
     JAVASCRIPT
========================================================= -->


    <!-- jQuery -->

    <script src="assets/vendors/jquery/dist/jquery.min.js"></script>


    <!-- Bootstrap -->

    <script src="assets/vendors/bootstrap/dist/js/bootstrap.min.js"></script>


    <!-- FastClick -->

    <script src="assets/vendors/fastclick/lib/fastclick.js"></script>


    <!-- NProgress -->

    <script src="assets/vendors/nprogress/nprogress.js"></script>


    <!-- Custom Theme -->

    <script src="assets/js/custom.min.js"></script>


    <!-- DataTables -->

    <script src="https://cdn.datatables.net/2.3.4/js/dataTables.js"></script>


    <script>
        $(document).ready(function() {

            $('#subcategory_table').DataTable({

                pageLength: 10,

                lengthMenu: [
                    [10, 25, 50, 100],
                    [10, 25, 50, 100]
                ],

                searching: true,

                ordering: true,

                paging: true,

                info: true,

                autoWidth: false

            });

        });
    </script>


</body>

</html>