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

require_once '../includes/functions/upload.php';

header("Content-Type: application/json");

/* ==========================================
   01. SECURITY HELPERS
========================================== */

function normalizeWidgetHost(string $domain): string
{

    $domain = trim(strtolower($domain));

    if ($domain === "") {

        return "";

    }

    if (!preg_match("/^https?:\/\//", $domain)) {

        $domain = "https://" . $domain;

    }

    $host = parse_url($domain, PHP_URL_HOST);

    return $host ? preg_replace("/^www\./", "", $host) : "";

}


function getRequestWidgetHost(): string
{

    $source = $_SERVER["HTTP_ORIGIN"]
        ?? $_SERVER["HTTP_REFERER"]
        ?? $_SERVER["HTTP_HOST"]
        ?? "";

    return normalizeWidgetHost($source);

}


function isLocalWidgetHost(string $host): bool
{

    return in_array($host, [
        "localhost",
        "127.0.0.1",
        "::1"
    ], true);

}


function isWidgetDomainAllowed(string $websiteDomain): bool
{

    $requestHost = getRequestWidgetHost();

    if (isLocalWidgetHost($requestHost)) {

        return true;

    }

    return $requestHost !== ""
        && $requestHost === normalizeWidgetHost($websiteDomain);

}


/* ==========================================
   02. RESPONSE HELPER
========================================== */

function sendJsonResponse(bool $success, array $data = []): void
{

    echo json_encode(array_merge([
        "success" => $success
    ], $data));

    exit;

}


/* ==========================================
   03. REQUEST HELPERS
========================================== */

function getPostValue(string $key): string
{

    return trim($_POST[$key] ?? "");

}


function limitPostValue(string $key, int $length): string
{

    return substr(getPostValue($key), 0, $length);

}


function validateRequiredRequest(string $widgetKey, string $visitorId): void
{

    if (empty($widgetKey) || empty($visitorId)) {

        sendJsonResponse(false, [
            "message" => "Missing required data."
        ]);

    }

}


function getVisitorDetails(): array
{

    $visitorEmail = limitPostValue("visitor_email", 150);

    if (!empty($visitorEmail) && !filter_var($visitorEmail, FILTER_VALIDATE_EMAIL)) {

        $visitorEmail = "";

    }

    return [
        "visitor_name" => limitPostValue("visitor_name", 100),
        "visitor_email" => $visitorEmail,
        "visitor_page_url" => limitPostValue("visitor_page_url", 2000),
        "visitor_referrer" => limitPostValue("visitor_referrer", 2000),
        "visitor_browser" => limitPostValue("visitor_browser", 500),
        "visitor_device" => limitPostValue("visitor_device", 100)
    ];

}


function validateVisitorDetails(array $visitorDetails): void
{

    if (empty($visitorDetails["visitor_name"])) {

        sendJsonResponse(false, [
            "message" => "Visitor name is required."
        ]);

    }

}


function getWidgetByKey(PDO $pdo, string $widgetKey): array
{

    $query = "
        SELECT
            widgets.id,
            widgets.website_id,
            widgets.status,
            websites.domain,
            websites.status AS website_status
        FROM widgets
        INNER JOIN websites
            ON widgets.website_id = websites.id
        WHERE widgets.widget_key = ?
        LIMIT 1
    ";

    $statement = $pdo->prepare($query);
    $statement->execute([$widgetKey]);

    $widget = $statement->fetch(PDO::FETCH_ASSOC);

    if (
        !$widget ||
        $widget["status"] !== "active" ||
        $widget["website_status"] !== "active"
    ) {

        sendJsonResponse(false, [
            "message" => "Widget not available."
        ]);

    }

    if (!isWidgetDomainAllowed($widget["domain"])) {

        sendJsonResponse(false, [
            "message" => "Widget domain is not allowed."
        ]);

    }

    return $widget;

}


/* ==========================================
   04. CHAT HELPERS
========================================== */

function getChatLockName(int $websiteId, string $visitorId): string
{

    return "xd_chat_" . md5($websiteId . "_" . $visitorId);

}


