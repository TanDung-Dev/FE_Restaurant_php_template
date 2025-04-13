<?php
require_once 'session.php';
checkUserLoggedIn();

// Lấy các tham số từ MoMo trả về
$resultCode = $_GET['resultCode'] ?? null;
$orderId = $_GET['orderId'] ?? '';
$message = $_GET['message'] ?? '';
$extraData = $_GET['extraData'] ?? '';
$transId = $_GET['transId'] ?? '';

// Giải mã extraData để lấy ID thanh toán
if ($extraData) {
    $paymentData = json_decode($extraData, true);
    $paymentId = $paymentData['ID_ThanhToan'] ?? null;
} else {
    // Nếu không có extraData, lấy từ session
    $paymentId = $_SESSION['momo_payment_id'] ?? null;
}

// Kiểm tra kết quả từ MoMo
if ($resultCode == 0 && $paymentId) {
    // Thanh toán thành công, cập nhật trạng thái trong database
    $updateData = [
        'TrangThaiThanhToan' => 1, // 1: Đã thanh toán
        'NgayThanhToan' => date('Y-m-d H:i:s'),
        'MaGiaoDich' => $transId,
        'MoMo_TransId' => $transId,
        'MoMo_ResultCode' => $resultCode,
        'MoMo_Message' => $message
    ];
    
    // Gọi API cập nhật trạng thái thanh toán
    $response = apiRequest('/thanh-toan/' . $paymentId, 'POST', $updateData);
    
    // Chuyển hướng đến trang thanh toán thành công
    header('Location: /restaurant-website/public/payment/payment-success?id=' . $paymentId);
    exit;
} else {
    // Thanh toán thất bại hoặc bị hủy
    $error = urlencode($message);
    header('Location: /restaurant-website/public/payment?error=' . $error);
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
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-color: #f8f9fa;
        }
        .loading-container {
            text-align: center;
            background: white;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
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
    </style>
</head>
<body>
    <div class="loading-container">
        <div class="spinner"></div>
        <h3>Đang xử lý thanh toán...</h3>
        <p>Vui lòng đợi trong giây lát.</p>
    </div>
</body>
</html>