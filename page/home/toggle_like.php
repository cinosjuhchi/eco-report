<?php
session_start();
require_once '../../config/config.php';
require_once '../../backend/koneksi.php';
require_once '../../backend/function.php';

header('Content-Type: application/json');

if (!isset($_SESSION['username'])) {
    echo json_encode(['ok' => false, 'message' => 'Unauthorized']);
    exit();
}

$laporan_id = isset($_POST['laporan_id']) ? (int)$_POST['laporan_id'] : 0;
if ($laporan_id <= 0) {
    echo json_encode(['ok' => false, 'message' => 'Invalid laporan_id']);
    exit();
}

// Ambil user_id dari session username
$user = get_logged_in_user('id');

if (!$user) {
    echo json_encode(['ok' => false, 'message' => 'User tidak ditemukan']);
    exit();
}
$user_id = $user['id'];

// Cek apakah sudah like
$stmt = $conn->prepare("SELECT id FROM laporan_like WHERE laporan_id = ? AND user_id = ?");
$stmt->bind_param("ii", $laporan_id, $user_id);
$stmt->execute();
$already_liked = $stmt->get_result()->num_rows > 0;

if ($already_liked) {
    // Unlike
    $stmt = $conn->prepare("DELETE FROM laporan_like WHERE laporan_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $laporan_id, $user_id);
    $stmt->execute();
    $liked = false;
} else {
    // Like
    $stmt = $conn->prepare("INSERT INTO laporan_like (laporan_id, user_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $laporan_id, $user_id);
    $stmt->execute();
    $liked = true;
}

// Ambil total like terbaru
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM laporan_like WHERE laporan_id = ?");
$stmt->bind_param("i", $laporan_id);
$stmt->execute();
$total_like = $stmt->get_result()->fetch_assoc()['total'];

echo json_encode(['ok' => true, 'liked' => $liked, 'total_like' => $total_like]);
?>