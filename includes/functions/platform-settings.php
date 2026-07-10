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
   04. UPDATE SETTINGS
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
