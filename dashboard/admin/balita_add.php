<?php
session_start();

// Aktifkan error reporting untuk debugging.
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Sertakan konfigurasi
include '../../config/paths.php';
include '../../config/db.php';

// Cek autentikasi admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: " . $root_app_url . "login.php");
    exit;
}

// Ambil data orang tua
$stmt_ortu = $conn->prepare("SELECT id, nama FROM orang_tua ORDER BY nama ASC");
if (!$stmt_ortu) {
    die("Gagal menyiapkan statement: " . $conn->error);
}
$stmt_ortu->execute();
$ortu = $stmt_ortu->get_result();
$stmt_ortu->close();

if (isset($_POST['simpan'])) {
    $nama = trim($_POST['nama']);
    $tgl_lahir = trim($_POST['tgl_lahir']);
    $jk = trim($_POST['jenis_kelamin']);
    $alamat = trim($_POST['alamat']);
    $id_orangtua = filter_var(trim($_POST['id_orangtua']), FILTER_VALIDATE_INT);

    if (empty($nama) || empty($tgl_lahir) || empty($jk) || empty($alamat) || $id_orangtua === false) {
        $_SESSION['error_message'] = "Semua field harus diisi dengan benar.";
        header("Location: balita_add.php");
        exit;
    }

    $stmt_insert = $conn->prepare("INSERT INTO balita (nama, tgl_lahir, jenis_kelamin, alamat, id_orangtua) VALUES (?, ?, ?, ?, ?)");
    if (!$stmt_insert) {
        $_SESSION['error_message'] = "Gagal menyiapkan query: " . $conn->error;
        header("Location: balita_add.php");
        exit;
    }

    $stmt_insert->bind_param("ssssi", $nama, $tgl_lahir, $jk, $alamat, $id_orangtua);
    
    if ($stmt_insert->execute()) {
        $_SESSION['success_message'] = "Data balita berhasil ditambahkan!";
        $stmt_insert->close();
        header("Location: " . $base_url_admin . "balita.php");
        exit;
    } else {
        $_SESSION['error_message'] = "Gagal menambahkan data: " . $stmt_insert->error;
        $stmt_insert->close();
        header("Location: balita_add.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Tambah Data Balita - POSYANDU BALITAKU</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary: #2a9d8f;
            --secondary: #264653;
            --accent: #e9c46a;
            --light: #f8f9fa;
            --danger: #e76f51;
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
            margin: auto;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
        }
        header {
            background: var(--primary);
            color: white;
            padding: 15px 20px;
            border-radius: 8px 8px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        h1 { margin: 0; font-size: 24px; }
        h2 {
            color: var(--secondary);
            border-bottom: 2px solid var(--accent);
            padding-bottom: 10px;
            margin-top: 30px;
        }
        .form-container {
            background: var(--light);
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid var(--primary);
        }
        .form-group { margin-bottom: 15px; }
        label { font-weight: 600; color: var(--secondary); display: block; margin-bottom: 5px; }
        input[type="text"], input[type="date"], select, textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        .gender-option { display: flex; gap: 15px; }
        .gender-option label { display: flex; align-items: center; gap: 5px; }
        .btn {
            background: var(--primary);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        .btn-back {
            background: #6c757d;
            color: white;
            text-decoration: none;
            padding: 8px 15px;
            border-radius: 4px;
            margin-top: 10px;
            display: inline-block;
        }
        .error-message, .success-message {
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
            font-weight: bold;
        }
        .error-message {
            color: var(--danger);
            background: #f8d7da;
            border: 1px solid #f5c6cb;
        }
        .success-message {
            color: #155724;
            background: #d4edda;
            border: 1px solid #c3e6cb;
        }
    </style>
</head>
<body>
<div class="container">
    <header>
        <div class="logo">
            <i class="fas fa-baby"></i> <h1>POSYANDU BALITAKU</h1>
        </div>
        <div>Selamat Datang, Administrator</div>
    </header>

    <h2>👶 Tambah Data Balita Baru</h2>

    <?php 
    if (isset($_SESSION['success_message'])) {
        echo '<div class="success-message">' . $_SESSION['success_message'] . '</div>';
        unset($_SESSION['success_message']);
    }
    if (isset($_SESSION['error_message'])) {
        echo '<div class="error-message">' . $_SESSION['error_message'] . '</div>';
        unset($_SESSION['error_message']);
    }
    ?>

    <div class="form-container">
        <form method="POST">
            <div class="form-group">
                <label for="nama">Nama Balita:</label>
                <input type="text" name="nama" id="nama" required>
            </div>

            <div class="form-group">
                <label for="tgl_lahir">Tanggal Lahir:</label>
                <input type="date" name="tgl_lahir" id="tgl_lahir" required>
            </div>

            <div class="form-group">
                <label>Jenis Kelamin:</label>
                <div class="gender-option">
                    <label><input type="radio" name="jenis_kelamin" value="L" checked> Laki-laki</label>
                    <label><input type="radio" name="jenis_kelamin" value="P"> Perempuan</label>
                </div>
            </div>

            <div class="form-group">
                <label for="alamat">Alamat:</label>
                <textarea name="alamat" id="alamat" required></textarea>
            </div>

            <div class="form-group">
                <label for="id_orangtua">Orang Tua/Wali:</label>
                <select name="id_orangtua" id="id_orangtua" required>
                    <option value="">-- Pilih Orang Tua/Wali --</option>
                    <?php
                    if ($ortu && $ortu->num_rows > 0) {
                        $ortu->data_seek(0);
                        while ($row = $ortu->fetch_assoc()) {
                            echo '<option value="' . htmlspecialchars($row['id']) . '">' . htmlspecialchars($row['nama']) . '</option>';
                        }
                    } else {
                        echo '<option value="" disabled>Tidak ada data orang tua tersedia</option>';
                    }
                    ?>
                </select>
            </div>

            <button type="submit" name="simpan" class="btn">💾 Simpan Data</button>
        </form>
    </div>

    <a href="<?= $base_url_admin ?>balita.php" class="btn-back">← Kembali ke Data Balita</a>

    <footer style="text-align:center; margin-top:20px; font-size:12px; color:#666;">
        <p>Pos Pelayanan Terpadu (Posyandu) - Membangun Generasi Sehat sejak Dini | © <?= date('Y') ?> Sistem Informasi Posyandu.</p>
    </footer>
</div>
</body>
</html>
<?php if (isset($conn) && $conn) { $conn->close(); } ?>
