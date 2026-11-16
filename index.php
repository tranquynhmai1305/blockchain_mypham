<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/Shopbanhang/conn.php';
/** @var PDO $conn */

require_once $_SERVER['DOCUMENT_ROOT'] . '/Shopbanhang/func.php';

$products = getAllSanPham();
$brands = getAllThuongHieu();

if ($conn->connect_error) die("Lỗi CSDL");

$saleProducts = [];
$normalProducts = [];

$sql = "SELECT sp.*, th.tenthuonghieu
FROM sanpham sp
LEFT JOIN thuonghieu th ON sp.id_thuonghieu = th.id_thuonghieu
WHERE sp.trangthai = 1
ORDER BY sp.masp DESC";

$result = $conn->query($sql);

if ($result) {
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

        $productData = [
            'id'    => $row['masp'],
            'brand' => $row['tenthuonghieu'],
            'name'  => $row['tensp'],
            'price' => $row['giakhuyenmai'],
            'old'   => $row['gia'],
            'sale'  => 0,
            'stock' => $row['soluong'],
            'imgs'  => $imageList,
            'detail'=> $row['mota']
        ];

        if ($row['giakhuyenmai'] > 0 && $row['giakhuyenmai'] < $row['gia']) {
            $productData['sale'] = round(100 - ($row['giakhuyenmai'] / $row['gia']) * 100);
            $saleProducts[] = $productData;
        } else {
            $normalProducts[] = $productData;
        }
    }
}

// 2. Xử lý giỏ hàng
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
    <title>Triple Beauty | Mỹ Phẩm Chính Hãng & Deal Hot</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/user_index.css">
    <style>
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        .fly-item { position: fixed; z-index: 9999; border-radius: 50%; transition: all 0.8s cubic-bezier(.2,.8,.2,1); pointer-events: none; }
    /* CSS cho hiệu ứng bay */
.fly-to-cart {
    position: fixed;
    z-index: 9999;
    width: 50px;
    height: 50px;
    border-radius: 50%;
    transition: all 0.8s cubic-bezier(0.2, 0.4, 0.6, 1);
    opacity: 0.8;
}
/* CSS cho Modal Đăng Nhập */
#login-modal {
    display: none; /* Mặc định ẩn */
    position: fixed; 
    inset: 0; 
    z-index: 10000; 
    background-color: rgba(0,0,0,0.5); 
    backdrop-filter: blur(4px);
    display: flex;
    align-items: center;
    justify-content: center;
}
    </style>
</head>
<body class="bg-[#f9fafb] text-gray-800 antialiased flex flex-col min-h-screen">

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

    <!-- MAIN CONTENT -->
    <main class="container mx-auto px-4 py-6 flex-1 space-y-12">
        
        <!-- BANNER SECTION (Đã thu nhỏ & thêm hiệu ứng JS chạy slider) -->
<section>
    <div class="grid grid-cols-12 gap-4">
        <?php
        $banner_chinh = $conn->query("SELECT hinh_anh FROM banner WHERE vi_tri = 'banner_chinh'")->fetch_assoc();
        $banner_phu = $conn->query("SELECT hinh_anh FROM banner WHERE vi_tri = 'banner_phu'")->fetch_assoc();
        
        $img_main = !empty($banner_chinh['hinh_anh']) ? "../" . $banner_chinh['hinh_anh'] : "../images/banner1.png";
        $img_sub = !empty($banner_phu['hinh_anh']) ? "../" . $banner_phu['hinh_anh'] : "../images/banner2.jpg";
        ?>

        <!-- Banner chính (Thu nhỏ chiều cao còn khoảng 220px - 280px và có hiệu ứng slider) -->
        <div class="col-span-12 lg:col-span-8">
            <div id="main-banner-slider" class="rounded-2xl overflow-hidden shadow-sm relative h-[220px] sm:h-[260px] md:h-[280px] bg-gray-100 group">
                <!-- Danh sách ảnh banner (Bạn có thể thêm nhiều ảnh vào mảng data-images này nếu muốn slider chạy nhiều ảnh) -->
                <div class="banner-slide w-full h-full absolute inset-0 transition-opacity duration-700 opacity-100">
                    <img src="<?= $img_main ?>" class="w-full h-full object-cover">
                </div>
                <!-- Ảnh phụ ví dụ để chạy slide (có thể thêm bớt tùy ý) -->
                <div class="banner-slide w-full h-full absolute inset-0 transition-opacity duration-700 opacity-0">
                    <img src="<?= $img_sub ?>" class="w-full h-full object-cover">
                </div>

                <!-- Nút chuyển trái/phải (Hiện khi di chuột vào banner) -->
                <button onclick="changeBannerSlide(-1)" class="absolute left-3 top-1/2 -translate-y-1/2 bg-white/70 hover:bg-white text-gray-800 w-8 h-8 rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition shadow cursor-pointer">
                    <i class="fa-solid fa-chevron-left text-xs"></i>
                </button>
                <button onclick="changeBannerSlide(1)" class="absolute right-3 top-1/2 -translate-y-1/2 bg-white/70 hover:bg-white text-gray-800 w-8 h-8 rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition shadow cursor-pointer">
                    <i class="fa-solid fa-chevron-right text-xs"></i>
                </button>

                <!-- Các dấu chấm chuyển slide (Dots) -->
                <div class="absolute bottom-3 left-1/2 -translate-x-1/2 flex gap-1.5 z-10">
                    <span class="banner-dot w-2 h-2 rounded-full bg-white/60 cursor-pointer transition-all duration-300 bg-white w-5" onclick="currentBannerSlide(0)"></span>
                    <span class="banner-dot w-2 h-2 rounded-full bg-white/60 cursor-pointer transition-all duration-300" onclick="currentBannerSlide(1)"></span>
                </div>
            </div>
        </div>

        <!-- Banner phụ bên phải (Thu nhỏ chiều cao đồng bộ với banner chính) -->
        <div class="hidden lg:col-span-4 lg:flex flex-col gap-4">
            <div class="rounded-2xl overflow-hidden shadow-sm h-[280px] bg-gray-100">
                <img src="<?= $img_sub ?>" class="w-full h-full object-cover hover:scale-105 transition duration-500">
            </div>
        </div>
    </div>
