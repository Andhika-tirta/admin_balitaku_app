<?php
session_start();
include 'config/paths.php'; // Sertakan file paths.php di sini

session_destroy(); // Hancurkan semua data session

// Arahkan kembali ke halaman login menggunakan $root_app_url
header("Location: " . $root_app_url . "login.php"); 
exit;
?>