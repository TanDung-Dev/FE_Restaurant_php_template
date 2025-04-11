<?php
require_once 'session.php';

// Kiểm tra người dùng đã đăng nhập
checkUserLoggedIn();
$user = getCurrentUser();

// Lấy danh sách đơn đặt bàn của người dùng
$response = apiRequest('/dat-ban?id_user=' . $user['ID_USER'], 'GET');
$bookings = $response['data'] ?? [];

// Sắp xếp đơn đặt bàn theo thời gian (mới nhất trước)
usort($bookings, function($a, $b) {
    return strtotime($b['ThoiGianDatBan']) - strtotime($a['ThoiGianDatBan']);
});

// Phân loại đơn đặt bàn
$upcomingBookings = [];
$pastBookings = [];
$cancelledBookings = [];

foreach ($bookings as $booking) {
    $bookingTime = strtotime($booking['ThoiGianDatBan']);
    $now = time();
    
    if ($booking['TrangThai'] == 2) { // Đã hủy
        $cancelledBookings[] = $booking;
    } else if ($bookingTime > $now) { // Sắp tới
        $upcomingBookings[] = $booking;
    } else { // Đã qua
        $pastBookings[] = $booking;
    }
}

// Xử lý hủy đơn đặt bàn
if (isset($_GET['action']) && $_GET['action'] == 'cancel' && isset($_GET['id'])) {
    $bookingId = $_GET['id'];
    
    // Gọi API để hủy đơn
    $response = apiRequest('/dat-ban/' . $bookingId, 'DELETE');
    
    if ($response['success'] ?? false) {
        header('Location: my-bookings.php?message=cancelled');
        exit;
    } else {
        $error = $response['message'] ?? 'Có lỗi xảy ra khi hủy đơn đặt bàn';
    }
}

