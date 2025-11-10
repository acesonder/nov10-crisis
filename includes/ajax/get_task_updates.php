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
$role = $_SESSION['role'];

// Get task updates based on role
if ($role === 'client') {
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM tasks WHERE client_id = ? AND status != 'completed' AND updated_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
    $stmt->bind_param("i", $user_id);
} else {
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM tasks WHERE assigned_by = ? AND updated_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
    $stmt->bind_param("i", $user_id);
}

$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
$stmt->close();

echo json_encode([
    'success' => true,
    'updates' => $result['count']
]);
?>
