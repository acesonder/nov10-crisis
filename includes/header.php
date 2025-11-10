<?php
if (!isset($_SESSION)) {
    session_start();
}

$current_page = basename($_SERVER['PHP_SELF'], '.php');
$user_id = $_SESSION['user_id'] ?? 0;
$username = $_SESSION['username'] ?? 'Guest';
$full_name = $_SESSION['full_name'] ?? 'Guest';
$role = $_SESSION['role'] ?? 'guest';

// Get notification count
$notification_count = 0;
$message_count = 0;

if ($user_id > 0) {
    $notification_count = get_unread_notification_count($conn, $user_id);
    $message_count = get_unread_message_count($conn, $user_id);
}

// Get user preferences
$preferences = $user_id > 0 ? get_user_preferences($conn, $user_id) : [];
$theme = $preferences['theme'] ?? 'light';
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?php echo $theme; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'Crisis Management System'; ?></title>
    <link rel="stylesheet" href="/nov10-crisis/assets/css/main.css">
    <?php if (isset($extra_css)): ?>
        <?php foreach ($extra_css as $css): ?>
            <link rel="stylesheet" href="<?php echo $css; ?>">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body data-user-id="<?php echo $user_id; ?>">
    <nav class="navbar">
        <div class="container-fluid">
            <a href="/nov10-crisis/pages/<?php echo $role; ?>/dashboard.php" class="navbar-brand">
                🏥 Crisis Management
            </a>
            
            <button class="navbar-toggle" aria-label="Toggle navigation">☰</button>
            
            <ul class="navbar-menu">
                <li><a href="/nov10-crisis/pages/<?php echo $role; ?>/dashboard.php">Dashboard</a></li>
                
                <?php if ($role === 'admin'): ?>
                    <li><a href="/nov10-crisis/pages/admin/users.php">Users</a></li>
                    <li><a href="/nov10-crisis/pages/admin/reports.php">Reports</a></li>
                    <li><a href="/nov10-crisis/pages/admin/settings.php">Settings</a></li>
                <?php elseif ($role === 'staff'): ?>
                    <li><a href="/nov10-crisis/pages/staff/clients.php">Clients</a></li>
                    <li><a href="/nov10-crisis/pages/staff/cases.php">Cases</a></li>
                    <li><a href="/nov10-crisis/pages/staff/incidents.php">Incidents</a></li>
                <?php elseif ($role === 'service_provider'): ?>
                    <li><a href="/nov10-crisis/pages/provider/referrals.php">Referrals</a></li>
                    <li><a href="/nov10-crisis/pages/provider/clients.php">My Clients</a></li>
                <?php elseif ($role === 'client'): ?>
                    <li><a href="/nov10-crisis/pages/client/assessments.php">Assessments</a></li>
                    <li><a href="/nov10-crisis/pages/client/tasks.php">Tasks & Goals</a></li>
                    <li><a href="/nov10-crisis/pages/client/resources.php">Resources</a></li>
                <?php endif; ?>
                
                <li><a href="/nov10-crisis/pages/common/messages.php" class="notification-badge" data-count="<?php echo $message_count; ?>" style="<?php echo $message_count > 0 ? '' : 'display:none;'; ?>">Messages</a></li>
                <li><a href="/nov10-crisis/pages/common/notifications.php" class="notification-badge" data-count="<?php echo $notification_count; ?>" style="<?php echo $notification_count > 0 ? '' : 'display:none;'; ?>">Notifications</a></li>
                <li><a href="/nov10-crisis/pages/common/profile.php"><?php echo htmlspecialchars($full_name); ?></a></li>
                <li><button onclick="toggleTheme()" class="btn btn-sm btn-light">🌙</button></li>
                <li><a href="/nov10-crisis/pages/logout.php" class="btn btn-sm btn-danger">Logout</a></li>
            </ul>
        </div>
    </nav>
    
    <div class="main-content">
