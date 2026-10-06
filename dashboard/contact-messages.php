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

require_once "../config/database.php";


/* =========================================================
   CHECK LOGIN
========================================================= */

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$admin_id = (int) $_SESSION['user_id'];


/* =========================================================
   CHECK ADMIN
========================================================= */

$stmt = $conn->prepare("
    SELECT role
    FROM users
    WHERE user_id = :user_id
    LIMIT 1
");

$stmt->execute([
    ':user_id' => $admin_id
]);

$admin = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$admin || $admin['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}


/* =========================================================
   DELETE CONTACT MESSAGE
========================================================= */

if (isset($_GET['delete_id'])) {

    $delete_id = (int) $_GET['delete_id'];

    if ($delete_id > 0) {

        $delete_stmt = $conn->prepare("
            DELETE FROM contact_messages
            WHERE message_id = :message_id
        ");

        $delete_stmt->execute([
            ':message_id' => $delete_id
        ]);
    }

    header("Location: contact-messages.php");
    exit;
}


/* =========================================================
   FETCH CONTACT MESSAGES
========================================================= */

$stmt = $conn->prepare("
    SELECT
        message_id,
        name,
        email,
        phone,
        message,
        status,
        created_at
    FROM contact_messages
    ORDER BY message_id DESC
");

$stmt->execute();

$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>Contact Messages - Admin Dashboard</title>

    <!-- Bootstrap -->
    <link rel="stylesheet"
        href="../css/bootstrap.css">

    <!-- Font Awesome -->
    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">


    <style>
        body {
            margin: 0;
            padding: 0;
            background: #f5f6fa;
            font-family: Arial, sans-serif;
        }

        .page-content {
            padding: 30px;
        }

        .page-title {
            margin-bottom: 25px;
        }

        .page-title h2 {
            margin: 0;
            font-size: 28px;
            font-weight: 600;
            color: #333;
        }

        .page-title p {
            margin-top: 7px;
            color: #777;
        }

        .message-card {
            background: #ffffff;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        }

        .table-responsive {
            width: 100%;
            overflow-x: auto;
        }

        .message-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1000px;
        }

        .message-table th {
            background: #f8f8f8;
            color: #333;
            font-size: 14px;
            font-weight: 600;
            padding: 14px 12px;
            border-bottom: 1px solid #ddd;
            text-align: left;
        }

        .message-table td {
            padding: 14px 12px;
            border-bottom: 1px solid #eeeeee;
            color: #555;
            font-size: 14px;
            vertical-align: top;
        }

        .message-table tr:hover {
            background: #fafafa;
        }

        .message-text {
            min-width: 250px;
            max-width: 400px;
            white-space: normal;
            line-height: 1.5;
        }

        .status {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            background: #fff3cd;
            color: #856404;
            font-size: 12px;
            text-transform: capitalize;
        }

        .delete-btn {
            display: inline-block;
            background: #dc3545;
            color: #ffffff;
            padding: 7px 12px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 13px;
        }

        .delete-btn:hover {
            color: #ffffff;
            text-decoration: none;
            background: #c82333;
        }

        .empty-message {
            text-align: center;
            padding: 50px 20px;
            color: #777;
        }

        .empty-message i {
            font-size: 45px;
            margin-bottom: 15px;
            color: #ccc;
        }

        .empty-message h4 {
            margin-bottom: 8px;
            color: #555;
        }

        @media (max-width: 768px) {

            .page-content {
                padding: 15px;
            }

            .page-title h2 {
                font-size: 23px;
            }

            .message-card {
                padding: 12px;
            }

        }
    </style>

</head>


<body>


    <div class="page-content">

        <!-- =====================================================
         PAGE TITLE
    ====================================================== -->

        <div class="page-title">

            <h2>
                <i class="fa fa-envelope"></i>
                Contact Messages
            </h2>

            <p>
                Messages received from the website contact form.
            </p>

        </div>


        <!-- =====================================================
         MESSAGE CARD
    ====================================================== -->

        <div class="message-card">

            <?php if (count($messages) > 0): ?>

                <div class="table-responsive">

                    <table class="message-table">

                        <thead>

                            <tr>

                                <th>ID</th>

                                <th>Name</th>

                                <th>Email</th>

                                <th>Phone</th>

                                <th>Message</th>

                                <th>Status</th>

                                <th>Date</th>

                                <th>Action</th>

                            </tr>

                        </thead>


                        <tbody>

                            <?php foreach ($messages as $row): ?>

                                <tr>

                                    <!-- ID -->
                                    <td>
                                        <?php echo (int) $row['message_id']; ?>
                                    </td>


                                    <!-- NAME -->
                                    <td>
                                        <?php
                                        echo htmlspecialchars(
                                            $row['name'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        );
                                        ?>
                                    </td>


                                    <!-- EMAIL -->
                                    <td>
                                        <?php
                                        echo htmlspecialchars(
                                            $row['email'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        );
                                        ?>
                                    </td>


                                    <!-- PHONE -->
                                    <td>
                                        <?php
                                        echo !empty($row['phone'])
                                            ? htmlspecialchars(
                                                $row['phone'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            )
                                            : '-';
                                        ?>
                                    </td>


                                    <!-- MESSAGE -->
                                    <td class="message-text">

                                        <?php
                                        echo nl2br(
                                            htmlspecialchars(
                                                $row['message'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            )
                                        );
                                        ?>

                                    </td>


                                    <!-- STATUS -->
                                    <td>

                                        <span class="status">

                                            <?php
                                            echo htmlspecialchars(
                                                $row['status'] ?? 'new',
                                                ENT_QUOTES,
                                                'UTF-8'
                                            );
                                            ?>

                                        </span>

                                    </td>


                                    <!-- DATE -->
                                    <td>

                                        <?php
                                        echo !empty($row['created_at'])
                                            ? date(
                                                'd M Y, h:i A',
                                                strtotime($row['created_at'])
                                            )
                                            : '-';
                                        ?>

                                    </td>


                                    <!-- ACTION -->
                                    <td>

                                        <a
                                            href="contact-messages.php?delete_id=<?php echo (int) $row['message_id']; ?>"
                                            class="delete-btn"
                                            onclick="return confirm('Are you sure you want to delete this message?');">
                                            <i class="fa fa-trash"></i>
                                            Delete
                                        </a>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>


            <?php else: ?>


                <!-- =================================================
                 NO MESSAGES
            ================================================== -->

                <div class="empty-message">

                    <i class="fa fa-envelope-o"></i>

                    <h4>No Contact Messages</h4>

                    <p>
                        No messages have been received yet.
                    </p>

                </div>


            <?php endif; ?>

        </div>

    </div>


</body>

</html>