<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/Shopbanhang/conn.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/Shopbanhang/func.php';

// 1. Kiểm tra quyền Admin
if ($_SESSION['role'] !== 'admin') {
    echo "<script>
        alert('Tài khoản nhân viên không có quyền truy cập vào mục này!');
        window.location.href = '/Shopbanhang/admin/index.php';
    </script>";
    exit();
}

$error = '';
$success = '';

// Khởi tạo biến lưu giá trị form để giữ lại dữ liệu khi nhập lỗi
$hoten = '';
$email = '';
$sdt = '';
$diachi = '';
$chucvu = 'Nhân viên bán hàng';
$matkhau = '123456';

// 2. Xử lý khi submit form thêm nhân viên
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $hoten = trim($_POST['hoten'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $sdt = trim($_POST['sdt'] ?? '');
    $diachi = trim($_POST['diachi'] ?? '');
    $chucvu = trim($_POST['chucvu'] ?? 'Nhân viên bán hàng');
    $matkhau = trim($_POST['matkhau'] ?? '123456');

    if ($hoten === '' || $email === '') {
        $error = "Vui lòng nhập đầy đủ thông tin bắt buộc!";
    } else {
        // Tạo tài khoản trong bảng users với role = 'staff'
        $hashed_password = password_hash($matkhau, PASSWORD_DEFAULT);
        $stmtUser = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'staff')");
        $stmtUser->bind_param("sss", $email, $email, $hashed_password);
        
        if ($stmtUser->execute()) {
            $id_user = $conn->insert_id;

            // Thêm chi tiết vào bảng nhanvien
            $stmtNv = $conn->prepare("INSERT INTO nhanvien (id_users, hoten, email, sdt, diachi, chucvu) VALUES (?, ?, ?, ?, ?, ?)");
            $stmtNv->bind_param("isssss", $id_user, $hoten, $email, $sdt, $diachi, $chucvu);
            
            if ($stmtNv->execute()) {
                $success = "Thêm nhân viên mới thành công!";
                // Reset form sau khi thêm thành công
                $hoten = $email = $sdt = $diachi = '';
                $chucvu = 'Nhân viên bán hàng';
                $matkhau = '123456';
            } else {
                $error = "Lỗi khi lưu thông tin chi tiết nhân viên.";
            }
        } else {
            $error = "Lỗi: Email đăng nhập này đã tồn tại trong hệ thống.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Thêm nhân viên mới | Triple Beauty</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root{--bg-main:#f8fafc;--sidebar-bg:#0f172a;--sidebar-hover:#1e293b;--text-muted:#94a3b8;--text-dark:#1e293b;--border-color:#e2e8f0;--primary-color:#6366f1;--accent-color:#a31d37;--success:#16a34a;--danger:#ef4444}
        *{box-sizing:border-box;margin:0;padding:0}body{font-family:'Plus Jakarta Sans',sans-serif;background:var(--bg-main);color:var(--text-dark);line-height:1.45}a{text-decoration:none}.wrapper{display:flex;min-height:100vh}#sidebar{width:260px;min-width:260px;background:var(--sidebar-bg);color:#fff;height:100vh;position:fixed;left:0;top:0;overflow-y:auto;z-index:1000}.sidebar-header{padding:24px 20px;background:#090d16;border-bottom:1px solid #1e293b;text-align:center}.sidebar-header h3{font-size:18px}.sidebar-header p{margin-top:6px;font-size:12px;color:var(--text-muted)}.sidebar-header a{color:var(--text-muted)}.menu-label{padding:20px 20px 8px;font-size:11px;font-weight:700;color:#64748b;letter-spacing:1px}#sidebar ul.components{padding:10px;list-style:none}#sidebar ul li{margin-bottom:4px}#sidebar ul li a{padding:12px 16px;display:flex;align-items:center;gap:12px;color:#cbd5e1;border-radius:8px;font-size:14px;font-weight:500;transition:.2s}#sidebar ul li a i{width:20px;text-align:center;color:#94a3b8}#sidebar ul li a:hover{background:var(--sidebar-hover);color:#fff}#sidebar ul li a.active{background:var(--primary-color);color:#fff}.has-submenu>a{justify-content:space-between}.menu-left{display:flex;align-items:center;gap:12px}.arrow{transition:.3s}.has-submenu.open .arrow{transform:rotate(180deg)}.submenu{list-style:none;padding:4px 0 4px 12px;display:none}.has-submenu.open .submenu{display:block}.submenu li a{padding:9px 16px!important;font-size:13px!important;color:#94a3b8!important}.submenu li a.active{background:#1e293b!important;color:#fff!important}#content{flex:1;margin-left:260px;min-height:100vh}.top-navbar{background:#fff;padding:14px 30px;display:flex;justify-content:flex-end;border-bottom:1px solid var(--border-color);box-shadow:0 1px 3px rgba(0,0,0,.05);position:sticky;top:0;z-index:999}.nav-info{display:flex;gap:22px;align-items:center;flex-wrap:wrap;font-size:13px;color:#64748b}.nav-info i{color:var(--primary-color);margin-right:5px}.nav-info a{color:var(--danger);font-weight:600}.main-body{padding:24px 30px;max-width:1500px;margin:auto}.breadcrumb{font-size:13px;color:#64748b;margin-bottom:8px}h2{font-size:21px;margin-bottom:18px;display:flex;align-items:center;gap:10px}
        
        .content-card {
            background: #fff;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,.025);
            max-width: 800px;
            margin: 0 auto;
            padding: 18px 24px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px 16px;
            margin-bottom: 4px;
        }
        .form-full {
            grid-column: span 2;
        }
        .form-group {
            margin-bottom: 0;
        }
        .form-group label {
            display: block;
            margin-bottom: 3px;
            font-size: 12px;
            font-weight: 600;
            color: #475569;
        }
        .form-group input, .form-group textarea, .form-group select {
            width: 100%;
            padding: 8px 11px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font: inherit;
            font-size: 13px;
            outline: none;
            background: #fff;
            transition: all 0.2s;
        }
        .form-group input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(99,102,241,0.1);
        }

        .form-section-title {
            font-size: 13px;
            font-weight: 700;
            color: #334155;
            margin-top: 12px;
            margin-bottom: 8px;
            padding-bottom: 4px;
            border-bottom: 1px dashed var(--border-color);
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .form-section-title:first-of-type {
            margin-top: 0;
        }

        .btn{border:0;border-radius:8px;padding:9px 14px;font-family:inherit;font-size:13px;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:6px}
        .btn-primary{background:var(--primary-color);color:#fff}.btn-secondary{background:#64748b;color:#fff}
        
        .alert-msg{padding:10px 14px;border-radius:8px;margin-bottom:12px;font-size:13px;display:flex;gap:8px;align-items:center;max-width:800px;margin-left:auto;margin-right:auto}
        .alert-success{background:#f0fdf4;color:#166534;border:1px solid #bbf7d0}
        .alert-error{background:#fef2f2;color:#991b1b;border:1px solid #fecaca}

        @media(max-width:800px){#sidebar{width:220px;min-width:220px}#content{margin-left:220px}.main-body{padding:20px}.nav-info{gap:10px}.form-grid{grid-template-columns:1fr}}@media(max-width:600px){.wrapper{flex-direction:column}#sidebar{position:relative;width:100%;min-width:100%;height:auto}#content{margin-left:0}.main-body{padding:15px}.top-navbar{position:relative}.nav-info{display:none}.form-grid{grid-template-columns:1fr}}
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
             
            <li class="has-submenu open">
                <a href="javascript:void(0);" class="menu-toggle">
                    <div class="menu-left">
                        <i class="fa fa-user-shield"></i>
                        <span>Quản lý Nhân sự</span>
                    </div>
                    <i class="fa fa-angle-down arrow"></i>
                </a>
                <ul class="submenu">
                    <li><a href="/Shopbanhang/admin/nhanvien/employees.php" class="active">Danh sách nhân viên</a></li>
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
            <div class="nav-info">
                <span><i class="fa fa-phone"></i> Hỗ trợ 24/7: 1900 9477</span>
                <span><i class="fa-solid fa-envelope"></i> Email: web@tbvietnam.com</span>
                <span><i class="fa fa-user"></i> Xin chào: <strong><?= htmlspecialchars($_SESSION['admin_name'] ?? 'Admin') ?></strong></span>
                <span><a href="../logout.php"><i class="fa fa-sign-out-alt"></i> Đăng xuất</a></span>
            </div>
        </header>

        <!-- MAIN BODY -->
        <div class="main-body">
            <h2><i class="fa fa-user-plus"></i> THÊM NHÂN VIÊN MỚI</h2>
            
            <div style="margin:0 0 14px;">
                <a href="employees.php" class="btn btn-secondary"><i class="fa fa-arrow-left"></i> Quay lại danh sách</a>
            </div>

            <!-- Thông báo nổi -->
            <?php if (!empty($error)): ?>
                <div class="alert-msg alert-error"><i class="fa fa-circle-exclamation"></i><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert-msg alert-success"><i class="fa fa-circle-check"></i><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <!-- KHUNG THÔNG TIN BỐ TRÍ 2 CỘT -->
            <div class="content-card">
                <form method="POST">
                    
                    <!-- Phần 1: Thông tin cá nhân cơ bản -->
                    <div class="form-section-title">
                        <i class="fa fa-id-card"></i> Thông tin cá nhân cơ bản
                    </div>
                    
                    <div class="form-grid">
                        <div class="form-group form-full">
                            <label>Họ tên nhân viên (*)</label>
                            <input type="text" name="hoten" required value="<?= htmlspecialchars($hoten) ?>" placeholder="Ví dụ: Nguyễn Văn A">
                        </div>

                        <div class="form-group">
                            <label>Số điện thoại</label>
                            <input type="text" name="sdt" value="<?= htmlspecialchars($sdt) ?>" placeholder="Ví dụ: 0912345678">
                        </div>

                        <div class="form-group">
                            <label>Địa chỉ</label>
                            <input type="text" name="diachi" value="<?= htmlspecialchars($diachi) ?>" placeholder="Ví dụ: Ninh Kiều, Cần Thơ">
                        </div>
                    </div>

                    <!-- Phần 2: Thông tin tài khoản & Phân quyền -->
                    <div class="form-section-title">
                        <i class="fa fa-shield-halved"></i> Thông tin tài khoản & Phân quyền
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label>Email (dùng để đăng nhập) (*)</label>
                            <input type="email" name="email" required value="<?= htmlspecialchars($email) ?>" placeholder="Ví dụ: staff@tbvietnam.com">
                        </div>

                        <div class="form-group">
                            <label>Chức vụ</label>
                            <input type="text" name="chucvu" value="<?= htmlspecialchars($chucvu) ?>" placeholder="Ví dụ: Thu ngân, Nhân viên kho...">
                        </div>

                        <div class="form-group form-full">
                            <label>Mật khẩu khởi tạo</label>
                            <input type="text" name="matkhau" value="<?= htmlspecialchars($matkhau) ?>" placeholder="Mặc định: 123456">
                        </div>
                    </div>

                    <!-- Nút lưu -->
                    <div style="text-align: right; border-top: 1px solid var(--border-color); padding-top: 14px; margin-top: 14px;">
                        <button type="submit" class="btn btn-primary" style="padding: 10px 22px;">
                            <i class="fa fa-save"></i> Lưu nhân viên
                        </button>
                    </div>

                </form>
            </div>

        </div>
    </div>
</div>

<script>
    document.querySelectorAll('.menu-toggle').forEach(menu => {
        menu.addEventListener('click', function() {
            this.parentElement.classList.toggle('open');
        });
    });

    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.alert-msg').forEach(el => 
            setTimeout(() => {
                el.style.transition = '.3s';
                el.style.opacity = '0';
                setTimeout(() => el.remove(), 300);
            }, 4000)
        );
    });
</script>

</body>
</html>