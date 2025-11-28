-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : ven. 28 nov. 2025 à 18:54
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `university_attendance`
--

-- --------------------------------------------------------

--
-- Structure de la table `attendance`
--

CREATE TABLE `attendance` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `session_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  `status` enum('present','absent','late','excused') DEFAULT 'absent',
  `recorded_by` int(11) NOT NULL,
  `recorded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `courses`
--

CREATE TABLE `courses` (
  `id` int(11) NOT NULL,
  `course_code` varchar(20) NOT NULL,
  `course_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `credits` int(11) DEFAULT 3,
  `teacher_id` int(11) DEFAULT NULL,
  `semester` varchar(50) DEFAULT 'Fall 2024',
  `max_students` int(11) DEFAULT 30,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `course_groups`
--

CREATE TABLE `course_groups` (
  `id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `group_name` varchar(50) NOT NULL,
  `schedule_info` text DEFAULT NULL,
  `max_capacity` int(11) DEFAULT 30,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `group_enrollments`
--

CREATE TABLE `group_enrollments` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  `enrolled_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('active','completed') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `justifications`
--

CREATE TABLE `justifications` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `session_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  `justification_text` text NOT NULL,
  `supporting_docs` varchar(255) DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `review_notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `sessions`
--

CREATE TABLE `sessions` (
  `id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  `session_date` date NOT NULL,
  `session_time` time NOT NULL,
  `topic` varchar(255) DEFAULT NULL,
  `room` varchar(50) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `user_type` enum('admin','teacher','student') NOT NULL,
  `student_id` varchar(20) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `section_groupe` varchar(50) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_attendance` (`student_id`,`session_id`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `group_id` (`group_id`),
  ADD KEY `recorded_by` (`recorded_by`),
  ADD KEY `idx_attendance_student` (`student_id`),
  ADD KEY `idx_attendance_session` (`session_id`);

--
-- Index pour la table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `course_code` (`course_code`),
  ADD KEY `idx_courses_teacher` (`teacher_id`);

--
-- Index pour la table `course_groups`
--
ALTER TABLE `course_groups`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_id` (`course_id`);

--
-- Index pour la table `group_enrollments`
--
ALTER TABLE `group_enrollments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_group_enrollment` (`student_id`,`group_id`),
  ADD KEY `group_id` (`group_id`);

--
-- Index pour la table `justifications`
--
ALTER TABLE `justifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `session_id` (`session_id`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `group_id` (`group_id`),
  ADD KEY `reviewed_by` (`reviewed_by`),
  ADD KEY `idx_justifications_student` (`student_id`),
  ADD KEY `idx_justifications_status` (`status`);

--
-- Index pour la table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `group_id` (`group_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_sessions_date` (`session_date`);

--
-- Index pour la table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `student_id` (`student_id`),
  ADD KEY `idx_users_type` (`user_type`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `courses`
--
ALTER TABLE `courses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `course_groups`
--
ALTER TABLE `course_groups`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `group_enrollments`
--
ALTER TABLE `group_enrollments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `justifications`
--
ALTER TABLE `justifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `sessions`
--
ALTER TABLE `sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attendance_ibfk_2` FOREIGN KEY (`session_id`) REFERENCES `sessions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attendance_ibfk_3` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attendance_ibfk_4` FOREIGN KEY (`group_id`) REFERENCES `course_groups` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attendance_ibfk_5` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `courses`
--
ALTER TABLE `courses`
  ADD CONSTRAINT `courses_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `course_groups`
--
ALTER TABLE `course_groups`
  ADD CONSTRAINT `course_groups_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `group_enrollments`
--
ALTER TABLE `group_enrollments`
  ADD CONSTRAINT `group_enrollments_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `group_enrollments_ibfk_2` FOREIGN KEY (`group_id`) REFERENCES `course_groups` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `justifications`
--
ALTER TABLE `justifications`
  ADD CONSTRAINT `justifications_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `justifications_ibfk_2` FOREIGN KEY (`session_id`) REFERENCES `sessions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `justifications_ibfk_3` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `justifications_ibfk_4` FOREIGN KEY (`group_id`) REFERENCES `course_groups` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `justifications_ibfk_5` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `sessions`
--
ALTER TABLE `sessions`
  ADD CONSTRAINT `sessions_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sessions_ibfk_2` FOREIGN KEY (`group_id`) REFERENCES `course_groups` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sessions_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;