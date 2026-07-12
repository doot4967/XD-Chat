<?php
/*
==================================================
Project : XD Chat
Version : 2.0.0
File    : chats.php
Module  : Super Admin Chats Overview
Status  : Development
Author  : Umesh + ChatGPT
Created : 10 July 2026
==================================================
*/


/* ==========================================
   01. LOAD DEPENDENCIES
========================================== */

require_once '../includes/functions/session.php';

require_once '../database/connection.php';

require_once '../includes/functions/platform-settings.php';

requireRole([
    "super_admin"
]);


/* ==========================================
   02. FILTER CONFIGURATION
========================================== */

$allowedStatuses = [
    "all",
    "open",
    "closed"
];

$search = trim($_GET["search"] ?? "");

$statusFilter = $_GET["status"] ?? "all";

$websiteFilter = (int) ($_GET["website_id"] ?? 0);

$ownerFilter = (int) ($_GET["owner_id"] ?? 0);

$dateFilter = trim($_GET["date"] ?? "");

$currentPage = (int) ($_GET["page"] ?? 1);

$perPage = 10;

if (!in_array($statusFilter, $allowedStatuses, true)) {

    $statusFilter = "all";

}

if ($websiteFilter < 0) {

    $websiteFilter = 0;

}

if ($ownerFilter < 0) {

    $ownerFilter = 0;

}

if ($dateFilter !== "" && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFilter)) {

    $dateFilter = "";

}

if ($currentPage < 1) {

    $currentPage = 1;

}

$offset = ($currentPage - 1) * $perPage;


/* ==========================================
   03. PAGE HELPERS
========================================== */

function buildSuperAdminChatFilters(
    string $search,
    string $statusFilter,
    int $websiteFilter,
    int $ownerFilter,
    string $dateFilter,
    array &$params
): string
{

    $where = [];

    if ($search !== "") {

        $where[] = "(
            chats.visitor_name LIKE :search
            OR chats.visitor_email LIKE :search
            OR websites.website_name LIKE :search
            OR websites.domain LIKE :search
            OR users.full_name LIKE :search
            OR users.email LIKE :search
        )";

        $params[":search"] = "%" . $search . "%";

    }

    if ($statusFilter !== "all") {

        $where[] = "chats.status = :status";

        $params[":status"] = $statusFilter;

    }

    if ($websiteFilter > 0) {

        $where[] = "chats.website_id = :website_id";

        $params[":website_id"] = $websiteFilter;

    }

    if ($ownerFilter > 0) {

        $where[] = "websites.user_id = :owner_id";

        $params[":owner_id"] = $ownerFilter;

    }

    if ($dateFilter !== "") {

        $where[] = "DATE(chats.created_at) = :date_filter";

        $params[":date_filter"] = $dateFilter;

    }

    if (empty($where)) {

        return "";

    }

    return " WHERE " . implode(" AND ", $where);

}


function getSuperAdminChatPageUrl(array $overrides = []): string
{

    $query = array_merge($_GET, $overrides);

    foreach ($query as $key => $value) {

        if ($value === "" || $value === null || $value === "all" || $value === 0 || $value === "0") {

            unset($query[$key]);

        }

    }

    return "chats.php" . (!empty($query) ? "?" . http_build_query($query) : "");

}


function getSuperAdminMessagePreview(array $message): string
{

    if (empty($message["id"])) {

        return "No message yet.";

    }

    if ((int) ($message["is_deleted"] ?? 0) === 1) {

        return "This message was deleted.";

    }

    if (($message["message_type"] ?? "text") === "text") {

        return $message["message"] ?: "Text message";

    }

    $label = ucfirst($message["message_type"] ?? "file");

    return $label . (!empty($message["file_name"]) ? ": " . $message["file_name"] : "");

}


function getSuperAdminSenderLabel(string $sender): string
{

    if ($sender === "agent") {

        return "Admin";

    }

    if ($sender === "bot") {

        return "Bot";

    }

    return "Visitor";

}


/* ==========================================
   04. LOAD FILTER OPTIONS
========================================== */

$websiteOptions = [];

$ownerOptions = [];

