<?php
/**
 * Data Import/Export Tool
 * Import and export data in various formats
 */

require_once '../../includes/config.php';
require_once '../../includes/functions.php';

require_login();
require_role('admin');

$page_title = 'Data Import/Export';
$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Breadcrumb
$breadcrumb_items = [
    ['label' => 'Home', 'url' => '/nov10-crisis/'],
    ['label' => 'Admin', 'url' => '/nov10-crisis/pages/admin/dashboard.php'],
    ['label' => 'Data Import/Export', 'url' => '']
];

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['import_file'])) {
    $import_type = sanitize_input($_POST['import_type']);
    
    if ($_FILES['import_file']['error'] === 0) {
        $file_ext = strtolower(pathinfo($_FILES['import_file']['name'], PATHINFO_EXTENSION));
        
        if (in_array($file_ext, ['csv', 'json'])) {
            $upload_dir = '../../uploads/imports/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $filename = time() . '_' . basename($_FILES['import_file']['name']);
            $filepath = $upload_dir . $filename;
            
            if (move_uploaded_file($_FILES['import_file']['tmp_name'], $filepath)) {
                // Create import job
                $stmt = $conn->prepare("INSERT INTO import_jobs (user_id, import_type, file_name, file_path, status) VALUES (?, ?, ?, ?, 'pending')");
                $stmt->bind_param("isss", $user_id, $import_type, $filename, $filepath);
                $stmt->execute();
                $job_id = $stmt->insert_id;
                $stmt->close();
                
                // Process import immediately (in production, this would be a background job)
                $result = process_import($job_id, $filepath, $import_type, $file_ext);
                
                if ($result['success']) {
                    $success = "Import completed! Processed: {$result['processed']}, Failed: {$result['failed']}";
                } else {
                    $error = "Import failed: " . $result['error'];
                }
            } else {
                $error = "Failed to upload file";
            }
        } else {
            $error = "Invalid file type. Only CSV and JSON files are supported.";
        }
    } else {
        $error = "File upload error";
    }
}

