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
require_once '../models/Tables.php';
require_once '../models/Customer.php';

// Masalar modeli oluştur
$tableModel = new Tables($conn);
$customerModel = new Customer($conn);

// Masa silme işlemi
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $table_id = $_GET['delete'];
    if ($tableModel->deleteTable($table_id)) {
        $success_msg = "Table deleted successfully.";
    } else {
        $error_msg = "Table could not be deleted.";
    }
}

// Masa durumu güncelleme
if (isset($_GET['update_status']) && !empty($_GET['update_status']) && isset($_GET['status'])) {
    $table_id = $_GET['update_status'];
    $status = $_GET['status'];
    if ($tableModel->updateTableStatus($table_id, $status)) {
        $success_msg = "Table status updated successfully.";
    } else {
        $error_msg = "Table status could not be updated.";
    }
}

// Müşteri atama işlemi
if (isset($_POST['assign_customer']) && !empty($_POST['table_id'])) {
    $table_id = $_POST['table_id'];
    $number_of_customers = isset($_POST['number_of_customers']) ? $_POST['number_of_customers'] : 1;
    
    if ($tableModel->assignCustomerToTable($table_id, 0, $number_of_customers)) {
        $success_msg = "Table successfully marked as occupied.";
    } else {
        $error_msg = "Table status could not be updated.";
    }
}

