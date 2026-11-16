<?php
require_once '../../func.php';

if (!isset($_POST['submit'])) {
    header('Location: add.php');
    exit;
}

$data = [
    'masp'         => $_POST['masp'],
    'tensp'        => $_POST['tensp'],
    'id_loaisp'    => $_POST['id_loaisp'],
    'gia'          => $_POST['gia'],
    'giakhuyenmai' => $_POST['giakhuyenmai'],
    'mota'         => $_POST['mota'],
    'soluong'      => $_POST['soluong'],
    'id_thuonghieu'=> $_POST['id_thuonghieu'],
    'xuatxu'       => $_POST['xuatxu'],
    'dungtichsp'   => $_POST['dungtichsp'],
    'han_sudung'   => $_POST['han_sudung']
];

$result = addSanPham($data);

if ($result === "duplicate") {
    header('Location: add.php?error=duplicate');
    exit;
}

if (!empty($_FILES['hinhanh']['name'])) {
    $filename = time().'_'.$_FILES['hinhanh']['name'];
    $path = "images/".$filename;

    move_uploaded_file(
        $_FILES['hinhanh']['tmp_name'],
        "../../../".$path
    );

    addHinhAnhSanPham($data['masp'], $path);
}

header('Location: index.php?success=1');
exit;
