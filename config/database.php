
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
       FOR OLD EXISTING TABLES
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
       ADD PRODUCT EXTRA COLUMNS
       FOR OLD EXISTING TABLES
    ===================================================== */

    $conn->exec("
        ALTER TABLE products
        ADD COLUMN IF NOT EXISTS brand_name VARCHAR(150)
    ");

    $conn->exec("
        ALTER TABLE products
        ADD COLUMN IF NOT EXISTS color VARCHAR(100)
    ");

    $conn->exec("
        ALTER TABLE products
        ADD COLUMN IF NOT EXISTS size VARCHAR(100)
    ");

    $conn->exec("
        ALTER TABLE products
        ADD COLUMN IF NOT EXISTS material VARCHAR(150)
    ");


    /* =====================================================
       PRODUCT PRICES TABLE
    ===================================================== */

    $conn->exec("
        CREATE TABLE IF NOT EXISTS product_prices (

            price_id SERIAL PRIMARY KEY,

            product_id INTEGER NOT NULL,

            original_price DECIMAL(10,2)
            NOT NULL DEFAULT 0,

            discount_percentage DECIMAL(5,2)
            DEFAULT 0,

            selling_price DECIMAL(10,2)
            NOT NULL DEFAULT 0,

            start_date DATE,

            end_date DATE,

            created_at TIMESTAMP
            DEFAULT CURRENT_TIMESTAMP,

            CONSTRAINT fk_product_price

            FOREIGN KEY (product_id)

            REFERENCES products(product_id)

            ON DELETE CASCADE
        )
    ");


    /* =====================================================
       PRODUCT IMAGES TABLE
    ===================================================== */

    $conn->exec("
        CREATE TABLE IF NOT EXISTS product_images (

            image_id SERIAL PRIMARY KEY,

            product_id INTEGER NOT NULL,

            image_name VARCHAR(255) NOT NULL,

            is_primary INTEGER DEFAULT 0,

            created_at TIMESTAMP
            DEFAULT CURRENT_TIMESTAMP,

            CONSTRAINT fk_product_images

            FOREIGN KEY (product_id)

            REFERENCES products(product_id)

            ON DELETE CASCADE
        )
    ");


    /* =====================================================
       CART TABLE
    ===================================================== */

    $conn->exec("
        CREATE TABLE IF NOT EXISTS cart (

            cart_id SERIAL PRIMARY KEY,

            user_id INTEGER NOT NULL,

            product_id INTEGER NOT NULL,

            quantity INTEGER NOT NULL DEFAULT 1,

            created_at TIMESTAMP
            DEFAULT CURRENT_TIMESTAMP,


            /* USER FOREIGN KEY */

            CONSTRAINT cart_user_fk

            FOREIGN KEY (user_id)

            REFERENCES users(user_id)

            ON DELETE CASCADE,


            /* PRODUCT FOREIGN KEY */

            CONSTRAINT cart_product_fk

            FOREIGN KEY (product_id)

            REFERENCES products(product_id)

            ON DELETE CASCADE,


            /* QUANTITY MUST BE GREATER THAN ZERO */

            CONSTRAINT cart_quantity_check

            CHECK (quantity > 0),


            /* SAME PRODUCT ONLY ONCE FOR SAME USER */

            CONSTRAINT unique_user_product

            UNIQUE (user_id, product_id)

        )
    ");


    /* =====================================================
       ORDERS TABLE
    ===================================================== */

    $conn->exec("
        CREATE TABLE IF NOT EXISTS orders (

            order_id SERIAL PRIMARY KEY,

            user_id INTEGER,

            total_amount DECIMAL(10,2)
            DEFAULT 0,

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


    /* USER PROFILE */

    $conn->exec("
        CREATE INDEX IF NOT EXISTS
        idx_user_profile_user_id

        ON user_profile(user_id)
    ");


    /* PRODUCTS */

    $conn->exec("
        CREATE INDEX IF NOT EXISTS
        idx_products_subcategory

        ON products(subcategory_id)
    ");


    /* PRODUCT PRICES */

    $conn->exec("
        CREATE INDEX IF NOT EXISTS
        idx_product_prices_product_id

        ON product_prices(product_id)
    ");


    /* PRODUCT IMAGES */

    $conn->exec("
        CREATE INDEX IF NOT EXISTS
        idx_product_images_product_id

        ON product_images(product_id)
    ");


    /* CART USER */

    $conn->exec("
        CREATE INDEX IF NOT EXISTS
        idx_cart_user_id

        ON cart(user_id)
    ");


    /* CART PRODUCT */

    $conn->exec("
        CREATE INDEX IF NOT EXISTS
        idx_cart_product_id

        ON cart(product_id)
    ");


    /* ORDERS USER */

    $conn->exec("
        CREATE INDEX IF NOT EXISTS
        idx_orders_user_id

        ON orders(user_id)
    ");


    /* ORDER ITEMS */

    $conn->exec("
        CREATE INDEX IF NOT EXISTS
        idx_order_items_order_id

        ON order_items(order_id)
    ");
} catch (PDOException $e) {

    die("Database Error: " .
        htmlspecialchars($e->getMessage()));
}

?>
