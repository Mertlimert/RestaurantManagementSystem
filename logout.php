<?php
// Oturum başlat
session_start();

// Tüm oturum değişkenlerini unset edelim
$_SESSION = array();

// Oturum çerezini silelim
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Oturumu yok edelim
session_destroy();

// Kullanıcıyı giriş sayfasına yönlendirelim
header("location: login.php");
exit;
?> 