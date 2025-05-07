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

// Çalışan silme işlemi
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $employee_id = $_GET['delete'];
    if ($employeeModel->deleteEmployee($employee_id)) {
        $success_msg = "Çalışan başarıyla silindi.";
    } else {
        $error_msg = "Çalışan silinemedi.";
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

// Tüm çalışanları getir
$employees = $employeeModel->getAllEmployees();

// Tüm pozisyonları getir
$positions = $employeeModel->getAllPositions();
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Çalışan Yönetimi - Restoran Yönetim Sistemi</title>
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
            <h3>Çalışan Yönetimi</h3>
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
                <h5 class="mb-0">Çalışan Listesi</h5>
                <button type="button" class="btn btn-add btn-sm" data-toggle="modal" data-target="#addEmployeeModal">
                    <i class="fas fa-plus"></i> Yeni Çalışan Ekle
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Ad</th>
                                <th>Soyad</th>
                                <th>Pozisyon</th>
                                <th>Telefon</th>
                                <th>E-posta</th>
                                <th>Saatlik Ücret</th>
                                <th>İşe Başlama</th>
                                <th>İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if (mysqli_num_rows($employees) > 0) {
                                while ($employee = mysqli_fetch_assoc($employees)) {
                                    echo "<tr>";
                                    echo "<td>" . $employee['employee_id'] . "</td>";
                                    echo "<td>" . $employee['first_name'] . "</td>";
                                    echo "<td>" . $employee['last_name'] . "</td>";
                                    echo "<td>" . $employee['position_title'] . "</td>";
                                    echo "<td>" . $employee['phone_number'] . "</td>";
                                    echo "<td>" . $employee['email'] . "</td>";
                                    echo "<td>" . $employee['hourly_rate'] . " ₺</td>";
                                    echo "<td>" . date('d.m.Y', strtotime($employee['hire_date'])) . "</td>";
                                    echo "<td>
                                            <a href='edit_employee.php?id=" . $employee['employee_id'] . "' class='btn btn-primary btn-sm'><i class='fas fa-edit'></i></a>
                                            <a href='employees.php?delete=" . $employee['employee_id'] . "' class='btn btn-danger btn-sm' onclick='return confirm(\"Bu çalışanı silmek istediğinize emin misiniz?\")'><i class='fas fa-trash'></i></a>
                                            <a href='employee_shifts.php?id=" . $employee['employee_id'] . "' class='btn btn-info btn-sm'><i class='fas fa-calendar-alt'></i></a>
                                          </td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='9' class='text-center'>Çalışan bulunmamaktadır.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Pozisyonlar Kartı -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Pozisyonlar</h5>
                <button type="button" class="btn btn-add btn-sm" data-toggle="modal" data-target="#addPositionModal">
                    <i class="fas fa-plus"></i> Yeni Pozisyon Ekle
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Pozisyon Adı</th>
                                <th>İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if (mysqli_num_rows($positions) > 0) {
                                while ($position = mysqli_fetch_assoc($positions)) {
                                    echo "<tr>";
                                    echo "<td>" . $position['position_id'] . "</td>";
                                    echo "<td>" . $position['title'] . "</td>";
                                    echo "<td>
                                            <a href='edit_position.php?id=" . $position['position_id'] . "' class='btn btn-primary btn-sm'><i class='fas fa-edit'></i></a>
                                            <a href='delete_position.php?id=" . $position['position_id'] . "' class='btn btn-danger btn-sm' onclick='return confirm(\"Bu pozisyonu silmek istediğinize emin misiniz?\")'><i class='fas fa-trash'></i></a>
                                          </td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='3' class='text-center'>Pozisyon bulunmamaktadır.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Çalışan Ekleme Modal -->
    <div class="modal fade" id="addEmployeeModal" tabindex="-1" role="dialog" aria-labelledby="addEmployeeModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addEmployeeModalLabel">Yeni Çalışan Ekle</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="add_employee.php" method="post">
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="first_name">Ad</label>
                            <input type="text" class="form-control" id="first_name" name="first_name" required>
                        </div>
                        <div class="form-group">
                            <label for="last_name">Soyad</label>
                            <input type="text" class="form-control" id="last_name" name="last_name" required>
                        </div>
                        <div class="form-group">
                            <label for="position_id">Pozisyon</label>
                            <select class="form-control" id="position_id" name="position_id" required>
                                <option value="">Pozisyon Seçin</option>
                                <?php
                                mysqli_data_seek($positions, 0); // Sonuç kümesini başa sar
                                while ($position = mysqli_fetch_assoc($positions)) {
                                    echo "<option value='" . $position['position_id'] . "'>" . $position['title'] . "</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="phone_number">Telefon</label>
                            <input type="text" class="form-control" id="phone_number" name="phone_number">
                        </div>
                        <div class="form-group">
                            <label for="email">E-posta</label>
                            <input type="email" class="form-control" id="email" name="email">
                        </div>
                        <div class="form-group">
                            <label for="hourly_rate">Saatlik Ücret (₺)</label>
                            <input type="number" step="0.1" class="form-control" id="hourly_rate" name="hourly_rate" required>
                        </div>
                        <div class="form-group">
                            <label for="hire_date">İşe Başlama Tarihi</label>
                            <input type="date" class="form-control" id="hire_date" name="hire_date" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">İptal</button>
                        <button type="submit" class="btn btn-primary">Kaydet</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Pozisyon Ekleme Modal -->
    <div class="modal fade" id="addPositionModal" tabindex="-1" role="dialog" aria-labelledby="addPositionModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addPositionModalLabel">Yeni Pozisyon Ekle</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="add_position.php" method="post">
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="title">Pozisyon Adı</label>
                            <input type="text" class="form-control" id="title" name="title" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">İptal</button>
                        <button type="submit" class="btn btn-primary">Kaydet</button>
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