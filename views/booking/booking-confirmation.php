<?php
require_once 'session.php';

// Kiểm tra người dùng đã đăng nhập
checkUserLoggedIn();
$user = getCurrentUser();

// Kiểm tra xem có ID đặt bàn không
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$bookingId = $_GET['id'];

// Lấy thông tin đặt bàn
$response = apiRequest('/dat-ban/' . $bookingId, 'GET');

// Kiểm tra kết quả từ API
if (!isset($response['success']) || !$response['success']) {
    header('Location: datban.php?error=notfound');
    exit;
}

$booking = $response['data'];

// Lấy thông tin chi tiết
$restaurantId = null;
$areaId = null;
$tableId = null;

// Kiểm tra và lấy thông tin từ chi tiết đặt bàn
if (!empty($booking['chiTietDatBans']) && !empty($booking['chiTietDatBans'][0])) {
    $tableId = $booking['chiTietDatBans'][0]['ID_Ban'] ?? null;
    
    // Lấy thông tin bàn
    if ($tableId) {
        $tableResponse = apiRequest('/ban/' . $tableId, 'GET');
        $table = $tableResponse['data'] ?? null;
        
        if ($table) {
            $areaId = $table['ID_KhuVuc'] ?? null;
            
            // Lấy thông tin khu vực
            if ($areaId) {
                $areaResponse = apiRequest('/khuvuc/' . $areaId, 'GET');
                $area = $areaResponse['data'] ?? null;
                
                if ($area) {
                    $restaurantId = $area['ID_NhaHang'] ?? null;
                    
                    // Lấy thông tin nhà hàng
                    if ($restaurantId) {
                        $restaurantResponse = apiRequest('/nhahang/' . $restaurantId, 'GET');
                        $restaurant = $restaurantResponse['data'] ?? null;
                    }
                }
            }
        }
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
    <meta name="author" content="modinatheme">
    <meta name="description" content="Xác nhận đặt bàn - Nhà hàng">
    <!-- Page title -->
    <title>Xác nhận đặt bàn - Nhà hàng</title>
    <!-- Favicon -->
    <link rel="shortcut icon" href="assets/img/logo/favicon.svg">
    <!-- CSS files -->
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/font-awesome.css">
    <link rel="stylesheet" href="assets/css/animate.css">
    <link rel="stylesheet" href="assets/css/magnific-popup.css">
    <link rel="stylesheet" href="assets/css/meanmenu.css">
    <link rel="stylesheet" href="assets/css/swiper-bundle.min.css">
    <link rel="stylesheet" href="assets/css/nice-select.css">
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="style.css">
    <!-- Custom styles for confirmation page -->
    <style>
        .confirmation-section {
            padding: 100px 0;
            background-color: #f8f9fa;
        }
        .confirmation-wrapper {
            background-color: #fff;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
            text-align: center;
        }
        .confirmation-header {
            margin-bottom: 40px;
        }
        .confirmation-icon {
            font-size: 80px;
            color: #28a745;
            margin-bottom: 20px;
        }
        .confirmation-title {
            font-size: 32px;
            margin-bottom: 15px;
        }
        .confirmation-subtitle {
            color: #666;
            margin-bottom: 30px;
        }
        .booking-details {
            background-color: #f9f9f9;
            padding: 30px;
            border-radius: 10px;
            text-align: left;
            margin-bottom: 30px;
        }
        .booking-detail-item {
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
        }
        .booking-detail-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        .booking-detail-label {
            font-weight: 600;
            color: #333;
        }
        .booking-id {
            background-color: #f0f8ff;
            padding: 10px 20px;
            border-radius: 5px;
            font-family: monospace;
            font-size: 18px;
            display: inline-block;
            margin-bottom: 30px;
        }
        .cancellation-policy {
            background-color: #fff8e6;
            padding: 20px;
            border-radius: 10px;
            border-left: 4px solid #ffc107;
            margin-bottom: 30px;
            text-align: left;
        }
        .action-buttons {
            margin-top: 30px;
        }
        .action-buttons .theme-btn {
            margin: 0 10px;
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
    <?php include '../includes/header.php'; ?>

    <!-- Confirmation Section -->
    <section class="confirmation-section">
        <div class="container">
            <div class="confirmation-wrapper">
                <div class="confirmation-header">
                    <div class="confirmation-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h2 class="confirmation-title">Đặt bàn thành công!</h2>
                    <p class="confirmation-subtitle">Cảm ơn bạn đã đặt bàn tại nhà hàng chúng tôi. Dưới đây là thông tin chi tiết đặt bàn của bạn.</p>
                    <div class="booking-id">
                        Mã đặt bàn: #<?php echo $bookingId; ?>
                    </div>
                </div>

                <div class="booking-details">
                    <div class="booking-detail-item">
                        <span class="booking-detail-label">Nhà hàng:</span>
                        <span class="booking-detail-value"><?php echo isset($restaurant['TenNhaHang']) ? $restaurant['TenNhaHang'] : 'Không có thông tin'; ?></span>
                    </div>
                    <div class="booking-detail-item">
                        <span class="booking-detail-label">Địa chỉ:</span>
                        <span class="booking-detail-value"><?php echo isset($restaurant['DiaChi']) ? $restaurant['DiaChi'] : 'Không có thông tin'; ?></span>
                    </div>
                    <div class="booking-detail-item">
                        <span class="booking-detail-label">Khu vực:</span>
                        <span class="booking-detail-value"><?php echo isset($area['Ten']) ? $area['Ten'] . ' - Tầng ' . $area['Tang'] : 'Chưa xác định'; ?></span>
                    </div>
                    <div class="booking-detail-item">
                        <span class="booking-detail-label">Bàn:</span>
                        <span class="booking-detail-value"><?php echo isset($table['SoBang']) ? 'Bàn số ' . $table['SoBang'] : 'Chưa xác định'; ?></span>
                    </div>
                    <div class="booking-detail-item">
                        <span class="booking-detail-label">Thời gian đặt bàn:</span>
                        <span class="booking-detail-value">
                            <?php 
                                echo isset($booking['ThoiGianDatBan']) 
                                    ? date('H:i - d/m/Y', strtotime($booking['ThoiGianDatBan'])) 
                                    : 'Không có thông tin';
                            ?>
                        </span>
                    </div>
                    <div class="booking-detail-item">
                        <span class="booking-detail-label">Số lượng khách:</span>
                        <span class="booking-detail-value"><?php echo $booking['SoLuongKhach'] ?? 'Không có thông tin'; ?> người</span>
                    </div>
                    <div class="booking-detail-item">
                        <span class="booking-detail-label">Trạng thái:</span>
                        <span class="booking-detail-value">
                            <?php 
                                $statusText = 'Không xác định';
                                $statusClass = '';
                                
                                if (isset($booking['TrangThai'])) {
                                    switch($booking['TrangThai']) {
                                        case 0:
                                            $statusText = 'Chờ xác nhận';
                                            $statusClass = 'text-warning';
                                            break;
                                        case 1:
                                            $statusText = 'Đã xác nhận';
                                            $statusClass = 'text-success';
                                            break;
                                        case 2:
                                            $statusText = 'Đã hủy';
                                            $statusClass = 'text-danger';
                                            break;
                                    }
                                }
                                
                                echo '<span class="' . $statusClass . '">' . $statusText . '</span>';
                            ?>
                        </span>
                    </div>
                    <?php if (!empty($booking['YeuCau'])): ?>
                        <div class="booking-detail-item">
                            <span class="booking-detail-label">Yêu cầu đặc biệt:</span>
                            <span class="booking-detail-value"><?php echo $booking['YeuCau']; ?></span>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="cancellation-policy">
                    <h4><i class="fas fa-exclamation-circle"></i> Chính sách hủy đặt bàn</h4>
                    <p>Bạn có thể hủy đặt bàn miễn phí trước 2 giờ so với thời gian đặt bàn. Vui lòng liên hệ với chúng tôi nếu có bất kỳ thay đổi nào về lịch đặt bàn của bạn.</p>
                    <p>Liên hệ: <strong><?php echo isset($restaurant['Sdt']) ? $restaurant['Sdt'] : '0123456789'; ?></strong></p>
                </div>

                <div class="action-buttons">
                    <a href="my-bookings.php" class="theme-btn">
                        <span class="button-content-wrapper d-flex align-items-center">
                            <span class="button-icon"><i class="fas fa-calendar-alt"></i></span>
                            <span class="button-text">Xem đặt bàn của tôi</span>
                        </span>
                    </a>
                    <a href="menu.php" class="theme-btn bg-secondary">
                        <span class="button-content-wrapper d-flex align-items-center">
                            <span class="button-icon"><i class="fas fa-utensils"></i></span>
                            <span class="button-text">Xem thực đơn</span>
                        </span>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <?php include '../includes/footer.php'; ?>

    <!-- Back to top area -->
    <div class="scroll-up">
        <svg class="scroll-circle svg-content" width="100%" height="100%" viewBox="-1 -1 102 102">
            <path d="M50,1 a49,49 0 0,1 0,98 a49,49 0 0,1 0,-98"/>
        </svg>
    </div>

    <!-- JavaScript files -->
    <script src="assets/js/jquery-3.7.1.min.js"></script>
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>