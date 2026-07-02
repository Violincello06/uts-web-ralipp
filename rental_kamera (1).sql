-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 02, 2026 at 09:07 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `rental_kamera`
--

-- --------------------------------------------------------

--
-- Table structure for table `kamera`
--

CREATE TABLE `kamera` (
  `id` int(11) NOT NULL,
  `kode_kamera` varchar(20) NOT NULL,
  `nama_kamera` varchar(100) NOT NULL,
  `merk` varchar(50) DEFAULT NULL,
  `tipe` varchar(50) DEFAULT NULL,
  `harga_sewa` decimal(10,2) NOT NULL DEFAULT 0.00,
  `stok` int(11) NOT NULL DEFAULT 1,
  `deskripsi` text DEFAULT NULL,
  `status` enum('tersedia','disewa','rusak') DEFAULT 'tersedia',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `kamera`
--

INSERT INTO `kamera` (`id`, `kode_kamera`, `nama_kamera`, `merk`, `tipe`, `harga_sewa`, `stok`, `deskripsi`, `status`, `created_at`) VALUES
(1, 'KAM-011', 'Canon EOS 800D', 'Canon', 'DSLR', 152000.00, 2, 'DSLR entry-level, cocok untuk pemula', 'disewa', '2026-05-12 13:07:49'),
(2, 'KAM-002', 'Sony Alpha A6400', 'Sony', 'Mirrorless', 200000.00, 1, 'Mirrorless APS-C, autofokus cepat', 'disewa', '2026-05-12 13:07:49'),
(3, 'KAM-003', 'Nikon D5600', 'Nikon', 'DSLR', 175000.00, 2, 'DSLR dengan layar putar, 24MP', 'tersedia', '2026-05-12 13:07:49'),
(8, 'KAM-22222', 'JAMILWakwiw', 'Nika', 'DSLR', 1000.00, 1, 'sajdsjd', 'rusak', '2026-05-13 06:26:45'),
(9, 'KAM-122', 'Sonywakwaw', 'Sony', 'Film', 200000.00, 5, 'ceritanya ini deskripsi', 'disewa', '2026-05-13 06:51:54'),
(11, 'KAM-220', 'GASCAM', 'Nook', 'Filmcam', 1121212.00, 23, 'Ganteng Cuyy', 'rusak', '2026-05-20 16:54:53'),
(13, 'KAM-222', 'GOKOK', 'Sony', 'Film', 2000.00, 2, '', 'tersedia', '2026-06-04 06:39:59'),
(14, 'KAM-223', 'KONON', 'Canon', 'Mirrorless', 20000.00, 20, 'HEHRERHERE', 'tersedia', '2026-06-04 07:54:43'),
(15, 'KAM-224', 'SONIC HEDG', 'Nika', 'DSLR', 64535.00, 9, '', 'tersedia', '2026-06-04 07:55:17'),
(16, 'KAM-225', 'Jamasss', 'Bond', 'DSLR', 30009.00, 45, '', 'tersedia', '2026-06-04 07:55:46'),
(18, 'KAM-776', 'Donua', 'Sony', 'Film', 200000.00, 5, '', 'tersedia', '2026-06-18 06:45:34'),
(19, 'KAM-121', 'Caryti', 'Canon', 'DSLR', 765200.00, 4, '', 'tersedia', '2026-06-18 06:46:24'),
(20, 'KAM-444', 'kiwup', 'Canon', 'Film', 66666.00, 3, 'sxs', 'tersedia', '2026-06-18 07:03:08'),
(21, 'KAM-555', 'Huahuh', 'Nika', 'Mirrorless', 3333333.00, 4, 'ajda', 'tersedia', '2026-06-18 07:20:11'),
(22, 'KAM-556', 'Kononu', 'Sony', 'DSLR', 111111.00, 2, '8728642j', 'tersedia', '2026-06-18 07:22:10'),
(23, 'KAM-557', 'HAHAHA', 'Nook', 'Film', 1361361.00, 4, '', 'tersedia', '2026-06-18 07:30:11'),
(25, 'KAM-559', 'Kukiku', 'Canon', 'Film', 12345.00, 3, '989879', 'tersedia', '2026-06-18 07:34:39'),
(26, 'KAM-560', 'yuyeue', 'Canon', 'Filmcam', 50000.00, 4, 'aa', 'tersedia', '2026-06-18 07:36:27'),
(27, 'KAM-561', 'Canon EOS 800Ds', 'Nika', 'Filmcam', 23222.00, 1, 'ww', 'tersedia', '2026-06-18 08:10:09'),
(28, 'KAM-562', 'RETEU', 'Bond', 'Filmcam', 44443.00, 1, 'aa', 'tersedia', '2026-06-18 08:12:47'),
(29, 'KAM-563', 'HAYAati', 'Canon', 'Filmcam', 25242.00, 7, 'sss', 'tersedia', '2026-06-18 08:16:22'),
(32, 'KAM-111', 'jsjsjs', 'Nikon', 'Film', 183187.00, 4, 'aaaaaaa', 'tersedia', '2026-06-25 06:54:24');

-- --------------------------------------------------------

--
-- Table structure for table `pengembalian`
--

CREATE TABLE `pengembalian` (
  `id` int(11) NOT NULL,
  `id_penyewaan` int(11) NOT NULL,
  `tanggal_kembali_aktual` date NOT NULL,
  `kondisi_kamera` enum('baik','rusak_ringan','rusak_berat') DEFAULT 'baik',
  `catatan` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pengembalian`
--

INSERT INTO `pengembalian` (`id`, `id_penyewaan`, `tanggal_kembali_aktual`, `kondisi_kamera`, `catatan`, `created_at`) VALUES
(1, 3, '2026-06-04', 'baik', '', '2026-06-04 07:11:44'),
(3, 4, '2026-06-04', 'baik', '', '2026-06-04 07:15:48'),
(4, 1, '2026-06-04', 'baik', '', '2026-06-04 07:16:01'),
(5, 5, '2026-06-04', 'rusak_berat', '', '2026-06-04 07:31:50'),
(6, 6, '2026-06-18', 'baik', '', '2026-06-18 06:18:00'),
(7, 7, '2026-06-18', 'baik', '', '2026-06-18 07:04:09'),
(8, 8, '2026-06-18', 'baik', '', '2026-06-18 07:25:24'),
(9, 9, '2026-06-18', 'baik', 'Terimakasi', '2026-06-18 07:56:26'),
(10, 10, '2026-06-18', 'rusak_berat', '', '2026-06-18 08:18:27'),
(12, 13, '2026-06-25', 'baik', '', '2026-06-25 07:14:26');

-- --------------------------------------------------------

--
-- Table structure for table `penyewaan`
--

CREATE TABLE `penyewaan` (
  `id` int(11) NOT NULL,
  `kode_sewa` varchar(20) NOT NULL,
  `nama_penyewa` varchar(100) NOT NULL,
  `id_kamera` int(11) NOT NULL,
  `tanggal_sewa` date NOT NULL,
  `tanggal_kembali` date NOT NULL,
  `lama_sewa` int(11) NOT NULL DEFAULT 1,
  `total_bayar` decimal(12,2) NOT NULL DEFAULT 0.00,
  `status` enum('dipinjam','dikembalikan','terlambat') DEFAULT 'dipinjam',
  `catatan` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `penyewaan`
--

INSERT INTO `penyewaan` (`id`, `kode_sewa`, `nama_penyewa`, `id_kamera`, `tanggal_sewa`, `tanggal_kembali`, `lama_sewa`, `total_bayar`, `status`, `catatan`, `created_at`) VALUES
(1, 'SW-00001', 'Ralip', 3, '2026-06-04', '2026-06-23', 19, 3325000.00, 'dikembalikan', '', '2026-06-04 06:39:12'),
(3, 'SW-00003', 'Agus', 13, '2026-06-04', '2026-06-05', 1, 2000.00, 'dikembalikan', 'sjhds', '2026-06-04 07:01:22'),
(4, 'SW-00004', 'Mamat', 13, '2026-06-04', '2026-06-24', 20, 40000.00, 'dikembalikan', '', '2026-06-04 07:13:50'),
(5, 'SW-00005', 'KOMANG', 8, '2026-06-04', '2026-07-08', 34, 34000.00, 'dikembalikan', '', '2026-06-04 07:31:40'),
(6, 'SW-00006', 'Ralip', 15, '2026-06-18', '2026-06-22', 4, 258140.00, 'dikembalikan', '', '2026-06-18 06:17:41'),
(7, 'SW-00007', 'Jawier', 16, '2026-06-18', '2026-06-30', 12, 360108.00, '', '', '2026-06-18 07:04:04'),
(8, 'SW-00008', 'Joko', 15, '2026-06-18', '2026-07-10', 22, 1419770.00, 'dikembalikan', '', '2026-06-18 07:25:11'),
(9, 'SW-00009', 'Kuliuu', 15, '2026-06-18', '2026-07-01', 13, 838955.00, 'dikembalikan', '', '2026-06-18 07:56:16'),
(10, 'SW-00010', 'Surti', 29, '2026-06-18', '2026-06-19', 1, 25242.00, 'dikembalikan', '', '2026-06-18 08:18:06'),
(13, 'SW-00011', 'Joko', 3, '2026-06-25', '2026-07-09', 14, 2450000.00, 'dikembalikan', 'Coba', '2026-06-25 07:13:52');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama_lengkap` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `role` enum('admin','user') DEFAULT 'admin',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `nama_lengkap`, `email`, `avatar`, `role`, `created_at`) VALUES
(1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'admin@rentalkamera.com', NULL, 'admin', '2026-05-12 13:07:49'),
(2, 'Violincello', '$2y$10$nayp3d6AWin/btJvA5/BYufOVFTVhRvVYFSK9aa6aUMPgTfg6lVFi', 'Violincello', 'aaaa@gmail.com', 'assets/images/profile/avatar_2_1780558649.jpg', 'user', '2026-05-12 13:10:56'),
(3, 'adminn', '$2y$10$eBC4jNqH/YM/Uvatq5rkgeW1xnScbNJF9jfjlj3y3PM3XOuD56qqW', 'AdminGanteng', '1odkksjd222@gmail.com', NULL, '', '2026-05-21 08:13:17'),
(4, 'cello', '$2y$10$3ELciyWClmcCsw/uhmxrN..NyAEkxhFE2tQd.yVVCcHWxFPPlu3WG', 'Violincello', 'kjafkafja@gmail.com', 'assets/images/profile/avatar_4_1781764886.jpg', '', '2026-06-18 06:16:52'),
(5, 'Ralep', '$2y$10$rdYwPy/6goijyD84qcIiieqXkKecu9wudjjZuBAg.nyOiu9rJOduK', 'Violincelloo', 'adadadadad@gmail.com', 'assets/images/profile/avatar_5_1782371719.jpg', '', '2026-06-24 08:44:20');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `kamera`
--
ALTER TABLE `kamera`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kode_kamera` (`kode_kamera`);

--
-- Indexes for table `pengembalian`
--
ALTER TABLE `pengembalian`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id_penyewaan` (`id_penyewaan`);

--
-- Indexes for table `penyewaan`
--
ALTER TABLE `penyewaan`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kode_sewa` (`kode_sewa`),
  ADD KEY `id_kamera` (`id_kamera`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `kamera`
--
ALTER TABLE `kamera`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `pengembalian`
--
ALTER TABLE `pengembalian`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `penyewaan`
--
ALTER TABLE `penyewaan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `pengembalian`
--
ALTER TABLE `pengembalian`
  ADD CONSTRAINT `pengembalian_ibfk_1` FOREIGN KEY (`id_penyewaan`) REFERENCES `penyewaan` (`id`);

--
-- Constraints for table `penyewaan`
--
ALTER TABLE `penyewaan`
  ADD CONSTRAINT `penyewaan_ibfk_1` FOREIGN KEY (`id_kamera`) REFERENCES `kamera` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
