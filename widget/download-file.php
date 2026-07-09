<?php
/*
==================================================
Project : XD Chat
Version : 2.0.0
File    : download-file.php
Module  : Widget Secure File Download
Status  : Development
Author  : Umesh + ChatGPT
Created : 08 July 2026
==================================================
*/

require_once '../database/connection.php';

require_once '../includes/functions/upload.php';


/* ==========================================
   01. GET REQUEST DATA
========================================== */

$message_id = isset($_GET["message_id"])
    ? (int) $_GET["message_id"]
    : 0;

$widget_key = trim($_GET["widget_key"] ?? "");

$visitor_id = trim($_GET["visitor_id"] ?? "");

if ($message_id <= 0 || $widget_key === "" || $visitor_id === "") {

    http_response_code(400);
    exit("Invalid file request.");

}


/* ==========================================
   02. CHECK FILE ACCESS
========================================== */

$query = "
    SELECT
        messages.file_name,
        messages.file_path,
        messages.file_mime,
        messages.file_size,
        messages.message_type
    FROM messages
    INNER JOIN chats
        ON messages.chat_id = chats.id
    INNER JOIN widgets
        ON chats.website_id = widgets.website_id
    INNER JOIN websites
        ON chats.website_id = websites.id
    WHERE messages.id = ?
    AND chats.visitor_id = ?
    AND widgets.widget_key = ?
    AND widgets.status = 'active'
    AND websites.status = 'active'
    AND messages.is_deleted = 0
    AND NOT EXISTS (
        SELECT 1
        FROM message_deletions
        WHERE message_deletions.message_id = messages.id
        AND message_deletions.deleted_for_type = 'visitor'
        AND message_deletions.deleted_for_id = ?
    )
    AND messages.message_type IN ('image', 'file', 'audio', 'video')
    LIMIT 1
";

$statement = $pdo->prepare($query);

$statement->execute([
    $message_id,
    $visitor_id,
    $widget_key,
    $visitor_id
]);

$message = $statement->fetch(PDO::FETCH_ASSOC);

if (!$message) {

    http_response_code(404);
    exit("File not found.");

}


/* ==========================================
   03. SEND FILE
========================================== */

sendChatFileDownload($message);