</section>

        <!-- DANH MỤC HOT -->
        <section class="bg-white border border-gray-100 rounded-3xl p-6 md:p-8 shadow-sm">
            <h2 class="text-center text-lg md:text-xl font-black uppercase italic tracking-tight text-gray-800 mb-8">Danh mục Hot</h2>
            <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 lg:grid-cols-11 gap-4">
                <script>
                    const hotCats = [
                        {n:'Son Môi', img:'../images/sonhaidau.jpg', key: 'son'},
                        {n:'Tinh Chất', img:'../images/tinh-chat.webp', key: 'tinh chat'},
                        {n:'Mặt Nạ', img:'../images/mat-na.jpg', key: 'mat na'},
                        {n:'Kem Dưỡng', img:'../images/kem-duong-am.jpg', key: 'kem duong'},
                        {n:'Sữa Rửa Mặt', img:'../images/sua-rua-mat.png', key: 'rua mat'},
                        {n:'Nước Hoa', img:'../images/nuoc-hoa.webp', key: 'nuoc hoa'},
                        {n:'Chống Nắng', img:'../images/chong-nang.webp', key: 'chong nang'},
                        {n:'Dưỡng Thể', img:'../images/duong-the.png', key: 'duong the'},
                        {n:'Tẩy Trang', img:'../images/nuoc-tay-trang.jpg', key: 'tay trang'},
                        {n:'Dưỡng Tóc', img:'../images/dưỡng tóc.jpg', key: 'duong toc'},
                        {n:'Phấn Nước', img:'../images/phan-nuoc.jpg', key: 'phan'}
                    ];

                    document.write(hotCats.map(c => `
                        <a href="timkiem.php?keyword=${encodeURIComponent(c.key)}" class="flex flex-col items-center group cursor-pointer text-center">
                            <div class="w-16 h-16 md:w-20 md:h-20 rounded-full shadow-sm overflow-hidden bg-gray-50 transform group-hover:scale-105 transition-all duration-300 border-2 border-rose-100">
                                <img src="${c.img}" alt="${c.n}" class="w-full h-full object-cover">
                            </div>
                            <span class="text-[11px] font-bold text-gray-700 mt-2.5 group-hover:text-rose-600 transition-colors line-clamp-2 leading-tight">
                                ${c.n}
                            </span>
                        </a>`).join(''));
                </script>
            </div>
        </section>

        <!-- FLASH SALE / DEAL HOT -->
        <section>
            <div class="bg-white rounded-3xl overflow-hidden shadow-sm border border-rose-100">
                <div class="bg-gradient-to-r from-rose-700 to-pink-600 text-white px-6 py-4 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <i class="fa-solid fa-bolt text-amber-300 text-xl animate-pulse"></i>
                        <h2 class="font-black text-lg md:text-xl italic uppercase tracking-wider">Flash Sale Đang Diễn Ra</h2>
                    </div>
                    <span class="text-xs bg-white/20 px-3 py-1 rounded-full font-bold">Deal Sốc Mỗi Ngày</span>
                </div>
                <div id="flash-sale-container" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4 p-6 bg-gray-50/50"></div>
            </div>
        </section>

        <!-- THƯƠNG HIỆU TIÊU BIỂU -->
        <section class="bg-white py-8 px-6 rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
            <h2 class="text-center text-lg md:text-xl font-black uppercase italic tracking-tight text-gray-800 mb-6">Thương hiệu tiêu biểu</h2>
            <div class="brand-container overflow-hidden relative">
                <div class="brand-track flex gap-8 items-center">
                    <?php 
                    $brandsList = getAllThuongHieu($conn); 
                    $displayBrands = array_merge($brandsList, $brandsList, $brandsList);
                    foreach($displayBrands as $b): ?>
                        <div class="flex flex-col items-center brand-item flex-shrink-0">
                            <a href="timkiem.php?brand=<?= $b['id_thuonghieu'] ?>" class="flex flex-col items-center group">
                                <div class="w-24 h-24 rounded-full bg-gray-50 border border-gray-200 flex items-center justify-center overflow-hidden shadow-xs group-hover:border-rose-400 group-hover:shadow-md transition">
                                    <img src="../<?= htmlspecialchars($b['hinh_thuonghieu']) ?>" class="w-full h-full object-cover group-hover:scale-110 transition duration-300">
                                </div>
                                <span class="text-[11px] font-bold mt-2 text-gray-600 group-hover:text-rose-600 uppercase transition">
                                    <?= htmlspecialchars($b['tenthuonghieu']) ?>
                                </span>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <!-- CAM KẾT TỪ SHOP -->
        <section class="bg-gradient-to-br from-pink-50 to-rose-50/40 border border-pink-100 rounded-3xl p-6 md:p-8">
            <div class="text-center mb-8">
                <h2 class="text-xl font-black uppercase text-gray-800 tracking-tight">Cam Kết Từ Shop</h2>
                <p class="text-gray-500 text-xs mt-1">Mang đến sản phẩm chất lượng và trải nghiệm mua sắm tốt nhất cho bạn.</p>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6 text-center">
                <div class="bg-white p-5 rounded-2xl shadow-xs hover:shadow-md transition flex flex-col items-center">
                    <div class="w-12 h-12 rounded-full bg-pink-100 flex items-center justify-center text-rose-600 mb-3 text-lg">
                        <i class="fa-solid fa-certificate"></i>
                    </div>
                    <h3 class="font-bold text-xs uppercase text-gray-800">Chính Hãng 100%</h3>
                </div>
                <div class="bg-white p-5 rounded-2xl shadow-xs hover:shadow-md transition flex flex-col items-center">
                    <div class="w-12 h-12 rounded-full bg-pink-100 flex items-center justify-center text-rose-600 mb-3 text-lg">
                        <i class="fa-solid fa-truck-fast"></i>
                    </div>
                    <h3 class="font-bold text-xs uppercase text-gray-800">Giao Hàng Nhanh</h3>
                </div>
                <div class="bg-white p-5 rounded-2xl shadow-xs hover:shadow-md transition flex flex-col items-center">
                    <div class="w-12 h-12 rounded-full bg-pink-100 flex items-center justify-center text-rose-600 mb-3 text-lg">
                        <i class="fa-solid fa-rotate-left"></i>
                    </div>
                    <h3 class="font-bold text-xs uppercase text-gray-800">Đổi Trả 7 Ngày</h3>
                </div>
                <div class="bg-white p-5 rounded-2xl shadow-xs hover:shadow-md transition flex flex-col items-center">
                    <div class="w-12 h-12 rounded-full bg-pink-100 flex items-center justify-center text-rose-600 mb-3 text-lg">
                        <i class="fa-solid fa-headset"></i>
                    </div>
                    <h3 class="font-bold text-xs uppercase text-gray-800">Hỗ Trợ 24/7</h3>
                </div>
            </div>
        </section>

    </main>

    <!-- QUICK VIEW MODAL -->
    <div id="quick-view-modal" class="fixed inset-0 z-[100] hidden items-center justify-center bg-black/60 backdrop-blur-xs p-4">
        <div class="relative w-full max-w-3xl bg-white rounded-3xl shadow-2xl overflow-hidden flex flex-col p-6 md:p-8 gap-6 max-h-[90vh] overflow-y-auto">
            <button onclick="closeQuickView()" class="absolute top-4 right-4 w-9 h-9 bg-gray-100 hover:bg-gray-200 rounded-full flex items-center justify-center text-gray-600 transition z-10"><i class="fa-solid fa-xmark text-lg"></i></button>
            
            <div class="flex flex-col md:flex-row gap-6">
                <div class="w-full md:w-1/2">
                    <div class="relative bg-gray-50 rounded-2xl p-4 flex items-center justify-center border border-gray-100 h-72">
                        <span class="absolute top-3 left-3 bg-rose-600 text-white text-[10px] font-bold px-2.5 py-1 rounded-md uppercase">SALE</span>
                        <img id="qv-img" src="" class="w-full h-full object-contain">
                    </div>
                </div>
                <div class="w-full md:w-1/2 flex flex-col justify-between">
                    <div>
                        <h2 id="qv-brand" class="text-rose-600 font-bold text-xs uppercase tracking-wider mb-1">BRAND</h2>
                        <h1 id="qv-name" class="text-lg font-bold text-gray-900 mb-3 leading-snug">Product Name</h1>
                        <div class="flex items-center gap-3 mb-4 bg-gray-50 p-3 rounded-xl">
                            <span id="qv-price" class="text-rose-600 font-black text-xl">0 đ</span>
                            <span id="qv-old-price" class="text-gray-400 line-through text-sm">0 đ</span>
                        </div>
                    </div>
                    <div class="space-y-4">
                        <div class="flex items-center gap-3">
                            <span class="text-xs font-bold text-gray-500 uppercase">Số Lượng:</span>
                            <div class="flex border border-gray-200 rounded-xl overflow-hidden shadow-2xs">
                                <button onclick="updateQty(-1)" class="px-3.5 py-1.5 bg-gray-50 hover:bg-gray-100 transition">-</button>
                                <input id="qv-qty" type="text" value="1" class="w-10 text-center text-xs font-bold outline-none bg-white" readonly>
                                <button onclick="updateQty(1)" class="px-3.5 py-1.5 bg-gray-50 hover:bg-gray-100 transition">+</button>
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <button onclick="triggerFlyEffect()" class="flex-1 border border-gray-900 py-3 rounded-xl text-xs font-bold uppercase hover:bg-gray-900 hover:text-white transition shadow-2xs"><i class="fa fa-shopping-bag mr-1"></i> Thêm Giỏ</button>
                            <button class="flex-1 bg-rose-600 text-white py-3 rounded-xl text-xs font-bold uppercase hover:bg-rose-700 transition shadow-sm">Mua Ngay</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="border-t pt-4">
                <h3 class="font-bold text-gray-800 uppercase text-xs mb-2 flex items-center gap-2">
                    <i class="fa-solid fa-file-lines text-rose-600"></i> Mô tả sản phẩm
                </h3>
                <div id="qv-detail" class="text-xs text-gray-600 leading-relaxed bg-gray-50 p-3.5 rounded-xl border border-gray-100 max-h-32 overflow-y-auto"></div>
            </div>
        </div>
    </div>

    <!-- FOOTER -->
    <footer class="bg-gray-950 text-white pt-12 pb-6 mt-auto border-t border-gray-900">
        <div class="max-w-7xl mx-auto px-6">
            <div class="flex flex-wrap justify-between items-center border-b border-gray-800/80 pb-6 mb-10 gap-4 text-[11px] font-bold uppercase tracking-wider text-gray-300">
                <div class="flex items-center gap-2.5"><i class="fa-solid fa-certificate text-rose-500 text-base"></i> Cam kết 28 ngày</div>
                <div class="flex items-center gap-2.5"><i class="fa-solid fa-truck text-rose-500 text-base"></i> Giao hàng miễn phí</div>
                <div class="flex items-center gap-2.5"><i class="fa-solid fa-briefcase text-rose-500 text-base"></i> Ưu đãi độc quyền</div>
                <div class="flex items-center gap-2.5"><i class="fa-solid fa-gift text-rose-500 text-base"></i> Quà tặng</div>
                <div class="flex items-center gap-2.5"><i class="fa-solid fa-vial text-rose-500 text-base"></i> Mẫu thử</div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                <div>
                    <h4 class="font-bold uppercase text-xs mb-4 tracking-widest text-gray-200">Liên hệ</h4>
                    <ul class="text-gray-400 text-xs space-y-2.5">
                        <li>Email: <br><span class="text-white font-medium">cskh@triplebeauty.vn</span></li>
                        <li class="text-base font-extrabold text-white tracking-wide pt-1">1800 5454 63</li>
                    </ul>
                </div>

                <div>
                    <h4 class="font-bold uppercase text-xs mb-4 tracking-widest text-gray-200">Chăm sóc khách hàng</h4>
                    <ul class="text-gray-400 text-xs space-y-2.5">
                        <li class="hover:text-white cursor-pointer transition">Hệ thống cửa hàng</li>
                        <li class="hover:text-white cursor-pointer transition">Theo dõi đơn hàng</li>
                        <li class="hover:text-white cursor-pointer transition">Câu hỏi thường gặp</li>
                        <li class="hover:text-white cursor-pointer transition">Chính sách giao hàng & hoàn trả</li>
                    </ul>
                </div>

                <div>
                    <h4 class="font-bold uppercase text-xs mb-4 tracking-widest text-gray-200">Về Triple Beauty</h4>
                    <ul class="text-gray-400 text-xs space-y-2.5">
                        <li class="hover:text-white cursor-pointer transition">Phát triển bền vững</li>
                        <li class="hover:text-white cursor-pointer transition">Lời khuyên cho da</li>
                        <li class="hover:text-white cursor-pointer transition">Chương trình thiện nguyện</li>
                        <li class="hover:text-white cursor-pointer transition">Danh sách chuyên gia</li>
                    </ul>
                </div>

                <div>
                    <h4 class="font-bold uppercase text-xs mb-4 tracking-widest text-gray-200">Kết nối</h4>
                    <div class="flex gap-4 text-lg mb-6 text-gray-300">
                        <i class="fa-brands fa-facebook hover:text-blue-500 cursor-pointer transition"></i>
                        <i class="fa-brands fa-instagram hover:text-pink-500 cursor-pointer transition"></i>
                        <i class="fa-brands fa-tiktok hover:text-white cursor-pointer transition"></i>
                        <i class="fa-brands fa-youtube hover:text-red-500 cursor-pointer transition"></i>
                    </div>
                </div>
            </div>

            <div class="border-t border-gray-900 mt-10 pt-6 flex flex-col md:flex-row justify-between items-center gap-3 text-[10px]">
                <p class="text-gray-500 font-bold uppercase tracking-wider">
                    &copy; 2026 TRIPLE BEAUTY VIỆT NAM. Đã đăng ký bản quyền.
                </p>
                <div class="flex gap-4 text-gray-500 font-bold uppercase tracking-wider">
                    <span class="hover:text-white cursor-pointer transition">Chính sách bảo mật</span>
                    <span class="text-gray-700">|</span>
                    <span class="hover:text-white cursor-pointer transition">Điều khoản sử dụng</span>
                </div>
            </div>
        </div>
    </footer>

    <!-- Script xử lý hiệu ứng chạy tự động & thủ công cho Banner chính -->
