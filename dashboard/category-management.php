<?php

session_start();

include "../config/database.php";

/* =========================================================
   1. CHECK LOGIN
========================================================= */

if (!isset($_SESSION['user_id'])) {

  header("Location: ../index.php");
  exit;
}

$login_user_id = $_SESSION['user_id'];


/* =========================================================
   2. CHECK ADMIN
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

$stmt = $conn->prepare($admin_check_sql);

$stmt->execute([
  ':user_id' => $login_user_id
]);

$admin = $stmt->fetch(PDO::FETCH_ASSOC);


/* User not found */

if (!$admin) {

  header("Location: ../index.php");
  exit;
}


/* Check admin */

if ($admin['role'] !== 'admin') {

  header("Location: ../index.php");
  exit;
}


/* =========================================================
   3. DELETE CATEGORY
========================================================= */

if (isset($_GET['delete']) && !empty($_GET['delete'])) {

  $category_id = (int) $_GET['delete'];


  /* ---------------------------------------------
       Get category information
    --------------------------------------------- */

  $check_sql = "
        SELECT
            category_id,
            category_image
        FROM product_category
        WHERE category_id = :category_id
        LIMIT 1
    ";

  $check_stmt = $conn->prepare($check_sql);

  $check_stmt->execute([
    ':category_id' => $category_id
  ]);

  $category = $check_stmt->fetch(PDO::FETCH_ASSOC);


  /* ---------------------------------------------
       Category exists
    --------------------------------------------- */

  if ($category) {

    try {

      /* Delete category */

      $delete_sql = "
                DELETE FROM product_category
                WHERE category_id = :category_id
            ";

      $delete_stmt = $conn->prepare($delete_sql);

      $delete_stmt->execute([
        ':category_id' => $category_id
      ]);


      /* -----------------------------------------
               Delete category image
            ----------------------------------------- */

      if (!empty($category['category_image'])) {

        $image_path = "uploads/categories/" . $category['category_image'];

        if (file_exists($image_path)) {

          unlink($image_path);
        }
      }


      /* -----------------------------------------
               Redirect
            ----------------------------------------- */

      header("Location: category-management.php?deleted=1");
      exit;
    } catch (PDOException $e) {

      die("Category Delete Error: " . $e->getMessage());
    }
  } else {

    header("Location: category-management.php");
    exit;
  }
}


/* =========================================================
   4. CATEGORY DATA
========================================================= */

$category_sql = "
    SELECT *
    FROM product_category
    ORDER BY category_id DESC
";

$category_stmt = $conn->query($category_sql);

$categories = $category_stmt->fetchAll(PDO::FETCH_ASSOC);


/* =========================================================
   5. SUBCATEGORY DATA
========================================================= */

$subcategory_sql = "
    SELECT *
    FROM product_subcategory
    ORDER BY subcategory_id ASC
";

$subcategory_stmt = $conn->query($subcategory_sql);

$subcategories = $subcategory_stmt->fetchAll(PDO::FETCH_ASSOC);


/* =========================================================
   6. ADD PRODUCT
========================================================= */

