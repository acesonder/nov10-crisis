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
$message_id = intval($_GET['id'] ?? 0);
$type = $_GET['type'] ?? 'inbox';

if ($message_id === 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid message ID']);
    exit;
}

// Get message
if ($type === 'inbox') {
    $stmt = $conn->prepare("SELECT m.*, u.full_name as sender_name FROM messages m JOIN users u ON m.sender_id = u.user_id WHERE m.message_id = ? AND m.recipient_id = ?");
} else {
    $stmt = $conn->prepare("SELECT m.*, u.full_name as recipient_name as sender_name FROM messages m JOIN users u ON m.recipient_id = u.user_id WHERE m.message_id = ? AND m.sender_id = ?");
}

$stmt->bind_param("ii", $message_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($message = $result->fetch_assoc()) {
    $stmt->close();
    
    // Mark as read if inbox message
    if ($type === 'inbox' && !$message['is_read']) {
        $update_stmt = $conn->prepare("UPDATE messages SET is_read = 1, read_at = NOW() WHERE message_id = ?");
        $update_stmt->bind_param("i", $message_id);
        $update_stmt->execute();
        $update_stmt->close();
    }
    
    echo json_encode([
        'success' => true,
        'message' => $message
    ]);
} else {
    $stmt->close();
    echo json_encode(['success' => false, 'message' => 'Message not found']);
}
?>
