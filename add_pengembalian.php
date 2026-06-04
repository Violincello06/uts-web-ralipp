<?php
session_start();
require_once 'koneksi.php';
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_penyewaan           = (int) $_POST['id_penyewaan'];
    $tanggal_kembali_aktual = $_POST['tanggal_kembali_aktual'];
    $kondisi_kamera         = $_POST['kondisi_kamera'];
    $catatan                = trim($_POST['catatan']);

    if (!$id_penyewaan || empty($tanggal_kembali_aktual)) {
        $error = 'Data penyewaan dan tanggal kembali wajib diisi!';
    } else {
        // Cek sudah pernah dikembalikan
        $cek = $conn->query("SELECT id FROM pengembalian WHERE id_penyewaan = $id_penyewaan")->fetch_assoc();
        if ($cek) {
            $error = 'Penyewaan ini sudah pernah diproses pengembaliannya!';
        } else {
            $sewa = $conn->query("SELECT * FROM penyewaan WHERE id = $id_penyewaan AND status = 'dipinjam'")->fetch_assoc();
            if (!$sewa) {
                $error = 'Data penyewaan tidak ditemukan atau sudah dikembalikan!';
            } else {
                $stmt = $conn->prepare("INSERT INTO pengembalian (id_penyewaan, tanggal_kembali_aktual, kondisi_kamera, catatan) VALUES (?,?,?,?)");
                $stmt->bind_param("isss", $id_penyewaan, $tanggal_kembali_aktual, $kondisi_kamera, $catatan);

                if ($stmt->execute()) {
                    // Update status penyewaan
                    $terlambat = $tanggal_kembali_aktual > $sewa['tanggal_kembali'];
                    $status_baru = $terlambat ? 'terlambat' : 'dikembalikan';
                    $conn->query("UPDATE penyewaan SET status='$status_baru' WHERE id = $id_penyewaan");

                    // Update stok & status kamera
                    $conn->query("UPDATE kamera SET stok = stok + 1, status = 'tersedia' WHERE id = {$sewa['id_kamera']}");

                    // Jika kondisi rusak berat, tandai kamera rusak
                    if ($kondisi_kamera == 'rusak_berat') {
                        $conn->query("UPDATE kamera SET status = 'rusak' WHERE id = {$sewa['id_kamera']}");
                    }

                    header("Location: pengembalian.php?notif=sukses"); exit;
                } else {
                    $error = 'Gagal menyimpan data!';
                }
            }
        }
    }
}

