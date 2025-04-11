<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/restaurant-website/public/session.php';

// Kiểm tra quyền admin
checkAdminAccess();

// Lấy danh sách người dùng để gửi thông báo
$usersResponse = apiRequest('/users', 'GET');
$users = $usersResponse['data'] ?? [];

// Lấy danh sách tất cả thông báo
$notificationsResponse = apiRequest('/thong-bao/all', 'GET');
$notifications = $notificationsResponse['data'] ?? [];

// Xử lý gửi thông báo mới
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    $data = [
        'ID_USER' => $_POST['user_id'] ?? null,
        'Ten' => $_POST['title'] ?? '',
        'MoTa' => $_POST['description'] ?? '',
        'NoiDung' => $_POST['content'] ?? '',
        'TheLoai' => $_POST['type'] ?? 'general'
    ];
    
    $response = apiRequest('/thong-bao/create', 'POST', $data);
    
    if ($response['success'] ?? false) {
        $successMsg = 'Đã gửi thông báo thành công';
        
        // Refresh danh sách thông báo
        $notificationsResponse = apiRequest('/thong-bao/all', 'GET');
        $notifications = $notificationsResponse['data'] ?? [];
    } else {
        $errorMsg = $response['message'] ?? 'Đã xảy ra lỗi khi gửi thông báo';
    }
}

// Xử lý xóa thông báo
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $notificationId = $_GET['id'];
    $response = apiRequest('/thong-bao/' . $notificationId, 'DELETE');
    
    if ($response['success'] ?? false) {
        $successMsg = 'Đã xóa thông báo thành công';
        
        // Refresh danh sách thông báo
        $notificationsResponse = apiRequest('/thong-bao/all', 'GET');
        $notifications = $notificationsResponse['data'] ?? [];
    } else {
        $errorMsg = $response['message'] ?? 'Đã xảy ra lỗi khi xóa thông báo';
    }
}

// Định nghĩa đường dẫn hiện tại cho menu active
$uri = $_SERVER['REQUEST_URI'];
$base_path = '/restaurant-website/public';
$path = str_replace($base_path, '', parse_url($uri, PHP_URL_PATH));
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <!-- ========== Meta Tags ========== -->
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Quản lý thông báo - Hệ thống đặt bàn nhà hàng">
    <!-- ======== Page title ============ -->
    <title>Quản lý thông báo - Hệ thống đặt bàn nhà hàng</title>
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
        .content-card {
            background: #fff;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
        }
        .notification-item {
            padding: 15px;
            margin-bottom: 15px;
            border: 1px solid #eee;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        .notification-item:hover {
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            transform: translateY(-2px);
        }
        .notification-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        .notification-title {
            font-weight: 600;
            font-size: 18px;
        }
        .notification-meta {
            color: #777;
            font-size: 14px;
        }
        .notification-content {
            margin-bottom: 15px;
            color: #555;
        }
        .notification-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .notification-type {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 12px;
            background-color: #f8f9fa;
            color: #495057;
        }
        .notification-actions {
            text-align: right;
        }
        .table-action a {
            margin-right: 5px;
        }
        .alert {
            margin-bottom: 20px;
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
                            <a href="/restaurant-website/public/" class="header-logo">
                                <img src="/restaurant-website/public/assets/img/logo/logo.svg" alt="logo-img">
                            </a>
                        </div>
                        <div class="header-right d-flex justify-content-end align-items-center">
                            <div class="header-button">
                                <a href="/restaurant-website/public/logout" class="theme-btn bg-red-2">Đăng xuất</a>
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
                <h2 class="text-center">Quản lý thông báo</h2>
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
                    <?php if (isset($successMsg)): ?>
                        <div class="alert alert-success">
                            <?php echo $successMsg; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($errorMsg)): ?>
                        <div class="alert alert-danger">
                            <?php echo $errorMsg; ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Form tạo thông báo mới -->
                    <div class="content-card">
                        <h3>Gửi thông báo mới</h3>
                        <form method="POST" action="">
                            <input type="hidden" name="action" value="create">
                            
                            <div class="mb-3">
                                <label for="user_id" class="form-label">Người nhận <span class="text-danger">*</span></label>
                                <select class="form-control" id="user_id" name="user_id" required>
                                    <option value="">-- Chọn người nhận --</option>
                                    <?php foreach ($users as $user): ?>
                                        <option value="<?php echo $user['ID_USER']; ?>">
                                            <?php echo htmlspecialchars($user['HoVaTen'] . ' (' . $user['Email'] . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="title" class="form-label">Tiêu đề <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="title" name="title" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="description" class="form-label">Mô tả ngắn</label>
                                <input type="text" class="form-control" id="description" name="description">
                            </div>
                            
                            <div class="mb-3">
                                <label for="content" class="form-label">Nội dung <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="content" name="content" rows="4" required></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label for="type" class="form-label">Loại thông báo</label>
                                <select class="form-control" id="type" name="type">
                                    <option value="general">Thông báo chung</option>
                                    <option value="booking_confirmation">Xác nhận đặt bàn</option>
                                    <option value="booking_reminder">Nhắc nhở đặt bàn</option>
                                    <option value="payment_confirmation">Xác nhận thanh toán</option>
                                    <option value="promotion">Khuyến mãi</option>
                                </select>
                            </div>
                            
                            <div class="text-end">
                                <button type="submit" class="theme-btn">
                                    <i class="fas fa-paper-plane"></i> Gửi thông báo
                                </button>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Danh sách thông báo -->
                    <div class="content-card">
                        <h3>Danh sách thông báo đã gửi</h3>
                        
                        <?php if (empty($notifications)): ?>
                            <div class="alert alert-info">
                                Chưa có thông báo nào được gửi.
                            </div>
                        <?php else: ?>
                            <?php foreach ($notifications as $notification): ?>
                                <div class="notification-item">
                                    <div class="notification-header">
                                        <div class="notification-title"><?php echo htmlspecialchars($notification['Ten']); ?></div>
                                        <div class="notification-meta">
                                            <?php echo date('d/m/Y H:i', strtotime($notification['NgayTao'])); ?>
                                        </div>
                                    </div>
                                    
                                    <div class="notification-content">
                                        <?php echo htmlspecialchars($notification['NoiDung']); ?>
                                    </div>
                                    
                                    <div class="notification-footer">
                                        <div>
                                            <span class="notification-type"><?php echo htmlspecialchars($notification['TheLoai']); ?></span>
                                            <?php if ($notification['DaDoc']): ?>
                                                <span class="badge bg-success">Đã đọc</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning text-dark">Chưa đọc</span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="notification-actions">
                                            <a href="/restaurant-website/public/admin/notifications?action=delete&id=<?php echo $notification['ID_ThongBao']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Bạn có chắc chắn muốn xóa thông báo này?');">
                                                <i class="fas fa-trash"></i> Xóa
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
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
                    © Copyright 2025 <a href="/restaurant-website/public/">Restaurant Booking</a>. All Rights Reserved.
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