<?php
session_start(); 

// 1. KẾT NỐI DATABASE & KIỂM TRA QUYỀN
$conn = mysqli_connect("localhost", "root", "", "qlybanhang");
mysqli_set_charset($conn, "utf8mb4");

if (!isset($_SESSION['userid'])) {
    header("Location: login.php");
    exit;
}

$userid = $_SESSION['userid'];

$sql_kh = "SELECT makh FROM khachhang WHERE id_users = '$userid'";
$res_kh = mysqli_query($conn, $sql_kh);
$row_kh = mysqli_fetch_assoc($res_kh);

$makh = $row_kh['makh'] ?? 0;

$sql = "SELECT * FROM donhang WHERE makh = '$makh' ORDER BY ngaydat DESC";
$result = mysqli_query($conn, $sql);

// 2. TÍNH SỐ LƯỢNG SẢN PHẨM TRONG GIỎ HÀNG CHO HEADER
$cartCount = 0;
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cartCount += (int)($item['soluong'] ?? 1);
    }
}

// 3. CẤU HÌNH TRẠNG THÁI ĐƠN HÀNG
$status_map = [
    'Chờ xác nhận' => ['class' => 'bg-amber-100 text-amber-800 border border-amber-200', 'text' => 'Chờ xác nhận'],
    'Đã xác nhận'  => ['class' => 'bg-blue-100 text-blue-800 border border-blue-200', 'text' => 'Đã xác nhận'],
    'Đang giao'    => ['class' => 'bg-cyan-100 text-cyan-800 border border-cyan-200', 'text' => 'Đang giao'],
    'Đã giao'      => ['class' => 'bg-emerald-100 text-emerald-800 border border-emerald-200', 'text' => 'Đã giao'],
    'Đã hủy'       => ['class' => 'bg-rose-100 text-rose-800 border border-rose-200', 'text' => 'Đã hủy']
];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đơn hàng của tôi - Triple Beauty</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 text-slate-800 font-sans min-h-screen flex flex-col">

    <!-- HEADER CHUẨN TRANG CHỦ -->
    <header class="sticky top-0 z-50 bg-white shadow-sm">
        <div class="bg-gradient-to-r from-rose-600 to-pink-600 py-3 text-white">
            <div class="container mx-auto px-4 flex items-center justify-between">
                <a href="index.php" class="flex flex-col leading-none">
                    <span class="text-2xl md:text-3xl font-black italic tracking-tighter uppercase">Triple Beauty</span>
                    <span class="text-[9px] font-medium tracking-widest uppercase opacity-90">Cosmetics & Skincare</span>
                </a>

                <form action="timkiem.php" method="GET" class="hidden md:flex flex-1 mx-8 relative max-w-xl">
                    <input type="text" 
                        name="keyword"
                        placeholder="Tìm kiếm mỹ phẩm, thương hiệu, danh mục..." 
                        class="w-full bg-white text-gray-800 text-sm rounded-full py-2.5 pl-6 pr-12 focus:ring-2 focus:ring-pink-300 outline-none shadow-inner transition">
                    <button type="submit" class="absolute right-1 top-1/2 -translate-y-1/2 w-8 h-8 bg-rose-600 rounded-full flex items-center justify-center text-white hover:bg-rose-700 transition">
                        <i class="fa fa-search text-xs"></i>
                    </button>
                </form>

                <div class="flex items-center space-x-5 text-xs font-semibold">
                    <div class="hidden lg:flex items-center gap-2 cursor-pointer hover:opacity-80 transition bg-white/10 px-3 py-1.5 rounded-lg">
                        <i class="fa-solid fa-store text-sm"></i>
                        <div class="flex flex-col leading-tight text-[10px]">
                            <span>HỆ THỐNG</span>
                            <span class="font-bold">CỬA HÀNG</span>
                        </div>
                    </div>

                    <?php if(isset($_SESSION['username'])): ?>
                    <div class="flex flex-col items-start">
                        <span class="text-[11px] opacity-80">Xin chào,</span>
                        <span class="font-bold truncate max-w-[100px]"><?= htmlspecialchars($_SESSION['username']) ?></span>
                        <a href="logout.php" class="text-[10px] text-pink-200 hover:underline">Đăng xuất</a>
                    </div>
                    <?php else: ?>
                    <div class="flex items-center gap-1.5 font-bold">
                        <a href="login.php" class="hover:text-pink-200 transition">Đăng nhập</a>
                        <span class="opacity-50">/</span>
                        <a href="dangky.php" class="hover:text-pink-200 transition">Đăng ký</a>
                    </div>
                    <?php endif; ?>

                    <!-- GIỎ HÀNG -->
                    <a href="giohang.php" id="cart-target" class="relative cursor-pointer bg-white/20 p-2.5 rounded-xl hover:bg-white/30 transition flex items-center justify-center">
                        <i class="fa fa-shopping-bag text-lg text-white"></i>
                        <?php if ($cartCount > 0): ?>
                        <span id="cart-count" class="absolute -top-1 -right-1 bg-amber-400 text-gray-900 font-bold text-[10px] w-5 h-5 flex items-center justify-center rounded-full shadow-md border-2 border-rose-600">
                            <?= $cartCount ?>
                        </span>
                        <?php endif; ?>
                    </a>
                </div>
            </div>
        </div>

        <!-- NAVIGATION MENU -->
        <nav class="border-b bg-white shadow-xs overflow-x-auto no-scrollbar">
            <div class="container mx-auto px-4 flex space-x-8 text-xs font-bold uppercase tracking-wider whitespace-nowrap text-gray-600 py-3">
                <a href="gioithieu.php" class="hover:text-rose-600 transition">Giới thiệu</a>
                <a href="index.php" class="text-rose-600 border-b-2 border-rose-600 pb-1"> <i class="fa-solid fa-house"></i> Trang chủ</a>
                <a href="donhangcuatoi.php" class="hover:text-rose-600 transition">Đơn hàng của tôi</a>
                <a href="sanpham_damua.php" class="hover:text-rose-600 transition">Lịch sử đơn mua</a>
                <a href="voucher.php" class="hover:text-rose-600 transition">Voucher</a>
                <a href="profile.php" class="hover:text-rose-600 transition">Hồ sơ</a>
                 <a href="diemthuong.php" class="text-rose-600 border-b-2 border-rose-600 pb-1">xem điểm thưởng</a>
                <a href="yeuthich.php" class="hover:text-rose-600 transition">Yêu thích</a>
            </div>
        </nav>
    </header>

    <!-- NỘI DUNG CHÍNH TRANG ĐƠN HÀNG (Mở rộng max-w-6xl để hiển thị 2 cột đẹp hơn) -->
    <main class="flex-1 max-w-6xl w-full mx-auto px-4 py-8">
       
        <?php if(mysqli_num_rows($result) > 0): ?>
            <!-- Đổi thành lưới 2 cột trên màn hình trung bình (md) trở lên -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <?php while($row = mysqli_fetch_assoc($result)): 
                    $ngay_giao = date('d/m/Y', strtotime($row['ngaydat'].' +7 days'));
                    $status = $status_map[$row['trangthai']] ?? ['class' => 'bg-gray-100 text-gray-800', 'text' => $row['trangthai']];
                ?>
                    <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100 hover:shadow-md transition flex flex-col justify-between gap-4">
                        
                        <!-- Phần thông tin trên: Mã đơn, trạng thái, ngày đặt, ngày giao -->
                        <div class="space-y-2">
                            <div class="flex items-center justify-between gap-2 flex-wrap">
                                <span class="font-black text-sm text-slate-900">Mã đơn: #<?= htmlspecialchars($row['madh']) ?></span>
                                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold <?= $status['class'] ?>">
                                    <?= $status['text'] ?>
                                </span>
                            </div>
                            
                            <div class="text-xs text-gray-500 flex items-center gap-2">
                                <i class="fa-regular fa-calendar-days text-rose-500 w-4 text-center"></i> Đặt ngày: <?= date('d/m/Y H:i', strtotime($row['ngaydat'])) ?>
                            </div>

                            <div class="text-xs text-gray-600 flex items-center gap-2">
                                <i class="fa-solid fa-truck-fast text-rose-500 w-4 text-center"></i> Dự kiến giao: <span class="font-medium text-slate-700"><?= $ngay_giao ?></span>
                            </div>
                        </div>

                        <!-- Phần thông tin dưới: Tổng tiền và các nút thao tác căn đều 2 bên -->
                        <div class="pt-3 border-t border-slate-100 flex items-center justify-between gap-2 mt-auto">
                            <div>
                                <span class="text-[10px] text-gray-400 block uppercase tracking-wider">Tổng thanh toán</span>
                                <span class="text-base font-black text-rose-600"><?= number_format($row['tongtien'], 0, ',', '.') ?>đ</span>
                            </div>

                            <div class="flex items-center gap-2">
                                <a href="chitietdonhang.php?madh=<?= $row['madh'] ?>" class="bg-rose-50 hover:bg-rose-100 text-rose-600 font-bold px-3 py-1.5 rounded-xl text-xs transition border border-rose-200">
                                    Chi tiết
                                </a>
                                
                                <?php if($row['trangthai'] == 'Chờ xác nhận'): ?>
                                    <a href="huydonhang.php?madh=<?= $row['madh'] ?>" 
                                       class="bg-gray-100 hover:bg-rose-600 hover:text-white text-gray-600 font-bold px-3 py-1.5 rounded-xl text-xs transition" 
                                       onclick="return confirm('Bạn có chắc chắn muốn hủy đơn hàng này?')">
                                        Hủy đơn
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>

                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="bg-white rounded-3xl p-12 text-center shadow-sm border border-slate-100 max-w-md mx-auto">
                <div class="w-20 h-20 bg-rose-50 text-rose-500 rounded-full flex items-center justify-center mx-auto mb-4 text-3xl">
                    <i class="fa-solid fa-bag-shopping"></i>
                </div>
                <h3 class="text-lg font-bold text-slate-800 mb-1">Chưa có đơn hàng nào</h3>
                <p class="text-xs text-gray-500 mb-6">Bạn chưa phát sinh đơn hàng nào tại cửa hàng của chúng tôi.</p>
                <a href="index.php" class="inline-block bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs px-6 py-3 rounded-xl transition shadow-md shadow-rose-200">
                    Khám phá sản phẩm ngay
                </a>
            </div>
        <?php endif; ?>
    </main>

    <!-- Footer Tiêu Chuẩn -->
    <footer class="bg-black text-white pt-10 pb-6 mt-16 border-t border-slate-900">
        <div class="max-w-7xl mx-auto px-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8 mb-8">
                <div>
                    <h4 class="font-bold uppercase text-xs mb-4 tracking-widest text-slate-300">Liên hệ</h4>
                    <p class="text-slate-400 text-xs leading-relaxed">Email: <span class="text-white font-medium">cskh@triplebeauty.vn</span></p>
                    <p class="text-base font-extrabold text-white tracking-wide pt-2">1800 5454 63</p>
                </div>
                <div>
                    <h4 class="font-bold uppercase text-xs mb-4 tracking-widest text-slate-300">Hỗ trợ khách hàng</h4>
                    <ul class="text-slate-400 text-xs space-y-2">
                        <li class="hover:text-white cursor-pointer transition">Hệ thống cửa hàng</li>
                        <li class="hover:text-white cursor-pointer transition">Chính sách đổi trả</li>
                        <li class="hover:text-white cursor-pointer transition">Chính sách vận chuyển</li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-bold uppercase text-xs mb-4 tracking-widest text-slate-300">Về Triple Beauty</h4>
                    <ul class="text-slate-400 text-xs space-y-2">
                        <li class="hover:text-white cursor-pointer transition">Giới thiệu thương hiệu</li>
                        <li class="hover:text-white cursor-pointer transition">Cam kết chính hãng</li>
                        <li class="hover:text-white cursor-pointer transition">Tin tức làm đẹp</li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-bold uppercase text-xs mb-4 tracking-widest text-slate-300">Kết nối</h4>
                    <div class="flex gap-4 text-base text-slate-300">
                        <i class="fa-brands fa-facebook hover:text-rose-500 cursor-pointer transition"></i>
                        <i class="fa-brands fa-instagram hover:text-rose-500 cursor-pointer transition"></i>
                        <i class="fa-brands fa-tiktok hover:text-rose-500 cursor-pointer transition"></i>
                        <i class="fa-brands fa-youtube hover:text-rose-500 cursor-pointer transition"></i>
                    </div>
                </div>
            </div>
            <div class="border-t border-slate-900 pt-6 flex flex-col md:flex-row justify-between items-center gap-3 text-[10px] text-slate-500 font-bold uppercase tracking-wider">
                <p>&copy; 2026 TRIPLE BEAUTY VIỆT NAM. ĐÃ ĐĂNG KÝ BẢN QUYỀN.</p>
                <div class="flex gap-4">
                    <span class="hover:text-white cursor-pointer transition">Chính sách bảo mật</span>
                    <span>|</span>
                    <span class="hover:text-white cursor-pointer transition">Điều khoản sử dụng</span>
                </div>
            </div>
        </div>
    </footer>

</body>
</html>