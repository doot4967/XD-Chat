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

    widget_name VARCHAR(100) NOT NULL,

    widget_key VARCHAR(100) UNIQUE NOT NULL,

    theme ENUM(
        'light',
        'dark'
    ) DEFAULT 'light',

    position ENUM(
        'bottom-right',
        'bottom-left'
    ) DEFAULT 'bottom-right',

    widget_color VARCHAR(20) DEFAULT '#2563eb',

    widget_icon VARCHAR(50) DEFAULT 'chat',

    welcome_message TEXT,

    offline_message TEXT,

    status ENUM(
        'active',
        'inactive'
    ) DEFAULT 'active',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_widgets_user_id (user_id),

    INDEX idx_widgets_website_id (website_id),

    FOREIGN KEY (user_id)
    REFERENCES users(id)
    ON DELETE CASCADE,

    FOREIGN KEY (website_id)
    REFERENCES websites(id)
    ON DELETE CASCADE

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

    sender ENUM(
        'visitor',
        'agent',
        'bot'
    ) NOT NULL,

    message TEXT,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_messages_chat_sender_id (
        chat_id,
        sender,
        id
    ),

    FOREIGN KEY(chat_id)
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
