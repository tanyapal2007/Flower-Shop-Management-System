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

        ps.subcategory_name,
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
         GOOGLE FONTS
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
           MAIN GALLERY
        ===================================================== */

    .gallery_content {

      display: flex;

      align-items: flex-start;

      gap: 25px;

    }


    /* =====================================================
           LEFT SIDEBAR
        ===================================================== */

    .gallery_sidebar {

      width: 190px;

      min-width: 190px;

      background: #ffffff;

      border: 1px solid #eeeeee;

      border-radius: 8px;

      padding: 15px;

      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);

    }


    .gallery_sidebar h4 {

      font-size: 18px;

      color: #17233c;

      margin: 0 0 15px;

      font-weight: 600;

      border-bottom: 1px solid #eeeeee;

      padding-bottom: 10px;

    }


    /* =====================================================
           FILTER BUTTONS
        ===================================================== */

    .type_filter {

      display: block;

      width: 100%;

      text-align: left;

      background: transparent;

      border: none;

      padding: 9px 10px;

      margin-bottom: 5px;

      border-radius: 5px;

      color: #555;

      font-size: 14px;

      cursor: pointer;

      transition: 0.3s;

    }


    .type_filter:hover {

      background: #fce4ed;

      color: #df2f68;

    }


    .type_filter.active {

      background: #df2f68;

      color: #ffffff;

    }


    /* =====================================================
           PRODUCTS AREA
        ===================================================== */

    .gallery_products {

      flex: 1;

      min-width: 0;

    }


    .gallery_grid {

      display: grid;

      grid-template-columns: repeat(3, 1fr);

      gap: 20px;

    }


    /* =====================================================
           PRODUCT CARD
        ===================================================== */

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


    /* =====================================================
           IMAGE
        ===================================================== */

    .gallery_item img {

      width: 100%;

      height: 220px;

      object-fit: cover;

      border-radius: 5px;

      display: block;

    }


    /* =====================================================
           PRODUCT INFO
        ===================================================== */

    .product_info {

      padding: 10px 4px 4px;

    }


    .product_info h5 {

      margin: 0 0 6px;

      font-size: 17px;

      color: #17233c;

      font-weight: 600;

    }


    .product_category {

      font-size: 13px;

      color: #888;

      margin-bottom: 5px;

    }


    /* =====================================================
           PRODUCT NAME
        ===================================================== */

    .product_name_link {

      color: #17233c;

      text-decoration: none;

      transition: 0.3s;

    }


    .product_name_link:hover {

      color: #df2f68;

      text-decoration: none;

      cursor: pointer;

    }


    /* =====================================================
           PRICE
        ===================================================== */

    .product_price {

      margin-bottom: 10px;

    }


    .selling_price {

      color: #e52d68;

      font-size: 18px;

      font-weight: 700;

    }


    .original_price {

      color: #999;

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
           ADD TO CART
        ===================================================== */

    .add-cart-btn {

      width: 100%;

      border: none;

      background: #df2f68;

      color: #ffffff;

      padding: 10px 15px;

      border-radius: 6px;

      font-size: 14px;

      cursor: pointer;

      transition: 0.3s;

    }


    .add-cart-btn:hover {

      background: #c92359;

    }


    .add-cart-btn:disabled {

      background: #999;

      cursor: not-allowed;

    }


    /* =====================================================
           HIDDEN PRODUCTS
        ===================================================== */

    .gallery_item.hidden {

      display: none !important;

    }


    /* =====================================================
           NO PRODUCTS
        ===================================================== */

    .no-products {

      text-align: center;

      width: 100%;

      padding: 50px 0;

      color: #777;

    }


    /* =====================================================
           RESPONSIVE
        ===================================================== */

    @media (max-width: 992px) {

      .gallery_content {

        flex-direction: column;

      }


      .gallery_sidebar {

        width: 100%;

        min-width: 100%;

      }


      .gallery_sidebar h4 {

        text-align: center;

      }


      .type_filter {

        display: inline-block;

        width: auto;

        margin-right: 5px;

      }


      .gallery_grid {

        grid-template-columns: repeat(3, 1fr);

      }

    }


    @media (max-width: 768px) {

      .gallery_grid {

        grid-template-columns: repeat(2, 1fr);

      }

    }


    @media (max-width: 576px) {

      .gallery_grid {

        grid-template-columns: 1fr;

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
         GALLERY SECTION
    ====================================================== -->

  <section class="gallery_section layout_padding">


    <div class="heading_container justify-content-center">

      <h2>Our Flower Gallery</h2>

    </div>


    <div class="container">


      <div class="gallery_content">


        <!-- =================================================
                     LEFT FILTER SIDEBAR
                ================================================== -->

        <div class="gallery_sidebar">

          <h4>Flower Types</h4>


          <button
            type="button"
            class="type_filter active"
            data-filter="all">

            🌸 All Flowers

          </button>


          <button
            type="button"
            class="type_filter"
            data-filter="bouquet">

            💐 Bouquet

          </button>


          <button
            type="button"
            class="type_filter"
            data-filter="basket">

            🧺 Basket Bouquet

          </button>


          <button
            type="button"
            class="type_filter"
            data-filter="single">

            🌷 Single Flowers

          </button>

        </div>


        <!-- =================================================
                     PRODUCTS
                ================================================== -->

        <div class="gallery_products">


          <div class="gallery_grid">


            <?php if (!empty($products)) { ?>


              <?php foreach ($products as $product) { ?>


                <?php

                /* =================================================
                                   PRODUCT IMAGE
                                ================================================= */

                $uploadedImage =
                  trim(
                    $product['uploaded_image'] ?? ''
                  );


                if ($uploadedImage !== '') {

                  $imagePath =
                    "uploads/products/" .
                    basename($uploadedImage);
                }


                /* OLD IMAGE */ elseif (
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
                      basename($oldImage);
                  }
                }


                /* NO IMAGE */ else {

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


                /*
                                 * Combine all information.
                                 *
                                 * This makes the filter work even if
                                 * Basket/Bouquet is written in
                                 * category, subcategory, product name
                                 * or description.
                                 */

                $searchText =
                  $productName . ' ' .
                  $subcategory . ' ' .
                  $category . ' ' .
                  $description;


                /* =================================================
                                   BASKET CHECK
                                   IMPORTANT:
                                   Basket is checked FIRST.
                                ================================================= */

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


                /* =================================================
                                   BOUQUET CHECK
                                ================================================= */

                $isBouquet =
                  strpos(
                    $searchText,
                    'bouquet'
                  ) !== false;


                /* =================================================
                                   FINAL TYPE
                                ================================================= */

                if ($isBasket) {

                  $productType =
                    'basket';
                } elseif ($isBouquet) {

                  $productType =
                    'bouquet';
                } else {

                  $productType =
                    'single';
                }

                ?>


                <!-- =================================================
                                     PRODUCT CARD
                                ================================================== -->

                <div
                  class="gallery_item"
                  data-type="<?php echo htmlspecialchars($productType); ?>">


                  <!-- IMAGE -->

                  <img
                    src="<?php echo htmlspecialchars($imagePath); ?>"
                    alt="<?php echo htmlspecialchars($product['product_name']); ?>">


                  <div class="product_info">


                    <!-- SUBCATEGORY -->

                    <div class="product_category">

                      <?php

                      echo htmlspecialchars(
                        $product['subcategory_name']
                          ?? 'Flowers'
                      );

                      ?>

                    </div>


                    <!-- PRODUCT NAME -->

                    <h5>

                      <a
                        href="product-details.php?product_id=<?php echo (int)$product['product_id']; ?>"
                        class="product_name_link">

                        <?php

                        echo htmlspecialchars(
                          $product['product_name']
                        );

                        ?>

                      </a>

                    </h5>


                    <!-- PRICE -->

                    <div class="product_price">


                      <?php if (
                        $sellingPrice !== null
                      ) { ?>


                        <span class="selling_price">

                          ₹<?php

                            echo number_format(
                              (float)$sellingPrice,
                              2
                            );

                            ?>

                        </span>


                      <?php } else { ?>


                        <span class="selling_price">

                          Price Not Available

                        </span>


                      <?php } ?>


                      <?php if (
                        $originalPrice !== null &&
                        $sellingPrice !== null &&
                        (float)$originalPrice >
                        (float)$sellingPrice
                      ) { ?>


                        <span class="original_price">

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


                        <span class="discount">

                          <?php

                          echo number_format(
                            (float)$discount,
                            0
                          );

                          ?>% OFF

                        </span>


                      <?php } ?>


                    </div>


                    <!-- ADD TO CART -->

                    <?php if ($outOfStock) { ?>


                      <button
                        type="button"
                        class="add-cart-btn"
                        disabled>

                        Out of Stock

                      </button>


                    <?php } else { ?>


                      <button
                        type="button"
                        class="add-cart-btn"
                        data-product-id="<?php echo (int)$product['product_id']; ?>">

                        🛒 Add to Cart

                      </button>


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
                  Please add products from the admin panel.
                </p>

              </div>


            <?php } ?>


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
         TYPE FILTER
    ====================================================== -->

  <script>
    document
      .querySelectorAll('.type_filter')
      .forEach(function(button) {


        button.addEventListener(
          'click',
          function() {


            /* =====================================
               ACTIVE BUTTON
            ===================================== */

            document
              .querySelectorAll('.type_filter')
              .forEach(function(btn) {

                btn.classList.remove(
                  'active'
                );

              });


            this.classList.add(
              'active'
            );


            /* =====================================
               GET FILTER
            ===================================== */

            var filter =
              this.getAttribute(
                'data-filter'
              );


            /* =====================================
               FILTER PRODUCTS
            ===================================== */

            document
              .querySelectorAll(
                '.gallery_item'
              )
              .forEach(function(product) {


                var productType =
                  product.getAttribute(
                    'data-type'
                  );


                /*
                 * ALL
                 */

                if (
                  filter === 'all'
                ) {

                  product.classList.remove(
                    'hidden'
                  );

                  return;
                }


                /*
                 * MATCH
                 */

                if (
                  productType === filter
                ) {

                  product.classList.remove(
                    'hidden'
                  );

                } else {

                  product.classList.add(
                    'hidden'
                  );

                }

              });

          }
        );

      });
  </script>


  <!-- =====================================================
         ADD TO CART
    ====================================================== -->

  <script>
    document
      .querySelectorAll('.add-cart-btn')
      .forEach(function(button) {


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
              "Adding...";


            fetch(
                "config/backend_cart.php?action=add&product_id=" +
                productId
              )

              .then(function(response) {

                return response.json();

              })

              .then(function(data) {


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


                  var quantity =
                    parseInt(
                      data.quantity
                    ) || 1;


                  currentButton.innerHTML =
                    "🛒 Add to Cart (" +
                    quantity +
                    ")";


                  currentButton.disabled =
                    false;


                  return;

                }


                /* =====================================
                   ERROR
                ===================================== */

                alert(
                  data.message ||
                  "Unable to add product to cart."
                );


                currentButton.disabled =
                  false;


                currentButton.innerHTML =
                  "🛒 Add to Cart";

              })


              .catch(function(error) {


                console.error(
                  error
                );


                alert(
                  "Something went wrong while adding product to cart."
                );


                currentButton.disabled =
                  false;


                currentButton.innerHTML =
                  "🛒 Add to Cart";

              });

          }
        );

      });
  </script>


</body>

</html>