<?php
/*
==================================================
Project : XD Chat
Version : 2.0.0
File    : visitors.php
Module  : Visitor Management
Status  : Development
Author  : Umesh + ChatGPT
Created : 05 July 2026
==================================================
*/


/* ==========================================
   01. LOAD DEPENDENCIES
========================================== */

require_once '../database/connection.php';

require_once '../includes/functions/session.php';

require_once '../includes/functions/platform-settings.php';

requireLogin();


/* ==========================================
   02. PAGE CONFIGURATION
========================================== */

$page_title = getPlatformPageTitle($pdo, "Visitors");

$page_heading = "Visitors";

$page_description = "Track unique visitors across your websites.";

$currentUserId = (int) $_SESSION["user_id"];

$allowedStatusFilters = [
    "all",
    "open",
    "closed"
];

$search = trim($_GET["search"] ?? "");

$statusFilter = $_GET["status"] ?? "all";

if (!in_array($statusFilter, $allowedStatusFilters, true)) {
    $statusFilter = "all";
}

$websiteFilter = filter_input(
    INPUT_GET,
    "website_id",
    FILTER_VALIDATE_INT
);

if ($websiteFilter === false || $websiteFilter === null) {
    $websiteFilter = 0;
}

$page = filter_input(
    INPUT_GET,
    "page",
    FILTER_VALIDATE_INT
);

if ($page === false || $page === null || $page < 1) {
    $page = 1;
}

$perPage = 25;

$offset = ($page - 1) * $perPage;

$visitors = [];

$websites = [];

$summary = [
    "unique_visitors" => 0,
    "total_chats" => 0,
    "open_visitors" => 0,
    "identified_visitors" => 0
];

$totalRows = 0;

$totalPages = 1;

$loadError = "";


/* ==========================================
   03. HELPER FUNCTIONS
========================================== */