<script>
let bannerIndex = 0;
let bannerTimer = null;
const slides = document.querySelectorAll('.banner-slide');
const dots = document.querySelectorAll('.banner-dot');

function showBannerSlide(index) {
    if (!slides.length) return;
    if (index >= slides.length) bannerIndex = 0;
    if (index < 0) bannerIndex = slides.length - 1;

    slides.forEach((slide, i) => {
        slide.style.opacity = (i === bannerIndex) ? '1' : '0';
    });

    dots.forEach((dot, i) => {
        if (i === bannerIndex) {
            dot.classList.add('bg-white', 'w-5');
            dot.classList.remove('bg-white/60', 'w-2');
        } else {
            dot.classList.remove('bg-white', 'w-5');
            dot.classList.add('bg-white/60', 'w-2');
        }
    });
}

function changeBannerSlide(n) {
    clearInterval(bannerTimer);
    bannerIndex += n;
    showBannerSlide(bannerIndex);
    startBannerAutoPlay();
}

function currentBannerSlide(n) {
    clearInterval(bannerTimer);
    bannerIndex = n;
    showBannerSlide(bannerIndex);
    startBannerAutoPlay();
}

function startBannerAutoPlay() {
    bannerTimer = setInterval(() => {
        bannerIndex++;
        showBannerSlide(bannerIndex);
    }, 4000); // Tự động chuyển ảnh sau mỗi 4 giây
}

