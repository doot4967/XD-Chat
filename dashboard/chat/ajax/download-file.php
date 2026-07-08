<?php
/*
==================================================
Project : XD Chat
Version : 2.0.0
File    : download-file.php
Module  : Dashboard Secure File Download
Status  : Development
Author  : Umesh + ChatGPT
Created : 08 July 2026
==================================================
*/


/* ==========================================
   01. LOAD DEPENDENCIES
========================================== */

require_once '../../../database/connection.php';

require_once '../../../includes/functions/session.php';

require_once '../../../includes/functions/upload.php';

requireLogin();


/* ==========================================
   02. GET REQUEST DATA
========================================== */

$message_id = isset($_GET["message_id"])
    ? (int) $_GET["message_id"]
    : 0;

if ($message_id <= 0) {

    http_response_code(400);
    exit("Invalid file request.");

}


/* ==========================================
   03. CHECK FILE OWNERSHIP
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
    INNER JOIN websites
        ON chats.website_id = websites.id
    WHERE messages.id = ?
    AND websites.user_id = ?
    AND messages.message_type IN ('image', 'file', 'audio', 'video')
    LIMIT 1
";

$statement = $pdo->prepare($query);

$statement->execute([
    $message_id,
    $_SESSION["user_id"]
]);

$message = $statement->fetch(PDO::FETCH_ASSOC);

if (!$message) {

    http_response_code(404);
    exit("File not found.");

}


/* ==========================================
   04. SEND FILE
========================================== */

sendChatFileDownload($message);
