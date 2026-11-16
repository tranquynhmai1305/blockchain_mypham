<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/Shopbanhang/func.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/Shopbanhang/conn.php';

// 1. Kiểm tra đăng nhập (Cho phép cả Admin và Nhân viên)
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'nhanvien'])) {
    header("Location: /Shopbanhang/login.php");
    exit;
}

// Kiểm tra lại kết nối Database
if (!$conn || !mysqli_ping($conn)) {
    die("Lỗi kết nối CSDL. Vui lòng thử lại hoặc khởi động lại MySQL trong XAMPP!");
}

// 2. Xử lý khi Admin gửi phản hồi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_dg'])) {
    $id_dg = intval($_POST['id_dg']);
    $noidung_rep = trim($_POST['noidung_phanhoi']);
    
    if (!empty($noidung_rep)) {
        $stmt = mysqli_prepare($conn, "UPDATE danhgiasp SET phanhoi = ? WHERE id_danhgia = ?");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "si", $noidung_rep, $id_dg);
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_close($stmt);

                // GHI NHẬT KÝ HOẠT ĐỘNG (AUDIT LOG - GỬI PHẢN HỒI)
                if (function_exists('save_log')) {
                    $user_id = $_SESSION['user_id'] ?? $_SESSION['id'] ?? $_SESSION['admin_id'] ?? 0;
                    $description = "Đã gửi phản hồi cho đánh giá ID #$id_dg";
                    save_log($conn, $user_id, 'DanhGia', 'UPDATE', $description);
                }

                echo "<script>alert('Gửi phản hồi thành công!'); window.location.href='quanly_danhgia.php';</script>";
                exit;
            } else {
                echo "<script>alert('Lỗi thực thi truy vấn: " . addslashes(mysqli_error($conn)) . "');</script>";
            }
        }
    }
}

// 3. Xử lý xóa phản hồi
if (isset($_GET['delete_rep'])) {
    $id_del = intval($_GET['delete_rep']);
    $stmt_del = mysqli_prepare($conn, "UPDATE danhgiasp SET phanhoi = NULL WHERE id_danhgia = ?");
    if ($stmt_del) {
        mysqli_stmt_bind_param($stmt_del, "i", $id_del);
        if (mysqli_stmt_execute($stmt_del)) {

            // GHI NHẬT KÝ HOẠT ĐỘNG (AUDIT LOG - XÓA PHẢN HỒI)
            if (function_exists('save_log')) {
                $user_id = $_SESSION['user_id'] ?? $_SESSION['id'] ?? $_SESSION['admin_id'] ?? 0;
                $description = "Đã xóa phản hồi của đánh giá ID #$id_del";
                save_log($conn, $user_id, 'DanhGia', 'DELETE', $description);
            }

        }
        mysqli_stmt_close($stmt_del);
    }
    header("Location: quanly_danhgia.php");
    exit;
}

// 4. Lấy danh sách đánh giá
$sql_list = "SELECT dg.*, kh.hoten, sp.tensp 
             FROM danhgiasp dg 
             LEFT JOIN khachhang kh ON dg.makh = kh.makh 
             LEFT JOIN sanpham sp ON dg.masp = sp.masp 
             ORDER BY dg.id_danhgia DESC";
$res_list = mysqli_query($conn, $sql_list);

