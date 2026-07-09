<?php
/*
==================================================
Project : XD Chat
Version : 2.0.0
File    : users.php
Module  : Super Admin Users Management
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
   02. STATUS UPDATE HANDLER
========================================== */

$allowedTargetStatuses = [
    "active",
    "inactive"
];

$requestMethod = $_SERVER["REQUEST_METHOD"] ?? "GET";

if ($requestMethod === "POST") {

    $action = $_POST["action"] ?? "";

    $csrfToken = $_POST["csrf_token"] ?? "";

    $targetUserId = (int) ($_POST["user_id"] ?? 0);

    $targetStatus = $_POST["target_status"] ?? "";

    $statusMessageType = "error";

    $statusMessage = "Invalid request. Please try again.";

    if (
        $action === "update_status"
        && verifyCsrfToken($csrfToken)
        && $targetUserId > 0
        && in_array($targetStatus, $allowedTargetStatuses, true)
    ) {

        if (
            $targetUserId === (int) ($_SESSION["user_id"] ?? 0)
            && $targetStatus === "inactive"
        ) {

            $statusMessage = "You cannot deactivate your own Super Admin account.";

        } else {

            try {

                $checkStatement = $pdo->prepare(
                    "SELECT id, status
                     FROM users
                     WHERE id = :user_id
                     LIMIT 1"
                );

                $checkStatement->bindValue(":user_id", $targetUserId, PDO::PARAM_INT);

                $checkStatement->execute();

                $targetUser = $checkStatement->fetch(PDO::FETCH_ASSOC);

                if (!$targetUser) {

                    $statusMessage = "User not found.";

                } else {

                    $updateStatement = $pdo->prepare(
                        "UPDATE users
                         SET status = :status
                         WHERE id = :user_id"
                    );

                    $updateStatement->bindValue(":status", $targetStatus);

                    $updateStatement->bindValue(":user_id", $targetUserId, PDO::PARAM_INT);

                    $updateStatement->execute();

                    $statusMessageType = "success";

                    $statusMessage = $targetStatus === "active"
                        ? "User activated successfully."
                        : "User deactivated successfully.";

                }

            } catch (Throwable $exception) {

                $statusMessage = "Unable to update user status right now.";

            }

        }

    }

    $redirectParams = $_GET;

    $redirectParams["status_message"] = $statusMessage;

    $redirectParams["status_type"] = $statusMessageType;

    header(
        "Location: users.php"
        . (!empty($redirectParams) ? "?" . http_build_query($redirectParams) : "")
    );

    exit;

}


/* ==========================================
   03. FILTER CONFIGURATION
========================================== */

$allowedRoles = [
    "all",
    "super_admin",
    "admin",
    "agent"
];

$allowedStatuses = [
    "all",
    "active",
    "inactive"
];

$search = trim($_GET["search"] ?? "");

$roleFilter = $_GET["role"] ?? "all";

$statusFilter = $_GET["status"] ?? "all";

$currentPage = (int) ($_GET["page"] ?? 1);

$perPage = 10;

if (!in_array($roleFilter, $allowedRoles, true)) {

    $roleFilter = "all";

}

if (!in_array($statusFilter, $allowedStatuses, true)) {

    $statusFilter = "all";

}

if ($currentPage < 1) {

    $currentPage = 1;

}

$offset = ($currentPage - 1) * $perPage;


/* ==========================================
   04. QUERY HELPERS
========================================== */

function buildSuperAdminUserFilters(string $search, string $roleFilter, string $statusFilter, array &$params): string
{

    $where = [];

    if ($search !== "") {

        $where[] = "(u.full_name LIKE :search OR u.email LIKE :search)";

        $params[":search"] = "%" . $search . "%";

    }

    if ($roleFilter !== "all") {

        $where[] = "u.role = :role";

        $params[":role"] = $roleFilter;

    }

    if ($statusFilter !== "all") {

        $where[] = "u.status = :status";

        $params[":status"] = $statusFilter;

    }

    if (empty($where)) {

        return "";

    }

    return " WHERE " . implode(" AND ", $where);

}


function getSuperAdminRoleLabel(string $role): string
{

    $labels = [
        "super_admin" => "Super Admin",
        "admin" => "Admin",
        "agent" => "Agent"
    ];

    return $labels[$role] ?? ucfirst($role);

}


