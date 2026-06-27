<?php
/**
 * backup_uploads.php - Backup folder uploads (gambar menu)
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

$filename = $backup_dir . 'uploads_' . date('Ymd_His') . '.zip';

// Buat file ZIP
$zip = new ZipArchive();
if ($zip->open($filename, ZipArchive::CREATE) !== TRUE) {
    die("Gagal membuat file ZIP");
}

// Folder uploads
$uploads_dir = 'uploads/';
if (file_exists($uploads_dir)) {
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($uploads_dir),
        RecursiveIteratorIterator::LEAVES_ONLY
    );
    
    foreach ($files as $name => $file) {
        if (!$file->isDir()) {
            $filePath = $file->getRealPath();
            $relativePath = substr($filePath, strlen($uploads_dir));
            $zip->addFile($filePath, $relativePath);
        }
    }
}

$zip->close();

// Download file
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
header('Content-Length: ' . filesize($filename));
readfile($filename);

exit();
?>
