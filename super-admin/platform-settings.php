<?php
/*
==================================================
Project : XD Chat
Version : 2.0.0
File    : platform-settings.php
Module  : Super Admin Platform Settings
Status  : Development
Author  : Umesh + ChatGPT
Created : 10 July 2026
==================================================
*/


/* ==========================================
   01. LOAD DEPENDENCIES
========================================== */

require_once '../includes/functions/session.php';

require_once '../database/connection.php';

require_once '../includes/functions/audit.php';

require_once '../includes/functions/platform-settings.php';

require_once '../includes/functions/platform-branding.php';

requireRole([
    "super_admin"
]);


/* ==========================================
   02. OPTION HELPERS
========================================== */

function getPlatformSettingsAllowedOptions(): array
{

    return [
        "date_time_format" => [
            "d M Y, h:i A" => "10 Jul 2026, 04:30 PM",
            "Y-m-d H:i" => "2026-07-10 16:30",
            "d/m/Y H:i" => "10/07/2026 16:30",
            "M d, Y h:i A" => "Jul 10, 2026 04:30 PM"
        ],
        "default_new_user_role" => [
            "admin" => "Admin",
            "agent" => "Agent"
        ],
        "default_new_user_status" => [
            "active" => "Active",
            "inactive" => "Inactive"
        ],
        "default_chat_status" => [
            "open" => "Open"
        ],
        "allowed_image_types" => [
            "jpg",
            "jpeg",
            "png",
            "webp"
        ],
        "allowed_document_types" => [
            "pdf",
            "doc",
            "docx",
            "xls",
            "xlsx",
            "txt"
        ],
        "allowed_audio_types" => [
            "mp3",
            "wav",
            "ogg",
            "webm"
        ],
        "allowed_video_types" => [
            "mp4",
            "webm",
            "mov"
        ]
    ];

}


function getPlatformSettingsCategories(): array
{

    return [
        "general" => [
            "title" => "General Settings",
            "description" => "Basic platform identity and regional display settings."
        ],
        "security" => [
            "title" => "User & Security Settings",
            "description" => "Stored account defaults and future security controls."
        ],
        "chat" => [
            "title" => "Chat Settings",
            "description" => "Stored chat defaults for later runtime integration."
        ],
        "upload" => [
            "title" => "Upload Settings",
            "description" => "Stored file limits and allowed file-type controls."
        ],
        "system" => [
            "title" => "System Settings",
            "description" => "Stored maintenance settings. Runtime enforcement is not active yet."
        ]
    ];

}


function getPlatformSettingsRedirect(string $message, string $type, string $category): string
{

    return "platform-settings.php?"
        . http_build_query([
            "settings_message" => $message,
            "settings_type" => $type,
            "category" => $category
        ]);

}


function normalizePlatformSettingsCheckboxes(array $values, array $allowedValues): array
{

    $values = array_values(array_unique(array_map("strtolower", $values)));

    return array_values(array_intersect($allowedValues, $values));

}


/* ==========================================
   03. VALIDATION
========================================== */

