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

// ID parametresi kontrolü
if (isset($_GET["id"]) && !empty(trim($_GET["id"]))) {
    // URL'den ID parametresini al
    $order_id = trim($_GET["id"]);

    // Sipariş bilgilerini getir
    $order = $orderModel->getOrderById($order_id);

    if (!$order) {
        // Sipariş bulunamadı, siparişler sayfasına yönlendir
        $_SESSION['error_msg'] = "Sipariş bulunamadı.";
        header("location: orders.php");
        exit();
    }
    
    // Sipariş detaylarını getir
    $orderDetails = $orderModel->getOrderDetails($order_id);
} else {
    // URL'de ID parametresi yok, siparişler sayfasına yönlendir
    header("location: orders.php");
    exit();
}

// Hata ve başarı mesajları
$error_msg = '';
$success_msg = '';

// Form gönderildiğinde
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Form verilerini al
    $customer_id = $_POST["customer_id"];
    $employee_id = $_POST["employee_id"];
    $table_id = $_POST["table_id"];
    $order_status = $_POST["order_status"];
    
    // Sipariş durumunu güncelle
    if ($orderModel->updateOrderStatus($order_id, $order_status)) {
        $success_msg = "Sipariş durumu güncellendi.";
    } else {
        $error_msg = "Sipariş durumu güncellenirken bir hata oluştu.";
    }
    
    // Sipariş bilgilerini güncelle (bu fonksiyonu Order sınıfına eklemeniz gerekebilir)
    $sql = "UPDATE Orders SET customer_id = ?, employee_id = ?, table_id = ? WHERE order_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "iiii", $customer_id, $employee_id, $table_id, $order_id);
    
    if (mysqli_stmt_execute($stmt)) {
        $success_msg = "Sipariş bilgileri başarıyla güncellendi.";
        
        // Değişiklikleri yansıtmak için siparişi yeniden yükle
        $order = $orderModel->getOrderById($order_id);
    } else {
        $error_msg = "Sipariş bilgileri güncellenirken bir hata oluştu.";
    }
    
    // Sipariş detayları güncellemesi
    if (isset($_POST["detail_id"]) && isset($_POST["quantity"]) && isset($_POST["special_instructions"])) {
        $details_count = count($_POST["detail_id"]);
        
        for ($i = 0; $i < $details_count; $i++) {
            $detail_id = $_POST["detail_id"][$i];
            $quantity = $_POST["quantity"][$i];
            $special_instructions = $_POST["special_instructions"][$i];
            
            // Sipariş detayını güncelle
            $sql = "UPDATE OrderDetails SET quantity = ?, special_instructions = ? WHERE id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "isi", $quantity, $special_instructions, $detail_id);
            mysqli_stmt_execute($stmt);
        }
        
        // Sipariş toplamını güncelle
        $orderModel->updateOrderTotal($order_id);
        
        // Güncellenmiş sipariş detaylarını getir
        $orderDetails = $orderModel->getOrderDetails($order_id);
        
        if (!$error_msg) {
            $success_msg = "Sipariş başarıyla güncellendi.";
        }
    }
}

// Müşterileri getir
$customers = $customerModel->getAllCustomers();

// Çalışanları getir
$employees = $employeeModel->getAllEmployees();

// Masaları getir
$tables = $tableModel->getAllTables();

// Menü öğelerini getir
$menuItems = $menuItemModel->getAllMenuItems();

// Statu durumları
$status_options = array(
    'new' => 'Yeni',
    'preparing' => 'Hazırlanıyor',
    'ready' => 'Hazır',
    'delivered' => 'Teslim Edildi',
    'paid' => 'Ödendi',
    'cancelled' => 'İptal Edildi'
);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sipariş Düzenle - Restoran Yönetim Sistemi</title>
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
            <h3>Sipariş Düzenle</h3>
            <div>
                <span class="mr-3">Hoş geldiniz, <?php echo htmlspecialchars($_SESSION["username"]); ?></span>
                <a href="../logout.php" class="btn btn-danger btn-sm"><i class="fas fa-sign-out-alt"></i> Çıkış</a>
            </div>
        </div>
        
        <!-- Mesajlar -->
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
                    <h5 class="mb-0">Sipariş #<?php echo $order_id; ?> - Bilgileri Düzenle</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="customer_id">Müşteri</label>
                                <select class="form-control" id="customer_id" name="customer_id">
                                    <option value="">Misafir</option>
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
                                <label for="employee_id">Personel</label>
                                <select class="form-control" id="employee_id" name="employee_id" required>
                                    <option value="">Personel Seçin</option>
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
                                <label for="table_id">Masa</label>
                                <select class="form-control" id="table_id" name="table_id" required>
                                    <option value="">Masa Seçin</option>
                                    <?php 
                                    mysqli_data_seek($tables, 0);
                                    while ($table = mysqli_fetch_assoc($tables)): 
                                    ?>
                                        <option value="<?php echo $table['table_id']; ?>" <?php echo ($order['table_id'] == $table['table_id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($table['table_number'] . ' (' . $table['capacity'] . ' kişilik)'); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="order_status">Sipariş Durumu</label>
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
                                <label>Sipariş Tarihi:</label>
                                <p><?php echo date('d.m.Y H:i', strtotime($order['order_date'])); ?></p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Toplam Tutar:</label>
                                <p><strong><?php echo number_format($order['total_amount'], 2); ?> ₺</strong></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Sipariş Öğeleri</h5>
                </div>
                <div class="card-body">
                    <?php
                    $subtotal = 0;
                    if (mysqli_num_rows($orderDetails) > 0) {
                        while ($item = mysqli_fetch_assoc($orderDetails)) {
                            $item_total = $item['quantity'] * $item['price'];
                            $subtotal += $item_total;
                    ?>
                        <div class="order-item">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Ürün:</label>
                                        <p><?php echo htmlspecialchars($item['name']); ?> (<?php echo htmlspecialchars($item['category']); ?>)</p>
                                        <input type="hidden" name="detail_id[]" value="<?php echo $item['id']; ?>">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label for="quantity_<?php echo $item['id']; ?>">Adet:</label>
                                        <input type="number" class="form-control" id="quantity_<?php echo $item['id']; ?>" name="quantity[]" value="<?php echo $item['quantity']; ?>" min="1" required>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label>Birim Fiyat:</label>
                                        <p><?php echo number_format($item['price'], 2); ?> ₺</p>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label>Toplam:</label>
                                        <p><strong><?php echo number_format($item_total, 2); ?> ₺</strong></p>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="special_instructions_<?php echo $item['id']; ?>">Özel İstek:</label>
                                        <textarea class="form-control" id="special_instructions_<?php echo $item['id']; ?>" name="special_instructions[]" rows="2"><?php echo htmlspecialchars($item['special_instructions']); ?></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php
                        }
                    } else {
                        echo "<p class='text-center'>Bu sipariş için ürün bulunmamaktadır.</p>";
                    }
                    ?>
                    
                    <div class="order-total">
                        Toplam: <?php echo number_format($subtotal, 2); ?> ₺
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <a href="view_order.php?id=<?php echo $order_id; ?>" class="btn btn-secondary">İptal</a>
                <button type="submit" class="btn btn-primary">Değişiklikleri Kaydet</button>
            </div>
        </form>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html> 