<?php
/**
 * Enhanced Messaging System
 * Includes: Templates, Priority levels, Attachments, Search, Archiving, Categories, Group messaging
 */

require_once '../../includes/config.php';
require_once '../../includes/functions.php';

require_login();

$page_title = 'Enhanced Messages';
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$error = '';
$success = '';

// Breadcrumb
$breadcrumb_items = [
    ['label' => 'Home', 'url' => '/nov10-crisis/'],
    ['label' => 'Messages', 'url' => '']
];

// Handle new message with enhanced features
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $recipients = isset($_POST['recipients']) ? $_POST['recipients'] : [intval($_POST['recipient_id'] ?? 0)];
    $subject = sanitize_input($_POST['subject'] ?? '');
    $message_body = sanitize_input($_POST['message_body'] ?? '');
    $priority = sanitize_input($_POST['priority'] ?? 'normal');
    $category = sanitize_input($_POST['category'] ?? 'general');
    $is_broadcast = isset($_POST['is_broadcast']) ? 1 : 0;
    
    // Handle attachments
    $attachments = [];
    if (isset($_FILES['attachments'])) {
        $upload_dir = '../../uploads/messages/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        foreach ($_FILES['attachments']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['attachments']['error'][$key] === 0) {
                $filename = time() . '_' . basename($_FILES['attachments']['name'][$key]);
                $target = $upload_dir . $filename;
                if (move_uploaded_file($tmp_name, $target)) {
                    $attachments[] = $filename;
                }
            }
        }
    }
    
    $attachments_json = json_encode($attachments);
    
    foreach ($recipients as $recipient_id) {
        $recipient_id = intval($recipient_id);
        if ($recipient_id > 0 && !empty($subject) && !empty($message_body)) {
            $stmt = $conn->prepare("INSERT INTO messages (sender_id, recipient_id, subject, message_body, priority, category, is_broadcast, attachments) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iissssss", $user_id, $recipient_id, $subject, $message_body, $priority, $category, $is_broadcast, $attachments_json);
            
            if ($stmt->execute()) {
                create_notification($conn, $recipient_id, 'message', 
                    ($priority === 'urgent' ? '🚨 URGENT: ' : '') . 'New Message', 
                    $_SESSION['full_name'] . ' sent you a message', 
                    '/nov10-crisis/pages/common/messages_enhanced.php');
            }
            $stmt->close();
        }
    }
    
    $success = count($recipients) > 1 ? 'Broadcast sent to ' . count($recipients) . ' recipients!' : 'Message sent successfully!';
}

// Handle message actions
if (isset($_GET['action'])) {
    $msg_id = intval($_GET['msg_id'] ?? 0);
    
    switch ($_GET['action']) {
        case 'read':
            $stmt = $conn->prepare("UPDATE messages SET is_read = 1, read_at = NOW() WHERE message_id = ? AND recipient_id = ?");
            $stmt->bind_param("ii", $msg_id, $user_id);
            $stmt->execute();
            $stmt->close();
            break;
            
        case 'archive':
            $stmt = $conn->prepare("UPDATE messages SET is_archived = 1 WHERE message_id = ? AND (recipient_id = ? OR sender_id = ?)");
            $stmt->bind_param("iii", $msg_id, $user_id, $user_id);
            $stmt->execute();
            $stmt->close();
            $success = 'Message archived';
            break;
            
        case 'unarchive':
            $stmt = $conn->prepare("UPDATE messages SET is_archived = 0 WHERE message_id = ? AND (recipient_id = ? OR sender_id = ?)");
            $stmt->bind_param("iii", $msg_id, $user_id, $user_id);
            $stmt->execute();
            $stmt->close();
            $success = 'Message restored';
            break;
            
        case 'delete':
            $stmt = $conn->prepare("DELETE FROM messages WHERE message_id = ? AND (recipient_id = ? OR sender_id = ?)");
            $stmt->bind_param("iii", $msg_id, $user_id, $user_id);
            $stmt->execute();
            $stmt->close();
            $success = 'Message deleted';
            break;
    }
}

