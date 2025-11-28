<?php
require_once '../config.php';

// Check if user is admin
if ($_SESSION['user_type'] != 'admin') {
    header('Location: ../login.php');
    exit();
}

$admin_id = $_SESSION['user_id'];

// Get all courses for dropdown
$courses = $db_functions->getAllCourses();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_teacher'])) {
        // Add new teacher
        $first_name = $_POST['first_name'];
        $last_name = $_POST['last_name'];
        $username = $_POST['username'];
        $email = $_POST['email'];
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $department = $_POST['department'];
        $assigned_courses = $_POST['assigned_courses'] ?? [];
        
        try {
            $pdo->beginTransaction();
            
            // Insert teacher
            $stmt = $pdo->prepare("
                INSERT INTO users (username, email, password, first_name, last_name, user_type, department, is_active) 
                VALUES (?, ?, ?, ?, ?, 'teacher', ?, 1)
            ");
            $stmt->execute([$username, $email, $password, $first_name, $last_name, $department]);
            $teacher_id = $pdo->lastInsertId();
            
            // Assign courses to teacher
            foreach ($assigned_courses as $course_id) {
                $stmt = $pdo->prepare("UPDATE courses SET teacher_id = ? WHERE id = ?");
                $stmt->execute([$teacher_id, $course_id]);
            }
            
            $pdo->commit();
            $success_message = "Teacher added successfully!";
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error_message = "Error adding teacher: " . $e->getMessage();
        }
    } elseif (isset($_POST['update_teacher'])) {
        // Update teacher
        $teacher_id = $_POST['teacher_id'];
        $first_name = $_POST['first_name'];
        $last_name = $_POST['last_name'];
        $email = $_POST['email'];
        $department = $_POST['department'];
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $assigned_courses = $_POST['assigned_courses'] ?? [];
        
        try {
            $pdo->beginTransaction();
            
            // Update teacher details
            $stmt = $pdo->prepare("
                UPDATE users 
                SET first_name = ?, last_name = ?, email = ?, department = ?, is_active = ? 
                WHERE id = ? AND user_type = 'teacher'
            ");
            $stmt->execute([$first_name, $last_name, $email, $department, $is_active, $teacher_id]);
            
            // Remove teacher from all courses first
            $stmt = $pdo->prepare("UPDATE courses SET teacher_id = NULL WHERE teacher_id = ?");
            $stmt->execute([$teacher_id]);
            
            // Assign selected courses to teacher
            foreach ($assigned_courses as $course_id) {
                $stmt = $pdo->prepare("UPDATE courses SET teacher_id = ? WHERE id = ?");
                $stmt->execute([$teacher_id, $course_id]);
            }
            
            $pdo->commit();
            $success_message = "Teacher updated successfully!";
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error_message = "Error updating teacher: " . $e->getMessage();
        }
    } elseif (isset($_POST['delete_teacher'])) {
        // Delete teacher
        $teacher_id = $_POST['teacher_id'];
        
        try {
            // First, remove teacher from courses
            $stmt = $pdo->prepare("UPDATE courses SET teacher_id = NULL WHERE teacher_id = ?");
            $stmt->execute([$teacher_id]);
            
            // Then delete the teacher
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND user_type = 'teacher'");
            $stmt->execute([$teacher_id]);
            $success_message = "Teacher deleted successfully!";
        } catch (PDOException $e) {
            $error_message = "Error deleting teacher: " . $e->getMessage();
        }
    }
}

// Get all teachers with their assigned courses
$teachers = $db_functions->getUsersByType('teacher');

