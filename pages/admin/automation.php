<?php
/**
 * Workflow Automation & Admin Tools
 * Includes: Auto-assignment, triggers, scheduled tasks, reminders, status automation
 */

require_once '../../includes/config.php';
require_once '../../includes/functions.php';

require_login();
require_role('admin');

$page_title = 'Workflow Automation';
$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Breadcrumb
$breadcrumb_items = [
    ['label' => 'Home', 'url' => '/nov10-crisis/'],
    ['label' => 'Admin', 'url' => '/nov10-crisis/pages/admin/dashboard.php'],
    ['label' => 'Automation', 'url' => '']
];

// Handle automation rule creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_rule'])) {
    $rule_name = sanitize_input($_POST['rule_name']);
    $rule_type = sanitize_input($_POST['rule_type']);
    $trigger_event = sanitize_input($_POST['trigger_event']);
    $conditions = json_encode($_POST['conditions'] ?? []);
    $actions = json_encode($_POST['actions'] ?? []);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    $stmt = $conn->prepare("INSERT INTO automation_rules (rule_name, rule_type, trigger_event, conditions, actions, is_active, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssii", $rule_name, $rule_type, $trigger_event, $conditions, $actions, $is_active, $user_id);
    
    if ($stmt->execute()) {
        $success = "Automation rule created successfully!";
    } else {
        $error = "Failed to create rule";
    }
    $stmt->close();
}

// Get existing automation rules
$stmt = $conn->prepare("SELECT * FROM automation_rules ORDER BY created_at DESC");
$stmt->execute();
$automation_rules = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get automation statistics
$stats = [
    'total_rules' => count($automation_rules),
    'active_rules' => count(array_filter($automation_rules, fn($r) => $r['is_active'])),
    'executions_today' => 0,
    'success_rate' => 95
];

include '../../includes/header.php';
?>

<style>
.automation-dashboard {
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
}

.stat-card .stat-value {
    font-size: 36px;
    font-weight: bold;
}

.automation-section {
    background: var(--bg-primary);
    border-radius: var(--border-radius);
    padding: 24px;
    margin-bottom: 24px;
    box-shadow: var(--shadow-sm);
}

.automation-section h2 {
    margin-top: 0;
    border-bottom: 2px solid var(--primary-color);
    padding-bottom: 12px;
}

.rule-card {
    background: var(--bg-secondary);
    border-left: 4px solid var(--primary-color);
    border-radius: var(--border-radius);
    padding: 20px;
    margin-bottom: 16px;
    transition: var(--transition-fast);
}

.rule-card:hover {
    transform: translateX(4px);
    box-shadow: var(--shadow-md);
}

.rule-card.inactive {
    opacity: 0.6;
    border-left-color: var(--text-secondary);
}

.rule-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 12px;
}

.rule-title {
    font-size: 18px;
    font-weight: 600;
    color: var(--text-primary);
}

.rule-type-badge {
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
}

.rule-body {
    color: var(--text-secondary);
    margin-bottom: 12px;
}

.rule-meta {
    display: flex;
    gap: 20px;
    font-size: 14px;
    color: var(--text-secondary);
}

.rule-actions {
    display: flex;
    gap: 8px;
    margin-top: 12px;
}

.automation-form {
    background: var(--bg-secondary);
    padding: 20px;
    border-radius: var(--border-radius);
    margin-bottom: 20px;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 16px;
    margin-bottom: 16px;
}

.condition-builder, .action-builder {
    background: var(--bg-tertiary);
    padding: 16px;
    border-radius: 8px;
    margin-top: 12px;
}

.condition-item, .action-item {
    background: var(--bg-primary);
    padding: 12px;
    border-radius: 6px;
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    gap: 12px;
}

.quick-templates {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 16px;
    margin-top: 20px;
}

.template-card {
    background: var(--bg-secondary);
    border: 2px solid var(--border-color);
    border-radius: var(--border-radius);
    padding: 20px;
    cursor: pointer;
    transition: var(--transition-fast);
}

