<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/restaurant-website/public/session.php';

$uri = $_SERVER['REQUEST_URI'];
$base_path = '/restaurant-website/public';
$path = str_replace($base_path, '', parse_url($uri, PHP_URL_PATH));
// Kiểm tra quyền admin
checkAdminAccess();

// Lấy tổng số người dùng
$userStats = apiRequest('/users', 'GET');
$totalUsers = count($userStats['data'] ?? []);

// Lấy tổng số nhà hàng
$restaurantStats = apiRequest('/nhahang', 'GET');
$totalRestaurants = count($restaurantStats['data'] ?? []);

// Lấy tổng số đặt bàn
$bookingStats = apiRequest('/dat-ban', 'GET');
$totalBookings = count($bookingStats['data'] ?? []);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <!-- ========== Meta Tags ========== -->
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Trang quản trị - Hệ thống đặt bàn nhà hàng">
    <!-- ======== Page title ============ -->
    <title>Trang quản trị - Hệ thống đặt bàn nhà hàng</title>
    <!-- Stylesheets -->
    <link rel="shortcut icon" href="/restaurant-website/public/assets/img/logo/favicon.svg">
    <link rel="stylesheet" href="/restaurant-website/public/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="/restaurant-website/public/assets/css/font-awesome.css">
    <link rel="stylesheet" href="/restaurant-website/public/assets/css/animate.css">
    <link rel="stylesheet" href="/restaurant-website/public/assets/css/main.css">
    <link rel="stylesheet" href="/restaurant-website/public/style.css">
    <style>
        .admin-section {
            padding: 80px 0;
            background-color: #f8f9fa;
        }
        .admin-title {
            margin-bottom: 40px;
        }
        .dashboard-card {
            background: #fff;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }
        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
        }
        .dashboard-card h3 {
            font-size: 22px;
            color: #555;
            margin-bottom: 15px;
        }
        .dashboard-card .count {
            font-size: 42px;
            font-weight: 700;
            color: #ff5b00;
        }
        .dashboard-card .icon {
            font-size: 48px;
            color: rgba(255, 91, 0, 0.2);
            position: absolute;
            top: 20px;
            right: 20px;
        }
        .admin-menu {
            background: #fff;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }
        .admin-menu ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .admin-menu ul li {
            margin-bottom: 10px;
        }
        .admin-menu ul li a {
            display: block;
            padding: 12px 15px;
            color: #555;
            border-radius: 5px;
            transition: all 0.3s ease;
        }
        .admin-menu ul li a:hover, .admin-menu ul li a.active {
            background: rgba(255, 91, 0, 0.1);
            color: #ff5b00;
            text-decoration: none;
        }
        .admin-menu ul li a i {
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <!-- Preloader -->
    <div id="preloader" class="preloader">
        <div class="animation-preloader">
            <div class="spinner"></div>
            <div class="txt-loading">
                <span data-text-preloader="F" class="letters-loading">F</span>
                <span data-text-preloader="O" class="letters-loading">O</span>
                <span data-text-preloader="O" class="letters-loading">O</span>
                <span data-text-preloader="D" class="letters-loading">D</span>
                <span data-text-preloader="K" class="letters-loading">K</span>
                <span data-text-preloader="I" class="letters-loading">I</span>
                <span data-text-preloader="N" class="letters-loading">N</span>
                <span data-text-preloader="G" class="letters-loading">G</span>
            </div>
            <p class="text-center">Loading</p>
        </div>
    </div>

    <!-- Header -->
    <header class="section-bg">
        <div class="header-top">
            <div class="container">
                <div class="header-top-wrapper">
                    <ul>
                        <li><span>Administrator</span> Dashboard</li>
                    </ul>
                </div>
            </div>
        </div>
        <div id="header-sticky" class="header-1">
            <div class="container">
                <div class="mega-menu-wrapper">
                    <div class="header-main">
                        <div class="logo">
                            <a href="/restaurant-website/public/index.php" class="header-logo">
                                <img src="/restaurant-website/public/assets/img/logo/logo.svg" alt="logo-img">
                            </a>
                        </div>
                        <div class="header-right d-flex justify-content-end align-items-center">
                            <div class="header-button">
                                <a href="/restaurant-website/public/logout.php" class="theme-btn bg-red-2">Đăng xuất</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Admin Section -->
    <section class="admin-section">
        <div class="container">
            <div class="admin-title">
                <h2 class="text-center">Quản trị hệ thống</h2>
                <p class="text-center">Chào mừng, <?php echo getCurrentUser()['HoVaTen']; ?></p>
            </div>
            
            <div class="row">
                <div class="col-lg-3">
                <div class="admin-menu">
                    <h4>Menu quản trị</h4>
                        <ul>
                            <li><a href="/restaurant-website/public/admin/dashboard" class="<?php echo $path == '/admin/dashboard' ? 'active' : ''; ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                            <li><a href="/restaurant-website/public/admin/users" class="<?php echo $path == '/admin/users' ? 'active' : ''; ?>"><i class="fas fa-users"></i> Quản lý người dùng</a></li>
                            <li><a href="/restaurant-website/public/admin/restaurants" class="<?php echo $path == '/admin/restaurants' ? 'active' : ''; ?>"><i class="fas fa-utensils"></i> Quản lý nhà hàng</a></li>
                            <li><a href="/restaurant-website/public/admin/bookings" class="<?php echo $path == '/admin/bookings' ? 'active' : ''; ?>"><i class="fas fa-calendar-check"></i> Quản lý đặt bàn</a></li>
                            <li><a href="/restaurant-website/public/admin/food" class="<?php echo $path == '/admin/food' ? 'active' : ''; ?>"><i class="fas fa-hamburger"></i> Quản lý món ăn</a></li>
                            <li><a href="/restaurant-website/public/admin/categories" class="<?php echo $path == '/admin/categories' ? 'active' : ''; ?>"><i class="fas fa-list"></i> Quản lý danh mục</a></li>
                            <li><a href="/restaurant-website/public/admin/reviews" class="<?php echo $path == '/admin/reviews' ? 'active' : ''; ?>"><i class="fas fa-star"></i> Quản lý đánh giá</a></li>
                            <li><a href="/restaurant-website/public/admin/notifications" class="<?php echo $path == '/admin/notifications' ? 'active' : ''; ?>"><i class="fas fa-bell"></i> Quản lý thông báo</a></li>
                            <li><a href="/restaurant-website/public/admin/payment" class="<?php echo $path == '/admin/payment' ? 'active' : ''; ?>"><i class="fas fa-credit-card"></i> Quản lý thanh toán</a></li>
                        </ul>
                </div>
                </div>
                
                <div class="col-lg-9">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="dashboard-card position-relative">
                                <h3>Tổng người dùng</h3>
                                <div class="count"><?php echo $totalUsers; ?></div>
                                <div class="icon">
                                    <i class="fas fa-users"></i>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="dashboard-card position-relative">
                                <h3>Tổng nhà hàng</h3>
                                <div class="count"><?php echo $totalRestaurants; ?></div>
                                <div class="icon">
                                    <i class="fas fa-utensils"></i>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="dashboard-card position-relative">
                                <h3>Tổng lượt đặt bàn</h3>
                                <div class="count"><?php echo $totalBookings; ?></div>
                                <div class="icon">
                                    <i class="fas fa-calendar-check"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-12">
                            <div class="dashboard-card">
                                <h3>Hoạt động gần đây</h3>
                                <p>Hệ thống đang thu thập dữ liệu...</p>
                                <!-- Thêm dữ liệu hoạt động gần đây ở đây -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer-section fix section-bg">
        <div class="container">
            <div class="footer-bottom-wrapper d-flex align-items-center justify-content-between">
                <p>
                    © Copyright 2025 <a href="index.php">Restaurant Booking</a>. All Rights Reserved.
                </p>
            </div>
        </div>
    </footer>

    <!-- Back to top area start here -->
    <div class="scroll-up">
        <svg class="scroll-circle svg-content" width="100%" height="100%" viewBox="-1 -1 102 102">
            <path d="M50,1 a49,49 0 0,1 0,98 a49,49 0 0,1 0,-98"/>
        </svg>
    </div>

    <!-- All JS Plugins -->
    <script src="/restaurant-website/public/assets/js/jquery-3.7.1.min.js"></script>
    <script src="/restaurant-website/public/assets/js/bootstrap.bundle.min.js"></script>
    <script src="/restaurant-website/public/assets/js/swiper-bundle.min.js"></script>
    <script src="/restaurant-website/public/assets/js/wow.min.js"></script>
    <script src="/restaurant-website/public/assets/js/main.js"></script>
</body>
</html>