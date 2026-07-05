<?php
/*
==================================================
Project : XD Chat
Version : 2.0.0
File    : register.php
Module  : User Registration
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


/* ==========================================
   02. HANDLE REGISTER REQUEST
========================================== */

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $full_name = clean($_POST["full_name"]);

    $email = clean($_POST["email"]);

    $password = clean($_POST["password"]);

    $message = registerUser(
        $pdo,
        $full_name,
        $email,
        $password
    );

}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Create Account | XD Chat</title>

</head>

<body>

    <h1>Create Account</h1>

    <?php if (!empty($message)) { ?>

        <p>
            <?php echo $message; ?>
        </p>

    <?php } ?>

    <form method="POST">

        <input
            type="text"
            name="full_name"
            placeholder="Full Name"
            required
        >

        <br><br>

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
            Create Account
        </button>

    </form>

</body>

</html>