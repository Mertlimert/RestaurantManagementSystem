<?php
// Güvenli giriş için temizleme fonksiyonu
function clean_input($data) {
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return mysqli_real_escape_string($conn, $data);
}

// Hata mesajı gösterme
function display_error($message) {
    echo '<div class="alert alert-danger" role="alert">' . $message . '</div>';
}

// Başarı mesajı gösterme
function display_success($message) {
    echo '<div class="alert alert-success" role="alert">' . $message . '</div>';
}

// Yönlendirme
function redirect($location) {
    header("Location: $location");
    exit;
}

// Oturum kontrolü
function is_logged_in() {
    return isset($_SESSION['employee_id']);
}

// Yönetici kontrolü
function is_admin() {
    return isset($_SESSION['position']) && $_SESSION['position'] == 'Yönetici';
}

// Menü öğelerini getir
function get_menu_items() {
    global $conn;
    $sql = "SELECT * FROM MENU_ITEM ORDER BY category, name";
    $result = mysqli_query($conn, $sql);
    return $result;
}

// Siparişleri getir
function get_orders($limit = 10) {
    global $conn;
    $sql = "SELECT o.*, c.name as customer_name, t.number as table_number, e.name as server_name 
            FROM `ORDER` o
            LEFT JOIN CUSTOMER c ON o.customer_id = c.customer_id
            LEFT JOIN TABLE t ON o.table_id = t.table_id
            LEFT JOIN EMPLOYEE e ON o.server_id = e.employee_id
            ORDER BY o.order_date DESC, o.order_time DESC 
            LIMIT $limit";
    $result = mysqli_query($conn, $sql);
    return $result;
}

// Masaları getir
function get_tables() {
    global $conn;
    $sql = "SELECT * FROM `TABLE` ORDER BY number";
    $result = mysqli_query($conn, $sql);
    return $result;
}

// Çalışanları getir
function get_employees() {
    global $conn;
    $sql = "SELECT e.*, p.title as position_title 
            FROM EMPLOYEE e
            JOIN EMPLOYEE_POSITION p ON e.position_id = p.position_id
            ORDER BY e.name";
    $result = mysqli_query($conn, $sql);
    return $result;
}
?> 