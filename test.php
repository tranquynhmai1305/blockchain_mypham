<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/Shopbanhang/conn.php';
$pass = "1234";
$hash = password_hash($pass, PASSWORD_DEFAULT);

echo "Hash tạo ra:<br>";
echo $hash;
echo "<br><br>";

var_dump(password_verify("1234", $hash));