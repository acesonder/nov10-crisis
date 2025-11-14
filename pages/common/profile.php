<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

require_login();

$page_title = 'My Profile';
$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Get user data
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Get user preferences
$preferences = get_user_preferences($conn, $user_id);

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $full_name = sanitize_input($_POST['full_name']);
    $email = sanitize_input($_POST['email']);
    $phone = sanitize_input($_POST['phone'] ?? '');
    $address = sanitize_input($_POST['address'] ?? '');
    
    if (empty($full_name) || empty($email)) {
        $error = 'Name and email are required';
    } elseif (!validate_email($email)) {
        $error = 'Invalid email address';
    } else {
        $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, address = ? WHERE user_id = ?");
        $stmt->bind_param("ssssi", $full_name, $email, $phone, $address, $user_id);
        
        if ($stmt->execute()) {
            $stmt->close();
            $_SESSION['full_name'] = $full_name;
            log_activity($conn, $user_id, 'profile_updated');
            $success = 'Profile updated successfully!';
            
            // Refresh user data
            $stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
            $stmt->close();
        } else {
            $error = 'Failed to update profile';
        }
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = 'All password fields are required';
    } elseif (!verify_password($current_password, $user['password_hash'])) {
        $error = 'Current password is incorrect';
    } elseif (strlen($new_password) < 6) {
        $error = 'New password must be at least 6 characters';
    } elseif ($new_password !== $confirm_password) {
        $error = 'New passwords do not match';
    } else {
        $new_hash = hash_password($new_password);
        $stmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?");
        $stmt->bind_param("si", $new_hash, $user_id);
        
        if ($stmt->execute()) {
            $stmt->close();
            log_activity($conn, $user_id, 'password_changed');
            $success = 'Password changed successfully!';
        } else {
            $error = 'Failed to change password';
        }
    }
}

// Handle preferences update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_preferences'])) {
    $theme = sanitize_input($_POST['theme']);
    $notifications_enabled = isset($_POST['notifications_enabled']) ? 1 : 0;
    $email_notifications = isset($_POST['email_notifications']) ? 1 : 0;
    
    $stmt = $conn->prepare("UPDATE user_preferences SET theme = ?, notifications_enabled = ?, email_notifications = ? WHERE user_id = ?");
    $stmt->bind_param("siii", $theme, $notifications_enabled, $email_notifications, $user_id);
    
    if ($stmt->execute()) {
        $stmt->close();
        $success = 'Preferences updated successfully!';
        $preferences = get_user_preferences($conn, $user_id);
    } else {
        $error = 'Failed to update preferences';
    }
}

include '../../includes/header.php';
?>

<div class="container" style="padding: 2rem 0;">
    <div class="mb-4">
        <h1>My Profile 👤</h1>
        <p style="color: var(--text-secondary);">Manage your account settings and preferences</p>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <!-- Profile Information -->
    <div class="card mb-4">
        <div class="card-header">
            <h3 class="card-title">Profile Information</h3>
        </div>
        <div class="card-body">
            <form method="POST" action="">
                <div class="row">
                    <div class="col-12 col-md-6">
                        <div class="form-group">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                            <small class="form-text">Username cannot be changed</small>
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="form-group">
                            <label class="form-label">Role</label>
                            <input type="text" class="form-control" value="<?php echo ucfirst($user['role']); ?>" disabled>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Full Name *</label>
                    <input type="text" name="full_name" class="form-control" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Email Address *</label>
                    <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Phone Number</label>
                    <input type="tel" name="phone" class="form-control" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Address</label>
                    <textarea name="address" class="form-control" rows="3"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                </div>
                
                <button type="submit" name="update_profile" class="btn btn-primary">Update Profile</button>
            </form>
        </div>
    </div>
    
    <!-- Change Password -->
    <div class="card mb-4">
        <div class="card-header">
            <h3 class="card-title">Change Password</h3>
        </div>
        <div class="card-body">
            <form method="POST" action="">
                <div class="form-group">
                    <label class="form-label">Current Password</label>
                    <input type="password" name="current_password" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">New Password (min. 6 characters)</label>
                    <input type="password" name="new_password" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Confirm New Password</label>
                    <input type="password" name="confirm_password" class="form-control" required>
                </div>
                
                <button type="submit" name="change_password" class="btn btn-warning">Change Password</button>
            </form>
        </div>
    </div>
    
    <!-- Preferences -->
    <div class="card mb-4">
        <div class="card-header">
            <h3 class="card-title">Preferences</h3>
        </div>
        <div class="card-body">
            <form method="POST" action="">
                <div class="form-group">
                    <label class="form-label">Theme</label>
                    <select name="theme" class="form-control" onchange="applyTheme(this.value)">
                        <option value="light" <?php echo $preferences['theme'] === 'light' ? 'selected' : ''; ?>>Light</option>
                        <option value="dark" <?php echo $preferences['theme'] === 'dark' ? 'selected' : ''; ?>>Dark</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <div class="form-check">
                        <input type="checkbox" name="notifications_enabled" class="form-check-input" id="notif-enabled" <?php echo $preferences['notifications_enabled'] ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="notif-enabled">Enable notifications</label>
                    </div>
                </div>
                
                <div class="form-group">
                    <div class="form-check">
                        <input type="checkbox" name="email_notifications" class="form-check-input" id="email-notif" <?php echo $preferences['email_notifications'] ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="email-notif">Email notifications</label>
                    </div>
                </div>
                
                <button type="submit" name="update_preferences" class="btn btn-primary">Update Preferences</button>
            </form>
        </div>
    </div>
    
    <!-- Account Information -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Account Information</h3>
        </div>
        <div class="card-body">
            <p><strong>Account Status:</strong> <?php echo get_status_badge($user['status']); ?></p>
            <p><strong>Member Since:</strong> <?php echo format_date($user['created_at']); ?></p>
            <p><strong>Last Login:</strong> <?php echo $user['last_login'] ? format_datetime($user['last_login']) : 'N/A'; ?></p>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