// Search functionality
$search_query = $_GET['search'] ?? '';
$filter_category = $_GET['category'] ?? 'all';
$filter_priority = $_GET['priority'] ?? 'all';
$view = $_GET['view'] ?? 'inbox';

// Build query based on filters
$where_conditions = [];
$params = [];
$types = '';

if ($view === 'inbox') {
    $where_conditions[] = "m.recipient_id = ?";
    $params[] = $user_id;
    $types .= 'i';
} elseif ($view === 'sent') {
    $where_conditions[] = "m.sender_id = ?";
    $params[] = $user_id;
    $types .= 'i';
} elseif ($view === 'archived') {
    $where_conditions[] = "(m.recipient_id = ? OR m.sender_id = ?)";
    $where_conditions[] = "m.is_archived = 1";
    $params[] = $user_id;
    $params[] = $user_id;
    $types .= 'ii';
} else {
    $where_conditions[] = "(m.recipient_id = ? OR m.sender_id = ?)";
    $params[] = $user_id;
    $params[] = $user_id;
    $types .= 'ii';
}

if ($view !== 'archived') {
    $where_conditions[] = "m.is_archived = 0";
}

if (!empty($search_query)) {
    $where_conditions[] = "(m.subject LIKE ? OR m.message_body LIKE ?)";
    $search_param = '%' . $search_query . '%';
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'ss';
}

if ($filter_category !== 'all') {
    $where_conditions[] = "m.category = ?";
    $params[] = $filter_category;
    $types .= 's';
}

if ($filter_priority !== 'all') {
    $where_conditions[] = "m.priority = ?";
    $params[] = $filter_priority;
    $types .= 's';
}

$where_clause = implode(' AND ', $where_conditions);
$query = "SELECT m.*, 
          u1.full_name as sender_name, u1.role as sender_role,
          u2.full_name as recipient_name, u2.role as recipient_role
          FROM messages m 
          LEFT JOIN users u1 ON m.sender_id = u1.user_id
          LEFT JOIN users u2 ON m.recipient_id = u2.user_id
          WHERE $where_clause
          ORDER BY m.priority = 'urgent' DESC, m.created_at DESC";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$messages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get message templates
$templates = [
    'appointment' => [
        'subject' => 'Appointment Reminder',
        'body' => 'This is a reminder about your upcoming appointment on [DATE] at [TIME].'
    ],
    'follow_up' => [
        'subject' => 'Follow-up Check-in',
        'body' => 'I wanted to follow up with you regarding our previous conversation...'
    ],
    'resource' => [
        'subject' => 'Resource Information',
        'body' => 'I have information about resources that may be helpful for you...'
    ],
    'update' => [
        'subject' => 'Status Update',
        'body' => 'I wanted to provide you with an update on your case...'
    ]
];

// Get users for messaging
$users_query = "SELECT user_id, full_name, role FROM users WHERE user_id != ? AND status = 'active'";
if ($role === 'client') {
    $users_query .= " AND (role = 'staff' OR role = 'admin')";
}
$users_query .= " ORDER BY full_name";

$stmt = $conn->prepare($users_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$available_users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

include '../../includes/header.php';
?>

<style>
.message-container {
    display: grid;
    grid-template-columns: 250px 1fr;
    gap: 20px;
    min-height: 600px;
}

.message-sidebar {
    background: var(--bg-primary);
    border-radius: var(--border-radius);
    padding: 20px;
    box-shadow: var(--shadow-sm);
}

.sidebar-section {
    margin-bottom: 24px;
}

.sidebar-section h3 {
    font-size: 14px;
    color: var(--text-secondary);
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-bottom: 12px;
}

.nav-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 12px;
    border-radius: 6px;
    cursor: pointer;
    transition: var(--transition-fast);
    text-decoration: none;
    color: var(--text-primary);
    margin-bottom: 4px;
}

.nav-item:hover {
    background: var(--bg-secondary);
}

.nav-item.active {
    background: var(--primary-color);
    color: white;
}

.nav-item .count {
    margin-left: auto;
    background: var(--bg-tertiary);
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 12px;
}

.nav-item.active .count {
    background: rgba(255, 255, 255, 0.2);
}

