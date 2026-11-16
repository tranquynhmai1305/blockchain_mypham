<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/Shopbanhang/conn.php';

// Kiểm tra quyền truy cập
if ($_SESSION['role'] !== 'admin') {
    echo "<script>
        alert('Tài khoản nhân viên không có quyền truy cập vào mục này!');
        window.location.href = '/Shopbanhang/admin/index.php';
    </script>";
    exit();
}

$admin_id = $_SESSION['admin_id'];
$current_pass = $_POST['current_pass'] ?? '';
$new_pass = $_POST['new_pass'] ?? '';

// 1. Lấy mật khẩu hiện tại trong DB để đối chiếu
$sql = "SELECT password FROM users WHERE id = '$admin_id'";
$result = mysqli_query($conn, $sql);
$user = mysqli_fetch_assoc($result);

// 2. Kiểm tra mật khẩu cũ có khớp không
// Lưu ý: Nếu bạn dùng MD5 thì: if (md5($current_pass) !== $user['password'])
if ($current_pass !== $user['password']) {
    echo json_encode(['status' => 'error', 'message' => 'Mật khẩu hiện tại không chính xác!']);
    exit;
}

// 3. Tiến hành cập nhật mật khẩu mới
// Lưu ý: Nếu bạn dùng MD5 thì: $new_pass = md5($new_pass);
$sql_update = "UPDATE users SET password = '$new_pass' WHERE id = '$admin_id'";

if (mysqli_query($conn, $sql_update)) {
    echo json_encode(['status' => 'success', 'message' => 'Cập nhật mật khẩu thành công!']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Lỗi hệ thống: Không thể cập nhật database.']);
}
?>