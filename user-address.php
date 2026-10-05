<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "config/database.php";


/* =========================================================
   CHECK LOGIN
========================================================= */

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = (int) $_SESSION['user_id'];

$error = "";


/* =========================================================
   FETCH USER DETAILS
========================================================= */

$stmt = $conn->prepare("
    SELECT
        user_id,
        name,
        phone,
        email
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
   FETCH EXISTING ADDRESS
========================================================= */

$stmt = $conn->prepare("
    SELECT
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
   DEFAULT VALUES
========================================================= */

$address = $profile['address'] ?? "";
$city    = $profile['city'] ?? "";
$state   = $profile['state'] ?? "";
$pincode = $profile['pincode'] ?? "";
$country = $profile['country'] ?? "India";


/* =========================================================
   SAVE ADDRESS
========================================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $address = trim($_POST['address'] ?? "");
    $city    = trim($_POST['city'] ?? "");
    $state   = trim($_POST['state'] ?? "");
    $pincode = trim($_POST['pincode'] ?? "");
    $country = trim($_POST['country'] ?? "India");


    /* =====================================================
       VALIDATION
    ===================================================== */

    if ($address === "") {

        $error = "Please enter your address.";
    } elseif ($city === "") {

        $error = "Please enter your city.";
    } elseif ($state === "") {

        $error = "Please enter your state.";
    } elseif ($pincode === "") {

        $error = "Please enter your pincode.";
    } elseif (!preg_match('/^[0-9]{6}$/', $pincode)) {

        $error = "Please enter a valid 6-digit pincode.";
    } elseif ($country === "") {

        $error = "Please enter your country.";
    }


    /* =====================================================
       INSERT / UPDATE
    ===================================================== */

    if ($error === "") {

        try {

            /* Check whether profile already exists */

            $checkStmt = $conn->prepare("
                SELECT user_id
                FROM user_profile
                WHERE user_id = :user_id
                LIMIT 1
            ");

            $checkStmt->execute([
                ':user_id' => $user_id
            ]);

            $existingProfile = $checkStmt->fetch(PDO::FETCH_ASSOC);


            /* =================================================
               UPDATE EXISTING PROFILE
            ================================================= */

            if ($existingProfile) {

                $updateStmt = $conn->prepare("
                    UPDATE user_profile
                    SET
                        address = :address,
                        city = :city,
                        state = :state,
                        pincode = :pincode,
                        country = :country,
                        updated_at = CURRENT_TIMESTAMP
                    WHERE user_id = :user_id
                ");

                $updateStmt->execute([
                    ':address' => $address,
                    ':city' => $city,
                    ':state' => $state,
                    ':pincode' => $pincode,
                    ':country' => $country,
                    ':user_id' => $user_id
                ]);
            }


            /* =================================================
               INSERT NEW PROFILE
            ================================================= */ else {

                $insertStmt = $conn->prepare("
                    INSERT INTO user_profile
                    (
                        user_id,
                        address,
                        city,
                        state,
                        pincode,
                        country,
                        created_at,
                        updated_at
                    )
                    VALUES
                    (
                        :user_id,
                        :address,
                        :city,
                        :state,
                        :pincode,
                        :country,
                        CURRENT_TIMESTAMP,
                        CURRENT_TIMESTAMP
                    )
                ");

                $insertStmt->execute([
                    ':user_id' => $user_id,
                    ':address' => $address,
                    ':city' => $city,
                    ':state' => $state,
                    ':pincode' => $pincode,
                    ':country' => $country
                ]);
            }


            /* =================================================
               AFTER SAVE → CHECKOUT
            ================================================= */

            header("Location: checkout.php");
            exit;
        } catch (PDOException $e) {

            $error = "Unable to save address. Please try again.";
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>My Address - Fior</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f7f7f7;
            color: #333;
        }

        .address-page {
            width: 100%;
            min-height: 100vh;
            padding: 40px 20px;
        }

        .address-container {
            max-width: 750px;
            margin: 0 auto;
        }

        .address-header {
            background: #ffffff;
            padding: 25px 30px;
            border-radius: 8px 8px 0 0;
            border-bottom: 1px solid #eeeeee;
        }

        .address-header h2 {
            margin: 0 0 8px;
            font-size: 28px;
            color: #222;
        }

        .address-header p {
            margin: 0;
            color: #777;
            font-size: 14px;
        }

        .address-card {
            background: #ffffff;
            padding: 30px;
            border-radius: 0 0 8px 8px;
        }

        .user-info {
            background: #f9f9f9;
            padding: 15px 18px;
            margin-bottom: 25px;
            border-radius: 6px;
            border-left: 4px solid #f7444e;
        }

        .user-info strong {
            display: block;
            margin-bottom: 5px;
            color: #222;
        }

        .user-info span {
            color: #666;
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-row {
            display: flex;
            gap: 20px;
        }

        .form-row .form-group {
            width: 50%;
        }

        label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #333;
            font-size: 14px;
        }

        input,
        textarea {
            width: 100%;
            border: 1px solid #dddddd;
            border-radius: 5px;
            padding: 12px 14px;
            font-size: 14px;
            outline: none;
        }

        input:focus,
        textarea:focus {
            border-color: #f7444e;
        }

        textarea {
            min-height: 100px;
            resize: vertical;
        }

        .error-message {
            background: #ffe5e5;
            color: #d60000;
            padding: 12px 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .buttons {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            margin-top: 25px;
        }

        .btn {
            display: inline-block;
            padding: 12px 25px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 14px;
            cursor: pointer;
            border: none;
        }

        .back-btn {
            background: #eeeeee;
            color: #333;
        }

        .save-btn {
            background: #f7444e;
            color: #ffffff;
        }

        .save-btn:hover {
            background: #d9363e;
        }

        .back-btn:hover {
            background: #dddddd;
        }


        /* =====================================================
           MOBILE
        ===================================================== */

        @media (max-width: 600px) {

            .address-page {
                padding: 20px 10px;
            }

            .address-header,
            .address-card {
                padding: 20px;
            }

            .form-row {
                display: block;
            }

            .form-row .form-group {
                width: 100%;
            }

            .buttons {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                text-align: center;
            }
        }
    </style>

</head>


<body>

    <div class="address-page">

        <div class="address-container">


            <!-- =================================================
             HEADER
        ================================================== -->

            <div class="address-header">

                <h2>Delivery Address</h2>

                <p>
                    Add or update your delivery address.
                </p>

            </div>


            <!-- =================================================
             ADDRESS FORM
        ================================================== -->

            <div class="address-card">


                <!-- USER INFORMATION -->

                <div class="user-info">

                    <strong>
                        <?php echo htmlspecialchars($user['name']); ?>
                    </strong>

                    <span>
                        Phone:
                        <?php echo htmlspecialchars($user['phone']); ?>
                    </span>

                    <?php if (!empty($user['email'])) { ?>

                        <br>

                        <span>
                            Email:
                            <?php echo htmlspecialchars($user['email']); ?>
                        </span>

                    <?php } ?>

                </div>


                <!-- ERROR -->

                <?php if ($error !== "") { ?>

                    <div class="error-message">
                        <?php echo htmlspecialchars($error); ?>
                    </div>

                <?php } ?>


                <!-- FORM -->

                <form method="POST"
                    action="user-address.php">


                    <!-- ADDRESS -->

                    <div class="form-group">

                        <label for="address">
                            Full Address
                        </label>

                        <textarea
                            id="address"
                            name="address"
                            placeholder="House No., Building, Street, Area"
                            required><?php echo htmlspecialchars($address); ?></textarea>

                    </div>


                    <!-- CITY + STATE -->

                    <div class="form-row">

                        <div class="form-group">

                            <label for="city">
                                City
                            </label>

                            <input
                                type="text"
                                id="city"
                                name="city"
                                value="<?php echo htmlspecialchars($city); ?>"
                                placeholder="Enter city"
                                required>

                        </div>


                        <div class="form-group">

                            <label for="state">
                                State
                            </label>

                            <input
                                type="text"
                                id="state"
                                name="state"
                                value="<?php echo htmlspecialchars($state); ?>"
                                placeholder="Enter state"
                                required>

                        </div>

                    </div>


                    <!-- PINCODE + COUNTRY -->

                    <div class="form-row">

                        <div class="form-group">

                            <label for="pincode">
                                Pincode
                            </label>

                            <input
                                type="text"
                                id="pincode"
                                name="pincode"
                                value="<?php echo htmlspecialchars($pincode); ?>"
                                placeholder="6-digit pincode"
                                maxlength="6"
                                inputmode="numeric"
                                required>

                        </div>


                        <div class="form-group">

                            <label for="country">
                                Country
                            </label>

                            <input
                                type="text"
                                id="country"
                                name="country"
                                value="<?php echo htmlspecialchars($country); ?>"
                                placeholder="Enter country"
                                required>

                        </div>

                    </div>


                    <!-- BUTTONS -->

                    <div class="buttons">

                        <a href="checkout.php"
                            class="btn back-btn">
                            ← Back to Checkout
                        </a>

                        <button
                            type="submit"
                            class="btn save-btn">
                            Save Address
                        </button>

                    </div>

                </form>

            </div>

        </div>

    </div>

</body>

</html>