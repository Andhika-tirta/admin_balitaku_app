<?php
// PASTIKAN INI ADALAH BARIS PERTAMA. HANYA SATU KALI session_start().
session_start();

// Aktifkan error reporting untuk debugging.
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Sertakan file konfigurasi path URL.
// Path ini relatif dari pemeriksaan_edit.php (di dashboard/admin/) naik dua level ke root aplikasi.
include '../../config/paths.php';

// Sertakan file koneksi database.
// Path ini relatif dari pemeriksaan_edit.php (di dashboard/admin/) naik dua level ke root aplikasi.
include '../../config/db.php';

// --- Pengecekan Sesi dan Otentikasi ---
// Cek apakah user sudah login DAN role-nya adalah 'admin'.
// Jika tidak, arahkan ke halaman login di root aplikasi dengan pesan error.
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') { // Menggunakan 'role' sesuai standar aplikasi
    $_SESSION['error_message'] = "Anda tidak memiliki izin untuk mengakses halaman ini.";
    header("Location: " . $root_app_url . "login.php");
    exit;
}

// Tambahkan pengecekan koneksi database untuk debugging awal
if (!isset($conn) || !$conn) {
    $_SESSION['error_message'] = "Koneksi database gagal. Silakan coba lagi.";
    header("Location: " . $base_url_admin . "pemeriksaan.php"); // Redirect ke daftar pemeriksaan
    exit;
}

$id_pemeriksaan = null;
$pemeriksaan_data = []; // Akan menyimpan data pemeriksaan yang akan diedit
$error_message = ''; // Untuk pesan error di halaman ini

// --- Ambil ID Pemeriksaan dari URL dan Data Lama ---
if (isset($_GET['id'])) {
    $id_pemeriksaan = filter_var($_GET['id'], FILTER_VALIDATE_INT);

    if ($id_pemeriksaan === false) {
        $_SESSION['error_message'] = "ID pemeriksaan tidak valid.";
        header("Location: " . $base_url_admin . "pemeriksaan.php");
        exit;
    }

    // Ambil data pemeriksaan yang akan diedit dengan Prepared Statement
    $stmt_select = $conn->prepare("SELECT id, id_balita, tanggal, berat, tinggi, lingkar_kepala, catatan FROM pemeriksaan WHERE id = ?");
    
    if ($stmt_select === false) {
        $_SESSION['error_message'] = "Gagal menyiapkan query select data pemeriksaan: " . $conn->error;
        header("Location: " . $base_url_admin . "pemeriksaan.php");
        exit;
    }
    
    $stmt_select->bind_param("i", $id_pemeriksaan);
    $stmt_select->execute();
    $result_select = $stmt_select->get_result();

    if ($result_select->num_rows > 0) {
        $pemeriksaan_data = $result_select->fetch_assoc();
    } else {
        $_SESSION['error_message'] = "Data pemeriksaan dengan ID tersebut tidak ditemukan.";
        header("Location: " . $base_url_admin . "pemeriksaan.php");
        exit;
    }
    $stmt_select->close();

} else {
    $_SESSION['error_message'] = "ID pemeriksaan tidak diberikan untuk diedit.";
    header("Location: " . $base_url_admin . "pemeriksaan.php");
    exit;
}

// Ambil data balita untuk dropdown (Sama seperti pemeriksaan_add.php)
$stmt_balita = $conn->prepare("SELECT id, nama FROM balita ORDER BY nama ASC");
if ($stmt_balita === false) {
    die("Error preparing balita select statement: " . $conn->error);
}
$stmt_balita->execute();
$balita_list = $stmt_balita->get_result();
$stmt_balita->close();

