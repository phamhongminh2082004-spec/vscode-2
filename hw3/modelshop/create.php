<?php
include 'check.php';

$error = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $price = (int)$_POST['price'];
    $quantity = (int)$_POST['quantity'];
    $description = trim($_POST['description']);

    if ($name == "" || $price < 0 || $quantity < 0) {
        $error = "Vui lòng nhập tên, giá và số lượng hợp lệ.";
    } else {
        $stmt = $conn->prepare("INSERT INTO products (name, price, quantity, description) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("siis", $name, $price, $quantity, $description);
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
    <title>Thêm sản phẩm</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php include 'menu.php'; ?>
    <h2>Thêm Sản Phẩm Mới</h2>
    <?php if ($error): ?><p class="loi"><?= h($error) ?></p><?php endif; ?>
    <form method="POST">
        <p>Tên sản phẩm:<br><input type="text" name="name" required></p>
        <p>Giá (₫):<br><input type="number" name="price" min="0" required></p>
        <p>Số lượng:<br><input type="number" name="quantity" min="0" required></p>
        <p>Mô tả:<br><textarea name="description" rows="3"></textarea></p>
        <p><button type="submit">Lưu</button> <a href="index.php">Quay lại</a></p>
    </form>
</body>
</html>
