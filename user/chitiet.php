<?php
session_start(); 
require_once $_SERVER['DOCUMENT_ROOT'] . '/Shopbanhang/func.php'; 

if (!isset($_GET['id'])) {
    die("Không tìm thấy sản phẩm");
}

$id = $_GET['id']; 

// 2. Lấy thông tin sản phẩm
$row = getSanPhamById($id);

if (!$row) {
    die("Sản phẩm không tồn tại");
}

// 3. Lấy danh sách hình ảnh
$list_anh = getHinhAnhBySanPham($id);

// 4. KHAI BÁO VÀ TÍNH TOÁN SỐ LƯỢNG GIỎ HÀNG (Khắc phục lỗi undefined variable)
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
    <title><?= htmlspecialchars($row['tensp']) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../assets/css/chitietsp.css">
</head>
<body class="bg-gray-50 text-slate-800 font-sans">

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
                <a href="index.php" class="hover:text-rose-600 transition"> <i class="fa-solid fa-house"></i>Trang chủ</a>
                <a href="donhangcuatoi.php" class="hover:text-rose-600 transition">Đơn hàng của tôi</a>
                <a href="sanpham_damua.php" class="hover:text-rose-600 transition">Đánh giá sản phẩm</a>
                <a href="voucher.php" class="hover:text-rose-600 transition">Voucher</a>
                <a href="profile.php" class="hover:text-rose-600 transition">Hồ sơ</a>
                <a href="yeuthich.php" class="hover:text-rose-600 transition">Yêu thích</a>
            </div>
        </nav>
    </header>

