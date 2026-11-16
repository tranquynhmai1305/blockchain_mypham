<?php
$conn = mysqli_connect("localhost", "root", "", "qlybanhang");
mysqli_set_charset($conn, "utf8mb4");

$id = $_GET['id'];
$set = $_GET['set'];

$sql = "UPDATE users SET trangthai = '$set' WHERE id = '$id'";
mysqli_query($conn,$sql);

header("Location: trangthaitk.php");
exit;
?>