// --- Proses Update Data (Ketika Form Disubmit) ---
if (isset($_POST['update'])) {
    // Ambil dan sanitasi data dari form
    // Gunakan id_pemeriksaan dari GET request awal untuk UPDATE WHERE
    $id_balita      = filter_var(trim($_POST['id_balita']), FILTER_VALIDATE_INT);
    $tanggal        = trim($_POST['tanggal']);
    $berat          = filter_var(trim($_POST['berat']), FILTER_VALIDATE_FLOAT);
    $tinggi         = filter_var(trim($_POST['tinggi']), FILTER_VALIDATE_FLOAT);
    $lingkar_kepala = filter_var(trim($_POST['lingkar_kepala']), FILTER_VALIDATE_FLOAT);
    $catatan        = trim($_POST['catatan']);

    // Validasi input sisi server
    $validation_errors = [];

    if ($id_balita === false || empty($tanggal) || $berat === false || $tinggi === false || $lingkar_kepala === false) {
        $validation_errors[] = "Semua field angka dan tanggal harus diisi dengan format yang benar.";
    }
    if ($berat < 0 || $tinggi < 0 || $lingkar_kepala < 0) {
        $validation_errors[] = "Berat, tinggi, dan lingkar kepala tidak boleh negatif.";
    }
    if (!empty($tanggal) && strtotime($tanggal) > time()) {
        $validation_errors[] = "Tanggal pemeriksaan tidak boleh di masa depan.";
    }

    if (!empty($validation_errors)) {
        $error_message = implode("<br>", $validation_errors);
    } else {
        // Prepared Statement untuk UPDATE data pemeriksaan
        $stmt_update = $conn->prepare("UPDATE pemeriksaan SET 
            id_balita = ?, tanggal = ?, berat = ?, tinggi = ?, lingkar_kepala = ?, catatan = ? 
            WHERE id = ?");

        if ($stmt_update === false) {
            $error_message = "Gagal menyiapkan query update: " . $conn->error;
        } else {
            // Bind parameter: isdddsi -> (id_balita, tanggal, berat, tinggi, lingkar_kepala, catatan, id_pemeriksaan)
            $stmt_update->bind_param("isdddsi", $id_balita, $tanggal, $berat, $tinggi, $lingkar_kepala, $catatan, $id_pemeriksaan);

            if ($stmt_update->execute()) {
                $_SESSION['success_message'] = "Data pemeriksaan berhasil diperbarui!";
                $stmt_update->close(); // Tutup statement sebelum redirect
                header("Location: " . $base_url_admin . "pemeriksaan.php");
                exit;
            } else {
                $error_message = "Gagal memperbarui data pemeriksaan: " . $stmt_update->error;
            }
            $stmt_update->close(); // Tutup statement jika execute() gagal
        }
    }
    // Jika ada error, isi ulang data form dengan nilai yang baru disubmit
    $pemeriksaan_data['id_balita'] = $id_balita;
    $pemeriksaan_data['tanggal'] = $tanggal;
    $pemeriksaan_data['berat'] = $berat;
    $pemeriksaan_data['tinggi'] = $tinggi;
    $pemeriksaan_data['lingkar_kepala'] = $lingkar_kepala;
    $pemeriksaan_data['catatan'] = $catatan;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Pemeriksaan - POSYANDU BALITAKU</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Menggunakan CSS yang sama untuk konsistensi */
        :root {
          --primary: #2a9d8f;
          --secondary: #264653;
          --accent: #e9c46a;
          --light: #f8f9fa;
          --danger: #e76f51;
          --info-blue: #2196F3;
          --success-green: #28a745;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f1f8f7;
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
            color: white;
        }
        h2 {
            color: var(--secondary);
            border-bottom: 2px solid var(--accent);
            padding-bottom: 10px;
            margin-top: 30px;
        }
        .logo {
            display: flex;
            align-items: center;
        }
        .logo i {
            font-size: 2rem;
            margin-right: 15px;
            color: white;
        }
        .form-container {
            background-color: var(--light);
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid var(--primary);
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
        input[type="date"],
        input[type="number"],
        select,
        textarea {
            width: calc(100% - 22px);
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
            box-sizing: border-box;
        }
        input[type="number"] {
            max-width: 200px;
        }
        textarea {
            height: 100px;
            resize: vertical;
        }
        .btn {
            background-color: var(--primary);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s;
            display: inline-block;
            margin-right: 10px;
        }
        .btn:hover {
            background-color: #217a70;
        }
        .btn-back {
            display: inline-block;
            padding: 8px 15px;
            background-color: #6c757d;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 10px;
            transition: background-color 0.3s;
        }
        .btn-back:hover {
            background-color: #5a6268;
        }
        .notification {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 5px;
            font-weight: bold;
        }
        .notification.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .notification.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        footer {
            text-align: center;
            margin-top: 20px;
            padding: 10px;
            color: #666;
            font-size: 12px;
        }
        .info-box {
            background-color: #e8f4f8;
            border-left: 4px solid #2196F3;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 0 5px 5px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <div class="logo">
                <i class="fas fa-stethoscope"></i> <h1>POSYANDU BALITAKU</h1>
            </div>
            <div>
                Selamat Datang, Administrator
            </div>
        </header>
        
        <h2>📝 Edit Data Pemeriksaan Balita</h2>
        
        <?php 
        // Tampilkan pesan sukses jika ada
        if (isset($_SESSION['success_message'])) {
            echo '<div class="notification success">' . htmlspecialchars($_SESSION['success_message']) . '</div>';
            unset($_SESSION['success_message']); // Hapus pesan setelah ditampilkan
        }
        // Tampilkan pesan error jika ada
        if (!empty($error_message)): ?>
            <div class="notification error"><?= htmlspecialchars($error_message) ?></div>
        <?php endif; ?>
        
        <div class="info-box">
            <p>Silakan perbarui data pemeriksaan balita. Pastikan semua informasi yang dimasukkan akurat untuk pemantauan perkembangan balita.</p>
        </div>
        
        <div class="form-container">
            <form method="POST">
                <input type="hidden" name="id_pemeriksaan" value="<?= htmlspecialchars($id_pemeriksaan) ?>">

                <div class="form-group">
                    <label for="id_balita">Nama Balita:</label>
                    <select name="id_balita" id="id_balita" required>
                        <option value="">-- Pilih Balita --</option>
                        <?php 
                        // Pastikan $balita_list adalah hasil dari query dan bukan null/false
                        // Reset pointer agar loop bisa dimulai dari awal jika ini adalah POST request dengan error
                        if ($balita_list && $balita_list->num_rows > 0) {
                            $balita_list->data_seek(0);
                            while ($b = $balita_list->fetch_assoc()): ?>
                                <option value="<?= htmlspecialchars($b['id']) ?>" 
                                    <?= ($b['id'] == $pemeriksaan_data['id_balita']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($b['nama']) ?>
                                </option>
                            <?php endwhile;
                        } else {
                            echo '<option value="" disabled>Tidak ada data balita tersedia.</option>';
                        }
                        ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="tanggal">Tanggal Pemeriksaan:</label>
                    <input type="date" name="tanggal" id="tanggal" 
                           value="<?= htmlspecialchars($pemeriksaan_data['tanggal']) ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="berat">Berat Badan (kg):</label>
                    <input type="number" step="0.1" min="0" name="berat" id="berat" 
                           value="<?= htmlspecialchars($pemeriksaan_data['berat']) ?>" required placeholder="Contoh: 8.5">
                </div>
                
                <div class="form-group">
                    <label for="tinggi">Tinggi Badan (cm):</label>
                    <input type="number" step="0.1" min="0" name="tinggi" id="tinggi" 
                           value="<?= htmlspecialchars($pemeriksaan_data['tinggi']) ?>" required placeholder="Contoh: 70.3">
                </div>
                
                <div class="form-group">
                    <label for="lingkar_kepala">Lingkar Kepala (cm):</label>
                    <input type="number" step="0.1" min="0" name="lingkar_kepala" id="lingkar_kepala" 
                           value="<?= htmlspecialchars($pemeriksaan_data['lingkar_kepala']) ?>" required placeholder="Contoh: 45.0">
                </div>
                
                <div class="form-group">
                    <label for="catatan">Catatan Pemeriksaan:</label>
                    <textarea name="catatan" id="catatan" 
                              placeholder="Masukkan catatan tambahan atau keluhan..."><?= htmlspecialchars($pemeriksaan_data['catatan']) ?></textarea>
                </div>
                
                <button type="submit" name="update" class="btn">💾 Simpan Perubahan</button>
            </form>
        </div>
        
        <a href="<?= $base_url_admin ?>pemeriksaan.php" class="btn-back">← Kembali ke Data Pemeriksaan</a>
        
        <footer>
            <p>Pos Pelayanan Terpadu (Posyandu) - Membangun Generasi Sehat sejak Dini | © <?= date('Y') ?> Sistem Informasi Posyandu. All rights reserved.</p>
        </footer>
    </div>
    
    <script>
        // Validasi input angka (sisi klien)
        document.querySelectorAll('input[type="number"]').forEach(input => {
            input.addEventListener('change', function() {
                if (parseFloat(this.value) < 0) {
                    this.value = 0;
                }
                this.value = parseFloat(this.value).toFixed(1);
            });
        });
    </script>
</body>
</html>
<?php
// Tutup koneksi database di akhir file setelah semua operasi selesai
if (isset($conn) && $conn) {
    $conn->close();
}
?>