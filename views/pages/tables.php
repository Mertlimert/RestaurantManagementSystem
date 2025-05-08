<?php
// Sadece giriş yapan kullanıcılar erişebilir
if(!is_logged_in()) {
    redirect('index.php?page=login');
}

// Masa ekleme işlemi
if(isset($_POST['add_table'])) {
    $number = clean_input($_POST['number']);
    $capacity = clean_input($_POST['capacity']);
    $table_status = 'Empty';
    
    // Basit validasyon
    if(empty($number) || empty($capacity)) {
        $error = "Table number and capacity are required.";
    } else {
        // Benzersiz ID oluştur
        $table_id = uniqid('TBL_');
        
        // Veritabanına ekle
        $sql = "INSERT INTO `TABLE` (table_id, number, capacity, table_status) VALUES (?, ?, ?, ?)";
                
        if($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "siis", $table_id, $number, $capacity, $table_status);
            
            if(mysqli_stmt_execute($stmt)) {
                $success = "Table added successfully.";
            } else {
                $error = "An error occurred while adding the table.";
            }
            
            mysqli_stmt_close($stmt);
        }
    }
}

// Masa güncelleme
if(isset($_POST['update_table_status'])) {
    $table_id = clean_input($_POST['table_id']);
    $table_status = clean_input($_POST['table_status']);
    
    $sql = "UPDATE `TABLE` SET table_status = ? WHERE table_id = ?";
    
    if($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "ss", $table_status, $table_id);
        
        if(mysqli_stmt_execute($stmt)) {
            $success = "Table status updated successfully.";
        } else {
            $error = "An error occurred while updating the table status.";
        }
        
        mysqli_stmt_close($stmt);
    }
}

// Masaları getir
$tables = array();
$sql = "SELECT * FROM `TABLE` ORDER BY number";
$result = mysqli_query($conn, $sql);
if(mysqli_num_rows($result) > 0) {
    while($row = mysqli_fetch_assoc($result)) {
        $tables[] = $row;
    }
}
?>

<h2 class="mb-4">Table Management</h2>

