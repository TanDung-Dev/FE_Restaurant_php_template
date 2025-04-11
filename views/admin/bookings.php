<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/restaurant-website/public/session.php';

// Kiểm tra quyền admin
checkAdminAccess();

// Lấy trạng thái lọc từ query string
$status = isset($_GET['status']) ? intval($_GET['status']) : -1; // -1 là tất cả
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 10; // Số lượng đơn mỗi trang

// Lấy danh sách đặt bàn
$query = '/dat-ban';

// Thêm param trạng thái nếu không phải lấy tất cả
if ($status >= 0) {
    $query .= '?trang_thai=' . $status;
}

$response = apiRequest($query, 'GET');
$bookings = $response['data'] ?? [];

// Phân trang
$totalBookings = count($bookings);
$totalPages = ceil($totalBookings / $limit);
$startIndex = ($page - 1) * $limit;
$paginatedBookings = array_slice($bookings, $startIndex, $limit);

// Xử lý action từ form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && isset($_POST['booking_id'])) {
        $action = $_POST['action'];
        $bookingId = $_POST['booking_id'];
        
        switch ($action) {
            case 'confirm':
                // Xác nhận đặt bàn
                $confirmResponse = apiRequest('/dat-ban/' . $bookingId . '/confirm', 'POST');
                if ($confirmResponse['success']) {
                    $successMessage = 'Đã xác nhận đơn đặt bàn thành công!';
                } else {
                    $errorMessage = $confirmResponse['message'] ?? 'Có lỗi xảy ra khi xác nhận đơn đặt bàn';
                }
                break;
                
            case 'cancel':
                // Hủy đơn đặt bàn
                $cancelResponse = apiRequest('/dat-ban/' . $bookingId, 'DELETE');
                if ($cancelResponse['success']) {
                    $successMessage = 'Đã hủy đơn đặt bàn thành công!';
                } else {
                    $errorMessage = $cancelResponse['message'] ?? 'Có lỗi xảy ra khi hủy đơn đặt bàn';
                }
                break;
                
            case 'assign_table':
                // Gán bàn cho đơn đặt bàn
                if (!empty($_POST['table_id'])) {
                    $assignData = [
                        'ID_ThongTinDatBan' => $bookingId,
                        'ID_Ban' => $_POST['table_id']
                    ];
                    
                    $assignResponse = apiRequest('/chi-tiet-dat-ban/gan-ban', 'POST', $assignData);
                    if ($assignResponse['success']) {
                        $successMessage = 'Đã gán bàn thành công!';
                    } else {
                        $errorMessage = $assignResponse['message'] ?? 'Có lỗi xảy ra khi gán bàn';
                    }
                } else {
                    $errorMessage = 'Vui lòng chọn bàn để gán';
                }
                break;
        }
        
        // Làm mới danh sách sau khi thực hiện hành động
        $response = apiRequest($query, 'GET');
        $bookings = $response['data'] ?? [];
        $totalBookings = count($bookings);
        $totalPages = ceil($totalBookings / $limit);
        $paginatedBookings = array_slice($bookings, $startIndex, $limit);
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
    <meta name="description" content="Quản lý đặt bàn - Hệ thống đặt bàn nhà hàng">
    <!-- ======== Page title ============ -->
    <title>Quản lý đặt bàn - Hệ thống đặt bàn nhà hàng</title>
     <!-- Favicon -->
     <link rel="shortcut icon" href="/restaurant-website/public/assets/img/logo/favicon.svg">
    <!-- CSS files -->
    <link rel="stylesheet" href="/restaurant-website/public/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="/restaurant-website/public/assets/css/font-awesome.css">
    <link rel="stylesheet" href="/restaurant-website/public/assets/css/animate.css">
    <link rel="stylesheet" href="/restaurant-website/public/assets/css/magnific-popup.css">
    <link rel="stylesheet" href="/restaurant-website/public/assets/css/meanmenu.css">
    <link rel="stylesheet" href="/restaurant-website/public/assets/css/swiper-bundle.min.css">
    <link rel="stylesheet" href="/restaurant-website/public/assets/css/nice-select.css">
    <link rel="stylesheet" href="/restaurant-website/public/assets/css/main.css">
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
        }
        .filter-label {
            margin-right: 15px;
            font-weight: 600;
        }
        .filter-buttons {
            display: flex;
            gap: 10px;
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
        .booking-table {
            width: 100%;
            border-collapse: collapse;
        }
        .booking-table th, .booking-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .booking-table th {
            background-color: #f8f9fa;
            font-weight: 600;
        }
        .booking-table tr:hover {
            background-color: #f9f9f9;
        }
        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-pending {
            background-color: #ffeeba;
            color: #856404;
        }
        .status-confirmed {
            background-color: #d4edda;
            color: #155724;
        }
        .status-cancelled {
            background-color: #f8d7da;
            color: #721c24;
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
        .confirm-btn {
            background-color: #2ecc71;
            color: white;
        }
        .confirm-btn:hover {
            background-color: #27ae60;
        }
        .cancel-btn {
            background-color: #e74c3c;
            color: white;
        }
        .cancel-btn:hover {
            background-color: #c0392b;
        }
        .assign-btn {
            background-color: #f39c12;
            color: white;
        }
        .assign-btn:hover {
            background-color: #d35400;
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
        .modal-body {
            padding: 20px;
        }
        .modal-footer {
            border-top: 1px solid #eee;
            padding: 15px 20px;
        }
        .detail-section {
            margin-bottom: 20px;
        }
        .detail-title {
            font-weight: 600;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        .detail-row {
            display: flex;
            margin-bottom: 10px;
        }
        .detail-label {
            font-weight: 600;
            min-width: 150px;
        }
        .detail-value {
            flex: 1;
        }
        .dish-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        .dish-table th, .dish-table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .dish-table th {
            background-color: #f8f9fa;
        }
        .payment-info {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-top: 15px;
        }
        .payment-status {
            font-weight: 600;
        }
        .payment-status.paid {
            color: #2ecc71;
        }
        .payment-status.not-paid {
            color: #e74c3c;
        }
        .total-amount {
            font-size: 18px;
            font-weight: 600;
            text-align: right;
            margin-top: 15px;
        }
        .empty-message {
            text-align: center;
            padding: 50px 0;
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
                <h2 class="text-center">Quản lý đặt bàn</h2>
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
                        <?php if (isset($successMessage)): ?>
                            <div class="alert alert-success mb-4">
                                <?php echo $successMessage; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (isset($errorMessage)): ?>
                            <div class="alert alert-danger mb-4">
                                <?php echo $errorMessage; ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="filter-section">
                            <div class="filter-label">Lọc theo trạng thái:</div>
                            <div class="filter-buttons">
                                <a href="?status=-1" class="filter-btn <?php echo $status == -1 ? 'active' : ''; ?>">Tất cả</a>
                                <a href="?status=0" class="filter-btn <?php echo $status == 0 ? 'active' : ''; ?>">Chờ xác nhận</a>
                                <a href="?status=1" class="filter-btn <?php echo $status == 1 ? 'active' : ''; ?>">Đã xác nhận</a>
                                <a href="?status=2" class="filter-btn <?php echo $status == 2 ? 'active' : ''; ?>">Đã hủy</a>
                            </div>
                        </div>
                        
                        <?php if (empty($paginatedBookings)): ?>
                            <div class="empty-message">
                                <i class="fas fa-calendar-times fa-3x mb-3"></i>
                                <h3>Không tìm thấy đơn đặt bàn</h3>
                                <p>Không có đơn đặt bàn nào phù hợp với bộ lọc hiện tại.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="booking-table">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Khách hàng</th>
                                            <th>Nhà hàng</th>
                                            <th>Ngày đặt</th>
                                            <th>Giờ</th>
                                            <th>Số khách</th>
                                            <th>Trạng thái</th>
                                            <th>Thanh toán</th>
                                            <th>Thao tác</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($paginatedBookings as $booking): 
                                            // Lấy thông tin bàn và khu vực
                                            $chiTietDatBan = $booking['chiTietDatBans'][0] ?? null;
                                            $ban = $chiTietDatBan ? $chiTietDatBan['ban'] ?? null : null;
                                            $khuVuc = $ban ? $ban['khuVuc'] ?? null : null;
                                            $nhaHang = $khuVuc ? $khuVuc['nhaHang'] ?? null : null;
                                            
                                            // Kiểm tra trạng thái thanh toán
                                            $thanhToan = $booking['thanhToan'] ?? null;
                                            $isPaid = $thanhToan && $thanhToan['TrangThaiThanhToan'] == 1;
                                            
                                            // Xác định trạng thái đặt bàn
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
                                                default:
                                                    $statusClass = 'status-pending';
                                                    $statusText = 'Chờ xác nhận';
                                            }
                                        ?>
                                            <tr>
                                                <td><?php echo $booking['ID_ThongTinDatBan']; ?></td>
                                                <td><?php echo htmlspecialchars($booking['user']['HoVaTen']); ?></td>
                                                <td><?php echo htmlspecialchars($nhaHang['TenNhaHang'] ?? 'N/A'); ?></td>
                                                <td><?php echo date('d/m/Y', strtotime($booking['ThoiGianDatBan'])); ?></td>
                                                <td><?php echo date('H:i', strtotime($booking['ThoiGianDatBan'])); ?></td>
                                                <td><?php echo $booking['SoLuongKhach']; ?></td>
                                                <td><span class="status-badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span></td>
                                                <td>
                                                    <?php if ($isPaid): ?>
                                                        <span class="payment-status paid"><i class="fas fa-check-circle"></i> Đã thanh toán</span>
                                                    <?php else: ?>
                                                        <span class="payment-status not-paid"><i class="fas fa-clock"></i> Chưa thanh toán</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="action-buttons">
                                                        <button type="button" class="action-btn view-btn" data-toggle="modal" data-target="#detailModal<?php echo $booking['ID_ThongTinDatBan']; ?>">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                        
                                                        <?php if ($booking['TrangThai'] == 0): ?>
                                                            <form method="POST" style="display: inline;">
                                                                <input type="hidden" name="action" value="confirm">
                                                                <input type="hidden" name="booking_id" value="<?php echo $booking['ID_ThongTinDatBan']; ?>">
                                                                <button type="submit" class="action-btn confirm-btn" onclick="return confirm('Xác nhận đơn đặt bàn này?')">
                                                                    <i class="fas fa-check"></i>
                                                                </button>
                                                            </form>
                                                            
                                                            <button type="button" class="action-btn assign-btn" data-toggle="modal" data-target="#assignModal<?php echo $booking['ID_ThongTinDatBan']; ?>">
                                                                <i class="fas fa-chair"></i>
                                                            </button>
                                                            
                                                            <form method="POST" style="display: inline;">
                                                                <input type="hidden" name="action" value="cancel">
                                                                <input type="hidden" name="booking_id" value="<?php echo $booking['ID_ThongTinDatBan']; ?>">
                                                                <button type="submit" class="action-btn cancel-btn" onclick="return confirm('Bạn có chắc chắn muốn hủy đơn đặt bàn này?')">
                                                                    <i class="fas fa-times"></i>
                                                                </button>
                                                            </form>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                            
                                            <!-- Modal Chi tiết đặt bàn -->
                                            <div class="modal fade" id="detailModal<?php echo $booking['ID_ThongTinDatBan']; ?>" tabindex="-1" role="dialog" aria-hidden="true">
                                                <div class="modal-dialog modal-lg" role="document">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Chi tiết đặt bàn #<?php echo $booking['ID_ThongTinDatBan']; ?></h5>
                                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                                <span aria-hidden="true">&times;</span>
                                                            </button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <div class="detail-section">
                                                                <h5 class="detail-title">Thông tin khách hàng</h5>
                                                                <div class="detail-row">
                                                                    <div class="detail-label">Họ tên:</div>
                                                                    <div class="detail-value"><?php echo htmlspecialchars($booking['user']['HoVaTen']); ?></div>
                                                                </div>
                                                                <div class="detail-row">
                                                                    <div class="detail-label">Email:</div>
                                                                    <div class="detail-value"><?php echo htmlspecialchars($booking['user']['Email']); ?></div>
                                                                </div>
                                                                <div class="detail-row">
                                                                    <div class="detail-label">Số điện thoại:</div>
                                                                    <div class="detail-value"><?php echo htmlspecialchars($booking['user']['Sdt']); ?></div>
                                                                </div>
                                                            </div>
                                                            
                                                            <div class="detail-section">
                                                                <h5 class="detail-title">Thông tin đặt bàn</h5>
                                                                <div class="detail-row">
                                                                    <div class="detail-label">Nhà hàng:</div>
                                                                    <div class="detail-value"><?php echo htmlspecialchars($nhaHang['TenNhaHang'] ?? 'N/A'); ?></div>
                                                                </div>
                                                                <div class="detail-row">
                                                                    <div class="detail-label">Khu vực:</div>
                                                                    <div class="detail-value"><?php echo htmlspecialchars($khuVuc['Ten'] ?? 'Chưa xác định'); ?></div>
                                                                </div>
                                                                <div class="detail-row">
                                                                    <div class="detail-label">Bàn số:</div>
                                                                    <div class="detail-value"><?php echo $ban ? $ban['SoBang'] : 'Chưa gán bàn'; ?></div>
                                                                </div>
                                                                <div class="detail-row">
                                                                    <div class="detail-label">Ngày đặt bàn:</div>
                                                                    <div class="detail-value"><?php echo date('d/m/Y', strtotime($booking['ThoiGianDatBan'])); ?></div>
                                                                </div>
                                                                <div class="detail-row">
                                                                    <div class="detail-label">Giờ đặt bàn:</div>
                                                                    <div class="detail-value"><?php echo date('H:i', strtotime($booking['ThoiGianDatBan'])); ?></div>
                                                                </div>
                                                                <div class="detail-row">
                                                                    <div class="detail-label">Số lượng khách:</div>
                                                                    <div class="detail-value"><?php echo $booking['SoLuongKhach']; ?> người</div>
                                                                </div>
                                                                <div class="detail-row">
                                                                    <div class="detail-label">Yêu cầu đặc biệt:</div>
                                                                    <div class="detail-value"><?php echo htmlspecialchars($booking['YeuCau'] ?? 'Không có'); ?></div>
                                                                </div>
                                                                <div class="detail-row">
                                                                    <div class="detail-label">Trạng thái:</div>
                                                                    <div class="detail-value"><span class="status-badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span></div>
                                                                </div>
                                                                <div class="detail-row">
                                                                    <div class="detail-label">Ngày tạo:</div>
                                                                    <div class="detail-value"><?php echo date('d/m/Y H:i', strtotime($booking['NgayTao'])); ?></div>
                                                                </div>
                                                            </div>
                                                            
                                                            <?php 
                                                                // Lấy danh sách món ăn
                                                                $dishesResponse = apiRequest('/chi-tiet-dat-mon?id_thongtin_datban=' . $booking['ID_ThongTinDatBan'], 'GET');
                                                                $dishes = $dishesResponse['data'] ?? [];
                                                                $totalAmount = 0;
                                                                
                                                                foreach ($dishes as $dish) {
                                                                    $totalAmount += $dish['ThanhTien'];
                                                                }
                                                            ?>
                                                            
                                                            <?php if (!empty($dishes)): ?>
                                                                <div class="detail-section">
                                                                    <h5 class="detail-title">Các món ăn đã đặt</h5>
                                                                    <table class="dish-table">
                                                                        <thead>
                                                                            <tr>
                                                                                <th>Món ăn</th>
                                                                                <th>Số lượng</th>
                                                                                <th>Đơn giá</th>
                                                                                <th>Thành tiền</th>
                                                                                <th>Ghi chú</th>
                                                                            </tr>
                                                                        </thead>
                                                                        <tbody>
                                                                        
                                                                        <?php foreach ($dishes as $dish): ?>
                                                                            <tr>
                                                                            <td>
                                                                                    <?php 
                                                                                    // Kiểm tra xem 'monAn' có tồn tại và không phải null
                                                                                    echo isset($dish['monAn']) && $dish['monAn'] !== null && isset($dish['monAn']['TenMonAn']) 
                                                                                        ? htmlspecialchars($dish['monAn']['TenMonAn']) 
                                                                                        : 'Không xác định'; 
                                                                                    ?>
                                                                                </td>
                                                                                <td><?php echo $dish['SoLuong'] ?? 0; ?></td>
                                                                                <td><?php echo number_format($dish['DonGia'] ?? 0, 0, ',', '.'); ?>đ</td>
                                                                                <td><?php echo number_format($dish['ThanhTien'] ?? 0, 0, ',', '.'); ?>đ</td>
                                                                                <td><?php echo htmlspecialchars($dish['GhiChu'] ?? ''); ?></td>
                                                                            </tr>
                                                                        <?php endforeach; ?>
                                                                        
                                                                        </tbody>
                                                                    </table>
                                                                    <div class="total-amount">
                                                                        Tổng tiền: <?php echo number_format($totalAmount, 0, ',', '.'); ?>đ
                                                                    </div>
                                                                </div>
                                                            <?php endif; ?>
                                                            
                                                            <?php if ($thanhToan): ?>
                                                                <div class="detail-section">
                                                                    <h5 class="detail-title">Thông tin thanh toán</h5>
                                                                    <div class="payment-info">
                                                                        <div class="detail-row">
                                                                            <div class="detail-label">Trạng thái:</div>
                                                                            <div class="detail-value">
                                                                                <span class="payment-status <?php echo $isPaid ? 'paid' : 'not-paid'; ?>">
                                                                                    <?php echo $isPaid ? 'Đã thanh toán' : 'Chưa thanh toán'; ?>
                                                                                </span>
                                                                            </div>
                                                                        </div>
                                                                        <div class="detail-row">
                                                                            <div class="detail-label">Phương thức:</div>
                                                                            <div class="detail-value">
                                                                                <?php
                                                                                    switch ($thanhToan['PhuongThucThanhToan']) {
                                                                                        case 1:
                                                                                            echo 'Tiền mặt';
                                                                                            break;
                                                                                        case 2:
                                                                                            echo 'Thẻ tín dụng/ghi nợ';
                                                                                            break;
                                                                                        case 3:
                                                                                            echo 'Chuyển khoản ngân hàng';
                                                                                            break;
                                                                                        default:
                                                                                            echo 'Không xác định';
                                                                                    }
                                                                                ?>
                                                                            </div>
                                                                        </div>
                                                                        <?php if ($isPaid): ?>
                                                                            <div class="detail-row">
                                                                                <div class="detail-label">Ngày thanh toán:</div>
                                                                                <div class="detail-value">
                                                                                    <?php echo date('d/m/Y H:i', strtotime($thanhToan['NgayThanhToan'])); ?>
                                                                                </div>
                                                                            </div>
                                                                        <?php endif; ?>
                                                                        <?php if (!empty($thanhToan['MaGiaoDich'])): ?>
                                                                            <div class="detail-row">
                                                                                <div class="detail-label">Mã giao dịch:</div>
                                                                                <div class="detail-value"><?php echo $thanhToan['MaGiaoDich']; ?></div>
                                                                            </div>
                                                                        <?php endif; ?>
                                                                        <div class="detail-row">
                                                                            <div class="detail-label">Số tiền:</div>
                                                                            <div class="detail-value">
                                                                                <?php echo number_format($thanhToan['SoLuong'], 0, ',', '.'); ?>đ
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                                                            <?php if ($booking['TrangThai'] == 0): ?>
                                                                <form method="POST" style="display: inline;">
                                                                    <input type="hidden" name="action" value="confirm">
                                                                    <input type="hidden" name="booking_id" value="<?php echo $booking['ID_ThongTinDatBan']; ?>">
                                                                    <button type="submit" class="btn btn-success">Xác nhận đặt bàn</button>
                                                                </form>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <!-- Modal Gán bàn -->
                                            <div class="modal fade" id="assignModal<?php echo $booking['ID_ThongTinDatBan']; ?>" tabindex="-1" role="dialog" aria-hidden="true">
                                                <div class="modal-dialog" role="document">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Gán bàn cho đơn đặt bàn #<?php echo $booking['ID_ThongTinDatBan']; ?></h5>
                                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                                <span aria-hidden="true">&times;</span>
                                                            </button>
                                                        </div>
                                                        <form method="POST">
                                                            <div class="modal-body">
                                                                <input type="hidden" name="action" value="assign_table">
                                                                <input type="hidden" name="booking_id" value="<?php echo $booking['ID_ThongTinDatBan']; ?>">
                                                                
                                                                <div class="form-group">
                                                                    <label for="table_id_<?php echo $booking['ID_ThongTinDatBan']; ?>">Chọn bàn:</label>
                                                                    <select class="form-control" id="table_id_<?php echo $booking['ID_ThongTinDatBan']; ?>" name="table_id" required>
                                                                        <option value="">-- Chọn bàn --</option>
                                                                        <?php
                                                                            // Lấy danh sách bàn có thể gán
                                                                            $areaId = $khuVuc ? $khuVuc['ID_KhuVuc'] : null;
                                                                            if ($areaId) {
                                                                                $tablesResponse = apiRequest('/ban?id_khuvuc=' . $areaId, 'GET');
                                                                                $availableTables = $tablesResponse['data'] ?? [];
                                                                                
                                                                                foreach ($availableTables as $table) {
                                                                                    $selected = $ban && $ban['ID_Ban'] == $table['ID_Ban'] ? 'selected' : '';
                                                                                    echo '<option value="' . $table['ID_Ban'] . '" ' . $selected . '>Bàn số ' . $table['SoBang'] . ' - Sức chứa: ' . $table['DungTich'] . ' người</option>';
                                                                                }
                                                                            }
                                                                        ?>
                                                                    </select>
                                                                    <small class="form-text text-muted">Chọn bàn phù hợp với số lượng khách (<?php echo $booking['SoLuongKhach']; ?> người)</small>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
                                                                <button type="submit" class="btn btn-primary">Gán bàn</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- Phân trang -->
                            <?php if ($totalPages > 1): ?>
                                <div class="pagination-container">
                                    <ul class="pagination">
                                        <?php if ($page > 1): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?status=<?php echo $status; ?>&page=<?php echo $page - 1; ?>">
                                                    &laquo;
                                                </a>
                                            </li>
                                        <?php endif; ?>
                                        
                                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                                <a class="page-link" href="?status=<?php echo $status; ?>&page=<?php echo $i; ?>">
                                                    <?php echo $i; ?>
                                                </a>
                                            </li>
                                        <?php endfor; ?>
                                        
                                        <?php if ($page < $totalPages): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?status=<?php echo $status; ?>&page=<?php echo $page + 1; ?>">
                                                    &raquo;
                                                </a>
                                            </li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
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

      <!-- JavaScript files -->
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