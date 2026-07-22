<?php
/*
==================================================
Project : XD Chat
Version : 2.0.0
File    : settings.php
Module  : Dashboard Account Settings
Status  : Development
Author  : Umesh + ChatGPT
Created : 13 July 2026
==================================================
*/


/* ==========================================
   01. LOAD DEPENDENCIES
========================================== */

require_once '../includes/functions/session.php';

require_once '../database/connection.php';

require_once '../includes/functions/settings.php';

require_once '../includes/functions/platform-settings.php';

require_once '../includes/functions/audit.php';

requireLogin();


/* ==========================================
   02. SETTINGS HELPERS
========================================== */

function xdSettingsEscape($value): string
{

    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");

}


function xdSettingsLength(string $value): int
{

    return function_exists("mb_strlen")
        ? mb_strlen($value, "UTF-8")
        : strlen($value);

}


function setAdminSettingsFlash(string $message, string $type = "error"): void
{

    $_SESSION["admin_settings_flash"] = [
        "message" => $message,
        "type" => in_array($type, ["success", "error"], true)
            ? $type
            : "error"
    ];

}


function pullAdminSettingsFlash(): array
{

    $flash = $_SESSION["admin_settings_flash"] ?? [];

    unset($_SESSION["admin_settings_flash"]);

    return [
        "message" => (string) ($flash["message"] ?? ""),
        "type" => in_array(($flash["type"] ?? ""), ["success", "error"], true)
            ? $flash["type"]
            : "error"
    ];

}


function redirectToAdminSettings(): void
{

    header("Location: settings.php");

    exit;

}


function logAdminSettingsAction(PDO $pdo, string $action, string $description): void
{

    createAuditLog($pdo, [
        "actor_user_id" => (int) ($_SESSION["user_id"] ?? 0),
        "actor_name" => (string) ($_SESSION["user_name"] ?? "Admin"),
        "action" => $action,
        "target_type" => "user",
        "target_id" => (int) ($_SESSION["user_id"] ?? 0),
        "target_name" => (string) ($_SESSION["user_name"] ?? "Admin"),
        "description" => $description
    ]);

}


/* ==========================================
   03. LOAD AND AUTHORIZE CURRENT USER
========================================== */

$currentUserId = (int) ($_SESSION["user_id"] ?? 0);

$currentUser = getAccountSettingsUser($pdo, $currentUserId);

if (!$currentUser) {

    logoutUser();

    header("Location: ../auth/login.php");

    exit;

}

$_SESSION["user_role"] = $currentUser["role"];

if ($currentUser["role"] === "super_admin") {

    header("Location: /XD-Chat/super-admin/settings.php");

    exit;

}

if (!in_array($currentUser["role"], ["admin", "agent"], true)) {

    header("Location: index.php");

    exit;

}


/* ==========================================
   04. PAGE CONFIGURATION
========================================== */

$page_title = getPlatformPageTitle($pdo, "Settings");

$page_heading = "Settings";

$page_description = "Manage your profile and account password.";

$minimumPasswordLength = getPlatformMinimumPasswordLength($pdo);

$maximumPasswordLength = 128;


/* ==========================================
   05. HANDLE POST REQUESTS
========================================== */

$requestMethod = $_SERVER["REQUEST_METHOD"] ?? "GET";

