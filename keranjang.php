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
    background: linear-gradient(135deg, #fff9f0 0%, #ffe6cc 100%);
    overflow-x: hidden;
    position: relative;
}

/* Background gambar makanan */
body::before {
    content: "";
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-image: url('assets/img/burger.jpeg');
    background-repeat: repeat;
    background-size: 300px;
    opacity: 0.2;
    pointer-events: none;
    z-index: 0;
}

body::after {
    content: "";
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-image: url('assets/img/minuman.png');
    background-repeat: repeat;
    background-size: 230px;
    opacity: 0.2;
    pointer-events: none;
    z-index: 0;
    background-position: 75px 75px;
}

/* Konten di atas background */
.container, .navbar, .hero-section {
    position: relative;
    z-index: 1;
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
        
        /* ============================================ */
        /* PERBAIKAN: Validasi No Meja */
        /* ============================================ */
        .form-control.is-invalid {
            border-color: #dc3545;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' width='12' height='12' fill='none' stroke='%23dc3545'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath stroke-linejoin='round' d='M5.8 3.6h.4L6 6.5z'/%3e%3ccircle cx='6' cy='8.2' r='.6' fill='%23dc3545' stroke='none'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right calc(0.375em + 0.1875rem) center;
            background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
        }
        
        .invalid-feedback {
            display: none;
            color: #dc3545;
            font-size: 0.8rem;
            margin-top: 5px;
        }
        
        .invalid-feedback.show {
            display: block;
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
            
            <!-- ============================================ -->
            <!-- FORM DENGAN VALIDASI NO MEJA                  -->
            <!-- ============================================ -->
            <form id="checkoutForm" onsubmit="return validateForm(event)">
                <div class="row mt-4">
                    <div class="col-md-6">
                        <label class="form-label">Nama Pemesan</label>
                        <input type="text" id="nama_pemesan" class="form-control" placeholder="Masukkan nama Anda" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">No Meja</label>
                        <input type="number" id="no_meja" class="form-control" 
                               placeholder="Masukkan nomor meja" 
                               min="1" step="1"
                               oninput="validateNoMeja(this)"
                               required>
                        <div class="invalid-feedback" id="noMejaError">
                            <i class="fas fa-exclamation-circle me-1"></i> 
                            Nomor meja harus lebih dari 0!
                        </div>
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
                    <button type="submit" class="btn btn-premium">
                        <i class="fas fa-check-circle me-2"></i> Checkout
                    </button>
                </div>
            </form>
            
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
// ============================================
// VALIDASI NO MEJA - TIDAK BOLEH MINUS ATAU 0
// ============================================
function validateNoMeja(input) {
    var value = parseInt(input.value);
    var errorDiv = document.getElementById('noMejaError');
    
    if (isNaN(value) || value <= 0) {
        input.classList.add('is-invalid');
        errorDiv.classList.add('show');
        return false;
    } else {
        input.classList.remove('is-invalid');
        errorDiv.classList.remove('show');
        return true;
    }
}

// ============================================
// UPDATE QUANTITY
// ============================================
function updateQty(id, qty) {
    if (qty < 1) {
        Swal.fire('Error', 'Jumlah minimal 1', 'error');
        location.reload();
        return;
    }
    
    fetch('update_keranjang.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'id=' + id + '&qty=' + qty
    }).then(() => location.reload());
}

// ============================================
// HAPUS ITEM
// ============================================
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
                body: 'id=' + id
            }).then(() => location.reload());
        }
    });
}

// ============================================
// VALIDASI FORM SEBELUM CHECKOUT
// ============================================
function validateForm(event) {
    event.preventDefault();
    
    var nama = document.getElementById('nama_pemesan').value.trim();
    var noMeja = document.getElementById('no_meja');
    var noMejaValue = parseInt(noMeja.value);
    var errorDiv = document.getElementById('noMejaError');
    
    // Cek nama pemesan
    if (nama === '') {
        Swal.fire('Error', 'Nama pemesan harus diisi!', 'error');
        document.getElementById('nama_pemesan').focus();
        return false;
    }
    
    // Cek no meja
    if (isNaN(noMejaValue) || noMejaValue <= 0 || noMeja.value === '') {
        noMeja.classList.add('is-invalid');
        errorDiv.classList.add('show');
        Swal.fire('Error', 'Nomor meja harus lebih dari 0!', 'error');
        noMeja.focus();
        return false;
    }
    
    // Jika semua valid, lanjutkan checkout
    checkout();
    return false;
}

// ============================================
// CHECKOUT
// ============================================
function checkout() {
    var nama_pemesan = document.getElementById('nama_pemesan').value;
    var no_meja = document.getElementById('no_meja').value;
    var catatan = document.getElementById('catatan').value;
    
    // Tampilkan loading
    Swal.fire({
        title: 'Memproses...',
        text: 'Mohon tunggu sebentar',
        icon: 'info',
        showConfirmButton: false,
        allowOutsideClick: false
    });
    
    fetch('checkout.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'nama_pemesan=' + encodeURIComponent(nama_pemesan) + 
              '&no_meja=' + encodeURIComponent(no_meja) + 
              '&catatan=' + encodeURIComponent(catatan)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire('Berhasil!', 'Pesanan Anda telah diterima', 'success')
                .then(() => window.location.href = 'pesanan_saya.php?no_pesanan=' + data.no_pesanan);
        } else {
            Swal.fire('Gagal!', data.message, 'error');
        }
    })
    .catch(error => {
        Swal.fire('Error!', 'Terjadi kesalahan pada server', 'error');
    });
}
</script>

</body>
</html>
