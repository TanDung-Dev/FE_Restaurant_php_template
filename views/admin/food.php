<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/restaurant-website/public/session.php';

// Kiểm tra quyền admin
checkAdminAccess();

// Xác định đường dẫn hiện tại cho menu active
$path = '/admin/food';

// Cấu hình API URL
$apiBaseUrl = 'http://localhost:8000/api';

// Lấy danh sách món ăn
$response = apiRequest('/mon-an', 'GET');
$foods = $response['data'] ?? [];

// Lấy danh sách loại món ăn để hiển thị trong form
$categoryResponse = apiRequest('/loai-mon-an', 'GET');
$categories = $categoryResponse['data'] ?? [];

// Lấy danh sách nhà hàng để hiển thị trong form
$restaurantResponse = apiRequest('/nhahang', 'GET');
$restaurants = $restaurantResponse['data'] ?? [];

// Khởi tạo biến
$editFood = null;
$foodId = null;
$error = null;
$verboseLog = '';

// Lấy thông tin món ăn để chỉnh sửa nếu có
if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $foodId = $_GET['id'];
    $foodResponse = apiRequest('/mon-an/' . $foodId, 'GET');
    
    if ($foodResponse['success'] ?? false) {
        $editFood = $foodResponse['data'];
    } else {
        $error = 'Không thể lấy thông tin món ăn. ' . ($foodResponse['message'] ?? 'Lỗi không xác định.');
    }
}

// Xử lý xóa món ăn
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $foodId = $_GET['id'];
    $deleteResponse = apiRequest('/mon-an/' . $foodId, 'DELETE');
    
    if ($deleteResponse['success'] ?? false) {
        header('Location: /restaurant-website/public/admin/food?message=deleted');
        exit;
    } else {
        $error = $deleteResponse['message'] ?? 'Có lỗi xảy ra khi xóa món ăn';
    }
}

// Xử lý thêm/cập nhật món ăn
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Lấy ID món ăn nếu đang cập nhật
    $foodId = isset($_POST['food_id']) ? $_POST['food_id'] : null;
    
    // Chuẩn bị dữ liệu form
    $formData = [
        'ID_NhaHang' => $_POST['id_nhahang'],
        'MaLoai' => $_POST['ma_loai'],
        'TenMonAn' => $_POST['ten_monan'],
        'MoTa' => $_POST['mo_ta'] ?? '',
        'Gia' => $_POST['gia'],
        'TrangThai' => $_POST['trang_thai'],
    ];
    
    // Thêm ID_MonAn nếu là cập nhật
    if ($foodId) {
        $formData['ID_MonAn'] = $foodId;
    }
    
    // Xử lý upload ảnh (tối đa 5 ảnh)
    for ($i = 1; $i <= 5; $i++) {
        $fieldName = 'Anh' . $i;
        if (isset($_FILES[$fieldName]) && $_FILES[$fieldName]['error'] == 0) {
            $formData[$fieldName] = curl_file_create(
                $_FILES[$fieldName]['tmp_name'],
                $_FILES[$fieldName]['type'],
                $_FILES[$fieldName]['name']
            );
        }
    }
    
    // Xác định endpoint dựa vào việc đang thêm mới hay cập nhật
    $endpoint = $foodId ? '/mon-an/' . $foodId : '/mon-an';
    
    try {
        // Gửi request thông qua cURL
        $ch = curl_init($apiBaseUrl . $endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $formData);
        
        // Thêm debug info
        $verboseLog = "Request to: " . $apiBaseUrl . $endpoint . "\n";
        $verboseLog .= "Method: POST\n";
        $verboseLog .= "Data: " . print_r($formData, true) . "\n";
        
        // Thêm header Authorization
        $token = getAuthToken();
        if ($token) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token]);
            $verboseLog .= "Token: " . $token . "\n";
        } else {
            $verboseLog .= "No token available\n";
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            throw new Exception("cURL Error: " . curl_error($ch));
        }
        
        curl_close($ch);
        
        $verboseLog .= "Response Code: " . $httpCode . "\n";
        $verboseLog .= "Response: " . $response . "\n";
        
        $result = json_decode($response, true);
        
        // Xử lý response
        if ($httpCode >= 200 && $httpCode < 300 && ($result['success'] ?? false)) {
            $redirectMessage = $foodId ? 'updated' : 'created';
            header('Location: /restaurant-website/public/admin/food?message=' . $redirectMessage);
            exit;
        } else {
            $error = $result['message'] ?? 'Có lỗi xảy ra khi ' . ($foodId ? 'cập nhật' : 'thêm') . ' món ăn';
            if (isset($result['errors'])) {
                $errors = [];
                foreach ($result['errors'] as $field => $errorMessages) {
                    $errors[] = $field . ': ' . implode(', ', $errorMessages);
                }
                $error .= '<br>' . implode('<br>', $errors);
            }
        }
        
    } catch (Exception $e) {
        $error = 'Lỗi khi gửi request: ' . $e->getMessage();
        $verboseLog .= "Exception: " . $e->getMessage() . "\n";
    }
}

