<?php
/**
 * Permission Matrix
 * View and manage role-based permissions
 */

require_once '../../includes/config.php';
require_once '../../includes/functions.php';

require_login();
require_role('admin');

$page_title = 'Permission Matrix';
$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Breadcrumb
$breadcrumb_items = [
    ['label' => 'Home', 'url' => '/nov10-crisis/'],
    ['label' => 'Admin', 'url' => '/nov10-crisis/pages/admin/dashboard.php'],
    ['label' => 'Permissions', 'url' => '']
];

// Get all roles
$stmt = $conn->prepare("SELECT * FROM permission_roles ORDER BY role_name");
$stmt->execute();
$roles = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get all permissions grouped by category
$stmt = $conn->prepare("SELECT * FROM permissions ORDER BY category, permission_name");
$stmt->execute();
$all_permissions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Group permissions by category
$permissions_by_category = [];
foreach ($all_permissions as $perm) {
    $permissions_by_category[$perm['category']][] = $perm;
}

// Get role permissions
$role_permissions = [];
foreach ($roles as $role) {
    $stmt = $conn->prepare("SELECT permission_id FROM role_permissions WHERE role_name = ?");
    $stmt->bind_param("s", $role['role_name']);
    $stmt->execute();
    $result = $stmt->get_result();
    $role_permissions[$role['role_name']] = array_column($result->fetch_all(MYSQLI_ASSOC), 'permission_id');
    $stmt->close();
}

include '../../includes/header.php';
?>

<style>
.permission-matrix {
    background: var(--bg-primary);
    border-radius: var(--border-radius);
    padding: 24px;
    box-shadow: var(--shadow-sm);
    overflow-x: auto;
}

.matrix-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
}

.matrix-table th {
    background: var(--primary-color);
    color: white;
    padding: 12px;
    text-align: left;
    position: sticky;
    top: 0;
    z-index: 10;
}

.matrix-table td {
    padding: 12px;
    border-bottom: 1px solid var(--border-color);
}

.matrix-table tr:hover {
    background: var(--bg-secondary);
}

.category-header {
    background: var(--bg-tertiary) !important;
    font-weight: bold;
    font-size: 14px;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.permission-check {
    text-align: center;
}

.permission-check.has-permission {
    color: var(--success-color);
    font-size: 20px;
}

.permission-check.no-permission {
    color: var(--text-secondary);
    font-size: 20px;
    opacity: 0.3;
}

.role-column {
    min-width: 100px;
    text-align: center;
}

.role-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
    margin: 2px;
}

.role-admin { background: var(--danger-color); color: white; }
.role-staff { background: var(--primary-color); color: white; }
.role-client { background: var(--success-color); color: white; }
.role-provider { background: var(--warning-color); color: white; }

.legend {
    display: flex;
    gap: 16px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 8px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
}

.stat-box {
    background: var(--bg-secondary);
    padding: 16px;
    border-radius: var(--border-radius);
    border-left: 4px solid var(--primary-color);
}

.stat-value {
    font-size: 32px;
    font-weight: bold;
    color: var(--primary-color);
}

.stat-label {
    font-size: 14px;
    color: var(--text-secondary);
    text-transform: uppercase;
    letter-spacing: 1px;
}
</style>

<div class="container-fluid">
    <h1>🔐 Permission Matrix</h1>
    
    <!-- Statistics -->
    <div class="stats-grid">
        <div class="stat-box">
            <div class="stat-value"><?php echo count($roles); ?></div>
            <div class="stat-label">Roles</div>
        </div>
        <div class="stat-box">
            <div class="stat-value"><?php echo count($all_permissions); ?></div>
            <div class="stat-label">Permissions</div>
        </div>
        <div class="stat-box">
            <div class="stat-value"><?php echo count($permissions_by_category); ?></div>
            <div class="stat-label">Categories</div>
        </div>
    </div>
    
    <!-- Legend -->
    <div class="permission-matrix">
        <div class="legend">
            <div class="legend-item">
                <span class="role-badge role-admin">Admin</span>
                <span>Full Access</span>
            </div>
            <div class="legend-item">
                <span class="role-badge role-staff">Staff</span>
                <span>Case Management</span>
            </div>
            <div class="legend-item">
                <span class="role-badge role-client">Client</span>
                <span>Service Recipient</span>
            </div>
            <div class="legend-item">
                <span class="role-badge role-provider">Provider</span>
                <span>Service Provider</span>
            </div>
        </div>
        
        <!-- Permission Matrix Table -->
        <table class="matrix-table">
            <thead>
                <tr>
                    <th style="width: 40%;">Permission</th>
                    <?php foreach ($roles as $role): ?>
                        <th class="role-column">
                            <span class="role-badge role-<?php echo $role['role_name']; ?>">
                                <?php echo htmlspecialchars($role['display_name']); ?>
                            </span>
                        </th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($permissions_by_category as $category => $perms): ?>
                    <tr>
                        <td colspan="<?php echo count($roles) + 1; ?>" class="category-header">
                            <?php echo htmlspecialchars($category); ?>
                        </td>
                    </tr>
                    
                    <?php foreach ($perms as $perm): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($perm['permission_name']); ?></strong>
                                <?php if (!empty($perm['description'])): ?>
                                    <br><small class="text-muted"><?php echo htmlspecialchars($perm['description']); ?></small>
                                <?php endif; ?>
                            </td>
                            
                            <?php foreach ($roles as $role): ?>
                                <td class="permission-check <?php echo in_array($perm['permission_id'], $role_permissions[$role['role_name']]) ? 'has-permission' : 'no-permission'; ?>">
                                    <?php echo in_array($perm['permission_id'], $role_permissions[$role['role_name']]) ? '✓' : '–'; ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Permission Summary -->
    <div class="permission-matrix mt-4">
        <h3>Permission Summary by Role</h3>
        
        <div class="stats-grid">
            <?php foreach ($roles as $role): ?>
                <div class="stat-box">
                    <div class="stat-value"><?php echo count($role_permissions[$role['role_name']]); ?></div>
                    <div class="stat-label">
                        <span class="role-badge role-<?php echo $role['role_name']; ?>">
                            <?php echo htmlspecialchars($role['display_name']); ?>
                        </span>
                        Permissions
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="permission-matrix mt-4">
        <h3>Quick Actions</h3>
        <p>Permission editing coming soon. Currently showing read-only view of the permission matrix.</p>
        <button class="btn btn-light" disabled>✏️ Edit Permissions</button>
        <button class="btn btn-light" disabled>➕ Add New Role</button>
        <button class="btn btn-light" disabled>➕ Add New Permission</button>
        <button class="btn btn-primary" onclick="window.print()">🖨️ Print Matrix</button>
    </div>
</div>

<style media="print">
.btn, .legend { display: none !important; }
.matrix-table th { background: #333 !important; -webkit-print-color-adjust: exact; }
</style>

<?php include '../../includes/footer.php'; ?>
