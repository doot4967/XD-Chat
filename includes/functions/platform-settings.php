<?php
/*
==================================================
Project : XD Chat
Version : 2.0.0
File    : platform-settings.php
Module  : Platform Settings Helpers
Status  : Development
Author  : Umesh + ChatGPT
Created : 10 July 2026
==================================================
*/


/* ==========================================
   01. DEFAULT SETTINGS
========================================== */

function getPlatformSettingDefinitions(): array
{

    return [
        "platform_name" => [
            "value" => "XD Chat",
            "type" => "string",
            "category" => "general",
            "label" => "Platform Name",
            "description" => "Main platform name shown across admin areas."
        ],
        "platform_tagline" => [
            "value" => "Live Chat Platform",
            "type" => "string",
            "category" => "general",
            "label" => "Platform Tagline",
            "description" => "Short platform tagline for dashboard and branding."
        ],
        "support_email" => [
            "value" => "",
            "type" => "string",
            "category" => "general",
            "label" => "Support Email",
            "description" => "Public support email for platform assistance."
        ],
        "support_phone" => [
            "value" => "",
            "type" => "string",
            "category" => "general",
            "label" => "Support Phone",
            "description" => "Public support phone number for platform assistance."
        ],
        "footer_copyright_text" => [
            "value" => "",
            "type" => "string",
            "category" => "general",
            "label" => "Footer Copyright Text",
            "description" => "Optional footer copyright text. Blank values use the platform fallback."
        ],
        "platform_logo_path" => [
            "value" => "",
            "type" => "string",
            "category" => "general",
            "label" => "Platform Logo Path",
            "description" => "Controlled relative path for the uploaded platform logo."
        ],
        "platform_favicon_path" => [
            "value" => "",
            "type" => "string",
            "category" => "general",
            "label" => "Platform Favicon Path",
            "description" => "Controlled relative path for the uploaded platform favicon."
        ],
        "default_timezone" => [
            "value" => "Asia/Kolkata",
            "type" => "string",
            "category" => "general",
            "label" => "Default Timezone",
            "description" => "Default timezone for platform date and time display."
        ],
        "date_time_format" => [
            "value" => "d M Y, h:i A",
            "type" => "string",
            "category" => "general",
            "label" => "Date Time Format",
            "description" => "Default date and time display format."
        ],
        "default_new_user_role" => [
            "value" => "admin",
            "type" => "string",
            "category" => "security",
            "label" => "Default New User Role",
            "description" => "Default role assigned to newly registered users."
        ],
        "default_new_user_status" => [
            "value" => "active",
            "type" => "string",
            "category" => "security",
            "label" => "Default New User Status",
            "description" => "Default account status for newly registered users."
        ],
        "session_idle_timeout" => [
            "value" => 7200,
            "type" => "integer",
            "category" => "security",
            "label" => "Session Idle Timeout",
            "description" => "Session idle timeout in seconds."
        ],
        "minimum_password_length" => [
            "value" => 8,
            "type" => "integer",
            "category" => "security",
            "label" => "Minimum Password Length",
            "description" => "Minimum password length for user accounts."
        ],
        "allow_registration" => [
            "value" => true,
            "type" => "boolean",
            "category" => "security",
            "label" => "Allow Registration",
            "description" => "Controls whether public registration is enabled."
        ],
        "default_welcome_message" => [
            "value" => "Hi there! How can we help you today?",
            "type" => "string",
            "category" => "chat",
            "label" => "Default Welcome Message",
            "description" => "Default widget welcome message for new widgets."
        ],
        "default_offline_message" => [
            "value" => "We are currently offline. Please leave a message.",
            "type" => "string",
            "category" => "chat",
            "label" => "Default Offline Message",
            "description" => "Default widget offline message for new widgets."
        ],
        "default_chat_status" => [
            "value" => "open",
            "type" => "string",
            "category" => "chat",
            "label" => "Default Chat Status",
            "description" => "Default status assigned to new visitor chats."
        ],
        "message_max_length" => [
            "value" => 1000,
            "type" => "integer",
            "category" => "chat",
            "label" => "Message Maximum Length",
            "description" => "Maximum allowed text message length."
        ],
        "delete_everyone_time_limit" => [
            "value" => 60,
            "type" => "integer",
            "category" => "chat",
            "label" => "Delete From Everyone Time Limit",
            "description" => "Time limit in minutes for future delete-from-everyone behavior."
        ],
        "image_max_size_mb" => [
            "value" => 5,
            "type" => "integer",
            "category" => "upload",
            "label" => "Image Maximum Size",
            "description" => "Maximum image upload size in MB."
        ],
        "document_max_size_mb" => [
            "value" => 10,
            "type" => "integer",
            "category" => "upload",
            "label" => "Document Maximum Size",
            "description" => "Maximum document upload size in MB."
        ],
        "audio_max_size_mb" => [
            "value" => 10,
            "type" => "integer",
            "category" => "upload",
            "label" => "Audio Maximum Size",
            "description" => "Maximum audio upload size in MB."
        ],
        "video_max_size_mb" => [
            "value" => 15,
            "type" => "integer",
            "category" => "upload",
            "label" => "Video Maximum Size",
            "description" => "Maximum video upload size in MB."
        ],
        "allowed_image_types" => [
            "value" => ["jpg", "jpeg", "png", "webp"],
            "type" => "json",
            "category" => "upload",
            "label" => "Allowed Image Types",
            "description" => "Allowed image file extensions."
        ],
        "allowed_document_types" => [
            "value" => ["pdf", "doc", "docx", "xls", "xlsx", "txt"],
            "type" => "json",
            "category" => "upload",
            "label" => "Allowed Document Types",
            "description" => "Allowed document file extensions."
        ],
        "allowed_audio_types" => [
            "value" => ["mp3", "wav", "ogg", "webm"],
            "type" => "json",
            "category" => "upload",
            "label" => "Allowed Audio Types",
            "description" => "Allowed audio file extensions."
        ],
        "allowed_video_types" => [
            "value" => ["mp4", "webm", "mov"],
            "type" => "json",
            "category" => "upload",
            "label" => "Allowed Video Types",
            "description" => "Allowed video file extensions."
        ],
        "maintenance_mode" => [
            "value" => false,
            "type" => "boolean",
            "category" => "system",
            "label" => "Maintenance Mode",
            "description" => "Stores maintenance mode setting. Runtime enforcement will be added later."
        ],
        "maintenance_message" => [
            "value" => "XD Chat is currently under maintenance. Please check back soon.",
            "type" => "string",
            "category" => "system",
            "label" => "Maintenance Message",
            "description" => "Message shown during maintenance mode after runtime enforcement is added."
        ]
    ];

}


