<?php
include 'check.php';

// Cập nhật trạng thái đơn
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = (int)$_POST['id'];
    $new = $_POST['status'] ?? '';

    if (isset($trang_thai[$new])) {
        $stmt = $conn->prepare("SELECT status FROM orders WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $cur = $stmt->get_result()->fetch_assoc();

        // Đơn đã huỷ thì không đổi nữa
        if ($cur && $cur['status'] != 'cancelled' && $cur['status'] != $new) {
            $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
            $stmt->bind_param("si", $new, $id);
            $stmt->execute();

            // Huỷ đơn -> hoàn lại số lượng vào kho
            if ($new == 'cancelled') {
                $stmt = $conn->prepare("UPDATE products p JOIN order_items i ON i.product_id = p.id
                                        SET p.quantity = p.quantity + i.quantity WHERE i.order_id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
            }
        }
    }
    header("Location: donhang.php" . (!empty($_GET['status']) ? "?status=" . urlencode($_GET['status']) : ""));
    exit();
}

// Lọc theo trạng thái (tuỳ chọn)
$loc = $_GET['status'] ?? '';
$sql = "SELECT o.*, GROUP_CONCAT(CONCAT(i.product_name, ' x', i.quantity) SEPARATOR ', ') AS items
        FROM orders o LEFT JOIN order_items i ON i.order_id = o.id ";
if (isset($trang_thai[$loc])) {
    $stmt = $conn->prepare($sql . "WHERE o.status = ? GROUP BY o.id ORDER BY o.id DESC LIMIT 100");
    $stmt->bind_param("s", $loc);
} else {
    $stmt = $conn->prepare($sql . "GROUP BY o.id ORDER BY o.id DESC LIMIT 100");
}
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đơn hàng</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php include 'menu.php'; ?>
    <h2>LỊCH SỬ ĐƠN HÀNG</h2>
    <p>Lọc: <a href="donhang.php">Tất cả</a>
        <?php foreach ($trang_thai as $k => $v): ?> | <a href="donhang.php?status=<?= $k ?>"><?= $v ?></a><?php endforeach; ?>
    </p>

    <table>
        <tr><th>Mã</th><th>Ngày đặt</th><th>Khách hàng</th><th>Món</th><th>Tổng</th><th>Trạng thái</th></tr>
        <?php if ($result->num_rows > 0): while ($o = $result->fetch_assoc()): ?>
            <tr>
                <td>DH<?= str_pad($o['id'], 5, "0", STR_PAD_LEFT) ?></td>
                <td><?= date("d/m/Y H:i", strtotime($o['created_at'])) ?></td>
                <td><?= h($o['customer_name']) ?><br><?= h($o['phone']) ?><br><span class="nho"><?= h($o['address']) ?></span></td>
                <td><?= h($o['items']) ?></td>
                <td><?= tien($o['total']) ?></td>
                <td>
                    <?php if ($o['status'] == 'cancelled'): ?>
                        <?= $trang_thai['cancelled'] ?>
                    <?php else: ?>
                        <form method="POST">
                            <input type="hidden" name="id" value="<?= $o['id'] ?>">
                            <select name="status">
                                <?php foreach ($trang_thai as $k => $v): ?>
                                    <option value="<?= $k ?>" <?= $k == $o['status'] ? 'selected' : '' ?>><?= $v ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit">Lưu</button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endwhile; else: ?>
            <tr><td colspan="6">Chưa có đơn hàng nào.</td></tr>
        <?php endif; ?>
    </table>
    <p class="nho">Hiển thị tối đa 100 đơn mới nhất. Chọn "Đã huỷ" sẽ hoàn lại số lượng vào kho và không đổi lại được.</p>
</body>
</html>
