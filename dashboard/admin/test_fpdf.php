<?php
require_once 'fpdf/fpdf.php';

if (class_exists('FPDF')) {
    echo "✅ FPDF tersedia dan siap digunakan!";
} else {
    echo "❌ Kelas FPDF tidak ditemukan.";
}
