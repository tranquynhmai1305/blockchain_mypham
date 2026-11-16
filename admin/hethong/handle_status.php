<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/Shopbanhang/func.php';

// Bảo mật: Chỉ admin mới được thực thi file này
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die("Access Denied");
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$action = $_GET['action'] ?? '';

// Không cho phép tự sửa chính mình để tránh mất quyền quản trị đột ngột
if ($id == $_SESSION['admin_id']) {
    header("Location: index.php?msg=error_self");
    exit();
}

if ($action == 'toggle_status') {
    $set = $_GET['set'];
    $sql = "UPDATE users SET trangthai = ? WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $set, $id);
    mysqli_stmt_execute($stmt);
} 

elseif ($action == 'change_role') {
    $current = $_GET['current'];
    $new_role = ($current == 'admin') ? 'user' : 'admin';
    $sql = "UPDATE users SET role = ? WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "si", $new_role, $id);
    mysqli_stmt_execute($stmt);
}

header("Location: " . $_SERVER['HTTP_REFERER']); // Quay lại trang cũ
exit();