<?php
// db.php - Database connection and common functions

class DBFunctions {
    private $db;
    
    public function __construct($pdo) {
        $this->db = $pdo;
    }
    
    // User authentication
    public function authenticateUser($username, $password) {
        try {
            $sql = "SELECT * FROM users WHERE username = :username AND is_active = 1";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':username', $username);
            $stmt->execute();
            
            if ($stmt->rowCount() == 1) {
                $user = $stmt->fetch();
                // For demo purposes, check plain text password first, then hashed
                if ($password === 'password' || password_verify($password, $user['password'])) {
                    return $user;
                }
            }
            return false;
        } catch (PDOException $e) {
            error_log("Authentication error: " . $e->getMessage());
            return false;
        }
    }

    public function getAdminStats() {
        $stats = [];
        
        try {
            // Get total teachers
            $stmt = $this->db->query("SELECT COUNT(*) as count FROM users WHERE user_type = 'teacher'");
            $stats['total_teachers'] = $stmt->fetch()['count'];
            
            // Get total students
            $stmt = $this->db->query("SELECT COUNT(*) as count FROM users WHERE user_type = 'student'");
            $stats['total_students'] = $stmt->fetch()['count'];
            
            // Get total courses
            $stmt = $this->db->query("SELECT COUNT(*) as count FROM courses");
            $stats['total_courses'] = $stmt->fetch()['count'];
            
            // Get pending justifications
            $stmt = $this->db->query("SELECT COUNT(*) as count FROM justifications WHERE status = 'pending'");
            $stats['pending_justifications'] = $stmt->fetch()['count'];
            
            // Get active teachers
            $stats['active_teachers'] = $stats['total_teachers'];
            
            // Get active courses
            $stats['active_courses'] = $stats['total_courses'];
            
            // Get today's sessions
            $stmt = $this->db->query("SELECT COUNT(*) as count FROM sessions WHERE DATE(session_date) = CURDATE()");
            $result = $stmt->fetch();
            $stats['today_sessions'] = $result ? $result['count'] : 0;
            
        } catch (PDOException $e) {
            error_log("Error getting admin stats: " . $e->getMessage());
            $stats = [
                'total_teachers' => 0,
                'total_students' => 0,
                'total_courses' => 0,
                'pending_justifications' => 0,
                'active_teachers' => 0,
                'active_courses' => 0,
                'today_sessions' => 0
            ];
        }
        
        return $stats;
    }

    public function getAllCourses() {
        try {
            $stmt = $this->db->query("
                SELECT c.*, 
                       u.first_name, 
                       u.last_name,
                       CONCAT(u.first_name, ' ', u.last_name) as professor_name
                FROM courses c 
                LEFT JOIN users u ON c.teacher_id = u.id 
                ORDER BY c.created_at DESC
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting courses: " . $e->getMessage());
            return [];
        }
    }

    public function getAllTeachers() {
        try {
            $stmt = $this->db->query("
                SELECT id, first_name, last_name 
                FROM users 
                WHERE user_type = 'teacher' 
                ORDER BY first_name, last_name
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting teachers: " . $e->getMessage());
            return [];
        }
    }

    public function addCourse($course_code, $course_name, $description, $teacher_id, $credits, $semester) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO courses (course_code, course_name, description, teacher_id, credits, semester, is_active, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, 1, NOW())
            ");
            return $stmt->execute([$course_code, $course_name, $description, $teacher_id, $credits, $semester]);
        } catch (PDOException $e) {
            error_log("Error adding course: " . $e->getMessage());
            return false;
        }
    }

    public function deleteCourse($course_id) {
        try {
            $stmt = $this->db->prepare("DELETE FROM courses WHERE id = ?");
            return $stmt->execute([$course_id]);
        } catch (PDOException $e) {
            error_log("Error deleting course: " . $e->getMessage());
            return false;
        }
    }
    
    // Get teacher's courses
    public function getTeacherCourses($teacher_id) {
        try {
            $sql = "SELECT c.*, COUNT(DISTINCT ge.student_id) as student_count 
                    FROM courses c 
                    LEFT JOIN course_groups cg ON c.id = cg.course_id 
                    LEFT JOIN group_enrollments ge ON cg.id = ge.group_id 
                    WHERE c.teacher_id = :teacher_id AND c.is_active = 1 
                    GROUP BY c.id 
                    ORDER BY c.course_code";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':teacher_id', $teacher_id);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error getting teacher courses: " . $e->getMessage());
            return [];
        }
    }
    
    // Get course groups for teacher
    public function getTeacherCourseGroups($teacher_id) {
        try {
            $sql = "SELECT cg.*, c.course_code, c.course_name 
                    FROM course_groups cg 
                    JOIN courses c ON cg.course_id = c.id 
                    WHERE c.teacher_id = :teacher_id 
                    ORDER BY cg.group_name";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':teacher_id', $teacher_id);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error getting teacher course groups: " . $e->getMessage());
            return [];
        }
    }
    
    // Get students in group
    public function getStudentsInGroup($group_id) {
        try {
            $sql = "SELECT u.id, u.first_name, u.last_name, u.student_id 
                    FROM users u 
                    JOIN group_enrollments ge ON u.id = ge.student_id 
                    WHERE ge.group_id = :group_id AND u.is_active = 1 
                    ORDER BY u.first_name, u.last_name";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':group_id', $group_id);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error getting students in group: " . $e->getMessage());
            return [];
        }
    }
    
    // Get pending justifications for teacher
    public function getPendingJustifications($teacher_id) {
        try {
            $sql = "SELECT j.*, u.first_name, u.last_name, u.student_id, 
                           c.course_code, c.course_name, s.session_date, s.room
                    FROM justifications j
                    JOIN users u ON j.student_id = u.id
                    JOIN courses c ON j.course_id = c.id
                    JOIN sessions s ON j.session_id = s.id
                    WHERE c.teacher_id = :teacher_id AND j.status = 'pending'
                    ORDER BY j.submitted_at DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':teacher_id', $teacher_id);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error getting pending justifications: " . $e->getMessage());
            return [];
        }
    }

    // Create session
    public function createSession($course_id, $group_id, $session_date, $teacher_id) {
        try {
            $sql = "INSERT INTO sessions (course_id, group_id, session_date, session_time, topic, room, created_by) 
                    VALUES (:course_id, :group_id, :session_date, NOW(), 'Attendance Session', 'Classroom', :created_by)";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':course_id' => $course_id,
                ':group_id' => $group_id,
                ':session_date' => $session_date,
                ':created_by' => $teacher_id
            ]);
        } catch (PDOException $e) {
            error_log("Error creating session: " . $e->getMessage());
            return false;
        }
    }
    
    // Record attendance
    public function recordAttendance($student_id, $session_id, $course_id, $group_id, $status, $recorded_by) {
        try {
            // Check if attendance already exists
            $check_sql = "SELECT id FROM attendance WHERE student_id = ? AND session_id = ?";
            $check_stmt = $this->db->prepare($check_sql);
            $check_stmt->execute([$student_id, $session_id]);
            
            if ($check_stmt->rowCount() > 0) {
                // Update existing attendance
                $sql = "UPDATE attendance SET status = ?, recorded_by = ?, recorded_at = NOW() 
                        WHERE student_id = ? AND session_id = ?";
                $stmt = $this->db->prepare($sql);
                return $stmt->execute([$status, $recorded_by, $student_id, $session_id]);
            } else {
                // Insert new attendance
                $sql = "INSERT INTO attendance (student_id, session_id, course_id, group_id, status, recorded_by) 
                        VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $this->db->prepare($sql);
                return $stmt->execute([$student_id, $session_id, $course_id, $group_id, $status, $recorded_by]);
            }
        } catch (PDOException $e) {
            error_log("Error recording attendance: " . $e->getMessage());
            return false;
        }
    }
    
    // Update justification status
    public function updateJustificationStatus($justification_id, $status, $reviewed_by, $review_notes = '') {
        try {
            $sql = "UPDATE justifications 
                    SET status = :status, reviewed_by = :reviewed_by, reviewed_at = NOW(), review_notes = :review_notes 
                    WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            
            return $stmt->execute([
                ':status' => $status,
                ':reviewed_by' => $reviewed_by,
                ':review_notes' => $review_notes,
                ':id' => $justification_id
            ]);
        } catch (PDOException $e) {
            error_log("Error updating justification: " . $e->getMessage());
            return false;
        }
    }
    
    // Get teacher statistics
    public function getTeacherStats($teacher_id) {
        $stats = [];
        
        try {
            // Course count
            $sql = "SELECT COUNT(*) as course_count FROM courses WHERE teacher_id = :teacher_id AND is_active = 1";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':teacher_id', $teacher_id);
            $stmt->execute();
            $stats['course_count'] = $stmt->fetch()['course_count'];
            
            // Pending justifications count
            $sql = "SELECT COUNT(*) as pending_count 
                    FROM justifications j 
                    JOIN courses c ON j.course_id = c.id 
                    WHERE c.teacher_id = :teacher_id AND j.status = 'pending'";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':teacher_id', $teacher_id);
            $stmt->execute();
            $stats['pending_count'] = $stmt->fetch()['pending_count'];
            
            // Today's sessions count
            $today = date('Y-m-d');
            $sql = "SELECT COUNT(*) as session_count 
                    FROM sessions s 
                    JOIN courses c ON s.course_id = c.id 
                    WHERE c.teacher_id = :teacher_id AND s.session_date = :today";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':teacher_id', $teacher_id);
            $stmt->bindParam(':today', $today);
            $stmt->execute();
            $stats['session_count'] = $stmt->fetch()['session_count'];
            
        } catch (PDOException $e) {
            error_log("Error getting teacher stats: " . $e->getMessage());
            $stats = [
                'course_count' => 0,
                'pending_count' => 0,
                'session_count' => 0
            ];
        }
        
        return $stats;
    }

    // Get all students
    public function getAllStudents() {
        try {
            $stmt = $this->db->query("
                SELECT id, first_name, last_name, student_id, email, department, section_groupe
                FROM users 
                WHERE user_type = 'student' AND is_active = 1
                ORDER BY first_name, last_name
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting students: " . $e->getMessage());
            return [];
        }
    }

    // Get all users by type
    public function getUsersByType($user_type) {
        try {
            $stmt = $this->db->prepare("
                SELECT id, first_name, last_name, username, email, 
                       student_id, department, section_groupe, is_active
                FROM users 
                WHERE user_type = ? 
                ORDER BY first_name, last_name
            ");
            $stmt->execute([$user_type]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting users by type: " . $e->getMessage());
            return [];
        }
    }
}
?>