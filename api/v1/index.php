<?php
/**
 * Crisis Management System - RESTful API v1
 * Provides programmatic access to system resources
 */

// CORS headers for API access
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-API-Key');
header('Content-Type: application/json');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// API Authentication
function authenticate_api_request() {
    $api_key = $_SERVER['HTTP_X_API_KEY'] ?? '';
    
    if (empty($api_key)) {
        return ['authenticated' => false, 'error' => 'Missing API key'];
    }
    
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM api_keys WHERE api_key = ? AND is_active = 1 AND (expires_at IS NULL OR expires_at > NOW())");
    $stmt->bind_param("s", $api_key);
    $stmt->execute();
    $result = $stmt->get_result();
    $key_data = $result->fetch_assoc();
    $stmt->close();
    
    if (!$key_data) {
        return ['authenticated' => false, 'error' => 'Invalid or expired API key'];
    }
    
    // Update last used timestamp
    $stmt = $conn->prepare("UPDATE api_keys SET last_used_at = NOW(), request_count = request_count + 1 WHERE key_id = ?");
    $stmt->bind_param("i", $key_data['key_id']);
    $stmt->execute();
    $stmt->close();
    
    return [
        'authenticated' => true,
        'user_id' => $key_data['user_id'],
        'permissions' => json_decode($key_data['permissions'] ?? '[]', true)
    ];
}

// Rate limiting check
function check_rate_limit($user_id) {
    global $conn;
    
    // Get rate limit settings (default 1000/hour)
    $limit = 1000;
    
    // Check requests in last hour
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM api_requests WHERE user_id = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $count = $result->fetch_assoc()['count'];
    $stmt->close();
    
    return $count < $limit;
}

// Log API request
function log_api_request($user_id, $endpoint, $method, $status_code) {
    global $conn;
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    $stmt = $conn->prepare("INSERT INTO api_requests (user_id, endpoint, method, status_code, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ississ", $user_id, $endpoint, $method, $status_code, $ip_address, $user_agent);
    $stmt->execute();
    $stmt->close();
}

// Parse the request
$request_uri = $_SERVER['REQUEST_URI'];
$base_path = '/nov10-crisis/api/v1/';
$path = str_replace($base_path, '', $request_uri);
$path = strtok($path, '?'); // Remove query string
$parts = explode('/', trim($path, '/'));
$resource = $parts[0] ?? '';
$id = $parts[1] ?? null;
$method = $_SERVER['REQUEST_METHOD'];

// Authenticate
$auth = authenticate_api_request();
if (!$auth['authenticated']) {
    http_response_code(401);
    echo json_encode(['error' => $auth['error']]);
    exit;
}

$user_id = $auth['user_id'];

// Check rate limit
if (!check_rate_limit($user_id)) {
    http_response_code(429);
    echo json_encode(['error' => 'Rate limit exceeded. Maximum 1000 requests per hour.']);
    log_api_request($user_id, $path, $method, 429);
    exit;
}

// Route the request
$response = [];
$status_code = 200;

