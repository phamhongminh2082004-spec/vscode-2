<?php
// TRANG CHÍNH: khách (menu, giỏ, đặt hàng, tra cứu) + chủ tiệm (đăng nhập, quản lý món, đơn, thống kê)
require 'ketnoi.php';
$p = $_GET['p'] ?? 'menu';
$tb = $_SESSION['tb'] ?? ''; unset($_SESSION['tb']);
$loi = [];

// Lấy các món trong giỏ (giá luôn lấy từ DB)
function layGio($conn) {
    $gio = $_SESSION['gio'] ?? []; $ds = []; $tong = 0;
    if (!$gio) return [$ds, $tong];
    $ids = implode(',', array_map('intval', array_keys($gio)));
    foreach (layHet($conn, "SELECT * FROM sanpham WHERE dang_ban = 1 AND id IN ($ids)") as $sp) {
        $sp['sl'] = $gio[$sp['id']]; $sp['tt'] = $sp['sl'] * $sp['gia']; $tong += $sp['tt']; $ds[] = $sp;
    }
    return [$ds, $tong];
}

// ===== XỬ LÝ FORM (POST) =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    kiemCsrf();
    $hd = $_POST['hanhdong'] ?? '';

    if ($hd === 'themgio') {
        $id = (int)$_POST['id']; $sl = max(1, min(20, (int)$_POST['soluong']));
        if (layMot($conn, 'SELECT id FROM sanpham WHERE id=? AND dang_ban=1', 'i', [$id])) {
            $_SESSION['gio'][$id] = min(20, ($_SESSION['gio'][$id] ?? 0) + $sl);
            $_SESSION['tb'] = 'Đã thêm vào giỏ hàng.';
        }
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'index.php')); exit;
    }
    if ($hd === 'suagio') {
        $id = (int)$_POST['id'];
        if (isset($_SESSION['gio'][$id])) $_SESSION['gio'][$id] = max(1, min(20, (int)$_POST['soluong']));
        header('Location: index.php?p=giohang'); exit;
    }
    if ($hd === 'datdon') {
        $ten = trim($_POST['ten'] ?? ''); $sdt = trim($_POST['sdt'] ?? '');
        $dc = trim($_POST['diachi'] ?? ''); $gc = trim($_POST['ghichu'] ?? '');
        if ($ten === '' || mb_strlen($ten) > 100) $loi[] = 'Vui lòng nhập họ tên.';
        if (!sdtHopLe($sdt)) $loi[] = 'Số điện thoại không hợp lệ (VD: 0912345678).';
        if ($dc === '' || mb_strlen($dc) > 255) $loi[] = 'Vui lòng nhập địa chỉ.';
        if (mb_strlen($gc) > 255) $loi[] = 'Ghi chú quá dài.';
        [$ds, $tong] = layGio($conn);
        if (!$ds) $loi[] = 'Giỏ hàng trống.';
        if (!$loi) {
            $conn->begin_transaction();
            try {
                truyvan($conn, 'INSERT INTO donhang (ten_khach, sdt, diachi, ghichu, tongtien) VALUES (?,?,?,?,?)', 'ssssi', [$ten, $sdt, $dc, $gc, $tong]);
                $oid = $conn->insert_id;
                foreach ($ds as $sp)
                    truyvan($conn, 'INSERT INTO chitiet_don (donhang_id, sanpham_id, tensp, gia, soluong) VALUES (?,?,?,?,?)', 'iisii', [$oid, $sp['id'], $sp['ten'], $sp['gia'], $sp['sl']]);
                $conn->commit();
                unset($_SESSION['gio']);
                $_SESSION['tb'] = "Đặt hàng thành công! Mã đơn #$oid. Bạn có thể tra cứu bằng số điện thoại.";
                header('Location: index.php?p=tracuu&sdt=' . urlencode($sdt)); exit;
            } catch (Throwable $e) { $conn->rollback(); $loi[] = 'Có lỗi khi đặt hàng, vui lòng thử lại.'; }
        }
        $p = 'datdon';
    }
    if ($hd === 'dangnhap') {
        $u = layMot($conn, 'SELECT * FROM admin WHERE tendangnhap=?', 's', [trim($_POST['tendangnhap'] ?? '')]);
        if ($u && password_verify($_POST['matkhau'] ?? '', $u['matkhau'])) {
            session_regenerate_id(true); $_SESSION['admin'] = $u['id'];
            header('Location: index.php?p=admin'); exit;
        }
        sleep(1); $loi[] = 'Sai tên đăng nhập hoặc mật khẩu.'; $p = 'dangnhap';
    }
}

