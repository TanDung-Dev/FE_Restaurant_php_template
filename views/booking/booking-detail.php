<?php
require_once 'session.php';

// Kiểm tra người dùng đã đăng nhập
checkUserLoggedIn();
$user = getCurrentUser();

// Kiểm tra có ID đặt bàn không
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: my-bookings.php');
    exit;
}

$bookingId = $_GET['id'];

// Lấy thông tin đặt bàn
$response = apiRequest('/dat-ban/' . $bookingId, 'GET');

// Kiểm tra kết quả từ API
if (!isset($response['success']) || !$response['success']) {
    header('Location: my-bookings.php?error=notfound');
    exit;
}

$booking = $response['data'];

// Kiểm tra đơn đặt bàn có phải của người dùng hiện tại không
if ($booking['ID_USER'] != $user['ID_USER'] && !isAdmin()) {
    header('Location: my-bookings.php?error=unauthorized');
    exit;
}

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

// Lấy danh sách món ăn đã đặt
$orderedFoodsResponse = apiRequest('/chi-tiet-dat-mon?id_thongtin_datban=' . $bookingId, 'GET');
$orderedFoods = $orderedFoodsResponse['data'] ?? [];

// Tính tổng tiền các món đã đặt
$totalAmount = 0;
foreach ($orderedFoods as $food) {
    $totalAmount += $food['ThanhTien'];
}

// Trạng thái đặt bàn
$statusText = 'Không xác định';
$statusClass = '';
if (isset($booking['TrangThai'])) {
    switch($booking['TrangThai']) {
        case 0:
            $statusText = 'Chờ xác nhận';
            $statusClass = 'status-pending';
            break;
        case 1:
            $statusText = 'Đã xác nhận';
            $statusClass = 'status-confirmed';
            break;
        case 2:
            $statusText = 'Đã hủy';
            $statusClass = 'status-cancelled';
            break;
    }
}

// Lấy thông tin thanh toán
$paymentResponse = apiRequest('/thanh-toan?id_thongtin_datban=' . $bookingId, 'GET');
$payment = !empty($paymentResponse['data']) ? $paymentResponse['data'][0] : null;

