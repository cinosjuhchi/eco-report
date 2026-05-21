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

$user = get_logged_in_user('id, username, nama_lengkap, email, nomor_telepon, gender, tanggal_lahir, provinsi, kota_kabupaten, kecamatan, alamat_lengkap, foto_profil');

if (!$user) {
    header("Location: " . $assetBase . "page/login/index.php");
    exit();
}


function format_tanggal($date) {
    if (empty($date)) return '-';
    $bulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    $d = date('j', strtotime($date));
    $m = (int)date('n', strtotime($date));
    $y = date('Y', strtotime($date));
    return "$d {$bulan[$m]} $y";
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Saya - Ecoreport</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="<?= $assetBase ?>asset/style/theme.css">
    <link rel="stylesheet" href="<?= $assetBase ?>asset/style/main.css">
    <style>
        body { background-color: #f9f9f9; }
        .profile-container { max-width: 800px; margin: 0 auto; }
        .profile-card {
            background: white;
            border-radius: 12px;
            padding: 32px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            border: 1px solid #F3F4F6;
        }
        .profile-header { display: flex; align-items: center; gap: 24px; margin-bottom: 24px; width: 100%; min-width: 0; }
        .profile-text { min-width: 0; flex: 1 1 auto; }
        .profile-avatar { width: 100px; height: 100px; border-radius: 50%; object-fit: cover; border: 3px solid #E5E7EB; flex-shrink: 0; }
        .profile-name { font-size: 14px; font-weight: 700; color: #111827; margin-bottom: 4px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: block; }
        .profile-username { font-size: 10px; color: #6B7280; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: block; }
        @media (max-width: 576px) {
            .profile-header { gap: 16px; }
            .profile-avatar { width: 72px; height: 72px; }
        }
        .info-group { padding: 24px 0; border-bottom: 1px solid #E5E7EB; }
        .info-group:last-child { border-bottom: none; padding-bottom: 0; }
        .info-label { font-weight: 600; color: #374151; font-size: 14px; margin-bottom: 4px; }
        .info-value { font-size: 16px; color: #1F2937; }
        .profile-edit-btn { width: 100%; }
        @media (min-width: 768px) {
            .profile-edit-btn { width: auto; }
        }
    </style>
</head>
<body>
    <?php include_once '../../component/nav.php'; ?>
    <main class="container py-5 profile-container">
        <div class="profile-card">
            <div class="d-flex flex-column flex-md-row align-items-start gap-3">
                <div class="profile-header">
                    <img src="<?= avatar_url($user['nama_lengkap'], $user['foto_profil'], $assetBase) ?>" alt="Avatar" class="profile-avatar">
                    <div class="profile-text">
                        <h2 class="profile-name"><?= htmlspecialchars($user['nama_lengkap']) ?></h2>
                        <p class="profile-username">@<?= htmlspecialchars($user['username']) ?></p>
                    </div>
                </div>
                <a href="<?= $assetBase ?>page/profile/edit.php" class="btn btn-outline-primary fw-medium px-4 profile-edit-btn ms-md-auto">Edit Profil</a>
            </div>

            <div class="info-group">
                <h5 class="fw-bold mb-4" style="color: #111827;">Informasi Pribadi</h5>
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="info-label">Nama Lengkap</div>
                        <div class="info-value"><?= htmlspecialchars($user['nama_lengkap']) ?></div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-label">Email</div>
                        <div class="info-value"><?= htmlspecialchars($user['email']) ?></div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-label">Nomor Telepon</div>
                        <div class="info-value"><?= htmlspecialchars($user['nomor_telepon'] ?? '-') ?></div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-label">Gender</div>
                        <div class="info-value"><?= htmlspecialchars($user['gender'] ?? '-') ?></div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-label">Tanggal Lahir</div>
                        <div class="info-value"><?= format_tanggal($user['tanggal_lahir']) ?></div>
                    </div>
                </div>
            </div>

            <div class="info-group pt-4">
                <h5 class="fw-bold mb-4" style="color: #111827;">Informasi Alamat</h5>
                <div class="row g-4">
                    <div class="col-md-4">
                        <div class="info-label">Provinsi</div>
                        <div class="info-value"><?= htmlspecialchars($user['provinsi'] ?? '-') ?></div>
                    </div>
                    <div class="col-md-4">
                        <div class="info-label">Kota / Kabupaten</div>
                        <div class="info-value"><?= htmlspecialchars($user['kota_kabupaten'] ?? '-') ?></div>
                    </div>
                    <div class="col-md-4">
                        <div class="info-label">Kecamatan</div>
                        <div class="info-value"><?= htmlspecialchars($user['kecamatan'] ?? '-') ?></div>
                    </div>
                    <div class="col-12">
                        <div class="info-label">Alamat Lengkap</div>
                        <div class="info-value"><?= htmlspecialchars($user['alamat_lengkap'] ?? '-') ?></div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>