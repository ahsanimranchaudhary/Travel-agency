<?php
session_start();
require "config/db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$trip_id = isset($_GET["id"]) ? (int)$_GET["id"] : 0;

// Fetch trip price and details
$stmt = $conn->prepare("SELECT title, price_pkr FROM Trips WHERE trip_id = ?");
$stmt->bind_param("i", $trip_id);
$stmt->execute();
$trip = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$trip) {
    header("Location: home.php");
    exit;
}

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $seats_booked = (int)($_POST["seats_booked"] ?? 0);

    if ($seats_booked <= 0) {
        $error = "Please enter a valid number of seats.";
    } else {
        $user_id = $_SESSION["user_id"];
        $total_amount = $seats_booked * $trip["price_pkr"];
        $status = "confirmed"; // Default status

        $insert = $conn->prepare("INSERT INTO Bookings (user_id, trip_id, seats_booked, total_amount, status) VALUES (?, ?, ?, ?, ?)");
        $insert->bind_param("iiids", $user_id, $trip_id, $seats_booked, $total_amount, $status);
        
        if ($insert->execute()) {
            $success = "Booking successful! Total amount: PKR " . number_format($total_amount);
        } else {
            $error = "Booking failed. Please try again.";
        }
        $insert->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Book <?php echo htmlspecialchars($trip["title"]); ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <nav class="navbar">
        <span class="brand"><a href="home.php" style="color:white; text-decoration:none;">Travel Agency</a></span>
        <span><a href="trip.php?id=<?php echo $trip_id; ?>">Back to Trip</a></span>
    </nav>

    <main class="container auth-box" style="margin-top: 40px;">
        <h2>Book Trip: <?php echo htmlspecialchars($trip["title"]); ?></h2>
        <p>Price per seat: PKR <?php echo number_format($trip["price_pkr"]); ?></p>

        <?php if ($error !== ""): ?>
            <p class="msg error"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>

        <?php if ($success !== ""): ?>
            <p class="msg success"><?php echo htmlspecialchars($success); ?></p>
            <p><a href="home.php">Return to Home</a></p>
        <?php else: ?>
            <form method="POST">
                <label>Number of Seats:</label>
                <input type="number" name="seats_booked" value="1" min="1" style="width:100%; padding:8px; margin:10px 0;">
                <button type="submit" style="width:100%; padding:10px;">Confirm Booking</button>
            </form>
        <?php endif; ?>
    </main>
</body>
</html>