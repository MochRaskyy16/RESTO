<?php
/**
 * keranjang.php - Halaman keranjang belanja pembeli
 */

session_start();

if (!isset($_SESSION['keranjang'])) {
    $_SESSION['keranjang'] = [];
}

$keranjang = $_SESSION['keranjang'];
$total = 0;
foreach ($keranjang as $item) {
    $total += $item['harga'] * $item['qty'];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keranjang - Loehoer Restaurant</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap');
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e9ecef 100%);
        }
        
        .navbar {
            background: linear-gradient(135deg, #4a0000, #7a0000);
            padding: 1rem 0;
        }
        
        .navbar-brand, .nav-link { color: white !important; }
        
        .cart-card {
            background: white;
            border-radius: 25px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
        }
        
        .btn-premium {
            background: linear-gradient(135deg, #4a0000, #9a0000);
            color: white;
            border: none;
            border-radius: 12px;
            padding: 12px 25px;
            font-weight: 600;
        }
        
        .btn-premium:hover { transform: translateY(-2px); color: white; }
        
        .quantity-input {
            width: 60px;
            text-align: center;
            border-radius: 10px;
            border: 1px solid #e5e7eb;
            padding: 5px;
        }
        
        .table-cart thead th {
            background: linear-gradient(135deg, #4a0000, #7a0000);
            color: white;
            padding: 12px;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg sticky-top">
    <div class="container">
        <a class="navbar-brand" href="index_pembeli.php">
            <i class="fas fa-utensils me-2"></i> Loehoer Restaurant
        </a>
        <div class="ms-auto">
            <a href="index_pembeli.php" class="btn btn-outline-light">
                <i class="fas fa-arrow-left"></i> Lanjut Belanja
            </a>
            <a href="login.php" class="btn btn-outline-light ms-2">
                <i class="fas fa-lock"></i> Admin
            </a>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <div class="cart-card">
        <h3 class="mb-4"><i class="fas fa-shopping-cart me-2"></i> Keranjang Belanja</h3>
        
        <?php if (count($keranjang) > 0): ?>
            <div class="table-responsive">
                <table class="table table-cart">
                    <thead>
                        <tr><th>Menu</th><th>Harga</th><th>Jumlah</th><th>Subtotal</th><th>Aksi</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($keranjang as $id => $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['nama']); ?></td>
                                <td>Rp <?php echo number_format($item['harga'], 0, ',', '.'); ?></td>
                                <td>
                                    <input type="number" class="quantity-input" value="<?php echo $item['qty']; ?>" 
                                           min="1" onchange="updateQty(<?php echo $id; ?>, this.value)">
                                </td>
                                <td>Rp <?php echo number_format($item['harga'] * $item['qty'], 0, ',', '.'); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-danger" onclick="hapusItem(<?php echo $id; ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="fw-bold">
                            <td colspan="3" class="text-end">Total:</td>
                            <td colspan="2">Rp <?php echo number_format($total, 0, ',', '.'); ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            
            <div class="row mt-4">
                <div class="col-md-6">
                    <label class="form-label">Nama Pemesan</label>
                    <input type="text" id="nama_pemesan" class="form-control" placeholder="Masukkan nama Anda">
                </div>
                <div class="col-md-6">
                    <label class="form-label">No Meja</label>
                    <input type="text" id="no_meja" class="form-control" placeholder="Masukkan nomor meja">
                </div>
                <div class="col-12 mt-3">
                    <label class="form-label">Catatan (opsional)</label>
                    <textarea id="catatan" class="form-control" rows="2" placeholder="Contoh: Tidak pedas, ekstra sambal..."></textarea>
                </div>
            </div>
            
            <div class="d-flex justify-content-between mt-4">
                <a href="index_pembeli.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i> Lanjut Belanja
                </a>
                <button class="btn btn-premium" onclick="checkout()">
                    <i class="fas fa-check-circle me-2"></i> Checkout
                </button>
            </div>
            
        <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-shopping-cart fa-4x mb-3 text-muted"></i>
                <h5>Keranjang kosong</h5>
                <p>Silakan pilih menu terlebih dahulu</p>
                <a href="index_pembeli.php" class="btn btn-premium mt-2">
                    <i class="fas fa-shopping-basket"></i> Mulai Belanja
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function updateQty(id, qty) {
    fetch('update_keranjang.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `id=${id}&qty=${qty}`
    }).then(() => location.reload());
}

function hapusItem(id) {
    Swal.fire({
        title: 'Hapus Item?',
        text: 'Item akan dihapus dari keranjang',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        confirmButtonText: 'Ya, Hapus!'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('hapus_item_keranjang.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `id=${id}`
            }).then(() => location.reload());
        }
    });
}

function checkout() {
    const nama_pemesan = document.getElementById('nama_pemesan').value;
    const no_meja = document.getElementById('no_meja').value;
    const catatan = document.getElementById('catatan').value;
    
    if (!nama_pemesan) {
        Swal.fire('Error', 'Nama pemesan harus diisi', 'error');
        return;
    }
    
    fetch('checkout.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `nama_pemesan=${encodeURIComponent(nama_pemesan)}&no_meja=${encodeURIComponent(no_meja)}&catatan=${encodeURIComponent(catatan)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire('Berhasil!', 'Pesanan Anda telah diterima', 'success')
                .then(() => window.location.href = 'pesanan_saya.php?no_pesanan=' + data.no_pesanan);
        } else {
            Swal.fire('Gagal!', data.message, 'error');
        }
    });
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>