try {
    switch ($resource) {
        case 'users':
            if ($method === 'GET') {
                if ($id) {
                    // Get single user
                    $stmt = $conn->prepare("SELECT user_id, username, full_name, email, role, status, created_at FROM users WHERE user_id = ?");
                    $stmt->bind_param("i", $id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $response = $result->fetch_assoc();
                    $stmt->close();
                    
                    if (!$response) {
                        $status_code = 404;
                        $response = ['error' => 'User not found'];
                    }
                } else {
                    // Get all users (paginated)
                    $page = $_GET['page'] ?? 1;
                    $limit = min($_GET['limit'] ?? 20, 100);
                    $offset = ($page - 1) * $limit;
                    
                    $stmt = $conn->prepare("SELECT user_id, username, full_name, email, role, status, created_at FROM users LIMIT ? OFFSET ?");
                    $stmt->bind_param("ii", $limit, $offset);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $response = ['data' => $result->fetch_all(MYSQLI_ASSOC), 'page' => $page, 'limit' => $limit];
                    $stmt->close();
                }
            }
            break;
            
        case 'tasks':
            if ($method === 'GET') {
                if ($id) {
                    // Get single task
                    $stmt = $conn->prepare("SELECT * FROM tasks WHERE task_id = ?");
                    $stmt->bind_param("i", $id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $response = $result->fetch_assoc();
                    $stmt->close();
                } else {
                    // Get tasks with filters
                    $client_id = $_GET['client_id'] ?? null;
                    $status = $_GET['status'] ?? null;
                    
                    $query = "SELECT * FROM tasks WHERE 1=1";
                    $params = [];
                    $types = '';
                    
                    if ($client_id) {
                        $query .= " AND client_id = ?";
                        $params[] = $client_id;
                        $types .= 'i';
                    }
                    
                    if ($status) {
                        $query .= " AND status = ?";
                        $params[] = $status;
                        $types .= 's';
                    }
                    
                    $query .= " ORDER BY created_at DESC LIMIT 50";
                    
                    $stmt = $conn->prepare($query);
                    if (!empty($params)) {
                        $stmt->bind_param($types, ...$params);
                    }
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $response = ['data' => $result->fetch_all(MYSQLI_ASSOC)];
                    $stmt->close();
                }
            } elseif ($method === 'POST') {
                // Create new task
                $data = json_decode(file_get_contents('php://input'), true);
                $required = ['client_id', 'title'];
                
                foreach ($required as $field) {
                    if (empty($data[$field])) {
                        $status_code = 400;
                        $response = ['error' => "Missing required field: $field"];
                        break 2;
                    }
                }
                
                $stmt = $conn->prepare("INSERT INTO tasks (client_id, title, description, assigned_by, status, priority) VALUES (?, ?, ?, ?, 'not_started', ?)");
                $description = $data['description'] ?? '';
                $priority = $data['priority'] ?? 'medium';
                $stmt->bind_param("issis", $data['client_id'], $data['title'], $description, $user_id, $priority);
                
                if ($stmt->execute()) {
                    $status_code = 201;
                    $response = ['success' => true, 'task_id' => $stmt->insert_id];
                } else {
                    $status_code = 500;
                    $response = ['error' => 'Failed to create task'];
                }
                $stmt->close();
            }
            break;
            
        case 'assessments':
            if ($method === 'GET') {
                $client_id = $_GET['client_id'] ?? null;
                
                if ($client_id) {
                    $stmt = $conn->prepare("SELECT * FROM assessments WHERE client_id = ? ORDER BY created_at DESC");
                    $stmt->bind_param("i", $client_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $response = ['data' => $result->fetch_all(MYSQLI_ASSOC)];
                    $stmt->close();
                } else {
                    $status_code = 400;
                    $response = ['error' => 'client_id parameter required'];
                }
            }
            break;
            
        case 'messages':
            if ($method === 'GET') {
                // Get messages for user
                $stmt = $conn->prepare("SELECT * FROM messages WHERE recipient_id = ? OR sender_id = ? ORDER BY created_at DESC LIMIT 50");
                $stmt->bind_param("ii", $user_id, $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $response = ['data' => $result->fetch_all(MYSQLI_ASSOC)];
                $stmt->close();
            } elseif ($method === 'POST') {
                // Send message
                $data = json_decode(file_get_contents('php://input'), true);
                
                $stmt = $conn->prepare("INSERT INTO messages (sender_id, recipient_id, subject, message_body, priority, category) VALUES (?, ?, ?, ?, ?, ?)");
                $priority = $data['priority'] ?? 'normal';
                $category = $data['category'] ?? 'general';
                $stmt->bind_param("iissss", $user_id, $data['recipient_id'], $data['subject'], $data['message_body'], $priority, $category);
                
                if ($stmt->execute()) {
                    $status_code = 201;
                    $response = ['success' => true, 'message_id' => $stmt->insert_id];
                } else {
                    $status_code = 500;
                    $response = ['error' => 'Failed to send message'];
                }
                $stmt->close();
            }
            break;
            
        case 'stats':
            // Get system statistics
            $stats = [];
            
            $result = $conn->query("SELECT COUNT(*) as count FROM users WHERE status = 'active'");
            $stats['active_users'] = $result->fetch_assoc()['count'];
            
            $result = $conn->query("SELECT COUNT(*) as count FROM tasks WHERE status != 'completed'");
            $stats['active_tasks'] = $result->fetch_assoc()['count'];
            
            $result = $conn->query("SELECT COUNT(*) as count FROM assessments WHERE status = 'completed'");
            $stats['completed_assessments'] = $result->fetch_assoc()['count'];
            
            $result = $conn->query("SELECT COUNT(*) as count FROM referrals WHERE status = 'pending'");
            $stats['pending_referrals'] = $result->fetch_assoc()['count'];
            
            $response = $stats;
            break;
            
        default:
            $status_code = 404;
            $response = [
                'error' => 'Resource not found',
                'available_resources' => ['users', 'tasks', 'assessments', 'messages', 'stats']
            ];
            break;
    }
} catch (Exception $e) {
    $status_code = 500;
    $response = ['error' => 'Internal server error', 'message' => $e->getMessage()];
}

// Log the request
log_api_request($user_id, $path, $method, $status_code);

// Send response
http_response_code($status_code);
echo json_encode($response, JSON_PRETTY_PRINT);
?>
