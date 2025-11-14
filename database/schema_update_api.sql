-- Schema updates for RESTful API System

-- API keys table
CREATE TABLE IF NOT EXISTS api_keys (
    key_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    api_key VARCHAR(64) UNIQUE NOT NULL,
    key_name VARCHAR(255) NOT NULL,
    permissions JSON,
    is_active BOOLEAN DEFAULT TRUE,
    request_count INT DEFAULT 0,
    last_used_at TIMESTAMP NULL,
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_api_key (api_key),
    INDEX idx_user (user_id),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- API request log table
CREATE TABLE IF NOT EXISTS api_requests (
    request_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    endpoint VARCHAR(255) NOT NULL,
    method VARCHAR(10) NOT NULL,
    status_code INT NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_created (created_at),
    INDEX idx_endpoint (endpoint)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Webhooks table
CREATE TABLE IF NOT EXISTS webhooks (
    webhook_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    event_type VARCHAR(100) NOT NULL COMMENT 'task_created, message_sent, assessment_completed, etc.',
    target_url VARCHAR(500) NOT NULL,
    secret_key VARCHAR(64),
    is_active BOOLEAN DEFAULT TRUE,
    retry_count INT DEFAULT 3,
    last_triggered TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_event (event_type, is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Webhook delivery log
CREATE TABLE IF NOT EXISTS webhook_deliveries (
    delivery_id INT PRIMARY KEY AUTO_INCREMENT,
    webhook_id INT NOT NULL,
    event_data JSON,
    response_code INT,
    response_body TEXT,
    delivered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (webhook_id) REFERENCES webhooks(webhook_id) ON DELETE CASCADE,
    INDEX idx_webhook (webhook_id),
    INDEX idx_delivered (delivered_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data import jobs table
CREATE TABLE IF NOT EXISTS import_jobs (
    job_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    import_type ENUM('users', 'tasks', 'assessments', 'messages', 'other') NOT NULL,
    file_name VARCHAR(255),
    file_path VARCHAR(500),
    status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
    total_records INT DEFAULT 0,
    processed_records INT DEFAULT 0,
    failed_records INT DEFAULT 0,
    error_log TEXT,
    started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_status (status),
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add API settings to system_settings
INSERT INTO system_settings (category, setting_key, setting_value, setting_type, is_public) VALUES
('api', 'enabled', '1', 'boolean', FALSE),
('api', 'rate_limit_per_hour', '1000', 'number', FALSE),
('api', 'require_api_key', '1', 'boolean', FALSE),
('api', 'allow_cors', '1', 'boolean', FALSE),
('webhooks', 'enabled', '1', 'boolean', FALSE),
('webhooks', 'max_retries', '3', 'number', FALSE),
('webhooks', 'timeout_seconds', '30', 'number', FALSE)
ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value);
