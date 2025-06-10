<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get user information
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Get food items
$stmt = $pdo->prepare("
    SELECT p.*, c.name as category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    WHERE c.name LIKE '%Makanan%'
    ORDER BY p.name
");
$stmt->execute();
$food_items = $stmt->fetchAll();

// Get beverage items
$stmt = $pdo->prepare("
    SELECT p.*, c.name as category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    WHERE c.name LIKE '%Minuman%'
    ORDER BY p.name
");
$stmt->execute();
$beverage_items = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock Menu - Warkop Panjalu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .sidebar {
            width: 250px;
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
            background: #f1f3f9;
            padding: 20px;
        }
        .main-content {
            margin-left: 250px;
            padding: 20px;
        }
        .menu-card {
            background: white;
            border-radius: 15px;
            padding: 15px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .menu-title {
            font-size: 1.1rem;
            margin: 10px 0;
            font-weight: 600;
            color: #333;
        }
        .menu-description {
            color: #6c757d;
            font-size: 0.9rem;
            margin-bottom: 10px;
        }
        .menu-price {
            font-weight: 600;
            color: #dc3545;
        }
        .quantity-control {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin: 10px 0;
        }
        .quantity-btn {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            border: none;
            background: #007bff;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }
        .favorite-btn {
            border: none;
            background: none;
            color: #dc3545;
            font-size: 1.2rem;
            cursor: pointer;
            padding: 0;
        }
        .section-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin: 20px 0;
            padding-bottom: 10px;
            border-bottom: 2px solid #007bff;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
        <h2 class="section-title">Stock Makanan</h2>
        <div class="row">
            <?php foreach ($food_items as $item): ?>
            <div class="col-md-3">
                <div class="menu-card">
                    <h3 class="menu-title"><?php echo htmlspecialchars($item['name']); ?></h3>
                    <p class="menu-description"><?php echo htmlspecialchars($item['description']); ?></p>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="menu-price">Rp <?php echo number_format($item['price'], 0, ',', '.'); ?></span>
                        <button class="favorite-btn">
                            <i class="bi bi-heart"></i>
                        </button>
                    </div>
                    <div class="quantity-control">
                        <button class="quantity-btn" onclick="updateStock(<?php echo $item['id']; ?>, -1)">-</button>
                        <span id="stock-<?php echo $item['id']; ?>"><?php echo $item['stock']; ?></span>
                        <button class="quantity-btn" onclick="updateStock(<?php echo $item['id']; ?>, 1)">+</button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <h2 class="section-title">Stock Minuman</h2>
        <div class="row">
            <?php foreach ($beverage_items as $item): ?>
            <div class="col-md-3">
                <div class="menu-card">
                    <h3 class="menu-title"><?php echo htmlspecialchars($item['name']); ?></h3>
                    <p class="menu-description"><?php echo htmlspecialchars($item['description']); ?></p>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="menu-price">Rp <?php echo number_format($item['price'], 0, ',', '.'); ?></span>
                        <button class="favorite-btn">
                            <i class="bi bi-heart"></i>
                        </button>
                    </div>
                    <div class="quantity-control">
                        <button class="quantity-btn" onclick="updateStock(<?php echo $item['id']; ?>, -1)">-</button>
                        <span id="stock-<?php echo $item['id']; ?>"><?php echo $item['stock']; ?></span>
                        <button class="quantity-btn" onclick="updateStock(<?php echo $item['id']; ?>, 1)">+</button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function updateStock(productId, change) {
            const stockElement = document.getElementById(`stock-${productId}`);
            const currentStock = parseInt(stockElement.textContent);
            const newStock = currentStock + change;
            
            if (newStock < 0) {
                alert('Stock tidak boleh kurang dari 0');
                return;
            }
            
            fetch('update_stock.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    product_id: productId,
                    stock: newStock
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    stockElement.textContent = newStock;
                } else {
                    alert('Gagal mengupdate stock: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan saat mengupdate stock');
            });
        }

        function toggleFavorite(button) {
            const icon = button.querySelector('i');
            if (icon.classList.contains('bi-heart')) {
                icon.classList.remove('bi-heart');
                icon.classList.add('bi-heart-fill');
            } else {
                icon.classList.remove('bi-heart-fill');
                icon.classList.add('bi-heart');
            }
        }
    </script>
</body>
</html> 