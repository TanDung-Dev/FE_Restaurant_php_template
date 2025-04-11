<?php
require_once '../../session.php';
checkUserLoggedIn();
$user = getCurrentUser();

// Lấy trạng thái lọc từ query string
$status = isset($_GET['status']) ? intval($_GET['status']) : -1; // -1 là tất cả
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 10; // Số lượng đơn mỗi trang

// Lấy danh sách đặt bàn của người dùng
$query = '/dat-ban?id_user=' . $user['ID_USER'];

// Thêm param trạng thái nếu không phải lấy tất cả
if ($status >= 0) {
    $query .= '&trang_thai=' . $status;
}

$response = apiRequest($query, 'GET');
$bookings = $response['data'] ?? [];

// Phân trang
$totalBookings = count($bookings);
$totalPages = ceil($totalBookings / $limit);
$startIndex = ($page - 1) * $limit;
$paginatedBookings = array_slice($bookings, $startIndex, $limit);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <?php include '../../includes/header.php'; ?>
    <title>Lịch sử đặt bàn - Nhà hàng</title>
    <style>
        .bookings-container {
            margin-top: 30px;
        }
        .booking-card {
            margin-bottom: 20px;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.05);
            overflow: hidden;
            transition: all 0.3s ease;
        }
        .booking-card:hover {
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            transform: translateY(-3px);
        }
        .booking-header {
            padding: 15px 20px;
            background-color: #f8f9fa;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .booking-id {
            font-weight: 600;
            color: #3498db;
        }
        .booking-date {
            color: #666;
        }
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 14px;
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
        .booking-body {
            padding: 20px;
        }
        .booking-info {
            display: flex;
            flex-wrap: wrap;
            margin-bottom: 15px;
        }
        .booking-info-item {
            margin-right: 30px;
            margin-bottom: 10px;
        }
        .booking-info-label {
            font-weight: 600;
            margin-bottom: 5px;
            color: #555;
        }
        .booking-info-value {
            color: #333;
        }
        .booking-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }
        .payment-status {
            font-weight: 600;
        }
        .payment-status.paid {
            color: #27ae60;
        }
        .payment-status.not-paid {
            color: #e74c3c;
        }
        .action-buttons {
            display: flex;
            gap: 10px;
        }
        .action-btn {
            padding: 7px 15px;
            border-radius: 5px;
            font-size: 14px;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        .view-btn {
            background-color: #3498db;
            color: white;
        }
        .view-btn:hover {
            background-color: #2980b9;
            color: white;
        }
        .cancel-btn {
            background-color: #e74c3c;
            color: white;
        }
        .cancel-btn:hover {
            background-color: #c0392b;
            color: white;
        }
        .payment-btn {
            background-color: #2ecc71;
            color: white;
        }
        .payment-btn:hover {
            background-color: #27ae60;
            color: white;
        }
        .filter-section {
            margin-bottom: 30px;
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
        }
        .filter-btn:hover {
            background-color: #f8f9fa;
        }
        .filter-btn.active {
            background-color: #3498db;
            color: white;
            border-color: #3498db;
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
            padding: 5px 10px;
            border: 1px solid #ddd;
            color: #3498db;
            border-radius: 3px;
            text-decoration: none;
        }
        .page-item.active .page-link {
            background-color: #3498db;
            color: white;
            border-color: #3498db;
        }
        .empty-bookings {
            text-align: center;
            padding: 50px 0;
            color: #777;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <?php include '../../includes/navigation.php'; ?>

    <div class="container mt-5 mb-5">
        <div class="row">
            <div class="col-12">
                <h1 class="mb-4">Lịch sử đặt bàn</h1>
                
                <div class="filter-section">
                    <div class="filter-label">Lọc theo trạng thái:</div>
                    <div class="filter-buttons">
                        <a href="?status=-1" class="filter-btn <?php echo $status == -1 ? 'active' : ''; ?>">Tất cả</a>
                        <a href="?status=0" class="filter-btn <?php echo $status == 0 ? 'active' : ''; ?>">Chờ xác nhận</a>
                        <a href="?status=1" class="filter-btn <?php echo $status == 1 ? 'active' : ''; ?>">Đã xác nhận</a>
                        <a href="?status=2" class="filter-btn <?php echo $status == 2 ? 'active' : ''; ?>">Đã hủy</a>
                    </div>
                </div>
                
                <div class="bookings-container">
                    <?php if (empty($paginatedBookings)): ?>
                        <div class="empty-bookings">
                            <i class="fas fa-calendar-times fa-3x mb-3"></i>
                            <h3>Không có đơn đặt bàn</h3>
                            <p>Bạn chưa có đơn đặt bàn nào trong hệ thống.</p>
                            <a href="/restaurant-website/public/datban.php" class="btn btn-primary mt-3">Đặt bàn ngay</a>
                        </div>
                    <?php else: ?>
                        <?php foreach ($paginatedBookings as $booking): ?>
                            <?php
                                // Lấy thông tin bàn và khu vực
                                $tableInfo = $booking['chiTietDatBans'][0]['ban'] ?? null;
                                $areaInfo = $tableInfo ? $tableInfo['khuVuc'] ?? null : null;
                                $restaurantInfo = $areaInfo ? $areaInfo['nhaHang'] ?? null : null;
                                
                                // Kiểm tra trạng thái thanh toán
                                $paymentInfo = $booking['thanhToan'] ?? null;
                                $isPaid = $paymentInfo && $paymentInfo['TrangThaiThanhToan'] == 1;
                                
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
                            <div class="booking-card">
                                <div class="booking-header">
                                    <div class="booking-id">Đơn #<?php echo $booking['ID_ThongTinDatBan']; ?></div>
                                    <div class="booking-date">Đặt ngày: <?php echo date('d/m/Y H:i', strtotime($booking['NgayTao'])); ?></div>
                                    <div class="status-badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></div>
                                </div>
                                <div class="booking-body">
                                    <div class="booking-info">
                                        <div class="booking-info-item">
                                            <div class="booking-info-label">Nhà hàng:</div>
                                            <div class="booking-info-value"><?php echo htmlspecialchars($restaurantInfo['TenNhaHang'] ?? 'Không có thông tin'); ?></div>
                                        </div>
                                        <div class="booking-info-item">
                                            <div class="booking-info-label">Ngày đặt bàn:</div>
                                            <div class="booking-info-value"><?php echo date('d/m/Y', strtotime($booking['ThoiGianDatBan'])); ?></div>
                                        </div>
                                        <div class="booking-info-item">
                                            <div class="booking-info-label">Giờ đặt bàn:</div>
                                            <div class="booking-info-value"><?php echo date('H:i', strtotime($booking['ThoiGianDatBan'])); ?></div>
                                        </div>
                                        <div class="booking-info-item">
                                            <div class="booking-info-label">Khu vực:</div>
                                            <div class="booking-info-value"><?php echo htmlspecialchars($areaInfo['Ten'] ?? 'Chưa xác định'); ?></div>
                                        </div>
                                        <div class="booking-info-item">
                                            <div class="booking-info-label">Bàn số:</div>
                                            <div class="booking-info-value"><?php echo $tableInfo['SoBang'] ?? 'Chưa xác định'; ?></div>
                                        </div>
                                        <div class="booking-info-item">
                                            <div class="booking-info-label">Số lượng khách:</div>
                                            <div class="booking-info-value"><?php echo $booking['SoLuongKhach']; ?> người</div>
                                        </div>
                                    </div>
                                    
                                    <div class="booking-actions">
                                        <div class="payment-status <?php echo $isPaid ? 'paid' : 'not-paid'; ?>">
                                            <?php echo $isPaid ? '<i class="fas fa-check-circle"></i> Đã thanh toán' : '<i class="fas fa-clock"></i> Chưa thanh toán'; ?>
                                        </div>
                                        
                                        <div class="action-buttons">
                                            <a href="/restaurant-website/views/booking/detail.php?id=<?php echo $booking['ID_ThongTinDatBan']; ?>" class="action-btn view-btn">
                                                <i class="fas fa-eye"></i> Xem chi tiết
                                            </a>
                                            
                                            <?php if ($booking['TrangThai'] == 1 && !$isPaid): ?>
                                                <a href="/restaurant-website/views/booking/payment.php?id=<?php echo $booking['ID_ThongTinDatBan']; ?>" class="action-btn payment-btn">
                                                    <i class="fas fa-credit-card"></i> Thanh toán
                                                </a>
                                            <?php endif; ?>
                                            
                                            <?php if ($booking['TrangThai'] == 0): ?>
                                                <button class="action-btn cancel-btn" onclick="cancelBooking(<?php echo $booking['ID_ThongTinDatBan']; ?>)">
                                                    <i class="fas fa-times"></i> Hủy đặt bàn
                                                </button>
                                            <?php endif; ?>
                                        </div>
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

    <!-- Footer -->
    <?php include '../../includes/footer.php'; ?>

    <script>
        function cancelBooking(bookingId) {
            if (confirm('Bạn có chắc chắn muốn hủy đơn đặt bàn này không?')) {
                $.ajax({
                    url: '/restaurant-website/public/api-handler.php',
                    type: 'POST',
                    data: {
                        action: 'cancel_booking',
                        id: bookingId
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            toastr.success('Hủy đơn đặt bàn thành công');
                            setTimeout(function() {
                                location.reload();
                            }, 1000);
                        } else {
                            toastr.error(response.message || 'Đã có lỗi xảy ra');
                        }
                    },
                    error: function() {
                        toastr.error('Không thể kết nối đến máy chủ');
                    }
                });
            }
        }
    </script>
</body>
</html>