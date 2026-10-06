
<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "database.php";

header("Content-Type: application/json; charset=UTF-8");


/* =========================================================
   CHECK LOGIN
========================================================= */

if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {

    echo json_encode([
        "success" => false,
        "login_required" => true,
        "message" => "Please login first."
    ]);

    exit;
}


$user_id = (int) $_SESSION['user_id'];


/* =========================================================
   GET PRODUCT ID
========================================================= */

$product_id = isset($_GET['product_id'])
    ? (int) $_GET['product_id']
    : 0;


if ($product_id <= 0) {

    echo json_encode([
        "success" => false,
        "message" => "Invalid product ID."
    ]);

    exit;
}


/* =========================================================
   GET ACTION
========================================================= */

$action = $_GET['action'] ?? 'add';


try {

    /* =====================================================
       CHECK PRODUCT
    ====================================================== */

    $stmt = $conn->prepare("
        SELECT
            product_id,
            product_name,
            status
        FROM products
        WHERE product_id = :product_id
        LIMIT 1
    ");

    $stmt->execute([
        ':product_id' => $product_id
    ]);

    $product = $stmt->fetch(PDO::FETCH_ASSOC);


    if (!$product) {

        echo json_encode([
            "success" => false,
            "message" => "Product not found."
        ]);

        exit;
    }


    /* =====================================================
       CHECK ACTIVE PRODUCT
    ====================================================== */

    if ((int)$product['status'] !== 1) {

        echo json_encode([
            "success" => false,
            "message" => "Product is not active."
        ]);

        exit;
    }


    /* =====================================================
       ADD TO WISHLIST
    ====================================================== */

    if ($action === 'add') {

        /* CHECK ALREADY EXISTS */

        $check = $conn->prepare("
            SELECT 1
            FROM wishlist
            WHERE user_id = :user_id
            AND product_id = :product_id
            LIMIT 1
        ");

        $check->execute([
            ':user_id' => $user_id,
            ':product_id' => $product_id
        ]);


        if ($check->fetchColumn()) {

            echo json_encode([
                "success" => true,
                "status" => "success",
                "message" => "Already in wishlist."
            ]);

            exit;
        }


        /* INSERT WISHLIST */

        $insert = $conn->prepare("
            INSERT INTO wishlist
            (
                user_id,
                product_id
            )
            VALUES
            (
                :user_id,
                :product_id
            )
        ");

        $insert->execute([
            ':user_id' => $user_id,
            ':product_id' => $product_id
        ]);


        echo json_encode([
            "success" => true,
            "status" => "success",
            "message" => "Product added to wishlist."
        ]);

        exit;
    }


    /* =====================================================
       REMOVE FROM WISHLIST
    ====================================================== */

    if ($action === 'remove') {

        $delete = $conn->prepare("
            DELETE FROM wishlist
            WHERE user_id = :user_id
            AND product_id = :product_id
        ");

        $delete->execute([
            ':user_id' => $user_id,
            ':product_id' => $product_id
        ]);


        echo json_encode([
            "success" => true,
            "status" => "success",
            "message" => "Product removed from wishlist."
        ]);

        exit;
    }


    /* =====================================================
       INVALID ACTION
    ====================================================== */

    echo json_encode([
        "success" => false,
        "message" => "Invalid action."
    ]);

    exit;
} catch (PDOException $e) {

    echo json_encode([
        "success" => false,
        "status" => "error",
        "message" => $e->getMessage()
    ]);

    exit;
}

?>
