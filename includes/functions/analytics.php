<?php
/*
==================================================
Project : XD Chat
Version : 2.0.0
File    : analytics.php
Module  : Analytics Functions
Status  : Development
Author  : Umesh + ChatGPT
Created : 05 July 2026
==================================================
*/


/* ==========================================
   01. GET DASHBOARD STATS
========================================== */

function getDashboardStats(PDO $pdo, int $user_id): array
{

    if ($user_id <= 0) {

        return [
            "websites" => 0,
            "widgets" => 0,
            "chats" => 0,
            "messages" => 0
        ];

    }

    try {

        return [
            "websites" => getTotalWebsites($pdo, $user_id),
            "widgets" => getTotalWidgets($pdo, $user_id),
            "chats" => getOpenChats($pdo, $user_id),
            "messages" => getTotalMessages($pdo, $user_id)
        ];

    } catch (Throwable $exception) {

        error_log("Dashboard stats load failed: " . $exception->getMessage());

        return [
            "websites" => 0,
            "widgets" => 0,
            "chats" => 0,
            "messages" => 0
        ];

    }

}


/* ==========================================
   02. GET TOTAL WEBSITES
========================================== */

function getTotalWebsites(PDO $pdo, int $user_id): int
{

    $query = "
        SELECT COUNT(*) AS total
        FROM websites
        WHERE user_id = ?
    ";

    $statement = $pdo->prepare($query);

    $statement->execute([
        $user_id
    ]);

    return (int) $statement->fetchColumn();

}


/* ==========================================
   03. GET TOTAL WIDGETS
========================================== */

function getTotalWidgets(PDO $pdo, int $user_id): int
{

    $query = "
        SELECT COUNT(*) AS total
        FROM widgets
        INNER JOIN websites
            ON websites.id = widgets.website_id
        WHERE websites.user_id = ?
    ";

    $statement = $pdo->prepare($query);

    $statement->execute([
        $user_id
    ]);

    return (int) $statement->fetchColumn();

}


/* ==========================================
   04. GET OPEN CHATS
========================================== */

function getOpenChats(PDO $pdo, int $user_id): int
{

    $query = "
        SELECT COUNT(*) AS total
        FROM chats
        INNER JOIN websites
            ON websites.id = chats.website_id
        WHERE websites.user_id = ?
        AND chats.status = 'open'
    ";

    $statement = $pdo->prepare($query);

    $statement->execute([
        $user_id
    ]);

    return (int) $statement->fetchColumn();

}


/* ==========================================
   04B. GET TOTAL CHATS
========================================== */

function getTotalChats(PDO $pdo, int $user_id): int
{

    $query = "
        SELECT COUNT(*) AS total
        FROM chats
        INNER JOIN websites
            ON websites.id = chats.website_id
        WHERE websites.user_id = ?
    ";

    $statement = $pdo->prepare($query);

    $statement->execute([
        $user_id
    ]);

    return (int) $statement->fetchColumn();

}


/* ==========================================
   05. GET TOTAL MESSAGES
========================================== */

function getTotalMessages(PDO $pdo, int $user_id): int
{

    $query = "
        SELECT COUNT(*) AS total
        FROM messages
        INNER JOIN chats
            ON messages.chat_id = chats.id
        INNER JOIN websites
            ON chats.website_id = websites.id
        WHERE websites.user_id = ?
    ";

    $statement = $pdo->prepare($query);

    $statement->execute([
        $user_id
    ]);

    return (int) $statement->fetchColumn();

}


/* ==========================================
   06. NORMALIZE LIMIT
========================================== */

function normalizeDashboardLimit(int $limit): int
{

    if ($limit < 1) {
        return 5;
    }

    return min($limit, 10);

}


/* ==========================================
   07. GET RECENT CHATS
========================================== */

