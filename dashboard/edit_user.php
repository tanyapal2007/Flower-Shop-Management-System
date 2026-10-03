<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../config/database.php";


// ======================================================
// 1. CHECK LOGIN
// ======================================================

if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {

    header("Location: ../login.php");
    exit;
}

$login_user_id = $_SESSION['user_id'];


// ======================================================
// 2. CHECK ADMIN
// ======================================================

$admin_sql = "
    SELECT
        user_id,
        name,
        role
    FROM users
    WHERE user_id = :user_id
    LIMIT 1
";

$admin_stmt = $conn->prepare($admin_sql);

$admin_stmt->execute([
    ':user_id' => $login_user_id
]);

$admin = $admin_stmt->fetch(PDO::FETCH_ASSOC);


if (!$admin || strtolower($admin['role']) !== 'admin') {

    header("Location: ../index.php");
    exit;
}


// ======================================================
// 3. GET USER ID
// ======================================================

$user_id = isset($_GET['id'])
    ? (int)$_GET['id']
    : 0;


if ($user_id <= 0) {

    header("Location: All_user.php");
    exit;
}


// ======================================================
// 4. GET USER DATA
// ======================================================

$user_sql = "
    SELECT

        u.user_id,
        u.name,
        u.email,
        u.phone,
        u.role,
        u.status,
        u.created_at,

        up.profile_id,
        up.profile_image,
        up.gender,
        up.date_of_birth,
        up.bio,
        up.address,
        up.city,
        up.state,
        up.pincode,
        up.country

    FROM users u

    LEFT JOIN user_profile up
        ON u.user_id = up.user_id

    WHERE u.user_id = :user_id

    LIMIT 1
";

$user_stmt = $conn->prepare($user_sql);

$user_stmt->execute([
    ':user_id' => $user_id
]);

$user = $user_stmt->fetch(PDO::FETCH_ASSOC);


// ======================================================
// 5. USER NOT FOUND
// ======================================================

if (!$user) {

    die("User not found.");
}


// ======================================================
// 6. DEFAULT VALUES
// ======================================================

$user['gender'] =
    $user['gender'] ?? '';

$user['date_of_birth'] =
    $user['date_of_birth'] ?? '';

$user['bio'] =
    $user['bio'] ?? '';

$user['address'] =
    $user['address'] ?? '';

$user['city'] =
    $user['city'] ?? '';

$user['state'] =
    $user['state'] ?? '';

$user['pincode'] =
    $user['pincode'] ?? '';

$user['country'] =
    $user['country'] ?? 'India';

$user['status'] =
    $user['status'] ?? 1;


// ======================================================
// 7. UPDATE USER
// ======================================================

$error = "";


