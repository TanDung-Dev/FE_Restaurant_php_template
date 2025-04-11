<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/restaurant-website/public/session.php';

// Kiểm tra quyền admin
checkAdminAccess();

// Lấy danh sách loại món ăn
$response = apiRequest('/loai-mon-an', 'GET');
$categories = $response['data'] ?? [];

// Xử lý xóa loại món ăn
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $categoryId = $_GET['id'];
    $deleteResponse = apiRequest('/loai-mon-an/' . $categoryId, 'DELETE');
    
    if ($deleteResponse['success'] ?? false) {
        header('Location: /restaurant-website/public/admin/categories?message=deleted');
        exit;
    } else {
        $error = $deleteResponse['message'] ?? 'Có lỗi xảy ra khi xóa loại món ăn';
    }
}

// Xử lý thêm/cập nhật loại món ăn
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $categoryId = $_POST['category_id'] ?? null;
    $data = [
        'TenLoai' => $_POST['ten_loai'] ?? '',
        'MoTa' => $_POST['mo_ta'] ?? ''
    ];
    
    if ($categoryId) {
        // Cập nhật loại món ăn
        $updateResponse = apiRequest('/loai-mon-an/' . $categoryId, 'PUT', $data);
        
        if ($updateResponse['success'] ?? false) {
            header('Location: /restaurant-website/public/admin/categories?message=updated');
            exit;
        } else {
            $error = $updateResponse['message'] ?? 'Có lỗi xảy ra khi cập nhật loại món ăn';
        }
    } else {
        // Thêm loại món ăn mới
        $createResponse = apiRequest('/loai-mon-an', 'POST', $data);
        
        if ($createResponse['success'] ?? false) {
            header('Location: /restaurant-website/public/admin/categories?message=created');
            exit;
        } else {
            $error = $createResponse['message'] ?? 'Có lỗi xảy ra khi thêm loại món ăn';
        }
    }
}

// Xử lý thông báo
$message = '';
if (isset($_GET['message'])) {
    switch ($_GET['message']) {
        case 'deleted':
            $message = 'Loại món ăn đã được xóa thành công';
            break;
        case 'updated':
            $message = 'Loại món ăn đã được cập nhật thành công';
            break;
        case 'created':
            $message = 'Loại món ăn đã được thêm thành công';
            break;
    }
}

// Lấy thông tin loại món ăn để chỉnh sửa nếu có
$editCategory = null;
if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $categoryId = $_GET['id'];
    $categoryResponse = apiRequest('/loai-mon-an/' . $categoryId, 'GET');
    
    if ($categoryResponse['success'] ?? false) {
        $editCategory = $categoryResponse['data'];
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
    <meta name="description" content="Quản lý loại món ăn - Hệ thống đặt bàn nhà hàng">
    <!-- ======== Page title ============ -->
    <title>Quản lý loại món ăn - Hệ thống đặt bàn nhà hàng</title>
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
        .content-card {
            background: #fff;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
        }
        .table-action a {
            margin-right: 5px;
        }
        .alert {
            margin-bottom: 20px;
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
                <h2 class="text-center">Quản lý loại món ăn</h2>
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
                    <?php if (!empty($message)): ?>
                        <div class="alert alert-success">
                            <?php echo $message; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger">
                            <?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Form thêm/sửa loại món ăn -->
                    <div class="content-card">
                        <h3><?php echo $editCategory ? 'Cập nhật loại món ăn' : 'Thêm loại món ăn mới'; ?></h3>
                        <form method="POST" action="">
                            <?php if ($editCategory): ?>
                                <input type="hidden" name="category_id" value="<?php echo $editCategory['MaLoai']; ?>">
                            <?php endif; ?>
                            
                            <div class="mb-3">
                                <label for="ten_loai" class="form-label">Tên loại món ăn <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="ten_loai" name="ten_loai" value="<?php echo $editCategory ? $editCategory['TenLoai'] : ''; ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="mo_ta" class="form-label">Mô tả</label>
                                <textarea class="form-control" id="mo_ta" name="mo_ta" rows="3"><?php echo $editCategory ? $editCategory['MoTa'] : ''; ?></textarea>
                            </div>
                            
                            <div class="d-flex">
                                <button type="submit" class="theme-btn me-2">
                                    <?php echo $editCategory ? 'Cập nhật' : 'Thêm mới'; ?>
                                </button>
                                <?php if ($editCategory): ?>
                                    <a href="categories.php" class="theme-btn bg-secondary">Hủy</a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Danh sách loại món ăn -->
                    <div class="content-card">
                        <h3>Danh sách loại món ăn</h3>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Mã loại</th>
                                        <th>Tên loại</th>
                                        <th>Mô tả</th>
                                        <th>Trạng thái</th>
                                        <th>Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($categories)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center">Không có dữ liệu</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($categories as $category): ?>
                                            <tr>
                                                <td><?php echo $category['MaLoai']; ?></td>
                                                <td><?php echo $category['TenLoai']; ?></td>
                                                <td><?php echo $category['MoTa'] ?? 'Không có mô tả'; ?></td>
                                                <td>
                                                    <?php if ($category['Hide'] == 0): ?>
                                                        <span class="badge bg-success">Hiển thị</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">Ẩn</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="table-action">
                                                <a href="/restaurant-website/public/admin/categories?action=edit&id=<?php echo $category['MaLoai']; ?>" class="btn btn-sm btn-primary"><i class="fas fa-edit"></i></a>                                                   
                                                    <a href="/restaurant-website/public/admin/categories?action=delete&id=<?php echo $category['MaLoai']; ?>" 
                                                    class="btn btn-sm btn-danger" onclick="return confirm('Bạn có chắc chắn muốn xóa loại món ăn này?');"><i class="fas fa-trash"></i></a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
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