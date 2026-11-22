-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 12, 2025 at 12:44 PM
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
-- Database: `db_libs`
--

-- --------------------------------------------------------

--
-- Table structure for table `tbl_anggota`
--

CREATE TABLE `tbl_anggota` (
  `id` int(11) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `nama` varchar(150) NOT NULL,
  `nim_nik` varchar(50) NOT NULL,
  `program_studi` varchar(150) NOT NULL,
  `no_telp` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_anggota`
--

INSERT INTO `tbl_anggota` (`id`, `email`, `password_hash`, `nama`, `nim_nik`, `program_studi`, `no_telp`, `created_at`) VALUES
(2, 'wemz@gmail.com', '$2y$10$NyLj36lEs6hADvHUoOn4Ce9t0567vNzg.WSVLxGW/08VYOBEbBDSy', 'Wez', 'Bk-002', 'Ilkom', '0023123123', '2025-12-10 14:10:05'),
(3, 'wemz2@gmail.com', '$2y$10$UkPEPyQ8F88j07QXBnYguej9tnSwy6zDyzc8gC4MvTL99Hqh5OeMm', 'Shiro2', 'Bk-003', 'Ilkom', '08889944234', '2025-12-10 14:25:34'),
(4, 'zhaw@gmail.com', '$2y$10$67MO/gABeZJx9pxrxoshI.xEJmnKQuz3GpyaNwL5HyTF9AWivev0W', 'Zhaw', 'BK-004', 'Ilmu nyanyi', '089999999', '2025-12-10 15:39:24'),
(5, 'andin@gmail.com', '$2y$10$XeRZfR5JE31mXK1jXlj/ouDAaBWSBVVTSfwy8922ZT8l69FbgPBk2', 'andin', '123', 'ilpus', '08999999', '2025-12-11 02:08:19'),
(6, 'abel@gmail.com', '$2y$10$XQP73my.pNGQmdbAHAeXEeYD8OPb/1FNK.AlVQePJ17r/rVZ1Pely', 'abel', '321', 'ilpus', '321', '2025-12-11 02:29:43'),
(9, 'nopal@gmail.com', '$2y$10$RHlDo4Cf.ZUkVwy35w5tteowWliowf0NXd79L4yCuHCs/EcYZjanG', 'nopal', '13040123140081', 'teknik buku', '08134526768', '2025-12-11 07:56:51'),
(10, 'vdp@gmail.com', '$2y$10$MvsvmKQGchVNKvNlGnDUouDGd6qAwDp64pzy6ljr.zplN5iRNmTSm', 'Van De Pal', '13040131131', 'FT', '08778768877', '2025-12-11 08:17:42');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_blibliografi`
--

CREATE TABLE `tbl_blibliografi` (
  `ID` int(11) NOT NULL,
  `Judul` varchar(50) NOT NULL,
  `Pengarang` varchar(100) NOT NULL,
  `Kategori` varchar(30) NOT NULL,
  `Tahun` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_blibliografi`
--

INSERT INTO `tbl_blibliografi` (`ID`, `Judul`, `Pengarang`, `Kategori`, `Tahun`) VALUES
(1, 'Ubur-Ubur Lembur', 'Raditya Dika', 'Humor', 2015),
(2, 'Atomic Habits', 'James Clear', 'Lainnya', 2018),
(4, 'Namaku Alam', 'Tere Liye', 'Fiksi', 2023),
(6, 'Laut Bercerita', 'Leisa S. Chudori', 'Fiksi', 2018),
(7, 'Lelaki di Bulan', 'Connie Wong', 'Fiksi', 2020),
(8, 'Negeri Para Bedebah', 'Tere Liye', 'Fiksi', 2018),
(9, 'Dunia Sophie', 'Jostein Gaarder', 'Filsafat', 1991);

-- --------------------------------------------------------

--
-- Table structure for table `tbl_buku`
--

CREATE TABLE `tbl_buku` (
  `id` int(11) NOT NULL,
  `id_bibliografi` int(11) NOT NULL,
  `stok_total` int(11) NOT NULL DEFAULT 0,
  `stok_tersedia` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_buku`
--

INSERT INTO `tbl_buku` (`id`, `id_bibliografi`, `stok_total`, `stok_tersedia`) VALUES
(1, 1, 10, 6),
(2, 2, 1, 0),
(3, 4, 10, 10),
(4, 6, 10, 10),
(5, 7, 10, 7),
(6, 9, 1000, 998),
(7, 8, 10, 10),
(8, 10, 10, 10),
(9, 11, 10, 10);

-- --------------------------------------------------------

--
-- Table structure for table `tbl_sirkulasi`
--

CREATE TABLE `tbl_sirkulasi` (
  `id` int(11) NOT NULL,
  `id_anggota` int(11) NOT NULL,
  `id_buku` int(11) NOT NULL,
  `tgl_pinjam` date NOT NULL,
  `tgl_kembali_target` date NOT NULL,
  `tgl_kembali_aktual` datetime DEFAULT NULL,
  `denda` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_sirkulasi`
--

INSERT INTO `tbl_sirkulasi` (`id`, `id_anggota`, `id_buku`, `tgl_pinjam`, `tgl_kembali_target`, `tgl_kembali_aktual`, `denda`) VALUES
(1, 1, 2, '2025-12-10', '2025-12-17', '2025-12-10 16:35:38', 0),
(2, 1, 1, '2025-12-10', '2025-12-17', '2025-12-10 16:36:07', 0),
(3, 1, 3, '2025-12-01', '2025-12-07', '2025-12-10 17:26:31', 4000),
(4, 4, 4, '2025-12-01', '2025-12-07', '2025-12-11 03:14:19', 6000),
(5, 4, 4, '2025-12-10', '2025-12-17', '2025-12-11 03:22:55', 0),
(6, 1, 5, '2025-12-01', '2025-12-07', NULL, 0),
(7, 1, 5, '2025-12-10', '2025-12-17', NULL, 0),
(8, 5, 2, '2025-12-11', '2025-12-18', '2025-12-11 03:14:10', 0),
(9, 2, 2, '2025-12-01', '2025-12-07', '2025-12-11 03:18:13', 6000),
(10, 2, 2, '2025-12-11', '2025-12-18', '2025-12-11 03:22:45', 0),
(13, 3, 1, '2025-12-11', '2025-12-18', '2025-12-11 03:22:47', 0),
(14, 3, 1, '2025-12-11', '2025-12-18', '2025-12-11 03:22:49', 0),
(16, 3, 1, '2025-12-11', '2025-12-18', '2025-12-11 03:22:51', 0),
(17, 3, 6, '2025-12-11', '2025-12-18', '2025-12-11 03:22:53', 0),
(18, 3, 6, '2025-12-01', '2025-12-07', '2025-12-12 10:33:48', 8000),
(19, 3, 6, '2025-12-11', '2025-12-18', '2025-12-12 10:26:30', 0),
(20, 5, 5, '2025-12-11', '2025-12-18', '2025-12-11 03:26:47', 0),
(21, 5, 2, '2025-11-11', '2025-11-19', '2025-12-11 03:55:36', 46000),
(22, 10, 6, '2025-12-12', '2025-12-19', '2025-12-12 10:26:23', 0),
(23, 10, 6, '2025-12-12', '2025-12-19', '2025-12-12 10:31:29', 0),
(24, 9, 2, '2025-12-12', '2025-12-19', NULL, 0),
(25, 3, 5, '2025-12-12', '2025-12-19', NULL, 0),
(26, 4, 6, '2025-12-12', '2025-12-19', NULL, 0),
(27, 4, 6, '2025-12-12', '2025-12-19', NULL, 0),
(28, 2, 1, '2025-12-12', '2025-12-19', NULL, 0);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `tbl_anggota`
--
ALTER TABLE `tbl_anggota`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `nim_nik` (`nim_nik`),
  ADD KEY `idx_nim_nik` (`nim_nik`);

--
-- Indexes for table `tbl_blibliografi`
--
ALTER TABLE `tbl_blibliografi`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `tbl_buku`
--
ALTER TABLE `tbl_buku`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tbl_sirkulasi`
--
ALTER TABLE `tbl_sirkulasi`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `tbl_anggota`
--
ALTER TABLE `tbl_anggota`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `tbl_blibliografi`
--
ALTER TABLE `tbl_blibliografi`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `tbl_buku`
--
ALTER TABLE `tbl_buku`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `tbl_sirkulasi`
--
ALTER TABLE `tbl_sirkulasi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
