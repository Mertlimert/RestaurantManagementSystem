<?php
// Start session
session_start();

// Database configuration - include directly instead of using the database.php file
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'restaurant_db');

// Create connection directly
$conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD);
if (!$conn) {
    die("HATA: Veritabanına bağlanılamadı. " . mysqli_connect_error());
}

// First create the database
$sql = "CREATE DATABASE IF NOT EXISTS " . DB_NAME;
if (mysqli_query($conn, $sql)) {
    echo "Veritabanı kontrol edildi veya oluşturuldu.<br>";
    
    // Select the database
    mysqli_select_db($conn, DB_NAME);

    // Run db_init.php to ensure all tables exist
    echo "Veritabanı tablolarını oluşturuluyor...<br>";
    require_once '../config/db_init.php';
    
    // Re-establish connection after db_init closes it
    $conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
    if (!$conn) {
        die("HATA: Veritabanına bağlanılamadı. " . mysqli_connect_error());
    }

    // Include model classes
    require_once '../models/Customer.php';
    require_once '../models/Employee.php';
    require_once '../models/Ingredient.php';
    require_once '../models/MenuItem.php';
    require_once '../models/Order.php';
    require_once '../models/Reservation.php';
    require_once '../models/Tables.php';

    // Create instances of models
    $customerModel = new Customer($conn);
    $employeeModel = new Employee($conn);
    $ingredientModel = new Ingredient($conn);
    $menuItemModel = new MenuItem($conn);
    $orderModel = new Order($conn);
    $reservationModel = new Reservation($conn);
    $tableModel = new Tables($conn);

    // Initialize counters for tracking created records
    $createdTables = 0;
    $createdCustomers = 0;
    $createdEmployees = 0;
    $createdPositions = 0;
    $createdIngredients = 0;
    $createdMenuItems = 0;
    $createdOrders = 0;
    $createdReservations = 0;

    // *** First, create and populate Employee Positions BEFORE the transaction starts ***
    echo "EmployeePositions oluşturuluyor...<br>";
    
    // Check if position table exists and has data
    $checkPositions = mysqli_query($conn, "SELECT COUNT(*) as count FROM EmployeePositions");
    if (!$checkPositions) {
        echo "EmployeePositions tablosu bulunamadı, tekrar oluşturuluyor...<br>";
        
        // Create table if needed
        $createPositionsTable = "CREATE TABLE IF NOT EXISTS EmployeePositions (
            position_id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(50) NOT NULL
        ) ENGINE=InnoDB";
        
        if (mysqli_query($conn, $createPositionsTable)) {
            echo "EmployeePositions tablosu oluşturuldu.<br>";
        } else {
            echo "HATA: EmployeePositions tablosu oluşturulamadı: " . mysqli_error($conn) . "<br>";
        }
    }
    
    // Add positions data
    $positions = [
        ['position_id' => 1, 'title' => 'Manager'],
        ['position_id' => 2, 'title' => 'Chef'],
        ['position_id' => 3, 'title' => 'Sous Chef'],
        ['position_id' => 4, 'title' => 'Server'],
        ['position_id' => 5, 'title' => 'Bartender'],
        ['position_id' => 6, 'title' => 'Host/Hostess'],
        ['position_id' => 7, 'title' => 'Busser'],
        ['position_id' => 8, 'title' => 'Dishwasher']
    ];
    
    foreach ($positions as $position) {
        // Check if position exists by ID or title
        $checkPosition = "SELECT COUNT(*) as count FROM EmployeePositions WHERE position_id = ? OR title = ?";
        $checkStmt = mysqli_prepare($conn, $checkPosition);
        mysqli_stmt_bind_param($checkStmt, "is", $position['position_id'], $position['title']);
        mysqli_stmt_execute($checkStmt);
        $result = mysqli_stmt_get_result($checkStmt);
        $positionExists = mysqli_fetch_assoc($result)['count'] > 0;
        
        if (!$positionExists) {
            // Insert position if it doesn't exist
            $query = "INSERT INTO EmployeePositions (position_id, title) VALUES (?, ?)";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "is", $position['position_id'], $position['title']);
            
            if (mysqli_stmt_execute($stmt)) {
                $createdPositions++;
                echo "Pozisyon eklendi: {$position['title']}<br>";
            } else {
                echo "HATA: Pozisyon eklenemedi: " . mysqli_stmt_error($stmt) . "<br>";
            }
        } else {
            echo "Pozisyon zaten var: {$position['title']}<br>";
        }
    }

    // Start transaction
    mysqli_begin_transaction($conn);

    try {
        // 1. Create Tables
        echo "Masalar oluşturuluyor...<br>";
        $tables = [
            ['capacity' => 2, 'number_of_customers' => 0, 'table_status' => 'available'],
            ['capacity' => 2, 'number_of_customers' => 0, 'table_status' => 'available'],
            ['capacity' => 4, 'number_of_customers' => 0, 'table_status' => 'available'],
            ['capacity' => 4, 'number_of_customers' => 0, 'table_status' => 'available'],
            ['capacity' => 4, 'number_of_customers' => 0, 'table_status' => 'available'],
            ['capacity' => 6, 'number_of_customers' => 0, 'table_status' => 'available'],
            ['capacity' => 8, 'number_of_customers' => 0, 'table_status' => 'available'],
            ['capacity' => 10, 'number_of_customers' => 0, 'table_status' => 'available']
        ];

        foreach ($tables as $table) {
            $query = "INSERT INTO `Tables` (capacity, number_of_customers, table_status) VALUES (?, ?, ?)";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "iis", $table['capacity'], $table['number_of_customers'], $table['table_status']);
            mysqli_stmt_execute($stmt);
            $createdTables++;
        }

        // 2. Create Customers
        echo "Müşteriler oluşturuluyor...<br>";
        $customers = [
            ['first_name' => 'John', 'last_name' => 'Doe', 'phone_number' => '555-123-4567', 'email' => 'john.doe@example.com', 'address' => '123 Main St, City'],
            ['first_name' => 'Jane', 'last_name' => 'Smith', 'phone_number' => '555-987-6543', 'email' => 'jane.smith@example.com', 'address' => '456 Oak Ave, City'],
            ['first_name' => 'Michael', 'last_name' => 'Johnson', 'phone_number' => '555-222-3333', 'email' => 'michael.j@example.com', 'address' => '789 Pine Rd, City'],
            ['first_name' => 'Emma', 'last_name' => 'Williams', 'phone_number' => '555-444-5555', 'email' => 'emma.w@example.com', 'address' => '101 Cedar Ln, City'],
            ['first_name' => 'James', 'last_name' => 'Brown', 'phone_number' => '555-666-7777', 'email' => 'james.b@example.com', 'address' => '202 Elm St, City'],
            ['first_name' => 'Olivia', 'last_name' => 'Jones', 'phone_number' => '555-888-9999', 'email' => 'olivia.j@example.com', 'address' => '303 Maple Dr, City'],
            ['first_name' => 'William', 'last_name' => 'Garcia', 'phone_number' => '555-111-2222', 'email' => 'william.g@example.com', 'address' => '404 Birch Ave, City'],
            ['first_name' => 'Sophia', 'last_name' => 'Miller', 'phone_number' => '555-333-4444', 'email' => 'sophia.m@example.com', 'address' => '505 Walnut St, City'],
            ['first_name' => 'Robert', 'last_name' => 'Taylor', 'phone_number' => '555-555-6666', 'email' => 'robert.t@example.com', 'address' => '606 Cherry Ln, City'],
            ['first_name' => 'Ava', 'last_name' => 'Anderson', 'phone_number' => '555-777-8888', 'email' => 'ava.a@example.com', 'address' => '707 Spruce Rd, City']
        ];

        foreach ($customers as $customer) {
            $query = "INSERT INTO Customers (first_name, last_name, phone_number, email, address) VALUES (?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "sssss", 
                $customer['first_name'], 
                $customer['last_name'], 
                $customer['phone_number'], 
                $customer['email'], 
                $customer['address']
            );
            
            if (mysqli_stmt_execute($stmt)) {
                $createdCustomers++;
            } else {
                echo "HATA: Müşteri eklenemedi: " . mysqli_stmt_error($stmt) . "<br>";
            }
        }

        // 3. Create Employees
        echo "Çalışanlar oluşturuluyor...<br>";
        $employees = [
            ['first_name' => 'David', 'last_name' => 'Wilson', 'position_id' => 1, 'phone_number' => '555-111-0000', 'email' => 'david.w@restaurant.com', 'hourly_rate' => 25.0, 'hire_date' => '2021-01-10'],
            ['first_name' => 'Maria', 'last_name' => 'Rodriguez', 'position_id' => 2, 'phone_number' => '555-222-0000', 'email' => 'maria.r@restaurant.com', 'hourly_rate' => 22.5, 'hire_date' => '2021-02-15'],
            ['first_name' => 'Thomas', 'last_name' => 'Chen', 'position_id' => 3, 'phone_number' => '555-333-0000', 'email' => 'thomas.c@restaurant.com', 'hourly_rate' => 18.5, 'hire_date' => '2021-03-05'],
            ['first_name' => 'Sarah', 'last_name' => 'Lopez', 'position_id' => 4, 'phone_number' => '555-444-0000', 'email' => 'sarah.l@restaurant.com', 'hourly_rate' => 15.0, 'hire_date' => '2021-04-20'],
            ['first_name' => 'Kevin', 'last_name' => 'Kim', 'position_id' => 4, 'phone_number' => '555-555-0000', 'email' => 'kevin.k@restaurant.com', 'hourly_rate' => 15.0, 'hire_date' => '2021-05-12'],
            ['first_name' => 'Rachel', 'last_name' => 'Green', 'position_id' => 5, 'phone_number' => '555-666-0000', 'email' => 'rachel.g@restaurant.com', 'hourly_rate' => 16.5, 'hire_date' => '2021-06-30'],
            ['first_name' => 'Carlos', 'last_name' => 'Perez', 'position_id' => 6, 'phone_number' => '555-777-0000', 'email' => 'carlos.p@restaurant.com', 'hourly_rate' => 14.0, 'hire_date' => '2022-01-15'],
            ['first_name' => 'Ashley', 'last_name' => 'Johnson', 'position_id' => 7, 'phone_number' => '555-888-0000', 'email' => 'ashley.j@restaurant.com', 'hourly_rate' => 13.5, 'hire_date' => '2022-02-28'],
            ['first_name' => 'Daniel', 'last_name' => 'Park', 'position_id' => 8, 'phone_number' => '555-999-0000', 'email' => 'daniel.p@restaurant.com', 'hourly_rate' => 13.0, 'hire_date' => '2022-03-10']
        ];

        foreach ($employees as $employee) {
            $query = "INSERT INTO Employees (first_name, last_name, position_id, phone_number, email, hourly_rate, hire_date) 
                     VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "ssissds", 
                $employee['first_name'],
                $employee['last_name'],
                $employee['position_id'],
                $employee['phone_number'],
                $employee['email'],
                $employee['hourly_rate'],
                $employee['hire_date']
            );
            
            if (mysqli_stmt_execute($stmt)) {
                $createdEmployees++;
            } else {
                echo "HATA: Çalışan eklenemedi: " . mysqli_stmt_error($stmt) . "<br>";
            }
        }

        // 4. Create Ingredients
        echo "Malzemeler oluşturuluyor...<br>";
        $ingredients = [
            ['ingredient_name' => 'Ground Beef', 'unit' => 'kg', 'stock_quantity' => 25.0, 'allergen' => null],
            ['ingredient_name' => 'Chicken Breast', 'unit' => 'kg', 'stock_quantity' => 20.0, 'allergen' => null],
            ['ingredient_name' => 'Salmon Fillet', 'unit' => 'kg', 'stock_quantity' => 15.0, 'allergen' => 'Fish'],
            ['ingredient_name' => 'Pasta', 'unit' => 'kg', 'stock_quantity' => 30.0, 'allergen' => 'Gluten'],
            ['ingredient_name' => 'Rice', 'unit' => 'kg', 'stock_quantity' => 50.0, 'allergen' => null],
            ['ingredient_name' => 'Tomatoes', 'unit' => 'kg', 'stock_quantity' => 15.0, 'allergen' => null],
            ['ingredient_name' => 'Onions', 'unit' => 'kg', 'stock_quantity' => 20.0, 'allergen' => null],
            ['ingredient_name' => 'Garlic', 'unit' => 'kg', 'stock_quantity' => 5.0, 'allergen' => null],
            ['ingredient_name' => 'Lettuce', 'unit' => 'kg', 'stock_quantity' => 10.0, 'allergen' => null],
            ['ingredient_name' => 'Cheese', 'unit' => 'kg', 'stock_quantity' => 12.0, 'allergen' => 'Dairy']
        ];

        foreach ($ingredients as $ingredient) {
            $query = "INSERT INTO Ingredients (ingredient_name, unit, stock_quantity, allergen) VALUES (?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "ssds", 
                $ingredient['ingredient_name'], 
                $ingredient['unit'], 
                $ingredient['stock_quantity'], 
                $ingredient['allergen']
            );
            
            if (mysqli_stmt_execute($stmt)) {
                $createdIngredients++;
            } else {
                echo "HATA: Malzeme eklenemedi: " . mysqli_stmt_error($stmt) . "<br>";
            }
        }

        // 5. Create MenuItems
        echo "Menü öğeleri oluşturuluyor...<br>";
        $menuItems = [
            ['name' => 'Classic Burger', 'category' => 'Main Course', 'price' => 12.99, 'description' => 'Juicy beef patty with lettuce, tomato, onion, and special sauce on a brioche bun.'],
            ['name' => 'Chicken Alfredo Pasta', 'category' => 'Main Course', 'price' => 15.99, 'description' => 'Fettuccine pasta with creamy alfredo sauce and grilled chicken breast.'],
            ['name' => 'Grilled Salmon', 'category' => 'Main Course', 'price' => 18.99, 'description' => 'Fresh salmon fillet grilled to perfection, served with seasonal vegetables.'],
            ['name' => 'Caesar Salad', 'category' => 'Starter', 'price' => 8.99, 'description' => 'Crisp romaine lettuce, parmesan cheese, croutons, and our house-made Caesar dressing.'],
            ['name' => 'Garlic Bread', 'category' => 'Starter', 'price' => 5.99, 'description' => 'Toasted bread with garlic butter and a sprinkle of cheese.']
        ];

        foreach ($menuItems as $menuItem) {
            $query = "INSERT INTO MenuItems (name, category, price, description) VALUES (?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $query);
            
            if (!$stmt) {
                echo "HATA: Sorgu hazırlanamadı: " . mysqli_error($conn) . "<br>";
                continue;
            }
            
            mysqli_stmt_bind_param($stmt, "ssds", 
                $menuItem['name'], 
                $menuItem['category'], 
                $menuItem['price'], 
                $menuItem['description']
            );
            
            if (mysqli_stmt_execute($stmt)) {
                $menuItemId = mysqli_insert_id($conn);
                $createdMenuItems++;
                echo "Menü öğesi eklendi: {$menuItem['name']} (ID: {$menuItemId})<br>";
            } else {
                echo "HATA: Menü öğesi eklenemedi: " . mysqli_stmt_error($stmt) . "<br>";
            }
        }
        
        // 6. Connect menu items with ingredients
        echo "Menü öğeleri ve malzemeler bağlanıyor...<br>";
        
        // Get all menu items
        $menuItemsQuery = "SELECT menu_item_id, name FROM MenuItems";
        $menuItemsResult = mysqli_query($conn, $menuItemsQuery);
        $menuItemsMap = [];
        
        while ($row = mysqli_fetch_assoc($menuItemsResult)) {
            $menuItemsMap[$row['name']] = $row['menu_item_id'];
        }
        
        // Get all ingredients
        $ingredientsQuery = "SELECT ingredient_id, ingredient_name FROM Ingredients";
        $ingredientsResult = mysqli_query($conn, $ingredientsQuery);
        $ingredientsMap = [];
        
        while ($row = mysqli_fetch_assoc($ingredientsResult)) {
            $ingredientsMap[$row['ingredient_name']] = $row['ingredient_id'];
        }
        
        // Connections
        $menuItemConnections = [
            ['menu_name' => 'Classic Burger', 'ingredient_name' => 'Ground Beef', 'quantity' => 0.2],
            ['menu_name' => 'Classic Burger', 'ingredient_name' => 'Lettuce', 'quantity' => 0.05],
            ['menu_name' => 'Classic Burger', 'ingredient_name' => 'Tomatoes', 'quantity' => 0.05],
            ['menu_name' => 'Chicken Alfredo Pasta', 'ingredient_name' => 'Chicken Breast', 'quantity' => 0.15],
            ['menu_name' => 'Chicken Alfredo Pasta', 'ingredient_name' => 'Pasta', 'quantity' => 0.15],
            ['menu_name' => 'Grilled Salmon', 'ingredient_name' => 'Salmon Fillet', 'quantity' => 0.2]
        ];
        
        foreach ($menuItemConnections as $connection) {
            if (isset($menuItemsMap[$connection['menu_name']]) && isset($ingredientsMap[$connection['ingredient_name']])) {
                $menuItemId = $menuItemsMap[$connection['menu_name']];
                $ingredientId = $ingredientsMap[$connection['ingredient_name']];
                
                $query = "INSERT INTO MenuItemIngredients (menu_item_id, ingredient_id, quantity_required) VALUES (?, ?, ?)";
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, "iid", $menuItemId, $ingredientId, $connection['quantity']);
                
                if (mysqli_stmt_execute($stmt)) {
                    echo "Bağlantı eklendi: {$connection['menu_name']} ve {$connection['ingredient_name']}<br>";
                } else {
                    echo "HATA: Bağlantı eklenemedi: " . mysqli_stmt_error($stmt) . "<br>";
                }
            } else {
                echo "HATA: Menü öğesi veya malzeme bulunamadı: {$connection['menu_name']} - {$connection['ingredient_name']}<br>";
            }
        }

        // Create an admin user
        echo "Yönetici kullanıcı oluşturuluyor...<br>";
        $adminQuery = "CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            admin TINYINT(1) NOT NULL DEFAULT 0
        )";
        mysqli_query($conn, $adminQuery);
        
        $adminQuery = "INSERT INTO users (username, password, admin) VALUES ('admin', '$2y$10$gOzIwIQrIIj5gB5zYI9eIO/gMsF84qYA6Olr1iO0jxuJA9GJUFyLO', 1) 
                       ON DUPLICATE KEY UPDATE username=username";
        mysqli_query($conn, $adminQuery);
        
        // Success message
        echo "Veritabanı başarıyla dolduruldu!<br>";
        echo "Oluşturulan kayıtlar:<br>";
        echo "- Masalar: $createdTables<br>";
        echo "- Müşteriler: $createdCustomers<br>";
        echo "- Pozisyonlar: $createdPositions<br>";
        echo "- Çalışanlar: $createdEmployees<br>";
        echo "- Malzemeler: $createdIngredients<br>";
        echo "- Menü öğeleri: $createdMenuItems<br>";
        
        // Commit transaction if everything was successful
        mysqli_commit($conn);
        
        echo "<div style='margin-top: 20px;'>";
        echo "<a href='../login.php'>Giriş Yap</a> | ";
        echo "<a href='index.php'>Yönetici Paneli</a>";
        echo "</div>";
        
    } catch (Exception $e) {
        // Rollback in case of error
        mysqli_rollback($conn);
        echo "HATA: " . $e->getMessage() . "<br>";
    }
} else {
    echo "HATA: Veritabanı oluşturulamadı: " . mysqli_error($conn) . "<br>";
}

// Close connection
mysqli_close($conn);
?> 