-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 06, 2025 at 10:40 AM
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
-- Table structure for table `break_point`
--

CREATE TABLE `break_point` (
  `brkp_id` int(11) NOT NULL,
  `brkp_name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `break_point`
--

INSERT INTO `break_point` (`brkp_id`, `brkp_name`) VALUES
(1, 'จุดรับส่ง 1'),
(2, 'จุดรับส่ง 2'),
(3, 'จุดรับส่ง 3'),
(4, 'จุดรับส่ง 4'),
(5, 'จุดรับส่ง 5'),
(6, 'จุดรับส่ง 6'),
(7, 'จุดรับส่ง 7'),
(8, 'จุดรับส่ง 8'),
(9, 'จุดรับส่ง 9'),
(10, 'จุดรับส่ง 10'),
(11, 'จุดรับส่ง 11'),
(12, 'จุดรับส่ง 12'),
(13, 'จุดรับส่ง 13'),
(14, 'จุดรับส่ง 14'),
(15, 'จุดรับส่ง 15'),
(16, 'จุดรับส่ง 16'),
(17, 'จุดรับส่ง 17'),
(18, 'จุดรับส่ง 18'),
(19, 'จุดรับส่ง 19'),
(20, 'จุดรับส่ง 20'),
(21, 'จุดรับส่ง 21'),
(22, 'จุดรับส่ง 22'),
(23, 'จุดรับส่ง 23'),
(24, 'จุดรับส่ง 24'),
(25, 'จุดรับส่ง 25'),
(26, 'จุดรับส่ง 26'),
(27, 'จุดรับส่ง 27'),
(28, 'จุดรับส่ง 28'),
(29, 'จุดรับส่ง 29'),
(30, 'จุดรับส่ง 30');

-- --------------------------------------------------------

--
-- Table structure for table `brk_in_route`
--

CREATE TABLE `brk_in_route` (
  `bir_id` int(11) NOT NULL,
  `br_id` int(11) NOT NULL,
  `bir_time` int(11) NOT NULL,
  `brkp_id` int(11) NOT NULL,
  `bir_status` int(11) NOT NULL,
  `bir_type` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `brk_in_route`
--

INSERT INTO `brk_in_route` (`bir_id`, `br_id`, `bir_time`, `brkp_id`, `bir_status`, `bir_type`) VALUES
(1, 1, 21, 1, 1, 2),
(2, 1, 33, 2, 2, 1),
(3, 1, 41, 3, 1, 2),
(4, 1, 15, 4, 2, 1),
(5, 1, 28, 5, 2, 1),
(6, 1, 19, 6, 2, 1),
(7, 1, 48, 7, 1, 2),
(8, 1, 55, 8, 2, 1),
(9, 1, 38, 9, 2, 1),
(10, 1, 17, 10, 1, 2),
(11, 2, 19, 10, 1, 2),
(12, 2, 35, 9, 2, 1),
(13, 2, 41, 8, 2, 1),
(14, 2, 59, 7, 1, 2),
(15, 2, 28, 6, 2, 1),
(16, 2, 19, 5, 2, 1),
(17, 2, 32, 4, 2, 1),
(18, 2, 55, 3, 1, 2),
(19, 2, 38, 2, 1, 1),
(20, 2, 17, 1, 1, 2),
(21, 3, 46, 11, 1, 2),
(22, 3, 51, 12, 1, 1),
(23, 3, 25, 13, 1, 2),
(24, 3, 39, 14, 2, 1),
(25, 3, 20, 15, 2, 1),
(26, 3, 56, 16, 2, 1),
(27, 3, 42, 17, 1, 2),
(28, 3, 12, 18, 2, 1),
(29, 3, 53, 19, 1, 1),
(30, 3, 16, 20, 1, 2),
(31, 4, 46, 20, 1, 1),
(32, 4, 51, 19, 1, 2),
(33, 4, 25, 18, 2, 1),
(34, 4, 39, 17, 1, 2),
(35, 4, 41, 16, 2, 1),
(36, 4, 56, 15, 2, 1),
(37, 4, 42, 14, 2, 1),
(38, 4, 29, 13, 1, 2),
(39, 4, 53, 12, 1, 1),
(40, 4, 16, 11, 1, 2);

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
(19, '24-2125', 4, 2),
(20, '24-2126', 4, 2);

-- --------------------------------------------------------

--
-- Table structure for table `bus_plan`
--

CREATE TABLE `bus_plan` (
  `bp_id` int(11) NOT NULL,
  `br_id` int(11) NOT NULL,
  `pr_id` int(11) NOT NULL,
  `bp_pr_no` int(11) NOT NULL,
  `bg_id` int(11) NOT NULL,
  `bs_id` int(11) NOT NULL,
  `bp_date` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

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
(2, 2, 1, 1),
(3, 1, 9, 1),
(4, 9, 1, 1),
(5, 1, 10, 1),
(6, 10, 1, 1),
(7, 1, 11, 1),
(8, 11, 1, 1),
(9, 1, 12, 1),
(10, 12, 1, 1),
(11, 1, 13, 1),
(12, 13, 1, 1),
(13, 1, 14, 1),
(14, 14, 1, 1),
(15, 1, 15, 1),
(16, 15, 1, 1),
(17, 1, 16, 1),
(18, 16, 1, 1),
(19, 1, 17, 1),
(20, 17, 1, 1),
(21, 1, 18, 1),
(22, 18, 1, 1),
(23, 1, 19, 1),
(24, 19, 1, 1),
(25, 1, 20, 1),
(26, 20, 1, 1),
(27, 1, 21, 1),
(28, 21, 1, 1),
(29, 1, 22, 1),
(30, 22, 1, 1),
(31, 1, 23, 1),
(32, 23, 1, 1),
(33, 1, 24, 1),
(34, 24, 1, 1),
(35, 1, 25, 1),
(36, 25, 1, 1),
(37, 1, 26, 1),
(38, 26, 1, 1),
(39, 1, 27, 1),
(40, 27, 1, 1),
(41, 1, 28, 1),
(42, 28, 1, 1),
(43, 1, 29, 1),
(44, 29, 1, 1),
(45, 1, 30, 1),
(46, 30, 1, 1),
(47, 1, 31, 1),
(48, 31, 1, 1),
(49, 1, 32, 1),
(50, 32, 1, 1),
(51, 1, 33, 1),
(52, 33, 1, 1),
(53, 1, 3, 2),
(54, 3, 1, 2),
(55, 1, 4, 2),
(56, 4, 1, 2),
(57, 1, 5, 2),
(58, 5, 1, 2),
(59, 1, 6, 2),
(60, 6, 1, 2),
(61, 1, 7, 2),
(62, 7, 1, 2),
(63, 1, 8, 2),
(64, 8, 1, 2),
(65, 9, 5, 3),
(66, 5, 9, 3),
(67, 9, 4, 3),
(68, 4, 9, 3),
(69, 9, 34, 3),
(70, 34, 9, 3),
(71, 12, 34, 3),
(72, 34, 12, 3),
(73, 34, 5, 3),
(74, 5, 34, 3),
(75, 35, 34, 3),
(76, 34, 35, 3),
(77, 3, 34, 3),
(78, 34, 3, 3),
(79, 11, 34, 3),
(80, 34, 11, 3),
(81, 4, 34, 3),
(82, 34, 4, 3),
(83, 8, 34, 3),
(84, 34, 8, 3),
(85, 9, 3, 3),
(86, 3, 9, 3),
(87, 13, 34, 3),
(88, 34, 13, 3),
(89, 12, 5, 3),
(90, 5, 12, 3),
(91, 36, 34, 3),
(92, 34, 36, 3),
(93, 2, 34, 3),
(94, 34, 2, 3),
(95, 15, 34, 3),
(96, 34, 15, 3),
(97, 22, 34, 3),
(98, 34, 22, 3),
(99, 21, 34, 3),
(100, 34, 21, 3),
(101, 18, 34, 3),
(102, 34, 18, 3);

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
  `em_timeOut` datetime DEFAULT NULL,
  `es_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `employee`
--

INSERT INTO `employee` (`em_id`, `title_id`, `em_name`, `em_surname`, `gen_id`, `main_car`, `main_route`, `et_id`, `em_queue`, `em_timeOut`, `es_id`) VALUES
(1, 1, 'สมศักดิ์', 'ใจดี', 1, 1, 1, 1, '1-3-1', '2025-07-30 20:45:00', 1),
(2, 1, 'วีระ', 'มีสุข', 1, 2, 1, 1, '1-3-2', '2025-07-29 14:47:00', 1),
(3, 2, 'สุดา', 'รักไทย', 2, 3, 1, 1, '1-1-2', '2025-07-29 14:48:00', 1),
(4, 1, 'มานะ', 'กล้าหาญ', 1, 4, 1, 1, '1-1-3', '2025-07-29 14:50:00', 1),
(5, 3, 'ดวงใจ', 'เมตตา', 2, 5, 1, 1, '1-1-1', '2025-07-30 14:50:37', 1),
(6, 1, 'ประเสริฐ', 'ยิ่งยง', 1, 6, 1, 1, '1-3-last', '2025-07-30 14:51:30', 1),
(7, 3, 'อรุณี', 'แสงทอง', 2, 7, 1, 1, '1-3-3', '2025-07-30 14:51:00', 1),
(8, 1, 'ชัยวัฒน์', 'สุขสบาย', 1, 8, 1, 1, '1-3-4', '2025-07-30 14:51:22', 1),
(9, 2, 'เพ็ญศรี', 'รัศมี', 2, 0, 1, 3, '1-1-3', '2025-07-30 14:52:47', 1),
(10, 1, 'กำพล', 'พร้อมพงษ์', 1, 0, 1, 3, '1-2-2', '2025-07-30 14:53:00', 1),
(11, 3, 'สายชล', 'ธารา', 2, 0, 1, 2, '3', '2025-07-30 14:54:23', 1),
(12, 1, 'ทรงชัย', 'บารมี', 1, 0, 1, 2, '3', '2025-07-30 14:55:04', 1),
(13, 2, 'นงนุช', 'พฤกษา', 2, 0, 1, 2, '3', '2025-07-29 14:53:29', 1),
(14, 1, 'เดชา', 'ชาญชัย', 1, 0, 1, 2, '3', '2025-07-30 14:54:09', 1),
(15, 3, 'ปาริฉัตร', 'งามดี', 2, 0, 1, 2, '3', '2025-07-30 14:53:37', 1),
(16, 1, 'ทนงศักดิ์', 'พงษ์ไทย', 1, 0, 1, 2, '3', '2025-07-30 14:54:41', 1),
(17, 2, 'รัชนี', 'ดารา', 2, 0, 1, 2, '7', '2025-07-30 14:53:55', 1),
(18, 2, 'กานดา', 'ไพเราะ', 2, 0, 1, 3, '1-2-4', '2025-07-30 14:53:48', 1),
(19, 1, 'ศักดา', 'มั่นคง', 1, 0, 1, 3, '1-2-5', '2025-07-30 14:54:30', 1),
(20, 1, 'บุญมี', 'ลาภผล', 1, 0, 1, 3, '1-1-3', '2025-07-30 14:55:01', 1),
(21, 3, 'อัจฉรา', 'ฉลาด', 2, 0, 1, 3, '1-2-1', '2025-07-29 14:54:49', 1),
(34, 1, 'สายฟ้า', 'ทองดี', 1, 9, 2, 1, '2-3-1', '2025-07-30 16:11:19', 1),
(35, 2, 'อรุณ', 'มณี', 2, 10, 2, 1, '2-3-2', '2025-07-30 16:01:58', 1),
(36, 3, 'ประทีป', 'ศิริ', 1, 11, 2, 1, '2-3-last', '2025-07-30 15:46:26', 1),
(37, 1, 'รุ่ง', 'จิตร', 2, 12, 2, 1, '2-1-3', '2025-07-30 16:39:58', 1),
(38, 2, 'สุมาลี', 'บุญมาก', 2, 13, 3, 1, '3-3-1', '2025-07-29 17:42:27', 1),
(39, 3, 'พนม', 'ชูชาติ', 1, 14, 3, 1, '3-1-1', '2025-07-30 15:51:37', 1),
(40, 1, 'สมหมาย', 'สายใจ', 1, 15, 3, 1, '3-3-1', '2025-07-30 15:40:15', 1),
(41, 2, 'สุนี', 'ใจดี', 2, 16, 3, 1, '3-3-2', '2025-07-29 17:42:31', 1),
(42, 3, 'สุชาติ', 'มานะ', 1, 17, 4, 1, '4-3-1', '2025-07-30 16:42:09', 1),
(43, 1, 'ศิริพร', 'เพียรดี', 2, 18, 4, 1, '4-3-2', '2025-07-30 15:51:06', 1),
(44, 2, 'เจริญ', 'เอื้อเฟื้อ', 1, 19, 4, 1, '4-3-last', '2025-07-30 15:40:46', 1),
(45, 3, 'ผ่องศรี', 'วิชัย', 2, 20, 4, 1, '4-1-1', '2025-07-29 17:42:34', 1),
(46, 1, 'จิราพร', 'วิเศษ', 2, 0, 2, 2, '7', '2025-07-30 15:42:59', 1),
(47, 2, 'มานพ', 'เลิศ', 1, 0, 2, 2, '7', '2025-07-30 15:58:15', 1),
(48, 3, 'ปริญญา', 'เบิกบาน', 1, 0, 2, 2, '7', '2025-07-29 17:46:05', 1),
(49, 1, 'ชลธิชา', 'เพ็ญ', 2, 0, 2, 2, '7', '2025-07-30 15:23:43', 1),
(50, 2, 'สุนันทา', 'นามดี', 2, 0, 3, 2, '13', '2025-07-30 15:32:50', 1),
(51, 3, 'วิทยา', 'รุ่งเรือง', 1, 0, 3, 2, '13', '2025-07-30 15:53:53', 1),
(52, 1, 'อารี', 'มั่นคง', 1, 0, 3, 2, '13', '2025-07-30 16:24:01', 1),
(53, 2, 'บรรเจิด', 'ก้องเกียรติ', 1, 0, 3, 2, '13', '2025-07-29 17:46:01', 1),
(54, 3, 'จันทร์เพ็ญ', 'วรางค์', 2, 0, 4, 2, '17', '2025-07-30 14:55:40', 1),
(55, 1, 'ณัฐ', 'เสริมสุข', 1, 0, 4, 2, '17', '2025-07-30 14:58:55', 1),
(56, 1, 'สุนทร', 'กิติ', 1, 0, 2, 3, '2-2-1', '2025-07-30 15:34:22', 1),
(57, 2, 'พชร', 'จันทรา', 2, 0, 2, 3, '2-2-1', '2025-07-30 15:54:32', 1),
(58, 3, 'อารีย์', 'บุญมาก', 2, 0, 2, 3, '2-2-1', '2025-07-30 16:24:42', 1),
(59, 1, 'วิชัย', 'เกียรติคุณ', 1, 0, 2, 3, '2-2-2', '2025-07-29 17:45:58', 1),
(60, 2, 'สายพิณ', 'สวัสดี', 2, 0, 3, 3, '3-2-3', '2025-07-30 15:32:41', 1),
(61, 3, 'สุพัตรา', 'เรืองศรี', 1, 0, 3, 3, '3-2-1', '2025-07-30 15:45:21', 1),
(62, 1, 'จันทร์', 'สามัคคี', 2, 0, 3, 3, '3-1-1', '2025-07-30 16:15:27', 1),
(63, 2, 'ประเสริฐ', 'วัฒนกิจ', 1, 0, 3, 3, '3-2-2', '2025-07-29 17:45:55', 1),
(64, 3, 'สายใจ', 'สมบูรณ์', 2, 0, 4, 3, '4-2-2', '2025-07-30 14:33:14', 1),
(65, 1, 'อุษา', 'แสงทอง', 2, 0, 4, 3, '4-2-3', '2025-07-30 14:58:54', 1),
(66, 2, 'พิมพ์', 'เรืองเดช', 2, 0, 4, 2, '17', '2025-07-29 14:45:33', 1),
(67, 3, 'อภิชาติ', 'เพชรดี', 1, 0, 4, 2, '17', '2025-07-30 14:40:51', 1),
(68, 2, 'อภิญญา', 'รัตนชัย', 2, 0, 4, 3, '4-2-1', '2025-07-29 14:55:02', 1),
(69, 3, 'เกรียงไกร', 'พูนผล', 1, 0, 4, 3, '4-1-1', '2025-07-30 14:30:04', 1);

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
-- Table structure for table `plan_request`
--

CREATE TABLE `plan_request` (
  `pr_id` int(11) NOT NULL,
  `pr_name` varchar(255) NOT NULL,
  `br_id` int(11) NOT NULL,
  `pr_date` date NOT NULL,
  `pr_request` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`pr_request`)),
  `pr_plus` int(11) NOT NULL,
  `pr_status` int(11) NOT NULL,
  `pr_loc` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `queue_request`
