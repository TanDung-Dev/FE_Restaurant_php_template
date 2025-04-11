<?php
require_once 'session.php';

// Đảm bảo response là JSON
header('Content-Type: application/json');

// Debug mode (để gỡ lỗi)
$debug = true;

// Log function
function debugLog($message) {
    global $debug;
    if ($debug) {
        error_log($message);
    }
}

// Xử lý các action
$action = $_GET['action'] ?? '';
debugLog("Received action: " . $action);

try {
    switch ($action) {
        case 'cancel_booking':
            $bookingId = $_POST['id'] ?? 0;
            
            if (!$bookingId) {
                echo json_encode(['success' => false, 'message' => 'ID đặt bàn không hợp lệ']);
                exit;
            }
            
            $response = apiRequest('/dat-ban/' . $bookingId, 'DELETE');
            echo json_encode($response);
            break;

        case 'add_dish_to_order':
            $required_fields = ['ID_ThongTinDatBan', 'ID_MonAn', 'SoLuong'];
            $missing_fields = [];
            
            foreach ($required_fields as $field) {
                if (!isset($_POST[$field]) || empty($_POST[$field])) {
                    $missing_fields[] = $field;
                }
            }
            
            if (!empty($missing_fields)) {
                echo json_encode(['success' => false, 'message' => 'Thiếu thông tin bắt buộc']);
                exit;
            }
            
            $data = [
                'ID_ThongTinDatBan' => (int)$_POST['ID_ThongTinDatBan'],
                'ID_MonAn' => (int)$_POST['ID_MonAn'],
                'SoLuong' => (int)$_POST['SoLuong'],
                'GhiChu' => $_POST['GhiChu'] ?? ''
            ];
            
            $response = apiRequest('/chi-tiet-dat-mon/them-mon', 'POST', $data);
            echo json_encode($response);
            break;
        
        case 'update_dish_quantity':
            $required_fields = ['ID_ThongTinDatBan', 'ID_MonAn', 'SoLuong'];
            $missing_fields = [];
            
            foreach ($required_fields as $field) {
                if (!isset($_POST[$field]) || empty($_POST[$field])) {
                    $missing_fields[] = $field;
                }
            }
            
            if (!empty($missing_fields)) {
                echo json_encode(['success' => false, 'message' => 'Thiếu thông tin bắt buộc']);
                exit;
            }
            
            $data = [
                'ID_ThongTinDatBan' => (int)$_POST['ID_ThongTinDatBan'],
                'ID_MonAn' => (int)$_POST['ID_MonAn'],
                'SoLuong' => (int)$_POST['SoLuong'],
                'GhiChu' => $_POST['GhiChu'] ?? ''
            ];
            
            $response = apiRequest('/chi-tiet-dat-mon/cap-nhat', 'POST', $data);
            echo json_encode($response);
            break;
        
        case 'remove_dish_from_order':
            $required_fields = ['ID_ThongTinDatBan', 'ID_MonAn'];
            $missing_fields = [];
            
            foreach ($required_fields as $field) {
                if (!isset($_POST[$field]) || empty($_POST[$field])) {
                    $missing_fields[] = $field;
                }
            }
            
            if (!empty($missing_fields)) {
                echo json_encode(['success' => false, 'message' => 'Thiếu thông tin bắt buộc']);
                exit;
            }
            
            $data = [
                'ID_ThongTinDatBan' => (int)$_POST['ID_ThongTinDatBan'],
                'ID_MonAn' => (int)$_POST['ID_MonAn']
            ];
            
            $response = apiRequest('/chi-tiet-dat-mon/xoa-mon', 'POST', $data);
            echo json_encode($response);
            break;

        case 'mark_notification_read':
            $notificationId = $_POST['id'] ?? 0;
            
            if (!$notificationId) {
                echo json_encode(['success' => false, 'message' => 'ID thông báo không hợp lệ']);
                exit;
            }
            
            $response = apiRequest('/thong-bao/' . $notificationId . '/mark-read', 'POST');
            echo json_encode($response);
            break;

        case 'mark_all_notifications_read':
            $response = apiRequest('/thong-bao/mark-all-read', 'POST');
            echo json_encode($response);
            break;

        case 'remove_notification':
            $notificationId = $_POST['id'] ?? 0;
            
            if (!$notificationId) {
                echo json_encode(['success' => false, 'message' => 'ID thông báo không hợp lệ']);
                exit;
            }
            
            $response = apiRequest('/thong-bao/' . $notificationId, 'DELETE');
            echo json_encode($response);
            break;

        case 'get_areas':
            $restaurantId = $_GET['id_nhahang'] ?? 0;
            debugLog("Getting areas for restaurant ID: " . $restaurantId);
            
            if (!$restaurantId) {
                echo json_encode(['success' => false, 'message' => 'Thiếu ID nhà hàng']);
                exit;
            }
            
            $response = apiRequest('/khuvuc?id_nhahang=' . $restaurantId, 'GET');
            debugLog("API response: " . json_encode($response));
            echo json_encode($response);
            break;
        
        case 'get_tables':
            $areaId = $_GET['id_khuvuc'] ?? 0;
            debugLog("Getting tables for area ID: " . $areaId);
            
            if (!$areaId) {
                echo json_encode(['success' => false, 'message' => 'Thiếu ID khu vực']);
                exit;
            }
            
            $response = apiRequest('/ban?id_khuvuc=' . $areaId, 'GET');
            debugLog("API response: " . json_encode($response));
            echo json_encode($response);
            break;
        
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
} catch (Exception $e) {
    debugLog("Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>