/* ==========================================
   02. JSON VALUE HELPERS
========================================== */

function decodePlatformSettingValue(?string $value, string $type, $fallback)
{

    if ($value === null || $value === "") {

        return $fallback;

    }

    $decoded = json_decode($value, true);

    if (json_last_error() !== JSON_ERROR_NONE) {

        return $fallback;

    }

    if ($type === "integer") {

        return (int) $decoded;

    }

    if ($type === "boolean") {

        return (bool) $decoded;

    }

    if ($type === "json") {

        return is_array($decoded) ? $decoded : $fallback;

    }

    return is_scalar($decoded) ? (string) $decoded : $fallback;

}


function encodePlatformSettingValue($value): string
{

    return json_encode(
        $value,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );

}


/* ==========================================
   03. LOAD SETTINGS
========================================== */

function getPlatformSettingsMap(PDO $pdo): array
{

    static $settingsCache = null;

    if ($settingsCache !== null) {

        return $settingsCache;

    }

    $definitions = getPlatformSettingDefinitions();

    $settings = [];

    foreach ($definitions as $key => $definition) {

        $settings[$key] = array_merge($definition, [
            "key" => $key,
            "db_value" => null
        ]);

    }

    try {

        $statement = $pdo->query(
            "SELECT
                setting_key,
                setting_value,
                value_type,
                category,
                label,
                description
             FROM platform_settings"
        );

        foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $row) {

            $key = $row["setting_key"];

            if (!isset($settings[$key])) {

                continue;

            }

            $type = $row["value_type"] ?: $settings[$key]["type"];

            $settings[$key]["value"] = decodePlatformSettingValue(
                $row["setting_value"],
                $type,
                $settings[$key]["value"]
            );

            $settings[$key]["type"] = $type;

            $settings[$key]["category"] = $row["category"] ?: $settings[$key]["category"];

            $settings[$key]["label"] = $row["label"] ?: $settings[$key]["label"];

            $settings[$key]["description"] = $row["description"] ?: $settings[$key]["description"];

            $settings[$key]["db_value"] = $row["setting_value"];

        }

    } catch (Throwable $exception) {

        $settingsCache = $settings;

        return $settingsCache;

    }

    $settingsCache = $settings;

    return $settingsCache;

}


