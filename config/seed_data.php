<?php
// Veritabanı bağlantısını dahil et
require_once 'database.php';

// Veritabanını seç
mysqli_select_db($conn, "restaurant_db");

// Örnek veri ekleme fonksiyonu - hata kontrolü ile
function insertData($conn, $sql, $message) {
    if (mysqli_query($conn, $sql)) {
        echo $message . " başarıyla eklendi.<br>";
    } else {
        echo "HATA: " . $message . " eklenirken hata oluştu: " . mysqli_error($conn) . "<br>";
    }
}

// EmployeePositions - Pozisyonlar
$positions = [
    "INSERT INTO EmployeePositions (title) VALUES ('Yönetici')",
    "INSERT INTO EmployeePositions (title) VALUES ('Şef')",
    "INSERT INTO EmployeePositions (title) VALUES ('Garson')",
    "INSERT INTO EmployeePositions (title) VALUES ('Kasiyer')",
    "INSERT INTO EmployeePositions (title) VALUES ('Bulaşıkçı')"
];

foreach ($positions as $sql) {
    insertData($conn, $sql, "Pozisyon");
}

// Employees - Çalışanlar
$employees = [
    "INSERT INTO Employees (first_name, last_name, position_id, phone_number, email, hourly_rate, hire_date) 
     VALUES ('Ahmet', 'Yılmaz', 1, '5551234567', 'ahmet@restoran.com', 30.0, '2020-01-15')",
    "INSERT INTO Employees (first_name, last_name, position_id, phone_number, email, hourly_rate, hire_date) 
     VALUES ('Mehmet', 'Demir', 2, '5552345678', 'mehmet@restoran.com', 25.0, '2020-02-10')",
    "INSERT INTO Employees (first_name, last_name, position_id, phone_number, email, hourly_rate, hire_date) 
     VALUES ('Ayşe', 'Kaya', 3, '5553456789', 'ayse@restoran.com', 15.0, '2021-03-20')",
    "INSERT INTO Employees (first_name, last_name, position_id, phone_number, email, hourly_rate, hire_date) 
     VALUES ('Fatma', 'Şahin', 4, '5554567890', 'fatma@restoran.com', 15.0, '2021-06-15')",
    "INSERT INTO Employees (first_name, last_name, position_id, phone_number, email, hourly_rate, hire_date) 
     VALUES ('Ali', 'Çelik', 5, '5555678901', 'ali@restoran.com', 12.5, '2022-01-10')"
];

foreach ($employees as $sql) {
    insertData($conn, $sql, "Çalışan");
}

// Tables - Masalar
$tables = [
    "INSERT INTO `Tables` (number_of_customers, capacity, table_status) VALUES (0, 2, 'available')",
    "INSERT INTO `Tables` (number_of_customers, capacity, table_status) VALUES (0, 4, 'available')",
    "INSERT INTO `Tables` (number_of_customers, capacity, table_status) VALUES (0, 4, 'available')",
    "INSERT INTO `Tables` (number_of_customers, capacity, table_status) VALUES (0, 6, 'available')",
    "INSERT INTO `Tables` (number_of_customers, capacity, table_status) VALUES (0, 8, 'available')"
];

foreach ($tables as $sql) {
    insertData($conn, $sql, "Masa");
}

// Customers - Müşteriler
$customers = [
    "INSERT INTO Customers (first_name, last_name, phone_number, email, address) 
     VALUES ('Murat', 'Yıldız', '5321234567', 'murat@gmail.com', 'Atatürk Cad. No:123 İstanbul')",
    "INSERT INTO Customers (first_name, last_name, phone_number, email, address) 
     VALUES ('Zeynep', 'Öztürk', '5332345678', 'zeynep@gmail.com', 'Cumhuriyet Sok. No:45 Ankara')",
    "INSERT INTO Customers (first_name, last_name, phone_number, email, address) 
     VALUES ('Kemal', 'Aydın', '5363456789', 'kemal@gmail.com', 'İnönü Cad. No:78 İzmir')",
    "INSERT INTO Customers (first_name, last_name, phone_number, email, address) 
     VALUES ('Elif', 'Korkmaz', '5394567890', 'elif@gmail.com', 'Bağdat Cad. No:56 İstanbul')",
    "INSERT INTO Customers (first_name, last_name, phone_number, email, address) 
     VALUES ('Mustafa', 'Aksoy', '5425678901', 'mustafa@gmail.com', 'Gazi Cad. No:34 Antalya')"
];

foreach ($customers as $sql) {
    insertData($conn, $sql, "Müşteri");
}

