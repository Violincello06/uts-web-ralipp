<?php
session_start();
$basePath = '../';
require_once '../koneksi.php';
require_once '../helpers/send_email.php';
if (!isset($_SESSION['user_id'])) { header("Location: ../login.php"); exit; }
if (($_SESSION['role'] ?? 'user') !== 'admin') { header("Location: ../user/user_dashboard.php"); exit; }

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_penyewa    = trim($_POST['nama_penyewa']);
    $id_kamera       = (int) $_POST['id_kamera'];
    $tanggal_sewa    = $_POST['tanggal_sewa'];
    $tanggal_kembali = $_POST['tanggal_kembali'];
    $catatan         = trim($_POST['catatan']);

    if (empty($nama_penyewa) || empty($id_kamera) || empty($tanggal_sewa) || empty($tanggal_kembali)) {
        $error = 'Semua field wajib diisi!';
    } elseif ($tanggal_kembali <= $tanggal_sewa) {
        $error = 'Tanggal kembali harus lebih dari tanggal sewa!';
    } else {
        // Cek stok kamera
        $kamera = $conn->query("SELECT * FROM kamera WHERE id = $id_kamera AND stok > 0 AND status = 'tersedia'")->fetch_assoc();
        if (!$kamera) {
            $error = 'Kamera tidak tersedia atau stok habis!';
        } else {
            $lama_sewa  = (strtotime($tanggal_kembali) - strtotime($tanggal_sewa)) / 86400;
            $total_bayar = $lama_sewa * $kamera['harga_sewa'];

            // Generate kode sewa
            $last_kode = $conn->query("SELECT kode_sewa FROM penyewaan ORDER BY id DESC LIMIT 1")->fetch_assoc();
            $next_num  = $last_kode ? ((int) substr($last_kode['kode_sewa'], 3)) + 1 : 1;
            $kode_sewa = 'SW-' . str_pad($next_num, 5, '0', STR_PAD_LEFT);

            $stmt = $conn->prepare("INSERT INTO penyewaan (kode_sewa, nama_penyewa, id_kamera, tanggal_sewa, tanggal_kembali, lama_sewa, total_bayar, catatan) VALUES (?,?,?,?,?,?,?,?)");
            $stmt->bind_param("ssissids", $kode_sewa, $nama_penyewa, $id_kamera, $tanggal_sewa, $tanggal_kembali, $lama_sewa, $total_bayar, $catatan);

            if ($stmt->execute()) {
                // Kurangi stok & update status kamera
                $conn->query("UPDATE kamera SET stok = stok - 1, status = IF(stok - 1 <= 0, 'disewa', status) WHERE id = $id_kamera");

                // Kirim notifikasi email ke admin
                sendNotifikasiSewa([
                    'kode_sewa'       => $kode_sewa,
                    'nama_penyewa'    => $nama_penyewa,
                    'nama_kamera'     => $kamera['nama_kamera'],
                    'tanggal_sewa'    => $tanggal_sewa,
                    'tanggal_kembali' => $tanggal_kembali,
                    'lama_sewa'       => $lama_sewa,
                    'total_bayar'     => $total_bayar,
                    'catatan'         => $catatan,
                ]);

                header("Location: penyewaan.php?notif=tambah"); exit;
            } else {
                $error = 'Gagal menyimpan data!';
            }
        }
    }
}

