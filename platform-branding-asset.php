<?php
/*
==================================================
Project : XD Chat
Version : 2.0.0
File    : platform-branding-asset.php
Module  : Platform Branding Asset Endpoint
Status  : Development
Author  : Umesh + ChatGPT
Created : 12 July 2026
==================================================
*/


/* ==========================================
   01. LOAD DEPENDENCIES
========================================== */

require_once __DIR__ . "/database/connection.php";
require_once __DIR__ . "/includes/functions/platform-settings.php";
require_once __DIR__ . "/includes/functions/platform-branding.php";


/* ==========================================
   02. RESOLVE ASSET
========================================== */

$type = $_GET["type"] ?? "";

if (!in_array($type, ["logo", "favicon"], true)) {
    http_response_code(404);
    exit;
}

$relativePath = $type === "logo"
    ? getPlatformLogoPath($pdo)
    : getPlatformFaviconPath($pdo);

$asset = validatePlatformBrandingStoredFile($type, $relativePath);

if (empty($asset["success"]) || empty($asset["path"]) || empty($asset["mime"])) {
    http_response_code(404);
    exit;
}


/* ==========================================
   03. OUTPUT FILE
========================================== */

$filePath = $asset["path"];
$mime = $asset["mime"];
$fileSize = filesize($filePath);

if ($fileSize === false) {
    http_response_code(404);
    exit;
}

header("Content-Type: " . $mime);
header("Content-Length: " . $fileSize);
header("X-Content-Type-Options: nosniff");
header("Cache-Control: public, max-age=86400");
header("Pragma: public");

readfile($filePath);
exit;
