<?php
require_once '../config.php';

// Check if user is student
if ($_SESSION['user_type'] != 'student') {
    header('Location: ../login.php');
    exit();
}

$student_id = $_SESSION['user_id'];

// Get student's courses and groups
$student_courses = [];
try {
    $sql = "SELECT c.id, c.course_code, c.course_name, cg.id as group_id, cg.group_name
            FROM courses c 
            JOIN course_groups cg ON c.id = cg.course_id 
            JOIN group_enrollments ge ON cg.id = ge.group_id 
            WHERE ge.student_id = :student_id AND ge.status = 'active' 
            ORDER BY c.course_code";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':student_id', $student_id);
    $stmt->execute();
    $student_courses = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error getting student courses: " . $e->getMessage());
}

// Get student's previous justifications
$student_justifications = [];
try {
    $sql = "SELECT j.*, c.course_code, c.course_name, s.session_date, s.room, j.status
            FROM justifications j
            JOIN courses c ON j.course_id = c.id
            JOIN sessions s ON j.session_id = s.id
            WHERE j.student_id = :student_id
            ORDER BY j.submitted_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':student_id', $student_id);
    $stmt->execute();
    $student_justifications = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error getting student justifications: " . $e->getMessage());
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_justification'])) {
    $course_id = $_POST['course_id'];
    $group_id = $_POST['group_id'];
    $session_date = $_POST['session_date'];
    $justification_text = $_POST['justification_text'];
    
    // Get session ID for the selected course, group, and date
    try {
        $sql = "SELECT id FROM sessions WHERE course_id = :course_id AND group_id = :group_id AND session_date = :session_date LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':course_id', $course_id);
        $stmt->bindParam(':group_id', $group_id);
        $stmt->bindParam(':session_date', $session_date);
        $stmt->execute();
        $session = $stmt->fetch();
        
        if ($session) {
            $session_id = $session['id'];
            
            // Handle file upload
            $supporting_docs = null;
            if (isset($_FILES['supporting_docs']) && $_FILES['supporting_docs']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = '../uploads/justifications/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                $file_extension = pathinfo($_FILES['supporting_docs']['name'], PATHINFO_EXTENSION);
                $filename = 'justification_' . $student_id . '_' . time() . '.' . $file_extension;
                $file_path = $upload_dir . $filename;
                
                if (move_uploaded_file($_FILES['supporting_docs']['tmp_name'], $file_path)) {
                    $supporting_docs = 'justifications/' . $filename;
                }
            }
            
            // Insert justification
            $sql = "INSERT INTO justifications (student_id, session_id, course_id, group_id, justification_text, supporting_docs, status) 
                    VALUES (:student_id, :session_id, :course_id, :group_id, :justification_text, :supporting_docs, 'pending')";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':student_id', $student_id);
            $stmt->bindParam(':session_id', $session_id);
            $stmt->bindParam(':course_id', $course_id);
            $stmt->bindParam(':group_id', $group_id);
            $stmt->bindParam(':justification_text', $justification_text);
            $stmt->bindParam(':supporting_docs', $supporting_docs);
            
            if ($stmt->execute()) {
                $success = "Justification submitted successfully!";
                // Refresh the list
                $sql = "SELECT j.*, c.course_code, c.course_name, s.session_date, s.room, j.status
                        FROM justifications j
                        JOIN courses c ON j.course_id = c.id
                        JOIN sessions s ON j.session_id = s.id
                        WHERE j.student_id = :student_id
                        ORDER BY j.submitted_at DESC";
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':student_id', $student_id);
                $stmt->execute();
                $student_justifications = $stmt->fetchAll();
            } else {
                $error = "Error submitting justification!";
            }
        } else {
            $error = "No session found for the selected date!";
        }
    } catch (PDOException $e) {
        error_log("Error submitting justification: " . $e->getMessage());
        $error = "Error submitting justification!";
    }
}
?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Submit Justification — Student</title>
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
    .form-group{margin-bottom:16px}
    label{display:block;margin-bottom:6px;font-weight:600;font-size:13px;color:#374151}
    input,select,textarea{width:100%;padding:10px;border:1px solid #d1d5db;border-radius:8px;font-size:14px}
    input:focus,select:focus,textarea:focus{outline:none;border-color:var(--brand);box-shadow:0 0 0 3px rgba(43,107,230,0.1)}
    .file-upload{display:flex;flex-direction:column;align-items:center;justify-content:center;padding:40px;border:2px dashed #d1d5db;border-radius:8px;cursor:pointer;transition:all 0.3s;background:#fafbfc}
    .file-upload:hover{border-color:var(--brand);background:#f0f4ff}
    .file-upload.dragover{border-color:var(--brand);background:#e0e7ff}
    .file-upload-icon{font-size:48px;margin-bottom:12px;color:#9ca3af}
    .file-input{display:none}
    .file-info{margin-top:12px;font-size:13px;color:var(--muted)}
    .status-badge{padding:4px 8px;border-radius:6px;font-size:11px;font-weight:600}
    .status-pending{background:#fef3c7;color:#92400e}
    .status-approved{background:#d1fae5;color:#065f46}
    .status-rejected{background:#fee2e2;color:#991b1b}
    @media (max-width:960px){.grid{grid-template-columns:1fr}.sidebar{display:none}}
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
        <a href="dashboard.php">🏠 Dashboard</a>
        <a href="justifications.php" class="active">📄 Submit Justification</a>
        <a href="../logout.php" class="logout">↩️ Logout</a>
      </nav>
    </aside>

    <section class="content" aria-labelledby="just-title">
      <div class="page-head">
        <div>
          <h1 id="just-title" class="title">Submit Justification</h1>
          <div class="subtitle">Submit absence justifications with supporting documents</div>
        </div>
      </div>

      <?php if (isset($success)): ?>
        <div class="alert"><?php echo htmlspecialchars($success); ?></div>
      <?php endif; ?>

      <?php if (isset($error)): ?>
        <div class="alert error"><?php echo htmlspecialchars($error); ?></div>
      <?php endif; ?>

      <div class="grid">
        <!-- New Justification Form -->
        <div class="card">
          <h3>New Justification Request</h3>
          <div class="muted" style="margin-top:6px">Submit a new absence justification</div>

          <form method="POST" action="" enctype="multipart/form-data" style="margin-top:18px">
            <div class="form-group">
              <label for="course_id">Course</label>
              <select id="course_id" name="course_id" required>
                <option value="">Select Course</option>
                <?php foreach ($student_courses as $course): ?>
                  <option value="<?php echo htmlspecialchars($course['id']); ?>">
                    <?php echo htmlspecialchars($course['course_code'] . ' - ' . $course['course_name'] . ' (' . $course['group_name'] . ')'); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="form-group">
              <label for="session_date">Absence Date</label>
              <input type="date" id="session_date" name="session_date" required max="<?php echo date('Y-m-d'); ?>">
            </div>

            <div class="form-group">
              <label for="justification_text">Reason for Absence</label>
              <textarea id="justification_text" name="justification_text" rows="4" placeholder="Please provide a detailed reason for your absence..." required></textarea>
            </div>

            <div class="form-group">
              <label>Supporting Documents (Optional)</label>
              <div class="file-upload" id="fileUploadArea">
                <div class="file-upload-icon">📁</div>
                <div>Drag & drop files here or click to browse</div>
                <div class="file-info">Max file size: 5MB • Supported: PDF, JPG, PNG</div>
                <input type="file" id="supporting_docs" name="supporting_docs" class="file-input" accept=".pdf,.jpg,.jpeg,.png">
              </div>
              <div id="fileName" class="file-info" style="display:none;"></div>
            </div>

            <input type="hidden" name="group_id" value="<?php echo !empty($student_courses) ? htmlspecialchars($student_courses[0]['group_id']) : ''; ?>">

            <button type="submit" name="submit_justification" class="btn primary">Submit Justification</button>
          </form>
        </div>

        <!-- Previous Justifications -->
        <div class="card">
          <h3>My Justification History</h3>
          <div class="muted" style="margin-top:6px">Review your previous justification submissions</div>

          <div style="margin-top:18px">
            <?php if (empty($student_justifications)): ?>
              <div style="text-align:center;padding:40px;color:var(--muted)">
                No justifications submitted yet.
              </div>
            <?php else: ?>
              <?php foreach ($student_justifications as $justification): ?>
              <div class="just-item">
                <div style="display:flex;justify-content:space-between;align-items:start">
                  <div>
                    <h6><?php echo htmlspecialchars($justification['course_code'] . ' - ' . $justification['course_name']); ?></h6>
                    <div class="muted"><?php echo date('M j, Y', strtotime($justification['session_date'])); ?> • <?php echo htmlspecialchars($justification['room']); ?></div>
                    <p style="margin-top:8px"><?php echo nl2br(htmlspecialchars($justification['justification_text'])); ?></p>
                  </div>
                  <div style="text-align:right">
                    <span class="status-badge status-<?php echo htmlspecialchars($justification['status']); ?>">
                      <?php echo ucfirst(htmlspecialchars($justification['status'])); ?>
                    </span>
                    <div class="muted" style="font-size:12px;margin-top:4px">
                      Submitted: <?php echo date('M j, Y g:i A', strtotime($justification['submitted_at'])); ?>
                    </div>
                  </div>
                </div>

                <?php if (!empty($justification['supporting_docs'])): ?>
                  <div style="margin-top:8px">
                    <a href="../uploads/<?php echo htmlspecialchars($justification['supporting_docs']); ?>" target="_blank" class="btn outline">📄 View Documents</a>
                  </div>
                <?php endif; ?>

                <?php if (!empty($justification['review_notes'])): ?>
                  <div style="margin-top:8px;padding:8px;background:#f8fafc;border-radius:6px;">
                    <strong>Teacher's Notes:</strong>
                    <p style="margin:4px 0 0 0;color:#374151"><?php echo nl2br(htmlspecialchars($justification['review_notes'])); ?></p>
                  </div>
                <?php endif; ?>
              </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </section>
  </main>

  <script>
    // File upload drag and drop functionality
    const fileUploadArea = document.getElementById('fileUploadArea');
    const fileInput = document.getElementById('supporting_docs');
    const fileName = document.getElementById('fileName');

    fileUploadArea.addEventListener('click', () => {
      fileInput.click();
    });

    fileInput.addEventListener('change', (e) => {
      if (e.target.files.length > 0) {
        fileName.textContent = `Selected file: ${e.target.files[0].name}`;
        fileName.style.display = 'block';
        fileUploadArea.style.borderColor = '#16a34a';
        fileUploadArea.style.background = '#f0fdf4';
      }
    });

    // Drag and drop events
    fileUploadArea.addEventListener('dragover', (e) => {
      e.preventDefault();
      fileUploadArea.classList.add('dragover');
    });

    fileUploadArea.addEventListener('dragleave', () => {
      fileUploadArea.classList.remove('dragover');
    });

    fileUploadArea.addEventListener('drop', (e) => {
      e.preventDefault();
      fileUploadArea.classList.remove('dragover');
      
      if (e.dataTransfer.files.length > 0) {
        fileInput.files = e.dataTransfer.files;
        fileName.textContent = `Selected file: ${e.dataTransfer.files[0].name}`;
        fileName.style.display = 'block';
        fileUploadArea.style.borderColor = '#16a34a';
        fileUploadArea.style.background = '#f0fdf4';
      }
    });

    // Form validation
    document.querySelector('form').addEventListener('submit', (e) => {
      const justificationText = document.getElementById('justification_text').value.trim();
      if (justificationText.length < 10) {
        e.preventDefault();
        alert('Please provide a detailed reason for your absence (at least 10 characters).');
        return false;
      }
    });
  </script>
</body>
</html>