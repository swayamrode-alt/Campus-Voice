-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 04, 2026 at 12:16 AM
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
-- Database: `college_complaint_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `name`, `username`, `password`, `email`, `created_at`) VALUES
(1, 'Administrator', 'admin', '$2y$10$bGNOtackIFN1cVDb60rcturS4Grk/pitfuss1ue6FGb249qojdqlS', 'admin@college.edu', '2026-08-15 11:55:53');

-- --------------------------------------------------------

--
-- Table structure for table `assignments`
--

CREATE TABLE `assignments` (
  `id` int(11) NOT NULL,
  `complaint_id` int(11) NOT NULL,
  `staff_id` int(11) NOT NULL,
  `assigned_by` int(11) DEFAULT NULL,
  `work_details` text DEFAULT NULL,
  `status` enum('Assigned','Accepted','In Progress','Completed','Rejected') DEFAULT 'Assigned',
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `completed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `assignments`
--

INSERT INTO `assignments` (`id`, `complaint_id`, `staff_id`, `assigned_by`, `work_details`, `status`, `assigned_at`, `completed_at`) VALUES
(27, 25, 1, NULL, '', 'Completed', '2026-10-03 19:09:45', '2026-10-03 19:11:14');

-- --------------------------------------------------------

--
-- Table structure for table `complaints`
--

CREATE TABLE `complaints` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `title` varchar(150) NOT NULL,
  `description` text NOT NULL,
  `category` varchar(100) NOT NULL,
  `location` varchar(150) DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `status` enum('Pending','Assigned','In Progress','Resolved','Rejected') DEFAULT 'Pending',
  `priority` enum('Low','Medium','High','Urgent') DEFAULT 'Medium',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `after_photo` varchar(255) DEFAULT NULL,
  `reopen_reason` text DEFAULT NULL,
  `reopen_count` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `complaints`
--

