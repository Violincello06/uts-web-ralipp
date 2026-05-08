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

    // Validasi
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
        // Cek username sudah ada
        $cek = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $cek->bind_param("s", $username);
        $cek->execute();
        $cek->store_result();

        if ($cek->num_rows > 0) {
            $error = 'Username sudah digunakan, pilih username lain!';
        } else {
            // Cek email sudah ada
            $cek2 = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $cek2->bind_param("s", $email);
            $cek2->execute();
            $cek2->store_result();

            if ($cek2->num_rows > 0) {
                $error = 'Email sudah terdaftar!';
            } else {
                // Simpan user baru
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO users (username, password, nama_lengkap, email, role) VALUES (?, ?, ?, ?, 'user')");
                $stmt->bind_param("ssss", $username, $hash, $nama_lengkap, $email);

                if ($stmt->execute()) {
                    $success = 'Akun berhasil dibuat! Silakan login.';
                } else {
                    $error = 'Gagal membuat akun, coba lagi.';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Rental Kamera</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background-color: #f0f0f0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px 0;
        }

        .register-wrapper {
            background: #fff;
            border: 1px solid #ccc;
            padding: 30px;
            width: 380px;
            border-radius: 6px;
        }

        .register-wrapper h2 {
            text-align: center;
            margin-bottom: 6px;
            font-size: 20px;
            color: #333;
        }

        .register-wrapper p.sub {
            text-align: center;
            font-size: 13px;
            color: #777;
            margin-bottom: 24px;
        }

        .form-group {
            margin-bottom: 14px;
        }

        .form-group label {
            display: block;
            font-size: 13px;
            margin-bottom: 5px;
            color: #444;
        }

        .form-group input {
            width: 100%;
            padding: 8px 10px;
            border: 1px solid #bbb;
            border-radius: 4px;
            font-size: 14px;
            outline: none;
        }

        .form-group input:focus {
            border-color: #3a7bd5;
        }

        .form-group .hint {
            font-size: 11px;
            color: #999;
            margin-top: 3px;
        }

        .btn-register {
            width: 100%;
            padding: 10px;
            background-color: #27ae60;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 14px;
            cursor: pointer;
            margin-top: 6px;
        }

        .btn-register:hover {
            background-color: #219150;
        }

        .alert-error {
            background-color: #fdecea;
            border: 1px solid #f5c2c7;
            color: #842029;
            padding: 9px 12px;
            border-radius: 4px;
            font-size: 13px;
            margin-bottom: 16px;
        }

        .alert-success {
            background-color: #d1e7dd;
            border: 1px solid #a3cfbb;
            color: #0a3622;
            padding: 9px 12px;
            border-radius: 4px;
            font-size: 13px;
            margin-bottom: 16px;
        }

        .divider {
            border: none;
            border-top: 1px solid #eee;
            margin: 20px 0;
        }

        .footer-text {
            text-align: center;
            font-size: 13px;
            color: #666;
        }

        .footer-text a {
            color: #3a7bd5;
            text-decoration: none;
        }

        .footer-text a:hover {
            text-decoration: underline;
        }

        .copyright {
            text-align: center;
            font-size: 11px;
            color: #aaa;
            margin-top: 10px;
        }
    </style>
</head>
<body>

<div class="register-wrapper">
    <h2>🎥 Rental Kamera</h2>
    <p class="sub">Buat akun baru untuk mengakses sistem</p>

    <?php if (!empty($error)): ?>
        <div class="alert-error">⚠️ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <div class="alert-success">
            ✅ <?= htmlspecialchars($success) ?><br>
            <a href="login.php" style="color:#0a3622;font-weight:bold;">Klik di sini untuk login »</a>
        </div>
    <?php endif; ?>

    <?php if (empty($success)): ?>
    <form method="POST" action="">
        <div class="form-group">
            <label for="nama_lengkap">Nama Lengkap <span style="color:red">*</span></label>
            <input type="text" id="nama_lengkap" name="nama_lengkap"
                   value="<?= htmlspecialchars($_POST['nama_lengkap'] ?? '') ?>"
                   placeholder="Nama lengkap kamu" autocomplete="off">
        </div>

        <div class="form-group">
            <label for="username">Username <span style="color:red">*</span></label>
            <input type="text" id="username" name="username"
                   value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                   placeholder="Minimal 4 karakter" autocomplete="off">
            <div class="hint">Huruf kecil, tanpa spasi</div>
        </div>

        <div class="form-group">
            <label for="email">Email <span style="color:red">*</span></label>
            <input type="email" id="email" name="email"
                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                   placeholder="contoh@email.com">
        </div>

        <div class="form-group">
            <label for="password">Password <span style="color:red">*</span></label>
            <input type="password" id="password" name="password"
                   placeholder="Minimal 6 karakter">
        </div>

        <div class="form-group">
            <label for="konfirmasi">Konfirmasi Password <span style="color:red">*</span></label>
            <input type="password" id="konfirmasi" name="konfirmasi"
                   placeholder="Ulangi password">
        </div>

        <button type="submit" class="btn-register">Daftar Sekarang</button>
    </form>
    <?php endif; ?>

    <hr class="divider">
    <p class="footer-text">Sudah punya akun? <a href="login.php">Login di sini</a></p>
    <p class="copyright">Sistem Rental Kamera &copy; <?= date('Y') ?></p>
</div>

</body>
</html>