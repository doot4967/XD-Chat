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
   06. CHECK AJAX REQUEST
========================================== */

function isAjaxRequest(): bool
{

    $requested_with = $_SERVER["HTTP_X_REQUESTED_WITH"] ?? "";

    $accept_header = $_SERVER["HTTP_ACCEPT"] ?? "";

    $request_uri = $_SERVER["REQUEST_URI"] ?? "";

    return strtolower($requested_with) === "xmlhttprequest"
        || stripos($accept_header, "application/json") !== false
        || stripos($request_uri, "/ajax/") !== false;

}


/* ==========================================
   07. HANDLE INACTIVE SESSION
========================================== */

function handleInactiveSession(): void
{

    logoutUser();

    if (isAjaxRequest()) {

        http_response_code(401);

        header("Content-Type: application/json");

        echo json_encode([
            "success" => false,
            "message" => "Your account is inactive. Please contact support.",
            "redirect" => "../auth/login.php?inactive=1"
        ]);

        exit;

    }

    header("Location: ../auth/login.php?inactive=1");

    exit;

}


/* ==========================================
   08. VERIFY ACTIVE SESSION USER
========================================== */

function verifyActiveSessionUser(): void
{

    if (!isLoggedIn()) {

        return;

    }

    $connection_path = dirname(__DIR__, 2)
        . DIRECTORY_SEPARATOR
        . "database"
        . DIRECTORY_SEPARATOR
        . "connection.php";

    try {

        require $connection_path;

        if (!isset($pdo) || !$pdo instanceof PDO) {

            handleInactiveSession();

        }

        $statement = $pdo->prepare(
            "SELECT status
             FROM users
             WHERE id = ?
             LIMIT 1"
        );

        $statement->execute([
            $_SESSION["user_id"]
        ]);

        $status = $statement->fetchColumn();

        if ($status !== "active") {

            handleInactiveSession();

        }

    } catch (Throwable $exception) {

        handleInactiveSession();

    }

}


/* ==========================================
   09. REFRESH SESSION ACTIVITY
========================================== */

function refreshSessionActivity(): void
{

    $_SESSION["last_activity"] = time();

}


/* ==========================================
   10. LOGOUT USER
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
   11. REQUIRE LOGIN
========================================== */

function requireLogin(): void
{

    if (!isLoggedIn()) {

        header("Location: ../auth/login.php");

        exit;

    }

    verifyActiveSessionUser();

    if (isSessionExpired()) {

        logoutUser();

        header("Location: ../auth/login.php?timeout=1");

        exit;

    }

    refreshSessionActivity();

}


/* ==========================================
   12. REQUIRE ROLE
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
   13. GENERATE CSRF TOKEN
========================================== */

function generateCsrfToken(): string
{

    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));

    return $_SESSION["csrf_token"];

}


/* ==========================================
   14. GET CSRF TOKEN
========================================== */

function getCsrfToken(): string
{

    if (empty($_SESSION["csrf_token"])) {

        return generateCsrfToken();

    }

    return $_SESSION["csrf_token"];

}


/* ==========================================
   15. VERIFY CSRF TOKEN
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
