<?php
include 'ketnoi.php';
$_SESSION = [];
session_destroy();
header("Location: index.php");
exit();
?>
