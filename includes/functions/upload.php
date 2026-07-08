<?php
/*
==================================================
Project : XD Chat
Version : 2.0.0
File    : upload.php
Module  : Chat File Upload Helper
Status  : Development
Author  : Umesh + ChatGPT
Created : 08 July 2026
==================================================
*/


/* ==========================================
   01. UPLOAD CONFIG
========================================== */

define("XD_CHAT_IMAGE_MAX_SIZE", 5 * 1024 * 1024);
define("XD_CHAT_DOCUMENT_MAX_SIZE", 10 * 1024 * 1024);
define("XD_CHAT_AUDIO_MAX_SIZE", 10 * 1024 * 1024);
define("XD_CHAT_VIDEO_MAX_SIZE", 15 * 1024 * 1024);


function getChatUploadBasePath(): string
{

    return dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . "uploads" . DIRECTORY_SEPARATOR . "chat-files";

}


function getChatUploadRelativePath(string $category, string $fileName): string
{

    return "uploads/chat-files/" . $category . "/" . $fileName;

}


function getChatUploadRules(): array
{

    return [
        "images" => [
            "message_type" => "image",
            "max_size" => XD_CHAT_IMAGE_MAX_SIZE,
            "extensions" => ["jpg", "jpeg", "png", "webp"],
            "mimes" => ["image/jpeg", "image/png", "image/webp"]
        ],
        "documents" => [
            "message_type" => "file",
            "max_size" => XD_CHAT_DOCUMENT_MAX_SIZE,
            "extensions" => ["pdf", "doc", "docx", "xls", "xlsx", "txt"],
            "mimes" => [
                "application/pdf",
                "application/msword",
                "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
                "application/vnd.ms-excel",
                "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
                "text/plain",
                "application/zip",
                "application/x-zip-compressed",
                "application/x-ole-storage",
                "application/octet-stream"
            ]
        ],
        "audio" => [
            "message_type" => "audio",
            "max_size" => XD_CHAT_AUDIO_MAX_SIZE,
            "extensions" => ["mp3", "wav", "ogg", "webm"],
            "mimes" => [
                "audio/mpeg",
                "audio/mp3",
                "audio/wav",
                "audio/x-wav",
                "audio/ogg",
                "audio/webm",
                "application/ogg"
            ]
        ],
        "videos" => [
            "message_type" => "video",
            "max_size" => XD_CHAT_VIDEO_MAX_SIZE,
            "extensions" => ["mp4", "webm", "mov"],
            "mimes" => [
                "video/mp4",
                "video/webm",
                "video/quicktime",
                "application/octet-stream"
            ]
        ]
    ];

}


function getBlockedChatFileExtensions(): array
{

    return [
        "php",
        "phtml",
        "php3",
        "php4",
        "php5",
        "php7",
        "php8",
        "js",
        "exe",
        "bat",
        "sh",
        "cmd",
        "com",
        "scr",
        "msi",
        "jar",
        "vbs",
        "ps1"
    ];

}


/* ==========================================
   02. DIRECTORY HELPERS
========================================== */

function ensureChatUploadDirectories(): void
{

    $basePath = getChatUploadBasePath();

    $directories = [
        $basePath,
        $basePath . DIRECTORY_SEPARATOR . "images",
        $basePath . DIRECTORY_SEPARATOR . "documents",
        $basePath . DIRECTORY_SEPARATOR . "audio",
        $basePath . DIRECTORY_SEPARATOR . "videos"
    ];

    foreach ($directories as $directory) {

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

    }

}


function getChatUploadHtaccessContent(): string
{

    return "Options -Indexes\n"
        . "Require all denied\n"
        . "php_flag engine off\n"
        . "RemoveHandler .php .phtml .php3 .php4 .php5 .php7 .php8\n"
        . "RemoveType .php .phtml .php3 .php4 .php5 .php7 .php8\n"
        . "<FilesMatch \"\\.(php|phtml|php3|php4|php5|php7|php8|js|exe|bat|sh|cmd|com|scr|msi|jar|vbs|ps1)$\">\n"
        . "    Require all denied\n"
        . "</FilesMatch>\n";

}


/* ==========================================
   03. VALIDATION HELPERS
========================================== */

function getChatUploadErrorMessage(int $errorCode): string
{

    if ($errorCode === UPLOAD_ERR_INI_SIZE || $errorCode === UPLOAD_ERR_FORM_SIZE) {
        return "File is too large.";
    }

    if ($errorCode !== UPLOAD_ERR_OK) {
        return "File upload failed.";
    }

    return "";

}


function getChatUploadedFileMime(string $temporaryPath): string
{

    $finfo = finfo_open(FILEINFO_MIME_TYPE);

    if (!$finfo) {
        return "";
    }

    $mime = finfo_file($finfo, $temporaryPath);

    finfo_close($finfo);

    return $mime ?: "";

}


function getChatUploadCategory(string $extension, string $mime): ?array
{

    foreach (getChatUploadRules() as $category => $rule) {

        if (
            in_array($extension, $rule["extensions"], true) &&
            in_array($mime, $rule["mimes"], true)
        ) {

            return [
                "category" => $category,
                "rule" => $rule
            ];

        }

    }

    return null;

}


