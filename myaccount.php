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


/* =========================================================
   FETCH USER DETAILS
========================================================= */

$user = [];

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
   ORDERS COUNT
========================================================= */

$stmt = $conn->prepare("
    SELECT COUNT(*)
    FROM orders
    WHERE user_id = :user_id
");

$stmt->execute([
    ':user_id' => $user_id
]);

$order_count = (int) $stmt->fetchColumn();


/* =========================================================
   CART COUNT
========================================================= */

$stmt = $conn->prepare("
    SELECT COALESCE(SUM(quantity), 0)
    FROM cart
    WHERE user_id = :user_id
");

$stmt->execute([
    ':user_id' => $user_id
]);

$cart_count = (int) $stmt->fetchColumn();


/* =========================================================
   WISHLIST COUNT
========================================================= */

$wishlist_count = 0;

try {

    $stmt = $conn->prepare("
        SELECT COUNT(*)
        FROM wishlist
        WHERE user_id = :user_id
    ");

    $stmt->execute([
        ':user_id' => $user_id
    ]);

    $wishlist_count = (int) $stmt->fetchColumn();
} catch (PDOException $e) {

    $wishlist_count = 0;
}


/* =========================================================
   ADDRESS COUNT
========================================================= */

$address_count = 0;

try {

    $stmt = $conn->prepare("
        SELECT COUNT(*)
        FROM user_address
        WHERE user_id = :user_id
    ");

    $stmt->execute([
        ':user_id' => $user_id
    ]);

    $address_count = (int) $stmt->fetchColumn();
} catch (PDOException $e) {

    $address_count = 0;
}


/* =========================================================
   GET RECENT ORDERS
========================================================= */

$recent_orders = [];

try {

    $stmt = $conn->prepare("
        SELECT
            order_id,
            total_amount,
            order_status,
            created_at
        FROM orders
        WHERE user_id = :user_id
        ORDER BY order_id DESC
        LIMIT 5
    ");

    $stmt->execute([
        ':user_id' => $user_id
    ]);

    $recent_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {

    $recent_orders = [];
}


/* =========================================================
   USER INITIAL
========================================================= */

$user_name = $user['name'] ?? 'User';

$user_initial = strtoupper(substr(trim($user_name), 0, 1));

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>My Account - Fior</title>


    <!-- Bootstrap -->

    <link rel="stylesheet" href="css/bootstrap.css">


    <!-- Main CSS -->

    <link rel="stylesheet" href="css/style.css">


    <!-- Responsive CSS -->

    <link rel="stylesheet" href="css/responsive.css">


    <!-- Font Awesome -->

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">


    <style>
        /* =====================================================
           MY ACCOUNT
        ===================================================== */

        .myaccount_section {
            padding: 70px 0;
            background: #fafafa;
            min-height: 650px;
        }


        .myaccount_container {
            max-width: 1100px;
            margin: 0 auto;
        }


        /* =====================================================
           PAGE TITLE
        ===================================================== */

        .myaccount_title {
            text-align: center;
            margin-bottom: 40px;
        }

        .myaccount_title h2 {
            font-size: 32px;
            font-weight: 700;
            color: #222;
            margin-bottom: 8px;
        }

        .myaccount_title p {
            color: #777;
            margin: 0;
        }


        /* =====================================================
           PROFILE BOX
        ===================================================== */

        .profile_box {
            background: #fff;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 3px 15px rgba(0, 0, 0, 0.08);
            margin-bottom: 30px;
        }


        .profile_left {
            display: flex;
            align-items: center;
            gap: 20px;
        }


        .profile_avatar {
            width: 85px;
            height: 85px;
            border-radius: 50%;
            background: #df2f68;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 34px;
            font-weight: 700;
            flex-shrink: 0;
        }


        .profile_info h3 {
            margin: 0 0 8px;
            font-size: 25px;
            font-weight: 700;
            color: #222;
        }


        .profile_info p {
            margin: 4px 0;
            color: #777;
            font-size: 14px;
        }


        .profile_info i {
            width: 20px;
            color: #df2f68;
        }


        .profile_role {
            display: inline-block;
            background: #ffe6ef;
            color: #df2f68;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: capitalize;
            margin-top: 5px;
        }


        .edit_profile_btn {
            display: inline-block;
            margin-top: 15px;
            background: #df2f68;
            color: #fff;
            padding: 9px 18px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 14px;
        }


        .edit_profile_btn:hover {
            background: #c92359;
            color: #fff;
            text-decoration: none;
        }


        /* =====================================================
           STAT CARDS
        ===================================================== */

        .account_stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }


        .stat_card {
            background: #fff;
            padding: 25px 20px;
            border-radius: 10px;
            box-shadow: 0 3px 15px rgba(0, 0, 0, 0.07);
            text-align: center;
            text-decoration: none;
            transition: 0.3s;
        }


        .stat_card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.12);
            text-decoration: none;
        }


        .stat_icon {
            width: 55px;
            height: 55px;
            border-radius: 50%;
            background: #ffe6ef;
            color: #df2f68;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 12px;
            font-size: 22px;
        }


        .stat_card h3 {
            font-size: 25px;
            color: #222;
            margin: 0 0 5px;
            font-weight: 700;
        }


        .stat_card p {
            color: #777;
            margin: 0;
            font-size: 14px;
        }


        /* =====================================================
           ACCOUNT MENU
        ===================================================== */

        .account_content {
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 25px;
        }


        .account_menu {
            background: #fff;
            border-radius: 10px;
            padding: 10px;
            box-shadow: 0 3px 15px rgba(0, 0, 0, 0.07);
            height: fit-content;
        }


        .account_menu_title {
            padding: 15px;
            font-size: 18px;
            font-weight: 700;
            color: #222;
            border-bottom: 1px solid #eee;
        }


        .account_menu a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 15px;
            color: #555;
            text-decoration: none;
            border-bottom: 1px solid #f2f2f2;
            font-size: 14px;
            transition: 0.3s;
        }


        .account_menu a:last-child {
            border-bottom: none;
        }


        .account_menu a:hover {
            background: #fff0f5;
            color: #df2f68;
        }


        .account_menu a i {
            width: 20px;
            color: #df2f68;
        }


        .logout_link {
            color: #dc3545 !important;
        }


        .logout_link i {
            color: #dc3545 !important;
        }


        /* =====================================================
           RECENT ORDERS
        ===================================================== */

        .recent_orders {
            background: #fff;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 3px 15px rgba(0, 0, 0, 0.07);
        }


        .section_heading {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
        }


        .section_heading h3 {
            margin: 0;
            font-size: 21px;
            font-weight: 700;
            color: #222;
        }


        .view_all_link {
            color: #df2f68;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
        }


        .view_all_link:hover {
            color: #c92359;
        }


        .orders_table {
            width: 100%;
            border-collapse: collapse;
        }


        .orders_table th {
            background: #fafafa;
            padding: 13px 10px;
            text-align: left;
            font-size: 13px;
            color: #555;
            border-bottom: 1px solid #eee;
        }


        .orders_table td {
            padding: 14px 10px;
            font-size: 13px;
            color: #666;
            border-bottom: 1px solid #eee;
        }


        .order_id {
            font-weight: 700;
            color: #222;
        }


        .order_amount {
            font-weight: 600;
            color: #222;
        }


        .order_status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            text-transform: capitalize;
            background: #eee;
            color: #555;
        }


        .order_status.completed,
        .order_status.delivered {
            background: #e7f8ed;
            color: #198754;
        }


        .order_status.pending {
            background: #fff3cd;
            color: #856404;
        }


        .order_status.cancelled {
            background: #f8d7da;
            color: #842029;
        }


        .order_status.processing,
        .order_status.shipped {
            background: #cfe2ff;
            color: #084298;
        }


        .view_order_btn {
            color: #df2f68;
            text-decoration: none;
            font-weight: 600;
        }


        .view_order_btn:hover {
            color: #c92359;
        }


        .no_orders {
            text-align: center;
            padding: 35px 15px;
            color: #777;
        }


        .no_orders i {
            font-size: 40px;
            color: #ddd;
            margin-bottom: 12px;
        }


        /* =====================================================
           MOBILE
        ===================================================== */

        @media (max-width: 991px) {

            .account_stats {
                grid-template-columns: repeat(2, 1fr);
            }

            .account_content {
                grid-template-columns: 1fr;
            }

        }


        @media (max-width: 767px) {

            .myaccount_section {
                padding: 45px 15px;
            }

            .myaccount_title h2 {
                font-size: 26px;
            }

            .profile_box {
                padding: 20px;
            }

            .profile_left {
                align-items: flex-start;
            }

            .profile_avatar {
                width: 65px;
                height: 65px;
                font-size: 26px;
            }

            .profile_info h3 {
                font-size: 20px;
            }

            .account_stats {
                grid-template-columns: 1fr 1fr;
                gap: 12px;
            }

            .stat_card {
                padding: 18px 10px;
            }

            .orders_table {
                min-width: 650px;
            }

            .recent_orders {
                overflow-x: auto;
            }

        }


        @media (max-width: 450px) {

            .account_stats {
                grid-template-columns: 1fr;
            }

            .profile_left {
                flex-direction: column;
            }

        }
    </style>

</head>


<body>


    <!-- =========================================================
     HEADER
========================================================= -->

    <?php

    if (file_exists("header.php")) {
        include "header.php";
    }

    ?>


    <!-- =========================================================
     MY ACCOUNT SECTION
========================================================= -->

    <section class="myaccount_section">

        <div class="container">

            <div class="myaccount_container">


                <!-- PAGE TITLE -->

                <div class="myaccount_title">

                    <h2>My Account</h2>

                    <p>
                        Manage your profile, orders and account details
                    </p>

                </div>


                <!-- =================================================
                 PROFILE
            ================================================== -->

                <div class="profile_box">

                    <div class="profile_left">

                        <div class="profile_avatar">

                            <?php echo htmlspecialchars($user_initial); ?>

                        </div>


                        <div class="profile_info">

                            <h3>
                                <?php echo htmlspecialchars($user['name']); ?>
                            </h3>


                            <?php if (!empty($user['email'])) { ?>

                                <p>
                                    <i class="fa fa-envelope"></i>

                                    <?php echo htmlspecialchars($user['email']); ?>
                                </p>

                            <?php } ?>


                            <?php if (!empty($user['phone'])) { ?>

                                <p>
                                    <i class="fa fa-phone"></i>

                                    <?php echo htmlspecialchars($user['phone']); ?>
                                </p>

                            <?php } ?>


                            <span class="profile_role">

                                <?php echo htmlspecialchars($user['role'] ?? 'user'); ?>

                            </span>


                            <br>


                            <a href="profile.php" class="edit_profile_btn">

                                <i class="fa fa-user-edit"></i>

                                Edit Profile

                            </a>

                        </div>

                    </div>

                </div>


                <!-- =================================================
                 STATISTICS
            ================================================== -->

                <div class="account_stats">


                    <!-- ORDERS -->

                    <a href="my-orders.php" class="stat_card">

                        <div class="stat_icon">

                            <i class="fa fa-box"></i>

                        </div>

                        <h3>
                            <?php echo $order_count; ?>
                        </h3>

                        <p>
                            My Orders
                        </p>

                    </a>


                    <!-- CART -->

                    <a href="cart.php" class="stat_card">

                        <div class="stat_icon">

                            <i class="fa fa-shopping-cart"></i>

                        </div>

                        <h3>
                            <?php echo $cart_count; ?>
                        </h3>

                        <p>
                            Cart Items
                        </p>

                    </a>


                    <!-- WISHLIST -->

                    <a href="wishlist.php" class="stat_card">

                        <div class="stat_icon">

                            <i class="fa fa-heart"></i>

                        </div>

                        <h3>
                            <?php echo $wishlist_count; ?>
                        </h3>

                        <p>
                            Wishlist
                        </p>

                    </a>


                    <!-- ADDRESS -->

                    <a href="user-address.php" class="stat_card">

                        <div class="stat_icon">

                            <i class="fa fa-location-dot"></i>

                        </div>

                        <h3>
                            <?php echo $address_count; ?>
                        </h3>

                        <p>
                            Addresses
                        </p>

                    </a>


                </div>


                <!-- =================================================
                 ACCOUNT CONTENT
            ================================================== -->

                <div class="account_content">


                    <!-- =================================================
                     LEFT MENU
                ================================================== -->

                    <div class="account_menu">

                        <div class="account_menu_title">

                            My Account

                        </div>


                        <a href="profile.php">

                            <i class="fa fa-user"></i>

                            <span>
                                My Profile
                            </span>

                        </a>


                        <a href="my-orders.php">

                            <i class="fa fa-box"></i>

                            <span>
                                My Orders
                            </span>

                        </a>


                        <a href="cart.php">

                            <i class="fa fa-shopping-cart"></i>

                            <span>
                                My Cart
                            </span>

                        </a>


                        <a href="wishlist.php">

                            <i class="fa fa-heart"></i>

                            <span>
                                My Wishlist
                            </span>

                        </a>


                        <a href="user-address.php">

                            <i class="fa fa-location-dot"></i>

                            <span>
                                My Addresses
                            </span>

                        </a>

                        <a href="change-number.php"> <i class="fa fa-phone"></i> <span> Change Number </span> </a>


                        <a href="logout.php" class="logout_link">

                            <i class="fa fa-right-from-bracket"></i>

                            <span>
                                Logout
                            </span>

                        </a>

                    </div>


                    <!-- =================================================
                     RECENT ORDERS
                ================================================== -->

                    <div class="recent_orders">


                        <div class="section_heading">

                            <h3>
                                Recent Orders
                            </h3>


                            <a href="my-orders.php" class="view_all_link">

                                View All

                            </a>

                        </div>


                        <?php if (!empty($recent_orders)) { ?>


                            <div style="overflow-x:auto;">

                                <table class="orders_table">

                                    <thead>

                                        <tr>

                                            <th>
                                                Order ID
                                            </th>

                                            <th>
                                                Date
                                            </th>

                                            <th>
                                                Amount
                                            </th>

                                            <th>
                                                Status
                                            </th>

                                            <th>
                                                Action
                                            </th>

                                        </tr>

                                    </thead>


                                    <tbody>

                                        <?php foreach ($recent_orders as $order) { ?>


                                            <?php

                                            $status = strtolower(
                                                trim($order['order_status'] ?? 'pending')
                                            );

                                            ?>


                                            <tr>

                                                <td class="order_id">

                                                    #<?php
                                                        echo htmlspecialchars(
                                                            $order['order_id']
                                                        );
                                                        ?>

                                                </td>


                                                <td>

                                                    <?php

                                                    if (!empty($order['created_at'])) {

                                                        echo date(
                                                            'd M Y',
                                                            strtotime($order['created_at'])
                                                        );
                                                    } else {

                                                        echo '-';
                                                    }

                                                    ?>

                                                </td>


                                                <td class="order_amount">

                                                    ₹<?php

                                                        echo number_format(
                                                            (float) $order['total_amount'],
                                                            2
                                                        );

                                                        ?>

                                                </td>


                                                <td>

                                                    <span class="order_status <?php echo htmlspecialchars($status); ?>">

                                                        <?php

                                                        echo htmlspecialchars(
                                                            $order['order_status'] ?? 'Pending'
                                                        );

                                                        ?>

                                                    </span>

                                                </td>


                                                <td>

                                                    <a
                                                        href="order-details.php?order_id=<?php echo (int) $order['order_id']; ?>"
                                                        class="view_order_btn">

                                                        View

                                                    </a>

                                                </td>

                                            </tr>


                                        <?php } ?>

                                    </tbody>

                                </table>

                            </div>


                        <?php } else { ?>


                            <div class="no_orders">

                                <i class="fa fa-box-open"></i>

                                <p>
                                    You have not placed any orders yet.
                                </p>


                                <a
                                    href="gallery.php"
                                    class="edit_profile_btn">

                                    Start Shopping

                                </a>

                            </div>


                        <?php } ?>


                    </div>

                </div>

            </div>

        </div>

    </section>


    <!-- =========================================================
     FOOTER
========================================================= -->

    <?php

    if (file_exists("footer.php")) {
        include "footer.php";
    }

    ?>


    <!-- =========================================================
     JS
========================================================= -->

    <script src="js/jquery-3.4.1.min.js"></script>

    <script src="js/bootstrap.js"></script>

    <script src="js/custom.js"></script>


</body>

</html>