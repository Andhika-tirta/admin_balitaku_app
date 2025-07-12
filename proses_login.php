<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'config/db.php';   
include 'config/paths.php'; 

// Variabel untuk menyimpan pesan error
$error_redirect_param = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    // Cek kelengkapan input
    if (empty($email) || empty($password)) {
        $error_redirect_param = 'empty_fields';
        header("Location: " . $root_app_url . "login.php?error=" . $error_redirect_param);
        exit;
    }

    // Cek koneksi database
    if (!isset($conn) || !$conn) {
        $error_redirect_param = 'db_connection_failed';
        header("Location: " . $root_app_url . "login.php?error=" . $error_redirect_param);
        exit;
    }

    // Menggunakan Prepared Statement untuk mencari pengguna berdasarkan EMAIL
    $sql = "SELECT id, nama, username, email, password, role FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);

    // Cek jika prepared statement gagal
    if ($stmt === false) {
        $error_redirect_param = 'db_error';
        $conn->close(); // Tutup koneksi karena error di prepare
        header("Location: " . $root_app_url . "login.php?error=" . $error_redirect_param);
        exit;
    }

    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // Verifikasi password yang di-hash
        if (password_verify($password, $user['password'])) {
            // Login berhasil
            $_SESSION['loggedin'] = true;
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['nama'] = $user['nama'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];

            // Tutup statement dan koneksi sebelum redirect sukses
            $stmt->close();
            $conn->close();

            // Redirect berdasarkan role
            switch ($user['role']) {
                case 'admin':
                    header("Location: " . $base_url_admin . "index.php");
                    break;
                case 'kader':
                    header("Location: " . $base_url_kader . "index.php");
                    break;
                case 'ortu':
                    header("Location: " . $base_url_ortu . "index.php");
                    break;
                default:
                    // Jika role tidak dikenali, logout dan redirect dengan error
                    session_destroy();
                    $error_redirect_param = 'invalid_role';
                    header("Location: " . $root_app_url . "login.php?error=" . $error_redirect_param);
                    break;
            }
            exit; // Sangat penting untuk menghentikan script setelah header()
        } else {
            // Password salah
            $error_redirect_param = 'invalid_credentials';
        }
    } else {
        // Email tidak ditemukan
        $error_redirect_param = 'invalid_credentials';
    }

    // Jika login gagal (password salah atau email tidak ditemukan)
    // Tutup statement dan koneksi sebelum redirect error
    if (isset($stmt) && $stmt) { 
        $stmt->close();
    }
    if (isset($conn) && $conn) {
        $conn->close();
    }
    header("Location: " . $root_app_url . "login.php?error=" . $error_redirect_param);
    exit;

} else {
    // Jika diakses langsung tanpa POST request
    header("Location: " . $root_app_url . "login.php");
    exit;
}
?>