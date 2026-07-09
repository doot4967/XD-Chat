<?php
/*
==================================================
Project : XD Chat
Version : 2.0.0
File    : websites.php
Module  : Super Admin Websites Overview
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

requireRole([
    "super_admin"
]);


/* ==========================================
   02. FILTER CONFIGURATION
========================================== */

$allowedStatuses = [
    "all",
    "active",
    "inactive"
];

$search = trim($_GET["search"] ?? "");

$statusFilter = $_GET["status"] ?? "all";

$ownerFilter = (int) ($_GET["owner_id"] ?? 0);

$currentPage = (int) ($_GET["page"] ?? 1);

$perPage = 10;

if (!in_array($statusFilter, $allowedStatuses, true)) {

    $statusFilter = "all";

}

if ($ownerFilter < 0) {

    $ownerFilter = 0;

}

if ($currentPage < 1) {

    $currentPage = 1;

}

$offset = ($currentPage - 1) * $perPage;


/* ==========================================
   03. PAGE HELPERS
========================================== */

function buildSuperAdminWebsiteFilters(string $search, string $statusFilter, int $ownerFilter, array &$params): string
{

    $where = [];

    if ($search !== "") {

        $where[] = "(
            websites.website_name LIKE :search
            OR websites.domain LIKE :search
            OR users.full_name LIKE :search
            OR users.email LIKE :search
        )";

        $params[":search"] = "%" . $search . "%";

    }

    if ($statusFilter !== "all") {

        $where[] = "websites.status = :status";

        $params[":status"] = $statusFilter;

    }

    if ($ownerFilter > 0) {

        $where[] = "websites.user_id = :owner_id";

        $params[":owner_id"] = $ownerFilter;

    }

    if (empty($where)) {

        return "";

    }

    return " WHERE " . implode(" AND ", $where);

}


function getSuperAdminWebsitePageUrl(array $overrides = []): string
{

    $query = array_merge($_GET, $overrides);

    foreach ($query as $key => $value) {

        if ($value === "" || $value === null || $value === "all" || $value === 0 || $value === "0") {

            unset($query[$key]);

        }

    }

    return "websites.php" . (!empty($query) ? "?" . http_build_query($query) : "");

}


/* ==========================================
   04. LOAD OWNER FILTER OPTIONS
========================================== */

$owners = [];

try {

    $ownerStatement = $pdo->prepare(
        "SELECT DISTINCT
            users.id,
            users.full_name,
            users.email
         FROM users
         INNER JOIN websites
            ON websites.user_id = users.id
         ORDER BY users.full_name ASC, users.email ASC"
    );

    $ownerStatement->execute();

    $owners = $ownerStatement->fetchAll(PDO::FETCH_ASSOC);

    $validOwnerIds = array_map(
        static fn ($owner) => (int) $owner["id"],
        $owners
    );

    if ($ownerFilter > 0 && !in_array($ownerFilter, $validOwnerIds, true)) {

        $ownerFilter = 0;

    }

} catch (Throwable $exception) {

    $owners = [];

    $ownerFilter = 0;

}


/* ==========================================
   05. LOAD WEBSITES
========================================== */

$queryParams = [];

$whereSql = buildSuperAdminWebsiteFilters(
    $search,
    $statusFilter,
    $ownerFilter,
    $queryParams
);

$websites = [];

$totalWebsites = 0;

$totalPages = 1;

try {

    $countStatement = $pdo->prepare(
        "SELECT COUNT(*)
         FROM websites
         INNER JOIN users
            ON users.id = websites.user_id"
        . $whereSql
    );

    foreach ($queryParams as $key => $value) {

        $countStatement->bindValue(
            $key,
            $value,
            $key === ":owner_id" ? PDO::PARAM_INT : PDO::PARAM_STR
        );

    }

    $countStatement->execute();

    $totalWebsites = (int) $countStatement->fetchColumn();

    $totalPages = max(1, (int) ceil($totalWebsites / $perPage));

    if ($currentPage > $totalPages) {

        $currentPage = $totalPages;

        $offset = ($currentPage - 1) * $perPage;

    }

    $websiteStatement = $pdo->prepare(
        "SELECT
            websites.id,
            websites.website_name,
            websites.domain,
            websites.status,
            websites.created_at,
            users.id AS owner_id,
            users.full_name AS owner_name,
            users.email AS owner_email,
            COALESCE(widget_counts.total, 0) AS widgets_count,
            COALESCE(chat_counts.total, 0) AS chats_count
         FROM websites
         INNER JOIN users
            ON users.id = websites.user_id
         LEFT JOIN (
            SELECT website_id, COUNT(*) AS total
            FROM widgets
            GROUP BY website_id
         ) widget_counts ON widget_counts.website_id = websites.id
         LEFT JOIN (
            SELECT website_id, COUNT(*) AS total
            FROM chats
            GROUP BY website_id
         ) chat_counts ON chat_counts.website_id = websites.id"
        . $whereSql .
        " ORDER BY websites.created_at DESC, websites.id DESC
         LIMIT :limit OFFSET :offset"
    );

    foreach ($queryParams as $key => $value) {

        $websiteStatement->bindValue(
            $key,
            $value,
            $key === ":owner_id" ? PDO::PARAM_INT : PDO::PARAM_STR
        );

    }

    $websiteStatement->bindValue(":limit", $perPage, PDO::PARAM_INT);

    $websiteStatement->bindValue(":offset", $offset, PDO::PARAM_INT);

    $websiteStatement->execute();

    $websites = $websiteStatement->fetchAll(PDO::FETCH_ASSOC);

} catch (Throwable $exception) {

    $websites = [];

    $totalWebsites = 0;

    $totalPages = 1;

}


