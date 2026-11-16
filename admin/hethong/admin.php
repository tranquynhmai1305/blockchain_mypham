<?php
session_start();

require_once $_SERVER['DOCUMENT_ROOT'] . '/Shopbanhang/conn.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/Shopbanhang/func.php';

// 1. KIỂM TRA QUYỀN TRUY CẬP (Cho phép Admin và Staff)
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'staff', 'nhanvien'])) {
    header("Location: /Shopbanhang/login.php");
    exit();
}

// 2. LẤY MÃ ID TÀI KHOẢN (ID của bảng 'users')
$user_id = $_SESSION['user_id'] ?? $_SESSION['admin_id'] ?? $_SESSION['id'] ?? 0;

if ($user_id <= 0) {
    session_destroy();
    header("Location: /Shopbanhang/login.php");
    exit();
}

// 3. TRUY VẤN DỮ LIỆU SỬ DỤNG LEFT JOIN (Kết nối bảng users và nhanvien)
$sql = "SELECT 
            u.id AS user_id,
            u.username,
            u.email AS user_email,
            u.hoten AS user_hoten,
            u.role AS user_role,
            nv.id AS nv_id,
            nv.hoten AS nv_hoten,
            nv.email AS nv_email,
            nv.sdt,
            nv.diachi,
            nv.chucvu,
            nv.ngayvaolam
        FROM users u
        LEFT JOIN nhanvien nv ON u.id = nv.id_users
        WHERE u.id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user_info = $result->fetch_assoc();

if (!$user_info) {
    session_destroy();
    header("Location: /Shopbanhang/login.php");
    exit();
}

// 4. GÁN DỮ LIỆU THÔNG TIN HIỂN THỊ
$display_name = !empty($user_info['nv_hoten']) ? $user_info['nv_hoten'] : ($user_info['user_hoten'] ?? $user_info['username']);
$email        = !empty($user_info['nv_email']) ? $user_info['nv_email'] : $user_info['user_email'];
$sdt          = $user_info['sdt'] ?? 'Chưa cập nhật';
$diachi       = $user_info['diachi'] ?? 'Chưa cập nhật';
$role_name    = $user_info['user_role'];
$chucvu       = !empty($user_info['chucvu']) ? $user_info['chucvu'] : ($role_name === 'admin' ? 'Quản trị viên hệ thống' : 'Nhân viên');
$ngayvaolam   = !empty($user_info['ngayvaolam']) ? date('d/m/Y', strtotime($user_info['ngayvaolam'])) : 'Chưa cập nhật';

// Định dạng Mã định danh đẹp mắt
$prefix    = ($role_name === 'admin') ? 'ADM' : 'NV';
$user_code = $prefix . '-' . str_pad($user_info['user_id'], 4, '0', STR_PAD_LEFT);

// Hàm lấy chữ cái đầu làm Avatar
function getFirstLetter($name) {
    $str = trim($name);
    if (empty($str)) return 'A';
    return mb_strtoupper(mb_substr($str, 0, 1, 'UTF-8'), 'UTF-8');
}
$first_letter = getFirstLetter($display_name);

