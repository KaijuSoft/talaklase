-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 13, 2026 at 07:38 AM
-- Server version: 10.4.27-MariaDB
-- PHP Version: 8.2.0

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `talaklasedb`
--

-- --------------------------------------------------------

--
-- Table structure for table `assign_subject`
--

CREATE TABLE `assign_subject` (
  `assign_id` int(11) NOT NULL,
  `sub_id` int(11) DEFAULT NULL,
  `inst_id` int(11) DEFAULT NULL,
  `course_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `Att_ID` int(10) NOT NULL,
  `st_id` int(11) NOT NULL,
  `sectionID` int(11) NOT NULL,
  `_date` varchar(10) NOT NULL,
  `status` varchar(20) NOT NULL,
  `term` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`Att_ID`, `st_id`, `sectionID`, `_date`, `status`, `term`) VALUES
(1, 99, 3, '2025-12-16', 'Absent', 'Prelim'),
(2, 96, 3, '2025-12-16', 'Absent', 'Prelim'),
(3, 108, 3, '2025-12-16', 'Absent', 'Prelim'),
(4, 79, 3, '2025-12-16', 'Present', 'Prelim'),
(5, 98, 3, '2025-12-16', 'Absent', 'Prelim'),
(6, 80, 3, '2025-12-16', 'Absent', 'Prelim'),
(7, 91, 3, '2025-12-16', 'Absent', 'Prelim'),
(8, 81, 3, '2025-12-16', 'Absent', 'Prelim'),
(9, 105, 3, '2025-12-16', 'Present', 'Prelim'),
(10, 74, 3, '2025-12-16', 'Absent', 'Prelim'),
(11, 94, 3, '2025-12-16', 'Absent', 'Prelim'),
(12, 75, 3, '2025-12-16', 'Present', 'Prelim'),
(13, 86, 3, '2025-12-16', 'Absent', 'Prelim'),
(14, 76, 3, '2025-12-16', 'Present', 'Prelim'),
(15, 104, 3, '2025-12-16', 'Absent', 'Prelim'),
(16, 95, 3, '2025-12-16', 'Absent', 'Prelim'),
(17, 82, 3, '2025-12-16', 'Absent', 'Prelim'),
(18, 97, 3, '2025-12-16', 'Absent', 'Prelim'),
(19, 102, 3, '2025-12-16', 'Absent', 'Prelim'),
(20, 101, 3, '2025-12-16', 'Absent', 'Prelim'),
(21, 110, 3, '2025-12-16', 'Absent', 'Prelim'),
(22, 87, 3, '2025-12-16', 'Absent', 'Prelim'),
(23, 77, 3, '2025-12-16', 'Absent', 'Prelim'),
(24, 78, 3, '2025-12-16', 'Absent', 'Prelim'),
(25, 88, 3, '2025-12-16', 'Absent', 'Prelim'),
(26, 103, 3, '2025-12-16', 'Absent', 'Prelim'),
(27, 93, 3, '2025-12-16', 'Absent', 'Prelim'),
(28, 89, 3, '2025-12-16', 'Absent', 'Prelim'),
(29, 100, 3, '2025-12-16', 'Absent', 'Prelim'),
(30, 31, 3, '2025-12-16', 'Absent', 'Prelim'),
(31, 109, 3, '2025-12-16', 'Absent', 'Prelim'),
(32, 90, 3, '2025-12-16', 'Present', 'Prelim'),
(33, 92, 3, '2025-12-16', 'Absent', 'Prelim'),
(34, 83, 3, '2025-12-16', 'Present', 'Prelim'),
(35, 107, 3, '2025-12-16', 'Absent', 'Prelim'),
(36, 84, 3, '2025-12-16', 'Absent', 'Prelim'),
(37, 106, 3, '2025-12-16', 'Absent', 'Prelim'),
(38, 85, 3, '2025-12-16', 'Present', 'Prelim'),
(39, 44, 2, '2025-12-17', 'Absent', 'Prelim'),
(40, 45, 2, '2025-12-17', 'Absent', 'Prelim'),
(41, 28, 2, '2025-12-17', 'Present', 'Prelim'),
(42, 43, 2, '2025-12-17', 'Absent', 'Prelim'),
(43, 47, 2, '2025-12-17', 'Absent', 'Prelim'),
(44, 42, 2, '2025-12-17', 'Absent', 'Prelim'),
(45, 69, 2, '2025-12-17', 'Absent', 'Prelim'),
(46, 48, 2, '2025-12-17', 'Present', 'Prelim'),
(47, 17, 2, '2025-12-17', 'Present', 'Prelim'),
(48, 58, 2, '2025-12-17', 'Present', 'Prelim'),
(49, 49, 2, '2025-12-17', 'Absent', 'Prelim'),
(50, 18, 2, '2025-12-17', 'Present', 'Prelim'),
(51, 68, 2, '2025-12-17', 'Absent', 'Prelim'),
(52, 71, 2, '2025-12-17', 'Absent', 'Prelim'),
(53, 62, 2, '2025-12-17', 'Absent', 'Prelim'),
(54, 50, 2, '2025-12-17', 'Present', 'Prelim'),
(55, 46, 2, '2025-12-17', 'Absent', 'Prelim'),
(56, 65, 2, '2025-12-17', 'Absent', 'Prelim'),
(57, 70, 2, '2025-12-17', 'Present', 'Prelim'),
(58, 38, 2, '2025-12-17', 'Absent', 'Prelim'),
(59, 61, 2, '2025-12-17', 'Absent', 'Prelim'),
(60, 51, 2, '2025-12-17', 'Present', 'Prelim'),
(61, 52, 2, '2025-12-17', 'Present', 'Prelim'),
(62, 39, 2, '2025-12-17', 'Absent', 'Prelim'),
(63, 53, 2, '2025-12-17', 'Present', 'Prelim'),
(64, 63, 2, '2025-12-17', 'Absent', 'Prelim'),
(65, 73, 2, '2025-12-17', 'Absent', 'Prelim'),
(66, 67, 2, '2025-12-17', 'Absent', 'Prelim'),
(67, 54, 2, '2025-12-17', 'Present', 'Prelim'),
(68, 55, 2, '2025-12-17', 'Absent', 'Prelim'),
(69, 56, 2, '2025-12-17', 'Absent', 'Prelim'),
(70, 24, 2, '2025-12-17', 'Present', 'Prelim'),
(71, 40, 2, '2025-12-17', 'Present', 'Prelim'),
(72, 59, 2, '2025-12-17', 'Absent', 'Prelim'),
(73, 41, 2, '2025-12-17', 'Absent', 'Prelim'),
(74, 26, 2, '2025-12-17', 'Absent', 'Prelim'),
(75, 57, 2, '2025-12-17', 'Absent', 'Prelim'),
(76, 66, 2, '2025-12-17', 'Absent', 'Prelim'),
(77, 60, 2, '2025-12-17', 'Absent', 'Prelim'),
(78, 64, 2, '2025-12-17', 'Absent', 'Prelim'),
(79, 72, 2, '2025-12-17', 'Absent', 'Prelim'),
(80, 1, 1, '2025-12-18', 'Present', 'Prelim'),
(81, 2, 1, '2025-12-18', 'Present', 'Prelim'),
(82, 3, 1, '2025-12-18', 'Absent', 'Prelim'),
(83, 4, 1, '2025-12-18', 'Present', 'Prelim'),
(84, 5, 1, '2025-12-18', 'Present', 'Prelim'),
(85, 6, 1, '2025-12-18', 'Present', 'Prelim'),
(86, 7, 1, '2025-12-18', 'Absent', 'Prelim'),
(87, 9, 1, '2025-12-18', 'Absent', 'Prelim'),
(88, 10, 1, '2025-12-18', 'Present', 'Prelim'),
(89, 11, 1, '2025-12-18', 'Present', 'Prelim'),
(90, 12, 1, '2025-12-18', 'Present', 'Prelim'),
(91, 13, 1, '2025-12-18', 'Present', 'Prelim'),
(92, 14, 1, '2025-12-18', 'Absent', 'Prelim'),
(93, 15, 1, '2025-12-18', 'Present', 'Prelim'),
(94, 16, 1, '2025-12-18', 'Present', 'Prelim'),
(95, 19, 1, '2025-12-18', 'Present', 'Prelim'),
(96, 20, 1, '2025-12-18', 'Absent', 'Prelim'),
(97, 21, 1, '2025-12-18', 'Present', 'Prelim'),
(98, 22, 1, '2025-12-18', 'Present', 'Prelim'),
(99, 23, 1, '2025-12-18', 'Present', 'Prelim'),
(100, 25, 1, '2025-12-18', 'Present', 'Prelim'),
(101, 30, 1, '2025-12-18', 'Present', 'Prelim'),
(102, 33, 1, '2025-12-18', 'Absent', 'Prelim'),
(103, 35, 1, '2025-12-18', 'Absent', 'Prelim'),
(104, 36, 1, '2025-12-18', 'Absent', 'Prelim'),
(105, 37, 1, '2025-12-18', 'Absent', 'Prelim'),
(106, 99, 3, '2026-01-06', 'Present', 'Prelim'),
(107, 96, 3, '2026-01-06', 'Present', 'Prelim'),
(108, 108, 3, '2026-01-06', 'Absent', 'Prelim'),
(109, 79, 3, '2026-01-06', 'Present', 'Prelim'),
(110, 98, 3, '2026-01-06', 'Present', 'Prelim'),
(111, 80, 3, '2026-01-06', 'Absent', 'Prelim'),
(112, 91, 3, '2026-01-06', 'Present', 'Prelim'),
(113, 81, 3, '2026-01-06', 'Present', 'Prelim'),
(114, 105, 3, '2026-01-06', 'Absent', 'Prelim'),
(115, 74, 3, '2026-01-06', 'Present', 'Prelim'),
(116, 94, 3, '2026-01-06', 'Absent', 'Prelim'),
(117, 75, 3, '2026-01-06', 'Absent', 'Prelim'),
(118, 86, 3, '2026-01-06', 'Present', 'Prelim'),
(119, 76, 3, '2026-01-06', 'Present', 'Prelim'),
(120, 104, 3, '2026-01-06', 'Absent', 'Prelim'),
(121, 95, 3, '2026-01-06', 'Present', 'Prelim'),
(122, 82, 3, '2026-01-06', 'Absent', 'Prelim'),
(123, 97, 3, '2026-01-06', 'Present', 'Prelim'),
(124, 102, 3, '2026-01-06', 'Absent', 'Prelim'),
(125, 101, 3, '2026-01-06', 'Absent', 'Prelim'),
(126, 110, 3, '2026-01-06', 'Absent', 'Prelim'),
(127, 87, 3, '2026-01-06', 'Present', 'Prelim'),
(128, 77, 3, '2026-01-06', 'Present', 'Prelim'),
(129, 78, 3, '2026-01-06', 'Present', 'Prelim'),
(130, 88, 3, '2026-01-06', 'Present', 'Prelim'),
(131, 103, 3, '2026-01-06', 'Absent', 'Prelim'),
(132, 93, 3, '2026-01-06', 'Absent', 'Prelim'),
(133, 89, 3, '2026-01-06', 'Absent', 'Prelim'),
(134, 100, 3, '2026-01-06', 'Present', 'Prelim'),
(135, 31, 3, '2026-01-06', 'Present', 'Prelim'),
(136, 109, 3, '2026-01-06', 'Absent', 'Prelim'),
(137, 90, 3, '2026-01-06', 'Absent', 'Prelim'),
(138, 92, 3, '2026-01-06', 'Present', 'Prelim'),
(139, 83, 3, '2026-01-06', 'Present', 'Prelim'),
(140, 107, 3, '2026-01-06', 'Absent', 'Prelim'),
(141, 84, 3, '2026-01-06', 'Present', 'Prelim'),
(142, 106, 3, '2026-01-06', 'Absent', 'Prelim'),
(143, 85, 3, '2026-01-06', 'Present', 'Prelim'),
(144, 44, 2, '2026-01-07', 'Absent', 'Prelim'),
(145, 45, 2, '2026-01-07', 'Absent', 'Prelim'),
(146, 28, 2, '2026-01-07', 'Present', 'Prelim'),
(147, 43, 2, '2026-01-07', 'Present', 'Prelim'),
(148, 47, 2, '2026-01-07', 'Absent', 'Prelim'),
(149, 42, 2, '2026-01-07', 'Present', 'Prelim'),
(150, 69, 2, '2026-01-07', 'Absent', 'Prelim'),
(151, 48, 2, '2026-01-07', 'Present', 'Prelim'),
(152, 17, 2, '2026-01-07', 'Present', 'Prelim'),
(153, 58, 2, '2026-01-07', 'Present', 'Prelim'),
(154, 49, 2, '2026-01-07', 'Present', 'Prelim'),
(155, 18, 2, '2026-01-07', 'Present', 'Prelim'),
(156, 68, 2, '2026-01-07', 'Absent', 'Prelim'),
(157, 71, 2, '2026-01-07', 'Absent', 'Prelim'),
(158, 62, 2, '2026-01-07', 'Present', 'Prelim'),
(159, 50, 2, '2026-01-07', 'Present', 'Prelim'),
(160, 46, 2, '2026-01-07', 'Absent', 'Prelim'),
(161, 65, 2, '2026-01-07', 'Present', 'Prelim'),
(162, 70, 2, '2026-01-07', 'Present', 'Prelim'),
(163, 38, 2, '2026-01-07', 'Present', 'Prelim'),
(164, 61, 2, '2026-01-07', 'Present', 'Prelim'),
(165, 51, 2, '2026-01-07', 'Present', 'Prelim'),
(166, 52, 2, '2026-01-07', 'Present', 'Prelim'),
(167, 39, 2, '2026-01-07', 'Present', 'Prelim'),
(168, 53, 2, '2026-01-07', 'Present', 'Prelim'),
(169, 63, 2, '2026-01-07', 'Present', 'Prelim'),
(170, 73, 2, '2026-01-07', 'Present', 'Prelim'),
(171, 67, 2, '2026-01-07', 'Absent', 'Prelim'),
(172, 54, 2, '2026-01-07', 'Present', 'Prelim'),
(173, 55, 2, '2026-01-07', 'Present', 'Prelim'),
(174, 56, 2, '2026-01-07', 'Present', 'Prelim'),
(175, 24, 2, '2026-01-07', 'Present', 'Prelim'),
(176, 40, 2, '2026-01-07', 'Present', 'Prelim'),
(177, 59, 2, '2026-01-07', 'Present', 'Prelim'),
(178, 41, 2, '2026-01-07', 'Present', 'Prelim'),
(179, 26, 2, '2026-01-07', 'Absent', 'Prelim'),
(180, 57, 2, '2026-01-07', 'Present', 'Prelim'),
(181, 66, 2, '2026-01-07', 'Absent', 'Prelim'),
(182, 60, 2, '2026-01-07', 'Absent', 'Prelim'),
(183, 64, 2, '2026-01-07', 'Absent', 'Prelim'),
(184, 72, 2, '2026-01-07', 'Absent', 'Prelim'),
(185, 1, 1, '2026-01-08', 'Present', 'Prelim'),
(186, 2, 1, '2026-01-08', 'Present', 'Prelim'),
(187, 3, 1, '2026-01-08', 'Present', 'Prelim'),
(188, 4, 1, '2026-01-08', 'Present', 'Prelim'),
(189, 5, 1, '2026-01-08', 'Present', 'Prelim'),
(190, 6, 1, '2026-01-08', 'Present', 'Prelim'),
(191, 35, 1, '2026-01-08', 'Present', 'Prelim'),
(192, 33, 1, '2026-01-08', 'Present', 'Prelim'),
(193, 7, 1, '2026-01-08', 'Present', 'Prelim'),
(194, 9, 1, '2026-01-08', 'Absent', 'Prelim'),
(195, 10, 1, '2026-01-08', 'Present', 'Prelim'),
(196, 11, 1, '2026-01-08', 'Present', 'Prelim'),
(197, 12, 1, '2026-01-08', 'Present', 'Prelim'),
(198, 30, 1, '2026-01-08', 'Present', 'Prelim'),
(199, 36, 1, '2026-01-08', 'Present', 'Prelim'),
(200, 13, 1, '2026-01-08', 'Present', 'Prelim'),
(201, 14, 1, '2026-01-08', 'Present', 'Prelim'),
(202, 15, 1, '2026-01-08', 'Present', 'Prelim'),
(203, 16, 1, '2026-01-08', 'Present', 'Prelim'),
(204, 19, 1, '2026-01-08', 'Present', 'Prelim'),
(205, 20, 1, '2026-01-08', 'Present', 'Prelim'),
(206, 22, 1, '2026-01-08', 'Present', 'Prelim'),
(207, 23, 1, '2026-01-08', 'Present', 'Prelim'),
(208, 25, 1, '2026-01-08', 'Absent', 'Prelim'),
(209, 21, 1, '2026-01-08', 'Present', 'Prelim'),
(210, 37, 1, '2026-01-08', 'Absent', 'Prelim'),
(211, 99, 3, '2026-01-13', 'Present', 'Prelim'),
(212, 96, 3, '2026-01-13', 'Present', 'Prelim'),
(213, 108, 3, '2026-01-13', 'Present', 'Prelim'),
(214, 79, 3, '2026-01-13', 'Present', 'Prelim'),
(215, 98, 3, '2026-01-13', 'Present', 'Prelim'),
(216, 80, 3, '2026-01-13', 'Present', 'Prelim'),
(217, 91, 3, '2026-01-13', 'Present', 'Prelim'),
(218, 81, 3, '2026-01-13', 'Present', 'Prelim'),
(219, 105, 3, '2026-01-13', 'Present', 'Prelim'),
(220, 74, 3, '2026-01-13', 'Present', 'Prelim'),
(221, 94, 3, '2026-01-13', 'Present', 'Prelim'),
(222, 75, 3, '2026-01-13', 'Absent', 'Prelim'),
(223, 86, 3, '2026-01-13', 'Present', 'Prelim'),
(224, 76, 3, '2026-01-13', 'Absent', 'Prelim'),
(225, 104, 3, '2026-01-13', 'Present', 'Prelim'),
(226, 95, 3, '2026-01-13', 'Present', 'Prelim'),
(227, 82, 3, '2026-01-13', 'Present', 'Prelim'),
(228, 97, 3, '2026-01-13', 'Present', 'Prelim'),
(229, 102, 3, '2026-01-13', 'Present', 'Prelim'),
(230, 101, 3, '2026-01-13', 'Present', 'Prelim'),
(231, 110, 3, '2026-01-13', 'Present', 'Prelim'),
(232, 87, 3, '2026-01-13', 'Present', 'Prelim'),
(233, 77, 3, '2026-01-13', 'Present', 'Prelim'),
(234, 78, 3, '2026-01-13', 'Present', 'Prelim'),
(235, 88, 3, '2026-01-13', 'Present', 'Prelim'),
(236, 103, 3, '2026-01-13', 'Absent', 'Prelim'),
(237, 93, 3, '2026-01-13', 'Present', 'Prelim'),
(238, 89, 3, '2026-01-13', 'Absent', 'Prelim'),
(239, 100, 3, '2026-01-13', 'Present', 'Prelim'),
(240, 31, 3, '2026-01-13', 'Present', 'Prelim'),
(241, 109, 3, '2026-01-13', 'Present', 'Prelim'),
(242, 90, 3, '2026-01-13', 'Present', 'Prelim'),
(243, 92, 3, '2026-01-13', 'Present', 'Prelim'),
(244, 83, 3, '2026-01-13', 'Present', 'Prelim'),
(245, 107, 3, '2026-01-13', 'Present', 'Prelim'),
(246, 84, 3, '2026-01-13', 'Present', 'Prelim'),
(247, 106, 3, '2026-01-13', 'Present', 'Prelim'),
(248, 85, 3, '2026-01-13', 'Present', 'Prelim'),
(249, 44, 2, '2026-01-14', 'Present', 'Prelim'),
(250, 45, 2, '2026-01-14', 'Present', 'Prelim'),
(251, 28, 2, '2026-01-14', 'Present', 'Prelim'),
(252, 43, 2, '2026-01-14', 'Present', 'Prelim'),
(253, 47, 2, '2026-01-14', 'Absent', 'Prelim'),
(254, 42, 2, '2026-01-14', 'Present', 'Prelim'),
(255, 69, 2, '2026-01-14', 'Present', 'Prelim'),
(256, 48, 2, '2026-01-14', 'Present', 'Prelim'),
(257, 17, 2, '2026-01-14', 'Present', 'Prelim'),
(258, 58, 2, '2026-01-14', 'Present', 'Prelim'),
(259, 49, 2, '2026-01-14', 'Present', 'Prelim'),
(260, 18, 2, '2026-01-14', 'Present', 'Prelim'),
(261, 68, 2, '2026-01-14', 'Present', 'Prelim'),
(262, 71, 2, '2026-01-14', 'Present', 'Prelim'),
(263, 62, 2, '2026-01-14', 'Present', 'Prelim'),
(264, 50, 2, '2026-01-14', 'Present', 'Prelim'),
(265, 46, 2, '2026-01-14', 'Present', 'Prelim'),
(266, 65, 2, '2026-01-14', 'Present', 'Prelim'),
(267, 70, 2, '2026-01-14', 'Present', 'Prelim'),
(268, 38, 2, '2026-01-14', 'Present', 'Prelim'),
(269, 61, 2, '2026-01-14', 'Present', 'Prelim'),
(270, 51, 2, '2026-01-14', 'Present', 'Prelim'),
(271, 52, 2, '2026-01-14', 'Present', 'Prelim'),
(272, 39, 2, '2026-01-14', 'Present', 'Prelim'),
(273, 53, 2, '2026-01-14', 'Present', 'Prelim'),
(274, 63, 2, '2026-01-14', 'Present', 'Prelim'),
(275, 73, 2, '2026-01-14', 'Present', 'Prelim'),
(276, 67, 2, '2026-01-14', 'Present', 'Prelim'),
(277, 54, 2, '2026-01-14', 'Present', 'Prelim'),
(278, 55, 2, '2026-01-14', 'Present', 'Prelim'),
(279, 56, 2, '2026-01-14', 'Present', 'Prelim'),
(280, 24, 2, '2026-01-14', 'Present', 'Prelim'),
(281, 40, 2, '2026-01-14', 'Present', 'Prelim'),
(282, 59, 2, '2026-01-14', 'Present', 'Prelim'),
(283, 41, 2, '2026-01-14', 'Present', 'Prelim'),
(284, 26, 2, '2026-01-14', 'Present', 'Prelim'),
(285, 57, 2, '2026-01-14', 'Present', 'Prelim'),
(286, 66, 2, '2026-01-14', 'Present', 'Prelim'),
(287, 60, 2, '2026-01-14', 'Absent', 'Prelim'),
(288, 64, 2, '2026-01-14', 'Present', 'Prelim'),
(289, 72, 2, '2026-01-14', 'Present', 'Prelim'),
(290, 1, 1, '2026-01-15', 'Present', 'Prelim'),
(291, 2, 1, '2026-01-15', 'Present', 'Prelim'),
(292, 3, 1, '2026-01-15', 'Present', 'Prelim'),
(293, 4, 1, '2026-01-15', 'Present', 'Prelim'),
(294, 5, 1, '2026-01-15', 'Present', 'Prelim'),
(295, 6, 1, '2026-01-15', 'Present', 'Prelim'),
(296, 35, 1, '2026-01-15', 'Present', 'Prelim'),
(297, 33, 1, '2026-01-15', 'Present', 'Prelim'),
(298, 7, 1, '2026-01-15', 'Present', 'Prelim'),
(299, 9, 1, '2026-01-15', 'Present', 'Prelim'),
(300, 10, 1, '2026-01-15', 'Present', 'Prelim'),
(301, 11, 1, '2026-01-15', 'Present', 'Prelim'),
(302, 12, 1, '2026-01-15', 'Present', 'Prelim'),
(303, 30, 1, '2026-01-15', 'Present', 'Prelim'),
(304, 36, 1, '2026-01-15', 'Present', 'Prelim'),
(305, 13, 1, '2026-01-15', 'Present', 'Prelim'),
(306, 14, 1, '2026-01-15', 'Present', 'Prelim'),
(307, 15, 1, '2026-01-15', 'Present', 'Prelim'),
(308, 16, 1, '2026-01-15', 'Present', 'Prelim'),
(309, 19, 1, '2026-01-15', 'Present', 'Prelim'),
(310, 20, 1, '2026-01-15', 'Present', 'Prelim'),
(311, 22, 1, '2026-01-15', 'Present', 'Prelim'),
(312, 23, 1, '2026-01-15', 'Present', 'Prelim'),
(313, 25, 1, '2026-01-15', 'Present', 'Prelim'),
(314, 21, 1, '2026-01-15', 'Present', 'Prelim'),
(315, 37, 1, '2026-01-15', 'Present', 'Prelim'),
(316, 44, 2, '2026-01-21', 'Absent', 'Prelim'),
(317, 45, 2, '2026-01-21', 'Absent', 'Prelim'),
(318, 28, 2, '2026-01-21', 'Present', 'Prelim'),
(319, 43, 2, '2026-01-21', 'Present', 'Prelim'),
(320, 47, 2, '2026-01-21', 'Present', 'Prelim'),
(321, 42, 2, '2026-01-21', 'Present', 'Prelim'),
(322, 69, 2, '2026-01-21', 'Present', 'Prelim'),
(323, 48, 2, '2026-01-21', 'Present', 'Prelim'),
(324, 17, 2, '2026-01-21', 'Present', 'Prelim'),
(325, 58, 2, '2026-01-21', 'Present', 'Prelim'),
(326, 49, 2, '2026-01-21', 'Present', 'Prelim'),
(327, 18, 2, '2026-01-21', 'Present', 'Prelim'),
(328, 68, 2, '2026-01-21', 'Present', 'Prelim'),
(329, 71, 2, '2026-01-21', 'Present', 'Prelim'),
(330, 62, 2, '2026-01-21', 'Present', 'Prelim'),
(331, 50, 2, '2026-01-21', 'Present', 'Prelim'),
(332, 46, 2, '2026-01-21', 'Present', 'Prelim'),
(333, 65, 2, '2026-01-21', 'Present', 'Prelim'),
(334, 70, 2, '2026-01-21', 'Present', 'Prelim'),
(335, 38, 2, '2026-01-21', 'Present', 'Prelim'),
(336, 61, 2, '2026-01-21', 'Present', 'Prelim'),
(337, 51, 2, '2026-01-21', 'Present', 'Prelim'),
(338, 52, 2, '2026-01-21', 'Present', 'Prelim'),
(339, 39, 2, '2026-01-21', 'Present', 'Prelim'),
(340, 53, 2, '2026-01-21', 'Present', 'Prelim'),
(341, 63, 2, '2026-01-21', 'Present', 'Prelim'),
(342, 73, 2, '2026-01-21', 'Present', 'Prelim'),
(343, 67, 2, '2026-01-21', 'Present', 'Prelim'),
(344, 54, 2, '2026-01-21', 'Present', 'Prelim'),
(345, 55, 2, '2026-01-21', 'Present', 'Prelim'),
(346, 56, 2, '2026-01-21', 'Present', 'Prelim'),
(347, 24, 2, '2026-01-21', 'Absent', 'Prelim'),
(348, 40, 2, '2026-01-21', 'Present', 'Prelim'),
(349, 59, 2, '2026-01-21', 'Present', 'Prelim'),
(350, 41, 2, '2026-01-21', 'Present', 'Prelim'),
(351, 26, 2, '2026-01-21', 'Absent', 'Prelim'),
(352, 57, 2, '2026-01-21', 'Present', 'Prelim'),
(353, 66, 2, '2026-01-21', 'Present', 'Prelim'),
(354, 60, 2, '2026-01-21', 'Present', 'Prelim'),
(355, 64, 2, '2026-01-21', 'Present', 'Prelim'),
(356, 72, 2, '2026-01-21', 'Present', 'Prelim'),
(357, 1, 1, '2026-01-22', 'Present', 'Prelim'),
(358, 2, 1, '2026-01-22', 'Present', 'Prelim'),
(359, 3, 1, '2026-01-22', 'Present', 'Prelim'),
(360, 4, 1, '2026-01-22', 'Present', 'Prelim'),
(361, 5, 1, '2026-01-22', 'Present', 'Prelim'),
(362, 6, 1, '2026-01-22', 'Present', 'Prelim'),
(363, 35, 1, '2026-01-22', 'Absent', 'Prelim'),
(364, 33, 1, '2026-01-22', 'Present', 'Prelim'),
(365, 7, 1, '2026-01-22', 'Present', 'Prelim'),
(366, 9, 1, '2026-01-22', 'Present', 'Prelim'),
(367, 10, 1, '2026-01-22', 'Present', 'Prelim'),
(368, 11, 1, '2026-01-22', 'Present', 'Prelim'),
(369, 12, 1, '2026-01-22', 'Present', 'Prelim'),
(370, 30, 1, '2026-01-22', 'Present', 'Prelim'),
(371, 36, 1, '2026-01-22', 'Present', 'Prelim'),
(372, 13, 1, '2026-01-22', 'Present', 'Prelim'),
(373, 14, 1, '2026-01-22', 'Present', 'Prelim'),
(374, 15, 1, '2026-01-22', 'Present', 'Prelim'),
(375, 16, 1, '2026-01-22', 'Present', 'Prelim'),
(376, 19, 1, '2026-01-22', 'Present', 'Prelim'),
(377, 20, 1, '2026-01-22', 'Present', 'Prelim'),
(378, 22, 1, '2026-01-22', 'Present', 'Prelim'),
(379, 23, 1, '2026-01-22', 'Present', 'Prelim'),
(380, 25, 1, '2026-01-22', 'Present', 'Prelim'),
(381, 21, 1, '2026-01-22', 'Present', 'Prelim'),
(382, 37, 1, '2026-01-22', 'Present', 'Prelim'),
(383, 31, 3, '2026-01-27', 'Present', 'Prelim'),
(384, 74, 3, '2026-01-27', 'Present', 'Prelim'),
(385, 75, 3, '2026-01-27', 'Present', 'Prelim'),
(386, 76, 3, '2026-01-27', 'Present', 'Prelim'),
(387, 77, 3, '2026-01-27', 'Present', 'Prelim'),
(388, 78, 3, '2026-01-27', 'Present', 'Prelim'),
(389, 79, 3, '2026-01-27', 'Present', 'Prelim'),
(390, 80, 3, '2026-01-27', 'Present', 'Prelim'),
(391, 81, 3, '2026-01-27', 'Present', 'Prelim'),
(392, 82, 3, '2026-01-27', 'Present', 'Prelim'),
(393, 83, 3, '2026-01-27', 'Present', 'Prelim'),
(394, 84, 3, '2026-01-27', 'Present', 'Prelim'),
(395, 85, 3, '2026-01-27', 'Present', 'Prelim'),
(396, 86, 3, '2026-01-27', 'Present', 'Prelim'),
(397, 87, 3, '2026-01-27', 'Present', 'Prelim'),
(398, 88, 3, '2026-01-27', 'Present', 'Prelim'),
(399, 89, 3, '2026-01-27', 'Present', 'Prelim'),
(400, 90, 3, '2026-01-27', 'Present', 'Prelim'),
(401, 91, 3, '2026-01-27', 'Present', 'Prelim'),
(402, 92, 3, '2026-01-27', 'Present', 'Prelim'),
(403, 93, 3, '2026-01-27', 'Present', 'Prelim'),
(404, 94, 3, '2026-01-27', 'Present', 'Prelim'),
(405, 95, 3, '2026-01-27', 'Present', 'Prelim'),
(406, 96, 3, '2026-01-27', 'Present', 'Prelim'),
(407, 97, 3, '2026-01-27', 'Present', 'Prelim'),
(408, 98, 3, '2026-01-27', 'Present', 'Prelim'),
(409, 99, 3, '2026-01-27', 'Present', 'Prelim'),
(410, 100, 3, '2026-01-27', 'Present', 'Prelim'),
(411, 101, 3, '2026-01-27', 'Present', 'Prelim'),
(412, 102, 3, '2026-01-27', 'Present', 'Prelim'),
(413, 103, 3, '2026-01-27', 'Present', 'Prelim'),
(414, 104, 3, '2026-01-27', 'Present', 'Prelim'),
(415, 105, 3, '2026-01-27', 'Present', 'Prelim'),
(416, 106, 3, '2026-01-27', 'Present', 'Prelim'),
(417, 107, 3, '2026-01-27', 'Present', 'Prelim'),
(418, 108, 3, '2026-01-27', 'Present', 'Prelim'),
(419, 109, 3, '2026-01-27', 'Present', 'Prelim'),
(420, 110, 3, '2026-01-27', 'Present', 'Prelim'),
(421, 17, 2, '2026-01-28', 'Present', 'Prelim'),
(422, 18, 2, '2026-01-28', 'Present', 'Prelim'),
(423, 24, 2, '2026-01-28', 'Present', 'Prelim'),
(424, 26, 2, '2026-01-28', 'Present', 'Prelim'),
(425, 28, 2, '2026-01-28', 'Present', 'Prelim'),
(426, 38, 2, '2026-01-28', 'Present', 'Prelim'),
(427, 39, 2, '2026-01-28', 'Present', 'Prelim'),
(428, 40, 2, '2026-01-28', 'Present', 'Prelim'),
(429, 41, 2, '2026-01-28', 'Present', 'Prelim'),
(430, 42, 2, '2026-01-28', 'Present', 'Prelim'),
(431, 43, 2, '2026-01-28', 'Present', 'Prelim'),
(432, 44, 2, '2026-01-28', 'Present', 'Prelim'),
(433, 45, 2, '2026-01-28', 'Present', 'Prelim'),
(434, 46, 2, '2026-01-28', 'Present', 'Prelim'),
(435, 47, 2, '2026-01-28', 'Present', 'Prelim'),
(436, 48, 2, '2026-01-28', 'Present', 'Prelim'),
(437, 49, 2, '2026-01-28', 'Present', 'Prelim'),
(438, 50, 2, '2026-01-28', 'Present', 'Prelim'),
(439, 51, 2, '2026-01-28', 'Present', 'Prelim'),
(440, 52, 2, '2026-01-28', 'Present', 'Prelim'),
(441, 53, 2, '2026-01-28', 'Present', 'Prelim'),
(442, 54, 2, '2026-01-28', 'Present', 'Prelim'),
(443, 55, 2, '2026-01-28', 'Present', 'Prelim'),
(444, 56, 2, '2026-01-28', 'Present', 'Prelim'),
(445, 57, 2, '2026-01-28', 'Present', 'Prelim'),
(446, 58, 2, '2026-01-28', 'Present', 'Prelim'),
(447, 59, 2, '2026-01-28', 'Present', 'Prelim'),
(448, 60, 2, '2026-01-28', 'Present', 'Prelim'),
(449, 61, 2, '2026-01-28', 'Present', 'Prelim'),
(450, 62, 2, '2026-01-28', 'Absent', 'Prelim'),
(451, 63, 2, '2026-01-28', 'Present', 'Prelim'),
(452, 64, 2, '2026-01-28', 'Present', 'Prelim'),
(453, 65, 2, '2026-01-28', 'Present', 'Prelim'),
(454, 66, 2, '2026-01-28', 'Present', 'Prelim'),
(455, 67, 2, '2026-01-28', 'Present', 'Prelim'),
(456, 68, 2, '2026-01-28', 'Present', 'Prelim'),
(457, 69, 2, '2026-01-28', 'Absent', 'Prelim'),
(458, 70, 2, '2026-01-28', 'Present', 'Prelim'),
(459, 71, 2, '2026-01-28', 'Present', 'Prelim'),
(460, 72, 2, '2026-01-28', 'Present', 'Prelim'),
(461, 73, 2, '2026-01-28', 'Present', 'Prelim'),
(462, 1, 1, '2026-01-29', 'Present', 'Prelim'),
(463, 2, 1, '2026-01-29', 'Present', 'Prelim'),
(464, 3, 1, '2026-01-29', 'Present', 'Prelim'),
(465, 4, 1, '2026-01-29', 'Present', 'Prelim'),
(466, 5, 1, '2026-01-29', 'Present', 'Prelim'),
(467, 6, 1, '2026-01-29', 'Present', 'Prelim'),
(468, 7, 1, '2026-01-29', 'Present', 'Prelim'),
(469, 9, 1, '2026-01-29', 'Present', 'Prelim'),
(470, 10, 1, '2026-01-29', 'Present', 'Prelim'),
(471, 11, 1, '2026-01-29', 'Present', 'Prelim'),
(472, 12, 1, '2026-01-29', 'Present', 'Prelim'),
(473, 13, 1, '2026-01-29', 'Present', 'Prelim'),
(474, 14, 1, '2026-01-29', 'Present', 'Prelim'),
(475, 15, 1, '2026-01-29', 'Present', 'Prelim'),
(476, 16, 1, '2026-01-29', 'Present', 'Prelim'),
(477, 19, 1, '2026-01-29', 'Present', 'Prelim'),
(478, 20, 1, '2026-01-29', 'Present', 'Prelim'),
(479, 21, 1, '2026-01-29', 'Present', 'Prelim'),
(480, 22, 1, '2026-01-29', 'Present', 'Prelim'),
(481, 23, 1, '2026-01-29', 'Present', 'Prelim'),
(482, 25, 1, '2026-01-29', 'Present', 'Prelim'),
(483, 30, 1, '2026-01-29', 'Present', 'Prelim'),
(484, 33, 1, '2026-01-29', 'Present', 'Prelim'),
(485, 35, 1, '2026-01-29', 'Present', 'Prelim'),
(486, 36, 1, '2026-01-29', 'Present', 'Prelim'),
(487, 37, 1, '2026-01-29', 'Present', 'Prelim'),
(488, 99, 3, '2026-02-10', 'Present', 'Midterm'),
(489, 96, 3, '2026-02-10', 'Present', 'Midterm'),
(490, 108, 3, '2026-02-10', 'Absent', 'Midterm'),
(491, 79, 3, '2026-02-10', 'Present', 'Midterm'),
(492, 98, 3, '2026-02-10', 'Present', 'Midterm'),
(493, 80, 3, '2026-02-10', 'Present', 'Midterm'),
(494, 91, 3, '2026-02-10', 'Present', 'Midterm'),
(495, 81, 3, '2026-02-10', 'Present', 'Midterm'),
(496, 105, 3, '2026-02-10', 'Present', 'Midterm'),
(497, 74, 3, '2026-02-10', 'Present', 'Midterm'),
(498, 94, 3, '2026-02-10', 'Present', 'Midterm'),
(499, 75, 3, '2026-02-10', 'Present', 'Midterm'),
(500, 86, 3, '2026-02-10', 'Present', 'Midterm'),
(501, 76, 3, '2026-02-10', 'Present', 'Midterm'),
(502, 104, 3, '2026-02-10', 'Absent', 'Midterm'),
(503, 111, 3, '2026-02-10', 'Present', 'Midterm'),
(504, 95, 3, '2026-02-10', 'Absent', 'Midterm'),
(505, 82, 3, '2026-02-10', 'Present', 'Midterm'),
(506, 97, 3, '2026-02-10', 'Present', 'Midterm'),
(507, 102, 3, '2026-02-10', 'Present', 'Midterm'),
(508, 101, 3, '2026-02-10', 'Present', 'Midterm'),
(509, 110, 3, '2026-02-10', 'Present', 'Midterm'),
(510, 87, 3, '2026-02-10', 'Present', 'Midterm'),
(511, 77, 3, '2026-02-10', 'Present', 'Midterm'),
(512, 78, 3, '2026-02-10', 'Present', 'Midterm'),
(513, 88, 3, '2026-02-10', 'Present', 'Midterm'),
(514, 103, 3, '2026-02-10', 'Absent', 'Midterm'),
(515, 93, 3, '2026-02-10', 'Present', 'Midterm'),
(516, 89, 3, '2026-02-10', 'Absent', 'Midterm'),
(517, 100, 3, '2026-02-10', 'Absent', 'Midterm'),
(518, 31, 3, '2026-02-10', 'Present', 'Midterm'),
(519, 109, 3, '2026-02-10', 'Absent', 'Midterm'),
(520, 90, 3, '2026-02-10', 'Absent', 'Midterm'),
(521, 92, 3, '2026-02-10', 'Present', 'Midterm'),
(522, 83, 3, '2026-02-10', 'Absent', 'Midterm'),
(523, 107, 3, '2026-02-10', 'Present', 'Midterm'),
(524, 84, 3, '2026-02-10', 'Present', 'Midterm'),
(525, 106, 3, '2026-02-10', 'Present', 'Midterm'),
(526, 85, 3, '2026-02-10', 'Present', 'Midterm'),
(527, 1, 1, '2026-02-12', 'Present', 'Midterm'),
(528, 2, 1, '2026-02-12', 'Present', 'Midterm'),
(529, 3, 1, '2026-02-12', 'Present', 'Midterm'),
(530, 4, 1, '2026-02-12', 'Present', 'Midterm'),
(531, 5, 1, '2026-02-12', 'Present', 'Midterm'),
(532, 6, 1, '2026-02-12', 'Present', 'Midterm'),
(533, 7, 1, '2026-02-12', 'Present', 'Midterm'),
(534, 9, 1, '2026-02-12', 'Present', 'Midterm'),
(535, 10, 1, '2026-02-12', 'Present', 'Midterm'),
(536, 11, 1, '2026-02-12', 'Present', 'Midterm'),
(537, 12, 1, '2026-02-12', 'Present', 'Midterm'),
(538, 13, 1, '2026-02-12', 'Present', 'Midterm'),
(539, 14, 1, '2026-02-12', 'Present', 'Midterm'),
(540, 15, 1, '2026-02-12', 'Present', 'Midterm'),
(541, 16, 1, '2026-02-12', 'Present', 'Midterm'),
(542, 19, 1, '2026-02-12', 'Present', 'Midterm'),
(543, 20, 1, '2026-02-12', 'Present', 'Midterm'),
(544, 21, 1, '2026-02-12', 'Present', 'Midterm'),
(545, 22, 1, '2026-02-12', 'Present', 'Midterm'),
(546, 23, 1, '2026-02-12', 'Present', 'Midterm'),
(547, 25, 1, '2026-02-12', 'Present', 'Midterm'),
(548, 30, 1, '2026-02-12', 'Present', 'Midterm'),
(549, 33, 1, '2026-02-12', 'Present', 'Midterm'),
(550, 35, 1, '2026-02-12', 'Present', 'Midterm'),
(551, 36, 1, '2026-02-12', 'Present', 'Midterm'),
(552, 37, 1, '2026-02-12', 'Present', 'Midterm'),
(553, 1, 1, '2026-02-19', 'Present', 'Prelim'),
(554, 2, 1, '2026-02-19', 'Present', 'Prelim'),
(555, 3, 1, '2026-02-19', 'Present', 'Prelim'),
(556, 4, 1, '2026-02-19', 'Present', 'Prelim'),
(557, 5, 1, '2026-02-19', 'Present', 'Prelim'),
(558, 6, 1, '2026-02-19', 'Present', 'Prelim'),
(559, 7, 1, '2026-02-19', 'Present', 'Prelim'),
(560, 9, 1, '2026-02-19', 'Present', 'Prelim'),
(561, 10, 1, '2026-02-19', 'Present', 'Prelim'),
(562, 11, 1, '2026-02-19', 'Present', 'Prelim'),
(563, 12, 1, '2026-02-19', 'Present', 'Prelim'),
(564, 13, 1, '2026-02-19', 'Present', 'Prelim'),
(565, 14, 1, '2026-02-19', 'Present', 'Prelim'),
(566, 15, 1, '2026-02-19', 'Present', 'Prelim'),
(567, 16, 1, '2026-02-19', 'Present', 'Prelim'),
(568, 19, 1, '2026-02-19', 'Present', 'Prelim'),
(569, 20, 1, '2026-02-19', 'Present', 'Prelim'),
(570, 21, 1, '2026-02-19', 'Present', 'Prelim'),
(571, 22, 1, '2026-02-19', 'Present', 'Prelim'),
(572, 23, 1, '2026-02-19', 'Present', 'Prelim'),
(573, 25, 1, '2026-02-19', 'Present', 'Prelim'),
(574, 30, 1, '2026-02-19', 'Present', 'Prelim'),
(575, 33, 1, '2026-02-19', 'Present', 'Prelim'),
(576, 35, 1, '2026-02-19', 'Present', 'Prelim'),
(577, 36, 1, '2026-02-19', 'Present', 'Prelim'),
(578, 37, 1, '2026-02-19', 'Absent', 'Prelim'),
(579, 99, 3, '2026-02-24', 'Present', 'Midterm'),
(580, 96, 3, '2026-02-24', 'Present', 'Midterm'),
(581, 108, 3, '2026-02-24', 'Present', 'Midterm'),
(582, 79, 3, '2026-02-24', 'Present', 'Midterm'),
(583, 98, 3, '2026-02-24', 'Present', 'Midterm'),
(584, 80, 3, '2026-02-24', 'Present', 'Midterm'),
(585, 91, 3, '2026-02-24', 'Present', 'Midterm'),
(586, 81, 3, '2026-02-24', 'Present', 'Midterm'),
(587, 105, 3, '2026-02-24', 'Present', 'Midterm'),
(588, 74, 3, '2026-02-24', 'Present', 'Midterm'),
(589, 94, 3, '2026-02-24', 'Present', 'Midterm'),
(590, 75, 3, '2026-02-24', 'Present', 'Midterm'),
(591, 86, 3, '2026-02-24', 'Present', 'Midterm'),
(592, 76, 3, '2026-02-24', 'Present', 'Midterm'),
(593, 104, 3, '2026-02-24', 'Absent', 'Midterm'),
(594, 111, 3, '2026-02-24', 'Absent', 'Midterm'),
(595, 95, 3, '2026-02-24', 'Present', 'Midterm'),
(596, 82, 3, '2026-02-24', 'Present', 'Midterm'),
(597, 97, 3, '2026-02-24', 'Present', 'Midterm'),
(598, 102, 3, '2026-02-24', 'Present', 'Midterm'),
(599, 101, 3, '2026-02-24', 'Present', 'Midterm'),
(600, 110, 3, '2026-02-24', 'Present', 'Midterm'),
(601, 87, 3, '2026-02-24', 'Present', 'Midterm'),
(602, 77, 3, '2026-02-24', 'Present', 'Midterm'),
(603, 78, 3, '2026-02-24', 'Present', 'Midterm'),
(604, 88, 3, '2026-02-24', 'Present', 'Midterm'),
(605, 103, 3, '2026-02-24', 'Present', 'Midterm'),
(606, 93, 3, '2026-02-24', 'Present', 'Midterm'),
(607, 89, 3, '2026-02-24', 'Absent', 'Midterm'),
(608, 100, 3, '2026-02-24', 'Present', 'Midterm'),
(609, 31, 3, '2026-02-24', 'Present', 'Midterm'),
(610, 109, 3, '2026-02-24', 'Absent', 'Midterm'),
(611, 90, 3, '2026-02-24', 'Present', 'Midterm'),
(612, 92, 3, '2026-02-24', 'Present', 'Midterm'),
(613, 83, 3, '2026-02-24', 'Absent', 'Midterm'),
(614, 107, 3, '2026-02-24', 'Present', 'Midterm'),
(615, 84, 3, '2026-02-24', 'Present', 'Midterm'),
(616, 106, 3, '2026-02-24', 'Present', 'Midterm'),
(617, 85, 3, '2026-02-24', 'Present', 'Midterm'),
(618, 1, 1, '2026-02-26', 'Present', 'Midterm'),
(619, 2, 1, '2026-02-26', 'Present', 'Midterm'),
(620, 3, 1, '2026-02-26', 'Present', 'Midterm'),
(621, 4, 1, '2026-02-26', 'Present', 'Midterm'),
(622, 5, 1, '2026-02-26', 'Present', 'Midterm'),
(623, 6, 1, '2026-02-26', 'Present', 'Midterm'),
(624, 7, 1, '2026-02-26', 'Present', 'Midterm'),
(625, 9, 1, '2026-02-26', 'Present', 'Midterm'),
(626, 10, 1, '2026-02-26', 'Present', 'Midterm'),
(627, 11, 1, '2026-02-26', 'Present', 'Midterm'),
(628, 12, 1, '2026-02-26', 'Present', 'Midterm'),
(629, 13, 1, '2026-02-26', 'Present', 'Midterm'),
(630, 14, 1, '2026-02-26', 'Present', 'Midterm'),
(631, 15, 1, '2026-02-26', 'Present', 'Midterm'),
(632, 16, 1, '2026-02-26', 'Present', 'Midterm'),
(633, 19, 1, '2026-02-26', 'Present', 'Midterm'),
(634, 20, 1, '2026-02-26', 'Present', 'Midterm'),
(635, 21, 1, '2026-02-26', 'Present', 'Midterm'),
(636, 22, 1, '2026-02-26', 'Present', 'Midterm'),
(637, 23, 1, '2026-02-26', 'Present', 'Midterm'),
(638, 25, 1, '2026-02-26', 'Present', 'Midterm'),
(639, 30, 1, '2026-02-26', 'Present', 'Midterm'),
(640, 33, 1, '2026-02-26', 'Present', 'Midterm'),
(641, 35, 1, '2026-02-26', 'Present', 'Midterm'),
(642, 36, 1, '2026-02-26', 'Present', 'Midterm'),
(643, 37, 1, '2026-02-26', 'Absent', 'Midterm'),
(644, 99, 3, '2026-02-23', 'Present', 'Midterm'),
(645, 96, 3, '2026-02-23', 'Present', 'Midterm'),
(646, 108, 3, '2026-02-23', 'Present', 'Midterm'),
(647, 79, 3, '2026-02-23', 'Present', 'Midterm'),
(648, 98, 3, '2026-02-23', 'Present', 'Midterm'),
(649, 80, 3, '2026-02-23', 'Present', 'Midterm'),
(650, 91, 3, '2026-02-23', 'Present', 'Midterm'),
(651, 81, 3, '2026-02-23', 'Present', 'Midterm'),
(652, 105, 3, '2026-02-23', 'Present', 'Midterm'),
(653, 74, 3, '2026-02-23', 'Present', 'Midterm'),
(654, 94, 3, '2026-02-23', 'Present', 'Midterm'),
(655, 75, 3, '2026-02-23', 'Present', 'Midterm'),
(656, 86, 3, '2026-02-23', 'Present', 'Midterm'),
(657, 76, 3, '2026-02-23', 'Present', 'Midterm'),
(658, 104, 3, '2026-02-23', 'Present', 'Midterm'),
(659, 111, 3, '2026-02-23', 'Absent', 'Midterm'),
(660, 95, 3, '2026-02-23', 'Present', 'Midterm'),
(661, 82, 3, '2026-02-23', 'Present', 'Midterm'),
(662, 97, 3, '2026-02-23', 'Present', 'Midterm'),
(663, 102, 3, '2026-02-23', 'Present', 'Midterm'),
(664, 101, 3, '2026-02-23', 'Present', 'Midterm'),
(665, 110, 3, '2026-02-23', 'Present', 'Midterm'),
(666, 87, 3, '2026-02-23', 'Present', 'Midterm'),
(667, 77, 3, '2026-02-23', 'Present', 'Midterm'),
(668, 78, 3, '2026-02-23', 'Present', 'Midterm'),
(669, 88, 3, '2026-02-23', 'Present', 'Midterm'),
(670, 103, 3, '2026-02-23', 'Present', 'Midterm'),
(671, 93, 3, '2026-02-23', 'Present', 'Midterm'),
(672, 89, 3, '2026-02-23', 'Present', 'Midterm'),
(673, 100, 3, '2026-02-23', 'Present', 'Midterm'),
(674, 31, 3, '2026-02-23', 'Present', 'Midterm'),
(675, 109, 3, '2026-02-23', 'Present', 'Midterm'),
(676, 90, 3, '2026-02-23', 'Present', 'Midterm'),
(677, 92, 3, '2026-02-23', 'Present', 'Midterm'),
(678, 83, 3, '2026-02-23', 'Present', 'Midterm'),
(679, 107, 3, '2026-02-23', 'Present', 'Midterm'),
(680, 84, 3, '2026-02-23', 'Present', 'Midterm'),
(681, 106, 3, '2026-02-23', 'Present', 'Midterm'),
(682, 85, 3, '2026-02-23', 'Present', 'Midterm'),
(683, 1, 1, '2026-02-05', 'Absent', 'Midterm'),
(684, 2, 1, '2026-02-05', 'Absent', 'Midterm'),
(685, 3, 1, '2026-02-05', 'Absent', 'Midterm'),
(686, 4, 1, '2026-02-05', 'Present', 'Midterm'),
(687, 5, 1, '2026-02-05', 'Absent', 'Midterm'),
(688, 6, 1, '2026-02-05', 'Present', 'Midterm'),
(689, 7, 1, '2026-02-05', 'Absent', 'Midterm'),
(690, 9, 1, '2026-02-05', 'Absent', 'Midterm'),
(691, 10, 1, '2026-02-05', 'Present', 'Midterm'),
(692, 11, 1, '2026-02-05', 'Absent', 'Midterm'),
(693, 12, 1, '2026-02-05', 'Present', 'Midterm'),
(694, 13, 1, '2026-02-05', 'Present', 'Midterm'),
(695, 14, 1, '2026-02-05', 'Absent', 'Midterm'),
(696, 15, 1, '2026-02-05', 'Present', 'Midterm'),
(697, 16, 1, '2026-02-05', 'Present', 'Midterm'),
(698, 19, 1, '2026-02-05', 'Present', 'Midterm'),
(699, 20, 1, '2026-02-05', 'Present', 'Midterm'),
(700, 21, 1, '2026-02-05', 'Present', 'Midterm'),
(701, 22, 1, '2026-02-05', 'Absent', 'Midterm'),
(702, 23, 1, '2026-02-05', 'Present', 'Midterm'),
(703, 25, 1, '2026-02-05', 'Present', 'Midterm'),
(704, 30, 1, '2026-02-05', 'Absent', 'Midterm'),
(705, 33, 1, '2026-02-05', 'Present', 'Midterm'),
(706, 35, 1, '2026-02-05', 'Present', 'Midterm'),
(707, 36, 1, '2026-02-05', 'Absent', 'Midterm'),
(708, 37, 1, '2026-02-05', 'Absent', 'Midterm'),
(709, 31, 3, '2026-02-03', 'Present', 'Midterm'),
(710, 74, 3, '2026-02-03', 'Present', 'Midterm'),
(711, 75, 3, '2026-02-03', 'Present', 'Midterm'),
(712, 76, 3, '2026-02-03', 'Present', 'Midterm'),
(713, 77, 3, '2026-02-03', 'Present', 'Midterm'),
(714, 78, 3, '2026-02-03', 'Present', 'Midterm'),
(715, 79, 3, '2026-02-03', 'Present', 'Midterm'),
(716, 80, 3, '2026-02-03', 'Present', 'Midterm'),
(717, 81, 3, '2026-02-03', 'Present', 'Midterm'),
(718, 82, 3, '2026-02-03', 'Present', 'Midterm'),
(719, 83, 3, '2026-02-03', 'Present', 'Midterm'),
(720, 84, 3, '2026-02-03', 'Present', 'Midterm'),
(721, 85, 3, '2026-02-03', 'Present', 'Midterm'),
(722, 86, 3, '2026-02-03', 'Present', 'Midterm'),
(723, 87, 3, '2026-02-03', 'Present', 'Midterm'),
(724, 88, 3, '2026-02-03', 'Present', 'Midterm'),
(725, 89, 3, '2026-02-03', 'Present', 'Midterm'),
(726, 90, 3, '2026-02-03', 'Present', 'Midterm'),
(727, 91, 3, '2026-02-03', 'Present', 'Midterm'),
(728, 92, 3, '2026-02-03', 'Present', 'Midterm'),
(729, 93, 3, '2026-02-03', 'Present', 'Midterm'),
(730, 94, 3, '2026-02-03', 'Present', 'Midterm'),
(731, 95, 3, '2026-02-03', 'Present', 'Midterm'),
(732, 96, 3, '2026-02-03', 'Present', 'Midterm'),
(733, 97, 3, '2026-02-03', 'Present', 'Midterm'),
(734, 98, 3, '2026-02-03', 'Present', 'Midterm'),
(735, 99, 3, '2026-02-03', 'Present', 'Midterm'),
(736, 100, 3, '2026-02-03', 'Present', 'Midterm'),
(737, 101, 3, '2026-02-03', 'Present', 'Midterm'),
(738, 102, 3, '2026-02-03', 'Present', 'Midterm'),
(739, 103, 3, '2026-02-03', 'Present', 'Midterm'),
(740, 104, 3, '2026-02-03', 'Present', 'Midterm'),
(741, 105, 3, '2026-02-03', 'Present', 'Midterm'),
(742, 106, 3, '2026-02-03', 'Present', 'Midterm'),
(743, 107, 3, '2026-02-03', 'Present', 'Midterm'),
(744, 108, 3, '2026-02-03', 'Present', 'Midterm'),
(745, 109, 3, '2026-02-03', 'Present', 'Midterm'),
(746, 110, 3, '2026-02-03', 'Present', 'Midterm'),
(747, 111, 3, '2026-02-03', 'Present', 'Midterm'),
(748, 99, 3, '2026-03-03', 'Present', 'Midterm'),
(749, 96, 3, '2026-03-03', 'Present', 'Midterm'),
(750, 108, 3, '2026-03-03', 'Present', 'Midterm'),
(751, 79, 3, '2026-03-03', 'Present', 'Midterm'),
(752, 98, 3, '2026-03-03', 'Present', 'Midterm'),
(753, 80, 3, '2026-03-03', 'Absent', 'Midterm'),
(754, 91, 3, '2026-03-03', 'Present', 'Midterm'),
(755, 81, 3, '2026-03-03', 'Present', 'Midterm'),
(756, 105, 3, '2026-03-03', 'Present', 'Midterm'),
(757, 74, 3, '2026-03-03', 'Present', 'Midterm'),
(758, 94, 3, '2026-03-03', 'Present', 'Midterm'),
(759, 75, 3, '2026-03-03', 'Present', 'Midterm'),
(760, 86, 3, '2026-03-03', 'Absent', 'Midterm'),
(761, 76, 3, '2026-03-03', 'Present', 'Midterm'),
(762, 104, 3, '2026-03-03', 'Present', 'Midterm'),
(763, 111, 3, '2026-03-03', 'Absent', 'Midterm'),
(764, 95, 3, '2026-03-03', 'Present', 'Midterm'),
(765, 82, 3, '2026-03-03', 'Present', 'Midterm'),
(766, 97, 3, '2026-03-03', 'Present', 'Midterm'),
(767, 102, 3, '2026-03-03', 'Present', 'Midterm'),
(768, 101, 3, '2026-03-03', 'Present', 'Midterm'),
(769, 110, 3, '2026-03-03', 'Present', 'Midterm'),
(770, 87, 3, '2026-03-03', 'Present', 'Midterm'),
(771, 77, 3, '2026-03-03', 'Present', 'Midterm'),
(772, 78, 3, '2026-03-03', 'Absent', 'Midterm'),
(773, 88, 3, '2026-03-03', 'Present', 'Midterm'),
(774, 103, 3, '2026-03-03', 'Present', 'Midterm'),
(775, 93, 3, '2026-03-03', 'Present', 'Midterm'),
(776, 89, 3, '2026-03-03', 'Absent', 'Midterm'),
(777, 100, 3, '2026-03-03', 'Present', 'Midterm'),
(778, 31, 3, '2026-03-03', 'Present', 'Midterm'),
(779, 109, 3, '2026-03-03', 'Absent', 'Midterm'),
(780, 90, 3, '2026-03-03', 'Present', 'Midterm'),
(781, 92, 3, '2026-03-03', 'Present', 'Midterm'),
(782, 83, 3, '2026-03-03', 'Present', 'Midterm'),
(783, 107, 3, '2026-03-03', 'Present', 'Midterm'),
(784, 84, 3, '2026-03-03', 'Absent', 'Midterm'),
(785, 106, 3, '2026-03-03', 'Absent', 'Midterm'),
(786, 85, 3, '2026-03-03', 'Present', 'Midterm'),
(787, 99, 3, '2026-03-03', 'Present', 'Pre-Finals'),
(788, 96, 3, '2026-03-03', 'Present', 'Pre-Finals'),
(789, 108, 3, '2026-03-03', 'Present', 'Pre-Finals'),
(790, 79, 3, '2026-03-03', 'Present', 'Pre-Finals'),
(791, 98, 3, '2026-03-03', 'Present', 'Pre-Finals'),
(792, 80, 3, '2026-03-03', 'Present', 'Pre-Finals'),
(793, 91, 3, '2026-03-03', 'Present', 'Pre-Finals'),
(794, 81, 3, '2026-03-03', 'Present', 'Pre-Finals'),
(795, 105, 3, '2026-03-03', 'Present', 'Pre-Finals'),
(796, 74, 3, '2026-03-03', 'Present', 'Pre-Finals'),
(797, 94, 3, '2026-03-03', 'Present', 'Pre-Finals'),
(798, 75, 3, '2026-03-03', 'Present', 'Pre-Finals'),
(799, 86, 3, '2026-03-03', 'Present', 'Pre-Finals'),
(800, 76, 3, '2026-03-03', 'Present', 'Pre-Finals'),
(801, 104, 3, '2026-03-03', 'Present', 'Pre-Finals'),
(802, 111, 3, '2026-03-03', 'Absent', 'Pre-Finals'),
(803, 95, 3, '2026-03-03', 'Present', 'Pre-Finals'),
(804, 82, 3, '2026-03-03', 'Present', 'Pre-Finals'),
(805, 97, 3, '2026-03-03', 'Present', 'Pre-Finals'),
(806, 102, 3, '2026-03-03', 'Present', 'Pre-Finals'),
(807, 101, 3, '2026-03-03', 'Present', 'Pre-Finals'),
(808, 110, 3, '2026-03-03', 'Present', 'Pre-Finals'),
(809, 87, 3, '2026-03-03', 'Present', 'Pre-Finals'),
(810, 77, 3, '2026-03-03', 'Present', 'Pre-Finals'),
(811, 78, 3, '2026-03-03', 'Present', 'Pre-Finals'),
(812, 88, 3, '2026-03-03', 'Present', 'Pre-Finals'),
(813, 103, 3, '2026-03-03', 'Present', 'Pre-Finals'),
(814, 93, 3, '2026-03-03', 'Present', 'Pre-Finals'),
(815, 89, 3, '2026-03-03', 'Absent', 'Pre-Finals'),
(816, 100, 3, '2026-03-03', 'Present', 'Pre-Finals'),
(817, 31, 3, '2026-03-03', 'Present', 'Pre-Finals'),
(818, 109, 3, '2026-03-03', 'Absent', 'Pre-Finals'),
(819, 90, 3, '2026-03-03', 'Present', 'Pre-Finals'),
(820, 92, 3, '2026-03-03', 'Present', 'Pre-Finals'),
(821, 83, 3, '2026-03-03', 'Present', 'Pre-Finals'),
(822, 107, 3, '2026-03-03', 'Present', 'Pre-Finals'),
(823, 84, 3, '2026-03-03', 'Absent', 'Pre-Finals'),
(824, 106, 3, '2026-03-03', 'Absent', 'Pre-Finals'),
(825, 85, 3, '2026-03-03', 'Present', 'Pre-Finals'),
(826, 1, 1, '2026-02-05', 'Present', 'Midterm'),
(827, 2, 1, '2026-02-05', 'Present', 'Midterm'),
(828, 3, 1, '2026-02-05', 'Present', 'Midterm'),
(829, 4, 1, '2026-02-05', 'Present', 'Midterm'),
(830, 5, 1, '2026-02-05', 'Present', 'Midterm'),
(831, 6, 1, '2026-02-05', 'Present', 'Midterm'),
(832, 7, 1, '2026-02-05', 'Present', 'Midterm'),
(833, 9, 1, '2026-02-05', 'Present', 'Midterm'),
(834, 10, 1, '2026-02-05', 'Present', 'Midterm'),
(835, 11, 1, '2026-02-05', 'Present', 'Midterm'),
(836, 12, 1, '2026-02-05', 'Present', 'Midterm'),
(837, 13, 1, '2026-02-05', 'Present', 'Midterm'),
(838, 14, 1, '2026-02-05', 'Present', 'Midterm'),
(839, 15, 1, '2026-02-05', 'Present', 'Midterm'),
(840, 16, 1, '2026-02-05', 'Present', 'Midterm'),
(841, 19, 1, '2026-02-05', 'Present', 'Midterm'),
(842, 20, 1, '2026-02-05', 'Present', 'Midterm'),
(843, 21, 1, '2026-02-05', 'Present', 'Midterm'),
(844, 22, 1, '2026-02-05', 'Present', 'Midterm'),
(845, 23, 1, '2026-02-05', 'Present', 'Midterm'),
(846, 25, 1, '2026-02-05', 'Present', 'Midterm'),
(847, 30, 1, '2026-02-05', 'Present', 'Midterm'),
(848, 33, 1, '2026-02-05', 'Present', 'Midterm'),
(849, 35, 1, '2026-02-05', 'Present', 'Midterm'),
(850, 36, 1, '2026-02-05', 'Present', 'Midterm'),
(851, 37, 1, '2026-02-05', 'Present', 'Midterm'),
(852, 1, 1, '2026-03-05', 'Present', 'Pre-Finals'),
(853, 2, 1, '2026-03-05', 'Present', 'Pre-Finals'),
(854, 3, 1, '2026-03-05', 'Present', 'Pre-Finals'),
(855, 4, 1, '2026-03-05', 'Present', 'Pre-Finals'),
(856, 5, 1, '2026-03-05', 'Present', 'Pre-Finals'),
(857, 6, 1, '2026-03-05', 'Present', 'Pre-Finals'),
(858, 7, 1, '2026-03-05', 'Present', 'Pre-Finals'),
(859, 9, 1, '2026-03-05', 'Present', 'Pre-Finals'),
(860, 10, 1, '2026-03-05', 'Absent', 'Pre-Finals'),
(861, 11, 1, '2026-03-05', 'Absent', 'Pre-Finals'),
(862, 12, 1, '2026-03-05', 'Present', 'Pre-Finals'),
(863, 13, 1, '2026-03-05', 'Absent', 'Pre-Finals'),
(864, 14, 1, '2026-03-05', 'Present', 'Pre-Finals'),
(865, 15, 1, '2026-03-05', 'Present', 'Pre-Finals'),
(866, 16, 1, '2026-03-05', 'Present', 'Pre-Finals'),
(867, 19, 1, '2026-03-05', 'Present', 'Pre-Finals'),
(868, 20, 1, '2026-03-05', 'Present', 'Pre-Finals'),
(869, 21, 1, '2026-03-05', 'Present', 'Pre-Finals'),
(870, 22, 1, '2026-03-05', 'Present', 'Pre-Finals'),
(871, 23, 1, '2026-03-05', 'Present', 'Pre-Finals'),
(872, 25, 1, '2026-03-05', 'Present', 'Pre-Finals'),
(873, 30, 1, '2026-03-05', 'Present', 'Pre-Finals'),
(874, 33, 1, '2026-03-05', 'Present', 'Pre-Finals'),
(875, 35, 1, '2026-03-05', 'Present', 'Pre-Finals'),
(876, 36, 1, '2026-03-05', 'Present', 'Pre-Finals'),
(877, 37, 1, '2026-03-05', 'Absent', 'Pre-Finals'),
(878, 99, 3, '2026-03-10', 'Present', 'Pre-Finals'),
(879, 96, 3, '2026-03-10', 'Present', 'Pre-Finals'),
(880, 108, 3, '2026-03-10', 'Absent', 'Pre-Finals'),
(881, 79, 3, '2026-03-10', 'Present', 'Pre-Finals'),
(882, 98, 3, '2026-03-10', 'Present', 'Pre-Finals'),
(883, 80, 3, '2026-03-10', 'Present', 'Pre-Finals'),
(884, 91, 3, '2026-03-10', 'Present', 'Pre-Finals'),
(885, 81, 3, '2026-03-10', 'Present', 'Pre-Finals'),
(886, 105, 3, '2026-03-10', 'Present', 'Pre-Finals'),
(887, 74, 3, '2026-03-10', 'Present', 'Pre-Finals'),
(888, 94, 3, '2026-03-10', 'Present', 'Pre-Finals'),
(889, 75, 3, '2026-03-10', 'Present', 'Pre-Finals'),
(890, 86, 3, '2026-03-10', 'Present', 'Pre-Finals'),
(891, 76, 3, '2026-03-10', 'Present', 'Pre-Finals'),
(892, 104, 3, '2026-03-10', 'Present', 'Pre-Finals'),
(893, 111, 3, '2026-03-10', 'Absent', 'Pre-Finals'),
(894, 95, 3, '2026-03-10', 'Present', 'Pre-Finals'),
(895, 82, 3, '2026-03-10', 'Present', 'Pre-Finals'),
(896, 97, 3, '2026-03-10', 'Present', 'Pre-Finals'),
(897, 102, 3, '2026-03-10', 'Present', 'Pre-Finals'),
(898, 101, 3, '2026-03-10', 'Present', 'Pre-Finals'),
(899, 110, 3, '2026-03-10', 'Present', 'Pre-Finals'),
(900, 87, 3, '2026-03-10', 'Present', 'Pre-Finals'),
(901, 77, 3, '2026-03-10', 'Present', 'Pre-Finals'),
(902, 78, 3, '2026-03-10', 'Present', 'Pre-Finals'),
(903, 88, 3, '2026-03-10', 'Present', 'Pre-Finals'),
(904, 103, 3, '2026-03-10', 'Present', 'Pre-Finals'),
(905, 93, 3, '2026-03-10', 'Absent', 'Pre-Finals'),
(906, 89, 3, '2026-03-10', 'Absent', 'Pre-Finals'),
(907, 100, 3, '2026-03-10', 'Present', 'Pre-Finals'),
(908, 31, 3, '2026-03-10', 'Present', 'Pre-Finals'),
(909, 109, 3, '2026-03-10', 'Absent', 'Pre-Finals'),
(910, 90, 3, '2026-03-10', 'Present', 'Pre-Finals'),
(911, 92, 3, '2026-03-10', 'Present', 'Pre-Finals'),
(912, 83, 3, '2026-03-10', 'Present', 'Pre-Finals'),
(913, 107, 3, '2026-03-10', 'Present', 'Pre-Finals'),
(914, 84, 3, '2026-03-10', 'Present', 'Pre-Finals'),
(915, 106, 3, '2026-03-10', 'Present', 'Pre-Finals'),
(916, 85, 3, '2026-03-10', 'Present', 'Pre-Finals'),
(917, 1, 1, '2026-03-12', 'Present', 'Pre-Finals'),
(918, 2, 1, '2026-03-12', 'Present', 'Pre-Finals'),
(919, 3, 1, '2026-03-12', 'Present', 'Pre-Finals'),
(920, 4, 1, '2026-03-12', 'Present', 'Pre-Finals'),
(921, 5, 1, '2026-03-12', 'Present', 'Pre-Finals'),
(922, 6, 1, '2026-03-12', 'Present', 'Pre-Finals'),
(923, 7, 1, '2026-03-12', 'Present', 'Pre-Finals'),
(924, 9, 1, '2026-03-12', 'Present', 'Pre-Finals'),
(925, 10, 1, '2026-03-12', 'Absent', 'Pre-Finals'),
(926, 11, 1, '2026-03-12', 'Present', 'Pre-Finals'),
(927, 12, 1, '2026-03-12', 'Present', 'Pre-Finals'),
(928, 13, 1, '2026-03-12', 'Present', 'Pre-Finals'),
(929, 14, 1, '2026-03-12', 'Present', 'Pre-Finals'),
(930, 15, 1, '2026-03-12', 'Present', 'Pre-Finals'),
(931, 16, 1, '2026-03-12', 'Present', 'Pre-Finals'),
(932, 19, 1, '2026-03-12', 'Present', 'Pre-Finals'),
(933, 20, 1, '2026-03-12', 'Present', 'Pre-Finals'),
(934, 21, 1, '2026-03-12', 'Present', 'Pre-Finals'),
(935, 22, 1, '2026-03-12', 'Present', 'Pre-Finals'),
(936, 23, 1, '2026-03-12', 'Present', 'Pre-Finals'),
(937, 25, 1, '2026-03-12', 'Present', 'Pre-Finals'),
(938, 30, 1, '2026-03-12', 'Present', 'Pre-Finals'),
(939, 33, 1, '2026-03-12', 'Present', 'Pre-Finals'),
(940, 35, 1, '2026-03-12', 'Present', 'Pre-Finals'),
(941, 36, 1, '2026-03-12', 'Present', 'Pre-Finals'),
(942, 37, 1, '2026-03-12', 'Absent', 'Pre-Finals'),
(943, 99, 3, '2026-03-17', 'Present', 'Pre-Finals'),
(944, 96, 3, '2026-03-17', 'Present', 'Pre-Finals'),
(945, 108, 3, '2026-03-17', 'Present', 'Pre-Finals'),
(946, 79, 3, '2026-03-17', 'Present', 'Pre-Finals'),
(947, 98, 3, '2026-03-17', 'Present', 'Pre-Finals'),
(948, 80, 3, '2026-03-17', 'Present', 'Pre-Finals'),
(949, 91, 3, '2026-03-17', 'Present', 'Pre-Finals'),
(950, 81, 3, '2026-03-17', 'Present', 'Pre-Finals'),
(951, 105, 3, '2026-03-17', 'Present', 'Pre-Finals'),
(952, 74, 3, '2026-03-17', 'Present', 'Pre-Finals'),
(953, 94, 3, '2026-03-17', 'Present', 'Pre-Finals'),
(954, 75, 3, '2026-03-17', 'Present', 'Pre-Finals'),
(955, 86, 3, '2026-03-17', 'Present', 'Pre-Finals'),
(956, 76, 3, '2026-03-17', 'Present', 'Pre-Finals'),
(957, 104, 3, '2026-03-17', 'Present', 'Pre-Finals'),
(958, 111, 3, '2026-03-17', 'Absent', 'Pre-Finals'),
(959, 95, 3, '2026-03-17', 'Present', 'Pre-Finals'),
(960, 82, 3, '2026-03-17', 'Present', 'Pre-Finals'),
(961, 97, 3, '2026-03-17', 'Present', 'Pre-Finals'),
(962, 102, 3, '2026-03-17', 'Present', 'Pre-Finals'),
(963, 101, 3, '2026-03-17', 'Present', 'Pre-Finals'),
(964, 110, 3, '2026-03-17', 'Absent', 'Pre-Finals'),
(965, 87, 3, '2026-03-17', 'Present', 'Pre-Finals'),
(966, 77, 3, '2026-03-17', 'Present', 'Pre-Finals'),
(967, 78, 3, '2026-03-17', 'Present', 'Pre-Finals'),
(968, 88, 3, '2026-03-17', 'Present', 'Pre-Finals'),
(969, 103, 3, '2026-03-17', 'Present', 'Pre-Finals'),
(970, 93, 3, '2026-03-17', 'Present', 'Pre-Finals'),
(971, 89, 3, '2026-03-17', 'Absent', 'Pre-Finals'),
(972, 100, 3, '2026-03-17', 'Present', 'Pre-Finals'),
(973, 31, 3, '2026-03-17', 'Present', 'Pre-Finals'),
(974, 109, 3, '2026-03-17', 'Absent', 'Pre-Finals'),
(975, 90, 3, '2026-03-17', 'Present', 'Pre-Finals'),
(976, 92, 3, '2026-03-17', 'Present', 'Pre-Finals'),
(977, 83, 3, '2026-03-17', 'Present', 'Pre-Finals'),
(978, 107, 3, '2026-03-17', 'Present', 'Pre-Finals'),
(979, 84, 3, '2026-03-17', 'Present', 'Pre-Finals'),
(980, 106, 3, '2026-03-17', 'Present', 'Pre-Finals'),
(981, 85, 3, '2026-03-17', 'Present', 'Pre-Finals'),
(1060, 1, 1, '2026-03-19', 'Present', 'Pre-Finals'),
(1061, 2, 1, '2026-03-19', 'Present', 'Pre-Finals'),
(1062, 3, 1, '2026-03-19', 'Present', 'Pre-Finals'),
(1063, 4, 1, '2026-03-19', 'Present', 'Pre-Finals'),
(1064, 5, 1, '2026-03-19', 'Present', 'Pre-Finals'),
(1065, 6, 1, '2026-03-19', 'Present', 'Pre-Finals'),
(1066, 7, 1, '2026-03-19', 'Present', 'Pre-Finals'),
(1067, 9, 1, '2026-03-19', 'Present', 'Pre-Finals'),
(1068, 10, 1, '2026-03-19', 'Present', 'Pre-Finals'),
(1069, 11, 1, '2026-03-19', 'Present', 'Pre-Finals'),
(1070, 12, 1, '2026-03-19', 'Present', 'Pre-Finals'),
(1071, 13, 1, '2026-03-19', 'Present', 'Pre-Finals'),
(1072, 14, 1, '2026-03-19', 'Present', 'Pre-Finals'),
(1073, 15, 1, '2026-03-19', 'Absent', 'Pre-Finals'),
(1074, 16, 1, '2026-03-19', 'Present', 'Pre-Finals'),
(1075, 19, 1, '2026-03-19', 'Present', 'Pre-Finals'),
(1076, 20, 1, '2026-03-19', 'Present', 'Pre-Finals'),
(1077, 21, 1, '2026-03-19', 'Present', 'Pre-Finals'),
(1078, 22, 1, '2026-03-19', 'Present', 'Pre-Finals'),
(1079, 23, 1, '2026-03-19', 'Present', 'Pre-Finals'),
(1080, 25, 1, '2026-03-19', 'Present', 'Pre-Finals'),
(1081, 30, 1, '2026-03-19', 'Present', 'Pre-Finals'),
(1082, 33, 1, '2026-03-19', 'Present', 'Pre-Finals'),
(1083, 35, 1, '2026-03-19', 'Present', 'Pre-Finals'),
(1084, 36, 1, '2026-03-19', 'Present', 'Pre-Finals'),
(1085, 37, 1, '2026-03-19', 'Absent', 'Pre-Finals');

-- --------------------------------------------------------

--
-- Table structure for table `class_record`
--

CREATE TABLE `class_record` (
  `rec_id` int(20) NOT NULL,
  `st_id` int(11) NOT NULL,
  `sectionID` int(11) NOT NULL,
  `sub_id` int(11) NOT NULL,
  `par_id` int(11) DEFAULT NULL,
  `written_id` int(11) DEFAULT NULL,
  `perf_id` int(11) DEFAULT NULL,
  `exam_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `class_record`
--

INSERT INTO `class_record` (`rec_id`, `st_id`, `sectionID`, `sub_id`, `par_id`, `written_id`, `perf_id`, `exam_id`) VALUES
(165, 99, 3, 1, 0, 0, 0, 0),
(166, 96, 3, 1, 0, 0, 0, 0),
(167, 108, 3, 1, 0, 0, 0, 0),
(168, 79, 3, 1, 0, 0, 0, 0),
(169, 98, 3, 1, 0, 0, 0, 0),
(170, 80, 3, 1, 0, 0, 0, 0),
(171, 91, 3, 1, 0, 0, 0, 0),
(172, 81, 3, 1, 0, 0, 0, 0),
(173, 105, 3, 1, 0, 0, 0, 0),
(174, 74, 3, 1, 0, 0, 0, 0),
(175, 94, 3, 1, 0, 0, 0, 0),
(176, 75, 3, 1, 0, 0, 0, 0),
(177, 86, 3, 1, 0, 0, 0, 0),
(178, 76, 3, 1, 0, 0, 0, 0),
(179, 104, 3, 1, 0, 0, 0, 0),
(180, 111, 3, 1, 0, 0, 0, 0),
(181, 95, 3, 1, 0, 0, 0, 0),
(182, 82, 3, 1, 0, 0, 0, 0),
(183, 97, 3, 1, 0, 0, 0, 0),
(184, 102, 3, 1, 0, 0, 0, 0),
(185, 101, 3, 1, 0, 0, 0, 0),
(186, 110, 3, 1, 0, 0, 0, 0),
(187, 87, 3, 1, 0, 0, 0, 0),
(188, 77, 3, 1, 0, 0, 0, 0),
(189, 78, 3, 1, 0, 0, 0, 0),
(190, 88, 3, 1, 0, 0, 0, 0),
(191, 103, 3, 1, 0, 0, 0, 0),
(192, 93, 3, 1, 0, 0, 0, 0),
(193, 89, 3, 1, 0, 0, 0, 0),
(194, 100, 3, 1, 0, 0, 0, 0),
(195, 31, 3, 1, 0, 0, 0, 0),
(196, 109, 3, 1, 0, 0, 0, 0),
(197, 90, 3, 1, 0, 0, 0, 0),
(198, 92, 3, 1, 0, 0, 0, 0),
(199, 83, 3, 1, 0, 0, 0, 0),
(200, 107, 3, 1, 0, 0, 0, 0),
(201, 84, 3, 1, 0, 0, 0, 0),
(202, 106, 3, 1, 0, 0, 0, 0),
(203, 85, 3, 1, 0, 0, 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `class_record_exam`
--

CREATE TABLE `class_record_exam` (
  `rec_id` int(11) NOT NULL,
  `exam_id` int(11) NOT NULL,
  `term` enum('Prelim','Midterm','Prefinal','Final') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `class_record_participation`
--

CREATE TABLE `class_record_participation` (
  `rec_id` int(11) NOT NULL,
  `par_id` int(11) NOT NULL,
  `term` enum('Prelim','Midterm','Prefinal','Final') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `class_record_performance`
--

CREATE TABLE `class_record_performance` (
  `rec_id` int(11) NOT NULL,
  `perf_id` int(11) NOT NULL,
  `term` enum('Prelim','Midterm','Prefinal','Final') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `class_record_performance`
--

INSERT INTO `class_record_performance` (`rec_id`, `perf_id`, `term`) VALUES
(165, 7, 'Prefinal'),
(166, 8, 'Prefinal'),
(167, 9, 'Prefinal'),
(168, 10, 'Prefinal'),
(169, 11, 'Prefinal'),
(170, 12, 'Prefinal'),
(171, 13, 'Prefinal'),
(172, 14, 'Prefinal'),
(173, 15, 'Prefinal'),
(174, 16, 'Prefinal'),
(175, 17, 'Prefinal'),
(176, 18, 'Prefinal'),
(177, 19, 'Prefinal'),
(178, 20, 'Prefinal'),
(179, 21, 'Prefinal'),
(180, 22, 'Prefinal'),
(181, 23, 'Prefinal'),
(182, 24, 'Prefinal'),
(183, 25, 'Prefinal'),
(184, 26, 'Prefinal'),
(185, 27, 'Prefinal'),
(186, 28, 'Prefinal'),
(187, 29, 'Prefinal'),
(188, 30, 'Prefinal'),
(189, 31, 'Prefinal'),
(190, 32, 'Prefinal'),
(191, 33, 'Prefinal'),
(192, 34, 'Prefinal'),
(193, 35, 'Prefinal'),
(194, 36, 'Prefinal'),
(195, 37, 'Prefinal'),
(196, 38, 'Prefinal'),
(197, 39, 'Prefinal'),
(198, 40, 'Prefinal'),
(199, 41, 'Prefinal'),
(200, 42, 'Prefinal'),
(201, 43, 'Prefinal'),
(202, 44, 'Prefinal'),
(203, 45, 'Prefinal');

-- --------------------------------------------------------

--
-- Table structure for table `class_record_written`
--

CREATE TABLE `class_record_written` (
  `rec_id` int(11) NOT NULL,
  `written_id` int(11) NOT NULL,
  `term` enum('Prelim','Midterm','Prefinal','Final') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `course`
--

CREATE TABLE `course` (
  `course_id` int(11) NOT NULL,
  `course_name` varchar(100) NOT NULL,
  `course_acronym` varchar(10) NOT NULL,
  `dept_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `course`
--

INSERT INTO `course` (`course_id`, `course_name`, `course_acronym`, `dept_id`) VALUES
(1, 'Bachelor of Science in Information Technology', 'BSIT', 1);

-- --------------------------------------------------------

--
-- Table structure for table `department`
--

CREATE TABLE `department` (
  `dept_id` int(11) NOT NULL,
  `dept_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `department`
--

INSERT INTO `department` (`dept_id`, `dept_name`) VALUES
(1, 'Computer Studies Department');

-- --------------------------------------------------------

--
-- Table structure for table `exam`
--

CREATE TABLE `exam` (
  `exam_id` int(11) NOT NULL,
  `score` int(11) DEFAULT 0,
  `term` varchar(50) DEFAULT NULL,
  `date_created` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `exam_settings`
--

CREATE TABLE `exam_settings` (
  `id` int(11) NOT NULL,
  `term` varchar(20) NOT NULL,
  `score_max` int(11) NOT NULL DEFAULT 100
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `exam_settings`
--

INSERT INTO `exam_settings` (`id`, `term`, `score_max`) VALUES
(1, 'Prelim', 100),
(2, 'Midterm', 100),
(3, 'Prefinal', 100),
(4, 'Final', 100);

-- --------------------------------------------------------

--
-- Table structure for table `instructor`
--

CREATE TABLE `instructor` (
  `inst_id` int(11) NOT NULL,
  `inst_name` varchar(100) NOT NULL,
  `dept_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `instructor`
--

INSERT INTO `instructor` (`inst_id`, `inst_name`, `dept_id`) VALUES
(6, 'Julius Frederick C. Vendivil', 1);

-- --------------------------------------------------------

--
-- Table structure for table `last_sync`
--

CREATE TABLE `last_sync` (
  `sync_id` int(11) NOT NULL,
  `last_updated` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `last_sync`
--

INSERT INTO `last_sync` (`sync_id`, `last_updated`) VALUES
(1, '2026-03-19 12:15:07');

-- --------------------------------------------------------

--
-- Table structure for table `participation`
--

CREATE TABLE `participation` (
  `par_id` int(11) NOT NULL,
  `par_one` int(11) NOT NULL DEFAULT 0,
  `par_two` int(11) NOT NULL DEFAULT 0,
  `par_three` int(11) NOT NULL DEFAULT 0,
  `par_four` int(11) NOT NULL DEFAULT 0,
  `par_five` int(11) NOT NULL DEFAULT 0,
  `term` varchar(20) NOT NULL,
  `date_created` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `performance`
--

CREATE TABLE `performance` (
  `perf_id` int(20) NOT NULL,
  `perf_one` int(11) NOT NULL,
  `perf_two` int(11) NOT NULL,
  `perf_three` int(11) NOT NULL,
  `perf_four` int(11) NOT NULL,
  `perf_five` int(11) NOT NULL,
  `term` varchar(20) NOT NULL,
  `date_created` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `performance`
--

INSERT INTO `performance` (`perf_id`, `perf_one`, `perf_two`, `perf_three`, `perf_four`, `perf_five`, `term`, `date_created`) VALUES
(7, 50, 0, 0, 0, 0, 'Prefinal', ''),
(8, 50, 0, 0, 0, 0, 'Prefinal', ''),
(9, 0, 0, 0, 0, 0, 'Prefinal', ''),
(10, 50, 0, 0, 0, 0, 'Prefinal', ''),
(11, 50, 0, 0, 0, 0, 'Prefinal', ''),
(12, 0, 0, 0, 0, 0, 'Prefinal', ''),
(13, 50, 0, 0, 0, 0, 'Prefinal', ''),
(14, 50, 0, 0, 0, 0, 'Prefinal', ''),
(15, 0, 0, 0, 0, 0, 'Prefinal', ''),
(16, 50, 0, 0, 0, 0, 'Prefinal', ''),
(17, 50, 0, 0, 0, 0, 'Prefinal', ''),
(18, 40, 0, 0, 0, 0, 'Prefinal', ''),
(19, 50, 0, 0, 0, 0, 'Prefinal', ''),
(20, 50, 0, 0, 0, 0, 'Prefinal', ''),
(21, 0, 0, 0, 0, 0, 'Prefinal', ''),
(22, 0, 0, 0, 0, 0, 'Prefinal', ''),
(23, 50, 0, 0, 0, 0, 'Prefinal', ''),
(24, 50, 0, 0, 0, 0, 'Prefinal', ''),
(25, 50, 0, 0, 0, 0, 'Prefinal', ''),
(26, 50, 0, 0, 0, 0, 'Prefinal', ''),
(27, 50, 0, 0, 0, 0, 'Prefinal', ''),
(28, 0, 0, 0, 0, 0, 'Prefinal', ''),
(29, 50, 0, 0, 0, 0, 'Prefinal', ''),
(30, 50, 0, 0, 0, 0, 'Prefinal', ''),
(31, 50, 0, 0, 0, 0, 'Prefinal', ''),
(32, 50, 0, 0, 0, 0, 'Prefinal', ''),
(33, 50, 0, 0, 0, 0, 'Prefinal', ''),
(34, 40, 0, 0, 0, 0, 'Prefinal', ''),
(35, 0, 0, 0, 0, 0, 'Prefinal', ''),
(36, 50, 0, 0, 0, 0, 'Prefinal', ''),
(37, 50, 0, 0, 0, 0, 'Prefinal', ''),
(38, 0, 0, 0, 0, 0, 'Prefinal', ''),
(39, 50, 0, 0, 0, 0, 'Prefinal', ''),
(40, 50, 0, 0, 0, 0, 'Prefinal', ''),
(41, 50, 0, 0, 0, 0, 'Prefinal', ''),
(42, 0, 0, 0, 0, 0, 'Prefinal', ''),
(43, 0, 0, 0, 0, 0, 'Prefinal', ''),
(44, 0, 0, 0, 0, 0, 'Prefinal', ''),
(45, 50, 0, 0, 0, 0, 'Prefinal', '');

-- --------------------------------------------------------

--
-- Table structure for table `score_settings`
--

CREATE TABLE `score_settings` (
  `id` int(11) NOT NULL,
  `term` varchar(20) NOT NULL,
  `component` varchar(20) NOT NULL,
  `one_max` int(11) NOT NULL DEFAULT 100,
  `two_max` int(11) NOT NULL DEFAULT 100,
  `three_max` int(11) NOT NULL DEFAULT 100,
  `four_max` int(11) NOT NULL DEFAULT 100,
  `five_max` int(11) NOT NULL DEFAULT 100
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `score_settings`
--

INSERT INTO `score_settings` (`id`, `term`, `component`, `one_max`, `two_max`, `three_max`, `four_max`, `five_max`) VALUES
(1, 'Prelim', 'participation', 100, 100, 100, 100, 100),
(2, 'Prelim', 'written', 100, 100, 100, 100, 100),
(3, 'Prelim', 'performance', 100, 100, 100, 100, 100),
(4, 'Midterm', 'participation', 100, 100, 100, 100, 100),
(5, 'Midterm', 'written', 100, 100, 100, 100, 100),
(6, 'Midterm', 'performance', 100, 100, 100, 100, 100),
(7, 'Prefinal', 'participation', 100, 100, 100, 100, 100),
(8, 'Prefinal', 'written', 100, 100, 100, 100, 100),
(9, 'Prefinal', 'performance', 50, 100, 100, 100, 100),
(10, 'Final', 'participation', 100, 100, 100, 100, 100),
(11, 'Final', 'written', 100, 100, 100, 100, 100),
(12, 'Final', 'performance', 100, 100, 100, 100, 100);

-- --------------------------------------------------------

--
-- Table structure for table `section`
--

CREATE TABLE `section` (
  `sectionID` int(100) NOT NULL,
  `section` varchar(20) NOT NULL,
  `course_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `section`
--

INSERT INTO `section` (`sectionID`, `section`, `course_id`) VALUES
(1, 'Xiaomi', 1),
(2, 'TaskUs', 1),
(3, 'Everise', 1);

-- --------------------------------------------------------

--
-- Table structure for table `student`
--

CREATE TABLE `student` (
  `st_id` int(11) NOT NULL,
  `st_lastname` varchar(100) NOT NULL,
  `st_name` varchar(100) NOT NULL,
  `st_middlename` varchar(100) DEFAULT NULL,
  `st_suffix` varchar(10) DEFAULT NULL,
  `st_gender` text NOT NULL,
  `course_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student`
--

INSERT INTO `student` (`st_id`, `st_lastname`, `st_name`, `st_middlename`, `st_suffix`, `st_gender`, `course_id`) VALUES
(1, 'Abuyan', 'Christian ', 'Dacayana', '', 'Male', 1),
(2, 'Acopio', 'Jorge Matthew', 'Magat', '', 'Male', 1),
(3, 'Alvaro', 'John Carl', 'Faustino', '', 'Male', 1),
(4, 'Angeles', 'John Andrei', 'Aguilar', '', 'Male', 1),
(5, 'Aquino', 'John Carlo', 'Delos Santos', '', 'Male', 1),
(6, 'Arcilla', 'Eulfred', 'Talastas', '', 'Male', 1),
(7, 'Bernados', 'Klent Ivan ', 'Listones', '', 'Male', 1),
(9, 'Cagampang ', 'John Zedric', 'Espinosa', '', 'Male', 1),
(10, 'Calderon', 'Vinz Justine', 'Diama', '', 'Male', 1),
(11, 'Cencil', 'Jomar Samuel', 'Bitara', '', 'Male', 1),
(12, 'Cruz', 'Peejay', 'Repollo', '', 'Male', 1),
(13, 'Dela Cruz', 'Justine', 'Ladoc', '', 'Male', 1),
(14, 'Dillo', 'Jetric', 'Mejorado', '', 'Male', 1),
(15, 'Dulay', 'Mark Kenneth', 'Zafe', '', 'Male', 1),
(16, 'Espiritu', 'Limmuel', 'Guintu', '', 'Male', 1),
(17, 'Felix', 'Joshua', 'Reyes', '', 'Male', 1),
(18, 'Gayto', 'Kieth Wilson', 'Alba', '', 'Male', 1),
(19, 'Gonzales', 'Joshua', 'Peralta', '', 'Male', 1),
(20, 'Jornadal', 'Exequiel', 'Gutierrez', '', 'Male', 1),
(21, 'Toledo', 'Queen Ashley', 'Gabriel', '', 'Female', 1),
(22, 'Lazaro', 'Elfman Michael', 'Bernardino', '', 'Male', 1),
(23, 'Peñafiel', 'Christian Lloyd', 'Viola', '', 'Male', 1),
(24, 'Salvador', 'Joshua Joachim', 'Marquez', '', 'Male', 1),
(25, 'San Gabriel', 'Mark John', 'Aguilar', '', 'Male', 1),
(26, 'Tongohan', 'Rovie Andrei', 'Bojangin', '', 'Male', 1),
(28, 'Castillo', 'Sophia Lorriane', 'Tapado', '', 'Female', 1),
(30, 'Dava', 'Sofia Claudette', 'Lespasana', '', 'Female', 1),
(31, 'Santos', 'Mark Christian', 'Monceda', '', 'Male', 1),
(33, 'Belleca', 'Rhud Lester', 'Romagoza', '', 'Male', 1),
(35, 'Bautista', 'Eduardo', 'Hapa', '', 'Male', 1),
(36, 'De Jesus', 'Mark Joseph', 'Regalado', '', 'Male', 1),
(37, 'Vicmundo', 'Jhan Reinald', '', '', 'Male', 1),
(38, 'Musni', 'Kristine Anne ', 'Capote', '', 'Female', 1),
(39, 'Padayao', 'Karla Mae', 'Santiago', '', 'Female', 1),
(40, 'San Pedro', 'Ella', 'Reverente', '', 'Female', 1),
(41, 'Tintero', 'Andrea Felicity', '', '', 'Female', 1),
(42, 'De Guzman', 'Janneth', 'De Jesus', '', 'Female', 1),
(43, 'Clavel', 'Marianne Angela', 'Laron', '', 'Female', 1),
(44, 'Castillejos', 'Chrizan Hazel', 'Acope', '', 'Female', 1),
(45, 'Castillejos', 'Johannah Jessel', 'Acope', '', 'Female', 1),
(46, 'Martin', 'Kelly Joy', 'Hernandez', '', 'Female', 1),
(47, 'Cruz ', 'Ariane Marie', 'Intal', '', 'Female', 1),
(48, 'Diego', 'Jefferson', 'Flores', '', 'Male', 1),
(49, 'Garcia', 'Rovick', 'Cruz', '', 'Male', 1),
(50, 'Magdaong', 'Aaron Paul', 'Ramirez', '', 'Male', 1),
(51, 'Ondoy', 'Mark Jolo', 'Mendoza', '', 'Male', 1),
(52, 'Orido', 'Justine Cedric', 'Martin', '', 'Male', 1),
(53, 'Pagtalunan', 'John Luis', 'Marcelo', '', 'Male', 1),
(54, 'Puti', 'Rowell ', 'Chang', '', 'Male', 1),
(55, 'Querubin', 'Gian', 'Ejandra', '', 'Male', 1),
(56, 'Reyes', 'Kyle Jhermaine', 'Romero', '', 'Male', 1),
(57, 'Ubaldo', 'Ray Arthur', 'Domo', '', 'Male', 1),
(58, 'Garcia', 'Gabrielle', 'Linas', '', 'Male', 1),
(59, 'Sulit', 'Jose Domingo', 'Manapat', '', 'Male', 1),
(60, 'Villena', 'Rodge Allen', 'Villena', '', 'Male', 1),
(61, 'Nava', 'John Benedict', 'Baltazar', '', 'Male', 1),
(62, 'Lucas', 'Sherwin', 'Tuscano', '', 'Male', 1),
(63, 'Panganiban', 'Miltes Faith', 'Evangelista', '', 'Male', 1),
(64, 'Villena', 'Sherwin Patrick', 'Velasco', '', 'Male', 1),
(65, 'Martinez', 'Lawrenece', 'De Jesus', '', 'Male', 1),
(66, 'Ventura', 'Jairus John', 'Principe', '', 'Male', 1),
(67, 'Pulumbarit', 'Allen', 'Gozo', '', 'Male', 1),
(68, 'Geromala', 'Paul Vincent', 'Lenogon', '', 'Male', 1),
(69, 'Dela Cruz', 'Hanz Tyrone', 'Palomar', '', 'Male', 1),
(70, 'Masaoay', 'John Mark ', 'Fruto', '', 'Male', 1),
(71, 'Lito', 'Ryan Rey', '', '', 'Male', 1),
(72, 'Yamzon', 'Jhanine Rose', 'Concepcion', '', 'Female', 1),
(73, 'Posedio', 'Shiela Mae', 'Santiago', '', 'Female', 1),
(74, 'Gonzales ', 'Rasheed', 'Moranda', '', 'Male', 1),
(75, 'Guiemzon', 'Rebert Jade', 'Cajubas', '', 'Male', 1),
(76, 'Hilario', 'Dave', 'Cruz', '', 'Male', 1),
(77, 'Penolio', 'Justin', 'Quijano', '', 'Male', 1),
(78, 'Ramirez', 'Justine ', 'Enrile', '', 'Male', 1),
(79, 'Artiaga', 'Charish', 'Tampos', '', 'Female', 1),
(80, 'Cruz', 'Jasmin Mocis', 'Mano', '', 'Female', 1),
(81, 'Dela Cruz', 'Marielle', 'Ramos', '', 'Female', 1),
(82, 'Magnayon', 'Ginalyn', '', '', 'Female', 1),
(83, 'Soriano', 'Harvey', 'Aquino', '', 'Female', 1),
(84, 'Tabuzo', 'Edwared Greeih', 'Melgar', '', 'Male', 1),
(85, 'Tungolh', 'Ferdinand', 'Valenzuela', '', 'Male', 1),
(86, 'Guillermo', 'Kurt Ryan ', 'Tadeo', '', 'Male', 1),
(87, 'Paloma', 'Axl John Ross', 'Paloma', '', 'Male', 1),
(88, 'Reyes', 'John Mark', 'Merida', '', 'Male', 1),
(89, 'Santos ', 'Zeidrel', 'Austria', '', 'Male', 1),
(90, 'Seacor', 'Ash Peirre', 'Gonzales', '', 'Male', 1),
(91, 'Dela Cruz', 'Jan Rich', 'Dumalay', '', 'Male', 1),
(92, 'Sicat', 'John Ree', 'Carulla', '', 'Male', 1),
(93, 'Roxas', 'April', '', '', 'Female', 1),
(94, 'Gonzales', 'John Andre', 'Bonus', '', 'Male', 1),
(95, 'Legaspi', 'Neo Nestric Kram', 'Beleno', '', 'Male', 1),
(96, 'Agapito', 'Rosemarie', 'Salvador', '', 'Female', 1),
(97, 'Malumbres', 'Julia', 'Iglesia', '', 'Female', 1),
(98, 'Berroya', 'Wilnerin', 'Villafuerte', '', 'Female', 1),
(99, 'Abarejo', 'Tyrone', 'Lopez', '', 'Male', 1),
(100, 'Santos', 'John Mark', 'Vega', '', 'Male', 1),
(101, 'Marcelo', 'Ashley Amira', 'De Jesus', '', 'Female', 1),
(102, 'Manalastas', 'John Louie', 'Labial', '', 'Male', 1),
(103, 'Rivera', 'Jenna Pauline', 'Gimena', '', 'Female', 1),
(104, 'Indita ', 'Ma. Eurika Sophia', 'Giban', '', 'Female', 1),
(105, 'Dela Cruz', 'Nielyia', 'Garcia', '', 'Female', 1),
(106, 'Tagalog', 'Angeline Noime', 'Ogabar', '', 'Female', 1),
(107, 'Sta. Maria', 'Sharina', 'Peralta', '', 'Female', 1),
(108, 'Alejandro', 'Kurt Andrew', 'Galdote', '', 'Male', 1),
(109, 'Santos', 'Simon Morten', 'Enriquez', '', 'Male', 1),
(110, 'Marcelo', 'Rommel', 'Delos Santos', 'Jr.', 'Male', 1),
(111, 'Lamosa', 'Jefferson', 'Darang', '', 'Male', 1);

-- --------------------------------------------------------

--
-- Table structure for table `student_section`
--

CREATE TABLE `student_section` (
  `stsec_id` int(11) NOT NULL,
  `st_id` int(11) NOT NULL,
  `sectionID` int(11) NOT NULL,
  `yearlvl` int(5) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_section`
--

INSERT INTO `student_section` (`stsec_id`, `st_id`, `sectionID`, `yearlvl`) VALUES
(9, 1, 1, 3),
(10, 2, 1, 3),
(11, 3, 1, 3),
(12, 4, 1, 3),
(13, 5, 1, 3),
(14, 6, 1, 3),
(15, 7, 1, 3),
(17, 9, 1, 3),
(18, 10, 1, 3),
(19, 11, 1, 3),
(20, 12, 1, 3),
(21, 13, 1, 3),
(22, 14, 1, 3),
(23, 15, 1, 3),
(24, 16, 1, 3),
(25, 17, 2, 3),
(26, 18, 2, 3),
(27, 19, 1, 3),
(28, 20, 1, 3),
(29, 21, 1, 3),
(30, 22, 1, 3),
(31, 23, 1, 3),
(32, 24, 2, 3),
(33, 25, 1, 3),
(34, 26, 2, 3),
(36, 28, 2, 3),
(38, 30, 1, 3),
(39, 31, 3, 3),
(41, 33, 1, 3),
(43, 35, 1, 3),
(44, 36, 1, 3),
(45, 37, 1, 3),
(46, 38, 2, 3),
(47, 39, 2, 3),
(48, 40, 2, 3),
(49, 41, 2, 3),
(50, 42, 2, 3),
(51, 43, 2, 3),
(52, 44, 2, 3),
(53, 45, 2, 3),
(54, 46, 2, 3),
(55, 47, 2, 3),
(56, 48, 2, 3),
(57, 49, 2, 3),
(58, 50, 2, 3),
(59, 51, 2, 3),
(60, 52, 2, 3),
(61, 53, 2, 3),
(62, 54, 2, 3),
(63, 55, 2, 3),
(64, 56, 2, 3),
(65, 57, 2, 3),
(66, 58, 2, 3),
(67, 59, 2, 3),
(68, 60, 2, 3),
(69, 61, 2, 3),
(70, 62, 2, 3),
(71, 63, 2, 3),
(72, 64, 2, 3),
(73, 65, 2, 3),
(74, 66, 2, 3),
(75, 67, 2, 3),
(76, 68, 2, 3),
(77, 69, 2, 3),
(78, 70, 2, 3),
(79, 71, 2, 3),
(80, 72, 2, 3),
(81, 73, 2, 3),
(82, 74, 3, 3),
(83, 75, 3, 3),
(84, 76, 3, 3),
(85, 77, 3, 3),
(86, 78, 3, 3),
(87, 79, 3, 3),
(88, 80, 3, 3),
(89, 81, 3, 3),
(90, 82, 3, 3),
(91, 83, 3, 3),
(92, 84, 3, 3),
(93, 85, 3, 3),
(94, 86, 3, 3),
(95, 87, 3, 3),
(96, 88, 3, 3),
(97, 89, 3, 3),
(98, 90, 3, 3),
(99, 91, 3, 3),
(100, 92, 3, 3),
(101, 93, 3, 3),
(102, 94, 3, 3),
(103, 95, 3, 3),
(104, 96, 3, 3),
(105, 97, 3, 3),
(106, 98, 3, 3),
(107, 99, 3, 3),
(108, 100, 3, 3),
(109, 101, 3, 3),
(110, 102, 3, 3),
(111, 103, 3, 3),
(112, 104, 3, 3),
(113, 105, 3, 3),
(114, 106, 3, 3),
(115, 107, 3, 3),
(116, 108, 3, 3),
(117, 109, 3, 3),
(118, 110, 3, 3),
(119, 111, 3, 3);

-- --------------------------------------------------------

--
-- Table structure for table `student_subject`
--

CREATE TABLE `student_subject` (
  `id` int(11) NOT NULL,
  `st_id` int(11) DEFAULT NULL,
  `assign_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `subject`
--

CREATE TABLE `subject` (
  `sub_id` int(11) NOT NULL,
  `sub_name` varchar(100) NOT NULL,
  `sub_code` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subject`
--

INSERT INTO `subject` (`sub_id`, `sub_name`, `sub_code`) VALUES
(1, 'System Administration and Maintenance', 'SA101');

-- --------------------------------------------------------

--
-- Table structure for table `written`
--

CREATE TABLE `written` (
  `written_id` int(11) NOT NULL,
  `written_one` int(11) NOT NULL,
  `written_two` int(11) NOT NULL,
  `written_three` int(11) NOT NULL,
  `written_four` int(11) NOT NULL,
  `written_five` int(11) NOT NULL,
  `term` varchar(20) NOT NULL,
  `date_created` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `assign_subject`
--
ALTER TABLE `assign_subject`
  ADD PRIMARY KEY (`assign_id`),
  ADD KEY `sub_id` (`sub_id`),
  ADD KEY `inst_id` (`inst_id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`Att_ID`),
  ADD KEY `StudentID` (`st_id`);

--
-- Indexes for table `class_record`
--
ALTER TABLE `class_record`
  ADD PRIMARY KEY (`rec_id`),
  ADD UNIQUE KEY `uq_class_record` (`st_id`,`sectionID`,`sub_id`);

--
-- Indexes for table `class_record_exam`
--
ALTER TABLE `class_record_exam`
  ADD PRIMARY KEY (`rec_id`,`term`),
  ADD KEY `exam_id` (`exam_id`);

--
-- Indexes for table `class_record_participation`
--
ALTER TABLE `class_record_participation`
  ADD PRIMARY KEY (`rec_id`,`term`),
  ADD KEY `par_id` (`par_id`);

--
-- Indexes for table `class_record_performance`
--
ALTER TABLE `class_record_performance`
  ADD PRIMARY KEY (`rec_id`,`term`),
  ADD KEY `perf_id` (`perf_id`);

--
-- Indexes for table `class_record_written`
--
ALTER TABLE `class_record_written`
  ADD PRIMARY KEY (`rec_id`,`term`),
  ADD KEY `written_id` (`written_id`);

--
-- Indexes for table `course`
--
ALTER TABLE `course`
  ADD PRIMARY KEY (`course_id`),
  ADD KEY `dept_id` (`dept_id`);

--
-- Indexes for table `department`
--
ALTER TABLE `department`
  ADD PRIMARY KEY (`dept_id`);

--
-- Indexes for table `exam`
--
ALTER TABLE `exam`
  ADD PRIMARY KEY (`exam_id`);

--
-- Indexes for table `exam_settings`
--
ALTER TABLE `exam_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_term` (`term`);

--
-- Indexes for table `instructor`
--
ALTER TABLE `instructor`
  ADD PRIMARY KEY (`inst_id`),
  ADD KEY `dept_id` (`dept_id`);

--
-- Indexes for table `last_sync`
--
ALTER TABLE `last_sync`
  ADD PRIMARY KEY (`sync_id`);

--
-- Indexes for table `participation`
--
ALTER TABLE `participation`
  ADD PRIMARY KEY (`par_id`);

--
-- Indexes for table `performance`
--
ALTER TABLE `performance`
  ADD PRIMARY KEY (`perf_id`);

--
-- Indexes for table `score_settings`
--
ALTER TABLE `score_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_term_component` (`term`,`component`);

--
-- Indexes for table `section`
--
ALTER TABLE `section`
  ADD PRIMARY KEY (`sectionID`);

--
-- Indexes for table `student`
--
ALTER TABLE `student`
  ADD PRIMARY KEY (`st_id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `student_section`
--
ALTER TABLE `student_section`
  ADD PRIMARY KEY (`stsec_id`),
  ADD KEY `st_id` (`st_id`);

--
-- Indexes for table `student_subject`
--
ALTER TABLE `student_subject`
  ADD PRIMARY KEY (`id`),
  ADD KEY `st_id` (`st_id`),
  ADD KEY `assign_id` (`assign_id`);

--
-- Indexes for table `subject`
--
ALTER TABLE `subject`
  ADD PRIMARY KEY (`sub_id`);

--
-- Indexes for table `written`
--
ALTER TABLE `written`
  ADD PRIMARY KEY (`written_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `assign_subject`
--
ALTER TABLE `assign_subject`
  MODIFY `assign_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `Att_ID` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1086;

--
-- AUTO_INCREMENT for table `class_record`
--
ALTER TABLE `class_record`
  MODIFY `rec_id` int(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=204;

--
-- AUTO_INCREMENT for table `course`
--
ALTER TABLE `course`
  MODIFY `course_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `department`
--
ALTER TABLE `department`
  MODIFY `dept_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `exam`
--
ALTER TABLE `exam`
  MODIFY `exam_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `exam_settings`
--
ALTER TABLE `exam_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `instructor`
--
ALTER TABLE `instructor`
  MODIFY `inst_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `participation`
--
ALTER TABLE `participation`
  MODIFY `par_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `performance`
--
ALTER TABLE `performance`
  MODIFY `perf_id` int(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT for table `score_settings`
--
ALTER TABLE `score_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `section`
--
ALTER TABLE `section`
  MODIFY `sectionID` int(100) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `student`
--
ALTER TABLE `student`
  MODIFY `st_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=112;

--
-- AUTO_INCREMENT for table `student_section`
--
ALTER TABLE `student_section`
  MODIFY `stsec_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=120;

--
-- AUTO_INCREMENT for table `student_subject`
--
ALTER TABLE `student_subject`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `subject`
--
ALTER TABLE `subject`
  MODIFY `sub_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `written`
--
ALTER TABLE `written`
  MODIFY `written_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `assign_subject`
--
ALTER TABLE `assign_subject`
  ADD CONSTRAINT `assign_subject_ibfk_1` FOREIGN KEY (`sub_id`) REFERENCES `subject` (`sub_id`),
  ADD CONSTRAINT `assign_subject_ibfk_2` FOREIGN KEY (`inst_id`) REFERENCES `instructor` (`inst_id`),
  ADD CONSTRAINT `assign_subject_ibfk_3` FOREIGN KEY (`course_id`) REFERENCES `course` (`course_id`);

--
-- Constraints for table `class_record_exam`
--
ALTER TABLE `class_record_exam`
  ADD CONSTRAINT `class_record_exam_ibfk_1` FOREIGN KEY (`rec_id`) REFERENCES `class_record` (`rec_id`),
  ADD CONSTRAINT `class_record_exam_ibfk_2` FOREIGN KEY (`exam_id`) REFERENCES `exam` (`exam_id`);

--
-- Constraints for table `class_record_participation`
--
ALTER TABLE `class_record_participation`
  ADD CONSTRAINT `class_record_participation_ibfk_1` FOREIGN KEY (`rec_id`) REFERENCES `class_record` (`rec_id`),
  ADD CONSTRAINT `class_record_participation_ibfk_2` FOREIGN KEY (`par_id`) REFERENCES `participation` (`par_id`);

--
-- Constraints for table `class_record_performance`
--
ALTER TABLE `class_record_performance`
  ADD CONSTRAINT `class_record_performance_ibfk_1` FOREIGN KEY (`rec_id`) REFERENCES `class_record` (`rec_id`),
  ADD CONSTRAINT `class_record_performance_ibfk_2` FOREIGN KEY (`perf_id`) REFERENCES `performance` (`perf_id`);

--
-- Constraints for table `class_record_written`
--
ALTER TABLE `class_record_written`
  ADD CONSTRAINT `class_record_written_ibfk_1` FOREIGN KEY (`rec_id`) REFERENCES `class_record` (`rec_id`),
  ADD CONSTRAINT `class_record_written_ibfk_2` FOREIGN KEY (`written_id`) REFERENCES `written` (`written_id`);

--
-- Constraints for table `course`
--
ALTER TABLE `course`
  ADD CONSTRAINT `course_ibfk_1` FOREIGN KEY (`dept_id`) REFERENCES `department` (`dept_id`);

--
-- Constraints for table `instructor`
--
ALTER TABLE `instructor`
  ADD CONSTRAINT `instructor_ibfk_1` FOREIGN KEY (`dept_id`) REFERENCES `department` (`dept_id`);

--
-- Constraints for table `student`
--
ALTER TABLE `student`
  ADD CONSTRAINT `student_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `course` (`course_id`);

--
-- Constraints for table `student_section`
--
ALTER TABLE `student_section`
  ADD CONSTRAINT `StudentID_Constraint` FOREIGN KEY (`st_id`) REFERENCES `student` (`st_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `student_subject`
--
ALTER TABLE `student_subject`
  ADD CONSTRAINT `student_subject_ibfk_1` FOREIGN KEY (`st_id`) REFERENCES `student` (`st_id`),
  ADD CONSTRAINT `student_subject_ibfk_2` FOREIGN KEY (`assign_id`) REFERENCES `assign_subject` (`assign_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
