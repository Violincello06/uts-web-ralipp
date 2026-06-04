<?php
session_start();
require_once 'koneksi.php';
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if (!$id) { header("Location: pengembalian.php"); exit; }

$data = $conn->query("
    SELECT pb.*, p.kode_sewa, p.nama_penyewa, p.tanggal_sewa, p.tanggal_kembali AS tgl_rencana,
           p.total_bayar, p.id_kamera, k.nama_kamera, k.kode_kamera
    FROM pengembalian pb
    JOIN penyewaan p ON pb.id_penyewaan = p.id
    JOIN kamera k    ON p.id_kamera = k.id
    WHERE pb.id = $id
")->fetch_assoc();

if (!$data) { header("Location: pengembalian.php"); exit; }

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $tanggal_kembali_aktual = $_POST['tanggal_kembali_aktual'];
    $kondisi_kamera         = $_POST['kondisi_kamera'];
    $catatan                = trim($_POST['catatan']);

    if (empty($tanggal_kembali_aktual)) {
        $error = 'Tanggal kembali aktual wajib diisi!';
    } else {
        $stmt = $conn->prepare("UPDATE pengembalian SET tanggal_kembali_aktual=?, kondisi_kamera=?, catatan=? WHERE id=?");
        $stmt->bind_param("sssi", $tanggal_kembali_aktual, $kondisi_kamera, $catatan, $id);

        if ($stmt->execute()) {
            // Update status penyewaan berdasarkan tgl aktual
            $terlambat   = $tanggal_kembali_aktual > $data['tgl_rencana'];
            $status_baru = $terlambat ? 'terlambat' : 'dikembalikan';
            $conn->query("UPDATE penyewaan SET status='$status_baru' WHERE id = {$data['id_penyewaan']}");

            // Update status kamera jika kondisi rusak berat
            if ($kondisi_kamera == 'rusak_berat') {
                $conn->query("UPDATE kamera SET status='rusak' WHERE id = {$data['id_kamera']}");
            } elseif ($data['kondisi_kamera'] == 'rusak_berat' && $kondisi_kamera != 'rusak_berat') {
                // Jika sebelumnya rusak berat lalu diubah, kembalikan ke tersedia
                $conn->query("UPDATE kamera SET status='tersedia' WHERE id = {$data['id_kamera']}");
            }

            header("Location: pengembalian.php?notif=sukses"); exit;
        } else {
            $error = 'Gagal memperbarui data!';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Edit Pengembalian - Rental Kamera</title>
  <link rel="stylesheet" href="assets/css/bootstrap.min.css"/>
  <link rel="stylesheet" href="assets/css/lineicons.css"/>
  <link rel="stylesheet" href="assets/css/materialdesignicons.min.css"/>
  <link rel="stylesheet" href="assets/css/main.css"/>
</head>
<body>

<?php include 'partials/sidebar.php'; ?>

<main class="main-wrapper">
  <?php include 'partials/topbar.php'; ?>

  <section class="section">
    <div class="container-fluid">

      <div class="title-wrapper pt-30">
        <div class="row align-items-center">
          <div class="col-md-6">
            <div class="title"><h2>Edit Pengembalian</h2></div>
          </div>
          <div class="col-md-6">
            <div class="breadcrumb-wrapper">
              <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                  <li class="breadcrumb-item"><a href="main.php">Dashboard</a></li>
                  <li class="breadcrumb-item"><a href="pengembalian.php">Pengembalian</a></li>
                  <li class="breadcrumb-item active">Edit</li>
                </ol>
              </nav>
            </div>
          </div>
        </div>
      </div>

      <div class="row">

        <div class="col-lg-7">
          <div class="card-style mb-30">
            <div class="d-flex justify-content-between align-items-center mb-20">
              <h6 class="text-medium">Form Edit Pengembalian</h6>
              <span class="text-sm text-gray">Kode: <code><?= htmlspecialchars($data['kode_sewa']) ?></code></span>
            </div>

            <?php if (!empty($error)): ?>
              <div class="alert alert-danger alert-dismissible fade show mb-20">
                <i class="lni lni-warning me-2"></i><?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
              </div>
            <?php endif; ?>

            <!-- Info penyewaan (readonly) -->
            <div class="card-style mb-20" style="background:#f8f9ff;border:1px solid #e0e7ff;">
              <h6 class="text-sm text-medium mb-15">Info Penyewaan</h6>
              <div class="row g-2">
                <div class="col-6">
                  <p class="text-xs text-gray">Nama Penyewa</p>
                  <p class="text-sm text-bold"><?= htmlspecialchars($data['nama_penyewa']) ?></p>
                </div>
                <div class="col-6">
                  <p class="text-xs text-gray">Kamera</p>
                  <p class="text-sm text-bold"><?= htmlspecialchars($data['nama_kamera']) ?></p>
                </div>
                <div class="col-6">
                  <p class="text-xs text-gray">Tanggal Sewa</p>
                  <p class="text-sm"><?= date('d/m/Y', strtotime($data['tanggal_sewa'])) ?></p>
                </div>
                <div class="col-6">
                  <p class="text-xs text-gray">Rencana Kembali</p>
                  <p class="text-sm"><?= date('d/m/Y', strtotime($data['tgl_rencana'])) ?></p>
                </div>
              </div>
            </div>

            <form method="POST" action="">

              <div class="input-style-1 mb-20">
                <label>Tanggal Kembali Aktual <span class="text-danger">*</span></label>
                <input type="date" name="tanggal_kembali_aktual" id="tanggal_kembali_aktual"
                       value="<?= $data['tanggal_kembali_aktual'] ?>"
                       onchange="cekTerlambat()">
              </div>

              <div id="alert-terlambat" class="alert alert-warning mb-20 py-2"
                   style="font-size:13px;<?= $data['tanggal_kembali_aktual'] > $data['tgl_rencana'] ? '' : 'display:none;' ?>">
                <i class="lni lni-warning me-1"></i> Pengembalian <strong>terlambat</strong> dari jadwal!
              </div>

              <div class="select-style-1 mb-20">
                <label>Kondisi Kamera <span class="text-danger">*</span></label>
                <div class="select-position">
                  <select name="kondisi_kamera">
                    <option value="baik"        <?= $data['kondisi_kamera']=='baik'        ? 'selected':'' ?>>Baik</option>
                    <option value="rusak_ringan" <?= $data['kondisi_kamera']=='rusak_ringan'? 'selected':'' ?>>Rusak Ringan</option>
                    <option value="rusak_berat"  <?= $data['kondisi_kamera']=='rusak_berat' ? 'selected':'' ?>>Rusak Berat</option>
                  </select>
                </div>
              </div>

              <div class="input-style-1 mb-20">
                <label>Catatan</label>
                <textarea name="catatan" rows="3"><?= htmlspecialchars($data['catatan'] ?? '') ?></textarea>
              </div>

              <div class="d-flex gap-2">
                <button type="submit" class="main-btn warning-btn btn-hover">
                  <i class="lni lni-save me-1"></i> Simpan Perubahan
                </button>
                <a href="pengembalian.php" class="main-btn deactive-btn-2">Batal</a>
              </div>

            </form>
          </div>
        </div>

        <!-- Info -->
        <div class="col-lg-5">
          <div class="card-style mb-30">
            <h6 class="mb-20 text-medium">Info Transaksi</h6>
            <div class="mb-15">
              <p class="text-xs text-gray mb-1">Kode Sewa</p>
              <p class="text-sm text-bold"><code><?= htmlspecialchars($data['kode_sewa']) ?></code></p>
            </div>
            <div class="mb-15">
              <p class="text-xs text-gray mb-1">Total Bayar</p>
              <p class="text-sm text-bold" style="color:#365CF5;">Rp <?= number_format($data['total_bayar'],0,',','.') ?></p>
            </div>
            <div class="mb-15">
              <p class="text-xs text-gray mb-1">Kondisi Saat Ini</p>
              <?php if ($data['kondisi_kamera'] == 'baik'): ?>
                <span class="badge bg-success">Baik</span>
              <?php elseif ($data['kondisi_kamera'] == 'rusak_ringan'): ?>
                <span class="badge bg-warning text-dark">Rusak Ringan</span>
              <?php else: ?>
                <span class="badge bg-danger">Rusak Berat</span>
              <?php endif; ?>
            </div>
            <div class="mb-15">
              <p class="text-xs text-gray mb-1">Dicatat pada</p>
              <p class="text-sm"><?= date('d/m/Y H:i', strtotime($data['created_at'])) ?></p>
            </div>
          </div>
        </div>

      </div>
    </div>
  </section>
</main>

<script src="assets/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/main.js"></script>
<script>
const rencanaKembali = '<?= $data['tgl_rencana'] ?>';
function cekTerlambat() {
    const aktual  = document.getElementById('tanggal_kembali_aktual').value;
    const alertEl = document.getElementById('alert-terlambat');
    alertEl.style.display = (aktual && aktual > rencanaKembali) ? 'block' : 'none';
}
</script>
</body>
</html>