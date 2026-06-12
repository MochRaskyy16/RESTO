<?php
/**
 * hapus.php - Menghapus data menu dari database
 */

require_once 'database.php';

// Ambil ID dari URL
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Ambil data gambar untuk dihapus
$query = "SELECT gambar FROM menu WHERE id = $id";
$result = mysqli_query($conn, $query);
$menu = mysqli_fetch_assoc($result);

if ($menu) {
    // Hapus file gambar jika ada
    if ($menu['gambar'] && file_exists('uploads/' . $menu['gambar'])) {
        unlink('uploads/' . $menu['gambar']);
    }
    
    // Hapus data dari database
    $query = "DELETE FROM menu WHERE id = $id";
    mysqli_query($conn, $query);
}

// Redirect ke halaman utama
header("Location: index.php?status=hapus_success");
exit();
?>