<?php
require 'koneksi.php';
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$title = 'Eco Report';
$url = $scheme . '://' . $host . '/';


function get_logged_in_user($fields = 'id, nama_lengkap, foto_profil') {
	if (empty($_SESSION['username'])) {
		return null;
	}

	global $conn;

	try {
		$fields = trim((string)$fields);
		if ($fields === '') {
			$fields = 'id, nama_lengkap, foto_profil';
		}

		$query = "SELECT $fields FROM users WHERE username = ?";
		$stmt_user = $conn->prepare($query);
		if (!$stmt_user) {
			return null;
		}

		$stmt_user->bind_param("s", $_SESSION['username']);
		$stmt_user->execute();
		$result = $stmt_user->get_result();

		return $result ? $result->fetch_assoc() : null;
	} catch (Exception $e) {
		return null;
	}
}

function time_ago($datetime) {
	$now = new DateTime();
	$ago = new DateTime($datetime);
	$diff = $now->diff($ago);
	if ($diff->y > 0) return $diff->y . " tahun yang lalu";
	if ($diff->m > 0) return $diff->m . " bulan yang lalu";
	if ($diff->d > 0) return $diff->d . " hari yang lalu";
	if ($diff->h > 0) return $diff->h . " jam yang lalu";
	if ($diff->i > 0) return $diff->i . " menit yang lalu";
	return "Baru saja";
}

function avatar_url($nama, $foto_profil, $assetBase) {
	if (!empty($foto_profil)) return $assetBase . 'public/uploads/' . $foto_profil;
	return "https://ui-avatars.com/api/?name=" . $nama . "&background=2E7D32&color=fff";
}


function register($data) {
	global $conn, $url;
    $conn->begin_transaction();
	try {
		// Ambil dan bersihkan data dari form
		$username = strtolower(trim($data['username']));
		$nama_lengkap = trim($data['full_name']);
		$email = strtolower(trim($data['email']));
		$password = $data['password'];
		$password2 = $data['password2'];
		$nomor_telepon = trim((string)$data['phone']);
		$gender = $data['gender'];
		$tanggal_lahir = $data['birth_date'];
		$provinsi = $data['provinsi'];
		$kota_kabupaten = $data['kota'];
		$kecamatan = $data['kecamatan'];
		$alamat_lengkap = trim($data['alamat']);

		// Cek password harus sama
		if ($password !== $password2) {
			header("Location: " . $url . "page/register/index.php?error=Password+dan+konfirmasi+tidak+cocok.");
			exit();
		}

		// Cek username atau email sudah ada belum
		$checkUserSql = "SELECT username, email FROM users WHERE username = ? OR email = ? LIMIT 1";
		$stmt_check = $conn->prepare($checkUserSql);
		$stmt_check->bind_param("ss", $username, $email);
		$stmt_check->execute();
		$result = $stmt_check->get_result();

		if ($result->num_rows > 0) {
			$existingUser = $result->fetch_assoc();
			if (strtolower($existingUser['username']) === $username) {
				header("Location: " . $url . "page/register/index.php?error=Username+'$username'+sudah+dipakai,+coba+yang+lain.");
				exit();
			}
			if (strtolower($existingUser['email']) === $email) {
				header("Location: " . $url . "page/register/index.php?error=Email+ini+sudah+terdaftar,+coba+login.");
				exit();
			}
		}

		// Hash password sebelum disimpan
		$hashed_password = password_hash($password, PASSWORD_DEFAULT);

		// Normalisasi nomor telepon agar memakai prefix +62
		if ($nomor_telepon !== '') {
			$digits = preg_replace('/\D+/', '', $nomor_telepon);
			if (strpos($digits, '62') === 0) {
				$digits = substr($digits, 2);
			} elseif (strpos($digits, '0') === 0) {
				$digits = ltrim($digits, '0');
			}
			if ($digits !== '') {
				$nomor_telepon = '+62' . $digits;
			}
		}

		// Query buat insert user baru
		$insertQuery = "INSERT INTO users (
							username, nama_lengkap, email, password, nomor_telepon, 
							gender, tanggal_lahir, provinsi, kota_kabupaten, kecamatan, alamat_lengkap
						) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
		$stmt_insert = $conn->prepare($insertQuery);
		$stmt_insert->bind_param(
			"sssssssssss",
			$username,
			$nama_lengkap,
			$email,
			$hashed_password,
			$nomor_telepon,
			$gender,
			$tanggal_lahir,
			$provinsi,
			$kota_kabupaten,
			$kecamatan,
			$alamat_lengkap
		);

		if ($stmt_insert->execute()) {
			// Kalo berhasil, redirect ke login
            $conn->commit();
			header("Location: " . $url . "page/login/index.php?message=Registrasi+berhasil!+Silakan+login.");
			exit();
		}

		// Kalo gagal, balik ke register dengan pesan error
		$conn->rollback();
		header("Location: " . $url . "page/register/index.php?error=Gagal+melakukan+registrasi.+Error:+" . $conn->error);
		exit();
	} catch (Exception $e) {
		$conn->rollback();
		header("Location: " . $url . "page/register/index.php?error=Gagal+melakukan+registrasi.");
		exit();
	}
}