<div class="row mb-4">
    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Tables</h5>
                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addTableModal">
                    <i class="fas fa-plus me-1"></i> Add New Table
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
                                <th>Table No</th>
                                <th>Capacity</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(count($tables) > 0): ?>
                                <?php foreach($tables as $table): ?>
                                <tr>
                                    <td><?php echo $table['number']; ?></td>
                                    <td><?php echo $table['capacity']; ?> people</td>
                                    <td>
                                        <?php if($table['table_status'] == 'Empty'): ?>
                                            <span class="badge bg-success">Empty</span>
                                        <?php elseif($table['table_status'] == 'Occupied'): ?>
                                            <span class="badge bg-danger">Occupied</span>
                                        <?php elseif($table['table_status'] == 'Reserved'): ?>
                                            <span class="badge bg-warning text-dark">Reserved</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-sm btn-outline-primary edit-table" data-id="<?php echo $table['table_id']; ?>" data-number="<?php echo $table['number']; ?>" data-capacity="<?php echo $table['capacity']; ?>" data-bs-toggle="modal" data-bs-target="#editTableModal">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-success update-status" data-id="<?php echo $table['table_id']; ?>" data-status="<?php echo $table['table_status']; ?>" data-bs-toggle="modal" data-bs-target="#updateStatusModal">
                                                <i class="fas fa-sync-alt"></i>
                                            </button>
                                            <?php if($table['table_status'] == 'Empty'): ?>
                                            <a href="index.php?page=orders&table_id=<?php echo $table['table_id']; ?>" class="btn btn-sm btn-outline-info">
                                                <i class="fas fa-utensils"></i>
                                            </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center">No tables added yet.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card shadow-sm">
            <div class="card-header">
                <h5 class="mb-0">Table Status</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php if(count($tables) > 0): ?>
                        <?php 
                        $status_counts = array(
                            'Empty' => 0,
                            'Occupied' => 0,
                            'Reserved' => 0
                        );
                        
                        foreach($tables as $table) {
                            $status_counts[$table['table_status']]++;
                        }
                        ?>
                        
                        <div class="col-md-12 mb-3">
                            <div class="progress" style="height: 30px;">
                                <?php if($status_counts['Empty'] > 0): ?>
                                <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo ($status_counts['Empty'] / count($tables)) * 100; ?>%" title="Empty: <?php echo $status_counts['Empty']; ?>">
                                    <?php echo $status_counts['Empty']; ?> Empty
                                </div>
                                <?php endif; ?>
                                
                                <?php if($status_counts['Occupied'] > 0): ?>
                                <div class="progress-bar bg-danger" role="progressbar" style="width: <?php echo ($status_counts['Occupied'] / count($tables)) * 100; ?>%" title="Occupied: <?php echo $status_counts['Occupied']; ?>">
                                    <?php echo $status_counts['Occupied']; ?> Occupied
                                </div>
                                <?php endif; ?>
                                
                                <?php if($status_counts['Reserved'] > 0): ?>
                                <div class="progress-bar bg-warning" role="progressbar" style="width: <?php echo ($status_counts['Reserved'] / count($tables)) * 100; ?>%" title="Reserved: <?php echo $status_counts['Reserved']; ?>">
                                    <?php echo $status_counts['Reserved']; ?> Reserved
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="col-md-12">
                        <div class="list-group">
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="fas fa-circle text-success me-2"></i> Empty Tables
                                </div>
                                <span class="badge bg-success rounded-pill"><?php echo $status_counts['Empty']; ?></span>
                            </div>
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="fas fa-circle text-danger me-2"></i> Occupied Tables
                                </div>
                                <span class="badge bg-danger rounded-pill"><?php echo $status_counts['Occupied']; ?></span>
                            </div>
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="fas fa-circle text-warning me-2"></i> Reserved Tables
                                </div>
                                <span class="badge bg-warning rounded-pill"><?php echo $status_counts['Reserved']; ?></span>
                            </div>
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="fas fa-circle text-dark me-2"></i> Total Tables
                                </div>
                                <span class="badge bg-dark rounded-pill"><?php echo count($tables); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Masa Ekleme Modal -->
<div class="modal fade" id="addTableModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Table</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="index.php?page=tables" method="post">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="number" class="form-label">Table Number</label>
                        <input type="number" min="1" class="form-control" id="number" name="number" required>
                    </div>
                    <div class="mb-3">
                        <label for="capacity" class="form-label">Capacity (People)</label>
                        <input type="number" min="1" class="form-control" id="capacity" name="capacity" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_table" class="btn btn-primary">Add</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Masa Düzenleme Modal -->
<div class="modal fade" id="editTableModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Table</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="index.php?page=tables" method="post">
                <input type="hidden" name="table_id" id="edit_table_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_number" class="form-label">Table Number</label>
                        <input type="number" min="1" class="form-control" id="edit_number" name="number" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_capacity" class="form-label">Capacity (People)</label>
                        <input type="number" min="1" class="form-control" id="edit_capacity" name="capacity" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update_table" class="btn btn-primary">Save</button>
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
                <h5 class="modal-title">Update Table Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="index.php?page=tables" method="post">
                <input type="hidden" name="table_id" id="update_table_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="table_status" class="form-label">New Status</label>
                        <select class="form-select" id="table_status" name="table_status" required>
                            <option value="" selected disabled>Select</option>
                            <option value="Boş">Empty</option>
                            <option value="Dolu">Occupied</option>
                            <option value="Rezerve">Reserved</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update_table_status" class="btn btn-primary">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Durum güncelleme modalına masa id'sini aktar
    const updateButtons = document.querySelectorAll('.update-status');
    updateButtons.forEach(button => {
        button.addEventListener('click', function() {
            const tableId = this.getAttribute('data-id');
            document.getElementById('update_table_id').value = tableId;
        });
    });
});
</script> 