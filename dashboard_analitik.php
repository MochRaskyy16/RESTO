<?php
/**
 * dashboard_analitik.php - Halaman Dashboard Analitik Penjualan
 * Menampilkan statistik penjualan, grafik batang, grafik garis, dan tabel penjualan
 */

require_once 'database.php';

// ==============================================
// 1. AMBIL DATA STATISTIK RINGKASAN
// ==============================================

// Total Penjualan Hari Ini
$query_hari_ini = "SELECT COALESCE(SUM(total_harga), 0) as total FROM transaksi WHERE DATE(tanggal) = CURDATE()";
$result_hari_ini = mysqli_query($conn, $query_hari_ini);
$total_hari_ini = mysqli_fetch_assoc($result_hari_ini)['total'];

// Total Penjualan Minggu Ini
$query_minggu_ini = "SELECT COALESCE(SUM(total_harga), 0) as total FROM transaksi WHERE YEARWEEK(tanggal, 1) = YEARWEEK(CURDATE(), 1)";
$result_minggu_ini = mysqli_query($conn, $query_minggu_ini);
$total_minggu_ini = mysqli_fetch_assoc($result_minggu_ini)['total'];

// Total Transaksi Minggu Ini
$query_transaksi_minggu = "SELECT COUNT(*) as total FROM transaksi WHERE YEARWEEK(tanggal, 1) = YEARWEEK(CURDATE(), 1)";
$result_transaksi_minggu = mysqli_query($conn, $query_transaksi_minggu);
$total_transaksi_minggu = mysqli_fetch_assoc($result_transaksi_minggu)['total'];

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
// 2. AMBIL DATA PENJUALAN 1 MINGGU KEBELAKANG & 1 MINGGU KEDEPAN
// ==============================================

// Buat array tanggal untuk 14 hari (7 hari lalu sampai 7 hari ke depan)
$dates = [];
$current = strtotime('-7 days');
$end = strtotime('+7 days');

while ($current <= $end) {
    $dates[date('Y-m-d', $current)] = [
        'tanggal' => date('Y-m-d', $current),
        'tanggal_format' => date('d M Y', $current),
        'total_transaksi' => 0,
        'total_pendapatan' => 0
    ];
    $current = strtotime('+1 day', $current);
}

// Ambil data transaksi dari database
$query_penjualan = "SELECT 
    DATE(tanggal) as tanggal,
    COUNT(*) as jumlah_transaksi,
    SUM(total_harga) as total_pendapatan
FROM transaksi
WHERE tanggal BETWEEN DATE_SUB(CURDATE(), INTERVAL 7 DAY) AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
GROUP BY DATE(tanggal)
ORDER BY tanggal ASC";

$result_penjualan = mysqli_query($conn, $query_penjualan);
while ($row = mysqli_fetch_assoc($result_penjualan)) {
    if (isset($dates[$row['tanggal']])) {
        $dates[$row['tanggal']]['total_transaksi'] = (int)$row['jumlah_transaksi'];
        $dates[$row['tanggal']]['total_pendapatan'] = (float)$row['total_pendapatan'];
    }
}

// ==============================================
// 3. DATA UNTUK GRAFIK BATANG (MENU TERLARIS)
// ==============================================

$query_bar_chart = "SELECT 
    m.nama_menu, 
    COALESCE(SUM(dt.qty), 0) AS total_terjual
FROM menu m
LEFT JOIN detail_transaksi dt ON m.id = dt.id_menu
LEFT JOIN transaksi t ON dt.id_transaksi = t.id
GROUP BY m.id
ORDER BY total_terjual DESC
LIMIT 10"; // Ambil 10 menu terlaris

$result_bar_chart = mysqli_query($conn, $query_bar_chart);
$menu_names = [];
$menu_sales = [];

while ($row = mysqli_fetch_assoc($result_bar_chart)) {
    $menu_names[] = $row['nama_menu'];
    $menu_sales[] = (int)$row['total_terjual'];
}

// ==============================================
// 4. DATA UNTUK GRAFIK GARIS (TREND MENU TERLARIS)
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
$line_chart_dates = [];
$line_chart_sales = [];

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
    while ($row = mysqli_fetch_assoc($result_line_chart)) {
        if (isset($line_chart_sales[$row['tanggal']])) {
            $line_chart_sales[$row['tanggal']] = (int)$row['total_terjual'];
        }
    }
}