$userName = $_SESSION['admin_name'] ?? $_SESSION['user_name'] ?? 'Nhân viên';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title>Quản lý đánh giá - Triple Beauty</title>
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

    /* DASHBOARD CONTAINER */
    .dashboard-container {
        padding: 20px 30px;
        max-width: 1400px;
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
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    /* 2-COLUMN GRID LAYOUT FOR REVIEWS */
    .reviews-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 20px;
    }

    /* REFINED REVIEW CARDS */
    .review-card {
        background: #ffffff;
        border-radius: 12px;
        border: 1px solid var(--border-color);
        padding: 20px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.02);
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }

    .review-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid #f1f5f9;
        padding-bottom: 12px;
        font-size: 14px;
    }

    .stars { 
        color: #f59e0b; 
        font-size: 14px;
        letter-spacing: 1px;
    }

    .review-content {
        margin: 12px 0;
        color: #334155;
        font-style: italic;
        font-size: 14px;
        line-height: 1.5;
    }

    .review-image-wrap {
        margin-bottom: 12px;
    }

    .review-image-wrap img {
        width: 90px;
        height: 90px;
        object-fit: cover;
        border-radius: 8px;
        border: 1px solid var(--border-color);
    }

    .admin-reply-box {
        margin-top: 12px;
        background: #fdf2f4;
        padding: 14px 16px;
        border-radius: 8px;
        border-left: 4px solid var(--accent-color);
        font-size: 13px;
    }

    .admin-reply-box.pending {
        background: #f8fafc;
        border-left-color: #cbd5e1;
    }

    textarea {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid var(--border-color);
        border-radius: 8px;
        margin-top: 8px;
        font-family: 'Plus Jakarta Sans', sans-serif;
        font-size: 13px;
        resize: vertical;
        outline: none;
        transition: border-color 0.2s;
    }

    textarea:focus {
        border-color: var(--primary-color);
    }

    .btn-send {
        background: var(--accent-color);
        color: white;
        border: none;
        padding: 8px 16px;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 600;
        font-size: 13px;
        margin-top: 8px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: opacity 0.2s;
    }

    .btn-send:hover {
        opacity: 0.9;
    }

    .empty-state {
        text-align: center;
        padding: 40px;
        color: var(--text-muted);
        background: #ffffff;
        border-radius: 12px;
        border: 1px solid var(--border-color);
        grid-column: span 2;
    }

    @media (max-width: 1200px) {
        .reviews-grid {
            grid-template-columns: 1fr;
        }
        .empty-state {
            grid-column: span 1;
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
                    <span class="user-name"><?= htmlspecialchars($userName) ?></span>
                </div>
                <a href="/Shopbanhang/logout.php" class="btn-logout" title="Đăng xuất">
                    <i class="fa fa-sign-out-alt"></i>
                </a>
            </div>
        </header>

        <!-- MAIN BODY -->
        <div class="dashboard-container">

            <?php if ($res_list && mysqli_num_rows($res_list) > 0): ?>
                <div class="reviews-grid">
                    <?php while($row = mysqli_fetch_assoc($res_list)): ?>
                    <div class="review-card">
                        <div>
                            <div class="review-header">
                                <div>
                                    <strong style="color: var(--text-dark);"><?= htmlspecialchars($row['hoten'] ?? 'Khách hàng ẩn danh') ?></strong> 
                                    <span style="color: #64748b; font-size: 13px;">đánh giá:</span> 
                                    <b style="color: var(--accent-color);"><?= htmlspecialchars($row['tensp'] ?? 'Sản phẩm đã xóa') ?></b>
                                </div>
                                <div class="stars"><?= str_repeat('★', intval($row['sosao'])) ?></div>
                            </div>

                            <p class="review-content">"<?= htmlspecialchars($row['noidung']) ?>"</p>

                            <?php if(!empty($row['hinhanh'])): ?>
                                <div class="review-image-wrap">
                                    <img src="/Shopbanhang/uploads/reviews/<?= htmlspecialchars($row['hinhanh']) ?>" alt="Ảnh đánh giá">
                                </div>
                            <?php endif; ?>
                        </div>

                        <div>
                            <?php if(!empty($row['phanhoi'])): ?>
                                <div class="admin-reply-box">
                                    <strong style="color: var(--accent-color);"><i class="fa fa-reply"></i> Phản hồi từ quản trị viên:</strong>
                                    <p style="margin-top: 4px; color: #334155; font-size: 13px;"><?= htmlspecialchars($row['phanhoi']) ?></p>
                                    <div style="margin-top: 8px; text-align: right;">
                                        <a href="?delete_rep=<?= $row['id_danhgia'] ?>" style="color: #ef4444; text-decoration: none; font-size: 12px; font-weight: 600;" onclick="return confirm('Bạn có chắc chắn muốn xóa phản hồi này?')">
                                            <i class="fa fa-trash"></i> Xóa / Sửa lại phản hồi
                                        </a>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="admin-reply-box pending">
                                    <form action="" method="POST">
                                        <input type="hidden" name="id_dg" value="<?= $row['id_danhgia'] ?>">
                                        <strong style="color: #475569; font-size: 13px;">Phản hồi khách hàng:</strong>
                                        <textarea name="noidung_phanhoi" rows="2" placeholder="Nhập lời cảm ơn hoặc giải đáp thắc mắc..." required></textarea>
                                        <button type="submit" class="btn-send">
                                            <i class="fa fa-paper-plane"></i> Gửi phản hồi
                                        </button>
                                    </form>
                                </div>
                            <?php endif; ?>
                            
                            <div style="font-size: 11px; color: #94a3b8; text-align: right; margin-top: 10px;">
                                Ngày gửi: <?= date('d/m/Y H:i', strtotime($row['ngaydanhgia'])) ?>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fa fa-comment-slash" style="font-size: 32px; margin-bottom: 8px; color: #cbd5e1;"></i>
                    <p>Hiện chưa có đánh giá nào từ khách hàng.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('.menu-toggle').forEach(menu => {
    menu.addEventListener('click', () => {
        menu.parentElement.classList.toggle('open');
    });
});
</script>

</body>
</html>