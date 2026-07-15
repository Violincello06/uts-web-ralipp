<?php
session_start();
require_once 'koneksi.php';
require_once 'helpers/send_email.php';

// Otomatis tambahkan kolom reset password jika belum ada
$checkCols = $conn->query("SHOW COLUMNS FROM users LIKE 'reset_token'");
if ($checkCols->num_rows == 0) {
    $conn->query("ALTER TABLE users ADD COLUMN reset_token VARCHAR(255) DEFAULT NULL, ADD COLUMN reset_expires DATETIME DEFAULT NULL");
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);

    if (empty($email)) {
        $error = 'Email harus diisi!';
    } else {
        // Cari email di database
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($user) {
            // Generate secure token
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+24 hours'));

            // Update database dengan token & kedaluwarsa
            $stmtUpd = $conn->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE id = ?");
            $stmtUpd->bind_param("ssi", $token, $expires, $user['id']);
            $stmtUpd->execute();
            $stmtUpd->close();

            // Buat tautan reset password
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
            $host = $_SERVER['HTTP_HOST'];
            $uri = dirname($_SERVER['REQUEST_URI']);
            $uri = ($uri === '/' || $uri === '\\') ? '' : $uri;
            $resetLink = $protocol . "://" . $host . $uri . "/reset_password.php?token=" . $token;

            // Kirim email
            $nama = !empty($user['nama_lengkap']) ? $user['nama_lengkap'] : $user['username'];
            $mailResult = sendResetPasswordLink($email, $nama, $resetLink);

            if ($mailResult['ok']) {
                $success = 'Tautan untuk mereset kata sandi telah dikirim ke email Anda. Silakan periksa kotak masuk atau spam email Anda.';
            } else {
                $error = 'Gagal mengirim email reset password: ' . $mailResult['msg'];
            }
        } else {
            $error = 'Alamat email tidak terdaftar!';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<!-- Theme anti-flash -->
<script>(function(){ var t=localStorage.getItem('sg_theme')||'light'; document.documentElement.setAttribute('data-theme',t); })();</script>
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Lupa Password - Rental Kamera</title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/css/lineicons.css" type="text/css" />
    <link rel="stylesheet" href="assets/css/main.css" />
    <style>
      /* Dark mode adaptation */
      [data-theme="dark"] body.login-page {
        background: #0f1117 !important;
      }
      [data-theme="dark"] .login-card {
        background: #1e2536 !important;
        border: 1px solid #2a3045 !important;
        box-shadow: 0 20px 60px rgba(0,0,0,0.4) !important;
      }
      [data-theme="dark"] .login-card h2 { color: #f1f5f9 !important; }
      [data-theme="dark"] .login-card p  { color: #8b9ab5 !important; }
      [data-theme="dark"] .input-style-1 label { color: #8b9ab5 !important; }
      [data-theme="dark"] .input-style-1 input {
        background: #0f1117 !important;
        border-color: #2a3045 !important;
        color: #e2e8f0 !important;
      }
      [data-theme="dark"] .input-style-1 input:focus {
        border-color: #5b7af8 !important;
        box-shadow: 0 0 0 3px rgba(91,122,248,0.18) !important;
      }
      [data-theme="dark"] .border-top { border-color: #2a3045 !important; }
      [data-theme="dark"] .text-gray { color: #8b9ab5 !important; }

      .login-theme-btn {
        position: fixed; top: 18px; right: 20px;
        width: 40px; height: 40px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        background: rgba(54,92,245,0.1); border: 1px solid rgba(54,92,245,0.25);
        color: #365CF5; font-size: 1.1rem; cursor: pointer;
        transition: all 0.3s ease; z-index: 999;
      }
      .login-theme-btn:hover { background: rgba(54,92,245,0.2); transform: rotate(20deg) scale(1.1); }
      [data-theme="dark"] .login-theme-btn {
        background: rgba(255,220,50,0.1); border-color: rgba(255,220,50,0.25); color: #fde68a;
      }
      [data-theme="dark"] .login-theme-btn:hover { background: rgba(255,220,50,0.2); }
    </style>
</head>
<body class="login-page bg-light d-flex align-items-center justify-content-center" style="min-height: 100vh;">

    <!-- Theme toggle -->
    <button id="loginThemeBtn" class="login-theme-btn" title="Ganti Mode" aria-label="Toggle tema">
      <i id="loginThemeIcon" class="lni lni-night"></i>
    </button>

    <div class="container">
      <div class="row justify-content-center">
        <div class="col-12 col-md-6 col-lg-5">
          <div class="card-style login-card shadow-sm p-40">
            
            <div class="text-center mb-30">
              <h2 class="mb-10 text-bold text-primary">Lupa Password</h2>
              <p class="text-sm text-gray">Masukkan email Anda untuk menerima link pemulihan</p>
            </div>

            <?php if (!empty($error)): ?>
              <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
              </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
              <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($success) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
              </div>
            <?php endif; ?>

            <form method="POST" action="">
              <div class="input-style-1">
                <label for="email">Alamat Email</label>
                <input type="email" id="email" name="email"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                       placeholder="nama@email.com" autocomplete="off" required />
              </div>

              <div class="button-group d-flex justify-content-center flex-wrap mt-20">
                <button type="submit" class="main-btn primary-btn w-100 btn-hover">
                  <i class="lni lni-envelope me-2"></i> Kirim Link Reset
                </button>
              </div>
            </form>

            <div class="text-center mt-30 pt-20 border-top">
              <p class="text-sm text-gray mb-0">
                Kembali ke <a href="login.php" class="text-bold text-primary text-decoration-none">Halaman Login</a>
              </p>
            </div>

          </div>
        </div>
      </div>
    </div>

    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script>
      (function() {
        function syncIcon() {
          var theme = document.documentElement.getAttribute('data-theme') || 'light';
          var icon  = document.getElementById('loginThemeIcon');
          if (icon) icon.className = theme === 'dark' ? 'lni lni-sun' : 'lni lni-night';
          var btn = document.getElementById('loginThemeBtn');
          if (btn) btn.title = theme === 'dark' ? 'Mode Terang' : 'Mode Gelap';
        }
        syncIcon();
        document.getElementById('loginThemeBtn').addEventListener('click', function() {
          var cur  = document.documentElement.getAttribute('data-theme') || 'light';
          var next = cur === 'dark' ? 'light' : 'dark';
          document.documentElement.setAttribute('data-theme', next);
          localStorage.setItem('sg_theme', next);
          syncIcon();
        });
      })();
    </script>
</body>
</html>
