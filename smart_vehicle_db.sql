-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 13, 2026 at 08:11 AM
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
-- Database: `smart_vehicle_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id`, `name`, `username`, `password`) VALUES
(1, 'Abegail Begino', 'begino.abegail@mdci.edu.ph', '$2y$10$W8zsTr.hqtYKg1cwLdq2ne9P1TaotIDknHMMRFrxq3TiA4CLqnfI.');

-- --------------------------------------------------------

--
-- Table structure for table `guards`
--

CREATE TABLE `guards` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `employee_id` varchar(50) NOT NULL,
  `sex` varchar(10) DEFAULT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `guards`
--

INSERT INTO `guards` (`id`, `name`, `employee_id`, `sex`, `password`) VALUES
(1, 'Kris Mae Casusi', 'EM123', 'Female', '$2y$10$KN3DrIAvOQpG8piS4WC3n.BFFceg3iJtwmfkwhCxKKvX/mF81W1OW');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `school_id` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `year_section` varchar(50) DEFAULT NULL,
  `sex` varchar(10) DEFAULT NULL,
  `course` varchar(50) DEFAULT NULL,
  `birthdate` date DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `qr_code_path` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `name`, `school_id`, `email`, `year_section`, `sex`, `course`, `birthdate`, `age`, `password`, `profile_picture`, `qr_code_path`) VALUES
(12, 'Christine Romano', 'STUD12371', 'romano101@mdci.edu.ph', '3-Acabal R', 'Female', 'DSET', '2002-03-11', 23, '$2y$10$EAUsLzn9AN6a65e/prlUr.TcUX6x80Hwn0pVDWi0aG4PhZKDOxR0O', 'uploads/profile_pictures/student_12_1768128344.JPG', 'uploads/qr_codes/student_12_1768125391.png'),
(13, 'Crisalene Caburnay', 'STUD456', 'caburnation@mdci.edu.ph', '3-Acabal R', 'Female', 'DSET', '2002-03-11', 23, '$2y$10$EekN6EdNOW4rmuKNvbcwd.kfIoGfKFHzP4IvbE40AeqjTRAryzAyi', 'uploads/profile_pictures/student_13_1768129837.JPG', 'uploads/qr_codes/student_13_1768129627.png'),
(14, 'Kris Mae Casusi', 'STUD2002', 'kris@mdci.edu.ph', '3-Acabal R', 'Female', 'DSET', '2004-12-23', 21, '$2y$10$XH1gqGJnGIZ5F0NsZgp5S.4ZSURmjZniZ7yFSula1uw6Z7eJJni2m', 'uploads/profile_pictures/student_14_1768199404.JPG', 'uploads/qr_codes/student_14_1768199354.png');

-- --------------------------------------------------------

--
-- Table structure for table `teachers_staff`
--

CREATE TABLE `teachers_staff` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `employee_id` varchar(50) NOT NULL,
  `sex` varchar(10) DEFAULT NULL,
  `course` varchar(50) DEFAULT NULL,
  `birthdate` date DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `qr_code_path` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teachers_staff`
--

INSERT INTO `teachers_staff` (`id`, `name`, `employee_id`, `sex`, `course`, `birthdate`, `age`, `password`, `profile_picture`, `qr_code_path`) VALUES
(3, 'Kyla Araneta', 'EM789', 'Female', 'DSET', '1995-07-09', 35, '$2y$10$iTn1p1MSDDOWDrLfbiFrUuUE3jbZxvzMclyQrlSq18.LIFIoKREXS', NULL, 'uploads/qr_codes/teacher_3_1768126813.png');

-- --------------------------------------------------------

--
-- Table structure for table `vehicles`
--

CREATE TABLE `vehicles` (
  `id` int(11) NOT NULL,
  `owner_type` enum('Teacher/Staff','Student') NOT NULL,
  `owner_id` int(11) NOT NULL,
  `type` enum('Car','Motorcycle') NOT NULL,
  `picture` varchar(255) DEFAULT NULL,
  `registered_under` varchar(100) DEFAULT NULL,
  `color` varchar(50) DEFAULT NULL,
  `brand` varchar(50) DEFAULT NULL,
  `plate_number` varchar(50) NOT NULL,
  `status` enum('Not Expired','Expired','Revoked') DEFAULT 'Not Expired',
  `date_registered` date DEFAULT NULL,
  `date_expiration` date DEFAULT NULL,
  `qr_code_path` varchar(255) DEFAULT NULL,
  `vehicle_image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `vehicles`
--

INSERT INTO `vehicles` (`id`, `owner_type`, `owner_id`, `type`, `picture`, `registered_under`, `color`, `brand`, `plate_number`, `status`, `date_registered`, `date_expiration`, `qr_code_path`, `vehicle_image`) VALUES
(6, 'Student', 12, 'Motorcycle', NULL, 'Badang Lazarter', 'Orange and Black', 'Yamaha Mio', 'AKC5478', 'Not Expired', '2026-01-11', '2026-07-11', 'uploads/qrcodes/AKC5478_1768127144_fixed.png', 'uploads/vehicles/AKC5478_1768125335.jpg'),
(8, 'Teacher/Staff', 3, 'Car', NULL, 'Kyla Araneta', 'Blue', 'Honda', 'HJKLY475K', 'Not Expired', '2026-01-11', '2027-01-11', 'uploads/qrcodes/HJKLY475K_1768127146_fixed.png', 'uploads/vehicles/HJKLY475K_1768126660.jpg'),
(9, 'Student', 13, 'Motorcycle', NULL, 'Dodo Caburnay', 'Orange and Black', 'Click', 'VF47O5P', 'Not Expired', '2026-01-11', '2026-07-11', 'uploads/qrcodes/VF47O5P_1768129250.png', 'uploads/vehicles/VF47O5P_1768129250.jpg'),
(10, 'Student', 14, 'Motorcycle', NULL, 'Leonora Casuse', 'Black and Yellow', 'Honda Beat Scooter', 'GDU1452L', 'Not Expired', '2026-01-12', '2026-07-12', 'uploads/qrcodes/GDU1452L_1768199289.png', 'uploads/vehicles/GDU1452L_1768199289.jpg');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `guards`
--
ALTER TABLE `guards`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `employee_id` (`employee_id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `school_id` (`school_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `teachers_staff`
--
ALTER TABLE `teachers_staff`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `employee_id` (`employee_id`);

--
-- Indexes for table `vehicles`
--
ALTER TABLE `vehicles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `plate_number` (`plate_number`),
  ADD KEY `owner_id` (`owner_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `guards`
--
ALTER TABLE `guards`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `teachers_staff`
--
ALTER TABLE `teachers_staff`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `vehicles`
--
ALTER TABLE `vehicles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

-- ========================================================
-- DEDUP FIX: Remove duplicate vehicle rows (keep latest id per plate_number)
-- Run this once if you have duplicate rows in your vehicles table
-- ========================================================
DELETE v1 FROM vehicles v1
INNER JOIN vehicles v2
WHERE v1.plate_number = v2.plate_number AND v1.id < v2.id;
