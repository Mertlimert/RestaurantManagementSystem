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
require_once '../models/MenuItem.php';

// Menü öğesi modelini oluştur
$menuItemModel = new MenuItem($conn);

// ID parametresi kontrolü
if (isset($_GET["id"]) && !empty(trim($_GET["id"]))) {
    // URL'den ID parametresini al
    $menu_item_id = trim($_GET["id"]);

    // Menü öğesi bilgilerini getir
    $menu_item = $menuItemModel->getMenuItemById($menu_item_id);

    if (!$menu_item) {
        // Menü öğesi bulunamadı, menü sayfasına yönlendir
        header("location: menu.php");
        exit();
    }
    
    // Menü öğesi için malzemeleri getir
    $ingredients = $menuItemModel->getMenuItemIngredients($menu_item_id);
} else {
    // URL'de ID parametresi yok, menü sayfasına yönlendir
    header("location: menu.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menü Öğesi Detayları - Restoran Yönetim Sistemi</title>
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
        .price {
            font-weight: bold;
            color: #28a745;
            font-size: 1.2rem;
        }
        .info-row {
            margin-bottom: 10px;
        }
        .info-label {
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
        <a href="tables.php"><i class="fas fa-chair mr-2"></i> Masalar</a>
        <a href="menu.php" class="active"><i class="fas fa-utensils mr-2"></i> Menü</a>
        <a href="ingredients.php"><i class="fas fa-carrot mr-2"></i> Malzemeler</a>
        <a href="orders.php"><i class="fas fa-clipboard-list mr-2"></i> Siparişler</a>
        <a href="reservations.php"><i class="fas fa-calendar-alt mr-2"></i> Rezervasyonlar</a>
        <a href="../logout.php"><i class="fas fa-sign-out-alt mr-2"></i> Çıkış</a>
    </div>
    
    <!-- Main Content -->
    <div class="content col-md-10">
        <div class="top-bar d-flex justify-content-between align-items-center">
            <h3>Menü Öğesi Detayları</h3>
            <div>
                <span class="mr-3">Hoş geldiniz, <?php echo htmlspecialchars($_SESSION["username"]); ?></span>
                <a href="../logout.php" class="btn btn-danger btn-sm"><i class="fas fa-sign-out-alt"></i> Çıkış</a>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><?php echo htmlspecialchars($menu_item['name']); ?></h5>
                <div>
                    <a href="edit_menu_item.php?id=<?php echo $menu_item_id; ?>" class="btn btn-primary btn-sm">
                        <i class="fas fa-edit"></i> Düzenle
                    </a>
                    <a href="menu.php" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left"></i> Geri
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="row info-row">
                    <div class="col-md-3 info-label">Kategori:</div>
                    <div class="col-md-9"><?php echo htmlspecialchars($menu_item['category']); ?></div>
                </div>
                
                <div class="row info-row">
                    <div class="col-md-3 info-label">Fiyat:</div>
                    <div class="col-md-9 price"><?php echo number_format($menu_item['price'], 2); ?> ₺</div>
                </div>
                
                <div class="row info-row">
                    <div class="col-md-3 info-label">Açıklama:</div>
                    <div class="col-md-9"><?php echo htmlspecialchars($menu_item['description']); ?></div>
                </div>
            </div>
        </div>
        
        <!-- Malzemeler Kartı -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Malzemeler</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Malzeme Adı</th>
                                <th>Gerekli Miktar</th>
                                <th>Birim</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if (mysqli_num_rows($ingredients) > 0) {
                                while ($ingredient = mysqli_fetch_assoc($ingredients)) {
                                    echo "<tr>";
                                    echo "<td>" . htmlspecialchars($ingredient['ingredient_name']) . "</td>";
                                    echo "<td>" . $ingredient['quantity_required'] . "</td>";
                                    echo "<td>" . htmlspecialchars($ingredient['unit']) . "</td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='3' class='text-center'>Bu menü öğesi için malzeme bulunmamaktadır.</td></tr>";
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