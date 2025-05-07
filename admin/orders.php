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

// Model sınıfını dahil et
require_once '../models/Order.php';
require_once '../models/Tables.php';

// Sipariş modeli oluştur
$orderModel = new Order($conn);
$tableModel = new Tables($conn);

// URL'den table_id parametresini kontrol et
$table_filter = isset($_GET['table_id']) ? $_GET['table_id'] : null;

// Sipariş silme işlemi
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $order_id = $_GET['delete'];
    if ($orderModel->deleteOrder($order_id)) {
        $success_msg = "Sipariş başarıyla silindi.";
    } else {
        $error_msg = "Sipariş silinemedi.";
    }
}

// Durumu güncelle
if (isset($_GET['update_status']) && !empty($_GET['update_status']) && isset($_GET['status']) && !empty($_GET['status'])) {
    $order_id = $_GET['update_status'];
    $new_status = $_GET['status'];
    if ($orderModel->updateOrderStatus($order_id, $new_status)) {
        $success_msg = "Sipariş durumu başarıyla güncellendi.";
    } else {
        $error_msg = "Sipariş durumu güncellenemedi.";
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

// Tüm siparişleri getir (veya belirli bir masanın siparişlerini getir)
if ($table_filter) {
    $orders = $orderModel->getOrdersByTable($table_filter);
    // Masa bilgisini al
    $table = $tableModel->getTableById($table_filter);
    $filter_title = "Masa " . $table_filter;
} else {
    $orders = $orderModel->getAllOrders();
    $filter_title = "Tüm Siparişler";
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sipariş Yönetimi - Restoran Yönetim Sistemi</title>
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
        .btn-add {
            background-color: #28a745;
            color: white;
        }
        .status-badge {
            font-size: 0.8rem;
            padding: 5px 10px;
            border-radius: 20px;
        }
        .status-new {
            background-color: #007bff;
            color: white;
        }
        .status-preparing {
            background-color: #ffc107;
            color: black;
        }
        .status-ready {
            background-color: #28a745;
            color: white;
        }
        .status-delivered {
            background-color: #6c757d;
            color: white;
        }
        .status-paid {
            background-color: #17a2b8;
            color: white;
        }
        .status-cancelled {
            background-color: #dc3545;
            color: white;
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
            <h3>Sipariş Yönetimi</h3>
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
        
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><?php echo $filter_title; ?></h5>
                <div>
                    <?php if ($table_filter): ?>
                        <a href="tables.php" class="btn btn-secondary btn-sm mr-2">
                            <i class="fas fa-arrow-left"></i> Masalara Dön
                        </a>
                    <?php endif; ?>
                    <a href="add_order.php<?php echo $table_filter ? '?table_id='.$table_filter : ''; ?>" class="btn btn-add btn-sm">
                        <i class="fas fa-plus"></i> Yeni Sipariş Ekle
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Sipariş No</th>
                                <th>Tarih</th>
                                <th>Müşteri</th>
                                <th>Personel</th>
                                <th>Masa</th>
                                <th>Toplam</th>
                                <th>Durum</th>
                                <th>İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if (mysqli_num_rows($orders) > 0) {
                                while ($order = mysqli_fetch_assoc($orders)) {
                                    // Sipariş durumu için badge sınıfını belirle
                                    $status_class = '';
                                    switch($order['order_status']) {
                                        case 'new':
                                            $status_class = 'status-new';
                                            $status_text = 'Yeni';
                                            break;
                                        case 'preparing':
                                            $status_class = 'status-preparing';
                                            $status_text = 'Hazırlanıyor';
                                            break;
                                        case 'ready':
                                            $status_class = 'status-ready';
                                            $status_text = 'Hazır';
                                            break;
                                        case 'delivered':
                                            $status_class = 'status-delivered';
                                            $status_text = 'Teslim Edildi';
                                            break;
                                        case 'paid':
                                            $status_class = 'status-paid';
                                            $status_text = 'Ödendi';
                                            break;
                                        case 'cancelled':
                                            $status_class = 'status-cancelled';
                                            $status_text = 'İptal Edildi';
                                            break;
                                        default:
                                            $status_text = $order['order_status'];
                                    }
                                    
                                    // Müşteri adı
                                    $customer_name = isset($order['first_name']) ? $order['first_name'] . ' ' . $order['last_name'] : 'Misafir';
                                    
                                    // Personel adı
                                    $employee_name = isset($order['employee_first_name']) ? $order['employee_first_name'] . ' ' . $order['employee_last_name'] : '-';
                                    
                                    echo "<tr>";
                                    echo "<td>" . $order['order_id'] . "</td>";
                                    echo "<td>" . date('d.m.Y H:i', strtotime($order['order_date'])) . "</td>";
                                    echo "<td>" . htmlspecialchars($customer_name) . "</td>";
                                    echo "<td>" . htmlspecialchars($employee_name) . "</td>";
                                    echo "<td>" . $order['table_id'] . "</td>";
                                    echo "<td>" . number_format($order['total_amount'], 2) . " ₺</td>";
                                    echo "<td><span class='badge " . $status_class . "'>" . $status_text . "</span></td>";
                                    echo "<td>
                                            <div class='btn-group'>
                                                <a href='view_order.php?id=" . $order['order_id'] . "' class='btn btn-info btn-sm' title='Görüntüle'><i class='fas fa-eye'></i></a>
                                                <button type='button' class='btn btn-primary btn-sm dropdown-toggle' data-toggle='dropdown' aria-haspopup='true' aria-expanded='false' title='Durum Güncelle'>
                                                    <i class='fas fa-sync-alt'></i>
                                                </button>
                                                <div class='dropdown-menu'>
                                                    <a class='dropdown-item' href='orders.php?update_status=" . $order['order_id'] . "&status=new'>Yeni</a>
                                                    <a class='dropdown-item' href='orders.php?update_status=" . $order['order_id'] . "&status=preparing'>Hazırlanıyor</a>
                                                    <a class='dropdown-item' href='orders.php?update_status=" . $order['order_id'] . "&status=ready'>Hazır</a>
                                                    <a class='dropdown-item' href='orders.php?update_status=" . $order['order_id'] . "&status=delivered'>Teslim Edildi</a>
                                                    <a class='dropdown-item' href='orders.php?update_status=" . $order['order_id'] . "&status=paid'>Ödendi</a>
                                                    <div class='dropdown-divider'></div>
                                                    <a class='dropdown-item text-danger' href='orders.php?update_status=" . $order['order_id'] . "&status=cancelled'>İptal Edildi</a>
                                                </div>
                                                <a href='orders.php?delete=" . $order['order_id'] . "' class='btn btn-danger btn-sm' onclick='return confirm(\"Bu siparişi silmek istediğinize emin misiniz?\")' title='Sil'><i class='fas fa-trash'></i></a>
                                            </div>
                                          </td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='8' class='text-center'>Sipariş bulunmamaktadır.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html> 