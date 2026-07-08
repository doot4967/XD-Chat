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

    return [
        "websites" => getTotalWebsites($pdo, $user_id),
        "widgets" => getTotalWidgets($pdo, $user_id),
        "chats" => getTotalChats($pdo, $user_id),
        "messages" => getTotalMessages($pdo, $user_id)
    ];

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
        WHERE user_id = ?
    ";

    $statement = $pdo->prepare($query);

    $statement->execute([
        $user_id
    ]);

    return (int) $statement->fetchColumn();

}


/* ==========================================
   04. GET TOTAL CHATS
========================================== */

function getTotalChats(PDO $pdo, int $user_id): int
{

    $query = "
        SELECT COUNT(*) AS total
        FROM chats
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
