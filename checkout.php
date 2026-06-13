<?php
/**
 * checkout.php - Proses checkout pesanan
 */

session_start();
require_once 'database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['keranjang']) || count($_SESSION['keranjang']) == 0) {
    echo json_encode(['success' => false, 'message' => 'Keranjang kosong']);
    exit();
}

$nama_pemesan = mysqli_real_escape_string($conn, $_POST['nama_pemesan']);
$no_meja = mysqli_real_escape_string($conn, $_POST['no_meja']);
$catatan = mysqli_real_escape_string($conn, $_POST['catatan']);

// Generate nomor pesanan unik
$no_pesanan = 'INV' . date('Ymd') . rand(1000, 9999);

// Hitung total
$total = 0;
foreach ($_SESSION['keranjang'] as $item) {
    $total += $item['harga'] * $item['qty'];
}

// Simpan ke tabel pesanan
$query = "INSERT INTO pesanan (no_pesanan, nama_pemesan, no_meja, total_harga, catatan, status) 
          VALUES ('$no_pesanan', '$nama_pemesan', '$no_meja', '$total', '$catatan', 'pending')";

if (mysqli_query($conn, $query)) {
    $id_pesanan = mysqli_insert_id($conn);
    
    // Simpan detail pesanan
    foreach ($_SESSION['keranjang'] as $item) {
        $id_menu = $item['id'];
        $qty = $item['qty'];
        $harga = $item['harga'];
        
        $query_detail = "INSERT INTO detail_pesanan (id_pesanan, id_menu, qty, harga) 
                         VALUES ('$id_pesanan', '$id_menu', '$qty', '$harga')";
        mysqli_query($conn, $query_detail);
    }
    
    // Kosongkan keranjang
    $_SESSION['keranjang'] = [];
    
    echo json_encode(['success' => true, 'no_pesanan' => $no_pesanan]);
} else {
    echo json_encode(['success' => false, 'message' => 'Gagal menyimpan pesanan']);
}
?>