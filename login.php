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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Rental Kamera</title>
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
        }

        .login-wrapper {
            background: #fff;
            border: 1px solid #ccc;
            padding: 30px;
            width: 350px;
            border-radius: 6px;
        }

        .login-wrapper h2 {
            text-align: center;
            margin-bottom: 6px;
            font-size: 20px;
            color: #333;
        }

        .login-wrapper p.sub {
            text-align: center;
            font-size: 13px;
            color: #777;
            margin-bottom: 24px;
        }

        .form-group {
            margin-bottom: 16px;
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

        .btn-login {
            width: 100%;
            padding: 10px;
            background-color: #3a7bd5;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 14px;
            cursor: pointer;
            margin-top: 4px;
        }

        .btn-login:hover {
            background-color: #2f64b0;
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

        .divider {
            border: none;
            border-top: 1px solid #eee;
            margin: 20px 0;
        }

        .footer-text {
            text-align: center;
            font-size: 12px;
            color: #aaa;
        }
    </style>
</head>
<body>

<div class="login-wrapper">
    <h2>🎥 Rental Kamera</h2>
    <p class="sub">Masuk ke sistem manajemen sewa</p>

    <?php if (!empty($error)): ?>
        <div class="alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" id="username" name="username"
                   value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                   placeholder="Masukkan username" autocomplete="off">
        </div>
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password"
                   placeholder="Masukkan password">
        </div>
        <button type="submit" class="btn-login">Masuk</button>
    </form>

    <hr class="divider">
    <p class="footer-text" style="font-size:13px;color:#666;">
        Belum punya akun? <a href="register.php" style="color:#3a7bd5;text-decoration:none;">Daftar di sini</a>
    </p>
    <p class="footer-text" style="margin-top:8px;">Sistem Rental Kamera &copy; <?= date('Y') ?></p>
</div>

</body>
</html>