/* ==========================================
   06. LOAD WEBSITE DETAILS
========================================== */

$viewWebsiteId = isset($_GET["view_website_id"]) ? (int) $_GET["view_website_id"] : 0;

$selectedWebsite = null;

if ($viewWebsiteId > 0) {

    try {

        $detailStatement = $pdo->prepare(
            "SELECT
                websites.id,
                websites.website_name,
                websites.domain,
                websites.widget_key,
                websites.status,
                websites.created_at,
                users.full_name AS owner_name,
                users.email AS owner_email,
                COALESCE(widget_counts.total, 0) AS widgets_count,
                COALESCE(chat_counts.total, 0) AS chats_count,
                COALESCE(message_counts.total, 0) AS messages_count
             FROM websites
             INNER JOIN users
                ON users.id = websites.user_id
             LEFT JOIN (
                SELECT website_id, COUNT(*) AS total
                FROM widgets
                GROUP BY website_id
             ) widget_counts ON widget_counts.website_id = websites.id
             LEFT JOIN (
                SELECT website_id, COUNT(*) AS total
                FROM chats
                GROUP BY website_id
             ) chat_counts ON chat_counts.website_id = websites.id
             LEFT JOIN (
                SELECT chats.website_id, COUNT(messages.id) AS total
                FROM chats
                LEFT JOIN messages
                    ON messages.chat_id = chats.id
                GROUP BY chats.website_id
             ) message_counts ON message_counts.website_id = websites.id
             WHERE websites.id = :website_id
             LIMIT 1"
        );

        $detailStatement->bindValue(":website_id", $viewWebsiteId, PDO::PARAM_INT);

        $detailStatement->execute();

        $selectedWebsite = $detailStatement->fetch(PDO::FETCH_ASSOC) ?: null;

    } catch (Throwable $exception) {

        $selectedWebsite = null;

    }

}


/* ==========================================
   07. PAGE CONFIGURATION
========================================== */

$page_title = "Websites Overview | XD Chat";

$page_heading = "Websites Overview";

$page_description = "View all websites connected across XD Chat accounts.";

$active_menu = "websites";

require_once 'includes/header.php';
?>

