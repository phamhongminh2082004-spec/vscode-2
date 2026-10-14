<?php
include 'check.php';

$by = (($_GET['by'] ?? 'month') == 'week') ? 'week' : 'month';

if ($by == 'month') {
    $ky = "DATE_FORMAT(created_at, '%Y-%m')";
    $tu = date('Y-m-01', strtotime('first day of -11 months'));   // 12 tháng gần nhất
} else {
    $ky = "DATE_SUB(DATE(created_at), INTERVAL WEEKDAY(created_at) DAY)"; // thứ Hai đầu tuần
    $tu = date('Y-m-d', strtotime('monday this week -11 weeks'));  // 12 tuần gần nhất
}

$stmt = $conn->prepare("SELECT $ky AS ky, COUNT(*) AS so_don,
        SUM(status = 'completed') AS hoan_thanh, SUM(status = 'cancelled') AS da_huy,
        COALESCE(SUM(CASE WHEN status = 'completed' THEN total END), 0) AS doanh_thu
    FROM orders WHERE created_at >= ? GROUP BY ky ORDER BY ky DESC");
$stmt->bind_param("s", $tu);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Thống kê</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php include 'menu.php'; ?>
    <h2>THỐNG KÊ ĐƠN HÀNG</h2>
    <p><a href="thongke.php?by=month">Theo tháng</a> | <a href="thongke.php?by=week">Theo tuần</a>
       – đang xem: <strong><?= $by == 'month' ? '12 tháng gần nhất' : '12 tuần gần nhất' ?></strong></p>

    <table>
        <tr><th><?= $by == 'month' ? 'Tháng' : 'Tuần (từ thứ Hai)' ?></th><th>Số đơn</th><th>Hoàn thành</th><th>Đã huỷ</th><th>Doanh thu</th></tr>
        <?php if ($result->num_rows > 0): while ($r = $result->fetch_assoc()): ?>
            <tr>
                <td><?= $by == 'month' ? date('m/Y', strtotime($r['ky'] . '-01')) : date('d/m/Y', strtotime($r['ky'])) ?></td>
                <td><?= $r['so_don'] ?></td>
                <td><?= $r['hoan_thanh'] ?></td>
                <td><?= $r['da_huy'] ?></td>
                <td><?= tien($r['doanh_thu']) ?></td>
            </tr>
        <?php endwhile; else: ?>
            <tr><td colspan="5">Chưa có đơn hàng trong khoảng thời gian này.</td></tr>
        <?php endif; ?>
    </table>
    <p class="nho">Doanh thu chỉ tính các đơn có trạng thái "Hoàn thành".</p>
</body>
</html>
