<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

init_session();

// Redirect if already logged in
if (is_logged_in()) {
    $role = $_SESSION['role'];
    header("Location: /nov10-crisis/pages/$role/dashboard.php");
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize_input($_POST['username'] ?? '');
    $email = sanitize_input($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $full_name = sanitize_input($_POST['full_name'] ?? '');
    $phone = sanitize_input($_POST['phone'] ?? '');
    $role = sanitize_input($_POST['role'] ?? 'client');
    
    // Validation
    if (empty($username) || empty($email) || empty($password) || empty($full_name)) {
        $error = 'Please fill in all required fields';
    } elseif (!validate_email($email)) {
        $error = 'Please enter a valid email address';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } else {
        // Check if username exists
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $error = 'Username already exists';
            $stmt->close();
        } else {
            $stmt->close();
            
            // Check if email exists
            $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $error = 'Email already registered';
                $stmt->close();
            } else {
                $stmt->close();
                
                // Create user
                $password_hash = hash_password($password);
                $stmt = $conn->prepare("INSERT INTO users (username, email, password_hash, full_name, phone, role, status) VALUES (?, ?, ?, ?, ?, ?, 'active')");
                $stmt->bind_param("ssssss", $username, $email, $password_hash, $full_name, $phone, $role);
                
                if ($stmt->execute()) {
                    $user_id = $stmt->insert_id;
                    $stmt->close();
                    
                    // Create default preferences
                    $pref_stmt = $conn->prepare("INSERT INTO user_preferences (user_id) VALUES (?)");
                    $pref_stmt->bind_param("i", $user_id);
                    $pref_stmt->execute();
                    $pref_stmt->close();
                    
                    // Log activity
                    log_activity($conn, $user_id, 'registration');
                    
                    // Auto-login
                    $_SESSION['user_id'] = $user_id;
                    $_SESSION['username'] = $username;
                    $_SESSION['full_name'] = $full_name;
                    $_SESSION['role'] = $role;
                    
                    header("Location: /nov10-crisis/pages/$role/dashboard.php");
                    exit;
                } else {
                    $error = 'Registration failed. Please try again.';
                    $stmt->close();
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Crisis Management System</title>
    <link rel="stylesheet" href="/nov10-crisis/assets/css/main.css">
    <style>
        .register-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #4a90e2, #7b68ee);
            padding: 2rem;
        }
        
        .register-card {
            background: var(--bg-primary);
            padding: 3rem;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-lg);
            max-width: 550px;
            width: 100%;
            animation: zoomIn 0.5s ease;
        }
        
        .register-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .register-header h1 {
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-card">
            <div class="register-header">
                <h1>Create Account</h1>
                <p style="color: var(--text-secondary);">Join our crisis management system</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="full_name" class="form-label">Full Name *</label>
                    <input type="text" id="full_name" name="full_name" class="form-control" required value="<?php echo $_POST['full_name'] ?? ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="username" class="form-label">Username *</label>
                    <input type="text" id="username" name="username" class="form-control" required value="<?php echo $_POST['username'] ?? ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="email" class="form-label">Email Address *</label>
                    <input type="email" id="email" name="email" class="form-control" required value="<?php echo $_POST['email'] ?? ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="phone" class="form-label">Phone Number</label>
                    <input type="tel" id="phone" name="phone" class="form-control" value="<?php echo $_POST['phone'] ?? ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="role" class="form-label">I am registering as *</label>
                    <select id="role" name="role" class="form-control" required>
                        <option value="client" <?php echo ($_POST['role'] ?? 'client') === 'client' ? 'selected' : ''; ?>>Client (seeking services)</option>
                        <option value="service_provider" <?php echo ($_POST['role'] ?? '') === 'service_provider' ? 'selected' : ''; ?>>Service Provider</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="password" class="form-label">Password * (min. 6 characters)</label>
                    <input type="password" id="password" name="password" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password" class="form-label">Confirm Password *</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                </div>
                
                <button type="submit" class="btn btn-primary w-100 btn-lg">Create Account</button>
            </form>
            
            <div class="text-center mt-3">
                <p>Already have an account? <a href="/nov10-crisis/pages/login.php">Sign in here</a></p>
                <p><a href="/nov10-crisis/index.php">← Back to Home</a></p>
            </div>
        </div>
    </div>
    
    <script src="/nov10-crisis/assets/js/main.js"></script>
</body>
</html>
