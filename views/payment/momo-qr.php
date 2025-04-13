<?php
echo "<div style='display:none'>";
echo "Debug: QR Data = " . htmlspecialchars($qrData) . "<br>";
echo "Debug: Payment ID = " . $paymentId . "<br>";
echo "Debug: Amount = " . $amount . "<br>";
echo "</div>";
require_once 'session.php';
checkUserLoggedIn();

// Lấy ID thanh toán từ URL
$paymentId = $_GET['id'] ?? null;

if (!$paymentId) {
    header('Location: /restaurant-website/public/booking/my-bookings?error=missing_payment_id');
    exit;
}

// Lấy thông tin thanh toán
$paymentResponse = apiRequest('/thanh-toan/' . $paymentId, 'GET');
$payment = $paymentResponse['data'] ?? null;

if (!$payment) {
    header('Location: /restaurant-website/public/booking/my-bookings?error=payment_not_found');
    exit;
}

// Lấy thông tin đặt bàn
$bookingId = $payment['ID_ThongTinDatBan'] ?? 0;
$amount = $payment['SoLuong'] ?? 0;

$phoneNumber = "0917221066"; // Số điện thoại MoMo test
$orderId = "ORDER_" . $bookingId . "_" . time();


// Tạo mã QR với định dạng phù hợp với MoMo
$orderId = "ORDER_" . $bookingId . "_" . time();
$description = "Thanh toan dat ban #" . $bookingId;

// Tạo mã QR theo định dạng chính thức của MoMo
$qrData = "2|99|".$phoneNumber."|".$orderId."|".$amount."|Thanh toan don dat ban #".$bookingId."|transfer_myqr|0|0|||||test|test|0|0|0";
$qrCode = "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . urlencode($qrData);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thanh toán MoMo - Quét mã QR</title>
    <link rel="shortcut icon" href="/restaurant-website/public/assets/img/logo/favicon.svg">
    <link rel="stylesheet" href="/restaurant-website/public/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="/restaurant-website/public/assets/css/font-awesome.css">
    <link rel="stylesheet" href="/restaurant-website/public/assets/css/animate.css">
    <link rel="stylesheet" href="/restaurant-website/public/assets/css/main.css">
    <style>
        .qr-section {
            padding: 80px 0;
            background-color: #f8f9fa;
        }
        .qr-container {
            background-color: #fff;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            text-align: center;
        }
        .qr-title {
            font-size: 24px;
            margin-bottom: 20px;
            color: #ae2070;
        }
        .qr-instructions {
            margin-bottom: 30px;
        }
        .qr-image {
            margin: 0 auto 30px;
            max-width: 250px;
        }
        .qr-info {
            margin-top: 20px;
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 8px;
        }
        .qr-info-item {
            margin-bottom: 10px;
        }
        .qr-info-label {
            font-weight: 600;
            color: #555;
        }
        .qr-actions {
            margin-top: 30px;
        }
        .btn-cancel {
            background-color: #6c757d;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            margin-right: 10px;
        }
        .btn-check {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
        }
        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #ae2070;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 2s linear infinite;
            margin: 0 auto 20px;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .momo-logo {
            max-width: 120px;
            margin-bottom: 20px;
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
                        <h2>Thanh toán MoMo</h2>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <section class="qr-section">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-6">
                    <div class="qr-container">
                        <img src="/restaurant-website/public/assets/img/payment/momo.png" alt="MoMo" class="momo-logo">
                        <h3 class="qr-title">Quét mã QR để thanh toán</h3>
                        
                        <div class="qr-instructions">
                            <p>Vui lòng sử dụng ứng dụng MoMo Test để quét mã QR bên dưới và hoàn tất thanh toán.</p>
                            <p><small>Lưu ý: Chỉ quét được bằng ứng dụng MoMo Test, không phải ứng dụng MoMo thông thường.</small></p>
                        </div>
                        
                        <div class="qr-image">
                            <img src="<?php echo $qrCode; ?>" alt="Mã QR MoMo" class="img-fluid">
                        </div>
                        
                        <div class="qr-info">
                            <div class="qr-info-item">
                                <span class="qr-info-label">Số tiền:</span>
                                <span><?php echo number_format($amount, 0, ',', '.'); ?>đ</span>
                            </div>
                            <div class="qr-info-item">
                                <span class="qr-info-label">Trạng thái:</span>
                                <span id="payment-status">Đang chờ thanh toán</span>
                            </div>
                        </div>
                        
                        <div id="loading" style="display: none;">
                            <div class="spinner"></div>
                            <p>Đang kiểm tra trạng thái thanh toán...</p>
                        </div>
                        
                        <div class="qr-actions">
                            <a href="/restaurant-website/public/booking/my-bookings" class="btn-cancel">Hủy</a>
                            <button id="check-payment" class="btn-check">Kiểm tra thanh toán</button>
                        </div>

                        <!-- Giả lập thanh toán -->
                <div class="mt-4 pt-3 border-top">
                    <p><small>Dành cho testing:</small></p>
                    <form method="POST" action="/restaurant-website/public/api-handler.php">
                        <input type="hidden" name="action" value="simulate_momo_payment">
                        <input type="hidden" name="payment_id" value="<?php echo $paymentId; ?>">
                        <button type="submit" class="btn btn-sm btn-warning">Giả lập thanh toán thành công</button>
                    </form>
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
    
    <script>
    $(document).ready(function() {
        const paymentId = '<?php echo $paymentId; ?>';
        let checkInterval;
        
        function checkPaymentStatus() {
            $('#loading').show();
            $('#check-payment').prop('disabled', true);
            
            $.ajax({
                url: '/restaurant-website/public/api-handler.php',
                type: 'GET',
                data: {
                    action: 'check_momo_payment',
                    id: paymentId
                },
                dataType: 'json',
                success: function(response) {
                    $('#loading').hide();
                    $('#check-payment').prop('disabled', false);
                    
                    if (response.success) {
                        if (response.data.trang_thai_thanh_toan == 1) {
                            // Thanh toán thành công
                            $('#payment-status').text('Đã thanh toán').addClass('text-success');
                            clearInterval(checkInterval);
                            
                            // Chuyển hướng đến trang thanh toán thành công
                            setTimeout(function() {
                                window.location.href = '/restaurant-website/public/payment/payment-success?id=' + paymentId;
                            }, 2000);
                        } else {
                            // Vẫn đang chờ thanh toán
                            $('#payment-status').text('Đang chờ thanh toán');
                        }
                    } else {
                        $('#payment-status').text('Lỗi kiểm tra thanh toán').addClass('text-danger');
                    }
                },
                error: function() {
                    $('#loading').hide();
                    $('#check-payment').prop('disabled', false);
                    $('#payment-status').text('Lỗi kết nối server').addClass('text-danger');
                }
            });
        }
        
        // Kiểm tra trạng thái thanh toán mỗi 5 giây
        checkInterval = setInterval(checkPaymentStatus, 5000);
        
        // Kiểm tra thủ công khi nhấn nút
        $('#check-payment').click(checkPaymentStatus);
    });
    </script>
</body>
</html>