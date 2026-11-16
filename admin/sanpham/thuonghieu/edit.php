<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/Shopbanhang/func.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/Shopbanhang/conn.php'; // Nhúng conn.php để có sẵn kết nối $conn

// Kiểm tra quyền truy cập (Admin hoặc Nhân viên)
$userRole = $_SESSION['role'] ?? '';
if (!isset($_SESSION['admin_id']) && !in_array($userRole, ['admin', 'nhanvien'])) {
    header("Location: ../../login.php");
    exit;
}

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$id = trim($_GET['id']);

// Lấy thông tin thương hiệu hiện tại
$stmt = $conn->prepare("SELECT * FROM thuonghieu WHERE id_thuonghieu = ?");
$stmt->bind_param("s", $id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

if (!$row) {
    header("Location: index.php");
    exit;
}

$error = "";

// XỬ LÝ KHI NHẤN NÚT CẬP NHẬT
if (isset($_POST['btnUpdate'])) {
    $ten  = trim($_POST['tenthuonghieu']);
    $mota = trim($_POST['mota']);
    
    // Mặc định giữ lại ảnh cũ
    $hinh_save = $row['hinh_thuonghieu'];

    // Xử lý nếu người dùng chọn file ảnh mới
    if (isset($_FILES['hinhanh']) && $_FILES['hinhanh']['error'] == 0) {
        $target_dir = $_SERVER['DOCUMENT_ROOT'] . "/Shopbanhang/images/";
        
        // Tạo tên file duy nhất để tránh trùng lặp
        $file_name = time() . "_" . basename($_FILES["hinhanh"]["name"]);
        $target_file = $target_dir . $file_name;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        // Kiểm tra định dạng file (jpg, png, jpeg, gif)
        $allow_types = array('jpg', 'png', 'jpeg', 'gif');
        if (in_array($imageFileType, $allow_types)) {
            if (move_uploaded_file($_FILES["hinhanh"]["tmp_name"], $target_file)) {
                // Nếu upload thành công, cập nhật đường dẫn lưu vào DB
                $hinh_save = "images/" . $file_name;
            } else {
                $error = "❌ Có lỗi xảy ra khi upload file.";
            }
        } else {
            $error = "❌ Chỉ chấp nhận file JPG, JPEG, PNG & GIF.";
        }
    }

    if (empty($error)) {
        // Cập nhật Database
        $sql_update = "UPDATE thuonghieu SET tenthuonghieu = ?, mota = ?, hinh_thuonghieu = ? WHERE id_thuonghieu = ?";
        $stmt_up = $conn->prepare($sql_update);
        $stmt_up->bind_param("ssss", $ten, $mota, $hinh_save, $id);
        
        if ($stmt_up->execute()) {

            // GHI NHẬT KÝ HOẠT ĐỘNG (AUDIT LOG - CẬP NHẬT THƯƠNG HIỆU)
            if (function_exists('save_log')) {
                $admin_id = $_SESSION['admin_id'] ?? $_SESSION['user_id'] ?? 0;
                $description = "Đã cập nhật thương hiệu: " . $ten . " (Mã: " . $id . ")";
                save_log($conn, $admin_id, 'ThuongHieu', 'UPDATE', $description);
            }

            echo "<script>alert('Cập nhật thương hiệu thành công!'); window.location.href='index.php';</script>";
            exit;
        } else {
            $error = "❌ Cập nhật thất bại: " . $conn->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Chỉnh sửa thương hiệu - Triple Beauty</title>
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
            padding: 12px 30px;
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
            width: 34px;
            height: 34px;
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
            width: 32px;
            height: 32px;
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

        /* Đã giảm padding container để khung sát lên trên hơn và gọn gàng */
        .dashboard-container {
            padding: 16px 24px;
        }

        /* CARD & 2-COLUMN FORM COMPONENTS */
        .card {
            background: #ffffff;
            border-radius: 14px;
            border: 1px solid var(--border-color);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.02);
            overflow: hidden;
            max-width: 950px;
            margin: 0 auto;
        }

        .card-header {
            padding: 14px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--border-color);
            background: #ffffff;
        }

        .card-header span {
            font-weight: 700;
            font-size: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--text-dark);
        }

        .card-body {
            padding: 20px;
        }

        /* 2-Column Layout Grid */
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
        }

        .form-group {
            margin-bottom: 14px;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            font-size: 12.5px;
            color: #475569;
            margin-bottom: 6px;
        }

        .form-control {
            width: 100%;
            padding: 9px 12px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 13.5px;
            font-family: inherit;
            color: var(--text-dark);
            background: #f8fafc;
            transition: all 0.2s;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            background: #fff;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
            outline: none;
        }

        textarea.form-control {
            resize: none;
        }

        .form-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 16px;
            padding-top: 14px;
            border-top: 1px solid var(--border-color);
        }

        .btn-save {
            background: var(--primary-color);
            color: #fff;
            padding: 9px 18px;
            border-radius: 8px;
            border: none;
            font-size: 13.5px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            box-shadow: 0 2px 4px rgba(99, 102, 241, 0.2);
            transition: all 0.2s;
        }

        .btn-save:hover {
            opacity: 0.95;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(99, 102, 241, 0.3);
        }

        .btn-back {
            background: #f1f5f9;
            color: #64748b;
            padding: 9px 18px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 13.5px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
        }

        .btn-back:hover {
            background: #e2e8f0;
            color: var(--text-dark);
        }

        .alert-error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626;
            padding: 10px 14px;
            border-radius: 8px;
            margin-bottom: 14px;
            font-size: 13.5px;
            max-width: 950px;
            margin-left: auto;
            margin-right: auto;
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
            <?php if(!empty($error)): ?>
                <div class="alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <span><i class="fa fa-edit" style="color: var(--primary-color);"></i> Chỉnh sửa thương hiệu: <?= htmlspecialchars($row['id_thuonghieu']) ?></span>
                </div>

                <div class="card-body">
                    <form action="" method="POST" enctype="multipart/form-data">
                        <div class="form-grid">
                            <!-- CỘT TRÁI: Thông tin cơ bản & Ảnh -->
                            <div>
                                <div class="form-group">
                                    <label>Mã thương hiệu (ID):</label>
                                    <input type="text" value="<?= htmlspecialchars($row['id_thuonghieu']) ?>" disabled class="form-control" style="background: #e2e8f0; cursor: not-allowed;">
                                </div>
                                
                                <div class="form-group">
                                    <label>Tên thương hiệu:</label>
                                    <input type="text" name="tenthuonghieu" value="<?= htmlspecialchars($row['tenthuonghieu']) ?>" required class="form-control">
                                </div>

                                <div class="form-group">
                                    <label>Ảnh hiện tại:</label><br>
                                    <?php if (!empty($row['hinh_thuonghieu'])): ?>
                                        <img src="/Shopbanhang/<?= htmlspecialchars($row['hinh_thuonghieu']) ?>" 
                                            style="width: 80px; height: 80px; object-fit: cover; border-radius: 6px; margin-top: 4px; border: 1px solid var(--border-color); box-shadow: var(--shadow-sm);">
                                    <?php else: ?>
                                        <span style="color: var(--text-muted); font-size: 13px;">Chưa có ảnh</span>
                                    <?php endif; ?>
                                </div>

                                <div class="form-group">
                                    <label>Thay đổi ảnh mới (Nếu muốn):</label>
                                    <input type="file" name="hinhanh" accept="image/*" class="form-control" style="padding: 6px 12px;">
                                    <small style="color: var(--text-muted); font-size: 11.5px; margin-top: 3px; display: block;">Định dạng: jpg, png, jpeg. Dung lượng dưới 2MB</small>
                                </div>
                            </div>

                            <!-- CỘT PHẢI: Mô tả dài -->
                            <div>
                                <div class="form-group" style="height: 100%; display: flex; flex-direction: column;">
                                    <label>Mô tả:</label>
                                    <textarea name="mota" rows="11" class="form-control" style="flex: 1;"><?= htmlspecialchars($row['mota'] ?? '') ?></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" name="btnUpdate" class="btn-save">
                                <i class="fa fa-save"></i> Cập nhật thương hiệu
                            </button>
                            <a href="index.php" class="btn-back">
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