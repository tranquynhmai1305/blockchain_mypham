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

// 2. Thống kê nhanh số đơn hàng đã giao (dùng cho phần khác nếu cần)
$count_delivered = 0;
if ($makh) {
    $res_count = mysqli_query($conn, "SELECT COUNT(*) as total FROM donhang WHERE makh = '$makh' AND trangthai = 'Đã giao'");
    $count_delivered = mysqli_fetch_assoc($res_count)['total'];
}

// 3. Lấy toàn bộ sản phẩm và thông tin thương hiệu
$sql = "SELECT sp.*, th.tenthuonghieu
FROM sanpham sp
LEFT JOIN thuonghieu th ON sp.id_thuonghieu = th.id_thuonghieu
WHERE sp.trangthai = 1";

$result = $conn->query($sql);
$allProducts = [];

while ($row = $result->fetch_assoc()) {
    $imgs = getHinhAnhBySanPham($row['masp']);
    $baseUrl = "/Shopbanhang/";
    $imageList = [];

    if (!empty($imgs)) {
        foreach ($imgs as $img) {
            $imageList[] = $baseUrl . ltrim($img['duongdan'], '/');
        }
    } else {
        $imageList[] = $baseUrl . "images/no-image.png";
    }

    $allProducts[] = [
        'id'        => (string)$row['masp'],
        'name'      => $row['tensp'],
        'brand'     => $row['tenthuonghieu'] ? $row['tenthuonghieu'] : 'Triple Beauty',
        'price'     => $row['giakhuyenmai'] > 0 ? $row['giakhuyenmai'] : $row['gia'],
        'old'       => $row['gia'],
        'stock'     => $row['soluong'],
        'imgs'      => $imageList
    ];
}

