<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/Shopbanhang/conn.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/Shopbanhang/func.php';

if (!isset($_SESSION['userid'])) {
    header("Location: login.php");
    exit;
}

$userid = $_SESSION['userid'];

// 1. Lấy thông tin chi tiết (bao gồm ngaysinh và wallet_address)
$sql_user = "SELECT kh.*, u.username, u.email as auth_email 
             FROM users u 
             LEFT JOIN khachhang kh ON u.id = kh.id_users 
             WHERE u.id = '$userid'";
$res_user = mysqli_query($conn, $sql_user);
$user_info = mysqli_fetch_assoc($res_user);

$display_name = !empty($user_info['hoten']) ? $user_info['hoten'] : "Thành viên mới";
$display_sdt = $user_info['sdt'] ?? '';
$display_diachi = $user_info['diachi'] ?? '';
$display_ngaysinh = $user_info['ngaysinh'] ?? '';
$display_wallet = $user_info['wallet_address'] ?? ''; // Lấy địa chỉ ví hiện tại

// 3. Tính tổng tiền các đơn đã giao thành công
$tong_mua = 0;

if (!empty($user_info['makh'])) {
    $sql_tong_mua = "
        SELECT COALESCE(SUM(tongtien), 0) AS tong_mua
        FROM donhang
        WHERE makh = ?
        AND trangthai = 'Đã giao'
    ";

    $stmt_tong_mua = mysqli_prepare($conn, $sql_tong_mua);
    mysqli_stmt_bind_param($stmt_tong_mua, "s", $user_info['makh']);
    mysqli_stmt_execute($stmt_tong_mua);

    $result_tong_mua = mysqli_stmt_get_result($stmt_tong_mua);
    $row_tong_mua = mysqli_fetch_assoc($result_tong_mua);

    $tong_mua = (float)($row_tong_mua['tong_mua'] ?? 0);
}

// 4. Tính điểm tích lũy
$diem_tich_luy = floor($tong_mua / 10000);

// 5. Lấy phân loại khách hàng trực tiếp từ bảng loai_khachhang
$loai_khach = "Thành viên mới";
$uudai_phantram = 0;
$mo_ta_loai = "";
$dieukien_hien_tai = 0;
$dieukien_tiep_theo = null;
$ten_loai_tiep_theo = null;

$sql_loai_khach = "
    SELECT *
    FROM loai_khachhang
    WHERE dieukien_chitieu <= ?
    ORDER BY dieukien_chitieu DESC
    LIMIT 1
";

$stmt_loai = mysqli_prepare($conn, $sql_loai_khach);
mysqli_stmt_bind_param($stmt_loai, "d", $tong_mua);
mysqli_stmt_execute($stmt_loai);

$result_loai = mysqli_stmt_get_result($stmt_loai);

if ($row_loai = mysqli_fetch_assoc($result_loai)) {
    $loai_khach = $row_loai['tenloai'];
    $uudai_phantram = (int)$row_loai['uudai_phantram'];
    $mo_ta_loai = $row_loai['mota'];
    $dieukien_hien_tai = (float)$row_loai['dieukien_chitieu'];
}

// Lấy hạng tiếp theo
$sql_loai_tiep = "
    SELECT *
    FROM loai_khachhang
    WHERE dieukien_chitieu > ?
    ORDER BY dieukien_chitieu ASC
    LIMIT 1
";

$stmt_tiep = mysqli_prepare($conn, $sql_loai_tiep);
mysqli_stmt_bind_param($stmt_tiep, "d", $tong_mua);
mysqli_stmt_execute($stmt_tiep);

$result_tiep = mysqli_stmt_get_result($stmt_tiep);

if ($row_tiep = mysqli_fetch_assoc($result_tiep)) {
    $dieukien_tiep_theo = (float)$row_tiep['dieukien_chitieu'];
    $ten_loai_tiep_theo = $row_tiep['tenloai'];

    if ($dieukien_tiep_theo > 0) {
        $phan_tram_dat_duoc = ($tong_mua / $dieukien_tiep_theo) * 100;
    } else {
        $phan_tram_dat_duoc = 100;
    }

    if ($phan_tram_dat_duoc > 100) {
        $phan_tram_dat_duoc = 100;
    }

    $con_lai_tien = $dieukien_tiep_theo - $tong_mua;
} else {
    $phan_tram_dat_duoc = 100;
    $con_lai_tien = 0;
}

