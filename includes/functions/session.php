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
   01. SESSION CONFIGURATION
========================================== */

if (session_status() === PHP_SESSION_NONE) {

    $session_config_path = dirname(__DIR__, 2)
        . DIRECTORY_SEPARATOR
        . "config"
        . DIRECTORY_SEPARATOR
        . "app.php";

    $session_app_config = is_file($session_config_path)
        ? require $session_config_path
        : [];

    $session_environment = $session_app_config["environment"] ?? "local";

    $is_https_request = !empty($_SERVER["HTTPS"])
        && $_SERVER["HTTPS"] !== "off";

    $secure_cookie = $session_environment === "production"
        || $is_https_request;

    session_set_cookie_params([
        "lifetime" => 0,
        "path" => "/",
        "domain" => "",
        "secure" => $secure_cookie,
        "httponly" => true,
        "samesite" => "Lax"
    ]);

    session_start();

}


/* ==========================================
   02. SESSION TIMEOUT CONFIGURATION
========================================== */

define("XD_SESSION_TIMEOUT", 7200);


/* ==========================================
   03. LOGIN USER
========================================== */

function loginUser(array $user): void
{

    session_regenerate_id(true);

    $_SESSION["user_id"] = $user["id"];

    $_SESSION["user_name"] = $user["full_name"];

    $_SESSION["user_email"] = $user["email"];

    $_SESSION["user_role"] = $user["role"];

    $_SESSION["last_activity"] = time();

}


/* ==========================================
   04. CHECK LOGIN
========================================== */

function isLoggedIn(): bool
{

    return isset($_SESSION["user_id"]);

}


/* ==========================================
   05. CHECK SESSION TIMEOUT
========================================== */

function isSessionExpired(): bool
{

    if (!isLoggedIn()) {

        return false;

    }

    if (empty($_SESSION["last_activity"])) {

        $_SESSION["last_activity"] = time();

        return false;

    }

    return (time() - (int) $_SESSION["last_activity"]) > XD_SESSION_TIMEOUT;

}


/* ==========================================
   06. REFRESH SESSION ACTIVITY
========================================== */

function refreshSessionActivity(): void
{

    $_SESSION["last_activity"] = time();

}


/* ==========================================
   07. LOGOUT USER
========================================== */

function logoutUser(): void
{

    $_SESSION = [];

    if (ini_get("session.use_cookies")) {

        $params = session_get_cookie_params();

        setcookie(session_name(), "", [
            "expires" => time() - 42000,
            "path" => $params["path"],
            "domain" => $params["domain"],
            "secure" => $params["secure"],
            "httponly" => $params["httponly"],
            "samesite" => $params["samesite"] ?? "Lax"
        ]);

    }

    session_destroy();

}


/* ==========================================
   08. REQUIRE LOGIN
========================================== */

function requireLogin(): void
{

    if (!isLoggedIn()) {

        header("Location: ../auth/login.php");

        exit;

    }

    if (isSessionExpired()) {

        logoutUser();

        header("Location: ../auth/login.php?timeout=1");

        exit;

    }

    refreshSessionActivity();

}


/* ==========================================
   09. REQUIRE ROLE
========================================== */

function requireRole(array $roles): void
{

    requireLogin();

    $user_role = $_SESSION["user_role"] ?? "";

    if (!in_array($user_role, $roles, true)) {

        header("Location: ../dashboard/index.php");

        exit;

    }

}


/* ==========================================
   10. GENERATE CSRF TOKEN
========================================== */

function generateCsrfToken(): string
{

    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));

    return $_SESSION["csrf_token"];

}


/* ==========================================
   11. GET CSRF TOKEN
========================================== */

function getCsrfToken(): string
{

    if (empty($_SESSION["csrf_token"])) {

        return generateCsrfToken();

    }

    return $_SESSION["csrf_token"];

}


/* ==========================================
   12. VERIFY CSRF TOKEN
========================================== */

function verifyCsrfToken(string $token): bool
{

    if (empty($_SESSION["csrf_token"])) {

        return false;

    }

    return hash_equals(
        $_SESSION["csrf_token"],
        $token
    );

}