function login($data) {
	global $conn, $url;

	try {
		$email = strtolower(trim($data['email']));
		$password = $data['password'];

		$sql = "SELECT id, username, email, password, nama_lengkap, foto_profil, role FROM users WHERE email = ? LIMIT 1";
		$stmt = $conn->prepare($sql);
		$stmt->bind_param("s", $email);
		$stmt->execute();
		$result = $stmt->get_result();

		if ($result->num_rows > 0) {
			$user = $result->fetch_assoc();
			if (password_verify($password, $user['password'])) {
				// Set session
				$_SESSION['user_id'] = $user['id'];
				$_SESSION['username'] = $user['username'];
				$_SESSION['email'] = $user['email'];
				$_SESSION['nama_lengkap'] = $user['nama_lengkap'] ?? '';
				$_SESSION['foto_profil'] = $user['foto_profil'] ?? '';
				$_SESSION['is_admin'] = $user['role'] === 'admin' ? 'admin' : 'user';
				header("Location: " . $url . "page/home/");
				exit();
			}
			header("Location: " . $url . "page/login/index.php?error=Password+salah.");
			exit();
		}

		header("Location: " . $url . "page/login/index.php?error=Email+tidak+ditemukan.");
		exit();
	} catch (Exception $e) {
		header("Location: " . $url . "page/login/index.php?error=Login+gagal:+" . $e->getMessage());
		exit();
	}
}

function buatLaporan($data, $file) {
	global $conn, $url;
	try {
		$user_id = $_SESSION['user_id'];
		$judul = trim($data['title']);
		$provinsi = trim($data['provinsi']);
		$kota = trim($data['kota']);
		$kecamatan = trim($data['kecamatan']);
		$area = trim($data['area']);
		$deskripsi = trim($data['description']);

		// Generate random string like NNNN
		$random_number = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
		$checkKodeQuery = "SELECT id FROM laporan ORDER BY id DESC LIMIT 1";
		$stmtKode = $conn->prepare($checkKodeQuery);
		$stmtKode->execute();
		$resultKode = $stmtKode->get_result();
        
		// Create new id based on result Kode to ensure unique prefix
		if ($resultKode->num_rows > 0) {
		  $rowKode = $resultKode->fetch_assoc();
		  $newId = $rowKode['id'] + 1;
		} else {
		  $newId = 1;
		}

		$kode_laporan = "RECO-" . $newId . $random_number;

		$conn->begin_transaction();

		try {
			$insertLaporan = "INSERT INTO laporan (user_id, kode_laporan, judul, provinsi, kota_kabupaten, kecamatan, area_spesifik, deskripsi_laporan) 
							  VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
			$stmtLaporan = $conn->prepare($insertLaporan);
			$stmtLaporan->bind_param(
				"isssssss",
				$user_id,
				$kode_laporan,
				$judul,
				$provinsi,
				$kota,
				$kecamatan,
				$area,
				$deskripsi
			);

			if (!$stmtLaporan->execute()) {
				throw new Exception("Gagal menyimpan data laporan.");
			}

			$laporan_id = $conn->insert_id;

			// Proses Upload Gambar
			$target_dir = __DIR__ . '/../public/uploads/reports/';
            
			$imageFileType = strtolower(pathinfo($file['image']['name'], PATHINFO_EXTENSION));
			$newFileName = strtolower(str_replace('-', '_', $kode_laporan)) . '_' . time() . '.' . $imageFileType;
			$target_file = $target_dir . $newFileName;
			$db_path = 'public/uploads/reports/' . $newFileName;

			$check = getimagesize($file['image']['tmp_name']);
			if ($check === false) {
			   throw new Exception("File bukan gambar.");
			}

			if ($file['image']['size'] > 5000000) { // Limit 5MB
				throw new Exception("Ukuran gambar terlalu besar.");
			}

			if (!in_array($imageFileType, ['jpg', 'jpeg', 'png', 'gif'])) {
				throw new Exception("Format gambar tidak diizinkan.");
			}

			if (!move_uploaded_file($file['image']['tmp_name'], $target_file)) {
				throw new Exception("Gagal mengunggah gambar.");
			}

			// Insert ke tabel laporan_foto
			$insertFoto = "INSERT INTO laporan_foto (laporan_id, foto_url) VALUES (?, ?)";
			$stmtFoto = $conn->prepare($insertFoto);
			$stmtFoto->bind_param("is", $laporan_id, $db_path);
			if (!$stmtFoto->execute()) {
				 throw new Exception("Gagal menyimpan data foto.");
			}

			$conn->commit();
			header("Location: " . $url . "page/home/index.php?message=Laporan+berhasil+dibuat!");
			exit();

		} catch (Exception $e) {
			$conn->rollback();
			// If image uploaded successfully before DB fail, remove it to save space
			if (isset($target_file) && file_exists($target_file)) {
				 unlink($target_file);
			}
			header("Location: " . $url . "page/laporan/index.php?error=" . urlencode($e->getMessage()));
			exit();
		}
	} catch (Exception $e) {
		header("Location: " . $url . "page/laporan/index.php?error=Gagal+membuat+laporan.");
		exit();
	}
}
?>
