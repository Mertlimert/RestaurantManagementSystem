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
require_once '../models/Reservation.php';
require_once '../models/Customer.php';
require_once '../models/Tables.php';

// Create models
$reservationModel = new Reservation($conn);
$customerModel = new Customer($conn);
$tableModel = new Tables($conn);

// Check ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error_msg'] = "Reservation ID not specified.";
    header("location: reservations.php");
    exit;
}

$reservation_id = $_GET['id'];
$reservation = $reservationModel->getReservationById($reservation_id);

if (!$reservation) {
    $_SESSION['error_msg'] = "Reservation not found.";
    header("location: reservations.php");
    exit;
}

// Check if form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $customer_id = $_POST['customer_id'];
    $table_id = $_POST['table_id'];
    $reservation_date = $_POST['reservation_date'];
    $reservation_time = $_POST['reservation_time'];
    $reservation_datetime = $reservation_date . ' ' . $reservation_time . ':00';
    $duration = $_POST['duration'];
    $guest_count = $_POST['guest_count'];
    $status = $_POST['status'];
    $notes = isset($_POST['notes']) ? $_POST['notes'] : null;
    
    // Check for conflicting reservations - exclude own ID
    if ($reservationModel->checkConflictingReservations($table_id, $reservation_datetime, $duration, $reservation_id)) {
        $_SESSION['error_msg'] = "Another reservation exists for this table and time.";
        header("location: edit_reservation.php?id=" . $reservation_id);
        exit;
    }
    
    // Store old table ID
    $old_table_id = $reservation['table_id'];
    
    // Update reservation
    if ($reservationModel->updateReservation(
        $reservation_id,
        $customer_id, 
        $table_id, 
        $reservation_datetime, 
        $duration, 
        $guest_count, 
        $status, 
        $notes)
    ) {
        // If table changed, mark old table as available
        if ($old_table_id != $table_id) {
            $tableModel->updateTableStatus($old_table_id, 'available');
        }
        
        // Mark new table as reserved
        $tableModel->updateTableStatus($table_id, 'reserved');
        
        $_SESSION['success_msg'] = "Reservation updated successfully.";
        header("location: reservations.php");
        exit;
    } else {
        $_SESSION['error_msg'] = "An error occurred while updating the reservation.";
        header("location: edit_reservation.php?id=" . $reservation_id);
        exit;
    }
}

// Parse reservation details
$reservation_date = date('Y-m-d', strtotime($reservation['reservation_datetime']));
$reservation_time = date('H:i', strtotime($reservation['reservation_datetime']));

// Get all customers and tables
$customers = $customerModel->getAllCustomers();
$tables = $tableModel->getAllTables();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Reservation - Restaurant Management System</title>
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
        <a href="ingredients.php"><i class="fas fa-carrot mr-2"></i> Ingredients</a>
        <a href="orders.php"><i class="fas fa-clipboard-list mr-2"></i> Orders</a>
        <a href="reservations.php" class="active"><i class="fas fa-calendar-alt mr-2"></i> Reservations</a>
        <a href="../logout.php"><i class="fas fa-sign-out-alt mr-2"></i> Logout</a>
    </div>
    
    <!-- Main Content -->
    <div class="content col-md-10">
        <div class="top-bar d-flex justify-content-between align-items-center">
            <h3>Edit Reservation</h3>
            <div>
                <span class="mr-3">Welcome, <?php echo htmlspecialchars($_SESSION["username"]); ?></span>
                <a href="../logout.php" class="btn btn-danger btn-sm"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
        
        <!-- Messages -->
        <?php if(isset($_SESSION['error_msg'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php 
                    echo $_SESSION['error_msg']; 
                    unset($_SESSION['error_msg']);
                ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Edit Reservation #<?php echo $reservation_id; ?></h5>
            </div>
            <div class="card-body">
                <form action="edit_reservation.php?id=<?php echo $reservation_id; ?>" method="post">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="customer_id">Customer</label>
                                <select class="form-control" id="customer_id" name="customer_id" required>
                                    <option value="">Select Customer</option>
                                    <?php 
                                    mysqli_data_seek($customers, 0);
                                    while ($customer = mysqli_fetch_assoc($customers)): 
                                        $selected = ($customer['customer_id'] == $reservation['customer_id']) ? 'selected' : '';
                                    ?>
                                        <option value="<?php echo $customer['customer_id']; ?>" <?php echo $selected; ?>>
                                            <?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name'] . ' (' . $customer['phone_number'] . ')'); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="table_id">Table</label>
                                <select class="form-control" id="table_id" name="table_id" required>
                                    <option value="">Select Table</option>
                                    <?php 
                                    mysqli_data_seek($tables, 0);
                                    while ($table = mysqli_fetch_assoc($tables)): 
                                        $selected = ($table['table_id'] == $reservation['table_id']) ? 'selected' : '';
                                    ?>
                                        <option value="<?php echo $table['table_id']; ?>" <?php echo $selected; ?>>
                                            Table <?php echo $table['table_id']; ?> (<?php echo $table['capacity']; ?> person capacity)
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="reservation_date">Date</label>
                                <input type="date" class="form-control" id="reservation_date" name="reservation_date" value="<?php echo $reservation_date; ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="reservation_time">Time</label>
                                <input type="time" class="form-control" id="reservation_time" name="reservation_time" value="<?php echo $reservation_time; ?>" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="guest_count">Number of Guests</label>
                                <input type="number" class="form-control" id="guest_count" name="guest_count" value="<?php echo $reservation['guest_count']; ?>" min="1" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="duration">Duration (minutes)</label>
                                <input type="number" class="form-control" id="duration" name="duration" value="<?php echo $reservation['duration']; ?>" min="30" step="30" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="status">Status</label>
                                <select class="form-control" id="status" name="status" required>
                                    <?php
                                    // Reservation statuses (example)
                                    $statuses = ['confirmed', 'pending', 'cancelled', 'completed'];
                                    $status_text_map = ['confirmed' => 'Confirmed', 'pending' => 'Pending', 'cancelled' => 'Cancelled', 'completed' => 'Completed'];
                                    foreach ($statuses as $stat):
                                        $selected = ($stat == $reservation['status']) ? 'selected' : '';
                                    ?>
                                        <option value="<?php echo $stat; ?>" <?php echo $selected; ?>>
                                            <?php echo $status_text_map[$stat]; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="notes">Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3"><?php echo htmlspecialchars($reservation['notes']); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <a href="reservations.php" class="btn btn-secondary">Cancel</a>
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