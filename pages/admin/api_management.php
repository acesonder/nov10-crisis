<?php
/**
 * API Key Management & Documentation
 * Admin interface for managing API access
 */

require_once '../../includes/config.php';
require_once '../../includes/functions.php';

require_login();
require_role('admin');

$page_title = 'API Management';
$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Breadcrumb
$breadcrumb_items = [
    ['label' => 'Home', 'url' => '/nov10-crisis/'],
    ['label' => 'Admin', 'url' => '/nov10-crisis/pages/admin/dashboard.php'],
    ['label' => 'API Management', 'url' => '']
];

// Generate new API key
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_key'])) {
    $key_name = sanitize_input($_POST['key_name']);
    $for_user_id = intval($_POST['for_user_id']);
    $expires_days = intval($_POST['expires_days'] ?? 0);
    $permissions = json_encode($_POST['permissions'] ?? []);
    
    // Generate secure API key
    $api_key = bin2hex(random_bytes(32));
    $expires_at = $expires_days > 0 ? date('Y-m-d H:i:s', strtotime("+$expires_days days")) : null;
    
    $stmt = $conn->prepare("INSERT INTO api_keys (user_id, api_key, key_name, permissions, expires_at) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issss", $for_user_id, $api_key, $key_name, $permissions, $expires_at);
    
    if ($stmt->execute()) {
        $success = "API key generated successfully! Key: <code>$api_key</code><br><strong>Save this key now - it won't be shown again!</strong>";
    } else {
        $error = "Failed to generate API key";
    }
    $stmt->close();
}

// Toggle key status
if (isset($_GET['toggle_key'])) {
    $key_id = intval($_GET['toggle_key']);
    $stmt = $conn->prepare("UPDATE api_keys SET is_active = NOT is_active WHERE key_id = ?");
    $stmt->bind_param("i", $key_id);
    $stmt->execute();
    $stmt->close();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Delete key
if (isset($_GET['delete_key'])) {
    $key_id = intval($_GET['delete_key']);
    $stmt = $conn->prepare("DELETE FROM api_keys WHERE key_id = ?");
    $stmt->bind_param("i", $key_id);
    $stmt->execute();
    $stmt->close();
    $success = "API key deleted";
}

// Get all API keys
$stmt = $conn->prepare("SELECT ak.*, u.full_name, u.username FROM api_keys ak JOIN users u ON ak.user_id = u.user_id ORDER BY ak.created_at DESC");
$stmt->execute();
$api_keys = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get API statistics
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM api_requests WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)");
$stmt->execute();
$requests_24h = $stmt->get_result()->fetch_assoc()['count'];
$stmt->close();

// Get users for dropdown
$stmt = $conn->prepare("SELECT user_id, full_name, username, role FROM users WHERE status = 'active' ORDER BY full_name");
$stmt->execute();
$users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

include '../../includes/header.php';
?>

<style>
.api-dashboard {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.api-section {
    background: var(--bg-primary);
    border-radius: var(--border-radius);
    padding: 24px;
    margin-bottom: 24px;
    box-shadow: var(--shadow-sm);
}

.api-section h2 {
    margin-top: 0;
    border-bottom: 2px solid var(--primary-color);
    padding-bottom: 12px;
}

.key-card {
    background: var(--bg-secondary);
    border-left: 4px solid var(--primary-color);
    border-radius: var(--border-radius);
    padding: 20px;
    margin-bottom: 16px;
}

.key-card.inactive {
    opacity: 0.6;
    border-left-color: var(--text-secondary);
}

.key-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 12px;
}

.key-name {
    font-size: 18px;
    font-weight: 600;
    color: var(--text-primary);
}

.key-meta {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 12px;
    margin: 12px 0;
    font-size: 14px;
}

.key-actions {
    display: flex;
    gap: 8px;
    margin-top: 12px;
}

.code-block {
    background: var(--bg-tertiary);
    padding: 12px;
    border-radius: 6px;
    font-family: monospace;
    font-size: 13px;
    overflow-x: auto;
    margin: 16px 0;
}

