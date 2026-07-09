<?php
/*
==================================================
Project : XD Chat
Version : 2.0.0
File    : chats.php
Module  : Live Chat Dashboard
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

requireLogin();


/* ==========================================
   02. PAGE CONFIGURATION
========================================== */

$page_title = "Live Chats | XD Chat";

$page_heading = "Live Chats";

$page_description = "Manage visitor conversations in real time.";

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

    <link rel="stylesheet" href="/XD-Chat/dashboard/chat/assets/css/chat.css?v=<?php echo time(); ?>">

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

</head>

<body>

<div class="xd-dashboard">

    <?php require_once 'includes/sidebar.php'; ?>

    <main class="xd-dashboard-main">

        <?php require_once 'includes/header.php'; ?>

        <section class="xd-dashboard-panel">

            <div class="xd-chat-admin">

                <?php require_once 'chat/includes/window.php'; ?>

            </div>

        </section>

    </main>

</div>

<script src="../assets/js/03-dashboard.js"></script>
<script>
    window.XD_CSRF_TOKEN = "<?php echo htmlspecialchars(getCsrfToken()); ?>";
</script>
<script src="/XD-Chat/dashboard/chat/assets/js/chat.js?v=<?php echo time(); ?>"></script>

</body>

</html>
