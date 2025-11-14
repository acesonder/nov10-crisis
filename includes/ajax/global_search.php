<?php
/**
 * Global Search AJAX Handler
 * Search across all content in the system
 */

require_once '../config.php';
require_once '../functions.php';

header('Content-Type: application/json');

init_session();

if (!is_logged_in()) {
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$query = $_GET['q'] ?? '';
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

if (strlen($query) < 2) {
    echo json_encode([]);
    exit;
}

$results = [];
$search_term = '%' . $query . '%';

try {
    // Search in messages
    if ($role === 'admin' || $role === 'staff') {
        $stmt = $conn->prepare("SELECT id, subject, body, created_at FROM messages WHERE subject LIKE ? OR body LIKE ? ORDER BY created_at DESC LIMIT 5");
        $stmt->bind_param("ss", $search_term, $search_term);
    } else {
        $stmt = $conn->prepare("SELECT id, subject, body, created_at FROM messages WHERE (sender_id = ? OR recipient_id = ?) AND (subject LIKE ? OR body LIKE ?) ORDER BY created_at DESC LIMIT 5");
        $stmt->bind_param("iiss", $user_id, $user_id, $search_term, $search_term);
    }
    
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $results[] = [
                'type' => 'Message',
                'title' => $row['subject'],
                'description' => substr($row['body'], 0, 100) . '...',
                'url' => '/nov10-crisis/pages/common/messages.php?id=' . $row['id'],
                'icon' => '✉️'
            ];
        }
    }
    $stmt->close();

    // Search in tasks (for clients and staff)
    if ($role === 'client') {
        $stmt = $conn->prepare("SELECT id, title, description, status FROM tasks WHERE client_id = ? AND (title LIKE ? OR description LIKE ?) ORDER BY due_date DESC LIMIT 5");
        $stmt->bind_param("iss", $user_id, $search_term, $search_term);
    } elseif ($role === 'staff' || $role === 'admin') {
        $stmt = $conn->prepare("SELECT id, title, description, status FROM tasks WHERE title LIKE ? OR description LIKE ? ORDER BY due_date DESC LIMIT 5");
        $stmt->bind_param("ss", $search_term, $search_term);
    }
    
    if (isset($stmt) && $stmt->execute()) {
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $results[] = [
                'type' => 'Task',
                'title' => $row['title'],
                'description' => $row['description'] ? substr($row['description'], 0, 100) . '...' : '',
                'url' => '/nov10-crisis/pages/client/tasks.php?id=' . $row['id'],
                'icon' => '✓'
            ];
        }
        $stmt->close();
    }

    // Search in assessments (for clients and staff)
    if ($role === 'client') {
        $stmt = $conn->prepare("SELECT id, assessment_type, status, created_at FROM assessments WHERE client_id = ? ORDER BY created_at DESC LIMIT 5");
        $stmt->bind_param("i", $user_id);
    } elseif ($role === 'staff' || $role === 'admin') {
        $stmt = $conn->prepare("SELECT id, assessment_type, status, created_at FROM assessments ORDER BY created_at DESC LIMIT 10");
    }
    
    if (isset($stmt) && $stmt->execute()) {
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            if (stripos($row['assessment_type'], $query) !== false) {
                $results[] = [
                    'type' => 'Assessment',
                    'title' => ucfirst($row['assessment_type']) . ' Assessment',
                    'description' => 'Status: ' . $row['status'],
                    'url' => '/nov10-crisis/pages/client/assessments.php?id=' . $row['id'],
                    'icon' => '📋'
                ];
            }
        }
        $stmt->close();
    }

    // Search in users (admin and staff only)
    if ($role === 'admin' || $role === 'staff') {
        $stmt = $conn->prepare("SELECT id, username, full_name, role FROM users WHERE username LIKE ? OR full_name LIKE ? LIMIT 5");
        $stmt->bind_param("ss", $search_term, $search_term);
        
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $results[] = [
                    'type' => 'User',
                    'title' => $row['full_name'] . ' (@' . $row['username'] . ')',
                    'description' => 'Role: ' . ucfirst($row['role']),
                    'url' => '/nov10-crisis/pages/common/profile.php?id=' . $row['id'],
                    'icon' => '👤'
                ];
            }
        }
        $stmt->close();
    }

    // Search in news
    $stmt = $conn->prepare("SELECT id, title, content, category FROM news_feed WHERE title LIKE ? OR content LIKE ? ORDER BY created_at DESC LIMIT 5");
    $stmt->bind_param("ss", $search_term, $search_term);
    
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $results[] = [
                'type' => 'News',
                'title' => $row['title'],
                'description' => substr($row['content'], 0, 100) . '...',
                'url' => '/nov10-crisis/pages/common/news.php?id=' . $row['id'],
                'icon' => '📰'
            ];
        }
    }
    $stmt->close();

    echo json_encode($results);

} catch (Exception $e) {
    error_log("Global search error: " . $e->getMessage());
    echo json_encode(['error' => 'Search failed']);
}
?>
