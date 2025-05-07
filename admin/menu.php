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

// Menü modelleri oluştur
$menuItemModel = new MenuItem($conn);
$ingredientModel = new Ingredient($conn);

// Menü öğesi silme işlemi
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $menu_item_id = $_GET['delete'];
    if ($menuItemModel->deleteMenuItem($menu_item_id)) {
        $success_msg = "Menü öğesi başarıyla silindi.";
    } else {
        $error_msg = "Menü öğesi silinemedi.";
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

// Tüm menü öğelerini getir
$menuItems = $menuItemModel->getAllMenuItems();

// Kategorilere göre menü öğelerini grupla
$categories = array();
while ($item = mysqli_fetch_assoc($menuItems)) {
    if (!isset($categories[$item['category']])) {
        $categories[$item['category']] = array();
    }
    $categories[$item['category']][] = $item;
}

// Kategori bilgilerini yeniden oluştur
mysqli_data_seek($menuItems, 0);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menü Yönetimi - Restoran Yönetim Sistemi</title>
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
        .category-title {
            background-color: #f8f9fa;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 5px;
            font-weight: bold;
        }
        .menu-item-card {
            height: 100%;
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
        <a href="menu.php" class="active"><i class="fas fa-utensils mr-2"></i> Menü</a>
        <a href="ingredients.php"><i class="fas fa-carrot mr-2"></i> Malzemeler</a>
        <a href="orders.php"><i class="fas fa-clipboard-list mr-2"></i> Siparişler</a>
        <a href="reservations.php"><i class="fas fa-calendar-alt mr-2"></i> Rezervasyonlar</a>
        <a href="../logout.php"><i class="fas fa-sign-out-alt mr-2"></i> Çıkış</a>
    </div>
    
    <!-- Main Content -->
    <div class="content col-md-10">
        <div class="top-bar d-flex justify-content-between align-items-center">
            <h3>Menü Yönetimi</h3>
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
                <h5 class="mb-0">Menü Öğeleri</h5>
                <div>
                    <button type="button" class="btn btn-primary btn-sm mr-2" data-toggle="modal" data-target="#viewMenuModal">
                        <i class="fas fa-list"></i> Listeyi Görüntüle
                    </button>
                    <button type="button" class="btn btn-add btn-sm" data-toggle="modal" data-target="#addMenuItemModal">
                        <i class="fas fa-plus"></i> Yeni Menü Öğesi Ekle
                    </button>
                </div>
            </div>
            <div class="card-body">
                <!-- Kategorilere göre menü öğeleri -->
                <?php foreach ($categories as $category => $items): ?>
                    <div class="category-title">
                        <?php echo htmlspecialchars($category); ?>
                    </div>
                    <div class="row mb-4">
                        <?php foreach ($items as $item): ?>
                            <div class="col-md-4 mb-3">
                                <div class="card menu-item-card">
                                    <div class="card-body">
                                        <h5 class="card-title"><?php echo htmlspecialchars($item['name']); ?></h5>
                                        <p class="card-text"><?php echo htmlspecialchars($item['description']); ?></p>
                                        <p class="price"><?php echo number_format($item['price'], 2); ?> ₺</p>
                                        <div class="btn-group">
                                            <a href="edit_menu_item.php?id=<?php echo $item['menu_item_id']; ?>" class="btn btn-primary btn-sm">
                                                <i class="fas fa-edit"></i> Düzenle
                                            </a>
                                            <a href="view_menu_item.php?id=<?php echo $item['menu_item_id']; ?>" class="btn btn-info btn-sm">
                                                <i class="fas fa-info-circle"></i> Detaylar
                                            </a>
                                            <a href="menu.php?delete=<?php echo $item['menu_item_id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Bu menü öğesini silmek istediğinize emin misiniz?')">
                                                <i class="fas fa-trash"></i> Sil
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <!-- Menü Öğesi Ekleme Modal -->
    <div class="modal fade" id="addMenuItemModal" tabindex="-1" role="dialog" aria-labelledby="addMenuItemModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addMenuItemModalLabel">Yeni Menü Öğesi Ekle</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="add_menu_item.php" method="post">
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="name">İsim</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="form-group">
                            <label for="category">Kategori</label>
                            <select class="form-control" id="category" name="category">
                                <option value="Ana Yemek">Ana Yemek</option>
                                <option value="Yan Yemek">Yan Yemek</option>
                                <option value="Salata">Salata</option>
                                <option value="Tatlı">Tatlı</option>
                                <option value="İçecek">İçecek</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="price">Fiyat (₺)</label>
                            <input type="number" step="0.01" class="form-control" id="price" name="price" required>
                        </div>
                        <div class="form-group">
                            <label for="description">Açıklama</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
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
    
    <!-- Menü Listesi Modal -->
    <div class="modal fade" id="viewMenuModal" tabindex="-1" role="dialog" aria-labelledby="viewMenuModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewMenuModalLabel">Menü Listesi</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>İsim</th>
                                    <th>Kategori</th>
                                    <th>Fiyat</th>
                                    <th>İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                mysqli_data_seek($menuItems, 0);
                                if (mysqli_num_rows($menuItems) > 0) {
                                    while ($item = mysqli_fetch_assoc($menuItems)) {
                                        echo "<tr>";
                                        echo "<td>" . $item['menu_item_id'] . "</td>";
                                        echo "<td>" . $item['name'] . "</td>";
                                        echo "<td>" . $item['category'] . "</td>";
                                        echo "<td>" . number_format($item['price'], 2) . " ₺</td>";
                                        echo "<td>
                                                <a href='edit_menu_item.php?id=" . $item['menu_item_id'] . "' class='btn btn-primary btn-sm'><i class='fas fa-edit'></i></a>
                                                <a href='view_menu_item.php?id=" . $item['menu_item_id'] . "' class='btn btn-info btn-sm'><i class='fas fa-info-circle'></i></a>
                                                <a href='menu.php?delete=" . $item['menu_item_id'] . "' class='btn btn-danger btn-sm' onclick='return confirm(\"Bu menü öğesini silmek istediğinize emin misiniz?\")'><i class='fas fa-trash'></i></a>
                                              </td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='5' class='text-center'>Menü öğesi bulunmamaktadır.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Kapat</button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html> 