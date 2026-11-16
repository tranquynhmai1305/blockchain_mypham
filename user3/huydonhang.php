<?php
session_start();

$conn = mysqli_connect("localhost", "root", "", "qlybanhang");
mysqli_set_charset($conn, "utf8mb4");

if (!isset($_SESSION['userid']) || !isset($_GET['madh'])) {
    header("Location: donhangcuatoi.php");
    exit;
}

$userid = $_SESSION['userid'];
$madh = $_GET['madh'];

/* LẤY MAKH TỪ USERS */
$sql_kh = "SELECT makh FROM khachhang WHERE id_users = '$userid'";
$res_kh = mysqli_query($conn, $sql_kh);
$row_kh = mysqli_fetch_assoc($res_kh);

$makh = $row_kh['makh'];

/* KIỂM TRA ĐƠN HÀNG */
$sql_check = "SELECT trangthai FROM donhang WHERE madh='$madh' AND makh='$makh'";
$result = mysqli_query($conn, $sql_check);
$row = mysqli_fetch_assoc($result);

if (!$row) {
    header("Location: donhangcuatoi.php");
    exit;
}

/* CHỈ CHO HỦY KHI CHỜ XÁC NHẬN */
if ($row['trangthai'] != 'Chờ xác nhận') {
    echo "<script>alert('Đơn hàng đã được xử lý, không thể hủy!');window.location='donhangcuatoi.php';</script>";
    exit;
}

/* LẤY CHI TIẾT ĐƠN HÀNG */
$sql_ct = "SELECT masp, soluong FROM chitiet_donhang WHERE madh='$madh'";
$res_ct = mysqli_query($conn, $sql_ct);

/* HOÀN LẠI TỒN KHO */
while ($sp = mysqli_fetch_assoc($res_ct)) {

    $masp = $sp['masp'];
    $soluong = $sp['soluong'];

    $sql_update_kho = "UPDATE sanpham 
                       SET soluong = soluong + $soluong 
                       WHERE masp='$masp'";

    mysqli_query($conn, $sql_update_kho);
}

/* CẬP NHẬT TRẠNG THÁI ĐƠN */
$sql_update = "UPDATE donhang 
               SET trangthai='Đã hủy' 
               WHERE madh='$madh'";

mysqli_query($conn, $sql_update);

echo "<script>
alert('Hủy đơn hàng thành công!');
window.location='donhangcuatoi.php';
</script>";
?>