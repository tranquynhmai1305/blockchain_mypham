<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/Shopbanhang/conn.php';

$id = $_GET['id'] ?? 0;
if (!$id) {
    echo "<p style='color:red; text-align:center;'>Không tìm thấy mã khách hàng.</p>";
    exit;
}

// 1. Lấy thông tin chuẩn xác trực tiếp từ bảng khachhang, kết hợp với bảng users thông qua id_users
$query = "SELECT k.*, u.username, u.email as user_email 
          FROM khachhang k 
          LEFT JOIN users u ON k.id_users = u.id 
          WHERE k.makh = '$id' LIMIT 1";
$result = @mysqli_query($conn, $query);
$customer = ($result && mysqli_num_rows($result) > 0) ? mysqli_fetch_assoc($result) : null;

if (!$customer) {
    echo "<p style='color:red; text-align:center;'>Không tìm thấy thông tin hồ sơ khách hàng.</p>";
    exit;
}

// 2. Lấy danh sách lịch sử đơn hàng từ bảng donhang
$orders = [];
$total_spent_from_orders = 0;

$order_queries = [
    "SELECT * FROM donhang WHERE makh = '$id' ORDER BY ngaydat DESC",
    isset($customer['id_users']) ? "SELECT * FROM donhang WHERE user_id = '{$customer['id_users']}' ORDER BY ngaydat DESC" : ""
];

foreach ($order_queries as $o_query) {
    if (empty($o_query)) continue;
    $order_result = @mysqli_query($conn, $o_query);
    if ($order_result && mysqli_num_rows($order_result) > 0) {
        while ($row = mysqli_fetch_assoc($order_result)) {
            $total_spent_from_orders += ($row['tongtien'] ?? 0);
            $orders[] = $row;
        }
        break;
    }
}

$total_orders = count($orders);
// Ưu tiên hiển thị tổng chi tiêu từ lịch sử đơn hàng thực tế, nếu chưa có đơn thì lấy từ cột tong_chi_tieu trong bảng khachhang
$display_chi_tieu = $total_orders > 0 ? $total_spent_from_orders : ($customer['tong_chi_tieu'] ?? 0);
?>

<div style="font-family: 'Plus Jakarta Sans', sans-serif; color: #1e293b;">
    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #f1f5f9; padding-bottom: 12px; margin-bottom: 20px;">
        <h3 style="font-size: 18px; margin: 0; color: #0f172a;"><i class="fa fa-user-circle" style="color: #6366f1; margin-right: 8px;"></i> Hồ sơ khách hàng: <?= htmlspecialchars($customer['hoten'] ?? 'Chưa cập nhật') ?></h3>
        <span style="font-size: 12px; background: #e0e7ff; color: #6366f1; padding: 4px 10px; border-radius: 20px; font-weight: 700;">
            KH<?= str_pad($customer['makh'], 3, '0', STR_PAD_LEFT) ?>
        </span>
    </div>

    <!-- Thông tin cá nhân cơ bản -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; background: #f8fafc; padding: 15px; border-radius: 10px; margin-bottom: 20px; font-size: 13px;">
        <div>
            <p style="margin-bottom: 8px;"><i class="fa fa-phone" style="width: 20px; color: #64748b;"></i> <strong>SĐT:</strong> <?= htmlspecialchars($customer['sdt'] ?? 'Chưa cập nhật') ?></p>
            <p style="margin-bottom: 0;"><i class="fa fa-envelope" style="width: 20px; color: #64748b;"></i> <strong>Email:</strong> <?= htmlspecialchars($customer['email'] ?? 'Chưa cập nhật') ?></p>
        </div>
        <div>
            <p style="margin-bottom: 8px;"><i class="fa fa-map-marker-alt" style="width: 20px; color: #f43f5e;"></i> <strong>Địa chỉ:</strong> <?= htmlspecialchars($customer['diachi'] ?? 'Chưa cập nhật') ?></p>
            <p style="margin-bottom: 0;"><i class="fa fa-shopping-bag" style="width: 20px; color: #10b981;"></i> <strong>Tổng đơn / Chi tiêu:</strong> <?= $total_orders ?> đơn (<?= number_format($display_chi_tieu, 0, ',', '.') ?>đ)</p>
        </div>
    </div>

    <!-- Lịch sử đơn hàng -->
    <h4 style="font-size: 15px; margin-bottom: 12px; color: #334155;"><i class="fa fa-history" style="margin-right: 6px;"></i> Lịch sử giao dịch gần đây</h4>
    
    <?php if ($total_orders > 0): ?>
        <div style="max-height: 250px; overflow-y: auto; border: 1px solid #e2e8f0; border-radius: 8px;">
            <table style="width: 100%; border-collapse: collapse; font-size: 13px; text-align: left;">
                <thead>
                    <tr style="background: #f1f5f9; position: sticky; top: 0;">
                        <th style="padding: 10px 12px; border-bottom: 1px solid #e2e8f0;">Mã ĐH</th>
                        <th style="padding: 10px 12px; border-bottom: 1px solid #e2e8f0;">Ngày đặt</th>
                        <th style="padding: 10px 12px; border-bottom: 1px solid #e2e8f0;">Tổng tiền</th>
                        <th style="padding: 10px 12px; border-bottom: 1px solid #e2e8f0;">Trạng thái</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($orders as $row): ?>
                    <tr style="border-bottom: 1px solid #f1f5f9;">
                        <td style="padding: 10px 12px; font-weight: bold; color: #6366f1;">DH<?= $row['madh'] ?? $row['id'] ?? '' ?></td>
                        <td style="padding: 10px 12px; color: #64748b;"><?= $row['ngaydat'] ?? '---' ?></td>
                        <td style="padding: 10px 12px; color: #f43f5e; font-weight: bold;"><?= number_format($row['tongtien'] ?? 0, 0, ',', '.') ?>đ</td>
                        <td style="padding: 10px 12px;">
                            <span style="font-size: 11px; padding: 3px 8px; border-radius: 4px; background: #e2e8f0; font-weight: 600;">
                                <?= $row['trangthai'] ?? '---' ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p style="color: #94a3b8; font-style: italic; text-align: center; padding: 20px; background: #f8fafc; border-radius: 8px;">Khách hàng này chưa thực hiện giao dịch nào.</p>
    <?php endif; ?>
</div>