function getSuperAdminUsersPageUrl(array $overrides = []): string
{

    $query = array_merge($_GET, $overrides);

    unset(
        $query["status_message"],
        $query["status_type"]
    );

    foreach ($query as $key => $value) {

        if ($value === "" || $value === null || $value === "all") {

            unset($query[$key]);

        }

    }

    return "users.php" . (!empty($query) ? "?" . http_build_query($query) : "");

}


/* ==========================================
   05. LOAD USERS
========================================== */

$queryParams = [];

$whereSql = buildSuperAdminUserFilters(
    $search,
    $roleFilter,
    $statusFilter,
    $queryParams
);

$users = [];

$totalUsers = 0;

$totalPages = 1;

try {

    $countStatement = $pdo->prepare(
        "SELECT COUNT(*)
         FROM users u" . $whereSql
    );

    foreach ($queryParams as $key => $value) {

        $countStatement->bindValue($key, $value);

    }

    $countStatement->execute();

    $totalUsers = (int) $countStatement->fetchColumn();

    $totalPages = max(1, (int) ceil($totalUsers / $perPage));

    if ($currentPage > $totalPages) {

        $currentPage = $totalPages;

        $offset = ($currentPage - 1) * $perPage;

    }

    $usersStatement = $pdo->prepare(
        "SELECT
            u.id,
            u.full_name,
            u.email,
            u.role,
            u.status,
            u.created_at,
            COALESCE(websites.total, 0) AS websites_count,
            COALESCE(widgets.total, 0) AS widgets_count,
            COALESCE(chats.total, 0) AS chats_count
         FROM users u
         LEFT JOIN (
            SELECT user_id, COUNT(*) AS total
            FROM websites
            GROUP BY user_id
         ) websites ON websites.user_id = u.id
         LEFT JOIN (
            SELECT user_id, COUNT(*) AS total
            FROM widgets
            GROUP BY user_id
         ) widgets ON widgets.user_id = u.id
         LEFT JOIN (
            SELECT w.user_id, COUNT(c.id) AS total
            FROM websites w
            LEFT JOIN chats c ON c.website_id = w.id
            GROUP BY w.user_id
         ) chats ON chats.user_id = u.id"
        . $whereSql .
        " ORDER BY u.created_at DESC, u.id DESC
         LIMIT :limit OFFSET :offset"
    );

    foreach ($queryParams as $key => $value) {

        $usersStatement->bindValue($key, $value);

    }

    $usersStatement->bindValue(":limit", $perPage, PDO::PARAM_INT);

    $usersStatement->bindValue(":offset", $offset, PDO::PARAM_INT);

    $usersStatement->execute();

    $users = $usersStatement->fetchAll(PDO::FETCH_ASSOC);

} catch (Throwable $exception) {

    $users = [];

    $totalUsers = 0;

    $totalPages = 1;

}


/* ==========================================
   06. LOAD USER DETAILS
========================================== */

$viewUserId = isset($_GET["view_user_id"]) ? (int) $_GET["view_user_id"] : 0;

$selectedUser = null;

if ($viewUserId > 0) {

    try {

        $detailStatement = $pdo->prepare(
            "SELECT
                u.id,
                u.full_name,
                u.email,
                u.role,
                u.status,
                u.created_at,
                u.updated_at,
                COALESCE(websites.total, 0) AS websites_count,
                COALESCE(widgets.total, 0) AS widgets_count,
                COALESCE(chats.total, 0) AS chats_count,
                COALESCE(messages.total, 0) AS messages_count
             FROM users u
             LEFT JOIN (
                SELECT user_id, COUNT(*) AS total
                FROM websites
                GROUP BY user_id
             ) websites ON websites.user_id = u.id
             LEFT JOIN (
                SELECT user_id, COUNT(*) AS total
                FROM widgets
                GROUP BY user_id
             ) widgets ON widgets.user_id = u.id
             LEFT JOIN (
                SELECT w.user_id, COUNT(c.id) AS total
                FROM websites w
                LEFT JOIN chats c ON c.website_id = w.id
                GROUP BY w.user_id
             ) chats ON chats.user_id = u.id
             LEFT JOIN (
                SELECT w.user_id, COUNT(m.id) AS total
                FROM websites w
                LEFT JOIN chats c ON c.website_id = w.id
                LEFT JOIN messages m ON m.chat_id = c.id
                GROUP BY w.user_id
             ) messages ON messages.user_id = u.id
             WHERE u.id = :user_id
             LIMIT 1"
        );

        $detailStatement->bindValue(":user_id", $viewUserId, PDO::PARAM_INT);

        $detailStatement->execute();

        $selectedUser = $detailStatement->fetch(PDO::FETCH_ASSOC) ?: null;

    } catch (Throwable $exception) {

        $selectedUser = null;

    }

}