function getRecentChats(PDO $pdo, int $user_id, int $limit = 5): array
{

    if ($user_id <= 0) {
        return [];
    }

    $limit = normalizeDashboardLimit($limit);

    $query = "
        SELECT
            chats.id,
            chats.visitor_name,
            chats.visitor_email,
            chats.status,
            chats.created_at,
            websites.website_name,
            websites.domain,
            latest_messages.message AS latest_message,
            latest_messages.message_type AS latest_message_type,
            latest_messages.file_name AS latest_file_name,
            latest_messages.is_deleted AS latest_is_deleted,
            latest_messages.created_at AS latest_message_time
        FROM chats
        INNER JOIN websites
            ON websites.id = chats.website_id
        LEFT JOIN messages AS latest_messages
            ON latest_messages.id = (
                SELECT messages.id
                FROM messages
                WHERE messages.chat_id = chats.id
                ORDER BY messages.id DESC
                LIMIT 1
            )
        WHERE websites.user_id = :user_id
        ORDER BY
            COALESCE(latest_messages.created_at, chats.created_at) DESC,
            chats.id DESC
        LIMIT :limit
    ";

    try {

        $statement = $pdo->prepare($query);

        $statement->bindValue(":user_id", $user_id, PDO::PARAM_INT);

        $statement->bindValue(":limit", $limit, PDO::PARAM_INT);

        $statement->execute();

        return $statement->fetchAll(PDO::FETCH_ASSOC);

    } catch (Throwable $exception) {

        error_log("Recent chats load failed: " . $exception->getMessage());

        return [];

    }

}


/* ==========================================
   08. GET RECENT VISITORS
========================================== */

function getRecentVisitors(PDO $pdo, int $user_id, int $limit = 5): array
{

    if ($user_id <= 0) {
        return [];
    }

    $limit = normalizeDashboardLimit($limit);

    $activitySql = "
        COALESCE(
            (
                SELECT messages.created_at
                FROM messages
                WHERE messages.chat_id = chats.id
                ORDER BY messages.id DESC
                LIMIT 1
            ),
            chats.created_at
        )
    ";

    $visitorKeySql = "
        CASE
            WHEN chats.visitor_id IS NULL OR chats.visitor_id = ''
            THEN CONCAT('chat:', chats.id)
            ELSE CONCAT('visitor:', chats.visitor_id)
        END
    ";

    $query = "
        SELECT
            visitor_rows.visitor_key,
            visitor_rows.website_id,
            visitor_rows.first_seen,
            visitor_rows.last_seen,
            visitor_rows.total_sessions,
            visitor_rows.open_chat_count,
            visitor_rows.closed_chat_count,
            latest_chats.visitor_id,
            latest_chats.visitor_name,
            latest_chats.visitor_email,
            latest_chats.visitor_page_url,
            latest_chats.visitor_browser,
            latest_chats.visitor_device,
            websites.website_name,
            websites.domain
        FROM (
            SELECT
                chats.website_id,
                {$visitorKeySql} AS visitor_key,
                MIN(chats.created_at) AS first_seen,
                MAX({$activitySql}) AS last_seen,
                COUNT(*) AS total_sessions,
                SUM(CASE WHEN chats.status = 'open' THEN 1 ELSE 0 END) AS open_chat_count,
                SUM(CASE WHEN chats.status = 'closed' THEN 1 ELSE 0 END) AS closed_chat_count,
                CAST(
                    SUBSTRING_INDEX(
                        GROUP_CONCAT(
                            chats.id
                            ORDER BY {$activitySql} DESC, chats.id DESC
                        ),
                        ',',
                        1
                    ) AS UNSIGNED
                ) AS latest_chat_id
            FROM chats
            INNER JOIN websites
                ON websites.id = chats.website_id
            WHERE websites.user_id = :user_id
            GROUP BY
                chats.website_id,
                visitor_key
        ) AS visitor_rows
        INNER JOIN chats AS latest_chats
            ON latest_chats.id = visitor_rows.latest_chat_id
        INNER JOIN websites
            ON websites.id = visitor_rows.website_id
        ORDER BY
            visitor_rows.last_seen DESC,
            visitor_rows.latest_chat_id DESC
        LIMIT :limit
    ";

    try {

        $statement = $pdo->prepare($query);

        $statement->bindValue(":user_id", $user_id, PDO::PARAM_INT);

        $statement->bindValue(":limit", $limit, PDO::PARAM_INT);

        $statement->execute();

        return $statement->fetchAll(PDO::FETCH_ASSOC);

    } catch (Throwable $exception) {

        error_log("Recent visitors load failed: " . $exception->getMessage());

        return [];

    }

}


/* ==========================================
   09. GET ANALYTICS OVERVIEW
========================================== */

