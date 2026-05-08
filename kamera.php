<?php
session_start();
require_once 'koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Hapus kamera
if (isset($_GET['hapus'])) {
    $id = (int) $_GET['hapus'];
    $cek = $conn->prepare("SELECT id FROM penyewaan WHERE id_kamera = ? AND status = 'dipinjam'");
    $cek->bind_param("i", $id);
    $cek->execute();
    $cek->store_result();
    if ($cek->num_rows > 0) {
        $pesan_error = "Kamera tidak bisa dihapus karena sedang disewa!";
    } else {
        $conn->query("DELETE FROM kamera WHERE id = $id");
        header("Location: kamera.php?notif=hapus");
        exit;
    }
}

$notif = isset($_GET['notif']) ? $_GET['notif'] : '';

// Filter & pencarian
$cari   = isset($_GET['cari'])   ? trim($_GET['cari'])   : '';
$filter = isset($_GET['status']) ? trim($_GET['status']) : '';

$where = "WHERE 1=1";
if (!empty($cari))   $where .= " AND (nama_kamera LIKE '%".mysqli_real_escape_string($conn,$cari)."%' OR kode_kamera LIKE '%".mysqli_real_escape_string($conn,$cari)."%' OR merk LIKE '%".mysqli_real_escape_string($conn,$cari)."%')";
if (!empty($filter)) $where .= " AND status = '".mysqli_real_escape_string($conn,$filter)."'";

