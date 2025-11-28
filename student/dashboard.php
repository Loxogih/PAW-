<?php
require_once '../config.php';

// Check if user is student
if ($_SESSION['user_type'] != 'student') {
    header('Location: ../login.php');
    exit();
}

$student_id = $_SESSION['user_id'];

// Get student statistics
$stats = [];
try {
    // Get total justifications count
    $sql = "SELECT COUNT(*) as total_justifications FROM justifications WHERE student_id = :student_id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':student_id', $student_id);
    $stmt->execute();
    $stats['total_justifications'] = $stmt->fetch()['total_justifications'];

    // Get pending justifications count
    $sql = "SELECT COUNT(*) as pending_justifications FROM justifications WHERE student_id = :student_id AND status = 'pending'";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':student_id', $student_id);
    $stmt->execute();
    $stats['pending_justifications'] = $stmt->fetch()['pending_justifications'];

    // Get approved justifications count
    $sql = "SELECT COUNT(*) as approved_justifications FROM justifications WHERE student_id = :student_id AND status = 'approved'";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':student_id', $student_id);
    $stmt->execute();
    $stats['approved_justifications'] = $stmt->fetch()['approved_justifications'];

    // Get enrolled courses count
    $sql = "SELECT COUNT(DISTINCT c.id) as enrolled_courses 
            FROM courses c 
            JOIN course_groups cg ON c.id = cg.course_id 
            JOIN group_enrollments ge ON cg.id = ge.group_id 
            WHERE ge.student_id = :student_id AND ge.status = 'active'";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':student_id', $student_id);
    $stmt->execute();
    $stats['enrolled_courses'] = $stmt->fetch()['enrolled_courses'];

} catch (PDOException $e) {
    error_log("Error getting student stats: " . $e->getMessage());
    $stats = [
        'total_justifications' => 0,
        'pending_justifications' => 0,
        'approved_justifications' => 0,
        'enrolled_courses' => 0
    ];
}
?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Student Dashboard</title>
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
    .content{flex:1;padding:12px}
    .page-head{display:flex;justify-content:space-between;align-items:flex-start;gap:12px}
    .title{font-size:22px;font-weight:800;color:#1f2937}
    .subtitle{font-size:13px;color:var(--muted);margin-top:6px}
    .stats-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:16px;margin-top:18px}
    .stat-card{background:var(--panel);padding:20px;border-radius:12px;border:1px solid var(--glass);text-align:center}
    .stat-number{font-size:28px;font-weight:800;color:var(--brand)}
    .stat-label{font-size:13px;color:var(--muted);margin-top:8px}
    .quick-actions{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;margin-top:24px}
    .action-card{background:var(--panel);padding:20px;border-radius:12px;border:1px solid var(--glass);text-align:center;cursor:pointer;transition:transform 0.2s,box-shadow 0.2s}
    .action-card:hover{transform:translateY(-2px);box-shadow:0 8px 25px rgba(15,23,42,0.1)}
    .action-icon{font-size:32px;margin-bottom:12px}
    .action-title{font-weight:700;margin-bottom:8px}
    .action-desc{font-size:13px;color:var(--muted)}
    .welcome-banner{background:linear-gradient(135deg,var(--brand),var(--accent));color:white;padding:24px;border-radius:12px;margin-bottom:24px}
    .welcome-banner h2{margin:0;font-size:20px}
    .welcome-banner p{margin:8px 0 0;opacity:0.9;font-size:14px}
    @media (max-width:960px){.stats-grid{grid-template-columns:1fr}.sidebar{display:none}.quick-actions{grid-template-columns:1fr}}
  </style>
