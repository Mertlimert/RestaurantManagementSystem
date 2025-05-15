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

// Include model class
require_once '../models/Ingredient.php';

// Create ingredient model
$ingredientModel = new Ingredient($conn);

// Variables for error and success messages
$error_msg = '';
$success_msg = '';

// When form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get ingredient information
    $ingredient_id = $_POST["ingredient_id"];
    $ingredient_name = $_POST["ingredient_name"];
    $unit = $_POST["unit"];
    $stock_quantity = $_POST["stock_quantity"];
    $allergen = isset($_POST["allergen"]) ? $_POST["allergen"] : null;

    // Update ingredient
    if ($ingredientModel->updateIngredient($ingredient_id, $ingredient_name, $unit, $stock_quantity, $allergen)) {
        $success_msg = "Ingredient information updated successfully.";
    } else {
        $error_msg = "An error occurred while updating ingredient information.";
    }
}

// Check ID parameter
if (isset($_GET["id"]) && !empty(trim($_GET["id"]))) {
    // Get ID parameter from URL
    $ingredient_id = trim($_GET["id"]);

    // Get ingredient information
    $ingredient = $ingredientModel->getIngredientById($ingredient_id);

    if (!$ingredient) {
        // Ingredient not found, redirect to ingredients page
        header("location: ingredients.php");
        exit();
    }
} else {
    // ID parameter not in URL, redirect to ingredients page
    header("location: ingredients.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Ingredient - Restaurant Management System</title>
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
        <a href="menu.php"><i class="fas fa-utensils mr-2"></i> Menu</a>
        <a href="ingredients.php" class="active"><i class="fas fa-carrot mr-2"></i> Ingredients</a>
        <a href="orders.php"><i class="fas fa-clipboard-list mr-2"></i> Orders</a>
        <a href="reservations.php"><i class="fas fa-calendar-alt mr-2"></i> Reservations</a>
        <a href="../logout.php"><i class="fas fa-sign-out-alt mr-2"></i> Logout</a>
    </div>
    
    <!-- Main Content -->
    <div class="content col-md-10">
        <div class="top-bar d-flex justify-content-between align-items-center">
            <h3>Edit Ingredient</h3>
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
                <h5 class="mb-0">Edit Ingredient Information</h5>
            </div>
            <div class="card-body">
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <input type="hidden" name="ingredient_id" value="<?php echo $ingredient['ingredient_id']; ?>">
                    
                    <div class="form-group">
                        <label for="ingredient_name">Ingredient Name</label>
                        <input type="text" class="form-control" id="ingredient_name" name="ingredient_name" value="<?php echo htmlspecialchars($ingredient['ingredient_name']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="unit">Unit</label>
                        <select class="form-control" id="unit" name="unit" required>
                            <option value="kg" <?php echo ($ingredient['unit'] == 'kg') ? 'selected' : ''; ?>>Kilogram (kg)</option>
                            <option value="g" <?php echo ($ingredient['unit'] == 'g') ? 'selected' : ''; ?>>Gram (g)</option>
                            <option value="litre" <?php echo ($ingredient['unit'] == 'litre') ? 'selected' : ''; ?>>Liter (lt)</option>
                            <option value="ml" <?php echo ($ingredient['unit'] == 'ml') ? 'selected' : ''; ?>>Milliliter (ml)</option>
                            <option value="adet" <?php echo ($ingredient['unit'] == 'adet') ? 'selected' : ''; ?>>Piece(s)</option>
                            <option value="paket" <?php echo ($ingredient['unit'] == 'paket') ? 'selected' : ''; ?>>Package</option>
                            <option value="porsiyon" <?php echo ($ingredient['unit'] == 'porsiyon') ? 'selected' : ''; ?>>Portion</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="stock_quantity">Stock Quantity</label>
                        <input type="number" step="0.01" class="form-control" id="stock_quantity" name="stock_quantity" value="<?php echo $ingredient['stock_quantity']; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="allergen">Allergen (if any)</label>
                        <select class="form-control" id="allergen" name="allergen">
                            <option value="" <?php echo (empty($ingredient['allergen'])) ? 'selected' : ''; ?>>No Allergen</option>
                            <option value="Gluten" <?php echo ($ingredient['allergen'] == 'Gluten') ? 'selected' : ''; ?>>Gluten</option>
                            <option value="Dairy" <?php echo ($ingredient['allergen'] == 'Süt') ? 'selected' : ''; ?>>Dairy</option>
                            <option value="Egg" <?php echo ($ingredient['allergen'] == 'Yumurta') ? 'selected' : ''; ?>>Egg</option>
                            <option value="Peanuts" <?php echo ($ingredient['allergen'] == 'Fıstık') ? 'selected' : ''; ?>>Peanuts</option>
                            <option value="Tree Nuts" <?php echo ($ingredient['allergen'] == 'Kabuklu Yemiş') ? 'selected' : ''; ?>>Tree Nuts</option>
                            <option value="Soy" <?php echo ($ingredient['allergen'] == 'Soya') ? 'selected' : ''; ?>>Soy</option>
                            <option value="Fish" <?php echo ($ingredient['allergen'] == 'Balık') ? 'selected' : ''; ?>>Fish</option>
                            <option value="Shellfish" <?php echo ($ingredient['allergen'] == 'Kabuklu Deniz Ürünü') ? 'selected' : ''; ?>>Shellfish</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <a href="ingredients.php" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">Update</button>
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