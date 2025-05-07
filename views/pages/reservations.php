<?php
// Sadece giriş yapan kullanıcılar erişebilir
if(!is_logged_in()) {
    redirect('index.php?page=login');
}

// Rezervasyon ekleme işlemi
if(isset($_POST['add_reservation'])) {
    $customer_id = !empty($_POST['customer_id']) ? clean_input($_POST['customer_id']) : null;
    $customer_name = clean_input($_POST['customer_name']);
    $customer_phone = clean_input($_POST['customer_phone']);
    $reservation_date = clean_input($_POST['reservation_date']);
    $reservation_time = clean_input($_POST['reservation_time']);
    $number_of_guests = clean_input($_POST['number_of_guests']);
    $table_id = clean_input($_POST['table_id']);
    
    // Basit validasyon
    if(empty($reservation_date) || empty($reservation_time) || empty($number_of_guests) || empty($table_id)) {
        $error = "Tarih, saat, misafir sayısı ve masa seçimi zorunludur.";
    } else {
        // Müşteri ID'si yoksa yeni müşteri ekle
        if(empty($customer_id) && !empty($customer_name) && !empty($customer_phone)) {
            $customer_id = uniqid('CUST_');
            $sql = "INSERT INTO CUSTOMER (customer_id, name, phone) VALUES (?, ?, ?)";
            
            if($stmt = mysqli_prepare($conn, $sql)) {
                mysqli_stmt_bind_param($stmt, "sss", $customer_id, $customer_name, $customer_phone);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
            }
        }
        
        // Rezervasyon ekle
        $reservation_id = uniqid('RES_');
        $sql = "INSERT INTO RESERVATION (reservation_id, customer_id, reservation_date, reservation_time, 
                number_of_guests, reservation_status, table_id) 
                VALUES (?, ?, ?, ?, ?, 'Onaylandı', ?)";
                
        if($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "ssssss", $reservation_id, $customer_id, $reservation_date, 
                                  $reservation_time, $number_of_guests, $table_id);
            
            if(mysqli_stmt_execute($stmt)) {
                // Masa durumunu güncelle
                $update_table = "UPDATE `TABLE` SET table_status = 'Rezerve' WHERE table_id = ?";
                if($table_stmt = mysqli_prepare($conn, $update_table)) {
                    mysqli_stmt_bind_param($table_stmt, "s", $table_id);
                    mysqli_stmt_execute($table_stmt);
                    mysqli_stmt_close($table_stmt);
                }
                
                $success = "Rezervasyon başarıyla oluşturuldu.";
            } else {
                $error = "Rezervasyon oluşturulurken bir hata oluştu.";
            }
            
            mysqli_stmt_close($stmt);
        }
    }
}

// Rezervasyonları getir
$reservations = array();
$sql = "SELECT r.*, c.name as customer_name, c.phone as customer_phone, t.number as table_number 
        FROM RESERVATION r
        LEFT JOIN CUSTOMER c ON r.customer_id = c.customer_id
        LEFT JOIN `TABLE` t ON r.table_id = t.table_id
        ORDER BY r.reservation_date DESC, r.reservation_time DESC";
$result = mysqli_query($conn, $sql);
if(mysqli_num_rows($result) > 0) {
    while($row = mysqli_fetch_assoc($result)) {
        $reservations[] = $row;
    }
}

// Müşterileri getir
$customers = array();
$sql = "SELECT customer_id, name, phone FROM CUSTOMER ORDER BY name";
$result = mysqli_query($conn, $sql);
if(mysqli_num_rows($result) > 0) {
    while($row = mysqli_fetch_assoc($result)) {
        $customers[$row['customer_id']] = array(
            'name' => $row['name'],
            'phone' => $row['phone']
        );
    }
}

// Masaları getir
$tables = array();
$sql = "SELECT table_id, number, capacity FROM `TABLE` WHERE table_status = 'Boş' ORDER BY number";
$result = mysqli_query($conn, $sql);
if(mysqli_num_rows($result) > 0) {
    while($row = mysqli_fetch_assoc($result)) {
        $tables[$row['table_id']] = array(
            'number' => $row['number'],
            'capacity' => $row['capacity']
        );
    }
}
?>

