<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/Shopbanhang/conn.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/Shopbanhang/func.php';

// 1. Kiểm tra quyền truy cập (Chặn truy cập trái phép)
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'nhanvien'])) {
    header("Location: ../../login.php");
    exit;
}

// 2. LẤY VÀ KIỂM TRA ID ĐƠN HÀNG
$id = isset($_GET['id']) ? mysqli_real_escape_string($conn, $_GET['id']) : '';

if (empty($id)) {
    echo "<script>alert('Không có đơn hàng nào được chọn'); window.location.href='index.php';</script>";
    exit;
}

// 3. LẤY THÔNG TIN ĐƠN HÀNG (JOIN để lấy tên khách hàng)
$sql = "SELECT dh.*, kh.hoten, kh.sdt, kh.diachi 
        FROM donhang dh 
        LEFT JOIN khachhang kh ON dh.makh = kh.makh 
        WHERE dh.madh = '$id'";
$res = mysqli_query($conn, $sql);
$donhang = mysqli_fetch_assoc($res);

if (!$donhang) {
    echo "<script>alert('Không tìm thấy đơn hàng!'); window.location.href='index.php';</script>";
    exit;
}

// 4. XỬ LÝ CẬP NHẬT TRẠNG THÁI (POST)
$error = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($donhang['trangthai'] == 'Đã hủy') {
        $error = "Đơn hàng đã bị hủy, không thể cập nhật!";
    } else {
        $trangthai_moi = mysqli_real_escape_string($conn, $_POST['trangthai']);
        $sql_update = "UPDATE donhang SET trangthai='$trangthai_moi' WHERE madh='$id'";
        
        if (mysqli_query($conn, $sql_update)) {
            // ------------------------------------------------------------------
            // 👉 GHI NHẬT KÝ HOẠT ĐỘNG (AUDIT LOG)
            $user_id = $_SESSION['user_id'] ?? $_SESSION['id'] ?? $_SESSION['admin_id'] ?? 0;
            $trangthai_cu = $donhang['trangthai'];
            
            $description = "Đã cập nhật trạng thái đơn hàng #$id từ '$trangthai_cu' thành '$trangthai_moi'";
            save_log($conn, $user_id, 'DonHang', 'UPDATE', $description);
            // ------------------------------------------------------------------

            echo "<script>alert('Cập nhật trạng thái thành công!'); window.location.href='index.php';</script>";
            exit;
        } else {
            $error = "Lỗi cập nhật: " . mysqli_error($conn);
        }
    }
}

