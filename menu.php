<?php
session_start();
require_once 'config/database.php';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        try {
            if ($_POST['action'] == 'add' || $_POST['action'] == 'edit') {
                $name = $_POST['name'];
                $price = $_POST['price'];
                $stock = $_POST['stock'];
                $description = $_POST['description'] ?? '';
                $category = $_POST['category'] ?? 'uncategorized';

                // Handle image upload
                $image_path = null;
                if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
                    $target_dir = "uploads/menu/";
                    if (!file_exists($target_dir)) {
                        mkdir($target_dir, 0777, true);
                    }
                    
                    $file_extension = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION));
                    $new_filename = uniqid() . '.' . $file_extension;
                    $target_file = $target_dir . $new_filename;
                    
                    if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                        $image_path = $target_file;
                    }
                }

                if ($_POST['action'] == 'add') {
                    $stmt = $pdo->prepare("INSERT INTO menu (name, price, stock, description, category, image) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$name, $price, $stock, $description, $category, $image_path]);
                    $_SESSION['success'] = "Menu berhasil ditambahkan";
                } else {
                    $id = $_POST['id'];
                    if ($image_path) {
                        $stmt = $pdo->prepare("UPDATE menu SET name = ?, price = ?, stock = ?, description = ?, category = ?, image = ? WHERE id = ?");
                        $stmt->execute([$name, $price, $stock, $description, $category, $image_path, $id]);
                    } else {
                        $stmt = $pdo->prepare("UPDATE menu SET name = ?, price = ?, stock = ?, description = ?, category = ? WHERE id = ?");
                        $stmt->execute([$name, $price, $stock, $description, $category, $id]);
                    }
                    $_SESSION['success'] = "Menu berhasil diperbarui";
                }
            } elseif ($_POST['action'] == 'delete') {
                $id = $_POST['id'];
                
                // Get image path before deleting
                $stmt = $pdo->prepare("SELECT image FROM menu WHERE id = ?");
                $stmt->execute([$id]);
                $image = $stmt->fetchColumn();
                
                // Delete the menu
                $stmt = $pdo->prepare("DELETE FROM menu WHERE id = ?");
                $stmt->execute([$id]);
                
                // Delete image file if exists
                if ($image && file_exists($image)) {
                    unlink($image);
                }
                
                $_SESSION['success'] = "Menu berhasil dihapus";
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error: " . $e->getMessage();
        }
        
        header("Location: menu.php");
        exit();
    }
}

// Get all menu items
$stmt = $pdo->query("SELECT * FROM menu ORDER BY category, name");
$menu_items = $stmt->fetchAll();

