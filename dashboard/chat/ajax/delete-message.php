<?php
/*
==================================================
Project : XD Chat
Version : 2.0.0
File    : delete-message.php
Module  : Delete Agent Message
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

requireLogin();

header("Content-Type: application/json");


/* ==========================================
   02. GET POST DATA
========================================== */

$message_id = isset($_POST["message_id"])
    ? (int) $_POST["message_id"]
    : 0;

if ($message_id <= 0) {

    echo json_encode([
        "success" => false,
        "message" => "Invalid message."
    ]);

    exit;

}


/* ==========================================
   03. DELETE FOR ME
========================================== */

$query = "
    INSERT IGNORE INTO message_deletions (
        message_id,
        chat_id,
        deleted_for_type,
        deleted_for_id
    )
    SELECT
        messages.id,
        messages.chat_id,
        ?,
        ?
    FROM messages
    INNER JOIN chats
        ON messages.chat_id = chats.id
    INNER JOIN websites
        ON chats.website_id = websites.id
    WHERE messages.id = ?
    AND websites.user_id = ?
";

$statement = $pdo->prepare($query);

$statement->execute([
    "agent",
    (string) $_SESSION["user_id"],
    $message_id,
    $_SESSION["user_id"]
]);

if ($statement->rowCount() === 0) {

    echo json_encode([
        "success" => false,
        "message" => "Message cannot be deleted."
    ]);

    exit;

}


/* ==========================================
   04. SUCCESS RESPONSE
========================================== */

echo json_encode([
    "success" => true,
    "message_id" => $message_id,
    "delete_type" => "me",
    "message" => "Message deleted successfully."
]);

exit;
