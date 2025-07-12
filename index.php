<?php
session_start();

if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    if ($_SESSION['is_admin'] == 1) {
        header("location: admin.php");
    } else {
        header("location: chat.php");
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to Chatbot</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <h2>Welcome to the Chatbot App!</h2>
        <p>Please <a href="login.php">Login</a> or <a href="register.php">Register</a>.</p>
    </div>
</body>
</html>