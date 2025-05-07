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
require_once '../models/Reservation.php';
require_once '../models/Tables.php';

// POST verilerini kontrol et
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Form verilerini al
    $customer_id = $_POST['customer_id'];
    $table_id = $_POST['table_id'];
    $reservation_date = $_POST['reservation_date'];
    $reservation_time = $_POST['reservation_time'];
    $reservation_datetime = $reservation_date . ' ' . $reservation_time . ':00';
    $duration = $_POST['duration'];
    $guest_count = $_POST['guest_count'];
    $status = $_POST['status'];
    $notes = isset($_POST['notes']) ? $_POST['notes'] : null;
    
    // Modelleri oluştur
    $reservationModel = new Reservation($conn);
    $tableModel = new Tables($conn);
    
    // Çakışan rezervasyon kontrolü
    if ($reservationModel->checkConflictingReservations($table_id, $reservation_datetime, $duration)) {
        $_SESSION['error_msg'] = "Bu masa ve saat için başka bir rezervasyon mevcut.";
        header("location: reservations.php");
        exit;
    }
    
    // Rezervasyonu ekle
    $reservation_id = $reservationModel->addReservation(
        $customer_id, 
        $table_id, 
        $reservation_datetime, 
        $duration, 
        $guest_count, 
        $status, 
        $notes
    );
    
    if ($reservation_id) {
        // Masanın durumunu "reserved" olarak güncelle
        $tableModel->updateTableStatus($table_id, 'reserved');
        
        $_SESSION['success_msg'] = "Rezervasyon başarıyla eklendi.";
        header("location: reservations.php");
        exit;
    } else {
        $_SESSION['error_msg'] = "Rezervasyon eklenirken bir hata oluştu.";
        header("location: reservations.php");
        exit;
    }
} else {
    // POST değilse, rezervasyonlar sayfasına yönlendir
    header("location: reservations.php");
    exit;
}
?> 