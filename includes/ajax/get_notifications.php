<?php
require_once '../config.php';
require_once '../functions.php';

init_session();

header('Content-Type: application/json');

if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Get unread notification count
$unread_count = get_unread_notification_count($conn, $user_id);

// Get new notifications (last 5 unread)
$stmt = $conn->prepare("SELECT * FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC LIMIT 5");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$new_notifications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

echo json_encode([
    'success' => true,
    'unread_count' => $unread_count,
    'new_notifications' => $new_notifications
]);
?>
