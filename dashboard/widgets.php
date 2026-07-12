<?php
/*
==================================================
Project : XD Chat
Version : 2.0.0
File    : widgets.php
Module  : Widget Management
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

require_once '../includes/functions/platform-settings.php';

require_once '../includes/functions/widget.php';

requireLogin();


/* ==========================================
   02. PAGE CONFIGURATION
========================================== */

$page_title = getPlatformPageTitle($pdo, "Widgets");

$page_heading = "Widgets";

$page_description = "Manage chat widgets connected with your websites.";

$widgets = getWidgets(
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
                    Widget added successfully.
                </div>

            <?php } ?>

            <?php if (isset($_GET["updated"])) { ?>

                <div class="xd-alert success">
                    Widget updated successfully.
                </div>

            <?php } ?>

            <?php if (isset($_GET["deleted"])) { ?>

                <div class="xd-alert success">
                    Widget deleted successfully.
                </div>

            <?php } ?>

            <!-- ==========================================
                 04. PANEL HEADER
            ========================================== -->
            <div class="xd-panel-header">

                <h2>All Widgets</h2>

                <a href="widget-add.php">Add Widget</a>

            </div>

            <!-- ==========================================
                 05. WIDGET TABLE
            ========================================== -->
            <?php if (count($widgets) > 0) { ?>

                <div class="xd-table-wrap">

                    <table class="xd-table">

                        <thead>

                            <tr>
                                <th>Widget</th>
                                <th>Website</th>
                                <th>Widget Key</th>
                                <th>Theme</th>
                                <th>Position</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Action</th>
                            </tr>

                        </thead>

                        <tbody>

                            <?php foreach ($widgets as $widget) { ?>

                                <tr>

                                    <td>
                                        <strong>
                                            <?php echo htmlspecialchars($widget["widget_name"]); ?>
                                        </strong>
                                    </td>

                                  <td>
    <div class="xd-website-info">

        <strong>
            <?php echo htmlspecialchars($widget["website_name"]); ?>
        </strong>

        <small>
            <?php echo htmlspecialchars($widget["domain"]); ?>
        </small>

    </div>
</td>

                                    <td>
                                        <code>
                                            <?php echo htmlspecialchars($widget["widget_key"]); ?>
                                        </code>
                                    </td>

                                    <td>
                                        <?php echo htmlspecialchars(ucfirst($widget["theme"])); ?>
                                    </td>

                                    <td>
                                        <?php echo htmlspecialchars(ucwords(str_replace("-", " ", $widget["position"]))); ?>
                                    </td>

                                    <td>
                                       <?php

$statusClass = ($widget["status"] == "active")
    ? "success"
    : "danger";

?>

<span class="xd-badge <?php echo $statusClass; ?>">

    <?php echo ucfirst($widget["status"]); ?>

</span>
                                    </td>

                                  <td>

    <?php

    $createdDate = strtotime($widget["created_at"]);

    ?>

    <strong>

        <?php echo date("d M Y", $createdDate); ?>

    </strong>

    <br>

    <small>

        <?php echo date("h:i A", $createdDate); ?>

    </small>

</td>

                                    <!-- ==========================================
                                         06. ACTION BUTTONS
                                    ========================================== -->
                                    <td>

                                        <div class="xd-action-buttons">

                                            <a href="widget-edit.php?id=<?php echo (int) $widget["id"]; ?>"
                                               class="xd-btn-edit"
                                               title="Edit Widget">

                                                <i class="fas fa-edit"></i>

                                                Edit

                                            </a>

                                            <a href="#"
                                               class="xd-btn-delete xd-delete-trigger"
                                               data-id="<?php echo (int) $widget["id"]; ?>"
                                               data-name="<?php echo htmlspecialchars($widget["widget_name"]); ?>"
                                               title="Delete Widget">

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
                <p>No widgets added yet.</p>

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

        <h3>Delete Widget?</h3>

        <p>
            Are you sure you want to delete
            <strong id="xdDeleteName">this widget</strong>?
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
      action="widget-delete.php"
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
