<?php
session_start();
include '../../config/paths.php';
include '../../config/db.php';

// Cek otorisasi
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'admin') {
    header("Location: " . $root_app_url . "login.php?error=unauthorized_access");
    exit;
}

// Ambil data kader dari database
$kader_data = [];
if ($conn) {
    $sql = "SELECT id, nama, email, username FROM users WHERE role = 'kader'";
    $result = $conn->query($sql);

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $kader_data[] = $row;
        }
        $result->free();
    } else {
        $error_message = "Error mengambil data kader: " . $conn->error;
    }
    $conn->close();
} else {
    $error_message = "Koneksi database gagal.";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Kader - POSYANDU BALITAKU</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary: #4CAF50;
            --primary-dark: #388E3C;
            --secondary: #2196F3;
            --danger: #E53935;
            --light: #F5F5F5;
            --dark: #212121;
            --gray: #757575;
            --border-radius: 8px;
            --shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f5f9fc;
            color: var(--dark);
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e0e0e0;
        }

        .header h1 {
            font-size: 1.8rem;
            color: var(--primary-dark);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .header-actions {
            display: flex;
            gap: 15px;
        }

        .card {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            padding: 25px;
            margin-bottom: 25px;
        }

        .table-container {
            overflow-x: auto;
            margin-top: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }

        th {
            background-color: var(--light);
            font-weight: 600;
            color: var(--dark);
            position: sticky;
            top: 0;
        }

        tr:hover {
            background-color: rgba(0, 0, 0, 0.02);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            padding: 8px 16px;
            border-radius: var(--border-radius);
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            gap: 8px;
            border: none;
        }

        .btn i {
            font-size: 0.9rem;
        }

        .btn-primary {
            background-color: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
        }

        .btn-secondary {
            background-color: var(--secondary);
            color: white;
        }

        .btn-secondary:hover {
            background-color: #1976D2;
        }

        .btn-danger {
            background-color: var(--danger);
            color: white;
        }

        .btn-danger:hover {
            background-color: #C62828;
        }

        .btn-outline {
            background-color: transparent;
            border: 1px solid var(--gray);
            color: var(--dark);
        }

        .btn-outline:hover {
            background-color: rgba(0, 0, 0, 0.05);
        }

        .action-buttons {
            display: flex;
            gap: 8px;
        }

        .alert {
            padding: 12px 16px;
            border-radius: var(--border-radius);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success {
            background-color: #E8F5E9;
            color: #2E7D32;
            border-left: 4px solid #4CAF50;
        }

        .alert-error {
            background-color: #FFEBEE;
            color: #C62828;
            border-left: 4px solid #E53935;
        }

        .alert i {
            font-size: 1.2rem;
        }

        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 20px;
            color: var(--gray);
            font-size: 0.9rem;
        }

        .breadcrumb a {
            color: var(--primary);
            text-decoration: none;
        }

        .breadcrumb a:hover {
            text-decoration: underline;
        }

        .breadcrumb .separator {
            color: var(--gray);
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .header-actions {
                width: 100%;
                flex-direction: column;
                gap: 10px;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
            
            .action-buttons {
                flex-direction: column;
                gap: 5px;
            }
            
            th, td {
                padding: 8px 10px;
                font-size: 0.85rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="breadcrumb">
            <a href="<?= $base_url_admin ?>index.php"><i class="fas fa-home"></i> Dashboard</a>
            <span class="separator">/</span>
            <span>Kelola Kader</span>
        </div>

        <div class="header">
            <h1><i class="fas fa-users-cog"></i> Kelola Data Kader</h1>
            <div class="header-actions">
                  <a href="<?= $base_url_admin ?>index.php" class="btn back-to-dashboard">⬅️ Kembali ke Dashboard</a>
                </a>
                <a href="<?= $base_url_admin ?>tambah_kader.php" class="btn btn-primary">
                    <i class="fas fa-user-plus"></i> Tambah Kader
                </a>
            </div>
        </div>
        
        <?php if (isset($_GET['status'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php 
                    if ($_GET['status'] == 'added') echo "Kader berhasil ditambahkan!";
                    elseif ($_GET['status'] == 'updated') echo "Data kader berhasil diperbarui!";
                    elseif ($_GET['status'] == 'deleted') echo "Kader berhasil dihapus!";
                ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?= htmlspecialchars($error_message) ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nama</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($kader_data)): ?>
                            <?php foreach ($kader_data as $kader): ?>
                            <tr>
                                <td><?= htmlspecialchars($kader['id']) ?></td>
                                <td><?= htmlspecialchars($kader['nama']) ?></td>
                                <td><?= htmlspecialchars($kader['username']) ?></td>
                                <td><?= htmlspecialchars($kader['email']) ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="<?= $base_url_admin ?>edit_kader.php?id=<?= $kader['id'] ?>" class="btn btn-secondary">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <button class="btn btn-danger" onclick="confirmDelete(<?= $kader['id'] ?>)">
                                            <i class="fas fa-trash-alt"></i> Hapus
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 20px; color: var(--gray);">
                                    <i class="fas fa-info-circle" style="font-size: 1.2rem;"></i> Tidak ada data kader
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        function confirmDelete(id) {
            if (confirm("Apakah Anda yakin ingin menghapus kader ini?")) {
                window.location.href = '<?= $base_url_admin ?>proses_kader.php?action=delete&id=' + id;
            }
        }
    </script>
</body>
</html>