$kamera_list = $conn->query("SELECT * FROM kamera $where ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Kamera - Rental Kamera</title>
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

        .toolbar {
            display: flex; justify-content: space-between;
            align-items: center; margin-bottom: 14px; gap: 10px; flex-wrap: wrap;
        }
        .toolbar-left { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; }
        .toolbar input[type="text"], .toolbar select {
            padding: 7px 10px; border: 1px solid #bbb;
            border-radius: 4px; font-size: 13px; outline: none;
        }
        .toolbar input[type="text"]:focus, .toolbar select:focus { border-color: #3a7bd5; }

        .btn {
            padding: 7px 14px; border: none; border-radius: 4px;
            font-size: 13px; cursor: pointer; text-decoration: none; display: inline-block;
        }
        .btn-sm { padding: 4px 10px; font-size: 12px; }
        .btn-primary   { background: #3a7bd5; color: white; }
        .btn-primary:hover { background: #2f64b0; }
        .btn-success   { background: #27ae60; color: white; }
        .btn-success:hover { background: #219150; }
        .btn-warning   { background: #e67e22; color: white; }
        .btn-warning:hover { background: #cf6d17; }
        .btn-danger    { background: #e74c3c; color: white; }
        .btn-danger:hover  { background: #c0392b; }
        .btn-secondary { background: #95a5a6; color: white; }
        .btn-secondary:hover { background: #7f8c8d; }

        .section-box {
            background: white; border: 1px solid #ddd;
            border-radius: 6px; overflow: hidden;
        }
        .section-head {
            padding: 12px 16px; border-bottom: 1px solid #eee;
            font-size: 14px; font-weight: bold;
            display: flex; justify-content: space-between; align-items: center;
        }

        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        th, td { text-align: left; padding: 9px 16px; border-bottom: 1px solid #f0f0f0; }
        th { background-color: #fafafa; color: #555; font-weight: bold; font-size: 12px; }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background-color: #fafafa; }

        .badge {
            display: inline-block; padding: 2px 8px;
            border-radius: 4px; font-size: 11px; font-weight: bold;
        }
        .badge-tersedia { background: #d1e7dd; color: #0a3622; }
        .badge-disewa   { background: #fff3cd; color: #856404; }
        .badge-rusak    { background: #f8d7da; color: #842029; }

        .alert {
            padding: 10px 14px; border-radius: 4px;
            font-size: 13px; margin-bottom: 14px;
        }
        .alert-success { background: #d1e7dd; border: 1px solid #a3cfbb; color: #0a3622; }
        .alert-danger  { background: #fdecea; border: 1px solid #f5c2c7; color: #842029; }

        .rupiah { font-family: 'Courier New', monospace; }
        .empty-state { text-align: center; padding: 40px; color: #aaa; font-size: 14px; }
        .aksi { white-space: nowrap; }
        .aksi .btn { margin-right: 4px; }
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
        <a href="pelanggan.php">👤 Pelanggan</a>
        <a href="penyewaan.php">📋 Penyewaan</a>
        <div class="menu-title">Data</div>
        <a href="add.php">➕ Tambah Kamera</a>
        <a href="laporan.php">📄 Laporan</a>
        <div class="menu-title">Akun</div>
        <a href="logout.php">🚪 Logout</a>
    </div>

    <div class="content">
        <div class="page-title">📷 Data Kamera</div>
        <div class="page-sub">Kelola inventaris kamera yang tersedia untuk disewa.</div>

        <?php if ($notif == 'tambah'): ?>
            <div class="alert alert-success">✅ Kamera berhasil ditambahkan!</div>
        <?php elseif ($notif == 'edit'): ?>
            <div class="alert alert-success">✅ Data kamera berhasil diperbarui!</div>
        <?php elseif ($notif == 'hapus'): ?>
            <div class="alert alert-success">✅ Kamera berhasil dihapus!</div>
        <?php elseif (isset($pesan_error)): ?>
            <div class="alert alert-danger">⚠️ <?= htmlspecialchars($pesan_error) ?></div>
        <?php endif; ?>

        <!-- Toolbar -->
        <div class="toolbar">
            <div class="toolbar-left">
                <form method="GET" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
                    <input type="text" name="cari" placeholder="Cari nama / kode / merk..." value="<?= htmlspecialchars($cari) ?>">
                    <select name="status">
                        <option value="">-- Semua Status --</option>
                        <option value="tersedia" <?= $filter=='tersedia' ? 'selected':'' ?>>Tersedia</option>
                        <option value="disewa"   <?= $filter=='disewa'   ? 'selected':'' ?>>Disewa</option>
                        <option value="rusak"    <?= $filter=='rusak'    ? 'selected':'' ?>>Rusak</option>
                    </select>
                    <button type="submit" class="btn btn-primary">Cari</button>
                    <?php if (!empty($cari) || !empty($filter)): ?>
                        <a href="kamera.php" class="btn btn-secondary">Reset</a>
                    <?php endif; ?>
                </form>
            </div>
            <a href="add_kamera.php" class="btn btn-success">+ Tambah Kamera</a>
        </div>

        <!-- Tabel -->
        <div class="section-box">
            <div class="section-head">
                <span>Daftar Kamera <span style="color:#999;font-weight:normal;font-size:13px;">(<?= $kamera_list->num_rows ?> data)</span></span>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Kode</th>
                        <th>Nama Kamera</th>
                        <th>Merk</th>
                        <th>Tipe</th>
                        <th>Harga/Hari</th>
                        <th>Stok</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($kamera_list->num_rows > 0): ?>
                    <?php $no = 1; while ($row = $kamera_list->fetch_assoc()): ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><code><?= htmlspecialchars($row['kode_kamera']) ?></code></td>
                        <td><?= htmlspecialchars($row['nama_kamera']) ?></td>
                        <td><?= htmlspecialchars($row['merk']) ?></td>
                        <td><?= htmlspecialchars($row['tipe']) ?></td>
                        <td class="rupiah">Rp <?= number_format($row['harga_sewa'], 0, ',', '.') ?></td>
                        <td><?= $row['stok'] ?> unit</td>
                        <td><span class="badge badge-<?= $row['status'] ?>"><?= ucfirst($row['status']) ?></span></td>
                        <td class="aksi">
                            <a href="edit_kamera.php?id=<?= $row['id'] ?>" class="btn btn-warning btn-sm">Edit</a>
                            <a href="kamera.php?hapus=<?= $row['id'] ?>"
                               class="btn btn-danger btn-sm"
                               onclick="return confirm('Yakin ingin menghapus kamera ini?')">Hapus</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="9"><div class="empty-state">📷 Belum ada data kamera.<br><a href="add.php">Tambah kamera sekarang »</a></div></td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>
</div>

</body>
</html>