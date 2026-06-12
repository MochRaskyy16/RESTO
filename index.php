<?php
/**
 * index.php - Halaman Dashboard & Data Menu
 * Menampilkan statistik dan daftar menu dengan fitur pencarian
 */

require_once 'database.php';

// Pagination
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Search filter
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$kategori = isset($_GET['kategori']) ? mysqli_real_escape_string($conn, $_GET['kategori']) : '';

// Build query
$where = [];
if (!empty($search)) {
    $where[] = "nama_menu LIKE '%$search%'";
}
if (!empty($kategori)) {
    $where[] = "kategori = '$kategori'";
}
$where_clause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

// Get total records
$total_query = "SELECT COUNT(*) as total FROM menu $where_clause";
$total_result = mysqli_query($conn, $total_query);
$total_row = mysqli_fetch_assoc($total_result);
$total_records = $total_row['total'];
$total_pages = ceil($total_records / $limit);

// Get menu data
$query = "SELECT * FROM menu $where_clause ORDER BY created_at DESC LIMIT $offset, $limit";
$result = mysqli_query($conn, $query);

// Get statistics
$total_menu = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM menu"))['total'];
$total_kategori = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(DISTINCT kategori) as total FROM menu"))['total'];
$menu_terbaru = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM menu WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)"))['total'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Manajemen Restoran</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg sticky-top">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <i class="fas fa-utensils"></i> LOEHOER
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link active" href="index.php">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="tambah.php">
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
    <!-- Statistik Cards -->
    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="card card-stat">
                <div class="card-body position-relative">
                    <i class="fas fa-hamburger"></i>
                    <h3><?= $total_menu ?></h3>
                    <p>Total Menu</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card card-stat">
                <div class="card-body position-relative">
                    <i class="fas fa-tags"></i>
                    <h3><?= $total_kategori ?></h3>
                    <p>Total Kategori</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card card-stat">
                <div class="card-body position-relative">
                    <i class="fas fa-clock"></i>
                    <h3><?= $menu_terbaru ?></h3>
                    <p>Menu Baru (7 Hari)</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Search Section -->
    <div class="search-section">
        <form method="GET" action="index.php" class="row g-3">
            <div class="col-md-5">
                <label class="form-label">
                    <i class="fas fa-search"></i> Cari Menu
                </label>
                <input type="text" name="search" class="form-control" placeholder="Cari berdasarkan nama menu..." value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="col-md-5">
                <label class="form-label">
                    <i class="fas fa-filter"></i> Filter Kategori
                </label>
                <select name="kategori" class="form-select">
                    <option value="">Semua Kategori</option>
                    <option value="Makanan" <?= $kategori == 'Makanan' ? 'selected' : '' ?>>Makanan</option>
                    <option value="Minuman" <?= $kategori == 'Minuman' ? 'selected' : '' ?>>Minuman</option>
                    <option value="Snack" <?= $kategori == 'Snack' ? 'selected' : '' ?>>Snack</option>
                    <option value="Dessert" <?= $kategori == 'Dessert' ? 'selected' : '' ?>>Dessert</option>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-search"></i> Cari
                </button>
            </div>
        </form>
    </div>

    <!-- Data Table -->
    <div class="table-container">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Foto</th>
                        <th>Nama Menu</th>
                        <th>Kategori</th>
                        <th>Harga</th>
                        <th>Deskripsi</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($result) > 0): ?>
                        <?php $no = $offset + 1; ?>
                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td>
                                    <?php if ($row['gambar'] && file_exists('uploads/' . $row['gambar'])): ?>
                                        <img src="uploads/<?= $row['gambar'] ?>" class="menu-img" alt="Foto Menu">
                                    <?php else: ?>
                                        <img src="assets/img/default-food.png" class="menu-img" alt="Default Image">
                                    <?php endif; ?>
                                </td>
                                <td class="fw-bold"><?= htmlspecialchars($row['nama_menu']) ?></td>
                                <td>
                                    <span class="badge bg-<?= $row['kategori'] == 'Makanan' ? 'danger' : ($row['kategori'] == 'Minuman' ? 'info' : ($row['kategori'] == 'Snack' ? 'warning' : 'success')) ?>">
                                        <?= $row['kategori'] ?>
                                    </span>
                                </td>
                                <td>Rp <?= number_format($row['harga'], 0, ',', '.') ?></td>
                                <td><?= htmlspecialchars(substr($row['deskripsi'], 0, 50)) ?>...</td>
                                <td>
                                    <a href="edit.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-warning">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <button class="btn btn-sm btn-danger btn-delete" data-id="<?= $row['id'] ?>" data-nama="<?= htmlspecialchars($row['nama_menu']) ?>">
                                        <i class="fas fa-trash"></i> Hapus
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7">
                                <div class="empty-state">
                                    <i class="fas fa-database"></i>
                                    <h5>Tidak ada data menu</h5>
                                    <p>Silakan tambah menu baru melalui menu Tambah Menu</p>
                                    <a href="tambah.php" class="btn btn-primary mt-2">
                                        <i class="fas fa-plus"></i> Tambah Menu
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <nav class="mt-4">
        <ul class="pagination justify-content-center">
            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="?page=<?= $page-1 ?>&search=<?= urlencode($search) ?>&kategori=<?= urlencode($kategori) ?>">Previous</a>
            </li>
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                    <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&kategori=<?= urlencode($kategori) ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
            <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                <a class="page-link" href="?page=<?= $page+1 ?>&search=<?= urlencode($search) ?>&kategori=<?= urlencode($kategori) ?>">Next</a>
            </li>
        </ul>
    </nav>
    <?php endif; ?>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- SweetAlert2 Delete Confirmation -->
<script>
document.querySelectorAll('.btn-delete').forEach(button => {
    button.addEventListener('click', function() {
        const id = this.dataset.id;
        const nama = this.dataset.nama;
        
        Swal.fire({
            title: 'Hapus Menu?',
            text: `Apakah Anda yakin ingin menghapus "${nama}"?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = `hapus.php?id=${id}`;
            }
        });
    });
});

// Show success message from URL parameter
const urlParams = new URLSearchParams(window.location.search);
if (urlParams.has('status')) {
    const status = urlParams.get('status');
    if (status === 'tambah_success') {
        Swal.fire('Berhasil!', 'Menu berhasil ditambahkan', 'success');
    } else if (status === 'edit_success') {
        Swal.fire('Berhasil!', 'Menu berhasil diupdate', 'success');
    } else if (status === 'hapus_success') {
        Swal.fire('Terhapus!', 'Menu berhasil dihapus', 'success');
    }
    // Hapus parameter dari URL
    window.history.replaceState({}, document.title, window.location.pathname);
}
</script>

</body>
</html>