<?php
session_start();

// Kết nối CSDL
$conn = new mysqli("localhost", "root", "", "qlybanhang");
if ($conn->connect_error) die("Lỗi kết nối CSDL");
mysqli_set_charset($conn, "utf8");

// 1. Lấy dữ liệu từ URL - KHÔNG dùng intval cho brand
$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
$brand = isset($_GET['brand']) ? trim($_GET['brand']) : ''; // Giữ nguyên định dạng chuỗi

$products = [];
$brandName = "";

// 2. Nếu có brand -> lấy tên thương hiệu (Dùng kiểu "s" cho chuỗi)
if(!empty($brand)){
    $stmtBrand = $conn->prepare("SELECT tenthuonghieu FROM thuonghieu WHERE id_thuonghieu=?");
    $stmtBrand->bind_param("s", $brand);
    $stmtBrand->execute();
    $brandResult = $stmtBrand->get_result();
    if($brandRow = $brandResult->fetch_assoc()){
        $brandName = $brandRow['tenthuonghieu'];
    }
}

// 3. Tạo SQL động
$sql = "SELECT sp.*, ha.duongdan 
        FROM sanpham sp
        LEFT JOIN hinhanh_sanpham ha ON sp.masp = ha.masp
        WHERE sp.trangthai = 1";

$params = [];
$types = "";

// Tìm theo tên
if($keyword !== ''){
    $sql .= " AND sp.tensp LIKE ?";
    $params[] = "%$keyword%";
    $types .= "s";
}

// 4. Lọc theo thương hiệu (Dùng kiểu "s" cho chuỗi)
if(!empty($brand)){
    $sql .= " AND sp.id_thuonghieu = ?";
    $params[] = $brand;
    $types .= "s";
}

$sql .= " ORDER BY sp.masp DESC";

$stmt = $conn->prepare($sql);