function getAnalyticsOverview(PDO $pdo, int $user_id): array
{

    $fallback = [
        "total_chats" => 0,
        "open_chats" => 0,
        "closed_chats" => 0,
        "unique_visitors" => 0
    ];

    if ($user_id <= 0) {
        return $fallback;
    }

    $visitorKeySql = "
        CASE
            WHEN chats.visitor_id IS NULL OR chats.visitor_id = ''
            THEN CONCAT('chat:', chats.id)
            ELSE CONCAT('visitor:', chats.website_id, ':', chats.visitor_id)
        END
    ";

    $query = "
        SELECT
            COUNT(chats.id) AS total_chats,
            SUM(CASE WHEN chats.status = 'open' THEN 1 ELSE 0 END) AS open_chats,
            SUM(CASE WHEN chats.status = 'closed' THEN 1 ELSE 0 END) AS closed_chats,
            COUNT(DISTINCT {$visitorKeySql}) AS unique_visitors
        FROM chats
        INNER JOIN websites
            ON websites.id = chats.website_id
        WHERE websites.user_id = ?
    ";

    try {

        $statement = $pdo->prepare($query);

        $statement->execute([
            $user_id
        ]);

        $row = $statement->fetch(PDO::FETCH_ASSOC) ?: [];

        return [
            "total_chats" => (int) ($row["total_chats"] ?? 0),
            "open_chats" => (int) ($row["open_chats"] ?? 0),
            "closed_chats" => (int) ($row["closed_chats"] ?? 0),
            "unique_visitors" => (int) ($row["unique_visitors"] ?? 0)
        ];

    } catch (Throwable $exception) {

        error_log("Analytics overview load failed: " . $exception->getMessage());

        return $fallback;

    }

}


/* ==========================================
   10. GET ANALYTICS MESSAGE BREAKDOWN
========================================== */

function getAnalyticsMessageBreakdown(PDO $pdo, int $user_id): array
{

    $fallback = [
        "total_messages" => 0,
        "visitor_messages" => 0,
        "admin_messages" => 0,
        "media_messages" => 0
    ];

    if ($user_id <= 0) {
        return $fallback;
    }

    $query = "
        SELECT
            COUNT(messages.id) AS total_messages,
            SUM(CASE WHEN messages.sender = 'visitor' THEN 1 ELSE 0 END) AS visitor_messages,
            SUM(CASE WHEN messages.sender = 'agent' THEN 1 ELSE 0 END) AS admin_messages,
            SUM(CASE WHEN messages.message_type IN ('image', 'file', 'audio', 'video') THEN 1 ELSE 0 END) AS media_messages
        FROM messages
        INNER JOIN chats
            ON chats.id = messages.chat_id
        INNER JOIN websites
            ON websites.id = chats.website_id
        WHERE websites.user_id = ?
    ";

    try {

        $statement = $pdo->prepare($query);

        $statement->execute([
            $user_id
        ]);

        $row = $statement->fetch(PDO::FETCH_ASSOC) ?: [];

        return [
            "total_messages" => (int) ($row["total_messages"] ?? 0),
            "visitor_messages" => (int) ($row["visitor_messages"] ?? 0),
            "admin_messages" => (int) ($row["admin_messages"] ?? 0),
            "media_messages" => (int) ($row["media_messages"] ?? 0)
        ];

    } catch (Throwable $exception) {

        error_log("Analytics message breakdown load failed: " . $exception->getMessage());

        return $fallback;

    }

}


/* ==========================================
   11. GET ANALYTICS SEVEN DAY TREND
========================================== */

