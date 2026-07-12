<?php
/*
==================================================
Project : XD Chat
Version : 2.0.0
File    : analytics.php
Module  : Super Admin Analytics Dashboard
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
   02. DATE FILTER HELPERS
========================================== */

function getSuperAdminAnalyticsRange(): array
{

    $allowedRanges = [
        "7d",
        "30d",
        "90d",
        "year",
        "custom"
    ];

    $range = $_GET["range"] ?? "30d";

    if (!in_array($range, $allowedRanges, true)) {

        $range = "30d";

    }

    $today = new DateTimeImmutable("today");

    $end = $today;

    if ($range === "7d") {

        $start = $today->modify("-6 days");

        $label = "Last 7 Days";

    } elseif ($range === "90d") {

        $start = $today->modify("-89 days");

        $label = "Last 90 Days";

    } elseif ($range === "year") {

        $start = new DateTimeImmutable(date("Y") . "-01-01");

        $label = "This Year";

    } elseif ($range === "custom") {

        $customStart = $_GET["start_date"] ?? "";

        $customEnd = $_GET["end_date"] ?? "";

        $start = DateTimeImmutable::createFromFormat("Y-m-d", $customStart) ?: $today->modify("-29 days");

        $end = DateTimeImmutable::createFromFormat("Y-m-d", $customEnd) ?: $today;

        if ($start > $end) {

            $start = $today->modify("-29 days");

            $end = $today;

        }

        $label = "Custom Range";

    } else {

        $range = "30d";

        $start = $today->modify("-29 days");

        $label = "Last 30 Days";

    }

    return [
        "range" => $range,
        "start" => $start,
        "end" => $end,
        "end_exclusive" => $end->modify("+1 day"),
        "label" => $label
    ];

}


function getSuperAdminAnalyticsParams(array $dateRange): array
{

    return [
        ":start_date" => $dateRange["start"]->format("Y-m-d 00:00:00"),
        ":end_date" => $dateRange["end_exclusive"]->format("Y-m-d 00:00:00")
    ];

}


/* ==========================================
   03. QUERY HELPERS
========================================== */

function getSuperAdminAnalyticsCount(PDO $pdo, string $sql, array $params): int
{

    try {

        $statement = $pdo->prepare($sql);

        foreach ($params as $key => $value) {

            $statement->bindValue($key, $value);

        }

        $statement->execute();

        return (int) $statement->fetchColumn();

    } catch (Throwable $exception) {

        return 0;

    }

}


function getSuperAdminDailySeries(PDO $pdo, string $table, DateTimeImmutable $start, DateTimeImmutable $end, array $params): array
{

    $allowedTables = [
        "users",
        "chats",
        "messages"
    ];

    if (!in_array($table, $allowedTables, true)) {

        return [];

    }

    $series = [];

    $cursor = $start;

    while ($cursor <= $end) {

        $series[$cursor->format("Y-m-d")] = 0;

        $cursor = $cursor->modify("+1 day");

    }

    try {

        $statement = $pdo->prepare(
            "SELECT DATE(created_at) AS date_key, COUNT(*) AS total
             FROM " . $table . "
             WHERE created_at >= :start_date
             AND created_at < :end_date
             GROUP BY DATE(created_at)
             ORDER BY date_key ASC"
        );

        foreach ($params as $key => $value) {

            $statement->bindValue($key, $value);

        }

        $statement->execute();

        foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $row) {

            if (isset($series[$row["date_key"]])) {

                $series[$row["date_key"]] = (int) $row["total"];

            }

        }

    } catch (Throwable $exception) {

        return $series;

    }

    return $series;

}


function getSuperAdminGroupedCounts(PDO $pdo, string $table, string $column, array $allowedValues, array $params): array
{

    $allowedTables = [
        "chats",
        "messages"
    ];

    $allowedColumns = [
        "status",
        "message_type",
        "sender"
    ];

    if (!in_array($table, $allowedTables, true) || !in_array($column, $allowedColumns, true)) {

        return array_fill_keys($allowedValues, 0);

    }

    $counts = array_fill_keys($allowedValues, 0);

    try {

        $statement = $pdo->prepare(
            "SELECT " . $column . " AS group_key, COUNT(*) AS total
             FROM " . $table . "
             WHERE created_at >= :start_date
             AND created_at < :end_date
             GROUP BY " . $column
        );

        foreach ($params as $key => $value) {

            $statement->bindValue($key, $value);

        }

        $statement->execute();

        foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $row) {

            if (isset($counts[$row["group_key"]])) {

                $counts[$row["group_key"]] = (int) $row["total"];

            }

        }

    } catch (Throwable $exception) {

        return $counts;

    }

    return $counts;

}


