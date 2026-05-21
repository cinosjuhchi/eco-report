<?php
session_start();
require_once '../../config/config.php';
require_once '../../backend/koneksi.php';
require_once '../../backend/function.php';

$assetBase = isset($url) ? $url : '/';

if (!isset($_SESSION['username'])) {
    header("Location: " . $assetBase . "page/login/index.php?error=Anda+harus+login+terlebih+dulu.");
    exit();
}

$kode_laporan = isset($_GET['kode']) ? $_GET['kode'] : '';

if (empty($kode_laporan)) {
    header("Location: " . $assetBase . "page/home/index.php");
    exit();
}

// Fetch detail laporan
$sql_laporan = "SELECT l.*, u.nama_lengkap, u.foto_profil 
                FROM laporan l 
                JOIN users u ON l.user_id = u.id 
                WHERE l.kode_laporan = ?";
$stmt = $conn->prepare($sql_laporan);
$stmt->bind_param("s", $kode_laporan);
$stmt->execute();
$result_laporan = $stmt->get_result();

if ($result_laporan->num_rows === 0) {
    header("Location: " . $assetBase . "page/home/index.php?error=Laporan+tidak+ditemukan");
    exit();
}

$laporan = $result_laporan->fetch_assoc();
$laporan_id = $laporan['id'];

// Fetch user yang sedang login
$current_user = get_logged_in_user();
if (!$current_user) {
    header("Location: " . $assetBase . "page/login/index.php?error=User+tidak+ditemukan.");
    exit();
}

// Handle POST komentar
$komentar_error = null;
$komentar_success = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['isi_komentar'])) {
    $isi_komentar = trim($_POST['isi_komentar']);
    if (empty($isi_komentar)) {
        $komentar_error = "Komentar tidak boleh kosong.";
    } else {
        $query = "INSERT INTO laporan_komentar (laporan_id, user_id, isi_komentar) VALUES (?, ?, ?)";
        $stmt_insert = $conn->prepare($query);
        $stmt_insert->bind_param("iis", $laporan_id, $current_user['id'], $isi_komentar);
        if ($stmt_insert->execute()) {
            header("Location: ?kode=" . $kode_laporan . "#komentar");
            exit();
        } else {
            $komentar_error = "Gagal mengirim komentar. Coba lagi.";
        }
    }
}

// Fetch foto laporan
$sql_foto = "SELECT foto_url FROM laporan_foto WHERE laporan_id = ?";
$stmt_foto = $conn->prepare($sql_foto);
$stmt_foto->bind_param("i", $laporan_id);
$stmt_foto->execute();
$result_foto = $stmt_foto->get_result();
$foto_urls = [];
while ($row = $result_foto->fetch_assoc()) {
    $foto_urls[] = $row['foto_url'];
}

// Fetch komentar
$sql_komentar = "SELECT k.*, u.nama_lengkap, u.foto_profil 
                 FROM laporan_komentar k 
                 JOIN users u ON k.user_id = u.id 
                 WHERE k.laporan_id = ? 
                 ORDER BY k.created_at DESC";
