<?php
/**
 * Enhanced Task & Goal Management
 * Includes: SMART goals, sub-tasks, dependencies, time tracking, attachments, gamification
 */

require_once '../../includes/config.php';
require_once '../../includes/functions.php';

require_login();
require_role('client');

$page_title = 'Enhanced Tasks & Goals';
$user_id = $_SESSION['user_id'];

// Breadcrumb
$breadcrumb_items = [
    ['label' => 'Home', 'url' => '/nov10-crisis/'],
    ['label' => 'Dashboard', 'url' => '/nov10-crisis/pages/client/dashboard.php'],
    ['label' => 'Tasks & Goals', 'url' => '']
];

// Get tasks with extended data
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
        END,
        t.priority DESC,
        t.due_date ASC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$tasks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Calculate task statistics
$stats = [
    'total' => count($tasks),
    'completed' => 0,
    'in_progress' => 0,
    'not_started' => 0,
    'points' => 0
];

foreach ($tasks as $task) {
    $stats[$task['status']]++;
    if ($task['status'] === 'completed') {
        // Points based on priority
        $points_map = ['low' => 10, 'medium' => 20, 'high' => 30, 'urgent' => 50];
        $stats['points'] += $points_map[$task['priority']] ?? 10;
    }
}

$completion_rate = $stats['total'] > 0 ? round(($stats['completed'] / $stats['total']) * 100) : 0;

// Handle task actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_status'])) {
        $task_id = intval($_POST['task_id']);
        $new_status = sanitize_input($_POST['new_status']);
        
        $stmt = $conn->prepare("UPDATE tasks SET status = ?, updated_at = NOW() WHERE task_id = ? AND client_id = ?");
        $stmt->bind_param("sii", $new_status, $task_id, $user_id);
        
        if ($stmt->execute()) {
            if ($new_status === 'completed') {
                $update_stmt = $conn->prepare("UPDATE tasks SET completed_at = NOW() WHERE task_id = ?");
                $update_stmt->bind_param("i", $task_id);
                $update_stmt->execute();
                $update_stmt->close();
            }
            log_activity($conn, $user_id, 'task_status_updated', 'task', $task_id);
            $success = "Task status updated!";
        }
        $stmt->close();
    }
    
    if (isset($_POST['log_time'])) {
        $task_id = intval($_POST['task_id']);
        $hours = floatval($_POST['hours']);
        $notes = sanitize_input($_POST['time_notes'] ?? '');
        
        // Store time log in task_data JSON or separate table
        $stmt = $conn->prepare("SELECT task_data FROM tasks WHERE task_id = ? AND client_id = ?");
        $stmt->bind_param("ii", $task_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $task_data = json_decode($result['task_data'] ?? '{}', true);
        
        if (!isset($task_data['time_logs'])) {
            $task_data['time_logs'] = [];
        }
        
        $task_data['time_logs'][] = [
            'hours' => $hours,
            'notes' => $notes,
            'logged_at' => date('Y-m-d H:i:s'),
            'logged_by' => $user_id
        ];
        
        $json_data = json_encode($task_data);
        $update_stmt = $conn->prepare("UPDATE tasks SET task_data = ? WHERE task_id = ?");
        $update_stmt->bind_param("si", $json_data, $task_id);
        $update_stmt->execute();
        $update_stmt->close();
        $stmt->close();
        
        $success = "Time logged successfully!";
    }
}

include '../../includes/header.php';
?>

<style>
.task-dashboard {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    color: white;
    padding: 24px;
    border-radius: var(--border-radius);
    box-shadow: var(--shadow-md);
}

