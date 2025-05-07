<?php
// Oturumu temizle ve sonlandır
$_SESSION = array();
session_destroy();

// Ana sayfaya yönlendir
redirect('index.php');
?> 