$line_chart_data = array_values($line_chart_sales);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Analitik Penjualan</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <!-- Custom CSS -->
    <style>
        :root {
            --primary-color: #800000;
            --primary-dark: #5c0000;
            --primary-light: #a52a2a;
            --secondary-color: #6c757d;
            --light-gray: #f8f9fa;
            --white: #ffffff;
            --shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.08);
            --shadow-hover: 0 1rem 2rem rgba(0, 0, 0, 0.12);
            --border-radius: 16px;
        }

        body {
            background-color: #f0f2f5;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        /* Navbar */
        .navbar {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            box-shadow: var(--shadow);
            padding: 1rem 0;
        }

        .navbar-brand {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--white) !important;
        }

        .navbar-brand i {
            margin-right: 10px;
        }

        .nav-link {
            color: rgba(255, 255, 255, 0.9) !important;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .nav-link:hover {
            color: var(--white) !important;
            transform: translateY(-2px);
        }

        .nav-link.active {
            background-color: rgba(255, 255, 255, 0.2);
            border-radius: 8px;
        }

        /* Card Statistik */
        .stat-card {
            border: none;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            transition: all 0.3s ease;
            overflow: hidden;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-hover);
        }

        .stat-card .card-body {
            padding: 1.5rem;
        }

        .stat-card h3 {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 0.25rem;
        }

        .stat-card p {
            margin-bottom: 0;
            color: var(--secondary-color);
        }

        .stat-icon {
            font-size: 2.5rem;
            opacity: 0.3;
            position: absolute;
            right: 1.5rem;
            top: 50%;
            transform: translateY(-50%);
        }

        .stat-card-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
            color: white;
        }

        .stat-card-primary p {
            color: rgba(255, 255, 255, 0.9);
        }

        .stat-card-success {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
        }

        .stat-card-info {
            background: linear-gradient(135deg, #17a2b8, #6f42c1);
            color: white;
        }

        .stat-card-warning {
            background: linear-gradient(135deg, #ffc107, #fd7e14);
            color: white;
        }

        /* Chart Container */
        .chart-container {
            background: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: var(--shadow);
        }

        .chart-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            color: var(--primary-color);
            border-left: 4px solid var(--primary-color);
            padding-left: 1rem;
        }

        /* Tabel */
        .table-container {
            background: white;
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--shadow);
        }

        .table thead th {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 1rem;
            font-weight: 600;
        }

        .table tbody tr:hover {
            background-color: rgba(128, 0, 0, 0.05);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .stat-card h3 {
                font-size: 1.5rem;
            }
            
            .chart-container {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg sticky-top">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <i class="fas fa-utensils"></i> RestoManager
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link" href="index.php">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="tambah.php">
                        <i class="fas fa-plus-circle"></i> Tambah Menu
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="dashboard_analitik.php">
                        <i class="fas fa-chart-line"></i> Analitik Penjualan
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <!-- ============================================== -->
    <!-- RINGKASAN STATISTIK - 4 CARD -->
    <!-- ============================================== -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card stat-card stat-card-primary">
                <div class="card-body position-relative">
                    <i class="fas fa-money-bill-wave stat-icon"></i>
                    <h3>Rp <?= number_format($total_hari_ini, 0, ',', '.') ?></h3>
                    <p>Total Penjualan Hari Ini</p>
                    <small><i class="fas fa-calendar-day"></i> <?= date('d M Y') ?></small>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card stat-card stat-card-success">
                <div class="card-body position-relative">
                    <i class="fas fa-chart-line stat-icon"></i>
                    <h3>Rp <?= number_format($total_minggu_ini, 0, ',', '.') ?></h3>
                    <p>Total Penjualan Minggu Ini</p>
                    <small><i class="fas fa-calendar-week"></i> Minggu ke-<?= date('W') ?></small>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card stat-card stat-card-info">
                <div class="card-body position-relative">
                    <i class="fas fa-receipt stat-icon"></i>
                    <h3><?= number_format($total_transaksi_minggu) ?></h3>
                    <p>Total Transaksi Minggu Ini</p>
                    <small><i class="fas fa-store"></i> <?= $total_transaksi_minggu ?> transaksi</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card stat-card stat-card-warning">
                <div class="card-body position-relative">
                    <i class="fas fa-crown stat-icon"></i>
                    <h3><?= htmlspecialchars($nama_menu_terlaris) ?></h3>
                    <p>Menu Terlaris Minggu Ini</p>
                    <small><i class="fas fa-chart-simple"></i> Terjual <?= $jumlah_menu_terlaris ?> porsi</small>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================== -->
    <!-- GRAFIK BATANG - MENU TERLARIS -->
    <!-- ============================================== -->
    <div class="chart-container">
        <div class="chart-title">
            <i class="fas fa-chart-bar me-2"></i> Perbandingan Menu Terlaris
        </div>
        <canvas id="barChart" height="100"></canvas>
        <div class="text-muted mt-3 small text-center">
            <i class="fas fa-info-circle"></i> Menampilkan 10 menu dengan jumlah penjualan tertinggi sepanjang masa
        </div>
    </div>

    <!-- ============================================== -->
    <!-- GRAFIK GARIS - TREND MENU TERLARIS -->
    <!-- ============================================== -->
    <div class="chart-container">
        <div class="chart-title">
            <i class="fas fa-chart-line me-2"></i> Trend Penjualan Menu Terlaris: <?= htmlspecialchars($top_menu_name) ?>
        </div>
        <canvas id="lineChart" height="100"></canvas>
        <div class="text-muted mt-3 small text-center">
            <i class="fas fa-info-circle"></i> Data penjualan 7 hari terakhir untuk menu terlaris
        </div>
    </div>

    <!-- ============================================== -->
    <!-- TABEL RINGKASAN PENJUALAN -->
    <!-- ============================================== -->
    <div class="table-container">
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
                            <td class="fw-bold"><?= date('d/m/Y', strtotime($date['tanggal'])) ?></td>
                            <td><?= tanggal_ke_hari($date['tanggal']) ?></td>
                            <td>
                                <?php if ($date['total_transaksi'] > 0): ?>
                                    <span class="badge bg-primary rounded-pill"><?= number_format($date['total_transaksi']) ?> transaksi</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary rounded-pill">0 transaksi</span>
                                <?php endif; ?>
                            </td>
                            <td class="fw-bold text-success">Rp <?= number_format($date['total_pendapatan'], 0, ',', '.') ?></td>
                            <td>Rp <?= number_format($rata_rata, 0, ',', '.') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot class="table-light">
                    <tr class="fw-bold">
                        <td colspan="2" class="text-end">TOTAL:</td>
                        <td><?= number_format($grand_total_transaksi) ?> transaksi</td>
                        <td>Rp <?= number_format($grand_total_pendapatan, 0, ',', '.') ?></td>
                        <td>Rp <?= number_format($grand_total_transaksi > 0 ? $grand_total_pendapatan / $grand_total_transaksi : 0, 0, ',', '.') ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
// ==============================================
// GRAFIK BATANG (COLUMN CHART)
// ==============================================
const barCtx = document.getElementById('barChart').getContext('2d');
new Chart(barCtx, {
    type: 'bar',
    data: {
        labels: <?= json_encode($menu_names) ?>,
        datasets: [{
            label: 'Jumlah Terjual (porsi)',
            data: <?= json_encode($menu_sales) ?>,
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
        maintainAspectRatio: true,
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
                        return `Terjual: ${context.raw} porsi`;
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

// ==============================================
// GRAFIK GARIS (LINE CHART)
// ==============================================
const lineCtx = document.getElementById('lineChart').getContext('2d');
new Chart(lineCtx, {
    type: 'line',
    data: {
        labels: <?= json_encode($line_chart_dates) ?>,
        datasets: [{
            label: 'Penjualan ' + <?= json_encode($top_menu_name) ?> + ' (porsi)',
            data: <?= json_encode($line_chart_data) ?>,
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
        maintainAspectRatio: true,
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
                        return `Terjual: ${context.raw} porsi`;
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
</script>

</body>
</html>

<?php
/**
 * Fungsi helper untuk mengkonversi tanggal ke nama hari
 */
function tanggal_ke_hari($tanggal) {
    $hari = date('N', strtotime($tanggal));
    $nama_hari = [
        1 => 'Senin',
        2 => 'Selasa',
        3 => 'Rabu',
        4 => 'Kamis',
        5 => 'Jumat',
        6 => 'Sabtu',
        7 => 'Minggu'
    ];
    return $nama_hari[$hari];
}
?>