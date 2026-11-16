<?php
session_start();

require_once $_SERVER['DOCUMENT_ROOT'] . '/Shopbanhang/conn.php';

// Kiểm tra quyền truy cập (Admin hoặc Nhân viên)
$userRole = $_SESSION['role'] ?? '';
if (!isset($_SESSION['admin_id']) && !in_array($userRole, ['admin', 'nhanvien'])) {
    header("Location: ../../login.php");
    exit;
}

// 1. Lấy từ khóa tìm kiếm từ thanh địa chỉ
$keyword = isset($_GET['keyword']) ? mysqli_real_escape_string($conn, $_GET['keyword']) : '';

// 2. Xây dựng câu lệnh SQL tìm kiếm theo ID hoặc Tên thương hiệu
if (!empty($keyword)) {
    $sql = "SELECT * FROM thuonghieu 
            WHERE tenthuonghieu LIKE '%$keyword%' 
            OR id_thuonghieu LIKE '%$keyword%' 
            ORDER BY id_thuonghieu DESC";
} else {
    $sql = "SELECT * FROM thuonghieu ORDER BY id_thuonghieu DESC";
}
$result = mysqli_query($conn, $sql);

if (!$result) {
    die("Lỗi truy vấn: " . mysqli_error($conn));
}

// Đổ dữ liệu từ MySQL result vào mảng $data
$data = [];
while ($row = mysqli_fetch_assoc($result)) {
    $data[] = $row;
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý thương hiệu - Triple Beauty</title>
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

        .dashboard-container {
            padding: 16px 24px; /* Đã giảm khoảng cách phía trên và xung quanh */
        }

        /* CARD & TABLE COMPONENTS */
        .card {
            background: #ffffff;
            border-radius: 16px;
            border: 1px solid var(--border-color);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
            overflow: hidden;
        }

        .card-header {
            padding: 16px 20px; /* Làm gọn phần header */
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--border-color);
            flex-wrap: wrap;
            gap: 16px;
            background: #ffffff;
        }

        .card-header span {
            font-weight: 700;
            font-size: 16px;
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--text-dark);
        }

        .filter-form {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 0;
        }

        .search-input-group {
            display: flex;
            align-items: center;
            background: #f8fafc;
            border: 1px solid var(--border-color);
            border-radius: 10px;
            padding: 8px 14px;
            width: 300px;
            transition: all 0.2s;
        }

        .search-input-group:focus-within {
            border-color: var(--primary-color);
            background: #fff;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }

        .search-input-group i {
            color: var(--text-muted);
            margin-right: 8px;
            font-size: 14px;
        }

        .search-input-group input {
            border: none;
            outline: none;
            background: transparent;
            font-size: 13px;
            width: 100%;
            font-family: inherit;
            color: var(--text-dark);
        }

        .btn-refresh {
            color: #64748b;
            text-decoration: none;
            font-size: 14px;
            padding: 8px 10px;
            border-radius: 8px;
            background: #f1f5f9;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .btn-refresh:hover {
            background: #e2e8f0;
            color: var(--text-dark);
        }

        .btn-add {
            background: var(--primary-color);
            color: #fff;
            padding: 9px 18px;
            border-radius: 10px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 2px 4px rgba(99, 102, 241, 0.2);
            transition: all 0.2s;
        }

        .btn-add:hover {
            opacity: 0.95;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(99, 102, 241, 0.3);
        }

        .card-body {
            padding: 0;
            overflow-x: auto;
        }

        .admin-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 14px;
        }

        .admin-table th {
            background: #f8fafc;
            padding: 12px 16px; /* Làm gọn bảng */
            font-weight: 600;
            color: #475569;
            border-bottom: 1px solid var(--border-color);
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 0.5px;
        }

        .admin-table td {
            padding: 12px 16px; /* Làm gọn bảng */
            border-bottom: 1px solid var(--border-color);
            vertical-align: middle;
            color: #334155;
        }

        .admin-table tr:last-child td {
            border-bottom: none;
        }

        .admin-table tr {
            transition: background 0.15s ease;
        }

        .admin-table tr:hover {
            background: #f8fafc;
        }

        .brand-img {
            width: 42px;
            height: 42px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow-sm);
        }

        .no-image {
            font-size: 12px;
            color: var(--text-muted);
            background: #f1f5f9;
            padding: 6px 10px;
            border-radius: 6px;
            display: inline-block;
        }

        .brand-name {
            font-weight: 600;
            color: var(--text-dark);
        }

        .brand-desc {
            color: #64748b;
            font-size: 13px;
            max-width: 400px;
            line-height: 1.4;
        }

        .action-buttons {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .btn-edit, .btn-delete {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border-radius: 8px;
            text-decoration: none;
            transition: all 0.2s;
        }

        .btn-edit {
            background: #fef3c7;
            color: #d97706;
        }

        .btn-edit:hover {
            background: #fde68a;
            transform: translateY(-1px);
        }

        .btn-delete {
            background: #fee2e2;
            color: #dc2626;
        }

        .btn-delete:hover {
            background: #fecaca;
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
                <div class="card-header">
                    <span><i class="fa fa-list" style="color: var(--primary-color);"></i> Danh sách thương hiệu</span>
                    
                    <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                        <form method="GET" action="" class="filter-form">
                            <div class="search-input-group">
                                <i class="fa fa-search"></i>
                                <input type="text" name="keyword" placeholder="Tìm tên hoặc mã thương hiệu..." 
                                    value="<?= htmlspecialchars($keyword) ?>">
                                <button type="submit" style="display:none"></button>
                            </div>
                            <?php if (!empty($keyword)): ?>
                                <a href="index.php" class="btn-refresh" title="Hủy lọc">
                                    <i class="fa-solid fa-arrow-rotate-left"></i>
                                </a>
                            <?php endif; ?>
                        </form>

                        <a href="add.php" class="btn-add">
                            <i class="fa fa-plus"></i> Thêm thương hiệu
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th style="width:110px; text-align: center;">Hình ảnh</th>
                                <th style="width:140px;">Mã thương hiệu</th>
                                <th style="width:200px;">Tên thương hiệu</th>
                                <th>Mô tả</th>
                                <th style="width:110px; text-align: center;">Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($data)): ?>
                                <?php foreach ($data as $row): ?>
                                    <tr>
                                        <td style="text-align: center;">
                                            <?php if (!empty($row['hinh_thuonghieu'])): ?>
                                                <img src="../../../<?= htmlspecialchars($row['hinh_thuonghieu']) ?>" 
                                                    alt="<?= htmlspecialchars($row['tenthuonghieu']) ?>" 
                                                    class="brand-img">
                                            <?php else: ?>
                                                <span class="no-image">Không có ảnh</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><strong><?= htmlspecialchars($row['id_thuonghieu']) ?></strong></td>
                                        <td><span class="brand-name"><?= htmlspecialchars($row['tenthuonghieu']) ?></span></td>
                                        <td><div class="brand-desc"><?= htmlspecialchars($row['mota']) ?></div></td>
                                        <td style="text-align: center;">
                                            <div class="action-buttons">
                                                <a href="edit.php?id=<?= $row['id_thuonghieu'] ?>" class="btn-edit" title="Sửa">
                                                    <i class="fa fa-edit"></i>
                                                </a>
                                                <a href="delete.php?id=<?= $row['id_thuonghieu'] ?>" class="btn-delete" title="Xóa" onclick="return confirm('Bạn có chắc chắn muốn xóa thương hiệu này?')">
                                                    <i class="fa fa-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" style="text-align:center; padding: 40px; color: var(--text-muted);">
                                        Không tìm thấy thương hiệu nào khớp với từ khóa "<strong><?= htmlspecialchars($keyword) ?></strong>"
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Sidebar Toggle
document.querySelectorAll('.menu-toggle').forEach(menu => {
    menu.addEventListener('click', () => {
        const parent = menu.parentElement;
        parent.classList.toggle('open');
    });
});
</script>

</body>
</html>