<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/webbanmypham/func.php';

// 1. Kiểm tra đăng nhập và giỏ hàng
if (!isset($_SESSION['userid']) || empty($_SESSION['giohang'])) {
    header("Location: giohang.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 2. Lấy thông tin từ Form thanh toán
    $makh = $_SESSION['userid'];
    $hoten = mysqli_real_escape_string($conn, $_POST['hoten']);
    $sdt = mysqli_real_escape_string($conn, $_POST['sdt']);
    $diachi = mysqli_real_escape_string($conn, $_POST['diachi']);
    $phuongthuc = mysqli_real_escape_string($conn, $_POST['phuongthuc']);
    $ngaydat = date("Y-m-d H:i:s");
    $trangthai = 1; // 1: Chờ xác nhận (khớp với file Admin của bạn)

    // Tính tổng tiền từ session
    $tongtien = 0;
    foreach ($_SESSION['giohang'] as $sp) {
        $tongtien += $sp['gia'] * $sp['soluong'];
    }

    // 3. Lưu vào bảng đơn hàng (donhang)
    // Lưu ý: Đảm bảo tên cột trong DB của bạn khớp (makh, hoten, sdt, diachi, tongtien, ngaydat, trangthai)
    $sql_dh = "INSERT INTO donhang (makh, hoten, sdt, diachi, tongtien, ngaydat, trangthai) 
               VALUES ('$makh', '$hoten', '$sdt', '$diachi', '$tongtien', '$ngaydat', '$trangthai')";

    if (mysqli_query($conn, $sql_dh)) {
        // Lấy mã đơn hàng (ID) vừa tự động tạo ra
        $madh = mysqli_insert_id($conn);

        // 4. Lưu vào bảng chi tiết đơn hàng (chitietdonhang)
        foreach ($_SESSION['giohang'] as $id_sp => $sp) {
            $masp = $id_sp;
            $soluong = $sp['soluong'];
            $gia = $sp['gia'];

            $sql_ct = "INSERT INTO chitiet_donhang (madh, masp, soluong, gia) 
                       VALUES ('$madh', '$masp', '$soluong', '$gia')";
            mysqli_query($conn, $sql_ct);
        }

        // 5. Xóa giỏ hàng sau khi đặt thành công
        unset($_SESSION['giohang']);

        // 6. Chuyển hướng thông báo thành công
        echo "<script>
                alert('Đặt hàng thành công! Cảm ơn bạn đã tin dùng Triple Beauty.');
                window.location.href = 'index.php'; 
              </script>";
    } else {
        echo "Lỗi hệ thống: " . mysqli_error($conn);
    }
} else {
    header("Location: index.php");
}
?>