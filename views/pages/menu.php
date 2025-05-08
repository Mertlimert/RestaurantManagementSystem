<?php
// Sadece giriş yapan kullanıcılar erişebilir
if(!is_logged_in()) {
    redirect('index.php?page=login');
}

// Menü öğesi ekleme işlemi
if(isset($_POST['add_menu_item'])) {
    $name = clean_input($_POST['name']);
    $category = clean_input($_POST['category']);
    $price = clean_input($_POST['price']);
    $description = clean_input($_POST['description']);
    $dietary_info = clean_input($_POST['dietary_info']);
    
    // Basit validasyon
    if(empty($name) || empty($category) || empty($price)) {
        $error = "Menu item name, category, and price are required.";
    } else {
        // Benzersiz ID oluştur
        $menu_item_id = uniqid('MI_');
        
        // Veritabanına ekle
        $sql = "INSERT INTO MENU_ITEM (menu_item_id, name, category, price, description, dietary_info) 
                VALUES (?, ?, ?, ?, ?, ?)";
                
        if($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "sssdss", $menu_item_id, $name, $category, $price, $description, $dietary_info);
            
            if(mysqli_stmt_execute($stmt)) {
                $success = "Menu item added successfully.";
            } else {
                $error = "An error occurred while adding the menu item.";
            }
            
            mysqli_stmt_close($stmt);
        }
    }
}

// Menü öğelerini getir
$menu_items = array();
$categories = array();

$result = get_menu_items();
if(mysqli_num_rows($result) > 0) {
    while($row = mysqli_fetch_assoc($result)) {
        $menu_items[] = $row;
        // Kategorileri topla (eşsiz)
        if(!in_array($row['category'], $categories)) {
            $categories[] = $row['category'];
        }
    }
}
?>

<h2 class="mb-4">Menu Management</h2>

