<!DOCTYPE html>
<html lang="en">
<head>
    <!-- ========== Meta Tags ========== -->
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="author" content="modinatheme">
    <meta name="description" content="Restaurant Booking System">
    <!-- ======== Page title ============ -->
    <title>Restaurant Booking System</title>
    <!--<< CSS Files >>-->
    <link rel="stylesheet" href="/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="/assets/css/font-awesome.css">
    <link rel="stylesheet" href="/assets/css/animate.css">
    <link rel="stylesheet" href="/assets/css/magnific-popup.css">
    <link rel="stylesheet" href="/assets/css/meanmenu.css">
    <link rel="stylesheet" href="/assets/css/swiper-bundle.min.css">
    <link rel="stylesheet" href="/assets/css/nice-select.css">
    <link rel="stylesheet" href="/assets/css/main.css">
</head>
<style>
    .section-bg {
        background-color: #f8f9fa;
        padding: 15px 0;
        border-bottom: 1px solid #ddd;
    }

    .header-wrapper {
        width: 100%;
    }

    .header-logo {
        display: flex;
        align-items: center;
    }

    .logo-img {
        width: 40px;
        margin-right: 10px;
    }

    .logo-text {
        font-size: 24px;
        font-weight: 700;
        color: #333;
    }

    .main-menu ul {
        list-style: none;
        padding: 0;
        margin: 0;
        display: flex;
        align-items: center;
    }

    .main-menu ul li {
        margin: 0 20px;
    }

    .main-menu ul li a {
        text-decoration: none;
        color: #333;
        font-size: 16px;
        font-weight: 500;
        text-transform: uppercase;
        transition: color 0.3s ease;
    }

    .main-menu ul li.active a,
    .main-menu ul li a:hover {
        color: #ff5b00;
    }

    .header-right {
        display: flex;
        align-items: center;
    }

    .cart-icon {
        font-size: 20px;
        color: #333;
        text-decoration: none;
    }

    .admin-btn {
        background-color: #ff5b00;
        color: #fff;
        padding: 8px 15px;
        border: none;
        border-radius: 5px;
        font-size: 14px;
        font-weight: 500;
        text-transform: uppercase;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .admin-btn i {
        font-size: 12px;
    }

    /* Dropdown styles */
    .dropdown-menu {
        min-width: 200px;
        border-radius: 5px;
    }

    .dropdown-item {
        font-size: 14px;
        padding: 8px 15px;
    }

    .dropdown-item i {
        margin-right: 10px;
    }

    .dropdown-divider {
        margin: 5px 0;
    }

    /* Mobile menu adjustments */
    @media (max-width: 991px) {
        .mean__menu-wrapper {
            display: none !important;
        }
        .mobile-menu-toggle {
            display: block !important;
        }
    }
</style>
<body>
    <!-- Preloader Start -->
    <div id="preloader" class="preloader">
        <!-- Nội dung preloader từ template -->
    </div>
    
    <!-- Offcanvas Area Start -->
    <div class="fix-area">
        <!-- Nội dung offcanvas từ template -->
    </div>
    <div class="offcanvas__overlay"></div>
    
    <!-- Header Section Start -->
    <header class="section-bg">
        <div class="container">
            <div class="header-wrapper d-flex align-items-center justify-content-between">
                <!-- Logo -->
                <div class="header-logo">
                    <a href="/restaurant-website/public/index.php">
                        <img src="assets/img/logo/favicon.svg" alt="Logo" class="logo-img">
                        <span class="logo-text">FOODKING</span>
                    </a>
                </div>

                <!-- Menu -->
                <div class="header-left">
                    <div class="mean__menu-wrapper d-none d-lg-block">
                        <div class="main-menu">
                            <nav id="mobile-menu">
                                <ul>
                                    <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                                        <a href="/restaurant-website/public/index.php">HOME PAGE</a>
                                    </li>
                                    <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'menu.php' ? 'active' : ''; ?>">
                                        <a href="/restaurant-website/public/menu">THỰC ĐƠN</a>
                                    </li>
                                    <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'datban.php' ? 'active' : ''; ?>">
                                        <a href="/restaurant-website/public/datban">ĐẶT BÀN</a>
                                    </li>
                                </ul>
                            </nav>
                        </div>
                    </div>
                    <!-- Mobile Menu Toggle -->
                    <div class="mobile-menu-toggle d-lg-none">
                        <button class="meanmenu-reveal" style="display: none;">
                            <span></span><span></span><span></span>
                        </button>
                    </div>
                </div>

                <!-- Admin and Cart -->
                <div class="header-right d-flex align-items-center">
                    <!-- <a href="/restaurant-website/public/shop-cart" class="cart-icon me-3"><i class="fas fa-shopping-cart"></i></a> -->
                    <div class="admin-dropdown">
                        <?php if (isLoggedIn()): ?>
                            <div class="dropdown">
                                <button class="admin-btn dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                    <?php echo $user['HoVaTen']; ?> <i class="fas fa-chevron-down"></i>
                                </button>
                                <ul class="dropdown-menu" aria-labelledby="userDropdown">
                                    <?php if (isAdmin()): ?>
                                        <li><a class="dropdown-item" href="/restaurant-website/public/admin/dashboard"><i class="fas fa-tachometer-alt"></i> Admin Dashboard</a></li>
                                        <li><hr class="dropdown-divider"></li>
                                    <?php endif; ?>
                                    <li><a class="dropdown-item" href="/restaurant-website/public/profile"><i class="fas fa-user"></i> Tài khoản</a></li>
                                    <li><a class="dropdown-item" href="/restaurant-website/public/booking/my-bookings"><i class="fas fa-calendar-check"></i> Đặt bàn của tôi</a></li>
                                    <li><a class="dropdown-item" href="/restaurant-website/public/notifications"><i class="fas fa-bell"></i> Thông báo</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="/restaurant-website/public/logout"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a></li>
                                </ul>
                            </div>
                        <?php else: ?>
                            <a href="/restaurant-website/public/login" class="admin-btn">ĐĂNG NHẬP</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </header>
</body>
</html>