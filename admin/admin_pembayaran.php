<?php
session_start();
$basePath = '../';
require_once '../koneksi.php';

if (!isset($_SESSION['user_id'])) { header("Location: ../login.php"); exit; }
if (($_SESSION['role'] ?? 'user') !== 'admin') { header("Location: ../user/user_dashboard.php"); exit; }

$notif = '';

// Proses Konfirmasi atau Tolak Pembayaran
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi'])) {
    $id_bayar      = (int) $_POST['id_bayar'];
    $aksi          = $_POST['aksi'];
    $catatan_admin = trim($_POST['catatan_admin'] ?? '');

    // Ambil data pembayaran
    $dataBayar = $conn->query("SELECT * FROM pembayaran WHERE id = $id_bayar")->fetch_assoc();

    if ($dataBayar && $dataBayar['status'] === 'menunggu') {
        if ($aksi === 'konfirmasi') {
            // Generate Kode Sewa SW-XXXXX
            $lastKode = $conn->query("SELECT kode_sewa FROM penyewaan ORDER BY id DESC LIMIT 1")->fetch_assoc();
            $nextNum  = $lastKode ? ((int) substr($lastKode['kode_sewa'], 3)) + 1 : 1;
            $kodeSewa = 'SW-' . str_pad($nextNum, 5, '0', STR_PAD_LEFT);

            // Insert ke tabel penyewaan
            $stmtIns = $conn->prepare("INSERT INTO penyewaan (kode_sewa, user_id, nama_penyewa, id_kamera, tanggal_sewa, tanggal_kembali, lama_sewa, total_bayar, status, catatan) VALUES (?,?,?,?,?,?,?,?,'dipinjam',?)");
            $stmtIns->bind_param("sissisids",
                $kodeSewa,
                $dataBayar['user_id'],
                $dataBayar['nama_penyewa'],
                $dataBayar['id_kamera'],
                $dataBayar['tanggal_sewa'],
                $dataBayar['tanggal_kembali'],
                $dataBayar['lama_sewa'],
                $dataBayar['total_bayar'],
                $dataBayar['catatan']
            );

            if ($stmtIns->execute()) {
                // Kurangi stok kamera
                $conn->query("UPDATE kamera SET stok = IF(stok > 0, stok - 1, 0), status = IF(stok - 1 <= 0, 'disewa', status) WHERE id = {$dataBayar['id_kamera']}");

                // Update status pembayaran menjadi dikonfirmasi
                $stmtUpd = $conn->prepare("UPDATE pembayaran SET status = 'dikonfirmasi', catatan_admin = ? WHERE id = ?");
                $stmtUpd->bind_param("si", $catatan_admin, $id_bayar);
                $stmtUpd->execute();
                $stmtUpd->close();

                $notif = 'konfirmasi_ok';
            } else {
                $notif = 'konfirmasi_gagal';
            }
            $stmtIns->close();

        } elseif ($aksi === 'tolak') {
            $stmtTolak = $conn->prepare("UPDATE pembayaran SET status = 'ditolak', catatan_admin = ? WHERE id = ?");
            $stmtTolak->bind_param("si", $catatan_admin, $id_bayar);
            $stmtTolak->execute();
            $stmtTolak->close();
            $notif = 'tolak_ok';
        }
    }
}

// Ambil semua pembayaran
$filterStatus = $_GET['status'] ?? 'menunggu';
$allowedStatus = ['menunggu', 'dikonfirmasi', 'ditolak', 'semua'];
if (!in_array($filterStatus, $allowedStatus)) $filterStatus = 'menunggu';

$where = $filterStatus !== 'semua' ? "WHERE pb.status = '$filterStatus'" : '';

