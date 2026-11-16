// nhận AJAX cập nhật ví
<?php
session_start();
include("conn.php"); // File kết nối CSDL của bạn (ví dụ: conn.php)

header('Content-Type: application/json');

if (!isset($_SESSION['makh'])) {
    echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập']);
    exit;
}

$makh = $_SESSION['makh'];
$wallet_address = isset($_POST['wallet_address']) ? trim($_POST['wallet_address']) : '';

if (!empty($wallet_address)) {
    $stmt = $conn->prepare("UPDATE khachhang SET wallet_address = ? WHERE makh = ?");
    $stmt->execute([$wallet_address, $makh]);
    
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Địa chỉ ví trống']);
}
?>