/* ==========================================
   07. PAGE CONFIGURATION
========================================== */

$page_title = "Users Management | XD Chat";

$page_heading = "Users Management";

$page_description = "View platform users, roles, and account activity.";

$active_menu = "users";

$statusMessage = $_GET["status_message"] ?? "";

$statusMessageType = $_GET["status_type"] ?? "";

if (!in_array($statusMessageType, [
    "success",
    "error"
], true)) {

    $statusMessageType = "error";

}

require_once 'includes/header.php';
?>

<section class="xd-sa-users-panel">

    <div class="xd-sa-users-header">

        <div>
            <h2>All Users</h2>
            <p><?php echo htmlspecialchars(number_format($totalUsers)); ?> users found.</p>
        </div>

    </div>

    <?php if ($statusMessage !== "") { ?>

        <div class="xd-sa-alert <?php echo htmlspecialchars($statusMessageType); ?>">
            <?php echo htmlspecialchars($statusMessage); ?>
        </div>

    <?php } ?>

    <form class="xd-sa-filter-bar"
          method="GET"
          action="users.php">

        <div class="xd-sa-filter-field wide">
            <label for="search">Search</label>
            <input type="text"
                   id="search"
                   name="search"
                   value="<?php echo htmlspecialchars($search); ?>"
                   placeholder="Search by name or email">
        </div>

        <div class="xd-sa-filter-field">
            <label for="role">Role</label>
            <select id="role" name="role">
                <option value="all" <?php echo $roleFilter === "all" ? "selected" : ""; ?>>All</option>
                <option value="super_admin" <?php echo $roleFilter === "super_admin" ? "selected" : ""; ?>>Super Admin</option>
                <option value="admin" <?php echo $roleFilter === "admin" ? "selected" : ""; ?>>Admin</option>
                <option value="agent" <?php echo $roleFilter === "agent" ? "selected" : ""; ?>>Agent</option>
            </select>
        </div>

        <div class="xd-sa-filter-field">
            <label for="status">Status</label>
            <select id="status" name="status">
                <option value="all" <?php echo $statusFilter === "all" ? "selected" : ""; ?>>All</option>
                <option value="active" <?php echo $statusFilter === "active" ? "selected" : ""; ?>>Active</option>
                <option value="inactive" <?php echo $statusFilter === "inactive" ? "selected" : ""; ?>>Inactive</option>
            </select>
        </div>

        <div class="xd-sa-filter-actions">
            <button type="submit">Search</button>
            <a href="users.php">Clear</a>
        </div>

    </form>

    <div class="xd-sa-table-wrap">

        <table class="xd-sa-users-table">

            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Websites</th>
                    <th>Widgets</th>
                    <th>Chats</th>
                    <th>Joined</th>
                    <th>Action</th>
                </tr>
            </thead>

            <tbody>

                <?php if (!empty($users)) { ?>

                    <?php foreach ($users as $user) { ?>

                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($user["full_name"]); ?></strong>
                            </td>
                            <td><?php echo htmlspecialchars($user["email"]); ?></td>
                            <td>
                                <span class="xd-sa-badge role">
                                    <?php echo htmlspecialchars(getSuperAdminRoleLabel($user["role"])); ?>
                                </span>
                            </td>
                            <td>
                                <span class="xd-sa-badge status <?php echo htmlspecialchars($user["status"]); ?>">
                                    <?php echo htmlspecialchars(ucfirst($user["status"])); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars(number_format((int) $user["websites_count"])); ?></td>
                            <td><?php echo htmlspecialchars(number_format((int) $user["widgets_count"])); ?></td>
                            <td><?php echo htmlspecialchars(number_format((int) $user["chats_count"])); ?></td>
                            <td><?php echo htmlspecialchars(date("d M Y", strtotime($user["created_at"]))); ?></td>
                            <td>
                                <a class="xd-sa-action-link"
                                   href="<?php echo htmlspecialchars(getSuperAdminUsersPageUrl([
                                       "view_user_id" => $user["id"]
                                   ])); ?>">
                                    View Details
                                </a>

                                <?php if ((int) $user["id"] === (int) ($_SESSION["user_id"] ?? 0)) { ?>

                                    <span class="xd-sa-current-account">
                                        Current account
                                    </span>

                                <?php } else { ?>

                                    <form class="xd-sa-status-form"
                                          method="POST"
                                          action="<?php echo htmlspecialchars(getSuperAdminUsersPageUrl()); ?>"
                                          onsubmit="return confirm('<?php echo $user["status"] === "active" ? "Deactivate this user?" : "Activate this user?"; ?>');">

                                        <input type="hidden"
                                               name="csrf_token"
                                               value="<?php echo htmlspecialchars(getCsrfToken()); ?>">

                                        <input type="hidden"
                                               name="action"
                                               value="update_status">

                                        <input type="hidden"
                                               name="user_id"
                                               value="<?php echo htmlspecialchars((string) $user["id"]); ?>">

                                        <input type="hidden"
                                               name="target_status"
                                               value="<?php echo $user["status"] === "active" ? "inactive" : "active"; ?>">

                                        <button type="submit"
                                                class="xd-sa-status-action <?php echo $user["status"] === "active" ? "danger" : "success"; ?>">
                                            <?php echo $user["status"] === "active" ? "Deactivate" : "Activate"; ?>
                                        </button>

                                    </form>

                                <?php } ?>
                            </td>
                        </tr>

                    <?php } ?>

                <?php } else { ?>

                    <tr>
                        <td colspan="9">
                            <div class="xd-sa-empty-state">
                                <strong>No users found.</strong>
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
                <a href="<?php echo htmlspecialchars(getSuperAdminUsersPageUrl([
                    "page" => $currentPage - 1
                ])); ?>">Previous</a>
            <?php } ?>

            <?php if ($currentPage < $totalPages) { ?>
                <a href="<?php echo htmlspecialchars(getSuperAdminUsersPageUrl([
                    "page" => $currentPage + 1
                ])); ?>">Next</a>
            <?php } ?>
        </div>

    </div>

