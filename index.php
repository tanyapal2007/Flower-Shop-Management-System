<?php

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

require_once "config/database.php";


/* =========================================================
   FETCH RANDOM 8 ACTIVE PRODUCTS
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
        ORDER BY
            product_images.is_primary DESC,
            product_images.image_id DESC
        LIMIT 1
    ) pi ON TRUE

    WHERE p.status = 1

    ORDER BY RANDOM()

    LIMIT 8
";


try {

  $stmt = $conn->prepare($sql);

  $stmt->execute();

  $gallery_products =
    $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {

  die("Product Fetch Error: " .
    htmlspecialchars($e->getMessage()));
}


/* =========================================================
   CATEGORY / SUBCATEGORY LIST
========================================================= */

$gallery_categories = [];
$gallery_subcategories = [];

foreach ($gallery_products as $product) {

  if (
    !empty($product['category_id']) &&
    !empty($product['category_name'])
  ) {

    $gallery_categories[$product['category_id']] = $product['category_name'];
  }

  if (
    !empty($product['subcategory_id']) &&
    !empty($product['subcategory_name'])
  ) {

    $gallery_subcategories[$product['subcategory_id']] = $product['subcategory_name'];
  }
}

sort($gallery_categories);
sort($gallery_subcategories);

?>

<!DOCTYPE html>

<html>