<section class="xd-sa-users-panel xd-sa-websites-panel">

    <div class="xd-sa-users-header">

        <div>
            <h2>All Websites</h2>
            <p><?php echo htmlspecialchars(number_format($totalWebsites)); ?> websites found.</p>
        </div>

    </div>

    <form class="xd-sa-filter-bar xd-sa-website-filter"
          method="GET"
          action="websites.php">

        <div class="xd-sa-filter-field wide">
            <label for="search">Search</label>
            <input type="text"
                   id="search"
                   name="search"
                   value="<?php echo htmlspecialchars($search); ?>"
                   placeholder="Website, domain, owner">
        </div>

        <div class="xd-sa-filter-field">
            <label for="status">Status</label>
            <select id="status" name="status">
                <option value="all" <?php echo $statusFilter === "all" ? "selected" : ""; ?>>All</option>
                <option value="active" <?php echo $statusFilter === "active" ? "selected" : ""; ?>>Active</option>
                <option value="inactive" <?php echo $statusFilter === "inactive" ? "selected" : ""; ?>>Inactive</option>
            </select>
        </div>

        <div class="xd-sa-filter-field">
            <label for="owner_id">Owner</label>
            <select id="owner_id" name="owner_id">
                <option value="0">All Owners</option>
                <?php foreach ($owners as $owner) { ?>
                    <option value="<?php echo htmlspecialchars((string) $owner["id"]); ?>"
                        <?php echo $ownerFilter === (int) $owner["id"] ? "selected" : ""; ?>>
                        <?php echo htmlspecialchars($owner["full_name"] . " - " . $owner["email"]); ?>
                    </option>
                <?php } ?>
            </select>
        </div>

        <div class="xd-sa-filter-actions">
            <button type="submit">Search</button>
            <a href="websites.php">Clear</a>
        </div>

    </form>

    <div class="xd-sa-table-wrap">

        <table class="xd-sa-users-table xd-sa-websites-table">

            <thead>
                <tr>
                    <th>Website</th>
                    <th>Domain / URL</th>
                    <th>Owner</th>
                    <th>Status</th>
                    <th>Widgets</th>
                    <th>Chats</th>
                    <th>Created</th>
                    <th>Action</th>
                </tr>
            </thead>

            <tbody>

                <?php if (!empty($websites)) { ?>

                    <?php foreach ($websites as $website) { ?>

                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($website["website_name"] ?? "Untitled Website"); ?></strong>
                            </td>
                            <td>
                                <span class="xd-sa-url-text">
                                    <?php echo htmlspecialchars($website["domain"] ?? "-"); ?>
                                </span>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($website["owner_name"]); ?></strong>
                                <span class="xd-sa-table-subtext">
                                    <?php echo htmlspecialchars($website["owner_email"]); ?>
                                </span>
                            </td>
                            <td>
                                <span class="xd-sa-badge status <?php echo htmlspecialchars($website["status"]); ?>">
                                    <?php echo htmlspecialchars(ucfirst($website["status"])); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars(number_format((int) $website["widgets_count"])); ?></td>
                            <td><?php echo htmlspecialchars(number_format((int) $website["chats_count"])); ?></td>
                            <td><?php echo htmlspecialchars(date("d M Y", strtotime($website["created_at"]))); ?></td>
                            <td>
                                <a class="xd-sa-action-link"
                                   href="<?php echo htmlspecialchars(getSuperAdminWebsitePageUrl([
                                       "view_website_id" => $website["id"]
                                   ])); ?>">
                                    View Details
                                </a>
                            </td>
                        </tr>

                    <?php } ?>

                <?php } else { ?>

                    <tr>
                        <td colspan="8">
                            <div class="xd-sa-empty-state">
                                <strong>No websites found.</strong>
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
                <a href="<?php echo htmlspecialchars(getSuperAdminWebsitePageUrl([
                    "page" => $currentPage - 1
                ])); ?>">Previous</a>
            <?php } ?>

            <?php if ($currentPage < $totalPages) { ?>
                <a href="<?php echo htmlspecialchars(getSuperAdminWebsitePageUrl([
                    "page" => $currentPage + 1
                ])); ?>">Next</a>
            <?php } ?>
        </div>

    </div>

</section>


<?php if ($selectedWebsite) { ?>

    <section class="xd-sa-user-detail xd-sa-website-detail">

        <div class="xd-sa-users-header">
            <div>
                <h2><?php echo htmlspecialchars($selectedWebsite["website_name"] ?? "Untitled Website"); ?></h2>
                <p><?php echo htmlspecialchars($selectedWebsite["domain"] ?? "-"); ?></p>
            </div>
            <a href="<?php echo htmlspecialchars(getSuperAdminWebsitePageUrl([
                "view_website_id" => null
            ])); ?>">Close</a>
        </div>

        <div class="xd-sa-detail-grid">
            <div>
                <span>Status</span>
                <strong><?php echo htmlspecialchars(ucfirst($selectedWebsite["status"])); ?></strong>
            </div>
            <div>
                <span>Owner</span>
                <strong><?php echo htmlspecialchars($selectedWebsite["owner_name"]); ?></strong>
            </div>
            <div>
                <span>Owner Email</span>
                <strong><?php echo htmlspecialchars($selectedWebsite["owner_email"]); ?></strong>
            </div>
            <div>
                <span>Widgets</span>
                <strong><?php echo htmlspecialchars(number_format((int) $selectedWebsite["widgets_count"])); ?></strong>
            </div>
            <div>
                <span>Chats</span>
                <strong><?php echo htmlspecialchars(number_format((int) $selectedWebsite["chats_count"])); ?></strong>
            </div>
            <div>
                <span>Messages</span>
                <strong><?php echo htmlspecialchars(number_format((int) $selectedWebsite["messages_count"])); ?></strong>
            </div>
            <div>
                <span>Created</span>
                <strong><?php echo htmlspecialchars(date("d M Y", strtotime($selectedWebsite["created_at"]))); ?></strong>
            </div>
            <div>
                <span>Updated</span>
                <strong>Not tracked</strong>
            </div>
            <div class="xd-sa-detail-wide">
                <span>Website Key</span>
                <strong><?php echo htmlspecialchars($selectedWebsite["widget_key"] ?? "-"); ?></strong>
            </div>
        </div>

    </section>

<?php } ?>

<?php require_once 'includes/footer.php'; ?>
