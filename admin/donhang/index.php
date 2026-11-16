<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/Shopbanhang/conn.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/Shopbanhang/func.php';

// 1. Kiểm tra quyền truy cập (Cho phép cả Admin và Nhân viên)
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'nhanvien'])) {
    header("Location: ../../login.php");
    exit;
}

$keyword   = $_GET['keyword'] ?? '';
$trangthai = $_GET['trangthai'] ?? '';

$listDonHang = getAllDonHangFilter($keyword, $trangthai);
$orders = [];

if ($listDonHang) {
    while ($row = mysqli_fetch_assoc($listDonHang)) {
        $orders[] = $row;
    }
}

// Bảng ánh xạ trạng thái đơn hàng -> Class màu badge CSS
$badge_map = [
    'Chờ xác nhận' => 'warning',   // Màu vàng
    'Đã xác nhận'  => 'primary',   // Màu xanh dương
    'Đang giao'    => 'info',      // Màu xanh lam
    'Đã giao'      => 'success',   // Màu xanh lá
    'Đã hủy'       => 'danger'     // Màu đỏ
];
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý đơn hàng - Triple Beauty</title>
    <!-- Google Fonts & FontAwesome -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/chitietdon.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
            --shadow-lg: 0 10px 25px -5px rgba(0,0,0,0.05);
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

        /* Đã bỏ màu xanh, active sẽ dùng chung background với hover */
        #sidebar ul li a:hover, #sidebar ul li.active > a {
            background: var(--sidebar-hover);
            color: #ffffff;
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

        .main-body {
            padding: 30px;
        }

        .breadcrumb {
            font-size: 13px;
            color: #64748b;
            margin-bottom: 8px;
        }

        h2 {
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 20px;
            color: var(--text-dark);
        }

        .card {
            background: #ffffff;
            border-radius: 16px;
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow-sm);
            overflow: hidden;
        }

        .card-header {
            padding: 20px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--border-color);
            background: #ffffff;
            flex-wrap: wrap;
            gap: 15px;
        }

        .header-title {
            font-size: 16px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--text-dark);
        }

        .header-right-tools {
            display: flex;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }

        .filter-form {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
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
        }

        .search-input-group input {
            padding: 8px 12px 8px 36px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 14px;
            outline: none;
            transition: border-color 0.2s;
        }

        .search-input-group input:focus {
            border-color: var(--primary-color);
        }

        .filter-select-group select {
            padding: 8px 12px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 14px;
            outline: none;
            background: #fff;
            cursor: pointer;
        }

        .btn-filter-submit {
            padding: 8px 16px;
            background: var(--primary-color);
            color: #fff;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .btn-refresh {
            padding: 8px 12px;
            background: #f1f5f9;
            color: #64748b;
            border-radius: 8px;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .card-body {
            padding: 24px;
        }

        /* Badge Colors */
        .badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }
        .badge.warning { background: #fef3c7; color: #d97706; }
        .badge.info    { background: #e0f2fe; color: #0284c7; }
        .badge.primary { background: #e0e7ff; color: #4f46e5; }
        .badge.success { background: #d1fae5; color: #059669; }
        .badge.danger  { background: #fee2e2; color: #dc2626; }
        .badge.secondary{ background: #f1f5f9; color: #64748b; }

        /* Style cho nút chuyển đổi */
        .view-switcher {
            display: flex;
            gap: 6px;
        }
        .btn-view-type {
            padding: 8px 14px;
            border: 1px solid var(--border-color);
            background: white;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
            color: #64748b;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .btn-view-type.active {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }

        /* Style cho bảng tùy chỉnh */
        .table-custom {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .table-custom th {
            background: #f8fafc;
            padding: 14px 16px;
            text-align: left;
            border-bottom: 2px solid var(--border-color);
            font-size: 12px;
            text-transform: uppercase;
            color: #64748b;
            font-weight: 700;
        }
        .table-custom td {
            padding: 14px 16px;
            border-bottom: 1px solid var(--border-color);
            font-size: 14px;
            color: var(--text-dark);
        }
        .text-price { color: #ef4444; font-weight: bold; }
        .action-buttons { display: flex; gap: 8px; }

        .btn-view, .btn-edit {
            width: 32px;
            height: 32px;
            border-radius: 6px;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            text-decoration: none;
            font-size: 13px;
        }
        .btn-view { background: #e0e7ff; color: #4f46e5; }
        .btn-edit { background: #e2e8f0; color: #475569; }

        /* Product Card List (Thẻ view) */
        .product-card-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
        }
        .product-card {
            background: #fff;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 20px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            box-shadow: var(--shadow-sm);
        }
        .product-card-info h4 {
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 8px;
        }
        .product-card-info p {
            font-size: 14px;
            color: #64748b;
            margin-bottom: 6px;
        }
        .product-card-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        .product-card-actions .btn-view, .product-card-actions .btn-edit {
            flex: 1;
            width: auto;
            height: 36px;
            border-radius: 8px;
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        .modal-content {
            background-color: #fff;
            margin: 5% auto;
            padding: 24px;
            border-radius: 16px;
            width: 80%;
            max-width: 700px;
            position: relative;
            box-shadow: var(--shadow-lg);
        }
        .close-btn {
            position: absolute;
            right: 20px;
            top: 15px;
            font-size: 24px;
            font-weight: bold;
            cursor: pointer;
            color: #94a3b8;
        }
        .close-btn:hover { color: #000; }
    </style>
</head>
<body>

<audio id="orderAudio">
  <source src="https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3" type="audio/mpeg">
</audio>

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
                <span><i class="fa-solid fa-headset"></i> Hỗ trợ: <strong>1900 9477</strong></span>
                <span><i class="fa-solid fa-envelope"></i> <strong>web@tbvietnam.com</strong></span>
            </div>
            <div class="nav-user-profile">
                <div class="user-avatar">
                    <i class="fa-solid fa-user-shield"></i>
                </div>
                <div class="user-details">
                    <span class="welcome-text">Xin chào,</span>
                    <span class="user-name"><?= htmlspecialchars($_SESSION['admin_name'] ?? $_SESSION['user_name'] ?? 'Tài khoản') ?></span>
                </div>
                <a href="../../logout.php" class="btn-logout" title="Đăng xuất">
                    <i class="fa-solid fa-arrow-right-from-bracket"></i>
                </a>
            </div>
        </header>

        <div class="main-body">
            <div class="card">
                <div class="card-header">
                    <span class="header-title"><i class="fa fa-list"></i> Danh sách đơn hàng</span>
                    <div class="header-right-tools">
                        <form method="GET" class="filter-form">
                            <div class="search-input-group">
                                <i class="fa fa-search"></i>
                                <input type="text" name="keyword" placeholder="Mã đơn hoặc tên khách" value="<?= htmlspecialchars($keyword) ?>">
                            </div>
                            <div class="filter-select-group">
                                <select name="trangthai">
                                    <option value="">-- Tất cả trạng thái --</option>
                                    <option value="Chờ xác nhận" <?= $trangthai === 'Chờ xác nhận' ? 'selected' : '' ?>>Chờ xác nhận</option>
                                    <option value="Đã xác nhận" <?= $trangthai === 'Đã xác nhận' ? 'selected' : '' ?>>Đã xác nhận</option>
                                    <option value="Đang giao" <?= $trangthai === 'Đang giao' ? 'selected' : '' ?>>Đang giao</option>
                                    <option value="Đã giao" <?= $trangthai === 'Đã giao' ? 'selected' : '' ?>>Đã giao</option>
                                    <option value="Đã hủy" <?= $trangthai === 'Đã hủy' ? 'selected' : '' ?>>Đã hủy</option>
                                </select>
                            </div>
                            <button type="submit" class="btn-filter-submit"><i class="fa fa-filter"></i> Lọc</button>
                            <?php if ($keyword !== '' || $trangthai !== ''): ?>
                                <a href="index.php" class="btn-refresh"><i class="fa-solid fa-arrow-rotate-left"></i></a>
                            <?php endif; ?>
                        </form>

                        <div class="view-switcher">
                            <button type="button" onclick="switchView('table')" id="btn-table" class="btn-view-type active" title="Xem dạng bảng">
                                <i class="fa fa-table"></i> Bảng
                            </button>
                            <button type="button" onclick="switchView('card')" id="btn-card" class="btn-view-type" title="Xem dạng thẻ">
                                <i class="fa fa-th-large"></i> Thẻ
                            </button>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <!-- VIEW BẢNG -->
                    <div id="table-view" class="view-container">
                        <div class="table-responsive">
                            <table class="table-custom">
                                <thead>
                                    <tr>
                                        <th>Mã ĐH</th>
                                        <th>Khách hàng</th>
                                        <th>Điện thoại</th>
                                        <th>Tổng tiền</th>
                                        <th>Ngày đặt</th>
                                        <th>Trạng thái</th>
                                        <th>Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($orders)) : ?>
                                        <?php foreach ($orders as $dh) : 
                                            $stt = $dh['trangthai'];
                                            $badge_class = $badge_map[$stt] ?? 'secondary';
                                        ?>
                                        <tr>
                                            <td class="font-bold">#<?= htmlspecialchars($dh['madh']) ?></td>
                                            <td><?= htmlspecialchars($dh['hoten'] ?? 'N/A') ?></td>
                                            <td><?= htmlspecialchars($dh['sdt'] ?? 'N/A') ?></td>
                                            <td class="text-price"><?= number_format($dh['tongtien']) ?>đ</td>
                                            <td><?= date('d/m/Y H:i', strtotime($dh['ngaydat'])) ?></td>
                                            <td>
                                                <span class="badge <?= $badge_class ?>"><?= htmlspecialchars($stt) ?></span>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <button class="btn-view" data-id="<?= htmlspecialchars($dh['madh']) ?>" title="Xem chi tiết"><i class="fa fa-eye"></i></button>
                                                    <a href="trangthaidon.php?id=<?= htmlspecialchars($dh['madh']) ?>" class="btn-edit" title="Cập nhật trạng thái"><i class="fa fa-sync"></i></a>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- VIEW THẺ -->
                    <div id="card-view" class="view-container" style="display: none;">
                        <div class="product-card-list">
                            <?php if (!empty($orders)) : ?>
                                <?php foreach ($orders as $dh) : 
                                    $stt = $dh['trangthai'];
                                    $badge_class = $badge_map[$stt] ?? 'secondary';
                                ?>
                                <div class="product-card">
                                    <div class="product-card-info">
                                        <h4>Đơn hàng #<?= htmlspecialchars($dh['madh']) ?></h4>
                                        <p class="brand">Khách: <?= htmlspecialchars($dh['hoten'] ?? 'N/A') ?></p>
                                        <p class="price">Tổng: <?= number_format($dh['tongtien']) ?> VND</p>
                                        <p>Trạng thái: <span class="badge <?= $badge_class ?>"><?= htmlspecialchars($stt) ?></span></p>
                                    </div>
                                    <div class="product-card-actions">
                                        <button class="btn-view" data-id="<?= htmlspecialchars($dh['madh']) ?>">Xem</button>
                                        <a href="trangthaidon.php?id=<?= htmlspecialchars($dh['madh']) ?>" class="btn-edit">Trạng thái</a>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if (empty($orders)) : ?>
                        <div class="empty-state text-center py-10" style="text-align: center; padding: 40px 0;">
                            <img src="../../assets/images/empty-box.png" style="width: 100px; opacity: 0.5;">
                            <p style="margin-top: 10px; color: #777;">Không tìm thấy đơn hàng nào khớp với bộ lọc.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL CHI TIẾT ĐƠN HÀNG -->
<div id="orderDetailModal" class="modal">
    <div class="modal-content">
        <span class="close-btn">&times;</span>
        <div id="modalBody"></div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Menu Sidebar Toggle
   document.querySelectorAll('.menu-toggle').forEach(menu => {
        menu.addEventListener('click', () => {
            const parent = menu.parentElement;
            parent.classList.toggle('open');
            const submenu = parent.querySelector('.submenu');
            if (submenu) {
                if (parent.classList.contains('open')) {
                    submenu.style.display = 'block';
                } else {
                    submenu.style.display = 'none';
                }
            }
        });
    });

    // Modal Xử lý
    const modal = document.getElementById('orderDetailModal');
    const modalBody = document.getElementById('modalBody');
    const closeBtn = document.querySelector('.close-btn');

    document.querySelectorAll('.btn-view').forEach(button => {
        button.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            modal.style.display = "block";
            modalBody.innerHTML = "<p style='text-align:center;'>Đang tải dữ liệu...</p>";
            
            fetch(`get_order_info.php?id=${id}`)
                .then(res => res.text())
                .then(data => modalBody.innerHTML = data)
                .catch(() => modalBody.innerHTML = "<p style='text-align:center; color:red;'>Lỗi tải dữ liệu!</p>");
        });
    });

    if (closeBtn) {
        closeBtn.onclick = () => modal.style.display = "none";
    }
    window.onclick = (e) => { if (e.target == modal) modal.style.display = "none"; }

    // TỰ ĐỘNG CẬP NHẬT (REAL-TIME POLLING)
    let lastStateHash = null;
    let lastOrderCount = 0;

    function checkUpdates() {
        fetch('check_new_order.php')
            .then(res => res.json())
            .then(data => {
                if (lastStateHash !== null && lastStateHash !== data.hash) {
                    if (data.total > lastOrderCount) {
                        const audio = document.getElementById('orderAudio');
                        if (audio) {
                            audio.play().catch(err => console.log("Cần tương tác người dùng để phát âm thanh"));
                        }
                        Swal.fire({
                            title: 'Cập nhật hệ thống!',
                            text: 'Có đơn hàng mới hoặc có thay đổi trạng thái đơn.',
                            icon: 'info',
                            confirmButtonText: 'Làm mới ngay',
                            confirmButtonColor: '#6366f1'
                        }).then(() => location.reload());
                    } else {
                        location.reload();
                    }
                }
                lastStateHash = data.hash;
                lastOrderCount = data.total;
            })
            .catch(err => console.error("Lỗi khi kiểm tra đơn mới:", err));
    }

    setInterval(checkUpdates, 10000);
});

function switchView(type) {
    const tableView = document.getElementById('table-view');
    const cardView = document.getElementById('card-view');
    const btnTable = document.getElementById('btn-table');
    const btnCard = document.getElementById('btn-card');

    if (type === 'table') {
        tableView.style.display = 'block';
        cardView.style.display = 'none';
        btnTable.classList.add('active');
        btnCard.classList.remove('active');
        localStorage.setItem('preferredView', 'table');
    } else {
        tableView.style.display = 'none';
        cardView.style.display = 'block';
        btnCard.classList.add('active');
        btnTable.classList.remove('active');
        localStorage.setItem('preferredView', 'card');
    }
}

// Khôi phục tùy chọn xem (Bảng/Thẻ) đã lưu
document.addEventListener('DOMContentLoaded', () => {
    const savedView = localStorage.getItem('preferredView') || 'table';
    switchView(savedView);
});
</script>
</body>
</html>