</section>


<?php if ($selectedUser) { ?>

    <section class="xd-sa-user-detail">

        <div class="xd-sa-users-header">
            <div>
                <h2><?php echo htmlspecialchars($selectedUser["full_name"]); ?></h2>
                <p><?php echo htmlspecialchars($selectedUser["email"]); ?></p>
            </div>
            <a href="<?php echo htmlspecialchars(getSuperAdminUsersPageUrl([
                "view_user_id" => null
            ])); ?>">Close</a>
        </div>

        <div class="xd-sa-detail-grid">
            <div>
                <span>Role</span>
                <strong><?php echo htmlspecialchars(getSuperAdminRoleLabel($selectedUser["role"])); ?></strong>
            </div>
            <div>
                <span>Status</span>
                <strong><?php echo htmlspecialchars(ucfirst($selectedUser["status"])); ?></strong>
            </div>
            <div>
                <span>Websites</span>
                <strong><?php echo htmlspecialchars(number_format((int) $selectedUser["websites_count"])); ?></strong>
            </div>
            <div>
                <span>Widgets</span>
                <strong><?php echo htmlspecialchars(number_format((int) $selectedUser["widgets_count"])); ?></strong>
            </div>
            <div>
                <span>Chats</span>
                <strong><?php echo htmlspecialchars(number_format((int) $selectedUser["chats_count"])); ?></strong>
            </div>
            <div>
                <span>Messages</span>
                <strong><?php echo htmlspecialchars(number_format((int) $selectedUser["messages_count"])); ?></strong>
            </div>
            <div>
                <span>Joined</span>
                <strong><?php echo htmlspecialchars(date("d M Y", strtotime($selectedUser["created_at"]))); ?></strong>
            </div>
            <div>
                <span>Updated</span>
                <strong><?php echo htmlspecialchars(date("d M Y", strtotime($selectedUser["updated_at"]))); ?></strong>
            </div>
        </div>

    </section>

<?php } ?>

<?php require_once 'includes/footer.php'; ?>
