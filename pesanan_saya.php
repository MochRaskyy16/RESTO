<?php
/**
 * pesanan_saya.php - Melihat riwayat pesanan
 */

require_once 'database.php';

$no_pesanan = isset($_GET['no_pesanan']) ? $_GET['no_pesanan'] : '';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesanan Saya - Loehoer Restaurant</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
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
        
        .receipt-card {
            background: white;
            border-radius: 25px;
            padding: 30px;
            margin-bottom: 25px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            max-width: 600px;
            margin: 0 auto;
        }
        
        .btn-premium {
            background: linear-gradient(135deg, #4a0000, #9a0000);
            color: white;
            border-radius: 12px;
            padding: 10px 25px;
            text-decoration: none;
        }
        
        .status-badge {
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .status-pending { background: #f59e0b; color: white; }
        .status-proses { background: #3b82f6; color: white; }
        .status-selesai { background: #10b981; color: white; }
        .status-batal { background: #ef4444; color: white; }
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
                <i class="fas fa-shopping-basket"></i> Pesan Lagi
            </a>
            <a href="login.php" class="btn btn-outline-light ms-2">
                <i class="fas fa-lock"></i> Admin
            </a>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <div class="receipt-card text-center">
        <?php if ($no_pesanan): 
            $query = "SELECT * FROM pesanan WHERE no_pesanan = '$no_pesanan'";
            $result = mysqli_query($conn, $query);
            $pesanan = mysqli_fetch_assoc($result);
            
            if ($pesanan):
        ?>
            <i class="fas fa-check-circle fa-4x text-success mb-3"></i>
            <h3 class="mb-2">Pesanan Berhasil!</h3>
            <p class="text-muted">Terima kasih, pesanan Anda telah kami terima</p>
            
            <div class="text-start mt-4">
                <h5>Detail Pesanan</h5>
                <hr>
                <p><strong>No. Pesanan:</strong> <?php echo $pesanan['no_pesanan']; ?></p>
                <p><strong>Nama Pemesan:</strong> <?php echo htmlspecialchars($pesanan['nama_pemesan']); ?></p>
                <p><strong>No Meja:</strong> <?php echo $pesanan['no_meja'] ?: '-'; ?></p>
                <p><strong>Total:</strong> Rp <?php echo number_format($pesanan['total_harga'], 0, ',', '.'); ?></p>
                <p><strong>Status:</strong> 
                    <span class="status-badge status-<?php echo $pesanan['status']; ?>">
                        <?php echo ucfirst($pesanan['status']); ?>
                    </span>
                </p>
                <?php if ($pesanan['catatan']): ?>
                    <p><strong>Catatan:</strong> <?php echo htmlspecialchars($pesanan['catatan']); ?></p>
                <?php endif; ?>
            </div>
            
            <div class="mt-4">
                <a href="index_pembeli.php" class="btn btn-premium">
                    <i class="fas fa-shopping-basket"></i> Pesan Lagi
                </a>
                <button class="btn btn-outline-secondary ms-2" onclick="window.print()">
                    <i class="fas fa-print"></i> Print
                </button>
            </div>
        <?php else: ?>
            <i class="fas fa-exclamation-circle fa-4x text-danger mb-3"></i>
            <h3>Pesanan Tidak Ditemukan</h3>
            <a href="index_pembeli.php" class="btn btn-premium mt-3">Kembali ke Menu</a>
        <?php endif; ?>
        <?php else: ?>
            <i class="fas fa-search fa-4x text-muted mb-3"></i>
            <h3>Belum Ada Pesanan</h3>
            <a href="index_pembeli.php" class="btn btn-premium mt-3">Mulai Pesan</a>
        <?php endif; ?>
    </div>
</div>

</body>
</html>