if ($requestMethod === "POST") {

    $action = (string) ($_POST["action"] ?? "");

    $csrfToken = (string) ($_POST["csrf_token"] ?? "");

    if (!verifyCsrfToken($csrfToken)) {

        setAdminSettingsFlash("Invalid request. Please try again.");

        redirectToAdminSettings();

    }

    $currentUser = getAccountSettingsUser($pdo, $currentUserId);

    if (!$currentUser) {

        logoutUser();

        header("Location: ../auth/login.php");

        exit;

    }

    if ($action === "update_profile") {

        $fullName = trim((string) ($_POST["full_name"] ?? ""));

        $email = strtolower(trim((string) ($_POST["email"] ?? "")));

        $currentPassword = (string) ($_POST["profile_current_password"] ?? "");

        if ($fullName === "" || $email === "" || $currentPassword === "") {

            setAdminSettingsFlash("Full name, email, and current password are required.");

        } elseif (xdSettingsLength($fullName) > 100) {

            setAdminSettingsFlash("Full name cannot exceed 100 characters.");

        } elseif (preg_match('/[\x00-\x1F\x7F]/u', $fullName)) {

            setAdminSettingsFlash("Full name contains unsupported characters.");

        } elseif (xdSettingsLength($email) > 150 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {

            setAdminSettingsFlash("Please enter a valid email address.");

        } elseif (!password_verify($currentPassword, $currentUser["password"])) {

            setAdminSettingsFlash("Current password is incorrect.");

        } else {

            try {

                if (accountSettingsEmailBelongsToAnotherUser($pdo, $email, $currentUserId)) {

                    setAdminSettingsFlash("Email address is already used by another account.");

                } else {

                    updateOwnAccountProfile(
                        $pdo,
                        $currentUserId,
                        $fullName,
                        $email
                    );

                    $_SESSION["user_name"] = $fullName;

                    $_SESSION["user_email"] = $email;

                    generateCsrfToken();

                    logAdminSettingsAction(
                        $pdo,
                        "account_profile_updated",
                        "Account profile information was updated."
                    );

                    setAdminSettingsFlash("Profile updated successfully.", "success");

                }

            } catch (PDOException $exception) {

                if ((string) $exception->getCode() === "23000") {

                    setAdminSettingsFlash("Email address is already used by another account.");

                } else {

                    setAdminSettingsFlash("Unable to update profile. Please try again.");

                }

            } catch (Throwable $exception) {

                setAdminSettingsFlash("Unable to update profile. Please try again.");

            }

        }

    } elseif ($action === "change_password") {

        $currentPassword = (string) ($_POST["current_password"] ?? "");

        $newPassword = (string) ($_POST["new_password"] ?? "");

        $confirmPassword = (string) ($_POST["confirm_password"] ?? "");

        if ($currentPassword === "" || $newPassword === "" || $confirmPassword === "") {

            setAdminSettingsFlash("All password fields are required.");

        } elseif (!password_verify($currentPassword, $currentUser["password"])) {

            setAdminSettingsFlash("Current password is incorrect.");

        } elseif ($newPassword !== $confirmPassword) {

            setAdminSettingsFlash("New password and confirm password do not match.");

        } elseif (xdSettingsLength($newPassword) < $minimumPasswordLength) {

            setAdminSettingsFlash(
                "New password must be at least " . $minimumPasswordLength . " characters."
            );

        } elseif (xdSettingsLength($newPassword) > $maximumPasswordLength) {

            setAdminSettingsFlash(
                "New password cannot exceed " . $maximumPasswordLength . " characters."
            );

        } elseif (password_verify($newPassword, $currentUser["password"])) {

            setAdminSettingsFlash("New password must be different from the current password.");

        } else {

            try {

                $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);

                if ($passwordHash === false) {

                    throw new RuntimeException("Password hashing failed.");

                }

                updateOwnAccountPassword(
                    $pdo,
                    $currentUserId,
                    $passwordHash
                );

                session_regenerate_id(true);

                generateCsrfToken();

                logAdminSettingsAction(
                    $pdo,
                    "account_password_changed",
                    "Account password was changed."
                );

                setAdminSettingsFlash("Password changed successfully.", "success");

            } catch (Throwable $exception) {

                setAdminSettingsFlash("Unable to change password. Please try again.");

            }

        }

    } else {

        setAdminSettingsFlash("Invalid request. Please try again.");

    }

    redirectToAdminSettings();

}


/* ==========================================
   06. LOAD PAGE DATA
========================================== */

$currentUser = getAccountSettingsUser($pdo, $currentUserId);

if (!$currentUser) {

    logoutUser();

    header("Location: ../auth/login.php");

    exit;

}

$settingsFlash = pullAdminSettingsFlash();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title><?php echo xdSettingsEscape($page_title); ?></title>

    <?php require_once 'includes/head-branding.php'; ?>

    <link rel="stylesheet" href="../assets/css/01-reset.css">
    <link rel="stylesheet" href="../assets/css/02-variables.css">
    <link rel="stylesheet" href="../assets/css/03-base.css">
    <link rel="stylesheet" href="../assets/css/10-dashboard.css">

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

