<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/restaurant-website/public/session.php';
// Kiểm tra người dùng đã đăng nhập và là admin
checkUserLoggedIn();
if (!isAdmin()) {
    header('Location: /restaurant-website/public/');
    exit;
}

$user = getCurrentUser();

// Lấy thống kê từ API
// Lấy tổng số người dùng
$usersResponse = apiRequest('/users', 'GET');
$totalUsers = count($usersResponse['data'] ?? []);

// Lấy số lượng nhà hàng
$restaurantsResponse = apiRequest('/nhahang', 'GET');
$totalRestaurants = count($restaurantsResponse['data'] ?? []);

// Lấy danh sách đặt bàn
$bookingsResponse = apiRequest('/dat-ban', 'GET');
$bookings = $bookingsResponse['data'] ?? [];

// Tính toán thống kê
$totalBookings = count($bookings);
$pendingBookings = 0;
$confirmedBookings = 0;
$cancelledBookings = 0;
$revenue = 0;

foreach ($bookings as $booking) {
    if ($booking['TrangThai'] == 0) {
        $pendingBookings++;
    } elseif ($booking['TrangThai'] == 1) {
        $confirmedBookings++;
    } elseif ($booking['TrangThai'] == 2) {
        $cancelledBookings++;
    }
    
    // Tính doanh thu từ các đơn đã thanh toán
    if (isset($booking['thanhToan']) && $booking['thanhToan']['TrangThaiThanhToan'] == 1) {
        $revenue += $booking['thanhToan']['SoLuong'];
    }
}

// Lấy các đơn đặt bàn gần đây
$recentBookings = array_slice($bookings, 0, 5);

