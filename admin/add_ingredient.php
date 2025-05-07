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

// Form gönderildi mi kontrol et
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Malzeme bilgilerini al
    $ingredient_name = $_POST["ingredient_name"];
    $unit = $_POST["unit"];
    $stock_quantity = $_POST["stock_quantity"];
    $allergen = isset($_POST["allergen"]) ? $_POST["allergen"] : null;

    // Malzemeyi ekle
    $ingredient_id = $ingredientModel->addIngredient($ingredient_name, $unit, $stock_quantity, $allergen);
    
    if ($ingredient_id) {
        // Başarıyla eklendi, malzemeler sayfasına yönlendir
        $_SESSION['success_msg'] = "Malzeme başarıyla eklendi.";
    } else {
        // Hata oluştu, malzemeler sayfasına yönlendir
        $_SESSION['error_msg'] = "Malzeme eklenirken bir hata oluştu.";
    }
    
    // Malzemeler sayfasına yönlendir
    header("location: ingredients.php");
    exit();
} else {
    // POST isteği değilse malzemeler sayfasına yönlendir
    header("location: ingredients.php");
    exit();
}
?> 