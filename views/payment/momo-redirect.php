<?php
require_once 'session.php';
checkUserLoggedIn();

// Lấy thông số từ MoMo trả về
$resultCode = $_GET['resultCode'] ?? null;
$message = $_GET['message'] ?? '';
$orderId = $_GET['orderId'] ?? '';
$transId = $_GET['transId'] ?? '';
$amount = $_GET['amount'] ?? 0;
$extraData = $_GET['extraData'] ?? '';

// Lấy ID thanh toán từ session
$paymentId = $_SESSION['momo_payment_id'] ?? null;

if (!$paymentId) {
    // Không tìm thấy thông tin thanh toán trong session
    header('Location: /restaurant-website/public/booking/my-bookings?error=invalid_payment');
    exit;
}

// Xóa ID thanh toán khỏi session
unset($_SESSION['momo_payment_id']);

// Kiểm tra kết quả từ MoMo
if ($resultCode === '0') { // Thanh toán thành công
    // Gọi API để kiểm tra trạng thái thanh toán
    $response = apiRequest('/thanh-toan/momo/status/' . $paymentId, 'GET');
    
    if ($response && $response['success'] && $response['data']['trang_thai_thanh_toan'] == 1) {
        // Thanh toán đã được xác nhận trên hệ thống
        header('Location: /restaurant-website/public/payment/payment-success?id=' . $paymentId);
        exit;
    } else {
        // Kiểm tra thêm thông qua IPN - có thể đã được cập nhật trước khi redirect
        sleep(2); // Đợi IPN xử lý
        
        $retryResponse = apiRequest('/thanh-toan/momo/status/' . $paymentId, 'GET');
        
        if ($retryResponse && $retryResponse['success'] && $retryResponse['data']['trang_thai_thanh_toan'] == 1) {
            // Thanh toán đã được xác nhận sau khi đợi
            header('Location: /restaurant-website/public/payment/payment-success?id=' . $paymentId);
            exit;
        } else {
            // Vẫn chưa cập nhật trạng thái - có thể có vấn đề ở IPN
            // Cập nhật trạng thái bằng tay nếu cần
            $updateData = [
                'TrangThaiThanhToan' => 1,
                'MaGiaoDich' => $transId
            ];
            apiRequest('/thanh-toan/' . $paymentId, 'POST', $updateData);
            
            header('Location: /restaurant-website/public/payment/payment-success?id=' . $paymentId);
            exit;
        }
    }
} else {
    // Thanh toán thất bại hoặc bị hủy
    // Lấy ID đặt bàn từ thanh toán
    $bookingResponse = apiRequest('/thanh-toan/' . $paymentId, 'GET');
    $bookingId = null;
    
    if ($bookingResponse && $bookingResponse['success']) {
        $bookingId = $bookingResponse['data']['ID_ThongTinDatBan'];
    }
    
    if ($bookingId) {
        $errorMessage = urlencode('Thanh toán MoMo không thành công: ' . $message);
        header('Location: /restaurant-website/public/payment?id=' . $bookingId . '&momo_error=' . $errorMessage);
    } else {
        header('Location: /restaurant-website/public/booking/my-bookings?error=payment_failed');
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đang xử lý thanh toán - MoMo</title>
    <link rel="stylesheet" href="/restaurant-website/public/assets/css/bootstrap.min.css">
    <style>
        body {
            background-color: #f5f5f5;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .loading-container {
            text-align: center;
            background-color: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
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
            max-width: 150px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="loading-container">
        <img src="/restaurant-website/public/assets/img/payment/momo-logo.png" alt="MoMo" class="momo-logo">
        <div class="spinner"></div>
        <h3>Đang xử lý thanh toán...</h3>
        <p>Vui lòng chờ trong giây lát, hệ thống đang xác nhận thanh toán của bạn.</p>
        <?php if ($resultCode === '0'): ?>
            <p class="text-success">Thanh toán thành công. Số tiền: <?php echo number_format($amount, 0, ',', '.'); ?>đ</p>
        <?php else: ?>
            <p class="text-danger">Thanh toán không thành công: <?php echo htmlspecialchars($message); ?></p>
        <?php endif; ?>
    </div>
    
    <script>
        // Auto redirect after 5 seconds if not redirected yet
        setTimeout(function() {
            <?php if ($resultCode === '0'): ?>
                window.location.href = '/restaurant-website/public/payment/payment-success?id=<?php echo $paymentId; ?>';
            <?php else: ?>
                window.location.href = '/restaurant-website/public/booking/my-bookings';
            <?php endif; ?>
        }, 5000);
    </script>
</body>
</html>