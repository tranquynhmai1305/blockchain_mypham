<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/Shopbanhang/conn.php';

// Kiểm tra quyền nhân viên/admin
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'nhanvien'])) {
    header("Location: /Shopbanhang/login.php");
    exit();
}

$voucher_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$voucher = [
    'ma_voucher' => '',
    'loai_giam' => 'phamtram',
    'giam_toi_da' => '',
    'giatri_dh_toithieu' => '',
    'ngay_bat_dau' => '',
    'ngay_ket_thuc' => '',
    'soluong' => '',
    'id_loai' => 0
];

// Nếu có ID truyền vào, lấy thông tin cũ để hiển thị form sửa
if ($voucher_id > 0) {
    $result = mysqli_query($conn, "SELECT * FROM vouchers WHERE voucher_id = $voucher_id");
    if ($result && mysqli_num_rows($result) > 0) {
        $voucher = mysqli_fetch_assoc($result);
    }
}

// Xử lý khi submit form Thêm/Sửa voucher
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $ma_voucher = mysqli_real_escape_string($conn, $_POST['ma_voucher']);
    $loai_giam = $_POST['loai_giam'];
    $giam_toi_da = floatval($_POST['giam_toi_da']);
    $giatri_dh_toithieu = floatval($_POST['giatri_dh_toithieu']);
    $ngay_bat_dau = $_POST['ngay_bat_dau'];
    $ngay_ket_thuc = $_POST['ngay_ket_thuc'];
    $soluong = intval($_POST['soluong']);
    
    // Xử lý id_loai: nếu bằng 0 thì gán là NULL, ngược lại lấy giá trị số nguyên
    $id_loai_input = intval($_POST['id_loai']);
    $id_loai_sql = ($id_loai_input == 0) ? "NULL" : $id_loai_input;

    $post_id = isset($_POST['voucher_id']) ? intval($_POST['voucher_id']) : 0;

    if ($post_id > 0) {
        // Câu lệnh UPDATE khi chỉnh sửa
        $sql = "UPDATE vouchers SET 
                ma_voucher = '$ma_voucher', 
                loai_giam = '$loai_giam', 
                giam_toi_da = $giam_toi_da, 
                giatri_dh_toithieu = $giatri_dh_toithieu, 
                ngay_bat_dau = '$ngay_bat_dau', 
                ngay_ket_thuc = '$ngay_ket_thuc', 
                soluong = $soluong, 
                id_loai = $id_loai_sql 
                WHERE voucher_id = $post_id";
    } else {
        // Câu lệnh INSERT khi thêm mới
        $sql = "INSERT INTO vouchers (ma_voucher, loai_giam, giam_toi_da, giatri_dh_toithieu, ngay_bat_dau, ngay_ket_thuc, soluong, dadung, id_loai) 
                VALUES ('$ma_voucher', '$loai_giam', $giam_toi_da, $giatri_dh_toithieu, '$ngay_bat_dau', '$ngay_ket_thuc', $soluong, 0, $id_loai_sql)";
    }
    
    if (mysqli_query($conn, $sql)) {
        header("Location: /Shopbanhang/admin/noidung/vouchers.php");
        exit();
    } else {
        $error = "Lỗi cơ sở dữ liệu: " . mysqli_error($conn);
    }
}

