<?php
class Order {
    private $conn;
    
    // Yapıcı
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // Tüm siparişleri getir
    public function getAllOrders() {
        $query = "SELECT o.*, c.first_name, c.last_name, e.first_name as employee_first_name, e.last_name as employee_last_name
                  FROM Orders o
                  LEFT JOIN Customers c ON o.customer_id = c.customer_id
                  LEFT JOIN Employees e ON o.employee_id = e.employee_id
                  ORDER BY o.order_date DESC";
        $result = mysqli_query($this->conn, $query);
        return $result;
    }
    
    // Sipariş ekle
    public function addOrder($customer_id, $employee_id, $table_id) {
        $query = "INSERT INTO Orders (customer_id, employee_id, table_id) VALUES (?, ?, ?)";
        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, "iii", $customer_id, $employee_id, $table_id);
        
        if(mysqli_stmt_execute($stmt)) {
            return mysqli_insert_id($this->conn);
        }
        
        return false;
    }
    
    // Sipariş detayı ekle
    public function addOrderDetail($order_id, $menu_item_id, $quantity, $price, $special_instructions = null) {
        $query = "INSERT INTO OrderDetails (order_id, menu_item_id, quantity, price, special_instructions) VALUES (?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, "iiids", $order_id, $menu_item_id, $quantity, $price, $special_instructions);
        
        return mysqli_stmt_execute($stmt);
    }
    
    // Sipariş toplamını güncelle
    public function updateOrderTotal($order_id) {
        $query = "UPDATE Orders o 
                  SET o.total_amount = (
                      SELECT SUM(od.quantity * od.price) 
                      FROM OrderDetails od 
                      WHERE od.order_id = ?
                  )
                  WHERE o.order_id = ?";
        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, "ii", $order_id, $order_id);
        
        return mysqli_stmt_execute($stmt);
    }
    
    // Sipariş durumunu güncelle
    public function updateOrderStatus($order_id, $order_status) {
        $query = "UPDATE Orders SET order_status = ? WHERE order_id = ?";
        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, "si", $order_status, $order_id);
        
        $result = mysqli_stmt_execute($stmt);
        
        // Eğer sipariş "paid" (ödenmiş) durumuna geçtiyse, masayı müsait durumuna getir
        if ($result && $order_status == "paid") {
            // Siparişe bağlı masayı bul
            $query = "SELECT table_id FROM Orders WHERE order_id = ?";
            $stmt = mysqli_prepare($this->conn, $query);
            mysqli_stmt_bind_param($stmt, "i", $order_id);
            mysqli_stmt_execute($stmt);
            $order_result = mysqli_stmt_get_result($stmt);
            
            if ($order_data = mysqli_fetch_assoc($order_result)) {
                $table_id = $order_data['table_id'];
                
                // Tables sınıfını kullanarak masayı müsait duruma getir
                require_once 'Tables.php';
                $tableModel = new Tables($this->conn);
                $tableModel->removeCustomerFromTable($table_id);
            }
        }
        
        return $result;
    }
    
    // Sipariş sil
    public function deleteOrder($order_id) {
        // Önce sipariş detaylarını silmemiz gerekiyor (CASCADE yapısı olmasına rağmen açıkça yapalım)
        $query = "DELETE FROM OrderDetails WHERE order_id = ?";
        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $order_id);
        mysqli_stmt_execute($stmt);
        
        // Sonra ana siparişi sil
        $query = "DELETE FROM Orders WHERE order_id = ?";
        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $order_id);
        
        return mysqli_stmt_execute($stmt);
    }
    
    // Delete order details by order id and menu item id
    public function deleteOrderDetail($order_id, $menu_item_id) {
        $query = "DELETE FROM OrderDetails WHERE order_id = ? AND menu_item_id = ?";
        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, "ii", $order_id, $menu_item_id);
        
        return mysqli_stmt_execute($stmt);
    }
    
    // Sipariş detaylarını ID ile sil
    public function deleteOrderDetailById($detail_id) {
        $query = "DELETE FROM OrderDetails WHERE order_detail_id = ?";
        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $detail_id);
        
        return mysqli_stmt_execute($stmt);
    }
    
    // Sipariş getir (ID'ye göre)
    public function getOrderById($order_id) {
        $query = "SELECT o.*, c.first_name, c.last_name, e.first_name as employee_first_name, e.last_name as employee_last_name
                  FROM Orders o
                  LEFT JOIN Customers c ON o.customer_id = c.customer_id
                  LEFT JOIN Employees e ON o.employee_id = e.employee_id
                  WHERE o.order_id = ?";
        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $order_id);
        mysqli_stmt_execute($stmt);
        
        $result = mysqli_stmt_get_result($stmt);
        return mysqli_fetch_assoc($result);
    }
    
    // Sipariş detaylarını getir
    public function getOrderDetails($order_id) {
        $query = "SELECT od.*, m.name, m.category, m.description
                  FROM OrderDetails od
                  JOIN MenuItems m ON od.menu_item_id = m.menu_item_id
                  WHERE od.order_id = ?";
        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $order_id);
        mysqli_stmt_execute($stmt);
        
        $result = mysqli_stmt_get_result($stmt);
        return $result;
    }
    
    // Müşterinin siparişlerini getir
    public function getOrdersByCustomer($customer_id) {
        $query = "SELECT o.*, c.first_name, c.last_name, e.first_name as employee_first_name, e.last_name as employee_last_name
                  FROM Orders o
                  LEFT JOIN Customers c ON o.customer_id = c.customer_id
                  LEFT JOIN Employees e ON o.employee_id = e.employee_id
                  WHERE o.customer_id = ? ORDER BY o.order_date DESC";
        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $customer_id);
        mysqli_stmt_execute($stmt);
        
        $result = mysqli_stmt_get_result($stmt);
        return $result;
    }
    
    // Çalışanın siparişlerini getir
    public function getOrdersByEmployee($employee_id) {
        $query = "SELECT o.*, c.first_name, c.last_name, e.first_name as employee_first_name, e.last_name as employee_last_name
                  FROM Orders o
                  LEFT JOIN Customers c ON o.customer_id = c.customer_id
                  LEFT JOIN Employees e ON o.employee_id = e.employee_id
                  WHERE o.employee_id = ? ORDER BY o.order_date DESC";
        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $employee_id);
        mysqli_stmt_execute($stmt);
        
        $result = mysqli_stmt_get_result($stmt);
        return $result;
    }
    
    // Çalışanın belirli tarih aralığındaki siparişlerini getir
    public function getOrdersByEmployeeAndDateRange($employee_id, $from_date = null, $to_date = null) {
        $conditions = ["o.employee_id = ?"];
        $params = [$employee_id];
        $types = "i";
        
        if($from_date) {
            $conditions[] = "DATE(o.order_date) >= ?";
            $params[] = $from_date;
            $types .= "s";
        }
        
        if($to_date) {
            $conditions[] = "DATE(o.order_date) <= ?";
            $params[] = $to_date;
            $types .= "s";
        }
        
        $whereClause = implode(" AND ", $conditions);
        
        $query = "SELECT o.*, c.first_name, c.last_name, e.first_name as employee_first_name, e.last_name as employee_last_name
                  FROM Orders o
                  LEFT JOIN Customers c ON o.customer_id = c.customer_id
                  LEFT JOIN Employees e ON o.employee_id = e.employee_id
                  WHERE $whereClause ORDER BY o.order_date DESC";
        
        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        
        $result = mysqli_stmt_get_result($stmt);
        return $result;
    }
    
    // Müşterinin belirli tarih aralığındaki siparişlerini getir
    public function getOrdersByCustomerAndDateRange($customer_id, $from_date = null, $to_date = null) {
        $conditions = ["o.customer_id = ?"];
        $params = [$customer_id];
        $types = "i";
        
        if($from_date) {
            $conditions[] = "DATE(o.order_date) >= ?";
            $params[] = $from_date;
            $types .= "s";
        }
        
        if($to_date) {
            $conditions[] = "DATE(o.order_date) <= ?";
            $params[] = $to_date;
            $types .= "s";
        }
        
        $whereClause = implode(" AND ", $conditions);
        
        $query = "SELECT o.*, c.first_name, c.last_name, e.first_name as employee_first_name, e.last_name as employee_last_name
                  FROM Orders o
                  LEFT JOIN Customers c ON o.customer_id = c.customer_id
                  LEFT JOIN Employees e ON o.employee_id = e.employee_id
                  WHERE $whereClause ORDER BY o.order_date DESC";
        
        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        
        $result = mysqli_stmt_get_result($stmt);
        return $result;
    }
    
    // Belirli bir tarihteki siparişleri getir
    public function getOrdersByDate($date) {
        $query = "SELECT o.*, c.first_name, c.last_name 
                  FROM Orders o
                  LEFT JOIN Customers c ON o.customer_id = c.customer_id
                  WHERE DATE(o.order_date) = ? 
                  ORDER BY o.order_date DESC";
        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, "s", $date);
        mysqli_stmt_execute($stmt);
        
        $result = mysqli_stmt_get_result($stmt);
        return $result;
    }
    
    // Belirli bir masanın siparişlerini getir
    public function getOrdersByTable($table_id) {
        $query = "SELECT o.*, c.first_name, c.last_name, e.first_name as employee_first_name, e.last_name as employee_last_name
                  FROM Orders o
                  LEFT JOIN Customers c ON o.customer_id = c.customer_id
                  LEFT JOIN Employees e ON o.employee_id = e.employee_id
                  WHERE o.table_id = ? 
                  ORDER BY o.order_date DESC";
        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $table_id);
        mysqli_stmt_execute($stmt);
        
        $result = mysqli_stmt_get_result($stmt);
        return $result;
    }
    
    // Belirli bir masanın aktif siparişlerini getir
    public function getActiveOrdersByTable($table_id) {
        $query = "SELECT o.*, c.first_name, c.last_name, e.first_name as employee_first_name, e.last_name as employee_last_name
                  FROM Orders o
                  LEFT JOIN Customers c ON o.customer_id = c.customer_id
                  LEFT JOIN Employees e ON o.employee_id = e.employee_id
                  WHERE o.table_id = ? AND o.order_status NOT IN ('completed', 'cancelled', 'paid')
                  ORDER BY o.order_date DESC";
        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $table_id);
        mysqli_stmt_execute($stmt);
        
        $result = mysqli_stmt_get_result($stmt);
        return $result;
    }
    
    // Sipariş için ödeme ekle
    public function addPayment($order_id, $payment_method, $total_amount, $tip_amount) {
        $query = "INSERT INTO Payments (order_id, payment_method, total_amount, tip_amount) 
                  VALUES (?, ?, ?, ?)";
        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, "isdd", $order_id, $payment_method, $total_amount, $tip_amount);
        
        if(mysqli_stmt_execute($stmt)) {
            // Sipariş durumunu "paid" olarak güncelle
            $this->updateOrderStatus($order_id, 'paid');
            
            // Siparişe bağlı masayı bul
            $query = "SELECT table_id FROM Orders WHERE order_id = ?";
            $stmt = mysqli_prepare($this->conn, $query);
            mysqli_stmt_bind_param($stmt, "i", $order_id);
            mysqli_stmt_execute($stmt);
            $order_result = mysqli_stmt_get_result($stmt);
            
            if ($order_data = mysqli_fetch_assoc($order_result)) {
                $table_id = $order_data['table_id'];
                
                // Tables sınıfını kullanarak masayı müsait duruma getir
                require_once 'Tables.php';
                $tableModel = new Tables($this->conn);
                $tableModel->removeCustomerFromTable($table_id);
            }
            
            return mysqli_insert_id($this->conn);
        }
        
        return false;
    }
    
    // Sipariş ödemesini getir
    public function getPaymentByOrderId($order_id) {
        $query = "SELECT * FROM Payments WHERE order_id = ?";
        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $order_id);
        mysqli_stmt_execute($stmt);
        
        $result = mysqli_stmt_get_result($stmt);
        return mysqli_fetch_assoc($result);
    }
}
?> 