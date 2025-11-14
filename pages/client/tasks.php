<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

require_login();
require_role('client');

$page_title = 'Tasks & Goals';
$user_id = $_SESSION['user_id'];

// Get all tasks
$stmt = $conn->prepare("
    SELECT t.*, 
           u1.full_name as assigned_by_name,
           u2.full_name as assigned_to_name
    FROM tasks t
    LEFT JOIN users u1 ON t.assigned_by = u1.user_id
    LEFT JOIN users u2 ON t.assigned_to = u2.user_id
    WHERE t.client_id = ?
    ORDER BY 
        CASE t.status
            WHEN 'not_started' THEN 1
            WHEN 'in_progress' THEN 2
            WHEN 'completed' THEN 3
            WHEN 'cancelled' THEN 4
        END,
        t.priority DESC,
        t.due_date ASC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$tasks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $task_id = intval($_POST['task_id']);
    $new_status = sanitize_input($_POST['new_status']);
    
    $stmt = $conn->prepare("UPDATE tasks SET status = ?, updated_at = NOW() WHERE task_id = ? AND client_id = ?");
    $stmt->bind_param("sii", $new_status, $task_id, $user_id);
    
    if ($stmt->execute()) {
        $stmt->close();
        if ($new_status === 'completed') {
            $update_stmt = $conn->prepare("UPDATE tasks SET completed_at = NOW() WHERE task_id = ?");
            $update_stmt->bind_param("i", $task_id);
            $update_stmt->execute();
            $update_stmt->close();
        }
        log_activity($conn, $user_id, 'task_status_updated', 'task', $task_id);
        header("Location: " . $_SERVER['PHP_SELF'] . "?success=1");
        exit;
    }
}

include '../../includes/header.php';
?>

<style>
    .task-card {
        border-left: 4px solid;
        transition: transform 0.2s;
    }
    
    .task-card:hover {
        transform: translateX(5px);
    }
    
    .task-card.priority-low { border-left-color: var(--success-color); }
    .task-card.priority-medium { border-left-color: var(--warning-color); }
    .task-card.priority-high { border-left-color: var(--danger-color); }
    
    .task-card.status-completed {
        opacity: 0.7;
        background-color: var(--bg-tertiary);
    }
    
    .progress-summary {
        display: flex;
        gap: 1rem;
        margin-bottom: 2rem;
    }
    
    .progress-item {
        flex: 1;
        text-align: center;
        padding: 1rem;
        background: var(--bg-primary);
        border-radius: var(--border-radius);
        box-shadow: var(--shadow-md);
    }
</style>

<div class="container" style="padding: 2rem 0;">
    <div class="mb-4">
        <h1>My Tasks & Goals ✅</h1>
        <p style="color: var(--text-secondary);">Track your progress and achieve your goals</p>
    </div>
    
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">Task updated successfully!</div>
    <?php endif; ?>
    
    <!-- Progress Summary -->
    <div class="progress-summary">
        <div class="progress-item">
            <h2><?php echo count(array_filter($tasks, fn($t) => $t['status'] === 'not_started')); ?></h2>
            <p style="color: var(--text-secondary);">Not Started</p>
        </div>
        <div class="progress-item">
            <h2><?php echo count(array_filter($tasks, fn($t) => $t['status'] === 'in_progress')); ?></h2>
            <p style="color: var(--text-secondary);">In Progress</p>
        </div>
        <div class="progress-item">
            <h2><?php echo count(array_filter($tasks, fn($t) => $t['status'] === 'completed')); ?></h2>
            <p style="color: var(--text-secondary);">Completed</p>
        </div>
    </div>
    
    <!-- Tasks List -->
    <?php if (empty($tasks)): ?>
        <div class="card">
            <div class="card-body text-center" style="padding: 3rem;">
                <h3>No tasks assigned yet</h3>
                <p style="color: var(--text-secondary);">Your case manager will create tasks to help you achieve your goals.</p>
            </div>
        </div>
    <?php else: ?>
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">All Tasks</h3>
            </div>
            <div class="card-body" style="padding: 0;">
                <?php foreach ($tasks as $task): ?>
                    <div class="task-card card mb-3 priority-<?php echo $task['priority']; ?> status-<?php echo $task['status']; ?>" style="margin: 1rem;">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-12 col-md-8">
                                    <h4><?php echo htmlspecialchars($task['title']); ?></h4>
                                    <?php if ($task['description']): ?>
                                        <p><?php echo nl2br(htmlspecialchars($task['description'])); ?></p>
                                    <?php endif; ?>
                                    
                                    <div style="margin-top: 1rem;">
                                        <span><?php echo get_priority_badge($task['priority']); ?></span>
                                        <span><?php echo get_status_badge($task['status']); ?></span>
                                        <?php if ($task['category']): ?>
                                            <span class="badge badge-info"><?php echo htmlspecialchars($task['category']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div style="margin-top: 0.5rem; font-size: 0.875rem; color: var(--text-secondary);">
                                        <?php if ($task['assigned_by_name']): ?>
                                            <p>Assigned by: <?php echo htmlspecialchars($task['assigned_by_name']); ?></p>
                                        <?php endif; ?>
                                        <?php if ($task['due_date']): ?>
                                            <p>Due: <?php echo format_date($task['due_date']); ?>
                                            <?php if (strtotime($task['due_date']) < time() && $task['status'] !== 'completed'): ?>
                                                <span class="badge badge-danger">Overdue</span>
                                            <?php endif; ?>
                                            </p>
                                        <?php endif; ?>
                                        <?php if ($task['completed_at']): ?>
                                            <p>Completed: <?php echo format_datetime($task['completed_at']); ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="col-12 col-md-4 text-right">
                                    <?php if ($task['status'] !== 'completed' && $task['status'] !== 'cancelled'): ?>
                                        <form method="POST" action="" style="margin-bottom: 0.5rem;">
                                            <input type="hidden" name="task_id" value="<?php echo $task['task_id']; ?>">
                                            <select name="new_status" class="form-control mb-2" required>
                                                <option value="not_started" <?php echo $task['status'] === 'not_started' ? 'selected' : ''; ?>>Not Started</option>
                                                <option value="in_progress" <?php echo $task['status'] === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                                <option value="completed">Mark Complete</option>
                                            </select>
                                            <button type="submit" name="update_status" class="btn btn-sm btn-primary w-100">Update Status</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include '../../includes/footer.php'; ?>