if ($p === 'dangxuat') { $_SESSION = []; session_destroy(); header('Location: index.php'); exit; }
if (in_array($p, ['admin', 'donhang', 'thongke'])) yeuCauAdmin();

dauTrang('Tiệm Trà Sữa');
if ($tb) echo '<div class="tb">' . h($tb) . '</div>';
foreach ($loi as $l) echo '<div class="tb l">' . h($l) . '</div>';

// ===== CÁC TRANG =====
if ($p === 'menu') {
    $q = trim($_GET['q'] ?? ''); $loai = trim($_GET['loai'] ?? '');
    $sql = 'SELECT * FROM sanpham WHERE dang_ban=1'; $k = ''; $a = [];
    if ($q !== '')    { $sql .= ' AND ten LIKE ?';  $k .= 's'; $a[] = "%$q%"; }
    if ($loai !== '') { $sql .= ' AND loai = ?';    $k .= 's'; $a[] = $loai; }
    $ds = layHet($conn, $sql . ' ORDER BY loai, ten', $k, $a);
    $loais = array_column(layHet($conn, 'SELECT DISTINCT loai FROM sanpham WHERE dang_ban=1 ORDER BY loai'), 'loai');
    ?>
    <h1>Menu</h1>
    <form class="tim" method="get">
      <input name="q" value="<?= h($q) ?>" placeholder="Tìm món..." style="flex:1;min-width:160px">
      <select name="loai"><option value="">Tất cả loại</option>
        <?php foreach ($loais as $l): ?><option <?= $l === $loai ? 'selected' : '' ?>><?= h($l) ?></option><?php endforeach; ?>
      </select>
      <button class="btn">Tìm</button>
    </form>
    <?php if (!$ds) echo '<p>Không tìm thấy món nào.</p>'; ?>
    <div class="grid">
    <?php foreach ($ds as $sp): ?>
      <div class="card">
        <small style="color:#c8873f"><b><?= h($sp['loai']) ?></b></small>
        <h3><?= h($sp['ten']) ?></h3>
        <div class="mo"><?= h($sp['mota']) ?></div>
        <p class="gia"><?= tien($sp['gia']) ?></p>
        <form method="post"><?= csrf() ?><input type="hidden" name="hanhdong" value="themgio"><input type="hidden" name="id" value="<?= $sp['id'] ?>">
          <input type="number" name="soluong" value="1" min="1" max="20" style="width:60px"> <button class="btn s">Thêm vào giỏ</button>
        </form>
      </div>
    <?php endforeach; ?>
    </div>
<?php } elseif ($p === 'giohang') {
    [$ds, $tong] = layGio($conn); ?>
    <h1>Giỏ hàng</h1>
    <?php if (!$ds): ?><p>Giỏ hàng trống. <a href="index.php">Xem menu</a></p><?php else: ?>
    <div class="cot"><table>
      <tr><th>Món</th><th>Giá</th><th>SL</th><th>Thành tiền</th><th></th></tr>
      <?php foreach ($ds as $sp): ?>
      <tr><td><?= h($sp['ten']) ?></td><td><?= tien($sp['gia']) ?></td>
        <td><form method="post" style="display:flex;gap:5px"><?= csrf() ?><input type="hidden" name="hanhdong" value="suagio"><input type="hidden" name="id" value="<?= $sp['id'] ?>">
          <input type="number" name="soluong" value="<?= $sp['sl'] ?>" min="1" max="20" style="width:60px"><button class="btn s">Cập nhật</button></form></td>
        <td><?= tien($sp['tt']) ?></td>
        <td><form method="post" action="delete.php"><?= csrf() ?><input type="hidden" name="loai" value="gio"><input type="hidden" name="id" value="<?= $sp['id'] ?>"><button class="btn s x">Xoá</button></form></td></tr>
      <?php endforeach; ?>
      <tr><th colspan="3" style="text-align:right">Tổng cộng</th><th colspan="2"><?= tien($tong) ?></th></tr>
    </table></div>
    <p><a class="btn" href="index.php?p=datdon">Đặt hàng</a></p>
    <?php endif; ?>
<?php } elseif ($p === 'datdon') {
    if (empty($_SESSION['gio'])) { echo '<p>Giỏ hàng trống. <a href="index.php">Xem menu</a></p>'; cuoiTrang(); exit; } ?>
    <h1>Thông tin đặt hàng</h1>
    <form method="post" class="form"><?= csrf() ?><input type="hidden" name="hanhdong" value="datdon">
      <label>Họ tên <input name="ten" value="<?= h($_POST['ten'] ?? '') ?>" required maxlength="100"></label>
      <label>Số điện thoại <input name="sdt" value="<?= h($_POST['sdt'] ?? '') ?>" required placeholder="0912345678"></label>
      <label>Địa chỉ giao hàng <input name="diachi" value="<?= h($_POST['diachi'] ?? '') ?>" required maxlength="255"></label>
      <label>Ghi chú (ít đường, ít đá...) <input name="ghichu" value="<?= h($_POST['ghichu'] ?? '') ?>" maxlength="255"></label>
      <button class="btn">Xác nhận đặt hàng</button>
    </form>
<?php } elseif ($p === 'tracuu') {
    $sdt = trim($_GET['sdt'] ?? ''); $dons = []; $e = '';
    if ($sdt !== '') {
        if (!sdtHopLe($sdt)) $e = 'Số điện thoại không hợp lệ.';
        else $dons = layHet($conn, 'SELECT * FROM donhang WHERE sdt=? ORDER BY ngaytao DESC LIMIT 20', 's', [$sdt]);
    } ?>
    <h1>Tra cứu đơn hàng</h1>
    <form class="tim" method="get"><input type="hidden" name="p" value="tracuu">
      <input name="sdt" value="<?= h($sdt) ?>" placeholder="Nhập số điện thoại đã đặt hàng" required style="flex:1;min-width:200px">
      <button class="btn">Tra cứu</button></form>
    <?php if ($e) echo '<div class="tb l">' . h($e) . '</div>';
    if ($sdt !== '' && !$e && !$dons) echo '<p>Không tìm thấy đơn hàng nào.</p>';
    foreach ($dons as $d): ?>
      <div class="box">
        <b>Đơn #<?= $d['id'] ?></b> <span class="nhan <?= $d['trangthai'] ?>"><?= h($TRANGTHAI[$d['trangthai']]) ?></span><br>
        <small><?= date('d/m/Y H:i', strtotime($d['ngaytao'])) ?> · <?= h($d['diachi']) ?></small>
        <ul><?php foreach (layHet($conn, 'SELECT * FROM chitiet_don WHERE donhang_id=?', 'i', [$d['id']]) as $c): ?>
          <li><?= h($c['tensp']) ?> × <?= $c['soluong'] ?> — <?= tien($c['gia'] * $c['soluong']) ?></li><?php endforeach; ?></ul>
        <?php if ($d['ghichu']) echo '<small>Ghi chú: ' . h($d['ghichu']) . '</small><br>'; ?>
        <b>Tổng: <?= tien($d['tongtien']) ?></b>
      </div>
    <?php endforeach; ?>
<?php } elseif ($p === 'dangnhap') {
    if (laAdmin()) { header('Location: index.php?p=admin'); exit; } ?>
    <h1>🔐 Đăng nhập chủ tiệm</h1>
    <form method="post" class="form"><?= csrf() ?><input type="hidden" name="hanhdong" value="dangnhap">
      <label>Tên đăng nhập <input name="tendangnhap" required autofocus></label>
      <label>Mật khẩu <input type="password" name="matkhau" required></label>
      <button class="btn">Đăng nhập</button></form>
<?php } elseif ($p === 'admin') {
    $ds = layHet($conn, 'SELECT * FROM sanpham ORDER BY loai, ten'); ?>
    <h1>Quản lý món</h1>
    <p><a class="btn" href="create.php">+ Thêm món</a></p>
    <div class="cot"><table>
      <tr><th>Tên</th><th>Loại</th><th>Giá</th><th>Trạng thái</th><th></th></tr>
      <?php foreach ($ds as $sp): ?>
      <tr><td><?= h($sp['ten']) ?></td><td><?= h($sp['loai']) ?></td><td><?= tien($sp['gia']) ?></td>
        <td><?= $sp['dang_ban'] ? 'Đang bán' : 'Ẩn' ?></td>
        <td><a class="btn s" href="update.php?id=<?= $sp['id'] ?>">Sửa</a>
          <form method="post" action="delete.php" style="display:inline" onsubmit="return confirm('Xoá món này?')"><?= csrf() ?>
            <input type="hidden" name="loai" value="sanpham"><input type="hidden" name="id" value="<?= $sp['id'] ?>"><button class="btn s x">Xoá</button></form></td></tr>
      <?php endforeach; ?>
    </table></div>
<?php } elseif ($p === 'donhang') {
    $id = (int)($_GET['id'] ?? 0);
    $d = $id ? layMot($conn, 'SELECT * FROM donhang WHERE id=?', 'i', [$id]) : null;
    if ($d): $cts = layHet($conn, 'SELECT * FROM chitiet_don WHERE donhang_id=?', 'i', [$id]); ?>
      <h1>Đơn #<?= $d['id'] ?> <span class="nhan <?= $d['trangthai'] ?>"><?= h($TRANGTHAI[$d['trangthai']]) ?></span></h1>
      <div class="box">
        <b>Khách:</b> <?= h($d['ten_khach']) ?> · <b>SĐT:</b> <?= h($d['sdt']) ?><br>
        <b>Địa chỉ:</b> <?= h($d['diachi']) ?><br><b>Ghi chú:</b> <?= h($d['ghichu'] ?: '—') ?><br>
        <b>Thời gian:</b> <?= date('d/m/Y H:i', strtotime($d['ngaytao'])) ?>
        <table style="margin-top:10px"><tr><th>Món</th><th>Giá</th><th>SL</th><th>Thành tiền</th></tr>
        <?php foreach ($cts as $c): ?><tr><td><?= h($c['tensp']) ?></td><td><?= tien($c['gia']) ?></td><td><?= $c['soluong'] ?></td><td><?= tien($c['gia'] * $c['soluong']) ?></td></tr><?php endforeach; ?>
        <tr><th colspan="3" style="text-align:right">Tổng</th><th><?= tien($d['tongtien']) ?></th></tr></table>
      </div>
      <form method="post" action="update.php?don=<?= $d['id'] ?>" style="display:flex;gap:8px"><?= csrf() ?>
        <select name="trangthai"><?php foreach ($TRANGTHAI as $k => $v): ?><option value="<?= $k ?>" <?= $k === $d['trangthai'] ? 'selected' : '' ?>><?= h($v) ?></option><?php endforeach; ?></select>
        <button class="btn">Cập nhật trạng thái</button><a class="btn g" href="index.php?p=donhang">Quay lại</a>
      </form>
    <?php else:
      $tt = $_GET['trangthai'] ?? ''; $sdt = trim($_GET['sdt'] ?? '');
      $sql = 'SELECT * FROM donhang WHERE 1=1'; $k = ''; $a = [];
      if (isset($TRANGTHAI[$tt])) { $sql .= ' AND trangthai=?'; $k .= 's'; $a[] = $tt; } else $tt = '';
      if ($sdt !== '') { $sql .= ' AND sdt LIKE ?'; $k .= 's'; $a[] = "%$sdt%"; }
      $dons = layHet($conn, $sql . ' ORDER BY ngaytao DESC LIMIT 200', $k, $a); ?>
      <h1>Lịch sử đơn hàng</h1>
      <form class="tim" method="get"><input type="hidden" name="p" value="donhang">
        <select name="trangthai"><option value="">Mọi trạng thái</option>
          <?php foreach ($TRANGTHAI as $k2 => $v): ?><option value="<?= $k2 ?>" <?= $k2 === $tt ? 'selected' : '' ?>><?= h($v) ?></option><?php endforeach; ?></select>
        <input name="sdt" value="<?= h($sdt) ?>" placeholder="Lọc theo SĐT"><button class="btn">Lọc</button></form>
      <div class="cot"><table>
        <tr><th>#</th><th>Thời gian</th><th>Khách</th><th>SĐT</th><th>Tổng</th><th>Trạng thái</th><th></th></tr>
        <?php foreach ($dons as $o): ?>
        <tr><td><?= $o['id'] ?></td><td><?= date('d/m/Y H:i', strtotime($o['ngaytao'])) ?></td><td><?= h($o['ten_khach']) ?></td><td><?= h($o['sdt']) ?></td>
          <td><?= tien($o['tongtien']) ?></td><td><span class="nhan <?= $o['trangthai'] ?>"><?= h($TRANGTHAI[$o['trangthai']]) ?></span></td>
          <td><a class="btn s" href="index.php?p=donhang&id=<?= $o['id'] ?>">Chi tiết</a></td></tr>
        <?php endforeach; if (!$dons) echo '<tr><td colspan="7">Chưa có đơn nào.</td></tr>'; ?>
      </table></div>
    <?php endif;
} elseif ($p === 'thongke') {
    $loai = ($_GET['loai'] ?? 'thang') === 'tuan' ? 'tuan' : 'thang';
    $sql = $loai === 'tuan'
        ? "SELECT YEARWEEK(ngaytao,1) k, DATE_FORMAT(DATE_SUB(DATE(ngaytao), INTERVAL WEEKDAY(ngaytao) DAY),'%d/%m/%Y') nhan, COUNT(*) sodon, SUM(tongtien) dt
           FROM donhang WHERE trangthai<>'huy' GROUP BY k, nhan ORDER BY k DESC LIMIT 12"
        : "SELECT DATE_FORMAT(ngaytao,'%Y-%m') k, DATE_FORMAT(ngaytao,'%m/%Y') nhan, COUNT(*) sodon, SUM(tongtien) dt
           FROM donhang WHERE trangthai<>'huy' GROUP BY k, nhan ORDER BY k DESC LIMIT 12";
    $rows = layHet($conn, $sql);
    $max = max(1, ...array_map('intval', array_column($rows, 'dt') ?: [1]));
    $top = layHet($conn, "SELECT c.tensp, SUM(c.soluong) sl, SUM(c.soluong*c.gia) dt FROM chitiet_don c JOIN donhang d ON d.id=c.donhang_id
                          WHERE d.trangthai<>'huy' GROUP BY c.tensp ORDER BY sl DESC LIMIT 5"); ?>
    <h1>Thống kê đơn hàng</h1>
    <p><a class="btn <?= $loai === 'tuan' ? '' : 'g' ?>" href="index.php?p=thongke&loai=tuan">Theo tuần</a>
       <a class="btn <?= $loai === 'thang' ? '' : 'g' ?>" href="index.php?p=thongke&loai=thang">Theo tháng</a></p>
    <div class="cot"><table>
      <tr><th><?= $loai === 'tuan' ? 'Tuần bắt đầu' : 'Tháng' ?></th><th>Số đơn</th><th>Doanh thu</th><th style="width:30%"></th></tr>
      <?php foreach ($rows as $r): ?>
      <tr><td><?= h($r['nhan']) ?></td><td><?= $r['sodon'] ?></td><td><?= tien($r['dt']) ?></td>
        <td><div style="background:#f1e4d3;border-radius:4px"><div style="background:#c8873f;height:14px;border-radius:4px;width:<?= round($r['dt'] / $max * 100) ?>%"></div></div></td></tr>
      <?php endforeach; if (!$rows) echo '<tr><td colspan="4">Chưa có dữ liệu.</td></tr>'; ?>
    </table></div>
    <p><small>Không tính đơn đã huỷ.</small></p>
    <h2>Top 5 món bán chạy</h2>
    <table><tr><th>Món</th><th>Số ly</th><th>Doanh thu</th></tr>
      <?php foreach ($top as $t): ?><tr><td><?= h($t['tensp']) ?></td><td><?= $t['sl'] ?></td><td><?= tien($t['dt']) ?></td></tr><?php endforeach; ?></table>
<?php }
cuoiTrang();
