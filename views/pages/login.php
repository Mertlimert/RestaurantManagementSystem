<?php
// Kullanıcı giriş yapmış mı kontrol et
if(is_logged_in()) {
    redirect('index.php');
}

// Form gönderildiğinde
if($_SERVER["REQUEST_METHOD"] == "POST") {
    // Hata değişkeni
    $error = '';
    
    // E-posta ve şifre 
    if(empty(trim($_POST["email"]))) {
        $error = "Lütfen e-posta adresinizi girin.";
    } else {
        $email = clean_input($_POST["email"]);
    }
    
    if(empty($error) && empty(trim($_POST["password"]))) {
        $error = "Lütfen şifrenizi girin.";
    } else {
        $password = clean_input($_POST["password"]);
    }
    
    // Hata yoksa veritabanı sorgusu
    if(empty($error)) {
        $sql = "SELECT e.employee_id, e.name, e.email, p.title FROM EMPLOYEE e 
                JOIN EMPLOYEE_POSITION p ON e.position_id = p.position_id 
                WHERE e.email = ?";
        
        if($stmt = mysqli_prepare($conn, $sql)) {
            // Parametreleri bağla
            mysqli_stmt_bind_param($stmt, "s", $param_email);
            
            // Parametreleri ayarla
            $param_email = $email;
            
            // Çalıştır
            if(mysqli_stmt_execute($stmt)) {
                // Sonuçları sakla
                mysqli_stmt_store_result($stmt);
                
                // E-posta var mı kontrol et
                if(mysqli_stmt_num_rows($stmt) == 1) {                    
                    // Sonuçları bağla
                    mysqli_stmt_bind_result($stmt, $employee_id, $name, $email, $position);
                    if(mysqli_stmt_fetch($stmt)) {
                        // Örnek olarak, gerçek uygulamada parola hash kontrolü yapılmalı
                        // if(password_verify($password, $hashed_password)) {
                        if($password == "1234") { // Örnek basit şifre
                            // Oturum değişkenlerini başlat
                            $_SESSION["logged_in"] = true;
                            $_SESSION["employee_id"] = $employee_id;
                            $_SESSION["name"] = $name;
                            $_SESSION["email"] = $email;
                            $_SESSION["position"] = $position;
                            
                            // Ana sayfaya yönlendir
                            redirect("index.php");
                        } else {
                            $error = "Girilen şifre yanlış.";
                        }
                    }
                } else {
                    $error = "Geçersiz e-posta adresi.";
                }
            } else {
                $error = "Bir şeyler yanlış gitti, lütfen daha sonra tekrar deneyin.";
            }

            // Statementı kapat
            mysqli_stmt_close($stmt);
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card shadow">
            <div class="card-header bg-primary text-white text-center py-3">
                <h4 class="mb-0">Giriş Yap</h4>
            </div>
            <div class="card-body p-4">
                <?php if(!empty($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . '?page=login'; ?>" method="post">
                    <div class="mb-3">
                        <label for="email" class="form-label">E-posta Adresi</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                            <input type="email" class="form-control" id="email" name="email" placeholder="E-posta adresinizi girin" required>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label for="password" class="form-label">Şifre</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control" id="password" name="password" placeholder="Şifrenizi girin" required>
                        </div>
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">Giriş Yap</button>
                    </div>
                </form>
            </div>
            <div class="card-footer text-center py-3 bg-light">
                <p class="mb-0">
                    <small class="text-muted">Örnek giriş: admin@restoransistemi.com / 1234</small>
                </p>
            </div>
        </div>
    </div>
</div> 