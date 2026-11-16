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
    header('Location: index.php');
    exit;
}

$masp = $_GET['masp'];
$sp   = getSanPhamById($masp);

if (!$sp) {
    header('Location: index.php');
    exit;
}

$imgs = getHinhAnhBySanPham($masp);

$gia_goc = $sp['gia'];
$gia_km  = $sp['giakhuyenmai'];

$percent = 0;
if ($gia_km > 0 && $gia_km < $gia_goc) {
    $percent = round((($gia_goc - $gia_km) / $gia_goc) * 100);
}

$userName = $_SESSION['admin_name'] ?? $_SESSION['user_name'] ?? 'N/A';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title>Chi tiết sản phẩm - <?= htmlspecialchars($sp['tensp']) ?></title>
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

    /* PRODUCT DETAIL SPECIFIC STYLES */
    .detail-main {
        display: flex;
        gap: 40px;
    }

    .detail-images {
        flex: 0 0 450px;
    }

    .detail-slider {
        position: relative;
    }

    .detail-slider-img {
        width: 100%;
        height: 420px;
        object-fit: contain;
        background: #f8f8f8;
        border-radius: 10px;
        border: 1px solid var(--border-color);
    }

    .discount-badge {
        position: absolute;
        top: 12px;
        left: 12px;
        background: #ef4444;
        color: white;
        padding: 4px 10px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 700;
    }

    .prev, .next {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        background: rgba(0,0,0,0.4);
        color: white;
        border: none;
        padding: 8px 12px;
        cursor: pointer;
        border-radius: 50%;
        transition: background 0.2s;
    }

    .prev { left: 10px; }
    .next { right: 10px; }

    .prev:hover, .next:hover {
        background: rgba(0,0,0,0.7);
    }

    .dots {
        text-align: center;
        margin-top: 10px;
    }

    .dot {
        display: inline-block;
        width: 8px;
        height: 8px;
        margin: 4px;
        background: #ccc;
        border-radius: 50%;
        cursor: pointer;
    }

    .dot.active {
        background: var(--primary-color);
    }

    .detail-info {
        flex: 1;
    }

    .info-box {
        background: #fafafa;
        padding: 20px;
        border-radius: 10px;
        border: 1px solid var(--border-color);
        margin-bottom: 20px;
    }

    .info-box ul {
        list-style: none;
        padding: 0;
    }

    .info-box li {
        padding: 8px 0;
        border-bottom: 1px solid #eee;
        font-size: 13px;
        color: var(--text-dark);
    }

    .info-box li:last-child {
        border-bottom: none;
    }

    .detail-price {
        margin: 20px 0;
        display: flex;
        align-items: baseline;
        gap: 15px;
    }

    .price-sale {
        color: #ef4444;
        font-size: 26px;
        font-weight: 700;
    }

    .price-old {
        text-decoration: line-through;
        color: var(--text-muted);
        font-size: 15px;
    }

    .detail-actions {
        margin-top: 20px;
        display: flex;
        gap: 12px;
    }

    .detail-actions a {
        padding: 10px 18px;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 600;
        font-size: 13px;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: opacity 0.2s;
    }

    .detail-edit {
        background: var(--primary-color);
        color: white;
    }

    .detail-delete {
        background: #ef4444;
        color: white;
    }

    .detail-back {
        background: #f1f5f9;
        color: #475569;
        border: 1px solid var(--border-color);
    }

    .detail-actions a:hover {
        opacity: 0.9;
    }

    .stock {
        font-weight: bold;
        color: #10b981;
    }

    /* ĐỊNH DẠNG MÔ TẢ 2 CỘT */
    .product-desc {
        line-height: 1.7;
        color: #444;
        font-size: 14px;
        column-count: 2;          /* Chia nội dung thành 2 cột */
        column-gap: 24px;         /* Khoảng cách giữa 2 cột */
        column-rule: 1px solid #eee; /* Đường kẻ phân cách nhẹ giữa 2 cột (tùy chọn) */
    }

    .product-desc p {
        margin-bottom: 8px;
    }

    @media (max-width: 992px) {
        .detail-main {
            flex-direction: column;
        }
        .detail-images {
            flex: 1;
        }
        .product-desc {
            column-count: 1;      /* Màn hình nhỏ tự động gom về 1 cột */
            column-rule: none;
        }
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
                        <i class="fa fa-info-circle"></i> Thông tin sản phẩm: <strong><?= htmlspecialchars($sp['tensp']) ?></strong>
                    </span>
                </div>

                <div class="card-body">
                    <div class="detail-main">

                        <!-- IMAGE -->
                        <div class="detail-images">
                            <?php
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

                            <div class="detail-slider" data-images='<?= json_encode($imageList) ?>'>
                                <img src="<?= $imageList[0] ?>" class="detail-slider-img" alt="Ảnh sản phẩm">

                                <?php if ($percent > 0): ?>
                                    <div class="discount-badge">-<?= $percent ?>%</div>
                                <?php endif; ?>

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

                        <!-- INFO -->
                        <div class="detail-info">
                            <div class="info-box">
                                <ul>
                                    <li><strong>Mã sản phẩm:</strong> <?= htmlspecialchars($sp['masp']) ?></li>
                                    <li><strong>Thương hiệu:</strong> <?= htmlspecialchars($sp['tenthuonghieu'] ?? 'Không có') ?></li>
                                    <li><strong>Loại sản phẩm:</strong> <?= htmlspecialchars($sp['tenloai'] ?? 'Không có') ?></li>
                                    <li><strong>Xuất xứ:</strong> <?= htmlspecialchars($sp['xuatxu'] ?? 'Không rõ') ?></li>
                                    <li><strong>Dung tích:</strong> <?= htmlspecialchars($sp['dungtichsp'] ?? 'Không có') ?></li>
                                    <li><strong>Hạn sử dụng:</strong> <?= htmlspecialchars($sp['han_sudung'] ?? 'Không có') ?></li>
                                    <li><strong>Ngày thêm:</strong> <?= !empty($sp['ngaythemsp']) ? date('d/m/Y', strtotime($sp['ngaythemsp'])) : 'Không có' ?></li>
                                    <li><strong>Tồn kho:</strong> <span class="stock"><?= htmlspecialchars($sp['soluong']) ?> sản phẩm</span></li>
                                </ul>
                            </div>

                            <!-- PRICE -->
                            <div class="detail-price">
                                <?php if ($percent > 0): ?>
                                    <div class="price-sale"><?= number_format($gia_km) ?> đ</div>
                                    <div class="price-old"><?= number_format($gia_goc) ?> đ</div>
                                <?php else: ?>
                                    <div class="price-sale"><?= number_format($gia_goc) ?> đ</div>
                                <?php endif; ?>
                            </div>

                            <!-- MÔ TẢ SẢN PHẨM (2 CỘT) -->
                            <div class="info-box">
                                <h3 style="margin-bottom:10px; font-size: 14px; font-weight: 700; color: var(--text-dark);">
                                    <i class="fa-solid fa-align-left"></i> Mô tả sản phẩm
                                </h3>
                                <div class="product-desc">
                                    <?= !empty($sp['mota']) ? nl2br(htmlspecialchars($sp['mota'])) : "Chưa có mô tả cho sản phẩm này." ?>
                                </div>
                            </div>

                            <!-- ACTION -->
                            <div class="detail-actions">
                                <a href="edit.php?masp=<?= $sp['masp'] ?>" class="detail-edit">
                                    <i class="fa fa-edit"></i> Sửa
                                </a>

                                <a href="delete.php?masp=<?= $sp['masp'] ?>"
                                   onclick="return confirm('Xóa sản phẩm này?')"
                                   class="detail-delete">
                                    <i class="fa fa-trash"></i> Xóa
                                </a>

                                <a href="index.php" class="detail-back">
                                    <i class="fa fa-angle-left"></i> Quay lại
                                </a>
                            </div>

                        </div>
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

document.querySelectorAll('.detail-slider').forEach(slider => {
    let images = JSON.parse(slider.dataset.images);
    let imgTag = slider.querySelector('.detail-slider-img');
    let dots = slider.querySelectorAll('.dot');
    let current = 0;

    function updateSlider(index){
        current = index;
        imgTag.src = images[current];

        if(dots.length > 0){
            dots.forEach(dot => dot.classList.remove('active'));
            dots[current].classList.add('active');
        }
    }

    slider.querySelector('.next')?.addEventListener('click', ()=>{
        let next = (current + 1) % images.length;
        updateSlider(next);
    });

    slider.querySelector('.prev')?.addEventListener('click', ()=>{
        let prev = (current - 1 + images.length) % images.length;
        updateSlider(prev);
    });

    dots.forEach((dot, index)=>{
        dot.addEventListener('click', ()=>{
            updateSlider(index);
        });
    });
});
</script>

</body>
</html>