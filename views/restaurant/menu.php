<?php
require_once 'session.php';

// Biến chứa thông báo lỗi nếu có
$error_message = '';

// Lấy danh sách loại món ăn từ API
try {
    $loaiMonAnResponse = apiRequest('/loai-mon-an', 'GET');
    $loaiMonAns = isset($loaiMonAnResponse['data']) ? $loaiMonAnResponse['data'] : [];
} catch (Exception $e) {
    $error_message = 'Không thể kết nối đến máy chủ để lấy danh sách loại món ăn.';
    $loaiMonAns = [];
}

// Lấy danh sách món ăn từ API
try {
    // Xử lý filter theo loại món ăn nếu có
    $filter = [];
    if (isset($_GET['ma_loai']) && !empty($_GET['ma_loai'])) {
        $filter['ma_loai'] = $_GET['ma_loai'];
    }
    
    // Xử lý filter theo nhà hàng nếu có
    if (isset($_GET['id_nhahang']) && !empty($_GET['id_nhahang'])) {
        $filter['id_nhahang'] = $_GET['id_nhahang'];
    }
    
    $monAnResponse = apiRequest('/mon-an' . (!empty($filter) ? '?' . http_build_query($filter) : ''), 'GET');
    $monAns = isset($monAnResponse['data']) ? $monAnResponse['data'] : [];
} catch (Exception $e) {
    $error_message = 'Không thể kết nối đến máy chủ để lấy danh sách món ăn.';
    $monAns = [];
}

// Lấy danh sách nhà hàng để lọc
try {
    $nhaHangResponse = apiRequest('/nhahang', 'GET');
    $nhaHangs = isset($nhaHangResponse['data']) ? $nhaHangResponse['data'] : [];
} catch (Exception $e) {
    $nhaHangs = [];
}

// Lấy thông tin loại món ăn hiện tại nếu đang lọc
$currentLoaiMonAn = null;
if (isset($_GET['ma_loai']) && !empty($_GET['ma_loai'])) {
    foreach ($loaiMonAns as $loai) {
        if ($loai['MaLoai'] == $_GET['ma_loai']) {
            $currentLoaiMonAn = $loai;
            break;
        }
    }
}

// Lấy thông tin nhà hàng hiện tại nếu đang lọc
$currentNhaHang = null;
if (isset($_GET['id_nhahang']) && !empty($_GET['id_nhahang'])) {
    foreach ($nhaHangs as $nhaHang) {
        if ($nhaHang['ID_NhaHang'] == $_GET['id_nhahang']) {
            $currentNhaHang = $nhaHang;
            break;
        }
    }
}

