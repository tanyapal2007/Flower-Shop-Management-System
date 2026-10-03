<?php

// ======================================================
// SESSION START
// ======================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


// ======================================================
// DATABASE CONNECTION
// ======================================================

require_once "../config/database.php";


// ======================================================
// 1. CHECK LOGIN
// ======================================================

if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {

    header("Location: ../login.php");
    exit;
}

$login_user_id = (int) $_SESSION['user_id'];


// ======================================================
// 2. CHECK ADMIN
// ======================================================

$admin_check_stmt = $conn->prepare("
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
");

$admin_check_stmt->execute([
    ':user_id' => $login_user_id
]);

$admin = $admin_check_stmt->fetch(PDO::FETCH_ASSOC);


// ======================================================
// ONLY ADMIN CAN ACCESS THIS PAGE
// ======================================================

if (!$admin || strtolower($admin['role']) !== 'admin') {

    header("Location: ../index.php");
    exit;
}


// ======================================================
// 3. MESSAGE VARIABLES
// ======================================================

$success_message = "";
$error_message = "";


// ======================================================
// 4. DELETE USER
// ======================================================

// ======================================================
// 4. DELETE USER
// ======================================================

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['delete_user'])
) {

    // --------------------------------------------------
    // GET DELETE USER ID
    // --------------------------------------------------

    $delete_user_id = isset($_POST['delete_user_id'])
        ? (int) $_POST['delete_user_id']
        : 0;


    // --------------------------------------------------
    // CHECK USER ID
    // --------------------------------------------------

    if ($delete_user_id <= 0) {

        header("Location: All_user.php?error=invalid");
        exit;
    }


    // --------------------------------------------------
    // ADMIN CANNOT DELETE HIMSELF
    // --------------------------------------------------

    if ($delete_user_id === $login_user_id) {

        header("Location: All_user.php?error=self");
        exit;
    }


    try {

        // ==================================================
        // START TRANSACTION
        // ==================================================

        $conn->beginTransaction();


        // ==================================================
        // CHECK USER EXISTS
        // ==================================================

        $check_user_stmt = $conn->prepare("
            SELECT
                user_id,
                role
            FROM users
            WHERE user_id = :user_id
            LIMIT 1
        ");

        $check_user_stmt->execute([
            ':user_id' => $delete_user_id
        ]);

        $user_to_delete =
            $check_user_stmt->fetch(PDO::FETCH_ASSOC);


        // ==================================================
        // USER NOT FOUND
        // ==================================================

        if (!$user_to_delete) {

            $conn->rollBack();

            header(
                "Location: All_user.php?error=notfound"
            );

            exit;
        }


        // ==================================================
        // DO NOT DELETE ADMIN
        // ==================================================

        if (
            strtolower(
                trim($user_to_delete['role'])
            ) === 'admin'
        ) {

            $conn->rollBack();

            header(
                "Location: All_user.php?error=admin"
            );

            exit;
        }


        // ==================================================
        // DELETE USER PROFILE FIRST
        // ==================================================

        $delete_profile_stmt = $conn->prepare("
            DELETE FROM user_profile
            WHERE user_id = :user_id
        ");

        $delete_profile_stmt->execute([
            ':user_id' => $delete_user_id
        ]);


        // ==================================================
        // DELETE USER
        // ==================================================

        $delete_user_stmt = $conn->prepare("
            DELETE FROM users
            WHERE user_id = :user_id
            RETURNING user_id
        ");

        $delete_user_stmt->execute([
            ':user_id' => $delete_user_id
        ]);


        // ==================================================
        // CHECK DELETE RESULT
        // ==================================================

        $deleted_user =
            $delete_user_stmt->fetch(PDO::FETCH_ASSOC);


        // ==================================================
        // DELETE SUCCESS
        // ==================================================

        if ($deleted_user) {

            $conn->commit();

            header(
                "Location: All_user.php?deleted=1"
            );

            exit;
        }


        // ==================================================
        // DELETE FAILED
        // ==================================================

        $conn->rollBack();

        header(
            "Location: All_user.php?error=notfound"
        );

        exit;
    } catch (PDOException $e) {


        // ==================================================
        // ROLLBACK
        // ==================================================

        if ($conn->inTransaction()) {

            $conn->rollBack();
        }


        // ==================================================
        // DATABASE ERROR
        // ==================================================

        header(
            "Location: All_user.php?error=database"
        );

        exit;
    }
}

// ======================================================
// 5. DELETE SUCCESS MESSAGE
// ======================================================

if (
    isset($_GET['deleted'])
    && $_GET['deleted'] === '1'
) {

    $success_message = "User deleted successfully.";
}


// ======================================================
// 6. UPDATE SUCCESS MESSAGE
// ======================================================

if (
    isset($_GET['updated'])
    && $_GET['updated'] === '1'
) {

    $success_message = "User updated successfully.";
}


// ======================================================
// 7. ERROR MESSAGES
// ======================================================

if (isset($_GET['error'])) {


    switch ($_GET['error']) {


        case 'invalid':

            $error_message = "Invalid user ID.";

            break;


        case 'self':

            $error_message =
                "Admin user cannot delete himself.";

            break;


        case 'notfound':

            $error_message =
                "User was already deleted or does not exist.";

            break;


        case 'database':

            $error_message =
                "User could not be deleted because of a database restriction.";

            break;
    }
}


// ======================================================
// 8. GET ADMIN PROFILE
// ======================================================

$admin_profile = null;


try {

    $admin_profile_stmt = $conn->prepare("
        SELECT
            profile_image,
            gender,
            date_of_birth,
            bio
        FROM user_profile
        WHERE user_id = :user_id
        LIMIT 1
    ");

    $admin_profile_stmt->execute([
        ':user_id' => $login_user_id
    ]);

    $admin_profile =
        $admin_profile_stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {

    $admin_profile = null;
}


// ======================================================
// 9. LOGIN DATE & TIME
// ======================================================

if (
    isset($_SESSION['login_time'])
    && !empty($_SESSION['login_time'])
) {

    $login_date_time =
        $_SESSION['login_time'];
} else {

    $login_date_time =
        date("Y-m-d H:i:s");
}


// ======================================================
// 10. TOTAL CUSTOMERS
// ======================================================

$count_stmt = $conn->prepare("
    SELECT COUNT(*) AS total_users
    FROM users
    WHERE LOWER(role) != 'admin'
");

$count_stmt->execute();

$count_data =
    $count_stmt->fetch(PDO::FETCH_ASSOC);

$total_users =
    (int) ($count_data['total_users'] ?? 0);


// ======================================================
// 11. GET ALL CUSTOMERS
// ======================================================

$users = [];


try {


    $user_stmt = $conn->prepare("
        SELECT
            u.user_id,
            u.name,
            u.email,
            u.phone,
            u.role,
            u.status,
            u.created_at,

            up.profile_image,
            up.gender,
            up.date_of_birth,
            up.bio

        FROM users u

        LEFT JOIN user_profile up
            ON u.user_id = up.user_id

        WHERE LOWER(u.role) != 'admin'

        ORDER BY u.user_id DESC
    ");


    $user_stmt->execute();


    $users =
        $user_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {

    $users = [];

    $error_message =
        "Unable to load users.";
}


// ======================================================
// 12. ADMIN PROFILE IMAGE
// ======================================================

if (
    !empty($admin_profile)
    && !empty($admin_profile['profile_image'])
) {

    $admin_image =
        "../" .
        ltrim(
            $admin_profile['profile_image'],
            "/"
        );
} else {

    $admin_image =
        "../assets/images/default-user.png";
}

?>



<!DOCTYPE html>

<html lang="en">


<head>


    <!-- ==================================================
         BASIC META
    ================================================== -->

    <meta charset="utf-8">

    <meta
        http-equiv="X-UA-Compatible"
        content="IE=edge">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1">


    <!-- ==================================================
         PAGE TITLE
    ================================================== -->

    <title>All Users</title>


    <!-- ==================================================
         BOOTSTRAP CSS
    ================================================== -->

    <link
        href="assets/vendors/bootstrap/dist/css/bootstrap.min.css"
        rel="stylesheet">


    <!-- ==================================================
         FONT AWESOME
    ================================================== -->

    <link
        href="assets/vendors/font-awesome/css/font-awesome.min.css"
        rel="stylesheet">


    <!-- ==================================================
         NPROGRESS
    ================================================== -->

    <link
        href="assets/vendors/nprogress/nprogress.css"
        rel="stylesheet">


    <!-- ==================================================
         ICHECK
    ================================================== -->

    <link
        href="assets/vendors/iCheck/skins/flat/green.css"
        rel="stylesheet">


    <!-- ==================================================
         BOOTSTRAP PROGRESSBAR
    ================================================== -->

    <link
        href="assets/vendors/bootstrap-progressbar/css/bootstrap-progressbar-3.3.4.min.css"
        rel="stylesheet">


    <!-- ==================================================
         JQVMAP
    ================================================== -->

    <link
        href="assets/vendors/jqvmap/dist/jqvmap.min.css"
        rel="stylesheet">


    <!-- ==================================================
         DATE RANGE PICKER
    ================================================== -->

    <link
        href="assets/vendors/bootstrap-daterangepicker/daterangepicker.css"
        rel="stylesheet">


    <!-- ==================================================
         CUSTOM THEME
    ================================================== -->

    <link
        href="assets/css/custom.min.css"
        rel="stylesheet">


    <!-- ==================================================
         CUSTOM POPUP
    ================================================== -->

    <link
        href="assets/css/custom-popup.css"
        rel="stylesheet">


    <!-- ==================================================
         ALL USERS CSS
    ================================================== -->

    <link
        href="assets/css/all_user.css"
        rel="stylesheet">


</head>



<body class="nav-md">


    <div class="container body">


        <div class="main_container">


            <!-- =================================================
             SIDEBAR
        ================================================== -->

            <?php include 'sidebar.php'; ?>



            <!-- =================================================
             RIGHT CONTENT
        ================================================== -->

            <div
                class="right_col"
                role="main">


                <div class="content">


                    <!-- =========================================
                     SUCCESS MESSAGE
                ========================================== -->

                    <?php if ($success_message !== '') { ?>


                        <div class="alert alert-success">


                            <i
                                class="fa fa-check-circle">
                            </i>


                            <?php

                            echo htmlspecialchars(
                                $success_message
                            );

                            ?>


                        </div>


                    <?php } ?>



                    <!-- =========================================
                     ERROR MESSAGE
                ========================================== -->

                    <?php if ($error_message !== '') { ?>


                        <div class="alert alert-danger">


                            <i
                                class="fa fa-exclamation-circle">
                            </i>


                            <?php

                            echo htmlspecialchars(
                                $error_message
                            );

                            ?>


                        </div>


                    <?php } ?>



                    <!-- =========================================
                     PAGE TITLE
                ========================================== -->

                    <div
                        class="page-title-section">


                        <h2>
                            All Users
                        </h2>


                        <p>
                            Manage all registered customers.
                        </p>


                    </div>



                    <!-- =========================================
                     ADMIN CARD
                ========================================== -->

                    <div
                        class="admin-card">


                        <div
                            class="row align-items-center">


                            <!-- ---------------------------------
                             ADMIN PROFILE
                        ---------------------------------- -->

                            <div
                                class="col-lg-5 col-md-6 mb-3 mb-md-0">


                                <div
                                    class="d-flex align-items-center gap-3">


                                    <img
                                        src="<?php
                                                echo htmlspecialchars(
                                                    $admin_image
                                                );
                                                ?>"
                                        alt="Admin">


                                    <div>


                                        <div
                                            class="admin-name">


                                            <?php

                                            echo htmlspecialchars(
                                                $admin['name']
                                            );

                                            ?>


                                        </div>


                                        <div
                                            class="admin-detail">


                                            <i
                                                class="fa fa-phone">
                                            </i>


                                            <?php

                                            echo htmlspecialchars(
                                                $admin['phone']
                                            );

                                            ?>


                                        </div>


                                        <div
                                            class="mt-2">


                                            <span
                                                class="badge bg-dark">


                                                ADMIN


                                            </span>


                                        </div>


                                    </div>


                                </div>


                            </div>



                            <!-- ---------------------------------
                             LOGIN INFORMATION
                        ---------------------------------- -->

                            <div
                                class="col-lg-7 col-md-6">


                                <div
                                    class="row">


                                    <!-- LOGIN DATE -->

                                    <div
                                        class="col-sm-6 mb-3 mb-sm-0">


                                        <div
                                            class="admin-detail">


                                            Login Date


                                        </div>


                                        <strong>


                                            <?php

                                            echo date(
                                                "d-m-Y",
                                                strtotime(
                                                    $login_date_time
                                                )
                                            );

                                            ?>


                                        </strong>


                                    </div>



                                    <!-- LOGIN TIME -->

                                    <div
                                        class="col-sm-6">


                                        <div
                                            class="admin-detail">


                                            Login Time


                                        </div>


                                        <strong>


                                            <?php

                                            echo date(
                                                "h:i A",
                                                strtotime(
                                                    $login_date_time
                                                )
                                            );

                                            ?>


                                        </strong>


                                    </div>


                                </div>


                            </div>


                        </div>


                    </div>



                    <!-- =========================================
                     SUMMARY CARDS
                ========================================== -->

                    <div
                        class="row">


                        <!-- TOTAL CUSTOMERS -->

                        <div
                            class="col-lg-4 col-md-6 mb-3">


                            <div
                                class="summary-card">


                                <div
                                    class="summary-icon">


                                    <i
                                        class="fa fa-users">
                                    </i>


                                </div>


                                <div
                                    class="summary-number">


                                    <?php

                                    echo $total_users;

                                    ?>


                                </div>


                                <div
                                    class="summary-title">


                                    Total Customers


                                </div>


                            </div>


                        </div>



                        <!-- ADMIN -->

                        <div
                            class="col-lg-4 col-md-6 mb-3">


                            <div
                                class="summary-card">


                                <div
                                    class="summary-icon">


                                    <i
                                        class="fa fa-user">
                                    </i>


                                </div>


                                <div
                                    class="summary-number">


                                    <?php

                                    echo htmlspecialchars(
                                        $admin['name']
                                    );

                                    ?>


                                </div>


                                <div
                                    class="summary-title">


                                    Logged In Admin


                                </div>


                            </div>


                        </div>



                        <!-- LOGIN TIME -->

                        <div
                            class="col-lg-4 col-md-6 mb-3">


                            <div
                                class="summary-card">


                                <div
                                    class="summary-icon">


                                    <i
                                        class="fa fa-clock-o">
                                    </i>


                                </div>


                                <div
                                    class="summary-number">


                                    <?php

                                    echo date(
                                        "h:i A",
                                        strtotime(
                                            $login_date_time
                                        )
                                    );

                                    ?>


                                </div>


                                <div
                                    class="summary-title">


                                    Current Login Time


                                </div>


                            </div>


                        </div>


                    </div>



                    <!-- =========================================
                     USERS TABLE
                ========================================== -->

                    <div
                        class="table-card">


                        <!-- TABLE HEADER -->

                        <div
                            class="table-card-header">


                            <h3>


                                <i
                                    class="fa fa-users">
                                </i>


                                All Customers


                            </h3>


                            <span
                                class="badge bg-primary">


                                <?php

                                echo $total_users;

                                ?>


                                Users


                            </span>


                        </div>



                        <!-- TABLE BODY -->

                        <div
                            class="table-card-body">


                            <?php if (!empty($users)) { ?>


                                <div
                                    class="table-responsive">


                                    <table
                                        class="table table-bordered table-hover user-table">


                                        <!-- =================================
                                         TABLE HEAD
                                    ================================== -->

                                        <thead>


                                            <tr>


                                                <th>
                                                    #
                                                </th>


                                                <th>
                                                    User
                                                </th>


                                                <th>
                                                    Contact
                                                </th>


                                                <th>
                                                    Gender
                                                </th>


                                                <th>
                                                    Date of Birth
                                                </th>


                                                <th>
                                                    Role
                                                </th>


                                                <th>
                                                    Status
                                                </th>


                                                <th>
                                                    Registered
                                                </th>


                                                <th>
                                                    Action
                                                </th>


                                            </tr>


                                        </thead>



                                        <!-- =================================
                                         TABLE BODY
                                    ================================== -->

                                        <tbody>


                                            <?php

                                            $count = 1;


                                            foreach (
                                                $users as $row
                                            ) {

                                            ?>


                                                <tr>


                                                    <!-- NUMBER -->

                                                    <td>


                                                        <?php

                                                        echo $count++;

                                                        ?>


                                                    </td>



                                                    <!-- USER -->

                                                    <td>


                                                        <div
                                                            class="d-flex align-items-center gap-2">


                                                            <?php

                                                            if (
                                                                !empty($row['profile_image'])
                                                            ) {

                                                                $user_image =
                                                                    "../" .
                                                                    ltrim(
                                                                        $row['profile_image'],
                                                                        "/"
                                                                    );
                                                            } else {

                                                                $user_image =
                                                                    "../assets/images/default-user.png";
                                                            }

                                                            ?>


                                                            <img
                                                                src="<?php
                                                                        echo htmlspecialchars(
                                                                            $user_image
                                                                        );
                                                                        ?>"
                                                                class="user-image"
                                                                alt="User">


                                                            <div>


                                                                <div
                                                                    class="user-name">


                                                                    <?php

                                                                    echo htmlspecialchars(
                                                                        $row['name']
                                                                    );

                                                                    ?>


                                                                </div>


                                                                <div
                                                                    class="user-id">


                                                                    ID:


                                                                    <?php

                                                                    echo (int)
                                                                    $row['user_id'];

                                                                    ?>


                                                                </div>


                                                            </div>


                                                        </div>


                                                    </td>



                                                    <!-- PHONE -->

                                                    <td>


                                                        <?php

                                                        if (
                                                            !empty($row['phone'])
                                                        ) {

                                                            echo htmlspecialchars(
                                                                $row['phone']
                                                            );
                                                        } else {

                                                            echo "-";
                                                        }

                                                        ?>


                                                    </td>



                                                    <!-- GENDER -->

                                                    <td>


                                                        <?php

                                                        if (
                                                            !empty($row['gender'])
                                                        ) {

                                                            echo htmlspecialchars(
                                                                ucfirst(
                                                                    $row['gender']
                                                                )
                                                            );
                                                        } else {

                                                            echo "-";
                                                        }

                                                        ?>


                                                    </td>



                                                    <!-- DATE OF BIRTH -->

                                                    <td>


                                                        <?php

                                                        if (
                                                            !empty($row['date_of_birth'])
                                                        ) {

                                                            echo date(
                                                                "d-m-Y",
                                                                strtotime(
                                                                    $row['date_of_birth']
                                                                )
                                                            );
                                                        } else {

                                                            echo "-";
                                                        }

                                                        ?>


                                                    </td>



                                                    <!-- ROLE -->

                                                    <td>


                                                        <span
                                                            class="badge bg-primary">


                                                            <?php

                                                            echo htmlspecialchars(
                                                                ucfirst(
                                                                    $row['role']
                                                                )
                                                            );

                                                            ?>


                                                        </span>


                                                    </td>



                                                    <!-- STATUS -->

                                                    <td>


                                                        <?php

                                                        if (
                                                            (int)
                                                            $row['status']
                                                            === 1
                                                        ) {

                                                        ?>


                                                            <span
                                                                class="badge bg-success">


                                                                Active


                                                            </span>


                                                        <?php

                                                        } else {

                                                        ?>


                                                            <span
                                                                class="badge bg-danger">


                                                                Inactive


                                                            </span>


                                                        <?php

                                                        }

                                                        ?>


                                                    </td>



                                                    <!-- REGISTERED -->

                                                    <td>


                                                        <?php

                                                        if (
                                                            !empty($row['created_at'])
                                                        ) {


                                                            echo date(
                                                                "d-m-Y",
                                                                strtotime(
                                                                    $row['created_at']
                                                                )
                                                            );

                                                        ?>


                                                            <br>


                                                            <small
                                                                class="text-muted">


                                                                <?php

                                                                echo date(
                                                                    "h:i A",
                                                                    strtotime(
                                                                        $row['created_at']
                                                                    )
                                                                );

                                                                ?>


                                                            </small>


                                                        <?php

                                                        } else {

                                                            echo "-";
                                                        }

                                                        ?>


                                                    </td>



                                                    <!-- ACTION -->

                                                    <td>


                                                        <div
                                                            class="d-flex gap-1">


                                                            <!-- =================
                                                         EDIT BUTTON
                                                    ================== -->

                                                            <a
                                                                href="edit_user.php?id=<?php
                                                                                        echo urlencode(
                                                                                            $row['user_id']
                                                                                        );
                                                                                        ?>"
                                                                class="btn btn-sm btn-primary">


                                                                <i
                                                                    class="fa fa-edit">
                                                                </i>


                                                                Edit


                                                            </a>



                                                            <!-- =================
                                                         DELETE FORM
                                                    ================== -->

                                                            <form
                                                                method="POST"
                                                                action="All_user.php"
                                                                style="display:inline;"
                                                                onsubmit="return confirm('Are you sure you want to delete this user?');">


                                                                <input
                                                                    type="hidden"
                                                                    name="delete_user_id"
                                                                    value="<?php
                                                                            echo (int)
                                                                            $row['user_id'];
                                                                            ?>">


                                                                <button
                                                                    type="submit"
                                                                    name="delete_user"
                                                                    value="1"
                                                                    class="btn btn-sm btn-danger">


                                                                    <i
                                                                        class="fa fa-trash">
                                                                    </i>


                                                                    Delete


                                                                </button>


                                                            </form>


                                                        </div>


                                                    </td>


                                                </tr>


                                            <?php

                                            }

                                            ?>


                                        </tbody>


                                    </table>


                                </div>


                            <?php } else { ?>


                                <!-- =================================
                                 NO USERS
                            ================================== -->

                                <div
                                    class="text-center p-4">


                                    <i
                                        class="fa fa-users"
                                        style="font-size:40px; opacity:0.4;">
                                    </i>


                                    <h4
                                        class="mt-3">


                                        No Users Found


                                    </h4>


                                    <p
                                        class="text-muted">


                                        No registered customers are available.


                                    </p>


                                </div>


                            <?php } ?>


                        </div>


                    </div>


                </div>


            </div>



            <!-- =================================================
             FOOTER
        ================================================== -->

            <?php include 'footer.php'; ?>


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
        src="assets/vendors/Chart.js/dist/Chart.min.js">
    </script>

    <script
        src="assets/vendors/gauge.js/dist/gauge.min.js">
    </script>

    <script
        src="assets/vendors/bootstrap-progressbar/bootstrap-progressbar.min.js">
    </script>

    <script
        src="assets/vendors/iCheck/icheck.min.js">
    </script>

    <script
        src="assets/vendors/skycons/skycons.js">
    </script>

    <script
        src="assets/vendors/Flot/jquery.flot.js">
    </script>

    <script
        src="assets/vendors/Flot/jquery.flot.pie.js">
    </script>

    <script
        src="assets/vendors/Flot/jquery.flot.time.js">
    </script>

    <script
        src="assets/vendors/Flot/jquery.flot.stack.js">
    </script>

    <script
        src="assets/vendors/Flot/jquery.flot.resize.js">
    </script>

    <script
        src="assets/vendors/flot.orderbars/js/jquery.flot.orderBars.js">
    </script>

    <script
        src="assets/vendors/flot-spline/js/jquery.flot.spline.min.js">
    </script>

    <script
        src="assets/vendors/flot.curvedlines/curvedLines.js">
    </script>

    <script
        src="assets/vendors/DateJS/build/date.js">
    </script>

    <script
        src="assets/vendors/jqvmap/dist/jquery.vmap.js">
    </script>

    <script
        src="assets/vendors/jqvmap/dist/maps/jquery.vmap.world.js">
    </script>

    <script
        src="assets/vendors/jqvmap/examples/js/jquery.vmap.sampledata.js">
    </script>

    <script
        src="assets/vendors/moment/min/moment.min.js">
    </script>

    <script
        src="assets/vendors/bootstrap-daterangepicker/daterangepicker.js">
    </script>

    <script
        src="assets/js/custom.min.js">
    </script>

    <script
        src="assets/js/custom-popup.js">
    </script>


</body>

</html>