function acquireChatLock(PDO $pdo, string $lockName): void
{

    $statement = $pdo->prepare("SELECT GET_LOCK(?, 5)");
    $statement->execute([$lockName]);

}


function releaseChatLock(PDO $pdo, string $lockName): void
{

    $statement = $pdo->prepare("SELECT RELEASE_LOCK(?)");
    $statement->execute([$lockName]);

}


function getOrCreateChat(PDO $pdo, array $widget, string $visitorId, array $visitorDetails): int
{

    $lockName = getChatLockName((int) $widget["website_id"], $visitorId);

    acquireChatLock($pdo, $lockName);

    try {

    $query = "
        SELECT id
        FROM chats
        WHERE website_id = ?
        AND visitor_id = ?
        ORDER BY id ASC
        LIMIT 1
    ";

    $statement = $pdo->prepare($query);
    $statement->execute([
        $widget["website_id"],
        $visitorId
    ]);

    $chat = $statement->fetch(PDO::FETCH_ASSOC);

    if (!$chat) {

        $defaultChatStatus = getPlatformDefaultChatStatus($pdo);

        $query = "
            INSERT INTO chats (
                website_id,
                visitor_id,
                visitor_name,
                visitor_email,
                visitor_page_url,
                visitor_referrer,
                visitor_browser,
                visitor_device,
                status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ";

        $statement = $pdo->prepare($query);
        $statement->execute([
            $widget["website_id"],
            $visitorId,
            $visitorDetails["visitor_name"] ?: "Guest Visitor",
            $visitorDetails["visitor_email"],
            $visitorDetails["visitor_page_url"],
            $visitorDetails["visitor_referrer"],
            $visitorDetails["visitor_browser"],
            $visitorDetails["visitor_device"],
            $defaultChatStatus
        ]);

        return (int) $pdo->lastInsertId();

    }

    updateChatVisitorDetails($pdo, (int) $chat["id"], $visitorDetails);

    return (int) $chat["id"];

    } finally {

        releaseChatLock($pdo, $lockName);

    }

}


function updateChatVisitorDetails(PDO $pdo, int $chatId, array $visitorDetails): void
{

    if (
        empty($visitorDetails["visitor_name"]) &&
        empty($visitorDetails["visitor_email"]) &&
        empty($visitorDetails["visitor_page_url"]) &&
        empty($visitorDetails["visitor_referrer"]) &&
        empty($visitorDetails["visitor_browser"]) &&
        empty($visitorDetails["visitor_device"])
    ) {

        return;

    }

    $query = "
        UPDATE chats
        SET
            visitor_name = CASE
                WHEN ? != '' THEN ?
                ELSE visitor_name
            END,
            visitor_email = CASE
                WHEN ? != '' THEN ?
                ELSE visitor_email
            END,
            visitor_page_url = CASE
                WHEN ? != '' THEN ?
                ELSE visitor_page_url
            END,
            visitor_referrer = CASE
                WHEN ? != '' THEN ?
                ELSE visitor_referrer
            END,
            visitor_browser = CASE
                WHEN ? != '' THEN ?
                ELSE visitor_browser
            END,
            visitor_device = CASE
                WHEN ? != '' THEN ?
                ELSE visitor_device
            END
        WHERE id = ?
    ";

    $statement = $pdo->prepare($query);

    $statement->execute([
        $visitorDetails["visitor_name"],
        $visitorDetails["visitor_name"],
        $visitorDetails["visitor_email"],
        $visitorDetails["visitor_email"],
        $visitorDetails["visitor_page_url"],
        $visitorDetails["visitor_page_url"],
        $visitorDetails["visitor_referrer"],
        $visitorDetails["visitor_referrer"],
        $visitorDetails["visitor_browser"],
        $visitorDetails["visitor_browser"],
        $visitorDetails["visitor_device"],
        $visitorDetails["visitor_device"],
        $chatId
    ]);

}


function reopenChatIfClosed(PDO $pdo, int $chatId): void
{

    $query = "
        UPDATE chats
        SET
            status = 'open',
            closed_at = NULL
        WHERE id = ?
        AND status = 'closed'
    ";

    $statement = $pdo->prepare($query);
    $statement->execute([
        $chatId
    ]);

}


/* ==========================================
   05. MESSAGE HELPERS
========================================== */

function validateVisitorMessage(PDO $pdo, string $message): void
{

    $messageMaxLength = getPlatformMessageMaxLength($pdo);

    if (empty($message)) {

        sendJsonResponse(false, [
            "message" => "Message is required."
        ]);

    }

    if (mb_strlen($message, "UTF-8") > $messageMaxLength) {

        sendJsonResponse(false, [
            "message" => "Message is too long. Maximum " . $messageMaxLength . " characters allowed."
        ]);

    }

}


function validateRateLimit(PDO $pdo, int $chatId): void
{

    $rateLimitMaxMessages = 10;

    $rateLimitSeconds = 60;

    $query = "
        SELECT COUNT(*) AS total
        FROM messages
        WHERE chat_id = ?
        AND sender = ?
        AND created_at >= DATE_SUB(NOW(), INTERVAL {$rateLimitSeconds} SECOND)
    ";

    $statement = $pdo->prepare($query);

    $statement->execute([
        $chatId,
        "visitor"
    ]);

    $recentMessageCount = (int) $statement->fetchColumn();

    if ($recentMessageCount >= $rateLimitMaxMessages) {

        sendJsonResponse(false, [
            "message" => "Please wait before sending more messages."
        ]);

    }

}


function hasAgentReply(PDO $pdo, int $chatId): bool
{

    $query = "
        SELECT COUNT(*) AS total
        FROM messages
        WHERE chat_id = ?
        AND sender = ?
    ";

    $statement = $pdo->prepare($query);

    $statement->execute([
        $chatId,
        "agent"
    ]);

    return (int) $statement->fetchColumn() > 0;

}


function saveMessage(
    PDO $pdo,
    int $chatId,
    string $sender,
    string $message,
    string $messageType = "text",
    ?array $fileData = null,
    ?int $replyToMessageId = null
): int
{

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
        $chatId,
        $replyToMessageId,
        $sender,
        $message,
        $messageType,
        $fileData["file_name"] ?? null,
        $fileData["file_path"] ?? null,
        $fileData["file_mime"] ?? null,
        $fileData["file_size"] ?? null
    ]);

    return (int) $pdo->lastInsertId();

}


