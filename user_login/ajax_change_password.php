<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/Shopbanhang/conn.php';

// Kiểm tra lỗi kết nối DB
if (!$conn) {
    echo json_encode(['status' => 'error', 'message' => 'Lỗi kết nối DB: ' . mysqli_connect_error()]);
    exit;
}

$userid = $_SESSION['userid'];
$current_pass = $_POST['current_pass'];
$new_pass = $_POST['new_pass'];

// 1. Kiểm tra mật khẩu cũ trong bảng 'users'
// Lưu ý: Cột khóa chính của bạn là 'id'
$sql = "SELECT `password` FROM `users` WHERE `id` = '$userid'";
$res = mysqli_query($conn, $sql);
$user = mysqli_fetch_assoc($res);

if (!$user) {
    echo json_encode(['status' => 'error', 'message' => 'Không tìm thấy người dùng']);
    exit;
}

if ($current_pass !== $user['password']) {
    echo json_encode(['status' => 'error', 'message' => 'Mật khẩu cũ không đúng']);
    exit;
}

// 2. Cập nhật mật khẩu mới vào bảng 'users'
$update_sql = "UPDATE `users` SET `password` = '$new_pass' WHERE `id` = '$userid'";

if (mysqli_query($conn, $update_sql)) {
    echo json_encode(['status' => 'success', 'message' => 'Mật khẩu đã được đổi thành công trong DB!']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Lỗi SQL: ' . mysqli_error($conn)]);
}
?>