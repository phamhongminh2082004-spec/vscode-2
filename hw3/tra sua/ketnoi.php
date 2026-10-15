<?php
// Kết nối CSDL + các hàm dùng chung. Sửa user/mật khẩu MySQL cho đúng máy bạn.
session_start();
$conn = new mysqli('localhost', 'root', '123456', 'trasua');
if ($conn->connect_error) die('Lỗi kết nối: ' . $conn->connect_error);
$conn->set_charset('utf8mb4');

$TRANGTHAI = ['moi' => 'Chờ xác nhận', 'dang_lam' => 'Đang pha chế', 'xong' => 'Hoàn thành', 'huy' => 'Đã huỷ'];

function h($s)    { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function tien($n) { return number_format((int)$n, 0, ',', '.') . 'đ'; }
function laAdmin() { return !empty($_SESSION['admin']); }
function yeuCauAdmin() { if (!laAdmin()) { header('Location: index.php?p=dangnhap'); exit; } }
function sdtHopLe($s) { return (bool)preg_match('/^0\d{9,10}$/', $s); }

// CSRF
function csrf() {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return '<input type="hidden" name="csrf" value="' . $_SESSION['csrf'] . '">';
}
function kiemCsrf() {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) die('Phiên không hợp lệ, hãy tải lại trang.');
}

// Truy vấn có tham số (chống SQL injection): truyvan($conn, "SELECT ... WHERE id=?", 'i', [5])
function truyvan($conn, $sql, $kieu = '', $tham = []) {
    $st = $conn->prepare($sql);
    if ($kieu) $st->bind_param($kieu, ...$tham);
    $st->execute();
    return $st;
}
function layHet($conn, $sql, $kieu = '', $tham = []) {
    return truyvan($conn, $sql, $kieu, $tham)->get_result()->fetch_all(MYSQLI_ASSOC);
}
function layMot($conn, $sql, $kieu = '', $tham = []) {
    $r = layHet($conn, $sql, $kieu, $tham);
    return $r[0] ?? null;
}

// Phần đầu / cuối trang dùng chung
function dauTrang($tieude = 'Tiệm Trà Sữa') {
    $sl = array_sum($_SESSION['gio'] ?? []);
    ?>
<!DOCTYPE html>
<html lang="vi"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= h($tieude) ?></title>
<style>
*{box-sizing:border-box}body{margin:0;font-family:Arial,sans-serif;background:#faf6f1;color:#3b2f2f}
.wrap{max-width:960px;margin:0 auto;padding:16px}
.top{background:#8b5e3c}.top .wrap{display:flex;justify-content:space-between;flex-wrap:wrap;gap:8px;padding:12px 16px}
.top a{color:#fff;text-decoration:none;margin-right:14px}.top b{color:#fff}
.btn{display:inline-block;background:#c8873f;color:#fff;border:0;padding:7px 14px;border-radius:6px;cursor:pointer;text-decoration:none;font-size:14px}
.btn.x{background:#c0392b}.btn.g{background:#777}.btn.s{padding:3px 9px;font-size:13px}
input,select,textarea{padding:7px;border:1px solid #cdbfb0;border-radius:6px;font:inherit}
table{width:100%;border-collapse:collapse;background:#fff}th,td{padding:9px;border-bottom:1px solid #eee;text-align:left}th{background:#f1e4d3}
.grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(210px,1fr));gap:14px}
.card{background:#fff;border-radius:10px;padding:14px;box-shadow:0 1px 4px rgba(0,0,0,.1)}
.card h3{margin:4px 0}.gia{color:#a8541c;font-weight:bold}.mo{color:#8a7a6a;font-size:14px}
.box{background:#fff;border-radius:10px;padding:14px;margin-bottom:12px}
.form{display:flex;flex-direction:column;gap:10px;max-width:460px;background:#fff;padding:18px;border-radius:10px}
.form label{display:flex;flex-direction:column;gap:3px;font-weight:bold}
.tb{padding:10px;border-radius:6px;margin-bottom:12px;background:#e3f4e1;color:#245c20}.tb.l{background:#fde4e1;color:#8a1f12}
.nhan{padding:2px 9px;border-radius:12px;color:#fff;font-size:12px;background:#999}
.moi{background:#e0a100}.dang_lam{background:#2e86c1}.xong{background:#27ae60}.huy{background:#c0392b}
.tim{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px}.cot{overflow-x:auto}
</style></head><body>
<div class="top"><div class="wrap">
  <b>🧋 Tiệm Trà Sữa</b>
  <div>
    <a href="index.php">Menu</a><a href="index.php?p=giohang">Giỏ hàng (<?= $sl ?>)</a><a href="index.php?p=tracuu">Tra cứu đơn</a>
    <?php if (laAdmin()): ?>
      <a href="index.php?p=admin">Quản lý món</a><a href="index.php?p=donhang">Đơn hàng</a><a href="index.php?p=thongke">Thống kê</a><a href="index.php?p=dangxuat">Đăng xuất</a>
    <?php else: ?><a href="index.php?p=dangnhap">Chủ tiệm</a><?php endif; ?>
  </div>
</div></div>
<div class="wrap">
<?php
}
function cuoiTrang() { echo '</div></body></html>'; }
