--
-- Table structure for table `applications`
--

CREATE TABLE `applications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `status` enum('pending','approved','rejected','enrolled','certificates_issued') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `personal_statement` text DEFAULT NULL,
  `previous_qualification_1` varchar(255) DEFAULT NULL,
  `previous_qualification_2` varchar(255) DEFAULT NULL,
  `academic_history` text DEFAULT NULL,
  `present_enrolled_course` varchar(255) DEFAULT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `approved` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `applications`
--

INSERT INTO `applications` (`id`, `user_id`, `course_id`, `status`, `created_at`, `personal_statement`, `previous_qualification_1`, `previous_qualification_2`, `academic_history`, `present_enrolled_course`, `submitted_at`, `updated_at`, `approved`) VALUES
(13, 7, 1, 'enrolled', '2026-02-03 07:12:31', NULL, NULL, NULL, NULL, NULL, '2026-02-03 07:12:31', '2026-02-03 09:08:27', 0),
(15, 7, 3, 'enrolled', '2026-02-03 07:16:36', NULL, NULL, NULL, NULL, NULL, '2026-02-03 07:16:36', '2026-02-03 09:07:23', 0);

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `id` int(11) NOT NULL,
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
  `program_fee` decimal(10,2) DEFAULT 2000.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `courses`
--

INSERT INTO `courses` (`id`, `title`, `description`, `image_url`, `registration_fee`, `programme_fee`, `duration`, `start_date`, `status`, `created_at`, `updated_at`, `category`, `program_fee`) VALUES
(1, 'Advanced Project Management', 'Master agile methodologies and lead complex projects to successful completion. This comprehensive course covers project planning, risk management, team leadership, and stakeholder communication.', 'https://de.freepik.com/fotos-kostenlos/nahaufnahme-von-server-hub-it-profi-debugging-und-optimierung-von-code_412395059.htm#fromView=search&page=1&position=2&uuid=71464b02-e424-4edb-8f11-7a20d8357695&query=it', 500.00, 35000.00, '6 mon', '2026-03-01', 'active', '2026-01-21 14:12:13', '2026-02-02 11:46:47', 'Management', 2000.00),
(2, 'Digital Marketing Strategy', 'Learn cutting-edge digital marketing techniques including SEO, social media marketing, content strategy, and analytics to drive business growth in the modern digital landscape.', 'course2.jpg', 500.00, 32000.00, '5 months', '2026-03-15', 'active', '2026-01-21 14:12:13', '2026-01-22 12:03:28', 'Marketing', 2000.00),
(3, 'Data Science & Analytics', 'Comprehensive training in data analysis, machine learning, statistical modeling, and big data technologies. Perfect for aspiring data scientists and analysts.', 'course3.jpg', 500.00, 40000.00, '8 months', '2026-04-01', 'active', '2026-01-21 14:12:13', '2026-01-22 12:03:28', 'Data Science', 2000.00),
(4, 'Business Administration', 'Develop essential business skills including strategic management, finance, operations, and leadership to excel in today\'s competitive business environment.', 'course4.jpg', 500.00, 38000.00, '7 months', '2026-03-20', 'active', '2026-01-21 14:12:13', '2026-01-22 12:03:28', 'Management', 2000.00),
(5, 'Web Development Bootcamp', 'Intensive full-stack web development training covering HTML, CSS, JavaScript, React, Node.js, databases, and deployment. Build real-world applications.', 'course5.jpg', 500.00, 30000.00, '4 months', '2026-04-10', 'active', '2026-01-21 14:12:13', '2026-01-22 12:03:28', 'Web Devlopment', 2000.00),
(6, 'Cybersecurity Professional', 'Master network security, ethical hacking, threat analysis, and security architecture. Prepare for industry certifications and protect organizations from cyber threats.', 'course6.jpg', 500.00, 42000.00, '6 months', '2026-03-25', 'active', '2026-01-21 14:12:13', '2026-01-22 12:03:28', 'IT', 2000.00);

-- --------------------------------------------------------

--
-- Table structure for table `documents`
--

CREATE TABLE `documents` (
  `id` int(11) NOT NULL,
  `application_id` int(11) NOT NULL,
  `document_type` enum('university_transcript','personal_statement','proof_of_identity','other') NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_size` int(11) DEFAULT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `verified_by` int(11) DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `documents`
--