// Process import function
function process_import($job_id, $filepath, $import_type, $file_ext) {
    global $conn;
    
    $processed = 0;
    $failed = 0;
    $errors = [];
    
    try {
        // Update job status
        $conn->query("UPDATE import_jobs SET status = 'processing', started_at = NOW() WHERE job_id = $job_id");
        
        if ($file_ext === 'csv') {
            $handle = fopen($filepath, 'r');
            $headers = fgetcsv($handle);
            
            while (($data = fgetcsv($handle)) !== FALSE) {
                $row = array_combine($headers, $data);
                
                // Process based on import type
                $result = import_row($import_type, $row);
                
                if ($result) {
                    $processed++;
                } else {
                    $failed++;
                    $errors[] = "Row error: " . json_encode($row);
                }
            }
            
            fclose($handle);
        } elseif ($file_ext === 'json') {
            $json_data = json_decode(file_get_contents($filepath), true);
            
            foreach ($json_data as $row) {
                $result = import_row($import_type, $row);
                
                if ($result) {
                    $processed++;
                } else {
                    $failed++;
                    $errors[] = "Row error: " . json_encode($row);
                }
            }
        }
        
        // Update job completion
        $error_log = implode("\n", array_slice($errors, 0, 100)); // Store first 100 errors
        $stmt = $conn->prepare("UPDATE import_jobs SET status = 'completed', processed_records = ?, failed_records = ?, error_log = ?, completed_at = NOW() WHERE job_id = ?");
        $stmt->bind_param("iisi", $processed, $failed, $error_log, $job_id);
        $stmt->execute();
        $stmt->close();
        
        return ['success' => true, 'processed' => $processed, 'failed' => $failed];
        
    } catch (Exception $e) {
        $conn->query("UPDATE import_jobs SET status = 'failed', error_log = '{$e->getMessage()}' WHERE job_id = $job_id");
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

// Import single row
function import_row($import_type, $row) {
    global $conn;
    
    try {
        switch ($import_type) {
            case 'users':
                if (empty($row['username']) || empty($row['email'])) {
                    return false;
                }
                
                // Check if user exists
                $stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ? OR email = ?");
                $stmt->bind_param("ss", $row['username'], $row['email']);
                $stmt->execute();
                $exists = $stmt->get_result()->num_rows > 0;
                $stmt->close();
                
                if (!$exists) {
                    $password_hash = password_hash($row['password'] ?? 'temppass123', PASSWORD_BCRYPT);
                    $stmt = $conn->prepare("INSERT INTO users (username, email, password_hash, full_name, role) VALUES (?, ?, ?, ?, ?)");
                    $role = $row['role'] ?? 'client';
                    $full_name = $row['full_name'] ?? $row['username'];
                    $stmt->bind_param("sssss", $row['username'], $row['email'], $password_hash, $full_name, $role);
                    return $stmt->execute();
                }
                return false;
                break;
                
            case 'tasks':
                if (empty($row['client_id']) || empty($row['title'])) {
                    return false;
                }
                
                $stmt = $conn->prepare("INSERT INTO tasks (client_id, title, description, status, priority) VALUES (?, ?, ?, ?, ?)");
                $status = $row['status'] ?? 'not_started';
                $priority = $row['priority'] ?? 'medium';
                $description = $row['description'] ?? '';
                $stmt->bind_param("issss", $row['client_id'], $row['title'], $description, $status, $priority);
                return $stmt->execute();
                break;
                
            default:
                return false;
        }
    } catch (Exception $e) {
        error_log("Import row error: " . $e->getMessage());
        return false;
    }
}

// Get import history
$stmt = $conn->prepare("SELECT * FROM import_jobs ORDER BY created_at DESC LIMIT 20");
$stmt->execute();
$import_jobs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

include '../../includes/header.php';
?>

<style>
.import-section {
    background: var(--bg-primary);
    border-radius: var(--border-radius);
    padding: 24px;
    margin-bottom: 24px;
    box-shadow: var(--shadow-sm);
}

.import-section h2 {
    margin-top: 0;
    border-bottom: 2px solid var(--primary-color);
    padding-bottom: 12px;
}

.job-card {
    background: var(--bg-secondary);
    border-left: 4px solid var(--primary-color);
    border-radius: var(--border-radius);
    padding: 20px;
    margin-bottom: 16px;
}

.job-card.completed {
    border-left-color: var(--success-color);
}

.job-card.failed {
    border-left-color: var(--danger-color);
}

.job-card.processing {
    border-left-color: var(--warning-color);
}

.job-meta {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 12px;
    margin-top: 12px;
    font-size: 14px;
}

.export-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
    margin-top: 16px;
}

.export-card {
    background: var(--bg-secondary);
    border: 2px solid var(--border-color);
    border-radius: var(--border-radius);
    padding: 20px;
    text-align: center;
    cursor: pointer;
    transition: var(--transition-fast);
}

.export-card:hover {
    border-color: var(--primary-color);
    transform: translateY(-2px);
}

.export-icon {
    font-size: 48px;
    margin-bottom: 12px;
}

.file-format-info {
    background: var(--bg-tertiary);
    padding: 16px;
    border-radius: 6px;
    margin-top: 16px;
}
</style>

<div class="container">
    <h1>📊 Data Import/Export</h1>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <!-- Import Section -->
    <div class="import-section">
        <h2>📥 Import Data</h2>
        
        <form method="POST" enctype="multipart/form-data">
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label>Import Type *</label>
                    <select name="import_type" class="form-control" required>
                        <option value="users">Users</option>
                        <option value="tasks">Tasks</option>
                        <option value="assessments">Assessments</option>
                        <option value="messages">Messages</option>
                    </select>
                </div>
                
                <div class="form-group col-md-6">
                    <label>File (CSV or JSON) *</label>
                    <input type="file" name="import_file" class="form-control" accept=".csv,.json" required>
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary">📤 Upload and Import</button>
        </form>
        
        <div class="file-format-info">
            <h4>📋 File Format Requirements</h4>
            <p><strong>CSV Format:</strong> First row should contain column headers matching database fields</p>
            <p><strong>JSON Format:</strong> Array of objects with keys matching database fields</p>
            <p><strong>Users:</strong> Required fields: username, email, full_name, role</p>
            <p><strong>Tasks:</strong> Required fields: client_id, title</p>
        </div>
    </div>
    
    <!-- Export Section -->
    <div class="import-section">
        <h2>📤 Export Data</h2>
        
        <div class="export-grid">
            <a href="/nov10-crisis/includes/ajax/export_items.php?table=users&type=csv" class="export-card">
                <div class="export-icon">👥</div>
                <strong>Export Users</strong>
                <p>CSV Format</p>
            </a>
            
            <a href="/nov10-crisis/includes/ajax/export_items.php?table=tasks&type=csv" class="export-card">
                <div class="export-icon">✅</div>
                <strong>Export Tasks</strong>
                <p>CSV Format</p>
            </a>
            
            <a href="/nov10-crisis/includes/ajax/export_items.php?table=messages&type=csv" class="export-card">
                <div class="export-icon">✉️</div>
                <strong>Export Messages</strong>
                <p>CSV Format</p>
            </a>
            
            <a href="/nov10-crisis/includes/ajax/export_items.php?table=assessments&type=csv" class="export-card">
                <div class="export-icon">📋</div>
                <strong>Export Assessments</strong>
                <p>CSV Format</p>
            </a>
        </div>
    </div>
    
    <!-- Import History -->
    <div class="import-section">
        <h2>📜 Import History</h2>
        
        <?php if (empty($import_jobs)): ?>
            <p>No import jobs yet.</p>
        <?php else: ?>
            <?php foreach ($import_jobs as $job): ?>
                <div class="job-card <?php echo $job['status']; ?>">
                    <div>
                        <strong><?php echo ucfirst($job['import_type']); ?> Import</strong>
                        <span class="badge badge-<?php 
                            echo $job['status'] === 'completed' ? 'success' : 
                                ($job['status'] === 'failed' ? 'danger' : 
                                ($job['status'] === 'processing' ? 'warning' : 'secondary')); 
                        ?>">
                            <?php echo ucfirst($job['status']); ?>
                        </span>
                    </div>
                    
                    <div class="job-meta">
                        <div><strong>File:</strong> <?php echo htmlspecialchars($job['file_name']); ?></div>
                        <div><strong>Started:</strong> <?php echo $job['started_at'] ? date('M d, g:i A', strtotime($job['started_at'])) : 'Not started'; ?></div>
                        <div><strong>Processed:</strong> <?php echo number_format($job['processed_records']); ?></div>
                        <div><strong>Failed:</strong> <?php echo number_format($job['failed_records']); ?></div>
                    </div>
                    
                    <?php if (!empty($job['error_log']) && $job['status'] === 'failed'): ?>
                        <div class="mt-2">
                            <strong>Error:</strong> <code><?php echo htmlspecialchars($job['error_log']); ?></code>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
