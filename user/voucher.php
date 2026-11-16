<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/Shopbanhang/conn.php';

if (!isset($_SESSION['userid'])) {
    header("Location: login.php");
    exit;
}

$userid = $_SESSION['userid'];

// Hàm format tiền tệ đầy đủ giống bên giỏ hàng/thanh toán
function formatVND($amount) {
    return number_format($amount, 0, ',', '.') . ' VND';
}

// Lấy mã khách hàng + ngày sinh + họ tên
$sql_kh = "
    SELECT makh, ngaysinh, hoten
    FROM khachhang
    WHERE id_users = ?
    LIMIT 1
";

$stmt_kh = mysqli_prepare($conn, $sql_kh);
mysqli_stmt_bind_param($stmt_kh, "i", $userid);
mysqli_stmt_execute($stmt_kh);

$result_kh = mysqli_stmt_get_result($stmt_kh);
$khachhang = mysqli_fetch_assoc($result_kh);

if (!$khachhang) {
    die("Không tìm thấy thông tin khách hàng.");
}

$makh = $khachhang['makh'];
$ngaysinh = $khachhang['ngaysinh'] ?? '';
$hoten = $khachhang['hoten'] ?? 'Khách hàng';

/*
|--------------------------------------------------------------------------
| TÍNH TỔNG TIỀN ĐÃ MUA
|--------------------------------------------------------------------------
| Chỉ tính những đơn đã giao thành công
*/
$sql_tongmua = "
    SELECT COALESCE(SUM(tongtien), 0) AS tong_mua
    FROM donhang
    WHERE makh = ?
    AND trangthai = 'Đã giao thành công'
";

$stmt_tongmua = mysqli_prepare($conn, $sql_tongmua);
mysqli_stmt_bind_param($stmt_tongmua, "s", $makh);
mysqli_stmt_execute($stmt_tongmua);

$result_tongmua = mysqli_stmt_get_result($stmt_tongmua);
$row_tongmua = mysqli_fetch_assoc($result_tongmua);

$tong_mua = (float)($row_tongmua['tong_mua'] ?? 0);

/*
|--------------------------------------------------------------------------
| PHÂN LOẠI KHÁCH HÀNG
|--------------------------------------------------------------------------
*/

$is_than_thiet = ($tong_mua >= 5000000);

// Kiểm tra khách mới (chưa có đơn hàng nào)
$sql_don = "
    SELECT COUNT(*) AS sodon
    FROM donhang
    WHERE makh = ?
";

$stmt_don = mysqli_prepare($conn, $sql_don);
mysqli_stmt_bind_param($stmt_don, "s", $makh);
mysqli_stmt_execute($stmt_don);

$result_don = mysqli_stmt_get_result($stmt_don);
$row_don = mysqli_fetch_assoc($result_don);

$is_khach_moi = (($row_don['sodon'] ?? 0) == 0);

// Ngày sinh
$today = date('m-d');
$birthday = '';
if (!empty($ngaysinh)) {
    $birthday = date('m-d', strtotime($ngaysinh));
}

/*
|--------------------------------------------------------------------------
| LẤY DANH SÁCH VOUCHER TỪ CƠ SỞ DỮ LIỆU
|--------------------------------------------------------------------------
*/
$vouchers = [];

$sql_vouchers = "
    SELECT v.*, l.tenloai 
    FROM vouchers v 
    LEFT JOIN loai_khachhang l ON v.id_loai = l.maloai
    WHERE v.ngay_ket_thuc >= CURDATE() AND v.soluong > v.dadung
    ORDER BY v.ngay_ket_thuc ASC
";

$result_vouchers = mysqli_query($conn, $sql_vouchers);

