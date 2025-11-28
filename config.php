<?php
// config.php - Database configuration and basic settings

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'university_attendance');
define('DB_USER', 'root');
define('DB_PASS', '');

// Application settings
define('BASE_URL', 'http://localhost/paw');
define('UPLOAD_PATH', __DIR__ . '/uploads/');
define('MAX_FILE_SIZE', 5242880); // 5MB

// Session configuration
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set timezone
date_default_timezone_set('Africa/Algiers');

// Include database functions
require_once 'db.php';

// Initialize database connection
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Initialize DBFunctions with the PDO connection
    $db_functions = new DBFunctions($pdo);
    
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>