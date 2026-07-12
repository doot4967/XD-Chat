<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

/*
==================================================
Project : XD Chat
Version : 2.0.0
File    : login.php
Module  : User Login
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

$page_title = getPlatformPageTitle($pdo, "Login");

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
   03. HANDLE LOGIN REQUEST
========================================== */

$message = "";

if (isset($_GET["inactive"]) && $_GET["inactive"] === "1") {

    $message = "Your account is inactive. Please contact support.";

}

$request_method = $_SERVER["REQUEST_METHOD"] ?? "GET";

if ($request_method === "POST") {

    $csrf_token = $_POST["csrf_token"] ?? "";

    if (!verifyCsrfToken($csrf_token)) {

        $message = "Invalid request. Please try again.";

    } else {

        $email = clean($_POST["email"]);

        $password = clean($_POST["password"]);

        $result = authenticateUser(
            $pdo,
            $email,
            $password
        );

        if ($result["status"] === true) {

            loginUser($result["user"]);

            if ($result["user"]["role"] === "super_admin") {

                header("Location: ../super-admin/index.php");

                exit;

            }

            header("Location: ../dashboard/index.php");

            exit;

        }

        $message = $result["message"];

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

                <h1>Welcome back to <?php echo htmlspecialchars($platform_name, ENT_QUOTES, "UTF-8"); ?></h1>

                <p>
                    Manage conversations, websites, and widgets from one clean dashboard.
                </p>

            </div>


            <!-- ==========================================
                 06. LOGIN FORM
            ========================================== -->

            <div class="xd-auth-content">

                <div class="xd-auth-heading">

                    <h2>Login</h2>

                    <p>Enter your account details to continue.</p>

                </div>

                <?php if (!empty($message)) { ?>

                    <div class="xd-auth-alert">
                        <?php echo htmlspecialchars($message); ?>
                    </div>

                <?php } ?>

                <form method="POST" class="xd-auth-form">

                    <input type="hidden"
                           name="csrf_token"
                           value="<?php echo htmlspecialchars(getCsrfToken()); ?>">

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

                        <div class="xd-auth-label-row">

                            <label>Password</label>

                            <a href="forgot-password.php">
                                Forgot password?
                            </a>

                        </div>

                        <input
                            type="password"
                            name="password"
                            placeholder="Enter your password"
                            required
                        >

                    </div>

                    <button type="submit" class="xd-auth-submit">
                        Login
                    </button>

                </form>

                <p class="xd-auth-footer-text">
                    New to <?php echo htmlspecialchars($platform_name, ENT_QUOTES, "UTF-8"); ?>?
                    <a href="register.php">Create account</a>
                </p>

            </div>

        </section>

    </main>

</body>

</html>
