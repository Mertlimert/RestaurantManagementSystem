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

// Sipariş modeli oluştur
$orderModel = new Order($conn);

// Form gönderildiğinde
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Form verilerini al
    $order_id = $_POST["order_id"];
    $payment_method = $_POST["payment_method"];
    $total_amount = $_POST["total_amount"];
    $tip_amount = isset($_POST["tip_amount"]) ? $_POST["tip_amount"] : 0;
    
    // Ödemeyi ekle
    if ($orderModel->addPayment($order_id, $payment_method, $total_amount, $tip_amount)) {
        $_SESSION['success_msg'] = "Ödeme başarıyla kaydedildi.";
        header("location: view_order.php?id=" . $order_id);
        exit();
    } else {
        $_SESSION['error_msg'] = "Ödeme kaydedilirken bir hata oluştu.";
        header("location: view_order.php?id=" . $order_id);
        exit();
    }
}

// ID parametresi kontrolü
if (isset($_GET["id"]) && !empty(trim($_GET["id"]))) {
    // URL'den ID parametresini al
    $order_id = trim($_GET["id"]);

    // Sipariş bilgilerini getir
    $order = $orderModel->getOrderById($order_id);

    if (!$order) {
        // Sipariş bulunamadı, siparişler sayfasına yönlendir
        header("location: orders.php");
        exit();
    }
    
    // Sipariş zaten ödendi mi kontrol et
    if ($order['order_status'] === 'paid') {
        $_SESSION['error_msg'] = "Bu sipariş zaten ödenmiş.";
        header("location: view_order.php?id=" . $order_id);
        exit();
    }
} else {
    // URL'de ID parametresi yok, siparişler sayfasına yönlendir
    header("location: orders.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ödeme Ekle - Restoran Yönetim Sistemi</title>
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
        .payment-summary {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .total-row {
            font-weight: bold;
            font-size: 1.1rem;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid #dee2e6;
        }
        .price {
            font-weight: bold;
            color: #28a745;
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
            <h3>Ödeme Ekle</h3>
            <div>
                <span class="mr-3">Hoş geldiniz, <?php echo htmlspecialchars($_SESSION["username"]); ?></span>
                <a href="../logout.php" class="btn btn-danger btn-sm"><i class="fas fa-sign-out-alt"></i> Çıkış</a>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Sipariş #<?php echo $order_id; ?> - Ödeme Bilgileri</h5>
            </div>
            <div class="card-body">
                <div class="payment-summary">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Sipariş Tarihi:</strong> <?php echo date('d.m.Y H:i', strtotime($order['order_date'])); ?></p>
                            <p><strong>Müşteri:</strong> 
                                <?php 
                                echo isset($order['first_name']) ? 
                                     htmlspecialchars($order['first_name'] . ' ' . $order['last_name']) : 
                                     'Misafir'; 
                                ?>
                            </p>
                            <p><strong>Masa:</strong> <?php echo $order['table_id']; ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Durum:</strong> 
                                <?php 
                                $status_text = '';
                                switch($order['order_status']) {
                                    case 'new':
                                        $status_text = 'Yeni';
                                        break;
                                    case 'preparing':
                                        $status_text = 'Hazırlanıyor';
                                        break;
                                    case 'ready':
                                        $status_text = 'Hazır';
                                        break;
                                    case 'delivered':
                                        $status_text = 'Teslim Edildi';
                                        break;
                                    case 'paid':
                                        $status_text = 'Ödendi';
                                        break;
                                    case 'cancelled':
                                        $status_text = 'İptal Edildi';
                                        break;
                                    default:
                                        $status_text = $order['order_status'];
                                }
                                echo $status_text;
                                ?>
                            </p>
                            <p><strong>Personel:</strong> 
                                <?php 
                                echo isset($order['employee_first_name']) ? 
                                     htmlspecialchars($order['employee_first_name'] . ' ' . $order['employee_last_name']) : 
                                     '-'; 
                                ?>
                            </p>
                            <p class="price"><strong>Toplam Tutar:</strong> <?php echo number_format($order['total_amount'], 2); ?> ₺</p>
                        </div>
                    </div>
                </div>
                
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <input type="hidden" name="order_id" value="<?php echo $order_id; ?>">
                    <input type="hidden" name="total_amount" value="<?php echo $order['total_amount']; ?>">
                    
                    <div class="form-group">
                        <label for="payment_method">Ödeme Yöntemi</label>
                        <select class="form-control" id="payment_method" name="payment_method" required>
                            <option value="">Ödeme Yöntemi Seçin</option>
                            <option value="cash">Nakit</option>
                            <option value="credit_card">Kredi Kartı</option>
                            <option value="debit_card">Banka Kartı</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="tip_amount">Bahşiş (₺)</label>
                        <input type="number" step="0.01" min="0" class="form-control" id="tip_amount" name="tip_amount" value="0">
                    </div>
                    
                    <div class="total-row">
                        <div class="row">
                            <div class="col-md-6 text-right">
                                <p>Sipariş Toplamı:</p>
                                <p>Bahşiş:</p>
                                <p>Genel Toplam:</p>
                            </div>
                            <div class="col-md-6">
                                <p><?php echo number_format($order['total_amount'], 2); ?> ₺</p>
                                <p id="tip_display">0.00 ₺</p>
                                <p id="grand_total" class="price"><?php echo number_format($order['total_amount'], 2); ?> ₺</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <a href="view_order.php?id=<?php echo $order_id; ?>" class="btn btn-secondary">İptal</a>
                        <button type="submit" class="btn btn-primary">Ödemeyi Kaydet</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        // Bahşiş değiştiğinde hesaplamayı güncelle
        document.getElementById('tip_amount').addEventListener('input', updateTotal);
        
        function updateTotal() {
            const orderTotal = <?php echo $order['total_amount']; ?>;
            const tipAmount = parseFloat(document.getElementById('tip_amount').value) || 0;
            const grandTotal = orderTotal + tipAmount;
            
            document.getElementById('tip_display').textContent = tipAmount.toFixed(2) + ' ₺';
            document.getElementById('grand_total').textContent = grandTotal.toFixed(2) + ' ₺';
        }
    </script>
</body>
</html> 