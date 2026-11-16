<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/Shopbanhang/conn.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/Shopbanhang/func.php';

// 1. Kiểm tra quyền Admin (Fix lỗi Undefined index)
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo "<script>
        alert('Tài khoản nhân viên không có quyền truy cập vào mục này!');
        window.location.href = '/Shopbanhang/admin/index.php';
    </script>";
    exit();
}

// Lấy danh sách nhân viên từ bảng users
$listAdmins = [];
$resultAdmin = $conn->query("SELECT id, username FROM users ORDER BY username ASC");
if ($resultAdmin) {
    while ($row = $resultAdmin->fetch_assoc()) {
        $listAdmins[] = $row;
    }
}

// Xử lý bộ lọc dữ liệu từ GET
$filter_admin  = $_GET['admin_id'] ?? '';
$filter_module = $_GET['module'] ?? '';
$tu_ngay       = $_GET['tu_ngay'] ?? '';
$den_ngay      = $_GET['den_ngay'] ?? '';

// Cấu hình phân trang
$limit  = 20;
$page   = isset($_GET['page']) && (int)$_GET['page'] > 0 ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Xây dựng câu lệnh lọc động
$whereClauses = [];
$types  = "";
$params = [];

if (!empty($filter_admin)) {
    $whereClauses[] = "l.admin_id = ?";
    $types .= "i";
    $params[] = $filter_admin;
}
if (!empty($filter_module)) {
    $whereClauses[] = "l.module = ?";
    $types .= "s";
    $params[] = $filter_module;
}
if (!empty($tu_ngay)) {
    $whereClauses[] = "DATE(l.created_at) >= ?";
    $types .= "s";
    $params[] = $tu_ngay;
}
if (!empty($den_ngay)) {
    $whereClauses[] = "DATE(l.created_at) <= ?";
    $types .= "s";
    $params[] = $den_ngay;
}

$sqlWhere = "";
if (count($whereClauses) > 0) {
    $sqlWhere = " WHERE " . implode(" AND ", $whereClauses);
}

// 1. Đếm tổng số bản ghi
$sqlCount  = "SELECT COUNT(*) as total FROM activity_logs l" . $sqlWhere;
$stmtCount = $conn->prepare($sqlCount);
if (!empty($params)) {
    $stmtCount->bind_param($types, ...$params);
}
$stmtCount->execute();
$resultCount = $stmtCount->get_result();
$totalRows   = $resultCount->fetch_assoc()['total'] ?? 0;
$totalPages  = ceil($totalRows / $limit);
$stmtCount->close();

// 2. Lấy dữ liệu phân trang (JOIN bảng users)
$sqlData = "SELECT l.*, a.username FROM activity_logs l 
            LEFT JOIN users a ON l.admin_id = a.id" 
            . $sqlWhere . " 
            ORDER BY l.created_at DESC 
            LIMIT ? OFFSET ?";

$stmtData = $conn->prepare($sqlData);
if (!empty($params)) {
    $bindTypes  = $types . "ii";
    $bindParams = array_merge($params, [$limit, $offset]);
    $stmtData->bind_param($bindTypes, ...$bindParams);
} else {
    $stmtData->bind_param("ii", $limit, $offset);
}

