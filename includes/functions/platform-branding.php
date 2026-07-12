<?php
/*
==================================================
Project : XD Chat
Version : 2.0.0
File    : platform-branding.php
Module  : Platform Branding Upload Helper
Status  : Development
Author  : Umesh + ChatGPT
Created : 12 July 2026
==================================================
*/


/* ==========================================
   01. BRANDING CONFIG
========================================== */

function getPlatformBrandingBasePath(): string
{

    return dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . "uploads" . DIRECTORY_SEPARATOR . "platform" . DIRECTORY_SEPARATOR . "branding";

}


function getPlatformBrandingRelativeBase(): string
{

    return "uploads/platform/branding";

}


function getPlatformBrandingRules(): array
{

    return [
        "logo" => [
            "setting_key" => "platform_logo_path",
            "prefix" => "logo",
            "max_size" => 2 * 1024 * 1024,
            "extensions" => ["jpg", "jpeg", "png", "webp"],
            "mimes" => ["image/jpeg", "image/png", "image/webp"],
            "image_check" => true
        ],
        "favicon" => [
            "setting_key" => "platform_favicon_path",
            "prefix" => "favicon",
            "max_size" => 1 * 1024 * 1024,
            "extensions" => ["ico", "png", "jpg", "jpeg", "webp"],
            "mimes" => ["image/x-icon", "image/vnd.microsoft.icon", "image/ico", "application/ico", "application/x-ico", "image/jpeg", "image/png", "image/webp"],
            "image_check" => true
        ]
    ];

}


function getPlatformBrandingBlockedExtensions(): array
{

    return [
        "svg",
        "php",
        "phtml",
        "phar",
        "html",
        "htm",
        "js",
        "exe",
        "bat",
        "cmd",
        "sh"
    ];

}


/* ==========================================
   02. DIRECTORY AND PATH HELPERS
========================================== */

function ensurePlatformBrandingDirectory(): void
{

    $basePath = getPlatformBrandingBasePath();

    if (!is_dir($basePath)) {
        mkdir($basePath, 0755, true);
    }

    $htaccessPath = $basePath . DIRECTORY_SEPARATOR . ".htaccess";

    if (!is_file($htaccessPath)) {
        file_put_contents($htaccessPath, getPlatformBrandingHtaccessContent());
    }

}


function getPlatformBrandingHtaccessContent(): string
{

    return "Options -Indexes\n"
        . "php_flag engine off\n"
        . "RemoveHandler .php .phtml .php3 .php4 .php5 .php7 .php8 .phar\n"
        . "RemoveType .php .phtml .php3 .php4 .php5 .php7 .php8 .phar\n"
        . "<FilesMatch \"\\.(php|phtml|php3|php4|php5|php7|php8|phar|html|htm|js|exe|bat|cmd|sh|com|scr|msi|jar|vbs|ps1|svg)$\">\n"
        . "    Require all denied\n"
        . "</FilesMatch>\n";

}


function normalizePlatformBrandingRelativePath(string $relativePath): string
{

    $relativePath = trim(str_replace("\\", "/", $relativePath));
    $relativePath = ltrim($relativePath, "/");

    if ($relativePath === "" || strpos($relativePath, "../") !== false || strpos($relativePath, "/..") !== false) {
        return "";
    }

    $base = getPlatformBrandingRelativeBase() . "/";

    if (strpos($relativePath, $base) !== 0) {
        return "";
    }

    if (basename($relativePath) !== substr($relativePath, strlen($base))) {
        return "";
    }

    return $relativePath;

}


function getPlatformBrandingAbsolutePath(string $relativePath): string
{

    $relativePath = normalizePlatformBrandingRelativePath($relativePath);

    if ($relativePath === "") {
        return "";
    }

    return dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . str_replace("/", DIRECTORY_SEPARATOR, $relativePath);

}


function isPlatformBrandingControlledPath(string $relativePath): bool
{

    $absolutePath = getPlatformBrandingAbsolutePath($relativePath);

    if ($absolutePath === "") {
        return false;
    }

    $basePath = realpath(getPlatformBrandingBasePath());
    $filePath = realpath($absolutePath);

    return $basePath !== false
        && $filePath !== false
        && strpos($filePath, $basePath . DIRECTORY_SEPARATOR) === 0;

}


function removePlatformBrandingFile(string $relativePath): void
{

    if (!isPlatformBrandingControlledPath($relativePath)) {
        return;
    }

    $absolutePath = getPlatformBrandingAbsolutePath($relativePath);

    if ($absolutePath !== "" && is_file($absolutePath)) {
        unlink($absolutePath);
    }

}


