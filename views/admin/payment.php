<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/restaurant-website/public/session.php';

// Kiểm tra quyền admin
checkAdminAccess();

// Lấy khoảng thời gian (tuần hiện tại, tuần trước, v.v.)
$timeFrame = isset($_GET['time_frame']) ? $_GET['time_frame'] : 'current_week';
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 10; // Số lượng đơn mỗi trang

// Xác định các mốc thời gian
$current_date = date('Y-m-d');
$current_week_start = date('Y-m-d', strtotime('monday this week'));
$current_week_end = date('Y-m-d', strtotime('sunday this week'));
$last_week_start = date('Y-m-d', strtotime('monday last week'));
$last_week_end = date('Y-m-d', strtotime('sunday last week'));
$current_month_start = date('Y-m-01');
$current_month_end = date('Y-m-t');

// Xây dựng query dựa trên khoảng thời gian
$query = '/thanh-toan';
$date_filter = '';

switch ($timeFrame) {
    case 'current_week':
        $date_filter = "?start_date={$current_week_start}&end_date={$current_week_end}";
        $title = "Thanh toán tuần này ({$current_week_start} đến {$current_week_end})";
        break;
    case 'last_week':
        $date_filter = "?start_date={$last_week_start}&end_date={$last_week_end}";
        $title = "Thanh toán tuần trước ({$last_week_start} đến {$last_week_end})";
        break;
    case 'current_month':
        $date_filter = "?start_date={$current_month_start}&end_date={$current_month_end}";
        $title = "Thanh toán tháng này ({$current_month_start} đến {$current_month_end})";
        break;
    case 'custom':
        $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : $current_week_start;
        $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : $current_week_end;
        $date_filter = "?start_date={$start_date}&end_date={$end_date}";
        $title = "Thanh toán từ {$start_date} đến {$end_date}";
        break;
    default:
        $date_filter = "?start_date={$current_week_start}&end_date={$current_week_end}";
        $title = "Thanh toán tuần này ({$current_week_start} đến {$current_week_end})";
}

// Lấy danh sách thanh toán
$response = apiRequest($query . $date_filter, 'GET');
$payments = $response['data'] ?? [];

// Tính toán thống kê
$totalAmount = 0;
$paymentMethods = [
    1 => ['name' => 'Tiền mặt', 'count' => 0, 'amount' => 0],
    2 => ['name' => 'Thẻ tín dụng/ghi nợ', 'count' => 0, 'amount' => 0],
    3 => ['name' => 'Chuyển khoản', 'count' => 0, 'amount' => 0]
];

foreach ($payments as $payment) {
    if ($payment['TrangThaiThanhToan'] == 1) { // Chỉ tính các thanh toán đã hoàn thành
        $totalAmount += $payment['SoLuong'];
        $method = $payment['PhuongThucThanhToan'];
        if (isset($paymentMethods[$method])) {
            $paymentMethods[$method]['count']++;
            $paymentMethods[$method]['amount'] += $payment['SoLuong'];
        }
    }
}

