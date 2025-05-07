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
require_once '../models/Customer.php';
require_once '../models/Employee.php';
require_once '../models/Ingredient.php';
require_once '../models/MenuItem.php';
require_once '../models/Order.php';
require_once '../models/Reservation.php';
require_once '../models/Tables.php';

// İstatistikler için model nesneleri oluştur
$customerModel = new Customer($conn);
$employeeModel = new Employee($conn);
$orderModel = new Order($conn);
$reservationModel = new Reservation($conn);
$tableModel = new Tables($conn);

// Temel istatistikleri al
// Müşteri sayısı
$customersResult = $customerModel->getAllCustomers();
$totalCustomers = mysqli_num_rows($customersResult);

// Çalışan sayısı
$employeesResult = $employeeModel->getAllEmployees();
$totalEmployees = mysqli_num_rows($employeesResult);

// Günlük sipariş sayısı
$today = date('Y-m-d');
$orderResult = $orderModel->getOrdersByDate($today);
$todayOrders = mysqli_num_rows($orderResult);

// Günlük rezervasyon sayısı
$reservationResult = $reservationModel->getReservationsByDate($today);
$todayReservations = mysqli_num_rows($reservationResult);

// Müsait masa sayısı
$availableTablesResult = $tableModel->getAvailableTables();
$availableTables = mysqli_num_rows($availableTablesResult);

// Bugünkü gelir
$totalRevenue = 0;
while ($order = mysqli_fetch_assoc($orderResult)) {
    if ($order['order_status'] == 'paid') {
        $totalRevenue += $order['total_amount'];
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restoran Yönetim Sistemi - Admin Paneli</title>
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
        .dashboard-card {
            background-color: #fff;
            border-radius: 5px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .card-icon {
            font-size: 30px;
            margin-bottom: 15px;
        }
        .card-title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .card-value {
            font-size: 24px;
            font-weight: bold;
            color: #28a745;
        }
        .top-bar {
            background-color: white;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar col-md-2">
        <h4 class="text-center mb-4">Admin Paneli</h4>
        <a href="index.php" class="active"><i class="fas fa-tachometer-alt mr-2"></i> Gösterge Paneli</a>
        <a href="customers.php"><i class="fas fa-users mr-2"></i> Müşteriler</a>
        <a href="employees.php"><i class="fas fa-user-tie mr-2"></i> Çalışanlar</a>
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
            <h3>Gösterge Paneli</h3>
            <div>
                <span class="mr-3">Hoş geldiniz, <?php echo htmlspecialchars($_SESSION["username"]); ?></span>
                <a href="../logout.php" class="btn btn-danger btn-sm"><i class="fas fa-sign-out-alt"></i> Çıkış</a>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-4">
                <div class="dashboard-card text-center">
                    <div class="card-icon text-primary">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="card-title">Toplam Müşteri</div>
                    <div class="card-value"><?php echo $totalCustomers; ?></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="dashboard-card text-center">
                    <div class="card-icon text-success">
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <div class="card-title">Toplam Çalışan</div>
                    <div class="card-value"><?php echo $totalEmployees; ?></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="dashboard-card text-center">
                    <div class="card-icon text-warning">
                        <i class="fas fa-chair"></i>
                    </div>
                    <div class="card-title">Müsait Masa</div>
                    <div class="card-value"><?php echo $availableTables; ?></div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-4">
                <div class="dashboard-card text-center">
                    <div class="card-icon text-danger">
                        <i class="fas fa-clipboard-list"></i>
                    </div>
                    <div class="card-title">Bugünkü Siparişler</div>
                    <div class="card-value"><?php echo $todayOrders; ?></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="dashboard-card text-center">
                    <div class="card-icon text-info">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <div class="card-title">Bugünkü Rezervasyonlar</div>
                    <div class="card-value"><?php echo $todayReservations; ?></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="dashboard-card text-center">
                    <div class="card-icon text-success">
                        <i class="fas fa-lira-sign"></i>
                    </div>
                    <div class="card-title">Bugünkü Gelir</div>
                    <div class="card-value"><?php echo number_format($totalRevenue, 2); ?> ₺</div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-12">
                <div class="dashboard-card">
                    <h4>Son Siparişler</h4>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Sipariş ID</th>
                                    <th>Müşteri</th>
                                    <th>Masa</th>
                                    <th>Tarih</th>
                                    <th>Tutar</th>
                                    <th>Durum</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Tüm siparişleri al (sınırlı sayıda)
                                $allOrdersResult = $orderModel->getAllOrders();
                                $count = 0;
                                while ($order = mysqli_fetch_assoc($allOrdersResult)) {
                                    if ($count >= 5) break; // Sadece son 5 siparişi göster
                                    $count++;
                                    
                                    $status_color = '';
                                    switch ($order['order_status']) {
                                        case 'ordered':
                                            $status_color = 'primary';
                                            break;
                                        case 'preparing':
                                            $status_color = 'warning';
                                            break;
                                        case 'served':
                                            $status_color = 'info';
                                            break;
                                        case 'paid':
                                            $status_color = 'success';
                                            break;
                                    }
                                    
                                    echo "<tr>";
                                    echo "<td>" . $order['order_id'] . "</td>";
                                    echo "<td>" . ($order['first_name'] ? $order['first_name'] . ' ' . $order['last_name'] : 'Misafir') . "</td>";
                                    echo "<td>" . $order['table_id'] . "</td>";
                                    echo "<td>" . date('d.m.Y H:i', strtotime($order['order_date'])) . "</td>";
                                    echo "<td>" . number_format($order['total_amount'], 2) . " ₺</td>";
                                    echo "<td><span class='badge badge-" . $status_color . "'>" . $order['order_status'] . "</span></td>";
                                    echo "</tr>";
                                }
                                
                                if ($count == 0) {
                                    echo "<tr><td colspan='6' class='text-center'>Sipariş bulunmamaktadır.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html> 