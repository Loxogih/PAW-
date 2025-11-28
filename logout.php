<?php
require_once 'config.php';

// Destroy all session data
session_destroy();

// Clear session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Redirect to login page with success message
header('Location: login.php?logout=success');
exit();
?>