// Lấy đánh giá gần đây
$reviewsResponse = apiRequest('/danh-gia', 'GET');
$recentReviews = array_slice($reviewsResponse['data'] ?? [], 0, 5);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <!-- Meta Tags -->
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Dashboard - Nhà hàng">
    <!-- Page title -->
    <title>Dashboard Admin - Nhà hàng</title>
    <!-- Favicon -->
    <link rel="shortcut icon" href="/restaurant-website/public/assets/img/logo/favicon.svg">
    <!-- CSS files -->
    <link rel="stylesheet" href="/restaurant-website/public/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="/restaurant-website/public/assets/css/font-awesome.css">
    <link rel="stylesheet" href="/restaurant-website/public/assets/css/animate.css">
    <link rel="stylesheet" href="/restaurant-website/public/assets/css/magnific-popup.css">
    <link rel="stylesheet" href="/restaurant-website/public/assets/css/meanmenu.css">
    <link rel="stylesheet" href="/restaurant-website/public/assets/css/swiper-bundle.min.css">
    <link rel="stylesheet" href="/restaurant-website/public/assets/css/nice-select.css">
    <link rel="stylesheet" href="/restaurant-website/public/assets/css/main.css">
    <!-- Custom styles -->
    <style>
        .dashboard-section {
            padding: 100px 0;
            background-color: #f8f9fa;
        }
        .dashboard-wrapper {
            background-color: #fff;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
        }
        .dashboard-title {
            margin-bottom: 40px;
        }
        .dashboard-title h2 {
            font-size: 36px;
            margin-bottom: 15px;
        }
        .dashboard-card {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            padding: 25px;
            margin-bottom: 30px;
            transition: all 0.3s ease;
            text-align: center;
            height: 100%;
        }
        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.12);
        }
        .dashboard-icon {
            font-size: 40px;
            margin-bottom: 15px;
        }
        .dashboard-card h5 {
            color: #666;
            font-size: 16px;
            margin-bottom: 12px;
        }
        .dashboard-card h3 {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 0;
        }
        .border-primary {
            border-top: 4px solid #007bff;
        }
        .border-success {
            border-top: 4px solid #28a745;
        }
        .border-info {
            border-top: 4px solid #17a2b8;
        }
        .border-warning {
            border-top: 4px solid #ffc107;
        }
        .border-danger {
            border-top: 4px solid #dc3545;
        }
        .text-primary {
            color: #007bff !important;
        }
        .text-success {
            color: #28a745 !important;
        }
        .text-info {
            color: #17a2b8 !important;
        }
        .text-warning {
            color: #ffc107 !important;
        }
        .text-danger {
            color: #dc3545 !important;
        }
        .recent-card {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            margin-bottom: 30px;
        }
        .recent-card-header {
            padding: 15px 20px;
            border-bottom: 1px solid #eee;
        }
        .recent-card-header h5 {
            margin: 0;
            font-size: 18px;
        }
        .recent-card-body {
            padding: 20px;
            min-height: 200px;
        }
        .recent-card-footer {
            padding: 15px 20px;
            border-top: 1px solid #eee;
            text-align: right;
        }
        .recent-list-item {
            padding: 12px 0;
            border-bottom: 1px solid #f5f5f5;
        }
        .recent-list-item:last-child {
            border-bottom: none;
        }
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-pending {
            background-color: #fff8e6;
            color: #f2b01e;
        }
        .status-confirmed {
            background-color: #e1f7e7;
            color: #28a745;
        }
        .status-cancelled {
            background-color: #ffe6e6;
            color: #dc3545;
        }
        .rating {
            color: #ffc107;
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
    <?php include __DIR__ . '/../../app/includes/header.php'; ?>

    <!-- Dashboard Section -->
    <section class="dashboard-section">
        <div class="container">
            <div class="dashboard-wrapper">
                <div class="dashboard-title">
                    <h2>Dashboard</h2>
                    <p>Tổng quan về hệ thống đặt bàn nhà hàng</p>
                </div>

                <div class="row">
                    <!-- Statistics Cards -->
                    <div class="col-md-4 mb-4">
                        <a href="/restaurant-website/public/admin/users" class="text-decoration-none">
                            <div class="dashboard-card border-primary">
                                <i class="fas fa-users dashboard-icon text-primary"></i>
                                <h5>Tổng người dùng</h5>
                                <h3 class="text-primary"><?php echo $totalUsers; ?></h3>
                            </div>
                        </a>
                    </div>
                    
                    <div class="col-md-4 mb-4">
                        <a href="/restaurant-website/public/admin/restaurants" class="text-decoration-none">
                            <div class="dashboard-card border-success">
                                <i class="fas fa-utensils dashboard-icon text-success"></i>
                                <h5>Nhà hàng</h5>
                                <h3 class="text-success"><?php echo $totalRestaurants; ?></h3>
                            </div>
                        </a>
                    </div>
                    
                    <div class="col-md-4 mb-4">
                        <a href="/restaurant-website/public/admin/bookings" class="text-decoration-none">
                            <div class="dashboard-card border-info">
                                <i class="fas fa-calendar-alt dashboard-icon text-info"></i>
                                <h5>Tổng đơn đặt bàn</h5>
                                <h3 class="text-info"><?php echo $totalBookings; ?></h3>
                            </div>
                        </a>
                    </div>
                    
                    <div class="col-md-4 mb-4">
                        <a href="/restaurant-website/public/admin/bookings" class="text-decoration-none">
                            <div class="dashboard-card border-warning">
                                <i class="fas fa-clipboard-list dashboard-icon text-warning"></i>
                                <h5>Đơn chờ xác nhận</h5>
                                <h3 class="text-warning"><?php echo $pendingBookings; ?></h3>
                            </div>
                        </a>
                    </div>
                    
                    <div class="col-md-4 mb-4">
                        <a href="/restaurant-website/public/admin/bookings" class="text-decoration-none">
                            <div class="dashboard-card border-success">
                                <i class="fas fa-clipboard-check dashboard-icon text-success"></i>
                                <h5>Đơn đã xác nhận</h5>
                                <h3 class="text-success"><?php echo $confirmedBookings; ?></h3>
                            </div>
                        </a>
                    </div>
                    
                    <div class="col-md-4 mb-4">
                        <a href="/restaurant-website/public/admin/bookings" class="text-decoration-none">
                            <div class="dashboard-card border-danger">
                                <i class="fas fa-money-bill-wave dashboard-icon text-danger"></i>
                                <h5>Doanh thu</h5>
                                <h3 class="text-danger"><?php echo number_format($revenue, 0, ',', '.'); ?>đ</h3>
                            </div>
                        </a>
                    </div>
                </div>

                <div class="row mt-4">
                    <!-- Recent Bookings -->
                    <div class="col-md-6">
                        <div class="recent-card">
                            <div class="recent-card-header">
                                <h5>Đơn đặt bàn gần đây</h5>
                            </div>
                            <div class="recent-card-body">
                                <?php if (empty($recentBookings)): ?>
                                    <p class="text-center text-muted py-3">Không có đơn đặt bàn nào gần đây.</p>
                                <?php else: ?>
                                    <?php foreach ($recentBookings as $booking): ?>
                                        <div class="recent-list-item">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <strong>Đơn #<?php echo $booking['ID_ThongTinDatBan']; ?></strong>
                                                    <div>
                                                        <small><?php echo date('d/m/Y H:i', strtotime($booking['ThoiGianDatBan'])); ?></small>
                                                    </div>
                                                </div>
                                                <div>
                                                    <?php
                                                    $statusClass = '';
                                                    $statusText = '';
                                                    switch ($booking['TrangThai']) {
                                                        case 0:
                                                            $statusClass = 'status-pending';
                                                            $statusText = 'Chờ xác nhận';
                                                            break;
                                                        case 1:
                                                            $statusClass = 'status-confirmed';
                                                            $statusText = 'Đã xác nhận';
                                                            break;
                                                        case 2:
                                                            $statusClass = 'status-cancelled';
                                                            $statusText = 'Đã hủy';
                                                            break;
                                                    }
                                                    ?>
                                                    <span class="status-badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                            <div class="recent-card-footer">
                                <a href="/restaurant-website/public/admin/bookings">Xem tất cả</a>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Reviews -->
                    <div class="col-md-6">
                        <div class="recent-card">
                            <div class="recent-card-header">
                                <h5>Đánh giá gần đây</h5>
                            </div>
                            <div class="recent-card-body">
                                <?php if (empty($recentReviews)): ?>
                                    <p class="text-center text-muted py-3">Không có đánh giá nào gần đây.</p>
                                <?php else: ?>
                                    <?php foreach ($recentReviews as $review): ?>
                                        <div class="recent-list-item">
                                            <div>
                                                <strong>
                                                    <?php echo isset($review['user']) ? htmlspecialchars($review['user']['HoVaTen']) : 'Người dùng'; ?>
                                                </strong>
                                                <div class="rating">
                                                    <?php 
                                                    $rating = $review['XepHang'];
                                                    for ($i = 1; $i <= 5; $i++) {
                                                        if ($i <= $rating) {
                                                            echo '<i class="fas fa-star"></i>';
                                                        } else {
                                                            echo '<i class="far fa-star"></i>';
                                                        }
                                                    }
                                                    ?>
                                                </div>
                                            </div>
                                            <div>
                                                <small><?php echo htmlspecialchars(substr($review['BinhLuan'], 0, 100)) . (strlen($review['BinhLuan']) > 100 ? '...' : ''); ?></small>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                            <div class="recent-card-footer">
                                <a href="/restaurant-website/public/admin/reviews">Xem tất cả</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <?php include __DIR__ . '/../../app/includes/footer.php'; ?>

    <!-- Back to top area -->
    <div class="scroll-up">
        <svg class="scroll-circle svg-content" width="100%" height="100%" viewBox="-1 -1 102 102">
            <path d="M50,1 a49,49 0 0,1 0,98 a49,49 0 0,1 0,-98"/>
        </svg>
    </div>

    <!-- JavaScript files -->
    <script src="/restaurant-website/public/assets/js/jquery-3.7.1.min.js"></script>
    <script src="/restaurant-website/public/assets/js/bootstrap.bundle.min.js"></script>
    <script src="/restaurant-website/public/assets/js/jquery.nice-select.min.js"></script>
    <script src="/restaurant-website/public/assets/js/jquery.waypoints.js"></script>
    <script src="/restaurant-website/public/assets/js/jquery.counterup.min.js"></script>
    <script src="/restaurant-website/public/assets/js/swiper-bundle.min.js"></script>
    <script src="/restaurant-website/public/assets/js/jquery.meanmenu.min.js"></script>
    <script src="/restaurant-website/public/assets/js/jquery.magnific-popup.min.js"></script>
    <script src="/restaurant-website/public/assets/js/animation.js"></script>
    <script src="/restaurant-website/public/assets/js/wow.min.js"></script>
    <script src="/restaurant-website/public/assets/js/main.js"></script>
</body>
</html>