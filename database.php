<?php
/**
 * database.php - File koneksi database
 * Menggunakan MySQLi untuk koneksi ke database
 */

$host = 'localhost';      // Server database
$user = 'root';           // Username database (default root)
$password = '';           // Password database (kosong untuk XAMPP/Laragon)
$database = 'serba_resto'; // Nama database

// Membuat koneksi
$conn = mysqli_connect($host, $user, $password, $database);

// Cek koneksi
if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

// Set charset ke UTF-8
mysqli_set_charset($conn, "utf8");

// Catatan: File ini akan di-include di semua halaman CRUD
?>