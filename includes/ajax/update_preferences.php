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
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['key']) || !isset($data['value'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit;
}

$key = $data['key'];
$value = $data['value'];

// Validate key
$allowed_keys = ['theme', 'color_scheme', 'notifications_enabled', 'email_notifications', 'push_notifications'];
if (!in_array($key, $allowed_keys)) {
    echo json_encode(['success' => false, 'message' => 'Invalid preference key']);
    exit;
}

// Update preference
$stmt = $conn->prepare("UPDATE user_preferences SET $key = ? WHERE user_id = ?");
$stmt->bind_param("si", $value, $user_id);

if ($stmt->execute()) {
    $stmt->close();
    echo json_encode(['success' => true]);
} else {
    $stmt->close();
    echo json_encode(['success' => false, 'message' => 'Failed to update preference']);
}
?>
