<?php
/**
 * Common Functions
 * Crisis Management System
 */

// Start session if not already started
function init_session() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

// Check if user is logged in
function is_logged_in() {
    init_session();
    return isset($_SESSION['user_id']) && isset($_SESSION['role']);
}

// Check user role
function has_role($required_role) {
    init_session();
    if (!is_logged_in()) {
        return false;
    }
    
    $role_hierarchy = [
        'client' => 1,
        'service_provider' => 2,
        'staff' => 3,
        'admin' => 4
    ];
    
    $user_role = $_SESSION['role'];
    return isset($role_hierarchy[$user_role]) && 
           $role_hierarchy[$user_role] >= $role_hierarchy[$required_role];
}

// Redirect to login
function require_login() {
    if (!is_logged_in()) {
        header('Location: /nov10-crisis/pages/login.php');
        exit;
    }
}

// Redirect based on role
function require_role($required_role) {
    require_login();
    if (!has_role($required_role)) {
        header('Location: /nov10-crisis/pages/unauthorized.php');
        exit;
    }
}

// Sanitize input
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// Validate email
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Hash password
function hash_password($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

// Verify password
function verify_password($password, $hash) {
    return password_verify($password, $hash);
}

// Generate unique identifier
function generate_unique_id($prefix = '') {
    return $prefix . uniqid() . bin2hex(random_bytes(8));
}

// Format date
function format_date($date, $format = 'M d, Y') {
    if (empty($date) || $date === '0000-00-00 00:00:00') {
        return 'N/A';
    }
    return date($format, strtotime($date));
}

// Format date time
function format_datetime($datetime, $format = 'M d, Y g:i A') {
    if (empty($datetime) || $datetime === '0000-00-00 00:00:00') {
        return 'N/A';
    }
    return date($format, strtotime($datetime));
}

// Time ago function
function time_ago($datetime) {
    $timestamp = strtotime($datetime);
    $difference = time() - $timestamp;
    
    if ($difference < 60) {
        return 'just now';
    } elseif ($difference < 3600) {
        $minutes = floor($difference / 60);
        return $minutes . ' minute' . ($minutes > 1 ? 's' : '') . ' ago';
    } elseif ($difference < 86400) {
        $hours = floor($difference / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($difference < 604800) {
        $days = floor($difference / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } else {
        return date('M d, Y', $timestamp);
    }
}

// Log activity
function log_activity($conn, $user_id, $action, $entity_type = null, $entity_id = null, $details = null) {
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    $stmt = $conn->prepare("INSERT INTO activity_log (user_id, action, entity_type, entity_id, details, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issssss", $user_id, $action, $entity_type, $entity_id, $details, $ip_address, $user_agent);
    $stmt->execute();
    $stmt->close();
}

// Create notification
function create_notification($conn, $user_id, $type, $title, $message, $link = null, $priority = 'normal') {
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, notification_type, title, message, link, priority) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssss", $user_id, $type, $title, $message, $link, $priority);
    $stmt->execute();
    $stmt->close();
}

// Get unread notification count
function get_unread_notification_count($conn, $user_id) {
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    return $row['count'];
}

// Get unread message count
function get_unread_message_count($conn, $user_id) {
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM messages WHERE recipient_id = ? AND is_read = 0");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    return $row['count'];
}

// Upload file
function upload_file($file, $allowed_types = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'], $upload_dir = null) {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'File upload error'];
    }
    
    if ($upload_dir === null) {
        $upload_dir = UPLOAD_DIR . 'documents/';
    }
    
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($file_extension, $allowed_types)) {
        return ['success' => false, 'message' => 'Invalid file type'];
    }
    
    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'message' => 'File too large'];
    }
    
    $new_filename = generate_unique_id('file_') . '.' . $file_extension;
    $destination = $upload_dir . $new_filename;
    
    if (move_uploaded_file($file['tmp_name'], $destination)) {
        return [
            'success' => true,
            'filename' => $new_filename,
            'path' => $destination,
            'size' => $file['size'],
            'type' => $file_extension
        ];
    }
    
    return ['success' => false, 'message' => 'Failed to move uploaded file'];
}

// Get user preferences
function get_user_preferences($conn, $user_id) {
    $stmt = $conn->prepare("SELECT * FROM user_preferences WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $preferences = $result->fetch_assoc();
    $stmt->close();
    
    if (!$preferences) {
        // Create default preferences
        $stmt = $conn->prepare("INSERT INTO user_preferences (user_id) VALUES (?)");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();
        
        return [
            'theme' => 'light',
            'color_scheme' => 'blue',
            'notifications_enabled' => true,
            'email_notifications' => true,
            'push_notifications' => true
        ];
    }
    
    return $preferences;
}

// Escape JSON for JavaScript
function json_encode_safe($data) {
    return htmlspecialchars(json_encode($data), ENT_QUOTES, 'UTF-8');
}

// Generate CSRF token
function generate_csrf_token() {
    init_session();
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Verify CSRF token
function verify_csrf_token($token) {
    init_session();
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Get role badge HTML
function get_role_badge($role) {
    $badges = [
        'admin' => '<span class="badge badge-danger">Admin</span>',
        'staff' => '<span class="badge badge-primary">Staff</span>',
        'service_provider' => '<span class="badge badge-info">Service Provider</span>',
        'client' => '<span class="badge badge-secondary">Client</span>'
    ];
    return $badges[$role] ?? '<span class="badge badge-secondary">Unknown</span>';
}

// Get status badge HTML
function get_status_badge($status, $type = 'general') {
    $status_lower = strtolower($status);
    
    $colors = [
        'active' => 'success',
        'completed' => 'success',
        'open' => 'primary',
        'in_progress' => 'info',
        'pending' => 'warning',
        'closed' => 'secondary',
        'cancelled' => 'danger',
        'inactive' => 'secondary',
        'suspended' => 'danger'
    ];
    
    $color = $colors[$status_lower] ?? 'secondary';
    $label = ucwords(str_replace('_', ' ', $status));
    
    return "<span class='badge badge-{$color}'>{$label}</span>";
}

// Get priority badge HTML
function get_priority_badge($priority) {
    $colors = [
        'low' => 'success',
        'medium' => 'warning',
        'high' => 'danger',
        'critical' => 'danger',
        'urgent' => 'danger'
    ];
    
    $color = $colors[$priority] ?? 'secondary';
    $label = ucfirst($priority);
    
    return "<span class='badge badge-{$color}'>{$label}</span>";
}
?>
