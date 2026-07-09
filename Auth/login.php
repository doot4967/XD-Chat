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


/* ==========================================
   02. HANDLE LOGIN REQUEST
========================================== */

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

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

    <title>Login | XD Chat</title>

    <link rel="stylesheet"
          href="../assets/css/09-auth.css">

</head>

<body class="xd-auth-page">

    <!-- ==========================================
         03. AUTH WRAPPER
    ========================================== -->

    <main class="xd-auth-wrapper">

        <section class="xd-auth-card">

            <!-- ==========================================
                 04. BRAND PANEL
            ========================================== -->

            <div class="xd-auth-brand">

                <div class="xd-auth-logo">
                    XD
                </div>

                <span>Live Chat Platform</span>

                <h1>Welcome back to XD Chat</h1>

                <p>
                    Manage conversations, websites, and widgets from one clean dashboard.
                </p>

            </div>


            <!-- ==========================================
                 05. LOGIN FORM
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
                    New to XD Chat?
                    <a href="register.php">Create account</a>
                </p>

            </div>

        </section>

    </main>

</body>

</html>
