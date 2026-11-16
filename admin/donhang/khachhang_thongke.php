<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/Shopbanhang/conn.php';

// 1. Kiểm tra quyền truy cập (Chặn truy cập trái phép)
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'nhanvien'])) {
    header("Location: /Shopbanhang/login.php");
    exit();
}

$sql = "SELECT kh.makh, kh.hoten, kh.sdt, kh.email, kh.diachi,
               COUNT(dh.madh) AS so_don, 
               IFNULL(SUM(dh.tongtien), 0) AS tong_tien
        FROM khachhang kh
        LEFT JOIN donhang dh ON kh.makh = dh.makh
        GROUP BY kh.makh, kh.hoten, kh.sdt, kh.email, kh.diachi
        ORDER BY tong_tien DESC";

$result = mysqli_query($conn, $sql);
$customers = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $customers[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thống kê khách hàng - Triple Beauty</title>
    <!-- Google Fonts & FontAwesome -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>

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

        .dashboard-container {
            padding: 30px;
        }

        /* Custom overrides for view switcher */
        .btn-view-type.active {
            background-color: var(--primary-color);
            color: white;
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
 
    <!-- MAIN CONTENT -->
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

        <main class="dashboard-container">
            <div class="flex justify-between items-end mb-8">
                <div>
                    <div class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">Báo cáo hệ thống</div>
                    <h2 class="text-3xl font-black text-slate-800 tracking-tighter uppercase">Giá trị khách hàng</h2>
                </div>
                
                <div class="flex gap-2 bg-white p-1.5 rounded-2xl shadow-sm border border-slate-200">
                    <button onclick="toggleView('table')" id="btn-table" class="btn-view-type active px-4 py-2 rounded-xl font-bold text-[10px] uppercase transition-all">
                        <i class="fa fa-list-ul mr-1"></i> Dạng bảng
                    </button>
                    <button onclick="toggleView('grid')" id="btn-grid" class="btn-view-type px-4 py-2 rounded-xl font-bold text-[10px] uppercase transition-all">
                        <i class="fa fa-th-large mr-1"></i> Dạng thẻ
                    </button>
                </div>
            </div>

            <!-- VIEW TABLE -->
            <div id="view-table" class="bg-white rounded-[2rem] shadow-sm border border-slate-200 overflow-hidden">
                <table class="w-full text-left">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-100 text-slate-400 text-[10px] font-black uppercase tracking-widest">
                            <th class="p-6">Thành viên</th>
                            <th class="p-6 text-center">Số đơn</th>
                            <th class="p-6 text-right">Tổng chi tiêu</th>
                            <th class="p-6 text-center">Hành động</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <?php foreach ($customers as $c): ?>
                            <tr class="hover:bg-slate-50/50 transition-colors group">
                                <td class="p-6">
                                    <div class="flex items-center gap-4">
                                        <div class="w-10 h-10 rounded-xl bg-slate-900 text-white flex items-center justify-center font-bold text-sm">
                                            <?= strtoupper(mb_substr($c['hoten'], 0, 1)) ?>
                                        </div>
                                        <div>
                                            <div class="font-bold text-slate-800 text-sm"><?= htmlspecialchars($c['hoten']) ?></div>
                                            <div class="text-[10px] text-slate-400 uppercase font-bold tracking-tighter"><?= $c['email'] ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="p-6 text-center font-bold text-slate-600 text-sm">
                                    <?= $c['so_don'] ?>
                                </td>
                                <td class="p-6 text-right font-black text-rose-600">
                                    <?= number_format($c['tong_tien']) ?> <span class="text-[10px] text-slate-300">VND</span>
                                </td>
                                <td class="p-6 text-center">
                                    <button onclick="viewHistory('<?= $c['makh'] ?>', '<?= htmlspecialchars($c['hoten']) ?>')" 
                                            class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-slate-50 text-slate-400 hover:bg-rose-600 hover:text-white transition-all shadow-sm group">
                                        <i class="fa fa-eye group-hover:scale-110 transition-transform"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- VIEW GRID -->
            <div id="view-grid" class="hidden grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($customers as $c): ?>
                    <div class="bg-white p-6 rounded-[2rem] border border-slate-200 shadow-sm hover:shadow-md transition-all group">
                        <div class="flex justify-between items-start mb-4">
                            <div class="w-12 h-12 rounded-2xl bg-slate-50 text-slate-400 flex items-center justify-center text-xl font-black group-hover:bg-rose-600 group-hover:text-white transition-all">
                                <?= strtoupper(mb_substr($c['hoten'], 0, 1)) ?>
                            </div>
                            <span class="text-[9px] font-black bg-emerald-50 text-emerald-600 px-2 py-1 rounded-md uppercase">VIP Client</span>
                        </div>
                        <h3 class="font-bold text-slate-800 mb-1"><?= htmlspecialchars($c['hoten']) ?></h3>
                        <p class="text-slate-400 text-xs mb-4"><i class="fa fa-phone mr-1"></i> <?= $c['sdt'] ?></p>
                        <div class="grid grid-cols-2 gap-4 pt-4 border-t border-slate-50">
                            <div>
                                <p class="text-[9px] text-slate-400 font-bold uppercase">Đơn hàng</p>
                                <p class="font-black text-slate-700 text-lg"><?= $c['so_don'] ?></p>
                            </div>
                            <div class="text-right">
                                <p class="text-[9px] text-slate-400 font-bold uppercase">Tổng chi</p>
                                <p class="font-black text-rose-600 text-lg"><?= number_format($c['tong_tien'] / 1000, 0) ?>K</p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </main>
    </div>
</div>

<!-- HISTORY MODAL -->
<div id="historyModal" class="hidden fixed inset-0 z-[9999] flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" onclick="closeModal()"></div>
    <div class="relative bg-white w-full max-w-2xl rounded-[2.5rem] shadow-2xl overflow-hidden animate-in fade-in zoom-in duration-300">
        <div class="p-8 border-b border-slate-100 flex justify-between items-center">
            <div>
                <h3 id="modalTitle" class="text-xl font-black text-slate-800 uppercase tracking-tighter">Lịch sử mua hàng</h3>
                <p class="text-xs text-slate-400 font-bold">Chi tiết các giao dịch đã thực hiện</p>
            </div>
            <button onclick="closeModal()" class="w-10 h-10 rounded-full bg-slate-50 text-slate-400 hover:bg-rose-50 hover:text-rose-500 transition-all">
                <i class="fa fa-times"></i>
            </button>
        </div>
        
        <div id="modalBody" class="p-0 max-h-[60vh] overflow-y-auto">
            <div class="flex justify-center p-10">
                <i class="fa fa-spinner fa-spin text-3xl text-slate-200"></i>
            </div>
        </div>
        
        <div class="p-6 bg-slate-50 text-center">
            <button onclick="closeModal()" class="px-8 py-3 bg-slate-900 text-white rounded-2xl font-bold text-xs uppercase tracking-widest hover:bg-slate-800 transition-all">Đóng cửa sổ</button>
        </div>
    </div>
</div>

<script>
    // Xử lý đóng mở Menu con (Submenu) theo chuẩn mẫu mới
    document.querySelectorAll('.menu-toggle').forEach(menu => {
        menu.addEventListener('click', () => {
            const parent = menu.parentElement;
            parent.classList.toggle('open');
            const submenu = menu.nextElementSibling;
            if (submenu) {
                submenu.style.display = submenu.style.display === 'block' ? 'none' : 'block';
            }
        });
    });

    function toggleView(type) {
        const tableView = document.getElementById('view-table');
        const gridView = document.getElementById('view-grid');
        const btnTable = document.getElementById('btn-table');
        const btnGrid = document.getElementById('btn-grid');

        if (type === 'table') {
            tableView.classList.remove('hidden');
            gridView.classList.add('hidden');
            btnTable.classList.add('active');
            btnGrid.classList.remove('active');
        } else {
            tableView.classList.add('hidden');
            gridView.classList.remove('hidden');
            btnGrid.classList.add('active');
            btnTable.classList.remove('active');
        }
    }

    function viewHistory(makh, hoten) {
        const modal = document.getElementById('historyModal');
        const modalBody = document.getElementById('modalBody');
        const modalTitle = document.getElementById('modalTitle');
        
        modal.classList.remove('hidden');
        modalTitle.innerText = "Lịch sử: " + hoten;
        modalBody.innerHTML = '<div class="flex justify-center p-10"><i class="fa fa-spinner fa-spin text-3xl text-slate-200"></i></div>';

        fetch('get_customer_history.php?makh=' + makh)
            .then(response => response.text())
            .then(data => {
                modalBody.innerHTML = data;
            })
            .catch(error => {
                modalBody.innerHTML = '<div class="p-10 text-center text-rose-500 font-bold">Lỗi tải dữ liệu!</div>';
            });
    }

    function closeModal() {
        document.getElementById('historyModal').classList.add('hidden');
    }
</script>

</body>
</html>