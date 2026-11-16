<?php
session_start();
// Sử dụng file kết nối conn.php của bạn
require_once $_SERVER['DOCUMENT_ROOT'] . '/Shopbanhang/conn.php';

// 1. Xử lý cập nhật số lượng qua AJAX (khi khách bấm tăng/giảm)
if (isset($_GET['action']) && $_GET['action'] === 'update_qty') {
    $id = $_GET['id'] ?? '';
    $qty = intval($_GET['qty'] ?? 1);
    if ($qty < 1) $qty = 1;
    if (isset($_SESSION['giohang'][$id])) {
        $_SESSION['giohang'][$id]['soluong'] = $qty;
    }
    exit;
}

// 2. Bảo vệ route
if (!isset($_SESSION['userid'])) {
    header("Location: login.php");
    exit;
}

// --- TỰ ĐỘNG LẤY THÔNG TIN KHÁCH HÀNG TỪ DATABASE ---
$user_hoten = "";
$user_sdt = "";
$user_diachi = "";
$user_info = [];

$userid = $_SESSION['userid'];

$sql_user = "SELECT hoten, sdt, diachi, ngaysinh FROM khachhang WHERE id_users = ? LIMIT 1";
$stmt_user = $conn->prepare($sql_user);
$stmt_user->bind_param("i", $userid);
$stmt_user->execute();
$result_user = $stmt_user->get_result();

if ($result_user && $result_user->num_rows === 1) {
    $user_info = $result_user->fetch_assoc();
    $user_hoten = $user_info['hoten'] ?? "";
    $user_sdt = $user_info['sdt'] ?? "";
    $user_diachi = $user_info['diachi'] ?? "";
}

// 3. Xử lý dữ liệu giỏ hàng
$selected_ids = isset($_GET['ids']) ? explode(',', $_GET['ids']) : [];
$giohang_full = $_SESSION['giohang'] ?? [];
$giohang_thanhtoan = array_intersect_key($giohang_full, array_flip($selected_ids));

if (empty($giohang_thanhtoan)) {
    header("Location: giohang.php");
    exit;
}

// 4. Tính tổng tiền tạm tính
$tongtien = array_reduce($giohang_thanhtoan, function($sum, $item) {
    return $sum + ($item['gia'] * $item['soluong']);
}, 0);

// 5. Lấy mã voucher từ URL
$url_voucher_code = isset($_GET['voucher']) ? trim($_GET['voucher']) : '';
$applied_voucher = null;
$discount_amount = 0;
$v_min_val = 0;
$v_val = 0;
$v_loai = '';

if (!empty($url_voucher_code)) {
    $stmt_v = $conn->prepare("SELECT * FROM vouchers WHERE ma_voucher = ? LIMIT 1");
    $stmt_v->bind_param("s", $url_voucher_code);
    $stmt_v->execute();
    $res_v = $stmt_v->get_result();
    if ($res_v && $res_v->num_rows > 0) {
        $applied_voucher = $res_v->fetch_assoc();
        $v_min_val = (float)($applied_voucher['giatri_dh_toithieu'] ?? 0) * 1000;
        $v_val = (float)($applied_voucher['giam_toi_da'] ?? 0);
        $v_loai = $applied_voucher['loai_giam'];
        
        if ($tongtien >= $v_min_val) {
            if ($v_loai == 'phamtram') {
                $discount_amount = ($tongtien * $v_val) / 100;
            } else {
                $discount_amount = $v_val * 1000;
            }
        }
    }
}

if ($discount_amount > $tongtien) {
    $discount_amount = $tongtien;
}
$tong_thanhtoan = $tongtien - $discount_amount;

// 6. Lấy danh sách toàn bộ voucher hợp lệ từ CSDL cho Modal
$sql_all_v = "SELECT * FROM vouchers WHERE ngay_ket_thuc >= NOW()";
$result_all_v = $conn->query($sql_all_v);
$list_vouchers = [];
if ($result_all_v) {
    while ($row = $result_all_v->fetch_assoc()) {
        $list_vouchers[] = $row;
    }
}

