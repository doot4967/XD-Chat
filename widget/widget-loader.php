<?php
/*
==================================================
Project : XD Chat
Version : 2.0.0
File    : widget-loader.php
Module  : Public Widget Loader
Status  : Development
Author  : Umesh + ChatGPT
Created : 06 July 2026
==================================================
*/


/* ==========================================
   01. LOAD DEPENDENCIES
========================================== */

require_once '../database/connection.php';


/* ==========================================
   02. SET JSON RESPONSE HEADER
========================================== */

header("Content-Type: application/json");


/* ==========================================
   03. VALIDATE WIDGET KEY
========================================== */

if (!isset($_GET["key"]) || empty($_GET["key"])) {

    echo json_encode([
        "success" => false,
        "message" => "Widget key is required."
    ]);

    exit;

}

$widget_key = trim($_GET["key"]);


/* ==========================================
   04. GET WIDGET DATA
========================================== */

$query = "
    SELECT
        widgets.id,
        widgets.user_id,
        widgets.website_id,
        widgets.widget_name,
        widgets.widget_key,
        widgets.theme,
        widgets.position,
        widgets.widget_color,
        widgets.widget_icon,
        widgets.welcome_message,
        widgets.offline_message,
        widgets.status,
        websites.website_name,
        websites.domain
    FROM widgets
    INNER JOIN websites
        ON widgets.website_id = websites.id
   
        WHERE widgets.widget_key = ?
    LIMIT 1
";

$statement = $pdo->prepare($query);

$statement->execute([
    $widget_key
]);

$widget = $statement->fetch(PDO::FETCH_ASSOC);


/* ==========================================
   05. CHECK WIDGET FOUND
========================================== */

if (!$widget) {

    echo json_encode([
        "success" => false,
        "message" => "Widget not found."
    ]);

    exit;

}


/* ==========================================
   06. CHECK WIDGET STATUS
========================================== */

if ($widget["status"] !== "active") {

    echo json_encode([
        "success" => false,
        "message" => "Widget is inactive."
    ]);

    exit;

}


/* ==========================================
   07. RETURN WIDGET CONFIG
========================================== */

echo json_encode([
    "success" => true,
    "widget" => [
        "id" => (int) $widget["id"],
        "name" => $widget["widget_name"],
        "key" => $widget["widget_key"],
        "theme" => $widget["theme"],
        "position" => $widget["position"],
        "color" => $widget["widget_color"],
        "icon" => $widget["widget_icon"],
        "welcome_message" => $widget["welcome_message"],
        "offline_message" => $widget["offline_message"],
        "website_name" => $widget["website_name"],
        "domain" => $widget["domain"]
    ]
]);

exit;