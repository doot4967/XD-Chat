<?php
/*
==================================================
Project : XD Chat
Version : 2.0.0
File    : load-chat.php
Module  : Load Chat Messages
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

header("Content-Type: text/html; charset=UTF-8");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");


/* ==========================================
   02. VALIDATE CHAT ID
========================================== */

$chat_id = isset($_GET["chat_id"])
    ? (int) $_GET["chat_id"]
    : 0;

if ($chat_id <= 0) { ?>

    <div class="xd-chat-empty-state large">
        Invalid chat selected.
    </div>

<?php exit; }


/* ==========================================
   03. CHECK CHAT OWNERSHIP
========================================== */

$query = "
    SELECT
        chats.id,
        chats.visitor_name,
        chats.visitor_email,
        chats.visitor_page_url,
        chats.visitor_referrer,
        chats.visitor_browser,
        chats.visitor_device,
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

if (!$chat) { ?>

    <div class="xd-chat-empty-state large">
        Invalid chat selected.
    </div>

<?php exit; }


/* ==========================================
   04. RENDER VISITOR DETAILS
========================================== */

$visitorName = !empty($chat["visitor_name"])
    ? $chat["visitor_name"]
    : "Guest Visitor";

$visitorEmail = !empty($chat["visitor_email"])
    ? $chat["visitor_email"]
    : "Not provided";

$visitorPageUrl = !empty($chat["visitor_page_url"])
    ? $chat["visitor_page_url"]
    : "Not captured";

$visitorReferrer = !empty($chat["visitor_referrer"])
    ? $chat["visitor_referrer"]
    : "Direct visit";

$visitorDevice = !empty($chat["visitor_device"])
    ? $chat["visitor_device"]
    : "Unknown";

$visitorBrowser = !empty($chat["visitor_browser"])
    ? $chat["visitor_browser"]
    : "Unknown";

?>

<div class="xd-chat-visitor-payload"
     data-name="<?php echo htmlspecialchars($visitorName); ?>"
     data-email="<?php echo htmlspecialchars($visitorEmail); ?>"
     data-page-url="<?php echo htmlspecialchars($visitorPageUrl); ?>"
     data-referrer="<?php echo htmlspecialchars($visitorReferrer); ?>"
     data-device="<?php echo htmlspecialchars($visitorDevice); ?>"
     data-browser="<?php echo htmlspecialchars($visitorBrowser); ?>"
     data-status="<?php echo htmlspecialchars($chat["status"]); ?>">
</div>

<?php

/* ==========================================
   05. GET MESSAGES
========================================== */

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
    AND NOT EXISTS (
        SELECT 1
        FROM message_deletions
        WHERE message_deletions.message_id = messages.id
        AND message_deletions.deleted_for_type = 'agent'
        AND message_deletions.deleted_for_id = ?
    )
    ORDER BY messages.id ASC
";

$statement = $pdo->prepare($query);

$statement->execute([
    $chat_id,
    (string) $_SESSION["user_id"]
]);

$messages = $statement->fetchAll(PDO::FETCH_ASSOC);


/* ==========================================
   06. EMPTY MESSAGE STATE
========================================== */

if (count($messages) === 0) { ?>

    <div class="xd-chat-empty-state large">
        No messages found in this conversation.
    </div>

<?php exit; }


/* ==========================================
   07. RENDER MESSAGES
========================================== */

