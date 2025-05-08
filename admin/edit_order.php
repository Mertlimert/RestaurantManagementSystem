<?php
// Enable error reporting (For development)
error_reporting(E_ALL);
ini_set('display_errors', 1);

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
require_once '../models/Order.php';
require_once '../models/Customer.php';
require_once '../models/Employee.php';
require_once '../models/Tables.php';
require_once '../models/MenuItem.php';

// Create models
$orderModel = new Order($conn);
$customerModel = new Customer($conn);
$employeeModel = new Employee($conn);
$tableModel = new Tables($conn);
$menuItemModel = new MenuItem($conn);

// Check ID parameter
if (isset($_GET["id"]) && !empty(trim($_GET["id"]))) {
    // Get ID parameter from URL
    $order_id = trim($_GET["id"]);

    // Get order information
    $order = $orderModel->getOrderById($order_id);

    if (!$order) {
        // Order not found, redirect to orders page
        $_SESSION['error_msg'] = "Order not found.";
        header("location: orders.php");
        exit();
    }
    
    // Get order details
    $orderDetails = $orderModel->getOrderDetails($order_id);
} else {
    // ID parameter not in URL, redirect to orders page
    header("location: orders.php");
    exit();
}

// Error and success messages
$error_msg = '';
$success_msg = '';

// When form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $customer_id = $_POST["customer_id"];
    $employee_id = $_POST["employee_id"];
    $table_id = $_POST["table_id"];
    $order_status = $_POST["order_status"];
    
    // Check action type - delete item, add item, or update order
    $action_type = "update"; // Default action
    
    // Delete item action
    if (isset($_POST["delete_item_menu_id"]) && !empty($_POST["delete_item_menu_id"])) {
        $action_type = "delete";
        $menu_item_id_to_delete = $_POST["delete_item_menu_id"];
        
        // Delete order detail with order_id and menu_item_id
        if ($orderModel->deleteOrderDetail($order_id, $menu_item_id_to_delete)) {
            $_SESSION['success_msg'] = "Item deleted successfully.";
        } else {
            $_SESSION['error_msg'] = "An error occurred while deleting the item.";
        }
        
        // Update order total
        $orderModel->updateOrderTotal($order_id);
        
        // Refresh page
        header("Location: edit_order.php?id=" . $order_id);
        exit();
    }
    
    // Add new item action
    if (isset($_POST["add_new_item_action"]) && // Check if the specific "add item" button was pressed
        isset($_POST["new_menu_item_id"]) && !empty($_POST["new_menu_item_id"]) && 
        isset($_POST["new_quantity"]) && !empty($_POST["new_quantity"])) {
        
        $action_type = "add";
        $menu_item_id = $_POST["new_menu_item_id"];
        $quantity = $_POST["new_quantity"];
        $price = $_POST["new_price"];
        $special_instructions = isset($_POST["new_special_instructions"]) ? $_POST["new_special_instructions"] : "";
        
        // Add order detail
        if ($orderModel->addOrderDetail($order_id, $menu_item_id, $quantity, $price, $special_instructions)) {
            $_SESSION['success_msg'] = "New item added successfully.";
        } else {
            $_SESSION['error_msg'] = "An error occurred while adding the new item.";
        }
        
        // Update order total
        $orderModel->updateOrderTotal($order_id);
        
        // Refresh page
        header("Location: edit_order.php?id=" . $order_id);
        exit();
    }
    
    // Normal order update action
    if ($action_type == "update") {
        // Update order status
        if ($orderModel->updateOrderStatus($order_id, $order_status)) {
            $success_msg = "Order status updated.";
        } else {
            $error_msg = "An error occurred while updating order status.";
        }
        
        // Update order information (this part is for general order info, not items)
        $sql_order_update = "UPDATE Orders SET customer_id = ?, employee_id = ?, table_id = ? WHERE order_id = ?";
        $stmt_order_update = mysqli_prepare($conn, $sql_order_update);
        mysqli_stmt_bind_param($stmt_order_update, "iiii", $customer_id, $employee_id, $table_id, $order_id);
        
        if (mysqli_stmt_execute($stmt_order_update)) {
            if (!$error_msg) { // Set success message only if there are no other errors
                $success_msg = "Order information updated successfully.";
            }
            $order = $orderModel->getOrderById($order_id); // Reload order information
        } else {
            $error_msg = "An error occurred while updating main order information: " . mysqli_error($conn);
        }
        mysqli_stmt_close($stmt_order_update);
        
        // Order details update (item quantities and special requests)
        if (isset($_POST["item_menu_ids"]) && isset($_POST["quantity"]) && isset($_POST["special_instructions"])) {
            $item_menu_ids_posted = $_POST["item_menu_ids"];
            $quantities_posted = $_POST["quantity"];
            $special_instructions_posted = $_POST["special_instructions"];
            
            // Ensure that the array sizes match
            if (count($item_menu_ids_posted) == count($quantities_posted) && count($item_menu_ids_posted) == count($special_instructions_posted)) {
                for ($i = 0; $i < count($item_menu_ids_posted); $i++) {
                    $posted_menu_item_id = $item_menu_ids_posted[$i];
                    $posted_quantity = $quantities_posted[$i];
                    $posted_special_instructions = $special_instructions_posted[$i];
                    
                    // Update order detail (using order_id and menu_item_id)
                    $sql_detail_update = "UPDATE OrderDetails SET quantity = ?, special_instructions = ? WHERE order_id = ? AND menu_item_id = ?";
                    $stmt_detail_update = mysqli_prepare($conn, $sql_detail_update);
                    mysqli_stmt_bind_param($stmt_detail_update, "isii", $posted_quantity, $posted_special_instructions, $order_id, $posted_menu_item_id);
                    
                    if (!mysqli_stmt_execute($stmt_detail_update)) {
                        $error_msg = "An error occurred while updating an item (Menu ID: $posted_menu_item_id): " . mysqli_error($conn);
                        // Optional: Can break out of loop on error
                        // break; 
                    }
                    mysqli_stmt_close($stmt_detail_update);
                }
            } else {
                $error_msg = "Inconsistency in item update data.";
            }
            
            if (!$error_msg) { // If no errors in item updates, strengthen general success message
                $success_msg = "Order and items updated successfully.";
            }
            
            // Update order total
            $orderModel->updateOrderTotal($order_id);
            
            // Fetch updated order details and main order information again
            $orderDetails = $orderModel->getOrderDetails($order_id);
            $order = $orderModel->getOrderById($order_id); // Total amount may have changed
        }

        // Redirect to orders page if update was successful
        if (empty($error_msg)) {
            $_SESSION['success_msg'] = $success_msg ? $success_msg : "Order updated successfully.";
            header("Location: orders.php");
            exit();
        }
    }
}