// Get distinct categories
$stmt = $pdo->query("SELECT DISTINCT category FROM menu ORDER BY category");
$categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu - Warkop Panjalu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .sidebar {
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            padding: 20px;
            background-color: #f8f9fa;
            border-right: 1px solid #dee2e6;
        }
        .main-content {
            margin-left: 250px;
            padding: 20px;
        }
        .nav-link {
            color: #333;
            padding: 8px 16px;
            margin-bottom: 5px;
            border-radius: 4px;
        }
        .nav-link:hover {
            background-color: #e9ecef;
        }
        .nav-link.active {
            background-color: #0d6efd;
            color: white;
        }
        .nav-link i {
            margin-right: 10px;
        }
        .menu-image {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 8px;
        }
        .menu-card {
            transition: all 0.3s;
        }
        .menu-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar" style="width: 250px;">
        <h3 class="mb-4 text-center">Warkop Panjalu</h3>
        <nav class="nav flex-column">
            <a class="nav-link" href="index.php">
                <i class="bi bi-house-door"></i> Dashboard
            </a>
            <a class="nav-link active" href="menu.php">
                <i class="bi bi-menu-button-wide"></i> Menu
            </a>
            <a class="nav-link" href="karyawan.php">
                <i class="bi bi-people"></i> Karyawan
            </a>
            <a class="nav-link" href="new_order.php">
                <i class="bi bi-cart-plus"></i> Pesanan Baru
            </a>
            <a class="nav-link" href="order_list.php">
                <i class="bi bi-list-check"></i> Pesanan Aktif
            </a>
            <a class="nav-link" href="order_history.php">
                <i class="bi bi-clock-history"></i> Riwayat Pesanan
            </a>
            <a class="nav-link" href="customers.php">
                <i class="bi bi-person-lines-fill"></i> Pelanggan
            </a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Daftar Menu</h2>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addMenuModal">
                <i class="bi bi-plus-lg"></i> Tambah Menu
            </button>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php 
                echo $_SESSION['success'];
                unset($_SESSION['success']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php 
                echo $_SESSION['error'];
                unset($_SESSION['error']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Category filter -->
        <div class="mb-4">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-outline-primary active" onclick="filterCategory('all')">
                            Semua
                        </button>
                        <?php foreach ($categories as $category): ?>
                            <button type="button" class="btn btn-outline-primary" onclick="filterCategory('<?php echo $category; ?>')">
                                <?php echo ucfirst($category); ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control" id="searchMenu" placeholder="Cari menu...">
                    </div>
                </div>
            </div>
        </div>

        <!-- Menu list -->
        <div class="row" id="menuContainer">
            <?php foreach ($menu_items as $item): ?>
                <div class="col-md-4 mb-4 menu-item" data-category="<?php echo htmlspecialchars($item['category']); ?>" 
                     data-name="<?php echo htmlspecialchars(strtolower($item['name'])); ?>">
                    <div class="card menu-card h-100">
                        <?php if ($item['image']): ?>
                            <img src="<?php echo htmlspecialchars($item['image']); ?>" class="card-img-top menu-image" alt="<?php echo htmlspecialchars($item['name']); ?>">
                        <?php endif; ?>
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($item['name']); ?></h5>
                            <p class="card-text">
                                <strong>Harga:</strong> Rp <?php echo number_format($item['price'], 0, ',', '.'); ?><br>
                                <div class="d-flex align-items-center gap-2">
                                    <strong>Stok:</strong> 
                                    <span id="stock-<?php echo $item['id']; ?>"><?php echo $item['stock']; ?></span>
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-outline-secondary" onclick="updateStock(<?php echo $item['id']; ?>, -1)">-</button>
                                        <button class="btn btn-outline-secondary" onclick="updateStock(<?php echo $item['id']; ?>, 1)">+</button>
                                    </div>
                                </div>
                                <strong>Kategori:</strong> <?php echo ucfirst($item['category']); ?><br>
                                <?php if ($item['description']): ?>
                                    <small class="text-muted"><?php echo htmlspecialchars($item['description']); ?></small>
                                <?php endif; ?>
                            </p>
                            <div class="btn-group">
                                <button type="button" class="btn btn-sm btn-outline-primary" 
                                        onclick="editMenu(<?php echo htmlspecialchars(json_encode($item)); ?>)">
                                    <i class="bi bi-pencil"></i> Edit
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger" 
                                        onclick="deleteMenu(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars($item['name']); ?>')">
                                    <i class="bi bi-trash"></i> Hapus
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Add Menu Modal -->
    <div class="modal fade" id="addMenuModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Menu Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="menu.php" method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        
                        <div class="mb-3">
                            <label class="form-label">Nama Menu</label>
                            <input type="text" class="form-control" name="name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Harga</label>
                            <input type="number" class="form-control" name="price" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Stok</label>
                            <input type="number" class="form-control" name="stock" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Kategori</label>
                            <input type="text" class="form-control" name="category" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Deskripsi</label>
                            <textarea class="form-control" name="description" rows="3"></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Gambar</label>
                            <input type="file" class="form-control" name="image" accept="image/*">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Menu Modal -->
    <div class="modal fade" id="editMenuModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Menu</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="menu.php" method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" id="edit_id">
                        
                        <div class="mb-3">
                            <label class="form-label">Nama Menu</label>
                            <input type="text" class="form-control" name="name" id="edit_name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Harga</label>
                            <input type="number" class="form-control" name="price" id="edit_price" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Stok</label>
                            <input type="number" class="form-control" name="stock" id="edit_stock" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Kategori</label>
                            <input type="text" class="form-control" name="category" id="edit_category" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Deskripsi</label>
                            <textarea class="form-control" name="description" id="edit_description" rows="3"></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Gambar Baru</label>
                            <input type="file" class="form-control" name="image" accept="image/*">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteMenuModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Konfirmasi Hapus</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Anda yakin ingin menghapus menu <strong id="delete_menu_name"></strong>?</p>
                </div>
                <div class="modal-footer">
                    <form action="menu.php" method="POST">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" id="delete_id">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-danger">Hapus</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function editMenu(menu) {
        document.getElementById('edit_id').value = menu.id;
        document.getElementById('edit_name').value = menu.name;
        document.getElementById('edit_price').value = menu.price;
        document.getElementById('edit_stock').value = menu.stock;
        document.getElementById('edit_category').value = menu.category;
        document.getElementById('edit_description').value = menu.description;
        
        new bootstrap.Modal(document.getElementById('editMenuModal')).show();
    }

    function deleteMenu(id, name) {
        document.getElementById('delete_id').value = id;
        document.getElementById('delete_menu_name').textContent = name;
        
        new bootstrap.Modal(document.getElementById('deleteMenuModal')).show();
    }

    function filterCategory(category) {
        const menuItems = document.querySelectorAll('.menu-item');
        const buttons = document.querySelectorAll('.btn-group .btn');
        
        buttons.forEach(btn => btn.classList.remove('active'));
        event.target.classList.add('active');
        
        menuItems.forEach(item => {
            if (category === 'all' || item.dataset.category === category) {
                item.style.display = '';
            } else {
                item.style.display = 'none';
            }
        });
    }

    // Search functionality
    document.getElementById('searchMenu').addEventListener('input', function(e) {
        const searchTerm = e.target.value.toLowerCase();
        const menuItems = document.querySelectorAll('.menu-item');
        
        menuItems.forEach(item => {
            const name = item.dataset.name;
            if (name.includes(searchTerm)) {
                item.style.display = '';
            } else {
                item.style.display = 'none';
            }
        });
    });

    // Quick stock update
    function updateStock(menuId, change) {
        fetch('update_stock.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                menu_id: menuId,
                change: change
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const stockElement = document.getElementById('stock-' + menuId);
                stockElement.textContent = data.new_stock;
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            alert('Error updating stock: ' + error);
        });
    }
    </script>
</body>
</html> 