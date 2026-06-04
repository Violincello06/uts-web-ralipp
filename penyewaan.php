<?php
session_start();
require_once 'koneksi.php';
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

// Quick-return action removed: pengembalian sekarang diproses melalui form add_pengembalian.php

// Hapus penyewaan
if (isset($_GET['hapus'])) {
    $id = (int) $_GET['hapus'];
    $sewa = $conn->query("SELECT id_kamera, status FROM penyewaan WHERE id = $id")->fetch_assoc();
    if ($sewa) {
        if ($sewa['status'] == 'dipinjam') {
            $conn->query("UPDATE kamera SET stok = stok + 1 WHERE id = {$sewa['id_kamera']}");
        }
        $conn->query("DELETE FROM penyewaan WHERE id = $id");
        header("Location: penyewaan.php?notif=hapus"); exit;
    }
}

$notif  = $_GET['notif'] ?? '';
$cari   = trim($_GET['cari']   ?? '');
$filter = trim($_GET['status'] ?? '');

$where = "WHERE 1=1";
if (!empty($cari))   $where .= " AND (p.nama_penyewa LIKE '%".mysqli_real_escape_string($conn,$cari)."%' OR p.kode_sewa LIKE '%".mysqli_real_escape_string($conn,$cari)."%')";
if (!empty($filter)) $where .= " AND p.status = '".mysqli_real_escape_string($conn,$filter)."'";

