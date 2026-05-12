<?php
session_start();
require_once 'koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
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
    } else {
        // Cek kode duplikat (selain id ini)
        $cek = $conn->prepare("SELECT id FROM kamera WHERE kode_kamera = ? AND id != ?");
        $cek->bind_param("si", $kode_kamera, $id);
        $cek->execute();
        $cek->store_result();

        if ($cek->num_rows > 0) {
            $error = 'Kode kamera sudah digunakan oleh kamera lain!';
        } else {
            $stmt = $conn->prepare("UPDATE kamera SET kode_kamera=?, nama_kamera=?, merk=?, tipe=?, harga_sewa=?, stok=?, deskripsi=?, status=? WHERE id=?");
            $stmt->bind_param("ssssdisdi", $kode_kamera, $nama_kamera, $merk, $tipe, $harga_sewa, $stok, $deskripsi, $status, $id);

            if ($stmt->execute()) {
                header("Location: kamera.php?notif=edit");
                exit;
            } else {
                $error = 'Gagal memperbarui data, coba lagi!';
            }
        }
    }

    // Update tampilan form dengan data POST
    $data = [
        'kode_kamera' => $kode_kamera,
        'nama_kamera' => $nama_kamera,
        'merk'        => $merk,
        'tipe'        => $tipe,
        'harga_sewa'  => $harga_sewa,
        'stok'        => $stok,
        'deskripsi'   => $deskripsi,
        'status'      => $status,
    ];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Kamera - Rental Kamera</title>
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
        .form-group label { display: block; font-size: 13px; margin-bottom: 5px; color: #444; }
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

        .btn {
            padding: 8px 18px; border: none; border-radius: 4px;
            font-size: 13px; cursor: pointer; text-decoration: none; display: inline-block;
        }
        .btn-warning   { background: #e67e22; color: white; }
        .btn-warning:hover { background: #cf6d17; }
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
        <a href="kamera.php" class="active">📷 Data Kamera</a>
        <div class="menu-title">Data</div>
        <a href="add.php">➕ Tambah Kamera</a>
        <a href="laporan.php">📄 Laporan</a>
        <div class="menu-title">Akun</div>
        <a href="logout.php">🚪 Logout</a>
    </div>

    <div class="content">
        <div class="page-title">✏️ Edit Kamera</div>
        <div class="page-sub">Perbarui data kamera yang sudah ada.</div>

        <div class="form-box">
            <div class="form-head">Form Edit Kamera</div>
            <div class="form-body">

                <?php if (!empty($error)): ?>
                    <div class="alert-danger">⚠️ <?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="POST" action="">

                    <div class="form-row">
                        <div class="form-group">
                            <label>Kode Kamera <span class="required">*</span></label>
                            <input type="text" name="kode_kamera"
                                   value="<?= htmlspecialchars($data['kode_kamera']) ?>">
                        </div>
                        <div class="form-group">
                            <label>Status <span class="required">*</span></label>
                            <select name="status">
                                <option value="tersedia" <?= $data['status']=='tersedia' ? 'selected':'' ?>>Tersedia</option>
                                <option value="disewa"   <?= $data['status']=='disewa'   ? 'selected':'' ?>>Disewa</option>
                                <option value="rusak"    <?= $data['status']=='rusak'    ? 'selected':'' ?>>Rusak</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Nama Kamera <span class="required">*</span></label>
                        <input type="text" name="nama_kamera"
                               value="<?= htmlspecialchars($data['nama_kamera']) ?>">
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Merk</label>
                            <input type="text" name="merk"
                                   value="<?= htmlspecialchars($data['merk']) ?>">
                        </div>
                        <div class="form-group">
                            <label>Tipe</label>
                            <select name="tipe">
                                <option value="">-- Pilih Tipe --</option>
                                <?php
                                $tipe_list = ['DSLR','Mirrorless','Action Cam','Pocket','Medium Format','Film'];
                                foreach ($tipe_list as $t):
                                    $sel = $data['tipe'] == $t ? 'selected' : '';
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
                                   value="<?= htmlspecialchars($data['harga_sewa']) ?>">
                        </div>
                        <div class="form-group">
                            <label>Stok (unit)</label>
                            <input type="number" name="stok" min="0"
                                   value="<?= htmlspecialchars($data['stok']) ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Deskripsi</label>
                        <textarea name="deskripsi"><?= htmlspecialchars($data['deskripsi']) ?></textarea>
                    </div>

                    <div style="display:flex;gap:10px;margin-top:4px;">
                        <button type="submit" class="btn btn-warning">💾 Simpan Perubahan</button>
                        <a href="kamera.php" class="btn btn-secondary">Batal</a>
                    </div>

                </form>
            </div>
        </div>

    </div>
</div>

</body>
</html>