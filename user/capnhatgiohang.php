<?php
session_start();
header('Content-Type: application/json');
require_once $_SERVER['DOCUMENT_ROOT'] . '/Shopbanhang/conn.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/Shopbanhang/func.php';

$id = $_POST['id'] ?? '';
$action = $_POST['action'] ?? '';

if (!isset($_SESSION['giohang'][$id])) {
    echo json_encode(['status' => 'error', 'message' => 'Sản phẩm không tồn tại trong giỏ hàng']);
    exit;
}

// Lấy thông tin sản phẩm từ DB để kiểm tra số lượng tồn kho mới nhất
$sp_db = getSanPhamById($id);
if (!$sp_db) {
    echo json_encode(['status' => 'error', 'message' => 'Sản phẩm không còn tồn tại trên hệ thống']);
    exit;
}

$ton_kho = (int)$sp_db['soluong'];
$soluong_hien_tai = $_SESSION['giohang'][$id]['soluong'];

switch ($action) {
    case 'plus':
        // KIỂM TRA: Nếu số lượng trong giỏ đã bằng hoặc vượt quá tồn kho thì không cho tăng
        if ($soluong_hien_tai >= $ton_kho) {
            echo json_encode([
                'status' => 'error', 
                'message' => "Rất tiếc, sản phẩm này chỉ còn $ton_kho sản phẩm."
            ]);
            exit;
        }
        $_SESSION['giohang'][$id]['soluong']++;
        break;

    case 'minus':
        $_SESSION['giohang'][$id]['soluong']--;
        if ($_SESSION['giohang'][$id]['soluong'] <= 0) {
            unset($_SESSION['giohang'][$id]);
        }
        break;

    case 'remove':
        unset($_SESSION['giohang'][$id]);
        break;
}

/* Tính lại tổng tiền */
$tongtien = 0;
if (isset($_SESSION['giohang'])) {
    foreach ($_SESSION['giohang'] as $sp) {
        $tongtien += $sp['gia'] * $sp['soluong'];
    }
}

echo json_encode([
    'status' => 'success',
    'cart' => $_SESSION['giohang'],
    'tongtien' => $tongtien
]);