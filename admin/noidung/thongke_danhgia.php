<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/Shopbanhang/func.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/Shopbanhang/conn.php';

// 1. KIỂM TRA QUYỀN TRUY CẬP (Cho phép Admin và Nhân viên)
$userId = $_SESSION['admin_id'] ?? $_SESSION['user_id'] ?? null;
$userRole = $_SESSION['role'] ?? '';

if (!$userId || !in_array($userRole, ['admin', 'nhanvien'])) {
    header("Location: ../../login.php");
    exit;
}

// 2. THỐNG KÊ TỔNG QUAN MỚI
// Tổng số lượt đánh giá
$sql_total_dg = "SELECT COUNT(*) as tong FROM danhgiasp";
$res_total_dg = mysqli_query($conn, $sql_total_dg);
$tong_danh_gia = mysqli_fetch_assoc($res_total_dg)['tong'] ?? 0;

// Điểm đánh giá trung bình chung
$sql_avg_star = "SELECT AVG(sosao) as tb_chung FROM danhgiasp";
$res_avg_star = mysqli_query($conn, $sql_avg_star);
$diem_trung_binh = round(mysqli_fetch_assoc($res_avg_star)['tb_chung'] ?? 0, 1);

// Số lượng sản phẩm đã có đánh giá
$sql_sp_dg = "SELECT COUNT(DISTINCT masp) as sp_dg FROM danhgiasp";
$res_sp_dg = mysqli_query($conn, $sql_sp_dg);
$so_sp_danh_gia = mysqli_fetch_assoc($res_sp_dg)['sp_dg'] ?? 0;

// 3. THỐNG KÊ TỔNG SỐ ĐÁNH GIÁ THEO MỨC SAO (1-5 sao)
$sql_stars = "SELECT sosao, COUNT(*) as soluong FROM danhgiasp GROUP BY sosao ORDER BY sosao DESC";
$res_stars = mysqli_query($conn, $sql_stars);
$thongke_sao = [];
while($r = mysqli_fetch_assoc($res_stars)) {
    $thongke_sao[$r['sosao']] = $r['soluong'];
}

// 4. TOP SẢN PHẨM CÓ ĐÁNH GIÁ DƯỚI 3 SAO (Cần cải thiện)
$sql_bad_sp = "SELECT sp.tensp, AVG(dg.sosao) as tb_sao, COUNT(dg.id_danhgia) as tong_dg
               FROM danhgiasp dg
               JOIN sanpham sp ON dg.masp = sp.masp
               GROUP BY dg.masp
               HAVING tb_sao <= 3.5
               ORDER BY tb_sao ASC LIMIT 10";
$res_bad = mysqli_query($conn, $sql_bad_sp);

// 5. TOP SẢN PHẨM ĐƯỢC "KHEN" NHIỀU NHẤT (Trung bình sao cao nhất)
$sql_good_sp = "SELECT sp.tensp, AVG(dg.sosao) as tb_sao, COUNT(dg.id_danhgia) as tong_dg
                FROM danhgiasp dg
                JOIN sanpham sp ON dg.masp = sp.masp
                GROUP BY dg.masp
                HAVING tb_sao >= 4
                ORDER BY tb_sao DESC, tong_dg DESC LIMIT 10";
$res_good = mysqli_query($conn, $sql_good_sp);