function getSuperAdminInsightRows(PDO $pdo, string $sql, array $params): array
{

    try {

        $statement = $pdo->prepare($sql);

        foreach ($params as $key => $value) {

            $statement->bindValue($key, $value);

        }

        $statement->execute();

        return $statement->fetchAll(PDO::FETCH_ASSOC);

    } catch (Throwable $exception) {

        return [];

    }

}


/* ==========================================
   04. CHART HELPERS
========================================== */

function renderSuperAdminLineChart(array $series, string $tone): string
{

    $total = array_sum($series);

    if ($total === 0) {

        return '<div class="xd-sa-analytics-empty">No data for selected period.</div>';

    }

    $values = array_values($series);

    $maxValue = max($values);

    $width = 640;

    $height = 180;

    $padding = 18;

    $count = max(1, count($values) - 1);

    $points = [];

    foreach ($values as $index => $value) {

        $x = $padding + (($width - ($padding * 2)) / $count) * $index;

        $y = $height - $padding - (($height - ($padding * 2)) * ($value / max(1, $maxValue)));

        $points[] = round($x, 2) . "," . round($y, 2);

    }

    return '<svg class="xd-sa-analytics-line ' . htmlspecialchars($tone) . '" viewBox="0 0 ' . $width . ' ' . $height . '" role="img" aria-label="Analytics chart">
        <polyline points="' . htmlspecialchars(implode(" ", $points)) . '"></polyline>
    </svg>';

}


function renderSuperAdminBars(array $counts, array $labels): string
{

    $total = array_sum($counts);

    if ($total === 0) {

        return '<div class="xd-sa-analytics-empty">No data for selected period.</div>';

    }

    $html = '<div class="xd-sa-analytics-bars">';

    foreach ($labels as $key => $label) {

        $value = (int) ($counts[$key] ?? 0);

        $percent = $total > 0 ? round(($value / $total) * 100, 1) : 0;

        $html .= '<div class="xd-sa-analytics-bar-row">
            <div>
                <strong>' . htmlspecialchars($label) . '</strong>
                <span>' . number_format($value) . ' (' . htmlspecialchars((string) $percent) . '%)</span>
            </div>
            <em><i style="width:' . htmlspecialchars((string) $percent) . '%"></i></em>
        </div>';

    }

    $html .= '</div>';

    return $html;

}


/* ==========================================
   05. ANALYTICS DATA
========================================== */

$dateRange = getSuperAdminAnalyticsRange();

$rangeParams = getSuperAdminAnalyticsParams($dateRange);

$newUsers = getSuperAdminAnalyticsCount(
    $pdo,
    "SELECT COUNT(*) FROM users WHERE created_at >= :start_date AND created_at < :end_date",
    $rangeParams
);

$newWebsites = getSuperAdminAnalyticsCount(
    $pdo,
    "SELECT COUNT(*) FROM websites WHERE created_at >= :start_date AND created_at < :end_date",
    $rangeParams
);

$newWidgets = getSuperAdminAnalyticsCount(
    $pdo,
    "SELECT COUNT(*) FROM widgets WHERE created_at >= :start_date AND created_at < :end_date",
    $rangeParams
);

$totalChats = getSuperAdminAnalyticsCount(
    $pdo,
    "SELECT COUNT(*) FROM chats WHERE created_at >= :start_date AND created_at < :end_date",
    $rangeParams
);

$openChats = getSuperAdminAnalyticsCount(
    $pdo,
    "SELECT COUNT(*) FROM chats WHERE status = 'open' AND created_at >= :start_date AND created_at < :end_date",
    $rangeParams
);

