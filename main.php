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
<?php include ('partials/header.php')?>
  <body>
    <div id="preloader">
      <div class="spinner"></div>
    </div>

  <?php
  include('partials/sidebar.php')
  ?>
    <div class="overlay"></div>
    <main class="main-wrapper">
      <?php include 'partials/topbar.php'; ?>
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
                <i class="lni lni-bullhorn"></i>Daftar Kamera
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