foreach ($messages as $message) {

    $messageClass = ($message["sender"] === "agent")
        ? "agent"
        : "visitor";

    $isDeleted = (int) ($message["is_deleted"] ?? 0) === 1;

    $copyText = $message["message_type"] === "text"
        ? $message["message"]
        : (
            $message["message_type"] === "audio"
                ? "Voice message"
                : ($message["file_name"] ?: $message["message"])
        );

    $voiceDownloadUrl = $message["message_type"] === "audio" && !$isDeleted
        ? "chat/ajax/download-file.php?message_id=" . (int) $message["id"] . "&download=1"
        : "";

    ?>

    <div class="xd-admin-message <?php echo $messageClass; ?> <?php echo $isDeleted ? "deleted" : ""; ?><?php echo $voiceDownloadUrl !== "" ? " audio-message" : ""; ?>"
         data-message-id="<?php echo (int) $message["id"]; ?>"
         data-message-sender="<?php echo htmlspecialchars($message["sender"]); ?>"
         data-message-text="<?php echo htmlspecialchars($isDeleted ? "Deleted message" : $copyText); ?>"
         data-is-deleted="<?php echo $isDeleted ? "1" : "0"; ?>">

        <div class="xd-admin-message-bubble">

            <?php if (!$isDeleted) { ?>

            <div class="xd-message-action-wrap">

                <button class="xd-message-menu-trigger"
                        type="button"
                        aria-label="Message actions">
                    &#8942;
                </button>

                <div class="xd-message-actions"
                     role="menu"
                     aria-hidden="true">
                <button type="button"
                        data-action="reply">
                    ↩ Reply
                </button>
                <button type="button"
                        data-action="copy">
                    📋 Copy
                </button>
                <?php if ($voiceDownloadUrl !== "") { ?>
                <button type="button"
                        data-action="download"
                        data-download-url="<?php echo htmlspecialchars($voiceDownloadUrl); ?>"
                        data-download-name="<?php echo htmlspecialchars($message["file_name"] ?: "voice-message"); ?>">
                    Download
                </button>
                <?php } ?>
                <button type="button"
                        data-action="delete">
                    Delete
                </button>
                </div>

            </div>

            <?php } ?>

            <?php if (!$isDeleted && !empty($message["reply_id"])) {

                $replySender = $message["reply_sender"] === "agent"
                    ? "Admin"
                    : "Visitor";

                $replyText = !empty($message["reply_is_deleted"])
                    ? "Deleted message"
                    : (
                        $message["reply_message_type"] === "text"
                            ? $message["reply_message"]
                            : (
                                $message["reply_message_type"] === "audio"
                                    ? "Voice message"
                                    : ($message["reply_file_name"] ?: ucfirst($message["reply_message_type"]))
                            )
                    );

                ?>

                <div class="xd-message-quote"
                     data-reply-id="<?php echo (int) $message["reply_id"]; ?>">
                    <strong><?php echo htmlspecialchars($replySender); ?></strong>
                    <span><?php echo htmlspecialchars(substr($replyText, 0, 80)); ?></span>
                </div>

            <?php } ?>

            <?php if ($isDeleted) { ?>

                <p class="xd-message-deleted-text">
                    &#128465; This message was deleted.
                </p>

            <?php } elseif ($message["message_type"] === "image") {

                $downloadUrl = "chat/ajax/download-file.php?message_id=" . (int) $message["id"];
                $mediaDownloadUrl = $downloadUrl . "&download=1";

                ?>

                <a class="xd-chat-image-link"
                   href="<?php echo htmlspecialchars($downloadUrl); ?>"
                   target="_blank">
                    <img src="<?php echo htmlspecialchars($downloadUrl); ?>"
                         alt="<?php echo htmlspecialchars($message["file_name"]); ?>">
                </a>

                <p class="xd-chat-file-caption">
                    <?php echo htmlspecialchars($message["file_name"]); ?>
                </p>

            <?php } elseif ($message["message_type"] === "file") {

                $downloadUrl = "chat/ajax/download-file.php?message_id=" . (int) $message["id"];

                ?>

                <div class="xd-chat-file-card">

                    <div class="xd-chat-file-icon">
                        FILE
                    </div>

                    <div class="xd-chat-file-meta">

                        <strong>
                            <?php echo htmlspecialchars($message["file_name"]); ?>
                        </strong>

                        <small>
                            <?php echo htmlspecialchars(formatChatFileSize((int) $message["file_size"])); ?>
                        </small>

                    </div>

                    <a href="<?php echo htmlspecialchars($downloadUrl); ?>"
                       title="Download"
                       aria-label="Download">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M12 3v10.2l3.6-3.6L17 11l-5 5-5-5 1.4-1.4 3.6 3.6V3h2z"></path>
                            <path d="M5 19h14v2H5z"></path>
                        </svg>
                    </a>

                </div>

            <?php } elseif (
                $message["message_type"] === "audio" ||
                $message["message_type"] === "video"
            ) {

                $downloadUrl = "chat/ajax/download-file.php?message_id=" . (int) $message["id"];
                $mediaDownloadUrl = $downloadUrl . "&download=1";

                ?>

                <div class="xd-chat-media-card<?php echo $message["message_type"] === "audio" ? " xd-chat-audio-card" : ""; ?>">

                    <?php if ($message["message_type"] === "video") { ?>

                        <video controls
                               src="<?php echo htmlspecialchars($downloadUrl); ?>">
                        </video>

                    <?php } else { ?>

                        <audio controls
                               src="<?php echo htmlspecialchars($downloadUrl); ?>">
                        </audio>

                    <?php } ?>

                    <?php if ($message["message_type"] === "video") { ?>

                    <div class="xd-chat-file-meta">

                        <strong>
                            <?php echo htmlspecialchars($message["file_name"]); ?>
                        </strong>

                        <small>
                            <?php echo htmlspecialchars(formatChatFileSize((int) $message["file_size"])); ?>
                        </small>

                    </div>

                    <a href="<?php echo htmlspecialchars($mediaDownloadUrl); ?>"
                       title="Download"
                       aria-label="Download">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M12 3v10.2l3.6-3.6L17 11l-5 5-5-5 1.4-1.4 3.6 3.6V3h2z"></path>
                            <path d="M5 19h14v2H5z"></path>
                        </svg>
                    </a>

                    <?php } ?>

                </div>

            <?php } else { ?>

                <p>
                    <?php echo htmlspecialchars($message["message"]); ?>
                </p>

            <?php } ?>

            <span class="xd-message-time">
                <?php echo date("h:i A", strtotime($message["created_at"])); ?>
            </span>

        </div>

    </div>

<?php } ?>
