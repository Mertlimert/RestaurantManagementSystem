<?php
class Customer {
    private $conn;
    
    // Yapıcı
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // Tüm müşterileri getir
    public function getAllCustomers() {
        $query = "SELECT * FROM Customers";
        $result = mysqli_query($this->conn, $query);
        return $result;
    }
    
    // Müşteri ekle
    public function addCustomer($first_name, $last_name, $phone_number, $email, $address) {
        $query = "INSERT INTO Customers (first_name, last_name, phone_number, email, address) VALUES (?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, "sssss", $first_name, $last_name, $phone_number, $email, $address);
        
        if(mysqli_stmt_execute($stmt)) {
            return mysqli_insert_id($this->conn);
        }
        
        return false;
    }
    
    // Müşteri güncelle
    public function updateCustomer($customer_id, $first_name, $last_name, $phone_number, $email, $address) {
        $query = "UPDATE Customers SET first_name = ?, last_name = ?, phone_number = ?, email = ?, address = ? WHERE customer_id = ?";
        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, "sssssi", $first_name, $last_name, $phone_number, $email, $address, $customer_id);
        
        return mysqli_stmt_execute($stmt);
    }
    
    // Müşteri sil
    public function deleteCustomer($customer_id) {
        $query = "DELETE FROM Customers WHERE customer_id = ?";
        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $customer_id);
        
        return mysqli_stmt_execute($stmt);
    }
    
    // Müşteri güvenli sil (ilişkili kayıtları kontrol ederek)
    public function deleteCustomerSafely($customer_id) {
        // Veritabanı işlemini başlat
        mysqli_begin_transaction($this->conn);
        
        try {
            // İlgili siparişleri kontrol et
            $order_query = "SELECT order_id FROM Orders WHERE customer_id = ?";
            $order_stmt = mysqli_prepare($this->conn, $order_query);
            mysqli_stmt_bind_param($order_stmt, "i", $customer_id);
            mysqli_stmt_execute($order_stmt);
            $orders_result = mysqli_stmt_get_result($order_stmt);
            
            // Siparişleri NULL olarak güncelle veya sil
            if (mysqli_num_rows($orders_result) > 0) {
                $update_orders = "UPDATE Orders SET customer_id = NULL WHERE customer_id = ?";
                $update_stmt = mysqli_prepare($this->conn, $update_orders);
                mysqli_stmt_bind_param($update_stmt, "i", $customer_id);
                mysqli_stmt_execute($update_stmt);
            }
            
            // İlgili rezervasyonları kontrol et
            $reservation_query = "SELECT reservation_id FROM Reservations WHERE customer_id = ?";
            $reservation_stmt = mysqli_prepare($this->conn, $reservation_query);
            mysqli_stmt_bind_param($reservation_stmt, "i", $customer_id);
            mysqli_stmt_execute($reservation_stmt);
            $reservations_result = mysqli_stmt_get_result($reservation_stmt);
            
            // Rezervasyonları NULL olarak güncelle veya sil
            if (mysqli_num_rows($reservations_result) > 0) {
                $update_reservations = "UPDATE Reservations SET customer_id = NULL WHERE customer_id = ?";
                $update_stmt = mysqli_prepare($this->conn, $update_reservations);
                mysqli_stmt_bind_param($update_stmt, "i", $customer_id);
                mysqli_stmt_execute($update_stmt);
            }
            
            // Şimdi müşteriyi sil
            $delete_query = "DELETE FROM Customers WHERE customer_id = ?";
            $delete_stmt = mysqli_prepare($this->conn, $delete_query);
            mysqli_stmt_bind_param($delete_stmt, "i", $customer_id);
            $result = mysqli_stmt_execute($delete_stmt);
            
            // İşlem başarılıysa commit yap
            if ($result) {
                mysqli_commit($this->conn);
                return true;
            } else {
                // Bir hata oluştuysa geri al
                mysqli_rollback($this->conn);
                return false;
            }
        } catch (Exception $e) {
            // Hata durumunda geri al
            mysqli_rollback($this->conn);
            return false;
        }
    }
    
    // Müşteri getir (ID'ye göre)
    public function getCustomerById($customer_id) {
        $query = "SELECT * FROM Customers WHERE customer_id = ?";
        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $customer_id);
        mysqli_stmt_execute($stmt);
        
        $result = mysqli_stmt_get_result($stmt);
        return mysqli_fetch_assoc($result);
    }
    
    // Müşteri ara (isme göre)
    public function searchCustomersByName($search_term) {
        $search_term = "%$search_term%";
        $query = "SELECT * FROM Customers WHERE first_name LIKE ? OR last_name LIKE ?";
        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, "ss", $search_term, $search_term);
        mysqli_stmt_execute($stmt);
        
        $result = mysqli_stmt_get_result($stmt);
        return $result;
    }
    
    // Müşteri ara (telefon numarasına göre)
    public function searchCustomersByPhone($phone_number) {
        $phone_number = "%$phone_number%";
        $query = "SELECT * FROM Customers WHERE phone_number LIKE ?";
        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, "s", $phone_number);
        mysqli_stmt_execute($stmt);
        
        $result = mysqli_stmt_get_result($stmt);
        return $result;
    }
    
    // Müşteri ara (e-posta adresine göre)
    public function searchCustomersByEmail($email) {
        $query = "SELECT * FROM Customers WHERE email = ?";
        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        
        $result = mysqli_stmt_get_result($stmt);
        return $result;
    }
}
?> 