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
   02. GET FILTER
========================================== */

$status = isset($_GET["status"])
    ? trim($_GET["status"])
    : "open";

$search = isset($_GET["search"])
    ? trim($_GET["search"])
    : "";

$allowedStatuses = [
    "open",
    "closed",
    "unread"
];

if (!in_array($status, $allowedStatuses, true)) {

    $status = "open";

}

$chatStatus = $status === "unread"
    ? "open"
    : $status;

$searchLike = "%" . $search . "%";


/* ==========================================
   03. GET CHAT LIST
========================================== */

$whereConditions = [
    "websites.user_id = ?",
    "chats.status = ?"
];

$queryParams = [
    $_SESSION["user_id"],
    $chatStatus
];

if ($search !== "") {

    $whereConditions[] = "(
        chats.visitor_name LIKE ?
        OR chats.visitor_email LIKE ?
        OR websites.website_name LIKE ?
    )";

    $queryParams[] = $searchLike;
    $queryParams[] = $searchLike;
    $queryParams[] = $searchLike;

}

$unreadFilterSql = $status === "unread"
    ? "WHERE chat_data.unread_count > 0"
    : "";

$whereSql = implode("\n        AND ", $whereConditions);

$query = "
    SELECT
        chat_data.id,
        chat_data.visitor_id,
        chat_data.visitor_name,
        chat_data.status,
        chat_data.last_seen_message_id,
        chat_data.created_at,
        chat_data.website_name,
        chat_data.last_message,
        chat_data.last_message_type,
        chat_data.last_message_time,
        chat_data.unread_count
    FROM (
        SELECT
            chats.id,
            chats.visitor_id,
            chats.visitor_name,
            chats.status,
            chats.last_seen_message_id,
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
                SELECT messages.message_type
                FROM messages
                WHERE messages.chat_id = chats.id
                ORDER BY messages.id DESC
                LIMIT 1
            ) AS last_message_type,

            (
                SELECT messages.created_at
                FROM messages
                WHERE messages.chat_id = chats.id
                ORDER BY messages.id DESC
                LIMIT 1
            ) AS last_message_time,

            (
                SELECT COUNT(*)
                FROM messages
                WHERE messages.chat_id = chats.id
                AND messages.sender = 'visitor'
                AND messages.id > chats.last_seen_message_id
            ) AS unread_count

        FROM chats
        INNER JOIN websites
            ON chats.website_id = websites.id
        WHERE " . $whereSql . "
    ) AS chat_data
    " . $unreadFilterSql . "
    ORDER BY
        CASE
            WHEN chat_data.status = 'open'
            AND chat_data.unread_count > 0
            THEN 0
            ELSE 1
        END ASC,
        COALESCE(chat_data.last_message_time, chat_data.created_at) DESC
";

$statement = $pdo->prepare($query);

$statement->execute($queryParams);

$chats = $statement->fetchAll(PDO::FETCH_ASSOC);


/* ==========================================
   04. EMPTY STATE
========================================== */

if (count($chats) === 0) { ?>

    <div class="xd-chat-empty-state">
        No matching conversations found.
    </div>

<?php exit; }


/* ==========================================
   05. RENDER CHAT LIST
========================================== */

foreach ($chats as $chat) {

    $visitorName = !empty($chat["visitor_name"])
        ? $chat["visitor_name"]
        : "Guest Visitor";

    $lastMessage = ($chat["last_message_type"] ?? "") === "audio"
        ? "Voice message"
        : (
            !empty($chat["last_message"])
                ? $chat["last_message"]
                : "No message yet."
        );

    $messageTime = !empty($chat["last_message_time"])
        ? $chat["last_message_time"]
        : $chat["created_at"];

    $unreadCount = (int) $chat["unread_count"];

    ?>

    <button class="xd-chat-list-item"
            type="button"
            data-chat-id="<?php echo (int) $chat["id"]; ?>"
            data-visitor-name="<?php echo htmlspecialchars($visitorName); ?>"
            data-chat-status="<?php echo htmlspecialchars($chat["status"]); ?>">

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

            <div class="xd-chat-list-badges">

                <em class="xd-chat-status-badge <?php echo htmlspecialchars($chat["status"]); ?>">
                    <?php echo ucfirst(htmlspecialchars($chat["status"])); ?>
                </em>

                <?php if ($unreadCount > 0) { ?>

                    <em class="xd-chat-unread-badge">
                        <?php echo $unreadCount; ?>
                    </em>

                <?php } ?>

            </div>

        </div>

    </button>

<?php } ?>