<div class="max-w-7xl mx-auto px-4 py-6">
    <!-- BỐ CỤC CHÍNH: 2 CỘT -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        
        <!-- CỘT TRÁI (7 PHẦN): Sản phẩm, Mua hàng & Mô tả -->
        <div class="lg:col-span-7 flex flex-col gap-6">
            
            <div class="bg-white rounded-3xl shadow-sm border border-slate-100 p-6 md:p-8">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-center mb-6">
                    <?php
                    $images = [];
                    if(!empty($list_anh)){
                        foreach($list_anh as $img){
                            $images[] = "/Shopbanhang/" . $img['duongdan'];
                        }
                    }else{
                        $images[] = "/Shopbanhang/images/no-image.png";
                    }
                    $imgs_json = json_encode($images);
                    ?>
                    <div class="slider relative bg-gray-50 rounded-2xl overflow-hidden aspect-square flex items-center justify-center border border-gray-100" data-images='<?= $imgs_json ?>'>
                        <img src="<?= $images[0] ?>" class="slider-img w-full h-full object-contain p-4">

                        <?php if(count($images) > 1): ?>
                            <button class="prev absolute left-2 top-1/2 -translate-y-1/2 bg-white/90 hover:bg-white text-gray-700 w-7 h-7 rounded-full shadow flex items-center justify-center transition text-xs">&#10094;</button>
                            <button class="next absolute right-2 top-1/2 -translate-y-1/2 bg-white/90 hover:bg-white text-gray-700 w-7 h-7 rounded-full shadow flex items-center justify-center transition text-xs">&#10095;</button>

                            <div class="dots absolute bottom-2 left-0 right-0 flex justify-center gap-1.5">
                                <?php foreach($images as $i=>$img): ?>
                                    <span class="dot w-1.5 h-1.5 rounded-full bg-gray-300 cursor-pointer <?= $i==0?'active bg-rose-600 w-3':'' ?>"></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="flex flex-col justify-between">
                        <div>
                            <span class="text-xs font-bold text-rose-600 uppercase tracking-widest bg-rose-50 px-3 py-1 rounded-full inline-block mb-2">
                                <?= htmlspecialchars($row['tenthuonghieu'] ?? 'Thương hiệu') ?>
                            </span>
                            <h1 class="text-xl md:text-2xl font-black text-slate-900 leading-tight mb-3">
                                <?= htmlspecialchars($row['tensp']) ?>
                            </h1>

                            <div class="flex items-center gap-3 bg-gray-50 p-3 rounded-xl mb-4">
                                <?php 
                                $gia = (float)$row['gia']; $km = (float)$row['giakhuyenmai'];
                                if ($km > 0 && $km < $gia): ?>
                                    <span class="text-2xl font-black text-rose-600"><?= number_format($km) ?> đ</span>
                                    <span class="text-sm text-gray-400 line-through"><?= number_format($gia) ?> đ</span>
                                <?php else: ?>
                                    <span class="text-2xl font-black text-rose-600"><?= number_format($gia) ?> đ</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="text-xs text-gray-600 space-y-1.5 border-t border-gray-100 pt-3">
                            <p><b>Xuất xứ:</b> <?= htmlspecialchars($row['xuatxu'] ?? 'Đang cập nhật') ?></p>
                            <p><b>Dung tích:</b> <?= htmlspecialchars($row['dungtichsp'] ?? 'Đang cập nhật') ?></p>
                            <p><b>Hạn dùng:</b> <?= htmlspecialchars($row['han_sudung'] ?? 'Đang cập nhật') ?></p>
                            <p><b>Kho hàng:</b> <span class="font-semibold text-slate-800" id="max-stock"><?= htmlspecialchars($row['soluong']) ?></span></p>
                        </div>
                    </div>
                </div>

                <!-- Form chọn số lượng & Nút thao tác nằm gọn trên 1 hàng -->
                <div class="buy-box pt-4 border-t border-gray-100">
                    <form method="get" action="themgiohang.php" id="buy-form" class="flex items-center gap-3">
                        <input type="hidden" name="id" value="<?= $row['masp'] ?>">
                        
                        <div class="flex items-center border border-gray-200 rounded-xl overflow-hidden bg-gray-50 shrink-0">
                            <button type="button" id="decrease-btn" class="w-9 h-10 flex items-center justify-center text-gray-600 hover:bg-gray-200 transition font-bold text-base">-</button>
                            <input type="number" id="quantity-input" name="soluong" value="1" min="1" max="<?= $row['soluong'] ?>" class="w-10 text-center font-bold text-sm bg-transparent focus:outline-none [-appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none">
                            <button type="button" id="increase-btn" class="w-9 h-10 flex items-center justify-center text-gray-600 hover:bg-gray-200 transition font-bold text-base">+</button>
                        </div>

                        <div class="flex items-center gap-2 flex-1">
                            <button type="submit" formaction="themgiohang.php" class="flex-1 bg-rose-50 hover:bg-rose-100 text-rose-600 font-bold py-2.5 px-3 rounded-xl transition border border-rose-200 flex items-center justify-center gap-1.5 text-xs">
                                <i class="fa-solid fa-cart-plus text-sm"></i> Thêm giỏ
                            </button>
                            <button type="submit" formaction="muangay.php" class="flex-1 bg-rose-600 hover:bg-rose-700 text-white font-bold py-2.5 px-3 rounded-xl transition shadow-md shadow-rose-200 flex items-center justify-center gap-1.5 text-xs">
                                <i class="fa-solid fa-bag-shopping text-sm"></i> Mua ngay
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Khối Mô tả sản phẩm -->
            <div class="bg-white rounded-3xl shadow-sm border border-slate-100 p-6 md:p-8">
                <h2 class="text-base font-black uppercase tracking-wider text-slate-900 mb-4 pb-2 border-b border-gray-100">Mô tả sản phẩm</h2>
                <div class="text-gray-600 leading-relaxed text-sm space-y-2">
                    <?= !empty($row['mota']) ? nl2br(htmlspecialchars($row['mota'])) : "Chưa có mô tả chi tiết cho sản phẩm này." ?>
                </div>
            </div>
        </div>

        <!-- CỘT PHẢI (5 PHẦN): Toàn bộ Đánh giá sản phẩm -->
        <?php
            $sql_stats = "SELECT COUNT(*) as total, AVG(sosao) as average FROM danhgiasp WHERE masp = '$id'";
            $res_stats = mysqli_query($conn, $sql_stats);
            $stats = mysqli_fetch_assoc($res_stats);
            $total_reviews = $stats['total'];
            $avg_stars = round($stats['average'], 1) ?: 0;
        ?>
        <div class="lg:col-span-5 bg-white rounded-3xl shadow-sm border border-slate-100 p-6 md:p-8">
            <h2 class="text-lg font-black uppercase tracking-wider text-slate-900 mb-6 pb-2 border-b border-gray-100">Đánh giá sản phẩm</h2>
            
            <div class="bg-gray-50 p-5 rounded-2xl mb-6">
                <div class="flex items-baseline gap-2 mb-3">
                    <span class="text-3xl font-black text-slate-900"><?= $avg_stars ?></span> 
                    <span class="text-amber-500 text-lg">★</span>
                    <span class="text-xs text-gray-500 font-medium">(<?= $total_reviews ?> nhận xét)</span>
                </div>
                
                <div class="space-y-1.5">
                    <?php 
                    for($i=5; $i>=1; $i--) {
                        $sql_s = "SELECT COUNT(*) as cnt FROM danhgiasp WHERE masp = '$id' AND sosao = $i";
                        $res_s = mysqli_query($conn, $sql_s);
                        $count_s = mysqli_fetch_assoc($res_s)['cnt'];
                        $percent = ($total_reviews > 0) ? ($count_s / $total_reviews) * 100 : 0;
                    ?>
                    <div class="flex items-center text-xs text-gray-600 gap-2">
                        <span class="w-10"><?= $i ?> sao</span>
                        <div class="flex-1 bg-gray-200 h-2 rounded-full overflow-hidden">
                            <div class="bg-amber-400 h-full" style="width: <?= $percent ?>%;"></div>
                        </div>
                        <span class="w-5 text-right"><?= $count_s ?></span>
                    </div>
                    <?php } ?>
                </div>
            </div>

            <div class="space-y-6 max-h-[700px] overflow-y-auto pr-2">
                <?php
                $sql_list = "SELECT dg.*, kh.hoten FROM danhgiasp dg 
                             JOIN khachhang kh ON dg.makh = kh.makh 
                             WHERE dg.masp = '$id' ORDER BY dg.id_danhgia DESC";
                $res_list = mysqli_query($conn, $sql_list);

                if (mysqli_num_rows($res_list) > 0):
                    while ($dg = mysqli_fetch_assoc($res_list)):
                ?>
                    <div class="pb-5 border-b border-gray-100 last:border-none last:pb-0">
                        <div class="flex items-center justify-between mb-1.5">
                            <strong class="text-slate-900 text-sm font-bold"><?= htmlspecialchars($dg['hoten']) ?></strong>
                            <span class="text-amber-500 text-xs"><?= str_repeat('★', $dg['sosao']) ?></span>
                        </div>
                        <p class="text-xs text-gray-600 mb-2 leading-relaxed"><?= nl2br(htmlspecialchars($dg['noidung'])) ?></p>
                        
                        <?php if(!empty($dg['hinhanh'])): ?>
                            <img src="uploads/reviews/<?= $dg['hinhanh'] ?>" class="w-20 h-20 object-cover rounded-lg border border-gray-100 mb-2">
                        <?php endif; ?>

                        <?php if(!empty($dg['phanhoi'])): ?>
                            <div class="bg-rose-50/50 border-l-2 border-rose-600 p-2.5 rounded-r-lg text-[11px] mb-2">
                                <b class="text-rose-600">Triple Beauty phản hồi:</b>
                                <p class="text-gray-600 mt-0.5"><?= htmlspecialchars($dg['phanhoi']) ?></p>
                            </div>
                        <?php endif; ?>
                        
                        <div class="text-[10px] text-gray-400">
                            Đã đánh giá vào: <?= date("d/m/Y", strtotime($dg['ngaydanhgia'])) ?>
                        </div>
                    </div>
                <?php endwhile; else: ?>
                    <div class="text-center py-8 text-gray-400 text-xs">
                        Sản phẩm này chưa có nhận xét nào.
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<script>
const decreaseBtn = document.getElementById('decrease-btn');
const increaseBtn = document.getElementById('increase-btn');
const quantityInput = document.getElementById('quantity-input');
const maxStock = parseInt(document.getElementById('max-stock').innerText) || 1;

