<?php
/*
==================================================
Project : XD Chat
Version : 2.0.0
File    : presence.php
Module  : Chat Presence
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

$chat_id = isset($_POST["chat_id"])
    ? (int) $_POST["chat_id"]
    : 0;

$is_typing = isset($_POST["is_typing"])
    && $_POST["is_typing"] === "1";


/* ==========================================
   03. VALIDATION
========================================== */

if ($chat_id <= 0) {

    echo json_encode([
        "success" => false,
        "message" => "Chat ID is required."
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

    $is_typing = false;

}


/* ==========================================
   05. UPDATE ADMIN PRESENCE
========================================== */

$query = "
    INSERT INTO chat_presence (
        chat_id,
        admin_last_seen,
        admin_typing_until
    ) VALUES (
        ?,
        NOW(),
        CASE
            WHEN ? = 1
            THEN DATE_ADD(NOW(), INTERVAL 3 SECOND)
            ELSE NULL
        END
    )
    ON DUPLICATE KEY UPDATE
        admin_last_seen = NOW(),
        admin_typing_until = CASE
            WHEN ? = 1
            THEN DATE_ADD(NOW(), INTERVAL 3 SECOND)
            ELSE admin_typing_until
        END
";

$statement = $pdo->prepare($query);

$statement->execute([
    $chat_id,
    $is_typing ? 1 : 0,
    $is_typing ? 1 : 0
]);


/* ==========================================
   06. GET VISITOR STATUS
========================================== */

$query = "
    SELECT
        CASE
            WHEN visitor_last_seen >= DATE_SUB(NOW(), INTERVAL 15 SECOND)
            THEN 1
            ELSE 0
        END AS visitor_online,
        CASE
            WHEN visitor_typing_until >= NOW()
            THEN 1
            ELSE 0
        END AS visitor_typing
    FROM chat_presence
    WHERE chat_id = ?
    LIMIT 1
";

$statement = $pdo->prepare($query);

$statement->execute([
    $chat_id
]);

$presence = $statement->fetch(PDO::FETCH_ASSOC);


/* ==========================================
   07. SUCCESS RESPONSE
========================================== */

echo json_encode([
    "success" => true,
    "chat_id" => $chat_id,
    "visitor_online" => !empty($presence["visitor_online"]),
    "visitor_typing" => !empty($presence["visitor_typing"])
]);

exit;
