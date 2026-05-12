<?php
session_start();
require_once 'koneksi.php';
<<<<<<< HEAD

=======
 
>>>>>>> 1b7042cad935a134c88c013e814ecf0ba9384291
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
<<<<<<< HEAD

// Statistik kamera
$total_kamera = $conn->query("SELECT COUNT(*) as total FROM kamera")->fetch_assoc()['total'];
$tersedia     = $conn->query("SELECT COUNT(*) as total FROM kamera WHERE status='tersedia'")->fetch_assoc()['total'];
$disewa       = $conn->query("SELECT COUNT(*) as total FROM kamera WHERE status='disewa'")->fetch_assoc()['total'];
$rusak        = $conn->query("SELECT COUNT(*) as total FROM kamera WHERE status='rusak'")->fetch_assoc()['total'];

// Semua kamera terbaru
$kamera_list = $conn->query("SELECT * FROM kamera ORDER BY created_at DESC LIMIT 10");
=======
 
// Ambil data statistik
$total_kamera    = $conn->query("SELECT COUNT(*) as total FROM kamera")->fetch_assoc()['total'];
$tersedia        = $conn->query("SELECT COUNT(*) as total FROM kamera WHERE status='tersedia'")->fetch_assoc()['total'];
$total_pelanggan = $conn->query("SELECT COUNT(*) as total FROM pelanggan")->fetch_assoc()['total'];
$aktif_sewa      = $conn->query("SELECT COUNT(*) as total FROM penyewaan WHERE status='dipinjam'")->fetch_assoc()['total'];
$pendapatan      = $conn->query("SELECT SUM(total_bayar) as total FROM penyewaan WHERE status='dikembalikan'")->fetch_assoc()['total'] ?? 0;
 
