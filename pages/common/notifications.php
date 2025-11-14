<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

require_login();

$page_title = 'Notifications';
$user_id = $_SESSION['user_id'];

// Mark notification as read
if (isset($_GET['read']) && isset($_GET['id'])) {
    $notif_id = intval($_GET['id']);
    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1, read_at = NOW() WHERE notification_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $notif_id, $user_id);
    $stmt->execute();
    $stmt->close();
    
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Mark all as read
if (isset($_GET['read_all'])) {
    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1, read_at = NOW() WHERE user_id = ? AND is_read = 0");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();
    
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Get all notifications
$stmt = $conn->prepare("
    SELECT * FROM notifications 
    WHERE user_id = ? 
    ORDER BY 
        CASE priority
            WHEN 'high' THEN 1
            WHEN 'normal' THEN 2
            WHEN 'low' THEN 3
        END,
        created_at DESC
    LIMIT 50
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$notifications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$unread_count = count(array_filter($notifications, fn($n) => !$n['is_read']));

include '../../includes/header.php';
?>

<style>
    .notification-item {
        padding: 1rem;
        border-bottom: 1px solid var(--border-color);
        transition: background-color 0.2s;
        cursor: pointer;
    }
    
    .notification-item:hover {
        background-color: var(--bg-secondary);
    }
    
    .notification-item.unread {
        background-color: rgba(74, 144, 226, 0.05);
        border-left: 3px solid var(--primary-color);
    }
    
    .notification-icon {
        font-size: 2rem;
        margin-right: 1rem;
    }
    
    .notification-priority-high {
        border-left-color: var(--danger-color) !important;
    }
    
    .notification-content {
        flex: 1;
    }
</style>

<div class="container" style="padding: 2rem 0;">
    <div class="mb-4 d-flex justify-between align-center">
        <div>
            <h1>Notifications 🔔</h1>
            <p style="color: var(--text-secondary);">
                <?php echo $unread_count; ?> unread notification<?php echo $unread_count !== 1 ? 's' : ''; ?>
            </p>
        </div>
        <?php if ($unread_count > 0): ?>
            <a href="?read_all=1" class="btn btn-primary">Mark All as Read</a>
        <?php endif; ?>
    </div>
    
    <?php if (empty($notifications)): ?>
        <div class="card">
            <div class="card-body text-center" style="padding: 3rem;">
                <h3>No notifications</h3>
                <p style="color: var(--text-secondary);">You're all caught up! 🎉</p>
            </div>
        </div>
    <?php else: ?>
        <div class="card">
            <div class="card-body" style="padding: 0;">
                <?php foreach ($notifications as $notification): ?>
                    <div class="notification-item <?php echo $notification['is_read'] ? '' : 'unread'; ?> <?php echo $notification['priority'] === 'high' ? 'notification-priority-high' : ''; ?>"
                         onclick="window.location='<?php echo $notification['link'] ?? '#'; ?>?read=1&id=<?php echo $notification['notification_id']; ?>'">
                        <div class="d-flex align-center">
                            <div class="notification-icon">
                                <?php
                                $icons = [
                                    'message' => '💬',
                                    'task' => '✅',
                                    'assessment' => '📋',
                                    'referral' => '🤝',
                                    'case' => '📁',
                                    'incident' => '⚠️',
                                    'resource' => '🛏️',
                                    'system' => '⚙️',
                                    'news' => '📰'
                                ];
                                echo $icons[$notification['notification_type']] ?? '🔔';
                                ?>
                            </div>
                            
                            <div class="notification-content">
                                <h4 style="margin-bottom: 0.5rem;">
                                    <?php echo htmlspecialchars($notification['title']); ?>
                                    <?php if ($notification['priority'] === 'high'): ?>
                                        <span class="badge badge-danger">High Priority</span>
                                    <?php endif; ?>
                                    <?php if (!$notification['is_read']): ?>
                                        <span class="badge badge-primary">New</span>
                                    <?php endif; ?>
                                </h4>
                                <p style="margin-bottom: 0.5rem; color: var(--text-primary);">
                                    <?php echo htmlspecialchars($notification['message']); ?>
                                </p>
                                <small style="color: var(--text-secondary);">
                                    <?php echo time_ago($notification['created_at']); ?>
                                </small>
                            </div>
                            
                            <?php if (!$notification['is_read']): ?>
                                <a href="?read=1&id=<?php echo $notification['notification_id']; ?>" 
                                   class="btn btn-sm btn-secondary" 
                                   onclick="event.stopPropagation();">
                                    Mark Read
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include '../../includes/footer.php'; ?>
