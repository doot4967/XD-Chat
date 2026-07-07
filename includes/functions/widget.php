<?php
/*
==================================================
Project : XD Chat
Version : 2.0.0
File    : widget.php
Module  : Widget Functions
Status  : Development
Author  : Umesh + ChatGPT
Created : 06 July 2026
==================================================
*/


/* ==========================================
   01. GENERATE UNIQUE WIDGET KEY
========================================== */

function generateWidgetKey(PDO $pdo)
{

    do {

        $widget_key = "XDW-" .
            strtoupper(bin2hex(random_bytes(3))) .
            "-" .
            strtoupper(bin2hex(random_bytes(2)));

        $query = "
            SELECT id
            FROM widgets
            WHERE widget_key = ?
            LIMIT 1
        ";

        $statement = $pdo->prepare($query);

        $statement->execute([
            $widget_key
        ]);

    } while ($statement->rowCount() > 0);

    return $widget_key;

}


/* ==========================================
   02. CREATE WIDGET
========================================== */

function createWidget(
    PDO $pdo,
    int $user_id,
    int $website_id,
    string $widget_name,
    string $theme,
    string $position,
    string $widget_color,
    string $widget_icon,
    string $welcome_message,
    string $offline_message,
    string $status
) {

    $widget_key = generateWidgetKey($pdo);

    $query = "
        INSERT INTO widgets (
            user_id,
            website_id,
            widget_name,
            widget_key,
            theme,
            position,
            widget_color,
            widget_icon,
            welcome_message,
            offline_message,
            status
        ) VALUES (
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?
        )
    ";

    $statement = $pdo->prepare($query);

    return $statement->execute([
        $user_id,
        $website_id,
        $widget_name,
        $widget_key,
        $theme,
        $position,
        $widget_color,
        $widget_icon,
        $welcome_message,
        $offline_message,
        $status
    ]);

}


/* ==========================================
   03. GET ALL WIDGETS
========================================== */

function getWidgets(PDO $pdo, int $user_id)
{

    $query = "
        SELECT
            widgets.*,
            websites.website_name,
            websites.domain
        FROM widgets
        INNER JOIN websites
            ON widgets.website_id = websites.id
        WHERE widgets.user_id = ?
        ORDER BY widgets.id DESC
    ";

    $statement = $pdo->prepare($query);

    $statement->execute([
        $user_id
    ]);

    return $statement->fetchAll(PDO::FETCH_ASSOC);

}


/* ==========================================
   04. GET SINGLE WIDGET
========================================== */

function getWidget(PDO $pdo, int $widget_id, int $user_id)
{

    $query = "
        SELECT
            widgets.*,
            websites.website_name,
            websites.domain
        FROM widgets
        INNER JOIN websites
            ON widgets.website_id = websites.id
        WHERE widgets.id = ?
        AND widgets.user_id = ?
        LIMIT 1
    ";

    $statement = $pdo->prepare($query);

    $statement->execute([
        $widget_id,
        $user_id
    ]);

    return $statement->fetch(PDO::FETCH_ASSOC);

}


/* ==========================================
   05. UPDATE WIDGET
========================================== */

function updateWidget(
    PDO $pdo,
    int $widget_id,
    int $user_id,
    int $website_id,
    string $widget_name,
    string $theme,
    string $position,
    string $widget_color,
    string $widget_icon,
    string $welcome_message,
    string $offline_message,
    string $status
) {

    $query = "
        UPDATE widgets
        SET
            website_id = ?,
            widget_name = ?,
            theme = ?,
            position = ?,
            widget_color = ?,
            widget_icon = ?,
            welcome_message = ?,
            offline_message = ?,
            status = ?
        WHERE id = ?
        AND user_id = ?
    ";

    $statement = $pdo->prepare($query);

    return $statement->execute([
        $website_id,
        $widget_name,
        $theme,
        $position,
        $widget_color,
        $widget_icon,
        $welcome_message,
        $offline_message,
        $status,
        $widget_id,
        $user_id
    ]);

}


/* ==========================================
   06. DELETE WIDGET
========================================== */

function deleteWidget(PDO $pdo, int $widget_id, int $user_id)
{

    $query = "
        DELETE FROM widgets
        WHERE id = ?
        AND user_id = ?
    ";

    $statement = $pdo->prepare($query);

    return $statement->execute([
        $widget_id,
        $user_id
    ]);

}