try {

    $websiteStatement = $pdo->prepare(
        "SELECT DISTINCT
            websites.id,
            websites.website_name,
            websites.domain
         FROM websites
         INNER JOIN chats
            ON chats.website_id = websites.id
         ORDER BY websites.website_name ASC, websites.domain ASC"
    );

    $websiteStatement->execute();

    $websiteOptions = $websiteStatement->fetchAll(PDO::FETCH_ASSOC);

    $validWebsiteIds = array_map(
        static fn ($website) => (int) $website["id"],
        $websiteOptions
    );

    if ($websiteFilter > 0 && !in_array($websiteFilter, $validWebsiteIds, true)) {

        $websiteFilter = 0;

    }

    $ownerStatement = $pdo->prepare(
        "SELECT DISTINCT
            users.id,
            users.full_name,
            users.email
         FROM users
         INNER JOIN websites
            ON websites.user_id = users.id
         INNER JOIN chats
            ON chats.website_id = websites.id
         ORDER BY users.full_name ASC, users.email ASC"
    );

    $ownerStatement->execute();

    $ownerOptions = $ownerStatement->fetchAll(PDO::FETCH_ASSOC);

    $validOwnerIds = array_map(
        static fn ($owner) => (int) $owner["id"],
        $ownerOptions
    );

    if ($ownerFilter > 0 && !in_array($ownerFilter, $validOwnerIds, true)) {

        $ownerFilter = 0;

    }

} catch (Throwable $exception) {

    $websiteOptions = [];

    $ownerOptions = [];

    $websiteFilter = 0;

    $ownerFilter = 0;

}


/* ==========================================
   05. LOAD CHATS
========================================== */

$queryParams = [];

$whereSql = buildSuperAdminChatFilters(
    $search,
    $statusFilter,
    $websiteFilter,
    $ownerFilter,
    $dateFilter,
    $queryParams
);

$chats = [];

$totalChats = 0;

$totalPages = 1;

try {

    $countStatement = $pdo->prepare(
        "SELECT COUNT(*)
         FROM chats
         INNER JOIN websites
            ON websites.id = chats.website_id
         INNER JOIN users
            ON users.id = websites.user_id"
        . $whereSql
    );

    foreach ($queryParams as $key => $value) {

        $countStatement->bindValue(
            $key,
            $value,
            in_array($key, [":website_id", ":owner_id"], true) ? PDO::PARAM_INT : PDO::PARAM_STR
        );

    }

    $countStatement->execute();

    $totalChats = (int) $countStatement->fetchColumn();

    $totalPages = max(1, (int) ceil($totalChats / $perPage));

    if ($currentPage > $totalPages) {

        $currentPage = $totalPages;

        $offset = ($currentPage - 1) * $perPage;

    }

    $chatStatement = $pdo->prepare(
        "SELECT
            chats.id,
            chats.visitor_name,
            chats.visitor_email,
            chats.status,
            chats.created_at,
            websites.website_name,
            websites.domain,
            users.full_name AS owner_name,
            users.email AS owner_email,
            COALESCE(message_stats.total_messages, 0) AS total_messages,
            COALESCE(message_stats.last_message_time, chats.created_at) AS last_activity,
            latest_message.id AS last_message_id,
            latest_message.message AS last_message,
            latest_message.message_type AS last_message_type,
            latest_message.file_name AS last_file_name,
            latest_message.is_deleted AS last_is_deleted
         FROM chats
         INNER JOIN websites
            ON websites.id = chats.website_id
         INNER JOIN users
            ON users.id = websites.user_id
         LEFT JOIN (
            SELECT
                chat_id,
                COUNT(*) AS total_messages,
                MAX(id) AS latest_message_id,
                MAX(created_at) AS last_message_time
            FROM messages
            GROUP BY chat_id
         ) message_stats ON message_stats.chat_id = chats.id
         LEFT JOIN messages latest_message
            ON latest_message.id = message_stats.latest_message_id"
        . $whereSql .
        " ORDER BY COALESCE(message_stats.last_message_time, chats.created_at) DESC, chats.id DESC
         LIMIT :limit OFFSET :offset"
    );

    foreach ($queryParams as $key => $value) {

        $chatStatement->bindValue(
            $key,
            $value,
            in_array($key, [":website_id", ":owner_id"], true) ? PDO::PARAM_INT : PDO::PARAM_STR
        );

    }

    $chatStatement->bindValue(":limit", $perPage, PDO::PARAM_INT);

    $chatStatement->bindValue(":offset", $offset, PDO::PARAM_INT);

    $chatStatement->execute();

    $chats = $chatStatement->fetchAll(PDO::FETCH_ASSOC);

} catch (Throwable $exception) {

    $chats = [];

    $totalChats = 0;

    $totalPages = 1;

}


/* ==========================================
   06. LOAD CONVERSATION DETAILS
========================================== */