if ($result_vouchers) {
    while ($row = mysqli_fetch_assoc($result_vouchers)) {
        $ma_v = strtoupper($row['ma_voucher']);
        $id_loai = $row['id_loai'];
        
        // Nhân 1000 vì giá trị lưu trong CSDL tính theo đơn vị nghìn đồng
        $giatri_min = (float)($row['giatri_dh_toithieu'] ?? 0) * 1000;
        $giam_max = (float)($row['giam_toi_da'] ?? 0) * 1000;
        $loai_giam = $row['loai_giam'] ?? ''; 
        $hsd = date('d/m/Y', strtotime($row['ngay_ket_thuc']));
        $ten_loai_kh = strtoupper($row['tenloai'] ?? '');

        $title = "";
        $sub = "";
        $dieukien = "";

        // ==========================================
        // XÉT ĐIỀU KIỆN PHÂN LOẠI KHÁCH HÀNG & MÃ
        // ==========================================
        if (!empty($id_loai) && (strpos($ten_loai_kh, 'MỚI') !== false || strpos($ten_loai_kh, 'NEW') !== false || strpos($ma_v, 'NEW') !== false)) {
            if (!$is_khach_moi) continue; // Khách cũ thì bỏ qua voucher này
            $title = "Giảm " . $loai_giam . " cho khách hàng mới";
            $sub = "Áp dụng cho lần mua hàng đầu tiên";
            $dieukien = "Dành riêng cho tài khoản chưa phát sinh bất kỳ đơn hàng nào trên hệ thống. Giảm tối đa " . formatVND($giam_max) . ".";
        } 
        elseif (!empty($id_loai) && (strpos($ten_loai_kh, 'THÂN THIẾT') !== false || strpos($ten_loai_kh, 'VIP') !== false || strpos($ma_v, 'OLD') !== false || strpos($ma_v, 'VIP') !== false)) {
            if (!$is_than_thiet) continue; // Chưa đủ điều kiện thân thiết thì bỏ qua
            $title = "👑 Giảm " . $loai_giam . " khách hàng thân thiết";
            $sub = "Dành cho khách hàng đã mua từ " . formatVND(5000000);
            $dieukien = "Khách hàng phải có tổng giá trị các đơn hàng đã giao thành công từ " . formatVND(5000000) . " trở lên. Giảm tối đa " . formatVND($giam_max) . ".";
        } 
        elseif (!empty($id_loai) && (strpos($ten_loai_kh, 'SINH NHẬT') !== false || strpos($ten_loai_kh, 'BIRTH') !== false || strpos($ma_v, 'BIRTH') !== false)) {
            if (empty($birthday) || $today != $birthday) continue; // Chỉ hiện đúng ngày sinh nhật
            $title = "🎂 Giảm " . $loai_giam . " mừng sinh nhật";
            $sub = "Áp dụng trong ngày sinh nhật";
            $dieukien = "Món quà đặc biệt mừng sinh nhật bạn. Chỉ áp dụng duy nhất trong ngày sinh nhật của chủ tài khoản. Giảm tối đa " . formatVND($giam_max) . ".";
        } 
        elseif (!empty($id_loai)) {
            $title = "Giảm " . $loai_giam . " cho hội viên " . $row['tenloai'];
            $sub = "Dành riêng cho hạng thành viên: " . $row['tenloai'];
            $dieukien = "Áp dụng riêng cho phân loại khách hàng: " . $row['tenloai'] . ". Giảm tối đa " . formatVND($giam_max) . ".";
        }
        else {
            if ($giatri_min > 0) {
                $title = "Giảm " . $loai_giam . " cho đơn từ " . formatVND($giatri_min);
                $sub = "Áp dụng cho đơn hàng từ " . formatVND($giatri_min);
                $dieukien = "Giá trị giỏ hàng (tạm tính) phải đạt từ " . formatVND($giatri_min) . " trở lên mới có thể kích hoạt giảm. Giảm tối đa " . formatVND($giam_max) . ".";
            } else {
                $title = "Mã ưu đãi " . $ma_v;
                $sub = "Áp dụng toàn bộ hệ thống Triple Beauty";
                $dieukien = "Mã giảm giá áp dụng cho khách hàng thỏa mãn điều kiện hệ thống. Giảm tối đa " . formatVND($giam_max) . ".";
            }
        }

        $brand_display = "TRIPLE BEAUTY";
        if (!empty($row['tenloai'])) {
            $brand_display = "DÀNH CHO: " . strtoupper($row['tenloai']);
        }

        // Thêm vào danh sách hiển thị
        $vouchers[] = [
            "brand" => $brand_display,
            "title" => $title,
            "sub" => $sub,
            "hsd" => $hsd,
            "img" => "../images/tải xuống.png",
            "code" => $ma_v,
            "dieukien" => $dieukien
        ];
    }
}

