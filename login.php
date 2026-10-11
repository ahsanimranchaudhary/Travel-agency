<?php
session_start();
require "config/db.php";

if (isset($_SESSION["user_id"])) {
    header("Location: home.php");
    exit;
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email    = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    $stmt = $conn->prepare("SELECT user_id, full_name, password, role FROM Users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->bind_result($id, $full_name, $dbHash, $role);

    if ($stmt->fetch() && password_verify($password, $dbHash)) {
        session_regenerate_id(true);
        $_SESSION["user_id"]   = $id;
        $_SESSION["full_name"] = $full_name;
        $_SESSION["role"]      = $role;
        header("Location: home.php");
        exit;
    } else {
        $error = "Invalid email or password.";
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - Travel Agency</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="auth-box">
        <h2>Login</h2>

        <?php if (isset($_GET["registered"])): ?>
            <p class="msg success">Account created. Please login.</p>
        <?php endif; ?>

        <?php if ($error !== ""): ?>
            <p class="msg error"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>

        <form method="POST">
            <input type="text" name="email" placeholder="Email">
            <input type="password" name="password" placeholder="Password">
            <button type="submit">Login</button>
        </form>

        <p class="link">New here? <a href="signup.php">Create an account</a></p>
    </div>
</body>
</html>