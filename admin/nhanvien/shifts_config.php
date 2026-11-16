<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/Shopbanhang/conn.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/Shopbanhang/func.php';

// 2. Nếu là Nhân viên -> Chặn truy cập và chuyển về trang chủ admin
if ($_SESSION['role'] !== 'admin') {
    echo "<script>
        alert('Tài khoản nhân viên không có quyền truy cập vào mục này!');
        window.location.href = '/Shopbanhang/admin/index.php';
    </script>";
    exit();
}

$success_msg = $error_msg = '';

// Xử lý Thêm / Sửa ca trực
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $shift_id = $_POST['shift_id'] ?? '';
    $shift_name = trim($_POST['shift_name'] ?? '');
    $start_time = $_POST['start_time'] ?? '';
    $end_time = $_POST['end_time'] ?? '';
    $description = trim($_POST['description'] ?? '');

    if (empty($shift_name) || empty($start_time) || empty($end_time)) {
        $error_msg = 'Vui lòng nhập đầy đủ tên ca và khung giờ!';
    } else {
        if (empty($shift_id)) {
            // Thêm mới
            $stmt = $conn->prepare("INSERT INTO shifts (shift_name, start_time, end_time, description) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $shift_name, $start_time, $end_time, $description);
            if ($stmt->execute()) {
                $success_msg = 'Thêm ca làm việc thành công!';
            } else {
                $error_msg = 'Lỗi khi thêm ca làm việc.';
            }
            $stmt->close();
        } else {
            // Cập nhật
            $stmt = $conn->prepare("UPDATE shifts SET shift_name = ?, start_time = ?, end_time = ?, description = ? WHERE id = ?");
            $stmt->bind_param("ssssi", $shift_name, $start_time, $end_time, $description, $shift_id);
            if ($stmt->execute()) {
                $success_msg = 'Cập nhật ca làm việc thành công!';
            } else {
                $error_msg = 'Lỗi khi cập nhật ca làm việc.';
            }
            $stmt->close();
        }
    }
}

// Xử lý Xóa ca trực
if (isset($_GET['delete'])) {
    $del_id = (int)$_GET['delete'];
    $stmtDel = $conn->prepare("DELETE FROM shifts WHERE id = ?");
    $stmtDel->bind_param("i", $del_id);
    if ($stmtDel->execute()) {
        $stmtDel->close();
        header("Location: shifts_config.php?msg=deleted");
        exit;
    }
    $stmtDel->close();
}

// Lấy danh sách ca
$resultShifts = $conn->query("SELECT * FROM shifts ORDER BY start_time ASC");
$shifts = $resultShifts->fetch_all(MYSQLI_ASSOC);

