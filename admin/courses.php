<?php
require_once '../config.php';

// Check if user is admin
if ($_SESSION['user_type'] != 'admin') {
    header('Location: ../login.php');
    exit();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_course'])) {
        // Add new course
        $code = trim($_POST['code']);
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        $professor_id = $_POST['professor_id'] ?: null;
        $credits = $_POST['credits'] ?: 3;
        $semester = trim($_POST['semester']);
        
        // Validate required fields
        if (empty($code) || empty($name)) {
            $message = "Course code and name are required!";
            $message_type = "error";
        } else {
            // Add course to database
            $success = $db_functions->addCourse($code, $name, $description, $professor_id, $credits, $semester);
            if ($success) {
                $message = "Course added successfully!";
                $message_type = "success";
            } else {
                $message = "Error adding course! Course code might already exist.";
                $message_type = "error";
            }
        }
    }
    
    if (isset($_POST['delete_course'])) {
        // Delete course
        $course_id = $_POST['course_id'];
        $success = $db_functions->deleteCourse($course_id);
        if ($success) {
            $message = "Course deleted successfully!";
            $message_type = "success";
        } else {
            $message = "Error deleting course! It might have associated data.";
            $message_type = "error";
        }
    }
}

// Get all courses
$courses = $db_functions->getAllCourses();
// Get all teachers for dropdown
$teachers = $db_functions->getAllTeachers();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Management - Algiers University</title>
    <style>
    :root{
      --brand:#2b6be6;
      --accent:#4f86ff;
      --bg:#f4f7fb;
      --panel:#fff;
      --muted:#6b7280;
      --glass:rgba(15,23,42,0.04);
      --success: #16a34a;
      --error: #ef4444;
      font-family: Inter, system-ui, -apple-system, "Segoe UI", Roboto, Arial, sans-serif;
    }
    *{box-sizing:border-box}
    html,body{height:100%;margin:0;background:linear-gradient(180deg,#f8fbff 0%,var(--bg) 100%);color:#0b1220}
    .topbar{position:fixed;left:0;right:0;top:0;height:68px;background:#fff;display:flex;align-items:center;padding:0 22px;border-bottom:1px solid rgba(11,18,32,0.06);z-index:40}
    .mark{width:44px;height:44px;border-radius:8px;background:linear-gradient(135deg,var(--brand),var(--accent));display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:16px;margin-right:12px}
    .brand-text{font-size:14px;line-height:1}
    .brand-text .title{font-weight:800}
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
    .btn.success{background:var(--success);color:#fff}
    .btn.danger{background:var(--error);color:#fff}
    .btn.warning{background:#f59e0b;color:#fff}
    .btn.outline{background:transparent;border:1px solid var(--brand);color:var(--brand)}
    .table-responsive{overflow-x:auto;margin-top:16px}
    .table{width:100%;border-collapse:collapse}
    .table th,.table td{padding:12px;text-align:left;border-bottom:1px solid rgba(15,23,42,0.08)}
    .table th{font-weight:700;color:var(--muted);font-size:13px}
    .table tr:hover{background:rgba(15,23,42,0.02)}
    .badge{padding:4px 8px;border-radius:6px;font-size:12px;font-weight:600}
    .badge.primary{background:#eef6ff;color:var(--brand)}
    .badge.info{background:#f0f9ff;color:#0ea5e9}
    .badge.success{background:#f0fdf4;color:var(--success)}
    .badge.error{background:#fef2f2;color:var(--error)}
    .form-group{margin-bottom:16px}
    .form-label{display:block;margin-bottom:6px;font-weight:600;font-size:14px}
    .form-control{width:100%;padding:10px;border:1px solid rgba(15,23,42,0.1);border-radius:8px;font-size:14px}
    .form-control:focus{outline:none;border-color:var(--brand)}
    .modal{display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(15,23,42,0.5);z-index:50;align-items:center;justify-content:center}
    .modal.show{display:flex}
    .modal-content{background:#fff;border-radius:12px;width:90%;max-width:600px;max-height:90vh;overflow:auto}
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
    .action-btn.groups{background:#fffbeb;color:#f59e0b}
    .action-btn.delete{background:#fef2f2;color:var(--error)}
    .course-description{font-size:12px;color:var(--muted);margin-top:4px}
    .alert{padding:12px;border-radius:8px;margin-bottom:16px;font-weight:600}
    .alert.success{background:#f0fdf4;color:var(--success);border:1px solid #bbf7d0}
    .alert.error{background:#fef2f2;color:var(--error);border:1px solid #fecaca}
    @media (max-width:960px){.sidebar{display:none}.layout{padding:88px 12px 12px 12px}.actions{flex-wrap:wrap}}
    </style>
</head>
<body>
    <!-- Top Bar -->
    <header class="topbar" role="banner">
        <div style="display:flex;align-items:center;">
            <div class="mark">UA</div>
            <div class="brand-text">
                <div class="title">Université Alger 1</div>
                <div style="font-size:12px;color:var(--muted)">Admin panel</div>
            </div>
        </div>
        <div class="user-actions" role="navigation" aria-label="User">
            <div class="username">Welcome, <?php echo htmlspecialchars($_SESSION['first_name']); ?></div>
            <div style="width:36px;height:36px;border-radius:8px;background:linear-gradient(135deg,#f3f7ff,#eef6ff);display:flex;align-items:center;justify-content:center;color:var(--brand);font-weight:800">
                <?php echo strtoupper(substr($_SESSION['first_name'], 0, 1) . substr($_SESSION['last_name'], 0, 1)); ?>
            </div>
        </div>
    </header>

    <main class="layout" role="main">
        <!-- Sidebar -->
        <aside class="sidebar" aria-label="Main navigation">
            <div class="profile">
                <div class="avatar">
                    <?php echo strtoupper(substr($_SESSION['first_name'], 0, 1) . substr($_SESSION['last_name'], 0, 1)); ?>
                </div>
                <div class="pmeta">
                    <div class="name"><?php echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']); ?></div>
                    <div class="role">Administrator</div>
                </div>
            </div>

            <nav class="nav" aria-label="Primary">
                <a href="dashboard.php">🏠 Dashboard</a>
                <a href="courses.php" class="active">📚 Manage Courses</a>
                <a href="teachers.php">👨‍🏫 Manage Teachers</a>
                <a href="students.php">👩‍🎓 Manage Students</a>
                <a href="../logout.php" class="logout">↩️ Logout</a>
            </nav>
        </aside>

        <!-- Main Content -->
        <section class="content" aria-labelledby="courses-title">
            <div class="page-head">
                <div>
                    <h1 id="courses-title" class="title">Course Management</h1>
                    <div class="subtitle">Manage courses and their information</div>
                </div>
                <button class="btn primary" onclick="openAddCourseModal()">
                    📚 Add New Course
                </button>
            </div>

            <?php if (isset($message)): ?>
                <div class="alert <?php echo $message_type; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <!-- Search Box -->
            <div class="search-box">
                <input type="text" class="search-input" placeholder="Search courses..." id="searchCourses">
                <button class="search-clear" onclick="clearSearch()">✕</button>
            </div>

            <!-- Courses Table -->
            <div class="card">
                <h3 style="margin:0 0 16px 0">Course List (<?php echo count($courses); ?> courses)</h3>
                <div class="table-responsive">
                    <table class="table" id="coursesTable">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Course Name</th>
                                <th>Professor</th>
                                <th>Credits</th>
                                <th>Semester</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($courses)): ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; color: var(--muted);">
                                        No courses found. Add your first course!
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($courses as $course): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($course['course_code']); ?></strong></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($course['course_name']); ?></strong>
                                            <?php if (!empty($course['description'])): ?>
                                                <div class="course-description"><?php echo htmlspecialchars($course['description']); ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($course['professor_name'] ?? 'Not assigned'); ?></td>
                                        <td><?php echo htmlspecialchars($course['credits']); ?></td>
                                        <td><span class="badge info"><?php echo htmlspecialchars($course['semester']); ?></span></td>
                                        <td>
                                            <span class="badge <?php echo ($course['is_active'] ? 'success' : 'error'); ?>">
                                                <?php echo ($course['is_active'] ? 'Active' : 'Inactive'); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="actions">
                                                <button class="action-btn edit" onclick="editCourse(<?php echo $course['id']; ?>)">✏️</button>
                                                <button class="action-btn view" onclick="viewCourse(<?php echo $course['id']; ?>)">👁️</button>
                                                <button class="action-btn groups" onclick="manageGroups(<?php echo $course['id']; ?>)">👥</button>
                                                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this course? This action cannot be undone.')">
                                                    <input type="hidden" name="course_id" value="<?php echo $course['id']; ?>">
                                                    <input type="hidden" name="delete_course" value="1">
                                                    <button type="submit" class="action-btn delete">🗑️</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </main>

    <!-- Add Course Modal -->
    <div class="modal" id="addCourseModal">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h3 style="margin:0">Add New Course</h3>
                    <button type="button" class="search-clear" onclick="closeAddCourseModal()">✕</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label">Course Code *</label>
                        <input type="text" class="form-control" name="code" required placeholder="e.g., MATH101" 
                               pattern="[A-Za-z0-9]{2,20}" title="Course code should be 2-20 alphanumeric characters">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Course Name *</label>
                        <input type="text" class="form-control" name="name" required placeholder="e.g., Mathematics Fundamentals">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="3" placeholder="Course description..."></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Professor</label>
                        <select class="form-control" name="professor_id">
                            <option value="">Select Professor</option>
                            <?php foreach ($teachers as $teacher): ?>
                                <option value="<?php echo $teacher['id']; ?>">
                                    <?php echo htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Credits</label>
                        <input type="number" class="form-control" name="credits" value="3" min="1" max="6">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Semester</label>
                        <select class="form-control" name="semester">
                            <option value="Fall 2024">Fall 2024</option>
                            <option value="Spring 2025">Spring 2025</option>
                            <option value="Summer 2025">Summer 2025</option>
                            <option value="Fall 2025">Fall 2025</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn outline" onclick="closeAddCourseModal()">Cancel</button>
                    <button type="submit" name="add_course" value="1" class="btn primary">Add Course</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    // Search functionality
    document.getElementById('searchCourses').addEventListener('input', function(e) {
        const searchText = e.target.value.toLowerCase();
        const rows = document.querySelectorAll('#coursesTable tbody tr');
        
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(searchText) ? '' : 'none';
        });
    });

    function clearSearch() {
        document.getElementById('searchCourses').value = '';
        const rows = document.querySelectorAll('#coursesTable tbody tr');
        rows.forEach(row => row.style.display = '');
    }

    // Modal functions
    function openAddCourseModal() {
        document.getElementById('addCourseModal').classList.add('show');
    }

    function closeAddCourseModal() {
        document.getElementById('addCourseModal').classList.remove('show');
        // Reset form when closing modal
        document.querySelector('#addCourseModal form').reset();
    }

    // Course management functions
    function editCourse(courseId) {
        alert('Edit functionality for course ' + courseId + ' will be implemented soon!');
    }

    function viewCourse(courseId) {
        alert('View functionality for course ' + courseId + ' will be implemented soon!');
    }

    function manageGroups(courseId) {
        alert('Group management for course ' + courseId + ' will be implemented soon!');
    }

    // Close modal when clicking outside
    document.getElementById('addCourseModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeAddCourseModal();
        }
    });

    // Close modal with Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeAddCourseModal();
        }
    });
    </script>
</body>
</html>