// Check session messages
if (isset($_SESSION['success_msg'])) {
    $success_msg = $_SESSION['success_msg'];
    unset($_SESSION['success_msg']);
}

if (isset($_SESSION['error_msg'])) {
    $error_msg = $_SESSION['error_msg'];
    unset($_SESSION['error_msg']);
}

// Get customers
$customers = $customerModel->getAllCustomers();

// Get employees
$employees = $employeeModel->getAllEmployees();

// Get tables
$tables = $tableModel->getAllTables();

// Get menu items
$menuItems = $menuItemModel->getAllMenuItems();

// Status options
$status_options = array(
    'ordered' => 'Ordered',
    'preparing' => 'Preparing',
    'served' => 'Served',
    'paid' => 'Paid'
);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Order - Restaurant Management System</title>
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
        .order-item {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 15px;
        }
        .order-total {
            font-weight: bold;
            font-size: 1.2rem;
            margin-top: 20px;
            text-align: right;
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
            <h3>Edit Order</h3>
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
        
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . '?id=' . $order_id; ?>" method="post">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Order #<?php echo $order_id; ?> - Edit Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="customer_id">Customer</label>
                                <select class="form-control" id="customer_id" name="customer_id">
                                    <option value="">Guest</option>
                                    <?php 
                                    mysqli_data_seek($customers, 0);
                                    while ($customer = mysqli_fetch_assoc($customers)): 
                                    ?>
                                        <option value="<?php echo $customer['customer_id']; ?>" <?php echo ($order['customer_id'] == $customer['customer_id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="employee_id">Staff</label>
                                <select class="form-control" id="employee_id" name="employee_id" required>
                                    <option value="">Select Staff</option>
                                    <?php 
                                    mysqli_data_seek($employees, 0);
                                    while ($employee = mysqli_fetch_assoc($employees)): 
                                    ?>
                                        <option value="<?php echo $employee['employee_id']; ?>" <?php echo ($order['employee_id'] == $employee['employee_id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="table_id">Table</label>
                                <select class="form-control" id="table_id" name="table_id" required>
                                    <option value="">Select Table</option>
                                    <?php 
                                    mysqli_data_seek($tables, 0);
                                    while ($table = mysqli_fetch_assoc($tables)): 
                                    ?>
                                        <option value="<?php echo $table['table_id']; ?>" <?php echo ($order['table_id'] == $table['table_id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars('Table ' . $table['table_id'] . ' (' . $table['capacity'] . ' person capacity)'); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="order_status">Order Status</label>
                                <select class="form-control" id="order_status" name="order_status" required>
                                    <?php foreach ($status_options as $value => $text): ?>
                                        <option value="<?php echo $value; ?>" <?php echo ($order['order_status'] == $value) ? 'selected' : ''; ?>>
                                            <?php echo $text; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Order Date:</label>
                                <p><?php echo date('d.m.Y H:i', strtotime($order['order_date'])); ?></p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Total Amount:</label>
                                <p><strong><?php echo number_format($order['total_amount'], 2); ?> ₺</strong></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Order Items</h5>
                </div>
                <div class="card-body">
                    <?php
                    $subtotal = 0;
                    if (mysqli_num_rows($orderDetails) > 0) {
                        while ($item = mysqli_fetch_assoc($orderDetails)) {
                            $item_total = $item['quantity'] * $item['price'];
                            $subtotal += $item_total;
                            $current_menu_item_id = intval($item['menu_item_id']);
                    ?>
                        <div class="order-item">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Product:</label>
                                        <p><?php echo htmlspecialchars($item['name']); ?> (<?php echo htmlspecialchars($item['category']); ?>)</p>
                                        <input type="hidden" name="item_menu_ids[]" value="<?php echo $current_menu_item_id; ?>">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label for="quantity_<?php echo $current_menu_item_id; ?>">Quantity:</label>
                                        <input type="number" class="form-control" id="quantity_<?php echo $current_menu_item_id; ?>" name="quantity[]" value="<?php echo intval($item['quantity']); ?>" min="1" required>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label>Unit Price:</label>
                                        <p><?php echo number_format(floatval($item['price']), 2); ?> ₺</p>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label>Total:</label>
                                        <p><strong><?php echo number_format($item_total, 2); ?> ₺</strong></p>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label for="special_instructions_<?php echo $current_menu_item_id; ?>">Special Request:</label>
                                        <textarea class="form-control" id="special_instructions_<?php echo $current_menu_item_id; ?>" name="special_instructions[]" rows="2"><?php echo htmlspecialchars($item['special_instructions'] ?? ''); ?></textarea>
                                    </div>
                                </div>
                                <div class="col-md-1 d-flex align-items-center">
                                    <button type="submit" class="btn btn-danger btn-sm" name="delete_item_menu_id" value="<?php echo $current_menu_item_id; ?>" onclick="return confirm('Are you sure you want to delete this item?');">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php
                        }
                    } else {
                        echo "<p class='text-center'>No items found for this order.</p>";
                    }
                    ?>
                    
                    <div class="order-total">
                        Total: <?php echo number_format($subtotal, 2); ?> ₺
                    </div>

                    <hr>
                    
                    <!-- Add New Item Section -->
                    <div class="card mt-4">
                        <div class="card-header">
                            <h5 class="mb-0">Add New Item</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="new_menu_item_id">Product:</label>
                                        <select class="form-control" id="new_menu_item_id" name="new_menu_item_id" onchange="updateNewPrice()">
                                            <option value="">-- Select Product --</option>
                                            <?php 
                                            mysqli_data_seek($menuItems, 0);
                                            while ($item = mysqli_fetch_assoc($menuItems)): 
                                            ?>
                                                <option value="<?php echo $item['menu_item_id']; ?>" data-price="<?php echo $item['price']; ?>">
                                                    <?php echo htmlspecialchars($item['name'] . ' (' . $item['category'] . ')'); ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label for="new_quantity">Quantity:</label>
                                        <input type="number" class="form-control" id="new_quantity" name="new_quantity" value="1" min="1" onchange="calculateNewTotal()">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label for="new_price">Unit Price:</label>
                                        <input type="number" step="0.01" class="form-control" id="new_price" name="new_price" readonly>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label for="new_total">Total:</label>
                                        <input type="text" class="form-control" id="new_total" readonly>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="new_special_instructions">Special Request:</label>
                                        <textarea class="form-control" id="new_special_instructions" name="new_special_instructions" rows="2"></textarea>
                                    </div>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-md-12 text-right">
                                    <button type="submit" name="add_new_item_action" value="true" class="btn btn-success">
                                        <i class="fas fa-plus"></i> Add Item
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <a href="view_order.php?id=<?php echo $order_id; ?>" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        // Update new item price
        function updateNewPrice() {
            const select = document.getElementById('new_menu_item_id');
            const selectedOption = select.options[select.selectedIndex];
            const price = selectedOption.getAttribute('data-price');
            
            document.getElementById('new_price').value = price;
            
            calculateNewTotal();
        }
        
        // Calculate new item total price
        function calculateNewTotal() {
            const quantity = document.getElementById('new_quantity').value;
            const price = document.getElementById('new_price').value;
            
            if (quantity && price) {
                const total = quantity * price;
                document.getElementById('new_total').value = total.toFixed(2) + ' ₺';
            } else {
                document.getElementById('new_total').value = '';
            }
        }
    </script>
</body>
</html> 