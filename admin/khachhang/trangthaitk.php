<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/Shopbanhang/func.php';
$conn = mysqli_connect("localhost", "root", "", "qlybanhang");
mysqli_set_charset($conn, "utf8mb4");

if ($_SESSION['role'] !== 'admin') {
    echo "<script>
        alert('Tài khoản nhân viên không có quyền truy cập vào mục này!');
        window.location.href = '/Shopbanhang/admin/index.php';
    </script>";
    exit();
}

$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';

// Truy vấn lấy trực tiếp cột password từ bảng users[cite: 1]
$sql = "SELECT kh.*, u.password, u.trangthai 
        FROM khachhang kh
        LEFT JOIN users u ON kh.id_users = u.id"; 

if (!empty($keyword)) {
    $sql .= " WHERE kh.hoten LIKE '%$keyword%' 
              OR kh.email LIKE '%$keyword%' 
              OR kh.sdt LIKE '%$keyword%' 
              OR kh.diachi LIKE '%$keyword%'";
}

$sql .= " ORDER BY kh.ngaydky DESC";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý trạng thái tài khoản - Triple Beauty</title>
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
    text-align: center; /* Căn giữa toàn bộ nội dung bên trong */
}

.logo-box {
    display: flex;
    align-items: center;
    justify-content: center; /* Căn giữa icon và chữ Triple Beauty theo chiều ngang */
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
    text-align: center; /* Căn giữa phần Website | Xóa Cache */
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

        .main-body {
            padding: 30px;
        }

        .breadcrumb {
            font-size: 14px;
            color: #64748b;
            margin-bottom: 20px;
        }

        .card {
            background: #ffffff;
            border-radius: 16px;
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow-sm);
            overflow: hidden;
        }

        .search-form-center {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 15px;
            padding: 20px;
        }

        .search-input-group {
            position: relative;
            width: 100%;
            max-width: 400px;
        }

        .btn-refresh {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 10px 20px;
            background-color: #f8f9fa;
            color: #5f6368;
            border: 1px solid #dadce0;
            border-radius: 25px;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s;
            white-space: nowrap;
        }

        .btn-refresh:hover {
            background-color: #eeeeee;
            color: #202124;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .btn-refresh i {
            margin-right: 5px;
        }
        
        /* Style cho mật khẩu */
        .pwd-box {
            font-size: 12px; 
            color: #e83e8c; 
            background: #f8f9fa; 
            padding: 4px 8px; 
            border-radius: 4px;
            border: 1px solid #eee;
            font-family: monospace;
        }

        /* Bảng dữ liệu */
        .admin-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        .admin-table th, .admin-table td {
            padding: 14px 20px;
            border-bottom: 1px solid var(--border-color);
            font-size: 14px;
        }

        .admin-table th {
            background: #f8fafc;
            font-weight: 700;
            color: #475569;
        }

        .admin-table tbody tr:hover {
            background: #f1f5f9;
        }

        /* Trạng thái */
        .status {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .status.active { background: #d4edda; color: #155724; }
        .status.inactive { background: #f8d7da; color: #721c24; }

        /* Nút hành động */
        .btn-action-status {
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 13px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: 0.3s;
        }
        .btn-lock { background: #ffeaa7; color: #d35400; }
        .btn-lock:hover { background: #f1c40f; color: #fff; }
        .btn-unlock { background: #dff9fb; color: #0984e3; }
        .btn-unlock:hover { background: #74b9ff; color: #fff; }
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
                    <span class="user-name"><?= htmlspecialchars($_SESSION['admin_name'] ?? $_SESSION['user_name'] ?? 'Nhân viên') ?></span>
                </div>
                <a href="../logout.php" class="btn-logout" title="Đăng xuất">
                    <i class="fa-solid fa-arrow-right-from-bracket"></i>
                </a>
            </div>
        </header>

        <div class="main-body">
            <div class="breadcrumb">Quản lý khách hàng / <strong>Trạng thái tài khoản</strong></div>

            <div class="card">
                <div class="search-form-center">
                    <form method="GET" style="display: flex; width: 100%; max-width: 400px;">
                        <div class="search-input-group">
                            <i class="fa fa-search" style="position: absolute; left: 15px; top: 12px; color: #999;"></i>
                            <input type="text" name="keyword" style="width: 100%; padding: 10px 15px 10px 40px; border-radius: 25px; border: 1px solid #ddd; outline: none;" 
                                   placeholder="Tìm kiếm theo tên, email, sđt..." value="<?= htmlspecialchars($keyword) ?>">
                        </div>
                    </form>
                    
                    <a href="trangthaitk.php" class="btn-refresh" title="Làm mới">
                        <i class="fa fa-sync-alt"></i> Làm mới
                    </a>
                </div>

                <div class="card-body" style="padding: 0 20px 20px 20px;">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Mã KH</th>
                                <th>Khách hàng</th>
                                <th>Mật khẩu</th>
                                <th>Liên hệ & Địa chỉ</th>
                                <th>Ngày đăng ký</th>
                                <th>Trạng thái</th>
                                <th class="text-center">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td><strong>#<?= htmlspecialchars($row['makh'] ?? '') ?></strong></td>
                                <td>
                                    <div style="font-weight: 600; color: #2c3e50;"><?= htmlspecialchars($row['hoten']) ?></div>
                                    <div style="font-size: 11px; color: #7f8c8d;">Email: <?= htmlspecialchars($row['email']) ?></div>
                                </td>
                                <td>
                                    <?php if (!empty($row['password'])): ?>
                                        <code class="pwd-box" title="<?= htmlspecialchars($row['password']) ?>">
                                            <?= htmlspecialchars($row['password']) ?>
                                        </code>
                                    <?php else: ?>
                                        <span style="color: #ccc; font-style: italic; font-size: 11px;">N/A</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="font-size: 13px;"><i class="fa fa-phone fa-fw"></i> <?= htmlspecialchars($row['sdt']) ?></div>
                                    <div style="font-size: 13px; color: #57606f;"><i class="fa fa-location-dot fa-fw"></i> <?= htmlspecialchars($row['diachi']) ?></div>
                                </td>
                                <td>
                                    <div style="font-size: 13px;"><?= date('H:i', strtotime($row['ngaydky'])) ?></div>
                                    <div style="font-weight: 600;"><?= date('d/m/Y', strtotime($row['ngaydky'])) ?></div>
                                </td>
                                <td>
                                    <?php if ($row['trangthai'] == 1): ?>
                                        <span class="status active"><i class="fa fa-check"></i> Hoạt động</span>
                                    <?php else: ?>
                                        <span class="status inactive"><i class="fa fa-lock"></i> Đã khóa</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($row['trangthai'] == 1): ?>
                                        <a href="handle_status.php?id=<?= $row['id_users'] ?>&set=0" class="btn-action-status btn-lock" onclick="return confirm('Bạn có chắc muốn KHÓA tài khoản này?')">
                                            <i class="fa fa-user-slash"></i> Khóa
                                        </a>
                                    <?php else: ?>
                                        <a href="handle_status.php?id=<?= $row['id_users'] ?>&set=1" class="btn-action-status btn-unlock" onclick="return confirm('Bạn có chắc muốn MỞ KHÓA tài khoản này?')">
                                            <i class="fa fa-user-check"></i> Mở
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.querySelectorAll('.menu-toggle').forEach(menu => {
        menu.addEventListener('click', () => {
            const parent = menu.parentElement;
            parent.classList.toggle('open');
        });
    });
</script>
</body>
</html>