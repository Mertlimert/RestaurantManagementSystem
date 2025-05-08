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
require_once '../models/Tables.php';
require_once '../models/Customer.php';

// Create models
$tableModel = new Tables($conn);
$customerModel = new Customer($conn);

// Variables for error and success messages
$error_msg = '';
$success_msg = '';

// When form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get table information
    $table_id = $_POST["table_id"];
    $number_of_customers = $_POST["number_of_customers"];
    $capacity = $_POST["capacity"];
    $table_status = $_POST["table_status"];
    $customer_id = isset($_POST["customer_id"]) ? $_POST["customer_id"] : null;

    // If customer assignment is requested
    if (!empty($customer_id)) {
        if ($tableModel->assignCustomerToTable($table_id, $customer_id, $number_of_customers)) {
            $success_msg = "Table and customer information updated successfully.";
        } else {
            $error_msg = "An error occurred while assigning customer to table.";
        }
    } 
    // If customer removal is requested (customer_id is empty and status is "available")
    else if ($table_status == "available") {
        if ($tableModel->removeCustomerFromTable($table_id)) {
            // Update table (removeCustomerFromTable function already sets status to available,
            // but this update is necessary to change capacity)
            if ($tableModel->updateTable($table_id, 0, $capacity, 'available')) {
                $success_msg = "Table information updated successfully and customer removed.";
            } else {
                $error_msg = "An error occurred while updating table information.";
            }
        } else {
            $error_msg = "An error occurred while removing customer from table.";
        }
    }
    // Update only table information
    else {
        if ($tableModel->updateTable($table_id, $number_of_customers, $capacity, $table_status)) {
            $success_msg = "Table information updated successfully.";
        } else {
            $error_msg = "An error occurred while updating table information.";
        }
    }
}

// Check ID parameter
if (isset($_GET["id"]) && !empty(trim($_GET["id"]))) {
    // Get ID parameter from URL
    $table_id = trim($_GET["id"]);

    // Get table information with customer details
    $table_data = $tableModel->getTableWithCustomerDetails($table_id);

    if (!$table_data) {
        // Table not found, redirect to tables page
        header("location: tables.php");
        exit();
    }
} else {
    // ID parameter not in URL, redirect to tables page
    header("location: tables.php");
    exit();
}

// Get all customers
$customers = $customerModel->getAllCustomers();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Table - Restaurant Management System</title>
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
        .customer-info {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-top: 15px;
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
        <a href="tables.php" class="active"><i class="fas fa-chair mr-2"></i> Tables</a>
        <a href="menu.php"><i class="fas fa-utensils mr-2"></i> Menu</a>
        <a href="ingredients.php"><i class="fas fa-carrot mr-2"></i> Ingredients</a>
        <a href="orders.php"><i class="fas fa-clipboard-list mr-2"></i> Orders</a>
        <a href="reservations.php"><i class="fas fa-calendar-alt mr-2"></i> Reservations</a>
        <a href="../logout.php"><i class="fas fa-sign-out-alt mr-2"></i> Logout</a>
    </div>
    
    <!-- Main Content -->
    <div class="content col-md-10">
        <div class="top-bar d-flex justify-content-between align-items-center">
            <h3>Edit Table</h3>
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
                <h5 class="mb-0">Edit Table Information</h5>
            </div>
            <div class="card-body">
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . '?id=' . $table_data['table_id']; ?>" method="post">
                    <input type="hidden" name="table_id" value="<?php echo $table_data['table_id']; ?>">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="number_of_customers">Current Number of Customers</label>
                                <input type="number" class="form-control" id="number_of_customers" name="number_of_customers" value="<?php echo $table_data['number_of_customers']; ?>" min="0" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="capacity">Capacity</label>
                                <input type="number" class="form-control" id="capacity" name="capacity" value="<?php echo $table_data['capacity']; ?>" min="1" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="table_status">Status</label>
                                <select class="form-control" id="table_status" name="table_status">
                                    <option value="available" <?php echo ($table_data['table_status'] == 'available') ? 'selected' : ''; ?>>Available</option>
                                    <option value="occupied" <?php echo ($table_data['table_status'] == 'occupied') ? 'selected' : ''; ?>>Occupied</option>
                                    <option value="reserved" <?php echo ($table_data['table_status'] == 'reserved') ? 'selected' : ''; ?>>Reserved</option>
                                </select>
                                <small class="form-text text-muted">Note: If you change the status to "Available", the table will be vacated and the customer link will be removed.</small>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="customer_id">Customer</label>
                                <select class="form-control" id="customer_id" name="customer_id">
                                    <option value="">Select Customer (Optional)</option>
                                    <?php 
                                    mysqli_data_seek($customers, 0);
                                    while ($customer = mysqli_fetch_assoc($customers)): 
                                    ?>
                                        <option value="<?php echo $customer['customer_id']; ?>" <?php echo (isset($table_data['customer_id']) && $table_data['customer_id'] == $customer['customer_id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name'] . ' (' . $customer['phone_number'] . ')'); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                                <small class="form-text text-muted">Note: If you select a customer, the table status will automatically be set to "Occupied".</small>
                            </div>
                            
                            <?php if(isset($table_data['customer_id']) && !empty($table_data['customer_id'])): ?>
                            <div class="customer-info">
                                <h6>Current Customer Information</h6>
                                <p><strong>Name:</strong> <?php echo htmlspecialchars($table_data['first_name'] . ' ' . $table_data['last_name']); ?></p>
                                <p><strong>Phone:</strong> <?php echo htmlspecialchars($table_data['phone_number']); ?></p>
                                <p><strong>Email:</strong> <?php echo htmlspecialchars($table_data['email']); ?></p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="form-group mt-3">
                        <a href="tables.php" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        // Set table status to "occupied" when customer is selected
        document.getElementById('customer_id').addEventListener('change', function() {
            if (this.value) {
                document.getElementById('table_status').value = 'occupied';
            }
        });
        
        // Clear customer selection when status is changed to "available"
        document.getElementById('table_status').addEventListener('change', function() {
            if (this.value === 'available') {
                document.getElementById('customer_id').value = '';
            }
        });
    </script>
</body>
</html> 