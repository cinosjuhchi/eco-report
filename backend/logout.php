<?php
require_once '../config/config.php';
session_start();
session_destroy();
header("Location: " . $url . "page/login/index.php?message=Logout+berhasil.");
exit();
?>