// Phân trang
$totalPayments = count($payments);
$totalPages = ceil($totalPayments / $limit);
$startIndex = ($page - 1) * $limit;
$paginatedPayments = array_slice($payments, $startIndex, $limit);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <!-- ========== Meta Tags ========== -->
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Quản lý thanh toán - Hệ thống đặt bàn nhà hàng">
    <!-- ======== Page title ============ -->
    <title>Quản lý thanh toán - Hệ thống đặt bàn nhà hàng</title>
    <!-- Stylesheets -->
    <link rel="shortcut icon" href="/restaurant-website/public/assets/img/logo/favicon.svg">
    <link rel="stylesheet" href="/restaurant-website/public/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="/restaurant-website/public/assets/css/font-awesome.css">
    <link rel="stylesheet" href="/restaurant-website/public/assets/css/animate.css">
    <link rel="stylesheet" href="/restaurant-website/public/assets/css/main.css">
    <link rel="stylesheet" href="/restaurant-website/public/style.css">
    <style>
        .admin-section {
            padding: 80px 0;
            background-color: #f8f9fa;
        }
        .admin-title {
            margin-bottom: 40px;
        }
        .admin-menu {
            background: #fff;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }
        .admin-menu ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .admin-menu ul li {
            margin-bottom: 10px;
        }
        .admin-menu ul li a {
            display: block;
            padding: 12px 15px;
            color: #555;
            border-radius: 5px;
            transition: all 0.3s ease;
        }
        .admin-menu ul li a:hover, .admin-menu ul li a.active {
            background: rgba(255, 91, 0, 0.1);
            color: #ff5b00;
            text-decoration: none;
        }
        .admin-menu ul li a i {
            margin-right: 10px;
        }
        .admin-content {
            background: #fff;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }
        .filter-section {
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            flex-wrap: wrap;
        }
        .filter-label {
            margin-right: 15px;
            font-weight: 600;
        }
        .filter-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .filter-btn {
            padding: 8px 15px;
            border-radius: 5px;
            border: 1px solid #ddd;
            background-color: white;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            color: #333;
        }
        .filter-btn:hover {
            background-color: #f8f9fa;
        }
        .filter-btn.active {
            background-color: #ff5b00;
            color: white;
            border-color: #ff5b00;
        }
        .custom-date-form {
            display: flex;
            gap: 10px;
            align-items: center;
            margin-top: 15px;
            flex-wrap: wrap;
        }
        .custom-date-form .form-control {
            max-width: 150px;
        }
        .stats-cards {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 30px;
        }
        .stats-card {
            flex: 1;
            min-width: 250px;
            padding: 20px;
            border-radius: 10px;
            background-color: #fff;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            border-left: 5px solid #ff5b00;
        }
        .stats-card-title {
            font-size: 14px;
            color: #777;
            margin-bottom: 10px;
        }
        .stats-card-value {
            font-size: 24px;
            font-weight: 700;
            color: #333;
            margin-bottom: 5px;
        }
        .stats-card-subtitle {
            font-size: 14px;
            color: #555;
        }
        .payment-table {
            width: 100%;
            border-collapse: collapse;
        }
        .payment-table th, .payment-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .payment-table th {
            background-color: #f8f9fa;
            font-weight: 600;
        }
        .payment-table tr:hover {
            background-color: #f9f9f9;
        }
        .payment-status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-paid {
            background-color: #d4edda;
            color: #155724;
        }
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        .payment-method {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 12px;
            background-color: #f8f9fa;
        }
        .method-cash {
            border-left: 3px solid #28a745;
        }
        .method-card {
            border-left: 3px solid #007bff;
        }
        .method-transfer {
            border-left: 3px solid #ffc107;
        }
        .action-buttons {
            display: flex;
            gap: 5px;
        }
        .action-btn {
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 12px;
            cursor: pointer;
            border: none;
            transition: all 0.3s ease;
        }
        .view-btn {
            background-color: #3498db;
            color: white;
        }
        .view-btn:hover {
            background-color: #2980b9;
        }
        .pagination-container {
            display: flex;
            justify-content: center;
            margin-top: 30px;
        }
        .pagination {
            display: flex;
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .page-item {
            margin: 0 5px;
        }
        .page-link {
            display: block;
            padding: 8px 12px;
            border: 1px solid #ddd;
            color: #ff5b00;
            border-radius: 5px;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        .page-item.active .page-link {
            background-color: #ff5b00;
            color: white;
            border-color: #ff5b00;
        }
        .page-link:hover {
            background-color: #f8f9fa;
        }
        .payment-method-stats {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 30px;
        }
        .method-stat-card {
            flex: 1;
            min-width: 200px;
            padding: 15px;
            border-radius: 8px;
            background-color: #fff;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }
        .method-stat-card.cash {
            border-left: 5px solid #28a745;
        }
        .method-stat-card.card {
            border-left: 5px solid #007bff;
        }
        .method-stat-card.transfer {
            border-left: 5px solid #ffc107;
        }
        .method-stat-title {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 10px;
        }
        .method-stat-value {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 5px;
        }
        .method-stat-count {
            font-size: 14px;
            color: #777;
        }
    </style>
</head>
<body>
    <!-- Preloader -->
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

    <!-- Header -->
    <header class="section-bg">
        <div class="header-top">
            <div class="container">
                <div class="header-top-wrapper">
                    <ul>
                        <li><span>Administrator</span> Dashboard</li>
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
                        <div class="header-right d-flex justify-content-end align-items-center">
                            <div class="header-button">
                                <a href="/restaurant-website/public/logout.php" class="theme-btn bg-red-2">Đăng xuất</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Admin Section -->
    <section class="admin-section">
        <div class="container">
            <div class="admin-title">
                <h2 class="text-center">Quản lý thanh toán</h2>
            </div>
            
            <div class="row">
                <div class="col-lg-3">
                    <div class="admin-menu">
                        <h4>Menu quản trị</h4>
                        <ul>
                            <li><a href="/restaurant-website/public/admin/dashboard" class="<?php echo $path == '/admin/dashboard' ? 'active' : ''; ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                            <li><a href="/restaurant-website/public/admin/users" class="<?php echo $path == '/admin/users' ? 'active' : ''; ?>"><i class="fas fa-users"></i> Quản lý người dùng</a></li>
                            <li><a href="/restaurant-website/public/admin/restaurants" class="<?php echo $path == '/admin/restaurants' ? 'active' : ''; ?>"><i class="fas fa-utensils"></i> Quản lý nhà hàng</a></li>
                            <li><a href="/restaurant-website/public/admin/bookings" class="<?php echo $path == '/admin/bookings' ? 'active' : ''; ?>"><i class="fas fa-calendar-check"></i> Quản lý đặt bàn</a></li>
                            <li><a href="/restaurant-website/public/admin/food" class="<?php echo $path == '/admin/food' ? 'active' : ''; ?>"><i class="fas fa-hamburger"></i> Quản lý món ăn</a></li>
                            <li><a href="/restaurant-website/public/admin/categories" class="<?php echo $path == '/admin/categories' ? 'active' : ''; ?>"><i class="fas fa-list"></i> Quản lý danh mục</a></li>
                            <li><a href="/restaurant-website/public/admin/reviews" class="<?php echo $path == '/admin/reviews' ? 'active' : ''; ?>"><i class="fas fa-star"></i> Quản lý đánh giá</a></li>
                            <li><a href="/restaurant-website/public/admin/notifications" class="<?php echo $path == '/admin/notifications' ? 'active' : ''; ?>"><i class="fas fa-bell"></i> Quản lý thông báo</a></li>
                            <li><a href="/restaurant-website/public/admin/payment" class="<?php echo $path == '/admin/payment' ? 'active' : ''; ?>"><i class="fas fa-credit-card"></i> Quản lý thanh toán</a></li>
                        </ul>
                    </div>
                </div>
                
                <div class="col-lg-9">
                    <div class="admin-content">
                        <h3 class="mb-4"><?php echo $title; ?></h3>
                        
                        <div class="filter-section">
                            <div class="filter-label">Khoảng thời gian:</div>
                            <div class="filter-buttons">
                                <a href="?time_frame=current_week" class="filter-btn <?php echo $timeFrame == 'current_week' ? 'active' : ''; ?>">Tuần này</a>
                                <a href="?time_frame=last_week" class="filter-btn <?php echo $timeFrame == 'last_week' ? 'active' : ''; ?>">Tuần trước</a>
                                <a href="?time_frame=current_month" class="filter-btn <?php echo $timeFrame == 'current_month' ? 'active' : ''; ?>">Tháng này</a>
                                <a href="?time_frame=custom" class="filter-btn <?php echo $timeFrame == 'custom' ? 'active' : ''; ?>">Tùy chỉnh</a>
                            </div>
                        </div>
                        
                        <?php if ($timeFrame == 'custom'): ?>
                            <div class="custom-date-form">
                                <form method="GET" class="d-flex align-items-end">
                                    <input type="hidden" name="time_frame" value="custom">
                                    <div class="form-group mb-0 mr-2">
                                        <label for="start_date">Từ ngày:</label>
                                        <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo isset($_GET['start_date']) ? $_GET['start_date'] : $current_week_start; ?>">
                                    </div>
                                    <div class="form-group mb-0 mr-2">
                                        <label for="end_date">Đến ngày:</label>
                                        <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo isset($_GET['end_date']) ? $_GET['end_date'] : $current_week_end; ?>">
                                    </div>
                                    <button type="submit" class="btn btn-primary">Lọc</button>
                                </form>
                            </div>
                        <?php endif; ?>
                        
                        <div class="stats-cards mt-4">
                            <div class="stats-card">
                                <div class="stats-card-title">Tổng thanh toán</div>
                                <div class="stats-card-value"><?php echo number_format($totalAmount, 0, ',', '.'); ?>đ</div>
                                <div class="stats-card-subtitle"><?php echo count($payments); ?> giao dịch</div>
                            </div>
                        </div>
                        
                        <div class="payment-method-stats">
                            <div class="method-stat-card cash">
                                <div class="method-stat-title"><i class="fas fa-money-bill-wave mr-2"></i> Tiền mặt</div>
                                <div class="method-stat-value"><?php echo number_format($paymentMethods[1]['amount'], 0, ',', '.'); ?>đ</div>
                                <div class="method-stat-count"><?php echo $paymentMethods[1]['count']; ?> giao dịch</div>
                            </div>
                            <div class="method-stat-card card">
                                <div class="method-stat-title"><i class="fas fa-credit-card mr-2"></i> Thẻ tín dụng</div>
                                <div class="method-stat-value"><?php echo number_format($paymentMethods[2]['amount'], 0, ',', '.'); ?>đ</div>
                                <div class="method-stat-count"><?php echo $paymentMethods[2]['count']; ?> giao dịch</div>
                            </div>
                            <div class="method-stat-card transfer">
                                <div class="method-stat-title"><i class="fas fa-university mr-2"></i> Chuyển khoản</div>
                                <div class="method-stat-value"><?php echo number_format($paymentMethods[3]['amount'], 0, ',', '.'); ?>đ</div>
                                <div class="method-stat-count"><?php echo $paymentMethods[3]['count']; ?> giao dịch</div>
                            </div>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="payment-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Mã đặt bàn</th>
                                        <th>Khách hàng</th>
                                        <th>Ngày thanh toán</th>
                                        <th>Phương thức</th>
                                        <th>Số tiền</th>
                                        <th>Trạng thái</th>
                                        <th>Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($paginatedPayments)): ?>
                                        <tr>
                                            <td colspan="8" class="text-center py-4">Không có dữ liệu thanh toán trong khoảng thời gian này</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($paginatedPayments as $payment): 
                                            // Lấy thông tin đặt bàn
                                            $booking = $payment['thongTinDatBan'] ?? null;
                                            $user = $booking ? $booking['user'] ?? null : null;
                                            
                                            // Phương thức thanh toán
                                            $methodClass = '';
                                            $methodText = '';
                                            switch ($payment['PhuongThucThanhToan']) {
                                                case 1:
                                                    $methodClass = 'method-cash';
                                                    $methodText = 'Tiền mặt';
                                                    break;
                                                case 2:
                                                    $methodClass = 'method-card';
                                                    $methodText = 'Thẻ tín dụng';
                                                    break;
                                                case 3:
                                                    $methodClass = 'method-transfer';
                                                    $methodText = 'Chuyển khoản';
                                                    break;
                                                default:
                                                    $methodClass = '';
                                                    $methodText = 'Không xác định';
                                            }
                                            
                                            // Trạng thái thanh toán
                                            $statusClass = $payment['TrangThaiThanhToan'] == 1 ? 'status-paid' : 'status-pending';
                                            $statusText = $payment['TrangThaiThanhToan'] == 1 ? 'Đã thanh toán' : 'Chưa thanh toán';
                                        ?>
                                            <tr>
                                                <td><?php echo $payment['ID_ThanhToan']; ?></td>
                                                <td><?php echo $payment['ID_ThongTinDatBan']; ?></td>
                                                <td><?php echo $user ? htmlspecialchars($user['HoVaTen']) : 'N/A'; ?></td>
                                                <td><?php echo $payment['NgayThanhToan'] ? date('d/m/Y H:i', strtotime($payment['NgayThanhToan'])) : 'Chưa thanh toán'; ?></td>
                                                <td><span class="payment-method <?php echo $methodClass; ?>"><?php echo $methodText; ?></span></td>
                                                <td><?php echo number_format($payment['SoLuong'], 0, ',', '.'); ?>đ</td>
                                                <td><span class="payment-status <?php echo $statusClass; ?>"><?php echo $statusText; ?></span></td>
                                                <td>
                                                    <div class="action-buttons">
                                                    <a href="/restaurant-website/public/admin/bookings?booking_id=<?php echo $payment['ID_ThongTinDatBan']; ?>" class="action-btn view-btn">
                                                        <i class="fas fa-eye"></i> Xem chi tiết
                                                    </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Phân trang -->
                        <?php if ($totalPages > 1): ?>
                            <div class="pagination-container">
                                <ul class="pagination">
                                    <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?time_frame=<?php echo $timeFrame; ?>&page=<?php echo $page - 1; ?><?php echo $timeFrame == 'custom' ? '&start_date=' . ($_GET['start_date'] ?? $current_week_start) . '&end_date=' . ($_GET['end_date'] ?? $current_week_end) : ''; ?>">
                                                &laquo;
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                            <a class="page-link" href="?time_frame=<?php echo $timeFrame; ?>&page=<?php echo $i; ?><?php echo $timeFrame == 'custom' ? '&start_date=' . ($_GET['start_date'] ?? $current_week_start) . '&end_date=' . ($_GET['end_date'] ?? $current_week_end) : ''; ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <?php if ($page < $totalPages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?time_frame=<?php echo $timeFrame; ?>&page=<?php echo $page + 1; ?><?php echo $timeFrame == 'custom' ? '&start_date=' . ($_GET['start_date'] ?? $current_week_start) . '&end_date=' . ($_GET['end_date'] ?? $current_week_end) : ''; ?>">
                                                &raquo;
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer-section fix section-bg">
        <div class="container">
            <div class="footer-bottom-wrapper d-flex align-items-center justify-content-between">
                <p>
                    © Copyright 2025 <a href="index.php">Restaurant Booking</a>. All Rights Reserved.
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
    <script src="/restaurant-website/public/assets/js/bootstrap.bundle.min.js"></script>
    <script src="/restaurant-website/public/assets/js/swiper-bundle.min.js"></script>
    <script src="/restaurant-website/public/assets/js/wow.min.js"></script>
    <script src="/restaurant-website/public/assets/js/main.js"></script>
</body>
</html>