</head>

<body>

<div class="xd-dashboard">

    <?php require_once 'includes/sidebar.php'; ?>

    <main class="xd-dashboard-main">

        <?php require_once 'includes/header.php'; ?>

        <?php if ($settingsFlash["message"] !== "") { ?>

            <div class="xd-alert <?php echo xdSettingsEscape($settingsFlash["type"]); ?>">
                <?php echo xdSettingsEscape($settingsFlash["message"]); ?>
            </div>

        <?php } ?>

        <div class="xd-dashboard-grid">

            <!-- ==========================================
                 PROFILE SETTINGS
            =========================================== -->

            <section class="xd-dashboard-panel">

                <div class="xd-panel-header">

                    <div>
                        <h2>Profile Settings</h2>
                        <p>Update your account name and email address.</p>
                    </div>

                </div>

                <form method="POST" class="xd-form" autocomplete="on">

                    <input type="hidden"
                           name="csrf_token"
                           value="<?php echo xdSettingsEscape(getCsrfToken()); ?>">

                    <input type="hidden"
                           name="action"
                           value="update_profile">

                    <div class="xd-form-group">

                        <label for="full_name">Full Name</label>

                        <input type="text"
                               id="full_name"
                               name="full_name"
                               value="<?php echo xdSettingsEscape($currentUser["full_name"]); ?>"
                               maxlength="100"
                               autocomplete="name"
                               required>

                    </div>

                    <div class="xd-form-group">

                        <label for="email">Email Address</label>

                        <input type="email"
                               id="email"
                               name="email"
                               value="<?php echo xdSettingsEscape($currentUser["email"]); ?>"
                               maxlength="150"
                               autocomplete="email"
                               required>

                    </div>

                    <div class="xd-form-group">

                        <label for="profile_current_password">Current Password</label>

                        <input type="password"
                               id="profile_current_password"
                               name="profile_current_password"
                               autocomplete="current-password"
                               required>

                        <small>Required to confirm profile and email changes.</small>

                    </div>

                    <div class="xd-form-actions">

                        <button type="submit" class="xd-btn-submit">
                            <i class="fa-solid fa-user-check"></i>
                            Save Profile
                        </button>

                    </div>

                </form>

            </section>


            <!-- ==========================================
                 CHANGE PASSWORD
            =========================================== -->

            <section class="xd-dashboard-panel">

                <div class="xd-panel-header">

                    <div>
                        <h2>Change Password</h2>
                        <p>Choose a new password for your account.</p>
                    </div>

                </div>

                <form method="POST" class="xd-form" autocomplete="on">

                    <input type="hidden"
                           name="csrf_token"
                           value="<?php echo xdSettingsEscape(getCsrfToken()); ?>">

                    <input type="hidden"
                           name="action"
                           value="change_password">

                    <div class="xd-form-group">

                        <label for="current_password">Current Password</label>

                        <input type="password"
                               id="current_password"
                               name="current_password"
                               autocomplete="current-password"
                               required>

                    </div>

                    <div class="xd-form-group">

                        <label for="new_password">New Password</label>

                        <input type="password"
                               id="new_password"
                               name="new_password"
                               minlength="<?php echo (int) $minimumPasswordLength; ?>"
                               maxlength="128"
                               autocomplete="new-password"
                               required>

                        <small>
                            Use <?php echo (int) $minimumPasswordLength; ?>–128 characters.
                        </small>

                    </div>

                    <div class="xd-form-group">

                        <label for="confirm_password">Confirm New Password</label>

                        <input type="password"
                               id="confirm_password"
                               name="confirm_password"
                               minlength="<?php echo (int) $minimumPasswordLength; ?>"
                               maxlength="128"
                               autocomplete="new-password"
                               required>

                    </div>

                    <div class="xd-form-actions">

                        <button type="submit" class="xd-btn-submit">
                            <i class="fa-solid fa-key"></i>
                            Change Password
                        </button>

                    </div>

                </form>

            </section>

        </div>

    </main>

</div>

<script src="../assets/js/03-dashboard.js"></script>

</body>

</html>