--

CREATE TABLE `queue_request` (
  `qr_id` int(11) NOT NULL,
  `br_id` int(11) NOT NULL,
  `qr_name` varchar(255) NOT NULL,
  `br_go` int(11) NOT NULL,
  `qr_request` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `qr_return` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`qr_return`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `queue_request`
--

INSERT INTO `queue_request` (`qr_id`, `br_id`, `qr_name`, `br_go`, `qr_request`, `qr_return`) VALUES
(1, 1, 'แผนมาตรฐานของสาย 1', 2, '{\"request\":[\"2\",\"2\",\"2\",\"2\",\"2\"],\"reserve\":[],\"time\":[\"09:45:00\",\"10:45\",\"11:45\",\"13:45\",\"15:45\"],\"time_plus\":[\"300\",\"315\",\"315\",\"315\",\"315\"],\"point\":[[1,2,3,5,6,7,8,9,10],[1,2,3,4,5,6,7,8,9,10],[1,2,3,4,5,6,7,8,9,10],[1,2,3,4,5,6,7,8,9,10],[1,2,3,4,5,6,7,8,9,10]],\"ex\":[{\"start1\":\"3\",\"end1\":\"7\",\"start2\":\"\",\"end2\":\"\"},{\"start1\":\"3\",\"end1\":\"7\",\"start2\":\"\",\"end2\":\"\"},{\"start1\":\"3\",\"end1\":\"7\",\"start2\":\"\",\"end2\":\"\"},{\"start1\":\"3\",\"end1\":\"7\",\"start2\":\"\",\"end2\":\"\"},{\"start1\":\"3\",\"end1\":\"7\",\"start2\":\"\",\"end2\":\"\"}]}', '[\"2\"]'),
(2, 2, 'แผนมาตรฐานของสาย 2', 1, '{\"request\":[\"2\",\"2\",\"2\"],\"reserve\":[],\"time\":[\"09:45:00\",\"10:45\",\"11:45\"],\"time_plus\":[\"343\",\"343\",\"343\"],\"point\":[[10,9,8,7,6,5,4,3,2,1],[10,9,8,6,5,4,2,1,7,3],[10,9,8,6,5,4,2,1,7,3]],\"ex\":[{\"start1\":\"7\",\"end1\":\"3\",\"start2\":\"\",\"end2\":\"\"},{\"start1\":\"7\",\"end1\":\"3\",\"start2\":\"\",\"end2\":\"\"},{\"start1\":\"7\",\"end1\":\"3\",\"start2\":\"\",\"end2\":\"\"}]}', '[\"1\"]'),
(3, 3, 'แผนมาตรฐานของสาย 3', 4, '{\"request\":[\"2\",\"2\",\"2\"],\"reserve\":[],\"time\":[\"09:45\",\"12:45\",\"15:45\"],\"time_plus\":[\"360\",\"360\",\"360\"],\"point\":[[14,15,16,18,19,20,11,12,13,17],[11,12,13,14,15,16,17,18,19,20],[14,15,16,18,19,20,11,12,13,17]],\"ex\":[{\"start1\":\"13\",\"end1\":\"17\",\"start2\":\"\",\"end2\":\"\"},{\"start1\":\"13\",\"end1\":\"17\",\"start2\":\"\",\"end2\":\"\"},{\"start1\":\"13\",\"end1\":\"17\",\"start2\":\"\",\"end2\":\"\"}]}', '[\"4\"]'),
(4, 4, 'แผนมาตรฐานของสาย 4', 3, '{\"request\":[\"2\",\"2\",\"2\"],\"reserve\":[],\"time\":[\"10:45\",\"13:30\",\"18:45\"],\"time_plus\":[\"398\",\"398\",\"398\"],\"point\":[[20,19,18,16,15,14,17,13,12,11],[20,19,18,16,15,14,17,13,12,11],[20,19,18,16,15,14,17,13,12,11]],\"ex\":[{\"start1\":\"17\",\"end1\":\"13\",\"start2\":\"\",\"end2\":\"\"},{\"start1\":\"17\",\"end1\":\"13\",\"start2\":\"\",\"end2\":\"\"},{\"start1\":\"17\",\"end1\":\"13\",\"start2\":\"\",\"end2\":\"\"}]}', '[\"3\"]');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `break_point`
--
ALTER TABLE `break_point`
  ADD PRIMARY KEY (`brkp_id`);

--
-- Indexes for table `brk_in_route`
--
ALTER TABLE `brk_in_route`
  ADD PRIMARY KEY (`bir_id`);

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
-- Indexes for table `emp_history`
--
ALTER TABLE `emp_history`
  ADD PRIMARY KEY (`eh_id`);

--
-- Indexes for table `location`
--
ALTER TABLE `location`
  ADD PRIMARY KEY (`locat_id`);

--
-- Indexes for table `plan_request`
--
ALTER TABLE `plan_request`
  ADD PRIMARY KEY (`pr_id`);

--
-- Indexes for table `queue_request`
--
ALTER TABLE `queue_request`
  ADD PRIMARY KEY (`qr_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `break_point`
--
ALTER TABLE `break_point`
  MODIFY `brkp_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `brk_in_route`
--
ALTER TABLE `brk_in_route`
  MODIFY `bir_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `bus_group`
--
ALTER TABLE `bus_group`
  MODIFY `gb_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bus_info`
--
ALTER TABLE `bus_info`
  MODIFY `bi_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `bus_plan`
--
ALTER TABLE `bus_plan`
  MODIFY `bp_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bus_routes`
--
ALTER TABLE `bus_routes`
  MODIFY `br_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=103;

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
-- AUTO_INCREMENT for table `emp_history`
--
ALTER TABLE `emp_history`
  MODIFY `eh_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `location`
--
ALTER TABLE `location`
  MODIFY `locat_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `plan_request`
--
ALTER TABLE `plan_request`
  MODIFY `pr_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `queue_request`
--
ALTER TABLE `queue_request`
  MODIFY `qr_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
