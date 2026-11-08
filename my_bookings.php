<?php
session_start();
require "config/db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$user_id = (int)$_SESSION["user_id"];
$trip_id = isset($_GET["id"]) ? (int)$_GET["id"] : 0;
$error   = "";
$success = "";
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["pay_booking"])) {
    $booking_id     = (int)$_POST["booking_id"];
    $payment_method = trim($_POST["payment_method"] ?? "JazzCash");
    $transaction_id = trim($_POST["transaction_id"] ?? "");

    if ($transaction_id !== "") {
        $pay_stmt = $conn->prepare("INSERT INTO Payments (booking_id, payment_method, transaction_id) VALUES (?, ?, ?)");
        $pay_stmt->bind_param("iss", $booking_id, $payment_method, $transaction_id);
        
        if ($pay_stmt->execute()) {
            $upd = $conn->prepare("UPDATE Bookings SET status = 'confirmed' WHERE booking_id = ? AND user_id = ?");
            $upd->bind_param("ii", $booking_id, $user_id);
            $upd->execute();
            $upd->close();
            $success = "Payment recorded! Your booking is now confirmed.";
        }
        $pay_stmt->close();
    } else {
        $error = "Please enter a valid Transaction ID.";
    }
}

$trip = null;
$remaining_seats = 0;

if ($trip_id > 0) {

    $stmt = $conn->prepare("SELECT trip_id, title, price_pkr, max_capacity FROM Trips WHERE trip_id = ?");
    $stmt->bind_param("i", $trip_id);
    $stmt->execute();
    $trip = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($trip) {
     
        $sum_stmt = $conn->prepare("SELECT COALESCE(SUM(seats_booked), 0) AS total_booked FROM Bookings WHERE trip_id = ? AND status != 'cancelled'");
        $sum_stmt->bind_param("i", $trip_id);
        $sum_stmt->execute();
        $booked_row = $sum_stmt->get_result()->fetch_assoc();
        $sum_stmt->close();

        $already_booked  = (int)$booked_row["total_booked"];
        $remaining_seats = max(0, (int)$trip["max_capacity"] - $already_booked);

        if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["confirm_booking"])) {
            $seats_booked = (int)($_POST["seats_booked"] ?? 0);

            if ($remaining_seats <= 0) {
                $error = "Sorry, this trip is completely sold out.";
            } elseif ($seats_booked <= 0) {
                $error = "Please enter at least 1 seat.";
            } elseif ($seats_booked > $remaining_seats) {
            
                $error = "Cannot book $seats_booked seats! Only $remaining_seats seat(s) are left.";
            } else {
                $total_amount = $seats_booked * $trip["price_pkr"];
                $status = "pending";

                $insert = $conn->prepare("INSERT INTO Bookings (user_id, trip_id, seats_booked, total_amount, status) VALUES (?, ?, ?, ?, ?)");
                $insert->bind_param("iiids", $user_id, $trip_id, $seats_booked, $total_amount, $status);
                
                if ($insert->execute()) {
                    $remaining_seats -= $seats_booked;
                    $success = "Booked $seats_booked seat(s)! Total: PKR " . number_format($total_amount) . ". Scroll down to complete payment.";
                } else {
                    $error = "Booking failed. Please try again.";
                }
                $insert->close();
            }
        }
    }
}

