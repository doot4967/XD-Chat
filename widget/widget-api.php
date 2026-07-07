<?php
/*
==================================================
Project : XD Chat
Version : 2.0.0
File    : widget-api.php
Module  : Public Widget API
Status  : Development
Author  : Umesh + ChatGPT
Created : 06 July 2026
==================================================
*/

require_once '../database/connection.php';

header("Content-Type: application/json");

$action = $_POST["action"] ?? "";
$widget_key = trim($_POST["widget_key"] ?? "");
$visitor_id = trim($_POST["visitor_id"] ?? "");
$message = trim($_POST["message"] ?? "");

if (empty($widget_key) || empty($visitor_id)) {
    echo json_encode([
        "success" => false,
        "message" => "Missing required data."
    ]);
    exit;
}

/* ==========================================
   01. GET WIDGET
========================================== */

$query = "
    SELECT id, website_id, status
    FROM widgets
    WHERE widget_key = ?
    LIMIT 1
";

$statement = $pdo->prepare($query);
$statement->execute([$widget_key]);
$widget = $statement->fetch(PDO::FETCH_ASSOC);

if (!$widget || $widget["status"] !== "active") {
    echo json_encode([
        "success" => false,
        "message" => "Widget not available."
    ]);
    exit;
}

/* ==========================================
   02. GET OR CREATE CHAT
========================================== */

$query = "
    SELECT id
    FROM chats
    WHERE website_id = ?
    AND visitor_id = ?
    LIMIT 1
";

$statement = $pdo->prepare($query);
$statement->execute([
    $widget["website_id"],
    $visitor_id
]);

$chat = $statement->fetch(PDO::FETCH_ASSOC);

if (!$chat) {

    $query = "
        INSERT INTO chats (
            website_id,
            visitor_id,
            visitor_name,
            visitor_email,
            status
        ) VALUES (?, ?, ?, ?, ?)
    ";

    $statement = $pdo->prepare($query);
    $statement->execute([
        $widget["website_id"],
        $visitor_id,
        "Guest Visitor",
        "",
        "open"
    ]);

    $chat_id = $pdo->lastInsertId();

} else {

    $chat_id = $chat["id"];

}

/* ==========================================
   03. SAVE VISITOR MESSAGE
========================================== */

if ($action === "send_message") {

    if (empty($message)) {
        echo json_encode([
            "success" => false,
            "message" => "Message is required."
        ]);
        exit;
    }

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
        "visitor",
        $message
    ]);

    echo json_encode([
        "success" => true,
        "chat_id" => (int) $chat_id,
        "message" => "Visitor message saved successfully."
    ]);

    exit;
}

/* ==========================================
   04. SAVE AGENT AUTO REPLY
========================================== */

if ($action === "agent_reply") {

    if (empty($message)) {
        echo json_encode([
            "success" => false,
            "message" => "Message is required."
        ]);
        exit;
    }

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

    echo json_encode([
        "success" => true,
        "chat_id" => (int) $chat_id,
        "message" => "Agent reply saved successfully."
    ]);

    exit;
}

/* ==========================================
   05. LOAD MESSAGES
========================================== */

if ($action === "load_messages") {

    $query = "
        SELECT sender, message, created_at
        FROM messages
        WHERE chat_id = ?
        ORDER BY id ASC
    ";

    $statement = $pdo->prepare($query);
    $statement->execute([$chat_id]);

    echo json_encode([
        "success" => true,
        "chat_id" => (int) $chat_id,
        "messages" => $statement->fetchAll(PDO::FETCH_ASSOC)
    ]);

    exit;
}

/* ==========================================
   06. INVALID ACTION
========================================== */

echo json_encode([
    "success" => false,
    "message" => "Invalid action."
]);

exit;