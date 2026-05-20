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
            header("Location: main.php");
            exit;
        } else {
            $error = 'Username atau password salah!';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Login - Rental Kamera</title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/css/lineicons.css" type="text/css" />
    <link rel="stylesheet" href="assets/css/main.css" />
  </head>
  <body class="bg-light d-flex align-items-center justify-content-center" style="min-height: 100vh;">

    <div class="container">
      <div class="row justify-content-center">
        <div class="col-12 col-md-6 col-lg-5">
          <div class="card-style shadow-sm p-40">
            
            <div class="text-center mb-30">
              <h2 class="mb-10 text-bold text-primary">RENTAL KAMERA</h2>
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
  </body>
</html>