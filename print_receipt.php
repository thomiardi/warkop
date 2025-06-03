<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

include 'db.php';

if (!isset($_GET['order_id'])) {
    header('Location: new_order.php');
    exit;
}

$order_id = intval($_GET['order_id']);
$query = "SELECT * FROM orders WHERE id = $order_id AND user_id = (SELECT id FROM users WHERE username = '" . $_SESSION['user'] . "')";
$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
    $order = $result->fetch_assoc();
} else {
    header('Location: new_order.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt - Warkop Panjalu</title>
    <link rel="stylesheet" href="print_receipt.css">
</head>
<body>
    <div class="receipt-container">
        <h1>Warkop Panjalu</h1>
        <p><strong>Order ID:</strong> <?php echo htmlspecialchars($order['id']); ?></p>
        <p><strong>Date:</strong> <?php echo htmlspecialchars($order['created_at']); ?></p>
        <p><strong>Items:</strong> <?php echo htmlspecialchars($order['items']); ?></p>
        <p><strong>Total:</strong> Rp. <?php echo number_format($order['total_price'], 0, ',', '.'); ?></p>
        <p><strong>Payment Method:</strong> <?php echo htmlspecialchars($order['payment_method']); ?></p>
        <button onclick="window.print()">Print Receipt</button>
        <a href="new_order.php" class="btn-back">Back to Orders</a>
    </div>
</body>
</html>
