<?php
/*
==================================================
Project : XD Chat
Version : 2.0.0
File    : logout.php
Module  : User Logout
Status  : Development
Author  : Umesh + ChatGPT
Created : 05 July 2026
==================================================
*/

require_once '../includes/functions/session.php';

logoutUser();

header("Location: login.php");

exit;