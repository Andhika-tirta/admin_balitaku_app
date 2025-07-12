<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include '../../config/paths.php';
include '../../config/db.php';

// Cek otorisasi
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'admin') {
    header("Location: " . $root_app_url . "login.php?error=unauthorized_access");
    exit;
}

// Pastikan koneksi database berhasil
if (!isset($conn) || !$conn) {
    header("Location: " . $base_url_admin . "kelola_kader.php?status=error_db_conn");
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$redirect_url = $base_url_admin . "kelola_kader.php";
$status_param = '';

switch ($action) {
    case 'add':
        $nama = $_POST['nama'] ?? '';
        $username = $_POST['username'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? ''; // Plaintext password

        if (empty($nama) || empty($username) || empty($email) || empty($password)) {
            $status_param = 'error_empty_fields';
        } else {
            // Hash password sebelum menyimpan
            $hashed_password = password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);
            $role = 'kader';

            $sql = "INSERT INTO users (nama, username, email, password, role) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);

            if ($stmt) {
                $stmt->bind_param("sssss", $nama, $username, $email, $hashed_password, $role);
                if ($stmt->execute()) {
                    $status_param = 'added';
                } else {
                    $status_param = 'error_add'; // Bisa jadi karena email/username duplikat
                }
                $stmt->close();
            } else {
                $status_param = 'error_prepare';
            }
        }
        break;

    case 'edit':
        $id = $_POST['id'] ?? '';
        $nama = $_POST['nama'] ?? '';
        $username = $_POST['username'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? ''; // Plaintext password (opsional)

        if (empty($id) || empty($nama) || empty($username) || empty($email)) {
            $status_param = 'error_empty_fields';
        } else {
            $sql = "UPDATE users SET nama = ?, username = ?, email = ? ";
            $params = [$nama, $username, $email];
            $types = "sss";

            if (!empty($password)) {
                $hashed_password = password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);
                $sql .= ", password = ? ";
                $params[] = $hashed_password;
                $types .= "s";
            }
            
            $sql .= " WHERE id = ? AND role = 'kader'";
            $params[] = $id;
            $types .= "i";

            $stmt = $conn->prepare($sql);

            if ($stmt) {
                // Gunakan call_user_func_array untuk bind_param dengan jumlah parameter dinamis
                // Source: https://www.php.net/manual/en/mysqli-stmt.bind-param.php#96057
                array_unshift($params, $types); // Tambahkan tipe sebagai elemen pertama array
                call_user_func_array([$stmt, 'bind_param'], $params);

                if ($stmt->execute()) {
                    $status_param = 'updated';
                } else {
                    $status_param = 'error_update';
                }
                $stmt->close();
            } else {
                $status_param = 'error_prepare';
            }
        }
        break;

    case 'delete':
        $id = $_GET['id'] ?? '';

        if (empty($id) || !is_numeric($id)) {
            $status_param = 'error_invalid_id';
        } else {
            $sql = "DELETE FROM users WHERE id = ? AND role = 'kader'";
            $stmt = $conn->prepare($sql);

            if ($stmt) {
                $stmt->bind_param("i", $id);
                if ($stmt->execute()) {
                    $status_param = 'deleted';
                } else {
                    $status_param = 'error_delete';
                }
                $stmt->close();
            } else {
                $status_param = 'error_prepare';
            }
        }
        break;

    default:
        $status_param = 'error_invalid_action';
        break;
}

// Tutup koneksi database setelah semua operasi selesai
if (isset($conn) && $conn) {
    $conn->close();
}

header("Location: " . $redirect_url . "?status=" . $status_param);
exit;
?>