// 2. Xử lý cập nhật thông tin & ví MetaMask
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $hoten = mysqli_real_escape_string($conn, $_POST['hoten']);
    $sdt = mysqli_real_escape_string($conn, $_POST['sdt']);
    $diachi = mysqli_real_escape_string($conn, $_POST['diachi']);
    $ngaysinh = trim($_POST['ngaysinh'] ?? '');
    $wallet_address = trim($_POST['wallet_address'] ?? ''); // Lấy địa chỉ ví gửi từ JS

    $ngaysinh_sql = ($ngaysinh === '') ? "NULL" : "'$ngaysinh'";

    $check = mysqli_query($conn, "SELECT id_users FROM khachhang WHERE id_users = '$userid'");
    if (mysqli_num_rows($check) > 0) {
        // Cập nhật kèm theo wallet_address nếu có thay đổi
        if (!empty($wallet_address)) {
            $sql_action = "UPDATE khachhang SET hoten='$hoten', sdt='$sdt', diachi='$diachi', ngaysinh=$ngaysinh_sql, wallet_address='$wallet_address' WHERE id_users='$userid'";
        } else {
            $sql_action = "UPDATE khachhang SET hoten='$hoten', sdt='$sdt', diachi='$diachi', ngaysinh=$ngaysinh_sql WHERE id_users='$userid'";
        }
    } else {
        $email = $user_info['auth_email'];
        $sql_action = "INSERT INTO khachhang (id_users, hoten, email, sdt, diachi, ngaysinh, wallet_address) VALUES ('$userid', '$hoten', '$email', '$sdt', '$diachi', $ngaysinh_sql, '$wallet_address')";
    }
    
    if (mysqli_query($conn, $sql_action)) {
        echo "<script>alert('Đã lưu thay đổi hồ sơ và ví thành công!'); window.location.href='profile.php';</script>";
    }
}

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
$display_wallet = $display_wallet ?? '';
$masked_wallet = '';
if (!empty($display_wallet)) {
    $masked_wallet = substr($display_wallet, 0, 6) . '••••••••••••' . substr($display_wallet, -5);
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hồ sơ cá nhân | Triple Beauty</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');
        body { font-family: 'Inter', sans-serif; background: #f8fafc; }
        .sidebar-link { transition: all 0.2s ease-in-out; }
        .sidebar-link.active { background: #fff1f2; color: #e11d48; box-shadow: inset 3px 0 0 #e11d48; }
        .profile-input { transition: all 0.2s ease-in-out; }
        .no-scrollbar::-webkit-scrollbar { display: none; }
    </style>
</head>
<body class="text-slate-800 antialiased flex flex-col min-h-screen">

    <!-- HEADER -->
    <header class="sticky top-0 z-50 bg-white shadow-sm">
        <div class="bg-gradient-to-r from-rose-600 to-pink-600 py-3 text-white">
            <div class="container mx-auto px-4 flex items-center justify-between">
                <a href="index.php" class="flex flex-col leading-none">
                    <span class="text-2xl md:text-3xl font-black italic tracking-tighter uppercase">Triple Beauty</span>
                    <span class="text-[9px] font-medium tracking-widest uppercase opacity-90">Cosmetics & Skincare</span>
                </a>

                <form action="timkiem.php" method="GET" class="hidden md:flex flex-1 mx-8 relative max-w-xl">
                    <input type="text" name="keyword" placeholder="Tìm kiếm mỹ phẩm, thương hiệu, danh mục..." 
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

                    <a href="giohang.php" class="relative cursor-pointer bg-white/20 p-2.5 rounded-xl hover:bg-white/30 transition flex items-center justify-center">
                        <i class="fa fa-shopping-bag text-lg text-white"></i>
                        <?php if ($cartCount > 0): ?>
                        <span class="absolute -top-1 -right-1 bg-amber-400 text-gray-900 font-bold text-[10px] w-5 h-5 flex items-center justify-center rounded-full shadow-md border-2 border-rose-600">
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
                <a href="voucher.php" class="hover:text-rose-600 transition">Voucher</a>
                <a href="profile.php" class="text-rose-600 border-b-2 border-rose-600 pb-1">Hồ sơ</a>
                <a href="diemthuong.php" class="hover:text-rose-600 transition">Xem điểm thưởng</a>
                <a href="yeuthich.php" class="hover:text-rose-600 transition">Yêu thích</a>
            </div>
        </nav>
    </header>

    <!-- Main Content -->
    <div class="flex-grow max-w-7xl w-full mx-auto px-6 py-8">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
            
            <!-- Sidebar -->
            <aside class="lg:col-span-3 space-y-6">
                <div class="bg-white rounded-2xl p-6 shadow-md border border-slate-100 text-center">
                    <div class="w-20 h-20 rounded-2xl bg-gradient-to-tr from-rose-500 to-pink-400 p-0.5 shadow-md mx-auto">
                        <div class="w-full h-full bg-white rounded-[0.9rem] flex items-center justify-center text-rose-500 text-3xl font-black">
                            <?= strtoupper(mb_substr($display_name, 0, 1)) ?>
                        </div>
                    </div>
                    <h3 class="mt-4 font-bold text-lg text-slate-900 truncate"><?= htmlspecialchars($display_name) ?></h3>
                    <p class="text-xs font-medium text-slate-400 tracking-tight mt-0.5">@<?= htmlspecialchars($user_info['username']) ?></p>

                    <div class="mt-3 inline-flex items-center gap-1.5 px-3 py-1 bg-rose-50 text-rose-700 rounded-full text-[10px] font-black shadow-xs">
                        <i class="fa-solid fa-user"></i>
                        <span><?= htmlspecialchars($loai_khach) ?></span>
                    </div>
                    
                    <nav class="mt-6 space-y-1.5 text-left">
                        <a href="profile.php" class="sidebar-link active flex items-center gap-3 px-3.5 py-2.5 font-bold text-xs rounded-xl">
                            <i class="fa-regular fa-user-circle text-base"></i> Thông tin tài khoản
                        </a>
                        <a href="sanpham_damua.php" class="sidebar-link flex items-center gap-3 px-3.5 py-2.5 text-slate-500 hover:bg-slate-50 font-bold text-xs rounded-xl">
                            <i class="fa-solid fa-clock-rotate-left text-base"></i> Lịch sử mua hàng
                        </a>
                        <a href="yeuthich.php" class="sidebar-link flex items-center gap-3 px-4 py-3 text-slate-500 hover:text-rose-600 font-bold text-xs rounded-xl transition-all">
                            <i class="fa-regular fa-heart w-4"></i> Yêu thích
                        </a>
                        <hr class="my-2 border-slate-100">
                        <a href="logout.php" class="sidebar-link flex items-center gap-3 px-3.5 py-2.5 text-rose-400 hover:bg-rose-50 hover:text-rose-600 font-bold text-xs rounded-xl">
                            <i class="fa-solid fa-power-off text-base"></i> Đăng xuất
                        </a>                
                    </nav>
                </div>
            </aside>

            <!-- Main Content Area -->
            <main class="lg:col-span-9 space-y-6">
                
                <!-- BẢNG ĐIỂM & HẠNG -->
                <div class="bg-gradient-to-br from-rose-500 to-pink-600 rounded-2xl px-6 py-4 text-white shadow-md relative overflow-hidden">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center relative z-10">
                        <div>
                            <span class="text-[10px] uppercase tracking-wider font-semibold opacity-80 block">Hạng thành viên hiện tại</span>
                            <div class="text-base font-black flex items-center gap-1.5 mt-0.5">
                                <i class="fa-solid fa-crown text-amber-300"></i>
                                <span><?= htmlspecialchars($loai_khach) ?></span>
                            </div>
                        </div>
                        <div class="border-t md:border-t-0 md:border-l border-white/20 pt-2.5 md:pt-0 md:pl-4">
                            <span class="text-[10px] uppercase tracking-wider font-semibold opacity-80 block">Tổng điểm tích lũy</span>
                            <div class="text-lg font-black flex items-center gap-1.5 mt-0.5">
                                <i class="fa-solid fa-coins text-amber-300 text-sm"></i>
                                <span><?= number_format($diem_tich_luy) ?> <span class="text-xs font-medium">điểm</span></span>
                            </div>
                        </div>
                        <div class="border-t md:border-t-0 md:border-l border-white/20 pt-2.5 md:pt-0 md:pl-4">
                            <span class="text-[10px] uppercase tracking-wider font-semibold opacity-80 block">Tổng chi tiêu thành công</span>
                            <div class="text-base font-black mt-0.5"><?= number_format($tong_mua, 0, ',', '.') ?> đ</div>
                        </div>
                    </div>
                </div>

                <!-- KHUNG THÔNG TIN CÁ NHÂN & KẾT NỐI VÍ METAMASK -->
                <div class="bg-white rounded-2xl shadow-md border border-slate-100 overflow-hidden mt-4">
                    <div class="px-6 py-4 bg-slate-900 text-white flex justify-between items-center">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-white/10 rounded-xl flex items-center justify-center text-rose-400 text-lg border border-white/10">
                                <i class="fa-solid fa-id-card-clip"></i>
                            </div>
                            <div>
                                <h2 class="text-base font-black tracking-tight">Hồ Sơ Cá Nhân & Ví Web3</h2>
                                <p class="text-[11px] text-slate-400">Quản lý thông tin tài khoản và liên kết ví MetaMask nhận điểm thưởng</p>
                            </div>
                        </div>
                        <button type="button" onclick="togglePasswordModal()" class="px-3.5 py-2 bg-white/10 hover:bg-white/20 text-white rounded-xl text-xs font-bold transition-all border border-white/10 flex items-center gap-2">
                            <i class="fa-solid fa-lock text-rose-400"></i> Đổi mật khẩu
                        </button>
                    </div>

                    <div class="p-6">
                        <form id="profileForm" method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4">
                            
                            <!-- Input ẩn lưu địa chỉ ví MetaMask -->
                            <input type="hidden" name="wallet_address" id="wallet_address" value="<?= htmlspecialchars($display_wallet) ?>">

                            <!-- Họ và tên -->
                            <div class="space-y-1.5">
                                <label class="flex items-center gap-1.5 text-[11px] font-bold text-slate-500 uppercase tracking-wider ml-0.5">
                                    <i class="fa-solid fa-signature text-rose-500"></i> Họ và tên
                                </label>
                                <input type="text" name="hoten" id="hoten" value="<?= htmlspecialchars($display_name) ?>" 
                                       class="profile-input w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-700 text-xs font-semibold focus:outline-none focus:bg-white cursor-not-allowed" readonly required>
                            </div>

                            <!-- Email -->
                            <div class="space-y-1.5">
                                <label class="flex items-center gap-1.5 text-[11px] font-bold text-slate-500 uppercase tracking-wider ml-0.5">
                                    <i class="fa-solid fa-envelope text-rose-500"></i> Địa chỉ Email (cố định)
                                </label>
                                <input type="text" value="<?= $user_info['auth_email'] ?>" 
                                       class="w-full px-3.5 py-2.5 bg-slate-100/70 border border-slate-200/60 rounded-xl text-slate-400 text-xs font-medium cursor-not-allowed" readonly>
                            </div>

                            <!-- Số điện thoại -->
                            <div class="space-y-1.5">
                                <label class="flex items-center gap-1.5 text-[11px] font-bold text-slate-500 uppercase tracking-wider ml-0.5">
                                    <i class="fa-solid fa-phone text-rose-500"></i> Số điện thoại
                                </label>
                                <input type="text" name="sdt" id="sdt" value="<?= htmlspecialchars($display_sdt) ?>" placeholder="Chưa cập nhật"
                                       class="profile-input w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-700 text-xs font-semibold focus:outline-none focus:bg-white cursor-not-allowed" readonly>
                            </div>

                            <!-- Ngày sinh -->
                            <div class="space-y-1.5">
                                <label class="flex items-center gap-1.5 text-[11px] font-bold text-slate-500 uppercase tracking-wider ml-0.5">
                                    <i class="fa-solid fa-cake-candles text-rose-500"></i> Ngày sinh
                                </label>
                                <input type="date" name="ngaysinh" id="ngaysinh" value="<?= htmlspecialchars($display_ngaysinh ?? '') ?>" 
                                       class="profile-input w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-700 text-xs font-semibold focus:outline-none focus:bg-white cursor-not-allowed" readonly>
                            </div>

                           <!-- PHẦN LIÊN KẾT / ĐỔI VÍ METAMASK -->
<div class="md:col-span-2 bg-amber-50/60 border border-amber-200/80 p-4 rounded-xl space-y-2">
    <label class="flex items-center gap-1.5 text-[11px] font-bold text-amber-800 uppercase tracking-wider">
        <i class="fa-brands fa-ethereum text-amber-600"></i> Địa chỉ ví MetaMask nhận điểm Blockchain (CLT)
    </label>
    
    <div class="flex flex-col sm:flex-row items-center gap-3">
        <!-- Khung chứa input ví và nút con mắt ẩn/hiện -->
        <div class="relative w-full">
            <input type="text" id="display_wallet" 
       data-full-wallet="<?= htmlspecialchars($display_wallet) ?>"
       value="<?= htmlspecialchars($display_wallet ? $masked_wallet : 'Chưa kết nối ví MetaMask') ?>" 
       class="w-full px-3.5 py-2.5 pr-10 bg-white border border-amber-200 rounded-xl text-slate-700 text-xs font-mono font-bold select-all" readonly>
            
            <!-- Nút con mắt ẩn/hiện thông tin -->
            <?php if (!empty($display_wallet)): ?>
            <button type="button" onclick="toggleWalletVisibility()" 
                    class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-amber-700 transition text-sm focus:outline-none"
                    title="Ẩn/Hiện địa chỉ ví">
                <i id="eye-wallet-icon" class="fa-regular fa-eye-slash"></i>
            </button>
            <?php endif; ?>
        </div>
        
        <!-- Nút bấm gọi hàm kết nối ví -->
        <button type="button" id="connectWalletBtn" onclick="connectWalletProfile()" disabled
                class="w-full sm:w-auto px-5 py-2.5 bg-amber-500 hover:bg-amber-600 text-white rounded-xl font-bold text-xs uppercase tracking-wider transition shadow-sm whitespace-nowrap opacity-50 cursor-not-allowed">
            <i class="fa-solid fa-link mr-1"></i> Kết nối ví mới
        </button>
    </div>
    <p class="text-[10px] text-amber-700 italic">Nhấn vào "Sửa hồ sơ" bên dưới để bật tính năng thay đổi hoặc liên kết ví MetaMask mới.</p>
</div>

                            <!-- Địa chỉ nhận hàng -->
                            <div class="md:col-span-2 space-y-1.5">
                                <label class="flex items-center gap-1.5 text-[11px] font-bold text-slate-500 uppercase tracking-wider ml-0.5">
                                    <i class="fa-solid fa-location-dot text-rose-500"></i> Địa chỉ nhận hàng
                                </label>
                                <textarea name="diachi" id="diachi" rows="2" placeholder="Chưa cập nhật địa chỉ giao hàng"
                                      class="profile-input w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-700 text-xs font-semibold focus:outline-none focus:bg-white cursor-not-allowed resize-none" readonly><?= htmlspecialchars($display_diachi) ?></textarea>
                            </div>

                            <!-- Actions -->
                            <div class="md:col-span-2 mt-3 pt-4 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-3">
                                <p id="status-text" class="text-[11px] font-medium text-slate-400 flex items-center gap-1.5">
                                    <i class="fa-solid fa-circle-info"></i> Cập nhật lần cuối: <span class="font-bold text-slate-600"><?= date("d/m/Y") ?></span>
                                </p>
                                <div class="flex gap-2.5 w-full sm:w-auto">
                                    <button type="button" id="editBtn" onclick="enableEditing()" 
                                            class="flex-1 sm:flex-none px-6 py-2.5 bg-slate-900 text-white rounded-xl font-bold text-xs uppercase tracking-wider hover:bg-rose-600 transition shadow-sm">
                                        <i class="fa-solid fa-pen-to-square mr-1"></i> Sửa hồ sơ
                                    </button>
                                    <button type="submit" name="update_profile" id="saveBtn" disabled 
                                            class="flex-1 sm:flex-none px-6 py-2.5 bg-slate-200 text-slate-400 rounded-xl font-bold text-xs uppercase tracking-wider transition cursor-not-allowed">
                                        <i class="fa-solid fa-check mr-1"></i> Lưu thay đổi
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-black text-white pt-10 pb-6 mt-16 border-t border-slate-900">
        <div class="max-w-7xl mx-auto px-6 text-center text-xs text-slate-500 font-bold uppercase tracking-wider">
            <p>&copy; 2026 TRIPLE BEAUTY VIỆT NAM. ĐÃ ĐĂNG KÝ BẢN QUYỀN.</p>
        </div>
    </footer>

    <!-- JavaScript xử lý form & MetaMask -->
    <script>
        function enableEditing() {
            const fields = ['hoten', 'sdt', 'ngaysinh', 'diachi'];
            const saveBtn = document.getElementById('saveBtn');
            const editBtn = document.getElementById('editBtn');
            const walletBtn = document.getElementById('connectWalletBtn');
            const statusText = document.getElementById('status-text');

            fields.forEach(id => {
                const el = document.getElementById(id);
                el.readOnly = false;
                el.classList.remove('bg-slate-50', 'text-slate-700', 'cursor-not-allowed');
                el.classList.add('bg-white', 'border-rose-400', 'ring-2', 'ring-rose-500/10', 'text-slate-900');
            });

            // Kích hoạt nút bấm kết nối ví khi ở chế độ chỉnh sửa
            walletBtn.disabled = false;
            walletBtn.classList.remove('opacity-50', 'cursor-not-allowed');

            saveBtn.disabled = false;
            saveBtn.classList.remove('bg-slate-200', 'text-slate-400', 'cursor-not-allowed');
            saveBtn.classList.add('bg-rose-600', 'text-white', 'hover:bg-rose-700', 'shadow-sm');
            
            editBtn.classList.add('hidden');
            statusText.innerHTML = "<span class='flex items-center gap-1.5 text-rose-600 font-bold animate-pulse'><i class='fa-solid fa-triangle-exclamation'></i> Đang chỉnh sửa hồ sơ & liên kết ví...</span>";
            
            document.getElementById('hoten').focus();
        }

        // Hàm tương tác MetaMask trực tiếp tại trang Profile
        async function connectWalletProfile() {
            if (typeof window.ethereum !== "undefined") {
                try {
                    const accounts = await window.ethereum.request({ method: "eth_requestAccounts" });
                    const userWallet = accounts[0];

                    // Gán vào input ẩn và input hiển thị
                    document.getElementById("wallet_address").value = userWallet;
                    document.getElementById("display_wallet").value = userWallet;

                    alert("Đã kết nối ví MetaMask thành công! Nhấn 'Lưu thay đổi' để hoàn tất.");
                } catch (error) {
                    console.error("Lỗi kết nối ví:", error);
                    alert("Bạn đã hủy kết nối ví MetaMask.");
                }
            } else {
                alert("Vui lòng cài đặt tiện ích mở rộng MetaMask trên trình duyệt của bạn!");
            }
        }

        function toggleWalletVisibility() {
    const input = document.getElementById('display_wallet');
    const icon = document.getElementById('eye-wallet-icon');
    
    if (!input) return;

    // Lấy giá trị đầy đủ từ thuộc tính data-full-wallet
    const fullWallet = input.getAttribute('data-full-wallet');
    
    // Tạo dạng ẩn rút gọn (ví dụ: giữ lại 6 ký tự đầu và 5 ký tự cuối)
    let maskedWallet = fullWallet;
    if (fullWallet && fullWallet.length > 11) {
        maskedWallet = fullWallet.substring(0, 6) + '••••••••••••' + fullWallet.substring(fullWallet.length - 5);
    }

    // Kiểm tra trạng thái hiện tại dựa vào icon hoặc giá trị của input
    const isHidden = icon.classList.contains('fa-eye-slash');

    if (isHidden) {
        // Đang ẩn -> Chuyển sang HIỆN địa chỉ đầy đủ
        input.value = fullWallet;
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
        icon.title = "Ẩn địa chỉ ví";
    } else {
        // Đang hiện -> Chuyển sang ẨN (rút gọn)
        input.value = maskedWallet;
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
        icon.title = "Hiện địa chỉ ví";
    }
}
    </script>
</body>
</html>