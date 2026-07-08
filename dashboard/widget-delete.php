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
   02. VALIDATE REQUEST
========================================== */

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    header("Location: widgets.php");

    exit;

}

$csrf_token = isset($_POST["csrf_token"])
    ? $_POST["csrf_token"]
    : "";

if (!verifyCsrfToken($csrf_token)) {

    header("Location: widgets.php");

    exit;

}


/* ==========================================
   03. VALIDATE WIDGET ID
========================================== */

if (!isset($_POST["id"])) {

    header("Location: widgets.php");

    exit;

}

$widget_id = (int) $_POST["id"];

if ($widget_id <= 0) {

    header("Location: widgets.php");

    exit;

}


/* ==========================================
   04. DELETE WIDGET
========================================== */

deleteWidget(
    $pdo,
    $widget_id,
    $_SESSION["user_id"]
);


/* ==========================================
   05. REDIRECT WITH SUCCESS MESSAGE
========================================== */

header("Location: widgets.php?deleted=1");

exit;
