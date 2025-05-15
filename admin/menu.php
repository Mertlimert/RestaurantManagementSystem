<?php
// Oturum başlat
session_start();

// Kullanıcı giriş yapmış mı kontrol et
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../login.php");
    exit;
}

// Veritabanı bağlantısını dahil et
require_once '../config/database.php';

// Model sınıflarını dahil et
require_once '../models/MenuItem.php';
require_once '../models/Ingredient.php';

// Menü modelleri oluştur
$menuItemModel = new MenuItem($conn);
$ingredientModel = new Ingredient($conn);

// Menü öğesi silme işlemi
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $menu_item_id = $_GET['delete'];
    if ($menuItemModel->deleteMenuItem($menu_item_id)) {
        $success_msg = "Menu item deleted successfully.";
    } else {
        $error_msg = "Menu item could not be deleted.";
    }
}

// Oturum mesajlarını kontrol et
if (isset($_SESSION['success_msg'])) {
    $success_msg = $_SESSION['success_msg'];
    unset($_SESSION['success_msg']);
}

if (isset($_SESSION['error_msg'])) {
    $error_msg = $_SESSION['error_msg'];
    unset($_SESSION['error_msg']);
}

// Tüm menü öğelerini getir
$menuItems = $menuItemModel->getAllMenuItems();

// Kategorilere göre menü öğelerini grupla
$categories = array();
while ($item = mysqli_fetch_assoc($menuItems)) {
    if (!isset($categories[$item['category']])) {
        $categories[$item['category']] = array();
    }
    $categories[$item['category']][] = $item;
}

// Kategori bilgilerini yeniden oluştur
mysqli_data_seek($menuItems, 0);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu Management - Restaurant Management System</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
        }
        .sidebar {
            background-color: #343a40;
            color: white;
            height: 100vh;
            position: fixed;
            padding-top: 20px;
        }
        .sidebar a {
            color: #fff;
            padding: 10px 15px;
            display: block;
            text-decoration: none;
        }
        .sidebar a:hover {
            background-color: #495057;
        }
        .sidebar .active {
            background-color: #28a745;
        }
        .content {
            margin-left: 250px;
            padding: 20px;
        }
        .top-bar {
            background-color: white;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .card {
            margin-bottom: 20px;
            border: none;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .btn-add {
            background-color: #28a745;
            color: white;
        }
        .category-title {
            background-color: #f8f9fa;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 5px;
            font-weight: bold;
        }
        .menu-item-card {
            height: 100%;
        }
        .price {
            font-weight: bold;
            color: #28a745;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar col-md-2">
        <h4 class="text-center mb-4">Admin Panel</h4>
        <a href="index.php"><i class="fas fa-tachometer-alt mr-2"></i> Dashboard</a>
        <a href="customers.php"><i class="fas fa-users mr-2"></i> Customers</a>
        <a href="employees.php"><i class="fas fa-user-tie mr-2"></i> Employees</a>
        <a href="shift_types.php"><i class="fas fa-clock mr-2"></i> Shift Types</a>
        <a href="tables.php"><i class="fas fa-chair mr-2"></i> Tables</a>
        <a href="menu.php" class="active"><i class="fas fa-utensils mr-2"></i> Menu</a>
        <a href="ingredients.php"><i class="fas fa-carrot mr-2"></i> Ingredients</a>
        <a href="orders.php"><i class="fas fa-clipboard-list mr-2"></i> Orders</a>
        <a href="reservations.php"><i class="fas fa-calendar-alt mr-2"></i> Reservations</a>
        <a href="../logout.php"><i class="fas fa-sign-out-alt mr-2"></i> Logout</a>
    </div>
    
    <!-- Main Content -->
    <div class="content col-md-10">
        <div class="top-bar d-flex justify-content-between align-items-center">
            <h3>Menu Management</h3>
            <div>
                <span class="mr-3">Welcome, <?php echo htmlspecialchars($_SESSION["username"]); ?></span>
                <a href="../logout.php" class="btn btn-danger btn-sm"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
        
        <!-- Messages -->
        <?php if(isset($success_msg)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $success_msg; ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>
        
        <?php if(isset($error_msg)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error_msg; ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Menu Items</h5>
                <div>
                    <button type="button" class="btn btn-primary btn-sm mr-2" data-toggle="modal" data-target="#viewMenuModal">
                        <i class="fas fa-list"></i> View List
                    </button>
                    <button type="button" class="btn btn-add btn-sm" data-toggle="modal" data-target="#addMenuItemModal">
                        <i class="fas fa-plus"></i> Add New Menu Item
                    </button>
                </div>
            </div>
            <div class="card-body">
                <!-- Kategorilere göre menü öğeleri -->
                <?php foreach ($categories as $category => $items): ?>
                    <div class="category-title">
                        <?php echo htmlspecialchars($category); ?>
                    </div>
                    <div class="row mb-4">
                        <?php foreach ($items as $item): ?>
                            <div class="col-md-4 mb-3">
                                <div class="card menu-item-card">
                                    <div class="card-body">
                                        <h5 class="card-title"><?php echo htmlspecialchars($item['name']); ?></h5>
                                        <p class="card-text"><?php echo htmlspecialchars($item['description']); ?></p>
                                        <p class="price"><?php echo number_format($item['price'], 2); ?> ₺</p>
                                        <div class="btn-group">
                                            <a href="edit_menu_item.php?id=<?php echo $item['menu_item_id']; ?>" class="btn btn-primary btn-sm">
                                                <i class="fas fa-edit"></i> Edit
                                            </a>
                                            <a href="view_menu_item.php?id=<?php echo $item['menu_item_id']; ?>" class="btn btn-info btn-sm">
                                                <i class="fas fa-info-circle"></i> Details
                                            </a>
                                            <a href="menu.php?delete=<?php echo $item['menu_item_id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this menu item?')">
                                                <i class="fas fa-trash"></i> Delete
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <!-- Add Menu Item Modal -->
    <div class="modal fade" id="addMenuItemModal" tabindex="-1" role="dialog" aria-labelledby="addMenuItemModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addMenuItemModalLabel">Add New Menu Item</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="add_menu_item.php" method="post">
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="name">Name</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="form-group">
                            <label for="category">Category</label>
                            <select class="form-control" id="category" name="category">
                                <option value="Main Course">Main Course</option>
                                <option value="Side Dish">Side Dish</option>
                                <option value="Salad">Salad</option>
                                <option value="Dessert">Dessert</option>
                                <option value="Beverage">Beverage</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="price">Price (₺)</label>
                            <input type="number" step="0.01" class="form-control" id="price" name="price" required>
                        </div>
                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Menü Listesi Görüntüleme Modal -->
    <div class="modal fade" id="viewMenuModal" tabindex="-1" role="dialog" aria-labelledby="viewMenuModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewMenuModalLabel">View Menu List</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="list-group">
                        <?php 
                        // Kategorilere göre listele
                        foreach ($categories as $category => $items): 
                        ?>
                            <h5 class="mt-3"><?php echo htmlspecialchars($category); ?></h5>
                            <table class="table table-sm table-bordered">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Price</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php if (count($items) > 0): ?>
                                    <?php foreach ($items as $item): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($item['name']); ?></td>
                                            <td><?php echo number_format($item['price'], 2); ?> ₺</td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="2" class="text-center">No items found in this category.</td></tr>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html> 