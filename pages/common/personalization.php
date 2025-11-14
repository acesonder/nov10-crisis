<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

require_login();

$page_title = 'Personalization Settings';
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Breadcrumb
$breadcrumb_items = [
    ['label' => 'Home', 'url' => '/nov10-crisis/'],
    ['label' => 'Profile', 'url' => '/nov10-crisis/pages/common/profile.php'],
    ['label' => 'Personalization', 'url' => '']
];

// Get current preferences
$prefs = get_user_preferences($conn, $user_id);
$preferences_data = isset($prefs['preferences_data']) ? json_decode($prefs['preferences_data'], true) : [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $theme = $_POST['theme'] ?? 'light';
    $color_scheme = $_POST['color_scheme'] ?? 'blue';
    $language = $_POST['language'] ?? 'en';
    $timezone = $_POST['timezone'] ?? 'America/New_York';
    $dashboard_layout = $_POST['dashboard_layout'] ?? 'default';
    $notification_sound = $_POST['notification_sound'] ?? 'default';
    $email_signature = $_POST['email_signature'] ?? '';
    
    // Widget preferences
    $widgets = isset($_POST['widgets']) ? $_POST['widgets'] : ['stats', 'recent_activity', 'tasks', 'messages'];
    
    // Build preferences JSON
    $pref_data = [
        'timezone' => $timezone,
        'dashboard_layout' => $dashboard_layout,
        'notification_sound' => $notification_sound,
        'email_signature' => $email_signature,
        'widgets' => $widgets,
        'quick_links' => $preferences_data['quick_links'] ?? []
    ];
    
    $json_prefs = json_encode($pref_data);
    
    $stmt = $conn->prepare("UPDATE user_preferences SET theme = ?, color_scheme = ?, language = ?, preferences_data = ? WHERE user_id = ?");
    $stmt->bind_param("ssssi", $theme, $color_scheme, $language, $json_prefs, $user_id);
    
    if ($stmt->execute()) {
        $success = "Preferences saved successfully!";
        $prefs = get_user_preferences($conn, $user_id);
        $preferences_data = json_decode($prefs['preferences_data'], true);
    } else {
        $error = "Failed to save preferences.";
    }
    $stmt->close();
}

// Avatar upload handling
if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === 0) {
    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
    $filename = $_FILES['avatar']['name'];
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    
    if (in_array($ext, $allowed)) {
        $new_filename = 'avatar_' . $user_id . '_' . time() . '.' . $ext;
        $upload_path = '../../uploads/profiles/' . $new_filename;
        
        if (move_uploaded_file($_FILES['avatar']['tmp_name'], $upload_path)) {
            $stmt = $conn->prepare("UPDATE users SET profile_image = ? WHERE user_id = ?");
            $stmt->bind_param("si", $new_filename, $user_id);
            $stmt->execute();
            $stmt->close();
            $success = "Avatar updated successfully!";
        }
    }
}

include '../../includes/header.php';
?>

<style>
.personalization-section {
    background: var(--bg-primary);
    border-radius: var(--border-radius);
    padding: 24px;
    margin-bottom: 24px;
    box-shadow: var(--shadow-sm);
}

.personalization-section h3 {
    margin-top: 0;
    margin-bottom: 20px;
    color: var(--text-primary);
    border-bottom: 2px solid var(--primary-color);
    padding-bottom: 8px;
}

.theme-selector {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 16px;
    margin-top: 16px;
}

.theme-option {
    border: 2px solid var(--border-color);
    border-radius: var(--border-radius);
    padding: 16px;
    text-align: center;
    cursor: pointer;
    transition: var(--transition-normal);
}

.theme-option:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

.theme-option.selected {
    border-color: var(--primary-color);
    background: rgba(74, 144, 226, 0.1);
}

.theme-preview {
    width: 100%;
    height: 80px;
    border-radius: 4px;
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
}

.color-scheme-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
    gap: 12px;
    margin-top: 16px;
}

.color-option {
    height: 60px;
    border-radius: var(--border-radius);
    cursor: pointer;
    border: 3px solid transparent;
    transition: var(--transition-fast);
    position: relative;
}

.color-option:hover {
    transform: scale(1.05);
}

.color-option.selected {
    border-color: var(--text-primary);
}

.color-option.selected::after {
    content: '✓';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    color: white;
    font-size: 24px;
    font-weight: bold;
    text-shadow: 0 0 4px rgba(0,0,0,0.5);
}

.widget-selector {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 12px;
    margin-top: 16px;
}

