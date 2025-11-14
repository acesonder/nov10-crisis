<?php
/**
 * Export Items to Various Formats
 * Supports CSV, PDF (basic), JSON
 */

require_once '../config.php';
require_once '../functions.php';

init_session();

if (!is_logged_in()) {
    die('Not authenticated');
}

$ids = isset($_GET['ids']) ? explode(',', $_GET['ids']) : [];
$type = $_GET['type'] ?? 'csv';
$table = $_GET['table'] ?? 'tasks';

if (empty($ids)) {
    die('No items selected');
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Sanitize IDs
$ids = array_map('intval', $ids);
$placeholders = implode(',', array_fill(0, count($ids), '?'));

$data = [];
$filename = '';

try {
    switch ($table) {
        case 'tasks':
            if ($role === 'client') {
                $stmt = $conn->prepare("SELECT * FROM tasks WHERE id IN ($placeholders) AND client_id = ?");
                $types = str_repeat('i', count($ids)) . 'i';
                $params = array_merge($ids, [$user_id]);
            } else {
                $stmt = $conn->prepare("SELECT * FROM tasks WHERE id IN ($placeholders)");
                $types = str_repeat('i', count($ids));
                $params = $ids;
            }
            $filename = 'tasks_export_' . date('Y-m-d');
            break;
            
        case 'messages':
            $stmt = $conn->prepare("SELECT * FROM messages WHERE id IN ($placeholders) AND (sender_id = ? OR recipient_id = ?)");
            $types = str_repeat('i', count($ids)) . 'ii';
            $params = array_merge($ids, [$user_id, $user_id]);
            $filename = 'messages_export_' . date('Y-m-d');
            break;
            
        case 'assessments':
            if ($role === 'client') {
                $stmt = $conn->prepare("SELECT * FROM assessments WHERE id IN ($placeholders) AND client_id = ?");
                $types = str_repeat('i', count($ids)) . 'i';
                $params = array_merge($ids, [$user_id]);
            } else {
                $stmt = $conn->prepare("SELECT * FROM assessments WHERE id IN ($placeholders)");
                $types = str_repeat('i', count($ids));
                $params = $ids;
            }
            $filename = 'assessments_export_' . date('Y-m-d');
            break;
            
        default:
            die('Invalid table');
    }
    
    if (isset($stmt)) {
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }
    
} catch (Exception $e) {
    error_log("Export error: " . $e->getMessage());
    die('Export failed');
}

if (empty($data)) {
    die('No data to export');
}

// Export based on type
switch ($type) {
    case 'json':
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $filename . '.json"');
        echo json_encode($data, JSON_PRETTY_PRINT);
        break;
        
    case 'csv':
    default:
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        // Headers
        if (!empty($data)) {
            fputcsv($output, array_keys($data[0]));
            
            // Data rows
            foreach ($data as $row) {
                // Convert JSON fields to strings
                foreach ($row as $key => $value) {
                    if (is_array($value) || is_object($value)) {
                        $row[$key] = json_encode($value);
                    }
                }
                fputcsv($output, $row);
            }
        }
        
        fclose($output);
        break;
}

exit;
?>
