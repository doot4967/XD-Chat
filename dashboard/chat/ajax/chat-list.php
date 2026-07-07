<?php
/*
==================================================
Project : XD Chat
Version : 2.0.0
File    : chat-list.php
Module  : Load Chat List
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


/* ==========================================
   02. GET CHAT LIST
========================================== */

$query = "
    SELECT
        chats.id,
        chats.visitor_id,
        chats.visitor_name,
        chats.status,
        chats.created_at,
        websites.website_name,

        (
            SELECT messages.message
            FROM messages
            WHERE messages.chat_id = chats.id
            ORDER BY messages.id DESC
            LIMIT 1
        ) AS last_message,

        (
            SELECT messages.created_at
            FROM messages
            WHERE messages.chat_id = chats.id
            ORDER BY messages.id DESC
            LIMIT 1
        ) AS last_message_time

    FROM chats
    INNER JOIN websites
        ON chats.website_id = websites.id

    ORDER BY chats.id DESC
";

$statement = $pdo->prepare($query);

$statement->execute();

$chats = $statement->fetchAll(PDO::FETCH_ASSOC);


/* ==========================================
   03. EMPTY STATE
========================================== */

if (count($chats) === 0) { ?>

    <div class="xd-chat-empty-state">
        No conversations yet.
    </div>

<?php exit; }


/* ==========================================
   04. RENDER CHAT LIST
========================================== */

foreach ($chats as $chat) {

    $visitorName = !empty($chat["visitor_name"])
        ? $chat["visitor_name"]
        : "Guest Visitor";

    $lastMessage = !empty($chat["last_message"])
        ? $chat["last_message"]
        : "No message yet.";

    $messageTime = !empty($chat["last_message_time"])
        ? $chat["last_message_time"]
        : $chat["created_at"];

    ?>

    <button class="xd-chat-list-item"
            type="button"
            data-chat-id="<?php echo (int) $chat["id"]; ?>"
            data-visitor-name="<?php echo htmlspecialchars($visitorName); ?>">

        <div class="xd-chat-list-avatar">
            <?php echo strtoupper(substr($visitorName, 0, 1)); ?>
        </div>

        <div class="xd-chat-list-content">

            <div class="xd-chat-list-top">

                <strong>
                    <?php echo htmlspecialchars($visitorName); ?>
                </strong>

                <small>
                    <?php echo date("h:i A", strtotime($messageTime)); ?>
                </small>

            </div>

            <p>
                <?php echo htmlspecialchars($lastMessage); ?>
            </p>

            <span>
                <?php echo htmlspecialchars($chat["website_name"]); ?>
            </span>

        </div>

    </button>

<?php } ?>