$closedChats = getSuperAdminAnalyticsCount(
    $pdo,
    "SELECT COUNT(*) FROM chats WHERE status = 'closed' AND created_at >= :start_date AND created_at < :end_date",
    $rangeParams
);

$totalMessages = getSuperAdminAnalyticsCount(
    $pdo,
    "SELECT COUNT(*) FROM messages WHERE created_at >= :start_date AND created_at < :end_date",
    $rangeParams
);

$averageMessages = $totalChats > 0 ? round($totalMessages / $totalChats, 1) : 0;

$chatsGrowth = getSuperAdminDailySeries(
    $pdo,
    "chats",
    $dateRange["start"],
    $dateRange["end"],
    $rangeParams
);

$messagesActivity = getSuperAdminDailySeries(
    $pdo,
    "messages",
    $dateRange["start"],
    $dateRange["end"],
    $rangeParams
);

$userGrowth = getSuperAdminDailySeries(
    $pdo,
    "users",
    $dateRange["start"],
    $dateRange["end"],
    $rangeParams
);

$messageTypes = getSuperAdminGroupedCounts(
    $pdo,
    "messages",
    "message_type",
    [
        "text",
        "image",
        "file",
        "audio",
        "video",
        "system"
    ],
    $rangeParams
);

$senderDistribution = getSuperAdminGroupedCounts(
    $pdo,
    "messages",
    "sender",
    [
        "visitor",
        "agent",
        "bot"
    ],
    $rangeParams
);

$chatStatusDistribution = getSuperAdminGroupedCounts(
    $pdo,
    "chats",
    "status",
    [
        "open",
        "closed"
    ],
    $rangeParams
);

$activeWebsites = getSuperAdminInsightRows(
    $pdo,
    "SELECT
        websites.website_name,
        websites.domain,
        users.full_name AS owner_name,
        COUNT(chats.id) AS chat_count
     FROM chats
     INNER JOIN websites
        ON websites.id = chats.website_id
     INNER JOIN users
        ON users.id = websites.user_id
     WHERE chats.created_at >= :start_date
     AND chats.created_at < :end_date
     GROUP BY websites.id, websites.website_name, websites.domain, users.full_name
     ORDER BY chat_count DESC, websites.website_name ASC
     LIMIT 5",
    $rangeParams
);

$activeOwners = getSuperAdminInsightRows(
    $pdo,
    "SELECT
        users.full_name,
        users.email,
        COUNT(chats.id) AS chat_count
     FROM chats
     INNER JOIN websites
        ON websites.id = chats.website_id
     INNER JOIN users
        ON users.id = websites.user_id
     WHERE chats.created_at >= :start_date
     AND chats.created_at < :end_date
     GROUP BY users.id, users.full_name, users.email
     ORDER BY chat_count DESC, users.full_name ASC
     LIMIT 5",
    $rangeParams
);

$peakActivity = getSuperAdminInsightRows(
    $pdo,
    "SELECT
        DATE(created_at) AS activity_date,
        COUNT(*) AS message_count
     FROM messages
     WHERE created_at >= :start_date
     AND created_at < :end_date
     GROUP BY DATE(created_at)
     ORDER BY message_count DESC, activity_date DESC
     LIMIT 1",
    $rangeParams
);


/* ==========================================
   06. PAGE CONFIGURATION
========================================== */

$page_title = getPlatformPageTitle($pdo, "Analytics");

$page_heading = "Analytics Dashboard";

$page_description = "Track platform growth, usage, and chat activity.";

$active_menu = "analytics";

