<?php

require_once "config/database.php";

/* =========================================================
   FETCH ANY 8 ACTIVE PRODUCTS
========================================================= */

$sql = "
    SELECT
        p.product_id,
        p.product_name,
        p.product_description,

        pp.selling_price,

        pi.image_name

    FROM products p

    LEFT JOIN LATERAL
    (
        SELECT
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

    ORDER BY RANDOM()

    LIMIT 8
";

$stmt = $conn->prepare($sql);
$stmt->execute();

$gallery_products = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html>

<head>
  <!-- Basic -->
  <meta charset="utf-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <!-- Mobile Metas -->
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
  <!-- Site Metas -->
  <meta name="keywords" content="" />
  <meta name="description" content="" />
  <meta name="author" content="" />

  <title>Fior</title>

  <!-- slider stylesheet -->
  <link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.1.3/assets/owl.carousel.min.css" />

  <!-- bootstrap core css -->
  <link rel="stylesheet" type="text/css" href="css/bootstrap.css" />

  <!-- fonts style -->
  <link href="https://fonts.googleapis.com/css?family=Baloo+Chettan|Poppins:400,600,700&display=swap" rel="stylesheet">
  <!-- Custom styles for this template -->
  <link href="css/style.css" rel="stylesheet" />
  <!-- responsive style -->
  <link href="css/responsive.css" rel="stylesheet" />
</head>

<body>

  <div class="hero_area">
    <!-- header section strats -->
    <?php include 'header.php';  ?>
    <!-- end header section -->
    <!-- slider section -->
    <section class=" slider_section position-relative">
      <div class="slider_number-container ">
        <div class="number-box">
          <span>
            01
          </span>
          <hr>
          <span>
            02
          </span>
        </div>
      </div>
      <div class="container">
        <div class="row">
          <div id="carouselExampleIndicators" class="carousel slide" data-ride="carousel">
            <div class="carousel-inner">
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
                      <a href="">Buy Now</a>
                    </div>
                  </div>
                </div>
              </div>
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
                      Welcome to Fior Flower Shop, where fresh flowers bring beauty and happiness to every special moment. Discover beautiful bouquets and elegant floral arrangements, thoughtfully designed for birthdays, weddings, anniversaries, and every memorable occasion. 💐
                    </p>
                    <div>
                      <a href="">Buy Now</a>
                    </div>
                  </div>
                </div>
              </div>
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
                      Discover the beauty of fresh flowers at Fior Flower Shop. We create beautiful bouquets and floral arrangements to make your special moments more joyful, colorful, and memorable. 💐
                    </p>
                    <div>
                      <a href="">Buy Now</a>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="carousel_btn-container">
              <a class="carousel-control-prev" href="#carouselExampleIndicators" role="button" data-slide="prev">
                <span class="sr-only">Previous</span>
              </a>
              <a class="carousel-control-next" href="#carouselExampleIndicators" role="button" data-slide="next">
                <span class="sr-only">Next</span>
              </a>
            </div>
          </div>
        </div>
      </div>

    </section>
    <!-- end slider section -->
  </div>

  <!-- about section -->
  <section class="about_section ">
    <div class="section_number">
      01
    </div>
    <div class="container">
      <div class="row">
        <div class="col-md-6 col-xl-7">
          <div class="img-box">
            <img src="images/about-img.png" alt="" />
          </div>
        </div>
        <div class="col-md-5 col-xl-5">
          <div class="detail_box">
            <div class="heading_container justify-content-end">
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
  <!-- end about section -->

  <!-- why section -->
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
            <a href="">
              Read More
            </a>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- end why section -->




  <!-- gallery section -->
  <section class="gallery_section layout_padding">

    <div class="section_number">
      03
    </div>

    <div class="heading_container justify-content-center">
      <h2>Our Gallery</h2>
    </div>

    <div class="container">

      <div class="gallery_grid">

        <?php if (!empty($gallery_products)) { ?>

          <?php foreach ($gallery_products as $product) { ?>

            <?php

            /* =====================================================
             PRODUCT IMAGE
          ===================================================== */

            if (!empty($product['image_name'])) {

              $image_path =
                "uploads/products/" .
                basename($product['image_name']);
            } else {

              $image_path =
                "images/no-image.jpg";
            }


            /* =====================================================
             PRODUCT PRICE
          ===================================================== */

            $price = $product['selling_price'] ?? 0;

            ?>

            <div class="gallery_item">

              <!-- PRODUCT IMAGE -->
              <img
                src="<?php echo htmlspecialchars($image_path); ?>"
                alt="<?php echo htmlspecialchars($product['product_name']); ?>">

              <!-- PRODUCT INFORMATION -->
              <div class="product_info">

                <h5>
                  <?php
                  echo htmlspecialchars(
                    $product['product_name']
                  );
                  ?>
                </h5>

                <p>
                  ₹<?php
                    echo number_format(
                      (float)$price,
                      2
                    );
                    ?>
                </p>

                <!-- ADD TO CART BUTTON -->
                <button
                  type="button"
                  class="add-to-cart"
                  data-product-id="<?php echo (int)$product['product_id']; ?>">
                  Add to Cart
                </button>

              </div>

            </div>

          <?php } ?>

        <?php } else { ?>

          <div class="col-12 text-center">

            <p>
              No products available.
            </p>

          </div>

        <?php } ?>

      </div>

    </div>

  </section>
  <!-- end gallery section -->


  <script>
    document.addEventListener("DOMContentLoaded", function() {

      const buttons = document.querySelectorAll(".add-to-cart");

      buttons.forEach(function(button) {

        button.addEventListener("click", function() {

          const productId = this.getAttribute("data-product-id");

          if (!productId) {
            return;
          }

          /* Disable button while processing */
          this.disabled = true;
          this.innerText = "Adding...";

          fetch(
              "config/backend_cart.php?action=add&product_id=" + productId, {
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

              /* =========================================
                 LOGIN REQUIRED
              ========================================= */

              if (data.login_required) {

                window.location.href = "login.php";

                return;
              }


              /* =========================================
                 PRODUCT ADDED
              ========================================= */

              if (
                data.success ||
                data.status === "success"
              ) {

                window.location.href = "cart.php";

                return;
              }


              /* =========================================
                 ERROR
              ========================================= */

              alert(
                data.message ||
                "Something went wrong while adding product to cart."
              );

              button.disabled = false;
              button.innerText = "Add to Cart";

            })
            .catch(function(error) {

              console.error(error);

              alert(
                "Something went wrong while adding product to cart."
              );

              button.disabled = false;
              button.innerText = "Add to Cart";

            });

        });

      });

    });
  </script>




  <!-- client section -->

  <section class="client_section layout_padding">
    <div class="container">
      <div class="heading_container justify-content-center">
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
                <img src="images/client-1.png">
              </div>
              <h5>
                nomil du
              </h5>
              <p>Contrary to popular belief, Lorem Ipsum is not simply random text. It has roots in a piece of
                classical Latin literature from 45 BC, making it over </p>
            </div>
          </div>
        </div>
        <div class="col-md-6">
          <div class="client_box">
            <div class="detail_box">
              <div class="img_box">
                <img src="images/client-2.png">
              </div>
              <h5>
                zabih jo
              </h5>
              <p>Contrary to popular belief, Lorem Ipsum is not simply random text. It has roots in a piece of
                classical Latin literature from 45 BC, making it over </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>



  <!-- end client section -->

  <!-- arrange section -->

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



  <!-- end arrange section -->

  <!-- contact section -->

  <section class="contact_section layout_padding">
    <div class="section_number">
      05
    </div>
    <div class="container ">
      <div class="heading_container justify-content-center">
        <h2 class="">
          Contact Us
        </h2>
      </div>

    </div>
    <div class="container">
      <div class="row">
        <div class="col-md-6 mx-auto">
          <form action="">
            <div>
              <input type="text" placeholder="Name" />
            </div>
            <div>
              <input type="email" placeholder="Email" />
            </div>
            <div>
              <input type="text" placeholder="Pone Number" />
            </div>
            <div>
              <input type="text" class="message-box" placeholder="Message" />
            </div>
            <div class="d-flex  mt-4 ">
              <button>
                SEND
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </section>

  <!-- end contact section -->

  <!-- map section -->

  <div class="map_section">
    <div class="map_container">
      <div class="map">
        <div id="googleMap"></div>
      </div>
    </div>
  </div>

  <!-- end map section -->


  <!-- footer section -->
  <?php include 'footer.php';  ?>
  <!-- footer section -->

  <script type="text/javascript" src="js/jquery-3.4.1.min.js"></script>
  <script type="text/javascript" src="js/bootstrap.js"></script>
  <script type="text/javascript" src="js/custom.js"></script>
  <!-- Google Map -->
  <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCh39n5U-4IoWpsVGUHWdqB6puEkhRLdmI&callback=myMap">
  </script>
  <!-- End Google Map -->

</body>

</html>