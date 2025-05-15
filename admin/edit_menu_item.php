<?php
// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../login.php");
    exit;
}

// Include database connection
require_once '../config/database.php';

// Include model classes
require_once '../models/MenuItem.php';
require_once '../models/Ingredient.php';

// Create menu item and ingredient models
$menuItemModel = new MenuItem($conn);
$ingredientModel = new Ingredient($conn);

// Variables for error and success messages
$error_msg = '';
$success_msg = '';

// When form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get menu item information
    $menu_item_id = $_POST["menu_item_id"];
    $name = $_POST["name"];
    $category = $_POST["category"];
    $price = $_POST["price"];
    $description = $_POST["description"];

    // Update menu item
    if ($menuItemModel->updateMenuItem($menu_item_id, $name, $category, $price, $description)) {
        $success_msg = "Menu item updated successfully.";
    } else {
        $error_msg = "An error occurred while updating the menu item.";
    }
}

// Check ID parameter
if (isset($_GET["id"]) && !empty(trim($_GET["id"]))) {
    // Get ID parameter from URL
    $menu_item_id = trim($_GET["id"]);

    // Get menu item information
    $menu_item = $menuItemModel->getMenuItemById($menu_item_id);

    if (!$menu_item) {
        // Menu item not found, redirect to menu page
        header("location: menu.php");
        exit();
    }
    
    // Get ingredients for the menu item
    $ingredients = $menuItemModel->getMenuItemIngredients($menu_item_id);
    
    // Get all ingredients (for the modal)
    $all_ingredients = $ingredientModel->getAllIngredients();
} else {
    // ID parameter not in URL, redirect to menu page
    header("location: menu.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Menu Item - Restaurant Management System</title>
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
            <h3>Edit Menu Item</h3>
            <div>
                <span class="mr-3">Welcome, <?php echo htmlspecialchars($_SESSION["username"]); ?></span>
                <a href="../logout.php" class="btn btn-danger btn-sm"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
        
        <!-- Messages -->
        <?php if(isset($success_msg) && !empty($success_msg)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $success_msg; ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>
        
        <?php if(isset($error_msg) && !empty($error_msg)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error_msg; ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Edit Menu Item Information</h5>
            </div>
            <div class="card-body">
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <input type="hidden" name="menu_item_id" value="<?php echo $menu_item['menu_item_id']; ?>">
                    
                    <div class="form-group">
                        <label for="name">Name</label>
                        <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($menu_item['name']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="category">Category</label>
                        <select class="form-control" id="category" name="category">
                            <option value="Main Course" <?php echo ($menu_item['category'] == 'Main Course') ? 'selected' : ''; ?>>Main Course</option>
                            <option value="Side Dish" <?php echo ($menu_item['category'] == 'Side Dish') ? 'selected' : ''; ?>>Side Dish</option>
                            <option value="Salad" <?php echo ($menu_item['category'] == 'Salad') ? 'selected' : ''; ?>>Salad</option>
                            <option value="Dessert" <?php echo ($menu_item['category'] == 'Dessert') ? 'selected' : ''; ?>>Dessert</option>
                            <option value="Beverage" <?php echo ($menu_item['category'] == 'Beverage') ? 'selected' : ''; ?>>Beverage</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="price">Price (₺)</label>
                        <input type="number" step="0.01" class="form-control" id="price" name="price" value="<?php echo $menu_item['price']; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($menu_item['description']); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <a href="menu.php" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">Update</button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Ingredients Card -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Ingredients</h5>
                <button type="button" class="btn btn-success btn-sm" data-toggle="modal" data-target="#addIngredientModal">
                    <i class="fas fa-plus"></i> Add Ingredient
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Ingredient Name</th>
                                <th>Required Quantity</th>
                                <th>Unit</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if (mysqli_num_rows($ingredients) > 0) {
                                while ($ingredient = mysqli_fetch_assoc($ingredients)) {
                                    echo "<tr>";
                                    echo "<td>" . htmlspecialchars($ingredient['ingredient_name']) . "</td>";
                                    echo "<td>" . $ingredient['quantity_required'] . "</td>";
                                    echo "<td>" . htmlspecialchars($ingredient['unit']) . "</td>";
                                    echo "<td>
                                            <a href='remove_ingredient.php?menu_id=" . $menu_item_id . "&ingredient_id=" . $ingredient['ingredient_id'] . "' class='btn btn-danger btn-sm' onclick='return confirm(\"Are you sure you want to remove this ingredient from the menu item?\")'><i class='fas fa-trash'></i></a>
                                          </td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='4' class='text-center'>No ingredients found for this menu item.</td></tr>";
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
                    <h5 class="modal-title" id="addIngredientModalLabel">Add Ingredient</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="add_ingredient_to_menu.php" method="post">
                    <input type="hidden" name="menu_item_id" value="<?php echo $menu_item_id; ?>">
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="ingredient_id">Select Ingredient</label>
                            <select class="form-control" id="ingredient_id" name="ingredient_id" required>
                                <option value="">Select Ingredient</option>
                                <?php 
                                if(mysqli_num_rows($all_ingredients) > 0){
                                    while($ing = mysqli_fetch_assoc($all_ingredients)){
                                        echo "<option value='" . $ing['ingredient_id'] . "'>" . htmlspecialchars($ing['ingredient_name']) . " (" . htmlspecialchars($ing['unit']) . ")</option>";
                                    }
                                }
                                ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="quantity_required">Required Quantity</label>
                            <input type="number" step="0.01" class="form-control" id="quantity_required" name="quantity_required" required>
                            <small class="form-text text-muted">Enter the quantity in the unit specified for the ingredient.</small>
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
    
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html> 