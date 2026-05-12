<?php
session_start();
require_once 'koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
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

    // Validasi
    if (empty($kode_kamera) || empty($nama_kamera) || empty($harga_sewa)) {
        $error = 'Kode kamera, nama kamera, dan harga sewa wajib diisi!';
    } elseif (!is_numeric($harga_sewa) || $harga_sewa < 0) {
        $error = 'Harga sewa harus berupa angka positif!';
    } elseif ($stok < 0) {
        $error = 'Stok tidak boleh negatif!';
    } else {
        // Cek kode kamera duplikat
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

// Auto-generate kode kamera
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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Kamera - Rental Kamera</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background-color: #f4f4f4; color: #333; }

        .navbar {
            background-color: #3a7bd5; color: white;
            padding: 12px 20px;
            display: flex; justify-content: space-between; align-items: center;
        }
        .navbar .brand { font-size: 18px; font-weight: bold; }
        .navbar a { color: white; text-decoration: none; font-size: 13px; }
        .navbar a:hover { text-decoration: underline; }

        .wrapper { display: flex; min-height: calc(100vh - 48px); }

        .sidebar { width: 200px; background-color: #2c3e50; padding: 16px 0; flex-shrink: 0; }
        .sidebar a {
            display: block; color: #ccc; text-decoration: none;
            padding: 10px 20px; font-size: 14px; border-left: 3px solid transparent;
        }
        .sidebar a:hover, .sidebar a.active {
            background-color: #3d5166; color: white; border-left-color: #3a7bd5;
        }
        .sidebar .menu-title {
            font-size: 11px; text-transform: uppercase; color: #666;
            padding: 14px 20px 6px; letter-spacing: 0.05em;
        }

        .content { flex: 1; padding: 24px; }
        .page-title { font-size: 20px; margin-bottom: 4px; }
        .page-sub { font-size: 13px; color: #777; margin-bottom: 20px; }

        .form-box {
            background: white; border: 1px solid #ddd;
            border-radius: 6px; max-width: 600px;
        }
        .form-head {
            padding: 12px 16px; border-bottom: 1px solid #eee;
            font-size: 14px; font-weight: bold;
        }
        .form-body { padding: 20px; }

        .form-group { margin-bottom: 16px; }
        .form-group label {
            display: block; font-size: 13px;
            margin-bottom: 5px; color: #444;
        }
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%; padding: 8px 10px;
            border: 1px solid #bbb; border-radius: 4px;
            font-size: 14px; outline: none; font-family: Arial, sans-serif;
        }
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus { border-color: #3a7bd5; }
        .form-group textarea { resize: vertical; min-height: 80px; }
        .form-group .hint { font-size: 11px; color: #999; margin-top: 3px; }

        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }

        .form-footer {
            padding: 14px 20px; border-top: 1px solid #eee;
            display: flex; gap: 10px;
        }

        .btn {
            padding: 8px 18px; border: none; border-radius: 4px;
            font-size: 13px; cursor: pointer; text-decoration: none; display: inline-block;
        }
        .btn-success { background: #27ae60; color: white; }
        .btn-success:hover { background: #219150; }
        .btn-secondary { background: #95a5a6; color: white; }
        .btn-secondary:hover { background: #7f8c8d; }

        .alert-danger {
            background: #fdecea; border: 1px solid #f5c2c7;
            color: #842029; padding: 10px 14px;
            border-radius: 4px; font-size: 13px; margin-bottom: 16px;
        }

        .required { color: red; }
    </style>
</head>
<body>

<div class="navbar">
    <div class="brand">🎥 Rental Kamera</div>
    <div style="display:flex;gap:16px;align-items:center;">
        <span style="font-size:13px;">Halo, <strong><?= htmlspecialchars($_SESSION['nama_lengkap']) ?></strong></span>
        <a href="logout.php">Keluar</a>
    </div>
</div>

<div class="wrapper">
    <div class="sidebar">
        <div class="menu-title">Menu Utama</div>
        <a href="main.php">📊 Dashboard</a>
        <a href="kamera.php">📷 Data Kamera</a>
        <div class="menu-title">Data</div>
        <a href="add.php" class="active">➕ Tambah Kamera</a>
        <a href="laporan.php">📄 Laporan</a>
        <div class="menu-title">Akun</div>
        <a href="logout.php">🚪 Logout</a>
    </div>

    <div class="content">
        <div class="page-title">➕ Tambah Kamera</div>
        <div class="page-sub">Isi form di bawah untuk menambahkan kamera baru ke inventaris.</div>

        <div class="form-box">
            <div class="form-head">Form Tambah Kamera</div>
            <div class="form-body">

                <?php if (!empty($error)): ?>
                    <div class="alert-danger">⚠️ <?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="POST" action="">

                    <div class="form-row">
                        <div class="form-group">
                            <label>Kode Kamera <span class="required">*</span></label>
                            <input type="text" name="kode_kamera"
                                   value="<?= htmlspecialchars($_POST['kode_kamera'] ?? $next_kode) ?>"
                                   placeholder="Contoh: KAM-001">
                            <div class="hint">Kode unik untuk setiap kamera</div>
                        </div>
                        <div class="form-group">
                            <label>Status <span class="required">*</span></label>
                            <select name="status">
                                <option value="tersedia" <?= ($_POST['status'] ?? '') == 'tersedia' ? 'selected':'' ?>>Tersedia</option>
                                <option value="disewa"   <?= ($_POST['status'] ?? '') == 'disewa'   ? 'selected':'' ?>>Disewa</option>
                                <option value="rusak"    <?= ($_POST['status'] ?? '') == 'rusak'    ? 'selected':'' ?>>Rusak</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Nama Kamera <span class="required">*</span></label>
                        <input type="text" name="nama_kamera"
                               value="<?= htmlspecialchars($_POST['nama_kamera'] ?? '') ?>"
                               placeholder="Contoh: Canon EOS 800D">
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Merk</label>
                            <input type="text" name="merk"
                                   value="<?= htmlspecialchars($_POST['merk'] ?? '') ?>"
                                   placeholder="Canon, Sony, Nikon, dll">
                        </div>
                        <div class="form-group">
                            <label>Tipe</label>
                            <select name="tipe">
                                <option value="">-- Pilih Tipe --</option>
                                <?php
                                $tipe_list = ['DSLR','Mirrorless','Action Cam','Pocket','Medium Format','Film'];
                                foreach ($tipe_list as $t):
                                    $sel = ($_POST['tipe'] ?? '') == $t ? 'selected' : '';
                                ?>
                                <option value="<?= $t ?>" <?= $sel ?>><?= $t ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Harga Sewa / Hari (Rp) <span class="required">*</span></label>
                            <input type="number" name="harga_sewa" min="0"
                                   value="<?= htmlspecialchars($_POST['harga_sewa'] ?? '') ?>"
                                   placeholder="Contoh: 150000">
                        </div>
                        <div class="form-group">
                            <label>Stok (unit)</label>
                            <input type="number" name="stok" min="0"
                                   value="<?= htmlspecialchars($_POST['stok'] ?? '1') ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Deskripsi</label>
                        <textarea name="deskripsi" placeholder="Spesifikasi singkat, kondisi kamera, kelengkapan, dll..."><?= htmlspecialchars($_POST['deskripsi'] ?? '') ?></textarea>
                    </div>

                    <div class="form-footer" style="padding:0;border:none;margin-top:4px;">
                        <button type="submit" class="btn btn-success">💾 Simpan Kamera</button>
                        <a href="kamera.php" class="btn btn-secondary">Batal</a>
                    </div>
                </form>
            </div>
        </div>

    </div>
</div>

</body>
</html>