// Khởi chạy khi tải trang
if (slides.length > 0) {
    showBannerSlide(bannerIndex);
    startBannerAutoPlay();
}
</script>

    <!-- KHUNG GIAO DIỆN CHATBOX AI -->
    <div id="ai-chat-widget" style="position: fixed; bottom: 20px; right: 20px; z-index: 99999;">
      <button id="chat-toggle-btn"
        style="background-color: #e91e63;
               color: white;
               border: none;
               padding: 12px 20px;
               border-radius: 30px;
               cursor: pointer;
               font-weight: bold;
               box-shadow: 0 4px 12px rgba(0,0,0,0.3);
               display: flex;
               align-items: center;
               gap: 8px;">

    <i class="fas fa-headset" style="font-size: 21px;"></i>

    <span>Chat tư vấn</span>
</button>
        
        <div id="chat-box-container" style="display: none; position: absolute; bottom: 55px; right: 0; width: 340px; height: 460px; background: white; border: 1px solid #ddd; border-radius: 15px; flex-direction: column; box-shadow: 0 5px 20px rgba(0,0,0,0.2); overflow: hidden; z-index: 100000;">
            <div class="chat-header" style="background: #e91e63; color: white; padding: 10px 15px; display: flex; justify-content: space-between; align-items: center; font-weight: bold;">
                <span>Trợ lý ảo Triple Beauty </span>
                <button id="chat-close-btn" style="background: none; border: none; color: white; font-size: 20px; cursor: pointer;">&times;</button>
            </div>
            
            <!-- Khu vực tin nhắn -->
            <div id="chat-messages" style="flex: 1; padding: 10px; overflow-y: auto; display: flex; flex-direction: column; gap: 8px; background: #fdfdfd;">
                <div class="chat-msg bot-msg" style="background: #f1f2f6; color: #333; padding: 8px 12px; border-radius: 8px; max-width: 80%; font-size: 13px;">Xin chào bạn! Mình là trợ lý AI của Triple Beauty. Bạn cần tư vấn mỹ phẩm nào hôm nay ạ?</div>
            </div>

           <!-- THANH CÂU HỎI GỢI Ý NHANH -->
