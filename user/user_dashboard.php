<?php
session_start();
$basePath = '../';
require_once '../koneksi.php';

// Proteksi halaman: Wajib login dan harus role 'user'
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}
if (($_SESSION['role'] ?? 'user') === 'admin') {
    header("Location: ../admin/main.php");
    exit;
}

$user_id = (int) $_SESSION['user_id'];
$nama_user = $_SESSION['nama_lengkap'];

// Otomatis tambahkan kolom user_id di tabel penyewaan jika belum ada
$checkPenyewaanUser = $conn->query("SHOW COLUMNS FROM penyewaan LIKE 'user_id'");
if ($checkPenyewaanUser->num_rows == 0) {
    $conn->query("ALTER TABLE penyewaan ADD COLUMN user_id INT(11) DEFAULT NULL AFTER id");
}

$notif = '';
$page = $_GET['page'] ?? 'sewa';

// Penanganan form pengajuan sewa kamera
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'sewa') {
    $id_kamera = (int) $_POST['id_kamera'];
    $tanggal_sewa = $_POST['tanggal_sewa'];
    $tanggal_kembali = $_POST['tanggal_kembali'];
    $catatan = trim($_POST['catatan'] ?? '');

    // Ambil info kamera
    $stmtKam = $conn->prepare("SELECT * FROM kamera WHERE id = ? AND status = 'tersedia' AND stok > 0");
    $stmtKam->bind_param("i", $id_kamera);
    $stmtKam->execute();
    $kamera = $stmtKam->get_result()->fetch_assoc();
    $stmtKam->close();

    if (!$kamera) {
        $notif = 'kamera_tidak_tersedia';
    } else {
        $start    = new DateTime($tanggal_sewa);
        $end      = new DateTime($tanggal_kembali);
        $lama_sewa = $start->diff($end)->days;

        if ($lama_sewa <= 0) {
            $notif = 'durasi_tidak_valid';
        } else {
            $total_bayar = $lama_sewa * $kamera['harga_sewa'];

            // Generate Kode Pembayaran PAY-XXXXX
            $lastKode = $conn->query("SELECT kode_bayar FROM pembayaran ORDER BY id DESC LIMIT 1")->fetch_assoc();
            $nextNum  = $lastKode ? ((int) substr($lastKode['kode_bayar'], 4)) + 1 : 1;
            $kode_bayar = 'PAY-' . str_pad($nextNum, 5, '0', STR_PAD_LEFT);

            // Simpan ke tabel pembayaran (menunggu konfirmasi)
            $stmtIns = $conn->prepare("INSERT INTO pembayaran (user_id, kode_bayar, nama_penyewa, id_kamera, tanggal_sewa, tanggal_kembali, lama_sewa, total_bayar, catatan) VALUES (?,?,?,?,?,?,?,?,?)");
            $stmtIns->bind_param("isssissds", $user_id, $kode_bayar, $nama_user, $id_kamera, $tanggal_sewa, $tanggal_kembali, $lama_sewa, $total_bayar, $catatan);

            if ($stmtIns->execute()) {
                $newId = $stmtIns->insert_id;
                $stmtIns->close();
                // Redirect ke halaman konfirmasi pembayaran
                header("Location: konfirmasi_pembayaran.php?id=$newId");
                exit;
            } else {
                $notif = 'sewa_gagal';
            }
            $stmtIns->close();
        }
    }
}

// Ambil list kamera tersedia untuk katalog
$katalog = $conn->query("SELECT * FROM kamera WHERE status = 'tersedia' AND stok > 0 ORDER BY nama_kamera ASC");

