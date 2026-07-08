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
   03. SECURITY HELPERS
========================================== */

function normalizeWidgetHost(string $domain): string
{

    $domain = trim(strtolower($domain));

    if ($domain === "") {

        return "";

    }

    if (!preg_match("/^https?:\/\//", $domain)) {

        $domain = "https://" . $domain;

    }

    $host = parse_url($domain, PHP_URL_HOST);

    return $host ? preg_replace("/^www\./", "", $host) : "";

}


function getRequestWidgetHost(): string
{

    $source = $_SERVER["HTTP_ORIGIN"]
        ?? $_SERVER["HTTP_REFERER"]
        ?? $_SERVER["HTTP_HOST"]
        ?? "";

    return normalizeWidgetHost($source);

}


function isLocalWidgetHost(string $host): bool
{

    return in_array($host, [
        "localhost",
        "127.0.0.1",
        "::1"
    ], true);

}


function isWidgetDomainAllowed(string $websiteDomain): bool
{

    $requestHost = getRequestWidgetHost();

    if (isLocalWidgetHost($requestHost)) {

        return true;

    }

    return $requestHost !== ""
        && $requestHost === normalizeWidgetHost($websiteDomain);

}


/* ==========================================
   04. VALIDATE WIDGET KEY
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
   05. GET WIDGET DATA
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
        websites.domain,
        websites.status AS website_status
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
   06. CHECK WIDGET FOUND
========================================== */

if (!$widget) {

    echo json_encode([
        "success" => false,
        "message" => "Widget not found."
    ]);

    exit;

}


/* ==========================================
   07. CHECK WIDGET AND WEBSITE STATUS
========================================== */

if (
    $widget["status"] !== "active" ||
    $widget["website_status"] !== "active"
) {

    echo json_encode([
        "success" => false,
        "message" => "Widget is not available."
    ]);

    exit;

}


/* ==========================================
   08. CHECK ALLOWED DOMAIN
========================================== */

if (!isWidgetDomainAllowed($widget["domain"])) {

    echo json_encode([
        "success" => false,
        "message" => "Widget domain is not allowed."
    ]);

    exit;

}


/* ==========================================
   09. RETURN WIDGET CONFIG
========================================== */

echo json_encode([
    "success" => true,
    "widget" => [
        "name" => $widget["widget_name"],
        "theme" => $widget["theme"],
        "position" => $widget["position"],
        "color" => $widget["widget_color"],
        "icon" => $widget["widget_icon"],
        "welcome_message" => $widget["welcome_message"],
        "offline_message" => $widget["offline_message"]
    ]
]);

exit;
