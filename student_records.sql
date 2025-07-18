-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 20, 2025 at 12:14 PM
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
-- Database: `student_records`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `username` varchar(50) NOT NULL,
  `password` varchar(255) DEFAULT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `email_address` varchar(100) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `registrar_id` int(11) NOT NULL,
  `profile_photo` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`username`, `password`, `first_name`, `last_name`, `email_address`, `date_of_birth`, `created_at`, `registrar_id`, `profile_photo`) VALUES
('admin', 'admin123', 'Admin', 'Test', 'adminadmin@ustp.edu.ph', '2111-02-11', '2025-04-24 03:10:11', 1231232131, 'uploads/1745640923_carlos.png'),
('Test', '$2y$10$tqy0v932eVqUeY54DnQ0yer7tVJouBh4UHi6k6.LbTOqTrkqeRfmC', 'Test', 'Admin', 'qweqweqweqweqwe@ustp.edu.ph', '2222-02-22', '2025-04-28 09:01:13', 1231231231, 'uploads/1745834308_Christine.png');

-- --------------------------------------------------------

--
-- Table structure for table `logs`
--

CREATE TABLE `logs` (
  `id` int(11) NOT NULL,
  `date_logged` timestamp NOT NULL DEFAULT current_timestamp(),
  `studentid` varchar(255) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `course` varchar(255) DEFAULT NULL,
  `yearStarted` int(11) DEFAULT NULL,
  `status` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `logs`
--

INSERT INTO `logs` (`id`, `date_logged`, `studentid`, `name`, `course`, `yearStarted`, `status`) VALUES
(5, '2025-04-21 01:34:38', '2022306793', 'Christine Laurente', 'BSET-MST', 2022, 'Active'),
(6, '2025-04-21 01:37:11', '2022306793', 'Christine Laurente', 'BSET-ES', 2023, 'Inactive'),
(7, '2025-04-21 01:42:08', '2022306793', 'Christine Laurente', 'BSET-MST', 2022, 'Active'),
(8, '2025-04-21 01:50:55', '2022306793', 'Christine Laurente', 'BSET-MST', 2022, '0'),
(9, '2025-04-21 01:51:00', '2022306793', 'Christine Laurente', 'BSET-MST', 2022, '0'),
(10, '2025-04-21 01:53:10', '2022306793', 'Christine Laurente', 'BSET-MST', 2022, '0'),
(11, '2025-04-21 01:53:20', '2022306793', 'Christine Laurente', 'BSET-ES', 2015, '0'),
(12, '2025-04-21 01:55:44', '2022306793', 'Christine Laurente', 'BSET-MST', 2022, '0'),
(13, '2025-04-21 01:56:44', '2022306793', 'Christine Laurente', 'BSET-MST', 2022, '0'),
(14, '2025-04-21 01:57:35', '2022306793', 'Christine Laurente', 'BSET-MST', 2024, 'Active'),
(15, '2025-04-29 17:08:45', '2019101020', 'Carlos Miguel Trayfalgar', 'BSET-MST', 2019, 'Active'),
(16, '2025-04-29 17:20:20', '2019101020', 'Carlos Miguel Trayfalgar', 'N/A', 2019, 'N/A'),
(17, '2025-04-30 03:28:46', '2022308058', 'Jimboy Mendez', 'N/A', 2022, 'N/A'),
(18, '2025-04-30 03:29:56', '2022308058', 'Jimboy Mendez', 'BSET-MST', 2022, 'Active'),
(19, '2025-04-30 03:58:22', '2231312', 'Dsds Tdsdsaa', 'N/A', 2025, 'N/A'),
(20, '2025-05-02 05:54:50', '2022310928', 'Jenny Caser', 'N/A', 2022, 'N/A'),
(21, '2025-05-13 06:22:48', '2022310928', 'Jenny Caser', 'N/A', 2022, 'N/A'),
(22, '2025-05-13 06:22:56', '2022310928', 'Jenny Caser', 'BSET-MST', 2022, 'Active'),
(23, '2025-05-14 08:29:22', '2019101020', 'Carlos Miguel Trayfalgar', 'BSET-MST', 2019, 'Active');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `date_logged` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `studentid` varchar(50) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `course` varchar(100) DEFAULT NULL,
  `yearStarted` int(11) DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`date_logged`, `studentid`, `name`, `course`, `yearStarted`, `status`) VALUES
('2025-05-14 08:29:22', '2019101020', 'Carlos Miguel Trayfalgar', 'BSET-MST', 2019, 'Active'),
('2025-04-21 01:57:35', '2022306793', 'Christine Laurente', 'BSET-MST', 2024, 'Active'),
('2025-04-30 03:29:56', '2022308058', 'Jimboy Mendez', 'BSET-MST', 2022, 'Active'),
('2025-05-13 06:22:56', '2022310928', 'Jenny Caser', 'BSET-MST', 2022, 'Active');

-- --------------------------------------------------------

--
-- Table structure for table `student_qr_codes`
--

CREATE TABLE `student_qr_codes` (
  `id` int(11) NOT NULL,
  `studentid` varchar(50) NOT NULL,
  `qr_code` varchar(255) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `yearStarted` varchar(4) DEFAULT NULL,
  `course` varchar(100) DEFAULT NULL,
  `date_logged` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`username`);

--
-- Indexes for table `logs`
--
ALTER TABLE `logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`studentid`),
  ADD UNIQUE KEY `studentid` (`studentid`),
  ADD UNIQUE KEY `studentid_2` (`studentid`);

--
-- Indexes for table `student_qr_codes`
--
ALTER TABLE `student_qr_codes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `studentid` (`studentid`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `logs`
--
ALTER TABLE `logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `student_qr_codes`
--
ALTER TABLE `student_qr_codes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1186;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `student_qr_codes`
--
ALTER TABLE `student_qr_codes`
  ADD CONSTRAINT `fk_student_qr` FOREIGN KEY (`studentid`) REFERENCES `students` (`studentid`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
