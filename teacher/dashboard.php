<?php
require_once '../config.php';

// Check if user is teacher
if ($_SESSION['user_type'] != 'teacher') {
    header('Location: ../login.php');
    exit();
}

$teacher_id = $_SESSION['user_id'];

// Get teacher statistics
$stats = $db_functions->getTeacherStats($teacher_id);
?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Teacher Dashboard — Attendance</title>
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
    .grid{display:grid;grid-template-columns:repeat(2,1fr);gap:16px;margin-top:18px}
    .card{background:var(--panel);padding:18px;border-radius:12px;border:1px solid var(--glass);box-shadow:0 10px 30px rgba(15,23,42,0.04);min-height:140px;display:flex;flex-direction:column;justify-content:space-between}
    .card h3{margin:0;font-size:16px}
    .card p{margin:8px 0 0;color:var(--muted);font-size:13px}
    .card .cta{display:flex;gap:8px;margin-top:12px}
    .btn{padding:10px 12px;border-radius:8px;border:0;cursor:pointer;font-weight:700;text-decoration:none;display:inline-block;text-align:center}
    .btn.primary{background:linear-gradient(90deg,var(--brand),var(--accent));color:#fff}
    .quick{display:flex;gap:12px;flex-wrap:wrap;margin-top:18px}
    .tile{background:linear-gradient(180deg,#fff,#fbfdff);padding:12px;border-radius:10px;border:1px solid rgba(14,39,77,0.03);min-width:160px;box-shadow:0 6px 18px rgba(14,39,77,0.03)}
    .tile strong{display:block;font-size:20px}
    @media (max-width:940px){
      .layout{padding:18px;max-width:940px}
      .grid{grid-template-columns:1fr}
      .sidebar{display:none}
    }
  </style>
</head>
<body>
  <header class="topbar" role="banner">
    <div style="display:flex;align-items:center;">
      <div class="mark">UA</div>
      <div class="brand-text"><div class="title">Université Alger 1</div><div style="font-size:12px;color:var(--muted)">Teacher panel</div></div>
    </div>

    <div class="user-actions" role="navigation" aria-label="User">
      <div class="username">Welcome, <?php echo $_SESSION['first_name']; ?></div>
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
          <div class="name"><?php echo $_SESSION['first_name'] . ' ' . $_SESSION['last_name']; ?></div>
          <div class="role">Teacher</div>
        </div>
      </div>

      <nav class="nav" aria-label="Primary">
        <a href="dashboard.php" class="active">🏠 Dashboard</a>
        <a href="courses.php">📚 My Courses</a>
        <a href="attendance.php">🗓️ Attendance</a>
        <a href="justifications.php">📄 Justifications</a>
        <a href="../logout.php" class="logout">↩️ Logout</a>
      </nav>

      <div class="help">Need help? <br/><a href="#" style="color:var(--brand);text-decoration:none;font-weight:700">Contact support</a></div>
    </aside>

    <section class="content" aria-labelledby="dashboard-title">
      <div class="page-head">
        <div>
          <h1 id="dashboard-title" class="title">Teacher Dashboard</h1>
          <div class="subtitle">Quick access to attendance, courses and justifications</div>
        </div>

        <div style="display:flex;gap:10px;align-items:center">
          <div class="tile" aria-hidden="true"><small class="muted">Today</small><strong><?php echo date('Y-m-d'); ?></strong></div>
          <div class="tile" aria-hidden="true"><small class="muted">Upcoming sessions</small><strong><?php echo $stats['session_count']; ?></strong></div>
        </div>
      </div>

      <div class="grid" aria-hidden="true">
        <article class="card">
          <div>
            <h3>Take Attendance</h3>
            <p>Open the attendance sheets for your sessions and mark students present, absent, or late.</p>
          </div>
          <div class="cta">
            <a href="attendance.php" class="btn primary">Open Attendance</a>
          </div>
        </article>

        <article class="card">
          <div>
            <h3>My Courses</h3>
            <p>View the list of courses you teach and jump to course details or reports.</p>
          </div>
          <div class="cta">
            <a href="courses.php" class="btn primary">View Courses</a>
          </div>
        </article>

        <article class="card">
          <div>
            <h3>Justifications</h3>
            <p>Review submitted justifications from students and approve or reject with notes.</p>
          </div>
          <div class="cta">
            <a href="justifications.php" class="btn primary">Review Justifications</a>
          </div>
        </article>

        <article class="card">
          <div>
            <h3>Course Statistics</h3>
            <p>View your courses: <?php echo $stats['course_count']; ?> active course(s) with <?php echo $stats['pending_count']; ?> pending justifications.</p>
          </div>
          <div class="cta">
            <a href="courses.php" class="btn primary">View Details</a>
          </div>
        </article>
      </div>

      <div style="margin-top:18px">
        <div style="font-weight:700;margin-bottom:8px">Shortcuts</div>
        <div class="quick">
          <div class="tile"><small class="muted">My Courses</small><strong><?php echo $stats['course_count']; ?></strong></div>
          <div class="tile"><small class="muted">Pending Justifications</small><strong><?php echo $stats['pending_count']; ?></strong></div>
          <div class="tile"><small class="muted">Today's Sessions</small><strong><?php echo $stats['session_count']; ?></strong></div>
        </div>
      </div>
    </section>
  </main>
</body>
</html>