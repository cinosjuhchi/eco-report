<?php
session_start();
require_once '../../config/config.php';
$assetBase = isset($url) ? $url : '/';
if (isset($_SESSION['username'])) {
    header("Location: " . $assetBase . "/");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
        <link rel="stylesheet" href="<?php echo $assetBase; ?>asset/style/theme.css">
        <link rel="stylesheet" href="<?php echo $assetBase; ?>asset/style/login.css">    
</head>
<body>
    <main>
        <section class="banner-login">
            <div class="d-flex justify-content-between flex-column h-100">
                <div class="d-flex gap-2 align-items-center">
                    <img src="<?= $assetBase; ?>asset/image/logo.png" alt="Ecoreport Logo" style="height: 40px;">
                    <h3 class="banner-header">Ecoreport</h3>
                </div>
                <div class="banner-description">
                    <h3>“Suaramu penting untuk dunia yang lebih baik.”</h3>
                    <p>Laporkan, pantau dan bantu tingkatkan kualitas lingkungan!</p>
                </div>
            </div>
        </section>
        <section class="content">
            <div class="form-login">
                <div class="greeting-text">
                    <h2 class="text-center text-header">Selamat Datang Kembali</h2>
                    <p class="text-center text-secondary">Silahkan masuk untuk melanjutkan ke Ecoreport</p>
                </div>
                <form action="" method="post">
                    <div class="input-label">
                        <label for="formEmail" class="form-label">Email</label>  
                        <input type="email" class="form-control" id="formEmail" placeholder="name@example.com">
                    </div>
                    <div class="input-label">
                        <label for="formPassword" class="form-label">Password</label>  
                        <input type="password" class="form-control" id="formPassword" placeholder="Password">
                    </div>        
                    <div class="d-flex justify-content-end input-label">
                        <a href="#" class="text-primary text-decoration-none small">Lupa password?</a>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Masuk</button>

                    <div class="text-center mt-3">
                        <a href="<?php echo $assetBase; ?>page/register/" class="text-primary text-decoration-none small">Belum punya akun? Daftar</a>
                    </div>
                </form>
            </div>
        </section>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>