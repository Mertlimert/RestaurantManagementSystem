<?php
// Oturum başlat
session_start();

// Gerekli dosyaları dahil et
require_once "config/database.php";
require_once "includes/functions.php";

// Varsayılan sayfa
$page = isset($_GET['page']) ? $_GET['page'] : 'home';

// Sayfa başlığı
$title = "Restoran Yönetim Sistemi";

// Üst kısmı dahil et
include "views/includes/header.php";

// Sayfayı dahil et
if(file_exists("views/pages/{$page}.php")) {
    include "views/pages/{$page}.php";
} else {
    include "views/pages/404.php";
}

// Alt kısmı dahil et
include "views/includes/footer.php";
?> 