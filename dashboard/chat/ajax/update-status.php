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

if ($chat_id <= 0 || !in_array($status, ["open", "closed"], true)) {

    echo json_encode([
        "success" => false,
        "message" => "Invalid chat status request."
    ]);

    exit;

}


/* ==========================================
   05. UPDATE STATUS
========================================== */

$closingMessage = "Your issue/query has been resolved. This chat has been closed. If you need further help, please send a new message.";

try {

    $pdo->beginTransaction();

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
        FOR UPDATE
    ";

    $statement = $pdo->prepare($query);

    $statement->execute([
        $chat_id,
        $_SESSION["user_id"]
    ]);

    $chat = $statement->fetch(PDO::FETCH_ASSOC);

    if (!$chat) {

        $pdo->rollBack();

        echo json_encode([
            "success" => false,
            "message" => "Chat not found or access denied."
        ]);

        exit;

    }

    $currentStatus = $chat["status"] ?? "";

    if ($status === "closed" && $currentStatus !== "closed") {

        $query = "
            INSERT INTO messages (
                chat_id,
                sender,
                message,
                message_type
            ) VALUES (?, ?, ?, ?)
        ";

        $statement = $pdo->prepare($query);

        $statement->execute([
            $chat_id,
            "agent",
            $closingMessage,
            "system"
        ]);

    }

    if ($status !== $currentStatus) {

        $query = "
            UPDATE chats
            SET
                status = ?,
                closed_at = CASE
                    WHEN ? = 'closed'
                    THEN NOW()
                    ELSE NULL
                END
            WHERE id = ?
        ";

        $statement = $pdo->prepare($query);

        $statement->execute([
            $status,
            $status,
            $chat_id
        ]);

    }

    $pdo->commit();

    echo json_encode([
        "success" => true,
        "status" => $status,
        "message" => $status === "closed"
            ? (
                $currentStatus === "closed"
                    ? "Chat is already closed."
                    : "Chat closed successfully."
            )
            : "Chat reopened successfully."
    ]);

    exit;

} catch (Throwable $exception) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log("Chat status update failed: " . $exception->getMessage());

    echo json_encode([
        "success" => false,
        "message" => "Unable to update chat status. Please try again."
    ]);

    exit;

}
