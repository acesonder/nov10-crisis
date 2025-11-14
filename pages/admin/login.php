<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

init_session();

// Redirect if already logged in as admin
if (is_logged_in() && $_SESSION['role'] === 'admin') {
    header("Location: /nov10-crisis/pages/admin/dashboard.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $passcode = sanitize_input($_POST['passcode'] ?? '');
    $username = sanitize_input($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($passcode) || empty($username) || empty($password)) {
        $error = 'Please fill in all fields';
    } elseif ($passcode !== ADMIN_PASSCODE) {
        $error = 'Invalid admin passcode';
        log_activity($conn, null, 'failed_admin_login', null, null, "Invalid passcode attempt");
    } else {
        $stmt = $conn->prepare("SELECT user_id, username, password_hash, full_name, role, status FROM users WHERE username = ? AND role = 'admin'");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($user = $result->fetch_assoc()) {
            if ($user['status'] !== 'active') {
                $error = 'Your account is inactive';
            } elseif (verify_password($password, $user['password_hash'])) {
                // Login successful
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role'] = $user['role'];
                
                // Update last login
                $update_stmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
                $update_stmt->bind_param("i", $user['user_id']);
                $update_stmt->execute();
                $update_stmt->close();
                
                log_activity($conn, $user['user_id'], 'admin_login');
                
                header("Location: /nov10-crisis/pages/admin/dashboard.php");
                exit;
            } else {
                $error = 'Invalid credentials';
            }
        } else {
            $error = 'Invalid credentials or not an admin account';
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Crisis Management System</title>
    <link rel="stylesheet" href="/nov10-crisis/assets/css/main.css">
    <style>
        .admin-login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #dc3545, #343a40);
            padding: 2rem;
        }
        
        .admin-login-card {
            background: var(--bg-primary);
            padding: 3rem;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-lg);
            max-width: 450px;
            width: 100%;
            border-top: 4px solid var(--danger-color);
        }
        
        .admin-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .admin-header h1 {
            color: var(--danger-color);
            margin-bottom: 0.5rem;
        }
        
        .admin-badge {
            background: var(--danger-color);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            display: inline-block;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="admin-login-container">
        <div class="admin-login-card">
            <div class="admin-header">
                <span class="admin-badge">🔒 ADMIN ACCESS</span>
                <h1>Administrator Login</h1>
                <p style="color: var(--text-secondary);">Secure access required</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="passcode" class="form-label">Admin Passcode</label>
                    <input type="password" id="passcode" name="passcode" class="form-control" required autofocus>
                    <small class="form-text">Enter the 5-digit admin passcode</small>
                </div>
                
                <div class="form-group">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" id="username" name="username" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" id="password" name="password" class="form-control" required>
                </div>
                
                <button type="submit" class="btn btn-danger w-100 btn-lg">Admin Sign In</button>
            </form>
            
            <div class="text-center mt-3">
                <p><a href="/nov10-crisis/index.php">← Back to Home</a></p>
            </div>
        </div>
    </div>
    
    <script src="/nov10-crisis/assets/js/main.js"></script>
</body>
</html>
