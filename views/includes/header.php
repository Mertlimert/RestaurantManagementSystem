<!DOCTYPE html>
<html lang="en">
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
                    <i class="fas fa-utensils me-2"></i>Restaurant Management System
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="index.php">Home</a>
                        </li>
                        <?php
                        // Oturumun başlatıldığından emin olalım (eğer başka bir yerde başlatılmadıysa)
                        if (session_status() == PHP_SESSION_NONE) {
                            session_start();
                        }

                        // Admin girişi için kullanılacak session değişkenlerini kontrol et
                        if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true && isset($_SESSION["admin"]) && $_SESSION["admin"] === true):
                        ?>
                            <li class="nav-item">
                                <a class="nav-link" href="admin/index.php">Admin Panel</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="logout.php">Logout (<?php echo htmlspecialchars($_SESSION["username"]); ?>)</a>
                            </li>
                        <?php else: ?>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="adminLoginDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    Admin Login
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="adminLoginDropdown">
                                    <li>
                                        <form class="px-4 py-3" action="admin_login_handler.php" method="post">
                                            <div class="mb-3">
                                                <label for="adminUsername" class="form-label">Username</label>
                                                <input type="text" class="form-control" id="adminUsername" name="username" placeholder="admin" required>
                                            </div>
                                            <div class="mb-3">
                                                <label for="adminPassword" class="form-label">Password</label>
                                                <input type="password" class="form-control" id="adminPassword" name="password" placeholder="123456" required>
                                            </div>
                                            <button type="submit" class="btn btn-primary">Login</button>
                                        </form>
                                    </li>
                                </ul>
                            </li>
                            <?php
                            // Mevcut employee login/logout (eğer varsa ve farklı bir sistemse korunabilir)
                            // Bu örnekte admin login'e odaklanıyorum veya admin login ile birleştiriyorum.
                            /*
                            if(is_logged_in()): ?>
                                <li class="nav-item">
                                    <a class="nav-link" href="index.php?page=menu">Menu</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="index.php?page=orders">Orders</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="index.php?page=tables">Tables</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="index.php?page=reservations">Reservations</a>
                                </li>
                                <?php if(is_admin()): ?> // Bu is_admin() farklı bir admin tanımı olabilir.
                                    <li class="nav-item dropdown">
                                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                                            Management (Employee)
                                        </a>
                                        <ul class="dropdown-menu">
                                            <li><a class="dropdown-item" href="index.php?page=employees">Employees</a></li>
                                            <li><a class="dropdown-item" href="index.php?page=inventory">Inventory</a></li>
                                            <li><a class="dropdown-item" href="index.php?page=reports">Reports</a></li>
                                        </ul>
                                    </li>
                                <?php endif; ?>
                                <li class="nav-item">
                                    <a class="nav-link" href="index.php?page=logout">Logout (Employee)</a>
                                </li>
                            <?php else: ?>
                                <li class="nav-item">
                                    <a class="nav-link" href="index.php?page=login">Login (Employee)</a>
                                </li>
                            <?php endif; */
                            ?>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </nav>
    </header>
    
    <main class="container py-4">
        <!-- Main content will come here -->
    </main>
</body>
</html> 