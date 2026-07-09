<?php
/*
==================================================
Project : XD Chat
Version : 2.0.0
File    : audit-logs.php
Module  : Super Admin Audit Logs
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

$allowedActions = [
    "all",
    "user_activated",
    "user_deactivated"
];

$search = trim($_GET["search"] ?? "");

$actionFilter = $_GET["action"] ?? "all";

$dateFilter = trim($_GET["date"] ?? "");

$currentPage = (int) ($_GET["page"] ?? 1);

$perPage = 20;

if (!in_array($actionFilter, $allowedActions, true)) {

    $actionFilter = "all";

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

function buildSuperAdminAuditFilters(string $search, string $actionFilter, string $dateFilter, array &$params): string
{

    $where = [];

    if ($search !== "") {

        $where[] = "(
            actor_name LIKE :search
            OR target_name LIKE :search
            OR action LIKE :search
        )";

        $params[":search"] = "%" . $search . "%";

    }

    if ($actionFilter !== "all") {

        $where[] = "action = :action";

        $params[":action"] = $actionFilter;

    }

    if ($dateFilter !== "") {

        $where[] = "DATE(created_at) = :date_filter";

        $params[":date_filter"] = $dateFilter;

    }

    if (empty($where)) {

        return "";

    }

    return " WHERE " . implode(" AND ", $where);

}


function getSuperAdminAuditActionLabel(string $action): string
{

    $labels = [
        "user_activated" => "User Activated",
        "user_deactivated" => "User Deactivated"
    ];

    return $labels[$action] ?? ucwords(str_replace("_", " ", $action));

}


function getSuperAdminAuditPageUrl(array $overrides = []): string
{

    $query = array_merge($_GET, $overrides);

    foreach ($query as $key => $value) {

        if ($value === "" || $value === null || $value === "all") {

            unset($query[$key]);

        }

    }

    return "audit-logs.php" . (!empty($query) ? "?" . http_build_query($query) : "");

}


/* ==========================================
   04. LOAD AUDIT LOGS
========================================== */

$queryParams = [];

$whereSql = buildSuperAdminAuditFilters(
    $search,
    $actionFilter,
    $dateFilter,
    $queryParams
);

$auditLogs = [];

$totalLogs = 0;

$totalPages = 1;

try {

    $countStatement = $pdo->prepare(
        "SELECT COUNT(*)
         FROM audit_logs"
        . $whereSql
    );

    foreach ($queryParams as $key => $value) {

        $countStatement->bindValue($key, $value);

    }

    $countStatement->execute();

    $totalLogs = (int) $countStatement->fetchColumn();

    $totalPages = max(1, (int) ceil($totalLogs / $perPage));

    if ($currentPage > $totalPages) {

        $currentPage = $totalPages;

        $offset = ($currentPage - 1) * $perPage;

    }

    $logsStatement = $pdo->prepare(
        "SELECT
            id,
            actor_user_id,
            actor_name,
            action,
            target_type,
            target_id,
            target_name,
            description,
            ip_address,
            created_at
         FROM audit_logs"
        . $whereSql .
        " ORDER BY created_at DESC, id DESC
         LIMIT :limit OFFSET :offset"
    );

    foreach ($queryParams as $key => $value) {

        $logsStatement->bindValue($key, $value);

    }

    $logsStatement->bindValue(":limit", $perPage, PDO::PARAM_INT);

    $logsStatement->bindValue(":offset", $offset, PDO::PARAM_INT);

    $logsStatement->execute();

    $auditLogs = $logsStatement->fetchAll(PDO::FETCH_ASSOC);

} catch (Throwable $exception) {

    $auditLogs = [];

    $totalLogs = 0;

    $totalPages = 1;

}


/* ==========================================
   05. PAGE CONFIGURATION
========================================== */

$page_title = "Audit Logs | XD Chat";

$page_heading = "Audit Logs";

$page_description = "Review important Super Admin actions across the platform.";

$active_menu = "audit";

require_once 'includes/header.php';
?>

<section class="xd-sa-users-panel xd-sa-audit-panel">

    <div class="xd-sa-users-header">

        <div>
            <h2>Audit Records</h2>
            <p><?php echo htmlspecialchars(number_format($totalLogs)); ?> logs found.</p>
        </div>

    </div>

    <form class="xd-sa-filter-bar xd-sa-audit-filter"
          method="GET"
          action="audit-logs.php">

        <div class="xd-sa-filter-field wide">
            <label for="search">Search</label>
            <input type="text"
                   id="search"
                   name="search"
                   value="<?php echo htmlspecialchars($search); ?>"
                   placeholder="Actor, target, or action">
        </div>

        <div class="xd-sa-filter-field">
            <label for="action">Action</label>
            <select id="action" name="action">
                <option value="all" <?php echo $actionFilter === "all" ? "selected" : ""; ?>>All</option>
                <option value="user_activated" <?php echo $actionFilter === "user_activated" ? "selected" : ""; ?>>User Activated</option>
                <option value="user_deactivated" <?php echo $actionFilter === "user_deactivated" ? "selected" : ""; ?>>User Deactivated</option>
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
            <a href="audit-logs.php">Clear</a>
        </div>

    </form>

    <div class="xd-sa-table-wrap">

        <table class="xd-sa-users-table xd-sa-audit-table">

            <thead>
                <tr>
                    <th>Actor</th>
                    <th>Action</th>
                    <th>Target</th>
                    <th>Description</th>
                    <th>IP Address</th>
                    <th>Date / Time</th>
                </tr>
            </thead>

            <tbody>

                <?php if (!empty($auditLogs)) { ?>

                    <?php foreach ($auditLogs as $log) { ?>

                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($log["actor_name"]); ?></strong>
                            </td>
                            <td>
                                <span class="xd-sa-audit-badge <?php echo htmlspecialchars($log["action"]); ?>">
                                    <?php echo htmlspecialchars(getSuperAdminAuditActionLabel($log["action"])); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($log["target_name"] ?? "Unknown"); ?></td>
                            <td>
                                <span class="xd-sa-audit-description">
                                    <?php echo htmlspecialchars($log["description"] ?? "No description."); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($log["ip_address"] ?? "-"); ?></td>
                            <td><?php echo htmlspecialchars(date("d M Y, h:i A", strtotime($log["created_at"]))); ?></td>
                        </tr>

                    <?php } ?>

                <?php } else { ?>

                    <tr>
                        <td colspan="6">
                            <div class="xd-sa-empty-state">
                                <strong>No audit logs found.</strong>
                                <span>Important Super Admin actions will appear here.</span>
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
                <a href="<?php echo htmlspecialchars(getSuperAdminAuditPageUrl([
                    "page" => $currentPage - 1
                ])); ?>">Previous</a>
            <?php } ?>

            <?php if ($currentPage < $totalPages) { ?>
                <a href="<?php echo htmlspecialchars(getSuperAdminAuditPageUrl([
                    "page" => $currentPage + 1
                ])); ?>">Next</a>
            <?php } ?>
        </div>

    </div>

</section>

<?php require_once 'includes/footer.php'; ?>
