<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/Shopbanhang/conn.php';

// Kiểm tra quyền nhân viên/admin
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'nhanvien'])) {
    header("Location: /Shopbanhang/admin/login.php");
    exit();
}

// Xử lý Xóa voucher
if (isset($_GET['action']) && $_GET['action'] == 'delete') {
    $id = intval($_GET['id']);
    
    // Thực hiện câu lệnh xóa trong database
    $sql_delete = "DELETE FROM vouchers WHERE voucher_id = $id";
    if (mysqli_query($conn, $sql_delete)) {
        // Chuyển hướng về lại trang danh sách voucher (thay đường dẫn cho khớp với file của bạn)
        header("Location: /Shopbanhang/admin/noidung/vouchers.php");
        exit();
    } else {
        echo "Lỗi khi xóa: " . mysqli_error($conn);
    }
}
// Lấy danh sách voucher kết hợp lấy tên loại khách hàng (nếu bảng loai_khachhang có trường maloai và tenloai)
$query = "SELECT v.*, l.tenloai FROM vouchers v LEFT JOIN loai_khachhang l ON v.id_loai = l.maloai ORDER BY v.voucher_id DESC";
$result = mysqli_query($conn, $query);

$userName = $_SESSION['admin_name'] ?? $_SESSION['user_name'] ?? 'Nhân viên';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title>Quản lý Vouchers - Triple Beauty</title>
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
        --accent-color: #a31d37;
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
        padding: 20px 30px;
        max-width: 1400px;
        margin: 0 auto;
        width: 100%;
    }

    h2 {
        font-size: 20px;
        font-weight: 700;
        color: var(--text-dark);
        margin-bottom: 6px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .page-desc {
        color: #64748b;
        font-size: 13px;
        margin-bottom: 20px;
    }

    /* Bảng dữ liệu tùy chỉnh */
    .table-container {
        background: #ffffff;
        border-radius: 12px;
        border: 1px solid var(--border-color);
        padding: 20px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.02);
    }

    .table {
        width: 100%;
        border-collapse: collapse;
        text-align: left;
        font-size: 13px;
    }

    .table th, .table td {
        padding: 12px;
        border-bottom: 1px solid var(--border-color);
    }

    .table th {
        font-weight: 600;
        color: #475569;
        background: #f8fafc;
    }

    .btn-action {
        padding: 6px 12px;
        border-radius: 6px;
        text-decoration: none;
        font-size: 12px;
        font-weight: 600;
        display: inline-block;
    }

    .btn-delete {
        background: #fef2f2;
        color: #ef4444;
    }

    .btn-delete:hover {
        background: #ef4444;
        color: #fff;
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
                <a href="/Shopbanhang/logout.php" class="btn-logout" title="Đăng xuất">
                    <i class="fa fa-sign-out-alt"></i>
                </a>
            </div>
        </header>

        <!-- MAIN BODY -->
        <div class="dashboard-container">
            <h2><i class="fa fa-ticket" style="color: var(--accent-color);"></i> QUẢN LÝ MÃ GIẢM GIÁ (VOUCHER)</h2>
            <p class="page-desc">Thêm mới, chỉnh sửa và theo dõi tình trạng sử dụng mã giảm giá của khách hàng.</p>

            <div class="table-container">
                <div style="margin-bottom: 15px; text-align: right;">
                    <a href="add_edit_vouchers.php" class="btn-action" style="background: var(--primary-color); color: #fff;"><i class="fa fa-plus"></i> Thêm Voucher Mới</a>
                </div>
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Mã Voucher</th>
                            <th>Loại Giảm</th>
                            <th>Giảm Tối Đa</th>
                            <th>Đơn Tối Thiểu</th>
                            <th>Thời Hạn</th>
                            <th>Đã Dùng / Tổng</th>
                            <th>Thao Tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td><?= $row['voucher_id'] ?></td>
                            <td><strong><?= htmlspecialchars($row['ma_voucher']) ?></strong></td>
                            <td><?= $row['loai_giam'] == 'phamtram' ? 'Phần trăm (%)' : 'Số tiền (VNĐ)' ?></td>
                            <td><?= number_format($row['giam_toi_da']) ?></td>
                            <td><?= number_format($row['giatri_dh_toithieu']) ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($row['ngay_bat_dau'])) ?> <br> đến <br> <?= date('d/m/Y H:i', strtotime($row['ngay_ket_thuc'])) ?></td>
                            <td><?= $row['dadung'] ?> / <?= $row['soluong'] ?></td>
                            <td>
                                <a href="add_edit_vouchers.php?id=<?= $row['voucher_id'] ?>" class="btn-action" style="background: #e0e7ff; color: var(--primary-color); margin-right: 5px;"><i class="fa fa-edit"></i> Sửa</a>
                                <a href="vouchers.php?action=delete&id=<?= $row['voucher_id'] ?>" class="btn-action btn-delete" onclick="return confirm('Bạn có chắc chắn muốn xóa voucher này không?');"><i class="fa fa-trash"></i> Xóa</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
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