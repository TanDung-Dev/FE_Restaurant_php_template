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

// Khởi tạo biến ngay từ đầu
$paymentMethod = 0;
$errorMsg = "";
$successMsg = "";

// Lấy thông tin đặt bàn
$bookingResponse = apiRequest('/dat-ban/' . $bookingId, 'GET');
$booking = $bookingResponse['data'] ?? null;

if (!$booking) {
    header('Location: /restaurant-website/public/booking/my-bookings?error=booking_not_found');
    exit;
}

// Kiểm tra đơn đặt bàn thuộc về người dùng hiện tại
if ($booking['user']['ID_USER'] != $user['ID_USER']) {
    header('Location: /restaurant-website/public/booking/my-bookings?error=unauthorized');
    exit;
}

// Lấy danh sách món ăn đã đặt
$orderedFoodsResponse = apiRequest('/chi-tiet-dat-mon?id_thongtin_datban=' . $bookingId, 'GET');
$orderedFoods = $orderedFoodsResponse['data'] ?? [];

// Tính tổng tiền
$totalAmount = 0;
foreach ($orderedFoods as $food) {
    $totalAmount += $food['ThanhTien'];
}

// Xử lý thanh toán
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['payment_method'])) {
        $paymentMethod = $_POST['payment_method'];
        
        if ($paymentMethod == 1) { // Tiền mặt
            // Dữ liệu thanh toán tiền mặt
            $paymentData = [
                'ID_ThongTinDatBan' => $bookingId,
                'PhuongThucThanhToan' => 1, // 1: Tiền mặt
                'MaGiaoDich' => 'CASH_' . time()
            ];
            
            // Gọi API thanh toán thông thường
            $response = apiRequest('/thanh-toan', 'POST', $paymentData);
            
            if ($response['success']) {
                // Chuyển hướng đến trang xác nhận thanh toán
                header('Location: /restaurant-website/public/payment/payment-success?id=' . $response['data']['ID_ThanhToan']);
                exit;
            } else {
                $errorMsg = $response['message'] ?? 'Có lỗi xảy ra khi thanh toán!';
            }
        } else if ($paymentMethod == 4) { // MoMo
            // Dữ liệu thanh toán MoMo
            $paymentData = [
                'ID_ThongTinDatBan' => $bookingId,
                'PhuongThucThanhToan' => 4, // 4: MoMo
                'SoLuong' => $totalAmount,
                'TrangThaiThanhToan' => 0, // 0: Chưa thanh toán
                'MaGiaoDich' => 'MOMO_INIT_' . time()
            ];
            
            // Gọi API tạo thanh toán trước
            $response = apiRequest('/thanh-toan', 'POST', $paymentData);
            
            if ($response['success']) {
                // Lưu ID thanh toán vào session
                $_SESSION['momo_payment_id'] = $response['data']['ID_ThanhToan'];
                
                // Chuyển hướng đến trang MoMo payment
                header('Location: /restaurant-website/public/payment/momo-payment?id=' . $response['data']['ID_ThanhToan']);
                exit;
            } else {
                $errorMsg = $response['message'] ?? 'Có lỗi xảy ra khi tạo thanh toán!';
            }
        }
    }
}

// Kiểm tra nếu có thông báo lỗi từ quá trình thanh toán MoMo
if (isset($_GET['momo_error'])) {
    $errorMsg = urldecode($_GET['momo_error']);
}

