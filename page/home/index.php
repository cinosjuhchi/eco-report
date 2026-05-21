<?php
session_start();
require_once '../../config/config.php';

$assetBase = isset($url) ? $url : '/';

if (!isset($_SESSION['username'])) {
    header("Location: " . $assetBase . "/page/login/index.php?error=Anda+harus+login+terlebih+dulu.");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beranda</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="<?php echo $assetBase; ?>asset/style/theme.css">
        <link rel="stylesheet" href="<?php echo $assetBase; ?>asset/style/main.css">    
</head>
<body>
    <?php include_once '../../component/nav.php'; ?>
    <main class="container my-4">
        <section class="mb-4">
            <div class="row g-2 align-items-center">
                <div class="col-md-8">
                    <div class="input-group">
                        <input type="text" class="form-control" id="search-input" placeholder="Cari laporan...">
                        <button class="btn btn-outline-secondary" id="search-button" type="button"><i class="fas fa-search"></i></button>
                    </div>
                </div>
                <div class="col-md-4 text-end">
                    <a href="<?php echo $assetBase; ?>page/laporan/" class="btn btn-primary w-100">
                        <i class="fas fa-plus me-2"></i>Buat Laporan
                    </a>
                </div>
            </div>
        </section>

        <section>
            <div class="cards" id="reports-container">
                <!-- Konten laporan akan diload dengan Javascript -->
            </div>
            
            <div id="loading-indicator" class="text-center my-4" style="display: none;">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
            
            <div id="no-more-data" class="text-center text-muted my-4" style="display: none;">
                <p><i class="fas fa-check-circle me-1"></i>Semua laporan sudah ditampilkan.</p>
            </div>
            
            <!-- Elemen ini diamati oleh Intersection Observer -->
            <div id="observer-target" style="height: 20px;"></div>
        </section>
    </main>
    <script>
        const assetBase = "<?= $assetBase; ?>";
        const container = document.getElementById('reports-container');
        const loadingIndicator = document.getElementById('loading-indicator');
        const noMoreData = document.getElementById('no-more-data');
        const observerTarget = document.getElementById('observer-target');
        const searchInput = document.getElementById('search-input');
        const searchButton = document.getElementById('search-button');
        
        let offset = 0;
        const limit = 5;
        let isFetching = false;
        let hasMore = true;
        let currentQuery = '';
        let searchTimer = null;

        function getStatusBadge(status) {
            switch(status) {
                case 'BARU': return '<span class="status ms-auto" style="background-color: var(--primary-color-subtle); color: var(--primary-color);">Baru</span>';
                case 'DIPROSES': return '<span class="status ms-auto" style="background-color: var(--warning-color-subtle); color: var(--warning-color);">Dalam Proses</span>';
                case 'SELESAI': return '<span class="status ms-auto" style="background-color: #e8f5e9; color: #2e7d32;">Selesai</span>';
                case 'DITOLAK': return '<span class="status ms-auto" style="background-color: #ffebee; color: #c62828;">Ditolak</span>';
                default: return '<span class="status ms-auto">Unknown</span>';
            }
        }

        async function toggleLike(btn, laporanId) {
            btn.disabled = true;
            try {
                const formData = new FormData();
                formData.append('laporan_id', laporanId);

                const res = await fetch(`${assetBase}page/home/toggle_like.php`, {
                    method: 'POST',
                    body: formData
                });
                const result = await res.json();

                if (result.ok) {
                    const icon = btn.querySelector('i');
                    if (result.liked) {
                        icon.classList.remove('far');
                        icon.classList.add('fas');
                        btn.style.color = 'var(--primary-color)';
                    } else {
                        icon.classList.remove('fas');
                        icon.classList.add('far');
                        btn.style.color = '';
                    }
                    btn.querySelector('span').textContent = ` ${result.total_like} Suka`;
                }
            } catch (err) {
                console.error('Gagal toggle like:', err);
            } finally {
                btn.disabled = false;
            }
        }

        async function fetchLaporan() {
            if (isFetching || !hasMore) return;
            
            isFetching = true;
            loadingIndicator.style.display = 'block';

            try {
                const queryParam = currentQuery ? `&q=${currentQuery}` : '';
                const response = await fetch(`${assetBase}page/home/load_laporan.php?offset=${offset}&limit=${limit}${queryParam}`);
                const result = await response.json();
                
                if (result.data && result.data.length > 0) {
                    result.data.forEach(laporan => {
                        const card = document.createElement('div');
                        card.className = 'card-custome';
                        card.onclick = () => window.location.href = `${assetBase}page/detail/index.php?kode=${laporan.kode_laporan}`;
                        const isLiked = laporan.is_liked > 0;
                        const likeIconClass = isLiked ? 'fas' : 'far';
                        const likeColor = isLiked ? 'color: var(--primary-color);' : '';

                        card.innerHTML = `
                            <div class="card-header">
                                <img src="${laporan.foto_profil}" alt="User" class="profile-pic">
                                <div class="user-info">
                                    <h5>${laporan.nama_lengkap}</h5>
                                    <p>${laporan.waktu_berlalu}</p>
                                </div>
                                ${getStatusBadge(laporan.status)}
                            </div>
                            <div class="card-body">
                                <p class="card-title">${laporan.judul}</p>
                                <div class="desc-wrapper" onclick="event.stopPropagation();">
                                    <p class="card-text text-truncate-custom">${laporan.deskripsi_laporan}</p>
                                    <button class="btn-selengkapnya" onclick="toggleText(this)">Lihat Selengkapnya</button>
                                </div>
                            </div>
                            <img src="${laporan.foto_utama}" alt="Report Image" class="card-img-top">
                            <div class="interaction" onclick="event.stopPropagation();">
                                <button class="btn-interaction" style="${likeColor}" onclick="toggleLike(this, ${laporan.id})">
                                    <i class="${likeIconClass} fa-thumbs-up"></i>
                                    <span> ${laporan.total_like} Suka</span>
                                </button>
                                <button class="btn-interaction"><i class="far fa-comment"></i> ${laporan.total_komentar} Komentar</button>
                            </div>
                        `;
                        container.appendChild(card);
                    });
                    
                    offset += limit;
                    hasMore = result.has_more;
                } else {
                    hasMore = false;
                }
                
                if (!hasMore && offset > 0) {
                    noMoreData.style.display = 'block';
                }

            } catch (error) {
                console.error('Error fetching laporan:', error);
            } finally {
                isFetching = false;
                loadingIndicator.style.display = 'none';
            }
        }

        function resetAndSearch() {
            offset = 0;
            hasMore = true;
            container.innerHTML = '';
            noMoreData.style.display = 'none';
            fetchLaporan();
        }

        searchInput.addEventListener('input', () => {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(() => {
                currentQuery = searchInput.value.trim();
                resetAndSearch();
            }, 300);
        });

        searchButton.addEventListener('click', () => {
            currentQuery = searchInput.value.trim();
            resetAndSearch();
        });

        // Setup Intersection Observer untuk infinite scrolling
        const observer = new IntersectionObserver((entries) => {
            if (entries[0].isIntersecting && !isFetching && hasMore) {
                fetchLaporan();
            }
        }, {
            root: null,
            rootMargin: '0px',
            threshold: 1.0
        });

        // Mulai mulai mengawasi observerTarget
        observer.observe(observerTarget);

        function toggleText(btn) {
            const desc = btn.previousElementSibling;
            if (desc.classList.contains('text-truncate-custom')) {
                desc.classList.remove('text-truncate-custom');
                desc.classList.add('text-expanded');
                btn.innerText = 'Tampilkan lebih sedikit';
            } else {
                desc.classList.add('text-truncate-custom');
                desc.classList.remove('text-expanded');
                btn.innerText = 'Lihat Selengkapnya';
            }
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>