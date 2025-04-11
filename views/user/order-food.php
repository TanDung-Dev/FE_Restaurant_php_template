<?php
require_once 'session.php';
checkUserLoggedIn();
$user = getCurrentUser();

// Lấy ID đặt bàn từ URL
$bookingId = $_GET['id'] ?? 0;

if (!$bookingId) {
    header('Location: /restaurant-website/public/booking/my-bookings');
    exit;
}

// Lấy thông tin đặt bàn
$bookingResponse = apiRequest('/dat-ban/' . $bookingId, 'GET');
$booking = $bookingResponse['data'] ?? null;

if (!$booking) {
    header('Location: /restaurant-website/public/booking/my-bookings?error=booking_not_found');
    exit;
}

// Kiểm tra đơn đặt bàn thuộc về người dùng hiện tại
if ($booking['user']['ID_USER'] != $user['ID_USER']) {
    header('Location: /restaurant-website/public/booking/my-bookings?error=unauthorized');
    exit;
}

// Bỏ điều kiện kiểm tra trạng thái để có thể đặt món ở mọi trạng thái (trừ hủy)
$updateData = [
    'TrangThai' => 1
];
apiRequest('/dat-ban/' . $bookingId, 'PUT', $updateData);

// Lấy danh sách món ăn đã đặt
$orderedFoodsResponse = apiRequest('/chi-tiet-dat-mon?id_thongtin_datban=' . $bookingId, 'GET');
$orderedFoods = $orderedFoodsResponse['data'] ?? [];

// Tính tổng tiền hiện tại
$currentTotal = 0;
foreach ($orderedFoods as $food) {
    $currentTotal += $food['ThanhTien'];
}

// Lấy danh sách loại món ăn
$categoriesResponse = apiRequest('/loai-mon-an', 'GET');
$categories = $categoriesResponse['data'] ?? [];

// Lấy thông tin nhà hàng từ booking
$restaurantId = null;

// Lấy thông tin bàn từ chi tiết đặt bàn
if (!empty($booking['chi_tiet_dat_bans']) && !empty($booking['chi_tiet_dat_bans'][0]['ban'])) {
    $table = $booking['chi_tiet_dat_bans'][0]['ban'];
    
    // Lấy thông tin khu vực
    if (!empty($table['khu_vuc'])) {
        $area = $table['khu_vuc'];
        $restaurantId = $area['ID_NhaHang'];
    }
}

// Lấy danh sách món ăn theo nhà hàng
$foodsResponse = apiRequest('/mon-an?id_nhahang=' . $restaurantId . '&trang_thai=1', 'GET');
$allFoods = $foodsResponse['data'] ?? [];

// Phân loại món ăn theo loại
$foodsByCategory = [];
foreach ($allFoods as $food) {
    $categoryId = $food['MaLoai'];
    if (!isset($foodsByCategory[$categoryId])) {
        $foodsByCategory[$categoryId] = [];
    }
    $foodsByCategory[$categoryId][] = $food;
}

