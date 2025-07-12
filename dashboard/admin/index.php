<?php

session_start(); // <-- HANYA SATU KALI DI SINI
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include '../../config/paths.php'; 
include '../../config/db.php'; 

// Cek apakah user sudah login dan role-nya adalah 'admin'
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'admin') {
    header("Location: " . $root_app_url . "login.php?error=unauthorized_access"); // Perbaikan: Tambah parameter error
    exit;
}

// Perbaikan: Hapus baris ini karena $_SESSION['admin'] kemungkinan tidak ada
// echo "Selamat datang, " . $_SESSION['nama'] . " (" . $_SESSION['admin'] . ")";

// Pastikan koneksi database tidak null sebelum melakukan query
if (!isset($conn) || !$conn) {
    // Jika koneksi gagal, redirect ke halaman login dengan pesan error yang lebih informatif
    header("Location: " . $root_app_url . "login.php?error=db_connection_failed");
    exit;
}

// Ambil data dashboard
$jml_balita = $conn->query("SELECT COUNT(*) AS total FROM balita")->fetch_assoc()['total'] ?? 0;
$jml_pemeriksaan = $conn->query("SELECT COUNT(*) AS total FROM pemeriksaan")->fetch_assoc()['total'] ?? 0;
$jml_ortu = $conn->query("SELECT COUNT(*) AS total FROM orang_tua")->fetch_assoc()['total'] ?? 0;

