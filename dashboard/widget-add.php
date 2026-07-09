<?php
/*
==================================================
Project : XD Chat
Version : 2.0.0
File    : widget-add.php
Module  : Add Widget
Status  : Development
Author  : Umesh + ChatGPT
Created : 06 July 2026
==================================================
*/


/* ==========================================
   01. LOAD DEPENDENCIES
========================================== */

require_once '../database/connection.php';

require_once '../includes/functions/session.php';

require_once '../includes/functions/widget.php';

require_once '../includes/functions/website.php';

requireLogin();


/* ==========================================
   02. PAGE CONFIGURATION
========================================== */

$page_title = "Add Widget | XD Chat";

$page_heading = "Add Widget";

$page_description = "Create a new chat widget for your website.";

$error = "";

$websites = getWebsitesWithoutWidget(
    $pdo,
    $_SESSION["user_id"]
);


/* ==========================================
   03. HANDLE FORM SUBMIT
========================================== */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $csrf_token = $_POST["csrf_token"] ?? "";

    if (!verifyCsrfToken($csrf_token)) {

        $error = "Invalid request. Please try again.";

    } else {

        $website_id = (int) $_POST["website_id"];

        $widget_name = trim($_POST["widget_name"]);

        $theme = trim($_POST["theme"]);

        $position = trim($_POST["position"]);

        $widget_color = trim($_POST["widget_color"]);

        $widget_icon = "chat";

        $welcome_message = trim($_POST["welcome_message"]);

        $offline_message = trim($_POST["offline_message"]);

        $status = trim($_POST["status"]);


        if (
            empty($website_id) ||
            empty($widget_name) ||
            empty($theme) ||
            empty($position) ||
            empty($widget_color) ||
            empty($status)
        ) {

            $error = "Please fill all required fields.";

        } elseif (
            widgetExistsForWebsite(
                $pdo,
                $_SESSION["user_id"],
                $website_id
            )
        ) {

            $error = "This website already has a widget.";

        } else {

            createWidget(
                $pdo,
                $_SESSION["user_id"],
                $website_id,
                $widget_name,
                $theme,
                $position,
                $widget_color,
                $widget_icon,
                $welcome_message,
                $offline_message,
                $status
            );

            header("Location: widgets.php?added=1");

            exit;

        }

    }

}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title><?php echo $page_title; ?></title>

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

        <section class="xd-dashboard-panel">

            <!-- ==========================================
                 04. PANEL HEADER
            ========================================== -->
            <div class="xd-panel-header">

                <h2>Add New Widget</h2>

                <a href="widgets.php">Back</a>

            </div>


            <!-- ==========================================
                 05. ERROR MESSAGE
            ========================================== -->
            <?php if (!empty($error)) { ?>

                <div class="xd-alert error">
                    <?php echo htmlspecialchars($error); ?>
                </div>

            <?php } ?>


            <!-- ==========================================
                 06. AVAILABLE WEBSITE CHECK
            ========================================== -->
            <?php if (count($websites) === 0) { ?>

                <div class="xd-alert warning">
                    Please add a website first or delete an existing widget before creating a new one.
                </div>

                <a href="website-add.php" class="xd-btn-edit">
                    Add Website
                </a>

            <?php } else { ?>


                <!-- ==========================================
                     07. ADD WIDGET FORM
                ========================================== -->
                <form method="POST" class="xd-form">

                    <input type="hidden"
                           name="csrf_token"
                           value="<?php echo htmlspecialchars(getCsrfToken()); ?>">

                    <div class="xd-form-group">

                        <label>Website *</label>

                        <select name="website_id" required>

                            <option value="">Select Website</option>

                            <?php foreach ($websites as $website) { ?>

                                <option value="<?php echo (int) $website["id"]; ?>">

                                    <?php
                                        echo htmlspecialchars($website["website_name"]);
                                        echo " - ";
                                        echo htmlspecialchars($website["domain"]);
                                    ?>

                                </option>

                            <?php } ?>

                        </select>

                    </div>


                    <div class="xd-form-group">

                        <label>Widget Name *</label>

                        <input type="text"
                               name="widget_name"
                               placeholder="Customer Support"
                               required>

                    </div>


                    <div class="xd-form-group">

                        <label>Theme *</label>

                        <select name="theme" required>

                            <option value="light">Light</option>

                            <option value="dark">Dark</option>

                        </select>

                    </div>


                    <div class="xd-form-group">

                        <label>Position *</label>

                        <select name="position" required>

                            <option value="bottom-right">Bottom Right</option>

                            <option value="bottom-left">Bottom Left</option>

                        </select>

                    </div>


                    <div class="xd-form-group">

                        <label>Widget Color *</label>

                        <input type="color"
                               name="widget_color"
                               value="#2563eb"
                               required>

                    </div>


                    <div class="xd-form-group">

                        <label>Welcome Message</label>

                        <textarea name="welcome_message"
                                  rows="4"
                                  placeholder="Hi there! How can we help you today?"></textarea>

                    </div>


                    <div class="xd-form-group">

                        <label>Offline Message</label>

                        <textarea name="offline_message"
                                  rows="4"
                                  placeholder="We are currently offline. Please leave a message."></textarea>

                    </div>


                    <div class="xd-form-group">

                        <label>Status *</label>

                        <select name="status" required>

                            <option value="active">Active</option>

                            <option value="inactive">Inactive</option>

                        </select>

                    </div>


                    <div class="xd-form-actions">

                        <a href="widgets.php" class="xd-btn-cancel">
                            Cancel
                        </a>

                        <button type="submit" class="xd-btn-submit">
                            Save Widget
                        </button>

                    </div>

                </form>

            <?php } ?>

        </section>

    </main>

</div>

<script src="../assets/js/03-dashboard.js"></script>

</body>

</html>
