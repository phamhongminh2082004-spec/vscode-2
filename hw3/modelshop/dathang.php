<?php
include 'ketnoi.php';

if ($_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: index.php");
    exit();
}

$name = trim($_POST['name'] ?? '');
$phone = sdt($_POST['phone'] ?? '');
$address = trim($_POST['address'] ?? '');
$qty = array_filter(array_map('intval', (array)($_POST['qty'] ?? [])), fn($n) => $n > 0);
$error = "";
$order_id = 0;

if ($name == "" || $address == "") $error = "Vui lòng nhập họ tên và địa chỉ.";
elseif (!preg_match('/^0\d{9,10}$/', $phone)) $error = "Số điện thoại không hợp lệ.";
elseif (!$qty) $error = "Bạn chưa chọn món nào.";

if ($error == "") {
    try {
        $conn->begin_transaction();
        ksort($qty);
        $items = [];
        $total = 0;
        foreach ($qty as $id => $n) {
            $s = $conn->prepare("SELECT name, price, quantity FROM products WHERE id = ? FOR UPDATE");
            $s->bind_param("i", $id);
            $s->execute();
            $p = $s->get_result()->fetch_assoc();
            if (!$p) throw new Exception("Có món không còn bán.");
            if ($p['quantity'] < $n) throw new Exception("Món \"" . $p['name'] . "\" chỉ còn " . $p['quantity'] . " sản phẩm.");
            $items[] = [$id, $p['name'], $p['price'], $n];
            $total += $p['price'] * $n;
        }

        $s = $conn->prepare("INSERT INTO orders (customer_name, phone, address, total) VALUES (?, ?, ?, ?)");
        $s->bind_param("sssi", $name, $phone, $address, $total);
        $s->execute();
        $order_id = $conn->insert_id;

        $ins = $conn->prepare("INSERT INTO order_items (order_id, product_id, product_name, price, quantity) VALUES (?, ?, ?, ?, ?)");
        $upd = $conn->prepare("UPDATE products SET quantity = quantity - ? WHERE id = ?");
        foreach ($items as $it) {
            $ins->bind_param("iisii", $order_id, $it[0], $it[1], $it[2], $it[3]);
            $ins->execute();
            $upd->bind_param("ii", $it[3], $it[0]);
            $upd->execute();
        }
        $conn->commit();
    } catch (Exception $e) {
        $conn->rollback();
        $order_id = 0;
        $error = ($e instanceof mysqli_sql_exception) ? "Lỗi hệ thống, vui lòng thử lại." : $e->getMessage();
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Đặt hàng</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php if ($error == ""): ?>
        <h2 class="ok">Đặt hàng thành công!</h2>
        <p>Mã đơn của bạn: <strong>DH<?= str_pad($order_id, 5, "0", STR_PAD_LEFT) ?></strong></p>
        <p>Tiệm sẽ gọi số <strong><?= h($phone) ?></strong> để xác nhận. Bạn có thể theo dõi đơn bằng số điện thoại này.</p>
        <p><a href="tracuu.php">Tra cứu đơn hàng</a> | <a href="index.php">Quay lại menu</a></p>
    <?php else: ?>
        <h2 class="loi">Chưa đặt được hàng</h2>
        <p class="loi"><?= h($error) ?></p>
        <p><a href="index.php">Quay lại menu</a></p>
    <?php endif; ?>
</body>
</html>
