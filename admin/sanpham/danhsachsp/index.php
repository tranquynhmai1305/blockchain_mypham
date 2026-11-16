<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/Shopbanhang/func.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/Shopbanhang/conn.php';

// Kiểm tra quyền truy cập (Admin hoặc Nhân viên)
$userRole = $_SESSION['role'] ?? '';
if (!isset($_SESSION['admin_id']) && !in_array($userRole, ['admin', 'nhanvien'])) {
    header("Location: ../../login.php");
    exit;
}

$listLoai = getAllLoai();
$listThuongHieu = getAllThuongHieu();

$keyword = $_GET['keyword'] ?? '';
$loai = $_GET['loai'] ?? '';
$thuonghieu = $_GET['thuonghieu'] ?? '';

$listSanPham = getAllSanPham($keyword, $loai, $thuonghieu);
$products = [];
if ($listSanPham) {
    while ($row = mysqli_fetch_assoc($listSanPham)) {
        $products[] = $row;
    }
}

$showAll = isset($_GET['show']) && $_GET['show'] == 'all';

if (!$showAll) {
    $products = array_slice($products, 0, 15);
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý sản phẩm - Triple Beauty</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
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
            line-height: 1.5;
        }

        .wrapper {
            display: flex;
            width: 100%;
            min-height: 100vh;
        }

        /* SIDEBAR STYLES */
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
    text-align: center; /* Căn giữa toàn bộ nội dung bên trong */
}

