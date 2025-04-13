<?php
require_once __DIR__ . '/../app/config/api.php';
require_once __DIR__ . '/ApiService.php';

class MoMoService {
    private $apiService;
    private $endpoint;
    private $partnerCode;
    private $accessKey;
    private $secretKey;
    private $redirectUrl;
    private $ipnUrl;

    public function __construct() {
        $this->apiService = new ApiService();
        
        // Cấu hình MoMo - lấy từ .env hoặc cấu hình cố định
        $this->endpoint = 'https://test-payment.momo.vn/v2/gateway/api/create';
        $this->partnerCode = 'MOMO';  // Thay bằng MOMO_PARTNER_CODE từ .env
        $this->accessKey = 'F8BBA842ECF85';     // Thay bằng MOMO_ACCESS_KEY từ .env
        $this->secretKey = 'K951B6PE1waDMi640xX08PD3vg6EkVlz';  // Thay bằng MOMO_SECRET_KEY từ .env
        $this->redirectUrl = 'http://localhost/restaurant-website/public/payment/momo-redirect.php';
        $this->ipnUrl = 'http://localhost:8000/api/momo/ipn';
    }

    /**
     * Tạo thanh toán MoMo
     */
    public function createPayment($bookingId) {
        try {
            // Lấy thông tin đặt bàn
            $bookingResponse = $this->apiRequest('/dat-ban/' . $bookingId, 'GET');
            if (!isset($bookingResponse['success']) || !$bookingResponse['success']) {
                return [
                    'success' => false,
                    'message' => 'Không tìm thấy thông tin đặt bàn'
                ];
            }
            
            $booking = $bookingResponse['data'];
            
            // Lấy tổng tiền món ăn đã đặt
            $orderedFoodsResponse = $this->apiRequest('/chi-tiet-dat-mon?id_thongtin_datban=' . $bookingId, 'GET');
            $totalAmount = 0;
            
            if (isset($orderedFoodsResponse['success']) && $orderedFoodsResponse['success']) {
                foreach ($orderedFoodsResponse['data'] as $food) {
                    $totalAmount += $food['ThanhTien'];
                }
            }
            
            if ($totalAmount <= 0) {
                return [
                    'success' => false,
                    'message' => 'Chưa có món ăn nào được đặt'
                ];
            }
            
            // Tạo thanh toán trước
            $paymentData = [
                'ID_ThongTinDatBan' => $bookingId,
                'PhuongThucThanhToan' => 4, // 4: MoMo
                'SoLuong' => $totalAmount,
                'TrangThaiThanhToan' => 0 // 0: Chưa thanh toán
            ];
            
            $paymentResponse = $this->apiRequest('/thanh-toan', 'POST', $paymentData);
            
            if (!isset($paymentResponse['success']) || !$paymentResponse['success']) {
                return [
                    'success' => false,
                    'message' => $paymentResponse['message'] ?? 'Không thể tạo thanh toán'
                ];
            }
            
            $payment = $paymentResponse['data'];
            $paymentId = $payment['ID_ThanhToan'];
            
            // Tạo dữ liệu thanh toán MoMo
            $orderId = "ORDER_" . $bookingId . "_" . time();
            $requestId = time() . "";
            $orderInfo = "Thanh toán đặt bàn #" . $bookingId;
            
            // Extra data để xác nhận sau khi thanh toán
            $extraData = json_encode(['ID_ThanhToan' => $paymentId]);
            
            // Tạo chuỗi ký (raw hash)
            $rawHash = "accessKey=" . $this->accessKey . 
                      "&amount=" . $totalAmount . 
                      "&extraData=" . $extraData . 
                      "&ipnUrl=" . $this->ipnUrl . 
                      "&orderId=" . $orderId . 
                      "&orderInfo=" . $orderInfo . 
                      "&partnerCode=" . $this->partnerCode . 
                      "&redirectUrl=" . $this->redirectUrl . 
                      "&requestId=" . $requestId . 
                      "&requestType=captureWallet";
            
            // Tạo chữ ký
            $signature = hash_hmac("sha256", $rawHash, $this->secretKey);
            
            // Dữ liệu gửi tới MoMo
            $rawData = [
                'partnerCode' => $this->partnerCode,
                'accessKey' => $this->accessKey,
                'requestId' => $requestId,
                'amount' => $totalAmount,
                'orderId' => $orderId,
                'orderInfo' => $orderInfo,
                'redirectUrl' => $this->redirectUrl,
                'ipnUrl' => $this->ipnUrl,
                'extraData' => $extraData,
                'requestType' => 'captureWallet',
                'signature' => $signature
            ];
            
            // Gửi request đến MoMo
            $ch = curl_init($this->endpoint);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($rawData));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            $result = curl_exec($ch);
            
            if (curl_errno($ch)) {
                error_log('Curl error: ' . curl_error($ch));
                curl_close($ch);
                return ['success' => false, 'message' => 'Lỗi kết nối đến MoMo'];
            }
            
            curl_close($ch);
            $response = json_decode($result, true);
            
            // Cập nhật thông tin thanh toán
            if (isset($response['payUrl'])) {
                // Cập nhật thông tin MoMo vào bản ghi thanh toán
                $updateData = [
                    'MoMo_RequestId' => $requestId,
                    'MoMo_OrderId' => $orderId,
                    'MoMo_PaymentUrl' => $response['payUrl'],
                    'MoMo_ResultCode' => $response['resultCode'] ?? null,
                    'MoMo_Message' => $response['message'] ?? null,
                    'MoMo_ExtraData' => $extraData
                ];
                
                $this->apiRequest('/thanh-toan/' . $paymentId, 'POST', $updateData);
                
                return [
                    'success' => true,
                    'message' => 'Tạo thanh toán MoMo thành công',
                    'data' => [
                        'id_thanh_toan' => $paymentId,
                        'paymentUrl' => $response['payUrl'],
                        'qrCodeUrl' => $response['qrCodeUrl'] ?? null
                    ]
                ];
            } else {
                return [
                    'success' => false,
                    'message' => $response['message'] ?? 'Lỗi tạo thanh toán MoMo'
                ];
            }
        } catch (Exception $e) {
            error_log('Error in MoMo payment: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Lỗi xử lý thanh toán: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Kiểm tra trạng thái thanh toán
     */
    public function checkPaymentStatus($idThanhToan) {
        return $this->apiRequest('/thanh-toan/' . $idThanhToan, 'GET');
    }

    /**
     * Gọi API backend
     */
    private function apiRequest($endpoint, $method = 'GET', $data = null) {
        $url = API_BASE_URL . $endpoint;
        $headers = [
            'Content-Type: application/json',
            'Accept: application/json'
        ];
        
        // Thêm token nếu có
        if (isset($_SESSION['auth_token'])) {
            $headers[] = 'Authorization: Bearer ' . $_SESSION['auth_token'];
        }
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        } else if ($method === 'PUT') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        } else if ($method === 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        }
        
        $response = curl_exec($ch);
        
        if (curl_errno($ch)) {
            error_log('Curl error: ' . curl_error($ch));
            curl_close($ch);
            return ['success' => false, 'message' => 'Lỗi kết nối đến server'];
        }
        
        curl_close($ch);
        
        return json_decode($response, true) ?: [
            'success' => false, 
            'message' => 'Lỗi xử lý phản hồi từ server'
        ];
    }
}