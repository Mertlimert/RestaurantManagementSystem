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
require_once '../models/Reservation.php';
require_once '../models/Customer.php';
require_once '../models/Tables.php';

// Modelleri oluştur
$reservationModel = new Reservation($conn);
$customerModel = new Customer($conn);
$tableModel = new Tables($conn);

// Rezervasyon silme işlemi
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $reservation_id = $_GET['delete'];
    if ($reservationModel->deleteReservation($reservation_id)) {
        $success_msg = "Reservation deleted successfully.";
    } else {
        $error_msg = "Reservation could not be deleted.";
    }
}

// Rezervasyon durumu güncelleme
if (isset($_GET['update_status']) && !empty($_GET['update_status']) && isset($_GET['status'])) {
    $reservation_id = $_GET['update_status'];
    $status = $_GET['status'];
    
    // Veritabanı statüsüne dönüştür
    $db_status = 'reserved'; // varsayılan olarak rezerve
    
    // Sistem durumundan veritabanı durumuna dönüştür
    switch($status) {
        case 'confirmed':
        case 'pending':
        case 'reserved':
            $db_status = 'reserved';
            break;
        case 'cancelled':
        case 'completed':
        case 'available':
            $db_status = 'available';
            break;
    }
    
    if ($reservationModel->updateReservationStatus($reservation_id, $db_status)) {
        $success_msg = "Reservation status updated successfully.";
    } else {
        $error_msg = "Reservation status could not be updated.";
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

// Tarih filtresini kontrol et
$date_filter = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// Filtre tipini kontrol et
$filter_type = isset($_GET['filter']) ? $_GET['filter'] : 'today';

// Filtre tipine göre rezervasyonları getir
if ($filter_type == 'today') {
    $reservations = $reservationModel->getReservationsByDate(date('Y-m-d'));
    $filter_title = "Today's Reservations";
} elseif ($filter_type == 'upcoming') {
    $reservations = $reservationModel->getUpcomingReservations(7);
    $filter_title = "Upcoming Reservations (7 days)";
} elseif ($filter_type == 'date' && !empty($date_filter)) {
    $reservations = $reservationModel->getReservationsByDate($date_filter);
    $filter_title = "Reservations for " . date('d.m.Y', strtotime($date_filter));
} else {
    $reservations = $reservationModel->getAllReservations();
    $filter_title = "All Reservations";
}

// Masa bilgilerini getir
$tables = $tableModel->getAllTables();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservation Management - Restaurant Management System</title>
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
        .status-badge {
            font-size: 0.8rem;
            padding: 5px 10px;
            border-radius: 20px;
        }
        .status-confirmed {
            background-color: #28a745;
            color: white;
        }
        .status-pending {
            background-color: #ffc107;
            color: black;
        }
        .status-cancelled {
            background-color: #dc3545;
            color: white;
        }
        .status-completed {
            background-color: #6c757d;
            color: white;
        }
        .filter-card {
            margin-bottom: 20px;
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
        <a href="ingredients.php"><i class="fas fa-carrot mr-2"></i> Ingredients</a>
        <a href="orders.php"><i class="fas fa-clipboard-list mr-2"></i> Orders</a>
        <a href="reservations.php" class="active"><i class="fas fa-calendar-alt mr-2"></i> Reservations</a>
        <a href="../logout.php"><i class="fas fa-sign-out-alt mr-2"></i> Logout</a>
    </div>
    
    <!-- Main Content -->
    <div class="content col-md-10">
        <div class="top-bar d-flex justify-content-between align-items-center">
            <h3>Reservation Management</h3>
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
        
        <!-- Filters -->
        <div class="card filter-card">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-8">
                        <div class="btn-group mb-3" role="group">
                            <a href="reservations.php?filter=today" class="btn <?php echo $filter_type == 'today' ? 'btn-primary' : 'btn-outline-primary'; ?>">Today</a>
                            <a href="reservations.php?filter=upcoming" class="btn <?php echo $filter_type == 'upcoming' ? 'btn-primary' : 'btn-outline-primary'; ?>">Upcoming (7 days)</a>
                            <a href="reservations.php?filter=all" class="btn <?php echo $filter_type == 'all' ? 'btn-primary' : 'btn-outline-primary'; ?>">All</a>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <form method="get" class="form-inline">
                            <input type="hidden" name="filter" value="date">
                            <div class="input-group w-100">
                                <input type="date" class="form-control" name="date" value="<?php echo $date_filter; ?>">
                                <div class="input-group-append">
                                    <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Search</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><?php echo $filter_title; ?></h5>
                <button type="button" class="btn btn-add btn-sm" data-toggle="modal" data-target="#addReservationModal">
                    <i class="fas fa-plus"></i> Add New Reservation
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Reservation ID</th>
                                <th>Customer</th>
                                <th>Table</th>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Number of People</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if (mysqli_num_rows($reservations) > 0) {
                                while ($reservation = mysqli_fetch_assoc($reservations)) {
                                    $status_class = '';
                                    $status_text = '';
                                    
                                    // Get reservation_status from database
                                    $status = isset($reservation['reservation_status']) ? $reservation['reservation_status'] : 'reserved';
                                    
                                    // Map database status to display status
                                    if ($status == 'reserved') {
                                        $status_class = 'status-confirmed';
                                        $status_text = 'Confirmed';
                                    } else if ($status == 'available') {
                                        $status_class = 'status-cancelled';
                                        $status_text = 'Cancelled';
                                    } else {
                                        $status_text = ucfirst($status);
                                        $status_class = 'status-secondary';
                                    }
                                    
                                    // Extract date and time from reservation_datetime
                                    $reservation_datetime = isset($reservation['reservation_datetime']) ? 
                                                          strtotime($reservation['reservation_datetime']) : time();
                                    $reservation_date = date('d.m.Y', $reservation_datetime);
                                    $reservation_time = date('H:i', $reservation_datetime);
                                    
                                    // Default to 2 people if guest_count not available
                                    $guest_count = isset($reservation['guest_count']) ? $reservation['guest_count'] : 2;
                                    
                                    echo "<tr>";
                                    echo "<td>" . $reservation['reservation_id'] . "</td>";
                                    echo "<td>" . htmlspecialchars($reservation['first_name'] . " " . $reservation['last_name']) . "</td>";
                                    echo "<td>" . $reservation['table_id'] . "</td>";
                                    echo "<td>" . $reservation_date . "</td>";
                                    echo "<td>" . $reservation_time . "</td>";
                                    echo "<td>" . $guest_count . "</td>";
                                    echo "<td><span class='status-badge " . $status_class . "'>" . $status_text . "</span></td>";
                                    echo "<td>";
                                    echo "<a href='edit_reservation.php?id=" . $reservation['reservation_id'] . "' class='btn btn-primary btn-sm' title='Edit'><i class='fas fa-edit'></i></a> ";
                                    echo "<a href='reservations.php?delete=" . $reservation['reservation_id'] . "' class='btn btn-danger btn-sm' title='Delete' onclick='return confirm(\"Are you sure you want to delete this reservation?\")'><i class='fas fa-trash'></i></a>";
                                    // Status change dropdown
                                    echo '<div class="btn-group ml-1">';
                                    echo '<button type="button" class="btn btn-info btn-sm dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="Change Status">';
                                    echo '<i class="fas fa-exchange-alt"></i>';
                                    echo '</button>';
                                    echo '<div class="dropdown-menu">';
                                    echo '<a class="dropdown-item" href="reservations.php?update_status='.$reservation['reservation_id'].'&status=confirmed">Confirmed</a>';
                                    echo '<a class="dropdown-item" href="reservations.php?update_status='.$reservation['reservation_id'].'&status=pending">Pending</a>';
                                    echo '<a class="dropdown-item" href="reservations.php?update_status='.$reservation['reservation_id'].'&status=cancelled">Cancelled</a>';
                                    echo '<a class="dropdown-item" href="reservations.php?update_status='.$reservation['reservation_id'].'&status=completed">Completed</a>';
                                    echo '</div>';
                                    echo '</div>';
                                    echo "</td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='8' class='text-center'>No reservations found.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Yeni Rezervasyon Ekleme Modal -->
    <div class="modal fade" id="addReservationModal" tabindex="-1" role="dialog" aria-labelledby="addReservationModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addReservationModalLabel">Add New Reservation</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="add_reservation.php" method="post">
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="customer_id">Customer</label>
                            <select class="form-control" id="customer_id" name="customer_id" required>
                                <option value="">Select Customer</option>
                                <?php 
                                $allCustomers = $customerModel->getAllCustomers();
                                if(mysqli_num_rows($allCustomers) > 0){
                                    while($cust = mysqli_fetch_assoc($allCustomers)){
                                        echo "<option value='" . $cust['customer_id'] . "'>" . htmlspecialchars($cust['first_name'] . ' ' . $cust['last_name']) . " (" . htmlspecialchars($cust['phone']) . ")</option>";
                                    }
                                }
                                ?>
                                <option value="new">Add New Customer...</option>
                            </select>
                        </div>
                        <div id="new_customer_fields" style="display:none;">
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="new_first_name">Customer Name</label>
                                    <input type="text" class="form-control" id="new_first_name" name="new_first_name">
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="new_last_name">Customer Last Name</label>
                                    <input type="text" class="form-control" id="new_last_name" name="new_last_name">
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="new_phone">Phone Number</label>
                                    <input type="text" class="form-control" id="new_phone" name="new_phone">
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="new_email">Email (Optional)</label>
                                    <input type="email" class="form-control" id="new_email" name="new_email">
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="table_id">Table</label>
                            <select class="form-control" id="table_id" name="table_id" required>
                                <option value="">Select Table</option>
                                <?php 
                                if(mysqli_num_rows($tables) > 0){
                                    mysqli_data_seek($tables, 0); // Reset pointer for tables
                                    while($tbl = mysqli_fetch_assoc($tables)){
                                        echo "<option value='" . $tbl['table_id'] . "' data-capacity='" . $tbl['capacity'] . "'>Table " . $tbl['table_id'] . " (Capacity: " . $tbl['capacity'] . " people)</option>";
                                    }
                                }
                                ?>
                            </select>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="reservation_date">Reservation Date</label>
                                <input type="date" class="form-control" id="reservation_date" name="reservation_date" min="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="reservation_time">Reservation Time</label>
                                <input type="time" class="form-control" id="reservation_time" name="reservation_time" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="guest_count">Number of People</label>
                            <input type="number" class="form-control" id="guest_count" name="guest_count" min="1" required>
                            <small id="capacity_info" class="form-text text-muted"></small>
                        </div>
                        <div class="form-group">
                            <label for="duration">Duration (minutes)</label>
                            <input type="number" class="form-control" id="duration" name="duration" min="30" step="30" value="120" required>
                        </div>
                         <div class="form-group">
                            <label for="status">Status</label>
                            <select class="form-control" id="status" name="status" required>
                                <option value="confirmed">Confirmed</option>
                                <option value="pending">Pending</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="notes">Notes (Optional)</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
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
    
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        // Sayfa yüklendiğinde çalışacak işlemler
        document.addEventListener('DOMContentLoaded', function() {
            // Bugünün tarihini rezervasyon tarihi için varsayılan olarak ayarla
            document.getElementById('reservation_date').value = '<?php echo date('Y-m-d'); ?>';
            
            // Şu anki saati alıp en yakın 30 dakikaya yuvarlama
            const now = new Date();
            const hours = now.getHours();
            const minutes = Math.ceil(now.getMinutes() / 30) * 30;
            const timeStr = (hours + Math.floor(minutes / 60)).toString().padStart(2, '0') + ':' + 
                            (minutes % 60).toString().padStart(2, '0');
            
            document.getElementById('reservation_time').value = timeStr;
            
            // Show/hide new customer fields
            const customerSelect = document.getElementById('customer_id');
            customerSelect.addEventListener('change', function() {
                if (this.value === 'new') {
                    document.getElementById('new_customer_fields').style.display = 'block';
                } else {
                    document.getElementById('new_customer_fields').style.display = 'none';
                }
            });
            
            // Check table capacity against guest count
            const tableSelect = document.getElementById('table_id');
            const guestCount = document.getElementById('guest_count');
            const capacityInfo = document.getElementById('capacity_info');
            
            function checkCapacity() {
                if (tableSelect.selectedIndex > 0 && guestCount.value > 0) {
                    const capacity = parseInt(tableSelect.options[tableSelect.selectedIndex].dataset.capacity);
                    const guests = parseInt(guestCount.value);
                    
                    if (guests > capacity) {
                        capacityInfo.textContent = 'Warning: Number of guests exceeds table capacity!';
                        capacityInfo.classList.add('text-danger');
                    } else {
                        capacityInfo.textContent = '';
                        capacityInfo.classList.remove('text-danger');
                    }
                }
            }
            
            tableSelect.addEventListener('change', checkCapacity);
            guestCount.addEventListener('input', checkCapacity);
        });
    </script>
</body>
</html> 