<div id="quick-suggestions" style="padding: 6px 10px; display: flex; gap: 5px; overflow-x: auto; background: #fff; border-top: 1px solid #eee;">
    <button onclick="sendQuickQuery(this)" data-q="Kem dưỡng nào hợp da dầu?" style="background: #fce4ec; color: #e91e63; border: none; padding: 4px 8px; border-radius: 12px; font-size: 11px; white-space: nowrap; cursor: pointer;">Da dầu dùng gì?</button>
    
    <!-- CÁC CÂU HỎI MỚI VỀ LOẠI DA VÀ SẢN PHẨM HỖ TRỢ -->
    <button onclick="sendQuickQuery(this)" data-q="Tư vấn chu trình chăm sóc cho da khô?" style="background: #fce4ec; color: #e91e63; border: none; padding: 4px 8px; border-radius: 12px; font-size: 11px; white-space: nowrap; cursor: pointer;">Da khô chăm sóc sao?</button>
    <button onclick="sendQuickQuery(this)" data-q="Da nhạy cảm nên dùng sản phẩm nào của shop?" style="background: #fce4ec; color: #e91e63; border: none; padding: 4px 8px; border-radius: 12px; font-size: 11px; white-space: nowrap; cursor: pointer;">Sản phẩm cho da nhạy cảm?</button>
    <button onclick="sendQuickQuery(this)" data-q="Shop có tinh chất hoặc serum nào trị mụn cho da dầu mụn không?" style="background: #fce4ec; color: #e91e63; border: none; padding: 4px 8px; border-radius: 12px; font-size: 11px; white-space: nowrap; cursor: pointer;">Serum trị mụn?</button>
    <button onclick="sendQuickQuery(this)" data-q="Da hỗn hợp thiên dầu nên dùng kem chống nắng nào?" style="background: #fce4ec; color: #e91e63; border: none; padding: 4px 8px; border-radius: 12px; font-size: 11px; white-space: nowrap; cursor: pointer;">Chống nắng da hỗn hợp?</button>
    
    <button onclick="sendQuickQuery(this)" data-q="Shop có những loại son nào?" style="background: #fce4ec; color: #e91e63; border: none; padding: 4px 8px; border-radius: 12px; font-size: 11px; white-space: nowrap; cursor: pointer;">Son môi hãng nào tốt?</button>
    <button onclick="sendQuickQuery(this)" data-q="Chính sách đổi trả của shop thế nào?" style="background: #fce4ec; color: #e91e63; border: none; padding: 4px 8px; border-radius: 12px; font-size: 11px; white-space: nowrap; cursor: pointer;">Chính sách đổi trả?</button>
    <button onclick="switchToHumanSupport()" style="background: #e91e63; color: white; border: none; padding: 4px 10px; border-radius: 12px; font-size: 11px; white-space: nowrap; font-weight: bold; cursor: pointer;">👤 Nhắn trực tiếp CSKH</button>
