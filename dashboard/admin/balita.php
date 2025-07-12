<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include '../../config/paths.php';
include '../../config/db.php';

if (!isset($_SESSION['role'])) {
    header("Location: " . $root_app_url . "login.php");
    exit;
}

if (!isset($conn) || !$conn) {
    die("Koneksi database gagal. Periksa konfigurasi.");
}

// Siapkan query dengan menambahkan kolom 'rt'
$sql = "SELECT b.id, b.nama, b.tgl_lahir, b.jenis_kelamin, b.alamat, b.rt, o.nama AS nama_ortu
        FROM balita b
        JOIN orang_tua o ON b.id_orangtua = o.id";

$result = null;

if ($_SESSION['role'] === 'ortu') {
    if (!isset($_SESSION['user_id'])) {
        die("ID pengguna tidak ditemukan.");
    }

    $stmt = $conn->prepare($sql . " WHERE b.id_orangtua = ?");
    if (!$stmt) die("Gagal menyiapkan statement: " . $conn->error);

    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();

} elseif ($_SESSION['role'] === 'admin') {
    $result = $conn->query($sql);
    if (!$result) die("Query gagal: " . $conn->error);
} else {
    $_SESSION['error_message'] = "Akses tidak diizinkan.";
    header("Location: " . $root_app_url . "login.php");
    exit;
}

if (!$result) {
    die("Tidak ada hasil. Periksa logika peran.");
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Data Balita - POSYANDU BALITAKU</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary: #2a9d8f;
            --secondary: #264653;
            --accent: #e9c46a;
            --light: #f8f9fa;
            --danger: #e76f51;
            --info-blue: #2196F3;
            --success-green: #28a745;
            --warning-orange: #ffc107;
        }
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f1f8f7;
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
        h1, h2 {
            margin: 0;
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
        }
        .export-buttons {
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .btn {
            padding: 8px 15px;
            background-color: var(--primary);
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-size: 14px;
            white-space: nowrap;
            transition: background-color 0.3s;
        }
        .btn:hover { background-color: #217a70; }
        .btn-add { background-color: var(--success-green); }
        .btn-add:hover { background-color: #218838; }
        .btn-edit { background-color: var(--info-blue); }
        .btn-edit:hover { background-color: #0d6efd; }
        .btn-delete { background-color: var(--danger); }
        .btn-delete:hover { background-color: #dc3545; }
        .btn-back-dashboard { background-color: #6c757d; }
        .btn-back-dashboard:hover { background-color: #5a6268; }

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
        tr:nth-child(even) { background-color: #f2f2f2; }
        tr:hover { background-color: #e6f7e6; }

        .jk-laki { color: var(--info-blue); font-weight: bold; }
        .jk-perempuan { color: #E91E63; font-weight: bold; }

        footer {
            text-align: center;
            margin-top: 20px;
            padding: 10px;
            color: #666;
            font-size: 12px;
        }
        .notification {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 5px;
            font-weight: bold;
        }
        .notification.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .notification.error {
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
            <i class="fas fa-baby-carriage"></i>
            <h1>POSYANDU BALITAKU</h1>
        </div>
        <div>
            Selamat Datang, <?= htmlspecialchars($_SESSION['role'] === 'admin' ? 'Administrator' : 'Orang Tua') ?>
        </div>
    </header>

    <h2>📊 Data Balita Terdaftar</h2>
    <p>Berikut adalah data balita yang terdaftar di Posyandu Balitaku:</p>

    <?php
    if (isset($_SESSION['success_message'])) {
        echo '<div class="notification success">' . htmlspecialchars($_SESSION['success_message']) . '</div>';
        unset($_SESSION['success_message']);
    }
    if (isset($_SESSION['error_message'])) {
        echo '<div class="notification error">' . htmlspecialchars($_SESSION['error_message']) . '</div>';
        unset($_SESSION['error_message']);
    }
    ?>

    <div class="export-buttons">
        <?php if ($_SESSION['role'] === 'admin'): ?>
            <a href="<?= $root_app_url ?>export/export_excel.php?tipe=balita" class="btn">📄 Export ke Excel</a>
            <a href="<?= $root_app_url ?>export/export_pdf.php?tipe=balita" target="_blank" class="btn">📑 Export ke PDF</a>
            <a href="<?= $base_url_admin ?>index.php" class="btn btn-back-dashboard">⬅️ Kembali ke Dashboard Admin</a>
        <?php else: ?>
            <a href="<?= $root_app_url ?>dashboard/ortu/index.php" class="btn btn-back-dashboard">⬅️ Kembali ke Dashboard</a>
        <?php endif; ?>
    </div>

    <table>
        <thead>
            <tr>
                <th>Nama Balita</th>
                <th>Tanggal Lahir</th>
                <th>Jenis Kelamin</th>
                <th>Orang Tua</th>
                <th>Alamat</th>
                <th>RT</th> <?php if ($_SESSION['role'] === 'admin'): ?>
                    <th>Aksi</th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
        <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['nama']) ?></td>
                    <td><?= date('d-m-Y', strtotime($row['tgl_lahir'])) ?></td>
                    <td><?= $row['jenis_kelamin'] === 'L'
                        ? '<span class="jk-laki">Laki-laki</span>'
                        : '<span class="jk-perempuan">Perempuan</span>' ?></td>
                    <td><?= htmlspecialchars($row['nama_ortu']) ?></td>
                    <td><?= htmlspecialchars($row['alamat']) ?></td>
                    <td><?= htmlspecialchars($row['rt']) ?></td> <?php if ($_SESSION['role'] === 'admin'): ?>
                        <td>
                            <a href="<?= $base_url_admin ?>balita_edit.php?id=<?= (int)$row['id'] ?>" class="btn btn-edit">✏️ Edit</a>
                            <a href="<?= $base_url_admin ?>balita_delete.php?id=<?= (int)$row['id'] ?>" class="btn btn-delete" onclick="return confirm('Yakin ingin menghapus data ini?');">🗑️ Hapus</a>
                        </td>
                    <?php endif; ?>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="<?= ($_SESSION['role'] === 'admin') ? '7' : '6' ?>">Tidak ada data balita ditemukan.</td> </tr>
        <?php endif; ?>
        </tbody>
    </table>

    <footer>
        <p>Pos Pelayanan Terpadu (Posyandu) - Membangun Generasi Sehat sejak Dini | © <?= date('Y') ?> Sistem Informasi Posyandu.</p>
    </footer>
</div>
</body>
</html>

<?php
if (isset($conn)) {
    $conn->close();
}
?>