<?php
require_once 'session.php';
checkUserLoggedIn();

$amount = $_GET['amount'] ?? 0;
$orderId = $_GET['orderId'] ?? 0;

if (!$amount || !$orderId) {
    header('Location: /restaurant-website/public/booking/my-bookings');
    exit;
}

// Trong thực tế, ở đây sẽ tích hợp API MoMo để tạo thanh toán
// Demo sẽ giả lập quá trình và chuyển hướng người dùng sang trang thành công

// Giả lập quá trình thanh toán MoMo (3 giây)
sleep(3);

// Lấy dữ liệu thanh toán từ session
$paymentData = $_SESSION['pending_payment'] ?? null;

if ($paymentData) {
    // Gọi API thanh toán
    $response = apiRequest('/thanh-toan', 'POST', $paymentData);
    
    // Xóa dữ liệu thanh toán từ session
    unset($_SESSION['pending_payment']);
    
    // Chuyển hướng đến trang thành công
    header('Location: /restaurant-website/public/payment/payment-success?id=' . $orderId);
    exit;
} else {
    // Có lỗi xảy ra
    header('Location: /restaurant-website/public/payment?id=' . $orderId . '&error=payment_failed');
    exit;
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đang chuyển hướng thanh toán - MoMo</title>
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
        <h3>Đang chuyển hướng đến MoMo...</h3>
        <p>Vui lòng không đóng trang này.</p>
        <p>Số tiền: <?php echo number_format($amount, 0, ',', '.'); ?>đ</p>
    </div>
</body>
</html>