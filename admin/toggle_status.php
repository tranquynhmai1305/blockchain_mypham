<?php

$mysqli = mysqli_connect("localhost","root","","qlybanhang");

if(!$mysqli){
    die("Lỗi kết nối database");
}

if(isset($_GET['masp'])){

    $masp = $_GET['masp'];

    $sql = "SELECT trangthai FROM sanpham WHERE masp='$masp' LIMIT 1";
    $query = mysqli_query($mysqli,$sql);

    if($query && mysqli_num_rows($query) > 0){

        $row = mysqli_fetch_assoc($query);

        if($row['trangthai']==1){
            $new_status = 0;
        }else{
            $new_status = 1;
        }

        $update = "UPDATE sanpham SET trangthai='$new_status' WHERE masp='$masp'";
        mysqli_query($mysqli,$update);
    }

    header("Location: sanpham/danhsachsp/index.php");
    exit();
}
?>