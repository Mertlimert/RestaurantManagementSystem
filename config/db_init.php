<?php
// Veritabanı bağlantısını dahil et
require_once 'database.php';

// Veritabanı oluşturma ve seçme
$sql = "CREATE DATABASE IF NOT EXISTS restaurant_db";
if (mysqli_query($conn, $sql)) {
    // Veritabanı seç
    mysqli_select_db($conn, "restaurant_db");
    
    // Tabloları oluştur
    
    // Customers tablosu
    $sql = "CREATE TABLE IF NOT EXISTS Customers (
        customer_id INT AUTO_INCREMENT PRIMARY KEY,
        first_name VARCHAR(50) NOT NULL,
        last_name VARCHAR(50) NOT NULL,
        phone_number VARCHAR(20),
        email VARCHAR(100),
        address VARCHAR(255)
    ) ENGINE=InnoDB";
    mysqli_query($conn, $sql);
    
    // EmployeePositions tablosu
    $sql = "CREATE TABLE IF NOT EXISTS EmployeePositions (
        position_id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(50) NOT NULL
    ) ENGINE=InnoDB";
    mysqli_query($conn, $sql);
    
    // Employees tablosu
    $sql = "CREATE TABLE IF NOT EXISTS Employees (
        employee_id INT AUTO_INCREMENT PRIMARY KEY,
        first_name VARCHAR(50) NOT NULL,
        last_name VARCHAR(50) NOT NULL,
        position_id INT NOT NULL,
        phone_number VARCHAR(20),
        email VARCHAR(100),
        hourly_rate DECIMAL(3,1) NOT NULL,
        hire_date DATE,
        FOREIGN KEY (position_id) REFERENCES EmployeePositions(position_id)
    ) ENGINE=InnoDB";
    mysqli_query($conn, $sql);
    
    // Tables tablosu
    $sql = "CREATE TABLE IF NOT EXISTS `Tables` (
        table_id INT AUTO_INCREMENT PRIMARY KEY,
        number_of_customers INT NOT NULL,
        capacity INT NOT NULL,
        table_status ENUM('available', 'occupied', 'reserved') DEFAULT 'available'
    ) ENGINE=InnoDB";
    mysqli_query($conn, $sql);
    
    // Ingredients tablosu
    $sql = "CREATE TABLE IF NOT EXISTS Ingredients (
        ingredient_id INT AUTO_INCREMENT PRIMARY KEY,
        ingredient_name VARCHAR(100) NOT NULL,
        unit VARCHAR(20) NOT NULL,
        stock_quantity DECIMAL(10,2) NOT NULL DEFAULT 0,
        allergen VARCHAR(50)
    ) ENGINE=InnoDB";
    mysqli_query($conn, $sql);
    
    // MenuItems tablosu
    $sql = "CREATE TABLE IF NOT EXISTS MenuItems (
        menu_item_id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        category VARCHAR(50),
        price DECIMAL(8,2) NOT NULL,
        description TEXT
    ) ENGINE=InnoDB";
    mysqli_query($conn, $sql);
    
    // MenuItemIngredients tablosu
    $sql = "CREATE TABLE IF NOT EXISTS MenuItemIngredients (
        menu_item_id INT NOT NULL,
        ingredient_id INT NOT NULL,
        quantity_required DECIMAL(10,2) NOT NULL,
        PRIMARY KEY (menu_item_id, ingredient_id),
        FOREIGN KEY (menu_item_id) REFERENCES MenuItems(menu_item_id) ON DELETE CASCADE,
        FOREIGN KEY (ingredient_id) REFERENCES Ingredients(ingredient_id) ON DELETE CASCADE
    ) ENGINE=InnoDB";
    mysqli_query($conn, $sql);
    
    // Reservations tablosu
    $sql = "CREATE TABLE IF NOT EXISTS Reservations (
        reservation_id INT AUTO_INCREMENT PRIMARY KEY,
        customer_id INT NOT NULL,
        table_id INT NOT NULL,
        reservation_datetime DATETIME NOT NULL,
        reservation_status ENUM('available', 'reserved') DEFAULT 'available',
        FOREIGN KEY (customer_id) REFERENCES Customers(customer_id),
        FOREIGN KEY (table_id) REFERENCES Tables(table_id)
    ) ENGINE=InnoDB";
    mysqli_query($conn, $sql);
    
    // Orders tablosu
    $sql = "CREATE TABLE IF NOT EXISTS Orders (
        order_id INT AUTO_INCREMENT PRIMARY KEY,
        customer_id INT,
        employee_id INT,
        table_id INT,
        order_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        total_amount DECIMAL(10,2),
        order_status ENUM('ordered', 'preparing', 'served', 'paid') DEFAULT 'ordered',
        FOREIGN KEY (customer_id) REFERENCES Customers(customer_id),
        FOREIGN KEY (employee_id) REFERENCES Employees(employee_id),
        FOREIGN KEY (table_id) REFERENCES Tables(table_id)
    ) ENGINE=InnoDB";
    mysqli_query($conn, $sql);
    
    // OrderDetails tablosu
    $sql = "CREATE TABLE IF NOT EXISTS OrderDetails (
        order_id INT NOT NULL,
        menu_item_id INT NOT NULL,
        quantity INT NOT NULL,
        price DECIMAL(8,2) NOT NULL,
        special_instructions TEXT,
        FOREIGN KEY (order_id) REFERENCES Orders(order_id) ON DELETE CASCADE,
        FOREIGN KEY (menu_item_id) REFERENCES MenuItems(menu_item_id)
    ) ENGINE=InnoDB";
    mysqli_query($conn, $sql);
    
    // Payments tablosu
    $sql = "CREATE TABLE IF NOT EXISTS Payments (
        payment_id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        payment_method ENUM('cash', 'credit', 'debit') NOT NULL,
        total_amount DECIMAL(10,2) NOT NULL,
        tip_amount DECIMAL(10,2) NOT NULL,
        payment_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (order_id) REFERENCES Orders(order_id)
    ) ENGINE=InnoDB";
    mysqli_query($conn, $sql);
    
    // ShiftTypes tablosu
    $sql = "CREATE TABLE IF NOT EXISTS ShiftTypes (
        shift_type_id INT AUTO_INCREMENT PRIMARY KEY,
        shift_name VARCHAR(50) NOT NULL,
        start_time TIME NOT NULL,
        end_time TIME NOT NULL,
        description TEXT
    ) ENGINE=InnoDB";
    mysqli_query($conn, $sql);
    
    // EmployeeShifts tablosu
    $sql = "CREATE TABLE IF NOT EXISTS EmployeeShifts (
        employee_id INT NOT NULL,
        shift_type_id INT NOT NULL,
        shift_date DATE NOT NULL,
        FOREIGN KEY (employee_id) REFERENCES Employees(employee_id),
        FOREIGN KEY (shift_type_id) REFERENCES ShiftTypes(shift_type_id)
    ) ENGINE=InnoDB";
    mysqli_query($conn, $sql);
    
    echo "Veritabanı ve tablolar başarıyla oluşturuldu.";
} else {
    echo "Veritabanı oluşturulurken hata: " . mysqli_error($conn);
}

// Bağlantıyı kapat
mysqli_close($conn);
?> 