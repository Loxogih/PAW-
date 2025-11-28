<?php
require_once '../config.php';

// Check if user is admin
if ($_SESSION['user_type'] != 'admin') {
    header('Location: ../login.php');
    exit();
}

$admin_id = $_SESSION['user_id'];

// Get admin statistics
$stats = $db_functions->getAdminStats();
?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Admin Dashboard — Attendance Management System</title>
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
    .stats-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-top:18px}
    .stat-card{background:var(--panel);padding:20px;border-radius:12px;border:1px solid var(--glass);text-align:center}
    .stat-number{font-size:28px;font-weight:800;color:var(--brand)}
    .stat-label{font-size:13px;color:var(--muted);margin-top:8px}
    @media (max-width:940px){
      .layout{padding:18px;max-width:940px}
      .grid{grid-template-columns:1fr}
      .stats-grid{grid-template-columns:repeat(2,1fr)}
      .sidebar{display:none}
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
        <a href="dashboard.php" class="active">🏠 Dashboard</a>
        <a href="courses.php">📚 Manage Courses</a>
        <a href="teachers.php">👨‍🏫 Manage Teachers</a>
        <a href="students.php">👩‍🎓 Manage Students</a>
        <a href="../logout.php" class="logout">↩️ Logout</a>
      </nav>

      <div class="help">System Administration<br/><a href="#" style="color:var(--brand);text-decoration:none;font-weight:700">Contact support</a></div>
    </aside>

    <section class="content" aria-labelledby="dashboard-title">
      <div class="page-head">
        <div>
          <h1 id="dashboard-title" class="title">Admin Dashboard</h1>
          <div class="subtitle">System overview and management tools</div>
        </div>

        <div style="display:flex;gap:10px;align-items:center">
          <div class="tile" aria-hidden="true"><small class="muted">Today</small><strong><?php echo date('Y-m-d'); ?></strong></div>
          <div class="tile" aria-hidden="true"><small class="muted">System Status</small><strong>Online</strong></div>
        </div>
      </div>

      <!-- Statistics Overview -->
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-number"><?php echo htmlspecialchars($stats['total_teachers']); ?></div>
          <div class="stat-label">Teachers</div>
        </div>
        <div class="stat-card">
          <div class="stat-number"><?php echo htmlspecialchars($stats['total_students']); ?></div>
          <div class="stat-label">Students</div>
        </div>
        <div class="stat-card">
          <div class="stat-number"><?php echo htmlspecialchars($stats['total_courses']); ?></div>
          <div class="stat-label">Courses</div>
        </div>
        <div class="stat-card">
          <div class="stat-number"><?php echo htmlspecialchars($stats['pending_justifications']); ?></div>
          <div class="stat-label">Pending Justifications</div>
        </div>
      </div>

      <div class="grid" aria-hidden="true">
        <article class="card">
          <div>
            <h3>Manage Teachers</h3>
            <p>Add, edit, or remove teacher accounts. Assign courses and manage teacher permissions.</p>
          </div>
          <div class="cta">
            <a href="teachers.php" class="btn primary">Manage Teachers</a>
          </div>
        </article>

        <article class="card">
          <div>
            <h3>Manage Students</h3>
            <p>Manage student accounts, enrollments, and view student attendance records.</p>
          </div>
          <div class="cta">
            <a href="students.php" class="btn primary">Manage Students</a>
          </div>
        </article>

        <article class="card">
          <div>
            <h3>Course Management</h3>
            <p>Create and manage courses, assign teachers, and set up course schedules.</p>
          </div>
          <div class="cta">
            <a href="courses.php" class="btn primary">Manage Courses</a>
          </div>
        </article>

        <article class="card">
          <div>
            <h3>System Reports</h3>
            <p>Generate attendance reports, system usage statistics, and export data.</p>
          </div>
          <div class="cta">
            <a href="#" class="btn primary" onclick="alert('Reports feature coming soon!')">View Reports</a>
          </div>
        </article>
      </div>

      <div style="margin-top:18px">
        <div style="font-weight:700;margin-bottom:8px">Quick Overview</div>
        <div class="quick">
          <div class="tile"><small class="muted">Active Teachers</small><strong><?php echo htmlspecialchars($stats['active_teachers']); ?></strong></div>
          <div class="tile"><small class="muted">Enrolled Students</strong><?php echo htmlspecialchars($stats['total_students']); ?></strong></div>
          <div class="tile"><small class="muted">Active Courses</small><strong><?php echo htmlspecialchars($stats['active_courses']); ?></strong></div>
          <div class="tile"><small class="muted">Today's Sessions</small><strong><?php echo htmlspecialchars($stats['today_sessions']); ?></strong></div>
        </div>
      </div>
    </section>
  </main>
</body>
</html>