// Get courses for each teacher
foreach ($teachers as &$teacher) {
    $stmt = $pdo->prepare("
        SELECT c.id, c.course_code, c.course_name 
        FROM courses c 
        WHERE c.teacher_id = ?
    ");
    $stmt->execute([$teacher['id']]);
    $teacher['assigned_courses'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
unset($teacher); // break the reference
?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Manage Teachers — Attendance Management System</title>
  <style>
    :root{
      --brand:#2b6be6;
      --accent:#4f86ff;
      --bg:#f4f7fb;
      --panel:#fff;
      --muted:#6b7280;
      --glass:rgba(15,23,42,0.04);
      --radius:12px;
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
    .layout{display:flex;padding-top:88px;max-width:1200px;margin:30px auto;gap:22px}
    .sidebar{width:260px;background:var(--panel);border-radius:14px;padding:18px;border:1px solid var(--glass);box-shadow:0 10px 30px rgba(15,23,42,0.04);height:calc(100vh - 88px);position:sticky;top:88px}
    .profile{display:flex;gap:12px;align-items:center;margin-bottom:12px}
    .avatar{width:56px;height:56px;border-radius:8px;background:#eef6ff;color:var(--brand);display:flex;align-items:center;justify-content:center;font-weight:800}
    .pmeta{font-size:13px}
    .pmeta .name{font-weight:800}
    .pmeta .role{font-size:12px;color:var(--muted)}
    .nav{margin-top:14px;display:flex;flex-direction:column;gap:8px}
    .nav a{display:flex;align-items:center;gap:10px;padding:10px;border-radius:10px;color:#28323a;text-decoration:none;font-weight:700}
    .nav a.active{background:linear-gradient(90deg,rgba(43,107,230,0.08),rgba(43,107,230,0.02));border:1px solid rgba(43,107,230,0.06);color:var(--brand)}
    .nav a.logout{color:#ef4444}
    .help{margin-top:20px;color:var(--muted);font-size:13px}
    .content{flex:1;padding:8px 12px}
    .page-head{display:flex;justify-content:space-between;align-items:flex-start;gap:12px}
    .title{font-size:22px;font-weight:800;color:#1f2937}
    .subtitle{font-size:13px;color:var(--muted);margin-top:6px}
    .btn{padding:10px 12px;border-radius:8px;border:0;cursor:pointer;font-weight:700;text-decoration:none;display:inline-block;text-align:center}
    .btn.primary{background:linear-gradient(90deg,var(--brand),var(--accent));color:#fff}
    .btn.secondary{background:#f3f4f6;color:#374151}
    .btn.danger{background:#fee2e2;color:#dc2626}
    .btn.small{padding:6px 10px;font-size:12px}
    .alert{padding:12px;border-radius:8px;margin-bottom:16px}
    .alert.success{background:#d1fae5;color:#065f46;border:1px solid #a7f3d0}
    .alert.error{background:#fee2e2;color:#dc2626;border:1px solid #fecaca}
    .table-container{background:var(--panel);border-radius:12px;border:1px solid var(--glass);box-shadow:0 10px 30px rgba(15,23,42,0.04);overflow:hidden;margin-top:18px}
    table{width:100%;border-collapse:collapse}
    th,td{padding:12px;text-align:left;border-bottom:1px solid #f3f4f6}
    th{background:#f8fafc;font-weight:700;font-size:13px;color:#374151}
    tr:hover{background:#f8fafc}
    .form-group{margin-bottom:16px}
    label{display:block;margin-bottom:6px;font-weight:600;font-size:13px;color:#374151}
    input,select,textarea{width:100%;padding:10px;border:1px solid #d1d5db;border-radius:8px;font-size:14px}
    input:focus,select:focus,textarea:focus{outline:none;border-color:var(--brand);box-shadow:0 0 0 3px rgba(43,107,230,0.1)}
    .form-row{display:grid;grid-template-columns:1fr 1fr;gap:16px}
    .modal{display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.5);z-index:50;align-items:center;justify-content:center}
    .modal.active{display:flex}
    .modal-content{background:#fff;border-radius:12px;padding:24px;max-width:600px;width:90%;max-height:90vh;overflow-y:auto}
    .modal-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:20px}
    .modal-title{font-size:18px;font-weight:700}
    .close{background:none;border:none;font-size:24px;cursor:pointer;color:#6b7280}
    .actions{display:flex;gap:8px;align-items:center}
    .status-badge{padding:4px 8px;border-radius:6px;font-size:11px;font-weight:600}
    .status-active{background:#d1fae5;color:#065f46}
    .status-inactive{background:#f3f4f6;color:#6b7280}
    .checkbox-group{display:flex;align-items:center;gap:8px}
    .checkbox-group input{width:auto}
    .course-badge{display:inline-block;background:#eef6ff;color:var(--brand);padding:4px 8px;border-radius:6px;font-size:11px;font-weight:600;margin:2px}
    .courses-list{display:flex;flex-wrap:wrap;gap:4px;margin-top:4px}
    .courses-select{height:120px;overflow-y:auto;border:1px solid #d1d5db;border-radius:8px;padding:8px}
    .courses-select option{padding:8px;margin:2px 0;border-radius:4px}
    .courses-select option:checked{background:var(--brand);color:white}
    @media (max-width:940px){
      .layout{padding:18px;max-width:940px}
      .sidebar{display:none}
      .form-row{grid-template-columns:1fr}
    }
  </style>
</head>
<body>
  <header class="topbar" role="banner">
    <div style="display:flex;align-items:center;">
      <div class="mark">UA</div>
      <div class="brand-text"><div class="title">Université Alger 1</div><div style="font-size:12px;color:var(--muted)">Administrator panel</div></div>
    </div>

    <div class="user-actions" role="navigation" aria-label="User">
      <div class="username">Welcome, <?php echo htmlspecialchars($_SESSION['first_name']); ?></div>
      <div style="width:36px;height:36px;border-radius:8px;background:linear-gradient(135deg,#f3f7ff,#eef6ff);display:flex;align-items:center;justify-content:center;color:var(--brand);font-weight:800">
        <?php echo strtoupper(substr($_SESSION['first_name'], 0, 1) . substr($_SESSION['last_name'], 0, 1)); ?>
      </div>
    </div>
  </header>

  <main class="layout" role="main">
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
        <a href="courses.php">📚 Manage Courses</a>
        <a href="teachers.php" class="active">👨‍🏫 Manage Teachers</a>
        <a href="students.php">👩‍🎓 Manage Students</a>
        <a href="../logout.php" class="logout">↩️ Logout</a>
      </nav>

      <div class="help">System Administration<br/><a href="#" style="color:var(--brand);text-decoration:none;font-weight:700">Contact support</a></div>
    </aside>

    <section class="content" aria-labelledby="teachers-title">
      <div class="page-head">
        <div>
          <h1 id="teachers-title" class="title">Manage Teachers</h1>
          <div class="subtitle">Add, edit, and manage teacher accounts and course assignments</div>
        </div>
        <button class="btn primary" onclick="openAddModal()">Add New Teacher</button>
      </div>

      <?php if (isset($success_message)): ?>
        <div class="alert success"><?php echo htmlspecialchars($success_message); ?></div>
      <?php endif; ?>

      <?php if (isset($error_message)): ?>
        <div class="alert error"><?php echo htmlspecialchars($error_message); ?></div>
      <?php endif; ?>

      <div class="table-container">
        <table>
          <thead>
            <tr>
              <th>Name</th>
              <th>Username</th>
              <th>Email</th>
              <th>Department</th>
              <th>Assigned Courses</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($teachers)): ?>
              <tr>
                <td colspan="7" style="text-align:center;padding:40px;color:var(--muted)">No teachers found</td>
              </tr>
            <?php else: ?>
              <?php foreach ($teachers as $teacher): ?>
                <tr>
                  <td><?php echo htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']); ?></td>
                  <td><?php echo htmlspecialchars($teacher['username']); ?></td>
                  <td><?php echo htmlspecialchars($teacher['email']); ?></td>
                  <td><?php echo htmlspecialchars($teacher['department'] ?? 'N/A'); ?></td>
                  <td>
                    <div class="courses-list">
                      <?php if (!empty($teacher['assigned_courses'])): ?>
                        <?php foreach ($teacher['assigned_courses'] as $course): ?>
                          <span class="course-badge" title="<?php echo htmlspecialchars($course['course_name']); ?>">
                            <?php echo htmlspecialchars($course['course_code']); ?>
                          </span>
                        <?php endforeach; ?>
                      <?php else: ?>
                        <span style="color:var(--muted);font-size:12px">No courses assigned</span>
                      <?php endif; ?>
                    </div>
                  </td>
                  <td>
                    <span class="status-badge <?php echo $teacher['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                      <?php echo $teacher['is_active'] ? 'Active' : 'Inactive'; ?>
                    </span>
                  </td>
                  <td class="actions">
                    <button class="btn secondary small" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($teacher)); ?>)">Edit</button>
                    <form method="POST" style="display:inline" onsubmit="return confirm('Are you sure you want to delete this teacher?')">
                      <input type="hidden" name="teacher_id" value="<?php echo $teacher['id']; ?>">
                      <button type="submit" name="delete_teacher" class="btn danger small">Delete</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </section>
  </main>

  <!-- Add Teacher Modal -->
  <div id="addModal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h3 class="modal-title">Add New Teacher</h3>
        <button class="close" onclick="closeAddModal()">&times;</button>
      </div>
      <form method="POST">
        <div class="form-row">
          <div class="form-group">
            <label for="first_name">First Name</label>
            <input type="text" id="first_name" name="first_name" required>
          </div>
          <div class="form-group">
            <label for="last_name">Last Name</label>
            <input type="text" id="last_name" name="last_name" required>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" required>
          </div>
          <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" required>
          </div>
        </div>
        <div class="form-group">
          <label for="password">Password</label>
          <input type="password" id="password" name="password" required>
        </div>
        <div class="form-group">
          <label for="department">Department</label>
          <input type="text" id="department" name="department">
        </div>
        <div class="form-group">
          <label for="assigned_courses">Assign Courses</label>
          <select multiple id="assigned_courses" name="assigned_courses[]" class="courses-select">
            <?php foreach ($courses as $course): ?>
              <option value="<?php echo $course['id']; ?>">
                <?php echo htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']); ?>
              </option>
            <?php endforeach; ?>
          </select>
          <div style="font-size:12px;color:var(--muted);margin-top:4px">
            Hold Ctrl/Cmd to select multiple courses
          </div>
        </div>
        <div style="display:flex;gap:12px;justify-content:flex-end;margin-top:24px">
          <button type="button" class="btn secondary" onclick="closeAddModal()">Cancel</button>
          <button type="submit" name="add_teacher" class="btn primary">Add Teacher</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Edit Teacher Modal -->
  <div id="editModal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h3 class="modal-title">Edit Teacher</h3>
        <button class="close" onclick="closeEditModal()">&times;</button>
      </div>
      <form method="POST">
        <input type="hidden" id="edit_teacher_id" name="teacher_id">
        <div class="form-row">
          <div class="form-group">
            <label for="edit_first_name">First Name</label>
            <input type="text" id="edit_first_name" name="first_name" required>
          </div>
          <div class="form-group">
            <label for="edit_last_name">Last Name</label>
            <input type="text" id="edit_last_name" name="last_name" required>
          </div>
        </div>
        <div class="form-group">
          <label for="edit_email">Email</label>
          <input type="email" id="edit_email" name="email" required>
        </div>
        <div class="form-group">
          <label for="edit_department">Department</label>
          <input type="text" id="edit_department" name="department">
        </div>
        <div class="form-group">
          <label for="edit_assigned_courses">Assign Courses</label>
          <select multiple id="edit_assigned_courses" name="assigned_courses[]" class="courses-select">
            <?php foreach ($courses as $course): ?>
              <option value="<?php echo $course['id']; ?>">
                <?php echo htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']); ?>
              </option>
            <?php endforeach; ?>
          </select>
          <div style="font-size:12px;color:var(--muted);margin-top:4px">
            Hold Ctrl/Cmd to select multiple courses
          </div>
        </div>
        <div class="form-group">
          <div class="checkbox-group">
            <input type="checkbox" id="edit_is_active" name="is_active" value="1">
            <label for="edit_is_active">Active</label>
          </div>
        </div>
        <div style="display:flex;gap:12px;justify-content:flex-end;margin-top:24px">
          <button type="button" class="btn secondary" onclick="closeEditModal()">Cancel</button>
          <button type="submit" name="update_teacher" class="btn primary">Update Teacher</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    function openAddModal() {
      document.getElementById('addModal').classList.add('active');
    }

    function closeAddModal() {
      document.getElementById('addModal').classList.remove('active');
    }

    function openEditModal(teacher) {
      document.getElementById('edit_teacher_id').value = teacher.id;
      document.getElementById('edit_first_name').value = teacher.first_name;
      document.getElementById('edit_last_name').value = teacher.last_name;
      document.getElementById('edit_email').value = teacher.email;
      document.getElementById('edit_department').value = teacher.department || '';
      document.getElementById('edit_is_active').checked = teacher.is_active == 1;
      
      // Clear previous selections
      const courseSelect = document.getElementById('edit_assigned_courses');
      for (let option of courseSelect.options) {
        option.selected = false;
      }
      
      // Select assigned courses
      if (teacher.assigned_courses && teacher.assigned_courses.length > 0) {
        for (let course of teacher.assigned_courses) {
          for (let option of courseSelect.options) {
            if (option.value == course.id) {
              option.selected = true;
              break;
            }
          }
        }
      }
      
      document.getElementById('editModal').classList.add('active');
    }

    function closeEditModal() {
      document.getElementById('editModal').classList.remove('active');
    }

    // Close modals when clicking outside
    document.addEventListener('click', function(event) {
      if (event.target.classList.contains('modal')) {
        event.target.classList.remove('active');
      }
    });
  </script>
</body>
</html>