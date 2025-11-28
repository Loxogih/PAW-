<?php
require_once '../config.php';

// Check if user is teacher
if ($_SESSION['user_type'] != 'teacher') {
    header('Location: ../login.php');
    exit();
}

$teacher_id = $_SESSION['user_id'];

// Get pending justifications for this teacher
$justifications = $db_functions->getPendingJustifications($teacher_id);

// Handle justification approval/rejection
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['approve_justification']) || isset($_POST['reject_justification'])) {
        $justification_id = $_POST['justification_id'];
        $teacher_notes = $_POST['teacher_notes'] ?? '';
        $status = isset($_POST['approve_justification']) ? 'approved' : 'rejected';
        
        if ($db_functions->updateJustificationStatus($justification_id, $status, $teacher_id, $teacher_notes)) {
            $success = "Justification " . $status . " successfully!";
        } else {
            $error = "Error updating justification!";
        }
        
        // Refresh the list
        $justifications = $db_functions->getPendingJustifications($teacher_id);
    }
}
?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Justifications — Teacher</title>
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
    .grid{display:grid;grid-template-columns:1fr;gap:16px;margin-top:18px}
    .card{background:var(--panel);padding:18px;border-radius:12px;border:1px solid var(--glass);box-shadow:0 10px 30px rgba(15,23,42,0.04)}
    .card h3{margin:0;font-size:16px}
    .muted{color:var(--muted);font-size:13px}
    .just-item{border:1px dashed rgba(15,23,42,0.04);padding:12px;border-radius:10px;margin-bottom:12px;background:linear-gradient(180deg,#fff,#fbfdff)}
    .just-item h6{margin:0;font-weight:700}
    .just-item p{margin:6px 0;color:#374151}
    .controls{display:flex;gap:10px;margin-top:8px}
    .btn{padding:8px 10px;border-radius:8px;border:0;cursor:pointer;font-weight:700}
    .btn.primary{background:linear-gradient(90deg,var(--brand),var(--accent));color:#fff}
    .btn.success{background:#16a34a;color:#fff}
    .btn.danger{background:#ef4444;color:#fff}
    .btn.outline{background:transparent;border:1px solid var(--brand);color:var(--brand)}
    .alert{background:#d1fae5;color:#065f46;padding:12px;border-radius:8px;margin-bottom:16px;border:1px solid #a7f3d0}
    .alert.error{background:#fee2e2;color:#991b1b;border-color:#fecaca}
    textarea{width:100%;padding:10px;border:1px solid rgba(15,23,42,0.1);border-radius:8px;font-size:14px;margin-top:8px}
    table{width:100%;border-collapse:collapse;margin-top:8px;font-size:14px}
    th,td{padding:10px;border-bottom:1px dashed #eef3f9;text-align:left}
    .stats-row{display:flex;gap:12px;margin-top:12px}
    .stat{flex:1;padding:12px;border-radius:10px;background:linear-gradient(180deg,#fff,#fbfdff);border:1px solid rgba(14,39,77,0.03);text-align:center}
    .stat strong{display:block;font-size:18px}
    .empty-state{text-align:center;padding:40px;color:var(--muted)}
    @media (max-width:960px){.grid{grid-template-columns:1fr}.sidebar{display:none}}
  </style>
</head>
<body>
  <header class="topbar" role="banner">
    <div style="display:flex;align-items:center;">
      <div class="mark">UA</div>
      <div class="brand-text"><div style="font-weight:800">Université Alger 1</div><div style="font-size:12px;color:var(--muted)">Teacher panel</div></div>
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
          <div class="role">Teacher</div>
        </div>
      </div>

      <nav class="nav" aria-label="Primary">
        <a href="dashboard.php">🏠 Dashboard</a>
        <a href="courses.php">📚 My Courses</a>
        <a href="attendance.php">🗓️ Attendance</a>
        <a href="justifications.php" class="active">📄 Justifications</a>
        <a href="../logout.php" class="logout">↩️ Logout</a>
      </nav>
    </aside>

    <section class="content" aria-labelledby="just-title">
      <div class="page-head">
        <div>
          <h1 id="just-title" class="title">Justification Management</h1>
          <div class="subtitle">Review and manage student absence justifications</div>
        </div>
      </div>

      <?php if (isset($success)): ?>
        <div class="alert"><?php echo htmlspecialchars($success); ?></div>
      <?php endif; ?>

      <?php if (isset($error)): ?>
        <div class="alert error"><?php echo htmlspecialchars($error); ?></div>
      <?php endif; ?>

      <!-- Statistics Row -->
      <div class="stats-row">
        <div class="stat">
          <strong><?php echo count($justifications); ?></strong>
          <div class="muted">Pending Justifications</div>
        </div>
        <div class="stat">
          <strong><?php echo $db_functions->getTeacherStats($teacher_id)['course_count']; ?></strong>
          <div class="muted">Your Courses</div>
        </div>
      </div>

      <div class="grid">
        <div class="card">
          <h3>Pending Justifications</h3>
          <div class="muted" style="margin-top:6px">Review and take action on student justifications</div>

          <div style="margin-top:18px">
            <?php if (empty($justifications)): ?>
              <div class="empty-state">
                <h4>No Pending Justifications</h4>
                <p>All student justifications have been reviewed.</p>
              </div>
            <?php else: ?>
              <?php foreach ($justifications as $justification): ?>
              <div class="just-item">
                <div style="display:flex;justify-content:space-between;align-items:start">
                  <div>
                    <h6><?php echo htmlspecialchars($justification['first_name'] . ' ' . $justification['last_name']); ?> (<?php echo htmlspecialchars($justification['student_id']); ?>)</h6>
                    <div class="muted"><?php echo htmlspecialchars($justification['course_code'] . ' — ' . $justification['course_name']); ?> • <?php echo date('M j, Y', strtotime($justification['session_date'])); ?> • <?php echo htmlspecialchars($justification['room']); ?></div>
                    <p style="margin-top:8px"><?php echo nl2br(htmlspecialchars($justification['justification_text'])); ?></p>
                  </div>
                  <div style="text-align:right">
                    <div class="muted" style="font-size:12px">Submitted: <?php echo date('M j, Y g:i A', strtotime($justification['submitted_at'])); ?></div>
                  </div>
                </div>

                <?php if (!empty($justification['supporting_docs'])): ?>
                  <div style="margin-top:8px">
                    <a href="../uploads/<?php echo htmlspecialchars($justification['supporting_docs']); ?>" target="_blank" class="btn outline">📄 View Supporting Documents</a>
                  </div>
                <?php endif; ?>

                <form method="POST" action="" style="margin-top:10px">
                  <input type="hidden" name="justification_id" value="<?php echo htmlspecialchars($justification['id']); ?>">
                  <textarea name="teacher_notes" placeholder="Add your notes or comments (optional)" rows="2"><?php echo isset($_POST['teacher_notes']) ? htmlspecialchars($_POST['teacher_notes']) : ''; ?></textarea>
                  <div class="controls">
                    <button type="submit" name="approve_justification" class="btn success">✅ Approve</button>
                    <button type="submit" name="reject_justification" class="btn danger">❌ Reject</button>
                  </div>
                </form>
              </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </section>
  </main>

  <script>
    // Add confirmation for reject actions
    document.addEventListener('DOMContentLoaded', function() {
      const rejectButtons = document.querySelectorAll('button[name="reject_justification"]');
      
      rejectButtons.forEach(button => {
        button.addEventListener('click', function(e) {
          if (!confirm('Are you sure you want to reject this justification? This action cannot be undone.')) {
            e.preventDefault();
          }
        });
      });

      // Add confirmation for approve actions
      const approveButtons = document.querySelectorAll('button[name="approve_justification"]');
      
      approveButtons.forEach(button => {
        button.addEventListener('click', function(e) {
          if (!confirm('Are you sure you want to approve this justification?')) {
            e.preventDefault();
          }
        });
      });
    });
  </script>
</body>
</html>