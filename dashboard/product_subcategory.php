<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../config/database.php";


/* =========================================================
   GET LOGGED IN USER ID
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
    FROM users
    WHERE user_id = :user_id
    LIMIT 1
";

$admin_check_stmt = $conn->prepare($admin_check_sql);

$admin_check_stmt->execute([
    ':user_id' => $login_user_id
]);

$admin = $admin_check_stmt->fetch(PDO::FETCH_ASSOC);


/* =========================================================
   USER NOT FOUND
========================================================= */

if (!$admin) {
    header("Location: ../index.php");
    exit;
}


/* =========================================================
   CHECK USER STATUS
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
   VARIABLES
========================================================= */

$error = "";
$success = "";

$edit_subcategory = null;


/* =========================================================
   DELETE SUBCATEGORY
========================================================= */

if (isset($_GET['delete_id'])) {

    $delete_id = (int)$_GET['delete_id'];

    if ($delete_id > 0) {

        try {

            /* -------------------------------------------------
               CHECK PRODUCTS USING THIS SUBCATEGORY
            ------------------------------------------------- */

            $check_product_sql = "
                SELECT COUNT(*)
                FROM products
                WHERE subcategory_id = :subcategory_id
            ";

            $check_product_stmt =
                $conn->prepare($check_product_sql);

            $check_product_stmt->execute([
                ':subcategory_id' => $delete_id
            ]);

            $product_count =
                (int)$check_product_stmt->fetchColumn();


            if ($product_count > 0) {

                $error =
                    "This subcategory cannot be deleted because products are using it.";
            } else {

                $delete_sql = "
                    DELETE FROM product_subcategory
                    WHERE subcategory_id = :subcategory_id
                ";

                $delete_stmt =
                    $conn->prepare($delete_sql);

                $delete_stmt->execute([
                    ':subcategory_id' => $delete_id
                ]);

                $success =
                    "Subcategory deleted successfully.";
            }
        } catch (PDOException $e) {

            $error =
                "Unable to delete subcategory. " .
                $e->getMessage();
        }
    }
}


/* =========================================================
   EDIT SUBCATEGORY
========================================================= */

if (isset($_GET['edit_id'])) {

    $edit_id = (int)$_GET['edit_id'];

    if ($edit_id > 0) {

        try {

            $edit_sql = "
                SELECT
                    subcategory_id,
                    category_id,
                    subcategory_name
                FROM product_subcategory
                WHERE subcategory_id = :subcategory_id
                LIMIT 1
            ";

            $edit_stmt =
                $conn->prepare($edit_sql);

            $edit_stmt->execute([
                ':subcategory_id' => $edit_id
            ]);

            $edit_subcategory =
                $edit_stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {

            $error =
                "Unable to load subcategory. " .
                $e->getMessage();
        }
    }
}


/* =========================================================
   ADD / UPDATE SUBCATEGORY
========================================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';

    $category_id =
        isset($_POST['category_id'])
        ? (int)$_POST['category_id']
        : 0;

    $subcategory_name =
        trim($_POST['subcategory_name'] ?? '');

    $subcategory_id =
        isset($_POST['subcategory_id'])
        ? (int)$_POST['subcategory_id']
        : 0;


    /* =====================================================
       VALIDATION
    ===================================================== */

    if ($category_id <= 0) {

        $error = "Please select a category.";
    } elseif ($subcategory_name === '') {

        $error = "Please enter subcategory type.";
    } else {

        try {

            /* =================================================
               ADD
            ================================================= */

            if ($action === 'add') {

                $duplicate_sql = "
                    SELECT COUNT(*)
                    FROM product_subcategory
                    WHERE category_id = :category_id
                      AND LOWER(subcategory_name)
                          = LOWER(:subcategory_name)
                ";

                $duplicate_stmt =
                    $conn->prepare($duplicate_sql);

                $duplicate_stmt->execute([
                    ':category_id' =>
                    $category_id,

                    ':subcategory_name' =>
                    $subcategory_name
                ]);

                $duplicate_count =
                    (int)$duplicate_stmt->fetchColumn();


                if ($duplicate_count > 0) {

                    $error =
                        "This subcategory type already exists in this category.";
                } else {

                    $insert_sql = "
                        INSERT INTO product_subcategory
                        (
                            category_id,
                            subcategory_name
                        )
                        VALUES
                        (
                            :category_id,
                            :subcategory_name
                        )
                    ";

                    $insert_stmt =
                        $conn->prepare($insert_sql);

                    $insert_stmt->execute([
                        ':category_id' =>
                        $category_id,

                        ':subcategory_name' =>
                        $subcategory_name
                    ]);

                    $success =
                        "Subcategory type added successfully.";
                }
            }


            /* =================================================
               UPDATE
            ================================================= */ elseif ($action === 'update') {

                if ($subcategory_id <= 0) {

                    $error =
                        "Invalid subcategory ID.";
                } else {

                    $duplicate_sql = "
                        SELECT COUNT(*)
                        FROM product_subcategory
                        WHERE category_id = :category_id
                          AND LOWER(subcategory_name)
                              = LOWER(:subcategory_name)
                          AND subcategory_id != :subcategory_id
                    ";

                    $duplicate_stmt =
                        $conn->prepare($duplicate_sql);

                    $duplicate_stmt->execute([
                        ':category_id' =>
                        $category_id,

                        ':subcategory_name' =>
                        $subcategory_name,

                        ':subcategory_id' =>
                        $subcategory_id
                    ]);

                    $duplicate_count =
                        (int)$duplicate_stmt->fetchColumn();


                    if ($duplicate_count > 0) {

                        $error =
                            "This subcategory type already exists in this category.";
                    } else {

                        $update_sql = "
                            UPDATE product_subcategory
                            SET
                                category_id = :category_id,
                                subcategory_name = :subcategory_name
                            WHERE subcategory_id = :subcategory_id
                        ";

                        $update_stmt =
                            $conn->prepare($update_sql);

                        $update_stmt->execute([
                            ':category_id' =>
                            $category_id,

                            ':subcategory_name' =>
                            $subcategory_name,

                            ':subcategory_id' =>
                            $subcategory_id
                        ]);

                        $success =
                            "Subcategory type updated successfully.";

                        $edit_subcategory = null;
                    }
                }
            }
        } catch (PDOException $e) {

            $error =
                "Database error: " .
                $e->getMessage();
        }
    }
}