function getAnalyticsSevenDayTrend(PDO $pdo, int $user_id, string $timezone = "UTC"): array
{

    $timezone = trim($timezone) !== "" ? $timezone : "UTC";

    try {

        $timezoneObject = new DateTimeZone($timezone);

    } catch (Throwable $exception) {

        $timezoneObject = new DateTimeZone("UTC");

    }

    $today = new DateTime("today", $timezoneObject);
    $start = (clone $today)->modify("-6 days");
    $end = (clone $today)->modify("+1 day");

    $trend = [];

    for ($day = 0; $day < 7; $day++) {

        $date = (clone $start)->modify("+" . $day . " days");
        $key = $date->format("Y-m-d");

        $trend[$key] = [
            "date" => $key,
            "chats_created" => 0,
            "messages_sent" => 0
        ];

    }

    if ($user_id <= 0) {
        return array_values($trend);
    }

    $startSql = $start->format("Y-m-d H:i:s");
    $endSql = $end->format("Y-m-d H:i:s");

    try {

        $chatQuery = "
            SELECT
                DATE(chats.created_at) AS activity_date,
                COUNT(chats.id) AS total
            FROM chats
            INNER JOIN websites
                ON websites.id = chats.website_id
            WHERE websites.user_id = :user_id
            AND chats.created_at >= :start_date
            AND chats.created_at < :end_date
            GROUP BY DATE(chats.created_at)
        ";

        $chatStatement = $pdo->prepare($chatQuery);
        $chatStatement->execute([
            ":user_id" => $user_id,
            ":start_date" => $startSql,
            ":end_date" => $endSql
        ]);

        foreach ($chatStatement->fetchAll(PDO::FETCH_ASSOC) as $row) {

            $dateKey = (string) ($row["activity_date"] ?? "");

            if (isset($trend[$dateKey])) {
                $trend[$dateKey]["chats_created"] = (int) ($row["total"] ?? 0);
            }

        }

        $messageQuery = "
            SELECT
                DATE(messages.created_at) AS activity_date,
                COUNT(messages.id) AS total
            FROM messages
            INNER JOIN chats
                ON chats.id = messages.chat_id
            INNER JOIN websites
                ON websites.id = chats.website_id
            WHERE websites.user_id = :user_id
            AND messages.created_at >= :start_date
            AND messages.created_at < :end_date
            GROUP BY DATE(messages.created_at)
        ";

        $messageStatement = $pdo->prepare($messageQuery);
        $messageStatement->execute([
            ":user_id" => $user_id,
            ":start_date" => $startSql,
            ":end_date" => $endSql
        ]);

        foreach ($messageStatement->fetchAll(PDO::FETCH_ASSOC) as $row) {

            $dateKey = (string) ($row["activity_date"] ?? "");

            if (isset($trend[$dateKey])) {
                $trend[$dateKey]["messages_sent"] = (int) ($row["total"] ?? 0);
            }

        }

    } catch (Throwable $exception) {

        error_log("Analytics seven day trend load failed: " . $exception->getMessage());

    }

    return array_values($trend);

}


/* ==========================================
   12. GET WEBSITE PERFORMANCE ANALYTICS
========================================== */

function getWebsitePerformanceAnalytics(PDO $pdo, int $user_id): array
{

    if ($user_id <= 0) {
        return [];
    }

    $visitorKeySql = "
        CASE
            WHEN chats.visitor_id IS NULL OR chats.visitor_id = ''
            THEN CONCAT('chat:', chats.id)
            ELSE CONCAT('visitor:', chats.website_id, ':', chats.visitor_id)
        END
    ";

    $query = "
        SELECT
            websites.id,
            websites.website_name,
            websites.domain,
            COUNT(DISTINCT chats.id) AS total_chats,
            COUNT(DISTINCT CASE WHEN chats.status = 'open' THEN chats.id END) AS open_chats,
            COUNT(DISTINCT CASE WHEN chats.status = 'closed' THEN chats.id END) AS closed_chats,
            COUNT(DISTINCT {$visitorKeySql}) AS unique_visitors,
            COUNT(messages.id) AS total_messages
        FROM websites
        LEFT JOIN chats
            ON chats.website_id = websites.id
        LEFT JOIN messages
            ON messages.chat_id = chats.id
        WHERE websites.user_id = ?
        GROUP BY
            websites.id,
            websites.website_name,
            websites.domain
        ORDER BY
            total_chats DESC,
            websites.website_name ASC,
            websites.id DESC
    ";

    try {

        $statement = $pdo->prepare($query);

        $statement->execute([
            $user_id
        ]);

        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);

        return array_map(
            function (array $row): array {
                return [
                    "id" => (int) ($row["id"] ?? 0),
                    "website_name" => (string) ($row["website_name"] ?? ""),
                    "domain" => (string) ($row["domain"] ?? ""),
                    "total_chats" => (int) ($row["total_chats"] ?? 0),
                    "open_chats" => (int) ($row["open_chats"] ?? 0),
                    "closed_chats" => (int) ($row["closed_chats"] ?? 0),
                    "unique_visitors" => (int) ($row["unique_visitors"] ?? 0),
                    "total_messages" => (int) ($row["total_messages"] ?? 0)
                ];
            },
            $rows
        );

    } catch (Throwable $exception) {

        error_log("Website performance analytics load failed: " . $exception->getMessage());

        return [];

    }

}
