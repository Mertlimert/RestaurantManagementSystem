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
require_once '../models/Order.php';
require_once '../models/Customer.php';
require_once '../models/Employee.php';
require_once '../models/Tables.php';
require_once '../models/MenuItem.php';

// Modelleri oluştur
$orderModel = new Order($conn);
$customerModel = new Customer($conn);
$employeeModel = new Employee($conn);
$tableModel = new Tables($conn);
$menuItemModel = new MenuItem($conn);

// Form gönderildiğinde
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Form verilerini al
    $customer_id = $_POST["customer_id"];
    $employee_id = $_POST["employee_id"];
    $table_id = $_POST["table_id"];
    
    // Sipariş oluştur
    $order_id = $orderModel->addOrder($customer_id, $employee_id, $table_id);
    
    if ($order_id) {
        // Masanın durumunu "dolu" olarak güncelle
        $tableModel->updateTableStatus($table_id, "occupied");
        
        // Sipariş detayları için döngü
        if (isset($_POST["menu_item_id"]) && is_array($_POST["menu_item_id"])) {
            $success = true;
            
            for ($i = 0; $i < count($_POST["menu_item_id"]); $i++) {
                if (!empty($_POST["menu_item_id"][$i]) && !empty($_POST["quantity"][$i])) {
                    $menu_item_id = $_POST["menu_item_id"][$i];
                    $quantity = $_POST["quantity"][$i];
                    $price = $_POST["price"][$i];
                    $special_instructions = isset($_POST["special_instructions"][$i]) ? $_POST["special_instructions"][$i] : null;
                    
                    // Sipariş detayı ekle
                    if (!$orderModel->addOrderDetail($order_id, $menu_item_id, $quantity, $price, $special_instructions)) {
                        $success = false;
                    }
                }
            }
            
            // Sipariş toplamını güncelle
            $orderModel->updateOrderTotal($order_id);
            
            if ($success) {
                $_SESSION['success_msg'] = "Sipariş başarıyla oluşturuldu.";
                header("location: view_order.php?id=" . $order_id);
                exit();
            } else {
                // Sipariş detayları eklenemezse ana siparişi sil
                $orderModel->deleteOrder($order_id);
                $error_msg = "Sipariş detayları eklenirken bir hata oluştu.";
            }
        } else {
            // Sipariş detayları yoksa ana siparişi sil
            $orderModel->deleteOrder($order_id);
            $error_msg = "Lütfen en az bir menü öğesi ekleyin.";
        }
    } else {
        $error_msg = "Sipariş oluşturulurken bir hata oluştu.";
    }
}

// Müşterileri getir
$customers = $customerModel->getAllCustomers();

// Çalışanları getir
$employees = $employeeModel->getAllEmployees();

// Masaları getir
$tables = $tableModel->getAllTables();

// URL'den table_id parametresini al
$selected_table_id = isset($_GET['table_id']) ? $_GET['table_id'] : '';

