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

// Process shift type deletion
if (isset($_GET['delete'])) {
    $shift_type_id = $_GET['delete'];
    
    // First check if this shift type is used in any employee shifts
    $query = "SELECT COUNT(*) as count FROM EmployeeShifts WHERE shift_type_id = ?";
    $stmt = mysqli_prepare($db, $query);
    mysqli_stmt_bind_param($stmt, "i", $shift_type_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    
    if ($row['count'] > 0) {
        $error_message = "Cannot delete shift type because it is assigned to employees. Remove those assignments first.";
    } else {
        // Delete the shift type
        $query = "DELETE FROM ShiftTypes WHERE shift_type_id = ?";
        $stmt = mysqli_prepare($db, $query);
        mysqli_stmt_bind_param($stmt, "i", $shift_type_id);
        
        if (mysqli_stmt_execute($stmt)) {
            $success_message = "Shift type deleted successfully.";
        } else {
            $error_message = "Failed to delete shift type.";
        }
    }
}

// Process form submission for adding shift type
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_shift_type'])) {
    $shift_name = $_POST['shift_name'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $description = $_POST['description'];
    
    if ($employee->addShiftType($shift_name, $start_time, $end_time, $description)) {
        $success_message = "Shift type added successfully.";
    } else {
        $error_message = "Failed to add shift type.";
    }
}

// Process form submission for editing shift type
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_shift_type'])) {
    $shift_type_id = $_POST['shift_type_id'];
    $shift_name = $_POST['shift_name'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $description = $_POST['description'];
    
    $query = "UPDATE ShiftTypes SET shift_name = ?, start_time = ?, end_time = ?, description = ? WHERE shift_type_id = ?";
    $stmt = mysqli_prepare($db, $query);
    mysqli_stmt_bind_param($stmt, "ssssi", $shift_name, $start_time, $end_time, $description, $shift_type_id);
    
    if (mysqli_stmt_execute($stmt)) {
        $success_message = "Shift type updated successfully.";
    } else {
        $error_message = "Failed to update shift type.";
    }
}

// Get all shift types
$shiftTypes = $employee->getAllShiftTypes();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shift Types - Restaurant Management System</title>
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
        <a href="employees.php"><i class="fas fa-user-tie mr-2"></i> Employees</a>
        <a href="shift_types.php" class="active"><i class="fas fa-clock mr-2"></i> Shift Types</a>
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
            <h3>Shift Types</h3>
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
                <h5 class="mb-0">Shift Types</h5>
                <button type="button" class="btn btn-success btn-sm" data-toggle="modal" data-target="#addShiftTypeModal">
                    <i class="fas fa-plus"></i> Add New Shift Type
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Shift Name</th>
                                <th>Start Time</th>
                                <th>End Time</th>
                                <th>Description</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if (mysqli_num_rows($shiftTypes) > 0) {
                                while ($type = mysqli_fetch_assoc($shiftTypes)) {
                                    echo "<tr>";
                                    echo "<td>" . $type['shift_type_id'] . "</td>";
                                    echo "<td>" . $type['shift_name'] . "</td>";
                                    echo "<td>" . date('H:i', strtotime($type['start_time'])) . "</td>";
                                    echo "<td>" . date('H:i', strtotime($type['end_time'])) . "</td>";
                                    echo "<td>" . $type['description'] . "</td>";
                                    echo "<td>
                                            <button type='button' class='btn btn-primary btn-sm' data-toggle='modal' data-target='#editShiftTypeModal' 
                                                data-id='" . $type['shift_type_id'] . "' 
                                                data-name='" . $type['shift_name'] . "' 
                                                data-start='" . $type['start_time'] . "' 
                                                data-end='" . $type['end_time'] . "' 
                                                data-desc='" . htmlspecialchars($type['description'], ENT_QUOTES) . "'>
                                                <i class='fas fa-edit'></i>
                                            </button>
                                            <a href='shift_types.php?delete=" . $type['shift_type_id'] . "' class='btn btn-danger btn-sm' onclick='return confirm(\"Are you sure you want to delete this shift type?\")'>
                                                <i class='fas fa-trash'></i>
                                            </a>
                                          </td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='6' class='text-center'>No shift types found.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

<!-- Add Shift Type Modal -->
<div class="modal fade" id="addShiftTypeModal" tabindex="-1" role="dialog" aria-labelledby="addShiftTypeModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addShiftTypeModalLabel">Add New Shift Type</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="shift_name">Shift Name:</label>
                        <input type="text" class="form-control" id="shift_name" name="shift_name" required>
                    </div>
                    <div class="form-group">
                        <label for="start_time">Start Time:</label>
                        <input type="time" class="form-control" id="start_time" name="start_time" required>
                    </div>
                    <div class="form-group">
                        <label for="end_time">End Time:</label>
                        <input type="time" class="form-control" id="end_time" name="end_time" required>
                    </div>
                    <div class="form-group">
                        <label for="description">Description:</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" name="add_shift_type" class="btn btn-primary">Add Shift Type</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Shift Type Modal -->
<div class="modal fade" id="editShiftTypeModal" tabindex="-1" role="dialog" aria-labelledby="editShiftTypeModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editShiftTypeModalLabel">Edit Shift Type</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" id="edit_shift_type_id" name="shift_type_id">
                    <div class="form-group">
                        <label for="edit_shift_name">Shift Name:</label>
                        <input type="text" class="form-control" id="edit_shift_name" name="shift_name" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_start_time">Start Time:</label>
                        <input type="time" class="form-control" id="edit_start_time" name="start_time" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_end_time">End Time:</label>
                        <input type="time" class="form-control" id="edit_end_time" name="end_time" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_description">Description:</label>
                        <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" name="edit_shift_type" class="btn btn-primary">Update Shift Type</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<script>
$(document).ready(function() {
    // Handle edit modal data
    $('#editShiftTypeModal').on('show.bs.modal', function(event) {
        var button = $(event.relatedTarget);
        var id = button.data('id');
        var name = button.data('name');
        var start = button.data('start');
        var end = button.data('end');
        var desc = button.data('desc');
        
        var modal = $(this);
        modal.find('#edit_shift_type_id').val(id);
        modal.find('#edit_shift_name').val(name);
        modal.find('#edit_start_time').val(start);
        modal.find('#edit_end_time').val(end);
        modal.find('#edit_description').val(desc);
    });
});
</script>
</body>
</html> 