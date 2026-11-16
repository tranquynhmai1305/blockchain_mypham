<?php
session_start();
$_SESSION = [];

session_destroy();

/* Xóa cookie session (PHPSESSID) */
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

/* Quay về trang người dùng */
header("Location: index.php");
exit;
