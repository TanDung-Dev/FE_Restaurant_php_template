<?php
require_once 'session.php';
checkUserLoggedIn();
$user = getCurrentUser();

// Lấy ID thanh toán từ URL
$paymentId = $_GET['id'] ?? 0;

if (!$paymentId) {
    header('Location: /restaurant-website/public/booking/my-bookings');
    exit;
}

// Lấy thông tin thanh toán
$paymentResponse = apiRequest('/thanh-toan/' . $paymentId, 'GET');
$payment = $paymentResponse['data'] ?? null;

if (!$payment) {
    header('Location: /restaurant-website/public/booking/my-bookings?error=payment_not_found');
    exit;
}

// Lấy ID đặt bàn từ thông tin thanh toán
$bookingId = $payment['ID_ThongTinDatBan'] ?? 0;

// Lấy thông tin đặt bàn
$bookingResponse = apiRequest('/dat-ban/' . $bookingId, 'GET');
$booking = $bookingResponse['data'] ?? null;

if (!$booking) {
    header('Location: /restaurant-website/public/booking/my-bookings?error=booking_not_found');
    exit;
}

// Lấy danh sách món ăn đã đặt
$orderedFoodsResponse = apiRequest('/chi-tiet-dat-mon?id_thongtin_datban=' . $bookingId, 'GET');
$orderedFoods = $orderedFoodsResponse['data'] ?? [];

// Tính tổng tiền
$totalAmount = $payment['SoLuong'] ?? 0;

// Lấy ID nhà hàng
$restaurantId = null;
if (!empty($booking['chi_tiet_dat_bans']) && 
    !empty($booking['chi_tiet_dat_bans'][0]['ban']) && 
    !empty($booking['chi_tiet_dat_bans'][0]['ban']['khu_vuc'])) {
    $restaurantId = $booking['chi_tiet_dat_bans'][0]['ban']['khu_vuc']['ID_NhaHang'];
}

// Lấy thông tin nhà hàng nếu có ID
$restaurant = null;
if ($restaurantId) {
    $restaurantResponse = apiRequest('/nhahang/' . $restaurantId, 'GET');
    $restaurant = $restaurantResponse['data'] ?? null;
}

// Kiểm tra đã đánh giá chưa
$userHasReviewed = false;
if ($restaurantId) {
    $reviewsResponse = apiRequest('/danh-gia?id_nhahang=' . $restaurantId . '&id_user=' . $user['ID_USER'], 'GET');
    $userHasReviewed = !empty($reviewsResponse['data']);
}

