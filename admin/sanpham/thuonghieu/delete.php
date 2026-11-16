<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/Shopbanhang/func.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/Shopbanhang/conn.php'; // Nhúng conn.php để có biến $conn

// Kiểm tra quyền truy cập (Admin hoặc Nhân viên)
$userRole = $_SESSION['role'] ?? '';
if (!isset($_SESSION['admin_id']) && !in_array($userRole, ['admin', 'nhanvien'])) {
    header("Location: ../../login.php");
    exit;
}
if (isset($_GET['id'])) {
    $id = trim($_GET['id']);

    if (deleteThuongHieu($id)) {

        // GHI NHẬT KÝ HOẠT ĐỘNG (AUDIT LOG - XÓA THƯƠNG HIỆU)
        if (function_exists('save_log')) {
            $admin_id = $_SESSION['admin_id'] ?? $_SESSION['user_id'] ?? 0;
            $description = "Đã xóa thương hiệu (Mã: " . $id . ")";
            save_log($conn, $admin_id, 'ThuongHieu', 'DELETE', $description);
        }

        header("Location: index.php");
        exit;
    } else {
        echo "<script>
            alert('Không thể xóa thương hiệu này! (Có thể đang chứa sản phẩm liên quan)');
            window.location.href = 'index.php';
        </script>";
    }
}