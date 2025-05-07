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

// Form gönderildi mi kontrol et
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Bilgileri al
    $menu_item_id = $_POST["menu_item_id"];
    $ingredient_id = $_POST["ingredient_id"];
    $quantity_required = $_POST["quantity_required"];

    // Menü öğesine malzeme ekle
    if ($menuItemModel->addIngredientToMenuItem($menu_item_id, $ingredient_id, $quantity_required)) {
        // Başarıyla eklendi
        $_SESSION['success_msg'] = "Malzeme başarıyla eklendi.";
    } else {
        // Hata oluştu
        $_SESSION['error_msg'] = "Malzeme eklenirken bir hata oluştu.";
    }
    
    // Düzenleme sayfasına yönlendir
    header("location: edit_menu_item.php?id=" . $menu_item_id);
    exit();
} else {
    // POST isteği değilse menü sayfasına yönlendir
    header("location: menu.php");
    exit();
}
?> 