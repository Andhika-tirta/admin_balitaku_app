<?php
// PASTIKAN INI ADALAH BARIS PERTAMA. HANYA SATU KALI session_start().
session_start(); 

// Aktifkan error reporting untuk debugging.
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Sertakan file konfigurasi path URL.
// Path ini relatif dari laporan.php (di dashboard/admin/) naik dua level ke root aplikasi.
include '../../config/paths.php'; 

// Sertakan file koneksi database.
// Path ini relatif dari laporan.php (di dashboard/admin/) naik dua level ke root aplikasi.
include '../../config/db.php'; 

// --- Pengecekan Sesi dan Otentikasi ---
// Cek apakah user sudah login DAN role-nya adalah 'admin'.
// Jika tidak, arahkan ke halaman login di root aplikasi.
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: " . $root_app_url . "login.php"); 
    exit;
}

// Tambahkan pengecekan koneksi database untuk debugging awal
if (!isset($conn) || !$conn) {
    die("Error: Koneksi database belum terbentuk atau gagal. Pastikan file db.php benar.");
}

// Ambil rekap pemeriksaan per bulan menggunakan Prepared Statement
$stmt = $conn->prepare("
    SELECT 
        DATE_FORMAT(p.tanggal, '%Y-%m') AS bulan,
        COUNT(p.id) AS total_pemeriksaan,
        COUNT(DISTINCT p.id_balita) AS balita_diperiksa,
        ROUND(AVG(p.berat), 2) AS rata_berat,
        ROUND(AVG(p.tinggi), 2) AS rata_tinggi
    FROM pemeriksaan p
    GROUP BY bulan
    ORDER BY bulan DESC
");

if ($stmt === false) {
    die("Error preparing statement: " . $conn->error);
}

$stmt->execute();
$data = $stmt->get_result(); // Hasil query data laporan

$stmt->close(); // Tutup statement setelah mendapatkan hasil
?>

<!DOCTYPE html>
<html>
<head>
    <title>Laporan Pemeriksaan - POSYANDU BALITAKU</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* CSS Styling dari template Anda sebelumnya, disesuaikan dengan Poppins dan warna tema */
        :root {
          --primary: #2a9d8f;
          --secondary: #264653;
          --accent: #e9c46a;
          --light: #f8f9fa;
          --danger: #e76f51;
          --info-blue: #2196F3; /* Warna biru untuk info-box */
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f1f8f7; /* Menggunakan warna dari template */
            margin: 0;
            padding: 20px;
            color: #333;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
        }
        header {
            background-color: var(--primary);
            color: white;
            padding: 15px 20px;
            border-radius: 8px 8px 0 0;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        h1 {
            margin: 0;
            font-size: 24px;
            color: white; /* H1 di header putih */
        }
        h2 {
            color: var(--secondary);
            border-bottom: 2px solid var(--accent); /* Warna accent dari template */
            padding-bottom: 10px;
            margin-top: 30px;
        }
        .logo {
            display: flex;
            align-items: center;
        }
        .logo i { /* Tambahkan styling untuk ikon logo */
            font-size: 2rem; 
            margin-right: 15px; 
            color: white;
        }
        .export-buttons {
            margin-bottom: 20px;
            display: flex; /* Agar tombol sejajar */
            gap: 10px; /* Jarak antar tombol */
            flex-wrap: wrap; /* Agar responsif di layar kecil */
        }
        .btn {
            display: inline-block;
            padding: 8px 15px;
            background-color: var(--primary);
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s;
            white-space: nowrap; /* Mencegah teks putus */
        }
        .btn:hover {
            background-color: #217a70; /* Warna hover primary */
        }
        /* Gaya untuk tombol kembali ke dashboard */
        .btn.back-to-dashboard {
            background-color: #4CAF50; /* Hijau yang sama dengan balita.php */
        }
        .btn.back-to-dashboard:hover {
            background-color: #45a049;
        }
        .info-box {
            background-color: var(--light); /* Menggunakan warna light dari root */
            border-left: 4px solid var(--info-blue); /* Menggunakan warna info-blue dari root */
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 0 5px 5px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th {
            background-color: var(--primary);
            color: white;
            padding: 12px;
            text-align: center;
        }
        td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
            text-align: center;
        }
        tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        tr:hover {
            background-color: #e6f7e6;
        }
        .highlight {
            font-weight: bold;
            color: var(--danger); /* Menggunakan warna danger dari root */
        }
        footer {
            text-align: center;
            margin-top: 20px;
            padding: 10px;
            color: #666;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <div class="logo">
                <i class="fas fa-baby-carriage"></i> <h1>POSYANDU BALITAKU</h1>
            </div>
            <div>
                Selamat Datang, Administrator
            </div>
        </header>
        
        <h2>📊 Laporan Rekap Pemeriksaan per Bulan</h2>
        
        <div class="info-box">
            <p>Laporan ini menampilkan rekapitulasi kegiatan pemeriksaan balita di Posyandu Balitaku setiap bulannya. Data mencakup jumlah pemeriksaan, balita yang diperiksa, serta perkembangan rata-rata berat dan tinggi badan.</p>
        </div>
        
        <div class="export-buttons">
            <a href="<?= $root_app_url ?>export/export_excel.php?tipe=laporan" class="btn">📄 Export ke Excel</a>
              <a href="<?= $root_app_url ?>export/export_pdf.php?tipe=balita" target="_blank" class="btn">📑 Export ke PDF</a>
            <a href="<?= $base_url_admin ?>index.php" class="btn back-to-dashboard">⬅️ Kembali ke Dashboard</a>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Bulan</th>
                    <th>Total Pemeriksaan</th>
                    <th>Balita Diperiksa</th>
                    <th>Rata-rata Berat (kg)</th>
                    <th>Rata-rata Tinggi (cm)</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($data->num_rows > 0): ?>
                    <?php while ($row = $data->fetch_assoc()): ?>
                        <tr>
                            <td><?= date('F Y', strtotime($row['bulan'])) ?></td>
                            <td><?= htmlspecialchars($row['total_pemeriksaan']) ?></td>
                            <td><?= htmlspecialchars($row['balita_diperiksa']) ?></td>
                            <td><?= htmlspecialchars(number_format($row['rata_berat'], 2)) ?></td>
                            <td><?= htmlspecialchars(number_format($row['rata_tinggi'], 2)) ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5">Tidak ada data pemeriksaan yang tersedia untuk laporan.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        
        <footer>
            <p>Pos Pelayanan Terpadu (Posyandu) - Membangun Generasi Sehat sejak Dini | © <?= date('Y') ?> Sistem Informasi Posyandu. All rights reserved.</p>
        </footer>
    </div>
</body>
</html>
<?php 
// Tutup koneksi database di akhir file setelah semua operasi selesai
if (isset($conn) && $conn) {
    $conn->close();
}
?>