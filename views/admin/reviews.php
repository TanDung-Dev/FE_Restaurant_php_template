<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/restaurant-website/public/session.php';

// Kiểm tra quyền admin
checkAdminAccess();

// Lấy danh sách đánh giá
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 10; // Số lượng đánh giá mỗi trang
$restaurant_id = isset($_GET['restaurant_id']) ? intval($_GET['restaurant_id']) : 0;
$rating = isset($_GET['rating']) ? intval($_GET['rating']) : 0;

// Xây dựng query
$query = '/danh-gia';
$filters = [];

if ($restaurant_id > 0) {
    $filters[] = 'id_nhahang=' . $restaurant_id;
}

if ($rating > 0) {
    $filters[] = 'xep_hang=' . $rating;
}

if (!empty($filters)) {
    $query .= '?' . implode('&', $filters);
}

$response = apiRequest($query, 'GET');
$reviews = $response['data'] ?? [];

// Lấy danh sách nhà hàng cho filter
$restaurantsResponse = apiRequest('/nhahang', 'GET');
$restaurants = $restaurantsResponse['data'] ?? [];

// Phân trang
$totalReviews = count($reviews);
$totalPages = ceil($totalReviews / $limit);
$startIndex = ($page - 1) * $limit;
$paginatedReviews = array_slice($reviews, $startIndex, $limit);

