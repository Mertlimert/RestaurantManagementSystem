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
require_once '../models/Order.php';
require_once '../models/Tables.php';
require_once '../models/Customer.php';
require_once '../models/Employee.php';

// Modelleri oluştur
$orderModel = new Order($conn);
$tableModel = new Tables($conn);
$customerModel = new Customer($conn);
$employeeModel = new Employee($conn);

// Tüm müşterileri ve çalışanları getir
$customers = $customerModel->getAllCustomers();
$employees = $employeeModel->getAllEmployees();

// URL parametrelerini al
$table_filter = isset($_GET['table_id']) ? $_GET['table_id'] : null;
$customer_filter = isset($_GET['customer_id']) ? $_GET['customer_id'] : null;
$employee_filter = isset($_GET['employee_id']) ? $_GET['employee_id'] : null;
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : '';
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : '';

// Sipariş silme işlemi
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $order_id = $_GET['delete'];
    if ($orderModel->deleteOrder($order_id)) {
        $success_msg = "Order deleted successfully.";
    } else {
        $error_msg = "Order could not be deleted.";
    }
}

// Durumu güncelle
if (isset($_GET['update_status']) && !empty($_GET['update_status']) && isset($_GET['status']) && !empty($_GET['status'])) {
    $order_id = $_GET['update_status'];
    $new_status = $_GET['status'];
    if ($orderModel->updateOrderStatus($order_id, $new_status)) {
        $success_msg = "Order status updated successfully.";
    } else {
        $error_msg = "Order status could not be updated.";
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

// Siparişleri getir (filtreli veya filtresiz)
$orders = null;
$filter_title = "All Orders";

if ($table_filter) {
    $orders = $orderModel->getOrdersByTable($table_filter);
    $table = $tableModel->getTableById($table_filter);
    $filter_title = "Table " . $table_filter . "'s Orders";
} elseif ($customer_filter) {
    if (!empty($from_date) || !empty($to_date)) {
        $orders = $orderModel->getOrdersByCustomerAndDateRange($customer_filter, $from_date, $to_date);
    } else {
        $orders = $orderModel->getOrdersByCustomer($customer_filter);
    }
    $customer = $customerModel->getCustomerById($customer_filter);
    $filter_title = $customer['first_name'] . ' ' . $customer['last_name'] . "'s Orders";
} elseif ($employee_filter) {
    if (!empty($from_date) || !empty($to_date)) {
        $orders = $orderModel->getOrdersByEmployeeAndDateRange($employee_filter, $from_date, $to_date);
    } else {
        $orders = $orderModel->getOrdersByEmployee($employee_filter);
    }
    $employee = $employeeModel->getEmployeeById($employee_filter);
    $filter_title = $employee['first_name'] . ' ' . $employee['last_name'] . "'s Orders";
} elseif (!empty($from_date) || !empty($to_date)) {
    // Tarih aralığına göre filtreleme
    // İki tarih de varsa
    if (!empty($from_date) && !empty($to_date)) {
        $filter_title = $from_date . " - " . $to_date . " Orders Between";
    } 
    // Sadece başlangıç tarihi varsa
    elseif (!empty($from_date)) {
        $filter_title = $from_date . " Orders After";
    } 
    // Sadece bitiş tarihi varsa
    else {
        $filter_title = $to_date . " Orders Before";
    }
}

// Hiçbir filtre seçilmemişse tüm siparişleri getir
if ($orders === null) {
    $orders = $orderModel->getAllOrders();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Management - Restaurant Management System</title>
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
        .status-new {
            background-color: #007bff;
            color: white;
        }
        .status-preparing {
            background-color: #ffc107;
            color: black;
        }
        .status-ready {
            background-color: #28a745;
            color: white;
        }
        .status-delivered {
            background-color: #6c757d;
            color: white;
        }
        .status-paid {
            background-color: #17a2b8;
            color: white;
        }
        .status-cancelled {
            background-color: #dc3545;
            color: white;
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
        <a href="orders.php" class="active"><i class="fas fa-clipboard-list mr-2"></i> Orders</a>
        <a href="reservations.php"><i class="fas fa-calendar-alt mr-2"></i> Reservations</a>
        <a href="../logout.php"><i class="fas fa-sign-out-alt mr-2"></i> Logout</a>
    </div>
    
    <!-- Main Content -->
    <div class="content col-md-10">
        <div class="top-bar d-flex justify-content-between align-items-center">
            <h3>Order Management</h3>
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
                <h5 class="mb-0"><?php echo $filter_title; ?></h5>
                <div>
                    <?php if ($table_filter || $customer_filter || $employee_filter || !empty($from_date) || !empty($to_date)): ?>
                        <a href="orders.php" class="btn btn-secondary btn-sm mr-2">
                            <i class="fas fa-filter"></i> Reset Filters
                        </a>
                    <?php endif; ?>
                    <a href="add_order.php" class="btn btn-add btn-sm">
                        <i class="fas fa-plus"></i> Add New Order
                    </a>
                </div>
            </div>
            
            <!-- Filtreleme Formu -->
            <div class="card-body border-bottom pb-3">
                <form method="get" action="" class="mb-0">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="customer_id">Customer Filter</label>
                                <select class="form-control" id="customer_id" name="customer_id">
                                    <option value="">-- Select Customer --</option>
                                    <?php while ($customer = mysqli_fetch_assoc($customers)): ?>
                                        <option value="<?php echo $customer['customer_id']; ?>" <?php echo ($customer_filter == $customer['customer_id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="employee_id">Employee Filter</label>
                                <select class="form-control" id="employee_id" name="employee_id">
                                    <option value="">-- Select Employee --</option>
                                    <?php while ($employee = mysqli_fetch_assoc($employees)): ?>
                                        <option value="<?php echo $employee['employee_id']; ?>" <?php echo ($employee_filter == $employee['employee_id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="from_date">Start Date</label>
                                <input type="date" class="form-control" id="from_date" name="from_date" value="<?php echo $from_date; ?>">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="to_date">End Date</label>
                                <input type="date" class="form-control" id="to_date" name="to_date" value="<?php echo $to_date; ?>">
                            </div>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary btn-block">Filter</button>
                        </div>
                    </div>
                </form>
            </div>
            
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Table</th>
                                <th>Staff</th>
                                <th>Date</th>
                                <th>Total Amount</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if (mysqli_num_rows($orders) > 0) {
                                while ($order = mysqli_fetch_assoc($orders)) {
                                    $status_class = '';
                                    $status_text = '';
                                    switch ($order['order_status']) {
                                        case 'ordered':
                                            $status_class = 'status-ordered';
                                            $status_text = 'Ordered';
                                            break;
                                        case 'preparing':
                                            $status_class = 'status-preparing';
                                            $status_text = 'Preparing';
                                            break;
                                        case 'served':
                                            $status_class = 'status-served';
                                            $status_text = 'Served';
                                            break;
                                        case 'paid':
                                            $status_class = 'status-paid';
                                            $status_text = 'Paid';
                                            break;
                                        default:
                                            $status_class = 'status-unknown';
                                            $status_text = 'Unknown';
                                    }
                                    
                                    echo "<tr>";
                                    echo "<td>" . $order['order_id'] . "</td>";
                                    echo "<td>" . (isset($order['first_name']) ? htmlspecialchars($order['first_name'] . ' ' . $order['last_name']) : 'Guest') . "</td>";
                                    echo "<td>" . $order['table_id'] . "</td>";
                                    echo "<td>" . (isset($order['employee_first_name']) ? htmlspecialchars($order['employee_first_name'] . ' ' . $order['employee_last_name']) : '-') . "</td>";
                                    echo "<td>" . date('d.m.Y H:i', strtotime($order['order_date'])) . "</td>";
                                    echo "<td>" . number_format($order['total_amount'], 2) . " ₺</td>";
                                    echo "<td><span class='status-badge " . $status_class . "'>" . $status_text . "</span></td>";
                                    echo "<td>";
                                    echo "<a href='view_order.php?id=" . $order['order_id'] . "' class='btn btn-info btn-sm' title='Details'><i class='fas fa-eye'></i></a> ";
                                    echo "<a href='edit_order.php?id=" . $order['order_id'] . "' class='btn btn-primary btn-sm' title='Edit'><i class='fas fa-edit'></i></a> ";
                                    echo "<a href='orders.php?delete=" . $order['order_id'] . "' class='btn btn-danger btn-sm' title='Delete' onclick='return confirm(\"Are you sure you want to delete this order?\")'><i class='fas fa-trash'></i></a>";
                                    // Durum Güncelleme Dropdown
                                    echo '<div class="btn-group ml-1">';
                                    echo '<button type="button" class="btn btn-secondary btn-sm dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="Update Status">';
                                    echo '<i class="fas fa-sync-alt"></i>';
                                    echo '</button>';
                                    echo '<div class="dropdown-menu">';
                                    echo '<a class="dropdown-item" href="orders.php?update_status='.$order['order_id'].'&status=new">New</a>';
                                    echo '<a class="dropdown-item" href="orders.php?update_status='.$order['order_id'].'&status=preparing">Preparing</a>';
                                    echo '<a class="dropdown-item" href="orders.php?update_status='.$order['order_id'].'&status=ready">Ready</a>';
                                    echo '<a class="dropdown-item" href="orders.php?update_status='.$order['order_id'].'&status=delivered">Delivered</a>';
                                    echo '<a class="dropdown-item" href="orders.php?update_status='.$order['order_id'].'&status=paid">Paid</a>';
                                    echo '<div class="dropdown-divider"></div>';
                                    echo '<a class="dropdown-item text-danger" href="orders.php?update_status='.$order['order_id'].'&status=cancelled">Cancelled</a>';
                                    echo '</div>';
                                    echo '</div>';
                                    echo "</td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='8' class='text-center'>No orders found.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html> 