.endpoint-list {
    margin-top: 16px;
}

.endpoint-item {
    background: var(--bg-secondary);
    padding: 12px;
    border-radius: 6px;
    margin-bottom: 8px;
    font-family: monospace;
    font-size: 13px;
}

.endpoint-method {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 4px;
    font-weight: bold;
    margin-right: 8px;
}

.method-get { background: var(--info-color); color: white; }
.method-post { background: var(--success-color); color: white; }
.method-put { background: var(--warning-color); color: white; }
.method-delete { background: var(--danger-color); color: white; }

.documentation-section {
    margin-top: 24px;
}

.doc-example {
    background: #1e1e1e;
    color: #d4d4d4;
    padding: 16px;
    border-radius: 8px;
    overflow-x: auto;
    margin: 12px 0;
}

.doc-example pre {
    margin: 0;
    font-family: 'Courier New', monospace;
    font-size: 13px;
}
</style>

<div class="container">
    <h1>🔐 API Management</h1>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <!-- API Statistics -->
    <div class="api-dashboard">
        <div class="stat-card">
            <h3>Active Keys</h3>
            <div class="stat-value"><?php echo count(array_filter($api_keys, fn($k) => $k['is_active'])); ?></div>
        </div>
        <div class="stat-card">
            <h3>Total Keys</h3>
            <div class="stat-value"><?php echo count($api_keys); ?></div>
        </div>
        <div class="stat-card">
            <h3>Requests (24h)</h3>
            <div class="stat-value"><?php echo $requests_24h; ?></div>
        </div>
        <div class="stat-card">
            <h3>Rate Limit</h3>
            <div class="stat-value">1000/hr</div>
        </div>
    </div>
    
    <!-- Generate New Key -->
    <div class="api-section">
        <h2>➕ Generate New API Key</h2>
        
        <form method="POST">
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label>Key Name *</label>
                    <input type="text" name="key_name" class="form-control" required placeholder="e.g., Mobile App Key">
                </div>
                
                <div class="form-group col-md-6">
                    <label>For User *</label>
                    <select name="for_user_id" class="form-control" required>
                        <?php foreach ($users as $user): ?>
                            <option value="<?php echo $user['user_id']; ?>">
                                <?php echo htmlspecialchars($user['full_name']); ?> (<?php echo $user['role']; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label>Expires In (days, 0 = never)</label>
                    <input type="number" name="expires_days" class="form-control" value="0" min="0">
                </div>
                
                <div class="form-group col-md-6">
                    <label>Permissions</label>
                    <div>
                        <label><input type="checkbox" name="permissions[]" value="read" checked> Read</label>
                        <label><input type="checkbox" name="permissions[]" value="write"> Write</label>
                        <label><input type="checkbox" name="permissions[]" value="delete"> Delete</label>
                    </div>
                </div>
            </div>
            
            <button type="submit" name="generate_key" class="btn btn-primary">🔑 Generate API Key</button>
        </form>
    </div>
    
    <!-- Existing Keys -->
    <div class="api-section">
        <h2>🔑 API Keys</h2>
        
        <?php if (empty($api_keys)): ?>
            <p>No API keys generated yet.</p>
        <?php else: ?>
            <?php foreach ($api_keys as $key): ?>
                <div class="key-card <?php echo $key['is_active'] ? '' : 'inactive'; ?>">
                    <div class="key-header">
                        <div>
                            <div class="key-name">
                                <?php echo $key['is_active'] ? '✅' : '⏸️'; ?>
                                <?php echo htmlspecialchars($key['key_name']); ?>
                            </div>
                            <small>User: <?php echo htmlspecialchars($key['full_name']); ?> (@<?php echo $key['username']; ?>)</small>
                        </div>
                    </div>
                    
                    <div class="key-meta">
                        <div><strong>Created:</strong> <?php echo date('M d, Y', strtotime($key['created_at'])); ?></div>
                        <div><strong>Requests:</strong> <?php echo number_format($key['request_count']); ?></div>
                        <div><strong>Last Used:</strong> <?php echo $key['last_used_at'] ? date('M d, g:i A', strtotime($key['last_used_at'])) : 'Never'; ?></div>
                        <div><strong>Expires:</strong> <?php echo $key['expires_at'] ? date('M d, Y', strtotime($key['expires_at'])) : 'Never'; ?></div>
                    </div>
                    
                    <div class="code-block">
                        <strong>API Key:</strong> <?php echo substr($key['api_key'], 0, 16); ?>••••••••••••••••
                    </div>
                    
                    <div class="key-actions">
                        <a href="?toggle_key=<?php echo $key['key_id']; ?>" class="btn btn-sm btn-<?php echo $key['is_active'] ? 'warning' : 'success'; ?>">
                            <?php echo $key['is_active'] ? '⏸️ Deactivate' : '▶️ Activate'; ?>
                        </a>
                        <a href="?delete_key=<?php echo $key['key_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this API key?')">
                            🗑️ Delete
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <!-- API Documentation -->
    <div class="api-section">
        <h2>📖 API Documentation</h2>
        
        <p><strong>Base URL:</strong> <code>https://your-domain.com/nov10-crisis/api/v1/</code></p>
        <p><strong>Authentication:</strong> Include your API key in the <code>X-API-Key</code> header</p>
        
        <h3>Available Endpoints</h3>
        
        <div class="endpoint-list">
            <div class="endpoint-item">
                <span class="endpoint-method method-get">GET</span>
                <span>/users</span> - Get all users (paginated)
            </div>
            
            <div class="endpoint-item">
                <span class="endpoint-method method-get">GET</span>
                <span>/users/{id}</span> - Get specific user
            </div>
            
            <div class="endpoint-item">
                <span class="endpoint-method method-get">GET</span>
                <span>/tasks</span> - Get tasks (filterable by client_id, status)
            </div>
            
            <div class="endpoint-item">
                <span class="endpoint-method method-post">POST</span>
                <span>/tasks</span> - Create new task
            </div>
            
            <div class="endpoint-item">
                <span class="endpoint-method method-get">GET</span>
                <span>/assessments</span> - Get assessments (requires client_id)
            </div>
            
            <div class="endpoint-item">
                <span class="endpoint-method method-get">GET</span>
                <span>/messages</span> - Get user's messages
            </div>
            
            <div class="endpoint-item">
                <span class="endpoint-method method-post">POST</span>
                <span>/messages</span> - Send new message
            </div>
            
            <div class="endpoint-item">
                <span class="endpoint-method method-get">GET</span>
                <span>/stats</span> - Get system statistics
            </div>
        </div>
        
        <h3>Example Request</h3>
        
        <div class="doc-example">
            <pre>
curl -X GET "https://your-domain.com/nov10-crisis/api/v1/tasks?client_id=123" \
  -H "X-API-Key: your_api_key_here" \
  -H "Content-Type: application/json"
            </pre>
        </div>
        
        <h3>Example Response</h3>
        
        <div class="doc-example">
            <pre>
{
  "data": [
    {
      "task_id": 1,
      "client_id": 123,
      "title": "Complete intake assessment",
      "status": "in_progress",
      "priority": "high",
      "due_date": "2025-11-20",
      "created_at": "2025-11-14 10:00:00"
    }
  ]
}
            </pre>
        </div>
        
        <h3>Rate Limiting</h3>
        <p>API requests are limited to <strong>1000 requests per hour</strong> per API key.</p>
        <p>When the limit is exceeded, you'll receive a <code>429 Too Many Requests</code> response.</p>
        
        <h3>Error Responses</h3>
        <ul>
            <li><code>401 Unauthorized</code> - Missing or invalid API key</li>
            <li><code>404 Not Found</code> - Resource not found</li>
            <li><code>429 Too Many Requests</code> - Rate limit exceeded</li>
            <li><code>500 Internal Server Error</code> - Server error</li>
        </ul>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
