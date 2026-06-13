<?php
// Mulai session
session_start();

// Cek apakah admin sudah login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    // Jika belum login, redirect ke halaman login
    header("Location: login.php");
    exit();
}

// Jika sudah login, lanjutkan kode berikutnya...
require_once 'database.php';
// ... kode selanjutnya
/**
 * dashboard-premium.php - Dashboard Restoran Premium
 * Redesign modern dengan sidebar, hero banner, dan animasi
 */

require_once 'database.php';

// ==============================================
// CEK KONEKSI DATABASE
// ==============================================
if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

// ==============================================
// AMBIL DATA UNTUK STATISTIK
// ==============================================

// Total Menu
$query_total_menu = "SELECT COUNT(*) as total FROM menu";
$result_total_menu = mysqli_query($conn, $query_total_menu);
$total_menu = $result_total_menu ? mysqli_fetch_assoc($result_total_menu)['total'] : 0;

// Total Kategori (menghitung kategori unik dari tabel menu)
$query_total_kategori = "SELECT COUNT(DISTINCT kategori) as total FROM menu WHERE kategori IS NOT NULL AND kategori != ''";
$result_total_kategori = mysqli_query($conn, $query_total_kategori);
$total_kategori = $result_total_kategori ? mysqli_fetch_assoc($result_total_kategori)['total'] : 0;

// Menu Baru (7 Hari Terakhir) - cek apakah kolom created_at ada
$check_column = mysqli_query($conn, "SHOW COLUMNS FROM menu LIKE 'created_at'");
if (mysqli_num_rows($check_column) > 0) {
    $query_menu_baru = "SELECT COUNT(*) as total FROM menu WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
} else {
    // Jika tidak ada kolom created_at, gunakan ID terbaru (asumsi ID terbesar adalah menu terbaru)
    $query_menu_baru = "SELECT COUNT(*) as total FROM menu WHERE id > (SELECT IFNULL(MAX(id)-7, 0) FROM menu)";
}
$result_menu_baru = mysqli_query($conn, $query_menu_baru);
$menu_baru = $result_menu_baru ? mysqli_fetch_assoc($result_menu_baru)['total'] : 0;

// ==============================================
// TOTAL PENDAPATAN HARI INI
// ==============================================

// Cek apakah tabel pesanan ada
$check_pesanan = mysqli_query($conn, "SHOW TABLES LIKE 'pesanan'");
if (mysqli_num_rows($check_pesanan) > 0) {
    // Ambil dari tabel pesanan (pembeli online)
    $query_pendapatan = "SELECT COALESCE(SUM(total_harga), 0) as total 
                         FROM pesanan 
                         WHERE DATE(tanggal_pesan) = CURDATE() 
                         AND status != 'batal'";
    $result_pendapatan = mysqli_query($conn, $query_pendapatan);
    $pendapatan_hari_ini = $result_pendapatan ? mysqli_fetch_assoc($result_pendapatan)['total'] : 0;
} else {
    // Fallback ke tabel transaksi (jika ada)
    $check_transaksi = mysqli_query($conn, "SHOW TABLES LIKE 'transaksi'");
    if (mysqli_num_rows($check_transaksi) > 0) {
        $query_pendapatan = "SELECT COALESCE(SUM(total_harga), 0) as total FROM transaksi WHERE DATE(tanggal) = CURDATE()";
        $result_pendapatan = mysqli_query($conn, $query_pendapatan);
        $pendapatan_hari_ini = $result_pendapatan ? mysqli_fetch_assoc($result_pendapatan)['total'] : 0;
    } else {
        $pendapatan_hari_ini = 0;
    }
}

// ==============================================
// DATA UNTUK TABEL MENU
// ==============================================
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$kategori_filter = isset($_GET['kategori']) ? mysqli_real_escape_string($conn, $_GET['kategori']) : '';

