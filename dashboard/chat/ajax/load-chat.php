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
   03. GET MESSAGES
========================================== */

$query = "
    SELECT
        sender,
        message,
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
   04. EMPTY MESSAGE STATE
========================================== */

if (count($messages) === 0) { ?>

    <div class="xd-chat-empty-state large">
        No messages found in this conversation.
    </div>

<?php exit; }


/* ==========================================
   05. RENDER MESSAGES
========================================== */

foreach ($messages as $message) {

    $messageClass = ($message["sender"] === "agent")
        ? "agent"
        : "visitor";

    ?>

    <div class="xd-admin-message <?php echo $messageClass; ?>">

        <div class="xd-admin-message-bubble">

            <p>
                <?php echo htmlspecialchars($message["message"]); ?>
            </p>

            <span>
                <?php echo date("h:i A", strtotime($message["created_at"])); ?>
            </span>

        </div>

    </div>

<?php } ?>