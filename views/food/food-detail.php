<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/restaurant-website/public/session.php';

// Kiểm tra có ID món ăn không
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: /restaurant-website/public/menu.php');
    exit;
}

$foodId = $_GET['id'];

// Lấy thông tin chi tiết món ăn
$response = apiRequest('/mon-an/' . $foodId, 'GET');

// Kiểm tra kết quả từ API
if (!isset($response['success']) || !$response['success']) {
    header('Location: /restaurant-website/public/menu.php?error=notfound');
    exit;
}

$food = $response['data'];

// Lấy thông tin loại món ăn
$loaiMonAn = null;
if (!empty($food['MaLoai'])) {
    $loaiMonAnResponse = apiRequest('/loai-mon-an/' . $food['MaLoai'], 'GET');
    if (isset($loaiMonAnResponse['success']) && $loaiMonAnResponse['success']) {
        $loaiMonAn = $loaiMonAnResponse['data'];
    }
}

// Lấy thông tin nhà hàng của món ăn
$restaurant = null;
if (!empty($food['ID_NhaHang'])) {
    $restaurantResponse = apiRequest('/nhahang/' . $food['ID_NhaHang'], 'GET');
    if (isset($restaurantResponse['success']) && $restaurantResponse['success']) {
        $restaurant = $restaurantResponse['data'];
    }
}

// Lấy danh sách món ăn cùng loại để gợi ý
$relatedFoods = [];
if (!empty($food['MaLoai']) && !empty($food['ID_NhaHang'])) {
    $relatedFoodsResponse = apiRequest('/mon-an?ma_loai=' . $food['MaLoai'] . '&id_nhahang=' . $food['ID_NhaHang'], 'GET');
    if (isset($relatedFoodsResponse['success']) && $relatedFoodsResponse['success']) {
        $relatedFoods = $relatedFoodsResponse['data'] ?? [];
        
        // Lọc danh sách món ăn liên quan (loại bỏ món ăn hiện tại và giới hạn 4 món)
        $relatedFoods = array_filter($relatedFoods, function($item) use ($foodId) {
            return $item['ID_MonAn'] != $foodId && $item['TrangThai'] == 1;
        });
        $relatedFoods = array_slice($relatedFoods, 0, 4);
    }
}

// Hàm helper để lấy URL hình ảnh đầy đủ
function getFullImageUrl($imagePath) {
    if (empty($imagePath)) {
        return '/restaurant-website/public/assets/img/food/default-food.jpg';
    }
    
    $baseUrl = 'http://localhost:8000';
    
    // Đảm bảo đường dẫn không bắt đầu bằng /
    $imagePath = ltrim($imagePath, '/');
    
    return $baseUrl . '/' . $imagePath;
}

// Chuẩn bị dữ liệu hình ảnh
$foodImages = [];
for ($i = 1; $i <= 5; $i++) {
    $imageField = 'Anh' . $i;
    if (!empty($food[$imageField])) {
        $foodImages[] = getFullImageUrl($food[$imageField]);
    }
}

