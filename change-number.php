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

$success_message = '';
$error_message = '';

$current_phone = '';
$new_phone = '';

$user_phone = '';


/* =========================================================
   GET CURRENT PHONE NUMBER
========================================================= */

$stmt = $conn->prepare("
    SELECT phone
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


$user_phone = $user['phone'] ?? '';


/* =========================================================
   CHANGE NUMBER
========================================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $current_phone = trim($_POST['current_phone'] ?? '');
    $new_phone = trim($_POST['new_phone'] ?? '');


    /* =====================================================
       VALIDATION
    ===================================================== */

    if (empty($current_phone) || empty($new_phone)) {

        $error_message = "Please fill all phone number fields.";
    } elseif ($current_phone !== $user_phone) {

        $error_message = "Current phone number is incorrect.";
    } elseif ($current_phone === $new_phone) {

        $error_message = "New phone number must be different from current number.";
    } elseif (!preg_match('/^[0-9]{10}$/', $new_phone)) {

        $error_message = "Please enter a valid 10 digit phone number.";
    } else {

        try {

            /* =============================================
               CHECK NEW NUMBER ALREADY EXISTS
            ============================================= */

            $stmt = $conn->prepare("
                SELECT user_id
                FROM users
                WHERE phone = :phone
                  AND user_id != :user_id
                LIMIT 1
            ");

            $stmt->execute([
                ':phone' => $new_phone,
                ':user_id' => $user_id
            ]);

            $existing_user = $stmt->fetch(PDO::FETCH_ASSOC);


            if ($existing_user) {

                $error_message = "This phone number is already registered.";
            } else {

                /* =========================================
                   UPDATE PHONE NUMBER
                ========================================= */

                $stmt = $conn->prepare("
                    UPDATE users
                    SET phone = :phone
                    WHERE user_id = :user_id
                ");

                $stmt->execute([
                    ':phone' => $new_phone,
                    ':user_id' => $user_id
                ]);


                /* =========================================
                   SUCCESS
                ========================================= */

                $success_message = "Phone number changed successfully!";


                /* Update current phone */

                $user_phone = $new_phone;

                $current_phone = '';
                $new_phone = '';
            }
        } catch (PDOException $e) {

            $error_message = "Something went wrong. Please try again.";
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>Change Number - Fior</title>


    <!-- Bootstrap -->

    <link
        rel="stylesheet"
        href="css/bootstrap.css">


    <!-- Main CSS -->

    <link
        rel="stylesheet"
        href="css/style.css">


    <!-- Responsive CSS -->

    <link
        rel="stylesheet"
        href="css/responsive.css">


    <!-- Font Awesome -->

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">


    <style>
        /* =====================================================
           CHANGE NUMBER PAGE
        ===================================================== */

        .change_number_section {
            padding: 70px 0;
            background: #fafafa;
            min-height: 650px;
        }


        .change_number_container {
            max-width: 600px;
            margin: 0 auto;
        }


        /* =====================================================
           PAGE TITLE
        ===================================================== */

        .change_number_title {
            text-align: center;
            margin-bottom: 35px;
        }


        .change_number_title h2 {
            font-size: 32px;
            font-weight: 700;
            color: #222;
            margin-bottom: 8px;
        }


        .change_number_title p {
            color: #777;
            margin: 0;
        }


        /* =====================================================
           FORM BOX
        ===================================================== */

        .number_box {
            background: #fff;
            padding: 35px;
            border-radius: 10px;
            box-shadow: 0 3px 15px rgba(0, 0, 0, 0.08);
        }


        /* =====================================================
           MESSAGES
        ===================================================== */

        .success_message {
            background: #e7f8ed;
            color: #198754;
            border: 1px solid #b7e4c7;
            padding: 13px 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
        }


        .error_message {
            background: #f8d7da;
            color: #842029;
            border: 1px solid #f1aeb5;
            padding: 13px 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
        }


        /* =====================================================
           FORM
        ===================================================== */

        .number_form_group {
            margin-bottom: 20px;
        }


        .number_form_group label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            font-weight: 600;
            color: #333;
        }


        .number_input_box {
            position: relative;
        }


        .number_input_box input {
            width: 100%;
            height: 48px;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 0 15px;
            font-size: 14px;
            outline: none;
            transition: 0.3s;
        }


        .number_input_box input:focus {
            border-color: #df2f68;
            box-shadow: 0 0 0 2px rgba(223, 47, 104, 0.08);
        }


        /* =====================================================
           BUTTONS
        ===================================================== */

        .number_buttons {
            display: flex;
            gap: 12px;
            margin-top: 25px;
        }


        .change_number_btn {
            border: none;
            background: #df2f68;
            color: #fff;
            padding: 11px 22px;
            border-radius: 5px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: 0.3s;
        }


        .change_number_btn:hover {
            background: #c92359;
            color: #fff;
            text-decoration: none;
        }


        .back_account_btn {
            background: #eee;
            color: #555;
            padding: 11px 22px;
            border-radius: 5px;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            transition: 0.3s;
        }


        .back_account_btn:hover {
            background: #ddd;
            color: #333;
            text-decoration: none;
        }


        /* =====================================================
           NOTE
        ===================================================== */

        .number_note {
            margin-top: 20px;
            padding: 12px 15px;
            background: #fff7fa;
            border-left: 3px solid #df2f68;
            color: #777;
            font-size: 13px;
            line-height: 1.6;
        }


        /* =====================================================
           MOBILE
        ===================================================== */

        @media (max-width: 767px) {

            .change_number_section {
                padding: 45px 15px;
            }


            .change_number_title h2 {
                font-size: 26px;
            }


            .number_box {
                padding: 22px;
            }


            .number_buttons {
                flex-direction: column;
            }


            .change_number_btn,
            .back_account_btn {
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
         CHANGE NUMBER SECTION
    ===================================================== -->

    <section class="change_number_section">

        <div class="container">

            <div class="change_number_container">


                <!-- PAGE TITLE -->

                <div class="change_number_title">

                    <h2>
                        Change Number
                    </h2>

                    <p>
                        Update your registered phone number
                    </p>

                </div>


                <!-- FORM BOX -->

                <div class="number_box">


                    <!-- SUCCESS MESSAGE -->

                    <?php if (!empty($success_message)) { ?>

                        <div class="success_message">

                            <i class="fa fa-check-circle"></i>

                            <?php
                            echo htmlspecialchars($success_message);
                            ?>

                        </div>

                    <?php } ?>


                    <!-- ERROR MESSAGE -->

                    <?php if (!empty($error_message)) { ?>

                        <div class="error_message">

                            <i class="fa fa-exclamation-circle"></i>

                            <?php
                            echo htmlspecialchars($error_message);
                            ?>

                        </div>

                    <?php } ?>


                    <!-- FORM -->

                    <form method="POST" action="">


                        <!-- CURRENT NUMBER -->

                        <div class="number_form_group">

                            <label>
                                Current Phone Number
                            </label>

                            <div class="number_input_box">

                                <input
                                    type="text"
                                    name="current_phone"
                                    placeholder="Enter current phone number"
                                    maxlength="10"
                                    inputmode="numeric"
                                    value="<?php echo htmlspecialchars($current_phone); ?>"
                                    required>

                            </div>

                        </div>


                        <!-- NEW NUMBER -->

                        <div class="number_form_group">

                            <label>
                                New Phone Number
                            </label>

                            <div class="number_input_box">

                                <input
                                    type="text"
                                    name="new_phone"
                                    placeholder="Enter new phone number"
                                    maxlength="10"
                                    inputmode="numeric"
                                    value="<?php echo htmlspecialchars($new_phone); ?>"
                                    required>

                            </div>

                        </div>


                        <!-- BUTTONS -->

                        <div class="number_buttons">

                            <button
                                type="submit"
                                class="change_number_btn">
                                <i class="fa fa-phone"></i>
                                Change Number
                            </button>


                            <a
                                href="myaccount.php"
                                class="back_account_btn">
                                <i class="fa fa-arrow-left"></i>
                                Back to My Account
                            </a>

                        </div>

                    </form>


                    <!-- NOTE -->

                    <div class="number_note">

                        <i class="fa fa-info-circle"></i>

                        Please enter a valid 10 digit phone number.

                    </div>


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