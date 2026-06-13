<?php
/**
 * index_pembeli.php - Halaman untuk pembeli (tanpa login)
 */

require_once 'database.php';

// Inisialisasi session untuk keranjang
session_start();
if (!isset($_SESSION['keranjang'])) {
    $_SESSION['keranjang'] = [];
}

// Ambil data menu
// Kode BARU (menampilkan semua menu)
$query = "SELECT * FROM menu ORDER BY kategori, nama_menu";
$result = mysqli_query($conn, $query);

// Hitung jumlah item di keranjang
$jumlah_keranjang = 0;
foreach ($_SESSION['keranjang'] as $item) {
    $jumlah_keranjang += $item['qty'];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesan Menu - Loehoer Restaurant</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap');
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e9ecef 100%);
        }
        
        .navbar {
            background: linear-gradient(135deg, #4a0000, #7a0000);
            padding: 1rem 0;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .navbar-brand {
            color: white !important;
            font-weight: 700;
            font-size: 1.5rem;
        }
        
        .cart-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #ffb347;
            color: #4a0000;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 0.7rem;
            font-weight: bold;
        }
        
        .menu-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            height: 100%;
        }
        
        .menu-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.15);
        }
        
        .menu-img {
            width: 100%;
            height: 180px;
            object-fit: cover;
        }
        
        .menu-img-placeholder {
            width: 100%;
            height: 180px;
            background: linear-gradient(135deg, #ffd89b, #ffb347);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 60px;
        }
        
        .menu-body {
            padding: 15px;
        }
        
        .menu-title {
            font-weight: 700;
            font-size: 1.1rem;
            margin-bottom: 5px;
        }
        
        .menu-price {
            color: #4a0000;
            font-weight: 700;
            font-size: 1.2rem;
        }
        
        .btn-order {
            background: linear-gradient(135deg, #4a0000, #9a0000);
            color: white;
            border: none;
            border-radius: 12px;
            padding: 8px 15px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-order:hover {
            transform: scale(1.05);
            box-shadow: 0 5px 15px rgba(74,0,0,0.3);
        }
        
        .hero-section {
            background: linear-gradient(135deg, #4a0000, #7a0000);
            border-radius: 25px;
            padding: 40px;
            margin-bottom: 30px;
            color: white;
        }
        
        .badge-custom {
            background: #ffb347;
            color: #4a0000;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
        }
        
        @media (max-width: 768px) {
            .hero-section { padding: 25px; }
            .hero-section h1 { font-size: 1.5rem; }
        }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg sticky-top">
    <div class="container">
        <a class="navbar-brand" href="index_pembeli.php">
            <i class="fas fa-utensils me-2"></i> Loehoer Restaurant
        </a>
        <div class="ms-auto">
            <a href="keranjang.php" class="btn btn-outline-light position-relative">
                <i class="fas fa-shopping-cart"></i> Keranjang
                <?php if ($jumlah_keranjang > 0): ?>
                    <span class="cart-badge"><?php echo $jumlah_keranjang; ?></span>
                <?php endif; ?>
            </a>
            <a href="login.php" class="btn btn-outline-light ms-2">
                <i class="fas fa-lock"></i> Admin
            </a>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <!-- Hero Section -->
    <div class="hero-section text-center">
        <h1><i class="fas fa-hamburger me-2"></i> Selamat Datang di Loehoer Restaurant</h1>
        <p class="mb-0">Pesan menu favorit Anda secara online, praktis dan cepat!</p>
    </div>
    
    <!-- Filter Kategori (Opsional) -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="btn-group flex-wrap gap-2">
                <button class="btn btn-outline-danger btn-filter active" data-kategori="semua">Semua</button>
                <button class="btn btn-outline-danger btn-filter" data-kategori="Makanan">🍚 Makanan</button>
                <button class="btn btn-outline-danger btn-filter" data-kategori="Minuman">🥤 Minuman</button>
                <button class="btn btn-outline-danger btn-filter" data-kategori="Snack">🍿 Snack</button>
                <button class="btn btn-outline-danger btn-filter" data-kategori="Dessert">🍰 Dessert</button>
            </div>
        </div>
    </div>
    
    <!-- Daftar Menu -->
    <div class="row g-4" id="menuContainer">
        <?php while ($row = mysqli_fetch_assoc($result)): ?>
            <div class="col-md-6 col-lg-4 col-xl-3 menu-item" data-kategori="<?php echo $row['kategori']; ?>">
                <div class="menu-card">
                    <?php if (!empty($row['gambar']) && file_exists('uploads/' . $row['gambar'])): ?>
                        <img src="uploads/<?php echo $row['gambar']; ?>" class="menu-img" alt="<?php echo $row['nama_menu']; ?>">
                    <?php else: ?>
                        <div class="menu-img-placeholder">
                            <?php
                            $emoji = '🍽️';
                            if ($row['kategori'] == 'Makanan') $emoji = '🍚';
                            if ($row['kategori'] == 'Minuman') $emoji = '🥤';
                            if ($row['kategori'] == 'Snack') $emoji = '🍿';
                            if ($row['kategori'] == 'Dessert') $emoji = '🍰';
                            echo $emoji;
                            ?>
                        </div>
                    <?php endif; ?>
                    <div class="menu-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h5 class="menu-title"><?php echo htmlspecialchars($row['nama_menu']); ?></h5>
                            <span class="badge-custom"><?php echo $row['kategori']; ?></span>
                        </div>
                        <p class="text-muted small"><?php echo htmlspecialchars(substr($row['deskripsi'] ?? '', 0, 60)); ?>...</p>
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <span class="menu-price">Rp <?php echo number_format($row['harga'], 0, ',', '.'); ?></span>
                            <button class="btn-order" onclick="tambahKeKeranjang(<?php echo $row['id']; ?>, '<?php echo addslashes($row['nama_menu']); ?>', <?php echo $row['harga']; ?>)">
                                <i class="fas fa-plus me-1"></i> Pesan
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
</div>

<script>
// Filter kategori
document.querySelectorAll('.btn-filter').forEach(btn => {
    btn.addEventListener('click', function() {
        const kategori = this.dataset.kategori;
        
        document.querySelectorAll('.btn-filter').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        
        document.querySelectorAll('.menu-item').forEach(item => {
            if (kategori === 'semua' || item.dataset.kategori === kategori) {
                item.style.display = '';
            } else {
                item.style.display = 'none';
            }
        });
    });
});

// Tambah ke keranjang
function tambahKeKeranjang(id, nama, harga) {
    fetch('tambah_keranjang.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `id=${id}&nama=${encodeURIComponent(nama)}&harga=${harga}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                title: 'Berhasil!',
                text: `${nama} ditambahkan ke keranjang`,
                icon: 'success',
                timer: 1500,
                showConfirmButton: false
            });
            // Update badge keranjang
            location.reload();
        }
    });
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>