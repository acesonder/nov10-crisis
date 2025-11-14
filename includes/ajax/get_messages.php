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

// Get unread message count
$unread_count = get_unread_message_count($conn, $user_id);

echo json_encode([
    'success' => true,
    'unread_count' => $unread_count
]);
?>
