<?php
session_start();
$conn = mysqli_connect("localhost", "root", "", "qlybanhang");
mysqli_set_charset($conn, "utf8mb4");

if (!isset($_SESSION['userid']) || !isset($_GET['madh'])) {
    header("Location: donhangcuatoi.php");
    exit;
}

$madh = mysqli_real_escape_string($conn, $_GET['madh']);
$userid = $_SESSION['userid'];

// Lấy thông tin khách hàng từ bảng khachhang dựa theo id_users
$sql_kh = "SELECT makh, hoten, sdt, diachi FROM khachhang WHERE id_users = '$userid'";
$res_kh = mysqli_query($conn, $sql_kh);
$info_kh = mysqli_fetch_assoc($res_kh);

$makh = $info_kh['makh'] ?? 0;

$sql_donhang = "SELECT * FROM donhang WHERE madh = '$madh' AND makh = '$makh'";
$res_donhang = mysqli_query($conn, $sql_donhang);
$info_dh = mysqli_fetch_assoc($res_donhang);

if (!$info_dh) {
    echo "<script>alert('Không tìm thấy đơn hàng!'); window.location.href='donhangcuatoi.php';</script>";
    exit;
}

$sql_chitiet = "
SELECT 
    ct.masp,
    sp.tensp, 
    ct.soluong,
    ct.dongia,
    MIN(ha.duongdan) AS duongdan
FROM chitiet_donhang ct
LEFT JOIN sanpham sp ON ct.masp = sp.masp
LEFT JOIN hinhanh_sanpham ha ON sp.masp = ha.masp
WHERE ct.madh = '$madh'
GROUP BY ct.masp, sp.tensp, ct.soluong, ct.dongia
";

$res_chitiet = mysqli_query($conn, $sql_chitiet);

