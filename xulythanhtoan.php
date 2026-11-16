<?php
session_start();

require_once $_SERVER['DOCUMENT_ROOT'] . '/Shopbanhang/conn.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/Shopbanhang/func.php';

/* 1. Kiểm tra request */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: giohang.php");
    exit;
}

/* 2. Kiểm tra đăng nhập */
if (!isset($_SESSION['userid'])) {
    header("Location: login.php");
    exit;
}

if (empty($_POST['selected_ids'])) {
    header("Location: giohang.php");
    exit;
}

$userid = $_SESSION['userid'];

/* 3. Lấy mã khách hàng */
$sql = "SELECT makh, wallet_address FROM khachhang WHERE id_users = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $userid);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (!$result || mysqli_num_rows($result) == 0) {
    die("Không tìm thấy khách hàng.");
}

$row = mysqli_fetch_assoc($result);
$makh = $row['makh'];
$wallet_address = $row['wallet_address'];

/* 4. Lấy sản phẩm được chọn */
$selected_ids = explode(',', $_POST['selected_ids']);

$tongtien = 0;
$items = [];

foreach ($selected_ids as $id) {
    if (isset($_SESSION['giohang'][$id])) {
        $sp = $_SESSION['giohang'][$id];
        $thanhtien = $sp['gia'] * $sp['soluong'];
        $tongtien += $thanhtien;
        $items[$id] = $sp;
    }
}

if (empty($items)) {
    header("Location: giohang.php");
    exit;
}

// =================== ÁP DỤNG VOUCHER ===================
$voucher = trim($_POST['voucher'] ?? '');
$giamgia = 0;

if ($voucher === 'NEW10') {
    $sql_dem = "SELECT COUNT(*) AS sodon FROM donhang WHERE makh = ?";
    $stmt_dem = mysqli_prepare($conn, $sql_dem);
    mysqli_stmt_bind_param($stmt_dem, "i", $makh);
    mysqli_stmt_execute($stmt_dem);
    $rs_dem = mysqli_stmt_get_result($stmt_dem);
    $row_dem = mysqli_fetch_assoc($rs_dem);
    $so_don = (int)($row_dem['sodon'] ?? 0);

    if ($so_don == 0 && $tongtien >= 99000) {
        $giamgia = 10000;
    }
} elseif ($voucher === 'SHOP20') {
    if ($tongtien >= 299000) {
        $giamgia = 20000;
    }
} elseif ($voucher === 'SHOP50') {
    if ($tongtien >= 699000) {
        $giamgia = 50000;
    }
} elseif ($voucher === 'MASK15') {
    $coMatNa = false;
    foreach ($items as $sp) {
        $ten = mb_strtolower($sp['ten'], 'UTF-8');
        if (strpos($ten, 'mặt nạ') !== false || strpos($ten, 'mat na') !== false) {
            $coMatNa = true;
            break;
        }
    }
    if ($coMatNa && $tongtien >= 200000) {
        $giamgia = 15000;
    }
} elseif ($voucher === 'VIP10') {
    $sql_tongmua = "SELECT COALESCE(SUM(tongtien), 0) AS tong_mua FROM donhang WHERE makh = ? AND trangthai = 'Đã giao thành công'";
    $stmt_tongmua = mysqli_prepare($conn, $sql_tongmua);
    mysqli_stmt_bind_param($stmt_tongmua, "i", $makh);
    mysqli_stmt_execute($stmt_tongmua);
    $rs_tongmua = mysqli_stmt_get_result($stmt_tongmua);
    $row_tongmua = mysqli_fetch_assoc($rs_tongmua);
    $tong_mua = (float)($row_tongmua['tong_mua'] ?? 0);

    if ($tong_mua >= 5000000) {
        $giamgia = $tongtien * 0.10;
        if ($giamgia > 100000) {
            $giamgia = 100000;
        }
    }
}

if ($giamgia > $tongtien) {
    $giamgia = $tongtien;
}

$tongtien -= $giamgia;
if ($tongtien < 0) {
    $tongtien = 0;
}

/* 5. Tạo mã đơn hàng */
$madh = "DH" . strtoupper(uniqid());

/* 6. Lưu đơn hàng */
$sql_donhang = "INSERT INTO donhang (madh, makh, ngaydat, tongtien, trangthai) VALUES (?, ?, NOW(), ?, 'Chờ xác nhận')";
$stmt_dh = mysqli_prepare($conn, $sql_donhang);
// Chỉ có 3 dấu ? cần bind dữ liệu: $madh (string), $makh (int), $tongtien (double/decimal)
// Do đó chuỗi định nghĩa đúng phải là "sid" (bỏ bớt 1 ký tự i ở cuối)
mysqli_stmt_bind_param($stmt_dh, "sid", $madh, $makh, $tongtien);

if (!mysqli_stmt_execute($stmt_dh)) {
    die("Lỗi lưu đơn hàng: " . mysqli_error($conn));
}

