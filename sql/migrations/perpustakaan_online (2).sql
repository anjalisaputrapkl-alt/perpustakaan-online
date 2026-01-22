-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 22, 2026 at 09:42 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `perpustakaan_online`
--

-- --------------------------------------------------------

--
-- Table structure for table `books`
--

CREATE TABLE `books` (
  `id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `author` varchar(255) DEFAULT NULL,
  `isbn` varchar(100) DEFAULT NULL,
  `category` varchar(100) NOT NULL,
  `copies` int(11) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `shelf` varchar(50) DEFAULT NULL,
  `row_number` int(11) DEFAULT NULL,
  `cover_image` varchar(225) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `books`
--

INSERT INTO `books` (`id`, `school_id`, `title`, `author`, `isbn`, `category`, `copies`, `created_at`, `shelf`, `row_number`, `cover_image`) VALUES
(10, 2, 'Bedebah Di Ujung Tanduk', 'Tere Liye', '123993002', 'Fiksi', 10, '2026-01-15 06:09:41', '1A', 3, 'book_1768807416_696ddbf83888f.jpg'),
(18, 7, 'the art of stoicsm', 'andora ', '598467', 'Non-Fiksi', 0, '2026-01-19 03:24:18', '3', 2, 'book_1768793058_696da3e2b0ead.jpg'),
(19, 7, 'pyshcology of money', 'morgan housel', '4523657568', 'Non-Fiksi', 0, '2026-01-19 03:31:52', '4', 2, 'book_1768793512_696da5a8af9d1.jpg'),
(20, 7, 'selamat tinggal', 'Tere liye', '47906879478', 'Lainnya', 0, '2026-01-19 03:34:48', '5', 1, 'book_1768793688_696da6586b752.jpg'),
(21, 7, 'bicara itu ada seninya', 'oh su hyang', '9589646', 'Seni & Budaya', 0, '2026-01-19 03:35:35', '4', 3, 'book_1768793735_696da687805b4.jpg'),
(22, 7, 'belajar coding itu penting di era revousi industri', 'yeni mulayani', '6974854', 'Teknologi', 0, '2026-01-19 03:38:49', '2', 2, 'book_1768793929_696da7499f7cc.jpg'),
(23, 7, 'belajar coding membuat program', 'someone', '47568673', 'Teknologi', 0, '2026-01-19 03:39:41', '1', 4, 'book_1768793981_696da77d831ab.jpg'),
(24, 2, 'Madilog', 'Tan Malaka', '131234123', 'Non-Fiksi', 12, '2026-01-19 07:05:48', '1A', 2, 'book_1768807083_696ddaab3d431.jpeg'),
(25, 7, 'stoicsm', 'andora ', '63476', 'Non-Fiksi', 8, '2026-01-20 03:13:28', '2', 5, 'book_1768878808_696ef2d876f4a.jpg'),
(26, 7, 'aw', 'someone', '987654', 'Fiksi', 1, '2026-01-22 07:52:30', '5', 1, 'book_1769068350_6971d73ee2072.jpg'),
(27, 7, 'trtr', 'someone', '1435556', 'Teknologi', 0, '2026-01-22 07:55:59', '2', 2, 'book_1769068559_6971d80f8d901.jpg'),
(28, 7, 'jhj', 'someone', '75888', 'Non-Fiksi', 1, '2026-01-22 08:25:09', '2', 1, 'book_1769070309_6971dee584ab6.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `book_maintenance`
--

CREATE TABLE `book_maintenance` (
  `id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `status` varchar(50) NOT NULL,
  `notes` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `book_maintenance`
--

INSERT INTO `book_maintenance` (`id`, `book_id`, `status`, `notes`, `updated_at`) VALUES
(1, 10, 'Damaged', 'rusaK', '2026-01-15 07:33:42'),
(3, 10, 'Good', 'ihihi', '2026-01-19 04:42:28');

-- --------------------------------------------------------

--
-- Table structure for table `borrows`
--

CREATE TABLE `borrows` (
  `id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  `borrowed_at` datetime DEFAULT current_timestamp(),
  `due_at` datetime DEFAULT NULL,
  `returned_at` datetime DEFAULT NULL,
  `status` enum('borrowed','returned','overdue') DEFAULT 'borrowed'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `borrows`
--

INSERT INTO `borrows` (`id`, `school_id`, `book_id`, `member_id`, `borrowed_at`, `due_at`, `returned_at`, `status`) VALUES
(12, 7, 25, 11, '2026-01-22 11:33:56', '2026-01-29 11:33:56', '2026-01-22 13:23:51', 'returned'),
(13, 7, 23, 11, '2026-01-22 13:23:41', '2026-01-29 13:23:41', '2026-01-22 13:24:00', 'returned'),
(14, 7, 22, 11, '2026-01-22 13:32:53', '2026-01-29 13:32:53', '2026-01-22 13:33:11', 'returned'),
(15, 7, 21, 11, '2026-01-22 13:37:32', '2026-01-29 13:37:32', '2026-01-22 13:38:07', 'returned'),
(16, 7, 20, 11, '2026-01-22 13:37:49', '2026-01-29 13:37:49', '2026-01-22 13:38:05', 'returned'),
(17, 7, 19, 11, '2026-01-22 13:43:36', '2026-01-29 13:43:36', '2026-01-22 13:50:31', 'returned'),
(18, 7, 18, 11, '2026-01-22 13:51:01', '2026-01-29 13:51:01', '2026-01-22 14:10:52', 'returned'),
(19, 7, 25, 11, '2026-01-22 14:11:34', '2026-01-29 14:11:34', '2026-01-22 14:51:25', 'returned'),
(20, 7, 25, 11, '2026-01-22 14:51:59', '2026-01-29 14:51:59', NULL, 'borrowed'),
(21, 7, 27, 11, '2026-01-22 15:23:23', '2026-01-29 15:23:23', NULL, 'borrowed'),
(22, 2, 24, 10, '2026-01-22 15:38:49', '2026-01-29 15:38:49', NULL, 'borrowed');

-- --------------------------------------------------------

--
-- Table structure for table `favorites`
--

CREATE TABLE `favorites` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `members`
--

CREATE TABLE `members` (
  `id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `member_no` varchar(100) DEFAULT NULL,
  `nisn` varchar(20) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `members`
--

INSERT INTO `members` (`id`, `school_id`, `name`, `email`, `member_no`, `nisn`, `password`, `status`, `created_at`) VALUES
(10, 2, 'Adi Triyanto', 'adi@gmail.com', '089129993223', '333333', NULL, 'active', '2026-01-21 03:52:39'),
(11, 7, 'surya', 'uya4767@gmail.com', '4675965903', '2121', NULL, 'active', '2026-01-22 04:23:31'),
(12, 8, 'Uya', 'uya4767@gmail.com', '4675965903', '232323', NULL, 'active', '2026-01-22 08:41:10');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` enum('borrow','return_request','return_confirm','late_warning','info','new_book') NOT NULL DEFAULT 'info',
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `school_id`, `student_id`, `title`, `message`, `type`, `is_read`, `created_at`, `updated_at`) VALUES
(1, 7, 11, 'Permintaan Pengembalian Dikirim', 'Permintaan pengembalian untuk buku \"stoicsm\" menunggu konfirmasi admin.', 'return_request', 0, '2026-01-22 07:51:20', '2026-01-22 07:51:20'),
(2, 7, 11, 'Peminjaman Berhasil', 'Anda telah meminjam buku \"stoicsm\". Harap dikembalikan sebelum tanggal 29/01/2026.', 'borrow', 0, '2026-01-22 07:51:59', '2026-01-22 07:51:59'),
(3, 7, 11, 'Buku Baru Tersedia', 'Buku \"trtr\" telah ditambahkan ke perpustakaan. Silakan pinjam sekarang!', 'new_book', 0, '2026-01-22 07:55:59', '2026-01-22 07:55:59'),
(4, 7, 11, 'Peminjaman Berhasil', 'Anda telah meminjam buku \"trtr\". Harap dikembalikan sebelum tanggal 29/01/2026.', 'borrow', 0, '2026-01-22 08:23:23', '2026-01-22 08:23:23'),
(5, 7, 11, 'Buku Baru Tersedia', 'Buku \"jhj\" telah ditambahkan ke perpustakaan. Silakan pinjam sekarang!', 'new_book', 0, '2026-01-22 08:25:09', '2026-01-22 08:25:09'),
(6, 2, 10, 'Peminjaman Berhasil', 'Anda telah meminjam buku \"Madilog\". Harap dikembalikan sebelum tanggal 29/01/2026.', 'borrow', 0, '2026-01-22 08:38:49', '2026-01-22 08:38:49');

-- --------------------------------------------------------

--
-- Table structure for table `notifikasi`
--

CREATE TABLE `notifikasi` (
  `id_notifikasi` int(11) NOT NULL,
  `id_siswa` int(11) NOT NULL,
  `judul` varchar(255) NOT NULL,
  `pesan` text NOT NULL,
  `jenis_notifikasi` enum('telat','peringatan','pengembalian','info','sukses','buku','default') DEFAULT 'default',
  `tanggal` datetime DEFAULT current_timestamp(),
  `status_baca` tinyint(1) DEFAULT 0 COMMENT '0 = belum dibaca, 1 = sudah dibaca',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notifikasi`
--

INSERT INTO `notifikasi` (`id_notifikasi`, `id_siswa`, `judul`, `pesan`, `jenis_notifikasi`, `tanggal`, `status_baca`, `created_at`, `updated_at`) VALUES
(1, 1, 'Buku Telat Dikembalikan', 'Buku \"Clean Code\" belum dikembalikan. Tenggat: 2024-01-15. Denda: Rp 5.000/hari', 'telat', '2026-01-17 10:06:06', 0, '2026-01-20 03:06:06', '2026-01-20 03:06:06'),
(2, 1, 'Peringatan: Denda Diperoleh', 'Anda telah dikenakan denda sebesar Rp 15.000 untuk keterlambatan pengembalian buku', 'peringatan', '2026-01-15 10:06:06', 1, '2026-01-20 03:06:06', '2026-01-20 03:06:06'),
(3, 1, 'Notifikasi Pengembalian Buku', 'Jangan lupa mengembalikan buku \"Design Patterns\" sebelum tanggal 2024-01-20', 'pengembalian', '2026-01-19 10:06:06', 0, '2026-01-20 03:06:06', '2026-01-20 03:06:06'),
(4, 1, 'Informasi Terbaru', 'Perpustakaan akan ditutup pada tanggal 25 Januari untuk pemeliharaan sistem', 'info', '2026-01-20 10:06:06', 0, '2026-01-20 03:06:06', '2026-01-20 03:06:06'),
(5, 1, 'Peminjaman Berhasil', 'Anda berhasil meminjam buku \"Refactoring\" pada 2024-01-10', 'sukses', '2026-01-13 10:06:06', 1, '2026-01-20 03:06:06', '2026-01-20 03:06:06'),
(6, 1, 'Katalog Buku Baru', 'Ada 5 buku baru dalam katalog perpustakaan: \"Microservices\", \"Cloud Native\", dan lainnya', 'buku', '2026-01-18 10:06:06', 1, '2026-01-20 03:06:06', '2026-01-20 03:06:06'),
(7, 2, 'Buku Siap Diambil', 'Buku yang Anda pesan \"Introduction to Algorithms\" sudah tersedia di perpustakaan', 'info', '2026-01-20 10:06:06', 0, '2026-01-20 03:06:06', '2026-01-20 03:06:06'),
(8, 2, 'Peminjaman Berhasil', 'Anda berhasil meminjam 3 buku pada 2024-01-10', 'sukses', '2026-01-18 10:06:06', 1, '2026-01-20 03:06:06', '2026-01-20 03:06:06');

-- --------------------------------------------------------

--
-- Table structure for table `schools`
--

CREATE TABLE `schools` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `status` enum('pending','active','rejected') DEFAULT 'pending',
  `activation_code` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `npsn` varchar(20) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `photo_path` varchar(255) DEFAULT NULL,
  `founded_year` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `schools`
--

INSERT INTO `schools` (`id`, `name`, `slug`, `status`, `activation_code`, `created_at`, `email`, `phone`, `address`, `description`, `logo`, `profile_picture`, `npsn`, `website`, `photo_path`, `founded_year`) VALUES
(1, 'Contoh Sekolah', 'contoh-sekolah', 'pending', NULL, '2026-01-12 06:24:19', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(2, 'SMK BINA MANDIRI MULTIMEDIA', 'smk-bina-mandiri-multimedia', 'active', NULL, '2026-01-12 06:44:08', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'public/uploads/school-photos/school_1768963427_69703d63a07d0.png', NULL),
(4, 'smp menang 01', 'smp-menang-01', 'pending', NULL, '2026-01-13 01:53:13', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(5, 'smk ahay', 'smk-ahay', 'pending', NULL, '2026-01-13 06:47:10', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'public/uploads/school-photos/school_1768963474_69703d92e1902.png', NULL),
(7, 'smk bm3', 'smk-bm3', 'pending', NULL, '2026-01-19 01:33:26', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'public/uploads/school-photos/school_1769055791_6971a62f6ad1f.jpg', NULL),
(8, 'dfgdfgd', 'dfgdfgd', 'pending', NULL, '2026-01-22 08:40:13', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `school_themes`
--

CREATE TABLE `school_themes` (
  `id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `theme_name` varchar(50) DEFAULT 'light',
  `custom_colors` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`custom_colors`)),
  `typography` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`typography`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `school_themes`
--

INSERT INTO `school_themes` (`id`, `school_id`, `theme_name`, `custom_colors`, `typography`, `created_at`, `updated_at`) VALUES
(1, 1, 'dark', '{\"color-text\":\"#ffffff\",\"color-muted\":\"#aabbcc\",\"color-accent\":\"#ff0000\",\"color-danger\":\"#dd0000\",\"color-success\":\"#00ff00\",\"color-border\":\"#0000ff\"}', '{\"font-family\":\"Georgia\",\"font-weight\":\"700\"}', '2026-01-15 03:04:29', '2026-01-15 03:24:34'),
(2, 2, 'light', NULL, NULL, '2026-01-15 03:04:29', '2026-01-21 01:18:41'),
(4, 5, 'indigo', NULL, NULL, '2026-01-15 03:05:01', '2026-01-15 04:14:18'),
(7, 4, 'purple', '{\"color-text\":\"#6b21a8\",\"color-muted\":\"#c084fc\",\"color-accent\":\"#d946ef\",\"color-danger\":\"#dc2626\",\"color-success\":\"#a855f7\",\"color-border\":\"#0000FF\"}', '{\"font-family\":\"Merriweather\",\"font-weight\":\"900\"}', '2026-01-15 03:07:50', '2026-01-15 03:13:44'),
(18, 7, 'sunset', NULL, NULL, '2026-01-19 01:37:02', '2026-01-22 04:23:01');

-- --------------------------------------------------------

--
-- Table structure for table `siswa`
--

CREATE TABLE `siswa` (
  `id_siswa` int(11) NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `nisn` varchar(20) DEFAULT NULL,
  `kelas` varchar(20) DEFAULT NULL,
  `jurusan` varchar(50) DEFAULT NULL,
  `tanggal_lahir` date DEFAULT NULL,
  `jenis_kelamin` char(1) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `no_hp` varchar(15) DEFAULT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `siswa`
--

INSERT INTO `siswa` (`id_siswa`, `nama_lengkap`, `nisn`, `kelas`, `jurusan`, `tanggal_lahir`, `jenis_kelamin`, `alamat`, `email`, `no_hp`, `foto`, `created_at`, `updated_at`) VALUES
(10, 'Adi Triyanto', '333333', 'XII', 'Rekayasa Perangkat Lunak', '2009-02-17', 'L', 'Metland', 'adi@gmail.com', '081234567890', 'uploads/siswa/siswa_10_1768967952_69704f10a01a7.jpeg', '2026-01-21 03:53:09', '2026-01-21 04:02:31'),
(11, 'surya', '2121', NULL, NULL, NULL, NULL, NULL, 'uya4767@gmail.com', NULL, NULL, '2026-01-22 08:02:09', '2026-01-22 08:02:09'),
(13, 'Uya', '232323', NULL, NULL, NULL, NULL, NULL, 'uya4767@gmail.com', NULL, NULL, '2026-01-22 08:41:22', '2026-01-22 08:41:22');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `nisn` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `verification_code` varchar(10) DEFAULT NULL,
  `is_verified` tinyint(1) DEFAULT 0,
  `verified_at` timestamp NULL DEFAULT NULL,
  `role` enum('admin','librarian','student') DEFAULT 'librarian',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `school_id`, `name`, `email`, `nisn`, `password`, `verification_code`, `is_verified`, `verified_at`, `role`, `created_at`) VALUES
(1, 1, 'Admin Sekolah', 'admin@contoh.sch.id', NULL, '$2y$10$PLACEHOLDER_HASH', NULL, 1, '2026-01-22 08:39:46', 'admin', '2026-01-12 06:24:19'),
(2, 2, 'Anjali', 'anjalisaputra@gmail.com', NULL, '$2y$10$w0d2n2raeoL8FioSrPh/0esCDb.i6wk2ZZDjO3Ibqx2lquGn/Zr66', NULL, 1, '2026-01-22 08:39:46', 'admin', '2026-01-12 06:44:08'),
(3, 4, 'surya', 'uya47467@gmail.com', NULL, '$2y$10$Fw9bDgKPX7Vp.F6xXBbCQuqRKkEc5zuJ4zGPHCuJjKZfGlz8ziTuu', NULL, 1, '2026-01-22 08:39:46', 'admin', '2026-01-13 01:53:13'),
(4, 5, 'saya', 'saya@gmail.com', NULL, '$2y$10$doKWQAmV8KM5GLIUC5lHB.nk6MbiubNWq32EWDZohp8BR1mroFblm', NULL, 1, '2026-01-22 08:39:46', 'admin', '2026-01-13 06:47:10'),
(5, 7, 'someone', 'uya4767@gmail.com', NULL, '$2y$10$K7P1nbWS6EqrF69R39PZ5udKqxckTUMDnUDftvP2UmJI.Cvbhfs7y', NULL, 1, '2026-01-22 08:39:46', 'admin', '2026-01-19 01:33:26'),
(7, 2, 'Surya', 'saya@gmail.com', '222222', '$2y$10$cN.EPRDjv3Us66bGdnZ5e.RDqFc7CnYAoh7Srfa1cm06Ey9546302', NULL, 1, '2026-01-22 08:39:46', 'student', '2026-01-20 01:31:31'),
(8, 5, 'surya ali rafsanjani pkl', 'sta@gmail.com', '121212', '$2y$10$SfhCn0ZCGKg8rq26FiguFexzRw85SrU18jfYxgROFfkKfamD0hCaa', NULL, 1, '2026-01-22 08:39:46', 'student', '2026-01-20 07:20:17'),
(10, 2, 'Adi Triyanto', 'adi@gmail.com', '333333', '$2y$10$l942/cijYd9PrkhRZhepWOf.kaeRJsFDy7q0mi6gsb8cCVIJ76wTi', NULL, 1, '2026-01-22 08:39:46', 'student', '2026-01-21 03:52:39'),
(11, 7, 'surya', 'uya4767@gmail.com', '2121', '$2y$10$z/kPXMbQd3AEYEO3lRM0ueZ2AipG.T4cRZSnOivZrH/aFAurg5oOm', NULL, 1, '2026-01-22 08:39:46', 'student', '2026-01-22 04:23:31'),
(12, 8, 'someone', 'someone@sch.id', NULL, '$2y$10$hS7s1zF1Z7afqPGoGTPVrOtWWaZU7K/umdJjJ1hlwYc6.zwuS.Cfu', NULL, 1, '2026-01-22 08:40:31', 'admin', '2026-01-22 08:40:13'),
(13, 8, 'Uya', 'uya4767@gmail.com', '232323', '$2y$10$dSTV7Ym/LoRXVcKlfTuA7OjurZkz0Z6ZB4Gss4QoUnzkx2nayRX2O', NULL, 0, NULL, 'student', '2026-01-22 08:41:10');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `books`
--
ALTER TABLE `books`
  ADD PRIMARY KEY (`id`),
  ADD KEY `school_id` (`school_id`);

--
-- Indexes for table `book_maintenance`
--
ALTER TABLE `book_maintenance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_book_id` (`book_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_updated_at` (`updated_at`);

--
-- Indexes for table `borrows`
--
ALTER TABLE `borrows`
  ADD PRIMARY KEY (`id`),
  ADD KEY `school_id` (`school_id`),
  ADD KEY `book_id` (`book_id`),
  ADD KEY `member_id` (`member_id`);

--
-- Indexes for table `favorites`
--
ALTER TABLE `favorites`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_student_book` (`student_id`,`book_id`),
  ADD KEY `idx_student` (`student_id`),
  ADD KEY `idx_book` (`book_id`),
  ADD KEY `idx_student_book` (`student_id`,`book_id`);

--
-- Indexes for table `members`
--
ALTER TABLE `members`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nisn` (`nisn`),
  ADD KEY `idx_members_school_no` (`school_id`,`member_no`),
  ADD KEY `idx_members_school_status` (`school_id`,`status`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_student_school` (`student_id`,`school_id`),
  ADD KEY `idx_read_status` (`is_read`),
  ADD KEY `idx_type` (`type`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `school_id` (`school_id`),
  ADD KEY `idx_student_unread` (`student_id`,`is_read`,`created_at`),
  ADD KEY `idx_student_type` (`student_id`,`type`,`created_at`);

--
-- Indexes for table `notifikasi`
--
ALTER TABLE `notifikasi`
  ADD PRIMARY KEY (`id_notifikasi`),
  ADD KEY `idx_siswa` (`id_siswa`),
  ADD KEY `idx_status` (`status_baca`),
  ADD KEY `idx_jenis` (`jenis_notifikasi`),
  ADD KEY `idx_tanggal` (`tanggal`);
ALTER TABLE `notifikasi` ADD FULLTEXT KEY `ft_search` (`judul`,`pesan`);

--
-- Indexes for table `schools`
--
ALTER TABLE `schools`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_schools_status` (`status`),
  ADD KEY `idx_slug` (`slug`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `school_themes`
--
ALTER TABLE `school_themes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `school_id` (`school_id`),
  ADD KEY `idx_school_id` (`school_id`);

--
-- Indexes for table `siswa`
--
ALTER TABLE `siswa`
  ADD PRIMARY KEY (`id_siswa`),
  ADD KEY `idx_nisn` (`nisn`),
  ADD KEY `idx_email` (`email`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nisn` (`nisn`),
  ADD KEY `idx_users_school_email` (`school_id`,`email`),
  ADD KEY `idx_verification_code` (`verification_code`),
  ADD KEY `idx_is_verified` (`is_verified`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `books`
--
ALTER TABLE `books`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `book_maintenance`
--
ALTER TABLE `book_maintenance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `borrows`
--
ALTER TABLE `borrows`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `favorites`
--
ALTER TABLE `favorites`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `members`
--
ALTER TABLE `members`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `notifikasi`
--
ALTER TABLE `notifikasi`
  MODIFY `id_notifikasi` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `schools`
--
ALTER TABLE `schools`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `school_themes`
--
ALTER TABLE `school_themes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `siswa`
--
ALTER TABLE `siswa`
  MODIFY `id_siswa` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `books`
--
ALTER TABLE `books`
  ADD CONSTRAINT `books_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `book_maintenance`
--
ALTER TABLE `book_maintenance`
  ADD CONSTRAINT `fk_maintenance_book` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `borrows`
--
ALTER TABLE `borrows`
  ADD CONSTRAINT `borrows_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `borrows_ibfk_2` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `borrows_ibfk_3` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `favorites`
--
ALTER TABLE `favorites`
  ADD CONSTRAINT `favorites_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `favorites_ibfk_2` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `members`
--
ALTER TABLE `members`
  ADD CONSTRAINT `members_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `notifications_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `school_themes`
--
ALTER TABLE `school_themes`
  ADD CONSTRAINT `school_themes_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
