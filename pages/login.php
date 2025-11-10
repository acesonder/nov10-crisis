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
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password';
    } else {
        $stmt = $conn->prepare("SELECT user_id, username, password_hash, full_name, role, status FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($user = $result->fetch_assoc()) {
            if ($user['status'] !== 'active') {
                $error = 'Your account is inactive. Please contact support.';
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
                
                // Log activity
                log_activity($conn, $user['user_id'], 'login');
                
                // Redirect based on role
                header("Location: /nov10-crisis/pages/{$user['role']}/dashboard.php");
                exit;
            } else {
                $error = 'Invalid username or password';
            }
        } else {
            $error = 'Invalid username or password';
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
    <title>Login - Crisis Management System</title>
    <link rel="stylesheet" href="/nov10-crisis/assets/css/main.css">
    <style>
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #4a90e2, #7b68ee);
            padding: 2rem;
        }
        
        .login-card {
            background: var(--bg-primary);
            padding: 3rem;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-lg);
            max-width: 450px;
            width: 100%;
            animation: zoomIn 0.5s ease;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .login-header h1 {
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }
        
        .login-links {
            text-align: center;
            margin-top: 1.5rem;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h1>Welcome Back</h1>
                <p style="color: var(--text-secondary);">Sign in to your account</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="username" class="form-label">Username or Email</label>
                    <input type="text" id="username" name="username" class="form-control" required autofocus>
                </div>
                
                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" id="password" name="password" class="form-control" required>
                </div>
                
                <button type="submit" class="btn btn-primary w-100 btn-lg">Sign In</button>
            </form>
            
            <div class="login-links">
                <p>Don't have an account? <a href="/nov10-crisis/pages/register.php">Register here</a></p>
                <p><a href="/nov10-crisis/index.php">← Back to Home</a></p>
            </div>
        </div>
    </div>
    
    <script src="/nov10-crisis/assets/js/main.js"></script>
</body>
</html>