<head>

  <!-- Basic -->

  <meta charset="utf-8">

  <meta
    http-equiv="X-UA-Compatible"
    content="IE=edge">


  <!-- Mobile -->

  <meta
    name="viewport"
    content="width=device-width, initial-scale=1, shrink-to-fit=no">


  <!-- SEO -->

  <meta
    name="keywords"
    content="flowers, flower shop, bouquet, Fior">

  <meta
    name="description"
    content="Fior Flower Shop - Fresh flowers, bouquets and beautiful floral arrangements.">

  <meta
    name="author"
    content="Fior">


  <title>Fior</title>


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
         GOOGLE FONT
    ====================================================== -->

  <link
    href="https://fonts.googleapis.com/css?family=Baloo+Chettan|Poppins:400,600,700&display=swap"
    rel="stylesheet">


  <!-- =====================================================
         FONT AWESOME
    ====================================================== -->

  <link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">


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


  <!-- =====================================================
         INDEX GALLERY CSS
    ====================================================== -->

  <style>
    /* =====================================================
           GALLERY TOP CONTROLS
        ====================================================== */

    .index_gallery_top_controls {

      width: 100%;

      display: flex;

      align-items: flex-start;

      gap: 15px;

      margin-bottom: 25px;

      position: relative;

      z-index: 1000;

    }


    .index_gallery_types_wrapper {

      width: 220px;

      min-width: 220px;

      position: relative;

      z-index: 1003;

    }


    .index_gallery_search_wrapper {

      flex: 1;

      min-width: 0;

      position: relative;

      z-index: 1001;

    }


    .index_gallery_filter_wrapper {

      width: 220px;

      min-width: 220px;

      position: relative;

      z-index: 1002;

    }


    /* =====================================================
           SEARCH BOX
        ====================================================== */

    .index_gallery_search_box {

      width: 100%;

      min-height: 48px;

      background: #ffffff;

      border: 1px solid #dddddd;

      border-radius: 6px;

      display: flex;

      align-items: center;

      overflow: hidden;

    }


    .index_gallery_search_icon {

      width: 45px;

      min-width: 45px;

      text-align: center;

      color: #df2f68;

      font-size: 16px;

    }


    .index_gallery_search_icon i {

      color: #df2f68;

    }


    .index_gallery_search_input {

      flex: 1;

      height: 46px;

      border: none;

      outline: none;

      padding: 0 8px;

      font-size: 14px;

      color: #333333;

      background: #ffffff;

    }


    .index_gallery_search_input::placeholder {

      color: #999999;

    }


    .index_gallery_search_btn {

      width: 48px;

      height: 46px;

      border: none;

      background: #df2f68;

      color: #ffffff;

      cursor: pointer;

    }


    .index_gallery_search_btn:hover {

      background: #c92359;

    }


    .index_gallery_search_btn i {

      font-size: 14px;

    }


    /* =====================================================
           DROPDOWN BUTTON
        ====================================================== */

    .index_gallery_dropdown {

      position: relative;

    }


    .index_gallery_dropdown_button {

      width: 100%;

      height: 48px;

      background: #ffffff;

      border: 1px solid #dddddd;

      border-radius: 6px;

      padding: 0 14px;

      display: flex;

      align-items: center;

      justify-content: space-between;

      cursor: pointer;

      color: #333333;

      font-size: 14px;

    }


    .index_gallery_dropdown_button:hover {

      border-color: #df2f68;

    }


    .index_gallery_dropdown_button i {

      color: #df2f68;

    }


    .index_gallery_dropdown_menu {

      display: none;

      position: absolute;

      top: 53px;

      left: 0;

      width: 100%;

      background: #ffffff;

      border: 1px solid #dddddd;

      border-radius: 6px;

      box-shadow:
        0 5px 20px rgba(0, 0, 0, 0.12);

      padding: 10px;

      z-index: 99999;

    }


    .index_gallery_dropdown_menu.show {

      display: block;

    }


    .index_gallery_dropdown_menu label {

      display: block;

      padding: 8px 5px;

      margin: 0;

      font-size: 13px;

      cursor: pointer;

      color: #444444;

    }


    .index_gallery_dropdown_menu label:hover {

      color: #df2f68;

    }


    .index_gallery_dropdown_menu input {

      margin-right: 7px;

    }


    /* =====================================================
           FILTER MENU
        ====================================================== */

    .index_filter_menu {

      display: none;

      position: absolute;

      top: 53px;

      right: 0;

      width: 300px;

      background: #ffffff;

      border: 1px solid #dddddd;

      border-radius: 6px;

      box-shadow:
        0 5px 20px rgba(0, 0, 0, 0.12);

      padding: 15px;

      z-index: 99999;

    }


    .index_filter_menu.show {

      display: block;

    }


    .index_filter_group {

      margin-bottom: 14px;

    }


    .index_filter_group:last-child {

      margin-bottom: 0;

    }


    .index_filter_group label {

      display: block;

      font-size: 13px;

      font-weight: 600;

      color: #333333;

      margin-bottom: 6px;

    }


    .index_filter_group select {

      width: 100%;

      height: 40px;

      border: 1px solid #dddddd;

      border-radius: 5px;

      padding: 0 8px;

      outline: none;

      font-size: 13px;

    }


    .index_filter_buttons {

      display: flex;

      gap: 8px;

      margin-top: 15px;

    }


    .index_apply_filter {

      flex: 1;

      background: #df2f68;

      color: #ffffff;

      border: none;

      border-radius: 5px;

      padding: 9px;

      cursor: pointer;

    }


    .index_clear_filter {

      flex: 1;

      background: #17233c;

      color: #ffffff;

      border: none;

      border-radius: 5px;

      padding: 9px;

      cursor: pointer;

    }


    /* =====================================================
           PRODUCT GRID
        ====================================================== */

    .index_gallery_grid {

      display: grid;

      grid-template-columns:
        repeat(4, 1fr);

      gap: 20px;

    }


    /* =====================================================
           PRODUCT CARD
        ====================================================== */

    .index_gallery_item {

      background: #ffffff;

      border: 1px solid #eeeeee;

      border-radius: 8px;

      overflow: hidden;

      padding: 8px;

      transition: 0.3s;

    }


    .index_gallery_item:hover {

      box-shadow:
        0 5px 20px rgba(0, 0, 0, 0.10);

      transform: translateY(-3px);

    }


    /* =====================================================
           PRODUCT IMAGE
        ====================================================== */

    .index_gallery_item img {

      width: 100%;

      height: 220px;

      object-fit: cover;

      border-radius: 5px;

      display: block;

    }


    /* =====================================================
           PRODUCT INFO
        ====================================================== */

    .index_product_info {

      padding: 10px 4px 4px;

    }


    .index_product_category {

      font-size: 13px;

      color: #888888;

      margin-bottom: 5px;

    }


    .index_product_name {

      margin: 0 0 6px;

      font-size: 17px;

      color: #17233c;

      font-weight: 600;

      min-height: 25px;

    }


    .index_product_name a {

      color: #17233c;

      text-decoration: none;

      transition: 0.3s;

    }


    .index_product_name a:hover {

      color: #df2f68;

    }


    /* =====================================================
           PRICE
        ====================================================== */

    .index_product_price {

      margin-bottom: 10px;

    }


    .index_selling_price {

      color: #e52d68;

      font-size: 18px;

      font-weight: 700;

    }


    .index_original_price {

      color: #999999;

      text-decoration: line-through;

      font-size: 13px;

      margin-left: 8px;

    }


    .index_discount {

      background: #e52d68;

      color: #ffffff;

      padding: 3px 7px;

      border-radius: 15px;

      font-size: 11px;

      margin-left: 5px;

    }


    /* =====================================================
           BUTTONS
        ====================================================== */

    .index_product_buttons {

      display: flex;

      gap: 6px;

      align-items: center;

      margin-top: 8px;

    }


    .index_add-cart-btn {

      flex: 1;

      border: none;

      background: #df2f68;

      color: #ffffff;

      padding: 10px 8px;

      border-radius: 6px;

      font-size: 13px;

      cursor: pointer;

      transition: 0.3s;

    }


    .index_add-cart-btn:hover {

      background: #c92359;

    }


    .index_add-cart-btn:disabled {

      background: #999999;

      cursor: not-allowed;

    }


    .index_buy-now-btn {

      flex: 1;

      border: none;

      background: #17233c;

      color: #ffffff;

      padding: 10px 8px;

      border-radius: 6px;

      font-size: 13px;

      cursor: pointer;

      transition: 0.3s;

    }


    .index_buy-now-btn:hover {

      background: #0d1628;

    }


    .index_buy-now-btn:disabled {

      background: #999999;

      cursor: not-allowed;

    }


    /* =====================================================
           WISHLIST
        ====================================================== */

    .index_wishlist-btn {

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

      transition: 0.3s;

    }


    .index_wishlist-btn:hover {

      border-color: #df2f68;

      color: #df2f68;

    }


    .index_wishlist-btn.active {

      background: #df2f68;

      border-color: #df2f68;

      color: #ffffff;

    }


    /* =====================================================
           NO PRODUCTS / NO SEARCH
        ====================================================== */

    .index_no_products {

      width: 100%;

      text-align: center;

      padding: 50px 0;

      color: #777777;

      grid-column: 1 / -1;

    }


    .index_no_search_result {

      display: none;

      text-align: center;

      padding: 40px 0;

      color: #777777;

      grid-column: 1 / -1;

    }


    /* =====================================================
           VIEW ALL BUTTON
        ====================================================== */

    .index_view_all_wrapper {

      text-align: center;

      margin-top: 30px;

    }


    .index_view_all_btn {

      display: inline-block;

      background: #df2f68;

      color: #ffffff;

      padding: 10px 25px;

      border-radius: 6px;

      text-decoration: none;

      transition: 0.3s;

    }


    .index_view_all_btn:hover {

      background: #c92359;

      color: #ffffff;

      text-decoration: none;

    }


    /* =====================================================
           RESPONSIVE
        ====================================================== */

    @media (max-width: 992px) {

      .index_gallery_grid {

        grid-template-columns:
          repeat(3, 1fr);

      }

    }


    @media (max-width: 768px) {

      .index_gallery_top_controls {

        flex-direction: column;

      }


      .index_gallery_types_wrapper,

      .index_gallery_filter_wrapper,

      .index_gallery_search_wrapper {

        width: 100%;

        min-width: 100%;

      }


      .index_filter_menu {

        width: 100%;

        left: 0;

        right: auto;

      }


      .index_gallery_grid {

        grid-template-columns:
          repeat(2, 1fr);

      }

    }


    @media (max-width: 576px) {

      .index_gallery_grid {

        grid-template-columns: 1fr;

      }


      .index_gallery_item img {

        height: 240px;

      }

    }
  </style>