function validatePlatformSettingsCategory(string $category, array $input): array
{

    $errors = [];

    $values = [];

    $options = getPlatformSettingsAllowedOptions();

    if ($category === "general") {

        $values["platform_name"] = trim($input["platform_name"] ?? "");

        $values["platform_tagline"] = trim($input["platform_tagline"] ?? "");

        $values["support_email"] = trim($input["support_email"] ?? "");

        $values["support_phone"] = trim($input["support_phone"] ?? "");

        $values["footer_copyright_text"] = trim($input["footer_copyright_text"] ?? "");

        $values["default_timezone"] = trim($input["default_timezone"] ?? "");

        $values["date_time_format"] = trim($input["date_time_format"] ?? "");

        if ($values["platform_name"] === "" || mb_strlen($values["platform_name"], "UTF-8") > 100) {
            $errors[] = "Platform name is required and must be 100 characters or less.";
        }

        if (mb_strlen($values["platform_tagline"], "UTF-8") > 180) {
            $errors[] = "Platform tagline must be 180 characters or less.";
        }

        if ($values["support_email"] !== "" && !filter_var($values["support_email"], FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Support email must be valid or empty.";
        }

        if (mb_strlen($values["support_phone"], "UTF-8") > 30) {
            $errors[] = "Support phone must be 30 characters or less.";
        }

        if (mb_strlen($values["footer_copyright_text"], "UTF-8") > 180) {
            $errors[] = "Footer copyright text must be 180 characters or less.";
        }

        if (!in_array($values["default_timezone"], timezone_identifiers_list(), true)) {
            $errors[] = "Please select a valid timezone.";
        }

        if (!isset($options["date_time_format"][$values["date_time_format"]])) {
            $errors[] = "Please select a valid date format.";
        }

    } elseif ($category === "security") {

        $values["default_new_user_role"] = trim($input["default_new_user_role"] ?? "");

        $values["default_new_user_status"] = trim($input["default_new_user_status"] ?? "");

        $values["session_idle_timeout"] = (int) ($input["session_idle_timeout"] ?? 0);

        $values["minimum_password_length"] = (int) ($input["minimum_password_length"] ?? 0);

        $values["allow_registration"] = !empty($input["allow_registration"]);

        if (!isset($options["default_new_user_role"][$values["default_new_user_role"]])) {
            $errors[] = "Default role must be Admin or Agent.";
        }

        if (!isset($options["default_new_user_status"][$values["default_new_user_status"]])) {
            $errors[] = "Default status must be Active or Inactive.";
        }

        if ($values["session_idle_timeout"] < 900 || $values["session_idle_timeout"] > 86400) {
            $errors[] = "Session timeout must be between 900 and 86400 seconds.";
        }

        if ($values["minimum_password_length"] < 8 || $values["minimum_password_length"] > 64) {
            $errors[] = "Minimum password length must be between 8 and 64.";
        }

    } elseif ($category === "chat") {

        $values["default_welcome_message"] = trim($input["default_welcome_message"] ?? "");

        $values["default_offline_message"] = trim($input["default_offline_message"] ?? "");

        $values["default_chat_status"] = trim($input["default_chat_status"] ?? "");

        $values["message_max_length"] = (int) ($input["message_max_length"] ?? 0);

        $values["delete_everyone_time_limit"] = (int) ($input["delete_everyone_time_limit"] ?? 0);

        if (strlen($values["default_welcome_message"]) > 500) {
            $errors[] = "Welcome message must be 500 characters or less.";
        }

        if (strlen($values["default_offline_message"]) > 500) {
            $errors[] = "Offline message must be 500 characters or less.";
        }

        if (!isset($options["default_chat_status"][$values["default_chat_status"]])) {
            $errors[] = "Default chat status must be Open.";
        }

        if ($values["message_max_length"] < 100 || $values["message_max_length"] > 5000) {
            $errors[] = "Message maximum length must be between 100 and 5000.";
        }

        if ($values["delete_everyone_time_limit"] < 5 || $values["delete_everyone_time_limit"] > 1440) {
            $errors[] = "Delete time limit must be between 5 and 1440 minutes.";
        }

    } elseif ($category === "upload") {

        $values["image_max_size_mb"] = (int) ($input["image_max_size_mb"] ?? 0);

        $values["document_max_size_mb"] = (int) ($input["document_max_size_mb"] ?? 0);

        $values["audio_max_size_mb"] = (int) ($input["audio_max_size_mb"] ?? 0);

        $values["video_max_size_mb"] = (int) ($input["video_max_size_mb"] ?? 0);

        $values["allowed_image_types"] = normalizePlatformSettingsCheckboxes($input["allowed_image_types"] ?? [], $options["allowed_image_types"]);

        $values["allowed_document_types"] = normalizePlatformSettingsCheckboxes($input["allowed_document_types"] ?? [], $options["allowed_document_types"]);

        $values["allowed_audio_types"] = normalizePlatformSettingsCheckboxes($input["allowed_audio_types"] ?? [], $options["allowed_audio_types"]);

        $values["allowed_video_types"] = normalizePlatformSettingsCheckboxes($input["allowed_video_types"] ?? [], $options["allowed_video_types"]);

        if ($values["image_max_size_mb"] < 1 || $values["image_max_size_mb"] > 20) {
            $errors[] = "Image size must be between 1 and 20 MB.";
        }

        if ($values["document_max_size_mb"] < 1 || $values["document_max_size_mb"] > 50) {
            $errors[] = "Document size must be between 1 and 50 MB.";
        }

        if ($values["audio_max_size_mb"] < 1 || $values["audio_max_size_mb"] > 50) {
            $errors[] = "Audio size must be between 1 and 50 MB.";
        }

        if ($values["video_max_size_mb"] < 1 || $values["video_max_size_mb"] > 100) {
            $errors[] = "Video size must be between 1 and 100 MB.";
        }

        foreach ([
            "allowed_image_types" => "image",
            "allowed_document_types" => "document",
            "allowed_audio_types" => "audio",
            "allowed_video_types" => "video"
        ] as $key => $label) {
            if (empty($values[$key])) {
                $errors[] = "At least one " . $label . " file type must be selected.";
            }
        }

    } elseif ($category === "system") {

        $values["maintenance_mode"] = !empty($input["maintenance_mode"]);

        $values["maintenance_message"] = trim($input["maintenance_message"] ?? "");

        if ($values["maintenance_message"] === "" || strlen($values["maintenance_message"]) > 300) {
            $errors[] = "Maintenance message is required and must be 300 characters or less.";
        }

    } else {

        $errors[] = "Invalid settings category.";

    }

    return [
        "errors" => $errors,
        "values" => $values
    ];

}


/* ==========================================
   04. HANDLE POST REQUEST
========================================== */

$categories = getPlatformSettingsCategories();

$requestMethod = $_SERVER["REQUEST_METHOD"] ?? "GET";

if ($requestMethod === "POST") {

    $brandingAction = $_POST["branding_action"] ?? "";

    if (in_array($brandingAction, [
        "upload_platform_logo",
        "remove_platform_logo",
        "upload_platform_favicon",
        "remove_platform_favicon"
    ], true)) {

        $csrfToken = $_POST["csrf_token"] ?? "";

        if (!verifyCsrfToken($csrfToken)) {

            header("Location: " . getPlatformSettingsRedirect("Invalid request. Please try again.", "error", "general"));

            exit;

        }

        $type = in_array($brandingAction, [
            "upload_platform_logo",
            "remove_platform_logo"
        ], true) ? "logo" : "favicon";

        $settingKey = $type === "logo" ? "platform_logo_path" : "platform_favicon_path";
        $fileKey = $type === "logo" ? "platform_logo" : "platform_favicon";
        $oldPath = $type === "logo" ? getPlatformLogoPath($pdo) : getPlatformFaviconPath($pdo);
        $label = $type === "logo" ? "Logo" : "Favicon";

        try {

            if (strpos($brandingAction, "upload_") === 0) {

                $upload = savePlatformBrandingUpload($_FILES[$fileKey] ?? [], $type);

                if (empty($upload["success"]) || empty($upload["relative_path"])) {

                    header("Location: " . getPlatformSettingsRedirect($upload["message"] ?? "Unable to save uploaded file.", "error", "general"));

                    exit;

                }

                $newPath = $upload["relative_path"];

                if (!updatePlatformSetting($pdo, $settingKey, $newPath, (int) ($_SESSION["user_id"] ?? 0))) {

                    removePlatformBrandingFile($newPath);

                    header("Location: " . getPlatformSettingsRedirect("Unable to save " . strtolower($label) . " setting.", "error", "general"));

                    exit;

                }

                if ($oldPath !== "" && $oldPath !== $newPath) {
                    removePlatformBrandingFile($oldPath);
                }

                createAuditLog($pdo, [
                    "actor_user_id" => (int) ($_SESSION["user_id"] ?? 0),
                    "actor_name" => $_SESSION["user_name"] ?? "Super Admin",
                    "action" => "platform_settings_updated",
                    "target_type" => "platform_settings",
                    "target_id" => 0,
                    "target_name" => "General Settings",
                    "description" => "Updated general settings: " . $settingKey
                ]);

                header("Location: " . getPlatformSettingsRedirect($label . " uploaded successfully.", "success", "general"));

                exit;

            }

            if (!updatePlatformSetting($pdo, $settingKey, "", (int) ($_SESSION["user_id"] ?? 0))) {

                header("Location: " . getPlatformSettingsRedirect("Unable to remove " . strtolower($label) . ".", "error", "general"));

                exit;

            }

            if ($oldPath !== "") {
                removePlatformBrandingFile($oldPath);
            }

            createAuditLog($pdo, [
                "actor_user_id" => (int) ($_SESSION["user_id"] ?? 0),
                "actor_name" => $_SESSION["user_name"] ?? "Super Admin",
                "action" => "platform_settings_updated",
                "target_type" => "platform_settings",
                "target_id" => 0,
                "target_name" => "General Settings",
                "description" => "Updated general settings: " . $settingKey
            ]);

            header("Location: " . getPlatformSettingsRedirect($label . " reset successfully.", "success", "general"));

            exit;

        } catch (Throwable $exception) {

            header("Location: " . getPlatformSettingsRedirect("Unable to update branding asset. Please try again.", "error", "general"));

            exit;

        }

    }

    $category = $_POST["category"] ?? "";

    $csrfToken = $_POST["csrf_token"] ?? "";

    if (!isset($categories[$category])) {

        header("Location: " . getPlatformSettingsRedirect("Invalid settings section.", "error", "general"));

        exit;

    }

    if (!verifyCsrfToken($csrfToken)) {

        header("Location: " . getPlatformSettingsRedirect("Invalid request. Please try again.", "error", $category));

        exit;

    }

    $currentSettings = getPlatformSettingsMap($pdo);

    $validation = validatePlatformSettingsCategory($category, $_POST);

    if (!empty($validation["errors"])) {

        header("Location: " . getPlatformSettingsRedirect(implode(" ", $validation["errors"]), "error", $category));

        exit;

    }

    $changedKeys = [];

    try {

        foreach ($validation["values"] as $key => $value) {

            if (($currentSettings[$key]["value"] ?? null) !== $value) {

                $changedKeys[] = $key;

            }

            updatePlatformSetting(
                $pdo,
                $key,
                $value,
                (int) ($_SESSION["user_id"] ?? 0)
            );

        }

        if (!empty($changedKeys)) {

            createAuditLog($pdo, [
                "actor_user_id" => (int) ($_SESSION["user_id"] ?? 0),
                "actor_name" => $_SESSION["user_name"] ?? "Super Admin",
                "action" => "platform_settings_updated",
                "target_type" => "platform_settings",
                "target_id" => 0,
                "target_name" => $categories[$category]["title"],
                "description" => "Updated " . $category . " settings: " . implode(", ", $changedKeys)
            ]);

        }

        header("Location: " . getPlatformSettingsRedirect("Platform settings saved successfully.", "success", $category));

        exit;

    } catch (Throwable $exception) {

        header("Location: " . getPlatformSettingsRedirect("Unable to save platform settings. Please try again.", "error", $category));

        exit;

    }

}


/* ==========================================
   05. PAGE CONFIGURATION
========================================== */

$page_title = getPlatformPageTitle($pdo, "Platform Settings");

$page_heading = "Platform Settings";

$page_description = "Manage stored global settings for the " . getPlatformName($pdo) . " platform.";

$active_menu = "platform_settings";

$settings = getPlatformSettingsMap($pdo);

$activeCategory = $_GET["category"] ?? "general";

if (!isset($categories[$activeCategory])) {

    $activeCategory = "general";

}

$settingsMessage = $_GET["settings_message"] ?? "";

$settingsType = $_GET["settings_type"] ?? "";

if (!in_array($settingsType, [
    "success",
    "error"
], true)) {

    $settingsType = "error";

}

$options = getPlatformSettingsAllowedOptions();

$platformLogoUrl = getPlatformLogoUrl($pdo);

$platformFaviconUrl = getPlatformFaviconUrl($pdo);

require_once 'includes/header.php';
?>

<section class="xd-sa-platform-note">
    <strong>Phase 11A Foundation</strong>
    <span>Platform name and tagline are connected to Dashboard and Super Admin runtime. Logo and favicon branding will be added separately.</span>
</section>

<?php if ($settingsMessage !== "") { ?>

    <div class="xd-sa-alert <?php echo htmlspecialchars($settingsType); ?>">
        <?php echo htmlspecialchars($settingsMessage); ?>
    </div>

<?php } ?>

<section class="xd-sa-platform-tabs">
    <?php foreach ($categories as $categoryKey => $categoryData) { ?>
        <a href="platform-settings.php?category=<?php echo htmlspecialchars($categoryKey); ?>"
           class="<?php echo $activeCategory === $categoryKey ? "active" : ""; ?>">
            <?php echo htmlspecialchars($categoryData["title"]); ?>
        </a>
    <?php } ?>
</section>

<?php if ($activeCategory === "general") { ?>

<section class="xd-sa-branding-grid">

    <article class="xd-sa-branding-card">
        <div>
            <h3>Platform Logo</h3>
            <p>JPG, PNG or WEBP, maximum 2 MB.</p>
        </div>

        <div class="xd-sa-branding-preview xd-sa-branding-logo-preview">
            <?php if ($platformLogoUrl !== "") { ?>
                <img src="<?php echo htmlspecialchars($platformLogoUrl); ?>"
                     alt="<?php echo htmlspecialchars(getPlatformName($pdo)); ?> logo">
            <?php } else { ?>
                <i class="fa-regular fa-comments"></i>
            <?php } ?>
        </div>

        <form method="POST"
              enctype="multipart/form-data"
              class="xd-sa-branding-form">
            <input type="hidden"
                   name="csrf_token"
                   value="<?php echo htmlspecialchars(getCsrfToken()); ?>">
            <input type="hidden"
                   name="branding_action"
                   value="upload_platform_logo">
            <input type="file"
                   name="platform_logo"
                   accept=".jpg,.jpeg,.png,.webp"
                   required>
            <button type="submit">Upload / Replace Logo</button>
        </form>

        <?php if ($platformLogoUrl !== "") { ?>
            <form method="POST"
                  class="xd-sa-branding-remove"
                  onsubmit="return confirm('Remove platform logo?');">
                <input type="hidden"
                       name="csrf_token"
                       value="<?php echo htmlspecialchars(getCsrfToken()); ?>">
                <input type="hidden"
                       name="branding_action"
                       value="remove_platform_logo">
                <button type="submit">Remove / Reset Logo</button>
            </form>
        <?php } ?>
    </article>

    <article class="xd-sa-branding-card">
        <div>
            <h3>Favicon</h3>
            <p>ICO, PNG, JPG or WEBP, maximum 1 MB.</p>
        </div>

        <div class="xd-sa-branding-preview xd-sa-branding-favicon-preview">
            <?php if ($platformFaviconUrl !== "") { ?>
                <img src="<?php echo htmlspecialchars($platformFaviconUrl); ?>"
                     alt="<?php echo htmlspecialchars(getPlatformName($pdo)); ?> favicon">
            <?php } else { ?>
                <i class="fa-regular fa-comments"></i>
            <?php } ?>
        </div>

        <form method="POST"
              enctype="multipart/form-data"
              class="xd-sa-branding-form">
            <input type="hidden"
                   name="csrf_token"
                   value="<?php echo htmlspecialchars(getCsrfToken()); ?>">
            <input type="hidden"
                   name="branding_action"
                   value="upload_platform_favicon">
            <input type="file"
                   name="platform_favicon"
                   accept=".ico,.png,.jpg,.jpeg,.webp"
                   required>
            <button type="submit">Upload / Replace Favicon</button>
        </form>

        <?php if ($platformFaviconUrl !== "") { ?>
            <form method="POST"
                  class="xd-sa-branding-remove"
                  onsubmit="return confirm('Remove platform favicon?');">
                <input type="hidden"
                       name="csrf_token"
                       value="<?php echo htmlspecialchars(getCsrfToken()); ?>">
                <input type="hidden"
                       name="branding_action"
                       value="remove_platform_favicon">
                <button type="submit">Remove / Reset Favicon</button>
            </form>
        <?php } ?>
    </article>

</section>

<?php } ?>

<section class="xd-sa-settings-card xd-sa-platform-card">

    <div class="xd-sa-panel-header">
        <div>
            <h2><?php echo htmlspecialchars($categories[$activeCategory]["title"]); ?></h2>
            <p><?php echo htmlspecialchars($categories[$activeCategory]["description"]); ?></p>
        </div>
    </div>

    <form method="POST" class="xd-sa-settings-form xd-sa-platform-form">

        <input type="hidden"
               name="csrf_token"
               value="<?php echo htmlspecialchars(getCsrfToken()); ?>">

        <input type="hidden"
               name="category"
               value="<?php echo htmlspecialchars($activeCategory); ?>">

        <?php if ($activeCategory === "general") { ?>

            <div class="xd-sa-platform-grid">
                <div class="xd-sa-settings-field">
                    <label>Platform Name</label>
                    <input type="text"
                           name="platform_name"
                           maxlength="100"
                           value="<?php echo htmlspecialchars($settings["platform_name"]["value"]); ?>"
                           required>
                </div>
                <div class="xd-sa-settings-field">
                    <label>Platform Tagline</label>
                    <input type="text"
                           name="platform_tagline"
                           maxlength="180"
                           value="<?php echo htmlspecialchars($settings["platform_tagline"]["value"]); ?>">
                </div>
                <div class="xd-sa-settings-field">
                    <label>Support Email</label>
                    <input type="email"
                           name="support_email"
                           value="<?php echo htmlspecialchars($settings["support_email"]["value"]); ?>">
                </div>
                <div class="xd-sa-settings-field">
                    <label>Support Phone</label>
                    <input type="text"
                           name="support_phone"
                           maxlength="30"
                           value="<?php echo htmlspecialchars($settings["support_phone"]["value"]); ?>">
                </div>
                <div class="xd-sa-settings-field xd-sa-platform-wide">
                    <label>Footer / Copyright Text</label>
                    <input type="text"
                           name="footer_copyright_text"
                           maxlength="180"
                           placeholder="<?php echo htmlspecialchars(getPlatformCopyright($pdo)); ?>"
                           value="<?php echo htmlspecialchars($settings["footer_copyright_text"]["value"]); ?>">
                </div>
                <div class="xd-sa-settings-field">
                    <label>Default Timezone</label>
                    <select name="default_timezone" required>
                        <?php foreach (timezone_identifiers_list() as $timezone) { ?>
                            <option value="<?php echo htmlspecialchars($timezone); ?>"
                                <?php echo $settings["default_timezone"]["value"] === $timezone ? "selected" : ""; ?>>
                                <?php echo htmlspecialchars($timezone); ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>
                <div class="xd-sa-settings-field">
                    <label>Date Time Format</label>
                    <select name="date_time_format" required>
                        <?php foreach ($options["date_time_format"] as $format => $label) { ?>
                            <option value="<?php echo htmlspecialchars($format); ?>"
                                <?php echo $settings["date_time_format"]["value"] === $format ? "selected" : ""; ?>>
                                <?php echo htmlspecialchars($label); ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>
            </div>

        <?php } elseif ($activeCategory === "security") { ?>

            <div class="xd-sa-platform-grid">
                <div class="xd-sa-settings-field">
                    <label>Default New User Role</label>
                    <select name="default_new_user_role" required>
                        <?php foreach ($options["default_new_user_role"] as $value => $label) { ?>
                            <option value="<?php echo htmlspecialchars($value); ?>"
                                <?php echo $settings["default_new_user_role"]["value"] === $value ? "selected" : ""; ?>>
                                <?php echo htmlspecialchars($label); ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>
                <div class="xd-sa-settings-field">
                    <label>Default New User Status</label>
                    <select name="default_new_user_status" required>
                        <?php foreach ($options["default_new_user_status"] as $value => $label) { ?>
                            <option value="<?php echo htmlspecialchars($value); ?>"
                                <?php echo $settings["default_new_user_status"]["value"] === $value ? "selected" : ""; ?>>
                                <?php echo htmlspecialchars($label); ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>
                <div class="xd-sa-settings-field">
                    <label>Session Idle Timeout</label>
                    <input type="number"
                           name="session_idle_timeout"
                           min="900"
                           max="86400"
                           value="<?php echo htmlspecialchars((string) $settings["session_idle_timeout"]["value"]); ?>"
                           required>
                </div>
                <div class="xd-sa-settings-field">
                    <label>Minimum Password Length</label>
                    <input type="number"
                           name="minimum_password_length"
                           min="8"
                           max="64"
                           value="<?php echo htmlspecialchars((string) $settings["minimum_password_length"]["value"]); ?>"
                           required>
                </div>
                <label class="xd-sa-platform-toggle">
                    <input type="checkbox"
                           name="allow_registration"
                           value="1"
                        <?php echo !empty($settings["allow_registration"]["value"]) ? "checked" : ""; ?>>
                    <span></span>
                    <strong>Allow Registration</strong>
                </label>
            </div>

        <?php } elseif ($activeCategory === "chat") { ?>

            <div class="xd-sa-platform-grid">
                <div class="xd-sa-settings-field xd-sa-platform-wide">
                    <label>Default Welcome Message</label>
                    <textarea name="default_welcome_message"
                              maxlength="500"
                              rows="4"><?php echo htmlspecialchars($settings["default_welcome_message"]["value"]); ?></textarea>
                </div>
                <div class="xd-sa-settings-field xd-sa-platform-wide">
                    <label>Default Offline Message</label>
                    <textarea name="default_offline_message"
                              maxlength="500"
                              rows="4"><?php echo htmlspecialchars($settings["default_offline_message"]["value"]); ?></textarea>
                </div>
                <div class="xd-sa-settings-field">
                    <label>Default Chat Status</label>
                    <select name="default_chat_status" required>
                        <option value="open" <?php echo $settings["default_chat_status"]["value"] === "open" ? "selected" : ""; ?>>Open</option>
                    </select>
                </div>
                <div class="xd-sa-settings-field">
                    <label>Message Maximum Length</label>
                    <input type="number"
                           name="message_max_length"
                           min="100"
                           max="5000"
                           value="<?php echo htmlspecialchars((string) $settings["message_max_length"]["value"]); ?>"
                           required>
                </div>
                <div class="xd-sa-settings-field">
                    <label>Delete From Everyone Time Limit</label>
                    <input type="number"
                           name="delete_everyone_time_limit"
                           min="5"
                           max="1440"
                           value="<?php echo htmlspecialchars((string) $settings["delete_everyone_time_limit"]["value"]); ?>"
                           required>
                </div>
            </div>

        <?php } elseif ($activeCategory === "upload") { ?>

            <div class="xd-sa-platform-warning">
                Application upload settings cannot exceed server PHP limits such as upload_max_filesize and post_max_size.
            </div>

            <div class="xd-sa-platform-grid">
                <div class="xd-sa-settings-field">
                    <label>Image Max Size MB</label>
                    <input type="number"
                           name="image_max_size_mb"
                           min="1"
                           max="20"
                           value="<?php echo htmlspecialchars((string) $settings["image_max_size_mb"]["value"]); ?>"
                           required>
                </div>
                <div class="xd-sa-settings-field">
                    <label>Document Max Size MB</label>
                    <input type="number"
                           name="document_max_size_mb"
                           min="1"
                           max="50"
                           value="<?php echo htmlspecialchars((string) $settings["document_max_size_mb"]["value"]); ?>"
                           required>
                </div>
                <div class="xd-sa-settings-field">
                    <label>Audio Max Size MB</label>
                    <input type="number"
                           name="audio_max_size_mb"
                           min="1"
                           max="50"
                           value="<?php echo htmlspecialchars((string) $settings["audio_max_size_mb"]["value"]); ?>"
                           required>
                </div>
                <div class="xd-sa-settings-field">
                    <label>Video Max Size MB</label>
                    <input type="number"
                           name="video_max_size_mb"
                           min="1"
                           max="100"
                           value="<?php echo htmlspecialchars((string) $settings["video_max_size_mb"]["value"]); ?>"
                           required>
                </div>

                <?php foreach ([
                    "allowed_image_types" => "Allowed Image Types",
                    "allowed_document_types" => "Allowed Document Types",
                    "allowed_audio_types" => "Allowed Audio Types",
                    "allowed_video_types" => "Allowed Video Types"
                ] as $key => $label) { ?>
                    <div class="xd-sa-settings-field xd-sa-platform-wide">
                        <label><?php echo htmlspecialchars($label); ?></label>
                        <div class="xd-sa-platform-checkboxes">
                            <?php foreach ($options[$key] as $extension) { ?>
                                <label>
                                    <input type="checkbox"
                                           name="<?php echo htmlspecialchars($key); ?>[]"
                                           value="<?php echo htmlspecialchars($extension); ?>"
                                        <?php echo in_array($extension, $settings[$key]["value"], true) ? "checked" : ""; ?>>
                                    <span><?php echo htmlspecialchars($extension); ?></span>
                                </label>
                            <?php } ?>
                        </div>
                    </div>
                <?php } ?>
            </div>

        <?php } elseif ($activeCategory === "system") { ?>

            <div class="xd-sa-platform-grid">
                <label class="xd-sa-platform-toggle">
                    <input type="checkbox"
                           name="maintenance_mode"
                           value="1"
                        <?php echo !empty($settings["maintenance_mode"]["value"]) ? "checked" : ""; ?>>
                    <span></span>
                    <strong>Maintenance Mode</strong>
                </label>
                <div class="xd-sa-settings-field xd-sa-platform-wide">
                    <label>Maintenance Message</label>
                    <textarea name="maintenance_message"
                              maxlength="300"
                              rows="4"
                              required><?php echo htmlspecialchars($settings["maintenance_message"]["value"]); ?></textarea>
                </div>
                <div class="xd-sa-platform-warning xd-sa-platform-wide">
                    Maintenance mode is stored only in Phase 11A. Runtime enforcement will be implemented in a later phase.
                </div>
            </div>

        <?php } ?>

        <div class="xd-sa-settings-actions">
            <button type="submit">
                Save <?php echo htmlspecialchars($categories[$activeCategory]["title"]); ?>
            </button>
        </div>

    </form>

</section>

<?php require_once 'includes/footer.php'; ?>