// Ambil riwayat penyewaan user saat ini
$riwayat = $conn->query("
    SELECT p.*, k.nama_kamera, k.kode_kamera, k.merk
    FROM penyewaan p
    JOIN kamera k ON p.id_kamera = k.id
    WHERE p.user_id = $user_id
    ORDER BY p.created_at DESC
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Portal Penyewa - SnapGear</title>
  <link rel="stylesheet" href="../assets/css/bootstrap.min.css"/>
  <link rel="stylesheet" href="../assets/css/lineicons.css"/>
  <link rel="stylesheet" href="../assets/css/materialdesignicons.min.css"/>
  <link rel="stylesheet" href="../assets/css/main.css"/>
  <link rel="stylesheet" href="../assets/css/custom.css"/>
  <?php include '../partials/theme_head.php'; ?>
  <style>
    /* Styling Khas Portal Renter */
    .camera-card {
      border: 1px solid rgba(0, 0, 0, 0.06);
      border-radius: 16px;
      overflow: hidden;
      transition: all 0.3s ease;
      background: #ffffff;
    }
    .camera-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 25px rgba(54, 92, 245, 0.08);
    }
    /* Gambar kamera AI */
    .camera-img-wrap {
      width: 100%;
      height: 180px;
      overflow: hidden;
      background: linear-gradient(135deg, #0a0f1e, #1a2236);
      display: flex;
      align-items: center;
      justify-content: center;
      position: relative;
    }
    .camera-img-wrap img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      transition: transform 0.4s ease;
    }
    .camera-card:hover .camera-img-wrap img {
      transform: scale(1.07);
    }
    /* Fallback icon (jika gambar gagal load) */
    .camera-icon-wrapper {
      width: 70px;
      height: 70px;
      border-radius: 50%;
      background: rgba(54, 92, 245, 0.1);
      color: #365CF5;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 2.2rem;
      margin: 0 auto;
    }
    [data-theme="dark"] .camera-card {
      background: #1e2536 !important;
      border-color: #2a3045 !important;
    }
    [data-theme="dark"] .camera-img-wrap {
      background: linear-gradient(135deg, #0a0f1e, #1a2236) !important;
    }
    [data-theme="dark"] .camera-icon-wrapper {
      background: rgba(91, 122, 248, 0.15) !important;
      color: #5b7af8 !important;
    }
    .badge-tersedia {
      background-color: #e6f7ed;
      color: #219653;
      font-weight: 700;
      font-size: 11px;
      padding: 4px 10px;
      border-radius: 20px;
    }
    [data-theme="dark"] .badge-tersedia {
      background-color: rgba(33, 150, 83, 0.15) !important;
      color: #6ee7b7 !important;
    }
    .price-tag {
      font-size: 1.25rem;
      font-weight: 800;
      color: #365CF5;
    }
    [data-theme="dark"] .price-tag {
      color: #5b7af8 !important;
    }
  </style>
</head>
<body>

<?php include '../partials/sidebar_user.php'; ?>

<main class="main-wrapper">
  <?php include '../partials/topbar.php'; ?>

  <section class="section">
    <div class="container-fluid">

      <!-- Notifikasi Popup -->
      <?php if ($notif === 'sewa_sukses'): ?>
        <div class="alert alert-success alert-dismissible fade show mt-30" role="alert">
          <i class="lni lni-checkmark-circle me-2"></i> Pengajuan sewa kamera berhasil dikirim! Silakan lihat status penyewaan di tab Riwayat.
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php elseif ($notif === 'kamera_tidak_tersedia'): ?>
        <div class="alert alert-danger alert-dismissible fade show mt-30" role="alert">
          <i class="lni lni-warning me-2"></i> Kamera yang dipilih sudah tidak tersedia atau stok habis.
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php elseif ($notif === 'durasi_tidak_valid'): ?>
        <div class="alert alert-warning alert-dismissible fade show mt-30" role="alert">
          <i class="lni lni-warning me-2"></i> Tanggal pengembalian harus setelah tanggal sewa!
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php elseif ($notif === 'sewa_gagal'): ?>
        <div class="alert alert-danger alert-dismissible fade show mt-30" role="alert">
          <i class="lni lni-cross-circle me-2"></i> Terjadi kesalahan sistem. Pengajuan sewa gagal diproses.
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>

      <!-- Header Title -->
      <div class="title-wrapper pt-30">
        <div class="row align-items-center">
          <div class="col-md-6">
            <div class="title">
              <h2><?= $page === 'riwayat' ? 'Riwayat Sewa Saya' : 'Katalog Kamera Tersedia' ?></h2>
            </div>
          </div>
          <div class="col-md-6">
            <div class="breadcrumb-wrapper">
              <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                  <li class="breadcrumb-item"><a href="user_dashboard.php">Portal Renter</a></li>
                  <li class="breadcrumb-item active"><?= $page === 'riwayat' ? 'Riwayat' : 'Katalog Sewa' ?></li>
                </ol>
              </nav>
            </div>
          </div>
        </div>
      </div>

      <!-- KONTEN HALAMAN -->
      <?php if ($page === 'riwayat'): ?>
        <!-- ================= RIWAYAT PENYEWAAN ================= -->
        <div class="card-style mb-30 shadow-sm border-0">
          <div class="title mb-20 d-flex justify-content-between align-items-center flex-wrap">
            <h6 class="text-medium">Daftar Pengajuan Sewa Anda</h6>
            <a href="user_dashboard.php?page=sewa" class="main-btn primary-btn btn-hover btn-sm">Sewa Kamera Baru</a>
          </div>
          <div class="table-responsive">
            <table class="table">
              <thead>
                <tr>
                  <th><h6>No</h6></th>
                  <th><h6>Kode Sewa</h6></th>
                  <th><h6>Kamera</h6></th>
                  <th><h6>Tanggal Sewa</h6></th>
                  <th><h6>Tanggal Kembali</h6></th>
                  <th><h6>Durasi</h6></th>
                  <th><h6>Total Bayar</h6></th>
                  <th><h6>Status</h6></th>
                  <th><h6>Nota</h6></th>
                </tr>
              </thead>
              <tbody>
                <?php if ($riwayat && $riwayat->num_rows > 0): ?>
                  <?php $no = 1; while ($row = $riwayat->fetch_assoc()): ?>
                    <tr>
                      <td><p class="text-sm"><?= $no++ ?></p></td>
                      <td><p class="text-sm"><code><?= htmlspecialchars($row['kode_sewa']) ?></code></p></td>
                      <td>
                        <p class="text-sm fw-bold"><?= htmlspecialchars($row['nama_kamera']) ?></p>
                        <span class="text-xs text-gray"><?= htmlspecialchars($row['merk']) . ' (' . htmlspecialchars($row['kode_kamera']) . ')' ?></span>
                      </td>
                      <td><p class="text-sm"><?= date('d/m/Y', strtotime($row['tanggal_sewa'])) ?></p></td>
                      <td><p class="text-sm"><?= date('d/m/Y', strtotime($row['tanggal_kembali'])) ?></p></td>
                      <td><p class="text-sm"><?= $row['lama_sewa'] ?> Hari</p></td>
                      <td><p class="text-sm fw-bold text-success">Rp <?= number_format($row['total_bayar'], 0, ',', '.') ?></p></td>
                      <td>
                        <?php if ($row['status'] === 'dipinjam'): ?>
                          <span class="badge bg-warning text-dark">Aktif (Dipinjam)</span>
                        <?php elseif ($row['status'] === 'dikembalikan'): ?>
                          <span class="badge bg-success">Selesai (Kembali)</span>
                        <?php else: ?>
                          <span class="badge bg-danger">Terlambat</span>
                        <?php endif; ?>
                      </td>
                      <td>
                        <a href="../admin/penyewaan.php?export=pdf&id=<?= $row['id'] ?>" target="_blank" class="main-btn info-btn btn-hover btn-sm" style="padding: 4px 10px; font-size:11px;">
                          <i class="lni lni-file"></i> PDF
                        </a>
                      </td>
                    </tr>
                  <?php endwhile; ?>
                <?php else: ?>
                  <tr>
                    <td colspan="9" class="text-center py-4 text-gray">
                      Belum ada riwayat penyewaan kamera. <a href="user_dashboard.php?page=sewa">Mulai sewa sekarang »</a>
                    </td>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>

      <?php else: ?>
        <!-- ================= KATALOG SEWA KAMERA ================= -->
        <div class="row">
          <?php if ($katalog && $katalog->num_rows > 0): ?>
            <?php while ($cam = $katalog->fetch_assoc()): ?>
              <div class="col-12 col-md-6 col-lg-4 mb-30">
                <div class="camera-card shadow-sm">

                  <?php
                    // Pilih gambar berdasarkan merk kamera
                    $merkLower = strtolower(trim($cam['merk'] ?? ''));
                    if (str_contains($merkLower, 'canon')) {
                        $camImg = '../assets/img/cameras/canon.png';
                    } elseif (str_contains($merkLower, 'sony')) {
                        $camImg = '../assets/img/cameras/sony.png';
                    } elseif (str_contains($merkLower, 'nikon') || str_contains($merkLower, 'nika')) {
                        $camImg = '../assets/img/cameras/nikon.png';
                    } elseif (str_contains($merkLower, 'gopro') || str_contains($merkLower, 'go pro')) {
                        $camImg = '../assets/img/cameras/gopro.png';
                    } else {
                        $camImg = '../assets/img/cameras/default.png';
                    }
                  ?>

                  <!-- Gambar Kamera AI -->
                  <div class="camera-img-wrap">
                    <span class="badge-tersedia" style="position:absolute;top:12px;right:12px;z-index:2;">
                      <i class="lni lni-checkmark-circle me-1"></i>Tersedia
                    </span>
                    <img src="<?= $camImg ?>" alt="<?= htmlspecialchars($cam['nama_kamera']) ?>" loading="lazy">
                  </div>

                  <!-- Info Kamera -->
                  <div class="p-25">
                    <div class="brand-text text-uppercase text-xs text-gray font-weight-700 mb-5"><?= htmlspecialchars($cam['merk']) ?></div>
                    <h4 class="camera-title mb-10 text-dark"><?= htmlspecialchars($cam['nama_kamera']) ?></h4>

                    <div class="text-sm text-gray mb-20" style="min-height: 40px; line-height: 1.5;">
                      <?= !empty($cam['deskripsi']) ? htmlspecialchars(mb_strimwidth($cam['deskripsi'], 0, 75, '...')) : 'Tidak ada spesifikasi tambahan.' ?>
                    </div>

                    <div class="border-top pt-20 d-flex justify-content-between align-items-center">
                      <div class="text-start">
                        <span class="text-xs text-gray block">Tarif Sewa</span>
                        <div class="price-tag">Rp <?= number_format($cam['harga_sewa'], 0, ',', '.') ?><span class="text-xs text-gray font-weight-normal">/hari</span></div>
                      </div>
                      <button class="main-btn primary-btn btn-hover btn-sm btn-sewa"
                              data-id="<?= $cam['id'] ?>"
                              data-nama="<?= htmlspecialchars($cam['nama_kamera']) ?>"
                              data-harga="<?= $cam['harga_sewa'] ?>"
                              data-stok="<?= $cam['stok'] ?>">
                        Sewa Sekarang
                      </button>
                    </div>
                  </div>

                </div>
              </div>
            <?php endwhile; ?>
          <?php else: ?>
            <div class="col-12 text-center py-5">
              <div class="h4 text-gray mb-10">Katalog Kamera Kosong</div>
              <p class="text-muted">Maaf, saat ini seluruh kamera sedang disewa atau tidak tersedia.</p>
            </div>
          <?php endif; ?>
        </div>
      <?php endif; ?>

    </div>
  </section>

</main>

<!-- Modal Sewa Kamera -->
<div class="modal fade" id="modalSewa" tabindex="-1" aria-labelledby="modalSewaLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content shadow-lg border-0">
      <div class="modal-header">
        <h5 class="modal-title" id="modalSewaLabel">Form Pengajuan Sewa</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST" action="user_dashboard.php?page=sewa">
        <input type="hidden" name="action" value="sewa">
        <input type="hidden" name="id_kamera" id="sewa_id_kamera">
        <input type="hidden" name="harga_sewa" id="sewa_harga_sewa">
        
        <div class="modal-body p-4">
          <div class="mb-3">
            <label class="form-label fw-bold">Kamera Pilihan</label>
            <input type="text" id="sewa_nama_kamera" class="form-control" readonly style="background:#f1f5f9; cursor:not-allowed;">
          </div>
          
          <div class="row">
            <div class="col-6 mb-3">
              <label for="tanggal_sewa" class="form-label fw-bold">Tanggal Sewa <span class="text-danger">*</span></label>
              <input type="date" name="tanggal_sewa" id="tanggal_sewa" class="form-control" required min="<?= date('Y-m-d') ?>">
            </div>
            <div class="col-6 mb-3">
              <label for="tanggal_kembali" class="form-label fw-bold">Tanggal Kembali <span class="text-danger">*</span></label>
              <input type="date" name="tanggal_kembali" id="tanggal_kembali" class="form-control" required min="<?= date('Y-m-d', strtotime('+1 day')) ?>">
            </div>
          </div>

          <div class="card bg-light border-0 p-3 mb-3">
            <div class="d-flex justify-content-between mb-1">
              <span class="text-muted text-sm">Durasi Sewa:</span>
              <span class="fw-bold text-sm" id="calc_durasi">0 hari</span>
            </div>
            <div class="d-flex justify-content-between border-top pt-2">
              <span class="fw-bold text-dark">Estimasi Total:</span>
              <span class="fw-bold text-primary text-lg" id="calc_total">Rp 0</span>
            </div>
          </div>
          
          <div class="mb-3">
            <label for="catatan" class="form-label fw-bold">Catatan Tambahan (Opsional)</label>
            <textarea name="catatan" id="catatan" class="form-control" rows="3" placeholder="Contoh: Lensa tambahan, memori cadangan, dll..."></textarea>
          </div>
        </div>
        
        <div class="modal-footer">
          <button type="button" class="main-btn secondary-btn btn-hover" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="main-btn primary-btn btn-hover">Kirim Permintaan Sewa</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="../assets/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/main.js"></script>
<script>
  // Event modal popup formulir sewa kamera
  const sewaModal = new bootstrap.Modal(document.getElementById('modalSewa'));
  
  document.querySelectorAll('.btn-sewa').forEach(button => {
    button.addEventListener('click', () => {
      document.getElementById('sewa_id_kamera').value = button.dataset.id;
      document.getElementById('sewa_nama_kamera').value = button.dataset.nama;
      document.getElementById('sewa_harga_sewa').value = button.dataset.harga;
      
      // Reset kalkulator
      document.getElementById('tanggal_sewa').value = '';
      document.getElementById('tanggal_kembali').value = '';
      document.getElementById('calc_durasi').innerText = '0 hari';
      document.getElementById('calc_total').innerText = 'Rp 0';
      document.getElementById('catatan').value = '';
      
      sewaModal.show();
    });
  });

  // Kalkulasi estimasi harga penyewaan real-time
  const sDateInput = document.getElementById('tanggal_sewa');
  const eDateInput = document.getElementById('tanggal_kembali');
  const durasiText = document.getElementById('calc_durasi');
  const totalText  = document.getElementById('calc_total');
  const hargaInput = document.getElementById('sewa_harga_sewa');

  function calculatePrice() {
    const startDateVal = sDateInput.value;
    const endDateVal   = eDateInput.value;
    const hargaSewa    = parseFloat(hargaInput.value || 0);

    if (startDateVal && endDateVal) {
      const start = new Date(startDateVal);
      const end   = new Date(endDateVal);
      
      const diffTime = end - start;
      const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
      
      if (diffDays > 0) {
        durasiText.innerText = diffDays + ' hari';
        const total = diffDays * hargaSewa;
        totalText.innerText = 'Rp ' + total.toLocaleString('id-ID');
      } else {
        durasiText.innerText = '0 hari';
        totalText.innerText = 'Rp 0';
      }
    } else {
      durasiText.innerText = '0 hari';
      totalText.innerText = 'Rp 0';
    }
  }

  sDateInput.addEventListener('change', () => {
    if (sDateInput.value) {
      const minEndDate = new Date(sDateInput.value);
      minEndDate.setDate(minEndDate.getDate() + 1);
      eDateInput.min = minEndDate.toISOString().split('T')[0];
      
      if (eDateInput.value && new Date(eDateInput.value) <= new Date(sDateInput.value)) {
        eDateInput.value = eDateInput.min;
      }
    }
    calculatePrice();
  });

  eDateInput.addEventListener('change', calculatePrice);
</script>
</body>
</html>