</head>
<body>
  <header class="topbar" role="banner">
    <div style="display:flex;align-items:center;">
      <div class="mark">UA</div>
      <div class="brand-text"><div style="font-weight:800">Université Alger 1</div><div style="font-size:12px;color:var(--muted)">Student panel</div></div>
    </div>

    <div class="user-actions" role="navigation" aria-label="User">
      <div class="username"><?php echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']); ?></div>
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
          <div class="role">Student</div>
        </div>
      </div>

      <nav class="nav" aria-label="Primary">
        <a href="dashboard.php" class="active">🏠 Dashboard</a>
        <a href="justifications.php">📄 Submit Justification</a>
        <a href="../logout.php" class="logout">↩️ Logout</a>
      </nav>
    </aside>

    <section class="content" aria-labelledby="dashboard-title">
      <div class="page-head">
        <div>
          <h1 id="dashboard-title" class="title">Student Dashboard</h1>
          <div class="subtitle">Welcome back! Here's your academic overview</div>
        </div>
      </div>

      <!-- Welcome Banner -->
      <div class="welcome-banner">
        <h2>Hello, <?php echo htmlspecialchars($_SESSION['first_name']); ?>! 👋</h2>
        <p>Manage your attendance justifications and track your academic progress</p>
      </div>

      <!-- Statistics Overview -->
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-number"><?php echo $stats['enrolled_courses']; ?></div>
          <div class="stat-label">Enrolled Courses</div>
        </div>
        <div class="stat-card">
          <div class="stat-number"><?php echo $stats['total_justifications']; ?></div>
          <div class="stat-label">Total Justifications</div>
        </div>
        <div class="stat-card">
          <div class="stat-number"><?php echo $stats['pending_justifications']; ?></div>
          <div class="stat-label">Pending Review</div>
        </div>
        <div class="stat-card">
          <div class="stat-number"><?php echo $stats['approved_justifications']; ?></div>
          <div class="stat-label">Approved</div>
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="quick-actions">
        <a href="justifications.php" style="text-decoration:none">
          <div class="action-card">
            <div class="action-icon">📄</div>
            <div class="action-title">Submit Justification</div>
            <div class="action-desc">Submit a new absence justification with documents</div>
          </div>
        </a>

        <a href="justifications.php" style="text-decoration:none">
          <div class="action-card">
            <div class="action-icon">📊</div>
            <div class="action-title">View Justifications</div>
            <div class="action-desc">Check status of your submitted justifications</div>
          </div>
        </a>

        <a href="attendance.php" style="text-decoration:none">
          <div class="action-card">
            <div class="action-icon">🗓️</div>
            <div class="action-title">My Attendance</div>
            <div class="action-desc">View your attendance records and history</div>
          </div>
        </a>

        <div class="action-card" onclick="alert('Profile feature coming soon!')">
          <div class="action-icon">👤</div>
          <div class="action-title">My Profile</div>
          <div class="action-desc">View and update your personal information</div>
        </div>
      </div>

      <!-- Recent Activity Section -->
      <div style="margin-top:32px">
        <h3 style="margin-bottom:16px">Quick Access</h3>
        <div style="display:flex;gap:12px;flex-wrap:wrap">
          <a href="justifications.php" class="action-card" style="text-decoration:none;flex:1;min-width:150px;padding:16px">
            <div style="font-weight:700;margin-bottom:8px">🚀 Submit New</div>
            <div style="font-size:12px;color:var(--muted)">Quick justification submission</div>
          </a>
          
          <a href="justifications.php" class="action-card" style="text-decoration:none;flex:1;min-width:150px;padding:16px">
            <div style="font-weight:700;margin-bottom:8px">📋 View History</div>
            <div style="font-size:12px;color:var(--muted)">All your justifications</div>
          </a>
          
          <div class="action-card" style="flex:1;min-width:150px;padding:16px;cursor:pointer" onclick="alert('Need help? Contact student support at support@university.dz')">
            <div style="font-weight:700;margin-bottom:8px">❓ Help</div>
            <div style="font-size:12px;color:var(--muted)">Get assistance</div>
          </div>
        </div>
      </div>
    </section>
  </main>

  <script>
    // Simple greeting based on time of day
    document.addEventListener('DOMContentLoaded', function() {
      const hour = new Date().getHours();
      let greeting = "Good day";
      
      if (hour < 12) greeting = "Good morning";
      else if (hour < 18) greeting = "Good afternoon";
      else greeting = "Good evening";
      
      const welcomeTitle = document.querySelector('.welcome-banner h2');
      if (welcomeTitle) {
        welcomeTitle.innerHTML = `${greeting}, <?php echo htmlspecialchars($_SESSION['first_name']); ?>! 👋`;
      }
    });
  </script>
</body>
</html>