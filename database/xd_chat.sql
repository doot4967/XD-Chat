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
   03. CHATS
================================================== */

CREATE TABLE chats (

    id BIGINT AUTO_INCREMENT PRIMARY KEY,

    website_id INT NOT NULL,

    visitor_id VARCHAR(100),

    visitor_name VARCHAR(100),

    visitor_email VARCHAR(150),

    status ENUM(
        'open',
        'closed'
    ) DEFAULT 'open',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (website_id)
    REFERENCES websites(id)
    ON DELETE CASCADE

);


/* ==================================================
   04. MESSAGES
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

    FOREIGN KEY(chat_id)
    REFERENCES chats(id)
    ON DELETE CASCADE

);