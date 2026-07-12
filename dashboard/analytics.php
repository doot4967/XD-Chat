<?php
/*
==================================================
Project : XD Chat
Version : 2.0.0
File    : analytics.php
Module  : Dashboard Analytics
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

$currentUserId = (int) $_SESSION["user_id"];

$page_title = getPlatformPageTitle($pdo, "Analytics");

$page_heading = "Analytics";

$page_description = "Real platform activity for your websites.";

$dashboardTimezone = getPlatformSetting(
    $pdo,
    "default_timezone",
    "Asia/Kolkata"
);

$overview = getAnalyticsOverview($pdo, $currentUserId);

$messageBreakdown = getAnalyticsMessageBreakdown($pdo, $currentUserId);

$sevenDayTrend = getAnalyticsSevenDayTrend(
    $pdo,
    $currentUserId,
    $dashboardTimezone
);

$websitePerformance = getWebsitePerformanceAnalytics($pdo, $currentUserId);

$maxTrendValue = 0;

foreach ($sevenDayTrend as $trendRow) {
    $maxTrendValue = max(
        $maxTrendValue,
        (int) ($trendRow["chats_created"] ?? 0),
        (int) ($trendRow["messages_sent"] ?? 0)
    );
}

function xdAnalyticsEscape($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

function xdAnalyticsNumber($value): string
{
    return number_format((int) $value);
}

function xdAnalyticsText($value, string $fallback): string
{
    $value = trim((string) ($value ?? ""));

    return $value !== "" ? $value : $fallback;
}

function xdAnalyticsDateLabel(string $date, string $timezone): string
{
    try {

        $dateObject = new DateTime($date, new DateTimeZone($timezone));

        return $dateObject->format("d M");

    } catch (Throwable $exception) {

        return $date;

    }
}

function xdAnalyticsBarWidth(int $value, int $maxValue): int
{
    if ($value <= 0 || $maxValue <= 0) {
        return 0;
    }

    return max(4, (int) round(($value / $maxValue) * 100));
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title><?php echo xdAnalyticsEscape($page_title); ?></title>

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
             PRIMARY SUMMARY
        =========================================== -->

        <section class="xd-dashboard-cards">

            <div class="xd-dashboard-card">

                <div class="xd-card-icon blue">
                    <i class="fa-regular fa-comments"></i>
                </div>

                <span>Total Chats</span>

                <strong><?php echo xdAnalyticsNumber($overview["total_chats"]); ?></strong>

                <small>All conversations</small>

            </div>

            <div class="xd-dashboard-card">

                <div class="xd-card-icon green">
                    <i class="fa-solid fa-unlock"></i>
                </div>

                <span>Open Chats</span>

                <strong><?php echo xdAnalyticsNumber($overview["open_chats"]); ?></strong>

                <small>Currently active</small>

            </div>

            <div class="xd-dashboard-card">

                <div class="xd-card-icon orange">
                    <i class="fa-solid fa-lock"></i>
                </div>

                <span>Closed Chats</span>

                <strong><?php echo xdAnalyticsNumber($overview["closed_chats"]); ?></strong>

                <small>Resolved conversations</small>

            </div>

            <div class="xd-dashboard-card">

                <div class="xd-card-icon purple">
                    <i class="fa-solid fa-users"></i>
                </div>

                <span>Unique Visitors</span>

                <strong><?php echo xdAnalyticsNumber($overview["unique_visitors"]); ?></strong>

                <small>Grouped per website</small>

            </div>

        </section>



        <!-- ==========================================
             MESSAGE SUMMARY
        =========================================== -->

        <section class="xd-dashboard-cards">

            <div class="xd-dashboard-card">

                <div class="xd-card-icon blue">
                    <i class="fa-regular fa-envelope"></i>
                </div>

                <span>Total Messages</span>

                <strong><?php echo xdAnalyticsNumber($messageBreakdown["total_messages"]); ?></strong>

                <small>All message types</small>

            </div>

            <div class="xd-dashboard-card">

                <div class="xd-card-icon green">
                    <i class="fa-solid fa-user"></i>
                </div>

                <span>Visitor Messages</span>

                <strong><?php echo xdAnalyticsNumber($messageBreakdown["visitor_messages"]); ?></strong>

                <small>Sent by visitors</small>

            </div>

            <div class="xd-dashboard-card">

                <div class="xd-card-icon purple">
                    <i class="fa-solid fa-headset"></i>
                </div>

                <span>Admin Messages</span>

                <strong><?php echo xdAnalyticsNumber($messageBreakdown["admin_messages"]); ?></strong>

                <small>Sent by agents</small>

            </div>

            <div class="xd-dashboard-card">

                <div class="xd-card-icon orange">
                    <i class="fa-solid fa-paperclip"></i>
                </div>

                <span>Media Messages</span>

                <strong><?php echo xdAnalyticsNumber($messageBreakdown["media_messages"]); ?></strong>

                <small>Image, file, audio, video</small>

            </div>

        </section>



        <!-- ==========================================
             ANALYTICS PANELS
        =========================================== -->

        <section class="xd-dashboard-grid">

            <div class="xd-dashboard-panel">

                <div class="xd-panel-header">

                    <h2>Last 7 Days Activity</h2>

                    <span>Oldest first</span>

                </div>

                <div style="overflow-x:auto;">

                    <table style="width:100%; border-collapse:collapse;">

                        <thead>

                        <tr>
                            <th style="text-align:left; padding:12px; border-bottom:1px solid #e2e8f0;">Date</th>
                            <th style="text-align:left; padding:12px; border-bottom:1px solid #e2e8f0;">Chats Created</th>
                            <th style="text-align:left; padding:12px; border-bottom:1px solid #e2e8f0;">Messages Sent</th>
                        </tr>

                        </thead>

                        <tbody>

                        <?php foreach ($sevenDayTrend as $trendRow): ?>

                            <?php
                            $chatCount = (int) ($trendRow["chats_created"] ?? 0);
                            $messageCount = (int) ($trendRow["messages_sent"] ?? 0);
                            $chatWidth = xdAnalyticsBarWidth($chatCount, $maxTrendValue);
                            $messageWidth = xdAnalyticsBarWidth($messageCount, $maxTrendValue);
                            ?>

                            <tr>

                                <td style="padding:12px; border-bottom:1px solid #f1f5f9;">
                                    <?php echo xdAnalyticsEscape(xdAnalyticsDateLabel((string) $trendRow["date"], $dashboardTimezone)); ?>
                                </td>

                                <td style="padding:12px; border-bottom:1px solid #f1f5f9;">
                                    <strong><?php echo xdAnalyticsNumber($chatCount); ?></strong>
                                    <div style="height:8px; margin-top:8px; border-radius:999px; background:#e2e8f0;">
                                        <div style="width:<?php echo xdAnalyticsEscape($chatWidth); ?>%; height:8px; border-radius:999px; background:#2563eb;"></div>
                                    </div>
                                </td>

                                <td style="padding:12px; border-bottom:1px solid #f1f5f9;">
                                    <strong><?php echo xdAnalyticsNumber($messageCount); ?></strong>
                                    <div style="height:8px; margin-top:8px; border-radius:999px; background:#e2e8f0;">
                                        <div style="width:<?php echo xdAnalyticsEscape($messageWidth); ?>%; height:8px; border-radius:999px; background:#22c55e;"></div>
                                    </div>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            </div>



            <div class="xd-dashboard-panel">

                <div class="xd-panel-header">

                    <h2>Website Performance</h2>

                    <span><?php echo xdAnalyticsNumber(count($websitePerformance)); ?> websites</span>

                </div>

                <?php if (count($websitePerformance) === 0): ?>

                    <div class="xd-chat-row">

                        <div class="xd-chat-avatar green">-</div>

                        <div class="xd-chat-info">

                            <strong>No websites yet.</strong>

                            <span>Website analytics will appear once you add a website.</span>

                        </div>

                    </div>

                <?php else: ?>

                    <div style="overflow-x:auto;">

                        <table style="width:100%; border-collapse:collapse;">

                            <thead>

                            <tr>
                                <th style="text-align:left; padding:12px; border-bottom:1px solid #e2e8f0;">Website</th>
                                <th style="text-align:left; padding:12px; border-bottom:1px solid #e2e8f0;">Total Chats</th>
                                <th style="text-align:left; padding:12px; border-bottom:1px solid #e2e8f0;">Open</th>
                                <th style="text-align:left; padding:12px; border-bottom:1px solid #e2e8f0;">Closed</th>
                                <th style="text-align:left; padding:12px; border-bottom:1px solid #e2e8f0;">Visitors</th>
                                <th style="text-align:left; padding:12px; border-bottom:1px solid #e2e8f0;">Messages</th>
                            </tr>

                            </thead>

                            <tbody>

                            <?php foreach ($websitePerformance as $websiteRow): ?>

                                <tr>

                                    <td style="padding:12px; border-bottom:1px solid #f1f5f9;">
                                        <strong>
                                            <?php echo xdAnalyticsEscape(xdAnalyticsText($websiteRow["website_name"] ?? "", "Untitled website")); ?>
                                        </strong>
                                        <br>
                                        <small>
                                            <?php echo xdAnalyticsEscape(xdAnalyticsText($websiteRow["domain"] ?? "", "No domain")); ?>
                                        </small>
                                    </td>

                                    <td style="padding:12px; border-bottom:1px solid #f1f5f9;">
                                        <?php echo xdAnalyticsNumber($websiteRow["total_chats"] ?? 0); ?>
                                    </td>

                                    <td style="padding:12px; border-bottom:1px solid #f1f5f9;">
                                        <?php echo xdAnalyticsNumber($websiteRow["open_chats"] ?? 0); ?>
                                    </td>

                                    <td style="padding:12px; border-bottom:1px solid #f1f5f9;">
                                        <?php echo xdAnalyticsNumber($websiteRow["closed_chats"] ?? 0); ?>
                                    </td>

                                    <td style="padding:12px; border-bottom:1px solid #f1f5f9;">
                                        <?php echo xdAnalyticsNumber($websiteRow["unique_visitors"] ?? 0); ?>
                                    </td>

                                    <td style="padding:12px; border-bottom:1px solid #f1f5f9;">
                                        <?php echo xdAnalyticsNumber($websiteRow["total_messages"] ?? 0); ?>
                                    </td>

                                </tr>

                            <?php endforeach; ?>

                            </tbody>

                        </table>

                    </div>

                <?php endif; ?>

            </div>

        </section>

    </main>

</div>

</body>

</html>
