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
require_once '../models/Reservation.php';
require_once '../models/Customer.php';
require_once '../models/Tables.php';

// Modelleri oluştur
$reservationModel = new Reservation($conn);
$customerModel = new Customer($conn);
$tableModel = new Tables($conn);

// Rezervasyon silme işlemi
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $reservation_id = $_GET['delete'];
    if ($reservationModel->deleteReservation($reservation_id)) {
        $success_msg = "Rezervasyon başarıyla silindi.";
    } else {
        $error_msg = "Rezervasyon silinemedi.";
    }
}

// Rezervasyon durumu güncelleme
if (isset($_GET['update_status']) && !empty($_GET['update_status']) && isset($_GET['status'])) {
    $reservation_id = $_GET['update_status'];
    $status = $_GET['status'];
    
    // Veritabanı statüsüne dönüştür
    $db_status = 'reserved'; // varsayılan olarak rezerve
    
    // Sistem durumundan veritabanı durumuna dönüştür
    switch($status) {
        case 'confirmed':
        case 'pending':
        case 'reserved':
            $db_status = 'reserved';
            break;
        case 'cancelled':
        case 'completed':
        case 'available':
            $db_status = 'available';
            break;
    }
    
    if ($reservationModel->updateReservationStatus($reservation_id, $db_status)) {
        $success_msg = "Rezervasyon durumu başarıyla güncellendi.";
    } else {
        $error_msg = "Rezervasyon durumu güncellenemedi.";
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

// Tarih filtresini kontrol et
$date_filter = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// Filtre tipini kontrol et
$filter_type = isset($_GET['filter']) ? $_GET['filter'] : 'today';

// Filtre tipine göre rezervasyonları getir
if ($filter_type == 'today') {
    $reservations = $reservationModel->getReservationsByDate(date('Y-m-d'));
    $filter_title = "Bugünkü Rezervasyonlar";
} elseif ($filter_type == 'upcoming') {
    $reservations = $reservationModel->getUpcomingReservations(7);
    $filter_title = "Yaklaşan Rezervasyonlar (7 gün)";
} elseif ($filter_type == 'date' && !empty($date_filter)) {
    $reservations = $reservationModel->getReservationsByDate($date_filter);
    $filter_title = date('d.m.Y', strtotime($date_filter)) . " Tarihli Rezervasyonlar";
} else {
    $reservations = $reservationModel->getAllReservations();
    $filter_title = "Tüm Rezervasyonlar";
}

// Masa bilgilerini getir
$tables = $tableModel->getAllTables();
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rezervasyon Yönetimi - Restoran Yönetim Sistemi</title>
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
        .status-confirmed {
            background-color: #28a745;
            color: white;
        }
        .status-pending {
            background-color: #ffc107;
            color: black;
        }
        .status-cancelled {
            background-color: #dc3545;
            color: white;
        }
        .status-completed {
            background-color: #6c757d;
            color: white;
        }
        .filter-card {
            margin-bottom: 20px;
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
        <a href="orders.php"><i class="fas fa-clipboard-list mr-2"></i> Siparişler</a>
        <a href="reservations.php" class="active"><i class="fas fa-calendar-alt mr-2"></i> Rezervasyonlar</a>
        <a href="../logout.php"><i class="fas fa-sign-out-alt mr-2"></i> Çıkış</a>
    </div>
    
    <!-- Main Content -->
    <div class="content col-md-10">
        <div class="top-bar d-flex justify-content-between align-items-center">
            <h3>Rezervasyon Yönetimi</h3>
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
        
        <!-- Filtreler -->
        <div class="card filter-card">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-8">
                        <div class="btn-group mb-3" role="group">
                            <a href="reservations.php?filter=today" class="btn <?php echo $filter_type == 'today' ? 'btn-primary' : 'btn-outline-primary'; ?>">Bugün</a>
                            <a href="reservations.php?filter=upcoming" class="btn <?php echo $filter_type == 'upcoming' ? 'btn-primary' : 'btn-outline-primary'; ?>">Yaklaşan (7 gün)</a>
                            <a href="reservations.php?filter=all" class="btn <?php echo $filter_type == 'all' ? 'btn-primary' : 'btn-outline-primary'; ?>">Tümü</a>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <form method="get" class="form-inline">
                            <input type="hidden" name="filter" value="date">
                            <div class="input-group w-100">
                                <input type="date" class="form-control" name="date" value="<?php echo $date_filter; ?>">
                                <div class="input-group-append">
                                    <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Ara</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><?php echo $filter_title; ?></h5>
                <button type="button" class="btn btn-add btn-sm" data-toggle="modal" data-target="#addReservationModal">
                    <i class="fas fa-plus"></i> Yeni Rezervasyon
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Tarih & Saat</th>
                                <th>Müşteri</th>
                                <th>Telefon</th>
                                <th>Kişi Sayısı</th>
                                <th>Masa</th>
                                <th>Süre (dk)</th>
                                <th>Durum</th>
                                <th>İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if (mysqli_num_rows($reservations) > 0) {
                                while ($reservation = mysqli_fetch_assoc($reservations)) {
                                    // Rezervasyon durumu için badge sınıfını belirle
                                    $status_class = '';
                                    $status_text = '';
                                    
                                    switch($reservation['reservation_status']) {
                                        case 'confirmed':
                                            $status_class = 'status-confirmed';
                                            $status_text = 'Onaylandı';
                                            break;
                                        case 'pending':
                                            $status_class = 'status-pending';
                                            $status_text = 'Beklemede';
                                            break;
                                        case 'cancelled':
                                            $status_class = 'status-cancelled';
                                            $status_text = 'İptal Edildi';
                                            break;
                                        case 'completed':
                                            $status_class = 'status-completed';
                                            $status_text = 'Tamamlandı';
                                            break;
                                        case 'reserved':
                                            $status_class = 'status-confirmed';
                                            $status_text = 'Rezerve';
                                            break;
                                        case 'available':
                                            $status_class = 'status-pending';
                                            $status_text = 'Müsait';
                                            break;
                                        default:
                                            $status_text = $reservation['reservation_status'];
                                    }
                                    
                                    // Masa adını belirle
                                    $table_name = "Masa " . $reservation['table_id'];
                                    
                                    // guest_count ve duration değerlerini kontrol et
                                    $guest_count = isset($reservation['guest_count']) ? $reservation['guest_count'] : "2";
                                    $duration = isset($reservation['duration']) ? $reservation['duration'] : "120";
                                    
                                    echo "<tr>";
                                    echo "<td>" . $reservation['reservation_id'] . "</td>";
                                    echo "<td>" . date('d.m.Y H:i', strtotime($reservation['reservation_datetime'])) . "</td>";
                                    echo "<td>" . htmlspecialchars($reservation['first_name'] . ' ' . $reservation['last_name']) . "</td>";
                                    echo "<td>" . htmlspecialchars($reservation['phone_number']) . "</td>";
                                    echo "<td>" . $guest_count . "</td>";
                                    echo "<td>" . $table_name . "</td>";
                                    echo "<td>" . $duration . "</td>";
                                    echo "<td><span class='badge " . $status_class . "'>" . $status_text . "</span></td>";
                                    echo "<td>
                                            <div class='btn-group'>
                                                <a href='edit_reservation.php?id=" . $reservation['reservation_id'] . "' class='btn btn-primary btn-sm' title='Düzenle'><i class='fas fa-edit'></i></a>
                                                <button type='button' class='btn btn-info btn-sm dropdown-toggle' data-toggle='dropdown' aria-haspopup='true' aria-expanded='false' title='Durum Güncelle'>
                                                    <i class='fas fa-sync-alt'></i>
                                                </button>
                                                <div class='dropdown-menu'>
                                                    <a class='dropdown-item' href='reservations.php?update_status=" . $reservation['reservation_id'] . "&status=confirmed'>Onaylandı</a>
                                                    <a class='dropdown-item' href='reservations.php?update_status=" . $reservation['reservation_id'] . "&status=pending'>Beklemede</a>
                                                    <a class='dropdown-item' href='reservations.php?update_status=" . $reservation['reservation_id'] . "&status=completed'>Tamamlandı</a>
                                                    <div class='dropdown-divider'></div>
                                                    <a class='dropdown-item text-danger' href='reservations.php?update_status=" . $reservation['reservation_id'] . "&status=cancelled'>İptal Edildi</a>
                                                </div>
                                                <a href='reservations.php?delete=" . $reservation['reservation_id'] . "' class='btn btn-danger btn-sm' onclick='return confirm(\"Bu rezervasyonu silmek istediğinize emin misiniz?\")' title='Sil'><i class='fas fa-trash'></i></a>
                                            </div>
                                          </td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='9' class='text-center'>Rezervasyon bulunmamaktadır.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Yeni Rezervasyon Modal -->
    <div class="modal fade" id="addReservationModal" tabindex="-1" role="dialog" aria-labelledby="addReservationModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addReservationModalLabel">Yeni Rezervasyon Ekle</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="add_reservation.php" method="post">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="customer_id">Müşteri</label>
                                    <select class="form-control" id="customer_id" name="customer_id" required>
                                        <option value="">Müşteri Seçin</option>
                                        <?php 
                                        $customers = $customerModel->getAllCustomers();
                                        while ($customer = mysqli_fetch_assoc($customers)): 
                                        ?>
                                            <option value="<?php echo $customer['customer_id']; ?>">
                                                <?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name'] . ' (' . $customer['phone_number'] . ')'); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="table_id">Masa</label>
                                    <select class="form-control" id="table_id" name="table_id" required>
                                        <option value="">Masa Seçin</option>
                                        <?php 
                                        mysqli_data_seek($tables, 0);
                                        while ($table = mysqli_fetch_assoc($tables)): 
                                        ?>
                                            <option value="<?php echo $table['table_id']; ?>">
                                                Masa <?php echo $table['table_id']; ?> (<?php echo $table['capacity']; ?> kişilik)
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="reservation_date">Tarih</label>
                                    <input type="date" class="form-control" id="reservation_date" name="reservation_date" required min="<?php echo date('Y-m-d'); ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="reservation_time">Saat</label>
                                    <input type="time" class="form-control" id="reservation_time" name="reservation_time" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="guest_count">Kişi Sayısı</label>
                                    <input type="number" class="form-control" id="guest_count" name="guest_count" min="1" value="2" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="duration">Süre (dakika)</label>
                                    <input type="number" class="form-control" id="duration" name="duration" min="30" step="30" value="120" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="status">Durum</label>
                                    <select class="form-control" id="status" name="status" required>
                                        <option value="confirmed">Onaylandı</option>
                                        <option value="pending">Beklemede</option>
                                        <option value="cancelled">İptal Edildi</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="notes">Notlar</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
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
    <script>
        // Sayfa yüklendiğinde çalışacak işlemler
        document.addEventListener('DOMContentLoaded', function() {
            // Bugünün tarihini rezervasyon tarihi için varsayılan olarak ayarla
            document.getElementById('reservation_date').value = '<?php echo date('Y-m-d'); ?>';
            
            // Şu anki saati alıp en yakın 30 dakikaya yuvarlama
            const now = new Date();
            const hours = now.getHours();
            const minutes = Math.ceil(now.getMinutes() / 30) * 30;
            const timeStr = (hours + Math.floor(minutes / 60)).toString().padStart(2, '0') + ':' + 
                            (minutes % 60).toString().padStart(2, '0');
            
            document.getElementById('reservation_time').value = timeStr;
        });
    </script>
</body>
</html> 