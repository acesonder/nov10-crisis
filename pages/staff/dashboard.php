<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

require_login();
require_role('staff');

$page_title = 'Staff Dashboard';
$user_id = $_SESSION['user_id'];

// Get staff statistics
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM cases WHERE case_manager_id = ? AND status IN ('open', 'in_progress')");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$my_cases_count = $stmt->get_result()->fetch_assoc()['count'];
$stmt->close();

$stmt = $conn->prepare("SELECT COUNT(*) as count FROM tasks WHERE assigned_by = ? AND status != 'completed'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$pending_tasks_count = $stmt->get_result()->fetch_assoc()['count'];
$stmt->close();

$result = $conn->query("SELECT COUNT(*) as count FROM incidents WHERE status NOT IN ('closed')");
$open_incidents_count = $result->fetch_assoc()['count'];

// Get my recent cases
$stmt = $conn->prepare("SELECT c.*, u.full_name as client_name FROM cases c JOIN users u ON c.client_id = u.user_id WHERE c.case_manager_id = ? ORDER BY c.updated_at DESC LIMIT 5");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$my_cases = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

include '../../includes/header.php';
?>

<div class="container" style="padding: 2rem 0;">
    <div class="mb-4">
        <h1>Welcome back, <?php echo htmlspecialchars($_SESSION['full_name']); ?>! 💼</h1>
        <p style="color: var(--text-secondary);">Your case management overview</p>
    </div>
    
    <!-- Stats Overview -->
    <div class="row mb-4">
        <div class="col-12 col-md-4">
            <div class="card text-center">
                <div class="widget">
                    <span class="widget-icon">📁</span>
                    <span class="widget-value"><?php echo $my_cases_count; ?></span>
                    <span class="widget-label">Active Cases</span>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card text-center">
                <div class="widget">
                    <span class="widget-icon">✅</span>
                    <span class="widget-value"><?php echo $pending_tasks_count; ?></span>
                    <span class="widget-label">Pending Tasks</span>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card text-center">
                <div class="widget">
                    <span class="widget-icon">⚠️</span>
                    <span class="widget-value"><?php echo $open_incidents_count; ?></span>
                    <span class="widget-label">Open Incidents</span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="card mb-4">
        <div class="card-header">
            <h3 class="card-title">Quick Actions</h3>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-12 col-md-3 mb-2">
                    <a href="/nov10-crisis/pages/staff/cases.php?action=new" class="btn btn-primary w-100">
                        📁 New Case
                    </a>
                </div>
                <div class="col-12 col-md-3 mb-2">
                    <a href="/nov10-crisis/pages/staff/clients.php" class="btn btn-info w-100">
                        👥 View Clients
                    </a>
                </div>
                <div class="col-12 col-md-3 mb-2">
                    <a href="/nov10-crisis/pages/staff/incidents.php?action=report" class="btn btn-warning w-100">
                        ⚠️ Report Incident
                    </a>
                </div>
                <div class="col-12 col-md-3 mb-2">
                    <a href="/nov10-crisis/pages/staff/resources.php" class="btn btn-success w-100">
                        🛏️ Manage Resources
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- My Recent Cases -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">My Recent Cases</h3>
        </div>
        <div class="card-body">
            <?php if (empty($my_cases)): ?>
                <p style="color: var(--text-secondary);">No cases assigned yet.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Case #</th>
                                <th>Client</th>
                                <th>Title</th>
                                <th>Priority</th>
                                <th>Status</th>
                                <th>Updated</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($my_cases as $case): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($case['case_number']); ?></td>
                                    <td><?php echo htmlspecialchars($case['client_name']); ?></td>
                                    <td><?php echo htmlspecialchars($case['title']); ?></td>
                                    <td><?php echo get_priority_badge($case['priority']); ?></td>
                                    <td><?php echo get_status_badge($case['status']); ?></td>
                                    <td><?php echo time_ago($case['updated_at']); ?></td>
                                    <td>
                                        <a href="/nov10-crisis/pages/staff/case-view.php?id=<?php echo $case['case_id']; ?>" class="btn btn-sm btn-primary">View</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <a href="/nov10-crisis/pages/staff/cases.php" class="btn btn-secondary">View All Cases</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