// Menü öğelerini getir
$menuItems = $menuItemModel->getAllMenuItems();
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yeni Sipariş - Restoran Yönetim Sistemi</title>
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
        <h4 class="text-center mb-4">Admin Paneli</h4>
        <a href="index.php"><i class="fas fa-tachometer-alt mr-2"></i> Gösterge Paneli</a>
        <a href="customers.php"><i class="fas fa-users mr-2"></i> Müşteriler</a>
        <a href="employees.php"><i class="fas fa-user-tie mr-2"></i> Çalışanlar</a>
        <a href="tables.php"><i class="fas fa-chair mr-2"></i> Masalar</a>
        <a href="menu.php"><i class="fas fa-utensils mr-2"></i> Menü</a>
        <a href="ingredients.php"><i class="fas fa-carrot mr-2"></i> Malzemeler</a>
        <a href="orders.php" class="active"><i class="fas fa-clipboard-list mr-2"></i> Siparişler</a>
        <a href="reservations.php"><i class="fas fa-calendar-alt mr-2"></i> Rezervasyonlar</a>
        <a href="../logout.php"><i class="fas fa-sign-out-alt mr-2"></i> Çıkış</a>
    </div>
    
    <!-- Main Content -->
    <div class="content col-md-10">
        <div class="top-bar d-flex justify-content-between align-items-center">
            <h3>Yeni Sipariş Oluştur</h3>
            <div>
                <span class="mr-3">Hoş geldiniz, <?php echo htmlspecialchars($_SESSION["username"]); ?></span>
                <a href="../logout.php" class="btn btn-danger btn-sm"><i class="fas fa-sign-out-alt"></i> Çıkış</a>
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
                    <h5 class="mb-0">Sipariş Bilgileri</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="customer_id">Müşteri</label>
                                <select class="form-control" id="customer_id" name="customer_id">
                                    <option value="">Misafir</option>
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
                                <label for="employee_id">Personel</label>
                                <select class="form-control" id="employee_id" name="employee_id" required>
                                    <option value="">Personel Seçin</option>
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
                                <label for="table_id">Masa</label>
                                <select class="form-control" id="table_id" name="table_id" required>
                                    <option value="">Masa Seçin</option>
                                    <?php mysqli_data_seek($tables, 0); ?>
                                    <?php while ($table = mysqli_fetch_assoc($tables)): ?>
                                        <option value="<?php echo $table['table_id']; ?>" <?php echo $table['table_id'] == $selected_table_id ? 'selected' : ''; ?>>
                                            Masa <?php echo htmlspecialchars($table['table_id']); ?> (<?php echo $table['capacity']; ?> kişilik)
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Sipariş Öğeleri</h5>
                </div>
                <div class="card-body">
                    <div id="orderItems">
                        <!-- Sipariş öğeleri dinamik olarak buraya eklenecek -->
                    </div>
                    
                    <button type="button" class="btn btn-success add-item-btn" id="addItemBtn">
                        <i class="fas fa-plus"></i> Öğe Ekle
                    </button>
                    
                    <div class="order-total">
                        Toplam: <span id="orderTotal">0.00</span> ₺
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <a href="orders.php" class="btn btn-secondary">İptal</a>
                <button type="submit" class="btn btn-primary">Siparişi Oluştur</button>
            </div>
        </form>
    </div>
    
    <!-- Menü Öğesi Şablonu (JavaScript ile kullanılacak) -->
    <template id="orderItemTemplate">
        <div class="order-item">
            <i class="fas fa-times remove-item" onclick="removeItem(this)"></i>
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Menü Öğesi</label>
                        <select class="form-control menu-item-select" name="menu_item_id[]" required onchange="updatePrice(this)">
                            <option value="">Menü Öğesi Seçin</option>
                            <?php 
                            mysqli_data_seek($menuItems, 0); // Veritabanı sonucunu başa sar
                            while ($item = mysqli_fetch_assoc($menuItems)): 
                            ?>
                                <option value="<?php echo $item['menu_item_id']; ?>" data-price="<?php echo $item['price']; ?>">
                                    <?php echo htmlspecialchars($item['name'] . ' - ' . $item['category']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>Adet</label>
                        <input type="number" class="form-control quantity-input" name="quantity[]" value="1" min="1" required onchange="calculateItemTotal(this)">
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>Birim Fiyat (₺)</label>
                        <input type="number" step="0.01" class="form-control price-input" name="price[]" readonly>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>Toplam (₺)</label>
                        <input type="text" class="form-control item-total" readonly>
                    </div>
                </div>
                <div class="col-md-12 mt-2">
                    <div class="form-group">
                        <label>Özel İstek</label>
                        <textarea class="form-control" name="special_instructions[]" rows="2"></textarea>
                    </div>
                </div>
            </div>
        </div>
    </template>
    
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        // Sayfa yüklendiğinde bir öğe ekle
        document.addEventListener('DOMContentLoaded', function() {
            addItem();
        });
        
        // Yeni bir sipariş öğesi ekle
        function addItem() {
            const template = document.getElementById('orderItemTemplate');
            const orderItems = document.getElementById('orderItems');
            
            const clone = document.importNode(template.content, true);
            orderItems.appendChild(clone);
        }
        
        // Sipariş öğesini kaldır
        function removeItem(element) {
            const item = element.closest('.order-item');
            item.remove();
            updateOrderTotal();
        }
        
        // Menü öğesi seçildiğinde fiyatı güncelle
        function updatePrice(selectElement) {
            const item = selectElement.closest('.order-item');
            const selectedOption = selectElement.options[selectElement.selectedIndex];
            const price = selectedOption.getAttribute('data-price');
            
            const priceInput = item.querySelector('.price-input');
            priceInput.value = price;
            
            calculateItemTotal(item.querySelector('.quantity-input'));
        }
        
        // Öğe toplamını hesapla
        function calculateItemTotal(inputElement) {
            const item = inputElement.closest('.order-item');
            const quantity = item.querySelector('.quantity-input').value;
            const price = item.querySelector('.price-input').value;
            
            const total = quantity * price;
            item.querySelector('.item-total').value = total.toFixed(2);
            
            updateOrderTotal();
        }
        
        // Sipariş toplamını güncelle
        function updateOrderTotal() {
            let total = 0;
            const itemTotals = document.querySelectorAll('.item-total');
            
            itemTotals.forEach(function(element) {
                if (element.value) {
                    total += parseFloat(element.value);
                }
            });
            
            document.getElementById('orderTotal').textContent = total.toFixed(2);
        }
        
        // "Öğe Ekle" düğmesine tıklandığında
        document.getElementById('addItemBtn').addEventListener('click', addItem);
        
        // Form gönderilmeden önce doğrulama
        document.getElementById('orderForm').addEventListener('submit', function(event) {
            const menuItems = document.querySelectorAll('.menu-item-select');
            let hasItems = false;
            
            menuItems.forEach(function(element) {
                if (element.value) {
                    hasItems = true;
                }
            });
            
            if (!hasItems) {
                alert('Lütfen en az bir menü öğesi ekleyin.');
                event.preventDefault();
            }
        });
    </script>
</body>
</html> 