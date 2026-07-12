<?php
/*
==================================================
Project : XD Chat
Version : 2.0.0
File    : settings.php
Module  : Super Admin Settings
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

requireRole([
    "super_admin"
]);


/* ==========================================
   02. SETTINGS HELPERS
========================================== */

function getSuperAdminSettingsUser(PDO $pdo, int $userId): ?array
{

    $statement = $pdo->prepare(
        "SELECT
            id,
            full_name,
            email,
            password,
            role,
            status,
            created_at,
            updated_at
         FROM users
         WHERE id = :user_id
         LIMIT 1"
    );

    $statement->bindValue(":user_id", $userId, PDO::PARAM_INT);

    $statement->execute();

    $user = $statement->fetch(PDO::FETCH_ASSOC);

    return $user ?: null;

}


function getSuperAdminSettingsRedirect(string $message, string $type): string
{

    return "settings.php?"
        . http_build_query([
            "settings_message" => $message,
            "settings_type" => $type
        ]);

}


function logSuperAdminSettingsAction(PDO $pdo, string $action, string $description): void
{

    createAuditLog($pdo, [
        "actor_user_id" => (int) ($_SESSION["user_id"] ?? 0),
        "actor_name" => $_SESSION["user_name"] ?? "Super Admin",
        "action" => $action,
        "target_type" => "user",
        "target_id" => (int) ($_SESSION["user_id"] ?? 0),
        "target_name" => $_SESSION["user_name"] ?? "Super Admin",
        "description" => $description
    ]);

}


/* ==========================================
   03. HANDLE POST REQUESTS
========================================== */

$currentUserId = (int) ($_SESSION["user_id"] ?? 0);

$requestMethod = $_SERVER["REQUEST_METHOD"] ?? "GET";

if ($requestMethod === "POST") {

    $action = $_POST["action"] ?? "";

    $csrfToken = $_POST["csrf_token"] ?? "";

    $message = "Invalid request. Please try again.";

    $messageType = "error";

    if (!verifyCsrfToken($csrfToken)) {

        header("Location: " . getSuperAdminSettingsRedirect($message, $messageType));

        exit;

    }

    $currentUser = getSuperAdminSettingsUser($pdo, $currentUserId);

    if (!$currentUser) {

        header("Location: " . getSuperAdminSettingsRedirect("Account not found.", "error"));

        exit;

    }

    if ($action === "update_profile") {

        $fullName = trim($_POST["full_name"] ?? "");

        $email = trim($_POST["email"] ?? "");

        if ($fullName === "") {

            $message = "Full name is required.";

        } elseif ($email === "" || !filter_var($email, FILTER_VALIDATE_EMAIL)) {

            $message = "Please enter a valid email address.";

        } else {

            try {

                $checkStatement = $pdo->prepare(
                    "SELECT id
                     FROM users
                     WHERE email = :email
                     AND id != :user_id
                     LIMIT 1"
                );

                $checkStatement->bindValue(":email", $email);

                $checkStatement->bindValue(":user_id", $currentUserId, PDO::PARAM_INT);

                $checkStatement->execute();

                if ($checkStatement->fetchColumn()) {

                    $message = "Email address is already used by another account.";

                } else {

                    $updateStatement = $pdo->prepare(
                        "UPDATE users
                         SET full_name = :full_name,
                             email = :email
                         WHERE id = :user_id
                         LIMIT 1"
                    );

                    $updateStatement->bindValue(":full_name", $fullName);

                    $updateStatement->bindValue(":email", $email);

                    $updateStatement->bindValue(":user_id", $currentUserId, PDO::PARAM_INT);

                    $updateStatement->execute();

                    $_SESSION["user_name"] = $fullName;

                    $_SESSION["user_email"] = $email;

                    logSuperAdminSettingsAction(
                        $pdo,
                        "super_admin_profile_updated",
                        "Super Admin profile information was updated."
                    );

                    $message = "Profile updated successfully.";

                    $messageType = "success";

                }

            } catch (Throwable $exception) {

                $message = "Unable to update profile. Please try again.";

            }

        }

    } elseif ($action === "change_password") {

        $currentPassword = $_POST["current_password"] ?? "";

        $newPassword = $_POST["new_password"] ?? "";

        $confirmPassword = $_POST["confirm_password"] ?? "";

        if ($currentPassword === "" || $newPassword === "" || $confirmPassword === "") {

            $message = "All password fields are required.";

        } elseif (!password_verify($currentPassword, $currentUser["password"])) {

            $message = "Current password is incorrect.";

        } elseif (strlen($newPassword) < 8) {

            $message = "New password must be at least 8 characters.";

        } elseif ($newPassword !== $confirmPassword) {

            $message = "New password and confirm password do not match.";

        } else {

            try {

                $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);

                $updateStatement = $pdo->prepare(
                    "UPDATE users
                     SET password = :password
                     WHERE id = :user_id
                     LIMIT 1"
                );

                $updateStatement->bindValue(":password", $passwordHash);

                $updateStatement->bindValue(":user_id", $currentUserId, PDO::PARAM_INT);

                $updateStatement->execute();

                session_regenerate_id(true);

                logSuperAdminSettingsAction(
                    $pdo,
                    "super_admin_password_changed",
                    "Super Admin account password was changed."
                );

                $message = "Password changed successfully.";

                $messageType = "success";

            } catch (Throwable $exception) {

                $message = "Unable to change password. Please try again.";

            }

        }

    }

    header("Location: " . getSuperAdminSettingsRedirect($message, $messageType));

    exit;

}


