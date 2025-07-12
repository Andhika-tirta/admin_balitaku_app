<?php
session_start();
include '../../config/paths.php';

// Cek otorisasi
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'admin') {
    header("Location: " . $root_app_url . "login.php?error=unauthorized_access");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Kader Baru - POSYANDU BALITAKU</title>
    <link rel="stylesheet" href="<?= $root_app_url ?>assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            /* Menggunakan variabel warna dari tema Posyandu yang disepakati */
            --primary-color: #2a9d8f; /* Hijau Teal */
            --primary-dark: #21867a;
            --secondary-color: #264653; /* Biru Tua / Hampir Hitam */
            --accent-color: #e9c46a; /* Kuning Kecoklatan */
            --danger-color: #e76f51; /* Merah Oranye */
            --success-color: #4CAF50; /* Hijau untuk success/default button */

            --light-gray: #f8f9fa;
            --white: #ffffff;
            --text-dark: #2b2d42;
            --text-light: #8d99ae;
            --card-shadow: 0 10px 30px -15px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            color: var(--text-dark);
            line-height: 1.6;
            min-height: 100vh; /* Memastikan body mengisi seluruh viewport */
            display: flex; /* Untuk memposisikan form di tengah */
            justify-content: center; /* Memposisikan horizontal */
            align-items: center; /* Memposisikan vertikal */

            /* Background Image dengan Overlay */
            background-image: 
                radial-gradient(circle at 10% 20%, rgba(255,255,255,0.8) 0%, transparent 20%),
                radial-gradient(circle at 90% 80%, rgba(255, 255, 255, 0.8) 0%, transparent 20%),
                linear-gradient(to bottom, rgba(255,255,255,0.9) 0%, rgba(255,255,255,0.8) 100%),
                url('<?= $root_app_url ?>assets/img/bg-posyandu.jpeg'); /* Menggunakan gambar background yang diunggah */
            background-size: cover;
            background-attachment: fixed;
            background-position: center;
            background-blend-mode: overlay; /* Memadukan warna overlay dengan gambar */
        }
        
        .form-container {
            background-color: var(--white);
            padding: 2.5rem;
            border-radius: 16px;
            box-shadow: var(--card-shadow);
            max-width: 600px;
            width: 90%; /* Memberikan lebar responsif */
            transition: var(--transition);
            border: 1px solid rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(5px); /* Sedikit blur pada background form */
            background: rgba(255, 255, 255, 0.95); /* Lebih opaque agar lebih mudah dibaca */
        }
        
        .form-container:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        }
        
        .form-header {
            text-align: center;
            margin-bottom: 2.5rem;
            position: relative;
        }
        
        .form-header h2 {
            color: var(--primary-color); /* Menggunakan primary-color */
            font-size: 2rem;
            margin-bottom: 0.5rem;
            font-weight: 600;
            position: relative;
            display: inline-block;
        }
        
        .form-header h2:after {
            content: '';
            position: absolute;
            width: 50px;
            height: 3px;
            background: linear-gradient(90deg, var(--primary-color), var(--accent-color)); /* Gradien warna tema */
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            border-radius: 3px;
        }
        
        .form-header p {
            color: var(--text-light);
            font-size: 0.95rem;
            margin-top: 1.5rem;
        }
        
        .form-header i {
            font-size: 2.5rem;
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color)); /* Gradien warna tema */
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 1rem;
            display: inline-block;
        }
        
        .form-group {
            margin-bottom: 1.75rem;
            position: relative;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.75rem;
            font-weight: 500;
            color: var(--text-dark);
            font-size: 0.95rem;
        }
        
        .form-group input {
            width: 100%;
            padding: 14px 20px;
            border: 1px solid #e9ecef;
            border-radius: 10px;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
            transition: var(--transition);
            background-color: var(--light-gray);
            font-size: 0.95rem;
            color: var(--text-dark); /* Agar teks input tidak hilang */
        }
        
        .form-group input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(42, 157, 143, 0.2); /* Sesuaikan shadow dengan primary-color */
            outline: none;
            background-color: var(--white);
        }
        
        .form-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 2.5rem;
            gap: 1rem;
        }
        
        .btn {
            padding: 14px 28px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-size: 1em;
            font-weight: 500;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 1;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }
        
        .btn-submit {
            background-color: var(--primary-color); /* Menggunakan primary-color */
            color: white;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 10px rgba(42, 157, 143, 0.2); /* Shadow dengan primary-color */
        }
        
        .btn-submit:hover {
            background-color: var(--primary-dark); /* primary-dark */
            transform: translateY(-3px);
            box-shadow: 0 7px 14px rgba(42, 157, 143, 0.3); /* Shadow lebih kuat */
        }
        
        .btn-submit:active {
            transform: translateY(1px);
        }
        
        .btn-back {
            background-color: var(--secondary-color); /* Menggunakan secondary-color */
            color: var(--white); /* Teks putih untuk secondary-color */
            text-decoration: none;
            border: none; /* Hilangkan border jika sudah ada background-color */
            box-shadow: 0 4px 10px rgba(38, 70, 83, 0.2); /* Shadow dengan secondary-color */
        }
        
        .btn-back:hover {
            background-color: #1a323c; /* Sedikit lebih gelap dari secondary */
            transform: translateY(-3px);
            box-shadow: 0 7px 14px rgba(38, 70, 83, 0.3);
        }
        
        .btn i {
            margin-right: 10px;
            font-size: 1.1em;
        }
        
        .password-toggle {
            position: absolute;
            right: 15px;
            /* Sesuaikan top agar sejajar dengan input field */
            top: 50%; 
            transform: translateY(-50%);
            cursor: pointer;
            color: var(--text-light);
            transition: var(--transition);
        }
        
        .password-toggle:hover {
            color: var(--primary-color);
        }
        
        @media (max-width: 768px) {
            .form-container {
                padding: 1.75rem;
                margin: 1.5rem;
                border-radius: 12px;
            }
            
            .form-actions {
                flex-direction: column;
                gap: 1rem;
            }
            
            .btn {
                width: 100%;
            }
            
            .form-header h2 {
                font-size: 1.7rem;
            }
        }
        
        /* Floating animation for header icon */
        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
            100% { transform: translateY(0px); }
        }
        
        .form-header i {
            animation: float 3s ease-in-out infinite;
        }
        
        /* Input focus label effect */
        .form-group.focused label {
            color: var(--primary-color);
        }
        
        /* Additional decorative elements */
        /* Pastikan elemen ini ada di HTML jika ingin ditampilkan */
        .decorative-circle {
            position: absolute;
            width: 200px;
            height: 200px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(42, 157, 143, 0.1) 0%, rgba(255, 255, 255, 0) 70%); /* Sesuaikan warna dengan tema */
            z-index: -1;
        }
        
        .circle-1 {
            top: -50px;
            right: -50px;
        }
        
        .circle-2 {
            bottom: -50px;
            left: -50px;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <div class="decorative-circle circle-1"></div>
        <div class="decorative-circle circle-2"></div>
        
        <div class="form-header">
            <i class="fas fa-user-plus"></i>
            <h2>Tambah Kader Baru</h2>
            <p>Silakan lengkapi formulir berikut untuk mendaftarkan kader baru ke sistem POSYANDU BALITAKU</p>
        </div>
        <form action="<?= $base_url_admin ?>proses_kader.php" method="POST">
            <input type="hidden" name="action" value="add">
            <div class="form-group">
                <label for="nama"><i class="fas fa-user"></i> Nama Lengkap:</label>
                <input type="text" id="nama" name="nama" required placeholder="Contoh: Andhika Tirtaprana Ardi">
            </div>
            <div class="form-group">
                <label for="username"><i class="fas fa-at"></i> Username:</label>
                <input type="text" id="username" name="username" required placeholder="Contoh: andhika_tirta">
            </div>
            <div class="form-group">
                <label for="email"><i class="fas fa-envelope"></i> Email:</label>
                <input type="email" id="email" name="email" required placeholder="Contoh: andhika@example.com">
            </div>
            <div class="form-group">
                <label for="password"><i class="fas fa-lock"></i> Password:</label>
                <input type="password" id="password" name="password" required placeholder="Minimal 8 karakter">
                <span class="password-toggle" onclick="togglePassword()">
                    <i class="fas fa-eye"></i>
                </span>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-submit"><i class="fas fa-user-plus"></i> Tambah Kader</button>
                <a href="<?= $base_url_admin ?>kelola_kader.php" class="btn btn-back"><i class="fas fa-arrow-left"></i> Kembali</a>
            </div>
        </form>
    </div>

    <script>
        function togglePassword() {
            const passwordField = document.getElementById('password');
            const toggleIcon = document.querySelector('.password-toggle i');
            
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordField.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }

        // Add focus class to form group when input is focused
        document.querySelectorAll('.form-group input').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.classList.add('focused');
            });
            
            input.addEventListener('blur', function() {
                if (this.value === '') {
                    this.parentElement.classList.remove('focused');
                }
            });
            
            // Add focused class if input has value on page load
            if (input.value !== '') {
                input.parentElement.classList.add('focused');
            }
        });
    </script>
</body>
</html>