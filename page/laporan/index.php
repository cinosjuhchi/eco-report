<?php
session_start();
require_once '../../config/config.php';
require_once '../../backend/function.php';

$assetBase = isset($url) ? $url : '/';

if (!isset($_SESSION['username'])) {
    header("Location: " . $assetBase . "page/login/index.php?error=Anda+harus+login+terlebih+dulu.");
    exit();
}

$provinces = [];
$ctx = stream_context_create([
    'http' => [
        'timeout' => 10,
    ]
]);
$res = @file_get_contents('https://wilayah.id/api/provinces.json', false, $ctx);
if ($res !== false) {
    $decoded = json_decode($res, true);
    if (is_array($decoded) && isset($decoded['data']) && is_array($decoded['data'])) {
        $provinces = $decoded['data'];
    }
}

$error_message = isset($_GET['error']) ? htmlspecialchars($_GET['error']) : null;

if (isset($_POST['submit_laporan'])) {
    buatLaporan($_POST, $_FILES);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buat Laporan Baru - Ecoreport</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="<?php echo $assetBase; ?>asset/style/theme.css">
    <link rel="stylesheet" href="<?php echo $assetBase; ?>asset/style/main.css">
    <style>
        body { background-color: #f9f9f9; }
        .report-container {
            max-width: 800px;
            margin: 0 auto;
        }
        .report-card {
            background: white;
            border-radius: 12px;
            padding: 32px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            border: 1px solid #F3F4F6;
        }
        .form-label {
            font-weight: 600;
            color: #374151;
            font-size: 14px;
        }
        .form-control, .form-select {
            font-size: 15px;
            padding: 10px 16px;
            border-radius: 8px;
            border: 1px solid #D1D5DB;
        }
    </style>
</head>
<body>
    <?php include_once '../../component/nav.php'; ?>
    <main class="container py-5 report-container">
        <div class="report-card">
            <h2 class="fw-bold mb-4" style="color: #111827;">Buat Laporan Lingkungan</h2>
            
            <?php if ($error_message): ?>
                <div class="alert alert-danger" role="alert">
                    <?= $error_message ?>
                </div>
            <?php endif; ?>

            <form action="" method="post" enctype="multipart/form-data">
                <div class="mb-4">
                    <label for="formTitle" class="form-label">Judul Laporan</label>
                    <input type="text" class="form-control" id="formTitle" name="title" placeholder="Contoh: Tumpukan Sampah di Sungai Ciliwung" required>
                </div>

                <div class="mb-4">
                    <label for="formImage" class="form-label">Foto Laporan</label>
                    <input class="form-control" type="file" id="formImage" name="image" accept="image/*" required>
                    <div class="form-text">Unggah foto yang jelas mengenai kondisi lingkungan yang dilaporkan.</div>
                </div>

                <hr class="my-4">
                <h5 class="fw-bold mb-4" style="color: #111827;">Detail Lokasi</h5>

                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label for="formProvince" class="form-label">Provinsi</label>
                        <select class="form-select" id="formProvince" name="provinsi" required>
                            <option value="" selected disabled>Pilih provinsi</option>
                            <?php foreach ($provinces as $province): ?>
                                <option value="<?= htmlspecialchars($province['name'], ENT_QUOTES, 'UTF-8') ?>" data-id="<?= htmlspecialchars($province['code'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($province['name'], ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="formCity" class="form-label">Kota / Kabupaten</label>
                        <select class="form-select" id="formCity" name="kota" required disabled>
                            <option value="" selected>Pilih provinsi dulu</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="formDistrict" class="form-label">Kecamatan</label>
                        <select class="form-select" id="formDistrict" name="kecamatan" required disabled>
                            <option value="" selected>Pilih kota dulu</option>
                        </select>
                    </div>
                </div>

                <div class="mb-4">
                    <label for="formArea" class="form-label">Area Spesifik</label>
                    <textarea class="form-control" id="formArea" name="area" rows="2" placeholder="Contoh: Bantaran sungai dekat jembatan, seberang pasar" required></textarea>
                </div>

                <hr class="my-4">
                <h5 class="fw-bold mb-4" style="color: #111827;">Informasi Tambahan</h5>

                <div class="mb-4">
                    <label for="formDescription" class="form-label">Deskripsi Laporan</label>
                    <textarea class="form-control" id="formDescription" name="description" rows="5" placeholder="Jelaskan secara rinci kondisi yang Anda laporkan, dampaknya, dan sudah berapa lama kondisi ini terjadi." required></textarea>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-5">
                    <button type="button" onclick="window.history.back()" class="btn border fw-medium px-4" style="color: #4B5563; background: #F9FAFB;">Batal</button>
                    <button type="submit" name="submit_laporan" class="btn btn-primary fw-medium px-4">Kirim Laporan</button>
                </div>
            </form>
        </div>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script>
        const baseUrl = <?php echo json_encode($assetBase, JSON_UNESCAPED_SLASHES); ?>;
        const provinsiEl = document.getElementById('formProvince');
        const kotaEl = document.getElementById('formCity');
        const kecamatanEl = document.getElementById('formDistrict');

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
            const url = baseUrl + 'page/register/wilayah.php?type=' + type + '&' + idParam + '=' + encodeURIComponent(idValue);
            const res = await fetch(url);
            const json = await res.json();
            return (json && json.ok && Array.isArray(json.data)) ? json.data : [];
        }

        function fillSelect(select, items, placeholderText) {
            setSelectState(select, placeholderText, false);
            select.options[0].disabled = true;
            items.forEach(item => {
                const id = item.code || item.id;
                const name = item.name;
                if (!id || !name) return;
                const opt = document.createElement('option');
                opt.value = name;
                opt.dataset.id = id;
                opt.textContent = name;
                select.appendChild(opt);
            });
        }
        
        setSelectState(kotaEl, 'Pilih provinsi dulu', true);
        setSelectState(kecamatanEl, 'Pilih kota dulu', true);

        provinsiEl.addEventListener('change', async () => {
            const selectedOpt = provinsiEl.options[provinsiEl.selectedIndex];
            const provinceId = selectedOpt ? selectedOpt.dataset.id : null;
            setSelectState(kotaEl, 'Memuat kota...', true);
            setSelectState(kecamatanEl, 'Pilih kota dulu', true);
            if (!provinceId) {
                setSelectState(kotaEl, 'Pilih provinsi dulu', true);
                return;
            }
            const regencies = await getWilayah('regencies', 'province_id', provinceId);
            fillSelect(kotaEl, regencies, 'Pilih kota');
        });

        kotaEl.addEventListener('change', async () => {
            const selectedOpt = kotaEl.options[kotaEl.selectedIndex];
            const regencyId = selectedOpt ? selectedOpt.dataset.id : null;
            setSelectState(kecamatanEl, 'Memuat kecamatan...', true);
            if (!regencyId) {
                setSelectState(kecamatanEl, 'Pilih kota dulu', true);
                return;
            }
            const districts = await getWilayah('districts', 'regency_id', regencyId);
            fillSelect(kecamatanEl, districts, 'Pilih kecamatan');
        });
    </script>
</body>
</html>