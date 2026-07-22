<?php
/*
==================================================
Project : XD Chat
Version : 2.0.0
File    : index.php
Module  : Dashboard
Status  : Development
Author  : Umesh + ChatGPT
Created : 05 July 2026
==================================================
*/


/* ==========================================
   01. LOAD DEPENDENCIES
========================================== */

require_once '../includes/functions/session.php';

require_once '../database/connection.php';

require_once '../includes/functions/platform-settings.php';

require_once '../includes/functions/analytics.php';

requireLogin();


/* ==========================================
   02. PAGE CONFIGURATION
========================================== */

$page_title = getPlatformPageTitle($pdo, "Dashboard");

$page_heading = "Dashboard";

$page_description = "Welcome back, " . $_SESSION["user_name"] . " 👋";

$dashboardStats = getDashboardStats(
    $pdo,
    (int) $_SESSION["user_id"]
);

$recentChats = getRecentChats(
    $pdo,
    (int) $_SESSION["user_id"],
    5
);

$recentVisitors = getRecentVisitors(
    $pdo,
    (int) $_SESSION["user_id"],
    5
);

$dashboardDateFormat = getPlatformSetting(
    $pdo,
    "date_time_format",
    "d M Y, h:i A"
);

$dashboardTimezone = getPlatformSetting(
    $pdo,
    "default_timezone",
    "Asia/Kolkata"
);

