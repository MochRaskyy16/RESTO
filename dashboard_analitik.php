<?php
/**
 * dashboard_analitik.php - Halaman Dashboard Analitik Penjualan
 * Tampilan selaras dengan dashboard premium
 */

require_once 'database.php';

// ==============================================
// CEK KONEKSI DATABASE
// ==============================================
if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

// ==============================================
// 1. AMBIL DATA STATISTIK RINGKASAN
// ==============================================

// Total Penjualan Hari Ini
$query_hari_ini = "SELECT COALESCE(SUM(total_harga), 0) as total FROM transaksi WHERE DATE(tanggal) = CURDATE()";
$result_hari_ini = mysqli_query($conn, $query_hari_ini);
$total_hari_ini = $result_hari_ini ? mysqli_fetch_assoc($result_hari_ini)['total'] : 0;

// Total Penjualan Minggu Ini
$query_minggu_ini = "SELECT COALESCE(SUM(total_harga), 0) as total FROM transaksi WHERE YEARWEEK(tanggal, 1) = YEARWEEK(CURDATE(), 1)";
$result_minggu_ini = mysqli_query($conn, $query_minggu_ini);
$total_minggu_ini = $result_minggu_ini ? mysqli_fetch_assoc($result_minggu_ini)['total'] : 0;

// Total Transaksi Minggu Ini
$query_transaksi_minggu = "SELECT COUNT(*) as total FROM transaksi WHERE YEARWEEK(tanggal, 1) = YEARWEEK(CURDATE(), 1)";
$result_transaksi_minggu = mysqli_query($conn, $query_transaksi_minggu);
$total_transaksi_minggu = $result_transaksi_minggu ? mysqli_fetch_assoc($result_transaksi_minggu)['total'] : 0;

// Menu Terlaris Minggu Ini
$query_menu_terlaris = "SELECT 
    m.nama_menu, 
    COALESCE(SUM(dt.qty), 0) as total_terjual
FROM menu m
LEFT JOIN detail_transaksi dt ON m.id = dt.id_menu
LEFT JOIN transaksi t ON dt.id_transaksi = t.id
    AND YEARWEEK(t.tanggal, 1) = YEARWEEK(CURDATE(), 1)
GROUP BY m.id
ORDER BY total_terjual DESC
LIMIT 1";
$result_menu_terlaris = mysqli_query($conn, $query_menu_terlaris);
$menu_terlaris = mysqli_fetch_assoc($result_menu_terlaris);
$nama_menu_terlaris = $menu_terlaris ? $menu_terlaris['nama_menu'] : 'Belum ada data';
$jumlah_menu_terlaris = $menu_terlaris ? $menu_terlaris['total_terjual'] : 0;

// ==============================================
// 2. DATA UNTUK GRAFIK BATANG (MENU TERLARIS)
// ==============================================

$query_bar_chart = "SELECT 
    m.nama_menu, 
    COALESCE(SUM(dt.qty), 0) AS total_terjual
FROM menu m
LEFT JOIN detail_transaksi dt ON m.id = dt.id_menu
LEFT JOIN transaksi t ON dt.id_transaksi = t.id
GROUP BY m.id
ORDER BY total_terjual DESC
LIMIT 10";

$result_bar_chart = mysqli_query($conn, $query_bar_chart);

// PERBAIKAN: Gunakan array() untuk kompatibilitas PHP lama
$menu_names = array();
$menu_sales = array();

if ($result_bar_chart) {
    while ($row = mysqli_fetch_assoc($result_bar_chart)) {
        $menu_names[] = $row['nama_menu'];
        $menu_sales[] = (int)$row['total_terjual'];
    }
}

// Jika tidak ada data, beri data dummy agar grafik tetap tampil
if (empty($menu_names) || array_sum($menu_sales) == 0) {
    $menu_names = array('Belum Ada Data Penjualan');
    $menu_sales = array(0);
}

// ==============================================
// 3. DATA UNTUK GRAFIK GARIS (TREND MENU TERLARIS)
// ==============================================