/* =========================================================
   FETCH CATEGORIES
========================================================= */

try {

    $category_sql = "
        SELECT
            category_id,
            category_name
        FROM product_category
        ORDER BY category_name ASC
    ";

    $category_stmt =
        $conn->prepare($category_sql);

    $category_stmt->execute();

    $categories =
        $category_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {

    $categories = [];

    $error =
        "Unable to load categories. " .
        $e->getMessage();
}


/* =========================================================
   PAGINATION
========================================================= */

$records_per_page = 10;


/* =========================================================
   CURRENT PAGE
========================================================= */

$current_page =
    isset($_GET['page'])
    ? (int)$_GET['page']
    : 1;


if ($current_page < 1) {
    $current_page = 1;
}


/* =========================================================
   TOTAL RECORDS
========================================================= */

try {

    $count_sql = "
        SELECT COUNT(*)
        FROM product_subcategory
    ";

    $count_stmt =
        $conn->prepare($count_sql);

    $count_stmt->execute();

    $total_records =
        (int)$count_stmt->fetchColumn();
} catch (PDOException $e) {

    $total_records = 0;

    $error =
        "Unable to count subcategories. " .
        $e->getMessage();
}


/* =========================================================
   TOTAL PAGES
========================================================= */

$total_pages =
    $total_records > 0
    ? (int)ceil(
        $total_records / $records_per_page
    )
    : 1;


/* =========================================================
   CHECK CURRENT PAGE
========================================================= */

if ($current_page > $total_pages) {
    $current_page = $total_pages;
}


/* =========================================================
   OFFSET
========================================================= */

$offset =
    ($current_page - 1)
    * $records_per_page;


/* =========================================================
   FETCH 10 SUBCATEGORIES
========================================================= */

try {

    $subcategory_sql = "
        SELECT
            ps.subcategory_id,
            ps.category_id,
            ps.subcategory_name,
            pc.category_name
        FROM product_subcategory ps

        LEFT JOIN product_category pc
            ON ps.category_id = pc.category_id

        ORDER BY
            ps.subcategory_id DESC

        LIMIT :limit
        OFFSET :offset
    ";

    $subcategory_stmt =
        $conn->prepare($subcategory_sql);

    $subcategory_stmt->bindValue(
        ':limit',
        $records_per_page,
        PDO::PARAM_INT
    );

    $subcategory_stmt->bindValue(
        ':offset',
        $offset,
        PDO::PARAM_INT
    );

    $subcategory_stmt->execute();

    $subcategories =
        $subcategory_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {

    $subcategories = [];

    $error =
        "Unable to load subcategories. " .
        $e->getMessage();
}


/* =========================================================
   START / END RECORD
========================================================= */

$start_record =
    $total_records > 0
    ? $offset + 1
    : 0;

$end_record =
    min(
        $offset + $records_per_page,
        $total_records
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
        Product Subcategory Management
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
        .form-panel {

            margin-bottom: 20px;

        }


        .table th {

            background: #f5f5f5;

        }


        .action-buttons {

            white-space: nowrap;

        }


        .page-title {

            margin-bottom: 20px;

        }


        .empty-box {

            text-align: center;

            padding: 40px;

            color: #999;

        }


        .empty-box i {

            font-size: 45px;

            margin-bottom: 15px;

        }


        .subcategory-type {

            font-weight: 600;

            color: #333;

        }


        .category-label {

            font-size: 12px;

            padding: 6px 10px;

        }


        .pagination-box {

            text-align: center;

            margin-top: 20px;

        }


        .pagination {

            margin: 0;

        }


        .record-info {

            margin-top: 15px;

            color: #777;

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

                                            <a href="javascript:;">

                                                <i
                                                    class="fa fa-user pull-right">
                                                </i>

                                                Profile

                                            </a>

                                        </li>


                                        <li>

                                            <a href="javascript:;">

                                                <i
                                                    class="fa fa-cog pull-right">
                                                </i>

                                                Settings

                                            </a>

                                        </li>


                                        <li>

                                            <a href="javascript:;">

                                                <i
                                                    class="fa fa-question-circle pull-right">
                                                </i>

                                                Help

                                            </a>

                                        </li>


                                        <li>

                                            <a href="../logout.php">

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
                        class="row page-title">


                        <div class="col-md-8">


                            <h3>

                                <i class="fa fa-list-alt"></i>

                                Product Subcategory

                            </h3>


                            <p class="text-muted">

                                Manage product subcategory types

                            </p>


                        </div>


                        <div class="col-md-4 text-right">


                            <a
                                href="products.php"
                                class="btn btn-default">

                                <i class="fa fa-arrow-left"></i>

                                Products

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
                            echo htmlspecialchars($success);
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
                            echo htmlspecialchars($error);
                            ?>

                        </div>

                    <?php } ?>


                    <!-- =================================================
                     ADD / EDIT SUBCATEGORY
                ================================================== -->

                    <div class="x_panel form-panel">


                        <div class="x_title">


                            <h2>

                                <?php if ($edit_subcategory) { ?>

                                    <i class="fa fa-edit"></i>

                                    Edit Subcategory Type

                                <?php } else { ?>

                                    <i class="fa fa-plus"></i>

                                    Add Subcategory Type

                                <?php } ?>

                            </h2>


                            <div class="clearfix"></div>


                        </div>


                        <div class="x_content">


                            <form
                                method="POST"
                                class="form-horizontal">


                                <!-- ACTION -->

                                <input
                                    type="hidden"
                                    name="action"
                                    value="<?php

                                            echo $edit_subcategory
                                                ? 'update'
                                                : 'add';

                                            ?>">


                                <!-- EDIT ID -->

                                <?php if ($edit_subcategory) { ?>

                                    <input
                                        type="hidden"
                                        name="subcategory_id"
                                        value="<?php

                                                echo (int)
                                                $edit_subcategory['subcategory_id'];

                                                ?>">

                                <?php } ?>


                                <!-- CATEGORY -->

                                <div class="form-group">


                                    <label
                                        class="control-label col-md-3">

                                        Category

                                        <span class="text-danger">
                                            *
                                        </span>

                                    </label>


                                    <div class="col-md-6">


                                        <select
                                            name="category_id"
                                            class="form-control"
                                            required>


                                            <option value="">

                                                -- Select Category --

                                            </option>


                                            <?php
                                            foreach (
                                                $categories
                                                as $category
                                            ) {
                                            ?>


                                                <option
                                                    value="<?php

                                                            echo (int)
                                                            $category['category_id'];

                                                            ?>"

                                                    <?php

                                                    if (
                                                        $edit_subcategory
                                                        &&
                                                        (int)$edit_subcategory['category_id']
                                                        ===
                                                        (int)$category['category_id']
                                                    ) {

                                                        echo "selected";
                                                    }

                                                    ?>>

                                                    <?php

                                                    echo htmlspecialchars(
                                                        $category['category_name']
                                                    );

                                                    ?>

                                                </option>


                                            <?php } ?>


                                        </select>


                                    </div>


                                </div>


                                <!-- SUBCATEGORY TYPE -->

                                <div class="form-group">


                                    <label
                                        class="control-label col-md-3">

                                        Subcategory Type

                                        <span class="text-danger">
                                            *
                                        </span>

                                    </label>


                                    <div class="col-md-6">


                                        <input
                                            type="text"
                                            name="subcategory_name"
                                            class="form-control"
                                            placeholder="Example: Bouquet Flowers"
                                            value="<?php

                                                    echo $edit_subcategory
                                                        ? htmlspecialchars(
                                                            $edit_subcategory['subcategory_name']
                                                        )
                                                        : '';

                                                    ?>"
                                            required>


                                        <small
                                            class="text-muted">

                                            Example:
                                            Bouquet Flowers,
                                            Roses,
                                            Lilies,
                                            Orchids

                                        </small>


                                    </div>


                                </div>


                                <!-- BUTTONS -->

                                <div class="form-group">


                                    <div
                                        class="col-md-offset-3 col-md-6">


                                        <?php if ($edit_subcategory) { ?>


                                            <button
                                                type="submit"
                                                class="btn btn-success">

                                                <i
                                                    class="fa fa-save">
                                                </i>

                                                Update

                                            </button>


                                            <a
                                                href="product_subcategory.php"
                                                class="btn btn-default">

                                                <i
                                                    class="fa fa-times">
                                                </i>

                                                Cancel

                                            </a>


                                        <?php } else { ?>


                                            <button
                                                type="submit"
                                                class="btn btn-primary">

                                                <i
                                                    class="fa fa-plus">
                                                </i>

                                                Add Subcategory

                                            </button>


                                            <button
                                                type="reset"
                                                class="btn btn-default">

                                                <i
                                                    class="fa fa-refresh">
                                                </i>

                                                Reset

                                            </button>


                                        <?php } ?>


                                    </div>


                                </div>


                            </form>


                        </div>


                    </div>


                    <!-- =================================================
                     SUBCATEGORY TABLE
                ================================================== -->

                    <div class="x_panel">


                        <div class="x_title">


                            <h2>

                                <i class="fa fa-table"></i>

                                Product Subcategory List

                                <small>

                                    <?php
                                    echo $total_records;
                                    ?>

                                    Total

                                </small>

                            </h2>


                            <div class="clearfix"></div>


                        </div>


                        <div class="x_content">


                            <?php if (!empty($subcategories)) { ?>


                                <div class="table-responsive">


                                    <table
                                        class="table table-striped table-bordered">


                                        <thead>


                                            <tr>


                                                <th
                                                    style="width:60px;">

                                                    #

                                                </th>


                                                <th>

                                                    Subcategory ID

                                                </th>


                                                <th>

                                                    Category

                                                </th>


                                                <th>

                                                    Subcategory Type

                                                </th>


                                                <th
                                                    style="width:180px;">

                                                    Action

                                                </th>


                                            </tr>


                                        </thead>


                                        <tbody>


                                            <?php

                                            $sr_no = $offset + 1;

                                            foreach (
                                                $subcategories
                                                as $subcategory
                                            ) {

                                            ?>


                                                <tr>


                                                    <!-- SERIAL NUMBER -->

                                                    <td>

                                                        <?php

                                                        echo $sr_no++;

                                                        ?>

                                                    </td>


                                                    <!-- ID -->

                                                    <td>

                                                        <strong>

                                                            <?php

                                                            echo (int)
                                                            $subcategory['subcategory_id'];

                                                            ?>

                                                        </strong>

                                                    </td>


                                                    <!-- CATEGORY -->

                                                    <td>


                                                        <span
                                                            class="label label-info category-label">


                                                            <i
                                                                class="fa fa-folder">
                                                            </i>


                                                            <?php

                                                            echo htmlspecialchars(
                                                                $subcategory['category_name']
                                                                    ??
                                                                    'N/A'
                                                            );

                                                            ?>


                                                        </span>


                                                    </td>


                                                    <!-- SUBCATEGORY TYPE -->

                                                    <td>


                                                        <span
                                                            class="subcategory-type">


                                                            <i
                                                                class="fa fa-tag text-primary">
                                                            </i>


                                                            <?php

                                                            echo htmlspecialchars(
                                                                $subcategory['subcategory_name']
                                                            );

                                                            ?>


                                                        </span>


                                                    </td>


                                                    <!-- ACTION -->

                                                    <td
                                                        class="action-buttons">


                                                        <!-- EDIT -->

                                                        <a
                                                            href="product_subcategory.php?edit_id=<?php

                                                                                                    echo (int)
                                                                                                    $subcategory['subcategory_id'];

                                                                                                    ?>&page=<?php

                                                                                                            echo $current_page;

                                                                                                            ?>"
                                                            class="btn btn-info btn-sm">


                                                            <i
                                                                class="fa fa-edit">
                                                            </i>

                                                            Edit


                                                        </a>


                                                        <!-- DELETE -->

                                                        <a
                                                            href="product_subcategory.php?delete_id=<?php

                                                                                                    echo (int)
                                                                                                    $subcategory['subcategory_id'];

                                                                                                    ?>&page=<?php

                                                                                                            echo $current_page;

                                                                                                            ?>"
                                                            class="btn btn-danger btn-sm"
                                                            onclick="return confirm(
                                                            'Are you sure you want to delete this subcategory?'
                                                        );">


                                                            <i
                                                                class="fa fa-trash">
                                                            </i>

                                                            Delete


                                                        </a>


                                                    </td>


                                                </tr>


                                            <?php } ?>


                                        </tbody>


                                    </table>


                                </div>


                                <!-- =================================================
                                 RECORD INFORMATION
                            ================================================== -->

                                <div class="record-info">


                                    Showing

                                    <strong>
                                        <?php
                                        echo $start_record;
                                        ?>
                                    </strong>

                                    to

                                    <strong>
                                        <?php
                                        echo $end_record;
                                        ?>
                                    </strong>

                                    of

                                    <strong>
                                        <?php
                                        echo $total_records;
                                        ?>
                                    </strong>

                                    subcategories


                                </div>


                                <!-- =================================================
                                 PAGINATION
                            ================================================== -->

                                <?php if ($total_pages > 1) { ?>


                                    <div class="pagination-box">


                                        <ul class="pagination">


                                            <!-- PREVIOUS -->

                                            <?php if ($current_page > 1) { ?>


                                                <li>


                                                    <a
                                                        href="product_subcategory.php?page=<?php

                                                                                            echo $current_page - 1;

                                                                                            ?>">


                                                        <i
                                                            class="fa fa-angle-left">
                                                        </i>

                                                        Previous


                                                    </a>


                                                </li>


                                            <?php } else { ?>


                                                <li class="disabled">


                                                    <span>


                                                        <i
                                                            class="fa fa-angle-left">
                                                        </i>

                                                        Previous


                                                    </span>


                                                </li>


                                            <?php } ?>


                                            <!-- PAGE NUMBERS -->

                                            <?php

                                            for (
                                                $page = 1;
                                                $page <= $total_pages;
                                                $page++
                                            ) {

                                            ?>


                                                <?php if (
                                                    $page === $current_page
                                                ) { ?>


                                                    <li class="active">


                                                        <span>

                                                            <?php
                                                            echo $page;
                                                            ?>

                                                        </span>


                                                    </li>


                                                <?php } else { ?>


                                                    <li>


                                                        <a
                                                            href="product_subcategory.php?page=<?php

                                                                                                echo $page;

                                                                                                ?>">

                                                            <?php
                                                            echo $page;
                                                            ?>

                                                        </a>


                                                    </li>


                                                <?php } ?>


                                            <?php } ?>


                                            <!-- NEXT -->

                                            <?php if (
                                                $current_page < $total_pages
                                            ) { ?>


                                                <li>


                                                    <a
                                                        href="product_subcategory.php?page=<?php

                                                                                            echo $current_page + 1;

                                                                                            ?>">


                                                        Next

                                                        <i
                                                            class="fa fa-angle-right">
                                                        </i>


                                                    </a>


                                                </li>


                                            <?php } else { ?>


                                                <li class="disabled">


                                                    <span>


                                                        Next

                                                        <i
                                                            class="fa fa-angle-right">
                                                        </i>


                                                    </span>


                                                </li>


                                            <?php } ?>


                                        </ul>


                                    </div>


                                <?php } ?>


                            <?php } else { ?>


                                <div class="empty-box">


                                    <i
                                        class="fa fa-list-alt">
                                    </i>


                                    <h4>

                                        No Subcategories Found

                                    </h4>


                                    <p>

                                        Add your first subcategory
                                        using the form above.

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
            <!-- /right_col -->


        </div>
        <!-- /main_container -->


    </div>
    <!-- /container body -->


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