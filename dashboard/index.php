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

        <section class="xd-dashboard-cards">

            <div class="xd-dashboard-card">

                <div class="xd-card-icon blue">
                    <i class="fa-solid fa-globe"></i>
                </div>

                <span>Total Websites</span>

                <strong><?php echo $dashboardStats["websites"]; ?></strong>

                <small class="positive">

                    Configured websites

                </small>

            </div>



            <div class="xd-dashboard-card">

                <div class="xd-card-icon green">
                    <i class="fa-regular fa-comments"></i>
                </div>

                <span>Open Chats</span>

                <strong><?php echo $dashboardStats["chats"]; ?></strong>

                <small class="positive">

                    Currently open conversations

                </small>

            </div>



            <div class="xd-dashboard-card">

                <div class="xd-card-icon purple">
                    <i class="fa-solid fa-users"></i>
                </div>

                <span>Total Widgets</span>

                <strong><?php echo $dashboardStats["widgets"]; ?></strong>

                <small class="positive">

                    Installed widgets

                </small>

            </div>



            <div class="xd-dashboard-card">

                <div class="xd-card-icon orange">
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

        <section class="xd-dashboard-grid">

            <div class="xd-dashboard-panel">

                <div class="xd-panel-header">

                    <h2>Recent Chats</h2>

                    <a href="chats.php">View All</a>

                </div>

                <div class="xd-chat-list">

                    <?php if (count($recentChats) === 0): ?>

                        <div class="xd-chat-row">

                            <div class="xd-chat-avatar green">-</div>

                            <div class="xd-chat-info">

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

                            <div class="xd-chat-row">

                                <div class="xd-chat-avatar green">
                                    <?php echo xdDashboardEscape(xdDashboardInitial($visitorName)); ?>
                                </div>

                                <div class="xd-chat-info">

                                    <strong><?php echo xdDashboardEscape($visitorName); ?></strong>

                                    <span><?php echo xdDashboardEscape(xdDashboardChatPreview($chat)); ?></span>

                                    <small>
                                        <?php echo xdDashboardEscape($websiteName); ?>
                                        &middot;
                                        <?php echo xdDashboardEscape($domain); ?>
                                    </small>

                                </div>

                                <div class="xd-chat-meta">

                                    <small>
                                        <?php echo xdDashboardEscape(xdDashboardFormatDate($activityTime, $dashboardDateFormat, $dashboardTimezone)); ?>
                                    </small>

                                    <span class="xd-badge <?php echo xdDashboardEscape($badgeClass); ?>">

                                        <?php echo xdDashboardEscape($badgeText); ?>

                                    </span>

                                </div>

                            </div>

                        <?php endforeach; ?>

                    <?php endif; ?>

                </div>

            </div>



            <div class="xd-dashboard-panel">

                <div class="xd-panel-header">

                    <h2>Recent Visitors</h2>

                    <a href="visitors.php">View All</a>

                </div>

                <div class="xd-visitor-list">

                    <?php if (count($recentVisitors) === 0): ?>

                    <div class="xd-visitor-row">

                        <span>-</span>

                        <div>

                            <strong>No recent visitors yet.</strong>

                            <small>Visitor activity will appear here.</small>

                        </div>

                        <em></em>

                    </div>

                    <?php else: ?>

                        <?php foreach ($recentVisitors as $visitor): ?>

                            <?php
                            $visitorName = xdDashboardText($visitor["visitor_name"] ?? "", "Guest Visitor");
                            $visitorId = xdDashboardText($visitor["visitor_id"] ?? "", $visitor["visitor_key"] ?? "Unknown visitor");
                            $websiteName = xdDashboardText($visitor["website_name"] ?? "", "Untitled website");
                            $domain = xdDashboardText($visitor["domain"] ?? "", "No domain");
                            $pageUrl = xdDashboardShortText($visitor["visitor_page_url"] ?? "", 52);
                            $browser = xdDashboardText($visitor["visitor_browser"] ?? "", "");
                            $device = xdDashboardText($visitor["visitor_device"] ?? "", "");
                            $lastSeen = $visitor["last_seen"] ?? "";
                            $sessions = (int) ($visitor["total_sessions"] ?? 0);
                            $hasOpenChat = (int) ($visitor["open_chat_count"] ?? 0) > 0;
                            $statusText = $hasOpenChat ? "Open" : "Closed";
                            ?>

                            <div class="xd-visitor-row">

                                <span><?php echo xdDashboardEscape(xdDashboardInitial($visitorName)); ?></span>

                                <div>

                                    <strong><?php echo xdDashboardEscape($visitorName); ?></strong>

                                    <small>
                                        <?php echo xdDashboardEscape($websiteName); ?>
                                        &middot;
                                        <?php echo xdDashboardEscape($domain); ?>
                                    </small>

                                    <small>
                                        <?php echo xdDashboardEscape($pageUrl !== "" ? $pageUrl : $visitorId); ?>
                                    </small>

                                    <?php if ($browser !== "" || $device !== ""): ?>

                                        <small>
                                            <?php echo xdDashboardEscape(trim($browser . " " . $device)); ?>
                                        </small>

                                    <?php endif; ?>

                                    <small>
                                        <?php echo xdDashboardEscape(xdDashboardFormatDate($lastSeen, $dashboardDateFormat, $dashboardTimezone)); ?>
                                        &middot;
                                        <?php echo xdDashboardEscape($sessions); ?>
                                        <?php echo $sessions === 1 ? "session" : "sessions"; ?>
                                    </small>

                                </div>

                                <em title="<?php echo xdDashboardEscape($statusText); ?>"></em>

                            </div>

                        <?php endforeach; ?>

                    <?php endif; ?>

                </div>

            </div>

        </section>

    </main>

</div>

</body>

</html>