function xdVisitorEscape($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

function xdVisitorText($value, $fallback = "Not provided")
{
    $value = trim((string) $value);

    return ($value !== "")
        ? $value
        : $fallback;
}

function xdVisitorShortText($value, $limit = 48)
{
    $value = trim((string) $value);

    if ($value === "") {
        return "Not provided";
    }

    if (mb_strlen($value, "UTF-8") <= $limit) {
        return $value;
    }

    return mb_substr($value, 0, $limit - 3, "UTF-8") . "...";
}

function xdVisitorQueryString($overrides = [])
{
    $params = array_merge($_GET, $overrides);

    foreach ($params as $key => $value) {
        if ($value === "" || $value === null || $value === "all" || $value === 0 || $value === "0") {
            unset($params[$key]);
        }
    }

    return http_build_query($params);
}


/* ==========================================
   04. LOAD VISITOR DATA
========================================== */

try {
    $websiteStatement = $pdo->prepare(
        "SELECT id, website_name, domain
         FROM websites
         WHERE user_id = ?
         ORDER BY website_name ASC, id DESC"
    );

    $websiteStatement->execute([
        $currentUserId
    ]);

    $websites = $websiteStatement->fetchAll(PDO::FETCH_ASSOC);

    $baseWhere = [
        "websites.user_id = :user_id"
    ];

    $baseParams = [
        ":user_id" => $currentUserId
    ];

    if ($websiteFilter > 0) {
        $baseWhere[] = "websites.id = :website_id";
        $baseParams[":website_id"] = $websiteFilter;
    }

    if ($search !== "") {
        $baseWhere[] = "(
            chats.visitor_name LIKE :search
            OR chats.visitor_email LIKE :search
            OR chats.visitor_id LIKE :search
            OR websites.website_name LIKE :search
            OR websites.domain LIKE :search
        )";

        $baseParams[":search"] = "%" . $search . "%";
    }

    $whereSql = implode(" AND ", $baseWhere);

    $havingSql = "";

    if ($statusFilter === "open") {
        $havingSql = "HAVING open_chats > 0";
    }

    if ($statusFilter === "closed") {
        $havingSql = "HAVING open_chats = 0";
    }

    $visitorKeySql = "
        CASE
            WHEN chats.visitor_id IS NULL OR chats.visitor_id = ''
            THEN CONCAT('chat:', chats.id)
            ELSE CONCAT('visitor:', chats.visitor_id)
        END
    ";

    $aggregateSql = "
        SELECT
            chats.website_id,
            {$visitorKeySql} AS visitor_key,
            COUNT(*) AS total_chats,
            SUM(CASE WHEN chats.status = 'open' THEN 1 ELSE 0 END) AS open_chats,
            SUM(CASE WHEN chats.status = 'closed' THEN 1 ELSE 0 END) AS closed_chats,
            MIN(chats.created_at) AS first_seen,
            MAX(chats.created_at) AS last_seen,
            MAX(chats.id) AS latest_chat_id
        FROM chats
        INNER JOIN websites
            ON websites.id = chats.website_id
        WHERE {$whereSql}
        GROUP BY
            chats.website_id,
            visitor_key
        {$havingSql}
    ";

    $summarySql = "
        SELECT
            COUNT(*) AS unique_visitors,
            COALESCE(SUM(visitor_rows.total_chats), 0) AS total_chats,
            COALESCE(SUM(CASE WHEN visitor_rows.open_chats > 0 THEN 1 ELSE 0 END), 0) AS open_visitors,
            COALESCE(SUM(
                CASE
                    WHEN TRIM(COALESCE(latest_chats.visitor_name, '')) <> ''
                         OR TRIM(COALESCE(latest_chats.visitor_email, '')) <> ''
                    THEN 1
                    ELSE 0
                END
            ), 0) AS identified_visitors
        FROM ({$aggregateSql}) visitor_rows
        INNER JOIN chats latest_chats
            ON latest_chats.id = visitor_rows.latest_chat_id
    ";

    $summaryStatement = $pdo->prepare($summarySql);

    $summaryStatement->execute($baseParams);

    $loadedSummary = $summaryStatement->fetch(PDO::FETCH_ASSOC);

    if ($loadedSummary) {
        $summary = [
            "unique_visitors" => (int) $loadedSummary["unique_visitors"],
            "total_chats" => (int) $loadedSummary["total_chats"],
            "open_visitors" => (int) $loadedSummary["open_visitors"],
            "identified_visitors" => (int) $loadedSummary["identified_visitors"]
        ];
    }

    $countSql = "
        SELECT COUNT(*) AS total_rows
        FROM ({$aggregateSql}) visitor_rows
    ";

    $countStatement = $pdo->prepare($countSql);

    $countStatement->execute($baseParams);

    $totalRows = (int) $countStatement->fetchColumn();

    $totalPages = max(1, (int) ceil($totalRows / $perPage));

    if ($page > $totalPages) {
        $page = $totalPages;
        $offset = ($page - 1) * $perPage;
    }

    $visitorSql = "
        SELECT
            visitor_rows.*,
            latest_chats.visitor_id,
            latest_chats.visitor_name,
            latest_chats.visitor_email,
            latest_chats.visitor_page_url,
            latest_chats.visitor_referrer,
            latest_chats.visitor_browser,
            latest_chats.visitor_device,
            latest_chats.status AS latest_status,
            websites.website_name,
            websites.domain
        FROM ({$aggregateSql}) visitor_rows
        INNER JOIN chats latest_chats
            ON latest_chats.id = visitor_rows.latest_chat_id
        INNER JOIN websites
            ON websites.id = visitor_rows.website_id
        ORDER BY
            visitor_rows.last_seen DESC,
            visitor_rows.latest_chat_id DESC
        LIMIT :limit OFFSET :offset
    ";

    $visitorStatement = $pdo->prepare($visitorSql);

    foreach ($baseParams as $key => $value) {
        $paramType = is_int($value)
            ? PDO::PARAM_INT
            : PDO::PARAM_STR;

        $visitorStatement->bindValue($key, $value, $paramType);
    }

    $visitorStatement->bindValue(":limit", $perPage, PDO::PARAM_INT);
    $visitorStatement->bindValue(":offset", $offset, PDO::PARAM_INT);

    $visitorStatement->execute();

    $visitors = $visitorStatement->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $exception) {
    error_log("Visitors page load failed: " . $exception->getMessage());

    $loadError = "Visitors could not be loaded right now.";
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title><?php echo xdVisitorEscape($page_title); ?></title>

    <?php require_once 'includes/head-branding.php'; ?>

    <link rel="stylesheet" href="../assets/css/01-reset.css">
    <link rel="stylesheet" href="../assets/css/02-variables.css">
    <link rel="stylesheet" href="../assets/css/03-base.css">
    <link rel="stylesheet" href="../assets/css/10-dashboard.css">

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

    <style>
        .xd-visitors-filters{
            display:grid;
            grid-template-columns:2fr 1fr 1fr auto;
            gap:14px;
            align-items:end;
            margin-bottom:24px;
        }

        .xd-visitors-filters .xd-form-group{
            margin:0;
        }

        .xd-visitors-filters input,
        .xd-visitors-filters select{
            width:100%;
            padding:12px 14px;
            border:1px solid #dbe3ef;
            border-radius:14px;
            font-size:14px;
            outline:none;
        }

        .xd-visitors-filters input:focus,
        .xd-visitors-filters select:focus{
            border-color:#2563eb;
            box-shadow:0 0 0 3px rgba(37,99,235,.12);
        }

        .xd-filter-actions{
            display:flex;
            gap:10px;
            align-items:center;
        }

        .xd-filter-button,
        .xd-filter-reset{
            border:none;
            border-radius:14px;
            padding:12px 18px;
            font-weight:700;
            cursor:pointer;
            text-decoration:none;
            white-space:nowrap;
        }

        .xd-filter-button{
            background:#2563eb;
            color:#ffffff;
        }

        .xd-filter-reset{
            background:#eef2f7;
            color:#475569;
        }

        .xd-visitor-meta{
            display:flex;
            flex-direction:column;
            gap:5px;
        }

        .xd-visitor-meta strong{
            color:#0f172a;
        }

        .xd-visitor-meta small{
            color:#64748b;
            line-height:1.4;
        }

        .xd-visitor-page{
            max-width:240px;
            word-break:break-word;
        }

        .xd-pagination{
            display:flex;
            flex-wrap:wrap;
            gap:8px;
            justify-content:flex-end;
            margin-top:24px;
        }

        .xd-pagination a,
        .xd-pagination span{
            min-width:38px;
            padding:9px 12px;
            border-radius:12px;
            background:#eef2f7;
            color:#475569;
            text-align:center;
            text-decoration:none;
            font-weight:700;
        }

        .xd-pagination .active{
            background:#2563eb;
            color:#ffffff;
        }

        .xd-pagination .disabled{
            opacity:.45;
            cursor:not-allowed;
        }

        .xd-empty-state{
            padding:38px 18px;
            text-align:center;
            color:#64748b;
        }

        @media (max-width: 992px){
            .xd-visitors-filters{
                grid-template-columns:1fr 1fr;
            }
        }

        @media (max-width: 640px){
            .xd-visitors-filters{
                grid-template-columns:1fr;
            }

            .xd-filter-actions{
                flex-direction:column;
                align-items:stretch;
            }
        }
    </style>

</head>

<body>

<div class="xd-dashboard">

    <?php require_once 'includes/sidebar.php'; ?>

    <main class="xd-dashboard-main">

        <?php require_once 'includes/header.php'; ?>

        <!-- ==========================================
             05. SUMMARY CARDS
        =========================================== -->
        <section class="xd-dashboard-cards">

            <div class="xd-dashboard-card">

                <div class="xd-card-icon blue">
                    <i class="fa-solid fa-users"></i>
                </div>

                <span>Unique Visitors</span>

                <strong><?php echo (int) $summary["unique_visitors"]; ?></strong>

                <small>Across your websites</small>

            </div>

            <div class="xd-dashboard-card">

                <div class="xd-card-icon green">
                    <i class="fa-regular fa-comments"></i>
                </div>

                <span>Total Chat Sessions</span>

                <strong><?php echo (int) $summary["total_chats"]; ?></strong>

                <small>Visitor conversations</small>

            </div>

            <div class="xd-dashboard-card">

                <div class="xd-card-icon orange">
                    <i class="fa-solid fa-circle-dot"></i>
                </div>

                <span>Visitors With Open Chats</span>

                <strong><?php echo (int) $summary["open_visitors"]; ?></strong>

                <small>Need attention</small>

            </div>

            <div class="xd-dashboard-card">

                <div class="xd-card-icon purple">
                    <i class="fa-solid fa-id-card"></i>
                </div>

                <span>Identified Visitors</span>

                <strong><?php echo (int) $summary["identified_visitors"]; ?></strong>

                <small>Name or email available</small>

            </div>

        </section>

        <section class="xd-dashboard-panel">

            <?php if ($loadError !== "") { ?>

                <div class="xd-alert error">
                    <?php echo xdVisitorEscape($loadError); ?>
                </div>

            <?php } ?>

            <!-- ==========================================
                 06. PANEL HEADER
            ========================================== -->
            <div class="xd-panel-header">

                <h2>Website Visitors</h2>

                <a href="chats.php">Open Live Chats</a>

            </div>

            <!-- ==========================================
                 07. SEARCH AND FILTERS
            ========================================== -->
            <form class="xd-visitors-filters"
                  method="get"
                  action="visitors.php">

                <div class="xd-form-group">
                    <label for="visitorSearch">Search</label>
                    <input type="text"
                           id="visitorSearch"
                           name="search"
                           value="<?php echo xdVisitorEscape($search); ?>"
                           placeholder="Name, email, visitor ID, website...">
                </div>

                <div class="xd-form-group">
                    <label for="websiteFilter">Website</label>
                    <select id="websiteFilter"
                            name="website_id">

                        <option value="0">All Websites</option>

                        <?php foreach ($websites as $website) { ?>

                            <option value="<?php echo (int) $website["id"]; ?>"
                                <?php echo ((int) $website["id"] === (int) $websiteFilter) ? "selected" : ""; ?>>
                                <?php echo xdVisitorEscape($website["website_name"] ?: $website["domain"]); ?>
                            </option>

                        <?php } ?>

                    </select>
                </div>

                <div class="xd-form-group">
                    <label for="statusFilter">Status</label>
                    <select id="statusFilter"
                            name="status">

                        <option value="all" <?php echo ($statusFilter === "all") ? "selected" : ""; ?>>
                            All
                        </option>

                        <option value="open" <?php echo ($statusFilter === "open") ? "selected" : ""; ?>>
                            Has Open Chat
                        </option>

                        <option value="closed" <?php echo ($statusFilter === "closed") ? "selected" : ""; ?>>
                            Closed Only
                        </option>

                    </select>
                </div>

                <div class="xd-filter-actions">
                    <button type="submit"
                            class="xd-filter-button">
                        Filter
                    </button>

                    <a href="visitors.php"
                       class="xd-filter-reset">
                        Reset
                    </a>
                </div>

            </form>

            <!-- ==========================================
                 08. VISITORS TABLE
            ========================================== -->
            <?php if ($loadError === "" && count($visitors) > 0) { ?>

                <div class="xd-table-wrap">

                    <table class="xd-table">

                        <thead>

                            <tr>
                                <th>Visitor</th>
                                <th>Contact</th>
                                <th>Website</th>
                                <th>Activity</th>
                                <th>Latest Page</th>
                                <th>Environment</th>
                                <th>First Seen</th>
                                <th>Last Seen</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>

                        </thead>

                        <tbody>

                            <?php foreach ($visitors as $visitor) { ?>

                                <?php

                                $visitorName = xdVisitorText(
                                    $visitor["visitor_name"],
                                    "Visitor"
                                );

                                $visitorEmail = xdVisitorText(
                                    $visitor["visitor_email"]
                                );

                                $visitorId = trim((string) $visitor["visitor_id"]);

                                $statusClass = ((int) $visitor["open_chats"] > 0)
                                    ? "success"
                                    : "warning";

                                $statusText = ((int) $visitor["open_chats"] > 0)
                                    ? "Open"
                                    : "Closed";

                                ?>

                                <tr>

                                    <td>
                                        <div class="xd-visitor-meta">
                                            <strong><?php echo xdVisitorEscape($visitorName); ?></strong>

                                            <small>
                                                <?php echo ($visitorId !== "")
                                                    ? xdVisitorEscape($visitorId)
                                                    : "Session visitor"; ?>
                                            </small>
                                        </div>
                                    </td>

                                    <td>
                                        <?php echo xdVisitorEscape($visitorEmail); ?>
                                    </td>

                                    <td>
                                        <div class="xd-visitor-meta">
                                            <strong><?php echo xdVisitorEscape($visitor["website_name"]); ?></strong>
                                            <small><?php echo xdVisitorEscape($visitor["domain"]); ?></small>
                                        </div>
                                    </td>

                                    <td>
                                        <div class="xd-visitor-meta">
                                            <strong><?php echo (int) $visitor["total_chats"]; ?> chats</strong>
                                            <small>
                                                <?php echo (int) $visitor["open_chats"]; ?> open,
                                                <?php echo (int) $visitor["closed_chats"]; ?> closed
                                            </small>
                                        </div>
                                    </td>

                                    <td>
                                        <div class="xd-visitor-page"
                                             title="<?php echo xdVisitorEscape($visitor["visitor_page_url"]); ?>">
                                            <?php echo xdVisitorEscape(xdVisitorShortText($visitor["visitor_page_url"])); ?>
                                        </div>
                                    </td>

                                    <td>
                                        <div class="xd-visitor-meta">
                                            <strong>
                                                <?php echo xdVisitorEscape(xdVisitorShortText($visitor["visitor_device"], 32)); ?>
                                            </strong>
                                            <small>
                                                <?php echo xdVisitorEscape(xdVisitorShortText($visitor["visitor_browser"], 44)); ?>
                                            </small>
                                        </div>
                                    </td>

                                    <td>
                                        <?php echo xdVisitorEscape(date("d M Y", strtotime($visitor["first_seen"]))); ?>
                                        <br>
                                        <small><?php echo xdVisitorEscape(date("h:i A", strtotime($visitor["first_seen"]))); ?></small>
                                    </td>

                                    <td>
                                        <?php echo xdVisitorEscape(date("d M Y", strtotime($visitor["last_seen"]))); ?>
                                        <br>
                                        <small><?php echo xdVisitorEscape(date("h:i A", strtotime($visitor["last_seen"]))); ?></small>
                                    </td>

                                    <td>
                                        <span class="xd-badge <?php echo $statusClass; ?>">
                                            <?php echo $statusText; ?>
                                        </span>
                                    </td>

                                    <td>
                                        <a href="chats.php"
                                           class="xd-btn-edit"
                                           title="Open Live Chats">
                                            <i class="fa-regular fa-comments"></i>
                                            View Chats
                                        </a>
                                    </td>

                                </tr>

                            <?php } ?>

                        </tbody>

                    </table>

                </div>

                <!-- ==========================================
                     09. PAGINATION
                ========================================== -->
                <div class="xd-pagination">

                    <?php if ($page > 1) { ?>

                        <a href="visitors.php?<?php echo xdVisitorEscape(xdVisitorQueryString(["page" => $page - 1])); ?>">
                            Prev
                        </a>

                    <?php } else { ?>

                        <span class="disabled">Prev</span>

                    <?php } ?>

                    <?php

                    $startPage = max(1, $page - 2);
                    $endPage = min($totalPages, $page + 2);

                    for ($pageNumber = $startPage; $pageNumber <= $endPage; $pageNumber++) {

                    ?>

                        <?php if ($pageNumber === $page) { ?>

                            <span class="active"><?php echo (int) $pageNumber; ?></span>

                        <?php } else { ?>

                            <a href="visitors.php?<?php echo xdVisitorEscape(xdVisitorQueryString(["page" => $pageNumber])); ?>">
                                <?php echo (int) $pageNumber; ?>
                            </a>

                        <?php } ?>

                    <?php } ?>

                    <?php if ($page < $totalPages) { ?>

                        <a href="visitors.php?<?php echo xdVisitorEscape(xdVisitorQueryString(["page" => $page + 1])); ?>">
                            Next
                        </a>

                    <?php } else { ?>

                        <span class="disabled">Next</span>

                    <?php } ?>

                </div>

            <?php } elseif ($loadError === "") { ?>

                <div class="xd-empty-state">

                    <h3>No visitors found</h3>

                    <p>
                        <?php echo ($search !== "" || $websiteFilter > 0 || $statusFilter !== "all")
                            ? "Try changing your search or filters."
                            : "Visitors will appear here when website chats start."; ?>
                    </p>

                </div>

            <?php } ?>

        </section>

    </main>

</div>

<script src="../assets/js/03-dashboard.js"></script>

</body>

</html>
