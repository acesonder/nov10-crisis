-- Schema updates for Enhanced Messaging System
-- Add new columns to messages table

ALTER TABLE messages 
ADD COLUMN IF NOT EXISTS priority ENUM('normal', 'high', 'urgent') DEFAULT 'normal' AFTER message_body,
ADD COLUMN IF NOT EXISTS category VARCHAR(50) DEFAULT 'general' AFTER priority,
ADD COLUMN IF NOT EXISTS attachments JSON AFTER category,
ADD COLUMN IF NOT EXISTS is_broadcast BOOLEAN DEFAULT FALSE AFTER attachments;

-- Add indexes for new columns
ALTER TABLE messages 
ADD INDEX IF NOT EXISTS idx_priority (priority),
ADD INDEX IF NOT EXISTS idx_category (category);

-- Message templates table (optional - for storing user-created templates)
CREATE TABLE IF NOT EXISTS message_templates (
    template_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    subject VARCHAR(255),
    body TEXT NOT NULL,
    category VARCHAR(50) DEFAULT 'general',
    is_shared BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Scheduled messages table
CREATE TABLE IF NOT EXISTS scheduled_messages (
    scheduled_id INT PRIMARY KEY AUTO_INCREMENT,
    sender_id INT NOT NULL,
    recipient_id INT NOT NULL,
    subject VARCHAR(255),
    message_body TEXT NOT NULL,
    priority ENUM('normal', 'high', 'urgent') DEFAULT 'normal',
    category VARCHAR(50) DEFAULT 'general',
    scheduled_for DATETIME NOT NULL,
    sent BOOLEAN DEFAULT FALSE,
    sent_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (recipient_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_scheduled (scheduled_for, sent)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
