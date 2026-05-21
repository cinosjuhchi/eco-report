<?php
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$title = 'Eco Report';
$url = $scheme . '://' . $host . '/';

$title = 'Eco Report';

