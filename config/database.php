
<?php

/* =========================================================
   DATABASE CONFIGURATION
========================================================= */

$host = "localhost";
$port = "5432";
$dbname = "fior_flower_shop";
$username = "postgres";
$password = "1234";


try {

    /* =====================================================
       CONNECT TO POSTGRES DEFAULT DATABASE
    ===================================================== */

    $pdo = new PDO(
        "pgsql:host=$host;port=$port;dbname=postgres",
        $username,
        $password
    );

    $pdo->setAttribute(
        PDO::ATTR_ERRMODE,
        PDO::ERRMODE_EXCEPTION
    );


    /* =====================================================
       CHECK DATABASE EXISTS
    ===================================================== */

    $checkDatabase = $pdo->prepare("
        SELECT 1
        FROM pg_database
        WHERE datname = :dbname
    ");

    $checkDatabase->execute([
        ':dbname' => $dbname
    ]);


    /* =====================================================
       CREATE DATABASE IF NOT EXISTS
    ===================================================== */

    if (!$checkDatabase->fetch()) {

        $pdo->exec(
            'CREATE DATABASE "' . $dbname . '"'
        );
    }


    /* =====================================================
       CONNECT TO PROJECT DATABASE
    ===================================================== */

    $conn = new PDO(
        "pgsql:host=$host;port=$port;dbname=$dbname",
        $username,
        $password
    );

    $conn->setAttribute(
        PDO::ATTR_ERRMODE,
        PDO::ERRMODE_EXCEPTION
    );


    /* =====================================================
       USERS TABLE
    ===================================================== */

    $conn->exec("
        CREATE TABLE IF NOT EXISTS users (

            user_id SERIAL PRIMARY KEY,

            name VARCHAR(100) NOT NULL,

            email VARCHAR(150) UNIQUE NOT NULL,

            phone VARCHAR(20) UNIQUE NOT NULL,

            password VARCHAR(255),

            role VARCHAR(20) DEFAULT 'user',

            status INTEGER DEFAULT 1,

            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");


    /* =====================================================
       USER PROFILE TABLE
    ===================================================== */

    $conn->exec("
        CREATE TABLE IF NOT EXISTS user_profile (

            profile_id SERIAL PRIMARY KEY,

            user_id INTEGER NOT NULL UNIQUE,

            profile_image VARCHAR(255),

            gender VARCHAR(20),

            date_of_birth DATE,

            bio TEXT,

            address TEXT,

            city VARCHAR(100),

            state VARCHAR(100),

            pincode VARCHAR(10),

            country VARCHAR(100) DEFAULT 'India',

            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

            CONSTRAINT fk_user_profile
            FOREIGN KEY (user_id)
            REFERENCES users(user_id)
            ON DELETE CASCADE
        )
    ");


    /* =====================================================
       ADD MISSING USER PROFILE COLUMNS
       For old existing tables
    ===================================================== */

    $conn->exec("
        ALTER TABLE user_profile
        ADD COLUMN IF NOT EXISTS address TEXT
    ");

    $conn->exec("
        ALTER TABLE user_profile
        ADD COLUMN IF NOT EXISTS city VARCHAR(100)
    ");

    $conn->exec("
        ALTER TABLE user_profile
        ADD COLUMN IF NOT EXISTS state VARCHAR(100)
    ");

    $conn->exec("
        ALTER TABLE user_profile
        ADD COLUMN IF NOT EXISTS pincode VARCHAR(10)
    ");

    $conn->exec("
        ALTER TABLE user_profile
        ADD COLUMN IF NOT EXISTS country VARCHAR(100)
        DEFAULT 'India'
    ");

    $conn->exec("
        ALTER TABLE user_profile
        ADD COLUMN IF NOT EXISTS created_at
        TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ");

    $conn->exec("
        ALTER TABLE user_profile
        ADD COLUMN IF NOT EXISTS updated_at
        TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ");


    /* =====================================================
       PRODUCT CATEGORY TABLE
    ===================================================== */

    $conn->exec("
        CREATE TABLE IF NOT EXISTS product_category (

            category_id SERIAL PRIMARY KEY,

            category_name VARCHAR(100) NOT NULL,

            category_description TEXT,

            category_image VARCHAR(255),

            status INTEGER DEFAULT 1,

            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");


    /* =====================================================
       PRODUCT SUBCATEGORY TABLE
    ===================================================== */

    $conn->exec("
        CREATE TABLE IF NOT EXISTS product_subcategory (

            subcategory_id SERIAL PRIMARY KEY,

            category_id INTEGER NOT NULL,

            subcategory_name VARCHAR(100) NOT NULL,

            subcategory_description TEXT,

            subcategory_image VARCHAR(255),

            status INTEGER DEFAULT 1,

            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

            CONSTRAINT fk_category
            FOREIGN KEY (category_id)
            REFERENCES product_category(category_id)
            ON DELETE CASCADE
        )
    ");


    /* =====================================================
       PRODUCTS TABLE
    ===================================================== */

    $conn->exec("
        CREATE TABLE IF NOT EXISTS products (

            product_id SERIAL PRIMARY KEY,

            subcategory_id INTEGER NOT NULL,

            product_name VARCHAR(150) NOT NULL,

            product_code VARCHAR(100) UNIQUE,

            stock_quantity INTEGER DEFAULT 0,

            product_price DECIMAL(10,2) DEFAULT 0,

            product_description TEXT,

            product_image VARCHAR(255),

            status INTEGER DEFAULT 1,

            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

            CONSTRAINT fk_subcategory
            FOREIGN KEY (subcategory_id)
            REFERENCES product_subcategory(subcategory_id)
            ON DELETE CASCADE
        )
    ");


    /* =====================================================
       ORDERS TABLE
    ===================================================== */

    $conn->exec("
        CREATE TABLE IF NOT EXISTS orders (

            order_id SERIAL PRIMARY KEY,

            user_id INTEGER,

            total_amount DECIMAL(10,2) DEFAULT 0,

            order_status VARCHAR(50)
            DEFAULT 'pending',

            created_at TIMESTAMP
            DEFAULT CURRENT_TIMESTAMP,

            CONSTRAINT fk_order_user
            FOREIGN KEY (user_id)
            REFERENCES users(user_id)
            ON DELETE SET NULL
        )
    ");


    /* =====================================================
       ORDER ITEMS TABLE
    ===================================================== */

    $conn->exec("
        CREATE TABLE IF NOT EXISTS order_items (

            order_item_id SERIAL PRIMARY KEY,

            order_id INTEGER NOT NULL,

            product_id INTEGER NOT NULL,

            quantity INTEGER DEFAULT 1,

            price DECIMAL(10,2) DEFAULT 0,

            CONSTRAINT fk_order
            FOREIGN KEY (order_id)
            REFERENCES orders(order_id)
            ON DELETE CASCADE,

            CONSTRAINT fk_product
            FOREIGN KEY (product_id)
            REFERENCES products(product_id)
            ON DELETE CASCADE
        )
    ");


    /* =====================================================
       CREATE USER PROFILE FOR EXISTING USERS
    ===================================================== */

    $conn->exec("
        INSERT INTO user_profile (user_id)

        SELECT u.user_id

        FROM users u

        LEFT JOIN user_profile p
            ON p.user_id = u.user_id

        WHERE p.user_id IS NULL
    ");


    /* =====================================================
       INDEXES
    ===================================================== */

    $conn->exec("
        CREATE INDEX IF NOT EXISTS
        idx_user_profile_user_id
        ON user_profile(user_id)
    ");

    $conn->exec("
        CREATE INDEX IF NOT EXISTS
        idx_orders_user_id
        ON orders(user_id)
    ");

    $conn->exec("
        CREATE INDEX IF NOT EXISTS
        idx_order_items_order_id
        ON order_items(order_id)
    ");

    $conn->exec("
        CREATE INDEX IF NOT EXISTS
        idx_products_subcategory
        ON products(subcategory_id)
    ");
} catch (PDOException $e) {

    die("Database Error: " .
        htmlspecialchars($e->getMessage()));
}

?>
