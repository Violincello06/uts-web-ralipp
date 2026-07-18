<?php
session_start();
$basePath = '../';
require_once '../koneksi.php';

if (!isset($_SESSION['user_id'])) { header("Location: ../login.php"); exit; }
if (($_SESSION['role'] ?? 'user') === 'admin') { header("Location: ../admin/main.php"); exit; }

$user_id = (int) $_SESSION['user_id'];

$list = $conn->query("
    SELECT pb.*, k.nama_kamera, k.kode_kamera, k.merk, p.id as id_penyewaan
    FROM pembayaran pb
    JOIN kamera k ON pb.id_kamera = k.id
    LEFT JOIN penyewaan p ON pb.user_id = p.user_id 
        AND pb.id_kamera = p.id_kamera 
        AND pb.tanggal_sewa = p.tanggal_sewa 
        AND pb.tanggal_kembali = p.tanggal_kembali
    WHERE pb.user_id = $user_id
    ORDER BY pb.created_at DESC
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Status Pembayaran - SnapGear</title>
  <link rel="stylesheet" href="../assets/css/bootstrap.min.css"/>
  <link rel="stylesheet" href="../assets/css/lineicons.css"/>
  <link rel="stylesheet" href="../assets/css/main.css"/>
  <?php include '../partials/theme_head.php'; ?>
</head>
<body>

<?php include '../partials/sidebar_user.php'; ?>

<main class="main-wrapper">
  <?php include '../partials/topbar.php'; ?>
  <section class="section">
    <div class="container-fluid">

      <div class="title-wrapper pt-30">
        <div class="row align-items-center">
          <div class="col-md-6">
            <div class="title"><h2>Status Pembayaran Saya</h2></div>
          </div>
          <div class="col-md-6">
            <nav aria-label="breadcrumb">
              <ol class="breadcrumb justify-content-md-end">
                <li class="breadcrumb-item"><a href="user_dashboard.php">Katalog</a></li>
                <li class="breadcrumb-item active">Status Pembayaran</li>
              </ol>
            </nav>
          </div>
        </div>
      </div>

      <div class="card-style mb-30 shadow-sm border-0">
        <div class="title mb-20 d-flex justify-content-between align-items-center flex-wrap">
          <h6 class="text-medium">Riwayat Pengajuan Pembayaran</h6>
          <a href="user_dashboard.php" class="main-btn primary-btn btn-hover btn-sm">Sewa Kamera Baru</a>
        </div>
        <div class="table-responsive">
          <table class="table">
            <thead>
              <tr>
                <th><h6>No</h6></th>
                <th><h6>Kode</h6></th>
                <th><h6>Kamera</h6></th>
                <th><h6>Periode Sewa</h6></th>
                <th><h6>Total Bayar</h6></th>
                <th><h6>Metode</h6></th>
                <th><h6>Status Bayar</h6></th>
                <th><h6>Catatan Admin</h6></th>
                <th><h6>Aksi</h6></th>
              </tr>
            </thead>
            <tbody>
              <?php if ($list && $list->num_rows > 0): ?>
                <?php $no = 1; while ($row = $list->fetch_assoc()): ?>
                  <tr>
                    <td><p class="text-sm"><?= $no++ ?></p></td>
                    <td><p class="text-sm"><code><?= htmlspecialchars($row['kode_bayar']) ?></code></p></td>
                    <td>
                      <p class="text-sm fw-bold"><?= htmlspecialchars($row['nama_kamera']) ?></p>
                      <span class="text-xs text-gray"><?= htmlspecialchars($row['merk']) ?> (<?= htmlspecialchars($row['kode_kamera']) ?>)</span>
                    </td>
                    <td>
                      <p class="text-sm"><?= date('d/m/Y', strtotime($row['tanggal_sewa'])) ?> &rarr; <?= date('d/m/Y', strtotime($row['tanggal_kembali'])) ?></p>
                      <span class="text-xs text-gray"><?= $row['lama_sewa'] ?> hari</span>
                    </td>
                    <td><p class="text-sm fw-bold text-primary">Rp <?= number_format($row['total_bayar'], 0, ',', '.') ?></p></td>
                    <td>
                      <?php if ($row['metode_bayar'] === 'transfer'): ?>
                        <span class="badge bg-info text-dark"><i class="lni lni-credit-cards me-1"></i>Transfer</span>
                      <?php elseif ($row['metode_bayar'] === 'tunai'): ?>
                        <span class="badge bg-secondary"><i class="lni lni-money-location me-1"></i>Tunai</span>
                      <?php else: ?>
                        <span class="text-xs text-gray">Belum dipilih</span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <?php if ($row['status'] === 'menunggu'): ?>
                        <span class="badge bg-warning text-dark"><i class="lni lni-timer me-1"></i>Menunggu Konfirmasi</span>
                      <?php elseif ($row['status'] === 'dikonfirmasi'): ?>
                        <span class="badge bg-success"><i class="lni lni-checkmark-circle me-1"></i>Dikonfirmasi (Lunas)</span>
                      <?php else: ?>
                        <span class="badge bg-danger"><i class="lni lni-cross-circle me-1"></i>Ditolak</span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <p class="text-xs text-gray"><?= !empty($row['catatan_admin']) ? htmlspecialchars($row['catatan_admin']) : '-' ?></p>
                    </td>
                    <td>
                      <?php if ($row['status'] === 'menunggu' && empty($row['metode_bayar'])): ?>
                        <a href="konfirmasi_pembayaran.php?id=<?= $row['id'] ?>" class="main-btn warning-btn btn-hover btn-sm" style="font-size:11px;padding:4px 10px;">
                          Pilih Metode
                        </a>
                      <?php elseif ($row['status'] === 'dikonfirmasi' && !empty($row['id_penyewaan'])): ?>
                        <a href="../admin/penyewaan.php?export=pdf&id=<?= $row['id_penyewaan'] ?>" target="_blank" class="main-btn success-btn btn-hover btn-sm" style="font-size:11px;padding:4px 10px;">
                          <i class="lni lni-file"></i> Nota
                        </a>
                      <?php elseif (!empty($row['bukti_transfer'])): ?>
                        <a href="<?= htmlspecialchars($row['bukti_transfer']) ?>" target="_blank" class="main-btn info-btn btn-hover btn-sm" style="font-size:11px;padding:4px 10px;">
                          <i class="lni lni-image"></i> Bukti
                        </a>
                      <?php else: ?>
                        <span class="text-xs text-gray">-</span>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endwhile; ?>
              <?php else: ?>
                <tr>
                  <td colspan="9" class="text-center py-4 text-gray">
                    Belum ada pengajuan pembayaran. <a href="user_dashboard.php">Sewa kamera sekarang »</a>
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

<script src="../assets/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/main.js"></script>
</body>
</html>
