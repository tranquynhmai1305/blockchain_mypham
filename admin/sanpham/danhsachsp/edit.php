<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/Shopbanhang/func.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/Shopbanhang/conn.php';

// 1. KIỂM TRA QUYỀN TRUY CẬP
$userId = $_SESSION['admin_id'] ?? $_SESSION['user_id'] ?? null;
$userRole = $_SESSION['role'] ?? '';

if (!$userId || !in_array($userRole, ['admin', 'nhanvien'])) {
    header("Location: ../../login.php");
    exit;
}

if (!isset($_GET['masp']) || empty($_GET['masp'])) {
    header("Location: index.php");
    exit;
}

$masp = $_GET['masp'];
$sp = getSanPhamById($masp);

if (!$sp) {
    header("Location: index.php");
    exit;
}

// 2. TỰ ĐỘNG GHI NHẬT KÝ HOẠT ĐỘNG
$userName = $_SESSION['admin_name'] ?? $_SESSION['user_name'] ?? 'N/A';
if (function_exists('save_log')) {
    $description = "Người dùng '$userName' (ID: $userId) xem giao diện chỉnh sửa sản phẩm mã: $masp ({$sp['tensp']})";
    save_log($conn, $userId, 'SanPham', 'VIEW', $description);
}

// 3. HÀM CHUẨN HÓA ĐƯỜNG DẪN ẢNH
function normalize_image_path($path) {
    if (empty($path)) {
        return '/Shopbanhang/assets/images/no-image.png';
    }
    $cleanPath = ltrim($path, '/');
    if (strpos($cleanPath, 'Shopbanhang/') === 0) {
        return '/' . $cleanPath;
    }
    return '/Shopbanhang/' . $cleanPath;
}

// Lấy loại & thương hiệu
$loaisp = getAllLoai();
$thuonghieu = getAllThuongHieu();

