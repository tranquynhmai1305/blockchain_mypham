<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/Shopbanhang/conn.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/Shopbanhang/func.php';

// 1. Kiểm tra quyền truy cập (Cho phép cả Admin và Nhân viên)
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'nhanvien'])) {
    header("Location: ../../login.php");
    exit;
}

$keyword = '';
if (isset($_GET['keyword'])) {
    $keyword = trim($_GET['keyword']);
}


// 2. Lấy dữ liệu và CHỈ giữ lại các tài khoản có role là 'user' hoặc 'khachhang'
$raw_users = getAllUsers($keyword);
$users = array_filter($raw_users, function($u) {
    $role = strtolower(trim($u['role'] ?? ''));
    return in_array($role, ['user', 'khachhang']);
});

?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý khách hàng - Triple Beauty</title>
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

        .sidebar-header h3 {
            font-size: 18px;
            font-weight: 700;
            color: #ffffff;
        }

        .header-links {
            margin-top: 10px;
            font-size: 12px;
            color: var(--text-muted);
            text-align: center;
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

        /* Bảng dữ liệu */
        .admin-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        .admin-table th, .admin-table td {
            padding: 14px 15px;
            border-bottom: 1px solid var(--border-color);
            font-size: 13px;
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
            text-decoration: none;
        }
        .status.active { background: #d4edda; color: #155724; }
        .status.inactive { background: #f8d7da; color: #721c24; }

        .badge {
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
        }

        .btn-view {
            width: 32px;
            height: 32px;
            border-radius: 6px;
            background: #e0e7ff;
            color: var(--primary-color);
            border: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: 0.2s;
        }

        .btn-view:hover {
            background: var(--primary-color);
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

            <li class="has-submenu open">
                <a href="javascript:void(0);" class="menu-toggle">
                    <div class="menu-left">
                        <i class="fa-solid fa-users"></i>
                        <span>Quản lý khách hàng</span>
                    </div>
                    <i class="fa fa-angle-down arrow"></i>
                </a>
                <ul class="submenu" style="display: block;">
                    <li><a href="/Shopbanhang/admin/khachhang/index.php" style="color: #ffffff;">Danh sách khách hàng</a></li>
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
                    <li><a href="/Shopbanhang/admin/noidung/banner.php">Banner</a></li>
                    <li><a href="/Shopbanhang/admin/noidung/vouchers.php">Vouchers</a></li>
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
            <div class="breadcrumb">Quản lý khách hàng / <strong>Danh sách khách hàng</strong></div>

            <div class="card">
                <div class="search-form-center">
                    <form method="GET" style="display: flex; align-items: center; gap: 10px; width: 100%; max-width: 450px;">
                        <div class="search-input-group">
                            <i class="fa fa-search" style="position: absolute; left: 15px; top: 12px; color: #999;"></i>
                            <input type="text" name="keyword" style="width: 100%; padding: 10px 15px 10px 40px; border-radius: 25px; border: 1px solid #ddd; outline: none;" 
                                   placeholder="Tìm kiếm theo tên hoặc email..." value="<?= htmlspecialchars($keyword) ?>">
                        </div>
                        <?php if($keyword): ?>
                            <a href="index.php" class="btn-refresh" style="padding: 10px 15px; border-radius: 20px; font-size: 13px;">Xóa lọc</a>
                        <?php endif; ?>
                    </form>
                    
                    <a href="index.php" class="btn-refresh" title="Làm mới">
                        <i class="fa fa-sync-alt"></i> Làm mới
                    </a>
                </div>

                <div class="card-body" style="padding: 0 20px 20px 20px; overflow-x: auto;">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Mã KH</th>
                                <th>Họ tên khách hàng</th>
                                <th>Liên hệ (SĐT / Email)</th>
                                <th>Hạng thành viên</th>
                                <th>Tổng chi tiêu</th>
                                <th>Trạng thái</th>
                                <th class="text-center" width="70">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (!empty($users)): ?>
                            <?php foreach ($users as $u): ?>
                            <tr>
                                <td>
                                    <span class="badge" style="background:#f1f5f9; color:#1e293b">
                                        KH<?= str_pad($u['makh'] ?? $u['id'] ?? 0, 3, '0', STR_PAD_LEFT) ?>
                                    </span>
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($u['hoten'] ?? 'Chưa có hồ sơ') ?></strong>
                                </td>
                                <td>
                                    <div><i class="fa fa-phone" style="width:14px; color: #64748b;"></i> <?= htmlspecialchars($u['sdt'] ?? '---') ?></div>
                                    <div style="font-size: 12px; color:#64748b;"><i class="fa fa-envelope" style="width:14px; color: #64748b;"></i> <?= htmlspecialchars($u['email'] ?? '---') ?></div>
                                </td>
                                <td>
                                    <span class="badge" style="background: #fef3c7; color: #d97706;">
                                        <?= htmlspecialchars($u['hang_thanh_vien'] ?? 'Thành viên') ?>
                                    </span>
                                </td>
                                <td style="color:#f43f5e; font-weight:bold; white-space: nowrap;">
                                    <?= number_format($u['tong_chi_tieu'] ?? $u['tong_chi'] ?? 0, 0, ',', '.') ?>đ
                                </td>
                                <td>
                                    <a href="change_status.php?id=<?= $u['makh'] ?? $u['id'] ?? 0 ?>&status=<?= $u['trangthai']==1?0:1 ?>" style="text-decoration: none;">
                                        <span class="status <?= $u['trangthai'] == 1 ? 'active' : 'inactive' ?>">
                                            <?= $u['trangthai'] == 1 ? 'Hoạt động' : 'Bị khóa' ?>
                                        </span>
                                    </a>
                                </td>
                                <td class="text-center">
                                    <button onclick="viewQuickDetail(<?= $u['makh'] ?? $u['id'] ?? 0 ?>)" class="btn-view" title="Xem thông tin chi tiết & lịch sử"><i class="fa fa-eye"></i></button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="7" style="text-align: center; color: #94a3b8; padding: 30px;">Không có tài khoản khách hàng nào.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal xem nhanh lịch sử và thông tin chi tiết đầy đủ -->
<div id="quickViewModal" class="modal" style="display:none; position:fixed; z-index:1001; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.6);">
    <div style="background:#fff; margin:5% auto; padding:25px; width:65%; border-radius:12px; position:relative; max-height:85vh; overflow-y:auto; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);">
        <span onclick="closeModal()" style="position:absolute; right:20px; top:15px; font-size:28px; cursor:pointer; color:#999">&times;</span>
        <div id="quickViewContent">Đang tải thông tin...</div>
    </div>
</div>

<script>
function viewQuickDetail(id) {
    document.getElementById('quickViewModal').style.display = 'block';
    fetch('ajax_customer_history.php?id=' + id)
        .then(res => res.text())
        .then(html => { document.getElementById('quickViewContent').innerHTML = html; });
}
function closeModal() { document.getElementById('quickViewModal').style.display = 'none'; }
window.onclick = function(e) { if(e.target.className == 'modal') closeModal(); }
</script>

<script>
    document.querySelectorAll('.menu-toggle').forEach(menu => {
        menu.addEventListener('click', () => {
            const parent = menu.parentElement;
            parent.classList.toggle('open');
            const submenu = parent.querySelector('.submenu');
            if (submenu) {
                if (parent.classList.contains('open')) {
                    submenu.style.display = 'block';
                } else {
                    submenu.style.display = 'none';
                }
            }
        });
    });
</script>

</body>
</html>