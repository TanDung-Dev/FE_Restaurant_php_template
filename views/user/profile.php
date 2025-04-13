<?php
// Đảm bảo người dùng đã đăng nhập
if (!isLoggedIn()) {
    header('Location: /restaurant-website/public/login');
    exit;
}

// Lấy thông tin người dùng hiện tại
$user = getCurrentUser();

// Kiểm tra xem có yêu cầu cập nhật thông tin không
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    // Thu thập dữ liệu từ form
    $ho_va_ten = $_POST['ho_va_ten'] ?? '';
    $email = $_POST['email'] ?? '';
    $sdt = $_POST['sdt'] ?? '';
    
    // Dữ liệu gửi đến API
    $formData = [
        'HoVaTen' => $ho_va_ten,
        'Email' => $email,
        'Sdt' => $sdt,
    ];
    
    // Cập nhật mật khẩu nếu được cung cấp
    if (!empty($_POST['mat_khau']) && $_POST['mat_khau'] === $_POST['xac_nhan_mat_khau']) {
        $formData['MatKhau'] = $_POST['mat_khau'];
    } elseif (!empty($_POST['mat_khau'])) {
        $error = 'Mật khẩu và xác nhận mật khẩu không khớp!';
    }
    
    // Gửi yêu cầu cập nhật đến API nếu không có lỗi
    if (empty($error)) {
        $response = apiRequest('/users/' . $user['ID_USER'], 'PUT', $formData);
        
        if ($response['success'] ?? false) {
            $message = 'Cập nhật thông tin thành công!';
            // Cập nhật thông tin người dùng trong session
            $_SESSION['user'] = $response['data'];
            $user = $response['data'];
        } else {
            $error = $response['message'] ?? 'Có lỗi xảy ra khi cập nhật thông tin.';
        }
    }
}

