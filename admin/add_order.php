<?php
// Disable error reporting
error_reporting(0);
ini_set('display_errors', 0);

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

// When form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $customer_id = $_POST["customer_id"];
    $employee_id = $_POST["employee_id"];
    $table_id = $_POST["table_id"];
    
    // Create order
    $order_id = $orderModel->addOrder($customer_id, $employee_id, $table_id);
    
    if ($order_id) {
        // Update table status to "occupied"
        $tableModel->updateTableStatus($table_id, "occupied");
        
        // Loop for order details
        if (isset($_POST["menu_item_id"]) && is_array($_POST["menu_item_id"])) {
            $success = true;
            
            for ($i = 0; $i < count($_POST["menu_item_id"]); $i++) {
                if (!empty($_POST["menu_item_id"][$i]) && !empty($_POST["quantity"][$i])) {
                    $menu_item_id = $_POST["menu_item_id"][$i];
                    $quantity = $_POST["quantity"][$i];
                    
                    // Fetch the current price directly from the MenuItems table
                    $menuItemQuery = "SELECT price FROM MenuItems WHERE menu_item_id = ?";
                    $menuItemStmt = mysqli_prepare($conn, $menuItemQuery);
                    mysqli_stmt_bind_param($menuItemStmt, "i", $menu_item_id);
                    mysqli_stmt_execute($menuItemStmt);
                    $menuItemResult = mysqli_stmt_get_result($menuItemStmt);
                    $menuItemData = mysqli_fetch_assoc($menuItemResult);
                    $price = $menuItemData['price']; // Get the actual price from menu item
                    
                    $special_instructions = isset($_POST["special_instructions"][$i]) ? $_POST["special_instructions"][$i] : null;
                    
                    // Add order detail
                    if (!$orderModel->addOrderDetail($order_id, $menu_item_id, $quantity, $price, $special_instructions)) {
                        $success = false;
                    }
                }
            }
            
            // Update order total
            $orderModel->updateOrderTotal($order_id);
            
            if ($success) {
                $_SESSION['success_msg'] = "Order created successfully.";
                header("location: view_order.php?id=" . $order_id);
                exit();
            } else {
                // If order details cannot be added, delete the main order
                $orderModel->deleteOrder($order_id);
                $error_msg = "An error occurred while adding order details.";
            }
        } else {
            // If no order details, delete the main order
            $orderModel->deleteOrder($order_id);
            $error_msg = "Please add at least one menu item.";
        }
    } else {
        $error_msg = "An error occurred while creating the order.";
    }
}

// Get customers
$customers = $customerModel->getAllCustomers();

// Get employees
$employees = $employeeModel->getAllEmployees();

// Get tables
$tables = $tableModel->getAllTables();

// Get table_id parameter from URL
$selected_table_id = isset($_GET['table_id']) ? $_GET['table_id'] : '';