.logo-box {
    display: flex;
    align-items: center;
    justify-content: center; /* Căn giữa icon và chữ Triple Beauty theo chiều ngang */
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
    text-align: center; /* Căn giữa phần Website | Xóa Cache */
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

        /* MAIN CONTENT & NAVBAR STYLES */
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

        .user-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: #e0e7ff;
            color: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
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

        /* DASHBOARD CONTAINER */
        .dashboard-container {
            padding: 16px 30px;
            max-width: 1400px;
            margin: 0 auto;
            width: 100%;
        }

        .breadcrumb {
            font-size: 13px;
            color: #64748b;
            margin-bottom: 4px;
        }

        h2 {
            font-size: 20px;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 14px;
        }

        /* CARD STYLES */
        .card {
            background: #ffffff;
            border-radius: 16px;
            border: 1px solid var(--border-color);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
            overflow: hidden;
            margin-bottom: 20px;
        }

        .card-header {
            padding: 16px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--border-color);
            background: #ffffff;
        }

        .header-title {
            font-weight: 700;
            font-size: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--primary-color);
        }

        /* TOOLBAR & FILTER FORM STYLES */
        .card-toolbar {
            padding: 14px 20px;
            background: #f8fafc;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .filter-form {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            width: 100%;
        }

        .search-input-group {
            position: relative;
            display: flex;
            align-items: center;
        }

        .search-input-group i {
            position: absolute;
            left: 12px;
            color: #94a3b8;
            font-size: 13px;
        }

        .search-input-group input {
            padding: 8px 12px 8px 34px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 13px;
            outline: none;
            background: #ffffff;
            width: 250px;
            transition: all 0.2s;
        }

        .search-input-group input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }

        .filter-select-group {
            display: flex;
            gap: 8px;
        }

        .filter-select-group select {
            padding: 8px 12px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 13px;
            outline: none;
            background: #ffffff;
            color: var(--text-dark);
            cursor: pointer;
            transition: all 0.2s;
        }

        .filter-select-group select:focus {
            border-color: var(--primary-color);
        }

        .filter-actions-group {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-left: auto;
        }

        .btn-filter-submit {
            background: var(--primary-color);
            color: #ffffff;
            padding: 8px 16px;
            border-radius: 8px;
            border: none;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: opacity 0.2s;
        }

        .btn-filter-submit:hover {
            opacity: 0.9;
        }

        .btn-refresh {
            color: #ef4444;
            background: #fef2f2;
            padding: 8px 12px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border: 1px solid #fee2e2;
            transition: all 0.2s;
        }

        .btn-refresh:hover {
            background: #ef4444;
            color: #ffffff;
        }

        /* VIEW TOGGLE & ADD BTN */
        .header-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .view-toggle {
            display: flex;
            background: #f1f5f9;
            padding: 3px;
            border-radius: 8px;
            border: 1px solid var(--border-color);
        }

        .btn-view-mode {
            background: transparent;
            border: none;
            padding: 6px 12px;
            font-size: 13px;
            font-weight: 600;
            color: #64748b;
            border-radius: 6px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
        }

        .btn-view-mode.active, .btn-view-mode:hover {
            background: #ffffff;
            color: var(--primary-color);
            box-shadow: var(--shadow-sm);
        }

        .btn-add {
            background: var(--primary-color);
            color: #fff;
            padding: 8px 14px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            box-shadow: 0 2px 4px rgba(99, 102, 241, 0.2);
            transition: all 0.2s;
        }

        .btn-add:hover {
            opacity: 0.95;
            transform: translateY(-1px);
        }

        .card-body {
            padding: 20px;
        }

        /* PRODUCT CARDS GRID */
        .product-card-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 20px;
        }

        .product-card {
            background: #ffffff;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 14px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            transition: all 0.2s;
            box-shadow: 0 2px 5px rgba(0,0,0,0.02);
        }

        .product-card:hover {
            border-color: var(--primary-color);
            box-shadow: 0 6px 15px rgba(99, 102, 241, 0.08);
            transform: translateY(-2px);
        }

        /* SLIDER & IMAGES */
        .card-img {
            width: 100%;
            height: 160px;
            border-radius: 8px;
            overflow: hidden;
            background: #f8fafc;
            position: relative;
            margin-bottom: 12px;
        }

        .slider {
            width: 100%;
            height: 100%;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .slider-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .slider .prev, .slider .next {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(0,0,0,0.4);
            color: white;
            border: none;
            width: 26px;
            height: 26px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            opacity: 0;
            transition: opacity 0.2s;
        }

        .slider:hover .prev, .slider:hover .next {
            opacity: 1;
        }

        .slider .prev { left: 6px; }
        .slider .next { right: 6px; }

        .dots {
            position: absolute;
            bottom: 6px;
            display: flex;
            gap: 4px;
        }

        .dot {
            width: 5px;
            height: 5px;
            background: rgba(255,255,255,0.6);
            border-radius: 50%;
            cursor: pointer;
        }

        .dot.active {
            background: white;
            width: 12px;
            border-radius: 3px;
        }

        .product-name {
            font-size: 14px;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 8px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            height: 40px;
        }

        .product-price {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 10px;
        }

        .price-sale {
            font-size: 14px;
            font-weight: 700;
            color: #ef4444;
        }

        .price-old {
            font-size: 12px;
            color: #94a3b8;
            text-decoration: line-through;
        }

        /* ACTION BUTTONS */
        .btn-action-custom {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            font-size: 13px;
            transition: all 0.2s;
        }

        .btn-view {
            background: #eff6ff;
            color: #3b82f6;
        }
        .btn-view:hover { background: #3b82f6; color: #fff; }

        .btn-edit {
            background: #fdf8f6;
            color: #f97316;
        }
        .btn-edit:hover { background: #f97316; color: #fff; }

        .btn-delete {
            background: #fef2f2;
            color: #ef4444;
        }
        .btn-delete:hover { background: #ef4444; color: #fff; }

        /* TABLE VIEW */
        .table-responsive {
            width: 100%;
            overflow-x: auto;
        }

        .custom-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 13px;
        }

        .custom-table th {
            background: #f8fafc;
            padding: 12px 14px;
            font-weight: 600;
            color: #475569;
            border-bottom: 1px solid var(--border-color);
        }

        .custom-table td {
            padding: 12px 14px;
            border-bottom: 1px solid var(--border-color);
            vertical-align: middle;
            color: var(--text-dark);
        }

        .custom-table tr:hover {
            background: #f8fafc;
        }

        .table-img {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 6px;
            border: 1px solid var(--border-color);
        }

        /* STATUS BADGES & TOGGLE BTNS */
        .badge-status {
            font-weight: 600;
            font-size: 12px;
            padding: 3px 8px;
            border-radius: 6px;
            display: inline-block;
        }
        .badge-active { background: #dcfce7; color: #15803d; }
        .badge-inactive { background: #fee2e2; color: #b91c1c; }

        .btn-status-toggle {
            padding: 4px 10px;
            font-size: 11px;
            font-weight: 600;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            margin-top: 4px;
            transition: opacity 0.2s;
        }
        .btn-status-toggle.on { background: #fee2e2; color: #b91c1c; }
        .btn-status-toggle.off { background: #dcfce7; color: #15803d; }
        .btn-status-toggle:hover { opacity: 0.85; }

        .action-group {
            display: flex;
            gap: 6px;
        }

        /* SHOW ALL BUTTON CONTAINER */
        .show-all-container {
            text-align: center;
            margin-top: 20px;
        }

        .btn-show-all {
            padding: 10px 20px;
            background: #ffffff;
            color: var(--primary-color);
            border: 1px solid var(--primary-color);
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 13px;
            display: inline-block;
            transition: all 0.2s;
            box-shadow: var(--shadow-sm);
        }

        .btn-show-all:hover {
            background: var(--primary-color);
            color: #ffffff;
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
        <header class="top-navbar">
            <div class="nav-left-info">
                <span><i class="fa fa-phone"></i> Hỗ trợ 24/7: 1900 9477</span>
                <span><i class="fa-solid fa-envelope"></i> Email: web@tbvietnam.com</span>
            </div>

            <div class="nav-user-profile">
                <div class="user-avatar">
                    <i class="fa fa-user"></i>
                </div>
                <div class="user-details">
                    <span class="welcome-text">Xin chào,</span>
                    <span class="user-name"><?= htmlspecialchars($_SESSION['admin_name'] ?? $_SESSION['user_name'] ?? 'Nhân viên') ?></span>
                </div>
                <a href="../../logout.php" class="btn-logout" title="Đăng xuất">
                    <i class="fa fa-sign-out-alt"></i>
                </a>
            </div>
        </header>

        <!-- MAIN BODY -->
        <div class="dashboard-container">
            <div class="card">
                <!-- MAIN HEADER: Tiêu đề & Hành động chính (Thêm SP, Chế độ xem) -->
                <div class="card-header">
                    <span class="header-title"><i class="fa fa-list"></i> Danh sách sản phẩm</span>
                    
                    <div class="header-actions">
                        <div class="view-toggle">
                            <button type="button" onclick="setView('card')" id="btn-mode-card" class="btn-view-mode active">
                                <i class="fa fa-th-large"></i> Thẻ
                            </button>
                            <button type="button" onclick="setView('table')" id="btn-mode-table" class="btn-view-mode">
                                <i class="fa fa-table"></i> Bảng
                            </button>
                        </div>

                        <a href="add.php" class="btn-add">
                            <i class="fa fa-plus"></i> Thêm sản phẩm
                        </a>
                    </div>
                </div>

                <!-- TOOLBAR & FILTER SECTION: Tách thành hàng riêng biệt để không bị chật chội -->
                <div class="card-toolbar">
                    <form method="GET" class="filter-form">
                        <div class="search-input-group">
                            <i class="fa fa-search"></i>
                            <input type="text" name="keyword" placeholder="Tìm sản phẩm (mã hoặc tên)..."
                                value="<?= htmlspecialchars($_GET['keyword'] ?? '') ?>">
                        </div>

                        <div class="filter-select-group">
                            <select name="loai">
                                <option value="">-- Tất cả loại --</option>
                                <?php foreach ($listLoai as $itemLoai): ?>
                                    <option value="<?= $itemLoai['id_loai'] ?>"
                                        <?= (($_GET['loai'] ?? '') == $itemLoai['id_loai']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($itemLoai['tenloai']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <select name="thuonghieu">
                                <option value="">-- Tất cả thương hiệu --</option>
                                <?php foreach ($listThuongHieu as $th): ?>
                                    <option value="<?= $th['id_thuonghieu'] ?>"
                                        <?= (($_GET['thuonghieu'] ?? '') == $th['id_thuonghieu']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($th['tenthuonghieu']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="filter-actions-group">
                            <button type="submit" class="btn-filter-submit">
                                <i class="fa fa-filter"></i> Lọc kết quả
                            </button>
                            <?php if (!empty($keyword) || !empty($loai) || !empty($thuonghieu)): ?>
                                <a href="index.php" class="btn-refresh" title="Hủy lọc">
                                    <i class="fa-solid fa-arrow-rotate-left"></i> Bỏ lọc
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>

                <div class="card-body">
                    <!-- CARD VIEW -->
                    <div id="card-view" class="product-card-list">
                        <?php if (!empty($products)) : ?>
                            <?php foreach ($products as $sp) : ?>
                            <div class="product-card">
                                <div>
                                    <!-- ẢNH -->
                                    <div class="card-img">
                                        <?php
                                        $imgs = getHinhAnhBySanPham($sp['masp']);
                                        $baseUrl = "/Shopbanhang/";
                                        $imageList = [];

                                        if (!empty($imgs)) {
                                            foreach ($imgs as $img) {
                                                $imageList[] = $baseUrl . ltrim($img['duongdan'], '/');
                                            }
                                        } else {
                                            $imageList[] = $baseUrl . "images/no-image.png";
                                        }
                                        ?>

                                        <div class="slider" data-images='<?= json_encode($imageList) ?>'>
                                            <img src="<?= $imageList[0] ?>" class="slider-img">

                                            <?php if (count($imageList) > 1): ?>
                                                <button class="prev">&#10094;</button>
                                                <button class="next">&#10095;</button>

                                                <div class="dots">
                                                    <?php foreach ($imageList as $index => $img): ?>
                                                        <span class="dot <?= $index === 0 ? 'active' : '' ?>"></span>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <!-- TÊN -->
                                    <h4 class="product-name" title="<?= htmlspecialchars($sp['tensp']) ?>">
                                        <?= htmlspecialchars($sp['tensp']) ?>
                                    </h4>

                                    <!-- GIÁ -->
                                    <div class="product-price">
                                        <?php
                                        $gia_goc = $sp['gia'];
                                        $gia_km  = $sp['giakhuyenmai'];

                                        if ($gia_km > 0 && $gia_km < $gia_goc):
                                        ?>
                                            <span class="price-sale"><?= number_format($gia_km) ?> đ</span>
                                            <span class="price-old"><?= number_format($gia_goc) ?> đ</span>
                                        <?php else: ?>
                                            <span class="price-sale"><?= number_format($gia_goc) ?> đ</span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- NÚT HÀNH ĐỘNG -->
                                <div style="display: flex; justify-content: center; gap: 8px; margin-top: 10px; border-top: 1px solid var(--border-color); padding-top: 10px;">
                                    <a href="detail.php?masp=<?= $sp['masp'] ?>" class="btn-action-custom btn-view" title="Xem">
                                        <i class="fa fa-eye"></i>
                                    </a>
                                    <a href="edit.php?masp=<?= $sp['masp'] ?>" class="btn-action-custom btn-edit" title="Sửa">
                                        <i class="fa fa-edit"></i>
                                    </a>
                                    <a href="delete.php?masp=<?= $sp['masp'] ?>" 
                                       onclick="return confirm('Xóa sản phẩm này?')"
                                       class="btn-action-custom btn-delete" title="Xóa">
                                        <i class="fa fa-trash"></i>
                                    </a>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <p style="grid-column: 1 / -1; text-align: center; color: #64748b; padding: 30px 0;">Chưa có sản phẩm nào.</p>
                        <?php endif; ?>
                    </div>

                    <!-- TABLE VIEW -->
                    <div id="table-view" style="display:none;" class="table-responsive">
                        <table class="custom-table">
                            <thead>
                                <tr>
                                    <th>Ảnh</th>
                                    <th>Tên sản phẩm</th>
                                    <th>Thương hiệu</th>
                                    <th>Giá</th>
                                    <th>Trạng thái</th>
                                    <th>Hành động</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($products)): ?>
                                    <?php foreach ($products as $sp): ?>
                                    <tr>
                                        <td>
                                            <?php 
                                            $firstImg = $baseUrl . "images/no-image.png";
                                            $imgsT = getHinhAnhBySanPham($sp['masp']);
                                            if (!empty($imgsT) && isset($imgsT[0]['duongdan'])) {
                                                $firstImg = $baseUrl . ltrim($imgsT[0]['duongdan'], '/');
                                            }
                                            ?>
                                            <img src="<?= $firstImg ?>" class="table-img">
                                        </td>
                                        <td style="font-weight: 600; max-width: 250px;"><?= htmlspecialchars($sp['tensp']) ?></td>
                                        <td><?= htmlspecialchars($sp['tenthuonghieu'] ?? 'N/A') ?></td>
                                        <td>
                                            <?php
                                            $gia_goc = $sp['gia'];
                                            $gia_km  = $sp['giakhuyenmai'];

                                            if ($gia_km > 0 && $gia_km < $gia_goc):
                                            ?>
                                                <span style="color:#ef4444; font-weight:700;"><?= number_format($gia_km) ?> đ</span><br>
                                                <span style="text-decoration:line-through; color:#94a3b8; font-size:11px;"><?= number_format($gia_goc) ?> đ</span>
                                            <?php else: ?>
                                                <span style="color:#ef4444; font-weight:700;"><?= number_format($gia_goc) ?> đ</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if($sp['trangthai'] == 1): ?>
                                                <span class="badge-status badge-active">Đang bán</span>
                                            <?php else: ?>
                                                <span class="badge-status badge-inactive">Ngừng bán</span>
                                            <?php endif; ?>
                                            <br>
                                            <a href="../../toggle_status.php?masp=<?= $sp['masp'] ?>">
                                                <?php if($sp['trangthai'] == 1): ?>
                                                    <button class="btn-status-toggle on">Ngừng bán</button>
                                                <?php else: ?>
                                                    <button class="btn-status-toggle off">Bật bán</button>
                                                <?php endif; ?>
                                            </a>
                                        </td>
                                        <td>
                                            <div class="action-group">
                                                <a href="detail.php?masp=<?= $sp['masp'] ?>" class="btn-action-custom btn-view" title="Xem">
                                                    <i class="fa fa-eye"></i>
                                                </a>
                                                <a href="edit.php?masp=<?= $sp['masp'] ?>" class="btn-action-custom btn-edit" title="Sửa">
                                                    <i class="fa fa-edit"></i>
                                                </a>
                                                <a href="delete.php?masp=<?= $sp['masp'] ?>" 
                                                   onclick="return confirm('Bạn có chắc chắn muốn xóa sản phẩm này?')"
                                                   class="btn-action-custom btn-delete" title="Xóa">
                                                    <i class="fa fa-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" style="text-align: center; color: #64748b; padding: 30px;">Chưa có sản phẩm nào.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <?php if (!$showAll && count($products) >= 15): ?>
                <div class="show-all-container">
                    <a href="?show=all" class="btn-show-all">
                        Hiển thị tất cả sản phẩm
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
// Sidebar sub-menu toggle
document.querySelectorAll('.menu-toggle').forEach(menu => {
    menu.addEventListener('click', () => {
        menu.parentElement.classList.toggle('open');
    });
});

// Switch view mode (Card / Table)
function setView(type) {
    const cardView = document.getElementById('card-view');
    const tableView = document.getElementById('table-view');
    const btnCard = document.getElementById('btn-mode-card');
    const btnTable = document.getElementById('btn-mode-table');

    if (type === 'card') {
        cardView.style.display = 'grid';
        tableView.style.display = 'none';
        btnCard.classList.add('active');
        btnTable.classList.remove('active');
        localStorage.setItem('viewMode', 'card');
    } else {
        cardView.style.display = 'none';
        tableView.style.display = 'block';
        btnTable.classList.add('active');
        btnCard.classList.remove('active');
        localStorage.setItem('viewMode', 'table');
    }
}

// Restore saved view mode on load
window.addEventListener('DOMContentLoaded', () => {
    const saved = localStorage.getItem('viewMode');
    if (saved === 'table') {
        setView('table');
    }
});

// Image Slider implementation
document.querySelectorAll('.slider').forEach(slider => {
    let imagesData = slider.dataset.images;
    if (!imagesData) return;
    
    let images = JSON.parse(imagesData);
    let imgTag = slider.querySelector('.slider-img');
    let dots = slider.querySelectorAll('.dot');
    let current = 0;

    function updateSlider(index) {
        current = index;
        imgTag.src = images[current];

        if (dots.length > 0) {
            dots.forEach(dot => dot.classList.remove('active'));
            if (dots[current]) dots[current].classList.add('active');
        }
    }

    slider.querySelector('.next')?.addEventListener('click', (e) => {
        e.preventDefault();
        let next = (current + 1) % images.length;
        updateSlider(next);
    });

    slider.querySelector('.prev')?.addEventListener('click', (e) => {
        e.preventDefault();
        let prev = (current - 1 + images.length) % images.length;
        updateSlider(prev);
    });

    dots.forEach((dot, index) => {
        dot.addEventListener('click', (e) => {
            e.preventDefault();
            updateSlider(index);
        });
    });
});
</script>

</body>
</html>