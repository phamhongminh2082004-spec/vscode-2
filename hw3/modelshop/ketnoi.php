<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "shop_db";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Tạo kết nối
try {
    $conn = new mysqli($servername, $username, $password, $dbname);
} catch (mysqli_sql_exception $e) {
    die("Kết nối thất bại. Kiểm tra lại thông tin trong ketnoi.php");
}

// Thiết lập tiếng Việt + múi giờ
$conn->set_charset("utf8mb4");
$conn->query("SET time_zone = '+07:00'");
date_default_timezone_set("Asia/Ho_Chi_Minh");

// Phiên đăng nhập (dùng cho phần quản trị)
session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
session_start();

// Hàm dùng chung
function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function tien($n) { return number_format((int)$n, 0, ',', '.') . ' ₫'; }
function sdt($s) {
    $s = preg_replace('/\D/', '', $s);
    if (strlen($s) == 11 && substr($s, 0, 2) == '84') $s = '0' . substr($s, 2);
    return $s;
}
$trang_thai = ['pending' => 'Chờ xác nhận', 'confirmed' => 'Đã xác nhận', 'shipping' => 'Đang giao',
               'completed' => 'Hoàn thành', 'cancelled' => 'Đã huỷ'];
?>
