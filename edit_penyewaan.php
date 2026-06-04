<?php
session_start();
require_once 'koneksi.php';
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

$id   = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if (!$id) { header("Location: penyewaan.php"); exit; }

$data = $conn->query("SELECT p.*, k.harga_sewa FROM penyewaan p JOIN kamera k ON p.id_kamera = k.id WHERE p.id = $id")->fetch_assoc();
if (!$data) { header("Location: penyewaan.php"); exit; }

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_penyewa    = trim($_POST['nama_penyewa']);
    $tanggal_sewa    = $_POST['tanggal_sewa'];
    $tanggal_kembali = $_POST['tanggal_kembali'];
    $status          = $_POST['status'];
    $catatan         = trim($_POST['catatan']);

    if (empty($nama_penyewa) || empty($tanggal_sewa) || empty($tanggal_kembali)) {
        $error = 'Nama penyewa dan tanggal wajib diisi!';
    } elseif ($tanggal_kembali <= $tanggal_sewa) {
        $error = 'Tanggal kembali harus lebih dari tanggal sewa!';
    } else {
        $lama_sewa   = (strtotime($tanggal_kembali) - strtotime($tanggal_sewa)) / 86400;
        $total_bayar = $lama_sewa * $data['harga_sewa'];

        // Jika status diubah ke dikembalikan dari dipinjam, kembalikan stok
        $old_status = $data['status'];
        if ($old_status == 'dipinjam' && $status == 'dikembalikan') {
            $conn->query("UPDATE kamera SET stok = stok + 1, status = 'tersedia' WHERE id = {$data['id_kamera']}");
        }

        $stmt = $conn->prepare("UPDATE penyewaan SET nama_penyewa=?, tanggal_sewa=?, tanggal_kembali=?, lama_sewa=?, total_bayar=?, status=?, catatan=? WHERE id=?");
        $stmt->bind_param("sssiidsi", $nama_penyewa, $tanggal_sewa, $tanggal_kembali, $lama_sewa, $total_bayar, $status, $catatan, $id);

        if ($stmt->execute()) {
            header("Location: penyewaan.php?notif=edit"); exit;
        } else {
            $error = 'Gagal memperbarui data!';
        }
    }
}

