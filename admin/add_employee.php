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
    // Çalışan bilgilerini al
    $first_name = $_POST["first_name"];
    $last_name = $_POST["last_name"];
    $position_id = $_POST["position_id"];
    $phone_number = $_POST["phone_number"];
    $email = $_POST["email"];
    $hourly_rate = $_POST["hourly_rate"];
    $hire_date = $_POST["hire_date"];

    // Çalışanı ekle
    if ($employeeModel->addEmployee($first_name, $last_name, $position_id, $phone_number, $email, $hourly_rate, $hire_date)) {
        // Başarıyla eklendi, çalışanlar sayfasına yönlendir
        $_SESSION['success_msg'] = "Çalışan başarıyla eklendi.";
    } else {
        // Hata oluştu, çalışanlar sayfasına yönlendir
        $_SESSION['error_msg'] = "Çalışan eklenirken bir hata oluştu.";
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