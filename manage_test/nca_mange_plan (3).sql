-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 25, 2025 at 08:20 AM
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
-- Database: `nca_mange_plan`
--

-- --------------------------------------------------------

--
-- Table structure for table `bus_group`
--

CREATE TABLE `bus_group` (
  `gb_id` int(11) NOT NULL,
  `bi_id` int(11) NOT NULL,
  `main_dri` int(11) NOT NULL,
  `ex_1` int(11) NOT NULL,
  `ex_2` int(11) NOT NULL,
  `coach` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `bus_group`
--

INSERT INTO `bus_group` (`gb_id`, `bi_id`, `main_dri`, `ex_1`, `ex_2`, `coach`) VALUES
(1, 1, 1, 11, 0, 9),
(2, 2, 2, 12, 0, 10),
(3, 3, 3, 13, 0, 18),
(4, 4, 4, 14, 0, 19),
(5, 5, 8, 15, 0, 20),
(6, 9, 34, 46, 0, 56),
(7, 10, 35, 47, 0, 57),
(8, 11, 36, 48, 0, 58),
(9, 13, 38, 50, 0, 60),
(10, 14, 39, 51, 0, 61),
(11, 15, 40, 52, 0, 62),
(12, 17, 42, 54, 0, 64),
(13, 18, 43, 55, 0, 65),
(14, 19, 44, 66, 0, 68),
(15, 19, 44, 46, 0, 59),
(16, 12, 37, 47, 0, 56),
(17, 13, 38, 48, 0, 57),
(18, 10, 35, 53, 0, 63),
(19, 16, 41, 50, 0, 60),
(20, 14, 39, 51, 0, 61),
(21, 20, 45, 67, 0, 69),
(22, 17, 42, 54, 0, 64),
(23, 18, 43, 55, 0, 65),
(24, 6, 6, 16, 0, 21),
(25, 1, 1, 17, 0, 9),
(26, 2, 2, 11, 0, 10),
(27, 3, 3, 12, 0, 18),
(28, 4, 4, 13, 0, 19),
(29, 5, 5, 14, 0, 20),
(30, 7, 7, 15, 0, 21),
(31, 8, 8, 16, 0, 9),
(32, 6, 6, 17, 0, 10),
(33, 1, 1, 11, 0, 18),
(34, 2, 2, 12, 0, 19),
(35, 3, 3, 13, 0, 20),
(36, 4, 4, 14, 0, 21),
(37, 5, 5, 15, 0, 9),
(38, 7, 7, 16, 0, 10),
(39, 8, 8, 17, 0, 18),
(40, 6, 6, 11, 0, 19),
(41, 1, 1, 12, 0, 20),
(42, 18, 43, 46, 0, 58),
(43, 11, 36, 47, 0, 59),
(44, 10, 35, 48, 0, 56),
(45, 12, 37, 52, 0, 62),
(46, 15, 40, 53, 0, 63),
(47, 16, 41, 50, 0, 60),
(48, 9, 34, 66, 0, 68),
(49, 20, 45, 67, 0, 69),
(50, 17, 42, 54, 0, 64),
(51, 17, 42, 46, 0, 57),
(52, 13, 38, 47, 0, 58),
(53, 12, 37, 48, 0, 59),
(54, 11, 36, 51, 0, 61),
(55, 14, 39, 52, 0, 62),
(56, 15, 40, 53, 0, 63),
(57, 19, 44, 55, 0, 65),
(58, 9, 34, 66, 0, 68),
(59, 20, 45, 67, 0, 69),
(60, 20, 45, 46, 0, 56),
(61, 10, 35, 47, 0, 57),
(62, 11, 36, 48, 0, 58),
(63, 13, 38, 50, 0, 60),
(64, 16, 41, 51, 0, 61),
(65, 14, 39, 52, 0, 62),
(66, 19, 44, 54, 0, 64),
(67, 18, 43, 55, 0, 65),
(68, 9, 34, 66, 0, 68);

-- --------------------------------------------------------

--
-- Table structure for table `bus_info`
--

CREATE TABLE `bus_info` (
  `bi_id` int(11) NOT NULL,
  `bi_licen` varchar(10) NOT NULL,
  `br_id` int(11) NOT NULL,
  `bt_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `bus_info`
--

INSERT INTO `bus_info` (`bi_id`, `bi_licen`, `br_id`, `bt_id`) VALUES
(1, '11-1234', 1, 2),
(2, '12-1234', 1, 2),
(3, '13-1234', 1, 2),
(4, '14-1234', 1, 2),
(5, '15-1234', 1, 2),
(6, '16-1234', 1, 2),
(7, '17-1234', 1, 2),
(8, '18-1234', 1, 2),
(9, '22-2115', 2, 2),
(10, '22-2116', 2, 2),
(11, '22-2117', 2, 2),
(12, '22-2118', 2, 2),
(13, '23-2119', 3, 2),
(14, '23-2120', 3, 2),
(15, '23-2121', 3, 2),
(16, '23-2122', 3, 2),
(17, '24-2123', 4, 2),
(18, '24-2124', 4, 2),
(19, '24-2125', 4, 1),
(20, '24-2126', 4, 2);

-- --------------------------------------------------------

--
-- Table structure for table `bus_plan`
--

CREATE TABLE `bus_plan` (
  `bp_id` int(11) NOT NULL,
  `br_id` int(11) NOT NULL,
  `bg_id` int(11) NOT NULL,
  `bs_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `bus_plan`
--

INSERT INTO `bus_plan` (`bp_id`, `br_id`, `bg_id`, `bs_id`) VALUES
(1, 1, 1, 1),
(2, 1, 2, 1),
(3, 1, 3, 1),
(4, 1, 4, 1),
(5, 1, 5, 1),
(6, 2, 6, 1),
(7, 2, 7, 1),
(8, 2, 8, 1),
(9, 3, 9, 1),
(10, 3, 10, 1),
(11, 3, 11, 1),
(12, 4, 12, 1),
(13, 4, 13, 1),
(14, 4, 14, 1),
(15, 2, 15, 1),
(16, 2, 16, 1),
(17, 2, 17, 1),
(18, 3, 18, 1),
(19, 3, 19, 1),
(20, 3, 20, 1),
(21, 4, 21, 1),
(22, 4, 22, 1),
(23, 4, 23, 1),
(24, 1, 24, 1),
(25, 1, 25, 1),
(26, 1, 26, 1),
(27, 1, 27, 1),
(28, 1, 28, 1),
(29, 1, 29, 1),
(30, 1, 30, 1),
(31, 1, 31, 1),
(32, 1, 32, 1),
(33, 1, 33, 1),
(34, 1, 34, 1),
(35, 1, 35, 1),
(36, 1, 36, 1),
(37, 1, 37, 1),
(38, 1, 38, 1),
(39, 1, 39, 1),
(40, 1, 40, 1),
(41, 1, 41, 1),
(42, 2, 42, 1),
(43, 2, 43, 1),
(44, 2, 44, 1),
(45, 3, 45, 1),
(46, 3, 46, 1),
(47, 3, 47, 1),
(48, 4, 48, 1),
(49, 4, 49, 1),
(50, 4, 50, 1),
(51, 2, 51, 1),
(52, 2, 52, 1),
(53, 2, 53, 1),
(54, 3, 54, 1),
(55, 3, 55, 1),
(56, 3, 56, 1),
(57, 4, 57, 1),
(58, 4, 58, 1),
(59, 4, 59, 1),
(60, 2, 60, 1),
(61, 2, 61, 1),
(62, 2, 62, 1),
(63, 3, 63, 1),
(64, 3, 64, 1),
(65, 3, 65, 1),
(66, 4, 66, 1),
(67, 4, 67, 1),
(68, 4, 68, 1);

-- --------------------------------------------------------

--
-- Table structure for table `bus_routes`
--

CREATE TABLE `bus_routes` (
  `br_id` int(11) NOT NULL,
  `br_start` int(11) NOT NULL,
  `br_end` int(11) NOT NULL,
  `bz_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `bus_routes`
--

INSERT INTO `bus_routes` (`br_id`, `br_start`, `br_end`, `bz_id`) VALUES
(1, 1, 2, 1),
(2, 1, 9, 1),
(3, 1, 10, 1),
(4, 1, 11, 1),
(5, 1, 12, 1),
(6, 1, 13, 1),
(7, 1, 14, 1),
(8, 1, 15, 1),
(9, 1, 16, 1),
(10, 1, 17, 1),
(11, 1, 18, 1),
(12, 1, 19, 1),
(13, 1, 20, 1),
(14, 1, 21, 1),
(15, 1, 22, 1),
(16, 1, 23, 1),
(17, 1, 24, 1),
(18, 1, 25, 1),
(19, 1, 26, 1),
(20, 1, 27, 1),
(21, 1, 28, 1),
(22, 1, 29, 1),
(23, 1, 30, 1),
(24, 1, 31, 1),
(25, 1, 32, 1),
(26, 1, 33, 1),
(27, 1, 3, 2),
(28, 1, 4, 2),
(29, 1, 5, 2),
(30, 1, 6, 2),
(31, 1, 7, 2),
(32, 1, 8, 2),
(33, 9, 5, 3),
(34, 9, 4, 3),
(35, 9, 34, 3),
(36, 12, 34, 3),
(37, 34, 5, 3),
(38, 35, 34, 3),
(39, 3, 34, 3),
(40, 11, 34, 3),
(41, 4, 34, 3),
(42, 8, 34, 3),
(43, 9, 3, 3),
(44, 13, 34, 3),
(45, 12, 5, 3),
(46, 36, 34, 3),
(47, 2, 34, 3),
(48, 15, 34, 3),
(49, 22, 34, 3),
(50, 21, 34, 3),
(51, 18, 34, 3);

-- --------------------------------------------------------

--
-- Table structure for table `bus_zone`
--

CREATE TABLE `bus_zone` (
  `bz_id` int(11) NOT NULL,
  `bz_name_th` varchar(255) NOT NULL,
  `bz_name_en` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `bus_zone`
--

INSERT INTO `bus_zone` (`bz_id`, `bz_name_th`, `bz_name_en`) VALUES
(1, 'ภาคตะวันออกเฉียงเหนือ (อีสาน)', 'Northeastern'),
(2, 'ภาคเหนือ', 'Northern'),
(3, 'ต่างจังหวัด', 'Cross-regional');

-- --------------------------------------------------------

--
-- Table structure for table `employee`
--

CREATE TABLE `employee` (
  `em_id` int(11) NOT NULL,
  `title_id` int(11) NOT NULL,
  `em_name` varchar(255) NOT NULL,
  `em_surname` varchar(255) NOT NULL,
  `gen_id` int(11) NOT NULL,
  `main_car` int(11) NOT NULL,
  `main_route` int(11) NOT NULL,
  `et_id` int(11) NOT NULL,
  `em_queue` varchar(10) NOT NULL,
  `es_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `employee`
--

INSERT INTO `employee` (`em_id`, `title_id`, `em_name`, `em_surname`, `gen_id`, `main_car`, `main_route`, `et_id`, `em_queue`, `es_id`) VALUES
(1, 1, 'สมศักดิ์', 'ใจดี', 1, 1, 1, 1, '1-3-last', 1),
(2, 1, 'วีระ', 'มีสุข', 1, 2, 1, 1, '1-1-1', 1),
(3, 2, 'สุดา', 'รักไทย', 2, 3, 1, 1, '1-1-2', 1),
(4, 1, 'มานะ', 'กล้าหาญ', 1, 4, 1, 1, '1-1-3', 1),
(5, 3, 'ดวงใจ', 'เมตตา', 2, 5, 1, 1, '1-3-1', 1),
(6, 1, 'ประเสริฐ', 'ยิ่งยง', 1, 6, 1, 1, '1-3-4', 1),
(7, 3, 'อรุณี', 'แสงทอง', 2, 7, 1, 1, '1-3-2', 1),
(8, 1, 'ชัยวัฒน์', 'สุขสบาย', 1, 8, 1, 1, '1-3-3', 1),
(9, 2, 'เพ็ญศรี', 'รัศมี', 2, 0, 1, 3, '1-2-1', 1),
(10, 1, 'กำพล', 'พร้อมพงษ์', 1, 0, 1, 3, '1-2-2', 1),
(11, 3, 'สายชล', 'ธารา', 2, 0, 1, 2, '1-2-4', 1),
(12, 1, 'ทรงชัย', 'บารมี', 1, 0, 1, 2, '3-last', 1),
(13, 2, 'นงนุช', 'พฤกษา', 2, 0, 1, 2, '1-1-1', 1),
(14, 1, 'เดชา', 'ชาญชัย', 1, 0, 1, 2, '1-1-2', 1),
(15, 3, 'ปาริฉัตร', 'งามดี', 2, 0, 1, 2, '1-2-1', 1),
(16, 1, 'ทนงศักดิ์', 'พงษ์ไทย', 1, 0, 1, 2, '1-2-2', 1),
(17, 2, 'รัชนี', 'ดารา', 2, 0, 1, 2, '1-2-3', 1),
(18, 2, 'กานดา', 'ไพเราะ', 2, 0, 1, 3, '1-2-3', 1),
(19, 1, 'ศักดา', 'มั่นคง', 1, 0, 1, 3, '1-2-4', 1),
(20, 1, 'บุญมี', 'ลาภผล', 1, 0, 1, 3, '1-3-last', 1),
(21, 3, 'อัจฉรา', 'ฉลาด', 2, 0, 1, 3, '1-1-1', 1),
(34, 1, 'สายฟ้า', 'ทองดี', 1, 9, 2, 1, '4-3-last', 1),
(35, 2, 'อรุณ', 'มณี', 2, 10, 2, 1, '2-3-2', 1),
(36, 3, 'ประทีป', 'ศิริ', 1, 11, 2, 1, '2-3-last', 1),
(37, 1, 'รุ่ง', 'จิตร', 2, 12, 2, 1, '2-1-1', 1),
(38, 2, 'สุมาลี', 'บุญมาก', 2, 13, 3, 1, '3-3-1', 1),
(39, 3, 'พนม', 'ชูชาติ', 1, 14, 3, 1, '3-3-last', 1),
(40, 1, 'สมหมาย', 'สายใจ', 1, 15, 3, 1, '3-1-1', 1),
(41, 2, 'สุนี', 'ใจดี', 2, 16, 3, 1, '3-3-2', 1),
(42, 3, 'สุชาติ', 'มานะ', 1, 17, 4, 1, '4-1-1', 1),
(43, 1, 'ศิริพร', 'เพียรดี', 2, 18, 4, 1, '4-3-2', 1),
(44, 2, 'เจริญ', 'เอื้อเฟื้อ', 1, 19, 4, 1, '4-3-1', 1),
(45, 3, 'ผ่องศรี', 'วิชัย', 2, 20, 4, 1, '2-3-1', 1),
(46, 1, 'จิราพร', 'วิเศษ', 2, 0, 2, 2, '2-2-2', 1),
(47, 2, 'มานพ', 'เลิศ', 1, 0, 2, 2, '2-2-3', 1),
(48, 3, 'ปริญญา', 'เบิกบาน', 1, 0, 2, 2, '2-2-4', 1),
(49, 1, 'ชลธิชา', 'เพ็ญ', 2, 0, 2, 2, '2-1-1', 2),
(50, 2, 'สุนันทา', 'นามดี', 2, 0, 3, 2, '3-2-1', 1),
(51, 3, 'วิทยา', 'รุ่งเรือง', 1, 0, 3, 2, '3-2-2', 1),
(52, 1, 'อารี', 'มั่นคง', 1, 0, 3, 2, '3-2-3', 1),
(53, 2, 'บรรเจิด', 'ก้องเกียรติ', 1, 0, 3, 2, '3-1-1', 1),
(54, 3, 'จันทร์เพ็ญ', 'วรางค์', 2, 0, 4, 2, '4-2-1', 1),
(55, 1, 'ณัฐ', 'เสริมสุข', 1, 0, 4, 2, '4-2-2', 1),
(56, 1, 'สุนทร', 'กิติ', 1, 0, 2, 3, '2-2-1', 1),
(57, 2, 'พชร', 'จันทรา', 2, 0, 2, 3, '2-2-2', 1),
(58, 3, 'อารีย์', 'บุญมาก', 2, 0, 2, 3, '2-2-3', 1),
(59, 1, 'วิชัย', 'เกียรติคุณ', 1, 0, 2, 3, '2-1-1', 1),
(60, 2, 'สายพิณ', 'สวัสดี', 2, 0, 3, 3, '3-2-1', 1),
(61, 3, 'สุพัตรา', 'เรืองศรี', 1, 0, 3, 3, '3-2-2', 1),
(62, 1, 'จันทร์', 'สามัคคี', 2, 0, 3, 3, '3-2-3', 1),
(63, 2, 'ประเสริฐ', 'วัฒนกิจ', 1, 0, 3, 3, '3-1-1', 1),
(64, 3, 'สายใจ', 'สมบูรณ์', 2, 0, 4, 3, '4-2-1', 1),
(65, 1, 'อุษา', 'แสงทอง', 2, 0, 4, 3, '4-2-2', 1),
(66, 2, 'พิมพ์', 'เรืองเดช', 2, 0, 4, 2, '4-2-3', 1),
(67, 3, 'อภิชาติ', 'เพชรดี', 1, 0, 4, 2, '4-1-1', 1),
(68, 2, 'อภิญญา', 'รัตนชัย', 2, 0, 4, 3, '4-2-3', 1),
(69, 3, 'เกรียงไกร', 'พูนผล', 1, 0, 4, 3, '4-1-1', 1);

-- --------------------------------------------------------

--
-- Table structure for table `emp_history`
--

CREATE TABLE `emp_history` (
  `eh_id` int(11) NOT NULL,
  `em_id` int(11) NOT NULL,
  `eh_his` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `location`
--

CREATE TABLE `location` (
  `locat_id` int(11) NOT NULL,
  `locat_name_th` varchar(100) NOT NULL,
  `locat_name_eng` varchar(100) NOT NULL,
  `lot_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `location`
--

INSERT INTO `location` (`locat_id`, `locat_name_th`, `locat_name_eng`, `lot_id`) VALUES
(1, 'กรุงเทพฯ', 'Bangkok', 1),
(2, 'ขอนแก่น', 'Khon Kaen', 1),
(3, 'น่าน', 'Nan', 1),
(4, 'เชียงราย', 'Chiang Rai', 1),
(5, 'เชียงใหม่', 'Chiang Mai', 1),
(6, 'ลำปาง', 'Lampang', 1),
(7, 'แพร่', 'Phrae', 1),
(8, 'อุตรดิตถ์', 'Uttaradit', 1),
(9, 'อุบลราชธานี', 'Ubon Ratchathani', 1),
(10, 'อ.เดชอุดม', 'Det Udom', 1),
(11, 'ศรีสะเกษ', 'Si Sa Ket', 1),
(12, 'สุรินทร์', 'Surin', 1),
(13, 'นางรอง,บุรีรัมย์', 'Nang Rong', 1),
(14, 'หนองคาย', 'Nong Khai', 1),
(15, 'อุดรธานี', 'Udon Thani', 1),
(16, 'มหาสารคาม', 'Maha Sarakham', 1),
(17, 'หนองบัวลำภู', 'Nong Bua Lamphu', 1),
(18, 'นครพนม', 'Nakhon Phanom', 1),
(19, 'อ.ธาตุพนม', 'That Phanom', 1),
(20, 'อ.ศรีสงคราม', 'Si Songkhram', 1),
(21, 'สกลนคร', 'Sakon Nakhon', 1),
(22, 'กาฬสินธุ์', 'Kalasin', 1),
(23, 'อ.คำม่วง', 'Kham Muang', 1),
(24, 'มุกดาหาร', 'Mukdahan', 1),
(25, 'ร้อยเอ็ด', 'Roi Et', 1),
(26, 'อำนาจเจริญ', 'Amnat Charoen', 1),
(27, 'สนม', 'Sanom', 1),
(28, 'อ.เขมราฐ', 'Khemarat', 1),
(29, 'จักราช,บุรีรัมย์', 'Chakkarat', 1),
(30, 'ยโสธร', 'Yasothon', 1),
(31, 'บ้านแพง', 'Ban Phaeng', 1),
(32, 'อ.บุณฑริก', 'Buntharik', 1),
(33, 'อ.ราษีไศล', 'Rasi Salai', 1),
(34, 'ระยอง', 'Rayong', 1),
(35, 'อ.แม่สาย', 'Mae Sai', 1),
(36, 'พิษณุโลก', 'Phitsanulok', 1);

-- --------------------------------------------------------

--
-- Table structure for table `queue_request`
--

CREATE TABLE `queue_request` (
  `qr_id` int(11) NOT NULL,
  `br_id` int(11) NOT NULL,
  `qr_request` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `queue_request`
--

INSERT INTO `queue_request` (`qr_id`, `br_id`, `qr_request`) VALUES
(1, 2, '{\"request\":[\"4-3-last\",\"2\",\"3-3-1\"],\"reserve\":[]}'),
(2, 3, '{\"request\":[\"2-3-2\",\"2\",\"2\"],\"reserve\":[]}'),
(3, 4, '{\"request\":[\"1\",\"2\",\"2\"],\"reserve\":[\"2-3-1\"]}'),
(4, 1, '{\"request\":[\"2\",\"2\",\"2\",\"2\",\"2\"],\"reserve\":[]}');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bus_group`
--
ALTER TABLE `bus_group`
  ADD PRIMARY KEY (`gb_id`);

--
-- Indexes for table `bus_info`
--
ALTER TABLE `bus_info`
  ADD PRIMARY KEY (`bi_id`);

--
-- Indexes for table `bus_plan`
--
ALTER TABLE `bus_plan`
  ADD PRIMARY KEY (`bp_id`);

--
-- Indexes for table `bus_routes`
--
ALTER TABLE `bus_routes`
  ADD PRIMARY KEY (`br_id`);

--
-- Indexes for table `bus_zone`
--
ALTER TABLE `bus_zone`
  ADD PRIMARY KEY (`bz_id`);

--
-- Indexes for table `employee`
--
ALTER TABLE `employee`
  ADD PRIMARY KEY (`em_id`);

--
-- Indexes for table `location`
--
ALTER TABLE `location`
  ADD PRIMARY KEY (`locat_id`);

--
-- Indexes for table `queue_request`
--
ALTER TABLE `queue_request`
  ADD PRIMARY KEY (`qr_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bus_group`
--
ALTER TABLE `bus_group`
  MODIFY `gb_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=69;

--
-- AUTO_INCREMENT for table `bus_info`
--
ALTER TABLE `bus_info`
  MODIFY `bi_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `bus_plan`
--
ALTER TABLE `bus_plan`
  MODIFY `bp_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=69;

--
-- AUTO_INCREMENT for table `bus_routes`
--
ALTER TABLE `bus_routes`
  MODIFY `br_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=52;

--
-- AUTO_INCREMENT for table `bus_zone`
--
ALTER TABLE `bus_zone`
  MODIFY `bz_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `employee`
--
ALTER TABLE `employee`
  MODIFY `em_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=106;

--
-- AUTO_INCREMENT for table `location`
--
ALTER TABLE `location`
  MODIFY `locat_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `queue_request`
--
ALTER TABLE `queue_request`
  MODIFY `qr_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