if (isset($_POST['add_product'])) {

  $subcategory_id = $_POST['subcategory_id'] ?? '';

  $product_name = trim(
    $_POST['product_name'] ?? ''
  );

  $product_code = trim(
    $_POST['product_code'] ?? ''
  );

  $stock_quantity = (int)(
    $_POST['stock_quantity'] ?? 0
  );

  $product_description = trim(
    $_POST['product_description'] ?? ''
  );


  /* ---------------------------------------------
       Validation
    --------------------------------------------- */

  if (
    $subcategory_id === '' ||
    $product_name === ''
  ) {

    die("Please fill all required fields.");
  }


  try {

    /* -----------------------------------------
           Insert Product
        ----------------------------------------- */

    $insert_sql = "
            INSERT INTO products
            (
                subcategory_id,
                product_name,
                product_code,
                stock_quantity,
                product_description,
                status
            )
            VALUES
            (
                :subcategory_id,
                :product_name,
                :product_code,
                :stock_quantity,
                :product_description,
                1
            )
            RETURNING product_id
        ";


    $insert_stmt = $conn->prepare($insert_sql);


    $insert_stmt->execute([

      ':subcategory_id' =>
      $subcategory_id,

      ':product_name' =>
      $product_name,

      ':product_code' =>
      $product_code,

      ':stock_quantity' =>
      $stock_quantity,

      ':product_description' =>
      $product_description
    ]);


    /* -----------------------------------------
           Get inserted product ID
        ----------------------------------------- */

    $product_id = $insert_stmt->fetchColumn();


    /* -----------------------------------------
           Redirect to add price
        ----------------------------------------- */

    header(
      "Location: add_price.php?product_id=" .
        $product_id
    );

    exit;
  } catch (PDOException $e) {

    die("Product Error: " .
      $e->getMessage());
  }
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

  <title>Category Management</title>


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


  <!-- Custom Theme -->

  <link
    href="assets/css/custom.min.css"
    rel="stylesheet">

</head>


<body class="nav-md">


  <div class="container body">

    <div class="main_container">


      <!-- =====================================================
             SIDEBAR
        ====================================================== -->

      <?php include "sidebar.php"; ?>


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


              <!-- Menu Toggle -->

              <div class="nav toggle">

                <a id="menu_toggle">

                  <i class="fa fa-bars"></i>

                </a>

              </div>


              <!-- Right Menu -->

              <ul
                class="nav navbar-nav navbar-right">


                <!-- User Profile -->

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

                    <span
                      class="fa fa-angle-down"></span>

                  </a>


                  <ul
                    class="dropdown-menu dropdown-usermenu pull-right">


                    <li>

                      <a href="profile.php">

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

                      <a href="logout.php">

                        <i
                          class="fa fa-sign-out pull-right"></i>

                        Log Out

                      </a>

                    </li>


                  </ul>

                </li>


                <!-- Notification -->

                <li
                  role="presentation"
                  class="dropdown">

                  <a
                    href="javascript:;"
                    class="dropdown-toggle info-number"
                    data-toggle="dropdown">

                    <i
                      class="fa fa-envelope-o"></i>

                    <span class="badge bg-green">

                      6

                    </span>

                  </a>

                </li>


              </ul>

            </nav>

          </div>

        </div>


        <!-- =================================================
                 PAGE CONTENT
            ================================================== -->

        <div class="container-fluid">

          <div class="row">

            <div class="col-md-12">


              <!-- PAGE HEADER -->

              <div class="page-title">

                <div class="title_left">

                  <h3>
                    Category Management
                  </h3>

                  <p>
                    Add, update and delete categories
                  </p>

                </div>

              </div>


              <div class="clearfix"></div>


              <!-- =================================================
                             SUCCESS MESSAGE
                        ================================================== -->

              <?php

              if (
                isset($_GET['deleted']) &&
                $_GET['deleted'] == 1
              ) {

              ?>

                <div class="alert alert-success">

                  <i
                    class="fa fa-check-circle"></i>

                  Category deleted successfully.

                </div>

              <?php

              }

              ?>


              <!-- =================================================
                             CATEGORY TABLE
                        ================================================== -->

              <div class="x_panel">


                <div class="x_title">

                  <h2>
                    Category List
                  </h2>

                  <div class="clearfix"></div>

                </div>


                <div class="x_content">


                  <div class="table-responsive">


                    <table
                      class="table table-striped table-bordered">


                      <thead>

                        <tr>

                          <th>
                            #
                          </th>

                          <th>
                            Category ID
                          </th>

                          <th>
                            Category Image
                          </th>

                          <th>
                            Category Name
                          </th>

                          <th>
                            Description
                          </th>

                          <th>
                            Status
                          </th>

                          <th>
                            Created Date
                          </th>

                          <th>
                            Action
                          </th>

                        </tr>

                      </thead>


                      <tbody>


                        <?php

                        if (
                          count($categories) > 0
                        ) {

                          $count = 1;


                          foreach (
                            $categories
                            as $category
                          ) {

                        ?>


                            <tr>


                              <!-- Number -->

                              <td>

                                <?php
                                echo $count++;
                                ?>

                              </td>


                              <!-- Category ID -->

                              <td>

                                <?php

                                echo htmlspecialchars(
                                  $category['category_id']
                                );

                                ?>

                              </td>


                              <!-- Category Image -->

                              <td>


                                <?php

                                if (
                                  !empty($category['category_image'])
                                ) {

                                ?>
                                  <img
                                    src="../assets/images/<?php
                                                          echo htmlspecialchars($category['category_image']);
                                                          ?>"
                                    style="
        width:60px;
        height:60px;
        object-fit:cover;
        border-radius:8px;
    "
                                    alt="Category">
                                <?php

                                } else {

                                ?>


                                  <div
                                    style="
                                                            width:60px;
                                                            height:60px;
                                                            background:#f1f1f1;
                                                            display:flex;
                                                            align-items:center;
                                                            justify-content:center;
                                                            border-radius:8px;
                                                        ">

                                    <i
                                      class="fa fa-image"></i>

                                  </div>


                                <?php

                                }

                                ?>

                              </td>


                              <!-- Category Name -->

                              <td>

                                <strong>

                                  <?php

                                  echo htmlspecialchars(
                                    $category['category_name']
                                  );

                                  ?>

                                </strong>

                              </td>


                              <!-- Description -->

                              <td>


                                <?php

                                if (
                                  !empty($category['category_description'])
                                ) {

                                  echo htmlspecialchars(
                                    $category['category_description']
                                  );
                                } else {

                                  echo "N/A";
                                }

                                ?>

                              </td>


                              <!-- Status -->

                              <td>


                                <?php

                                if (
                                  $category['status'] == 1
                                ) {

                                ?>

                                  <span
                                    class="label label-success">

                                    Active

                                  </span>

                                <?php

                                } else {

                                ?>

                                  <span
                                    class="label label-default">

                                    Inactive

                                  </span>

                                <?php

                                }

                                ?>

                              </td>


                              <!-- Created Date -->

                              <td>


                                <?php

                                if (
                                  !empty($category['created_at'])
                                ) {

                                  echo date(
                                    "d M Y, h:i A",
                                    strtotime(
                                      $category['created_at']
                                    )
                                  );
                                } else {

                                  echo "N/A";
                                }

                                ?>

                              </td>


                              <!-- Actions -->

                              <td>


                                <!-- Edit -->

                                <a
                                  href="edit_category.php?id=<?php
                                                              echo (int)$category['category_id'];
                                                              ?>"
                                  class="btn btn-warning btn-sm"
                                  title="Edit Category">

                                  <i
                                    class="fa fa-edit"></i>

                                </a>


                                <!-- Delete -->

                                <a
                                  href="category-management.php?delete=<?php
                                                                        echo (int)$category['category_id'];
                                                                        ?>"
                                  class="btn btn-danger btn-sm"
                                  title="Delete Category"
                                  onclick="return confirm('Are you sure you want to delete this category?');">

                                  <i
                                    class="fa fa-trash"></i>

                                </a>


                              </td>


                            </tr>


                          <?php

                          }
                        } else {

                          ?>


                          <tr>

                            <td
                              colspan="8"
                              class="text-center">

                              No Categories Found

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


              <!-- =================================================
                             ADD CATEGORY BUTTON
                        ================================================== -->

              <div class="text-right">

                <a
                  href="add_category.php"
                  class="btn btn-primary">

                  <i
                    class="fa fa-plus"></i>

                  Add Flower Category

                </a>

              </div>


            </div>

          </div>

        </div>


        <!-- =================================================
                 FOOTER
            ================================================== -->

        <footer>

          <div class="pull-right">

            Gentelella -
            Bootstrap Admin Template

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
    src="assets/vendors/jquery/dist/jquery.min.js"></script>


  <!-- Bootstrap -->

  <script
    src="assets/vendors/bootstrap/dist/js/bootstrap.min.js"></script>


  <!-- FastClick -->

  <script
    src="assets/vendors/fastclick/lib/fastclick.js"></script>


  <!-- NProgress -->

  <script
    src="assets/vendors/nprogress/nprogress.js"></script>


  <!-- Bootstrap Progressbar -->

  <script
    src="assets/vendors/bootstrap-progressbar/bootstrap-progressbar.min.js"></script>


  <!-- iCheck -->

  <script
    src="assets/vendors/iCheck/icheck.min.js"></script>


  <!-- Custom Theme -->

  <script
    src="assets/js/custom.min.js"></script>


</body>

</html>