// Hàm hỗ trợ chọn class badge tương ứng với trạng thái
function getStatusBadgeClass($status) {
    switch ($status) {
        case 'Chờ xác nhận': return 'status-cho-xac-nhan';
        case 'Đã xác nhận':  return 'status-da-xac-nhan';
        case 'Đang giao':    return 'status-dang-giao';
        case 'Đã giao':      return 'status-da-giao';
        case 'Đã hủy':       return 'status-da-huy';
        default:             return 'status-cho-xac-nhan';
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cập nhật đơn hàng #<?= $donhang['madh'] ?> - Triple Beauty</title>
    
    <!-- Google Fonts & FontAwesome -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
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
     
        .logo-box {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .logo-icon {
            font-size: 20px;
            color: #f43f5e;
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

        /* PAGE CONTENT STYLES */
        .page-content {
            padding: 30px;
        }

        .status-card {
            max-width: 650px;
            margin: 0 auto;
            background: #fff;
            padding: 35px;
            border-radius: 16px;
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow-md);
        }

        .header-title { 
            color: #1e293b; 
            font-size: 20px;
            margin-bottom: 25px; 
            border-bottom: 2px solid #f1f1f1; 
            padding-bottom: 12px; 
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .header-title i {
            color: var(--primary-color);
        }

        .info-group { 
            margin-bottom: 15px; 
            border-bottom: 1px dashed #eee; 
            padding-bottom: 10px; 
            font-size: 14px;
            display: flex;
            align-items: center;
        }

        .info-group strong { 
            width: 140px; 
            display: inline-block; 
            color: #64748b; 
        }

        /* PROFESSIONAL STATUS BADGES */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            letter-spacing: 0.3px;
        }

        .status-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
        }

        .status-cho-xac-nhan {
            background-color: #fef9c3;
            color: #854d0e;
        }
        .status-cho-xac-nhan .status-dot { background-color: #ca8a04; }

        .status-da-xac-nhan {
            background-color: #e0f2fe;
            color: #0369a1;
        }
        .status-da-xac-nhan .status-dot { background-color: #0284c7; }

        .status-dang-giao {
            background-color: #ede9fe;
            color: #6d28d9;
        }
        .status-dang-giao .status-dot { background-color: #7c3aed; }

        .status-da-giao {
            background-color: #dcfce7;
            color: #15803d;
        }
        .status-da-giao .status-dot { background-color: #16a34a; }

        .status-da-huy {
            background-color: #fee2e2;
            color: #b91c1c;
        }
        .status-da-huy .status-dot { background-color: #dc2626; }

        .status-select {
            width: 100%;
            padding: 12px;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            margin: 15px 0;
            font-size: 15px;
            outline: none;
            transition: 0.3s;
            background-color: #fff;
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: var(--text-dark);
            font-weight: 500;
        }

        .status-select:focus { border-color: var(--primary-color); }

        .btn-save {
            width: 100%;
            padding: 14px;
            background: var(--primary-color);
            color: #fff;
            border: none;
            border-radius: 8px;
            font-weight: 700;
            font-size: 15px;
            cursor: pointer;
            transition: 0.3s;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        .btn-save:hover { background: #4f46e5; transform: translateY(-2px); }

        .btn-back {
            display: block;
            text-align: center;
            margin-top: 20px;
            padding: 10px;
            color: #64748b;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            transition: color 0.2s;
        }

        .btn-back:hover { color: var(--text-dark); }

        .alert-error { 
            color: #d9534f; 
            background: #fdf7f7; 
            padding: 12px; 
            border-radius: 8px; 
            margin-bottom: 15px; 
            border-left: 4px solid #d9534f; 
            font-size: 14px;
        }
    </style>
</head>
<body>

<div class="wrapper"> 
    <!-- 1. SIDEBAR -->
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

            <li class="has-submenu open">
                <a href="javascript:void(0);" class="menu-toggle">
                    <div class="menu-left">
                        <i class="fa fa-shopping-cart"></i>
                        <span>Quản lý đơn hàng</span>
                    </div>
                    <i class="fa fa-angle-down arrow"></i>
                </a>
                <ul class="submenu" style="display: block;">
                    <li><a href="/Shopbanhang/admin/donhang/index.php" style="color: #ffffff;">Danh sách đơn hàng</a></li>
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

    <!-- 2. NỘI DUNG CHÍNH -->
    <div id="content">
        <!-- Top Navbar -->
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
                    <span class="user-name"><?= htmlspecialchars($_SESSION['admin_name'] ?? $_SESSION['user_name'] ?? 'Nhân viên') ?></span>
                </div>
                <a href="../logout.php" class="btn-logout" title="Đăng xuất">
                    <i class="fa-solid fa-arrow-right-from-bracket"></i>
                </a>
            </div>
        </header>

        <!-- Page Content -->
        <main class="page-content">
            <div class="status-card">
                <h2 class="header-title">
                    <i class="fa fa-file-invoice"></i> CẬP NHẬT ĐƠN HÀNG #<?= $donhang['madh'] ?>
                </h2>

                <div class="info-group"><strong>Khách hàng:</strong> <?= htmlspecialchars($donhang['hoten'] ?? 'N/A') ?></div>
                <div class="info-group"><strong>Số điện thoại:</strong> <?= htmlspecialchars($donhang['sdt'] ?? 'N/A') ?></div>
                <div class="info-group"><strong>Địa chỉ:</strong> <?= htmlspecialchars($donhang['diachi'] ?? 'N/A') ?></div>
                <div class="info-group"><strong>Ngày đặt:</strong> <?= date('d/m/Y H:i', strtotime($donhang['ngaydat'])) ?></div>
                <div class="info-group"><strong>Tổng tiền:</strong> <span style="color:#f43f5e; font-weight:bold; font-size:1.15em;"><?= number_format($donhang['tongtien'], 0, ',', '.') ?> VNĐ</span></div>
                
                <!-- TRẠNG THÁI HIỆN TẠI HIỂN THỊ DẠNG BADGE CHUYÊN NGHIỆP -->
                <div class="info-group">
                    <strong>Trạng thái:</strong> 
                    <span class="status-badge <?= getStatusBadgeClass($donhang['trangthai']) ?>">
                        <span class="status-dot"></span>
                        <?= htmlspecialchars($donhang['trangthai']) ?>
                    </span>
                </div>

                <form method="POST">
                    <?php if(!empty($error)): ?>
                        <div class="alert-error"><i class="fa fa-exclamation-circle"></i> <?= $error ?></div>
                    <?php endif; ?>

                    <label style="font-weight:700; color:#1e293b; font-size: 14px; display: block; margin-top: 10px;">Chọn trạng thái mới cho đơn hàng:</label>
                    <select name="trangthai" class="status-select" <?= $donhang['trangthai'] == 'Đã hủy' ? 'disabled' : '' ?>>
                        <?php 
                        $list_status = [
                            "Chờ xác nhận" => "Chờ xác nhận",
                            "Đã xác nhận"  => "Đã xác nhận",
                            "Đang giao"    => "Đang giao",
                            "Đã giao"      => "Đã giao",
                            "Đã hủy"       => "Đã hủy"
                        ];
                        foreach($list_status as $val => $label): ?>
                            <option value="<?= $val ?>" <?= $donhang['trangthai'] == $val ? 'selected' : '' ?>>
                                <?= $label ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <?php if($donhang['trangthai'] != 'Đã hủy'): ?>
                        <button type="submit" class="btn-save">
                            <i class="fa fa-save"></i> CẬP NHẬT TRẠNG THÁI
                        </button>
                    <?php else: ?>
                        <div class="alert-error" style="text-align:center;">
                            <strong>Đơn hàng này đã hủy.</strong> Không thể thay đổi trạng thái.
                        </div>
                    <?php endif; ?>

                    <a href="index.php" class="btn-back">
                        <i class="fa fa-chevron-left"></i> Quay lại danh sách đơn hàng
                    </a>
                </form>
            </div>
        </main>
    </div>
</div>

<script>
// Sidebar Toggle Submenu
document.querySelectorAll('.menu-toggle').forEach(menu => {
    menu.addEventListener('click', () => {
        const parent = menu.parentElement;
        parent.classList.toggle('open');
    });
});
</script>

</body>
</html>