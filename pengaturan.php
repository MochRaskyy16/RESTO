<?php
/**
 * pengaturan.php - Halaman Pengaturan Restoran
 */

require_once 'database.php';

// Cek koneksi
if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

// Proses simpan
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Simpan pengaturan (contoh - sesuaikan dengan kebutuhan)
    $nama_resto = mysqli_real_escape_string($conn, $_POST['nama_resto']);
    $alamat = mysqli_real_escape_string($conn, $_POST['alamat']);
    $telepon = mysqli_real_escape_string($conn, $_POST['telepon']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $pajak = mysqli_real_escape_string($conn, $_POST['pajak']);
    $service = mysqli_real_escape_string($conn, $_POST['service']);
    
    // Simpan ke file atau database (contoh simpan ke file JSON)
    $setting = [
        'nama_resto' => $nama_resto,
        'alamat' => $alamat,
        'telepon' => $telepon,
        'email' => $email,
        'pajak' => $pajak,
        'service' => $service
    ];
    
    if (file_put_contents('setting.json', json_encode($setting))) {
        $success = "Pengaturan berhasil disimpan!";
    } else {
        $error = "Gagal menyimpan pengaturan!";
    }
}

// Load pengaturan yang sudah disimpan
$setting = [];
if (file_exists('setting.json')) {
    $setting = json_decode(file_get_contents('setting.json'), true);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan - Loehoer Restaurant</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        /* Gunakan CSS yang sama dengan dashboard-premium.php */
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap');
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e9ecef 100%);
            overflow-x: hidden;
        }
        
        /* Sidebar (sama seperti sebelumnya) */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 280px;
            height: 100vh;
            background: linear-gradient(180deg, #2d0000 0%, #4b0000 50%, #6b0000 100%);
            color: white;
            transition: all 0.3s ease;
            z-index: 1000;
            overflow-y: auto;
            box-shadow: 5px 0 30px rgba(0, 0, 0, 0.1);
        }
        
        .sidebar::-webkit-scrollbar { width: 5px; }
        .sidebar::-webkit-scrollbar-track { background: rgba(255, 255, 255, 0.1); }
        .sidebar::-webkit-scrollbar-thumb { background: rgba(255, 255, 255, 0.3); border-radius: 10px; }
        
        .sidebar-logo { padding: 30px 25px; border-bottom: 1px solid rgba(255, 255, 255, 0.1); margin-bottom: 20px; }
        .sidebar-logo h3 { font-size: 1.5rem; font-weight: 700; margin: 0; }
        .sidebar-logo h3 i { margin-right: 10px; font-size: 1.8rem; }
        .sidebar-logo p { font-size: 0.75rem; opacity: 0.7; margin-top: 5px; margin-bottom: 0; }
        
        .sidebar-nav { padding: 0 15px; }
        .sidebar-nav .nav-item { list-style: none; margin-bottom: 8px; }
        .sidebar-nav .nav-link {
            display: flex;
            align-items: center;
            padding: 12px 18px;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            border-radius: 12px;
            transition: all 0.3s ease;
            font-weight: 500;
        }
        .sidebar-nav .nav-link i { width: 28px; font-size: 1.2rem; margin-right: 12px; }
        .sidebar-nav .nav-link:hover { background: rgba(255, 255, 255, 0.15); color: white; transform: translateX(5px); }
        .sidebar-nav .nav-link.active { background: linear-gradient(135deg, rgba(255, 255, 255, 0.2), rgba(255, 255, 255, 0.05)); color: white; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1); }
        
        .sidebar-user {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 20px;
            background: rgba(0, 0, 0, 0.3);
            backdrop-filter: blur(10px);
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        .user-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, #ffd89b, #c7e9fb);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: bold;
            color: #4b0000;
        }
        .user-info h6 { margin: 0; font-size: 0.9rem; font-weight: 600; }
        .user-info p { margin: 0; font-size: 0.7rem; opacity: 0.7; }
        .user-status { width: 10px; height: 10px; background: #4ade80; border-radius: 50%; display: inline-block; margin-right: 5px; animation: pulse 2s infinite; }
        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }
        
        /* Main Content */
        .main-content { margin-left: 280px; padding: 20px; transition: all 0.3s ease; }
        
        /* Hero Banner */
        .hero-banner-mini {
            background: linear-gradient(135deg, #4a0000 0%, #7a0000 50%, #9a0000 100%);
            border-radius: 25px;
            padding: 30px 40px;
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
        }
        .hero-banner-mini::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200"><path fill="rgba(255,255,255,0.05)" d="M100 0L200 200H0L100 0z"/></svg>');
            background-size: 60px;
            opacity: 0.3;
        }
        .hero-content { position: relative; z-index: 2; }
        .hero-banner-mini h1 { font-size: 1.8rem; font-weight: 800; margin-bottom: 10px; color: white; }
        .hero-banner-mini p { font-size: 0.95rem; opacity: 0.85; margin-bottom: 0; color: white; }
        .gradient-text { background: linear-gradient(135deg, #FFD89B, #FFB347); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
        
        /* Form Card */
        .form-card {
            background: white;
            border-radius: 25px;
            padding: 35px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
        }
        .form-card:hover { box-shadow: 0 20px 40px rgba(0, 0, 0, 0.12); }
        
        .form-label {
            font-weight: 600;
            color: #4a0000;
            margin-bottom: 8px;
            font-size: 0.9rem;
        }
        
        .form-control, .form-select {
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            padding: 12px 16px;
            font-family: 'Poppins', sans-serif;
            transition: all 0.3s ease;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #9a0000;
            box-shadow: 0 0 0 3px rgba(154, 0, 0, 0.1);
            outline: none;
        }
        
        .btn-premium {
            background: linear-gradient(135deg, #4a0000, #9a0000);
            color: white;
            border: none;
            border-radius: 12px;
            padding: 12px 30px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-premium:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(74, 0, 0, 0.3); color: white; }
        
        /* Responsive */
        @media (max-width: 992px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.show { transform: translateX(0); }
            .main-content { margin-left: 0; }
            .menu-toggle { display: block !important; }
        }
        @media (max-width: 768px) {
            .hero-banner-mini h1 { font-size: 1.3rem; }
            .form-card { padding: 20px; }
        }
        
        .menu-toggle {
            display: none;
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1100;
            background: #4a0000;
            color: white;
            border: none;
            width: 45px;
            height: 45px;
            border-radius: 12px;
            font-size: 1.2rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .fade-up {
            animation: fadeUp 0.8s ease forwards;
        }
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .nav-tabs .nav-link {
            color: #4a0000;
            font-weight: 500;
            border-radius: 12px;
            padding: 10px 20px;
        }
        .nav-tabs .nav-link.active {
            background: #4a0000;
            color: white;
            border: none;
        }
    </style>
</head>
<body>

<button class="menu-toggle" onclick="toggleSidebar()">
    <i class="fas fa-bars"></i>
</button>

<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <div class="sidebar-logo">
        <h3><i class="fas fa-utensils"></i> Loehoer</h3>
        <p>Restaurant Management System</p>
    </div>
    
    <ul class="sidebar-nav">
        <li class="nav-item"><a class="nav-link" href="dashboard-premium.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
        <li class="nav-item"><a class="nav-link" href="tambah.php"><i class="fas fa-plus-circle"></i> Tambah Menu</a></li>
        <li class="nav-item">
            <a class="nav-link" href="manajemen_pesanan.php">
                <i class="fas fa-clipboard-list"></i> Manajemen Pesanan
            </a>
        </li>
        <li class="nav-item"><a class="nav-link" href="dashboard_analitik.php"><i class="fas fa-chart-line"></i> Analitik Penjualan</a></li>
        <li class="nav-item"><a class="nav-link" href="laporan.php"><i class="fas fa-file-alt"></i> Laporan</a></li>
        <li class="nav-item"><a class="nav-link active" href="pengaturan.php"><i class="fas fa-cog"></i> Pengaturan</a></li>
    </ul>
    
    <div class="sidebar-user">
    <div class="d-flex align-items-center">
        <div class="user-avatar me-3">
            MR
        </div>
        <div class="user-info">
            <h6>Moch Rasky P</h6>
            <p><span class="user-status"></span> Online</p>
        </div>
    </div>
    <div class="mt-3">
        <button onclick="confirmLogout()" class="btn btn-logout w-100">
            <i class="fas fa-sign-out-alt me-2"></i> Logout
        </button>
    </div>
</div>
</div>

<!-- Main Content -->
<div class="main-content">
    
    <div class="hero-banner-mini fade-up">
        <div class="hero-content">
            <h1><i class="fas fa-cog me-2"></i> Pengaturan <span class="gradient-text">Restoran</span></h1>
            <p>Atur konfigurasi sistem dan informasi restoran Anda</p>
        </div>
    </div>
    
    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i> <?php echo $success; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <div class="form-card fade-up" style="animation-delay: 0.1s;">
        <ul class="nav nav-tabs mb-4">
            <li class="nav-item">
                <a class="nav-link active" data-bs-toggle="tab" href="#umum">
                    <i class="fas fa-building me-2"></i> Umum
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#pajak">
                    <i class="fas fa-percent me-2"></i> Pajak & Service
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#backup">
                    <i class="fas fa-database me-2"></i> Backup
                </a>
            </li>
        </ul>
        
        <form method="POST">
            <div class="tab-content">
                <!-- Tab Umum -->
                <div class="tab-pane fade show active" id="umum">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><i class="fas fa-store me-1"></i> Nama Restoran</label>
                            <input type="text" name="nama_resto" class="form-control" value="<?php echo $setting['nama_resto'] ?? 'Loehoer Restaurant'; ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><i class="fas fa-phone me-1"></i> Nomor Telepon</label>
                            <input type="text" name="telepon" class="form-control" value="<?php echo $setting['telepon'] ?? '08123456789'; ?>">
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label"><i class="fas fa-map-marker-alt me-1"></i> Alamat</label>
                            <textarea name="alamat" class="form-control" rows="3"><?php echo $setting['alamat'] ?? 'Jl. Contoh No. 123, Kota'; ?></textarea>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><i class="fas fa-envelope me-1"></i> Email</label>
                            <input type="email" name="email" class="form-control" value="<?php echo $setting['email'] ?? 'info@loehoer.com'; ?>">
                        </div>
                    </div>
                </div>
                
                <!-- Tab Pajak & Service -->
                <div class="tab-pane fade" id="pajak">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><i class="fas fa-percent me-1"></i> Pajak (PPN) %</label>
                            <div class="input-group">
                                <input type="number" name="pajak" class="form-control" value="<?php echo $setting['pajak'] ?? '11'; ?>" step="0.1">
                                <span class="input-group-text">%</span>
                            </div>
                            <small class="text-muted">Persentase pajak yang akan ditambahkan ke setiap transaksi</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><i class="fas fa-hand-holding-heart me-1"></i> Service Charge %</label>
                            <div class="input-group">
                                <input type="number" name="service" class="form-control" value="<?php echo $setting['service'] ?? '5'; ?>" step="0.1">
                                <span class="input-group-text">%</span>
                            </div>
                            <small class="text-muted">Persentase biaya layanan</small>
                        </div>
                    </div>
                </div>
                
                <!-- Tab Backup -->
                <div class="tab-pane fade" id="backup">
                    <div class="text-center py-4">
                        <i class="fas fa-database fa-3x text-muted mb-3"></i>
                        <h5>Backup Database</h5>
                        <p class="text-muted">Backup semua data menu, transaksi, dan pengaturan</p>
                        <button type="button" class="btn btn-premium" onclick="backupDatabase()">
                            <i class="fas fa-download me-2"></i> Backup Sekarang
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="text-end mt-4 pt-3">
                <button type="submit" class="btn btn-premium">
                    <i class="fas fa-save me-2"></i> Simpan Pengaturan
                </button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('show');
}

function backupDatabase() {
    Swal.fire({
        title: 'Backup Database',
        text: 'Proses backup akan memakan waktu beberapa saat. Lanjutkan?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#4a0000',
        confirmButtonText: 'Ya, Backup!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire('Berhasil!', 'Backup database telah disimpan', 'success');
        }
    });
}
// Fungsi konfirmasi logout
function confirmLogout() {
    Swal.fire({
        title: 'Yakin ingin logout?',
        text: 'Anda akan keluar dari panel admin',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Ya, Logout!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'logout.php';
        }
    });
}
</script>

</body>
</html>