function getPlatformSetting(PDO $pdo, string $key, $default = null)
{

    $settings = getPlatformSettingsMap($pdo);

    return $settings[$key]["value"] ?? $default;

}


/* ==========================================
   04. SAFE RUNTIME GETTERS
========================================== */

function getPlatformBooleanSettingSafe(PDO $pdo, string $key, bool $fallback): bool
{

    $value = getPlatformSetting($pdo, $key, $fallback);

    return is_bool($value) ? $value : $fallback;

}


function getPlatformIntegerSettingSafe(PDO $pdo, string $key, int $fallback, int $min, int $max): int
{

    $value = getPlatformSetting($pdo, $key, $fallback);

    if (!is_int($value) && !ctype_digit((string) $value)) {

        return $fallback;

    }

    $value = (int) $value;

    if ($value < $min || $value > $max) {

        return $fallback;

    }

    return $value;

}


function getPlatformStringSettingSafe(PDO $pdo, string $key, string $fallback, array $allowedValues = []): string
{

    $value = getPlatformSetting($pdo, $key, $fallback);

    if (!is_string($value)) {

        return $fallback;

    }

    if (!empty($allowedValues) && !in_array($value, $allowedValues, true)) {

        return $fallback;

    }

    return $value;

}


function isPlatformRegistrationAllowed(PDO $pdo): bool
{

    return getPlatformBooleanSettingSafe($pdo, "allow_registration", true);

}


function getPlatformDefaultUserRole(PDO $pdo): string
{

    return getPlatformStringSettingSafe($pdo, "default_new_user_role", "admin", [
        "admin",
        "agent"
    ]);

}


function getPlatformDefaultUserStatus(PDO $pdo): string
{

    return getPlatformStringSettingSafe($pdo, "default_new_user_status", "active", [
        "active",
        "inactive"
    ]);

}


function getPlatformMinimumPasswordLength(PDO $pdo): int
{

    return getPlatformIntegerSettingSafe($pdo, "minimum_password_length", 8, 8, 64);

}


function getPlatformSessionIdleTimeout(PDO $pdo): int
{

    return getPlatformIntegerSettingSafe($pdo, "session_idle_timeout", 7200, 900, 86400);

}


function getPlatformName(PDO $pdo): string
{

    $name = trim(getPlatformStringSettingSafe($pdo, "platform_name", "XD Chat"));

    if ($name === "") {
        return "XD Chat";
    }

    return mb_substr($name, 0, 100, "UTF-8");

}


function getPlatformTagline(PDO $pdo): string
{

    $tagline = trim(getPlatformStringSettingSafe($pdo, "platform_tagline", "Live Chat Platform"));

    if ($tagline === "") {
        return "Live Chat Platform";
    }

    return mb_substr($tagline, 0, 180, "UTF-8");

}


function getPlatformSupportEmail(PDO $pdo): string
{

    $email = trim(getPlatformStringSettingSafe($pdo, "support_email", ""));

    return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : "";

}


