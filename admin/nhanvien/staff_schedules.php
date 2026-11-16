<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/Shopbanhang/conn.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/Shopbanhang/func.php';

// 1. Kiểm tra quyền Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo "<script>
        alert('Tài khoản nhân viên không có quyền truy cập vào mục này!');
        window.location.href = '/Shopbanhang/admin/index.php';
    </script>";
    exit();
}

$user_id = $_SESSION['admin_id'] ?? $_SESSION['user_id'] ?? $_SESSION['id'] ?? 0;

// Lấy ca làm việc hôm nay của Admin (nếu muốn dùng hiển thị banner)
$today = date('Y-m-d');
$sql_my_shift = "SELECT ss.status, s.shift_name, s.start_time, s.end_time 
                 FROM staff_schedules ss
                 JOIN shifts s ON ss.shift_id = s.id
                 WHERE ss.admin_id = ? AND ss.work_date = ?";
$stmt_shift = $conn->prepare($sql_my_shift);
$stmt_shift->bind_param("is", $user_id, $today);
$stmt_shift->execute();
$my_shift = $stmt_shift->get_result()->fetch_assoc();
$stmt_shift->close();

$success_msg = $error_msg = '';

// Bắt thông báo từ URL
if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'deleted') $success_msg = 'Đã xóa lịch phân ca!';
    if ($_GET['msg'] === 'added') $success_msg = 'Phân ca mới thành công!';
}

// 2. Xử lý Thêm phân ca mới từ Modal (Có kiểm tra trùng lịch)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] == 'add_schedule') {
    $admin_id = $_POST['admin_id'] ?? '';
    $shift_id = $_POST['shift_id'] ?? '';
    $work_date = $_POST['work_date'] ?? '';

    if (empty($admin_id) || empty($shift_id) || empty($work_date)) {
        $error_msg = 'Vui lòng chọn đầy đủ thông tin nhân viên, ca trực và ngày làm việc!';
    } else {
        // Kiểm tra xem nhân viên đã có ca này trong ngày này chưa
        $chkStmt = $conn->prepare("SELECT id FROM staff_schedules WHERE admin_id = ? AND shift_id = ? AND work_date = ?");
        $chkStmt->bind_param("iis", $admin_id, $shift_id, $work_date);
        $chkStmt->execute();
        $chkRes = $chkStmt->get_result();

        if ($chkRes->num_rows > 0) {
            $error_msg = 'Nhân viên này đã được phân ca trực này trong ngày chọn!';
        } else {
            $stmt = $conn->prepare("INSERT INTO staff_schedules (admin_id, shift_id, work_date, status) VALUES (?, ?, ?, 'ChuaLam')");
            $stmt->bind_param("iis", $admin_id, $shift_id, $work_date);
            if ($stmt->execute()) {
                header("Location: staff_schedules.php?msg=added");
                exit();
            } else {
                $error_msg = 'Lỗi hệ thống khi phân ca.';
            }
            $stmt->close();
        }
        $chkStmt->close();
    }
}

// 3. Xử lý xóa lịch phân ca
if (isset($_GET['delete'])) {
    $del_id = (int)$_GET['delete'];
    $stmtDel = $conn->prepare("DELETE FROM staff_schedules WHERE id = ?");
    $stmtDel->bind_param("i", $del_id);
    $stmtDel->execute();
    $stmtDel->close();
    header("Location: staff_schedules.php?msg=deleted");
    exit;
}

// Lấy danh sách Nhân viên & Ca làm việc
$resultAdmins = $conn->query("SELECT id, username FROM users WHERE role IN ('admin', 'staff') ORDER BY username ASC");
$listAdmins = $resultAdmins->fetch_all(MYSQLI_ASSOC);

$resultShifts = $conn->query("SELECT id, shift_name, start_time, end_time FROM shifts ORDER BY start_time ASC");
$listShifts = $resultShifts->fetch_all(MYSQLI_ASSOC);

// Bộ lọc danh sách
$filter_admin = $_GET['admin_id'] ?? '';
$filter_date = $_GET['work_date'] ?? '';
$filter_status = $_GET['status'] ?? '';

$whereClauses = [];
$params = [];
$types = "";

