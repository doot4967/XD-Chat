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
