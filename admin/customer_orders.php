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

// Modelleri oluştur
$orderModel = new Order($conn);
$customerModel = new Customer($conn);

// Müşterileri getir
$customers = $customerModel->getAllCustomers();

// Seçili müşteriyi ve siparişleri getir
$customer_id = isset($_GET['customer_id']) ? intval($_GET['customer_id']) : 0;
$orders = [];
$selected_customer = null;

if ($customer_id > 0) {
    $selected_customer = $customerModel->getCustomerById($customer_id);
}

// Tarih filtreleme
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : '';
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : '';

// Siparişleri al
if ($customer_id > 0) {
    if (!empty($from_date) || !empty($to_date)) {
        $orders = $orderModel->getOrdersByCustomerAndDateRange($customer_id, $from_date, $to_date);
    } else {
        $orders = $orderModel->getOrdersByCustomer($customer_id);
    }
}

// Müşteri istatistikleri
$total_spent = 0;
$order_count = 0;
$avg_order_value = 0;
$most_ordered_items = [];

if ($customer_id > 0 && mysqli_num_rows($orders) > 0) {
    // Toplam harcama ve sipariş sayısı
    $order_count = mysqli_num_rows($orders);
    $orders_data = mysqli_data_seek($orders, 0);
    
    while ($order = mysqli_fetch_assoc($orders)) {
        $total_spent += $order['total_amount'];
    }
    
    // Ortalama sipariş değeri
    $avg_order_value = $total_spent / $order_count;
    
    // En çok sipariş edilen ürünler
    $most_ordered_query = "SELECT m.name, SUM(od.quantity) as total_quantity
                           FROM OrderDetails od
                           JOIN Orders o ON od.order_id = o.order_id
                           JOIN MenuItems m ON od.menu_item_id = m.menu_item_id
                           WHERE o.customer_id = ?
                           GROUP BY od.menu_item_id
                           ORDER BY total_quantity DESC
                           LIMIT 5";
    
    $stmt = mysqli_prepare($conn, $most_ordered_query);
    mysqli_stmt_bind_param($stmt, "i", $customer_id);
    mysqli_stmt_execute($stmt);
    $most_ordered_items = mysqli_stmt_get_result($stmt);
    
    // Siparişleri tekrar çek (pointer'ı sıfırla)
    if (!empty($from_date) || !empty($to_date)) {
        $orders = $orderModel->getOrdersByCustomerAndDateRange($customer_id, $from_date, $to_date);
    } else {
        $orders = $orderModel->getOrdersByCustomer($customer_id);
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
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Müşteri Siparişleri - Restoran Yönetim Sistemi</title>
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
        .status-badge {
            font-size: 0.8rem;
            padding: 5px 10px;
            border-radius: 20px;
        }
        .status-ordered {
            background-color: #ffc107;
            color: black;
        }
        .status-preparing {
            background-color: #17a2b8;
            color: white;
        }
        .status-served {
            background-color: #28a745;
            color: white;
        }
        .status-paid {
            background-color: #6c757d;
            color: white;
        }
        .stat-card {
            border-left: 4px solid;
            min-height: 100px;
        }
        .stat-card.primary {
            border-left-color: #007bff;
        }
        .stat-card.success {
            border-left-color: #28a745;
        }
        .stat-card.warning {
            border-left-color: #ffc107;
        }
        .stat-card.info {
            border-left-color: #17a2b8;
        }
        .stat-value {
            font-size: 1.8rem;
            font-weight: bold;
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
        <a href="employee_orders.php"><i class="fas fa-clipboard-check mr-2"></i> Çalışan Siparişleri</a>
        <a href="customer_orders.php" class="active"><i class="fas fa-chart-bar mr-2"></i> Müşteri Analizi</a>
        <a href="tables.php"><i class="fas fa-chair mr-2"></i> Masalar</a>
        <a href="menu.php"><i class="fas fa-utensils mr-2"></i> Menü</a>
        <a href="ingredients.php"><i class="fas fa-carrot mr-2"></i> Malzemeler</a>
        <a href="orders.php"><i class="fas fa-clipboard-list mr-2"></i> Siparişler</a>
        <a href="reservations.php"><i class="fas fa-calendar-alt mr-2"></i> Rezervasyonlar</a>
        <a href="../logout.php"><i class="fas fa-sign-out-alt mr-2"></i> Çıkış</a>
    </div>
    
    <!-- Main Content -->
    <div class="content col-md-10">
        <div class="top-bar d-flex justify-content-between align-items-center">
            <h3>Müşteri Sipariş Analizi</h3>
            <div>
                <span class="mr-3">Hoş geldiniz, <?php echo htmlspecialchars($_SESSION["username"]); ?></span>
                <a href="../logout.php" class="btn btn-danger btn-sm"><i class="fas fa-sign-out-alt"></i> Çıkış</a>
            </div>
        </div>
        
        <!-- Mesajlar -->
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
        
        <!-- Filtreleme Kartı -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Müşteri ve Tarih Filtrele</h5>
            </div>
            <div class="card-body">
                <form method="get" action="">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="customer_id">Müşteri Seçin</label>
                                <select class="form-control" id="customer_id" name="customer_id" required>
                                    <option value="">-- Müşteri Seçin --</option>
                                    <?php while ($customer = mysqli_fetch_assoc($customers)): ?>
                                        <option value="<?php echo $customer['customer_id']; ?>" <?php echo ($customer_id == $customer['customer_id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="from_date">Başlangıç Tarihi</label>
                                <input type="date" class="form-control" id="from_date" name="from_date" value="<?php echo $from_date; ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="to_date">Bitiş Tarihi</label>
                                <input type="date" class="form-control" id="to_date" name="to_date" value="<?php echo $to_date; ?>">
                            </div>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary btn-block">Filtrele</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <?php if ($selected_customer): ?>
            <!-- Müşteri İstatistikleri Kartı -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><?php echo htmlspecialchars($selected_customer['first_name'] . ' ' . $selected_customer['last_name']); ?> - Müşteri İstatistikleri</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <div class="card stat-card primary">
                                <div class="card-body">
                                    <h6 class="text-muted">Toplam Harcama</h6>
                                    <div class="stat-value"><?php echo number_format($total_spent, 2); ?> ₺</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card stat-card success">
                                <div class="card-body">
                                    <h6 class="text-muted">Sipariş Sayısı</h6>
                                    <div class="stat-value"><?php echo $order_count; ?></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card stat-card warning">
                                <div class="card-body">
                                    <h6 class="text-muted">Ortalama Sipariş</h6>
                                    <div class="stat-value"><?php echo number_format($avg_order_value, 2); ?> ₺</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card stat-card info">
                                <div class="card-body">
                                    <h6 class="text-muted">Son Ziyaret</h6>
                                    <?php 
                                    $last_order = mysqli_data_seek($orders, 0);
                                    $first_order = mysqli_fetch_assoc($orders);
                                    if ($first_order) {
                                        echo '<div class="stat-value">'.date('d.m.Y', strtotime($first_order['order_date'])).'</div>';
                                    } else {
                                        echo '<div class="stat-value">-</div>';
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- En Çok Sipariş Edilen Ürünler -->
                    <?php if (mysqli_num_rows($most_ordered_items) > 0): ?>
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <h5>En Çok Sipariş Edilen Ürünler</h5>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Ürün</th>
                                            <th>Toplam Adet</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($item = mysqli_fetch_assoc($most_ordered_items)): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($item['name']); ?></td>
                                            <td><?php echo $item['total_quantity']; ?></td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Sipariş Listesi -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <?php if ($selected_customer): ?>
                        <?php echo htmlspecialchars($selected_customer['first_name'] . ' ' . $selected_customer['last_name']); ?> - Siparişleri
                    <?php else: ?>
                        Müşteri siparişlerini görmek için bir müşteri seçin
                    <?php endif; ?>
                </h5>
            </div>
            <div class="card-body">
                <?php if ($customer_id > 0): ?>
                    <?php if (mysqli_num_rows($orders) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Sipariş ID</th>
                                        <th>Tarih</th>
                                        <th>Masa</th>
                                        <th>Personel</th>
                                        <th>Ürünler</th>
                                        <th>Toplam Tutar</th>
                                        <th>Durum</th>
                                        <th>İşlemler</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    while ($order = mysqli_fetch_assoc($orders)) {
                                        $status_class = '';
                                        $status_text = '';
                                        
                                        switch ($order['order_status']) {
                                            case 'ordered':
                                                $status_class = 'status-ordered';
                                                $status_text = 'Sipariş Alındı';
                                                break;
                                            case 'preparing':
                                                $status_class = 'status-preparing';
                                                $status_text = 'Hazırlanıyor';
                                                break;
                                            case 'served':
                                                $status_class = 'status-served';
                                                $status_text = 'Servis Edildi';
                                                break;
                                            case 'paid':
                                                $status_class = 'status-paid';
                                                $status_text = 'Ödendi';
                                                break;
                                            default:
                                                $status_text = $order['order_status'];
                                        }
                                    ?>
                                        <tr>
                                            <td><?php echo $order['order_id']; ?></td>
                                            <td><?php echo date('d.m.Y H:i', strtotime($order['order_date'])); ?></td>
                                            <td>Masa <?php echo $order['table_id']; ?></td>
                                            <td><?php echo isset($order['employee_first_name']) ? htmlspecialchars($order['employee_first_name'] . ' ' . $order['employee_last_name']) : 'Atanmamış'; ?></td>
                                            <td>
                                                <?php
                                                    $order_details = $orderModel->getOrderDetails($order['order_id']);
                                                    $item_count = mysqli_num_rows($order_details);
                                                    echo $item_count . ' ürün';
                                                ?>
                                            </td>
                                            <td><?php echo number_format($order['total_amount'], 2); ?> ₺</td>
                                            <td><span class="badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span></td>
                                            <td>
                                                <a href="view_order.php?id=<?php echo $order['order_id']; ?>" class="btn btn-primary btn-sm"><i class="fas fa-eye"></i> Görüntüle</a>
                                            </td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">Bu müşteri için sipariş bulunmamaktadır.</div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="alert alert-warning">Lütfen bir müşteri seçin.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html> 