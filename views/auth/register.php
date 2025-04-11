<?php
// Hiển thị lỗi trong quá trình phát triển
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Biến lưu trữ thông báo
$error_message = '';
$success_message = '';

// Xử lý đăng ký khi form được submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Lấy dữ liệu từ form
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $fullname = $_POST['fullname'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    
    // Kiểm tra mật khẩu xác nhận
    if ($password !== $confirm_password) {
        $error_message = 'Mật khẩu xác nhận không khớp';
    } else {
        // Gọi API đăng ký
        $api_url = 'http://localhost:8000/api/register';
        $data = [
            'TenDangNhap' => $username,
            'MatKhau' => $password,
            'HoVaTen' => $fullname,
            'Email' => $email,
            'Sdt' => $phone
        ];
        
        $options = [
            'http' => [
                'header' => "Content-Type: application/json\r\n",
                'method' => 'POST',
                'content' => json_encode($data),
                'ignore_errors' => true
            ]
        ];
        
        $context = stream_context_create($options);
        $response = file_get_contents($api_url, false, $context);
        
        // Xử lý phản hồi từ API
        if ($response !== false) {
            $result = json_decode($response, true);
            
            if (isset($result['success']) && $result['success']) {
                // Lưu token và thông tin người dùng vào session
                session_start();
                $_SESSION['auth_token'] = $result['data']['access_token'];
                $_SESSION['user'] = $result['data']['user'];
                
                // Hiển thị thông báo thành công và chuyển hướng sau 3 giây
                $success_message = 'Đăng ký thành công! Hệ thống sẽ chuyển hướng sau 3 giây...';
                header("refresh:3;url=index.php"); 
            } else {
                // Hiển thị lỗi
                if (isset($result['errors'])) {
                    $errors = [];
                    foreach ($result['errors'] as $field => $error) {
                        $errors[] = $error[0];
                    }
                    $error_message = implode("<br>", $errors);
                } else {
                    $error_message = $result['message'] ?? 'Đăng ký không thành công';
                }
            }
        } else {
            $error_message = 'Không thể kết nối đến máy chủ';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <!-- ========== Meta Tags ========== -->
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Đăng ký - Hệ thống đặt bàn nhà hàng">
    <!-- ======== Page title ============ -->
    <title>Đăng ký - Hệ thống đặt bàn nhà hàng</title>
    <!-- Stylesheets -->
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
        .register-section {
            padding: 100px 0;
            background-color: #f8f9fa;
        }
        .register-form {
            background: #fff;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0px 0px 20px rgba(0, 0, 0, 0.1);
        }
        .register-form h2 {
            color: #ff5b00;
            margin-bottom: 30px;
            text-align: center;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .register-form .theme-btn {
            width: 100%;
            margin-top: 20px;
        }
        .register-form .login-link {
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
        .success-message {
            color: #28a745;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
            background-color: rgba(40, 167, 69, 0.1);
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
                            <a href="/restaurant-website/public/index.php" class="header-logo">
                                <img src="/restaurant-website/public/assets/img/logo/logo.svg" alt="logo-img">
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Register Section -->
    <section class="register-section">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-6 col-md-8">
                    <div class="register-form">
                        <h2>Đăng Ký Tài Khoản</h2>
                        
                        <?php if (!empty($error_message)): ?>
                            <div class="error-message">
                                <?php echo $error_message; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($success_message)): ?>
                            <div class="success-message">
                                <?php echo $success_message; ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="">
                            <div class="form-group">
                                <label for="username">Tên đăng nhập <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="username" name="username" placeholder="Nhập tên đăng nhập" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="fullname">Họ và tên <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="fullname" name="fullname" placeholder="Nhập họ và tên" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="email">Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="email" name="email" placeholder="Nhập địa chỉ email" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="phone">Số điện thoại <span class="text-danger">*</span></label>
                                <input type="tel" class="form-control" id="phone" name="phone" placeholder="Nhập số điện thoại" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="password">Mật khẩu <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" id="password" name="password" placeholder="Nhập mật khẩu" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="confirm_password">Xác nhận mật khẩu <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Nhập lại mật khẩu" required>
                            </div>
                            
                            <button type="submit" class="theme-btn">
                                <span class="button-content-wrapper">
                                    <span class="button-text">Đăng Ký</span>
                                </span>
                            </button>
                        </form>
                        
                        <div class="login-link">
                            <p>Đã có tài khoản? <a href="/restaurant-website/public/login.php">Đăng nhập</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer Section Start -->
    <footer class="footer-section fix section-bg">
        <!-- Footer content (đơn giản hóa) -->
        <div class="container">
            <div class="footer-bottom-wrapper d-flex align-items-center justify-content-between">
                <p class="wow fadeInLeft" data-wow-delay=".3s">
                    © Copyright <span class="theme-color-3">2025</span> <a href="index.php">Restaurant Booking</a>. All Rights Reserved.
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