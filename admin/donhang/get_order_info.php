<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/Shopbanhang/conn.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/Shopbanhang/func.php';

$id = $_GET['id'] ?? 0;


$sql_info = "SELECT dh.*, kh.hoten, kh.sdt, kh.diachi 
             FROM donhang dh 
             LEFT JOIN khachhang kh ON dh.makh = kh.makh 
             WHERE dh.madh = '$id'";
$res_info = mysqli_query($conn, $sql_info);
$order = mysqli_fetch_assoc($res_info);

if (!$order) {
    echo "<p style='color:red; text-align:center; padding:20px;'>Không tìm thấy đơn hàng #$id này!</p>";
    exit;
}

// 2. Lấy danh sách sản phẩm
$sql_items = "SELECT ct.*, sp.tensp 
              FROM chitiet_donhang ct 
              LEFT JOIN sanpham sp ON ct.masp = sp.masp 
              WHERE ct.madh = '$id'";
$items = mysqli_query($conn, $sql_items);
?>

<h2 style="border-bottom: 2px solid #a31d37; padding-bottom: 10px; color: #a31d37; font-weight: bold; text-transform: uppercase;">
    CHI TIẾT ĐƠN HÀNG #<?= $id ?>
</h2>

<div style="display: flex; justify-content: space-between; margin-bottom: 20px; background: #fff; padding: 15px; border: 1px solid #ddd; border-radius: 5px;">
    <div>
        <p><strong>Khách hàng:</strong> <?= $order['hoten'] ?? 'Không rõ' ?></p>
        <p><strong>Số điện thoại:</strong> <?= $order['sdt'] ?? 'Trống' ?></p>
        <p><strong>Địa chỉ:</strong> <?= $order['diachi'] ?? 'Trống' ?></p>
    </div>
    <div style="text-align: right;">
        <p><strong>Ngày đặt:</strong> <?= date('d/m/Y H:i', strtotime($order['ngaydat'])) ?></p>
       <p><strong>Trạng thái:</strong> 
    <?php
    $stt = $order['trangthai']; // Bỏ (int)
    if ($stt === 'Chờ xác nhận') {
        echo '<span style="color: #f39c12; font-weight: bold;">Chờ xác nhận</span>';
    } elseif ($stt === 'Đang giao') {
        echo '<span style="color: #3498db; font-weight: bold;">Đang giao</span>';
    } elseif ($stt === 'Đã giao') {
        echo '<span style="color: #27ae60; font-weight: bold;">Đã giao</span>';
    } elseif ($stt === 'Đã hủy') {
        echo '<span style="color: #e74c3c; font-weight: bold;">Đã hủy</span>';
    } else {
        echo '<span style="color: #999; font-weight: bold;">'.$stt.'</span>';
    }
    ?>
</p>
    </div>
</div>

<table width="100%" style="border-collapse: collapse; text-align: left; border: 1px solid #ddd;">
    <thead style="background: #f4f4f4;">
        <tr>
            <th style="padding: 10px; border: 1px solid #ddd;">Mã SP</th>
            <th style="padding: 10px; border: 1px solid #ddd;">Tên sản phẩm</th>
            <th style="padding: 10px; border: 1px solid #ddd; text-align: center;">Số lượng</th>
            <th style="padding: 10px; border: 1px solid #ddd; text-align: right;">Đơn giá</th>
            <th style="padding: 10px; border: 1px solid #ddd; text-align: right;">Thành tiền</th>
        </tr>
    </thead>
    <tbody>
        <?php 
        $tong_don_hang = 0;
        while ($item = mysqli_fetch_assoc($items)): 
            $thanh_tien = (float)$item['soluong'] * (float)$item['dongia'];
$tong_don_hang += $thanh_tien;
        ?>
        <tr>
            <td style="padding: 10px; border: 1px solid #ddd;"><?= $item['masp'] ?></td>
            <td style="padding: 10px; border: 1px solid #ddd;"><?= $item['tensp'] ?></td>
            <td style="padding: 10px; border: 1px solid #ddd; text-align: center;"><?= $item['soluong'] ?></td>
            <td style="padding: 10px; border: 1px solid #ddd; text-align: right;"><?= number_format($item['dongia']) ?>đ</td>
            <td style="padding: 10px; border: 1px solid #ddd; text-align: right; font-weight: bold;"><?= number_format($thanh_tien) ?>đ</td>
        </tr>
        <?php endwhile; ?>
    </tbody>
    <tfoot style="background: #fff;">
        <tr>
            <td colspan="4" style="text-align: right; padding: 15px; border: 1px solid #ddd;"><strong>Tổng cộng:</strong></td>
            <td style="padding: 15px; color: red; font-weight: bold; text-align: right; border: 1px solid #ddd; font-size: 1.1em;">
                <?= number_format($order['tongtien']) ?>đ
            </td>
        </tr>
    </tfoot>
</table>