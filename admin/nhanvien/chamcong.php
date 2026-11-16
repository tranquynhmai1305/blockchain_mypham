<?php
session_start();
date_default_timezone_set('Asia/Ho_Chi_Minh');

require_once $_SERVER['DOCUMENT_ROOT'] . '/Shopbanhang/conn.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/Shopbanhang/func.php';

// 1. KIỂM TRA QUYỀN TRUY CẬP
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'staff', 'nhanvien'])) {
    header("Location: /Shopbanhang/login.php");
    exit();
}

// 2. LẤY MÃ USER DỰA TRÊN SESSION
$user_id   = $_SESSION['user_id'] ?? $_SESSION['admin_id'] ?? $_SESSION['id'] ?? 0;
$role_name = $_SESSION['role'];

if ($user_id <= 0) {
    session_destroy();
    header("Location: /Shopbanhang/login.php");
    exit();
}

// Tạo CSRF Token nếu chưa có
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// 3. LẤY THÔNG TIN TÀI KHOẢN ĐỂ HIỂN THỊ TRÊN TOPBAR
$sql_user = "SELECT u.username, u.role, nv.hoten 
             FROM users u 
             LEFT JOIN nhanvien nv ON u.id = nv.id_users 
             WHERE u.id = ?";
$stmt_u = $conn->prepare($sql_user);
$stmt_u->bind_param("i", $user_id);
$stmt_u->execute();
$user_data = $stmt_u->get_result()->fetch_assoc();
$stmt_u->close();

$display_name = !empty($user_data['hoten']) ? $user_data['hoten'] : ($user_data['username'] ?? 'NhanVien');

// 4. KIỂM TRA TRẠNG THÁI CHẤM CÔNG HÔM NAY (LẤY LẦN GẦN NHẤT)
$today = date('Y-m-d');
$sql_check = "SELECT * FROM cham_cong WHERE user_id = ? AND ngay_lam = ? ORDER BY id DESC LIMIT 1";
$stmt_c = $conn->prepare($sql_check);
$stmt_c->bind_param("is", $user_id, $today);
$stmt_c->execute();
$today_attendance = $stmt_c->get_result()->fetch_assoc();
$stmt_c->close();