if (isset($_POST['update_user'])) {


    // ==================================================
    // GET FORM DATA
    // ==================================================

    $name = trim($_POST['name'] ?? '');

    $email = trim($_POST['email'] ?? '');

    $phone = trim($_POST['phone'] ?? '');

    $gender = trim($_POST['gender'] ?? '');

    $date_of_birth =
        trim($_POST['date_of_birth'] ?? '');

    $bio =
        trim($_POST['bio'] ?? '');

    $address =
        trim($_POST['address'] ?? '');

    $city =
        trim($_POST['city'] ?? '');

    $state =
        trim($_POST['state'] ?? '');

    $pincode =
        trim($_POST['pincode'] ?? '');

    $country =
        trim($_POST['country'] ?? '');

    $status =
        isset($_POST['status'])
        ? (int)$_POST['status']
        : 1;


    // ==================================================
    // VALIDATION
    // ==================================================

    if ($name === '') {

        $error = "Please enter user name.";
    } elseif ($email === '') {

        $error = "Please enter email.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = "Please enter a valid email address.";
    } elseif ($phone === '') {

        $error = "Please enter phone number.";
    } elseif (!preg_match('/^[6-9][0-9]{9}$/', $phone)) {

        $error = "Please enter a valid 10-digit mobile number.";
    }


    // ==================================================
    // UPDATE
    // ==================================================

    if ($error === '') {

        try {

            // Start transaction
            $conn->beginTransaction();


            // ==========================================
            // CHECK EMAIL
            // ==========================================

            $email_check = $conn->prepare("
                SELECT user_id
                FROM users
                WHERE LOWER(email) = LOWER(:email)
                AND user_id != :user_id
                LIMIT 1
            ");

            $email_check->execute([

                ':email' => $email,

                ':user_id' => $user_id

            ]);


            if ($email_check->fetch()) {

                throw new Exception(
                    "This email is already used by another user."
                );
            }


            // ==========================================
            // CHECK PHONE
            // ==========================================

            $phone_check = $conn->prepare("
                SELECT user_id
                FROM users
                WHERE phone = :phone
                AND user_id != :user_id
                LIMIT 1
            ");

            $phone_check->execute([

                ':phone' => $phone,

                ':user_id' => $user_id

            ]);


            if ($phone_check->fetch()) {

                throw new Exception(
                    "This phone number is already used by another user."
                );
            }


            // ==========================================
            // UPDATE USERS TABLE
            // ==========================================

            $update_user_sql = "
                UPDATE users
                SET
                    name = :name,
                    email = :email,
                    phone = :phone,
                    status = :status
                WHERE user_id = :user_id
            ";

            $update_user_stmt =
                $conn->prepare($update_user_sql);

            $update_user_stmt->execute([

                ':name' => $name,

                ':email' => $email,

                ':phone' => $phone,

                ':status' => $status,

                ':user_id' => $user_id

            ]);


            // ==========================================
            // CHECK USER PROFILE
            // ==========================================

            $profile_check = $conn->prepare("
                SELECT profile_id
                FROM user_profile
                WHERE user_id = :user_id
                LIMIT 1
            ");

            $profile_check->execute([

                ':user_id' => $user_id

            ]);

            $profile =
                $profile_check->fetch(PDO::FETCH_ASSOC);


            // ==========================================
            // UPDATE PROFILE
            // ==========================================

            if ($profile) {


                $update_profile_sql = "
                    UPDATE user_profile
                    SET

                        gender = :gender,

                        date_of_birth = :date_of_birth,

                        bio = :bio,

                        address = :address,

                        city = :city,

                        state = :state,

                        pincode = :pincode,

                        country = :country,

                        updated_at = CURRENT_TIMESTAMP

                    WHERE user_id = :user_id
                ";


                $update_profile_stmt =
                    $conn->prepare(
                        $update_profile_sql
                    );


                $update_profile_stmt->execute([

                    ':gender' => ($gender !== '')
                        ? $gender
                        : null,

                    ':date_of_birth' => ($date_of_birth !== '')
                        ? $date_of_birth
                        : null,

                    ':bio' => ($bio !== '')
                        ? $bio
                        : null,

                    ':address' => ($address !== '')
                        ? $address
                        : null,

                    ':city' => ($city !== '')
                        ? $city
                        : null,

                    ':state' => ($state !== '')
                        ? $state
                        : null,

                    ':pincode' => ($pincode !== '')
                        ? $pincode
                        : null,

                    ':country' => ($country !== '')
                        ? $country
                        : 'India',

                    ':user_id' =>
                    $user_id

                ]);
            } else {


                // ======================================
                // CREATE PROFILE
                // ======================================

                $insert_profile_sql = "
                    INSERT INTO user_profile
                    (
                        user_id,
                        gender,
                        date_of_birth,
                        bio,
                        address,
                        city,
                        state,
                        pincode,
                        country
                    )

                    VALUES
                    (
                        :user_id,
                        :gender,
                        :date_of_birth,
                        :bio,
                        :address,
                        :city,
                        :state,
                        :pincode,
                        :country
                    )
                ";


                $insert_profile_stmt =
                    $conn->prepare(
                        $insert_profile_sql
                    );


                $insert_profile_stmt->execute([

                    ':user_id' =>
                    $user_id,

                    ':gender' => ($gender !== '')
                        ? $gender
                        : null,

                    ':date_of_birth' => ($date_of_birth !== '')
                        ? $date_of_birth
                        : null,

                    ':bio' => ($bio !== '')
                        ? $bio
                        : null,

                    ':address' => ($address !== '')
                        ? $address
                        : null,

                    ':city' => ($city !== '')
                        ? $city
                        : null,

                    ':state' => ($state !== '')
                        ? $state
                        : null,

                    ':pincode' => ($pincode !== '')
                        ? $pincode
                        : null,

                    ':country' => ($country !== '')
                        ? $country
                        : 'India'

                ]);
            }


            // ==========================================
            // COMMIT DATABASE UPDATE
            // ==========================================

            $conn->commit();


            // ==========================================
            // REDIRECT TO ALL USERS
            // ==========================================

            header(
                "Location: All_user.php?updated=1"
            );

            exit;
        } catch (Exception $e) {


            // Rollback if transaction is active

            if ($conn->inTransaction()) {

                $conn->rollBack();
            }


            $error = $e->getMessage();
        }
    }
}


// ======================================================
// 8. PROFILE IMAGE
// ======================================================

if (
    !empty($user['profile_image'])
) {

    $profile_image =
        "../" .
        ltrim(
            $user['profile_image'],
            "/"
        );
} else {

    $profile_image =
        "../assets/images/default-user.png";
}

?>
<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1">

    <title>Edit User</title>


    <!-- Bootstrap -->

    <link
        href="assets/vendors/bootstrap/dist/css/bootstrap.min.css"
        rel="stylesheet">


    <!-- Font Awesome -->

    <link
        href="assets/vendors/font-awesome/css/font-awesome.min.css"
        rel="stylesheet">


    <!-- Custom Theme -->

    <link
        href="assets/css/custom.min.css"
        rel="stylesheet">


    <style>
        .edit-user-card {

            background: #ffffff;

            border-radius: 12px;

            padding: 30px;

            margin-bottom: 30px;

            box-shadow:
                0 3px 15px rgba(0, 0, 0, 0.08);
        }


        .page-heading {

            margin-bottom: 25px;
        }


        .page-heading h2 {

            margin-bottom: 5px;

            font-weight: 600;
        }


        .page-heading p {

            color: #777;

            margin: 0;
        }


        .form-group {

            margin-bottom: 20px;
        }


        .form-group label {

            font-weight: 600;

            margin-bottom: 8px;

            display: block;
        }


        .form-control {

            height: 45px;

            border-radius: 6px;
        }


        textarea.form-control {

            height: 110px;

            resize: vertical;
        }


        .user-profile-box {

            text-align: center;

            margin-bottom: 25px;
        }


        .user-profile-box img {

            width: 100px;

            height: 100px;

            object-fit: cover;

            border-radius: 50%;

            border: 3px solid #eeeeee;
        }


        .user-profile-name {

            margin-top: 10px;

            font-size: 20px;

            font-weight: 600;
        }


        .button-area {

            margin-top: 25px;

            display: flex;

            gap: 10px;
        }


        .error-message {

            background: #f8d7da;

            color: #721c24;

            padding: 12px 15px;

            border-radius: 6px;

            margin-bottom: 20px;
        }
    </style>

</head>


<body class="nav-md">


    <div class="container body">


        <div class="main_container">


            <?php include 'sidebar.php'; ?>


            <div
                class="right_col"
                role="main">


                <div class="content">


                    <!-- PAGE HEADING -->

                    <div class="page-heading">

                        <h2>
                            Edit User
                        </h2>

                        <p>
                            Update customer information and profile details.
                        </p>

                    </div>


                    <!-- ERROR -->

                    <?php if ($error !== '') { ?>

                        <div class="error-message">

                            <i class="fa fa-exclamation-circle"></i>

                            <?php
                            echo htmlspecialchars($error);
                            ?>

                        </div>

                    <?php } ?>


                    <!-- EDIT CARD -->

                    <div class="edit-user-card">


                        <!-- PROFILE -->

                        <div class="user-profile-box">


                            <img
                                src="<?php
                                        echo htmlspecialchars(
                                            $profile_image
                                        );
                                        ?>"
                                alt="User">


                            <div class="user-profile-name">

                                <?php
                                echo htmlspecialchars(
                                    $user['name']
                                );
                                ?>

                            </div>


                            <small class="text-muted">

                                User ID:

                                <?php
                                echo htmlspecialchars(
                                    $user['user_id']
                                );
                                ?>

                            </small>

                        </div>


                        <!-- FORM -->

                        <form
                            method="POST"
                            action="">


                            <div class="row">


                                <!-- NAME -->

                                <div class="col-md-6">

                                    <div class="form-group">

                                        <label>
                                            Full Name
                                        </label>

                                        <input
                                            type="text"
                                            name="name"
                                            class="form-control"
                                            value="<?php
                                                    echo htmlspecialchars(
                                                        $user['name']
                                                    );
                                                    ?>"
                                            required>

                                    </div>

                                </div>


                                <!-- EMAIL -->

                                <div class="col-md-6">

                                    <div class="form-group">

                                        <label>
                                            Email
                                        </label>

                                        <input
                                            type="email"
                                            name="email"
                                            class="form-control"
                                            value="<?php
                                                    echo htmlspecialchars(
                                                        $user['email']
                                                    );
                                                    ?>"
                                            required>

                                    </div>

                                </div>


                                <!-- PHONE -->

                                <div class="col-md-6">

                                    <div class="form-group">

                                        <label>
                                            Phone Number
                                        </label>

                                        <input
                                            type="text"
                                            name="phone"
                                            class="form-control"
                                            maxlength="10"
                                            value="<?php
                                                    echo htmlspecialchars(
                                                        $user['phone']
                                                    );
                                                    ?>"
                                            required>

                                    </div>

                                </div>


                                <!-- GENDER -->

                                <div class="col-md-6">

                                    <div class="form-group">

                                        <label>
                                            Gender
                                        </label>

                                        <select
                                            name="gender"
                                            class="form-control">

                                            <option value="">
                                                Select Gender
                                            </option>

                                            <option
                                                value="male"
                                                <?php
                                                echo (
                                                    strtolower(
                                                        $user['gender']
                                                    ) === 'male'
                                                )
                                                    ? 'selected'
                                                    : '';
                                                ?>>
                                                Male
                                            </option>

                                            <option
                                                value="female"
                                                <?php
                                                echo (
                                                    strtolower(
                                                        $user['gender']
                                                    ) === 'female'
                                                )
                                                    ? 'selected'
                                                    : '';
                                                ?>>
                                                Female
                                            </option>

                                            <option
                                                value="other"
                                                <?php
                                                echo (
                                                    strtolower(
                                                        $user['gender']
                                                    ) === 'other'
                                                )
                                                    ? 'selected'
                                                    : '';
                                                ?>>
                                                Other
                                            </option>

                                        </select>

                                    </div>

                                </div>


                                <!-- DOB -->

                                <div class="col-md-6">

                                    <div class="form-group">

                                        <label>
                                            Date of Birth
                                        </label>

                                        <input
                                            type="date"
                                            name="date_of_birth"
                                            class="form-control"
                                            value="<?php
                                                    echo htmlspecialchars(
                                                        $user['date_of_birth']
                                                    );
                                                    ?>">

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

                                            <option
                                                value="1"
                                                <?php
                                                echo (
                                                    (int)$user['status'] === 1
                                                )
                                                    ? 'selected'
                                                    : '';
                                                ?>>
                                                Active
                                            </option>

                                            <option
                                                value="0"
                                                <?php
                                                echo (
                                                    (int)$user['status'] === 0
                                                )
                                                    ? 'selected'
                                                    : '';
                                                ?>>
                                                Inactive
                                            </option>

                                        </select>

                                    </div>

                                </div>


                                <!-- ADDRESS -->

                                <div class="col-md-12">

                                    <div class="form-group">

                                        <label>
                                            Address
                                        </label>

                                        <textarea
                                            name="address"
                                            class="form-control"><?php
                                                                    echo htmlspecialchars(
                                                                        $user['address']
                                                                    );
                                                                    ?></textarea>

                                    </div>

                                </div>


                                <!-- CITY -->

                                <div class="col-md-4">

                                    <div class="form-group">

                                        <label>
                                            City
                                        </label>

                                        <input
                                            type="text"
                                            name="city"
                                            class="form-control"
                                            value="<?php
                                                    echo htmlspecialchars(
                                                        $user['city']
                                                    );
                                                    ?>">

                                    </div>

                                </div>


                                <!-- STATE -->

                                <div class="col-md-4">

                                    <div class="form-group">

                                        <label>
                                            State
                                        </label>

                                        <input
                                            type="text"
                                            name="state"
                                            class="form-control"
                                            value="<?php
                                                    echo htmlspecialchars(
                                                        $user['state']
                                                    );
                                                    ?>">

                                    </div>

                                </div>


                                <!-- PINCODE -->

                                <div class="col-md-4">

                                    <div class="form-group">

                                        <label>
                                            Pincode
                                        </label>

                                        <input
                                            type="text"
                                            name="pincode"
                                            class="form-control"
                                            maxlength="6"
                                            value="<?php
                                                    echo htmlspecialchars(
                                                        $user['pincode']
                                                    );
                                                    ?>">

                                    </div>

                                </div>


                                <!-- COUNTRY -->

                                <div class="col-md-6">

                                    <div class="form-group">

                                        <label>
                                            Country
                                        </label>

                                        <input
                                            type="text"
                                            name="country"
                                            class="form-control"
                                            value="<?php
                                                    echo htmlspecialchars(
                                                        $user['country']
                                                    );
                                                    ?>">

                                    </div>

                                </div>


                                <!-- BIO -->

                                <div class="col-md-6">

                                    <div class="form-group">

                                        <label>
                                            Bio
                                        </label>

                                        <textarea
                                            name="bio"
                                            class="form-control"><?php
                                                                    echo htmlspecialchars(
                                                                        $user['bio']
                                                                    );
                                                                    ?></textarea>

                                    </div>

                                </div>


                            </div>


                            <!-- BUTTONS -->

                            <div class="button-area">


                                <button
                                    type="submit"
                                    name="update_user"
                                    class="btn btn-primary">

                                    <i class="fa fa-save"></i>

                                    Update User

                                </button>


                                <a
                                    href="All_user.php"
                                    class="btn btn-secondary">

                                    <i class="fa fa-arrow-left"></i>

                                    Back to Users

                                </a>


                            </div>


                        </form>


                    </div>


                </div>

            </div>


            <?php include 'footer.php'; ?>


        </div>

    </div>


    <!-- JAVASCRIPT -->

    <script src="assets/vendors/jquery/dist/jquery.min.js"></script>

    <script src="assets/vendors/bootstrap/dist/js/bootstrap.min.js"></script>

    <script src="assets/vendors/fastclick/lib/fastclick.js"></script>

    <script src="assets/vendors/nprogress/nprogress.js"></script>

    <script src="assets/js/custom.min.js"></script>


</body>

</html>