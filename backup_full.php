<?php
/**
 * backup_full.php - Backup seluruh file project
 * Menghasilkan file ZIP
 */

session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

$backup_dir = 'backup/';
if (!file_exists($backup_dir)) {
    mkdir($backup_dir, 0777, true);
}

$filename = $backup_dir . 'full_project_' . date('Ymd_His') . '.zip';

// Buat file ZIP
$zip = new ZipArchive();
if ($zip->open($filename, ZipArchive::CREATE) !== TRUE) {
    die("Gagal membuat file ZIP");
}

// Folder yang akan di-backup
$folders_to_backup = array('.', 'assets', 'uploads');
$exclude = array('backup', '.vscode', '.git', 'node_modules');

function addFilesToZip($dir, $zip, $exclude) {
    $files = scandir($dir);
    foreach ($files as $file) {
        if ($file == '.' || $file == '..') continue;
        if (in_array($file, $exclude)) continue;
        
        $path = $dir . '/' . $file;
        if (is_dir($path)) {
            addFilesToZip($path, $zip, $exclude);
        } else {
            $relativePath = substr($path, 2); // Hapus './'
            $zip->addFile($path, $relativePath);
        }
    }
}

// Backup file PHP di root
addFilesToZip('.', $zip, $exclude);

$zip->close();

// Download file
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
header('Content-Length: ' . filesize($filename));
readfile($filename);

exit();
?>