$viewChatId = isset($_GET["view_chat_id"]) ? (int) $_GET["view_chat_id"] : 0;

$selectedChat = null;

$messages = [];

if ($viewChatId > 0) {

    try {

        $detailStatement = $pdo->prepare(
            "SELECT
                chats.id,
                chats.visitor_id,
                chats.visitor_name,
                chats.visitor_email,
                chats.visitor_page_url,
                chats.visitor_referrer,
                chats.visitor_browser,
                chats.visitor_device,
                chats.status,
                chats.closed_at,
                chats.created_at,
                websites.website_name,
                websites.domain,
                users.full_name AS owner_name,
                users.email AS owner_email
             FROM chats
             INNER JOIN websites
                ON websites.id = chats.website_id
             INNER JOIN users
                ON users.id = websites.user_id
             WHERE chats.id = :chat_id
             LIMIT 1"
        );

        $detailStatement->bindValue(":chat_id", $viewChatId, PDO::PARAM_INT);

        $detailStatement->execute();

        $selectedChat = $detailStatement->fetch(PDO::FETCH_ASSOC) ?: null;

        if ($selectedChat) {

            $messageStatement = $pdo->prepare(
                "SELECT
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
                 WHERE messages.chat_id = :chat_id
                 ORDER BY messages.id ASC"
            );

            $messageStatement->bindValue(":chat_id", $viewChatId, PDO::PARAM_INT);

            $messageStatement->execute();

            $messages = $messageStatement->fetchAll(PDO::FETCH_ASSOC);

        }

    } catch (Throwable $exception) {

        $selectedChat = null;

        $messages = [];

    }

}


/* ==========================================
   07. PAGE CONFIGURATION
========================================== */

$page_title = getPlatformPageTitle($pdo, "Chats Overview");

$page_heading = "Chats Overview";

$page_description = "Review visitor conversations across all websites in read-only mode.";

$active_menu = "chats";

require_once 'includes/header.php';
?>