// Ingredients - Malzemeler
$ingredients = [
    "INSERT INTO Ingredients (ingredient_name, unit, stock_quantity, allergen) 
     VALUES ('Domates', 'kg', 50.0, NULL)",
    "INSERT INTO Ingredients (ingredient_name, unit, stock_quantity, allergen) 
     VALUES ('Soğan', 'kg', 30.0, NULL)",
    "INSERT INTO Ingredients (ingredient_name, unit, stock_quantity, allergen) 
     VALUES ('Kıyma', 'kg', 20.0, NULL)",
    "INSERT INTO Ingredients (ingredient_name, unit, stock_quantity, allergen) 
     VALUES ('Tavuk Göğsü', 'kg', 25.0, NULL)",
    "INSERT INTO Ingredients (ingredient_name, unit, stock_quantity, allergen) 
     VALUES ('Un', 'kg', 40.0, 'Gluten')",
    "INSERT INTO Ingredients (ingredient_name, unit, stock_quantity, allergen) 
     VALUES ('Süt', 'litre', 30.0, 'Laktoz')",
    "INSERT INTO Ingredients (ingredient_name, unit, stock_quantity, allergen) 
     VALUES ('Yumurta', 'adet', 100.0, NULL)",
    "INSERT INTO Ingredients (ingredient_name, unit, stock_quantity, allergen) 
     VALUES ('Peynir', 'kg', 15.0, 'Laktoz')",
    "INSERT INTO Ingredients (ingredient_name, unit, stock_quantity, allergen) 
     VALUES ('Pirinç', 'kg', 35.0, NULL)",
    "INSERT INTO Ingredients (ingredient_name, unit, stock_quantity, allergen) 
     VALUES ('Salatalık', 'kg', 20.0, NULL)"
];

foreach ($ingredients as $sql) {
    insertData($conn, $sql, "Malzeme");
}

// MenuItems - Menü Öğeleri
$menuItems = [
    "INSERT INTO MenuItems (name, category, price, description) 
     VALUES ('Köfte', 'Ana Yemek', 75.00, 'Dana kıyma ile hazırlanmış enfes köfte')",
    "INSERT INTO MenuItems (name, category, price, description) 
     VALUES ('Tavuk Şiş', 'Ana Yemek', 65.00, 'Marine edilmiş tavuk göğsü şiş')",
    "INSERT INTO MenuItems (name, category, price, description) 
     VALUES ('Mevsim Salata', 'Salata', 35.00, 'Taze mevsim sebzeleri ile hazırlanmış salata')",
    "INSERT INTO MenuItems (name, category, price, description) 
     VALUES ('Pizza', 'Ana Yemek', 80.00, 'Karışık malzemeli pizza')",
    "INSERT INTO MenuItems (name, category, price, description) 
     VALUES ('Pilav', 'Yan Yemek', 25.00, 'Tereyağlı pirinç pilavı')",
    "INSERT INTO MenuItems (name, category, price, description) 
     VALUES ('Ayran', 'İçecek', 15.00, 'Taze ayran')",
    "INSERT INTO MenuItems (name, category, price, description) 
     VALUES ('Künefe', 'Tatlı', 50.00, 'Antep fıstıklı künefe')",
    "INSERT INTO MenuItems (name, category, price, description) 
     VALUES ('Kola', 'İçecek', 20.00, 'Soğuk kola')"
];

foreach ($menuItems as $sql) {
    insertData($conn, $sql, "Menü öğesi");
}

// MenuItemIngredients - Menü Öğeleri Malzemeleri
$menuItemIngredients = [
    "INSERT INTO MenuItemIngredients (menu_item_id, ingredient_id, quantity_required) VALUES (1, 3, 0.2)",
    "INSERT INTO MenuItemIngredients (menu_item_id, ingredient_id, quantity_required) VALUES (1, 1, 0.1)",
    "INSERT INTO MenuItemIngredients (menu_item_id, ingredient_id, quantity_required) VALUES (1, 2, 0.05)",
    "INSERT INTO MenuItemIngredients (menu_item_id, ingredient_id, quantity_required) VALUES (2, 4, 0.25)",
    "INSERT INTO MenuItemIngredients (menu_item_id, ingredient_id, quantity_required) VALUES (3, 1, 0.15)",
    "INSERT INTO MenuItemIngredients (menu_item_id, ingredient_id, quantity_required) VALUES (3, 10, 0.15)",
    "INSERT INTO MenuItemIngredients (menu_item_id, ingredient_id, quantity_required) VALUES (4, 5, 0.3)",
    "INSERT INTO MenuItemIngredients (menu_item_id, ingredient_id, quantity_required) VALUES (4, 8, 0.2)",
    "INSERT INTO MenuItemIngredients (menu_item_id, ingredient_id, quantity_required) VALUES (4, 1, 0.15)",
    "INSERT INTO MenuItemIngredients (menu_item_id, ingredient_id, quantity_required) VALUES (5, 9, 0.2)",
    "INSERT INTO MenuItemIngredients (menu_item_id, ingredient_id, quantity_required) VALUES (6, 6, 0.25)",
    "INSERT INTO MenuItemIngredients (menu_item_id, ingredient_id, quantity_required) VALUES (7, 5, 0.2)",
    "INSERT INTO MenuItemIngredients (menu_item_id, ingredient_id, quantity_required) VALUES (7, 8, 0.15)"
];

