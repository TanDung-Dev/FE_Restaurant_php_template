<?php
require_once 'session.php';

// Người dùng phải đăng nhập để đặt bàn
checkUserLoggedIn();
$user = getCurrentUser();

// Lấy danh sách nhà hàng
$response = apiRequest('/nhahang', 'GET');
$restaurants = $response['data'] ?? [];

// Lấy danh sách khu vực (nếu đã chọn nhà hàng)
$areas = [];
if (isset($_GET['nha_hang_id']) && !empty($_GET['nha_hang_id'])) {
    $areaResponse = apiRequest('/khuvuc?id_nhahang=' . $_GET['nha_hang_id'], 'GET');
    $areas = $areaResponse['data'] ?? [];
}

// Lấy danh sách bàn (nếu đã chọn khu vực)
$tables = [];
if (isset($_GET['khu_vuc_id']) && !empty($_GET['khu_vuc_id'])) {
    $tableResponse = apiRequest('/ban?id_khuvuc=' . $_GET['khu_vuc_id'], 'GET');
    $tables = $tableResponse['data'] ?? [];
}

// Xử lý đặt bàn
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Xử lý form submit
    $required_fields = ['restaurant_id', 'area_id', 'table_id', 'booking_date', 'booking_time', 'guests'];
    $missing_fields = [];
    
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || empty($_POST[$field])) {
            $missing_fields[] = $field;
        }
    }
    
    if (!empty($missing_fields)) {
        $errors[] = 'Vui lòng điền đầy đủ thông tin bắt buộc';
    } else {
        // Chuyển đổi ngày và giờ thành định dạng datetime
        $booking_datetime = date('Y-m-d H:i:s', strtotime($_POST['booking_date'] . ' ' . $_POST['booking_time']));
        
        // Dữ liệu gửi đến API
        $bookingData = [
            'ThoiGianDatBan' => $booking_datetime,
            'SoLuongKhach' => (int)$_POST['guests'],
            'YeuCau' => $_POST['special_requests'] ?? '',
            'ID_Ban' => (int)$_POST['table_id']
        ];
        
        // Gọi API đặt bàn
        $bookingResponse = apiRequest('/dat-ban', 'POST', $bookingData);
        
        if (isset($bookingResponse['success']) && $bookingResponse['success']) {
            $success = true;
            $bookingId = $bookingResponse['data']['ID_ThongTinDatBan'] ?? 0;
            
            // Gửi thông báo cho admin
            $notificationData = [
                'ID_USER' => 1, // ID của admin, lấy từ config hoặc database
                'Ten' => 'Đơn đặt bàn mới',
                'MoTa' => 'Có đơn đặt bàn mới cần xác nhận',
                'NoiDung' => 'Khách hàng ' . $user['HoVaTen'] . ' đã đặt bàn ngày ' . date('d/m/Y', strtotime($_POST['booking_date'])) . ' vào lúc ' . $_POST['booking_time'] . '. Vui lòng kiểm tra và xác nhận.',
                'TheLoai' => 'new_booking'
            ];
            
            $notifyResponse = apiRequest('/thong-bao/create', 'POST', $notificationData);
            
            // Chuyển hướng đến trang xác nhận đặt bàn
            header('Location: booking-confirmation.php?id=' . $bookingId);
            exit;
        } else {
            $errors[] = $bookingResponse['message'] ?? 'Có lỗi xảy ra khi đặt bàn';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <!-- Meta Tags -->
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="author" content="modinatheme">
    <meta name="description" content="Đặt bàn - Nhà hàng">
    <!-- Page title -->
    <title>Đặt bàn - Nhà hàng</title>
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
    <link rel="stylesheet" href="/restaurant-website/public/style.css">
    <!-- Custom styles for booking page -->
    <style>
        .booking-form-section {
            padding: 100px 0;
            background-color: #f8f9fa;
        }
        .booking-form-wrapper {
            background-color: #fff;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
        }
        .booking-title {
            margin-bottom: 40px;
        }
        .booking-title h2 {
            font-size: 36px;
            margin-bottom: 15px;
        }
        .booking-title p {
            color: #666;
            font-size: 16px;
        }
        .form-group {
            margin-bottom: 25px;
        }
        .form-label {
            font-weight: 600;
            margin-bottom: 10px;
            display: block;
        }
        .form-control {
            height: 50px;
            border-radius: 8px;
        }
        textarea.form-control {
            height: 120px;
        }
        .booking-summary {
            background-color: #f9f9f9;
            padding: 30px;
            border-radius: 15px;
            margin-top: 30px;
        }
        .summary-title {
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .summary-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
        }
        .summary-label {
            font-weight: 600;
        }
        .booking-banner {
            background: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)), url('/restaurant-website/public/assets/img/banner/booking-banner.jpg');
            background-size: cover;
            background-position: center;
            height: 400px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            margin-bottom: 50px;
        }
        .booking-banner-content {
            text-align: center;
        }
        .booking-banner h1 {
            font-size: 48px;
            margin-bottom: 15px;
        }
        .booking-banner p {
            font-size: 18px;
            max-width: 600px;
            margin: 0 auto;
        }
        .required-field::after {
            content: "*";
            color: red;
            margin-left: 5px;
        }
        .loading-message {
            display: none;
            text-align: center;
            padding: 10px;
            background-color: #f8f9fa;
            margin-bottom: 20px;
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
    <?php include __DIR__ . '/../../app/includes/header.php'; ?>

    <!-- Booking Banner -->
    <section class="booking-banner">
        <div class="booking-banner-content">
            <h1>Đặt bàn</h1>
            <p>Đặt bàn tại nhà hàng chúng tôi để có trải nghiệm ẩm thực tuyệt vời</p>
        </div>
    </section>

    <!-- Booking Form Section -->
    <section class="booking-form-section">
        <div class="container">
            <div class="booking-form-wrapper">
                <div class="booking-title text-center">
                    <h2>Đặt bàn ngay hôm nay</h2>
                    <p>Điền thông tin đặt bàn để đảm bảo có chỗ cho bạn và những người thân yêu</p>
                </div>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger mb-4">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <!-- Loading message -->
                <div id="loading-message" class="loading-message">
                    <i class="fa fa-spinner fa-spin"></i> Đang tải dữ liệu...
                </div>

                <form method="POST" action="" id="booking-form">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="restaurant_id" class="form-label required-field">Chọn nhà hàng</label>
                            <select class="form-select form-control" id="restaurant_id" name="restaurant_id" required>
                                <option value="">-- Chọn nhà hàng --</option>
                                <?php foreach ($restaurants as $restaurant): ?>
                                    <option value="<?php echo $restaurant['ID_NhaHang']; ?>" <?php echo (isset($_GET['nha_hang_id']) && $_GET['nha_hang_id'] == $restaurant['ID_NhaHang']) ? 'selected' : ''; ?>>
                                        <?php echo $restaurant['TenNhaHang']; ?> - <?php echo $restaurant['DiaChi']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="area_id" class="form-label required-field">Chọn khu vực</label>
                            <select class="form-select form-control" id="area_id" name="area_id" required <?php echo empty($areas) ? 'disabled' : ''; ?>>
                                <option value="">-- Chọn khu vực --</option>
                                <?php foreach ($areas as $area): ?>
                                    <option value="<?php echo $area['ID_KhuVuc']; ?>" <?php echo (isset($_GET['khu_vuc_id']) && $_GET['khu_vuc_id'] == $area['ID_KhuVuc']) ? 'selected' : ''; ?>>
                                        <?php echo $area['Ten']; ?> - Tầng <?php echo $area['Tang']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="table_id" class="form-label required-field">Chọn bàn</label>
                            <select class="form-select form-control" id="table_id" name="table_id" required <?php echo empty($tables) ? 'disabled' : ''; ?>>
                                <option value="">-- Chọn bàn --</option>
                                <?php foreach ($tables as $table): ?>
                                    <option value="<?php echo $table['ID_Ban']; ?>" data-capacity="<?php echo $table['DungTich']; ?>">
                                        Bàn số <?php echo $table['SoBang']; ?> - Sức chứa: <?php echo $table['DungTich']; ?> người
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="guests" class="form-label required-field">Số lượng khách</label>
                            <input type="number" class="form-control" id="guests" name="guests" min="1" max="20" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="booking_date" class="form-label required-field">Ngày đặt bàn</label>
                            <input type="date" class="form-control" id="booking_date" name="booking_date" min="<?php echo date('Y-m-d'); ?>" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="booking_time" class="form-label required-field">Giờ đặt bàn</label>
                            <input type="time" class="form-control" id="booking_time" name="booking_time" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="special_requests" class="form-label">Yêu cầu đặc biệt</label>
                        <textarea class="form-control" id="special_requests" name="special_requests" rows="4" placeholder="Nhập yêu cầu đặc biệt của bạn (nếu có)"></textarea>
                    </div>

                    <div class="booking-summary">
                        <h4 class="summary-title">Thông tin đặt bàn</h4>
                        <div id="booking-summary-content">
                            <p class="text-center text-muted">Vui lòng chọn nhà hàng, khu vực, bàn và điền đầy đủ thông tin để xem tóm tắt đặt bàn</p>
                        </div>
                    </div>

                    <div class="text-center mt-4">
                        <button type="submit" class="theme-btn">
                            <span class="button-content-wrapper d-flex align-items-center">
                                <span class="button-icon"><i class="fas fa-calendar-check"></i></span>
                                <span class="button-text">Xác nhận đặt bàn</span>
                            </span>
                        </button>
                    </div>
                </form>
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
    <script src="/restaurant-website/public/assets/js/bootstrap.bundle.min.js"></script>
    <script src="/restaurant-website/public/assets/js/swiper-bundle.min.js"></script>
    <script src="/restaurant-website/public/assets/js/jquery.nice-select.min.js"></script>
    <script src="/restaurant-website/public/assets/js/jquery.magnific-popup.min.js"></script>
    <script src="/restaurant-website/public/assets/js/main.js"></script>

    <!-- Custom booking script -->
    <script>
    $(document).ready(function() {
        console.log("Document ready");
        
        // Vô hiệu hóa nice-select tránh xung đột
        if ($.fn.niceSelect) {
            $('.form-select').niceSelect('destroy');
        }
        
        // Hiện thông báo đang tải
        function showLoading() {
            $('#loading-message').show();
        }
        
        // Ẩn thông báo đang tải
        function hideLoading() {
            $('#loading-message').hide();
        }
        
        // Hàm hỗ trợ debug
        function logObject(obj, name) {
            console.log(name + ":", obj);
            try {
                console.log(name + " (stringified):", JSON.stringify(obj));
            } catch (e) {
                console.log("Cannot stringify", name);
            }
        }
        
        // Handle restaurant selection change
        $('#restaurant_id').on('change', function() {
            const restaurantId = $(this).val();
            console.log("Restaurant changed to:", restaurantId);
            
            // Reset select boxes
            $('#area_id').html('<option value="">-- Chọn khu vực --</option>').prop('disabled', true);
            $('#table_id').html('<option value="">-- Chọn bàn --</option>').prop('disabled', true);
            
            if (restaurantId) {
                showLoading();
                
                // Gọi API để lấy danh sách khu vực
                $.ajax({
                    url: '/restaurant-website/public/api-handler.php',
                    type: 'GET',
                    data: {
                        action: 'get_areas',
                        id_nhahang: restaurantId
                    },
                    dataType: 'json',
                    beforeSend: function() {
                        console.log("Sending request to get areas for restaurant ID:", restaurantId);
                    },
                    success: function(response) {
                        hideLoading();
                        logObject(response, "Areas response");
                        
                        if (response.success && response.data && response.data.length > 0) {
                            let options = '<option value="">-- Chọn khu vực --</option>';
                            
                            response.data.forEach(function(area) {
                                options += `<option value="${area.ID_KhuVuc}">${area.Ten} - Tầng ${area.Tang}</option>`;
                            });
                            
                            $('#area_id').html(options).prop('disabled', false);
                            console.log("Updated area dropdown with", response.data.length, "options");
                            
                            // Nếu có nice-select, cập nhật nó
                            if ($.fn.niceSelect) {
                                try {
                                    $('#area_id').niceSelect('update');
                                } catch (e) {
                                    console.log("Nice Select update failed:", e);
                                }
                            }
                        } else {
                            console.log("No areas found or error in response");
                            alert('Không tìm thấy khu vực cho nhà hàng này');
                        }
                    },
                    error: function(xhr, status, error) {
                        hideLoading();
                        console.error("AJAX error:", status, error);
                        console.log("Response text:", xhr.responseText);
                        alert('Có lỗi xảy ra khi lấy danh sách khu vực: ' + error);
                    }
                });
            }
            
            updateBookingSummary();
        });

        // Xử lý khi thay đổi lựa chọn khu vực
        $('#area_id').on('change', function() {
            const areaId = $(this).val();
            console.log("Area changed to:", areaId);
            
            // Reset hộp chọn bàn
            $('#table_id').html('<option value="">-- Chọn bàn --</option>').prop('disabled', true);
            
            if (areaId) {
                showLoading();
                
                // Gọi API để lấy danh sách bàn
                $.ajax({
                    url: '/restaurant-website/public/api-handler.php',
                    type: 'GET',
                    data: {
                        action: 'get_tables',
                        id_khuvuc: areaId
                    },
                    dataType: 'json',
                    beforeSend: function() {
                        console.log("Sending request to get tables for area ID:", areaId);
                    },
                    success: function(response) {
                        hideLoading();
                        logObject(response, "Tables response");
                        
                        if (response.success && response.data && response.data.length > 0) {
                            let options = '<option value="">-- Chọn bàn --</option>';
                            
                            response.data.forEach(function(table) {
                                options += `<option value="${table.ID_Ban}" data-capacity="${table.DungTich}">Bàn số ${table.SoBang} - Sức chứa: ${table.DungTich} người</option>`;
                            });
                            
                            $('#table_id').html(options).prop('disabled', false);
                            console.log("Updated table dropdown with", response.data.length, "options");
                            
                            // Nếu có nice-select, cập nhật nó
                            if ($.fn.niceSelect) {
                                try {
                                    $('#table_id').niceSelect('update');
                                } catch (e) {
                                    console.log("Nice Select update failed:", e);
                                }
                            }
                        } else {
                            console.log("No tables found or error in response");
                            alert('Không tìm thấy bàn cho khu vực này');
                        }
                    },
                    error: function(xhr, status, error) {
                        hideLoading();
                        console.error("AJAX error:", status, error);
                        console.log("Response text:", xhr.responseText);
                        alert('Có lỗi xảy ra khi lấy danh sách bàn: ' + error);
                    }
                });
            }
            
            updateBookingSummary();
        });
        
        // Cập nhật booking summary
        function updateBookingSummary() {
            const restaurant = $('#restaurant_id option:selected').text();
            const area = $('#area_id option:selected').text();
            const table = $('#table_id option:selected').text();
            const guests = $('#guests').val();
            const date = $('#booking_date').val();
            const time = $('#booking_time').val();
            
            let summaryHTML = '';
            
            if (restaurant && restaurant !== '-- Chọn nhà hàng --') {
                summaryHTML += `<div class="summary-item">
                    <span class="summary-label">Nhà hàng:</span>
                    <span class="summary-value">${restaurant}</span>
                </div>`;
            }
            
            if (area && area !== '-- Chọn khu vực --') {
                summaryHTML += `<div class="summary-item">
                    <span class="summary-label">Khu vực:</span>
                    <span class="summary-value">${area}</span>
                </div>`;
            }
            
            if (table && table !== '-- Chọn bàn --') {
                summaryHTML += `<div class="summary-item">
                    <span class="summary-label">Bàn:</span>
                    <span class="summary-value">${table}</span>
                </div>`;
            }
            
            if (guests) {
                summaryHTML += `<div class="summary-item">
                    <span class="summary-label">Số khách:</span>
                    <span class="summary-value">${guests}</span>
                </div>`;
            }
            
            if (date) {
                const formattedDate = new Date(date).toLocaleDateString('vi-VN');
                summaryHTML += `<div class="summary-item">
                    <span class="summary-label">Ngày:</span>
                    <span class="summary-value">${formattedDate}</span>
                </div>`;
            }
            
            if (time) {
                summaryHTML += `<div class="summary-item">
                    <span class="summary-label">Giờ:</span>
                    <span class="summary-value">${time}</span>
                </div>`;
            }
            
            if (summaryHTML) {
                $('#booking-summary-content').html(summaryHTML);
            } else {
                $('#booking-summary-content').html('<p class="text-center text-muted">Vui lòng chọn nhà hàng, khu vực, bàn và điền đầy đủ thông tin để xem tóm tắt đặt bàn</p>');
            }
        }
        
        // Thêm sự kiện lắng nghe cho tất cả các trường form
        $('#booking-form input, #booking-form select').on('change', updateBookingSummary);
        
        // Xác thực số lượng khách dựa trên sức chứa của bàn
        $('#table_id').on('change', function() {
            const selectedOption = $(this).find('option:selected');
            if (selectedOption.length) {
                const capacity = selectedOption.data('capacity');
                if (capacity) {
                    $('#guests').attr('max', capacity);
                    if ($('#guests').val() > capacity) {
                        $('#guests').val(capacity);
                    }
                }
            }
            updateBookingSummary();
        });
        
        // Validate form before submission
        $('#booking-form').on('submit', function(e) {
            // Check if all required fields are filled
            const restaurant = $('#restaurant_id').val();
            const area = $('#area_id').val();
            const table = $('#table_id').val();
            const guests = $('#guests').val();
            const date = $('#booking_date').val();
            const time = $('#booking_time').val();
            
            if (!restaurant || !area || !table || !guests || !date || !time) {
                e.preventDefault();
                alert('Vui lòng điền đầy đủ thông tin bắt buộc!');
                return false;
            }
            
            // Check if booking date is in the future
            const bookingDate = new Date(date);
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            
            if (bookingDate < today) {
                e.preventDefault();
                alert('Ngày đặt bàn phải là ngày hiện tại hoặc trong tương lai!');
                return false;
            }
            
            return true;
        });

        // Đảm bảo dropdown hoạt động đúng ngay khi trang tải xong
        $(window).on('load', function() {
            // Nếu jQuery nice select đang được sử dụng, vô hiệu hóa nó để sử dụng select mặc định
            if ($.fn.niceSelect) {
                try {
                    $('.form-select').niceSelect('destroy');
                    console.log("Nice Select destroyed");
                } catch (e) {
                    console.log("Failed to destroy Nice Select, might not be initialized:", e);
                }
            }
            
            // Khởi tạo ban đầu
            setTimeout(function() {
                // Nếu đã có restaurant_id được chọn từ URL, kích hoạt sự kiện change để lấy khu vực
                if ($('#restaurant_id').val()) {
                    $('#restaurant_id').trigger('change');
                    console.log("Triggered restaurant change on load");
                    
                    // Nếu đã có area_id được chọn từ URL, kích hoạt sự kiện change để lấy bàn sau 1 giây
                    setTimeout(function() {
                        if ($('#area_id').val()) {
                            $('#area_id').trigger('change');
                            console.log("Triggered area change on load");
                        }
                    }, 1000);
                }
            }, 500);
        });
    });
    </script>
</body>
</html>