// Lấy danh sách loại khách hàng để hiển thị trong select box
$loai_kh_result = mysqli_query($conn, "SELECT * FROM loai_khachhang");
$userName = $_SESSION['admin_name'] ?? $_SESSION['user_name'] ?? 'Nhân viên';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title><?= $voucher_id > 0 ? 'Sửa Voucher' : 'Thêm Voucher Mới' ?> - Triple Beauty</title>
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

    /* DASHBOARD CONTAINER & FORM */
    .dashboard-container {
        padding: 20px 30px;
        max-width: 900px;
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

    .form-card {
        background: #ffffff;
        border-radius: 12px;
        border: 1px solid var(--border-color);
        padding: 25px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.02);
    }

    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
    }

    .form-group {
        margin-bottom: 16px;
    }

    .form-group.full-width {
        grid-column: span 2;
    }

    .form-group label {
        display: block;
        font-size: 13px;
        font-weight: 600;
        color: #475569;
        margin-bottom: 6px;
    }

    .form-control {
        width: 100%;
        padding: 10px 14px;
        border: 1px solid var(--border-color);
        border-radius: 8px;
        font-size: 13px;
        background: #f8fafc;
        outline: none;
        transition: border-color 0.2s;
    }

    .form-control:focus {
        border-color: var(--primary-color);
        background: #fff;
    }

    .btn-submit {
        background: var(--primary-color);
        color: #fff;
        border: none;
        padding: 12px 20px;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        font-size: 14px;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: opacity 0.2s;
    }

    .btn-submit:hover {
        opacity: 0.9;
    }

    .btn-back {
        background: #e2e8f0;
        color: #475569;
        text-decoration: none;
        padding: 12px 20px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 14px;
        display: inline-block;
        margin-right: 10px;
    }

    .alert-error {
        background: #fef2f2;
        color: #ef4444;
        padding: 10px 15px;
        border-radius: 8px;
        margin-bottom: 15px;
        font-size: 13px;
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
            <h2><i class="fa fa-ticket" style="color: var(--accent-color);"></i> <?= $voucher_id > 0 ? 'CHỈNH SỬA VOUCHER' : 'THÊM VOUCHER MỚI' ?></h2>
            <p class="page-desc">Điền thông tin chi tiết để thiết lập mã giảm giá cho khách hàng.</p>

            <?php if (!empty($error)): ?>
                <div class="alert-error"><?= $error ?></div>
            <?php endif; ?>

            <div class="form-card">
                <form action="" method="POST">
                    <?php if ($voucher_id > 0): ?>
                        <input type="hidden" name="voucher_id" value="<?= $voucher_id ?>">
                    <?php endif; ?>

                    <div class="form-row">
                        <div class="form-group full-width">
                            <label>Mã Voucher (Ví dụ: GIAM20K, SUMMER2026):</label>
                            <input type="text" name="ma_voucher" value="<?= htmlspecialchars($voucher['ma_voucher']) ?>" required class="form-control" placeholder="Nhập mã voucher...">
                        </div>

                        <div class="form-group">
                            <label>Loại Giảm:</label>
                            <select name="loai_giam" class="form-control" required>
                                <option value="phamtram" <?= $voucher['loai_giam'] == 'phamtram' ? 'selected' : '' ?>>Phần trăm (%)</option>
                                <option value="tien" <?= $voucher['loai_giam'] == 'tien' ? 'selected' : '' ?>>Số tiền cố định (VNĐ)</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Mức Giảm Tối Đa / Giá Trị Giảm:</label>
                            <input type="number" step="0.01" name="giam_toi_da" value="<?= $voucher['giam_toi_da'] ?>" required class="form-control" placeholder="Nhập số tiền hoặc %...">
                        </div>

                        <div class="form-group">
                            <label>Giá Trị Đơn Hàng Tối Thiểu (VNĐ):</label>
                            <input type="number" step="0.01" name="giatri_dh_toithieu" value="<?= $voucher['giatri_dh_toithieu'] ?>" required class="form-control" placeholder="0">
                        </div>

                        <div class="form-group">
                            <label>Số Lượng Phát Hành:</label>
                            <input type="number" name="soluong" value="<?= $voucher['soluong'] ?>" required class="form-control" placeholder="0">
                        </div>

                        <div class="form-group">
                            <label>Ngày Bắt Đầu:</label>
                            <input type="datetime-local" name="ngay_bat_dau" value="<?= !empty($voucher['ngay_bat_dau']) ? date('Y-m-d\TH:i', strtotime($voucher['ngay_bat_dau'])) : '' ?>" required class="form-control">
                        </div>

                        <div class="form-group">
                            <label>Ngày Kết Thúc:</label>
                            <input type="datetime-local" name="ngay_ket_thuc" value="<?= !empty($voucher['ngay_ket_thuc']) ? date('Y-m-d\TH:i', strtotime($voucher['ngay_ket_thuc'])) : '' ?>" required class="form-control">
                        </div>

                        <div class="form-group full-width">
                            <label>Áp Dụng Cho Loại Khách Hàng:</label>
                            <select name="id_loai" class="form-control" required>
                                <option value="0">Tất cả khách hàng</option>
                                <?php while($lk = mysqli_fetch_assoc($loai_kh_result)): ?>
                                    <option value="<?= $lk['maloai'] ?>" <?= $voucher['id_loai'] == $lk['maloai'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($lk['tenloai']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>

                    <div style="margin-top: 20px;">
                        <a href="vouchers.php" class="btn-back"><i class="fa fa-arrow-left"></i> Quay lại</a>
                        <button type="submit" class="btn-submit">
                            <i class="fa fa-save"></i> <?= $voucher_id > 0 ? 'Cập Nhật Voucher' : 'Thêm Voucher' ?>
                        </button>
                    </div>
                </form>
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