<?php
session_start();
require "config/db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$trip_id = isset($_GET["id"]) ? (int)$_GET["id"] : 0;
$review_msg = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["submit_review"])) {
    $rating  = (int)($_POST["rating"] ?? 5);
    $comment = trim($_POST["comment"] ?? "");
    $user_id = $_SESSION["user_id"];

    if ($rating >= 1 && $rating <= 5 && $comment !== "") {
        $rev_stmt = $conn->prepare("INSERT INTO Reviews (user_id, trip_id, rating, comment) VALUES (?, ?, ?, ?)");
        $rev_stmt->bind_param("iiis", $user_id, $trip_id, $rating, $comment);
        $rev_stmt->execute();
        $rev_stmt->close();
        $review_msg = "Review submitted successfully!";
    }
}

$stmt = $conn->prepare("SELECT * FROM Trips WHERE trip_id = ?");
$stmt->bind_param("i", $trip_id);
$stmt->execute();
$trip = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$trip) {
    header("Location: home.php");
    exit;
}

$it_stmt = $conn->prepare("SELECT day_number, activity_description FROM Itineraries WHERE trip_id = ? ORDER BY day_number");
$it_stmt->bind_param("i", $trip_id);
$it_stmt->execute();
$itineraries = $it_stmt->get_result();
$it_stmt->close();

$guide_stmt = $conn->prepare("
    SELECT g.name, g.contact_number, g.experience_years 
    FROM Guides g
    JOIN Trip_Guides tg ON g.guide_id = tg.guide_id
    WHERE tg.trip_id = ?
");
$guide_stmt->bind_param("i", $trip_id);
$guide_stmt->execute();
$guides = $guide_stmt->get_result();
$guide_stmt->close();

$rev_list_stmt = $conn->prepare("
    SELECT r.rating, r.comment, r.created_at, u.full_name 
    FROM Reviews r
    JOIN Users u ON r.user_id = u.user_id
    WHERE r.trip_id = ?
    ORDER BY r.created_at DESC
");
$rev_list_stmt->bind_param("i", $trip_id);
$rev_list_stmt->execute();
$reviews = $rev_list_stmt->get_result();
$rev_list_stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($trip["title"]); ?> - Details</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <nav class="navbar">
        <span class="brand"><a href="home.php" style="color:white; text-decoration:none;">Travel Agency</a></span>
        <span>
            <a href="my_bookings.php" style="margin-right:15px;">My Bookings</a>
            <a href="home.php">Back to Trips</a>
        </span>
    </nav>

    <main class="container">
        <h1><?php echo htmlspecialchars($trip["title"]); ?></h1>
        <p><strong>Destination:</strong> <?php echo htmlspecialchars($trip["destination"]); ?></p>
        <p><strong>Departure Date:</strong> <?php echo htmlspecialchars($trip["departure_date"]); ?></p>
        <p><strong>Price:</strong> PKR <?php echo number_format($trip["price_pkr"]); ?></p>
        <p><strong>Max Capacity:</strong> <?php echo (int)$trip["max_capacity"]; ?></p>

        <div style="margin: 20px 0;">
            <a href="book.php?id=<?php echo $trip_id; ?>" style="padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 4px;">Book This Trip</a>
        </div>

        <hr style="margin: 25px 0;">

        <h2>Day-by-Day Itinerary</h2>
        <?php if ($itineraries->num_rows > 0): ?>
            <ul>
                <?php while ($row = $itineraries->fetch_assoc()): ?>
                    <li><strong>Day <?php echo (int)$row["day_number"]; ?>:</strong> <?php echo htmlspecialchars($row["activity_description"]); ?></li>
                <?php endwhile; ?>
            </ul>
        <?php else: ?>
            <p>Itinerary details coming soon.</p>
        <?php endif; ?>

        <h2>Assigned Tour Guides</h2>
        <?php if ($guides->num_rows > 0): ?>
            <ul>
                <?php while ($g = $guides->fetch_assoc()): ?>
                    <li><strong><?php echo htmlspecialchars($g["name"]); ?></strong> — <?php echo (int)$g["experience_years"]; ?> years experience (Contact: <?php echo htmlspecialchars($g["contact_number"]); ?>)</li>
                <?php endwhile; ?>
            </ul>
        <?php else: ?>
            <p>No guides assigned yet.</p>
        <?php endif; ?>

        <hr style="margin: 25px 0;">

        <h2>Traveler Reviews</h2>
        <?php if ($review_msg !== ""): ?>
            <p class="msg success"><?php echo htmlspecialchars($review_msg); ?></p>
        <?php endif; ?>

        <form method="POST" style="margin-bottom: 25px; background: #f9f9f9; padding: 15px; border-radius: 6px;">
            <h4>Leave a Review</h4>
            <label>Rating (1 to 5):</label>
            <select name="rating" style="padding: 6px; margin: 8px 0; display:block;">
                <option value="5">5 - Excellent</option>
                <option value="4">4 - Very Good</option>
                <option value="3">3 - Average</option>
                <option value="2">2 - Poor</option>
                <option value="1">1 - Terrible</option>
            </select>
            <textarea name="comment" rows="3" placeholder="Share your experience..." required style="width:100%; padding:8px; margin-bottom:10px;"></textarea>
            <button type="submit" name="submit_review" style="padding: 8px 16px;">Submit Review</button>
        </form>

        <?php if ($reviews->num_rows > 0): ?>
            <?php while ($rev = $reviews->fetch_assoc()): ?>
                <div style="border-bottom: 1px solid #ddd; padding: 10px 0;">
                    <strong><?php echo htmlspecialchars($rev["full_name"]); ?></strong> 
                    <span>(Rating: <?php echo (int)$rev["rating"]; ?>/5)</span>
                    <p style="margin: 5px 0;"><?php echo htmlspecialchars($rev["comment"]); ?></p>
                    <small style="color: #666;"><?php echo htmlspecialchars($rev["created_at"]); ?></small>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No reviews yet. Be the first to review this trip!</p>
        <?php endif; ?>
    </main>
</body>
</html>