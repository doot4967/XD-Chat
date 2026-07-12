<?php
/*
==================================================
Project : XD Chat
Version : 2.0.0
File    : widgets.php
Module  : Super Admin Widgets Overview
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
    "active",
    "inactive"
];

$allowedThemes = [
    "all",
    "light",
    "dark"
];

$allowedPositions = [
    "all",
    "bottom-right",
    "bottom-left"
];

$search = trim($_GET["search"] ?? "");

$statusFilter = $_GET["status"] ?? "all";

$themeFilter = $_GET["theme"] ?? "all";

$positionFilter = $_GET["position"] ?? "all";

$ownerFilter = (int) ($_GET["owner_id"] ?? 0);

$currentPage = (int) ($_GET["page"] ?? 1);

$perPage = 10;

if (!in_array($statusFilter, $allowedStatuses, true)) {

    $statusFilter = "all";

}

if (!in_array($themeFilter, $allowedThemes, true)) {

    $themeFilter = "all";

}

if (!in_array($positionFilter, $allowedPositions, true)) {

    $positionFilter = "all";

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

function buildSuperAdminWidgetFilters(
    string $search,
    string $statusFilter,
    string $themeFilter,
    string $positionFilter,
    int $ownerFilter,
    array &$params
): string
{

    $where = [];

    if ($search !== "") {

        $where[] = "(
            widgets.widget_name LIKE :search
            OR widgets.widget_key LIKE :search
            OR websites.website_name LIKE :search
            OR websites.domain LIKE :search
            OR users.full_name LIKE :search
            OR users.email LIKE :search
        )";

        $params[":search"] = "%" . $search . "%";

    }

    if ($statusFilter !== "all") {

        $where[] = "widgets.status = :status";

        $params[":status"] = $statusFilter;

    }

    if ($themeFilter !== "all") {

        $where[] = "widgets.theme = :theme";

        $params[":theme"] = $themeFilter;

    }

    if ($positionFilter !== "all") {

        $where[] = "widgets.position = :position";

        $params[":position"] = $positionFilter;

    }

    if ($ownerFilter > 0) {

        $where[] = "widgets.user_id = :owner_id";

        $params[":owner_id"] = $ownerFilter;

    }

    if (empty($where)) {

        return "";

    }

    return " WHERE " . implode(" AND ", $where);

}


function formatSuperAdminWidgetValue(string $value): string
{

    return ucwords(str_replace("-", " ", $value));

}


function getSuperAdminWidgetPageUrl(array $overrides = []): string
{

    $query = array_merge($_GET, $overrides);

    foreach ($query as $key => $value) {

        if ($value === "" || $value === null || $value === "all" || $value === 0 || $value === "0") {

            unset($query[$key]);

        }

    }

    return "widgets.php" . (!empty($query) ? "?" . http_build_query($query) : "");

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
         INNER JOIN widgets
            ON widgets.user_id = users.id
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
   05. LOAD WIDGETS
========================================== */

$queryParams = [];

$whereSql = buildSuperAdminWidgetFilters(
    $search,
    $statusFilter,
    $themeFilter,
    $positionFilter,
    $ownerFilter,
    $queryParams
);

$widgets = [];

$totalWidgets = 0;

$totalPages = 1;