// Xử lý gửi đánh giá
$reviewSuccess = false;
$reviewError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    $rating = $_POST['rating'] ?? 0;
    $comment = $_POST['comment'] ?? '';
    
    if (!$rating) {
        $reviewError = 'Vui lòng chọn số sao đánh giá!';
    } elseif (empty($comment)) {
        $reviewError = 'Vui lòng nhập nội dung đánh giá!';
    } else {
        $reviewData = [
            'ID_NhaHang' => $restaurantId,
            'XepHang' => $rating,
            'BinhLuan' => $comment
        ];
        
        $reviewResponse = apiRequest('/danh-gia', 'POST', $reviewData);
        
        if ($reviewResponse && $reviewResponse['success']) {
            $reviewSuccess = true;
            $userHasReviewed = true;
        } else {
            $reviewError = $reviewResponse['message'] ?? 'Có lỗi xảy ra khi gửi đánh giá!';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thanh toán thành công - Nhà hàng</title>
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
        .success-section {
            padding: 80px 0;
            background-color: #f8f9fa;
        }
        .success-container {
            background-color: #fff;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            padding: 40px;
            text-align: center;
        }
        .success-icon {
            width: 80px;
            height: 80px;
            background-color: #d4edda;
            color: #28a745;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
            font-size: 40px;
        }
        .success-title {
            font-size: 24px;
            margin-bottom: 20px;
            color: #28a745;
        }
        .payment-details {
            margin: 30px auto;
            max-width: 500px;
            text-align: left;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #f5f5f5;
        }
        .detail-label {
            font-weight: 600;
            color: #555;
        }
        .payment-method {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 14px;
            background-color: #f8f9fa;
        }
        .actions {
            margin-top: 40px;
        }
        .btn-action {
            margin: 0 10px;
            padding: 10px 20px;
            border-radius: 5px;
        }
        
        /* Rating styles */
        .review-section {
            margin-top: 50px;
            text-align: left;
            padding-top: 30px;
            border-top: 1px solid #eee;
        }
        .review-heading {
            text-align: center;
            margin-bottom: 30px;
        }
        .star-rating {
            direction: rtl;
            display: flex;
            justify-content: center;
            padding: 20px 0;
        }
        .star-rating input[type="radio"] {
            display: none;
        }
        .star-rating label {
            color: #ddd;
            font-size: 30px;
            padding: 0 5px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .star-rating label:hover,
        .star-rating label:hover ~ label,
        .star-rating input[type="radio"]:checked ~ label {
            color: #ffb800;
        }
        .comment-box {
            margin-top: 20px;
        }
        .comment-box textarea {
            width: 100%;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            resize: vertical;
            min-height: 120px;
        }
        .review-submit {
            margin-top: 20px;
            text-align: center;
        }
        .btn-submit-review {
            background-color: #ff5b00;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .btn-submit-review:hover {
            background-color: #e64d00;
        }
        .review-thanks {
            text-align: center;
            padding: 30px;
            background-color: #f8f9fa;
            border-radius: 10px;
            margin-top: 20px;
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
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
                        <h2>Thanh toán thành công</h2>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <section class="success-section">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="success-container">
                        <div class="success-icon">
                            <i class="fas fa-check"></i>
                        </div>
                        <h3 class="success-title">Thanh toán thành công!</h3>
                        <p>Cảm ơn bạn đã đặt món tại nhà hàng của chúng tôi.</p>
                        
                        <div class="payment-details">
                            <div class="detail-row">
                                <div class="detail-label">Mã đơn đặt bàn:</div>
                                <div>#<?php echo $bookingId; ?></div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">Thời gian đặt bàn:</div>
                                <div><?php echo date('H:i - d/m/Y', strtotime($booking['ThoiGianDatBan'])); ?></div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">Số lượng khách:</div>
                                <div><?php echo $booking['SoLuongKhach']; ?> người</div>
                            </div>
                            <?php if ($restaurant): ?>
                            <div class="detail-row">
                                <div class="detail-label">Nhà hàng:</div>
                                <div><?php echo htmlspecialchars($restaurant['TenNhaHang']); ?></div>
                            </div>
                            <?php endif; ?>
                            <div class="detail-row">
                                <div class="detail-label">Tổng thanh toán:</div>
                                <div><?php echo number_format($totalAmount, 0, ',', '.'); ?>đ</div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">Phương thức thanh toán:</div>
                                <div>
                                    <span class="payment-method">
                                        <?php
                                        switch ($payment['PhuongThucThanhToan']) {
                                            case 1:
                                                echo '<i class="fas fa-money-bill-wave"></i> Tiền mặt';
                                                break;
                                            case 2:
                                                echo '<i class="fas fa-credit-card"></i> Thẻ ngân hàng';
                                                break;
                                            case 3:
                                                echo '<i class="fas fa-university"></i> Chuyển khoản';
                                                break;
                                            case 4:
                                                echo '<i class="fas fa-wallet"></i> Ví MoMo';
                                                break;
                                            default:
                                                echo 'Khác';
                                        }
                                        ?>
                                    </span>
                                </div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">Mã giao dịch:</div>
                                <div><?php echo $payment['MaGiaoDich'] ?? ($payment['MoMo_TransId'] ?? 'N/A'); ?></div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">Thời gian thanh toán:</div>
                                <div><?php echo date('H:i - d/m/Y', strtotime($payment['NgayThanhToan'])); ?></div>
                            </div>
                        </div>
                        
                        <?php if ($restaurant && !$userHasReviewed): ?>
                        <div class="review-section">
                            <h4 class="review-heading">Đánh giá trải nghiệm của bạn</h4>
                            
                            <?php if ($reviewError): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle"></i> <?php echo $reviewError; ?>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($reviewSuccess): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle"></i> Cảm ơn bạn đã gửi đánh giá!
                            </div>
                            <?php else: ?>
                            <form method="POST" action="">
                                <div class="star-rating">
                                    <input type="radio" id="star5" name="rating" value="5" />
                                    <label for="star5" title="5 sao"><i class="fas fa-star"></i></label>
                                    <input type="radio" id="star4" name="rating" value="4" />
                                    <label for="star4" title="4 sao"><i class="fas fa-star"></i></label>
                                    <input type="radio" id="star3" name="rating" value="3" />
                                    <label for="star3" title="3 sao"><i class="fas fa-star"></i></label>
                                    <input type="radio" id="star2" name="rating" value="2" />
                                    <label for="star2" title="2 sao"><i class="fas fa-star"></i></label>
                                    <input type="radio" id="star1" name="rating" value="1" />
                                    <label for="star1" title="1 sao"><i class="fas fa-star"></i></label>
                                </div>
                                
                                <div class="comment-box">
                                    <textarea name="comment" placeholder="Hãy chia sẻ trải nghiệm của bạn về nhà hàng..."></textarea>
                                </div>
                                
                                <div class="review-submit">
                                    <button type="submit" name="submit_review" class="btn-submit-review">
                                        <i class="fas fa-paper-plane"></i> Gửi đánh giá
                                    </button>
                                </div>
                            </form>
                            <?php endif; ?>
                        </div>
                        <?php elseif ($userHasReviewed): ?>
                        <div class="review-thanks">
                            <h5><i class="fas fa-heart text-danger"></i> Cảm ơn bạn đã đánh giá!</h5>
                            <p>Chúng tôi rất trân trọng những phản hồi của bạn và sẽ cải thiện dịch vụ tốt hơn.</p>
                        </div>
                        <?php endif; ?>
                        
                        <div class="actions">
                            <a href="/restaurant-website/public/booking/my-bookings" class="btn btn-primary btn-action">
                                <i class="fas fa-list"></i> Xem đơn đặt bàn
                            </a>
                            <a href="/restaurant-website/public/" class="btn btn-outline-primary btn-action">
                                <i class="fas fa-home"></i> Trang chủ
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php include __DIR__ . '/../../app/includes/footer.php'; ?>

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