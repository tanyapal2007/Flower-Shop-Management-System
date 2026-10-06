<?php

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

require_once "config/database.php";


/* =========================================================
   FETCH PRODUCTS
========================================================= */

$sql = "
    SELECT
        p.product_id,
        p.product_name,
        p.product_code,
        p.stock_quantity,
        p.product_description,
        p.product_image,
        p.status,

        ps.subcategory_id,
        ps.subcategory_name,

        pc.category_id,
        pc.category_name,

        pp.original_price,
        pp.discount_percentage,
        pp.selling_price,

        pi.image_name AS uploaded_image

    FROM products p

    LEFT JOIN product_subcategory ps
        ON p.subcategory_id = ps.subcategory_id

    LEFT JOIN product_category pc
        ON ps.category_id = pc.category_id

    LEFT JOIN LATERAL
    (
        SELECT
            product_prices.original_price,
            product_prices.discount_percentage,
            product_prices.selling_price

        FROM product_prices

        WHERE product_prices.product_id = p.product_id

        ORDER BY product_prices.price_id DESC

        LIMIT 1

    ) pp ON TRUE

    LEFT JOIN LATERAL
    (
        SELECT
            product_images.image_name

        FROM product_images

        WHERE product_images.product_id = p.product_id

        ORDER BY product_images.image_id DESC

        LIMIT 1

    ) pi ON TRUE

    WHERE p.status = 1

    ORDER BY p.product_id DESC
";