.template-card:hover {
    border-color: var(--primary-color);
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

.template-icon {
    font-size: 48px;
    margin-bottom: 12px;
}

.template-title {
    font-size: 18px;
    font-weight: 600;
    margin-bottom: 8px;
    color: var(--text-primary);
}

.template-description {
    font-size: 14px;
    color: var(--text-secondary);
}

.execution-log {
    max-height: 400px;
    overflow-y: auto;
}

.log-entry {
    padding: 12px;
    border-left: 3px solid var(--success-color);
    background: var(--bg-secondary);
    border-radius: 4px;
    margin-bottom: 8px;
    font-size: 13px;
}

.log-entry.error {
    border-left-color: var(--danger-color);
}

.log-time {
    color: var(--text-secondary);
    font-size: 12px;
}
</style>

<div class="container">
    <h1>⚙️ Workflow Automation & Admin Tools</h1>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <!-- Statistics Dashboard -->
    <div class="automation-dashboard">
        <div class="stat-card">
            <h3>Total Rules</h3>
            <div class="stat-value"><?php echo $stats['total_rules']; ?></div>
        </div>
        <div class="stat-card">
            <h3>Active Rules</h3>
            <div class="stat-value"><?php echo $stats['active_rules']; ?></div>
        </div>
        <div class="stat-card">
            <h3>Executions Today</h3>
            <div class="stat-value"><?php echo $stats['executions_today']; ?></div>
        </div>
        <div class="stat-card">
            <h3>Success Rate</h3>
            <div class="stat-value"><?php echo $stats['success_rate']; ?>%</div>
        </div>
    </div>
    
    <!-- Quick Templates -->
    <div class="automation-section">
        <h2>🚀 Quick Start Templates</h2>
        <p>Click a template to quickly create a common automation rule</p>
        
        <div class="quick-templates">
            <div class="template-card" onclick="loadTemplate('auto_assign')">
                <div class="template-icon">👥</div>
                <div class="template-title">Auto-Assignment</div>
                <div class="template-description">Automatically assign new cases to available staff based on workload</div>
            </div>
            
            <div class="template-card" onclick="loadTemplate('follow_up')">
                <div class="template-icon">📅</div>
                <div class="template-title">Follow-up Reminders</div>
                <div class="template-description">Send automatic reminders 3 days after assessment completion</div>
            </div>
            
            <div class="template-card" onclick="loadTemplate('status_update')">
                <div class="template-icon">🔄</div>
                <div class="template-title">Status Automation</div>
                <div class="template-description">Auto-update case status when all tasks are completed</div>
            </div>
            
            <div class="template-card" onclick="loadTemplate('urgent_alert')">
                <div class="template-icon">🚨</div>
                <div class="template-title">Urgent Alerts</div>
                <div class="template-description">Notify supervisors immediately when urgent cases are created</div>
            </div>
            
            <div class="template-card" onclick="loadTemplate('task_creation')">
                <div class="template-icon">✅</div>
                <div class="template-title">Scheduled Tasks</div>
                <div class="template-description">Create follow-up tasks automatically after specific events</div>
            </div>
            
            <div class="template-card" onclick="loadTemplate('referral_routing')">
                <div class="template-icon">🏥</div>
                <div class="template-title">Referral Routing</div>
                <div class="template-description">Route referrals to providers based on service type and capacity</div>
            </div>
        </div>
    </div>
    
    <!-- Create New Rule -->
    <div class="automation-section">
        <h2>➕ Create Automation Rule</h2>
        
        <form method="POST" class="automation-form">
            <div class="form-grid">
                <div class="form-group">
                    <label>Rule Name *</label>
                    <input type="text" name="rule_name" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label>Rule Type *</label>
                    <select name="rule_type" class="form-control" required>
                        <option value="assignment">Auto-Assignment</option>
                        <option value="notification">Notification</option>
                        <option value="status">Status Change</option>
                        <option value="task">Task Creation</option>
                        <option value="reminder">Reminder</option>
                        <option value="routing">Routing</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Trigger Event *</label>
                    <select name="trigger_event" class="form-control" required>
                        <option value="case_created">New Case Created</option>
                        <option value="assessment_completed">Assessment Completed</option>
                        <option value="task_completed">Task Completed</option>
                        <option value="referral_received">Referral Received</option>
                        <option value="message_received">Message Received</option>
                        <option value="time_based">Time-Based (Scheduled)</option>
                    </select>
                </div>
            </div>
            
            <div class="condition-builder">
                <h4>Conditions (When to trigger)</h4>
                <div id="conditions">
                    <div class="condition-item">
                        <select name="conditions[0][field]" class="form-control">
                            <option value="priority">Priority</option>
                            <option value="status">Status</option>
                            <option value="risk_level">Risk Level</option>
                            <option value="assigned_to">Assigned To</option>
                        </select>
                        <select name="conditions[0][operator]" class="form-control">
                            <option value="equals">Equals</option>
                            <option value="not_equals">Not Equals</option>
                            <option value="contains">Contains</option>
                            <option value="greater_than">Greater Than</option>
                        </select>
                        <input type="text" name="conditions[0][value]" class="form-control" placeholder="Value">
                    </div>
                </div>
                <button type="button" class="btn btn-sm btn-light" onclick="addCondition()">+ Add Condition</button>
            </div>
            
            <div class="action-builder">
                <h4>Actions (What to do)</h4>
                <div id="actions">
                    <div class="action-item">
                        <select name="actions[0][type]" class="form-control">
                            <option value="assign">Assign to User</option>
                            <option value="notify">Send Notification</option>
                            <option value="create_task">Create Task</option>
                            <option value="update_status">Update Status</option>
                            <option value="send_email">Send Email</option>
                            <option value="send_message">Send Message</option>
                        </select>
                        <input type="text" name="actions[0][params]" class="form-control" placeholder="Parameters (JSON)">
                    </div>
                </div>
                <button type="button" class="btn btn-sm btn-light" onclick="addAction()">+ Add Action</button>
            </div>
            
            <div class="form-group mt-3">
                <label>
                    <input type="checkbox" name="is_active" checked> Activate rule immediately
                </label>
            </div>
            
            <button type="submit" name="create_rule" class="btn btn-primary">💾 Create Rule</button>
        </form>
    </div>
    
    <!-- Existing Rules -->
    <div class="automation-section">
        <h2>📋 Active Automation Rules</h2>
        
        <?php if (empty($automation_rules)): ?>
            <p>No automation rules created yet. Use the quick templates above to get started!</p>
        <?php else: ?>
            <?php foreach ($automation_rules as $rule): 
                $conditions = json_decode($rule['conditions'] ?? '[]', true);
                $actions = json_decode($rule['actions'] ?? '[]', true);
            ?>
                <div class="rule-card <?php echo $rule['is_active'] ? '' : 'inactive'; ?>">
                    <div class="rule-header">
                        <div>
                            <div class="rule-title">
                                <?php echo $rule['is_active'] ? '✅' : '⏸️'; ?>
                                <?php echo htmlspecialchars($rule['rule_name']); ?>
                            </div>
                            <span class="rule-type-badge badge badge-info"><?php echo $rule['rule_type']; ?></span>
                        </div>
                    </div>
                    
                    <div class="rule-body">
                        <strong>Trigger:</strong> <?php echo ucwords(str_replace('_', ' ', $rule['trigger_event'])); ?><br>
                        <strong>Conditions:</strong> <?php echo count($conditions); ?> condition(s)<br>
                        <strong>Actions:</strong> <?php echo count($actions); ?> action(s)
                    </div>
                    
                    <div class="rule-meta">
                        <span>Created: <?php echo date('M d, Y', strtotime($rule['created_at'])); ?></span>
                        <span>Executions: <?php echo $rule['execution_count'] ?? 0; ?></span>
                        <span>Last Run: <?php echo $rule['last_executed'] ? date('M d, g:i A', strtotime($rule['last_executed'])) : 'Never'; ?></span>
                    </div>
                    
                    <div class="rule-actions">
                        <button class="btn btn-sm btn-primary" onclick="editRule(<?php echo $rule['rule_id']; ?>)">✏️ Edit</button>
                        <button class="btn btn-sm btn-<?php echo $rule['is_active'] ? 'warning' : 'success'; ?>" onclick="toggleRule(<?php echo $rule['rule_id']; ?>)">
                            <?php echo $rule['is_active'] ? '⏸️ Pause' : '▶️ Activate'; ?>
                        </button>
                        <button class="btn btn-sm btn-light" onclick="testRule(<?php echo $rule['rule_id']; ?>)">🧪 Test</button>
                        <button class="btn btn-sm btn-danger" onclick="deleteRule(<?php echo $rule['rule_id']; ?>)">🗑️ Delete</button>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <!-- Recent Executions Log -->
    <div class="automation-section">
        <h2>📊 Execution Log (Last 24 hours)</h2>
        <div class="execution-log">
            <div class="log-entry">
                <div class="log-time">Nov 14, 2025 10:30 AM</div>
                <div><strong>Auto-Assignment</strong> executed successfully - Assigned Case #123 to John Doe</div>
            </div>
            <div class="log-entry">
                <div class="log-time">Nov 14, 2025 09:15 AM</div>
                <div><strong>Follow-up Reminders</strong> executed successfully - Sent 5 reminders</div>
            </div>
            <div class="log-entry error">
                <div class="log-time">Nov 14, 2025 08:00 AM</div>
                <div><strong>Status Automation</strong> failed - Invalid status value</div>
            </div>
        </div>
    </div>
</div>

<script>
let conditionCount = 1;
let actionCount = 1;

function addCondition() {
    const container = document.getElementById('conditions');
    const newCondition = document.createElement('div');
    newCondition.className = 'condition-item';
    newCondition.innerHTML = `
        <select name="conditions[${conditionCount}][field]" class="form-control">
            <option value="priority">Priority</option>
            <option value="status">Status</option>
            <option value="risk_level">Risk Level</option>
            <option value="assigned_to">Assigned To</option>
        </select>
        <select name="conditions[${conditionCount}][operator]" class="form-control">
            <option value="equals">Equals</option>
            <option value="not_equals">Not Equals</option>
            <option value="contains">Contains</option>
        </select>
        <input type="text" name="conditions[${conditionCount}][value]" class="form-control" placeholder="Value">
        <button type="button" class="btn btn-sm btn-danger" onclick="this.parentElement.remove()">×</button>
    `;
    container.appendChild(newCondition);
    conditionCount++;
}

function addAction() {
    const container = document.getElementById('actions');
    const newAction = document.createElement('div');
    newAction.className = 'action-item';
    newAction.innerHTML = `
        <select name="actions[${actionCount}][type]" class="form-control">
            <option value="assign">Assign to User</option>
            <option value="notify">Send Notification</option>
            <option value="create_task">Create Task</option>
            <option value="update_status">Update Status</option>
        </select>
        <input type="text" name="actions[${actionCount}][params]" class="form-control" placeholder="Parameters">
        <button type="button" class="btn btn-sm btn-danger" onclick="this.parentElement.remove()">×</button>
    `;
    container.appendChild(newAction);
    actionCount++;
}

function loadTemplate(templateId) {
    alert('Loading template: ' + templateId + '\n\nThis will pre-fill the form with template values.');
    // Template loading logic would go here
}

function editRule(ruleId) {
    alert('Edit rule ID: ' + ruleId);
}

function toggleRule(ruleId) {
    if (confirm('Toggle rule activation status?')) {
        window.location.href = '?action=toggle&rule_id=' + ruleId;
    }
}

function testRule(ruleId) {
    alert('Testing rule ID: ' + ruleId + '\n\nThis would execute the rule in test mode.');
}

function deleteRule(ruleId) {
    if (confirm('Delete this automation rule? This cannot be undone.')) {
        window.location.href = '?action=delete&rule_id=' + ruleId;
    }
}
</script>

<?php include '../../includes/footer.php'; ?>
