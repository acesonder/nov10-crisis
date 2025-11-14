-- Schema updates for Workflow Automation
-- Create automation rules table

CREATE TABLE IF NOT EXISTS automation_rules (
    rule_id INT PRIMARY KEY AUTO_INCREMENT,
    rule_name VARCHAR(255) NOT NULL,
    rule_type ENUM('assignment', 'notification', 'status', 'task', 'reminder', 'routing') NOT NULL,
    trigger_event VARCHAR(100) NOT NULL,
    conditions JSON,
    actions JSON,
    is_active BOOLEAN DEFAULT TRUE,
    execution_count INT DEFAULT 0,
    last_executed TIMESTAMP NULL,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_trigger (trigger_event, is_active),
    INDEX idx_type (rule_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Automation execution log
CREATE TABLE IF NOT EXISTS automation_log (
    log_id INT PRIMARY KEY AUTO_INCREMENT,
    rule_id INT NOT NULL,
    execution_status ENUM('success', 'failure', 'skipped') NOT NULL,
    trigger_data JSON,
    result_data JSON,
    error_message TEXT,
    executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (rule_id) REFERENCES automation_rules(rule_id) ON DELETE CASCADE,
    INDEX idx_executed (executed_at),
    INDEX idx_status (execution_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Scheduled jobs table
CREATE TABLE IF NOT EXISTS scheduled_jobs (
    job_id INT PRIMARY KEY AUTO_INCREMENT,
    job_name VARCHAR(255) NOT NULL,
    job_type ENUM('task_creation', 'reminder', 'report', 'backup', 'cleanup', 'email', 'notification') NOT NULL,
    schedule_pattern VARCHAR(100) NOT NULL COMMENT 'Cron format or simple schedule',
    job_data JSON,
    is_active BOOLEAN DEFAULT TRUE,
    last_run TIMESTAMP NULL,
    next_run TIMESTAMP NULL,
    run_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_next_run (next_run, is_active),
    INDEX idx_type (job_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- System settings for admin tools
CREATE TABLE IF NOT EXISTS system_settings (
    setting_id INT PRIMARY KEY AUTO_INCREMENT,
    category VARCHAR(50) NOT NULL,
    setting_key VARCHAR(100) NOT NULL,
    setting_value TEXT,
    setting_type ENUM('string', 'number', 'boolean', 'json') DEFAULT 'string',
    is_public BOOLEAN DEFAULT FALSE,
    updated_by INT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_setting (category, setting_key),
    FOREIGN KEY (updated_by) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default system settings
INSERT INTO system_settings (category, setting_key, setting_value, setting_type, is_public) VALUES
('branding', 'site_name', 'Crisis Management System', 'string', TRUE),
('branding', 'logo_url', '', 'string', TRUE),
('branding', 'primary_color', '#4a90e2', 'string', TRUE),
('branding', 'secondary_color', '#7b68ee', 'string', TRUE),
('features', 'enable_messaging', '1', 'boolean', FALSE),
('features', 'enable_assessments', '1', 'boolean', FALSE),
('features', 'enable_resources', '1', 'boolean', FALSE),
('features', 'enable_automation', '1', 'boolean', FALSE),
('notifications', 'email_enabled', '0', 'boolean', FALSE),
('notifications', 'sms_enabled', '0', 'boolean', FALSE),
('security', 'session_timeout', '3600', 'number', FALSE),
('security', 'max_login_attempts', '5', 'number', FALSE),
('limits', 'max_file_upload_mb', '10', 'number', FALSE),
('limits', 'max_attachments_per_message', '5', 'number', FALSE)
ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value);