// Lấy ảnh sản phẩm
$imgs = getHinhAnhBySanPham($masp);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Sửa sản phẩm - <?= htmlspecialchars($sp['tensp']) ?></title>
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

        .card-body {
            padding: 24px;
        }

        /* FORM STYLES */
        .admin-form .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        .admin-form .form-group {
            margin-bottom: 16px;
        }

        .admin-form .form-group.full-width {
            grid-column: span 2;
        }

        .admin-form label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 6px;
        }

        .admin-form .form-control {
            width: 100%;
            padding: 9px 12px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 13px;
            outline: none;
            background: #ffffff;
            color: var(--text-dark);
            transition: all 0.2s;
        }

        .admin-form .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }

        .admin-form textarea.form-control {
            resize: vertical;
        }

        /* IMAGE LIST MANAGER */
        .image-manager-list {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 8px;
        }

        .image-item-box {
            position: relative;
            width: 80px;
            height: 80px;
            border-radius: 8px;
            border: 1px solid var(--border-color);
            background: #f8fafc;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .image-item-box img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .btn-img-delete {
            position: absolute;
            top: 4px;
            right: 4px;
            background: #ef4444;
            color: white;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            text-decoration: none;
            font-weight: bold;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
            transition: opacity 0.2s;
        }

        .btn-img-delete:hover {
            opacity: 0.85;
        }

        .img-sort-btn {
            position: absolute;
            bottom: 2px;
            background: rgba(15, 23, 42, 0.7);
            color: white;
            padding: 1px 6px;
            font-size: 10px;
            border-radius: 4px;
            text-decoration: none;
            transition: background 0.2s;
        }

        .img-sort-btn:hover {
            background: var(--primary-color);
        }

        .img-sort-left { left: 4px; }
        .img-sort-right { right: 4px; }

        /* FORM ACTIONS */
        .form-actions {
            display: flex;
            gap: 12px;
            margin-top: 24px;
            padding-top: 20px;
            border-top: 1px solid var(--border-color);
        }

        .btn-submit-custom {
            background: var(--primary-color);
            color: #ffffff;
            padding: 10px 20px;
            border-radius: 8px;
            border: none;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: opacity 0.2s;
        }

        .btn-submit-custom:hover {
            opacity: 0.9;
        }

        .btn-back-custom {
            background: #f1f5f9;
            color: #475569;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: 1px solid var(--border-color);
            transition: all 0.2s;
        }

        .btn-back-custom:hover {
            background: #e2e8f0;
            color: var(--text-dark);
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
            <div class="card">
                <div class="card-header">
                    <span class="header-title">
                        <i class="fa fa-edit"></i> Cập nhật thông tin sản phẩm: <strong><?= htmlspecialchars($sp['tensp']) ?></strong>
                    </span>
                </div>

                <div class="card-body">
                    <form action="edit_process.php" method="POST" enctype="multipart/form-data" class="admin-form">
                        <input type="hidden" name="masp" value="<?= htmlspecialchars($sp['masp']) ?>">

                        <div class="form-grid">
                            <div class="form-group">
                                <label>Mã sản phẩm:</label>
                                <input type="text" value="<?= htmlspecialchars($sp['masp']) ?>" disabled class="form-control" style="background: #f1f5f9;">
                            </div>

                            <div class="form-group">
                                <label>Tên sản phẩm:</label>
                                <input type="text" name="tensp" value="<?= htmlspecialchars($sp['tensp']) ?>" required class="form-control">
                            </div>

                            <div class="form-group">
                                <label>Loại sản phẩm:</label>
                                <select name="id_loaisp" class="form-control" required>
                                    <?php foreach ($loaisp as $l): ?>
                                        <option value="<?= $l['id_loai'] ?>" <?= ($l['id_loai'] == $sp['id_loaisp']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($l['tenloai']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Thương hiệu:</label>
                                <select name="id_thuonghieu" class="form-control" required>
                                    <?php foreach ($thuonghieu as $th): ?>
                                        <option value="<?= $th['id_thuonghieu'] ?>" <?= ($th['id_thuonghieu'] == $sp['id_thuonghieu']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($th['tenthuonghieu']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Giá gốc (VNĐ):</label>
                                <input type="number" name="gia" value="<?= htmlspecialchars($sp['gia']) ?>" class="form-control" required>
                            </div>

                            <div class="form-group">
                                <label>Giá khuyến mãi (VNĐ):</label>
                                <input type="number" name="giakhuyenmai" value="<?= htmlspecialchars($sp['giakhuyenmai']) ?>" class="form-control">
                            </div>

                            <div class="form-group">
                                <label>Số lượng kho:</label>
                                <input type="number" name="soluong" value="<?= htmlspecialchars($sp['soluong']) ?>" class="form-control" required>
                            </div>

                            <div class="form-group">
                                <label>Xuất xứ:</label>
                                <input type="text" name="xuatxu" value="<?= htmlspecialchars($sp['xuatxu']) ?>" class="form-control">
                            </div>

                            <div class="form-group">
                                <label>Dung tích:</label>
                                <input type="text" name="dungtichsp" value="<?= htmlspecialchars($sp['dungtichsp']) ?>" class="form-control">
                            </div>

                            <div class="form-group">
                                <label>Hạn sử dụng:</label>
                                <input type="text" name="han_sudung" value="<?= htmlspecialchars($sp['han_sudung'] ?? '') ?>" class="form-control">
                            </div>

                            <div class="form-group full-width">
                                <label>Mô tả sản phẩm:</label>
                                <textarea name="mota" class="form-control" rows="5"><?= htmlspecialchars($sp['mota']) ?></textarea>
                            </div>

                            <!-- ẢNH HIỆN CÓ -->
                            <div class="form-group full-width">
                                <label>Danh sách ảnh hiện tại:</label>
                                <?php if (!empty($imgs)): ?>
                                    <div class="image-manager-list">
                                        <?php foreach ($imgs as $img): ?>
                                        <div class="image-item-box">
                                            <img src="<?= htmlspecialchars(normalize_image_path($img['duongdan'])) ?>" alt="Ảnh sản phẩm">
                                            
                                            <!-- Nút xóa ảnh -->
                                            <a href="delete_image.php?id=<?= $img['id_hinhanh'] ?>&masp=<?= $masp ?>"
                                               onclick="return confirm('Bạn có chắc muốn xóa ảnh này?')"
                                               class="btn-img-delete" title="Xóa ảnh">×</a>

                                            <!-- Nút di chuyển vị trí -->
                                            <a href="sort_image.php?id=<?= $img['id_hinhanh'] ?>&type=up&masp=<?= $masp ?>"
                                               class="img-sort-btn img-sort-left" title="Chuyển lên">↑</a>
                                            <a href="sort_image.php?id=<?= $img['id_hinhanh'] ?>&type=down&masp=<?= $masp ?>"
                                               class="img-sort-btn img-sort-right" title="Chuyển xuống">↓</a>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <p style="color: var(--text-muted); font-style: italic; font-size: 13px; margin-top: 6px;">Chưa có ảnh hiển thị cho sản phẩm này.</p>
                                <?php endif; ?>
                            </div>

                            <!-- THÊM ẢNH MỚI -->
                            <div class="form-group full-width">
                                <label>Tải lên ảnh mới (chọn nhiều ảnh):</label>
                                <input type="file" name="hinhanh[]" multiple accept="image/*" class="form-control" style="padding: 6px;">
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" name="btnUpdate" class="btn-submit-custom">
                                <i class="fa fa-save"></i> Cập nhật sản phẩm
                            </button>
                            <a href="index.php" class="btn-back-custom">
                                <i class="fa-solid fa-angle-left"></i> Quay lại
                            </a>
                        </div>
                    </form>
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