<?php

session_start();
include "../config/database.php";

// ======================================================
// 1. CHECK LOGIN
// ======================================================

if (!isset($_SESSION['user_id'])) {

  header("Location: ../index.php");
  exit;
}


$login_user_id = $_SESSION['user_id'];


// ======================================================
// 2. CHECK ADMIN
// ======================================================

$admin_check_sql = "SELECT
                        user_id,
                        name,
                        phone,
                        role,
                        status,
                        created_at
                    FROM users
                    WHERE user_id = :user_id
                    LIMIT 1";

$stmt = $conn->prepare($admin_check_sql);

$stmt->execute([
  'user_id' => $login_user_id
]);

$admin = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$admin) {
  header("Location: ../index.php");
  exit;
}

if ($admin['role'] != 'admin') {
  header("Location: ../index.php");
  exit;
}



/* =========================================================
   DELETE CATEGORY
========================================================= */

if (isset($_GET['delete']) && !empty($_GET['delete'])) {

  $category_id = (int) $_GET['delete'];

  /* Check category exists */
  $check_sql = "
        SELECT category_image
        FROM product_category
        WHERE category_id = $category_id
        LIMIT 1
    ";
  $check_stmt = $conn->prepare($check_sql);
  $check_stmt->execute();

  $category = $check_stmt->fetch(PDO::FETCH_ASSOC);


  if ($category) {

    /* Delete category */

    /* Delete category */
    $delete_sql = "
            DELETE FROM product_category
            WHERE category_id = $category_id
        ";

    if ($conn->query($delete_sql)) {

      /* Delete category image */
      if (
        !empty($category['category_image'])
      ) {

        $image_path =
          "uploads/categories/" .
          $category['category_image'];

        if (file_exists($image_path)) {
          unlink($image_path);
        }
      }

      /* Redirect back */
      header("Location: category-management.php?deleted=1");
      exit;
    } else {

      die("Category Delete Error: " .
        $conn->error);
    }
  } else {

    header("Location: category-management.php");
    exit;
  }
}


/* =========================================================
   CATEGORY DATA
========================================================= */

$category_sql = "
    SELECT *
    FROM product_category
    ORDER BY category_id DESC
";

$category_stmt = $conn->query($category_sql);
$categories = $category_stmt->fetchAll(PDO::FETCH_ASSOC);


/* =========================================================
SUBCATEGORY DATA
========================================================= */
$subcategory_sql = "SELECT * FROM product_subcategory ORDER BY subcategory_id ASC";
$subcategory_result = mysqli_query($conn, $subcategory_sql);

/* =========================================================
ADD CATEGORY
========================================================= */
if (isset($_POST['add_product'])) {

  $category_id = mysqli_real_escape_string($conn, $_POST['category_id']);
  $subcategory_id = mysqli_real_escape_string($conn, $_POST['subcategory_id']);
  $product_name = mysqli_real_escape_string($conn, $_POST['product_name']);
  $product_code = mysqli_real_escape_string($conn, $_POST['product_code']);
  $stock_quantity = (int)$_POST['stock_quantity'];
  $product_description = mysqli_real_escape_string(
    $conn,
    $_POST['product_description']
  );

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
'$subcategory_id',
'$product_name',
'$product_code',
'$stock_quantity',
'$product_description',
1
)
";

  if (mysqli_query($conn, $insert_sql)) {

    $product_id = mysqli_insert_id($conn);

    header("Location: add_price.php?product_id=" . $product_id);
    exit;
  } else {

    echo "Product Error: " . mysqli_error($conn);
  }
}
?>
<!-- <?php if (isset($_GET['deleted'])) { ?>

  <div class="alert alert-success">
    <i class="fa fa-check-circle"></i>
    <!-- Category deleted successfully. -->
</div>

<?php } ?> -->

<!DOCTYPE html>
<html lang="en">

