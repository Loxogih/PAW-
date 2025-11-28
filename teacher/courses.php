<?php
require_once '../config.php';

// Check if user is teacher
if ($_SESSION['user_type'] != 'teacher') {
    header('Location: ../login.php');
    exit();
}

$teacher_id = $_SESSION['user_id'];

// Get teacher's courses using the DBFunctions method
$courses = $db_functions->getTeacherCourses($teacher_id);
?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>My Courses — Teacher</title>
  <style>
    :root{
      --bg:#f4f7fb;
      --card:#ffffff;
      --accent:#2b6be6;
      --accent-2:#4f86ff;
      --muted:#6b7280;
      --danger:#ef4444;
      --glass: rgba(15,23,42,0.04);
      --radius:12px;
      font-family: Inter, system-ui, -apple-system, "Segoe UI", Roboto, Arial;
      color-scheme: light;
    }
    *{box-sizing:border-box}
    html,body{height:100%;margin:0;background:linear-gradient(180deg,#f8fbff 0%,var(--bg) 100%);color:#0f172a}
    .topbar{height:66px;background:#fff;border-bottom:1px solid var(--glass);display:flex;align-items:center;gap:18px;padding:0 24px;position:sticky;top:0;z-index:20}
    .topbar .mark{display:flex;align-items:center;gap:10px}
    .mark .logo{width:44px;height:44px;border-radius:8px;background:linear-gradient(135deg,var(--accent),var(--accent-2));display:flex;align-items:center;justify-content:center;color:#fff;font-weight:800}
    .topbar h1{font-size:15px;margin:0}
    .layout{display:flex;gap:22px;padding:24px;max-width:1200px;margin:26px auto;align-items:flex-start}
    /* left sidebar (match attendance UI) */
    .sidebar{width:260px;background:var(--card);border-radius:14px;padding:18px;border:1px solid var(--glass);box-shadow:0 10px 30px rgba(15,23,42,0.04)}
    .profile{display:flex;gap:12px;align-items:center;margin-bottom:12px}
    .avatar{width:56px;height:56px;border-radius:8px;background:#eef6ff;color:var(--accent);display:flex;align-items:center;justify-content:center;font-weight:800}
    .pmeta{font-size:13px}
    .nav{margin-top:8px}
    .nav a{display:flex;align-items:center;gap:10px;padding:10px;border-radius:9px;color:#263238;text-decoration:none;font-weight:700;font-size:13px;margin-bottom:6px}
    .nav a.active{background:linear-gradient(90deg,rgba(43,107,230,0.08),rgba(43,107,230,0.02));border:1px solid rgba(43,107,230,0.06);color:var(--accent)}
    .nav a.logout{color:var(--danger)}
    .help{margin-top:18px;color:var(--muted);font-size:13px}
    /* main */
    .main{flex:1}
    .header-row{display:flex;justify-content:space-between;align-items:center;gap:16px;margin-bottom:18px}
    .title{font-size:20px;font-weight:800;color:#273241}
    .subtitle{font-size:13px;color:var(--muted)}
    .controls{display:flex;gap:10px;align-items:center;flex-wrap:wrap}
    .search, .select{padding:10px 12px;border-radius:10px;border:1px solid #e6eef7;background:#fff;font-size:14px}
    .search{width:320px;display:flex;align-items:center;gap:8px}
    .search input{width:100%;border:none;outline:none;background:none}
    /* courses list */
    .list{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:16px}
    .card{background:var(--card);border-radius:14px;padding:18px;border:1px solid var(--glass);box-shadow:0 12px 30px rgba(15,23,42,0.06);display:flex;flex-direction:column;justify-content:space-between}
    .course-head{display:flex;justify-content:space-between;align-items:start;gap:12px}
    .course-info{display:flex;gap:12px;align-items:center}
    .course-badge{min-width:72px;background:linear-gradient(90deg,var(--accent),var(--accent-2));color:white;padding:8px;border-radius:8px;font-weight:800;display:flex;align-items:center;justify-content:center}
    .meta{color:var(--muted);font-size:13px;margin-top:8px}
    .card-actions{display:flex;gap:8px;margin-top:14px}
    .card-actions .btn{flex:1;padding:10px;border-radius:10px;border:1px solid #e6eef7;background:#fff;cursor:pointer;font-weight:700;text-decoration:none;display:inline-block;text-align:center;color:#374151;transition:all 0.2s}
    .card-actions .btn.primary{background:linear-gradient(90deg,var(--accent),var(--accent-2));color:#fff;border:none}
    .card-actions .btn:hover{transform:translateY(-1px);box-shadow:0 4px 12px rgba(15,23,42,0.1)}
    .empty{padding:40px;border-radius:12px;text-align:center;background:linear-gradient(180deg,#fff,#fbfdff);border:1px dashed var(--glass);color:var(--muted)}
    .stats{display:flex;gap:12px;margin-bottom:20px}
    .stat-card{background:var(--card);padding:16px;border-radius:12px;border:1px solid var(--glass);flex:1;text-align:center}
    .stat-number{font-size:24px;font-weight:800;color:var(--accent)}
    .stat-label{font-size:12px;color:var(--muted);margin-top:4px}
    @media (max-width:900px){.layout{padding:18px}.sidebar{display:none}.search{width:100%}.header-row{flex-direction:column;align-items:stretch}.stats{flex-direction:column}}
  </style>
</head>
<body>
  <header class="topbar" role="banner">
    <div class="mark" aria-hidden="true">
      <div class="logo">UA</div>
    </div>
    <div>
      <h1 style="margin:0;font-size:15px;font-weight:800">Université Alger 1</h1>
      <div style="font-size:12px;color:var(--muted)">Attendance System — My courses</div>
    </div>
  </header>

  <div class="layout" role="main">
    <aside class="sidebar" aria-label="left navigation">
      <div class="profile">
        <div class="avatar">
          <?php echo strtoupper(substr($_SESSION['first_name'], 0, 1) . substr($_SESSION['last_name'], 0, 1)); ?>
        </div>
        <div>
          <div style="font-weight:800"><?php echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']); ?></div>
          <div style="font-size:13px;color:var(--muted)">Teacher</div>
        </div>
      </div>

      <nav class="nav" aria-label="section nav">
        <a href="dashboard.php">🏠 Dashboard</a>
        <a href="courses.php" class="active">📚 My Courses</a>
        <a href="attendance.php">🗓️ Attendance Sheet</a>
        <a href="justifications.php">📄 Justifications</a>
        <a href="../logout.php" class="logout">↩️ Logout</a>
      </nav>

      <div class="help">Need help? <br/><a href="#" style="color:var(--accent);text-decoration:none;font-weight:700">Contact support</a></div>
    </aside>

    <main class="main">
      <div class="header-row">
        <div>
          <div class="title">My Courses</div>
          <div class="subtitle">Courses you're currently assigned to — click into a course to manage sessions and attendance</div>
        </div>

        <div class="controls" aria-hidden="true">
          <div class="search" role="search" aria-label="Search courses">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" style="opacity:0.6"><path d="M21 21l-4.35-4.35" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/><circle cx="11" cy="11" r="6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
            <input type="text" placeholder="Search courses (code, name)" id="searchCourses"/>
          </div>

          <select class="select" aria-label="filter" id="semesterFilter">
            <option>All Semesters</option>
            <option>Spring 2025</option>
            <option>Fall 2024</option>
          </select>
        </div>
      </div>

      <!-- Teacher Statistics -->
      <div class="stats">
        <div class="stat-card">
          <div class="stat-number"><?php echo count($courses); ?></div>
          <div class="stat-label">Total Courses</div>
        </div>
        <?php
        // Get teacher stats
        $teacher_stats = $db_functions->getTeacherStats($teacher_id);
        ?>
        <div class="stat-card">
          <div class="stat-number"><?php echo $teacher_stats['pending_count']; ?></div>
          <div class="stat-label">Pending Justifications</div>
        </div>
        <div class="stat-card">
          <div class="stat-number"><?php echo $teacher_stats['session_count']; ?></div>
          <div class="stat-label">Today's Sessions</div>
        </div>
      </div>

      <section class="list" aria-label="courses list">
        <?php if (empty($courses)): ?>
          <div class="empty">
            <h3>No Courses Assigned</h3>
            <p>You are not currently assigned to any courses.</p>
          </div>
        <?php else: ?>
          <?php foreach ($courses as $course): ?>
          <article class="card" aria-labelledby="c<?php echo $course['id']; ?>">
            <div>
              <div class="course-head">
                <div class="course-info">
                  <div class="course-badge"><?php echo htmlspecialchars($course['course_code']); ?></div>
                  <div>
                    <div id="c<?php echo $course['id']; ?>" style="font-weight:800;font-size:16px"><?php echo htmlspecialchars($course['course_name']); ?></div>
                    <div class="meta">
                      <?php echo htmlspecialchars($course['semester']); ?> • 
                      <?php echo $course['student_count']; ?> enrolled students
                      <?php if ($course['description']): ?>
                        <br><?php echo htmlspecialchars($course['description']); ?>
                      <?php endif; ?>
                    </div>
                  </div>
                </div>
                <div style="text-align:right;color:var(--muted);font-size:13px">
                  <?php echo $course['is_active'] ? 'Active' : 'Inactive'; ?>
                </div>
              </div>
            </div>

            <div class="card-actions">
              <a href="attendance.php?course_id=<?php echo $course['id']; ?>" class="btn primary">Take Attendance</a>
              <a href="course_groups.php?course_id=<?php echo $course['id']; ?>" class="btn">View Groups</a>
            </div>
          </article>
          <?php endforeach; ?>
        <?php endif; ?>
      </section>
    </main>
  </div>

  <script>
    // Search functionality
    document.getElementById('searchCourses').addEventListener('input', function(e) {
      const searchText = e.target.value.toLowerCase();
      const cards = document.querySelectorAll('.card');
      
      cards.forEach(card => {
        const text = card.textContent.toLowerCase();
        card.style.display = text.includes(searchText) ? '' : 'none';
      });
    });

    // Semester filter
    document.getElementById('semesterFilter').addEventListener('change', function(e) {
      const selectedSemester = e.target.value;
      const cards = document.querySelectorAll('.card');
      
      cards.forEach(card => {
        if (selectedSemester === 'All Semesters') {
          card.style.display = '';
        } else {
          const text = card.textContent.toLowerCase();
          card.style.display = text.includes(selectedSemester.toLowerCase()) ? '' : 'none';
        }
      });
    });

    // Auto-populate semester filter based on actual course data
    document.addEventListener('DOMContentLoaded', function() {
      const cards = document.querySelectorAll('.card');
      const semesters = new Set();
      
      cards.forEach(card => {
        const meta = card.querySelector('.meta');
        if (meta) {
          const text = meta.textContent;
          // Extract semester from meta text (format: "Semester Year • X enrolled students")
          const semesterMatch = text.match(/(Spring|Fall|Summer|Winter)\s+\d{4}/);
          if (semesterMatch) {
            semesters.add(semesterMatch[0]);
          }
        }
      });
      
      // Update semester filter options
      const semesterFilter = document.getElementById('semesterFilter');
      if (semesters.size > 0) {
        // Clear existing options except "All Semesters"
        while (semesterFilter.children.length > 1) {
          semesterFilter.removeChild(semesterFilter.lastChild);
        }
        
        // Add unique semesters from courses
        semesters.forEach(semester => {
          const option = document.createElement('option');
          option.value = semester;
          option.textContent = semester;
          semesterFilter.appendChild(option);
        });
      }
    });
  </script>
</body>
</html>