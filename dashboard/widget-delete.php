<?php
/*
==================================================
Project : XD Chat
Version : 2.0.0
File    : widget-delete.php
Module  : Delete Widget
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
   03. DELETE WIDGET
========================================== */

deleteWidget(
    $pdo,
    $widget_id,
    $_SESSION["user_id"]
);


/* ==========================================
   04. REDIRECT WITH SUCCESS MESSAGE
========================================== */

header("Location: widgets.php?deleted=1");

exit;