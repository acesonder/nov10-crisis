<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

require_login();
require_role('admin');

$page_title = 'Admin Dashboard';

// Get system statistics
$stats = [];

// Total users by role
$result = $conn->query("SELECT role, COUNT(*) as count FROM users GROUP BY role");
while ($row = $result->fetch_assoc()) {
    $stats['users_' . $row['role']] = $row['count'];
}

// Total assessments
$result = $conn->query("SELECT COUNT(*) as count FROM assessments");
$stats['total_assessments'] = $result->fetch_assoc()['count'];

// Active cases
$result = $conn->query("SELECT COUNT(*) as count FROM cases WHERE status IN ('open', 'in_progress')");
$stats['active_cases'] = $result->fetch_assoc()['count'];

// Open incidents
$result = $conn->query("SELECT COUNT(*) as count FROM incidents WHERE status NOT IN ('closed')");
$stats['open_incidents'] = $result->fetch_assoc()['count'];

// Pending referrals
$result = $conn->query("SELECT COUNT(*) as count FROM referrals WHERE status = 'pending'");
$stats['pending_referrals'] = $result->fetch_assoc()['count'];

// Recent activity
$recent_activity = $conn->query("SELECT a.*, u.username, u.full_name FROM activity_log a LEFT JOIN users u ON a.user_id = u.user_id ORDER BY a.created_at DESC LIMIT 10")->fetch_all(MYSQLI_ASSOC);

include '../../includes/header.php';
?>

<div class="container" style="padding: 2rem 0;">
    <div class="mb-4">
        <h1>Admin Dashboard 🔐</h1>
        <p style="color: var(--text-secondary);">System overview and management</p>
    </div>
    
    <!-- System Stats -->
    <div class="row mb-4">
        <div class="col-12 col-md-3">
            <div class="card text-center">
                <div class="widget">
                    <span class="widget-icon">👥</span>
                    <span class="widget-value"><?php echo $stats['users_client'] ?? 0; ?></span>
                    <span class="widget-label">Clients</span>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-3">
            <div class="card text-center">
                <div class="widget">
                    <span class="widget-icon">👨‍💼</span>
                    <span class="widget-value"><?php echo $stats['users_staff'] ?? 0; ?></span>
                    <span class="widget-label">Staff Members</span>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-3">
            <div class="card text-center">
                <div class="widget">
                    <span class="widget-icon">🏢</span>
                    <span class="widget-value"><?php echo $stats['users_service_provider'] ?? 0; ?></span>
                    <span class="widget-label">Service Providers</span>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-3">
            <div class="card text-center">
                <div class="widget">
                    <span class="widget-icon">📋</span>
                    <span class="widget-value"><?php echo $stats['total_assessments']; ?></span>
                    <span class="widget-label">Total Assessments</span>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mb-4">
        <div class="col-12 col-md-4">
            <div class="card text-center">
                <div class="widget">
                    <span class="widget-icon">📁</span>
                    <span class="widget-value"><?php echo $stats['active_cases']; ?></span>
                    <span class="widget-label">Active Cases</span>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card text-center">
                <div class="widget">
                    <span class="widget-icon">⚠️</span>
                    <span class="widget-value"><?php echo $stats['open_incidents']; ?></span>
                    <span class="widget-label">Open Incidents</span>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card text-center">
                <div class="widget">
                    <span class="widget-icon">🤝</span>
                    <span class="widget-value"><?php echo $stats['pending_referrals']; ?></span>
                    <span class="widget-label">Pending Referrals</span>
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
                    <a href="/nov10-crisis/pages/admin/users.php?action=new" class="btn btn-primary w-100">
                        👤 Add User
                    </a>
                </div>
                <div class="col-12 col-md-3 mb-2">
                    <a href="/nov10-crisis/pages/admin/providers.php?action=new" class="btn btn-info w-100">
                        🏢 Add Provider
                    </a>
                </div>
                <div class="col-12 col-md-3 mb-2">
                    <a href="/nov10-crisis/pages/admin/news.php?action=new" class="btn btn-secondary w-100">
                        📰 Post News
                    </a>
                </div>
                <div class="col-12 col-md-3 mb-2">
                    <a href="/nov10-crisis/pages/admin/reports.php" class="btn btn-success w-100">
                        📊 View Reports
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Recent Activity -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Recent System Activity</h3>
        </div>
        <div class="card-body">
            <?php if (empty($recent_activity)): ?>
                <p style="color: var(--text-secondary);">No recent activity</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Action</th>
                                <th>Entity</th>
                                <th>Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_activity as $activity): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($activity['full_name'] ?? 'System'); ?></td>
                                    <td><?php echo htmlspecialchars($activity['action']); ?></td>
                                    <td><?php echo htmlspecialchars($activity['entity_type'] ?? 'N/A'); ?></td>
                                    <td><?php echo time_ago($activity['created_at']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
