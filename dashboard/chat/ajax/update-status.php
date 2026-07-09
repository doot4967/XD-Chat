<?php
/*
==================================================
Project : XD Chat
Version : 2.0.0
File    : update-status.php
Module  : Update Chat Status
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
   02. VERIFY CSRF TOKEN
========================================== */

$csrf_token = $_POST["csrf_token"] ?? "";

if (!verifyCsrfToken($csrf_token)) {

    echo json_encode([
        "success" => false,
        "message" => "Invalid request. Please refresh and try again."
    ]);

    exit;

}


/* ==========================================
   03. GET POST DATA
========================================== */

$chat_id = isset($_POST["chat_id"])
    ? (int) $_POST["chat_id"]
    : 0;

$status = isset($_POST["status"])
    ? trim($_POST["status"])
    : "";


/* ==========================================
   04. VALIDATION
========================================== */

if ($chat_id <= 0 || !in_array($status, ["closed"], true)) {

    echo json_encode([
        "success" => false,
        "message" => "Invalid chat status request."
    ]);

    exit;

}


/* ==========================================
   05. CHECK CHAT OWNERSHIP
========================================== */

$query = "
    SELECT chats.id
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


/* ==========================================
   06. UPDATE STATUS
========================================== */

$query = "
    UPDATE chats
    SET
        status = ?,
        closed_at = NOW()
    WHERE id = ?
";

$statement = $pdo->prepare($query);

$statement->execute([
    $status,
    $chat_id
]);


/* ==========================================
   07. SUCCESS RESPONSE
========================================== */

echo json_encode([
    "success" => true,
    "message" => "Chat closed successfully."
]);

exit;