$analyticsCards = [
    [
        "label" => "New Users",
        "value" => number_format($newUsers),
        "icon" => "fa-solid fa-user-plus",
        "tone" => "blue"
    ],
    [
        "label" => "New Websites",
        "value" => number_format($newWebsites),
        "icon" => "fa-solid fa-globe",
        "tone" => "green"
    ],
    [
        "label" => "New Widgets",
        "value" => number_format($newWidgets),
        "icon" => "fa-solid fa-puzzle-piece",
        "tone" => "purple"
    ],
    [
        "label" => "Total Chats",
        "value" => number_format($totalChats),
        "icon" => "fa-regular fa-comments",
        "tone" => "orange"
    ],
    [
        "label" => "Open Chats",
        "value" => number_format($openChats),
        "icon" => "fa-solid fa-lock-open",
        "tone" => "green"
    ],
    [
        "label" => "Closed Chats",
        "value" => number_format($closedChats),
        "icon" => "fa-solid fa-lock",
        "tone" => "orange"
    ],
    [
        "label" => "Total Messages",
        "value" => number_format($totalMessages),
        "icon" => "fa-regular fa-envelope",
        "tone" => "blue"
    ],
    [
        "label" => "Avg Messages per Chat",
        "value" => htmlspecialchars((string) $averageMessages),
        "icon" => "fa-solid fa-chart-simple",
        "tone" => "purple"
    ]
];

require_once 'includes/header.php';
?>

<section class="xd-sa-users-panel xd-sa-analytics-panel">

    <div class="xd-sa-users-header">
        <div>
            <h2>Analytics Filters</h2>
            <p><?php echo htmlspecialchars($dateRange["label"]); ?>: <?php echo htmlspecialchars($dateRange["start"]->format("d M Y")); ?> - <?php echo htmlspecialchars($dateRange["end"]->format("d M Y")); ?></p>
        </div>
    </div>

    <form class="xd-sa-filter-bar xd-sa-analytics-filter"
          method="GET"
          action="analytics.php">

        <div class="xd-sa-filter-field">
            <label for="range">Date Range</label>
            <select id="range" name="range">
                <option value="7d" <?php echo $dateRange["range"] === "7d" ? "selected" : ""; ?>>Last 7 Days</option>
                <option value="30d" <?php echo $dateRange["range"] === "30d" ? "selected" : ""; ?>>Last 30 Days</option>
                <option value="90d" <?php echo $dateRange["range"] === "90d" ? "selected" : ""; ?>>Last 90 Days</option>
                <option value="year" <?php echo $dateRange["range"] === "year" ? "selected" : ""; ?>>This Year</option>
                <option value="custom" <?php echo $dateRange["range"] === "custom" ? "selected" : ""; ?>>Custom Range</option>
            </select>
        </div>

        <div class="xd-sa-filter-field">
            <label for="start_date">Start Date</label>
            <input type="date"
                   id="start_date"
                   name="start_date"
                   value="<?php echo htmlspecialchars($dateRange["start"]->format("Y-m-d")); ?>">
        </div>

        <div class="xd-sa-filter-field">
            <label for="end_date">End Date</label>
            <input type="date"
                   id="end_date"
                   name="end_date"
                   value="<?php echo htmlspecialchars($dateRange["end"]->format("Y-m-d")); ?>">
        </div>

        <div class="xd-sa-filter-actions">
            <button type="submit">Apply</button>
            <a href="analytics.php">Reset</a>
        </div>

    </form>

</section>


<section class="xd-sa-card-grid xd-sa-analytics-card-grid">

    <?php foreach ($analyticsCards as $card) { ?>

        <article class="xd-sa-card xd-sa-analytics-card">

            <div class="xd-sa-card-icon <?php echo htmlspecialchars($card["tone"]); ?>">
                <i class="<?php echo htmlspecialchars($card["icon"]); ?>"></i>
            </div>

            <div>
                <span><?php echo htmlspecialchars($card["label"]); ?></span>
                <strong><?php echo $card["value"]; ?></strong>
                <small>Selected date range.</small>
            </div>

        </article>

    <?php } ?>

</section>


