<?php
session_start();
require_once 'config.php';

$login_identity = $password = ""; // This variable will hold either username or email
$login_identity_err = $password_err = $login_err = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Check if login identity (username or email) is empty
    if (empty(trim($_POST["login_identity"]))) {
        $login_identity_err = "Please enter your username or email address.";
    } else {
        $login_identity = trim($_POST["login_identity"]);
    }

    // Check if password is empty
    if (empty(trim($_POST["password"]))) {
        $password_err = "Please enter your password.";
    } else {
        $password = trim($_POST["password"]);
    }

    // Validate credentials
    if (empty($login_identity_err) && empty($password_err)) {
        // SQL query to check both username and email
        // User can log in with either username OR email
        $sql = "SELECT id, username, email, password, is_admin FROM users WHERE username = ? OR email = ?";

        if ($stmt = mysqli_prepare($conn, $sql)) {
            // Bind the same login_identity to both placeholders
            mysqli_stmt_bind_param($stmt, "ss", $param_login_identity, $param_login_identity);
            $param_login_identity = $login_identity;

            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_store_result($stmt);

                if (mysqli_stmt_num_rows($stmt) == 1) {
                    mysqli_stmt_bind_result($stmt, $id, $db_username, $db_email, $hashed_password, $is_admin);
                    if (mysqli_stmt_fetch($stmt)) {
                        if (password_verify($password, $hashed_password)) {
                            session_regenerate_id();
                            $_SESSION['loggedin'] = true;
                            $_SESSION['id'] = $id;
                            $_SESSION['username'] = $db_username; // Store actual username
                            $_SESSION['email'] = $db_email;       // Store actual email (new)
                            $_SESSION['is_admin'] = $is_admin;

                            if ($is_admin == 1) {
                                header("location: admin.php");
                            } else {
                                header("location: chat.php");
                            }
                        } else {
                            $login_err = "Invalid username/email or password.";
                        }
                    }
                } else {
                    $login_err = "Invalid username/email or password.";
                }
            } else {
                echo "Oops! Something went wrong. Please try again later.";
            }
            mysqli_stmt_close($stmt);
        }
    }
    mysqli_close($conn);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <h2>Login</h2>
        <p>Please fill in your credentials to login.</p>

        <?php
        if (!empty($login_err)) {
            echo '<div class="alert alert-danger">' . $login_err . '</div>';
        }
        ?>

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group <?php echo (!empty($login_identity_err)) ? 'has-error' : ''; ?>">
                <label>Username or Email Address</label>
                <input type="text" name="login_identity" class="form-control" value="<?php echo $login_identity; ?>" placeholder="your username or email">
                <span class="help-block"><?php echo $login_identity_err; ?></span>
            </div>
            <div class="form-group <?php echo (!empty($password_err)) ? 'has-error' : ''; ?>">
                <label>Password</label>
                <input type="password" name="password" class="form-control" placeholder="your password">
                <span class="help-block"><?php echo $password_err; ?></span>
            </div>
            <div class="form-group">
                <input type="submit" class="btn" value="Login">
            </div>
            <p>Don't have an account? <a href="register.php">Sign up now</a>.</p>
        </form>
    </div>
</body>
</html>