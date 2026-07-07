<?php
/*
==================================================
Project : XD Chat
Version : 2.0.0
File    : website-add.php
Module  : Add Website
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

$page_title = "Add Website | XD Chat";

$page_heading = "Add Website";

$page_description = "Connect a new website with XD Chat.";

$message = "";


/* ==========================================
   03. HANDLE FORM SUBMIT
========================================== */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $website_name = clean($_POST["website_name"]);

    $domain = clean($_POST["domain"]);

    $status = clean($_POST["status"]);

    $save = addWebsite(
        $pdo,
        $_SESSION["user_id"],
        $website_name,
        $domain,
        $status
    );

    if ($save) {

        header("Location: websites.php?added=1");

        exit;

    }

    $message = "Unable to save website.";

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

                <h2>Add Website</h2>

                <a href="websites.php">Back</a>

            </div>

            <?php if (!empty($message)) { ?>

                <p><?php echo $message; ?></p>

            <?php } ?>

            <form method="POST">

                <p>

                    <label>Website Name</label>

                    <br><br>

                    <input
                        type="text"
                        name="website_name"
                        required
                    >

                </p>

                <br>

                <p>

                    <label>Website URL</label>

                    <br><br>

                    <input
                        type="url"
                        name="domain"
                        placeholder="https://example.com"
                        required
                    >

                </p>

                <br>

                <p>

                    <label>Status</label>

                    <br><br>

                    <select name="status">

                        <option value="active">
                            Active
                        </option>

                        <option value="inactive">
                            Inactive
                        </option>

                    </select>

                </p>

                <br><br>

                <button type="submit">
                    Save Website
                </button>

                <a href="websites.php">
                    Cancel
                </a>

            </form>

        </section>

    </main>

</div>

</body>

</html>