// Phần debug và test API (khi cần thiết)
if (isset($_GET['debug']) && $_GET['debug'] == 1) {
    echo "<pre>Session Data: ";
    var_dump($_SESSION);
    echo "</pre>";
    
    $token = $_SESSION['auth_token'] ?? 'no-token';
    $testBookingId = $bookingId;
    
    echo "<pre>Test with Token: " . substr($token, 0, 10) . "...</pre>";
    
    // Thay đổi URL nếu cần
    $testUrl = API_BASE_URL . '/thanh-toan/momo';
    
    // Đảm bảo dữ liệu đầy đủ
    $testData = json_encode([
        'ID_ThongTinDatBan' => (int)$testBookingId // Đảm bảo là số nguyên nếu cần
    ]);

    echo "<pre>Test Request Data: " . htmlspecialchars($testData) . "</pre>";

    $testHeaders = [
        'Content-Type: application/json',
        'Accept: application/json',
        'Authorization: Bearer ' . $token
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $testUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $testData);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $testHeaders);
    $testResponse = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    echo "<pre>Test API Response ($httpCode): " . htmlspecialchars($testResponse) . "</pre>";
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thanh toán - Nhà hàng</title>
    <link rel="shortcut icon" href="/restaurant-website/public/assets/img/logo/favicon.svg">
    <link rel="stylesheet" href="/restaurant-website/public/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="/restaurant-website/public/assets/css/font-awesome.css">
    <link rel="stylesheet" href="/restaurant-website/public/assets/css/animate.css">
    <link rel="stylesheet" href="/restaurant-website/public/assets/css/main.css">
    <style>
        .payment-section {
            padding: 80px 0;
            background-color: #f8f9fa;
        }
        .payment-container {
            background-color: #fff;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            padding: 30px;
        }
        .payment-header {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
            text-align: center;
        }
        .payment-summary {
            margin-bottom: 30px;
        }
        .payment-info {
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #f5f5f5;
        }
        .payment-info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        .payment-info-label {
            font-weight: 600;
            color: #555;
        }
        .food-list {
            margin-bottom: 30px;
        }
        .food-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #f5f5f5;
        }
        .food-name {
            flex: 1;
        }
        .food-quantity {
            width: 80px;
            text-align: center;
        }
        .food-price {
            width: 120px;
            text-align: right;
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            font-size: 20px;
            font-weight: 700;
            padding-top: 15px;
            margin-top: 15px;
            border-top: 2px solid #eee;
        }
        .payment-methods {
            margin-top: 40px;
        }
        .payment-method-title {
            font-size: 18px;
            margin-bottom: 20px;
            text-align: center;
        }
        .payment-options {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-bottom: 30px;
        }
        .payment-option {
            border: 2px solid #ddd;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            width: 200px;
            transition: all 0.3s ease;
        }
        .payment-option:hover {
            border-color: #ff5b00;
        }
        .payment-option.selected {
            border-color: #ff5b00;
            background-color: #fff8f3;
        }
        .payment-option img {
            height: 40px;
            margin-bottom: 10px;
        }
        .payment-option-title {
            font-weight: 600;
            margin-bottom: 5px;
        }
        .payment-option-description {
            font-size: 14px;
            color: #666;
        }
        .payment-action {
            text-align: center;
            margin-top: 30px;
        }
        .btn-pay {
            background-color: #ff5b00;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .btn-pay:hover {
            background-color: #e64d00;
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
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
                        <h2>Thanh toán</h2>
                        <!-- <ul class="page-list">
                            <li><a href="/restaurant-website/public/">Trang chủ</a></li>
                            <li><a href="/restaurant-website/public/booking/my-bookings">Đơn đặt bàn của tôi</a></li>
                            <li><a href="/restaurant-website/public/user/order-food?id=<?php echo $bookingId; ?>">Đặt món ăn</a></li>
                            <li>Thanh toán</li>
                        </ul> -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <section class="payment-section">
        <div class="container">
            <?php if ($errorMsg): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $errorMsg; ?>
                </div>
            <?php endif; ?>
            
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="payment-container">
                        <div class="payment-header">
                            <h3>Thanh toán đơn đặt bàn #<?php echo $bookingId; ?></h3>
                        </div>
                        
                        <div class="payment-summary">
                            <h4>Thông tin đặt bàn</h4>
                            
                            <div class="payment-info">
                                <div class="payment-info-row">
                                    <div class="payment-info-label">Thời gian đặt bàn:</div>
                                    <div><?php echo date('H:i - d/m/Y', strtotime($booking['ThoiGianDatBan'])); ?></div>
                                </div>
                                <div class="payment-info-row">
                                    <div class="payment-info-label">Số lượng khách:</div>
                                    <div><?php echo $booking['SoLuongKhach']; ?> người</div>
                                </div>
                                <div class="payment-info-row">
                                    <div class="payment-info-label">Nhà hàng:</div>
                                    <div>
                                        <?php
                                        if (!empty($booking['chi_tiet_dat_bans']) && 
                                            !empty($booking['chi_tiet_dat_bans'][0]['ban']) && 
                                            !empty($booking['chi_tiet_dat_bans'][0]['ban']['khu_vuc'])) {
                                            $nhaHangId = $booking['chi_tiet_dat_bans'][0]['ban']['khu_vuc']['ID_NhaHang'];
                                            // Gọi API lấy thông tin nhà hàng nếu cần
                                            $nhaHangResponse = apiRequest('/nhahang/' . $nhaHangId, 'GET');
                                            $nhaHang = $nhaHangResponse['data'] ?? null;
                                            echo $nhaHang ? htmlspecialchars($nhaHang['TenNhaHang']) : 'Không có thông tin';
                                        } else {
                                            echo 'Không có thông tin';
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>
                            
                            <h4>Danh sách món đã đặt</h4>
                            
                            <div class="food-list">
                                <?php if (empty($orderedFoods)): ?>
                                    <div class="text-center py-4">
                                        <p>Không có món ăn nào được đặt.</p>
                                        <a href="/restaurant-website/public/user/order-food?id=<?php echo $bookingId; ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-utensils"></i> Đặt món
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <div class="food-item font-weight-bold">
                                        <div class="food-name">Tên món</div>
                                        <div class="food-quantity">Số lượng</div>
                                        <div class="food-price">Thành tiền</div>
                                    </div>
                                    
                                    <?php foreach ($orderedFoods as $food): ?>
                                        <div class="food-item">
                                            <div class="food-name">
                                                <?php 
                                                if (isset($food['monAn']) && isset($food['monAn']['TenMonAn'])) {
                                                    echo htmlspecialchars($food['monAn']['TenMonAn']);
                                                } else {
                                                    echo 'Món ăn không xác định';
                                                }
                                                ?>
                                            </div>
                                            <div class="food-quantity"><?php echo $food['SoLuong']; ?></div>
                                            <div class="food-price"><?php echo number_format($food['ThanhTien'], 0, ',', '.'); ?>đ</div>
                                        </div>
                                    <?php endforeach; ?>
                                    
                                    <div class="total-row">
                                        <div>Tổng cộng:</div>
                                        <div><?php echo number_format($totalAmount, 0, ',', '.'); ?>đ</div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <?php if (!empty($orderedFoods)): ?>
                            <div class="payment-methods">
                                <h4 class="payment-method-title">Chọn phương thức thanh toán</h4>
                                
                                <form method="POST" id="paymentForm">
                                    <div class="payment-options">
                                        <div class="payment-option" data-payment="1" onclick="selectPaymentMethod(1)">
                                            <img src="/restaurant-website/public/assets/img/payment/cash.png" alt="Tiền mặt">
                                            <div class="payment-option-title">Tiền mặt</div>
                                            <div class="payment-option-description">Thanh toán tại nhà hàng</div>
                                        </div>
                                        
                                        <div class="payment-option" data-payment="4" onclick="selectPaymentMethod(4)">
                                            <img src="/restaurant-website/public/assets/img/payment/momo.png" alt="MoMo">
                                            <div class="payment-option-title">Ví MoMo</div>
                                            <div class="payment-option-description">Thanh toán qua ví điện tử MoMo</div>
                                        </div>
                                    </div>
                                    
                                    <input type="hidden" name="payment_method" id="paymentMethod" value="">
                                    
                                    <div class="payment-action">
                                        <button type="submit" class="btn-pay" id="payButton" disabled>
                                            <i class="fas fa-wallet"></i> Thanh toán
                                        </button>
                                    </div>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php include __DIR__ . '/../../app/includes/footer.php'; ?>

    <script src="/restaurant-website/public/assets/js/jquery-3.7.1.min.js"></script>
    <script src="/restaurant-website/public/assets/js/bootstrap.bundle.min.js"></script>
    <script src="/restaurant-website/public/assets/js/main.js"></script>
    
    <script>
    // Chọn phương thức thanh toán
    function selectPaymentMethod(method) {
        // Cập nhật UI
        document.querySelectorAll('.payment-option').forEach(function(option) {
            if (parseInt(option.dataset.payment) === method) {
                option.classList.add('selected');
            } else {
                option.classList.remove('selected');
            }
        });
        
        // Cập nhật giá trị form
        document.getElementById('paymentMethod').value = method;
        
        // Kích hoạt nút thanh toán
        document.getElementById('payButton').disabled = false;
    }
    </script>
</body>
</html>