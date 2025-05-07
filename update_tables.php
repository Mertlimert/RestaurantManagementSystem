<?php
// Veritabanı bağlantısını dahil et
require_once 'config/database.php';

// Tables tablosuna customer_id sütunu ekle
$sql = "ALTER TABLE `Tables` ADD COLUMN customer_id INT DEFAULT NULL";
if (mysqli_query($conn, $sql)) {
    echo "customer_id sütunu başarıyla eklendi.<br>";
    
    // Foreign key ekle
    $sql_fk = "ALTER TABLE `Tables` ADD FOREIGN KEY (customer_id) REFERENCES Customers(customer_id) ON DELETE SET NULL";
    if (mysqli_query($conn, $sql_fk)) {
        echo "Foreign key başarıyla eklendi.<br>";
    } else {
        echo "Foreign key eklenirken hata oluştu: " . mysqli_error($conn) . "<br>";
    }
} else {
    // Hata durumunda
    if (strpos(mysqli_error($conn), "Duplicate column name") !== false) {
        echo "customer_id sütunu zaten mevcut.<br>";
    } else {
        echo "Sütun eklenirken hata oluştu: " . mysqli_error($conn) . "<br>";
    }
}

// Tabloyu kontrol et
$result = mysqli_query($conn, "DESCRIBE `Tables`");
if ($result) {
    echo "<h3>Tables tablosu yapısı:</h3>";
    echo "<table border='1'><tr><th>Alan</th><th>Tip</th><th>Null</th><th>Anahtar</th><th>Varsayılan</th><th>Ekstra</th></tr>";
    while ($row = mysqli_fetch_array($result)) {
        echo "<tr>";
        echo "<td>" . $row["Field"] . "</td>";
        echo "<td>" . $row["Type"] . "</td>";
        echo "<td>" . $row["Null"] . "</td>";
        echo "<td>" . $row["Key"] . "</td>";
        echo "<td>" . $row["Default"] . "</td>";
        echo "<td>" . $row["Extra"] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Foreign key'leri kontrol et
$result_fk = mysqli_query($conn, "SELECT * FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_NAME = 'Tables' AND REFERENCED_TABLE_NAME IS NOT NULL");
if ($result_fk) {
    echo "<h3>Foreign Key İlişkileri:</h3>";
    echo "<table border='1'><tr><th>Sütun</th><th>Referans Tablo</th><th>Referans Sütun</th></tr>";
    while ($row = mysqli_fetch_array($result_fk)) {
        echo "<tr>";
        echo "<td>" . $row["COLUMN_NAME"] . "</td>";
        echo "<td>" . $row["REFERENCED_TABLE_NAME"] . "</td>";
        echo "<td>" . $row["REFERENCED_COLUMN_NAME"] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Bağlantıyı kapat
mysqli_close($conn);

echo "<p>İşlem tamamlandı. Artık masalara müşteri ataması yapabilirsiniz.</p>";
echo "<p><a href='admin/tables.php'>Masa Yönetimi sayfasına git</a></p>";
?> 