</head>


<body>


  <!-- =====================================================
         HERO AREA
    ====================================================== -->

  <div class="hero_area">

    <?php include 'header.php'; ?>


    <!-- =================================================
             SLIDER
        ================================================== -->

    <section class="slider_section position-relative">

      <div class="slider_number-container">

        <div class="number-box">

          <span>01</span>

          <hr>

          <span>02</span>

        </div>

      </div>


      <div class="container">

        <div class="row">

          <div
            id="carouselExampleIndicators"
            class="carousel slide"
            data-ride="carousel">


            <div class="carousel-inner">


              <!-- SLIDE 1 -->

              <div class="carousel-item active">

                <div class="col-lg-6 col-md-8">

                  <div class="detail_box">

                    <h2>
                      Welcome
                    </h2>

                    <h1>
                      Flowers shop
                    </h1>

                    <p>
                      Bring beauty and happiness to every moment with Fior Flower Shop. Discover fresh flowers, elegant bouquets, and beautiful floral arrangements made with love for every special occasion.
                    </p>

                    <div>

                      <a href="gallery.php">
                        Buy Now
                      </a>

                    </div>

                  </div>

                </div>

              </div>


              <!-- SLIDE 2 -->

              <div class="carousel-item">

                <div class="col-lg-6 col-md-8">

                  <div class="detail_box">

                    <h2>
                      Welcome
                    </h2>

                    <h1>
                      Flowers shop
                    </h1>

                    <p>
                      Welcome to Fior Flower Shop, where fresh flowers bring beauty and happiness to every special moment. Discover beautiful bouquets and elegant floral arrangements, thoughtfully designed for birthdays, weddings, anniversaries, and every memorable occasion.
                    </p>

                    <div>

                      <a href="gallery.php">
                        Buy Now
                      </a>

                    </div>

                  </div>

                </div>

              </div>


              <!-- SLIDE 3 -->

              <div class="carousel-item">

                <div class="col-lg-6 col-md-8">

                  <div class="detail_box">

                    <h2>
                      Welcome
                    </h2>

                    <h1>
                      Flowers shop
                    </h1>

                    <p>
                      Discover the beauty of fresh flowers at Fior Flower Shop. We create beautiful bouquets and floral arrangements to make your special moments more joyful, colorful, and memorable.
                    </p>

                    <div>

                      <a href="gallery.php">
                        Buy Now
                      </a>

                    </div>

                  </div>

                </div>

              </div>


            </div>


            <div class="carousel_btn-container">

              <a
                class="carousel-control-prev"
                href="#carouselExampleIndicators"
                role="button"
                data-slide="prev">

                <span class="sr-only">
                  Previous
                </span>

              </a>


              <a
                class="carousel-control-next"
                href="#carouselExampleIndicators"
                role="button"
                data-slide="next">

                <span class="sr-only">
                  Next
                </span>

              </a>

            </div>


          </div>

        </div>

      </div>

    </section>

  </div>


  <!-- =====================================================
         ABOUT
    ====================================================== -->

  <section class="about_section">

    <div class="section_number">
      01
    </div>

    <div class="container">

      <div class="row">

        <div class="col-md-6 col-xl-7">

          <div class="img-box">

            <img
              src="images/about-img.png"
              alt="About Flowers">

          </div>

        </div>


        <div class="col-md-5 col-xl-5">

          <div class="detail_box">

            <div
              class="heading_container justify-content-end">

              <h2>
                About Flowers
              </h2>

            </div>

            <p>
              Welcome to Fior Flower Shop, where we turn beautiful flowers into memorable moments. We provide fresh and colorful flowers, elegant bouquets, and creative floral arrangements for birthdays, weddings, anniversaries, and other special occasions. Our flowers are carefully selected and beautifully arranged to bring joy, love, and freshness to every celebration. At Fior, we believe that every flower has a story to tell. 💐
            </p>

          </div>

        </div>

      </div>

    </div>

  </section>


  <!-- =====================================================
         WHY SECTION
    ====================================================== -->

  <section class="why_section layout_padding">

    <div class="section_number">
      02
    </div>

    <div class="container">

      <div class="row">

        <div class="col-12">

          <h2>
            Why Choose Us
          </h2>

          <p>
            At Fior Flower Shop, we believe flowers are more than just beautiful gifts—they are a way to express love, happiness, and emotions. We offer fresh flowers, elegant bouquets, and creative floral arrangements for birthdays, weddings, anniversaries, and every special occasion. Our goal is to make every celebration brighter and more memorable with the beauty of flowers. 💐
          </p>

          <div>

            <a href="gallery.php">
              Read More
            </a>

          </div>

        </div>

      </div>

    </div>

  </section>


  <!-- =====================================================
         GALLERY SECTION
    ====================================================== -->

  <section class="gallery_section layout_padding">

    <div class="section_number">
      03
    </div>

    <div class="heading_container justify-content-center">

      <h2>
        Our Gallery
      </h2>

    </div>

    <div class="container">

      <div
        class="index_gallery_grid"
        id="indexGalleryGrid">

        <?php if (!empty($gallery_products)) { ?>

          <?php foreach ($gallery_products as $product) { ?>

            <?php

            $productName =
              $product['product_name']
              ?? 'Flower';

            $categoryName =
              $product['category_name']
              ?? 'Flowers';

            $subcategoryName =
              $product['subcategory_name']
              ?? '';

            /* IMAGE */

            $uploadedImage =
              trim(
                $product['uploaded_image'] ?? ''
              );

            if ($uploadedImage !== '') {

              $imagePath =
                "uploads/products/" .
                basename($uploadedImage);
            } elseif (!empty($product['product_image'])) {

              $oldImage =
                trim($product['product_image']);

              if (
                strpos(
                  $oldImage,
                  'assets/images/'
                ) === 0
              ) {

                $imagePath = $oldImage;
              } else {

                $imagePath =
                  "assets/images/" .
                  basename($oldImage);
              }
            } else {

              $imagePath =
                "images/no-image.jpg";
            }


            /* PRICE */

            $sellingPrice =
              $product['selling_price']
              ?? null;

            $originalPrice =
              $product['original_price']
              ?? null;

            $discount =
              $product['discount_percentage']
              ?? null;


            /* STOCK */

            $stock =
              (int)(
                $product['stock_quantity'] ?? 0
              );

            $outOfStock =
              ($stock <= 0);

            ?>


            <div class="index_gallery_item">

              <!-- IMAGE -->

              <img
                src="<?php echo htmlspecialchars($imagePath); ?>"
                alt="<?php echo htmlspecialchars($productName); ?>">


              <div class="index_product_info">


                <!-- CATEGORY -->

                <div class="index_product_category">

                  <?php
                  echo htmlspecialchars(
                    $subcategoryName !== ''
                      ? $subcategoryName
                      : $categoryName
                  );
                  ?>

                </div>


                <!-- NAME -->

                <h5 class="index_product_name">

                  <a
                    href="product-details.php?product_id=<?php echo (int)$product['product_id']; ?>">

                    <?php
                    echo htmlspecialchars(
                      $productName
                    );
                    ?>

                  </a>

                </h5>


                <!-- PRICE -->

                <div class="index_product_price">

                  <?php if ($sellingPrice !== null) { ?>

                    <span class="index_selling_price">

                      ₹<?php
                        echo number_format(
                          (float)$sellingPrice,
                          2
                        );
                        ?>

                    </span>

                  <?php } else { ?>

                    <span class="index_selling_price">

                      Price Not Available

                    </span>

                  <?php } ?>


                  <?php if (
                    $originalPrice !== null &&
                    $sellingPrice !== null &&
                    (float)$originalPrice >
                    (float)$sellingPrice
                  ) { ?>

                    <span class="index_original_price">

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

                    <span class="index_discount">

                      <?php
                      echo number_format(
                        (float)$discount,
                        0
                      );
                      ?>% OFF

                    </span>

                  <?php } ?>

                </div>


                <!-- BUTTONS -->

                <div class="index_product_buttons">


                  <?php if ($outOfStock) { ?>

                    <button
                      type="button"
                      class="index_add-cart-btn"
                      disabled>

                      Out of Stock

                    </button>


                    <button
                      type="button"
                      class="index_buy-now-btn"
                      disabled>

                      Buy Now

                    </button>


                    <button
                      type="button"
                      class="index_wishlist-btn"
                      disabled
                      title="Out of Stock">

                      <i class="fa fa-heart-o"></i>

                    </button>


                  <?php } else { ?>


                    <!-- ADD TO CART -->

                    <button
                      type="button"
                      class="index_add-cart-btn"
                      data-product-id="<?php echo (int)$product['product_id']; ?>">

                      <i class="fa fa-shopping-cart"></i>
                      Add to Cart

                    </button>


                    <!-- BUY NOW -->

                    <button
                      type="button"
                      class="index_buy-now-btn"
                      data-product-id="<?php echo (int)$product['product_id']; ?>">

                      Buy Now

                    </button>


                    <!-- WISHLIST -->

                    <button
                      type="button"
                      class="index_wishlist-btn"
                      data-product-id="<?php echo (int)$product['product_id']; ?>"
                      title="Add to Wishlist">

                      <i class="fa fa-heart-o"></i>

                    </button>


                  <?php } ?>


                </div>

              </div>

            </div>


          <?php } ?>

        <?php } else { ?>

          <div class="index_no_products">

            <h4>
              No flowers available.
            </h4>

            <p>
              Please add products from the admin panel.
            </p>

          </div>

        <?php } ?>


      </div>


      <!-- VIEW ALL -->

      <div class="index_view_all_wrapper">

        <a
          href="gallery.php"
          class="index_view_all_btn">

          View All Flowers

        </a>

      </div>

    </div>

  </section>
  <!-- =====================================================
         CLIENT SECTION
    ====================================================== -->

  <section class="client_section layout_padding">

    <div class="container">

      <div
        class="heading_container justify-content-center">

        <h2>
          What Our Customers Say
        </h2>

        <div class="section_number">
          04
        </div>

      </div>

    </div>


    <div class="container">

      <div class="row">


        <div class="col-md-6">

          <div class="client_box">

            <div class="detail_box">

              <div class="img_box">

                <img
                  src="images/client-1.png">

              </div>

              <h5>
                nomil du
              </h5>

              <p>
                Contrary to popular belief, Lorem Ipsum is not simply random text. It has roots in a piece of classical Latin literature from 45 BC, making it over
              </p>

            </div>

          </div>

        </div>


        <div class="col-md-6">

          <div class="client_box">

            <div class="detail_box">

              <div class="img_box">

                <img
                  src="images/client-2.png">

              </div>

              <h5>
                zabih jo
              </h5>

              <p>
                Contrary to popular belief, Lorem Ipsum is not simply random text. It has roots in a piece of classical Latin literature from 45 BC, making it over
              </p>

            </div>

          </div>

        </div>


      </div>

    </div>

  </section>


  <!-- =====================================================
         ARRANGE SECTION
    ====================================================== -->

  <section class="arrange_section">

    <div class="container">

      <div class="detail_box">

        <h2>
          Our Wonderful Arrangements
        </h2>

        <p>
          At Fior Flower Shop, we bring fresh and beautiful flowers to make every occasion special. From colorful bouquets to elegant floral arrangements, our flowers are carefully selected and designed with love. Celebrate birthdays, weddings, anniversaries, and special moments with Fior. 💐
        </p>

      </div>

    </div>

  </section>


  <!-- =====================================================
         CONTACT SECTION
    ====================================================== -->

  <section class="contact_section layout_padding">

    <div class="section_number">
      05
    </div>

    <div class="container">

      <div
        class="heading_container justify-content-center">

        <h2>
          Contact Us
        </h2>

      </div>

    </div>


    <div class="container">

      <div class="row">

        <div class="col-md-6 mx-auto">

          <form action="contact.php" method="POST">

            <div>

              <input
                type="text"
                name="name"
                placeholder="Name"
                required>

            </div>


            <div>

              <input
                type="email"
                name="email"
                placeholder="Email"
                required>

            </div>


            <div>

              <input
                type="text"
                name="phone"
                placeholder="Phone Number">

            </div>


            <div>

              <input
                type="text"
                name="message"
                class="message-box"
                placeholder="Message"
                required>

            </div>


            <div class="d-flex mt-4">

              <button
                type="submit">

                SEND

              </button>

            </div>

          </form>

        </div>

      </div>

    </div>

  </section>


  <!-- =====================================================
         MAP
    ====================================================== -->

  <div class="map_section">

    <div class="map_container">

      <div class="map">

        <div id="googleMap"></div>

      </div>

    </div>

  </div>


  <!-- =====================================================
         FOOTER
    ====================================================== -->

  <?php include 'footer.php'; ?>


  <!-- =====================================================
         JAVASCRIPT
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
         GALLERY SEARCH + FILTER
    ====================================================== -->

  <script>
    document.addEventListener(
      "DOMContentLoaded",
      function() {


        /* =================================================
           ELEMENTS
        ================================================= */

        var searchInput =
          document.getElementById(
            "indexGallerySearchInput"
          );


        var searchButton =
          document.getElementById(
            "indexGallerySearchButton"
          );


        var typeButton =
          document.getElementById(
            "indexTypesButton"
          );


        var typeMenu =
          document.getElementById(
            "indexTypesMenu"
          );


        var filterButton =
          document.getElementById(
            "indexFilterButton"
          );


        var filterMenu =
          document.getElementById(
            "indexFilterMenu"
          );


        var categoryFilter =
          document.getElementById(
            "indexCategoryFilter"
          );


        var subcategoryFilter =
          document.getElementById(
            "indexSubcategoryFilter"
          );


        var priceFilter =
          document.getElementById(
            "indexPriceFilter"
          );


        var applyFilter =
          document.getElementById(
            "indexApplyFilter"
          );


        var clearFilter =
          document.getElementById(
            "indexClearFilter"
          );


        var noResult =
          document.getElementById(
            "indexNoSearchResult"
          );


        var productCards =
          document.querySelectorAll(
            ".index_gallery_item"
          );


        var selectedType =
          "all";


        var selectedCategory =
          "";


        var selectedSubcategory =
          "";


        var selectedPrice =
          "";


        /* =================================================
           TYPES DROPDOWN
        ================================================== */

        if (typeButton) {

          typeButton.addEventListener(
            "click",
            function(event) {

              event.stopPropagation();

              typeMenu.classList.toggle(
                "show"
              );

              filterMenu.classList.remove(
                "show"
              );

            }
          );

        }


        /* =================================================
           FILTER DROPDOWN
        ================================================== */

        if (filterButton) {

          filterButton.addEventListener(
            "click",
            function(event) {

              event.stopPropagation();

              filterMenu.classList.toggle(
                "show"
              );

              typeMenu.classList.remove(
                "show"
              );

            }
          );

        }


        /* =================================================
           RADIO TYPE
        ================================================== */

        document
          .querySelectorAll(
            'input[name="indexProductType"]'
          )
          .forEach(
            function(radio) {

              radio.addEventListener(
                "change",
                function() {

                  selectedType =
                    this.value;

                  typeMenu.classList.remove(
                    "show"
                  );

                  applyGalleryFilters();

                }
              );

            }
          );


        /* =================================================
           APPLY FILTER
        ================================================== */

        if (applyFilter) {

          applyFilter.addEventListener(
            "click",
            function() {

              selectedCategory =
                categoryFilter.value
                .toLowerCase();


              selectedSubcategory =
                subcategoryFilter.value
                .toLowerCase();


              selectedPrice =
                priceFilter.value;


              filterMenu.classList.remove(
                "show"
              );


              applyGalleryFilters();

            }
          );

        }


        /* =================================================
           CLEAR FILTER
        ================================================== */

        if (clearFilter) {

          clearFilter.addEventListener(
            "click",
            function() {


              categoryFilter.value =
                "";


              subcategoryFilter.value =
                "";


              priceFilter.value =
                "";


              selectedCategory =
                "";


              selectedSubcategory =
                "";


              selectedPrice =
                "";


              selectedType =
                "all";


              var allRadio =
                document.querySelector(
                  'input[name="indexProductType"][value="all"]'
                );


              if (allRadio) {

                allRadio.checked =
                  true;

              }


              filterMenu.classList.remove(
                "show"
              );


              applyGalleryFilters();

            }
          );

        }


        /* =================================================
           SEARCH
        ================================================== */

        if (searchInput) {

          searchInput.addEventListener(
            "input",
            function() {

              applyGalleryFilters();

            }
          );

        }


        if (searchButton) {

          searchButton.addEventListener(
            "click",
            function() {

              applyGalleryFilters();

            }
          );

        }


        /* =================================================
           PRICE CHECK
        ================================================== */

        function priceMatches(
          price
        ) {

          price =
            parseFloat(price || 0);


          if (
            selectedPrice === ""
          ) {

            return true;

          }


          if (
            selectedPrice === "0-500"
          ) {

            return price < 500;

          }


          if (
            selectedPrice === "500-1000"
          ) {

            return price >= 500 &&
              price <= 1000;

          }


          if (
            selectedPrice === "1000-2000"
          ) {

            return price > 1000 &&
              price <= 2000;

          }


          if (
            selectedPrice === "2000+"
          ) {

            return price > 2000;

          }


          return true;

        }


        /* =================================================
           MAIN FILTER FUNCTION
        ================================================== */

        function applyGalleryFilters() {


          var searchText =
            (
              searchInput.value ||
              ""
            )
            .toLowerCase()
            .trim();


          var visibleCount =
            0;


          productCards.forEach(
            function(card) {


              var name =
                (
                  card.getAttribute(
                    "data-product-name"
                  ) ||
                  ""
                )
                .toLowerCase();


              var category =
                (
                  card.getAttribute(
                    "data-category"
                  ) ||
                  ""
                )
                .toLowerCase();


              var subcategory =
                (
                  card.getAttribute(
                    "data-subcategory"
                  ) ||
                  ""
                )
                .toLowerCase();


              var type =
                (
                  card.getAttribute(
                    "data-type"
                  ) ||
                  ""
                )
                .toLowerCase();


              var price =
                card.getAttribute(
                  "data-price"
                );


              var searchMatch =
                searchText === "" ||
                name.indexOf(
                  searchText
                ) !== -1 ||
                category.indexOf(
                  searchText
                ) !== -1 ||
                subcategory.indexOf(
                  searchText
                ) !== -1;


              var typeMatch =
                selectedType === "all" ||
                type === selectedType;


              var categoryMatch =
                selectedCategory === "" ||
                category === selectedCategory;


              var subcategoryMatch =
                selectedSubcategory === "" ||
                subcategory === selectedSubcategory;


              var priceMatch =
                priceMatches(
                  price
                );


              if (
                searchMatch &&
                typeMatch &&
                categoryMatch &&
                subcategoryMatch &&
                priceMatch
              ) {

                card.style.display =
                  "";


                visibleCount++;

              } else {

                card.style.display =
                  "none";

              }

            }
          );


          if (noResult) {

            if (
              visibleCount === 0 &&
              productCards.length > 0
            ) {

              noResult.style.display =
                "block";

            } else {

              noResult.style.display =
                "none";

            }

          }

        }


        /* =================================================
           CLOSE DROPDOWNS OUTSIDE
        ================================================== */

        document.addEventListener(
          "click",
          function(event) {

            if (
              !event.target.closest(
                ".index_gallery_dropdown"
              )
            ) {

              typeMenu.classList.remove(
                "show"
              );

              filterMenu.classList.remove(
                "show"
              );

            }

          }
        );


      }
    );
  </script>


  <!-- =====================================================
         ADD TO CART
         NO REDIRECT
         SHOW REAL CART COUNT ONLY IF BACKEND RETURNS IT
    ====================================================== -->

  <script>
    document
      .querySelectorAll(
        ".index_add-cart-btn"
      )
      .forEach(
        function(button) {


          button.addEventListener(
            "click",
            function() {


              var productId =
                this.getAttribute(
                  "data-product-id"
                );


              var currentButton =
                this;


              currentButton.disabled =
                true;


              currentButton.innerHTML =
                '<i class="fa fa-spinner fa-spin"></i> Adding...';


              fetch(
                  "config/backend_cart.php?action=add&product_id=" +
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


                    /* =========================
                       LOGIN REQUIRED
                    ========================= */

                    if (
                      data.login_required
                    ) {

                      window.location.href =
                        "login.php";

                      return;

                    }


                    /* =========================
                       SUCCESS
                    ========================= */

                    if (
                      data.success === true ||
                      data.status === "success"
                    ) {


                      var cartCount =
                        data.cart_count;


                      /*
                       * UPDATE HEADER COUNT
                       * ONLY IF BACKEND RETURNS IT
                       */

                      if (
                        cartCount !== undefined &&
                        cartCount !== null
                      ) {


                        cartCount =
                          parseInt(
                            cartCount
                          );


                        document
                          .querySelectorAll(
                            ".cart-count, #cartCount, .cart_badge, .cart-count-badge"
                          )
                          .forEach(
                            function(
                              countElement
                            ) {

                              countElement.textContent =
                                cartCount;

                            }
                          );


                        currentButton.disabled =
                          false;


                        currentButton.innerHTML =
                          '<i class="fa fa-check"></i> Added (' +
                          cartCount +
                          ')';


                      } else {


                        /*
                         * NO CART COUNT
                         * KEEP NORMAL BUTTON
                         */

                        currentButton.disabled =
                          false;


                        currentButton.innerHTML =
                          '<i class="fa fa-shopping-cart"></i> Add to Cart';

                      }


                      setTimeout(
                        function() {

                          currentButton.innerHTML =
                            '<i class="fa fa-shopping-cart"></i> Add to Cart';

                        },
                        1500
                      );


                      return;

                    }


                    /* =========================
                       ERROR
                    ========================= */

                    alert(
                      data.message ||
                      "Unable to add product to cart."
                    );


                    currentButton.disabled =
                      false;


                    currentButton.innerHTML =
                      '<i class="fa fa-shopping-cart"></i> Add to Cart';


                  }
                )

                .catch(
                  function(error) {


                    console.error(
                      error
                    );


                    alert(
                      "Something went wrong while adding product to cart."
                    );


                    currentButton.disabled =
                      false;


                    currentButton.innerHTML =
                      '<i class="fa fa-shopping-cart"></i> Add to Cart';

                  }
                );


            }
          );

        }
      );
  </script>


  <!-- =====================================================
         BUY NOW
         SUCCESS -> CHECKOUT
    ====================================================== -->

  <script>
    document
      .querySelectorAll(
        ".index_buy-now-btn"
      )
      .forEach(
        function(button) {


          button.addEventListener(
            "click",
            function() {


              var productId =
                this.getAttribute(
                  "data-product-id"
                );


              var currentButton =
                this;


              currentButton.disabled =
                true;


              currentButton.innerHTML =
                '<i class="fa fa-spinner fa-spin"></i> Processing...';


              fetch(
                  "config/backend_cart.php?action=add&product_id=" +
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


                    /* LOGIN */

                    if (
                      data.login_required
                    ) {

                      window.location.href =
                        "login.php";

                      return;

                    }


                    /* SUCCESS */

                    if (
                      data.success === true ||
                      data.status === "success"
                    ) {

                      window.location.href =
                        "checkout.php";

                      return;

                    }


                    /* ERROR */

                    alert(
                      data.message ||
                      "Unable to process Buy Now."
                    );


                    currentButton.disabled =
                      false;


                    currentButton.innerHTML =
                      "Buy Now";

                  }
                )

                .catch(
                  function(error) {


                    console.error(
                      error
                    );


                    alert(
                      "Something went wrong while processing Buy Now."
                    );


                    currentButton.disabled =
                      false;


                    currentButton.innerHTML =
                      "Buy Now";

                  }
                );


            }
          );

        }
      );
  </script>


  <!-- =====================================================
         WISHLIST
    ====================================================== -->

  <script>
    document
      .querySelectorAll(
        ".index_wishlist-btn"
      )
      .forEach(
        function(button) {


          button.addEventListener(
            "click",
            function() {


              var productId =
                this.getAttribute(
                  "data-product-id"
                );


              var currentButton =
                this;


              currentButton.disabled =
                true;


              currentButton.innerHTML =
                '<i class="fa fa-spinner fa-spin"></i>';


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


                    /* LOGIN */

                    if (
                      data.login_required
                    ) {

                      window.location.href =
                        "login.php";

                      return;

                    }


                    /* SUCCESS */

                    if (
                      data.success === true ||
                      data.status === "success"
                    ) {


                      currentButton.classList.add(
                        "active"
                      );


                      currentButton.innerHTML =
                        '<i class="fa fa-heart"></i>';


                      currentButton.title =
                        "Added to Wishlist";


                      currentButton.disabled =
                        false;


                      window.location.href =
                        "wishlist.php";

                      return;

                    }


                    /* ERROR */

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


                    console.error(
                      error
                    );


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


  <!-- =====================================================
         GOOGLE MAP
    ====================================================== -->

  <script
    src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCh39n5U-4IoWpsVGUHWdqB6puEkhRLdmI&callback=myMap">
  </script>


</body>

</html>