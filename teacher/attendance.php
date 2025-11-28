<?php
require_once '../config.php';

// Check if user is teacher
if ($_SESSION['user_type'] != 'teacher') {
    header('Location: ../login.php');
    exit();
}

$teacher_id = $_SESSION['user_id'];

// Get teacher's courses for dropdown
$teacher_courses = $db_functions->getTeacherCourses($teacher_id);

// Get course groups
$course_groups = $db_functions->getTeacherCourseGroups($teacher_id);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['submit_attendance'])) {
        $session_date = $_POST['session_date'];
        $course_id = $_POST['course_id'];
        $group_id = $_POST['group_id'];
        
        // Create session first
        if ($db_functions->createSession($course_id, $group_id, $session_date, $teacher_id)) {
            // Get the last inserted session ID using a separate query
            $session_stmt = $pdo->query("SELECT LAST_INSERT_ID() as session_id");
            $session_result = $session_stmt->fetch();
            $session_id = $session_result['session_id'];
            
            // Insert attendance records
            if (isset($_POST['attendance'])) {
                foreach ($_POST['attendance'] as $student_id => $status) {
                    $db_functions->recordAttendance($student_id, $session_id, $course_id, $group_id, $status, $teacher_id);
                }
            }
            
            $success = "Attendance recorded successfully!";
        } else {
            $error = "Error creating session!";
        }
    } elseif (isset($_POST['generate_sheet'])) {
        // Handle generate sheet form submission
        $course_id = $_POST['course_id'];
        $group_id = $_POST['group_id'];
        header("Location: attendance.php?course_id=" . urlencode($course_id) . "&group_id=" . urlencode($group_id));
        exit();
    }
}

// Get students for selected group
$students = [];
$selected_course_id = isset($_GET['course_id']) ? $_GET['course_id'] : (isset($_POST['course_id']) ? $_POST['course_id'] : '');
$selected_group_id = isset($_GET['group_id']) ? $_GET['group_id'] : (isset($_POST['group_id']) ? $_POST['group_id'] : '');

