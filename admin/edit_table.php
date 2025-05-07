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
require_once '../models/Tables.php';
require_once '../models/Customer.php';

// Modelleri oluştur
$tableModel = new Tables($conn);
$customerModel = new Customer($conn);

// Hata ve başarı mesajları için değişkenler
$error_msg = '';
$success_msg = '';

// Form gönderildiğinde
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Masa bilgilerini al
    $table_id = $_POST["table_id"];
    $number_of_customers = $_POST["number_of_customers"];
    $capacity = $_POST["capacity"];
    $table_status = $_POST["table_status"];
    $customer_id = isset($_POST["customer_id"]) ? $_POST["customer_id"] : null;

    // Eğer müşteri atanması isteniyorsa
    if (!empty($customer_id)) {
        if ($tableModel->assignCustomerToTable($table_id, $customer_id, $number_of_customers)) {
            $success_msg = "Masa ve müşteri bilgileri başarıyla güncellendi.";
        } else {
            $error_msg = "Müşteri masaya atanırken bir hata oluştu.";
        }
    } 
    // Eğer müşteri kaldırılması isteniyorsa (customer_id boş ve durum "müsait" ise)
    else if ($table_status == "available") {
        if ($tableModel->removeCustomerFromTable($table_id)) {
            // Masayı güncelle (removeCustomerFromTable fonksiyonu zaten durumu available yapar, 
            // ama kapasiteyi değiştirmek için bu güncelleme gerekli)
            if ($tableModel->updateTable($table_id, 0, $capacity, 'available')) {
                $success_msg = "Masa bilgileri başarıyla güncellendi ve müşteri kaldırıldı.";
            } else {
                $error_msg = "Masa bilgileri güncellenirken bir hata oluştu.";
            }
        } else {
            $error_msg = "Müşteri masadan kaldırılırken bir hata oluştu.";
        }
    }
    // Sadece masa bilgilerini güncelle
    else {
        if ($tableModel->updateTable($table_id, $number_of_customers, $capacity, $table_status)) {
            $success_msg = "Masa bilgileri başarıyla güncellendi.";
        } else {
            $error_msg = "Masa bilgileri güncellenirken bir hata oluştu.";
        }
    }
}

// ID parametresi kontrolü
if (isset($_GET["id"]) && !empty(trim($_GET["id"]))) {
    // URL'den ID parametresini al
    $table_id = trim($_GET["id"]);

    // Masa bilgilerini müşteri detaylarıyla birlikte getir
    $table_data = $tableModel->getTableWithCustomerDetails($table_id);

    if (!$table_data) {
        // Masa bulunamadı, masalar sayfasına yönlendir
        header("location: tables.php");
        exit();
    }
} else {
    // URL'de ID parametresi yok, masalar sayfasına yönlendir
    header("location: tables.php");
    exit();
}

// Tüm müşterileri getir
$customers = $customerModel->getAllCustomers();
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masa Düzenle - Restoran Yönetim Sistemi</title>
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
        .customer-info {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-top: 15px;
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
        <a href="tables.php" class="active"><i class="fas fa-chair mr-2"></i> Masalar</a>
        <a href="menu.php"><i class="fas fa-utensils mr-2"></i> Menü</a>
        <a href="ingredients.php"><i class="fas fa-carrot mr-2"></i> Malzemeler</a>
        <a href="orders.php"><i class="fas fa-clipboard-list mr-2"></i> Siparişler</a>
        <a href="reservations.php"><i class="fas fa-calendar-alt mr-2"></i> Rezervasyonlar</a>
        <a href="../logout.php"><i class="fas fa-sign-out-alt mr-2"></i> Çıkış</a>
    </div>
    
    <!-- Main Content -->
    <div class="content col-md-10">
        <div class="top-bar d-flex justify-content-between align-items-center">
            <h3>Masa Düzenle</h3>
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
        
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Masa Bilgilerini Düzenle</h5>
            </div>
            <div class="card-body">
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . '?id=' . $table_data['table_id']; ?>" method="post">
                    <input type="hidden" name="table_id" value="<?php echo $table_data['table_id']; ?>">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="number_of_customers">Şu Anki Müşteri Sayısı</label>
                                <input type="number" class="form-control" id="number_of_customers" name="number_of_customers" value="<?php echo $table_data['number_of_customers']; ?>" min="0" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="capacity">Kapasite</label>
                                <input type="number" class="form-control" id="capacity" name="capacity" value="<?php echo $table_data['capacity']; ?>" min="1" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="table_status">Durum</label>
                                <select class="form-control" id="table_status" name="table_status">
                                    <option value="available" <?php echo ($table_data['table_status'] == 'available') ? 'selected' : ''; ?>>Müsait</option>
                                    <option value="occupied" <?php echo ($table_data['table_status'] == 'occupied') ? 'selected' : ''; ?>>Dolu</option>
                                    <option value="reserved" <?php echo ($table_data['table_status'] == 'reserved') ? 'selected' : ''; ?>>Rezerve</option>
                                </select>
                                <small class="form-text text-muted">Not: Durumu "Müsait" olarak değiştirirseniz, masa boşaltılır ve müşteri bağlantısı kaldırılır.</small>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="customer_id">Müşteri</label>
                                <select class="form-control" id="customer_id" name="customer_id">
                                    <option value="">Müşteri Seçin (İsteğe Bağlı)</option>
                                    <?php 
                                    mysqli_data_seek($customers, 0);
                                    while ($customer = mysqli_fetch_assoc($customers)): 
                                    ?>
                                        <option value="<?php echo $customer['customer_id']; ?>" <?php echo (isset($table_data['customer_id']) && $table_data['customer_id'] == $customer['customer_id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name'] . ' (' . $customer['phone_number'] . ')'); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                                <small class="form-text text-muted">Not: Müşteri seçerseniz, masa durumu otomatik olarak "Dolu" olarak ayarlanır.</small>
                            </div>
                            
                            <?php if(isset($table_data['customer_id']) && !empty($table_data['customer_id'])): ?>
                            <div class="customer-info">
                                <h6>Şu Anki Müşteri Bilgileri</h6>
                                <p><strong>İsim:</strong> <?php echo htmlspecialchars($table_data['first_name'] . ' ' . $table_data['last_name']); ?></p>
                                <p><strong>Telefon:</strong> <?php echo htmlspecialchars($table_data['phone_number']); ?></p>
                                <p><strong>E-posta:</strong> <?php echo htmlspecialchars($table_data['email']); ?></p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="form-group mt-3">
                        <a href="tables.php" class="btn btn-secondary">İptal</a>
                        <button type="submit" class="btn btn-primary">Güncelle</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        // Müşteri seçildiğinde masa durumunu "dolu" olarak ayarla
        document.getElementById('customer_id').addEventListener('change', function() {
            if (this.value) {
                document.getElementById('table_status').value = 'occupied';
            }
        });
        
        // Durum "müsait" olarak değiştirildiğinde müşteri seçimini temizle
        document.getElementById('table_status').addEventListener('change', function() {
            if (this.value === 'available') {
                document.getElementById('customer_id').value = '';
            }
        });
    </script>
</body>
</html> 