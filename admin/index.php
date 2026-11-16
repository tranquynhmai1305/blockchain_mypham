<?php
session_start();

require_once $_SERVER['DOCUMENT_ROOT'] . '/Shopbanhang/conn.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/Shopbanhang/func.php';

// Kiểm tra quyền truy cập (Admin hoặc Nhân viên)
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'nhanvien'])) {
    header("Location: /Shopbanhang/login.php");
    exit();
}

// 1. Dữ liệu thống kê cũ
$tongSanPham = countSanPham();
$dataHienTai = getThongKeDoanhThu12Thang();
$tongDonHangNamNay = array_sum($dataHienTai['sodon']);
$thangNay = (int)date('m') - 1; 
$doanhThuThangNay = $dataHienTai['doanhthu'][$thangNay];

// 2. BỔ SUNG THỐNG KÊ MỚI (Khách hàng & Đơn chờ xử lý)
$tongKhachHang = 0;
$donHangChoXuLy = 0;

if (function_exists('countKhachHang')) {
    $tongKhachHang = countKhachHang();
} elseif (isset($conn)) {
    $resKH = mysqli_query($conn, "SELECT COUNT(*) as total FROM khachhang");
    if ($resKH) { $tongKhachHang = mysqli_fetch_assoc($resKH)['total'] ?? 0; }
}

if (function_exists('countDonHangChoXuLy')) {
    $donHangChoXuLy = countDonHangChoXuLy();
} elseif (isset($conn)) {
    $resDH = mysqli_query($conn, "SELECT COUNT(*) as total FROM donhang WHERE trangthai = 'Chờ xử lý' OR trangthai = 0 OR trangthai = 'ChoXuLy'");
    if ($resDH) { $donHangChoXuLy = mysqli_fetch_assoc($resDH)['total'] ?? 0; }
}

// 4. BỔ SUNG THỐNG KÊ TRẠNG THÁI ĐƠN HÀNG CHO BIỂU ĐỒ TRÒN
$statusLabels = [];
$statusCounts = [];
$resStatus = mysqli_query($conn, "SELECT trangthai, COUNT(*) as soluong FROM donhang GROUP BY trangthai");
if ($resStatus) {
    while ($row = mysqli_fetch_assoc($resStatus)) {
        // Gán nhãn trạng thái (có thể tùy chỉnh hiển thị tên đẹp hơn nếu cần)
        $statusLabels[] = (string)($row['trangthai'] !== '' ? $row['trangthai'] : 'Chưa xác định');
        $statusCounts[] = (int)$row['soluong'];
    }
}
$jsonStatusLabels = json_encode($statusLabels);
$jsonStatusCounts = json_encode($statusCounts); 

