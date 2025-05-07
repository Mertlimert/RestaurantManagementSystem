<?php
class MenuItem {
    private $conn;
    
    // Yapıcı
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // Tüm menü öğelerini getir
    public function getAllMenuItems() {
        $query = "SELECT * FROM MenuItems";
        $result = mysqli_query($this->conn, $query);
        return $result;
    }
    
    // Menü öğesi ekle
    public function addMenuItem($name, $category, $price, $description) {
        $query = "INSERT INTO MenuItems (name, category, price, description) VALUES (?, ?, ?, ?)";
        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, "ssds", $name, $category, $price, $description);
        
        if(mysqli_stmt_execute($stmt)) {
            return mysqli_insert_id($this->conn);
        }
        
        return false;
    }
    
    // Menü öğesi güncelle
    public function updateMenuItem($menu_item_id, $name, $category, $price, $description) {
        $query = "UPDATE MenuItems SET name = ?, category = ?, price = ?, description = ? WHERE menu_item_id = ?";
        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, "ssdsi", $name, $category, $price, $description, $menu_item_id);
        
        return mysqli_stmt_execute($stmt);
    }
    
    // Menü öğesi sil
    public function deleteMenuItem($menu_item_id) {
        $query = "DELETE FROM MenuItems WHERE menu_item_id = ?";
        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $menu_item_id);
        
        return mysqli_stmt_execute($stmt);
    }
    
    // Menü öğesi getir (ID'ye göre)
    public function getMenuItemById($menu_item_id) {
        $query = "SELECT * FROM MenuItems WHERE menu_item_id = ?";
        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $menu_item_id);
        mysqli_stmt_execute($stmt);
        
        $result = mysqli_stmt_get_result($stmt);
        return mysqli_fetch_assoc($result);
    }
    
    // Menü öğeleri getir (kategoriye göre)
    public function getMenuItemsByCategory($category) {
        $query = "SELECT * FROM MenuItems WHERE category = ?";
        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, "s", $category);
        mysqli_stmt_execute($stmt);
        
        $result = mysqli_stmt_get_result($stmt);
        return $result;
    }
    
    // Menü öğesi ara (isme göre)
    public function searchMenuItemsByName($search_term) {
        $search_term = "%$search_term%";
        $query = "SELECT * FROM MenuItems WHERE name LIKE ?";
        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, "s", $search_term);
        mysqli_stmt_execute($stmt);
        
        $result = mysqli_stmt_get_result($stmt);
        return $result;
    }
    
    // Menü öğesi için gerekli malzemeleri getir
    public function getMenuItemIngredients($menu_item_id) {
        $query = "SELECT i.*, mi.quantity_required 
                  FROM Ingredients i
                  JOIN MenuItemIngredients mi ON i.ingredient_id = mi.ingredient_id
                  WHERE mi.menu_item_id = ?";
        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $menu_item_id);
        mysqli_stmt_execute($stmt);
        
        $result = mysqli_stmt_get_result($stmt);
        return $result;
    }
    
    // Menü öğesine malzeme ekle
    public function addIngredientToMenuItem($menu_item_id, $ingredient_id, $quantity_required) {
        $query = "INSERT INTO MenuItemIngredients (menu_item_id, ingredient_id, quantity_required) VALUES (?, ?, ?)";
        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, "iid", $menu_item_id, $ingredient_id, $quantity_required);
        
        return mysqli_stmt_execute($stmt);
    }
    
    // Menü öğesinden malzeme çıkar
    public function removeIngredientFromMenuItem($menu_item_id, $ingredient_id) {
        $query = "DELETE FROM MenuItemIngredients WHERE menu_item_id = ? AND ingredient_id = ?";
        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, "ii", $menu_item_id, $ingredient_id);
        
        return mysqli_stmt_execute($stmt);
    }
}
?> 