// Get menu items
$menuItems = $menuItemModel->getAllMenuItems();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Order - Restaurant Management System</title>
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
            position: relative;
        }
        .remove-item {
            position: absolute;
            top: 10px;
            right: 10px;
            color: #dc3545;
            cursor: pointer;
            font-size: 18px;
        }
        .add-item-btn {
            margin-bottom: 20px;
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
            <h3>Create New Order</h3>
            <div>
                <span class="mr-3">Welcome, <?php echo htmlspecialchars($_SESSION["username"]); ?></span>
                <a href="../logout.php" class="btn btn-danger btn-sm"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
        
        <?php if(isset($error_msg)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error_msg; ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>
        
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" id="orderForm">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Order Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="customer_id">Customer</label>
                                <select class="form-control" id="customer_id" name="customer_id">
                                    <option value="">Guest</option>
                                    <?php while ($customer = mysqli_fetch_assoc($customers)): ?>
                                        <option value="<?php echo $customer['customer_id']; ?>">
                                            <?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="employee_id">Staff</label>
                                <select class="form-control" id="employee_id" name="employee_id" required>
                                    <option value="">Select Staff</option>
                                    <?php while ($employee = mysqli_fetch_assoc($employees)): ?>
                                        <option value="<?php echo $employee['employee_id']; ?>">
                                            <?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="table_id">Table</label>
                                <select class="form-control" id="table_id" name="table_id" required>
                                    <option value="">Select Table</option>
                                    <?php mysqli_data_seek($tables, 0); ?>
                                    <?php while ($table = mysqli_fetch_assoc($tables)): ?>
                                        <option value="<?php echo $table['table_id']; ?>" <?php echo $table['table_id'] == $selected_table_id ? 'selected' : ''; ?>>
                                            Table <?php echo $table['table_id']; ?> (<?php echo $table['capacity']; ?> person capacity)
                                        </option>
                                    <?php endwhile; ?>
                                </select>
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
                    <div id="orderItemsContainer">
                        <!-- Order items will be added here by JavaScript -->
                    </div>
                    <button type="button" id="addItemBtn" class="btn btn-primary add-item-btn"><i class="fas fa-plus"></i> Add Item</button>
                    <div class="order-total">Total: <span id="totalAmount">0.00</span> ₺</div>
                </div>
            </div>
            
            <div class="mt-3">
                <a href="orders.php" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-success">Create Order</button>
            </div>
        </form>
    </div>
    
    <!-- Template for new order item -->
    <template id="orderItemTemplate">
        <div class="order-item">
            <i class="fas fa-trash removeItem" title="Remove Item"></i>
            <div class="row">
                <div class="col-md-5">
                    <div class="form-group">
                        <label for="menu_item_id_0">Product</label>
                        <select class="form-control menu-item-select" name="menu_item_id[]" required>
                            <option value="">Select Product</option>
                            <?php 
                            mysqli_data_seek($menuItems, 0); // Reset pointer
                            while ($item = mysqli_fetch_assoc($menuItems)): 
                            ?>
                                <option value="<?php echo $item['menu_item_id']; ?>" data-price="<?php echo $item['price']; ?>">
                                    <?php echo htmlspecialchars($item['name']) . " (" . $item['price'] . " ₺)"; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label for="quantity_0">Quantity</label>
                        <input type="number" class="form-control quantity-input" name="quantity[]" value="1" min="1" required>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label for="price_0">Price</label>
                        <input type="text" class="form-control price-input" name="price[]" readonly>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="special_instructions_0">Special Notes</label>
                        <input type="text" class="form-control" name="special_instructions[]">
                    </div>
                </div>
            </div>
        </div>
    </template>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const orderItemsContainer = document.getElementById('orderItemsContainer');
            const addItemBtn = document.getElementById('addItemBtn');
            const orderItemTemplate = document.getElementById('orderItemTemplate');
            let itemCounter = 0;

            // Function to add a new item
            function addNewItem() {
                const newItem = orderItemTemplate.content.cloneNode(true);
                const orderItemDiv = newItem.querySelector('.order-item');
                
                // Update IDs and names for uniqueness
                newItem.querySelectorAll('[id^="menu_item_id_"]').forEach(el => el.id = 'menu_item_id_' + itemCounter);
                newItem.querySelectorAll('[for^="menu_item_id_"]').forEach(el => el.htmlFor = 'menu_item_id_' + itemCounter);
                
                newItem.querySelectorAll('[id^="quantity_"]').forEach(el => el.id = 'quantity_' + itemCounter);
                newItem.querySelectorAll('[for^="quantity_"]').forEach(el => el.htmlFor = 'quantity_' + itemCounter);
                
                newItem.querySelectorAll('[id^="price_"]').forEach(el => el.id = 'price_' + itemCounter);
                newItem.querySelectorAll('[for^="price_"]').forEach(el => el.htmlFor = 'price_' + itemCounter);

                newItem.querySelectorAll('[id^="special_instructions_"]').forEach(el => el.id = 'special_instructions_' + itemCounter);
                newItem.querySelectorAll('[for^="special_instructions_"]').forEach(el => el.htmlFor = 'special_instructions_' + itemCounter);

                orderItemsContainer.appendChild(newItem);
                
                // Attach event listeners to the new item
                const newOrderItem = orderItemsContainer.lastElementChild;
                attachEventListenersToItem(newOrderItem.previousElementSibling); // The actual div is inside the template's document fragment

                itemCounter++;
                updateTotal();
            }

            // Function to remove an item
            function removeItem(event) {
                if (event.target.classList.contains('removeItem')) {
                    event.target.closest('.order-item').remove();
                    updateTotal();
                }
            }

            // Function to auto-fill price and update total for an item
            function updateItemPriceAndTotal(itemDiv) {
                const menuItemSelect = itemDiv.querySelector('.menu-item-select');
                const quantityInput = itemDiv.querySelector('.quantity-input');
                const priceInput = itemDiv.querySelector('.price-input');
                
                const selectedOption = menuItemSelect.options[menuItemSelect.selectedIndex];
                const price = selectedOption ? parseFloat(selectedOption.dataset.price) : 0;
                const quantity = parseInt(quantityInput.value) || 0;
                
                priceInput.value = price.toFixed(2);
                // No individual total for item, only overall total
                updateTotal();
            }
            
            // Function to update the grand total
            function updateTotal() {
                let totalAmount = 0;
                document.querySelectorAll('.order-item').forEach(item => {
                    const priceInput = item.querySelector('.price-input');
                    const quantityInput = item.querySelector('.quantity-input');
                    if (priceInput && quantityInput) {
                        const price = parseFloat(priceInput.value) || 0;
                        const quantity = parseInt(quantityInput.value) || 0;
                        totalAmount += price * quantity;
                    }
                });
                document.getElementById('totalAmount').textContent = totalAmount.toFixed(2);
            }

            // Attach event listeners to an item
            function attachEventListenersToItem(itemDiv) {
                const menuItemSelect = itemDiv.querySelector('.menu-item-select');
                const quantityInput = itemDiv.querySelector('.quantity-input');

                if (menuItemSelect) {
                    menuItemSelect.addEventListener('change', () => updateItemPriceAndTotal(itemDiv));
                }
                if (quantityInput) {
                    quantityInput.addEventListener('input', () => updateItemPriceAndTotal(itemDiv));
                }
            }

            // Event listener for adding a new item
            addItemBtn.addEventListener('click', addNewItem);

            // Event listener for removing an item (delegated)
            orderItemsContainer.addEventListener('click', removeItem);

            // Add an initial item when the page loads
            addNewItem();
        });
    </script>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html> 