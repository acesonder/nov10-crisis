-- Schema updates for Custom Form Builder

-- Custom forms table
CREATE TABLE IF NOT EXISTS custom_forms (
    form_id INT PRIMARY KEY AUTO_INCREMENT,
    form_name VARCHAR(255) NOT NULL,
    form_description TEXT,
    form_type ENUM('assessment', 'survey', 'intake', 'feedback', 'other') NOT NULL,
    fields JSON NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_type (form_type),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Form submissions table
CREATE TABLE IF NOT EXISTS form_submissions (
    submission_id INT PRIMARY KEY AUTO_INCREMENT,
    form_id INT NOT NULL,
    user_id INT NOT NULL,
    submission_data JSON NOT NULL,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (form_id) REFERENCES custom_forms(form_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_form (form_id),
    INDEX idx_user (user_id),
    INDEX idx_submitted (submitted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Permission roles table (for permission matrix)
CREATE TABLE IF NOT EXISTS permission_roles (
    role_name VARCHAR(50) PRIMARY KEY,
    display_name VARCHAR(100) NOT NULL,
    description TEXT,
    is_system_role BOOLEAN DEFAULT TRUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Permissions table
CREATE TABLE IF NOT EXISTS permissions (
    permission_id INT PRIMARY KEY AUTO_INCREMENT,
    permission_key VARCHAR(100) UNIQUE NOT NULL,
    permission_name VARCHAR(255) NOT NULL,
    category VARCHAR(50) NOT NULL,
    description TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Role permissions junction table
CREATE TABLE IF NOT EXISTS role_permissions (
    role_name VARCHAR(50) NOT NULL,
    permission_id INT NOT NULL,
    PRIMARY KEY (role_name, permission_id),
    FOREIGN KEY (role_name) REFERENCES permission_roles(role_name) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(permission_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default permission roles
INSERT INTO permission_roles (role_name, display_name, description, is_system_role) VALUES
('admin', 'Administrator', 'Full system access', TRUE),
('staff', 'Staff Member', 'Case management and support', TRUE),
('client', 'Client', 'Service recipient', TRUE),
('provider', 'Service Provider', 'External service provider', TRUE)
ON DUPLICATE KEY UPDATE display_name=VALUES(display_name);

-- Insert default permissions
INSERT INTO permissions (permission_key, permission_name, category, description) VALUES
('view_dashboard', 'View Dashboard', 'Dashboard', 'Access to dashboard'),
('view_users', 'View Users', 'Users', 'View user list and profiles'),
('create_users', 'Create Users', 'Users', 'Create new users'),
('edit_users', 'Edit Users', 'Users', 'Edit user information'),
('delete_users', 'Delete Users', 'Users', 'Delete users'),
('view_tasks', 'View Tasks', 'Tasks', 'View tasks'),
('create_tasks', 'Create Tasks', 'Tasks', 'Create new tasks'),
('edit_tasks', 'Edit Tasks', 'Tasks', 'Edit existing tasks'),
('delete_tasks', 'Delete Tasks', 'Tasks', 'Delete tasks'),
('view_messages', 'View Messages', 'Messages', 'View messages'),
('send_messages', 'Send Messages', 'Messages', 'Send messages'),
('view_assessments', 'View Assessments', 'Assessments', 'View assessments'),
('create_assessments', 'Create Assessments', 'Assessments', 'Create assessments'),
('view_referrals', 'View Referrals', 'Referrals', 'View referrals'),
('create_referrals', 'Create Referrals', 'Referrals', 'Create referrals'),
('manage_resources', 'Manage Resources', 'Resources', 'Manage beds, showers, laundry'),
('view_reports', 'View Reports', 'Reports', 'View system reports'),
('system_settings', 'System Settings', 'Admin', 'Modify system settings'),
('api_access', 'API Access', 'Admin', 'Access to API'),
('manage_automation', 'Manage Automation', 'Admin', 'Configure automation rules')
ON DUPLICATE KEY UPDATE permission_name=VALUES(permission_name);

-- Assign default permissions to roles
INSERT IGNORE INTO role_permissions (role_name, permission_id)
SELECT 'admin', permission_id FROM permissions;

INSERT IGNORE INTO role_permissions (role_name, permission_id)
SELECT 'staff', permission_id FROM permissions 
WHERE permission_key IN ('view_dashboard', 'view_users', 'view_tasks', 'create_tasks', 'edit_tasks', 
                          'view_messages', 'send_messages', 'view_assessments', 'create_assessments', 
                          'view_referrals', 'create_referrals', 'manage_resources');

INSERT IGNORE INTO role_permissions (role_name, permission_id)
SELECT 'client', permission_id FROM permissions 
WHERE permission_key IN ('view_dashboard', 'view_tasks', 'view_messages', 'send_messages', 
                          'view_assessments', 'view_referrals');

INSERT IGNORE INTO role_permissions (role_name, permission_id)
SELECT 'provider', permission_id FROM permissions 
WHERE permission_key IN ('view_dashboard', 'view_messages', 'send_messages', 
                          'view_referrals', 'view_reports');
