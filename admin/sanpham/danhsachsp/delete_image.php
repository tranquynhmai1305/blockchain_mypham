<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/Shopbanhang/func.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/Shopbanhang/conn.php';

// 1. Kiểm tra đăng nhập (Cho phép cả Admin và Nhân viên)
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'nhanvien'])) {
    header("Location: ../../login.php");
    exit;
}

$masp = isset($_GET['masp']) ? trim($_GET['masp']) : '';

// 2. Kiểm tra tham số ID ảnh và Mã sản phẩm
if (isset($_GET['id']) && !empty($masp)) {
    $id = intval($_GET['id']);

    // 3. Lấy đường dẫn ảnh bằng Prepared Statement
    $stmt = mysqli_prepare($conn, "SELECT duongdan FROM hinhanh_sanpham WHERE id_hinhanh = ?");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if ($row) {
            $file_name = $row['duongdan'];

            // Xác định đường dẫn tuyệt đối tới file ảnh vật lý trên máy chủ
            if (strpos($file_name, 'images/') === false) {
                $full_path = $_SERVER['DOCUMENT_ROOT'] . '/Shopbanhang/images/' . $file_name;
            } else {
                $full_path = $_SERVER['DOCUMENT_ROOT'] . '/Shopbanhang/' . ltrim($file_name, '/');
            }

            // Xóa file ảnh trong thư mục storage nếu tồn tại
            if (file_exists($full_path) && is_file($full_path)) {
                unlink($full_path);
            }

            // Xóa dữ liệu hình ảnh trong CSDL
            $stmt_del = mysqli_prepare($conn, "DELETE FROM hinhanh_sanpham WHERE id_hinhanh = ?");
            if ($stmt_del) {
                mysqli_stmt_bind_param($stmt_del, "i", $id);
                if (mysqli_stmt_execute($stmt_del)) {

                    // ------------------------------------------------------------------
                    // 👉 GHI NHẬT KÝ HOẠT ĐỘNG (AUDIT LOG - XÓA ẢNH SẢN PHẨM)
                    $user_id = $_SESSION['user_id'] ?? $_SESSION['id'] ?? $_SESSION['admin_id'] ?? 0;
                    $description = "Đã xóa 1 hình ảnh (ID #$id) thuộc sản phẩm mã: $masp";
                    save_log($conn, $user_id, 'HinhAnhSanPham', 'DELETE', $description);
                    // ------------------------------------------------------------------

                }
                mysqli_stmt_close($stmt_del);
            }
        }
    }
}

// 4. Chuyển hướng quay lại trang chỉnh sửa sản phẩm
header("Location: edit.php?masp=" . urlencode($masp));
exit;