$userName = $_SESSION['admin_name'] ?? $_SESSION['user_name'] ?? 'N/A';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title>Thống kê đánh giá - Triple Beauty</title>
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
        line-height: 1.4;
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
        text-align: center;
    }

    .sidebar-header h3 {
        font-size: 18px;
        font-weight: 700;
        color: #ffffff;
    }

    .header-links {
        margin-top: 6px;
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
        margin-bottom: 16px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    /* OVERVIEW METRICS CARDS (Thống kê tổng quan mới) */
    .overview-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 16px;
        margin-bottom: 20px;
    }

    .overview-card {
        background: #ffffff;
        border-radius: 12px;
        border: 1px solid var(--border-color);
        padding: 16px 20px;
        display: flex;
        align-items: center;
        gap: 16px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
    }

    .overview-icon {
        width: 48px;
        height: 48px;
        border-radius: 10px;
        background: #e0e7ff;
        color: var(--primary-color);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
    }

    .overview-info span {
        font-size: 12px;
        color: #64748b;
        font-weight: 500;
    }

    .overview-info h3 {
        font-size: 20px;
        font-weight: 700;
        color: var(--text-dark);
        margin-top: 2px;
    }

    /* STAR STATS CONTAINER */
    .stats-container { 
        display: grid;
        grid-template-columns: repeat(5, 1fr); 
        gap: 16px; 
        margin-bottom: 20px; 
    }

    .stats-box { 
        background: #ffffff; 
        padding: 16px; 
        border-radius: 12px; 
        border: 1px solid var(--border-color);
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03); 
        text-align: center;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        min-height: 130px; 
    }

    .stats-box .star-label {
        color: #f59e0b;
        font-size: 13px;
        letter-spacing: 2px;
    }

    .stats-box h3 { 
        font-size: 22px; 
        color: var(--text-dark); 
        margin: 4px 0 0 0; 
    }

    .progress-bar { 
        height: 8px; 
        background: #f1f5f9; 
        border-radius: 4px; 
        overflow: hidden; 
        margin-top: 8px; 
        width: 100%; 
    }

    .progress-fill { 
        height: 100%; 
        background: #f59e0b; 
        border-radius: 4px;
    }

    /* TABLES GRID */
    .tables-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin-bottom: 20px;
    }

    /* CARD STYLES */
    .card {
        background: #ffffff;
        border-radius: 14px;
        border: 1px solid var(--border-color);
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
        overflow: hidden;
    }

    .card-header {
        padding: 14px 20px;
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
    }

    .header-title.danger { color: #ef4444; }
    .header-title.success { color: #10b981; }

    .card-body {
        padding: 16px 20px;
        overflow-x: auto;
    }

    /* ADMIN TABLE */
    .admin-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 13px;
    }

    .admin-table th {
        background: #f8fafc;
        color: #475569;
        font-weight: 600;
        padding: 10px 12px;
        border-bottom: 1px solid var(--border-color);
        text-align: left;
    }

    .admin-table td {
        padding: 10px 12px;
        border-bottom: 1px solid var(--border-color);
        color: var(--text-dark);
        vertical-align: middle;
    }

    .admin-table tr:last-child td {
        border-bottom: none;
    }

    .badge {
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }

    .badge-bad {
        background: #fef2f2;
        color: #ef4444;
    }

    .badge-good {
        background: #ecfdf5;
        color: #10b981;
    }

    @media (max-width: 1200px) {
        .stats-container { grid-template-columns: repeat(3, 1fr); }
        .tables-grid { grid-template-columns: 1fr; }
        .overview-grid { grid-template-columns: repeat(2, 1fr); }
    }
    
    @media (max-width: 768px) {
        .stats-container { grid-template-columns: repeat(2, 1fr); }
        .overview-grid { grid-template-columns: 1fr; }
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
                    <span class="user-name"><?= htmlspecialchars($userName) ?></span>
                </div>
                <a href="../../logout.php" class="btn-logout" title="Đăng xuất">
                    <i class="fa fa-sign-out-alt"></i>
                </a>
            </div>
        </header>

        <!-- MAIN BODY -->
        <div class="dashboard-container">
            <!-- THỐNG KÊ TỔNG QUAN MỚI -->
            <div class="overview-grid">
                <div class="overview-card">
                    <div class="overview-icon">
                        <i class="fa fa-comments"></i>
                    </div>
                    <div class="overview-info">
                        <span>Tổng lượt đánh giá</span>
                        <h3><?= number_format($tong_danh_gia) ?></h3>
                    </div>
                </div>

                <div class="overview-card">
                    <div class="overview-icon" style="background: #fef3c7; color: #d97706;">
                        <i class="fa fa-star"></i>
                    </div>
                    <div class="overview-info">
                        <span>Điểm trung bình hệ thống</span>
                        <h3><?= $diem_trung_binh ?> / 5.0 ★</h3>
                    </div>
                </div>

                <div class="overview-card">
                    <div class="overview-icon" style="background: #ecfdf5; color: #10b981;">
                        <i class="fa fa-box-open"></i>
                    </div>
                    <div class="overview-info">
                        <span>Sản phẩm có đánh giá</span>
                        <h3><?= number_format($so_sp_danh_gia) ?></h3>
                    </div>
                </div>
            </div>

            <!-- PHÂN BỐ THEO SỐ SAO -->
            <div class="stats-container">
                <?php for($i=5; $i>=1; $i--): 
                    $count = $thongke_sao[$i] ?? 0;
                    $total = array_sum($thongke_sao);
                    $percent = ($total > 0) ? ($count / $total * 100) : 0;
                ?>
                <div class="stats-box">
                    <div class="star-label">
                        <?= str_repeat('★', $i) ?>
                    </div>
                    <div>
                        <h3><?= number_format($count) ?></h3>
                        <span style="color: #64748b; font-size: 11px;">Lượt đánh giá</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?= $percent ?>%"></div>
                    </div>
                </div>
                <?php endfor; ?>
            </div>

            <!-- BẢNG XẾP HẠNG SẢN PHẨM -->
            <div class="tables-grid">
                <!-- CẦN CẢI THIỆN -->
                <div class="card">
                    <div class="card-header">
                        <span class="header-title danger">
                            <i class="fa fa-thumbs-down"></i> Cần cải thiện (Dưới 3 sao)
                        </span>
                    </div>
                    <div class="card-body">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Sản phẩm</th>
                                    <th style="text-align:center;">TB Sao</th>
                                    <th style="text-align:center;">Lượt</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (mysqli_num_rows($res_bad) > 0): ?>
                                    <?php while($row = mysqli_fetch_assoc($res_bad)): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row['tensp']) ?></td>
                                        <td style="text-align:center;">
                                            <span class="badge badge-bad"><?= number_format($row['tb_sao'], 1) ?> ★</span>
                                        </td>
                                        <td style="text-align:center;"><?= $row['tong_dg'] ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="3" style="text-align:center; color:#94a3b8;">Không có sản phẩm nào cần cải thiện.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- SẢN PHẨM ĐƯỢC KHEN -->
                <div class="card">
                    <div class="card-header">
                        <span class="header-title success">
                            <i class="fa fa-thumbs-up"></i> Sản phẩm được khen nhiều nhất
                        </span>
                    </div>
                    <div class="card-body">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Sản phẩm</th>
                                    <th style="text-align:center;">TB Sao</th>
                                    <th style="text-align:center;">Lượt</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (mysqli_num_rows($res_good) > 0): ?>
                                    <?php while($row = mysqli_fetch_assoc($res_good)): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row['tensp']) ?></td>
                                        <td style="text-align:center;">
                                            <span class="badge badge-good"><?= number_format($row['tb_sao'], 1) ?> ★</span>
                                        </td>
                                        <td style="text-align:center;"><?= $row['tong_dg'] ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="3" style="text-align:center; color:#94a3b8;">Chưa có sản phẩm đạt đánh giá cao.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('.menu-toggle').forEach(menu => {
    menu.addEventListener('click', () => {
        menu.parentElement.classList.toggle('open');
    });
});
</script>

</body>
</html>