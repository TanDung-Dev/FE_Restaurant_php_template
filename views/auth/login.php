<?php
// Hiển thị lỗi trong quá trình phát triển
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Biến lưu trữ thông báo lỗi
$error_message = '';
$debug_info = '';

// Xử lý đăng nhập khi form được submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Lấy dữ liệu từ form
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Gọi API đăng nhập
    $api_url = 'http://localhost:8000/api/login';
    $data = [
        'TenDangNhap' => $username,
        'MatKhau' => $password
    ];
    
    // Sử dụng cURL thay vì file_get_contents
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $api_url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5); 
    curl_setopt($curl, CURLOPT_TIMEOUT, 5);
    
    $response = curl_exec($curl);
    $err = curl_error($curl);
    $info = curl_getinfo($curl);
    curl_close($curl);
    
    if ($err) {
        $error_message = "Không thể kết nối đến máy chủ API";
        $debug_info = "Lỗi cURL: " . $err;
    } else {
        $result = json_decode($response, true);
        
        if (isset($result['success']) && $result['success']) {
            // Lưu token và thông tin người dùng vào session
            session_start();
            $_SESSION['auth_token'] = $result['data']['access_token'];
            $_SESSION['user'] = $result['data']['user'];
            
            // Chuyển hướng về trang chủ
            header('Location: /restaurant-website/public/');
            exit;
        } else {
            // Hiển thị lỗi
            $error_message = $result['message'] ?? 'Đăng nhập không thành công';
            $debug_info = "Mã trạng thái HTTP: " . $info['http_code'] . ", Phản hồi: " . $response;
        }
    }
}

$redirect = isset($_GET['redirect']) ? $_GET['redirect'] : 'index.php';
$error_type = isset($_GET['error']) ? $_GET['error'] : '';