<head>

  <meta charset="utf-8">

  <meta http-equiv="X-UA-Compatible" content="IE=edge">

  <meta name="viewport"
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

      <div class="right_col" role="main">

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

                  <a href="javascript:;"
                    class="user-profile dropdown-toggle"
                    data-toggle="dropdown">

                    <img
                      src="assets/images/img.jpg"
                      alt="">

                    John Doe

                    <span class="fa fa-angle-down"></span>

                  </a>

                  <ul class="dropdown-menu dropdown-usermenu pull-right">

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

                      <a href="login.php">

                        <i class="fa fa-sign-out pull-right"></i>

                        Log Out

                      </a>

                    </li>

                  </ul>

                </li>


                <li role="presentation"
                  class="dropdown">

                  <a href="javascript:;"
                    class="dropdown-toggle info-number"
                    data-toggle="dropdown">

                    <i class="fa fa-envelope-o"></i>

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

                          <th>#</th>

                          <th>Category ID</th>

                          <th>Category Image</th>

                          <th>Category Name</th>

                          <th>Description</th>

                          <th>Status</th>

                          <th>Created Date</th>

                          <th>Action</th>

                        </tr>

                      </thead>


                      <tbody>

                        <?php

                        if (count($categories) > 0) {

                          $count = 1;

                          foreach ($categories as $category) {

                        ?>

                            <tr>

                              <td>
                                <?php
                                echo $count++;
                                ?>
                              </td>


                              <td>
                                <?php
                                echo htmlspecialchars(
                                  $category['category_id']
                                );
                                ?>
                              </td>


                              <td>

                                <?php
                                if (
                                  !empty($category['category_image'])
                                ) {
                                ?>

                                  <img
                                    src="uploads/categories/<?php
                                                            echo htmlspecialchars(
                                                              $category['category_image']
                                                            );
                                                            ?>"
                                    style="
                                                                width:60px;
                                                                height:60px;
                                                                object-fit:cover;
                                                                border-radius:8px;
                                                            ">

                                <?php
                                } else {
                                ?>

                                  <div style="
                                                            width:60px;
                                                            height:60px;
                                                            background:#f1f1f1;
                                                            display:flex;
                                                            align-items:center;
                                                            justify-content:center;
                                                            border-radius:8px;
                                                        ">

                                    <i class="fa fa-image"></i>

                                  </div>

                                <?php
                                }
                                ?>

                              </td>


                              <td>

                                <strong>

                                  <?php
                                  echo htmlspecialchars(
                                    $category['category_name']
                                  );
                                  ?>

                                </strong>

                              </td>


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


                              <td>

                                <?php

                                if (
                                  $category['status'] == 1
                                ) {

                                  echo '<span class="label label-success">
                                                                Active
                                                              </span>';
                                } else {

                                  echo '<span class="label label-default">
                                                                Inactive
                                                              </span>';
                                }

                                ?>

                              </td>


                              <td>

                                <?php

                                echo date(
                                  "d M Y, h:i A",
                                  strtotime(
                                    $category['created_at']
                                  )
                                );

                                ?>

                              </td>


                              <td>

                                <a
                                  href="edit_category.php?id=<?php
                                                              echo $category['category_id'];
                                                              ?>"
                                  class="btn btn-warning btn-sm">

                                  <i class="fa fa-edit"></i>

                                </a>
                                <a
                                  href="category-management.php?delete=<?php echo (int)$category['category_id']; ?>"
                                  class="btn btn-danger btn-sm"
                                  title="Delete Category"
                                  onclick="return confirm('Are you sure you want to delete this category?');">

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
                             ADD PRODUCT BUTTON
                        ================================================== -->

              <div class="text-right">

                <a
                  href="add_category.php"
                  class="btn btn-primary">

                  <i class="fa fa-plus"></i>

                  Add Category

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


  <!-- Bootstrap Progressbar -->

  <script
    src="assets/vendors/bootstrap-progressbar/bootstrap-progressbar.min.js">
  </script>


  <!-- iCheck -->

  <script
    src="assets/vendors/iCheck/icheck.min.js">
  </script>


  <!-- Custom Theme -->

  <script
    src="assets/js/custom.min.js">
  </script>


</body>

</html>