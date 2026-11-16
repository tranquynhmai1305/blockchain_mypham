<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/Shopbanhang/conn.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/Shopbanhang/func.php';

if (!isset($_SESSION['userid'])) {
    $_SESSION['redirect_after_login'] = 'giohang.php';
    header("Location: login.php");
    exit;
}

$userid = $_SESSION['userid'];

// Lấy thông tin khách hàng để check điều kiện voucher nếu cần
$sql_kh = "SELECT makh FROM khachhang WHERE id_users = ? LIMIT 1";
$stmt_kh = mysqli_prepare($conn, $sql_kh);
mysqli_stmt_bind_param($stmt_kh, "i", $userid);
mysqli_stmt_execute($stmt_kh);
$khachhang = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_kh));
$makh = $khachhang['makh'] ?? '';

$giohang = $_SESSION['giohang'] ?? [];
$cartCount = 0;
foreach ($giohang as $sp) $cartCount += $sp['soluong'];

// Lấy danh sách voucher hợp lệ từ cơ sở dữ liệu
$vouchers = [];
$sql_v = "SELECT * FROM vouchers WHERE ngay_ket_thuc >= CURDATE() AND soluong > dadung ORDER BY ngay_ket_thuc ASC";
$result_v = mysqli_query($conn, $sql_v);
if ($result_v) {
    while ($row = mysqli_fetch_assoc($result_v)) {
        $vouchers[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Giỏ hàng của bạn | Triple Beauty</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #fcfcfd; }
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
                <a href="index.php" class="hover:text-rose-600 transition"> <i class="fa-solid fa-house"></i> Trang chủ</a>
            </div>
        </nav>
    </header>

    <main class="container mx-auto px-4 py-10 max-w-6xl">

        <?php if (empty($giohang)): ?>
            <div class="bg-white p-16 text-center rounded-3xl border border-pink-100 shadow-sm">
                <div class="w-20 h-20 bg-pink-50 rounded-full flex items-center justify-center mx-auto mb-5">
                    <i class="fa fa-cart-shopping text-3xl text-pink-300"></i>
                </div>
                <p class="text-slate-400 font-bold text-base">Giỏ hàng của bạn đang trống</p>
                <a href="index.php" class="inline-block mt-5 bg-[#a31d37] text-white px-8 py-3 rounded-full font-bold text-sm hover:bg-red-800 transition shadow-md">Khám phá ngay</a>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Danh sách sản phẩm trong giỏ -->
                <div class="lg:col-span-2 bg-white rounded-3xl shadow-sm border border-slate-100 p-6 overflow-hidden">
                    <div class="flex justify-between items-center pb-4 mb-4 border-b border-slate-100">
                        <h2 class="font-extrabold text-slate-800 text-base uppercase tracking-wider">Sản phẩm trong giỏ</h2>
                        <label class="flex items-center gap-2 text-xs font-bold text-slate-500 cursor-pointer">
                            <input type="checkbox" id="check-all" class="accent-[#a31d37] w-4 h-4 cursor-pointer" checked> Chọn tất cả
                        </label>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <tbody id="cart-body">
                                <?php foreach ($giohang as $id => $sp): 
                                    $thanhtien = $sp['gia'] * $sp['soluong'];
                                ?>
                                <tr class="border-b border-slate-50 last:border-0 cart-item text-sm" data-id="<?= $id ?>">
                                    <td class="py-4 px-2 text-center w-10"><input type="checkbox" class="item-checkbox accent-[#a31d37] w-4 h-4 cursor-pointer align-middle" value="<?= $id ?>" checked></td>
                                    <td class="py-4 px-2 flex items-center gap-4">
                                        <img src="/Shopbanhang/images/<?= htmlspecialchars($sp['hinh']) ?>" class="w-14 h-14 object-cover rounded-xl border border-slate-100 flex-shrink-0">
                                        <div>
                                            <p class="font-bold text-slate-800 text-xs line-clamp-2"><?= $sp['ten'] ?></p>
                                            <p class="text-[#a31d37] font-bold text-xs mt-1"><?= number_format($sp['gia']) ?>đ</p>
                                        </div>
                                    </td>
                                    <td class="py-4 px-2 text-center">
                                        <div class="inline-flex items-center border border-slate-200 rounded-lg bg-slate-50 overflow-hidden text-xs">
                                            <button onclick="updateCart('<?= $id ?>','minus')" class="px-2.5 py-1 hover:bg-slate-200 transition text-slate-600">−</button>
                                            <span id="qty-<?= $id ?>" class="px-3 font-bold text-slate-700"><?= $sp['soluong'] ?></span>
                                            <button onclick="updateCart('<?= $id ?>','plus')" class="px-2.5 py-1 hover:bg-slate-200 transition text-slate-600">+</button>
                                        </div>
                                    </td>
                                    <td class="py-4 px-2 text-right font-black text-slate-800 text-xs">
                                        <span id="total-<?= $id ?>" class="item-subtotal" data-price="<?= $thanhtien ?>"><?= number_format($thanhtien) ?></span>đ
                                    </td>
                                    <td class="py-4 px-2 text-center w-10">
                                        <button onclick="updateCart('<?= $id ?>','remove')" class="text-slate-300 hover:text-red-600 transition p-2"><i class="fa fa-trash text-xs"></i></button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Tổng tiền & Chọn Voucher -->
                <div class="space-y-6">
                    <!-- Khung chọn Voucher -->
                    <div class="bg-white rounded-3xl shadow-sm border border-slate-100 p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="font-extrabold text-slate-800 text-sm uppercase flex items-center gap-2">
                                <i class="fa-solid fa-ticket text-rose-600"></i> Voucher Triple Beauty
                            </h3>
                            <button onclick="openVoucherModal()" class="text-xs font-bold text-rose-600 hover:underline">Chọn mã khác</button>
                        </div>
                        
                        <div id="selected-voucher-box" class="hidden bg-rose-50 border border-rose-200 rounded-2xl p-3 flex items-center justify-between">
                            <div>
                                <span id="applied-code" class="text-xs font-black text-rose-700 uppercase"></span>
                                <p id="applied-desc" class="text-[11px] text-slate-500 mt-0.5"></p>
                            </div>
                            <button onclick="removeVoucher()" class="text-slate-400 hover:text-red-600 text-xs font-bold px-2 py-1"><i class="fa fa-xmark"></i></button>
                        </div>

                        <div id="no-voucher-box" class="bg-slate-50 border border-dashed border-slate-200 rounded-2xl p-4 text-center cursor-pointer hover:bg-rose-50/50 transition" onclick="openVoucherModal()">
                            <p class="text-xs font-bold text-slate-500"><i class="fa fa-plus-circle text-rose-600 mr-1"></i> Chọn hoặc nhập mã giảm giá</p>
                        </div>
                    </div>

                    <!-- Thanh toán tóm tắt -->
                    <div class="bg-white rounded-3xl shadow-sm border border-slate-100 p-6 space-y-4">
                        <h3 class="font-extrabold text-slate-800 text-sm uppercase border-b border-slate-100 pb-3">Cộng giỏ hàng</h3>
                        
                        <div class="flex justify-between text-xs text-slate-500 font-medium">
                            <span>Sản phẩm đã chọn (<span id="selected-count" class="font-bold text-slate-700">0</span>):</span>
                            <span id="sub-total" class="font-bold text-slate-800">0đ</span>
                        </div>

                        <div class="flex justify-between text-xs text-slate-500 font-medium">
                            <span>Giảm giá từ voucher:</span>
                            <span id="discount-amount" class="font-bold text-emerald-600">-0đ</span>
                        </div>

                        <div class="border-t border-slate-100 pt-3 flex justify-between items-center">
                            <span class="text-xs font-bold text-slate-700 uppercase">Tổng cộng:</span>
                            <span id="display-total" class="text-xl font-black text-[#a31d37]">0đ</span>
                        </div>

                        <button id="btn-checkout" class="w-full bg-[#a31d37] text-white py-3.5 rounded-2xl font-bold hover:bg-red-800 transition shadow-lg shadow-pink-100 uppercase tracking-widest text-xs">
                            Thanh toán ngay
                        </button>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <!-- Modal Chọn Voucher -->
    <div id="voucherModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center hidden opacity-0 transition-opacity duration-300 p-4">
        <div class="bg-white rounded-3xl w-full max-w-lg overflow-hidden shadow-2xl transform scale-95 transition-transform duration-300 flex flex-col max-h-[80vh]">
            <div class="bg-gradient-to-r from-rose-900 to-rose-700 p-5 text-white flex justify-between items-center">
                <h3 class="font-black text-sm uppercase tracking-wider"><i class="fa-solid fa-ticket mr-2"></i> Chọn mã ưu đãi</h3>
                <button onclick="closeVoucherModal()" class="text-white hover:text-rose-200 text-base font-bold"><i class="fa fa-xmark"></i></button>
            </div>

            <div class="p-5 overflow-y-auto space-y-3 flex-1 bg-slate-50">
                <?php if(empty($vouchers)): ?>
                    <p class="text-center text-xs text-slate-400 py-8">Hiện tại không có mã giảm giá khả dụng.</p>
                <?php else: ?>
                    <?php foreach($vouchers as $v): ?>
                        <div class="bg-white border border-slate-200 rounded-2xl p-4 flex items-center justify-between shadow-xs hover:border-rose-300 transition">
    <div class="space-y-1 pr-4">
        <span class="bg-rose-50 text-rose-600 text-[10px] font-black uppercase px-2 py-0.5 rounded"><?= htmlspecialchars($v['ma_voucher']) ?></span>
        <h4 class="text-xs font-extrabold text-slate-800">
            <?php if($v['loai_giam'] == 'phamtram'): ?>
                Giảm <?= $v['giam_toi_da'] ?>% (Đơn tối thiểu <?= number_format($v['giatri_dh_toithieu'] * 1000) ?>VND)
            <?php else: ?>
                Giảm <?= number_format($v['giam_toi_da'] * 1000) ?>VND (Đơn tối thiểu <?= number_format($v['giatri_dh_toithieu'] * 1000) ?>VND)
            <?php endif; ?>
        </h4>
        <p class="text-[10px] text-slate-400">
            HSD: <?= date('d/m/Y', strtotime($v['ngay_ket_thuc'])) ?>
        </p>
    </div>
    <!-- Truyền trực tiếp giá trị đã nhân 1000 vào hàm applyVoucher -->
    <button type="button" onclick="applyVoucher('<?= htmlspecialchars($v['ma_voucher']) ?>', '<?= $v['loai_giam'] ?>', <?= $v['giam_toi_da'] ?>, <?= $v['giatri_dh_toithieu'] ?>)" class="bg-rose-600 text-white px-4 py-2 rounded-xl text-xs font-bold uppercase hover:bg-rose-700 transition shadow-sm flex-shrink-0">
        Dùng
    </button>
</div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <div class="p-4 border-t border-slate-100 bg-white flex justify-end">
                <button onclick="closeVoucherModal()" class="bg-slate-200 hover:bg-slate-300 text-slate-700 px-5 py-2 rounded-xl font-bold text-xs uppercase transition">Đóng</button>
            </div>
        </div>
    </div>

    <!-- Footer Tiêu Chuẩn -->
    <footer class="bg-black text-white pt-10 pb-6 mt-16 border-t border-slate-900">
        <div class="max-w-7xl mx-auto px-6 text-center text-[10px] text-slate-500 font-bold uppercase tracking-wider">
            <p>&copy; 2026 TRIPLE BEAUTY VIỆT NAM. ĐÃ ĐĂNG KÝ BẢN QUYỀN.</p>
        </div>
    </footer>

  <script>
    let currentVoucher = null;

    function updateCart(id, action) {
        fetch('capnhatgiohang.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `id=${id}&action=${action}`
        })
        .then(res => res.json())
        .then(data => {
            if (data.status !== 'success') { if (data.message) alert(data.message); return; }
            if (action === 'remove' || !data.cart[id]) { location.reload(); return; }
            let item = data.cart[id];
            document.getElementById('qty-' + id).innerText = item.soluong;
            let totalEl = document.getElementById('total-' + id);
            totalEl.innerText = new Intl.NumberFormat().format(item.gia * item.soluong);
            totalEl.dataset.price = item.gia * item.soluong;
            updateCartSummary();
        });
    }

    function updateCartSummary() {
        let subtotal = 0, count = 0;
        document.querySelectorAll('.item-checkbox:checked').forEach(cb => {
            let row = cb.closest('tr');
            subtotal += parseInt(row.querySelector('.item-subtotal').dataset.price) || 0;
            count++;
        });

        document.getElementById('sub-total').innerText = new Intl.NumberFormat('vi-VN').format(subtotal) + 'VND';
        document.getElementById('selected-count').innerText = count;

        let discount = 0;
        if (currentVoucher) {
            if (currentVoucher.minOrder > 0 && subtotal < currentVoucher.minOrder) {
                discount = 0;
            } else {
                if (currentVoucher.loaiGiam === 'phamtram') {
                    discount = (subtotal * currentVoucher.value) / 100;
                } else if (currentVoucher.loaiGiam === 'tien') {
                    // Nhân giá trị với 1000 (ví dụ 50 thành 50.000)
                    discount = currentVoucher.value * 1000; 
                }
            }
        }

        let finalTotal = Math.max(0, subtotal - discount);

        document.getElementById('discount-amount').innerText = '-' + new Intl.NumberFormat('vi-VN').format(discount) + 'VND';
        document.getElementById('display-total').innerText = new Intl.NumberFormat('vi-VN').format(finalTotal) + 'VND';
        document.getElementById('btn-checkout').disabled = (count === 0);
    }

    function applyVoucher(code, loaiGiam, rawValue, rawMinOrder) {
        currentVoucher = { 
            code: code, 
            loaiGiam: loaiGiam, 
            value: parseFloat(rawValue) || 0,
            minOrder: (parseFloat(rawMinOrder) || 0) * 1000 // Nhân điều kiện tối thiểu với 1000
        };
        
        let subtotal = 0;
        document.querySelectorAll('.item-checkbox:checked').forEach(cb => {
            let row = cb.closest('tr');
            subtotal += parseInt(row.querySelector('.item-subtotal').dataset.price) || 0;
        });

        if (currentVoucher.minOrder > 0 && subtotal < currentVoucher.minOrder) {
            alert('Đơn hàng cần đạt tối thiểu ' + new Intl.NumberFormat('vi-VN').format(currentVoucher.minOrder) + 'VND để sử dụng mã này!');
        }

        let descText = (loaiGiam === 'phamtram') ? `Giảm ${rawValue}%` : `Giảm ${new Intl.NumberFormat('vi-VN').format(rawValue * 1000)}VND`;

        document.getElementById('applied-code').innerText = code;
        document.getElementById('applied-desc').innerText = descText;
        document.getElementById('selected-voucher-box').classList.remove('hidden');
        document.getElementById('no-voucher-box').classList.add('hidden');
        
        closeVoucherModal();
        updateCartSummary();
    }

    function removeVoucher() {
        currentVoucher = null;
        document.getElementById('selected-voucher-box').classList.add('hidden');
        document.getElementById('no-voucher-box').classList.remove('hidden');
        updateCartSummary();
    }

    function openVoucherModal() {
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
        setTimeout(() => { modal.classList.add("hidden"); }, 300);
    }

    document.addEventListener('DOMContentLoaded', () => {
        const checkAll = document.getElementById('check-all');
        const cbs = document.querySelectorAll('.item-checkbox');
        if (checkAll) {
            checkAll.onchange = () => { cbs.forEach(c => c.checked = checkAll.checked); updateCartSummary(); };
        }
        cbs.forEach(c => c.onchange = () => { updateCartSummary(); if(checkAll) checkAll.checked = Array.from(cbs).every(i => i.checked); });
        
        document.getElementById('btn-checkout').onclick = () => {
            const ids = Array.from(cbs).filter(c => c.checked).map(c => c.value);
            if (ids.length) {
                let url = 'thanhtoan.php?ids=' + ids.join(',');
                if (currentVoucher) {
                    url += '&voucher=' + encodeURIComponent(currentVoucher.code);
                }
                window.location.href = url;
            }
        };
        updateCartSummary();
    });
</script>
</body>
</html>