<section class="xd-sa-analytics-grid">

    <article class="xd-sa-panel xd-sa-analytics-chart">
        <div class="xd-sa-panel-header">
            <div>
                <h2>Chats Growth</h2>
                <p>Date-wise new chats.</p>
            </div>
        </div>
        <?php echo renderSuperAdminLineChart($chatsGrowth, "blue"); ?>
    </article>

    <article class="xd-sa-panel xd-sa-analytics-chart">
        <div class="xd-sa-panel-header">
            <div>
                <h2>Messages Activity</h2>
                <p>Date-wise total messages.</p>
            </div>
        </div>
        <?php echo renderSuperAdminLineChart($messagesActivity, "green"); ?>
    </article>

    <article class="xd-sa-panel xd-sa-analytics-chart">
        <div class="xd-sa-panel-header">
            <div>
                <h2>User Growth</h2>
                <p>Date-wise new users.</p>
            </div>
        </div>
        <?php echo renderSuperAdminLineChart($userGrowth, "purple"); ?>
    </article>

    <article class="xd-sa-panel xd-sa-analytics-chart">
        <div class="xd-sa-panel-header">
            <div>
                <h2>Message Types</h2>
                <p>Text, media, and system messages.</p>
            </div>
        </div>
        <?php echo renderSuperAdminBars($messageTypes, [
            "text" => "Text",
            "image" => "Image",
            "file" => "File",
            "audio" => "Audio",
            "video" => "Video",
            "system" => "System"
        ]); ?>
    </article>

    <article class="xd-sa-panel xd-sa-analytics-chart">
        <div class="xd-sa-panel-header">
            <div>
                <h2>Sender Distribution</h2>
                <p>Visitor, agent, and bot messages.</p>
            </div>
        </div>
        <?php echo renderSuperAdminBars($senderDistribution, [
            "visitor" => "Visitor",
            "agent" => "Agent",
            "bot" => "Bot"
        ]); ?>
    </article>

    <article class="xd-sa-panel xd-sa-analytics-chart">
        <div class="xd-sa-panel-header">
            <div>
                <h2>Open vs Closed</h2>
                <p>Chat status distribution.</p>
            </div>
        </div>
        <?php echo renderSuperAdminBars($chatStatusDistribution, [
            "open" => "Open",
            "closed" => "Closed"
        ]); ?>
    </article>

</section>


<section class="xd-sa-analytics-insights">

    <article class="xd-sa-panel">
        <div class="xd-sa-panel-header">
            <div>
                <h2>Most Active Websites</h2>
                <p>Top websites by chat count.</p>
            </div>
        </div>

        <div class="xd-sa-analytics-list">
            <?php if ($activeWebsites) { ?>
                <?php foreach ($activeWebsites as $website) { ?>
                    <div>
                        <strong><?php echo htmlspecialchars($website["website_name"] ?: "Untitled Website"); ?></strong>
                        <span><?php echo htmlspecialchars(($website["domain"] ?: "No domain") . " - " . $website["owner_name"]); ?></span>
                        <em><?php echo htmlspecialchars(number_format((int) $website["chat_count"])); ?> chats</em>
                    </div>
                <?php } ?>
            <?php } else { ?>
                <div class="xd-sa-analytics-empty">No website activity found.</div>
            <?php } ?>
        </div>
    </article>

    <article class="xd-sa-panel">
        <div class="xd-sa-panel-header">
            <div>
                <h2>Most Active Owners</h2>
                <p>Top owners by chat count.</p>
            </div>
        </div>

        <div class="xd-sa-analytics-list">
            <?php if ($activeOwners) { ?>
                <?php foreach ($activeOwners as $owner) { ?>
                    <div>
                        <strong><?php echo htmlspecialchars($owner["full_name"]); ?></strong>
                        <span><?php echo htmlspecialchars($owner["email"]); ?></span>
                        <em><?php echo htmlspecialchars(number_format((int) $owner["chat_count"])); ?> chats</em>
                    </div>
                <?php } ?>
            <?php } else { ?>
                <div class="xd-sa-analytics-empty">No owner activity found.</div>
            <?php } ?>
        </div>
    </article>

    <article class="xd-sa-panel">
        <div class="xd-sa-panel-header">
            <div>
                <h2>Peak Activity Date</h2>
                <p>Highest message volume day.</p>
            </div>
        </div>

        <div class="xd-sa-analytics-peak">
            <?php if ($peakActivity) { ?>
                <strong><?php echo htmlspecialchars(date("d M Y", strtotime($peakActivity[0]["activity_date"]))); ?></strong>
                <span><?php echo htmlspecialchars(number_format((int) $peakActivity[0]["message_count"])); ?> messages</span>
            <?php } else { ?>
                <div class="xd-sa-analytics-empty">No message activity found.</div>
            <?php } ?>
        </div>
    </article>

</section>

<?php require_once 'includes/footer.php'; ?>
