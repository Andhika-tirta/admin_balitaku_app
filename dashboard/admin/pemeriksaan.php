<?php
// PASTIKAN INI ADALAH BARIS PERTAMA. HANYA SATU KALI session_start().
session_start(); 

// Aktifkan error reporting untuk debugging.
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Sertakan file konfigurasi path URL.
// Path ini relatif dari pemeriksaan.php (di dashboard/admin/) naik dua level ke root aplikasi.
include '../../config/paths.php'; 

// Sertakan file koneksi database.
// Path ini relatif dari pemeriksaan.php (di dashboard/admin/) naik dua level ke root aplikasi.
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

// Ambil data pemeriksaan dan join dengan balita
// Menggunakan prepared statement meskipun tanpa parameter input langsung,
// ini adalah praktik baik untuk query yang lebih kompleks.
$stmt = $conn->prepare("
    SELECT p.id, p.tanggal, p.berat, p.tinggi, p.lingkar_kepala, p.catatan, b.nama AS nama_balita 
    FROM pemeriksaan p
    JOIN balita b ON p.id_balita = b.id
    ORDER BY p.tanggal DESC
");

if ($stmt === false) {
    die("Error preparing statement: " . $conn->error);
}

$stmt->execute();
$result = $stmt->get_result();
$stmt->close(); // Tutup statement setelah mendapatkan hasil

?>

<!DOCTYPE html>
<html>
<head>
  <title>Riwayat Pemeriksaan Balita - POSYANDU BALITAKU</title>
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
      --blue-btn: #2196F3; /* Warna tombol biru dari kode Anda */
      --blue-btn-hover: #0b7dda;
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
    .logo {
      display: flex;
      align-items: center;
    }
    .logo i { /* Tambahkan styling untuk ikon logo */
        font-size: 2rem; 
        margin-right: 15px; 
        color: white;
    }
    .action-buttons {
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
    .btn-add {
      background-color: var(--blue-btn); /* Warna tombol tambah */
    }
    .btn-add:hover {
      background-color: var(--blue-btn-hover);
    }
    /* Gaya untuk tombol kembali ke dashboard */
    .btn.back-to-dashboard {
        background-color: #4CAF50; /* Hijau yang sama dengan balita.php */
    }
    .btn.back-to-dashboard:hover {
        background-color: #45a049;
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
      text-align: left;
    }
    td {
      padding: 10px;
      border-bottom: 1px solid #ddd;
    }
    tr:nth-child(even) {
      background-color: #f2f2f2;
    }
    tr:hover {
      background-color: #e6f7e6;
    }
    .action-link {
      color: #2196F3;
      text-decoration: none;
      margin: 0 5px;
      font-weight: 500;
    }
    .action-link.delete { /* Jika ada link delete, gunakan warna danger */
      color: var(--danger);
    }
    .action-link:hover {
      text-decoration: underline;
    }
    .numeric-cell {
      text-align: right;
      font-family: 'Courier New', monospace;
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
    
    <h2>📋 Riwayat Pemeriksaan Balita</h2>
    <p>Berikut adalah rekam medis pemeriksaan balita di Posyandu Balitaku:</p>
    
    <div class="action-buttons">
      <a href="<?= $root_app_url ?>export/export_excel.php?tipe=pemeriksaan" class="btn">📄 Export Excel</a>
      <a href="<?= $root_app_url ?>export/export_pdf.php?tipe=pemeriksaan" target="_blank" class="btn">📑 Export PDF</a>
      <a href="<?= $base_url_admin ?>index.php" class="btn back-to-dashboard">⬅️ Kembali ke Dashboard</a>
    </div>

    <table>
      <thead>
        <tr>
          <th>Nama Balita</th>
          <th>Tanggal Periksa</th>
          <th>Berat (kg)</th>
          <th>Tinggi (cm)</th>
          <th>Lingkar Kepala (cm)</th>
          <th>Catatan</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($result->num_rows > 0): ?>
          <?php while($row = $result->fetch_assoc()): ?>
            <tr>
              <td><?= htmlspecialchars($row['nama_balita']) ?></td>
              <td><?= date('d-m-Y', strtotime($row['tanggal'])) ?></td>
              <td class="numeric-cell"><?= number_format($row['berat'], 1) ?></td>
              <td class="numeric-cell"><?= number_format($row['tinggi'], 1) ?></td>
              <td class="numeric-cell"><?= number_format($row['lingkar_kepala'], 1) ?></td>
              <td><?= htmlspecialchars($row['catatan']) ?></td>
              <td>
                <a href="<?= $base_url_admin ?>pemeriksaan_edit.php?id=<?= $row['id'] ?>" class="action-link">✏️ Edit</a>
                </td>
            </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr><td colspan="7">Tidak ada data pemeriksaan ditemukan.</td></tr>
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