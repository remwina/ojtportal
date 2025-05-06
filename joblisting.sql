-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 05, 2025 at 05:04 PM
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
-- Database: `joblisting`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_authenticate_user` (IN `p_email` VARCHAR(255))   BEGIN
    SELECT u.id, u.srcode, u.email, u.password, u.usertype, u.status 
    FROM users u 
    WHERE u.email = p_email 
    AND u.status = 'active' 
    LIMIT 1;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_create_user` (IN `p_srcode` VARCHAR(9), IN `p_firstname` VARCHAR(50), IN `p_lastname` VARCHAR(50), IN `p_email` VARCHAR(255), IN `p_password` VARCHAR(255), IN `p_course_id` INT, IN `p_section` VARCHAR(20), IN `p_usertype` ENUM('admin','user','none'), IN `p_status` ENUM('active','inactive'))   BEGIN
    INSERT INTO users (
        srcode, firstname, lastname, email, password, 
        course_id, section, usertype, status
    ) VALUES (
        p_srcode, p_firstname, p_lastname, p_email, p_password,
        p_course_id, p_section, p_usertype, p_status
    );
    SELECT LAST_INSERT_ID() as user_id;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_get_active_companies_count` ()   BEGIN
    SELECT COUNT(*) as count 
    FROM companies 
    WHERE status = 'active';
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_get_available_positions_count` ()   BEGIN
    SELECT COUNT(*) as count 
    FROM job_listings 
    WHERE status = 'open' 
    AND (expires_at IS NULL OR expires_at >= CURDATE());
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_get_courses_by_department` (IN `p_department_id` INT)   BEGIN
    SELECT * FROM courses WHERE department_id = p_department_id ORDER BY name;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_get_departments` ()   BEGIN
    SELECT * FROM departments ORDER BY name;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_get_job_details` (IN `p_job_id` INT)   BEGIN
    SELECT jl.*, c.* 
    FROM job_listings jl 
    JOIN companies c ON jl.company_id = c.id 
    WHERE jl.id = p_job_id;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_get_job_listings` ()   BEGIN
    SELECT jl.*, c.name as company_name, c.logo_path 
    FROM job_listings jl 
    JOIN companies c ON jl.company_id = c.id 
    WHERE jl.status = 'open' 
    AND (jl.expires_at IS NULL OR jl.expires_at >= CURDATE())
    ORDER BY jl.created_at DESC;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_get_user_active_applications_count` (IN `p_user_id` INT)   BEGIN
    SELECT COUNT(*) as count 
    FROM job_applications 
    WHERE user_id = p_user_id 
    AND status != 'rejected';
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_get_user_applications` (IN `p_user_id` INT)   BEGIN
    SELECT ja.*, jl.title, c.name as company_name, c.logo_path 
    FROM job_applications ja 
    JOIN job_listings jl ON ja.job_id = jl.id
    JOIN companies c ON jl.company_id = c.id
    WHERE ja.user_id = p_user_id
    ORDER BY ja.created_at DESC;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_submit_application` (IN `p_user_id` INT, IN `p_job_id` INT, IN `p_resume_path` VARCHAR(255), IN `p_cover_letter` TEXT)   BEGIN
    INSERT INTO job_applications (
        user_id, job_id, resume_path, cover_letter
    ) VALUES (
        p_user_id, p_job_id, p_resume_path, p_cover_letter
    );
    SELECT LAST_INSERT_ID() as application_id;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `applications`
--

CREATE TABLE `applications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `company_name` varchar(100) NOT NULL,
  `position` varchar(100) NOT NULL,
  `status` enum('pending','approved','rejected','interview') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `companies`
--

