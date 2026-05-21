<?php
$host = 'db';
$username = 'root';
$password = 'root';
$database = 'eco_report';

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
	die("Koneksi gagal: " . $conn->connect_error);
}
?>