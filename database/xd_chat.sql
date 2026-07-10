/*
==================================================
Project : XD Chat
Version : 2.0.0
File    : xd_chat.sql
Module  : Database Structure
Status  : Development
Author  : Umesh + ChatGPT
Created : 05 July 2026
==================================================
*/

CREATE DATABASE IF NOT EXISTS xd_chat
CHARACTER SET utf8mb4
COLLATE utf8mb4_general_ci;

USE xd_chat;


/* ==================================================
   01. USERS
================================================== */

CREATE TABLE users (

    id INT AUTO_INCREMENT PRIMARY KEY,

    full_name VARCHAR(100) NOT NULL,

    email VARCHAR(150) UNIQUE NOT NULL,

    password VARCHAR(255) NOT NULL,

    role ENUM(
        'super_admin',
        'admin',
        'agent'
    ) DEFAULT 'admin',

    status ENUM(
        'active',
        'inactive'
    ) DEFAULT 'active',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ON UPDATE CURRENT_TIMESTAMP

);


/* ==================================================
   02. WEBSITES
================================================== */

CREATE TABLE websites (

    id INT AUTO_INCREMENT PRIMARY KEY,

    user_id INT NOT NULL,

    website_name VARCHAR(100),

    domain VARCHAR(255),

    widget_key VARCHAR(100) UNIQUE,

    status ENUM(
        'active',
        'inactive'
    ) DEFAULT 'active',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX user_id (user_id),

    FOREIGN KEY (user_id)
    REFERENCES users(id)
    ON DELETE CASCADE

);


/* ==================================================
   03. WIDGETS
================================================== */

CREATE TABLE widgets (

    id INT AUTO_INCREMENT PRIMARY KEY,

    user_id INT NOT NULL,

    website_id INT NOT NULL,

    widget_name VARCHAR(150) NOT NULL,

    widget_key VARCHAR(100) UNIQUE NOT NULL,

    theme VARCHAR(50) NOT NULL DEFAULT 'light',

    position VARCHAR(50) NOT NULL DEFAULT 'bottom-right',

    widget_color VARCHAR(20) DEFAULT '#2563eb',

    widget_icon VARCHAR(50) DEFAULT 'chat',

    welcome_message TEXT,

    offline_message TEXT,

    status VARCHAR(20) NOT NULL DEFAULT 'active',

    display_order INT DEFAULT 1,

    is_default TINYINT(1) DEFAULT 0,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ON UPDATE CURRENT_TIMESTAMP,

    INDEX user_id (user_id),

    INDEX website_id (website_id),

    INDEX widget_key_2 (widget_key)


);


/* ==================================================
   04. CHATS
================================================== */

CREATE TABLE chats (

    id BIGINT AUTO_INCREMENT PRIMARY KEY,

    website_id INT NOT NULL,

    visitor_id VARCHAR(100),

    visitor_name VARCHAR(100),

    visitor_email VARCHAR(150),

    visitor_page_url TEXT,

    visitor_referrer TEXT,

    visitor_browser TEXT,

    visitor_device VARCHAR(100),

    status ENUM(
        'open',
        'closed'
    ) DEFAULT 'open',

    last_seen_message_id BIGINT DEFAULT 0,

    closed_at TIMESTAMP NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX website_id (website_id),

    INDEX idx_chats_status (status),

    FOREIGN KEY (website_id)
    REFERENCES websites(id)
    ON DELETE CASCADE

);


/* ==================================================
   05. MESSAGES
================================================== */

CREATE TABLE messages (

    id BIGINT AUTO_INCREMENT PRIMARY KEY,

    chat_id BIGINT NOT NULL,

    reply_to_message_id BIGINT DEFAULT NULL,

    is_deleted TINYINT(1) NOT NULL DEFAULT 0,

    deleted_at DATETIME DEFAULT NULL,

    deleted_by ENUM(
        'visitor',
        'agent',
        'system'
    ) DEFAULT NULL,

    sender ENUM(
        'visitor',
        'agent',
        'bot'
    ) NOT NULL,

    message_type ENUM(
        'text',
        'image',
        'file',
        'audio',
        'video',
        'system'
    ) NOT NULL DEFAULT 'text',

    message TEXT,

    file_name VARCHAR(255) DEFAULT NULL,

    file_path VARCHAR(1000) DEFAULT NULL,

    file_mime VARCHAR(150) DEFAULT NULL,

    file_size BIGINT UNSIGNED DEFAULT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_messages_chat_sender_id (
        chat_id,
        sender,
        id
    ),

    INDEX idx_messages_chat_type (
        chat_id,
        message_type
    ),

    INDEX idx_messages_reply_to_message_id (reply_to_message_id),

    INDEX idx_messages_deleted (
        chat_id,
        is_deleted
    ),

    FOREIGN KEY (reply_to_message_id)
    REFERENCES messages(id)
    ON DELETE SET NULL,

    FOREIGN KEY (chat_id)
    REFERENCES chats(id)
    ON DELETE CASCADE

);