CREATE TABLE `companies` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `address` text NOT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `contact_email` varchar(255) DEFAULT NULL,
  `contact_phone` varchar(20) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `logo_path` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `department_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `courses`
--

INSERT INTO `courses` (`id`, `name`, `department_id`) VALUES
(1, 'BS Information Technology', 1),
(2, 'BS Computer Science', 1),
(3, 'Bachelor of Industrial Technology', 2),
(4, 'BA Communication', 3),
(5, 'BA Psychology', 3),
(6, 'BA Political Science', 3),
(7, 'Bachelor of Elementary Education', 4),
(8, 'Bachelor of Secondary Education', 4),
(9, 'BS Accountancy', 5),
(10, 'BS Accounting Management', 5),
(11, 'BS Applied Economics', 5),
(12, 'BS Business Administration', 5),
(13, 'BS Entrepreneurship', 5),
(14, 'BS Hotel and Restaurant Management', 5),
(15, 'BS Tourism Management', 5),
(16, 'BA Public Administration', 5),
(17, 'BS Customs Administration', 5);

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`id`, `name`) VALUES
(1, 'College of Informatics and Computing Sciences'),
(2, 'College of Engineering Technology'),
(3, 'College of Arts and Sciences'),
(4, 'College of Teacher Education'),
(5, 'College of Accountancy, Business and Economics');

-- --------------------------------------------------------

--
-- Table structure for table `job_applications`
--

CREATE TABLE `job_applications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `job_id` int(11) NOT NULL,
  `status` enum('pending','approved','rejected','interview') NOT NULL DEFAULT 'pending',
  `resume_path` varchar(255) DEFAULT NULL,
  `cover_letter` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_listings`
--

CREATE TABLE `job_listings` (
  `id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `requirements` text DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `job_type` enum('full-time','part-time','internship') NOT NULL,
  `slots` int(11) NOT NULL DEFAULT 1,
  `status` enum('open','closed','draft') NOT NULL DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `expires_at` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_resumes`
--

CREATE TABLE `student_resumes` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `resume_path` varchar(255) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_resumes`
--

INSERT INTO `student_resumes` (`id`, `user_id`, `resume_path`, `uploaded_at`, `updated_at`) VALUES
(1, 2, 'uploads/resumes/resume_2_1746318007.pdf', '2025-05-04 00:20:07', '2025-05-04 00:20:07');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `srcode` varchar(9) NOT NULL,
  `firstname` varchar(50) NOT NULL,
  `lastname` varchar(50) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `course_id` int(11) NOT NULL,
  `section` varchar(20) NOT NULL,
  `usertype` enum('admin','user','none') NOT NULL DEFAULT 'none',
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `srcode`, `firstname`, `lastname`, `email`, `password`, `course_id`, `section`, `usertype`, `status`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, '21-00001', 'Admin', 'User', 'admin@admin.com', '$2y$10$UFcusxI5HK6MfXwh5sjZ5O3GCgaavBduJm47yQ0K7RELBGD3dqWnS', 1, '2201', 'admin', 'active', '2025-05-04 00:17:22', '2025-05-04 00:17:22', NULL),
(2, '23-32966', 'Jaika Remina', 'Madrid', '23-32275@g.batstate-u.edu.ph', '$2y$10$p0jHbV3v2JDvrgyqHa3JVuVgXVw2lYPKp0CDgMfdbyzVMnoDfUMoa', 1, '4201', 'user', 'active', '2025-05-04 00:19:04', '2025-05-04 00:19:04', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `applications`
--
ALTER TABLE `applications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `companies`
--
ALTER TABLE `companies`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `department_id` (`department_id`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `job_applications`
--
ALTER TABLE `job_applications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_application` (`user_id`,`job_id`),
  ADD KEY `job_id` (`job_id`);

--
-- Indexes for table `job_listings`
--
ALTER TABLE `job_listings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `company_id` (`company_id`);

--
-- Indexes for table `student_resumes`
--
ALTER TABLE `student_resumes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `srcode` (`srcode`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `idx_email_status` (`email`,`status`),
  ADD KEY `idx_srcode_status` (`srcode`,`status`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `applications`
--
ALTER TABLE `applications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `companies`
--
ALTER TABLE `companies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `job_applications`
--
ALTER TABLE `job_applications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `job_listings`
--
ALTER TABLE `job_listings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_resumes`
--
ALTER TABLE `student_resumes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `applications`
--
ALTER TABLE `applications`
  ADD CONSTRAINT `applications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `courses`
--
ALTER TABLE `courses`
  ADD CONSTRAINT `courses_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`);

--
-- Constraints for table `job_applications`
--
ALTER TABLE `job_applications`
  ADD CONSTRAINT `job_applications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `job_applications_ibfk_2` FOREIGN KEY (`job_id`) REFERENCES `job_listings` (`id`);

--
-- Constraints for table `job_listings`
--
ALTER TABLE `job_listings`
  ADD CONSTRAINT `job_listings_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`);

--
-- Constraints for table `student_resumes`
--
ALTER TABLE `student_resumes`
  ADD CONSTRAINT `student_resumes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
