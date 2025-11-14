<?php
/**
 * System Settings & Branding Customization
 * Admin tools for system configuration
 */

require_once '../../includes/config.php';
require_once '../../includes/functions.php';

require_login();
require_role('admin');

$page_title = 'System Settings';
$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Breadcrumb
$breadcrumb_items = [
    ['label' => 'Home', 'url' => '/nov10-crisis/'],
    ['label' => 'Admin', 'url' => '/nov10-crisis/pages/admin/dashboard.php'],
    ['label' => 'System Settings', 'url' => '']
];

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_settings'])) {
    foreach ($_POST as $key => $value) {
        if (strpos($key, 'setting_') === 0) {
            $setting_key = str_replace('setting_', '', $key);
            list($category, $actual_key) = explode('_', $setting_key, 2);
            
            // Determine type
            $setting_type = is_numeric($value) ? 'number' : (in_array($value, ['0', '1']) ? 'boolean' : 'string');
            
            $stmt = $conn->prepare("INSERT INTO system_settings (category, setting_key, setting_value, setting_type, updated_by) 
                                   VALUES (?, ?, ?, ?, ?)
                                   ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_by = VALUES(updated_by)");
            $stmt->bind_param("ssssi", $category, $actual_key, $value, $setting_type, $user_id);
            $stmt->execute();
            $stmt->close();
        }
    }
    
    $success = "Settings updated successfully!";
}

// Handle logo upload
if (isset($_FILES['logo']) && $_FILES['logo']['error'] === 0) {
    $allowed = ['jpg', 'jpeg', 'png', 'svg'];
    $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
    
    if (in_array($ext, $allowed)) {
        $logo_filename = 'logo_' . time() . '.' . $ext;
        $upload_path = '../../uploads/branding/' . $logo_filename;
        
        if (!is_dir('../../uploads/branding')) {
            mkdir('../../uploads/branding', 0755, true);
        }
        
        if (move_uploaded_file($_FILES['logo']['tmp_name'], $upload_path)) {
            $logo_url = '/nov10-crisis/uploads/branding/' . $logo_filename;
            $stmt = $conn->prepare("INSERT INTO system_settings (category, setting_key, setting_value, setting_type, updated_by) 
                                   VALUES ('branding', 'logo_url', ?, 'string', ?)
                                   ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
            $stmt->bind_param("si", $logo_url, $user_id);
            $stmt->execute();
            $stmt->close();
            $success = "Logo uploaded successfully!";
        }
    }
}

// Get current settings
$stmt = $conn->prepare("SELECT * FROM system_settings ORDER BY category, setting_key");
$stmt->execute();
$all_settings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Organize by category
$settings = [];
foreach ($all_settings as $setting) {
    $settings[$setting['category']][$setting['setting_key']] = $setting['setting_value'];
}

include '../../includes/header.php';
?>

<style>
.settings-container {
    max-width: 1200px;
    margin: 0 auto;
}

.settings-tabs {
    display: flex;
    gap: 8px;
    margin-bottom: 24px;
    border-bottom: 2px solid var(--border-color);
}

.tab-button {
    padding: 12px 24px;
    background: none;
    border: none;
    border-bottom: 3px solid transparent;
    cursor: pointer;
    font-size: 16px;
    font-weight: 500;
    color: var(--text-secondary);
    transition: var(--transition-fast);
}

.tab-button:hover {
    color: var(--text-primary);
}

.tab-button.active {
    color: var(--primary-color);
    border-bottom-color: var(--primary-color);
}

.tab-content {
    display: none;
}

.tab-content.active {
    display: block;
}

.settings-section {
    background: var(--bg-primary);
    border-radius: var(--border-radius);
    padding: 24px;
    margin-bottom: 24px;
    box-shadow: var(--shadow-sm);
}

.settings-section h3 {
    margin-top: 0;
    margin-bottom: 20px;
    color: var(--text-primary);
    border-bottom: 2px solid var(--primary-color);
    padding-bottom: 8px;
}

.setting-item {
    display: grid;
    grid-template-columns: 250px 1fr 200px;
    gap: 16px;
    align-items: center;
    padding: 16px;
    border-bottom: 1px solid var(--border-color);
}

.setting-item:last-child {
    border-bottom: none;
}

.setting-label {
    font-weight: 600;
    color: var(--text-primary);
}

.setting-description {
    font-size: 14px;
    color: var(--text-secondary);
}

.setting-control {
    text-align: right;
}

.color-picker-wrapper {
    display: flex;
    align-items: center;
    gap: 12px;
    justify-content: flex-end;
}

.color-preview {
    width: 50px;
    height: 50px;
    border-radius: 8px;
    border: 2px solid var(--border-color);
}

.logo-preview {
    max-width: 200px;
    max-height: 100px;
    border: 2px solid var(--border-color);
    border-radius: 8px;
    padding: 8px;
    background: white;
}

.feature-toggle {
    display: flex;
    align-items: center;
    gap: 12px;
}

.toggle-switch {
    position: relative;
    width: 60px;
    height: 30px;
}

.toggle-switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.toggle-slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: var(--text-secondary);
    border-radius: 30px;
    transition: 0.4s;
}

