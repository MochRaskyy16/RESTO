<?php
/**
 * laporan.php - Halaman Laporan Penjualan
 */

require_once 'database.php';

// Cek koneksi
if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

// Ambil data untuk ringkasan
$query_total_penjualan = "SELECT COALESCE(SUM(total_harga), 0) as total FROM transaksi";
$result_total = mysqli_query($conn, $query_total_penjualan);
$total_penjualan = mysqli_fetch_assoc($result_total)['total'];

$query_total_transaksi = "SELECT COUNT(*) as total FROM transaksi";
$result_transaksi = mysqli_query($conn, $query_total_transaksi);
$total_transaksi = mysqli_fetch_assoc($result_transaksi)['total'];

$query_total_menu_terjual = "SELECT COALESCE(SUM(qty), 0) as total FROM detail_transaksi";
$result_menu_terjual = mysqli_query($conn, $query_total_menu_terjual);
$total_menu_terjual = mysqli_fetch_assoc($result_menu_terjual)['total'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan - Loehoer Restaurant</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    
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
            padding: 25px;
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
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #ffd89b, #ffb347);
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            color: #4a0000;
            margin-bottom: 15px;
        }
        .stat-card h3 { font-size: 2rem; font-weight: 800; margin-bottom: 5px; color: #1a1a1a; }
        .stat-card p { color: #6c757d; margin-bottom: 10px; font-weight: 500; }
        
        /* Card Container */
        .card-container {
            background: white;
            border-radius: 25px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
        }
        
        .btn-premium {
            background: linear-gradient(135deg, #4a0000, #9a0000);
            color: white;
            border: none;
            border-radius: 12px;
            padding: 10px 20px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-premium:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(74, 0, 0, 0.3); color: white; }
        
        .btn-outline-premium {
            background: transparent;
            color: #4a0000;
            border: 2px solid #4a0000;
            border-radius: 12px;
            padding: 10px 20px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-outline-premium:hover { background: #4a0000; color: white; transform: translateY(-2px); }
        
        /* Table */
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

<!-- Menu Toggle untuk Mobile -->
<button class="menu-toggle" onclick="toggleSidebar()">
    <i class="fas fa-bars"></i>
</button>

<!-- ============================================ -->
<!-- SIDEBAR PREMIUM -->
<!-- ============================================ -->
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
        <li class="nav-item"><a class="nav-link active" href="laporan.php"><i class="fas fa-file-alt"></i> Laporan</a></li>
        <li class="nav-item"><a class="nav-link" href="pengaturan.php"><i class="fas fa-cog"></i> Pengaturan</a></li>
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
    
    <!-- HERO BANNER -->
    <div class="hero-banner-mini fade-up">
        <div class="hero-content">
            <h1><i class="fas fa-file-alt me-2"></i> Laporan <span class="gradient-text">Penjualan</span></h1>
            <p>Lihat dan export laporan penjualan restoran Anda</p>
        </div>
    </div>
    
    <!-- STATISTIK CARDS -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="stat-card fade-up" style="animation-delay: 0.1s;">
                <div class="stat-icon"><i class="fas fa-money-bill-wave"></i></div>
                <h3>Rp <?php echo number_format($total_penjualan, 0, ',', '.'); ?></h3>
                <p>Total Pendapatan</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card fade-up" style="animation-delay: 0.2s;">
                <div class="stat-icon"><i class="fas fa-receipt"></i></div>
                <h3><?php echo number_format($total_transaksi); ?></h3>
                <p>Total Transaksi</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card fade-up" style="animation-delay: 0.3s;">
                <div class="stat-icon"><i class="fas fa-hamburger"></i></div>
                <h3><?php echo number_format($total_menu_terjual); ?></h3>
                <p>Total Menu Terjual</p>
            </div>
        </div>
    </div>
    
    <!-- FILTER TANGGAL -->
    <div class="card-container fade-up" style="animation-delay: 0.4s;">
        <h5 class="mb-3"><i class="fas fa-filter me-2"></i> Filter Laporan</h5>
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Dari Tanggal</label>
                <input type="date" id="dari_tanggal" class="form-control" value="<?php echo date('Y-m-01'); ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Sampai Tanggal</label>
                <input type="date" id="sampai_tanggal" class="form-control" value="<?php echo date('Y-m-d'); ?>">
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <button class="btn btn-premium w-100" onclick="filterLaporan()">
                    <i class="fas fa-search me-2"></i> Tampilkan
                </button>
            </div>
        </div>
    </div>
    
    <!-- TABEL LAPORAN -->
    <div class="table-premium fade-up" style="animation-delay: 0.5s;">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Jumlah Transaksi</th>
                        <th>Total Pendapatan</th>
                        <th>Rata-rata</th>
                    </tr>
                </thead>
                <tbody id="laporanTable">
                    <tr>
                        <td colspan="4" class="text-center py-5">
                            <div class="spinner-border text-danger" role="status"></div>
                            <p class="mt-2">Memuat data...</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- TOMBOL EXPORT -->
    <div class="text-center mt-4">
        <button class="btn btn-outline-premium me-2" onclick="exportExcel()">
            <i class="fas fa-file-excel me-2"></i> Export Excel
        </button>
        <button class="btn btn-outline-premium" onclick="window.print()">
            <i class="fas fa-print me-2"></i> Print
        </button>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Toggle sidebar
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('show');
}

// Filter laporan
function filterLaporan() {
    const dari = document.getElementById('dari_tanggal').value;
    const sampai = document.getElementById('sampai_tanggal').value;
    
    fetch(`get_laporan.php?dari=${dari}&sampai=${sampai}`)
        .then(response => response.json())
        .then(data => {
            const tbody = document.getElementById('laporanTable');
            tbody.innerHTML = '';
            
            if (data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="4" class="text-center py-5">Tidak ada data</td></tr>';
                return;
            }
            
            let totalTransaksi = 0;
            let totalPendapatan = 0;
            
            data.forEach(row => {
                totalTransaksi += row.jumlah_transaksi;
                totalPendapatan += row.total_pendapatan;
                tbody.innerHTML += `
                    <tr>
                        <td class="fw-bold">${row.tanggal}</td>
                        <td>${row.jumlah_transaksi} transaksi</td>
                        <td class="text-success fw-bold">Rp ${formatRupiah(row.total_pendapatan)}</td>
                        <td>Rp ${formatRupiah(row.rata_rata)}</td>
                    </tr>
                `;
            });
            
            tbody.innerHTML += `
                <tr class="table-light fw-bold">
                    <td>TOTAL</td>
                    <td>${totalTransaksi} transaksi</td>
                    <td class="text-success">Rp ${formatRupiah(totalPendapatan)}</td>
                    <td>Rp ${formatRupiah(totalTransaksi > 0 ? totalPendapatan / totalTransaksi : 0)}</td>
                </tr>
            `;
        });
}

function formatRupiah(angka) {
    return new Intl.NumberFormat('id-ID').format(Math.round(angka));
}

function exportExcel() {
    Swal.fire('Info', 'Fitur Export Excel akan segera tersedia', 'info');
}

// Load initial data
filterLaporan();
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
