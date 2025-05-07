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
    // Menü öğesi bilgilerini al
    $name = $_POST["name"];
    $category = $_POST["category"];
    $price = $_POST["price"];
    $description = $_POST["description"];

    // Menü öğesini ekle
    $menu_item_id = $menuItemModel->addMenuItem($name, $category, $price, $description);
    
    if ($menu_item_id) {
        // Başarıyla eklendi, menü sayfasına yönlendir
        $_SESSION['success_msg'] = "Menü öğesi başarıyla eklendi.";
    } else {
        // Hata oluştu, menü sayfasına yönlendir
        $_SESSION['error_msg'] = "Menü öğesi eklenirken bir hata oluştu.";
    }
    
    // Menü sayfasına yönlendir
    header("location: menu.php");
    exit();
} else {
    // POST isteği değilse menü sayfasına yönlendir
    header("location: menu.php");
    exit();
}
?> 