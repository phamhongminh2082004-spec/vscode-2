<?php
include 'ketnoi.php';

$phone = sdt($_GET['phone'] ?? '');
$orders = null;
$error = "";

if (isset($_GET['phone'])) {
    if (!preg_match('/^0\d{9,10}$/', $phone)) {
        $error = "Số điện thoại không hợp lệ.";
    } else {
        $stmt = $conn->prepare("SELECT o.id, o.total, o.status, o.created_at,
                GROUP_CONCAT(CONCAT(i.product_name, ' x', i.quantity) SEPARATOR ', ') AS items
            FROM orders o LEFT JOIN order_items i ON i.order_id = o.id
            WHERE o.phone = ? GROUP BY o.id ORDER BY o.id DESC LIMIT 20");
        $stmt->bind_param("s", $phone);
        $stmt->execute();
        $orders = $stmt->get_result();
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tra cứu đơn hàng</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h2>TRA CỨU ĐƠN HÀNG</h2>
    <?php include 'menu.php'; ?>

    <form method="GET">
        <p>Số điện thoại đã dùng khi đặt hàng:<br>
        <input type="tel" name="phone" value="<?= h($phone) ?>" required>
        <button type="submit">Tra cứu</button></p>
    </form>

    <?php if ($error): ?><p class="loi"><?= h($error) ?></p><?php endif; ?>

    <?php if ($orders !== null): if ($orders->num_rows > 0): ?>
        <table>
            <tr><th>Mã đơn</th><th>Ngày đặt</th><th>Món</th><th>Tổng</th><th>Trạng thái</th></tr>
            <?php while ($o = $orders->fetch_assoc()): ?>
                <tr>
                    <td>DH<?= str_pad($o['id'], 5, "0", STR_PAD_LEFT) ?></td>
                    <td><?= date("d/m/Y H:i", strtotime($o['created_at'])) ?></td>
                    <td><?= h($o['items']) ?></td>
                    <td><?= tien($o['total']) ?></td>
                    <td><?= h($trang_thai[$o['status']]) ?></td>
                </tr>
            <?php endwhile; ?>
        </table>
    <?php else: ?>
        <p>Không có đơn nào với số này.</p>
    <?php endif; endif; ?>
</body>
</html>
