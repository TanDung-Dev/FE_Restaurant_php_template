<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/restaurant-website/public/session.php';

// Kiểm tra quyền admin
checkAdminAccess();

// Lấy danh sách nhà hàng
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 10; // Số lượng nhà hàng mỗi trang

$response = apiRequest('/nhahang', 'GET');
$restaurants = $response['data'] ?? [];

// Phân trang
$totalRestaurants = count($restaurants);
$totalPages = ceil($totalRestaurants / $limit);
$startIndex = ($page - 1) * $limit;
$paginatedRestaurants = array_slice($restaurants, $startIndex, $limit);

// Xử lý submit form
$successMessage = '';
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                // Thêm nhà hàng mới
                $restaurantData = [
                    'TenNhaHang' => $_POST['ten_nhahang'] ?? '',
                    'DiaChi' => $_POST['dia_chi'] ?? '',
                    'Sdt' => $_POST['sdt'] ?? '',
                    'Email' => $_POST['email'] ?? '',
                    'MieuTa' => $_POST['mieu_ta'] ?? '',
                    'OpenTime' => $_POST['open_time'] ?? '',
                    'CloseTime' => $_POST['close_time'] ?? '',
                    'DungTich' => intval($_POST['dung_tich'] ?? 0)
                ];
                
                // Xử lý upload hình ảnh
                // Note: Xử lý upload ảnh thực tế sẽ phức tạp hơn, đây chỉ là mẫu
                
                $response = apiRequest('/nhahang', 'POST', $restaurantData);
                
                if ($response['success']) {
                    $successMessage = 'Thêm nhà hàng thành công!';
                    
                    // Làm mới danh sách
                    $response = apiRequest('/nhahang', 'GET');
                    $restaurants = $response['data'] ?? [];
                    $totalRestaurants = count($restaurants);
                    $totalPages = ceil($totalRestaurants / $limit);
                    $paginatedRestaurants = array_slice($restaurants, $startIndex, $limit);
                } else {
                    $errorMessage = $response['message'] ?? 'Có lỗi xảy ra khi thêm nhà hàng';
                }
                break;
                
            case 'edit':
                // Cập nhật nhà hàng
                $restaurantId = intval($_POST['restaurant_id'] ?? 0);
                
                if (!$restaurantId) {
                    $errorMessage = 'ID nhà hàng không hợp lệ';
                    break;
                }
                
                $restaurantData = [
                    'TenNhaHang' => $_POST['ten_nhahang'] ?? '',
                    'DiaChi' => $_POST['dia_chi'] ?? '',
                    'Sdt' => $_POST['sdt'] ?? '',
                    'Email' => $_POST['email'] ?? '',
                    'MieuTa' => $_POST['mieu_ta'] ?? '',
                    'OpenTime' => $_POST['open_time'] ?? '',
                    'CloseTime' => $_POST['close_time'] ?? '',
                    'DungTich' => intval($_POST['dung_tich'] ?? 0)
                ];
                
                // Xử lý upload hình ảnh
                // Note: Xử lý upload ảnh thực tế sẽ phức tạp hơn, đây chỉ là mẫu
                $response = apiRequest('/nhahang/' . $restaurantId, 'POST', $restaurantData);
                
                if ($response['success']) {
                    $successMessage = 'Cập nhật nhà hàng thành công!';
                    
                    // Làm mới danh sách
                    $response = apiRequest('/nhahang', 'GET');
                    $restaurants = $response['data'] ?? [];
                    $totalRestaurants = count($restaurants);
                    $totalPages = ceil($totalRestaurants / $limit);
                    $paginatedRestaurants = array_slice($restaurants, $startIndex, $limit);
                } else {
                    $errorMessage = $response['message'] ?? 'Có lỗi xảy ra khi cập nhật nhà hàng';
                }
                break;
                
            case 'delete':
                // Xóa nhà hàng
                $restaurantId = intval($_POST['restaurant_id'] ?? 0);
                
                if (!$restaurantId) {
                    $errorMessage = 'ID nhà hàng không hợp lệ';
                    break;
                }
                
                $response = apiRequest('/nhahang/' . $restaurantId, 'DELETE');
                
                if ($response['success']) {
                    $successMessage = 'Xóa nhà hàng thành công!';
                    
                    // Làm mới danh sách
                    $response = apiRequest('/nhahang', 'GET');
                    $restaurants = $response['data'] ?? [];
                    $totalRestaurants = count($restaurants);
                    $totalPages = ceil($totalRestaurants / $limit);
                    $paginatedRestaurants = array_slice($restaurants, $startIndex, $limit);
                } else {
                    $errorMessage = $response['message'] ?? 'Có lỗi xảy ra khi xóa nhà hàng';
                }
                break;
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
    <meta name="description" content="Quản lý nhà hàng - Hệ thống đặt bàn nhà hàng">
    <!-- ======== Page title ============ -->
    <title>Quản lý nhà hàng - Hệ thống đặt bàn nhà hàng</title>
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
        .action-buttons {
            margin-bottom: 20px;
            text-align: right;
        }
        .action-btn {
            display: inline-block;
            padding: 10px 20px;
            border-radius: 5px;
            background-color: #ff5b00;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }
        .action-btn:hover {
            background-color: #e04f00;
            color: white;
        }
        .action-btn i {
            margin-right: 5px;
        }
        .restaurant-table {
            width: 100%;
            border-collapse: collapse;
        }
        .restaurant-table th, .restaurant-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .restaurant-table th {
            background-color: #f8f9fa;
            font-weight: 600;
        }
        .restaurant-table tr:hover {
            background-color: #f9f9f9;
        }
        .table-action-buttons {
            display: flex;
            gap: 5px;
        }
        .table-action-btn {
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 12px;
            cursor: pointer;
            border: none;
            transition: all 0.3s ease;
        }
        .edit-btn {
            background-color: #3498db;
            color: white;
        }
        .edit-btn:hover {
            background-color: #2980b9;
        }
        .delete-btn {
            background-color: #e74c3c;
            color: white;
        }
        .delete-btn:hover {
            background-color: #c0392b;
        }
        .view-btn {
            background-color: #2ecc71;
            color: white;
        }
        .view-btn:hover {
            background-color: #27ae60;
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
        .form-group {
            margin-bottom: 15px;
        }
        .form-label {
            font-weight: 600;
            margin-bottom: 5px;
        }
        .form-control {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .restaurant-image {
            width: 100px;
            height: 60px;
            object-fit: cover;
            border-radius: 5px;
        }
        .empty-restaurants {
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
                <h2 class="text-center">Quản lý nhà hàng</h2>
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
                        <?php if ($successMessage): ?>
                            <div class="alert alert-success mb-4">
                                <?php echo $successMessage; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($errorMessage): ?>
                            <div class="alert alert-danger mb-4">
                                <?php echo $errorMessage; ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="action-buttons">
                            <button type="button" class="action-btn" data-toggle="modal" data-target="#addRestaurantModal">
                                <i class="fas fa-plus"></i> Thêm nhà hàng
                            </button>
                        </div>
                        
                        <?php if (empty($paginatedRestaurants)): ?>
                            <div class="empty-restaurants">
                                <i class="fas fa-utensils fa-3x mb-3"></i>
                                <h3>Không có nhà hàng</h3>
                                <p>Chưa có nhà hàng nào trong hệ thống.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="restaurant-table">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Hình ảnh</th>
                                            <th>Tên nhà hàng</th>
                                            <th>Địa chỉ</th>
                                            <th>Số điện thoại</th>
                                            <th>Thời gian hoạt động</th>
                                            <th>Đánh giá</th>
                                            <th>Thao tác</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($paginatedRestaurants as $restaurant): ?>
                                            <tr>
                                                <td><?php echo $restaurant['ID_NhaHang']; ?></td>
                                                <td>
                                                    <?php if (!empty($restaurant['Anh1'])): ?>
                                                        <img src="<?php echo $restaurant['Anh1']; ?>" alt="<?php echo htmlspecialchars($restaurant['TenNhaHang']); ?>" class="restaurant-image">
                                                    <?php else: ?>
                                                        <img src="/restaurant-website/public/assets/img/default-restaurant.jpg" alt="Default" class="restaurant-image">
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($restaurant['TenNhaHang']); ?></td>
                                                <td><?php echo htmlspecialchars($restaurant['DiaChi']); ?></td>
                                                <td><?php echo htmlspecialchars($restaurant['Sdt']); ?></td>
                                                <td>
                                                    <?php
                                                        $openTime = $restaurant['OpenTime'] ? date('H:i', strtotime($restaurant['OpenTime'])) : 'N/A';
                                                        $closeTime = $restaurant['CloseTime'] ? date('H:i', strtotime($restaurant['CloseTime'])) : 'N/A';
                                                        echo $openTime . ' - ' . $closeTime;
                                                    ?>
                                                </td>
                                                <td>
                                                    <div class="rating-stars">
                                                        <?php
                                                            $rating = $restaurant['XepHangTrungBinh'] ?? 0;
                                                            for ($i = 1; $i <= 5; $i++) {
                                                                if ($i <= $rating) {
                                                                    echo '<i class="fas fa-star text-warning"></i>';
                                                                } else {
                                                                    echo '<i class="far fa-star text-warning"></i>';
                                                                }
                                                            }
                                                        ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="table-action-buttons">
                                                        <a href="/restaurant-website/public/restaurant-detail.php?id=<?php echo $restaurant['ID_NhaHang']; ?>" class="table-action-btn view-btn" target="_blank">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <button type="button" class="table-action-btn edit-btn" 
                                                                data-toggle="modal" data-target="#editRestaurantModal" 
                                                                data-id="<?php echo $restaurant['ID_NhaHang']; ?>"
                                                                data-name="<?php echo htmlspecialchars($restaurant['TenNhaHang']); ?>"
                                                                data-address="<?php echo htmlspecialchars($restaurant['DiaChi']); ?>"
                                                                data-phone="<?php echo htmlspecialchars($restaurant['Sdt']); ?>"
                                                                data-email="<?php echo htmlspecialchars($restaurant['Email']); ?>"
                                                                data-description="<?php echo htmlspecialchars($restaurant['MieuTa'] ?? ''); ?>"
                                                                data-open="<?php echo $openTime; ?>"
                                                                data-close="<?php echo $closeTime; ?>"
                                                                data-capacity="<?php echo $restaurant['DungTich']; ?>">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <form method="POST" style="display: inline;">
                                                            <input type="hidden" name="action" value="delete">
                                                            <input type="hidden" name="restaurant_id" value="<?php echo $restaurant['ID_NhaHang']; ?>">
                                                            <button type="submit" class="table-action-btn delete-btn" 
                                                                    onclick="return confirm('Bạn có chắc chắn muốn xóa nhà hàng này?')">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
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
                                                <a class="page-link" href="?page=<?php echo $page - 1; ?>">
                                                    &laquo;
                                                </a>
                                            </li>
                                        <?php endif; ?>
                                        
                                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                                <a class="page-link" href="?page=<?php echo $i; ?>">
                                                    <?php echo $i; ?>
                                                </a>
                                            </li>
                                        <?php endfor; ?>
                                        
                                        <?php if ($page < $totalPages): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?page=<?php echo $page + 1; ?>">
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

    <!-- Modal Thêm nhà hàng -->
    <div class="modal fade" id="addRestaurantModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Thêm nhà hàng mới</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="ten_nhahang" class="form-label">Tên nhà hàng *</label>
                                    <input type="text" class="form-control" id="ten_nhahang" name="ten_nhahang" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="dia_chi" class="form-label">Địa chỉ *</label>
                                    <input type="text" class="form-control" id="dia_chi" name="dia_chi" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="sdt" class="form-label">Số điện thoại *</label>
                                    <input type="text" class="form-control" id="sdt" name="sdt" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="email" class="form-label">Email *</label>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="open_time" class="form-label">Giờ mở cửa *</label>
                                    <input type="time" class="form-control" id="open_time" name="open_time" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="close_time" class="form-label">Giờ đóng cửa *</label>
                                    <input type="time" class="form-control" id="close_time" name="close_time" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="dung_tich" class="form-label">Sức chứa *</label>
                                    <input type="number" class="form-control" id="dung_tich" name="dung_tich" min="1" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="mieu_ta" class="form-label">Mô tả</label>
                            <textarea class="form-control" id="mieu_ta" name="mieu_ta" rows="4"></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Hình ảnh nhà hàng</label>
                            <div class="row">
                                <div class="col-md-4">
                                    <input type="file" class="form-control-file" name="anh1" accept="image/*">
                                    <small class="form-text text-muted">Ảnh chính</small>
                                </div>
                                <div class="col-md-4">
                                    <input type="file" class="form-control-file" name="anh2" accept="image/*">
                                    <small class="form-text text-muted">Ảnh 2</small>
                                </div>
                                <div class="col-md-4">
                                    <input type="file" class="form-control-file" name="anh3" accept="image/*">
                                    <small class="form-text text-muted">Ảnh 3</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
                        <button type="submit" class="btn btn-primary">Thêm nhà hàng</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Cập nhật nhà hàng -->
    <div class="modal fade" id="editRestaurantModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Cập nhật nhà hàng</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="restaurant_id" id="edit_restaurant_id">
                    
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="edit_ten_nhahang" class="form-label">Tên nhà hàng *</label>
                                    <input type="text" class="form-control" id="edit_ten_nhahang" name="ten_nhahang" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="edit_dia_chi" class="form-label">Địa chỉ *</label>
                                    <input type="text" class="form-control" id="edit_dia_chi" name="dia_chi" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="edit_sdt" class="form-label">Số điện thoại *</label>
                                    <input type="text" class="form-control" id="edit_sdt" name="sdt" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="edit_email" class="form-label">Email *</label>
                                    <input type="email" class="form-control" id="edit_email" name="email" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="edit_open_time" class="form-label">Giờ mở cửa *</label>
                                    <input type="time" class="form-control" id="edit_open_time" name="open_time" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="edit_close_time" class="form-label">Giờ đóng cửa *</label>
                                    <input type="time" class="form-control" id="edit_close_time" name="close_time" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="edit_dung_tich" class="form-label">Sức chứa *</label>
                                    <input type="number" class="form-control" id="edit_dung_tich" name="dung_tich" min="1" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="edit_mieu_ta" class="form-label">Mô tả</label>
                            <textarea class="form-control" id="edit_mieu_ta" name="mieu_ta" rows="4"></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Cập nhật hình ảnh (nếu cần)</label>
                            <div class="row">
                                <div class="col-md-4">
                                    <input type="file" class="form-control-file" name="anh1" accept="image/*">
                                    <small class="form-text text-muted">Ảnh chính</small>
                                </div>
                                <div class="col-md-4">
                                    <input type="file" class="form-control-file" name="anh2" accept="image/*">
                                    <small class="form-text text-muted">Ảnh 2</small>
                                </div>
                                <div class="col-md-4">
                                    <input type="file" class="form-control-file" name="anh3" accept="image/*">
                                    <small class="form-text text-muted">Ảnh 3</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    </div>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
                        <button type="submit" class="btn btn-primary">Cập nhật nhà hàng</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Footer Section -->
    <footer class="footer-area section-bg section-space">
        <div class="container">
            <div class="footer-wrapper">
                <div class="row">
                    <div class="col-lg-3 col-md-6">
                        <div class="footer-widget">
                            <div class="footer-logo">
                                <a href="/restaurant-website/public/index.php">
                                    <img src="/restaurant-website/public/assets/img/logo/logo-white.svg" alt="logo">
                                </a>
                            </div>
                            <p class="footerdesc">Hệ thống đặt bàn nhà hàng trực tuyến hàng đầu Việt Nam, giúp bạn tìm kiếm và đặt bàn tại các nhà hàng chất lượng.</p>
                            <div class="footer-social">
                                <a href="#"><i class="fab fa-facebook-f"></i></a>
                                <a href="#"><i class="fab fa-twitter"></i></a>
                                <a href="#"><i class="fab fa-instagram"></i></a>
                                <a href="#"><i class="fab fa-linkedin-in"></i></a>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="footer-widget">
                            <h4 class="footer-title">Liên kết nhanh</h4>
                            <ul class="footer-link">
                                <li><a href="/restaurant-website/public/index.php"><i class="fas fa-angle-right"></i> Trang chủ</a></li>
                                <li><a href="/restaurant-website/public/restaurants.php"><i class="fas fa-angle-right"></i> Nhà hàng</a></li>
                                <li><a href="/restaurant-website/public/menu.php"><i class="fas fa-angle-right"></i> Thực đơn</a></li>
                                <li><a href="/restaurant-website/public/datban.php"><i class="fas fa-angle-right"></i> Đặt bàn</a></li>
                                <li><a href="/restaurant-website/public/contact.php"><i class="fas fa-angle-right"></i> Liên hệ</a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="footer-widget">
                            <h4 class="footer-title">Giờ mở cửa</h4>
                            <ul class="opening-time">
                                <li>Thứ Hai - Thứ Sáu <span>08:00 - 21:00</span></li>
                                <li>Thứ Bảy <span>09:00 - 22:00</span></li>
                                <li>Chủ Nhật <span>10:00 - 21:00</span></li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="footer-widget">
                            <h4 class="footer-title">Thông tin liên hệ</h4>
                            <div class="contact-info">
                                <div class="single-contact">
                                    <div class="contact-icon">
                                        <i class="fas fa-map-marker-alt"></i>
                                    </div>
                                    <div class="contact-content">
                                        <p>123 Đường ABC, Quận 1, TP. Hồ Chí Minh</p>
                                    </div>
                                </div>
                                <div class="single-contact">
                                    <div class="contact-icon">
                                        <i class="fas fa-phone"></i>
                                    </div>
                                    <div class="contact-content">
                                        <p>+84 123 456 789</p>
                                    </div>
                                </div>
                                <div class="single-contact">
                                    <div class="contact-icon">
                                        <i class="fas fa-envelope"></i>
                                    </div>
                                    <div class="contact-content">
                                        <p>info@restaurant-booking.com</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="copyright-area text-center">
                <p>&copy; 2025 Food King Restaurant. Được phát triển bởi <a href="#" class="text-color">Booking Team</a></p>
            </div>
        </div>
    </footer>

    <!-- Scroll to top -->
    <a href="#" class="back-to-top"><i class="fas fa-angle-up"></i></a>

    <!-- JavaScript -->
    <script src="/restaurant-website/public/assets/js/jquery-3.7.1.min.js"></script>
    <script src="/restaurant-website/public/assets/js/popper.min.js"></script>
    <script src="/restaurant-website/public/assets/js/bootstrap.min.js"></script>
    <script src="/restaurant-website/public/assets/js/swiper-bundle.min.js"></script>
    <script src="/restaurant-website/public/assets/js/jquery.nice-select.min.js"></script>
    <script src="/restaurant-website/public/assets/js/jquery-ui.min.js"></script>
    <script src="/restaurant-website/public/assets/js/main.js"></script>

    <script>
        // Xử lý preloader
        $(window).on('load', function() {
            $('.preloader').fadeOut(1000);
        });

        // Xử lý modal chỉnh sửa
        $('#editRestaurantModal').on('show.bs.modal', function (event) {
            var button = $(event.relatedTarget);
            var id = button.data('id');
            var name = button.data('name');
            var address = button.data('address');
            var phone = button.data('phone');
            var email = button.data('email');
            var description = button.data('description');
            var openTime = button.data('open');
            var closeTime = button.data('close');
            var capacity = button.data('capacity');
            
            var modal = $(this);
            modal.find('#edit_restaurant_id').val(id);
            modal.find('#edit_ten_nhahang').val(name);
            modal.find('#edit_dia_chi').val(address);
            modal.find('#edit_sdt').val(phone);
            modal.find('#edit_email').val(email);
            modal.find('#edit_mieu_ta').val(description);
            modal.find('#edit_open_time').val(openTime);
            modal.find('#edit_close_time').val(closeTime);
            modal.find('#edit_dung_tich').val(capacity);
        });

        // Tự động đóng alert sau 5 giây
        setTimeout(function() {
            $('.alert').fadeOut('slow');
        }, 5000);

        // Xác nhận xóa nhà hàng
        $('.delete-btn').on('click', function(e) {
            if (!confirm('Bạn có chắc chắn muốn xóa nhà hàng này?')) {
                e.preventDefault();
                return false;
            }
            return true;
        });

        // Xử lý khi form thêm mới được submit
        $('#addRestaurantModal form').on('submit', function() {
            // Kiểm tra các trường bắt buộc
            var tenNhaHang = $('#ten_nhahang').val().trim();
            var diaChi = $('#dia_chi').val().trim();
            var sdt = $('#sdt').val().trim();
            var email = $('#email').val().trim();
            
            if (!tenNhaHang || !diaChi || !sdt || !email) {
                alert('Vui lòng điền đầy đủ thông tin bắt buộc');
                return false;
            }
            
            // Kiểm tra số điện thoại hợp lệ
            var phonePattern = /^[0-9]{10,11}$/;
            if (!phonePattern.test(sdt)) {
                alert('Số điện thoại không hợp lệ');
                return false;
            }
            
            // Kiểm tra email hợp lệ
            var emailPattern = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/;
            if (!emailPattern.test(email)) {
                alert('Email không hợp lệ');
                return false;
            }
            
            return true;
        });

        // Áp dụng kiểm tra tương tự cho form cập nhật
        $('#editRestaurantModal form').on('submit', function() {
            // Kiểm tra các trường bắt buộc
            var tenNhaHang = $('#edit_ten_nhahang').val().trim();
            var diaChi = $('#edit_dia_chi').val().trim();
            var sdt = $('#edit_sdt').val().trim();
            var email = $('#edit_email').val().trim();
            
            if (!tenNhaHang || !diaChi || !sdt || !email) {
                alert('Vui lòng điền đầy đủ thông tin bắt buộc');
                return false;
            }
            
            // Kiểm tra số điện thoại hợp lệ
            var phonePattern = /^[0-9]{10,11}$/;
            if (!phonePattern.test(sdt)) {
                alert('Số điện thoại không hợp lệ');
                return false;
            }
            
            // Kiểm tra email hợp lệ
            var emailPattern = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/;
            if (!emailPattern.test(email)) {
                alert('Email không hợp lệ');
                return false;
            }
            
            return true;
        });
    </script>
</body>
</html>