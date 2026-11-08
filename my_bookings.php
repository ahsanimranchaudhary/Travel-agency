<?php
session_start();
require "config/db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION["user_id"];
$msg = "";

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
            $msg = "Payment recorded! Your booking is now confirmed.";
        }
        $pay_stmt->close();
    }
}

$stmt = $conn->prepare("
    SELECT b.booking_id, b.seats_booked, b.total_amount, b.status, b.created_at,
           t.title, t.destination, t.departure_date,
           p.payment_method, p.transaction_id, p.payment_date
    FROM Bookings b
    JOIN Trips t ON b.trip_id = t.trip_id
    LEFT JOIN Payments p ON b.booking_id = p.booking_id
    WHERE b.user_id = ?
    ORDER BY b.created_at DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$bookings = $stmt->get_result();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Bookings - Travel Agency</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <nav class="navbar">
        <span class="brand"><a href="home.php" style="color:white; text-decoration:none;">Travel Agency</a></span>
        <span>
            <a href="home.php" style="margin-right:15px;">Home</a>
            <a href="logout.php">Log out</a>
        </span>
    </nav>

    <main class="container">
        <h1>My Bookings & Payments</h1>

        <?php if ($msg !== ""): ?>
            <p class="msg success"><?php echo htmlspecialchars($msg); ?></p>
        <?php endif; ?>

        <div class="cards">
            <?php if ($bookings->num_rows > 0): ?>
                <?php while ($b = $bookings->fetch_assoc()): ?>
                    <div class="card" style="margin-bottom: 20px;">
                        <h3><?php echo htmlspecialchars($b["title"]); ?> (<?php echo htmlspecialchars($b["destination"]); ?>)</h3>
                        <p><strong>Departure:</strong> <?php echo htmlspecialchars($b["departure_date"]); ?></p>
                        <p><strong>Seats Booked:</strong> <?php echo (int)$b["seats_booked"]; ?></p>
                        <p class="price"><strong>Total:</strong> PKR <?php echo number_format($b["total_amount"]); ?></p>
                        <p><strong>Status:</strong> <?php echo strtoupper(htmlspecialchars($b["status"])); ?></p>

                        <?php if ($b["transaction_id"]): ?>
                            <p style="color: green;">
                                <strong>Paid via <?php echo htmlspecialchars($b["payment_method"]); ?></strong><br>
                                <small>Txn ID: <?php echo htmlspecialchars($b["transaction_id"]); ?></small>
                            </p>
                        <?php else: ?>
                            <form method="POST" style="margin-top: 10px; border-top: 1px solid #eee; padding-top: 10px;">
                                <input type="hidden" name="booking_id" value="<?php echo (int)$b["booking_id"]; ?>">
                                <label>Payment Method:</label>
                                <select name="payment_method" style="width:100%; padding:5px; margin:5px 0;">
                                    <option value="JazzCash">JazzCash</option>
                                    <option value="EasyPaisa">EasyPaisa</option>
                                    <option value="Bank Transfer">Bank Transfer</option>
                                </select>
                                <input type="text" name="transaction_id" placeholder="Enter Transaction ID (e.g. TXN12345)" required style="width:100%; padding:6px; margin:5px 0;">
                                <button type="submit" name="pay_booking" style="width:100%; padding:8px; background:#28a745; color:white; border:none; border-radius:4px;">Submit Payment</button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>You have no bookings yet. <a href="home.php">Browse upcoming trips</a>.</p>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>