$stmt_komentar = $conn->prepare($sql_komentar);
$stmt_komentar->bind_param("i", $laporan_id);
$stmt_komentar->execute();
$result_komentar = $stmt_komentar->get_result();
$komentars = [];
while ($row = $result_komentar->fetch_assoc()) {
    $komentars[] = $row;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Laporan Lingkungan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="<?= $assetBase ?>asset/style/theme.css">
    <link rel="stylesheet" href="<?= $assetBase ?>asset/style/main.css">
    <style>
        body { background-color: #f9f9f9; }
        .back-btn {
            color: #4B5563;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            font-size: 24px;
            margin-bottom: 24px;
        }
        .report-title {
            font-size: 22px;
            font-weight: 700;
            line-height: 1.3;
        }
        .status-badge {
            background-color: var(--accent-color);
            color: var(--text-color);
            padding: 4px 12px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            display: inline-block;
            margin-left: 12px;
            height: fit-content;
        }
        .report-id {
            color: #9CA3AF;
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 24px;
        }
        .detail-image {
            width: 100%;
            border-radius: 12px;
            object-fit: cover;
            margin-bottom: 24px;
            height: 280px;
        }
        .info-card {
            background: white;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            border: 1px solid #F3F4F6;
        }
        .info-card h4 {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 20px;
        }
        .info-label {
            color: #9CA3AF;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 4px;
        }
        .info-value {
            color: #111827;
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 16px;
        }
        .info-desc {
            color: #6B7280;
            font-size: 15px;
            line-height: 1.6;
        }
        .komentar-item:last-child {
            margin-bottom: 0 !important;
        }
    </style>
</head>
<body>
    <main class="container py-4" style="max-width: 1200px;">
        <a href="javascript:history.back()" class="back-btn">
            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" fill="currentColor" class="bi bi-arrow-left" viewBox="0 0 16 16">
                <path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8z"/>
            </svg>
        </a>

        <div class="d-flex align-items-start mb-1">
            <h1 class="report-title mb-0"><?= htmlspecialchars($laporan['judul']) ?></h1>
            <span class="status-badge"><?= htmlspecialchars($laporan['status']) ?></span>
        </div>
        <div class="report-id mb-4">LAPORAN - #<?= htmlspecialchars($laporan['kode_laporan']) ?></div>

        <div class="row g-4">
            <!-- Kolom Kiri -->
            <div class="col-lg-7 col-md-6">
                <?php if (!empty($foto_urls)): ?>
                    <?php foreach ($foto_urls as $foto): ?>
                        <img src="<?= $assetBase . htmlspecialchars($foto) ?>" alt="Foto Laporan" class="detail-image" onerror="this.style.display='none'">
                    <?php endforeach; ?>
                <?php else: ?>
                    <img src="<?= $assetBase ?>asset/image/banner.png" alt="Foto Laporan" class="detail-image" onerror="this.style.display='none'">
                <?php endif; ?>

                <div class="info-card">
                    <h4>Informasi Tambahan</h4>
                    <p class="info-desc mb-0"><?= nl2br(htmlspecialchars($laporan['deskripsi_laporan'])) ?></p>
                </div>
                <div class="info-card">
                    <h4>Lokasi</h4>
                    <div class="info-label">Provinsi</div>
                    <div class="info-value"><?= htmlspecialchars($laporan['provinsi']) ?></div>
                    <div class="info-label">Kota / Kabupaten</div>
                    <div class="info-value"><?= htmlspecialchars($laporan['kota_kabupaten']) ?></div>
                    <div class="info-label">Kecamatan / Kelurahan</div>
                    <div class="info-value"><?= htmlspecialchars($laporan['kecamatan']) ?></div>
                    <div class="info-label">Area Spesifik</div>
                    <div class="info-value mb-0"><?= htmlspecialchars($laporan['area_spesifik']) ?></div>
                </div>
            </div>

            <!-- Kolom Kanan -->
            <div class="col-lg-5 col-md-6">
                <div class="info-card" id="komentar">
                    <h4>Komentar (<?= count($komentars) ?>)</h4>

                    <?php if ($komentar_error): ?>
                        <div class="alert alert-danger py-2" style="font-size: 14px;"><?= htmlspecialchars($komentar_error) ?></div>
                    <?php endif; ?>

                    <!-- Daftar Komentar -->
                    <?php if (empty($komentars)): ?>
                        <p class="text-muted" style="font-size: 14px;">Belum ada komentar. Jadilah yang pertama!</p>
                    <?php else: ?>
                        <?php foreach ($komentars as $komentar): ?>
                            <div class="d-flex mb-4 komentar-item">
                                <img src="<?= avatar_url($komentar['nama_lengkap'], $komentar['foto_profil'], $assetBase) ?>"
                                     alt="<?= htmlspecialchars($komentar['nama_lengkap']) ?>"
                                     class="rounded-circle me-3" width="40" height="40"
                                     style="object-fit: cover; flex-shrink: 0;">
                                <div>
                                    <div class="fw-bold mb-1" style="font-size: 15px; color: #111827;">
                                        <?= htmlspecialchars($komentar['nama_lengkap']) ?>
                                        <span class="text-muted fw-normal ms-1" style="font-size: 12px;">• <?= time_ago($komentar['created_at']) ?></span>
                                    </div>
                                    <div class="info-desc" style="font-size: 14px;"><?= nl2br(htmlspecialchars($komentar['isi_komentar'])) ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <!-- Input Komentar -->
                    <hr class="mb-4" style="border-color: #E5E7EB;">
                    <div class="d-flex align-items-start">
                        <img src="<?= avatar_url($current_user['nama_lengkap'], $current_user['foto_profil'], $assetBase) ?>"
                             alt="<?= htmlspecialchars($current_user['nama_lengkap']) ?>"
                             class="rounded-circle me-3" width="40" height="40"
                             style="object-fit: cover; flex-shrink: 0;">
                        <div class="flex-grow-1">
                            <form method="POST" action="?kode=<?= $kode_laporan ?>">
                                <textarea class="form-control mb-3" name="isi_komentar" rows="2" placeholder="Tulis komentar Anda..." style="font-size: 14px; border-radius: 10px; border: 1px solid #E5E7EB; box-shadow: none; resize: none;" required><?= htmlspecialchars($_POST['isi_komentar'] ?? '') ?></textarea>
                                <div class="text-end">
                                    <button type="submit" class="btn btn-primary px-4 py-2" style="font-size: 14px; border-radius: 8px; font-weight: 600;">Kirim Komentar</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>