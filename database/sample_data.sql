-- Sample Data for University Attendance System
-- Université Alger 1 - Benyoucef Benkhedda

-- Insert default users
INSERT INTO `users` (`id`, `username`, `email`, `password`, `first_name`, `last_name`, `user_type`, `student_id`, `department`, `section_groupe`, `phone`, `profile_image`, `is_active`) VALUES
(1, 'admin', 'admin@university.dz', 'password', 'admin', 'Administrator', 'admin', NULL, NULL, NULL, NULL, NULL, 1),
(2, 'samy.char', 'john.doe@university.dz', 'password', 'Samy', 'Charallah', 'student', NULL, 'Computer Science', NULL, NULL, NULL, 1),
(4, 'Mohamed.Hemilli', 'ahmed.benali@university.dz', 'password', 'Mohamed', 'Hemili', 'teacher', NULL, 'Informatique', NULL, NULL, NULL, 1),
(19, 'sami.merabet', 'sami.merabet@university.dz', 'student123', 'Sami', 'Merabet', 'student', 'S2024001', 'Computer Science', 'CS-A', '+213-552-123-456', NULL, 1),
(20, 'nadia.hamidi', 'nadia.hamidi@university.dz', 'student123', 'Nadia', 'Hamidi', 'student', 'S2024002', 'Computer Science', 'CS-A', '+213-662-234-567', NULL, 1),
(21, 'bilal.touati', 'bilal.touati@university.dz', 'student123', 'Bilal', 'Touati', 'student', 'S2024003', 'Computer Science', 'CS-B', '+213-772-345-678', NULL, 1),
(22, 'sarah.mansour', 'sarah.mansour@university.dz', 'student123', 'Sarah', 'Mansour', 'student', 'S2024004', 'Mathematics', 'MATH-A', '+213-792-456-789', NULL, 1),
(23, 'omar.benguedda', 'omar.benguedda@university.dz', 'student123', 'Omar', 'Benguedda', 'student', 'S2024005', 'Mathematics', 'MATH-B', '+213-502-567-890', NULL, 1),
(24, 'lina.benamor', 'lina.benamor@university.dz', 'student123', 'Lina', 'Benamor', 'student', 'S2024006', 'Physics', 'PHY-A', '+213-553-678-901', NULL, 1),
(25, 'riad.guerfi', 'riad.guerfi@university.dz', 'student123', 'Riad', 'Guerfi', 'student', 'S2024007', 'Physics', 'PHY-B', '+213-663-789-012', NULL, 1),
(26, 'ines.bouchenak', 'ines.bouchenak@university.dz', 'student123', 'Ines', 'Bouchenak', 'student', 'S2024008', 'Chemistry', 'CHM-A', '+213-773-890-123', NULL, 1),
(27, 'hakim.zidane', 'hakim.zidane@university.dz', 'student123', 'Hakim', 'Zidane', 'student', 'S2024009', 'Chemistry', 'CHM-B', '+213-793-901-234', NULL, 1),
(28, 'yasmine.khelifi', 'yasmine.khelifi@university.dz', 'student123', 'Yasmine', 'Khelifi', 'student', 'S2024010', 'Engineering', 'ENG-A', '+213-503-012-345', NULL, 1),
(29, 'mounir.saadi', 'mounir.saadi@university.dz', 'student123', 'Mounir', 'Saadi', 'student', 'S2024011', 'Engineering', 'ENG-B', '+213-554-123-456', NULL, 1),
(30, 'salima.benyoucef', 'salima.benyoucef@university.dz', 'student123', 'Salima', 'Benyoucef', 'student', 'S2024012', 'Computer Science', 'CS-B', '+213-664-234-567', NULL, 1),
(31, 'karim.boudiaf', 'karim.boudiaf@university.dz', 'student123', 'Karim', 'Boudiaf', 'student', 'S2024013', 'Mathematics', 'MATH-A', '+213-774-345-678', NULL, 1),
(32, 'nawal.benbrahim', 'nawal.benbrahim@university.dz', 'student123', 'Nawal', 'Benbrahim', 'student', 'S2024014', 'Physics', 'PHY-A', '+213-794-456-789', NULL, 1),
(33, 'fares.mekki', 'fares.mekki@university.dz', 'student123', 'Fares', 'Mekki', 'student', 'S2024015', 'Engineering', 'ENG-A', '+213-504-567-890', NULL, 1),
(35, 'an_65es', 'an_65es@university.dz', '$2y$10$9ljfpCagQtVqElWkXVq7gOs7jFGYiGWBl82nmb4XzqodV4WgHnZha', 'Samy', 'Boukhari', 'student', 'S8889', 'Computer Science', 'Lab Group A', NULL, NULL, 1);

-- Insert courses
INSERT INTO `courses` (`id`, `course_code`, `course_name`, `description`, `credits`, `teacher_id`, `semester`, `max_students`, `is_active`) VALUES
(2, 'CS301', 'Data Structures', 'Advanced data structures and algorithms', 3, 4, 'Fall 2024', 30, 1);

-- Insert course groups
INSERT INTO `course_groups` (`id`, `course_id`, `group_name`, `schedule_info`, `max_capacity`) VALUES
(3, 2, 'Lab Group A', 'Tue, Thu - 9:00-11:00 - Lab A', 20),
(4, 2, 'Lab Group B', 'Mon, Wed - 13:00-15:00 - Lab B', 20);

-- Insert group enrollments
INSERT INTO `group_enrollments` (`id`, `student_id`, `group_id`, `status`) VALUES
(19, 19, 3, 'active'),
(20, 20, 3, 'active'),
(21, 30, 3, 'active'),
(22, 21, 4, 'active'),
(23, 31, 4, 'active'),
(36, 35, 3, 'active');

-- Insert sessions
INSERT INTO `sessions` (`id`, `course_id`, `group_id`, `session_date`, `session_time`, `topic`, `room`, `created_by`) VALUES
(10, 2, 3, '2024-11-04', '09:00:00', 'Arrays and Lists', 'Lab A', 4),
(11, 2, 4, '2024-11-05', '13:00:00', 'Arrays and Lists', 'Lab B', 4),
(16, 2, 3, '2025-11-28', '17:44:39', 'Attendance Session', 'Classroom', 4);

-- Insert attendance records
INSERT INTO `attendance` (`id`, `student_id`, `session_id`, `course_id`, `group_id`, `status`, `recorded_by`, `notes`) VALUES
(15, 20, 16, 2, 3, 'present', 4, NULL),
(16, 30, 16, 2, 3, 'present', 4, NULL),
(17, 19, 16, 2, 3, 'present', 4, NULL),
(18, 35, 16, 2, 3, 'present', 4, NULL);