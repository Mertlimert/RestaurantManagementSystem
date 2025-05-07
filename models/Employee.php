<?php
class Employee {
    private $conn;
    
    // Yapıcı
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // Tüm çalışanları getir
    public function getAllEmployees() {
        $query = "SELECT e.*, p.title as position_title 
                  FROM Employees e
                  JOIN EmployeePositions p ON e.position_id = p.position_id
                  ORDER BY e.last_name, e.first_name";
        $result = mysqli_query($this->conn, $query);
        return $result;
    }
    
    // Çalışan ekle
    public function addEmployee($first_name, $last_name, $position_id, $phone_number, $email, $hourly_rate, $hire_date) {
        $query = "INSERT INTO Employees (first_name, last_name, position_id, phone_number, email, hourly_rate, hire_date) 
                  VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, "ssissds", $first_name, $last_name, $position_id, $phone_number, $email, $hourly_rate, $hire_date);
        
        if(mysqli_stmt_execute($stmt)) {
            return mysqli_insert_id($this->conn);
        }
        
        return false;
    }
    
    // Çalışan güncelle
    public function updateEmployee($employee_id, $first_name, $last_name, $position_id, $phone_number, $email, $hourly_rate, $hire_date) {
        $query = "UPDATE Employees 
                  SET first_name = ?, last_name = ?, position_id = ?, phone_number = ?, email = ?, hourly_rate = ?, hire_date = ? 
                  WHERE employee_id = ?";
        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, "ssissdsi", $first_name, $last_name, $position_id, $phone_number, $email, $hourly_rate, $hire_date, $employee_id);
        
        return mysqli_stmt_execute($stmt);
    }
    
    // Çalışan sil
    public function deleteEmployee($employee_id) {
        $query = "DELETE FROM Employees WHERE employee_id = ?";
        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $employee_id);
        
        return mysqli_stmt_execute($stmt);
    }
    
    // Çalışan getir (ID'ye göre)
    public function getEmployeeById($employee_id) {
        $query = "SELECT e.*, p.title as position_title 
                  FROM Employees e
                  JOIN EmployeePositions p ON e.position_id = p.position_id
                  WHERE e.employee_id = ?";
        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $employee_id);
        mysqli_stmt_execute($stmt);
        
        $result = mysqli_stmt_get_result($stmt);
        return mysqli_fetch_assoc($result);
    }
    
    // Çalışan ara (isime göre)
    public function searchEmployeesByName($search_term) {
        $search_term = "%$search_term%";
        $query = "SELECT e.*, p.title as position_title 
                  FROM Employees e
                  JOIN EmployeePositions p ON e.position_id = p.position_id
                  WHERE e.first_name LIKE ? OR e.last_name LIKE ?
                  ORDER BY e.last_name, e.first_name";
        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, "ss", $search_term, $search_term);
        mysqli_stmt_execute($stmt);
        
        $result = mysqli_stmt_get_result($stmt);
        return $result;
    }
    
    // Görev pozisyonlarını getir
    public function getAllPositions() {
        $query = "SELECT * FROM EmployeePositions ORDER BY title";
        $result = mysqli_query($this->conn, $query);
        return $result;
    }
    
    // Görev pozisyonu ekle
    public function addPosition($title) {
        $query = "INSERT INTO EmployeePositions (title) VALUES (?)";
        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, "s", $title);
        
        if(mysqli_stmt_execute($stmt)) {
            return mysqli_insert_id($this->conn);
        }
        
        return false;
    }
    
    // Görev pozisyonu güncelle
    public function updatePosition($position_id, $title) {
        $query = "UPDATE EmployeePositions SET title = ? WHERE position_id = ?";
        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, "si", $title, $position_id);
        
        return mysqli_stmt_execute($stmt);
    }
    
    // Görev pozisyonu sil
    public function deletePosition($position_id) {
        $query = "DELETE FROM EmployeePositions WHERE position_id = ?";
        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $position_id);
        
        return mysqli_stmt_execute($stmt);
    }
    
    // Vardiya türlerini getir
    public function getAllShiftTypes() {
        $query = "SELECT * FROM ShiftTypes";
        $result = mysqli_query($this->conn, $query);
        return $result;
    }
    
    // Vardiya türü ekle
    public function addShiftType($shift_name, $start_time, $end_time, $description = null) {
        $query = "INSERT INTO ShiftTypes (shift_name, start_time, end_time, description) VALUES (?, ?, ?, ?)";
        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, "ssss", $shift_name, $start_time, $end_time, $description);
        
        if(mysqli_stmt_execute($stmt)) {
            return mysqli_insert_id($this->conn);
        }
        
        return false;
    }
    
    // Çalışana vardiya ata
    public function assignShift($employee_id, $shift_type_id, $shift_date) {
        $query = "INSERT INTO EmployeeShifts (employee_id, shift_type_id, shift_date) VALUES (?, ?, ?)";
        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, "iis", $employee_id, $shift_type_id, $shift_date);
        
        return mysqli_stmt_execute($stmt);
    }
    
    // Çalışanın vardiyalarını getir
    public function getEmployeeShifts($employee_id, $start_date = null, $end_date = null) {
        $query = "SELECT es.*, st.shift_name, st.start_time, st.end_time 
                  FROM EmployeeShifts es
                  JOIN ShiftTypes st ON es.shift_type_id = st.shift_type_id
                  WHERE es.employee_id = ?";
                  
        if($start_date !== null && $end_date !== null) {
            $query .= " AND es.shift_date BETWEEN ? AND ?";
        }
        
        $query .= " ORDER BY es.shift_date";
        
        $stmt = mysqli_prepare($this->conn, $query);
        
        if($start_date !== null && $end_date !== null) {
            mysqli_stmt_bind_param($stmt, "iss", $employee_id, $start_date, $end_date);
        } else {
            mysqli_stmt_bind_param($stmt, "i", $employee_id);
        }
        
        mysqli_stmt_execute($stmt);
        
        $result = mysqli_stmt_get_result($stmt);
        return $result;
    }
    
    // Belirli bir tarihteki vardiyaları getir
    public function getShiftsByDate($date) {
        $query = "SELECT es.*, e.first_name, e.last_name, st.shift_name, st.start_time, st.end_time 
                  FROM EmployeeShifts es
                  JOIN Employees e ON es.employee_id = e.employee_id
                  JOIN ShiftTypes st ON es.shift_type_id = st.shift_type_id
                  WHERE es.shift_date = ?
                  ORDER BY st.start_time, e.last_name, e.first_name";
        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, "s", $date);
        mysqli_stmt_execute($stmt);
        
        $result = mysqli_stmt_get_result($stmt);
        return $result;
    }
}
?> 