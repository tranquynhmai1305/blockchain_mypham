<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/Shopbanhang/func.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/Shopbanhang/conn.php';

// 1. Kiểm tra quyền truy cập (Admin hoặc Nhân viên)
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'nhanvien'])) {
    header("Location: ../../login.php");
    exit;
}

// 2. Kiểm tra tham số mã sản phẩm truyền vào
if (!isset($_GET['masp']) || empty(trim($_GET['masp']))) {
    header("Location: index.php");
    exit;
}

$masp = trim($_GET['masp']);

// 3. Lấy tên sản phẩm trước khi xóa để phục vụ cho việc ghi Audit Log
$stmt = mysqli_prepare($conn, "SELECT tensp FROM sanpham WHERE masp = ?");
$tensp = $masp; // Mặc định hiển thị mã nếu không tìm thấy tên sản phẩm

if ($stmt) {
    mysqli_stmt_bind_param($stmt, "s", $masp);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if ($row = mysqli_fetch_assoc($result)) {
        $tensp = $row['tensp'];
    }
    mysqli_stmt_close($stmt);
}

// 4. Thực hiện xóa sản phẩm
$deleted = deleteSanPham($masp);

// 5. Ghi nhật ký hoạt động nếu xóa thành công (hoặc hàm deleteSanPham trả về true)
if ($deleted !== false) {

    // ------------------------------------------------------------------
    // 👉 GHI NHẬT KÝ HOẠT ĐỘNG (AUDIT LOG - XÓA SẢN PHẨM)
    $user_id = $_SESSION['user_id'] ?? $_SESSION['id'] ?? $_SESSION['admin_id'] ?? 0;
    $description = "Đã xóa sản phẩm: " . $tensp . " (Mã: " . $masp . ")";
    save_log($conn, $user_id, 'SanPham', 'DELETE', $description);
    // ------------------------------------------------------------------

}

// 6. Chuyển hướng quay lại danh sách sản phẩm
header("Location: index.php");
exit;