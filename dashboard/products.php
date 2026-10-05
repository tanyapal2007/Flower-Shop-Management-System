<?php

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

require_once "../config/database.php";


/* =========================================================
   CHECK LOGIN
========================================================= */

$login_user_id = $_SESSION['user_id'] ?? 0;

if (!$login_user_id) {
  header("Location: ../login.php");
  exit;
}


/* =========================================================
   CHECK ADMIN
========================================================= */

$admin_check_sql = "
    SELECT
        user_id,
        name,
        phone,
        role,
        status,
        created_at
    FROM users
    WHERE user_id = :user_id
    LIMIT 1
";

$admin_check_stmt = $conn->prepare($admin_check_sql);

$admin_check_stmt->execute([
  ':user_id' => $login_user_id
]);

$admin = $admin_check_stmt->fetch(PDO::FETCH_ASSOC);


/* =========================================================
   ADMIN VALIDATION
========================================================= */

if (
  !$admin ||
  $admin['role'] !== 'admin' ||
  (int)$admin['status'] !== 1
) {
  header("Location: ../index.php");
  exit;
}


/* =========================================================
   DELETE PRODUCT
========================================================= */

if (isset($_GET['delete'])) {

  $product_id = (int)$_GET['delete'];

  if ($product_id > 0) {

    try {

      $delete_sql = "
                DELETE FROM products
                WHERE product_id = :product_id
            ";

      $delete_stmt = $conn->prepare($delete_sql);

      $delete_stmt->execute([
        ':product_id' => $product_id
      ]);

      header("Location: products.php");
      exit;
    } catch (PDOException $e) {

      die("Delete Error: " .
        htmlspecialchars($e->getMessage()));
    }
  }
}


/* =========================================================
   FETCH PRODUCTS + LATEST PRICE
========================================================= */


/* =========================================================
   FETCH PRODUCTS + LATEST PRICE
========================================================= */

$sql = "
    SELECT
        p.product_id,
        p.product_name,
        p.product_description,
        p.product_code,
        p.stock_quantity,
        p.status,
        p.created_at,

        ps.subcategory_name,

        pc.category_name,

        pp.price_id,
        pp.original_price,
        pp.discount_percentage,
        pp.selling_price,
        pp.start_date,
        pp.end_date

    FROM products p

    LEFT JOIN product_subcategory ps
        ON p.subcategory_id = ps.subcategory_id

    LEFT JOIN product_category pc
        ON ps.category_id = pc.category_id

    LEFT JOIN LATERAL
    (
        SELECT
            product_prices.price_id,
            product_prices.original_price,
            product_prices.discount_percentage,
            product_prices.selling_price,
            product_prices.start_date,
            product_prices.end_date

        FROM product_prices

        WHERE product_prices.product_id = p.product_id

        ORDER BY product_prices.price_id DESC

        LIMIT 1

    ) pp
        ON TRUE

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
<html lang="en">

<head>

  <meta charset="utf-8">

  <meta
    http-equiv="X-UA-Compatible"
    content="IE=edge">

  <meta
    name="viewport"
    content="width=device-width, initial-scale=1">

  <title>Products</title>


  <!-- Bootstrap -->

  <link
    href="assets/vendors/bootstrap/dist/css/bootstrap.min.css"
    rel="stylesheet">


  <!-- Font Awesome -->

  <link
    href="assets/vendors/font-awesome/css/font-awesome.min.css"
    rel="stylesheet">


  <!-- NProgress -->

  <link
    href="assets/vendors/nprogress/nprogress.css"
    rel="stylesheet">


  <!-- iCheck -->

  <link
    href="assets/vendors/iCheck/skins/flat/green.css"
    rel="stylesheet">


  <!-- Custom Theme -->

  <link
    href="assets/css/custom.min.css"
    rel="stylesheet">


  <!-- DataTables -->

  <link
    rel="stylesheet"
    href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap.min.css">

</head>


