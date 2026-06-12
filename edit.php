<?php
/**
 * edit.php - Halaman untuk mengedit menu
 */

require_once 'database.php';

// Ambil ID dari URL
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Ambil data menu
$query = "SELECT * FROM menu WHERE id = $id";
$result = mysqli_query($conn, $query);
$menu = mysqli_fetch_assoc($result);

if (!$menu) {
    header("Location: index.php");
    exit();
}

// Proses form submit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_menu = mysqli_real_escape_string($conn, $_POST['nama_menu']);
    $kategori = mysqli_real_escape_string($conn, $_POST['kategori']);
    $harga = mysqli_real_escape_string($conn, $_POST['harga']);
    $deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi']);
    
    $gambar = $menu['gambar'];
    
    // Proses upload gambar baru
    if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png'];
        $filename = $_FILES['gambar']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $file_size = $_FILES['gambar']['size'];
        
        if (in_array($ext, $allowed) && $file_size <= 2 * 1024 * 1024) {
            // Hapus gambar lama jika ada
            if ($gambar && file_exists('uploads/' . $gambar)) {
                unlink('uploads/' . $gambar);
            }
            
            $new_filename = time() . '_' . uniqid() . '.' . $ext;
            if (move_uploaded_file($_FILES['gambar']['tmp_name'], 'uploads/' . $new_filename)) {
                $gambar = $new_filename;
            }
        }
    }
    
    // Update database
    $query = "UPDATE menu SET 
              nama_menu = '$nama_menu',
              kategori = '$kategori',
              harga = '$harga',
              deskripsi = '$deskripsi',
              gambar = " . ($gambar ? "'$gambar'" : "NULL") . "
              WHERE id = $id";
    
    if (mysqli_query($conn, $query)) {
        header("Location: index.php?status=edit_success");
        exit();
    } else {
        $error = "Gagal mengupdate menu: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Menu - RestoManager</title>
    
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
                    <a class="nav-link" href="tambah.php">
                        <i class="fas fa-plus-circle"></i> Tambah Menu
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
                        <i class="fas fa-edit text-warning"></i> Edit Menu
                    </h3>
                    
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?= $error ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label class="form-label">Nama Menu <span class="text-danger">*</span></label>
                            <input type="text" name="nama_menu" class="form-control" value="<?= htmlspecialchars($menu['nama_menu']) ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Kategori <span class="text-danger">*</span></label>
                            <select name="kategori" class="form-select" required>
                                <option value="Makanan" <?= $menu['kategori'] == 'Makanan' ? 'selected' : '' ?>>🍚 Makanan</option>
                                <option value="Minuman" <?= $menu['kategori'] == 'Minuman' ? 'selected' : '' ?>>🥤 Minuman</option>
                                <option value="Snack" <?= $menu['kategori'] == 'Snack' ? 'selected' : '' ?>>🍿 Snack</option>
                                <option value="Dessert" <?= $menu['kategori'] == 'Dessert' ? 'selected' : '' ?>>🍰 Dessert</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Harga <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" name="harga" class="form-control" value="<?= $menu['harga'] ?>" required min="0" step="1000">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Deskripsi</label>
                            <textarea name="deskripsi" class="form-control" rows="4"><?= htmlspecialchars($menu['deskripsi']) ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Foto Menu Saat Ini</label>
                            <div>
                                <?php if ($menu['gambar'] && file_exists('uploads/' . $menu['gambar'])): ?>
                                    <img src="uploads/<?= $menu['gambar'] ?>" class="menu-img-preview" alt="Current Image">
                                <?php else: ?>
                                    <p class="text-muted">Tidak ada gambar</p>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Ganti Foto (Opsional)</label>
                            <input type="file" name="gambar" class="form-control" accept="image/jpg,image/jpeg,image/png" onchange="previewImage(event)">
                            <small class="text-muted">Format: JPG, JPEG, PNG | Maksimal: 2MB</small>
                            <div id="imagePreview" class="mt-2"></div>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="index.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left"></i> Batal
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update Menu
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