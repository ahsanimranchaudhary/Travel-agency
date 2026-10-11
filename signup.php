<?php
session_start();
require "config/db.php";

if (isset($_SESSION["user_id"])) {
    header("Location: home.php");
    exit;
}

$errors = [];
$full_name = "";
$email = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = trim($_POST["full_name"] ?? "");
    $email     = trim($_POST["email"] ?? "");
    $password  = $_POST["password"] ?? "";
    $confirm   = $_POST["confirm"] ?? "";

    if ($full_name === "") {
        $errors[] = "Full name is required.";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Enter a valid email.";
    }
    if (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters.";
    }
    if ($password !== $confirm) {
        $errors[] = "Passwords do not match.";
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT user_id FROM Users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $errors[] = "This email is already registered.";
        }
        $stmt->close();
    }

    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO Users (full_name, email, password) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $full_name, $email, $hash);
        $stmt->execute();
        $stmt->close();

        header("Location: login.php?registered=1");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign Up - Travel Agency</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="auth-box">
        <h2>Create Account</h2>

        <?php foreach ($errors as $error): ?>
            <p class="msg error"><?php echo htmlspecialchars($error); ?></p>
        <?php endforeach; ?>

        <form method="POST">
            <input type="text" name="full_name" placeholder="Full name"
                   value="<?php echo htmlspecialchars($full_name); ?>">
            <input type="text" name="email" placeholder="Email"
                   value="<?php echo htmlspecialchars($email); ?>">
            <input type="password" name="password" placeholder="Password">
            <input type="password" name="confirm" placeholder="Confirm password">
            <button type="submit">Sign Up</button>
        </form>

        <p class="link">Already have an account? <a href="login.php">Login</a></p>
    </div>
</body>
</html>