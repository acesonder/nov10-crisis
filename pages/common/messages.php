<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

require_login();

$page_title = 'Messages';
$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Handle new message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $recipient_id = intval($_POST['recipient_id'] ?? 0);
    $subject = sanitize_input($_POST['subject'] ?? '');
    $message_body = sanitize_input($_POST['message_body'] ?? '');
    
    if ($recipient_id > 0 && !empty($subject) && !empty($message_body)) {
        $stmt = $conn->prepare("INSERT INTO messages (sender_id, recipient_id, subject, message_body) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiss", $user_id, $recipient_id, $subject, $message_body);
        
        if ($stmt->execute()) {
            $stmt->close();
            create_notification($conn, $recipient_id, 'message', 'New Message', 
                $_SESSION['full_name'] . ' sent you a message', 
                '/nov10-crisis/pages/common/messages.php');
            $success = 'Message sent successfully!';
        } else {
            $error = 'Failed to send message';
            $stmt->close();
        }
    } else {
        $error = 'Please fill in all fields';
    }
}

// Mark message as read
if (isset($_GET['read']) && isset($_GET['msg_id'])) {
    $msg_id = intval($_GET['msg_id']);
    $stmt = $conn->prepare("UPDATE messages SET is_read = 1, read_at = NOW() WHERE message_id = ? AND recipient_id = ?");
    $stmt->bind_param("ii", $msg_id, $user_id);
    $stmt->execute();
    $stmt->close();
}

