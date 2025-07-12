<?php
include 'config/db.php';
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}

$id = $_GET['id'];
$conn->query("DELETE FROM pemeriksaan WHERE id = $id");
header("Location: pemeriksaan.php");
