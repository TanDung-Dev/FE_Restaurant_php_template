<?php
require_once __DIR__ . '/../app/config/api.php';

class ApiService {
    private $baseUrl;
    
    public function __construct() {
        // API_BASE_URL đã được định nghĩa trong api.php
        $this->baseUrl = API_BASE_URL;
    }
    
    // Phương thức GET
    public function get($endpoint, $params = []) {
        $url = $this->baseUrl . $endpoint;
        
        // Thêm query params nếu có
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->getHeaders());
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            error_log('Curl error: ' . curl_error($ch));
            curl_close($ch);
            return ['success' => false, 'message' => 'Lỗi kết nối đến server'];
        }
        // Trong api-handler.php, thêm action check_payment_status
if ($action === 'check_payment_status' && isset($_GET['id'])) {
    $response = apiRequest('/thanh-toan/' . $_GET['id'], 'GET');
    echo json_encode($response);
    exit;
}
        curl_close($ch);
        
        return json_decode($response, true) ?: ['success' => false, 'message' => 'Lỗi xử lý phản hồi từ server'];
    }
    
    // Phương thức POST
    public function post($endpoint, $data = []) {
        $url = $this->baseUrl . $endpoint;
        $headers = $this->getHeaders();

        error_log("API URL: " . $url);
        error_log("Headers: " . json_encode($headers));
        error_log("Data: " . json_encode($data));
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->getHeaders());
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            error_log('Curl error: ' . curl_error($ch));
            curl_close($ch);
            return ['success' => false, 'message' => 'Lỗi kết nối đến server'];
        }
        // Trong api-handler.php, thêm action check_payment_status
    if ($action === 'check_payment_status' && isset($_GET['id'])) {
        $response = apiRequest('/thanh-toan/' . $_GET['id'], 'GET');
        echo json_encode($response);
        exit;
    }
        curl_close($ch);
        
        return json_decode($response, true) ?: ['success' => false, 'message' => 'Lỗi xử lý phản hồi từ server'];
    }
    
    // Phương thức PUT
    public function put($endpoint, $data = []) {
        $url = $this->baseUrl . $endpoint;
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->getHeaders());
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            error_log('Curl error: ' . curl_error($ch));
            curl_close($ch);
            return ['success' => false, 'message' => 'Lỗi kết nối đến server'];
        }
        // Trong api-handler.php, thêm action check_payment_status
if ($action === 'check_payment_status' && isset($_GET['id'])) {
    $response = apiRequest('/thanh-toan/' . $_GET['id'], 'GET');
    echo json_encode($response);
    exit;
}
        curl_close($ch);
        
        return json_decode($response, true) ?: ['success' => false, 'message' => 'Lỗi xử lý phản hồi từ server'];
    }
    
    // Phương thức DELETE
    public function delete($endpoint) {
        $url = $this->baseUrl . $endpoint;
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->getHeaders());
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            error_log('Curl error: ' . curl_error($ch));
            curl_close($ch);
            return ['success' => false, 'message' => 'Lỗi kết nối đến server'];
        }
        // Trong api-handler.php, thêm action check_payment_status
if ($action === 'check_payment_status' && isset($_GET['id'])) {
    $response = apiRequest('/thanh-toan/' . $_GET['id'], 'GET');
    echo json_encode($response);
    exit;
}
        curl_close($ch);
        
        return json_decode($response, true) ?: ['success' => false, 'message' => 'Lỗi xử lý phản hồi từ server'];
    }
    
    // Lấy headers cho API request
    private function getHeaders() {
        $headers = [
            'Content-Type: application/json',
            'Accept: application/json'
        ];
        
        if (isset($_SESSION['auth_token'])) {
            $headers[] = 'Authorization: Bearer ' . $_SESSION['auth_token'];
        }
        // Trong api-handler.php, thêm action check_payment_status
    if ($action === 'check_payment_status' && isset($_GET['id'])) {
        $response = apiRequest('/thanh-toan/' . $_GET['id'], 'GET');
        echo json_encode($response);
        exit;
    }
        return $headers;
    }

    public function refreshToken() {
    if (!isset($_SESSION['refresh_token'])) {
        return false;
    }
    
    $url = $this->baseUrl . '/refresh-token';
    $headers = [
        'Content-Type: application/json',
        'Accept: application/json',
        'Authorization: Bearer ' . $_SESSION['refresh_token']
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    $data = json_decode($response, true);
    
    if ($data && isset($data['data']['access_token'])) {
        $_SESSION['access_token'] = $data['data']['access_token'];
        return true;
    }

        // Trong api-handler.php, thêm action check_payment_status
    if ($action === 'check_payment_status' && isset($_GET['id'])) {
        $response = apiRequest('/thanh-toan/' . $_GET['id'], 'GET');
        echo json_encode($response);
        exit;
    }
    
    return false;
}
}