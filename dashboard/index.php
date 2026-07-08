<?php
/*
==================================================
Project : XD Chat
Version : 2.0.0
File    : index.php
Module  : Dashboard
Status  : Development
Author  : Umesh + ChatGPT
Created : 05 July 2026
==================================================
*/


/* ==========================================
   01. LOAD DEPENDENCIES
========================================== */

require_once '../includes/functions/session.php';

require_once '../database/connection.php';

require_once '../includes/functions/analytics.php';

requireLogin();


/* ==========================================
   02. PAGE CONFIGURATION
========================================== */

$page_title = "Dashboard | XD Chat";

$page_heading = "Dashboard";

$page_description = "Welcome back, " . $_SESSION["user_name"] . " 👋";

$dashboardStats = getDashboardStats(
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



        <!-- ==========================================
             DASHBOARD CARDS
        =========================================== -->

        <section class="xd-dashboard-cards">

            <div class="xd-dashboard-card">

                <div class="xd-card-icon blue">
                    <i class="fa-solid fa-globe"></i>
                </div>

                <span>Total Websites</span>

                <strong><?php echo $dashboardStats["websites"]; ?></strong>

                <small class="positive">

                    <i class="fa-solid fa-arrow-trend-up"></i>

                    +12% this month

                </small>

            </div>



            <div class="xd-dashboard-card">

                <div class="xd-card-icon green">
                    <i class="fa-regular fa-comments"></i>
                </div>

                <span>Live Chats</span>

                <strong><?php echo $dashboardStats["chats"]; ?></strong>

                <small class="positive">

                    <i class="fa-solid fa-arrow-trend-up"></i>

                    +8% today

                </small>

            </div>



            <div class="xd-dashboard-card">

                <div class="xd-card-icon purple">
                    <i class="fa-solid fa-users"></i>
                </div>

                <span>Total Widgets</span>

                <strong><?php echo $dashboardStats["widgets"]; ?></strong>

                <small class="positive">

                    <i class="fa-solid fa-arrow-trend-up"></i>

                    +18% today

                </small>

            </div>



            <div class="xd-dashboard-card">

                <div class="xd-card-icon orange">
                    <i class="fa-regular fa-envelope"></i>
                </div>

                <span>Messages</span>

                <strong><?php echo $dashboardStats["messages"]; ?></strong>

                <small class="positive">

                    <i class="fa-solid fa-arrow-trend-up"></i>

                    +22% this week

                </small>

            </div>

        </section>



        <!-- ==========================================
             DASHBOARD PANELS
        =========================================== -->

        <section class="xd-dashboard-grid">

            <div class="xd-dashboard-panel">

                <div class="xd-panel-header">

                    <h2>Recent Chats</h2>

                    <a href="chats.php">View All</a>

                </div>

                <div class="xd-chat-list">

                    <div class="xd-chat-row">

                        <div class="xd-chat-avatar green">R</div>

                        <div class="xd-chat-info">

                            <strong>Rohit Sharma</strong>

                            <span>Hello! I need help with pricing.</span>

                        </div>

                        <div class="xd-chat-meta">

                            <small>2m ago</small>

                            <span class="xd-badge success">

                                Active

                            </span>

                        </div>

                    </div>

                </div>

            </div>



            <div class="xd-dashboard-panel">

                <div class="xd-panel-header">

                    <h2>Recent Visitors</h2>

                    <a href="visitors.php">View All</a>

                </div>

                <div class="xd-visitor-list">

                    <div class="xd-visitor-row">

                        <span>🇮🇳</span>

                        <div>

                            <strong>Mumbai, India</strong>

                            <small>example.com</small>

                        </div>

                        <em></em>

                    </div>

                </div>

            </div>

        </section>

    </main>

</div>

</body>

</html>