INSERT INTO `complaints` (`id`, `student_id`, `title`, `description`, `category`, `location`, `photo`, `status`, `priority`, `created_at`, `updated_at`, `after_photo`, `reopen_reason`, `reopen_count`) VALUES
(25, 101, 'IT / Computer complaint at Computer Lab 1', 'pc no 4 is not working in cs lab', 'IT / Computer', '4th Floor - Computer Lab 1', 'complaint_2aa567754887ad9b21422dffebcdaeec.jpg', 'Resolved', 'Medium', '2026-10-03 18:38:38', '2026-10-03 19:11:14', 'after_25_13cf352fcb54381b023b6d90dd8fa0e4.jpg', NULL, 0),
(26, 101, 'IT / Computer complaint at Computer Lab 6', 'pc not working', 'IT / Computer', '4th Floor - Computer Lab 6', 'complaint_89a841df4b1317761042b1d1c7c0517a.jpg', 'Pending', 'Medium', '2026-10-03 21:18:03', '2026-10-03 21:18:03', NULL, NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `complaint_warnings`
--

CREATE TABLE `complaint_warnings` (
  `id` int(11) NOT NULL,
  `complaint_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `warning_type` varchar(50) NOT NULL,
  `admin_remark` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `feedback`
--

CREATE TABLE `feedback` (
  `id` int(11) NOT NULL,
  `complaint_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `staff_id` int(11) DEFAULT NULL,
  `rating` int(11) NOT NULL,
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `student_id` int(11) DEFAULT NULL,
  `staff_id` int(11) DEFAULT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `student_id`, `staff_id`, `admin_id`, `message`, `is_read`, `created_at`) VALUES
(1, NULL, 1, NULL, 'New complaint assigned to you. Complaint ID: CMP-001', 1, '2026-08-15 12:17:30'),
(2, NULL, NULL, 1, 'Staff Rahul Staff updated complaint CMP-001 to Accepted.', 0, '2026-08-15 12:44:06'),
(3, NULL, NULL, 1, 'Staff Rahul Staff updated complaint CMP-001 to In Progress.', 0, '2026-08-15 12:44:17'),
(4, NULL, NULL, 1, 'Staff Rahul Staff updated complaint CMP-001 to Completed.', 0, '2026-08-15 12:44:22'),
(5, NULL, NULL, 1, 'New complaint submitted: CMP-002 - IN Library', 0, '2026-08-16 11:13:32'),
(6, NULL, 1, NULL, 'New complaint assigned to you. Complaint ID: CMP-002', 1, '2026-08-16 11:15:50'),
(8, NULL, NULL, 1, 'Staff Rahul Staff updated complaint CMP-002 to Accepted.', 0, '2026-08-16 11:18:29'),
(10, NULL, NULL, 1, 'Staff Rahul Staff updated complaint CMP-002 to In Progress.', 0, '2026-08-16 11:18:34'),
(12, NULL, NULL, 1, 'Staff Rahul Staff updated complaint CMP-002 to Completed.', 0, '2026-08-16 11:18:41'),
(13, NULL, NULL, 1, 'New complaint submitted: CMP-003 - IN Washroom', 0, '2026-08-16 11:51:27'),
(14, NULL, 1, NULL, 'New complaint assigned to you. Complaint ID: CMP-003', 1, '2026-08-16 11:55:30'),
(16, NULL, NULL, 1, 'Staff Rahul Staff updated complaint CMP-003 to Rejected.', 0, '2026-08-16 11:56:36'),
(17, NULL, NULL, 1, 'New complaint submitted: CMP-004 - garbage in quaridoor', 0, '2026-08-28 11:21:31'),
(18, NULL, NULL, 1, 'New complaint submitted: CMP-005 - Lab', 0, '2026-08-28 13:24:36'),
(19, NULL, 1, NULL, 'New complaint assigned to you. Complaint ID: CMP-005', 1, '2026-08-28 13:26:22'),
(21, NULL, NULL, 1, 'Staff Rahul Staff updated complaint CMP-005 to Accepted.', 0, '2026-08-28 13:27:42'),
(23, NULL, NULL, 1, 'Staff Rahul Staff updated complaint CMP-005 to In Progress.', 0, '2026-08-28 13:27:44'),
(25, NULL, NULL, 1, 'Staff Rahul Staff updated complaint CMP-005 to Completed.', 0, '2026-08-28 13:27:50'),
(26, NULL, NULL, 1, 'New complaint CMP-007 at Computer Lab 1 (IT / Computer)', 0, '2026-08-29 18:05:37'),
(27, NULL, NULL, 1, 'New complaint CMP-008 at Girls Washroom (Washroom)', 0, '2026-08-29 18:06:53'),
(28, NULL, NULL, 1, 'New complaint CMP-009 | 4th Floor - Computer Lab 1 | IT / Computer', 0, '2026-08-30 17:41:34'),
(29, NULL, NULL, 1, 'New complaint CMP-010 | 1st Floor - Physics Lab 3 | Laboratory', 0, '2026-08-30 17:42:32'),
(30, NULL, NULL, 1, 'New complaint CMP-011 | 3rd Floor - 3rd Floor Corridor | Cleanliness', 0, '2026-08-30 17:43:41'),
(31, NULL, NULL, 1, 'New complaint CMP-012 | 4th Floor - Computer Lab 1 | IT / Computer', 0, '2026-08-30 17:50:54'),
(32, NULL, NULL, 1, 'New complaint CMP-013 | 4th Floor - Computer Lab 2 (PG CS) | IT / Computer', 0, '2026-08-30 18:01:19'),
(33, NULL, 37, NULL, 'Complaint CMP-011 has been assigned to you.', 0, '2026-08-30 18:16:25'),
(34, NULL, 31, NULL, 'New complaint assigned to you. Complaint ID: CMP-008', 0, '2026-08-30 18:23:40'),
(35, NULL, NULL, 1, 'New complaint CMP-014 | 4th Floor - Computer Lab 1 | IT / Computer', 0, '2026-08-30 18:55:08'),
(36, NULL, NULL, 1, 'New complaint CMP-015 | Ground Floor - Gymkhana | Gymkhana', 0, '2026-09-02 17:18:12'),
(37, NULL, 1, NULL, 'New complaint assigned to you. Complaint ID: CMP-015', 1, '2026-09-02 17:19:41'),
(38, NULL, NULL, 1, 'New complaint CMP-016 | 4th Floor - Computer Lab 1 | IT / Computer', 0, '2026-09-02 17:52:27'),
(39, NULL, NULL, 1, 'New complaint CMP-017 | 4th Floor - Computer Lab 1 | IT / Computer', 0, '2026-09-02 17:52:43'),
(40, NULL, NULL, 1, 'New complaint CMP-018 | 4th Floor - Computer Lab 1 | IT / Computer', 0, '2026-09-02 18:02:20'),
(42, NULL, NULL, 1, 'New complaint CMP-019 | 4th Floor - Computer Lab 1 | IT / Computer', 0, '2026-09-02 18:04:59'),
(48, NULL, 1, NULL, 'Complaint CMP-019 has been reopened by the student. Reason: it is not resolved yet', 1, '2026-09-03 17:29:00'),
(49, NULL, NULL, 1, 'Complaint CMP-019 has been reopened by the student. Reason: it is not resolved yet', 0, '2026-09-03 17:29:00'),
(50, NULL, NULL, 1, 'New complaint CMP-020 | 4th Floor - Computer Lab 3 (PG IT) | IT / Computer', 0, '2026-09-03 17:57:08'),
(51, NULL, 1, NULL, 'New complaint CMP-020 is available in your department. Any related staff member can accept it.', 1, '2026-09-03 17:57:08'),
(52, NULL, 1, NULL, 'New complaint assigned to you. Complaint ID: CMP-020', 1, '2026-09-03 18:05:45'),
(57, NULL, NULL, 1, 'New complaint CMP-021 | 4th Floor - Computer Lab 3 (PG IT) | IT / Computer', 0, '2026-09-03 18:09:31'),
(58, NULL, 1, NULL, 'New complaint CMP-021 is available in your department. Any related staff member can accept it.', 1, '2026-09-03 18:09:31'),
(64, NULL, NULL, 1, 'New complaint CMP-022 | 4th Floor - Computer Lab 4 (IT) | IT / Computer', 0, '2026-09-03 18:13:11'),
(65, NULL, 1, NULL, 'New complaint CMP-022 is available in your department. Any related staff member can accept it.', 1, '2026-09-03 18:13:11'),
(69, NULL, NULL, 1, 'New complaint CMP-023 | 4th Floor - Computer Lab 3 (PG IT) | IT / Computer', 0, '2026-09-03 20:18:47'),
(70, NULL, 1, NULL, 'New complaint CMP-023 is available in your department. Any related staff member can accept it.', 1, '2026-09-03 20:18:47'),
(74, NULL, NULL, 1, 'New complaint CMP-024 | 4th Floor - Computer Lab 1 | IT / Computer', 0, '2026-09-04 06:16:22'),
(75, NULL, 1, NULL, 'New complaint CMP-024 is available in your department. Any related staff member can accept it.', 1, '2026-09-04 06:16:22'),
(79, NULL, NULL, 1, 'Student has requested reopening of Complaint #24 (IT / Computer complaint at Computer Lab 1).', 0, '2026-09-04 06:53:19'),
(81, NULL, 1, NULL, 'Complaint #24 has been reopened by admin. Please review and work on it again.', 1, '2026-09-04 06:54:20'),
(82, NULL, NULL, 1, 'Reopen request #1 approved for Complaint #24.', 0, '2026-09-04 06:54:20'),
(86, NULL, NULL, 1, 'Student has requested reopening of Complaint #24 (IT / Computer complaint at Computer Lab 1).', 0, '2026-09-04 07:14:12'),
(88, NULL, 1, NULL, 'Complaint #24 has been reopened by admin. Please review and work on it again.', 1, '2026-09-04 07:14:37'),
(89, NULL, NULL, 1, 'Reopen request #2 approved for Complaint #24.', 0, '2026-09-04 07:14:37'),
(93, NULL, NULL, 1, 'Staff member Rahul Staff has completed complaint CMP-024. The complaint has been marked Resolved.', 0, '2026-09-04 07:16:03'),
(95, NULL, NULL, 1, 'Staff member Rahul Staff has completed complaint CMP-016. The complaint has been marked Resolved.', 0, '2026-09-04 07:16:48'),
(96, NULL, NULL, 1, 'New complaint CMP-025 | 4th Floor - Computer Lab 1 | IT / Computer', 0, '2026-10-03 18:38:38'),
(97, NULL, 1, NULL, 'New complaint CMP-025 is available in your department. Any related staff member can accept it.', 1, '2026-10-03 18:38:38'),
(98, 101, NULL, NULL, 'Staff member has accepted your complaint CMP-025.', 0, '2026-10-03 18:39:29'),
(99, 101, NULL, NULL, 'Work has started on your complaint CMP-025.', 0, '2026-10-03 18:39:47'),
(100, 101, NULL, NULL, 'Your complaint CMP-025 has been resolved successfully. You can now rate the staff member.', 0, '2026-10-03 18:40:22'),
(101, NULL, NULL, 1, 'Complaint CMP-025 has been completed by staff Rahul Staff.', 0, '2026-10-03 18:40:22'),
(102, NULL, NULL, 1, 'Student has requested reopening of Complaint #25 (IT / Computer complaint at Computer Lab 1).', 0, '2026-10-03 18:41:29'),
(103, 101, NULL, NULL, 'Your reopen request for Complaint #25 has been approved by admin. The complaint has been reopened.', 0, '2026-10-03 18:41:58'),
(104, NULL, 1, NULL, 'Complaint #25 has been reopened by admin. Please review and work on it again.', 1, '2026-10-03 18:41:58'),
(105, NULL, NULL, 1, 'Reopen request #3 approved for Complaint #25.', 0, '2026-10-03 18:41:58'),
(106, 101, NULL, NULL, 'Work has started on your complaint CMP-025.', 0, '2026-10-03 19:10:59'),
(107, 101, NULL, NULL, 'Your complaint CMP-025 has been resolved successfully. You can now rate the staff member.', 0, '2026-10-03 19:11:14'),
(108, NULL, NULL, 1, 'Complaint CMP-025 has been completed by staff Rahul Staff.', 0, '2026-10-03 19:11:14'),
(109, NULL, NULL, 1, 'New complaint CMP-026 | 4th Floor - Computer Lab 6 | IT / Computer', 0, '2026-10-03 21:18:03'),
(110, NULL, 1, NULL, 'New complaint CMP-026 is available in your department. Any related staff member can accept it.', 0, '2026-10-03 21:18:03');

-- --------------------------------------------------------

--
-- Table structure for table `ratings`
--

CREATE TABLE `ratings` (
  `id` int(11) NOT NULL,
  `complaint_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `staff_id` int(11) NOT NULL,
  `rating` int(11) NOT NULL,
  `feedback` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ratings`
--

INSERT INTO `ratings` (`id`, `complaint_id`, `student_id`, `staff_id`, `rating`, `feedback`, `created_at`) VALUES
(1, 1, 1, 1, 4, 'good work by him', '2026-08-28 11:08:00'),
(2, 2, 1, 1, 3, '', '2026-08-28 11:11:20'),
(3, 5, 1, 1, 4, 'good work', '2026-08-28 13:28:41'),
(4, 13, 1, 1, 4, 'good work my boy', '2026-08-30 18:28:36'),
(5, 12, 1, 1, 5, 'good work my boy keep it up....!', '2026-08-30 18:29:39'),
(6, 19, 1, 1, 5, 'good and very fast work done by this staff', '2026-09-02 18:06:39'),
(7, 18, 1, 1, 4, 'good work by this staff', '2026-09-02 18:08:06'),
(8, 23, 2, 1, 5, 'GOOD WORK', '2026-09-03 20:19:53'),
(9, 24, 3, 1, 5, 'good work by this staff', '2026-09-04 07:31:12'),
(10, 25, 101, 1, 5, 'good work done my staff', '2026-10-03 19:12:54');

-- --------------------------------------------------------

--
-- Table structure for table `reopen_requests`
--

CREATE TABLE `reopen_requests` (
  `id` int(11) NOT NULL,
  `complaint_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `staff_id` int(11) NOT NULL,
  `reason` varchar(1000) NOT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'Pending',
  `admin_remark` varchar(1000) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reviewed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reopen_requests`
--

INSERT INTO `reopen_requests` (`id`, `complaint_id`, `student_id`, `staff_id`, `reason`, `photo`, `status`, `admin_remark`, `created_at`, `reviewed_at`) VALUES
(1, 24, 3, 1, 'still pc is not working fake recovery', 'reopen_f2634abaf0438b86fc2db0a1c5704c32.jpg', 'Approved', 'do this work man and tell me after that is this is still working or not', '2026-09-04 06:53:19', '2026-09-04 06:54:20'),
(2, 24, 3, 1, 'do recover my pc again', 'reopen_13b78bf7ba1da8f9b223ce268de06d7b.jpg', 'Approved', 'do recover this pc again', '2026-09-04 07:14:12', '2026-09-04 07:14:37'),
(3, 25, 101, 1, 'again pc is not working here', 'reopen_47962dff2995a6221365ee55d9c045ff.jpg', 'Approved', 'do the work as soon as possible', '2026-10-03 18:41:29', '2026-10-03 18:41:58');

-- --------------------------------------------------------

--
-- Table structure for table `staff`
--

CREATE TABLE `staff` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `staff_id` varchar(50) NOT NULL,
  `mobile` varchar(15) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `status` enum('Present','Absent') DEFAULT 'Present',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `approval_status` varchar(20) NOT NULL DEFAULT 'Approved'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staff`
--

INSERT INTO `staff` (`id`, `name`, `staff_id`, `mobile`, `department`, `username`, `password`, `status`, `created_at`, `approval_status`) VALUES
(1, 'Rahul Staff', 'STF001', '9876543210', 'IT Department', 'rahul', '$2y$10$pISCY/GZwrj2Elyc8STx1ezcjafXwKvBSb78eyl1TQQPegI44H1Cq', '', '2026-08-15 12:14:13', 'Approved'),
(30, 'Amit Electrical', 'STF002', NULL, 'ELECTRICAL', 'amit', 'staff123', 'Present', '2026-08-29 18:37:05', 'Approved'),
(31, 'Suresh Plumbing', 'STF003', NULL, 'PLUMBING', 'suresh', 'staff123', 'Present', '2026-08-29 18:37:05', 'Approved'),
(32, 'Mahesh Lab', 'STF004', NULL, 'LABORATORY', 'mahesh', 'staff123', 'Present', '2026-08-29 18:37:05', 'Approved'),
(33, 'Priya Library', 'STF005', NULL, 'LIBRARY', 'priya', 'staff123', 'Present', '2026-08-29 18:37:05', 'Approved'),
(34, 'Ramesh Ground', 'STF006', NULL, 'CLEANLINESS', 'ramesh', 'staff123', 'Present', '2026-08-29 18:37:05', 'Approved'),
(35, 'Sunil 1st Floor', 'STF007', NULL, 'CLEANLINESS', 'sunil', 'staff123', 'Present', '2026-08-29 18:37:05', 'Approved'),
(36, 'Vijay 2nd Floor', 'STF008', NULL, 'CLEANLINESS', 'vijay', 'staff123', 'Present', '2026-08-29 18:37:05', 'Approved'),
(37, 'Akash 3rd Floor', 'STF009', NULL, 'CLEANLINESS', 'akash', 'staff123', 'Present', '2026-08-29 18:37:05', 'Approved'),
(38, 'Manoj 4th Floor', 'STF010', NULL, 'CLEANLINESS', 'manoj', 'staff123', 'Present', '2026-08-29 18:37:05', 'Approved');

-- --------------------------------------------------------

--
-- Table structure for table `staff_ratings`
--

CREATE TABLE `staff_ratings` (
  `id` int(11) NOT NULL,
  `complaint_id` int(11) NOT NULL,
  `staff_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `rating` int(11) NOT NULL,
  `feedback` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `roll_no` varchar(50) NOT NULL,
  `mobile` varchar(15) DEFAULT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` varchar(20) NOT NULL DEFAULT 'Approved'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `name`, `roll_no`, `mobile`, `username`, `password`, `created_at`, `status`) VALUES
(101, 'swayam vinayak rode', 'TCS2627072', '8108042830', 'swayam123', '$2y$10$IrCLArumYpyVK28W0o6Sye3AlmYc6NDaPJeDsshC/rhq7Y34Gdebe', '2026-10-03 18:35:16', 'Approved');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `assignments`
--
ALTER TABLE `assignments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `complaint_id` (`complaint_id`),
  ADD KEY `staff_id` (`staff_id`),
  ADD KEY `assigned_by` (`assigned_by`);

--
-- Indexes for table `complaints`
--
ALTER TABLE `complaints`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `complaint_warnings`
--
ALTER TABLE `complaint_warnings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `feedback`
--
ALTER TABLE `feedback`
  ADD PRIMARY KEY (`id`),
  ADD KEY `complaint_id` (`complaint_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `staff_id` (`staff_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `staff_id` (`staff_id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `ratings`
--
ALTER TABLE `ratings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `reopen_requests`
--
ALTER TABLE `reopen_requests`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `staff`
--
ALTER TABLE `staff`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `staff_id` (`staff_id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `staff_ratings`
--
ALTER TABLE `staff_ratings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `roll_no` (`roll_no`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `assignments`
--
ALTER TABLE `assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `complaints`
--
ALTER TABLE `complaints`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `complaint_warnings`
--
ALTER TABLE `complaint_warnings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `feedback`
--
ALTER TABLE `feedback`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=111;

--
-- AUTO_INCREMENT for table `ratings`
--
ALTER TABLE `ratings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `reopen_requests`
--
ALTER TABLE `reopen_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `staff`
--
ALTER TABLE `staff`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `staff_ratings`
--
ALTER TABLE `staff_ratings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=102;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `assignments`
--
ALTER TABLE `assignments`
  ADD CONSTRAINT `assignments_ibfk_1` FOREIGN KEY (`complaint_id`) REFERENCES `complaints` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `assignments_ibfk_2` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `assignments_ibfk_3` FOREIGN KEY (`assigned_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `complaints`
--
ALTER TABLE `complaints`
  ADD CONSTRAINT `complaints_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `feedback`
--
ALTER TABLE `feedback`
  ADD CONSTRAINT `feedback_ibfk_1` FOREIGN KEY (`complaint_id`) REFERENCES `complaints` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `feedback_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `feedback_ibfk_3` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `notifications_ibfk_2` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `notifications_ibfk_3` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
