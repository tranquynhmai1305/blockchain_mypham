<?php
session_start();

require_once $_SERVER['DOCUMENT_ROOT'] . '/Shopbanhang/conn.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/Shopbanhang/func.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo "<script>
        alert('Tài khoản nhân viên không có quyền truy cập vào mục này!');
        window.location.href = '/Shopbanhang/admin/index.php';
    </script>";
    exit();
}

$admin_id = $_SESSION['admin_id'] ?? $_SESSION['user_id'] ?? 0; 
$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
$accounts = getAllAccountsAdmin($keyword); 
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý quyền & Trạng thái - Triple Beauty</title>
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

        .user-details {
            display: flex;
            flex-direction: column;
            text-align: right;
        }

        .welcome-text {
            font-size: 11px;
            color: #94a3b8;
        }

        .user-name {
            font-size: 13.5px;
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

        .main-body {
            padding: 24px 30px;
        }

        .breadcrumb {
            font-size: 12.5px;
            color: #64748b;
            margin-bottom: 14px;
            font-weight: 500;
        }

        /* CARD STYLING - COMPACT & SLIM */
        .card {
            background: #ffffff;
            border-radius: 12px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-color);
            overflow: hidden;
        }

        .card-header {
            padding: 16px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--border-color);
            background: #fff;
            flex-wrap: wrap;
            gap: 10px;
        }

        .header-title {
            font-size: 15px;
            font-weight: 700;
            color: var(--text-dark);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .header-title i {
            color: var(--primary-color);
        }

        .filter-form {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .filter-form input {
            padding: 8px 12px;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 13px;
            width: 220px;
            outline: none;
            transition: all 0.2s;
        }

        .filter-form input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.1);
        }

        .btn-filter-submit {
            background: var(--primary-color);
            color: #fff;
            border: none;
            padding: 8px 14px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 13px;
            cursor: pointer;
            transition: background 0.2s;
        }

        .btn-filter-submit:hover {
            background: #4f46e5;
        }

        .btn-cancel {
            background: #64748b;
            color: #fff;
            border-radius: 6px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: background 0.2s;
        }
        .btn-cancel:hover { background: #475569; }

        .card-body {
            padding: 0;
        }

        /* TABLE STYLING - COMPACT */
        .admin-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13.5px;
            text-align: left;
        }

        .admin-table th {
            background: #f8fafc;
            color: #475569;
            font-weight: 600;
            padding: 11px 16px;
            border-bottom: 1px solid var(--border-color);
            font-size: 12.5px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .admin-table td {
            padding: 11px 16px;
            border-bottom: 1px solid #f1f5f9;
            color: var(--text-dark);
            vertical-align: middle;
        }

        .admin-table tbody tr:hover {
            background-color: #f8fafc;
        }

        .admin-table small {
            color: var(--text-muted);
            font-size: 12px;
        }

        /* BADGES & STATUS */
        .role-badge {
            padding: 3px 9px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .role-admin { background: #fee2e2; color: #dc2626; }
        .role-staff { background: #fef3c7; color: #d97706; }
        .role-user { background: #e0f2fe; color: #0284c7; }

        .status {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 12.5px;
            font-weight: 600;
        }
        .status.active { color: #10b981; }
        .status.inactive { color: #ef4444; }

        /* ACTIONS */
        .product-card-actions {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .product-card-actions a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 30px;
            height: 30px;
            border-radius: 6px;
            text-decoration: none;
            color: #fff;
            transition: all 0.2s;
            font-size: 12px;
        }

        .product-card-actions a:hover {
            opacity: 0.85;
            transform: translateY(-1px);
        }
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

            <li class="has-submenu open">
                <a href="javascript:void(0);" class="menu-toggle">
                    <div class="menu-left">
                        <i class="fa-solid fa-users"></i>
                        <span>Quản lý khách hàng</span>
                    </div>
                    <i class="fa fa-angle-down arrow"></i>
                </a>
                <ul class="submenu" style="display: block;">
                    <li><a href="/Shopbanhang/admin/khachhang/index.php" style="color: #ffffff;">Danh sách khách hàng</a></li>
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
                    <li><a href="/Shopbanhang/admin/noidung/banner.php">Banner</a></li>
                    <li><a href="/Shopbanhang/admin/noidung/vouchers.php">Vouchers</a></li>
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
                <span><i class="fa fa-phone"></i> Hỗ trợ 24/7: <strong>1900 9477</strong></span>
                <span><i class="fa-solid fa-envelope"></i> Email: <strong>web@tbvietnam.com</strong></span>
            </div>
            <div class="nav-user-profile">
                <div class="user-details">
                    <span class="welcome-text">Xin chào,</span>
                    <span class="user-name"><?= htmlspecialchars($_SESSION['admin_name'] ?? $_SESSION['user_name'] ?? 'Quản trị viên') ?></span>
                </div>
                <a href="../logout.php" class="btn-logout" title="Đăng xuất">
                    <i class="fa fa-sign-out-alt"></i>
                </a>
            </div>
        </header>

        <div class="main-body">
            <div class="breadcrumb">Hệ thống &gt; Quản lý tài khoản toàn cục</div>
            
            <div class="card">
                <div class="card-header">
                    <span class="header-title"><i class="fa fa-users-cog"></i> TẤT CẢ TÀI KHOẢN</span>
                    <form method="GET" class="filter-form">
                        <input type="text" name="keyword" placeholder="Tìm tên, email..." value="<?= htmlspecialchars($keyword) ?>">
                        <button type="submit" class="btn-filter-submit">Tìm</button>
                        <?php if($keyword): ?>
                            <a href="phanquyen.php" class="btn-cancel" style="padding: 8px 14px; font-size: 13px;">Reload</a>
                        <?php endif; ?>
                    </form>
                </div>
                
                <div class="card-body">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th style="width: 70px;">ID</th>
                                <th>Họ tên</th>
                                <th>Tài khoản</th>
                                <th>Quyền hạn (Role)</th>
                                <th>Trạng thái</th>
                                <th style="width: 100px; text-align: center;">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $accounts = getAllAccountsAdmin($keyword);
                            while ($u = mysqli_fetch_assoc($accounts)): 
                                $is_me = ($u['id'] == $admin_id); 
                                
                                // Xử lý hiển thị tên dự phòng nếu cột hoten bị trống hoặc NULL
                                $ten_hien_thi = trim($u['hoten'] ?? '');
                                $co_ten = true;
                                if ($ten_hien_thi === '' || $ten_hien_thi === '0') {
                                    $co_ten = false;
                                    $ten_hien_thi = !empty($u['username']) ? $u['username'] : (explode('@', $u['email'])[0] ?? 'Chưa cập nhật');
                                }
                            ?>
                            <tr style="<?= $is_me ? 'background-color: #fefce8;' : '' ?>">
                                <td><strong>#<?= $u['id'] ?></strong></td>
                                <td>
                                    <span style="<?= !$co_ten ? 'color: #94a3b8; font-style: italic;' : '' ?>">
                                        <?= htmlspecialchars($ten_hien_thi) ?>
                                    </span> 
                                    <?= $is_me ? '<span style="color:#d97706; font-weight:600;">(Bạn)</span>' : '' ?>
                                </td>
                                <td>
                                    <div style="font-weight: 500;"><?= htmlspecialchars($u['username']) ?></div>
                                    <small><?= htmlspecialchars($u['email']) ?></small>
                                </td>
                                <td>
                                    <span class="role-badge <?= $u['role'] == 'admin' ? 'role-admin' : ($u['role'] == 'staff' ? 'role-staff' : 'role-user') ?>">
                                        <?= strtoupper($u['role']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($u['trangthai'] == 1): ?>
                                        <span class="status active"><i class="fa fa-check-circle"></i> Hoạt động</span>
                                    <?php else: ?>
                                        <span class="status inactive"><i class="fa fa-ban"></i> Bị khóa</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: center;">
                                    <div class="product-card-actions" style="justify-content: center;">
                                        <?php if (!$is_me): ?>
                                            <a href="handle_status.php?action=change_role&id=<?= $u['id'] ?>&current=<?= $u['role'] ?>" 
                                               class="btn-view" title="Thay đổi quyền" style="background:#8e44ad">
                                                <i class="fa fa-user-shield"></i>
                                            </a>

                                            <a href="handle_status.php?action=toggle_status&id=<?= $u['id'] ?>&set=<?= $u['trangthai'] == 1 ? 0 : 1 ?>" 
                                               class="btn-edit" 
                                               style="background: <?= $u['trangthai'] == 1 ? '#e67e22' : '#27ae60' ?>"
                                               title="<?= $u['trangthai'] == 1 ? 'Khóa tài khoản' : 'Mở khóa' ?>"
                                               onclick="return confirm('Xác nhận thay đổi trạng thái tài khoản?')">
                                                <i class="fa <?= $u['trangthai'] == 1 ? 'fa-lock' : 'fa-unlock' ?>"></i>
                                            </a>
                                        <?php else: ?>
                                            <small style="color: #94a3b8; font-style: italic;">Bảo vệ</small>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
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
</script>
</body>
</html>