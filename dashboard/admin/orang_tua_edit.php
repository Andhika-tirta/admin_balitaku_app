<?php
$base_url = 'http://localhost/admin_balitaku_app/dashboard/admin/';
?>

<?php
include 'config/db.php';
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}

$id = intval($_GET['id']);
$data = $conn->query("SELECT * FROM orang_tua WHERE id = $id")->fetch_assoc();

if (!$data) {
    header("Location: orang_tua.php");
    exit;
}

if (isset($_POST['update'])) {
    $nama = $conn->real_escape_string($_POST['nama']);
    $nik = $conn->real_escape_string($_POST['nik']);
    $no_hp = $conn->real_escape_string($_POST['no_hp']);
    $alamat = $conn->real_escape_string($_POST['alamat']);

    $conn->query("UPDATE orang_tua SET 
        nama = '$nama', 
        nik = '$nik', 
        no_hp = '$no_hp', 
        alamat = '$alamat'
        WHERE id = $id");
    header("Location: orang_tua.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Orang Tua - Posyandu Sehat</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f0f9ff;
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
            background-color: #4CAF50;
            color: white;
            padding: 15px 20px;
            border-radius: 8px 8px 0 0;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        h1, h2 {
            color: #2c3e50;
        }
        h2 {
            border-bottom: 2px solid #4CAF50;
            padding-bottom: 10px;
            margin-top: 0;
        }
        .form-container {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #4CAF50;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #2c3e50;
        }
        input[type="text"], textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-family: inherit;
            font-size: 14px;
            box-sizing: border-box;
        }
        textarea {
            height: 100px;
            resize: vertical;
        }
        .btn {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s;
        }
        .btn:hover {
            background-color: #45a049;
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
        footer {
            text-align: center;
            margin-top: 20px;
            padding: 10px;
            color: #666;
            font-size: 12px;
        }
        .input-nik {
            letter-spacing: 2px;
            font-family: 'Courier New', monospace;
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
                <h1>POSYANDU SEHAT</h1>
            </div>
            <div>
                Selamat Datang, Admin
            </div>
        </header>
        
        <h2>👪 Edit Data Orang Tua/Wali</h2>
        
        <div class="info-box">
            <p>Silakan perbarui data orang tua/wali balita. Pastikan informasi yang dimasukkan akurat untuk keperluan pelayanan Posyandu.</p>
        </div>
        
        <div class="form-container">
            <form method="POST">
                <div class="form-group">
                    <label for="nama">Nama Lengkap:</label>
                    <input type="text" name="nama" id="nama" value="<?= htmlspecialchars($data['nama']) ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="nik">NIK (Nomor Induk Kependudukan):</label>
                    <input type="text" name="nik" id="nik" class="input-nik" value="<?= htmlspecialchars($data['nik']) ?>" 
                           required maxlength="16" minlength="16" pattern="[0-9]{16}" 
                           title="Harus 16 digit angka">
                </div>
                
                <div class="form-group">
                    <label for="no_hp">Nomor HP/WhatsApp:</label>
                    <input type="text" name="no_hp" id="no_hp" value="<?= htmlspecialchars($data['no_hp']) ?>">
                </div>
                
                <div class="form-group">
                    <label for="alamat">Alamat Lengkap:</label>
                    <textarea name="alamat" id="alamat"><?= htmlspecialchars($data['alamat']) ?></textarea>
                </div>
                
                <button type="submit" name="update" class="btn">💾 Simpan Perubahan</button>
            </form>
        </div>
        
        <a href="orang_tua.php" class="btn-back">← Kembali ke Data Orang Tua</a>
        
        <footer>
            <p>Posyandu Sehat - Layanan Kesehatan Ibu dan Anak | © <?= date('Y') ?> - Dibangun dengan ❤ untuk masyarakat</p>
        </footer>
    </div>
    
    <script>
        // Validasi NIK harus 16 digit angka
        document.getElementById('nik').addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '');
        });
        
        // Validasi nomor HP
        document.getElementById('no_hp').addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '');
        });
        
        // Validasi form sebelum submit
        document.querySelector('form').addEventListener('submit', function(e) {
            const nik = document.getElementById('nik').value;
            if (nik.length !== 16) {
                alert('NIK harus terdiri dari 16 digit angka!');
                e.preventDefault();
            }
        });
    </script>
</body>
</html>