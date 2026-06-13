<?php
/**
 * tambah_keranjang.php - API untuk menambah item ke keranjang
 */

session_start();

if (!isset($_SESSION['keranjang'])) {
    $_SESSION['keranjang'] = [];
}

$id = $_POST['id'];
$nama = $_POST['nama'];
$harga = $_POST['harga'];

if (isset($_SESSION['keranjang'][$id])) {
    $_SESSION['keranjang'][$id]['qty']++;
} else {
    $_SESSION['keranjang'][$id] = [
        'id' => $id,
        'nama' => $nama,
        'harga' => $harga,
        'qty' => 1
    ];
}

echo json_encode(['success' => true]);
?>