<?php
// Include database connection and model
require_once '../config/database.php';
require_once '../models/Employee.php';

// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../login.php");
    exit;
}

// Get database connection
$db = $conn; // Use the existing connection variable from database.php

// Create employee object
$employee = new Employee($db);

// Get employee id from URL
$employee_id = isset($_GET['id']) ? $_GET['id'] : null;

// Check if employee exists
if ($employee_id) {
    $employeeData = $employee->getEmployeeById($employee_id);
    if (!$employeeData) {
        header("Location: employees.php");
        exit;
    }
}

// Get all shift types
$shiftTypes = $employee->getAllShiftTypes();

// Process form submission for adding shift
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_shift'])) {
    $shift_type_id = $_POST['shift_type_id'];
    $shift_date = $_POST['shift_date'];
    
    if ($employee->assignShift($employee_id, $shift_type_id, $shift_date)) {
        $success_message = "Shift assigned successfully.";
    } else {
        $error_message = "Failed to assign shift.";
    }
}

// Process shift deletion
if (isset($_GET['delete_shift'])) {
    $shift_id = $_GET['delete_shift'];
    
    // Create a query to delete the shift
    $query = "DELETE FROM EmployeeShifts WHERE shift_id = ? AND employee_id = ?";
    $stmt = mysqli_prepare($db, $query);
    mysqli_stmt_bind_param($stmt, "ii", $shift_id, $employee_id);
    
    if (mysqli_stmt_execute($stmt)) {
        $success_message = "Shift deleted successfully.";
    } else {
        $error_message = "Failed to delete shift.";
    }
}

// Get employee shifts
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d');
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d', strtotime('+30 days'));
$shifts = $employee->getEmployeeShifts($employee_id, $startDate, $endDate);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Shifts - Restaurant Management System</title>
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
        .float-end {
            float: right;
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
            <h3>Employee Shifts</h3>
            <div>
                <span class="mr-3">Welcome, <?php echo htmlspecialchars($_SESSION["username"]); ?></span>
                <a href="../logout.php" class="btn btn-danger btn-sm"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
        
        <!-- Messages -->
        <?php if(isset($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $success_message; ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>
        
        <?php if(isset($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error_message; ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <?php echo htmlspecialchars($employeeData['first_name'] . ' ' . $employeeData['last_name']); ?> - Shifts
                </h5>
                <button type="button" class="btn btn-success btn-sm" data-toggle="modal" data-target="#addShiftModal">
                    <i class="fas fa-plus"></i> Add Shift
                </button>
            </div>
            <div class="card-body">
                <form method="GET" action="" class="mb-4 row">
                    <input type="hidden" name="id" value="<?php echo $employee_id; ?>">
                    <div class="col-md-4">
                        <label for="start_date">Start Date:</label>
                        <input type="date" id="start_date" name="start_date" value="<?php echo $startDate; ?>" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label for="end_date">End Date:</label>
                        <input type="date" id="end_date" name="end_date" value="<?php echo $endDate; ?>" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label>&nbsp;</label>
                        <button type="submit" class="btn btn-primary form-control">Filter</button>
                    </div>
                </form>
                
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Shift ID</th>
                                <th>Shift Name</th>
                                <th>Date</th>
                                <th>Start Time</th>
                                <th>End Time</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if (mysqli_num_rows($shifts) > 0) {
                                while ($shift = mysqli_fetch_assoc($shifts)) {
                                    echo "<tr>";
                                    echo "<td>" . $shift['shift_id'] . "</td>";
                                    echo "<td>" . $shift['shift_name'] . "</td>";
                                    echo "<td>" . date('d.m.Y', strtotime($shift['shift_date'])) . "</td>";
                                    echo "<td>" . date('H:i', strtotime($shift['start_time'])) . "</td>";
                                    echo "<td>" . date('H:i', strtotime($shift['end_time'])) . "</td>";
                                    echo "<td>
                                            <a href='employee_shifts.php?id=" . $employee_id . "&delete_shift=" . $shift['shift_id'] . "' class='btn btn-danger btn-sm' onclick='return confirm(\"Are you sure you want to delete this shift?\")'>
                                                <i class='fas fa-trash'></i>
                                            </a>
                                          </td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='6' class='text-center'>No shifts assigned for this period.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

<!-- Add Shift Modal -->
<div class="modal fade" id="addShiftModal" tabindex="-1" role="dialog" aria-labelledby="addShiftModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addShiftModalLabel">Assign New Shift</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="shift_type_id">Shift Type:</label>
                        <select class="form-control" id="shift_type_id" name="shift_type_id" required>
                            <option value="">Select Shift Type</option>
                            <?php
                            if (mysqli_num_rows($shiftTypes) > 0) {
                                while ($type = mysqli_fetch_assoc($shiftTypes)) {
                                    echo "<option value='" . $type['shift_type_id'] . "'>" . $type['shift_name'] . " (" . 
                                        date('H:i', strtotime($type['start_time'])) . " - " . 
                                        date('H:i', strtotime($type['end_time'])) . ")</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="shift_date">Shift Date:</label>
                        <input type="date" class="form-control" id="shift_date" name="shift_date" required min="<?php echo date('Y-m-d'); ?>">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" name="add_shift" class="btn btn-primary">Assign Shift</button>
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