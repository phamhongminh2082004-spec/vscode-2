<?php
// Chèn vào đầu mọi trang quản trị: kết nối + bắt buộc đăng nhập
include 'ketnoi.php';

if (empty($_SESSION['admin'])) {
    header("Location: dangnhap.php");
    exit();
}
?>
