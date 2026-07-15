<?php
session_start();
require_once 'koneksi.php';

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username     = trim($_POST['username']);
    $nama_lengkap = trim($_POST['nama_lengkap']);
    $email        = trim($_POST['email']);
    $password     = trim($_POST['password']);
    $konfirmasi   = trim($_POST['konfirmasi']);

    if (empty($username) || empty($nama_lengkap) || empty($email) || empty($password) || empty($konfirmasi)) {
        $error = 'Semua kolom harus diisi!';
    } elseif (strlen($username) < 4) {
        $error = 'Username minimal 4 karakter!';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid!';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter!';
    } elseif ($password !== $konfirmasi) {
        $error = 'Konfirmasi password tidak cocok!';
    } else {
        $cek = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $cek->bind_param("s", $username);
        $cek->execute();
        $cek->store_result();

        if ($cek->num_rows > 0) {
            $error = 'Username sudah terdaftar!';
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $role = 'petugas';

            $stmt = $conn->prepare("INSERT INTO users (username, nama_lengkap, email, password, role) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $username, $nama_lengkap, $email, $hashed_password, $role);

            if ($stmt->execute()) {
                $success = 'Pendaftaran berhasil! Silakan login.';
            } else {
                $error = 'Gagal mendaftar, terjadi kesalahan sistem.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Daftar Akun - SnapGear</title>
    <!-- Theme anti-flash -->
    <script>(function(){ var t=localStorage.getItem('sg_theme')||'light'; document.documentElement.setAttribute('data-theme',t); })();</script>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/css/lineicons.css" type="text/css" />
    <link rel="stylesheet" href="assets/css/main.css" />
    <link rel="stylesheet" href="assets/css/custom.css" />
    <link rel="stylesheet" href="assets/css/darkmode.css" />
  </head>
  <body class="login-page d-flex align-items-center justify-content-center" style="min-height: 100vh; position: relative;">
    
    <!-- Animated Background -->
    <div class="bg-canvas">
      <div class="stars"></div>
      <div class="orb-mid"></div>
    </div>

    <div class="container py-5">
      <div class="row justify-content-center">
        <div class="col-12 col-md-10 col-lg-7">
          <div class="card-style shadow-sm p-40">
            
            <div class="text-center mb-30">
              <h2 class="mb-10 text-bold text-success"> BUAT AKUN BARU</h2>
              <p class="text-sm text-gray">Silakan lengkapi data di bawah untuk mendaftar petugas sewa</p>
            </div>

            <?php if (!empty($error)): ?>
              <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
              </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
              <div class="alert alert-success" role="alert">
                <?= htmlspecialchars($success) ?> <br>
                <a href="login.php" class="alert-link text-decoration-none">Klik di sini untuk Login »</a>
              </div>
            <?php endif; ?>

            <?php if (empty($success)): ?>
              <form method="POST" action="">
                
                <div class="row">
                  <div class="col-12 col-md-6">
                    <div class="input-style-1">
                      <label for="nama_lengkap">Nama Lengkap <span class="text-danger">*</span></label>
                      <input type="text" id="nama_lengkap" name="nama_lengkap"
                             value="<?= htmlspecialchars($_POST['nama_lengkap'] ?? '') ?>"
                             placeholder="Nama lengkap Anda" required />
                    </div>
                  </div>
                  <div class="col-12 col-md-6">
                    <div class="input-style-1">
                      <label for="username">Username <span class="text-danger">*</span></label>
                      <input type="text" id="username" name="username"
                             value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                             placeholder="Minimal 4 karakter" autocomplete="off" required />
                      <p class="text-sm text-muted mt-1"><i class="lni lni-info-circle"></i> Gunakan huruf kecil, tanpa spasi</p>
                    </div>
                  </div>
                </div>

                <div class="input-style-1">
                  <label for="email">Email Aktif <span class="text-danger">*</span></label>
                  <input type="email" id="email" name="email"
                         value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                         placeholder="contoh@email.com" required />
                </div>

                <div class="row">
                  <div class="col-12 col-md-6">
                    <div class="input-style-1">
                      <label for="password">Password <span class="text-danger">*</span></label>
                      <input type="password" id="password" name="password"
                             placeholder="Minimal 6 karakter" required />
                    </div>
                  </div>
                  <div class="col-12 col-md-6">
                    <div class="input-style-1">
                      <label for="konfirmasi">Konfirmasi Password <span class="text-danger">*</span></label>
                      <input type="password" id="konfirmasi" name="konfirmasi"
                             placeholder="Ulangi password" required />
                    </div>
                  </div>
                </div>

                <div class="button-group d-flex justify-content-end flex-wrap mt-20 gap-2">
                  <a href="login.php" class="main-btn dark-btn-outline btn-hover">Batal</a>
                  <button type="submit" class="main-btn success-btn btn-hover">
                    <i class="lni lni-checkmark-circle me-2"></i> Daftar Sekarang
                  </button>
                </div>
              </form>
            <?php endif; ?>

            <div class="text-center mt-30 pt-20 border-top">
              <p class="text-sm text-gray">
                Sudah punya akun resmi? <a href="login.php" class="text-bold text-success text-decoration-none">Masuk ke Sistem</a>
              </p>
            </div>

          </div>
        </div>
      </div>
    </div>

    <script src="assets/js/bootstrap.bundle.min.js"></script>
  </body>
</html>