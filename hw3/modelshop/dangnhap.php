<?php
include 'ketnoi.php';

if (!empty($_SESSION['admin'])) {
    header("Location: index.php");
    exit();
}

$error = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user = trim($_POST['username'] ?? '');
    $pass = $_POST['password'] ?? '';

    // Sai quá 5 lần trong 5 phút thì tạm khoá
    $_SESSION['fail'] = array_filter($_SESSION['fail'] ?? [], fn($t) => $t > time() - 300);
    if (count($_SESSION['fail']) >= 5) {
        $error = "Sai quá nhiều lần, vui lòng thử lại sau 5 phút.";
    } else {
        $stmt = $conn->prepare("SELECT username, password FROM admins WHERE username = ?");
        $stmt->bind_param("s", $user);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();

        if ($row && password_verify($pass, $row['password'])) {
            session_regenerate_id(true);
            $_SESSION['admin'] = $row['username'];
            $_SESSION['fail'] = [];
            header("Location: index.php");
            exit();
        }
        $_SESSION['fail'][] = time();
        $error = "Sai tên đăng nhập hoặc mật khẩu.";
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Đăng nhập quản trị</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h2>Đăng nhập quản trị</h2>
    <?php if ($error): ?><p class="loi"><?= h($error) ?></p><?php endif; ?>
    <form method="POST">
        <p>Tên đăng nhập:<br><input type="text" name="username" required></p>
        <p>Mật khẩu:<br><input type="password" name="password" required></p>
        <p><button type="submit">Đăng nhập</button> <a href="index.php">Về trang khách</a></p>
    </form>
</body>
</html>