<h2 class="mb-4">Rezervasyon Yönetimi</h2>

<div class="row mb-4">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Rezervasyonlar</h5>
                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addReservationModal">
                    <i class="fas fa-plus me-1"></i> Yeni Rezervasyon
                </button>
            </div>
            <div class="card-body">
                <?php if(isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if(isset($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Müşteri</th>
                                <th>Tarih/Saat</th>
                                <th>Masa</th>
                                <th>Kişi Sayısı</th>
                                <th>Durum</th>
                                <th>İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(count($reservations) > 0): ?>
                                <?php foreach($reservations as $reservation): ?>
                                <tr>
                                    <td><?php echo $reservation['customer_name'] ? $reservation['customer_name'] : 'Belirtilmemiş'; ?></td>
                                    <td><?php echo date('d.m.Y H:i', strtotime($reservation['reservation_date'] . ' ' . $reservation['reservation_time'])); ?></td>
                                    <td>Masa <?php echo $reservation['table_number']; ?></td>
                                    <td><?php echo $reservation['number_of_guests']; ?> kişi</td>
                                    <td>
                                        <?php if($reservation['reservation_status'] == 'Onaylandı'): ?>
                                            <span class="badge bg-success">Onaylandı</span>
                                        <?php elseif($reservation['reservation_status'] == 'Bekliyor'): ?>
                                            <span class="badge bg-warning text-dark">Bekliyor</span>
                                        <?php elseif($reservation['reservation_status'] == 'İptal Edildi'): ?>
                                            <span class="badge bg-danger">İptal Edildi</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="index.php?page=view_reservation&id=<?php echo $reservation['reservation_id']; ?>" class="btn btn-sm btn-outline-info">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="index.php?page=edit_reservation&id=<?php echo $reservation['reservation_id']; ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center">Henüz rezervasyon bulunmuyor.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Yeni Rezervasyon Modal -->
<div class="modal fade" id="addReservationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Yeni Rezervasyon</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="index.php?page=reservations" method="post">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="customer_id" class="form-label">Müşteri</label>
                        <select class="form-select" id="customer_id" name="customer_id">
                            <option value="" selected>Seçiniz veya yeni ekleyin</option>
                            <?php foreach($customers as $id => $customer): ?>
                                <option value="<?php echo $id; ?>"><?php echo $customer['name'] . ' (' . $customer['phone'] . ')'; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div id="new_customer_fields">
                        <div class="mb-3">
                            <label for="customer_name" class="form-label">Müşteri Adı</label>
                            <input type="text" class="form-control" id="customer_name" name="customer_name">
                        </div>
                        <div class="mb-3">
                            <label for="customer_phone" class="form-label">Telefon</label>
                            <input type="text" class="form-control" id="customer_phone" name="customer_phone">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="reservation_date" class="form-label">Tarih</label>
                            <input type="date" class="form-control" id="reservation_date" name="reservation_date" min="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="reservation_time" class="form-label">Saat</label>
                            <input type="time" class="form-control" id="reservation_time" name="reservation_time" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="number_of_guests" class="form-label">Misafir Sayısı</label>
                        <input type="number" min="1" class="form-control" id="number_of_guests" name="number_of_guests" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="table_id" class="form-label">Masa</label>
                        <select class="form-select" id="table_id" name="table_id" required>
                            <option value="" selected disabled>Masa Seçin</option>
                            <?php foreach($tables as $id => $table): ?>
                                <option value="<?php echo $id; ?>">
                                    Masa <?php echo $table['number']; ?> (<?php echo $table['capacity']; ?> kişilik)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" name="add_reservation" class="btn btn-primary">Rezervasyon Oluştur</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Müşteri seçildiğinde yeni müşteri alanlarını gizle/göster
    const customerSelect = document.getElementById('customer_id');
    const newCustomerFields = document.getElementById('new_customer_fields');
    
    customerSelect.addEventListener('change', function() {
        if(this.value === '') {
            newCustomerFields.style.display = 'block';
        } else {
            newCustomerFields.style.display = 'none';
        }
    });
});
</script> 