/* ==================================================
   06. CHAT PRESENCE
================================================== */

CREATE TABLE chat_presence (

    chat_id BIGINT PRIMARY KEY,

    visitor_last_seen TIMESTAMP NULL,

    admin_last_seen TIMESTAMP NULL,

    visitor_typing_until TIMESTAMP NULL,

    admin_typing_until TIMESTAMP NULL,

    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (chat_id)
    REFERENCES chats(id)
    ON DELETE CASCADE

);


/* ==================================================
   07. MESSAGE DELETIONS
================================================== */

CREATE TABLE message_deletions (

    id BIGINT AUTO_INCREMENT PRIMARY KEY,

    message_id BIGINT NOT NULL,

    chat_id BIGINT NOT NULL,

    deleted_for_type ENUM(
        'visitor',
        'agent'
    ) NOT NULL,

    deleted_for_id VARCHAR(191) NOT NULL,

    deleted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY unique_message_delete_for (
        message_id,
        deleted_for_type,
        deleted_for_id
    ),

    INDEX idx_message_deletions_chat_user (
        chat_id,
        deleted_for_type,
        deleted_for_id
    ),

    FOREIGN KEY (message_id)
    REFERENCES messages(id)
    ON DELETE CASCADE,

    FOREIGN KEY (chat_id)
    REFERENCES chats(id)
    ON DELETE CASCADE

);


/* ==================================================
   08. AUDIT LOGS
================================================== */

CREATE TABLE audit_logs (

    id BIGINT AUTO_INCREMENT PRIMARY KEY,

    actor_user_id INT NULL,

    actor_name VARCHAR(150) NOT NULL,

    action VARCHAR(100) NOT NULL,

    target_type VARCHAR(50) NOT NULL,

    target_id BIGINT NOT NULL,

    target_name VARCHAR(150) DEFAULT NULL,

    description TEXT,

    ip_address VARCHAR(45) DEFAULT NULL,

    user_agent TEXT,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_audit_logs_actor_created (
        actor_user_id,
        created_at
    ),

    INDEX idx_audit_logs_action_created (
        action,
        created_at
    ),

    INDEX idx_audit_logs_target (
        target_type,
        target_id
    ),

    INDEX idx_audit_logs_created_at (created_at),

    FOREIGN KEY (actor_user_id)
    REFERENCES users(id)
    ON DELETE SET NULL

);


/* ==================================================
   09. PLATFORM SETTINGS
================================================== */

CREATE TABLE platform_settings (

    id BIGINT AUTO_INCREMENT PRIMARY KEY,

    setting_key VARCHAR(120) NOT NULL UNIQUE,

    setting_value JSON NOT NULL,

    value_type ENUM(
        'string',
        'integer',
        'boolean',
        'json'
    ) NOT NULL DEFAULT 'string',

    category VARCHAR(50) NOT NULL,

    label VARCHAR(150) NOT NULL,

    description TEXT,

    is_sensitive TINYINT(1) NOT NULL DEFAULT 0,

    updated_by INT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_platform_settings_category (category),

    INDEX idx_platform_settings_updated_by (updated_by),

    FOREIGN KEY (updated_by)
    REFERENCES users(id)
    ON DELETE SET NULL

);


/* ==================================================
   10. PLATFORM SETTINGS SEED
================================================== */

INSERT INTO platform_settings (
    setting_key,
    setting_value,
    value_type,
    category,
    label,
    description,
    is_sensitive
) VALUES

(
    'platform_name',
    JSON_QUOTE('XD Chat'),
    'string',
    'general',
    'Platform Name',
    'Main platform name shown across admin areas.',
    0
),

(
    'platform_tagline',
    JSON_QUOTE('Live Chat Platform'),
    'string',
    'general',
    'Platform Tagline',
    'Short platform tagline for dashboard and branding.',
    0
),

(
    'support_email',
    JSON_QUOTE(''),
    'string',
    'general',
    'Support Email',
    'Public support email for platform assistance.',
    0
),