// Xử lý thêm món ăn vào đơn
$successMsg = "";
$errorMsg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'add_food') {
        $foodId = $_POST['food_id'] ?? 0;
        $quantity = $_POST['quantity'] ?? 1;
        
        if ($foodId && $quantity > 0) {
            $foodData = [
                'ID_ThongTinDatBan' => $bookingId,
                'ID_MonAn' => $foodId,
                'SoLuong' => $quantity,
            ];

            $response = apiRequest('/chi-tiet-dat-mon/them-mon', 'POST', $foodData);

            if ($response['success']) {
                $successMsg = "Thêm món ăn thành công!";
                
                // Cập nhật lại danh sách món ăn đã đặt
                $orderedFoodsResponse = apiRequest('/chi-tiet-dat-mon?id_thongtin_datban=' . $bookingId, 'GET');
                $orderedFoods = $orderedFoodsResponse['data'] ?? [];
                
                // Tính lại tổng tiền
                $currentTotal = 0;
                foreach ($orderedFoods as $food) {
                    $currentTotal += $food['ThanhTien'];
                }
            } else {
                $errorMsg = $response['message'] ?? "Có lỗi xảy ra khi thêm món ăn!";
            }
        } else {
            $errorMsg = "Thông tin món ăn không hợp lệ!";
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'remove_food') {
        $foodId = $_POST['food_id'] ?? 0;
        
        if ($foodId) {
            $removeData = [
                'ID_ThongTinDatBan' => $bookingId,
                'ID_MonAn' => $foodId
            ];
            
            $response = apiRequest('/chi-tiet-dat-mon/xoa-mon', 'POST', $removeData);
            
            if ($response['success']) {
                $successMsg = "Đã xóa món ăn khỏi đơn đặt bàn!";
                
                // Cập nhật lại danh sách món ăn đã đặt
                $orderedFoodsResponse = apiRequest('/chi-tiet-dat-mon?id_thongtin_datban=' . $bookingId, 'GET');
                $orderedFoods = $orderedFoodsResponse['data'] ?? [];
                
                // Tính lại tổng tiền
                $currentTotal = 0;
                foreach ($orderedFoods as $food) {
                    $currentTotal += $food['ThanhTien'];
                }
            } else {
                $errorMsg = $response['message'] ?? "Có lỗi xảy ra khi xóa món ăn!";
            }
        } else {
            $errorMsg = "Thông tin món ăn không hợp lệ!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Đặt món - Hệ thống đặt bàn nhà hàng">
    <title>Đặt món - Hệ thống đặt bàn nhà hàng</title>
    <link rel="shortcut icon" href="/restaurant-website/public/assets/img/logo/favicon.svg">
    <link rel="stylesheet" href="/restaurant-website/public/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="/restaurant-website/public/assets/css/font-awesome.css">
    <link rel="stylesheet" href="/restaurant-website/public/assets/css/animate.css">
    <link rel="stylesheet" href="/restaurant-website/public/assets/css/main.css">
    <style>
        .order-section {
            padding: 80px 0;
            background-color: #f8f9fa;
        }
        .order-container {
            background-color: #fff;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            padding: 30px;
            margin-bottom: 30px;
        }
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        .order-header h3 {
            margin-bottom: 0;
            color: #333;
        }
        .booking-status {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .status-pending {
            background-color: #fff8e6;
            color: #f2b01e;
        }
        .status-confirmed {
            background-color: #e1f7e7;
            color: #28a745;
        }
        .status-cancelled {
            background-color: #ffe6e6;
            color: #dc3545;
        }
        .booking-info {
            margin-bottom: 20px;
        }
        .info-row {
            display: flex;
            margin-bottom: 10px;
        }
        .info-label {
            width: 150px;
            font-weight: 600;
        }
        .category-title {
            margin: 30px 0 20px;
            font-size: 22px;
            border-bottom: 2px solid #ff5b00;
            padding-bottom: 10px;
            color: #333;
        }
        .food-list {
            margin-bottom: 40px;
        }
        .food-item {
            border: 1px solid #eee;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }
        .food-item:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .food-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 10px;
        }
        .food-description {
            color: #666;
            margin-bottom: 10px;
        }
        .food-price {
            color: #ff5b00;
            font-weight: 700;
            font-size: 18px;
            margin-bottom: 15px;
        }
        .food-actions {
            display: flex;
            align-items: center;
        }
        .quantity {
            display: flex;
            align-items: center;
            margin-right: 15px;
        }
        .quantity-btn {
            width: 30px;
            height: 30px;
            border: 1px solid #ddd;
            background-color: #f5f5f5;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }
        .quantity-input {
            width: 50px;
            height: 30px;
            text-align: center;
            border: 1px solid #ddd;
            margin: 0 5px;
        }
        .btn-add {
            background-color: #ff5b00;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
        }
        .order-summary {
            background-color: #fff;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            padding: 30px;
            position: sticky;
            top: 30px;
        }
        .summary-title {
            font-size: 20px;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #ff5b00;
        }
        .ordered-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #f5f5f5;
        }
        .item-name {
            font-weight: 600;
        }
        .item-price {
            color: #666;
        }
        .item-quantity {
            background-color: #f5f5f5;
            padding: 3px 8px;
            border-radius: 3px;
            margin-left: 10px;
        }
        .item-total {
            font-weight: 600;
            color: #333;
        }
        .item-remove {
            color: #dc3545;
            cursor: pointer;
            margin-left: 10px;
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            font-size: 18px;
            font-weight: 700;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px solid #eee;
        }
        .btn-checkout {
            width: 100%;
            background-color: #28a745;
            color: white;
            border: none;
            padding: 15px;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 20px;
        }
        .empty-list {
            text-align: center;
            padding: 30px;
            color: #666;
        }
        .empty-list i {
            font-size: 50px;
            color: #ddd;
            margin-bottom: 15px;
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../../app/includes/header.php'; ?>

    <div class="breadcrumb-area bg-img" style="background-image: url('/restaurant-website/public/assets/img/bg/breadcrumb.jpg');">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="breadcrumb-inner text-center">
                        <h2>Đặt món ăn</h2>
                        <ul class="page-list">
                            <li><a href="/restaurant-website/public/">Trang chủ</a></li>
                            <li><a href="/restaurant-website/public/booking/my-bookings">Đơn đặt bàn của tôi</a></li>
                            <li>Đặt món ăn</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <section class="order-section">
        <div class="container">
            <?php if ($successMsg): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $successMsg; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($errorMsg): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $errorMsg; ?>
                </div>
            <?php endif; ?>
            
            <div class="row">
                <div class="col-lg-8">
                    <div class="order-container">
                        <div class="order-header">
                            <h3>Thông tin đặt bàn #<?php echo $booking['ID_ThongTinDatBan']; ?></h3>
                            
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
                            
                            <span class="booking-status <?php echo $statusClass; ?>">
                                <?php echo $statusText; ?>
                            </span>
                        </div>
                        
                        <div class="booking-info">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="info-row">
                                        <div class="info-label">Ngày đặt bàn:</div>
                                        <div><?php echo date('d/m/Y', strtotime($booking['ThoiGianDatBan'])); ?></div>
                                    </div>
                                    <div class="info-row">
                                        <div class="info-label">Giờ đặt bàn:</div>
                                        <div><?php echo date('H:i', strtotime($booking['ThoiGianDatBan'])); ?></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-row">
                                        <div class="info-label">Số lượng khách:</div>
                                        <div><?php echo $booking['SoLuongKhach']; ?> người</div>
                                    </div>
                                    <div class="info-row">
                                        <div class="info-label">Nhà hàng:</div>
                                        <div>
                                            <?php
                                            if (!empty($booking['chi_tiet_dat_bans']) && 
                                                !empty($booking['chi_tiet_dat_bans'][0]['ban']) && 
                                                !empty($booking['chi_tiet_dat_bans'][0]['ban']['khu_vuc'])) {
                                                $nhaHangId = $booking['chi_tiet_dat_bans'][0]['ban']['khu_vuc']['ID_NhaHang'];
                                                // Gọi API lấy thông tin nhà hàng nếu cần
                                                $nhaHangResponse = apiRequest('/nhahang/' . $nhaHangId, 'GET');
                                                $nhaHang = $nhaHangResponse['data'] ?? null;
                                                echo $nhaHang ? htmlspecialchars($nhaHang['TenNhaHang']) : 'Không có thông tin';
                                            } else {
                                                echo 'Không có thông tin';
                                            }
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Danh sách món ăn theo danh mục -->
                        <h4>Danh sách món ăn</h4>
                        
                        <?php if (empty($categories) || empty($allFoods)): ?>
                            <div class="empty-list">
                                <i class="fas fa-utensils"></i>
                                <h5>Không có món ăn</h5>
                                <p>Không tìm thấy món ăn nào cho nhà hàng này.</p>
                            </div>
                        <?php else: ?>
                            <!-- Hiển thị danh sách món ăn theo từng danh mục -->
                            <?php foreach ($categories as $category): ?>
                                <?php
                                // Kiểm tra xem danh mục có món ăn không
                                $categoryId = $category['MaLoai'];
                                $foodsInCategory = array_filter($allFoods, function($food) use ($categoryId) {
                                    return $food['MaLoai'] == $categoryId;
                                });
                                
                                if (empty($foodsInCategory)) continue;
                                ?>
                                
                                <h4 class="category-title"><?php echo htmlspecialchars($category['TenLoai']); ?></h4>
                                
                                <div class="food-list">
                                    <div class="row">
                                        <?php foreach ($foodsInCategory as $food): ?>
                                            <div class="col-md-6">
                                                <div class="food-item">
                                                    <h5 class="food-title"><?php echo htmlspecialchars($food['TenMonAn']); ?></h5>
                                                    <p class="food-description">
                                                        <?php echo htmlspecialchars($food['MoTa'] ?? 'Không có mô tả'); ?>
                                                    </p>
                                                    <div class="food-price"><?php echo number_format($food['Gia'], 0, ',', '.'); ?>đ</div>
                                                    
                                                    <form method="POST" class="food-actions">
                                                        <input type="hidden" name="action" value="add_food">
                                                        <input type="hidden" name="food_id" value="<?php echo $food['ID_MonAn']; ?>">
                                                        
                                                        <div class="quantity">
                                                            <div class="quantity-btn" onclick="decrementQuantity(this)">-</div>
                                                            <input type="number" class="quantity-input" name="quantity" value="1" min="1" max="20">
                                                            <div class="quantity-btn" onclick="incrementQuantity(this)">+</div>
                                                        </div>
                                                        
                                                        <button type="submit" class="btn-add">
                                                            <i class="fas fa-plus"></i> Thêm món
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="col-lg-4">
                    <div class="order-summary">
                        <h4 class="summary-title">Món ăn đã đặt</h4>
                        
                        <?php if (empty($orderedFoods)): ?>
                            <div class="empty-list">
                                <i class="fas fa-shopping-cart"></i>
                                <h5>Chưa có món ăn</h5>
                                <p>Bạn chưa đặt món ăn nào.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($orderedFoods as $food): ?>
                                <div class="ordered-item">
                                    <div>
                                        <div class="item-name">
                                            <?php 
                                            if (isset($food['monAn']) && isset($food['monAn']['TenMonAn'])) {
                                                echo htmlspecialchars($food['monAn']['TenMonAn']);
                                            } else {
                                                echo 'Món ăn không xác định';
                                            }
                                            ?>
                                            <span class="item-quantity">x<?php echo $food['SoLuong']; ?></span>
                                        </div>
                                        <div class="item-price">
                                            <?php echo number_format($food['DonGia'], 0, ',', '.'); ?>đ
                                        </div>
                                    </div>
                                    <div>
                                        <span class="item-total"><?php echo number_format($food['ThanhTien'], 0, ',', '.'); ?>đ</span>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="remove_food">
                                            <input type="hidden" name="food_id" value="<?php echo $food['ID_MonAn']; ?>">
                                            <button type="submit" class="item-remove" title="Xóa">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            
                            <div class="total-row">
                                <span>Tổng cộng:</span>
                                <span><?php echo number_format($currentTotal, 0, ',', '.'); ?>đ</span>
                            </div>
                            
                            <a href="/restaurant-website/public/payment?id=<?php echo $bookingId; ?>" class="btn-checkout">
                                <i class="fas fa-credit-card"></i> Thanh toán
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php include __DIR__ . '/../../app/includes/footer.php'; ?>

    <script src="/restaurant-website/public/assets/js/jquery-3.7.1.min.js"></script>
    <script src="/restaurant-website/public/assets/js/bootstrap.bundle.min.js"></script>
    <script src="/restaurant-website/public/assets/js/main.js"></script>
    
    <script>
    // Tăng số lượng
    function incrementQuantity(button) {
        const input = button.parentNode.querySelector('.quantity-input');
        const currentValue = parseInt(input.value);
        if (currentValue < 20) {
            input.value = currentValue + 1;
        }
    }
    
    // Giảm số lượng
    function decrementQuantity(button) {
        const input = button.parentNode.querySelector('.quantity-input');
        const currentValue = parseInt(input.value);
        if (currentValue > 1) {
            input.value = currentValue - 1;
        }
    }
    
    // Ẩn thông báo sau 5 giây
    setTimeout(function() {
        document.querySelectorAll('.alert').forEach(function(alert) {
            alert.style.display = 'none';
        });
    }, 5000);
    </script>
</body>
</html>

