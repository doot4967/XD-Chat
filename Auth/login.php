<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

/*
==================================================
Project : XD Chat
Version : 2.0.0
File    : login.php
Module  : User Login
Status  : Development
Author  : Umesh + ChatGPT
Created : 05 July 2026
==================================================
*/


/* ==========================================
   01. LOAD DEPENDENCIES
========================================== */

require_once '../database/connection.php';
require_once '../includes/functions/auth.php';
require_once '../includes/functions/validation.php';
require_once '../includes/functions/session.php';


/* ==========================================
   02. HANDLE LOGIN REQUEST
========================================== */

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = clean($_POST["email"]);

    $password = clean($_POST["password"]);

    $result = authenticateUser(
        $pdo,
        $email,
        $password
    );

    if ($result["status"] === true) {

        loginUser($result["user"]);

        header("Location: ../dashboard/index.php");

        exit;

    }

    $message = $result["message"];

}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Login | XD Chat</title>

</head>

<body>

    <h1>Login</h1>

    <?php if (!empty($message)) { ?>

        <p>
            <?php echo $message; ?>
        </p>

    <?php } ?>

    <form method="POST">

        <input
            type="email"
            name="email"
            placeholder="Email Address"
            required
        >

        <br><br>

        <input
            type="password"
            name="password"
            placeholder="Password"
            required
        >

        <br><br>

        <button type="submit">
            Login
        </button>

    </form>

</body>

</html>