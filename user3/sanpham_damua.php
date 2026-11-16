<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/Shopbanhang/conn.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/Shopbanhang/func.php';

if (!isset($_SESSION['userid'])) {
    header("Location: login.php");
    exit;
}

$userid = $_SESSION['userid'];

// 1. Lấy thông tin khách hàng an toàn
$sql_user = "SELECT * FROM khachhang WHERE id_users = '$userid'";
$res_user = mysqli_query($conn, $sql_user);
$user_info = mysqli_fetch_assoc($res_user);

if (!$user_info) {
    $user_info = ['hoten' => 'Khách hàng', 'makh' => ''];
}
$makh = $user_info['makh'];

// 2. Thống kê nhanh
$count_delivered = 0;
if ($makh) {
    $res_count = mysqli_query($conn, "SELECT COUNT(*) as total FROM donhang WHERE makh = '$makh' AND trangthai = 'Đã giao'");
    $count_delivered = mysqli_fetch_assoc($res_count)['total'];
}

// 3. Xử lý gửi đánh giá
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['gui_danhgia'])) {
    $masp = mysqli_real_escape_string($conn, $_POST['masp']);
    $madh = mysqli_real_escape_string($conn, $_POST['madh']);   
    $sosao = mysqli_real_escape_string($conn, $_POST['sosao']);
    $noidung = mysqli_real_escape_string($conn, $_POST['noidung']);
    $ngaydanhgia = date("Y-m-d H:i:s");

    $sql_ins = "INSERT INTO danhgiasp (makh, masp, madh, sosao, noidung, ngaydanhgia) 
                VALUES ('$makh', '$masp', '$madh', '$sosao', '$noidung', '$ngaydanhgia')";
    if (mysqli_query($conn, $sql_ins)) {
        echo "<script>alert('Cảm ơn bạn đã đánh giá!'); window.location.href='sanpham_damua.php';</script>";
    }
}

$cartCount = 0;
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cartCount += (int)($item['soluong'] ?? 1);
    }
}

// 4. Truy vấn danh sách sản phẩm đã giao
$sql_mua = "
SELECT dh.madh, dh.ngaydat, sp.masp, sp.tensp, ct.soluong, ct.dongia, 
       (ct.soluong * ct.dongia) AS thanhtien, MIN(ha.duongdan) AS hinh
