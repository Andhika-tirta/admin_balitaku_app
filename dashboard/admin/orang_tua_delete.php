<?php
include 'config/db.php';
session_start();
if (!isset($_SESSION['admin'])) exit;

$id = $_GET['id'];
$conn->query("DELETE FROM orang_tua WHERE id = $id");
header("Location: orang_tua.php");
