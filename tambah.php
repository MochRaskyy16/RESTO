<?php
/**
 * tambah.php - Halaman Tambah Menu Restoran Premium
 * Tampilan selaras dengan dashboard premium
 */
/**
 * tambah.php - Halaman Tambah Menu
 */

// ============================================
// CEK SESSION LOGIN
// ============================================
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

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
                
                // Buat folder uploads jika belum ada
                if (!file_exists('uploads')) {
                    mkdir('uploads', 0777, true);
                }
                
                if (move_uploaded_file($_FILES['gambar']['tmp_name'], $upload_path)) {
                    $gambar = $new_filename;
                }
            } else {
                $error = "Ukuran file terlalu besar. Maksimal 2MB!";
            }
        } else {
            $error = "Format file tidak support. Gunakan JPG, JPEG, atau PNG!";
        }
    }
    
    // Insert ke database
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
    <title>Tambah Menu - Loehoer Restaurant</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        /* ============================================ */
        /* PREMIUM DASHBOARD CSS - SAMA DENGAN DASHBOARD */
        /* ============================================ */
        
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap');
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e9ecef 100%);
            overflow-x: hidden;
        }
        
        /* Loading Spinner */
        .spinner-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }
        .spinner-overlay.show { opacity: 1; visibility: visible; }
        .spinner {
            width: 60px;
            height: 60px;
            border: 4px solid rgba(255, 255, 255, 0.3);
            border-top-color: #ffb347;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
        
        /* Sidebar */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 280px;
            height: 100vh;
            background: linear-gradient(180deg, #2d0000 0%, #4b0000 50%, #6b0000 100%);
            color: white;
            transition: all 0.3s ease;
            z-index: 1000;
            overflow-y: auto;
            box-shadow: 5px 0 30px rgba(0, 0, 0, 0.1);
        }
        
        .sidebar::-webkit-scrollbar { width: 5px; }
        .sidebar::-webkit-scrollbar-track { background: rgba(255, 255, 255, 0.1); }
        .sidebar::-webkit-scrollbar-thumb { background: rgba(255, 255, 255, 0.3); border-radius: 10px; }
        
        .sidebar-logo { padding: 30px 25px; border-bottom: 1px solid rgba(255, 255, 255, 0.1); margin-bottom: 20px; }
        .sidebar-logo h3 { font-size: 1.5rem; font-weight: 700; margin: 0; }
        .sidebar-logo h3 i { margin-right: 10px; font-size: 1.8rem; }
        .sidebar-logo p { font-size: 0.75rem; opacity: 0.7; margin-top: 5px; margin-bottom: 0; }
        
        .sidebar-nav { padding: 0 15px; }
        .sidebar-nav .nav-item { list-style: none; margin-bottom: 8px; }
        .sidebar-nav .nav-link {
            display: flex;
            align-items: center;
            padding: 12px 18px;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            border-radius: 12px;
            transition: all 0.3s ease;
            font-weight: 500;
        }
        .sidebar-nav .nav-link i { width: 28px; font-size: 1.2rem; margin-right: 12px; }
        .sidebar-nav .nav-link:hover { background: rgba(255, 255, 255, 0.15); color: white; transform: translateX(5px); }
        .sidebar-nav .nav-link.active { background: linear-gradient(135deg, rgba(255, 255, 255, 0.2), rgba(255, 255, 255, 0.05)); color: white; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1); }
        
       /* Sidebar User */