.widget-item {
    padding: 16px;
    border: 2px solid var(--border-color);
    border-radius: var(--border-radius);
    cursor: pointer;
    transition: var(--transition-fast);
    display: flex;
    align-items: center;
    gap: 12px;
}

.widget-item:hover {
    border-color: var(--primary-color);
}

.widget-item.selected {
    border-color: var(--primary-color);
    background: rgba(74, 144, 226, 0.1);
}

.widget-item input[type="checkbox"] {
    width: 20px;
    height: 20px;
}

.avatar-upload-area {
    display: flex;
    align-items: center;
    gap: 24px;
    margin-top: 16px;
}

.avatar-preview {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    border: 3px solid var(--border-color);
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--bg-secondary);
    font-size: 48px;
}

.avatar-preview img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.dashboard-layout-options {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
    margin-top: 16px;
}

.layout-option {
    border: 2px solid var(--border-color);
    border-radius: var(--border-radius);
    padding: 12px;
    cursor: pointer;
    transition: var(--transition-fast);
}

.layout-option:hover {
    border-color: var(--primary-color);
}

.layout-option.selected {
    border-color: var(--primary-color);
    background: rgba(74, 144, 226, 0.1);
}

.layout-preview {
    height: 100px;
    background: var(--bg-secondary);
    border-radius: 4px;
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 8px;
}

.layout-grid {
    display: grid;
    gap: 4px;
    width: 100%;
    height: 100%;
}

.layout-grid.default {
    grid-template-columns: 1fr 1fr;
    grid-template-rows: 1fr 1fr;
}

.layout-grid.sidebar {
    grid-template-columns: 1fr 2fr;
    grid-template-rows: 1fr 1fr;
}

.layout-grid.single {
    grid-template-columns: 1fr;
}

.layout-grid > div {
    background: var(--primary-color);
    opacity: 0.3;
    border-radius: 2px;
}
</style>

