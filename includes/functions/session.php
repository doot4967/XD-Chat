<?php
/*
==================================================
Project : XD Chat
Version : 2.0.0
File    : session.php
Module  : Session Management
Status  : Development
Author  : Umesh + ChatGPT
Created : 05 July 2026
==================================================
*/


/* ==========================================
   01. START SESSION
========================================== */

if (session_status() === PHP_SESSION_NONE) {

    session_start();

}


/* ==========================================
   02. LOGIN USER
========================================== */

function loginUser(array $user): void
{

    $_SESSION["user_id"] = $user["id"];

    $_SESSION["user_name"] = $user["full_name"];

    $_SESSION["user_email"] = $user["email"];

    $_SESSION["user_role"] = $user["role"];

}


/* ==========================================
   03. CHECK LOGIN
========================================== */

function isLoggedIn(): bool
{

    return isset($_SESSION["user_id"]);

}


/* ==========================================
   04. LOGOUT USER
========================================== */

function logoutUser(): void
{

    $_SESSION = [];

    session_destroy();

}


/* ==========================================
   05. REQUIRE LOGIN
========================================== */

function requireLogin(): void
{

    if (!isLoggedIn()) {

        header("Location: ../auth/login.php");

        exit;

    }

}