// Xử lý thông báo
$message = '';
if (isset($_GET['message'])) {
    switch ($_GET['message']) {
        case 'deleted':
            $message = 'Món ăn đã được xóa thành công';
            break;
        case 'updated':
            $message = 'Món ăn đã được cập nhật thành công';
            break;
        case 'created':
            $message = 'Món ăn đã được thêm thành công';
            break;
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
    <meta name="description" content="Quản lý món ăn - Hệ thống đặt bàn nhà hàng">
    <!-- ======== Page title ============ -->
    <title>Quản lý món ăn - Hệ thống đặt bàn nhà hàng</title>
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
        .food-image-preview {
            margin-top: 10px;
            max-width: 150px;
            max-height: 150px;
            border-radius: 5px;
        }
        .food-table-image {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 5px;
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
                            <a href="/restaurant-website/public/" class="header-logo">
                                <img src="/restaurant-website/public/assets/img/logo/logo.svg" alt="logo-img">
                            </a>
                        </div>
                        <div class="header-right d-flex justify-content-end align-items-center">
                            <div class="header-button">
                            <a href="/restaurant-website/public/logout" class="theme-btn bg-red-2">Đăng xuất</a>
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
                <h2 class="text-center">Quản lý món ăn</h2>
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
                    
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger">
                            <?php echo $error; ?>
                            <?php if (!empty($verboseLog)): ?>
                                <button class="btn btn-sm btn-outline-secondary mt-2" type="button" data-bs-toggle="collapse" data-bs-target="#debugInfo">
                                    Show Debug Info
                                </button>
                                <div class="collapse mt-2" id="debugInfo">
                                    <div class="card card-body">
                                        <pre><?php echo htmlspecialchars($verboseLog); ?></pre>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Form thêm/sửa món ăn -->
                    <div class="content-card">
                        <h3><?php echo $editFood ? 'Cập nhật món ăn' : 'Thêm món ăn mới'; ?></h3>
                        <form method="POST" action="" enctype="multipart/form-data">
                            <?php if ($editFood): ?>
                                <input type="hidden" name="food_id" value="<?php echo $editFood['ID_MonAn']; ?>">
                            <?php endif; ?>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="id_nhahang" class="form-label">Nhà hàng <span class="text-danger">*</span></label>
                                    <select class="form-select" id="id_nhahang" name="id_nhahang" required>
                                        <option value="">Chọn nhà hàng</option>
                                        <?php foreach ($restaurants as $restaurant): ?>
                                            <option value="<?php echo $restaurant['ID_NhaHang']; ?>" <?php echo ($editFood && $editFood['ID_NhaHang'] == $restaurant['ID_NhaHang']) ? 'selected' : ''; ?>>
                                                <?php echo $restaurant['TenNhaHang']; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="ma_loai" class="form-label">Loại món ăn <span class="text-danger">*</span></label>
                                    <select class="form-select" id="ma_loai" name="ma_loai" required>
                                        <option value="">Chọn loại món ăn</option>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?php echo $category['MaLoai']; ?>" <?php echo ($editFood && $editFood['MaLoai'] == $category['MaLoai']) ? 'selected' : ''; ?>>
                                                <?php echo $category['TenLoai']; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="ten_monan" class="form-label">Tên món ăn <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="ten_monan" name="ten_monan" value="<?php echo $editFood ? $editFood['TenMonAn'] : ''; ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="mo_ta" class="form-label">Mô tả</label>
                                <textarea class="form-control" id="mo_ta" name="mo_ta" rows="3"><?php echo $editFood ? $editFood['MoTa'] : ''; ?></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label for="gia" class="form-label">Giá <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="gia" name="gia" value="<?php echo $editFood ? $editFood['Gia'] : ''; ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="trang_thai" class="form-label">Trạng thái <span class="text-danger">*</span></label>
                                <select class="form-select" id="trang_thai" name="trang_thai" required>
                                    <option value="1" <?php echo ($editFood && $editFood['TrangThai'] == 1) ? 'selected' : ''; ?>>Hiển thị</option>
                                    <option value="0" <?php echo ($editFood && $editFood['TrangThai'] == 0) ? 'selected' : ''; ?>>Ẩn</option>
                                </select>
                            </div>
                            
                            <div class="row">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <div class="col-md-4 mb-3">
                                        <label for="Anh<?php echo $i; ?>" class="form-label">Ảnh <?php echo $i; ?></label>
                                        <input type="file" class="form-control" id="Anh<?php echo $i; ?>" name="Anh<?php echo $i; ?>" accept="image/*">
                                        <?php if ($editFood && !empty($editFood['Anh' . $i])): ?>
                                            <div class="mt-2">
                                                <img src="<?php echo $apiBaseUrl . '/../' . $editFood['Anh' . $i]; ?>" alt="Food Image <?php echo $i; ?>" class="food-image-preview">
                                                <p class="small text-muted mt-1">Ảnh hiện tại</p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endfor; ?>
                            </div>
                            
                            <div class="d-flex">
                                <button type="submit" class="theme-btn me-2">
                                    <?php echo $editFood ? 'Cập nhật' : 'Thêm mới'; ?>
                                </button>
                                <?php if ($editFood): ?>
                                    <a href="/restaurant-website/public/admin/food" class="theme-btn bg-secondary">Hủy</a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Danh sách món ăn -->
                    <div class="content-card">
                        <h3>Danh sách món ăn</h3>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Ảnh</th>
                                        <th>Tên món ăn</th>
                                        <th>Loại</th>
                                        <th>Giá</th>
                                        <th>Trạng thái</th>
                                        <th>Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($foods)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center">Không có dữ liệu</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($foods as $food): ?>
                                            <tr>
                                                <td><?php echo $food['ID_MonAn']; ?></td>
                                                <td>
                                                    <?php if (!empty($food['Anh1'])): ?>
                                                        <img src="<?php echo $apiBaseUrl . '/../' . $food['Anh1']; ?>" alt="Food Image" class="food-table-image">
                                                    <?php else: ?>
                                                        <img src="/restaurant-website/public/assets/img/food/Default-food-image.png" alt="Default Food Image" class="food-table-image">
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo $food['TenMonAn']; ?></td>
                                                <td>
                                                    <?php 
                                                        $categoryName = 'N/A';
                                                        foreach ($categories as $category) {
                                                            if ($category['MaLoai'] == $food['MaLoai']) {
                                                                $categoryName = $category['TenLoai'];
                                                                break;
                                                            }
                                                        }
                                                        echo $categoryName;
                                                    ?>
                                                </td>
                                                <td><?php echo number_format($food['Gia'], 0, ',', '.'); ?> đ</td>
                                                <td>
                                                    <?php if ($food['TrangThai'] == 1): ?>
                                                        <span class="badge bg-success">Hiển thị</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">Ẩn</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="table-action">
                                                    <a href="/restaurant-website/public/admin/food?action=edit&id=<?php echo $food['ID_MonAn']; ?>" class="btn btn-sm btn-primary"><i class="fas fa-edit"></i></a>
                                                    <a href="/restaurant-website/public/admin/food?action=delete&id=<?php echo $food['ID_MonAn']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Bạn có chắc chắn muốn xóa món ăn này?');"><i class="fas fa-trash"></i></a>
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
                    © Copyright 2025 <a href="/restaurant-website/public/">Restaurant Booking</a>. All Rights Reserved.
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