<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

include '../../config/paths.php';
include '../../config/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    $_SESSION['error_message'] = "Anda tidak memiliki izin untuk mengakses halaman ini.";
    header("Location: " . $root_app_url . "login.php");
    exit;
}

if (!isset($conn) || !$conn) {
    $_SESSION['error_message'] = "Koneksi database gagal.";
    header("Location: " . $base_url_admin . "balita.php");
    exit;
}

$id_balita = null;
$balita_data = [];
$error_message = '';

if (isset($_GET['id'])) {
    $id_balita = filter_var($_GET['id'], FILTER_VALIDATE_INT);
    if ($id_balita === false) {
        $_SESSION['error_message'] = "ID balita tidak valid.";
        header("Location: " . $base_url_admin . "balita.php");
        exit;
    }

    // --- START CHANGE ---
    // Added 'rt' to the SELECT statement
    $stmt_select = $conn->prepare("SELECT id, nama, tgl_lahir, jenis_kelamin, alamat, rt, id_orangtua FROM balita WHERE id = ?");
    // --- END CHANGE ---
    $stmt_select->bind_param("i", $id_balita);
    $stmt_select->execute();
    $result_select = $stmt_select->get_result();

    if ($result_select->num_rows > 0) {
        $balita_data = $result_select->fetch_assoc();
    } else {
        $_SESSION['error_message'] = "Data balita tidak ditemukan.";
        header("Location: " . $base_url_admin . "balita.php");
        exit;
    }
    $stmt_select->close();
} else {
    $_SESSION['error_message'] = "ID balita tidak diberikan.";
    header("Location: " . $base_url_admin . "balita.php");
    exit;
}

$stmt_ortu = $conn->prepare("SELECT id, nama FROM orang_tua ORDER BY nama ASC");
$stmt_ortu->execute();
$ortu_list = $stmt_ortu->get_result();
$stmt_ortu->close();

