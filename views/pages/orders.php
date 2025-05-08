<?php
// Sadece giriş yapan kullanıcılar erişebilir
if(!is_logged_in()) {
    redirect('index.php?page=login');
}

// Sipariş ekleme/güncelleme
if(isset($_POST['add_order'])) {
    $customer_id = !empty($_POST['customer_id']) ? clean_input($_POST['customer_id']) : null;
    $table_id = clean_input($_POST['table_id']);
    $number_of_guests = clean_input($_POST['number_of_guests']);
    $server_id = $_SESSION['employee_id'];
    
    // Basit validasyon
    if(empty($table_id) || empty($number_of_guests)) {
        $error = "Table and number of guests are required.";
    } else {
        // Benzersiz ID oluştur
        $order_id = uniqid('ORD_');
        
        // Sipariş ekle
        $sql = "INSERT INTO `ORDER` (order_id, order_date, order_time, customer_id, table_id, server_id, order_status, number_of_guests) 
                VALUES (?, CURDATE(), CURTIME(), ?, ?, ?, 'Pending', ?)";
                
        if($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "ssssi", $order_id, $customer_id, $table_id, $server_id, $number_of_guests);
            
            if(mysqli_stmt_execute($stmt)) {
                // Masa durumunu güncelle
                $update_table = "UPDATE `TABLE` SET table_status = 'Occupied' WHERE table_id = ?";
                if($table_stmt = mysqli_prepare($conn, $update_table)) {
                    mysqli_stmt_bind_param($table_stmt, "s", $table_id);
                    mysqli_stmt_execute($table_stmt);
                    mysqli_stmt_close($table_stmt);
                }
                
                $success = "Order created successfully.";
            } else {
                $error = "An error occurred while creating the order.";
            }
            
            mysqli_stmt_close($stmt);
        }
    }
}

// Sipariş güncelleme
if(isset($_POST['update_order_status'])) {
    $order_id = clean_input($_POST['order_id']);
    $order_status = clean_input($_POST['order_status']);
    
    $sql = "UPDATE `ORDER` SET order_status = ? WHERE order_id = ?";
    
    if($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "ss", $order_status, $order_id);
        
        if(mysqli_stmt_execute($stmt)) {
            // Eğer sipariş tamamlandıysa masayı boşalt
            if($order_status == 'Completed') {
                $get_table = "SELECT table_id FROM `ORDER` WHERE order_id = ?";
                if($table_stmt = mysqli_prepare($conn, $get_table)) {
                    mysqli_stmt_bind_param($table_stmt, "s", $order_id);
                    mysqli_stmt_execute($table_stmt);
                    mysqli_stmt_bind_result($table_stmt, $table_id);
                    mysqli_stmt_fetch($table_stmt);
                    mysqli_stmt_close($table_stmt);
                    
                    $update_table = "UPDATE `TABLE` SET table_status = 'Empty' WHERE table_id = ?";
                    if($update_stmt = mysqli_prepare($conn, $update_table)) {
                        mysqli_stmt_bind_param($update_stmt, "s", $table_id);
                        mysqli_stmt_execute($update_stmt);
                        mysqli_stmt_close($update_stmt);
                    }
                }
            }
            
            $success = "Order status updated successfully.";
        } else {
            $error = "An error occurred while updating the order status.";
        }
        
        mysqli_stmt_close($stmt);
    }
}

// Siparişleri getir
$orders = get_orders();

// Müşterileri getir
$customers = array();
$sql = "SELECT customer_id, name FROM CUSTOMER ORDER BY name";
$result = mysqli_query($conn, $sql);
if(mysqli_num_rows($result) > 0) {
    while($row = mysqli_fetch_assoc($result)) {
        $customers[$row['customer_id']] = $row['name'];
    }
}

// Masaları getir
$tables = array();
$sql = "SELECT table_id, number FROM `TABLE` WHERE table_status = 'Empty' ORDER BY number";
$result = mysqli_query($conn, $sql);
if(mysqli_num_rows($result) > 0) {
    while($row = mysqli_fetch_assoc($result)) {
        $tables[$row['table_id']] = 'Table ' . $row['number'];
    }
}

?>

<h2 class="mb-4">Order Management</h2>

