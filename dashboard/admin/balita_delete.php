<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include '../../config/paths.php';
include '../../config/db.php';

// Pastikan hanya admin yang bisa mengakses skrip ini
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    $_SESSION['error_message'] = "Anda tidak memiliki izin untuk melakukan aksi ini.";
    header("Location: " . $root_app_url . "login.php");
    exit;
}

if (!isset($conn) || !$conn) {
    $_SESSION['error_message'] = "Koneksi database gagal.";
    header("Location: " . $base_url_admin . "balita.php");
    exit;
}

if (isset($_GET['id'])) {
    $id_balita = filter_var($_GET['id'], FILTER_VALIDATE_INT);

    if ($id_balita === false) {
        $_SESSION['error_message'] = "ID balita tidak valid untuk dihapus.";
        header("Location: " . $base_url_admin . "balita.php");
        exit;
    }

    // Gunakan Prepared Statement untuk menghapus data
    $stmt_delete = $conn->prepare("DELETE FROM balita WHERE id = ?");
    if ($stmt_delete === false) {
        $_SESSION['error_message'] = "Gagal menyiapkan query delete: " . $conn->error;
        header("Location: " . $base_url_admin . "balita.php");
        exit;
    }

    $stmt_delete->bind_param("i", $id_balita);

    if ($stmt_delete->execute()) {
        $_SESSION['success_message'] = "Data balita berhasil dihapus.";
    } else {
        // Cek apakah error karena foreign key constraint
        if ($conn->errno == 1451) { // MySQL error code for foreign key constraint fail
            $_SESSION['error_message'] = "Gagal menghapus data balita karena masih terkait dengan data pemeriksaan atau imunisasi lainnya. Harap hapus data terkait terlebih dahulu.";
        } else {
            $_SESSION['error_message'] = "Gagal menghapus data balita: " . $stmt_delete->error;
        }
    }
    $stmt_delete->close();
} else {
    $_SESSION['error_message'] = "ID balita tidak diberikan untuk dihapus.";
}

// Tutup koneksi database sebelum redirect
if (isset($conn) && $conn) {
    $conn->close();
}

// Redirect kembali ke halaman daftar balita
header("Location: " . $base_url_admin . "balita.php");
exit;
?>