.sidebar-user {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    padding: 20px;
    background: rgba(0, 0, 0, 0.3);
    backdrop-filter: blur(10px);
    border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.user-avatar {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: linear-gradient(135deg, #ffd89b, #c7e9fb);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    font-weight: bold;
    color: #4b0000;
}

.user-info h6 {
    margin: 0;
    font-size: 0.9rem;
    font-weight: 600;
}

.user-info p {
    margin: 0;
    font-size: 0.7rem;
    opacity: 0.7;
}

.user-status {
    width: 10px;
    height: 10px;
    background: #4ade80;
    border-radius: 50%;
    display: inline-block;
    margin-right: 5px;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

/* Tombol Logout */
.btn-logout {
    background: rgba(255, 255, 255, 0.15);
    color: white;
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 12px;
    padding: 10px;
    font-size: 0.85rem;
    font-weight: 500;
    transition: all 0.3s ease;
    cursor: pointer;
    width: 100%;
    text-align: center;
}

.btn-logout:hover {
    background: #ef4444;
    border-color: #ef4444;
    transform: translateY(-2px);
    color: white;
}
        /* Main Content */
        .main-content { margin-left: 280px; padding: 20px; transition: all 0.3s ease; }
        
        /* Hero Banner Mini */
        .hero-banner-mini {
            background: linear-gradient(135deg, #4a0000 0%, #7a0000 50%, #9a0000 100%);
            border-radius: 25px;
            padding: 30px 40px;
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
        }
        .hero-banner-mini::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200"><path fill="rgba(255,255,255,0.05)" d="M100 0L200 200H0L100 0z"/></svg>');
            background-size: 60px;
            opacity: 0.3;
        }
        .hero-content { position: relative; z-index: 2; }
        .hero-banner-mini h1 { font-size: 1.8rem; font-weight: 800; margin-bottom: 10px; color: white; }
        .hero-banner-mini p { font-size: 0.95rem; opacity: 0.85; margin-bottom: 0; color: white; }
        .gradient-text { background: linear-gradient(135deg, #FFD89B, #FFB347); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
        
        /* Form Card */
        .form-card {
            background: white;
            border-radius: 25px;
            padding: 35px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
        }
        .form-card:hover { box-shadow: 0 20px 40px rgba(0, 0, 0, 0.12); }
        
        .form-label {
            font-weight: 600;
            color: #4a0000;
            margin-bottom: 8px;
            font-size: 0.9rem;
        }
        
        .form-control, .form-select {
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            padding: 12px 16px;
            font-family: 'Poppins', sans-serif;
            transition: all 0.3s ease;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #9a0000;
            box-shadow: 0 0 0 3px rgba(154, 0, 0, 0.1);
            outline: none;
        }
        
        .input-group-text {
            background: #f8f9fa;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            font-weight: 600;
            color: #4a0000;
        }
        
        /* Preview Image */
        .image-preview {
            width: 150px;
            height: 150px;
            border-radius: 15px;
            object-fit: cover;
            margin-top: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            border: 3px solid #ffb347;
        }
        
        /* Buttons */
        .btn-premium {
            background: linear-gradient(135deg, #4a0000, #9a0000);
            color: white;
            border: none;
            border-radius: 12px;
            padding: 12px 30px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-premium:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(74, 0, 0, 0.3); color: white; }
        
        .btn-outline-premium {
            background: transparent;
            color: #4a0000;
            border: 2px solid #4a0000;
            border-radius: 12px;
            padding: 12px 30px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-outline-premium:hover { background: #4a0000; color: white; transform: translateY(-2px); }
        
        /* Required field */
        .text-danger { color: #ef4444 !important; }
        
        /* Responsive */
        @media (max-width: 992px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.show { transform: translateX(0); }
            .main-content { margin-left: 0; }
            .menu-toggle { display: block !important; }
        }
        @media (max-width: 768px) {
            .hero-banner-mini h1 { font-size: 1.3rem; }
            .form-card { padding: 20px; }
        }
        
        .menu-toggle {
            display: none;
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1100;
            background: #4a0000;
            color: white;
            border: none;
            width: 45px;
            height: 45px;
            border-radius: 12px;
            font-size: 1.2rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .fade-up {
            animation: fadeUp 0.8s ease forwards;
        }
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* File input styling */
        .file-input-wrapper {
            position: relative;
            display: inline-block;
            width: 100%;
        }
        .file-input-wrapper input[type=file] {
            padding: 12px;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            width: 100%;
            font-family: 'Poppins', sans-serif;
        }
        .text-muted small { font-size: 0.7rem; }
    </style>
</head>
<body>

<!-- Loading Spinner -->
<div id="loadingSpinner" class="spinner-overlay">
    <div class="spinner"></div>
</div>

<!-- Menu Toggle untuk Mobile -->
<button class="menu-toggle" onclick="toggleSidebar()">
    <i class="fas fa-bars"></i>
</button>

<!-- ============================================ -->
<!-- SIDEBAR PREMIUM -->
<!-- ============================================ -->
<!-- ============================================ -->
<!-- SIDEBAR PREMIUM -->
<!-- ============================================ -->
<div class="sidebar" id="sidebar">
    <div class="sidebar-logo">
        <h3>
            <i class="fas fa-utensils"></i> Loehoer
        </h3>
        <p>Restaurant Management System</p>
    </div>
    
    <ul class="sidebar-nav">
        <li class="nav-item">
            <a class="nav-link active" href="dashboard-premium.php">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="tambah.php">
                <i class="fas fa-plus-circle"></i> Tambah Menu
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="manajemen_pesanan.php">
                <i class="fas fa-clipboard-list"></i> Manajemen Pesanan
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="dashboard_analitik.php">
                <i class="fas fa-chart-line"></i> Analitik Penjualan
            </a>
        </li>
    </ul>
    
    <!-- ========================================== -->
    <!-- SIDEBAR USER - COCOKKAN DI SEMUA FILE     -->
    <!-- ========================================== -->
    <div class="sidebar-user">
        <div class="d-flex align-items-center">
            <div class="user-avatar me-3">
                MR
            </div>
            <div class="user-info">
                <h6>Moch Rasky P</h6>
                <p><span class="user-status"></span> Online</p>
            </div>
        </div>
        <div class="mt-3">
            <button onclick="confirmLogout()" class="btn btn-logout w-100">
                <i class="fas fa-sign-out-alt me-2"></i> Logout
            </button>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- MAIN CONTENT -->
<!-- ============================================ -->
<div class="main-content">
    
    <!-- HERO BANNER MINI -->
    <div class="hero-banner-mini fade-up">
        <div class="hero-content">
            <h1>
                <i class="fas fa-plus-circle me-2"></i> 
                Tambah <span class="gradient-text">Menu Baru</span>
            </h1>
            <p>Isi form di bawah ini untuk menambahkan menu baru ke restoran Anda</p>
        </div>
    </div>
    
    <!-- FORM CARD -->
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="form-card fade-up" style="animation-delay: 0.1s;">
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <form method="POST" enctype="multipart/form-data">
                    <!-- Nama Menu -->
                    <div class="mb-4">
                        <label class="form-label">
                            <i class="fas fa-utensil-spoon me-2"></i> 
                            Nama Menu <span class="text-danger">*</span>
                        </label>
                        <input type="text" name="nama_menu" class="form-control" required 
                               placeholder="Contoh: Nasi Goreng Spesial">
                        <small class="text-muted">Masukkan nama menu yang unik dan mudah diingat</small>
                    </div>
                    
                    <!-- Kategori -->
                    <div class="mb-4">
                        <label class="form-label">
                            <i class="fas fa-tag me-2"></i> 
                            Kategori <span class="text-danger">*</span>
                        </label>
                        <select name="kategori" class="form-select" required>
                            <option value="">Pilih Kategori</option>
                            <option value="Makanan">🍚 Makanan</option>
                            <option value="Minuman">🥤 Minuman</option>
                            <option value="Snack">🍿 Snack</option>
                            <option value="Dessert">🍰 Dessert</option>
                        </select>
                    </div>
                    
                    <!-- Harga -->
                    <div class="mb-4">
                        <label class="form-label">
                            <i class="fas fa-money-bill-wave me-2"></i> 
                            Harga <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="number" name="harga" class="form-control" required 
                                   min="0" step="1000" placeholder="0" id="hargaInput">
                        </div>
                        <small class="text-muted">Masukkan harga dalam Rupiah (tanpa titik atau koma)</small>
                    </div>
                    
                    <!-- Deskripsi -->
                    <div class="mb-4">
                        <label class="form-label">
                            <i class="fas fa-align-left me-2"></i> 
                            Deskripsi
                        </label>
                        <textarea name="deskripsi" class="form-control" rows="4" 
                                  placeholder="Deskripsi menu..."></textarea>
                        <small class="text-muted">Jelaskan bahan, rasa, atau keunggulan menu (opsional)</small>
                    </div>
                    
                    <!-- Foto Menu -->
                    <div class="mb-4">
                        <label class="form-label">
                            <i class="fas fa-image me-2"></i> 
                            Foto Menu
                        </label>
                        <div class="file-input-wrapper">
                            <input type="file" name="gambar" class="form-control" 
                                   accept="image/jpg,image/jpeg,image/png" 
                                   onchange="previewImage(event)">
                        </div>
                        <small class="text-muted">
                            <i class="fas fa-info-circle me-1"></i> 
                            Format: JPG, JPEG, PNG | Maksimal: 2MB
                        </small>
                        <div id="imagePreview" class="mt-3"></div>
                    </div>
                    
                    <!-- Tombol Aksi -->
                    <div class="d-flex gap-3 justify-content-end mt-4 pt-3">
                        <a href="dashboard-premium.php" class="btn btn-outline-premium">
                            <i class="fas fa-arrow-left me-2"></i> Batal
                        </a>
                        <button type="submit" class="btn btn-premium">
                            <i class="fas fa-save me-2"></i> Simpan Menu
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Floating decoration -->
    <div class="text-center mt-4">
        <small class="text-muted">
            <i class="fas fa-info-circle"></i> Pastikan semua data terisi dengan benar sebelum menyimpan
        </small>
    </div>
</div>

<!-- Bootstrap JS -->
<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Loading spinner
window.addEventListener('load', function() {
    setTimeout(function() {
        var spinner = document.getElementById('loadingSpinner');
        if (spinner) spinner.classList.remove('show');
    }, 500);
});

// Toggle sidebar untuk mobile
function toggleSidebar() {
    var sidebar = document.getElementById('sidebar');
    if (sidebar) sidebar.classList.toggle('show');
}

// Delete confirmation
function confirmDelete(id, nama) {
    Swal.fire({
        title: 'Hapus Menu?',
        text: 'Apakah Anda yakin ingin menghapus "' + nama + '"?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'hapus.php?id=' + id;
        }
    });
}

// Fungsi konfirmasi logout
function confirmLogout() {
    Swal.fire({
        title: 'Yakin ingin logout?',
        text: 'Anda akan keluar dari panel admin',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Ya, Logout!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'logout.php';
        }
    });
}

// Success message handler
<?php if (isset($_GET['status'])): ?>
    var status = '<?php echo $_GET['status']; ?>';
    var message = '';
    if (status === 'tambah_success') message = 'Menu berhasil ditambahkan';
    else if (status === 'edit_success') message = 'Menu berhasil diupdate';
    else if (status === 'hapus_success') message = 'Menu berhasil dihapus';
    
    if (message) {
        Swal.fire({
            title: 'Berhasil!',
            text: message,
            icon: 'success',
            confirmButtonColor: '#4a0000'
        });
        window.history.replaceState({}, document.title, window.location.pathname);
    }
<?php endif; ?>
</script>
<script>
// Loading spinner
window.addEventListener('load', function() {
    setTimeout(function() {
        var spinner = document.getElementById('loadingSpinner');
        if (spinner) spinner.classList.remove('show');
    }, 500);
});

// Toggle sidebar untuk mobile
function toggleSidebar() {
    var sidebar = document.getElementById('sidebar');
    if (sidebar) sidebar.classList.toggle('show');
}

// Preview image before upload
function previewImage(event) {
    const preview = document.getElementById('imagePreview');
    preview.innerHTML = '';
    
    if (event.target.files && event.target.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const img = document.createElement('img');
            img.src = e.target.result;
            img.classList.add('image-preview');
            preview.appendChild(img);
        }
        reader.readAsDataURL(event.target.files[0]);
    }
}

// Format harga saat diketik (opsional)
document.getElementById('hargaInput')?.addEventListener('input', function(e) {
    // Hanya angka
    this.value = this.value.replace(/[^0-9]/g, '');
});

// Show error from URL parameter
const urlParams = new URLSearchParams(window.location.search);
if (urlParams.has('error')) {
    const errorMsg = urlParams.get('error');
    Swal.fire({
        title: 'Gagal!',
        text: errorMsg === 'upload' ? 'Gagal mengupload gambar' : 'Terjadi kesalahan, silakan coba lagi',
        icon: 'error',
        confirmButtonColor: '#4a0000'
    });
    window.history.replaceState({}, document.title, window.location.pathname);
}
</script>

</body>
</html>