// Xử lý thông báo
$message = '';
if (isset($_GET['message'])) {
    switch ($_GET['message']) {
        case 'cancelled':
            $message = 'Đơn đặt bàn đã được hủy thành công';
            break;
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <!-- Meta Tags -->
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Đơn đặt bàn của tôi - Nhà hàng">
    <!-- Page title -->
    <title>Đơn đặt bàn của tôi - Nhà hàng</title>
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
        .bookings-section {
            padding: 100px 0;
            background-color: #f8f9fa;
        }
        .bookings-wrapper {
            background-color: #fff;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
        }
        .booking-title {
            margin-bottom: 40px;
        }
        .booking-title h2 {
            font-size: 36px;
            margin-bottom: 15px;
        }
        .booking-title p {
            color: #666;
            font-size: 16px;
        }
        .booking-tabs {
            margin-bottom: 30px;
        }
        .booking-card {
            background-color: #fff;
            border: 1px solid #eee;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }
        .booking-card:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .booking-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        .booking-header h5 {
            margin: 0;
            font-size: 18px;
        }
        .booking-date {
            color: #666;
            font-size: 14px;
        }
        .booking-details {
            margin-bottom: 15px;
        }
        .booking-detail-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        .booking-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
        .booking-status {
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
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
        .status-completed {
            background-color: #e6f7ff;
            color: #007bff;
        }
        .empty-state {
            text-align: center;
            padding: 50px 0;
        }
        .empty-state i {
            font-size: 60px;
            color: #ccc;
            margin-bottom: 20px;
        }
        .empty-state h4 {
            margin-bottom: 15px;
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

    <!-- Bookings Section -->
    <section class="bookings-section">
        <div class="container">
            <div class="bookings-wrapper">
                <div class="booking-title text-center">
                    <h2>Đơn đặt bàn của tôi</h2>
                    <p>Quản lý tất cả đơn đặt bàn của bạn tại đây</p>
                </div>

                <?php if (!empty($message)): ?>
                    <div class="alert alert-success mb-4">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger mb-4">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <!-- Booking Tabs -->
                <div class="booking-tabs">
                    <ul class="nav nav-tabs" id="bookingTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="upcoming-tab" data-bs-toggle="tab" data-bs-target="#upcoming" type="button" role="tab" aria-controls="upcoming" aria-selected="true">
                                Sắp tới (<?php echo count($upcomingBookings); ?>)
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="past-tab" data-bs-toggle="tab" data-bs-target="#past" type="button" role="tab" aria-controls="past" aria-selected="false">
                                Đã hoàn thành (<?php echo count($pastBookings); ?>)
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="cancelled-tab" data-bs-toggle="tab" data-bs-target="#cancelled" type="button" role="tab" aria-controls="cancelled" aria-selected="false">
                                Đã hủy (<?php echo count($cancelledBookings); ?>)
                            </button>
                        </li>
                    </ul>
                </div>

                <!-- Tab Content -->
                <div class="tab-content" id="bookingTabContent">
                    <!-- Upcoming Bookings -->
                    <div class="tab-pane fade show active" id="upcoming" role="tabpanel" aria-labelledby="upcoming-tab">
                        <?php if (empty($upcomingBookings)): ?>
                            <div class="empty-state">
                                <i class="fas fa-calendar-day"></i>
                                <h4>Không có đơn đặt bàn sắp tới</h4>
                                <p>Bạn chưa có đơn đặt bàn nào sắp tới. Hãy đặt bàn ngay!</p>
                                <a href="/restaurant-website/public/datban" class="theme-btn mt-3">
                                    <span class="button-content-wrapper">
                                        <span class="button-icon"><i class="fas fa-calendar-plus"></i></span>
                                        <span class="button-text">Đặt bàn ngay</span>
                                    </span>
                                </a>
                            </div>
                        <?php else: ?>
                            <?php foreach ($upcomingBookings as $booking): ?>
                                <div class="booking-card">
                                    <div class="booking-header">
                                        <h5>Đơn đặt bàn #<?php echo $booking['ID_ThongTinDatBan']; ?></h5>
                                        <span class="booking-status <?php echo $booking['TrangThai'] == 0 ? 'status-pending' : 'status-confirmed'; ?>">
                                            <?php echo $booking['TrangThai'] == 0 ? 'Chờ xác nhận' : 'Đã xác nhận'; ?>
                                        </span>
                                    </div>
                                    <div class="booking-date">
                                        <i class="far fa-calendar-alt"></i> 
                                        <?php echo date('H:i - d/m/Y', strtotime($booking['ThoiGianDatBan'])); ?>
                                    </div>
                                    <div class="booking-details">
                                        <div class="booking-detail-item">
                                            <span>Số lượng khách:</span>
                                            <span><?php echo $booking['SoLuongKhach']; ?> người</span>
                                        </div>
                                        <?php if (!empty($booking['YeuCau'])): ?>
                                            <div class="booking-detail-item">
                                                <span>Yêu cầu đặc biệt:</span>
                                                <span><?php echo $booking['YeuCau']; ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="booking-actions">
                                        <!-- <a href="booking-detail.php?id=<?php echo $booking['ID_ThongTinDatBan']; ?>" class="btn btn-sm btn-outline-primary"> -->
                                        <a href="/restaurant-website/public/booking/booking-detail?id=<?php echo $booking['ID_ThongTinDatBan']; ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye"></i> Chi tiết
                                        </a>

                                        <a href="/restaurant-website/public/user/order-food?id=<?php echo $booking['ID_ThongTinDatBan']; ?>" class="btn btn-sm btn-outline-success">
                                            <i class="fas fa-utensils"></i> Đặt món
                                        </a>
                                        <?php if ($booking['TrangThai'] == 0): ?>
                                            <!-- <a href="my-bookings.php?action=cancel&id=<?php echo $booking['ID_ThongTinDatBan']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Bạn có chắc chắn muốn hủy đơn đặt bàn này?');"> -->
                                            <a href="/restaurant-website/public/booking/my-bookings?action=cancel&id=<?php echo $booking['ID_ThongTinDatBan']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Bạn có chắc chắn muốn hủy đơn đặt bàn này?');">
                                                <i class="fas fa-times"></i> Hủy đơn
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Past Bookings -->
                    <div class="tab-pane fade" id="past" role="tabpanel" aria-labelledby="past-tab">
                        <?php if (empty($pastBookings)): ?>
                            <div class="empty-state">
                                <i class="fas fa-history"></i>
                                <h4>Không có đơn đặt bàn đã hoàn thành</h4>
                                <p>Bạn chưa có đơn đặt bàn nào đã hoàn thành.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($pastBookings as $booking): ?>
                                <div class="booking-card">
                                    <div class="booking-header">
                                        <h5>Đơn đặt bàn #<?php echo $booking['ID_ThongTinDatBan']; ?></h5>
                                        <span class="booking-status status-completed">Đã hoàn thành</span>
                                    </div>
                                    <div class="booking-date">
                                        <i class="far fa-calendar-alt"></i> 
                                        <?php echo date('H:i - d/m/Y', strtotime($booking['ThoiGianDatBan'])); ?>
                                    </div>
                                    <div class="booking-details">
                                        <div class="booking-detail-item">
                                            <span>Số lượng khách:</span>
                                            <span><?php echo $booking['SoLuongKhach']; ?> người</span>
                                        </div>
                                    </div>
                                    <div class="booking-actions">
                                        <a href="booking-detail.php?id=<?php echo $booking['ID_ThongTinDatBan']; ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye"></i> Chi tiết
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Cancelled Bookings -->
                    <div class="tab-pane fade" id="cancelled" role="tabpanel" aria-labelledby="cancelled-tab">
                        <?php if (empty($cancelledBookings)): ?>
                            <div class="empty-state">
                                <i class="fas fa-ban"></i>
                                <h4>Không có đơn đặt bàn đã hủy</h4>
                                <p>Bạn chưa có đơn đặt bàn nào đã hủy.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($cancelledBookings as $booking): ?>
                                <div class="booking-card">
                                    <div class="booking-header">
                                        <h5>Đơn đặt bàn #<?php echo $booking['ID_ThongTinDatBan']; ?></h5>
                                        <span class="booking-status status-cancelled">Đã hủy</span>
                                    </div>
                                    <div class="booking-date">
                                        <i class="far fa-calendar-alt"></i> 
                                        <?php echo date('H:i - d/m/Y', strtotime($booking['ThoiGianDatBan'])); ?>
                                    </div>
                                    <div class="booking-details">
                                        <div class="booking-detail-item">
                                            <span>Số lượng khách:</span>
                                            <span><?php echo $booking['SoLuongKhach']; ?> người</span>
                                        </div>
                                    </div>
                                    <div class="booking-actions">
                                        <a href="booking-detail.php?id=<?php echo $booking['ID_ThongTinDatBan']; ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye"></i> Chi tiết
                                        </a>
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
    <?php include __DIR__ . '/../../app/includes/footer.php'; ?>

    <!-- Back to top area -->
    <div class="scroll-up">
        <svg class="scroll-circle svg-content" width="100%" height="100%" viewBox="-1 -1 102 102">
            <path d="M50,1 a49,49 0 0,1 0,98 a49,49 0 0,1 0,-98"/>
        </svg>
    </div>

    <!-- JavaScript files -->
    <script src="/restaurant-website/public/assets/js/jquery-3.7.1.min.js"></script>
    <script src="/restaurant-website/public/assets/js/viewport.jquery.js"></script>
    <script src="/restaurant-website/public/assets/js/bootstrap.bundle.min.js"></script>
    <script src="/restaurant-website/public/assets/js/jquery.nice-select.min.js"></script>
    <script src="/restaurant-website/public/assets/js/jquery.waypoints.js"></script>
    <script src="/restaurant-website/public/assets/js/jquery.counterup.min.js"></script>
    <script src="/restaurant-website/public/assets/js/swiper-bundle.min.js"></script>
    <script src="/restaurant-website/public/assets/js/jquery.meanmenu.min.js"></script>
    <script src="/restaurant-website/public/assets/js/jquery.magnific-popup.min.js"></script>
    <script src="/restaurant-website/public/assets/js/animation.js"></script>
    <script src="/restaurant-website/public/assets/js/wow.min.js"></script>
    <script src="/restaurant-website/public/assets/js/contact-from.js"></script>
    <script src="/restaurant-website/public/assets/js/main.js"></script>
</body>
</html>