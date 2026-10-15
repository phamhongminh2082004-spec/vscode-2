<?php
// XOÁ (chỉ nhận POST)
//   loai=gio      → khách xoá món khỏi giỏ hàng
//   loai=sanpham  → chủ tiệm xoá món (phải đăng nhập)
require 'ketnoi.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: index.php'); exit; }
kiemCsrf();
$id = (int)($_POST['id'] ?? 0);

if (($_POST['loai'] ?? '') === 'gio') {
    unset($_SESSION['gio'][$id]);
    header('Location: index.php?p=giohang'); exit;
}
if (($_POST['loai'] ?? '') === 'sanpham') {
    yeuCauAdmin();
    truyvan($conn, 'DELETE FROM sanpham WHERE id=?', 'i', [$id]);   // đơn cũ vẫn giữ tên món
    $_SESSION['tb'] = 'Đã xoá món.';
    header('Location: index.php?p=admin'); exit;
}
header('Location: index.php');
