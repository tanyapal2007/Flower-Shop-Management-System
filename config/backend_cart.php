
<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "database.php";

header("Content-Type: application/json");


/* =========================================================
   CHECK LOGIN
========================================================= */

if (!isset($_SESSION['user_id'])) {

    echo json_encode([
        "success" => false,
        "login_required" => true,
        "message" => "Please login first."
    ]);

    exit;
}


$user_id = (int) $_SESSION['user_id'];


/* =========================================================
   GET ACTION
========================================================= */

$action = $_GET['action'] ?? '';

$product_id = isset($_GET['product_id'])
    ? (int) $_GET['product_id']
    : 0;

$cart_id = isset($_GET['cart_id'])
    ? (int) $_GET['cart_id']
    : 0;

$quantity = isset($_GET['quantity'])
    ? (int) $_GET['quantity']
    : 1;


/* =========================================================
   ADD TO CART
========================================================= */

if ($action === 'add') {

    if ($product_id <= 0) {

        echo json_encode([
            "success" => false,
            "message" => "Invalid product."
        ]);

        exit;
    }


    try {

        /* CHECK PRODUCT */

        $stmt = $conn->prepare("
            SELECT
                product_id,
                stock_quantity,
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


        if ((int)$product['status'] !== 1) {

            echo json_encode([
                "success" => false,
                "message" => "Product is not available."
            ]);

            exit;
        }


        $stock = (int)$product['stock_quantity'];


        if ($stock <= 0) {

            echo json_encode([
                "success" => false,
                "message" => "Product is out of stock."
            ]);

            exit;
        }


        /* CHECK EXISTING CART */

        $stmt = $conn->prepare("
            SELECT
                cart_id,
                quantity
            FROM cart
            WHERE user_id = :user_id
            AND product_id = :product_id
            LIMIT 1
        ");

        $stmt->execute([
            ':user_id' => $user_id,
            ':product_id' => $product_id
        ]);

        $cart = $stmt->fetch(PDO::FETCH_ASSOC);


        /* EXISTING PRODUCT */

        if ($cart) {

            $new_quantity =
                (int)$cart['quantity'] + 1;


            /* STOCK CHECK */

            if ($new_quantity > $stock) {

                echo json_encode([
                    "success" => false,
                    "message" => "Only " . $stock . " item(s) available.",
                    "quantity" => (int)$cart['quantity']
                ]);

                exit;
            }


            $stmt = $conn->prepare("
                UPDATE cart
                SET quantity = :quantity
                WHERE cart_id = :cart_id
                AND user_id = :user_id
            ");

            $stmt->execute([
                ':quantity' => $new_quantity,
                ':cart_id' => (int)$cart['cart_id'],
                ':user_id' => $user_id
            ]);
        }


        /* NEW PRODUCT */ else {

            $new_quantity = 1;


            $stmt = $conn->prepare("
                INSERT INTO cart
                (
                    user_id,
                    product_id,
                    quantity
                )
                VALUES
                (
                    :user_id,
                    :product_id,
                    :quantity
                )
            ");

            $stmt->execute([
                ':user_id' => $user_id,
                ':product_id' => $product_id,
                ':quantity' => $new_quantity
            ]);
        }


        echo json_encode([
            "success" => true,
            "status" => "success",
            "quantity" => $new_quantity
        ]);

        exit;
    } catch (PDOException $e) {

        echo json_encode([
            "success" => false,
            "message" => "Database Error: " . $e->getMessage()
        ]);

        exit;
    }
}


/* =========================================================
   UPDATE QUANTITY
========================================================= */

if ($action === 'update') {

    if ($cart_id <= 0 || $quantity <= 0) {

        echo json_encode([
            "success" => false,
            "message" => "Invalid cart information."
        ]);

        exit;
    }


    try {

        /* GET CART + PRODUCT STOCK */

        $stmt = $conn->prepare("
            SELECT
                c.cart_id,
                c.quantity,
                p.stock_quantity,
                pp.selling_price
            FROM cart c

            INNER JOIN products p
                ON c.product_id = p.product_id

            LEFT JOIN LATERAL
            (
                SELECT
                    product_prices.selling_price
                FROM product_prices
                WHERE product_prices.product_id = p.product_id
                ORDER BY product_prices.price_id DESC
                LIMIT 1
            ) pp ON TRUE

            WHERE c.cart_id = :cart_id
            AND c.user_id = :user_id

            LIMIT 1
        ");

        $stmt->execute([
            ':cart_id' => $cart_id,
            ':user_id' => $user_id
        ]);

        $cart = $stmt->fetch(PDO::FETCH_ASSOC);


        if (!$cart) {

            echo json_encode([
                "success" => false,
                "message" => "Cart item not found."
            ]);

            exit;
        }


        $stock = (int)$cart['stock_quantity'];


        if ($quantity > $stock) {

            echo json_encode([
                "success" => false,
                "message" => "Only " . $stock . " item(s) available.",
                "quantity" => (int)$cart['quantity']
            ]);

            exit;
        }


        $stmt = $conn->prepare("
            UPDATE cart
            SET quantity = :quantity
            WHERE cart_id = :cart_id
            AND user_id = :user_id
        ");

        $stmt->execute([
            ':quantity' => $quantity,
            ':cart_id' => $cart_id,
            ':user_id' => $user_id
        ]);


        $price = (float)($cart['selling_price'] ?? 0);

        $item_total = $price * $quantity;


        echo json_encode([
            "success" => true,
            "quantity" => $quantity,
            "item_total" => number_format($item_total, 2)
        ]);

        exit;
    } catch (PDOException $e) {

        echo json_encode([
            "success" => false,
            "message" => "Database Error: " . $e->getMessage()
        ]);

        exit;
    }
}


/* =========================================================
   REMOVE FROM CART
========================================================= */

if ($action === 'remove') {

    if ($cart_id <= 0) {

        echo json_encode([
            "success" => false,
            "message" => "Invalid cart item."
        ]);

        exit;
    }


    try {

        /* IMPORTANT:
           user_id bhi check kar rahe hain
           taaki user kisi aur user ka cart delete na kar sake.
        */

        $stmt = $conn->prepare("
            DELETE FROM cart

            WHERE cart_id = :cart_id

            AND user_id = :user_id
        ");

        $stmt->execute([
            ':cart_id' => $cart_id,
            ':user_id' => $user_id
        ]);


        if ($stmt->rowCount() > 0) {

            echo json_encode([
                "success" => true,
                "status" => "success",
                "message" => "Product removed from cart."
            ]);
        } else {

            echo json_encode([
                "success" => false,
                "message" => "Cart item not found."
            ]);
        }

        exit;
    } catch (PDOException $e) {

        echo json_encode([
            "success" => false,
            "message" => "Database Error: " . $e->getMessage()
        ]);

        exit;
    }
}


/* =========================================================
   INVALID ACTION
========================================================= */

echo json_encode([
    "success" => false,
    "message" => "Invalid cart action."
]);

exit;
?>