/* ==========================================
   03. VALIDATION HELPERS
========================================== */

function getPlatformBrandingUploadErrorMessage(int $errorCode): string
{

    if ($errorCode === UPLOAD_ERR_INI_SIZE || $errorCode === UPLOAD_ERR_FORM_SIZE) {
        return "File is too large.";
    }

    if ($errorCode !== UPLOAD_ERR_OK) {
        return "File upload failed.";
    }

    return "";

}


function getPlatformBrandingMime(string $temporaryPath): string
{

    $finfo = finfo_open(FILEINFO_MIME_TYPE);

    if (!$finfo) {
        return "";
    }

    $mime = finfo_file($finfo, $temporaryPath);

    finfo_close($finfo);

    return $mime ?: "";

}


function isPlatformBrandingImageValid(string $temporaryPath, string $extension): bool
{

    if ($extension === "ico") {
        return true;
    }

    return getimagesize($temporaryPath) !== false;

}


function validatePlatformBrandingStoredFile(string $type, string $relativePath): array
{

    $rules = getPlatformBrandingRules();

    if (!isset($rules[$type])) {
        return [
            "success" => false,
            "mime" => ""
        ];
    }

    $relativePath = normalizePlatformBrandingRelativePath($relativePath);
    $absolutePath = getPlatformBrandingAbsolutePath($relativePath);

    if ($relativePath === "" || $absolutePath === "" || !is_file($absolutePath)) {
        return [
            "success" => false,
            "mime" => ""
        ];
    }

    $extension = strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION));
    $mime = getPlatformBrandingMime($absolutePath);
    $rule = $rules[$type];

    if (
        in_array($extension, getPlatformBrandingBlockedExtensions(), true)
        || !in_array($extension, $rule["extensions"], true)
        || !in_array($mime, $rule["mimes"], true)
        || !isPlatformBrandingControlledPath($relativePath)
    ) {
        return [
            "success" => false,
            "mime" => ""
        ];
    }

    return [
        "success" => true,
        "mime" => $mime,
        "path" => $absolutePath
    ];

}


/* ==========================================
   04. SAVE UPLOAD
========================================== */

function savePlatformBrandingUpload(array $file, string $type): array
{

    $rules = getPlatformBrandingRules();

    if (!isset($rules[$type])) {
        return [
            "success" => false,
            "message" => "Invalid branding asset."
        ];
    }

    ensurePlatformBrandingDirectory();

    $uploadError = getPlatformBrandingUploadErrorMessage((int) ($file["error"] ?? UPLOAD_ERR_NO_FILE));

    if ($uploadError !== "") {
        return [
            "success" => false,
            "message" => $uploadError
        ];
    }

    $originalName = basename($file["name"] ?? "");
    $temporaryPath = $file["tmp_name"] ?? "";
    $fileSize = (int) ($file["size"] ?? 0);
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $rule = $rules[$type];

    if ($originalName === "" || !is_uploaded_file($temporaryPath)) {
        return [
            "success" => false,
            "message" => "Invalid uploaded file."
        ];
    }

    if (
        in_array($extension, getPlatformBrandingBlockedExtensions(), true)
        || !in_array($extension, $rule["extensions"], true)
    ) {
        return [
            "success" => false,
            "message" => "This file type is not allowed."
        ];
    }

    if ($fileSize <= 0 || $fileSize > $rule["max_size"]) {
        return [
            "success" => false,
            "message" => "File size is too large."
        ];
    }

    $mime = getPlatformBrandingMime($temporaryPath);

    if (!in_array($mime, $rule["mimes"], true) || !isPlatformBrandingImageValid($temporaryPath, $extension)) {
        return [
            "success" => false,
            "message" => "Invalid image file."
        ];
    }

    $fileName = $rule["prefix"] . "_" . bin2hex(random_bytes(8)) . "." . $extension;
    $relativePath = getPlatformBrandingRelativeBase() . "/" . $fileName;
    $targetPath = getPlatformBrandingBasePath() . DIRECTORY_SEPARATOR . $fileName;

    if (!move_uploaded_file($temporaryPath, $targetPath)) {
        return [
            "success" => false,
            "message" => "Unable to save uploaded file."
        ];
    }

    return [
        "success" => true,
        "relative_path" => $relativePath,
        "mime" => $mime
    ];

}
