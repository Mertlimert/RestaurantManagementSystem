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
require_once '../models/Employee.php';

// Çalışan modeli oluştur
$employeeModel = new Employee($conn);

// Hata ve başarı mesajları için değişkenler
$error_msg = '';
$success_msg = '';

// Form gönderildiğinde
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Çalışan bilgilerini al
    $employee_id = $_POST["employee_id"];
    $first_name = $_POST["first_name"];
    $last_name = $_POST["last_name"];
    $position_id = $_POST["position_id"];
    $phone_number = $_POST["phone_number"];
    $email = $_POST["email"];
    $hourly_rate = $_POST["hourly_rate"];
    $hire_date = $_POST["hire_date"];

    // Çalışanı güncelle
    if ($employeeModel->updateEmployee($employee_id, $first_name, $last_name, $position_id, $phone_number, $email, $hourly_rate, $hire_date)) {
        $success_msg = "Çalışan bilgileri başarıyla güncellendi.";
    } else {
        $error_msg = "Çalışan bilgileri güncellenirken bir hata oluştu.";
    }
}

// ID parametresi kontrolü
if (isset($_GET["id"]) && !empty(trim($_GET["id"]))) {
    // URL'den ID parametresini al
    $employee_id = trim($_GET["id"]);

    // Çalışan bilgilerini getir
    $employee_data = $employeeModel->getEmployeeById($employee_id);

    if (!$employee_data) {
        // Çalışan bulunamadı, ana sayfaya yönlendir
        header("location: employees.php");
        exit();
    }
} else {
    // URL'de ID parametresi yok, ana sayfaya yönlendir
    header("location: employees.php");
    exit();
}

// Tüm pozisyonları getir
$positions = $employeeModel->getAllPositions();
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Çalışan Düzenle - Restoran Yönetim Sistemi</title>
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
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar col-md-2">
        <h4 class="text-center mb-4">Admin Paneli</h4>
        <a href="index.php"><i class="fas fa-tachometer-alt mr-2"></i> Gösterge Paneli</a>
        <a href="customers.php"><i class="fas fa-users mr-2"></i> Müşteriler</a>
        <a href="employees.php" class="active"><i class="fas fa-user-tie mr-2"></i> Çalışanlar</a>
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
            <h3>Çalışan Düzenle</h3>
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
                <h5 class="mb-0">Çalışan Bilgilerini Düzenle</h5>
            </div>
            <div class="card-body">
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <input type="hidden" name="employee_id" value="<?php echo $employee_data['employee_id']; ?>">
                    
                    <div class="form-group">
                        <label for="first_name">Ad</label>
                        <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo $employee_data['first_name']; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="last_name">Soyad</label>
                        <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo $employee_data['last_name']; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="position_id">Pozisyon</label>
                        <select class="form-control" id="position_id" name="position_id" required>
                            <?php
                            while ($position = mysqli_fetch_assoc($positions)) {
                                $selected = ($position['position_id'] == $employee_data['position_id']) ? 'selected' : '';
                                echo "<option value='" . $position['position_id'] . "' " . $selected . ">" . $position['title'] . "</option>";
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone_number">Telefon</label>
                        <input type="text" class="form-control" id="phone_number" name="phone_number" value="<?php echo $employee_data['phone_number']; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="email">E-posta</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo $employee_data['email']; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="hourly_rate">Saatlik Ücret (₺)</label>
                        <input type="number" step="0.1" class="form-control" id="hourly_rate" name="hourly_rate" value="<?php echo $employee_data['hourly_rate']; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="hire_date">İşe Başlama Tarihi</label>
                        <input type="date" class="form-control" id="hire_date" name="hire_date" value="<?php echo $employee_data['hire_date']; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <a href="employees.php" class="btn btn-secondary">İptal</a>
                        <button type="submit" class="btn btn-primary">Güncelle</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html> 