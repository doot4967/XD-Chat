<?php
/*
==================================================
Project : XD Chat
Version : 2.0.0
File    : widget-edit.php
Module  : Edit Widget
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
   02. VALIDATE WIDGET ID
========================================== */

if (!isset($_GET["id"])) {

    header("Location: widgets.php");

    exit;

}

$widget_id = (int) $_GET["id"];


/* ==========================================
   03. PAGE CONFIGURATION
========================================== */

$page_title = "Edit Widget | XD Chat";

$page_heading = "Edit Widget";

$page_description = "Update your chat widget settings.";

$error = "";

$websites = getWebsites(
    $pdo,
    $_SESSION["user_id"]
);

$widget = getWidget(
    $pdo,
    $widget_id,
    $_SESSION["user_id"]
);

if (!$widget) {

    header("Location: widgets.php");

    exit;

}


/* ==========================================
   04. HANDLE FORM SUBMIT
========================================== */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

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

    } else {

        updateWidget(
            $pdo,
            $widget_id,
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

        header("Location: widgets.php?updated=1");

        exit;

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

            <div class="xd-panel-header">

                <h2>Edit Widget</h2>

                <a href="widgets.php">Back</a>

            </div>

            <?php if (!empty($error)) { ?>

                <div class="xd-alert error">
                    <?php echo htmlspecialchars($error); ?>
                </div>

            <?php } ?>

            <form method="POST" class="xd-form">

                <div class="xd-form-group">

                    <label>Website *</label>

                    <select name="website_id" required>

                        <option value="">Select Website</option>

                        <?php foreach ($websites as $website) { ?>

                            <option value="<?php echo (int) $website["id"]; ?>"
                                <?php if ((int) $widget["website_id"] === (int) $website["id"]) { echo "selected"; } ?>>

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
                           value="<?php echo htmlspecialchars($widget["widget_name"]); ?>"
                           required>

                </div>

                <div class="xd-form-group">

                    <label>Theme *</label>

                    <select name="theme" required>

                        <option value="light" <?php if ($widget["theme"] === "light") { echo "selected"; } ?>>
                            Light
                        </option>

                        <option value="dark" <?php if ($widget["theme"] === "dark") { echo "selected"; } ?>>
                            Dark
                        </option>

                    </select>

                </div>

                <div class="xd-form-group">

                    <label>Position *</label>

                    <select name="position" required>

                        <option value="bottom-right" <?php if ($widget["position"] === "bottom-right") { echo "selected"; } ?>>
                            Bottom Right
                        </option>

                        <option value="bottom-left" <?php if ($widget["position"] === "bottom-left") { echo "selected"; } ?>>
                            Bottom Left
                        </option>

                    </select>

                </div>

                <div class="xd-form-group">

                    <label>Widget Color *</label>

                    <input type="color"
                           name="widget_color"
                           value="<?php echo htmlspecialchars($widget["widget_color"]); ?>"
                           required>

                </div>

                <div class="xd-form-group">

                    <label>Welcome Message</label>

                    <textarea name="welcome_message"
                              rows="4"><?php echo htmlspecialchars($widget["welcome_message"]); ?></textarea>

                </div>

                <div class="xd-form-group">

                    <label>Offline Message</label>

                    <textarea name="offline_message"
                              rows="4"><?php echo htmlspecialchars($widget["offline_message"]); ?></textarea>

                </div>

                <div class="xd-form-group">

                    <label>Status *</label>

                    <select name="status" required>

                        <option value="active" <?php if ($widget["status"] === "active") { echo "selected"; } ?>>
                            Active
                        </option>

                        <option value="inactive" <?php if ($widget["status"] === "inactive") { echo "selected"; } ?>>
                            Inactive
                        </option>

                    </select>

                </div>

                <div class="xd-form-actions">

                    <a href="widgets.php" class="xd-btn-cancel">
                        Cancel
                    </a>

                    <button type="submit" class="xd-btn-submit">
                        Update Widget
                    </button>

                </div>

            </form>

        </section>

    </main>

</div>

<script src="../assets/js/03-dashboard.js"></script>

</body>

</html>