$where_conditions = array();
if (!empty($search)) {
    $where_conditions[] = "nama_menu LIKE '%$search%'";
}
if (!empty($kategori_filter)) {
    $where_conditions[] = "kategori = '$kategori_filter'";
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";
$query = "SELECT * FROM menu $where_clause ORDER BY id DESC";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loehoer Restaurant - Premium Dashboard</title>
    
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
        /* PREMIUM DASHBOARD CSS - LENGKAP */
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
        
        /* Hero Banner */
        .hero-banner {
            background: linear-gradient(135deg, #4a0000 0%, #7a0000 50%, #9a0000 100%);
            border-radius: 25px;
            padding: 40px;
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
        }
        .hero-banner::before {
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
        .hero-banner h4 { font-size: 1rem; font-weight: 500; margin-bottom: 15px; opacity: 0.9; color: white; }
        .hero-banner h1 { font-size: 2rem; font-weight: 800; margin-bottom: 15px; color: white; }
        .gradient-text { background: linear-gradient(135deg, #FFD89B, #FFB347); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
        .hero-banner p { font-size: 0.95rem; opacity: 0.85; margin-bottom: 0; color: white; }
        
        /* Floating Food */
        .floating-food { position: relative; height: 100%; min-height: 250px; }
        .food-float { position: absolute; border-radius: 20px; animation: float 3s ease-in-out infinite; }
        .food-card-img {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 10px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        .food-card-img:hover { transform: scale(1.05); box-shadow: 0 15px 40px rgba(0, 0, 0, 0.3); }
        .food-card-img img { width: 60px; height: 60px; object-fit: cover; border-radius: 15px; }
        .food-card-img span { font-size: 0.7rem; font-weight: 600; color: #4a0000; margin-top: 8px; }
        
        .food-1 { width: 110px; height: 110px; top: 5%; right: 20%; animation-delay: 0s; z-index: 5; }
        .food-2 { width: 95px; height: 95px; bottom: 15%; right: 5%; animation-delay: 0.3s; z-index: 4; }
        .food-3 { width: 85px; height: 85px; top: 45%; right: 35%; animation-delay: 0.6s; z-index: 3; }
        .food-4 { width: 100px; height: 100px; bottom: 25%; right: 45%; animation-delay: 0.9s; z-index: 2; }
        .food-5 { width: 90px; height: 90px; top: 20%; right: 55%; animation-delay: 1.2s; z-index: 6; }
        
        @keyframes float { 0%, 100% { transform: translateY(0px); } 50% { transform: translateY(-15px); } }
        @keyframes fadeUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
        .fade-up { animation: fadeUp 0.8s ease forwards; }
        
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
        
        /* Search Section */
        .search-section {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
        }
        .search-input-group { position: relative; }
        .search-input-group i {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: #9a0000;
            font-size: 1rem;
        }
        .search-input-group input {
            padding-left: 45px;
            height: 50px;
            border-radius: 15px;
            border: 1px solid #e5e7eb;
            font-family: 'Poppins', sans-serif;
        }
        .search-input-group input:focus {
            border-color: #9a0000;
            box-shadow: 0 0 0 3px rgba(154, 0, 0, 0.1);
        }
        .btn-premium {
            background: linear-gradient(135deg, #4a0000, #9a0000);
            color: white;
            border: none;
            border-radius: 15px;
            padding: 12px 30px;
            font-weight: 600;
            transition: all 0.3s ease;
            height: 50px;
        }
        .btn-premium:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(74, 0, 0, 0.3); color: white; }
        
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
        
        .food-img {
            width: 70px;
            height: 70px;
            border-radius: 15px;
            object-fit: cover;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        .food-img:hover { transform: scale(1.1); box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2); }
        
        /* Badge Kategori */
        .badge-custom { padding: 6px 15px; border-radius: 20px; font-weight: 500; font-size: 0.75rem; color: white; }
        .badge-makanan { background: #ef4444; }
        .badge-minuman { background: #06b6d4; }
        .badge-dessert { background: #a855f7; }
        .badge-snack { background: #f59e0b; }
        
        /* Action Buttons */
        .btn-action {
            padding: 8px 15px;
            border-radius: 10px;
            font-size: 0.8rem;
            font-weight: 500;
            transition: all 0.3s ease;
            margin: 0 3px;
            border: none;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        .btn-edit { background: #facc15; color: #1a1a1a; }
        .btn-edit:hover { background: #eab308; transform: translateY(-2px); color: #1a1a1a; }
        .btn-delete { background: #ef4444; color: white; }
        .btn-delete:hover { background: #dc2626; transform: translateY(-2px); color: white; }
        
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
            .hero-banner h1 { font-size: 1.5rem; }
            .floating-food { display: none; }
            .stat-card h3 { font-size: 1.3rem; }
            .food-img { width: 45px; height: 45px; }
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
        
        .slide-in {
            animation: slideIn 0.6s ease forwards;
        }
        @keyframes slideIn {
            from { opacity: 0; transform: translateX(-30px); }
            to { opacity: 1; transform: translateX(0); }
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
            <a class="nav-link active" href="dashboard-premium.php">
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
            <a class="nav-link" href="laporan.php">
                <i class="fas fa-file-alt"></i> Laporan
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="pengaturan.php">
                <i class="fas fa-cog"></i> Pengaturan
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
    
    <!-- HERO BANNER PREMIUM -->
    <div class="hero-banner">
        <div class="row align-items-center">
            <div class="col-lg-7">
                <div class="hero-content">
                    <h4 class="fade-up">
                        <i class="fas fa-hand-wave me-2"></i> Selamat Datang Admin 👋
                    </h4>
                    <h1 class="fade-up" style="animation-delay: 0.1s;">
                        Kelola Menu Restoran <span class="gradient-text">Dengan Mudah & Cepat</span>
                    </h1>
                    <p class="fade-up" style="animation-delay: 0.2s;">
                        Kelola semua menu, kategori, dan pantau penjualan restoran Anda 
                        dalam satu dashboard modern yang profesional.
                    </p>
                </div>
            </div>
            <div class="col-lg-5">
               <!-- Floating Food - Ukuran Besar -->
<!-- Floating Food - Ukuran Besar dengan posisi acak kiri & kanan -->
<div class="floating-food">
    
    <!-- ========== SISI KANAN (YANG SUDAH ADA) ========== -->
    
    <!-- Steak - Kanan Atas -->
    <div class="food-float food-1" style="width: 165px; height: 165px; top: -5%; right: 11%; position: absolute; animation: float 3s ease-in-out infinite; animation-delay: 0s; z-index: 5;">
        <div class="food-card-img" style="background: rgba(255,255,255,0.95); border-radius: 20px; padding: 15px; text-align: center; box-shadow: 0 10px 30px rgba(0,0,0,0.2); width: 100%; height: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center;">
            <img src="assets/img/steak.jpg" alt="Steak" style="width: 135px; height: 135px; object-fit: cover; border-radius: 15px;">
            <span style="font-size: 1rem; font-weight: 600; color: #4a0000; margin-top: 10px;">Steak</span>
        </div>
    </div>
    
    <!-- Pizza - Kanan Bawah -->
    <div class="food-float food-2" style="width: 165px; height: 165px; bottom: -13%; right: -3%; position: absolute; animation: float 3s ease-in-out infinite; animation-delay: 0.3s; z-index: 4;">
        <div class="food-card-img" style="background: rgba(255,255,255,0.95); border-radius: 20px; padding: 15px; text-align: center; box-shadow: 0 10px 30px rgba(0,0,0,0.2); width: 100%; height: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center;">
            <img src="assets/img/pizza.jpg" alt="Pizza" style="width: 135px; height: 135px; object-fit: cover; border-radius: 15px;">
            <span style="font-size: 0.95rem; font-weight: 600; color: #4a0000; margin-top: 10px;">Pizza</span>
        </div>
    </div>
    
    <!-- Minuman - Kanan Tengah -->
    <div class="food-float food-3" style="width: 165px; height: 165px; top: -12%; right: 43%; position: absolute; animation: float 3s ease-in-out infinite; animation-delay: 0.6s; z-index: 3;">
        <div class="food-card-img" style="background: rgba(255,255,255,0.95); border-radius: 20px; padding: 12px; text-align: center; box-shadow: 0 10px 30px rgba(0,0,0,0.2); width: 100%; height: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center;">
            <img src="assets/img/minuman.png" alt="Minuman" style="width: 135px; height: 135px; object-fit: cover; border-radius: 15px;">
            <span style="font-size: 0.9rem; font-weight: 600; color: #4a0000; margin-top: 10px;">Minuman</span>
        </div>
    </div>
    
    <!-- Pasta - Kanan Tengah Bawah -->
    <div class="food-float food-4" style="width: 165px; height: 165px; top: 35%; right: 29%; position: absolute; animation: float 3s ease-in-out infinite; animation-delay: 0.9s; z-index: 2;">
        <div class="food-card-img" style="background: rgba(255,255,255,0.95); border-radius: 20px; padding: 15px; text-align: center; box-shadow: 0 10px 30px rgba(0,0,0,0.2); width: 100%; height: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center;">
            <img src="assets/img/pasta.png" alt="Pasta" style="width: 135px; height: 135px; object-fit: cover; border-radius: 15px;">
            <span style="font-size: 1rem; font-weight: 600; color: #4a0000; margin-top: 10px;">Pasta</span>
        </div>
    </div>
    
    <!-- Burger - Kanan Atas Tengah -->
    <div class="food-float food-5" style="width: 165px; height: 165px; top: 43%; left: 23%; position: absolute; animation: float 3s ease-in-out infinite; animation-delay: 1.2s; z-index: 6;">
        <div class="food-card-img" style="background: rgba(255,255,255,0.95); border-radius: 20px; padding: 13px; text-align: center; box-shadow: 0 10px 30px rgba(0,0,0,0.2); width: 100%; height: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center;">
            <img src="assets/img/burger.jpeg" alt="Burger" style="width: 135px; height: 135px; object-fit: cover; border-radius: 15px;">
            <span style="font-size: 0.95rem; font-weight: 600; color: #4a0000; margin-top: 10px;">Burger</span>
        </div>
    </div>
    
    <!-- ========== SISI KIRI (TAMBAHAN BARU) ========== -->
    
    <!-- Sate - Kiri Atas -->
    <div class="food-float" style="width: 165px; height: 165px; top: -8%; left: -16%; position: absolute; animation: float 3.5s ease-in-out infinite; animation-delay: 0.2s; z-index: 5;">
        <div class="food-card-img" style="background: rgba(255,255,255,0.95); border-radius: 20px; padding: 20px; text-align: center; box-shadow: 0 10px 30px rgba(0,0,0,0.2); width: 100%; height: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center;">
            <img src="assets/img/soto.png" alt="Sate" style="width: 135px; height: 135px; object-fit: cover; border-radius: 25px;">
            <span style="font-size: 0.9rem; font-weight: 600; color: #4a0000; margin-top: 8px;">Soto</span>
        </div>
    </div>
    
    <!-- Nasi Goreng - Kiri Tengah -->
    <div class="food-float" style="width: 180px; height: 180px; top: -9%; left: -45%; position: absolute; animation: float 2.8s ease-in-out infinite; animation-delay: 0.7s; z-index: 4;">
        <div class="food-card-img" style="background: rgba(255,255,255,0.95); border-radius: 20px; padding: 14px; text-align: center; box-shadow: 0 10px 30px rgba(0,0,0,0.2); width: 100%; height: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center;">
            <img src="assets/img/dessert.png" alt="Nasi Goreng" style="width: 135px; height: 135px; object-fit: cover; border-radius: 15px;">
            <span style="font-size: 0.95rem; font-weight: 600; color: #4a0000; margin-top: 8px;">Dessert</span>
        </div>
    </div>
    
    <!-- Mie Ayam - Kiri Bawah -->
    <div class="food-float" style="width: 165px; height: 165px; bottom: -15%; left: -2%; position: absolute; animation: float 3.2s ease-in-out infinite; animation-delay: 1.1s; z-index: 3;">
        <div class="food-card-img" style="background: rgba(255,255,255,0.95); border-radius: 20px; padding: 12px; text-align: center; box-shadow: 0 10px 30px rgba(0,0,0,0.2); width: 100%; height: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center;">
            <img src="assets/img/Nasgor.png" alt="Mie Ayam" style="width: 135px; height: 135px; object-fit: cover; border-radius: 15px;">
            <span style="font-size: 0.9rem; font-weight: 600; color: #4a0000; margin-top: 8px;">Nasi Goreng</span>
        </div>
    </div>
    
    <!-- Es Teh - Kiri Atas Tengah -->
    <div class="food-float" style="width: 165px; height: 165px; top: -10%; left: 13%; position: absolute; animation: float 2.5s ease-in-out infinite; animation-delay: 0.4s; z-index: 6;">
        <div class="food-card-img" style="background: rgba(255,255,255,0.95); border-radius: 20px; padding: 10px; text-align: center; box-shadow: 0 10px 30px rgba(0,0,0,0.2); width: 100%; height: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center;">
            <img src="assets/img/cumi.jpg" alt="Cumi " style="width: 130px; height: 130px; object-fit: cover; border-radius: 15px;">
            <span style="font-size: 0.85rem; font-weight: 600; color: #4a0000; margin-top: 8px;">Cumi Asam Manis</span>
        </div>
    </div>
    
    <!-- Kentang Goreng - Kiri Pojok -->
    <div class="food-float" style="width: 165px; height: 165px; top: 50%; left: -28%; position: absolute; animation: float 2.9s ease-in-out infinite; animation-delay: 0.9s; z-index: 1;">
        <div class="food-card-img" style="background: rgba(255,255,255,0.95); border-radius: 20px; padding: 12px; text-align: center; box-shadow: 0 10px 30px rgba(0,0,0,0.2); width: 100%; height: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center;">
            <img src="assets/img/kentang.png" alt="Kentang Goreng" style="width: 135px; height: 135px; object-fit: cover; border-radius: 15px;">
            <span style="font-size: 0.85rem; font-weight: 600; color: #4a0000; margin-top: 8px;">Kentang</span>
        </div>
    </div>
</div>
            </div>
        </div>
    </div>
    
    <!-- STATISTIK CARDS -->
    <div class="row mb-4">
        <div class="col-md-6 col-lg-3">
            <div class="stat-card fade-up" style="animation-delay: 0.1s;">
                <div class="stat-icon">
                    <i class="fas fa-hamburger"></i>
                </div>
                <h3><?php echo number_format($total_menu); ?></h3>
                <p>Total Menu</p>
                <span class="stat-trend up">
                    <i class="fas fa-arrow-up"></i> dari database
                </span>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="stat-card fade-up" style="animation-delay: 0.2s;">
                <div class="stat-icon">
                    <i class="fas fa-tags"></i>
                </div>
                <h3><?php echo number_format($total_kategori); ?></h3>
                <p>Total Kategori</p>
                <span class="stat-trend">
                    <i class="fas fa-minus"></i> dari database
                </span>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="stat-card fade-up" style="animation-delay: 0.3s;">
                <div class="stat-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <h3><?php echo number_format($menu_baru); ?></h3>
                <p>Menu Baru (7 Hari)</p>
                <span class="stat-trend up">
                    <i class="fas fa-arrow-up"></i> dari database
                </span>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="stat-card fade-up" style="animation-delay: 0.4s;">
                <div class="stat-icon">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
                <h3>Rp <?php echo number_format($pendapatan_hari_ini, 0, ',', '.'); ?></h3>
                <p>Total Pendapatan Hari Ini</p>
                <span class="stat-trend up">
                    <i class="fas fa-arrow-up"></i> dari database
                </span>
            </div>
        </div>
    </div>
    
    <!-- SEARCH SECTION -->
    <div class="search-section slide-in">
        <form method="GET" action="dashboard-premium.php">
            <div class="row g-3">
                <div class="col-md-5">
                    <div class="search-input-group">
                        <i class="fas fa-search"></i>
                        <input type="text" name="search" class="form-control" 
                               placeholder="Cari berdasarkan nama menu..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                </div>
                <div class="col-md-5">
                    <select name="kategori" class="form-select">
                        <option value="">Semua Kategori</option>
                        <option value="Makanan" <?php echo $kategori_filter == 'Makanan' ? 'selected' : ''; ?>>Makanan</option>
                        <option value="Minuman" <?php echo $kategori_filter == 'Minuman' ? 'selected' : ''; ?>>Minuman</option>
                        <option value="Snack" <?php echo $kategori_filter == 'Snack' ? 'selected' : ''; ?>>Snack</option>
                        <option value="Dessert" <?php echo $kategori_filter == 'Dessert' ? 'selected' : ''; ?>>Dessert</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-premium w-100">
                        <i class="fas fa-search me-2"></i> Cari
                    </button>
                </div>
            </div>
        </form>
    </div>
    
    <!-- TABLE MENU PREMIUM -->
    <div class="table-premium">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Foto</th>
                        <th>Nama Menu</th>
                        <th>Kategori</th>
                        <th>Harga</th>
                        <th>Deskripsi</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && mysqli_num_rows($result) > 0): ?>
                        <?php $no = 1; while ($row = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td>
                                    <?php if (!empty($row['gambar']) && file_exists('uploads/' . $row['gambar'])): ?>
                                        <img src="uploads/<?php echo $row['gambar']; ?>" class="food-img" alt="Foto Menu">
                                    <?php else: ?>
                                        <div style="width:70px;height:70px;background:#f0f0f0;border-radius:15px;display:flex;align-items:center;justify-content:center;">
                                            <i class="fas fa-utensils" style="font-size:30px;color:#ccc;"></i>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="fw-bold"><?php echo htmlspecialchars($row['nama_menu']); ?></td>
                                <td>
                                    <?php
                                    $badge_class = 'badge-makanan';
                                    $kategori_lower = strtolower($row['kategori']);
                                    if ($kategori_lower == 'minuman') $badge_class = 'badge-minuman';
                                    elseif ($kategori_lower == 'snack') $badge_class = 'badge-snack';
                                    elseif ($kategori_lower == 'dessert') $badge_class = 'badge-dessert';
                                    ?>
                                    <span class="badge-custom <?php echo $badge_class; ?>">
                                        <?php echo htmlspecialchars($row['kategori']); ?>
                                    </span>
                                </td>
                                <td>Rp <?php echo number_format($row['harga'], 0, ',', '.'); ?></td>
                                <td><?php echo htmlspecialchars(substr($row['deskripsi'] ?? '', 0, 50)); ?>...</td>
                                <td>
                                    <a href="edit.php?id=<?php echo $row['id']; ?>" class="btn-action btn-edit">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <button onclick="confirmDelete(<?php echo $row['id']; ?>, '<?php echo addslashes($row['nama_menu']); ?>')" 
                                            class="btn-action btn-delete">
                                        <i class="fas fa-trash"></i> Hapus
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <i class="fas fa-database fa-3x mb-3 d-block text-muted"></i>
                                <h5>Tidak ada data menu</h5>
                                <p>Silakan tambah menu baru</p>
                                <a href="tambah.php" class="btn btn-premium mt-2">
                                    <i class="fas fa-plus"></i> Tambah Menu
                                </a>
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

// Delete confirmation
function confirmDelete(id, nama) {
    Swal.fire({
        title: 'Hapus Menu?',
        text: 'Apakah Anda yakin ingin menghapus "' + nama + '"?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'hapus.php?id=' + id;
        }
    });
}

// Success message handler
<?php if (isset($_GET['status'])): ?>
    var status = '<?php echo $_GET['status']; ?>';
    var message = '';
    if (status === 'tambah_success') message = 'Menu berhasil ditambahkan';
    else if (status === 'edit_success') message = 'Menu berhasil diupdate';
    else if (status === 'hapus_success') message = 'Menu berhasil dihapus';
    
    if (message) {
        Swal.fire({
            title: 'Berhasil!',
            text: message,
            icon: 'success',
            confirmButtonColor: '#4a0000'
        });
        // Hapus parameter URL
        window.history.replaceState({}, document.title, window.location.pathname);
    }
<?php endif; ?>

// ============================================
// FUNGSI KONFIRMASI LOGOUT
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
            Swal.fire({
                title: 'Logout...',
                text: 'Sedang memproses logout',
                icon: 'info',
                showConfirmButton: false,
                timer: 1000
            });
            
            setTimeout(() => {
                window.location.href = 'logout.php';
            }, 1000);
        }
    });
}

// Fungsi lainnya (toggle sidebar, dll) bisa ditambahkan di sini juga
function toggleSidebar() {
    var sidebar = document.getElementById('sidebar');
    if (sidebar) sidebar.classList.toggle('show');
}
</script>

</body>
</html>
</body>
</html>