// Menutup koneksi di sini, setelah semua query selesai
// Ini agar koneksi tidak terbuka terlalu lama, tapi sebelum output HTML
$conn->close(); 
?>
<!DOCTYPE html>
<html>
<head>
    <title>POSYANDU BALITAKU - Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary: #2a9d8f;
            --primary-light: rgba(42, 157, 143, 0.1);
            --secondary: #264653;
            --accent: #e9c46a;
            --light: #f8f9fa;
            --danger: #e76f51;
            --white: #ffffff;
        }

        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            color: #333;
            background-color: #f1f8f7;
            background-image: 
                radial-gradient(circle at 10% 20%, var(--primary-light) 0%, transparent 20%),
                radial-gradient(circle at 90% 80%, var(--primary-light) 0%, transparent 20%),
                linear-gradient(to bottom, rgba(255,255,255,0.9) 0%, rgba(255,255,255,0.8) 100%),
                url('<?= $root_app_url ?>assets/images/bg-pattern.png'); /* Pastikan path ini benar */
            background-size: cover;
            background-attachment: fixed;
            background-blend-mode: overlay;
            min-height: 100vh;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        header {
            background-color: var(--primary);
            color: white;
            padding: 20px 0;
            border-radius: 0 0 10px 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            background-image: linear-gradient(135deg, var(--primary) 0%, #21867a 100%);
            position: relative;
            overflow: hidden;
        }

        header::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(90deg, var(--accent) 0%, var(--primary) 50%, var(--accent) 100%);
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            z-index: 1;
        }

        .logo {
            display: flex;
            align-items: center;
        }

        .logo i {
            font-size: 2rem;
            margin-right: 15px;
            color: var(--white);
            text-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }

        .logo h1 {
            margin: 0;
            font-size: 1.8rem;
            font-weight: 600;
            text-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }

        .user-info {
            display: flex;
            align-items: center;
            background-color: rgba(255,255,255,0.2);
            padding: 8px 15px;
            border-radius: 20px;
            transition: all 0.3s ease;
        }

        .user-info:hover {
            background-color: rgba(255,255,255,0.3);
        }

        .user-info i {
            margin-right: 10px;
            color: var(--white);
        }

        .user-info span {
            color: var(--white);
        }

        .dashboard-cards {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            margin-bottom: 30px;
            gap: 20px;
        }

        .card {
            background-color: var(--white);
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            padding: 25px;
            flex: 1 1 300px;
            text-align: center;
            transition: all 0.3s ease;
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255,255,255,0.3);
            position: relative;
            overflow: hidden;
        }

        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.12);
        }

        .card i {
            font-size: 2.5rem;
            margin-bottom: 15px;
            transition: all 0.3s ease;
        }

        .card:hover i {
            transform: scale(1.1);
        }

        .card h2 {
            margin: 0;
            font-size: 2.5rem;
            transition: all 0.3s ease;
        }

        .card p {
            margin: 10px 0 0;
            color: #666;
            font-size: 1rem;
        }

        .card.balita {
            background: linear-gradient(135deg, rgba(255,255,255,0.9) 0%, rgba(255,255,255,0.95) 100%);
        }

        .card.balita::before {
            background: linear-gradient(90deg, var(--primary) 0%, #4bb4a8 100%);
        }

        .card.balita i {
            color: var(--primary);
        }

        .card.balita h2 {
            color: var(--primary);
        }

        .card.ortu {
            background: linear-gradient(135deg, rgba(255,255,255,0.9) 0%, rgba(255,255,255,0.95) 100%);
        }

        .card.ortu::before {
            background: linear-gradient(90deg, var(--accent) 0%, #f2d27d 100%);
        }

        .card.ortu i {
            color: var(--accent);
        }

        .card.ortu h2 {
            color: var(--accent);
        }

        .card.pemeriksaan {
            background: linear-gradient(135deg, rgba(255,255,255,0.9) 0%, rgba(255,255,255,0.95) 100%);
        }

        .card.pemeriksaan::before {
            background: linear-gradient(90deg, var(--danger) 0%, #f1826b 100%);
        }

        .card.pemeriksaan i {
            color: var(--danger);
        }

        .card.pemeriksaan h2 {
            color: var(--danger);
        }

        .navigation {
            background: linear-gradient(135deg, rgba(255,255,255,0.95) 0%, rgba(255,255,255,0.98) 100%);
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            padding: 25px;
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255,255,255,0.3);
        }

        .navigation h3 {
            margin-top: 0;
            color: var(--secondary);
            border-bottom: 2px solid var(--accent);
            padding-bottom: 10px;
            font-size: 1.3rem;
        }

        .nav-links {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
        }

        .nav-link {
            display: flex;
            align-items: center;
            text-decoration: none;
            color: var(--secondary);
            background-color: rgba(42, 157, 143, 0.08);
            padding: 15px 20px;
            border-radius: 8px;
            transition: all 0.3s ease;
            flex: 1 1 200px;
            border: 1px solid rgba(42, 157, 143, 0.1);
        }

        .nav-link:hover {
            background-color: var(--primary);
            color: white;
            transform: translateX(5px);
            box-shadow: 0 4px 12px rgba(42, 157, 143, 0.2);
        }

        .nav-link:hover i {
            color: white;
        }

        .nav-link i {
            margin-right: 10px;
            font-size: 1.2rem;
            color: var(--primary);
            transition: all 0.3s ease;
        }

        footer {
            text-align: center;
            margin-top: 40px;
            padding: 20px;
            color: #666;
            font-size: 0.9rem;
            background-color: rgba(255,255,255,0.8);
            border-radius: 10px;
            margin-bottom: 20px;
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255,255,255,0.3);
        }

        @media (max-width: 768px) {
            .dashboard-cards {
                flex-direction: column;
            }

            .card {
                width: 100%;
            }

            .nav-links {
                flex-direction: column;
            }

            .header-content {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }

            .logo {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="container header-content">
            <div class="logo">
                <i class="fas fa-baby-carriage"></i>
                <h1>POSYANDU MAWAR</h1>
            </div>
            <div class="user-info">
                <i class="fas fa-user-shield"></i>
                <span>Selamat Datang, Admin <?php echo htmlspecialchars($_SESSION['nama']); ?>!</span> </div>
        </div>
    </header>

    <div class="container">
        <div class="dashboard-cards">
            <div class="card balita">
                <i class="fas fa-child"></i>
                <h2><?= $jml_balita ?></h2>
                <p>Balita Terdaftar</p>
            </div>

            <div class="card ortu">
                <i class="fas fa-users"></i>
                <h2><?= $jml_ortu ?></h2>
                <p>Orang Tua Terdaftar</p>
            </div>

            <div class="card pemeriksaan">
                <i class="fas fa-notes-medical"></i>
                <h2><?= $jml_pemeriksaan ?></h2>
                <p>Total Pemeriksaan</p>
            </div>
        </div>

        <div class="navigation">
            <h3><i class="fas fa-bars"></i> Menu Navigasi</h3>
            <div class="nav-links">
                <a href="<?= $base_url_admin ?>balita.php" class="nav-link">
                    <i class="fas fa-baby"></i><span>Data Balita</span>
                </a>
                <a href="<?= $base_url_admin ?>pemeriksaan.php" class="nav-link">
                    <i class="fas fa-stethoscope"></i><span>Pemeriksaan</span>
                </a>
                <a href="<?= $base_url_admin ?>statistik.php" class="nav-link">
                    <i class="fas fa-chart-line"></i><span>Statistik</span>
                </a>
                <a href="<?= $base_url_admin ?>imunisasi.php" class="nav-link">
                    <i class="fas fa-syringe"></i><span>Imunisasi</span>
                </a>
                <a href="<?= $base_url_admin ?>laporan.php" class="nav-link">
                    <i class="fas fa-file-alt"></i><span>Laporan</span>
                </a>
                <a href="<?= $base_url_admin ?>orang_tua.php" class="nav-link">
                    <i class="fas fa-user-friends"></i><span>Data Orang Tua</span>
                </a>
                <a href="<?= $base_url_admin ?>kelola_kader.php" class="nav-link">
                    <i class="fas fa-user-tie"></i><span>Kelola Kader</span> 
                </a>
                <a href="<?= $root_app_url ?>logout.php" class="nav-link">
                    <i class="fas fa-sign-out-alt"></i><span>Logout</span>
                </a>
            </div>
        </div>
    </div>

    <footer>
        <p>Pos Pelayanan Terpadu (Posyandu) - Membangun Generasi Sehat sejak Dini</p>
        <p>© <?= date('Y') ?> Sistem Informasi Posyandu. All rights reserved.</p>
    </footer>
</body>
</html>