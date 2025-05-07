<?php
class Reservation {
    private $conn;
    
    // Yapıcı
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // Tüm rezervasyonları getir
    public function getAllReservations() {
        $query = "SELECT r.*, c.first_name, c.last_name, c.phone_number
                  FROM Reservations r
                  LEFT JOIN Customers c ON r.customer_id = c.customer_id
                  ORDER BY r.reservation_datetime DESC";
        $result = mysqli_query($this->conn, $query);
        return $result;
    }
    
    // Rezervasyon ekle
    public function addReservation($customer_id, $table_id, $reservation_datetime, $duration, $guest_count, $status, $notes = null) {
        $query = "INSERT INTO Reservations (customer_id, table_id, reservation_datetime, reservation_status) 
                 VALUES (?, ?, ?, ?)";
        $stmt = mysqli_prepare($this->conn, $query);
        
        // status için 'reserved' kullan
        $reservation_status = 'reserved';
        
        mysqli_stmt_bind_param($stmt, "iiss", $customer_id, $table_id, $reservation_datetime, $reservation_status);
        
        if(mysqli_stmt_execute($stmt)) {
            return mysqli_insert_id($this->conn);
        }
        
        return false;
    }
    
    // Rezervasyon güncelle
    public function updateReservation($reservation_id, $customer_id, $table_id, $reservation_datetime, $duration = null, $guest_count = null, $status = null, $notes = null) {
        // Veritabanı şemasında sadece zorunlu alanlar var
        $query = "UPDATE Reservations 
                 SET customer_id = ?, table_id = ?, reservation_datetime = ?, reservation_status = ? 
                 WHERE reservation_id = ?";
        
        // status için 'reserved' kullan veya gelen değeri al
        $reservation_status = $status ?: 'reserved';
        
        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, "iissi", $customer_id, $table_id, $reservation_datetime, $reservation_status, $reservation_id);
        
        return mysqli_stmt_execute($stmt);
    }
    
    // Rezervasyon durumunu güncelle
    public function updateReservationStatus($reservation_id, $status) {
        $query = "UPDATE Reservations SET reservation_status = ? WHERE reservation_id = ?";
        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, "si", $status, $reservation_id);
        
        return mysqli_stmt_execute($stmt);
    }
    
    // Rezervasyon sil
    public function deleteReservation($reservation_id) {
        $query = "DELETE FROM Reservations WHERE reservation_id = ?";
        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $reservation_id);
        
        return mysqli_stmt_execute($stmt);
    }
    
    // Rezervasyon getir (ID'ye göre)
    public function getReservationById($reservation_id) {
        $query = "SELECT r.*, c.first_name, c.last_name, c.phone_number, c.email
                  FROM Reservations r
                  LEFT JOIN Customers c ON r.customer_id = c.customer_id
                  WHERE r.reservation_id = ?";
        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $reservation_id);
        mysqli_stmt_execute($stmt);
        
        $result = mysqli_stmt_get_result($stmt);
        $reservation = mysqli_fetch_assoc($result);
        
        // Eksik alanları varsayılan değerlerle doldur
        if ($reservation) {
            $reservation['guest_count'] = isset($reservation['guest_count']) ? $reservation['guest_count'] : 2;
            $reservation['duration'] = isset($reservation['duration']) ? $reservation['duration'] : 120;
            $reservation['status'] = isset($reservation['reservation_status']) ? $reservation['reservation_status'] : 'reserved';
            $reservation['notes'] = isset($reservation['notes']) ? $reservation['notes'] : '';
        }
        
        return $reservation;
    }
    
    // Müşterinin rezervasyonlarını getir
    public function getReservationsByCustomer($customer_id) {
        $query = "SELECT r.*, t.capacity 
                  FROM Reservations r
                  LEFT JOIN Tables t ON r.table_id = t.table_id
                  WHERE r.customer_id = ? 
                  ORDER BY r.reservation_datetime DESC";
        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $customer_id);
        mysqli_stmt_execute($stmt);
        
        $result = mysqli_stmt_get_result($stmt);
        return $result;
    }
    
    // Belirli bir tarihteki rezervasyonları getir
    public function getReservationsByDate($date) {
        $query = "SELECT r.*, c.first_name, c.last_name, c.phone_number, t.capacity
                  FROM Reservations r
                  LEFT JOIN Customers c ON r.customer_id = c.customer_id
                  LEFT JOIN Tables t ON r.table_id = t.table_id
                  WHERE DATE(r.reservation_datetime) = ? 
                  ORDER BY r.reservation_datetime ASC";
        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, "s", $date);
        mysqli_stmt_execute($stmt);
        
        $result = mysqli_stmt_get_result($stmt);
        return $result;
    }
    
    // Belirli bir masanın rezervasyonlarını getir
    public function getReservationsByTable($table_id) {
        $query = "SELECT r.*, c.first_name, c.last_name, c.phone_number
                  FROM Reservations r
                  LEFT JOIN Customers c ON r.customer_id = c.customer_id
                  WHERE r.table_id = ? 
                  ORDER BY r.reservation_datetime ASC";
        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $table_id);
        mysqli_stmt_execute($stmt);
        
        $result = mysqli_stmt_get_result($stmt);
        return $result;
    }
    
    // Çakışan rezervasyonları kontrol et
    public function checkConflictingReservations($table_id, $reservation_datetime, $duration, $exclude_id = null) {
        // Rezervasyon bitiş zamanını hesapla (süreyi dakika cinsinden ekle)
        $end_time = date('Y-m-d H:i:s', strtotime($reservation_datetime) + ($duration * 60));
        
        $query = "SELECT * FROM Reservations 
                  WHERE table_id = ? 
                  AND reservation_status = 'reserved'
                  AND (
                      (reservation_datetime <= ? AND DATE_ADD(reservation_datetime, INTERVAL ? MINUTE) > ?)
                      OR
                      (reservation_datetime < ? AND DATE_ADD(reservation_datetime, INTERVAL ? MINUTE) >= ?)
                  )";
        
        $params = [$table_id, $end_time, $duration, $reservation_datetime, $end_time, $duration, $reservation_datetime];
        $types = "issiisi";
        
        // Eğer bir rezervasyon ID'si dışlanıyorsa (güncelleme durumunda)
        if ($exclude_id) {
            $query .= " AND reservation_id != ?";
            $params[] = $exclude_id;
            $types .= "i";
        }
        
        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        
        $result = mysqli_stmt_get_result($stmt);
        return mysqli_num_rows($result) > 0;
    }
    
    // Bugünkü rezervasyonları getir
    public function getTodayReservations() {
        $today = date('Y-m-d');
        return $this->getReservationsByDate($today);
    }
    
    // Yaklaşan rezervasyonları getir (n gün içinde)
    public function getUpcomingReservations($days = 7) {
        $query = "SELECT r.*, c.first_name, c.last_name, c.phone_number, t.capacity
                  FROM Reservations r
                  LEFT JOIN Customers c ON r.customer_id = c.customer_id
                  LEFT JOIN Tables t ON r.table_id = t.table_id
                  WHERE r.reservation_datetime BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL ? DAY)
                  AND r.reservation_status = 'reserved'
                  ORDER BY r.reservation_datetime ASC";
        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $days);
        mysqli_stmt_execute($stmt);
        
        $result = mysqli_stmt_get_result($stmt);
        return $result;
    }
}
?> 