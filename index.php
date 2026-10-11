<?php
session_start();
header("Location: " . (isset($_SESSION["user_id"]) ? "home.php" : "login.php"));
exit;