<?php
// PASTIKAN INI ADALAH BARIS PERTAMA. HANYA SATU KALI session_start().
session_start(); 

// Aktifkan error reporting untuk debugging.
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Sertakan file konfigurasi path URL.
// Path ini relatif dari imunisasi.php (di dashboard/admin/) naik dua level ke root aplikasi.
include '../../config/paths.php'; 

// Sertakan file koneksi database.
// Path ini relatif dari imunisasi.php (di dashboard/admin/) naik dua level ke root aplikasi.
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

// Ambil semua data imunisasi menggunakan Prepared Statement
$stmt_data = $conn->prepare("
    SELECT i.*, b.nama AS nama_balita 
    FROM imunisasi i 
    JOIN balita b ON i.id_balita = b.id 
    ORDER BY i.tanggal DESC
");

if ($stmt_data === false) {
    die("Error preparing data statement: " . $conn->error);
}
$stmt_data->execute();
$data = $stmt_data->get_result(); // Hasil query data imunisasi

// Ambil data balita untuk dropdown tambah menggunakan Prepared Statement
$stmt_balita = $conn->prepare("SELECT id, nama FROM balita ORDER BY nama ASC"); // Tambahkan ORDER BY untuk balita
if ($stmt_balita === false) {
    die("Error preparing balita statement: " . $conn->error);
}
$stmt_balita->execute();
$balita = $stmt_balita->get_result(); // Hasil query data balita

// Proses Tambah
if (isset($_POST['simpan'])) {
    // Ambil data dari form
    $id_balita = $_POST['id_balita'];
    $tanggal = $_POST['tanggal'];
    $jenis = $_POST['jenis'];
    $keterangan = $_POST['keterangan'];

    // Prepared Statement untuk INSERT
    $stmt_insert = $conn->prepare("INSERT INTO imunisasi (id_balita, tanggal, jenis, keterangan) VALUES (?, ?, ?, ?)");
    
    // Periksa jika prepared statement gagal
    if ($stmt_insert === false) {
        die("Error preparing insert statement: " . $conn->error);
    }

    // Bind parameter dan eksekusi
    // 's' = string, 'i' = integer (untuk id_balita jika kolomnya INT)
    // Asumsi id_balita adalah integer, tanggal, jenis, keterangan adalah string.
    $stmt_insert->bind_param("isss", $id_balita, $tanggal, $jenis, $keterangan);
    
    if ($stmt_insert->execute()) {
        $_SESSION['success_message'] = "Data imunisasi berhasil ditambahkan!";
    } else {
        $_SESSION['error_message'] = "Gagal menambahkan data imunisasi: " . $stmt_insert->error;
    }
    
    $stmt_insert->close(); // Tutup statement insert
    header("Location: imunisasi.php"); // Redirect untuk menghindari resubmission form
    exit;
}

// Tutup statement setelah mendapatkan hasil
$stmt_data->close(); 
$stmt_balita->close(); 
?>

<!DOCTYPE html>
<html>
<head>
    <title>Data Imunisasi - POSYANDU BALITAKU</title>
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
        h2, h3 {
            color: var(--secondary);
            /* border-bottom: 2px solid var(--accent); */ /* Hapus atau sesuaikan jika tidak diinginkan */
            padding-bottom: 10px;
            margin-top: 30px;
        }
        h2 {
             border-bottom: 2px solid var(--accent); /* Warna accent dari template */
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
        .form-container {
            background-color: var(--light); /* Menggunakan warna dari template */
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            border-left: 4px solid var(--primary); /* Menggunakan warna dari template */
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
        }
        select, input[type="date"], input[type="text"], textarea {
            width: calc(100% - 22px); /* Mengkompensasi padding dan border */
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
            box-sizing: border-box; /* Agar padding tidak menambah lebar total */
        }
        textarea {
            height: 80px;
            resize: vertical;
        }
        .btn {
            background-color: var(--primary);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.3s;
            text-decoration: none; /* Untuk tombol yang juga link */
            display: inline-block; /* Agar bisa sejajar dengan tombol lain */
            margin-right: 10px; /* Jarak antar tombol */
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
            color: var(--info-blue);
            text-decoration: none;
            margin: 0 5px;
            font-weight: 500;
        }
        .action-link.delete {
            color: var(--danger);
        }
        .action-link:hover {
            text-decoration: underline;
        }
        footer {
            text-align: center;
            margin-top: 20px;
            padding: 10px;
            color: #666;
            font-size: 12px;
        }
        .info-box {
            background-color: var(--light); /* Menggunakan warna light dari root */
            border-left: 4px solid var(--info-blue); /* Menggunakan warna info-blue dari root */
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 0 5px 5px 0;
        }
        .message {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 5px;
            font-weight: bold;
        }
        .message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
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
        
        <h2>💉 Data Imunisasi Balita</h2>

        <?php
        // Tampilkan pesan sukses atau error jika ada
        if (isset($_SESSION['success_message'])) {
            echo '<div class="message success">' . $_SESSION['success_message'] . '</div>';
            unset($_SESSION['success_message']); // Hapus pesan setelah ditampilkan
        }
        if (isset($_SESSION['error_message'])) {
            echo '<div class="message error">' . $_SESSION['error_message'] . '</div>';
            unset($_SESSION['error_message']); // Hapus pesan setelah ditampilkan
        }
        ?>
        
        <div class="info-box">
            <p>Halaman ini mencatat seluruh riwayat imunisasi balita di Posyandu Balitaku. Pastikan data imunisasi selalu diperbarui untuk memantau kesehatan balita.</p>
        </div>
                <a href="<?= $base_url_admin ?>index.php" class="btn back-to-dashboard">⬅️ Kembali ke Dashboard</a>
            </form>
        </div>
        
        <h3>📋 Riwayat Imunisasi</h3>
        <table>
            <thead>
                <tr>
                    <th>Nama Balita</th>
                    <th>Tanggal</th>
                    <th>Jenis Imunisasi</th>
                    <th>Keterangan</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($data->num_rows > 0): ?>
                    <?php while ($row = $data->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['nama_balita']) ?></td>
                            <td><?= date('d-m-Y', strtotime($row['tanggal'])) ?></td>
                            <td><?= htmlspecialchars($row['jenis']) ?></td>
                            <td><?= htmlspecialchars($row['keterangan']) ?></td>
                            <td>
                                <a href="<?= $base_url_admin ?>imunisasi_edit.php?id=<?= htmlspecialchars($row['id']) ?>" class="action-link">✏️ Edit</a>
                                <a href="<?= $base_url_admin ?>imunisasi_delete.php?id=<?= htmlspecialchars($row['id']) ?>" class="action-link delete" onclick="return confirm('Apakah Anda yakin ingin menghapus data imunisasi ini?')">🗑️ Hapus</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5">Tidak ada data imunisasi yang tersedia.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        
        <footer>
            <p>Pos Pelayanan Terpadu (Posyandu) - Membangun Generasi Sehat sejak Dini | © <?= date('Y') ?> Sistem Informasi Posyandu. All rights reserved.</p>
        </footer>
    </div>
    
    <script>
        // Set default tanggal ke hari ini
        document.getElementById('tanggal').valueAsDate = new Date();
    </script>
</body>
</html>
<?php 
// Tutup koneksi database di akhir file setelah semua operasi selesai
if (isset($conn) && $conn) {
    $conn->close();
}
?>