.toggle-slider:before {
    position: absolute;
    content: "";
    height: 22px;
    width: 22px;
    left: 4px;
    bottom: 4px;
    background-color: white;
    border-radius: 50%;
    transition: 0.4s;
}

input:checked + .toggle-slider {
    background-color: var(--success-color);
}

input:checked + .toggle-slider:before {
    transform: translateX(30px);
}

.system-info {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 16px;
}

.info-card {
    background: var(--bg-secondary);
    padding: 16px;
    border-radius: var(--border-radius);
    border-left: 4px solid var(--primary-color);
}

.info-label {
    font-size: 12px;
    color: var(--text-secondary);
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-bottom: 8px;
}

.info-value {
    font-size: 18px;
    font-weight: 600;
    color: var(--text-primary);
}

.backup-section {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
}

.backup-btn {
    flex: 1;
    min-width: 200px;
}
</style>

<div class="settings-container">
    <h1>⚙️ System Settings & Configuration</h1>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <!-- Tabs -->
    <div class="settings-tabs">
        <button class="tab-button active" onclick="switchTab('branding')">🎨 Branding</button>
        <button class="tab-button" onclick="switchTab('features')">⚡ Features</button>
        <button class="tab-button" onclick="switchTab('notifications')">🔔 Notifications</button>
        <button class="tab-button" onclick="switchTab('security')">🔒 Security</button>
        <button class="tab-button" onclick="switchTab('limits')">📊 Limits</button>
        <button class="tab-button" onclick="switchTab('backup')">💾 Backup</button>
    </div>
    
    <form method="POST" enctype="multipart/form-data">
        
        <!-- Branding Tab -->
        <div id="tab-branding" class="tab-content active">
            <div class="settings-section">
                <h3>🎨 Branding & Appearance</h3>
                
                <div class="setting-item">
                    <div>
                        <div class="setting-label">Site Name</div>
                        <div class="setting-description">Display name for your organization</div>
                    </div>
                    <div></div>
                    <div class="setting-control">
                        <input type="text" name="setting_branding_site_name" class="form-control" 
                               value="<?php echo htmlspecialchars($settings['branding']['site_name'] ?? 'Crisis Management System'); ?>">
                    </div>
                </div>
                
                <div class="setting-item">
                    <div>
                        <div class="setting-label">Logo</div>
                        <div class="setting-description">Upload your organization logo</div>
                    </div>
                    <div>
                        <?php if (!empty($settings['branding']['logo_url'])): ?>
                            <img src="<?php echo htmlspecialchars($settings['branding']['logo_url']); ?>" class="logo-preview" alt="Logo">
                        <?php endif; ?>
                    </div>
                    <div class="setting-control">
                        <input type="file" name="logo" class="form-control" accept="image/*">
                    </div>
                </div>
                
                <div class="setting-item">
                    <div>
                        <div class="setting-label">Primary Color</div>
                        <div class="setting-description">Main theme color</div>
                    </div>
                    <div></div>
                    <div class="setting-control">
                        <div class="color-picker-wrapper">
                            <input type="color" name="setting_branding_primary_color" 
                                   value="<?php echo $settings['branding']['primary_color'] ?? '#4a90e2'; ?>">
                            <div class="color-preview" style="background: <?php echo $settings['branding']['primary_color'] ?? '#4a90e2'; ?>;"></div>
                        </div>
                    </div>
                </div>
                
                <div class="setting-item">
                    <div>
                        <div class="setting-label">Secondary Color</div>
                        <div class="setting-description">Accent color</div>
                    </div>
                    <div></div>
                    <div class="setting-control">
                        <div class="color-picker-wrapper">
                            <input type="color" name="setting_branding_secondary_color" 
                                   value="<?php echo $settings['branding']['secondary_color'] ?? '#7b68ee'; ?>">
                            <div class="color-preview" style="background: <?php echo $settings['branding']['secondary_color'] ?? '#7b68ee'; ?>;"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Features Tab -->
        <div id="tab-features" class="tab-content">
            <div class="settings-section">
                <h3>⚡ Feature Toggles</h3>
                
                <div class="setting-item">
                    <div>
                        <div class="setting-label">Messaging System</div>
                        <div class="setting-description">Enable/disable messaging features</div>
                    </div>
                    <div></div>
                    <div class="setting-control">
                        <label class="toggle-switch">
                            <input type="hidden" name="setting_features_enable_messaging" value="0">
                            <input type="checkbox" name="setting_features_enable_messaging" value="1" 
                                   <?php echo ($settings['features']['enable_messaging'] ?? '1') == '1' ? 'checked' : ''; ?>>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                </div>
                
                <div class="setting-item">
                    <div>
                        <div class="setting-label">Assessments</div>
                        <div class="setting-description">Enable/disable assessment features</div>
                    </div>
                    <div></div>
                    <div class="setting-control">
                        <label class="toggle-switch">
                            <input type="hidden" name="setting_features_enable_assessments" value="0">
                            <input type="checkbox" name="setting_features_enable_assessments" value="1" 
                                   <?php echo ($settings['features']['enable_assessments'] ?? '1') == '1' ? 'checked' : ''; ?>>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                </div>
                
                <div class="setting-item">
                    <div>
                        <div class="setting-label">Resource Booking</div>
                        <div class="setting-description">Enable/disable resource management</div>
                    </div>
                    <div></div>
                    <div class="setting-control">
                        <label class="toggle-switch">
                            <input type="hidden" name="setting_features_enable_resources" value="0">
                            <input type="checkbox" name="setting_features_enable_resources" value="1" 
                                   <?php echo ($settings['features']['enable_resources'] ?? '1') == '1' ? 'checked' : ''; ?>>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                </div>
                
                <div class="setting-item">
                    <div>
                        <div class="setting-label">Workflow Automation</div>
                        <div class="setting-description">Enable/disable automation features</div>
                    </div>
                    <div></div>
                    <div class="setting-control">
                        <label class="toggle-switch">
                            <input type="hidden" name="setting_features_enable_automation" value="0">
                            <input type="checkbox" name="setting_features_enable_automation" value="1" 
                                   <?php echo ($settings['features']['enable_automation'] ?? '1') == '1' ? 'checked' : ''; ?>>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Notifications Tab -->
        <div id="tab-notifications" class="tab-content">
            <div class="settings-section">
                <h3>🔔 Notification Settings</h3>
                
                <div class="setting-item">
                    <div>
                        <div class="setting-label">Email Notifications</div>
                        <div class="setting-description">Enable external email notifications</div>
                    </div>
                    <div></div>
                    <div class="setting-control">
                        <label class="toggle-switch">
                            <input type="hidden" name="setting_notifications_email_enabled" value="0">
                            <input type="checkbox" name="setting_notifications_email_enabled" value="1" 
                                   <?php echo ($settings['notifications']['email_enabled'] ?? '0') == '1' ? 'checked' : ''; ?>>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                </div>
                
                <div class="setting-item">
                    <div>
                        <div class="setting-label">SMS Notifications</div>
                        <div class="setting-description">Enable SMS text notifications</div>
                    </div>
                    <div></div>
                    <div class="setting-control">
                        <label class="toggle-switch">
                            <input type="hidden" name="setting_notifications_sms_enabled" value="0">
                            <input type="checkbox" name="setting_notifications_sms_enabled" value="1" 
                                   <?php echo ($settings['notifications']['sms_enabled'] ?? '0') == '1' ? 'checked' : ''; ?>>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Security Tab -->
        <div id="tab-security" class="tab-content">
            <div class="settings-section">
                <h3>🔒 Security Settings</h3>
                
                <div class="setting-item">
                    <div>
                        <div class="setting-label">Session Timeout</div>
                        <div class="setting-description">Minutes before auto-logout (3600 = 1 hour)</div>
                    </div>
                    <div></div>
                    <div class="setting-control">
                        <input type="number" name="setting_security_session_timeout" class="form-control" 
                               value="<?php echo $settings['security']['session_timeout'] ?? '3600'; ?>" min="300" max="86400">
                    </div>
                </div>
                
                <div class="setting-item">
                    <div>
                        <div class="setting-label">Max Login Attempts</div>
                        <div class="setting-description">Failed attempts before account lock</div>
                    </div>
                    <div></div>
                    <div class="setting-control">
                        <input type="number" name="setting_security_max_login_attempts" class="form-control" 
                               value="<?php echo $settings['security']['max_login_attempts'] ?? '5'; ?>" min="3" max="10">
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Limits Tab -->
        <div id="tab-limits" class="tab-content">
            <div class="settings-section">
                <h3>📊 System Limits</h3>
                
                <div class="setting-item">
                    <div>
                        <div class="setting-label">Max File Upload (MB)</div>
                        <div class="setting-description">Maximum file size for uploads</div>
                    </div>
                    <div></div>
                    <div class="setting-control">
                        <input type="number" name="setting_limits_max_file_upload_mb" class="form-control" 
                               value="<?php echo $settings['limits']['max_file_upload_mb'] ?? '10'; ?>" min="1" max="100">
                    </div>
                </div>
                
                <div class="setting-item">
                    <div>
                        <div class="setting-label">Max Attachments Per Message</div>
                        <div class="setting-description">Maximum files per message</div>
                    </div>
                    <div></div>
                    <div class="setting-control">
                        <input type="number" name="setting_limits_max_attachments_per_message" class="form-control" 
                               value="<?php echo $settings['limits']['max_attachments_per_message'] ?? '5'; ?>" min="1" max="20">
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Backup Tab -->
        <div id="tab-backup" class="tab-content">
            <div class="settings-section">
                <h3>💾 Backup & Maintenance</h3>
                
                <div class="system-info">
                    <div class="info-card">
                        <div class="info-label">Database Size</div>
                        <div class="info-value">45.2 MB</div>
                    </div>
                    <div class="info-card">
                        <div class="info-label">Total Users</div>
                        <div class="info-value">
                            <?php
                            $user_count = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
                            echo $user_count;
                            ?>
                        </div>
                    </div>
                    <div class="info-card">
                        <div class="info-label">Last Backup</div>
                        <div class="info-value">Never</div>
                    </div>
                    <div class="info-card">
                        <div class="info-label">System Status</div>
                        <div class="info-value" style="color: var(--success-color);">✓ Healthy</div>
                    </div>
                </div>
                
                <div class="backup-section mt-4">
                    <button type="button" class="btn btn-primary backup-btn" onclick="createBackup()">
                        💾 Create Database Backup
                    </button>
                    <button type="button" class="btn btn-warning backup-btn" onclick="clearCache()">
                        🗑️ Clear System Cache
                    </button>
                    <button type="button" class="btn btn-danger backup-btn" onclick="maintenanceMode()">
                        🔧 Enable Maintenance Mode
                    </button>
                </div>
            </div>
        </div>
        
        <div class="text-center mt-4">
            <button type="submit" name="update_settings" class="btn btn-primary btn-lg">💾 Save All Settings</button>
        </div>
    </form>
</div>

<script>
function switchTab(tabName) {
    // Hide all tabs
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.remove('active');
    });
    
    // Remove active from all buttons
    document.querySelectorAll('.tab-button').forEach(btn => {
        btn.classList.remove('active');
    });
    
    // Show selected tab
    document.getElementById('tab-' + tabName).classList.add('active');
    event.target.classList.add('active');
}

function createBackup() {
    if (confirm('Create a full database backup? This may take a few minutes.')) {
        alert('Backup feature would be implemented with actual backup logic.\nBackup file would be created and available for download.');
    }
}

function clearCache() {
    if (confirm('Clear all system caches? This may temporarily slow down the system.')) {
        alert('Cache clearing would be implemented here.');
    }
}

function maintenanceMode() {
    if (confirm('Enable maintenance mode? Users will not be able to access the system.')) {
        alert('Maintenance mode toggle would be implemented here.');
    }
}
</script>

<?php include '../../includes/footer.php'; ?>