// Hàm format tiền tệ (sử dụng &nbsp; để chống xuống dòng số tiền)
function formatVND($amount) {
    return number_format($amount, 0, ',', '.') . '&nbsp;VND';
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thanh toán đơn hàng | Triple Beauty</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;900&display=swap');
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>

<body class="bg-slate-50 text-slate-800">

<header class="bg-[#a31d37] text-white py-4 shadow-md sticky top-0 z-50">
    <div class="container mx-auto px-4 flex justify-between items-center">
        <h1 class="text-2xl font-black italic uppercase tracking-tighter">Triple Beauty</h1>
        <a href="giohang.php?ids=<?= implode(',', $selected_ids) ?>" class="text-sm font-bold hover:opacity-80 transition flex items-center gap-2">
            <i class="fa fa-arrow-left"></i> Quay lại giỏ hàng
        </a>
    </div>
</header>

<main class="container mx-auto px-4 py-10 max-w-6xl">
    <form action="xulythanhtoan.php" method="POST" class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <input type="hidden" name="selected_ids" value="<?= implode(',', $selected_ids) ?>">
        <input type="hidden" name="voucher" id="input_voucher_code" value="<?= htmlspecialchars($url_voucher_code) ?>">
        <input type="hidden" name="discount_amount" id="input_discount" value="<?= $discount_amount ?>">

        <!-- CỘT TRÁI: THÔNG TIN GIAO HÀNG & THANH TOÁN (4 COLUMNS) -->
        <div class="lg:col-span-4 space-y-5">
            <section class="bg-white p-5 rounded-3xl shadow-xs border border-slate-100 space-y-5">
                <div>
                    <h2 class="text-base font-black text-slate-800 uppercase italic mb-3.5 flex items-center gap-2">
                        <i class="fa fa-map-marker-alt text-[#a31d37]"></i> Thông tin giao hàng
                    </h2>
                    <div class="space-y-3.5">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 uppercase mb-1">Họ và tên</label>
                            <input type="text" name="hoten" value="<?= htmlspecialchars($user_hoten) ?>" required 
                                   class="w-full p-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-[#a31d37] focus:bg-white outline-none transition text-xs font-medium">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 uppercase mb-1">Số điện thoại</label>
                            <input type="tel" name="sdt" value="<?= htmlspecialchars($user_sdt) ?>" required 
                                   class="w-full p-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-[#a31d37] focus:bg-white outline-none transition text-xs font-medium">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 uppercase mb-1">Địa chỉ nhận hàng</label>
                            <textarea name="diachi" required rows="3" class="w-full p-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-[#a31d37] focus:bg-white outline-none transition text-xs font-medium"><?= htmlspecialchars($user_diachi) ?></textarea>
                        </div>
                    </div>
                </div>

                <hr class="border-slate-100">

                <div>
                    <h2 class="text-base font-black text-slate-800 uppercase italic mb-3.5 flex items-center gap-2">
                        <i class="fa fa-credit-card text-[#a31d37]"></i> Phương thức thanh toán
                    </h2>
                    <div class="space-y-2.5">
                        <label class="flex items-center p-3.5 bg-slate-50 border border-slate-200 rounded-2xl cursor-pointer hover:border-[#a31d37] transition has-[:checked]:border-[#a31d37] has-[:checked]:bg-rose-50/20">
                            <input type="radio" name="phuongthuc" value="cod" checked class="w-4 h-4 accent-[#a31d37]">
                            <div class="ml-3 w-9 h-9 flex-shrink-0 bg-white rounded-xl flex items-center justify-center shadow-xs border border-slate-100">
                                <i class="fa fa-truck text-[#a31d37] text-sm"></i>
                            </div>
                            <div class="ml-3">
                                <p class="font-bold text-slate-800 text-xs leading-none">Thanh toán khi nhận hàng (COD)</p>
                                <p class="text-[11px] text-slate-500 mt-1">Thanh toán bằng tiền mặt khi giao hàng</p>
                            </div>
                        </label>

                        <label class="flex items-center p-3.5 bg-slate-50 border border-slate-200 rounded-2xl cursor-pointer hover:border-[#a31d37] transition has-[:checked]:border-[#a31d37] has-[:checked]:bg-rose-50/20">
                            <input type="radio" name="phuongthuc" value="momo" class="w-4 h-4 accent-[#a31d37]">
                            <div class="ml-3 w-9 h-9 flex-shrink-0 bg-[#ae2070] rounded-xl flex items-center justify-center shadow-xs">
                                <img src="https://developers.momo.vn/v2/assets/images/logo.png" alt="MoMo Logo" class="w-5 h-5 object-contain">
                            </div>
                            <div class="ml-3">
                                <p class="font-bold text-slate-800 text-xs leading-none">Ví điện tử MoMo</p>
                                <p class="text-[11px] text-slate-500 mt-1">Thanh toán trực tuyến qua ứng dụng MoMo</p>
                            </div>
                        </label>
                    </div>
                </div>
            </section>
        </div>

        <!-- CỘT PHẢI: ĐƠN HÀNG & VOUCHER (8 COLUMNS) -->
        <div class="lg:col-span-8">
            <div class="bg-white p-7 rounded-3xl shadow-xl border border-slate-100 sticky top-24 space-y-6">
                <h2 class="text-lg font-black text-slate-800 uppercase">📦 Đơn hàng của bạn</h2>
                
                <!-- Danh sách sản phẩm có kèm nút tăng giảm số lượng -->
                <div class="max-h-80 overflow-y-auto pr-1 space-y-3 divide-y divide-slate-100">
                    <?php foreach ($giohang_thanhtoan as $id => $sp): ?>
                    <div class="pt-3 first:pt-0 flex justify-between items-center gap-3 item-row" data-id="<?= $id ?>" data-price="<?= $sp['gia'] ?>">
                        <div class="flex items-center gap-3">
                            <div class="relative flex-shrink-0">
                                <img src="/Shopbanhang/images/<?= htmlspecialchars($sp['hinh']) ?>" 
                                     class="w-16 h-16 rounded-2xl object-contain border border-slate-100 bg-slate-50 p-1" 
                                     onerror="this.src='/Shopbanhang/images/no-image.png'">
                            </div>
                            <div>
                                <p class="font-bold text-xs text-slate-800 line-clamp-2 leading-tight"><?= $sp['ten'] ?></p>
                                <p class="text-[11px] text-slate-400 mt-0.5 whitespace-nowrap"><?= formatVND($sp['gia']) ?></p>
                                
                                <!-- Cụm tăng giảm số lượng sản phẩm -->
                                <div class="flex items-center gap-1.5 mt-2">
                                    <button type="button" onclick="changeQty('<?= $id ?>', -1)" class="w-6 h-6 rounded-lg bg-slate-100 hover:bg-[#a31d37] hover:text-white text-slate-600 flex items-center justify-center text-xs font-bold transition shadow-2xs">-</button>
                                    <span id="qty-text-<?= $id ?>" class="text-xs font-bold px-2 text-slate-800"><?= $sp['soluong'] ?></span>
                                    <button type="button" onclick="changeQty('<?= $id ?>', 1)" class="w-6 h-6 rounded-lg bg-slate-100 hover:bg-[#a31d37] hover:text-white text-slate-600 flex items-center justify-center text-xs font-bold transition shadow-2xs">+</button>
                                </div>
                            </div>
                        </div>
                        <p id="subtotal-<?= $id ?>" class="font-bold text-xs text-slate-900 whitespace-nowrap"><?= formatVND($sp['gia'] * $sp['soluong']) ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- PHẦN HIỂN THỊ VOUCHER ĐÃ ÁP DỤNG & NÚT THAY ĐỔI -->
                <div class="bg-rose-50/50 border border-rose-100 p-4 rounded-2xl">
                    <div class="flex justify-between items-center">
                        <div class="flex items-center gap-2.5">
                            <i class="fa fa-ticket-alt text-[#a31d37] text-base"></i>
                            <div>
                                <p class="text-[10px] uppercase font-bold text-slate-400">Mã giảm giá</p>
                                <p id="voucher-display-text" class="text-xs font-black text-slate-800">
                                    <?= $applied_voucher ? htmlspecialchars($applied_voucher['ma_voucher']) : 'Chưa áp dụng mã nào' ?>
                                </p>
                            </div>
                        </div>
                        <button type="button" onclick="openVoucherModal()" class="text-xs font-bold text-[#a31d37] bg-white border border-rose-200 px-3.5 py-1.5 rounded-xl shadow-xs hover:bg-[#a31d37] hover:text-white transition">
                            <?= $applied_voucher ? 'Thay đổi' : 'Chọn mã' ?>
                        </button>
                    </div>
                </div>

                <div class="border-t border-dashed border-slate-200 pt-4 space-y-3">
                    <div class="flex justify-between text-slate-500 text-xs font-medium">
                        <span>Tạm tính</span>
                        <span id="tamtinh-display" class="whitespace-nowrap"><?= formatVND($tongtien) ?></span>
                    </div>
                    <div class="flex justify-between text-slate-500 text-xs font-medium">
                        <span>Phí vận chuyển</span>
                        <span class="text-emerald-600 font-bold uppercase">Miễn phí</span>
                    </div>
                    <div class="flex justify-between text-rose-600 text-xs font-medium">
                        <span>Giảm giá từ voucher</span>
                        <span id="giamgia" class="font-bold whitespace-nowrap">-<?= formatVND($discount_amount) ?></span>
                    </div>

                    <div class="flex justify-between items-center pt-3 border-t border-slate-200 mt-2">
                        <span class="font-black text-slate-800 text-sm">TỔNG THANH TOÁN</span>
                        <span id="tongcong" class="text-xl font-black text-[#a31d37] whitespace-nowrap">
                            <?= formatVND($tong_thanhtoan) ?>
                        </span>
                    </div>
                </div>

                <button type="submit" id="btn-submit" class="w-full bg-[#a31d37] text-white py-4 rounded-2xl font-black uppercase tracking-wider text-xs hover:bg-black transition-all shadow-lg active:scale-[0.98]">
                    Xác nhận đặt hàng
                </button>
                
                <p class="text-center text-[9px] text-slate-400 uppercase tracking-[0.2em] pt-1">
                    Triple Beauty — Đánh thức vẻ đẹp tiềm ẩn
                </p>
            </div>
        </div>
    </form>
</main>

<!-- MODAL CHỌN/ĐỔI VOUCHER -->
<div id="voucherModal" class="fixed inset-0 bg-black/60 backdrop-blur-xs z-50 hidden flex items-center justify-center p-4 opacity-0 transition-opacity duration-300">
    <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl transform scale-95 transition-transform duration-300 space-y-4">
        <div class="flex justify-between items-center border-b border-slate-100 pb-3">
            <h3 class="font-black text-slate-800 text-base uppercase italic">🎟️ Chọn mã giảm giá</h3>
            <button type="button" onclick="closeVoucherModal()" class="text-slate-400 hover:text-slate-600 font-bold text-lg">&times;</button>
        </div>

        <div class="max-h-80 overflow-y-auto space-y-3 pr-1">
            <div onclick="selectVoucher('', 'Không dùng mã', 0, 'tien', 0)" class="border border-slate-200 rounded-2xl p-3.5 flex justify-between items-center cursor-pointer hover:border-[#a31d37] transition bg-slate-50">
                <div>
                    <span class="text-xs font-bold text-slate-700">Không sử dụng mã giảm giá</span>
                </div>
                <span class="text-xs font-bold text-slate-400"><i class="fa fa-chevron-right"></i></span>
            </div>

            <?php if (empty($list_vouchers)): ?>
                <p class="text-center text-xs text-slate-400 py-4">Hiện không có mã giảm giá nào khả dụng.</p>
            <?php else: ?>
                <?php foreach ($list_vouchers as $v): 
                    $v_code = htmlspecialchars($v['ma_voucher']);
                    $v_loai = $v['loai_giam'];
                    $v_giatri = (float)$v['giam_toi_da'];
                    $v_min = (float)$v['giatri_dh_toithieu'] * 1000;
                    $v_desc = ($v_loai == 'phamtram') ? "Giảm {$v_giatri}%" : "Giảm " . number_format($v_giatri * 1000, 0, ',', '.') . " VND";
                ?>
                <div onclick="selectVoucher('<?= $v_code ?>', '<?= $v_code ?> (<?= $v_desc ?>)', <?= $v_giatri ?>, '<?= $v_loai ?>', <?= $v_min ?>)" 
                     class="border border-slate-200 rounded-2xl p-4 flex justify-between items-center cursor-pointer hover:border-[#a31d37] hover:bg-rose-50/10 transition shadow-2xs">
                    <div class="space-y-1">
                        <span class="bg-rose-50 text-[#a31d37] text-[10px] font-black uppercase px-2 py-0.5 rounded"><?= $v_code ?></span>
                        <h4 class="text-xs font-extrabold text-slate-800"><?= $v_desc ?></h4>
                        <p class="text-[10px] text-slate-400">Đơn tối thiểu: <?= number_format($v_min, 0, ',', '.') ?> VND</p>
                    </div>
                    <span class="bg-[#a31d37] text-white text-xs font-bold px-3 py-1.5 rounded-xl shadow-xs">Dùng</span>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
let currentTongTien = <?= $tongtien ?>;
let currentVoucherMin = <?= $v_min_val ?>;
let currentVoucherVal = <?= $v_val ?>;
let currentVoucherLoai = '<?= $v_loai ?>';

function formatVNDJS(amount) {
    return amount.toLocaleString('vi-VN') + ' VND';
}

function changeQty(id, delta) {
    const qtySpan = document.getElementById('qty-text-' + id);
    let currentQty = parseInt(qtySpan.innerText) + delta;
    if (currentQty < 1) currentQty = 1;
    
    qtySpan.innerText = currentQty;

    // Tính lại subtotal của sản phẩm
    const row = document.querySelector(`.item-row[data-id='${id}']`);
    const price = parseFloat(row.getAttribute('data-price'));
    const subtotal = price * currentQty;
    document.getElementById('subtotal-' + id).innerHTML = formatVNDJS(subtotal);

    // Tính lại tổng tiền giỏ hàng
    recalculateTotals();

    // Đồng bộ ngầm session bằng AJAX
    fetch(`thanhtoan.php?action=update_qty&id=${id}&qty=${currentQty}`)
        .catch(err => console.error(err));
}

function recalculateTotals() {
    let newTongTien = 0;
    document.querySelectorAll('.item-row').forEach(row => {
        const id = row.getAttribute('data-id');
        const price = parseFloat(row.getAttribute('data-price'));
        const qty = parseInt(document.getElementById('qty-text-' + id).innerText);
        newTongTien += price * qty;
    });

    currentTongTien = newTongTien;
    document.getElementById('tamtinh-display').innerHTML = formatVNDJS(newTongTien);

    let discount = 0;
    const activeCode = document.getElementById("input_voucher_code").value;

    if (activeCode !== "") {
        if (newTongTien < currentVoucherMin) {
            // Đơn hàng tụt dưới mức tối thiểu -> Tự động hủy mã
            selectVoucher('', 'Không dùng mã', 0, 'tien', 0);
            alert("Đơn hàng đã giảm xuống dưới mức tối thiểu của mã giảm giá, hệ thống đã gỡ mã.");
        } else {
            if (currentVoucherLoai === 'phamtram') {
                discount = (newTongTien * currentVoucherVal) / 100;
            } else {
                discount = currentVoucherVal * 1000;
            }
        }
    }

    if (discount > newTongTien) discount = newTongTien;

    document.getElementById('input_discount').value = discount;
    document.getElementById('giamgia').innerHTML = '-' + formatVNDJS(discount);

    let finalTotal = newTongTien - discount;
    document.getElementById('tongcong').innerHTML = formatVNDJS(finalTotal);
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

function selectVoucher(code, displayText, value, loaiGiam, minOrder) {
    let discount = 0;

    if (code !== "") {
        if (currentTongTien < minOrder) {
            alert("Đơn hàng chưa đạt giá trị tối thiểu (" + minOrder.toLocaleString("vi-VN") + " VND) để sử dụng mã này!");
            return;
        }

        if (loaiGiam === 'phamtram') {
            discount = (currentTongTien * value) / 100;
        } else {
            discount = value * 1000;
        }
    }

    if (discount > currentTongTien) {
        discount = currentTongTien;
    }

    // Cập nhật state voucher
    currentVoucherMin = minOrder;
    currentVoucherVal = value;
    currentVoucherLoai = loaiGiam;

    let finalTotal = currentTongTien - discount;

    document.getElementById("input_voucher_code").value = code;
    document.getElementById("input_discount").value = discount;

    document.getElementById("voucher-display-text").innerText = (code !== "") ? displayText : "Chưa áp dụng mã nào";
    document.getElementById("giamgia").innerHTML = "-" + formatVNDJS(discount);
    document.getElementById("tongcong").innerHTML = formatVNDJS(finalTotal);

    let btnChon = document.querySelector("button[onclick='openVoucherModal()']");
    btnChon.innerText = (code !== "") ? "Thay đổi" : "Chọn mã";

    closeVoucherModal();
}

document.querySelector("form").addEventListener("submit", function(){
    let btn = document.querySelector("button[type='submit']");
    btn.disabled = true;
    btn.innerText = "Đang xử lý...";
});
</script>
</body>
</html>