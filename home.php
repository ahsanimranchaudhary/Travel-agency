<?php
session_start();
require "config/db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$destination_filter = trim($_GET["destination"] ?? "");

if ($destination_filter !== "") {
    $stmt = $conn->prepare("
        SELECT t.trip_id, t.title, t.destination, t.price_pkr, t.departure_date, t.max_capacity,
               (t.max_capacity - COALESCE(SUM(b.seats_booked), 0)) AS seats_left
        FROM Trips t
        LEFT JOIN Bookings b ON t.trip_id = b.trip_id AND b.status != 'cancelled'
        WHERE t.departure_date >= CURDATE() AND t.destination = ?
        GROUP BY t.trip_id
        ORDER BY t.departure_date
    ");
    $stmt->bind_param("s", $destination_filter);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
} else {
    $result = $conn->query("
        SELECT t.trip_id, t.title, t.destination, t.price_pkr, t.departure_date, t.max_capacity,
               (t.max_capacity - COALESCE(SUM(b.seats_booked), 0)) AS seats_left
        FROM Trips t
        LEFT JOIN Bookings b ON t.trip_id = b.trip_id AND b.status != 'cancelled'
        WHERE t.departure_date >= CURDATE()
        GROUP BY t.trip_id
        ORDER BY t.departure_date
    ");
}

$destinations_query = $conn->query("SELECT DISTINCT destination FROM Trips ORDER BY destination");
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
            <a href="my_bookings.php">My Bookings & payments</a> |
            <a href="logout.php">Log out</a>
        </span>
    </nav>

    <main class="container">
        <h1>Upcoming Trips</h1>

        <form method="GET" style="margin-bottom: 20px;">
            <label for="destination">Filter by Destination: </label>
            <select name="destination" id="destination" onchange="this.form.submit()" style="padding: 6px; border-radius: 4px;">
                <option value="">All Destinations</option>
                <?php if ($destinations_query): ?>
                    <?php while ($row = $destinations_query->fetch_assoc()): ?>
                        <option value="<?php echo htmlspecialchars($row['destination']); ?>" <?php if ($destination_filter === $row['destination']) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($row['destination']); ?>
                        </option>
                    <?php endwhile; ?>
                <?php endif; ?>
            </select>
            <?php if ($destination_filter !== ""): ?>
                <a href="home.php" style="margin-left: 10px; text-decoration: none; color: #007bff;">Reset</a>
            <?php endif; ?>
        </form>

        <div class="cards">
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while ($trip = $result->fetch_assoc()): ?>
                    <?php $seats_left = max(0, (int)$trip["seats_left"]); ?>
                    <div class="card">
                        <h3>
                            <a href="trip.php?id=<?php echo (int)$trip["trip_id"]; ?>">
                                <?php echo htmlspecialchars($trip["title"]); ?>
                            </a>
                        </h3>
                        <p>Destination: <?php echo htmlspecialchars($trip["destination"]); ?></p>
                        <p>Departs: <?php echo htmlspecialchars($trip["departure_date"]); ?></p>
                        <p>
                            Available Seats: 
                            <strong><?php echo $seats_left; ?> / <?php echo (int)$trip["max_capacity"]; ?></strong>
                        </p>
                        <p class="price">PKR <?php echo number_format($trip["price_pkr"]); ?></p>
                        
                        <?php if ($seats_left > 0): ?>
                            <p style="margin-top: 10px;">
                                <a href="book.php?id=<?php echo (int)$trip["trip_id"]; ?>" style="padding: 6px 12px; background: #007bff; color: white; text-decoration: none; border-radius: 4px; display: inline-block;">Book Now</a>
                            </p>
                        <?php else: ?>
                            <p style="margin-top: 10px; color: red; font-weight: bold;">Sold Out</p>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>No upcoming trips found.</p>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>