// Tính số lượng giỏ hàng hỗ trợ cả hai kiểu SESSION ['cart'] hoặc ['giohang']
$cartCount = 0;
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cartCount += (int)($item['soluong'] ?? 1);
    }
} elseif (isset($_SESSION['giohang']) && is_array($_SESSION['giohang'])) {
    foreach ($_SESSION['giohang'] as $item) {
        $cartCount += (int)($item['soluong'] ?? 1);
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mã Giảm Giá | Triple Beauty</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #fcfcfd; }
        
        .bg-boshop { background-color: #800000; }
        .text-boshop { color: #800000; }

        .voucher-card {
            position: relative;
            background: #ffffff;
            border: 1px solid #f1f5f9;
            border-radius: 16px;
            display: flex;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02), 0 2px 4px -2px rgba(0, 0, 0, 0.02);
        }
        .voucher-card:hover { 
            transform: translateY(-4px); 
            box-shadow: 0 12px 20px -3px rgba(128, 0, 0, 0.08);
            border-color: #fecdd3;
        }

        .voucher-left {
            width: 110px;
            min-width: 110px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #fff1f2;
            border-right: 2px dashed #fecdd3;
            position: relative;
            padding: 16px;
        }

        .logo-circle {
            width: 64px;
            height: 64px;
            background: white;
            border-radius: 50%;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #ffe4e6;
            box-shadow: 0 2px 6px rgba(0,0,0,0.04);
        }

        .logo-circle img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            padding: 8px;
        }

        .voucher-left::before, .voucher-left::after {
            content: '';
            position: absolute;
            right: -8px;
            width: 16px;
            height: 16px;
            background: #fcfcfd;
            border-radius: 50%;
            z-index: 10;
        }
        .voucher-left::before { top: -8px; }
        .voucher-left::after { bottom: -8px; }

        .no-scrollbar::-webkit-scrollbar { display: none; }
    </style>
</head>
<body class="text-slate-700 flex flex-col min-h-screen">

    <!-- HEADER -->
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

        <nav class="border-b bg-white shadow-xs overflow-x-auto no-scrollbar">
            <div class="container mx-auto px-4 flex space-x-8 text-xs font-bold uppercase tracking-wider whitespace-nowrap text-gray-600 py-3 items-center">
                <a href="gioithieu.php" class="hover:text-rose-600 transition">Giới thiệu</a>
                <a href="index.php" class="hover:text-rose-600 transition flex items-center gap-1"><i class="fa-solid fa-house"></i> Trang chủ</a>
                <a href="donhangcuatoi.php" class="hover:text-rose-600 transition">Đơn hàng của tôi</a>
                <a href="sanpham_damua.php" class="hover:text-rose-600 transition">Lịch sử đơn mua</a>
                <a href="voucher.php" class="text-rose-600 border-b-2 border-rose-600 pb-1">Voucher</a>
                <a href="profile.php" class="hover:text-rose-600 transition">Hồ sơ</a>
                <a href="yeuthich.php" class="hover:text-rose-600 transition">Yêu thích</a>
            </div>
        </nav>
    </header>

    <main class="flex-grow max-w-7xl w-full mx-auto px-4 sm:px-6 py-8">
        
        <!-- Banner -->
        <section class="mb-10 rounded-3xl overflow-hidden shadow-xl relative bg-gradient-to-br from-rose-950 via-rose-900 to-slate-900 text-white p-8 md:p-12">
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(244,63,94,0.15),transparent_50%)]"></div>
            <div class="relative z-10 flex flex-col md:flex-row items-center justify-between gap-8">
                <div class="text-center md:text-left space-y-4 max-w-xl">
                    <span class="bg-rose-500/20 text-rose-300 border border-rose-500/30 text-xs font-black uppercase tracking-widest px-3 py-1.5 rounded-full inline-block">Săn Deal Đỉnh Cao</span>
                    <h2 class="text-3xl md:text-5xl font-black uppercase tracking-tight leading-none">Kho Voucher<br><span class="text-rose-400">Triple Beauty</span></h2>
                    <p class="text-sm md:text-base text-slate-300 font-medium leading-relaxed">Thu thập ngay các mã ưu đãi độc quyền được thiết kế riêng cho hạng thành viên và tài khoản của bạn!</p>
                </div>
                <div class="w-48 md:w-64 flex-shrink-0">
                    <img src="../images/3d-coupon-golden-coins-present-260nw-2653884681.webp" alt="Voucher Banner" class="w-full h-auto drop-shadow-2xl animate-pulse duration-1000">
                </div>
            </div>
        </section>

        <!-- Danh sách voucher -->
        <?php if (empty($vouchers)): ?>
            <div class="text-center py-16 bg-white rounded-2xl border border-slate-100 shadow-sm">
                <i class="fa-solid fa-ticket-slash text-4xl text-slate-300 mb-3"></i>
                <p class="text-slate-500 font-semibold text-sm">Hiện tại chưa có mã giảm giá nào phù hợp với tài khoản của bạn.</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                <?php foreach($vouchers as $v): ?>
                    <div class="voucher-card">
                        <div class="voucher-left flex flex-col items-center justify-center text-center">
                            <div class="logo-circle mb-1">
                                <img src="<?php echo $v['img']; ?>" alt="Logo">
                            </div>
                            <span class="text-[10px] font-bold text-rose-700 tracking-tight truncate w-full"><?php echo htmlspecialchars($v['code']); ?></span>
                        </div>
                        <div class="flex-1 p-4 flex flex-col justify-between">
                            <div>
                                <div class="flex justify-between items-start mb-1.5">
                                    <span class="text-[9px] font-black text-rose-600 uppercase tracking-widest bg-rose-50 px-2 py-0.5 rounded"><?php echo $v['brand']; ?></span>
                                    <span onclick="openVoucherModal(this)" 
                                        data-title="<?php echo htmlspecialchars($v['title']); ?>"
                                        data-hsd="<?php echo htmlspecialchars($v['hsd']); ?>"
                                        data-dieukien="<?php echo htmlspecialchars($v['dieukien']); ?>"
                                        class="text-[10px] text-slate-400 font-bold uppercase cursor-pointer hover:text-rose-600 transition">
                                        Điều kiện <i class="fa-solid fa-circle-info text-[9px]"></i>
                                    </span>
                                </div>
                                <h3 class="text-xs font-extrabold text-slate-800 line-clamp-2 leading-snug mb-1"><?php echo $v['title']; ?></h3>
                                <p class="text-[11px] text-slate-400 font-medium line-clamp-1"><?php echo $v['sub']; ?></p>
                            </div>
                            
                            <div class="flex justify-between items-center mt-3 pt-3 border-t border-slate-50">
                                <span class="text-[10px] text-slate-400 font-semibold">HSD: <?php echo $v['hsd']; ?></span>
                                <button onclick="saveVoucher(this, '<?php echo htmlspecialchars($v['code']); ?>')" class="bg-rose-600 text-white px-4 py-1.5 rounded-lg text-[11px] font-black uppercase hover:bg-rose-700 transition shadow-sm">Lưu</button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
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

    <!-- Modal Chi Tiết Điều Kiện Voucher -->
    <div id="voucherModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center hidden opacity-0 transition-opacity duration-300 p-4">
        <div class="bg-white rounded-2xl w-full max-w-md overflow-hidden shadow-2xl transform scale-95 transition-transform duration-300 flex flex-col">
            <div class="bg-gradient-to-r from-rose-900 to-rose-700 p-5 text-white relative">
                <span class="bg-white/25 text-white px-2 py-0.5 rounded text-[10px] font-black uppercase">Ưu đãi độc quyền</span>
                <p id="modalVoucherTitle" class="text-lg font-black mt-1 leading-snug">Tên mã giảm giá</p>
                <p class="text-xs text-rose-200 mt-1"><i class="fa-regular fa-clock"></i> HSD: <span id="modalVoucherHsd">31/12/2026</span></p>
            </div>

            <div class="p-5 overflow-y-auto space-y-4 text-xs text-slate-600 leading-relaxed">
                <div>
                    <h4 class="font-black text-slate-800 uppercase tracking-wider mb-1.5 text-rose-600">📌 Điều kiện áp dụng</h4>
                    <p id="modalVoucherDieukien" class="bg-slate-50 p-3 rounded-xl border border-slate-100 font-medium text-slate-700">Nội dung điều kiện...</p>
                </div>
            </div>

            <div class="p-4 border-t border-slate-100 bg-slate-50 flex justify-end">
                <button type="button" onclick="closeVoucherModal()" class="w-full bg-slate-900 hover:bg-rose-600 text-white py-2.5 rounded-xl font-bold uppercase tracking-wider text-xs transition shadow-sm">
                    Đã hiểu
                </button>
            </div>
        </div>
    </div>

    <script>
    function saveVoucher(button, code) {
        if (button.classList.contains('saved')) return;
        
        navigator.clipboard.writeText(code).then(function() {
            button.textContent = 'Đã lưu';
            button.classList.remove('bg-rose-600', 'hover:bg-rose-700');
            button.classList.add('bg-emerald-600', 'cursor-default', 'saved');
        }).catch(function(err) {
            alert('Không thể sao chép mã. Vui lòng thử lại!');
        });
    }

    function openVoucherModal(element) {
        const title = element.getAttribute('data-title');
        const hsd = element.getAttribute('data-hsd');
        const dieukien = element.getAttribute('data-dieukien');

        document.getElementById('modalVoucherTitle').innerText = title;
        document.getElementById('modalVoucherHsd').innerText = hsd;
        document.getElementById('modalVoucherDieukien').innerHTML = dieukien;

        const modal = document.getElementById("voucherModal");
        modal.classList.remove("hidden");
        setTimeout(() => {
            modal.classList.add("opacity-100");
            modal.querySelector(".transform").classList.remove("scale-95");
            modal.querySelector(".transform").classList.add("scale-100");
        }, 10);
    }

    function closeVoucherModal() {
        const modal = document.getElementById("voucherModal");
        modal.classList.remove("opacity-100");
        modal.querySelector(".transform").classList.remove("scale-100");
        modal.querySelector(".transform").classList.add("scale-95");
        setTimeout(() => {
            modal.classList.add("hidden");
        }, 300);
    }

    document.getElementById("voucherModal").addEventListener("click", function(e) {
        if(e.target === this) closeVoucherModal();
    });
    </script>
</body>
</html>