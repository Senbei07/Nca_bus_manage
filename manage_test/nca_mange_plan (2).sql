-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 23, 2025 at 10:44 AM
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
(23, 18, 43, 55, 0, 65);

-- --------------------------------------------------------

--
-- Table structure for table `bus_info`
--

CREATE TABLE `bus_info` (
  `bi_id` int(11) NOT NULL,
  `bi_licen` varchar(10) NOT NULL,
  `br_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `bus_info`
--

INSERT INTO `bus_info` (`bi_id`, `bi_licen`, `br_id`) VALUES
(1, '11-1234', 1),
(2, '12-1234', 1),
(3, '13-1234', 1),
(4, '14-1234', 1),
(5, '15-1234', 1),
(6, '16-1234', 1),
(7, '17-1234', 1),
(8, '18-1234', 1),
(9, '22-2115', 2),
(10, '22-2116', 2),
(11, '22-2117', 2),
(12, '22-2118', 2),
(13, '23-2119', 3),
(14, '23-2120', 3),
(15, '23-2121', 3),
(16, '23-2122', 3),
(17, '24-2123', 4),
(18, '24-2124', 4),
(19, '24-2125', 4),
(20, '24-2126', 4);

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
(23, 4, 23, 1);

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
(1, 1, 'สมศักดิ์', 'ใจดี', 1, 1, 1, 1, '3-1', 1),
(2, 1, 'วีระ', 'มีสุข', 1, 2, 1, 1, '3-2', 1),
(3, 2, 'สุดา', 'รักไทย', 2, 3, 1, 1, '3-3', 1),
(4, 1, 'มานะ', 'กล้าหาญ', 1, 4, 1, 1, '3-4', 1),
(5, 3, 'ดวงใจ', 'เมตตา', 2, 5, 1, 1, '1-1', 2),
(6, 1, 'ประเสริฐ', 'ยิ่งยง', 1, 6, 1, 1, '1-2', 1),
(7, 3, 'อรุณี', 'แสงทอง', 2, 7, 1, 1, '2-1', 2),
(8, 1, 'ชัยวัฒน์', 'สุขสบาย', 1, 8, 1, 1, '3-5', 1),
(9, 2, 'เพ็ญศรี', 'รัศมี', 2, 0, 1, 3, '2-1', 1),
(10, 1, 'กำพล', 'พร้อมพงษ์', 1, 0, 1, 3, '2-2', 1),
(11, 3, 'สายชล', 'ธารา', 2, 0, 1, 2, '2-1', 1),
(12, 1, 'ทรงชัย', 'บารมี', 1, 0, 1, 2, '2-2', 1),
(13, 2, 'นงนุช', 'พฤกษา', 2, 0, 1, 2, '2-3', 1),
(14, 1, 'เดชา', 'ชาญชัย', 1, 0, 1, 2, '2-4', 1),
(15, 3, 'ปาริฉัตร', 'งามดี', 2, 0, 1, 2, '2-5', 1),
(16, 1, 'ทนงศักดิ์', 'พงษ์ไทย', 1, 0, 1, 2, '1-1', 1),
(17, 2, 'รัชนี', 'ดารา', 2, 0, 1, 2, '1-2', 1),
(18, 2, 'กานดา', 'ไพเราะ', 2, 0, 1, 3, '2-3', 1),
(19, 1, 'ศักดา', 'มั่นคง', 1, 0, 1, 3, '2-4', 1),
(20, 1, 'บุญมี', 'ลาภผล', 1, 0, 1, 3, '2-5', 1),
(21, 3, 'อัจฉรา', 'ฉลาด', 2, 0, 1, 3, '1-1', 1),
(34, 1, 'สายฟ้า', 'ทองดี', 1, 9, 2, 1, '4-1-1', 1),
(35, 2, 'อรุณ', 'มณี', 2, 10, 2, 1, '3-3-1', 1),
(36, 3, 'ประทีป', 'ศิริ', 1, 11, 2, 1, '2-1-1', 1),
(37, 1, 'รุ่ง', 'จิตร', 2, 12, 2, 1, '2-3-2', 1),
(38, 2, 'สุมาลี', 'บุญมาก', 2, 13, 3, 1, '2-3-3', 1),
(39, 3, 'พนม', 'ชูชาติ', 1, 14, 3, 1, '3-3-3', 1),
(40, 1, 'สมหมาย', 'สายใจ', 1, 15, 3, 1, '3-1-1', 1),
(41, 2, 'สุนี', 'ใจดี', 2, 16, 3, 1, '3-3-2', 1),
(42, 3, 'สุชาติ', 'มานะ', 1, 17, 4, 1, '4-3-2', 1),
(43, 1, 'ศิริพร', 'เพียรดี', 2, 18, 4, 1, '4-3-3', 1),
(44, 2, 'เจริญ', 'เอื้อเฟื้อ', 1, 19, 4, 1, '2-3-1', 1),
(45, 3, 'ผ่องศรี', 'วิชัย', 2, 20, 4, 1, '4-3-1', 1),
(46, 1, 'จิราพร', 'วิเศษ', 2, 0, 2, 2, '2-2-2', 1),
(47, 2, 'มานพ', 'เลิศ', 1, 0, 2, 2, '2-2-3', 1),
(48, 3, 'ปริญญา', 'เบิกบาน', 1, 0, 2, 2, '2-2-4', 1),
(49, 1, 'ชลธิชา', 'เพ็ญ', 2, 0, 2, 2, '2-1-1', 2),
(50, 2, 'สุนันทา', 'นามดี', 2, 0, 3, 2, '3-2-2', 1),
(51, 3, 'วิทยา', 'รุ่งเรือง', 1, 0, 3, 2, '3-2-3', 1),
(52, 1, 'อารี', 'มั่นคง', 1, 0, 3, 2, '3-1-1', 1),
(53, 2, 'บรรเจิด', 'ก้องเกียรติ', 1, 0, 3, 2, '3-2-1', 1),
(54, 3, 'จันทร์เพ็ญ', 'วรางค์', 2, 0, 4, 2, '4-2-2', 1),
(55, 1, 'ณัฐ', 'เสริมสุข', 1, 0, 4, 2, '4-2-3', 1),
(56, 1, 'สุนทร', 'กิติ', 1, 0, 2, 3, '2-2-2', 1),
(57, 2, 'พชร', 'จันทรา', 2, 0, 2, 3, '2-2-3', 1),
(58, 3, 'อารีย์', 'บุญมาก', 2, 0, 2, 3, '2-1-1', 1),
(59, 1, 'วิชัย', 'เกียรติคุณ', 1, 0, 2, 3, '2-2-1', 1),
(60, 2, 'สายพิณ', 'สวัสดี', 2, 0, 3, 3, '3-2-2', 1),
(61, 3, 'สุพัตรา', 'เรืองศรี', 1, 0, 3, 3, '3-2-3', 1),
(62, 1, 'จันทร์', 'สามัคคี', 2, 0, 3, 3, '3-1-1', 1),
(63, 2, 'ประเสริฐ', 'วัฒนกิจ', 1, 0, 3, 3, '3-2-1', 1),
(64, 3, 'สายใจ', 'สมบูรณ์', 2, 0, 4, 3, '4-2-2', 1),
(65, 1, 'อุษา', 'แสงทอง', 2, 0, 4, 3, '4-2-3', 1),
(66, 2, 'พิมพ์', 'เรืองเดช', 2, 0, 4, 2, '4-1-1', 1),
(67, 3, 'อภิชาติ', 'เพชรดี', 1, 0, 4, 2, '4-2-1', 1),
(68, 2, 'อภิญญา', 'รัตนชัย', 2, 0, 4, 3, '4-1-1', 1),
(69, 3, 'เกรียงไกร', 'พูนผล', 1, 0, 4, 3, '4-2-1', 1);

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
(1, 2, '{\"request\":[\"4-3-3\",\"0\",\"3-3-1\"],\"reserve\":[]}'),
(2, 3, '{\"request\":[\"2-3-2\",\"0\",\"0\"],\"reserve\":[]}'),
(3, 4, '{\"request\":[\"0\",\"0\",\"0\"],\"reserve\":[\"2-3-1\"]}');

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
  MODIFY `gb_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `bus_info`
--
ALTER TABLE `bus_info`
  MODIFY `bi_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `bus_plan`
--
ALTER TABLE `bus_plan`
  MODIFY `bp_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

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
  MODIFY `qr_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