function getPlatformCopyright(PDO $pdo): string
{

    $copyright = trim(getPlatformStringSettingSafe($pdo, "footer_copyright_text", ""));

    if ($copyright !== "") {
        return mb_substr($copyright, 0, 180, "UTF-8");
    }

    return "© " . date("Y") . " " . getPlatformName($pdo) . ". All rights reserved.";

}


function getPlatformPageTitle(PDO $pdo, string $pageTitle = ""): string
{

    $platformName = getPlatformName($pdo);

    $pageTitle = trim($pageTitle);

    if ($pageTitle === "") {
        return $platformName;
    }

    return $pageTitle . " | " . $platformName;

}


function getPlatformConfiguredBaseUrl(): string
{

    static $baseUrl = null;

    if ($baseUrl !== null) {
        return $baseUrl;
    }

    $configPath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . "config" . DIRECTORY_SEPARATOR . "app.php";
    $config = is_file($configPath) ? require $configPath : [];
    $baseUrl = rtrim((string) ($config["base_url"] ?? "/"), "/") . "/";

    return $baseUrl;

}


function getPlatformConfiguredBasePath(): string
{

    $configuredBaseUrl = trim(getPlatformConfiguredBaseUrl());

    $parsedBaseUrl = parse_url($configuredBaseUrl);

    if (!is_array($parsedBaseUrl)) {
        return "/";
    }

    $configuredScheme = strtolower((string) ($parsedBaseUrl["scheme"] ?? ""));

    $hasValidAbsoluteUrl = in_array($configuredScheme, ["http", "https"], true)
        && !empty($parsedBaseUrl["host"]);

    $hasValidRootRelativePath = strpos($configuredBaseUrl, "/") === 0;

    if (!$hasValidAbsoluteUrl && !$hasValidRootRelativePath) {
        return "/";
    }

    $basePath = $parsedBaseUrl["path"] ?? "/";

    if (!is_string($basePath) || $basePath === "") {
        return "/";
    }

    $basePath = str_replace("\\", "/", $basePath);

    $pathSegments = array_values(array_filter(
        explode("/", trim($basePath, "/")),
        static function (string $segment): bool {
            return $segment !== "" && $segment !== ".";
        }
    ));

    foreach ($pathSegments as $pathSegment) {

        $decodedPathSegment = rawurldecode($pathSegment);

        if (
            in_array($decodedPathSegment, [".", ".."], true)
            || strpos($decodedPathSegment, "/") !== false
            || strpos($decodedPathSegment, "\\") !== false
            || preg_match('/[\x00-\x1F\x7F]/', $decodedPathSegment)
            || preg_match('/\s/', $pathSegment)
        ) {
            return "/";
        }

    }

    if (empty($pathSegments)) {
        return "/";
    }

    return "/" . implode("/", $pathSegments) . "/";

}


function normalizePlatformBrandingSettingPath(string $path): string
{

    $path = trim(str_replace("\\", "/", $path));
    $path = ltrim($path, "/");

    if ($path === "" || strpos($path, "../") !== false || strpos($path, "/..") !== false) {
        return "";
    }

    if (strpos($path, "uploads/platform/branding/") !== 0) {
        return "";
    }

    if (basename($path) !== substr($path, strlen("uploads/platform/branding/"))) {
        return "";
    }

    return $path;

}


function getPlatformBrandingSettingPath(PDO $pdo, string $key, array $allowedExtensions): string
{

    $path = normalizePlatformBrandingSettingPath(
        getPlatformStringSettingSafe($pdo, $key, "")
    );

    if ($path === "") {
        return "";
    }

    $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

    if (!in_array($extension, $allowedExtensions, true)) {
        return "";
    }

    $absolutePath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . str_replace("/", DIRECTORY_SEPARATOR, $path);

    return is_file($absolutePath) ? $path : "";

}


function getPlatformLogoPath(PDO $pdo): string
{

    return getPlatformBrandingSettingPath($pdo, "platform_logo_path", [
        "jpg",
        "jpeg",
        "png",
        "webp"
    ]);

}