try {

  $stmt = $conn->prepare($sql);
  $stmt->execute();

  $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {

  die("Product Fetch Error: " .
    htmlspecialchars($e->getMessage()));
}


/* =========================================================
   CATEGORY / SUBCATEGORY DATA
========================================================= */

$categories = [];
$subcategories = [];

foreach ($products as $product) {

  $categoryId =
    (int)($product['category_id'] ?? 0);

  $categoryName =
    trim($product['category_name'] ?? '');

  $subcategoryId =
    (int)($product['subcategory_id'] ?? 0);

  $subcategoryName =
    trim($product['subcategory_name'] ?? '');


  if (
    $categoryId > 0 &&
    $categoryName !== ''
  ) {

    $categories[$categoryId] =
      $categoryName;
  }


  if (
    $subcategoryId > 0 &&
    $subcategoryName !== ''
  ) {

    $subcategories[$subcategoryId] = [

      'id' => $subcategoryId,

      'name' => $subcategoryName,

      'category_id' => $categoryId

    ];
  }
}


asort($categories);


usort(
  $subcategories,
  function ($a, $b) {

    return strcasecmp(
      $a['name'],
      $b['name']
    );
  }
);

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

  <meta
    name="keywords"
    content="flowers, flower gallery, bouquet, flower basket">

  <meta
    name="description"
    content="Fior Flower Gallery">

  <meta
    name="author"
    content="Fior">

  <title>Fior - Gallery</title>


  <!-- =====================================================
       OWL CAROUSEL
  ====================================================== -->

  <link
    rel="stylesheet"
    type="text/css"
    href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.1.3/assets/owl.carousel.min.css">


  <!-- =====================================================
       BOOTSTRAP
  ====================================================== -->

  <link
    rel="stylesheet"
    type="text/css"
    href="css/bootstrap.css">


  <!-- =====================================================
       FONT AWESOME
  ====================================================== -->

  <link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">


  <!-- =====================================================
       GOOGLE FONT
  ====================================================== -->

  <link
    href="https://fonts.googleapis.com/css?family=Baloo+Chettan|Poppins:400,600,700&display=swap"
    rel="stylesheet">


  <!-- =====================================================
       MAIN CSS
  ====================================================== -->

  <link
    href="css/style.css"
    rel="stylesheet">


  <!-- =====================================================
       RESPONSIVE CSS
  ====================================================== -->

  <link
    href="css/responsive.css"
    rel="stylesheet">


  <style>
    /* =====================================================
       GALLERY
    ====================================================== */

    .gallery_content {

      display: block;

      width: 100%;

    }


    .gallery_sidebar {

      display: none;

    }


    .gallery_products {

      width: 100%;

      min-width: 0;

    }


    /* =====================================================
       TOP CONTROLS
    ====================================================== */

    .gallery_top_controls {

      width: 100%;

      display: flex;

      align-items: flex-start;

      gap: 15px;

      margin-bottom: 20px;

    }


    /* =====================================================
       TYPES
    ====================================================== */

    .gallery_types_wrapper {

      width: 220px;

      min-width: 220px;

      position: relative;

      z-index: 10000;

    }


    .types_hover_btn {

      width: 100%;

      height: 46px;

      border: 1px solid #eeeeee;

      background: #ffffff;

      color: #17233c;

      border-radius: 7px;

      padding: 0 15px;

      display: flex;

      align-items: center;

      justify-content: space-between;

      font-size: 14px;

      font-weight: 600;

      cursor: pointer;

      box-shadow:
        0 2px 10px rgba(0, 0, 0, 0.05);

      transition: 0.3s;

    }


    .types_hover_btn:hover {

      border-color: #df2f68;

      color: #df2f68;

    }


    .types_hover_left {

      display: flex;

      align-items: center;

      gap: 8px;

    }


    .types_hover_icon {

      font-size: 16px;

      color: #df2f68;

    }


    .types_arrow {

      font-size: 11px;

      color: #777777;

      transition: 0.3s;

    }


    .gallery_types_panel {

      width: 220px;

      position: absolute;

      left: 0;

      top: 50px;

      background: #ffffff;

      border: 1px solid #eeeeee;

      border-radius: 8px;

      padding: 10px;

      box-shadow:
        0 8px 25px rgba(0, 0, 0, 0.15);

      opacity: 0;

      visibility: hidden;

      transform: translateY(-8px);

      transition:
        opacity 0.20s ease,
        visibility 0.20s ease,
        transform 0.20s ease;

    }


    .gallery_types_wrapper:hover .gallery_types_panel {

      opacity: 1;

      visibility: visible;

      transform: translateY(0);

    }


    .gallery_types_wrapper:hover .types_arrow {

      transform: rotate(180deg);

    }


    .type_filter_btn {

      width: 100%;

      border: none;

      background: #ffffff;

      color: #555555;

      text-align: left;

      padding: 10px 12px;

      border-radius: 5px;

      font-size: 13px;

      cursor: pointer;

      transition: 0.3s;

      margin-bottom: 3px;

    }


    .type_filter_btn:hover {

      background: #fff0f5;

      color: #df2f68;

    }


    .type_filter_btn.active {

      background: #df2f68;

      color: #ffffff;

    }


    /* =====================================================
       SEARCH
    ====================================================== */

    .gallery_search_wrapper {

      flex: 1;

      width: auto;

      min-width: 0;

      position: relative;

      z-index: 9998;

    }


    .gallery_search_box {

      width: 100%;

      height: 46px;

      display: flex;

      align-items: center;

      background: #ffffff;

      border: 1px solid #eeeeee;

      border-radius: 7px;

      box-shadow:
        0 2px 10px rgba(0, 0, 0, 0.05);

      overflow: hidden;

      transition: 0.3s;

    }


    .gallery_search_box:focus-within {

      border-color: #df2f68;

      box-shadow:
        0 2px 10px rgba(223, 47, 104, 0.10);

    }


    .gallery_search_icon {

      width: 45px;

      min-width: 45px;

      text-align: center;

      color: #df2f68;

      font-size: 16px;

    }


    .gallery_search_icon i {

      color: #df2f68;

    }


    .gallery_search_input {

      flex: 1;

      width: 100%;

      height: 100%;

      border: none;

      outline: none;

      background: transparent;

      color: #333333;

      font-size: 13px;

      padding: 0 10px;

      min-width: 0;

    }


    .gallery_search_input::placeholder {

      color: #999999;

    }


    .gallery_search_btn {

      height: 34px;

      min-width: 45px;

      margin-right: 6px;

      border: none;

      background: #df2f68;

      color: #ffffff;

      border-radius: 5px;

      cursor: pointer;

      font-size: 14px;

      transition: 0.3s;

    }


    .gallery_search_btn:hover {

      background: #c92359;

    }


    .gallery_search_btn i {

      font-size: 14px;

    }


    /* =====================================================
       FILTER
    ====================================================== */

    .gallery_filter_wrapper {

      width: 220px;

      min-width: 220px;

      position: relative;

      z-index: 9999;

    }


    .filter_hover_btn {

      width: 100%;

      height: 46px;

      border: 1px solid #eeeeee;

      background: #ffffff;

      color: #17233c;

      border-radius: 7px;

      padding: 0 15px;

      display: flex;

      align-items: center;

      justify-content: space-between;

      font-size: 14px;

      font-weight: 600;

      cursor: pointer;

      box-shadow:
        0 2px 10px rgba(0, 0, 0, 0.05);

      transition: 0.3s;

    }


    .filter_hover_btn:hover {

      border-color: #df2f68;

      color: #df2f68;

    }


    .filter_hover_left {

      display: flex;

      align-items: center;

      gap: 8px;

    }


    .filter_hover_icon {

      font-size: 16px;

      color: #df2f68;

    }


    .filter_arrow {

      font-size: 11px;

      color: #777777;

      transition: 0.3s;

    }


    .gallery_filter_panel {

      width: 260px;

      position: absolute;

      right: 0;

      top: 50px;

      background: #ffffff;

      border: 1px solid #eeeeee;

      border-radius: 8px;

      padding: 18px;

      box-shadow:
        0 8px 25px rgba(0, 0, 0, 0.15);

      opacity: 0;

      visibility: hidden;

      transform: translateY(-8px);

      transition:
        opacity 0.20s ease,
        visibility 0.20s ease,
        transform 0.20s ease;

    }


    .gallery_filter_wrapper:hover .gallery_filter_panel {

      opacity: 1;

      visibility: visible;

      transform: translateY(0);

    }


    .gallery_filter_wrapper:hover .filter_arrow {

      transform: rotate(180deg);

    }


    .filter_heading {

      display: flex;

      align-items: center;

      justify-content: space-between;

      border-bottom: 1px solid #eeeeee;

      padding-bottom: 11px;

      margin-bottom: 17px;

    }


    .filter_heading h4 {

      margin: 0;

      font-size: 17px;

      font-weight: 600;

      color: #17233c;

    }


    .filter_icon {

      color: #df2f68;

      font-size: 17px;

    }


    .filter_group {

      margin-bottom: 16px;

    }


    .filter_group label {

      display: block;

      font-size: 13px;

      font-weight: 600;

      color: #555555;

      margin-bottom: 6px;

    }


    .filter_select {

      width: 100%;

      height: 39px;

      padding: 6px 9px;

      border: 1px solid #dddddd;

      border-radius: 5px;

      background: #ffffff;

      color: #555555;

      font-size: 13px;

      outline: none;

      cursor: pointer;

    }


    .filter_select:hover,

    .filter_select:focus {

      border-color: #df2f68;

    }


    .filter_buttons {

      display: flex;

      gap: 7px;

      margin-top: 5px;

    }


    .apply_filter_btn {

      flex: 1;

      border: none;

      background: #df2f68;

      color: #ffffff;

      padding: 9px;

      border-radius: 5px;

      font-size: 13px;

      cursor: pointer;

    }


    .apply_filter_btn:hover {

      background: #c92359;

    }


    .clear_filter_btn {

      flex: 1;

      border: 1px solid #dddddd;

      background: #ffffff;

      color: #555555;

      padding: 9px;

      border-radius: 5px;

      font-size: 13px;

      cursor: pointer;

    }


    .clear_filter_btn:hover {

      border-color: #df2f68;

      color: #df2f68;

    }


    .filter_result {

      margin-top: 11px;

      text-align: center;

      font-size: 12px;

      color: #888888;

    }


    /* =====================================================
       PRODUCT GRID
    ====================================================== */

    .gallery_grid {

      display: grid;

      grid-template-columns:
        repeat(3, 1fr);

      gap: 20px;

      width: 100%;

    }


    .gallery_item {

      background: #ffffff;

      border: 1px solid #eeeeee;

      border-radius: 8px;

      overflow: hidden;

      padding: 8px;

      transition: 0.3s;

    }


    .gallery_item:hover {

      box-shadow:
        0 5px 20px rgba(0, 0, 0, 0.10);

      transform: translateY(-3px);

    }


    .gallery_item img {

      width: 100%;

      height: 220px;

      object-fit: cover;

      border-radius: 5px;

      display: block;

    }


    .product_info {

      padding: 10px 4px 4px;

    }


    .product_category {

      font-size: 13px;

      color: #888888;

      margin-bottom: 5px;

    }


    .product_info h5 {

      margin: 0 0 6px;

      font-size: 17px;

      color: #17233c;

      font-weight: 600;

    }


    .product_name_link {

      color: #17233c;

      text-decoration: none;

      transition: 0.3s;

    }


    .product_name_link:hover {

      color: #df2f68;

      text-decoration: none;

    }


    .product_price {

      margin-bottom: 10px;

    }


    .selling_price {

      color: #e52d68;

      font-size: 18px;

      font-weight: 700;

    }


    .original_price {

      color: #999999;

      text-decoration: line-through;

      font-size: 13px;

      margin-left: 8px;

    }


    .discount {

      background: #e52d68;

      color: #ffffff;

      padding: 3px 7px;

      border-radius: 15px;

      font-size: 11px;

      margin-left: 5px;

    }


    /* =====================================================
       PRODUCT BUTTONS
    ====================================================== */

    .product_buttons {

      display: flex;

      gap: 6px;

      align-items: center;

      margin-top: 8px;

    }


    .add-cart-btn {

      flex: 1;

      border: none;

      background: #df2f68;

      color: #ffffff;

      padding: 10px 8px;

      border-radius: 6px;

      font-size: 13px;

      cursor: pointer;

    }


    .add-cart-btn:hover {

      background: #c92359;

    }


    .buy-now-btn {

      flex: 1;

      border: none;

      background: #17233c;

      color: #ffffff;

      padding: 10px 8px;

      border-radius: 6px;

      font-size: 13px;

      cursor: pointer;

    }


    .buy-now-btn:hover {

      background: #0d1628;

    }


    .wishlist-btn {

      width: 42px;

      min-width: 42px;

      height: 40px;

      border: 1px solid #dddddd;

      background: #ffffff;

      color: #777777;

      border-radius: 6px;

      display: flex;

      align-items: center;

      justify-content: center;

      font-size: 20px;

      cursor: pointer;

    }


    .wishlist-btn:hover {

      border-color: #df2f68;

      color: #df2f68;

    }


    .wishlist-btn.active {

      background: #df2f68;

      border-color: #df2f68;

      color: #ffffff;

    }


    /* =====================================================
       HIDDEN PRODUCT
    ====================================================== */

    .gallery_item.hidden {

      display: none !important;

    }


    /* =====================================================
       VIEW ALL BUTTON
    ====================================================== */

    .view_all_container {

      width: 100%;

      text-align: center;

      margin-top: 30px;

    }


    .view_all_btn {

      border: none;

      background: #df2f68;

      color: #ffffff;

      padding: 12px 30px;

      border-radius: 6px;

      font-size: 14px;

      font-weight: 600;

      cursor: pointer;

      transition: 0.3s;

      box-shadow:
        0 3px 10px rgba(223, 47, 104, 0.20);

    }


    .view_all_btn:hover {

      background: #c92359;

      transform: translateY(-2px);

    }


    /* =====================================================
       NO PRODUCTS
    ====================================================== */

    .no-products {

      text-align: center;

      width: 100%;

      padding: 50px 0;

      color: #777777;

    }


    /* =====================================================
       RESPONSIVE
    ====================================================== */

    @media (max-width: 992px) {

      .gallery_top_controls {

        gap: 10px;

      }


      .gallery_types_wrapper,
      .gallery_filter_wrapper {

        width: 190px;

        min-width: 190px;

      }

    }


    @media (max-width: 768px) {

      .gallery_top_controls {

        flex-wrap: wrap;

      }


      .gallery_types_wrapper,
      .gallery_filter_wrapper {

        width: calc(50% - 5px);

        min-width: 0;

      }


      .gallery_search_wrapper {

        width: 100%;

        flex: none;

        order: 3;

      }


      .gallery_grid {

        grid-template-columns:
          repeat(2, 1fr);

      }

    }


    @media (max-width: 576px) {

      .gallery_top_controls {

        gap: 10px;

      }


      .gallery_types_wrapper,
      .gallery_filter_wrapper {

        width: calc(50% - 5px);

      }


      .gallery_search_wrapper {

        width: 100%;

      }


      .gallery_types_panel {

        width: 200px;

      }


      .gallery_filter_panel {

        width: 250px;

        right: 0;

      }


      .gallery_grid {

        grid-template-columns: 1fr;

      }


      .product_buttons {

        flex-wrap: wrap;

      }

    }
  </style>

</head>


<body class="sub_page">


  <!-- =====================================================
     HEADER
====================================================== -->

  <div class="hero_area">

    <?php include 'header.php'; ?>

  </div>


  <!-- =====================================================
     GALLERY
====================================================== -->

  <section class="gallery_section layout_padding">


    <div class="heading_container justify-content-center">

      <h2>
        Our Flower Gallery
      </h2>

    </div>


    <div class="container">


      <div class="gallery_content">


        <!-- =================================================
           TOP CONTROLS
      ================================================== -->

        <div class="gallery_top_controls">


          <!-- =================================================
             TYPES
        ================================================== -->

          <div class="gallery_types_wrapper">


            <div class="types_hover_btn">

              <div class="types_hover_left">

                <span class="types_hover_icon">
                  <i class="fa fa-bars"></i>
                </span>

                <span>
                  Types
                </span>

              </div>


              <span class="types_arrow">
                <i class="fa fa-chevron-down"></i>
              </span>

            </div>


            <div class="gallery_types_panel">


              <button
                type="button"
                class="type_filter_btn active"
                data-type-filter="all">

                All Flowers

              </button>


              <button
                type="button"
                class="type_filter_btn"
                data-type-filter="bouquet">

                Bouquet

              </button>


              <button
                type="button"
                class="type_filter_btn"
                data-type-filter="basket">

                Basket Bouquet

              </button>


              <button
                type="button"
                class="type_filter_btn"
                data-type-filter="single">

                Single Flowers

              </button>


            </div>

          </div>


          <!-- =================================================
             SEARCH
        ================================================== -->

          <div class="gallery_search_wrapper">

            <div class="gallery_search_box">

              <span class="gallery_search_icon">
                <i class="fa fa-search"></i>
              </span>


              <input
                type="text"
                id="gallerySearchInput"
                class="gallery_search_input"
                placeholder="Search flowers..."
                autocomplete="off">


              <button
                type="button"
                id="gallerySearchButton"
                class="gallery_search_btn"
                title="Search">

                <i class="fa fa-search"></i>

              </button>

            </div>

          </div>


          <!-- =================================================
             FILTERS
        ================================================== -->

          <div class="gallery_filter_wrapper">


            <div class="filter_hover_btn">

              <div class="filter_hover_left">

                <span class="filter_hover_icon">
                  <i class="fa fa-filter"></i>
                </span>

                <span>
                  Filters
                </span>

              </div>


              <span class="filter_arrow">
                <i class="fa fa-chevron-down"></i>
              </span>

            </div>


            <div class="gallery_filter_panel">


              <div class="filter_heading">

                <h4>
                  Filter Flowers
                </h4>

                <span class="filter_icon">
                  <i class="fa fa-filter"></i>
                </span>

              </div>


              <!-- CATEGORY -->

              <div class="filter_group">

                <label for="categoryFilter">
                  Category
                </label>


                <select
                  id="categoryFilter"
                  class="filter_select">

                  <option value="all">
                    All Categories
                  </option>


                  <?php foreach (
                    $categories
                    as $categoryId =>
                    $categoryName
                  ) { ?>

                    <option
                      value="<?php
                              echo (int)$categoryId;
                              ?>">

                      <?php
                      echo htmlspecialchars(
                        $categoryName
                      );
                      ?>

                    </option>

                  <?php } ?>

                </select>

              </div>


              <!-- SUBCATEGORY -->

              <div class="filter_group">

                <label for="subcategoryFilter">
                  Subcategory
                </label>


                <select
                  id="subcategoryFilter"
                  class="filter_select">

                  <option value="all">
                    All Subcategories
                  </option>


                  <?php foreach (
                    $subcategories
                    as $subcategoryItem
                  ) { ?>

                    <option
                      value="<?php
                              echo (int)
                              $subcategoryItem['id'];
                              ?>"
                      data-category-id="<?php
                                        echo (int)
                                        $subcategoryItem['category_id'];
                                        ?>">

                      <?php
                      echo htmlspecialchars(
                        $subcategoryItem['name']
                      );
                      ?>

                    </option>

                  <?php } ?>

                </select>

              </div>


              <!-- PRICE -->

              <div class="filter_group">

                <label for="priceFilter">
                  Price
                </label>


                <select
                  id="priceFilter"
                  class="filter_select">

                  <option value="all">
                    All Prices
                  </option>


                  <option value="0-500">
                    Under ₹500
                  </option>


                  <option value="500-1000">
                    ₹500 - ₹1,000
                  </option>


                  <option value="1000-2000">
                    ₹1,000 - ₹2,000
                  </option>


                  <option value="2000-5000">
                    ₹2,000 - ₹5,000
                  </option>


                  <option value="5000-plus">
                    Above ₹5,000
                  </option>

                </select>

              </div>


              <div class="filter_buttons">

                <button
                  type="button"
                  id="applyFilters"
                  class="apply_filter_btn">

                  Apply

                </button>


                <button
                  type="button"
                  id="clearFilters"
                  class="clear_filter_btn">

                  Clear

                </button>

              </div>


              <div
                id="filterResult"
                class="filter_result">

                Showing 6 flowers

              </div>


            </div>

          </div>

        </div>


        <!-- =================================================
           PRODUCTS
      ================================================== -->

        <div class="gallery_products">


          <div class="gallery_grid">


            <?php if (!empty($products)) { ?>


              <?php foreach (
                $products
                as $index =>
                $product
              ) { ?>


                <?php

                /* =================================================
                 IMAGE
              ================================================= */

                $uploadedImage =
                  trim(
                    $product['uploaded_image']
                      ?? ''
                  );


                if (
                  $uploadedImage !== ''
                ) {

                  $imagePath =
                    "uploads/products/" .
                    basename(
                      $uploadedImage
                    );
                } elseif (
                  !empty($product['product_image'])
                ) {

                  $oldImage =
                    trim(
                      $product['product_image']
                    );


                  if (
                    strpos(
                      $oldImage,
                      'assets/images/'
                    ) === 0
                  ) {

                    $imagePath =
                      $oldImage;
                  } else {

                    $imagePath =
                      "assets/images/" .
                      basename(
                        $oldImage
                      );
                  }
                } else {

                  $imagePath =
                    "assets/images/no-image.jpg";
                }


                /* =================================================
                 PRICE
              ================================================= */

                $sellingPrice =
                  $product['selling_price']
                  ?? null;


                $originalPrice =
                  $product['original_price']
                  ?? null;


                $discount =
                  $product['discount_percentage']
                  ?? null;


                /* =================================================
                 STOCK
              ================================================= */

                $stock =
                  (int)(
                    $product['stock_quantity']
                    ?? 0
                  );


                $outOfStock =
                  ($stock <= 0);


                /* =================================================
                 PRODUCT TYPE
              ================================================= */

                $productName =
                  strtolower(
                    trim(
                      $product['product_name']
                        ?? ''
                    )
                  );


                $subcategory =
                  strtolower(
                    trim(
                      $product['subcategory_name']
                        ?? ''
                    )
                  );


                $category =
                  strtolower(
                    trim(
                      $product['category_name']
                        ?? ''
                    )
                  );


                $description =
                  strtolower(
                    trim(
                      $product['product_description']
                        ?? ''
                    )
                  );


                $searchText =
                  $productName . ' ' .
                  $subcategory . ' ' .
                  $category . ' ' .
                  $description;


                $isBasket =
                  strpos(
                    $searchText,
                    'basket'
                  ) !== false
                  ||
                  strpos(
                    $searchText,
                    'flower basket'
                  ) !== false;


                $isBouquet =
                  strpos(
                    $searchText,
                    'bouquet'
                  ) !== false;


                if ($isBasket) {

                  $productType =
                    'basket';
                } elseif (
                  $isBouquet
                ) {

                  $productType =
                    'bouquet';
                } else {

                  $productType =
                    'single';
                }


                /* =================================================
                 FILTER VALUES
              ================================================== */

                $productCategoryId =
                  (int)(
                    $product['category_id']
                    ?? 0
                  );


                $productSubcategoryId =
                  (int)(
                    $product['subcategory_id']
                    ?? 0
                  );


                $productPrice =
                  (float)(
                    $sellingPrice ?? 0
                  );

                ?>


                <!-- =================================================
                   PRODUCT CARD
              ================================================== -->

                <div
                  class="gallery_item"
                  data-index="<?php
                              echo $index;
                              ?>"
                  data-type="<?php
                              echo htmlspecialchars(
                                $productType
                              );
                              ?>"
                  data-category-id="<?php
                                    echo $productCategoryId;
                                    ?>"
                  data-subcategory-id="<?php
                                        echo $productSubcategoryId;
                                        ?>"
                  data-price="<?php
                              echo $productPrice;
                              ?>">


                  <img
                    src="<?php
                          echo htmlspecialchars(
                            $imagePath
                          );
                          ?>"
                    alt="<?php
                          echo htmlspecialchars(
                            $product['product_name']
                          );
                          ?>">


                  <div class="product_info">


                    <div class="product_category">

                      <?php
                      echo htmlspecialchars(
                        $product['subcategory_name']
                          ?? 'Flowers'
                      );
                      ?>

                    </div>


                    <h5>

                      <a
                        href="product-details.php?product_id=<?php
                                                              echo (int)
                                                              $product['product_id'];
                                                              ?>"
                        class="product_name_link">

                        <?php
                        echo htmlspecialchars(
                          $product['product_name']
                        );
                        ?>

                      </a>

                    </h5>


                    <div class="product_price">


                      <?php if (
                        $sellingPrice !== null
                      ) { ?>

                        <span
                          class="selling_price">

                          ₹<?php
                            echo number_format(
                              (float)$sellingPrice,
                              2
                            );
                            ?>

                        </span>

                      <?php } else { ?>

                        <span
                          class="selling_price">

                          Price Not Available

                        </span>

                      <?php } ?>


                      <?php if (
                        $originalPrice !== null &&
                        $sellingPrice !== null &&
                        (float)$originalPrice >
                        (float)$sellingPrice
                      ) { ?>

                        <span
                          class="original_price">

                          ₹<?php
                            echo number_format(
                              (float)$originalPrice,
                              2
                            );
                            ?>

                        </span>

                      <?php } ?>


                      <?php if (
                        $discount !== null &&
                        (float)$discount > 0
                      ) { ?>

                        <span
                          class="discount">

                          <?php
                          echo number_format(
                            (float)$discount,
                            0
                          );
                          ?>% OFF

                        </span>

                      <?php } ?>


                    </div>


                    <!-- =================================================
                       BUTTONS
                  ================================================== -->

                    <?php if (
                      $outOfStock
                    ) { ?>


                      <div
                        class="product_buttons">


                        <button
                          type="button"
                          class="add-cart-btn"
                          disabled>

                          Out of Stock

                        </button>


                        <button
                          type="button"
                          class="buy-now-btn"
                          disabled>

                          Buy Now

                        </button>


                        <button
                          type="button"
                          class="wishlist-btn"
                          disabled>

                          <i class="fa fa-heart-o"></i>

                        </button>


                      </div>


                    <?php } else { ?>


                      <div
                        class="product_buttons">


                        <button
                          type="button"
                          class="add-cart-btn"
                          data-product-id="<?php
                                            echo (int)
                                            $product['product_id'];
                                            ?>">

                          <i class="fa fa-shopping-cart"></i>
                          Add to Cart

                        </button>


                        <button
                          type="button"
                          class="buy-now-btn"
                          data-product-id="<?php
                                            echo (int)
                                            $product['product_id'];
                                            ?>">

                          Buy Now

                        </button>


                        <button
                          type="button"
                          class="wishlist-btn"
                          data-product-id="<?php
                                            echo (int)
                                            $product['product_id'];
                                            ?>"
                          title="Add to Wishlist">

                          <i class="fa fa-heart-o"></i>

                        </button>


                      </div>


                    <?php } ?>


                  </div>

                </div>


              <?php } ?>


            <?php } else { ?>


              <div class="no-products">

                <h4>
                  No flowers available.
                </h4>

                <p>
                  Please add products from
                  the admin panel.
                </p>

              </div>


            <?php } ?>


          </div>


          <!-- =================================================
             VIEW ALL BUTTON
        ================================================== -->

          <?php if (
            count($products) > 6
          ) { ?>

            <div
              class="view_all_container">


              <button
                type="button"
                id="viewAllBtn"
                class="view_all_btn">

                View More Products

              </button>


            </div>

          <?php } ?>


          <!-- =================================================
             NO FILTER RESULTS
        ================================================== -->

          <div
            id="noFilterResults"
            class="no-products"
            style="display:none;">

            <h4>
              No flowers found.
            </h4>

            <p>
              Try changing your filters.
            </p>

          </div>


        </div>

      </div>

    </div>

  </section>


  <!-- =====================================================
     FOOTER
====================================================== -->

  <?php include 'footer.php'; ?>


  <!-- =====================================================
     JS FILES
====================================================== -->

  <script
    type="text/javascript"
    src="js/jquery-3.4.1.min.js">
  </script>


  <script
    type="text/javascript"
    src="js/bootstrap.js">
  </script>


  <script
    type="text/javascript"
    src="js/custom.js">
  </script>


  <!-- =====================================================
     GALLERY FILTER + SEARCH
====================================================== -->

  <script>
    /* =====================================================
     FILTER ELEMENTS
  ====================================================== */

    var gallerySearchInput =
      document.getElementById(
        'gallerySearchInput'
      );


    var gallerySearchButton =
      document.getElementById(
        'gallerySearchButton'
      );


    var categoryFilter =
      document.getElementById(
        'categoryFilter'
      );


    var subcategoryFilter =
      document.getElementById(
        'subcategoryFilter'
      );


    var priceFilter =
      document.getElementById(
        'priceFilter'
      );


    var applyFiltersButton =
      document.getElementById(
        'applyFilters'
      );


    var clearFiltersButton =
      document.getElementById(
        'clearFilters'
      );


    var filterResult =
      document.getElementById(
        'filterResult'
      );


    var noFilterResults =
      document.getElementById(
        'noFilterResults'
      );


    var viewAllBtn =
      document.getElementById(
        'viewAllBtn'
      );


    /* =====================================================
       SETTINGS
    ====================================================== */

    var productsPerPage = 6;

    var showAllProducts = false;

    var selectedType = 'all';


    /* =====================================================
       SEARCH
    ====================================================== */

    function applyGallerySearch() {

      showAllProducts = false;

      applyAllFilters();

    }


    if (gallerySearchButton) {

      gallerySearchButton.addEventListener(
        'click',
        function() {

          applyGallerySearch();

        }
      );

    }


    if (gallerySearchInput) {

      gallerySearchInput.addEventListener(
        'keydown',
        function(event) {

          if (event.key === 'Enter') {

            event.preventDefault();

            applyGallerySearch();

          }

        }
      );


      gallerySearchInput.addEventListener(
        'input',
        function() {

          applyGallerySearch();

        }
      );

    }


    /* =====================================================
       TYPE BUTTONS
    ====================================================== */

    document
      .querySelectorAll(
        '.type_filter_btn'
      )
      .forEach(
        function(button) {

          button.addEventListener(
            'click',
            function() {

              document
                .querySelectorAll(
                  '.type_filter_btn'
                )
                .forEach(
                  function(btn) {

                    btn.classList.remove(
                      'active'
                    );

                  }
                );


              this.classList.add(
                'active'
              );


              selectedType =
                this.getAttribute(
                  'data-type-filter'
                );


              showAllProducts =
                false;


              applyAllFilters();

            }
          );

        }
      );


    /* =====================================================
       CATEGORY CHANGE
    ====================================================== */

    if (categoryFilter) {

      categoryFilter.addEventListener(
        'change',
        function() {

          var selectedCategory =
            this.value;


          var options =
            subcategoryFilter
            .querySelectorAll(
              'option'
            );


          options.forEach(
            function(option, index) {

              if (index === 0) {

                option.style.display =
                  '';

                return;

              }


              var optionCategory =
                option.getAttribute(
                  'data-category-id'
                );


              if (
                selectedCategory ===
                'all'
              ) {

                option.style.display =
                  '';

              } else if (
                optionCategory ===
                selectedCategory
              ) {

                option.style.display =
                  '';

              } else {

                option.style.display =
                  'none';

              }

            }
          );


          subcategoryFilter.value =
            'all';


          showAllProducts =
            false;


          applyAllFilters();

        }
      );

    }


    /* =====================================================
       SUBCATEGORY CHANGE
    ====================================================== */

    if (subcategoryFilter) {

      subcategoryFilter.addEventListener(
        'change',
        function() {

          showAllProducts = false;

          applyAllFilters();

        }
      );

    }


    /* =====================================================
       PRICE CHANGE
    ====================================================== */

    if (priceFilter) {

      priceFilter.addEventListener(
        'change',
        function() {

          showAllProducts = false;

          applyAllFilters();

        }
      );

    }


    /* =====================================================
       APPLY ALL FILTERS
    ====================================================== */

    function applyAllFilters() {

      var selectedSearch =
        gallerySearchInput ?
        gallerySearchInput.value
        .trim()
        .toLowerCase() :
        '';


      var selectedCategory =
        categoryFilter ?
        categoryFilter.value :
        'all';


      var selectedSubcategory =
        subcategoryFilter ?
        subcategoryFilter.value :
        'all';


      var selectedPrice =
        priceFilter ?
        priceFilter.value :
        'all';


      var visibleProducts = [];


      /* =================================================
         CHECK ALL FILTERS
      ================================================== */

      document
        .querySelectorAll(
          '.gallery_item'
        )
        .forEach(
          function(product) {

            var productType =
              product.getAttribute(
                'data-type'
              );


            var productCategory =
              product.getAttribute(
                'data-category-id'
              );


            var productSubcategory =
              product.getAttribute(
                'data-subcategory-id'
              );


            var productPrice =
              parseFloat(
                product.getAttribute(
                  'data-price'
                )
              ) || 0;


            var productSearchText =
              product.textContent
              .toLowerCase();


            /* SEARCH */

            var searchMatch =
              selectedSearch === '' ||
              productSearchText.indexOf(
                selectedSearch
              ) !== -1;


            /* TYPE */

            var typeMatch =
              selectedType === 'all' ||
              productType === selectedType;


            /* CATEGORY */

            var categoryMatch =
              selectedCategory === 'all' ||
              productCategory ===
              selectedCategory;


            /* SUBCATEGORY */

            var subcategoryMatch =
              selectedSubcategory === 'all' ||
              productSubcategory ===
              selectedSubcategory;


            /* PRICE */

            var priceMatch = true;


            if (
              selectedPrice !==
              'all'
            ) {

              if (
                selectedPrice ===
                '0-500'
              ) {

                priceMatch =
                  productPrice < 500;

              } else if (
                selectedPrice ===
                '500-1000'
              ) {

                priceMatch =
                  productPrice >= 500 &&
                  productPrice <= 1000;

              } else if (
                selectedPrice ===
                '1000-2000'
              ) {

                priceMatch =
                  productPrice > 1000 &&
                  productPrice <= 2000;

              } else if (
                selectedPrice ===
                '2000-5000'
              ) {

                priceMatch =
                  productPrice > 2000 &&
                  productPrice <= 5000;

              } else if (
                selectedPrice ===
                '5000-plus'
              ) {

                priceMatch =
                  productPrice > 5000;

              }

            }


            /* FINAL */

            if (
              searchMatch &&
              typeMatch &&
              categoryMatch &&
              subcategoryMatch &&
              priceMatch
            ) {

              visibleProducts.push(
                product
              );

            }

          }
        );


      /* =================================================
         HIDE ALL PRODUCTS
      ================================================== */

      document
        .querySelectorAll(
          '.gallery_item'
        )
        .forEach(
          function(product) {

            product.classList.add(
              'hidden'
            );

          }
        );


      /* =================================================
         SHOW PRODUCTS
      ================================================== */

      if (showAllProducts) {

        visibleProducts.forEach(
          function(product) {

            product.classList.remove(
              'hidden'
            );

          }
        );

      } else {

        visibleProducts
          .slice(
            0,
            productsPerPage
          )
          .forEach(
            function(product) {

              product.classList.remove(
                'hidden'
              );

            }
          );

      }


      /* =================================================
         NO RESULT
      ================================================== */

      if (
        visibleProducts.length === 0
      ) {

        noFilterResults.style.display =
          'block';

      } else {

        noFilterResults.style.display =
          'none';

      }


      /* =================================================
         VIEW ALL BUTTON
      ================================================== */

      if (viewAllBtn) {

        if (
          visibleProducts.length >
          productsPerPage
        ) {

          viewAllBtn.style.display =
            'inline-block';


          if (showAllProducts) {

            viewAllBtn.innerHTML =
              'Show Less Products';

          } else {

            viewAllBtn.innerHTML =
              'View More Products';

          }

        } else {

          viewAllBtn.style.display =
            'none';

        }

      }


      /* =================================================
         RESULT TEXT
      ================================================== */

      if (
        selectedSearch === '' &&
        selectedType === 'all' &&
        selectedCategory === 'all' &&
        selectedSubcategory === 'all' &&
        selectedPrice === 'all'
      ) {

        if (
          visibleProducts.length >
          productsPerPage &&
          !showAllProducts
        ) {

          filterResult.innerHTML =
            "Showing " +
            Math.min(
              productsPerPage,
              visibleProducts.length
            ) +
            " of " +
            visibleProducts.length +
            " flowers";

        } else {

          filterResult.innerHTML =
            "Showing " +
            visibleProducts.length +
            " flowers";

        }

      } else {

        if (
          visibleProducts.length >
          productsPerPage &&
          !showAllProducts
        ) {

          filterResult.innerHTML =
            "Showing " +
            productsPerPage +
            " of " +
            visibleProducts.length +
            " flowers";

        } else {

          filterResult.innerHTML =
            "Showing " +
            visibleProducts.length +
            " flower" +
            (
              visibleProducts.length !== 1 ?
              "s" :
              ""
            );

        }

      }

    }


    /* =====================================================
       VIEW ALL / SHOW LESS
    ====================================================== */

    if (viewAllBtn) {

      viewAllBtn.addEventListener(
        'click',
        function() {

          showAllProducts = !showAllProducts;


          applyAllFilters();


          if (showAllProducts) {

            viewAllBtn.innerHTML =
              'Show Less Products';

          } else {

            viewAllBtn.innerHTML =
              'View All Products';


            var galleryProducts =
              document.querySelector(
                '.gallery_products'
              );


            if (galleryProducts) {

              galleryProducts.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
              });

            }

          }

        }
      );

    }


    /* =====================================================
       APPLY BUTTON
    ====================================================== */

    if (applyFiltersButton) {

      applyFiltersButton.addEventListener(
        'click',
        function() {

          showAllProducts =
            false;

          applyAllFilters();

        }
      );

    }


    /* =====================================================
       CLEAR FILTERS
    ====================================================== */

    if (clearFiltersButton) {

      clearFiltersButton.addEventListener(
        'click',
        function() {

          if (gallerySearchInput) {

            gallerySearchInput.value =
              '';

          }


          if (categoryFilter) {

            categoryFilter.value =
              'all';

          }


          if (subcategoryFilter) {

            subcategoryFilter.value =
              'all';

          }


          if (priceFilter) {

            priceFilter.value =
              'all';

          }


          selectedType =
            'all';


          showAllProducts =
            false;


          /* RESET TYPE */

          document
            .querySelectorAll(
              '.type_filter_btn'
            )
            .forEach(
              function(btn) {

                btn.classList.remove(
                  'active'
                );

              }
            );


          var allTypeButton =
            document.querySelector(
              '.type_filter_btn[data-type-filter="all"]'
            );


          if (allTypeButton) {

            allTypeButton.classList.add(
              'active'
            );

          }


          /* SHOW SUBCATEGORIES */

          if (subcategoryFilter) {

            subcategoryFilter
              .querySelectorAll(
                'option'
              )
              .forEach(
                function(option) {

                  option.style.display =
                    '';

                }
              );

          }


          applyAllFilters();

        }
      );

    }


    /* =====================================================
       INITIAL LOAD
    ====================================================== */

    applyAllFilters();
  </script>




  <!-- =====================================================
     ADD TO CART
     NO CART PAGE REDIRECT + CART COUNTER