(
    'support_phone',
    JSON_QUOTE(''),
    'string',
    'general',
    'Support Phone',
    'Public support phone number for platform assistance.',
    0
),

(
    'default_timezone',
    JSON_QUOTE('Asia/Kolkata'),
    'string',
    'general',
    'Default Timezone',
    'Default timezone for platform date and time display.',
    0
),

(
    'date_time_format',
    JSON_QUOTE('d M Y, h:i A'),
    'string',
    'general',
    'Date Time Format',
    'Default date and time display format.',
    0
),

(
    'default_new_user_role',
    JSON_QUOTE('admin'),
    'string',
    'security',
    'Default New User Role',
    'Default role assigned to newly registered users.',
    0
),

(
    'default_new_user_status',
    JSON_QUOTE('active'),
    'string',
    'security',
    'Default New User Status',
    'Default account status for newly registered users.',
    0
),

(
    'session_idle_timeout',
    '7200',
    'integer',
    'security',
    'Session Idle Timeout',
    'Session idle timeout in seconds.',
    0
),

(
    'minimum_password_length',
    '8',
    'integer',
    'security',
    'Minimum Password Length',
    'Minimum password length for user accounts.',
    0
),

(
    'allow_registration',
    'true',
    'boolean',
    'security',
    'Allow Registration',
    'Controls whether public registration is enabled.',
    0
),

(
    'default_welcome_message',
    JSON_QUOTE('Hi there! How can we help you today?'),
    'string',
    'chat',
    'Default Welcome Message',
    'Default widget welcome message for new widgets.',
    0
),

(
    'default_offline_message',
    JSON_QUOTE('We are currently offline. Please leave a message.'),
    'string',
    'chat',
    'Default Offline Message',
    'Default widget offline message for new widgets.',
    0
),

(
    'default_chat_status',
    JSON_QUOTE('open'),
    'string',
    'chat',
    'Default Chat Status',
    'Default status assigned to new visitor chats.',
    0
),

(
    'message_max_length',
    '1000',
    'integer',
    'chat',
    'Message Maximum Length',
    'Maximum allowed text message length.',
    0
),

(
    'delete_everyone_time_limit',
    '60',
    'integer',
    'chat',
    'Delete From Everyone Time Limit',
    'Time limit in minutes for future delete-from-everyone behavior.',
    0
),

(
    'image_max_size_mb',
    '5',
    'integer',
    'upload',
    'Image Maximum Size',
    'Maximum image upload size in MB.',
    0
),

(
    'document_max_size_mb',
    '10',
    'integer',
    'upload',
    'Document Maximum Size',
    'Maximum document upload size in MB.',
    0
),

(
    'audio_max_size_mb',
    '10',
    'integer',
    'upload',
    'Audio Maximum Size',
    'Maximum audio upload size in MB.',
    0
),

(
    'video_max_size_mb',
    '15',
    'integer',
    'upload',
    'Video Maximum Size',
    'Maximum video upload size in MB.',
    0
),

(
    'allowed_image_types',
    JSON_ARRAY('jpg', 'jpeg', 'png', 'webp'),
    'json',
    'upload',
    'Allowed Image Types',
    'Allowed image file extensions.',
    0
),

(
    'allowed_document_types',
    JSON_ARRAY('pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt'),
    'json',
    'upload',
    'Allowed Document Types',
    'Allowed document file extensions.',
    0
),

(
    'allowed_audio_types',
    JSON_ARRAY('mp3', 'wav', 'ogg', 'webm'),
    'json',
    'upload',
    'Allowed Audio Types',
    'Allowed audio file extensions.',
    0
),

(
    'allowed_video_types',
    JSON_ARRAY('mp4', 'webm', 'mov'),
    'json',
    'upload',
    'Allowed Video Types',
    'Allowed video file extensions.',
    0
),

(
    'maintenance_mode',
    'false',
    'boolean',
    'system',
    'Maintenance Mode',
    'Stores maintenance mode setting. Runtime enforcement will be added later.',
    0
),

(
    'maintenance_message',
    JSON_QUOTE('XD Chat is currently under maintenance. Please check back soon.'),
    'string',
    'system',
    'Maintenance Message',
    'Message shown during maintenance mode after runtime enforcement is added.',
    0
)

ON DUPLICATE KEY UPDATE
    setting_value = VALUES(setting_value),
    value_type = VALUES(value_type),
    category = VALUES(category),
    label = VALUES(label),
    description = VALUES(description),
    is_sensitive = VALUES(is_sensitive);