$kamera_list = $conn->query("SELECT id, kode_kamera, nama_kamera, harga_sewa FROM kamera ORDER BY nama_kamera");
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Edit Penyewaan - Rental Kamera</title>
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

      <div class="title-wrapper pt-30">
        <div class="row align-items-center">
          <div class="col-md-6">
            <div class="title"><h2>Edit Penyewaan</h2></div>
          </div>
          <div class="col-md-6">
            <div class="breadcrumb-wrapper">
              <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                  <li class="breadcrumb-item"><a href="main.php">Dashboard</a></li>
                  <li class="breadcrumb-item"><a href="penyewaan.php">Penyewaan</a></li>
                  <li class="breadcrumb-item active">Edit</li>
                </ol>
              </nav>
            </div>
          </div>
        </div>
      </div>

      <div class="row">
        <div class="col-lg-8">
          <div class="card-style mb-30">
            <div class="d-flex justify-content-between align-items-center mb-20">
              <h6 class="text-medium">Form Edit Penyewaan</h6>
              <span class="text-sm text-gray">Kode: <code><?= htmlspecialchars($data['kode_sewa']) ?></code></span>
            </div>

            <?php if (!empty($error)): ?>
              <div class="alert alert-danger alert-dismissible fade show mb-20">
                <i class="lni lni-warning me-2"></i><?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
              </div>
            <?php endif; ?>

            <form method="POST" action="">

              <div class="input-style-1 mb-20">
                <label>Nama Penyewa <span class="text-danger">*</span></label>
                <input type="text" name="nama_penyewa"
                       value="<?= htmlspecialchars($data['nama_penyewa']) ?>">
              </div>

              <div class="select-style-1 mb-20">
                <label>Kamera</label>
                <div class="select-position">
                  <select name="id_kamera" disabled>
                    <?php
                    while ($k = $kamera_list->fetch_assoc()):
                    ?>
                    <option value="<?= $k['id'] ?>" <?= $k['id'] == $data['id_kamera'] ? 'selected':'' ?>>
                      <?= htmlspecialchars($k['nama_kamera']) ?> (<?= $k['kode_kamera'] ?>)
                    </option>
                    <?php endwhile; ?>
                  </select>
                </div>
                <p class="text-xs text-gray mt-1">Kamera tidak dapat diubah. Hapus dan buat transaksi baru jika perlu ganti kamera.</p>
              </div>

              <div class="row">
                <div class="col-md-6">
                  <div class="input-style-1 mb-20">
                    <label>Tanggal Sewa <span class="text-danger">*</span></label>
                    <input type="date" name="tanggal_sewa" id="tanggal_sewa"
                           value="<?= $data['tanggal_sewa'] ?>"
                           onchange="hitungTotal()">
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="input-style-1 mb-20">
                    <label>Tanggal Kembali <span class="text-danger">*</span></label>
                    <input type="date" name="tanggal_kembali" id="tanggal_kembali"
                           value="<?= $data['tanggal_kembali'] ?>"
                           onchange="hitungTotal()">
                  </div>
                </div>
              </div>

              <!-- Preview total -->
              <div id="preview-hitung" class="card-style mb-20" style="background:#f8f9ff;border:1px solid #e0e7ff;">
                <div class="d-flex justify-content-between">
                  <span class="text-sm text-gray">Lama Sewa</span>
                  <span class="text-sm text-bold" id="prev-lama"><?= $data['lama_sewa'] ?> hari</span>
                </div>
                <div class="d-flex justify-content-between mt-10">
                  <span class="text-sm text-gray">Harga per Hari</span>
                  <span class="text-sm" id="prev-harga">Rp <?= number_format($data['harga_sewa'],0,',','.') ?></span>
                </div>
                <hr style="margin:10px 0;">
                <div class="d-flex justify-content-between">
                  <span class="text-medium">Total Bayar</span>
                  <span class="text-medium text-bold" id="prev-total" style="color:#365CF5;">
                    Rp <?= number_format($data['total_bayar'],0,',','.') ?>
                  </span>
                </div>
              </div>

              <div class="select-style-1 mb-20">
                <label>Status</label>
                <div class="select-position">
                  <select name="status">
                    <option value="dipinjam"     <?= $data['status']=='dipinjam'     ? 'selected':'' ?>>Dipinjam</option>
                    <option value="dikembalikan" <?= $data['status']=='dikembalikan' ? 'selected':'' ?>>Dikembalikan</option>
                    <option value="terlambat"    <?= $data['status']=='terlambat'    ? 'selected':'' ?>>Terlambat</option>
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
                <a href="penyewaan.php" class="main-btn deactive-btn-2">Batal</a>
              </div>

            </form>
          </div>
        </div>

        <!-- Info Transaksi -->
        <div class="col-lg-4">
          <div class="card-style mb-30">
            <h6 class="mb-20 text-medium">Info Transaksi</h6>
            <div class="mb-15">
              <p class="text-xs text-gray mb-1">Kode Sewa</p>
              <p class="text-sm text-bold"><code><?= htmlspecialchars($data['kode_sewa']) ?></code></p>
            </div>
            <div class="mb-15">
              <p class="text-xs text-gray mb-1">Dibuat pada</p>
              <p class="text-sm"><?= date('d/m/Y H:i', strtotime($data['created_at'])) ?></p>
            </div>
            <div class="mb-15">
              <p class="text-xs text-gray mb-1">Status Saat Ini</p>
              <?php if ($data['status'] == 'dipinjam'): ?>
                <span class="badge bg-warning text-dark">Dipinjam</span>
              <?php elseif ($data['status'] == 'dikembalikan'): ?>
                <span class="badge bg-success">Dikembalikan</span>
              <?php else: ?>
                <span class="badge bg-danger">Terlambat</span>
              <?php endif; ?>
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
const hargaPerHari = <?= $data['harga_sewa'] ?>;
function hitungTotal() {
    const tglSewa    = document.getElementById('tanggal_sewa').value;
    const tglKembali = document.getElementById('tanggal_kembali').value;
    if (!tglSewa || !tglKembali) return;
    const lama  = Math.round((new Date(tglKembali) - new Date(tglSewa)) / 86400000);
    if (lama <= 0) return;
    const total = lama * hargaPerHari;
    document.getElementById('prev-lama').textContent  = lama + ' hari';
    document.getElementById('prev-harga').textContent = 'Rp ' + hargaPerHari.toLocaleString('id-ID');
    document.getElementById('prev-total').textContent = 'Rp ' + total.toLocaleString('id-ID');
}
</script>
</body>
</html>