<?php
/**
 * tambah.php - Halaman untuk menambahkan menu baru
 */

require_once 'database.php';

// Proses form submit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Ambil data dari form
    $nama_menu = mysqli_real_escape_string($conn, $_POST['nama_menu']);
    $kategori = mysqli_real_escape_string($conn, $_POST['kategori']);
    $harga = mysqli_real_escape_string($conn, $_POST['harga']);
    $deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi']);
    
    // Proses upload gambar
    $gambar = null;
    if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png'];
        $filename = $_FILES['gambar']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $file_size = $_FILES['gambar']['size'];
        
        // Validasi file
        if (in_array($ext, $allowed)) {
            if ($file_size <= 2 * 1024 * 1024) { // Max 2MB
                $new_filename = time() . '_' . uniqid() . '.' . $ext;
                $upload_path = 'uploads/' . $new_filename;
                
                if (move_uploaded_file($_FILES['gambar']['tmp_name'], $upload_path)) {
                    $gambar = $new_filename;
                }
            }
        }
    }
    
    // Insert ke database
    // Di file tambah.php, pastikan query insert seperti ini:
$query = "INSERT INTO menu (nama_menu, kategori, harga, deskripsi, gambar, created_at) 
VALUES ('$nama_menu', '$kategori', '$harga', '$deskripsi', " . ($gambar ? "'$gambar'" : "NULL") . ", NOW())";
    
    if (mysqli_query($conn, $query)) {
        header("Location: dashboard-premium.php?status=tambah_success");
        exit();
    } else {
        $error = "Gagal menambahkan menu: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Menu - RestoManager</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

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
                    <a class="nav-link active" href="tambah.php">
                        <i class="fas fa-plus-circle"></i> Tambah Menu
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="dashboard_analitik.php">
                        <i class="fas fa-chart-line"></i> Analitik Penjualan
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body p-4">
                    <h3 class="card-title mb-4">
                        <i class="fas fa-plus-circle text-primary"></i> Tambah Menu Baru
                    </h3>
                    
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?= $error ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label class="form-label">
                                <i class="fas fa-utensil-spoon"></i> Nama Menu <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="nama_menu" class="form-control" required placeholder="Contoh: Nasi Goreng Spesial">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">
                                <i class="fas fa-tag"></i> Kategori <span class="text-danger">*</span>
                            </label>
                            <select name="kategori" class="form-select" required>
                                <option value="">Pilih Kategori</option>
                                <option value="Makanan">🍚 Makanan</option>
                                <option value="Minuman">🥤 Minuman</option>
                                <option value="Snack">🍿 Snack</option>
                                <option value="Dessert">🍰 Dessert</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">
                                <i class="fas fa-money-bill-wave"></i> Harga <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" name="harga" class="form-control" required min="0" step="1000" placeholder="0">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">
                                <i class="fas fa-align-left"></i> Deskripsi
                            </label>
                            <textarea name="deskripsi" class="form-control" rows="4" placeholder="Deskripsi menu..."></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">
                                <i class="fas fa-image"></i> Foto Menu
                            </label>
                            <input type="file" name="gambar" class="form-control" accept="image/jpg,image/jpeg,image/png" onchange="previewImage(event)">
                            <small class="text-muted">Format: JPG, JPEG, PNG | Maksimal: 2MB</small>
                            <div id="imagePreview" class="mt-2"></div>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="index.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left"></i> Batal
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Simpan Menu
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function previewImage(event) {
    const preview = document.getElementById('imagePreview');
    preview.innerHTML = '';
    
    if (event.target.files && event.target.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const img = document.createElement('img');
            img.src = e.target.result;
            img.classList.add('menu-img-preview');
            preview.appendChild(img);
        }
        reader.readAsDataURL(event.target.files[0]);
    }
}
</script>

</body>
</html>