$list = $conn->query("
    SELECT p.*, k.nama_kamera, k.kode_kamera
    FROM penyewaan p
    JOIN kamera k ON p.id_kamera = k.id
    $where
    ORDER BY p.created_at DESC
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Penyewaan - Rental Kamera</title>
  <link rel="stylesheet" href="assets/css/bootstrap.min.css"/>
  <link rel="stylesheet" href="assets/css/lineicons.css"/>
  <link rel="stylesheet" href="assets/css/materialdesignicons.min.css"/>
  <link rel="stylesheet" href="assets/css/main.css"/>
</head>
<body>

<?php include 'partials/sidebar.php'; ?>

<main class="main-wrapper">
  <?php include 'partials/header.php'; ?>

  <section class="section">
    <div class="container-fluid">

      <!-- Title -->
      <div class="title-wrapper pt-30">
        <div class="row align-items-center">
          <div class="col-md-6">
            <div class="title"><h2>Data Penyewaan</h2></div>
          </div>
          <div class="col-md-6">
            <div class="breadcrumb-wrapper">
              <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                  <li class="breadcrumb-item"><a href="main.php">Dashboard</a></li>
                  <li class="breadcrumb-item active">Penyewaan</li>
                </ol>
              </nav>
            </div>
          </div>
        </div>
      </div>

      <!-- Notifikasi -->
      <?php if ($notif == 'tambah'): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
          <i class="lni lni-checkmark-circle me-2"></i> Data penyewaan berhasil ditambahkan!
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php elseif ($notif == 'edit'): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
          <i class="lni lni-checkmark-circle me-2"></i> Data penyewaan berhasil diperbarui!
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php elseif ($notif == 'hapus'): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
          <i class="lni lni-checkmark-circle me-2"></i> Data penyewaan berhasil dihapus!
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php elseif ($notif == 'kembali'): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
          <i class="lni lni-checkmark-circle me-2"></i> Kamera berhasil dikembalikan & stok diperbarui!
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>

      <!-- Card Tabel -->
      <div class="card-style mb-30">

        <!-- Toolbar -->
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-20">
          <form method="GET" class="d-flex gap-2 flex-wrap align-items-center">
            <div class="input-style-1">
              <input type="text" name="cari" placeholder="Cari nama / kode sewa..." value="<?= htmlspecialchars($cari) ?>" style="min-width:220px;">
            </div>
            <div class="select-style-1">
              <div class="select-position">
                <select name="status">
                  <option value="">-- Semua Status --</option>
                  <option value="dipinjam"     <?= $filter=='dipinjam'     ? 'selected':'' ?>>Dipinjam</option>
                  <option value="dikembalikan" <?= $filter=='dikembalikan' ? 'selected':'' ?>>Dikembalikan</option>
                  <option value="terlambat"    <?= $filter=='terlambat'    ? 'selected':'' ?>>Terlambat</option>
                </select>
              </div>
            </div>
            <button type="submit" class="main-btn primary-btn btn-hover">
              <i class="lni lni-search-alt me-1"></i> Cari
            </button>
            <?php if (!empty($cari) || !empty($filter)): ?>
              <a href="penyewaan.php" class="main-btn deactive-btn-2">Reset</a>
            <?php endif; ?>
          </form>
          <a href="add_penyewaan.php" class="main-btn success-btn btn-hover">
            <i class="lni lni-plus me-1"></i> Tambah Sewa
          </a>
        </div>

        <!-- Tabel -->
        <div class="table-responsive">
          <table class="table top-selling-table">
            <thead>
              <tr>
                <th><h6 class="text-sm text-medium">No</h6></th>
                <th><h6 class="text-sm text-medium">Kode Sewa</h6></th>
                <th><h6 class="text-sm text-medium">Nama Penyewa</h6></th>
                <th><h6 class="text-sm text-medium">Kamera</h6></th>
                <th><h6 class="text-sm text-medium">Tgl Sewa</h6></th>
                <th><h6 class="text-sm text-medium">Tgl Kembali</h6></th>
                <th><h6 class="text-sm text-medium">Lama</h6></th>
                <th><h6 class="text-sm text-medium">Total</h6></th>
                <th><h6 class="text-sm text-medium">Status</h6></th>
                <th><h6 class="text-sm text-medium">Aksi</h6></th>
              </tr>
            </thead>
            <tbody>
            <?php if ($list && $list->num_rows > 0): ?>
              <?php $no = 1; while ($row = $list->fetch_assoc()): ?>
              <tr>
                <td><p class="text-sm"><?= $no++ ?></p></td>
                <td><p class="text-sm"><code><?= htmlspecialchars($row['kode_sewa']) ?></code></p></td>
                <td><p class="text-sm"><?= htmlspecialchars($row['nama_penyewa']) ?></p></td>
                <td>
                  <p class="text-sm"><?= htmlspecialchars($row['nama_kamera']) ?></p>
                  <span class="text-xs text-gray"><?= htmlspecialchars($row['kode_kamera']) ?></span>
                </td>
                <td><p class="text-sm"><?= date('d/m/Y', strtotime($row['tanggal_sewa'])) ?></p></td>
                <td><p class="text-sm"><?= date('d/m/Y', strtotime($row['tanggal_kembali'])) ?></p></td>
                <td><p class="text-sm"><?= $row['lama_sewa'] ?> hari</p></td>
                <td><p class="text-sm">Rp <?= number_format($row['total_bayar'], 0, ',', '.') ?></p></td>
                <td>
                  <?php if ($row['status'] == 'dipinjam'): ?>
                    <span class="badge bg-warning text-dark">Dipinjam</span>
                  <?php elseif ($row['status'] == 'dikembalikan'): ?>
                    <span class="badge bg-success">Dikembalikan</span>
                  <?php else: ?>
                    <span class="badge bg-danger">Terlambat</span>
                  <?php endif; ?>
                </td>
                <td>
                  <div class="action d-flex gap-2 flex-wrap">
                    <?php if ($row['status'] == 'dipinjam'): ?>
                      <a href="add_pengembalian.php?id_penyewaan=<?= $row['id'] ?>"
                         class="main-btn primary-btn-outline btn-hover btn-sm"
                         style="font-size:12px;padding:4px 10px;">
                        <i class="lni lni-checkmark"></i> Proses
                      </a>
                    <?php endif; ?>
                    <a href="edit_penyewaan.php?id=<?= $row['id'] ?>"
                       class="main-btn warning-btn btn-hover btn-sm"
                       style="font-size:12px;padding:4px 10px;">
                      <i class="lni lni-pencil-alt"></i> Edit
                    </a>
                    <a href="penyewaan.php?hapus=<?= $row['id'] ?>"
                       class="main-btn danger-btn btn-hover btn-sm"
                       style="font-size:12px;padding:4px 10px;"
                       onclick="return confirm('Yakin hapus data ini?')">
                      <i class="lni lni-trash-can"></i> Hapus
                    </a>
                  </div>
                </td>
              </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr>
                <td colspan="10" class="text-center py-4 text-gray">
                  Belum ada data penyewaan. <a href="add_penyewaan.php">Tambah sekarang »</a>
                </td>
              </tr>
            <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </section>
</main>

<script src="assets/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/main.js"></script>
</body>
</html>