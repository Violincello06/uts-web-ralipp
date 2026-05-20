<?php
session_start();
require_once 'koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Mengambil data user untuk header profil dinamis
$nama_user_login  = $_SESSION['nama_lengkap'] ?? $_SESSION['username'];

// Statistik kamera (dipertahankan untuk kebutuhan data real-time grafik)
$total_kamera = $conn->query("SELECT COUNT(*) as total FROM kamera")->fetch_assoc()['total'];
$tersedia     = $conn->query("SELECT COUNT(*) as total FROM kamera WHERE status='tersedia'")->fetch_assoc()['total'];
$disewa       = $conn->query("SELECT COUNT(*) as total FROM kamera WHERE status='disewa'")->fetch_assoc()['total'];
$rusak        = $conn->query("SELECT COUNT(*) as total FROM kamera WHERE status='rusak'")->fetch_assoc()['total'];

// Mengambil daftar kamera terbaru dari database (Maksimal 10)
$kamera_list = $conn->query("SELECT * FROM kamera ORDER BY created_at DESC LIMIT 10");
?>
<!DOCTYPE html>
<html lang="id">
  <head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Dashboard - Rental Kamera</title>

    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/css/lineicons.css" type="text/css" />
    <link rel="stylesheet" href="assets/css/materialdesignicons.min.css" type="text/css" />
    <link rel="stylesheet" href="assets/css/main.css" />
  </head>
  <body>
    <div id="preloader">
      <div class="spinner"></div>
    </div>

    <aside class="sidebar-nav-wrapper">
      <div class="navbar-logo">
        <a href="main.php" class="fs-5 fw-bold text-dark text-decoration-none">📷 RENTAL KAMERA</a>
      </div>
      <nav class="sidebar-nav">
        <ul>
          <li class="nav-item active">
            <a href="main.php">
              <span class="icon"><i class="lni lni-dashboard"></i></span>
              <span class="text">Dashboard</span>
            </a>
          </li>
          <li class="nav-item">
            <a href="kamera.php">
              <span class="icon"><i class="lni lni-camera"></i></span>
              <span class="text">Data Kamera</span>
            </a>
          </li>
          <li class="nav-item">
            <a href="logout.php">
              <span class="icon"><i class="lni lni-exit"></i></span>
              <span class="text">Keluar</span>
            </a>
          </li>
        </ul>
      </nav>
    </aside>
    <div class="overlay"></div>
    <main class="main-wrapper">
      <header class="header">
        <div class="container-fluid">
          <div class="row">
            <div class="col-lg-5 col-md-5 col-6">
              <div class="header-left d-flex align-items-center">
                <div class="menu-toggle-btn mr-15">
                  <button id="menu-toggle" class="main-btn primary-btn btn-hover">
                    <i class="lni lni-chevron-left me-2"></i> Menu
                  </button>
                </div>
              </div>
            </div>
            
            <div class="col-lg-7 col-md-7 col-6">
              <div class="header-right d-flex align-items-center justify-content-end">
                <div class="profile-box ml-15">
                  <button class="dropdown-toggle bg-transparent border-0 d-flex align-items-center" type="button" id="profile" data-bs-toggle="dropdown" aria-expanded="false">
                    <div class="profile-info d-none d-md-block text-end me-3">
                      <h6 class="text-sm fw-bold text-dark"><?= htmlspecialchars($nama_user_login) ?></h6>
                      <p class="text-xs text-muted">@<?= htmlspecialchars($_SESSION['username']) ?></p>
                    </div>
                    <div class="avatar-image bg-primary d-flex align-items-center justify-content-center text-white fw-bold shadow-sm" style="width: 40px; height: 40px; border-radius: 50%;">
                      <i class="lni lni-user text-lg"></i>
                    </div>
                  </button>
                  <ul class="dropdown-menu dropdown-menu-end p-2 shadow-sm border-0 mt-2" aria-labelledby="profile">
                    <li>
                      <a class="dropdown-item py-2 text-danger" href="logout.php">
                        <i class="lni lni-exit me-2"></i> Keluar Aplikasi
                      </a>
                    </li>
                  </ul>
                </div>
              </div>
            </div>
          </div>
        </div>
      </header>
      <section class="section">
        <div class="container-fluid">
          <div class="title-wrapper pt-30">
            <div class="row align-items-center">
              <div class="col-md-6">
                <div class="title">
                  <h2>Dashboard Analisis</h2>
                </div>
              </div>
            </div>
          </div>
          <div class="title-wrapper mb-20">
            <h5 class="text-medium text-dark">Katalog Visual Kamera</h5>
          </div>
          
          <div class="row">
            <?php 
            // Mapping Gambar Berdasarkan Merk / Nama Kamera bawaan agar presisi dengan aset Anda
            $images_map = [
                'sony'     => 'image_f4496d.jpg',
                'nikon'    => 'image_f449b0.jpg',
                'canon'    => 'image_f449ed.jpg',
                'fujifilm' => 'image_f44cd0.jpg'
            ];

            if($kamera_list->num_rows > 0):
              $counter = 0;
              while($row = $kamera_list->fetch_assoc()): 
                if($counter >= 4) break; // Batasi tampilan galeri atas hanya 4 kamera teratas
                $counter++;

                // Menentukan gambar berdasarkan kolom merk database
                $merk_key = strtolower($row['merk']);
                $gambar_kamera = 'assets/images/default-camera.jpg'; // fallback jika tidak cocok
                
                foreach ($images_map as $key => $file_img) {
                    if (strpos($merk_key, $key) !== false) {
                        $gambar_kamera = 'assets/images/' . $file_img;
                        break;
                    }
                }
            ?>
              <div class="col-xl-3 col-lg-4 col-sm-6">
                <div class="card-style mb-30 p-0 overflow-hidden shadow-sm h-100 d-flex flex-column justify-content-between">
                  
                  <div class="image-box text-center d-flex align-items-center justify-content-center p-3 bg-white" style="height: 200px; position: relative;">
                    <img src="<?= $gambar_kamera ?>" alt="<?= htmlspecialchars($row['nama_kamera']) ?>" style="max-width: 100%; max-height: 100%; object-fit: contain;">
                    
                    <div style="position: absolute; top: 15px; right: 15px;">
                      <?php if($row['status'] == 'tersedia'): ?>
                        <span class="status-btn success-btn btn-sm text-xs px-2 py-1">Tersedia</span>
                      <?php elseif($row['status'] == 'disewa'): ?>
                        <span class="status-btn warning-btn btn-sm text-xs px-2 py-1">Disewa</span>
                      <?php else: ?>
                        <span class="status-btn danger-btn btn-sm text-xs px-2 py-1">Rusak</span>
                      <?php endif; ?>
                    </div>
                  </div>
                  
                  <div class="p-20 flex-grow-1 d-flex flex-column justify-content-between bg-white border-top">
                    <div>
                      <span class="text-xs text-muted text-uppercase fw-bold"><?= htmlspecialchars($row['merk']) ?></span>
                      <h5 class="text-medium text-dark mb-10 mt-5"><?= htmlspecialchars($row['nama_kamera']) ?></h5>
                      <p class="text-sm text-gray mb-15"><?= htmlspecialchars($row['deskripsi'] ?: 'Kamera berspesifikasi premium siap sewa.') ?></p>
                    </div>
                    <div class="d-flex justify-content-between align-items-center pt-10 border-top mt-auto">
                      <span class="text-xs text-gray">Harga Sewa:</span>
                      <span class="text-sm fw-bold text-primary">Rp <?= number_format($row['harga_sewa'], 0, ',', '.') ?> / Hari</span>
                    </div>
                  </div>

                </div>
              </div>
            <?php 
              endwhile;
              // Reset kembali pointer list agar data tabel di bawah tidak kosong
              $kamera_list->data_seek(0);
            else: 
            ?>
              <div class="col-12">
                <div class="card-style text-center p-40 mb-30">
                  <p class="text-muted">Belum ada data inventaris kamera di sistem.</p>
                </div>
              </div>
            <?php endif; ?>
          </div>
          <div class="row">
            <div class="col-lg-7">
              <div class="card-style mb-30">
                <div class="title d-flex justify-content-between align-items-center mb-20">
                  <h6 class="text-medium">Perbandingan Status Kamera</h6>
                </div>
                <div class="chart-container" style="position: relative; height:300px;">
                  <canvas id="statusBarChart"></canvas>
                </div>
              </div>
            </div>
            
            <div class="col-lg-5">
              <div class="card-style mb-30">
                <div class="title d-flex justify-content-between align-items-center mb-20">
                  <h6 class="text-medium">Persentase Kontribusi</h6>
                </div>
                <div class="chart-container" style="position: relative; height:300px;">
                  <canvas id="statusPieChart"></canvas>
                </div>
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-lg-12">
              <div class="card-style mb-30">
                <div class="title d-flex justify-content-between align-items-center flex-wrap mb-20">
                  <h6 class="text-medium mb-10">Kamera Terbaru</h6>
                  <a href="kamera.php" class="main-btn primary-btn-outline btn-hover btn-sm mb-10">Lihat Semua</a>
                </div>
                <div class="table-wrapper table-responsive">
                  <table class="table">
                    <thead>
                      <tr>
                        <th><h6>Kode</h6></th>
                        <th><h6>Nama Kamera</h6></th>
                        <th><h6>Merk</h6></th>
                        <th><h6>Harga Sewa</h6></th>
                        <th><h6>Status</h6></th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php if($kamera_list->num_rows > 0): ?>
                        <?php while($row = $kamera_list->fetch_assoc()): ?>
                        <tr>
                          <td><p><code><?= htmlspecialchars($row['kode_kamera']) ?></code></p></td>
                          <td><p><?= htmlspecialchars($row['nama_kamera']) ?></p></td>
                          <td><p><?= htmlspecialchars($row['merk']) ?></p></td>
                          <td><p>Rp <?= number_format($row['harga_sewa'], 0, ',', '.') ?></p></td>
                          <td>
                            <?php if($row['status'] == 'tersedia'): ?>
                              <span class="status-btn success-btn btn-sm">Tersedia</span>
                            <?php elseif($row['status'] == 'disewa'): ?>
                              <span class="status-btn warning-btn btn-sm">Disewa</span>
                            <?php else: ?>
                              <span class="status-btn danger-btn btn-sm">Rusak</span>
                            <?php endif; ?>
                          </td>
                        </tr>
                        <?php endwhile; ?>
                      <?php else: ?>
                        <tr>
                          <td colspan="5" class="text-center"><p class="text-muted">Belum ada data kamera.</p></td>
                        </tr>
                      <?php endif; ?>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
          </div>
      </section>
      </main>
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
      const dataTersedia = <?= $tersedia ?>;
      const dataDisewa   = <?= $disewa ?>;
      const dataRusak    = <?= $rusak ?>;

      const ctxBar = document.getElementById('statusBarChart').getContext('2d');
      new Chart(ctxBar, {
        type: 'bar',
        data: {
          labels: ['Tersedia', 'Sedang Disewa', 'Rusak'],
          datasets: [{
            label: 'Jumlah Kamera',
            data: [dataTersedia, dataDisewa, dataRusak],
            backgroundColor: ['#219653', '#F2994A', '#D32F2F'],
            borderWidth: 0,
            borderRadius: 6
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: { legend: { display: false } },
          scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
        }
      });

      const ctxPie = document.getElementById('statusPieChart').getContext('2d');
      new Chart(ctxPie, {
        type: 'doughnut',
        data: {
          labels: ['Tersedia', 'Disewa', 'Rusak'],
          datasets: [{
            data: [dataTersedia, dataDisewa, dataRusak],
            backgroundColor: ['#219653', '#F2994A', '#D32F2F'],
            hoverOffset: 4
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: { legend: { position: 'bottom' } }
        }
      });

      const menuToggleButton = document.getElementById('menu-toggle');
      const sidebarNavWrapper = document.querySelector('.sidebar-nav-wrapper');
      const mainWrapper = document.querySelector('.main-wrapper');
      const overlay = document.querySelector('.overlay');

      menuToggleButton.addEventListener('click', () => {
        sidebarNavWrapper.classList.toggle('active');
        mainWrapper.classList.toggle('active');
        overlay.classList.toggle('active');
      });
      overlay.addEventListener('click', () => {
        sidebarNavWrapper.classList.remove('active');
        mainWrapper.classList.remove('active');
        overlay.classList.remove('active');
      });
      window.addEventListener('load', () => {
        document.getElementById('preloader').style.display = 'none';
      });
    </script>
  </body>
</html>