/* 7. Lưu chi tiết đơn hàng (Dùng biến riêng biệt hoàn toàn để tránh xung đột) */
foreach ($items as $id => $sp) {
    $masp = $id;
    $tensp = $sp['ten'];
    $soluong = $sp['soluong'];
    $dongia = $sp['gia'];

    /* KIỂM TRA TỒN KHO */
    $sql_check = "SELECT soluong FROM sanpham WHERE masp = ?";
    $stmt_check = mysqli_prepare($conn, $sql_check);
    mysqli_stmt_bind_param($stmt_check, "s", $masp);
    mysqli_stmt_execute($stmt_check);
    $res = mysqli_stmt_get_result($stmt_check);
    $row = mysqli_fetch_assoc($res);

    if ($row['soluong'] < $soluong) {
        die("Sản phẩm $tensp không đủ hàng.");
    }

    $sql_ct = "INSERT INTO chitiet_donhang (madh, masp, soluong, dongia) VALUES (?, ?, ?, ?)";
    $stmt_ct = mysqli_prepare($conn, $sql_ct);
    mysqli_stmt_bind_param($stmt_ct, "ssii", $madh, $masp, $soluong, $dongia);
    mysqli_stmt_execute($stmt_ct);

    /* TRỪ TỒN KHO */
    $sql_update = "UPDATE sanpham SET soluong = soluong - ? WHERE masp = ?";
    $stmt_update = mysqli_prepare($conn, $sql_update);
    mysqli_stmt_bind_param($stmt_update, "is", $soluong, $masp);
    mysqli_stmt_execute($stmt_update);

    /* XÓA KHỎI GIỎ HÀNG */
    unset($_SESSION['giohang'][$id]);
}

// ======================================================
// 8. HÀM CẬP NHẬT ĐIỂM, PHÂN HẠNG & GHI NHẬN LỊCH SỬ ĐIỂM THƯỞNG
// ======================================================
function capNhatHangVaDiemThuong($conn, $makh, $madh, $so_tien_don_hang) {
    $diem_congthem = floor($so_tien_don_hang / 10000);

    if ($diem_congthem > 0) {
        $sql_update = "UPDATE khachhang 
                       SET tong_chi_tieu = COALESCE(tong_chi_tieu, 0) + ?, 
                           diem_tich_luy = COALESCE(diem_tich_luy, 0) + ? 
                       WHERE makh = ?";
        $stmt_kh = mysqli_prepare($conn, $sql_update);
        mysqli_stmt_bind_param($stmt_kh, "dii", $so_tien_don_hang, $diem_congthem, $makh);
        mysqli_stmt_execute($stmt_kh);

        $loai_giao_dich = "Nhận điểm từ đơn hàng: " . $madh;
        $sql_log = "INSERT INTO diem_thuong (makh, madh, so_diem, loai_giao_dich, tx_hash) VALUES (?, ?, ?, ?, ?)";
        $stmt_log = mysqli_prepare($conn, $sql_log);
        $tx_hash_empty = ""; 
        mysqli_stmt_bind_param($stmt_log, "isiss", $makh, $madh, $diem_congthem, $loai_giao_dich, $tx_hash_empty);
        mysqli_stmt_execute($stmt_log);
    }

    $sql_get = "SELECT COALESCE(SUM(tongtien), 0) AS tong_mua FROM donhang WHERE makh = ? AND trangthai = 'Đã giao thành công'";
    $stmt_get = mysqli_prepare($conn, $sql_get);
    mysqli_stmt_bind_param($stmt_get, "i", $makh);
    mysqli_stmt_execute($stmt_get);
    $result = mysqli_stmt_get_result($stmt_get);
    $khach = mysqli_fetch_assoc($result);
    $tong_chi_tieu_thuc_te = (float)($khach['tong_mua'] ?? 0);

    $sql_rank = "SELECT maloai FROM loai_khachhang 
                 WHERE dieukien_chitieu <= ? 
                 ORDER BY dieukien_chitieu DESC LIMIT 1";
    $stmt_rank = mysqli_prepare($conn, $sql_rank);
    mysqli_stmt_bind_param($stmt_rank, "d", $tong_chi_tieu_thuc_te);
    mysqli_stmt_execute($stmt_rank);
    $res_rank = mysqli_stmt_get_result($stmt_rank);
    
    if ($row_rank = mysqli_fetch_assoc($res_rank)) {
        $maloai_moi = $row_rank['maloai'];
        $sql_up_rank = "UPDATE khachhang SET maloai = ? WHERE makh = ?";
        $stmt_up_rank = mysqli_prepare($conn, $sql_up_rank);
        mysqli_stmt_bind_param($stmt_up_rank, "ii", $maloai_moi, $makh);
        mysqli_stmt_execute($stmt_up_rank);
    }
}

capNhatHangVaDiemThuong($conn, $makh, $madh, $tongtien);
/* 9. Chuyển sang trang xác nhận Blockchain */
$_SESSION['success_order'] = "Đặt hàng thành công! Mã đơn: $madh";
header("Location: thanhcong.php?madh=$madh");
exit;
?>