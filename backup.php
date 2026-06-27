<?php
/**
 * backup.php - Halaman Backup Sistem
 */

session_start();

// Cek login admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

require_once 'database.php';

// Cek apakah ada file backup di folder
$backup_files = array();
$backup_dir = 'backup/';

// Buat folder backup jika belum ada
if (!file_exists($backup_dir)) {
    mkdir($backup_dir, 0777, true);
}

// Ambil daftar file backup
$files = scandir($backup_dir);
foreach ($files as $file) {
    if ($file != '.' && $file != '..') {
        $file_path = $backup_dir . $file;
        $backup_files[] = array(
            'nama' => $file,
            'size' => filesize($file_path),
            'modified' => date('d M Y H:i:s', filemtime($file_path)),
            'path' => $file_path
        );
    }
}

// Urutkan dari yang terbaru
usort($backup_files, function($a, $b) {
    return strtotime($b['modified']) - strtotime($a['modified']);
});

// Ambil statistik database
$query_total = "SELECT 
    (SELECT COUNT(*) FROM menu) as total_menu,
    (SELECT COUNT(*) FROM transaksi) as total_transaksi,
    (SELECT COUNT(*) FROM pesanan) as total_pesanan";
$result = mysqli_query($conn, $query_total);
$stats = mysqli_fetch_assoc($result);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Backup - Loehoer Restaurant</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap');
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e9ecef 100%);
            overflow-x: hidden;
        }
        
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
        
        .btn-logout {
            background: rgba(255, 255, 255, 0.15);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            padding: 10px;
            font-size: 0.85rem;
            font-weight: 500;
            transition: all 0.3s ease;
            cursor: pointer;
            width: 100%;
            text-align: center;
        }
        .btn-logout:hover { background: #ef4444; border-color: #ef4444; transform: translateY(-2px); color: white; }
        
        .main-content { margin-left: 280px; padding: 20px; transition: all 0.3s ease; }
        
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
        
        .card-backup {
            background: white;
            border-radius: 25px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
        }
        .card-backup:hover { box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15); }
        
        .btn-backup {
            background: linear-gradient(135deg, #4a0000, #9a0000);
            color: white;
            border: none;
            border-radius: 12px;
            padding: 12px 25px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-backup:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(74, 0, 0, 0.3); color: white; }
        
        .btn-download {
            background: #facc15;
            color: #1a1a1a;
            border: none;
            border-radius: 12px;
            padding: 8px 15px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-download:hover { background: #eab308; transform: translateY(-2px); }
        
        .btn-delete-backup {
            background: #ef4444;
            color: white;
            border: none;
            border-radius: 12px;
            padding: 8px 15px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-delete-backup:hover { background: #dc2626; transform: translateY(-2px); color: white; }
        
        .stat-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            background: #e9ecef;
            color: #1a1a1a;
        }
        
        .footer-premium {
            background: white;
            border-radius: 20px;
            padding: 20px;
            margin-top: 30px;
            text-align: center;
            box-shadow: 0 -5px 20px rgba(0, 0, 0, 0.05);
        }
        .footer-premium p { margin: 0; color: #6c757d; font-size: 0.85rem; }
        
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
        }
        
        @media (max-width: 992px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.show { transform: translateX(0); }
            .main-content { margin-left: 0; }
            .menu-toggle { display: block !important; }
        }
        
        @media (max-width: 768px) {
            .hero-banner-mini h1 { font-size: 1.3rem; }
        }
        
        .fade-up {
            animation: fadeUp 0.8s ease forwards;
        }
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>

<!-- Menu Toggle Mobile -->
<button class="menu-toggle" onclick="toggleSidebar()">
    <i class="fas fa-bars"></i>
</button>

<!-- ============================================ -->
<!-- SIDEBAR -->
<!-- ============================================ -->
<div class="sidebar" id="sidebar">
    <div class="sidebar-logo">
        <h3><i class="fas fa-utensils"></i> Loehoer</h3>
        <p>Restaurant Management System</p>
    </div>
    
    <ul class="sidebar-nav">
        <li class="nav-item">
            <a class="nav-link" href="dashboard-premium.php">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="tambah.php">
                <i class="fas fa-plus-circle"></i> Tambah Menu
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="manajemen_pesanan.php">
                <i class="fas fa-clipboard-list"></i> Manajemen Pesanan
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="dashboard_analitik.php">
                <i class="fas fa-chart-line"></i> Analitik Penjualan
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link active" href="backup.php">
                <i class="fas fa-database"></i> Backup
            </a>
        </li>
    </ul>
    
    <div class="sidebar-user">
        <div class="d-flex align-items-center">
            <div class="user-avatar me-3">MR</div>
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

<!-- ============================================ -->
<!-- MAIN CONTENT -->
<!-- ============================================ -->
<div class="main-content">
    
    <!-- HERO BANNER -->
    <div class="hero-banner-mini fade-up">
        <div class="hero-content">
            <h1><i class="fas fa-database me-2"></i> Backup <span class="gradient-text">Sistem</span></h1>
            <p>Backup database dan file sistem restoran Anda</p>
        </div>
    </div>
    
    <!-- STATISTIK DATABASE -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card-backup text-center">
                <h4 class="text-muted">Total Menu</h4>
                <h2 class="text-primary"><?php echo number_format($stats['total_menu']); ?></h2>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card-backup text-center">
                <h4 class="text-muted">Total Transaksi</h4>
                <h2 class="text-success"><?php echo number_format($stats['total_transaksi']); ?></h2>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card-backup text-center">
                <h4 class="text-muted">Total Pesanan</h4>
                <h2 class="text-warning"><?php echo number_format($stats['total_pesanan']); ?></h2>
            </div>
        </div>
    </div>
    
    <!-- TOMBOL BACKUP -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card-backup text-center">
                <i class="fas fa-database fa-3x text-primary mb-3"></i>
                <h5>Backup Database</h5>
                <p class="text-muted small">Backup semua data (SQL)</p>
                <button onclick="backupDatabase()" class="btn btn-backup">
                    <i class="fas fa-download me-2"></i> Backup Database
                </button>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card-backup text-center">
                <i class="fas fa-image fa-3x text-success mb-3"></i>
                <h5>Backup Uploads</h5>
                <p class="text-muted small">Backup semua gambar menu</p>
                <button onclick="backupUploads()" class="btn btn-backup">
                    <i class="fas fa-download me-2"></i> Backup Uploads
                </button>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card-backup text-center">
                <i class="fas fa-folder fa-3x text-warning mb-3"></i>
                <h5>Backup Full</h5>
                <p class="text-muted small">Backup semua file project</p>
                <button onclick="backupFull()" class="btn btn-backup">
                    <i class="fas fa-download me-2"></i> Backup Full
                </button>
            </div>
        </div>
    </div>
    
    <!-- DAFTAR BACKUP -->
    <div class="card-backup">
        <h5 class="mb-3"><i class="fas fa-history me-2"></i> Riwayat Backup</h5>
        
        <?php if (empty($backup_files)): ?>
            <div class="text-center py-4">
                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                <p>Belum ada file backup</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Nama File</th>
                            <th>Ukuran</th>
                            <th>Tanggal</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($backup_files as $file): ?>
                            <tr>
                                <td>
                                    <i class="fas fa-file-archive me-2"></i>
                                    <?php echo $file['nama']; ?>
                                </td>
                                <td><?php echo number_format($file['size'] / 1024, 1); ?> KB</td>
                                <td><?php echo $file['modified']; ?></td>
                                <td>
                                    <a href="<?php echo $file['path']; ?>" download class="btn btn-download btn-sm">
                                        <i class="fas fa-download"></i> Download
                                    </a>
                                    <button onclick="deleteBackup('<?php echo $file['nama']; ?>')" class="btn btn-delete-backup btn-sm">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- FOOTER -->
    <div class="footer-premium">
        <p>
            <i class="fas fa-copyright me-1"></i> 2026 Loehoer Restaurant. 
            Built with <i class="fas fa-heart text-danger"></i> using PHP, Bootstrap
        </p>
    </div>
</div>

<!-- Form Backup (Hidden) -->
<form id="backupForm" action="backup_database.php" method="POST" target="_blank">
    <input type="hidden" name="action" id="backupAction" value="">
</form>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
// ============================================
// FUNGSI BACKUP
// ============================================

function backupDatabase() {
    Swal.fire({
        title: 'Backup Database?',
        text: 'Proses backup database akan memakan waktu beberapa saat',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#4a0000',
        confirmButtonText: 'Ya, Backup!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            // Tampilkan loading
            Swal.fire({
                title: 'Memproses...',
                text: 'Sedang membuat backup database',
                icon: 'info',
                showConfirmButton: false,
                timer: 2000
            });
            
            setTimeout(() => {
                window.location.href = 'backup_database.php';
            }, 2000);
        }
    });
}

function backupUploads() {
    Swal.fire({
        title: 'Backup Uploads?',
        text: 'Proses backup gambar akan memakan waktu beberapa saat',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#4a0000',
        confirmButtonText: 'Ya, Backup!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Memproses...',
                text: 'Sedang membuat backup uploads',
                icon: 'info',
                showConfirmButton: false,
                timer: 2000
            });
            
            setTimeout(() => {
                window.location.href = 'backup_uploads.php';
            }, 2000);
        }
    });
}

function backupFull() {
    Swal.fire({
        title: 'Backup Full?',
        text: 'Proses backup full project akan memakan waktu beberapa saat',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#4a0000',
        confirmButtonText: 'Ya, Backup!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Memproses...',
                text: 'Sedang membuat backup full project',
                icon: 'info',
                showConfirmButton: false,
                timer: 3000
            });
            
            setTimeout(() => {
                window.location.href = 'backup_full.php';
            }, 3000);
        }
    });
}

// ============================================
// HAPUS BACKUP
// ============================================

function deleteBackup(filename) {
    Swal.fire({
        title: 'Hapus Backup?',
        text: 'Apakah Anda yakin ingin menghapus ' + filename + '?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'backup_delete.php?file=' + encodeURIComponent(filename);
        }
    });
}

// ============================================
// TOGGLE SIDEBAR
// ============================================

function toggleSidebar() {
    var sidebar = document.getElementById('sidebar');
    if (sidebar) sidebar.classList.toggle('show');
}

// ============================================
// KONFIRMASI LOGOUT
// ============================================

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
