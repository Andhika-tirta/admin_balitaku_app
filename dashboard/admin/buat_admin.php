<?php
include 'config/db.php';

// Ubah data di bawah sesuai kebutuhan
$nama = 'Admin Posyandu';
$email = 'admin@posyandu.com';
$password_plain = 'admin123';
$role = 'admin';

// Hash password
$password_hash = password_hash($password_plain, PASSWORD_DEFAULT);

// Cek apakah email sudah ada
$cek = $conn->query("SELECT * FROM users WHERE email = '$email'");
if ($cek->num_rows > 0) {
    echo "Email sudah ada di database.";
} else {
    $sql = "INSERT INTO users (nama, email, password, role) 
            VALUES ('$nama', '$email', '$password_hash', '$role')";
    if ($conn->query($sql)) {
        echo "✅ Admin berhasil ditambahkan!";
    } else {
        echo "❌ Gagal menambahkan admin: " . $conn->error;
    }
}
?>
