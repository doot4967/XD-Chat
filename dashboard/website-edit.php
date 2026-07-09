<?php
/*
==================================================
Project : XD Chat
Version : 2.0.0
File    : website-edit.php
Module  : Edit Website
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

require_once '../includes/functions/validation.php';

require_once '../includes/functions/website.php';

requireLogin();


/* ==========================================
   02. PAGE CONFIGURATION
========================================== */

$page_title = "Edit Website | XD Chat";

$page_heading = "Edit Website";

$page_description = "Update your connected website details.";

$message = "";


/* ==========================================
   03. GET WEBSITE ID
========================================== */

if (!isset($_GET["id"])) {

    header("Location: websites.php");

    exit;

}

$website_id = (int) $_GET["id"];


/* ==========================================
   04. GET WEBSITE DATA
========================================== */

$website = getWebsite(
    $pdo,
    $website_id,
    $_SESSION["user_id"]
);

if (!$website) {

    header("Location: websites.php");

    exit;

}


/* ==========================================
   05. HANDLE FORM SUBMIT
========================================== */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $csrf_token = $_POST["csrf_token"] ?? "";

    if (!verifyCsrfToken($csrf_token)) {

        $message = "Invalid request. Please try again.";

    } else {

        $website_name = clean($_POST["website_name"]);

        $domain = clean($_POST["domain"]);

        $status = clean($_POST["status"]);

        $updated = updateWebsite(
            $pdo,
            $website_id,
            $_SESSION["user_id"],
            $website_name,
            $domain,
            $status
        );

        if ($updated) {

            header("Location: websites.php?updated=1");

            exit;

        }

        $message = "Unable to update website.";

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

                <h2>Edit Website</h2>

                <a href="websites.php">Back</a>

            </div>

            <?php if (!empty($message)) { ?>

                <div class="xd-alert error">
                    <?php echo htmlspecialchars($message); ?>
                </div>

            <?php } ?>

            <form method="POST" class="xd-form">

                <input type="hidden"
                       name="csrf_token"
                       value="<?php echo htmlspecialchars(getCsrfToken()); ?>">

                <div class="xd-form-group">

                    <label>Website Name</label>

                    <input
                        type="text"
                        name="website_name"
                        value="<?php echo htmlspecialchars($website["website_name"]); ?>"
                        required
                    >

                </div>

                <div class="xd-form-group">

                    <label>Website URL</label>

                    <input
                        type="url"
                        name="domain"
                        value="<?php echo htmlspecialchars($website["domain"]); ?>"
                        required
                    >

                </div>

                <div class="xd-form-group">

                    <label>Status</label>

                    <select name="status">

                        <option value="active"
                            <?php if ($website["status"] === "active") { echo "selected"; } ?>>
                            Active
                        </option>

                        <option value="inactive"
                            <?php if ($website["status"] === "inactive") { echo "selected"; } ?>>
                            Inactive
                        </option>

                    </select>

                </div>

                <div class="xd-form-actions">

                    <a href="websites.php" class="xd-btn-cancel">
                        Cancel
                    </a>

                    <button type="submit" class="xd-btn-submit">
                        Update Website
                    </button>

                </div>

            </form>

        </section>

    </main>

</div>

</body>

</html>