// Get inbox messages
$stmt = $conn->prepare("SELECT m.*, u.full_name as sender_name, u.role as sender_role FROM messages m JOIN users u ON m.sender_id = u.user_id WHERE m.recipient_id = ? AND m.is_archived = 0 ORDER BY m.created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$inbox_messages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get sent messages
$stmt = $conn->prepare("SELECT m.*, u.full_name as recipient_name, u.role as recipient_role FROM messages m JOIN users u ON m.recipient_id = u.user_id WHERE m.sender_id = ? ORDER BY m.created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$sent_messages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get users to message (based on role)
$role = $_SESSION['role'];
if ($role === 'client') {
    // Clients can message staff and their assigned providers
    $contacts = $conn->query("SELECT user_id, full_name, role FROM users WHERE role IN ('staff', 'admin') AND status = 'active' ORDER BY full_name")->fetch_all(MYSQLI_ASSOC);
} elseif ($role === 'staff') {
    // Staff can message anyone
    $contacts = $conn->query("SELECT user_id, full_name, role FROM users WHERE user_id != $user_id AND status = 'active' ORDER BY role, full_name")->fetch_all(MYSQLI_ASSOC);
} else {
    // Service providers and admins can message anyone
    $contacts = $conn->query("SELECT user_id, full_name, role FROM users WHERE user_id != $user_id AND status = 'active' ORDER BY full_name")->fetch_all(MYSQLI_ASSOC);
}

include '../../includes/header.php';
?>

<style>
    .message-row {
        cursor: pointer;
        transition: background-color 0.2s;
    }
    
    .message-row:hover {
        background-color: var(--bg-secondary);
    }
    
    .message-row.unread {
        font-weight: bold;
        background-color: rgba(74, 144, 226, 0.05);
    }
    
    .message-preview {
        max-width: 400px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
</style>

<div class="container" style="padding: 2rem 0;">
    <div class="mb-4">
        <h1>Messages 💬</h1>
        <p style="color: var(--text-secondary);">Secure communication with staff and service providers</p>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <!-- Compose Message Button -->
    <div class="mb-4">
        <button class="btn btn-primary" data-modal-target="compose-modal">✉️ New Message</button>
    </div>
    
    <!-- Tabs -->
    <div class="card">
        <div class="card-body" style="padding: 0;">
            <div style="border-bottom: 1px solid var(--border-color); padding: 1rem;">
                <button class="btn btn-sm btn-primary" id="inbox-tab" onclick="showTab('inbox')">
                    Inbox (<?php echo count(array_filter($inbox_messages, fn($m) => !$m['is_read'])); ?>)
                </button>
                <button class="btn btn-sm btn-secondary" id="sent-tab" onclick="showTab('sent')">
                    Sent (<?php echo count($sent_messages); ?>)
                </button>
            </div>
            
            <!-- Inbox Tab -->
            <div id="inbox-content" style="padding: 1rem;">
                <?php if (empty($inbox_messages)): ?>
                    <p style="color: var(--text-secondary);">No messages in your inbox.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>From</th>
                                    <th>Subject</th>
                                    <th>Date</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($inbox_messages as $message): ?>
                                    <tr class="message-row <?php echo $message['is_read'] ? '' : 'unread'; ?>">
                                        <td>
                                            <?php echo htmlspecialchars($message['sender_name']); ?>
                                            <?php echo get_role_badge($message['sender_role']); ?>
                                        </td>
                                        <td class="message-preview"><?php echo htmlspecialchars($message['subject']); ?></td>
                                        <td><?php echo time_ago($message['created_at']); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-primary" onclick="viewMessage(<?php echo $message['message_id']; ?>, 'inbox')">View</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Sent Tab -->
            <div id="sent-content" style="display: none; padding: 1rem;">
                <?php if (empty($sent_messages)): ?>
                    <p style="color: var(--text-secondary);">No sent messages.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>To</th>
                                    <th>Subject</th>
                                    <th>Date</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($sent_messages as $message): ?>
                                    <tr class="message-row">
                                        <td>
                                            <?php echo htmlspecialchars($message['recipient_name']); ?>
                                            <?php echo get_role_badge($message['recipient_role']); ?>
                                        </td>
                                        <td class="message-preview"><?php echo htmlspecialchars($message['subject']); ?></td>
                                        <td><?php echo time_ago($message['created_at']); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-primary" onclick="viewMessage(<?php echo $message['message_id']; ?>, 'sent')">View</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Compose Message Modal -->
<div class="modal" id="compose-modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>New Message</h3>
            <button class="modal-close" data-modal-close>&times;</button>
        </div>
        <form method="POST" action="">
            <div class="form-group">
                <label class="form-label">To:</label>
                <select name="recipient_id" class="form-control" required>
                    <option value="">Select recipient...</option>
                    <?php foreach ($contacts as $contact): ?>
                        <option value="<?php echo $contact['user_id']; ?>">
                            <?php echo htmlspecialchars($contact['full_name']); ?> (<?php echo ucfirst($contact['role']); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Subject:</label>
                <input type="text" name="subject" class="form-control" required>
            </div>
            <div class="form-group">
                <label class="form-label">Message:</label>
                <textarea name="message_body" class="form-control" rows="6" required></textarea>
            </div>
            <button type="submit" name="send_message" class="btn btn-primary">Send Message</button>
        </form>
    </div>
</div>

<!-- View Message Modal -->
<div class="modal" id="view-modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="view-subject">Message</h3>
            <button class="modal-close" data-modal-close>&times;</button>
        </div>
        <div id="view-body">
            <!-- Message content will be loaded here -->
        </div>
    </div>
</div>

<script>
function showTab(tab) {
    if (tab === 'inbox') {
        document.getElementById('inbox-content').style.display = 'block';
        document.getElementById('sent-content').style.display = 'none';
        document.getElementById('inbox-tab').className = 'btn btn-sm btn-primary';
        document.getElementById('sent-tab').className = 'btn btn-sm btn-secondary';
    } else {
        document.getElementById('inbox-content').style.display = 'none';
        document.getElementById('sent-content').style.display = 'block';
        document.getElementById('inbox-tab').className = 'btn btn-sm btn-secondary';
        document.getElementById('sent-tab').className = 'btn btn-sm btn-primary';
    }
}

function viewMessage(messageId, type) {
    fetch('/nov10-crisis/includes/ajax/get_message.php?id=' + messageId + '&type=' + type)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('view-subject').textContent = data.message.subject;
                document.getElementById('view-body').innerHTML = `
                    <p><strong>From:</strong> ${data.message.sender_name}</p>
                    <p><strong>Date:</strong> ${data.message.created_at}</p>
                    <hr>
                    <div style="white-space: pre-wrap;">${data.message.message_body}</div>
                `;
                openModal('view-modal');
                
                // Mark as read if inbox message
                if (type === 'inbox') {
                    setTimeout(() => location.reload(), 2000);
                }
            }
        });
}
</script>

<?php include '../../includes/footer.php'; ?>