// Transaksi terbaru
$transaksi_terbaru = $conn->query("
    SELECT p.kode_sewa, pl.nama, k.nama_kamera, p.tanggal_sewa, p.tanggal_kembali, p.total_bayar, p.status
    FROM penyewaan p
    JOIN pelanggan pl ON p.id_pelanggan = pl.id
    JOIN kamera k ON p.id_kamera = k.id
    ORDER BY p.created_at DESC
    LIMIT 5
");
 
// Kamera tersedia
$kamera_list = $conn->query("SELECT * FROM kamera ORDER BY created_at DESC LIMIT 5");
>>>>>>> 1b7042cad935a134c88c013e814ecf0ba9384291
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Rental Kamera</title>
    <style>
<<<<<<< HEAD
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body { font-family: Arial, sans-serif; background-color: #f4f4f4; color: #333; }

=======
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
 
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            color: #333;
        }
 
        /* ---- NAVBAR ---- */
>>>>>>> 1b7042cad935a134c88c013e814ecf0ba9384291
        .navbar {
            background-color: #3a7bd5;
            color: white;
            padding: 12px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
<<<<<<< HEAD
        .navbar .brand { font-size: 18px; font-weight: bold; }
        .navbar .nav-right { font-size: 13px; display: flex; align-items: center; gap: 16px; }
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

=======
 
        .navbar .brand {
            font-size: 18px;
            font-weight: bold;
        }
 
        .navbar .nav-right {
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 16px;
        }
 
        .navbar a {
            color: white;
            text-decoration: none;
            font-size: 13px;
        }
 
        .navbar a:hover {
            text-decoration: underline;
        }
 
        /* ---- SIDEBAR + CONTENT ---- */
        .wrapper {
            display: flex;
            min-height: calc(100vh - 48px);
        }
 
        .sidebar {
            width: 200px;
            background-color: #2c3e50;
            padding: 16px 0;
            flex-shrink: 0;
        }
 
        .sidebar a {
            display: block;
            color: #ccc;
            text-decoration: none;
            padding: 10px 20px;
            font-size: 14px;
            border-left: 3px solid transparent;
        }
 
        .sidebar a:hover,
        .sidebar a.active {
            background-color: #3d5166;
            color: white;
            border-left-color: #3a7bd5;
        }
 
        .sidebar .menu-title {
            font-size: 11px;
            text-transform: uppercase;
            color: #666;
            padding: 14px 20px 6px;
            letter-spacing: 0.05em;
        }
 
        .content {
            flex: 1;
            padding: 24px;
        }
 
        .page-title {
            font-size: 20px;
            margin-bottom: 4px;
        }
 
        .page-sub {
            font-size: 13px;
            color: #777;
            margin-bottom: 20px;
        }
 
        /* ---- CARDS ---- */
>>>>>>> 1b7042cad935a134c88c013e814ecf0ba9384291
        .cards {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 24px;
        }
<<<<<<< HEAD
=======
 
>>>>>>> 1b7042cad935a134c88c013e814ecf0ba9384291
        .card {
            background: white;
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 16px;
        }
<<<<<<< HEAD
        .card .card-label { font-size: 12px; color: #888; margin-bottom: 8px; }
        .card .card-value { font-size: 28px; font-weight: bold; color: #333; }
        .card .card-icon  { font-size: 28px; float: right; margin-top: -4px; }
=======
 
        .card .card-label {
            font-size: 12px;
            color: #888;
            margin-bottom: 8px;
        }
 
        .card .card-value {
            font-size: 26px;
            font-weight: bold;
            color: #333;
        }
 
        .card .card-icon {
            font-size: 28px;
            float: right;
            margin-top: -4px;
        }
 
>>>>>>> 1b7042cad935a134c88c013e814ecf0ba9384291
        .card.blue   { border-top: 3px solid #3a7bd5; }
        .card.green  { border-top: 3px solid #27ae60; }
        .card.orange { border-top: 3px solid #e67e22; }
        .card.red    { border-top: 3px solid #e74c3c; }
<<<<<<< HEAD

=======
 
        /* ---- TABLES ---- */
>>>>>>> 1b7042cad935a134c88c013e814ecf0ba9384291
        .section-box {
            background: white;
            border: 1px solid #ddd;
            border-radius: 6px;
<<<<<<< HEAD
            overflow: hidden;
            margin-bottom: 20px;
        }
        .section-head {
=======
            margin-bottom: 20px;
        }
 
        .section-box .section-head {
>>>>>>> 1b7042cad935a134c88c013e814ecf0ba9384291
            padding: 12px 16px;
            border-bottom: 1px solid #eee;
            font-size: 14px;
            font-weight: bold;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
<<<<<<< HEAD
        .section-head a { font-size: 12px; color: #3a7bd5; text-decoration: none; font-weight: normal; }

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

        .rupiah { font-family: 'Courier New', monospace; }
        .empty-state { text-align: center; padding: 40px; color: #aaa; font-size: 14px; }

        .bar-wrap { padding: 20px; }
        .bar-item { margin-bottom: 16px; }
        .bar-label { font-size: 13px; margin-bottom: 5px; display: flex; justify-content: space-between; }
        .bar-track { background: #f0f0f0; border-radius: 4px; height: 18px; width: 100%; }
        .bar-fill  { height: 18px; border-radius: 4px; }
        .bar-green  { background: #27ae60; }
        .bar-orange { background: #e67e22; }
        .bar-red    { background: #e74c3c; }
    </style>
</head>
<body>

=======
 
        .section-box .section-head a {
            font-size: 12px;
            color: #3a7bd5;
            text-decoration: none;
            font-weight: normal;
        }
 
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }
 
        th, td {
            text-align: left;
            padding: 9px 16px;
            border-bottom: 1px solid #f0f0f0;
        }
 
        th {
            background-color: #fafafa;
            color: #555;
            font-weight: bold;
            font-size: 12px;
        }
 
        tr:last-child td {
            border-bottom: none;
        }
 
        tr:hover td {
            background-color: #fafafa;
        }
 
        /* ---- BADGE STATUS ---- */
        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: bold;
        }
 
        .badge-dipinjam    { background: #fff3cd; color: #856404; }
        .badge-dikembalikan { background: #d1e7dd; color: #0a3622; }
        .badge-terlambat   { background: #f8d7da; color: #842029; }
        .badge-tersedia    { background: #d1e7dd; color: #0a3622; }
        .badge-disewa      { background: #fff3cd; color: #856404; }
        .badge-rusak       { background: #f8d7da; color: #842029; }
 
        /* ---- TWO COL ---- */
        .two-col {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
 
        .rupiah {
            font-family: 'Courier New', monospace;
        }
    </style>
</head>
<body>
 
<!-- NAVBAR -->
>>>>>>> 1b7042cad935a134c88c013e814ecf0ba9384291
<div class="navbar">
    <div class="brand">🎥 Rental Kamera</div>
    <div class="nav-right">
        <span>Halo, <strong><?= htmlspecialchars($_SESSION['nama_lengkap']) ?></strong></span>
        <a href="logout.php">Keluar</a>
    </div>
</div>
<<<<<<< HEAD

<div class="wrapper">

=======
 
<div class="wrapper">
 
    <!-- SIDEBAR -->
>>>>>>> 1b7042cad935a134c88c013e814ecf0ba9384291
    <div class="sidebar">
        <div class="menu-title">Menu Utama</div>
        <a href="main.php" class="active">📊 Dashboard</a>
        <a href="kamera.php">📷 Data Kamera</a>
<<<<<<< HEAD
        <div class="menu-title">Data</div>
        <a href="add_kamera.php">➕ Tambah Kamera</a>
        <div class="menu-title">Akun</div>
        <a href="logout.php">🚪 Logout</a>
    </div>

    <div class="content">
        <div class="page-title">Dashboard</div>
        <div class="page-sub">Selamat datang, <?= htmlspecialchars($_SESSION['nama_lengkap']) ?>. Berikut ringkasan inventaris kamera.</div>

=======
        <a href="pelanggan.php">👤 Pelanggan</a>
        <a href="penyewaan.php">📋 Penyewaan</a>
 
        <div class="menu-title">Data</div>
        <a href="add.php">➕ Tambah Data</a>
        <a href="laporan.php">📄 Laporan</a>
 
        <div class="menu-title">Akun</div>
        <a href="logout.php">🚪 Logout</a>
    </div>
 
    <!-- MAIN CONTENT -->
    <div class="content">
        <div class="page-title">Dashboard</div>
        <div class="page-sub">Selamat datang, <?= htmlspecialchars($_SESSION['nama_lengkap']) ?>. Berikut ringkasan data hari ini.</div>
 
>>>>>>> 1b7042cad935a134c88c013e814ecf0ba9384291
        <!-- CARDS -->
        <div class="cards">
            <div class="card blue">
                <div class="card-icon">📷</div>
                <div class="card-label">Total Kamera</div>
                <div class="card-value"><?= $total_kamera ?></div>
            </div>
            <div class="card green">
                <div class="card-icon">✅</div>
<<<<<<< HEAD
                <div class="card-label">Tersedia</div>
                <div class="card-value"><?= $tersedia ?></div>
            </div>
            <div class="card orange">
                <div class="card-icon">🔄</div>
                <div class="card-label">Sedang Disewa</div>
                <div class="card-value"><?= $disewa ?></div>
            </div>
            <div class="card red">
                <div class="card-icon">⚠️</div>
                <div class="card-label">Rusak</div>
                <div class="card-value"><?= $rusak ?></div>
            </div>
        </div>

        <!-- Tabel + Grafik -->
        <div style="display:grid;grid-template-columns:2fr 1fr;gap:16px;">

            <!-- Tabel kamera -->
            <div class="section-box">
                <div class="section-head">
                    Daftar Kamera Terbaru
=======
                <div class="card-label">Kamera Tersedia</div>
                <div class="card-value"><?= $tersedia ?></div>
            </div>
            <div class="card orange">
                <div class="card-icon">👥</div>
                <div class="card-label">Total Pelanggan</div>
                <div class="card-value"><?= $total_pelanggan ?></div>
            </div>
            <div class="card red">
                <div class="card-icon">📋</div>
                <div class="card-label">Sedang Disewa</div>
                <div class="card-value"><?= $aktif_sewa ?></div>
            </div>
        </div>
 
        <!-- TWO COLUMN -->
        <div class="two-col">
 
            <!-- Transaksi Terbaru -->
            <div class="section-box">
                <div class="section-head">
                    Transaksi Terbaru
                    <a href="penyewaan.php">Lihat semua »</a>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Kode</th>
                            <th>Pelanggan</th>
                            <th>Kamera</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($transaksi_terbaru->num_rows > 0): ?>
                        <?php while ($row = $transaksi_terbaru->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['kode_sewa']) ?></td>
                            <td><?= htmlspecialchars($row['nama']) ?></td>
                            <td><?= htmlspecialchars($row['nama_kamera']) ?></td>
                            <td>
                                <span class="badge badge-<?= $row['status'] ?>">
                                    <?= ucfirst($row['status']) ?>
                                </span>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="4" style="text-align:center;color:#aaa;padding:20px;">Belum ada transaksi</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
 
            <!-- Daftar Kamera -->
            <div class="section-box">
                <div class="section-head">
                    Daftar Kamera
>>>>>>> 1b7042cad935a134c88c013e814ecf0ba9384291
                    <a href="kamera.php">Lihat semua »</a>
                </div>
                <table>
                    <thead>
                        <tr>
<<<<<<< HEAD
                            <th>No</th>
                            <th>Kode</th>
=======
>>>>>>> 1b7042cad935a134c88c013e814ecf0ba9384291
                            <th>Nama Kamera</th>
                            <th>Tipe</th>
                            <th>Harga/Hari</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($kamera_list->num_rows > 0): ?>
<<<<<<< HEAD
                        <?php $no = 1; while ($row = $kamera_list->fetch_assoc()): ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><code><?= htmlspecialchars($row['kode_kamera']) ?></code></td>
                            <td><?= htmlspecialchars($row['nama_kamera']) ?></td>
                            <td><?= htmlspecialchars($row['tipe']) ?></td>
                            <td class="rupiah">Rp <?= number_format($row['harga_sewa'], 0, ',', '.') ?></td>
                            <td><span class="badge badge-<?= $row['status'] ?>"><?= ucfirst($row['status']) ?></span></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6">
                            <div class="empty-state">📷 Belum ada kamera.<br><a href="add_kamera.php">Tambah sekarang »</a></div>
                        </td></tr>
=======
                        <?php while ($row = $kamera_list->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['nama_kamera']) ?></td>
                            <td><?= htmlspecialchars($row['tipe']) ?></td>
                            <td class="rupiah">Rp <?= number_format($row['harga_sewa'], 0, ',', '.') ?></td>
                            <td>
                                <span class="badge badge-<?= $row['status'] ?>">
                                    <?= ucfirst($row['status']) ?>
                                </span>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="4" style="text-align:center;color:#aaa;padding:20px;">Belum ada kamera</td></tr>
>>>>>>> 1b7042cad935a134c88c013e814ecf0ba9384291
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
<<<<<<< HEAD

            <!-- Grafik kondisi -->
            <div class="section-box">
                <div class="section-head">Kondisi Inventaris</div>
                <div class="bar-wrap">

                    <?php $pct1 = $total_kamera > 0 ? round(($tersedia / $total_kamera) * 100) : 0; ?>
                    <div class="bar-item">
                        <div class="bar-label">
                            <span>✅ Tersedia</span>
                            <span><?= $tersedia ?> unit (<?= $pct1 ?>%)</span>
                        </div>
                        <div class="bar-track">
                            <div class="bar-fill bar-green" style="width:<?= $pct1 ?>%"></div>
                        </div>
                    </div>

                    <?php $pct2 = $total_kamera > 0 ? round(($disewa / $total_kamera) * 100) : 0; ?>
                    <div class="bar-item">
                        <div class="bar-label">
                            <span>🔄 Disewa</span>
                            <span><?= $disewa ?> unit (<?= $pct2 ?>%)</span>
                        </div>
                        <div class="bar-track">
                            <div class="bar-fill bar-orange" style="width:<?= $pct2 ?>%"></div>
                        </div>
                    </div>

                    <?php $pct3 = $total_kamera > 0 ? round(($rusak / $total_kamera) * 100) : 0; ?>
                    <div class="bar-item">
                        <div class="bar-label">
                            <span>⚠️ Rusak</span>
                            <span><?= $rusak ?> unit (<?= $pct3 ?>%)</span>
                        </div>
                        <div class="bar-track">
                            <div class="bar-fill bar-red" style="width:<?= $pct3 ?>%"></div>
                        </div>
                    </div>

                    <hr style="margin:16px 0;border:none;border-top:1px solid #eee;">
                    <div style="font-size:13px;color:#555;">
                        <div style="margin-bottom:6px;">📦 Total inventaris: <strong><?= $total_kamera ?> unit</strong></div>
                        <div style="font-size:12px;color:#999;">Diperbarui: <?= date('d/m/Y H:i') ?></div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

=======
 
        </div>
 
        <!-- Ringkasan Pendapatan -->
        <div class="section-box">
            <div class="section-head">Ringkasan Pendapatan</div>
            <div style="padding: 20px; display: flex; align-items: center; gap: 12px;">
                <span style="font-size:36px;">💰</span>
                <div>
                    <div style="font-size:13px;color:#888;">Total pendapatan dari sewa yang sudah dikembalikan</div>
                    <div style="font-size:28px;font-weight:bold;color:#27ae60;" class="rupiah">
                        Rp <?= number_format($pendapatan, 0, ',', '.') ?>
                    </div>
                </div>
            </div>
        </div>
 
    </div>
</div>
 
>>>>>>> 1b7042cad935a134c88c013e814ecf0ba9384291
</body>
</html>