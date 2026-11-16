<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/Shopbanhang/func.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/Shopbanhang/conn.php';

// 1. Kiểm tra đăng nhập (Cho phép cả Admin và Nhân viên)
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'nhanvien'])) {
    header("Location: /Shopbanhang/login.php");
    exit;
}

// Kiểm tra lại kết nối Database
if (!$conn || !mysqli_ping($conn)) {
    die("Lỗi kết nối CSDL. Vui lòng thử lại hoặc khởi động lại MySQL trong XAMPP!");
}

// 2. XỬ LÝ CẬP NHẬT BANNER
if (isset($_POST['update_banner'])) {
    $id = $_POST['id'];
    
    // Lấy thông tin ảnh cũ và vị trí để ghi log chi tiết
    $stmt_old = $conn->prepare("SELECT hinh_anh, vi_tri FROM banner WHERE id = ?");
    $stmt_old->bind_param("i", $id);
    $stmt_old->execute();
    $banner_data = $stmt_old->get_result()->fetch_assoc();
    $old_img = $banner_data['hinh_anh'] ?? '';
    $vi_tri_ten = ($banner_data['vi_tri'] == 'banner_chinh') ? 'Banner Lớn (Trái)' : 'Banner Nhỏ (Phải)';

    $target_dir = "../../images/"; // Đường dẫn vật lý để lưu file
    $file_extension = pathinfo($_FILES["hinh_anh"]["name"], PATHINFO_EXTENSION);
    $new_file_name = "banner_" . time() . "." . $file_extension; 
    $target_file = $target_dir . $new_file_name;
    $db_path = "images/" . $new_file_name; // Đường dẫn lưu vào DB

    $allow_types = array('jpg', 'png', 'jpeg', 'gif', 'webp');
    if (in_array(strtolower($file_extension), $allow_types)) {
        if (move_uploaded_file($_FILES["hinh_anh"]["tmp_name"], $target_file)) {
            $sql = "UPDATE banner SET hinh_anh = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $db_path, $id);
            
            if ($stmt->execute()) {
                // Xóa file cũ
                if (!empty($old_img) && file_exists("../../" . $old_img)) {
                    unlink("../../" . $old_img);
                }

                // ------------------------------------------------------------------
                // 👉 GHI NHẬT KÝ HOẠT ĐỘNG (AUDIT LOG)
                $user_id = $_SESSION['user_id'] ?? $_SESSION['id'] ?? $_SESSION['admin_id'] ?? 0;
                $description = "Đã cập nhật hình ảnh mới cho $vi_tri_ten (ID #$id)";
                save_log($conn, $user_id, 'Banner', 'UPDATE', $description);
                // ------------------------------------------------------------------

                echo "<script>alert('Cập nhật banner thành công!'); window.location.href='banner.php';</script>";
                exit;
            }
        } else {
            echo "<script>alert('Lỗi: Không thể upload file.');</script>";
        }
    } else {
        echo "<script>alert('Chỉ cho phép định dạng: JPG, JPEG, PNG, GIF, WEBP.');</script>";
    }
}

$banners = $conn->query("SELECT * FROM banner ORDER BY vi_tri ASC");
$userName = $_SESSION['admin_name'] ?? $_SESSION['user_name'] ?? 'Nhân viên';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title>Quản lý Banner Trang Chủ - Triple Beauty</title>
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

    .breadcrumb {
        font-size: 13px;
        color: #64748b;
        margin-bottom: 4px;
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

    /* BANNER GRID & CARDS */
    .banner-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
        gap: 24px;
    }

    .banner-card {
        background: #ffffff;
        border-radius: 12px;
        border: 1px solid var(--border-color);
        padding: 20px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.02);
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }

    .banner-badge {
        background: var(--accent-color);
        color: white;
        padding: 6px 12px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 600;
        display: inline-block;
        margin-bottom: 15px;
    }

    .banner-preview {
        margin-bottom: 15px;
        border-radius: 8px;
        overflow: hidden;
        height: 220px;
        background: #f1f5f9;
        border: 1px solid var(--border-color);
    }

    .banner-preview img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .form-group {
        margin-bottom: 14px;
    }

    .form-group label {
        display: block;
        font-size: 13px;
        font-weight: 600;
        color: #475569;
        margin-bottom: 6px;
    }

    .form-control-file {
        width: 100%;
        padding: 8px;
        border: 1px solid var(--border-color);
        border-radius: 8px;
        font-size: 13px;
        background: #f8fafc;
        outline: none;
    }

    .btn-update {
        width: 100%;
        background: var(--accent-color);
        color: #fff;
        border: none;
        padding: 11px;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        font-size: 13px;
        transition: opacity 0.2s;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
    }

    .btn-update:hover {
        opacity: 0.9;
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
            <h2><i class="fa fa-image" style="color: var(--accent-color);"></i> QUẢN LÝ BANNER TRANG CHỦ</h2>
            <p class="page-desc">Thay đổi và cập nhật hình ảnh hiển thị tại trang chủ khách hàng.</p>

            <div class="banner-grid">
                <?php while($row = $banners->fetch_assoc()): ?>
                <div class="banner-card">
                    <div>
                        <div class="banner-badge">
                            Vị trí: <?= ($row['vi_tri'] == 'banner_chinh') ? 'Banner Lớn (Trái)' : 'Banner Nhỏ (Phải)' ?>
                        </div>

                        <div class="banner-preview">
                            <?php 
                            $image_path_to_check = "../../" . $row['hinh_anh'];
                            if (file_exists($image_path_to_check) && !empty($row['hinh_anh'])) {
                                $display_src = $image_path_to_check . "?t=" . time();
                            } else {
                                $display_src = "https://placehold.co/600x400?text=Anh+Khong+Ton+Tai";
                            }
                            ?>
                            <img src="<?= $display_src ?>" alt="Banner">
                        </div>
                    </div>
                    
                    <form action="" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="id" value="<?= $row['id'] ?>">
                        
                        <div class="form-group">
                            <label>Chọn ảnh thay thế:</label>
                            <input type="file" name="hinh_anh" accept="image/*" required class="form-control-file">
                        </div>

                        <button type="submit" name="update_banner" class="btn-update">
                            <i class="fa fa-sync-alt"></i> CẬP NHẬT HÌNH ẢNH
                        </button>
                    </form>
                </div>
                <?php endwhile; ?>
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