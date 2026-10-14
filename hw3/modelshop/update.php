<?php
include 'check.php';

$id = (int)($_GET['id'] ?? 0);

// Lấy thông tin cũ của sản phẩm
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
if (!$row) {
    header("Location: index.php");
    exit();
}

$error = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $price = (int)$_POST['price'];
    $quantity = (int)$_POST['quantity'];
    $description = trim($_POST['description']);

    if ($name == "" || $price < 0 || $quantity < 0) {
        $error = "Vui lòng nhập tên, giá và số lượng hợp lệ.";
    } else {
        $stmt = $conn->prepare("UPDATE products SET name=?, price=?, quantity=?, description=? WHERE id=?");
        $stmt->bind_param("siisi", $name, $price, $quantity, $description, $id);
        $stmt->execute();
        header("Location: index.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Sửa sản phẩm</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php include 'menu.php'; ?>
    <h2>Sửa Thông Tin Sản Phẩm</h2>
    <?php if ($error): ?><p class="loi"><?= h($error) ?></p><?php endif; ?>
    <form method="POST">
        <p>Tên sản phẩm:<br><input type="text" name="name" value="<?= h($row['name']) ?>" required></p>
        <p>Giá (₫):<br><input type="number" name="price" value="<?= (int)$row['price'] ?>" min="0" required></p>
        <p>Số lượng:<br><input type="number" name="quantity" value="<?= (int)$row['quantity'] ?>" min="0" required></p>
        <p>Mô tả:<br><textarea name="description" rows="3"><?= h($row['description']) ?></textarea></p>
        <p><button type="submit">Cập nhật</button> <a href="index.php">Quay lại</a></p>
    </form>

    <hr>
    <form method="POST" action="delete.php">
        <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
        <button class="nut-xoa" type="submit">Xoá sản phẩm này</button>
        <span class="nho">Đơn hàng cũ vẫn giữ tên và giá của món.</span>
    </form>
</body>
</html>
