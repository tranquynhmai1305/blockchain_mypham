<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/Shopbanhang/conn.php';

if(isset($_GET['makh'])) {
    $makh = mysqli_real_escape_string($conn, $_GET['makh']);
    $sql = "SELECT * FROM donhang WHERE makh = '$makh' ORDER BY ngaydat DESC";
    $result = mysqli_query($conn, $sql);
    
    if(mysqli_num_rows($result) > 0) {
        echo '<table class="w-full text-left">';
        echo '<thead class="bg-slate-50 text-[10px] uppercase font-black text-slate-400 tracking-widest">';
        echo '<tr><th class="p-4">Mã đơn</th><th class="p-4">Ngày đặt</th><th class="p-4 text-right">Tổng tiền</th><th class="p-4 text-center">Trạng thái</th></tr></thead>';
        echo '<tbody class="divide-y divide-slate-100">';
        while($row = mysqli_fetch_assoc($result)) {
            $color = ($row['trangthai'] == 'Đã hủy') ? 'text-rose-500' : 'text-emerald-500';
            echo '<tr class="text-sm border-b border-slate-50">';
            echo '<td class="p-4 font-bold">#'.$row['madh'].'</td>';
            echo '<td class="p-4 text-slate-500">'.date('d/m/Y', strtotime($row['ngaydat'])).'</td>';
            echo '<td class="p-4 text-right font-black text-slate-700">'.number_format($row['tongtien']).'đ</td>';
            echo '<td class="p-4 text-center"><span class="text-[10px] font-bold px-2 py-1 rounded-md bg-slate-100 '.$color.'">'.$row['trangthai'].'</span></td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
    } else {
        echo '<div class="p-10 text-center text-slate-400 italic">Khách hàng này chưa có đơn hàng nào.</div>';
    }
}
?>