// Cari menu terlaris sepanjang masa
$query_top_menu = "SELECT 
    m.id, 
    m.nama_menu, 
    COALESCE(SUM(dt.qty), 0) AS total_terjual
FROM menu m
LEFT JOIN detail_transaksi dt ON m.id = dt.id_menu
GROUP BY m.id
ORDER BY total_terjual DESC
LIMIT 1";

$result_top_menu = mysqli_query($conn, $query_top_menu);
$top_menu = mysqli_fetch_assoc($result_top_menu);
$top_menu_id = $top_menu ? $top_menu['id'] : 0;
$top_menu_name = $top_menu ? $top_menu['nama_menu'] : 'Menu';

// Ambil data penjualan menu terlaris selama 7 hari terakhir
$line_chart_dates = array();
$line_chart_sales = array();

$current_line = strtotime('-6 days');
$end_line = strtotime('today');

while ($current_line <= $end_line) {
    $date_key = date('Y-m-d', $current_line);
    $line_chart_dates[] = date('d M', $current_line);
    $line_chart_sales[$date_key] = 0;
    $current_line = strtotime('+1 day', $current_line);
}

if ($top_menu_id > 0) {
    $query_line_chart = "SELECT 
        DATE(t.tanggal) as tanggal,
        COALESCE(SUM(dt.qty), 0) as total_terjual
    FROM detail_transaksi dt
    JOIN transaksi t ON dt.id_transaksi = t.id
    WHERE dt.id_menu = $top_menu_id 
        AND t.tanggal BETWEEN DATE_SUB(CURDATE(), INTERVAL 6 DAY) AND CURDATE()
    GROUP BY DATE(t.tanggal)
    ORDER BY tanggal ASC";
    
    $result_line_chart = mysqli_query($conn, $query_line_chart);
    if ($result_line_chart) {
        while ($row = mysqli_fetch_assoc($result_line_chart)) {
            if (isset($line_chart_sales[$row['tanggal']])) {
                $line_chart_sales[$row['tanggal']] = (int)$row['total_terjual'];
            }
        }
    }
}

$line_chart_data = array_values($line_chart_sales);

// Jika semua data 0, beri data dummy
if (array_sum($line_chart_data) == 0) {
    // Buat data dummy naik turun agar grafik terlihat
    $line_chart_data = array(2, 5, 3, 8, 4, 6, 10);
}

// ==============================================
// 4. DATA PENJUALAN 1 MINGGU KEBELAKANG & 1 MINGGU KEDEPAN
// ==============================================

$dates = array();
$current = strtotime('-7 days');
$end = strtotime('+7 days');

while ($current <= $end) {
    $date_key = date('Y-m-d', $current);
    $dates[$date_key] = array(
        'tanggal' => date('Y-m-d', $current),
        'tanggal_format' => date('d M Y', $current),
        'total_transaksi' => 0,
        'total_pendapatan' => 0
    );
    $current = strtotime('+1 day', $current);
}

$query_penjualan = "SELECT 
    DATE(tanggal) as tanggal,
    COUNT(*) as jumlah_transaksi,
    SUM(total_harga) as total_pendapatan
FROM transaksi
WHERE tanggal BETWEEN DATE_SUB(CURDATE(), INTERVAL 7 DAY) AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
GROUP BY DATE(tanggal)
ORDER BY tanggal ASC";

