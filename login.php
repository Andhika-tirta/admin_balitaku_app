<?php

session_start();
include 'config/paths.php';

// Cek jika sudah login, langsung arahkan berdasarkan rolenya
if (isset($_SESSION['role'])) {
    switch ($_SESSION['role']) {
        case 'admin':
            header("Location: " . $base_url_admin);
            exit;
        case 'kader':
            header("Location: " . $base_url_kader);
            exit;
        case 'ortu':
            header("Location: " . $base_url_ortu);
            exit;
        default:
            // Jika role tidak dikenal, logout saja
            session_destroy();
            header("Location: " . $root_app_url . "login.php"); // Gunakan $root_app_url untuk konsistensi
            exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Login - POSYANDU BALITAKU</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
    <style>
        /* CSS Anda tetap sama */
        :root {
            --primary: #4CAF50;
            --primary-dark: #388E3C;
            --danger: #e63757;
            --light: #f9fafd;
            --dark: #283252;
            --gray: #95aac9;
            --border-radius: 0.5rem;
            --shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: var(--light);
            /* Pastikan path gambar background ini benar relatif ke login.php */
            background-image:
                linear-gradient(rgba(76, 175, 80, 0.5), rgba(76, 175, 80, 0.5)),
                url('<?= $root_app_url ?>assets/img/umb.jpg'); /* <-- PERBAIKI PATH INI */
            background-size: cover;
            background-repeat: no-repeat;
            background-position: center;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .login-container {
            width: 100%;
            max-width: 420px;
            background: rgba(255, 255, 255, 0.95);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            overflow: hidden;
            animation: fadeIn 0.5s ease-out;
        }

        .login-header {
            background-color: var(--primary);
            color: white;
            padding: 1.5rem;
            text-align: center;
            position: relative;
        }

        .logo-container {
            text-align: center;
            padding: 1.5rem 0 0;
        }

        .logo {
            width: 100px;
            height: 100px;
            object-fit: contain;
            margin-bottom: 1rem;
            border-radius: 50%;
            background-color: white;
            padding: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .login-header h2 {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .login-header p {
            font-size: 0.875rem;
            opacity: 0.9;
        }

        .login-header::after {
            content: '';
            position: absolute;
            bottom: -12px;
            left: 0;
            width: 100%;
            height: 24px;
            background-color: white;
            clip-path: polygon(0 0, 100% 0, 50% 100%);
        }

        .login-body {
            padding: 2rem;
        }

        .error-message {
            background-color: rgba(230, 55, 87, 0.1);
            color: var(--danger);
            padding: 0.75rem 1rem;
            border-radius: var(--border-radius);
            margin-bottom: 1.5rem;
            font-size: 0.875rem;
            border-left: 3px solid var(--danger);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .error-message i {
            font-size: 1rem;
        }

        .form-group {
            margin-bottom: 1.25rem;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--dark);
        }

        .form-group input {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 2.5rem;
            border: 1px solid #e3ebf6;
            border-radius: var(--border-radius);
            font-size: 0.9375rem;
            transition: all 0.3s;
            background-color: #f9fafd;
        }

        .form-group input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.2);
            outline: none;
            background-color: white;
        }

        .form-group i {
            position: absolute;
            left: 1rem;
            top: 2.5rem;
            color: var(--gray);
            font-size: 1rem;
        }

        .btn-login {
            width: 100%;
            padding: 0.75rem;
            background-color: var(--primary);
            color: white;
            border: none;
            border-radius: var(--border-radius);
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 0.5rem;
        }

        .btn-login:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 480px) {
            .login-container {
                margin: 0 1rem;
            }

            .logo {
                width: 80px;
                height: 80px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="logo-container">
                <img src="<?= $root_app_url ?>assets/img/Posyandu Logo Vector.jpeg" alt="Logo Posyandu" class="logo"> </div>
            <h2>POSYANDU MAWAR</h2>
            <p>Sistem Administrasi Posyandu Digital</p>
        </div>
        
        <div class="login-body">
            <?php if (isset($_GET['error'])): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php
                        // Memastikan pesan error ditampilkan dengan benar
                        $error_message = match ($_GET['error']) {
                            'empty_fields' => 'Email dan password tidak boleh kosong.',
                            'invalid_credentials' => 'Email atau password salah!',
                            'db_error' => 'Terjadi kesalahan database. Silakan coba lagi nanti.',
                            'db_connection_failed' => 'Koneksi database gagal. Mohon hubungi administrator.',
                            default => 'Terjadi kesalahan saat login.'
                        };
                        echo htmlspecialchars($error_message); // Selalu gunakan htmlspecialchars untuk output
                    ?>
                </div>
            <?php endif; ?>
            
            <form action="proses_login.php" method="POST">
                <div class="form-group">
                    <label for="email">Email:</label> <i class="fas fa-envelope"></i>
                    <input type="email" id="email" name="email" required placeholder="Masukkan email Anda"> </div>
                
                <div class="form-group">
                    <label for="password">Password:</label>
                    <i class="fas fa-lock"></i>
                    <input type="password" id="password" name="password" required placeholder="Masukkan password">
                </div>
                
                <button type="submit" name="login" class="btn-login">
                    <i class="fas fa-sign-in-alt"></i> Masuk 
                </button>
            </form>
        </div>
    </div>
</body>
</html>