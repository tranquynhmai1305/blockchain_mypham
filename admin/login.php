<?php
session_start();
require_once '../conn.php';

$error = '';

if (isset($_POST['login'])) {
    $auth_key = trim($_POST['auth_key']); 
    $password = $_POST['password'];

    // Truy vấn tài khoản theo Username hoặc Email
    $sql = "SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $auth_key, $auth_key); 
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // Kiểm tra mật khẩu
        if ($password === $user['password'] || password_verify($password, $user['password'])) {
            
            $role = strtolower(trim($user['role']));

            // Lưu thông tin vào Session
            $_SESSION['user_id']    = $user['id'];
            $_SESSION['admin_id']   = $user['id'];
            $_SESSION['admin_name'] = $user['hoten'];
            $_SESSION['role']       = ($role === '1' || $role === 'admin') ? 'admin' : (($role === '2' || $role === 'staff' || $role === 'nhanvien') ? 'nhanvien' : $role);

            // PHÂN QUYỀN ĐIỀU HƯỚNG
            // Cả Admin và Nhân viên đều vào được Trang chủ quản trị để xem thống kê
            if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'nhanvien') {
                header("Location: /Shopbanhang/admin/index.php");
                exit;
            } else {
                // Dành cho Khách hàng / Khác
                header("Location: /Shopbanhang/index.php");
                exit;
            }

        } else {
            $error = "Mật khẩu không chính xác!";
        }
    } else {
        $error = "Tài khoản hoặc Email không tồn tại!";
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập hệ thống</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin_login.css">
</head>
<body>

    <div class="login-wrapper">
        <div class="login-box">
            <h2>Login</h2>

            <!-- Hiển thị thông báo lỗi nếu có -->
            <?php if (!empty($error)): ?>
                <div style="background-color: #fee2e2; color: #dc2626; padding: 10px; border-radius: 6px; margin-bottom: 15px; text-align: center; font-size: 0.875rem;">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="post">
                <div class="input-group">
                    <input type="text" name="auth_key" placeholder="Username or Email" value="<?= htmlspecialchars($_POST['auth_key'] ?? '') ?>" required>
                    <i class="fas fa-user"></i>
                </div>

                <div class="input-group">
                    <input type="password" name="password" id="password" placeholder="Password" required>
                    <i class="fas fa-lock"></i>
                    <i class="fa-regular fa-eye" id="togglePassword"></i>
                </div>

                <div class="remember-forgot">
                    <label><input type="checkbox" name="remember"> Remember me</label>
                    <a href="#">Forgot password?</a>
                </div>

                <button type="submit" name="login" class="btn-login">Login</button>

                <div class="register-link">
                    <p>Don't have an account? <a href="#">Register</a></p>
                </div>
            </form>
        </div>
    </div>

    <script src="../assets/js/admin_login.js"></script>
</body>
</html>