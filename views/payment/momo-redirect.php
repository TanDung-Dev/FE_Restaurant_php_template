<?php
require_once 'session.php';
checkUserLoggedIn();

// Lấy các tham số từ MoMo trả về
$resultCode = $_GET['resultCode'] ?? null;
$orderId = $_GET['orderId'] ?? '';
$message = $_GET['message'] ?? '';
$extraData = $_GET['extraData'] ?? '';
$transId = $_GET['transId'] ?? '';

// Debug thông tin
error_log("MoMo redirect params: " . json_encode($_GET));

// Giải mã extraData để lấy ID thanh toán
$paymentId = null;
if ($extraData) {
    try {
        $decoded = base64_decode($extraData);
        $paymentData = json_decode($decoded, true);
        $paymentId = $paymentData['payment_id'] ?? null;
        error_log("Decoded payment ID: " . $paymentId);
    } catch (Exception $e) {
        error_log("Error decoding extraData: " . $e->getMessage());
    }
}

// Nếu không có payment ID từ extraData, lấy từ session
if (!$paymentId) {
    $paymentId = $_SESSION['momo_payment_id'] ?? null;
    error_log("Using payment ID from session: " . $paymentId);
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
    error_log("Update payment response: " . json_encode($response));
    
    // Chuyển hướng đến trang thanh toán thành công
    header('Location: /restaurant-website/public/payment/payment-success?id=' . $paymentId);
    exit;
} else {
    // Thanh toán thất bại hoặc bị hủy
    $error = urlencode($message);
    
    // Lấy ID đặt bàn từ thanh toán
    $bookingId = null;
    if ($paymentId) {
        $paymentResponse = apiRequest('/thanh-toan/' . $paymentId, 'GET');
        if ($paymentResponse['success']) {
            $bookingId = $paymentResponse['data']['ID_ThongTinDatBan'];
        }
    }
    
    if ($bookingId) {
        header('Location: /restaurant-website/public/payment?id=' . $bookingId . '&error=' . $error);
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