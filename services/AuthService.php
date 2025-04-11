<?php
require_once __DIR__ . '/ApiService.php';

class AuthService {
    private $api;
    
    public function __construct() {
        $this->api = new ApiService();
    }
    
    public function login($username, $password) {
        $response = $this->api->post('/login', [
            'TenDangNhap' => $username,
            'MatKhau' => $password
        ]);
        
        if ($response['status'] === 200 && isset($response['data']['success']) && $response['data']['success']) {
            // Save token in session
            $_SESSION['auth_token'] = $response['data']['data']['access_token'];
            $_SESSION['user'] = $response['data']['data']['user'];
            return true;
        }
        
        return false;
    }
    
    public function register($data) {
        $response = $this->api->post('/register', $data);
        
        if ($response['status'] === 201 && isset($response['data']['success']) && $response['data']['success']) {
            // Save token in session
            $_SESSION['auth_token'] = $response['data']['data']['access_token'];
            $_SESSION['user'] = $response['data']['data']['user'];
            return true;
        }
        
        return false;
    }
    
    public function logout() {
        if (isset($_SESSION['auth_token'])) {
            $this->api->post('/logout', []);
            unset($_SESSION['auth_token']);
            unset($_SESSION['user']);
        }
    }
    
    public function isLoggedIn() {
        return isset($_SESSION['auth_token']);
    }
    
    public function getCurrentUser() {
        return isset($_SESSION['user']) ? $_SESSION['user'] : null;
    }
}