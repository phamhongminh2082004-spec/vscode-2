<?php
// CẬP NHẬT (chỉ chủ tiệm):
//   update.php?id=5   → sửa món
//   update.php?don=3  → đổi trạng thái đơn hàng (nhận POST từ trang chi tiết đơn)
require 'ketnoi.php';
yeuCauAdmin();

// --- Đổi trạng thái đơn ---
if (isset($_GET['don'])) {
    $don = (int)$_GET['don'];
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        kiemCsrf();
        $tt = $_POST['trangthai'] ?? '';
        if (isset($TRANGTHAI[$tt])) {
            truyvan($conn, 'UPDATE donhang SET trangthai=? WHERE id=?', 'si', [$tt, $don]);
            $_SESSION['tb'] = "Đã cập nhật trạng thái đơn #$don.";
        }
    }
    header("Location: index.php?p=donhang&id=$don"); exit;
}

// --- Sửa món ---
$id = (int)($_GET['id'] ?? 0);
$sp = layMot($conn, 'SELECT * FROM sanpham WHERE id=?', 'i', [$id]);
if (!$sp) { $_SESSION['tb'] = 'Không tìm thấy món.'; header('Location: index.php?p=admin'); exit; }
$loi = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    kiemCsrf();
    $sp['ten'] = trim($_POST['ten'] ?? ''); $sp['loai'] = trim($_POST['loai'] ?? '');
    $sp['gia'] = $_POST['gia'] ?? ''; $sp['mota'] = trim($_POST['mota'] ?? '');
    $sp['dang_ban'] = isset($_POST['dang_ban']) ? 1 : 0;

    if ($sp['ten'] === '' || mb_strlen($sp['ten']) > 120)  $loi[] = 'Tên món không hợp lệ.';
    if ($sp['loai'] === '' || mb_strlen($sp['loai']) > 60) $loi[] = 'Loại không hợp lệ.';
    if (!ctype_digit((string)$sp['gia']) || (int)$sp['gia'] <= 0) $loi[] = 'Giá phải là số nguyên dương.';

    if (!$loi) {
        truyvan($conn, 'UPDATE sanpham SET ten=?, loai=?, gia=?, mota=?, dang_ban=? WHERE id=?', 'ssisii',
                [$sp['ten'], $sp['loai'], (int)$sp['gia'], $sp['mota'], $sp['dang_ban'], $id]);
        $_SESSION['tb'] = 'Đã cập nhật món.';
        header('Location: index.php?p=admin'); exit;
    }
}
dauTrang('Sửa món');
?>
<h1>Sửa món</h1>
<?php foreach ($loi as $l) echo '<div class="tb l">' . h($l) . '</div>'; ?>
<form method="post" class="form"><?= csrf() ?>
  <label>Tên món <input name="ten" value="<?= h($sp['ten']) ?>" required></label>
  <label>Loại <input name="loai" value="<?= h($sp['loai']) ?>" required></label>
  <label>Giá (VNĐ) <input type="number" name="gia" min="1000" step="500" value="<?= h($sp['gia']) ?>" required></label>
  <label>Mô tả <textarea name="mota" rows="3"><?= h($sp['mota']) ?></textarea></label>
  <label style="flex-direction:row;align-items:center;gap:8px"><input type="checkbox" name="dang_ban" <?= $sp['dang_ban'] ? 'checked' : '' ?>> Đang bán</label>
  <div><button class="btn">Lưu</button> <a class="btn g" href="index.php?p=admin">Quay lại</a></div>
</form>
<?php cuoiTrang();
