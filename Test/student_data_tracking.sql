-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 11, 2025 at 01:06 AM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.0.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `student_data_tracking`
--

-- --------------------------------------------------------

--
-- Table structure for table `academic_years`
--

CREATE TABLE `academic_years` (
  `academic_year_id` int(11) NOT NULL,
  `year` varchar(10) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `is_current` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `academic_years`
--

INSERT INTO `academic_years` (`academic_year_id`, `year`, `start_date`, `end_date`, `is_current`, `created_at`, `updated_at`) VALUES
(1, '2024', '2024-06-01', '2025-05-31', 1, '2025-03-03 14:41:11', '2025-03-03 14:41:11');

-- --------------------------------------------------------

--
-- Table structure for table `account`
--

CREATE TABLE `account` (
  `id_account` int(11) NOT NULL,
  `username_account` varchar(40) NOT NULL,
  `email_account` varchar(40) NOT NULL,
  `password_account` varchar(97) NOT NULL,
  `Role_account` enum('student','teacher','academic') NOT NULL,
  `email_verified` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum('active','inactive','pending') NOT NULL DEFAULT 'pending',
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `account`
--

INSERT INTO `account` (`id_account`, `username_account`, `email_account`, `password_account`, `Role_account`, `email_verified`, `status`, `last_login`, `created_at`, `updated_at`) VALUES
(1, 'Test', 'Test@gmail.com', '$2y$10$5WCpLD.JcjUZ.rrdG9TVhuzrc/M/nBVPOWfBl/sO.0DVW0X9jvRdu', 'student', 0, 'pending', NULL, '2025-03-03 21:21:51', '2025-03-03 21:21:51'),
(2, 'Test1', 'Test1@gmail.com', '$2y$10$XhByeZyrXW0w88zNbwi8SeIvD0JCZSI3qqvShpj2XKpKeOrQ0UQyi', 'academic', 0, 'pending', '2025-03-03 23:02:12', '2025-03-03 21:31:09', '2025-03-03 23:02:12'),
(3, 'Test2', 'Test2@gmail.com', '$2y$10$5nvhWEoW.1qte43kc/6wtuig1bHMKlfRnG/SU3MWniqePEyfiWWbe', 'academic', 0, 'pending', '2025-03-03 21:43:19', '2025-03-03 21:43:15', '2025-03-03 21:43:19'),
(4, 'Test3', 'Test3@gmail.com', '$2y$10$CSYczpOBw.8qxyfFUlo51uU5oQIcbyl.Uw/lP1H4knRZQZ67QBEXy', 'academic', 0, 'pending', NULL, '2025-03-03 22:08:11', '2025-03-03 22:08:11'),
(5, 'Testt', 'Testt@gmail.com', '$2y$10$y55J2EckTWD/zRFd/dS4tOxo8KUpBFpKQdBnce0m8NcEqScsVfT1e', 'academic', 0, 'active', '2025-03-03 22:10:32', '2025-03-03 22:10:22', '2025-03-03 22:10:32'),
(6, 'testst', 'Testst@gmail.com', '$2y$10$coyx/wMDmFLloLX7HstC0upBQW9.UxP/f6OVwHhJAPmGJt8ICkfJ2', 'student', 0, 'active', '2025-03-03 22:34:56', '2025-03-03 22:16:31', '2025-03-03 22:34:56'),
(7, 'Testsdu', 'Testsdu@gmail.com', '$2y$10$B4TgYkCAW3/AlgH94ctIJeJE78YSDw/FAWSCvCRkM1eOyuD7APJSa', 'student', 0, 'active', '2025-03-10 12:37:25', '2025-03-06 14:38:43', '2025-03-10 12:37:25'),
(8, 'Testss', 'Testss@gmail.com', '$2y$10$JqSkeuxiagwUwYszK5DEZ.cH/ZvIKcW5hSMsoLkp72lZvbrQ/zL5O', 'student', 0, 'active', '2025-03-06 14:48:21', '2025-03-06 14:48:14', '2025-03-06 14:48:21'),
(9, 'Testtc', 'Testtc@gmail.com', '$2y$10$WmFSxrWL3pBxweIwVFi3VeIsbY4dVUVZ88Tri76mtafYT577ZIgvu', 'academic', 0, 'active', '2025-03-07 13:53:00', '2025-03-07 13:52:53', '2025-03-07 13:53:00'),
(10, 'Thinna', 'Thinna@gmail.com', '$2y$10$juE0A6Mx.ruAfA42JQlyO.IoWYYVTF46JmoYNaTuBaTM3Tt29oVPG', 'academic', 0, 'active', '2025-03-10 23:42:34', '2025-03-08 13:42:12', '2025-03-10 23:42:34'),
(11, 'สหรัฐ', 'saharat@gmail.com', '$2y$10$AumUtPHHjLkcWSn/FdYpvevV/nAV852xmjDbIVpBhiPJJSBqOyzaq', 'student', 0, 'active', '2025-03-10 23:43:56', '2025-03-08 13:44:25', '2025-03-10 23:43:56'),
(12, '1223', 'Testsss@gmail.com', '$2y$10$3uiWUTCIdiC18Dz4S7Di7OuhMKRRvPzuiBzxHZxxbZsCaEGYXxLdm', 'student', 0, 'active', '2025-03-10 15:00:24', '2025-03-10 15:00:13', '2025-03-10 15:00:24'),
(13, 'Teacher', 'Teacher@gmail.com', '$2y$10$ycpvNBI1NN2/miMsyc3H5u0gtHl7E0kbmVUTc8mK3rw4mGDVWG3Um', 'teacher', 0, 'active', '2025-03-10 15:47:31', '2025-03-10 15:47:27', '2025-03-10 15:47:31');

--
-- Triggers `account`
--
DELIMITER $$
CREATE TRIGGER `tr_after_account_insert` AFTER INSERT ON `account` FOR EACH ROW BEGIN
    INSERT INTO `logs` (id_account, action, details)
    VALUES (NEW.id_account, 'account_created', CONCAT('New account created with role: ', NEW.Role_account));
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `tr_after_password_update` AFTER UPDATE ON `account` FOR EACH ROW BEGIN
    IF NEW.password_account != OLD.password_account THEN
        INSERT INTO `logs` (id_account, action, details)
        VALUES (NEW.id_account, 'password_changed', 'User password was changed');
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `buildings`
--

CREATE TABLE `buildings` (
  `building_id` int(11) NOT NULL,
  `building_code` varchar(10) NOT NULL,
  `building_name` varchar(100) NOT NULL,
  `thai_building_name` varchar(100) NOT NULL,
  `location` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `buildings`
--

INSERT INTO `buildings` (`building_id`, `building_code`, `building_name`, `thai_building_name`, `location`, `created_at`, `updated_at`) VALUES
(1, 'Bld01', 'Building 1', 'อาคาร 1', NULL, '2025-03-03 14:41:11', '2025-03-03 14:41:11'),
(2, 'Bld11', 'Building 11', 'อาคาร 11', NULL, '2025-03-03 14:41:11', '2025-03-03 14:41:11'),
(3, 'BldSc', 'Science Building', 'อาคารวิทยาศาสตร์', NULL, '2025-03-03 14:41:11', '2025-03-03 14:41:11'),
(4, 'BldEng', 'Engineering Building', 'อาคารวิศวกรรมศาสตร์', NULL, '2025-03-03 14:41:11', '2025-03-03 14:41:11'),
(5, 'Online', 'Online', 'ออนไลน์', NULL, '2025-03-03 14:41:11', '2025-03-03 14:41:11');

-- --------------------------------------------------------

--
-- Table structure for table `class_schedules`
--

CREATE TABLE `class_schedules` (
  `schedule_id` int(11) NOT NULL,
  `section_id` int(11) NOT NULL,
  `room_id` int(11) DEFAULT NULL,
  `day_of_week` enum('monday','tuesday','wednesday','thursday','friday','saturday','sunday') NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `is_online` tinyint(1) NOT NULL DEFAULT 0,
  `online_meeting_link` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `course`
--

CREATE TABLE `course` (
  `Course_Code` int(11) NOT NULL,
  `Course_Name` varchar(255) NOT NULL,
  `Credits` int(11) NOT NULL,
  `Curriculum_ID` int(11) NOT NULL,
  `Instructor` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `course`
--

INSERT INTO `course` (`Course_Code`, `Course_Name`, `Credits`, `Curriculum_ID`, `Instructor`, `created_at`, `updated_at`) VALUES
(1145201, 'คณิตศาสตร์สำหรับเทคโนโลยีสารสนเทศ', 3, 6, 'อ.ดร. สุระสิทธิ์ ทรงม้า', '2025-03-10 13:40:39', '2025-03-10 13:40:39'),
(1145202, 'แคลคูลัสสำหรับเทคโนโลยีสารสนเทศ', 3, 6, 'อ.ดร. จุฑาวุฒิ จันทรมาลี', '2025-03-10 13:40:39', '2025-03-10 13:40:39'),
(1146202, 'หลักสำคัญของเทคโนโลยีสารสนเทศ', 3, 6, 'ผศ.ปรมัตถ์ปัญปรัชญ์ ต้องประสงค์', '2025-03-10 13:40:39', '2025-03-10 13:40:39'),
(1146203, 'การเขียนโปรแกรมคอมพิวเตอร์', 3, 6, 'อ.ดร. ชวาลศักดิ์ เพชรจันทร์ฉาย', '2025-03-10 13:40:39', '2025-03-10 13:40:39'),
(1146204, 'ฐานข้อมูลเบื้องต้น', 3, 6, 'อ.จุฑามาศ ศรีรัตนา', '2025-03-10 13:40:39', '2025-03-10 13:40:39'),
(1146205, 'เครือข่ายคอมพิวเตอร์เบื้องต้น', 3, 6, 'อ.อมรชัย ชัยชนะ', '2025-03-10 13:40:39', '2025-03-10 13:40:39'),
(1146206, 'การวิเคราะห์และออกแบบระบบ', 3, 6, 'รศ.ดร. ปริศนา มัชฌิมา', '2025-03-10 13:40:39', '2025-03-10 13:40:39'),
(1500201, 'ทักษะการสื่อสารภาษาไทย', 3, 1, 'ผศ.ดร. สุทธาสินี เกสร์ประทุม', '2025-03-10 13:40:39', '2025-03-10 13:40:39'),
(1500202, 'ภาษาอังกฤษสำหรับวิถีชีวิตสมัยใหม่', 3, 1, 'อ.ดร. สรพล จิระสวัสดิ์', '2025-03-10 13:40:39', '2025-03-10 13:40:39'),
(1500203, 'ภาษาอังกฤษเพื่อการสื่อสารสากล', 3, 1, 'อ.เบญจวรรณ ขุนฤทธิ์', '2025-03-10 13:40:39', '2025-03-10 13:40:39'),
(1500204, 'จิตวิทยาเชิงบวก', 3, 1, 'ผศ.ดร. พันธรักษ์ ผูกพันธุ์', '2025-03-10 13:40:39', '2025-03-10 13:40:39'),
(2500201, 'อาหารการกิน', 3, 1, 'ผศ.ดร. ฐิตา ฟูเผ่า', '2025-03-10 13:40:39', '2025-03-10 13:40:39'),
(2500202, 'วิถีชีวิตตามแนวเศรษฐกิจหมุนเวียน', 3, 1, 'อ.กมลกนก เกียรติศักดิ์ชัย', '2025-03-10 13:40:39', '2025-03-10 13:40:39'),
(3000201, 'การเป็นผู้ประกอบการ', 3, 77, 'ผศ.ดร. รจนา จันทราสา', '2025-03-10 13:40:39', '2025-03-10 13:40:39'),
(3000202, 'การบริหารความขัดแย้งและการจัดการความเครียด', 3, 77, 'อ.ดร. ศรีสุดา วงศ์วิเศษกุล', '2025-03-10 13:40:39', '2025-03-10 13:40:39'),
(3000203, 'ภาษาญี่ปุ่นเพื่อการสื่อสารเบื้องต้น', 3, 77, 'อ.รินทร์ลิตา จิตติสุนทร', '2025-03-10 13:40:39', '2025-03-10 13:40:39'),
(3000204, 'ภาษาจีนเพื่อการสื่อสารเบื้องต้น', 3, 77, 'อ.สุพรรณี หมั่นทำการ', '2025-03-10 13:40:39', '2025-03-10 13:40:39'),
(3000205, 'การออกแบบสื่อดิจิทัล', 3, 77, 'ผศ.ดร. ลัดดา สวนมะลิ', '2025-03-10 13:40:39', '2025-03-10 13:40:39'),
(4000201, 'ความเข้าใจและการใช้ดิจิทัล', 3, 1, 'อ.ดร. ชัยพร พานิชรุทติวงศ์', '2025-03-10 13:40:39', '2025-03-10 13:40:39');

-- --------------------------------------------------------

--
-- Table structure for table `course_registration`
--

CREATE TABLE `course_registration` (
  `Registration_ID` int(10) NOT NULL,
  `Student_ID` varchar(20) NOT NULL,
  `Course_Code` int(10) NOT NULL,
  `section_id` int(11) DEFAULT NULL,
  `Semester` int(1) NOT NULL,
  `Academic_Year` int(4) NOT NULL,
  `Grade` decimal(3,2) DEFAULT NULL,
  `Credits` int(1) NOT NULL,
  `status` enum('registered','withdrawn','dropped') NOT NULL DEFAULT 'registered',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `course_registration`
--

INSERT INTO `course_registration` (`Registration_ID`, `Student_ID`, `Course_Code`, `section_id`, `Semester`, `Academic_Year`, `Grade`, `Credits`, `status`, `created_at`, `updated_at`) VALUES
(1, '202505774', 1500202, 4, 1, 2024, NULL, 3, 'registered', '2025-03-10 23:06:38', '2025-03-10 23:06:38'),
(2, '202505774', 2500201, 6, 1, 2024, NULL, 3, 'registered', '2025-03-10 23:06:38', '2025-03-10 23:06:38'),
(3, '202505774', 1146203, 7, 1, 2024, NULL, 3, 'registered', '2025-03-10 23:06:38', '2025-03-10 23:06:38'),
(4, '202505774', 1146204, 9, 1, 2024, NULL, 3, 'registered', '2025-03-10 23:06:38', '2025-03-10 23:06:38'),
(5, '202505774', 3000201, 10, 1, 2024, NULL, 3, 'registered', '2025-03-10 23:06:38', '2025-03-10 23:06:38'),
(6, '202505774', 3000203, 11, 1, 2024, NULL, 3, 'registered', '2025-03-10 23:06:38', '2025-03-10 23:06:38'),
(7, '202505774', 1500201, 1, 1, 2024, NULL, 3, 'registered', '2025-03-10 23:06:47', '2025-03-10 23:06:47');

-- --------------------------------------------------------

--
-- Table structure for table `course_sections`
--

CREATE TABLE `course_sections` (
  `section_id` int(11) NOT NULL,
  `Course_Code` int(11) NOT NULL,
  `semester_id` int(11) NOT NULL,
  `section_number` varchar(5) NOT NULL,
  `instructor_name` varchar(200) NOT NULL,
  `max_students` int(3) NOT NULL DEFAULT 30,
  `current_students` int(3) NOT NULL DEFAULT 0,
  `status` enum('active','cancelled','closed') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `course_sections`
--

INSERT INTO `course_sections` (`section_id`, `Course_Code`, `semester_id`, `section_number`, `instructor_name`, `max_students`, `current_students`, `status`, `created_at`, `updated_at`) VALUES
(1, 1500201, 1, 'A1', 'ผศ.ดร. สุทธาสินี เกสร์ประทุม', 40, 36, 'active', '2025-03-10 13:40:39', '2025-03-10 23:06:47'),
(2, 1500201, 1, 'A2', 'ผศ.ดร. สุทธาสินี เกสร์ประทุม', 40, 38, 'active', '2025-03-10 13:40:39', '2025-03-10 13:40:39'),
(3, 1500202, 1, 'A1', 'อ.ดร. สรพล จิระสวัสดิ์', 35, 30, 'active', '2025-03-10 13:40:39', '2025-03-10 13:40:39'),
(4, 1500202, 1, 'A2', 'อ.เบญจวรรณ ขุนฤทธิ์', 35, 26, 'active', '2025-03-10 13:40:39', '2025-03-10 23:06:38'),
(5, 2500201, 1, 'A1', 'ผศ.ดร. ฐิตา ฟูเผ่า', 30, 30, 'closed', '2025-03-10 13:40:39', '2025-03-10 13:40:39'),
(6, 2500201, 1, 'A2', 'ผศ.ดร. ฐิตา ฟูเผ่า', 30, 29, 'active', '2025-03-10 13:40:39', '2025-03-10 23:06:38'),
(7, 1146203, 1, 'A1', 'อ.ดร. ชวาลศักดิ์ เพชรจันทร์ฉาย', 30, 21, 'active', '2025-03-10 13:40:39', '2025-03-10 23:06:38'),
(8, 1146203, 1, 'A2', 'อ.ธนชาติ นุ่มนนท์', 30, 15, 'active', '2025-03-10 13:40:39', '2025-03-10 13:40:39'),
(9, 1146204, 1, 'A1', 'อ.จุฑามาศ ศรีรัตนา', 25, 24, 'active', '2025-03-10 13:40:39', '2025-03-10 23:06:38'),
(10, 3000201, 1, 'A1', 'ผศ.ดร. รจนา จันทราสา', 40, 16, 'active', '2025-03-10 13:40:39', '2025-03-10 23:06:38'),
(11, 3000203, 1, 'A1', 'อ.รินทร์ลิตา จิตติสุนทร', 30, 11, 'active', '2025-03-10 13:40:39', '2025-03-10 23:06:38'),
(12, 3000205, 1, 'A1', 'ผศ.ดร. ลัดดา สวนมะลิ', 25, 25, 'closed', '2025-03-10 13:40:39', '2025-03-10 13:40:39');

-- --------------------------------------------------------

--
-- Table structure for table `course_type`
--

CREATE TABLE `course_type` (
  `Course_Code` int(11) NOT NULL,
  `Course_Name` varchar(255) NOT NULL,
  `Credits` int(11) NOT NULL,
  `Course_Type_ID` enum('1','2','3') DEFAULT NULL COMMENT '1=วิชาทั่วไป, 2=วิชาเฉพาะ, 3=วิชาเสรี',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `course_type`
--

INSERT INTO `course_type` (`Course_Code`, `Course_Name`, `Credits`, `Course_Type_ID`, `created_at`, `updated_at`) VALUES
(1145201, 'คณิตศาสตร์สำหรับเทคโนโลยีสารสนเทศ', 3, '2', '2025-03-10 13:40:39', '2025-03-10 13:40:39'),
(1145202, 'แคลคูลัสสำหรับเทคโนโลยีสารสนเทศ', 3, '2', '2025-03-10 13:40:39', '2025-03-10 13:40:39'),
(1146202, 'หลักสำคัญของเทคโนโลยีสารสนเทศ', 3, '2', '2025-03-10 13:40:39', '2025-03-10 13:40:39'),
(1146203, 'การเขียนโปรแกรมคอมพิวเตอร์', 3, '2', '2025-03-10 13:40:39', '2025-03-10 13:40:39'),
(1146204, 'ฐานข้อมูลเบื้องต้น', 3, '2', '2025-03-10 13:40:39', '2025-03-10 13:40:39'),
(1146205, 'เครือข่ายคอมพิวเตอร์เบื้องต้น', 3, '2', '2025-03-10 13:40:39', '2025-03-10 13:40:39'),
(1146206, 'การวิเคราะห์และออกแบบระบบ', 3, '2', '2025-03-10 13:40:39', '2025-03-10 13:40:39'),
(1500201, 'ทักษะการสื่อสารภาษาไทย', 3, '1', '2025-03-10 13:40:39', '2025-03-10 13:40:39'),
(1500202, 'ภาษาอังกฤษสำหรับวิถีชีวิตสมัยใหม่', 3, '1', '2025-03-10 13:40:39', '2025-03-10 13:40:39'),
(1500203, 'ภาษาอังกฤษเพื่อการสื่อสารสากล', 3, '1', '2025-03-10 13:40:39', '2025-03-10 13:40:39'),
(1500204, 'จิตวิทยาเชิงบวก', 3, '1', '2025-03-10 13:40:39', '2025-03-10 13:40:39'),
(2500201, 'อาหารการกิน', 3, '1', '2025-03-10 13:40:39', '2025-03-10 13:40:39'),
(2500202, 'วิถีชีวิตตามแนวเศรษฐกิจหมุนเวียน', 3, '1', '2025-03-10 13:40:39', '2025-03-10 13:40:39'),
(3000201, 'การเป็นผู้ประกอบการ', 3, '3', '2025-03-10 13:40:39', '2025-03-10 13:40:39'),
(3000202, 'การบริหารความขัดแย้งและการจัดการความเครียด', 3, '3', '2025-03-10 13:40:39', '2025-03-10 13:40:39'),
(3000203, 'ภาษาญี่ปุ่นเพื่อการสื่อสารเบื้องต้น', 3, '3', '2025-03-10 13:40:39', '2025-03-10 13:40:39'),
(3000204, 'ภาษาจีนเพื่อการสื่อสารเบื้องต้น', 3, '3', '2025-03-10 13:40:39', '2025-03-10 13:40:39'),
(3000205, 'การออกแบบสื่อดิจิทัล', 3, '3', '2025-03-10 13:40:39', '2025-03-10 13:40:39'),
(4000201, 'ความเข้าใจและการใช้ดิจิทัล', 3, '1', '2025-03-10 13:40:39', '2025-03-10 13:40:39');

-- --------------------------------------------------------

--
-- Table structure for table `curriculum`
--

CREATE TABLE `curriculum` (
  `Curriculum_ID` int(7) NOT NULL,
  `Curriculum_Name` varchar(50) NOT NULL,
  `Required_Credit` int(2) NOT NULL,
  `Duration` int(1) NOT NULL,
  `department_id` int(11) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'active',
  `year_established` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `curriculum`
--

INSERT INTO `curriculum` (`Curriculum_ID`, `Curriculum_Name`, `Required_Credit`, `Duration`, `department_id`, `status`, `year_established`, `created_at`, `updated_at`) VALUES
(1, 'หลักสูตรบริหารธุรกิจบัณฑิต สาขาวิชาการจัดการธุรกิจ', 120, 4, 1, 'active', 2023, '2025-03-07 13:22:07', '2025-03-07 13:22:07'),
(2, 'หลักสูตรบริหารธุรกิจบัณฑิต สาขาวิชาการจัดการทรัพยา', 120, 4, 1, 'active', 2023, '2025-03-07 13:22:07', '2025-03-07 13:22:07'),
(3, 'หลักสูตรบริหารธุรกิจบัณฑิต สาขาวิชาการตลาดดิจิทัล', 120, 4, 2, 'active', 2023, '2025-03-07 13:22:07', '2025-03-07 13:22:07'),
(4, 'หลักสูตรบัญชีบัณฑิต', 129, 4, 3, 'active', 2023, '2025-03-07 13:22:07', '2025-03-07 13:22:07'),
(5, 'หลักสูตรบริหารธุรกิจบัณฑิต สาขาวิชาการเงิน', 126, 4, 4, 'active', 2023, '2025-03-07 13:22:07', '2025-03-07 13:22:07'),
(6, 'หลักสูตรวิทยาศาสตรบัณฑิต สาขาวิชาวิศวกรรมซอฟต์แวร์', 132, 4, 5, 'active', 2023, '2025-03-07 13:22:07', '2025-03-07 13:22:07'),
(7, 'หลักสูตรวิทยาศาสตรบัณฑิต สาขาวิชาปัญญาประดิษฐ์', 135, 4, 5, 'active', 2023, '2025-03-07 13:22:07', '2025-03-07 13:22:07'),
(8, 'หลักสูตรวิทยาศาสตรบัณฑิต สาขาวิชานวัตกรรมดิจิทัล', 130, 4, 6, 'active', 2023, '2025-03-07 13:22:07', '2025-03-07 13:22:07'),
(9, 'หลักสูตรวิทยาศาสตรบัณฑิต สาขาวิชาธุรกิจดิจิทัล', 126, 4, 6, 'active', 2023, '2025-03-07 13:22:07', '2025-03-07 13:22:07'),
(10, 'หลักสูตรวิทยาศาสตรบัณฑิต สาขาวิชาวิทยาศาสตร์การอาห', 133, 4, 7, 'active', 2023, '2025-03-07 13:22:07', '2025-03-07 13:22:07'),
(11, 'หลักสูตรวิทยาศาสตรบัณฑิต สาขาวิชาคณิตศาสตร์และสถิต', 128, 4, 8, 'active', 2023, '2025-03-07 13:22:07', '2025-03-07 13:22:07'),
(12, 'หลักสูตรศิลปศาสตรบัณฑิต สาขาวิชาภาษาอังกฤษธุรกิจ', 124, 4, 9, 'active', 2023, '2025-03-07 13:22:07', '2025-03-07 13:22:07'),
(13, 'หลักสูตรศิลปศาสตรบัณฑิต สาขาวิชาสังคมศาสตร์', 120, 4, 10, 'active', 2023, '2025-03-07 13:22:07', '2025-03-07 13:22:07'),
(14, 'หลักสูตรนิติศาสตรบัณฑิต', 130, 4, 11, 'active', 2023, '2025-03-07 13:22:07', '2025-03-07 13:22:07'),
(15, 'หลักสูตรครุศาสตรบัณฑิต สาขาวิชาการศึกษาปฐมวัย', 136, 4, 12, 'active', 2023, '2025-03-07 13:22:07', '2025-03-07 13:22:07'),
(16, 'หลักสูตรครุศาสตรบัณฑิต สาขาวิชาการศึกษาพิเศษ', 140, 4, 13, 'active', 2023, '2025-03-07 13:22:07', '2025-03-07 13:22:07'),
(17, 'หลักสูตรศิลปศาสตรบัณฑิต สาขาวิชาการจัดการการท่องเท', 126, 4, 14, 'active', 2023, '2025-03-07 13:22:08', '2025-03-07 13:22:08'),
(18, 'หลักสูตรศิลปศาสตรบัณฑิต สาขาวิชาการจัดการโรงแรม', 128, 4, 15, 'active', 2023, '2025-03-07 13:22:08', '2025-03-07 13:22:08'),
(19, 'หลักสูตรศิลปศาสตรบัณฑิต สาขาวิชาคหกรรมศาสตร์', 124, 4, 16, 'active', 2023, '2025-03-07 13:22:08', '2025-03-07 13:22:08'),
(20, 'หลักสูตรบริหารธุรกิจบัณฑิต สาขาวิชาการจัดการธุรกิจ', 120, 4, 1, 'active', 2023, '2025-03-07 13:22:46', '2025-03-07 13:22:46'),
(21, 'หลักสูตรบริหารธุรกิจบัณฑิต สาขาวิชาการจัดการทรัพยา', 120, 4, 1, 'active', 2023, '2025-03-07 13:22:46', '2025-03-07 13:22:46'),
(22, 'หลักสูตรบริหารธุรกิจบัณฑิต สาขาวิชาการตลาดดิจิทัล', 120, 4, 2, 'active', 2023, '2025-03-07 13:22:46', '2025-03-07 13:22:46'),
(23, 'หลักสูตรบัญชีบัณฑิต', 129, 4, 3, 'active', 2023, '2025-03-07 13:22:46', '2025-03-07 13:22:46'),
(24, 'หลักสูตรบริหารธุรกิจบัณฑิต สาขาวิชาการเงิน', 126, 4, 4, 'active', 2023, '2025-03-07 13:22:46', '2025-03-07 13:22:46'),
(25, 'หลักสูตรวิทยาศาสตรบัณฑิต สาขาวิชาวิศวกรรมซอฟต์แวร์', 132, 4, 5, 'active', 2023, '2025-03-07 13:22:46', '2025-03-07 13:22:46'),
(26, 'หลักสูตรวิทยาศาสตรบัณฑิต สาขาวิชาปัญญาประดิษฐ์', 135, 4, 5, 'active', 2023, '2025-03-07 13:22:46', '2025-03-07 13:22:46'),
(27, 'หลักสูตรวิทยาศาสตรบัณฑิต สาขาวิชานวัตกรรมดิจิทัล', 130, 4, 6, 'active', 2023, '2025-03-07 13:22:46', '2025-03-07 13:22:46'),
(28, 'หลักสูตรวิทยาศาสตรบัณฑิต สาขาวิชาธุรกิจดิจิทัล', 126, 4, 6, 'active', 2023, '2025-03-07 13:22:46', '2025-03-07 13:22:46'),
(29, 'หลักสูตรวิทยาศาสตรบัณฑิต สาขาวิชาวิทยาศาสตร์การอาห', 133, 4, 7, 'active', 2023, '2025-03-07 13:22:46', '2025-03-07 13:22:46'),
(30, 'หลักสูตรวิทยาศาสตรบัณฑิต สาขาวิชาคณิตศาสตร์และสถิต', 128, 4, 8, 'active', 2023, '2025-03-07 13:22:46', '2025-03-07 13:22:46'),
(31, 'หลักสูตรศิลปศาสตรบัณฑิต สาขาวิชาภาษาอังกฤษธุรกิจ', 124, 4, 9, 'active', 2023, '2025-03-07 13:22:46', '2025-03-07 13:22:46'),
(32, 'หลักสูตรศิลปศาสตรบัณฑิต สาขาวิชาสังคมศาสตร์', 120, 4, 10, 'active', 2023, '2025-03-07 13:22:46', '2025-03-07 13:22:46'),
(33, 'หลักสูตรนิติศาสตรบัณฑิต', 130, 4, 11, 'active', 2023, '2025-03-07 13:22:46', '2025-03-07 13:22:46'),
(34, 'หลักสูตรครุศาสตรบัณฑิต สาขาวิชาการศึกษาปฐมวัย', 136, 4, 12, 'active', 2023, '2025-03-07 13:22:46', '2025-03-07 13:22:46'),
(35, 'หลักสูตรครุศาสตรบัณฑิต สาขาวิชาการศึกษาพิเศษ', 140, 4, 13, 'active', 2023, '2025-03-07 13:22:46', '2025-03-07 13:22:46'),
(36, 'หลักสูตรศิลปศาสตรบัณฑิต สาขาวิชาการจัดการการท่องเท', 126, 4, 14, 'active', 2023, '2025-03-07 13:22:46', '2025-03-07 13:22:46'),
(37, 'หลักสูตรศิลปศาสตรบัณฑิต สาขาวิชาการจัดการโรงแรม', 128, 4, 15, 'active', 2023, '2025-03-07 13:22:46', '2025-03-07 13:22:46'),
(38, 'หลักสูตรศิลปศาสตรบัณฑิต สาขาวิชาคหกรรมศาสตร์', 124, 4, 16, 'active', 2023, '2025-03-07 13:22:46', '2025-03-07 13:22:46'),
(39, 'หลักสูตรบริหารธุรกิจบัณฑิต สาขาวิชาการจัดการธุรกิจ', 120, 4, 1, 'active', 2023, '2025-03-07 13:24:17', '2025-03-07 13:24:17'),
(40, 'หลักสูตรบริหารธุรกิจบัณฑิต สาขาวิชาการจัดการทรัพยา', 120, 4, 1, 'active', 2023, '2025-03-07 13:24:17', '2025-03-07 13:24:17'),
(41, 'หลักสูตรบริหารธุรกิจบัณฑิต สาขาวิชาการตลาดดิจิทัล', 120, 4, 2, 'active', 2023, '2025-03-07 13:24:17', '2025-03-07 13:24:17'),
(42, 'หลักสูตรบัญชีบัณฑิต', 129, 4, 3, 'active', 2023, '2025-03-07 13:24:17', '2025-03-07 13:24:17'),
(43, 'หลักสูตรบริหารธุรกิจบัณฑิต สาขาวิชาการเงิน', 126, 4, 4, 'active', 2023, '2025-03-07 13:24:17', '2025-03-07 13:24:17'),
(44, 'หลักสูตรวิทยาศาสตรบัณฑิต สาขาวิชาวิศวกรรมซอฟต์แวร์', 132, 4, 5, 'active', 2023, '2025-03-07 13:24:17', '2025-03-07 13:24:17'),
(45, 'หลักสูตรวิทยาศาสตรบัณฑิต สาขาวิชาปัญญาประดิษฐ์', 135, 4, 5, 'active', 2023, '2025-03-07 13:24:17', '2025-03-07 13:24:17'),
(46, 'หลักสูตรวิทยาศาสตรบัณฑิต สาขาวิชานวัตกรรมดิจิทัล', 130, 4, 6, 'active', 2023, '2025-03-07 13:24:17', '2025-03-07 13:24:17'),
(47, 'หลักสูตรวิทยาศาสตรบัณฑิต สาขาวิชาธุรกิจดิจิทัล', 126, 4, 6, 'active', 2023, '2025-03-07 13:24:17', '2025-03-07 13:24:17'),
(48, 'หลักสูตรวิทยาศาสตรบัณฑิต สาขาวิชาวิทยาศาสตร์การอาห', 133, 4, 7, 'active', 2023, '2025-03-07 13:24:17', '2025-03-07 13:24:17'),
(49, 'หลักสูตรวิทยาศาสตรบัณฑิต สาขาวิชาคณิตศาสตร์และสถิต', 128, 4, 8, 'active', 2023, '2025-03-07 13:24:17', '2025-03-07 13:24:17'),
(50, 'หลักสูตรศิลปศาสตรบัณฑิต สาขาวิชาภาษาอังกฤษธุรกิจ', 124, 4, 9, 'active', 2023, '2025-03-07 13:24:17', '2025-03-07 13:24:17'),
(51, 'หลักสูตรศิลปศาสตรบัณฑิต สาขาวิชาสังคมศาสตร์', 120, 4, 10, 'active', 2023, '2025-03-07 13:24:17', '2025-03-07 13:24:17'),
(52, 'หลักสูตรนิติศาสตรบัณฑิต', 130, 4, 11, 'active', 2023, '2025-03-07 13:24:17', '2025-03-07 13:24:17'),
(53, 'หลักสูตรครุศาสตรบัณฑิต สาขาวิชาการศึกษาปฐมวัย', 136, 4, 12, 'active', 2023, '2025-03-07 13:24:17', '2025-03-07 13:24:17'),
(54, 'หลักสูตรครุศาสตรบัณฑิต สาขาวิชาการศึกษาพิเศษ', 140, 4, 13, 'active', 2023, '2025-03-07 13:24:17', '2025-03-07 13:24:17'),
(55, 'หลักสูตรศิลปศาสตรบัณฑิต สาขาวิชาการจัดการการท่องเท', 126, 4, 14, 'active', 2023, '2025-03-07 13:24:17', '2025-03-07 13:24:17'),
(56, 'หลักสูตรศิลปศาสตรบัณฑิต สาขาวิชาการจัดการโรงแรม', 128, 4, 15, 'active', 2023, '2025-03-07 13:24:17', '2025-03-07 13:24:17'),
(57, 'หลักสูตรศิลปศาสตรบัณฑิต สาขาวิชาคหกรรมศาสตร์', 124, 4, 16, 'active', 2023, '2025-03-07 13:24:17', '2025-03-07 13:24:17'),
(58, 'หลักสูตรบริหารธุรกิจบัณฑิต สาขาวิชาการจัดการธุรกิจ', 120, 4, 1, 'active', 2023, '2025-03-07 13:26:57', '2025-03-07 13:26:57'),
(59, 'หลักสูตรบริหารธุรกิจบัณฑิต สาขาวิชาการจัดการทรัพยา', 120, 4, 1, 'active', 2023, '2025-03-07 13:26:57', '2025-03-07 13:26:57'),
(60, 'หลักสูตรบริหารธุรกิจบัณฑิต สาขาวิชาการตลาดดิจิทัล', 120, 4, 2, 'active', 2023, '2025-03-07 13:26:57', '2025-03-07 13:26:57'),
(61, 'หลักสูตรบัญชีบัณฑิต', 129, 4, 3, 'active', 2023, '2025-03-07 13:26:57', '2025-03-07 13:26:57'),
(62, 'หลักสูตรบริหารธุรกิจบัณฑิต สาขาวิชาการเงิน', 126, 4, 4, 'active', 2023, '2025-03-07 13:26:57', '2025-03-07 13:26:57'),
(63, 'หลักสูตรวิทยาศาสตรบัณฑิต สาขาวิชาวิศวกรรมซอฟต์แวร์', 132, 4, 5, 'active', 2023, '2025-03-07 13:26:57', '2025-03-07 13:26:57'),
(64, 'หลักสูตรวิทยาศาสตรบัณฑิต สาขาวิชาปัญญาประดิษฐ์', 135, 4, 5, 'active', 2023, '2025-03-07 13:26:57', '2025-03-07 13:26:57'),
(65, 'หลักสูตรวิทยาศาสตรบัณฑิต สาขาวิชานวัตกรรมดิจิทัล', 130, 4, 6, 'active', 2023, '2025-03-07 13:26:57', '2025-03-07 13:26:57'),
(66, 'หลักสูตรวิทยาศาสตรบัณฑิต สาขาวิชาธุรกิจดิจิทัล', 126, 4, 6, 'active', 2023, '2025-03-07 13:26:57', '2025-03-07 13:26:57'),
(67, 'หลักสูตรวิทยาศาสตรบัณฑิต สาขาวิชาวิทยาศาสตร์การอาห', 133, 4, 7, 'active', 2023, '2025-03-07 13:26:57', '2025-03-07 13:26:57'),
(68, 'หลักสูตรวิทยาศาสตรบัณฑิต สาขาวิชาคณิตศาสตร์และสถิต', 128, 4, 8, 'active', 2023, '2025-03-07 13:26:57', '2025-03-07 13:26:57'),
(69, 'หลักสูตรศิลปศาสตรบัณฑิต สาขาวิชาภาษาอังกฤษธุรกิจ', 124, 4, 9, 'active', 2023, '2025-03-07 13:26:57', '2025-03-07 13:26:57'),
(70, 'หลักสูตรศิลปศาสตรบัณฑิต สาขาวิชาสังคมศาสตร์', 120, 4, 10, 'active', 2023, '2025-03-07 13:26:57', '2025-03-07 13:26:57'),
(71, 'หลักสูตรนิติศาสตรบัณฑิต', 130, 4, 11, 'active', 2023, '2025-03-07 13:26:57', '2025-03-07 13:26:57'),
(72, 'หลักสูตรครุศาสตรบัณฑิต สาขาวิชาการศึกษาปฐมวัย', 136, 4, 12, 'active', 2023, '2025-03-07 13:26:57', '2025-03-07 13:26:57'),
(73, 'หลักสูตรครุศาสตรบัณฑิต สาขาวิชาการศึกษาพิเศษ', 140, 4, 13, 'active', 2023, '2025-03-07 13:26:57', '2025-03-07 13:26:57'),
(74, 'หลักสูตรศิลปศาสตรบัณฑิต สาขาวิชาการจัดการการท่องเท', 126, 4, 14, 'active', 2023, '2025-03-07 13:26:57', '2025-03-07 13:26:57'),
(75, 'หลักสูตรศิลปศาสตรบัณฑิต สาขาวิชาการจัดการโรงแรม', 128, 4, 15, 'active', 2023, '2025-03-07 13:26:57', '2025-03-07 13:26:57'),
(76, 'หลักสูตรศิลปศาสตรบัณฑิต สาขาวิชาคหกรรมศาสตร์', 124, 4, 16, 'active', 2023, '2025-03-07 13:26:57', '2025-03-07 13:26:57'),
(77, 'หลักสูตรวิชาเสรี', 6, 4, 1, 'active', 2023, '2025-03-10 13:37:32', '2025-03-10 13:37:32');

-- --------------------------------------------------------

--
-- Table structure for table `department`
--

CREATE TABLE `department` (
  `department_id` int(11) NOT NULL,
  `faculty_id` int(11) NOT NULL,
  `department_code` varchar(20) NOT NULL,
  `department_name` varchar(100) NOT NULL,
  `thai_department_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `head_of_department` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `department`
--

INSERT INTO `department` (`department_id`, `faculty_id`, `department_code`, `department_name`, `thai_department_name`, `description`, `head_of_department`, `created_at`, `updated_at`) VALUES
(1, 1, 'MGT', 'Department of Management', 'ภาควิชาการจัดการ', 'ภาควิชาการจัดการเน้นการเรียนการสอนด้านการบริหารและการจัดการองค์กร', NULL, '2025-03-07 13:21:32', '2025-03-07 13:21:32'),
(2, 1, 'MKT', 'Department of Marketing', 'ภาควิชาการตลาด', 'ภาควิชาการตลาดเน้นการเรียนการสอนด้านการตลาด กลยุทธ์การตลาด และการขาย', NULL, '2025-03-07 13:21:32', '2025-03-07 13:21:32'),
(3, 1, 'ACC', 'Department of Accounting', 'ภาควิชาการบัญชี', 'ภาควิชาการบัญชีเน้นการเรียนการสอนด้านการบัญชีและการตรวจสอบบัญชี', NULL, '2025-03-07 13:21:32', '2025-03-07 13:21:32'),
(4, 1, 'FIN', 'Department of Finance', 'ภาควิชาการเงิน', 'ภาควิชาการเงินเน้นการเรียนการสอนด้านการเงินและการธนาคาร', NULL, '2025-03-07 13:21:32', '2025-03-07 13:21:32'),
(5, 2, 'CS', 'Department of Computer Science', 'ภาควิชาวิทยาการคอมพิวเตอร์', 'ภาควิชาวิทยาการคอมพิวเตอร์เน้นการเรียนการสอนด้านคอมพิวเตอร์และการเขียนโปรแกรม', NULL, '2025-03-07 13:21:32', '2025-03-07 13:21:32'),
(6, 2, 'IT', 'Department of Information Technology', 'ภาควิชาเทคโนโลยีสารสนเทศ', 'ภาควิชาเทคโนโลยีสารสนเทศเน้นการเรียนการสอนด้านการจัดการเทคโนโลยีสารสนเทศ', NULL, '2025-03-07 13:21:32', '2025-03-07 13:21:32'),
(7, 2, 'FSN', 'Department of Food Science and Nutrition', 'ภาควิชาวิทยาศาสตร์การอาหารและโภชนาการ', 'ภาควิชาวิทยาศาสตร์การอาหารและโภชนาการเน้นการเรียนการสอนด้านอาหารและโภชนาการ', NULL, '2025-03-07 13:21:32', '2025-03-07 13:21:32'),
(8, 2, 'MATH', 'Department of Mathematics and Statistics', 'ภาควิชาคณิตศาสตร์และสถิติ', 'ภาควิชาคณิตศาสตร์และสถิติเน้นการเรียนการสอนด้านคณิตศาสตร์และสถิติ', NULL, '2025-03-07 13:21:32', '2025-03-07 13:21:32'),
(9, 3, 'ENG', 'Department of English', 'ภาควิชาภาษาอังกฤษ', 'ภาควิชาภาษาอังกฤษเน้นการเรียนการสอนด้านภาษาอังกฤษและวรรณคดีอังกฤษ', NULL, '2025-03-07 13:21:32', '2025-03-07 13:21:32'),
(10, 3, 'SOC', 'Department of Social Sciences', 'ภาควิชาสังคมศาสตร์', 'ภาควิชาสังคมศาสตร์เน้นการเรียนการสอนด้านสังคมวิทยาและมานุษยวิทยา', NULL, '2025-03-07 13:21:32', '2025-03-07 13:21:32'),
(11, 3, 'LAW', 'Department of Law', 'ภาควิชานิติศาสตร์', 'ภาควิชานิติศาสตร์เน้นการเรียนการสอนด้านกฎหมายและนิติศาสตร์', NULL, '2025-03-07 13:21:32', '2025-03-07 13:21:32'),
(12, 4, 'ELEM', 'Department of Elementary Education', 'ภาควิชาการศึกษาปฐมวัย', 'ภาควิชาการศึกษาปฐมวัยเน้นการเรียนการสอนด้านการศึกษาสำหรับเด็กปฐมวัย', NULL, '2025-03-07 13:21:32', '2025-03-07 13:21:32'),
(13, 4, 'SPED', 'Department of Special Education', 'ภาควิชาการศึกษาพิเศษ', 'ภาควิชาการศึกษาพิเศษเน้นการเรียนการสอนด้านการศึกษาสำหรับเด็กที่มีความต้องการพิเศษ', NULL, '2025-03-07 13:21:32', '2025-03-07 13:21:32'),
(14, 5, 'TOUR', 'Department of Tourism Management', 'ภาควิชาการท่องเที่ยว', 'ภาควิชาการท่องเที่ยวเน้นการเรียนการสอนด้านการจัดการการท่องเที่ยว', NULL, '2025-03-07 13:21:32', '2025-03-07 13:21:32'),
(15, 5, 'HTM', 'Department of Hotel Management', 'ภาควิชาการโรงแรม', 'ภาควิชาการโรงแรมเน้นการเรียนการสอนด้านการจัดการโรงแรม', NULL, '2025-03-07 13:21:32', '2025-03-07 13:21:32'),
(16, 5, 'HECO', 'Department of Home Economics', 'ภาควิชาคหกรรมศาสตร์', 'ภาควิชาคหกรรมศาสตร์เน้นการเรียนการสอนด้านคหกรรมศาสตร์และเคหะการ', NULL, '2025-03-07 13:21:32', '2025-03-07 13:21:32');

-- --------------------------------------------------------

--
-- Table structure for table `faculty`
--

CREATE TABLE `faculty` (
  `id` int(11) NOT NULL,
  `faculty_name` varchar(255) NOT NULL,
  `thai_faculty_name` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `dean` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `faculty`
--

INSERT INTO `faculty` (`id`, `faculty_name`, `thai_faculty_name`, `description`, `dean`, `created_at`, `updated_at`) VALUES
(1, 'Management Science', 'คณะวิทยาการจัดการ', 'คณะวิทยาการจัดการมุ่งผลิตบัณฑิตที่มีความรู้ความสามารถในการบริหารจัดการ', 'ดร.สุรพล ศิริเศรษฐ', '2025-03-07 13:21:32', '2025-03-07 13:21:32'),
(2, 'Science and Technology', 'คณะวิทยาศาสตร์และเทคโนโลยี', 'คณะวิทยาศาสตร์และเทคโนโลยีมุ่งผลิตบัณฑิตที่มีความรู้ความสามารถด้านวิทยาศาสตร์และเทคโนโลยี', 'ดร.วิชชา ฉิมพลี', '2025-03-07 13:21:32', '2025-03-07 13:21:32'),
(3, 'Humanities and Social Sciences', 'คณะมนุษยศาสตร์และสังคมศาสตร์', 'คณะมนุษยศาสตร์และสังคมศาสตร์มุ่งผลิตบัณฑิตที่มีความรู้ความสามารถทางด้านภาษาและสังคม', 'ดร.สุวมาลย์ ม่วงประเสริฐ', '2025-03-07 13:21:32', '2025-03-07 13:21:32'),
(4, 'Education', 'คณะครุศาสตร์', 'คณะครุศาสตร์มุ่งผลิตบัณฑิตที่มีความรู้ความสามารถในการสอนและการศึกษา', 'ดร.พัชรี ชูเชิด', '2025-03-07 13:21:32', '2025-03-07 13:21:32'),
(5, 'School of Tourism and Hospitality Management', 'โรงเรียนการท่องเที่ยวและการบริการ', 'โรงเรียนการท่องเที่ยวและการบริการมุ่งผลิตบัณฑิตที่มีความรู้ความสามารถด้านการท่องเที่ยวและการบริการ', 'ดร.พรรณี สวนเพลง', '2025-03-07 13:21:32', '2025-03-07 13:21:32');

-- --------------------------------------------------------

--
-- Table structure for table `logs`
--

CREATE TABLE `logs` (
  `log_id` int(11) NOT NULL,
  `id_account` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `logs`
--

INSERT INTO `logs` (`log_id`, `id_account`, `action`, `details`, `ip_address`, `created_at`) VALUES
(1, 1, 'account_created', 'New account created with role: student', NULL, '2025-03-03 21:21:51'),
(2, 2, 'account_created', 'New account created with role: academic', NULL, '2025-03-03 21:31:09'),
(3, 2, 'login', 'User logged in', '::1', '2025-03-03 21:31:19'),
(4, 2, 'login', 'User logged in', '::1', '2025-03-03 21:31:46'),
(5, 2, 'login', 'User logged in', '::1', '2025-03-03 21:31:51'),
(6, 2, 'login', 'User logged in', '::1', '2025-03-03 21:34:05'),
(7, 2, 'login', 'User logged in', '::1', '2025-03-03 21:34:07'),
(8, 2, 'login', 'User logged in', '::1', '2025-03-03 21:34:22'),
(9, 2, 'login', 'User logged in', '::1', '2025-03-03 21:34:37'),
(10, 2, 'login', 'User logged in', '::1', '2025-03-03 21:34:39'),
(11, 2, 'login', 'User logged in', '::1', '2025-03-03 21:36:20'),
(12, 2, 'logout', 'User logged out', '::1', '2025-03-03 21:42:37'),
(13, 2, 'login', 'User logged in', '::1', '2025-03-03 21:42:48'),
(14, 3, 'account_created', 'New account created with role: academic', NULL, '2025-03-03 21:43:15'),
(15, 3, 'login', 'User logged in', '::1', '2025-03-03 21:43:19'),
(16, 2, 'login', 'User logged in', '::1', '2025-03-03 21:49:12'),
(17, 2, 'login', 'User logged in', '::1', '2025-03-03 21:49:16'),
(18, 2, 'login', 'User logged in', '::1', '2025-03-03 21:53:09'),
(19, 2, 'login', 'User logged in', '::1', '2025-03-03 21:54:17'),
(20, 2, 'login', 'User logged in', '::1', '2025-03-03 21:54:28'),
(21, 2, 'login', 'User logged in', '::1', '2025-03-03 21:55:08'),
(22, 4, 'account_created', 'New account created with role: academic', NULL, '2025-03-03 22:08:11'),
(23, 5, 'account_created', 'New account created with role: academic', NULL, '2025-03-03 22:10:22'),
(24, 5, 'login', 'User logged in', '::1', '2025-03-03 22:10:32'),
(25, 5, 'logout', 'User logged out', '::1', '2025-03-03 22:16:10'),
(26, 6, 'account_created', 'New account created with role: student', NULL, '2025-03-03 22:16:31'),
(27, 6, 'login', 'User logged in', '::1', '2025-03-03 22:16:42'),
(28, 6, 'login', 'User logged in', '::1', '2025-03-03 22:26:39'),
(29, 2, 'login', 'User logged in', '::1', '2025-03-03 22:27:38'),
(30, 2, 'logout', 'User logged out', '::1', '2025-03-03 22:34:47'),
(31, 6, 'login', 'User logged in', '::1', '2025-03-03 22:34:56'),
(32, 6, 'logout', 'User logged out', '::1', '2025-03-03 23:01:56'),
(33, 2, 'login', 'User logged in', '::1', '2025-03-03 23:02:12'),
(34, 7, 'account_created', 'New account created with role: student', NULL, '2025-03-06 14:38:43'),
(35, 7, 'login', 'User logged in', '::1', '2025-03-06 14:38:55'),
(36, 7, 'logout', 'User logged out', '::1', '2025-03-06 14:39:16'),
(37, 7, 'login', 'User logged in', '::1', '2025-03-06 14:40:53'),
(38, 7, 'logout', 'User logged out', '::1', '2025-03-06 14:42:10'),
(39, 7, 'login', 'User logged in', '::1', '2025-03-06 14:42:17'),
(40, 7, 'logout', 'User logged out', '::1', '2025-03-06 14:47:18'),
(41, 8, 'account_created', 'New account created with role: student', NULL, '2025-03-06 14:48:14'),
(42, 8, 'login', 'User logged in', '::1', '2025-03-06 14:48:21'),
(43, 7, 'login', 'User logged in', '::1', '2025-03-07 13:15:49'),
(44, 7, 'profile_update', 'User completed profile information', '::1', '2025-03-07 13:35:33'),
(45, 7, 'logout', 'User logged out', '::1', '2025-03-07 13:52:26'),
(46, 9, 'account_created', 'New account created with role: academic', NULL, '2025-03-07 13:52:53'),
(47, 9, 'login', 'User logged in', '::1', '2025-03-07 13:53:00'),
(48, 9, 'profile_update', 'User completed profile information', '::1', '2025-03-07 13:54:11'),
(49, 10, 'account_created', 'New account created with role: academic', NULL, '2025-03-08 13:42:12'),
(50, 10, 'login', 'User logged in', '::1', '2025-03-08 13:42:21'),
(51, 10, 'profile_update', 'User completed profile information', '::1', '2025-03-08 13:43:11'),
(52, 10, 'logout', 'User logged out', '::1', '2025-03-08 13:43:51'),
(53, 11, 'account_created', 'New account created with role: student', NULL, '2025-03-08 13:44:25'),
(54, 11, 'login', 'User logged in', '::1', '2025-03-08 13:44:36'),
(55, 11, 'profile_update', 'User completed profile information', '::1', '2025-03-08 13:45:36'),
(56, 11, 'logout', 'User logged out', '::1', '2025-03-08 13:46:06'),
(57, 10, 'login', 'User logged in', '::1', '2025-03-08 13:46:17'),
(58, 10, 'logout', 'User logged out', '::1', '2025-03-08 13:53:13'),
(59, 7, 'login', 'User logged in', '::1', '2025-03-10 12:37:25'),
(60, 7, 'logout', 'User logged out', '::1', '2025-03-10 12:37:34'),
(61, 10, 'login', 'User logged in', '::1', '2025-03-10 12:38:02'),
(62, 10, 'logout', 'User logged out', '::1', '2025-03-10 14:51:12'),
(63, 10, 'login', 'User logged in', '::1', '2025-03-10 14:51:31'),
(64, 10, 'logout', 'User logged out', '::1', '2025-03-10 14:58:45'),
(65, 11, 'login', 'User logged in', '::1', '2025-03-10 14:58:52'),
(66, 11, 'logout', 'User logged out', '::1', '2025-03-10 14:59:55'),
(67, 12, 'account_created', 'New account created with role: student', NULL, '2025-03-10 15:00:13'),
(68, 12, 'login', 'User logged in', '::1', '2025-03-10 15:00:24'),
(69, 12, 'profile_update', 'User completed profile information', '::1', '2025-03-10 15:03:14'),
(70, 12, 'logout', 'User logged out', '::1', '2025-03-10 15:03:51'),
(71, 10, 'login', 'User logged in', '::1', '2025-03-10 15:04:08'),
(72, 10, 'logout', 'User logged out', '::1', '2025-03-10 15:27:57'),
(73, 11, 'login', 'User logged in', '::1', '2025-03-10 15:28:06'),
(74, 11, 'logout', 'User logged out', '::1', '2025-03-10 15:36:27'),
(75, 10, 'login', 'User logged in', '::1', '2025-03-10 15:36:37'),
(76, 10, 'logout', 'User logged out', '::1', '2025-03-10 15:47:08'),
(77, 13, 'account_created', 'New account created with role: teacher', NULL, '2025-03-10 15:47:27'),
(78, 13, 'login', 'User logged in', '::1', '2025-03-10 15:47:31'),
(79, 13, 'profile_update', 'User completed profile information', '::1', '2025-03-10 15:49:48'),
(80, 10, 'login', 'User logged in', '::1', '2025-03-10 15:50:14'),
(81, 11, 'login', 'User logged in', '::1', '2025-03-10 22:52:33'),
(82, 11, 'logout', 'User logged out', '::1', '2025-03-10 23:42:27'),
(83, 10, 'login', 'User logged in', '::1', '2025-03-10 23:42:34'),
(84, 10, 'logout', 'User logged out', '::1', '2025-03-10 23:43:51'),
(85, 11, 'login', 'User logged in', '::1', '2025-03-10 23:43:56');

-- --------------------------------------------------------

--
-- Table structure for table `major`
--

CREATE TABLE `major` (
  `major_id` int(11) NOT NULL,
  `department_id` int(11) NOT NULL,
  `program_id` int(11) DEFAULT NULL,
  `major_code` varchar(20) NOT NULL,
  `major_name` varchar(100) NOT NULL,
  `thai_major_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `Curriculum_ID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `major`
--

INSERT INTO `major` (`major_id`, `department_id`, `program_id`, `major_code`, `major_name`, `thai_major_name`, `description`, `created_at`, `updated_at`, `Curriculum_ID`) VALUES
(1, 5, 1, 'CS-SE', 'Software Engineering', 'วิศวกรรมซอฟต์แวร์', 'สาขาวิชาวิศวกรรมซอฟต์แวร์เน้นการเรียนการสอนด้านการพัฒนาซอฟต์แวร์และการจัดการโครงการซอฟต์แวร์', '2025-03-07 13:21:33', '2025-03-07 13:21:33', NULL),
(2, 5, 1, 'CS-AI', 'Artificial Intelligence', 'ปัญญาประดิษฐ์', 'สาขาวิชาปัญญาประดิษฐ์เน้นการเรียนการสอนด้านการพัฒนาระบบปัญญาประดิษฐ์และการเรียนรู้ของเครื่อง', '2025-03-07 13:21:33', '2025-03-07 13:21:33', NULL),
(3, 6, 1, 'IT-DI', 'Digital Innovation', 'นวัตกรรมดิจิทัล', 'สาขาวิชานวัตกรรมดิจิทัลเน้นการเรียนการสอนด้านการสร้างนวัตกรรมดิจิทัลและการประยุกต์ใช้เทคโนโลยีดิจิทัล', '2025-03-07 13:21:33', '2025-03-07 13:21:33', NULL),
(4, 6, 1, 'IT-DB', 'Digital Business', 'ธุรกิจดิจิทัล', 'สาขาวิชาธุรกิจดิจิทัลเน้นการเรียนการสอนด้านการประยุกต์ใช้เทคโนโลยีดิจิทัลในการดำเนินธุรกิจ', '2025-03-07 13:21:33', '2025-03-07 13:21:33', NULL),
(5, 1, 1, 'MGT-BM', 'Business Management', 'การจัดการธุรกิจ', 'สาขาวิชาการจัดการธุรกิจเน้นการเรียนการสอนด้านการบริหารจัดการธุรกิจและองค์กร', '2025-03-07 13:21:33', '2025-03-07 13:21:33', NULL),
(6, 1, 1, 'MGT-HRM', 'Human Resource Management', 'การจัดการทรัพยากรมนุษย์', 'สาขาวิชาการจัดการทรัพยากรมนุษย์เน้นการเรียนการสอนด้านการบริหารจัดการทรัพยากรมนุษย์ในองค์กร', '2025-03-07 13:21:33', '2025-03-07 13:21:33', NULL),
(7, 2, 1, 'MKT-DM', 'Digital Marketing', 'การตลาดดิจิทัล', 'สาขาวิชาการตลาดดิจิทัลเน้นการเรียนการสอนด้านการตลาดในยุคดิจิทัลและการใช้เทคโนโลยีในการทำการตลาด', '2025-03-07 13:21:33', '2025-03-07 13:21:33', NULL),
(8, 12, 1, 'ELEM-ECE', 'Early Childhood Education', 'การศึกษาปฐมวัย', 'สาขาวิชาการศึกษาปฐมวัยเน้นการเรียนการสอนด้านการพัฒนาเด็กปฐมวัยและการจัดการเรียนรู้สำหรับเด็กปฐมวัย', '2025-03-07 13:21:33', '2025-03-07 13:21:33', NULL),
(9, 14, 1, 'TOUR-TM', 'Tourism Management', 'การจัดการการท่องเที่ยว', 'สาขาวิชาการจัดการการท่องเที่ยวเน้นการเรียนการสอนด้านการจัดการการท่องเที่ยวและอุตสาหกรรมการท่องเที่ยว', '2025-03-07 13:21:33', '2025-03-07 13:21:33', NULL),
(10, 15, 1, 'HTM-HM', 'Hotel Management', 'การจัดการโรงแรม', 'สาขาวิชาการจัดการโรงแรมเน้นการเรียนการสอนด้านการจัดการโรงแรมและการบริการ', '2025-03-07 13:21:33', '2025-03-07 13:21:33', NULL),
(11, 9, 1, 'ENG-BUS', 'Business English', 'ภาษาอังกฤษธุรกิจ', 'สาขาวิชาภาษาอังกฤษธุรกิจเน้นการเรียนการสอนด้านภาษาอังกฤษเพื่อการสื่อสารทางธุรกิจ', '2025-03-07 13:21:33', '2025-03-07 13:21:33', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `major_courses`
--

CREATE TABLE `major_courses` (
  `major_course_id` int(11) NOT NULL,
  `major_id` int(11) NOT NULL,
  `Course_Code` int(11) NOT NULL,
  `semester_number` int(1) NOT NULL,
  `study_year` int(1) NOT NULL,
  `is_required` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `programs`
--

CREATE TABLE `programs` (
  `program_id` int(11) NOT NULL,
  `program_code` varchar(10) NOT NULL,
  `program_name` varchar(100) NOT NULL,
  `thai_program_name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `programs`
--

INSERT INTO `programs` (`program_id`, `program_code`, `program_name`, `thai_program_name`, `created_at`, `updated_at`) VALUES
(1, 'bachelor', 'Bachelor degree', 'ปริญญาตรี', '2025-03-03 14:41:11', '2025-03-03 14:41:11'),
(2, 'master', 'Master degree', 'ปริญญาโท', '2025-03-03 14:41:11', '2025-03-03 14:41:11'),
(3, 'phd', 'Doctoral degree', 'ปริญญาเอก', '2025-03-03 14:41:11', '2025-03-03 14:41:11');

-- --------------------------------------------------------

--
-- Table structure for table `rooms`
--

CREATE TABLE `rooms` (
  `room_id` int(11) NOT NULL,
  `building_id` int(11) NOT NULL,
  `room_code` varchar(20) NOT NULL,
  `room_name` varchar(100) DEFAULT NULL,
  `thai_room_name` varchar(100) DEFAULT NULL,
  `room_type` enum('lecture','lab','online','other') NOT NULL DEFAULT 'lecture',
  `capacity` int(3) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `rooms`
--

INSERT INTO `rooms` (`room_id`, `building_id`, `room_code`, `room_name`, `thai_room_name`, `room_type`, `capacity`, `created_at`, `updated_at`) VALUES
(1, 1, '101', 'Room 101', 'ห้อง 101', 'lecture', 40, '2025-03-03 14:41:11', '2025-03-03 14:41:11'),
(2, 1, '102', 'Room 102', 'ห้อง 102', 'lecture', 40, '2025-03-03 14:41:11', '2025-03-03 14:41:11'),
(3, 2, '11204', 'Computer Lab 11204', 'ห้องปฏิบัติการคอมพิวเตอร์ 11204', 'lab', 30, '2025-03-03 14:41:11', '2025-03-03 14:41:11'),
(4, 2, '11305', 'Computer Lab 11305', 'ห้องปฏิบัติการคอมพิวเตอร์ 11305', 'lab', 30, '2025-03-03 14:41:11', '2025-03-03 14:41:11'),
(5, 5, 'MS Teams', 'Microsoft Teams', 'ไมโครซอฟท์ทีมส์', 'online', 100, '2025-03-03 14:41:11', '2025-03-03 14:41:11'),
(6, 5, 'Zoom', 'Zoom Meeting', 'ซูมมีตติ้ง', 'online', 100, '2025-03-03 14:41:11', '2025-03-03 14:41:11');

-- --------------------------------------------------------

--
-- Table structure for table `semesters`
--

CREATE TABLE `semesters` (
  `semester_id` int(11) NOT NULL,
  `academic_year_id` int(11) NOT NULL,
  `semester_number` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `thai_name` varchar(100) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `is_current` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `semesters`
--

INSERT INTO `semesters` (`semester_id`, `academic_year_id`, `semester_number`, `name`, `thai_name`, `start_date`, `end_date`, `is_current`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'First Semester', 'ภาคเรียนที่ 1', '2024-06-01', '2024-10-31', 1, '2025-03-03 14:41:11', '2025-03-03 14:41:11'),
(2, 1, 2, 'Second Semester', 'ภาคเรียนที่ 2', '2024-11-01', '2025-03-31', 0, '2025-03-03 14:41:11', '2025-03-03 14:41:11'),
(3, 1, 3, 'Summer', 'ภาคฤดูร้อน', '2025-04-01', '2025-05-31', 0, '2025-03-03 14:41:11', '2025-03-03 14:41:11');

-- --------------------------------------------------------

--
-- Table structure for table `student_details`
--

CREATE TABLE `student_details` (
  `student_detail_id` int(11) NOT NULL,
  `id_account` int(11) NOT NULL,
  `student_code` varchar(20) NOT NULL,
  `major_id` int(11) DEFAULT NULL,
  `Curriculum_ID` int(11) DEFAULT NULL,
  `entry_year` int(4) NOT NULL,
  `entry_semester` int(1) NOT NULL DEFAULT 1,
  `study_year` int(1) NOT NULL,
  `enrollment_date` date DEFAULT NULL,
  `status` enum('active','graduated','leave_of_absence','dismissed') NOT NULL DEFAULT 'active',
  `academic_status` enum('normal','probation','honors') NOT NULL DEFAULT 'normal',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `student_details`
--

INSERT INTO `student_details` (`student_detail_id`, `id_account`, `student_code`, `major_id`, `Curriculum_ID`, `entry_year`, `entry_semester`, `study_year`, `enrollment_date`, `status`, `academic_status`, `created_at`, `updated_at`) VALUES
(1, 1, '202537602', NULL, NULL, 2025, 1, 1, NULL, 'active', 'normal', '2025-03-03 21:21:51', '2025-03-03 21:21:51'),
(2, 6, '202516318', NULL, NULL, 2025, 1, 1, NULL, 'active', 'normal', '2025-03-03 22:16:31', '2025-03-03 22:16:31'),
(3, 7, '202556229', 8, NULL, 2025, 1, 1, NULL, 'active', 'normal', '2025-03-06 14:38:43', '2025-03-07 13:35:33'),
(4, 8, '202561964', NULL, NULL, 2025, 1, 1, NULL, 'active', 'normal', '2025-03-06 14:48:14', '2025-03-06 14:48:14'),
(5, 11, '202505774', 4, NULL, 2025, 1, 1, NULL, 'active', 'normal', '2025-03-08 13:44:25', '2025-03-08 13:45:36'),
(6, 12, '202526403', 4, NULL, 2025, 1, 1, NULL, 'active', 'normal', '2025-03-10 15:00:13', '2025-03-10 15:03:14');

-- --------------------------------------------------------

--
-- Table structure for table `teacher_details`
--

CREATE TABLE `teacher_details` (
  `teacher_detail_id` int(11) NOT NULL,
  `id_account` int(11) NOT NULL,
  `teacher_code` varchar(20) NOT NULL,
  `position` varchar(100) DEFAULT NULL,
  `expertise` text DEFAULT NULL,
  `office_location` varchar(100) DEFAULT NULL,
  `office_hours` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `teacher_details`
--

INSERT INTO `teacher_details` (`teacher_detail_id`, `id_account`, `teacher_code`, `position`, `expertise`, `office_location`, `office_hours`, `created_at`, `updated_at`) VALUES
(1, 13, 'T2025905', 'อาหาร 11', 'เทสสสสสสสสส', 'ห้อง', 'จัทร์ - ศุกร์ 9.00-12.00', '2025-03-10 15:47:27', '2025-03-10 15:49:48');

-- --------------------------------------------------------

--
-- Table structure for table `toeic`
--

CREATE TABLE `toeic` (
  `TOEIC_ID` int(10) NOT NULL,
  `Student_ID` varchar(20) NOT NULL,
  `Pre_Test_Score` int(3) NOT NULL,
  `Registration_Status` varchar(10) NOT NULL,
  `TOEIC_Score` int(3) NOT NULL,
  `Test_Date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `Pre_Test_Document` varchar(255) DEFAULT NULL,
  `Post_Training1_Score` int(3) DEFAULT NULL,
  `Post_Training1_Document` varchar(255) DEFAULT NULL,
  `Post_Training2_Score` int(3) DEFAULT NULL,
  `Post_Training2_Document` varchar(255) DEFAULT NULL,
  `Required_Courses` int(1) DEFAULT 0,
  `Document_Path` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `toeic`
--

INSERT INTO `toeic` (`TOEIC_ID`, `Student_ID`, `Pre_Test_Score`, `Registration_Status`, `TOEIC_Score`, `Test_Date`, `created_at`, `updated_at`, `Pre_Test_Document`, `Post_Training1_Score`, `Post_Training1_Document`, `Post_Training2_Score`, `Post_Training2_Document`, `Required_Courses`, `Document_Path`) VALUES
(1, '202505774', 0, 'completed', 355, '2025-03-11', '2025-03-11 00:00:58', '2025-03-11 00:00:58', NULL, 0, NULL, 0, NULL, 1, 'uploads/toeic/202505774_toeic_1741651258.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `user_profiles`
--

CREATE TABLE `user_profiles` (
  `profile_id` int(11) NOT NULL,
  `id_account` int(11) NOT NULL,
  `first_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `thai_first_name` varchar(50) DEFAULT NULL,
  `thai_last_name` varchar(50) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `faculty_id` int(11) DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `user_profiles`
--

INSERT INTO `user_profiles` (`profile_id`, `id_account`, `first_name`, `last_name`, `thai_first_name`, `thai_last_name`, `phone`, `address`, `faculty_id`, `department_id`, `profile_image`, `created_at`, `updated_at`) VALUES
(1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-03-03 21:21:51', '2025-03-03 21:21:51'),
(2, 2, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-03-03 21:31:09', '2025-03-03 21:31:09'),
(3, 3, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-03-03 21:43:15', '2025-03-03 21:43:15'),
(4, 4, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-03-03 22:08:11', '2025-03-03 22:08:11'),
(5, 5, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-03-03 22:10:22', '2025-03-03 22:10:22'),
(6, 6, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-03-03 22:16:31', '2025-03-03 22:16:31'),
(7, 7, 'Test', 'Test', 'เทส', 'เทส', '0123456789', 'fdffsadasfwadasd', 4, 12, 'uploads/profiles/7_1741354533_S__11911240.jpg', '2025-03-06 14:38:43', '2025-03-07 13:35:33'),
(8, 8, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-03-06 14:48:14', '2025-03-06 14:48:14'),
(9, 9, 'Testtc', 'Testtc', 'เทส..', 'เทส..', '1234567890', 'eewadsadsad', 2, 6, 'uploads/profiles/9_1741355651_Cafe_Logo-removebg-preview (1).png', '2025-03-07 13:52:53', '2025-03-07 13:54:11'),
(10, 10, 'Thinna', 'gron', 'ทินนา', 'กอน', '1234567891', 'กเดกเฟหกหฟกหฟก', 2, 6, 'uploads/profiles/10_1741441391_Cafe_Logo-removebg-preview (1).png', '2025-03-08 13:42:12', '2025-03-08 13:43:11'),
(11, 11, 'saharat', 'Test', 'สหรัฐ', 'เทส', '1234567892', 'กหฟกฟหดห', 2, 6, 'uploads/profiles/11_1741441536_Cafe_Logo-removebg-preview (1).png', '2025-03-08 13:44:25', '2025-03-08 13:45:36'),
(12, 12, 'dddd', 'dddddd', 'กกกกก', 'กกกกก', '0000000000', 'กหฟกฟหกหฟกหฟ', 2, 6, 'uploads/profiles/12_1741618994_63828048176240.png', '2025-03-10 15:00:13', '2025-03-10 15:03:14'),
(13, 13, 'Teacher', 'Teacher', 'เทส', 'เทส', '1234567890', 'กฟหกฟหกดแฟหดฟหกหฟ', 3, 9, 'uploads/profiles/13_1741621788_1fe27f293bb6f0b0cbe8712d3f3904e8.png', '2025-03-10 15:47:27', '2025-03-10 15:49:48');

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_current_class_schedules`
-- (See below for the actual view)
--
CREATE TABLE `vw_current_class_schedules` (
`Course_Code` int(11)
,`Course_Name` varchar(255)
,`section_number` varchar(5)
,`instructor_name` varchar(200)
,`location` varchar(203)
,`thai_location` varchar(203)
,`day_of_week` enum('monday','tuesday','wednesday','thursday','friday','saturday','sunday')
,`start_time` time
,`end_time` time
,`class_mode` varchar(6)
,`online_meeting_link` varchar(255)
,`semester_name` varchar(100)
,`thai_semester_name` varchar(100)
,`academic_year` varchar(10)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_major_timetable`
-- (See below for the actual view)
--
CREATE TABLE `vw_major_timetable` (
`major_id` int(11)
,`major_code` varchar(20)
,`major_name` varchar(100)
,`thai_major_name` varchar(100)
,`program_name` varchar(100)
,`thai_program_name` varchar(100)
,`department_name` varchar(100)
,`thai_department_name` varchar(100)
,`faculty_name` varchar(255)
,`thai_faculty_name` varchar(255)
,`study_year` int(1)
,`semester_number` int(11)
,`Course_Code` int(11)
,`Course_Name` varchar(255)
,`section_number` varchar(5)
,`instructor_name` varchar(200)
,`location` varchar(203)
,`thai_location` varchar(203)
,`day_of_week` enum('monday','tuesday','wednesday','thursday','friday','saturday','sunday')
,`start_time` time
,`end_time` time
,`class_mode` varchar(6)
,`online_meeting_link` varchar(255)
,`semester_name` varchar(100)
,`thai_semester_name` varchar(100)
,`academic_year` varchar(10)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_student_info`
-- (See below for the actual view)
--
CREATE TABLE `vw_student_info` (
`id_account` int(11)
,`username_account` varchar(40)
,`email_account` varchar(40)
,`Role_account` enum('student','teacher','academic')
,`student_code` varchar(20)
,`first_name` varchar(50)
,`last_name` varchar(50)
,`thai_first_name` varchar(50)
,`thai_last_name` varchar(50)
,`phone` varchar(20)
,`faculty_name` varchar(255)
,`thai_faculty_name` varchar(255)
,`department_name` varchar(100)
,`thai_department_name` varchar(100)
,`major_name` varchar(100)
,`thai_major_name` varchar(100)
,`Curriculum_Name` varchar(50)
,`enrollment_date` date
,`student_status` enum('active','graduated','leave_of_absence','dismissed')
,`academic_status` enum('normal','probation','honors')
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_student_registrations`
-- (See below for the actual view)
--
CREATE TABLE `vw_student_registrations` (
`id_account` int(11)
,`student_code` varchar(20)
,`student_name` varchar(101)
,`thai_student_name` varchar(101)
,`Registration_ID` int(10)
,`Course_Code` int(10)
,`Course_Name` varchar(255)
,`Semester` int(1)
,`Academic_Year` int(4)
,`Grade` decimal(3,2)
,`Credits` int(1)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_student_timetable`
-- (See below for the actual view)
--
CREATE TABLE `vw_student_timetable` (
`student_code` varchar(20)
,`student_name` varchar(101)
,`thai_student_name` varchar(101)
,`Course_Code` int(11)
,`Course_Name` varchar(255)
,`section_number` varchar(5)
,`instructor_name` varchar(200)
,`location` varchar(203)
,`thai_location` varchar(203)
,`day_of_week` enum('monday','tuesday','wednesday','thursday','friday','saturday','sunday')
,`start_time` time
,`end_time` time
,`class_mode` varchar(6)
,`online_meeting_link` varchar(255)
,`semester_name` varchar(100)
,`thai_semester_name` varchar(100)
,`academic_year` varchar(10)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_student_toeic`
-- (See below for the actual view)
--
CREATE TABLE `vw_student_toeic` (
`id_account` int(11)
,`student_code` varchar(20)
,`student_name` varchar(101)
,`thai_student_name` varchar(101)
,`TOEIC_ID` int(10)
,`Pre_Test_Score` int(3)
,`TOEIC_Score` int(3)
,`Registration_Status` varchar(10)
,`Test_Date` date
,`major_name` varchar(100)
,`thai_major_name` varchar(100)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_student_transcript`
-- (See below for the actual view)
--
CREATE TABLE `vw_student_transcript` (
`id_account` int(11)
,`student_code` varchar(20)
,`student_name` varchar(101)
,`thai_student_name` varchar(101)
,`major_name` varchar(100)
,`Curriculum_Name` varchar(50)
,`total_credits` decimal(32,0)
,`gpa` decimal(7,6)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_teacher_info`
-- (See below for the actual view)
--
CREATE TABLE `vw_teacher_info` (
`id_account` int(11)
,`username_account` varchar(40)
,`email_account` varchar(40)
,`Role_account` enum('student','teacher','academic')
,`teacher_code` varchar(20)
,`first_name` varchar(50)
,`last_name` varchar(50)
,`thai_first_name` varchar(50)
,`thai_last_name` varchar(50)
,`phone` varchar(20)
,`faculty_name` varchar(255)
,`thai_faculty_name` varchar(255)
,`department_name` varchar(100)
,`thai_department_name` varchar(100)
,`position` varchar(100)
,`expertise` text
,`office_location` varchar(100)
,`office_hours` varchar(255)
);

-- --------------------------------------------------------

--
-- Structure for view `vw_current_class_schedules`
--
DROP TABLE IF EXISTS `vw_current_class_schedules`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_current_class_schedules`  AS SELECT `c`.`Course_Code` AS `Course_Code`, `c`.`Course_Name` AS `Course_Name`, `cs`.`section_number` AS `section_number`, `cs`.`instructor_name` AS `instructor_name`, concat(`b`.`building_name`,' - ',`r`.`room_name`) AS `location`, concat(`b`.`thai_building_name`,' - ',`r`.`thai_room_name`) AS `thai_location`, `cl`.`day_of_week` AS `day_of_week`, `cl`.`start_time` AS `start_time`, `cl`.`end_time` AS `end_time`, CASE WHEN `cl`.`is_online` = 1 THEN 'Online' ELSE 'Onsite' END AS `class_mode`, `cl`.`online_meeting_link` AS `online_meeting_link`, `sm`.`name` AS `semester_name`, `sm`.`thai_name` AS `thai_semester_name`, `ay`.`year` AS `academic_year` FROM ((((((`class_schedules` `cl` join `course_sections` `cs` on(`cl`.`section_id` = `cs`.`section_id`)) join `course` `c` on(`cs`.`Course_Code` = `c`.`Course_Code`)) left join `rooms` `r` on(`cl`.`room_id` = `r`.`room_id`)) left join `buildings` `b` on(`r`.`building_id` = `b`.`building_id`)) join `semesters` `sm` on(`cs`.`semester_id` = `sm`.`semester_id`)) join `academic_years` `ay` on(`sm`.`academic_year_id` = `ay`.`academic_year_id`)) WHERE `sm`.`is_current` = 1 ORDER BY `cl`.`day_of_week` ASC, `cl`.`start_time` ASC, `c`.`Course_Code` ASC ;

-- --------------------------------------------------------

--
-- Structure for view `vw_major_timetable`
--
DROP TABLE IF EXISTS `vw_major_timetable`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_major_timetable`  AS SELECT `m`.`major_id` AS `major_id`, `m`.`major_code` AS `major_code`, `m`.`major_name` AS `major_name`, `m`.`thai_major_name` AS `thai_major_name`, `p`.`program_name` AS `program_name`, `p`.`thai_program_name` AS `thai_program_name`, `d`.`department_name` AS `department_name`, `d`.`thai_department_name` AS `thai_department_name`, `f`.`faculty_name` AS `faculty_name`, `f`.`thai_faculty_name` AS `thai_faculty_name`, `mc`.`study_year` AS `study_year`, `sm`.`semester_number` AS `semester_number`, `c`.`Course_Code` AS `Course_Code`, `c`.`Course_Name` AS `Course_Name`, `cs`.`section_number` AS `section_number`, `cs`.`instructor_name` AS `instructor_name`, concat(`b`.`building_name`,' - ',`r`.`room_name`) AS `location`, concat(`b`.`thai_building_name`,' - ',`r`.`thai_room_name`) AS `thai_location`, `cl`.`day_of_week` AS `day_of_week`, `cl`.`start_time` AS `start_time`, `cl`.`end_time` AS `end_time`, CASE WHEN `cl`.`is_online` = 1 THEN 'Online' ELSE 'Onsite' END AS `class_mode`, `cl`.`online_meeting_link` AS `online_meeting_link`, `sm`.`name` AS `semester_name`, `sm`.`thai_name` AS `thai_semester_name`, `ay`.`year` AS `academic_year` FROM (((((((((((`major` `m` join `major_courses` `mc` on(`m`.`major_id` = `mc`.`major_id`)) join `course` `c` on(`mc`.`Course_Code` = `c`.`Course_Code`)) join `course_sections` `cs` on(`c`.`Course_Code` = `cs`.`Course_Code`)) join `class_schedules` `cl` on(`cs`.`section_id` = `cl`.`section_id`)) left join `rooms` `r` on(`cl`.`room_id` = `r`.`room_id`)) left join `buildings` `b` on(`r`.`building_id` = `b`.`building_id`)) join `semesters` `sm` on(`cs`.`semester_id` = `sm`.`semester_id`)) join `academic_years` `ay` on(`sm`.`academic_year_id` = `ay`.`academic_year_id`)) join `department` `d` on(`m`.`department_id` = `d`.`department_id`)) join `faculty` `f` on(`d`.`faculty_id` = `f`.`id`)) join `programs` `p` on(`m`.`program_id` = `p`.`program_id`)) WHERE `mc`.`semester_number` = `sm`.`semester_number` ORDER BY `m`.`major_code` ASC, `mc`.`study_year` ASC, `cl`.`day_of_week` ASC, `cl`.`start_time` ASC ;

-- --------------------------------------------------------

--
-- Structure for view `vw_student_info`
--
DROP TABLE IF EXISTS `vw_student_info`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_student_info`  AS SELECT `a`.`id_account` AS `id_account`, `a`.`username_account` AS `username_account`, `a`.`email_account` AS `email_account`, `a`.`Role_account` AS `Role_account`, `sd`.`student_code` AS `student_code`, `up`.`first_name` AS `first_name`, `up`.`last_name` AS `last_name`, `up`.`thai_first_name` AS `thai_first_name`, `up`.`thai_last_name` AS `thai_last_name`, `up`.`phone` AS `phone`, `f`.`faculty_name` AS `faculty_name`, `f`.`thai_faculty_name` AS `thai_faculty_name`, `d`.`department_name` AS `department_name`, `d`.`thai_department_name` AS `thai_department_name`, `m`.`major_name` AS `major_name`, `m`.`thai_major_name` AS `thai_major_name`, `c`.`Curriculum_Name` AS `Curriculum_Name`, `sd`.`enrollment_date` AS `enrollment_date`, `sd`.`status` AS `student_status`, `sd`.`academic_status` AS `academic_status` FROM ((((((`account` `a` join `student_details` `sd` on(`a`.`id_account` = `sd`.`id_account`)) join `user_profiles` `up` on(`a`.`id_account` = `up`.`id_account`)) left join `faculty` `f` on(`up`.`faculty_id` = `f`.`id`)) left join `department` `d` on(`up`.`department_id` = `d`.`department_id`)) left join `major` `m` on(`sd`.`major_id` = `m`.`major_id`)) left join `curriculum` `c` on(`sd`.`Curriculum_ID` = `c`.`Curriculum_ID`)) WHERE `a`.`Role_account` = 'student' ;

-- --------------------------------------------------------

--
-- Structure for view `vw_student_registrations`
--
DROP TABLE IF EXISTS `vw_student_registrations`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_student_registrations`  AS SELECT `a`.`id_account` AS `id_account`, `sd`.`student_code` AS `student_code`, concat(`up`.`first_name`,' ',`up`.`last_name`) AS `student_name`, concat(`up`.`thai_first_name`,' ',`up`.`thai_last_name`) AS `thai_student_name`, `cr`.`Registration_ID` AS `Registration_ID`, `cr`.`Course_Code` AS `Course_Code`, `c`.`Course_Name` AS `Course_Name`, `cr`.`Semester` AS `Semester`, `cr`.`Academic_Year` AS `Academic_Year`, `cr`.`Grade` AS `Grade`, `cr`.`Credits` AS `Credits` FROM ((((`account` `a` join `student_details` `sd` on(`a`.`id_account` = `sd`.`id_account`)) join `user_profiles` `up` on(`a`.`id_account` = `up`.`id_account`)) join `course_registration` `cr` on(`sd`.`student_code` = `cr`.`Student_ID`)) join `course` `c` on(`cr`.`Course_Code` = `c`.`Course_Code`)) WHERE `a`.`Role_account` = 'student' ;

-- --------------------------------------------------------

--
-- Structure for view `vw_student_timetable`
--
DROP TABLE IF EXISTS `vw_student_timetable`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_student_timetable`  AS SELECT `sd`.`student_code` AS `student_code`, concat(`up`.`first_name`,' ',`up`.`last_name`) AS `student_name`, concat(`up`.`thai_first_name`,' ',`up`.`thai_last_name`) AS `thai_student_name`, `c`.`Course_Code` AS `Course_Code`, `c`.`Course_Name` AS `Course_Name`, `cs`.`section_number` AS `section_number`, `cs`.`instructor_name` AS `instructor_name`, concat(`b`.`building_name`,' - ',`r`.`room_name`) AS `location`, concat(`b`.`thai_building_name`,' - ',`r`.`thai_room_name`) AS `thai_location`, `cl`.`day_of_week` AS `day_of_week`, `cl`.`start_time` AS `start_time`, `cl`.`end_time` AS `end_time`, CASE WHEN `cl`.`is_online` = 1 THEN 'Online' ELSE 'Onsite' END AS `class_mode`, `cl`.`online_meeting_link` AS `online_meeting_link`, `sm`.`name` AS `semester_name`, `sm`.`thai_name` AS `thai_semester_name`, `ay`.`year` AS `academic_year` FROM ((((((((((`student_details` `sd` join `account` `a` on(`sd`.`id_account` = `a`.`id_account`)) join `user_profiles` `up` on(`a`.`id_account` = `up`.`id_account`)) join `course_registration` `cr` on(`sd`.`student_code` = `cr`.`Student_ID`)) join `course_sections` `cs` on(`cr`.`section_id` = `cs`.`section_id`)) join `course` `c` on(`cs`.`Course_Code` = `c`.`Course_Code`)) join `class_schedules` `cl` on(`cs`.`section_id` = `cl`.`section_id`)) left join `rooms` `r` on(`cl`.`room_id` = `r`.`room_id`)) left join `buildings` `b` on(`r`.`building_id` = `b`.`building_id`)) join `semesters` `sm` on(`cs`.`semester_id` = `sm`.`semester_id`)) join `academic_years` `ay` on(`sm`.`academic_year_id` = `ay`.`academic_year_id`)) WHERE `cr`.`status` = 'registered' ORDER BY `sd`.`student_code` ASC, `cl`.`day_of_week` ASC, `cl`.`start_time` ASC ;

-- --------------------------------------------------------

--
-- Structure for view `vw_student_toeic`
--
DROP TABLE IF EXISTS `vw_student_toeic`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_student_toeic`  AS SELECT `a`.`id_account` AS `id_account`, `sd`.`student_code` AS `student_code`, concat(`up`.`first_name`,' ',`up`.`last_name`) AS `student_name`, concat(`up`.`thai_first_name`,' ',`up`.`thai_last_name`) AS `thai_student_name`, `t`.`TOEIC_ID` AS `TOEIC_ID`, `t`.`Pre_Test_Score` AS `Pre_Test_Score`, `t`.`TOEIC_Score` AS `TOEIC_Score`, `t`.`Registration_Status` AS `Registration_Status`, `t`.`Test_Date` AS `Test_Date`, `m`.`major_name` AS `major_name`, `m`.`thai_major_name` AS `thai_major_name` FROM ((((`account` `a` join `student_details` `sd` on(`a`.`id_account` = `sd`.`id_account`)) join `user_profiles` `up` on(`a`.`id_account` = `up`.`id_account`)) left join `toeic` `t` on(`sd`.`student_code` = `t`.`Student_ID`)) left join `major` `m` on(`sd`.`major_id` = `m`.`major_id`)) WHERE `a`.`Role_account` = 'student' ;

-- --------------------------------------------------------

--
-- Structure for view `vw_student_transcript`
--
DROP TABLE IF EXISTS `vw_student_transcript`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_student_transcript`  AS SELECT `a`.`id_account` AS `id_account`, `sd`.`student_code` AS `student_code`, concat(`up`.`first_name`,' ',`up`.`last_name`) AS `student_name`, concat(`up`.`thai_first_name`,' ',`up`.`thai_last_name`) AS `thai_student_name`, `m`.`major_name` AS `major_name`, `c`.`Curriculum_Name` AS `Curriculum_Name`, sum(`cr`.`Credits`) AS `total_credits`, avg(`cr`.`Grade`) AS `gpa` FROM (((((`account` `a` join `student_details` `sd` on(`a`.`id_account` = `sd`.`id_account`)) join `user_profiles` `up` on(`a`.`id_account` = `up`.`id_account`)) left join `major` `m` on(`sd`.`major_id` = `m`.`major_id`)) left join `curriculum` `c` on(`sd`.`Curriculum_ID` = `c`.`Curriculum_ID`)) left join `course_registration` `cr` on(`sd`.`student_code` = `cr`.`Student_ID`)) WHERE `a`.`Role_account` = 'student' GROUP BY `a`.`id_account`, `sd`.`student_code`, concat(`up`.`first_name`,' ',`up`.`last_name`), concat(`up`.`thai_first_name`,' ',`up`.`thai_last_name`), `m`.`major_name`, `c`.`Curriculum_Name` ;

-- --------------------------------------------------------

--
-- Structure for view `vw_teacher_info`
--
DROP TABLE IF EXISTS `vw_teacher_info`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_teacher_info`  AS SELECT `a`.`id_account` AS `id_account`, `a`.`username_account` AS `username_account`, `a`.`email_account` AS `email_account`, `a`.`Role_account` AS `Role_account`, `td`.`teacher_code` AS `teacher_code`, `up`.`first_name` AS `first_name`, `up`.`last_name` AS `last_name`, `up`.`thai_first_name` AS `thai_first_name`, `up`.`thai_last_name` AS `thai_last_name`, `up`.`phone` AS `phone`, `f`.`faculty_name` AS `faculty_name`, `f`.`thai_faculty_name` AS `thai_faculty_name`, `d`.`department_name` AS `department_name`, `d`.`thai_department_name` AS `thai_department_name`, `td`.`position` AS `position`, `td`.`expertise` AS `expertise`, `td`.`office_location` AS `office_location`, `td`.`office_hours` AS `office_hours` FROM ((((`account` `a` join `teacher_details` `td` on(`a`.`id_account` = `td`.`id_account`)) join `user_profiles` `up` on(`a`.`id_account` = `up`.`id_account`)) left join `faculty` `f` on(`up`.`faculty_id` = `f`.`id`)) left join `department` `d` on(`up`.`department_id` = `d`.`department_id`)) WHERE `a`.`Role_account` = 'teacher' ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `academic_years`
--
ALTER TABLE `academic_years`
  ADD PRIMARY KEY (`academic_year_id`),
  ADD UNIQUE KEY `year_UNIQUE` (`year`);

--
-- Indexes for table `account`
--
ALTER TABLE `account`
  ADD PRIMARY KEY (`id_account`),
  ADD UNIQUE KEY `username_account_UNIQUE` (`username_account`),
  ADD UNIQUE KEY `email_account_UNIQUE` (`email_account`),
  ADD KEY `idx_role` (`Role_account`);

--
-- Indexes for table `buildings`
--
ALTER TABLE `buildings`
  ADD PRIMARY KEY (`building_id`),
  ADD UNIQUE KEY `building_code_UNIQUE` (`building_code`);

--
-- Indexes for table `class_schedules`
--
ALTER TABLE `class_schedules`
  ADD PRIMARY KEY (`schedule_id`),
  ADD KEY `fk_class_schedules_section_idx` (`section_id`),
  ADD KEY `fk_class_schedules_room_idx` (`room_id`);

--
-- Indexes for table `course`
--
ALTER TABLE `course`
  ADD PRIMARY KEY (`Course_Code`),
  ADD KEY `fk_course_curriculum_idx` (`Curriculum_ID`);

--
-- Indexes for table `course_registration`
--
ALTER TABLE `course_registration`
  ADD PRIMARY KEY (`Registration_ID`),
  ADD KEY `fk_course_registration_student_idx` (`Student_ID`),
  ADD KEY `fk_course_registration_course_idx` (`Course_Code`),
  ADD KEY `fk_course_registration_section_idx` (`section_id`);

--
-- Indexes for table `course_sections`
--
ALTER TABLE `course_sections`
  ADD PRIMARY KEY (`section_id`),
  ADD UNIQUE KEY `course_section_semester_UNIQUE` (`Course_Code`,`section_number`,`semester_id`),
  ADD KEY `fk_course_sections_course_idx` (`Course_Code`),
  ADD KEY `fk_course_sections_semester_idx` (`semester_id`);

--
-- Indexes for table `course_type`
--
ALTER TABLE `course_type`
  ADD PRIMARY KEY (`Course_Code`);

--
-- Indexes for table `curriculum`
--
ALTER TABLE `curriculum`
  ADD PRIMARY KEY (`Curriculum_ID`),
  ADD KEY `fk_curriculum_department_idx` (`department_id`);

--
-- Indexes for table `department`
--
ALTER TABLE `department`
  ADD PRIMARY KEY (`department_id`),
  ADD UNIQUE KEY `department_code_UNIQUE` (`department_code`),
  ADD KEY `fk_department_faculty_idx` (`faculty_id`);

--
-- Indexes for table `faculty`
--
ALTER TABLE `faculty`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `logs`
--
ALTER TABLE `logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `fk_logs_account_idx` (`id_account`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `major`
--
ALTER TABLE `major`
  ADD PRIMARY KEY (`major_id`),
  ADD UNIQUE KEY `major_code_UNIQUE` (`major_code`),
  ADD KEY `fk_major_department_idx` (`department_id`),
  ADD KEY `fk_majors_program_idx` (`program_id`),
  ADD KEY `fk_major_curriculum` (`Curriculum_ID`);

--
-- Indexes for table `major_courses`
--
ALTER TABLE `major_courses`
  ADD PRIMARY KEY (`major_course_id`),
  ADD UNIQUE KEY `major_course_year_semester_UNIQUE` (`major_id`,`Course_Code`,`study_year`,`semester_number`),
  ADD KEY `fk_major_courses_major_idx` (`major_id`),
  ADD KEY `fk_major_courses_course_idx` (`Course_Code`);

--
-- Indexes for table `programs`
--
ALTER TABLE `programs`
  ADD PRIMARY KEY (`program_id`),
  ADD UNIQUE KEY `program_code_UNIQUE` (`program_code`);

--
-- Indexes for table `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`room_id`),
  ADD UNIQUE KEY `building_room_UNIQUE` (`building_id`,`room_code`);

--
-- Indexes for table `semesters`
--
ALTER TABLE `semesters`
  ADD PRIMARY KEY (`semester_id`),
  ADD UNIQUE KEY `year_semester_UNIQUE` (`academic_year_id`,`semester_number`);

--
-- Indexes for table `student_details`
--
ALTER TABLE `student_details`
  ADD PRIMARY KEY (`student_detail_id`),
  ADD UNIQUE KEY `student_code_UNIQUE` (`student_code`),
  ADD UNIQUE KEY `id_account_UNIQUE` (`id_account`),
  ADD KEY `fk_student_details_major_idx` (`major_id`),
  ADD KEY `fk_student_details_curriculum_idx` (`Curriculum_ID`);

--
-- Indexes for table `teacher_details`
--
ALTER TABLE `teacher_details`
  ADD PRIMARY KEY (`teacher_detail_id`),
  ADD UNIQUE KEY `teacher_code_UNIQUE` (`teacher_code`),
  ADD UNIQUE KEY `id_account_UNIQUE` (`id_account`);

--
-- Indexes for table `toeic`
--
ALTER TABLE `toeic`
  ADD PRIMARY KEY (`TOEIC_ID`),
  ADD KEY `fk_toeic_student_idx` (`Student_ID`);

--
-- Indexes for table `user_profiles`
--
ALTER TABLE `user_profiles`
  ADD PRIMARY KEY (`profile_id`),
  ADD UNIQUE KEY `id_account_UNIQUE` (`id_account`),
  ADD KEY `fk_user_profiles_faculty_idx` (`faculty_id`),
  ADD KEY `fk_user_profiles_department_idx` (`department_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `academic_years`
--
ALTER TABLE `academic_years`
  MODIFY `academic_year_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `account`
--
ALTER TABLE `account`
  MODIFY `id_account` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `buildings`
--
ALTER TABLE `buildings`
  MODIFY `building_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `class_schedules`
--
ALTER TABLE `class_schedules`
  MODIFY `schedule_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `course`
--
ALTER TABLE `course`
  MODIFY `Course_Code` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4000202;

--
-- AUTO_INCREMENT for table `course_registration`
--
ALTER TABLE `course_registration`
  MODIFY `Registration_ID` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `course_sections`
--
ALTER TABLE `course_sections`
  MODIFY `section_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `curriculum`
--
ALTER TABLE `curriculum`
  MODIFY `Curriculum_ID` int(7) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=78;

--
-- AUTO_INCREMENT for table `department`
--
ALTER TABLE `department`
  MODIFY `department_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `faculty`
--
ALTER TABLE `faculty`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `logs`
--
ALTER TABLE `logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=86;

--
-- AUTO_INCREMENT for table `major`
--
ALTER TABLE `major`
  MODIFY `major_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `major_courses`
--
ALTER TABLE `major_courses`
  MODIFY `major_course_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `programs`
--
ALTER TABLE `programs`
  MODIFY `program_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `rooms`
--
ALTER TABLE `rooms`
  MODIFY `room_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `semesters`
--
ALTER TABLE `semesters`
  MODIFY `semester_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `student_details`
--
ALTER TABLE `student_details`
  MODIFY `student_detail_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `teacher_details`
--
ALTER TABLE `teacher_details`
  MODIFY `teacher_detail_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `toeic`
--
ALTER TABLE `toeic`
  MODIFY `TOEIC_ID` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `user_profiles`
--
ALTER TABLE `user_profiles`
  MODIFY `profile_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `class_schedules`
--
ALTER TABLE `class_schedules`
  ADD CONSTRAINT `fk_class_schedules_room` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`room_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_class_schedules_section` FOREIGN KEY (`section_id`) REFERENCES `course_sections` (`section_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `course`
--
ALTER TABLE `course`
  ADD CONSTRAINT `fk_course_curriculum` FOREIGN KEY (`Curriculum_ID`) REFERENCES `curriculum` (`Curriculum_ID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `course_registration`
--
ALTER TABLE `course_registration`
  ADD CONSTRAINT `fk_course_registration_course` FOREIGN KEY (`Course_Code`) REFERENCES `course` (`Course_Code`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_course_registration_section` FOREIGN KEY (`section_id`) REFERENCES `course_sections` (`section_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `course_sections`
--
ALTER TABLE `course_sections`
  ADD CONSTRAINT `fk_course_sections_course` FOREIGN KEY (`Course_Code`) REFERENCES `course` (`Course_Code`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_course_sections_semester` FOREIGN KEY (`semester_id`) REFERENCES `semesters` (`semester_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `course_type`
--
ALTER TABLE `course_type`
  ADD CONSTRAINT `fk_course_type_course` FOREIGN KEY (`Course_Code`) REFERENCES `course` (`Course_Code`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `curriculum`
--
ALTER TABLE `curriculum`
  ADD CONSTRAINT `fk_curriculum_department` FOREIGN KEY (`department_id`) REFERENCES `department` (`department_id`) ON UPDATE CASCADE;

--
-- Constraints for table `department`
--
ALTER TABLE `department`
  ADD CONSTRAINT `fk_department_faculty` FOREIGN KEY (`faculty_id`) REFERENCES `faculty` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `logs`
--
ALTER TABLE `logs`
  ADD CONSTRAINT `fk_logs_account` FOREIGN KEY (`id_account`) REFERENCES `account` (`id_account`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `major`
--
ALTER TABLE `major`
  ADD CONSTRAINT `fk_major_curriculum` FOREIGN KEY (`Curriculum_ID`) REFERENCES `curriculum` (`Curriculum_ID`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_major_department` FOREIGN KEY (`department_id`) REFERENCES `department` (`department_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_majors_program` FOREIGN KEY (`program_id`) REFERENCES `programs` (`program_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `major_courses`
--
ALTER TABLE `major_courses`
  ADD CONSTRAINT `fk_major_courses_course` FOREIGN KEY (`Course_Code`) REFERENCES `course` (`Course_Code`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_major_courses_major` FOREIGN KEY (`major_id`) REFERENCES `major` (`major_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `rooms`
--
ALTER TABLE `rooms`
  ADD CONSTRAINT `fk_rooms_building` FOREIGN KEY (`building_id`) REFERENCES `buildings` (`building_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `semesters`
--
ALTER TABLE `semesters`
  ADD CONSTRAINT `fk_semesters_academic_year` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`academic_year_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `student_details`
--
ALTER TABLE `student_details`
  ADD CONSTRAINT `fk_student_details_account` FOREIGN KEY (`id_account`) REFERENCES `account` (`id_account`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_student_details_curriculum` FOREIGN KEY (`Curriculum_ID`) REFERENCES `curriculum` (`Curriculum_ID`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_student_details_major` FOREIGN KEY (`major_id`) REFERENCES `major` (`major_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `teacher_details`
--
ALTER TABLE `teacher_details`
  ADD CONSTRAINT `fk_teacher_details_account` FOREIGN KEY (`id_account`) REFERENCES `account` (`id_account`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `user_profiles`
--
ALTER TABLE `user_profiles`
  ADD CONSTRAINT `fk_user_profiles_account` FOREIGN KEY (`id_account`) REFERENCES `account` (`id_account`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_user_profiles_department` FOREIGN KEY (`department_id`) REFERENCES `department` (`department_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_user_profiles_faculty` FOREIGN KEY (`faculty_id`) REFERENCES `faculty` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