// 5. LẤY LỊCH SỬ CHẤM CÔNG CỦA NHÂN VIÊN (10 LẦN GẦN NHẤT)
$sql_history = "SELECT * FROM cham_cong WHERE user_id = ? ORDER BY ngay_lam DESC, id DESC LIMIT 10";
$stmt_h = $conn->prepare($sql_history);
$stmt_h->bind_param("i", $user_id);
$stmt_h->execute();
$history_result = $stmt_h->get_result();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chấm công & Phân ca trực | Triple Beauty</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet"><style>
:root{--bg-main:#f8fafc;--sidebar-bg:#0f172a;--sidebar-hover:#1e293b;--text-muted:#94a3b8;--text-dark:#1e293b;--border-color:#e2e8f0;--primary-color:#6366f1;--accent-color:#a31d37;--success:#16a34a;--danger:#ef4444}
*{box-sizing:border-box;margin:0;padding:0}body{font-family:'Plus Jakarta Sans',sans-serif;background:var(--bg-main);color:var(--text-dark);line-height:1.45}a{text-decoration:none}.wrapper{display:flex;min-height:100vh}#sidebar{width:260px;min-width:260px;background:var(--sidebar-bg);color:#fff;height:100vh;position:fixed;left:0;top:0;overflow-y:auto;z-index:1000}.sidebar-header{padding:24px 20px;background:#090d16;border-bottom:1px solid #1e293b;text-align:center}.sidebar-header h3{font-size:18px}.sidebar-header p{margin-top:6px;font-size:12px;color:var(--text-muted)}.sidebar-header a{color:var(--text-muted)}.menu-label{padding:20px 20px 8px;font-size:11px;font-weight:700;color:#64748b;letter-spacing:1px}#sidebar ul.components{padding:10px;list-style:none}#sidebar ul li{margin-bottom:4px}#sidebar ul li a{padding:12px 16px;display:flex;align-items:center;gap:12px;color:#cbd5e1;border-radius:8px;font-size:14px;font-weight:500;transition:.2s}#sidebar ul li a i{width:20px;text-align:center;color:#94a3b8}#sidebar ul li a:hover{background:var(--sidebar-hover);color:#fff}#sidebar ul li a.active{background:var(--primary-color);color:#fff}.has-submenu>a{justify-content:space-between}.menu-left{display:flex;align-items:center;gap:12px}.arrow{transition:.3s}.has-submenu.open .arrow{transform:rotate(180deg)}.submenu{list-style:none;padding:4px 0 4px 12px;display:none}.has-submenu.open .submenu{display:block}.submenu li a{padding:9px 16px!important;font-size:13px!important;color:#94a3b8!important}.submenu li a.active{background:#1e293b!important;color:#fff!important}#content{flex:1;margin-left:260px;min-height:100vh}.top-navbar{background:#fff;padding:14px 30px;display:flex;justify-content:flex-end;border-bottom:1px solid var(--border-color);box-shadow:0 1px 3px rgba(0,0,0,.05);position:sticky;top:0;z-index:999}.nav-info{display:flex;gap:22px;align-items:center;flex-wrap:wrap;font-size:13px;color:#64748b}.nav-info i{color:var(--primary-color);margin-right:5px}.nav-info a{color:var(--danger);font-weight:600}.main-body{padding:24px 30px;max-width:1500px;margin:auto}.breadcrumb{font-size:13px;color:#64748b;margin-bottom:8px}h2{font-size:21px;margin-bottom:18px;display:flex;align-items:center;gap:10px}.content-card,.card{background:#fff;border:1px solid var(--border-color);border-radius:12px;box-shadow:0 4px 20px rgba(0,0,0,.025)}.card-header{padding:16px 20px;border-bottom:1px solid var(--border-color)}.card-body{padding:20px}.btn{border:0;border-radius:8px;padding:9px 14px;font-family:inherit;font-size:13px;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:6px}.btn-primary{background:var(--primary-color);color:#fff}.btn-secondary{background:#64748b;color:#fff}.btn-success{background:var(--success);color:#fff}.form-group{margin-bottom:16px}.form-group label{display:block;margin-bottom:6px;font-size:12px;font-weight:600;color:#475569}.form-group input,.form-group textarea,.form-group select{width:100%;padding:10px 11px;border:1px solid var(--border-color);border-radius:8px;font:inherit;font-size:13px;outline:none}.form-group input:focus,.form-group textarea:focus{border-color:var(--primary-color);box-shadow:0 0 0 3px rgba(99,102,241,.1)}.table-wrap{overflow-x:auto;border:1px solid var(--border-color);border-radius:10px}.modern-table{width:100%;border-collapse:collapse;min-width:700px;font-size:13px}.modern-table th{padding:13px 14px;background:#f8fafc;color:#475569;text-align:left;font-size:12px;border-bottom:1px solid var(--border-color)}.modern-table td{padding:13px 14px;border-bottom:1px solid #f1f5f9}.modern-table tbody tr:hover{background:#fafbff}.modern-table tbody tr:last-child td{border-bottom:0}.action-btn{display:inline-flex;width:32px;height:32px;align-items:center;justify-content:center;border-radius:7px}.action-edit{background:#fffbeb;color:#d97706}.action-delete{background:#fef2f2;color:#dc2626}.alert-msg{padding:13px 16px;border-radius:10px;margin-bottom:16px;font-size:13px;display:flex;gap:8px;align-items:center}.alert-success{background:#f0fdf4;color:#166534;border:1px solid #bbf7d0}.alert-error{background:#fef2f2;color:#991b1b;border:1px solid #fecaca}.empty-state{text-align:center;color:#94a3b8;padding:35px!important}
.attendance-box{display:grid;grid-template-columns:300px 1fr;gap:20px;margin-bottom:25px}.clock-card{background:linear-gradient(135deg,#1e293b,#0f172a);color:#fff;border-radius:12px;padding:25px;text-align:center}.clock-time{font-size:36px;font-weight:700;color:#818cf8;margin:10px 0;font-family:monospace}.clock-date{font-size:13px;color:#94a3b8}.status-badge{display:inline-block;padding:7px 13px;border-radius:999px;font-size:11px;font-weight:700;margin-top:15px}.badge-none{background:#fee2e2;color:#dc2626}.badge-working{background:#dcfce7;color:#15803d}.badge-done{background:#e0f2fe;color:#0369a1}.btn-checkin-group{display:flex;gap:10px;margin-top:20px;flex-wrap:wrap}.btn-attendance{padding:10px 16px;border:0;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer}.btn-in{background:#10b981;color:#fff}.btn-out{background:#ef4444;color:#fff}.btn-attendance:disabled{background:#cbd5e1!important;color:#94a3b8!important}.info-card{padding:20px}.table-custom{width:100%;border-collapse:collapse;min-width:600px;font-size:13px}.table-custom th{padding:13px 14px;background:#f8fafc;color:#475569;text-align:left}.table-custom td{padding:13px 14px;border-bottom:1px solid #f1f5f9}
.shift-layout{display:grid;grid-template-columns:360px minmax(0,1fr);gap:20px}.shift-card{padding:20px}.shift-card h3{font-size:15px;margin-bottom:16px;padding-bottom:12px;border-bottom:1px solid var(--border-color)}
@media(max-width:950px){.shift-layout{grid-template-columns:1fr}.attendance-box{grid-template-columns:1fr}}@media(max-width:800px){#sidebar{width:220px;min-width:220px}#content{margin-left:220px}.main-body{padding:20px}.nav-info{gap:10px}}@media(max-width:600px){.wrapper{flex-direction:column}#sidebar{position:relative;width:100%;min-width:100%;height:auto}#content{margin-left:0}.main-body{padding:15px}.top-navbar{position:relative}.nav-info{display:none}.card-body{padding:14px}.shift-layout{grid-template-columns:1fr}}
</style></head>
<body>
<div class="wrapper">
    <!-- 1. THANH BÊN (SIDEBAR) -->
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
    <!-- 2. NỘI DUNG CHÍNH (CONTENT) -->
    <div id="content">
        <!-- THANH TRÊN (TOP NAVBAR) -->
        <header class="top-navbar"><div class="nav-info"><span><i class="fa fa-phone"></i> Hỗ trợ 24/7: 1900 9477</span><span><i class="fa-solid fa-envelope"></i> Email: web@tbvietnam.com</span><span><i class="fa fa-user"></i> Xin chào: <strong><?= htmlspecialchars($_SESSION['admin_name'] ?? 'Admin') ?></strong></span><span><a href="../logout.php"><i class="fa fa-sign-out-alt"></i> Đăng xuất</a></span></div></header>

        <!-- MAIN BODY -->
        <div class="main-body">
            <div class="breadcrumb">Nhân sự > Lịch phân ca trực & Chấm công</div>

            <!-- HIỂN THỊ THÔNG BÁO TỪ PROCESS_CHECKIN.PHP -->
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert-msg alert-success">
                    <i class="fa fa-check-circle"></i> <?= htmlspecialchars($_SESSION['success']); ?>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert-msg alert-error">
                    <i class="fa fa-exclamation-circle"></i> <?= htmlspecialchars($_SESSION['error']); ?>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <span class="header-title"><i class="fa fa-clock"></i> ĐIỂM DANH & THỜI GIAN LÀM VIỆC</span>
                </div>
                <div class="card-body">
                    
                    <!-- BẢNG ĐỒNG HỒ VÀ ĐIỂM DANH -->
                    <div class="attendance-box">
                        <!-- ĐỒNG HỒ & TRẠNG THÁI -->
                        <div class="clock-card">
                            <i class="fa fa-user-clock fa-2x" style="color: #38bdf8;"></i>
                            <div class="clock-time" id="digitalClock">00:00:00</div>
                            <div class="clock-date" id="digitalDate">--/--/----</div>

                            <div>
                                <?php if (!$today_attendance): ?>
                                    <span class="status-badge badge-none"><i class="fa fa-circle"></i> Chưa vào ca</span>
                                <?php elseif ($today_attendance['check_out'] === null): ?>
                                    <span class="status-badge badge-working"><i class="fa fa-spinner fa-spin"></i> Đang trong ca làm</span>
                                <?php else: ?>
                                    <span class="status-badge badge-done"><i class="fa fa-check-circle"></i> Đã hoàn thành ca</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- KHU VỰC THAO TÁC CHECK-IN / CHECK-OUT -->
                        <div class="info-card">
                            <h4><i class="fa fa-calendar-check"></i> Chấm công hôm nay (<?= date('d/m/Y') ?>)</h4>
                            
                            <p style="color: #64748b; font-size: 14px;">
                                Vui lòng bấm <strong>Vào ca</strong> khi bắt đầu thời gian làm việc và bấm <strong>Ra ca</strong> trước khi kết thúc ca trực của bạn.
                            </p>

                            <div style="margin: 15px 0; font-size: 14px;">
                                <p><strong>Thời gian Check-in:</strong> 
                                    <span style="color: #10b981; font-weight: bold;">
                                        <?= !empty($today_attendance['check_in']) ? date('H:i:s d/m/Y', strtotime($today_attendance['check_in'])) : 'Chưa ghi nhận' ?>
                                    </span>
                                </p>
                                <p><strong>Thời gian Check-out:</strong> 
                                    <span style="color: #ef4444; font-weight: bold;">
                                        <?= !empty($today_attendance['check_out']) ? date('H:i:s d/m/Y', strtotime($today_attendance['check_out'])) : 'Chưa ghi nhận' ?>
                                    </span>
                                </p>
                            </div>

                            <div class="btn-checkin-group">
                                <!-- Form Check-in -->
                                <form action="process_checkin.php" method="POST">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                    <input type="hidden" name="type" value="checkin">
                                    <button type="submit" class="btn-attendance btn-in" <?= ($today_attendance) ? 'disabled' : '' ?>>
                                        <i class="fa fa-sign-in-alt"></i> Vào ca (Check-in)
                                    </button>
                                </form>

                                <!-- Form Check-out -->
                                <form action="process_checkin.php" method="POST">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                    <input type="hidden" name="type" value="checkout">
                                    <button type="submit" class="btn-attendance btn-out" <?= (!$today_attendance || $today_attendance['check_out'] !== null) ? 'disabled' : '' ?>>
                                        <i class="fa fa-sign-out-alt"></i> Ra ca (Check-out)
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- LỊCH SỬ CHẤM CÔNG GẦN ĐÂY -->
                    <h4 style="margin-top: 25px; color: #1e293b;"><i class="fa fa-history"></i> Lịch sử chấm công gần đây</h4>
                    <div class="table-wrap"><table class="table-custom">
                        <thead>
                            <tr>
                                <th>Ngày làm việc</th>
                                <th>Giờ vào ca (Check-in)</th>
                                <th>Giờ ra ca (Check-out)</th>
                                <th>Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($history_result && $history_result->num_rows > 0): ?>
                                <?php while($row = $history_result->fetch_assoc()): ?>
                                    <tr>
                                        <td><strong><?= date('d/m/Y', strtotime($row['ngay_lam'])) ?></strong></td>
                                        <td style="color: #059669; font-weight: 600;">
                                            <?= $row['check_in'] ? date('H:i:s', strtotime($row['check_in'])) : '-' ?>
                                        </td>
                                        <td style="color: #dc2626; font-weight: 600;">
                                            <?= $row['check_out'] ? date('H:i:s', strtotime($row['check_out'])) : '-' ?>
                                        </td>
                                        <td>
                                            <?php if ($row['check_out'] !== null): ?>
                                                <span style="color: #0284c7; font-weight: 600;">Hoàn thành</span>
                                            <?php else: ?>
                                                <span style="color: #d97706; font-weight: 600;">Đang làm việc</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" style="text-align: center; color: #94a3b8; padding: 15px;">Chưa có dữ liệu chấm công.</td>
                                </tr>
                            <?php endif; ?>
                            <?php $stmt_h->close(); ?>
                        </tbody>
                    </table></div>

                </div>
            </div>
        </div>
    </div>
</div>

<script>document.querySelectorAll('.menu-toggle').forEach(menu=>{menu.addEventListener('click',function(){this.parentElement.classList.toggle('open')})});document.addEventListener('DOMContentLoaded',()=>{document.querySelectorAll('.alert-msg').forEach(el=>setTimeout(()=>{el.style.transition='.3s';el.style.opacity='0';setTimeout(()=>el.remove(),300)},4000))});</script>
</body>
</html>