<div class="row mb-4">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Orders</h5>
                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addOrderModal">
                    <i class="fas fa-plus me-1"></i> New Order
                </button>
            </div>
            <div class="card-body">
                <?php if(isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if(isset($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Order No</th>
                                <th>Date/Time</th>
                                <th>Table</th>
                                <th>Server</th>
                                <th>Number of Guests</th>
                                <th>Status</th>
                                <th>Total</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(mysqli_num_rows($orders) > 0): ?>
                                <?php while($order = mysqli_fetch_assoc($orders)): ?>
                                <tr>
                                    <td><?php echo $order['order_id']; ?></td>
                                    <td><?php echo date('d.m.Y H:i', strtotime($order['order_date'] . ' ' . $order['order_time'])); ?></td>
                                    <td><?php echo $order['table_number']; ?></td>
                                    <td><?php echo $order['server_name']; ?></td>
                                    <td><?php echo $order['number_of_guests']; ?></td>
                                    <td>
                                        <?php if($order['order_status'] == 'Completed'): ?>
                                            <span class="badge bg-success">Completed</span>
                                        <?php elseif($order['order_status'] == 'Preparing'): ?>
                                            <span class="badge bg-warning text-dark">Preparing</span>
                                        <?php elseif($order['order_status'] == 'Pending'): ?>
                                            <span class="badge bg-danger">Pending</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo number_format($order['total_amount'], 2) . ' ₺'; ?></td>
                                    <td>
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                                Actions
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li>
                                                    <a class="dropdown-item" href="index.php?page=order_details&id=<?php echo $order['order_id']; ?>">
                                                        <i class="fas fa-eye me-1"></i> Details
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item" href="index.php?page=add_order_items&id=<?php echo $order['order_id']; ?>">
                                                        <i class="fas fa-utensils me-1"></i> Add Items
                                                    </a>
                                                </li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <button type="button" class="dropdown-item update-status" data-id="<?php echo $order['order_id']; ?>" data-bs-toggle="modal" data-bs-target="#updateStatusModal">
                                                        <i class="fas fa-edit me-1"></i> Update Status
                                                    </button>
                                                </li>
                                                <?php if($order['order_status'] == 'Completed'): ?>
                                                <li>
                                                    <a class="dropdown-item" href="index.php?page=payment&id=<?php echo $order['order_id']; ?>">
                                                        <i class="fas fa-credit-card me-1"></i> Payment
                                                    </a>
                                                </li>
                                                <?php endif; ?>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center">No orders found yet.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Yeni Sipariş Modal -->
<div class="modal fade" id="addOrderModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create New Order</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="index.php?page=orders" method="post">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="table_id" class="form-label">Table</label>
                        <select class="form-select" id="table_id" name="table_id" required>
                            <option value="" selected disabled>Select Table</option>
                            <?php foreach($tables as $id => $name): ?>
                                <option value="<?php echo $id; ?>"><?php echo $name; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="customer_id" class="form-label">Customer (Optional)</label>
                        <select class="form-select" id="customer_id" name="customer_id">
                            <option value="" selected>Select Customer</option>
                            <?php foreach($customers as $id => $name): ?>
                                <option value="<?php echo $id; ?>"><?php echo $name; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">If the customer is not registered, you can leave it blank.</div>
                    </div>
                    <div class="mb-3">
                        <label for="number_of_guests" class="form-label">Number of Guests</label>
                        <input type="number" min="1" class="form-control" id="number_of_guests" name="number_of_guests" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_order" class="btn btn-primary">Create Order</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Durum Güncelleme Modal -->
<div class="modal fade" id="updateStatusModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Status Update</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="index.php?page=orders" method="post">
                <input type="hidden" name="order_id" id="update_order_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="order_status" class="form-label">Status</label>
                        <select class="form-select" id="order_status" name="order_status" required>
                            <option value="" selected disabled>Select Order Status</option>
                            <option value="Bekliyor">Pending</option>
                            <option value="Hazırlanıyor">Preparing</option>
                            <option value="Tamamlandı">Completed</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update_order_status" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Durum güncelleme modalına sipariş id'sini aktar
    const updateButtons = document.querySelectorAll('.update-status');
    updateButtons.forEach(button => {
        button.addEventListener('click', function() {
            const orderId = this.getAttribute('data-id');
            document.getElementById('update_order_id').value = orderId;
        });
    });
});
</script> 