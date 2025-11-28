<?php
// students.php
require_once '../config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Use the database connection from config
$db = $pdo;

// Fetch all students from database
$students = $db_functions->getUsersByType('student');

// Fetch unique departments and sections for filters
$departments = [];
$sections = [];

foreach ($students as $student) {
    if (!empty($student['department']) && !in_array($student['department'], $departments)) {
        $departments[] = $student['department'];
    }
    if (!empty($student['section_groupe']) && !in_array($student['section_groupe'], $sections)) {
        $sections[] = $student['section_groupe'];
    }
}

// Fetch all courses and course groups
$courses = $db_functions->getAllCourses();
$course_groups = [];
$available_sections = [];

foreach ($courses as $course) {
    $stmt = $pdo->prepare("SELECT * FROM course_groups WHERE course_id = ?");
    $stmt->execute([$course['id']]);
    $course_groups[$course['id']] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Extract available sections from course groups
    foreach ($course_groups[$course['id']] as $group) {
        if (!empty($group['group_name']) && !in_array($group['group_name'], $available_sections)) {
            $available_sections[] = $group['group_name'];
        }
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_student'])) {
        // Add new student
        $first_name = trim($_POST['first_name']);
        $last_name = trim($_POST['last_name']);
        $username = trim($_POST['username']);
        $password = $_POST['password'];
        $department = $_POST['department'];
        $section_groupe = $_POST['section_groupe'];
        $course_groups_selected = $_POST['course_groups'] ?? [];
        
        // Basic validation
        if (!empty($first_name) && !empty($last_name) && !empty($username) && !empty($password)) {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            try {
                $pdo->beginTransaction();
                
                $stmt = $pdo->prepare("
                    INSERT INTO users (username, email, password, first_name, last_name, user_type, student_id, department, section_groupe, is_active) 
                    VALUES (?, ?, ?, ?, ?, 'student', ?, ?, ?, 1)
                ");
                
                // Generate student ID
                $student_id = 'S' . str_pad(rand(1000, 9999), 4, '0', STR_PAD_LEFT);
                $email = $username . '@university.dz';
                
                $stmt->execute([$username, $email, $hashed_password, $first_name, $last_name, $student_id, $department, $section_groupe]);
                $new_student_id = $pdo->lastInsertId();
                
                // Auto-enroll student in course groups that match their section
                if (!empty($section_groupe)) {
                    $auto_enroll_stmt = $pdo->prepare("
                        SELECT cg.id 
                        FROM course_groups cg 
                        JOIN courses c ON cg.course_id = c.id 
                        WHERE cg.group_name = ? OR cg.group_name LIKE ?
                    ");
                    $auto_enroll_stmt->execute([$section_groupe, "%$section_groupe%"]);
                    $matching_groups = $auto_enroll_stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    foreach ($matching_groups as $group) {
                        $course_groups_selected[] = $group['id'];
                    }
                }
                
                // Remove duplicates
                $course_groups_selected = array_unique($course_groups_selected);
                
                // Enroll student in selected course groups
                foreach ($course_groups_selected as $group_id) {
                    $stmt = $pdo->prepare("
                        INSERT INTO group_enrollments (student_id, group_id, enrolled_at, status) 
                        VALUES (?, ?, NOW(), 'active')
                    ");
                    $stmt->execute([$new_student_id, $group_id]);
                }
                
                $pdo->commit();
                
                // Refresh students list
                $students = $db_functions->getUsersByType('student');
                $success_message = "Student added successfully!";
                
            } catch (PDOException $e) {
                $pdo->rollBack();
                $error_message = "Error adding student: " . $e->getMessage();
            }
        } else {
            $error_message = "Please fill all required fields!";
        }
    }
    
    if (isset($_POST['delete_student'])) {
        $student_id = $_POST['student_id'];
        
        try {
            $pdo->beginTransaction();
            
            // Remove student from all course groups
            $stmt = $pdo->prepare("DELETE FROM group_enrollments WHERE student_id = ?");
            $stmt->execute([$student_id]);
            
            // Delete student
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND user_type = 'student'");
            $stmt->execute([$student_id]);
            
            $pdo->commit();
            
            // Refresh students list
            $students = $db_functions->getUsersByType('student');
            $success_message = "Student deleted successfully!";
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error_message = "Error deleting student: " . $e->getMessage();
        }
    }
    
    if (isset($_POST['update_course_enrollments'])) {
        $student_id = $_POST['student_id'];
        $course_groups_selected = $_POST['course_groups'] ?? [];
        
        try {
            $pdo->beginTransaction();
            
            // Remove all existing enrollments
            $stmt = $pdo->prepare("DELETE FROM group_enrollments WHERE student_id = ?");
            $stmt->execute([$student_id]);
            
            // Add new enrollments
            foreach ($course_groups_selected as $group_id) {
                $stmt = $pdo->prepare("
                    INSERT INTO group_enrollments (student_id, group_id, enrolled_at, status) 
                    VALUES (?, ?, NOW(), 'active')
                ");
                $stmt->execute([$student_id, $group_id]);
            }
            
            $pdo->commit();
            $success_message = "Course enrollments updated successfully!";
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error_message = "Error updating course enrollments: " . $e->getMessage();
        }
    }
    
    if (isset($_POST['update_student'])) {
        $student_id = $_POST['student_id'];
        $first_name = trim($_POST['first_name']);
        $last_name = trim($_POST['last_name']);
        $email = trim($_POST['email']);
        $department = $_POST['department'];
        $section_groupe = $_POST['section_groupe'];
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        try {
            $pdo->beginTransaction();
            
            // Update student information
            $stmt = $pdo->prepare("
                UPDATE users 
                SET first_name = ?, last_name = ?, email = ?, department = ?, section_groupe = ?, is_active = ? 
                WHERE id = ? AND user_type = 'student'
            ");
            $stmt->execute([$first_name, $last_name, $email, $department, $section_groupe, $is_active, $student_id]);
            
            // If section changed, suggest matching course groups
            if (!empty($section_groupe)) {
                $suggest_groups_stmt = $pdo->prepare("
                    SELECT cg.id, c.course_code, cg.group_name
                    FROM course_groups cg 
                    JOIN courses c ON cg.course_id = c.id 
                    WHERE cg.group_name = ? OR cg.group_name LIKE ?
                ");
                $suggest_groups_stmt->execute([$section_groupe, "%$section_groupe%"]);
                $suggested_groups = $suggest_groups_stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (!empty($suggested_groups)) {
                    $suggestion_message = "Suggested course groups for section " . $section_groupe . ": ";
                    $suggestions = [];
                    foreach ($suggested_groups as $group) {
                        $suggestions[] = $group['course_code'] . " - " . $group['group_name'];
                    }
                    $suggestion_message .= implode(", ", $suggestions);
                }
            }
            
            $pdo->commit();
            $success_message = "Student updated successfully!" . (isset($suggestion_message) ? "<br>" . $suggestion_message : "");
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error_message = "Error updating student: " . $e->getMessage();
        }
    }
}

// Get course enrollments for each student
foreach ($students as &$student) {
    $stmt = $pdo->prepare("
        SELECT cg.id, cg.group_name, c.course_code, c.course_name 
        FROM group_enrollments ge 
        JOIN course_groups cg ON ge.group_id = cg.id 
        JOIN courses c ON cg.course_id = c.id 
        WHERE ge.student_id = ?
    ");
    $stmt->execute([$student['id']]);
    $student['enrolled_courses'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
unset($student); // break the reference
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Management - Algiers University</title>
  <style>
    :root{
      --brand:#2b6be6;
      --accent:#4f86ff;
      --bg:#f4f7fb;
      --panel:#fff;
      --muted:#6b7280;
      --glass:rgba(15,23,42,0.04);
      font-family: Inter, system-ui, -apple-system, "Segoe UI", Roboto, Arial, sans-serif;
    }
    *{box-sizing:border-box}
    html,body{height:100%;margin:0;background:linear-gradient(180deg,#f8fbff 0%,var(--bg) 100%);color:#0b1220}
    .topbar{position:fixed;left:0;right:0;top:0;height:68px;background:#fff;display:flex;align-items:center;padding:0 22px;border-bottom:1px solid rgba(11,18,32,0.06);z-index:40}
    .mark{width:44px;height:44px;border-radius:8px;background:linear-gradient(135deg,var(--brand),var(--accent));display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:16px;margin-right:12px}
    .brand-text{font-size:14px;line-height:1}
    .topbar .user-actions{margin-left:auto;display:flex;gap:12px;align-items:center}
    .topbar .username{font-size:13px;color:var(--muted)}
    .layout{display:flex;padding-top:88px;max-width:1400px;margin:30px auto;gap:22px}
    .sidebar{width:260px;background:var(--panel);border-radius:14px;padding:18px;border:1px solid var(--glass);box-shadow:0 10px 30px rgba(15,23,42,0.04);height:calc(100vh - 88px);position:sticky;top:88px}
    .profile{display:flex;gap:12px;align-items:center;margin-bottom:12px}
    .avatar{width:56px;height:56px;border-radius:8px;background:#eef6ff;color:var(--brand);display:flex;align-items:center;justify-content:center;font-weight:800}
    .pmeta{font-size:13px}
    .pmeta .name{font-weight:800}
    .pmeta .role{font-size:12px;color:var(--muted)}
    .nav{margin-top:14px;display:flex;flex-direction:column;gap:8px}
    .nav a{display:flex;align-items:center;gap:10px;padding:10px;border-radius:10px;color:#28323a;text-decoration:none;font-weight:700}
    .nav a.active{background:linear-gradient(90deg,rgba(43,107,230,0.08),rgba(43,107,230,0.02));border:1px solid rgba(43,107,230,0.06);color:var(--brand)}
    .content{flex:1;padding:12px}
    .page-head{display:flex;justify-content:space-between;align-items:flex-start;gap:12px}
    .title{font-size:22px;font-weight:800;color:#1f2937}
    .subtitle{font-size:13px;color:var(--muted);margin-top:6px}
    .card{background:var(--panel);padding:18px;border-radius:12px;border:1px solid var(--glass);box-shadow:0 10px 30px rgba(15,23,42,0.04);margin-bottom:16px}
    .btn{padding:8px 16px;border-radius:8px;border:0;cursor:pointer;font-weight:700;font-size:14px}
    .btn.primary{background:linear-gradient(90deg,var(--brand),var(--accent));color:#fff}
    .btn.success{background:#16a34a;color:#fff}
    .btn.danger{background:#ef4444;color:#fff}
    .btn.outline{background:transparent;border:1px solid var(--brand);color:var(--brand)}
    .table-responsive{overflow-x:auto;margin-top:16px}
    .table{width:100%;border-collapse:collapse}
    .table th,.table td{padding:12px;text-align:left;border-bottom:1px solid rgba(15,23,42,0.08)}
    .table th{font-weight:700;color:var(--muted);font-size:13px}
    .table tr:hover{background:rgba(15,23,42,0.02)}
    .badge{padding:4px 8px;border-radius:6px;font-size:12px;font-weight:600}
    .badge.primary{background:#eef6ff;color:var(--brand)}
    .badge.secondary{background:#f3f4f6;color:var(--muted)}
    .badge.success{background:#f0fdf4;color:#16a34a}
    .badge.course{background:#f0f9ff;color:#0ea5e9;margin:2px;display:inline-block}
    .form-group{margin-bottom:16px}
    .form-label{display:block;margin-bottom:6px;font-weight:600;font-size:14px}
    .form-control{width:100%;padding:10px;border:1px solid rgba(15,23,42,0.1);border-radius:8px;font-size:14px}
    .form-control:focus{outline:none;border-color:var(--brand)}
    .modal{display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(15,23,42,0.5);z-index:50;align-items:center;justify-content:center}
    .modal.show{display:flex}
    .modal-content{background:#fff;border-radius:12px;width:90%;max-width:700px;max-height:90vh;overflow:auto}
    .modal-header{padding:16px;border-bottom:1px solid rgba(15,23,42,0.1);display:flex;justify-content:space-between;align-items:center}
    .modal-body{padding:16px}
    .modal-footer{padding:16px;border-top:1px solid rgba(15,23,42,0.1);display:flex;gap:8px;justify-content:flex-end}
    .search-box{position:relative;margin-bottom:16px}
    .search-input{width:100%;padding:10px 40px 10px 12px;border:1px solid rgba(15,23,42,0.1);border-radius:8px;font-size:14px}
    .search-clear{position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--muted)}
    .actions{display:flex;gap:6px}
    .action-btn{padding:6px;border:none;border-radius:6px;cursor:pointer;font-size:12px}
    .action-btn.edit{background:#eef6ff;color:var(--brand)}
    .action-btn.view{background:#f0f9ff;color:#0ea5e9}
    .action-btn.courses{background:#f0fdf4;color:#16a34a}
    .action-btn.delete{background:#fef2f2;color:#ef4444}
    .export-options{margin-top:16px;display:flex;gap:8px}
    .alert{background:#ffeaa7;border:1px solid #fdcb6e;color:#2d3436;padding:15px;border-radius:8px;margin-bottom:25px;font-size:14px;text-align:center}
    .alert.success{background:#55efc4;border:1px solid #00b894}
    .alert.error{background:#ffeaa7;border:1px solid #fdcb6e}
    .courses-list{display:flex;flex-wrap:wrap;gap:4px;margin-top:4px}
    .courses-select{height:200px;overflow-y:auto;border:1px solid #d1d5db;border-radius:8px;padding:8px}
    .courses-select option{padding:8px;margin:2px 0;border-radius:4px}
    .courses-select option:checked{background:var(--brand);color:white}
    .course-group{background:#f8fafc;padding:12px;border-radius:8px;margin-bottom:12px}
    .course-group h4{margin:0 0 8px 0;color:var(--brand)}
    
    /* Filter Section Styles */
    .filter-section {background:var(--panel);padding:16px;border-radius:12px;border:1px solid var(--glass);box-shadow:0 5px 15px rgba(15,23,42,0.03);margin-bottom:16px}
    .filter-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:12px}
    .filter-title{font-size:16px;font-weight:700;color:#1f2937}
    .filter-controls{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:12px}
    .filter-group{display:flex;flex-direction:column}
    .filter-label{font-size:12px;font-weight:600;color:var(--muted);margin-bottom:6px}
    .filter-select,.filter-input{width:100%;padding:8px 12px;border:1px solid rgba(15,23,42,0.1);border-radius:6px;font-size:13px;background:#fff}
    .filter-select:focus,.filter-input:focus{outline:none;border-color:var(--brand)}
    .filter-actions{display:flex;gap:8px;margin-top:8px}
    .filter-btn{padding:6px 12px;border-radius:6px;border:1px solid rgba(15,23,42,0.1);background:#fff;cursor:pointer;font-size:12px;font-weight:600}
    .filter-btn.apply{background:var(--brand);color:#fff;border-color:var(--brand)}
    .filter-btn:hover{background:#f8fafc}
    .filter-btn.apply:hover{background:var(--accent)}
    
    @media (max-width:960px){
        .sidebar{display:none}
        .layout{padding:88px 12px 12px 12px}
        .export-options{flex-direction:column}
        .filter-controls{grid-template-columns:1fr}
    }
    </style>
</head>
<body>
    <!-- Top Bar -->
    <header class="topbar" role="banner">
        <div style="display:flex;align-items:center;">
            <div class="mark">AU</div>
            <div class="brand-text">
                <div style="font-weight:800">Algiers University</div>
                <div style="font-size:12px;color:var(--muted)">Admin panel</div>
            </div>
        </div>
        <div class="user-actions" role="navigation" aria-label="User">
            <div class="username"><?php echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']); ?></div>
            <div style="width:36px;height:36px;border-radius:8px;background:linear-gradient(135deg,#f3f7ff,#eef6ff);display:flex;align-items:center;justify-content:center;color:var(--brand);font-weight:800"><?php echo substr($_SESSION['first_name'], 0, 1) . substr($_SESSION['last_name'], 0, 1); ?></div>
        </div>
    </header>

    <main class="layout" role="main">
        <!-- Sidebar -->
        <aside class="sidebar" aria-label="Main navigation">
            <div class="profile">
                <div class="avatar"><?php echo substr($_SESSION['first_name'], 0, 1) . substr($_SESSION['last_name'], 0, 1); ?></div>
                <div class="pmeta">
                    <div class="name"><?php echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']); ?></div>
                    <div class="role">Administrator</div>
                </div>
            </div>

            <nav class="nav" aria-label="Primary">
                <a href="dashboard.php">🏠 Dashboard</a>
                <a href="courses.php">📚 Manage Courses</a>
                <a href="teachers.php">👨‍🏫 Manage Teachers</a>
                <a href="students.php" class="active">👩‍🎓 Manage Students</a>
                <a href="../logout.php" class="logout">↩️ Logout</a>
            </nav>
        </aside>

        <!-- Main Content -->
        <section class="content" aria-labelledby="students-title">
            <div class="page-head">
                <div>
                    <h1 id="students-title" class="title">Student Management</h1>
                    <div class="subtitle">Manage students and their course enrollments by section/group</div>
                </div>
                <button class="btn primary" onclick="openAddStudentModal()">
                    👨‍🎓 Add New Student
                </button>
            </div>

            <?php if (isset($success_message)): ?>
                <div class="alert success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <div class="alert error"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>

            <!-- Filter Section -->
            <div class="filter-section">
                <div class="filter-header">
                    <div class="filter-title">🔍 Filter Students</div>
                    <button class="filter-btn" onclick="clearFilters()">Clear Filters</button>
                </div>
                <div class="filter-controls">
                    <div class="filter-group">
                        <label class="filter-label">Department</label>
                        <select class="filter-select" id="filterDepartment">
                            <option value="">All Departments</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo htmlspecialchars($dept); ?>"><?php echo htmlspecialchars($dept); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label class="filter-label">Section/Groupe</label>
                        <select class="filter-select" id="filterSection">
                            <option value="">All Sections</option>
                            <?php foreach ($sections as $section): ?>
                                <option value="<?php echo htmlspecialchars($section); ?>"><?php echo htmlspecialchars($section); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label class="filter-label">Status</label>
                        <select class="filter-select" id="filterStatus">
                            <option value="">All Status</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="filter-actions">
                    <button class="filter-btn apply" onclick="applyFilters()">Apply Filters</button>
                    <button class="filter-btn" onclick="resetFilters()">Reset</button>
                </div>
            </div>

            <!-- Search Box -->
            <div class="search-box">
                <input type="text" class="search-input" placeholder="Search students by name, username, or ID..." id="searchStudents">
                <button class="search-clear" onclick="clearSearch()">✕</button>
            </div>

            <!-- Students Table -->
            <div class="card">
                <h3 style="margin:0 0 16px 0">Student List (<?php echo count($students); ?> students)</h3>
                <div class="table-responsive">
                    <table class="table" id="studentsTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Department</th>
                                <th>Section/Groupe</th>
                                <th>Enrolled Courses</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $student): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($student['student_id'] ?? 'N/A'); ?></td>
                                <td><strong><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($student['username']); ?></td>
                                <td><?php echo htmlspecialchars($student['email']); ?></td>
                                <td><?php echo htmlspecialchars($student['department'] ?? 'N/A'); ?></td>
                                <td>
                                    <?php if (!empty($student['section_groupe'])): ?>
                                        <span class="badge primary"><?php echo htmlspecialchars($student['section_groupe']); ?></span>
                                    <?php else: ?>
                                        <span class="badge secondary">Not set</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="courses-list">
                                        <?php if (!empty($student['enrolled_courses'])): ?>
                                            <?php foreach ($student['enrolled_courses'] as $course): ?>
                                                <span class="badge course" title="<?php echo htmlspecialchars($course['course_name']); ?>">
                                                    <?php echo htmlspecialchars($course['course_code'] . ' - ' . $course['group_name']); ?>
                                                </span>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <span style="color:var(--muted);font-size:12px">No courses</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($student['is_active']): ?>
                                        <span class="badge success">Active</span>
                                    <?php else: ?>
                                        <span class="badge secondary">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="actions">
                                        <button class="action-btn courses" onclick="openCourseEnrollmentModal(<?php echo $student['id']; ?>, '<?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>', '<?php echo htmlspecialchars($student['section_groupe'] ?? ''); ?>')">📚</button>
                                        <button class="action-btn edit" onclick="openEditStudentModal(<?php echo htmlspecialchars(json_encode($student)); ?>)">✏️</button>
                                        <button class="action-btn view" onclick="viewStudent(<?php echo $student['id']; ?>)">👁️</button>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this student?')">
                                            <input type="hidden" name="student_id" value="<?php echo $student['id']; ?>">
                                            <button type="submit" name="delete_student" class="action-btn delete">🗑️</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Export Options -->
                <div class="export-options">
                    <button class="btn success" onclick="exportData('excel')">
                        📊 Export to Excel
                    </button>
                    <button class="btn danger" onclick="exportData('pdf')">
                        📄 Export to PDF
                    </button>
                </div>
            </div>
        </section>
    </main>

    <!-- Add Student Modal -->
    <div class="modal" id="addStudentModal">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h3 style="margin:0">Add New Student</h3>
                    <button type="button" class="search-clear" onclick="closeAddStudentModal()">✕</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label">First Name *</label>
                        <input type="text" class="form-control" name="first_name" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Last Name *</label>
                        <input type="text" class="form-control" name="last_name" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Username *</label>
                        <input type="text" class="form-control" name="username" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Password *</label>
                        <input type="password" class="form-control" name="password" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Department</label>
                        <select class="form-control" name="department">
                            <option value="">Select Department</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo htmlspecialchars($dept); ?>"><?php echo htmlspecialchars($dept); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Section/Groupe</label>
                        <select class="form-control" name="section_groupe">
                            <option value="">Select Section/Group</option>
                            <?php foreach ($available_sections as $section): ?>
                                <option value="<?php echo htmlspecialchars($section); ?>"><?php echo htmlspecialchars($section); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small style="color: var(--muted); font-size: 12px;">Selecting a section will auto-enroll student in matching course groups</small>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Course Enrollments</label>
                        <div class="courses-select">
                            <?php foreach ($courses as $course): ?>
                                <?php if (!empty($course_groups[$course['id']])): ?>
                                    <div class="course-group">
                                        <h4><?php echo htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']); ?></h4>
                                        <?php foreach ($course_groups[$course['id']] as $group): ?>
                                            <label style="display:block;padding:4px;">
                                                <input type="checkbox" name="course_groups[]" value="<?php echo $group['id']; ?>">
                                                <?php echo htmlspecialchars($group['group_name'] . ' (' . $group['schedule_info'] . ')'); ?>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn outline" onclick="closeAddStudentModal()">Cancel</button>
                    <button type="submit" name="add_student" class="btn primary">Add Student</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Student Modal -->
    <div class="modal" id="editStudentModal">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" id="edit_student_id" name="student_id">
                <div class="modal-header">
                    <h3 style="margin:0">Edit Student</h3>
                    <button type="button" class="search-clear" onclick="closeEditStudentModal()">✕</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label">First Name *</label>
                        <input type="text" class="form-control" id="edit_first_name" name="first_name" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Last Name *</label>
                        <input type="text" class="form-control" id="edit_last_name" name="last_name" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email *</label>
                        <input type="email" class="form-control" id="edit_email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Department</label>
                        <select class="form-control" id="edit_department" name="department">
                            <option value="">Select Department</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo htmlspecialchars($dept); ?>"><?php echo htmlspecialchars($dept); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Section/Groupe</label>
                        <select class="form-control" id="edit_section_groupe" name="section_groupe">
                            <option value="">Select Section/Group</option>
                            <?php foreach ($available_sections as $section): ?>
                                <option value="<?php echo htmlspecialchars($section); ?>"><?php echo htmlspecialchars($section); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <div class="checkbox-group">
                            <input type="checkbox" id="edit_is_active" name="is_active" value="1">
                            <label for="edit_is_active">Active Student</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn outline" onclick="closeEditStudentModal()">Cancel</button>
                    <button type="submit" name="update_student" class="btn primary">Update Student</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Course Enrollment Modal -->
    <div class="modal" id="courseEnrollmentModal">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" id="enrollment_student_id" name="student_id">
                <div class="modal-header">
                    <h3 style="margin:0">Manage Course Enrollments</h3>
                    <button type="button" class="search-clear" onclick="closeCourseEnrollmentModal()">✕</button>
                </div>
                <div class="modal-body">
                    <div id="enrollmentStudentInfo" style="background:#f8fafc;padding:12px;border-radius:8px;margin-bottom:16px;">
                        <strong>Student:</strong> <span id="enrollmentStudentName"></span><br>
                        <strong>Section/Group:</strong> <span id="enrollmentStudentSection"></span>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Select Course Groups</label>
                        <div class="courses-select" id="courseGroupsContainer">
                            <?php foreach ($courses as $course): ?>
                                <?php if (!empty($course_groups[$course['id']])): ?>
                                    <div class="course-group">
                                        <h4><?php echo htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']); ?></h4>
                                        <?php foreach ($course_groups[$course['id']] as $group): ?>
                                            <label style="display:block;padding:4px;">
                                                <input type="checkbox" name="course_groups[]" value="<?php echo $group['id']; ?>" class="course-group-checkbox">
                                                <?php echo htmlspecialchars($group['group_name'] . ' (' . $group['schedule_info'] . ')'); ?>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn outline" onclick="closeCourseEnrollmentModal()">Cancel</button>
                    <button type="submit" name="update_course_enrollments" class="btn primary">Update Enrollments</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    // Search functionality
    document.getElementById('searchStudents').addEventListener('input', function(e) {
        const searchText = e.target.value.toLowerCase();
        filterStudents();
    });

    function clearSearch() {
        document.getElementById('searchStudents').value = '';
        filterStudents();
    }

    // Filter functionality
    function applyFilters() {
        filterStudents();
    }

    function resetFilters() {
        document.getElementById('filterDepartment').value = '';
        document.getElementById('filterSection').value = '';
        document.getElementById('filterStatus').value = '';
        filterStudents();
    }

    function clearFilters() {
        resetFilters();
        clearSearch();
    }

    function filterStudents() {
        const searchText = document.getElementById('searchStudents').value.toLowerCase();
        const departmentFilter = document.getElementById('filterDepartment').value;
        const sectionFilter = document.getElementById('filterSection').value;
        const statusFilter = document.getElementById('filterStatus').value;
        
        const rows = document.querySelectorAll('#studentsTable tbody tr');
        
        rows.forEach(row => {
            const name = row.cells[1].textContent.toLowerCase();
            const username = row.cells[2].textContent.toLowerCase();
            const studentId = row.cells[0].textContent.toLowerCase();
            const department = row.cells[4].textContent;
            const section = row.cells[5].textContent.trim();
            const status = row.cells[7].textContent.trim();
            
            const matchesSearch = !searchText || 
                name.includes(searchText) || 
                username.includes(searchText) || 
                studentId.includes(searchText);
            
            const matchesDepartment = !departmentFilter || department === departmentFilter;
            const matchesSection = !sectionFilter || section === sectionFilter;
            const matchesStatus = !statusFilter || 
                (statusFilter === 'active' && status === 'Active') ||
                (statusFilter === 'inactive' && status === 'Inactive');
            
            row.style.display = (matchesSearch && matchesDepartment && matchesSection && matchesStatus) ? '' : 'none';
        });
    }

    // Modal functions
    function openAddStudentModal() {
        document.getElementById('addStudentModal').classList.add('show');
    }

    function closeAddStudentModal() {
        document.getElementById('addStudentModal').classList.remove('show');
    }

    function openEditStudentModal(student) {
        document.getElementById('edit_student_id').value = student.id;
        document.getElementById('edit_first_name').value = student.first_name;
        document.getElementById('edit_last_name').value = student.last_name;
        document.getElementById('edit_email').value = student.email;
        document.getElementById('edit_department').value = student.department || '';
        document.getElementById('edit_section_groupe').value = student.section_groupe || '';
        document.getElementById('edit_is_active').checked = student.is_active == 1;
        
        document.getElementById('editStudentModal').classList.add('show');
    }

    function closeEditStudentModal() {
        document.getElementById('editStudentModal').classList.remove('show');
    }

    function openCourseEnrollmentModal(studentId, studentName, studentSection) {
        document.getElementById('enrollment_student_id').value = studentId;
        document.getElementById('enrollmentStudentName').textContent = studentName;
        document.getElementById('enrollmentStudentSection').textContent = studentSection || 'Not set';
        
        // Clear all checkboxes first
        const checkboxes = document.querySelectorAll('.course-group-checkbox');
        checkboxes.forEach(checkbox => checkbox.checked = false);
        
        // Auto-check groups that match the student's section
        if (studentSection) {
            checkboxes.forEach(checkbox => {
                const groupLabel = checkbox.parentElement.textContent;
                if (groupLabel.includes(studentSection)) {
                    checkbox.checked = true;
                }
            });
        }
        
        // Fetch current enrollments and check them
        fetch(`get_student_enrollments.php?student_id=${studentId}`)
            .then(response => response.json())
            .then(enrollments => {
                enrollments.forEach(enrollment => {
                    const checkbox = document.querySelector(`.course-group-checkbox[value="${enrollment.group_id}"]`);
                    if (checkbox) {
                        checkbox.checked = true;
                    }
                });
            })
            .catch(error => console.error('Error fetching enrollments:', error));
        
        document.getElementById('courseEnrollmentModal').classList.add('show');
    }

    function closeCourseEnrollmentModal() {
        document.getElementById('courseEnrollmentModal').classList.remove('show');
    }

    // Student management functions
    function viewStudent(studentId) {
        window.location.href = 'student_details.php?id=' + studentId;
    }

    function exportData(format) {
        alert('Exporting students data to ' + format.toUpperCase());
        // You can implement export functionality here
    }

    // Close modals when clicking outside
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal')) {
            e.target.classList.remove('show');
        }
    });

    // Initialize filters
    document.addEventListener('DOMContentLoaded', function() {
        filterStudents();
    });
    </script>
</body>
</html>