====================================================== -->

  <script>
    document
      .querySelectorAll('.add-cart-btn')
      .forEach(function(button) {

        button.addEventListener('click', function() {

          var productId =
            this.getAttribute('data-product-id');

          var currentButton = this;


          /* =========================================
             DISABLE BUTTON
          ========================================= */

          currentButton.disabled = true;

          currentButton.innerHTML =
            '<i class="fa fa-spinner fa-spin"></i> Adding...';


          /* =========================================
             ADD PRODUCT TO CART
          ========================================= */

          fetch(
              "config/backend_cart.php?action=add&product_id=" +
              productId, {
                method: "GET",

                headers: {
                  "X-Requested-With": "XMLHttpRequest"
                }
              }
            )

            .then(function(response) {

              return response.json();

            })

            .then(function(data) {


              /* =====================================
                 LOGIN REQUIRED
              ===================================== */

              if (data.login_required) {

                window.location.href = "login.php";

                return;

              }


              /* =====================================
                 SUCCESS
              ===================================== */

              if (
                data.success === true ||
                data.status === "success"
              ) {


                /* =================================
                   CART COUNT
                ================================= */

                var cartCount = data.cart_count;


                /* =================================
                   HEADER CART COUNT
                ================================= */

                if (
                  cartCount !== undefined &&
                  cartCount !== null
                ) {

                  cartCount =
                    parseInt(cartCount);

                  document
                    .querySelectorAll(
                      '.cart-count, #cartCount, .cart_badge, .cart-count-badge'
                    )
                    .forEach(function(cartCountElement) {

                      cartCountElement.textContent =
                        cartCount;

                    });


                  /* ===============================
                     SHOW ADDED + COUNT
                  =============================== */

                  currentButton.disabled = false;

                  currentButton.innerHTML =
                    '<i class="fa fa-check"></i> Added (' +
                    cartCount +
                    ')';


                } else {


                  /* ===============================
                     IF COUNT NOT AVAILABLE
                     KEEP NORMAL BUTTON
                  =============================== */

                  currentButton.disabled = false;

                  currentButton.innerHTML =
                    '<i class="fa fa-shopping-cart"></i> Add to Cart';

                }


                /* =================================
                   RESET BUTTON
                ================================= */

                setTimeout(function() {

                  currentButton.innerHTML =
                    '<i class="fa fa-shopping-cart"></i> Add to Cart';

                }, 1500);


                return;

              }


              /* =====================================
                 ERROR
              ===================================== */

              alert(
                data.message ||
                "Unable to add product to cart."
              );


              currentButton.disabled = false;

              currentButton.innerHTML =
                '<i class="fa fa-shopping-cart"></i> Add to Cart';

            })

            .catch(function(error) {

              console.error(error);


              alert(
                "Something went wrong while adding product to cart."
              );


              currentButton.disabled = false;

              currentButton.innerHTML =
                '<i class="fa fa-shopping-cart"></i> Add to Cart';

            });

        });

      });
  </script>


  <!-- =====================================================
     BUY NOW
