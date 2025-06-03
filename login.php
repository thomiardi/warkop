<?php
session_start();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    include 'db.php';
    $username = $conn->real_escape_string($_POST['username']);
    $password = $conn->real_escape_string($_POST['password']);
    $result = $conn->query("SELECT * FROM users WHERE username='$username' AND password='$password'");
    if ($result && $result->num_rows > 0) {
        $_SESSION['user'] = $username;
        header('Location: dashboard.php'); // Redirect to dashboard
        exit;
    } else {
        $error = 'Invalid username or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Warkop Panjalu</title>
    <link rel="stylesheet" href="login.css">
</head>
<body>
    <form method="POST" class="login-form">
        <h1>Login</h1>
        <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>
        <label for="username">Username</label>
        <input type="text" name="username" id="username" required>
        <label for="password">Password</label>
        <input type="password" name="password" id="password" required>
        <button type="submit">Login</button>
        <p>Don't have an account? <a href="register.php">Register here</a></p>
    </form>
</body>
</html>
