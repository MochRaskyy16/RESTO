<?php
/**
 * login.php - Halaman Login Admin
 */

session_start();

// Jika sudah login, redirect ke dashboard
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: dashboard-premium.php");
    exit();
}

require_once 'database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    
    // Query cek admin (menggunakan MD5, ganti dengan password_verify() jika perlu)
    $query = "SELECT * FROM admin WHERE username = '$username' AND password = MD5('$password')";
    $result = mysqli_query($conn, $query);
    
    if (mysqli_num_rows($result) == 1) {
        $admin = mysqli_fetch_assoc($result);
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_nama'] = $admin['nama_lengkap'];
        $_SESSION['admin_username'] = $admin['username'];
        
        header("Location: dashboard-premium.php");
        exit();
    } else {
        $error = "Username atau password salah!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin - Loehoer Restaurant</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap');
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #4a0000 0%, #7a0000 50%, #9a0000 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }
        
        /* Background food icons */
        .bg-food {
            position: absolute;
            font-size: 150px;
            opacity: 0.05;
            pointer-events: none;
        }
        
        .bg-food-1 { top: 10%; left: 5%; transform: rotate(-15deg); }
        .bg-food-2 { bottom: 10%; right: 5%; transform: rotate(15deg); }
        .bg-food-3 { top: 50%; left: 20%; transform: rotate(45deg); }
        .bg-food-4 { bottom: 30%; right: 20%; transform: rotate(-30deg); }
        
        .login-card {
            background: white;
            border-radius: 30px;
            padding: 40px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 450px;
            position: relative;
            z-index: 10;
            animation: fadeUp 0.8s ease forwards;
        }
        
        @keyframes fadeUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .login-logo {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .login-logo i {
            font-size: 60px;
            color: #4a0000;
            background: linear-gradient(135deg, #ffd89b, #ffb347);
            padding: 20px;
            border-radius: 50%;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
        
        .login-logo h3 {
            margin-top: 15px;
            font-weight: 700;
            color: #4a0000;
        }
        
        .form-control {
            border-radius: 15px;
            padding: 12px 20px;
            border: 1px solid #e5e7eb;
            font-family: 'Poppins', sans-serif;
        }
        
        .form-control:focus {
            border-color: #9a0000;
            box-shadow: 0 0 0 3px rgba(154, 0, 0, 0.1);
        }
        
        .btn-login {
            background: linear-gradient(135deg, #4a0000, #9a0000);
            color: white;
            border: none;
            border-radius: 15px;
            padding: 12px;
            font-weight: 600;
            width: 100%;
            transition: all 0.3s ease;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(74, 0, 0, 0.3);
        }
        
        .btn-pembeli {
            background: transparent;
            border: 2px solid #4a0000;
            color: #4a0000;
            border-radius: 15px;
            padding: 12px;
            font-weight: 600;
            width: 100%;
            transition: all 0.3s ease;
            text-decoration: none;
            display: block;
            text-align: center;
        }
        
        .btn-pembeli:hover {
            background: #4a0000;
            color: white;
            transform: translateY(-2px);
        }
        
        .divider {
            text-align: center;
            margin: 20px 0;
            position: relative;
        }
        
        .divider::before,
        .divider::after {
            content: '';
            position: absolute;
            top: 50%;
            width: 45%;
            height: 1px;
            background: #e5e7eb;
        }
        
        .divider::before { left: 0; }
        .divider::after { right: 0; }
        
        .divider span {
            background: white;
            padding: 0 15px;
            color: #6c757d;
            font-size: 0.85rem;
        }
    </style>
</head>
<body>

<div class="bg-food bg-food-1">🍕</div>
<div class="bg-food bg-food-2">🍔</div>
<div class="bg-food bg-food-3">🍜</div>
<div class="bg-food bg-food-4">🍝</div>

<div class="login-card">
    <div class="login-logo">
        <i class="fas fa-utensils"></i>
        <h3>Loehoer Restaurant</h3>
        <p class="text-muted">Admin Panel</p>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <form method="POST" action="">
        <div class="mb-3">
            <label class="form-label"><i class="fas fa-user me-2"></i> Username</label>
            <input type="text" name="username" class="form-control" required placeholder="Masukkan username">
        </div>
        <div class="mb-4">
            <label class="form-label"><i class="fas fa-lock me-2"></i> Password</label>
            <input type="password" name="password" class="form-control" required placeholder="Masukkan password">
        </div>
        <button type="submit" class="btn-login mb-3">
            <i class="fas fa-sign-in-alt me-2"></i> Login Admin
        </button>
    </form>
    
    <div class="divider">
        <span>ATAU</span>
    </div>
    
    <a href="index_pembeli.php" class="btn-pembeli">
        <i class="fas fa-shopping-cart me-2"></i> Belanja Sebagai Pembeli
    </a>
    
    <div class="text-center mt-4">
        <small class="text-muted">Demo: username: admin | password: admin123</small>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>