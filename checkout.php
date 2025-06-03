<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    header('Location: menu.php');
    exit;
}
include 'db.php';

$total = 0;
if (!empty($_SESSION['cart'])) {
    $ids = implode(',', array_keys($_SESSION['cart']));
    $result = $conn->query("SELECT * FROM menu WHERE id IN ($ids)");
    while ($row = $result->fetch_assoc()) {
        $quantity = $_SESSION['cart'][$row['id']];
        $subtotal = $row['price'] * $quantity;
        $total += $subtotal;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    unset($_SESSION['cart']);
    header('Location: dashboard.php'); // Redirect to dashboard after payment
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Warkop Panjalu</title>
    <link rel="stylesheet" href="checkout.css">
</head>
<body>
    <div class="checkout-container">
        <h1>Checkout</h1>
        <p class="total">Total: <strong>Rp. <?php echo number_format($total, 0, ',', '.'); ?></strong></p>
        <form method="POST">
            <label for="payment-method">Payment Method</label>
            <select name="payment-method" id="payment-method" required>
                <option value="cash">Cash</option>
                <option value="credit-card">Credit Card</option>
                <option value="e-wallet">E-Wallet</option>
            </select>
            <button type="submit">Pay Now</button>
        </form>
    </div>
</body>
</html>
