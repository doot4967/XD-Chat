<?php
/*
==================================================
Project : XD Chat
Version : 2.0.0
File    : website.php
Module  : Website Management
Status  : Development
Author  : Umesh + ChatGPT
Created : 06 July 2026
==================================================
*/


/* ==========================================
   01. GENERATE WEBSITE KEY
========================================== */

function generateWebsiteKey(): string
{

    return "XDW-" . strtoupper(
        substr(
            md5(
                uniqid(
                    mt_rand(),
                    true
                )
            ),
            0,
            8
        )
    );

}


/* ==========================================
   02. ADD WEBSITE
========================================== */

function addWebsite(

    PDO $pdo,

    int $user_id,

    string $website_name,

    string $domain,

    string $status

): bool
{

    $widget_key = generateWebsiteKey();

    $query = $pdo->prepare("

        INSERT INTO websites
        (
            user_id,
            website_name,
            domain,
            widget_key,
            status
        )
        VALUES
        (
            ?,
            ?,
            ?,
            ?,
            ?
        )

    ");

    return $query->execute([

        $user_id,

        $website_name,

        $domain,

        $widget_key,

        $status

    ]);

}


/* ==========================================
   03. GET USER WEBSITES
========================================== */

function getWebsites(

    PDO $pdo,

    int $user_id

): array
{

    $query = $pdo->prepare("

        SELECT
            id,
            website_name,
            domain,
            widget_key,
            status,
            created_at
        FROM websites
        WHERE user_id = ?
        ORDER BY id DESC

    ");

    $query->execute([

        $user_id

    ]);

    return $query->fetchAll(PDO::FETCH_ASSOC);

}


/* ==========================================
   04. GET SINGLE WEBSITE
========================================== */

function getWebsite(

    PDO $pdo,

    int $website_id,

    int $user_id

): array|false
{

    $query = $pdo->prepare("

        SELECT
            id,
            website_name,
            domain,
            widget_key,
            status,
            created_at
        FROM websites
        WHERE id = ?
        AND user_id = ?
        LIMIT 1

    ");

    $query->execute([

        $website_id,

        $user_id

    ]);

    return $query->fetch(PDO::FETCH_ASSOC);

}


/* ==========================================
   05. UPDATE WEBSITE
========================================== */

function updateWebsite(

    PDO $pdo,

    int $website_id,

    int $user_id,

    string $website_name,

    string $domain,

    string $status

): bool
{

    $query = $pdo->prepare("

        UPDATE websites
        SET
            website_name = ?,
            domain = ?,
            status = ?
        WHERE id = ?
        AND user_id = ?

    ");

    return $query->execute([

        $website_name,

        $domain,

        $status,

        $website_id,

        $user_id

    ]);

}


/* ==========================================
   06. DELETE WEBSITE
========================================== */

function deleteWebsite(

    PDO $pdo,

    int $website_id,

    int $user_id

): bool
{

    $query = $pdo->prepare("

        DELETE FROM websites
        WHERE id = ?
        AND user_id = ?

    ");

    return $query->execute([

        $website_id,

        $user_id

    ]);

}