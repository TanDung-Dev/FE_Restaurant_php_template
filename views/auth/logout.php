<?php
// Bắt đầu session (nếu chưa bắt đầu)
session_start();

// Xóa tất cả biến session
$_SESSION = array();

// Hủy session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-42000, '/');
}

// Hủy session
session_destroy();

// Chuyển hướng về trang chủ
header('Location: /restaurant-website/public/');
exit;
?>