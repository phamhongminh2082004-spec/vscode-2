<?php
include 'ketnoi.php';

$la_chu = !empty($_SESSION['admin']);   // đã đăng nhập chủ tiệm?
$q = trim($_GET['q'] ?? '');
$like = "%$q%";
$stmt = $conn->prepare("SELECT * FROM products WHERE name LIKE ? OR description LIKE ? ORDER BY (quantity > 0) DESC, id DESC");
$stmt->bind_param("ss", $like, $like);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tiệm Mô Hình</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h2>TIỆM MÔ HÌNH – MENU</h2>
    <?php include 'menu.php'; ?>

    <form method="GET">
        <input type="text" name="q" value="<?= h($q) ?>" placeholder="Tìm món...">
        <button type="submit">Tìm</button> <a href="index.php">Xoá lọc</a>
    </form>

    <form method="POST" action="dathang.php">
        <table>
            <tr>
                <th>Tên món</th><th>Giá</th><th>Còn lại</th><th>Số lượng đặt</th>
                <?php if ($la_chu): ?><th>Quản lý</th><?php endif; ?>
            </tr>
            <?php if ($result->num_rows > 0): while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= h($row['name']) ?><br><span class="nho"><?= h($row['description']) ?></span></td>
                    <td><?= tien($row['price']) ?></td>
                    <td><?= $row['quantity'] > 0 ? (int)$row['quantity'] : 'Hết hàng' ?></td>
                    <td>
                        <?php if ($row['quantity'] > 0): ?>
                            <input type="number" name="qty[<?= (int)$row['id'] ?>]" value="0" min="0" max="<?= (int)$row['quantity'] ?>">
                        <?php else: ?>–<?php endif; ?>
                    </td>
                    <?php if ($la_chu): ?><td><a href="update.php?id=<?= (int)$row['id'] ?>">Sửa / Xoá</a></td><?php endif; ?>
                </tr>
            <?php endwhile; else: ?>
                <tr><td colspan="5">Không tìm thấy món nào.</td></tr>
            <?php endif; ?>
        </table>

        <h3>Thông tin nhận hàng</h3>
        <p>Họ tên:<br><input type="text" name="name" required></p>
        <p>Số điện thoại:<br><input type="tel" name="phone" required></p>
        <p>Địa chỉ:<br><input type="text" name="address" required></p>
        <p><button type="submit">Đặt hàng</button> <span class="nho">Thanh toán khi nhận hàng</span></p>
    </form>
</body>
</html>
