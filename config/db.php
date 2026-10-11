<?php
$conn = new mysqli("localhost", "admin", "maic135", "Travel_Agency");

if ($conn->connect_error) {
    die("Database connection failed.");
}
$conn->set_charset("utf8mb4");