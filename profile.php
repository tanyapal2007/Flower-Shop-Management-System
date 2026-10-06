<?php

/* =========================================================
   SESSION START
========================================================= */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/* =========================================================
   DATABASE CONNECTION
========================================================= */

require_once "config/database.php";


/* =========================================================
   CHECK LOGIN
========================================================= */

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = (int) $_SESSION['user_id'];


/* =========================================================
   VARIABLES
========================================================= */

$success_message = "";
$error_message = "";

$user = [];
$profile = [];


/* =========================================================
   FETCH USER DETAILS
========================================================= */

$stmt = $conn->prepare("
    SELECT
        user_id,
        name,
        email,
        phone,
        role,
        status,
        created_at
    FROM users
    WHERE user_id = :user_id
    LIMIT 1
");

$stmt->execute([
    ':user_id' => $user_id
]);

$user = $stmt->fetch(PDO::FETCH_ASSOC);


if (!$user) {

    session_destroy();

    header("Location: login.php");
    exit;
}


/* =========================================================
   FETCH USER PROFILE
========================================================= */

$stmt = $conn->prepare("
    SELECT
        profile_id,
        user_id,
        profile_image,
        gender,
        date_of_birth,
        bio,
        address,
        city,
        state,
        pincode,
        country
    FROM user_profile
    WHERE user_id = :user_id
    LIMIT 1
");

$stmt->execute([
    ':user_id' => $user_id
]);

$profile = $stmt->fetch(PDO::FETCH_ASSOC);


/* =========================================================
   CREATE PROFILE IF NOT EXISTS
========================================================= */

if (!$profile) {

    $stmt = $conn->prepare("
        INSERT INTO user_profile
        (
            user_id,
            country
        )
        VALUES
        (
            :user_id,
            'India'
        )
    ");

    $stmt->execute([
        ':user_id' => $user_id
    ]);


    /* =====================================================
       FETCH PROFILE AGAIN
    ===================================================== */

    $stmt = $conn->prepare("
        SELECT
            profile_id,
            user_id,
            profile_image,
            gender,
            date_of_birth,
            bio,
            address,
            city,
            state,
            pincode,
            country
        FROM user_profile
        WHERE user_id = :user_id
        LIMIT 1
    ");

    $stmt->execute([
        ':user_id' => $user_id
    ]);

    $profile = $stmt->fetch(PDO::FETCH_ASSOC);
}


/* =========================================================
   UPDATE PROFILE
========================================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    $gender = trim($_POST['gender'] ?? '');
    $date_of_birth = trim($_POST['date_of_birth'] ?? '');
    $bio = trim($_POST['bio'] ?? '');

    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $state = trim($_POST['state'] ?? '');
    $pincode = trim($_POST['pincode'] ?? '');
    $country = trim($_POST['country'] ?? 'India');


    /* =====================================================
       VALIDATION
    ===================================================== */

    if ($name === '') {

        $error_message = "Please enter your name.";
    } elseif ($email === '') {

        $error_message = "Please enter your email.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error_message = "Please enter a valid email address.";
    } elseif ($phone === '') {

        $error_message = "Please enter your phone number.";
    } else {

        try {

            /* =============================================
               CHECK EMAIL DUPLICATE
            ============================================= */

            $stmt = $conn->prepare("
                SELECT user_id
                FROM users
                WHERE email = :email
                AND user_id != :user_id
                LIMIT 1
            ");

            $stmt->execute([
                ':email' => $email,
                ':user_id' => $user_id
            ]);

            if ($stmt->fetch()) {

                $error_message =
                    "This email address is already used by another user.";
            } else {

                /* =============================================
                   CHECK PHONE DUPLICATE
                ============================================= */

                $stmt = $conn->prepare("
                    SELECT user_id
                    FROM users
                    WHERE phone = :phone
                    AND user_id != :user_id
                    LIMIT 1
                ");

                $stmt->execute([
                    ':phone' => $phone,
                    ':user_id' => $user_id
                ]);

                if ($stmt->fetch()) {

                    $error_message =
                        "This phone number is already used by another user.";
                } else {

                    /* =========================================
                       UPDATE USERS TABLE
                    ========================================= */

                    $stmt = $conn->prepare("
                        UPDATE users
                        SET
                            name = :name,
                            email = :email,
                            phone = :phone
                        WHERE user_id = :user_id
                    ");

                    $stmt->execute([
                        ':name' => $name,
                        ':email' => $email,
                        ':phone' => $phone,
                        ':user_id' => $user_id
                    ]);


                    /* =========================================
                       PROFILE IMAGE
                    ========================================= */

                    $profile_image =
                        $profile['profile_image'] ?? null;


                    if (
                        isset($_FILES['profile_image']) &&
                        $_FILES['profile_image']['error'] !== UPLOAD_ERR_NO_FILE
                    ) {

                        if (
                            $_FILES['profile_image']['error']
                            === UPLOAD_ERR_OK
                        ) {

                            $file_name =
                                $_FILES['profile_image']['name'];

                            $file_tmp =
                                $_FILES['profile_image']['tmp_name'];

                            $file_size =
                                $_FILES['profile_image']['size'];


                            /* =================================
                               ALLOWED EXTENSIONS
                            ================================= */

                            $allowed_extensions = [
                                'jpg',
                                'jpeg',
                                'png',
                                'webp'
                            ];


                            $extension = strtolower(
                                pathinfo(
                                    $file_name,
                                    PATHINFO_EXTENSION
                                )
                            );


                            /* =================================
                               CHECK EXTENSION
                            ================================= */

                            if (
                                !in_array(
                                    $extension,
                                    $allowed_extensions,
                                    true
                                )
                            ) {

                                throw new Exception(
                                    "Only JPG, JPEG, PNG and WEBP images are allowed."
                                );
                            }


                            /* =================================
                               MAX SIZE 2MB
                            ================================= */

                            if ($file_size > 2 * 1024 * 1024) {

                                throw new Exception(
                                    "Profile image size must be less than 2MB."
                                );
                            }


                            /* =================================
                               CREATE UPLOAD DIRECTORY
                            ================================= */

                            $upload_directory =
                                "uploads/profile/";


                            if (!is_dir($upload_directory)) {

                                mkdir(
                                    $upload_directory,
                                    0777,
                                    true
                                );
                            }


                            /* =================================
                               UNIQUE FILE NAME
                            ================================= */

                            $new_file_name =
                                "profile_" .
                                $user_id .
                                "_" .
                                time() .
                                "." .
                                $extension;


                            $upload_path =
                                $upload_directory .
                                $new_file_name;


                            /* =================================
                               MOVE IMAGE
                            ================================= */

                            if (
                                !move_uploaded_file(
                                    $file_tmp,
                                    $upload_path
                                )
                            ) {

                                throw new Exception(
                                    "Unable to upload profile image."
                                );
                            }


                            /* =================================
                               DELETE OLD IMAGE
                            ================================= */

                            if (
                                !empty($profile_image) &&
                                file_exists($profile_image)
                            ) {

                                @unlink($profile_image);
                            }


                            $profile_image =
                                $upload_path;
                        }
                    }


                    /* =========================================
                       UPDATE USER PROFILE TABLE
                    ========================================= */

                    $stmt = $conn->prepare("
                        UPDATE user_profile

                        SET
                            profile_image = :profile_image,
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
                    ");


                    $stmt->execute([

                        ':profile_image' =>
                        $profile_image,

                        ':gender' =>
                        $gender !== ''
                            ? $gender
                            : null,

                        ':date_of_birth' =>
                        $date_of_birth !== ''
                            ? $date_of_birth
                            : null,

                        ':bio' =>
                        $bio !== ''
                            ? $bio
                            : null,

                        ':address' =>
                        $address !== ''
                            ? $address
                            : null,

                        ':city' =>
                        $city !== ''
                            ? $city
                            : null,

                        ':state' =>
                        $state !== ''
                            ? $state
                            : null,

                        ':pincode' =>
                        $pincode !== ''
                            ? $pincode
                            : null,

                        ':country' =>
                        $country !== ''
                            ? $country
                            : 'India',

                        ':user_id' =>
                        $user_id
                    ]);


                    /* =================================================
                       SUCCESSFUL UPDATE
                       
                       DIRECTLY GO TO MY ACCOUNT
                    ================================================= */

                    header(
                        "Location: myaccount.php?profile_updated=1"
                    );

                    exit;
                }
            }
        } catch (PDOException $e) {

            $error_message =
                "Something went wrong. Please try again.";
        } catch (Exception $e) {

            $error_message =
                $e->getMessage();
        }
    }
}


/* =========================================================
   PROFILE IMAGE
========================================================= */

$profile_image =
    $profile['profile_image'] ?? '';


$profile_image_exists =
    !empty($profile_image) &&
    file_exists($profile_image);


/* =========================================================
   USER INITIAL
========================================================= */

$user_name =
    $user['name'] ?? 'User';


$user_initial =
    strtoupper(
        substr(
            trim($user_name),
            0,
            1
        )
    );

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>Edit Profile - Fior</title>


    <!-- =====================================================
         BOOTSTRAP
    ====================================================== -->

    <link
        rel="stylesheet"
        href="css/bootstrap.css">


    <!-- =====================================================
         MAIN CSS
    ====================================================== -->

    <link
        rel="stylesheet"
        href="css/style.css">


    <!-- =====================================================
         RESPONSIVE CSS
    ====================================================== -->

    <link
        rel="stylesheet"
        href="css/responsive.css">


    <!-- =====================================================
         FONT AWESOME
    ====================================================== -->

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">


    <style>
        /* =====================================================
           PROFILE SECTION
        ===================================================== */

        .profile_section {

            padding: 70px 0;

            background: #fafafa;

            min-height: 700px;
        }


        .profile_container {

            max-width: 950px;

            margin: 0 auto;
        }


        /* =====================================================
           PAGE TITLE
        ===================================================== */

        .profile_title {

            text-align: center;

            margin-bottom: 35px;
        }


        .profile_title h2 {

            font-size: 32px;

            font-weight: 700;

            color: #222;

            margin-bottom: 8px;
        }


        .profile_title p {

            color: #777;

            margin: 0;
        }


        /* =====================================================
           PROFILE CARD
        ===================================================== */

        .profile_card {

            background: #fff;

            border-radius: 10px;

            padding: 35px;

            box-shadow:
                0 3px 15px rgba(0, 0, 0, 0.08);
        }


        /* =====================================================
           PROFILE IMAGE
        ===================================================== */

        .profile_image_section {

            text-align: center;

            margin-bottom: 30px;
        }


        .profile_image {

            width: 110px;

            height: 110px;

            border-radius: 50%;

            object-fit: cover;

            border: 4px solid #ffe6ef;
        }


        .profile_initial {

            width: 110px;

            height: 110px;

            border-radius: 50%;

            background: #df2f68;

            color: #fff;

            display: flex;

            align-items: center;

            justify-content: center;

            margin: 0 auto;

            font-size: 42px;

            font-weight: 700;
        }


        .image_label {

            display: inline-block;

            margin-top: 15px;

            color: #df2f68;

            font-size: 14px;

            font-weight: 600;

            cursor: pointer;
        }


        .image_help {

            color: #888;

            font-size: 12px;

            margin-top: 5px;
        }


        /* =====================================================
           FORM
        ===================================================== */

        .form_group {

            margin-bottom: 20px;
        }


        .form_group label {

            display: block;

            margin-bottom: 7px;

            color: #333;

            font-weight: 600;

            font-size: 14px;
        }


        .form_group label i {

            color: #df2f68;

            width: 20px;
        }


        .form_group input,
        .form_group select,
        .form_group textarea {

            width: 100%;

            border: 1px solid #ddd;

            border-radius: 5px;

            padding: 11px 13px;

            font-size: 14px;

            outline: none;

            transition: 0.3s;
        }


        .form_group input:focus,
        .form_group select:focus,
        .form_group textarea:focus {

            border-color: #df2f68;

            box-shadow:
                0 0 0 2px rgba(223, 47, 104, 0.08);
        }


        .form_group textarea {

            min-height: 110px;

            resize: vertical;
        }


        /* =====================================================
           BUTTONS
        ===================================================== */

        .button_area {

            display: flex;

            gap: 12px;

            margin-top: 25px;
        }


        .save_btn {

            background: #df2f68;

            color: #fff;

            border: none;

            padding: 11px 25px;

            border-radius: 5px;

            font-size: 14px;

            font-weight: 600;

            cursor: pointer;
        }


        .save_btn:hover {

            background: #c92359;
        }


        .cancel_btn {

            background: #eee;

            color: #555;

            padding: 11px 25px;

            border-radius: 5px;

            text-decoration: none;

            font-size: 14px;

            font-weight: 600;
        }


        .cancel_btn:hover {

            background: #ddd;

            color: #333;

            text-decoration: none;
        }


        /* =====================================================
           MESSAGES
        ===================================================== */

        .success_message {

            background: #d1e7dd;

            color: #0f5132;

            border: 1px solid #badbcc;

            padding: 12px 15px;

            border-radius: 5px;

            margin-bottom: 25px;
        }


        .error_message {

            background: #f8d7da;

            color: #842029;

            border: 1px solid #f5c2c7;

            padding: 12px 15px;

            border-radius: 5px;

            margin-bottom: 25px;
        }


        /* =====================================================
           MOBILE
        ===================================================== */

        @media (max-width: 767px) {

            .profile_section {

                padding: 45px 15px;
            }


            .profile_title h2 {

                font-size: 26px;
            }


            .profile_card {

                padding: 20px;
            }


            .button_area {

                flex-direction: column;
            }


            .save_btn,
            .cancel_btn {

                width: 100%;

                text-align: center;
            }
        }
    </style>

</head>


<body>


    <!-- =====================================================
         HEADER
    ===================================================== -->

    <?php

    if (file_exists("header.php")) {

        include "header.php";
    }

    ?>


    <!-- =====================================================
         PROFILE SECTION
    ===================================================== -->

    <section class="profile_section">

        <div class="container">

            <div class="profile_container">


                <!-- =================================================
                     PAGE TITLE
                ================================================== -->

                <div class="profile_title">

                    <h2>
                        Edit Profile
                    </h2>

                    <p>
                        Update your personal information
                    </p>

                </div>


                <!-- =================================================
                     PROFILE CARD
                ================================================== -->

                <div class="profile_card">


                    <!-- =================================================
                         ERROR MESSAGE
                    ================================================== -->

                    <?php if ($error_message !== '') { ?>

                        <div class="error_message">

                            <i class="fa fa-exclamation-circle"></i>

                            <?php

                            echo htmlspecialchars(
                                $error_message
                            );

                            ?>

                        </div>

                    <?php } ?>


                    <!-- =================================================
                         FORM
                    ================================================== -->

                    <form
                        method="POST"
                        enctype="multipart/form-data">


                        <!-- =================================================
                             PROFILE IMAGE
                        ================================================== -->

                        <div class="profile_image_section">


                            <?php if ($profile_image_exists) { ?>

                                <img
                                    src="<?php

                                            echo htmlspecialchars(
                                                $profile_image
                                            );

                                            ?>"
                                    class="profile_image"
                                    alt="Profile Image">

                            <?php } else { ?>

                                <div class="profile_initial">

                                    <?php

                                    echo htmlspecialchars(
                                        $user_initial
                                    );

                                    ?>

                                </div>

                            <?php } ?>


                            <label
                                for="profile_image"
                                class="image_label">

                                <i class="fa fa-camera"></i>

                                Change Profile Image

                            </label>


                            <input
                                type="file"
                                name="profile_image"
                                id="profile_image"
                                accept=".jpg,.jpeg,.png,.webp"
                                style="display:none;">


                            <div class="image_help">

                                JPG, JPEG, PNG or WEBP
                                (Maximum 2MB)

                            </div>

                        </div>


                        <!-- =================================================
                             BASIC INFORMATION
                        ================================================== -->

                        <div class="row">


                            <!-- NAME -->

                            <div class="col-md-6">

                                <div class="form_group">

                                    <label>

                                        <i class="fa fa-user"></i>

                                        Full Name

                                    </label>

                                    <input
                                        type="text"
                                        name="name"
                                        value="<?php

                                                echo htmlspecialchars(
                                                    $user['name'] ?? ''
                                                );

                                                ?>"
                                        placeholder="Enter your name"
                                        required>

                                </div>

                            </div>


                            <!-- EMAIL -->

                            <div class="col-md-6">

                                <div class="form_group">

                                    <label>

                                        <i class="fa fa-envelope"></i>

                                        Email

                                    </label>

                                    <input
                                        type="email"
                                        name="email"
                                        value="<?php

                                                echo htmlspecialchars(
                                                    $user['email'] ?? ''
                                                );

                                                ?>"
                                        placeholder="Enter your email"
                                        required>

                                </div>

                            </div>


                            <!-- PHONE -->

                            <div class="col-md-6">

                                <div class="form_group">

                                    <label>

                                        <i class="fa fa-phone"></i>

                                        Phone Number

                                    </label>

                                    <input
                                        type="text"
                                        name="phone"
                                        value="<?php

                                                echo htmlspecialchars(
                                                    $user['phone'] ?? ''
                                                );

                                                ?>"
                                        placeholder="Enter phone number"
                                        required>

                                </div>

                            </div>


                            <!-- GENDER -->

                            <div class="col-md-6">

                                <div class="form_group">

                                    <label>

                                        <i class="fa fa-venus-mars"></i>

                                        Gender

                                    </label>

                                    <select name="gender">

                                        <option value="">
                                            Select Gender
                                        </option>

                                        <option
                                            value="Male"
                                            <?php

                                            echo (
                                                ($profile['gender'] ?? '')
                                                === 'Male'
                                            )
                                                ? 'selected'
                                                : '';

                                            ?>>

                                            Male

                                        </option>

                                        <option
                                            value="Female"
                                            <?php

                                            echo (
                                                ($profile['gender'] ?? '')
                                                === 'Female'
                                            )
                                                ? 'selected'
                                                : '';

                                            ?>>

                                            Female

                                        </option>

                                        <option
                                            value="Other"
                                            <?php

                                            echo (
                                                ($profile['gender'] ?? '')
                                                === 'Other'
                                            )
                                                ? 'selected'
                                                : '';

                                            ?>>

                                            Other

                                        </option>

                                    </select>

                                </div>

                            </div>


                            <!-- DATE OF BIRTH -->

                            <div class="col-md-6">

                                <div class="form_group">

                                    <label>

                                        <i class="fa fa-calendar"></i>

                                        Date of Birth

                                    </label>

                                    <input
                                        type="date"
                                        name="date_of_birth"
                                        value="<?php

                                                echo htmlspecialchars(
                                                    $profile['date_of_birth'] ?? ''
                                                );

                                                ?>">

                                </div>

                            </div>


                            <!-- COUNTRY -->

                            <div class="col-md-6">

                                <div class="form_group">

                                    <label>

                                        <i class="fa fa-globe"></i>

                                        Country

                                    </label>

                                    <input
                                        type="text"
                                        name="country"
                                        value="<?php

                                                echo htmlspecialchars(
                                                    $profile['country'] ?? 'India'
                                                );

                                                ?>"
                                        placeholder="Enter country">

                                </div>

                            </div>


                            <!-- CITY -->

                            <div class="col-md-6">

                                <div class="form_group">

                                    <label>

                                        <i class="fa fa-city"></i>

                                        City

                                    </label>

                                    <input
                                        type="text"
                                        name="city"
                                        value="<?php

                                                echo htmlspecialchars(
                                                    $profile['city'] ?? ''
                                                );

                                                ?>"
                                        placeholder="Enter city">

                                </div>

                            </div>


                            <!-- STATE -->

                            <div class="col-md-6">

                                <div class="form_group">

                                    <label>

                                        <i class="fa fa-map"></i>

                                        State

                                    </label>

                                    <input
                                        type="text"
                                        name="state"
                                        value="<?php

                                                echo htmlspecialchars(
                                                    $profile['state'] ?? ''
                                                );

                                                ?>"
                                        placeholder="Enter state">

                                </div>

                            </div>


                            <!-- PINCODE -->

                            <div class="col-md-6">

                                <div class="form_group">

                                    <label>

                                        <i class="fa fa-location-dot"></i>

                                        Pincode

                                    </label>

                                    <input
                                        type="text"
                                        name="pincode"
                                        value="<?php

                                                echo htmlspecialchars(
                                                    $profile['pincode'] ?? ''
                                                );

                                                ?>"
                                        placeholder="Enter pincode">

                                </div>

                            </div>


                            <!-- ADDRESS -->

                            <div class="col-md-6">

                                <div class="form_group">

                                    <label>

                                        <i class="fa fa-house"></i>

                                        Address

                                    </label>

                                    <input
                                        type="text"
                                        name="address"
                                        value="<?php

                                                echo htmlspecialchars(
                                                    $profile['address'] ?? ''
                                                );

                                                ?>"
                                        placeholder="Enter address">

                                </div>

                            </div>


                            <!-- BIO -->

                            <div class="col-12">

                                <div class="form_group">

                                    <label>

                                        <i class="fa fa-align-left"></i>

                                        Bio

                                    </label>

                                    <textarea
                                        name="bio"
                                        placeholder="Write something about yourself"><?php

                                                                                        echo htmlspecialchars(
                                                                                            $profile['bio'] ?? ''
                                                                                        );

                                                                                        ?></textarea>

                                </div>

                            </div>

                        </div>


                        <!-- =================================================
                             BUTTONS
                        ================================================== -->

                        <div class="button_area">


                            <button
                                type="submit"
                                class="save_btn">

                                <i class="fa fa-save"></i>

                                Save Changes

                            </button>


                            <a
                                href="myaccount.php"
                                class="cancel_btn">

                                <i class="fa fa-arrow-left"></i>

                                Back to My Account

                            </a>

                        </div>


                    </form>

                </div>

            </div>

        </div>

    </section>


    <!-- =====================================================
         FOOTER
    ===================================================== -->

    <?php

    if (file_exists("footer.php")) {

        include "footer.php";
    }

    ?>


    <!-- =====================================================
         JS
    ===================================================== -->

    <script src="js/jquery-3.4.1.min.js"></script>

    <script src="js/bootstrap.js"></script>

    <script src="js/custom.js"></script>


</body>

</html>