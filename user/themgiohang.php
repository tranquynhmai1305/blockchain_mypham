<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'].'/Shopbanhang/func.php';

// Chưa đăng nhập → chuyển login
if (!isset($_SESSION['userid'])) {
    $_SESSION['redirect_after_login'] = $_SERVER['HTTP_REFERER'] ?? 'index.php';
    header("Location: login.php");
    exit;
}

$id = $_GET['id'] ?? 0;
if ($id == 0) {
    header("Location: index.php");
    exit;
}

// Khởi tạo giỏ hàng
if (!isset($_SESSION['giohang'])) {
    $_SESSION['giohang'] = [];
}

// Thêm sản phẩm
$soluong = max(1, (int)($_GET['soluong'] ?? 1));

if (isset($_SESSION['giohang'][$id])) {

    $_SESSION['giohang'][$id]['soluong'] += $soluong;

} else {
    $sp = getSanPhamById($id);
    $hinhanh = getHinhAnhBySanPham($id);

    $hinh = 'no-image.png';

    if (!empty($hinhanh)) {
        // Lấy đường dẫn gốc từ DB
        $path_tu_db = $hinhanh[0]['duongdan']; 
        // Dùng basename để chỉ lấy tên file (ví dụ: 'srmhdlb.jpg') 
        // hoặc ltrim để bỏ bớt dấu / nếu có
        $hinh = basename($path_tu_db); 
    }

    $_SESSION['giohang'][$id] = [
        'ten' => $sp['tensp'],
        'gia' => $sp['giakhuyenmai'] > 0 ? $sp['giakhuyenmai'] : $sp['gia'],
        'soluong' => $soluong,
        'hinh' => $hinh // Lúc này $hinh chỉ còn là "1772641117_srmhdlb.jpg"
    ];
}
header("Location: index.php");
exit;