$b_stmt = $conn->prepare("
    SELECT b.booking_id, b.seats_booked, b.total_amount, b.status, b.created_at,
           t.title, t.destination, t.departure_date,
           p.payment_method, p.transaction_id
    FROM Bookings b
    JOIN Trips t ON b.trip_id = t.trip_id
    LEFT JOIN Payments p ON b.booking_id = p.booking_id
    WHERE b.user_id = ?
    ORDER BY b.created_at DESC
");
$b_stmt->bind_param("i", $user_id);
$b_stmt->execute();
$my_bookings = $b_stmt->get_result();
$b_stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Bookings - Travel Agency</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <nav class="navbar">
        <span class="brand"><a href="home.php" style="color:white; text-decoration:none;">Travel Agency</a></span>
        <span>
            <a href="home.php" style="margin-right:15px;">Home</a>
            <a href="book.php" style="margin-right:15px;">My Bookings</a>
            <a href="logout.php">Log out</a>
        </span>
    </nav>

    <main class="container">
        <?php if ($error !== ""): ?>
            <p class="msg error"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>

        <?php if ($success !== ""): ?>
            <p class="msg success"><?php echo htmlspecialchars($success); ?></p>
        <?php endif; ?>
        <?php if ($trip): ?>
            <div class="auth-box" style="margin: 20px auto 40px auto;">
                <h2>Book Trip: <?php echo htmlspecialchars($trip["title"]); ?></h2>
                <p>Price per seat: PKR <?php echo number_format($trip["price_pkr"]); ?></p>
                <p><strong>Available Seats:</strong> <?php echo $remaining_seats; ?> / <?php echo (int)$trip["max_capacity"]; ?></p>

                <?php if ($remaining_seats <= 0): ?>
                    <p class="msg error">This trip is fully booked!</p>
                <?php else: ?>
                    <form method="POST">
                        <label for="seats_booked">Number of Seats (Max <?php echo $remaining_seats; ?>):</label>
                        <input type="number" id="seats_booked" name="seats_booked" value="1" min="1" max="<?php echo $remaining_seats; ?>" required>
                        <button type="submit" name="confirm_booking">Confirm Booking</button>
                    </form>
                <?php endif; ?>
            </div>
            <hr style="margin: 30px 0;">
        <?php endif; ?>
        <h1>My Bookings & Payments</h1>
        <div class="cards">
            <?php if ($my_bookings && $my_bookings->num_rows > 0): ?>
                <?php while ($b = $my_bookings->fetch_assoc()): ?>
                    <div class="card">
                        <h3><?php echo htmlspecialchars($b["title"]); ?> (<?php echo htmlspecialchars($b["destination"]); ?>)</h3>
                        <p><strong>Departure:</strong> <?php echo htmlspecialchars($b["departure_date"]); ?></p>
                        <p><strong>Seats Booked:</strong> <?php echo (int)$b["seats_booked"]; ?></p>
                        <p class="price">PKR <?php echo number_format($b["total_amount"]); ?></p>
                        <p><strong>Status:</strong> <?php echo strtoupper(htmlspecialchars($b["status"])); ?></p>

                        <?php if (!empty($b["transaction_id"])): ?>
                            <p style="color: green; margin-top: 10px;">
                                <strong>Paid via <?php echo htmlspecialchars($b["payment_method"]); ?></strong><br>
                                <small>Txn ID: <?php echo htmlspecialchars($b["transaction_id"]); ?></small>
                            </p>
                        <?php else: ?>
                            <form method="POST" style="margin-top: 10px; border-top: 1px solid #eee; padding-top: 10px;">
                                <input type="hidden" name="booking_id" value="<?php echo (int)$b["booking_id"]; ?>">
                                <label>Pay via:</label>
                                <select name="payment_method" style="width:100%; padding:5px; margin:5px 0;">
                                    <option value="JazzCash">JazzCash</option>
                                    <option value="EasyPaisa">EasyPaisa</option>
                                    <option value="Bank Transfer">Bank Transfer</option>
                                </select>
                                <input type="text" name="transaction_id" placeholder="Transaction ID (e.g. TXN12345)" required style="width:100%; padding:6px; margin:5px 0;">
                                <button type="submit" name="pay_booking" style="width:100%; padding:8px; background:#28a745; color:white; border:none; border-radius:4px; cursor:pointer;">Submit Payment</button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>You have no bookings yet. <a href="home.php">Browse upcoming trips</a> to book one!</p>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>