.stat-card h3 {
    margin: 0 0 8px 0;
    font-size: 14px;
    opacity: 0.9;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.stat-card .stat-value {
    font-size: 36px;
    font-weight: bold;
    margin: 0;
}

.progress-ring {
    margin-top: 12px;
}

.progress-bar-container {
    background: rgba(255, 255, 255, 0.2);
    border-radius: 10px;
    height: 10px;
    overflow: hidden;
    margin-top: 12px;
}

.progress-bar-fill {
    height: 100%;
    background: white;
    border-radius: 10px;
    transition: width 0.5s ease;
}

.task-filters {
    display: flex;
    gap: 12px;
    margin-bottom: 20px;
    flex-wrap: wrap;
    align-items: center;
}

.filter-btn {
    padding: 8px 16px;
    border: 2px solid var(--border-color);
    background: var(--bg-primary);
    border-radius: 20px;
    cursor: pointer;
    transition: var(--transition-fast);
}

.filter-btn:hover, .filter-btn.active {
    border-color: var(--primary-color);
    background: var(--primary-color);
    color: white;
}

.task-card {
    background: var(--bg-primary);
    border-left: 4px solid var(--primary-color);
    border-radius: var(--border-radius);
    padding: 20px;
    margin-bottom: 16px;
    box-shadow: var(--shadow-sm);
    transition: var(--transition-normal);
    position: relative;
}

.task-card:hover {
    transform: translateX(4px);
    box-shadow: var(--shadow-md);
}

.task-card.priority-low { border-left-color: var(--info-color); }
.task-card.priority-medium { border-left-color: var(--warning-color); }
.task-card.priority-high { border-left-color: var(--danger-color); }
.task-card.priority-urgent { border-left-color: #ff0000; }

.task-card.status-completed {
    opacity: 0.7;
    border-left-color: var(--success-color);
}

.task-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 12px;
}

.task-title {
    font-size: 18px;
    font-weight: 600;
    color: var(--text-primary);
    margin: 0;
    flex: 1;
}

.task-badges {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.task-body {
    color: var(--text-secondary);
    margin-bottom: 12px;
}

.task-meta {
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
    font-size: 14px;
    color: var(--text-secondary);
    margin-top: 12px;
}

.task-meta-item {
    display: flex;
    align-items: center;
    gap: 6px;
}

.task-actions {
    display: flex;
    gap: 8px;
    margin-top: 16px;
    padding-top: 16px;
    border-top: 1px solid var(--border-color);
}

.smart-goal-indicator {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    background: var(--success-color);
    color: white;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: bold;
}

.time-tracker {
    background: var(--bg-secondary);
    padding: 12px;
    border-radius: 6px;
    margin-top: 12px;
}

.time-logs {
    display: grid;
    gap: 8px;
    margin-top: 8px;
}

.time-log-entry {
    background: var(--bg-primary);
    padding: 8px 12px;
    border-radius: 4px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 13px;
}

.subtasks-container {
    margin-top: 12px;
    padding-left: 20px;
    border-left: 2px solid var(--border-color);
}

.subtask-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 6px 0;
}

.subtask-item input[type="checkbox"] {
    width: 18px;
    height: 18px;
}

.achievement-badge {
    display: inline-block;
    background: gold;
    color: #333;
    padding: 8px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: bold;
    margin: 4px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

.gamification-panel {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 24px;
    border-radius: var(--border-radius);
    margin-bottom: 24px;
}

.level-display {
    display: flex;
    align-items: center;
    gap: 16px;
    margin-bottom: 16px;
}

.level-number {
    font-size: 48px;
    font-weight: bold;
}

.level-info h3 {
    margin: 0;
    font-size: 24px;
}

.points-display {
    font-size: 16px;
    opacity: 0.9;
}

.achievements-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
    gap: 12px;
    margin-top: 16px;
}

.achievement-item {
    text-align: center;
    padding: 12px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 8px;
    transition: var(--transition-fast);
}

.achievement-item:hover {
    background: rgba(255, 255, 255, 0.2);
    transform: scale(1.05);
}

.achievement-icon {
    font-size: 32px;
    margin-bottom: 4px;
}

.achievement-name {
    font-size: 11px;
    font-weight: 600;
}

.task-dependency {
    background: var(--warning-color);
    color: white;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 11px;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}

.progress-visualization {
    margin: 20px 0;
}

.progress-chart {
    background: var(--bg-primary);
    padding: 20px;
    border-radius: var(--border-radius);
    box-shadow: var(--shadow-sm);
}
</style>

<div class="container">
    <h1>🎯 Tasks & Goals</h1>
    
    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <!-- Gamification Panel -->
    <div class="gamification-panel">
        <div class="level-display">
            <div class="level-number"><?php echo floor($stats['points'] / 100) + 1; ?></div>
            <div class="level-info">
                <h3>Task Master Level</h3>
                <div class="points-display"><?php echo $stats['points']; ?> points earned</div>
                <div class="progress-bar-container" style="width: 200px;">
                    <div class="progress-bar-fill" style="width: <?php echo ($stats['points'] % 100); ?>%"></div>
                </div>
            </div>
        </div>
        
        <div class="achievements-grid">
            <?php if ($stats['completed'] >= 1): ?>
                <div class="achievement-item" title="Complete your first task">
                    <div class="achievement-icon">🎯</div>
                    <div class="achievement-name">First Steps</div>
                </div>
            <?php endif; ?>
            
            <?php if ($stats['completed'] >= 5): ?>
                <div class="achievement-item" title="Complete 5 tasks">
                    <div class="achievement-icon">⭐</div>
                    <div class="achievement-name">Go-Getter</div>
                </div>
            <?php endif; ?>
            
            <?php if ($stats['completed'] >= 10): ?>
                <div class="achievement-item" title="Complete 10 tasks">
                    <div class="achievement-icon">🏆</div>
                    <div class="achievement-name">Achiever</div>
                </div>
            <?php endif; ?>
            
            <?php if ($completion_rate >= 80): ?>
                <div class="achievement-item" title="80% completion rate">
                    <div class="achievement-icon">💯</div>
                    <div class="achievement-name">Perfectionist</div>
                </div>
            <?php endif; ?>
            
            <?php if ($stats['points'] >= 100): ?>
                <div class="achievement-item" title="Earn 100 points">
                    <div class="achievement-icon">💎</div>
                    <div class="achievement-name">Point Master</div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Task Statistics Dashboard -->
    <div class="task-dashboard">
        <div class="stat-card">
            <h3>Total Tasks</h3>
            <div class="stat-value"><?php echo $stats['total']; ?></div>
        </div>
        <div class="stat-card">
            <h3>Completed</h3>
            <div class="stat-value"><?php echo $stats['completed']; ?></div>
            <div class="progress-bar-container">
                <div class="progress-bar-fill" style="width: <?php echo $completion_rate; ?>%"></div>
            </div>
        </div>
        <div class="stat-card">
            <h3>In Progress</h3>
            <div class="stat-value"><?php echo $stats['in_progress']; ?></div>
        </div>
        <div class="stat-card">
            <h3>Not Started</h3>
            <div class="stat-value"><?php echo $stats['not_started']; ?></div>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="task-filters">
        <button class="filter-btn active" data-filter="all">All Tasks</button>
        <button class="filter-btn" data-filter="not_started">Not Started</button>
        <button class="filter-btn" data-filter="in_progress">In Progress</button>
        <button class="filter-btn" data-filter="completed">Completed</button>
        <button class="filter-btn" data-filter="high">High Priority</button>
        <button class="filter-btn" data-filter="smart">SMART Goals</button>
    </div>
    
    <!-- Task List -->
    <div class="tasks-container">
        <?php foreach ($tasks as $task): 
            $task_data = json_decode($task['task_data'] ?? '{}', true);
            $is_smart = !empty($task_data['smart_goal']);
            $subtasks = $task_data['subtasks'] ?? [];
            $time_logs = $task_data['time_logs'] ?? [];
            $total_time = array_sum(array_column($time_logs, 'hours'));
        ?>
            <div class="task-card priority-<?php echo $task['priority']; ?> status-<?php echo $task['status']; ?>" 
                 data-status="<?php echo $task['status']; ?>" 
                 data-priority="<?php echo $task['priority']; ?>"
                 data-smart="<?php echo $is_smart ? 'true' : 'false'; ?>">
                
                <div class="task-header">
                    <h3 class="task-title">
                        <?php if ($is_smart): ?>
                            <span class="smart-goal-indicator">✓ SMART</span>
                        <?php endif; ?>
                        <?php echo htmlspecialchars($task['title']); ?>
                    </h3>
                    <div class="task-badges">
                        <?php echo get_priority_badge($task['priority']); ?>
                        <?php echo get_status_badge($task['status']); ?>
                    </div>
                </div>
                
                <?php if (!empty($task['description'])): ?>
                    <div class="task-body">
                        <?php echo nl2br(htmlspecialchars($task['description'])); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($subtasks)): ?>
                    <div class="subtasks-container">
                        <strong>Subtasks:</strong>
                        <?php foreach ($subtasks as $index => $subtask): ?>
                            <div class="subtask-item">
                                <input type="checkbox" <?php echo $subtask['completed'] ? 'checked' : ''; ?> disabled>
                                <span><?php echo htmlspecialchars($subtask['title']); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($time_logs)): ?>
                    <div class="time-tracker">
                        <strong>Time Tracked: <?php echo number_format($total_time, 1); ?> hours</strong>
                        <div class="time-logs">
                            <?php foreach (array_slice($time_logs, -3) as $log): ?>
                                <div class="time-log-entry">
                                    <span><?php echo $log['hours']; ?>h - <?php echo htmlspecialchars($log['notes']); ?></span>
                                    <span class="text-muted"><?php echo date('M d', strtotime($log['logged_at'])); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <div class="task-meta">
                    <?php if (!empty($task['due_date'])): ?>
                        <div class="task-meta-item">
                            📅 Due: <?php echo date('M d, Y', strtotime($task['due_date'])); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($task['assigned_by_name'])): ?>
                        <div class="task-meta-item">
                            👤 Assigned by: <?php echo htmlspecialchars($task['assigned_by_name']); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($task['category'])): ?>
                        <div class="task-meta-item">
                            🏷️ <?php echo htmlspecialchars($task['category']); ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="task-actions">
                    <?php if ($task['status'] !== 'completed'): ?>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="task_id" value="<?php echo $task['task_id']; ?>">
                            <input type="hidden" name="update_status" value="1">
                            <?php if ($task['status'] === 'not_started'): ?>
                                <button type="submit" name="new_status" value="in_progress" class="btn btn-sm btn-primary">▶️ Start</button>
                            <?php elseif ($task['status'] === 'in_progress'): ?>
                                <button type="submit" name="new_status" value="completed" class="btn btn-sm btn-success">✓ Complete</button>
                            <?php endif; ?>
                        </form>
                    <?php endif; ?>
                    
                    <button class="btn btn-sm btn-light" onclick="openTimeLogModal(<?php echo $task['task_id']; ?>)">⏱️ Log Time</button>
                    <button class="btn btn-sm btn-light" onclick="viewTaskDetails(<?php echo $task['task_id']; ?>)">👁️ Details</button>
                </div>
            </div>
        <?php endforeach; ?>
        
        <?php if (empty($tasks)): ?>
            <div class="text-center p-5">
                <h3>No tasks yet</h3>
                <p>Your tasks and goals will appear here once assigned.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Time Log Modal -->
