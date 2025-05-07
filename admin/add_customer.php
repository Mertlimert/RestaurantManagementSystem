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

// Müşteri model sınıfını dahil et
require_once '../models/Customer.php';

// Müşteri modeli oluştur
$customerModel = new Customer($conn);

// Form POST edildiğinde
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Form verilerini al
    $first_name = $_POST["first_name"];
    $last_name = $_POST["last_name"];
    $phone_number = $_POST["phone_number"] ?? "";
    $email = $_POST["email"] ?? "";
    $address = $_POST["address"] ?? "";

    // Veri doğrulama
    $errors = [];

    if (empty($first_name)) {
        $errors[] = "Ad alanı gereklidir.";
    }

    if (empty($last_name)) {
        $errors[] = "Soyad alanı gereklidir.";
    }

    // Hata yoksa müşteriyi ekle
    if (empty($errors)) {
        $result = $customerModel->addCustomer($first_name, $last_name, $phone_number, $email, $address);
        
        if ($result) {
            $_SESSION['success_msg'] = "Müşteri başarıyla eklendi.";
            header("location: customers.php");
            exit;
        } else {
            $_SESSION['error_msg'] = "Müşteri eklenirken bir hata oluştu.";
            header("location: customers.php");
            exit;
        }
    } else {
        // Hata mesajlarını session'a kaydet
        $_SESSION['error_msg'] = implode("<br>", $errors);
        header("location: customers.php");
        exit;
    }
} else {
    // POST isteği değilse customers.php sayfasına yönlendir
    header("location: customers.php");
    exit;
}
?> 