</div>

            <!-- Khu vực nhập nội dung -->
            <div class="chat-input-area" style="display: flex; padding: 10px; border-top: 1px solid #ddd; background: white;">
                <input type="text" id="chat-input" placeholder="Nhập câu hỏi..." style="flex: 1; padding: 8px; border: 1px solid #ddd; border-radius: 6px; outline: none; font-size: 13px;">
                <button id="chat-send-btn" style="background: #e91e63; color: white; border: none; padding: 8px 14px; margin-left: 5px; border-radius: 6px; cursor: pointer; font-weight: bold;">Gửi</button>
            </div>
        </div>
    </div>

    <script>
    // --- KHỞI TẠO BIẾN VÀ SỰ KIỆN CHATBOX ---
    const chatToggleBtn = document.getElementById('chat-toggle-btn');
    const chatBoxContainer = document.getElementById('chat-box-container');
    const chatCloseBtn = document.getElementById('chat-close-btn');
    const chatSendBtn = document.getElementById('chat-send-btn');
    const chatInput = document.getElementById('chat-input');
    const chatMessages = document.getElementById('chat-messages');

    let isHumanSupport = false;
    let chatPollInterval = null;

    if (chatToggleBtn) {
        chatToggleBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            chatBoxContainer.style.display = chatBoxContainer.style.display === 'none' ? 'flex' : 'none';
        });
    }

    if (chatCloseBtn) {
        chatCloseBtn.addEventListener('click', () => {
            chatBoxContainer.style.display = 'none';
        });
    }

    if (chatSendBtn) {
        chatSendBtn.addEventListener('click', sendAiMessage);
    }

    if (chatInput) {
        chatInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') sendAiMessage();
        });
    }

    // --- HÀM GỬI TIN NHẮN (HỖ TRỢ CẢ AI VÀ NHÂN VIÊN CSKH) ---
    async function sendAiMessage() {
        const text = chatInput.value.trim();
        if (!text) return;

        appendChatMsg(text, 'user-msg', '#e91e63', 'white', 'flex-end');
        chatInput.value = '';
        chatMessages.scrollTop = chatMessages.scrollHeight;

        // NẾU ĐANG Ở CHẾ ĐỘ CHAT VỚI NHÂN VIÊN
        if (isHumanSupport) {
            try {
                const response = await fetch('save_chat.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ message: text })
                });
                const result = await response.json();
                if (result.status !== 'success') {
                    appendChatMsg("Không thể gửi tin nhắn đến nhân viên. Vui lòng thử lại.", 'bot-msg', '#ffdddd', '#900', 'flex-start');
                }
            } catch (error) {
                console.error("Lỗi gửi tin nhắn:", error);
            }
            return;
        }

        // NẾU VẪN LÀ AI TRẢ LỜI
        const loadingId = appendChatMsg("Đang suy nghĩ...", 'bot-msg', '#f1f2f6', '#333', 'flex-start');
        try {
            const aiResponse = await callGeminiChatAPI(text);
            loadingId.innerText = aiResponse;
        } catch (error) {
            loadingId.innerText = "Xin lỗi, hệ thống đang bận. Bạn vui lòng thử lại sau nhé!";
        }
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    // --- HÀM GỌI API GEMINI ---
    async function callGeminiChatAPI(userMessage) {
        const apiKey = "AQ.Ab8RN6KOGemDZ3ysSlq1MCHC7d-zp68R-3za4Lvym5elXUe46g";
        const shopContext = "Bạn là trợ lý chăm sóc khách hàng của shop mỹ phẩm Triple Beauty. Hãy tư vấn ngắn gọn, thân thiện bằng tiếng Việt.";
        const url = `https://generativelanguage.googleapis.com/v1beta/models/gemini-3.6-flash:generateContent?key=${apiKey}`;
        
        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    contents: [{
                        parts: [{ text: shopContext + "\nKhách hàng hỏi: " + userMessage }]
                    }]
                })
            });

            const data = await response.json();
            
            if (data.candidates && data.candidates[0].content.parts[0].text) {
                return data.candidates[0].content.parts[0].text;
            } else if (data.error) {
                return "Lỗi từ Google API: " + data.error.message;
            } else {
                return "Không nhận được phản hồi hợp lệ từ AI.";
            }
        } catch (error) {
            return "Lỗi kết nối mạng tới máy chủ AI!";
        }
    }

    // --- HÀM TIỆN ÍCH HIỂN THỊ TIN NHẮN ---
    function appendChatMsg(text, className, bgColor, textColor, align) {
        const msgDiv = document.createElement('div');
        msgDiv.className = `chat-msg ${className}`;
        msgDiv.style.cssText = `background: ${bgColor}; color: ${textColor}; padding: 8px 12px; border-radius: 8px; max-width: 80%; font-size: 13px; align-self: ${align}; margin-bottom: 4px;`;
        msgDiv.innerText = text;
        chatMessages.appendChild(msgDiv);
        chatMessages.scrollTop = chatMessages.scrollHeight;
        return msgDiv;
    }

    // --- XỬ LÝ CÂU HỎI GỢI Ý NHANH ---
    async function sendQuickQuery(btn) {
        const question = btn.getAttribute('data-q');
        
        appendChatMsg(question, 'user-msg', '#e91e63', 'white', 'flex-end');
        chatMessages.scrollTop = chatMessages.scrollHeight;

        const loadingId = appendChatMsg("Đang suy nghĩ...", 'bot-msg', '#f1f2f6', '#333', 'flex-start');
        const aiResponse = await callGeminiChatAPI(question);
        loadingId.innerText = aiResponse;
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    // --- CHUYỂN SANG CHẾ ĐỘ CSKH TRỰC TIẾP ---
    function switchToHumanSupport() {
        isHumanSupport = true;
        
        appendChatMsg("Tôi muốn nhắn trực tiếp với nhân viên CSKH.", 'user-msg', '#e91e63', 'white', 'flex-end');
        
        // Gửi yêu cầu ngầm lên CSDL
        fetch('save_chat.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ message: "Yêu cầu kết nối nhân viên CSKH." })
        });

        setTimeout(() => {
            appendChatMsg("Đã kết nối! Trợ lý AI tạm dừng. Nhân viên chăm sóc khách hàng của Triple Beauty sẽ phản hồi bạn trong giây lát...", 'bot-msg', '#f1f2f6', '#333', 'flex-start');
        }, 500);

        // Kích hoạt đồng bộ tin nhắn định kỳ từ nhân viên
        if (!chatPollInterval) {
            chatPollInterval = setInterval(fetchCustomerMessages, 3000);
        }
    }

 let lastMessageId = 0; // Lưu message_id mới nhất để tối ưu polling

async function fetchCustomerMessages() {
    if (!isHumanSupport) return;
    try {
        const res = await fetch(`get_messages.php?last_id=${lastMessageId}`);
        const messages = await res.json();
        
        if (Array.isArray(messages) && messages.length > 0) {
            messages.forEach(msg => {
                // Cập nhật lại ID tin nhắn cuối cùng đã duyệt
                if (msg.message_id > lastMessageId) {
                    lastMessageId = msg.message_id;
                }
                
                // Nếu người gửi không phải là chính user hiện tại (tức là do Admin/Nhân viên gửi tới)
                const currentUserId = <?= $_SESSION['user_id'] ?? 0 ?>;
                if (parseInt(msg.id_nguoigui) !== currentUserId) {
                    appendChatMsg(msg.noidung_tinnhan, 'bot-msg', '#f1f2f6', '#333', 'flex-start');
                }
            });
        }
    } catch (e) {
        console.error("Lỗi đồng bộ tin nhắn:", e);
    }
}
</script>

