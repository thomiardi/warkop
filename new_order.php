<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

include 'db.php';

// Handle marking an order as completed
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complete_order_id'])) {
    $order_id = intval($_POST['complete_order_id']);
    $conn->query("UPDATE orders SET status = 'completed' WHERE id = $order_id AND user_id = (SELECT id FROM users WHERE username = '" . $_SESSION['user'] . "')");
}

// Fetch orders in progress
$inProgressQuery = "SELECT * FROM orders WHERE user_id = (SELECT id FROM users WHERE username = '" . $_SESSION['user'] . "') AND status = 'in_progress'";
$inProgressResult = $conn->query($inProgressQuery);

// Fetch completed orders
$completedQuery = "SELECT * FROM orders WHERE user_id = (SELECT id FROM users WHERE username = '" . $_SESSION['user'] . "') AND status = 'completed'";
$completedResult = $conn->query($completedQuery);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders - Warkop Panjalu</title>
    <link rel="stylesheet" href="new_order.css">
</head>
<body>
    <div class="container">
        <h1>Orders</h1>

        <!-- Orders in Progress Section -->
        <section class="orders-section">
            <h2>Orders in Progress</h2>
            <div class="orders-list">
                <?php
                if ($inProgressResult && $inProgressResult->num_rows > 0) {
                    while ($row = $inProgressResult->fetch_assoc()) {
                        echo '<div class="order-card">';
                        echo '<h3>Order ID: ' . htmlspecialchars($row['id']) . '</h3>';
                        echo '<p><strong>Items:</strong> ' . htmlspecialchars($row['items']) . '</p>';
                        echo '<p><strong>Total:</strong> Rp. ' . number_format($row['total_price'], 0, ',', '.') . '</p>';
                        echo '<p><strong>Date:</strong> ' . htmlspecialchars($row['created_at']) . '</p>';
                        echo '<form method="POST">';
                        echo '<input type="hidden" name="complete_order_id" value="' . $row['id'] . '">';
                        echo '<button type="submit" class="btn-complete">Mark as Completed</button>';
                        echo '</form>';
                        echo '</div>';
                    }
                } else {
                    echo '<p>No orders in progress.</p>';
                }
                ?>
            </div>
        </section>

        <!-- Completed Orders Section -->
        <section class="orders-section">
            <h2>Completed Orders</h2>
            <div class="orders-list">
                <?php
                if ($completedResult && $completedResult->num_rows > 0) {
                    while ($row = $completedResult->fetch_assoc()) {
                        echo '<div class="order-card">';
                        echo '<h3>Order ID: ' . htmlspecialchars($row['id']) . '</h3>';
                        echo '<p><strong>Items:</strong> ' . htmlspecialchars($row['items']) . '</p>';
                        echo '<p><strong>Total:</strong> Rp. ' . number_format($row['total_price'], 0, ',', '.') . '</p>';
                        echo '<p><strong>Date:</strong> ' . htmlspecialchars($row['created_at']) . '</p>';
                        echo '</div>';
                    }
                } else {
                    echo '<p>No completed orders found.</p>';
                }
                ?>
            </div>
        </section>

        <a href="dashboard.php" class="btn-back">Back to Dashboard</a>
    </div>
</body>
</html>
