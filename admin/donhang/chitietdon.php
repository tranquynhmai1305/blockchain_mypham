<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/Shopbanhang/conn.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/Shopbanhang/func.php';

$id = $_GET['id'] ?? '';

$donhang = getDonHangById($id);
$chitiet = getChiTietDonHang($id);

if (!$donhang) {
    header("Location: index.php");
    exit;
}


if (!empty($keyword)) {
    $donHang = getDonHangByKeyword($keyword, $status);
} elseif (!empty($_GET['id'])) {
    $donHang = getDonHangById($_GET['id']);
}

?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý sản phẩm</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/admin_index.css">

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
            <div class="nav-info">
                <span><i class="fa fa-phone"></i> Hỗ trợ 24/7: 1900 9477</span>
                <span><i class="fa-solid fa-envelope"></i> Email: web@tbvietnam.com</span>
                <span><i class="fa fa-user"></i> Xin chào: <strong><?= htmlspecialchars($_SESSION['admin_name'] ?? $_SESSION['user_name'] ?? 'Nhân viên') ?></strong></span>
                <span><a href="../../logout.php"><i class="fa fa-sign-out-alt"></i> Đăng xuất</a>
            </div>
        </header>

        <!-- MAIN BODY -->
        <div class="main-body">
            <div class="breadcrumb">Trang chủ > Quản lý đơn hàng > Chi tiết</div>
                <h2>Chi tiết đơn hàng</h2>

                <div class="card">
                    <div class="card-header">
                        <span class="header-title">
                            <i class="fa fa-receipt"></i> Thông tin đơn hàng
                        </span>

                        <a href="index.php" class="btn-refresh">
                            <i class="fa fa-arrow-left"></i> Quay lại
                        </a>
                    </div>

                    <div class="card-body">
                        <div class="product-card-list">

                            <!-- THÔNG TIN ĐƠN -->
                            <div class="product-card" style="max-width:700px">
                                <div class="product-card-info">
                                    <h4>Đơn hàng #<?= $donhang['madh'] ?></h4>
                                    <p class="brand">Khách hàng: <?= $donhang['hoten'] ?></p>
                                    <p class="brand">SĐT: <?= $donhang['sdt'] ?></p>
                                    <p class="brand">Ngày đặt: <?= date('d/m/Y', strtotime($donhang['ngaydat'])) ?></p>
                                    <p class="price">Tổng tiền: <?= number_format($donhang['tongtien']) ?> VND</p>

                                    <p>
                                        Trạng thái:
                                        <?php
                                        switch ($donhang['trangthai']) {
                                            case 1: echo '<span class="badge warning">Chờ xác nhận</span>'; break;
                                            case 2: echo '<span class="badge info">Đang giao</span>'; break;
                                            case 3: echo '<span class="badge success">Hoàn thành</span>'; break;
                                            default: echo '<span class="badge danger">Đã hủy</span>';
                                        }
                                        ?>
                                    </p>
                                </div>
                            </div>

                            <!-- DANH SÁCH SẢN PHẨM -->
                            <div class="product-card" style="width:100%">
                                <div class="product-card-info">
                                    <h4>Sản phẩm trong đơn</h4>

                                    <table class="table" style="margin-top:15px">
                                        <thead>
                                            <tr>
                                                <th>Tên sản phẩm</th>
                                                <th>Số lượng</th>
                                                <th>Đơn giá</th>
                                                <th>Thành tiền</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($ct = mysqli_fetch_assoc($chitiet)) : ?>
                                            <tr>
                                                <td><?= $ct['tensp'] ?></td>
                                                <td><?= $ct['soluong'] ?></td>
                                                <td><?= number_format($ct['dongia']) ?> VND</td>
                                                <td><?= number_format($ct['soluong'] * $ct['dongia']) ?> VND</td>
                                            </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
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