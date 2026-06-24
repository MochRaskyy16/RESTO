<?php
/**
 * manajemen_pesanan.php - Admin melihat dan mengupdate status pesanan
 * Tampilan selaras dengan dashboard-premium.php
 */

session_start();

// Cek apakah admin sudah login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

require_once 'database.php';

// Update status pesanan
if (isset($_POST['update_status'])) {
    $id_pesanan = (int)$_POST['id_pesanan'];
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    $query = "UPDATE pesanan SET status = '$status' WHERE id = $id_pesanan";
    mysqli_query($conn, $query);
    
    header("Location: manajemen_pesanan.php?status=updated");
    exit();
}

// Hapus pesanan
if (isset($_GET['hapus'])) {
    $id_pesanan = (int)$_GET['hapus'];
    
    // Hapus detail pesanan terlebih dahulu
    $query_hapus_detail = "DELETE FROM detail_pesanan WHERE id_pesanan = $id_pesanan";
    mysqli_query($conn, $query_hapus_detail);
    
    // Hapus pesanan
    $query_hapus = "DELETE FROM pesanan WHERE id = $id_pesanan";
    if (mysqli_query($conn, $query_hapus)) {
        header("Location: manajemen_pesanan.php?status=deleted");
        exit();
    } else {
        header("Location: manajemen_pesanan.php?status=error");
        exit();
    }
}

// Ambil semua pesanan
$query = "SELECT p.*, 
          (SELECT COUNT(*) FROM detail_pesanan dp WHERE dp.id_pesanan = p.id) as jumlah_item
          FROM pesanan p 
          ORDER BY p.tanggal_pesan DESC";
$result = mysqli_query($conn, $query);

// Hitung statistik pesanan
$query_total = "SELECT 
                    COUNT(*) as total_pesanan,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = 'proses' THEN 1 ELSE 0 END) as proses,
                    SUM(CASE WHEN status = 'selesai' THEN 1 ELSE 0 END) as selesai,
                    SUM(CASE WHEN status = 'batal' THEN 1 ELSE 0 END) as batal,
                    COALESCE(SUM(total_harga), 0) as total_pendapatan
                FROM pesanan";
