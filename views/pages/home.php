<div class="row mb-4">
    <div class="col-12">
        <div class="jumbotron bg-light p-5 rounded">
            <h1 class="display-4">Restoran Yönetim Sistemi'ne Hoş Geldiniz!</h1>
            <p class="lead">Restoranınızı kolayca yönetmek için tasarlanmış kapsamlı bir sistem.</p>
            <hr class="my-4">
            <p>Menü yönetimi, sipariş takibi, masa rezervasyonu ve daha fazlası tek bir platform üzerinden.</p>
            <?php if(!is_logged_in()): ?>
                <a class="btn btn-primary btn-lg" href="index.php?page=login" role="button">Giriş Yap</a>
            <?php else: ?>
                <a class="btn btn-primary btn-lg" href="index.php?page=dashboard" role="button">Kontrol Paneli</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if(is_logged_in()): ?>
    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="fas fa-utensils fa-3x mb-3 text-primary"></i>
                    <h5 class="card-title">Menü Yönetimi</h5>
                    <p class="card-text">Menü öğelerini ekleyin, düzenleyin veya silin. Malzemeleri ve fiyatları yönetin.</p>
                    <a href="index.php?page=menu" class="btn btn-outline-primary">Menüyü Yönet</a>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="fas fa-receipt fa-3x mb-3 text-success"></i>
                    <h5 class="card-title">Sipariş Takibi</h5>
                    <p class="card-text">Siparişleri oluşturun, takip edin ve tamamlayın. Müşteri bilgilerini kaydedin.</p>
                    <a href="index.php?page=orders" class="btn btn-outline-success">Siparişleri Yönet</a>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="fas fa-chair fa-3x mb-3 text-info"></i>
                    <h5 class="card-title">Masa & Rezervasyonlar</h5>
                    <p class="card-text">Masaları yönetin, durumlarını takip edin ve rezervasyonları planlayın.</p>
                    <a href="index.php?page=tables" class="btn btn-outline-info">Masaları Yönet</a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Son Siparişler</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Sipariş No</th>
                                    <th>Masa</th>
                                    <th>Tutar</th>
                                    <th>Durum</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $orders = get_orders(5);
                                if(mysqli_num_rows($orders) > 0):
                                    while($order = mysqli_fetch_assoc($orders)):
                                ?>
                                <tr>
                                    <td><?php echo $order['order_id']; ?></td>
                                    <td><?php echo $order['table_number']; ?></td>
                                    <td><?php echo number_format($order['total_amount'], 2) . ' ₺'; ?></td>
                                    <td>
                                        <?php if($order['order_status'] == 'Tamamlandı'): ?>
                                            <span class="badge bg-success">Tamamlandı</span>
                                        <?php elseif($order['order_status'] == 'Hazırlanıyor'): ?>
                                            <span class="badge bg-warning text-dark">Hazırlanıyor</span>
                                        <?php elseif($order['order_status'] == 'Bekliyor'): ?>
                                            <span class="badge bg-danger">Bekliyor</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php 
                                    endwhile;
                                else:
                                ?>
                                <tr>
                                    <td colspan="4" class="text-center">Henüz sipariş bulunmuyor.</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <a href="index.php?page=orders" class="btn btn-sm btn-outline-primary">Tüm Siparişleri Görüntüle</a>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Masa Durumları</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php 
                        $tables = get_tables();
                        if(mysqli_num_rows($tables) > 0):
                            while($table = mysqli_fetch_assoc($tables)):
                                $status_class = '';
                                $status_text = '';
                                
                                if($table['table_status'] == 'Boş') {
                                    $status_class = 'bg-success';
                                    $status_text = 'Boş';
                                } elseif($table['table_status'] == 'Dolu') {
                                    $status_class = 'bg-danger';
                                    $status_text = 'Dolu';
                                } elseif($table['table_status'] == 'Rezerve') {
                                    $status_class = 'bg-warning';
                                    $status_text = 'Rezerve';
                                }
                        ?>
                        <div class="col-4 col-md-3 mb-3">
                            <div class="card text-center <?php echo $status_class; ?> text-white">
                                <div class="card-body py-2">
                                    <h5 class="mb-0">Masa <?php echo $table['number']; ?></h5>
                                    <small><?php echo $status_text; ?></small>
                                </div>
                            </div>
                        </div>
                        <?php 
                            endwhile;
                        else:
                        ?>
                        <div class="col-12">
                            <p class="text-center">Henüz masa bilgisi bulunmuyor.</p>
                        </div>
                        <?php endif; ?>
                    </div>
                    <a href="index.php?page=tables" class="btn btn-sm btn-outline-success mt-2">Tüm Masaları Yönet</a>
                </div>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="fas fa-tasks fa-3x mb-3 text-primary"></i>
                    <h5 class="card-title">Kolay Yönetim</h5>
                    <p class="card-text">Restoran operasyonlarınızı tek bir platformdan yönetin.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="fas fa-chart-line fa-3x mb-3 text-success"></i>
                    <h5 class="card-title">Performans Analizleri</h5>
                    <p class="card-text">Satış, çalışan performansı ve envanter durumunu takip edin.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="fas fa-user-friends fa-3x mb-3 text-info"></i>
                    <h5 class="card-title">Müşteri Memnuniyeti</h5>
                    <p class="card-text">Daha hızlı hizmet ve daha az hata ile müşteri memnuniyetini artırın.</p>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?> 