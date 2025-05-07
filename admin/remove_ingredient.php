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
require_once '../models/MenuItem.php';

// Menü öğesi modeli oluştur
$menuItemModel = new MenuItem($conn);

// ID parametreleri kontrolü
if (isset($_GET['menu_id']) && !empty($_GET['menu_id']) && isset($_GET['ingredient_id']) && !empty($_GET['ingredient_id'])) {
    // URL'den parametreleri al
    $menu_item_id = $_GET['menu_id'];
    $ingredient_id = $_GET['ingredient_id'];
    
    // Menü öğesinden malzeme çıkar
    if ($menuItemModel->removeIngredientFromMenuItem($menu_item_id, $ingredient_id)) {
        // Başarıyla silindi
        $_SESSION['success_msg'] = "Malzeme başarıyla kaldırıldı.";
    } else {
        // Hata oluştu
        $_SESSION['error_msg'] = "Malzeme kaldırılırken bir hata oluştu.";
    }
    
    // Düzenleme sayfasına yönlendir
    header("location: edit_menu_item.php?id=" . $menu_item_id);
    exit();
} else {
    // Gerekli parametreler yok, menü sayfasına yönlendir
    header("location: menu.php");
    exit();
}
?> 