/* ==========================================
   04. LOAD CURRENT USER
========================================== */

$currentUser = getSuperAdminSettingsUser($pdo, $currentUserId);

if (!$currentUser) {

    logoutUser();

    header("Location: ../auth/login.php");

    exit;

}


/* ==========================================
   05. PAGE CONFIGURATION
========================================== */

$page_title = getPlatformPageTitle($pdo, "Settings");

$page_heading = "Settings";

$page_description = "Manage your Super Admin profile and password.";

$active_menu = "account";

$settingsMessage = $_GET["settings_message"] ?? "";

$settingsType = $_GET["settings_type"] ?? "";

if (!in_array($settingsType, [
    "success",
    "error"
], true)) {

    $settingsType = "error";

}

require_once 'includes/header.php';
?>

<section class="xd-sa-settings-grid">

    <article class="xd-sa-settings-card">

        <div class="xd-sa-panel-header">
            <div>
                <h2>Profile Information</h2>
                <p>Update your account name and email address.</p>
            </div>
        </div>

        <?php if ($settingsMessage !== "") { ?>

            <div class="xd-sa-alert <?php echo htmlspecialchars($settingsType); ?>">
                <?php echo htmlspecialchars($settingsMessage); ?>
            </div>

        <?php } ?>

        <form method="POST" class="xd-sa-settings-form">

            <input type="hidden"
                   name="csrf_token"
                   value="<?php echo htmlspecialchars(getCsrfToken()); ?>">

            <input type="hidden"
                   name="action"
                   value="update_profile">

            <div class="xd-sa-settings-field">
                <label for="full_name">Full Name</label>
                <input type="text"
                       id="full_name"
                       name="full_name"
                       value="<?php echo htmlspecialchars($currentUser["full_name"]); ?>"
                       required>
            </div>

            <div class="xd-sa-settings-field">
                <label for="email">Email Address</label>
                <input type="email"
                       id="email"
                       name="email"
                       value="<?php echo htmlspecialchars($currentUser["email"]); ?>"
                       required>
            </div>

            <div class="xd-sa-settings-readonly-grid">
                <div>
                    <span>Role</span>
                    <strong>Super Admin</strong>
                </div>
                <div>
                    <span>Status</span>
                    <strong><?php echo htmlspecialchars(ucfirst($currentUser["status"])); ?></strong>
                </div>
                <div>
                    <span>Created</span>
                    <strong><?php echo htmlspecialchars(date("d M Y, h:i A", strtotime($currentUser["created_at"]))); ?></strong>
                </div>
            </div>

            <div class="xd-sa-settings-actions">
                <button type="submit">Save Profile</button>
            </div>

        </form>

    </article>


    <article class="xd-sa-settings-card">

        <div class="xd-sa-panel-header">
            <div>
                <h2>Change Password</h2>
                <p>Use a strong password with at least 8 characters.</p>
            </div>
        </div>

        <form method="POST" class="xd-sa-settings-form">

            <input type="hidden"
                   name="csrf_token"
                   value="<?php echo htmlspecialchars(getCsrfToken()); ?>">

            <input type="hidden"
                   name="action"
                   value="change_password">

            <div class="xd-sa-settings-field">
                <label for="current_password">Current Password</label>
                <input type="password"
                       id="current_password"
                       name="current_password"
                       autocomplete="current-password"
                       required>
            </div>

            <div class="xd-sa-settings-field">
                <label for="new_password">New Password</label>
                <input type="password"
                       id="new_password"
                       name="new_password"
                       minlength="8"
                       autocomplete="new-password"
                       required>
            </div>

            <div class="xd-sa-settings-field">
                <label for="confirm_password">Confirm New Password</label>
                <input type="password"
                       id="confirm_password"
                       name="confirm_password"
                       minlength="8"
                       autocomplete="new-password"
                       required>
            </div>

            <div class="xd-sa-settings-actions">
                <button type="submit">Change Password</button>
            </div>

        </form>

    </article>


    <article class="xd-sa-settings-card xd-sa-settings-summary">

        <div class="xd-sa-panel-header">
            <div>
                <h2>Account Security Summary</h2>
                <p>Current account protection status.</p>
            </div>
        </div>

        <div class="xd-sa-settings-summary-list">
            <div>
                <i class="fa-solid fa-shield-halved"></i>
                <div>
                    <strong>CSRF Protection</strong>
                    <span>Enabled for settings forms.</span>
                </div>
            </div>
            <div>
                <i class="fa-solid fa-key"></i>
                <div>
                    <strong>Password Hashing</strong>
                    <span>Protected with PHP password_hash().</span>
                </div>
            </div>
            <div>
                <i class="fa-solid fa-clock"></i>
                <div>
                    <strong>Session Timeout</strong>
                    <span>2-hour idle timeout active.</span>
                </div>
            </div>
            <div>
                <i class="fa-solid fa-clipboard-list"></i>
                <div>
                    <strong>Audit Logging</strong>
                    <span>Profile and password changes are logged safely.</span>
                </div>
            </div>
        </div>

    </article>

</section>

<?php require_once 'includes/footer.php'; ?>