if (decreaseBtn && increaseBtn && quantityInput) {
    decreaseBtn.addEventListener('click', () => {
        let currentValue = parseInt(quantityInput.value) || 1;
        if (currentValue > 1) {
            quantityInput.value = currentValue - 1;
        }
    });

    increaseBtn.addEventListener('click', () => {
        let currentValue = parseInt(quantityInput.value) || 1;
        if (currentValue < maxStock) {
            quantityInput.value = currentValue + 1;
        }
    });

    quantityInput.addEventListener('change', () => {
        let currentValue = parseInt(quantityInput.value) || 1;
        if (currentValue < 1) quantityInput.value = 1;
        if (currentValue > maxStock) quantityInput.value = maxStock;
    });
}

document.querySelectorAll('.slider').forEach(slider => {
    let images = JSON.parse(slider.dataset.images);
    let imgTag = slider.querySelector('.slider-img');
    let dots = slider.querySelectorAll('.dot');
    let current = 0;

    function updateSlider(index) {
        current = index;
        imgTag.src = images[current];

        dots.forEach((d, i) => {
            d.classList.remove('active', 'bg-rose-600', 'w-3');
            d.classList.add('bg-gray-300', 'w-1.5');
            if(i === current) {
                d.classList.remove('bg-gray-300', 'w-1.5');
                d.classList.add('active', 'bg-rose-600', 'w-3');
            }
        });
    }

    let nextBtn = slider.querySelector('.next');
    let prevBtn = slider.querySelector('.prev');

    if(nextBtn) {
        nextBtn.onclick = () => {
            let next = (current + 1) % images.length;
            updateSlider(next);
        }
    }

    if(prevBtn) {
        prevBtn.onclick = () => {
            let prev = (current - 1 + images.length) % images.length;
            updateSlider(prev);
        }
    }

    dots.forEach((dot, index) => {
        dot.onclick = () => {
            updateSlider(index);
        }
    });
});
</script>
</body>
</html>