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