// Lấy danh sách đặt bàn gần đây
$bookingsResponse = apiRequest('/dat-ban?id_user=' . $user['ID_USER'] . '&limit=5', 'GET');
$recentBookings = $bookingsResponse['data'] ?? [];
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <!-- ========== Meta Tags ========== -->
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Thông tin tài khoản - Hệ thống đặt bàn nhà hàng">
    
    <!-- ======== Page title ============ -->
    <title>Thông tin tài khoản - Hệ thống đặt bàn nhà hàng</title>
    
    <!-- ========== Favicon ========== -->
    <link rel="shortcut icon" href="/restaurant-website/public/assets/img/logo/favicon.svg">
    
    <!-- ========== Stylesheets ========== -->
    <link rel="stylesheet" href="/restaurant-website/public/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="/restaurant-website/public/assets/css/font-awesome.css">
    <link rel="stylesheet" href="/restaurant-website/public/assets/css/animate.css">
    <link rel="stylesheet" href="/restaurant-website/public/assets/css/main.css">
    <link rel="stylesheet" href="/restaurant-website/public/style.css">
    
    <style>
        .profile-section {
            padding: 80px 0;
            background-color: #f8f9fa;
        }
        .profile-title {
            margin-bottom: 40px;
        }
        .content-card {
            background: #fff;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
        }
        .profile-img {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 20px;
            border: 5px solid #f1f1f1;
        }
        .profile-info {
            text-align: center;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
            margin-bottom: 20px;
        }
        .profile-name {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 10px;
        }
        .profile-email, .profile-phone {
            color: #666;
            margin-bottom: 5px;
        }
        .profile-role {
            display: inline-block;
            padding: 5px 15px;
            background: #ff5b00;
            color: #fff;
            border-radius: 20px;
            font-size: 14px;
            margin-top: 10px;
        }
        .profile-stats {
            display: flex;
            justify-content: space-around;
            text-align: center;
            margin: 20px 0;
        }
        .stat-item {
            padding: 10px;
        }
        .stat-value {
            font-size: 24px;
            font-weight: 600;
            color: #ff5b00;
        }
        .stat-label {
            font-size: 14px;
            color: #666;
        }
        .recent-bookings {
            margin-top: 30px;
        }
        .booking-item {
            padding: 15px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .booking-item:last-child {
            border-bottom: none;
        }
        .booking-info {
            flex: 1;
        }
        .booking-date {
            font-weight: 600;
            margin-bottom: 5px;
        }
        .booking-status {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 15px;
            font-size: 12px;
            color: #fff;
        }
        .status-pending {
            background-color: #ffc107;
        }
        .status-confirmed {
            background-color: #28a745;
        }
        .status-cancelled {
            background-color: #dc3545;
        }
        .nav-tabs {
            border-bottom: 2px solid #dee2e6;
            margin-bottom: 20px;
        }
        .nav-tabs .nav-item {
            margin-bottom: -2px;
        }
        .nav-tabs .nav-link {
            border: none;
            color: #495057;
            font-weight: 500;
            padding: 12px 20px;
        }
        .nav-tabs .nav-link.active {
            color: #ff5b00;
            background-color: transparent;
            border-bottom: 2px solid #ff5b00;
        }
        .tab-content {
            padding: 15px 0;
        }
    </style>
</head>

<body>
    <!-- Preloader -->
    <div id="preloader" class="preloader">
        <div class="animation-preloader">
            <div class="spinner"></div>
            <div class="txt-loading">
                <span data-text-preloader="P" class="letters-loading">P</span>
                <span data-text-preloader="R" class="letters-loading">R</span>
                <span data-text-preloader="O" class="letters-loading">O</span>
                <span data-text-preloader="F" class="letters-loading">F</span>
                <span data-text-preloader="I" class="letters-loading">I</span>
                <span data-text-preloader="L" class="letters-loading">L</span>
                <span data-text-preloader="E" class="letters-loading">E</span>
            </div>
            <p class="text-center">Loading</p>
        </div>
    </div>

    <!-- Header -->
    <?php include __DIR__ . '/../../app/includes/header.php'; ?>

    <!-- Profile Section -->
    <section class="profile-section">
        <div class="container">
            <div class="profile-title">
                <h2 class="text-center">Thông tin tài khoản</h2>
            </div>
            
            <div class="row">
                <div class="col-lg-4">
                    <div class="content-card">
                        <div class="profile-info">
                          
                            <h3 class="profile-name"><?php echo $user['HoVaTen']; ?></h3>
                            <p class="profile-email"><i class="fas fa-envelope"></i> <?php echo $user['Email']; ?></p>
                            <p class="profile-phone"><i class="fas fa-phone"></i> <?php echo $user['Sdt']; ?></p>
                            <span class="profile-role"><?php echo ($user['Quyen'] == 1) ? 'Admin' : 'Người dùng'; ?></span>
                        </div>
                        
                        <div class="profile-stats">
                            <div class="stat-item">
                                <div class="stat-value"><?php echo count($recentBookings); ?></div>
                                <div class="stat-label">Đặt bàn</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-value"><?php echo date('d/m/Y', strtotime($user['NgayDK'])); ?></div>
                                <div class="stat-label">Ngày đăng ký</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="content-card recent-bookings">
                        <h4>Đặt bàn gần đây</h4>
                        <?php if (empty($recentBookings)): ?>
                            <p class="text-center">Bạn chưa có đơn đặt bàn nào.</p>
                        <?php else: ?>
                            <?php foreach ($recentBookings as $booking): ?>
                                <div class="booking-item">
                                    <div class="booking-info">
                                        <div class="booking-date"><?php echo date('d/m/Y H:i', strtotime($booking['ThoiGianDatBan'])); ?></div>
                                        <div>Số khách: <?php echo $booking['SoLuongKhach']; ?></div>
                                    </div>
                                    <?php
                                    $statusClass = '';
                                    $statusText = '';
                                    
                                    switch ($booking['TrangThai']) {
                                        case 0:
                                            $statusClass = 'status-pending';
                                            $statusText = 'Chờ xác nhận';
                                            break;
                                        case 1:
                                            $statusClass = 'status-confirmed';
                                            $statusText = 'Đã xác nhận';
                                            break;
                                        case 2:
                                            $statusClass = 'status-cancelled';
                                            $statusText = 'Đã hủy';
                                            break;
                                    }
                                    ?>
                                    <span class="booking-status <?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                                </div>
                            <?php endforeach; ?>
                            <div class="text-center mt-3">
                                <a href="/restaurant-website/public/booking/my-bookings" class="theme-btn">Xem tất cả</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="col-lg-8">
                    <div class="content-card">
                        <ul class="nav nav-tabs" id="profileTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="info-tab" data-bs-toggle="tab" data-bs-target="#info" type="button" role="tab" aria-controls="info" aria-selected="true">Thông tin cá nhân</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="security-tab" data-bs-toggle="tab" data-bs-target="#security" type="button" role="tab" aria-controls="security" aria-selected="false">Bảo mật</button>
                            </li>
                        </ul>
                        
                        <?php if (!empty($message)): ?>
                            <div class="alert alert-success">
                                <?php echo $message; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger">
                                <?php echo $error; ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="tab-content" id="profileTabsContent">
                            <div class="tab-pane fade show active" id="info" role="tabpanel" aria-labelledby="info-tab">
                                <form method="POST" action="">
                                    <div class="mb-3">
                                        <label for="ho_va_ten" class="form-label">Họ và tên</label>
                                        <input type="text" class="form-control" id="ho_va_ten" name="ho_va_ten" value="<?php echo $user['HoVaTen']; ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="email" class="form-label">Email</label>
                                        <input type="email" class="form-control" id="email" name="email" value="<?php echo $user['Email']; ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="sdt" class="form-label">Số điện thoại</label>
                                        <input type="text" class="form-control" id="sdt" name="sdt" value="<?php echo $user['Sdt']; ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="ten_dang_nhap" class="form-label">Tên đăng nhập</label>
                                        <input type="text" class="form-control" id="ten_dang_nhap" value="<?php echo $user['TenDangNhap']; ?>" disabled>
                                        <div class="form-text">Tên đăng nhập không thể thay đổi</div>
                                    </div>
                                    
                                    <button type="submit" name="update_profile" class="theme-btn">Cập nhật thông tin</button>
                                </form>
                            </div>
                            
                            <div class="tab-pane fade" id="security" role="tabpanel" aria-labelledby="security-tab">
                                <form method="POST" action="">
                                    <div class="mb-3">
                                        <label for="mat_khau" class="form-label">Mật khẩu mới</label>
                                        <input type="password" class="form-control" id="mat_khau" name="mat_khau">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="xac_nhan_mat_khau" class="form-label">Xác nhận mật khẩu mới</label>
                                        <input type="password" class="form-control" id="xac_nhan_mat_khau" name="xac_nhan_mat_khau">
                                    </div>
                                    
                                    <input type="hidden" name="ho_va_ten" value="<?php echo $user['HoVaTen']; ?>">
                                    <input type="hidden" name="email" value="<?php echo $user['Email']; ?>">
                                    <input type="hidden" name="sdt" value="<?php echo $user['Sdt']; ?>">
                                    
                                    <button type="submit" name="update_profile" class="theme-btn">Đổi mật khẩu</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <?php include __DIR__ . '/../../app/includes/footer.php'; ?>

    <!-- Back to top area start here -->
    <div class="scroll-up">
        <svg class="scroll-circle svg-content" width="100%" height="100%" viewBox="-1 -1 102 102">
            <path d="M50,1 a49,49 0 0,1 0,98 a49,49 0 0,1 0,-98"/>
        </svg>
    </div>

    <!-- All JS Plugins -->
    <script src="/restaurant-website/public/assets/js/jquery-3.7.1.min.js"></script>
    <script src="/restaurant-website/public/assets/js/bootstrap.bundle.min.js"></script>
    <script src="/restaurant-website/public/assets/js/swiper-bundle.min.js"></script>
    <script src="/restaurant-website/public/assets/js/wow.min.js"></script>
    <script src="/restaurant-website/public/assets/js/main.js"></script>
</body>
</html>