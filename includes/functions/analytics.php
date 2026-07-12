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
