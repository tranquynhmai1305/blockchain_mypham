<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/Shopbanhang/conn.php';

// 1. Kiểm tra đăng nhập
if (!isset($_SESSION['role'])) {
    header("Location: /Shopbanhang/login.php");
    exit();
}

// 2. Khai báo thông tin cơ bản
$user_id    = $_SESSION['user_id'] ?? $_SESSION['admin_id'] ?? $_SESSION['id'] ?? 0;
$username   = $_SESSION['username'] ?? $_SESSION['admin_name'] ?? 'Nhân viên';
$type       = $_POST['type'] ?? '';
$today      = date('Y-m-d');
$now        = date('Y-m-d H:i:s');

// Lấy IP client và gán phân hệ (module) cho nhật ký hoạt động
$ip_address = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
$module     = 'ChamCong';

if ($user_id > 0 && !empty($type)) {

    // -----------------------------------------------------------------
    // TRƯỜNG HỢP 1: BẤM VÀO CA (CHECK-IN)
    // -----------------------------------------------------------------
    if ($type === 'checkin') {
        // Kiểm tra xem hôm nay đã check-in chưa
        $sql_check = "SELECT id FROM cham_cong WHERE user_id = ? AND ngay_lam = ?";
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->bind_param("is", $user_id, $today);
        $stmt_check->execute();
        
        if ($stmt_check->get_result()->num_rows === 0) {
            // A. Thêm bản ghi chấm công
            $sql_ins = "INSERT INTO cham_cong (user_id, ngay_lam, check_in) VALUES (?, ?, ?)";
            $stmt_ins = $conn->prepare($sql_ins);
            $stmt_ins->bind_param("iss", $user_id, $today, $now);
            $stmt_ins->execute();

            // B. Cập nhật trạng thái lịch phân ca thành "Đang làm"
            $sql_up = "UPDATE staff_schedules SET status = 'DangLam' WHERE admin_id = ? AND work_date = ?";
            $stmt_up = $conn->prepare($sql_up);
            $stmt_up->bind_param("is", $user_id, $today);
            $stmt_up->execute();

            // C. GHI NHẬT KÝ HOẠT ĐỘNG (Activity Logs)
            $action      = "CHECKIN";
            $description = "Nhân viên [{$username}] đã thực hiện điểm danh Vào ca làm việc lúc " . date('H:i:s d/m/Y');
            
            $sql_log  = "INSERT INTO activity_logs (admin_id, module, action, description, ip_address, created_at) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt_log = $conn->prepare($sql_log);
            $stmt_log->bind_param("isssss", $user_id, $module, $action, $description, $ip_address, $now);
            $stmt_log->execute();
        }
    } 
    
    // -----------------------------------------------------------------
    // TRƯỜNG HỢP 2: BẤM RA CA (CHECK-OUT)
    // -----------------------------------------------------------------
    elseif ($type === 'checkout') {
        // A. Cập nhật giờ ra ca
        $sql_out = "UPDATE cham_cong SET check_out = ? WHERE user_id = ? AND ngay_lam = ? AND check_out IS NULL";
        $stmt_out = $conn->prepare($sql_out);
        $stmt_out->bind_param("sis", $now, $user_id, $today);
        $stmt_out->execute();

        // B. Cập nhật trạng thái lịch phân ca thành "Hoàn thành"
        $sql_up = "UPDATE staff_schedules SET status = 'HoanThanh' WHERE admin_id = ? AND work_date = ?";
        $stmt_up = $conn->prepare($sql_up);
        $stmt_up->bind_param("is", $user_id, $today);
        $stmt_up->execute();

        // C. GHI NHẬT KÝ HOẠT ĐỘNG (Activity Logs)
        $action      = "CHECKOUT";
        $description = "Nhân viên [{$username}] đã thực hiện điểm danh Ra ca làm việc lúc " . date('H:i:s d/m/Y');
        
        $sql_log  = "INSERT INTO activity_logs (admin_id, module, action, description, ip_address, created_at) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt_log = $conn->prepare($sql_log);
        $stmt_log->bind_param("isssss", $user_id, $module, $action, $description, $ip_address, $now);
        $stmt_log->execute();
    }
}

// 3. Chuyển hướng trở lại trang chấm công
header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'chamcong.php'));
exit();
?>