// Ambil kamera yang tersedia
$kamera_list = $conn->query("SELECT id, kode_kamera, nama_kamera, harga_sewa, stok FROM kamera WHERE status = 'tersedia' AND stok > 0 ORDER BY nama_kamera");
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Tambah Penyewaan - Rental Kamera</title>
  <link rel="stylesheet" href="../assets/css/bootstrap.min.css"/>
  <link rel="stylesheet" href="../assets/css/lineicons.css"/>
  <link rel="stylesheet" href="../assets/css/materialdesignicons.min.css"/>
  <link rel="stylesheet" href="../assets/css/main.css"/>
  <?php include '../partials/theme_head.php'; ?>
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
            <div class="title"><h2>Tambah Penyewaan</h2></div>
          </div>
          <div class="col-md-6">
            <div class="breadcrumb-wrapper">
              <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                  <li class="breadcrumb-item"><a href="main.php">Dashboard</a></li>
                  <li class="breadcrumb-item"><a href="penyewaan.php">Penyewaan</a></li>
                  <li class="breadcrumb-item active">Tambah</li>
                </ol>
              </nav>
            </div>
          </div>
        </div>
      </div>

      <div class="row">
        <div class="col-lg-8">
          <div class="card-style mb-30">
            <h6 class="mb-20 text-medium">Form Tambah Penyewaan</h6>

            <?php if (!empty($error)): ?>
              <div class="alert alert-danger alert-dismissible fade show mb-20">
                <i class="lni lni-warning me-2"></i><?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
              </div>
            <?php endif; ?>

            <form method="POST" action="" id="formSewa">

              <div class="input-style-1 mb-20">
                <label>Nama Penyewa <span class="text-danger">*</span></label>
                <input type="text" name="nama_penyewa" placeholder="Nama lengkap penyewa"
                       value="<?= htmlspecialchars($_POST['nama_penyewa'] ?? '') ?>">
              </div>

              <div class="select-style-1 mb-20">
                <label>Pilih Kamera <span class="text-danger">*</span></label>
                <div class="select-position">
                  <select name="id_kamera" id="id_kamera" onchange="hitungTotal()">
                    <option value="">-- Pilih Kamera --</option>
                    <?php if ($kamera_list && $kamera_list->num_rows > 0):
                      while ($k = $kamera_list->fetch_assoc()): ?>
                      <option value="<?= $k['id'] ?>"
                              data-harga="<?= $k['harga_sewa'] ?>"
                              <?= ($_POST['id_kamera'] ?? '') == $k['id'] ? 'selected':'' ?>>
                        <?= htmlspecialchars($k['nama_kamera']) ?> (<?= $k['kode_kamera'] ?>) - Rp <?= number_format($k['harga_sewa'],0,',','.') ?>/hari | Stok: <?= $k['stok'] ?>
                      </option>
                    <?php endwhile; else: ?>
                      <option value="" disabled>Tidak ada kamera tersedia</option>
                    <?php endif; ?>
                  </select>
                </div>
              </div>

              <div class="row">
                <div class="col-md-6">
                  <div class="input-style-1 mb-20">
                    <label>Tanggal Sewa <span class="text-danger">*</span></label>
                    <input type="date" name="tanggal_sewa" id="tanggal_sewa"
                           value="<?= htmlspecialchars($_POST['tanggal_sewa'] ?? date('Y-m-d')) ?>"
                           onchange="hitungTotal()">
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="input-style-1 mb-20">
                    <label>Tanggal Kembali <span class="text-danger">*</span></label>
                    <input type="date" name="tanggal_kembali" id="tanggal_kembali"
                           value="<?= htmlspecialchars($_POST['tanggal_kembali'] ?? '') ?>"
                           onchange="hitungTotal()">
                  </div>
                </div>
              </div>

              <!-- Preview kalkulasi -->
              <div id="preview-hitung" class="card-style mb-20" style="background:#f8f9ff;border:1px solid #e0e7ff;display:none;">
                <div class="d-flex justify-content-between">
                  <span class="text-sm text-gray">Lama Sewa</span>
                  <span class="text-sm text-bold" id="prev-lama">-</span>
                </div>
                <div class="d-flex justify-content-between mt-10">
                  <span class="text-sm text-gray">Harga per Hari</span>
                  <span class="text-sm" id="prev-harga">-</span>
                </div>
                <hr style="margin:10px 0;">
                <div class="d-flex justify-content-between">
                  <span class="text-medium">Total Bayar</span>
                  <span class="text-medium text-bold" id="prev-total" style="color:#365CF5;">-</span>
                </div>
              </div>

              <div class="input-style-1 mb-20">
                <label>Catatan</label>
                <textarea name="catatan" rows="3" placeholder="Catatan tambahan (opsional)"><?= htmlspecialchars($_POST['catatan'] ?? '') ?></textarea>
              </div>

              <div class="d-flex gap-2">
                <button type="submit" class="main-btn success-btn btn-hover">
                  <i class="lni lni-save me-1"></i> Simpan
                </button>
                <a href="penyewaan.php" class="main-btn deactive-btn-2">Batal</a>
              </div>

            </form>
          </div>
        </div>

        <!-- Info Kamera -->
        <div class="col-lg-4">
          <div class="card-style mb-30">
            <h6 class="mb-20 text-medium">Info Kamera Tersedia</h6>
            <?php
            $kamera_info = $conn->query("SELECT nama_kamera, kode_kamera, tipe, harga_sewa, stok FROM kamera WHERE status='tersedia' AND stok > 0 ORDER BY nama_kamera");
            if ($kamera_info && $kamera_info->num_rows > 0):
              while ($ki = $kamera_info->fetch_assoc()):
            ?>
            <div class="d-flex justify-content-between align-items-center mb-15" style="border-bottom:1px solid #f0f0f0;padding-bottom:10px;">
              <div>
                <p class="text-sm text-bold"><?= htmlspecialchars($ki['nama_kamera']) ?></p>
                <span class="text-xs text-gray"><?= $ki['kode_kamera'] ?> · <?= $ki['tipe'] ?></span>
              </div>
              <div class="text-end">
                <p class="text-sm text-bold" style="color:#365CF5;">Rp <?= number_format($ki['harga_sewa'],0,',','.') ?></p>
                <span class="text-xs text-gray">Stok: <?= $ki['stok'] ?></span>
              </div>
            </div>
            <?php endwhile; else: ?>
              <p class="text-sm text-gray">Tidak ada kamera tersedia.</p>
            <?php endif; ?>
          </div>
        </div>
      </div>

    </div>
  </section>
</main>

<script src="../assets/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/main.js"></script>
<script>
function hitungTotal() {
    const sel      = document.getElementById('id_kamera');
    const tglSewa  = document.getElementById('tanggal_sewa').value;
    const tglKembali = document.getElementById('tanggal_kembali').value;
    const preview  = document.getElementById('preview-hitung');

    if (!sel.value || !tglSewa || !tglKembali) { preview.style.display='none'; return; }

    const harga = parseFloat(sel.options[sel.selectedIndex].dataset.harga) || 0;
    const d1    = new Date(tglSewa);
    const d2    = new Date(tglKembali);
    const lama  = Math.round((d2 - d1) / 86400000);

    if (lama <= 0) { preview.style.display='none'; return; }

    const total = lama * harga;
    document.getElementById('prev-lama').textContent  = lama + ' hari';
    document.getElementById('prev-harga').textContent = 'Rp ' + harga.toLocaleString('id-ID');
    document.getElementById('prev-total').textContent = 'Rp ' + total.toLocaleString('id-ID');
    preview.style.display = 'block';
}
// Jalankan saat load jika ada nilai POST
window.addEventListener('load', hitungTotal);
</script>
</body>
</html>