function getPlatformFaviconPath(PDO $pdo): string
{

    return getPlatformBrandingSettingPath($pdo, "platform_favicon_path", [
        "ico",
        "png",
        "jpg",
        "jpeg",
        "webp"
    ]);

}


function hasPlatformLogo(PDO $pdo): bool
{

    return getPlatformLogoPath($pdo) !== "";

}


function hasPlatformFavicon(PDO $pdo): bool
{

    return getPlatformFaviconPath($pdo) !== "";

}


function getPlatformBrandingAssetUrl(string $type): string
{

    return getPlatformConfiguredBasePath()
        . "platform-branding-asset.php?"
        . http_build_query([
            "type" => $type,
            "v" => time()
        ]);

}


function getPlatformLogoUrl(PDO $pdo): string
{

    return hasPlatformLogo($pdo) ? getPlatformBrandingAssetUrl("logo") : "";

}


function getPlatformFaviconUrl(PDO $pdo): string
{

    return hasPlatformFavicon($pdo) ? getPlatformBrandingAssetUrl("favicon") : "";

}


function getPlatformMessageMaxLength(PDO $pdo): int
{

    return getPlatformIntegerSettingSafe($pdo, "message_max_length", 1000, 100, 5000);

}


function getPlatformDefaultChatStatus(PDO $pdo): string
{

    return getPlatformStringSettingSafe($pdo, "default_chat_status", "open", [
        "open"
    ]);

}


function getPlatformDeleteEveryoneTimeLimit(PDO $pdo): int
{

    return getPlatformIntegerSettingSafe($pdo, "delete_everyone_time_limit", 60, 5, 1440);

}


function getPlatformDefaultWelcomeMessage(PDO $pdo): string
{

    $fallback = "Hi there! How can we help you today?";

    $message = getPlatformStringSettingSafe(
        $pdo,
        "default_welcome_message",
        $fallback
    );

    $message = trim($message);

    if ($message === "") {
        $message = $fallback;
    }

    return mb_substr($message, 0, 500, "UTF-8");

}


function getPlatformDefaultOfflineMessage(PDO $pdo): string
{

    $fallback = "We are currently offline. Please leave a message.";

    $message = getPlatformStringSettingSafe(
        $pdo,
        "default_offline_message",
        $fallback
    );

    $message = trim($message);

    if ($message === "") {
        $message = $fallback;
    }

    return mb_substr($message, 0, 500, "UTF-8");

}


function getPlatformUploadSettingMap(): array
{

    return [
        "images" => [
            "size_key" => "image_max_size_mb",
            "fallback_size" => 5,
            "min_size" => 1,
            "max_size" => 20,
            "types_key" => "allowed_image_types",
            "fallback_types" => ["jpg", "jpeg", "png", "webp"],
            "secure_whitelist" => ["jpg", "jpeg", "png", "webp"]
        ],
        "documents" => [
            "size_key" => "document_max_size_mb",
            "fallback_size" => 10,
            "min_size" => 1,
            "max_size" => 50,
            "types_key" => "allowed_document_types",
            "fallback_types" => ["pdf", "doc", "docx", "xls", "xlsx", "txt"],
            "secure_whitelist" => ["pdf", "doc", "docx", "xls", "xlsx", "txt"]
        ],
        "audio" => [
            "size_key" => "audio_max_size_mb",
            "fallback_size" => 10,
            "min_size" => 1,
            "max_size" => 50,
            "types_key" => "allowed_audio_types",
            "fallback_types" => ["mp3", "wav", "ogg", "webm"],
            "secure_whitelist" => ["mp3", "wav", "ogg", "webm"]
        ],
        "videos" => [
            "size_key" => "video_max_size_mb",
            "fallback_size" => 15,
            "min_size" => 1,
            "max_size" => 100,
            "types_key" => "allowed_video_types",
            "fallback_types" => ["mp4", "webm", "mov"],
            "secure_whitelist" => ["mp4", "webm", "mov"]
        ]
    ];

}