====================================================== -->

  <!-- =====================================================
   BUY NOW - SINGLE PRODUCT ONLY
====================================================== -->

  <script>
    document
      .querySelectorAll('.buy-now-btn')
      .forEach(function(button) {

        button.addEventListener('click', function() {

          var productId =
            this.getAttribute('data-product-id');

          var currentButton = this;


          /* =========================================
             CHECK PRODUCT ID
          ========================================= */

          if (!productId) {

            alert("Product not found.");
            return;

          }


          /* =========================================
             BUTTON LOADING
          ========================================= */

          currentButton.disabled = true;

          currentButton.innerHTML =
            '<i class="fa fa-spinner fa-spin"></i> Processing...';


          /* =========================================
             LOGIN CHECK + SINGLE PRODUCT CHECKOUT
          ========================================= */

          fetch(
              "config/backend_cart.php?action=check_login&product_id=" +
              productId, {
                method: "GET",

                headers: {
                  "X-Requested-With": "XMLHttpRequest"
                }
              }
            )

            .then(function(response) {

              return response.json();

            })

            .then(function(data) {


              /* =====================================
                 LOGIN REQUIRED
              ===================================== */

              if (data.login_required) {

                window.location.href =
                  "login.php";

                return;

              }


              /* =====================================
                 GO TO CHECKOUT WITH PRODUCT ID
              ===================================== */

              window.location.href =
                "checkout.php?buy_now=1&product_id=" +
                encodeURIComponent(productId);

            })

            .catch(function(error) {

              console.error(error);

              alert(
                "Something went wrong. Please try again."
              );

              currentButton.disabled = false;

              currentButton.innerHTML =
                "Buy Now";

            });

        });

      });
  </script>


  <!-- =====================================================
     WISHLIST