<section class="xd-sa-users-panel xd-sa-chats-panel">

    <div class="xd-sa-users-header">

        <div>
            <h2>All Chats</h2>
            <p><?php echo htmlspecialchars(number_format($totalChats)); ?> chats found.</p>
        </div>

    </div>

    <form class="xd-sa-filter-bar xd-sa-chat-filter"
          method="GET"
          action="chats.php">

        <div class="xd-sa-filter-field wide">
            <label for="search">Search</label>
            <input type="text"
                   id="search"
                   name="search"
                   value="<?php echo htmlspecialchars($search); ?>"
                   placeholder="Visitor, website, owner">
        </div>

        <div class="xd-sa-filter-field">
            <label for="status">Status</label>
            <select id="status" name="status">
                <option value="all" <?php echo $statusFilter === "all" ? "selected" : ""; ?>>All</option>
                <option value="open" <?php echo $statusFilter === "open" ? "selected" : ""; ?>>Open</option>
                <option value="closed" <?php echo $statusFilter === "closed" ? "selected" : ""; ?>>Closed</option>
            </select>
        </div>

        <div class="xd-sa-filter-field">
            <label for="website_id">Website</label>
            <select id="website_id" name="website_id">
                <option value="0">All Websites</option>
                <?php foreach ($websiteOptions as $website) { ?>
                    <option value="<?php echo htmlspecialchars((string) $website["id"]); ?>"
                        <?php echo $websiteFilter === (int) $website["id"] ? "selected" : ""; ?>>
                        <?php echo htmlspecialchars($website["website_name"] . " - " . $website["domain"]); ?>
                    </option>
                <?php } ?>
            </select>
        </div>

        <div class="xd-sa-filter-field">
            <label for="owner_id">Owner</label>
            <select id="owner_id" name="owner_id">
                <option value="0">All Owners</option>
                <?php foreach ($ownerOptions as $owner) { ?>
                    <option value="<?php echo htmlspecialchars((string) $owner["id"]); ?>"
                        <?php echo $ownerFilter === (int) $owner["id"] ? "selected" : ""; ?>>
                        <?php echo htmlspecialchars($owner["full_name"] . " - " . $owner["email"]); ?>
                    </option>
                <?php } ?>
            </select>
        </div>

        <div class="xd-sa-filter-field">
            <label for="date">Date</label>
            <input type="date"
                   id="date"
                   name="date"
                   value="<?php echo htmlspecialchars($dateFilter); ?>">
        </div>

        <div class="xd-sa-filter-actions">
            <button type="submit">Search</button>
            <a href="chats.php">Clear</a>
        </div>

    </form>

    <div class="xd-sa-table-wrap">

        <table class="xd-sa-users-table xd-sa-chats-table">

            <thead>
                <tr>
                    <th>Visitor</th>
                    <th>Website / Domain</th>
                    <th>Owner</th>
                    <th>Status</th>
                    <th>Messages</th>
                    <th>Last Activity</th>
                    <th>Created</th>
                    <th>Action</th>
                </tr>
            </thead>

            <tbody>

                <?php if (!empty($chats)) { ?>

                    <?php foreach ($chats as $chat) {

                        $visitorName = $chat["visitor_name"] ?: "Guest Visitor";

                        $visitorEmail = $chat["visitor_email"] ?: "Not provided";

                        $lastPreview = getSuperAdminMessagePreview([
                            "id" => $chat["last_message_id"],
                            "message" => $chat["last_message"],
                            "message_type" => $chat["last_message_type"],
                            "file_name" => $chat["last_file_name"],
                            "is_deleted" => $chat["last_is_deleted"]
                        ]);

                        ?>

                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($visitorName); ?></strong>
                                <span class="xd-sa-table-subtext">
                                    <?php echo htmlspecialchars($visitorEmail); ?>
                                </span>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($chat["website_name"]); ?></strong>
                                <span class="xd-sa-table-subtext">
                                    <?php echo htmlspecialchars($chat["domain"]); ?>
                                </span>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($chat["owner_name"]); ?></strong>
                                <span class="xd-sa-table-subtext">
                                    <?php echo htmlspecialchars($chat["owner_email"]); ?>
                                </span>
                            </td>
                            <td>
                                <span class="xd-sa-chat-status <?php echo htmlspecialchars($chat["status"]); ?>">
                                    <?php echo htmlspecialchars(ucfirst($chat["status"])); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars(number_format((int) $chat["total_messages"])); ?></td>
                            <td>
                                <span class="xd-sa-chat-preview">
                                    <?php echo htmlspecialchars(substr($lastPreview, 0, 80)); ?>
                                </span>
                                <span class="xd-sa-table-subtext">
                                    <?php echo htmlspecialchars(date("d M Y, h:i A", strtotime($chat["last_activity"]))); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars(date("d M Y", strtotime($chat["created_at"]))); ?></td>
                            <td>
                                <a class="xd-sa-action-link"
                                   href="<?php echo htmlspecialchars(getSuperAdminChatPageUrl([
                                       "view_chat_id" => $chat["id"]
                                   ])); ?>">
                                    View Conversation
                                </a>
                            </td>
                        </tr>

                    <?php } ?>

                <?php } else { ?>

                    <tr>
                        <td colspan="8">
                            <div class="xd-sa-empty-state">
                                <strong>No chats found.</strong>
                                <span>Try changing your search or filter.</span>
                            </div>
                        </td>
                    </tr>

                <?php } ?>

            </tbody>

        </table>

    </div>

    <div class="xd-sa-pagination">

        <span>
            Page <?php echo htmlspecialchars((string) $currentPage); ?>
            of <?php echo htmlspecialchars((string) $totalPages); ?>
        </span>

        <div>
            <?php if ($currentPage > 1) { ?>
                <a href="<?php echo htmlspecialchars(getSuperAdminChatPageUrl([
                    "page" => $currentPage - 1
                ])); ?>">Previous</a>
            <?php } ?>

            <?php if ($currentPage < $totalPages) { ?>
                <a href="<?php echo htmlspecialchars(getSuperAdminChatPageUrl([
                    "page" => $currentPage + 1
                ])); ?>">Next</a>
            <?php } ?>
        </div>

    </div>

</section>


