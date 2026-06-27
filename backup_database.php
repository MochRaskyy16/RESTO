<?php
/**
 * backup_database.php - Proses backup database
 * Menghasilkan file SQL backup
 */

session_start();

// Cek login admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

require_once 'database.php';

// Buat folder backup jika belum ada
$backup_dir = 'backup/';
if (!file_exists($backup_dir)) {
    mkdir($backup_dir, 0777, true);
}

// Nama file backup
$filename = $backup_dir . 'backup_' . date('Ymd_His') . '.sql';

// Ambil semua tabel
$tables = array();
$result = mysqli_query($conn, "SHOW TABLES");
while ($row = mysqli_fetch_array($result)) {
    $tables[] = $row[0];
}

// Mulai konten SQL
$sql = "-- =============================================\n";
$sql .= "-- BACKUP DATABASE: menu_resto\n";
$sql .= "-- TANGGAL: " . date('Y-m-d H:i:s') . "\n";
$sql .= "-- =============================================\n\n";
$sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

// Loop setiap tabel
foreach ($tables as $table) {
    // Drop table
    $sql .= "DROP TABLE IF EXISTS `$table`;\n";
    
    // Create table
    $create = mysqli_query($conn, "SHOW CREATE TABLE $table");
    $row = mysqli_fetch_array($create);
    $sql .= $row[1] . ";\n\n";
    
    // Insert data
    $data = mysqli_query($conn, "SELECT * FROM $table");
    if (mysqli_num_rows($data) > 0) {
        $sql .= "INSERT INTO `$table` VALUES\n";
        $rows = array();
        while ($row_data = mysqli_fetch_assoc($data)) {
            $values = array();
            foreach ($row_data as $value) {
                $values[] = $value === null ? 'NULL' : "'" . mysqli_real_escape_string($conn, $value) . "'";
            }
            $rows[] = "(" . implode(",", $values) . ")";
        }
        $sql .= implode(",\n", $rows) . ";\n\n";
    }
}

$sql .= "SET FOREIGN_KEY_CHECKS=1;\n";

// Simpan ke file
file_put_contents($filename, $sql);

// Download file
header('Content-Type: application/sql');
header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
header('Content-Length: ' . filesize($filename));
readfile($filename);

// Hapus file setelah download (opsional)
// unlink($filename);

exit();
?>