<div class="row mb-4">
    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Menu Items</h5>
                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addMenuItemModal">
                    <i class="fas fa-plus me-1"></i> Add New Item
                </button>
            </div>
            <div class="card-body">
                <?php if(isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if(isset($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <ul class="nav nav-tabs mb-3" id="menuTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="all-tab" data-bs-toggle="tab" data-bs-target="#all" type="button" role="tab">All</button>
                    </li>
                    <?php foreach($categories as $category): ?>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="<?php echo strtolower(str_replace(' ', '-', $category)); ?>-tab" data-bs-toggle="tab" data-bs-target="#<?php echo strtolower(str_replace(' ', '-', $category)); ?>" type="button" role="tab"><?php echo $category; ?></button>
                    </li>
                    <?php endforeach; ?>
                </ul>
                
                <div class="tab-content" id="menuTabContent">
                    <div class="tab-pane fade show active" id="all" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Category</th>
                                        <th>Price</th>
                                        <th>Dietary Information</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(count($menu_items) > 0): ?>
                                        <?php foreach($menu_items as $item): ?>
                                        <tr>
                                            <td><?php echo $item['name']; ?></td>
                                            <td><?php echo $item['category']; ?></td>
                                            <td><?php echo number_format($item['price'], 2) . ' ₺'; ?></td>
                                            <td><?php echo $item['dietary_info'] ? $item['dietary_info'] : '-'; ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-info view-menu-item" data-id="<?php echo $item['menu_item_id']; ?>" data-bs-toggle="modal" data-bs-target="#viewMenuItemModal">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-primary edit-menu-item" data-id="<?php echo $item['menu_item_id']; ?>" data-bs-toggle="modal" data-bs-target="#editMenuItemModal">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-danger delete-menu-item" data-id="<?php echo $item['menu_item_id']; ?>" data-bs-toggle="modal" data-bs-target="#deleteMenuItemModal">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center">Menu item not found.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <?php foreach($categories as $category): ?>
                    <div class="tab-pane fade" id="<?php echo strtolower(str_replace(' ', '-', $category)); ?>" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Price</th>
                                        <th>Dietary Information</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $filtered_items = array_filter($menu_items, function($item) use ($category) {
                                        return $item['category'] == $category;
                                    });
                                    ?>
                                    
                                    <?php if(count($filtered_items) > 0): ?>
                                        <?php foreach($filtered_items as $item): ?>
                                        <tr>
                                            <td><?php echo $item['name']; ?></td>
                                            <td><?php echo number_format($item['price'], 2) . ' ₺'; ?></td>
                                            <td><?php echo $item['dietary_info'] ? $item['dietary_info'] : '-'; ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-info view-menu-item" data-id="<?php echo $item['menu_item_id']; ?>" data-bs-toggle="modal" data-bs-target="#viewMenuItemModal">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-primary edit-menu-item" data-id="<?php echo $item['menu_item_id']; ?>" data-bs-toggle="modal" data-bs-target="#editMenuItemModal">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-danger delete-menu-item" data-id="<?php echo $item['menu_item_id']; ?>" data-bs-toggle="modal" data-bs-target="#deleteMenuItemModal">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="text-center">No menu items found in this category.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card shadow-sm">
            <div class="card-header">
                <h5 class="mb-0">Menu Statistics</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <h6>Total Number of Items</h6>
                    <h2 class="text-primary"><?php echo count($menu_items); ?></h2>
                </div>
                <div class="mb-3">
                    <h6>Distribution by Category</h6>
                    <ul class="list-group">
                        <?php foreach($categories as $category): ?>
                            <?php
                            $category_count = count(array_filter($menu_items, function($item) use ($category) {
                                return $item['category'] == $category;
                            }));
                            ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <?php echo $category; ?>
                                <span class="badge bg-primary rounded-pill"><?php echo $category_count; ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Menü Öğesi Ekleme Modal -->
<div class="modal fade" id="addMenuItemModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Menu Item</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="index.php?page=menu" method="post">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="name" class="form-label">Menu Item Name</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="category" class="form-label">Category</label>
                        <select class="form-select" id="category" name="category" required>
                            <option value="" selected disabled>Select Category</option>
                            <?php foreach($categories as $category): ?>
                                <option value="<?php echo $category; ?>"><?php echo $category; ?></option>
                            <?php endforeach; ?>
                            <option value="Yeni">+ New Category</option>
                        </select>
                        <div id="newCategoryField" class="mt-2 d-none">
                            <input type="text" class="form-control" id="new_category" name="new_category" placeholder="New category name">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="price" class="form-label">Price (₺)</label>
                        <input type="number" step="0.01" min="0" class="form-control" id="price" name="price" required>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="dietary_info" class="form-label">Dietary Information (e.g., Vegetarian, Gluten-Free)</label>
                        <input type="text" class="form-control" id="dietary_info" name="dietary_info">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_menu_item" class="btn btn-primary">Add</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Menü Öğesi Görüntüleme Modal -->
<div class="modal fade" id="viewMenuItemModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Menu Item Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <dl class="row">
                    <dt class="col-sm-4">Item Name</dt>
                    <dd class="col-sm-8" id="view_name"></dd>
                    
                    <dt class="col-sm-4">Category</dt>
                    <dd class="col-sm-8" id="view_category"></dd>
                    
                    <dt class="col-sm-4">Price</dt>
                    <dd class="col-sm-8" id="view_price"></dd>
                    
                    <dt class="col-sm-4">Description</dt>
                    <dd class="col-sm-8" id="view_description"></dd>
                    
                    <dt class="col-sm-4">Dietary Information</dt>
                    <dd class="col-sm-8" id="view_dietary_info"></dd>
                </dl>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Menü Öğesi Düzenleme Modal -->
<div class="modal fade" id="editMenuItemModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Menu Item</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="index.php?page=menu" method="post">
                <input type="hidden" name="menu_item_id" id="edit_menu_item_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_name" class="form-label">Item Name</label>
                        <input type="text" class="form-control" id="edit_name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_category" class="form-label">Category</label>
                        <select class="form-select" id="edit_category" name="category" required>
                             <option value="" selected disabled>Select Category</option>
                            <?php foreach($categories as $category): ?>
                                <option value="<?php echo $category; ?>"><?php echo $category; ?></option>
                            <?php endforeach; ?>
                            <option value="Yeni">+ New Category</option>
                        </select>
                        <div id="editNewCategoryField" class="mt-2 d-none">
                            <input type="text" class="form-control" id="edit_new_category" name="new_category" placeholder="New category name">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="edit_price" class="form-label">Price (₺)</label>
                        <input type="number" step="0.01" min="0" class="form-control" id="edit_price" name="price" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_description" class="form-label">Description</label>
                        <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="edit_dietary_info" class="form-label">Dietary Information</label>
                        <input type="text" class="form-control" id="edit_dietary_info" name="dietary_info">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update_menu_item" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Menü Öğesi Silme Modal -->
<div class="modal fade" id="deleteMenuItemModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Delete Menu Item</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="index.php?page=menu" method="post">
                <input type="hidden" name="menu_item_id" id="delete_menu_item_id">
                <div class="modal-body">
                    <p>Are you sure you want to delete this menu item?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="delete_menu_item" class="btn btn-danger">Delete</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Yeni kategori alanını göster/gizle
    document.getElementById('category').addEventListener('change', function() {
        const newCategoryField = document.getElementById('newCategoryField');
        if (this.value === 'Yeni') {
            newCategoryField.classList.remove('d-none');
        } else {
            newCategoryField.classList.add('d-none');
        }
    });
});
</script> 