if ($error_type == 'noPermission') {
    $error_message = 'Bạn không có quyền truy cập vào trang này.';
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <!-- ========== Meta Tags ========== -->
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Đăng nhập - Hệ thống đặt bàn nhà hàng">
    <!-- ======== Page title ============ -->
    <title>Đăng nhập - Hệ thống đặt bàn nhà hàng</title>
    <!-- Đường dẫn đã đúng - giữ nguyên -->
    <link rel="shortcut icon" href="/restaurant-website/public/assets/img/logo/favicon.svg">
    <link rel="stylesheet" href="/restaurant-website/public/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="/restaurant-website/public/assets/css/font-awesome.css">
    <link rel="stylesheet" href="/restaurant-website/public/assets/css/animate.css">
    <link rel="stylesheet" href="/restaurant-website/public/assets/css/magnific-popup.css">
    <link rel="stylesheet" href="/restaurant-website/public/assets/css/meanmenu.css">
    <link rel="stylesheet" href="/restaurant-website/public/assets/css/swiper-bundle.min.css">
    <link rel="stylesheet" href="/restaurant-website/public/assets/css/nice-select.css">
    <link rel="stylesheet" href="/restaurant-website/public/assets/css/main.css">
    <style>
        .login-section {
            padding: 100px 0;
            background-color: #f8f9fa;
        }
        .login-form {
            background: #fff;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0px 0px 20px rgba(0, 0, 0, 0.1);
        }
        .login-form h2 {
            color: #ff5b00;
            margin-bottom: 30px;
            text-align: center;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .login-form .theme-btn {
            width: 100%;
            margin-top: 20px;
        }
        .login-form .register-link {
            text-align: center;
            margin-top: 20px;
        }
        .error-message {
            color: #dc3545;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
            background-color: rgba(220, 53, 69, 0.1);
        }
        .debug-info {
            margin-top: 20px;
            padding: 10px;
            background-color: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-family: monospace;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <!-- Preloader Start -->
    <div id="preloader" class="preloader">
        <div class="animation-preloader">
            <div class="spinner"></div>
            <div class="txt-loading">
                <span data-text-preloader="F" class="letters-loading">F</span>
                <span data-text-preloader="O" class="letters-loading">O</span>
                <span data-text-preloader="O" class="letters-loading">O</span>
                <span data-text-preloader="D" class="letters-loading">D</span>
                <span data-text-preloader="K" class="letters-loading">K</span>
                <span data-text-preloader="I" class="letters-loading">I</span>
                <span data-text-preloader="N" class="letters-loading">N</span>
                <span data-text-preloader="G" class="letters-loading">G</span>
            </div>
            <p class="text-center">Loading</p>
        </div>
    </div>

    <!-- Header Section (chỉ đơn giản hóa) -->
    <header class="section-bg">
        <div class="header-top">
            <div class="container">
                <div class="header-top-wrapper">
                    <ul>
                        <li><span>100%</span> Secure delivery without contacting the courier</li>
                        <li><i class="fas fa-truck"></i>Track Your Order</li>
                    </ul>
                </div>
            </div>
        </div>
        <div id="header-sticky" class="header-1">
            <div class="container">
                <div class="mega-menu-wrapper">
                    <div class="header-main">
                    <div class="logo">
                        <a href="/restaurant-website/public/" class="header-logo">
                            <img src="/restaurant-website/public/assets/img/logo/logo.svg" alt="logo-img">
                        </a>
                    </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Login Section -->
    <section class="login-section">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-5 col-md-8">
                    <div class="login-form">
                        <h2>Đăng Nhập</h2>
                        
                        <?php if (!empty($error_message)): ?>
                            <div class="error-message">
                                <?php echo $error_message; ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="/restaurant-website/public/login">
                            <div class="form-group">
                                <label for="username">Tên đăng nhập</label>
                                <input type="text" class="form-control" id="username" name="username" placeholder="Nhập tên đăng nhập" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="password">Mật khẩu</label>
                                <input type="password" class="form-control" id="password" name="password" placeholder="Nhập mật khẩu" required>
                            </div>
                            
                            <button type="submit" class="theme-btn">
                                <span class="button-content-wrapper">
                                    <span class="button-text">Đăng Nhập</span>
                                </span>
                            </button>
                        </form>
                        
                        <div class="register-link">
                            <p>Chưa có tài khoản? <a href="/restaurant-website/public/register">Đăng ký ngay</a></p>
                        </div>
                        
                        <?php if (!empty($debug_info)): ?>
                            <div class="debug-info">
                                <h5>Debug Info (Chỉ hiển thị trong môi trường phát triển)</h5>
                                <pre><?php echo $debug_info; ?></pre>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer Section Start -->
    <footer class="footer-section fix section-bg">
        <div class="container">
            <div class="footer-bottom-wrapper d-flex align-items-center justify-content-between">
                <p class="wow fadeInLeft" data-wow-delay=".3s">
                    © Copyright <span class="theme-color-3">2025</span> <a href="/restaurant-website/public/">Restaurant Booking</a>. All Rights Reserved.
                </p>
            </div>
        </div>
    </footer>

    <!-- Back to top area start here -->
    <div class="scroll-up">
        <svg class="scroll-circle svg-content" width="100%" height="100%" viewBox="-1 -1 102 102">
            <path d="M50,1 a49,49 0 0,1 0,98 a49,49 0 0,1 0,-98"/>
        </svg>
    </div>

    <!-- All JS Plugins -->
    <script src="/restaurant-website/public/assets/js/jquery-3.7.1.min.js"></script>
    <script src="/restaurant-website/public/assets/js/viewport.jquery.js"></script>
    <script src="/restaurant-website/public/assets/js/bootstrap.bundle.min.js"></script>
    <script src="/restaurant-website/public/assets/js/jquery.nice-select.min.js"></script>
    <script src="/restaurant-website/public/assets/js/jquery.waypoints.js"></script>
    <script src="/restaurant-website/public/assets/js/jquery.counterup.min.js"></script>
    <script src="/restaurant-website/public/assets/js/swiper-bundle.min.js"></script>
    <script src="/restaurant-website/public/assets/js/jquery.meanmenu.min.js"></script>
    <script src="/restaurant-website/public/assets/js/jquery.magnific-popup.min.js"></script>
    <script src="/restaurant-website/public/assets/js/animation.js"></script>
    <script src="/restaurant-website/public/assets/js/wow.min.js"></script>
    <script src="/restaurant-website/public/assets/js/contact-from.js"></script>
    <script src="/restaurant-website/public/assets/js/main.js"></script>
</body>
</html>