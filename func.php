<?php

// ===============================
// LẤY TẤT CẢ SẢN PHẨM
// ===============================
function getAllProducts($conn) {

    $sql = "
        SELECT 
            sp.*,
            th.tenthuonghieu
        FROM sanpham sp
        LEFT JOIN thuonghieu th 
            ON sp.id_thuonghieu = th.id_thuonghieu
        ORDER BY sp.masp DESC
    ";

    $result = $conn->query($sql);
    $products = [];

    if ($result) {
        while($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
    }

    return $products;
}


// ===============================
// LẤY 1 SẢN PHẨM THEO ID
// ===============================
function getProductById($conn, $id) {

    $id = intval($id);

    $sql = "
        SELECT 
            sp.*,
            th.tenthuonghieu
        FROM sanpham sp
        LEFT JOIN thuonghieu th 
            ON sp.id_thuonghieu = th.id_thuonghieu
        WHERE sp.masp = $id
        LIMIT 1
    ";

    $result = $conn->query($sql);

    if ($result) {
        return $result->fetch_assoc();
    }

    return null;
}


// ===============================
// LẤY SẢN PHẨM THEO THƯƠNG HIỆU
// ===============================
function getProductsByBrand($conn, $brandId) {

    $brandId = intval($brandId);

    $sql = "
        SELECT 
            sp.*,
            th.tenthuonghieu
        FROM sanpham sp
        LEFT JOIN thuonghieu th 
            ON sp.id_thuonghieu = th.id_thuonghieu
        WHERE sp.id_thuonghieu = $brandId
        ORDER BY sp.masp DESC
    ";

    $result = $conn->query($sql);
    $products = [];

    if ($result) {
        while($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
    }

    return $products;
}


// ===============================
// LẤY TẤT CẢ THƯƠNG HIỆU
// ===============================
function getAllBrands($conn) {

    $sql = "SELECT id_thuonghieu, tenthuonghieu, hinh_thuonghieu 
            FROM thuonghieu
            ORDER BY id_thuonghieu DESC";

    $result = $conn->query($sql);
    $brands = [];

    if ($result) {
        while($row = $result->fetch_assoc()) {
            $brands[] = $row;
        }
    }

    return $brands;
}


// ===============================
// LẤY NHIỀU ẢNH CỦA SẢN PHẨM
// ===============================
function getHinhAnhBySanPham($conn, $masp){

    $sql = "SELECT duongdan FROM hinhanh_sanpham WHERE masp='$masp'";
    $result = $conn->query($sql);

    $images = [];

    if($result){
        while($row = $result->fetch_assoc()){
            $images[] = $row['duongdan'];
        }
    }

    return $images;
}




?>