<?php
session_start();
require "config/db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$destination_filter = $_GET["destination"] ?? "";

if ($destination_filter !== "") {
    $stmt = $conn->prepare("
        SELECT trip_id, title, destination, price_pkr, departure_date, max_capacity
        FROM Trips
        WHERE departure_date >= CURDATE() AND destination = ?
        ORDER BY departure_date
    ");
    $stmt->bind_param("s", $destination_filter);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
} else {
    $result = $conn->query(
        "SELECT trip_id, title, destination, price_pkr, departure_date, max_capacity
         FROM Trips
         WHERE departure_date >= CURDATE()
         ORDER BY departure_date"
    );
}

$destinations_query = $conn->query("SELECT DISTINCT destination FROM Trips");
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
        Hello, <?php echo htmlspecialchars($_SESSION["full_name"]); ?> |
        <a href="my_bookings.php">My Bookings</a> |
        <a href="logout.php">Log out</a>
    </span>
</nav>

    <main class="container">
        <h1>Upcoming Trips</h1>
        <form method="GET" style="margin-bottom: 20px;">
            <label for="destination">Filter by Destination: </label>
            <select name="destination" onchange="this.form.submit()" style="padding: 5px; border-radius: 4px;">
                <option value="">All Destinations</option>
                <?php while ($row = $destinations_query->fetch_assoc()): ?>
                    <option value="<?php echo htmlspecialchars($row['destination']); ?>" <?php if($destination_filter == $row['destination']) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($row['destination']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
            <?php if ($destination_filter !== ""): ?>
                <a href="home.php" style="margin-left: 10px; text-decoration: none; color: #007bff;">Reset Filter</a>
            <?php endif; ?>
        </form>
        <div class="cards">
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while ($trip = $result->fetch_assoc()): ?>
                    <div class="card">
                        <h3><a href="trip.php?id=<?php echo (int)$trip["trip_id"]; ?>"><?php echo htmlspecialchars($trip["title"]); ?></a></h3>
                        <p>Destination: <?php echo htmlspecialchars($trip["destination"]); ?></p>
                        <p>Departs: <?php echo htmlspecialchars($trip["departure_date"]); ?></p>
                        <p>Seats: <?php echo (int)$trip["max_capacity"]; ?></p>
                        <p class="price">PKR <?php echo number_format($trip["price_pkr"]); ?></p>
                    </div>
                <?php endwhile;?>
            <?php else: ?>
                <p>No upcoming trips yet.</p>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>