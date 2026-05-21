<nav class="navbar navbar-expand-lg bg-body-tertiary shadow-sm" style="position: sticky; top: 0; z-index: 1000;">
  <div class="container">
    <a class="navbar-brand fw-bold" href="<?= $assetBase; ?>page/home/">
        <i class="fas fa-leaf me-2" style="color: var(--primary-color);"></i>EcoReport
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarSupportedContent">
      <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-center">
        <li class="nav-item">
          <a class="nav-link" href="<?= $assetBase; ?>page/home/">Home</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="<?= $assetBase; ?>page/laporan/">Laporan</a>
        </li>                
        <li class="nav-item">
          <a class="nav-link" href="<?= $assetBase; ?>page/notifikasi/">Pemberitahuan</a>
        </li>                
        <?php if(isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === 'admin'): ?>
        <li class="nav-item">
          <a class="nav-link" href="<?= $assetBase; ?>page/admin/">Admin</a>
        </li>
        <?php endif; ?>        
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="fas fa-user-circle fs-4" aria-hidden="true"></i>
            <span class="visually-hidden">User</span>
          </a>
          <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item" href="<?= $assetBase; ?>page/profile/">Profil</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="<?= $assetBase; ?>backend/logout.php">Logout</a></li>
          </ul>
        </li>                             
      </ul>      
    </div>
  </div>
</nav>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">