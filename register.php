<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    include 'db.php';
    $username = $conn->real_escape_string($_POST['username']);
    $password = password_hash($conn->real_escape_string($_POST['password']), PASSWORD_BCRYPT); // Secure password hashing
    $result = $conn->query("SELECT * FROM users WHERE username='$username'");
    if ($result && $result->num_rows > 0) {
        $error = 'Username already exists.';
    } else {
        $conn->query("INSERT INTO users (username, password) VALUES ('$username', '$password')");
        header('Location: login.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Warkop Panjalu</title>
    <link rel="stylesheet" href="register.css">
</head>
<body>
    <header class="header">
        <h1>Register</h1>
    </header>
    <section class="register-form">
        <form method="POST" class="login-form">
            <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>
            <label for="username">Username:</label>
            <input type="text" name="username" id="username" required>
            <label for="password">Password:</label>
            <input type="password" name="password" id="password" required>
            <button type="submit">Register</button>
            <p>Already have an account? <a href="login.php">Login here</a></p>
        </form>
    </section>
</body>
</html>
