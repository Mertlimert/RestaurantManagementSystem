<?php
class Tables {
    private $conn;
    
    // Yapıcı
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // Tüm masaları getir
    public function getAllTables() {
        $query = "SELECT * FROM `Tables`";
        $result = mysqli_query($this->conn, $query);
        return $result;
    }
    
    // Masa ekle
    public function addTable($number_of_customers, $capacity) {
        $query = "INSERT INTO `Tables` (number_of_customers, capacity) VALUES (?, ?)";
        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, "ii", $number_of_customers, $capacity);
        
        if(mysqli_stmt_execute($stmt)) {
            return mysqli_insert_id($this->conn);
        }
        
        return false;
    }
    
    // Masa güncelle
    public function updateTable($table_id, $number_of_customers, $capacity, $table_status) {
        $query = "UPDATE `Tables` SET number_of_customers = ?, capacity = ?, table_status = ? WHERE table_id = ?";
        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, "iisi", $number_of_customers, $capacity, $table_status, $table_id);
        
        return mysqli_stmt_execute($stmt);
    }
    
    // Masa durumunu güncelle
    public function updateTableStatus($table_id, $table_status) {
        $query = "UPDATE `Tables` SET table_status = ? WHERE table_id = ?";
        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, "si", $table_status, $table_id);
        
        return mysqli_stmt_execute($stmt);
    }
    
    // Masa sil
    public function deleteTable($table_id) {
        $query = "DELETE FROM `Tables` WHERE table_id = ?";
        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $table_id);
        
        return mysqli_stmt_execute($stmt);
    }
    
    // Masa getir (ID'ye göre)
    public function getTableById($table_id) {
        $query = "SELECT * FROM `Tables` WHERE table_id = ?";
        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $table_id);
        mysqli_stmt_execute($stmt);
        
        $result = mysqli_stmt_get_result($stmt);
        return mysqli_fetch_assoc($result);
    }
    
    // Boş masaları getir
    public function getAvailableTables() {
        $query = "SELECT * FROM `Tables` WHERE table_status = 'available'";
        $result = mysqli_query($this->conn, $query);
        return $result;
    }
    
    // Masaya müşteri ata
    public function assignCustomerToTable($table_id, $customer_id, $number_of_customers = 1) {
        // Masayı "dolu" olarak işaretle
        $query = "UPDATE `Tables` SET table_status = 'occupied', number_of_customers = ? WHERE table_id = ?";
        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, "ii", $number_of_customers, $table_id);
        
        return mysqli_stmt_execute($stmt);
    }
    
    // Masadan müşteri çıkar
    public function removeCustomerFromTable($table_id) {
        // Masayı "müsait" olarak işaretle
        $query = "UPDATE `Tables` SET table_status = 'available', number_of_customers = 0 WHERE table_id = ?";
        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $table_id);
        
        return mysqli_stmt_execute($stmt);
    }
    
    // Müşteri detayları ile birlikte masaları getir
    public function getTablesWithCustomerDetails() {
        $query = "SELECT * FROM `Tables`";
        $result = mysqli_query($this->conn, $query);
        return $result;
    }
    
    // Belirli bir masanın müşteri detaylarını getir
    public function getTableWithCustomerDetails($table_id) {
        $query = "SELECT * FROM `Tables` WHERE table_id = ?";
        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $table_id);
        mysqli_stmt_execute($stmt);
        
        $result = mysqli_stmt_get_result($stmt);
        return mysqli_fetch_assoc($result);
    }
    
    // Dolu masalar için müşteri ve çalışan bilgilerini getir
    public function getOccupiedTablesWithDetails() {
        $query = "SELECT t.*, o.order_id, o.customer_id, o.employee_id, 
                        c.first_name as customer_first_name, c.last_name as customer_last_name, 
                        c.phone_number as customer_phone,
                        e.first_name as employee_first_name, e.last_name as employee_last_name
                 FROM `Tables` t
                 LEFT JOIN Orders o ON t.table_id = o.table_id AND o.order_status != 'paid'
                 LEFT JOIN Customers c ON o.customer_id = c.customer_id
                 LEFT JOIN Employees e ON o.employee_id = e.employee_id
                 WHERE t.table_status = 'occupied'
                 ORDER BY t.table_id ASC";
        
        $result = mysqli_query($this->conn, $query);
        return $result;
    }
}
?> 