INSERT INTO `documents` (`id`, `application_id`, `document_type`, `file_name`, `file_path`, `file_size`, `mime_type`, `uploaded_at`, `status`, `verified_by`, `verified_at`, `rejection_reason`) VALUES
(13, 13, 'university_transcript', 'invoice-04742-19014136_canva.pdf', 'uploads/documents/university_transcript_13_1770102818.pdf', 417689, NULL, '2026-02-03 07:13:38', 'pending', NULL, NULL, NULL),
(14, 13, 'personal_statement', 'Assessment_Question_Answers_RPS_18122025 1.pdf', 'uploads/documents/personal_statement_13_1770102818.pdf', 250202, NULL, '2026-02-03 07:13:38', 'pending', NULL, NULL, NULL),
(15, 13, 'proof_of_identity', 'Manicharan_Ravi_CV.pdf', 'uploads/documents/proof_of_identity_13_1770102818.pdf', 176038, NULL, '2026-02-03 07:13:38', 'pending', NULL, NULL, NULL),
(19, 15, 'university_transcript', 'Manicharan_Ravi_europass (1).pdf', 'uploads/documents/university_transcript_15_1770103021.pdf', 516660, NULL, '2026-02-03 07:17:01', 'pending', NULL, NULL, NULL),
(20, 15, 'personal_statement', 'Manicharan_ravi_resume.pdf', 'uploads/documents/personal_statement_15_1770103021.pdf', 285841, NULL, '2026-02-03 07:17:01', 'pending', NULL, NULL, NULL),
(21, 15, 'proof_of_identity', 'Manicharan_Ravi_europass (1).pdf', 'uploads/documents/proof_of_identity_15_1770103021.pdf', 516660, NULL, '2026-02-03 07:17:01', 'pending', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` enum('info','success','warning','error') DEFAULT 'info',
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `user_id`, `application_id`, `amount`, `payment_type`, `payment_method`, `transaction_id`, `payment_proof`, `status`, `approved_by`, `approved_at`, `created_at`) VALUES
(16, 7, 13, 500.00, 'registration', 'bank_transfer', 'htrhrt', 'uploads/payments/payment_7_1770106808.pdf', 'completed', 6, '2026-02-03 08:20:24', '2026-02-03 08:20:08'),
(17, 7, 13, 2000.00, 'program', 'credit_card', 'htrhrt', 'uploads/payments/payment_7_1770106839.pdf', 'completed', 6, '2026-02-03 08:20:46', '2026-02-03 08:20:39'),
(18, 7, 15, 500.00, 'registration', 'credit_card', 'htrhrt', 'uploads/payments/payment_7_1770107000.pdf', 'completed', 6, '2026-02-03 08:23:27', '2026-02-03 08:23:20'),
(19, 7, 15, 2000.00, 'program', 'bank_transfer', 'htrhrt', 'uploads/payments/payment_7_1770107034.pdf', 'completed', 6, '2026-02-03 08:24:03', '2026-02-03 08:23:54');

-- --------------------------------------------------------

--
-- Table structure for table `support_tickets`
--

CREATE TABLE `support_tickets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `category` varchar(50) NOT NULL,
  `priority` enum('low','medium','high') NOT NULL DEFAULT 'medium',
  `message` text NOT NULL,
  `status` enum('open','closed') NOT NULL DEFAULT 'open',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) DEFAULT NULL,
  `Phone_Number` varchar(20) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `college` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `postcode` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL,
  `present_enrolled_course` varchar(255) DEFAULT NULL,
  `approved` tinyint(1) DEFAULT 1,
  `role` enum('student','admin') NOT NULL DEFAULT 'student'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `full_name`, `email`, `password_hash`, `Phone_Number`, `date_of_birth`, `college`, `address`, `city`, `postcode`, `created_at`, `updated_at`, `present_enrolled_course`, `approved`, `role`) VALUES
(6, 'Manicharan', 'admin@indoeurosync.com', '$2y$10$e.4VgYuPM2E3FegZmhXszeqhyLz0INu6rxELw9F3YTLzYRj9q0ONi', NULL, '1999-08-13', NULL, NULL, NULL, NULL, '2026-01-30 10:45:38', '2026-02-03 12:52:38', NULL, 1, 'admin'),
(7, 'Manicharan', 'admin@example.com', '$2y$10$InRZ4iRQWQ42NRzLAmIkCuIfnehIMwV0kRbyerwXYq0KuiQl4VdoK', '+4917634657173', '1999-08-13', 'IU university', 'Dreiweidenstraße 1', 'Wiesbaden', '65195', '2026-01-30 12:10:05', '2026-01-31 11:56:16', NULL, 1, 'student'),
(11, 'Manicharan Ravi', 'miller1@rpsonline.de', '$2y$10$ZnmfAGWGwe2CWchjFwsJAOhmvE/h.ozVaokhi5l2YxTDVQCHNfyE2', '+4917634657173', '1999-08-13', 'm.ravi@rpsonline.de', NULL, NULL, NULL, '2026-02-03 09:19:28', NULL, NULL, 1, 'student'),
(12, 'Manicharan Ravi', 'm.ravi@rpsonline.de', '$2y$10$A.8GW1Z8HGvi1ROivrzLwOF.kK4NEpD5mEoZ0H2AMfDM2nf9nP7f.', '+4917634657173', '1999-08-13', 'iu', NULL, NULL, NULL, '2026-02-03 09:20:01', NULL, NULL, 1, 'student'),
(13, 'Manicharan Ravi', 'm.schneemann@rpsonline.de', '$2y$10$P7nn.ytY0emI1nZY8xP5m.5.ozdhdQekkZCn6kCY1v3LPWlnYliOi', '8952226636', '1999-09-12', 'iu', NULL, NULL, NULL, '2026-02-03 09:21:31', NULL, NULL, 1, 'student'),
(14, 'Manicharan Ravi', 'manitester143@gmail.com', '$2y$10$BFaB3IIcJYVgoQnehbdIceDEiiSbCjTqUtrXOvnfdD0gMvRR4AmOe', '+497444894844', '1999-08-12', 'iu', NULL, NULL, NULL, '2026-02-03 09:24:46', NULL, NULL, 1, 'student');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `applications`
--
ALTER TABLE `applications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `idx_user_course` (`user_id`,`course_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `documents`
--
ALTER TABLE `documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_application` (`application_id`),
  ADD KEY `verified_by` (`verified_by`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_read` (`user_id`,`is_read`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `application_id` (`application_id`);

--
-- Indexes for table `support_tickets`
--
ALTER TABLE `support_tickets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `applications`
--
ALTER TABLE `applications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `documents`
--
ALTER TABLE `documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `support_tickets`
--
ALTER TABLE `support_tickets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

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
  ADD CONSTRAINT `documents_ibfk_1` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `documents_ibfk_2` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

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
COMMIT;