// Hàm tạo màu nền Avatar dựa trên tên
function stringToColorCode($str) {
    $code = dechex(crc32($str));
    return '#' . substr($code, 0, 6);
}
$avatar_bg = stringToColorCode($display_name);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trang cá nhân - Triple Beauty</title>
    <!-- Google Fonts & FontAwesome -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --bg-main: #f8fafc;
            --sidebar-bg: #0f172a;
            --sidebar-hover: #1e293b;
            --text-muted: #94a3b8;
            --text-dark: #1e293b;
            --border-color: #e2e8f0;
            --primary-color: #6366f1;
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.05);
            --shadow-md: 0 4px 12px -2px rgba(0,0,0,0.08);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-main);
            color: var(--text-dark);
            line-height: 1.45;
        }

        .wrapper {
            display: flex;
            width: 100%;
            min-height: 100vh;
        }

        /* SIDEBAR */
        #sidebar {
            width: 260px;
            min-width: 260px;
            background: var(--sidebar-bg);
            color: #fff;
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            overflow-y: auto;
            z-index: 1000;
        }

        .sidebar-header {
            padding: 24px 20px;
            background: #090d16;
            border-bottom: 1px solid #1e293b;
        }

        .logo-box {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .logo-icon {
            font-size: 20px;
            color: #f43f5e;
        }

        .sidebar-header h3 {
            font-size: 18px;
            font-weight: 700;
            color: #ffffff;
        }

        .header-links {
            margin-top: 10px;
            font-size: 12px;
            color: var(--text-muted);
        }

        .header-links a {
            color: var(--text-muted);
            text-decoration: none;
        }

        .header-links a:hover {
            color: #fff;
        }

        .header-links .divider {
            margin: 0 6px;
            opacity: 0.4;
        }

        .menu-label {
            padding: 20px 20px 8px 20px;
            font-size: 11px;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        #sidebar ul.components {
            padding: 10px;
            list-style: none;
        }

        #sidebar ul li {
            margin-bottom: 4px;
        }

        #sidebar ul li a {
            padding: 12px 16px;
            display: flex;
            align-items: center;
            gap: 12px;
            color: #cbd5e1;
            text-decoration: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s;
        }

        #sidebar ul li a i {
            font-size: 16px;
            width: 20px;
            text-align: center;
            color: #94a3b8;
        }

        #sidebar ul li a:hover, #sidebar ul li.active > a {
            background: var(--sidebar-hover);
            color: #ffffff;
        }

        #sidebar ul li.active > a {
            background: var(--primary-color);
        }

        .has-submenu > a {
            justify-content: space-between;
        }

        .menu-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .arrow {
            font-size: 12px !important;
            transition: transform 0.3s;
        }

        .has-submenu.open .arrow {
            transform: rotate(180deg);
        }

        .submenu {
            list-style: none;
            padding: 4px 0 4px 12px;
            display: none;
        }

        .submenu li a {
            padding: 9px 16px;
            font-size: 13px;
            color: #94a3b8;
        }

        .has-submenu.open .submenu {
            display: block;
        }

        /* MAIN CONTENT */
        #content {
            flex: 1;
            margin-left: 260px;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .top-navbar {
            background: #ffffff;
            padding: 14px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 999;
            border-bottom: 1px solid var(--border-color);
            box-shadow: var(--shadow-sm);
        }

        .nav-left-info {
            display: flex;
            gap: 25px;
            font-size: 13px;
            color: #64748b;
        }

        .nav-left-info i {
            color: var(--primary-color);
            margin-right: 6px;
        }

        .nav-user-profile {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .user-avatar-sm {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: #e0e7ff;
            color: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 14px;
        }

        .user-details {
            display: flex;
            flex-direction: column;
        }

        .welcome-text {
            font-size: 11px;
            color: #94a3b8;
        }

        .user-name {
            font-size: 14px;
            font-weight: 700;
        }

        .btn-logout {
            width: 34px;
            height: 34px;
            border-radius: 8px;
            background: #fef2f2;
            color: #ef4444;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            transition: all 0.2s;
        }

        .btn-logout:hover {
            background: #ef4444;
            color: #ffffff;
        }

        .dashboard-container {
            padding: 24px 30px;
        }

        .dashboard-header {
            margin-bottom: 20px;
        }

        .dashboard-header h2 {
            font-size: 20px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .dashboard-header h2 i {
            color: var(--primary-color);
        }

        .dashboard-header .subtitle {
            font-size: 13px;
            color: #64748b;
            margin-top: 2px;
        }

        /* PROFILE STYLES - COMPACT & SLIM */
        .profile-wrapper-limit {
            max-width: 1050px; /* Giới hạn chiều rộng tổng thể để khung mảnh, gọn hơn */
        }

        .profile-container {
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
        }

        .profile-card {
            background: #ffffff;
            border-radius: 12px;
            padding: 20px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-color);
            flex: 1;
            min-width: 260px;
        }

        .profile-avatar-section {
            text-align: center;
            padding-bottom: 14px;
            border-bottom: 1px dashed var(--border-color);
            margin-bottom: 14px;
        }

        .avatar-letter {
            width: 75px;
            height: 75px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 30px;
            font-weight: bold;
            color: #ffffff;
            margin: 0 auto 10px auto;
            box-shadow: 0 3px 8px rgba(0,0,0,0.12);
            text-transform: uppercase;
            user-select: none;
        }

        .role-badge {
            display: inline-block;
            padding: 3px 10px;
            font-size: 11px;
            font-weight: 700;
            border-radius: 20px;
            text-transform: uppercase;
            margin-top: 4px;
        }
        .badge-admin { background: #fee2e2; color: #dc2626; }
        .badge-staff { background: #e0f2fe; color: #0284c7; }

        /* Thu hẹp khoảng cách giữa các thông tin */
        .info-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #f1f5f9;
            font-size: 13.5px;
        }
        .info-item:last-child {
            border-bottom: none;
        }
        .info-label {
            color: #64748b;
            font-weight: 500;
        }
        .info-val {
            color: var(--text-dark);
            font-weight: 600;
        }

        /* Thu hẹp khoảng cách giữa các ô form */
        .form-group {
            margin-bottom: 12px;
        }
        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 4px;
            color: var(--text-dark);
            font-size: 12.5px;
        }
        .form-group input {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            box-sizing: border-box;
            background-color: #f8fafc;
            color: var(--text-dark);
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 13.5px;
            transition: all 0.3s;
        }
        .form-group input:not([readonly]) {
            background-color: #fff;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.1);
        }

        .btn-action-group {
            display: flex;
            gap: 10px;
            margin-top: 18px;
        }
        .btn-edit {
            background: var(--primary-color);
            color: #fff;
            border: none;
            padding: 9px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            font-size: 13.5px;
            font-family: 'Plus Jakarta Sans', sans-serif;
            transition: background 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .btn-edit:hover { background: #4f46e5; }

        .btn-submit {
            background: #10b981;
            color: #fff;
            border: none;
            padding: 9px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            font-size: 13.5px;
            font-family: 'Plus Jakarta Sans', sans-serif;
            transition: background 0.3s;
            display: none;
            align-items: center;
            gap: 6px;
        }
        .btn-submit:hover { background: #059669; }

        .btn-cancel {
            background: #ef4444;
            color: #fff;
            border: none;
            padding: 9px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            font-size: 13.5px;
            font-family: 'Plus Jakarta Sans', sans-serif;
            transition: background 0.3s;
            display: none;
            align-items: center;
            gap: 6px;
        }
        .btn-cancel:hover { background: #dc2626; }
    </style>
</head>
<body>
<div class="wrapper">
    <!-- SIDEBAR -->
    <nav id="sidebar">
    <div class="sidebar-header">
        <div class="logo-box">
            <i class="fa-solid fa-sparkles logo-icon"></i>
            <h3>Triple Beauty</h3>
        </div>
        <div class="header-links">
            <a href="/Shopbanhang/index.php" target="_blank"><i class="fa-solid fa-globe"></i>Website</a>
            <span class="divider">|</span>
            <a href="javascript:void(0);"><i class="fa-solid fa-broom"></i>Xin chào bạn</a>
        </div>
    </div>

    <ul class="list-unstyled components">
        <p class="menu-label">CHỨC NĂNG HỆ THỐNG</p>

        <li>
            <a href="/Shopbanhang/admin/index.php"><i class="fa fa-home"></i> <span>Trang chủ</span></a>
        </li>
         
        <li class="has-submenu">
            <a href="javascript:void(0);" class="menu-toggle">
                <div class="menu-left">
                    <i class="fa fa-user-shield"></i>
                    <span>Quản lý Nhân sự</span>
                </div>
                <i class="fa fa-angle-down arrow"></i>
            </a>
            <ul class="submenu">
                <li><a href="/Shopbanhang/admin/nhanvien/employees.php">Danh sách nhân viên</a></li>
                <li><a href="/Shopbanhang/admin/nhanvien/staff_schedules.php">Chọn phân ca nhân viên</a></li>
                <li><a href="/Shopbanhang/admin/nhanvien/shifts_config.php">Phân ca làm việc</a></li>
                <li><a href="/Shopbanhang/admin/nhanvien/chamcong.php">Nhân viên chấm công</a></li>
            </ul>
        </li>

        <li class="has-submenu">
            <a href="javascript:void(0);" class="menu-toggle">
                <div class="menu-left">
                    <i class="fa fa-box"></i>
                    <span>Quản lý sản phẩm</span>
                </div>
                <i class="fa fa-angle-down arrow"></i>
            </a>
            <ul class="submenu">
                <li><a href="/Shopbanhang/admin/sanpham/loaisp/index.php">Loại sản phẩm</a></li>
                <li><a href="/Shopbanhang/admin/sanpham/thuonghieu/index.php">Thương hiệu</a></li>
                <li><a href="/Shopbanhang/admin/sanpham/danhsachsp/index.php">Sản phẩm</a></li>
            </ul>
        </li>

        <li class="has-submenu">
            <a href="javascript:void(0);" class="menu-toggle">
                <div class="menu-left">
                    <i class="fa fa-shopping-cart"></i>
                    <span>Quản lý đơn hàng</span>
                </div>
                <i class="fa fa-angle-down arrow"></i>
            </a>
            <ul class="submenu">
                <li><a href="/Shopbanhang/admin/donhang/index.php">Danh sách đơn hàng</a></li>
                <li><a href="/Shopbanhang/admin/donhang/khachhang_thongke.php">Thống kê giá trị khách hàng</a></li>
            </ul>
        </li>

        <li class="has-submenu">
            <a href="javascript:void(0);" class="menu-toggle">
                <div class="menu-left">
                    <i class="fa-solid fa-users"></i>
                    <span>Quản lý khách hàng</span>
                </div>
                <i class="fa fa-angle-down arrow"></i>
            </a>
            <ul class="submenu">
                <li><a href="/Shopbanhang/admin/khachhang/index.php">Danh sách khách hàng</a></li>
                <li><a href="/Shopbanhang/admin/khachhang/trangthaitk.php">Trạng thái tài khoản</a></li>
            </ul>
        </li>

         <li class="has-submenu">
    <a href="javascript:void(0);" class="menu-toggle">
        <div class="menu-left">
            <i class="fa-solid fa-comments"></i>
            <span>Quản lý nội dung</span>
        </div>
        <i class="fa fa-angle-down arrow"></i>
    </a>
    <ul class="submenu">
        <li><a href="/Shopbanhang/admin/noidung/quanly_danhgia.php">Bài viết đánh giá</a></li>
        <li><a href="/Shopbanhang/admin/noidung/thongke_danhgia.php">Thống kê đánh giá</a></li>
        <li><a href="/Shopbanhang/admin/noidung/banner.php" style="color: #ffffff;">Banner</a></li>
        <li><a href="/Shopbanhang/admin/noidung/vouchers.php" style="color: #ffffff;">Vouchers</a></li>
    </ul>
</li>

        <li class="has-submenu">
            <a href="javascript:void(0);" class="menu-toggle">
                <div class="menu-left">
                    <i class="fa-solid fa-gears"></i>
                    <span>Quản lý hệ thống</span>
                </div>
                <i class="fa fa-angle-down arrow"></i>
            </a>
            <ul class="submenu">
                <li><a href="/Shopbanhang/admin/hethong/admin.php">Thông tin cá nhân</a></li>
                <li><a href="/Shopbanhang/admin/hethong/phanquyen.php">Phân quyền tài khoản</a></li>
                <li><a href="/Shopbanhang/admin/hethong/activity_logs.php">Nhật ký hoạt động nhân viên</a></li>
            </ul>
        </li>
    </ul>
</nav>

    <!-- CONTENT -->
    <div id="content">
        <!-- TOP NAVBAR -->
        <header class="top-navbar">
            <div class="nav-left-info">
                <span><i class="fa-solid fa-headset"></i> Hỗ trợ 24/7: <strong>1900 9477</strong></span>
                <span><i class="fa-solid fa-envelope"></i> Email: <strong>web@tbvietnam.com</strong></span>
            </div>
            <div class="nav-user-profile">
                <div class="user-avatar-sm" style="background-color: <?= $avatar_bg ?>; color: #fff;">
                    <?= $first_letter ?>
                </div>
                <div class="user-details">
                    <span class="welcome-text">Xin chào,</span>
                    <span class="user-name"><?= htmlspecialchars($display_name) ?></span>
                </div>
                <a href="../logout.php" class="btn-logout" title="Đăng xuất">
                    <i class="fa-solid fa-arrow-right-from-bracket"></i>
                </a>
            </div>
        </header>

        <!-- MAIN BODY CONTENT -->
        <main class="dashboard-container">
            <div class="dashboard-header">
                <div>
                    <h2><i class="fa-solid fa-user-circle"></i> Trang cá nhân</h2>
                    <p class="subtitle">Quản lý thông tin tài khoản và cập nhật thông tin cá nhân trên hệ thống</p>
                </div>
            </div>
            
            <!-- KHUNG CARD GỌN GÀNG, MẢNH HƠN -->
            <div class="profile-wrapper-limit">
                <div class="profile-container">
                    
                    <!-- THẺ BÊN TRÁI: THÔNG TIN ĐẠI DIỆN -->
                    <div class="profile-card" style="box-shadow: none; border: 1px solid var(--border-color); flex: 0.9;">
                        <div class="profile-avatar-section">
                            <div class="avatar-letter" style="background-color: <?= $avatar_bg ?>;">
                                <?= $first_letter ?>
                            </div>
                            <h3 style="margin: 0 0 3px 0; font-size: 16px; color: var(--text-dark);"><?= htmlspecialchars($display_name) ?></h3>
                            <span class="role-badge <?= ($role_name === 'admin') ? 'badge-admin' : 'badge-staff' ?>">
                                <?= htmlspecialchars($role_name) ?>
                            </span>
                        </div>

                        <div class="info-item">
                            <span class="info-label">Mã định danh:</span>
                            <span class="info-val" style="color: var(--primary-color);"><?= $user_code ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Tên đăng nhập:</span>
                            <span class="info-val"><?= htmlspecialchars($user_info['username']) ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Chức vụ:</span>
                            <span class="info-val"><?= htmlspecialchars($chucvu) ?></span>
                        </div>

                        <?php if ($role_name !== 'admin'): ?>
                        <div class="info-item">
                            <span class="info-label">Ngày vào làm:</span>
                            <span class="info-val"><?= htmlspecialchars($ngayvaolam) ?></span>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- THẺ BÊN PHẢI: FORM CẬP NHẬT -->
                    <div class="profile-card" style="flex: 1.4; box-shadow: none; border: 1px solid var(--border-color);">
                        <form id="profileForm" action="update_profile.php" method="POST">
                            <h3 style="margin-top: 0; font-size: 15px; color: var(--text-dark);">Cập nhật thông tin chi tiết</h3>
                            <hr style="border: 0; border-top: 1px solid var(--border-color); margin: 10px 0 14px 0;">
                            
                            <div class="form-group">
                                <label>Họ và tên:</label>
                                <input type="text" id="hoten" name="hoten" value="<?= htmlspecialchars($display_name) ?>" readonly required>
                            </div>

                            <div class="form-group">
                                <label>Email:</label>
                                <input type="email" id="email" name="email" value="<?= htmlspecialchars($email) ?>" readonly required>
                            </div>

                            <div class="form-group">
                                <label>Số điện thoại:</label>
                                <input type="text" id="sdt" name="sdt" value="<?= htmlspecialchars($sdt) ?>" readonly>
                            </div>

                            <div class="form-group">
                                <label>Địa chỉ:</label>
                                <input type="text" id="diachi" name="diachi" value="<?= htmlspecialchars($diachi) ?>" readonly>
                            </div>

                            <div class="form-group">
                                <label>Mật khẩu mới (Bỏ trống nếu không đổi):</label>
                                <input type="password" id="new_password" name="new_password" placeholder="••••••••" readonly>
                            </div>

                            <div class="btn-action-group">
                                <button type="button" id="btnEdit" class="btn-edit" onclick="enableEdit()">
                                    <i class="fa fa-pen"></i> Chỉnh sửa
                                </button>
                                
                                <button type="submit" id="btnSave" class="btn-submit">
                                    <i class="fa fa-save"></i> Lưu thay đổi
                                </button>

                                <button type="button" id="btnCancel" class="btn-cancel" onclick="disableEdit()">
                                    <i class="fa fa-times"></i> Hủy
                                </button>
                            </div>
                        </form>
                    </div>

                </div>
            </div>
        </main>
    </div>
</div>

<script>
    // Xử lý đóng mở Submenu Sidebar
    document.querySelectorAll('.menu-toggle').forEach(menu => {
        menu.addEventListener('click', () => {
            const parent = menu.parentElement;
            parent.classList.toggle('open');
            const submenu = parent.querySelector('.submenu');
            if(submenu) {
                submenu.style.display = parent.classList.contains('open') ? 'block' : 'none';
            }
        });
    });

    // Bật chế độ chỉnh sửa thông tin
    function enableEdit() {
        const inputs = document.querySelectorAll('#profileForm input');
        inputs.forEach(input => {
            input.removeAttribute('readonly');
        });

        document.getElementById('btnEdit').style.display = 'none';
        document.getElementById('btnSave').style.display = 'inline-flex';
        document.getElementById('btnCancel').style.display = 'inline-flex';
    }

    // Hủy chế độ chỉnh sửa thông tin
    function disableEdit() {
        const inputs = document.querySelectorAll('#profileForm input');
        inputs.forEach(input => {
            input.setAttribute('readonly', true);
        });

        document.getElementById('btnEdit').style.display = 'inline-flex';
        document.getElementById('btnSave').style.display = 'none';
        document.getElementById('btnCancel').style.display = 'none';
    }
</script>
</body>
</html>