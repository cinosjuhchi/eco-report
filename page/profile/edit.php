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

// Fetch data user
$user = get_logged_in_user('id, username, nama_lengkap, email, nomor_telepon, gender, tanggal_lahir, provinsi, kota_kabupaten, kecamatan, alamat_lengkap, foto_profil');

if (!$user) {
    header("Location: " . $assetBase . "page/login/index.php");
    exit();
}

$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name  = trim($_POST['full_name'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $phone      = trim($_POST['phone'] ?? '');
    $gender     = $_POST['gender'] ?? '';
    $birth_date = $_POST['birth_date'] ?? '';
    $provinsi   = $_POST['provinsi'] ?? '';
    $kota       = $_POST['kota'] ?? '';
    $kecamatan  = $_POST['kecamatan'] ?? '';
    $alamat     = trim($_POST['alamat'] ?? '');

    // Handle upload foto
    $foto_profil = $user['foto_profil'];
    if (isset($_FILES['foto_profil'])) {
        $ext = pathinfo($_FILES['foto_profil']['name'], PATHINFO_EXTENSION);
        $filename_only = uniqid('pp_', true) . '.' . $ext;
        $upload_dir = __DIR__ . '/../../public/uploads/photo-profile/';
        $upload_path = $upload_dir . $filename_only;
        $db_path = 'photo-profile/' . $filename_only;

        if (move_uploaded_file($_FILES['foto_profil']['tmp_name'], $upload_path)) {
            if (!empty($user['foto_profil'])) {
                $old_path = __DIR__ . '/../../public/uploads/' . $user['foto_profil'];
                if (file_exists($old_path)) {
                    unlink($old_path);
                }
            }
            $foto_profil = $db_path;
        } else {
            $error = "Gagal mengupload foto profil.";
        }
    }

    if (!$error) {
        $stmt = $conn->prepare("UPDATE users SET nama_lengkap=?, email=?, nomor_telepon=?, gender=?, tanggal_lahir=?, provinsi=?, kota_kabupaten=?, kecamatan=?, alamat_lengkap=?, foto_profil=? WHERE username=?");
        $stmt->bind_param("sssssssssss", $full_name, $email, $phone, $gender, $birth_date, $provinsi, $kota, $kecamatan, $alamat, $foto_profil, $_SESSION['username']);

        if ($stmt->execute()) {
            // Refresh data user
            $user = get_logged_in_user('id, username, nama_lengkap, email, nomor_telepon, gender, tanggal_lahir, provinsi, kota_kabupaten, kecamatan, alamat_lengkap, foto_profil');
            $success = "Profil berhasil diperbarui.";
        } else {
            $error = "Gagal menyimpan perubahan.";
        }
    }
}

$provinces = [];
$ctx = stream_context_create(['http' => ['timeout' => 10]]);
$res = @file_get_contents('https://wilayah.id/api/provinces.json', false, $ctx);
if ($res !== false) {
    $decoded = json_decode($res, true);
    if (is_array($decoded) && isset($decoded['data'])) {
        $provinces = $decoded['data'];
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profil - Ecoreport</title>
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
        .profile-header {
            display: flex;
            align-items: center;
            gap: 24px;
            margin-bottom: 32px;
            padding-bottom: 24px;
            border-bottom: 1px solid #E5E7EB;
        }
        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #E5E7EB;
            cursor: pointer;
        }
        .profile-name { font-size: 24px; font-weight: 700; color: #111827; margin-bottom: 4px; }
        .profile-username { font-size: 16px; color: #6B7280; }
        .form-label { font-weight: 600; color: #374151; font-size: 14px; }
        .form-control, .form-select {
            font-size: 15px;
            padding: 10px 16px;
            border-radius: 8px;
            border: 1px solid #D1D5DB;
        }
        .form-control[readonly] { background-color: #F9FAFB; }
    </style>
</head>
<body>
    <?php include_once '../../component/nav.php'; ?>
    <main class="container py-5 profile-container">
        <div class="profile-card">

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <form action="" method="post" enctype="multipart/form-data">
                <div class="profile-header">
                    <div>
                        <img src="<?= avatar_url($user['nama_lengkap'] ?? 'User', $user['foto_profil'] ?? '', $assetBase) ?>"
                             alt="Avatar"
                             class="profile-avatar"
                             id="avatarPreview"
                             onclick="document.getElementById('foto_profil').click()">
                        <input type="file" name="foto_profil" id="foto_profil" class="d-none" accept="image/*">
                    </div>
                    <div>
                        <h2 class="profile-name"><?= htmlspecialchars($user['nama_lengkap']) ?></h2>
                        <p class="profile-username">@<?= htmlspecialchars($user['username']) ?></p>
                        <button type="button" class="btn btn-outline-primary btn-sm mt-2 fw-medium"
                                onclick="document.getElementById('foto_profil').click()">Ubah Foto Profil</button>
                    </div>
                </div>

                <h5 class="fw-bold mb-4" style="color: #111827;">Informasi Pribadi</h5>
                <div class="row g-4 mb-4">
                    <div class="col-md-6">
                        <label class="form-label">Nama Lengkap</label>
                        <input type="text" class="form-control" name="full_name" value="<?= htmlspecialchars($user['nama_lengkap']) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Username</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($user['username']) ?>" readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Nomor Telepon</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted">+62</span>
                            <input type="text" class="form-control" name="phone" value="<?= htmlspecialchars(ltrim($user['nomor_telepon'] ?? '', '+62')) ?>">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Gender</label>
                        <select class="form-select" name="gender">
                            <option value="Laki-laki" <?= ($user['gender'] === 'Laki-laki') ? 'selected' : '' ?>>Laki-laki</option>
                            <option value="Perempuan" <?= ($user['gender'] === 'Perempuan') ? 'selected' : '' ?>>Perempuan</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Tanggal Lahir</label>
                        <input type="date" class="form-control" name="birth_date" value="<?= htmlspecialchars($user['tanggal_lahir'] ?? '') ?>">
                    </div>
                </div>

                <hr class="my-4" style="border-color: #E5E7EB;">
                <h5 class="fw-bold mb-4" style="color: #111827;">Informasi Alamat</h5>
                <div class="row g-4 mb-4">
                    <div class="col-md-4">
                        <label for="formProvince" class="form-label">Provinsi</label>
                        <select class="form-select" id="formProvince" name="provinsi">
                            <option value="" disabled>Pilih provinsi</option>
                            <?php foreach ($provinces as $province):
                                $code = (string)($province['code'] ?? $province['id'] ?? '');
                                $name = (string)($province['name'] ?? '');
                                if ($code === '' || $name === '') continue;
                            ?>
                                <option value="<?= htmlspecialchars($code) ?>" <?= ($user['provinsi'] == $code) ? 'selected' : '' ?>><?= htmlspecialchars($name) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="formCity" class="form-label">Kota / Kabupaten</label>
                        <select class="form-select" id="formCity" name="kota" disabled>
                            <option value="">Pilih provinsi dulu</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="formDistrict" class="form-label">Kecamatan</label>
                        <select class="form-select" id="formDistrict" name="kecamatan" disabled>
                            <option value="">Pilih kota dulu</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Alamat Lengkap</label>
                        <textarea class="form-control" name="alamat" rows="3"><?= htmlspecialchars($user['alamat_lengkap'] ?? '') ?></textarea>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-5">
                    <a href="<?= $assetBase ?>page/profile/" class="btn border fw-medium px-4" style="color: #4B5563; background: #F9FAFB;">Batal</a>
                    <button type="submit" class="btn btn-primary fw-medium px-4">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script>
        const baseUrl = <?= json_encode($assetBase, JSON_UNESCAPED_SLASHES) ?>;
        const provinsiEl = document.getElementById('formProvince');
        const kotaEl = document.getElementById('formCity');
        const kecamatanEl = document.getElementById('formDistrict');
        const savedKota = <?= json_encode($user['kota_kabupaten'] ?? '') ?>;
        const savedKecamatan = <?= json_encode($user['kecamatan'] ?? '') ?>;

        function setSelectState(select, placeholderText, disabled) {
            select.innerHTML = '';
            const opt = document.createElement('option');
            opt.value = '';
            opt.textContent = placeholderText;
            opt.selected = true;
            select.appendChild(opt);
            select.disabled = disabled;
        }

        async function getWilayah(type, idParam, idValue) {
            try {
                const url = baseUrl + 'page/register/wilayah.php?type=' + type + '&' + idParam + '=' + encodeURIComponent(idValue);
                const res = await fetch(url);
                const json = await res.json();
                return (json && json.ok && Array.isArray(json.data)) ? json.data : [];
            } catch (e) {
                return [];
            }
        }

        function fillSelect(select, items, placeholderText, selectedValue = null) {
            setSelectState(select, placeholderText, false);
            select.options[0].disabled = true;
            items.forEach(item => {
                const value = item.code || item.id;
                const name = item.name;
                if (!value || !name) return;
                const opt = document.createElement('option');
                opt.value = value;
                opt.textContent = name;
                if (selectedValue && selectedValue == value) opt.selected = true;
                select.appendChild(opt);
            });
        }

        provinsiEl.addEventListener('change', async () => {
            setSelectState(kotaEl, 'Memuat kota...', true);
            setSelectState(kecamatanEl, 'Pilih kota dulu', true);
            const regencies = await getWilayah('regencies', 'province_id', provinsiEl.value);
            fillSelect(kotaEl, regencies, 'Pilih kota');
        });

        kotaEl.addEventListener('change', async () => {
            setSelectState(kecamatanEl, 'Memuat kecamatan...', true);
            const districts = await getWilayah('districts', 'regency_id', kotaEl.value);
            fillSelect(kecamatanEl, districts, 'Pilih kecamatan');
        });

        // Load kota & kecamatan sesuai data user yang tersimpan
        document.addEventListener('DOMContentLoaded', async () => {
            if (provinsiEl.value) {
                const regencies = await getWilayah('regencies', 'province_id', provinsiEl.value);
                fillSelect(kotaEl, regencies, 'Pilih kota', savedKota);
                if (savedKota) {
                    const districts = await getWilayah('districts', 'regency_id', savedKota);
                    fillSelect(kecamatanEl, districts, 'Pilih kecamatan', savedKecamatan);
                }
            }
        });

        // Preview foto sebelum upload
        document.getElementById('foto_profil').addEventListener('change', function () {
            const file = this.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = e => document.getElementById('avatarPreview').src = e.target.result;
            reader.readAsDataURL(file);
        });
    </script>
</body>
</html>