<div id="timeLogModal" class="modal" style="display: none;">
    <div class="modal-content">
        <span class="modal-close" onclick="closeTimeLogModal()">&times;</span>
        <h2>Log Time</h2>
        <form method="POST">
            <input type="hidden" name="task_id" id="timeLogTaskId">
            <input type="hidden" name="log_time" value="1">
            
            <div class="form-group">
                <label>Hours Spent</label>
                <input type="number" name="hours" step="0.5" min="0.5" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label>Notes (optional)</label>
                <textarea name="time_notes" class="form-control" rows="3"></textarea>
            </div>
            
            <button type="submit" class="btn btn-primary">Log Time</button>
            <button type="button" class="btn btn-light" onclick="closeTimeLogModal()">Cancel</button>
        </form>
    </div>
</div>

<script>
// Filter functionality
document.querySelectorAll('.filter-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        
        const filter = this.dataset.filter;
        const tasks = document.querySelectorAll('.task-card');
        
        tasks.forEach(task => {
            if (filter === 'all') {
                task.style.display = 'block';
            } else if (filter === 'smart') {
                task.style.display = task.dataset.smart === 'true' ? 'block' : 'none';
            } else if (['not_started', 'in_progress', 'completed'].includes(filter)) {
                task.style.display = task.dataset.status === filter ? 'block' : 'none';
            } else if (filter === 'high') {
                task.style.display = ['high', 'urgent'].includes(task.dataset.priority) ? 'block' : 'none';
            }
        });
    });
});

function openTimeLogModal(taskId) {
    document.getElementById('timeLogTaskId').value = taskId;
    document.getElementById('timeLogModal').style.display = 'flex';
}

function closeTimeLogModal() {
    document.getElementById('timeLogModal').style.display = 'none';
}

function viewTaskDetails(taskId) {
    // Could open a detailed view modal
    alert('Task details view - to be implemented');
}
</script>

<?php include '../../includes/footer.php'; ?>
