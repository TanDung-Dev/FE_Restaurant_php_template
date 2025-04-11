<?php
require_once 'session.php';
checkUserLoggedIn();
$user = getCurrentUser();

// Lấy danh sách thông báo của người dùng
$response = apiRequest('/thong-bao', 'GET');
$notifications = $response['data'] ?? [];
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <!-- ========== Meta Tags ========== -->
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Thông báo - Hệ thống đặt bàn nhà hàng">
    <!-- ======== Page title ============ -->
    <title>Thông báo - Hệ thống đặt bàn nhà hàng</title>
    <!-- Stylesheets -->
    <link rel="shortcut icon" href="/restaurant-website/public/assets/img/logo/favicon.svg ">
    <link rel="stylesheet" href="/restaurant-website/public/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="/restaurant-website/public/assets/css/font-awesome.css">
    <link rel="stylesheet" href="/restaurant-website/public/assets/css/animate.css">
    <link rel="stylesheet" href="/restaurant-website/public/assets/css/magnific-popup.css">
    <link rel="stylesheet" href="/restaurant-website/public/assets/css/meanmenu.css">
    <link rel="stylesheet" href="/restaurant-website/public/assets/css/swiper-bundle.min.css">
    <link rel="stylesheet" href="/restaurant-website/public/assets/css/nice-select.css">
    <link rel="stylesheet" href="/restaurant-website/public/assets/css/main.css">
    <link rel="stylesheet" href="/restaurant-website/public/style.css">
    <style>
        /* CSS cho trang thông báo */
        .notification-section {
            padding: 80px 0;
            background-color: #f8f9fa;
        }
        .notification-title {
            margin-bottom: 40px;
        }
        .notification-list {
            margin-top: 30px;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 15px rgba(0,0,0,0.08);
            background-color: #fff;
        }
        .notification-item {
            position: relative;
            padding: 22px 25px;
            border-bottom: 1px solid #eee;
            transition: all 0.3s ease;
        }
        .notification-item:last-child {
            border-bottom: none;
        }
        .notification-item:hover {
            background-color: #f9f9f9;
            transform: translateY(-2px);
        }
        .notification-item.unread {
            background-color: rgba(255, 91, 0, 0.05);
            border-left: 3px solid #ff5b00;
        }
        .notification-item.unread:hover {
            background-color: rgba(255, 91, 0, 0.08);
        }
        .notification-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }
        .notification-title {
            font-weight: 600;
            font-size: 18px;
            color: #333;
            display: flex;
            align-items: center;
        }
        .notification-title:before {
            content: '';
            display: inline-block;
            width: 8px;
            height: 8px;
            background-color: #ff5b00;
            border-radius: 50%;
            margin-right: 10px;
            opacity: 0;
        }
        .unread .notification-title:before {
            opacity: 1;
            animation: pulse 1.5s infinite;
        }
        .notification-date {
            color: #777;
            font-size: 14px;
            font-style: italic;
        }
        .notification-content {
            margin-bottom: 15px;
            color: #444;
            line-height: 1.6;
        }
        .notification-actions {
            text-align: right;
        }
        .notification-actions button {
            margin-left: 10px;
            padding: 7px 14px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .mark-read-btn {
            background-color: #f0f0f0;
            color: #333;
        }
        .mark-read-btn:hover {
            background-color: #e0e0e0;
        }
        .remove-btn {
            background-color: #ff6b6b;
            color: white;
        }
        .remove-btn:hover {
            background-color: #ff5252;
            transform: translateY(-2px);
        }
        .notification-empty {
            text-align: center;
            padding: 60px 20px;
            color: #777;
            background-color: #f9f9f9;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .notification-empty i {
            color: #ddd;
            margin-bottom: 15px;
        }
        .mark-all-read {
            text-align: right;
            margin-bottom: 20px;
        }
        #mark-all-read-btn {
            padding: 8px 16px;
            background-color: #f8f9fa;
            color: #495057;
            border: 1px solid #ced4da;
            border-radius: 4px;
            transition: all 0.3s ease;
        }
        #mark-all-read-btn:hover {
            background-color: #e9ecef;
            transform: translateY(-2px);
        }
        /* Animation */
        @keyframes fadeOut {
            from { opacity: 1; }
            to { opacity: 0; }
        }
        @keyframes pulse {
            0% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.2); opacity: 0.7; }
            100% { transform: scale(1); opacity: 1; }
        }
        .fadeOut {
            animation: fadeOut 0.3s forwards;
        }
        /* Responsive */
        @media (max-width: 768px) {
            .notification-header {
                flex-direction: column;
                align-items: flex-start;
            }
            .notification-date {
                margin-top: 5px;
            }
            .notification-actions {
                display: flex;
                margin-top: 10px;
            }
            .notification-actions button {
                flex: 1;
                margin: 0 5px;
            }
            .notification-actions button:first-child {
                margin-left: 0;
            }
            .notification-actions button:last-child {
                margin-right: 0;
            }
        }
        /* Hiệu ứng loading */
        .loading-spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 2px solid rgba(0, 0, 0, 0.1);
            border-radius: 50%;
            border-top-color: #333;
            animation: spin 0.8s linear infinite;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        /* Badge cho số lượng thông báo chưa đọc */
        .badge-notification {
            display: inline-block;
            background-color: #ff5b00;
            color: white;
            border-radius: 50px;
            padding: 5px 10px;
            font-size: 14px;
            font-weight: 600;
            margin-left: 10px;
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

    <!-- Breadcrumb -->
    <div class="breadcrumb-area bg-img breabg-img">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="breadcrumb-inner text-center">
                        <div class="section-title wow fadeInUp" data-wow-delay="0.3s">
                            <h2>Thông báo của bạn</h2>
                        </div>
                        <ul class="page-list wow fadeInUp" data-wow-delay="0.5s">                            
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Notification Section -->
    <section class="notification-section">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <?php if (!empty($notifications)): ?>
                        <div class="mark-all-read">
                            <button id="mark-all-read-btn">
                                <i class="fas fa-check-double"></i> Đánh dấu tất cả là đã đọc
                            </button>
                        </div>
                        
                        <div class="notification-list">
                            <?php foreach ($notifications as $notification): ?>
                                <div class="notification-item <?php echo $notification['DaDoc'] ? '' : 'unread'; ?>" data-id="<?php echo $notification['ID_ThongBao']; ?>">
                                    <div class="notification-header">
                                        <div class="notification-title"><?php echo htmlspecialchars($notification['Ten']); ?></div>
                                        <div class="notification-date">
                                            <?php echo date('d/m/Y H:i', strtotime($notification['NgayTao'])); ?>
                                        </div>
                                    </div>
                                    <div class="notification-content">
                                        <?php echo htmlspecialchars($notification['NoiDung']); ?>
                                    </div>
                                    <div class="notification-actions">
                                        <?php if (!$notification['DaDoc']): ?>
                                            <button class="mark-read-btn" onclick="markAsRead(<?php echo $notification['ID_ThongBao']; ?>)">
                                                <i class="fas fa-check"></i> Đánh dấu đã đọc
                                            </button>
                                        <?php endif; ?>
                                        <button class="remove-btn" onclick="removeNotification(<?php echo $notification['ID_ThongBao']; ?>)">
                                            <i class="fas fa-trash"></i> Xóa
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="notification-empty">
                            <i class="fas fa-bell-slash fa-3x mb-3"></i>
                            <h4>Bạn không có thông báo nào</h4>
                            <p>Thông báo sẽ xuất hiện khi có cập nhật mới về đơn đặt bàn hoặc các tin tức khác.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <?php include __DIR__ . '/../../app/includes/footer.php'; ?>

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
    <script src="/restaurant-website/public/assets/js/jquery.meanmenu.min.js"></script>
    <script src="/restaurant-website/public/assets/js/jquery.nice-select.min.js"></script>
    <script src="/restaurant-website/public/assets/js/jquery.magnific-popup.min.js"></script>
    <script src="/restaurant-website/public/assets/js/main.js"></script>

    <script>
    // JavaScript cho trang thông báo
    $(document).ready(function() {
        // Xử lý preloader
        $(window).on('load', function() {
            $('.preloader').fadeOut(1000);
        });

        // Thêm hiệu ứng khi di chuột qua các thông báo
        $('.notification-item').hover(
            function() {
                $(this).addClass('shadow-sm');
            },
            function() {
                $(this).removeClass('shadow-sm');
            }
        );
        
        // Đếm số thông báo chưa đọc và hiển thị badge
        const unreadCount = $('.notification-item.unread').length;
        if (unreadCount > 0) {
            $('.section-title h2').append(`<span class="badge-notification">${unreadCount}</span>`);
        }

        // Cải thiện hàm đánh dấu đã đọc
        window.markAsRead = function(notificationId) {
            const notificationElement = $(`.notification-item[data-id="${notificationId}"]`);
            
            $.ajax({
                url: '/restaurant-website/public/api-handler.php',
                type: 'POST',
                data: {
                    action: 'mark_notification_read',
                    id: notificationId
                },
                dataType: 'json',
                beforeSend: function() {
                    notificationElement.find('.mark-read-btn').html('<span class="loading-spinner"></span> Đang xử lý...');
                },
                success: function(response) {
                    if (response.success) {
                        // Thêm hiệu ứng chuyển đổi trạng thái
                        notificationElement.addClass('transition-bg');
                        setTimeout(function() {
                            notificationElement.removeClass('unread transition-bg');
                            notificationElement.find('.mark-read-btn').fadeOut(300, function() {
                                $(this).remove();
                            });
                            
                            // Cập nhật số lượng thông báo chưa đọc
                            updateUnreadBadge();
                            
                            // Hiệu ứng nổi bật sau khi đánh dấu đã đọc
                            notificationElement.addClass('bg-light');
                            setTimeout(function() {
                                notificationElement.removeClass('bg-light');
                            }, 700);
                        }, 300);
                        
                        // Thông báo thành công
                        alert('Đã đánh dấu thông báo là đã đọc');
                    } else {
                        notificationElement.find('.mark-read-btn').html('<i class="fas fa-check"></i> Đánh dấu đã đọc');
                        alert('Đã có lỗi xảy ra: ' + (response.message || 'Không xác định'));
                    }
                },
                error: function() {
                    notificationElement.find('.mark-read-btn').html('<i class="fas fa-check"></i> Đánh dấu đã đọc');
                    alert('Không thể kết nối đến máy chủ');
                }
            });
        }
        
        // Cải thiện hàm xóa thông báo
        window.removeNotification = function(notificationId) {
            if (confirm('Bạn có chắc chắn muốn xóa thông báo này?')) {
                const notificationElement = $(`.notification-item[data-id="${notificationId}"]`);
                
                $.ajax({
                    url: '/restaurant-website/public/api-handler.php',
                    type: 'POST',
                    data: {
                        action: 'remove_notification',
                        id: notificationId
                    },
                    dataType: 'json',
                    beforeSend: function() {
                        // Thêm hiệu ứng khi đang xóa
                        notificationElement.css('opacity', '0.5');
                    },
                    success: function(response) {
                        if (response.success) {
                            // Cải thiện animation khi xóa
                            notificationElement.addClass('fadeOut');
                            setTimeout(function() {
                                notificationElement.slideUp(300, function() {
                                    $(this).remove();
                                    
                                    // Kiểm tra nếu không còn thông báo nào
                                    if ($('.notification-item').length === 0) {
                                        $('.notification-list').fadeOut(300, function() {
                                            $(this).html(`
                                                <div class="notification-empty">
                                                    <i class="fas fa-bell-slash fa-3x mb-3"></i>
                                                    <h4>Bạn không có thông báo nào</h4>
                                                    <p>Thông báo sẽ xuất hiện khi có cập nhật mới về đơn đặt bàn hoặc các tin tức khác.</p>
                                                </div>
                                            `).fadeIn(300);
                                        });
                                        $('.mark-all-read').fadeOut(300);
                                    }
                                    
                                    // Cập nhật số lượng thông báo chưa đọc
                                    updateUnreadBadge();
                                });
                            }, 300);
                            
                            // Thông báo thành công
                            alert('Đã xóa thông báo thành công');
                        } else {
                            notificationElement.css('opacity', '1');
                            alert('Đã có lỗi xảy ra: ' + (response.message || 'Không xác định'));
                        }
                    },
                    error: function() {
                        notificationElement.css('opacity', '1');
                        alert('Không thể kết nối đến máy chủ');
                    }
                });
            }
        }
        
        // Cải thiện hàm đánh dấu tất cả là đã đọc
        $('#mark-all-read-btn').click(function() {
            if ($('.notification-item.unread').length === 0) {
                alert('Tất cả thông báo đã được đọc');
                return;
            }
            
            const $btn = $(this);
            const originalText = $btn.html();
            
            $.ajax({
                url: '/restaurant-website/public/api-handler.php',
                type: 'POST',
                data: {
                    action: 'mark_all_notifications_read'
                },
                dataType: 'json',
                beforeSend: function() {
                    $btn.html('<span class="loading-spinner"></span> Đang xử lý...').prop('disabled', true);
                },
                success: function(response) {
                    if (response.success) {
                        // Highlight các thông báo chưa đọc trước khi chuyển trạng thái
                        $('.notification-item.unread').addClass('bg-light');
                        
                        // Cập nhật UI sau một khoảng thời gian nhỏ để tạo hiệu ứng
                        setTimeout(function() {
                            $('.notification-item').removeClass('unread bg-light');
                            $('.mark-read-btn').fadeOut(300, function() {
                                $(this).remove();
                            });
                            
                            // Cập nhật số lượng thông báo chưa đọc
                            updateUnreadBadge();
                        }, 300);
                        
                        // Thông báo thành công
                        alert('Đã đánh dấu tất cả thông báo là đã đọc');
                    } else {
                        alert('Đã có lỗi xảy ra: ' + (response.message || 'Không xác định'));
                    }
                    
                    $btn.html(originalText).prop('disabled', false);
                },
                error: function() {
                    alert('Không thể kết nối đến máy chủ');
                    $btn.html(originalText).prop('disabled', false);
                }
            });
        });
        
        // Hàm cập nhật số lượng thông báo chưa đọc
        function updateUnreadBadge() {
            const unreadCount = $('.notification-item.unread').length;
            const badgeElement = $('.section-title h2 .badge-notification');
            
            if (unreadCount > 0) {
                if (badgeElement.length > 0) {
                    badgeElement.text(unreadCount);
                } else {
                    $('.section-title h2').append(`<span class="badge-notification">${unreadCount}</span>`);
                }
            } else {
                badgeElement.fadeOut(300, function() {
                    $(this).remove();
                });
            }
        }
    });
    </script>
</body>
</html>