foreach ($menuItemIngredients as $sql) {
    insertData($conn, $sql, "Menü öğesi malzemesi");
}

// ShiftTypes - Vardiya Türleri
$shiftTypes = [
    "INSERT INTO ShiftTypes (shift_name, start_time, end_time, description) 
     VALUES ('Sabah', '08:00:00', '16:00:00', 'Sabah vardiyası')",
    "INSERT INTO ShiftTypes (shift_name, start_time, end_time, description) 
     VALUES ('Akşam', '16:00:00', '00:00:00', 'Akşam vardiyası')",
    "INSERT INTO ShiftTypes (shift_name, start_time, end_time, description) 
     VALUES ('Gece', '00:00:00', '08:00:00', 'Gece vardiyası')"
];

foreach ($shiftTypes as $sql) {
    insertData($conn, $sql, "Vardiya türü");
}

// EmployeeShifts - Çalışan Vardiyaları (Bugün için)
$today = date('Y-m-d');
$employeeShifts = [
    "INSERT INTO EmployeeShifts (employee_id, shift_type_id, shift_date) VALUES (1, 1, '$today')",
    "INSERT INTO EmployeeShifts (employee_id, shift_type_id, shift_date) VALUES (2, 1, '$today')",
    "INSERT INTO EmployeeShifts (employee_id, shift_type_id, shift_date) VALUES (3, 2, '$today')",
    "INSERT INTO EmployeeShifts (employee_id, shift_type_id, shift_date) VALUES (4, 2, '$today')",
    "INSERT INTO EmployeeShifts (employee_id, shift_type_id, shift_date) VALUES (5, 3, '$today')"
];

foreach ($employeeShifts as $sql) {
    insertData($conn, $sql, "Çalışan vardiyası");
}

// Reservations - Rezervasyonlar
$tomorrow = date('Y-m-d', strtotime('+1 day'));
$reservations = [
    "INSERT INTO Reservations (customer_id, table_id, reservation_datetime, reservation_status) 
     VALUES (1, 3, '$tomorrow 19:00:00', 'reserved')",
    "INSERT INTO Reservations (customer_id, table_id, reservation_datetime, reservation_status) 
     VALUES (2, 4, '$tomorrow 20:00:00', 'reserved')"
];

foreach ($reservations as $sql) {
    insertData($conn, $sql, "Rezervasyon");
}

// Rezervasyon yapıldı, masaların durumunu güncelle
$update_tables = [
    "UPDATE `Tables` SET table_status = 'reserved' WHERE table_id = 3",
    "UPDATE `Tables` SET table_status = 'reserved' WHERE table_id = 4"
];

foreach ($update_tables as $sql) {
    insertData($conn, $sql, "Masa durumu");
}

// Orders - Siparişler
$orders = [
    "INSERT INTO Orders (customer_id, employee_id, table_id, order_date, total_amount, order_status) 
     VALUES (3, 3, 1, NOW(), 185.00, 'served')",
    "INSERT INTO Orders (customer_id, employee_id, table_id, order_date, total_amount, order_status) 
     VALUES (4, 3, 2, NOW(), 120.00, 'ordered')"
];

foreach ($orders as $sql) {
    insertData($conn, $sql, "Sipariş");
}

// OrderDetails - Sipariş Detayları
$orderDetails = [
    "INSERT INTO OrderDetails (order_id, menu_item_id, quantity, price, special_instructions) 
     VALUES (1, 1, 2, 75.00, 'Az baharatlı')",
    "INSERT INTO OrderDetails (order_id, menu_item_id, quantity, price, special_instructions) 
     VALUES (1, 3, 1, 35.00, NULL)",
    "INSERT INTO OrderDetails (order_id, menu_item_id, quantity, price, special_instructions) 
     VALUES (2, 2, 1, 65.00, NULL)",
    "INSERT INTO OrderDetails (order_id, menu_item_id, quantity, price, special_instructions) 
     VALUES (2, 5, 1, 25.00, NULL)",
    "INSERT INTO OrderDetails (order_id, menu_item_id, quantity, price, special_instructions) 
     VALUES (2, 8, 2, 20.00, 'Soğuk servis')"
];

foreach ($orderDetails as $sql) {
    insertData($conn, $sql, "Sipariş detayı");
}

// Payments - Ödemeler (Sadece tamamlanmış siparişler için)
$payments = [
    "INSERT INTO Payments (order_id, payment_method, total_amount, tip_amount, payment_date) 
     VALUES (1, 'credit', 185.00, 15.00, NOW())"
];

foreach ($payments as $sql) {
    insertData($conn, $sql, "Ödeme");
}

echo "<br>Örnek veriler başarıyla eklendi!";

// Bağlantıyı kapat
mysqli_close($conn);
?> 