$list = $conn->query("
    SELECT pb.*, k.nama_kamera, k.kode_kamera, k.merk, u.username
    FROM pembayaran pb
    JOIN kamera k ON pb.id_kamera = k.id
    LEFT JOIN users u ON pb.user_id = u.id
    $where
    ORDER BY pb.created_at DESC
");

// Hitung jumlah menunggu untuk badge
$jumlahMenunggu = $conn->query("SELECT COUNT(*) as n FROM pembayaran WHERE status = 'menunggu'")->fetch_assoc()['n'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Konfirmasi Pembayaran - Admin SnapGear</title>
  <link rel="stylesheet" href="../assets/css/bootstrap.min.css"/>
  <link rel="stylesheet" href="../assets/css/lineicons.css"/>
  <link rel="stylesheet" href="../assets/css/materialdesignicons.min.css"/>
  <link rel="stylesheet" href="../assets/css/main.css"/>
  <?php include '../partials/theme_head.php'; ?>
  <style>
    .bukti-thumb {
      width: 60px; height: 60px; object-fit: cover;
      border-radius: 8px; cursor: pointer;
      border: 2px solid #e5e7eb;
      transition: transform 0.2s;
    }
    .bukti-thumb:hover { transform: scale(1.1); }
    .status-pill {
      font-size: 11px; font-weight: 700;
      padding: 4px 10px; border-radius: 20px;
    }
  </style>
</head>
<body>

<?php include '../partials/sidebar.php'; ?>

<main class="main-wrapper">
  <?php include '../partials/topbar.php'; ?>
  <section class="section">
    <div class="container-fluid">

      <div class="title-wrapper pt-30">
        <div class="row align-items-center">
          <div class="col-md-6">
            <div class="title">
              <h2>Konfirmasi Pembayaran
                <?php if ($jumlahMenunggu > 0): ?>
                  <span class="badge bg-danger ms-2" style="font-size:14px;"><?= $jumlahMenunggu ?></span>
                <?php endif; ?>
              </h2>
            </div>
          </div>
          <div class="col-md-6">
            <nav aria-label="breadcrumb">
              <ol class="breadcrumb justify-content-md-end">
                <li class="breadcrumb-item"><a href="main.php">Dashboard</a></li>
                <li class="breadcrumb-item active">Konfirmasi Pembayaran</li>
              </ol>
            </nav>
          </div>
        </div>
      </div>

      <!-- Notifikasi -->
      <?php if ($notif === 'konfirmasi_ok'): ?>
        <div class="alert alert-success alert-dismissible fade show mt-20" role="alert">
          <i class="lni lni-checkmark-circle me-2"></i> Pembayaran berhasil dikonfirmasi! Data penyewaan telah masuk ke Daftar Penyewa.
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php elseif ($notif === 'tolak_ok'): ?>
        <div class="alert alert-warning alert-dismissible fade show mt-20" role="alert">
          <i class="lni lni-warning me-2"></i> Pengajuan pembayaran telah ditolak.
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php elseif ($notif === 'konfirmasi_gagal'): ?>
        <div class="alert alert-danger alert-dismissible fade show mt-20" role="alert">
          <i class="lni lni-cross-circle me-2"></i> Gagal mengkonfirmasi pembayaran. Coba lagi.
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>

      <!-- Filter Tab -->
      <div class="d-flex gap-2 mb-20 flex-wrap">
        <?php foreach (['menunggu' => 'Menunggu', 'dikonfirmasi' => 'Dikonfirmasi', 'ditolak' => 'Ditolak', 'semua' => 'Semua'] as $val => $label): ?>
          <a href="?status=<?= $val ?>"
             class="main-btn btn-hover <?= $filterStatus === $val ? 'primary-btn' : 'deactive-btn-2' ?>">
            <?= $label ?>
            <?php if ($val === 'menunggu' && $jumlahMenunggu > 0): ?>
              <span class="badge bg-danger ms-1"><?= $jumlahMenunggu ?></span>
            <?php endif; ?>
          </a>
        <?php endforeach; ?>
      </div>

      <!-- Tabel -->
      <div class="card-style mb-30 shadow-sm border-0">
        <div class="table-responsive">
          <table class="table">
            <thead>
              <tr>
                <th><h6>No</h6></th>
                <th><h6>Kode</h6></th>
                <th><h6>Penyewa</h6></th>
                <th><h6>Kamera</h6></th>
                <th><h6>Periode</h6></th>
                <th><h6>Total Bayar</h6></th>
                <th><h6>Metode</h6></th>
                <th><h6>Bukti</h6></th>
                <th><h6>Status</h6></th>
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
                      <p class="text-sm fw-bold"><?= htmlspecialchars($row['nama_penyewa']) ?></p>
                      <span class="text-xs text-gray">@<?= htmlspecialchars($row['username'] ?? '-') ?></span>
                    </td>
                    <td>
                      <p class="text-sm"><?= htmlspecialchars($row['nama_kamera']) ?></p>
                      <span class="text-xs text-gray"><?= htmlspecialchars($row['kode_kamera']) ?></span>
                    </td>
                    <td>
                      <p class="text-sm"><?= date('d/m/Y', strtotime($row['tanggal_sewa'])) ?> &rarr; <?= date('d/m/Y', strtotime($row['tanggal_kembali'])) ?></p>
                      <span class="text-xs text-gray"><?= $row['lama_sewa'] ?> hari</span>
                    </td>
                    <td><p class="text-sm fw-bold text-primary">Rp <?= number_format($row['total_bayar'], 0, ',', '.') ?></p></td>
                    <td>
                      <?php if ($row['metode_bayar'] === 'transfer'): ?>
                        <span class="badge bg-info text-dark">Transfer</span>
                      <?php elseif ($row['metode_bayar'] === 'tunai'): ?>
                        <span class="badge bg-secondary">Tunai</span>
                      <?php else: ?>
                        <span class="text-xs text-gray">Belum dipilih</span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <?php if (!empty($row['bukti_transfer'])): ?>
                        <?php
                          $buktiPath = $row['bukti_transfer'];
                          // File tersimpan di folder user/, akses dari admin/ perlu prefix ../user/
                          $buktiUrl  = '../user/' . ltrim($buktiPath, '/');
                          $ext       = strtolower(pathinfo($buktiPath, PATHINFO_EXTENSION));
                        ?>
                        <?php if ($ext === 'pdf'): ?>
                          <a href="<?= htmlspecialchars($buktiUrl) ?>" target="_blank" class="text-primary text-sm">
                            <i class="lni lni-files"></i> PDF
                          </a>
                        <?php else: ?>
                          <img src="<?= htmlspecialchars($buktiUrl) ?>" class="bukti-thumb"
                               onclick="window.open('<?= htmlspecialchars($buktiUrl) ?>', '_blank')"
                               alt="Bukti Transfer"
                               onerror="this.style.display='none';this.nextElementSibling.style.display='inline'">
                          <a href="<?= htmlspecialchars($buktiUrl) ?>" target="_blank" class="text-primary text-sm" style="display:none">
                            <i class="lni lni-image"></i> Lihat Bukti
                          </a>
                        <?php endif; ?>
                      <?php else: ?>
                        <span class="text-xs text-gray">-</span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <?php if ($row['status'] === 'menunggu'): ?>
                        <span class="status-pill" style="background:#fff3cd;color:#856404;">Menunggu</span>
                      <?php elseif ($row['status'] === 'dikonfirmasi'): ?>
                        <span class="status-pill" style="background:#d1fae5;color:#065f46;">Lunas</span>
                      <?php else: ?>
                        <span class="status-pill" style="background:#fee2e2;color:#991b1b;">Ditolak</span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <?php if ($row['status'] === 'menunggu'): ?>
                        <button class="main-btn success-btn btn-hover btn-sm mb-5"
                                style="font-size:11px;padding:4px 10px;"
                                onclick="openModal(<?= $row['id'] ?>, 'konfirmasi', '<?= htmlspecialchars($row['nama_penyewa']) ?>', '<?= htmlspecialchars($row['kode_bayar']) ?>')">
                          <i class="lni lni-checkmark"></i> Konfirmasi
                        </button>
                        <br>
                        <button class="main-btn danger-btn btn-hover btn-sm"
                                style="font-size:11px;padding:4px 10px;"
                                onclick="openModal(<?= $row['id'] ?>, 'tolak', '<?= htmlspecialchars($row['nama_penyewa']) ?>', '<?= htmlspecialchars($row['kode_bayar']) ?>')">
                          <i class="lni lni-cross"></i> Tolak
                        </button>
                      <?php else: ?>
                        <?php if (!empty($row['catatan_admin'])): ?>
                          <span class="text-xs text-gray"><?= htmlspecialchars($row['catatan_admin']) ?></span>
                        <?php else: ?>
                          <span class="text-xs text-gray">-</span>
                        <?php endif; ?>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endwhile; ?>
              <?php else: ?>
                <tr>
                  <td colspan="10" class="text-center py-4 text-gray">
                    Tidak ada data pembayaran dengan status ini.
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

<!-- Modal Konfirmasi / Tolak -->
<div class="modal fade" id="modalAksi" tabindex="-1" aria-labelledby="modalAksiLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg">
      <div class="modal-header">
        <h5 class="modal-title" id="modalAksiLabel">Konfirmasi Aksi</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST">
        <input type="hidden" name="id_bayar" id="modal_id_bayar">
        <input type="hidden" name="aksi"     id="modal_aksi">
        <div class="modal-body p-4">
          <p id="modal_pesan" class="text-sm mb-20"></p>
          <div class="input-style-1">
            <label for="catatan_admin">Catatan Admin (Opsional)</label>
            <textarea name="catatan_admin" id="catatan_admin" class="form-control" rows="3"
                      placeholder="Mis: Transfer sudah diterima / Bukti tidak sesuai"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="main-btn secondary-btn btn-hover" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="main-btn primary-btn btn-hover" id="modal_btn_submit">Ya, Konfirmasi</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="../assets/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/main.js"></script>
<script>
  const modalAksi = new bootstrap.Modal(document.getElementById('modalAksi'));

  function openModal(id, aksi, nama, kode) {
    document.getElementById('modal_id_bayar').value = id;
    document.getElementById('modal_aksi').value     = aksi;
    document.getElementById('catatan_admin').value  = '';

    const btn   = document.getElementById('modal_btn_submit');
    const label = document.getElementById('modalAksiLabel');
    const pesan = document.getElementById('modal_pesan');

    if (aksi === 'konfirmasi') {
      label.textContent  = 'Konfirmasi Pembayaran';
      pesan.innerHTML    = `Konfirmasi pembayaran <strong>${kode}</strong> dari <strong>${nama}</strong>?<br><span class="text-gray text-xs">Data sewa akan otomatis masuk ke Daftar Penyewa dan stok kamera akan dikurangi.</span>`;
      btn.className      = 'main-btn success-btn btn-hover';
      btn.textContent    = 'Ya, Konfirmasi Lunas';
    } else {
      label.textContent  = 'Tolak Pembayaran';
      pesan.innerHTML    = `Tolak pengajuan pembayaran <strong>${kode}</strong> dari <strong>${nama}</strong>?`;
      btn.className      = 'main-btn danger-btn btn-hover';
      btn.textContent    = 'Ya, Tolak';
    }

    modalAksi.show();
  }
</script>
</body>
</html>
