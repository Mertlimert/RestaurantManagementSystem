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
require_once '../models/Ingredient.php';

// Malzeme modeli oluştur
$ingredientModel = new Ingredient($conn);

// Hata ve başarı mesajları için değişkenler
$error_msg = '';
$success_msg = '';

// Form gönderildiğinde
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Malzeme bilgilerini al
    $ingredient_id = $_POST["ingredient_id"];
    $ingredient_name = $_POST["ingredient_name"];
    $unit = $_POST["unit"];
    $stock_quantity = $_POST["stock_quantity"];
    $allergen = isset($_POST["allergen"]) ? $_POST["allergen"] : null;

    // Malzemeyi güncelle
    if ($ingredientModel->updateIngredient($ingredient_id, $ingredient_name, $unit, $stock_quantity, $allergen)) {
        $success_msg = "Malzeme bilgileri başarıyla güncellendi.";
    } else {
        $error_msg = "Malzeme bilgileri güncellenirken bir hata oluştu.";
    }
}

// ID parametresi kontrolü
if (isset($_GET["id"]) && !empty(trim($_GET["id"]))) {
    // URL'den ID parametresini al
    $ingredient_id = trim($_GET["id"]);

    // Malzeme bilgilerini getir
    $ingredient = $ingredientModel->getIngredientById($ingredient_id);

    if (!$ingredient) {
        // Malzeme bulunamadı, malzemeler sayfasına yönlendir
        header("location: ingredients.php");
        exit();
    }
} else {
    // URL'de ID parametresi yok, malzemeler sayfasına yönlendir
    header("location: ingredients.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Malzeme Düzenle - Restoran Yönetim Sistemi</title>
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
        <a href="employees.php"><i class="fas fa-user-tie mr-2"></i> Çalışanlar</a>
        <a href="tables.php"><i class="fas fa-chair mr-2"></i> Masalar</a>
        <a href="menu.php"><i class="fas fa-utensils mr-2"></i> Menü</a>
        <a href="ingredients.php" class="active"><i class="fas fa-carrot mr-2"></i> Malzemeler</a>
        <a href="orders.php"><i class="fas fa-clipboard-list mr-2"></i> Siparişler</a>
        <a href="reservations.php"><i class="fas fa-calendar-alt mr-2"></i> Rezervasyonlar</a>
        <a href="../logout.php"><i class="fas fa-sign-out-alt mr-2"></i> Çıkış</a>
    </div>
    
    <!-- Main Content -->
    <div class="content col-md-10">
        <div class="top-bar d-flex justify-content-between align-items-center">
            <h3>Malzeme Düzenle</h3>
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
                <h5 class="mb-0">Malzeme Bilgilerini Düzenle</h5>
            </div>
            <div class="card-body">
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <input type="hidden" name="ingredient_id" value="<?php echo $ingredient['ingredient_id']; ?>">
                    
                    <div class="form-group">
                        <label for="ingredient_name">Malzeme Adı</label>
                        <input type="text" class="form-control" id="ingredient_name" name="ingredient_name" value="<?php echo htmlspecialchars($ingredient['ingredient_name']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="unit">Birim</label>
                        <select class="form-control" id="unit" name="unit" required>
                            <option value="kg" <?php echo ($ingredient['unit'] == 'kg') ? 'selected' : ''; ?>>Kilogram (kg)</option>
                            <option value="g" <?php echo ($ingredient['unit'] == 'g') ? 'selected' : ''; ?>>Gram (g)</option>
                            <option value="litre" <?php echo ($ingredient['unit'] == 'litre') ? 'selected' : ''; ?>>Litre (lt)</option>
                            <option value="ml" <?php echo ($ingredient['unit'] == 'ml') ? 'selected' : ''; ?>>Mililitre (ml)</option>
                            <option value="adet" <?php echo ($ingredient['unit'] == 'adet') ? 'selected' : ''; ?>>Adet</option>
                            <option value="paket" <?php echo ($ingredient['unit'] == 'paket') ? 'selected' : ''; ?>>Paket</option>
                            <option value="porsiyon" <?php echo ($ingredient['unit'] == 'porsiyon') ? 'selected' : ''; ?>>Porsiyon</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="stock_quantity">Stok Miktarı</label>
                        <input type="number" step="0.01" class="form-control" id="stock_quantity" name="stock_quantity" value="<?php echo $ingredient['stock_quantity']; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="allergen">Alerjen (varsa)</label>
                        <select class="form-control" id="allergen" name="allergen">
                            <option value="" <?php echo (empty($ingredient['allergen'])) ? 'selected' : ''; ?>>Alerjen Yok</option>
                            <option value="Gluten" <?php echo ($ingredient['allergen'] == 'Gluten') ? 'selected' : ''; ?>>Gluten</option>
                            <option value="Süt" <?php echo ($ingredient['allergen'] == 'Süt') ? 'selected' : ''; ?>>Süt</option>
                            <option value="Yumurta" <?php echo ($ingredient['allergen'] == 'Yumurta') ? 'selected' : ''; ?>>Yumurta</option>
                            <option value="Fıstık" <?php echo ($ingredient['allergen'] == 'Fıstık') ? 'selected' : ''; ?>>Fıstık</option>
                            <option value="Kabuklu Yemiş" <?php echo ($ingredient['allergen'] == 'Kabuklu Yemiş') ? 'selected' : ''; ?>>Kabuklu Yemiş</option>
                            <option value="Soya" <?php echo ($ingredient['allergen'] == 'Soya') ? 'selected' : ''; ?>>Soya</option>
                            <option value="Balık" <?php echo ($ingredient['allergen'] == 'Balık') ? 'selected' : ''; ?>>Balık</option>
                            <option value="Kabuklu Deniz Ürünü" <?php echo ($ingredient['allergen'] == 'Kabuklu Deniz Ürünü') ? 'selected' : ''; ?>>Kabuklu Deniz Ürünü</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <a href="ingredients.php" class="btn btn-secondary">İptal</a>
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