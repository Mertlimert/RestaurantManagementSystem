<?php
// Oturum başlat
session_start();

// Eğer kullanıcı zaten giriş yapmışsa ve admin ise, admin paneline yönlendir
if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true && isset($_SESSION["admin"]) && $_SESSION["admin"] === true) {
    header("location: admin/index.php");
    exit;
}

// Veritabanı bağlantısını dahil et
require_once 'config/database.php';

// Hata ve başarı mesajları için değişkenler
$login_err = "";
$success_msg = "";

// Form gönderildi mi kontrol et
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Kullanıcı adı ve şifre alınıyor
    $username = trim($_POST["username"]);
    $password = trim($_POST["password"]);
    
    // Basit doğrulama - gerçek projede veritabanından kontrol edilmeli
    if ($username == "admin" && $password == "123456") {
        // Giriş başarılı, oturum değişkenlerini ayarla
        $_SESSION["loggedin"] = true;
        $_SESSION["username"] = $username;
        $_SESSION["admin"] = true;
        
        // Kullanıcıyı yönlendir
        header("location: admin/index.php");
        exit;
    } else {
        $login_err = "Geçersiz kullanıcı adı veya şifre!";
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restoran Yönetim Sistemi - Giriş</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
        }
        .login-container {
            max-width: 400px;
            margin: 100px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .login-header {
            text-align: center;
            margin-bottom: 20px;
        }
        .login-header h2 {
            color: #343a40;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .btn-login {
            background-color: #28a745;
            color: white;
            width: 100%;
        }
        .error-message {
            color: #dc3545;
            margin-top: 10px;
            text-align: center;
        }
        .success-message {
            color: #28a745;
            margin-top: 10px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-container">
            <div class="login-header">
                <h2>Restoran Yönetim Sistemi</h2>
                <p>Lütfen giriş yapın</p>
            </div>
            
            <?php
            if (!empty($login_err)) {
                echo '<div class="error-message">' . $login_err . '</div>';
            }
            if (!empty($success_msg)) {
                echo '<div class="success-message">' . $success_msg . '</div>';
            }
            ?>
            
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="form-group">
                    <label>Kullanıcı Adı</label>
                    <input type="text" name="username" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Şifre</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <div class="form-group">
                    <input type="submit" class="btn btn-login" value="Giriş Yap">
                </div>
                <p class="text-center">Kullanıcı adı: admin / Şifre: 123456</p>
            </form>
        </div>
    </div>
</body>
</html> 