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

// ID parametresi kontrolü
if (isset($_GET["id"]) && !empty(trim($_GET["id"]))) {
    // URL'den ID parametresini al
    $position_id = trim($_GET["id"]);
    
    // Pozisyonu sil
    if ($employeeModel->deletePosition($position_id)) {
        // Başarıyla silindi
        $_SESSION['success_msg'] = "Pozisyon başarıyla silindi.";
    } else {
        // Hata oluştu
        $_SESSION['error_msg'] = "Pozisyon silinirken bir hata oluştu. Bu pozisyonda çalışanlar olabilir.";
    }
} else {
    // URL'de ID parametresi yok
    $_SESSION['error_msg'] = "Geçersiz pozisyon ID'si.";
}

// Çalışanlar sayfasına yönlendir
header("location: employees.php");
exit();
?> 