if(!empty($params)){
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

// Gom ảnh sản phẩm
while ($row = $result->fetch_assoc()) {
    $masp = $row['masp'];

    if(!isset($products[$masp])){
        $products[$masp] = $row;
        $products[$masp]['images'] = [];
    }

    if(!empty($row['duongdan'])){
        $products[$masp]['images'][] = "/Shopbanhang/".$row['duongdan'];
    }
}

// Đếm giỏ hàng
$cartCount = 0;
if (isset($_SESSION['giohang'])) {
    foreach ($_SESSION['giohang'] as $sp) {
        $cartCount += $sp['soluong'];
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
    <?php 
    if(!empty($brand)) echo "Thương hiệu ".$brandName;
    else echo "Tìm kiếm ".$keyword;
    ?>
    | Triple Beauty
    </title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #fcfcfd; margin: 0; }
        .product-card { background: white; transition: all 0.3s ease; border: 1px solid #ffe4e8; cursor: pointer; }
        .product-card:hover { transform: translateY(-6px); box-shadow: 0 12px 25px rgba(163, 29, 55, 0.08); border-color: #a31d37; }
        .slider { position: relative; }
        .dots { position: absolute; bottom: 5px; left: 50%; transform: translateX(-50%); display: flex; gap: 4px; }
        .dot { width: 7px; height: 7px; background: #ddd; border-radius: 50%; cursor: pointer; }
        .dot.active { background: #a31d37; }
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
            <div class="container mx-auto px-4 flex space-x-8 text-xs font-bold uppercase tracking-wider whitespace-nowrap text-gray-600 py-3">
                <a href="gioithieu.php" class="hover:text-rose-600 transition">Giới thiệu</a>
                <a href="index.php" class="text-rose-600 border-b-2 border-rose-600 pb-1"> <i class="fa-solid fa-house"></i> Trang chủ</a>
                <a href="donhangcuatoi.php" class="hover:text-rose-600 transition">Đơn hàng của tôi</a>
                <a href="sanpham_damua.php" class="hover:text-rose-600 transition">Lịch sử đơn mua</a>
                <a href="voucher.php" class="hover:text-rose-600 transition">Voucher</a>
                <a href="profile.php" class="hover:text-rose-600 transition">Hồ sơ</a>
                <a href="yeuthich.php" class="hover:text-rose-600 transition">Yêu thích</a>
            </div>
        </nav>
    </header>

    <main class="container mx-auto px-4 py-8">
        <div class="flex flex-col lg:flex-row gap-6 items-start">
            
            <!-- Sidebar bên trái -->
            <aside class="w-full lg:w-1/4 space-y-4 sticky top-28">
                <a href="index.php" class="flex items-center gap-3 bg-white p-4 rounded-2xl shadow-sm border border-pink-100 text-[#a31d37] font-bold text-xs hover:bg-pink-50 transition group">
                    <span class="w-1 h-5 bg-[#a31d37] rounded-full group-hover:scale-125 transition"></span>
                    <i class="fa-solid fa-house"></i> VỀ TRANG CHỦ
                </a>
                
                <div class="bg-gradient-to-r from-[#a31d37] to-[#d42a44] rounded-2xl p-5 text-white shadow-sm">
                    <h4 class="font-bold mb-1 uppercase text-xs text-yellow-300 tracking-wider">Triple Beauty Member</h4>
                    <p class="text-[11px] opacity-90 leading-relaxed">Đăng nhập ngay để không bỏ lỡ voucher ưu đãi độc quyền dành riêng cho bạn.</p>
                </div>
            </aside>

            <!-- Nội dung kết quả tìm kiếm bên phải -->
            <div class="w-full lg:w-3/4">
                <div class="bg-white rounded-2xl p-6 mb-6 shadow-sm flex flex-col justify-between border-b-4 border-[#a31d37]">
                    <h2 class="text-lg md:text-xl font-extrabold uppercase italic tracking-tight text-slate-800">
                        Kết quả cho:
                        <span class="text-[#a31d37]">
                            <?= !empty($brand) ? htmlspecialchars($brandName) : '"' . htmlspecialchars($keyword) . '"' ?>
                        </span>
                    </h2>
                    <p class="text-slate-400 text-[11px] mt-1 font-bold uppercase tracking-widest">
                        Tìm thấy <?= count($products) ?> sản phẩm phù hợp
                    </p>
                </div>

                <?php if (count($products) > 0): ?>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                        <?php foreach ($products as $p): 
                            $images = !empty($p['images']) ? $p['images'] : ["/Shopbanhang/images/no-image.png"];
                            $imgs_json = json_encode($images);
                            $hasSale = ($p['giakhuyenmai'] > 0 && $p['giakhuyenmai'] < $p['gia']);
                            $salePercent = $hasSale ? round(100 - ($p['giakhuyenmai'] / $p['gia']) * 100) : 0;
                            
                            $productData = htmlspecialchars(json_encode([
                                'masp' => $p['masp'],
                                'tensp' => $p['tensp'],
                                'gia' => number_format($p['gia']) . 'đ',
                                'giakhuyenmai' => ($hasSale ? number_format($p['giakhuyenmai']) . 'đ' : ''),
                                'hasSale' => $hasSale,
                                'mota' => isset($p['mota']) ? $p['mota'] : 'Chưa có mô tả cho sản phẩm này.',
                                'images' => $images
                            ]), ENT_QUOTES, 'UTF-8');
                        ?>
                        <div class="product-card rounded-2xl p-4 flex flex-col relative overflow-hidden h-full shadow-sm" onclick="openQuickView(event, <?= $productData ?>)">
                            <?php if ($hasSale): ?>
                                <div class="absolute top-3 right-3 z-10 bg-gradient-to-r from-[#a31d37] to-[#d42a44] text-white text-[9px] font-black px-2.5 py-1 rounded-full shadow-md">
                                    -<?= $salePercent ?>%
                                </div>
                            <?php endif; ?>

                            <div class="slider h-40 flex items-center justify-center mb-3 mt-2 bg-slate-50/50 rounded-xl p-2"
                                 data-images='<?= $imgs_json ?>'>
                                <img src="<?= $images[0] ?>"
                                     class="slider-img max-h-full max-w-full object-contain mix-blend-multiply">

                                <?php if(count($images) > 1): ?>
                                    <button class="prev absolute left-1 text-white bg-black/40 px-2 py-1 rounded text-xs" onclick="event.stopPropagation()">&#10094;</button>
                                    <button class="next absolute right-1 text-white bg-black/40 px-2 py-1 rounded text-xs" onclick="event.stopPropagation()">&#10095;</button>

                                    <div class="dots absolute bottom-1 flex gap-1" onclick="event.stopPropagation()">
                                        <?php foreach($images as $i=>$img): ?>
                                            <span class="dot <?= $i==0?'active':'' ?>"></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <h3 class="font-bold text-slate-800 text-xs line-clamp-2 h-9 mb-3 leading-snug uppercase tracking-tight">
                                <?= $p['tensp'] ?>
                            </h3>

                            <div class="mt-auto border-t border-slate-50 pt-3">
                                <div class="flex flex-col mb-3">
                                    <span class="text-[#a31d37] font-extrabold text-sm"><?= number_format($hasSale ? $p['giakhuyenmai'] : $p['gia']) ?>đ</span>
                                    <?php if ($hasSale): ?>
                                        <span class="text-slate-400 text-[10px] line-through italic font-bold"><?= number_format($p['gia']) ?>đ</span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="grid grid-cols-2 gap-2" onclick="event.stopPropagation()">
                                    <a href="chitiet.php?id=<?= $p['masp'] ?>" 
                                       class="flex items-center justify-center bg-pink-50 text-[#a31d37] font-bold text-[10px] py-2.5 rounded-xl hover:bg-[#a31d37] hover:text-white transition-all uppercase tracking-tight">
                                        Chi tiết
                                    </a>
                                    <a href="themgiohang.php?action=add&id=<?= $p['masp'] ?>" 
                                       class="flex items-center justify-center bg-slate-900 text-white font-bold text-[10px] py-2.5 rounded-xl hover:bg-[#a31d37] transition-all uppercase tracking-tight gap-1 shadow-xs">
                                       <i class="fa fa-plus"></i> Thêm
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="bg-white rounded-3xl py-20 text-center border border-dashed border-pink-200 shadow-sm">
                        <div class="bg-pink-50 w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-4">
                             <i class="fa-solid fa-box-open text-pink-300 text-3xl"></i>
                        </div>
                        <p class="text-slate-400 font-bold uppercase tracking-wider text-xs">Không tìm thấy sản phẩm phù hợp</p>
                        <a href="index.php" class="inline-block mt-6 bg-[#a31d37] text-white px-6 py-2.5 rounded-xl font-bold uppercase text-[10px] shadow-sm hover:bg-red-800 transition">Tiếp tục mua sắm</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Phần cam kết -->
        <section class="py-12 bg-pink-50/60 mt-16 rounded-3xl border border-pink-100 shadow-xs">
            <div class="container mx-auto px-6 text-center">
                <h2 class="text-xl md:text-2xl font-black text-[#a31d37] uppercase italic mb-8">Cam Kết Từ Triple Beauty</h2>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <?php 
                    $commitments = [
                        ['icon' => 'fa-certificate', 'text' => 'Chính Hãng 100%'],
                        ['icon' => 'fa-truck-fast', 'text' => 'Giao Hàng Nhanh'],
                        ['icon' => 'fa-rotate-left', 'text' => 'Đổi Trả 7 Ngày'],
                        ['icon' => 'fa-headset', 'text' => 'Hỗ Trợ 24/7']
                    ];
                    foreach ($commitments as $item): ?>
                        <div class="bg-white p-5 rounded-2xl shadow-xs hover:shadow-md transition-all">
                            <i class="fa-solid <?= $item['icon'] ?> text-[#a31d37] text-2xl mb-3"></i>
                            <h3 class="font-bold text-slate-800 text-[11px] uppercase tracking-wider"><?= $item['text'] ?></h3>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    </main>

    <!-- Modal Xem Nhanh Sản Phẩm -->
    <div id="quickViewModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs hidden">
        <div class="bg-white w-full max-w-3xl rounded-3xl overflow-hidden shadow-2xl relative mx-4 transform transition-all animate-in fade-in zoom-in duration-200">
            <button onclick="closeQuickView()" class="absolute top-4 right-4 z-20 w-9 h-9 bg-gray-100 hover:bg-[#a31d37] hover:text-white rounded-full flex items-center justify-center text-gray-600 transition">
                <i class="fa fa-times"></i>
            </button>
            <div class="grid grid-cols-1 md:grid-cols-2 p-6 gap-6 items-center">
                <div class="relative bg-slate-50 rounded-2xl p-4 flex items-center justify-center h-72">
                    <img id="modalImg" src="" class="max-h-full max-w-full object-contain mix-blend-multiply">
                </div>
                <div class="flex flex-col h-full justify-between">
                    <div>
                        <span class="text-[10px] font-bold uppercase tracking-widest text-[#a31d37] bg-pink-50 px-2.5 py-1 rounded-full">Xem nhanh sản phẩm</span>
                        <h3 id="modalTitle" class="text-base font-extrabold text-slate-800 uppercase mt-3 mb-2 leading-snug"></h3>
                        <div class="flex items-center gap-3 mb-4">
                            <span id="modalPrice" class="text-xl font-black text-[#a31d37]"></span>
                            <span id="modalOldPrice" class="text-xs text-gray-400 line-through font-bold"></span>
                        </div>
                        <div class="text-xs text-slate-600 leading-relaxed max-h-32 overflow-y-auto pr-2 mb-6" id="modalDesc"></div>
                    </div>
                    <div class="flex gap-3 pt-4 border-t border-gray-100">
                        <a id="modalDetailBtn" href="#" class="flex-1 bg-gray-100 text-gray-800 text-center py-3 rounded-xl font-bold text-xs uppercase hover:bg-gray-200 transition">Xem chi tiết</a>
                        <a id="modalAddBtn" href="#" class="flex-1 bg-[#a31d37] text-white text-center py-3 rounded-xl font-bold text-xs uppercase hover:bg-red-800 transition shadow-md">Thêm vào giỏ</a>
                    </div>
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
    document.querySelectorAll('.slider').forEach(slider => {
        let images = JSON.parse(slider.dataset.images);
        let imgTag = slider.querySelector('.slider-img');
        let dots = slider.querySelectorAll('.dot');
        let current = 0;

        function updateSlider(index){
            current = index;
            imgTag.src = images[current];

            dots.forEach((d,i)=>{
                d.classList.remove('active');
                if(i===current) d.classList.add('active');
            });
        }

        let nextBtn = slider.querySelector('.next');
        let prevBtn = slider.querySelector('.prev');

        if(nextBtn){
            nextBtn.onclick = (e) => {
                e.preventDefault();
                updateSlider((current+1)%images.length);
            }
        }

        if(prevBtn){
            prevBtn.onclick = (e) => {
                e.preventDefault();
                updateSlider((current-1+images.length)%images.length);
            }
        }

        dots.forEach((dot,index)=>{
            dot.onclick = (e) => {
                e.preventDefault();
                updateSlider(index);
            }
        });
    });

    // Hàm xử lý Xem nhanh (Quick View) khi bấm vào thẻ sản phẩm
   function openQuickView(event, product) {
    document.getElementById('modalImg').src = product.images[0];
    document.getElementById('modalTitle').innerText = product.tensp;
    
    // XỬ LÝ MÔ TẢ: Cắt ngắn còn 150 ký tự + thêm dòng thông báo
    let desc = product.mota;
    if (desc.length > 150) {
        desc = desc.substring(0, 150) + "... <br><br><span class='text-[#a31d37] font-bold italic'>Nhấn xem chi tiết để biết thêm về sản phẩm</span>";
    }
    document.getElementById('modalDesc').innerHTML = desc; // Dùng innerHTML để render thẻ <br> và <span>
    
    // ... các phần còn lại giữ nguyên
    if (product.hasSale) {
        document.getElementById('modalPrice').innerText = product.giakhuyenmai;
        document.getElementById('modalOldPrice').innerText = product.gia;
        document.getElementById('modalOldPrice').style.display = 'inline';
    } else {
        document.getElementById('modalPrice').innerText = product.gia;
        document.getElementById('modalOldPrice').style.display = 'none';
    }

    document.getElementById('modalDetailBtn').href = 'chitiet.php?id=' + product.masp;
    document.getElementById('modalAddBtn').href = 'themgiohang.php?action=add&id=' + product.masp;

    document.getElementById('quickViewModal').classList.remove('hidden');
}
    function closeQuickView() {
        document.getElementById('quickViewModal').classList.add('hidden');
    }

    // Đóng modal khi bấm ra ngoài vùng nội dung modal
    window.onclick = function(event) {
        let modal = document.getElementById('quickViewModal');
        if (event.target == modal) {
            closeQuickView();
        }
    }
    </script>
</body>
</html>