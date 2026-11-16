<?php
$conn = mysqli_connect("localhost", "root", "", "qlybanhang");

if (!$conn) {
    die("Kết nối CSDL thất bại: " . mysqli_connect_error());
}
