<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    echo '<p>No items in your order.</p>';
    exit;
}
include 'db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order List - Warkop Panjalu</title>
    <link rel="stylesheet" href="list_order.css">
</head>
<body>
    <h1>Your Orders</h1>
    <ul>
        <?php
        $ids = implode(',', array_map('intval', $_SESSION['cart']));
        $result = $conn->query("SELECT * FROM menu WHERE id IN ($ids)");
        $total = 0;
        while ($row = $result->fetch_assoc()) {
            echo '<li>' . htmlspecialchars($row['name']) . ' - Rp. ' . number_format($row['price'], 0, ',', '.') . '</li>';
            $total += $row['price'];
        }
        echo "<p>Total: Rp. " . number_format($total, 0, ',', '.') . "</p>";
        ?>
    </ul>
    <a href="checkout.php">Proceed to Checkout</a>
</body>
</html>
