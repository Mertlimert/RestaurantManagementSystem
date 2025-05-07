<?php
class Ingredient {
    private $conn;
    
    // Yapıcı
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // Tüm malzemeleri getir
    public function getAllIngredients() {
        $query = "SELECT * FROM Ingredients ORDER BY ingredient_name";
        $result = mysqli_query($this->conn, $query);
        return $result;
    }
    
    // Malzeme ekle
    public function addIngredient($ingredient_name, $unit, $stock_quantity, $allergen = null) {
        $query = "INSERT INTO Ingredients (ingredient_name, unit, stock_quantity, allergen) VALUES (?, ?, ?, ?)";
        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, "ssds", $ingredient_name, $unit, $stock_quantity, $allergen);
        
        if(mysqli_stmt_execute($stmt)) {
            return mysqli_insert_id($this->conn);
        }
        
        return false;
    }
    
    // Malzeme güncelle
    public function updateIngredient($ingredient_id, $ingredient_name, $unit, $stock_quantity, $allergen = null) {
        $query = "UPDATE Ingredients 
                  SET ingredient_name = ?, unit = ?, stock_quantity = ?, allergen = ? 
                  WHERE ingredient_id = ?";
        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, "ssdsi", $ingredient_name, $unit, $stock_quantity, $allergen, $ingredient_id);
        
        return mysqli_stmt_execute($stmt);
    }
    
    // Stok miktarını güncelle
    public function updateStockQuantity($ingredient_id, $quantity_change) {
        $query = "UPDATE Ingredients 
                  SET stock_quantity = stock_quantity + ? 
                  WHERE ingredient_id = ?";
        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, "di", $quantity_change, $ingredient_id);
        
        return mysqli_stmt_execute($stmt);
    }
    
    // Malzeme sil
    public function deleteIngredient($ingredient_id) {
        $query = "DELETE FROM Ingredients WHERE ingredient_id = ?";
        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $ingredient_id);
        
        return mysqli_stmt_execute($stmt);
    }
    
    // Malzeme getir (ID'ye göre)
    public function getIngredientById($ingredient_id) {
        $query = "SELECT * FROM Ingredients WHERE ingredient_id = ?";
        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $ingredient_id);
        mysqli_stmt_execute($stmt);
        
        $result = mysqli_stmt_get_result($stmt);
        return mysqli_fetch_assoc($result);
    }
    
    // Malzeme ara (isme göre)
    public function searchIngredientsByName($search_term) {
        $search_term = "%$search_term%";
        $query = "SELECT * FROM Ingredients WHERE ingredient_name LIKE ? ORDER BY ingredient_name";
        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, "s", $search_term);
        mysqli_stmt_execute($stmt);
        
        $result = mysqli_stmt_get_result($stmt);
        return $result;
    }
    
    // Stok miktarı az olan malzemeleri getir
    public function getLowStockIngredients($threshold = 10) {
        $query = "SELECT * FROM Ingredients WHERE stock_quantity < ? ORDER BY stock_quantity";
        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, "d", $threshold);
        mysqli_stmt_execute($stmt);
        
        $result = mysqli_stmt_get_result($stmt);
        return $result;
    }
    
    // Menü öğesinde kullanılan malzemeleri getir
    public function getIngredientsByMenuItem($menu_item_id) {
        $query = "SELECT i.*, mi.quantity_required 
                  FROM Ingredients i
                  JOIN MenuItemIngredients mi ON i.ingredient_id = mi.ingredient_id
                  WHERE mi.menu_item_id = ?
                  ORDER BY i.ingredient_name";
        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $menu_item_id);
        mysqli_stmt_execute($stmt);
        
        $result = mysqli_stmt_get_result($stmt);
        return $result;
    }
    
    // Alerjen içeren malzemeleri getir
    public function getAllergenIngredients() {
        $query = "SELECT * FROM Ingredients WHERE allergen IS NOT NULL AND allergen != '' ORDER BY allergen, ingredient_name";
        $result = mysqli_query($this->conn, $query);
        return $result;
    }
    
    // Belirli bir alerjene sahip malzemeleri getir
    public function getIngredientsByAllergen($allergen) {
        $allergen = "%$allergen%";
        $query = "SELECT * FROM Ingredients WHERE allergen LIKE ? ORDER BY ingredient_name";
        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, "s", $allergen);
        mysqli_stmt_execute($stmt);
        
        $result = mysqli_stmt_get_result($stmt);
        return $result;
    }
}
?> 