<script>
    // --- XỬ LÝ SẢN PHẨM, GIỎ HÀNG VÀ SLIDER ---
    let currentCartCount = <?= $cartCount ?>;

    const saleProducts = <?= json_encode($saleProducts, JSON_UNESCAPED_UNICODE); ?>;
    const normalProducts = <?= json_encode($normalProducts, JSON_UNESCAPED_UNICODE); ?>;
    let favorites = JSON.parse(localStorage.getItem('favorites') || '[]');

    function render(id, data) {
        const container = document.getElementById(id);
        if (!container) return;

        container.innerHTML = data.map(p => {
            let imgs = JSON.stringify(p.imgs);
            let isFav = favorites.includes(String(p.id));
            let heartClass = isFav ? 'fa-solid text-rose-600' : 'fa-regular text-gray-500';
            
            let stockBadge = '';
            let stockStatus = '';
            if (p.stock <= 0) {
                stockBadge = `<span class="absolute top-3 left-3 bg-red-600 text-white text-[10px] font-bold px-2 py-0.5 rounded shadow-xs">Hết hàng</span>`;
                stockStatus = `<span class="bg-red-50 text-red-600 font-semibold text-[10px] px-2 py-0.5 rounded">Hết hàng</span>`;
            } else if (p.stock <= 5) {
                stockBadge = `<span class="absolute top-3 left-3 bg-amber-500 text-white text-[10px] font-bold px-2 py-0.5 rounded shadow-xs">Sắp hết</span>`;
                stockStatus = `<span class="bg-amber-50 text-amber-600 font-semibold text-[10px] px-2 py-0.5 rounded">Sắp hết (${p.stock})</span>`;
            } else {
                stockStatus = `<span class="text-gray-400 text-[10px]">Còn lại: ${p.stock}</span>`;
            }

            return `
            <div class="product-card relative bg-white rounded-2xl shadow-xs border border-gray-100 p-3.5 flex flex-col justify-between hover:shadow-md transition">
                <button onclick="toggleFavorite('${p.id}', this)" class="absolute top-3 right-3 z-10 bg-gray-50 hover:bg-rose-50 text-gray-500 hover:text-rose-600 rounded-full w-7 h-7 flex items-center justify-center transition shadow-2xs">
                    <i class="${heartClass} text-xs"></i>
                </button>

                <div>
                    <div class="slider relative bg-gray-50/50 rounded-xl overflow-hidden flex items-center justify-center h-40 mb-3" data-images='${imgs}' data-index="0">
                        <img src="${p.imgs[0]}" class="slider-img w-full h-full object-contain p-2">
                        ${p.imgs.length > 1 ? `
                            <button class="prev absolute left-1 top-1/2 -translate-y-1/2 bg-white/80 hover:bg-white px-1.5 py-1 text-[10px] rounded shadow">&#10094;</button>
                            <button class="next absolute right-1 top-1/2 -translate-y-1/2 bg-white/80 hover:bg-white px-1.5 py-1 text-[10px] rounded shadow">&#10095;</button>
                            <div class="dots absolute bottom-1.5 left-1/2 -translate-x-1/2 flex gap-1">
                                ${p.imgs.map((i,index)=>`<span class="dot w-1.5 h-1.5 rounded-full bg-gray-300 ${index==0?'bg-rose-600 w-3':''}"></span>`).join('')}
                            </div>
                        `:''}
                        ${stockBadge}
                    </div>

                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">${p.brand || ''}</span>
                    <h3 class="text-xs font-bold text-gray-800 mt-1 h-8 overflow-hidden line-clamp-2 leading-snug cursor-pointer hover:text-rose-600 transition" onclick="openQuickView('${p.id}')">${p.name}</h3>
                </div>

                <div class="mt-3 pt-3 border-t border-gray-100">
                    <div class="flex items-center justify-between mb-2">
                        <div>
                            ${p.price > 0 && p.price < p.old ? 
                                `<div class="flex flex-col">
                                    <span class="text-rose-600 font-black text-sm">${Number(p.price).toLocaleString()}đ</span>
                                    <span class="line-through text-gray-400 text-[10px]">${Number(p.old).toLocaleString()}đ</span>
                                </div>` : 
                                `<span class="text-rose-600 font-black text-sm">${Number(p.old).toLocaleString()}đ</span>`
                            }
                        </div>
                        <div>${stockStatus}</div>
                    </div>
                    <div class="grid grid-cols-2 gap-1.5">
                        <a href="chitiet.php?id=${p.id}" 
                           onclick="event.stopPropagation()"
                           class="text-center border border-gray-200 py-1.5 rounded-lg text-xs font-semibold text-gray-600 hover:bg-gray-50 transition block">
                            Xem
                        </a>

                        ${p.stock == 0 ? 
                            `<button class="bg-gray-200 text-gray-400 py-1.5 rounded-lg text-xs font-bold cursor-not-allowed" disabled>Hết</button>` : 
                            `<button onclick="addToCart('${p.id}', event)" 
                            class="bg-rose-600 text-white py-1.5 rounded-lg text-xs font-bold hover:bg-rose-700 transition shadow-2xs">
                            Thêm giỏ
                            </button>`
                        }
                    </div>
                </div>
            </div>`;
        }).join('');
    }

    render('flash-sale-container', saleProducts);
    initSlider(); 

    function openQuickView(id) {
        const p = [...saleProducts, ...normalProducts].find(x => x.id == id);
        if(!p) return;

        document.getElementById('qv-img').src = p.imgs[0];
        document.getElementById('qv-brand').innerText = p.brand || '';
        document.getElementById('qv-name').innerText = p.name;
        document.getElementById('qv-price').innerText = Number(p.price > 0 ? p.price : p.old).toLocaleString() + " đ";
        document.getElementById('qv-old-price').innerText = p.price > 0 && p.price < p.old ? Number(p.old).toLocaleString() + " đ" : "";
        document.getElementById('qv-detail').innerText = p.detail || "Chưa có mô tả chi tiết cho sản phẩm này.";
        document.getElementById('qv-qty').value = 1;
        document.getElementById('quick-view-modal').classList.replace('hidden', 'flex');
        document.body.style.overflow = 'hidden';
    }

    function closeQuickView() {
        document.getElementById('quick-view-modal').classList.replace('flex', 'hidden');
        document.body.style.overflow = 'auto';
    }

    function updateQty(val) {
        const input = document.getElementById('qv-qty');
        let n = parseInt(input.value) + val;
        if (n >= 1) input.value = n;
    }

    function addToCart(productId, event) {
    event.stopPropagation();

    const productCard = event.target.closest('.product-card');
    const img = productCard.querySelector('img');
    const cartTarget = document.getElementById('cart-target'); 

    // Hiệu ứng bay vào giỏ hàng
    const flyImg = document.createElement('img');
    flyImg.src = img.src;
    flyImg.classList.add('fly-to-cart');
    
    const rect = img.getBoundingClientRect();
    flyImg.style.top = rect.top + 'px';
    flyImg.style.left = rect.left + 'px';
    document.body.appendChild(flyImg);

    const cartRect = cartTarget.getBoundingClientRect();
    
    setTimeout(() => {
        flyImg.style.top = cartRect.top + 'px';
        flyImg.style.left = cartRect.left + 'px';
        flyImg.style.width = '20px';
        flyImg.style.height = '20px';
        flyImg.style.opacity = '0';
    }, 50);

    // Gửi yêu cầu ngầm bằng Fetch API thay vì chuyển trang đột ngột
    fetch(`themgiohang.php?action=add&id=${productId}`)
    .then(response => {
        // Sau khi thêm vào CSDL/Session thành công dưới nền
        setTimeout(() => {
            flyImg.remove();
            
            // Cập nhật lại con số trên biểu tượng giỏ hàng ngay lập tức
            let cartCountEl = document.getElementById('cart-count');
            currentCartCount++;
            
            if (cartCountEl) {
                cartCountEl.innerText = currentCartCount;
            } else {
                // Nếu trước đó giỏ hàng trống (chưa có badge số lượng), tạo mới badge
                const bagIcon = document.getElementById('cart-target');
                if (bagIcon) {
                    const newBadge = document.createElement('span');
                    newBadge.id = 'cart-count';
                    newBadge.className = 'absolute -top-1 -right-1 bg-amber-400 text-gray-900 font-bold text-[10px] w-5 h-5 flex items-center justify-center rounded-full shadow-md border-2 border-rose-600';
                    newBadge.innerText = currentCartCount;
                    bagIcon.appendChild(newBadge);
                }
            }
        }, 850);
    })
    .catch(error => {
        console.error('Lỗi khi thêm vào giỏ hàng:', error);
        flyImg.remove();
    });
}

    function initSlider(){
        document.querySelectorAll('.slider').forEach(slider => {
            let images = JSON.parse(slider.dataset.images);
            let imgTag = slider.querySelector('.slider-img');
            let dots = slider.querySelectorAll('.dot');
            let current = 0;

            function updateSlider(index){
                current = index;
                imgTag.src = images[current];

                dots.forEach((dot,i)=>{
                    dot.classList.remove('bg-rose-600', 'w-3');
                    dot.classList.add('bg-gray-300', 'w-1.5');
                    if(i === current){
                        dot.classList.remove('bg-gray-300', 'w-1.5');
                        dot.classList.add('bg-rose-600', 'w-3');
                    }
                });
            }

            let nextBtn = slider.querySelector('.next');
            let prevBtn = slider.querySelector('.prev');

            if(nextBtn){
                nextBtn.addEventListener('click',(e)=>{
                    e.preventDefault();
                    let next = (current + 1) % images.length;
                    updateSlider(next);
                });
            }

            if(prevBtn){
                prevBtn.addEventListener('click',(e)=>{
                    e.preventDefault();
                    let prev = (current - 1 + images.length) % images.length;
                    updateSlider(prev);
                });
            }

            dots.forEach((dot,index)=>{
                dot.addEventListener('click',(e)=>{
                    e.preventDefault();
                    updateSlider(index);
                });
            });
        });
    }

    function toggleFavorite(id, el){
        const icon = el.querySelector('i');
        const strId = String(id);
        if(favorites.includes(strId)){
            favorites = favorites.filter(x => x != strId);
            icon.classList.remove('fa-solid', 'text-rose-600');
            icon.classList.add('fa-regular', 'text-gray-500');
        } else {
            favorites.push(strId);
            icon.classList.remove('fa-regular', 'text-gray-500');
            icon.classList.add('fa-solid', 'text-rose-600');
        }
        localStorage.setItem('favorites', JSON.stringify(favorites));
    }
</script>

<!-- Tawk.to Chat Script -->
<script type="text/javascript">
var Tawk_API=Tawk_API||{}, Tawk_LoadStart=new Date();
(function(){
var s1=document.createElement("script"),s0=document.getElementsByTagName("script")[0];
s1.async=true;
s1.src='https://embed.tawk.to/YOUR_PROPERTY_ID/DEFAULT';
s1.charset='UTF-8';
s1.setAttribute('crossorigin','*');
s0.parentNode.insertBefore(s1,s0);
})();
</script>

</body>
</html>