function getValidReplyMessageId(PDO $pdo, int $chatId): ?int
{

    $replyToMessageId = (int) ($_POST["reply_to_message_id"] ?? 0);

    if ($replyToMessageId <= 0) {
        return null;
    }

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
        $replyToMessageId,
        $chatId
    ]);

    return $statement->fetchColumn()
        ? $replyToMessageId
        : null;

}


function getDeletedMessageIds(PDO $pdo, int $chatId): array
{

    $query = "
        SELECT id
        FROM messages
        WHERE chat_id = ?
        AND is_deleted = 1
    ";

    $statement = $pdo->prepare($query);
    $statement->execute([$chatId]);

    return array_map("intval", $statement->fetchAll(PDO::FETCH_COLUMN));

}


function getHiddenMessageIds(PDO $pdo, int $chatId, string $visitorId): array
{

    $query = "
        SELECT message_id
        FROM message_deletions
        WHERE chat_id = ?
        AND deleted_for_type = ?
        AND deleted_for_id = ?
    ";

    $statement = $pdo->prepare($query);
    $statement->execute([
        $chatId,
        "visitor",
        $visitorId
    ]);

    return array_map("intval", $statement->fetchAll(PDO::FETCH_COLUMN));

}


/* ==========================================
   06. ACTION HANDLERS
========================================== */

