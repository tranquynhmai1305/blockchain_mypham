<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/Shopbanhang/func.php';

$id = $_GET['id'];
$type = $_GET['type'];
$masp = $_GET['masp'];

if($type == "up"){
    $sql = "UPDATE hinhanh_sanpham 
            SET thutu = thutu - 1 
            WHERE id_hinhanh = ?";
}else{
    $sql = "UPDATE hinhanh_sanpham 
            SET thutu = thutu + 1 
            WHERE id_hinhanh = ?";
}

$stmt = $conn->prepare($sql);
$stmt->bind_param("i",$id);
$stmt->execute();

header("Location: edit.php?masp=".$masp);
exit;