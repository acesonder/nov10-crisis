<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unauthorized Access - Crisis Management System</title>
    <link rel="stylesheet" href="/nov10-crisis/assets/css/main.css">
    <style>
        .error-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 2rem;
        }
        
        .error-content h1 {
            font-size: 6rem;
            color: var(--danger-color);
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-content">
            <h1>403</h1>
            <h2>Unauthorized Access</h2>
            <p style="color: var(--text-secondary); margin: 2rem 0;">You don't have permission to access this page.</p>
            <div>
                <a href="/nov10-crisis/index.php" class="btn btn-primary">Go Home</a>
                <a href="javascript:history.back()" class="btn btn-secondary">Go Back</a>
            </div>
        </div>
    </div>
</body>
</html>