function handleSendMessage(PDO $pdo, int $chatId, string $message): void
{

    validateVisitorMessage($pdo, $message);

    validateRateLimit($pdo, $chatId);

    $replyToMessageId = getValidReplyMessageId($pdo, $chatId);

    $autoReplyMessage = "Thanks! Our team will reply shortly.";

    $hasAgentReply = hasAgentReply($pdo, $chatId);

    reopenChatIfClosed($pdo, $chatId);

    $messageId = saveMessage($pdo, $chatId, "visitor", $message, "text", null, $replyToMessageId);

    $autoReply = "";

    $lastMessageId = $messageId;

    if (!$hasAgentReply) {

        $lastMessageId = saveMessage($pdo, $chatId, "agent", $autoReplyMessage);

        $autoReply = $autoReplyMessage;

    }

    sendJsonResponse(true, [
        "chat_id" => $chatId,
        "message_id" => $messageId,
        "last_message_id" => $lastMessageId,
        "auto_reply" => $autoReply,
        "message" => "Visitor message saved successfully."
    ]);

}


function handleSendFile(PDO $pdo, int $chatId): void
{

    if (!isset($_FILES["chat_file"])) {

        sendJsonResponse(false, [
            "message" => "File is required."
        ]);

    }

    validateRateLimit($pdo, $chatId);

    $replyToMessageId = getValidReplyMessageId($pdo, $chatId);

    $fileData = saveChatUploadedFile($_FILES["chat_file"], $pdo);

    if (empty($fileData["success"])) {

        sendJsonResponse(false, [
            "message" => $fileData["message"] ?? "File upload failed."
        ]);

    }

    $message = $fileData["file_name"];

    $autoReplyMessage = "Thanks! Our team will reply shortly.";

    $hasAgentReply = hasAgentReply($pdo, $chatId);

    reopenChatIfClosed($pdo, $chatId);

    $messageId = saveMessage(
        $pdo,
        $chatId,
        "visitor",
        $message,
        $fileData["message_type"],
        $fileData,
        $replyToMessageId
    );

    $autoReply = "";

    $lastMessageId = $messageId;

    if (!$hasAgentReply) {

        $lastMessageId = saveMessage($pdo, $chatId, "agent", $autoReplyMessage);

        $autoReply = $autoReplyMessage;

    }

    sendJsonResponse(true, [
        "chat_id" => $chatId,
        "message_id" => $messageId,
        "last_message_id" => $lastMessageId,
        "auto_reply" => $autoReply,
        "message" => "File sent successfully."
    ]);

}


function handleLoadMessages(PDO $pdo, int $chatId, string $visitorId): void
{

    $lastMessageId = (int) ($_POST["last_message_id"] ?? 0);

    $query = "
        SELECT
            messages.id,
            messages.sender,
            messages.message,
            messages.message_type,
            messages.file_name,
            messages.file_mime,
            messages.file_size,
            messages.is_deleted,
            messages.created_at,
            reply_messages.id AS reply_id,
            reply_messages.sender AS reply_sender,
            reply_messages.message AS reply_message,
            reply_messages.message_type AS reply_message_type,
            reply_messages.file_name AS reply_file_name,
            reply_messages.is_deleted AS reply_is_deleted
        FROM messages
        LEFT JOIN messages AS reply_messages
            ON messages.reply_to_message_id = reply_messages.id
            AND reply_messages.chat_id = messages.chat_id
        WHERE messages.chat_id = ?
        AND messages.id > ?
        AND NOT EXISTS (
            SELECT 1
            FROM message_deletions
            WHERE message_deletions.message_id = messages.id
            AND message_deletions.deleted_for_type = 'visitor'
            AND message_deletions.deleted_for_id = ?
        )
        ORDER BY messages.id ASC
    ";

    $statement = $pdo->prepare($query);
    $statement->execute([
        $chatId,
        $lastMessageId,
        $visitorId
    ]);

    $messages = $statement->fetchAll(PDO::FETCH_ASSOC);

    foreach ($messages as $message) {

        $lastMessageId = max($lastMessageId, (int) $message["id"]);

    }

    sendJsonResponse(true, [
        "chat_id" => $chatId,
        "last_message_id" => $lastMessageId,
        "deleted_message_ids" => getDeletedMessageIds($pdo, $chatId),
        "hidden_message_ids" => getHiddenMessageIds($pdo, $chatId, $visitorId),
        "messages" => $messages
    ]);

}