function formatChatFileSize(int $bytes): string
{

    if ($bytes >= 1024 * 1024) {
        return round($bytes / 1024 / 1024, 1) . " MB";
    }

    if ($bytes >= 1024) {
        return round($bytes / 1024, 1) . " KB";
    }

    return $bytes . " B";

}


/* ==========================================
   04. SAVE UPLOAD
========================================== */

function saveChatUploadedFile(array $file): array
{

    ensureChatUploadDirectories();

    $uploadError = getChatUploadErrorMessage((int) ($file["error"] ?? UPLOAD_ERR_NO_FILE));

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

    if ($originalName === "" || !is_uploaded_file($temporaryPath)) {
        return [
            "success" => false,
            "message" => "Invalid uploaded file."
        ];
    }

    if (in_array($extension, getBlockedChatFileExtensions(), true)) {
        return [
            "success" => false,
            "message" => "This file type is not allowed."
        ];
    }

    $mime = getChatUploadedFileMime($temporaryPath);

    if (
        substr($originalName, 0, 14) === "voice-message-" &&
        $extension === "webm" &&
        $mime === "video/webm"
    ) {
        $mime = "audio/webm";
    }

    $categoryData = getChatUploadCategory($extension, $mime);

    if (!$categoryData) {
        return [
            "success" => false,
            "message" => "Unsupported file type."
        ];
    }

    $rule = $categoryData["rule"];

    if ($fileSize <= 0 || $fileSize > $rule["max_size"]) {
        return [
            "success" => false,
            "message" => "File size limit is " . formatChatFileSize($rule["max_size"]) . "."
        ];
    }

    $randomName = "chat_" . date("Ymd_His") . "_" . bin2hex(random_bytes(8)) . "." . $extension;
    $category = $categoryData["category"];
    $targetPath = getChatUploadBasePath() . DIRECTORY_SEPARATOR . $category . DIRECTORY_SEPARATOR . $randomName;

    if (!move_uploaded_file($temporaryPath, $targetPath)) {
        return [
            "success" => false,
            "message" => "Unable to save uploaded file."
        ];
    }

    return [
        "success" => true,
        "message_type" => $rule["message_type"],
        "file_name" => $originalName,
        "file_path" => getChatUploadRelativePath($category, $randomName),
        "file_mime" => $mime,
        "file_size" => $fileSize
    ];

}


/* ==========================================
   05. FILE OUTPUT
========================================== */

function getChatFileAbsolutePath(string $relativePath): string
{

    $cleanPath = str_replace(["/", "\\"], DIRECTORY_SEPARATOR, $relativePath);

    return dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . $cleanPath;

}


function sendChatFileDownload(array $message): void
{

    $filePath = $message["file_path"] ?? "";
    $fileName = $message["file_name"] ?? "download";
    $mime = $message["file_mime"] ?? "application/octet-stream";
    $messageType = $message["message_type"] ?? "file";
    $forceDownload = isset($_GET["download"]) && $_GET["download"] === "1";
    $absolutePath = getChatFileAbsolutePath($filePath);

    if ($filePath === "" || !is_file($absolutePath)) {
        http_response_code(404);
        exit("File not found.");
    }

    $fileSize = filesize($absolutePath);
    $start = 0;
    $end = $fileSize - 1;

    header("Content-Type: " . $mime);
    header("Accept-Ranges: bytes");
    header(
        "Content-Disposition: "
        . ($forceDownload || $messageType === "file" ? "attachment" : "inline")
        . "; filename=\"" . basename($fileName) . "\""
    );
    header("X-Content-Type-Options: nosniff");

    if (
        !$forceDownload &&
        isset($_SERVER["HTTP_RANGE"]) &&
        preg_match('/bytes=(\d*)-(\d*)/', $_SERVER["HTTP_RANGE"], $matches)
    ) {

        if ($matches[1] !== "") {
            $start = (int) $matches[1];
        }

        if ($matches[2] !== "") {
            $end = (int) $matches[2];
        }

        if ($start > $end || $start >= $fileSize) {
            header("Content-Range: bytes */" . $fileSize);
            http_response_code(416);
            exit;
        }

        $end = min($end, $fileSize - 1);

        http_response_code(206);
        header("Content-Range: bytes " . $start . "-" . $end . "/" . $fileSize);

    }

    $length = $end - $start + 1;

    header("Content-Length: " . $length);

    $handle = fopen($absolutePath, "rb");

    if (!$handle) {
        http_response_code(500);
        exit("Unable to read file.");
    }

    fseek($handle, $start);

    while (!feof($handle) && $length > 0) {

        $chunkSize = min(8192, $length);
        $buffer = fread($handle, $chunkSize);

        if ($buffer === false || $buffer === "") {
            break;
        }

        echo $buffer;

        $length -= strlen($buffer);

        if (connection_aborted()) {
            break;
        }

    }

    fclose($handle);

    exit;

}
