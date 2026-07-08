<?php
session_start();
require_once 'koneksi.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (empty($username) || empty($password)) {
        $error = 'Username dan password harus diisi!';
    } else {
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id']      = $user['id'];
            $_SESSION['username']     = $user['username'];
            $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
            $_SESSION['role']         = $user['role'];
            $_SESSION['avatar']       = $user['avatar'] ?? '';
            header("Location: main.php");
            exit;
        } else {
            $error = 'Username atau password salah!';
        }
    }
}
?>
<!DOCTYPE html>
<!-- Theme anti-flash -->
<script>(function(){ var t=localStorage.getItem('sg_theme')||'light'; document.documentElement.setAttribute('data-theme',t); })();</script>
<html lang="id">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Login - Rental Kamera</title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/css/lineicons.css" type="text/css" />
    <link rel="stylesheet" href="assets/css/main.css" />
    <link rel="stylesheet" href="assets/css/darkmode.css" />
    <style>
      /* Login page dark mode */
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
      [data-theme="dark"] .text-gray, [data-theme="dark"] .text-muted { color: #8b9ab5 !important; }

      /* Login page theme toggle button */
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

    <!-- Theme toggle button -->
    <button id="loginThemeBtn" class="login-theme-btn" title="Ganti Mode" aria-label="Toggle tema">
      <i id="loginThemeIcon" class="lni lni-night"></i>
    </button>


    <div class="container">
      <div class="row justify-content-center">
        <div class="col-12 col-md-6 col-lg-5">
          <div class="card-style login-card shadow-sm p-40">
            
            <div class="text-center mb-30">
              <h2 class="mb-10 text-bold text-primary">SnapGear</h2>
              <p class="text-sm text-gray">Masuk ke sistem manajemen sewa</p>
            </div>

            <?php if (!empty($error)): ?>
              <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
              </div>
            <?php endif; ?>

            <form method="POST" action="">
              <div class="input-style-1">
                <label for="username">Username</label>
                <input type="text" id="username" name="username"
                       value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                       placeholder="Masukkan username" autocomplete="off" required />
              </div>

              <div class="input-style-1">
                <label for="password">Password</label>
                <input type="password" id="password" name="password"
                       placeholder="Masukkan password" required />
              </div>

              <div class="button-group d-flex justify-content-center flex-wrap mt-20">
                <button type="submit" class="main-btn primary-btn w-100 btn-hover">
                  <i class="lni lni-enter me-2"></i> Masuk
                </button>
              </div>
            </form>

            <div class="d-flex align-items-center my-4">
              <hr class="flex-grow-1 border-gray">
              <span class="mx-3 text-muted text-xs text-uppercase" style="letter-spacing: 1px;">atau</span>
              <hr class="flex-grow-1 border-gray">
            </div>

            <div class="button-group d-flex justify-content-center flex-wrap">
              <a href="google-sso.php" class="main-btn danger-btn-outline w-100 btn-hover d-flex align-items-center justify-content-center">
                <i class="lni lni-google me-2"></i> Masuk dengan Google
              </a>
            </div>

            <div class="text-center mt-30 pt-20 border-top">
              <p class="text-sm text-gray">
                Belum punya akun? <a href="register.php" class="text-bold text-primary text-decoration-none">Daftar di sini</a>
              </p>
            </div>

          </div>
        </div>
      </div>
    </div>

    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script>
      // Theme toggle for login page
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