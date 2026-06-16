<?php
/**
 * get_laporan.php - API untuk mengambil data laporan penjualan
 * Dipanggil oleh laporan.php via AJAX
 */

require_once 'database.php';

header('Content-Type: application/json');


$dari = isset($_GET['dari']) ? $_GET['dari'] : date('Y-m-01');
$sampai = isset($_GET['sampai']) ? $_GET['sampai'] : date('Y-m-d');

// Validasi tanggal
$dari = mysqli_real_escape_string($conn, $dari);
$sampai = mysqli_real_escape_string($conn, $sampai);

// Query untuk mengambil data penjualan per tanggal
$query = "SELECT 
            DATE(tanggal) as tanggal,
            COUNT(*) as jumlah_transaksi,
            SUM(total_harga) as total_pendapatan
          FROM transaksi 
          WHERE tanggal BETWEEN '$dari' AND '$sampai'
          GROUP BY DATE(tanggal)
          ORDER BY tanggal ASC";

$result = mysqli_query($conn, $query);

$data = [];
while ($row = mysqli_fetch_assoc($result)) {
    $data[] = [
        'tanggal' => date('d/m/Y', strtotime($row['tanggal'])),
        'jumlah_transaksi' => (int)$row['jumlah_transaksi'],
        'total_pendapatan' => (float)$row['total_pendapatan'],
        'rata_rata' => $row['jumlah_transaksi'] > 0 ? $row['total_pendapatan'] / $row['jumlah_transaksi'] : 0
    ];
}

// Jika tidak ada data, tetap kirim array kosong
echo json_encode($data);
?>