// Ambil daftar penyewaan yang masih dipinjam & belum ada pengembaliannya
$sewa_aktif = $conn->query("
    SELECT p.id, p.kode_sewa, p.nama_penyewa, p.tanggal_sewa, p.tanggal_kembali,
           p.lama_sewa, p.total_bayar, k.nama_kamera, k.kode_kamera
    FROM penyewaan p
    JOIN kamera k ON p.id_kamera = k.id
    WHERE p.status = 'dipinjam'
      AND p.id NOT IN (SELECT id_penyewaan FROM pengembalian)
    ORDER BY p.tanggal_kembali ASC
");

$selected_id = isset($_GET['id_penyewaan']) ? (int)$_GET['id_penyewaan'] : 0;
$selected_sewa = null;
if ($selected_id) {
    $selected_sewa = $conn->query("
        SELECT p.*, k.nama_kamera, k.kode_kamera, k.harga_sewa
        FROM penyewaan p JOIN kamera k ON p.id_kamera = k.id
        WHERE p.id = $selected_id AND p.status = 'dipinjam'
    ")->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Proses Pengembalian - Rental Kamera</title>
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
            <div class="title"><h2>Proses Pengembalian</h2></div>
          </div>
          <div class="col-md-6">
            <div class="breadcrumb-wrapper">
              <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                  <li class="breadcrumb-item"><a href="main.php">Dashboard</a></li>
                  <li class="breadcrumb-item"><a href="pengembalian.php">Pengembalian</a></li>
                  <li class="breadcrumb-item active">Proses</li>
                </ol>
              </nav>
            </div>
          </div>
        </div>
      </div>

      <div class="row">

        <!-- Form -->
        <div class="col-lg-7">
          <div class="card-style mb-30">
            <h6 class="mb-20 text-medium">Form Pengembalian Kamera</h6>

            <?php if (!empty($error)): ?>
              <div class="alert alert-danger alert-dismissible fade show mb-20">
                <i class="lni lni-warning me-2"></i><?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
              </div>
            <?php endif; ?>

            <form method="POST" action="">

              <div class="select-style-1 mb-20">
                <label>Pilih Penyewaan Aktif <span class="text-danger">*</span></label>
                <div class="select-position">
                  <select name="id_penyewaan" id="id_penyewaan" onchange="loadDetail(this.value)">
                    <option value="">-- Pilih Penyewaan --</option>
                    <?php if ($sewa_aktif && $sewa_aktif->num_rows > 0):
                      while ($s = $sewa_aktif->fetch_assoc()):
                      $lewat = $s['tanggal_kembali'] < date('Y-m-d') ? ' ⚠️ TERLAMBAT' : '';
                    ?>
                      <option value="<?= $s['id'] ?>"
                              data-kode="<?= htmlspecialchars($s['kode_sewa']) ?>"
                              data-nama="<?= htmlspecialchars($s['nama_penyewa']) ?>"
                              data-kamera="<?= htmlspecialchars($s['nama_kamera']) ?>"
                              data-tgl-sewa="<?= $s['tanggal_sewa'] ?>"
                              data-tgl-kembali="<?= $s['tanggal_kembali'] ?>"
                              data-total="<?= $s['total_bayar'] ?>"
                              <?= ($selected_id == $s['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($s['kode_sewa']) ?> — <?= htmlspecialchars($s['nama_penyewa']) ?> (<?= $s['nama_kamera'] ?>) · Rencana: <?= date('d/m/Y', strtotime($s['tanggal_kembali'])) ?><?= $lewat ?>
                      </option>
                    <?php endwhile; else: ?>
                      <option disabled>Tidak ada penyewaan aktif</option>
                    <?php endif; ?>
                  </select>
                </div>
              </div>

              <!-- Detail penyewaan terpilih -->
              <div id="detail-sewa" class="card-style mb-20" style="background:#f8f9ff;border:1px solid #e0e7ff;display:none;">
                <h6 class="text-sm text-medium mb-15">Detail Penyewaan</h6>
                <div class="row g-2">
                  <div class="col-6">
                    <p class="text-xs text-gray">Nama Penyewa</p>
                    <p class="text-sm text-bold" id="d-nama">-</p>
                  </div>
                  <div class="col-6">
                    <p class="text-xs text-gray">Kamera</p>
                    <p class="text-sm text-bold" id="d-kamera">-</p>
                  </div>
                  <div class="col-6">
                    <p class="text-xs text-gray">Tanggal Sewa</p>
                    <p class="text-sm" id="d-tgl-sewa">-</p>
                  </div>
                  <div class="col-6">
                    <p class="text-xs text-gray">Rencana Kembali</p>
                    <p class="text-sm" id="d-tgl-kembali">-</p>
                  </div>
                  <div class="col-12">
                    <p class="text-xs text-gray">Total Bayar</p>
                    <p class="text-sm text-bold" style="color:#365CF5;" id="d-total">-</p>
                  </div>
                </div>
                <div id="alert-terlambat" class="alert alert-warning mt-15 mb-0 py-2" style="display:none;font-size:13px;">
                  <i class="lni lni-warning me-1"></i> Pengembalian <strong>terlambat</strong> dari jadwal!
                </div>
              </div>

              <div class="input-style-1 mb-20">
                <label>Tanggal Kembali Aktual <span class="text-danger">*</span></label>
                <input type="date" name="tanggal_kembali_aktual" id="tanggal_kembali_aktual"
                       value="<?= $_POST['tanggal_kembali_aktual'] ?? date('Y-m-d') ?>"
                       onchange="cekTerlambat()">
              </div>

              <div class="select-style-1 mb-20">
                <label>Kondisi Kamera <span class="text-danger">*</span></label>
                <div class="select-position">
                  <select name="kondisi_kamera">
                    <option value="baik"         <?= ($_POST['kondisi_kamera'] ?? '') == 'baik'         ? 'selected':'' ?>>Baik</option>
                    <option value="rusak_ringan"  <?= ($_POST['kondisi_kamera'] ?? '') == 'rusak_ringan' ? 'selected':'' ?>>Rusak Ringan</option>
                    <option value="rusak_berat"   <?= ($_POST['kondisi_kamera'] ?? '') == 'rusak_berat'  ? 'selected':'' ?>>Rusak Berat</option>
                  </select>
                </div>
                <p class="text-xs text-gray mt-1">Jika <strong>Rusak Berat</strong>, status kamera otomatis diubah menjadi Rusak.</p>
              </div>

              <div class="input-style-1 mb-20">
                <label>Catatan</label>
                <textarea name="catatan" rows="3" placeholder="Catatan kondisi, kelengkapan, dll..."><?= htmlspecialchars($_POST['catatan'] ?? '') ?></textarea>
              </div>

              <div class="d-flex gap-2">
                <button type="submit" class="main-btn success-btn btn-hover">
                  <i class="lni lni-save me-1"></i> Proses Pengembalian
                </button>
                <a href="pengembalian.php" class="main-btn deactive-btn-2">Batal</a>
              </div>

            </form>
          </div>
        </div>

        <!-- Penyewaan aktif -->
        <div class="col-lg-5">
          <div class="card-style mb-30">
            <h6 class="mb-20 text-medium">Penyewaan Aktif (Belum Dikembalikan)</h6>
            <?php
            $sewa_aktif->data_seek(0);
            if ($sewa_aktif->num_rows > 0):
              while ($s = $sewa_aktif->fetch_assoc()):
                $lewat = $s['tanggal_kembali'] < date('Y-m-d');
            ?>
            <div class="d-flex justify-content-between align-items-start mb-15 pb-15"
                 style="border-bottom:1px solid #f0f0f0;">
              <div>
                <p class="text-sm text-bold"><?= htmlspecialchars($s['nama_penyewa']) ?></p>
                <span class="text-xs text-gray"><?= $s['kode_sewa'] ?> · <?= $s['nama_kamera'] ?></span><br>
                <span class="text-xs text-gray">Kembali: <?= date('d/m/Y', strtotime($s['tanggal_kembali'])) ?></span>
              </div>
              <div class="text-end">
                <?php if ($lewat): ?>
                  <span class="badge bg-danger mb-1">Terlambat</span><br>
                <?php else: ?>
                  <span class="badge bg-warning text-dark mb-1">Dipinjam</span><br>
                <?php endif; ?>
                <a href="add_pengembalian.php?id_penyewaan=<?= $s['id'] ?>"
                   class="main-btn primary-btn-outline btn-hover"
                   style="font-size:11px;padding:3px 8px;">
                  Proses
                </a>
              </div>
            </div>
            <?php endwhile; else: ?>
              <p class="text-sm text-gray">Tidak ada penyewaan aktif.</p>
            <?php endif; ?>
          </div>
        </div>

      </div>
    </div>
  </section>
</main>

<script src="assets/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/main.js"></script>
<script>
let rencanaKembali = '';

function loadDetail(id) {
    const sel = document.getElementById('id_penyewaan');
    const opt = sel.options[sel.selectedIndex];
    const box = document.getElementById('detail-sewa');

    if (!id) { box.style.display = 'none'; return; }

    const fmt = d => {
        const [y,m,dn] = d.split('-');
        return `${dn}/${m}/${y}`;
    };
    const total = parseFloat(opt.dataset.total);

    document.getElementById('d-nama').textContent     = opt.dataset.nama;
    document.getElementById('d-kamera').textContent   = opt.dataset.kamera;
    document.getElementById('d-tgl-sewa').textContent = fmt(opt.dataset.tglSewa);
    document.getElementById('d-tgl-kembali').textContent = fmt(opt.dataset.tglKembali);
    document.getElementById('d-total').textContent    = 'Rp ' + total.toLocaleString('id-ID');

    rencanaKembali = opt.dataset.tglKembali;
    box.style.display = 'block';
    cekTerlambat();
}

function cekTerlambat() {
    if (!rencanaKembali) return;
    const aktual  = document.getElementById('tanggal_kembali_aktual').value;
    const alertEl = document.getElementById('alert-terlambat');
    if (aktual && aktual > rencanaKembali) {
        alertEl.style.display = 'block';
    } else {
        alertEl.style.display = 'none';
    }
}

// Auto load jika ada id_penyewaan dari GET
window.addEventListener('load', function() {
    const sel = document.getElementById('id_penyewaan');
    if (sel.value) loadDetail(sel.value);
});
</script>
</body>
</html>