<?php
require_once 'session.php';
checkUserLoggedIn();
$user = getCurrentUser();

// Lấy ID đặt bàn từ URL
$bookingId = $_GET['id'] ?? 0;

if (!$bookingId) {
    header('Location: /restaurant-website/public/booking/my-bookings');
    exit;
}

// Lấy thông tin đặt bàn
$bookingResponse = apiRequest('/dat-ban/' . $bookingId, 'GET');
$booking = $bookingResponse['data'] ?? null;

if (!$booking) {
    header('Location: /restaurant-website/public/booking/my-bookings?error=booking_not_found');
    exit;
}

// Lấy thông tin thanh toán
$paymentResponse = apiRequest('/thanh-toan?id_thongtin_datban=' . $bookingId, 'GET');
$payment = !empty($paymentResponse['data']) ? $paymentResponse['data'][0] : null;

if (!$payment) {
    header('Location: /restaurant-website/public/payment?id=' . $bookingId . '&error=payment_not_found');
    exit;
}

// Lấy danh sách món ăn đã đặt
$orderedFoodsResponse = apiRequest('/chi-tiet-dat-mon?id_thongtin_datban=' . $bookingId, 'GET');
$orderedFoods = $orderedFoodsResponse['data'] ?? [];

// Tính tổng tiền
$totalAmount = $payment['SoLuong'] ?? 0;
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thanh toán thành công - Nhà hàng</title>
    <link rel="shortcut icon" href="/restaurant-website/public/assets/img/logo/favicon.svg">
    <link rel="stylesheet" href="/restaurant-website/public/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="/restaurant-website/public/assets/css/font-awesome.css">
    <link rel="stylesheet" href="/restaurant-website/public/assets/css/animate.css">
    <link rel="stylesheet" href="/restaurant-website/public/assets/css/main.css">
    <style>
        .success-section {
            padding: 80px 0;
            background-color: #f8f9fa;
        }
        .success-container {
            background-color: #fff;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            padding: 40px;
            text-align: center;
        }
        .success-icon {
            width: 80px;
            height: 80px;
            background-color: #d4edda;
            color: #28a745;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
            font-size: 40px;
        }
        .success-title {
            font-size: 24px;
            margin-bottom: 20px;
            color: #28a745;
        }
        .payment-details {
            margin: 30px auto;
            max-width: 500px;
            text-align: left;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #f5f5f5;
        }
        .detail-label {
            font-weight: 600;
            color: #555;
        }
        .payment-method {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 14px;
            background-color: #f8f9fa;
        }
        .actions {
            margin-top: 40px;
        }
        .btn-action {
            margin: 0 10px;
            padding: 10px 20px;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../../app/includes/header.php'; ?>

    <div class="breadcrumb-area bg-img" style="background-image: url('/restaurant-website/public/assets/img/bg/breadcrumb.jpg');">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="breadcrumb-inner text-center">
                        <h2>Thanh toán thành công</h2>
                        <ul class="page-list">
                            <li><a href="/restaurant-website/public/">Trang chủ</a></li>
                            <li><a href="/restaurant-website/public/booking/my-bookings">Đơn đặt bàn của tôi</a></li>
                            <li>Thanh toán thành công</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <section class="success-section">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="success-container">
                        <div class="success-icon">
                            <i class="fas fa-check"></i>
                        </div>
                        <h3 class="success-title">Thanh toán thành công!</h3>
                        <p>Cảm ơn bạn đã đặt món tại nhà hàng của chúng tôi.</p>
                        
                        <div class="payment-details">
                            <div class="detail-row">
                                <div class="detail-label">Mã đơn đặt bàn:</div>
                                <div>#<?php echo $bookingId; ?></div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">Thời gian đặt bàn:</div>
                                <div><?php echo date('H:i - d/m/Y', strtotime($booking['ThoiGianDatBan'])); ?></div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">Số lượng khách:</div>
                                <div><?php echo $booking['SoLuongKhach']; ?> người</div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">Tổng thanh toán:</div>
                                <div><?php echo number_format($totalAmount, 0, ',', '.'); ?>đ</div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">Phương thức thanh toán:</div>
                                <div>
                                    <span class="payment-method">
                                        <?php
                                        switch ($payment['PhuongThucThanhToan']) {
                                            case 1:
                                                echo '<i class="fas fa-money-bill-wave"></i> Tiền mặt';
                                                break;
                                            case 3:
                                                echo '<i class="fas fa-wallet"></i> Ví MoMo';
                                                break;
                                            default:
                                                echo 'Khác';
                                        }
                                        ?>
                                    </span>
                                </div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">Mã giao dịch:</div>
                                <div><?php echo $payment['MaGiaoDich'] ?? 'N/A'; ?></div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">Thời gian thanh toán:</div>
                                <div><?php echo date('H:i - d/m/Y', strtotime($payment['NgayThanhToan'])); ?></div>
                            </div>
                        </div>
                        
                        <div class="actions">
                            <a href="/restaurant-website/public/booking/my-bookings" class="btn btn-primary btn-action">
                                <i class="fas fa-list"></i> Xem đơn đặt bàn
                            </a>
                            <a href="/restaurant-website/public/" class="btn btn-outline-primary btn-action">
                                <i class="fas fa-home"></i> Trang chủ
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php include __DIR__ . '/../../app/includes/footer.php'; ?>

    <script src="/restaurant-website/public/assets/js/jquery-3.7.1.min.js"></script>
    <script src="/restaurant-website/public/assets/js/bootstrap.bundle.min.js"></script>
    <script src="/restaurant-website/public/assets/js/main.js"></script>
</body>
</html>