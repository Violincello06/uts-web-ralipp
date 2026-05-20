<?php
session_start();
require_once 'koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Mengambil data user untuk header profil dinamis
$nama_user_login  = $_SESSION['nama_lengkap'] ?? $_SESSION['username'];

// Statistik kamera
$total_kamera = $conn->query("SELECT COUNT(*) as total FROM kamera")->fetch_assoc()['total'];
$tersedia     = $conn->query("SELECT COUNT(*) as total FROM kamera WHERE status='tersedia'")->fetch_assoc()['total'];
$disewa       = $conn->query("SELECT COUNT(*) as total FROM kamera WHERE status='disewa'")->fetch_assoc()['total'];
$rusak        = $conn->query("SELECT COUNT(*) as total FROM kamera WHERE status='rusak'")->fetch_assoc()['total'];

// Mengambil semua daftar kamera untuk animasi ticker berjalan
$kamera_ticker = $conn->query("SELECT nama_kamera, merk, status FROM kamera ORDER BY nama_kamera ASC");

// Mengambil 10 data kamera terbaru untuk tabel bawah
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

    <style>
      /* Background Modern Gradient Global */
      body {
        background: linear-gradient(135deg, #f5f7fa 0%, #e4ecf7 100%) !important;
        background-attachment: fixed;
        min-height: 100vh;
      }
      .main-wrapper {
        background: transparent !important;
      }

      /* Struktur Animasi Ticker Berjalan */
      .ticker-wrapper {
        width: 100%;
        overflow: hidden;
        background: #ffffff;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        padding: 18px 0;
        display: flex;
        align-items: center;
        border: 1px solid rgba(0, 0, 0, 0.03);
      }

      .ticker-title {
        background: #365CF5;
        color: #ffffff;
        padding: 18px 25px;
        font-weight: 700;
        text-transform: uppercase;
        font-size: 13px;
        letter-spacing: 1px;
        position: absolute;
        z-index: 5;
        border-radius: 12px 0 0 12px;
        box-shadow: 5px 0 15px rgba(0,0,0,0.1);
        display: flex;
        align-items: center;
        gap: 8px;
        margin-top: -18px;
        height: 78px;
      }

      .ticker-content {
        display: flex;
        white-space: nowrap;
        padding-left: 200px; /* Memberikan ruang agar tidak tertabrak judul */
        animation: ticker-move 25s linear infinite;
      }

      /* Efek Pause Saat Mouse Diarahkan ke Ticker */
      .ticker-wrapper:hover .ticker-content {
        animation-play-state: paused;
        cursor: pointer;
      }

      .ticker-item {
        display: inline-flex;
        align-items: center;
        padding: 0 30px;
        font-size: 16px;
        font-weight: 600;
        color: #24292d;
        border-right: 2px solid #e2e8f0;
      }

      .ticker-item .brand {
        color: #8f92a1;
        font-size: 12px;
        text-transform: uppercase;
        font-weight: 700;
        margin-right: 6px;
        background: #f1f5f9;
        padding: 2px 6px;
        border-radius: 4px;
      }

      .ticker-item .badge-status {
        font-size: 11px;
        padding: 3px 8px;
        border-radius: 30px;
        margin-left: 10px;
        font-weight: 700;
      }

      .status-tersedia { background-color: #e6f7ed; color: #219653; }
      .status-disewa { background-color: #fef5ec; color: #f2994a; }
      .status-rusak { background-color: #fdebae; color: #d32f2f; }

      /* Keyframes Pergerakan Ticker Horizontal */
      @keyframes ticker-move {
        0% {
          transform: translate3d(0, 0, 0);
        }
        100% {
          transform: translate3d(-100%, 0, 0);
        }
      }
    </style>
  </head>
  <body>
    <div id="preloader">
      <div class="spinner"></div>
    </div>

    <aside class="sidebar-nav-wrapper">
      <div class="navbar-logo">
        <a href="main.php" class="fs-5 fw-bold text-dark text-decoration-none">RENTAL KAMERA</a>
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
          
          <div class="title-wrapper pt-30 mb-20">
            <div class="row align-items-center">
              <div class="col-md-6">
                <div class="title">
                  <h2>Dashboard Analisis</h2>
                </div>
              </div>
            </div>
          </div>

          <div class="row mb-30">
            <div class="col-12" style="position: relative;">
              <div class="ticker-title">
                <i class="lni lni-bullhorn"></i> Live Ready
              </div>
              <div class="ticker-wrapper">
                <div class="ticker-content">
                  <?php 
                  if ($kamera_ticker && $kamera_ticker->num_rows > 0):
                    // Kita lakukan perulangan 2 kali agar teks bersambung tanpa putus di layar luas
                    for ($i = 0; $i < 2; $i++):
                      $kamera_ticker->data_seek(0);
                      while ($cam = $kamera_ticker->fetch_assoc()):
                        $status_class = 'status-' . strtolower($cam['status']);
                        $status_text  = ucfirst($cam['status']);
                  ?>
                        <div class="ticker-item">
                          <span class="brand"><?= htmlspecialchars($cam['merk']) ?></span>
                          <span class="name"><?= htmlspecialchars($cam['nama_kamera']) ?></span>
                          <span class="badge-status <?= $status_class ?>"><?= $status_text ?></span>
                        </div>
                  <?php 
                      endwhile;
                    endfor;
                  else:
                  ?>
                    <div class="ticker-item text-muted">Belum ada data kamera tersedia di database.</div>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-lg-7">
              <div class="card-style mb-30 shadow-sm border-0">
                <div class="title mb-20">
                  <h6 class="text-medium">Perbandingan Status Kamera</h6>
                </div>
                <div class="chart-container" style="position: relative; height:300px;">
                  <canvas id="statusBarChart"></canvas>
                </div>
              </div>
            </div>
            
            <div class="col-lg-5">
              <div class="card-style mb-30 shadow-sm border-0">
                <div class="title mb-20">
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
              <div class="card-style mb-30 shadow-sm border-0">
                <div class="title d-flex justify-content-between align-items-center flex-wrap mb-20">
                  <h6 class="text-medium mb-10">Daftar Input Terbaru</h6>
                  <a href="kamera.php" class="main-btn primary-btn-outline btn-hover btn-sm mb-10">Kelola Semua Data</a>
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
                          <td colspan="5" class="text-center"><p class="text-muted py-3">Belum ada data records.</p></td>
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