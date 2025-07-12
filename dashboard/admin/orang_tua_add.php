<?php
// PASTIKAN INI ADALAH BARIS PERTAMA. HANYA SATU KALI session_start().
session_start();

// Aktifkan error reporting untuk debugging.
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Sertakan file konfigurasi path URL.
// Path ini relatif dari orang_tua_add.php (di dashboard/admin/) naik dua level ke root aplikasi.
include '../../config/paths.php';

// Sertakan file koneksi database.
// Path ini relatif dari orang_tua_add.php (di dashboard/admin/) naik dua level ke root aplikasi.
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

// Inisialisasi variabel untuk pesan error
$error = '';

if (isset($_POST['simpan'])) {
    $nama   = trim($_POST['nama']);
    $nik    = trim($_POST['nik']);
    $no_hp  = trim($_POST['no_hp']);
    $alamat = trim($_POST['alamat']);

    // --- Validasi Sisi Server ---
    if (empty($nama) || empty($nik) || empty($no_hp) || empty($alamat)) {
        $_SESSION['error_message'] = "Semua field wajib diisi.";
        header("Location: orang_tua_add.php"); // Redirect kembali ke halaman ini
        exit;
    }

    if (!ctype_digit($nik) || strlen($nik) !== 16) {
        $_SESSION['error_message'] = "NIK harus terdiri dari 16 digit angka.";
        header("Location: orang_tua_add.php"); // Redirect kembali ke halaman ini
        exit;
    }

    // --- Cek Duplikat NIK dengan Prepared Statement ---
    $stmt_check = $conn->prepare("SELECT COUNT(*) FROM orang_tua WHERE nik = ?");
    if ($stmt_check === false) {
        $_SESSION['error_message'] = "Gagal menyiapkan query cek NIK: " . $conn->error;
        header("Location: orang_tua_add.php");
        exit;
    }
    $stmt_check->bind_param("s", $nik);
    $stmt_check->execute();
    $stmt_check->bind_result($count_nik);
    $stmt_check->fetch();
    $stmt_check->close();

    if ($count_nik > 0) {
        $_SESSION['error_message'] = "NIK sudah terdaftar. Harap gunakan NIK lain.";
        header("Location: orang_tua_add.php"); // Redirect kembali ke halaman ini
        exit;
    } else {
        // --- Insert Data dengan Prepared Statement ---
        $stmt_insert = $conn->prepare("INSERT INTO orang_tua (nama, nik, no_hp, alamat) VALUES (?, ?, ?, ?)");
        if ($stmt_insert === false) {
            $_SESSION['error_message'] = "Gagal menyiapkan query insert: " . $conn->error;
            header("Location: orang_tua_add.php");
            exit;
        }

        $stmt_insert->bind_param("ssss", $nama, $nik, $no_hp, $alamat);

        if ($stmt_insert->execute()) {
            $_SESSION['success_message'] = "Data orang tua berhasil ditambahkan!";
            $stmt_insert->close(); // Tutup statement sebelum redirect
            header("Location: " . $base_url_admin . "orang_tua.php");
            exit;
        } else {
            $_SESSION['error_message'] = "Gagal menyimpan data: " . $stmt_insert->error;
            $stmt_insert->close(); // Tutup statement sebelum redirect
            header("Location: orang_tua_add.php"); // Redirect kembali ke halaman ini
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Tambah Orang Tua - POSYANDU BALITAKU</title>
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
          --info-blue: #2196F3;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f1f8f7; /* Menggunakan warna dari template */
            margin: 0;
            padding: 20px;
            color: #333;
        }
        .container {
            max-width: 800px;
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
        .form-container {
            background-color: var(--light); /* Menggunakan warna dari template */
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid var(--primary); /* Menggunakan warna dari template */
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: var(--secondary);
        }
        input[type="text"],
        textarea {
            width: calc(100% - 22px); /* Mengkompensasi padding dan border */
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
            box-sizing: border-box;
        }
        textarea {
            resize: vertical;
            height: 90px;
        }
        .btn {
            background-color: var(--primary);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .btn:hover {
            background-color: #217a70; /* Warna hover primary */
        }
        .btn-back {
            display: inline-block;
            background-color: #6c757d; /* Warna abu-abu */
            color: white;
            padding: 10px 15px;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 10px;
            transition: background-color 0.3s;
        }
        .btn-back:hover {
            background-color: #5a6268;
        }
        .error-message, .success-message {
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
            font-weight: bold;
        }
        .error-message {
            color: var(--danger);
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
        }
        .success-message {
            color: #155724;
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
        }
        footer {
            text-align: center;
            margin-top: 20px;
            padding: 10px;
            font-size: 12px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <div class="logo">
                <i class="fas fa-child"></i> <h1>POSYANDU BALITAKU</h1>
            </div>
            <span>Selamat Datang, Administrator</span>
        </header>

        <h2>👪 Tambah Data Orang Tua / Wali</h2>

        <?php
        // Tampilkan pesan sukses jika ada
        if (isset($_SESSION['success_message'])) {
            echo '<div class="success-message">' . $_SESSION['success_message'] . '</div>';
            unset($_SESSION['success_message']); // Hapus pesan setelah ditampilkan
        }
        // Tampilkan pesan error jika ada
        if (isset($_SESSION['error_message'])) {
            echo '<div class="error-message">' . $_SESSION['error_message'] . '</div>';
            unset($_SESSION['error_message']); // Hapus pesan setelah ditampilkan
        }
        ?>

        <div class="form-container">
            <form method="POST">
                <div class="form-group">
                    <label for="nama">Nama Lengkap:</label>
                    <input type="text" name="nama" id="nama" required placeholder="Contoh: Budi Santoso">
                </div>

                <div class="form-group">
                    <label for="nik">NIK (16 digit):</label>
                    <input type="text" name="nik" id="nik" maxlength="16" minlength="16" pattern="\d{16}" 
                           title="NIK harus 16 digit angka" required placeholder="Contoh: 320xxxxxxxxxxxxx">
                </div>

                <div class="form-group">
                    <label for="no_hp">No HP/WA:</label>
                    <input type="text" name="no_hp" id="no_hp" placeholder="Contoh: 081234567890">
                </div>

                <div class="form-group">
                    <label for="alamat">Alamat:</label>
                    <textarea name="alamat" id="alamat" placeholder="Contoh: Jl. Merdeka No. 123, RT 01/RW 02, Kel. Sukamaju, Kec. Cikoneng"></textarea>
                </div>

                <button type="submit" name="simpan" class="btn">💾 Simpan</button>
            </form>
        </div>

        <a href="<?= $base_url_admin ?>orang_tua.php" class="btn-back">← Kembali ke Data Orang Tua</a>

        <footer>
            Pos Pelayanan Terpadu (Posyandu) - Membangun Generasi Sehat sejak Dini | © <?= date('Y') ?> Sistem Informasi Posyandu. All rights reserved.
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