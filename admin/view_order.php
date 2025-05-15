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
require_once '../models/Order.php';

// Create order model
$orderModel = new Order($conn);

// Check ID parameter
if (isset($_GET["id"]) && !empty(trim($_GET["id"]))) {
    // Get ID parameter from URL
    $order_id = trim($_GET["id"]);

    // Get order information
    $order = $orderModel->getOrderById($order_id);

    if (!$order) {
        // Order not found, redirect to orders page
        header("location: orders.php");
        exit();
    }
    
    // Get order details
    $orderDetails = $orderModel->getOrderDetails($order_id);
    
    // Get order payment (if any)
    $payment = $orderModel->getPaymentByOrderId($order_id);
} else {
    // ID parameter not in URL, redirect to orders page
    header("location: orders.php");
    exit();
}

// Determine English text and class for order status
$status_class = '';
switch($order['order_status']) {
    case 'ordered':
        $status_class = 'badge-primary';
        $status_text = 'Ordered';
        break;
    case 'preparing':
        $status_class = 'badge-warning';
        $status_text = 'Preparing';
        break;
    case 'served':
        $status_class = 'badge-success';
        $status_text = 'Served';
        break;
    case 'paid':
        $status_class = 'badge-info';
        $status_text = 'Paid';
        break;
    default:
        $status_text = $order['order_status'];
        $status_class = 'badge-secondary';
}

// Customer name
$customer_name = isset($order['first_name']) ? $order['first_name'] . ' ' . $order['last_name'] : 'Guest';

// Employee name
$employee_name = isset($order['employee_first_name']) ? $order['employee_first_name'] . ' ' . $order['employee_last_name'] : '-';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details - Restaurant Management System</title>
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
        .info-row {
            margin-bottom: 10px;
        }
        .info-label {
            font-weight: bold;
        }
        .price {
            font-weight: bold;
            color: #28a745;
        }
        .total-row {
            font-weight: bold;
            font-size: 1.1rem;
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
        <a href="orders.php" class="active"><i class="fas fa-clipboard-list mr-2"></i> Orders</a>
        <a href="reservations.php"><i class="fas fa-calendar-alt mr-2"></i> Reservations</a>
        <a href="../logout.php"><i class="fas fa-sign-out-alt mr-2"></i> Logout</a>
    </div>
    
    <!-- Main Content -->
    <div class="content col-md-10">
        <div class="top-bar d-flex justify-content-between align-items-center">
            <h3>Order Details</h3>
            <div>
                <span class="mr-3">Welcome, <?php echo htmlspecialchars($_SESSION["username"]); ?></span>
                <a href="../logout.php" class="btn btn-danger btn-sm"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Order #<?php echo $order_id; ?></h5>
                <div>
                    <?php if($order['order_status'] !== 'paid' && !$payment): ?>
                    <a href="add_payment.php?id=<?php echo $order_id; ?>" class="btn btn-success btn-sm">
                        <i class="fas fa-money-bill-wave"></i> Add Payment
                    </a>
                    <?php endif; ?>
                    <a href="edit_order.php?id=<?php echo $order_id; ?>" class="btn btn-primary btn-sm">
                        <i class="fas fa-edit"></i> Edit
                    </a>
                    <button type="button" class="btn btn-info btn-sm" onclick="window.print()">
                        <i class="fas fa-print"></i> Print
                    </button>
                    <a href="orders.php" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="row info-row">
                            <div class="col-md-4 info-label">Order Date:</div>
                            <div class="col-md-8"><?php echo date('d.m.Y H:i', strtotime($order['order_date'])); ?></div>
                        </div>
                        <div class="row info-row">
                            <div class="col-md-4 info-label">Customer:</div>
                            <div class="col-md-8"><?php echo htmlspecialchars($customer_name); ?></div>
                        </div>
                        <div class="row info-row">
                            <div class="col-md-4 info-label">Table:</div>
                            <div class="col-md-8"><?php echo $order['table_id']; ?></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="row info-row">
                            <div class="col-md-4 info-label">Status:</div>
                            <div class="col-md-8">
                                <span class="badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                            </div>
                        </div>
                        <div class="row info-row">
                            <div class="col-md-4 info-label">Staff:</div>
                            <div class="col-md-8"><?php echo htmlspecialchars($employee_name); ?></div>
                        </div>
                        <div class="row info-row">
                            <div class="col-md-4 info-label">Total Amount:</div>
                            <div class="col-md-8 price"><?php echo number_format($order['total_amount'], 2); ?> ₺</div>
                        </div>
                    </div>
                </div>
                
                <h6 class="border-bottom pb-2 mb-3">Order Items</h6>
                
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Category</th>
                                <th>Unit Price</th>
                                <th>Quantity</th>
                                <th>Total</th>
                                <th>Special Instructions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $subtotal = 0;
                            if (mysqli_num_rows($orderDetails) > 0) {
                                while ($item = mysqli_fetch_assoc($orderDetails)) {
                                    $item_total = $item['quantity'] * $item['price'];
                                    $subtotal += $item_total;
                                    
                                    echo "<tr>";
                                    echo "<td>" . htmlspecialchars($item['name']) . "</td>";
                                    echo "<td>" . htmlspecialchars($item['category']) . "</td>";
                                    echo "<td>" . number_format($item['price'], 2) . " ₺</td>";
                                    echo "<td>" . $item['quantity'] . "</td>";
                                    echo "<td>" . number_format($item_total, 2) . " ₺</td>";
                                    echo "<td>" . htmlspecialchars($item['special_instructions']) . "</td>";
                                    echo "</tr>";
                                }
                                
                                // Total row
                                echo "<tr class='total-row'>";
                                echo "<td colspan='4' class='text-right'>Total:</td>";
                                echo "<td>" . number_format($subtotal, 2) . " ₺</td>";
                                echo "<td></td>";
                                echo "</tr>";
                            } else {
                                echo "<tr><td colspan='6' class='text-center'>No items found for this order.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if($payment): ?>
                <h6 class="border-bottom pb-2 mb-3 mt-4">Payment Information</h6>
                <div class="row">
                    <div class="col-md-6">
                        <div class="row info-row">
                            <div class="col-md-4 info-label">Payment Method:</div>
                            <div class="col-md-8">
                                <?php 
                                switch($payment['payment_method']) {
                                    case 'cash':
                                        echo 'Cash';
                                        break;
                                    case 'credit_card':
                                        echo 'Credit Card';
                                        break;
                                    case 'debit_card':
                                        echo 'Debit Card';
                                        break;
                                    default:
                                        echo $payment['payment_method'];
                                }
                                ?>
                            </div>
                        </div>
                        <div class="row info-row">
                            <div class="col-md-4 info-label">Payment Date:</div>
                            <div class="col-md-8"><?php echo date('d.m.Y H:i', strtotime($payment['payment_date'])); ?></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="row info-row">
                            <div class="col-md-4 info-label">Amount:</div>
                            <div class="col-md-8"><?php echo number_format($payment['total_amount'], 2); ?> ₺</div>
                        </div>
                        <div class="row info-row">
                            <div class="col-md-4 info-label">Tip:</div>
                            <div class="col-md-8"><?php echo number_format($payment['tip_amount'], 2); ?> ₺</div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html> 