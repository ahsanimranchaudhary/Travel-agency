<?php
session_start();
require "config/db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$result = $conn->query(
    "SELECT trip_id, title, destination, price_pkr, departure_date, max_capacity
     FROM Trips
     WHERE departure_date >= CURDATE()
     ORDER BY departure_date"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Home - Travel Agency</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <nav class="navbar">
        <span class="brand">Travel Agency</span>
        <span>
            Hello, <?php echo htmlspecialchars($_SESSION["full_name"]); ?>
            <a href="logout.php">Logout</a>
        </span>
    </nav>

    <main class="container">
        <h1>Upcoming Trips</h1>

        <div class="cards">
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while ($trip = $result->fetch_assoc()): ?>
                    <div class="card">
                        <h3><?php echo htmlspecialchars($trip["title"]); ?></h3>
                        <p>Destination: <?php echo htmlspecialchars($trip["destination"]); ?></p>
                        <p>Departs: <?php echo htmlspecialchars($trip["departure_date"]); ?></p>
                        <p>Seats: <?php echo (int)$trip["max_capacity"]; ?></p>
                        <p class="price">PKR <?php echo number_format($trip["price_pkr"]); ?></p>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>No upcoming trips yet.</p>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>