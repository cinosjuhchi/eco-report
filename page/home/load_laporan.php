<?php
session_start();
require_once '../../config/config.php';
require_once '../../backend/koneksi.php';

header('Content-Type: application/json');

$assetBase = isset($url) ? $url : '/';

if (!isset($_SESSION['username'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 5;
$search = isset($_GET['q']) ? trim((string)$_GET['q']) : '';

// Query untuk mengambil laporan, data user, gambar pertama, jumlah like, dan jumlah komentar
$where = "";
$params = [];
$types = "";

if ($search !== '') {
    $where = "WHERE (l.judul LIKE ? OR l.deskripsi_laporan LIKE ? OR l.kode_laporan LIKE ? OR u.nama_lengkap LIKE ?)";
    $like = '%' . $search . '%';
    $params = [$like, $like, $like, $like];
    $types = "ssss";
}

$sql = "
    SELECT 
        l.id,
        l.kode_laporan,
        l.judul,
        l.deskripsi_laporan,
        l.status,
        l.created_at,
        u.nama_lengkap,
        u.foto_profil,
        (SELECT foto_url FROM laporan_foto lf WHERE lf.laporan_id = l.id LIMIT 1) as foto_utama,
        (SELECT COUNT(*) FROM laporan_like ll WHERE ll.laporan_id = l.id) as total_like,
        (SELECT COUNT(*) FROM laporan_komentar lk WHERE lk.laporan_id = l.id) as total_komentar
    FROM laporan l
    JOIN users u ON l.user_id = u.id
    $where
    ORDER BY l.created_at DESC, l.status ASC
    LIMIT ? OFFSET ?
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $conn->error]);
    exit();
}

$types .= "ii";
$params[] = $limit;
$params[] = $offset;

$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$laporans = [];
while ($row = $result->fetch_assoc()) {
    $datetime = new DateTime($row['created_at']);
    $now = new DateTime();
    $diff = $now->diff($datetime);
    
    if ($diff->days == 0) {
        $waktu = "Hari ini";
    } elseif ($diff->days == 1) {
        $waktu = "Kemarin";
    } else {
        $waktu = $diff->days . " hari yang lalu";
    }
    
    $row['waktu_berlalu'] = $waktu;
    $row['foto_profil'] = !empty($row['foto_profil'])
        ? $assetBase . 'public/uploads/' . $row['foto_profil']
        : 'https://ui-avatars.com/api/?name=' . $row['nama_lengkap'] . '&background=2E7D32&color=fff';
    $row['foto_utama'] = $row['foto_utama']
        ? $assetBase . $row['foto_utama']
        : $assetBase . 'asset/image/banner.png';
    
    $laporans[] = $row;
}


echo json_encode([
    'data' => $laporans,
    'has_more' => count($laporans) === $limit
]);
?>