try {

    $countStatement = $pdo->prepare(
        "SELECT COUNT(*)
         FROM widgets
         INNER JOIN websites
            ON websites.id = widgets.website_id
         INNER JOIN users
            ON users.id = widgets.user_id"
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

    $totalWidgets = (int) $countStatement->fetchColumn();

    $totalPages = max(1, (int) ceil($totalWidgets / $perPage));

    if ($currentPage > $totalPages) {

        $currentPage = $totalPages;

        $offset = ($currentPage - 1) * $perPage;

    }

    $widgetStatement = $pdo->prepare(
        "SELECT
            widgets.id,
            widgets.widget_name,
            widgets.status,
            widgets.theme,
            widgets.position,
            widgets.created_at,
            websites.website_name,
            websites.domain,
            users.full_name AS owner_name,
            users.email AS owner_email
         FROM widgets
         INNER JOIN websites
            ON websites.id = widgets.website_id
         INNER JOIN users
            ON users.id = widgets.user_id"
        . $whereSql .
        " ORDER BY widgets.created_at DESC, widgets.id DESC
         LIMIT :limit OFFSET :offset"
    );

    foreach ($queryParams as $key => $value) {

        $widgetStatement->bindValue(
            $key,
            $value,
            $key === ":owner_id" ? PDO::PARAM_INT : PDO::PARAM_STR
        );

    }

    $widgetStatement->bindValue(":limit", $perPage, PDO::PARAM_INT);

    $widgetStatement->bindValue(":offset", $offset, PDO::PARAM_INT);

    $widgetStatement->execute();

    $widgets = $widgetStatement->fetchAll(PDO::FETCH_ASSOC);

} catch (Throwable $exception) {

    $widgets = [];

    $totalWidgets = 0;

    $totalPages = 1;

}


/* ==========================================
   06. LOAD WIDGET DETAILS
========================================== */

$viewWidgetId = isset($_GET["view_widget_id"]) ? (int) $_GET["view_widget_id"] : 0;

$selectedWidget = null;

if ($viewWidgetId > 0) {

    try {

        $detailStatement = $pdo->prepare(
            "SELECT
                widgets.id,
                widgets.widget_name,
                widgets.widget_key,
                widgets.status,
                widgets.theme,
                widgets.position,
                widgets.widget_color,
                widgets.widget_icon,
                widgets.display_order,
                widgets.is_default,
                widgets.created_at,
                widgets.updated_at,
                websites.website_name,
                websites.domain,
                users.full_name AS owner_name,
                users.email AS owner_email
             FROM widgets
             INNER JOIN websites
                ON websites.id = widgets.website_id
             INNER JOIN users
                ON users.id = widgets.user_id
             WHERE widgets.id = :widget_id
             LIMIT 1"
        );

        $detailStatement->bindValue(":widget_id", $viewWidgetId, PDO::PARAM_INT);

        $detailStatement->execute();

        $selectedWidget = $detailStatement->fetch(PDO::FETCH_ASSOC) ?: null;

    } catch (Throwable $exception) {

        $selectedWidget = null;

    }

}


/* ==========================================
   07. PAGE CONFIGURATION
========================================== */

$page_title = getPlatformPageTitle($pdo, "Widgets Overview");

$page_heading = "Widgets Overview";

$page_description = "View all chat widgets connected across XD Chat accounts.";

$active_menu = "widgets";

require_once 'includes/header.php';
?>

<section class="xd-sa-users-panel xd-sa-widgets-panel">

    <div class="xd-sa-users-header">

        <div>
            <h2>All Widgets</h2>
            <p><?php echo htmlspecialchars(number_format($totalWidgets)); ?> widgets found.</p>
        </div>

    </div>

    <form class="xd-sa-filter-bar xd-sa-widget-filter"
          method="GET"
          action="widgets.php">

        <div class="xd-sa-filter-field wide">
            <label for="search">Search</label>
            <input type="text"
                   id="search"
                   name="search"
                   value="<?php echo htmlspecialchars($search); ?>"
                   placeholder="Widget, key, website, owner">
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
            <label for="theme">Theme</label>
            <select id="theme" name="theme">
                <option value="all" <?php echo $themeFilter === "all" ? "selected" : ""; ?>>All</option>
                <option value="light" <?php echo $themeFilter === "light" ? "selected" : ""; ?>>Light</option>
                <option value="dark" <?php echo $themeFilter === "dark" ? "selected" : ""; ?>>Dark</option>
            </select>
        </div>

        <div class="xd-sa-filter-field">
            <label for="position">Position</label>
            <select id="position" name="position">
                <option value="all" <?php echo $positionFilter === "all" ? "selected" : ""; ?>>All</option>
                <option value="bottom-right" <?php echo $positionFilter === "bottom-right" ? "selected" : ""; ?>>Bottom Right</option>
                <option value="bottom-left" <?php echo $positionFilter === "bottom-left" ? "selected" : ""; ?>>Bottom Left</option>
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
            <a href="widgets.php">Clear</a>
        </div>

    </form>

    <div class="xd-sa-table-wrap">

        <table class="xd-sa-users-table xd-sa-widgets-table">

            <thead>
                <tr>
                    <th>Widget</th>
                    <th>Website / Domain</th>
                    <th>Owner</th>
                    <th>Status</th>
                    <th>Theme</th>
                    <th>Position</th>
                    <th>Created</th>
                    <th>Action</th>
                </tr>
            </thead>

            <tbody>

                <?php if (!empty($widgets)) { ?>

                    <?php foreach ($widgets as $widget) { ?>

                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($widget["widget_name"]); ?></strong>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($widget["website_name"]); ?></strong>
                                <span class="xd-sa-table-subtext">
                                    <?php echo htmlspecialchars($widget["domain"]); ?>
                                </span>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($widget["owner_name"]); ?></strong>
                                <span class="xd-sa-table-subtext">
                                    <?php echo htmlspecialchars($widget["owner_email"]); ?>
                                </span>
                            </td>
                            <td>
                                <span class="xd-sa-badge status <?php echo htmlspecialchars($widget["status"]); ?>">
                                    <?php echo htmlspecialchars(ucfirst($widget["status"])); ?>
                                </span>
                            </td>
                            <td>
                                <span class="xd-sa-widget-meta-badge">
                                    <?php echo htmlspecialchars(formatSuperAdminWidgetValue($widget["theme"])); ?>
                                </span>
                            </td>
                            <td>
                                <span class="xd-sa-widget-meta-badge">
                                    <?php echo htmlspecialchars(formatSuperAdminWidgetValue($widget["position"])); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars(date("d M Y", strtotime($widget["created_at"]))); ?></td>
                            <td>
                                <a class="xd-sa-action-link"
                                   href="<?php echo htmlspecialchars(getSuperAdminWidgetPageUrl([
                                       "view_widget_id" => $widget["id"]
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
                                <strong>No widgets found.</strong>
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
                <a href="<?php echo htmlspecialchars(getSuperAdminWidgetPageUrl([
                    "page" => $currentPage - 1
                ])); ?>">Previous</a>
            <?php } ?>

            <?php if ($currentPage < $totalPages) { ?>
                <a href="<?php echo htmlspecialchars(getSuperAdminWidgetPageUrl([
                    "page" => $currentPage + 1
                ])); ?>">Next</a>
            <?php } ?>
        </div>

    </div>