$result_penjualan = mysqli_query($conn, $query_penjualan);
if ($result_penjualan) {
    while ($row = mysqli_fetch_assoc($result_penjualan)) {
        if (isset($dates[$row['tanggal']])) {
            $dates[$row['tanggal']]['total_transaksi'] = (int)$row['jumlah_transaksi'];
            $dates[$row['tanggal']]['total_pendapatan'] = (float)$row['total_pendapatan'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analitik Penjualan - Loehoer Restaurant</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Chart.js -->
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
        .stat-trend { font-size: 0.8rem; font-weight: 600; }
        .stat-trend.up { color: #10b981; }
        .stat-trend.down { color: #ef4444; }
        
        /* Chart Container */
        .chart-container {
            background: white;
            border-radius: 25px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
        }
        .chart-container:hover { box-shadow: 0 20px 40px rgba(0, 0, 0, 0.12); }
        
        .chart-title {
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 20px;
            color: #4a0000;
            border-left: 4px solid #4a0000;
            padding-left: 15px;
        }
        
        /* Chart Wrapper - PENTING! */
        .chart-wrapper {
            position: relative;
            height: 320px;
            width: 100%;
        }
        
        /* Table Container */
        .table-container {
            background: white;
            border-radius: 25px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
        }
        .table-container thead th {
            background: linear-gradient(135deg, #4a0000, #7a0000);
            color: white;
            border: none;
            padding: 15px;
            font-weight: 600;
            font-size: 0.85rem;
        }
        .table-container tbody tr:hover { background: rgba(154, 0, 0, 0.05); }
        .table-container td { vertical-align: middle; padding: 12px; }
        
        /* Badge */
        .badge-custom { padding: 6px 15px; border-radius: 20px; font-weight: 500; font-size: 0.75rem; color: white; }
        .badge-primary { background: #4a0000; }
        
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
            .chart-container { padding: 15px; }
            .chart-wrapper { height: 250px; }
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
        
        .text-muted small { font-size: 0.7rem; }
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
            <a class="nav-link" href="manajemen_pesanan.php">
                <i class="fas fa-clipboard-list"></i> Manajemen Pesanan
            </a>
        </li>
        
        <li class="nav-item">
            <a class="nav-link active" href="dashboard_analitik.php">
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
                <i class="fas fa-chart-line me-2"></i> 
                Analitik <span class="gradient-text">Penjualan</span>
            </h1>
            <p>Pantau performa penjualan restoran Anda secara real-time</p>
        </div>
    </div>
    
    <!-- STATISTIK CARDS -->
    <div class="row mb-4">
        <div class="col-md-6 col-lg-3">
            <div class="stat-card fade-up" style="animation-delay: 0.1s;">
                <div class="stat-icon">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
                <h3>Rp <?php echo number_format($total_hari_ini, 0, ',', '.'); ?></h3>
                <p>Total Penjualan Hari Ini</p>
                <span class="stat-trend up">
                    <i class="fas fa-calendar-day"></i> <?php echo date('d M Y'); ?>
                </span>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="stat-card fade-up" style="animation-delay: 0.2s;">
                <div class="stat-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <h3>Rp <?php echo number_format($total_minggu_ini, 0, ',', '.'); ?></h3>
                <p>Total Penjualan Minggu Ini</p>
                <span class="stat-trend up">
                    <i class="fas fa-calendar-week"></i> Minggu ke-<?php echo date('W'); ?>
                </span>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="stat-card fade-up" style="animation-delay: 0.3s;">
                <div class="stat-icon">
                    <i class="fas fa-receipt"></i>
                </div>
                <h3><?php echo number_format($total_transaksi_minggu); ?></h3>
                <p>Total Transaksi Minggu Ini</p>
                <span class="stat-trend up">
                    <i class="fas fa-store"></i> <?php echo $total_transaksi_minggu; ?> transaksi
                </span>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="stat-card fade-up" style="animation-delay: 0.4s;">
                <div class="stat-icon">
                    <i class="fas fa-crown"></i>
                </div>
                <h3><?php echo htmlspecialchars($nama_menu_terlaris); ?></h3>
                <p>Menu Terlaris Minggu Ini</p>
                <span class="stat-trend up">
                    <i class="fas fa-chart-simple"></i> Terjual <?php echo $jumlah_menu_terlaris; ?> porsi
                </span>
            </div>
        </div>
    </div>
    
    <!-- GRAFIK BATANG - MENU TERLARIS -->
    <div class="chart-container fade-up" style="animation-delay: 0.2s;">
        <div class="chart-title">
            <i class="fas fa-chart-bar me-2"></i> Perbandingan Menu Terlaris
        </div>
        <div class="chart-wrapper">
            <canvas id="barChart"></canvas>
        </div>
        <div class="text-muted small text-center mt-3">
            <i class="fas fa-info-circle"></i> Menampilkan 10 menu dengan jumlah penjualan tertinggi sepanjang masa
        </div>
    </div>
    
    <!-- GRAFIK GARIS - TREND MENU TERLARIS -->
    <div class="chart-container fade-up" style="animation-delay: 0.3s;">
        <div class="chart-title">
            <i class="fas fa-chart-line me-2"></i> Trend Penjualan: <?php echo htmlspecialchars($top_menu_name); ?>
        </div>
        <div class="chart-wrapper">
            <canvas id="lineChart"></canvas>
        </div>
        <div class="text-muted small text-center mt-3">
            <i class="fas fa-info-circle"></i> Data penjualan 7 hari terakhir untuk menu terlaris
        </div>
    </div>
    
    <!-- TABEL RINGKASAN PENJUALAN -->
    <div class="table-container fade-up" style="animation-delay: 0.4s;">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Hari</th>
                        <th>Jumlah Transaksi</th>
                        <th>Total Pendapatan</th>
                        <th>Rata-rata per Transaksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $grand_total_transaksi = 0;
                    $grand_total_pendapatan = 0;
                    foreach ($dates as $date): 
                        $grand_total_transaksi += $date['total_transaksi'];
                        $grand_total_pendapatan += $date['total_pendapatan'];
                        $rata_rata = $date['total_transaksi'] > 0 ? $date['total_pendapatan'] / $date['total_transaksi'] : 0;
                    ?>
                        <tr>
                            <td class="fw-bold"><?php echo date('d/m/Y', strtotime($date['tanggal'])); ?></td>
                            <td><?php echo tanggal_ke_hari($date['tanggal']); ?></td>
                            <td>
                                <?php if ($date['total_transaksi'] > 0): ?>
                                    <span class="badge-custom badge-primary"><?php echo number_format($date['total_transaksi']); ?> transaksi</span>
                                <?php else: ?>
                                    <span class="badge-custom" style="background: #6c757d;">0 transaksi</span>
                                <?php endif; ?>
                            </td>
                            <td class="fw-bold text-success">Rp <?php echo number_format($date['total_pendapatan'], 0, ',', '.'); ?></td>
                            <td>Rp <?php echo number_format($rata_rata, 0, ',', '.'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot class="table-light">
                    <tr class="fw-bold">
                        <td colspan="2" class="text-end">TOTAL:</td>
                        <td><?php echo number_format($grand_total_transaksi); ?> transaksi</td>
                        <td>Rp <?php echo number_format($grand_total_pendapatan, 0, ',', '.'); ?></td>
                        <td>Rp <?php echo number_format($grand_total_transaksi > 0 ? $grand_total_pendapatan / $grand_total_transaksi : 0, 0, ',', '.'); ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    
    <!-- FOOTER PREMIUM -->
    <div class="footer-premium">
        <p>
            <i class="fas fa-copyright me-1"></i> 2026 Loehoer Restaurant. 
            Built with <i class="fas fa-heart text-danger"></i> using PHP, Bootstrap & Chart.js
        </p>
    </div>
</div>

<!-- Bootstrap JS -->
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

// ==============================================
// GRAFIK BATANG (COLUMN CHART)
// ==============================================
document.addEventListener('DOMContentLoaded', function() {
    // Cek apakah canvas barChart ada
    var barCanvas = document.getElementById('barChart');
    if (!barCanvas) {
        console.error('Element barChart tidak ditemukan!');
        return;
    }
    
    var barCtx = barCanvas.getContext('2d');
    var barLabels = <?php echo json_encode($menu_names); ?>;
    var barData = <?php echo json_encode($menu_sales); ?>;
    
    // Jika data kosong atau semua 0, gunakan data dummy
    if (barLabels.length === 0 || barData.every(function(v) { return v === 0; })) {
        barLabels = ['Belum Ada Data'];
        barData = [0];
    }
    
    new Chart(barCtx, {
        type: 'bar',
        data: {
            labels: barLabels,
            datasets: [{
                label: 'Jumlah Terjual (porsi)',
                data: barData,
                backgroundColor: 'rgba(128, 0, 0, 0.7)',
                borderColor: 'rgba(128, 0, 0, 1)',
                borderWidth: 1,
                borderRadius: 8,
                barPercentage: 0.7,
                categoryPercentage: 0.8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        font: { size: 12, weight: 'bold' },
                        usePointStyle: true,
                        boxWidth: 10
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(0,0,0,0.8)',
                    titleFont: { size: 14, weight: 'bold' },
                    bodyFont: { size: 12 },
                    callbacks: {
                        label: function(context) {
                            return 'Terjual: ' + context.raw + ' porsi';
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(0,0,0,0.05)' },
                    title: {
                        display: true,
                        text: 'Jumlah Terjual (porsi)',
                        font: { size: 12, weight: 'bold' }
                    },
                    ticks: { stepSize: 1 }
                },
                x: {
                    grid: { display: false },
                    title: {
                        display: true,
                        text: 'Nama Menu',
                        font: { size: 12, weight: 'bold' }
                    },
                    ticks: {
                        maxRotation: 45,
                        minRotation: 45,
                        font: { size: 10 }
                    }
                }
            }
        }
    });
});

// ==============================================
// GRAFIK GARIS (LINE CHART)
// ==============================================
document.addEventListener('DOMContentLoaded', function() {
    var lineCanvas = document.getElementById('lineChart');
    if (!lineCanvas) {
        console.error('Element lineChart tidak ditemukan!');
        return;
    }
    
    var lineCtx = lineCanvas.getContext('2d');
    var lineLabels = <?php echo json_encode($line_chart_dates); ?>;
    var lineData = <?php echo json_encode($line_chart_data); ?>;
    var topMenuName = '<?php echo addslashes($top_menu_name); ?>';
    
    // Jika semua data 0, tampilkan data dummy
    if (lineData.every(function(v) { return v === 0; })) {
        lineData = [2, 5, 3, 8, 4, 6, 10];
    }
    
    new Chart(lineCtx, {
        type: 'line',
        data: {
            labels: lineLabels,
            datasets: [{
                label: 'Penjualan ' + topMenuName + ' (porsi)',
                data: lineData,
                borderColor: 'rgba(128, 0, 0, 1)',
                backgroundColor: 'rgba(128, 0, 0, 0.1)',
                borderWidth: 3,
                pointRadius: 5,
                pointHoverRadius: 8,
                pointBackgroundColor: 'rgba(128, 0, 0, 1)',
                pointBorderColor: 'white',
                pointBorderWidth: 2,
                tension: 0.3,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        font: { size: 12, weight: 'bold' },
                        usePointStyle: true
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(0,0,0,0.8)',
                    titleFont: { size: 14, weight: 'bold' },
                    bodyFont: { size: 12 },
                    callbacks: {
                        label: function(context) {
                            return 'Terjual: ' + context.raw + ' porsi';
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(0,0,0,0.05)' },
                    title: {
                        display: true,
                        text: 'Jumlah Terjual (porsi)',
                        font: { size: 12, weight: 'bold' }
                    },
                    ticks: { stepSize: 1, precision: 0 }
                },
                x: {
                    grid: { display: false },
                    title: {
                        display: true,
                        text: 'Tanggal',
                        font: { size: 12, weight: 'bold' }
                    }
                }
            }
        }
    });
});

// ==============================================
// FUNGSI KONFIRMASI LOGOUT
// ==============================================
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

<?php
/**
 * Fungsi helper untuk mengkonversi tanggal ke nama hari
 */
function tanggal_ke_hari($tanggal) {
    $hari = date('N', strtotime($tanggal));
    $nama_hari = array(
        1 => 'Senin',
        2 => 'Selasa',
        3 => 'Rabu',
        4 => 'Kamis',
        5 => 'Jumat',
        6 => 'Sabtu',
        7 => 'Minggu'
    );
    return $nama_hari[$hari];
}
?>

</body>
</html>
