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

$type = $data['type'] ?? '';
$slot_id = intval($data['slot_id'] ?? 0);

if (empty($type) || $slot_id === 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit;
}

if ($type === 'shower') {
    // Check if slot is still available
    $stmt = $conn->prepare("SELECT * FROM shower_slots WHERE slot_id = ? AND status = 'available'");
    $stmt->bind_param("i", $slot_id);
    $stmt->execute();
    $slot = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$slot) {
        echo json_encode(['success' => false, 'message' => 'Slot no longer available']);
        exit;
    }
    
    // Book the slot
    $stmt = $conn->prepare("UPDATE shower_slots SET status = 'booked', booked_by = ? WHERE slot_id = ?");
    $stmt->bind_param("ii", $user_id, $slot_id);
    
    if ($stmt->execute()) {
        $stmt->close();
        log_activity($conn, $user_id, 'booked_shower', 'shower_slot', $slot_id);
        echo json_encode(['success' => true, 'message' => 'Shower slot booked successfully']);
    } else {
        $stmt->close();
        echo json_encode(['success' => false, 'message' => 'Failed to book slot']);
    }
    
} elseif ($type === 'laundry') {
    // Check if slot is still available
    $stmt = $conn->prepare("SELECT * FROM laundry_slots WHERE slot_id = ? AND status = 'available'");
    $stmt->bind_param("i", $slot_id);
    $stmt->execute();
    $slot = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$slot) {
        echo json_encode(['success' => false, 'message' => 'Slot no longer available']);
        exit;
    }
    
    // Book the slot
    $stmt = $conn->prepare("UPDATE laundry_slots SET status = 'booked', booked_by = ? WHERE slot_id = ?");
    $stmt->bind_param("ii", $user_id, $slot_id);
    
    if ($stmt->execute()) {
        $stmt->close();
        log_activity($conn, $user_id, 'booked_laundry', 'laundry_slot', $slot_id);
        echo json_encode(['success' => true, 'message' => 'Laundry slot booked successfully']);
    } else {
        $stmt->close();
        echo json_encode(['success' => false, 'message' => 'Failed to book slot']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid resource type']);
}
?>