$result_total = mysqli_query($conn, $query_total);
$stats = mysqli_fetch_assoc($result_total);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Pesanan - Loehoer Restaurant</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        /* ============================================ */
        /* PREMIUM DASHBOARD CSS - SAMA DENGAN DASHBOARD */
        /* ============================================ */
        
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap');
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e9ecef 100%);
            overflow-x: hidden;
        }
        
        /* Loading Spinner */
        .spinner-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }
        .spinner-overlay.show { opacity: 1; visibility: visible; }
        .spinner {
            width: 60px;
            height: 60px;
            border: 4px solid rgba(255, 255, 255, 0.3);
            border-top-color: #ffb347;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
        
        /* Sidebar */
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
        
        /* Sidebar User */
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

.user-info h6 {
    margin: 0;
    font-size: 0.9rem;
    font-weight: 600;
}

.user-info p {
    margin: 0;
    font-size: 0.7rem;
    opacity: 0.7;
}

.user-status {
    width: 10px;
    height: 10px;
    background: #4ade80;
    border-radius: 50%;
    display: inline-block;
    margin-right: 5px;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

/* Tombol Logout */
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

.btn-logout:hover {
    background: #ef4444;
    border-color: #ef4444;
    transform: translateY(-2px);
    color: white;
}
        /* Main Content */
        .main-content { margin-left: 280px; padding: 20px; transition: all 0.3s ease; }
        
        /* Hero Banner Mini */
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
        
        /* Stat Cards */
        .stat-card {
            background: white;
            border-radius: 25px;
            padding: 20px;
            margin-bottom: 25px;
            transition: all 0.3s ease;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            position: relative;
            overflow: hidden;
        }
        .stat-card:hover { transform: translateY(-5px); box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15); }
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 5px;
            height: 100%;
            background: linear-gradient(180deg, #4a0000, #9a0000);
        }
        .stat-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #ffd89b, #ffb347);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: #4a0000;
            margin-bottom: 10px;
        }
        .stat-card h3 { font-size: 1.8rem; font-weight: 800; margin-bottom: 0; color: #1a1a1a; }
        .stat-card p { color: #6c757d; margin-bottom: 0; font-size: 0.85rem; }
        
        /* Table Premium */
        .table-premium {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
        }
        .table-premium thead th {
            background: linear-gradient(135deg, #4a0000, #7a0000);
            color: white;
            border: none;
            padding: 15px;
            font-weight: 600;
            font-size: 0.85rem;
        }
        .table-premium tbody tr { transition: all 0.3s ease; }
        .table-premium tbody tr:hover { background: rgba(154, 0, 0, 0.05); }
        .table-premium td { vertical-align: middle; padding: 12px; }
        
        /* Status Badges */
        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-block;
        }
        .status-pending { background: #f59e0b; color: white; }
        .status-proses { background: #3b82f6; color: white; }
        .status-selesai { background: #10b981; color: white; }
        .status-batal { background: #ef4444; color: white; }
        
        /* Buttons */
        .btn-action {
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 0.75rem;
            font-weight: 500;
            transition: all 0.3s ease;
            margin: 0 3px;
            border: none;
        }
        .btn-warning-custom { background: #facc15; color: #1a1a1a; }
        .btn-warning-custom:hover { background: #eab308; transform: translateY(-2px); }
        .btn-info-custom { background: #3b82f6; color: white; }
        .btn-info-custom:hover { background: #2563eb; transform: translateY(-2px); }
        .btn-danger-custom { background: #ef4444; color: white; }
        .btn-danger-custom:hover { background: #dc2626; transform: translateY(-2px); }
        
        /* Footer */
        .footer-premium {
            background: white;
            border-radius: 20px;
            padding: 20px;
            margin-top: 30px;
            text-align: center;
            box-shadow: 0 -5px 20px rgba(0, 0, 0, 0.05);
        }
        .footer-premium p { margin: 0; color: #6c757d; font-size: 0.85rem; }
        
        /* Alert */
        .alert-premium {
            border-radius: 15px;
            border: none;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        
        /* Responsive */
        @media (max-width: 992px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.show { transform: translateX(0); }
            .main-content { margin-left: 0; }
            .menu-toggle { display: block !important; }
        }
        @media (max-width: 768px) {
            .hero-banner-mini h1 { font-size: 1.3rem; }
            .stat-card h3 { font-size: 1.3rem; }
            .table-premium { font-size: 0.75rem; }
            .btn-action { padding: 4px 8px; font-size: 0.65rem; }
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
    </style>
</head>
<body>

<!-- Loading Spinner -->
<div id="loadingSpinner" class="spinner-overlay">
    <div class="spinner"></div>
</div>

<!-- Menu Toggle untuk Mobile -->
<button class="menu-toggle" onclick="toggleSidebar()">
    <i class="fas fa-bars"></i>
</button>

<!-- ============================================ -->
<!-- SIDEBAR PREMIUM -->
<!-- ============================================ -->
<div class="sidebar" id="sidebar">
    <div class="sidebar-logo">
        <h3>
            <i class="fas fa-utensils"></i> Loehoer
        </h3>
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
            <a class="nav-link active" href="manajemen_pesanan.php">
                <i class="fas fa-clipboard-list"></i> Manajemen Pesanan
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="dashboard_analitik.php">
                <i class="fas fa-chart-line"></i> Analitik Penjualan
            </a>
        </li>
        
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

<!-- ============================================ -->
<!-- MAIN CONTENT -->
<!-- ============================================ -->
<div class="main-content">
    
    <!-- HERO BANNER MINI -->
    <div class="hero-banner-mini fade-up">
        <div class="hero-content">
            <h1>
                <i class="fas fa-clipboard-list me-2"></i> 
                Manajemen <span class="gradient-text">Pesanan</span>
            </h1>
            <p>Kelola dan pantau semua pesanan dari pembeli</p>
        </div>
    </div>
    
    <!-- Alert Success Update -->
    <?php if (isset($_GET['status']) && $_GET['status'] == 'updated'): ?>
        <div class="alert alert-success alert-premium alert-dismissible fade show fade-up" role="alert">
            <i class="fas fa-check-circle me-2"></i> Status pesanan berhasil diupdate!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <!-- Alert Hapus Berhasil -->
    <?php if (isset($_GET['status']) && $_GET['status'] == 'deleted'): ?>
        <div class="alert alert-success alert-premium alert-dismissible fade show fade-up" role="alert">
            <i class="fas fa-trash-alt me-2"></i> Pesanan berhasil dihapus!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <!-- Alert Hapus Gagal -->
    <?php if (isset($_GET['status']) && $_GET['status'] == 'error'): ?>
        <div class="alert alert-danger alert-premium alert-dismissible fade show fade-up" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i> Gagal menghapus pesanan!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <!-- STATISTIK CARDS -->
    <div class="row mb-4">
        <div class="col-md-6 col-lg-3">
            <div class="stat-card fade-up" style="animation-delay: 0.1s;">
                <div class="stat-icon"><i class="fas fa-receipt"></i></div>
                <h3><?php echo number_format($stats['total_pesanan']); ?></h3>
                <p>Total Pesanan</p>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="stat-card fade-up" style="animation-delay: 0.2s;">
                <div class="stat-icon"><i class="fas fa-clock"></i></div>
                <h3><?php echo number_format($stats['pending']); ?></h3>
                <p>Pending</p>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="stat-card fade-up" style="animation-delay: 0.3s;">
                <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                <h3><?php echo number_format($stats['selesai']); ?></h3>
                <p>Selesai</p>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="stat-card fade-up" style="animation-delay: 0.4s;">
                <div class="stat-icon"><i class="fas fa-money-bill-wave"></i></div>
                <h3>Rp <?php echo number_format($stats['total_pendapatan'], 0, ',', '.'); ?></h3>
                <p>Total Pendapatan</p>
            </div>
        </div>
    </div>
    
    <!-- TABEL PESANAN PREMIUM -->
    <div class="table-premium fade-up" style="animation-delay: 0.5s;">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>No Pesanan</th>
                        <th>Pemesan</th>
                        <th>Meja</th>
                        <th>Total</th>
                        <th>Item</th>
                        <th>Tanggal</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($result) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td>
                                    <span class="fw-bold"><?php echo $row['no_pesanan']; ?></span>
                                </td>
                                <td><?php echo htmlspecialchars($row['nama_pemesan']); ?></td>
                                <td><?php echo $row['no_meja'] ?: '-'; ?></td>
                                <td>Rp <?php echo number_format($row['total_harga'], 0, ',', '.'); ?></td>
                                <td><?php echo $row['jumlah_item']; ?> item</td>
                                <td><?php echo date('d/m/Y H:i', strtotime($row['tanggal_pesan'])); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $row['status']; ?>">
                                        <?php 
                                        $status_text = [
                                            'pending' => 'Pending',
                                            'proses' => 'Proses',
                                            'selesai' => 'Selesai',
                                            'batal' => 'Batal'
                                        ];
                                        echo $status_text[$row['status']] ?? ucfirst($row['status']);
                                        ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn-action btn-warning-custom" onclick="updateStatus(<?php echo $row['id']; ?>, '<?php echo $row['status']; ?>')">
                                        <i class="fas fa-edit"></i> Update
                                    </button>
                                    <!-- <button class="btn-action btn-info-custom" onclick="lihatDetail(<?php echo $row['id']; ?>)">
                                        <i class="fas fa-eye"></i> Detail
                                    </button> -->
                                    <button class="btn-action btn-danger-custom" onclick="hapusPesanan(<?php echo $row['id']; ?>, '<?php echo $row['no_pesanan']; ?>')">
                                        <i class="fas fa-trash"></i> Hapus
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center py-5">
                                <i class="fas fa-inbox fa-3x mb-3 d-block text-muted"></i>
                                <h5>Belum ada pesanan</h5>
                                <p class="text-muted">Belum ada pesanan dari pembeli</p>
                              </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- FOOTER PREMIUM -->
    <div class="footer-premium">
        <p>
            <i class="fas fa-copyright me-1"></i> 2026 Loehoer Restaurant. 
            Built with <i class="fas fa-heart text-danger"></i> using PHP, Bootstrap
        </p>
    </div>
</div>

<!-- Form Update Status (Hidden) -->
<form id="updateStatusForm" method="POST" style="display: none;">
    <input type="hidden" name="id_pesanan" id="update_id">
    <input type="hidden" name="status" id="update_status">
    <input type="hidden" name="update_status" value="1">
</form>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Loading spinner
window.addEventListener('load', function() {
    setTimeout(function() {
        var spinner = document.getElementById('loadingSpinner');
        if (spinner) spinner.classList.remove('show');
    }, 500);
});

// Toggle sidebar untuk mobile
function toggleSidebar() {
    var sidebar = document.getElementById('sidebar');
    if (sidebar) sidebar.classList.toggle('show');
}

// Update status pesanan dengan SweetAlert
function updateStatus(id, currentStatus) {
    const statusOptions = {
        'pending': 'Pending',
        'proses': 'Proses',
        'selesai': 'Selesai',
        'batal': 'Batal'
    };
    
    Swal.fire({
        title: 'Update Status Pesanan',
        text: 'Pilih status baru untuk pesanan ini',
        icon: 'question',
        input: 'select',
        inputOptions: statusOptions,
        inputValue: currentStatus,
        showCancelButton: true,
        confirmButtonColor: '#4a0000',
        confirmButtonText: 'Update',
        cancelButtonText: 'Batal',
        inputValidator: (value) => {
            if (!value) {
                return 'Pilih status terlebih dahulu!';
            }
        }
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('update_id').value = id;
            document.getElementById('update_status').value = result.value;
            document.getElementById('updateStatusForm').submit();
        }
    });
}

// Lihat detail pesanan
function lihatDetail(id) {
    window.open(`detail_pesanan_admin.php?id=${id}`, '_blank', 'width=700,height=600,scrollbars=yes');
}

// Hapus pesanan dengan konfirmasi SweetAlert
function hapusPesanan(id, noPesanan) {
    Swal.fire({
        title: 'Hapus Pesanan?',
        html: `Apakah Anda yakin ingin menghapus pesanan <strong>${noPesanan}</strong>?<br><br>
               <span class="text-danger">⚠️ Peringatan: Data yang dihapus tidak dapat dikembalikan!</span>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = `manajemen_pesanan.php?hapus=${id}`;
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
