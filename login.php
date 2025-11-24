<?php
// login.php
require_once 'config.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    // Validate user_type to prevent directory traversal
    $allowed_types = ['admin', 'teacher', 'student'];
    $user_type = in_array($_SESSION['user_type'], $allowed_types) ? $_SESSION['user_type'] : 'student';
    header('Location: ' . $user_type . '/dashboard.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Basic validation
    if (empty($username) || empty($password)) {
        $error = "Please enter both username and password";
    } else {
        $user = $db_functions->authenticateUser($username, $password);
        
        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['first_name'] = $user['first_name'];
            $_SESSION['last_name'] = $user['last_name'];
            $_SESSION['user_type'] = $user['user_type'];
            
            // Validate and sanitize user_type for redirection
            $allowed_types = ['admin', 'teacher', 'student'];
            $user_type = in_array($user['user_type'], $allowed_types) ? $user['user_type'] : 'student';
            
            header('Location: ' . $user_type . '/dashboard.php');
            exit();
        } else {
            $error = "Invalid username or password";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Université Alger 1</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-wrapper {
            display: flex;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.15);
            overflow: hidden;
            max-width: 1000px;
            width: 100%;
            min-height: 600px;
        }

        .university-info {
            flex: 1;
            background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
            color: white;
            padding: 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
        }

        .university-logo {
            width: 120px;
            height: 120px;
            background: rgba(255, 255, 255, 0.15);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 30px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            font-size: 40px;
            font-weight: bold;
        }

        .university-info h1 {
            font-size: 42px;
            font-weight: 700;
            margin-bottom: 15px;
            line-height: 1.2;
        }

        .university-info .subtitle {
            font-size: 20px;
            opacity: 0.9;
            margin-bottom: 10px;
        }

        .university-info .system-name {
            font-size: 16px;
            opacity: 0.8;
            font-weight: 300;
        }

        .login-container {
            flex: 1;
            padding: 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .login-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .login-header h2 {
            font-size: 28px;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 8px;
        }

        .login-header p {
            color: #7f8c8d;
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #2c3e50;
            font-size: 14px;
        }

        .form-input {
            width: 100%;
            padding: 15px 18px;
            border: 2px solid #e8ecef;
            border-radius: 10px;
            font-size: 15px;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }

        .form-input:focus {
            outline: none;
            border-color: #3498db;
            background: white;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }

        .login-btn {
            width: 100%;
            background: #3498db;
            color: white;
            border: none;
            padding: 16px;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
        }

        .login-btn:hover {
            background: #2980b9;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(52, 152, 219, 0.3);
        }

        .demo-section {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 25px;
            margin-top: 30px;
            border: 1px solid #e8ecef;
        }

        .demo-title {
            font-size: 14px;
            font-weight: 600;
            color: #7f8c8d;
            margin-bottom: 18px;
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .demo-account {
            background: white;
            border: 1px solid #e8ecef;
            border-radius: 8px;
            padding: 14px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .demo-account:hover {
            border-color: #3498db;
            background: #ecf0f1;
            transform: translateX(5px);
        }

        .demo-account:last-child {
            margin-bottom: 0;
        }

        .account-type {
            font-weight: 600;
            color: #2c3e50;
            font-size: 13px;
            margin-bottom: 4px;
        }

        .account-info {
            font-size: 11px;
            color: #7f8c8d;
        }

        .account-details {
            font-size: 10px;
            color: #95a5a6;
            margin-top: 2px;
            font-style: italic;
        }

        .error-message {
            background: #ffeaa7;
            border: 1px solid #fdcb6e;
            color: #2d3436;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
            font-size: 14px;
            text-align: center;
        }

        .success-message {
            background: #55efc4;
            border: 1px solid #00b894;
            color: #2d3436;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
            font-size: 14px;
            text-align: center;
        }

        .form-footer {
            text-align: center;
            margin-top: 25px;
            color: #7f8c8d;
            font-size: 13px;
        }

        @media (max-width: 768px) {
            .login-wrapper {
                flex-direction: column;
                min-height: auto;
            }
            
            .university-info {
                padding: 40px 30px;
                text-align: center;
            }
            
            .university-info h1 {
                font-size: 32px;
            }
            
            .login-container {
                padding: 40px 30px;
            }
        }

        @media (max-width: 480px) {
            .university-info {
                padding: 30px 20px;
            }
            
            .university-info h1 {
                font-size: 28px;
            }
            
            .login-container {
                padding: 30px 20px;
            }
            
            .university-logo {
                width: 80px;
                height: 80px;
                font-size: 28px;
            }
        }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <!-- Left Side - University Information -->
        <div class="university-info">
            <div class="university-logo">UA1</div>
            <h1>Université<br>d'Alger 1</h1>
            <div class="subtitle">Benyoucef Benkhedda</div>
            <div class="system-name">Attendance Management System</div>
        </div>
        
        <!-- Right Side - Login Form -->
        <div class="login-container">
            <div class="login-header">
                <h2>Welcome Back</h2>
                <p>Sign in to your account</p>
            </div>
            
            <form method="POST" action="">
                <?php if (isset($_GET['logout']) && $_GET['logout'] == 'success'): ?>
                    <div class="success-message">
                        You have been successfully logged out.
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="error-message">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <div class="form-group">
                    <label class="form-label" for="username">Username</label>
                    <input type="text" id="username" name="username" class="form-input" placeholder="Enter your username" required 
                           value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-input" placeholder="Enter your password" required>
                </div>
                
                <button type="submit" name="login" class="login-btn">Sign In</button>
                
                <div class="demo-section">
                    <div class="demo-title">Demo Accounts</div>
                    
                    <div class="demo-account" onclick="fillCredentials('admin', 'password')">
                        <div class="account-type">⚙️ Admin Account</div>
                        <div class="account-info">Username: admin | Password: password</div>
                        <div class="account-details">System Administrator - Full Access</div>
                    </div>
                    
                    <div class="demo-account" onclick="fillCredentials('samy.char', 'password')">
                        <div class="account-type">👨‍🎓 Student Account</div>
                        <div class="account-info">Username: samy.char | Password: password</div>
                        <div class="account-details">Samy Charallah - Computer Science</div>
                    </div>
                    
                    <div class="demo-account" onclick="fillCredentials('Mohamed.Hemilli', 'password')">
                        <div class="account-type">👨‍🏫 Teacher Account</div>
                        <div class="account-info">Username: Mohamed.Hemilli | Password: password</div>
                        <div class="account-details">Mohamed Hemili - Physics Department</div>
                    </div>
                </div>
                
                <div class="form-footer">
                    <strong>Note:</strong> All demo accounts use "password" as password<br>
                    Need help? Contact system administrator
                </div>
            </form>
        </div>
    </div>

    <script>
        function fillCredentials(username, password) {
            document.getElementById('username').value = username;
            document.getElementById('password').value = password;
        }
        
        // Auto-fill admin credentials for quick testing
        document.addEventListener('DOMContentLoaded', function() {
            fillCredentials('admin', 'password');
        });
    </script>
</body>
</html>