$cartCount = 0;
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cartCount += (int)($item['soluong'] ?? 1);
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết đơn hàng #<?php echo htmlspecialchars($madh); ?> - Triple Beauty</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root { --pink-main: #e11d48; --pink-light: #fff1f2; --bg: #f8fafc; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: var(--bg); margin: 0; }
        .container-box { max-width: 850px; margin: 30px auto; background: #fff; padding: 25px; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.04); }
        .header-detail { border-bottom: 2px dashed #f1f5f9; padding-bottom: 15px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; }
        .header-detail h2 { color: #e11d48; margin: 0; font-size: 20px; font-weight: 800; }
        
        .info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 15px; margin-bottom: 25px; }
        .info-card { background: #fafafa; border: 1px solid #f1f5f9; padding: 15px; border-radius: 12px; font-size: 0.88em; line-height: 1.7; }
        .info-card h4 { font-weight: 700; color: #1e293b; margin-bottom: 8px; text-transform: uppercase; font-size: 0.8em; letter-spacing: 0.5px; border-bottom: 1px solid #e2e8f0; padding-bottom: 4px; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th { text-align: left; padding: 10px; border-bottom: 2px solid #ffe4e6; color: #475569; font-size: 0.85em; text-transform: uppercase; }
        td { padding: 12px 10px; border-bottom: 1px solid #f1f5f9; font-size: 0.9em; }
        .product-name { font-weight: 600; color: #1e293b; font-size: 0.95em; }
        .price { color: #e11d48; font-weight: bold; }
        
        .summary { margin-top: 25px; padding-top: 15px; border-top: 2px solid #f1f5f9; }
        .total-pay { font-size: 1.4em; font-weight: 800; color: #e11d48; }
        .btn-back { display: inline-flex; items-center; gap: 6px; text-decoration: none; color: #64748b; font-weight: 600; font-size: 0.85em; margin-bottom: 15px; transition: 0.2s; }
        .btn-back:hover { color: #e11d48; }

        .btn-cancel-red {
            background-color: #ef4444;
            color: white;
            padding: 9px 18px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 700;
            font-size: 0.85em;
            transition: 0.2s ease;
            display: inline-block;
        }
        .btn-cancel-red:hover {
            background-color: #dc2626;
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
        }

        .btn-disabled {
            background-color: #e2e8f0;
            color: #94a3b8;
            padding: 9px 18px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 700;
            font-size: 0.85em;
            cursor: not-allowed;
            pointer-events: none;
            display: inline-block;
        }
    </style>
</head>
<body>

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
            <div class="container mx-auto px-4 flex space-x-8 text-xs font-bold uppercase tracking-wider whitespace-nowrap text-gray-600 py-3 items-center">
                <a href="gioithieu.php" class="hover:text-rose-600 transition">Giới thiệu</a>
                <a href="index.php" class="hover:text-rose-600 transition flex items-center gap-1"><i class="fa-solid fa-house"></i> Trang chủ</a>
                <a href="donhangcuatoi.php" class="text-rose-600 border-b-2 border-rose-600 pb-1">Đơn hàng của tôi</a>
                <a href="sanpham_damua.php" class="hover:text-rose-600 transition">Lịch sử đơn mua</a>
                <a href="voucher.php" class="hover:text-rose-600 transition">Voucher</a>
                <a href="profile.php" class="hover:text-rose-600 transition">Hồ sơ</a>
                <a href="yeuthich.php" class="hover:text-rose-600 transition">Yêu thích</a>
            </div>
        </nav>
    </header>

<div class="container-box">
    <a href="donhangcuatoi.php" class="btn-back"><i class="fa-solid fa-chevron-left text-xs"></i> Quay lại danh sách đơn hàng</a>

    <div class="header-detail">
        <h2><i class="fa-solid fa-file-invoice-dollar"></i> CHI TIẾT ĐƠN HÀNG</h2>
        <span class="text-xs text-slate-400">Mã đơn: <b class="text-slate-800">#<?php echo htmlspecialchars($madh); ?></b></span>
    </div>

    <!-- KHỐI THÔNG TIN ĐƠN HÀNG & KHÁCH HÀNG -->
    <div class="info-grid">
        <!-- Cột 1: Thông tin trạng thái & Thời gian -->
        <div class="info-card">
            <h4><i class="fa-solid fa-clock text-rose-500 mr-1"></i> Thông tin đơn hàng</h4>
            <div><b>Ngày đặt:</b> <?php echo date('d/m/Y H:i', strtotime($info_dh['ngaydat'])); ?></div>
            <div><b>Dự kiến giao:</b> <?php echo date('d/m/Y', strtotime($info_dh['ngaydat'] . ' + 7 days')); ?></div>
            <div>
                <b>Trạng thái:</b> 
                <?php 
                    $stt = $info_dh['trangthai'];
                    if ($stt == 'Chờ xác nhận') echo '<span class="text-amber-600 font-bold">Chờ xác nhận</span>';
                    elseif ($stt == 'Đã xác nhận') echo '<span class="text-blue-600 font-bold">Đã xác nhận</span>';
                    elseif ($stt == 'Đang giao') echo '<span class="text-cyan-600 font-bold">Đang giao</span>';
                    elseif ($stt == 'Đã giao') echo '<span class="text-emerald-600 font-bold">Đã giao</span>';
                    elseif ($stt == 'Đã hủy') echo '<span class="text-rose-600 font-bold">Đã hủy</span>';
                    else echo '<span class="font-bold">'.htmlspecialchars($stt).'</span>';
                ?>
            </div>
            <div><b>Thanh toán:</b> <?php echo htmlspecialchars($info_dh['ptthanhtoan'] ?? $info_dh['phuongthucthanhtoan'] ?? 'Thanh toán khi nhận hàng (COD)'); ?></div>
        </div>

        <!-- Cột 2: Thông tin người nhận lấy ưu tiên từ đơn hàng hoặc bảng khachhang -->
        <div class="info-card">
            <h4><i class="fa-solid fa-location-dot text-rose-500 mr-1"></i> Địa chỉ nhận hàng</h4>
            <div><b>Người nhận:</b> <?php echo htmlspecialchars($info_dh['hoten'] ?? $info_kh['hoten'] ?? $_SESSION['username'] ?? 'Khách hàng'); ?></div>
            <div><b>Số điện thoại:</b> <?php echo htmlspecialchars($info_dh['sdt'] ?? $info_dh['sodt'] ?? $info_kh['sdt'] ?? 'Chưa cập nhật'); ?></div>
            <div><b>Địa chỉ:</b> <?php echo htmlspecialchars($info_dh['diachigiaohang'] ?? $info_dh['diachi'] ?? $info_kh['diachi'] ?? 'Chưa cập nhật'); ?></div>
            <?php if(!empty($info_dh['ghichu'])): ?>
                <div><b>Ghi chú:</b> <span class="italic text-gray-500"><?php echo htmlspecialchars($info_dh['ghichu']); ?></span></div>
            <?php endif; ?>
        </div>
    </div>

    <!-- BẢNG DANH SÁCH SẢN PHẨM -->
    <table>
        <thead>
            <tr>
                <th>Sản phẩm</th>
                <th style="text-align: center;">SL</th>
                <th style="text-align: right;">Đơn giá</th>
                <th style="text-align: right;">Thành tiền</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $subtotal = 0;
            while($item = mysqli_fetch_assoc($res_chitiet)): 
                $thanhtien = $item['soluong'] * $item['dongia'];
                $subtotal += $thanhtien;
                $img_path = !empty($item['duongdan']) ? $item['duongdan'] : 'uploads/default.jpg';
            ?>
                <tr>
                    <td>
                        <div style="display:flex; align-items:center; gap:12px;">
                            <img src="/Shopbanhang/<?php echo htmlspecialchars($img_path); ?>" 
                                 width="55" height="55" 
                                 style="border-radius: 8px; object-fit: cover; border: 1px solid #f1f5f9;" 
                                 onerror="this.src='https://via.placeholder.com/55'">

                            <div>
                                <div class="product-name"><?php echo htmlspecialchars($item['tensp']); ?></div>
                                <small style="color:#94a3b8;">Mã SP: #<?php echo htmlspecialchars($item['masp']); ?></small>
                            </div>
                        </div>
                    </td>
                    <td style="text-align: center; font-weight: 600; color: #475569;">x<?php echo $item['soluong']; ?></td>
                    <td style="text-align: right;" class="price"><?php echo number_format($item['dongia'], 0, ',', '.'); ?>đ</td>
                    <td style="text-align: right;" class="price"><?php echo number_format($thanhtien, 0, ',', '.'); ?>đ</td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <!-- TỔNG KẾT VÀ THAO TÁC -->
    <div class="summary flex flex-wrap justify-between items-end gap-4">
        <div>
            <?php if($info_dh['trangthai'] == 'Chờ xác nhận'): ?>
                <a href="huydonhang.php?madh=<?php echo $madh; ?>" class="btn-cancel-red" onclick="return confirm('Bạn có chắc chắn muốn hủy đơn hàng này?')">
                    <i class="fa-solid fa-xmark mr-1"></i> Hủy đơn hàng này
                </a>
            <?php else: ?>
                <a href="#" class="btn-disabled"><i class="fa-solid fa-lock mr-1"></i> Không thể hủy đơn</a>
            <?php endif; ?>
        </div>

        <div class="text-right space-y-1">
            <p class="text-xs text-slate-500">Phí vận chuyển: <b class="text-emerald-600">Miễn phí</b></p>
            <p class="text-xs text-slate-500">Tổng giá trị sản phẩm: <b><?php echo number_format($subtotal, 0, ',', '.'); ?>đ</b></p>
            <p class="text-sm font-bold text-slate-700 pt-1">Thành tiền: <span class="total-pay"><?php echo number_format($info_dh['tongtien'], 0, ',', '.'); ?>đ</span></p>
        </div>
    </div>
    
    <hr style="border: none; border-top: 1px dashed #e2e8f0; margin: 25px 0 15px 0;">
    <p style="text-align: center; color: #94a3b8; font-size: 0.8em;">Cảm ơn bạn đã tin tưởng mua sắm tại Triple Beauty!</p>
</div>

    <!-- FOOTER TIÊU CHUẨN -->
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