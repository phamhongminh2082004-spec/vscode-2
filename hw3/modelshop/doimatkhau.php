<?php
include 'check.php';

$msg = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $old = $_POST['old'] ?? '';
    $new = $_POST['new'] ?? '';

    $stmt = $conn->prepare("SELECT password FROM admins WHERE username = ?");
    $stmt->bind_param("s", $_SESSION['admin']);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    if (!$row || !password_verify($old, $row['password'])) {
        $msg = "<span class='loi'>Mật khẩu hiện tại không đúng.</span>";
    } elseif (strlen($new) < 8) {
        $msg = "<span class='loi'>Mật khẩu mới tối thiểu 8 ký tự.</span>";
    } else {
        $hash = password_hash($new, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE admins SET password = ? WHERE username = ?");
        $stmt->bind_param("ss", $hash, $_SESSION['admin']);
        $stmt->execute();
        $msg = "<span class='ok'>Đã đổi mật khẩu.</span>";
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đổi mật khẩu</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php include 'menu.php'; ?>
    <h2>Đổi mật khẩu</h2>
    <p><?= $msg ?></p>
    <form method="POST">
        <p>Mật khẩu hiện tại:<br><input type="password" name="old" required></p>
        <p>Mật khẩu mới (tối thiểu 8 ký tự):<br><input type="password" name="new" required></p>
        <p><button type="submit">Lưu</button></p>
    </form>
</body>
</html>
