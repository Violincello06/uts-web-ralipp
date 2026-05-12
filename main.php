<?php
session_start();
require_once 'koneksi.php';
 
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
 
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
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Rental Kamera</title>
    <style>
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
        .navbar {
            background-color: #3a7bd5;
            color: white;
            padding: 12px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
 
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
        .cards {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 24px;
        }
 
        .card {
            background: white;
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 16px;
        }
 
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
 
        .card.blue   { border-top: 3px solid #3a7bd5; }
        .card.green  { border-top: 3px solid #27ae60; }
        .card.orange { border-top: 3px solid #e67e22; }
        .card.red    { border-top: 3px solid #e74c3c; }
 
        /* ---- TABLES ---- */
        .section-box {
            background: white;
            border: 1px solid #ddd;
            border-radius: 6px;
            margin-bottom: 20px;
        }
 
        .section-box .section-head {
            padding: 12px 16px;
            border-bottom: 1px solid #eee;
            font-size: 14px;
            font-weight: bold;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
 
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
<div class="navbar">
    <div class="brand">🎥 Rental Kamera</div>
    <div class="nav-right">
        <span>Halo, <strong><?= htmlspecialchars($_SESSION['nama_lengkap']) ?></strong></span>
        <a href="logout.php">Keluar</a>
    </div>
</div>
 
<div class="wrapper">
 
    <!-- SIDEBAR -->
    <div class="sidebar">
        <div class="menu-title">Menu Utama</div>
        <a href="main.php" class="active">📊 Dashboard</a>
        <a href="kamera.php">📷 Data Kamera</a>
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
 
        <!-- CARDS -->
        <div class="cards">
            <div class="card blue">
                <div class="card-icon">📷</div>
                <div class="card-label">Total Kamera</div>
                <div class="card-value"><?= $total_kamera ?></div>
            </div>
            <div class="card green">
                <div class="card-icon">✅</div>
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
                    <a href="kamera.php">Lihat semua »</a>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Nama Kamera</th>
                            <th>Tipe</th>
                            <th>Harga/Hari</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($kamera_list->num_rows > 0): ?>
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
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
 
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
 
</body>
</html>