<?php if ($selectedChat) { ?>

    <section class="xd-sa-user-detail xd-sa-chat-detail">

        <div class="xd-sa-users-header">
            <div>
                <h2><?php echo htmlspecialchars($selectedChat["visitor_name"] ?: "Guest Visitor"); ?></h2>
                <p><?php echo htmlspecialchars($selectedChat["website_name"] . " - " . $selectedChat["domain"]); ?></p>
            </div>
            <a href="<?php echo htmlspecialchars(getSuperAdminChatPageUrl([
                "view_chat_id" => null
            ])); ?>">Close</a>
        </div>

        <div class="xd-sa-detail-grid">
            <div>
                <span>Status</span>
                <strong><?php echo htmlspecialchars(ucfirst($selectedChat["status"])); ?></strong>
            </div>
            <div>
                <span>Visitor Email</span>
                <strong><?php echo htmlspecialchars($selectedChat["visitor_email"] ?: "Not provided"); ?></strong>
            </div>
            <div>
                <span>Owner</span>
                <strong><?php echo htmlspecialchars($selectedChat["owner_name"]); ?></strong>
            </div>
            <div>
                <span>Owner Email</span>
                <strong><?php echo htmlspecialchars($selectedChat["owner_email"]); ?></strong>
            </div>
            <div>
                <span>Created</span>
                <strong><?php echo htmlspecialchars(date("d M Y, h:i A", strtotime($selectedChat["created_at"]))); ?></strong>
            </div>
            <div>
                <span>Closed</span>
                <strong>
                    <?php echo !empty($selectedChat["closed_at"])
                        ? htmlspecialchars(date("d M Y, h:i A", strtotime($selectedChat["closed_at"])))
                        : "Not closed"; ?>
                </strong>
            </div>
            <div>
                <span>Device</span>
                <strong><?php echo htmlspecialchars($selectedChat["visitor_device"] ?: "Unknown"); ?></strong>
            </div>
            <div>
                <span>Browser</span>
                <strong><?php echo htmlspecialchars($selectedChat["visitor_browser"] ?: "Unknown"); ?></strong>
            </div>
            <div class="xd-sa-detail-wide">
                <span>Page URL</span>
                <strong><?php echo htmlspecialchars($selectedChat["visitor_page_url"] ?: "Not captured"); ?></strong>
            </div>
            <div class="xd-sa-detail-wide">
                <span>Referrer</span>
                <strong><?php echo htmlspecialchars($selectedChat["visitor_referrer"] ?: "Direct visit"); ?></strong>
            </div>
        </div>

        <div class="xd-sa-chat-timeline">

            <div class="xd-sa-users-header">
                <div>
                    <h2>Message Timeline</h2>
                    <p>Read-only conversation history.</p>
                </div>
            </div>

            <?php if (!empty($messages)) { ?>

                <?php foreach ($messages as $message) {

                    $isDeleted = (int) ($message["is_deleted"] ?? 0) === 1;

                    $senderLabel = getSuperAdminSenderLabel($message["sender"]);

                    $messageType = $message["message_type"] ?? "text";

                    ?>

                    <article class="xd-sa-timeline-message <?php echo htmlspecialchars($message["sender"]); ?> <?php echo $isDeleted ? "deleted" : ""; ?>">

                        <div class="xd-sa-timeline-meta">
                            <strong><?php echo htmlspecialchars($senderLabel); ?></strong>
                            <span><?php echo htmlspecialchars(date("d M Y, h:i A", strtotime($message["created_at"]))); ?></span>
                        </div>

                        <?php if (!$isDeleted && !empty($message["reply_id"])) {

                            $replyText = !empty($message["reply_is_deleted"])
                                ? "Deleted message"
                                : (
                                    $message["reply_message_type"] === "text"
                                        ? $message["reply_message"]
                                        : ($message["reply_file_name"] ?: ucfirst($message["reply_message_type"]))
                                );

                            ?>

                            <div class="xd-sa-timeline-quote">
                                <strong><?php echo htmlspecialchars(getSuperAdminSenderLabel($message["reply_sender"] ?? "")); ?></strong>
                                <span><?php echo htmlspecialchars(substr($replyText, 0, 100)); ?></span>
                            </div>

                        <?php } ?>

                        <?php if ($isDeleted) { ?>

                            <p class="xd-sa-timeline-deleted">
                                &#128465; This message was deleted.
                            </p>

                        <?php } elseif ($messageType === "text" || $messageType === "system") { ?>

                            <p><?php echo htmlspecialchars($message["message"] ?: "Text message"); ?></p>

                        <?php } else { ?>

                            <p class="xd-sa-timeline-file">
                                <?php echo htmlspecialchars(ucfirst($messageType)); ?>:
                                <?php echo htmlspecialchars($message["file_name"] ?: "Attached file"); ?>
                            </p>

                        <?php } ?>

                    </article>

                <?php } ?>

            <?php } else { ?>

                <div class="xd-sa-empty-state">
                    <strong>No messages found.</strong>
                    <span>This conversation does not have any messages yet.</span>
                </div>

            <?php } ?>

        </div>

    </section>

<?php } ?>

<?php require_once 'includes/footer.php'; ?>