// Xử lý các action form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && isset($_POST['review_id'])) {
        $action = $_POST['action'];
        $reviewId = intval($_POST['review_id']);
        
        switch ($action) {
            case 'delete':
                // Xóa đánh giá
                $deleteResponse = apiRequest('/danh-gia/' . $reviewId, 'DELETE');
                if ($deleteResponse['success']) {
                    $successMessage = 'Đã xóa đánh giá thành công!';
                } else {
                    $errorMessage = $deleteResponse['message'] ?? 'Có lỗi xảy ra khi xóa đánh giá';
                }
                break;
            
            case 'reply':
                // Trả lời đánh giá
                if (isset($_POST['reply_content']) && !empty($_POST['reply_content'])) {
                    $replyData = [
                        'TraLoi' => $_POST['reply_content']
                    ];
                    
                    $replyResponse = apiRequest('/danh-gia/' . $reviewId . '/tra-loi', 'POST', $replyData);
                    if ($replyResponse['success']) {
                        $successMessage = 'Đã trả lời đánh giá thành công!';
                    } else {
                        $errorMessage = $replyResponse['message'] ?? 'Có lỗi xảy ra khi trả lời đánh giá';
                    }
                } else {
                    $errorMessage = 'Vui lòng nhập nội dung trả lời';
                }
                break;
        }
        
        // Cập nhật lại danh sách đánh giá
        $response = apiRequest($query, 'GET');
        $reviews = $response['data'] ?? [];
        $totalReviews = count($reviews);
        $totalPages = ceil($totalReviews / $limit);
        $paginatedReviews = array_slice($reviews, $startIndex, $limit);
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
    <meta name="description" content="Quản lý đánh giá - Hệ thống đặt bàn nhà hàng">
    <!-- ======== Page title ============ -->
    <title>Quản lý đánh giá - Hệ thống đặt bàn nhà hàng</title>
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
            gap: 15px;
        }
        .filter-group {
            display: flex;
            align-items: center;
        }
        .filter-label {
            font-weight: 600;
            margin-right: 10px;
        }
        .filter-control {
            min-width: 200px;
        }
        .review-card {
            margin-bottom: 20px;
            border: 1px solid #eee;
            border-radius: 10px;
            overflow: hidden;
        }
        .review-header {
            padding: 15px;
            background-color: #f8f9fa;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .review-restaurant {
            font-weight: 600;
            color: #333;
        }
        .review-rating {
            display: flex;
            align-items: center;
        }
        .rating-stars {
            color: #ffc107;
            margin-right: 5px;
        }
        .review-date {
            font-size: 14px;
            color: #777;
        }
        .review-body {
            padding: 15px;
        }
        .reviewer-info {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }
        .reviewer-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 10px;
            overflow: hidden;
        }
        .reviewer-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .reviewer-avatar i {
            font-size: 20px;
            color: #aaa;
        }
        .reviewer-name {
            font-weight: 600;
        }
        .review-content {
            margin-bottom: 15px;
            color: #333;
        }
        .review-reply {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 15px;
        }
        .reply-title {
            font-weight: 600;
            margin-bottom: 5px;
            display: flex;
            align-items: center;
        }
        .reply-title i {
            margin-right: 5px;
            color: #ff5b00;
        }
        .reply-content {
            color: #555;
        }
        .review-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            padding-top: 10px;
            border-top: 1px solid #eee;
        }
        .review-action-btn {
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 14px;
            cursor: pointer;
            border: none;
            transition: all 0.3s ease;
        }
        .reply-btn {
            background-color: #3498db;
            color: white;
        }
        .reply-btn:hover {
            background-color: #2980b9;
        }
        .delete-btn {
            background-color: #e74c3c;
            color: white;
        }
        .delete-btn:hover {
            background-color: #c0392b;
        }
        .reply-form {
            margin-top: 15px;
            display: none;
        }
        .reply-textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            resize: vertical;
            min-height: 100px;
            margin-bottom: 10px;
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
        .empty-reviews {
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
                <h2 class="text-center">Quản lý đánh giá</h2>
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
                            <div class="filter-group">
                                <div class="filter-label">Nhà hàng:</div>
                                <select class="form-control filter-control" id="restaurant-filter">
                                    <option value="0">Tất cả nhà hàng</option>
                                    <?php foreach ($restaurants as $r): ?>
                                        <option value="<?php echo $r['ID_NhaHang']; ?>" <?php echo $restaurant_id == $r['ID_NhaHang'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($r['TenNhaHang']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="filter-group">
                                <div class="filter-label">Đánh giá:</div>
                                <select class="form-control filter-control" id="rating-filter">
                                    <option value="0">Tất cả đánh giá</option>
                                    <option value="5" <?php echo $rating == 5 ? 'selected' : ''; ?>>5 sao</option>
                                    <option value="4" <?php echo $rating == 4 ? 'selected' : ''; ?>>4 sao</option>
                                    <option value="3" <?php echo $rating == 3 ? 'selected' : ''; ?>>3 sao</option>
                                    <option value="2" <?php echo $rating == 2 ? 'selected' : ''; ?>>2 sao</option>
                                    <option value="1" <?php echo $rating == 1 ? 'selected' : ''; ?>>1 sao</option>
                                </select>
                            </div>
                            
                            <button class="btn btn-primary" id="apply-filter">Lọc</button>
                        </div>
                        
                        <?php if (empty($paginatedReviews)): ?>
                            <div class="empty-reviews">
                                <i class="fas fa-star fa-3x mb-3"></i>
                                <h3>Không có đánh giá nào</h3>
                                <p>Chưa có đánh giá nào phù hợp với bộ lọc.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($paginatedReviews as $review): ?>
                                <div class="review-card">
                                    <div class="review-header">
                                        <div class="review-restaurant">
                                            <?php echo htmlspecialchars($review['nhaHang']['TenNhaHang'] ?? 'Nhà hàng chưa xác định'); ?>
                                        </div>
                                        <div class="review-rating">
                                            <div class="rating-stars">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <?php if ($i <= $review['XepHang']): ?>
                                                        <i class="fas fa-star"></i>
                                                    <?php else: ?>
                                                        <i class="far fa-star"></i>
                                                    <?php endif; ?>
                                                <?php endfor; ?>
                                            </div>
                                            <div class="review-date">
                                                <?php echo date('d/m/Y', strtotime($review['NgayTao'])); ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="review-body">
                                        <div class="reviewer-info">
                                            <div class="reviewer-avatar">
                                                <?php if (isset($review['user']['Anh']) && !empty($review['user']['Anh'])): ?>
                                                    <img src="<?php echo $review['user']['Anh']; ?>" alt="Avatar">
                                                <?php else: ?>
                                                    <i class="fas fa-user"></i>
                                                <?php endif; ?>
                                            </div>
                                            <div class="reviewer-name">
                                                <?php echo htmlspecialchars($review['user']['HoVaTen'] ?? 'Người dùng ẩn danh'); ?>
                                            </div>
                                        </div>
                                        <div class="review-content">
                                            <?php echo nl2br(htmlspecialchars($review['BinhLuan'])); ?>
                                        </div>
                                        
                                        <?php if (!empty($review['TraLoi'])): ?>
                                            <div class="review-reply">
                                                <div class="reply-title">
                                                    <i class="fas fa-reply"></i> Phản hồi của nhà hàng
                                                </div>
                                                <div class="reply-content">
                                                    <?php echo nl2br(htmlspecialchars($review['TraLoi'])); ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="review-actions">
                                            <?php if (empty($review['TraLoi'])): ?>
                                                <button type="button" class="review-action-btn reply-btn" onclick="showReplyForm(<?php echo $review['ID_DanhGia']; ?>)">
                                                    <i class="fas fa-reply"></i> Trả lời
                                                </button>
                                            <?php endif; ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="review_id" value="<?php echo $review['ID_DanhGia']; ?>">
                                                <button type="submit" class="review-action-btn delete-btn" onclick="return confirm('Bạn có chắc chắn muốn xóa đánh giá này?')">
                                                    <i class="fas fa-trash"></i> Xóa
                                                </button>
                                            </form>
                                        </div>
                                        
                                        <div class="reply-form" id="reply-form-<?php echo $review['ID_DanhGia']; ?>">
                                            <form method="POST">
                                                <input type="hidden" name="action" value="reply">
                                                <input type="hidden" name="review_id" value="<?php echo $review['ID_DanhGia']; ?>">
                                                <textarea name="reply_content" class="reply-textarea" placeholder="Nhập nội dung trả lời..."></textarea>
                                                <div class="text-right">
                                                    <button type="button" class="btn btn-secondary" onclick="hideReplyForm(<?php echo $review['ID_DanhGia']; ?>)">Hủy</button>
                                                    <button type="submit" class="btn btn-primary">Gửi trả lời</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            
                            <!-- Phân trang -->
                            <?php if ($totalPages > 1): ?>
                                <div class="pagination-container">
                                    <ul class="pagination">
                                        <?php if ($page > 1): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?page=<?php echo $page - 1; ?>&restaurant_id=<?php echo $restaurant_id; ?>&rating=<?php echo $rating; ?>">
                                                    &laquo;
                                                </a>
                                            </li>
                                        <?php endif; ?>
                                        
                                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                                <a class="page-link" href="?page=<?php echo $i; ?>&restaurant_id=<?php echo $restaurant_id; ?>&rating=<?php echo $rating; ?>">
                                                    <?php echo $i; ?>
                                                </a>
                                            </li>
                                        <?php endfor; ?>
                                        
                                        <?php if ($page < $totalPages): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?page=<?php echo $page + 1; ?>&restaurant_id=<?php echo $restaurant_id; ?>&rating=<?php echo $rating; ?>">
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

    <!-- All JS Plugins -->
    <script src="/restaurant-website/public/assets/js/jquery-3.7.1.min.js"></script>
    <script src="/restaurant-website/public/assets/js/bootstrap.bundle.min.js"></script>
    <script src="/restaurant-website/public/assets/js/swiper-bundle.min.js"></script>
    <script src="/restaurant-website/public/assets/js/wow.min.js"></script>
    <script src="/restaurant-website/public/assets/js/main.js"></script>
    
    <script>
        // Hiển thị form trả lời
        function showReplyForm(reviewId) {
            $('#reply-form-' + reviewId).show();
        }
        
        // Ẩn form trả lời
        function hideReplyForm(reviewId) {
            $('#reply-form-' + reviewId).hide();
        }
        
        // Áp dụng bộ lọc
        $('#apply-filter').click(function() {
            const restaurantId = $('#restaurant-filter').val();
            const rating = $('#rating-filter').val();
            
            window.location.href = '?restaurant_id=' + restaurantId + '&rating=' + rating;
        });
    </script>
</body>
</html>