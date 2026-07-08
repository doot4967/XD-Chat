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
        id,
        sender,
        message,
        message_type,
        file_name,
        file_mime,
        file_size,
        created_at
    FROM messages
    WHERE chat_id = ?
    ORDER BY id ASC
";

$statement = $pdo->prepare($query);

$statement->execute([
    $chat_id
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

    ?>

    <div class="xd-admin-message <?php echo $messageClass; ?>">

        <div class="xd-admin-message-bubble">

            <?php if ($message["message_type"] === "image") {

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

                    <a href="<?php echo htmlspecialchars($downloadUrl); ?>">
                        Download
                    </a>

                </div>

            <?php } elseif (
                $message["message_type"] === "audio" ||
                $message["message_type"] === "video"
            ) {

                $downloadUrl = "chat/ajax/download-file.php?message_id=" . (int) $message["id"];

                ?>

                <div class="xd-chat-media-card">

                    <?php if ($message["message_type"] === "video") { ?>

                        <video controls
                               src="<?php echo htmlspecialchars($downloadUrl); ?>">
                        </video>

                    <?php } else { ?>

                        <audio controls
                               src="<?php echo htmlspecialchars($downloadUrl); ?>">
                        </audio>

                    <?php } ?>

                    <div class="xd-chat-file-meta">

                        <strong>
                            <?php echo htmlspecialchars($message["file_name"]); ?>
                        </strong>

                        <small>
                            <?php echo htmlspecialchars(formatChatFileSize((int) $message["file_size"])); ?>
                        </small>

                    </div>

                    <a href="<?php echo htmlspecialchars($mediaDownloadUrl); ?>">
                        Download
                    </a>

                </div>

            <?php } else { ?>

                <p>
                    <?php echo htmlspecialchars($message["message"]); ?>
                </p>

            <?php } ?>

            <span>
                <?php echo date("h:i A", strtotime($message["created_at"])); ?>
            </span>

        </div>

    </div>

<?php } ?>