// Get the user information if logged in
$user = getCurrentUser();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <!-- ========== Meta Tags ========== -->
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Thực đơn - Hệ thống đặt bàn nhà hàng">
    <!-- ======== Page title ============ -->
    <title>Thực đơn - Hệ thống đặt bàn nhà hàng</title>
    <!-- Stylesheets -->
    <link rel="shortcut icon" href="/restaurant-website/public/assets/img/logo/favicon.svg">
    <link rel="stylesheet" href="/restaurant-website/public/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="/restaurant-website/public/assets/css/font-awesome.css">
    <link rel="stylesheet" href="/restaurant-website/public/assets/css/animate.css">
    <link rel="stylesheet" href="/restaurant-website/public/assets/css/magnific-popup.css">
    <link rel="stylesheet" href="/restaurant-website/public/assets/css/meanmenu.css">
    <link rel="stylesheet" href="/restaurant-website/public/assets/css/swiper-bundle.min.css">
    <link rel="stylesheet" href="/restaurant-website/public/assets/css/nice-select.css">
    <link rel="stylesheet" href="/restaurant-website/public/assets/css/main.css">
    <link rel="stylesheet" href="/restaurant-website/public/style.css">
    <style>
        .food-menu-section {
            padding: 100px 0;
            background-color: #f8f9fa;
        }
        .filter-container {
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0px 0px 15px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }
        .filter-title {
            font-size: 18px;
            margin-bottom: 15px;
            color: #ff5b00;
            font-weight: 600;
        }
        .filter-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .filter-list li {
            margin-bottom: 10px;
        }
        .filter-list li a {
            display: block;
            padding: 8px 15px;
            color: #555;
            border-radius: 5px;
            transition: all 0.3s ease;
        }
        .filter-list li a:hover, .filter-list li a.active {
            background: rgba(255, 91, 0, 0.1);
            color: #ff5b00;
            text-decoration: none;
        }
        .food-item {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0px 0px 15px rgba(0,0,0,0.05);
            margin-bottom: 30px;
            transition: all 0.3s ease;
            overflow: hidden;
        }
        .food-item:hover {
            transform: translateY(-5px);
            box-shadow: 0px 10px 20px rgba(0,0,0,0.1);
        }
        .food-image {
            height: 200px;
            overflow: hidden;
            position: relative;
        }
        .food-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }
        .food-item:hover .food-image img {
            transform: scale(1.1);
        }
        .food-content {
            padding: 20px;
        }
        .food-title {
            font-size: 18px;
            margin-bottom: 10px;
            font-weight: 600;
        }
        .food-title a {
            color: #333;
            text-decoration: none;
        }
        .food-title a:hover {
            color: #ff5b00;
        }
        .food-price {
            font-size: 16px;
            font-weight: 700;
            color: #ff5b00;
            margin-bottom: 15px;
        }
        .food-description {
            color: #666;
            margin-bottom: 15px;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .food-buttons {
            display: flex;
            justify-content: space-between;
        }
        .empty-message {
            padding: 50px 0;
            text-align: center;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0px 0px 15px rgba(0,0,0,0.05);
        }
        .current-filter {
            background: #fff;
            padding: 15px 20px;
            border-radius: 10px;
            box-shadow: 0px 0px 15px rgba(0,0,0,0.05);
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .current-filter h3 {
            margin: 0;
            font-size: 20px;
        }
        .clear-filter {
            color: #ff5b00;
            text-decoration: none;
        }
        .clear-filter:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <!-- Preloader Start -->
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

    <!-- Header Section Start -->
    <header class="section-bg">
        <div class="header-top">
            <div class="container">
                <div class="header-top-wrapper">
                    <ul>
                        <li><span>100%</span> Secure delivery without contacting the courier</li>
                        <li><i class="fas fa-truck"></i>Track Your Order</li>
                    </ul>
                    <div class="top-right">
                        <div class="search-wrp">
                            <button><i class="far fa-search"></i></button>
                            <input placeholder="Search" aria-label="Search">
                        </div>
                        <div class="social-icon d-flex align-items-center">
                            <a href="#"><i class="fab fa-facebook-f"></i></a>
                            <a href="#"><i class="fab fa-twitter"></i></a>
                            <a href="#"><i class="fab fa-vimeo-v"></i></a>
                            <a href="#"><i class="fab fa-pinterest-p"></i></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div id="header-sticky" class="header-1">
            <div class="container">
                <div class="mega-menu-wrapper">
                    <div class="header-main">
                        <div class="logo">
                            <a href="index.php" class="header-logo">
                                <img src="/restaurant-website/public/assets/img/logo/logo.svg" alt="logo-img">
                            </a>
                        </div>
                        <div class="header-left">
                            <div class="mean__menu-wrapper d-none d-lg-block">
                                <div class="main-menu">
                                    <nav id="mobile-menu">
                                        <ul>
                                            <li class="has-dropdown">
                                                <a href="index.php">
                                                Home Page                                   
                                                </a>                                            
                                            </li>
                                            <li class="has-dropdown active">
                                                <a href="/restaurant-website/public/">
                                                Thực Đơn                                           
                                                </a>                                           
                                            </li>                                                                        
                                            <li>
                                                <a href="/restaurant-website/public/datban">Đặt Bàn</a>
                                            </li>
                                        </ul>
                                    </nav>
                                </div>
                            </div>
                        </div>
                        <div class="header-right d-flex justify-content-end align-items-center">
                            <div class="header-button">
                                <?php if (isLoggedIn()): ?>
                                    <div class="dropdown">
                                        <button class="theme-btn bg-red-2 dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                            <?php echo $user['HoVaTen']; ?>
                                        </button>
                                        <ul class="dropdown-menu" aria-labelledby="userDropdown">
                                    <?php if (isAdmin()): ?>
                                        <a class="dropdown-item" href="/restaurant-website/public/admin/dashboard"><i class="fas fa-tachometer-alt"></i> Admin Dashboard</a>
                                        <li><hr class="dropdown-divider"></li>
                                    <?php endif; ?>
                                    
                                    <!-- Các mục chung cho cả admin và user -->
                                    <li><a class="dropdown-item" href="/restaurant-website/public/profile"><i class="fas fa-user"></i> Tài khoản</a></li>
                                    <li><a class="dropdown-item" href="/restaurant-website/public/booking/my-bookings"><i class="fas fa-calendar-check"></i> Đặt bàn của tôi</a></li>
                                    
                                    <!-- Thông báo cho tất cả người dùng -->
                                    <li><a class="dropdown-item" href="/restaurant-website/public/notifications"><i class="fas fa-bell"></i> Thông báo</a></li>
                                    
                                    <!-- Các mục đơn giản không cần ID cụ thể -->
                                    <?php if (isLoggedIn() && !isAdmin()): ?>
                                        <!-- Có thể thêm các mục khác ở đây nếu cần -->
                                        <li><hr class="dropdown-divider"></li>
                                <?php endif; ?>                            
                                    <!-- Mục đăng xuất chung -->
                                    <li><a class="dropdown-item" href="/restaurant-website/public/logout"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a></li>
                                </ul>
                                    </div>
                                <?php else: ?>
                                    <a href="login.php" class="theme-btn bg-red-2">Đăng Nhập</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Page Title Banner -->
    <!-- <section class="page-title-banner bg-cover">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="page-title-content text-center">
                        <h2 class="text-white">Thực Đơn</h2>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb justify-content-center">
                                <li class="breadcrumb-item"><a href="index.php">Trang chủ</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Thực Đơn</li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </section> -->

    <!-- Food Menu Section -->
    <section class="food-menu-section">
        <div class="container">
            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger mb-4">
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($_GET['ma_loai']) || !empty($_GET['id_nhahang'])): ?>
                <div class="current-filter">
                    <h3>
                        <?php if (!empty($currentLoaiMonAn)): ?>
                            Loại món: <?php echo $currentLoaiMonAn['TenLoai']; ?>
                        <?php endif; ?>
                        
                        <?php if (!empty($currentNhaHang)): ?>
                            <?php echo !empty($currentLoaiMonAn) ? ' - ' : ''; ?>
                            Nhà hàng: <?php echo $currentNhaHang['TenNhaHang']; ?>
                        <?php endif; ?>
                    </h3>
                    <a href="menu.php" class="clear-filter"><i class="fas fa-times-circle"></i> Xóa bộ lọc</a>
                </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-lg-3">
                    <!-- Filter Sidebar -->
                    <div class="filter-container">
                        <h4 class="filter-title">Loại Món Ăn</h4>
                        <ul class="filter-list">
                            <?php if (empty($loaiMonAns)): ?>
                                <li>Không có dữ liệu</li>
                            <?php else: ?>
                                <?php foreach ($loaiMonAns as $loai): ?>
                                    <li>
                                        <a href="menu.php?ma_loai=<?php echo $loai['MaLoai']; ?><?php echo !empty($_GET['id_nhahang']) ? '&id_nhahang=' . $_GET['id_nhahang'] : ''; ?>" 
                                           class="<?php echo (isset($_GET['ma_loai']) && $_GET['ma_loai'] == $loai['MaLoai']) ? 'active' : ''; ?>">
                                            <?php echo $loai['TenLoai']; ?>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </ul>
                    </div>

                    <div class="filter-container">
                        <h4 class="filter-title">Nhà Hàng</h4>
                        <ul class="filter-list">
                            <?php if (empty($nhaHangs)): ?>
                                <li>Không có dữ liệu</li>
                            <?php else: ?>
                                <?php foreach ($nhaHangs as $nhaHang): ?>
                                    <li>
                                        <a href="menu.php?id_nhahang=<?php echo $nhaHang['ID_NhaHang']; ?><?php echo !empty($_GET['ma_loai']) ? '&ma_loai=' . $_GET['ma_loai'] : ''; ?>" 
                                           class="<?php echo (isset($_GET['id_nhahang']) && $_GET['id_nhahang'] == $nhaHang['ID_NhaHang']) ? 'active' : ''; ?>">
                                            <?php echo $nhaHang['TenNhaHang']; ?>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>

                <div class="col-lg-9">
                    <?php if (empty($monAns)): ?>
                        <div class="empty-message">
                            <i class="fas fa-utensils fa-3x mb-3 text-muted"></i>
                            <h3>Không tìm thấy món ăn nào</h3>
                            <p>Vui lòng thử lại với bộ lọc khác hoặc quay lại sau.</p>
                        </div>
                    <?php else: ?>
                        <div class="row">
                            <?php foreach ($monAns as $monAn): ?>
                                <div class="col-md-4">
                                    <div class="food-item">
                                    <div class="food-image">
                                        <?php if (!empty($monAn['Anh1'])): ?>
                                            <img src="<?php echo 'http://localhost:8000/' . $monAn['Anh1']; ?>" alt="<?php echo $monAn['TenMonAn']; ?>">
                                        <?php else: ?>
                                            <img src="/restaurant-website/public/assets/img/food/default-food.jpg" alt="Default Food Image">
                                        <?php endif; ?>
                                    </div>
                                        <div class="food-content">
                                            <h4 class="food-title">
                                                <a href="/restaurant-website/public/food/food-detail.php?id=<?php echo $monAn['ID_MonAn']; ?>"><?php echo $monAn['TenMonAn']; ?></a>
                                            </h4>
                                            <div class="food-price">
                                                <?php echo number_format($monAn['Gia'], 0, ',', '.'); ?> đ
                                            </div>
                                            <div class="food-description">
                                                <?php echo !empty($monAn['MoTa']) ? $monAn['MoTa'] : 'Không có mô tả cho món ăn này.'; ?>
                                            </div>
                                            <div class="food-buttons">
                                                <a href="/restaurant-website/public/food/food-detail.php?id=<?php echo $monAn['ID_MonAn']; ?>" class="theme-btn btn-sm">
                                                    <i class="fas fa-eye"></i> Chi tiết
                                                </a>
                                                <a href="/restaurant-website/public/datban" class="theme-btn btn-sm bg-red-2">
                                                    <i class="fas fa-utensils"></i> Đặt bàn
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer Section Start -->
    <footer class="footer-section fix section-bg">
        <div class="burger-shape">
            <img src="/restaurant-website/public/assets/img/shape/burger-shape-3.png" alt="burger-shape">
        </div>
        <div class="fry-shape">
            <img src="/restaurant-website/public/assets/img/shape/fry-shape-2.png" alt="burger-shape">
        </div>
        <div class="container">
            <div class="footer-widgets-wrapper">
                <div class="row">
                    <div class="col-xl-3 col-sm-6 col-md-6 col-lg-3 wow fadeInUp" data-wow-delay=".2s">
                        <div class="single-footer-widget">
                            <div class="widget-head">
                                <a href="index.php">
                                    <img src="/restaurant-website/public/assets/img/logo/logo.svg" alt="logo-img">
                                </a>
                            </div>
                            <div class="footer-content">
                                <p>
                                    Hệ thống đặt bàn nhà hàng trực tuyến <br>
                                    tiện lợi và nhanh chóng.
                                </p>
                                <div class="social-icon d-flex align-items-center">
                                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                                    <a href="#"><i class="fab fa-twitter"></i></a>
                                    <a href="#"><i class="fab fa-vimeo-v"></i></a>
                                    <a href="#"><i class="fab fa-pinterest-p"></i></a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Thêm các phần footer khác nếu cần -->
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <div class="container">
                <div class="footer-bottom-wrapper d-flex align-items-center justify-content-between">
                    <p class="wow fadeInLeft" data-wow-delay=".3s">
                        © Copyright <span class="theme-color-3">2025</span> <a href="index.php">Restaurant Booking</a>. All Rights Reserved.
                    </p>
                </div>
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
    <script src="/restaurant-website/public/assets/js/viewport.jquery.js"></script>
    <script src="/restaurant-website/public/assets/js/bootstrap.bundle.min.js"></script>
    <script src="/restaurant-website/public/assets/js/jquery.nice-select.min.js"></script>
    <script src="/restaurant-website/public/assets/js/jquery.waypoints.js"></script>
    <script src="/restaurant-website/public/assets/js/jquery.counterup.min.js"></script>
    <script src="/restaurant-website/public/assets/js/swiper-bundle.min.js"></script>
    <script src="/restaurant-website/public/assets/js/jquery.meanmenu.min.js"></script>
    <script src="/restaurant-website/public/assets/js/jquery.magnific-popup.min.js"></script>
    <script src="/restaurant-website/public/assets/js/wow.min.js"></script>
    <script src="/restaurant-website/public/assets/js/main.js"></script>
</body>
</html>