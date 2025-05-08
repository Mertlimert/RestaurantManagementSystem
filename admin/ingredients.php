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

// Model sınıfını dahil et
require_once '../models/Ingredient.php';

// Malzeme modeli oluştur
$ingredientModel = new Ingredient($conn);

// Malzeme silme işlemi
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $ingredient_id = $_GET['delete'];
    if ($ingredientModel->deleteIngredient($ingredient_id)) {
        $success_msg = "Ingredient deleted successfully.";
    } else {
        $error_msg = "Ingredient could not be deleted. It might be used in a menu item.";
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

// Tüm malzemeleri getir
$ingredients = $ingredientModel->getAllIngredients();

// Stok seviyesi düşük malzemeleri getir
$low_stock_ingredients = $ingredientModel->getLowStockIngredients(10);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ingredient Management - Restaurant Management System</title>
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
        .low-stock {
            color: #dc3545;
            font-weight: bold;
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
        <a href="tables.php"><i class="fas fa-chair mr-2"></i> Tables</a>
        <a href="menu.php"><i class="fas fa-utensils mr-2"></i> Menu</a>
        <a href="ingredients.php" class="active"><i class="fas fa-carrot mr-2"></i> Ingredients</a>
        <a href="orders.php"><i class="fas fa-clipboard-list mr-2"></i> Orders</a>
        <a href="reservations.php"><i class="fas fa-calendar-alt mr-2"></i> Reservations</a>
        <a href="../logout.php"><i class="fas fa-sign-out-alt mr-2"></i> Logout</a>
    </div>
    
    <!-- Main Content -->
    <div class="content col-md-10">
        <div class="top-bar d-flex justify-content-between align-items-center">
            <h3>Ingredient Management</h3>
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
        
        <?php if(mysqli_num_rows($low_stock_ingredients) > 0): ?>
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <h5><i class="fas fa-exclamation-triangle"></i> Stock Alert</h5>
                <p>The stock quantity for the following ingredients is low:</p>
                <ul>
                    <?php while($item = mysqli_fetch_assoc($low_stock_ingredients)): ?>
                        <li>
                            <strong><?php echo htmlspecialchars($item['ingredient_name']); ?></strong>: 
                            <?php echo $item['stock_quantity']; ?> <?php echo htmlspecialchars($item['unit']); ?>
                        </li>
                    <?php endwhile; ?>
                </ul>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Ingredient List</h5>
                <button type="button" class="btn btn-add btn-sm" data-toggle="modal" data-target="#addIngredientModal">
                    <i class="fas fa-plus"></i> Add New Ingredient
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Ingredient Name</th>
                                <th>Unit</th>
                                <th>Stock Quantity</th>
                                <th>Allergen</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if (mysqli_num_rows($ingredients) > 0) {
                                while ($ingredient = mysqli_fetch_assoc($ingredients)) {
                                    $low_stock_class = ($ingredient['stock_quantity'] < 10) ? 'low-stock' : '';
                                    
                                    echo "<tr>";
                                    echo "<td>" . $ingredient['ingredient_id'] . "</td>";
                                    echo "<td>" . htmlspecialchars($ingredient['ingredient_name']) . "</td>";
                                    echo "<td>" . htmlspecialchars($ingredient['unit']) . "</td>";
                                    echo "<td class='" . $low_stock_class . "'>" . $ingredient['stock_quantity'] . "</td>";
                                    echo "<td>" . (empty($ingredient['allergen']) ? '-' : htmlspecialchars($ingredient['allergen'])) . "</td>";
                                    echo "<td>
                                            <a href='edit_ingredient.php?id=" . $ingredient['ingredient_id'] . "' class='btn btn-primary btn-sm'><i class='fas fa-edit'></i></a>
                                            <a href='ingredients.php?delete=" . $ingredient['ingredient_id'] . "' class='btn btn-danger btn-sm' onclick='return confirm(\"Are you sure you want to delete this ingredient?\")'><i class='fas fa-trash'></i></a>
                                            <button type='button' class='btn btn-success btn-sm' data-toggle='modal' data-target='#stockModal' 
                                                data-id='" . $ingredient['ingredient_id'] . "' 
                                                data-name='" . htmlspecialchars($ingredient['ingredient_name']) . "' 
                                                data-unit='" . htmlspecialchars($ingredient['unit']) . "'>
                                                <i class='fas fa-plus-circle'></i> Add Stock
                                            </button>
                                          </td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='6' class='text-center'>No ingredients found.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add Ingredient Modal -->
    <div class="modal fade" id="addIngredientModal" tabindex="-1" role="dialog" aria-labelledby="addIngredientModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addIngredientModalLabel">Add New Ingredient</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="add_ingredient.php" method="post">
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="ingredient_name">Ingredient Name</label>
                            <input type="text" class="form-control" id="ingredient_name" name="ingredient_name" required>
                        </div>
                        <div class="form-group">
                            <label for="unit">Unit</label>
                            <select class="form-control" id="unit" name="unit" required>
                                <option value="kg">Kilogram (kg)</option>
                                <option value="g">Gram (g)</option>
                                <option value="litre">Liter (lt)</option>
                                <option value="ml">Milliliter (ml)</option>
                                <option value="adet">Piece(s)</option>
                                <option value="paket">Package</option>
                                <option value="porsiyon">Portion</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="stock_quantity">Initial Stock Quantity</label>
                            <input type="number" step="0.01" class="form-control" id="stock_quantity" name="stock_quantity" value="0" required>
                        </div>
                        <div class="form-group">
                            <label for="allergen">Allergen Information (Optional)</label>
                            <input type="text" class="form-control" id="allergen" name="allergen">
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
    
    <!-- Update Stock Modal -->
    <div class="modal fade" id="stockModal" tabindex="-1" role="dialog" aria-labelledby="stockModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="stockModalLabel">Update Stock</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="edit_ingredient.php" method="post"> 
                    <div class="modal-body">
                        <input type="hidden" name="ingredient_id" id="stock_ingredient_id">
                        <p><strong>Ingredient Name:</strong> <span id="stock_ingredient_name"></span></p>
                        <p><strong>Current Stock:</strong> <span id="current_stock"></span> <span id="stock_unit"></span></p>
                        <div class="form-group">
                            <label for="quantity_to_add">Quantity to Add</label>
                            <input type="number" step="0.01" class="form-control" id="quantity_to_add" name="quantity_to_add" required>
                            <small>Use negative numbers to decrease stock.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" name="update_stock" class="btn btn-success">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        // Stok ekleme modalı için verileri yükleme
        $('#stockModal').on('show.bs.modal', function (event) {
            var button = $(event.relatedTarget);
            var id = button.data('id');
            var name = button.data('name');
            var unit = button.data('unit');
            
            var modal = $(this);
            modal.find('#stock_ingredient_id').val(id);
            modal.find('#stock_ingredient_name').text(name);
            modal.find('#stock_unit').text(unit);
        });
    </script>
</body>
</html> 