if (!empty($selected_group_id)) {
    $students = $db_functions->getStudentsInGroup($selected_group_id);
}
?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Teacher — Attendance Sheet</title>
  <style>
    :root{
      --brand:#2b6be6;
      --bg:#f4f7fb;
      --panel:#fff;
      --muted:#6b7280;
      --soft:#f6f8fb;
      --radius:10px;
      --accent-gradient: linear-gradient(90deg,#2b6be6,#4f86ff);
      font-family: Inter, system-ui, -apple-system, "Segoe UI", Roboto, Arial;
      color-scheme: light;
    }
    *{box-sizing:border-box}
    html,body{height:100%;margin:0;background:var(--bg);color:#0b1220}
    /* Topbar */
    .topbar{
      position:fixed;left:0;right:0;top:0;height:68px;background:#fff;display:flex;align-items:center;padding:0 22px;border-bottom:1px solid rgba(11,18,32,0.06);z-index:40;
    }
    .logo{display:flex;align-items:center;gap:12px}
    .mark{width:44px;height:44px;border-radius:8px;background:linear-gradient(135deg,#2b6be6,#6b9cf8);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:16px}
    .brand-text{font-size:13px;line-height:1}
    .brand-text .school{font-weight:700;color:var(--brand)}
    .search {margin-left:28px;flex:1;display:flex;align-items:center;gap:12px}
    .user-actions{display:flex;gap:12px;align-items:center}
    .user-actions .username{font-size:13px;color:var(--muted)}
    /* Page layout */
    .layout{display:flex;padding-top:68px;min-height:calc(100vh - 68px)}
    /* Sidebar */
    .sidebar{width:260px;background:var(--panel);border-right:1px solid rgba(11,18,32,0.04);padding:20px;position:sticky;top:68px;height:calc(100vh - 68px)}
    .sidebar .profile{display:flex;align-items:center;gap:12px;margin-bottom:18px}
    .avatar{width:56px;height:56px;border-radius:8px;background:#eef6ff;color:var(--brand);display:flex;align-items:center;justify-content:center;font-weight:800;font-size:18px}
    .pinfo{font-size:13px}
    .pinfo .name{font-weight:700;color:#0b1220}
    .pinfo .role{font-size:12px;color:var(--muted)}
    .nav{margin-top:12px}
    .nav a{display:flex;align-items:center;gap:12px;padding:10px 12px;border-radius:8px;color:#324050;text-decoration:none;font-weight:700;margin-bottom:6px}
    .nav a .icon{width:22px;height:22px;display:inline-flex;align-items:center;justify-content:center;color:var(--muted)}
    .nav a.active{background:linear-gradient(90deg,rgba(43,107,230,0.08),rgba(43,107,230,0.02));border:1px solid rgba(43,107,230,0.06);color:var(--brand)}
    .nav a.logout{color:#ef4444}
    .sidebar .help{margin-top:18px;color:var(--muted);font-size:13px}
    /* Content */
    .content{flex:1;padding:28px 36px}
    .page-head{display:flex;justify-content:space-between;align-items:flex-start}
    .page-title{font-size:18px;font-weight:800;color:#2b3440}
    .page-sub{font-size:13px;color:var(--muted);margin-top:4px}
    .controls{display:flex;gap:10px;align-items:center;margin-top:16px;flex-wrap:wrap}
    .select, .input, .btn{padding:10px 12px;border-radius:8px;border:1px solid #e6eef7;background:#fff;font-size:14px}
    .select{min-width:160px}
    .btn.primary{background:var(--accent-gradient);color:#fff;border:0;padding:10px 16px;font-weight:700;cursor:pointer;border-radius:8px}
    .card{background:var(--panel);padding:18px;border-radius:12px;border:1px solid rgba(11,18,32,0.04);box-shadow: 0 10px 30px rgba(11,18,32,0.03);margin-top:20px}
    .card h3{margin:0 0 6px 0;font-size:15px}
    .card .muted{color:var(--muted);font-size:13px;margin-bottom:12px}
    /* Table */
    table{width:100%;border-collapse:collapse;margin-top:6px}
    thead th{font-size:13px;color:#334155;text-align:left;padding:12px;background:transparent;border-bottom:1px solid #eef3f9;font-weight:700}
    tbody td{padding:12px;border-bottom:1px dashed #f1f5f9;color:#374151;font-size:14px}
    .status-select{width:100%;padding:8px;border-radius:6px;border:1px solid #e6eef7;background:#fff;font-size:13px}
    .status-pill{display:inline-block;padding:6px 10px;border-radius:999px;font-weight:700;font-size:12px}
    .status-present{background:rgba(34,197,94,0.12);color:#16a34a}
    .status-absent{background:rgba(239,68,68,0.08);color:#ef4444}
    .status-late{background:rgba(250,204,21,0.12);color:#b45309}
    .status-excused{background:rgba(168,85,247,0.12);color:#9333ea}
    .submit-row{display:flex;justify-content:flex-start;margin-top:14px}
    .alert{background:#d1fae5;color:#065f46;padding:12px;border-radius:8px;margin-bottom:16px;border:1px solid #a7f3d0}
    .alert.error{background:#fee2e2;color:#991b1b;border-color:#fecaca}
    /* Responsive */
    @media (max-width:980px){
      .sidebar{display:none}
      .content{padding:20px}
      .controls{flex-direction:column;align-items:stretch}
      .select{width:100%}
    }
  </style>
</head>
<body>
  <header class="topbar" role="banner">
    <div class="logo" aria-hidden="true">
      <div class="mark">UA</div>
      <div class="brand-text">
        <div class="school">Université</div>
        <div style="font-size:12px;color:var(--muted)">Alger 1</div>
      </div>
    </div>

    <div class="search" aria-hidden="true">
      <!-- placeholder for future search or breadcrumbs -->
    </div>

    <div class="user-actions" role="navigation">
      <div class="username"><?php echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']); ?></div>
      <div style="width:36px;height:36px;border-radius:8px;background:linear-gradient(135deg,#f3f7ff,#eef6ff);display:flex;align-items:center;justify-content:center;color:var(--brand);font-weight:800">
        <?php echo strtoupper(substr($_SESSION['first_name'], 0, 1) . substr($_SESSION['last_name'], 0, 1)); ?>
      </div>
    </div>
  </header>

  <div class="layout" role="main">
    <aside class="sidebar" aria-label="Left sidebar navigation">
      <div class="profile">
        <div class="avatar">
          <?php echo strtoupper(substr($_SESSION['first_name'], 0, 1) . substr($_SESSION['last_name'], 0, 1)); ?>
        </div>
        <div class="pinfo">
          <div class="name"><?php echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']); ?></div>
          <div class="role">Teacher</div>
        </div>
      </div>

      <nav class="nav" aria-label="Primary">
        <a href="dashboard.php"><span class="icon">🏠</span> Dashboard</a>
        <a href="courses.php"><span class="icon">📚</span> My Courses</a>
        <a href="attendance.php" class="active"><span class="icon">🗓️</span> Attendance Sheet</a>
        <a href="justifications.php"><span class="icon">📄</span> Justifications</a>
        <a href="../logout.php" class="logout"><span class="icon">↩️</span> Logout</a>
      </nav>

      <div class="help">Need help? <br/><a href="#" style="color:var(--brand);text-decoration:none;font-weight:700">Contact support</a></div>
    </aside>

    <section class="content">
      <div class="page-head">
        <div>
          <div class="page-title">Attendance</div>
          <div class="page-sub">Create and record attendance for your course sessions</div>
        </div>
        <div style="display:flex;gap:10px;align-items:center">
          <div style="font-size:13px;color:var(--muted)">Session date: <strong style="margin-left:6px"><?php echo date('Y-m-d'); ?></strong></div>
        </div>
      </div>

      <?php if (isset($success)): ?>
        <div class="alert"><?php echo htmlspecialchars($success); ?></div>
      <?php endif; ?>
      
      <?php if (isset($error)): ?>
        <div class="alert error"><?php echo htmlspecialchars($error); ?></div>
      <?php endif; ?>

      <form method="POST" action="">
        <div class="controls">
          <select class="select" name="course_id" aria-label="Course" required>
            <option value="">Select Course</option>
            <?php foreach ($teacher_courses as $course): ?>
              <option value="<?php echo $course['id']; ?>" <?php echo ($selected_course_id == $course['id']) ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']); ?>
              </option>
            <?php endforeach; ?>
          </select>

          <select class="select" name="group_id" aria-label="Group" required>
            <option value="">Select Group</option>
            <?php foreach ($course_groups as $group): ?>
              <?php if ($group['course_id'] == $selected_course_id || empty($selected_course_id)): ?>
                <option value="<?php echo $group['id']; ?>" <?php echo ($selected_group_id == $group['id']) ? 'selected' : ''; ?> data-course-id="<?php echo $group['course_id']; ?>">
                  <?php echo htmlspecialchars($group['group_name'] . ' (' . $group['course_code'] . ')'); ?>
                </option>
              <?php endif; ?>
            <?php endforeach; ?>
          </select>

          <input class="input" type="date" name="session_date" value="<?php echo date('Y-m-d'); ?>" aria-label="Date" required>

          <button type="submit" name="generate_sheet" class="btn primary">Generate Sheet</button>
        </div>
      </form>

      <?php if (!empty($students)): ?>
      <form method="POST" action="">
        <input type="hidden" name="course_id" value="<?php echo htmlspecialchars($selected_course_id); ?>">
        <input type="hidden" name="group_id" value="<?php echo htmlspecialchars($selected_group_id); ?>">
        <input type="hidden" name="session_date" value="<?php echo date('Y-m-d'); ?>">
        
        <article class="card" aria-labelledby="sheet-title" style="margin-top:20px">
          <h3 id="sheet-title">Attendance Sheet</h3>
          <div class="muted">Select student statuses below and submit the recorded attendance.</div>

          <table aria-describedby="sheet-title" role="table">
            <thead>
              <tr>
                <th style="width:40px">#</th>
                <th>Student name</th>
                <th>Student ID</th>
                <th style="width:140px">Status</th>
              </tr>
            </thead>
            <tbody id="studentsBody">
              <?php foreach ($students as $index => $student): ?>
              <tr>
                <td><?php echo $index + 1; ?></td>
                <td><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></td>
                <td><?php echo htmlspecialchars($student['student_id']); ?></td>
                <td>
                  <select class="status-select" name="attendance[<?php echo $student['id']; ?>]" required>
                    <option value="present">Present</option>
                    <option value="absent">Absent</option>
                    <option value="late">Late</option>
                    <option value="excused">Excused</option>
                  </select>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>

          <div class="submit-row">
            <button type="submit" name="submit_attendance" class="btn primary">Submit Attendance</button>
          </div>
        </article>
      </form>
      <?php elseif (!empty($selected_course_id) && !empty($selected_group_id)): ?>
        <div class="card" style="margin-top:20px; text-align:center;">
          <h3>No Students Found</h3>
          <div class="muted">There are no students enrolled in the selected group.</div>
        </div>
      <?php endif; ?>
    </section>
  </div>

  <script>
    // Filter groups based on selected course
    document.querySelector('select[name="course_id"]').addEventListener('change', function() {
      const courseId = this.value;
      const groupSelect = document.querySelector('select[name="group_id"]');
      const groups = groupSelect.querySelectorAll('option');
      
      groups.forEach(option => {
        if (option.value === '') return; // Keep "Select Group" option
        
        const groupCourseId = option.getAttribute('data-course-id');
        // Show all groups if no course selected, otherwise filter by course
        if (!courseId || groupCourseId === courseId) {
          option.style.display = '';
        } else {
          option.style.display = 'none';
        }
      });
      
      // Reset group selection when course changes
      groupSelect.value = '';
    });

    // Initialize group filtering on page load
    document.addEventListener('DOMContentLoaded', function() {
      // Trigger change event to apply initial filtering
      document.querySelector('select[name="course_id"]').dispatchEvent(new Event('change'));
    });
  </script>
</body>
</html>