if (isset($_POST['simpan'])) {
    $nama = trim($_POST['nama']);
    $tgl_lahir = trim($_POST['tgl_lahir']);
    $jk = trim($_POST['jenis_kelamin']);
    $alamat = trim($_POST['alamat']);
    $rt = trim($_POST['rt']); // Get RT from the form
    $id_orangtua = filter_var(trim($_POST['id_orangtua']), FILTER_VALIDATE_INT);
    $balita_id_to_update = filter_var(trim($_POST['id_balita']), FILTER_VALIDATE_INT);

    // --- START CHANGE ---
    // Added $rt to the empty check
    if (empty($nama) || empty($tgl_lahir) || empty($jk) || empty($alamat) || empty($rt) || $id_orangtua === false || $balita_id_to_update === false) {
        $error_message = "Semua field wajib diisi.";
    } elseif (!in_array($jk, ['L', 'P'])) {
        $error_message = "Jenis kelamin tidak valid.";
    } else {
        // Added 'rt = ?' to the UPDATE statement and 's' to bind_param
        $stmt_update = $conn->prepare("UPDATE balita SET nama = ?, tgl_lahir = ?, jenis_kelamin = ?, alamat = ?, rt = ?, id_orangtua = ? WHERE id = ?");
        $stmt_update->bind_param("sssssii", $nama, $tgl_lahir, $jk, $alamat, $rt, $id_orangtua, $balita_id_to_update);
    // --- END CHANGE ---
        if ($stmt_update->execute()) {
            $_SESSION['success_message'] = "Data balita berhasil diperbarui!";
            $stmt_update->close();
            header("Location: " . $base_url_admin . "balita.php");
            exit;
        } else {
            $error_message = "Gagal memperbarui data: " . $stmt_update->error;
        }
        $stmt_update->close();
    }

    $balita_data['nama'] = $nama;
    $balita_data['tgl_lahir'] = $tgl_lahir;
    $balita_data['jenis_kelamin'] = $jk;
    $balita_data['alamat'] = $alamat;
    $balita_data['rt'] = $rt; // Update balita_data with the submitted RT
    $balita_data['id_orangtua'] = $id_orangtua;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Data Balita - POSYANDU BALITAKU</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary: #4CAF50;
            --primary-dark: #388E3C;
            --secondary: #2196F3;
            --light: #f8f9fa;
            --dark: #2c3e50;
            --danger: #e74c3c;
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
            background-color: #f0f9ff;
            color: var(--dark);
            line-height: 1.6;
        }

        .container {
            max-width: 800px;
            margin: 30px auto;
            background: white;
            padding: 30px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            position: relative;
        }

        .logo {
            width: 100px;
            height: 100px;
            margin: 0 auto 15px;
            display: block;
            object-fit: contain;
        }

        h1 {
            color: var(--primary);
            font-size: 24px;
            margin-bottom: 10px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--dark);
        }

        input[type="text"],
        input[type="date"],
        input[type="number"], /* Added for RT field */
        select,
        textarea {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: var(--border-radius);
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
            transition: all 0.3s;
        }

        input[type="text"]:focus,
        input[type="date"]:focus,
        input[type="number"]:focus, /* Added for RT field */
        select:focus,
        textarea:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.2);
        }

        textarea {
            resize: vertical;
            min-height: 100px;
        }

        .gender-options {
            display: flex;
            gap: 20px;
            margin-top: 10px;
        }

        .gender-option {
            display: flex;
            align-items: center;
        }

        .gender-option input {
            margin-right: 8px;
        }

        .btn {
            background-color: var(--primary);
            color: white;
            border: none;
            padding: 12px 25px;
            font-size: 16px;
            font-weight: 500;
            border-radius: var(--border-radius);
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
        }

        .btn i {
            font-size: 14px;
        }

        .error-message {
            color: var(--danger);
            background-color: rgba(231, 76, 60, 0.1);
            padding: 12px 15px;
            border-radius: var(--border-radius);
            margin-bottom: 20px;
            border-left: 4px solid var(--danger);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .error-message i {
            font-size: 18px;
        }

        @media (max-width: 768px) {
            .container {
                margin: 15px;
                padding: 20px;
            }

            .logo {
                width: 80px;
                height: 80px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-baby"></i> Edit Data Balita</h1>
            <p>Perbarui informasi data balita</p>
        </div>

        <?php if (!empty($error_message)): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i>
                <span><?= htmlspecialchars($error_message) ?></span>
            </div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="id_balita" value="<?= htmlspecialchars($balita_data['id']) ?>">

            <div class="form-group">
                <label for="nama"><i class="fas fa-user"></i> Nama Balita:</label>
                <input type="text" id="nama" name="nama" value="<?= htmlspecialchars($balita_data['nama']) ?>" required>
            </div>

            <div class="form-group">
                <label for="tgl_lahir"><i class="fas fa-calendar-alt"></i> Tanggal Lahir:</label>
                <input type="date" id="tgl_lahir" name="tgl_lahir" value="<?= htmlspecialchars($balita_data['tgl_lahir']) ?>" required>
            </div>

            <div class="form-group">
                <label><i class="fas fa-venus-mars"></i> Jenis Kelamin:</label>
                <div class="gender-options">
                    <label class="gender-option">
                        <input type="radio" name="jenis_kelamin" value="L" <?= $balita_data['jenis_kelamin'] == 'L' ? 'checked' : '' ?> required>
                        Laki-laki
                    </label>
                    <label class="gender-option">
                        <input type="radio" name="jenis_kelamin" value="P" <?= $balita_data['jenis_kelamin'] == 'P' ? 'checked' : '' ?> required>
                        Perempuan
                    </label>
                </div>
            </div>

            <div class="form-group">
                <label for="alamat"><i class="fas fa-home"></i> Alamat:</label>
                <textarea id="alamat" name="alamat" required><?= htmlspecialchars($balita_data['alamat']) ?></textarea>
            </div>

            <div class="form-group">
                <label for="rt"><i class="fas fa-road"></i> RT:</label>
                <input type="number" id="rt" name="rt" value="<?= htmlspecialchars($balita_data['rt'] ?? '') ?>" required min="0" max="999">
            </div>
            <div class="form-group">
                <label for="id_orangtua"><i class="fas fa-users"></i> Orang Tua:</label>
                <select id="id_orangtua" name="id_orangtua" required>
                    <option value="">-- Pilih Orang Tua --</option>
                    <?php
                    $ortu_list->data_seek(0);
                    while ($row_ortu = $ortu_list->fetch_assoc()):
                    ?>
                    <option value="<?= $row_ortu['id'] ?>" <?= $row_ortu['id'] == $balita_data['id_orangtua'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($row_ortu['nama']) ?>
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <button type="submit" name="simpan" class="btn">
                <i class="fas fa-save"></i> Simpan Perubahan
            </button>
        </form>
    </div>
</body>
</html>

<?php
if (isset($conn)) $conn->close();
?>