.message-main {
    background: var(--bg-primary);
    border-radius: var(--border-radius);
    padding: 20px;
    box-shadow: var(--shadow-sm);
}

.message-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 16px;
    border-bottom: 2px solid var(--border-color);
}

.search-filters {
    display: flex;
    gap: 12px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}

.search-filters input,
.search-filters select {
    padding: 8px 12px;
    border: 1px solid var(--border-color);
    border-radius: 6px;
    background: var(--bg-secondary);
}

.message-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.message-item {
    background: var(--bg-secondary);
    border-left: 4px solid var(--primary-color);
    border-radius: var(--border-radius);
    padding: 16px;
    cursor: pointer;
    transition: var(--transition-fast);
    position: relative;
}

.message-item:hover {
    transform: translateX(4px);
    box-shadow: var(--shadow-md);
}

.message-item.unread {
    background: rgba(74, 144, 226, 0.1);
    font-weight: 600;
}

.message-item.priority-urgent {
    border-left-color: var(--danger-color);
    background: rgba(220, 53, 69, 0.1);
}

.message-item.priority-high {
    border-left-color: var(--warning-color);
}

.message-item-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 8px;
}

.message-sender {
    font-weight: 600;
    color: var(--text-primary);
}

.message-time {
    font-size: 12px;
    color: var(--text-secondary);
}

.message-subject {
    font-size: 15px;
    color: var(--text-primary);
    margin-bottom: 6px;
}

.message-preview {
    font-size: 13px;
    color: var(--text-secondary);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.message-badges {
    display: flex;
    gap: 8px;
    margin-top: 8px;
}

.message-actions {
    display: flex;
    gap: 8px;
    margin-top: 8px;
}

.compose-form {
    background: var(--bg-secondary);
    border-radius: var(--border-radius);
    padding: 20px;
    margin-bottom: 20px;
}

.form-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 12px;
    margin-bottom: 16px;
}

.template-selector {
    margin-bottom: 16px;
}

.template-buttons {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.template-btn {
    padding: 6px 12px;
    background: var(--bg-primary);
    border: 1px solid var(--border-color);
    border-radius: 6px;
    cursor: pointer;
    font-size: 13px;
    transition: var(--transition-fast);
}

.template-btn:hover {
    background: var(--primary-color);
    color: white;
    border-color: var(--primary-color);
}

.attachment-preview {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    margin-top: 8px;
}

.attachment-item {
    display: flex;
    align-items: center;
    gap: 6px;
    background: var(--bg-tertiary);
    padding: 6px 12px;
    border-radius: 6px;
    font-size: 13px;
}

.category-badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
}

.category-general { background: var(--info-color); color: white; }
.category-appointment { background: var(--primary-color); color: white; }
.category-resource { background: var(--success-color); color: white; }
.category-update { background: var(--warning-color); color: white; }
.category-urgent { background: var(--danger-color); color: white; }