FROM donhang dh
JOIN chitiet_donhang ct ON dh.madh = ct.madh
JOIN sanpham sp ON ct.masp = sp.masp
LEFT JOIN hinhanh_sanpham ha ON sp.masp = ha.masp
WHERE dh.makh = '$makh' AND dh.trangthai = 'Đã giao'
GROUP BY dh.madh, sp.masp
ORDER BY dh.ngaydat DESC";
$res_mua = mysqli_query($conn, $sql_mua);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lịch sử đơn hàng | Triple Beauty</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #fcfcfd; }
        .modal { display: none; position: fixed; z-index: 100; left: 0; top: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.7); backdrop-filter: blur(4px); }
        
        /* CSS thanh chọn sao đánh giá */
        .star-rating label { font-size: 1.5rem; color: #e2e8f0; cursor: pointer; transition: all 0.2s; padding: 0 2px; }
        .star-rating input:checked ~ label, .star-rating label:hover, .star-rating label:hover ~ label { color: #fbbf24; transform: scale(1.08); }
        .star-rating { display: flex; flex-direction: row-reverse; justify-content: center; }
        .star-rating input { display: none; }
        
        .sidebar-link.active { background-color: #fff1f2; color: #e11d48; border-right: 4px solid #e11d48; }
    </style>
</head>
<body class="text-slate-700">

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

        <!-- NAVIGATION -->
        <nav class="border-b bg-white shadow-xs overflow-x-auto no-scrollbar">
            <div class="container mx-auto px-4 flex space-x-8 text-xs font-bold uppercase tracking-wider whitespace-nowrap text-gray-600 py-3 items-center">
                <a href="gioithieu.php" class="hover:text-rose-600 transition">Giới thiệu</a>
                <a href="index.php" class="hover:text-rose-600 transition flex items-center gap-1"><i class="fa-solid fa-house"></i>Trang chủ</a>
                <a href="donhangcuatoi.php" class="hover:text-rose-600 transition">Đơn hàng của tôi</a>
                <a href="sanpham_damua.php" class="text-rose-600 border-b-2 border-rose-600 pb-1">Lịch sử đơn mua</a>
                <a href="voucher.php" class="hover:text-rose-600 transition">Voucher</a>
                <a href="profile.php" class="hover:text-rose-600 transition">Hồ sơ</a>
                <a href="yeuthich.php" class="hover:text-rose-600 transition">Yêu thích</a>
            </div>
        </nav>
    </header>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 py-8">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
            
            <!-- Cột bên trái: Thêm class sticky và top-20 để giữ cố định khi lướt -->
            <aside class="lg:col-span-3 space-y-4 sticky top-20">
                <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100">
                    <div class="flex items-center gap-3 mb-6 px-1">
                        <div class="w-10 h-10 rounded-xl bg-rose-50 flex items-center justify-center text-rose-500 text-base font-bold italic">
                            <?= $count_delivered ?>
                        </div>
                        <div>
                            <h3 class="text-xs font-bold text-slate-800 leading-tight">Đơn hoàn tất</h3>
                            <p class="text-[11px] text-slate-400 font-medium italic">Tổng cộng đã nhận</p>
                        </div>
                    </div>
                    
                    <nav class="space-y-1">
                        <hr class="my-2 border-slate-50">
                        <a href="profile.php" class="sidebar-link flex items-center gap-3 px-4 py-3 text-slate-500 hover:text-rose-600 font-bold text-xs rounded-xl transition-all">
                            <i class="fa-regular fa-user w-4"></i> Thông tin tài khoản
                        </a>
                        <a href="sanpham_damua.php" class="sidebar-link active flex items-center gap-3 px-4 py-3 text-rose-600 bg-rose-50 font-bold text-xs rounded-xl">
                            <i class="fa-solid fa-clock-rotate-left w-4"></i> Lịch sử mua hàng
                        </a>
                        <a href="yeuthich.php" class="sidebar-link flex items-center gap-3 px-4 py-3 text-slate-500 hover:text-rose-600 font-bold text-xs rounded-xl transition-all">
                            <i class="fa-regular fa-heart w-4"></i> Yêu thích
                        </a>
                        <a href="logout.php" class="sidebar-link flex items-center gap-3 px-4 py-3 text-rose-400 hover:text-rose-600 font-bold text-xs rounded-xl transition-all mt-2">
                            <i class="fa-solid fa-power-off w-4"></i> Đăng xuất
                        </a>
                    </nav>
                </div>

                <div class="bg-gradient-to-br from-slate-900 to-slate-800 rounded-2xl p-6 text-white relative overflow-hidden group shadow-lg">
                    <div class="relative z-10">
                        <h4 class="font-extrabold text-base leading-tight mb-1.5 italic">Beauty Club</h4>
                        <p class="text-slate-400 text-[11px] leading-relaxed mb-4">Đánh giá sản phẩm ngay để nhận tích điểm đổi quà!</p>
                        <a href="index.php" class="inline-block px-4 py-2 bg-rose-600 rounded-lg text-[10px] font-black uppercase tracking-wider hover:bg-rose-700 transition-all">Săn Deal Ngay</a>
                    </div>
                    <i class="fa-solid fa-crown absolute -right-4 -bottom-4 text-7xl text-white/5 group-hover:scale-110 transition-transform"></i>
                </div>
            </aside>

            <!-- Cột bên phải: Danh sách lịch sử đơn hàng tự do lướt -->
            <main class="lg:col-span-9">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-xl font-black text-slate-900 tracking-tight uppercase">Lịch sử đơn mua</h2>
                </div>

                <div class="space-y-4">
                    <?php if ($res_mua && mysqli_num_rows($res_mua) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($res_mua)): ?>
                            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden group hover:shadow-md transition-all duration-300">
                                <div class="bg-slate-50/70 px-6 py-3 border-b border-slate-100 flex flex-wrap justify-between items-center gap-3">
                                    <div class="flex items-center gap-3">
                                        <div class="bg-white px-2.5 py-0.5 rounded-md border border-slate-200 text-[10px] font-black text-rose-600">#<?= $row['madh'] ?></div>
                                        <span class="text-[11px] font-medium text-slate-400"><?= date('H:i - d/m/Y', strtotime($row['ngaydat'])) ?></span>
                                    </div>
                                    <div class="text-[10px] font-black text-emerald-600 uppercase flex items-center gap-1.5">
                                        <i class="fa-solid fa-circle-check"></i> Đã giao hoàn tất
                                    </div>
                                </div>

                                <div class="p-5 flex flex-col md:flex-row items-center gap-5">
                                    <div class="w-20 h-20 rounded-xl overflow-hidden border border-slate-100 p-0.5 bg-slate-50 flex-shrink-0">
                                        <img src="/Shopbanhang/<?= htmlspecialchars($row['hinh']) ?>" class="w-full h-full object-cover rounded-lg">
                                    </div>
                                    <div class="flex-1 text-center md:text-left">
                                        <h3 class="font-bold text-slate-800 text-sm group-hover:text-rose-600 transition-colors duration-200 line-clamp-1"><?= htmlspecialchars($row['tensp']) ?></h3>
                                        <div class="flex justify-center md:justify-start items-center gap-4 mt-1.5 text-xs">
                                            <span class="text-slate-400">SL: <strong class="text-slate-700">x<?= $row['soluong'] ?></strong></span>
                                            <span class="text-slate-300">|</span>
                                            <span class="text-slate-400">Giá: <strong class="text-slate-700"><?= number_format($row['dongia'],0,',','.') ?>đ</strong></span>
                                        </div>
                                        <div class="text-rose-600 font-extrabold text-sm mt-1.5"><?= number_format($row['thanhtien'],0,',','.') ?>đ</div>
                                    </div>
                                    <div class="flex-shrink-0 w-full md:w-auto">
                                        <?php
                                            $ms = $row['masp']; $md = $row['madh'];
                                            $check_dg = mysqli_query($conn, "SELECT id_danhgia FROM danhgiasp WHERE makh = '$makh' AND masp = '$ms' AND madh = '$md'");
                                            if (mysqli_num_rows($check_dg) > 0): ?>
                                            <div class="flex items-center justify-center gap-1.5 px-4 py-2.5 bg-emerald-50 text-emerald-600 rounded-xl text-xs font-bold border border-emerald-100 uppercase">
                                                <i class="fa-solid fa-star"></i> Đã phản hồi
                                            </div>
                                        <?php else: ?>
                                            <button class="reviewBtn w-full px-5 py-2.5 bg-slate-900 text-white rounded-xl text-xs font-bold hover:bg-rose-600 transition-all duration-200 shadow-sm"
                                                data-masp="<?= $row['masp'] ?>" data-madh="<?= $row['madh'] ?>" data-name="<?= $row['tensp'] ?>">
                                                VIẾT ĐÁNH GIÁ
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="py-20 text-center bg-white rounded-2xl border border-slate-100">
                            <i class="fa-solid fa-box-open text-slate-200 text-5xl mb-4"></i>
                            <h3 class="text-base font-bold text-slate-800">Bạn chưa có đơn hàng nào đã giao</h3>
                            <p class="text-slate-400 text-xs mt-1 mb-6">Hãy khám phá bộ sưu tập mỹ phẩm của chúng tôi!</p>
                            <a href="index.php" class="px-6 py-2.5 bg-rose-600 text-white rounded-xl font-bold text-xs uppercase tracking-wider shadow-sm hover:bg-rose-700 transition-all">Tiếp tục mua sắm</a>
                        </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>

    <!-- Modal đánh giá -->
    <div id="reviewModal" class="modal">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white w-full max-w-sm rounded-2xl shadow-xl overflow-hidden border border-slate-100">
                <div class="p-5">
                    <div class="text-center mb-4">
                        <h3 id="p_name" class="text-sm font-bold text-slate-900 line-clamp-1 mb-0.5"></h3>
                        <p class="text-slate-400 text-[11px]">Đánh giá trải nghiệm sản phẩm của bạn</p>
                    </div>
                    
                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="masp" id="p_id">
                        <input type="hidden" name="madh" id="order_id">
                        
                        <div class="star-rating py-1 bg-slate-50 rounded-xl border border-slate-100">
                            <input type="radio" id="star5" name="sosao" value="5" checked /><label for="star5"><i class="fa-solid fa-star"></i></label>
                            <input type="radio" id="star4" name="sosao" value="4" /><label for="star4"><i class="fa-solid fa-star"></i></label>
                            <input type="radio" id="star3" name="sosao" value="3" /><label for="star3"><i class="fa-solid fa-star"></i></label>
                            <input type="radio" id="star2" name="sosao" value="2" /><label for="star2"><i class="fa-solid fa-star"></i></label>
                            <input type="radio" id="star1" name="sosao" value="1" /><label for="star1"><i class="fa-solid fa-star"></i></label>
                        </div>

                        <textarea name="noidung" rows="3" required 
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 text-xs text-slate-700 focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500 focus:bg-white outline-none transition-all resize-none"
                            placeholder="Hãy chia sẻ cảm nhận về sản phẩm..."></textarea>

                        <div class="grid grid-cols-2 gap-2 pt-1">
                            <button type="button" onclick="closeReview()" class="py-2 bg-slate-100 text-slate-500 rounded-xl font-bold text-xs uppercase hover:bg-slate-200 transition-all">Bỏ qua</button>
                            <button type="submit" name="gui_danhgia" class="py-2 bg-rose-600 text-white rounded-xl font-bold text-xs uppercase hover:bg-rose-700 transition-all shadow-sm">Gửi phản hồi</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
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

    <script>
        const modal = document.getElementById("reviewModal");
        document.querySelectorAll(".reviewBtn").forEach(btn => {
            btn.addEventListener("click", function() {
                document.getElementById("p_id").value = this.dataset.masp;
                document.getElementById("order_id").value = this.dataset.madh;
                document.getElementById("p_name").innerText = this.dataset.name;
                modal.style.display = "block";
            });
        });
        function closeReview() { modal.style.display = "none"; }
        window.onclick = (e) => { if(e.target == modal) closeReview(); }
    </script>
</body>
</html>