<?php
function getMaKH($conn,$userid){
    $sql="SELECT makh FROM khachhang WHERE id_users='$userid'";
    $res=mysqli_query($conn,$sql);
    $row=mysqli_fetch_assoc($res);
    return $row['makh'];
}
?>
