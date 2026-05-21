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

$user = get_logged_in_user('id, nama_lengkap, foto_profil');
if (!$user) {
	header("Location: " . $assetBase . "page/login/index.php");
	exit();
}

if (isset($_GET['read']) && isset($_GET['to'])) {
	$notif_id = (int)$_GET['read'];
	$target = $_GET['to'];
	if ($notif_id > 0) {
		$stmt_read = $conn->prepare("UPDATE notifikasi SET is_read = 1 WHERE id = ? AND user_id = ?");
		if ($stmt_read) {
			$stmt_read->bind_param("ii", $notif_id, $user['id']);
			$stmt_read->execute();
			$stmt_read->close();
		}
	}
	if ($target !== '') {
		header("Location: " . $target);
		exit();
	}
}

$notifikasi = [];
$notif_error = null;

try {
	$stmt = $conn->prepare("SELECT n.id, n.judul_notifikasi, n.pesan, n.is_read, n.created_at, l.kode_laporan, l.judul AS judul_laporan FROM notifikasi n LEFT JOIN laporan l ON n.laporan_id = l.id WHERE n.user_id = ? AND n.is_read = 0 ORDER BY n.created_at DESC");
	if ($stmt) {
		$stmt->bind_param("i", $user['id']);
		$stmt->execute();
		$result = $stmt->get_result();
		if ($result) {
			while ($row = $result->fetch_assoc()) {
				$notifikasi[] = $row;
			}
		}
		$stmt->close();
	} else {
		$notif_error = 'Gagal memuat notifikasi.';
	}
} catch (Exception $e) {
	$notif_error = 'Gagal memuat notifikasi.';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Notifikasi - EcoReport</title>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
	<link rel="stylesheet" href="<?= $assetBase ?>asset/style/theme.css">
	<link rel="stylesheet" href="<?= $assetBase ?>asset/style/main.css">
	<style>
		body { background-color: #f9f9f9; }
		.notif-container { max-width: 900px; }
		.notif-card {
			background: #fff;
			border-radius: 12px;
			padding: 18px 20px;
			border: 1px solid #F3F4F6;
			box-shadow: 0 1px 3px rgba(0,0,0,0.05);
		}
		.notif-card.unread { border-left: 4px solid var(--primary-color); background: #f7fbf8; }
		.notif-title { font-weight: 700; color: #111827; font-size: 16px; }
		.notif-message { color: #4B5563; font-size: 14px; line-height: 1.5; }
		.notif-time { color: #9CA3AF; font-size: 12px; }
		.badge-unread {
			background: var(--primary-color-subtle);
			color: var(--primary-color);
			font-weight: 600;
			font-size: 12px;
		}
	</style>
</head>
<body>
	<?php include_once '../../component/nav.php'; ?>
	<main class="container py-4 notif-container">
		<div class="d-flex align-items-center justify-content-between mb-4">
			<h3 class="mb-0 fw-bold">Notifikasi</h3>
			<span class="text-muted" style="font-size: 14px;"><?= count($notifikasi) ?> notifikasi</span>
		</div>

		<?php if ($notif_error): ?>
			<div class="alert alert-danger" style="font-size: 14px;">
				<?= htmlspecialchars($notif_error) ?>
			</div>
		<?php elseif (empty($notifikasi)): ?>
			<div class="text-center py-5 bg-white rounded-3 border" style="border-color: #F3F4F6;">
				<div class="mb-2">
					<i class="fas fa-bell" style="font-size: 32px; color: #9CA3AF;"></i>
				</div>
				<p class="mb-0 text-muted">Belum ada notifikasi untuk kamu.</p>
			</div>
		<?php else: ?>
			<div class="d-flex flex-column gap-3">
				<?php foreach ($notifikasi as $notif): ?>
					<div class="notif-card <?= ((int)$notif['is_read'] === 0) ? 'unread' : '' ?>">
						<div class="d-flex flex-wrap align-items-center gap-2 mb-2">
							<div class="notif-title"><?= htmlspecialchars($notif['judul_notifikasi']) ?></div>
							<?php if ((int)$notif['is_read'] === 0): ?>
								<span class="badge badge-unread">Baru</span>
							<?php endif; ?>
						</div>
						<div class="notif-message mb-2"><?= nl2br(htmlspecialchars($notif['pesan'])) ?></div>
						<div class="d-flex flex-wrap align-items-center gap-3">
							<span class="notif-time"><?= htmlspecialchars(time_ago($notif['created_at'])) ?></span>
							<?php if (!empty($notif['kode_laporan'])): ?>
								<?php $target = $assetBase . 'page/detail/index.php?kode=' . urlencode($notif['kode_laporan']); ?>
								<a class="btn btn-sm btn-outline-primary" href="<?= $assetBase ?>page/notifikasi/index.php?read=<?= (int)$notif['id'] ?>&to=<?= urlencode($target) ?>">
									Lihat Laporan
								</a>
							<?php endif; ?>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</main>
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>
