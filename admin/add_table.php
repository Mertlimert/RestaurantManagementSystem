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

// Masa model sınıfını dahil et
require_once '../models/Tables.php';

// Masa modeli oluştur
$tableModel = new Tables($conn);

// Form POST edildiğinde
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Form verilerini al
    $capacity = $_POST["capacity"];
    $number_of_customers = $_POST["number_of_customers"] ?? 0;
    $table_status = $_POST["table_status"] ?? "available";

    // Veri doğrulama
    $errors = [];

    if (empty($capacity) || !is_numeric($capacity) || $capacity < 1) {
        $errors[] = "Geçerli bir kapasite değeri girilmelidir.";
    }

    if (!is_numeric($number_of_customers) || $number_of_customers < 0) {
        $errors[] = "Geçerli bir müşteri sayısı girilmelidir.";
    }

    if ($number_of_customers > $capacity) {
        $errors[] = "Müşteri sayısı, kapasite değerinden büyük olamaz.";
    }

    // Hata yoksa masayı ekle
    if (empty($errors)) {
        // Masayı ekle
        $result = $tableModel->addTable($number_of_customers, $capacity);
        
        if ($result) {
            // Durum belirlendiyse ve default değil ise güncelle
            if ($table_status != "available") {
                $tableModel->updateTableStatus($result, $table_status);
            }
            
            $_SESSION['success_msg'] = "Masa başarıyla eklendi.";
            header("location: tables.php");
            exit;
        } else {
            $_SESSION['error_msg'] = "Masa eklenirken bir hata oluştu.";
            header("location: tables.php");
            exit;
        }
    } else {
        // Hata mesajlarını session'a kaydet
        $_SESSION['error_msg'] = implode("<br>", $errors);
        header("location: tables.php");
        exit;
    }
} else {
    // POST isteği değilse tables.php sayfasına yönlendir
    header("location: tables.php");
    exit;
}
?> 