function normalizePlatformExtensionList(array $extensions, array $secureWhitelist): array
{

    $normalized = [];

    foreach ($extensions as $extension) {

        $extension = strtolower(trim((string) $extension));
        $extension = ltrim($extension, ".");

        if (
            $extension !== "" &&
            in_array($extension, $secureWhitelist, true) &&
            !in_array($extension, $normalized, true)
        ) {

            $normalized[] = $extension;

        }

    }

    return $normalized;

}


function getPlatformUploadMaxSizeMb(PDO $pdo, string $category): int
{

    $settings = getPlatformUploadSettingMap();

    if (!isset($settings[$category])) {
        return 10;
    }

    $rule = $settings[$category];

    return getPlatformIntegerSettingSafe(
        $pdo,
        $rule["size_key"],
        $rule["fallback_size"],
        $rule["min_size"],
        $rule["max_size"]
    );

}


function getPlatformUploadMaxSizeBytes(PDO $pdo, string $category): int
{

    return getPlatformUploadMaxSizeMb($pdo, $category) * 1024 * 1024;

}


function getPlatformAllowedUploadTypes(PDO $pdo, string $category): array
{

    $settings = getPlatformUploadSettingMap();

    if (!isset($settings[$category])) {
        return [];
    }

    $rule = $settings[$category];
    $value = getPlatformSetting($pdo, $rule["types_key"], $rule["fallback_types"]);

    if (!is_array($value)) {
        $value = $rule["fallback_types"];
    }

    $extensions = normalizePlatformExtensionList($value, $rule["secure_whitelist"]);

    return !empty($extensions)
        ? $extensions
        : $rule["fallback_types"];

}


function getPlatformUploadRuntimeConfig(PDO $pdo): array
{

    $config = [];

    foreach (array_keys(getPlatformUploadSettingMap()) as $category) {

        $config[$category] = [
            "maxSizeMb" => getPlatformUploadMaxSizeMb($pdo, $category),
            "extensions" => getPlatformAllowedUploadTypes($pdo, $category)
        ];

    }

    return $config;

}


function getPlatformSettingsByCategory(PDO $pdo, string $category): array
{

    $settings = getPlatformSettingsMap($pdo);

    return array_filter(
        $settings,
        function (array $setting) use ($category): bool {
            return $setting["category"] === $category;
        }
    );

}


/* ==========================================
   05. UPDATE SETTINGS
========================================== */

function updatePlatformSetting(PDO $pdo, string $key, $value, ?int $updatedBy = null): bool
{

    $definitions = getPlatformSettingDefinitions();

    if (!isset($definitions[$key])) {

        return false;

    }

    $definition = $definitions[$key];

    $statement = $pdo->prepare(
        "INSERT INTO platform_settings (
            setting_key,
            setting_value,
            value_type,
            category,
            label,
            description,
            is_sensitive,
            updated_by
        ) VALUES (
            :setting_key,
            :setting_value,
            :value_type,
            :category,
            :label,
            :description,
            0,
            :updated_by
        )
        ON DUPLICATE KEY UPDATE
            setting_value = VALUES(setting_value),
            value_type = VALUES(value_type),
            category = VALUES(category),
            label = VALUES(label),
            description = VALUES(description),
            updated_by = VALUES(updated_by)"
    );

    $statement->bindValue(":setting_key", $key);

    $statement->bindValue(":setting_value", encodePlatformSettingValue($value));

    $statement->bindValue(":value_type", $definition["type"]);

    $statement->bindValue(":category", $definition["category"]);

    $statement->bindValue(":label", $definition["label"]);

    $statement->bindValue(":description", $definition["description"]);

    $statement->bindValue(
        ":updated_by",
        $updatedBy,
        $updatedBy === null ? PDO::PARAM_NULL : PDO::PARAM_INT
    );

    $result = $statement->execute();

    if ($result) {

        $GLOBALS["platform_settings_cache_reset"] = true;

    }

    return $result;

}
