<?php
// Bắt đầu phiên làm việc
session_start();



// Kiểm tra người dùng đã đăng nhập chưa
function isLoggedIn() {
    return isset($_SESSION['auth_token']) && !empty($_SESSION['auth_token']);
}

// Lấy thông tin người dùng hiện tại
function getCurrentUser() {
    return $_SESSION['user'] ?? null;
}

// Lấy token xác thực
function getAuthToken() {
    return $_SESSION['auth_token'] ?? null;
}

// Kiểm tra người dùng có phải là admin không
function isAdmin() {
    $user = getCurrentUser();
    return isset($user['Quyen']) && $user['Quyen'] == 1;
}

// Kiểm tra người dùng có quyền truy cập trang admin không
function checkAdminAccess() {
    if (!isLoggedIn() || !isAdmin()) {
        header('Location: login.php?error=noPermission');
        exit;
    }
}

// Kiểm tra người dùng đã đăng nhập, nếu chưa sẽ chuyển hướng đến trang đăng nhập
function checkUserLoggedIn() {
    if (!isLoggedIn()) {
        header('Location: login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
}

// Đăng xuất
function logout() {
    // Gọi API đăng xuất
    $api_url = 'http://localhost:8000/api/logout';
    $token = getAuthToken();
    
    if ($token) {
        $options = [
            'http' => [
                'header' => "Content-Type: application/json\r\n" .
                            "Authorization: Bearer " . $token . "\r\n",
                'method' => 'POST',
                'content' => json_encode([]),
                'ignore_errors' => true
            ]
        ];
        
        $context = stream_context_create($options);
        file_get_contents($api_url, false, $context);
    }
    
    // Xóa session
    session_unset();
    session_destroy();
}

// Tạo yêu cầu API có xác thực
function apiRequest($endpoint, $method = 'GET', $data = null) {
    $api_base_url = 'http://localhost:8000/api';
    $token = getAuthToken();
    
    $options = [
        'http' => [
            'header' => "Content-Type: application/json\r\n" .
                        ($token ? "Authorization: Bearer " . $token . "\r\n" : ""),
            'method' => $method,
            'ignore_errors' => true
        ]
    ];
    
    if ($data && ($method === 'POST' || $method === 'PUT')) {
        $options['http']['content'] = json_encode($data);
    }
    
    $context = stream_context_create($options);
    $response = file_get_contents($api_base_url . $endpoint, false, $context);
    
    return json_decode($response, true);
}