<body class="nav-md">


  <div class="container body">

    <div class="main_container">


      <!-- =====================================================
             SIDEBAR
        ====================================================== -->

      <?php include 'sidebar.php'; ?>


      <!-- =====================================================
             RIGHT CONTENT
        ====================================================== -->

      <div
        class="right_col"
        role="main">


        <!-- =================================================
                 TOP NAVIGATION
            ================================================== -->

        <div class="top_nav">

          <div class="nav_menu">

            <nav>

              <div class="nav toggle">

                <a id="menu_toggle">

                  <i class="fa fa-bars"></i>

                </a>

              </div>


              <ul class="nav navbar-nav navbar-right">

                <li>

                  <a
                    href="javascript:;"
                    class="user-profile dropdown-toggle"
                    data-toggle="dropdown">

                    <img
                      src="assets/images/img.jpg"
                      alt="">

                    <?php
                    echo htmlspecialchars(
                      $admin['name']
                    );
                    ?>

                    <span class="fa fa-angle-down"></span>

                  </a>


                  <ul
                    class="dropdown-menu dropdown-usermenu pull-right">

                    <li>

                      <a href="javascript:;">
                        Profile
                      </a>

                    </li>


                    <li>

                      <a href="javascript:;">
                        Settings
                      </a>

                    </li>


                    <li>

                      <a href="javascript:;">
                        Help
                      </a>

                    </li>


                    <li>

                      <a href="../logout.php">

                        <i class="fa fa-sign-out pull-right"></i>

                        Log Out

                      </a>

                    </li>

                  </ul>

                </li>

              </ul>

            </nav>

          </div>

        </div>


        <!-- =================================================
                 PAGE CONTENT
            ================================================== -->

        <div
          class="container-fluid"
          style="padding:25px;">


          <!-- PAGE HEADER -->

          <div class="row">

            <div class="col-md-8">

              <h2 style="margin-top:0;">

                Products

              </h2>

              <p class="text-muted">

                Manage all products

              </p>

            </div>


            <div class="col-md-4 text-right">

              <a
                href="add_product.php"
                class="btn btn-primary">

                <i class="fa fa-plus"></i>

                Add Product

              </a>

            </div>

          </div>


          <br>


          <!-- =================================================
                     PRODUCT CARD
                ================================================== -->

          <div class="x_panel">

            <div class="x_title">

              <h2>

                Product List

                <small>
                  All Products
                </small>

              </h2>

              <div class="clearfix"></div>

            </div>


            <div class="x_content">


              <div class="table-responsive">


                <table
                  id="product_table"
                  class="table table-bordered table-hover">


                  <!-- TABLE HEADER -->

                  <thead>

                    <tr>

                      <th>#</th>

                      <th>
                        Product ID
                      </th>

                      <th>
                        Product Name
                      </th>

                      <th>
                        Category
                      </th>

                      <th>
                        Subcategory
                      </th>

                      <th>
                        Product Code
                      </th>

                      <th>
                        Stock
                      </th>

                      <th>
                        Original Price
                      </th>

                      <th>
                        Selling Price
                      </th>

                      <th>
                        Discount
                      </th>

                      <th>
                        Status
                      </th>

                      <th>
                        Date
                      </th>

                      <th class="text-center">
                        Action
                      </th>

                    </tr>

                  </thead>


                  <!-- TABLE BODY -->

                  <tbody>

                    <?php

                    $counter = 1;

                    if (count($products) > 0) {

                      foreach ($products as $product) {

                    ?>

                        <tr>


                          <!-- NUMBER -->

                          <td>

                            <?php
                            echo $counter++;
                            ?>

                          </td>


                          <!-- PRODUCT ID -->

                          <td>

                            <?php
                            echo htmlspecialchars(
                              $product['product_id']
                            );
                            ?>

                          </td>


                          <!-- PRODUCT NAME -->

                          <td>

                            <strong>

                              <?php
                              echo htmlspecialchars(
                                $product['product_name']
                              );
                              ?>

                            </strong>

                            <br>

                            <small class="text-muted">

                              <?php

                              if (
                                !empty($product['product_description'])
                              ) {

                                $description =
                                  $product['product_description'];

                                echo htmlspecialchars(
                                  substr(
                                    $description,
                                    0,
                                    50
                                  )
                                );

                                if (
                                  strlen($description) > 50
                                ) {

                                  echo "...";
                                }
                              }

                              ?>

                            </small>

                          </td>


                          <!-- CATEGORY -->

                          <td>

                            <?php

                            if (
                              !empty($product['category_name'])
                            ) {

                              echo htmlspecialchars(
                                $product['category_name']
                              );
                            } else {

                              echo '<span class="text-muted">
                                                        N/A
                                                      </span>';
                            }

                            ?>

                          </td>


                          <!-- SUBCATEGORY -->

                          <td>

                            <?php

                            if (
                              !empty($product['subcategory_name'])
                            ) {

                              echo htmlspecialchars(
                                $product['subcategory_name']
                              );
                            } else {

                              echo '<span class="text-muted">
                                                        N/A
                                                      </span>';
                            }

                            ?>

                          </td>


                          <!-- PRODUCT CODE -->

                          <td>

                            <?php

                            if (
                              !empty($product['product_code'])
                            ) {

                              echo htmlspecialchars(
                                $product['product_code']
                              );
                            } else {

                              echo '<span class="text-muted">
                                                        N/A
                                                      </span>';
                            }

                            ?>

                          </td>


                          <!-- STOCK -->

                          <td>

                            <?php

                            echo (int)
                            $product['stock_quantity'];

                            ?>

                          </td>


                          <!-- ORIGINAL PRICE -->

                          <td>

                            <?php

                            if (
                              $product['original_price']
                              !== null
                            ) {

                              echo '₹ ';

                              echo number_format(
                                (float)
                                $product['original_price'],
                                2
                              );
                            } else {

                              echo '<span class="text-muted">
                                                        N/A
                                                      </span>';
                            }

                            ?>

                          </td>


                          <!-- SELLING PRICE -->

                          <td>

                            <?php

                            if (
                              $product['selling_price']
                              !== null
                            ) {

                              echo '₹ ';

                              echo number_format(
                                (float)
                                $product['selling_price'],
                                2
                              );
                            } else {

                              echo '<span class="text-muted">
                                                        N/A
                                                      </span>';
                            }

                            ?>

                          </td>


                          <!-- DISCOUNT -->

                          <td>

                            <?php

                            if (
                              $product['discount_percentage']
                              !== null
                            ) {

                              echo number_format(
                                (float)
                                $product['discount_percentage'],
                                2
                              );

                              echo '%';
                            } else {

                              echo '0%';
                            }

                            ?>

                          </td>


                          <!-- STATUS -->

                          <td>

                            <?php

                            if (
                              (int)$product['status'] === 1
                            ) {

                              echo '
                                                    <span class="label label-success">
                                                        Active
                                                    </span>';
                            } else {

                              echo '
                                                    <span class="label label-danger">
                                                        Inactive
                                                    </span>';
                            }

                            ?>

                          </td>


                          <!-- DATE -->

                          <td>

                            <?php

                            if (
                              !empty($product['created_at'])
                            ) {

                              echo date(
                                "d-m-Y",
                                strtotime(
                                  $product['created_at']
                                )
                              );
                            }

                            ?>

                          </td>


                          <!-- ACTION -->

                          <td
                            class="text-center"
                            style="white-space:nowrap;">


                            <!-- PRODUCT IMAGES -->

                            <a
                              href="product_images.php?product_id=<?php echo (int)$product['product_id']; ?>"
                              class="btn btn-sm btn-info"
                              title="Manage Product Images">

                              <i class="fa fa-image"></i>

                            </a>


                            <!-- EDIT -->

                            <a
                              href="edit_product.php?id=<?php echo (int)$product['product_id']; ?>"
                              class="btn btn-sm btn-warning"
                              title="Edit Product">

                              <i class="fa fa-edit"></i>

                            </a>


                            <!-- DELETE -->

                            <a
                              href="products.php?delete=<?php echo (int)$product['product_id']; ?>"
                              class="btn btn-sm btn-danger"
                              title="Delete Product"
                              onclick="return confirm('Are you sure you want to delete this product?');">

                              <i class="fa fa-trash"></i>

                            </a>


                          </td>

                        </tr>


                      <?php

                      }
                    } else {

                      ?>

                      <tr>

                        <td
                          colspan="13"
                          class="text-center">

                          <br>

                          <i
                            class="fa fa-shopping-bag fa-3x text-muted">
                          </i>

                          <h4>
                            No Products Found
                          </h4>

                          <p class="text-muted">

                            Click "Add Product"
                            to add your first product.

                          </p>

                          <br>

                        </td>

                      </tr>

                    <?php

                    }

                    ?>

                  </tbody>

                </table>

              </div>

            </div>

          </div>

        </div>


        <!-- =================================================
                 FOOTER
            ================================================== -->

        <footer>

          <div class="pull-right">

            Fior Flower Shop - Admin Panel

          </div>

          <div class="clearfix"></div>

        </footer>


      </div>

    </div>

  </div>


  <!-- =========================================================
     JAVASCRIPT
========================================================= -->


  <!-- jQuery -->

  <script
    src="assets/vendors/jquery/dist/jquery.min.js">
  </script>


  <!-- Bootstrap -->

  <script
    src="assets/vendors/bootstrap/dist/js/bootstrap.min.js">
  </script>


  <!-- FastClick -->

  <script
    src="assets/vendors/fastclick/lib/fastclick.js">
  </script>


  <!-- NProgress -->

  <script
    src="assets/vendors/nprogress/nprogress.js">
  </script>


  <!-- Custom -->

  <script
    src="assets/js/custom.min.js">
  </script>


  <!-- DataTables -->

  <script
    src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js">
  </script>

  <script
    src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap.min.js">
  </script>


  <script>
    $(document).ready(function() {

      $('#product_table').DataTable({

        pageLength: 10,

        lengthMenu: [
          [10, 25, 50, 100, -1],
          [10, 25, 50, 100, "All"]
        ],

        ordering: true,

        searching: true,

        paging: true,

        info: true,

        autoWidth: false

      });

    });
  </script>


</body>

</html>