$stmtData->execute();
$resultData = $stmtData->get_result();
$logs = [];
if ($resultData) {
    while ($row = $resultData->fetch_assoc()) {
        $logs[] = $row;
    }
}
$stmtData->close();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nhật ký hoạt động hệ thống | Triple Beauty Admin</title>
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
        }

        .logo-box {
            display: flex;
            align-items: center;
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

        .breadcrumb {
            font-size: 13px;
            color: #64748b;
            margin-bottom: 8px;
        }

        /* CARD BOX & TABLES */
        .card-box {
            background: #ffffff;
            border-radius: 16px;
            padding: 24px;
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow-sm);
            margin-top: 20px;
        }

        .filter-form {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 25px;
            align-items: flex-end;
        }

        .filter-form label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
            font-size: 13px;
            color: #475569;
        }

        .filter-form select,
        .filter-form input[type="date"] {
            width: 100%;
            padding: 9px 12px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
            background: #fff;
            color: var(--text-dark);
            outline: none;
            transition: border-color 0.2s;
        }

        .filter-form select:focus,
        .filter-form input[type="date"]:focus {
            border-color: var(--primary-color);
        }

        .btn-primary-custom {
            background: var(--primary-color);
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: opacity 0.2s;
        }

        .btn-primary-custom:hover {
            opacity: 0.9;
        }

        .btn-secondary-custom {
            background: #f1f5f9;
            color: #475569;
            text-decoration: none;
            padding: 10px 16px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 14px;
            font-weight: 600;
            margin-left: 6px;
            transition: background 0.2s;
        }

        .btn-secondary-custom:hover {
            background: #e2e8f0;
        }

        .table-responsive {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 14px;
        }

        thead tr {
            background: #f8fafc;
            border-bottom: 2px solid var(--border-color);
            color: #334155;
        }

        th {
            padding: 14px 12px;
            font-weight: 700;
        }

        td {
            padding: 14px 12px;
            border-bottom: 1px solid var(--border-color);
            color: #334155;
        }

        tbody tr:hover {
            background-color: #f8fafc;
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
                <span><i class="fa-solid fa-headset"></i> Hỗ trợ: <strong>1900 9477</strong></span>
                <span><i class="fa-solid fa-envelope"></i> <strong>web@tbvietnam.com</strong></span>
            </div>
            <div class="nav-user-profile">
                <div class="user-avatar">
                    <i class="fa-solid fa-user-shield"></i>
                </div>
                <div class="user-details">
                    <span class="welcome-text">Xin chào,</span>
                    <span class="user-name"><?= htmlspecialchars($_SESSION['admin_name'] ?? $_SESSION['user_name'] ?? 'Admin') ?></span>
                </div>
                <a href="../logout.php" class="btn-logout" title="Đăng xuất">
                    <i class="fa-solid fa-arrow-right-from-bracket"></i>
                </a>
            </div>
        </header>

        <!-- MAIN BODY -->
        <main class="dashboard-container">
            <div class="breadcrumb">Trang chủ > Quản lý Hệ thống > Nhật ký hoạt động nhân viên</div>

            <div class="dashboard-header">
                <h2><i class="fa-solid fa-clock-rotate-left"></i> Nhật ký hoạt động hệ thống</h2>
            </div>

            <div class="card-box">
                
                <!-- THANH BỘ LỌC (FILTER BAR) -->
                <form method="GET" action="" class="filter-form">
                    <div style="flex: 1; min-width: 180px;">
                        <label>Nhân viên</label>
                        <select name="admin_id">
                            <option value="">-- Tất cả nhân viên --</option>
                            <?php foreach ($listAdmins as $adm): ?>
                                <option value="<?= $adm['id'] ?>" <?= $filter_admin == $adm['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($adm['username']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div style="flex: 1; min-width: 180px;">
                        <label>Phân hệ</label>
                        <select name="module">
                            <option value="">-- Tất cả phân hệ --</option>
                            <option value="SanPham" <?= $filter_module == 'SanPham' ? 'selected' : '' ?>>Sản phẩm</option>
                            <option value="DonHang" <?= $filter_module == 'DonHang' ? 'selected' : '' ?>>Đơn hàng</option>
                            <option value="KhachHang" <?= $filter_module == 'KhachHang' ? 'selected' : '' ?>>Khách hàng</option>
                            <option value="NhanVien" <?= $filter_module == 'NhanVien' ? 'selected' : '' ?>>Nhân viên</option>
                            <option value="HeThong" <?= $filter_module == 'HeThong' ? 'selected' : '' ?>>Hệ thống</option>
                        </select>
                    </div>

                    <div style="flex: 1; min-width: 150px;">
                        <label>Từ ngày</label>
                        <input type="date" name="tu_ngay" value="<?= htmlspecialchars($tu_ngay) ?>">
                    </div>

                    <div style="flex: 1; min-width: 150px;">
                        <label>Đến ngày</label>
                        <input type="date" name="den_ngay" value="<?= htmlspecialchars($den_ngay) ?>">
                    </div>

                    <div style="display: flex; align-items: flex-end;">
                        <button type="submit" class="btn-primary-custom">
                            <i class="fa fa-filter"></i> Lọc dữ liệu
                        </button>
                        <a href="activity_logs.php" class="btn-secondary-custom">
                            <i class="fa fa-refresh"></i> Đặt lại
                        </a>
                    </div>
                </form>

                <!-- BẢNG DỮ LIỆU HIỂN THỊ (TABLE) -->
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th style="width: 60px;">STT</th>
                                <th style="width: 160px;">Thời gian</th>
                                <th style="width: 130px;">Nhân viên</th>
                                <th style="width: 110px;">Phân hệ</th>
                                <th style="width: 110px;">Hành động</th>
                                <th>Mô tả chi tiết</th>
                                <th style="width: 120px;">Địa chỉ IP</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($logs) > 0): ?>
                                <?php 
                                $stt = $offset + 1;
                                foreach ($logs as $log): 
                                ?>
                                    <tr>
                                        <td><?= $stt++ ?></td>
                                        <td style="color: #64748b; white-space: nowrap;">
                                            <?= date('d/m/Y H:i:s', strtotime($log['created_at'])) ?>
                                        </td>
                                        <td style="font-weight: 600; color: var(--primary-color);">
                                            <?= htmlspecialchars($log['username'] ?? 'Không rõ') ?>
                                        </td>
                                        <td>
                                            <span style="background: #e0e7ff; color: #4338ca; padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 600;">
                                                <?= htmlspecialchars($log['module']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php 
                                                $action = strtoupper($log['action'] ?? '');
                                                $actionBg = '#f1f5f9';
                                                $actionColor = '#475569';
                                                if ($action === 'INSERT') { $actionBg = '#dcfce7'; $actionColor = '#15803d'; }
                                                elseif ($action === 'UPDATE') { $actionBg = '#fef9c3'; $actionColor = '#a16207'; }
                                                elseif ($action === 'DELETE') { $actionBg = '#fee2e2'; $actionColor = '#b91c1c'; }
                                                elseif ($action === 'LOGIN') { $actionBg = '#cffafe'; $actionColor = '#0e7490'; }
                                                elseif ($action === 'LOGOUT') { $actionBg = '#f3e8ff'; $actionColor = '#6b21a8'; }
                                            ?>
                                            <span style="background: <?= $actionBg ?>; color: <?= $actionColor ?>; padding: 4px 8px; border-radius: 6px; font-weight: 700; font-size: 11px;">
                                                <?= htmlspecialchars($action) ?>
                                            </span>
                                        </td>
                                        <td style="color: #334155;"><?= htmlspecialchars($log['description']) ?></td>
                                        <td style="color: #64748b; font-family: monospace;"><?= htmlspecialchars($log['ip_address']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; padding: 40px; color: #64748b;">Không tìm thấy lịch sử hoạt động nào phù hợp.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- PHÂN TRANG (PAGINATION) -->
                <?php if ($totalPages > 1): ?>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 25px; font-size: 14px; color: #64748b;">
                        <div>Hiển thị trang <strong><?= $page ?></strong> / <strong><?= $totalPages ?></strong> (Tổng số <strong><?= $totalRows ?></strong> bản ghi)</div>
                        <div style="display: flex; gap: 6px;">
                            <?php 
                            $queryParams = $_GET;
                            unset($queryParams['page']);
                            $queryString = http_build_query($queryParams);
                            $queryString = $queryString ? '&' . $queryString : '';
                            ?>

                            <?php if ($page > 1): ?>
                                <a href="?page=1<?= $queryString ?>" style="padding: 7px 12px; border: 1px solid var(--border-color); background: #fff; color: #334155; text-decoration: none; border-radius: 6px; font-weight: 500;">&laquo; Đầu</a>
                                <a href="?page=<?= $page - 1 ?><?= $queryString ?>" style="padding: 7px 12px; border: 1px solid var(--border-color); background: #fff; color: #334155; text-decoration: none; border-radius: 6px; font-weight: 500;">Trước</a>
                            <?php endif; ?>

                            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                <a href="?page=<?= $i ?><?= $queryString ?>" style="padding: 7px 12px; border: 1px solid var(--border-color); background: <?= $i == $page ? 'var(--primary-color)' : '#fff' ?>; color: <?= $i == $page ? '#fff' : '#334155' ?>; text-decoration: none; border-radius: 6px; font-weight: <?= $i == $page ? '600' : '500' ?>;">
                                    <?= $i ?>
                                </a>
                            <?php endfor; ?>

                            <?php if ($page < $totalPages): ?>
                                <a href="?page=<?= $page + 1 ?><?= $queryString ?>" style="padding: 7px 12px; border: 1px solid var(--border-color); background: #fff; color: #334155; text-decoration: none; border-radius: 6px; font-weight: 500;">Sau</a>
                                <a href="?page=<?= $totalPages ?><?= $queryString ?>" style="padding: 7px 12px; border: 1px solid var(--border-color); background: #fff; color: #334155; text-decoration: none; border-radius: 6px; font-weight: 500;">Cuối &raquo;</a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

            </div>

        </main>
    </div>
</div>

<script>
// Sidebar Toggle Script
document.querySelectorAll('.menu-toggle').forEach(menu => {
    menu.addEventListener('click', () => {
        const parent = menu.parentElement;
        parent.classList.toggle('open');
    });
});
</script>

</body>
</html>