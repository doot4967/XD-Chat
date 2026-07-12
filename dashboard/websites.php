<?php
/*
==================================================
Project : XD Chat
Version : 2.0.0
File    : websites.php
Module  : Website Management
Status  : Development
Author  : Umesh + ChatGPT
Created : 05 July 2026
==================================================
*/


/* ==========================================
   01. LOAD DEPENDENCIES
========================================== */

require_once '../database/connection.php';

require_once '../includes/functions/session.php';

require_once '../includes/functions/platform-settings.php';

require_once '../includes/functions/website.php';

requireLogin();


/* ==========================================
   02. PAGE CONFIGURATION
========================================== */

$page_title = getPlatformPageTitle($pdo, "Websites");

$page_heading = "Websites";

$page_description = "Manage websites connected with XD Chat.";

$websites = getWebsites(
    $pdo,
    $_SESSION["user_id"]
);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title><?php echo htmlspecialchars($page_title); ?></title>

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

        <section class="xd-dashboard-panel">

            <!-- ==========================================
                 03. SUCCESS ALERTS
            ========================================== -->
            <?php if (isset($_GET["added"])) { ?>

                <div class="xd-alert success">
                    Website added successfully.
                </div>

            <?php } ?>

            <?php if (isset($_GET["updated"])) { ?>

                <div class="xd-alert success">
                    Website updated successfully.
                </div>

            <?php } ?>

            <?php if (isset($_GET["deleted"])) { ?>

                <div class="xd-alert success">
                    Website deleted successfully.
                </div>

            <?php } ?>

            <!-- ==========================================
                 04. PANEL HEADER
            ========================================== -->
            <div class="xd-panel-header">

                <h2>Connected Websites</h2>

                <a href="website-add.php">Add New</a>

            </div>

            <!-- ==========================================
                 05. WEBSITE TABLE
            ========================================== -->
            <?php if (count($websites) > 0) { ?>

                <div class="xd-table-wrap">

                    <table class="xd-table">

                        <thead>

                            <tr>
                                <th>Website</th>
                                <th>Domain</th>
                                <th>Website Key</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Action</th>
                            </tr>

                        </thead>

                        <tbody>

                            <?php foreach ($websites as $website) { ?>

                                <tr>

                                    <td>
                                        <strong>
                                            <?php echo htmlspecialchars($website["website_name"]); ?>
                                        </strong>
                                    </td>

                                    <td>
                                        <?php echo htmlspecialchars($website["domain"]); ?>
                                    </td>

                                    <td>
                                        <code>
                                            <?php echo htmlspecialchars($website["widget_key"]); ?>
                                        </code>
                                    </td>

                                    <td>
                                        <span class="xd-badge success">
                                            <?php echo htmlspecialchars(ucfirst($website["status"])); ?>
                                        </span>
                                    </td>

                                    <td>
                                        <?php echo htmlspecialchars($website["created_at"]); ?>
                                    </td>

                                    <!-- ==========================================
                                         06. ACTION BUTTONS
                                    ========================================== -->
                                    <td>

                                        <div class="xd-action-buttons">

                                            <a href="website-edit.php?id=<?php echo (int) $website["id"]; ?>"
                                               class="xd-btn-edit"
                                               title="Edit Website">

                                                <i class="fas fa-edit"></i>

                                                Edit

                                            </a>

                                            <a href="#"
                                               class="xd-btn-delete xd-delete-trigger"
                                               data-id="<?php echo (int) $website["id"]; ?>"
                                               data-name="<?php echo htmlspecialchars($website["website_name"]); ?>"
                                               title="Delete Website">

                                                <i class="fas fa-trash"></i>

                                                Delete

                                            </a>

                                        </div>

                                    </td>

                                </tr>

                            <?php } ?>

                        </tbody>

                    </table>

                </div>

            <?php } else { ?>

                <!-- ==========================================
                     07. EMPTY STATE
                ========================================== -->
                <p>No websites added yet.</p>

            <?php } ?>

        </section>

    </main>

</div>

<!-- ==========================================
     08. DELETE CONFIRMATION MODAL
========================================== -->
<div class="xd-modal-overlay" id="xdDeleteModal">

    <div class="xd-modal-box">

        <div class="xd-modal-icon">
            <i class="fas fa-triangle-exclamation"></i>
        </div>

        <h3>Delete Website?</h3>

        <p>
            Are you sure you want to delete
            <strong id="xdDeleteName">this website</strong>?
        </p>

        <div class="xd-modal-actions">

            <button type="button" class="xd-modal-cancel">
                Cancel
            </button>

            <a href="#" class="xd-modal-delete" id="xdDeleteConfirm">
                Delete
            </a>

        </div>

    </div>

</div>

<form method="POST"
      action="website-delete.php"
      id="xdDeleteForm"
      style="display:none;">

    <input type="hidden"
           name="id"
           id="xdDeleteId">

    <input type="hidden"
           name="csrf_token"
           value="<?php echo htmlspecialchars(getCsrfToken()); ?>">

</form>

<!-- ==========================================
     09. DASHBOARD JAVASCRIPT
========================================== -->
<script src="../assets/js/03-dashboard.js"></script>

</body>

</html>
