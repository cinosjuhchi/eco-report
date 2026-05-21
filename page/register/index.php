<?php
session_start();
require_once '../../config/config.php';
require_once '../../backend/function.php';
$assetBase = isset($url) ? $url : '/';

if (isset($_SESSION['username'])) {
    header("Location: " . $assetBase . "/page/home/");
    exit();
}

$error_message = isset($_GET['error']) ? htmlspecialchars($_GET['error']) : null;

$provinces = [];
$ctx = stream_context_create(['http' => ['timeout' => 10]]);
$res = @file_get_contents('https://wilayah.id/api/provinces.json', false, $ctx);
if ($res !== false) {
    $decoded = json_decode($res, true);
    if (is_array($decoded) && isset($decoded['data']) && is_array($decoded['data'])) {
        $provinces = $decoded['data'];
    }
}

if (isset($_POST['submit_register'])) {
    $enkrip = [];
    foreach ($_POST as $key => $value) {
        $enkrip[$key] = htmlspecialchars($value);
    }
    register($enkrip);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Ecoreport</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="<?= $assetBase ?>asset/style/theme.css">
    <link rel="stylesheet" href="<?= $assetBase ?>asset/style/login.css">    
</head>
<body>
    <main>
        <section class="banner-login">
            <div class="d-flex justify-content-between flex-column h-100">
                <div class="d-flex gap-2 align-items-center">
                    <img src="<?= $assetBase ?>asset/image/logo.png" alt="Ecoreport Logo" style="height: 40px;">
                    <h3 class="banner-header">Ecoreport</h3>
                </div>
                <div class="banner-description">
                    <h3>"Suaramu penting untuk dunia yang lebih baik."</h3>
                    <p>Laporkan, pantau dan bantu tingkatkan kualitas lingkungan!</p>
                </div>
            </div>
        </section>
        <section class="content">
            <div class="form-login">
                <div class="greeting-text">
                    <h2 class="text-center text-header">Buat Akun Baru</h2>
                    <p class="text-center text-secondary">Lengkapi data untuk mendaftar ke Ecoreport</p>
                </div>

                <?php if ($error_message): ?>
                    <div class="alert alert-danger" role="alert">
                        <?= $error_message ?>
                    </div>
                <?php endif; ?>

                <form action="" method="post">
                    <div class="input-label">
                        <label for="formUsername" class="form-label">Username</label>
                        <input type="text" class="form-control" id="formUsername" name="username" placeholder="username" autocomplete="username" required value="<?= $_POST['username'] ?? '' ?>">
                    </div>
                    <div class="input-label">
                        <label for="formEmail" class="form-label">Email</label>
                        <input type="email" class="form-control" id="formEmail" name="email" placeholder="name@example.com" autocomplete="email" required value="<?= $_POST['email'] ?? '' ?>">
                    </div>

                    <div class="input-label">
                        <label for="formPhone" class="form-label">Nomor Telepon</label>
                        <div class="input-group">
                            <span class="input-group-text">+62</span>
                            <input type="tel" class="form-control" id="formPhone" name="code_phone" placeholder="81234567890" inputmode="numeric" autocomplete="tel-national" required value="<?= $_POST['code_phone'] ?? '' ?>">
                        </div>
                        <input type="hidden" id="formPhoneFull" name="phone" value="<?= $_POST['phone'] ?? '' ?>">
                        <div class="form-text">Contoh: 81234567890 (tanpa 0 di depan).</div>
                    </div>

                    <div class="input-label">
                        <label for="formFullName" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="formFullName" name="full_name" placeholder="Nama lengkap" autocomplete="name" required value="<?= $_POST['full_name'] ?? '' ?>">
                    </div>

                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <div class="input-label mb-0">
                                <label for="formGender" class="form-label">Gender</label>
                                <select class="form-select" id="formGender" name="gender" required>
                                    <option value="" selected disabled>Pilih gender</option>
                                    <option value="Laki-laki" <?= (($_POST['gender'] ?? '') === 'Laki-laki') ? 'selected' : '' ?>>Laki-laki</option>
                                    <option value="Perempuan" <?= (($_POST['gender'] ?? '') === 'Perempuan') ? 'selected' : '' ?>>Perempuan</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="input-label mb-0">
                                <label for="formBirthDate" class="form-label">Tanggal Lahir</label>
                                <input type="date" class="form-control" id="formBirthDate" name="birth_date" required value="<?= $_POST['birth_date'] ?? '' ?>">
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mt-3">
                        <div class="col-12 col-md-6">
                            <div class="input-label mb-0">
                                <label for="formProvince" class="form-label">Provinsi</label>
                                <select class="form-select" id="formProvince" name="provinsi" required>
                                    <option value="" selected disabled>Pilih provinsi</option>
                                    <?php foreach ($provinces as $province):
                                        $code = (string)($province['code'] ?? $province['id'] ?? '');
                                        $name = (string)($province['name'] ?? '');
                                        if ($code === '' || $name === '') continue;
                                    ?>
                                        <option value="<?= htmlspecialchars($name) ?>" data-id="<?= htmlspecialchars($code) ?>" <?= (($_POST['provinsi'] ?? '') === $name) ? 'selected' : '' ?>><?= htmlspecialchars($name) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="input-label mb-0">
                                <label for="formCity" class="form-label">Kota</label>
                                <select class="form-select" id="formCity" name="kota" required disabled>
                                    <option value="" selected>Pilih provinsi dulu</option>
                                </select>
                                <noscript>
                                    <div class="mt-2">
                                        <input type="text" class="form-control" name="kota_text" placeholder="Kota" autocomplete="address-level2">
                                    </div>
                                </noscript>
                            </div>
                        </div>
                    </div>
                    <div class="row g-3 mt-3">
                        <div class="col-12">
                            <div class="input-label mb-0">
                                <label for="formDistrict" class="form-label">Kecamatan</label>
                                <select class="form-select" id="formDistrict" name="kecamatan" required disabled>
                                    <option value="" selected>Pilih kota dulu</option>
                                </select>
                                <noscript>
                                    <div class="mt-2">
                                        <input type="text" class="form-control" name="kecamatan_text" placeholder="Kecamatan">
                                    </div>
                                </noscript>
                            </div>
                        </div>
                    </div>

                    <div class="input-label mt-3">
                        <label for="formAddress" class="form-label">Alamat</label>
                        <textarea class="form-control" id="formAddress" name="alamat" rows="3" placeholder="Alamat lengkap" autocomplete="street-address" required><?= $_POST['alamat'] ?? '' ?></textarea>
                    </div>

                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <div class="input-label mb-0">
                                <label for="formPassword" class="form-label">Password</label>
                                <input type="password" class="form-control" id="formPassword" name="password" placeholder="Password" autocomplete="new-password" required>
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="input-label mb-0">
                                <label for="formPassword2" class="form-label">Retype Password</label>
                                <input type="password" class="form-control" id="formPassword2" name="password2" placeholder="Ulangi password" autocomplete="new-password" required>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <a href="<?= $assetBase ?>page/login/" class="text-primary text-decoration-none small">Sudah punya akun? Masuk</a>
                        <button type="submit" name="submit_register" class="btn btn-primary px-4">Daftar</button>
                    </div>
                </form>
            </div>
        </section>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script>
        const baseUrl = <?= json_encode($assetBase, JSON_UNESCAPED_SLASHES) ?>;
        const provinsiEl = document.getElementById('formProvince');
        const kotaEl = document.getElementById('formCity');
        const kecamatanEl = document.getElementById('formDistrict');
        const phoneLocalEl = document.getElementById('formPhone');
        const phoneFullEl = document.getElementById('formPhoneFull');

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
            } catch (error) {
                console.error("Gagal fetch wilayah:", error);
                return [];
            }
        }

        function fillSelect(select, items, placeholderText, selectedValue = null) {
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
                if (selectedValue && selectedValue === name) opt.selected = true;
                select.appendChild(opt);
            });
        }

        function syncPhone() {
            if (!phoneLocalEl || !phoneFullEl) return;
            let digits = phoneLocalEl.value.replace(/\D/g, '');
            if (digits.startsWith('0')) digits = digits.slice(1);
            phoneLocalEl.value = digits;
            phoneFullEl.value = digits ? ('+62' + digits) : '';
        }

        document.addEventListener('DOMContentLoaded', () => {
            const initialProvince = "<?= $_POST['provinsi'] ?? '' ?>";
            const initialCity = "<?= $_POST['kota'] ?? '' ?>";
            const initialDistrict = "<?= $_POST['kecamatan'] ?? '' ?>";

            setSelectState(kotaEl, 'Pilih provinsi dulu', true);
            setSelectState(kecamatanEl, 'Pilih kota dulu', true);

            if (initialProvince) {
                provinsiEl.dispatchEvent(new CustomEvent('change', { detail: { city: initialCity, district: initialDistrict } }));
            }

            provinsiEl.addEventListener('change', async (e) => {
                const selectedOpt = provinsiEl.options[provinsiEl.selectedIndex];
                const provinceId = selectedOpt ? selectedOpt.dataset.id : null;
                const cityToSelect = e.detail?.city;
                const districtToSelect = e.detail?.district;

                setSelectState(kotaEl, 'Memuat kota...', true);
                setSelectState(kecamatanEl, 'Pilih kota dulu', true);
                if (!provinceId) {
                    setSelectState(kotaEl, 'Pilih provinsi dulu', true);
                    return;
                }
                const regencies = await getWilayah('regencies', 'province_id', provinceId);
                fillSelect(kotaEl, regencies, 'Pilih kota', cityToSelect);

                if (cityToSelect) {
                    kotaEl.dispatchEvent(new CustomEvent('change', { detail: { district: districtToSelect } }));
                }
            });

            kotaEl.addEventListener('change', async (e) => {
                const selectedOpt = kotaEl.options[kotaEl.selectedIndex];
                const regencyId = selectedOpt ? selectedOpt.dataset.id : null;
                const districtToSelect = e.detail?.district;

                setSelectState(kecamatanEl, 'Memuat kecamatan...', true);
                if (!regencyId) {
                    setSelectState(kecamatanEl, 'Pilih kota dulu', true);
                    return;
                }
                const districts = await getWilayah('districts', 'regency_id', regencyId);
                fillSelect(kecamatanEl, districts, 'Pilih kecamatan', districtToSelect);
            });

            phoneLocalEl?.addEventListener('input', syncPhone);
            syncPhone();
        });
    </script>
</body>
</html>