function handleDeleteForMe(PDO $pdo, int $chatId, string $visitorId, int $messageId): void
{

    $query = "
        INSERT IGNORE INTO message_deletions (
            message_id,
            chat_id,
            deleted_for_type,
            deleted_for_id
        )
        SELECT
            id,
            chat_id,
            ?,
            ?
        FROM messages
        WHERE id = ?
        AND chat_id = ?
    ";

    $statement = $pdo->prepare($query);
    $statement->execute([
        "visitor",
        $visitorId,
        $messageId,
        $chatId
    ]);

    if ($statement->rowCount() === 0) {

        sendJsonResponse(false, [
            "message" => "Message cannot be deleted."
        ]);

    }

    sendJsonResponse(true, [
        "message_id" => $messageId,
        "delete_type" => "me",
        "message" => "Message deleted successfully."
    ]);

}


function handleDeleteMessage(PDO $pdo, int $chatId, string $visitorId): void
{

    $messageId = (int) ($_POST["message_id"] ?? 0);

    if ($messageId <= 0) {

        sendJsonResponse(false, [
            "message" => "Invalid message."
        ]);

    }

    handleDeleteForMe($pdo, $chatId, $visitorId, $messageId);

}


function handlePresence(PDO $pdo, int $chatId): void
{

    $isTyping = getPostValue("is_typing") === "1";

    $query = "
        INSERT INTO chat_presence (
            chat_id,
            visitor_last_seen,
            visitor_typing_until
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
            visitor_last_seen = NOW(),
            visitor_typing_until = CASE
                WHEN ? = 1
                THEN DATE_ADD(NOW(), INTERVAL 3 SECOND)
                ELSE visitor_typing_until
            END
    ";

    $statement = $pdo->prepare($query);

    $statement->execute([
        $chatId,
        $isTyping ? 1 : 0,
        $isTyping ? 1 : 0
    ]);

    $query = "
        SELECT
            CASE
                WHEN admin_last_seen >= DATE_SUB(NOW(), INTERVAL 15 SECOND)
                THEN 1
                ELSE 0
            END AS admin_online,
            CASE
                WHEN admin_typing_until >= NOW()
                THEN 1
                ELSE 0
            END AS admin_typing
        FROM chat_presence
        WHERE chat_id = ?
        LIMIT 1
    ";

    $statement = $pdo->prepare($query);

    $statement->execute([
        $chatId
    ]);

    $presence = $statement->fetch(PDO::FETCH_ASSOC);

    sendJsonResponse(true, [
        "chat_id" => $chatId,
        "admin_online" => !empty($presence["admin_online"]),
        "admin_typing" => !empty($presence["admin_typing"])
    ]);

}


/* ==========================================
   07. REQUEST DATA
========================================== */

$action = getPostValue("action");
$widget_key = getPostValue("widget_key");
$visitor_id = getPostValue("visitor_id");
$message = getPostValue("message");
$visitorDetails = getVisitorDetails();

validateRequiredRequest($widget_key, $visitor_id);

$widget = getWidgetByKey($pdo, $widget_key);

if ($action === "send_message" || $action === "send_file") {

    validateVisitorDetails($visitorDetails);

}

$chat_id = getOrCreateChat($pdo, $widget, $visitor_id, $visitorDetails);


/* ==========================================
   08. ACTION ROUTER
========================================== */

if ($action === "send_message") {

    handleSendMessage($pdo, $chat_id, $message);

}

if ($action === "send_file") {

    handleSendFile($pdo, $chat_id);

}

if ($action === "load_messages") {

    handleLoadMessages($pdo, $chat_id, $visitor_id);

}

if ($action === "delete_message") {

    handleDeleteMessage($pdo, $chat_id, $visitor_id);

}

if ($action === "presence") {

    handlePresence($pdo, $chat_id);

}

sendJsonResponse(false, [
    "message" => "Invalid action."
]);