<div class="container">
    <h1>⚙️ Personalization Settings</h1>
    
    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <form method="POST" enctype="multipart/form-data">
        
        <!-- Avatar Customization -->
        <div class="personalization-section">
            <h3>👤 Profile Avatar</h3>
            <div class="avatar-upload-area">
                <div class="avatar-preview">
                    <?php if (!empty($_SESSION['profile_image'])): ?>
                        <img src="/nov10-crisis/uploads/profiles/<?php echo htmlspecialchars($_SESSION['profile_image']); ?>" alt="Avatar">
                    <?php else: ?>
                        <?php echo substr($_SESSION['full_name'], 0, 1); ?>
                    <?php endif; ?>
                </div>
                <div>
                    <label class="btn btn-primary">
                        Upload New Avatar
                        <input type="file" name="avatar" accept="image/*" style="display: none;" onchange="this.form.submit()">
                    </label>
                    <p class="text-muted mt-2">Recommended: 200x200px, max 2MB</p>
                </div>
            </div>
        </div>
        
        <!-- Theme Selection -->
        <div class="personalization-section">
            <h3>🎨 Theme</h3>
            <div class="theme-selector">
                <label class="theme-option <?php echo $prefs['theme'] === 'light' ? 'selected' : ''; ?>">
                    <input type="radio" name="theme" value="light" <?php echo $prefs['theme'] === 'light' ? 'checked' : ''; ?> style="display: none;">
                    <div class="theme-preview" style="background: #ffffff; color: #212529; border: 1px solid #dee2e6;">☀️</div>
                    <strong>Light</strong>
                </label>
                <label class="theme-option <?php echo $prefs['theme'] === 'dark' ? 'selected' : ''; ?>">
                    <input type="radio" name="theme" value="dark" <?php echo $prefs['theme'] === 'dark' ? 'checked' : ''; ?> style="display: none;">
                    <div class="theme-preview" style="background: #1a1a1a; color: #f8f9fa;">🌙</div>
                    <strong>Dark</strong>
                </label>
                <label class="theme-option <?php echo $prefs['theme'] === 'auto' ? 'selected' : ''; ?>">
                    <input type="radio" name="theme" value="auto" <?php echo $prefs['theme'] === 'auto' ? 'checked' : ''; ?> style="display: none;">
                    <div class="theme-preview" style="background: linear-gradient(to right, #ffffff 50%, #1a1a1a 50%); border: 1px solid #dee2e6;">🔄</div>
                    <strong>Auto</strong>
                </label>
            </div>
        </div>
        
        <!-- Color Scheme -->
        <div class="personalization-section">
            <h3>🎨 Color Scheme</h3>
            <div class="color-scheme-grid">
                <label class="color-option <?php echo $prefs['color_scheme'] === 'blue' ? 'selected' : ''; ?>" style="background: #4a90e2;">
                    <input type="radio" name="color_scheme" value="blue" <?php echo $prefs['color_scheme'] === 'blue' ? 'checked' : ''; ?> style="display: none;">
                </label>
                <label class="color-option <?php echo $prefs['color_scheme'] === 'purple' ? 'selected' : ''; ?>" style="background: #7b68ee;">
                    <input type="radio" name="color_scheme" value="purple" <?php echo $prefs['color_scheme'] === 'purple' ? 'checked' : ''; ?> style="display: none;">
                </label>
                <label class="color-option <?php echo $prefs['color_scheme'] === 'green' ? 'selected' : ''; ?>" style="background: #28a745;">
                    <input type="radio" name="color_scheme" value="green" <?php echo $prefs['color_scheme'] === 'green' ? 'checked' : ''; ?> style="display: none;">
                </label>
                <label class="color-option <?php echo $prefs['color_scheme'] === 'red' ? 'selected' : ''; ?>" style="background: #dc3545;">
                    <input type="radio" name="color_scheme" value="red" <?php echo $prefs['color_scheme'] === 'red' ? 'checked' : ''; ?> style="display: none;">
                </label>
                <label class="color-option <?php echo $prefs['color_scheme'] === 'orange' ? 'selected' : ''; ?>" style="background: #ff9800;">
                    <input type="radio" name="color_scheme" value="orange" <?php echo $prefs['color_scheme'] === 'orange' ? 'checked' : ''; ?> style="display: none;">
                </label>
                <label class="color-option <?php echo $prefs['color_scheme'] === 'teal' ? 'selected' : ''; ?>" style="background: #17a2b8;">
                    <input type="radio" name="color_scheme" value="teal" <?php echo $prefs['color_scheme'] === 'teal' ? 'checked' : ''; ?> style="display: none;">
                </label>
            </div>
        </div>
        
        <!-- Dashboard Layout -->
        <div class="personalization-section">
            <h3>📐 Dashboard Layout</h3>
            <div class="dashboard-layout-options">
                <label class="layout-option <?php echo ($preferences_data['dashboard_layout'] ?? 'default') === 'default' ? 'selected' : ''; ?>">
                    <input type="radio" name="dashboard_layout" value="default" <?php echo ($preferences_data['dashboard_layout'] ?? 'default') === 'default' ? 'checked' : ''; ?> style="display: none;">
                    <div class="layout-preview">
                        <div class="layout-grid default">
                            <div></div><div></div><div></div><div></div>
                        </div>
                    </div>
                    <strong>Grid (Default)</strong>
                </label>
                <label class="layout-option <?php echo ($preferences_data['dashboard_layout'] ?? 'default') === 'sidebar' ? 'selected' : ''; ?>">
                    <input type="radio" name="dashboard_layout" value="sidebar" <?php echo ($preferences_data['dashboard_layout'] ?? 'default') === 'sidebar' ? 'checked' : ''; ?> style="display: none;">
                    <div class="layout-preview">
                        <div class="layout-grid sidebar">
                            <div style="grid-row: 1/3;"></div><div></div><div></div>
                        </div>
                    </div>
                    <strong>Sidebar</strong>
                </label>
                <label class="layout-option <?php echo ($preferences_data['dashboard_layout'] ?? 'default') === 'single' ? 'selected' : ''; ?>">
                    <input type="radio" name="dashboard_layout" value="single" <?php echo ($preferences_data['dashboard_layout'] ?? 'default') === 'single' ? 'checked' : ''; ?> style="display: none;">
                    <div class="layout-preview">
                        <div class="layout-grid single">
                            <div></div>
                        </div>
                    </div>
                    <strong>Single Column</strong>
                </label>
            </div>
        </div>
        
        <!-- Widget Selection -->
        <div class="personalization-section">
            <h3>📊 Dashboard Widgets</h3>
            <p class="text-muted">Select which widgets to display on your dashboard</p>
            <div class="widget-selector">
                <?php
                $available_widgets = [
                    'stats' => ['icon' => '📊', 'label' => 'Statistics'],
                    'recent_activity' => ['icon' => '📝', 'label' => 'Recent Activity'],
                    'tasks' => ['icon' => '✅', 'label' => 'My Tasks'],
                    'messages' => ['icon' => '✉️', 'label' => 'Messages'],
                    'calendar' => ['icon' => '📅', 'label' => 'Calendar'],
                    'notifications' => ['icon' => '🔔', 'label' => 'Notifications'],
                    'news' => ['icon' => '📰', 'label' => 'News Feed'],
                    'resources' => ['icon' => '🛏️', 'label' => 'Resources']
                ];
                
                $selected_widgets = $preferences_data['widgets'] ?? ['stats', 'recent_activity', 'tasks', 'messages'];
                
                foreach ($available_widgets as $key => $widget):
                    $is_selected = in_array($key, $selected_widgets);
                ?>
                    <label class="widget-item <?php echo $is_selected ? 'selected' : ''; ?>">
                        <input type="checkbox" name="widgets[]" value="<?php echo $key; ?>" <?php echo $is_selected ? 'checked' : ''; ?>>
                        <span><?php echo $widget['icon']; ?> <?php echo $widget['label']; ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Language & Timezone -->
        <div class="personalization-section">
            <h3>🌍 Language & Timezone</h3>
            <div class="row">
                <div class="col-md-6">
                    <label>Language</label>
                    <select name="language" class="form-control">
                        <option value="en" <?php echo $prefs['language'] === 'en' ? 'selected' : ''; ?>>English</option>
                        <option value="es" <?php echo $prefs['language'] === 'es' ? 'selected' : ''; ?>>Español</option>
                        <option value="fr" <?php echo $prefs['language'] === 'fr' ? 'selected' : ''; ?>>Français</option>
                        <option value="de" <?php echo $prefs['language'] === 'de' ? 'selected' : ''; ?>>Deutsch</option>
                        <option value="zh" <?php echo $prefs['language'] === 'zh' ? 'selected' : ''; ?>>中文</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label>Timezone</label>
                    <select name="timezone" class="form-control">
                        <?php
                        $timezones = ['America/New_York', 'America/Chicago', 'America/Denver', 'America/Los_Angeles', 'America/Phoenix', 'America/Anchorage', 'Pacific/Honolulu', 'UTC'];
                        $current_tz = $preferences_data['timezone'] ?? 'America/New_York';
                        foreach ($timezones as $tz):
                        ?>
                            <option value="<?php echo $tz; ?>" <?php echo $current_tz === $tz ? 'selected' : ''; ?>><?php echo $tz; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
        
        <!-- Notification Sound -->
        <div class="personalization-section">
            <h3>🔔 Notification Sound</h3>
            <select name="notification_sound" class="form-control">
                <?php
                $sounds = ['default' => 'Default', 'chime' => 'Chime', 'bell' => 'Bell', 'ding' => 'Ding', 'none' => 'None'];
                $current_sound = $preferences_data['notification_sound'] ?? 'default';
                foreach ($sounds as $value => $label):
                ?>
                    <option value="<?php echo $value; ?>" <?php echo $current_sound === $value ? 'selected' : ''; ?>><?php echo $label; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <!-- Email Signature -->
        <div class="personalization-section">
            <h3>✉️ Email Signature</h3>
            <textarea name="email_signature" class="form-control" rows="4" placeholder="Your email signature..."><?php echo htmlspecialchars($preferences_data['email_signature'] ?? ''); ?></textarea>
            <p class="text-muted mt-2">This will be appended to your outgoing messages</p>
        </div>
        
        <div class="text-center mt-4">
            <button type="submit" class="btn btn-primary btn-lg">💾 Save All Preferences</button>
            <a href="/nov10-crisis/pages/common/profile.php" class="btn btn-light btn-lg">Cancel</a>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Theme selection handler
    document.querySelectorAll('.theme-option input').forEach(input => {
        input.addEventListener('change', function() {
            document.querySelectorAll('.theme-option').forEach(opt => opt.classList.remove('selected'));
            this.closest('.theme-option').classList.add('selected');
        });
    });
    
    // Color scheme selection handler
    document.querySelectorAll('.color-option input').forEach(input => {
        input.addEventListener('change', function() {
            document.querySelectorAll('.color-option').forEach(opt => opt.classList.remove('selected'));
            this.closest('.color-option').classList.add('selected');
        });
    });
    
    // Layout selection handler
    document.querySelectorAll('.layout-option input').forEach(input => {
        input.addEventListener('change', function() {
            document.querySelectorAll('.layout-option').forEach(opt => opt.classList.remove('selected'));
            this.closest('.layout-option').classList.add('selected');
        });
    });
    
    // Widget selection handler
    document.querySelectorAll('.widget-item input').forEach(input => {
        input.addEventListener('change', function() {
            if (this.checked) {
                this.closest('.widget-item').classList.add('selected');
            } else {
                this.closest('.widget-item').classList.remove('selected');
            }
        });
    });
});
</script>

<?php include '../../includes/footer.php'; ?>
