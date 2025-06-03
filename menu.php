<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

include 'db.php';

// Handle adding items to the cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['menu_id'])) {
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    $menu_id = $_POST['menu_id'];
    if ($_POST['action'] === 'increase') {
        $_SESSION['cart'][$menu_id] = ($_SESSION['cart'][$menu_id] ?? 0) + 1;
    } elseif ($_POST['action'] === 'decrease') {
        if (isset($_SESSION['cart'][$menu_id]) && $_SESSION['cart'][$menu_id] > 1) {
            $_SESSION['cart'][$menu_id]--;
        } else {
            unset($_SESSION['cart'][$menu_id]);
        }
    }
}

// Handle clearing the cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_cart'])) {
    unset($_SESSION['cart']);
}

// Handle checkout and insert into orders table
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['checkout'])) {
    if (!empty($_SESSION['cart']) && isset($_POST['payment_method'])) {
        $user_id_query = "SELECT id FROM users WHERE username = '" . $_SESSION['user'] . "'";
        $user_id_result = $conn->query($user_id_query);
        $user_id = $user_id_result->fetch_assoc()['id'];

        $items = [];
        $total_price = 0;
        $ids = implode(',', array_keys($_SESSION['cart']));
        $result = $conn->query("SELECT * FROM menu WHERE id IN ($ids)");
        while ($row = $result->fetch_assoc()) {
            $quantity = $_SESSION['cart'][$row['id']];
            $items[] = $row['name'] . " (x$quantity)";
            $total_price += $row['price'] * $quantity;
        }

        $items_string = implode(', ', $items);
        $payment_method = $conn->real_escape_string($_POST['payment_method']);
        $conn->query("INSERT INTO orders (user_id, items, total_price, payment_method, status) VALUES ('$user_id', '$items_string', '$total_price', '$payment_method', 'in_progress')");
        $order_id = $conn->insert_id;
        unset($_SESSION['cart']);
        header("Location: print_receipt.php?order_id=$order_id");
        exit;
    }
}

// Fetch menu items based on type
$menuType = $_GET['menu'] ?? 'all';
$query = $menuType === 'all' ? "SELECT * FROM menu" : "SELECT * FROM menu WHERE type='$menuType'";
$result = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu - Warkop Panjalu</title>
    <link rel="stylesheet" href="menu.css">
</head>
<body>
    <div class="container">
        <aside class="sidebar">
            <h2>Warkop Panjalu</h2>
            <nav>
                <ul>
                    <li><a href="?menu=food">Makanan</a></li>
                    <li><a href="?menu=drinks">Minuman</a></li>
                    <li><a href="dashboard.php">Dashboard</a></li>
                </ul>
            </nav>
        </aside>
        <main class="main-content">
            <h1>Menu</h1>
            <div class="menu-grid">
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <div class="menu-card">
                            <img src="images/<?= htmlspecialchars($row['image'] ?? 'default.jpg') ?>" alt="<?= htmlspecialchars($row['name']) ?>">
                            <h3><?= htmlspecialchars($row['name']) ?></h3>
                            <p><?= htmlspecialchars($row['description']) ?></p>
                            <p class="price">Rp. <?= number_format($row['price'], 0, ',', '.') ?></p>
                            <form method="POST">
                                <input type="hidden" name="menu_id" value="<?= $row['id'] ?>">
                                <button type="submit" name="action" value="increase">Add to Order</button>
                            </form>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p>No menu items available.</p>
                <?php endif; ?>
            </div>
        </main>
        <aside class="order-panel">
            <h2>Pesanan Baru</h2>
            <ul class="order-list">
                <?php
                $total = 0;
                if (!empty($_SESSION['cart'])):
                    $ids = implode(',', array_keys($_SESSION['cart']));
                    $result = $conn->query("SELECT * FROM menu WHERE id IN ($ids)");
                    while ($row = $result->fetch_assoc()):
                        $quantity = $_SESSION['cart'][$row['id']];
                        $subtotal = $row['price'] * $quantity;
                        $total += $subtotal;
                ?>
                    <li>
                        <span><?= htmlspecialchars($row['name']) ?></span>
                        <span>Rp. <?= number_format($subtotal, 0, ',', '.') ?></span>
                        <div class="quantity-controls">
                            <form method="POST">
                                <input type="hidden" name="menu_id" value="<?= $row['id'] ?>">
                                <button type="submit" name="action" value="decrease">-</button>
                            </form>
                            <span><?= $quantity ?></span>
                            <form method="POST">
                                <input type="hidden" name="menu_id" value="<?= $row['id'] ?>">
                                <button type="submit" name="action" value="increase">+</button>
                            </form>
                        </div>
                    </li>
                <?php endwhile; else: ?>
                    <p>No items in your order.</p>
                <?php endif; ?>
            </ul>
            <div class="order-total">
                <p>Total: <strong>Rp. <?= number_format($total, 0, ',', '.') ?></strong></p>
                <form method="POST">
                    <label for="payment_method">Payment Method:</label>
                    <select name="payment_method" id="payment_method" required>
                        <option value="cash">Cash</option>
                        <option value="credit_card">Credit Card</option>
                        <option value="e_wallet">E-Wallet</option>
                    </select>
                    <button type="submit" name="clear_cart" class="clear-button">Clear Order</button>
                    <button type="submit" name="checkout" class="pay-button">Pay</button>
                </form>
            </div>
        </aside>
    </div>
</body>
</html>
