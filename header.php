<?php

// ======================================================
// START SESSION
// ======================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


// ======================================================
// LOGIN STATUS
// ======================================================

$is_logged_in = isset($_SESSION['login'])
    && $_SESSION['login'] === true;


// ======================================================
// USER ROLE
// ======================================================

$user_role = '';

if ($is_logged_in && isset($_SESSION['role'])) {

    $user_role = strtolower(
        trim($_SESSION['role'])
    );
}

?>



<header class="header_section">

    <div class="container">

        <nav class="navbar navbar-expand-lg custom_nav-container">


            <!-- ==================================================
                 LOGO
            ================================================== -->

            <a
                class="navbar-brand"
                href="index.php">

                <span>
                    Fior
                </span>

            </a>



            <!-- ==================================================
                 MOBILE MENU BUTTON
            ================================================== -->

            <button
                class="navbar-toggler"
                type="button"
                data-toggle="collapse"
                data-target="#navbarSupportedContent"
                aria-controls="navbarSupportedContent"
                aria-expanded="false"
                aria-label="Toggle navigation">

                <span class="navbar-toggler-icon"></span>

            </button>



            <!-- ==================================================
                 NAVIGATION
            ================================================== -->

            <div
                class="collapse navbar-collapse"
                id="navbarSupportedContent">


                <!-- ==================================================
                     MAIN MENU
                ================================================== -->

                <div
                    class="d-flex mx-auto flex-column flex-lg-row align-items-center">

                    <ul class="navbar-nav">


                        <!-- HOME -->

                        <li class="nav-item active">

                            <a
                                class="nav-link"
                                href="index.php">

                                Home

                                <span class="sr-only">
                                    (current)
                                </span>

                            </a>

                        </li>



                        <!-- ABOUT -->

                        <li class="nav-item">

                            <a
                                class="nav-link"
                                href="about.php">

                                About

                            </a>

                        </li>



                        <!-- GALLERY -->

                        <li class="nav-item">

                            <a
                                class="nav-link"
                                href="gallery.php">

                                Gallery

                            </a>

                        </li>



                        <!-- CONTACT -->

                        <li class="nav-item">

                            <a
                                class="nav-link"
                                href="contact.php">

                                Contact us

                            </a>

                        </li>


                    </ul>

                </div>



                <!-- ==================================================
                     RIGHT SIDE
                ================================================== -->

                <div class="quote_btn-container">


                    <!-- ==================================================
                         PROFILE DROPDOWN
                    ================================================== -->

                    <div class="dropdown">


                        <a
                            href="#"
                            class="dropdown-toggle"
                            data-toggle="dropdown"
                            aria-haspopup="true"
                            aria-expanded="false">

                            <i class="fa fa-user"></i>

                            My Profile

                        </a>



                        <!-- ==================================================
                             DROPDOWN MENU
                        ================================================== -->

                        <div
                            class="dropdown-menu dropdown-menu-right">


                            <?php

                            // ==================================================
                            // NOT LOGGED IN
                            // ==================================================

                            if (!$is_logged_in) {

                            ?>


                                <!-- LOGIN -->

                                <a
                                    href="login.php"
                                    class="dropdown-item">

                                    <i class="fa fa-sign-in"></i>

                                    Login

                                </a>


                            <?php

                            }


                            // ==================================================
                            // LOGGED IN USER / ADMIN
                            // ==================================================

                            if ($is_logged_in) {

                            ?>


                                <!-- MY ACCOUNT -->

                                <a
                                    href="myaccount.php"
                                    class="dropdown-item">

                                    <i class="fa fa-user"></i>

                                    My Account

                                </a>



                                <?php

                                // ==================================================
                                // DASHBOARD ONLY FOR ADMIN
                                // ==================================================

                                if ($user_role === 'admin') {

                                ?>


                                    <a
                                        href="dashboard/index3.php"
                                        class="dropdown-item">

                                        <i class="fa fa-dashboard"></i>

                                        Dashboard

                                    </a>


                                <?php

                                }

                                ?>



                                <!-- WISHLIST -->

                                <a
                                    href="wishlist.php"
                                    class="dropdown-item">

                                    <i class="fa fa-heart"></i>

                                    Wishlist

                                </a>



                                <!-- MY CART -->

                                <a
                                    href="cart.php"
                                    class="dropdown-item">

                                    <i class="fa fa-shopping-cart"></i>

                                    My Cart

                                </a>



                                <!-- DIVIDER -->

                                <div class="dropdown-divider"></div>



                                <!-- LOGOUT -->

                                <a
                                    href="logout.php"
                                    class="dropdown-item">

                                    <i class="fa fa-sign-out"></i>

                                    Log Out

                                </a>


                            <?php

                            }

                            ?>


                        </div>

                    </div>



                    <!-- ==================================================
                         CART ICON
                    ================================================== -->

                    <a href="cart.php">

                        <img
                            src="images/cart.png"
                            alt="Cart">

                    </a>



                    <!-- ==================================================
                         SEARCH
                    ================================================== -->

                    <form class="form-inline">

                        <button
                            class="btn my-2 my-sm-0 nav_search-btn"
                            type="submit">

                        </button>

                    </form>


                </div>


            </div>


        </nav>

    </div>

</header>