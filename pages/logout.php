<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

init_session();

if (isset($_SESSION['user_id'])) {
    log_activity($conn, $_SESSION['user_id'], 'logout');
}

// Clear all session variables
$_SESSION = array();

// Destroy the session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 42000, '/');
}

// Destroy the session
session_destroy();

// Redirect to home page
header('Location: /nov10-crisis/index.php');
exit;
?>
