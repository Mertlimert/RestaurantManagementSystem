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
require_once '../models/Employee.php';

// Create employee model
$employeeModel = new Employee($conn);

// Variables for error and success messages
$error_msg = '';
$success_msg = '';

// When form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get employee information
    $employee_id = $_POST["employee_id"];
    $first_name = $_POST["first_name"];
    $last_name = $_POST["last_name"];
    $position_id = $_POST["position_id"];
    $phone_number = $_POST["phone_number"];
    $email = $_POST["email"];
    $hourly_rate = $_POST["hourly_rate"];
    $hire_date = $_POST["hire_date"];

    // Update employee
    if ($employeeModel->updateEmployee($employee_id, $first_name, $last_name, $position_id, $phone_number, $email, $hourly_rate, $hire_date)) {
        $success_msg = "Employee information updated successfully.";
    } else {
        $error_msg = "An error occurred while updating employee information.";
    }
}

// Check ID parameter
if (isset($_GET["id"]) && !empty(trim($_GET["id"]))) {
    // Get ID parameter from URL
    $employee_id = trim($_GET["id"]);

    // Get employee information
    $employee_data = $employeeModel->getEmployeeById($employee_id);

    if (!$employee_data) {
        // Employee not found, redirect to employees page
        header("location: employees.php");
        exit();
    }
} else {
    // ID parameter not in URL, redirect to employees page
    header("location: employees.php");
    exit();
}

// Get all positions
$positions = $employeeModel->getAllPositions();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Employee - Restaurant Management System</title>
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
        <a href="employees.php" class="active"><i class="fas fa-user-tie mr-2"></i> Employees</a>
        <a href="shift_types.php"><i class="fas fa-clock mr-2"></i> Shift Types</a>
        <a href="tables.php"><i class="fas fa-chair mr-2"></i> Tables</a>
        <a href="menu.php"><i class="fas fa-utensils mr-2"></i> Menu</a>
        <a href="ingredients.php"><i class="fas fa-carrot mr-2"></i> Ingredients</a>
        <a href="orders.php"><i class="fas fa-clipboard-list mr-2"></i> Orders</a>
        <a href="reservations.php"><i class="fas fa-calendar-alt mr-2"></i> Reservations</a>
        <a href="../logout.php"><i class="fas fa-sign-out-alt mr-2"></i> Logout</a>
    </div>
    
    <!-- Main Content -->
    <div class="content col-md-10">
        <div class="top-bar d-flex justify-content-between align-items-center">
            <h3>Edit Employee</h3>
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
                <h5 class="mb-0">Edit Employee Information</h5>
            </div>
            <div class="card-body">
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <input type="hidden" name="employee_id" value="<?php echo $employee_data['employee_id']; ?>">
                    
                    <div class="form-group">
                        <label for="first_name">First Name</label>
                        <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo $employee_data['first_name']; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="last_name">Last Name</label>
                        <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo $employee_data['last_name']; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="position_id">Position</label>
                        <select class="form-control" id="position_id" name="position_id" required>
                            <?php
                            while ($position = mysqli_fetch_assoc($positions)) {
                                $selected = ($position['position_id'] == $employee_data['position_id']) ? 'selected' : '';
                                echo "<option value='" . $position['position_id'] . "' " . $selected . ">" . $position['title'] . "</option>";
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone_number">Phone</label>
                        <input type="text" class="form-control" id="phone_number" name="phone_number" value="<?php echo $employee_data['phone_number']; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo $employee_data['email']; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="hourly_rate">Hourly Rate (₺)</label>
                        <input type="number" step="0.1" class="form-control" id="hourly_rate" name="hourly_rate" value="<?php echo $employee_data['hourly_rate']; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="hire_date">Hire Date</label>
                        <input type="date" class="form-control" id="hire_date" name="hire_date" value="<?php echo $employee_data['hire_date']; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <a href="employees.php" class="btn btn-secondary">Cancel</a>
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