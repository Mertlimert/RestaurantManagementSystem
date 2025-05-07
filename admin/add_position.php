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
require_once '../models/Employee.php';

// Çalışan modeli oluştur
$employeeModel = new Employee($conn);

// Form gönderildi mi kontrol et
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Pozisyon bilgilerini al
    $title = $_POST["title"];

    // Pozisyonu ekle
    if ($employeeModel->addPosition($title)) {
        // Başarıyla eklendi, çalışanlar sayfasına yönlendir
        $_SESSION['success_msg'] = "Pozisyon başarıyla eklendi.";
    } else {
        // Hata oluştu, çalışanlar sayfasına yönlendir
        $_SESSION['error_msg'] = "Pozisyon eklenirken bir hata oluştu.";
    }
    
    // Çalışanlar sayfasına yönlendir
    header("location: employees.php");
    exit();
} else {
    // POST isteği değilse çalışanlar sayfasına yönlendir
    header("location: employees.php");
    exit();
}
?> 