<?php
session_start();
require_once 'koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Statistik kamera
$total_kamera = $conn->query("SELECT COUNT(*) as total FROM kamera")->fetch_assoc()['total'];
$tersedia     = $conn->query("SELECT COUNT(*) as total FROM kamera WHERE status='tersedia'")->fetch_assoc()['total'];
$disewa       = $conn->query("SELECT COUNT(*) as total FROM kamera WHERE status='disewa'")->fetch_assoc()['total'];
$rusak        = $conn->query("SELECT COUNT(*) as total FROM kamera WHERE status='rusak'")->fetch_assoc()['total'];

// Semua kamera terbaru
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
          <div class="row">
            <div class="col-xl-3 col-lg-4 col-sm-6">
              <div class="icon-card mb-30">
                <div class="icon primary"><i class="lni lni-layers"></i></div>
                <div class="content">
                  <h6 class="text-gray mb-10">Total Kamera</h6>
                  <h3 class="text-bold mb-10"><?= $total_kamera ?></h3>
                </div>
              </div>
            </div>
            <div class="col-xl-3 col-lg-4 col-sm-6">
              <div class="icon-card mb-30">
                <div class="icon success"><i class="lni lni-checkmark-circle"></i></div>
                <div class="content">
                  <h6 class="text-gray mb-10">Tersedia</h6>
                  <h3 class="text-bold mb-10"><?= $tersedia ?></h3>
                </div>
              </div>
            </div>
            <div class="col-xl-3 col-lg-4 col-sm-6">
              <div class="icon-card mb-30">
                <div class="icon orange"><i class="lni lni-reload"></i></div>
                <div class="content">
                  <h6 class="text-gray mb-10">Disewa</h6>
                  <h3 class="text-bold mb-10"><?= $disewa ?></h3>
                </div>
              </div>
            </div>
            <div class="col-xl-3 col-lg-4 col-sm-6">
              <div class="icon-card mb-30">
                <div class="icon danger"><i class="lni lni-warning"></i></div>
                <div class="content">
                  <h6 class="text-gray mb-10">Rusak</h6>
                  <h3 class="text-bold mb-10"><?= $rusak ?></h3>
                </div>
              </div>
            </div>
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
                  <h6 class="text-medium">Persentase Circle</h6>
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
      // 1. Ambil data dari PHP ke JavaScript
      const dataTersedia = <?= $tersedia ?>;
      const dataDisewa   = <?= $disewa ?>;
      const dataRusak    = <?= $rusak ?>;

      // 2. Konfigurasi Grafik Batang (Bar Chart)
      const ctxBar = document.getElementById('statusBarChart').getContext('2d');
      new Chart(ctxBar, {
        type: 'bar',
        data: {
          labels: ['Tersedia', 'Sedang Disewa', 'Rusak / Perbaikan'],
          datasets: [{
            label: 'Jumlah Kamera',
            data: [dataTersedia, dataDisewa, dataRusak],
            backgroundColor: [
              '#219653', // Hijau (Success)
              '#F2994A', // Orange (Warning)
              '#D32F2F'  // Merah (Danger)
            ],
            borderWidth: 0,
            borderRadius: 6 // Membuat ujung batang sedikit melengkung rapi
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { display: false } // Sembunyikan label kotak atas karena sudah jelas
          },
          scales: {
            y: {
              beginAtZero: true,
              ticks: { stepSize: 1 } // Skala angka meloncat per 1 unit
            }
          }
        }
      });

      // 3. Konfigurasi Grafik Lingkaran (Doughnut/Pie Chart)
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
          plugins: {
            legend: { position: 'bottom' } // Taruh legenda penjelas di bawah grafik
          }
        }
      });

      // Kontrol navigasi sidebar menu
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