// Müşteri kaldırma işlemi
if (isset($_GET['remove_customer']) && !empty($_GET['remove_customer'])) {
    $table_id = $_GET['remove_customer'];
    // Masayı boşalt
    if ($tableModel->updateTableStatus($table_id, 'available')) {
        // Müşteri sayısını sıfırla
        $tableModel->updateTable($table_id, 0, $tableModel->getTableById($table_id)['capacity'], 'available');
        $success_msg = "Table successfully marked as available.";
    } else {
        $error_msg = "Table status could not be updated.";
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

// Tüm masaları getir
$tables = $tableModel->getAllTables();

// Dolu masalar için detayları getir
$occupiedTablesWithDetails = $tableModel->getOccupiedTablesWithDetails();
$occupiedTablesInfo = [];

// Dolu masaların bilgilerini diziye ekle
if ($occupiedTablesWithDetails) {
    while ($tableDetail = mysqli_fetch_assoc($occupiedTablesWithDetails)) {
        $occupiedTablesInfo[$tableDetail['table_id']] = $tableDetail;
    }
}

// Müşterileri getir (müşteri atama formunda kullanılacak)
$customers = $customerModel->getAllCustomers();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Table Management - Restaurant Management System</title>
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
        .table-status {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 5px;
        }
        .available {
            background-color: #28a745;
        }
        .occupied {
            background-color: #dc3545;
        }
        .reserved {
            background-color: #ffc107;
        }
        .customer-info {
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            margin-top: 10px;
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
            <h3>Table Management</h3>
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
                <h5 class="mb-0">Table List</h5>
                <button type="button" class="btn btn-add btn-sm" data-toggle="modal" data-target="#addTableModal">
                    <i class="fas fa-plus"></i> Add New Table
                </button>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-12">
                        <div class="d-flex align-items-center">
                            <span class="mr-3">Status:</span>
                            <div class="mr-3"><span class="table-status available"></span> Available</div>
                            <div class="mr-3"><span class="table-status occupied"></span> Occupied</div>
                            <div><span class="table-status reserved"></span> Reserved</div>
                        </div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Table ID</th>
                                <th>Capacity</th>
                                <th>Current Number of Customers</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if (mysqli_num_rows($tables) > 0) {
                                while ($table = mysqli_fetch_assoc($tables)) {
                                    $status_class = '';
                                    $status_text = '';
                                    
                                    switch ($table['table_status']) {
                                        case 'available':
                                            $status_class = 'available';
                                            $status_text = 'Available';
                                            break;
                                        case 'occupied':
                                            $status_class = 'occupied';
                                            $status_text = 'Occupied';
                                            break;
                                        case 'reserved':
                                            $status_class = 'reserved';
                                            $status_text = 'Reserved';
                                            break;
                                        default:
                                            $status_class = '';
                                            $status_text = $table['table_status'];
                                    }
                                    ?>
                                    <tr>
                                        <td><?php echo $table['table_id']; ?></td>
                                        <td><?php echo $table['capacity']; ?></td>
                                        <td><?php echo (isset($occupiedTablesInfo[$table['table_id']]) ? $occupiedTablesInfo[$table['table_id']]['number_of_customers'] : 0); ?></td>
                                        <td>
                                            <span class="table-status <?php echo $status_class; ?>"></span>
                                            <?php echo $status_text; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <?php if (isset($occupiedTablesInfo[$table['table_id']])): ?>
                                                    <a href="tables.php?remove_customer=<?php echo $table['table_id']; ?>" class="btn btn-warning btn-sm" title="Remove Customer">
                                                        <i class="fas fa-user-minus"></i>
                                                    </a>
                                                <?php else: ?>
                                                    <button type="button" class="btn btn-success btn-sm assign-customer-btn" data-toggle="modal" data-target="#assignCustomerModal" data-table-id="<?php echo $table['table_id']; ?>" data-table-capacity="<?php echo $table['capacity']; ?>" title="Assign Customer">
                                                        <i class="fas fa-user-plus"></i>
                                                    </button>
                                                <?php endif; ?>
                                                <a href="edit_table.php?id=<?php echo $table['table_id']; ?>" class="btn btn-primary btn-sm" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="tables.php?delete=<?php echo $table['table_id']; ?>" class="btn btn-danger btn-sm" title="Delete" onclick="return confirm('Are you sure you want to delete this table?')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php if ($table['table_status'] == 'occupied' && isset($occupiedTablesInfo[$table['table_id']])): 
                                        $tableInfo = $occupiedTablesInfo[$table['table_id']];
                                    ?>
                                    <tr>
                                        <td colspan="5">
                                            <div class="customer-info">
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <p class="mb-1"><strong>Customer Name: </strong>
                                                        <?php if (!empty($tableInfo['customer_first_name'])): ?>
                                                        <p class="mb-1"><i class="fas fa-user text-primary mr-2"></i> 
                                                            <?php echo htmlspecialchars($tableInfo['customer_first_name'] . ' ' . $tableInfo['customer_last_name']); ?>
                                                        </p>
                                                        <p class="mb-1"><i class="fas fa-phone text-primary mr-2"></i> 
                                                            <?php echo htmlspecialchars($tableInfo['customer_phone']); ?>
                                                        </p>
                                                        <?php else: ?>
                                                        <p class="mb-1 text-muted">Customer information not found.</p>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <p class="mb-1"><strong>Assigned Employee:</strong>
                                                        <?php if (!empty($tableInfo['employee_first_name'])): ?>
                                                        <p class="mb-1"><i class="fas fa-user-tie text-success mr-2"></i> 
                                                            <?php echo htmlspecialchars($tableInfo['employee_first_name'] . ' ' . $tableInfo['employee_last_name']); ?>
                                                        </p>
                                                        <?php else: ?>
                                                        <p class="mb-1 text-muted">Assigned employee information not found.</p>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                <?php
                                }
                            } else {
                                echo "<tr><td colspan='5' class='text-center'>No tables found.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Yeni Masa Ekle Modal -->
    <div class="modal fade" id="addTableModal" tabindex="-1" role="dialog" aria-labelledby="addTableModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addTableModalLabel">Add New Table</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="add_table.php" method="post">
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="table_number">Table Number</label>
                            <input type="number" class="form-control" id="table_number" name="table_number" placeholder="Table number must be unique." required>
                        </div>
                        <div class="form-group">
                            <label for="capacity">Capacity</label>
                            <input type="number" class="form-control" id="capacity" name="capacity" required>
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
    
    <!-- Müşteri Atama Modal -->
    <div class="modal fade" id="assignCustomerModal" tabindex="-1" role="dialog" aria-labelledby="assignCustomerModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="assignCustomerModalLabel">Assign Customer</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="tables.php" method="post">
                    <div class="modal-body">
                        <input type="hidden" name="table_id" id="assign_table_id">
                        <p><strong>Table ID: </strong><span id="modal_table_id_display"></span></p>
                        <p><strong>Current Capacity: </strong><span id="modal_table_capacity_display"></span></p>
                        <div class="form-group">
                            <label for="number_of_customers">Number of Customers</label>
                            <input type="number" class="form-control" id="number_of_customers" name="number_of_customers" min="1" required>
                            <small class="form-text text-muted">Maximum capacity: <span id="modal_max_capacity_info"></span></small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" name="assign_customer" class="btn btn-success">Assign Customer</button>
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