if (!empty($filter_admin)) {
    $whereClauses[] = "s.admin_id = ?";
    $params[] = $filter_admin;
    $types .= "i";
}
if (!empty($filter_date)) {
    $whereClauses[] = "s.work_date = ?";
    $params[] = $filter_date;
    $types .= "s";
}
if (!empty($filter_status)) {
    $whereClauses[] = "s.status = ?";
    $params[] = $filter_status;
    $types .= "s";
}

$sqlWhere = "";
if (count($whereClauses) > 0) {
    $sqlWhere = " WHERE " . implode(" AND ", $whereClauses);
}

$sqlSchedules = "SELECT s.*, u.username, sh.shift_name, sh.start_time, sh.end_time 
                 FROM staff_schedules s 
                 JOIN users u ON s.admin_id = u.id 
                 JOIN shifts sh ON s.shift_id = sh.id" 
                 . $sqlWhere . " 
                 ORDER BY s.work_date DESC, sh.start_time ASC";

$stmtSch = $conn->prepare($sqlSchedules);
if (!empty($params)) {
    $stmtSch->bind_param($types, ...$params);
}
$stmtSch->execute();
$schedules = $stmtSch->get_result()->fetch_all(MYSQLI_ASSOC);
$stmtSch->close();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý lịch phân ca trực & Chấm công - Triple Beauty</title>

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
        --success-color: #16a34a;
        --danger-color: #ef4444;
        --info-color: #2563eb;
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

    a {
        text-decoration: none;
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
        color: #fff;
    }

    .header-links {
        margin-top: 6px;
        font-size: 12px;
        color: var(--text-muted);
    }

    .header-links a {
        color: var(--text-muted);
    }

    .header-links a:hover {
        color: #fff;
    }

    .header-links .divider {
        margin: 0 6px;
        opacity: .4;
    }

    .menu-label {
        padding: 20px 20px 8px;
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
        border-radius: 8px;
        font-size: 14px;
        font-weight: 500;
        transition: all .2s;
    }

    #sidebar ul li a i {
        font-size: 16px;
        width: 20px;
        text-align: center;
        color: #94a3b8;
    }

    #sidebar ul li a:hover {
        background: var(--sidebar-hover);
        color: #fff;
    }

    #sidebar ul li.active > a,
    #sidebar ul li a.active {
        background: var(--primary-color);
        color: #fff;
    }

    #sidebar ul li.active > a i,
    #sidebar ul li a.active i {
        color: #fff;
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
        transition: transform .3s;
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

    .submenu li a.active {
        color: #fff;
        background: #1e293b;
    }

    .has-submenu.open .submenu {
        display: block;
    }

    /* CONTENT + TOP NAVBAR */
    #content {
        flex: 1;
        margin-left: 260px;
        display: flex;
        flex-direction: column;
        min-height: 100vh;
    }

    .top-navbar {
        background: #fff;
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
        transition: all .2s;
    }

    .btn-logout:hover {
        background: #ef4444;
        color: #fff;
    }

    /* MAIN */
    .dashboard-container {
        padding: 20px 30px;
        max-width: 1500px;
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

    /* ALERTS */
    .alert {
        padding: 13px 16px;
        border-radius: 10px;
        margin-bottom: 15px;
        font-size: 13px;
        border: 1px solid transparent;
        display: flex;
        align-items: center;
        gap: 9px;
    }

    .alert-info {
        background: #eff6ff;
        color: #1d4ed8;
        border-color: #bfdbfe;
    }

    .alert-success {
        background: #f0fdf4;
        color: #166534;
        border-color: #bbf7d0;
    }

    .alert-danger {
        background: #fef2f2;
        color: #991b1b;
        border-color: #fecaca;
    }

    /* CARD */
    .schedule-card {
        background: #fff;
        border-radius: 12px;
        border: 1px solid var(--border-color);
        padding: 20px;
        box-shadow: 0 4px 20px rgba(0,0,0,.02);
    }

    .toolbar {
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        flex-wrap: wrap;
        gap: 15px;
        margin-bottom: 20px;
    }

    .filter-form {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        align-items: flex-end;
    }

    .form-group {
        margin-bottom: 0;
    }

    .form-group label {
        display: block;
        font-size: 12px;
        font-weight: 600;
        color: #475569;
        margin-bottom: 5px;
    }

    .form-control,
    .filter-form select,
    .filter-form input[type="date"] {
        padding: 9px 11px;
        border: 1px solid var(--border-color);
        border-radius: 8px;
        min-width: 150px;
        font-family: inherit;
        font-size: 13px;
        background: #fff;
        color: var(--text-dark);
        outline: none;
        transition: border-color .2s, box-shadow .2s;
    }

    .filter-form select:focus,
    .filter-form input[type="date"]:focus,
    .form-control:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(99,102,241,.1);
    }

    .filter-form input[type="date"] {
        min-width: 145px;
    }

    .btn {
        border: none;
        border-radius: 8px;
        padding: 9px 14px;
        font-family: inherit;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        transition: all .2s;
    }

    .btn-primary {
        background: var(--primary-color);
        color: #fff;
    }

    .btn-primary:hover {
        opacity: .9;
        transform: translateY(-1px);
    }

    .btn-secondary {
        background: #64748b;
        color: #fff;
    }

    .btn-secondary:hover {
        background: #475569;
    }

    .btn-success {
        background: var(--success-color);
        color: #fff;
    }

    .btn-success:hover {
        background: #15803d;
        transform: translateY(-1px);
    }

    .btn-danger {
        color: var(--danger-color);
        background: #fef2f2;
    }

    .btn-danger:hover {
        background: #fee2e2;
    }

    /* TABLE */
    .table-wrapper {
        overflow-x: auto;
        border: 1px solid var(--border-color);
        border-radius: 10px;
    }

    .schedule-table {
        width: 100%;
        min-width: 900px;
        border-collapse: collapse;
        text-align: left;
        font-size: 13px;
    }

    .schedule-table thead tr {
        background: #f8fafc;
        border-bottom: 1px solid var(--border-color);
    }

    .schedule-table th {
        padding: 13px 14px;
        color: #475569;
        font-size: 12px;
        font-weight: 700;
        white-space: nowrap;
    }

    .schedule-table td {
        padding: 13px 14px;
        border-bottom: 1px solid #f1f5f9;
        vertical-align: middle;
    }

    .schedule-table tbody tr {
        transition: background .2s;
    }

    .schedule-table tbody tr:hover {
        background: #fafbff;
    }

    .schedule-table tbody tr:last-child td {
        border-bottom: none;
    }

    .employee-name {
        font-weight: 700;
        color: var(--primary-color);
    }

    .date-text {
        font-weight: 700;
        color: #334155;
    }

    .time-text {
        color: #64748b;
    }

    .status-badge {
        display: inline-flex;
        align-items: center;
        padding: 6px 10px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 700;
        white-space: nowrap;
    }

    .status-ChuaLam {
        background: #f1f5f9;
        color: #475569;
    }

    .status-DangLam {
        background: #e0f2fe;
        color: #0369a1;
    }

    .status-HoanThanh {
        background: #dcfce7;
        color: #166534;
    }

    .status-Vang {
        background: #fee2e2;
        color: #991b1b;
    }

    .action-cell {
        text-align: center;
        white-space: nowrap;
    }

    .delete-link {
        display: inline-flex;
        width: 32px;
        height: 32px;
        align-items: center;
        justify-content: center;
        margin-left: 6px;
        border-radius: 7px;
        color: var(--danger-color);
        background: #fef2f2;
        transition: all .2s;
    }

    .delete-link:hover {
        background: var(--danger-color);
        color: #fff;
    }

    .empty-state {
        text-align: center;
        padding: 45px 20px !important;
        color: #94a3b8;
    }

    .empty-state i {
        font-size: 30px;
        margin-bottom: 10px;
        display: block;
    }

    /* MODAL */
    .modal-overlay {
        display: none;
        position: fixed;
        z-index: 1100;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background: rgba(15,23,42,.65);
        align-items: center;
        justify-content: center;
        padding: 20px;
        backdrop-filter: blur(3px);
    }

    .modal-overlay.show {
        display: flex;
        animation: fadeIn .2s ease;
    }

    .modal-box {
        background: #fff;
        width: 100%;
        max-width: 480px;
        padding: 25px;
        border-radius: 14px;
        box-shadow: 0 15px 50px rgba(0,0,0,.18);
        position: relative;
        animation: modalIn .2s ease;
    }

    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid var(--border-color);
        padding-bottom: 14px;
        margin-bottom: 20px;
    }

    .modal-header h3 {
        font-size: 18px;
        font-weight: 700;
        color: var(--text-dark);
    }

    .modal-close {
        width: 32px;
        height: 32px;
        border: none;
        border-radius: 8px;
        background: #f1f5f9;
        color: #64748b;
        cursor: pointer;
        transition: all .2s;
    }

    .modal-close:hover {
        background: #fee2e2;
        color: var(--danger-color);
    }

    .modal-form-group {
        margin-bottom: 16px;
    }

    .modal-form-group label {
        display: block;
        margin-bottom: 6px;
        font-size: 13px;
        font-weight: 600;
        color: #475569;
    }

    .modal-form-group select,
    .modal-form-group input[type="date"] {
        width: 100%;
        padding: 10px 11px;
        border: 1px solid var(--border-color);
        border-radius: 8px;
        font-family: inherit;
        font-size: 13px;
        background: #fff;
        outline: none;
    }

    .modal-form-group select:focus,
    .modal-form-group input[type="date"]:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(99,102,241,.1);
    }

    .modal-actions {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        margin-top: 22px;
    }

    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    @keyframes modalIn {
        from { opacity: 0; transform: translateY(-10px) scale(.98); }
        to { opacity: 1; transform: translateY(0) scale(1); }
    }

    /* RESPONSIVE */
    @media (max-width: 1100px) {
        .nav-left-info {
            gap: 12px;
        }

        .dashboard-container {
            padding: 20px;
        }
    }

    @media (max-width: 800px) {
        #sidebar {
            width: 220px;
            min-width: 220px;
        }

        #content {
            margin-left: 220px;
        }

        .top-navbar {
            padding: 12px 18px;
        }

        .nav-left-info {
            display: none;
        }

        .toolbar {
            align-items: stretch;
        }

        .filter-form {
            width: 100%;
        }

        .filter-form .form-group,
        .filter-form select,
        .filter-form input[type="date"] {
            flex: 1;
            min-width: 140px;
        }

        .toolbar > div:last-child {
            width: 100%;
        }

        .toolbar > div:last-child .btn {
            width: 100%;
        }
    }

    @media (max-width: 600px) {
        #sidebar {
            position: relative;
            width: 100%;
            min-width: 100%;
            height: auto;
            max-height: none;
        }

        #content {
            margin-left: 0;
        }

        .wrapper {
            flex-direction: column;
        }

        .dashboard-container {
            padding: 15px;
        }

        .top-navbar {
            position: relative;
        }

        .filter-form {
            flex-direction: column;
            align-items: stretch;
        }

        .filter-form .form-group,
        .filter-form select,
        .filter-form input[type="date"] {
            width: 100%;
        }

        .filter-form .btn {
            width: 100%;
        }

        .filter-form > div:last-child {
            display: flex;
            gap: 8px;
        }

        .filter-form > div:last-child .btn,
        .filter-form > div:last-child a {
            flex: 1;
            margin-left: 0 !important;
            text-align: center;
        }

        .schedule-card {
            padding: 12px;
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

        <!-- TOP NAVBAR -->
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
                    <span class="user-name"><?= htmlspecialchars($_SESSION['admin_name'] ?? $_SESSION['user_name'] ?? 'Admin') ?></span>
                </div>

                <a href="../logout.php" class="btn-logout" title="Đăng xuất">
                    <i class="fa fa-sign-out-alt"></i>
                </a>
            </div>
        </header>

        <!-- MAIN BODY -->
        <main class="dashboard-container">

            <div class="breadcrumb">
                Trang chủ &gt; Quản lý Nhân sự &gt; Lịch phân ca trực & Chấm công
            </div>

            <h2>
                <i class="fa-solid fa-calendar-days" style="color: var(--accent-color);"></i>
                QUẢN LÝ LỊCH PHÂN CA TRỰC & CHẤM CÔNG
            </h2>

            <p class="page-desc">
                Theo dõi, phân ca và cập nhật trạng thái chấm công của nhân viên.
            </p>

            <?php if ($my_shift): ?>
                <div class="alert alert-info">
                    <i class="fa-solid fa-circle-info"></i>
                    <span>
                        Hôm nay bạn có lịch trực:
                        <strong><?= htmlspecialchars($my_shift['shift_name']) ?></strong>
                        (<?= htmlspecialchars($my_shift['start_time']) ?> - <?= htmlspecialchars($my_shift['end_time']) ?>)
                    </span>
                </div>
            <?php endif; ?>

            <?php if (!empty($success_msg)): ?>
                <div class="alert alert-success" id="successAlert">
                    <i class="fa-solid fa-circle-check"></i>
                    <span><?= htmlspecialchars($success_msg) ?></span>
                </div>
            <?php endif; ?>

            <?php if (!empty($error_msg)): ?>
                <div class="alert alert-danger" id="errorAlert">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <span><?= htmlspecialchars($error_msg) ?></span>
                </div>
            <?php endif; ?>

            <div class="schedule-card">

                <!-- TOOLBAR + FILTER -->
                <div class="toolbar">

                    <form method="GET" action="" class="filter-form">

                        <div class="form-group">
                            <label for="admin_id">Nhân viên</label>
                            <select name="admin_id" id="admin_id">
                                <option value="">-- Tất cả nhân viên --</option>
                                <?php foreach ($listAdmins as $adm): ?>
                                    <option value="<?= (int)$adm['id'] ?>" <?= $filter_admin == $adm['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($adm['username']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="work_date">Ngày làm</label>
                            <input type="date"
                                   name="work_date"
                                   id="work_date"
                                   value="<?= htmlspecialchars($filter_date) ?>">
                        </div>

                        <div class="form-group">
                            <label for="status">Trạng thái</label>
                            <select name="status" id="status">
                                <option value="">-- Tất cả trạng thái --</option>
                                <option value="ChuaLam" <?= $filter_status == 'ChuaLam' ? 'selected' : '' ?>>Chưa làm</option>
                                <option value="DangLam" <?= $filter_status == 'DangLam' ? 'selected' : '' ?>>Đang làm</option>
                                <option value="HoanThanh" <?= $filter_status == 'HoanThanh' ? 'selected' : '' ?>>Hoàn thành</option>
                                <option value="Vang" <?= $filter_status == 'Vang' ? 'selected' : '' ?>>Vắng mặt</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-filter"></i>
                                Lọc
                            </button>

                            <a href="staff_schedules.php" class="btn btn-secondary" style="margin-left: 5px;">
                                <i class="fa fa-rotate-left"></i>
                                Đặt lại
                            </a>
                        </div>

                    </form>

                    <div>
                        <button type="button" class="btn btn-success" onclick="openModal()">
                            <i class="fa fa-plus"></i>
                            Phân ca mới
                        </button>
                    </div>

                </div>

                <!-- TABLE -->
                <div class="table-wrapper">
                    <table class="schedule-table">
                        <thead>
                            <tr>
                                <th>STT</th>
                                <th>Ngày làm việc</th>
                                <th>Nhân viên</th>
                                <th>Ca trực</th>
                                <th>Khung giờ</th>
                                <th>Trạng thái chấm công</th>
                                <th style="text-align:center;">Hành động</th>
                            </tr>
                        </thead>

                        <tbody>
                        <?php if (count($schedules) > 0): ?>

                            <?php $stt = 1; ?>

                            <?php foreach ($schedules as $sc): ?>

                                <?php
                                    $statusText = 'Chưa làm';
                                    $statusClass = 'status-ChuaLam';

                                    if ($sc['status'] === 'DangLam') {
                                        $statusText = 'Đang làm';
                                        $statusClass = 'status-DangLam';
                                    } elseif ($sc['status'] === 'HoanThanh') {
                                        $statusText = 'Hoàn thành';
                                        $statusClass = 'status-HoanThanh';
                                    } elseif ($sc['status'] === 'Vang') {
                                        $statusText = 'Vắng mặt';
                                        $statusClass = 'status-Vang';
                                    }
                                ?>

                                <tr>
                                    <td><?= $stt++ ?></td>

                                    <td class="date-text">
                                        <?= date('d/m/Y', strtotime($sc['work_date'])) ?>
                                    </td>

                                    <td class="employee-name">
                                        <?= htmlspecialchars($sc['username']) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($sc['shift_name']) ?>
                                    </td>

                                    <td class="time-text">
                                        <?= htmlspecialchars($sc['start_time']) ?>
                                        -
                                        <?= htmlspecialchars($sc['end_time']) ?>
                                    </td>

                                    <td>
                                        <span class="status-badge <?= $statusClass ?>">
                                            <?= $statusText ?>
                                        </span>
                                    </td>

                                    <td class="action-cell">
                                        <a href="staff_schedules.php?delete=<?= (int)$sc['id'] ?>"
                                           class="delete-link"
                                           onclick="return confirmDelete();"
                                           title="Xóa lịch">
                                            <i class="fa fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>

                            <?php endforeach; ?>

                        <?php else: ?>

                            <tr>
                                <td colspan="7" class="empty-state">
                                    <i class="fa-regular fa-calendar-xmark"></i>
                                    Không có lịch phân ca nào phù hợp.
                                </td>
                            </tr>

                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            </div>

        </main>
    </div>
</div>

<!-- MODAL PHÂN CA MỚI -->
<div id="scheduleModal" class="modal-overlay" onclick="closeModalOnOverlay(event)">
    <div class="modal-box">

        <div class="modal-header">
            <h3>
                <i class="fa fa-calendar-plus" style="color: var(--accent-color); margin-right: 7px;"></i>
                Phân ca trực cho nhân viên
            </h3>

            <button type="button" class="modal-close" onclick="closeModal()" title="Đóng">
                <i class="fa fa-times"></i>
            </button>
        </div>

        <form method="POST" action="">
            <input type="hidden" name="action" value="add_schedule">

            <div class="modal-form-group">
                <label for="modal_admin_id">Chọn nhân viên (*)</label>

                <select name="admin_id" id="modal_admin_id" required>
                    <option value="">-- Chọn nhân viên --</option>

                    <?php foreach ($listAdmins as $adm): ?>
                        <option value="<?= (int)$adm['id'] ?>">
                            <?= htmlspecialchars($adm['username']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="modal-form-group">
                <label for="modal_shift_id">Chọn ca làm việc (*)</label>

                <select name="shift_id" id="modal_shift_id" required>
                    <option value="">-- Chọn ca trực --</option>

                    <?php foreach ($listShifts as $sh): ?>
                        <option value="<?= (int)$sh['id'] ?>">
                            <?= htmlspecialchars($sh['shift_name']) ?>
                            (<?= htmlspecialchars($sh['start_time']) ?> - <?= htmlspecialchars($sh['end_time']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="modal-form-group">
                <label for="modal_work_date">Ngày làm việc (*)</label>

                <input type="date"
                       name="work_date"
                       id="modal_work_date"
                       value="<?= date('Y-m-d') ?>"
                       required>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">
                    <i class="fa fa-times"></i>
                    Hủy
                </button>

                <button type="submit" class="btn btn-success">
                    <i class="fa fa-save"></i>
                    Lưu phân ca
                </button>
            </div>
        </form>

    </div>
</div>

<script>
    // ==============================
    // SIDEBAR MENU
    // ==============================
    document.querySelectorAll('.menu-toggle').forEach(menu => {
        menu.addEventListener('click', function () {
            this.parentElement.classList.toggle('open');
        });
    });

    // ==============================
    // MODAL PHÂN CA
    // ==============================
    function openModal() {
        const modal = document.getElementById('scheduleModal');

        if (modal) {
            modal.classList.add('show');
            document.body.style.overflow = 'hidden';

            const employeeSelect = document.getElementById('modal_admin_id');

            if (employeeSelect) {
                setTimeout(() => employeeSelect.focus(), 100);
            }
        }
    }

    function closeModal() {
        const modal = document.getElementById('scheduleModal');

        if (modal) {
            modal.classList.remove('show');
            document.body.style.overflow = '';
        }
    }

    function closeModalOnOverlay(event) {
        if (event.target.id === 'scheduleModal') {
            closeModal();
        }
    }

    // Đóng modal bằng phím ESC
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeModal();
        }
    });

    // ==============================
    // XÁC NHẬN XÓA
    // ==============================
    function confirmDelete() {
        return confirm(
            'Bạn có chắc chắn muốn xóa lịch trực này?\n\n' +
            'Hành động này không thể hoàn tác.'
        );
    }

    // ==============================
    // TỰ ẨN THÔNG BÁO
    // ==============================
    document.addEventListener('DOMContentLoaded', function () {
        const alerts = document.querySelectorAll(
            '#successAlert, #errorAlert'
        );

        alerts.forEach(function (alert) {
            setTimeout(function () {
                alert.style.transition = 'opacity .3s ease, transform .3s ease';
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-5px)';

                setTimeout(function () {
                    alert.remove();
                }, 300);
            }, 4000);
        });
    });
</script>

</body>
</html>