// Lấy thông tin người dùng nếu đã đăng nhập
$user = getCurrentUser();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <!-- Meta Tags -->
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="author" content="modinatheme">
    <meta name="description" content="Chi tiết món ăn - <?php echo $food['TenMonAn']; ?>">
    <!-- Page title -->
    <title><?php echo $food['TenMonAn']; ?> - Chi tiết món ăn</title>
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
    <!-- Custom styles for food detail page -->
    <style>
        .food-detail-section {
            padding: 100px 0;
            background-color: #f8f9fa;
        }
        .food-detail-wrapper {
            background-color: #fff;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
        }
        .main-image-container {
            position: relative;
            overflow: hidden;
            border-radius: 15px;
            margin-bottom: 20px;
        }
        .main-image {
            width: 100%;
            height: 400px;
            object-fit: cover;
            transition: transform 0.5s ease;
        }
        .main-image:hover {
            transform: scale(1.05);
        }
        .thumbnail-gallery {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
        }
        .thumbnail-item {
            width: 100px;
            height: 100px;
            border-radius: 10px;
            overflow: hidden;
            cursor: pointer;
        }
        .thumbnail-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        .thumbnail-item:hover img {
            transform: scale(1.1);
        }
        .thumbnail-item.active {
            border: 2px solid #ff5b00;
        }
        .food-title {
            margin-bottom: 20px;
        }
        .food-price {
            font-size: 24px;
            color: #ff5b00;
            font-weight: 700;
            margin-bottom: 20px;
        }
        .food-description {
            margin-bottom: 30px;
            color: #666;
            line-height: 1.8;
        }
        .food-meta {
            margin-bottom: 30px;
        }
        .meta-item {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        .meta-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: rgba(255, 91, 0, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            color: #ff5b00;
        }
        .related-foods-title {
            margin-bottom: 30px;
            padding-top: 30px;
            border-top: 1px solid #eee;
        }
        .related-food-card {
            background-color: #f9f9f9;
            border-radius: 15px;
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            margin-bottom: 20px;
        }
        .related-food-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.1);
        }
        .related-food-image {
            height: 200px;
            overflow: hidden;
        }
        .related-food-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }
        .related-food-card:hover .related-food-image img {
            transform: scale(1.1);
        }
        .related-food-info {
            padding: 20px;
        }
        .related-food-title {
            font-size: 18px;
            margin-bottom: 10px;
        }
        .related-food-price {
            color: #ff5b00;
            font-weight: 700;
        }
        .action-buttons {
            margin-top: 30px;
        }
        .action-buttons .theme-btn {
            margin-right: 15px;
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
    <?php include __DIR__ . '/../../app/includes/header.php'; ?>

    <!-- Food Detail Section -->
    <section class="food-detail-section">
        <div class="container">
            <div class="food-detail-wrapper">
                <div class="row">
                    <div class="col-lg-6">
                        <!-- Main Image -->
                        <div class="main-image-container">
                            <img src="<?php echo !empty($foodImages) ? $foodImages[0] : 'assets/img/food/default-food.jpg'; ?>" alt="<?php echo $food['TenMonAn']; ?>" class="main-image" id="main-food-image">
                        </div>

                        <!-- Thumbnail Gallery -->
                        <?php if (count($foodImages) > 1): ?>
                            <div class="thumbnail-gallery">
                                <?php foreach ($foodImages as $index => $image): ?>
                                    <div class="thumbnail-item <?php echo $index === 0 ? 'active' : ''; ?>" data-image="<?php echo $image; ?>">
                                        <img src="<?php echo $image; ?>" alt="<?php echo $food['TenMonAn']; ?> thumbnail">
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="col-lg-6">
                        <div class="food-details-content">
                            <div class="food-title">
                                <h2><?php echo $food['TenMonAn']; ?></h2>
                            </div>
                            <div class="food-price">
                                <?php echo number_format($food['Gia'], 0, ',', '.'); ?> đ
                            </div>
                            <div class="food-description">
                                <p><?php echo !empty($food['MoTa']) ? $food['MoTa'] : 'Không có mô tả cho món ăn này.'; ?></p>
                            </div>
                            
                            <div class="food-meta">
                                <div class="meta-item">
                                    <div class="meta-icon">
                                        <i class="fas fa-utensils"></i>
                                    </div>
                                    <div class="meta-text">
                                        <strong>Loại món:</strong> 
                                        <?php 
                                        if (isset($food['loaiMonAn']) && isset($food['loaiMonAn']['TenLoai'])) {
                                            echo $food['loaiMonAn']['TenLoai'];
                                        } elseif (isset($loaiMonAn) && isset($loaiMonAn['TenLoai'])) {
                                            echo $loaiMonAn['TenLoai'];
                                        } else {
                                            echo 'Không xác định';
                                        }
                                        ?>
                                    </div>
                                </div>
                                <div class="meta-item">
                                    <div class="meta-icon">
                                        <i class="fas fa-store"></i>
                                    </div>
                                    <div class="meta-text">
                                        <strong>Nhà hàng:</strong> 
                                        <?php 
                                        if (isset($restaurant) && isset($restaurant['TenNhaHang'])) {
                                            echo $restaurant['TenNhaHang'];
                                        } else {
                                            echo 'Không xác định';
                                        }
                                        ?>
                                    </div>
                                </div>
                                <div class="meta-item">
                                    <div class="meta-icon">
                                        <i class="fas fa-check-circle"></i>
                                    </div>
                                    <div class="meta-text">
                                        <strong>Trạng thái:</strong> 
                                        <?php if ($food['TrangThai'] == 1): ?>
                                            <span class="text-success">Còn món</span>
                                        <?php else: ?>
                                            <span class="text-secondary">Hết món</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="action-buttons">
                                <a href="/restaurant-website/public/datban" class="theme-btn">
                                    <span class="button-content-wrapper d-flex align-items-center">
                                        <span class="button-icon"><i class="fas fa-calendar-check"></i></span>
                                        <span class="button-text">Đặt bàn ngay</span>
                                    </span>
                                </a>
                                <a href="/restaurant-website/public/menu.php" class="theme-btn bg-secondary">
                                    <span class="button-content-wrapper d-flex align-items-center">
                                        <span class="button-icon"><i class="fas fa-arrow-left"></i></span>
                                        <span class="button-text">Quay lại menu</span>
                                    </span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Related Foods -->
                <?php if (!empty($relatedFoods)): ?>
                    <div class="related-foods">
                        <h3 class="related-foods-title">Món ăn liên quan</h3>
                        <div class="row">
                            <?php foreach ($relatedFoods as $relatedFood): ?>
                                <div class="col-lg-3 col-md-6">
                                    <div class="related-food-card">
                                        <div class="related-food-image">
                                            <a href="/restaurant-website/public/food/food-detail.php?id=<?php echo $relatedFood['ID_MonAn']; ?>">
                                                <img src="<?php echo getFullImageUrl($relatedFood['Anh1']); ?>" alt="<?php echo $relatedFood['TenMonAn']; ?>">
                                            </a>
                                        </div>
                                        <div class="related-food-info">
                                            <h4 class="related-food-title">
                                                <a href="/restaurant-website/public/food/food-detail.php?id=<?php echo $relatedFood['ID_MonAn']; ?>"><?php echo $relatedFood['TenMonAn']; ?></a>
                                            </h4>
                                            <div class="related-food-price">
                                                <?php echo number_format($relatedFood['Gia'], 0, ',', '.'); ?> đ
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <?php include __DIR__ . '/../../app/includes/footer.php'; ?>

    <!-- Back to top area -->
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

    <!-- Custom script for food detail page -->
    <script>
         $(document).ready(function() {
            // Handle thumbnail click
            $('.thumbnail-item').click(function() {
                const imageUrl = $(this).data('image');
                $('#main-food-image').attr('src', imageUrl);
                
                // Toggle active class
                $('.thumbnail-item').removeClass('active');
                $(this).addClass('active');
            });
        
            
            // Handle add to cart button click
            $('.add-to-cart-btn').click(function(e) {
                e.preventDefault();
                const foodId = $(this).data('food-id');
                // Implement your add to cart functionality here
                
                // Show success message
                alert('Đã thêm món ăn vào giỏ hàng!');
            });
        });
    </script>
</body>
</html>



