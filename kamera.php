<?php
session_start();
require_once 'koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if (isset($_GET['hapus'])) {
    $id = (int) $_GET['hapus'];
    $del = $conn->query("DELETE FROM kamera WHERE id = $id");
    if ($del) {
        header("Location: kamera.php?notif=hapus");
        exit;
    } else {
        $pesan_error = "Kamera tidak bisa dihapus!";
    }
}

$notif = isset($_GET['notif']) ? $_GET['notif'] : '';
$cari   = isset($_GET['cari'])   ? trim($_GET['cari'])   : '';
$filter = isset($_GET['status']) ? trim($_GET['status']) : '';

$where = "WHERE 1=1";
if (!empty($cari))   $where .= " AND (nama_kamera LIKE '%".mysqli_real_escape_string($conn,$cari)."%' OR kode_kamera LIKE '%".mysqli_real_escape_string($conn,$cari)."%' OR merk LIKE '%".mysqli_real_escape_string($conn,$cari)."%')";
if (!empty($filter)) $where .= " AND status = '".mysqli_real_escape_string($conn,$filter)."'";

$kamera_list = $conn->query("SELECT * FROM kamera $where ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="id">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Data Kamera - Rental Kamera</title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/css/lineicons.css" type="text/css" />
    <link rel="stylesheet" href="assets/css/materialdesignicons.min.css" type="text/css" />
    <link rel="stylesheet" href="assets/css/main.css" />
  </head>
  <body>
    <aside class="sidebar-nav-wrapper">
      <div class="navbar-logo">
        <a href="main.php" class="fs-5 fw-bold text-dark text-decoration-none"> RENTAL KAMERA</a>
      </div>
      <nav class="sidebar-nav">
        <ul>
          <li class="nav-item">
            <a href="main.php">
              <span class="icon"><i class="lni lni-dashboard"></i></span>
              <span class="text">Dashboard</span>
            </a>
          </li>
          <li class="nav-item active">
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
        <div class="container-fluid"><button id="menu-toggle" class="main-btn primary-btn btn-hover btn-sm"><i class="lni lni-chevron-left me-2"></i>Menu</button></div>
      </header>

      <section class="section">
        <div class="container-fluid">
          <div class="title-wrapper pt-30">
            <div class="row align-items-center">
              <div class="col-md-6">
                <div class="title"><h2>Manajemen Data Kamera</h2></div>
              </div>
            </div>
          </div>

          <?php if($notif == 'tambah'): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">Sukses menambahkan kamera baru!<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>
          <?php elseif($notif == 'edit'): ?>
            <div class="alert alert-warning alert-dismissible fade show" role="alert">Data kamera berhasil diperbarui!<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>
          <?php elseif($notif == 'hapus'): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">Kamera berhasil dihapus!<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>
          <?php endif; ?>

          <div class="card-style mb-30">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-25">
              <form method="GET" class="d-flex flex-wrap gap-2 align-items-center">
                <div class="select-style-1 mb-0">
                  <div class="select-position">
                    <select name="status" onchange="this.form.submit()">
                      <option value="">Semua Status</option>
                      <option value="tersedia" <?= $filter == 'tersedia' ? 'selected' : '' ?>>Tersedia</option>
                      <option value="disewa" <?= $filter == 'disewa' ? 'selected' : '' ?>>Disewa</option>
                      <option value="rusak" <?= $filter == 'rusak' ? 'selected' : '' ?>>Rusak</option>
                    </select>
                  </div>
                </div>
                <div class="input-style-1 mb-0">
                  <input type="text" name="cari" value="<?= htmlspecialchars($cari) ?>" placeholder="Cari nama/kode..." />
                </div>
                <button type="submit" class="main-btn primary-btn btn-hover"><i class="lni lni-search-alt"></i></button>
              </form>
              <a href="add.php" class="main-btn success-btn btn-hover"><i class="lni lni-plus me-2"></i> Tambah Kamera</a>
            </div>

            <div class="table-wrapper table-responsive">
              <table class="table">
                <thead>
                  <tr>
                    <th><h6>No</h6></th>
                    <th><h6>Kode</h6></th>
                    <th><h6>Nama Kamera</h6></th>
                    <th><h6>Merk / Tipe</h6></th>
                    <th><h6>Harga Sewa / Hari</h6></th>
                    <th><h6>Stok</h6></th>
                    <th><h6>Status</h6></th>
                    <th><h6>Aksi</h6></th>
                  </tr>
                </thead>
                <tbody>
                  <?php if($kamera_list->num_rows > 0): $no = 1; ?>
                    <?php while($row = $kamera_list->fetch_assoc()): ?>
                    <tr>
                      <td><p><?= $no++ ?></p></td>
                      <td><p><code><?= htmlspecialchars($row['kode_kamera']) ?></code></p></td>
                      <td><p class="text-bold"><?= htmlspecialchars($row['nama_kamera']) ?></p></td>
                      <td><p><?= htmlspecialchars($row['merk']) ?> / <?= htmlspecialchars($row['tipe']) ?></p></td>
                      <td><p>Rp <?= number_format($row['harga_sewa'], 0, ',', '.') ?></p></td>
                      <td><p><?= $row['stok'] ?> unit</p></td>
                      <td>
                        <?php if($row['status'] == 'tersedia'): ?>
                          <span class="status-btn success-btn btn-sm">Tersedia</span>
                        <?php elseif($row['status'] == 'disewa'): ?>
                          <span class="status-btn warning-btn btn-sm">Disewa</span>
                        <?php else: ?>
                          <span class="status-btn danger-btn btn-sm">Rusak</span>
                        <?php endif; ?>
                      </td>
                      <td>
                        <div class="action gap-2">
                          <a href="edit_kamera.php?id=<?= $row['id'] ?>" class="text-warning"><i class="lni lni-pencil"></i></a>
                          <a href="kamera.php?hapus=<?= $row['id'] ?>" class="text-danger" onclick="return confirm('Yakin ingin menghapus kamera ini?')"><i class="lni lni-trash-can"></i></a>
                        </div>
                      </td>
                    </tr>
                    <?php endwhile; ?>
                  <?php else: ?>
                    <tr><td colspan="8" class="text-center"><p class="text-muted py-4">📷 Belum ada data kamera.</p></td></tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </section>
    </main>

    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script>
      document.getElementById('menu-toggle').addEventListener('click', () => {
        document.querySelector('.sidebar-nav-wrapper').classList.toggle('active');
        document.querySelector('.main-wrapper').classList.toggle('active');
        document.querySelector('.overlay').classList.toggle('active');
      });
    </script>
  </body>
</html>