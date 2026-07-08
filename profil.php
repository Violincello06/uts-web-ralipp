<?php
session_start();
require_once 'koneksi.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = (int) $_SESSION['user_id'];
$error = '';
$success = '';
$uploadDir = 'assets/images/profile/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Pastikan kolom avatar ada di tabel users
$columnExists = $conn->query("SHOW COLUMNS FROM users LIKE 'avatar'")->num_rows > 0;
if (!$columnExists) {
    $conn->query("ALTER TABLE users ADD avatar VARCHAR(255) NULL AFTER email");
}

$user = $conn->query("SELECT username, nama_lengkap, email, password, avatar FROM users WHERE id = $user_id")->fetch_assoc();
if (!$user) {
    header('Location: logout.php');
    exit;
}

$avatarPath = $user['avatar'];
$hashedPassword = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
    $username     = trim($_POST['username'] ?? '');
    $email        = trim($_POST['email'] ?? '');
    $oldPassword  = trim($_POST['old_password'] ?? '');
    $newPassword  = trim($_POST['new_password'] ?? '');
    $confirmPassword = trim($_POST['confirm_password'] ?? '');

    if (empty($nama_lengkap) || empty($username) || empty($email)) {
        $error = 'Nama lengkap, username, dan email wajib diisi.';
    } elseif (strlen($username) < 4) {
        $error = 'Username minimal 4 karakter.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';
    } else {
        // Username unik
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $stmt->bind_param('si', $username, $user_id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $error = 'Username sudah digunakan oleh pengguna lain.';
            $stmt->close();
        } else {
            $stmt->close();

            $passwordUpdate = false;
            if ($oldPassword !== '' || $newPassword !== '' || $confirmPassword !== '') {
                if (empty($oldPassword) || empty($newPassword) || empty($confirmPassword)) {
                    $error = 'Isi semua kolom password untuk mengganti password.';
                } elseif (!password_verify($oldPassword, $user['password'])) {
                    $error = 'Password lama tidak cocok.';
                } elseif (strlen($newPassword) < 6) {
                    $error = 'Password baru minimal 6 karakter.';
                } elseif ($newPassword !== $confirmPassword) {
                    $error = 'Konfirmasi password tidak cocok.';
                } else {
                    $passwordUpdate = true;
                    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                }
            }

            $avatarFilename = '';
            if (empty($error) && isset($_FILES['avatar']) && $_FILES['avatar']['error'] !== UPLOAD_ERR_NO_FILE) {
                $avatarFile = $_FILES['avatar'];
                if ($avatarFile['error'] !== UPLOAD_ERR_OK) {
                    $error = 'Gagal mengunggah foto profil.';
                } else {
                    $allowed = ['jpg','jpeg','png','webp'];
                    $ext = strtolower(pathinfo($avatarFile['name'], PATHINFO_EXTENSION));
                    if (!in_array($ext, $allowed, true)) {
                        $error = 'Tipe file tidak diperbolehkan. Gunakan JPG, PNG, atau WEBP.';
                    } elseif ($avatarFile['size'] > 2 * 1024 * 1024) {
                        $error = 'Ukuran foto maksimal 2 MB.';
                    } else {
                        $avatarFilename = 'avatar_' . $user_id . '_' . time() . '.' . $ext;
                        $targetPath = $uploadDir . $avatarFilename;
                        if (!move_uploaded_file($avatarFile['tmp_name'], $targetPath)) {
                            $error = 'Gagal menyimpan foto profil.';
                        } else {
                            if (!empty($avatarPath) && file_exists($avatarPath)) {
                                @unlink($avatarPath);
                            }
                            $avatarPath = $targetPath;
                        }
                    }
                }
            }

            if (empty($error)) {
                $fields = ['username = ?', 'nama_lengkap = ?', 'email = ?'];
                $types = 'sss';
                $params = [$username, $nama_lengkap, $email];

                if ($passwordUpdate) {
                    $fields[] = 'password = ?';
                    $types .= 's';
                    $params[] = $hashedPassword;
                }
                if (!empty($avatarPath)) {
                    $fields[] = 'avatar = ?';
                    $types .= 's';
                    $params[] = $avatarPath;
                }

                $query = 'UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = ?';
                $types .= 'i';
                $params[] = $user_id;
                $stmt = $conn->prepare($query);
                $stmt->bind_param($types, ...$params);
                if ($stmt->execute()) {
                    $success = 'Profil berhasil diperbarui.';
                    $_SESSION['username'] = $username;
                    $_SESSION['nama_lengkap'] = $nama_lengkap;
                    $_SESSION['avatar'] = $avatarPath;
                    $user['username'] = $username;
                    $user['nama_lengkap'] = $nama_lengkap;
                    $user['email'] = $email;
                    if ($passwordUpdate) {
                        $user['password'] = $hashedPassword;
                    }
                    $user['avatar'] = $avatarPath;
                } else {
                    $error = 'Terjadi kesalahan saat menyimpan profil.';
                }
                $stmt->close();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Edit Profil - Rental Kamera</title>
  <link rel="stylesheet" href="assets/css/bootstrap.min.css"/>
  <link rel="stylesheet" href="assets/css/lineicons.css"/>
  <link rel="stylesheet" href="assets/css/materialdesignicons.min.css"/>
  <link rel="stylesheet" href="assets/css/main.css"/>
  <link rel="stylesheet" href="assets/css/custom.css"/>
  <?php include 'partials/theme_head.php'; ?>
</head>
<body>

<?php include 'partials/sidebar.php'; ?>

<main class="main-wrapper">
  <?php include 'partials/topbar.php'; ?>

  <section class="section">
    <div class="container-fluid">
      <div class="title-wrapper pt-30 mb-20">
        <div class="row align-items-center">
          <div class="col-md-6">
            <div class="title"><h2>Edit Profil</h2></div>
          </div>
          <div class="col-md-6">
            <div class="breadcrumb-wrapper">
              <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                  <li class="breadcrumb-item"><a href="main.php">Dashboard</a></li>
                  <li class="breadcrumb-item active">Edit Profil</li>
                </ol>
              </nav>
            </div>
          </div>
        </div>
      </div>

      <?php if (!empty($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
          <?= htmlspecialchars($error) ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php elseif (!empty($success)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
          <?= htmlspecialchars($success) ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>

      <div class="row">
        <div class="col-lg-8">
          <div class="card-style mb-30">
            <h6 class="mb-20 text-medium">Informasi Akun</h6>
            <form method="POST" action="" enctype="multipart/form-data">
              <div class="row">
                <div class="col-12 col-md-6">
                  <div class="input-style-1">
                    <label>Nama Lengkap <span class="text-danger">*</span></label>
                    <input type="text" name="nama_lengkap" value="<?= htmlspecialchars($user['nama_lengkap']) ?>" required />
                  </div>
                </div>
                <div class="col-12 col-md-6">
                  <div class="input-style-1">
                    <label>Username <span class="text-danger">*</span></label>
                    <input type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>" required />
                  </div>
                </div>
              </div>

              <div class="input-style-1">
                <label>Email <span class="text-danger">*</span></label>
                <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required />
              </div>

              <div class="input-style-1 mb-20">
                <label>Foto Profil</label>
                <input type="file" name="avatar" accept="image/*" />
                <p class="text-xs text-gray mt-1">Unggah foto JPG/PNG/WEBP maksimal 2 MB. Biarkan kosong jika tidak ingin mengganti.</p>
              </div>

              <?php if (!empty($user['avatar'])): ?>
                <div class="mb-20">
                  <p class="text-sm text-gray mb-2">Preview foto profil saat ini:</p>
                  <img src="<?= htmlspecialchars($user['avatar']) ?>" alt="Avatar" style="max-width:120px; border-radius:12px; object-fit:cover;" />
                </div>
              <?php endif; ?>

              <div class="pt-20 pb-10">
                <h6 class="mb-15 text-medium">Reset Password</h6>
                <div class="row">
                  <div class="col-12 col-md-4">
                    <div class="input-style-1">
                      <label>Password Lama</label>
                      <input type="password" name="old_password" placeholder="Password lama" />
                    </div>
                  </div>
                  <div class="col-12 col-md-4">
                    <div class="input-style-1">
                      <label>Password Baru</label>
                      <input type="password" name="new_password" placeholder="Password baru" />
                    </div>
                  </div>
                  <div class="col-12 col-md-4">
                    <div class="input-style-1">
                      <label>Konfirmasi Password</label>
                      <input type="password" name="confirm_password" placeholder="Ulangi password baru" />
                    </div>
                  </div>
                </div>
                <p class="text-xs text-gray mt-1">Isi semua kolom jika ingin mengganti password.</p>
              </div>

              <div class="d-flex gap-2">
                <button type="submit" class="main-btn primary-btn btn-hover">
                  <i class="lni lni-save me-1"></i> Simpan Perubahan
                </button>
                <a href="main.php" class="main-btn deactive-btn-2">Batal</a>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </section>

</main>

<script src="assets/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/main.js"></script>
</body>
</html>
