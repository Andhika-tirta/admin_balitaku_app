<?php
session_start();
include '../../config/paths.php';
include '../../config/db.php'; // Pastikan koneksi database di-include

// Cek otorisasi
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'admin') {
    header("Location: " . $root_app_url . "login.php?error=unauthorized_access");
    exit;
}

$kader = null;
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $kader_id = $_GET['id'];

    if ($conn) {
        $sql = "SELECT id, nama, username, email FROM users WHERE id = ? AND role = 'kader'";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("i", $kader_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows === 1) {
                $kader = $result->fetch_assoc();
            } else {
                $error_message = "Kader tidak ditemukan atau bukan role kader.";
            }
            $stmt->close();
        } else {
            $error_message = "Gagal menyiapkan query: " . $conn->error;
        }
        $conn->close();
    } else {
        $error_message = "Koneksi database gagal.";
    }
} else {
    header("Location: " . $base_url_admin . "kelola_kader.php?error=invalid_id");
    exit;
}

if (!$kader && !isset($error_message)) {
    $error_message = "Data kader tidak ditemukan.";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Kader - POSYANDU BALITAKU</title>
    <link rel="stylesheet" href="<?= $root_app_url ?>assets/css/style.css"> <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .form-container {
            background-color: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.05);
            max-width: 500px;
            margin: 20px auto;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .btn-submit {
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1em;
        }
        .btn-submit:hover {
            background-color: #0056b3;
        }
        .btn-back {
            background-color: #6c757d;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1em;
            text-decoration: none;
            display: inline-block;
            margin-left: 10px;
        }
        .btn-back:hover {
            background-color: #5a6268;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h3>Admin Panel</h3>
            </div>
            <ul class="sidebar-links">
                <li><a href="<?= $base_url_admin ?>index.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="<?= $base_url_admin ?>kelola_kader.php"><i class="fas fa-users"></i> Kelola Kader</a></li>
                <li><a href="<?= $root_app_url ?>logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>

        <div class="main-content">
            <header class="navbar">
                <h2>Edit Data Kader</h2>
            </header>
            <main class="content">
                <?php if (isset($error_message)): ?>
                    <p class="error-message"><?php echo htmlspecialchars($error_message); ?></p>
                <?php elseif ($kader): ?>
                    <div class="form-container">
                        <form action="<?= $base_url_admin ?>proses_kader.php" method="POST">
                            <input type="hidden" name="action" value="edit">
                            <input type="hidden" name="id" value="<?= htmlspecialchars($kader['id']) ?>">
                            <div class="form-group">
                                <label for="nama">Nama Lengkap:</label>
                                <input type="text" id="nama" name="nama" value="<?= htmlspecialchars($kader['nama']) ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="username">Username:</label>
                                <input type="text" id="username" name="username" value="<?= htmlspecialchars($kader['username']) ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="email">Email:</label>
                                <input type="email" id="email" name="email" value="<?= htmlspecialchars($kader['email']) ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="password">Password (kosongkan jika tidak ingin mengubah):</label>
                                <input type="password" id="password" name="password">
                            </div>
                            <button type="submit" class="btn-submit"><i class="fas fa-save"></i> Simpan Perubahan</button>
                            <a href="<?= $base_url_admin ?>kelola_kader.php" class="btn-back"><i class="fas fa-arrow-left"></i> Kembali</a>
                        </form>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>
</body>
</html>