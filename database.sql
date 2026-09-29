-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 26, 2026 at 07:45 AM
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
-- Database: `course_enrollment`
--
CREATE DATABASE IF NOT EXISTS `course_enrollment` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `course_enrollment`;

-- --------------------------------------------------------

--
-- Table structure for table `admin_users`
--
-- Creation: Jan 21, 2026 at 03:12 PM
--

DROP TABLE IF EXISTS `admin_users`;
CREATE TABLE IF NOT EXISTS `admin_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','superadmin') DEFAULT 'admin',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELATIONSHIPS FOR TABLE `admin_users`:
--

--
-- Truncate table before insert `admin_users`
--

TRUNCATE TABLE `admin_users`;
--
-- Dumping data for table `admin_users`
--

INSERT DELAYED IGNORE INTO `admin_users` (`id`, `username`, `email`, `password`, `role`, `created_at`, `last_login`) VALUES
(4, 'Admin User', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', '2026-01-22 08:49:32', '2026-01-23 09:56:29');

-- --------------------------------------------------------

--
-- Table structure for table `applications`
--
-- Creation: Jan 22, 2026 at 03:43 PM
--

DROP TABLE IF EXISTS `applications`;
CREATE TABLE IF NOT EXISTS `applications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `status` enum('pending','approved','rejected','enrolled') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `personal_statement` text DEFAULT NULL,
  `previous_qualification_1` varchar(255) DEFAULT NULL,
  `previous_qualification_2` varchar(255) DEFAULT NULL,
  `academic_history` text DEFAULT NULL,
  `present_enrolled_course` varchar(255) DEFAULT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `approved` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `course_id` (`course_id`),
  KEY `idx_user_course` (`user_id`,`course_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELATIONSHIPS FOR TABLE `applications`:
--   `user_id`
--       `users` -> `id`
--   `course_id`
--       `courses` -> `id`
--

--
-- Truncate table before insert `applications`
--

TRUNCATE TABLE `applications`;
--
-- Dumping data for table `applications`
--

INSERT DELAYED IGNORE INTO `applications` (`id`, `user_id`, `course_id`, `status`, `created_at`, `personal_statement`, `previous_qualification_1`, `previous_qualification_2`, `academic_history`, `present_enrolled_course`, `submitted_at`, `updated_at`, `approved`) VALUES
(3, 2, 1, 'approved', '2026-01-22 15:47:52', NULL, NULL, NULL, NULL, NULL, '2026-01-22 15:47:52', '2026-01-22 16:19:36', 0);

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--
-- Creation: Jan 22, 2026 at 01:03 PM
--

DROP TABLE IF EXISTS `courses`;
CREATE TABLE IF NOT EXISTS `courses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `registration_fee` decimal(10,2) NOT NULL,
  `programme_fee` decimal(10,2) NOT NULL,
  `duration` varchar(100) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `status` enum('active','inactive','archived') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `category` varchar(100) NOT NULL DEFAULT 'General',
  `program_fee` decimal(10,2) DEFAULT 2000.00,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELATIONSHIPS FOR TABLE `courses`:
--

--
-- Truncate table before insert `courses`
--

TRUNCATE TABLE `courses`;
--
-- Dumping data for table `courses`
--

INSERT DELAYED IGNORE INTO `courses` (`id`, `title`, `description`, `image_url`, `registration_fee`, `programme_fee`, `duration`, `start_date`, `status`, `created_at`, `updated_at`, `category`, `program_fee`) VALUES
(1, 'Advanced Project Management', 'Master agile methodologies and lead complex projects to successful completion. This comprehensive course covers project planning, risk management, team leadership, and stakeholder communication.', 'course1.jpg', 500.00, 35000.00, '6 mon', '2026-03-01', 'active', '2026-01-21 15:12:13', '2026-01-22 13:03:28', 'Management', 2000.00),
(2, 'Digital Marketing Strategy', 'Learn cutting-edge digital marketing techniques including SEO, social media marketing, content strategy, and analytics to drive business growth in the modern digital landscape.', 'course2.jpg', 500.00, 32000.00, '5 months', '2026-03-15', 'active', '2026-01-21 15:12:13', '2026-01-22 13:03:28', 'Marketing', 2000.00),
(3, 'Data Science & Analytics', 'Comprehensive training in data analysis, machine learning, statistical modeling, and big data technologies. Perfect for aspiring data scientists and analysts.', 'course3.jpg', 500.00, 40000.00, '8 months', '2026-04-01', 'active', '2026-01-21 15:12:13', '2026-01-22 13:03:28', 'Data Science', 2000.00),
(4, 'Business Administration', 'Develop essential business skills including strategic management, finance, operations, and leadership to excel in today\'s competitive business environment.', 'course4.jpg', 500.00, 38000.00, '7 months', '2026-03-20', 'active', '2026-01-21 15:12:13', '2026-01-22 13:03:28', 'Management', 2000.00),
(5, 'Web Development Bootcamp', 'Intensive full-stack web development training covering HTML, CSS, JavaScript, React, Node.js, databases, and deployment. Build real-world applications.', 'course5.jpg', 500.00, 30000.00, '4 months', '2026-04-10', 'active', '2026-01-21 15:12:13', '2026-01-22 13:03:28', 'Web Devlopment', 2000.00),
(6, 'Cybersecurity Professional', 'Master network security, ethical hacking, threat analysis, and security architecture. Prepare for industry certifications and protect organizations from cyber threats.', 'course6.jpg', 500.00, 42000.00, '6 months', '2026-03-25', 'active', '2026-01-21 15:12:13', '2026-01-22 13:03:28', 'IT', 2000.00);

-- --------------------------------------------------------

--
-- Table structure for table `documents`
--
-- Creation: Jan 21, 2026 at 03:12 PM
--

DROP TABLE IF EXISTS `documents`;
CREATE TABLE IF NOT EXISTS `documents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `application_id` int(11) NOT NULL,
  `document_type` enum('university_transcript','personal_statement','proof_of_identity','other') NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_size` int(11) DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_application` (`application_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELATIONSHIPS FOR TABLE `documents`:
--   `application_id`
--       `applications` -> `id`
--

--
-- Truncate table before insert `documents`
--

TRUNCATE TABLE `documents`;
-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--
-- Creation: Jan 21, 2026 at 03:12 PM
--

DROP TABLE IF EXISTS `notifications`;
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` enum('info','success','warning','error') DEFAULT 'info',
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_read` (`user_id`,`is_read`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELATIONSHIPS FOR TABLE `notifications`:
--   `user_id`
--       `users` -> `id`
--

--
-- Truncate table before insert `notifications`
--

TRUNCATE TABLE `notifications`;
--
-- Dumping data for table `notifications`
--

INSERT DELAYED IGNORE INTO `notifications` (`id`, `user_id`, `title`, `message`, `type`, `is_read`, `created_at`) VALUES
(1, 1, 'Application Updated', 'Your application has been updated successfully. Proceed to payment.', 'success', 0, '2026-01-22 12:30:19'),
(2, 1, 'Application Updated', 'Your application has been updated successfully. Proceed to payment.', 'success', 0, '2026-01-22 12:31:03'),
(3, 1, 'Payment Screenshot Uploaded', 'Your payment screenshot has been uploaded. Our team will verify it shortly.', 'info', 0, '2026-01-22 12:34:36'),
(4, 1, 'Payment Screenshot Uploaded', 'Your payment screenshot has been uploaded. Our team will verify it shortly.', 'info', 0, '2026-01-22 12:47:52'),
(5, 1, 'Application Updated', 'Your application has been updated successfully. Proceed to payment.', 'success', 0, '2026-01-22 13:42:42');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--
-- Creation: Jan 21, 2026 at 03:12 PM
--

DROP TABLE IF EXISTS `payments`;
CREATE TABLE IF NOT EXISTS `payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `application_id` int(11) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_type` enum('registration','program') NOT NULL DEFAULT 'registration',
  `payment_method` varchar(100) DEFAULT NULL,
  `transaction_id` varchar(255) DEFAULT NULL,
  `payment_proof` varchar(500) DEFAULT NULL,
  `status` enum('pending','completed','rejected') NOT NULL DEFAULT 'pending',
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `application_id` (`application_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELATIONSHIPS FOR TABLE `payments`:
--   `user_id`
--       `users` -> `id`
--   `application_id`
--       `applications` -> `id`
--

--
-- Truncate table before insert `payments`
--

TRUNCATE TABLE `payments`;
--
-- Dumping data for table `payments`
--

INSERT DELAYED IGNORE INTO `payments` (`id`, `user_id`, `application_id`, `amount`, `payment_type`, `payment_method`, `transaction_id`, `payment_proof`, `status`, `approved_by`, `approved_at`, `created_at`) VALUES
(3, 2, 3, 500.00, 'registration', 'other', '1485698944', 'uploads/payments/payment_2_1769096891.pdf', 'completed', 4, '2026-01-22 15:48:38', '2026-01-22 15:48:11'),
(4, 2, 3, 2000.00, 'program', 'other', '3r43r2r32r', 'uploads/payments/payment_2_1769096946.pdf', 'completed', 4, '2026-01-22 15:49:18', '2026-01-22 15:49:06');

-- --------------------------------------------------------

--
-- Table structure for table `support_tickets`
--
-- Creation: Jan 22, 2026 at 10:20 AM
--

DROP TABLE IF EXISTS `support_tickets`;
CREATE TABLE IF NOT EXISTS `support_tickets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `category` varchar(50) NOT NULL,
  `priority` enum('low','medium','high') NOT NULL DEFAULT 'medium',
  `message` text NOT NULL,
  `status` enum('open','closed') NOT NULL DEFAULT 'open',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELATIONSHIPS FOR TABLE `support_tickets`:
--   `user_id`
--       `users` -> `id`
--

--
-- Truncate table before insert `support_tickets`
--

TRUNCATE TABLE `support_tickets`;
-- --------------------------------------------------------

--
-- Table structure for table `users`
--
-- Creation: Jan 22, 2026 at 03:42 PM
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `full_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `Phone_Number` varchar(255) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `college` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `postcode` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `present_enrolled_course` varchar(255) DEFAULT NULL,
  `approved` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELATIONSHIPS FOR TABLE `users`:
--

--
-- Truncate table before insert `users`
--

TRUNCATE TABLE `users`;
--
-- Dumping data for table `users`
--

INSERT DELAYED IGNORE INTO `users` (`id`, `full_name`, `email`, `password`, `Phone_Number`, `date_of_birth`, `college`, `address`, `city`, `postcode`, `created_at`, `updated_at`, `present_enrolled_course`, `approved`) VALUES
(1, 'manicharan', 'miller1@example.com', '$2y$10$0syJKr.pj0g.jqZAt343zefB0pUEHxCzfNwCgIYmnMRs31rP3rd9G', NULL, '2026-01-24', NULL, 'Mainz,Germany', 'Mainz', '55118', '2026-01-21 15:57:02', '2026-01-22 12:17:48', NULL, 0),
(2, 'manicharan', 'hedek16276@elafans.com', '$2y$10$5QhebATxdOklfxbwArFdvun.lFclOXRcI3eXqt20/x8oerWmSNQ0C', NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-22 15:47:36', '2026-01-22 15:47:36', NULL, 0);

--
-- Constraints for dumped tables
--

--
-- Constraints for table `applications`
--
ALTER TABLE `applications`
  ADD CONSTRAINT `applications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `applications_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `documents`
--
ALTER TABLE `documents`
  ADD CONSTRAINT `documents_ibfk_1` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `support_tickets`
--
ALTER TABLE `support_tickets`
  ADD CONSTRAINT `fk_support_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;


--
-- Metadata
--
USE `phpmyadmin`;

--
-- Metadata for table admin_users
--

--
-- Truncate table before insert `pma__column_info`
--

TRUNCATE TABLE `pma__column_info`;
--
-- Truncate table before insert `pma__table_uiprefs`
--

TRUNCATE TABLE `pma__table_uiprefs`;
--
-- Truncate table before insert `pma__tracking`
--

TRUNCATE TABLE `pma__tracking`;
--
-- Metadata for table applications
--

--
-- Truncate table before insert `pma__column_info`
--

TRUNCATE TABLE `pma__column_info`;
--
-- Truncate table before insert `pma__table_uiprefs`
--

TRUNCATE TABLE `pma__table_uiprefs`;
--
-- Truncate table before insert `pma__tracking`
--

TRUNCATE TABLE `pma__tracking`;
--
-- Metadata for table courses
--

--
-- Truncate table before insert `pma__column_info`
--

TRUNCATE TABLE `pma__column_info`;
--
-- Truncate table before insert `pma__table_uiprefs`
--

TRUNCATE TABLE `pma__table_uiprefs`;
--
-- Dumping data for table `pma__table_uiprefs`
--

INSERT DELAYED IGNORE INTO `pma__table_uiprefs` (`username`, `db_name`, `table_name`, `prefs`, `last_update`) VALUES
('root', 'course_enrollment', 'courses', '{\"sorted_col\":\"`courses`.`id` ASC\"}', '2026-01-22 15:20:29');

--
-- Truncate table before insert `pma__tracking`
--

TRUNCATE TABLE `pma__tracking`;
--
-- Metadata for table documents
--

--
-- Truncate table before insert `pma__column_info`
--

TRUNCATE TABLE `pma__column_info`;
--
-- Truncate table before insert `pma__table_uiprefs`
--

TRUNCATE TABLE `pma__table_uiprefs`;
--
-- Truncate table before insert `pma__tracking`
--

TRUNCATE TABLE `pma__tracking`;
--
-- Metadata for table notifications
--

--
-- Truncate table before insert `pma__column_info`
--

TRUNCATE TABLE `pma__column_info`;
--
-- Truncate table before insert `pma__table_uiprefs`
--

TRUNCATE TABLE `pma__table_uiprefs`;
--
-- Truncate table before insert `pma__tracking`
--

TRUNCATE TABLE `pma__tracking`;
--
-- Metadata for table payments
--

--
-- Truncate table before insert `pma__column_info`
--

TRUNCATE TABLE `pma__column_info`;
--
-- Truncate table before insert `pma__table_uiprefs`
--

TRUNCATE TABLE `pma__table_uiprefs`;
--
-- Truncate table before insert `pma__tracking`
--

TRUNCATE TABLE `pma__tracking`;
--
-- Metadata for table support_tickets
--

--
-- Truncate table before insert `pma__column_info`
--

TRUNCATE TABLE `pma__column_info`;
--
-- Truncate table before insert `pma__table_uiprefs`
--

TRUNCATE TABLE `pma__table_uiprefs`;
--
-- Truncate table before insert `pma__tracking`
--

TRUNCATE TABLE `pma__tracking`;
--
-- Metadata for table users
--

--
-- Truncate table before insert `pma__column_info`
--

TRUNCATE TABLE `pma__column_info`;
--
-- Truncate table before insert `pma__table_uiprefs`
--

TRUNCATE TABLE `pma__table_uiprefs`;
--
-- Truncate table before insert `pma__tracking`
--

TRUNCATE TABLE `pma__tracking`;
--
-- Metadata for database course_enrollment
--

--
-- Truncate table before insert `pma__bookmark`
--

TRUNCATE TABLE `pma__bookmark`;
--
-- Truncate table before insert `pma__relation`
--

TRUNCATE TABLE `pma__relation`;
--
-- Truncate table before insert `pma__savedsearches`
--

TRUNCATE TABLE `pma__savedsearches`;
--
-- Truncate table before insert `pma__central_columns`
--

TRUNCATE TABLE `pma__central_columns`;COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
-- End of dump