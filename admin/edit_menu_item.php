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
require_once '../models/Ingredient.php';

// Menü öğesi ve malzeme modellerini oluştur
$menuItemModel = new MenuItem($conn);
$ingredientModel = new Ingredient($conn);

// Hata ve başarı mesajları için değişkenler
$error_msg = '';
$success_msg = '';

// Form gönderildiğinde
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Menü öğesi bilgilerini al
    $menu_item_id = $_POST["menu_item_id"];
    $name = $_POST["name"];
    $category = $_POST["category"];
    $price = $_POST["price"];
    $description = $_POST["description"];

    // Menü öğesini güncelle
    if ($menuItemModel->updateMenuItem($menu_item_id, $name, $category, $price, $description)) {
        $success_msg = "Menü öğesi başarıyla güncellendi.";
    } else {
        $error_msg = "Menü öğesi güncellenirken bir hata oluştu.";
    }
}

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
    <title>Menü Öğesi Düzenle - Restoran Yönetim Sistemi</title>
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
        <a href="menu.php" class="active"><i class="fas fa-utensils mr-2"></i> Menü</a>
        <a href="ingredients.php"><i class="fas fa-carrot mr-2"></i> Malzemeler</a>
        <a href="orders.php"><i class="fas fa-clipboard-list mr-2"></i> Siparişler</a>
        <a href="reservations.php"><i class="fas fa-calendar-alt mr-2"></i> Rezervasyonlar</a>
        <a href="../logout.php"><i class="fas fa-sign-out-alt mr-2"></i> Çıkış</a>
    </div>
    
    <!-- Main Content -->
    <div class="content col-md-10">
        <div class="top-bar d-flex justify-content-between align-items-center">
            <h3>Menü Öğesi Düzenle</h3>
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
                <h5 class="mb-0">Menü Öğesi Bilgilerini Düzenle</h5>
            </div>
            <div class="card-body">
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <input type="hidden" name="menu_item_id" value="<?php echo $menu_item['menu_item_id']; ?>">
                    
                    <div class="form-group">
                        <label for="name">İsim</label>
                        <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($menu_item['name']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="category">Kategori</label>
                        <select class="form-control" id="category" name="category">
                            <option value="Ana Yemek" <?php echo ($menu_item['category'] == 'Ana Yemek') ? 'selected' : ''; ?>>Ana Yemek</option>
                            <option value="Yan Yemek" <?php echo ($menu_item['category'] == 'Yan Yemek') ? 'selected' : ''; ?>>Yan Yemek</option>
                            <option value="Salata" <?php echo ($menu_item['category'] == 'Salata') ? 'selected' : ''; ?>>Salata</option>
                            <option value="Tatlı" <?php echo ($menu_item['category'] == 'Tatlı') ? 'selected' : ''; ?>>Tatlı</option>
                            <option value="İçecek" <?php echo ($menu_item['category'] == 'İçecek') ? 'selected' : ''; ?>>İçecek</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="price">Fiyat (₺)</label>
                        <input type="number" step="0.01" class="form-control" id="price" name="price" value="<?php echo $menu_item['price']; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Açıklama</label>
                        <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($menu_item['description']); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <a href="menu.php" class="btn btn-secondary">İptal</a>
                        <button type="submit" class="btn btn-primary">Güncelle</button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Malzemeler Kartı -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Malzemeler</h5>
                <button type="button" class="btn btn-success btn-sm" data-toggle="modal" data-target="#addIngredientModal">
                    <i class="fas fa-plus"></i> Malzeme Ekle
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Malzeme Adı</th>
                                <th>Gerekli Miktar</th>
                                <th>Birim</th>
                                <th>İşlemler</th>
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
                                    echo "<td>
                                            <a href='remove_ingredient.php?menu_id=" . $menu_item_id . "&ingredient_id=" . $ingredient['ingredient_id'] . "' class='btn btn-danger btn-sm' onclick='return confirm(\"Bu malzemeyi menü öğesinden çıkarmak istediğinize emin misiniz?\")'><i class='fas fa-trash'></i></a>
                                          </td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='4' class='text-center'>Bu menü öğesi için malzeme bulunmamaktadır.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Malzeme Ekleme Modal -->
    <div class="modal fade" id="addIngredientModal" tabindex="-1" role="dialog" aria-labelledby="addIngredientModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addIngredientModalLabel">Malzeme Ekle</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="add_ingredient_to_menu.php" method="post">
                    <div class="modal-body">
                        <input type="hidden" name="menu_item_id" value="<?php echo $menu_item_id; ?>">
                        
                        <div class="form-group">
                            <label for="ingredient_id">Malzeme</label>
                            <select class="form-control" id="ingredient_id" name="ingredient_id" required>
                                <option value="">Malzeme Seçin</option>
                                <?php
                                $all_ingredients = $ingredientModel->getAllIngredients();
                                while ($ing = mysqli_fetch_assoc($all_ingredients)) {
                                    echo "<option value='" . $ing['ingredient_id'] . "'>" . htmlspecialchars($ing['ingredient_name']) . " (" . htmlspecialchars($ing['unit']) . ")</option>";
                                }
                                ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="quantity_required">Gerekli Miktar</label>
                            <input type="number" step="0.01" class="form-control" id="quantity_required" name="quantity_required" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">İptal</button>
                        <button type="submit" class="btn btn-primary">Ekle</button>
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