<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "config/database.php";

$error = "";
$success = "";


/* =========================================================
   GENERATE OTP
========================================================= */

if (isset($_POST['generate_otp'])) {

    $username = trim($_POST['username'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if ($username === '') {

        $error = "Please enter your username.";
    } elseif (!preg_match('/^[6-9][0-9]{9}$/', $phone)) {

        $error = "Please enter a valid 10-digit mobile number.";
    } else {

        try {

            /* =================================================
               CHECK USER
            ================================================= */

            $stmt = $conn->prepare("
                SELECT
                    user_id,
                    name,
                    email,
                    phone,
                    role,
                    status
                FROM users
                WHERE LOWER(TRIM(name)) = LOWER(TRIM(:name))
                AND TRIM(phone) = TRIM(:phone)
                LIMIT 1
            ");

            $stmt->execute([
                ':name' => $username,
                ':phone' => $phone
            ]);

            $user = $stmt->fetch(PDO::FETCH_ASSOC);


            /* =================================================
               USER NOT FOUND
               CREATE NEW USER
            ================================================= */

            if (!$user) {

                /*
                 * New user ke liye email required hai
                 * kyunki users table mein email NOT NULL hai.
                 */

                $email = strtolower(
                    preg_replace('/[^a-zA-Z0-9]/', '', $username)
                );

                if ($email === '') {
                    $email = 'user';
                }

                $email = $email . $phone . '@fior.local';


                /* Check email duplicate */

                $emailCheck = $conn->prepare("
                    SELECT user_id
                    FROM users
                    WHERE email = :email
                    LIMIT 1
                ");

                $emailCheck->execute([
                    ':email' => $email
                ]);


                if ($emailCheck->fetch()) {

                    $email = 'user' . $phone . '@fior.local';
                }


                /* =================================================
                   INSERT NEW USER
                ================================================= */

                $insert = $conn->prepare("
                    INSERT INTO users
                    (
                        name,
                        email,
                        phone,
                        role,
                        status
                    )
                    VALUES
                    (
                        :name,
                        :email,
                        :phone,
                        'user',
                        1
                    )
                    RETURNING
                        user_id,
                        name,
                        email,
                        phone,
                        role,
                        status
                ");

                $insert->execute([
                    ':name' => $username,
                    ':email' => $email,
                    ':phone' => $phone
                ]);

                $user = $insert->fetch(PDO::FETCH_ASSOC);


                if (!$user) {

                    $error = "Unable to create user. Please try again.";
                } else {

                    $success = "New user created successfully. OTP generated.";
                }
            }


            /* =================================================
               EXISTING USER OR NEW USER
            ================================================= */

            if ($user) {

                /*
                 * IMPORTANT:
                 * Sirf existing admin ko admin rakho.
                 * Baaki sab user rahenge.
                 */

                if ($user['phone'] === '9979450222') {

                    $user['role'] = 'admin';
                } else {

                    $user['role'] = 'user';

                    /*
                     * Database mein bhi role user kar do.
                     */

                    $updateRole = $conn->prepare("
                        UPDATE users
                        SET role = 'user'
                        WHERE user_id = :user_id
                    ");

                    $updateRole->execute([
                        ':user_id' => $user['user_id']
                    ]);
                }


                /* =================================================
                   GENERATE 6 DIGIT OTP
                ================================================= */

                $otp = random_int(100000, 999999);


                /* =================================================
                   SAVE OTP SESSION
                ================================================= */

                $_SESSION['otp'] = $otp;
                $_SESSION['otp_time'] = time();

                $_SESSION['otp_user_id'] = $user['user_id'];
                $_SESSION['otp_name'] = $user['name'];
                $_SESSION['otp_email'] = $user['email'];
                $_SESSION['otp_phone'] = $user['phone'];
                $_SESSION['otp_role'] = $user['role'];

                $_SESSION['otp_generated'] = true;

                /* Testing purpose */

                $_SESSION['test_otp'] = $otp;


                if ($success === "") {

                    $success = "OTP generated successfully.";
                }
            }
        } catch (PDOException $e) {

            $error = "Database error: " . $e->getMessage();
        }
    }
}


/* =========================================================
   VERIFY OTP
========================================================= */

if (isset($_POST['verify_otp'])) {

    $entered_otp = trim($_POST['otp'] ?? '');


    if (!preg_match('/^[0-9]{6}$/', $entered_otp)) {

        $error = "Please enter a valid 6-digit OTP.";
    } elseif (
        !isset($_SESSION['otp_time']) ||
        (time() - $_SESSION['otp_time']) > 300
    ) {

        $error = "OTP has expired. Please generate a new OTP.";

        unset($_SESSION['otp']);
        unset($_SESSION['otp_time']);
        unset($_SESSION['otp_generated']);
        unset($_SESSION['test_otp']);
    } elseif (
        !isset($_SESSION['otp']) ||
        $entered_otp != $_SESSION['otp']
    ) {

        $error = "Invalid OTP. Please enter the correct OTP.";
    } else {

        /* =================================================
           LOGIN SUCCESS
        ================================================= */

        $_SESSION['login'] = true;

        $_SESSION['user_id'] = $_SESSION['otp_user_id'];
        $_SESSION['name'] = $_SESSION['otp_name'];
        $_SESSION['email'] = $_SESSION['otp_email'];
        $_SESSION['phone'] = $_SESSION['otp_phone'];
        $_SESSION['role'] = $_SESSION['otp_role'];


        $role = $_SESSION['role'];


        /* =================================================
           CLEAR OTP SESSION
        ================================================= */

        unset($_SESSION['otp']);
        unset($_SESSION['otp_time']);
        unset($_SESSION['otp_user_id']);
        unset($_SESSION['otp_name']);
        unset($_SESSION['otp_email']);
        unset($_SESSION['otp_phone']);
        unset($_SESSION['otp_role']);
        unset($_SESSION['otp_generated']);
        unset($_SESSION['test_otp']);


        /* =================================================
           REDIRECT
        ================================================= */

        if ($role === 'admin') {

            header("Location: dashboard/index3.php");
            exit();
        } else {

            header("Location: index.php");
            exit();
        }
    }
}

?>

<!DOCTYPE html>
<html>

<head>

    <meta charset="utf-8">

    <meta
        http-equiv="X-UA-Compatible"
        content="IE=edge">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <meta name="keywords" content="">

    <meta name="description" content="">

    <meta name="author" content="">

    <title>Fior - Login</title>


    <!-- Owl Carousel -->

    <link
        rel="stylesheet"
        type="text/css"
        href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.1.3/assets/owl.carousel.min.css">


    <!-- Bootstrap -->

    <link
        rel="stylesheet"
        type="text/css"
        href="css/bootstrap.css">


    <!-- Google Fonts -->

    <link
        href="https://fonts.googleapis.com/css?family=Baloo+Chettan|Poppins:400,600,700&display=swap"
        rel="stylesheet">


    <!-- Main CSS -->

    <link
        href="css/style.css"
        rel="stylesheet">


    <!-- Responsive CSS -->

    <link
        href="css/responsive.css"
        rel="stylesheet">


    <style>
        .login-page {
            padding: 80px 0;
            background: #fff;
        }

        .login-box {
            max-width: 480px;
            margin: 0 auto;
            padding: 40px;
            background: #fff;
            border: 1px solid #eeeeee;
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.08);
        }

        .login-title {
            text-align: center;
            margin-bottom: 30px;
        }

        .login-title h2 {
            font-size: 30px;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .login-title p {
            color: #777;
            margin: 0;
        }

        .login-form-group {
            margin-bottom: 20px;
        }

        .login-form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
        }

        .login-form-group input {
            width: 100%;
            height: 48px;
            padding: 0 15px;
            border: 1px solid #ddd;
            outline: none;
            font-size: 15px;
            transition: 0.3s;
        }

        .login-form-group input:focus {
            border-color: #e91e63;
        }

        .login-btn {
            width: 100%;
            height: 48px;
            border: none;
            background: #e91e63;
            color: #fff;
            font-size: 15px;
            cursor: pointer;
            transition: 0.3s;
        }

        .login-btn:hover {
            background: #d81b60;
        }

        .login-error {
            padding: 12px 15px;
            margin-bottom: 20px;
            background: #ffe8e8;
            color: #d32f2f;
            text-align: center;
            font-size: 14px;
        }

        .login-success {
            padding: 12px 15px;
            margin-bottom: 20px;
            background: #e8f5e9;
            color: #2e7d32;
            text-align: center;
            font-size: 14px;
        }

        .otp-box {
            margin-top: 30px;
            padding-top: 30px;
            border-top: 1px solid #eeeeee;
        }

        .otp-title {
            text-align: center;
            margin-bottom: 20px;
        }

        .otp-title h4 {
            margin-bottom: 8px;
            font-size: 20px;
        }

        .otp-title p {
            margin: 0;
            color: #777;
            font-size: 14px;
        }

        .otp-input {
            text-align: center;
            letter-spacing: 8px;
            font-size: 20px !important;
            font-weight: 600;
        }

        .test-otp {
            margin-top: 18px;
            padding: 12px;
            background: #f1f8f3;
            border: 1px solid #c8e6c9;
            color: #2e7d32;
            text-align: center;
            font-size: 14px;
        }

        @media (max-width: 576px) {

            .login-page {
                padding: 50px 15px;
            }

            .login-box {
                padding: 25px 20px;
            }

            .login-title h2 {
                font-size: 25px;
            }

        }
    </style>

</head>


<body class="sub_page">


    <div class="hero_area">

        <?php include 'header.php'; ?>

    </div>


    <section class="login-page">

        <div class="container">

            <div class="login-box">


                <!-- TITLE -->

                <div class="login-title">

                    <h2>Login</h2>

                    <p>
                        Login using your username and phone number
                    </p>

                </div>


                <!-- ERROR -->

                <?php if ($error !== "") { ?>

                    <div class="login-error">
                        <?php echo htmlspecialchars($error); ?>
                    </div>

                <?php } ?>


                <!-- SUCCESS -->

                <?php if ($success !== "") { ?>

                    <div class="login-success">
                        <?php echo htmlspecialchars($success); ?>
                    </div>

                <?php } ?>


                <!-- USERNAME + PHONE -->

                <form method="POST">

                    <div class="login-form-group">

                        <label>Username</label>

                        <input
                            type="text"
                            name="username"
                            placeholder="Enter your username"
                            value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                            required>

                    </div>


                    <div class="login-form-group">

                        <label>Phone Number</label>

                        <input
                            type="text"
                            name="phone"
                            placeholder="Enter 10-digit phone number"
                            maxlength="10"
                            value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>"
                            required>

                    </div>


                    <?php if (!isset($_SESSION['otp_generated'])) { ?>

                        <button
                            type="submit"
                            name="generate_otp"
                            class="login-btn">

                            Generate OTP

                        </button>

                    <?php } ?>

                </form>


                <!-- OTP SECTION -->

                <?php if (isset($_SESSION['otp_generated'])) { ?>

                    <div class="otp-box">

                        <div class="otp-title">

                            <h4>Verify OTP</h4>

                            <p>
                                Enter the 6-digit OTP
                            </p>

                        </div>


                        <form method="POST">

                            <div class="login-form-group">

                                <input
                                    type="text"
                                    name="otp"
                                    class="otp-input"
                                    placeholder="000000"
                                    maxlength="6"
                                    inputmode="numeric"
                                    autocomplete="one-time-code"
                                    required>

                            </div>


                            <button
                                type="submit"
                                name="verify_otp"
                                class="login-btn">

                                Verify OTP

                            </button>

                        </form>


                        <!-- TEST OTP -->

                        <?php if (isset($_SESSION['test_otp'])) { ?>

                            <div class="test-otp">

                                Testing OTP:

                                <strong>
                                    <?php echo $_SESSION['test_otp']; ?>
                                </strong>

                            </div>

                        <?php } ?>

                    </div>

                <?php } ?>


            </div>

        </div>

    </section>


    <?php include 'footer.php'; ?>


    <script src="js/jquery-3.4.1.min.js"></script>

    <script src="js/bootstrap.js"></script>

    <script src="js/custom.js"></script>


</body>

</html>