====================================================== -->

  <script>
    document
      .querySelectorAll(
        '.wishlist-btn'
      )
      .forEach(
        function(button) {

          button.addEventListener(
            'click',
            function() {

              var productId =
                this.getAttribute(
                  'data-product-id'
                );


              var currentButton =
                this;


              currentButton.disabled =
                true;


              currentButton.innerHTML =
                '<i class="fa fa-heart"></i>';


              fetch(
                  "config/backend_wishlist.php?action=add&product_id=" +
                  productId, {
                    method: "GET",

                    headers: {
                      "X-Requested-With": "XMLHttpRequest"
                    }

                  }
                )

                .then(
                  function(response) {

                    return response.json();

                  }
                )

                .then(
                  function(data) {


                    /* =====================================
                       LOGIN REQUIRED
                    ===================================== */

                    if (
                      data.login_required
                    ) {

                      window.location.href =
                        "login.php";

                      return;

                    }


                    /* =====================================
                       SUCCESS
                    ===================================== */

                    if (
                      data.success === true ||
                      data.status === "success"
                    ) {

                      currentButton
                        .classList
                        .add(
                          "active"
                        );


                      currentButton.innerHTML =
                        '<i class="fa fa-heart"></i>';


                      currentButton.title =
                        "Added to Wishlist";


                      currentButton.disabled =
                        false;


                      return;

                    }


                    /* =====================================
                       ERROR
                    ===================================== */

                    alert(
                      data.message ||
                      "Unable to add product to wishlist."
                    );


                    currentButton.innerHTML =
                      '<i class="fa fa-heart-o"></i>';


                    currentButton.disabled =
                      false;

                  }
                )

                .catch(
                  function(error) {

                    console.error(error);


                    alert(
                      "Something went wrong while adding product to wishlist."
                    );


                    currentButton.innerHTML =
                      '<i class="fa fa-heart-o"></i>';


                    currentButton.disabled =
                      false;

                  }
                );

            }
          );

        }
      );
  </script>


</body>

</html>