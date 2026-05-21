<?php
session_start();
require_once '../../config/config.php';
require_once '../../backend/koneksi.php';
require_once '../../backend/function.php';

$assetBase = isset($url) ? $url : '/';

if (!isset($_SESSION['username']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== 'admin') {
    header("Location: " . $assetBase . "page/login/index.php?error=Anda+harus+login+terlebih+dulu.");
    exit();
}

$flash = $_GET['message'] ?? '';
$error = $_GET['error'] ?? '';
$laporan_query = trim($_GET['laporan_q'] ?? '');
$user_query = trim($_GET['user_q'] ?? '');

function redirect_with($assetBase, $params) {
    $query = http_build_query($params);
    header("Location: " . $assetBase . "page/admin/index.php" . ($query ? "?$query" : ""));
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_status') {
        $conn->begin_transaction();
        try {
            $laporan_id = (int)($_POST['laporan_id'] ?? 0);
            $status = trim($_POST['status'] ?? '');
            if ($laporan_id <= 0 || $status === '') {
                redirect_with($assetBase, ['error' => 'Data status tidak valid.']);
            }

            $stmt = $conn->prepare("UPDATE laporan SET status = ? WHERE id = ?");        
            if (!$stmt) {
                redirect_with($assetBase, ['error' => 'Gagal memproses status.']);
            }
            $stmt->bind_param("si", $status, $laporan_id);
            if ($stmt->execute()) {
                $laporan_kode = '';
                $laporan_user_id = 0;
                $stmt2 = $conn->prepare("SELECT kode_laporan, user_id FROM laporan WHERE id = ?");
                $stmt2->bind_param("i", $laporan_id);
                $stmt2->execute();
                $result2 = $stmt2->get_result();
                if ($result2->num_rows > 0) {
                    $laporan_row = $result2->fetch_assoc();
                    $laporan_kode = $laporan_row['kode_laporan'];
                    $laporan_user_id = (int)$laporan_row['user_id'];
                }
                $notifikasi_pesan = "Status laporan dengan kode $laporan_kode telah diubah menjadi $status.";
                $stmt2 = $conn->prepare("INSERT INTO notifikasi (user_id, laporan_id, judul_notifikasi, pesan) VALUES (?, ?, 'Status Laporan Diubah', ?)");
                if ($laporan_user_id > 0) {
                    $stmt2->bind_param("iis", $laporan_user_id, $laporan_id, $notifikasi_pesan);
                    $stmt2->execute();
                }
                $conn->commit();
                redirect_with($assetBase, ['message' => 'Status laporan berhasil diperbarui.']);
            }
        } catch (Exception $e) {            
            $conn->rollback();
            redirect_with($assetBase, ['error' => 'Gagal memperbarui status laporan : ' . $e->getMessage()]);
        }
        
    }

    if ($action === 'delete_report') {
        $laporan_id = (int)($_POST['laporan_id'] ?? 0);
        if ($laporan_id <= 0) {
            redirect_with($assetBase, ['error' => 'Laporan tidak valid.']);
        }

        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare("DELETE FROM laporan_komentar WHERE laporan_id = ?");
            $stmt->bind_param("i", $laporan_id);
            $stmt->execute();

            $stmt = $conn->prepare("DELETE FROM laporan_like WHERE laporan_id = ?");
            $stmt->bind_param("i", $laporan_id);
            $stmt->execute();

            $stmt = $conn->prepare("DELETE FROM laporan_foto WHERE laporan_id = ?");
            $stmt->bind_param("i", $laporan_id);
            $stmt->execute();

            $stmt = $conn->prepare("DELETE FROM laporan WHERE id = ?");
            $stmt->bind_param("i", $laporan_id);
            $stmt->execute();

            $conn->commit();
            redirect_with($assetBase, ['message' => 'Laporan berhasil dihapus.']);
        } catch (Exception $e) {
            $conn->rollback();
            redirect_with($assetBase, ['error' => 'Gagal menghapus laporan : ' . $e->getMessage()]);
        }
    }

    if ($action === 'delete_user') {
        $user_id = (int)($_POST['user_id'] ?? 0);
        if ($user_id <= 0) {
            redirect_with($assetBase, ['error' => 'User tidak valid.']);
        }

        $conn->begin_transaction();
        try {
            $laporan_ids = [];
            $stmt = $conn->prepare("SELECT id FROM laporan WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $laporan_ids[] = (int)$row['id'];
            }

            if (!empty($laporan_ids)) {
                $placeholders = implode(',', array_fill(0, count($laporan_ids), '?'));
                $types = str_repeat('i', count($laporan_ids));

                $stmt = $conn->prepare("DELETE FROM laporan_komentar WHERE laporan_id IN ($placeholders)");
                $stmt->bind_param($types, ...$laporan_ids);
                $stmt->execute();

                $stmt = $conn->prepare("DELETE FROM laporan_like WHERE laporan_id IN ($placeholders)");
                $stmt->bind_param($types, ...$laporan_ids);
                $stmt->execute();

                $stmt = $conn->prepare("DELETE FROM laporan_foto WHERE laporan_id IN ($placeholders)");
                $stmt->bind_param($types, ...$laporan_ids);
                $stmt->execute();
            }

            $stmt = $conn->prepare("DELETE FROM laporan_komentar WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();

            $stmt = $conn->prepare("DELETE FROM laporan_like WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();

            $stmt = $conn->prepare("DELETE FROM laporan WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();

            $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();

            $conn->commit();
            redirect_with($assetBase, ['message' => 'User berhasil dihapus (banned).']);
        } catch (Exception $e) {
            $conn->rollback();
            redirect_with($assetBase, ['error' => 'Gagal menghapus user.']);
        }
    }
}

$total_users = 0;
$total_laporan = 0;

$result = $conn->query("SELECT COUNT(*) AS total FROM users");
if ($result) {
    $total_users = (int)$result->fetch_assoc()['total'];
}

$result = $conn->query("SELECT COUNT(*) AS total FROM laporan");
if ($result) {
    $total_laporan = (int)$result->fetch_assoc()['total'];
}


$laporans = [];
$laporan_sql = "SELECT l.id, l.kode_laporan, l.judul, l.status, l.created_at, u.nama_lengkap FROM laporan l JOIN users u ON l.user_id = u.id";
$laporan_params = [];
$laporan_types = "";
if ($laporan_query !== '') {
    $laporan_sql .= " WHERE l.kode_laporan LIKE ?";
    $laporan_params[] = '%' . $laporan_query . '%';
    $laporan_types .= "s";
}
$laporan_sql .= " ORDER BY l.created_at DESC LIMIT 20";
$stmt = $conn->prepare($laporan_sql);
if ($laporan_types !== '') {
    $stmt->bind_param($laporan_types, ...$laporan_params);
}
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $laporans[] = $row;
}

$users = [];
$user_sql = "SELECT id, username, nama_lengkap, email, created_at FROM users";
$user_params = [];
$user_types = "";
if ($user_query !== '') {
    $user_sql .= " WHERE username LIKE ?";
    $user_params[] = '%' . $user_query . '%';
    $user_types .= "s";
}
$user_sql .= " ORDER BY created_at DESC LIMIT 20";
$stmt = $conn->prepare($user_sql);
if ($user_types !== '') {
    $stmt->bind_param($user_types, ...$user_params);
}
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - EcoReport</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="<?= $assetBase ?>asset/style/theme.css">
    <link rel="stylesheet" href="<?= $assetBase ?>asset/style/main.css">
    <style>
        body { background-color: #f9f9f9; }
        .stat-card { background: #fff; border: 1px solid #F3F4F6; border-radius: 12px; padding: 20px; }
        .stat-title { color: #6B7280; font-weight: 600; font-size: 14px; }
        .stat-value { font-weight: 700; font-size: 24px; color: #111827; }
        .table thead th { white-space: nowrap; }
        .action-btns { display: flex; gap: 8px; flex-wrap: wrap; }
    </style>
</head>
<body>
    <?php include_once '../../component/nav.php'; ?>
    <main class="container py-4">
        <h1 class="mb-3" style="font-size: 24px; font-weight: 700;">Admin Panel</h1>

        <?php if ($flash): ?>
            <div class="alert alert-success"> <?= htmlspecialchars($flash) ?> </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"> <?= htmlspecialchars($error) ?> </div>
        <?php endif; ?>

        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <div class="stat-card">
                    <div class="stat-title">Total User</div>
                    <div class="stat-value"><?= $total_users ?></div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="stat-card">
                    <div class="stat-title">Total Laporan</div>
                    <div class="stat-value"><?= $total_laporan ?></div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header fw-bold">Kelola Laporan (20 terbaru)</div>
            <div class="card-body border-bottom">
                <form method="GET" class="row g-2 align-items-center">
                    <?php if ($user_query !== ''): ?>
                        <input type="hidden" name="user_q" value="<?= htmlspecialchars($user_query) ?>">
                    <?php endif; ?>
                    <div class="col-md-6">
                        <input type="text" name="laporan_q" value="<?= htmlspecialchars($laporan_query) ?>" class="form-control" placeholder="Cari kode laporan...">
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-primary">Cari</button>
                    </div>
                </form>
            </div>
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Kode</th>
                            <th>Judul</th>
                            <th>Pelapor</th>
                            <th>Status</th>
                            <th>Tanggal</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($laporans)): ?>
                            <tr><td colspan="6" class="text-center text-muted">Belum ada laporan.</td></tr>
                        <?php else: ?>
                            <?php foreach ($laporans as $laporan): ?>
                                <tr>
                                    <td><?= htmlspecialchars($laporan['kode_laporan']) ?></td>
                                    <td><?= htmlspecialchars($laporan['judul']) ?></td>
                                    <td><?= htmlspecialchars($laporan['nama_lengkap']) ?></td>
                                    <td>
                                        <form method="POST" class="d-flex gap-2">
                                            <input type="hidden" name="action" value="update_status">
                                            <input type="hidden" name="laporan_id" value="<?= (int)$laporan['id'] ?>">
                                            <select name="status" class="form-select form-select-sm" style="min-width: 140px;">
                                                <?php foreach (['BARU', 'DIPROSES', 'SELESAI', 'DITOLAK'] as $status): ?>
                                                    <option value="<?= $status ?>" <?= $laporan['status'] === $status ? 'selected' : '' ?>><?= $status ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button type="submit" class="btn btn-sm btn-primary">Simpan</button>
                                        </form>
                                    </td>
                                    <td><?= htmlspecialchars(date('d M Y', strtotime($laporan['created_at']))) ?></td>
                                    <td>
                                        <form method="POST" onsubmit="return confirm('Hapus laporan ini?');">
                                            <input type="hidden" name="action" value="delete_report">
                                            <input type="hidden" name="laporan_id" value="<?= (int)$laporan['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header fw-bold">Kelola User (20 terbaru)</div>
            <div class="card-body border-bottom">
                <form method="GET" class="row g-2 align-items-center">
                    <?php if ($laporan_query !== ''): ?>
                        <input type="hidden" name="laporan_q" value="<?= htmlspecialchars($laporan_query) ?>">
                    <?php endif; ?>
                    <div class="col-md-6">
                        <input type="text" name="user_q" value="<?= htmlspecialchars($user_query) ?>" class="form-control" placeholder="Cari username...">
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-primary">Cari</button>
                    </div>
                </form>
            </div>
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Daftar</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                            <tr><td colspan="5" class="text-center text-muted">Belum ada user.</td></tr>
                        <?php else: ?>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?= htmlspecialchars($user['nama_lengkap']) ?></td>
                                    <td><?= htmlspecialchars($user['username']) ?></td>
                                    <td><?= htmlspecialchars($user['email']) ?></td>
                                    <td><?= htmlspecialchars(date('d M Y', strtotime($user['created_at']))) ?></td>
                                    <td>
                                        <form method="POST" onsubmit="return confirm('Banned user ini dengan menghapus akun?');">
                                            <input type="hidden" name="action" value="delete_user">
                                            <input type="hidden" name="user_id" value="<?= (int)$user['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-danger">Banned</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>