// 3. Dữ liệu cho biểu đồ
$brandData = getThongKeThuongHieu();
$brandLabels = json_encode($brandData['labels']);
$brandCounts = json_encode($brandData['counts']);
$listDoanhThu = json_encode($dataHienTai['doanhthu']);
$listSoDon = json_encode($dataHienTai['sodon']);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Triple Beauty - Dashboard Admin</title>
    <!-- Google Fonts & FontAwesome -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

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
            justify-content: center; /* Đã căn giữa icon và chữ Triple Beauty */
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

        .dashboard-container {
            padding: 30px;
        }

        .dashboard-header {
            margin-bottom: 25px;
        }

        .dashboard-header h2 {
            font-size: 22px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .dashboard-header h2 i {
            color: var(--primary-color);
        }

        .dashboard-header .subtitle {
            font-size: 13px;
            color: #64748b;
            margin-top: 4px;
        }

        /* 5 THẺ THỐNG KÊ (GRID SYSTEM) */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
            gap: 18px;
            margin-bottom: 30px;
        }

        .stat-card {
            border-radius: 16px;
            padding: 20px;
            color: #ffffff;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: var(--shadow-md);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
        }

        .card-pink { background: linear-gradient(135deg, #f43f5e 0%, #fb7185 100%); }
        .card-blue { background: linear-gradient(135deg, #3b82f6 0%, #60a5fa 100%); }
        .card-green { background: linear-gradient(135deg, #10b981 0%, #34d399 100%); }
        .card-purple { background: linear-gradient(135deg, #8b5cf6 0%, #a78bfa 100%); }
        .card-amber { background: linear-gradient(135deg, #f59e0b 0%, #fbbf24 100%); }

        .stat-content h3 {
            font-size: 13px;
            font-weight: 500;
            opacity: 0.9;
            margin-bottom: 6px;
        }

        .stat-number {
            font-size: 24px;
            font-weight: 800;
            line-height: 1.1;
            margin-bottom: 8px;
        }

        .stat-number small {
            font-size: 14px;
            font-weight: 600;
        }

        .stat-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 10px;
            font-weight: 600;
            background: rgba(255, 255, 255, 0.22);
            padding: 3px 8px;
            border-radius: 20px;
        }

        .stat-icon-wrapper {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }

        /* KHU VỰC BIỂU ĐỒ */
        .charts-wrapper {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
        }

        .chart-section {
            background: #ffffff;
            border-radius: 16px;
            padding: 24px;
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow-sm);
        }

        .chart-header {
            margin-bottom: 20px;
        }

        .chart-header h3 {
            font-size: 15px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .chart-container {
            position: relative;
            height: 330px;
            width: 100%;
        }

        @media (max-width: 1200px) {
            .charts-wrapper { grid-template-columns: 1fr; }
        }
        @media (max-width: 992px) {
    .charts-row-2 { grid-template-columns: 1fr !important; }
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
                <a href="logout.php" class="btn-logout" title="Đăng xuất">
                    <i class="fa-solid fa-arrow-right-from-bracket"></i>
                </a>
            </div>
        </header>

        <!-- Main Dashboard Container -->
        <main class="dashboard-container">
            <div class="dashboard-header">
                <div>
                    <h2><i class="fa-solid fa-chart-line"></i> Thống kê tổng quan</h2>
                    <p class="subtitle">Báo cáo tình hình kinh doanh thực tế hệ thống Triple Beauty</p>
                </div>
            </div>

            <!-- STATS CARDS GRID (Bổ sung 2 chỉ số mới) -->
            <div class="stats-grid">
                <div class="stat-card card-pink">
                    <div class="stat-content">
                        <h3>Tổng sản phẩm</h3>
                        <p class="stat-number"><?= number_format($tongSanPham) ?></p>
                        <span class="stat-badge"><i class="fa-solid fa-arrow-trend-up"></i> Đang bán</span>
                    </div>
                    <div class="stat-icon-wrapper">
                        <i class="fa-solid fa-boxes-stacked"></i>
                    </div>
                </div>

                <div class="stat-card card-blue">
                    <div class="stat-content">
                        <h3>Đơn năm <?= date('Y') ?></h3>
                        <p class="stat-number"><?= number_format($tongDonHangNamNay) ?></p>
                        <span class="stat-badge"><i class="fa-solid fa-cart-check"></i> Đã hoàn tất</span>
                    </div>
                    <div class="stat-icon-wrapper">
                        <i class="fa-solid fa-bag-shopping"></i>
                    </div>
                </div>

                <div class="stat-card card-green">
                    <div class="stat-content">
                        <h3>Doanh thu T<?= date('m') ?></h3>
                        <p class="stat-number"><?= number_format($doanhThuThangNay) ?> <small>đ</small></p>
                        <span class="stat-badge"><i class="fa-solid fa-bolt"></i> Trực tiếp</span>
                    </div>
                    <div class="stat-icon-wrapper">
                        <i class="fa-solid fa-coins"></i>
                    </div>
                </div>

                <!-- THỐNG KÊ MỚI 1: TỔNG KHÁCH HÀNG -->
                <div class="stat-card card-purple">
                    <div class="stat-content">
                        <h3>Tổng khách hàng</h3>
                        <p class="stat-number"><?= number_format($tongKhachHang) ?></p>
                        <span class="stat-badge"><i class="fa-solid fa-user-plus"></i> Tài khoản</span>
                    </div>
                    <div class="stat-icon-wrapper">
                        <i class="fa-solid fa-users"></i>
                    </div>
                </div>

                <!-- THỐNG KÊ MỚI 2: ĐƠN HÀNG CHỜ XỬ LÝ -->
                <div class="stat-card card-amber">
                    <div class="stat-content">
                        <h3>Đơn chờ xử lý</h3>
                        <p class="stat-number"><?= number_format($donHangChoXuLy) ?></p>
                        <span class="stat-badge"><i class="fa-solid fa-clock"></i> Cần duyệt</span>
                    </div>
                    <div class="stat-icon-wrapper">
                        <i class="fa-solid fa-hourglass-half"></i>
                    </div>
                </div>
            </div>

            <!-- CHARTS SECTION -->
            <div style="display: flex; flex-direction: column; gap: 20px;">
                <!-- Hàng 1: Biểu đồ doanh thu (chiếm toàn chiều ngang) -->
                <div class="chart-section">
                    <div class="chart-header">
                        <h3><i class="fa-solid fa-chart-area" style="color:#6366f1;"></i> Doanh thu & Đơn hàng (12 tháng)</h3>
                    </div>
                    <div class="chart-container" style="height: 350px;">
                        <canvas id="revenueChart"></canvas>
                    </div>
                </div>

                <!-- Hàng 2: Biểu đồ thương hiệu và Biểu đồ trạng thái đơn hàng (chia 2 cột) -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;" class="charts-row-2">
                    <div class="chart-section">
                        <div class="chart-header">
                            <h3><i class="fa-solid fa-chart-column" style="color:#3b82f6;"></i> Thống kê theo thương hiệu</h3>
                        </div>
                        <div class="chart-container" style="height: 300px;">
                            <canvas id="brandChart"></canvas>
                        </div>
                    </div>

                    <div class="chart-section">
                        <div class="chart-header">
                            <h3><i class="fa-solid fa-chart-pie" style="color:#10b981;"></i> Tỷ lệ trạng thái đơn hàng</h3>
                        </div>
                        <div class="chart-container" style="height: 300px;">
                            <canvas id="orderStatusChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </main>
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

Chart.defaults.font.family = "'Plus Jakarta Sans', sans-serif";
Chart.defaults.color = '#64748b';

// Render Biểu đồ Doanh thu (Line Chart)
const ctxRev = document.getElementById('revenueChart').getContext('2d');
const gradientRev = ctxRev.createLinearGradient(0, 0, 0, 300);
gradientRev.addColorStop(0, 'rgba(99, 102, 241, 0.3)');
gradientRev.addColorStop(1, 'rgba(99, 102, 241, 0.0)');

new Chart(ctxRev, {
    type: 'line',
    data: {
        labels: ['T1', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'T8', 'T9', 'T10', 'T11', 'T12'],
        datasets: [
            {
                label: 'Doanh thu (VNĐ)',
                data: <?php echo $listDoanhThu; ?>,
                borderColor: '#6366f1',
                borderWidth: 3,
                backgroundColor: gradientRev,
                fill: true,
                tension: 0.4,
                yAxisID: 'y'
            },
            {
                label: 'Số đơn hàng',
                data: <?php echo $listSoDon; ?>,
                borderColor: '#06b6d4',
                borderWidth: 2,
                borderDash: [5, 5],
                tension: 0.4,
                yAxisID: 'y1'
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            x: { grid: { display: false } },
            y: { beginAtZero: true, grid: { color: '#f1f5f9' } },
            y1: { position: 'right', beginAtZero: true, grid: { drawOnChartArea: false } }
        }
    }
});

// Render Biểu đồ Thương hiệu (Bar Chart)
const ctxBrand = document.getElementById('brandChart').getContext('2d');
new Chart(ctxBrand, {
    type: 'bar',
    data: {
        labels: <?php echo $brandLabels; ?>,
        datasets: [{
            label: 'Sản phẩm đã bán',
            data: <?php echo $brandCounts; ?>,
            backgroundColor: '#3b82f6',
            borderRadius: 6
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            x: { grid: { display: false } },
            y: { beginAtZero: true, grid: { color: '#f1f5f9' } }
        }
    }
});

const ctxStatus = document.getElementById('orderStatusChart').getContext('2d');
new Chart(ctxStatus, {
    type: 'doughnut',
    data: {
        labels: <?php echo $jsonStatusLabels; ?>,
        datasets: [{
            data: <?php echo $jsonStatusCounts; ?>,
            backgroundColor: [
                '#3b82f6', // Xanh dương
                '#10b981', // Xanh lá
                '#f59e0b', // Vàng cam
                '#f43f5e', // Hồng đỏ
                '#8b5cf6', // Tím
                '#64748b'  // Xám
            ],
            borderWidth: 2,
            borderColor: '#ffffff'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    boxWidth: 12,
                    font: { size: 12 }
                }
            }
        },
        cutout: '65%' // Tạo khoảng trống ở giữa bánh donut nhìn hiện đại hơn
    }
});
</script>

</body>
</html>