// Lấy thông tin sửa nếu có yêu cầu
$editShift = null;
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $stmtEdit = $conn->prepare("SELECT * FROM shifts WHERE id = ?");
    $stmtEdit->bind_param("i", $edit_id);
    $stmtEdit->execute();
    $resultEdit = $stmtEdit->get_result();
    $editShift = $resultEdit->fetch_assoc();
    $stmtEdit->close();
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý ca làm việc | Triple Beauty</title>
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
        <header class="top-navbar"><div class="nav-info"><span><i class="fa fa-phone"></i> Hỗ trợ 24/7: 1900 9477</span><span><i class="fa-solid fa-envelope"></i> Email: web@tbvietnam.com</span><span><i class="fa fa-user"></i> Xin chào: <strong><?= htmlspecialchars($_SESSION['admin_name'] ?? 'Admin') ?></strong></span><span><a href="../logout.php"><i class="fa fa-sign-out-alt"></i> Đăng xuất</a></span></div></header>

        <!-- MAIN BODY -->
        <div class="main-body">
            <div class="breadcrumb">Trang chủ > Quản lý Nhân sự > Danh mục ca làm việc</div>

            <h2><i class="fa-solid fa-business-time"></i> Quản lý danh mục ca làm việc</h2>

            <?php if (!empty($success_msg)): ?>
                <div class="alert-msg alert-success"><i class="fa fa-circle-check"></i><?= htmlspecialchars($success_msg) ?></div>
            <?php endif; ?>
            <?php if (!empty($error_msg)): ?>
                <div class="alert-msg alert-error"><i class="fa fa-circle-exclamation"></i><?= htmlspecialchars($error_msg) ?></div>
            <?php endif; ?>

            <div class="shift-layout">
                
                <!-- Form Thêm / Sửa -->
                <div class="content-card shift-card">
                    <h3 style="margin-bottom: 15px; font-size: 16px; border-bottom: 1px solid #eee; padding-bottom: 10px;">
                        <?= $editShift ? 'Cập nhật ca làm việc' : 'Thêm ca làm việc mới' ?>
                    </h3>
                    <form method="POST" action="">
                        <input type="hidden" name="shift_id" value="<?= $editShift['id'] ?? '' ?>">
                        
                        <div style="margin-bottom: 15px;">
                            <label style="display: block; margin-bottom: 5px; font-weight: 500; font-size: 13px;">Tên ca (*)</label>
                            <input type="text" name="shift_name" value="<?= htmlspecialchars($editShift['shift_name'] ?? '') ?>" required placeholder="Ví dụ: Ca Sáng">
                        </div>

                        <div style="margin-bottom: 15px;">
                            <label style="display: block; margin-bottom: 5px; font-weight: 500; font-size: 13px;">Giờ bắt đầu (*)</label>
                            <input type="time" name="start_time" value="<?= $editShift['start_time'] ?? '' ?>" required>
                        </div>

                        <div style="margin-bottom: 15px;">
                            <label style="display: block; margin-bottom: 5px; font-weight: 500; font-size: 13px;">Giờ kết thúc (*)</label>
                            <input type="time" name="end_time" value="<?= $editShift['end_time'] ?? '' ?>" required>
                        </div>

                        <div style="margin-bottom: 15px;">
                            <label style="display: block; margin-bottom: 5px; font-weight: 500; font-size: 13px;">Ghi chú</label>
                            <textarea name="description"><?= htmlspecialchars($editShift['description'] ?? '') ?></textarea>
                        </div>

                        <div>
                            <button type="submit" class="btn btn-primary" style="width:100%;">
                                <i class="fa fa-save"></i> <?= $editShift ? 'Lưu thay đổi' : 'Thêm ca trực' ?>
                            </button>
                            <?php if ($editShift): ?>
                                <a href="shifts_config.php" style="display: block; text-align: center; margin-top: 10px; color: #6c757d; text-decoration: none; font-size: 13px;">Hủy chỉnh sửa</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>

                <!-- Bảng danh sách ca -->
                <div class="content-card shift-card">
                    <h3 style="margin-bottom: 15px; font-size: 16px; border-bottom: 1px solid #eee; padding-bottom: 10px;">Danh sách các ca trực</h3>
                    <div class="table-wrap"><table class="modern-table">
                            <thead>
                                <tr style="background: #f8f9fa; border-bottom: 2px solid #dee2e6; color: #333;">
                                    <th>ID</th>
                                    <th>Tên ca</th>
                                    <th>Khung giờ</th>
                                    <th>Ghi chú</th>
                                    <th>Hành động</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($shifts) > 0): ?>
                                    <?php foreach ($shifts as $s): ?>
                                        <tr style="border-bottom: 1px solid #eee;">
                                            <td><?= $s['id'] ?></td>
                                            <td><?= htmlspecialchars($s['shift_name']) ?></td>
                                            <td><i class="fa fa-clock" style="color: #6c757d; margin-right: 5px;"></i> <?= $s['start_time'] ?> - <?= $s['end_time'] ?></td>
                                            <td><?= htmlspecialchars($s['description']) ?></td>
                                            <td>
                                                <a class="action-btn action-edit" href="shifts_config.php?edit=<?= $s['id'] ?>" title="Sửa"><i class="fa fa-edit"></i></a>
                                                <a class="action-btn action-delete" href="shifts_config.php?delete=<?= $s['id'] ?>" title="Xóa"><i class="fa fa-trash"></i></a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" style="text-align: center; padding: 20px; color: #777;">Chưa có ca làm việc nào được thiết lập.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>

        </div>
    </div>
</div>

<script>document.querySelectorAll('.menu-toggle').forEach(menu=>{menu.addEventListener('click',function(){this.parentElement.classList.toggle('open')})});document.addEventListener('DOMContentLoaded',()=>{document.querySelectorAll('.alert-msg').forEach(el=>setTimeout(()=>{el.style.transition='.3s';el.style.opacity='0';setTimeout(()=>el.remove(),300)},4000))});</script>

</body>
</html>