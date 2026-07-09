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


/* ==========================================
   02. HANDLE REGISTER REQUEST
========================================== */

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $csrf_token = $_POST["csrf_token"] ?? "";

    if (!verifyCsrfToken($csrf_token)) {

        $message = "Invalid request. Please try again.";

    } else {

        $full_name = clean($_POST["full_name"]);

        $email = clean($_POST["email"]);

        $password = clean($_POST["password"]);

        $message = registerUser(
            $pdo,
            $full_name,
            $email,
            $password
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

    <title>Create Account | XD Chat</title>

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

                <span>Start your live chat workspace</span>

                <h1>Create your XD Chat account</h1>

                <p>
                    Add websites, install widgets, and start replying to visitors from your dashboard.
                </p>

            </div>


            <!-- ==========================================
                 05. REGISTER FORM
            ========================================== -->

            <div class="xd-auth-content">

                <div class="xd-auth-heading">

                    <h2>Create Account</h2>

                    <p>Enter your details to create your workspace.</p>

                </div>

                <?php if (!empty($message)) { ?>

                    <div class="xd-auth-alert <?php if ($message === "Account created successfully.") { echo "success"; } ?>">
                        <?php echo htmlspecialchars($message); ?>
                    </div>

                <?php } ?>

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
                            required
                        >

                    </div>

                    <button type="submit" class="xd-auth-submit">
                        Create Account
                    </button>

                </form>

                <p class="xd-auth-footer-text">
                    Already have an account?
                    <a href="login.php">Login</a>
                </p>

            </div>

        </section>

    </main>

</body>

</html>
