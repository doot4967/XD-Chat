<?php
/*
==================================================
Project : XD Chat
Version : 2.0.0
File    : send-message.php
Module  : Send Agent Message
Status  : Development
Author  : Umesh + ChatGPT
Created : 06 July 2026
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

$chat_id = isset($_POST["chat_id"])
    ? (int) $_POST["chat_id"]
    : 0;

$message = isset($_POST["message"])
    ? trim($_POST["message"])
    : "";


/* ==========================================
   03. VALIDATION
========================================== */

if ($chat_id <= 0 || $message === "") {

    echo json_encode([
        "success" => false,
        "message" => "Chat ID and message are required."
    ]);

    exit;

}


/* ==========================================
   04. CHECK CHAT EXISTS
========================================== */

$query = "
    SELECT id
    FROM chats
    WHERE id = ?
    LIMIT 1
";

$statement = $pdo->prepare($query);

$statement->execute([
    $chat_id
]);

$chat = $statement->fetch(PDO::FETCH_ASSOC);

if (!$chat) {

    echo json_encode([
        "success" => false,
        "message" => "Chat not found."
    ]);

    exit;

}


/* ==========================================
   05. INSERT AGENT MESSAGE
========================================== */

$query = "
    INSERT INTO messages (
        chat_id,
        sender,
        message
    ) VALUES (?, ?, ?)
";

$statement = $pdo->prepare($query);

$statement->execute([
    $chat_id,
    "agent",
    $message
]);


/* ==========================================
   06. SUCCESS RESPONSE
========================================== */

echo json_encode([
    "success" => true,
    "message" => "Message sent successfully."
]);

exit;