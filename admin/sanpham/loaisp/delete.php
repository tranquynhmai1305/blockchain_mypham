<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/Shopbanhang/func.php';
// Gọi thêm conn.php để đảm bảo biến $conn luôn khả dụng cho hàm save_log
require_once $_SERVER['DOCUMENT_ROOT'] . '/Shopbanhang/conn.php';

// Kiểm tra quyền (tránh việc khách vãng lai gõ URL để xóa)
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'nhanvien'])) {
    header("Location: ../../login.php");
    exit;
}

if (isset($_GET['id'])) {
    $id = trim($_GET['id']);

    // Thực hiện xóa loại sản phẩm
    if (deleteLoaiSP($id)) {
        
        // GHI NHẬT KÝ HOẠT ĐỘNG (AUDIT LOG - XÓA LOẠI SẢN PHẨM)
        if (function_exists('save_log')) {
            $userId = $_SESSION['admin_id'] ?? $_SESSION['user_id'] ?? 0;
            $description = "Đã xóa loại sản phẩm (Mã: " . $id . ")";
            
            // Truyền đủ 5 tham số: $conn, ID người xóa, Module, Hành động, Mô tả
            save_log($conn, $userId, 'LoaiSanPham', 'DELETE', $description);
        }

        header("Location: index.php");
        exit;
    } else {
        echo "<script>
            alert('Không thể xóa vì loại sản phẩm này đang có sản phẩm!');
            window.location.href = 'index.php';
        </script>";
    }
}
?>