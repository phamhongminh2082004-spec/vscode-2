<nav>
    <a href="index.php">Menu</a>
    <a href="tracuu.php">Tra cứu đơn hàng</a>
    <?php if (!empty($_SESSION['admin'])): ?>
        <a href="create.php">+ Thêm sản phẩm</a>
        <a href="donhang.php">Quản lý đơn hàng</a>
        <a href="thongke.php">Thống kê</a>
        <a href="doimatkhau.php">Đổi mật khẩu</a>
        <a href="dangxuat.php">Đăng xuất (<?= h($_SESSION['admin']) ?>)</a>
    <?php else: ?>
        <a href="dangnhap.php">Chủ tiệm đăng nhập</a>
    <?php endif; ?>
</nav>
