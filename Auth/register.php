<?php
/*
==================================================
Project : XD Chat
Version : 2.0.0
File    : register.php
Module  : User Registration
Status  : Development
Author  : Umesh + ChatGPT
Created : 05 July 2026
==================================================
*/


/* ==========================================
   01. LOAD DEPENDENCIES
========================================== */

require_once '../database/connection.php';

require_once '../includes/functions/auth.php';

require_once '../includes/functions/validation.php';

require_once '../includes/functions/session.php';

require_once '../includes/functions/platform-settings.php';


/* ==========================================
   02. LOAD BRANDING
========================================== */

$platform_name = getPlatformName($pdo);

$platform_tagline = getPlatformTagline($pdo);

$platform_logo_url = getPlatformLogoUrl($pdo);

$platform_favicon_url = getPlatformFaviconUrl($pdo);

$page_title = getPlatformPageTitle($pdo, "Register");

$auth_initials = (function (string $name): string {

    $name = trim($name);

    if ($name === "") {
        return "XD";
    }

    $parts = preg_split('/\s+/u', $name, -1, PREG_SPLIT_NO_EMPTY);

    if (count($parts) >= 2) {
        return mb_strtoupper(
            mb_substr($parts[0], 0, 1, "UTF-8") . mb_substr($parts[1], 0, 1, "UTF-8"),
            "UTF-8"
        );
    }

    $initials = mb_substr($name, 0, 2, "UTF-8");

    return $initials !== ""
        ? mb_strtoupper($initials, "UTF-8")
        : "XD";

})($platform_name);


/* ==========================================
   03. HANDLE REGISTER REQUEST
========================================== */

$message = "";

$registration_allowed = isPlatformRegistrationAllowed($pdo);

$minimum_password_length = getPlatformMinimumPasswordLength($pdo);

$default_user_role = getPlatformDefaultUserRole($pdo);

$default_user_status = getPlatformDefaultUserStatus($pdo);

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $csrf_token = $_POST["csrf_token"] ?? "";

    if (!verifyCsrfToken($csrf_token)) {

        $message = "Invalid request. Please try again.";

    } elseif (!$registration_allowed) {

        $message = "New account registration is currently disabled.";

    } else {

        $full_name = clean($_POST["full_name"]);

        $email = clean($_POST["email"]);

        $password = clean($_POST["password"]);

        $message = registerUser(
            $pdo,
            $full_name,
            $email,
            $password,
            [
                "role" => $default_user_role,
                "status" => $default_user_status,
                "minimum_password_length" => $minimum_password_length
            ]
        );

    }

}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title><?php echo htmlspecialchars($page_title, ENT_QUOTES, "UTF-8"); ?></title>

    <?php if ($platform_favicon_url !== "") { ?>
        <link rel="icon"
              href="<?php echo htmlspecialchars($platform_favicon_url, ENT_QUOTES, "UTF-8"); ?>">
    <?php } ?>

    <link rel="stylesheet"
          href="../assets/css/09-auth.css">

</head>

<body class="xd-auth-page">

    <!-- ==========================================
         04. AUTH WRAPPER
    ========================================== -->

    <main class="xd-auth-wrapper">

        <section class="xd-auth-card">

            <!-- ==========================================
                 05. BRAND PANEL
            ========================================== -->

            <div class="xd-auth-brand">

                <div class="xd-auth-logo">
                    <?php if ($platform_logo_url !== "") { ?>
                        <img src="<?php echo htmlspecialchars($platform_logo_url, ENT_QUOTES, "UTF-8"); ?>"
                             alt="<?php echo htmlspecialchars($platform_name, ENT_QUOTES, "UTF-8"); ?>">
                    <?php } else { ?>
                        <?php echo htmlspecialchars($auth_initials, ENT_QUOTES, "UTF-8"); ?>
                    <?php } ?>
                </div>

                <span><?php echo htmlspecialchars($platform_tagline, ENT_QUOTES, "UTF-8"); ?></span>

                <h1>Create your <?php echo htmlspecialchars($platform_name, ENT_QUOTES, "UTF-8"); ?> account</h1>

                <p>
                    Add websites, install widgets, and start replying to visitors from your dashboard.
                </p>

            </div>


            <!-- ==========================================
                 06. REGISTER FORM
            ========================================== -->

            <div class="xd-auth-content">

                <div class="xd-auth-heading">

                    <h2>Create Account</h2>

                    <p>Enter your details to create your workspace.</p>

                </div>

                <?php if (!empty($message)) { ?>

                    <div class="xd-auth-alert <?php if (strpos($message, "Account created successfully.") === 0) { echo "success"; } ?>">
                        <?php echo htmlspecialchars($message); ?>
                    </div>

                <?php } ?>

                <?php if (!$registration_allowed) { ?>

                    <div class="xd-auth-alert">
                        New account registration is currently disabled.
                    </div>

                <?php } else { ?>

                    <form method="POST" class="xd-auth-form">

                        <input type="hidden"
                               name="csrf_token"
                               value="<?php echo htmlspecialchars(getCsrfToken()); ?>">

                        <div class="xd-auth-field">

                            <label>Full Name</label>

                            <input
                                type="text"
                                name="full_name"
                                placeholder="Your full name"
                                required
                            >

                        </div>

                        <div class="xd-auth-field">

                            <label>Email Address</label>

                            <input
                                type="email"
                                name="email"
                                placeholder="you@example.com"
                                required
                            >

                        </div>

                        <div class="xd-auth-field">

                            <label>Password</label>

                            <input
                                type="password"
                                name="password"
                                placeholder="Create a secure password"
                                minlength="<?php echo (int) $minimum_password_length; ?>"
                                required
                            >

                            <small>
                                Minimum <?php echo (int) $minimum_password_length; ?> characters.
                            </small>

                        </div>

                        <button type="submit" class="xd-auth-submit">
                            Create Account
                        </button>

                    </form>

                <?php } ?>

                <p class="xd-auth-footer-text">
                    Already have an account?
                    <a href="login.php">Login</a>
                </p>

            </div>

        </section>

    </main>

</body>

</html>