@media (max-width: 768px) {
    .message-container {
        grid-template-columns: 1fr;
    }
    
    .message-sidebar {
        display: none;
    }
    
    .form-row {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="container-fluid">
    <h1>💬 Enhanced Messages</h1>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <div class="message-container">
        <!-- Sidebar -->
        <div class="message-sidebar">
            <div class="sidebar-section">
                <h3>Views</h3>
                <a href="?view=inbox" class="nav-item <?php echo $view === 'inbox' ? 'active' : ''; ?>">
                    <span>📥 Inbox</span>
                    <span class="count"><?php echo count(array_filter($messages, fn($m) => $m['recipient_id'] == $user_id && !$m['is_read'])); ?></span>
                </a>
                <a href="?view=sent" class="nav-item <?php echo $view === 'sent' ? 'active' : ''; ?>">
                    <span>📤 Sent</span>
                </a>
                <a href="?view=archived" class="nav-item <?php echo $view === 'archived' ? 'active' : ''; ?>">
                    <span>📦 Archived</span>
                </a>
                <a href="?view=all" class="nav-item <?php echo $view === 'all' ? 'active' : ''; ?>">
                    <span>📬 All Messages</span>
                </a>
            </div>
            
            <div class="sidebar-section">
                <h3>Categories</h3>
                <a href="?view=<?php echo $view; ?>&category=general" class="nav-item">
                    <span>💬 General</span>
                </a>
                <a href="?view=<?php echo $view; ?>&category=appointment" class="nav-item">
                    <span>📅 Appointments</span>
                </a>
                <a href="?view=<?php echo $view; ?>&category=resource" class="nav-item">
                    <span>🛏️ Resources</span>
                </a>
                <a href="?view=<?php echo $view; ?>&category=update" class="nav-item">
                    <span>📋 Updates</span>
                </a>
            </div>
            
            <button class="btn btn-primary w-100" onclick="toggleCompose()">✉️ Compose New</button>
        </div>
        
        <!-- Main Content -->
        <div class="message-main">
            <div class="message-header">
                <h2>
                    <?php 
                    echo match($view) {
                        'inbox' => '📥 Inbox',
                        'sent' => '📤 Sent Messages',
                        'archived' => '📦 Archived',
                        default => '📬 All Messages'
                    };
                    ?>
                </h2>
            </div>
            
            <!-- Compose Form (Hidden by default) -->
            <div id="composeForm" class="compose-form" style="display: none;">
                <h3>✉️ Compose Message</h3>
                
                <div class="template-selector">
                    <label><strong>Quick Templates:</strong></label>
                    <div class="template-buttons">
                        <?php foreach ($templates as $key => $template): ?>
                            <button type="button" class="template-btn" onclick="useTemplate('<?php echo $key; ?>')">
                                <?php echo ucfirst($key); ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-row">
                        <div>
                            <label>Recipients <?php if ($role !== 'client'): ?><input type="checkbox" name="is_broadcast" id="broadcastCheck"> Broadcast<?php endif; ?></label>
                            <select name="recipients[]" id="recipientSelect" class="form-control" <?php echo $role !== 'client' ? 'multiple' : ''; ?> required>
                                <?php foreach ($available_users as $user): ?>
                                    <option value="<?php echo $user['user_id']; ?>">
                                        <?php echo htmlspecialchars($user['full_name']); ?> (<?php echo $user['role']; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div>
                            <label>Priority</label>
                            <select name="priority" class="form-control">
                                <option value="normal">Normal</option>
                                <option value="high">High</option>
                                <option value="urgent">Urgent 🚨</option>
                            </select>
                        </div>
                        
                        <div>
                            <label>Category</label>
                            <select name="category" class="form-control">
                                <option value="general">General</option>
                                <option value="appointment">Appointment</option>
                                <option value="resource">Resource</option>
                                <option value="update">Update</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Subject</label>
                        <input type="text" name="subject" id="subjectInput" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Message</label>
                        <textarea name="message_body" id="bodyInput" class="form-control" rows="6" required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>Attachments (Max 5 files, 10MB total)</label>
                        <input type="file" name="attachments[]" class="form-control" multiple accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.txt">
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" name="send_message" class="btn btn-primary">📤 Send Message</button>
                        <button type="button" class="btn btn-light" onclick="toggleCompose()">Cancel</button>
                    </div>
                </form>
            </div>
            
            <!-- Search & Filters -->
            <div class="search-filters">
                <input type="text" id="searchInput" placeholder="🔍 Search messages..." value="<?php echo htmlspecialchars($search_query); ?>">
                <select id="priorityFilter">
                    <option value="all">All Priorities</option>
                    <option value="urgent" <?php echo $filter_priority === 'urgent' ? 'selected' : ''; ?>>Urgent</option>
                    <option value="high" <?php echo $filter_priority === 'high' ? 'selected' : ''; ?>>High</option>
                    <option value="normal" <?php echo $filter_priority === 'normal' ? 'selected' : ''; ?>>Normal</option>
                </select>
                <button class="btn btn-primary btn-sm" onclick="applyFilters()">Apply</button>
                <button class="btn btn-light btn-sm" onclick="clearFilters()">Clear</button>
            </div>
            
            <!-- Message List -->
            <div class="message-list">
                <?php if (empty($messages)): ?>
                    <div class="text-center p-5">
                        <h3>No messages found</h3>
                        <p>Your messages will appear here.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($messages as $message): 
                        $is_sender = $message['sender_id'] == $user_id;
                        $display_name = $is_sender ? $message['recipient_name'] : $message['sender_name'];
                        $attachments = json_decode($message['attachments'] ?? '[]', true);
                    ?>
                        <div class="message-item <?php echo !$message['is_read'] && !$is_sender ? 'unread' : ''; ?> priority-<?php echo $message['priority']; ?>" 
                             onclick="viewMessage(<?php echo $message['message_id']; ?>)">
                            <div class="message-item-header">
                                <div>
                                    <div class="message-sender">
                                        <?php echo $is_sender ? '→ ' : '← '; ?>
                                        <?php echo htmlspecialchars($display_name); ?>
                                    </div>
                                    <?php if ($message['priority'] === 'urgent'): ?>
                                        <span class="badge badge-danger">🚨 URGENT</span>
                                    <?php elseif ($message['priority'] === 'high'): ?>
                                        <span class="badge badge-warning">⚠️ HIGH</span>
                                    <?php endif; ?>
                                </div>
                                <div class="message-time"><?php echo date('M d, g:i A', strtotime($message['created_at'])); ?></div>
                            </div>
                            
                            <div class="message-subject"><?php echo htmlspecialchars($message['subject']); ?></div>
                            <div class="message-preview"><?php echo htmlspecialchars(substr($message['message_body'], 0, 100)); ?>...</div>
                            
                            <div class="message-badges">
                                <span class="category-badge category-<?php echo $message['category']; ?>">
                                    <?php echo $message['category']; ?>
                                </span>
                                
                                <?php if (!empty($attachments)): ?>
                                    <span class="badge badge-secondary">📎 <?php echo count($attachments); ?> attachment(s)</span>
                                <?php endif; ?>
                                
                                <?php if ($message['is_broadcast']): ?>
                                    <span class="badge badge-info">📢 Broadcast</span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="message-actions" onclick="event.stopPropagation()">
                                <?php if (!$is_sender && !$message['is_archived']): ?>
                                    <a href="?action=archive&msg_id=<?php echo $message['message_id']; ?>&view=<?php echo $view; ?>" class="btn btn-sm btn-light">📦 Archive</a>
                                <?php elseif ($message['is_archived']): ?>
                                    <a href="?action=unarchive&msg_id=<?php echo $message['message_id']; ?>&view=<?php echo $view; ?>" class="btn btn-sm btn-light">📥 Restore</a>
                                <?php endif; ?>
                                <a href="?action=delete&msg_id=<?php echo $message['message_id']; ?>&view=<?php echo $view; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this message?')">🗑️ Delete</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
const templates = <?php echo json_encode($templates); ?>;

function toggleCompose() {
    const form = document.getElementById('composeForm');
    form.style.display = form.style.display === 'none' ? 'block' : 'none';
    if (form.style.display === 'block') {
        form.scrollIntoView({ behavior: 'smooth' });
    }
}

function useTemplate(templateKey) {
    const template = templates[templateKey];
    document.getElementById('subjectInput').value = template.subject;
    document.getElementById('bodyInput').value = template.body;
}

function viewMessage(messageId) {
    window.location.href = '?action=read&msg_id=' + messageId + '&view=<?php echo $view; ?>';
}

function applyFilters() {
    const search = document.getElementById('searchInput').value;
    const priority = document.getElementById('priorityFilter').value;
    const category = new URLSearchParams(window.location.search).get('category') || 'all';
    
    let url = '?view=<?php echo $view; ?>';
    if (search) url += '&search=' + encodeURIComponent(search);
    if (priority !== 'all') url += '&priority=' + priority;
    if (category !== 'all') url += '&category=' + category;
    
    window.location.href = url;
}

function clearFilters() {
    window.location.href = '?view=<?php echo $view; ?>';
}

// Broadcast checkbox handler
document.getElementById('broadcastCheck')?.addEventListener('change', function() {
    const select = document.getElementById('recipientSelect');
    if (this.checked) {
        for (let option of select.options) {
            option.selected = true;
        }
    }
});
</script>

<?php include '../../includes/footer.php'; ?>
