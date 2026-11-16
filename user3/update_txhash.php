//nhận mã TxHash để lưu mã giao dịch blockchain vào cơ sở dữ liệu 
<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/Shopbanhang/conn.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['madh']) && isset($_POST['tx_hash'])) {
    $madh = $_POST['madh'];
    $tx_hash = $_POST['tx_hash'];

    $sql = "UPDATE diem_thuong SET tx_hash = ? WHERE madh = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ss", $tx_hash, $madh);
    mysqli_stmt_execute($stmt);
    
    echo "success";
}
?>