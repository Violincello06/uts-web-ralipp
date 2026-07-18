<?php
session_start();
require_once '../koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $kode_kamera = trim($_POST['kode_kamera']);
    $nama_kamera = trim($_POST['nama_kamera']);
    $merk        = trim($_POST['merk']);
    $tipe        = trim($_POST['tipe']);
    $harga_sewa  = trim($_POST['harga_sewa']);
    $stok        = (int) $_POST['stok'];
    $deskripsi   = trim($_POST['deskripsi']);
    $status      = $_POST['status'];

    if (empty($kode_kamera) || empty($nama_kamera) || empty($harga_sewa)) {
        $error = 'Kode kamera, nama kamera, dan harga sewa wajib diisi!';
    } elseif (!is_numeric($harga_sewa) || $harga_sewa < 0) {
        $error = 'Harga sewa harus berupa angka positif!';
    } elseif ($stok < 0) {
        $error = 'Stok tidak boleh negatif!';
    } else {
        $cek = $conn->prepare("SELECT id FROM kamera WHERE kode_kamera = ?");
        $cek->bind_param("s", $kode_kamera);
        $cek->execute();
        $cek->store_result();

        if ($cek->num_rows > 0) {
            $error = 'Kode kamera sudah digunakan, gunakan kode lain!';
        } else {
            $stmt = $conn->prepare("INSERT INTO kamera (kode_kamera, nama_kamera, merk, tipe, harga_sewa, stok, deskripsi, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssdisd", $kode_kamera, $nama_kamera, $merk, $tipe, $harga_sewa, $stok, $deskripsi, $status);

            if ($stmt->execute()) {
                header("Location: kamera.php?notif=tambah");
                exit;
            } else {
                $error = 'Gagal menyimpan data, coba lagi!';
            }
        }
    }
}

$last = $conn->query("SELECT kode_kamera FROM kamera ORDER BY id DESC LIMIT 1")->fetch_assoc();
$next_kode = 'KAM-001';
if ($last) {
    $num = (int) substr($last['kode_kamera'], 4) + 1;
    $next_kode = 'KAM-' . str_pad($num, 3, '0', STR_PAD_LEFT);
}
?>
<!DOCTYPE html>
<html lang="id">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Tambah Kamera - Rental Kamera</title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/css/lineicons.css" type="text/css" />
    <link rel="stylesheet" href="assets/css/main.css" />
    <?php include 'partials/theme_head.php'; ?>
  </head>
  <body>
    <main class="container py-5" style="max-width: 800px;">
      <div class="card-style mb-30">
        <div class="title d-flex justify-content-between align-items-center mb-25">
          <h3>Form Tambah Kamera</h3>
          <a href="kamera.php" class="main-btn dark-btn-outline btn-sm btn-hover">Kembali</a>
        </div>

        <?php if(!empty($error)): ?>
          <div class="alert alert-danger" role="alert"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST" action="">
          <div class="row">
            <div class="col-12 col-md-6">
              <div class="input-style-1">
                <label>Kode Kamera <span class="text-danger">*</span></label>
                <input type="text" name="kode_kamera" value="<?= htmlspecialchars($_POST['kode_kamera'] ?? $next_kode) ?>" required />
              </div>
            </div>
            <div class="col-12 col-md-6">
              <div class="select-style-1">
                <label>Status Operasional</label>
                <div class="select-position">
                  <select name="status">
                    <option value="tersedia" <?= ($_POST['status'] ?? '') == 'tersedia' ? 'selected' : '' ?>>Tersedia (Ready)</option>
                    <option value="disewa" <?= ($_POST['status'] ?? '') == 'disewa' ? 'selected' : '' ?>>Sedang Disewa</option>
                    <option value="rusak" <?= ($_POST['status'] ?? '') == 'rusak' ? 'selected' : '' ?>>Rusak / Maintenance</option>
                  </select>
                </div>
              </div>
            </div>
          </div>

          <div class="input-style-1">
            <label>Nama Kamera <span class="text-danger">*</span></label>
            <input type="text" name="nama_kamera" placeholder="Contoh: Sony Alpha A7 III" value="<?= htmlspecialchars($_POST['nama_kamera'] ?? '') ?>" required />
          </div>

          <div class="row">
            <div class="col-12 col-md-6">
              <div class="input-style-1">
                <label>Merk / Brand</label>
                <input type="text" name="merk" placeholder="Sony, Canon, Fujifilm, Nikon..." value="<?= htmlspecialchars($_POST['merk'] ?? '') ?>" />
              </div>
            </div>
            <div class="col-12 col-md-6">
              <div class="input-style-1">
                <label>Tipe Kamera</label>
                <input type="text" name="tipe" placeholder="DSLR, Mirrorless, Action Cam..." value="<?= htmlspecialchars($_POST['tipe'] ?? '') ?>" />
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-12 col-md-6">
              <div class="input-style-1">
                <label>Harga Sewa / Hari (Rp) <span class="text-danger">*</span></label>
                <input type="number" name="harga_sewa" min="0" placeholder="Contoh: 150000" value="<?= htmlspecialchars($_POST['harga_sewa'] ?? '') ?>" required />
              </div>
            </div>
            <div class="col-12 col-md-6">
              <div class="input-style-1">
                <label>Stok (unit)</label>
                <input type="number" name="stok" min="0" value="<?= htmlspecialchars($_POST['stok'] ?? '1') ?>" />
              </div>
            </div>
          </div>

          <div class="input-style-1">
            <label>Deskripsi & Kelengkapan</label>
            <textarea name="deskripsi" placeholder="Spesifikasi singkat, kelengkapan lensa, dll..." rows="4"><?= htmlspecialchars($_POST['deskripsi'] ?? '') ?></textarea>
          </div>

          <div class="d-flex gap-2 justify-content-end mt-20">
            <a href="kamera.php" class="main-btn secondary-btn btn-hover">Batal</a>
            <button type="submit" class="main-btn success-btn btn-hover"> Simpan Kamera</button>
          </div>
        </form>
      </div>
    </main>
  </body>
</html>