<?php
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

// Cấu hình MoMo
$endpoint = 'https://test-payment.momo.vn/v2/gateway/api/create';
$partnerCode = 'MOMOBKUN20180529';
$accessKey = 'klm05TvNBzhg7h7j';
$secretKey = 'at67qH6mk8w5Y1nAyMoYKMWACiEi2bsa';
$redirectUrl = 'http://localhost/restaurant-website/public/payment/momo-redirect';
$ipnUrl = 'http://localhost:8000/api/momo/ipn';

// Tạo dữ liệu thanh toán
$orderId = "ORDER_" . $bookingId . "_" . time();
$requestId = time() . "";
$orderInfo = "Thanh toán đặt bàn #" . $bookingId;
$extraData = base64_encode(json_encode(['payment_id' => $paymentId]));

// Tạo chữ ký
$rawHash = "accessKey=" . $accessKey . 
           "&amount=" . $amount . 
           "&extraData=" . $extraData . 
           "&ipnUrl=" . $ipnUrl . 
           "&orderId=" . $orderId . 
           "&orderInfo=" . $orderInfo . 
           "&partnerCode=" . $partnerCode . 
           "&redirectUrl=" . $redirectUrl . 
           "&requestId=" . $requestId . 
           "&requestType=captureWallet";

$signature = hash_hmac("sha256", $rawHash, $secretKey);

// Dữ liệu gửi tới MoMo
$data = [
    'partnerCode' => $partnerCode,
    'accessKey' => $accessKey,
    'requestId' => $requestId,
    'amount' => $amount,
    'orderId' => $orderId,
    'orderInfo' => $orderInfo,
    'redirectUrl' => $redirectUrl,
    'ipnUrl' => $ipnUrl,
    'extraData' => $extraData,
    'requestType' => 'captureWallet',
    'signature' => $signature
];

// Cập nhật thông tin MoMo cho thanh toán
$updateData = [
    'MoMo_RequestId' => $requestId,
    'MoMo_OrderId' => $orderId
];
apiRequest('/thanh-toan/' . $paymentId, 'POST', $updateData);

// Gửi request đến MoMo
$ch = curl_init($endpoint);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$result = curl_exec($ch);

// Debug thông tin
error_log("MoMo request data: " . json_encode($data));
error_log("MoMo response: " . $result);

if (curl_errno($ch)) {
    error_log("Curl error: " . curl_error($ch));
    header('Location: /restaurant-website/public/payment?id=' . $bookingId . '&error=' . urlencode('Lỗi kết nối đến MoMo'));
    exit;
}

curl_close($ch);
$response = json_decode($result, true);

// Chuyển hướng đến trang thanh toán MoMo nếu thành công
if (isset($response['payUrl'])) {
    // Cập nhật URL thanh toán
    $updateData = [
        'MoMo_PaymentUrl' => $response['payUrl']
    ];
    apiRequest('/thanh-toan/' . $paymentId, 'POST', $updateData);
    
    // Chuyển hướng đến trang thanh toán MoMo
    header('Location: ' . $response['payUrl']);
    exit;
} else {
    // Xảy ra lỗi
    $errorMsg = $response['message'] ?? 'Lỗi không xác định';
    header('Location: /restaurant-website/public/payment?id=' . $bookingId . '&error=' . urlencode($errorMsg));
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đang khởi tạo thanh toán MoMo</title>
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
        <h3>Đang khởi tạo thanh toán MoMo...</h3>
        <p>Vui lòng đợi trong giây lát, bạn sẽ được chuyển đến trang thanh toán.</p>
    </div>
</body>
</html>