function xdDashboardEscape($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

function xdDashboardText($value, string $fallback): string
{
    $value = trim((string) ($value ?? ""));

    return $value !== "" ? $value : $fallback;
}

function xdDashboardShortText($value, int $limit = 70): string
{
    $value = trim((string) ($value ?? ""));

    if ($value === "") {
        return "";
    }

    if (function_exists("mb_strlen") && function_exists("mb_substr")) {

        if (mb_strlen($value, "UTF-8") <= $limit) {
            return $value;
        }

        return mb_substr($value, 0, $limit - 3, "UTF-8") . "...";

    }

    if (strlen($value) <= $limit) {
        return $value;
    }

    return substr($value, 0, $limit - 3) . "...";
}

function xdDashboardFormatDate($value, string $format, string $timezone): string
{
    $value = trim((string) ($value ?? ""));

    if ($value === "") {
        return "Not available";
    }

    try {

        $date = new DateTime($value);
        $date->setTimezone(new DateTimeZone($timezone));

        return $date->format($format);

    } catch (Throwable $exception) {

        return $value;

    }
}

function xdDashboardChatPreview(array $chat): string
{
    if ((int) ($chat["latest_is_deleted"] ?? 0) === 1) {
        return "Message deleted";
    }

    $type = (string) ($chat["latest_message_type"] ?? "text");
    $message = (string) ($chat["latest_message"] ?? "");
    $fileName = (string) ($chat["latest_file_name"] ?? "");

    if ($type === "image") {
        return "Image";
    }

    if ($type === "audio") {
        return "Audio message";
    }

    if ($type === "video") {
        return "Video";
    }

    if ($type === "file") {
        return xdDashboardText($fileName, "Document");
    }

    return xdDashboardText(xdDashboardShortText($message, 70), "No message yet");
}

function xdDashboardInitial($value): string
{
    $value = xdDashboardText($value, "Visitor");

    if (function_exists("mb_substr") && function_exists("mb_strtoupper")) {
        return mb_strtoupper(mb_substr($value, 0, 1, "UTF-8"), "UTF-8");
    }

    return strtoupper(substr($value, 0, 1));
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title><?php echo htmlspecialchars($page_title); ?></title>

    <?php require_once 'includes/head-branding.php'; ?>

    <link rel="stylesheet" href="../assets/css/01-reset.css">

    <link rel="stylesheet" href="../assets/css/02-variables.css">

    <link rel="stylesheet" href="../assets/css/03-base.css">

    <link rel="stylesheet" href="../assets/css/10-dashboard.css">

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

</head>

<body>

<div class="xd-dashboard">

    <?php require_once 'includes/sidebar.php'; ?>

    <main class="xd-dashboard-main">

        <?php require_once 'includes/header.php'; ?>



        <!-- ==========================================
             DASHBOARD CARDS
        =========================================== -->

        <section class="xd-dashboard-cards xd-home-stats"
                 aria-label="Dashboard summary">

            <div class="xd-dashboard-card xd-home-stat-card">

                <div class="xd-card-icon xd-home-stat-icon blue">
                    <i class="fa-solid fa-globe"></i>
                </div>

                <span>Total Websites</span>

                <strong><?php echo $dashboardStats["websites"]; ?></strong>

                <small class="positive">

                    Configured websites

                </small>

            </div>



            <div class="xd-dashboard-card xd-home-stat-card">

                <div class="xd-card-icon xd-home-stat-icon green">
                    <i class="fa-regular fa-comments"></i>
                </div>

                <span>Open Chats</span>

                <strong><?php echo $dashboardStats["chats"]; ?></strong>

                <small class="positive">

                    Currently open conversations

                </small>

            </div>



            <div class="xd-dashboard-card xd-home-stat-card">

                <div class="xd-card-icon xd-home-stat-icon purple">
                    <i class="fa-solid fa-users"></i>
                </div>

                <span>Total Widgets</span>

                <strong><?php echo $dashboardStats["widgets"]; ?></strong>

                <small class="positive">

                    Installed widgets

                </small>

            </div>



            <div class="xd-dashboard-card xd-home-stat-card">

                <div class="xd-card-icon xd-home-stat-icon orange">
                    <i class="fa-regular fa-envelope"></i>
                </div>

                <span>Total Messages</span>

                <strong><?php echo $dashboardStats["messages"]; ?></strong>

                <small class="positive">

                    All conversation messages

                </small>

            </div>

        </section>



        <!-- ==========================================
             DASHBOARD PANELS
        =========================================== -->

        <section class="xd-dashboard-grid xd-home-grid">

            <section class="xd-dashboard-panel xd-home-panel xd-home-chats-panel">

                <div class="xd-panel-header xd-home-panel-header">

                    <h2>Recent Chats</h2>

                    <a href="chats.php" class="xd-home-view-all">View All</a>

                </div>

                <div class="xd-chat-list xd-home-chat-list">

                    <?php if (count($recentChats) === 0): ?>

                        <div class="xd-chat-row xd-home-chat-row xd-home-empty-row">

                            <div class="xd-chat-avatar xd-home-chat-avatar green">-</div>

                            <div class="xd-chat-info xd-home-chat-info">

                                <strong>No recent chats yet.</strong>

                                <span>New conversations will appear here.</span>

                            </div>

                        </div>

                    <?php else: ?>

                        <?php foreach ($recentChats as $chat): ?>

                            <?php
                            $visitorName = xdDashboardText($chat["visitor_name"] ?? "", "Guest Visitor");
                            $websiteName = xdDashboardText($chat["website_name"] ?? "", "Untitled website");
                            $domain = xdDashboardText($chat["domain"] ?? "", "No domain");
                            $status = (string) ($chat["status"] ?? "closed");
                            $badgeClass = $status === "open" ? "success" : "warning";
                            $badgeText = $status === "open" ? "Open" : "Closed";
                            $activityTime = $chat["latest_message_time"] ?? $chat["created_at"] ?? "";
                            ?>

                            <article class="xd-chat-row xd-home-chat-row">

                                <div class="xd-chat-avatar xd-home-chat-avatar green">
                                    <?php echo xdDashboardEscape(xdDashboardInitial($visitorName)); ?>
                                </div>

                                <div class="xd-chat-info xd-home-chat-info">

                                    <strong><?php echo xdDashboardEscape($visitorName); ?></strong>

                                    <span><?php echo xdDashboardEscape(xdDashboardChatPreview($chat)); ?></span>

                                    <small>
                                        <?php echo xdDashboardEscape($websiteName); ?>
                                        &middot;
                                        <?php echo xdDashboardEscape($domain); ?>
                                    </small>

                                </div>

                                <div class="xd-chat-meta xd-home-chat-meta">

                                    <small>
                                        <?php echo xdDashboardEscape(xdDashboardFormatDate($activityTime, $dashboardDateFormat, $dashboardTimezone)); ?>
                                    </small>

                                    <span class="xd-badge <?php echo xdDashboardEscape($badgeClass); ?>">

                                        <?php echo xdDashboardEscape($badgeText); ?>

                                    </span>

                                </div>

                            </article>

                        <?php endforeach; ?>

                    <?php endif; ?>

                </div>

            </section>



            <section class="xd-dashboard-panel xd-home-panel xd-home-visitors-panel">

                <div class="xd-panel-header xd-home-panel-header">

                    <h2>Recent Visitors</h2>

                    <a href="visitors.php" class="xd-home-view-all">View All</a>

                </div>

                <div class="xd-visitor-list xd-home-visitor-list">

                    <?php if (count($recentVisitors) === 0): ?>

                    <div class="xd-visitor-row xd-home-visitor-row xd-home-empty-row">

                        <span class="xd-home-visitor-avatar" aria-hidden="true">-</span>

                        <div>

                            <strong>No recent visitors yet.</strong>

                            <small>Visitor activity will appear here.</small>

                        </div>

                    </div>

                    <?php else: ?>

                        <?php foreach ($recentVisitors as $visitor): ?>

                            <?php
                            $visitorName = xdDashboardText($visitor["visitor_name"] ?? "", "Guest Visitor");
                            $visitorId = xdDashboardText($visitor["visitor_id"] ?? "", $visitor["visitor_key"] ?? "Unknown visitor");
                            $websiteName = xdDashboardText($visitor["website_name"] ?? "", "Untitled website");
                            $domain = xdDashboardText($visitor["domain"] ?? "", "No domain");
                            $pageUrl = trim((string) ($visitor["visitor_page_url"] ?? ""));
                            $visitorEmail = xdDashboardText($visitor["visitor_email"] ?? "", "Not provided");
                            $browser = xdDashboardText($visitor["visitor_browser"] ?? "", "");
                            $device = xdDashboardText($visitor["visitor_device"] ?? "", "");
                            $deviceSummary = trim($device . ($device !== "" && $browser !== "" ? " / " : "") . $browser);
                            $lastSeen = $visitor["last_seen"] ?? "";
                            $sessions = (int) ($visitor["total_sessions"] ?? 0);
                            $openChats = (int) ($visitor["open_chat_count"] ?? 0);
                            $closedChats = (int) ($visitor["closed_chat_count"] ?? 0);
                            $hasOpenChat = $openChats > 0;
                            $statusText = $hasOpenChat ? "Open" : "Closed";
                            $statusClass = $hasOpenChat ? "is-open" : "is-closed";
                            ?>

                            <article class="xd-visitor-row xd-home-visitor-row">

                                <span class="xd-home-visitor-avatar" aria-hidden="true">
                                    <?php echo xdDashboardEscape(xdDashboardInitial($visitorName)); ?>
                                </span>

                                <div class="xd-home-visitor-content">

                                    <div class="xd-home-visitor-heading">

                                        <strong><?php echo xdDashboardEscape($visitorName); ?></strong>

                                        <span class="xd-home-visitor-status <?php echo xdDashboardEscape($statusClass); ?>">
                                            <em aria-hidden="true"></em>
                                            <span><?php echo xdDashboardEscape($statusText); ?></span>
                                        </span>

                                    </div>

                                    <small class="xd-home-visitor-website">
                                        <?php echo xdDashboardEscape($websiteName); ?>
                                        &middot;
                                        <?php echo xdDashboardEscape($domain); ?>
                                    </small>

                                    <?php if ($deviceSummary !== ""): ?>

                                        <small class="xd-home-visitor-environment">
                                            <?php echo xdDashboardEscape($deviceSummary); ?>
                                        </small>

                                    <?php endif; ?>

                                    <small class="xd-home-visitor-activity">
                                        Last active
                                        <?php echo xdDashboardEscape(xdDashboardFormatDate($lastSeen, $dashboardDateFormat, $dashboardTimezone)); ?>
                                    </small>

                                    <details class="xd-home-visitor-details">

                                        <summary>View details</summary>

                                        <div class="xd-home-visitor-detail-grid">

                                            <div>
                                                <span>Visitor ID</span>
                                                <strong><?php echo xdDashboardEscape($visitorId); ?></strong>
                                            </div>

                                            <div>
                                                <span>Email</span>
                                                <strong><?php echo xdDashboardEscape($visitorEmail); ?></strong>
                                            </div>

                                            <div>
                                                <span>Latest page</span>
                                                <strong><?php echo xdDashboardEscape($pageUrl !== "" ? $pageUrl : "Not available"); ?></strong>
                                            </div>

                                            <div>
                                                <span>Sessions</span>
                                                <strong>
                                                    <?php echo xdDashboardEscape($sessions); ?> total
                                                    &middot;
                                                    <?php echo xdDashboardEscape($openChats); ?> open
                                                    &middot;
                                                    <?php echo xdDashboardEscape($closedChats); ?> closed
                                                </strong>
                                            </div>

                                        </div>

                                    </details>

                                </div>

                            </article>

                        <?php endforeach; ?>

                    <?php endif; ?>

                </div>

            </section>

        </section>

    </main>

</div>

</body>

</html>