// Xử lý hủy đơn đặt bàn
if (isset($_GET['action']) && $_GET['action'] == 'cancel') {
    // Kiểm tra đơn có thể hủy không
    if ($booking['TrangThai'] != 0) {
        header('Location: booking-detail.php?id=' . $bookingId . '&error=cannot_cancel');
        exit;
    }
    
    // Gọi API để hủy đơn
    $response = apiRequest('/dat-ban/' . $bookingId, 'DELETE');
    
    if ($response['success'] ?? false) {
        header('Location: booking-detail.php?id=' . $bookingId . '&message=cancelled');
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

$error = '';
if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'cannot_cancel':
            $error = 'Không thể hủy đơn đặt bàn đã được xác nhận hoặc đã hủy';
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
    <meta name="description" content="Chi tiết đặt bàn - Nhà hàng">
    <!-- Page title -->
    <title>Chi tiết đặt bàn - Nhà hàng</title>
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
        .booking-detail-section {
            padding: 100px 0;
            background-color: #f8f9fa;
        }
        .booking-detail-wrapper {
            background-color: #fff;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
        }
        .booking-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        .booking-header h2 {
            margin: 0;
            font-size: 24px;
        }
        .booking-status {
            padding: 8px 15px;
            border-radius: 5px;
            font-size: 14px;
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
        .section-title {
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        .booking-info, .restaurant-info, .order-info, .payment-info {
            margin-bottom: 30px;
        }
        .info-item {
            display: flex;
            margin-bottom: 15px;
        }
        .info-label {
            width: 200px;
            font-weight: 600;
        }
        .info-value {
            flex: 1;
        }
        .food-item {
            display: flex;
            justify-content: space-between;
            padding: 15px 0;
            border-bottom: 1px solid #eee;
        }
        .food-name {
            flex: 1;
        }
        .food-quantity {
            width: 50px;
            text-align: center;
        }
        .food-price {
            width: 120px;
            text-align: right;
        }
        .food-total {
            width: 120px;
            text-align: right;
            font-weight: 600;
        }
        .order-total {
            display: flex;
            justify-content: space-between;
            padding-top: 15px;
            margin-top: 15px;
            border-top: 1px solid #ddd;
        }
        .total-label {
            font-size: 18px;
            font-weight: 600;
        }
        .total-value {
            font-size: 20px;
            font-weight: 700;
            color: #ff5b00;
        }
        .payment-method {
            display: inline-block;
            padding: 5px 10px;
            background-color: #f8f9fa;
            border-radius: 5px;
            font-weight: 500;
        }
        .payment-status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 5px;
            font-weight: 600;
        }
        .payment-paid {
            background-color: #e1f7e7;
            color: #28a745;
        }
        .payment-unpaid {
            background-color: #fff8e6;
            color: #f2b01e;
        }
        .action-buttons {
            margin-top: 30px;
            text-align: center;
        }
        .cancel-btn {
            background-color: #dc3545;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .cancel-btn:hover {
            background-color: #c82333;
        }
        .back-btn {
            background-color: #6c757d;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-right: 10px;
            transition: all 0.3s ease;
        }
        .back-btn:hover {
            background-color: #5a6268;
        }
        .restaurant-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .note {
            font-style: italic;
            color: #6c757d;
            margin-top: 5px;
        }
        .empty-order {
            text-align: center;
            padding: 40px 0;
            color: #6c757d;
        }
        .empty-order i {
            font-size: 40px;
            margin-bottom: 15px;
            color: #dee2e6;
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

    <!-- Breadcrumb -->
    <div class="breadcrumb-area bg-img breabg-img">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="breadcrumb-inner text-center">
                        <div class="section-title wow fadeInUp" data-wow-delay="0.3s">
                            <h2>Chi tiết đặt bàn</h2>
                        </div>
                        
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Booking Detail Section -->
    <section class="booking-detail-section">
        <div class="container">
            <div class="booking-detail-wrapper">
                <?php if ($message): ?>
                    <div class="alert alert-success" role="alert">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger" role="alert">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <div class="booking-header">
                    <h2>Đơn đặt bàn #<?php echo $booking['ID_ThongTinDatBan']; ?></h2>
                    <span class="booking-status <?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                </div>

                <div class="row">
                    <div class="col-lg-6">
                        <div class="booking-info">
                            <h4 class="section-title">Thông tin đặt bàn</h4>
                            <div class="info-item">
                                <div class="info-label">Ngày đặt:</div>
                                <div class="info-value"><?php echo date('d/m/Y', strtotime($booking['ThoiGianDatBan'])); ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Giờ đặt:</div>
                                <div class="info-value"><?php echo date('H:i', strtotime($booking['ThoiGianDatBan'])); ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Số lượng khách:</div>
                                <div class="info-value"><?php echo $booking['SoLuongKhach']; ?> người</div>
                            </div>
                            <?php if (!empty($booking['YeuCau'])): ?>
                                <div class="info-item">
                                    <div class="info-label">Yêu cầu đặc biệt:</div>
                                    <div class="info-value"><?php echo htmlspecialchars($booking['YeuCau']); ?></div>
                                </div>
                            <?php endif; ?>
                            <div class="info-item">
                                <div class="info-label">Thời gian đặt:</div>
                                <div class="info-value"><?php echo date('d/m/Y H:i', strtotime($booking['NgayTao'])); ?></div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="restaurant-info">
                            <h4 class="section-title">Thông tin nhà hàng</h4>
                            <?php if (isset($restaurant) && !empty($restaurant)): ?>
                                <?php if (!empty($restaurant['Anh1'])): ?>
                                    <img src="<?php echo $restaurant['Anh1']; ?>" alt="<?php echo htmlspecialchars($restaurant['TenNhaHang']); ?>" class="restaurant-image">
                                <?php endif; ?>
                                <div class="info-item">
                                    <div class="info-label">Nhà hàng:</div>
                                    <div class="info-value"><?php echo htmlspecialchars($restaurant['TenNhaHang']); ?></div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Địa chỉ:</div>
                                    <div class="info-value"><?php echo htmlspecialchars($restaurant['DiaChi']); ?></div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Số điện thoại:</div>
                                    <div class="info-value"><?php echo htmlspecialchars($restaurant['Sdt']); ?></div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Khu vực:</div>
                                    <div class="info-value"><?php echo isset($area) ? htmlspecialchars($area['Ten']) : 'Không có thông tin'; ?></div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Bàn số:</div>
                                    <div class="info-value"><?php echo isset($table) ? $table['SoBang'] : 'Chưa gán bàn'; ?></div>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-info">
                                    Thông tin nhà hàng chưa được cập nhật.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Danh sách món ăn đã đặt -->
                <div class="order-info">
                    <h4 class="section-title">Món ăn đã đặt</h4>
                    
                    <?php if (empty($orderedFoods)): ?>
                        <div class="empty-order">
                            <i class="fas fa-utensils"></i>
                            <p>Chưa có món ăn nào được đặt</p>
                        </div>
                    <?php else: ?>
                        <div class="food-item food-header">
                            <div class="food-name">Tên món ăn</div>
                            <div class="food-quantity">SL</div>
                            <div class="food-price">Đơn giá</div>
                            <div class="food-total">Thành tiền</div>
                        </div>
                        
                        <?php foreach ($orderedFoods as $food): ?>
                            <div class="food-item">
                                <div class="food-name">
                                    <?php 
                                    // Kiểm tra xem monAn có tồn tại và có thuộc tính TenMonAn không
                                    echo isset($food['monAn']) && isset($food['monAn']['TenMonAn']) 
                                        ? htmlspecialchars($food['monAn']['TenMonAn']) 
                                        : 'Không xác định'; 
                                    ?>
                                    <?php if (!empty($food['GhiChu'])): ?>
                                        <div class="note"><?php echo htmlspecialchars($food['GhiChu']); ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="food-quantity"><?php echo $food['SoLuong']; ?></div>
                                <div class="food-price"><?php echo number_format($food['DonGia'], 0, ',', '.'); ?>đ</div>
                                <div class="food-total"><?php echo number_format($food['ThanhTien'], 0, ',', '.'); ?>đ</div>
                            </div>
                        <?php endforeach; ?>
                        
                        <div class="order-total">
                            <div class="total-label">Tổng cộng:</div>
                            <div class="total-value"><?php echo number_format($totalAmount, 0, ',', '.'); ?>đ</div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Thông tin thanh toán -->
                <?php if ($payment): ?>
                    <div class="payment-info">
                        <h4 class="section-title">Thông tin thanh toán</h4>
                        <div class="info-item">
                            <div class="info-label">Trạng thái:</div>
                            <div class="info-value">
                                <span class="payment-status <?php echo $payment['TrangThaiThanhToan'] == 1 ? 'payment-paid' : 'payment-unpaid'; ?>">
                                    <?php echo $payment['TrangThaiThanhToan'] == 1 ? 'Đã thanh toán' : 'Chưa thanh toán'; ?>
                                </span>
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Phương thức thanh toán:</div>
                            <div class="info-value">
                                <span class="payment-method">
                                    <?php
                                        switch ($payment['PhuongThucThanhToan']) {
                                            case 1:
                                                echo 'Tiền mặt';
                                                break;
                                            case 2:
                                                echo 'Thẻ tín dụng/ghi nợ';
                                                break;
                                            case 3:
                                                echo 'Chuyển khoản ngân hàng';
                                                break;
                                            default:
                                                echo 'Không xác định';
                                        }
                                    ?>
                                </span>
                            </div>
                        </div>
                        <?php if ($payment['TrangThaiThanhToan'] == 1 && isset($payment['NgayThanhToan'])): ?>
                            <div class="info-item">
                                <div class="info-label">Ngày thanh toán:</div>
                                <div class="info-value"><?php echo date('d/m/Y H:i', strtotime($payment['NgayThanhToan'])); ?></div>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($payment['MaGiaoDich'])): ?>
                            <div class="info-item">
                                <div class="info-label">Mã giao dịch:</div>
                                <div class="info-value"><?php echo htmlspecialchars($payment['MaGiaoDich']); ?></div>
                            </div>
                        <?php endif; ?>
                        <div class="info-item">
                            <div class="info-label">Số tiền:</div>
                            <div class="info-value total-value"><?php echo number_format($payment['SoLuong'], 0, ',', '.'); ?>đ</div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Buttons -->
                <div class="action-buttons">
                    <a href="my-bookings.php" class="back-btn">
                        <i class="fas fa-arrow-left"></i> Quay lại danh sách
                    </a>
                    
                    <?php if ($booking['TrangThai'] == 0): ?>
                        <a href="booking-detail.php?id=<?php echo $bookingId; ?>&action=cancel" 
                           class="cancel-btn" 
                           onclick="return confirm('Bạn có chắc chắn muốn hủy đơn đặt bàn này không?')">
                            <i class="fas fa-times"></i> Hủy đặt bàn
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <?php include __DIR__ . '/../../app/includes/footer.php'; ?>

    <!-- Back to top button -->
    <div class="back-to-top">
        <span class="back-top"><i class="fas fa-angle-up"></i></span>
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
    <script src="/restaurant-website/public/assets/js/animation.js"></script>
    <script src="/restaurant-website/public/assets/js/wow.min.js"></script>
    <script src="/restaurant-website/public/assets/js/contact-from.js"></script>
    <script src="/restaurant-website/public/assets/js/main.js"></script>

    <script>
        // Tự động ẩn thông báo sau 5 giây
        setTimeout(function() {
            $('.alert').fadeOut('slow');
        }, 5000);

        // Xử lý preloader
        $(window).on('load', function() {
            $('.preloader').fadeOut(1000);
        });
    </script>
</body>
</html>