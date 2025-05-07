<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Ana stil dosyası -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header class="bg-dark text-white">
        <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
            <div class="container">
                <a class="navbar-brand" href="index.php">
                    <i class="fas fa-utensils me-2"></i>Restoran Yönetim Sistemi
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="index.php">Ana Sayfa</a>
                        </li>
                        <?php if(is_logged_in()): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="index.php?page=menu">Menü</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="index.php?page=orders">Siparişler</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="index.php?page=tables">Masalar</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="index.php?page=reservations">Rezervasyonlar</a>
                            </li>
                            <?php if(is_admin()): ?>
                                <li class="nav-item dropdown">
                                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                                        Yönetim
                                    </a>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="index.php?page=employees">Çalışanlar</a></li>
                                        <li><a class="dropdown-item" href="index.php?page=inventory">Envanter</a></li>
                                        <li><a class="dropdown-item" href="index.php?page=reports">Raporlar</a></li>
                                    </ul>
                                </li>
                            <?php endif; ?>
                            <li class="nav-item">
                                <a class="nav-link" href="index.php?page=logout">Çıkış</a>
                            </li>
                        <?php else: ?>
                            <li class="nav-item">
                                <a class="nav-link" href="index.php?page=login">Giriş</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </nav>
    </header>
    
    <main class="container py-4">
        <!-- Ana içerik buraya gelecek -->
    </main>
</body>
</html> 