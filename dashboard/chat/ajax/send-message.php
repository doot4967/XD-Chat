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

require_once '../../../includes/functions/upload.php';

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

$reply_to_message_id = isset($_POST["reply_to_message_id"])
    ? (int) $_POST["reply_to_message_id"]
    : 0;

$has_file = isset($_FILES["chat_file"])
    && (int) $_FILES["chat_file"]["error"] !== UPLOAD_ERR_NO_FILE;


/* ==========================================
   03. VALIDATION
========================================== */

if ($chat_id <= 0 || ($message === "" && !$has_file)) {

    echo json_encode([
        "success" => false,
        "message" => "Chat ID and message or file are required."
    ]);

    exit;

}


/* ==========================================
   04. CHECK CHAT OWNERSHIP
========================================== */

$query = "
    SELECT
        chats.id,
        chats.status
    FROM chats
    INNER JOIN websites
        ON chats.website_id = websites.id
    WHERE chats.id = ?
    AND websites.user_id = ?
    LIMIT 1
";

$statement = $pdo->prepare($query);

$statement->execute([
    $chat_id,
    $_SESSION["user_id"]
]);

$chat = $statement->fetch(PDO::FETCH_ASSOC);

if (!$chat) {

    echo json_encode([
        "success" => false,
        "message" => "Chat not found or access denied."
    ]);

    exit;

}


if ($chat["status"] === "closed") {

    echo json_encode([
        "success" => false,
        "message" => "This chat is closed."
    ]);

    exit;

}


if ($reply_to_message_id > 0) {

    $query = "
        SELECT id
        FROM messages
        WHERE id = ?
        AND chat_id = ?
        AND is_deleted = 0
        LIMIT 1
    ";

    $statement = $pdo->prepare($query);

    $statement->execute([
        $reply_to_message_id,
        $chat_id
    ]);

    if (!$statement->fetchColumn()) {
        $reply_to_message_id = 0;
    }

}


/* ==========================================
   05. PREPARE MESSAGE DATA
========================================== */

$message_type = "text";
$file_data = null;

if ($has_file) {

    $file_data = saveChatUploadedFile($_FILES["chat_file"]);

    if (empty($file_data["success"])) {

        echo json_encode([
            "success" => false,
            "message" => $file_data["message"] ?? "File upload failed."
        ]);

        exit;

    }

    $message_type = $file_data["message_type"];

    if ($message === "") {
        $message = $file_data["file_name"];
    }

}


/* ==========================================
   06. INSERT AGENT MESSAGE
========================================== */

$query = "
    INSERT INTO messages (
        chat_id,
        reply_to_message_id,
        sender,
        message,
        message_type,
        file_name,
        file_path,
        file_mime,
        file_size
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
";

$statement = $pdo->prepare($query);

$statement->execute([
    $chat_id,
    $reply_to_message_id > 0 ? $reply_to_message_id : null,
    "agent",
    $message,
    $message_type,
    $file_data["file_name"] ?? null,
    $file_data["file_path"] ?? null,
    $file_data["file_mime"] ?? null,
    $file_data["file_size"] ?? null
]);


/* ==========================================
   07. SUCCESS RESPONSE
========================================== */

echo json_encode([
    "success" => true,
    "message" => "Message sent successfully."
]);

exit;
