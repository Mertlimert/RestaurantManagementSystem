<div class="row mb-4">
    <div class="col-12">
        <div class="jumbotron bg-light p-5 rounded">
            <h1 class="display-4">Welcome to the Restaurant Management System!</h1>
            <p class="lead">A comprehensive system designed to easily manage your restaurant.</p>
            <hr class="my-4">
            <p>Menu management, order tracking, table reservations, and more, all through a single platform.</p>
            <?php if(!is_logged_in()): ?>
                <a class="btn btn-primary btn-lg" href="index.php?page=login" role="button">Login</a>
            <?php else: ?>
                <a class="btn btn-primary btn-lg" href="index.php?page=dashboard" role="button">Control Panel</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if(is_logged_in()): ?>
    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="fas fa-utensils fa-3x mb-3 text-primary"></i>
                    <h5 class="card-title">Menu Management</h5>
                    <p class="card-text">Add, edit, or delete menu items. Manage ingredients and prices.</p>
                    <a href="index.php?page=menu" class="btn btn-outline-primary">Manage Menu</a>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="fas fa-receipt fa-3x mb-3 text-success"></i>
                    <h5 class="card-title">Order Tracking</h5>
                    <p class="card-text">Create, track, and complete orders. Save customer information.</p>
                    <a href="index.php?page=orders" class="btn btn-outline-success">Manage Orders</a>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="fas fa-chair fa-3x mb-3 text-info"></i>
                    <h5 class="card-title">Tables & Reservations</h5>
                    <p class="card-text">Manage tables, track their status, and plan reservations.</p>
                    <a href="index.php?page=tables" class="btn btn-outline-info">Manage Tables</a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Recent Orders</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Order No</th>
                                    <th>Table</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $orders = get_orders(5);
                                if(mysqli_num_rows($orders) > 0):
                                    while($order = mysqli_fetch_assoc($orders)):
                                ?>
                                <tr>
                                    <td><?php echo $order['order_id']; ?></td>
                                    <td><?php echo $order['table_number']; ?></td>
                                    <td><?php echo number_format($order['total_amount'], 2) . ' ₺'; ?></td>
                                    <td>
                                        <?php if($order['order_status'] == 'Completed'): ?>
                                            <span class="badge bg-success">Completed</span>
                                        <?php elseif($order['order_status'] == 'Preparing'): ?>
                                            <span class="badge bg-warning text-dark">Preparing</span>
                                        <?php elseif($order['order_status'] == 'Pending'): ?>
                                            <span class="badge bg-danger">Pending</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php 
                                    endwhile;
                                else:
                                ?>
                                <tr>
                                    <td colspan="4" class="text-center">No orders found yet.</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <a href="index.php?page=orders" class="btn btn-sm btn-outline-primary">View All Orders</a>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Table Statuses</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php 
                        $tables = get_tables();
                        if(mysqli_num_rows($tables) > 0):
                            while($table = mysqli_fetch_assoc($tables)):
                                $status_class = '';
                                $status_text = '';
                                
                                if($table['table_status'] == 'Empty') {
                                    $status_class = 'bg-success';
                                    $status_text = 'Empty';
                                } elseif($table['table_status'] == 'Occupied') {
                                    $status_class = 'bg-danger';
                                    $status_text = 'Occupied';
                                } elseif($table['table_status'] == 'Reserved') {
                                    $status_class = 'bg-warning';
                                    $status_text = 'Reserved';
                                }
                        ?>
                        <div class="col-4 col-md-3 mb-3">
                            <div class="card text-center <?php echo $status_class; ?> text-white">
                                <div class="card-body py-2">
                                    <h5 class="mb-0">Table <?php echo $table['number']; ?></h5>
                                    <small><?php echo $status_text; ?></small>
                                </div>
                            </div>
                        </div>
                        <?php 
                            endwhile;
                        else:
                        ?>
                        <div class="col-12">
                            <p class="text-center">No table information found yet.</p>
                        </div>
                        <?php endif; ?>
                    </div>
                    <a href="index.php?page=tables" class="btn btn-sm btn-outline-success mt-2">Manage All Tables</a>
                </div>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="fas fa-tasks fa-3x mb-3 text-primary"></i>
                    <h5 class="card-title">Easy Management</h5>
                    <p class="card-text">Manage your restaurant operations from a single platform.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="fas fa-chart-line fa-3x mb-3 text-success"></i>
                    <h5 class="card-title">Performance Analytics</h5>
                    <p class="card-text">Track sales, employee performance, and inventory status.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="fas fa-user-friends fa-3x mb-3 text-info"></i>
                    <h5 class="card-title">Customer Satisfaction</h5>
                    <p class="card-text">Increase customer satisfaction with faster service and fewer errors.</p>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?> 