$cartCount = 0;
if (isset($_SESSION['giohang'])) {
    foreach ($_SESSION['giohang'] as $item) {
        $cartCount += $item['soluong'];
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sản Phẩm Yêu Thích | Triple Beauty</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #fcfcfd; margin: 0; }
        .bg-do-do { background-color: #800000; }
        .text-do-do { color: #800000; }
        .sidebar-link.active { background-color: #fff1f2; color: #e11d48; border-right: 4px solid #e11d48; }
        .fly-item {
            position: fixed; 
            z-index: 9999; 
            pointer-events: none;
            transition: all 0.8s cubic-bezier(0.42, 0, 0.58, 1);
            border-radius: 10px; 
            object-fit: contain; 
            background: white;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
    </style>
</head>
<body class="text-slate-700">

    <!-- HEADER CHUẨN TRANG CHỦ (TRÀN FULL MÀN HÌNH) -->
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
                <a href="donhangcuatoi.php" class="hover:text-rose-600 transition">Đơn hàng của tôi</a>
                <a href="sanpham_damua.php" class="hover:text-rose-600 transition">Lịch sử đơn mua</a>
                <a href="voucher.php" class="hover:text-rose-600 transition">Voucher</a>
                <a href="profile.php" class="hover:text-rose-600 transition">Hồ sơ</a>
                <a href="yeuthich.php" class="text-rose-600 border-b-2 border-rose-600 pb-1">Yêu thích</a>
            </div>
        </nav>
    </header>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 py-8">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
            
            <!-- Cột bên trái: Menu điều hướng cố định có hiển thị tổng số yêu thích -->
            <aside class="lg:col-span-3 space-y-4 sticky top-28">
                <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100">
                    <div class="flex items-center gap-3 mb-6 px-1">
                        <div id="fav-count-box" class="w-10 h-10 rounded-xl bg-rose-50 flex items-center justify-center text-rose-500 text-base font-bold italic">
                            0
                        </div>
                        <div>
                            <h3 class="text-xs font-bold text-slate-800 leading-tight">Yêu thích</h3>
                            <p class="text-[11px] text-slate-400 font-medium italic">Sản phẩm đã lưu</p>
                        </div>
                    </div>
                    
                    <nav class="space-y-1">
                        <hr class="my-2 border-slate-50">
                        <a href="profile.php" class="sidebar-link flex items-center gap-3 px-4 py-3 text-slate-500 hover:text-rose-600 font-bold text-xs rounded-xl transition-all">
                            <i class="fa-regular fa-user w-4"></i> Thông tin tài khoản
                        </a>
                        <a href="sanpham_damua.php" class="sidebar-link flex items-center gap-3 px-4 py-3 text-slate-500 hover:text-rose-600 font-bold text-xs rounded-xl transition-all">
                            <i class="fa-solid fa-clock-rotate-left w-4"></i> Lịch sử mua hàng
                        </a>
                        <a href="yeuthich.php" class="sidebar-link active flex items-center gap-3 px-4 py-3 text-rose-600 bg-rose-50 font-bold text-xs rounded-xl">
                            <i class="fa-solid fa-heart w-4"></i> Sản phẩm yêu thích
                        </a>
                        <a href="logout.php" class="sidebar-link flex items-center gap-3 px-4 py-3 text-rose-400 hover:text-rose-600 font-bold text-xs rounded-xl transition-all mt-2">
                            <i class="fa-solid fa-power-off w-4"></i> Đăng xuất
                        </a>
                    </nav>
                </div>

                <div class="bg-gradient-to-br from-slate-900 to-slate-800 rounded-2xl p-6 text-white relative overflow-hidden group shadow-lg">
                    <div class="relative z-10">
                        <h4 class="font-extrabold text-base leading-tight mb-1.5 italic">Beauty Club</h4>
                        <p class="text-slate-400 text-[11px] leading-relaxed mb-4">Lưu lại những sản phẩm bạn quan tâm để dễ dàng theo dõi giá!</p>
                        <a href="index.php" class="inline-block px-4 py-2 bg-rose-600 rounded-lg text-[10px] font-black uppercase tracking-wider hover:bg-rose-700 transition-all">Khám phá ngay</a>
                    </div>
                    <i class="fa-solid fa-heart-circle-check absolute -right-4 -bottom-4 text-7xl text-white/5 group-hover:scale-110 transition-transform"></i>
                </div>
            </aside>

            <!-- Cột bên phải: Nội dung danh sách yêu thích -->
            <main class="lg:col-span-9">
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-rose-50 flex items-center justify-center text-rose-600">
                            <i class="fa-solid fa-heart text-base"></i>
                        </div>
                        <div>
                            <h2 class="text-xl font-black text-slate-900 tracking-tight uppercase">Sản phẩm yêu thích</h2>
                            <p class="text-xs text-slate-400 font-medium">Danh sách các món đồ mỹ phẩm bạn đã lưu</p>
                        </div>
                    </div>
                    <a href="index.php" class="inline-flex items-center gap-2 bg-white border border-slate-200 px-4 py-2 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-50 transition-all shadow-sm">
                        <i class="fa fa-arrow-left"></i> Tiếp tục mua sắm
                    </a>
                </div>

                <div id="fav-container" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4"></div>
            </main>
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
    let favorites = JSON.parse(localStorage.getItem('favorites') || '[]');
    const products = <?= json_encode($allProducts, JSON_UNESCAPED_UNICODE); ?>;
    let currentCartCount = <?= $cartCount ?>;

    function renderFavorites() {
        const container = document.getElementById('fav-container');
        const favProducts = products.filter(p => favorites.includes(p.id));

        const countBox = document.getElementById('fav-count-box');
        if (countBox) {
            countBox.innerText = favProducts.length;
        }

        if (favProducts.length === 0) {
            container.innerHTML = `
                <div class="col-span-full text-center py-20 bg-white rounded-2xl border border-slate-100 shadow-sm">
                    <div class="w-16 h-16 bg-rose-50 text-rose-500 rounded-2xl flex items-center justify-center mx-auto mb-4 text-2xl">
                        <i class="fa-regular fa-heart"></i>
                    </div>
                    <h3 class="text-base font-bold text-slate-800">Bạn chưa có sản phẩm yêu thích nào</h3>
                    <p class="text-slate-400 text-xs mt-1 mb-6">Hãy nhấn vào biểu tượng trái tim ở các sản phẩm bạn yêu mến nhé!</p>
                    <a href="index.php" class="inline-block bg-rose-600 text-white px-6 py-2.5 rounded-xl font-bold text-xs uppercase tracking-wider hover:bg-rose-700 transition shadow-sm">Khám phá ngay</a>
                </div>`;
            return;
        }

        container.innerHTML = favProducts.map(p => `
            <div class="product-card relative bg-white rounded-2xl shadow-sm p-4 border border-slate-100 hover:shadow-md transition-all duration-300 flex flex-col justify-between" id="prod-${p.id}">
                <button onclick="removeFavorite('${p.id}')" class="absolute top-3 right-3 z-10 bg-white/90 hover:bg-rose-50 rounded-full w-8 h-8 flex items-center justify-center shadow-sm border border-slate-100 text-rose-600 transition-all" title="Bỏ yêu thích">
                    <i class="fa-solid fa-heart text-sm"></i>
                </button>
                
                <div>
                    <div class="h-44 flex items-center justify-center overflow-hidden mb-3 bg-slate-50/50 rounded-xl p-2">
                        <img src="${p.imgs[0]}" class="slider-img w-full h-full object-contain mix-blend-multiply">
                    </div>

                    <span class="text-[10px] font-black uppercase text-rose-600 tracking-wider bg-rose-50 px-2 py-0.5 rounded-md inline-block mb-1.5">${p.brand}</span>
                    <h3 class="text-xs font-bold text-slate-800 line-clamp-2 h-8 leading-snug">${p.name}</h3>
                    
                    <div class="mt-2 mb-4 flex items-baseline gap-2">
                        <span class="text-rose-600 font-extrabold text-sm">${Number(p.price).toLocaleString()}đ</span>
                        ${p.old > p.price ? `<span class="text-slate-400 line-through text-[11px]">${Number(p.old).toLocaleString()}đ</span>` : ''}
                    </div>
                </div>

                <div class="flex flex-col gap-2 pt-2 border-t border-slate-50">
                    ${p.stock == 0 ? 
                        `<button class="w-full bg-slate-100 text-slate-400 py-2.5 rounded-xl text-xs font-bold cursor-not-allowed uppercase">Hết hàng</button>` :
                        `<button onclick="addToCart('${p.id}', event)" class="w-full bg-slate-900 text-white py-2.5 rounded-xl text-xs font-bold uppercase hover:bg-rose-600 transition shadow-sm">Thêm giỏ hàng</button>`
                    }
                    <a href="chitiet.php?id=${p.id}" class="text-center text-slate-400 text-[11px] font-bold py-1 hover:text-rose-600 transition">
                        Xem chi tiết sản phẩm
                    </a>
                </div>
            </div>
        `).join('');
    }

    function removeFavorite(id) {
        if(confirm('Bạn muốn bỏ sản phẩm này khỏi danh sách yêu thích?')) {
            favorites = favorites.filter(favId => favId !== id);
            localStorage.setItem('favorites', JSON.stringify(favorites));
            const element = document.getElementById(`prod-${id}`);
            element.style.opacity = '0';
            element.style.transform = 'scale(0.9)';
            setTimeout(() => renderFavorites(), 300);
        }
    }

    function addToCart(id, event) {
        if(event) event.preventDefault();
        const btn = event.currentTarget;
        const productCard = btn.closest('.product-card');
        const imgToFly = productCard.querySelector('.slider-img');
        const cartTarget = document.getElementById('cart-target');
        const cartCountBadge = document.getElementById('cart-count');

        if (imgToFly && cartTarget) {
            const flyer = imgToFly.cloneNode();
            flyer.classList.add('fly-item');
            const start = imgToFly.getBoundingClientRect();
            const end = cartTarget.getBoundingClientRect();

            flyer.style.left = start.left + 'px';
            flyer.style.top = start.top + 'px';
            flyer.style.width = start.width + 'px';
            flyer.style.height = start.height + 'px';
            document.body.appendChild(flyer);

            setTimeout(() => {
                flyer.style.left = (end.left + 5) + 'px';
                flyer.style.top = (end.top + 5) + 'px';
                flyer.style.width = '20px';
                flyer.style.height = '20px';
                flyer.style.opacity = '0';
                flyer.style.transform = 'scale(0.1) rotate(360deg)';
            }, 50);

            fetch("/Shopbanhang/user/themgiohang.php?id=" + id)
                .then(() => {
                    currentCartCount++;
                    if(cartCountBadge) {
                        cartCountBadge.innerText = currentCartCount;
                        cartCountBadge.classList.remove('hidden');
                    } else {
                        // Tạo mới badge nếu trước đó giỏ hàng trống không có thẻ span
                        location.reload();
                    }
                    setTimeout(() => flyer.remove(), 750);
                });
        }
    }

    renderFavorites();
    </script>
</body>
</html>