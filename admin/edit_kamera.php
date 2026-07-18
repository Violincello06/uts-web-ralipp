<?php
session_start();
$basePath = '../';
require_once '../koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}
if (($_SESSION['role'] ?? 'user') !== 'admin') {
    header("Location: ../user/user_dashboard.php");
    exit;
}

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id == 0) {
    header("Location: kamera.php");
    exit;
}

// Ambil data kamera
$data = $conn->query("SELECT * FROM kamera WHERE id = $id")->fetch_assoc();
if (!$data) {
    header("Location: kamera.php");
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
        // Cek kode kamera duplikat (abaikan milik sendiri)
        $cek = $conn->prepare("SELECT id FROM kamera WHERE kode_kamera = ? AND id != ?");
        $cek->bind_param("si", $kode_kamera, $id);
        $cek->execute();
        $cek->store_result();

        if ($cek->num_rows > 0) {
            $error = 'Kode kamera sudah digunakan oleh kamera lain!';
        } else {
            $stmt = $conn->prepare("UPDATE kamera SET kode_kamera = ?, nama_kamera = ?, merk = ?, tipe = ?, harga_sewa = ?, stok = ?, deskripsi = ?, status = ? WHERE id = ?");
            $stmt->bind_param("ssssdissi", $kode_kamera, $nama_kamera, $merk, $tipe, $harga_sewa, $stok, $deskripsi, $status, $id);

            if ($stmt->execute()) {
                header("Location: kamera.php?notif=edit");
                exit;
            } else {
                $error = 'Gagal menyimpan perubahan data.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
  <head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Edit Kamera - Rental Kamera</title>

    <link rel="stylesheet" href="../assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="../assets/css/lineicons.css" type="text/css" />
    <link rel="stylesheet" href="../assets/css/materialdesignicons.min.css" type="text/css" />
    <link rel="stylesheet" href="../assets/css/main.css" />
    <?php include '../partials/theme_head.php'; ?>
  </head>
  <body>
    <div id="preloader">
      <div class="spinner"></div>
    </div>

<?php include '../partials/sidebar.php'; ?>
    <main class="main-wrapper">
      <?php include '../partials/topbar.php'; ?>
      <section class="section">
        <div class="container-fluid">
          <div class="title-wrapper pt-30">
            <div class="row align-items-center">
              <div class="col-md-6">
                <div class="title">
                  <h2>Ubah Data Kamera</h2>
                </div>
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-lg-12">
              <div class="card-style mb-30">
                <h6 class="mb-25 text-medium">Formulir Pembaruan Data</h6>

                <?php if (!empty($error)): ?>
                  <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                  </div>
                <?php endif; ?>

                <form method="POST" action="">
                  <div class="row">
                    <div class="col-12 col-md-6">
                      <div class="input-style-1">
                        <label>Kode Kamera <span class="text-danger">*</span></label>
                        <input type="text" name="kode_kamera" value="<?= htmlspecialchars($_POST['kode_kamera'] ?? $data['kode_kamera']) ?>" required />
                      </div>
                    </div>
                    <div class="col-12 col-md-6">
                      <div class="select-style-1">
                        <label>Status Operasional</label>
                        <div class="select-position">
                          <select name="status" class="light-bg">
                            <?php 
                            $curr_status = $_POST['status'] ?? $data['status'];
                            $options = [
                              'tersedia' => 'Tersedia (Ready)',
                              'disewa'   => 'Sedang Disewa',
                              'rusak'    => 'Rusak / Maintenance'
                            ];
                            foreach ($options as $val => $label): ?>
                              <option value="<?= $val ?>" <?= $curr_status == $val ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                          </select>
                        </div>
                      </div>
                    </div>
                  </div>

                  <div class="input-style-1">
                    <label>Nama Kamera <span class="text-danger">*</span></label>
                    <input type="text" name="nama_kamera" placeholder="Contoh: Sony Alpha A7 III" value="<?= htmlspecialchars($_POST['nama_kamera'] ?? $data['nama_kamera']) ?>" required />
                  </div>

                  <div class="row">
                    <div class="col-12 col-md-6">
                      <div class="input-style-1">
                        <label>Merk / Brand</label>
                        <input type="text" name="merk" placeholder="Sony, Canon, Fujifilm..." value="<?= htmlspecialchars($_POST['merk'] ?? $data['merk']) ?>" />
                      </div>
                    </div>
                    <div class="col-12 col-md-6">
                      <div class="input-style-1">
                        <label>Tipe Kamera</label>
                        <input type="text" name="tipe" placeholder="Mirrorless, DSLR, Action..." value="<?= htmlspecialchars($_POST['tipe'] ?? $data['tipe']) ?>" />
                      </div>
                    </div>
                  </div>

                  <div class="row">
                    <div class="col-12 col-md-6">
                      <div class="input-style-1">
                        <label>Harga Sewa / Hari (Rp) <span class="text-danger">*</span></label>
                        <input type="number" name="harga_sewa" min="0" value="<?= htmlspecialchars($_POST['harga_sewa'] ?? $data['harga_sewa']) ?>" required />
                      </div>
                    </div>
                    <div class="col-12 col-md-6">
                      <div class="input-style-1">
                        <label>Stok (unit)</label>
                        <input type="number" name="stok" min="0" value="<?= htmlspecialchars($_POST['stok'] ?? $data['stok']) ?>" />
                      </div>
                    </div>
                  </div>

                  <div class="input-style-1">
                    <label>Deskripsi & Kelengkapan</label>
                    <textarea name="deskripsi" placeholder="Spesifikasi singkat..." rows="4"><?= htmlspecialchars($_POST['deskripsi'] ?? $data['deskripsi']) ?></textarea>
                  </div>

                  <div class="d-flex gap-2 justify-content-end mt-20">
                    <a href="kamera.php" class="main-btn secondary-btn btn-hover">Batal</a>
                    <button type="submit" class="main-btn warning-btn btn-hover"> Simpan Perubahan</button>
                  </div>
                </form>

              </div>
            </div>
          </div>
        </div>
      </section>
      </main>
    <script src="../assets/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/main.js"></script>
  </body>
</html>