</section>


<?php if ($selectedWidget) { ?>

    <section class="xd-sa-user-detail xd-sa-widget-detail">

        <div class="xd-sa-users-header">
            <div>
                <h2><?php echo htmlspecialchars($selectedWidget["widget_name"]); ?></h2>
                <p><?php echo htmlspecialchars($selectedWidget["website_name"] . " - " . $selectedWidget["domain"]); ?></p>
            </div>
            <a href="<?php echo htmlspecialchars(getSuperAdminWidgetPageUrl([
                "view_widget_id" => null
            ])); ?>">Close</a>
        </div>

        <div class="xd-sa-detail-grid">
            <div>
                <span>Status</span>
                <strong><?php echo htmlspecialchars(ucfirst($selectedWidget["status"])); ?></strong>
            </div>
            <div>
                <span>Theme</span>
                <strong><?php echo htmlspecialchars(formatSuperAdminWidgetValue($selectedWidget["theme"])); ?></strong>
            </div>
            <div>
                <span>Position</span>
                <strong><?php echo htmlspecialchars(formatSuperAdminWidgetValue($selectedWidget["position"])); ?></strong>
            </div>
            <div>
                <span>Owner</span>
                <strong><?php echo htmlspecialchars($selectedWidget["owner_name"]); ?></strong>
            </div>
            <div>
                <span>Owner Email</span>
                <strong><?php echo htmlspecialchars($selectedWidget["owner_email"]); ?></strong>
            </div>
            <div>
                <span>Display Order</span>
                <strong><?php echo htmlspecialchars((string) $selectedWidget["display_order"]); ?></strong>
            </div>
            <div>
                <span>Default Widget</span>
                <strong><?php echo (int) $selectedWidget["is_default"] === 1 ? "Yes" : "No"; ?></strong>
            </div>
            <div>
                <span>Created</span>
                <strong><?php echo htmlspecialchars(date("d M Y", strtotime($selectedWidget["created_at"]))); ?></strong>
            </div>
            <div>
                <span>Updated</span>
                <strong><?php echo htmlspecialchars(date("d M Y", strtotime($selectedWidget["updated_at"]))); ?></strong>
            </div>
            <div>
                <span>Widget Color</span>
                <strong><?php echo htmlspecialchars($selectedWidget["widget_color"] ?? "-"); ?></strong>
            </div>
            <div>
                <span>Widget Icon</span>
                <strong><?php echo htmlspecialchars($selectedWidget["widget_icon"] ?? "-"); ?></strong>
            </div>
            <div>
                <span>Chats</span>
                <strong>Not tracked at widget level</strong>
            </div>
            <div>
                <span>Messages</span>
                <strong>Not tracked at widget level</strong>
            </div>
            <div class="xd-sa-detail-wide">
                <span>Widget Key</span>
                <strong><?php echo htmlspecialchars($selectedWidget["widget_key"]); ?></strong>
            </div>
        </div>

    </section>

<?php } ?>

<?php require_once 'includes/footer.php'; ?>
